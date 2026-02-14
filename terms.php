<?php
/**
 * HStore - Terms of Service
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Terms of Service - ' . PLATFORM_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-4">Terms of Service</h1>
            
            <div class="card">
                <div class="card-body">
                    <h5>1. Acceptance of Terms</h5>
                    <p>By accessing and using <?= PLATFORM_NAME ?>, you accept and agree to be bound by the terms and provisions of this agreement.</p>
                    
                    <h5>2. User Accounts</h5>
                    <p>You are responsible for maintaining the confidentiality of your account and password. You agree to accept responsibility for all activities that occur under your account.</p>
                    
                    <h5>3. Digital Products</h5>
                    <p>All products sold on this platform are digital goods. Sellers are responsible for the accuracy of their product descriptions and the quality of their products.</p>
                    
                    <h5>4. Payments</h5>
                    <p>All payments are processed through USDT (TRC20) on the TRON blockchain. Transactions are final once confirmed on the blockchain.</p>
                    
                    <h5>5. Seller Responsibilities</h5>
                    <p>Sellers must:</p>
                    <ul>
                        <li>Provide accurate product descriptions</li>
                        <li>Deliver products as described</li>
                        <li>Respond to buyer inquiries promptly</li>
                        <li>Comply with all applicable laws</li>
                    </ul>
                    
                    <h5>6. Buyer Responsibilities</h5>
                    <p>Buyers must:</p>
                    <ul>
                        <li>Provide accurate payment information</li>
                        <li>Not engage in fraudulent activities</li>
                        <li>Report issues through proper channels</li>
                    </ul>
                    
                    <h5>7. Platform Fees</h5>
                    <p>The platform charges a <?= getSetting('commission_rate', DEFAULT_COMMISSION_RATE) ?>% commission on all sales. Seller verification requires a one-time fee of <?= formatCurrency(getSetting('verification_fee', VERIFICATION_FEE)) ?>.</p>
                    
                    <h5>8. Disputes</h5>
                    <p>In case of disputes, both parties agree to work with the platform's dispute resolution process. The platform's decision is final.</p>
                    
                    <h5>9. Prohibited Items</h5>
                    <p>The following items are prohibited:</p>
                    <ul>
                        <li>Illegal products or services</li>
                        <li>Stolen credentials or data</li>
                        <li>Malware or harmful software</li>
                        <li>Fraudulent services</li>
                    </ul>
                    
                    <h5>10. Termination</h5>
                    <p>We reserve the right to terminate accounts that violate these terms without prior notice.</p>
                    
                    <h5>11. Changes to Terms</h5>
                    <p>We reserve the right to modify these terms at any time. Continued use of the platform constitutes acceptance of modified terms.</p>
                    
                    <p class="text-muted mt-4"><small>Last updated: <?= date('F Y') ?></small></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
