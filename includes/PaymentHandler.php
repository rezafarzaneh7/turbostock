<?php
/**
 * HStore - Payment Handler
 * Manages wallet operations, deposits, and TRON payments
 */

require_once __DIR__ . '/TronAPI.php';

class PaymentHandler {
    private $db;
    private $tron;
    
    public function __construct() {
        $this->db = db();
        $this->tron = tronApi();
    }
    
    /**
     * Get or create user wallet
     */
    public function getWallet($userId) {
        $wallet = $this->db->fetch("SELECT * FROM wallets WHERE user_id = ?", [$userId]);
        
        if (!$wallet) {
            $this->db->insert('wallets', ['user_id' => $userId]);
            $wallet = $this->db->fetch("SELECT * FROM wallets WHERE user_id = ?", [$userId]);
        }
        
        return $wallet;
    }
    
    /**
     * Get wallet balance
     */
    public function getBalance($userId) {
        $wallet = $this->getWallet($userId);
        return (float) $wallet['balance'];
    }
    
    /**
     * Create deposit request
     */
    public function createDepositRequest($userId, $amount) {
        // Generate new TRON wallet for this deposit
        $walletData = $this->tron->generateWallet();
        
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . PAYMENT_EXPIRY_HOURS . ' hours'));
        
        $paymentId = $this->db->insert('tron_payments', [
            'user_id' => $userId,
            'wallet_address' => $walletData['address'],
            'private_key_encrypted' => $walletData['private_key'],
            'payment_type' => 'deposit',
            'expected_amount' => $amount,
            'status' => 'pending',
            'expires_at' => $expiresAt
        ]);
        
        return [
            'payment_id' => $paymentId,
            'wallet_address' => $walletData['address'],
            'amount' => $amount,
            'expires_at' => $expiresAt,
            'qr_code' => $this->tron->generatePaymentQR($walletData['address'], $amount)
        ];
    }
    
    /**
     * Create verification payment request
     */
    public function createVerificationRequest($sellerId) {
        $seller = $this->db->fetch("SELECT * FROM sellers WHERE id = ?", [$sellerId]);
        if (!$seller) {
            throw new Exception("Seller not found");
        }
        
        // Check for existing pending verification
        $existing = $this->db->fetch(
            "SELECT * FROM seller_verifications WHERE seller_id = ? AND status = 'pending'",
            [$sellerId]
        );
        
        if ($existing) {
            // Return existing request if not expired
            if (strtotime($existing['expires_at']) > time()) {
                $payment = $this->db->fetch(
                    "SELECT * FROM tron_payments WHERE reference_type = 'verification' AND reference_id = ?",
                    [$existing['id']]
                );
                
                return [
                    'verification_id' => $existing['id'],
                    'payment_id' => $payment['id'] ?? null,
                    'wallet_address' => $existing['tron_wallet_address'],
                    'amount' => $existing['amount_required'],
                    'expires_at' => $existing['expires_at'],
                    'qr_code' => $this->tron->generatePaymentQR($existing['tron_wallet_address'], $existing['amount_required'])
                ];
            }
            
            // Mark expired
            $this->db->update('seller_verifications', ['status' => 'expired'], 'id = ?', [$existing['id']]);
        }
        
        // Generate new wallet
        $walletData = $this->tron->generateWallet();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . PAYMENT_EXPIRY_HOURS . ' hours'));
        $verificationFee = getSetting('verification_fee', VERIFICATION_FEE);
        
        // Create verification record
        $verificationId = $this->db->insert('seller_verifications', [
            'seller_id' => $sellerId,
            'tron_wallet_address' => $walletData['address'],
            'amount_required' => $verificationFee,
            'status' => 'pending',
            'expires_at' => $expiresAt
        ]);
        
        // Create payment record
        $paymentId = $this->db->insert('tron_payments', [
            'user_id' => $seller['user_id'],
            'wallet_address' => $walletData['address'],
            'private_key_encrypted' => $walletData['private_key'],
            'payment_type' => 'verification',
            'expected_amount' => $verificationFee,
            'status' => 'pending',
            'reference_type' => 'verification',
            'reference_id' => $verificationId,
            'expires_at' => $expiresAt
        ]);
        
        // Update seller status to pending
        $this->db->update('sellers', ['verification_status' => 'pending'], 'id = ?', [$sellerId]);
        
        return [
            'verification_id' => $verificationId,
            'payment_id' => $paymentId,
            'wallet_address' => $walletData['address'],
            'amount' => $verificationFee,
            'expires_at' => $expiresAt,
            'qr_code' => $this->tron->generatePaymentQR($walletData['address'], $verificationFee)
        ];
    }
    
    /**
     * Check and process pending payments
     */
    public function checkPendingPayments() {
        $pendingPayments = $this->db->fetchAll(
            "SELECT * FROM tron_payments WHERE status IN ('pending', 'confirming') AND expires_at > NOW()"
        );
        
        $processed = 0;
        
        foreach ($pendingPayments as $payment) {
            $result = $this->checkPayment($payment['id']);
            if ($result['confirmed']) {
                $processed++;
            }
        }
        
        // Mark expired payments
        $this->db->query(
            "UPDATE tron_payments SET status = 'expired' WHERE status = 'pending' AND expires_at <= NOW()"
        );
        
        return $processed;
    }
    
    /**
     * Check single payment
     */
    public function checkPayment($paymentId) {
        $payment = $this->db->fetch("SELECT * FROM tron_payments WHERE id = ?", [$paymentId]);
        
        if (!$payment) {
            return ['found' => false, 'error' => 'Payment not found'];
        }
        
        if ($payment['status'] === 'confirmed') {
            return ['found' => true, 'confirmed' => true, 'already_processed' => true];
        }
        
        if ($payment['status'] === 'expired' || $payment['status'] === 'failed') {
            return ['found' => false, 'error' => 'Payment ' . $payment['status']];
        }
        
        // Check blockchain for payment
        $createdTimestamp = strtotime($payment['created_at']);
        $result = $this->tron->checkPayment(
            $payment['wallet_address'],
            $payment['expected_amount'],
            $createdTimestamp
        );
        
        if ($result['found']) {
            // Payment found, process it
            return $this->processPayment($payment, $result);
        }
        
        return ['found' => false, 'confirmed' => false];
    }
    
    /**
     * Process confirmed payment
     */
    private function processPayment($payment, $txData) {
        $this->db->beginTransaction();
        
        try {
            // Update payment record
            $this->db->update('tron_payments', [
                'received_amount' => $txData['amount'],
                'tx_hash' => $txData['tx_hash'],
                'sender_wallet' => $txData['from'],
                'confirmations' => 1,
                'status' => 'confirmed',
                'confirmed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$payment['id']]);
            
            // Process based on payment type
            switch ($payment['payment_type']) {
                case 'deposit':
                    $this->processDeposit($payment, $txData);
                    break;
                    
                case 'verification':
                    $this->processVerification($payment, $txData);
                    break;
            }
            
            $this->db->commit();
            
            return ['found' => true, 'confirmed' => true, 'tx_hash' => $txData['tx_hash']];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Payment processing error: " . $e->getMessage());
            return ['found' => true, 'confirmed' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Process deposit payment
     */
    private function processDeposit($payment, $txData) {
        $wallet = $this->getWallet($payment['user_id']);
        $amount = (float) $txData['amount'];
        
        // Update wallet balance
        $newBalance = $wallet['balance'] + $amount;
        $this->db->update('wallets', [
            'balance' => $newBalance,
            'total_deposited' => $wallet['total_deposited'] + $amount
        ], 'id = ?', [$wallet['id']]);
        
        // Create transaction record
        $this->db->insert('wallet_transactions', [
            'wallet_id' => $wallet['id'],
            'type' => 'deposit',
            'amount' => $amount,
            'balance_before' => $wallet['balance'],
            'balance_after' => $newBalance,
            'reference_type' => 'tron_payment',
            'reference_id' => $payment['id'],
            'description' => 'USDT Deposit via TRON',
            'status' => 'completed'
        ]);
        
        // Create notification
        createNotification(
            $payment['user_id'],
            'deposit',
            'Deposit Confirmed',
            'Your deposit of ' . formatCurrency($amount) . ' has been confirmed.',
            BASE_URL . '/wallet'
        );
    }
    
    /**
     * Process verification payment
     */
    private function processVerification($payment, $txData) {
        // Update verification record
        $this->db->update('seller_verifications', [
            'amount_received' => $txData['amount'],
            'tx_hash' => $txData['tx_hash'],
            'sender_wallet' => $txData['from'],
            'status' => 'confirmed',
            'confirmed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$payment['reference_id']]);
        
        // Get seller
        $verification = $this->db->fetch(
            "SELECT * FROM seller_verifications WHERE id = ?",
            [$payment['reference_id']]
        );
        
        // Update seller to verified
        $this->db->update('sellers', [
            'verification_status' => 'verified',
            'verified_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$verification['seller_id']]);
        
        // Create notification
        $seller = $this->db->fetch("SELECT user_id FROM sellers WHERE id = ?", [$verification['seller_id']]);
        createNotification(
            $seller['user_id'],
            'verification',
            'Verification Complete',
            'Congratulations! Your seller account has been verified.',
            BASE_URL . '/seller/dashboard'
        );
    }
    
    /**
     * Process purchase
     */
    public function processPurchase($buyerId, $productId, $quantity = 1) {
        $product = $this->db->fetch(
            "SELECT p.*, s.user_id as seller_user_id, s.id as seller_id 
             FROM products p 
             JOIN sellers s ON p.seller_id = s.id 
             WHERE p.id = ? AND p.status = 'active'",
            [$productId]
        );
        
        if (!$product) {
            throw new Exception("Product not found or unavailable");
        }
        
        // Check stock
        if ($product['stock_quantity'] != -1 && $product['stock_quantity'] < $quantity) {
            throw new Exception("Insufficient stock");
        }
        
        $buyerWallet = $this->getWallet($buyerId);
        $totalAmount = $product['price'] * $quantity;
        
        // Check buyer balance
        if ($buyerWallet['balance'] < $totalAmount) {
            throw new Exception("Insufficient balance");
        }
        
        // Calculate commission
        $commissionRate = getSetting('commission_rate', DEFAULT_COMMISSION_RATE);
        $commissionAmount = $totalAmount * ($commissionRate / 100);
        $sellerAmount = $totalAmount - $commissionAmount;
        
        $this->db->beginTransaction();
        
        try {
            // Deduct from buyer
            $newBuyerBalance = $buyerWallet['balance'] - $totalAmount;
            $this->db->update('wallets', [
                'balance' => $newBuyerBalance,
                'total_spent' => $buyerWallet['total_spent'] + $totalAmount
            ], 'id = ?', [$buyerWallet['id']]);
            
            // Record buyer transaction
            $this->db->insert('wallet_transactions', [
                'wallet_id' => $buyerWallet['id'],
                'type' => 'purchase',
                'amount' => -$totalAmount,
                'balance_before' => $buyerWallet['balance'],
                'balance_after' => $newBuyerBalance,
                'description' => 'Purchase: ' . $product['name'],
                'status' => 'completed'
            ]);
            
            // Get delivery data for auto delivery (per-line stock system)
            $deliveryData = null;
            if ($product['delivery_type'] === 'auto' && $product['delivery_data']) {
                // Split delivery data by lines - each line is one stock item
                $allLines = array_filter(array_map('trim', explode("\n", $product['delivery_data'])));
                
                if (count($allLines) < $quantity) {
                    throw new Exception("Not enough stock items available. Only " . count($allLines) . " items left.");
                }
                
                // Take required number of lines for this order
                $deliveredLines = array_slice($allLines, 0, $quantity);
                $remainingLines = array_slice($allLines, $quantity);
                
                // Set delivery data for buyer (only their purchased lines)
                $deliveryData = implode("\n", $deliveredLines);
                
                // Update product's delivery_data with remaining lines
                $newDeliveryData = implode("\n", $remainingLines);
                $this->db->update('products', [
                    'delivery_data' => $newDeliveryData,
                    'stock_quantity' => count($remainingLines) // Auto-update stock based on remaining lines
                ], 'id = ?', [$productId]);
            }
            
            // Create order
            $orderNumber = generateOrderNumber();
            $orderId = $this->db->insert('orders', [
                'order_number' => $orderNumber,
                'buyer_id' => $buyerId,
                'seller_id' => $product['seller_id'],
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $product['price'],
                'total_amount' => $totalAmount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'seller_amount' => $sellerAmount,
                'delivery_type' => $product['delivery_type'],
                'delivery_data' => $deliveryData,
                'status' => $product['delivery_type'] === 'auto' ? 'completed' : 'processing',
                'completed_at' => $product['delivery_type'] === 'auto' ? date('Y-m-d H:i:s') : null
            ]);
            
            // Update order reference in transaction
            $this->db->query(
                "UPDATE wallet_transactions SET reference_type = 'order', reference_id = ? 
                 WHERE wallet_id = ? ORDER BY id DESC LIMIT 1",
                [$orderId, $buyerWallet['id']]
            );
            
            // Handle seller payment based on delivery type
            $sellerWallet = $this->getWallet($product['seller_user_id']);
            
            if ($product['delivery_type'] === 'auto') {
                // AUTO DELIVERY: Credit seller immediately
                $newSellerBalance = $sellerWallet['balance'] + $sellerAmount;
                
                $this->db->update('wallets', [
                    'balance' => $newSellerBalance,
                    'total_earned' => $sellerWallet['total_earned'] + $sellerAmount
                ], 'id = ?', [$sellerWallet['id']]);
                
                // Record seller transaction as completed
                $this->db->insert('wallet_transactions', [
                    'wallet_id' => $sellerWallet['id'],
                    'type' => 'sale',
                    'amount' => $sellerAmount,
                    'balance_before' => $sellerWallet['balance'],
                    'balance_after' => $newSellerBalance,
                    'reference_type' => 'order',
                    'reference_id' => $orderId,
                    'description' => 'Sale: ' . $product['name'],
                    'status' => 'completed'
                ]);
            } else {
                // MANUAL DELIVERY: Hold payment until seller delivers
                $this->db->update('wallets', [
                    'pending_balance' => $sellerWallet['pending_balance'] + $sellerAmount
                ], 'id = ?', [$sellerWallet['id']]);
                
                // Record seller transaction as pending
                $this->db->insert('wallet_transactions', [
                    'wallet_id' => $sellerWallet['id'],
                    'type' => 'sale',
                    'amount' => $sellerAmount,
                    'balance_before' => $sellerWallet['balance'],
                    'balance_after' => $sellerWallet['balance'], // Balance unchanged until delivery
                    'reference_type' => 'order',
                    'reference_id' => $orderId,
                    'description' => 'Sale: ' . $product['name'] . ' (Pending delivery)',
                    'status' => 'pending'
                ]);
            }
            
            // Update product stock and sales
            // Note: For auto delivery, stock is already updated above when delivery_data is modified
            // So we only decrement stock for manual delivery products here
            if ($product['delivery_type'] === 'auto') {
                // Auto delivery: stock already updated via delivery_data, just update sales
                $this->db->query(
                    "UPDATE products SET total_sales = total_sales + ? WHERE id = ?",
                    [$quantity, $productId]
                );
            } elseif ($product['stock_quantity'] != -1) {
                // Manual delivery: decrement stock and update sales
                $this->db->query(
                    "UPDATE products SET stock_quantity = stock_quantity - ?, total_sales = total_sales + ? WHERE id = ?",
                    [$quantity, $quantity, $productId]
                );
            } else {
                // Unlimited stock: just update sales
                $this->db->query(
                    "UPDATE products SET total_sales = total_sales + ? WHERE id = ?",
                    [$quantity, $productId]
                );
            }
            
            // Update seller stats
            if ($product['delivery_type'] === 'auto') {
                // Auto delivery: update sales and earnings immediately
                $this->db->query(
                    "UPDATE sellers SET total_sales = total_sales + ?, total_earnings = total_earnings + ? WHERE id = ?",
                    [$quantity, $sellerAmount, $product['seller_id']]
                );
            } else {
                // Manual delivery: only update sales count, earnings updated on delivery
                $this->db->query(
                    "UPDATE sellers SET total_sales = total_sales + ? WHERE id = ?",
                    [$quantity, $product['seller_id']]
                );
            }
            
            // Notifications
            createNotification(
                $buyerId,
                'order',
                'Order Placed',
                'Your order #' . $orderNumber . ' has been placed.',
                BASE_URL . '/orders.php?view=' . $orderId
            );
            
            createNotification(
                $product['seller_user_id'],
                'sale',
                'New Sale',
                'You have a new order #' . $orderNumber,
                sellerOrderUrl($orderId)
            );
            
            $this->db->commit();
            
            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'delivery_type' => $product['delivery_type'],
                'delivery_data' => $deliveryData
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Seller delivers order (marks as delivered, payment still held)
     * Buyer must release payment to complete the order
     */
    public function deliverOrder($orderId, $deliveryData, $sellerId) {
        $order = $this->db->fetch(
            "SELECT * FROM orders WHERE id = ? AND seller_id = ? AND status = 'processing'",
            [$orderId, $sellerId]
        );
        
        if (!$order) {
            throw new Exception("Order not found or cannot be delivered");
        }
        
        $this->db->beginTransaction();
        
        try {
            // Update order to delivered status (NOT completed yet)
            // Payment remains in pending_balance until buyer releases
            $this->db->update('orders', [
                'delivery_data' => $deliveryData,
                'status' => 'delivered',
                'delivered_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$orderId]);
            
            // Notify buyer to release payment
            createNotification(
                $order['buyer_id'],
                'delivery',
                'Order Delivered - Please Confirm',
                'Your order #' . $order['order_number'] . ' has been delivered. Please check and release payment.',
                BASE_URL . '/orders.php?view=' . $orderId
            );
            
            $this->db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Buyer releases payment after confirming delivery
     * This completes the order and transfers funds to seller
     */
    public function releasePayment($orderId, $buyerId) {
        $order = $this->db->fetch(
            "SELECT * FROM orders WHERE id = ? AND buyer_id = ? AND status = 'delivered'",
            [$orderId, $buyerId]
        );
        
        if (!$order) {
            throw new Exception("Order not found or payment cannot be released");
        }
        
        $this->db->beginTransaction();
        
        try {
            // Update order to completed
            $this->db->update('orders', [
                'status' => 'completed',
                'payment_released' => 1,
                'payment_released_at' => date('Y-m-d H:i:s'),
                'completed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$orderId]);
            
            // Move pending balance to available for seller
            $seller = $this->db->fetch("SELECT user_id FROM sellers WHERE id = ?", [$order['seller_id']]);
            $wallet = $this->getWallet($seller['user_id']);
            
            $this->db->update('wallets', [
                'balance' => $wallet['balance'] + $order['seller_amount'],
                'pending_balance' => $wallet['pending_balance'] - $order['seller_amount'],
                'total_earned' => $wallet['total_earned'] + $order['seller_amount']
            ], 'id = ?', [$wallet['id']]);
            
            // Update transaction status
            $this->db->query(
                "UPDATE wallet_transactions SET status = 'completed', balance_after = ? 
                 WHERE reference_type = 'order' AND reference_id = ? AND type = 'sale'",
                [$wallet['balance'] + $order['seller_amount'], $orderId]
            );
            
            // Update seller earnings
            $this->db->query(
                "UPDATE sellers SET total_earnings = total_earnings + ? WHERE id = ?",
                [$order['seller_amount'], $order['seller_id']]
            );
            
            // Notify seller that payment was released
            createNotification(
                $seller['user_id'],
                'payment',
                'Payment Released',
                'Payment of ' . formatCurrency($order['seller_amount']) . ' for order #' . $order['order_number'] . ' has been released to your wallet.',
                BASE_URL . '/seller/orders.php?view=' . $orderId
            );
            
            $this->db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Legacy function - kept for backward compatibility
     * Now calls deliverOrder instead
     */
    public function completeOrder($orderId, $deliveryData, $sellerId) {
        return $this->deliverOrder($orderId, $deliveryData, $sellerId);
    }
    
    /**
     * Process refund
     */
    public function processRefund($orderId, $adminId = null) {
        $order = $this->db->fetch("SELECT * FROM orders WHERE id = ?", [$orderId]);
        
        if (!$order) {
            throw new Exception("Order not found");
        }
        
        if ($order['status'] === 'refunded') {
            throw new Exception("Order already refunded");
        }
        
        $this->db->beginTransaction();
        
        try {
            // Refund buyer
            $buyerWallet = $this->getWallet($order['buyer_id']);
            $newBalance = $buyerWallet['balance'] + $order['total_amount'];
            
            $this->db->update('wallets', [
                'balance' => $newBalance,
                'total_spent' => $buyerWallet['total_spent'] - $order['total_amount']
            ], 'id = ?', [$buyerWallet['id']]);
            
            $this->db->insert('wallet_transactions', [
                'wallet_id' => $buyerWallet['id'],
                'type' => 'refund',
                'amount' => $order['total_amount'],
                'balance_before' => $buyerWallet['balance'],
                'balance_after' => $newBalance,
                'reference_type' => 'order',
                'reference_id' => $orderId,
                'description' => 'Refund for order #' . $order['order_number'],
                'status' => 'completed'
            ]);
            
            // Deduct from seller if already credited
            $seller = $this->db->fetch("SELECT user_id FROM sellers WHERE id = ?", [$order['seller_id']]);
            $sellerWallet = $this->getWallet($seller['user_id']);
            
            if ($order['status'] === 'completed') {
                $newSellerBalance = $sellerWallet['balance'] - $order['seller_amount'];
                $this->db->update('wallets', [
                    'balance' => max(0, $newSellerBalance),
                    'total_earned' => $sellerWallet['total_earned'] - $order['seller_amount']
                ], 'id = ?', [$sellerWallet['id']]);
            } else {
                $this->db->update('wallets', [
                    'pending_balance' => $sellerWallet['pending_balance'] - $order['seller_amount']
                ], 'id = ?', [$sellerWallet['id']]);
            }
            
            // Update order status
            $this->db->update('orders', ['status' => 'refunded'], 'id = ?', [$orderId]);
            
            // Update seller stats
            $this->db->query(
                "UPDATE sellers SET total_sales = total_sales - ?, total_earnings = total_earnings - ? WHERE id = ?",
                [$order['quantity'], $order['seller_amount'], $order['seller_id']]
            );
            
            // Notifications
            createNotification(
                $order['buyer_id'],
                'refund',
                'Refund Processed',
                'Your refund of ' . formatCurrency($order['total_amount']) . ' has been processed.',
                BASE_URL . '/orders.php?view=' . $orderId
            );
            
            $this->db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}

// Helper function
function paymentHandler() {
    static $instance = null;
    if ($instance === null) {
        $instance = new PaymentHandler();
    }
    return $instance;
}
