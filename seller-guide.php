<?php
/**
 * HStore - Seller Guide Page
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Seller Guide - ' . PLATFORM_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="text-center mb-5">
                <h1 class="fw-bold">Seller Guide</h1>
                <p class="lead text-muted">Everything you need to know to start selling on <?= PLATFORM_NAME ?></p>
            </div>
            
            <!-- Getting Started -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-rocket me-2"></i>Getting Started</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-4 mb-md-0">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-user-plus fa-2x text-primary"></i>
                            </div>
                            <h5>1. Create Account</h5>
                            <p class="text-muted">Register for a free account on <?= PLATFORM_NAME ?></p>
                        </div>
                        <div class="col-md-4 text-center mb-4 mb-md-0">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-store fa-2x text-primary"></i>
                            </div>
                            <h5>2. Create Shop</h5>
                            <p class="text-muted">Set up your seller profile and shop details</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-box fa-2x text-primary"></i>
                            </div>
                            <h5>3. List Products</h5>
                            <p class="text-muted">Add your digital products and start selling</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Product Types -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tags me-2"></i>What Can You Sell?</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-3"><i class="fas fa-check text-success me-2"></i><strong>Digital Accounts</strong> - Social media, gaming, streaming</li>
                                <li class="mb-3"><i class="fas fa-check text-success me-2"></i><strong>Software & Keys</strong> - License keys, activation codes</li>
                                <li class="mb-3"><i class="fas fa-check text-success me-2"></i><strong>Gift Cards</strong> - Various platforms and services</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-3"><i class="fas fa-check text-success me-2"></i><strong>E-books & Courses</strong> - Educational content</li>
                                <li class="mb-3"><i class="fas fa-check text-success me-2"></i><strong>Graphics & Templates</strong> - Design assets</li>
                                <li class="mb-3"><i class="fas fa-check text-success me-2"></i><strong>Services</strong> - Promotion, marketing services</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Delivery Types -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Delivery Methods</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4 mb-md-0">
                            <div class="border rounded p-4 h-100">
                                <h5 class="text-success"><i class="fas fa-bolt me-2"></i>Instant Delivery</h5>
                                <p class="text-muted">Products are delivered automatically after purchase. Perfect for:</p>
                                <ul class="text-muted">
                                    <li>Account credentials</li>
                                    <li>License keys</li>
                                    <li>Gift card codes</li>
                                </ul>
                                <p class="mb-0"><strong>How it works:</strong> Add your stock (one item per line), and the system delivers automatically.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-4 h-100">
                                <h5 class="text-primary"><i class="fas fa-hand-paper me-2"></i>Manual Delivery</h5>
                                <p class="text-muted">You deliver the product manually after purchase. Perfect for:</p>
                                <ul class="text-muted">
                                    <li>Custom services</li>
                                    <li>Large files</li>
                                    <li>Personalized products</li>
                                </ul>
                                <p class="mb-0"><strong>How it works:</strong> You receive notification and deliver via the order chat system.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Fees & Payments -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Fees & Payments</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <td><strong>Shop Creation</strong></td>
                                <td class="text-success">FREE</td>
                            </tr>
                            <tr>
                                <td><strong>Product Listings</strong></td>
                                <td class="text-success">Unlimited FREE</td>
                            </tr>
                            <tr>
                                <td><strong>Sales Commission</strong></td>
                                <td><?= getSetting('commission_rate', DEFAULT_COMMISSION_RATE) ?>% per sale</td>
                            </tr>
                            <tr>
                                <td><strong>Payment Hold</strong></td>
                                <td>24 hours after delivery</td>
                            </tr>
                            <tr>
                                <td><strong>Minimum Withdrawal</strong></td>
                                <td><?= formatCurrency(MIN_WITHDRAWAL) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Withdrawal Fee</strong></td>
                                <td class="text-success">FREE (only network fees)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Verification -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Seller Verification</h5>
                </div>
                <div class="card-body">
                    <p>Get verified to boost your credibility and sales!</p>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Benefits:</h6>
                            <ul class="text-muted">
                                <li>Verified badge on your profile</li>
                                <li>Higher search ranking</li>
                                <li>Increased buyer trust</li>
                                <li>Priority support</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Cost:</h6>
                            <p class="h3 text-primary"><?= formatCurrency(VERIFICATION_FEE) ?></p>
                            <p class="text-muted">One-time payment, lifetime status</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tips for Success -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Tips for Success</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-3"><i class="fas fa-star text-warning me-2"></i><strong>Quality Products</strong> - Only sell working, legitimate products</li>
                                <li class="mb-3"><i class="fas fa-camera text-primary me-2"></i><strong>Good Images</strong> - Use clear, attractive product images</li>
                                <li class="mb-3"><i class="fas fa-file-alt text-info me-2"></i><strong>Detailed Descriptions</strong> - Explain exactly what buyers get</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-3"><i class="fas fa-tag text-success me-2"></i><strong>Competitive Pricing</strong> - Research market prices</li>
                                <li class="mb-3"><i class="fas fa-reply text-danger me-2"></i><strong>Fast Response</strong> - Reply to buyers quickly</li>
                                <li class="mb-3"><i class="fas fa-thumbs-up text-primary me-2"></i><strong>Great Service</strong> - Build positive reviews</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CTA -->
            <div class="text-center mt-5">
                <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/become-seller.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-store me-2"></i>Create Your Shop
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-lg me-2">
                        <i class="fas fa-user-plus me-2"></i>Register Now
                    </a>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
