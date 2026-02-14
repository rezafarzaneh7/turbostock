<?php
/**
 * HStore - Sellers List Page
 */
require_once __DIR__ . '/includes/init.php';

$page = max(1, (int) ($_GET['page'] ?? 1));
$sort = clean($_GET['sort'] ?? 'rating');

$orderBy = match($sort) {
    'sales' => 's.total_sales DESC',
    'newest' => 's.created_at DESC',
    default => 's.rating_average DESC, s.total_sales DESC'
};

$totalSellers = db()->count('sellers');
$pagination = paginate($totalSellers, $page, 12);

$sellers = db()->fetchAll("
    SELECT s.*, u.username,
           (SELECT COUNT(*) FROM products WHERE seller_id = s.id AND status = 'active') as product_count
    FROM sellers s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.verification_status = 'verified' DESC, {$orderBy}
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
");

$pageTitle = 'Sellers - ' . PLATFORM_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-store me-2"></i>Marketplace Sellers</h4>
        <select class="form-select" style="width: auto;" onchange="window.location.href='<?= BASE_URL ?>/sellers.php?sort='+this.value">
            <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Top Rated</option>
            <option value="sales" <?= $sort === 'sales' ? 'selected' : '' ?>>Most Sales</option>
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
        </select>
    </div>
    
    <?php if (empty($sellers)): ?>
        <div class="card">
            <div class="card-body empty-state">
                <i class="fas fa-store"></i>
                <h5>No sellers yet</h5>
                <p class="text-muted">Be the first to start selling!</p>
                <a href="<?= BASE_URL ?>/become-seller.php" class="btn btn-primary">Become a Seller</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($sellers as $seller): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card seller-card h-100">
                        <?php if ($seller['shop_logo']): ?>
                            <img src="<?= UPLOADS_URL ?>/<?= $seller['shop_logo'] ?>" class="seller-avatar mx-auto" alt="<?= sanitize($seller['shop_name']) ?>">
                        <?php else: ?>
                            <div class="seller-avatar-placeholder">
                                <?= strtoupper(substr($seller['shop_name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <h6 class="fw-bold mb-1">
                            <a href="<?= storeUrl($seller['id']) ?>" class="text-dark text-decoration-none">
                                <?= sanitize($seller['shop_name']) ?>
                            </a>
                        </h6>
                        <p class="text-muted small mb-2">@<?= sanitize($seller['username']) ?></p>
                        <?= getVerificationBadge($seller['verification_status']) ?>
                        <div class="mt-2">
                            <?= renderStars($seller['rating_average']) ?>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <?= $seller['product_count'] ?> products • <?= $seller['total_sales'] ?> sales
                            </small>
                        </div>
                        <a href="<?= storeUrl($seller['id']) ?>" class="btn btn-outline-primary btn-sm mt-3">
                            View Store
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-4">
            <?= renderPagination($pagination, BASE_URL . '/sellers.php?sort=' . $sort) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
