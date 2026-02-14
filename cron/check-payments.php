<?php
/**
 * Demostore - Payment Checker Cron Job
 * Run this script periodically to check for pending payments
 * 
 * Recommended: Run every 1-5 minutes via cron
 * Example crontab entry:
 * * /5 * * * * php /path/to/hstore/cron/check-payments.php
 */

// Prevent web access
if (php_sapi_name() !== 'cli' && !defined('CRON_ALLOWED')) {
    die('This script can only be run from command line');
}

require_once __DIR__ . '/../includes/init.php';

echo "Starting payment check at " . date('Y-m-d H:i:s') . "\n";

try {
    $processed = paymentHandler()->checkPendingPayments();
    echo "Processed {$processed} payments\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    error_log("Cron payment check error: " . $e->getMessage());
}

// Mark expired payments
$expired = db()->query("
    UPDATE tron_payments 
    SET status = 'expired' 
    WHERE status = 'pending' AND expires_at <= NOW()
")->rowCount();

if ($expired > 0) {
    echo "Marked {$expired} payments as expired\n";
}

// Mark expired verifications
$expiredVerifications = db()->query("
    UPDATE seller_verifications 
    SET status = 'expired' 
    WHERE status = 'pending' AND expires_at <= NOW()
")->rowCount();

if ($expiredVerifications > 0) {
    echo "Marked {$expiredVerifications} verifications as expired\n";
    
    // Reset seller status for expired verifications
    db()->query("
        UPDATE sellers s
        SET verification_status = 'unverified'
        WHERE verification_status = 'pending'
        AND NOT EXISTS (
            SELECT 1 FROM seller_verifications sv 
            WHERE sv.seller_id = s.id AND sv.status = 'pending'
        )
    ");
}

// Release frozen payments after 24 hours
$frozenPayments = db()->fetchAll("
    SELECT fp.*, s.user_id as seller_user_id
    FROM frozen_payments fp
    JOIN sellers s ON fp.seller_id = s.id
    WHERE fp.status = 'frozen' AND fp.release_at <= NOW()
");

$releasedCount = 0;
foreach ($frozenPayments as $frozen) {
    try {
        db()->beginTransaction();
        
        // Get seller wallet
        $wallet = db()->fetch("SELECT * FROM wallets WHERE user_id = ?", [$frozen['seller_user_id']]);
        
        if ($wallet) {
            // Move from pending to available balance
            $newBalance = $wallet['balance'] + $frozen['amount'];
            $newPending = $wallet['pending_balance'] - $frozen['amount'];
            
            db()->update('wallets', [
                'balance' => $newBalance,
                'pending_balance' => max(0, $newPending),
                'total_earned' => $wallet['total_earned'] + $frozen['amount']
            ], 'id = ?', [$wallet['id']]);
            
            // Update frozen payment status
            db()->update('frozen_payments', [
                'status' => 'released',
                'released_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$frozen['id']]);
            
            // Update transaction status
            db()->query("
                UPDATE wallet_transactions 
                SET status = 'completed', 
                    balance_after = ?,
                    description = REPLACE(description, ' (Frozen for 24hrs)', '')
                WHERE reference_type = 'order' AND reference_id = ? AND type = 'sale'
            ", [$newBalance, $frozen['order_id']]);
            
            // Notify seller
            createNotification(
                $frozen['seller_user_id'],
                'wallet',
                'Payment Released',
                formatCurrency($frozen['amount']) . ' has been released to your wallet.',
                BASE_URL . '/wallet.php'
            );
            
            $releasedCount++;
        }
        
        db()->commit();
    } catch (Exception $e) {
        db()->rollback();
        error_log("Error releasing frozen payment {$frozen['id']}: " . $e->getMessage());
    }
}

if ($releasedCount > 0) {
    echo "Released {$releasedCount} frozen payments\n";
}

echo "Completed at " . date('Y-m-d H:i:s') . "\n";
