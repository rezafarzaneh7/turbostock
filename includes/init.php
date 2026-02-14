<?php
/**
 * HStore - Initialization File
 * Include this file at the top of every page
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/TronAPI.php';
require_once __DIR__ . '/PaymentHandler.php';
require_once __DIR__ . '/NowPaymentsAPI.php';
require_once __DIR__ . '/Language.php';

// Start session
startSession();

// Generate CSRF token
generateCSRFToken();

// Background payment check (runs occasionally on page loads)
// This ensures deposits are processed even without cron job
if (rand(1, 10) === 1) { // 10% chance on each page load
    try {
        $lastCheck = $_SESSION['last_payment_check'] ?? 0;
        if (time() - $lastCheck > 60) { // Max once per minute
            $_SESSION['last_payment_check'] = time();
            paymentHandler()->checkPendingPayments();
        }
    } catch (Exception $e) {
        // Silent fail - don't break page load
        error_log("Background payment check error: " . $e->getMessage());
    }
}
