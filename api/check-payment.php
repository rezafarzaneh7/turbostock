<?php
/**
 * HStore - Payment Check API
 * Checks if a payment has been confirmed
 */
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonError('Unauthorized', 401);
}

$paymentId = (int) ($_GET['id'] ?? 0);

if (!$paymentId) {
    jsonError('Payment ID required');
}

// Get payment
$payment = db()->fetch("SELECT * FROM tron_payments WHERE id = ? AND user_id = ?", [$paymentId, getCurrentUserId()]);

if (!$payment) {
    jsonError('Payment not found', 404);
}

// If already confirmed
if ($payment['status'] === 'confirmed') {
    jsonSuccess([
        'confirmed' => true,
        'tx_hash' => $payment['tx_hash'],
        'amount' => $payment['received_amount']
    ], 'Payment confirmed');
}

// If expired or failed
if (in_array($payment['status'], ['expired', 'failed'])) {
    jsonSuccess([
        'confirmed' => false,
        'status' => $payment['status']
    ], 'Payment ' . $payment['status']);
}

// Check blockchain for payment
try {
    $result = paymentHandler()->checkPayment($paymentId);
    
    if ($result['confirmed'] ?? false) {
        jsonSuccess([
            'confirmed' => true,
            'tx_hash' => $result['tx_hash'] ?? null
        ], 'Payment confirmed');
    } else {
        jsonSuccess([
            'confirmed' => false,
            'status' => 'pending'
        ], 'Waiting for payment');
    }
} catch (Exception $e) {
    error_log("Payment check error: " . $e->getMessage());
    jsonSuccess([
        'confirmed' => false,
        'status' => 'pending'
    ], 'Checking payment');
}
