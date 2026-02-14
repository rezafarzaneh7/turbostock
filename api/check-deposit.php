<?php
/**
 * HStore - Check Deposit Status API
 * Called via AJAX to check if deposit has been received
 * Supports both legacy TRON payments and NOWPayments
 */
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$paymentId = clean($_GET['payment_id'] ?? '');
$userId = getCurrentUserId();

if (!$paymentId) {
    echo json_encode(['error' => 'Payment ID required']);
    exit;
}

// Check crypto_payments table (NOWPayments)
$payment = db()->fetch("
    SELECT * FROM crypto_payments 
    WHERE payment_id = ? AND user_id = ? AND payment_type = 'deposit'
", [$paymentId, $userId]);

if (!$payment) {
    // Fallback to legacy tron_payments
    $payment = db()->fetch("
        SELECT * FROM tron_payments 
        WHERE id = ? AND user_id = ? AND payment_type = 'deposit'
    ", [(int)$paymentId, $userId]);
    
    if (!$payment) {
        echo json_encode(['error' => 'Payment not found']);
        exit;
    }
    
    // Legacy TRON payment handling
    if ($payment['status'] === 'confirmed') {
        echo json_encode([
            'status' => 'confirmed',
            'message' => 'Deposit confirmed!',
            'amount' => $payment['received_amount'] ?? $payment['expected_amount']
        ]);
        exit;
    }
    
    if ($payment['status'] === 'expired' || strtotime($payment['expires_at']) < time()) {
        echo json_encode(['status' => 'expired', 'message' => 'Payment expired']);
        exit;
    }
    
    try {
        $result = paymentHandler()->checkPayment((int)$paymentId);
        if ($result['confirmed']) {
            echo json_encode([
                'status' => 'confirmed',
                'message' => 'Deposit confirmed! ' . formatCurrency($result['amount']) . ' added to your wallet.',
                'amount' => $result['amount']
            ]);
        } else {
            echo json_encode(['status' => 'pending', 'message' => 'Waiting for payment...']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'pending', 'message' => 'Checking...']);
    }
    exit;
}

// NOWPayments handling
if (in_array($payment['status'], ['finished', 'confirmed'])) {
    echo json_encode([
        'status' => 'confirmed',
        'message' => 'Deposit confirmed!',
        'amount' => $payment['price_amount']
    ]);
    exit;
}

if ($payment['status'] === 'expired' || strtotime($payment['expires_at']) < time()) {
    echo json_encode(['status' => 'expired', 'message' => 'Payment expired']);
    exit;
}

if ($payment['status'] === 'failed') {
    echo json_encode(['status' => 'failed', 'message' => 'Payment failed']);
    exit;
}

// Check NOWPayments API for status update
try {
    $statusData = nowPayments()->getPaymentStatus($paymentId);
    
    if ($statusData && isset($statusData['payment_status'])) {
        $newStatus = $statusData['payment_status'];
        
        // Update local status
        if ($newStatus !== $payment['status']) {
            db()->query("UPDATE crypto_payments SET status = ?, updated_at = NOW() WHERE id = ?", 
                [$newStatus, $payment['id']]);
        }
        
        if (in_array($newStatus, ['finished', 'confirmed'])) {
            echo json_encode([
                'status' => 'confirmed',
                'message' => 'Deposit confirmed! $' . number_format($payment['price_amount'], 2) . ' added to your wallet.',
                'amount' => $payment['price_amount']
            ]);
            exit;
        }
        
        if ($newStatus === 'confirming' || $newStatus === 'sending') {
            echo json_encode([
                'status' => 'confirming',
                'message' => 'Payment received, confirming on blockchain...'
            ]);
            exit;
        }
    }
    
    echo json_encode(['status' => 'pending', 'message' => 'Waiting for payment...']);
} catch (Exception $e) {
    error_log("Check deposit error: " . $e->getMessage());
    echo json_encode(['status' => 'pending', 'message' => 'Checking...']);
}
