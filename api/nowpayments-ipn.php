<?php
/**
 * HStore - NOWPayments IPN (Instant Payment Notification) Handler
 * Automatically updates user balance when payment is confirmed
 */

// Disable error display for API endpoint
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/init.php';

// Log all IPN requests for debugging
$logFile = BASE_PATH . '/logs/nowpayments_ipn.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Get request data
$rawInput = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';

// Log the request
$logEntry = date('Y-m-d H:i:s') . " | IPN Received\n";
$logEntry .= "Signature: " . $signature . "\n";
$logEntry .= "Body: " . $rawInput . "\n";
$logEntry .= "---\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Parse JSON
$data = json_decode($rawInput, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Verify signature
if (!nowPayments()->verifyIPN($data, $signature)) {
    $logEntry = date('Y-m-d H:i:s') . " | Signature verification FAILED\n---\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

$logEntry = date('Y-m-d H:i:s') . " | Signature verified OK\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Extract payment info
$paymentId = $data['payment_id'] ?? null;
$paymentStatus = $data['payment_status'] ?? null;
$orderId = $data['order_id'] ?? null;
$priceAmount = $data['price_amount'] ?? 0;
$priceCurrency = $data['price_currency'] ?? 'usd';
$payAmount = $data['pay_amount'] ?? 0;
$payCurrency = $data['pay_currency'] ?? '';
$actuallyPaid = $data['actually_paid'] ?? 0;

$logEntry = date('Y-m-d H:i:s') . " | Payment ID: {$paymentId}, Status: {$paymentStatus}, Order: {$orderId}, Amount: {$priceAmount} {$priceCurrency}\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Find the payment record in our database
$payment = db()->fetch("SELECT * FROM crypto_payments WHERE payment_id = ?", [$paymentId]);

if (!$payment) {
    // Try to find by order_id
    $payment = db()->fetch("SELECT * FROM crypto_payments WHERE order_id = ?", [$orderId]);
}

if (!$payment) {
    $logEntry = date('Y-m-d H:i:s') . " | Payment not found in database\n---\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    http_response_code(200); // Return 200 to prevent retries
    echo json_encode(['status' => 'payment_not_found']);
    exit;
}

// Update payment status in database
db()->query("UPDATE crypto_payments SET 
    status = ?,
    actually_paid = ?,
    pay_currency = ?,
    updated_at = NOW()
    WHERE id = ?", [
    $paymentStatus,
    $actuallyPaid,
    $payCurrency,
    $payment['id']
]);

$logEntry = date('Y-m-d H:i:s') . " | Updated payment record ID: {$payment['id']}\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Process based on status
if ($paymentStatus === 'finished' || $paymentStatus === 'confirmed') {
    // Payment completed - update user balance
    $userId = $payment['user_id'];
    $amount = (float) $priceAmount; // Amount in USD
    
    // Check if already processed
    if ($payment['status'] === 'finished' || $payment['status'] === 'confirmed') {
        $logEntry = date('Y-m-d H:i:s') . " | Payment already processed, skipping\n---\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        http_response_code(200);
        echo json_encode(['status' => 'already_processed']);
        exit;
    }
    
    // Get user's wallet
    $wallet = db()->fetch("SELECT * FROM wallets WHERE user_id = ?", [$userId]);
    
    if ($wallet) {
        $newBalance = $wallet['balance'] + $amount;
        $newTotalDeposited = $wallet['total_deposited'] + $amount;
        
        // Update wallet balance
        db()->query("UPDATE wallets SET 
            balance = ?,
            total_deposited = ?,
            updated_at = NOW()
            WHERE user_id = ?", [
            $newBalance,
            $newTotalDeposited,
            $userId
        ]);
        
        // Record transaction
        db()->insert('wallet_transactions', [
            'wallet_id' => $wallet['id'],
            'type' => 'deposit',
            'amount' => $amount,
            'balance_before' => $wallet['balance'],
            'balance_after' => $newBalance,
            'description' => 'Crypto deposit via ' . strtoupper($payCurrency),
            'reference_type' => 'crypto_payment',
            'reference_id' => $payment['id'],
            'status' => 'completed'
        ]);
        
        // Create notification for user
        db()->insert('notifications', [
            'user_id' => $userId,
            'type' => 'deposit',
            'title' => 'Deposit Successful',
            'message' => 'Your deposit of ' . formatCurrency($amount) . ' has been credited to your wallet.',
            'data' => json_encode(['payment_id' => $paymentId, 'amount' => $amount])
        ]);
        
        $logEntry = date('Y-m-d H:i:s') . " | SUCCESS: Credited {$amount} USD to user {$userId}. New balance: {$newBalance}\n---\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    } else {
        $logEntry = date('Y-m-d H:i:s') . " | ERROR: Wallet not found for user {$userId}\n---\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
} elseif ($paymentStatus === 'partially_paid') {
    // Partial payment received
    $logEntry = date('Y-m-d H:i:s') . " | Partial payment received: {$actuallyPaid} {$payCurrency}\n---\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // Notify user
    db()->insert('notifications', [
        'user_id' => $payment['user_id'],
        'type' => 'payment',
        'title' => 'Partial Payment Received',
        'message' => 'We received a partial payment. Please send the remaining amount to complete your deposit.',
        'data' => json_encode(['payment_id' => $paymentId])
    ]);
} elseif ($paymentStatus === 'expired') {
    $logEntry = date('Y-m-d H:i:s') . " | Payment expired\n---\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
} elseif ($paymentStatus === 'failed') {
    $logEntry = date('Y-m-d H:i:s') . " | Payment failed\n---\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
