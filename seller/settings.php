<?php
/**
 * HStore - Seller Shop Settings
 */
require_once __DIR__ . '/../includes/init.php';
requireSeller();

$userId = getCurrentUserId();
$seller = db()->fetch("SELECT * FROM sellers WHERE user_id = ?", [$userId]);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request';
    } else {
        $shopName = clean($_POST['shop_name'] ?? '');
        $shopDescription = clean($_POST['shop_description'] ?? '');
        
        if (empty($shopName)) {
            $error = 'Shop name is required';
        } else {
            $updateData = [
                'shop_name' => $shopName,
                'shop_description' => $shopDescription
            ];
            
            // Handle logo upload
            if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] === UPLOAD_ERR_OK) {
                $result = uploadFile($_FILES['shop_logo'], 'shops');
                if ($result['success']) {
                    $updateData['shop_logo'] = $result['path'];
                } else {
                    $error = $result['error'];
                }
            }
            
            // Handle banner upload
            if (!$error && isset($_FILES['shop_banner']) && $_FILES['shop_banner']['error'] === UPLOAD_ERR_OK) {
                $result = uploadFile($_FILES['shop_banner'], 'shops');
                if ($result['success']) {
                    $updateData['shop_banner'] = $result['path'];
                } else {
                    $error = $result['error'];
                }
            }
            
            if (!$error) {
                db()->update('sellers', $updateData, 'id = ?', [$seller['id']]);
                $seller = db()->fetch("SELECT * FROM sellers WHERE id = ?", [$seller['id']]);
                $success = 'Settings updated successfully!';
            }
        }
    }
}

$pageTitle = 'Shop Settings - Seller Dashboard';
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
                    <a class="nav-link" href="<?= BASE_URL ?>/seller/verification.php">
                        <i class="fas fa-check-circle"></i> <?= __('verification') ?>
                    </a>
                    <a class="nav-link active" href="<?= BASE_URL ?>/seller/settings.php">
                        <i class="fas fa-cog"></i> <?= __('shop_settings') ?>
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9 col-xl-10">
            <h4 class="fw-bold mb-4"><?= __('shop_settings') ?></h4>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= sanitize($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= sanitize($success) ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label"><?= __('shop_name') ?> <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="shop_name" required
                                           value="<?= sanitize($seller['shop_name']) ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?= __('shop_description') ?></label>
                                    <textarea class="form-control" name="shop_description" rows="4"><?= sanitize($seller['shop_description']) ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?= __('shop_url') ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?= BASE_URL ?>/store/</span>
                                        <input type="text" class="form-control" value="<?= $seller['id'] ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label"><?= __('shop_logo') ?></label>
                                    <?php if ($seller['shop_logo']): ?>
                                        <img src="<?= UPLOADS_URL ?>/<?= $seller['shop_logo'] ?>" class="img-fluid rounded mb-2" style="max-height: 150px;">
                                    <?php endif; ?>
                                    <input type="file" class="form-control" name="shop_logo" accept="image/*">
                                    <small class="text-muted">Recommended: 200x200px</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?= __('shop_banner') ?></label>
                                    <?php if ($seller['shop_banner']): ?>
                                        <img src="<?= UPLOADS_URL ?>/<?= $seller['shop_banner'] ?>" class="img-fluid rounded mb-2">
                                    <?php endif; ?>
                                    <input type="file" class="form-control" name="shop_banner" accept="image/*">
                                    <small class="text-muted">Recommended: 1200x300px</small>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3"><?= __('shop_statistics') ?></h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted"><?= __('total_sales') ?></span>
                                    <span class="fw-bold"><?= $seller['total_sales'] ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted"><?= __('total_earnings') ?></span>
                                    <span class="fw-bold"><?= formatCurrency($seller['total_earnings']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted"><?= __('rating') ?></span>
                                    <span class="fw-bold"><?= number_format($seller['rating_average'], 1) ?> (<?= $seller['rating_count'] ?> reviews)</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted"><?= __('member_since') ?></span>
                                    <span class="fw-bold"><?= formatDate($seller['created_at']) ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3"><?= __('verification_status') ?></h6>
                                <p><?= getVerificationBadge($seller['verification_status']) ?></p>
                                <?php if ($seller['verification_status'] !== 'verified'): ?>
                                    <a href="<?= BASE_URL ?>/seller/verification.php" class="btn btn-outline-success btn-sm">
                                        <?= __('get_verified') ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i><?= __('save_changes') ?>
                        </button>
                        <a href="<?= storeUrl($seller['id']) ?>" class="btn btn-outline-secondary" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i><?= __('view_store') ?>
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
