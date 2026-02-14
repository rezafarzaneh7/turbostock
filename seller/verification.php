<?php
/**
 * HStore - Seller Verification Page
 */
require_once __DIR__ . '/../includes/init.php';
requireSeller();

$userId = getCurrentUserId();
$seller = db()->fetch("SELECT * FROM sellers WHERE user_id = ?", [$userId]);
$wallet = paymentHandler()->getWallet($userId);

$verificationFee = getSetting('verification_fee', VERIFICATION_FEE);
$error = '';
$success = '';

// Handle verification request - pay from wallet balance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_verification'])) {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request';
    } elseif ($seller['verification_status'] === 'verified') {
        $error = 'You are already verified';
    } elseif ($wallet['balance'] < $verificationFee) {
        // Not enough balance - redirect to wallet page
        $_SESSION['flash_message'] = 'Insufficient balance. Please add ' . formatCurrency($verificationFee) . ' to your wallet for verification.';
        $_SESSION['flash_type'] = 'warning';
        redirect(BASE_URL . '/wallet.php');
    } else {
        try {
            // Deduct from wallet
            $newBalance = $wallet['balance'] - $verificationFee;
            db()->update('wallets', [
                'balance' => $newBalance,
                'total_spent' => $wallet['total_spent'] + $verificationFee
            ], 'id = ?', [$wallet['id']]);
            
            // Record transaction
            db()->insert('wallet_transactions', [
                'wallet_id' => $wallet['id'],
                'type' => 'verification',
                'amount' => -$verificationFee,
                'balance_before' => $wallet['balance'],
                'balance_after' => $newBalance,
                'reference_type' => 'verification',
                'reference_id' => $seller['id'],
                'description' => 'Seller verification fee',
                'status' => 'completed'
            ]);
            
            // Create verification record
            db()->insert('seller_verifications', [
                'seller_id' => $seller['id'],
                'amount_required' => $verificationFee,
                'amount_received' => $verificationFee,
                'status' => 'confirmed',
                'confirmed_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update seller to verified
            db()->update('sellers', [
                'verification_status' => 'verified',
                'verified_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$seller['id']]);
            
            // Refresh seller data
            $seller = db()->fetch("SELECT * FROM sellers WHERE user_id = ?", [$userId]);
            $wallet = paymentHandler()->getWallet($userId);
            
            // Create notification
            createNotification(
                $userId,
                'verification',
                'Verification Complete',
                'Congratulations! Your seller account has been verified.',
                BASE_URL . '/seller/'
            );
            
            $success = 'Congratulations! Your seller account has been verified.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Verification - Seller Dashboard';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 col-xl-2 mb-4">
            <div class="sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link" href="<?= BASE_URL ?>/seller/">
                        <i class="fas fa-tachometer-alt"></i> <?= __('dashboard') ?>
                    </a>
                    <a class="nav-link" href="<?= BASE_URL ?>/seller/products.php">
                        <i class="fas fa-box"></i> <?= __('products') ?>
                    </a>
                    <a class="nav-link" href="<?= BASE_URL ?>/seller/orders.php">
                        <i class="fas fa-shopping-cart"></i> <?= __('orders') ?>
                    </a>
                    <a class="nav-link active" href="<?= BASE_URL ?>/seller/verification.php">
                        <i class="fas fa-check-circle"></i> <?= __('verification') ?>
                    </a>
                    <a class="nav-link" href="<?= BASE_URL ?>/seller/settings.php">
                        <i class="fas fa-cog"></i> <?= __('shop_settings') ?>
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9 col-xl-10">
            <h4 class="fw-bold mb-4"><?= __('seller_verification') ?></h4>
            
            <?php if ($seller['verification_status'] === 'verified'): ?>
                <!-- Already Verified -->
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <span class="badge bg-success" style="font-size: 1.5rem; padding: 1rem 2rem;">
                                <i class="fas fa-check-circle me-2"></i><?= __('verified_seller') ?>
                            </span>
                        </div>
                        <h4 class="fw-bold"><?= __('congratulations') ?></h4>
                        <p class="text-muted mb-0">
                            Your seller account has been verified since <?= formatDate($seller['verified_at']) ?>
                        </p>
                    </div>
                </div>
                
                <!-- Benefits -->
                <div class="row g-4 mt-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-badge-check fa-3x text-success mb-3"></i>
                                <h6 class="fw-bold"><?= __('verified_badge') ?></h6>
                                <p class="text-muted small mb-0">Your products display a verified badge, building buyer trust</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-search fa-3x text-primary mb-3"></i>
                                <h6 class="fw-bold"><?= __('priority_listing') ?></h6>
                                <p class="text-muted small mb-0">Your products appear higher in search results</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-headset fa-3x text-warning mb-3"></i>
                                <h6 class="fw-bold"><?= __('priority_support') ?></h6>
                                <p class="text-muted small mb-0">Get faster response from our support team</p>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Request Verification -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i><?= __('get_verified') ?></h5>
                            </div>
                            <div class="card-body">
                                <p class="lead">
                                    Become a verified seller to build trust with buyers and increase your sales.
                                </p>
                                
                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?= sanitize($error) ?></div>
                                <?php endif; ?>
                                <?php if ($success): ?>
                                    <div class="alert alert-success"><?= sanitize($success) ?></div>
                                <?php endif; ?>
                                
                                <div class="bg-light rounded p-4 mb-4">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="fw-bold mb-1"><?= __('verification_fee') ?></h5>
                                            <p class="text-muted mb-0">One-time payment</p>
                                        </div>
                                        <div class="text-end">
                                            <h3 class="fw-bold text-success mb-0"><?= formatCurrency($verificationFee) ?></h3>
                                        </div>
                                    </div>
                                </div>
                                
                                <h6 class="fw-bold mb-3">Benefits of Verification:</h6>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Verified Badge</strong> - Display a green verified badge on your profile and products
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Increased Trust</strong> - Buyers prefer verified sellers
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Priority Listing</strong> - Your products appear higher in search
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Priority Support</strong> - Get faster help from our team
                                    </li>
                                </ul>
                                
                                <form method="POST" action="">
                                    <?= csrfField() ?>
                                    <button type="submit" name="request_verification" class="btn btn-success btn-lg">
                                        <i class="fas fa-check-circle me-2"></i>Pay <?= formatCurrency($verificationFee) ?> & Get Verified
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><?= __('current_status') ?></h6>
                            </div>
                            <div class="card-body text-center">
                                <?= getVerificationBadge($seller['verification_status']) ?>
                                <p class="text-muted mt-3 mb-0">
                                    <?php if ($seller['verification_status'] === 'unverified'): ?>
                                        Your shop is currently unverified. Get verified to build trust with buyers.
                                    <?php else: ?>
                                        Your verification is being processed.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0"><?= __('wallet_balance') ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-wallet fa-2x text-primary me-3"></i>
                                        <div>
                                            <strong><?= formatCurrency($wallet['balance']) ?></strong>
                                            <small class="text-muted d-block">Available Balance</small>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($wallet['balance'] < $verificationFee): ?>
                                    <div class="alert alert-warning mt-3 mb-0">
                                        <small><i class="fas fa-exclamation-triangle me-1"></i>Insufficient balance. <a href="<?= BASE_URL ?>/wallet.php">Add funds</a></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
var BASE_URL = '<?= BASE_URL ?>';
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
