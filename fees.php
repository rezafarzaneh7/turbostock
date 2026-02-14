<?php
/**
 * HStore - Fees & Pricing Page
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Fees & Pricing - ' . PLATFORM_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="text-center mb-5">
                <h1 class="fw-bold">Transparent Pricing</h1>
                <p class="lead text-muted">Simple, fair fees for buyers and sellers</p>
            </div>
            
            <div class="row g-4">
                <!-- Buyer Fees -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Buyer Fees</h4>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="d-flex justify-content-between py-3 border-bottom">
                                    <span>Account Registration</span>
                                    <strong class="text-success">FREE</strong>
                                </li>
                                <li class="d-flex justify-content-between py-3 border-bottom">
                                    <span>Deposits (USDT TRC20)</span>
                                    <strong class="text-success">FREE*</strong>
                                </li>
                                <li class="d-flex justify-content-between py-3 border-bottom">
                                    <span>Purchases</span>
                                    <strong class="text-success">No extra fees</strong>
                                </li>
                                <li class="d-flex justify-content-between py-3">
                                    <span>Dispute Resolution</span>
                                    <strong class="text-success">FREE</strong>
                                </li>
                            </ul>
                            <p class="text-muted small mt-3">*Only blockchain network fees apply for deposits</p>
                        </div>
                    </div>
                </div>
                
                <!-- Seller Fees -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0"><i class="fas fa-store me-2"></i>Seller Fees</h4>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="d-flex justify-content-between py-3 border-bottom">
                                    <span>Shop Creation</span>
                                    <strong class="text-success">FREE</strong>
                                </li>
                                <li class="d-flex justify-content-between py-3 border-bottom">
                                    <span>Product Listings</span>
                                    <strong class="text-success">Unlimited FREE</strong>
                                </li>
                                <li class="d-flex justify-content-between py-3 border-bottom">
                                    <span>Sales Commission</span>
                                    <strong><?= getSetting('commission_rate', DEFAULT_COMMISSION_RATE) ?>%</strong>
                                </li>
                                <li class="d-flex justify-content-between py-3 border-bottom">
                                    <span>Seller Verification</span>
                                    <strong><?= formatCurrency(VERIFICATION_FEE) ?></strong>
                                </li>
                                <li class="d-flex justify-content-between py-3">
                                    <span>Withdrawals</span>
                                    <strong class="text-success">FREE*</strong>
                                </li>
                            </ul>
                            <p class="text-muted small mt-3">*Minimum withdrawal: <?= formatCurrency(MIN_WITHDRAWAL) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Commission Example -->
            <div class="card mt-5">
                <div class="card-header">
                    <h5 class="mb-0">Commission Example</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center mb-3 mb-md-0">
                            <h6 class="text-muted">Product Price</h6>
                            <h2 class="fw-bold">$100 USDT</h2>
                        </div>
                        <div class="col-md-4 text-center mb-3 mb-md-0">
                            <h6 class="text-muted">Platform Fee (<?= getSetting('commission_rate', DEFAULT_COMMISSION_RATE) ?>%)</h6>
                            <h2 class="fw-bold text-danger">-$<?= getSetting('commission_rate', DEFAULT_COMMISSION_RATE) ?> USDT</h2>
                        </div>
                        <div class="col-md-4 text-center">
                            <h6 class="text-muted">You Receive</h6>
                            <h2 class="fw-bold text-success">$<?= 100 - getSetting('commission_rate', DEFAULT_COMMISSION_RATE) ?> USDT</h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Verification Benefits -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-check-circle text-success me-2"></i>Verification Benefits</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Verified badge on profile & products</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Higher search ranking</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Increased buyer trust</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Priority customer support</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Featured seller opportunities</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>One-time payment, lifetime status</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="<?= BASE_URL ?>/become-seller.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-rocket me-2"></i>Start Selling Today
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
