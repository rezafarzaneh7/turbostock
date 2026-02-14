<?php
/**
 * TurboStock - Store/Seller Profile Page (New Design)
 */
require_once __DIR__ . '/includes/init.php';

$sellerId = (int) ($_GET['id'] ?? 0);

if (!$sellerId) {
    redirectWithMessage(BASE_URL, 'Store not found', 'error');
}

// Get seller info
$seller = db()->fetch("
    SELECT s.*, u.username, u.created_at as member_since
    FROM sellers s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
", [$sellerId]);

if (!$seller) {
    redirectWithMessage(BASE_URL, 'Store not found', 'error');
}

// Get seller products
$page = max(1, (int) ($_GET['page'] ?? 1));
$category = $_GET['category'] ?? '';
$totalProducts = db()->count('products', "seller_id = ? AND status = 'active'", [$sellerId]);

// Get categories for this seller
$categories = db()->fetchAll("
    SELECT DISTINCT c.id, c.name, c.slug
    FROM categories c
    JOIN products p ON p.category_id = c.id
    WHERE p.seller_id = ? AND p.status = 'active'
    ORDER BY c.name
", [$sellerId]);

// Filter by category if specified
$whereClause = "seller_id = ? AND status = 'active'";
$params = [$sellerId];
if ($category) {
    $whereClause .= " AND category_id = ?";
    $params[] = (int) $category;
    $totalProducts = db()->count('products', $whereClause, $params);
}

$pagination = paginate($totalProducts, $page, 12);

// Build WHERE clause with proper table prefix for JOIN query
$productWhereClause = "p.seller_id = ? AND p.status = 'active'";
if ($category) {
    $productWhereClause .= " AND p.category_id = ?";
}

$products = db()->fetchAll("
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE {$productWhereClause}
    ORDER BY p.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

// Get seller reviews
$reviews = db()->fetchAll("
    SELECT sr.*, u.username, p.name as product_name, p.id as product_id
    FROM seller_reviews sr
    JOIN users u ON sr.buyer_id = u.id
    JOIN orders o ON sr.order_id = o.id
    JOIN products p ON o.product_id = p.id
    WHERE sr.seller_id = ? AND sr.status = 'active'
    ORDER BY sr.created_at DESC
    LIMIT 10
", [$sellerId]);

// Calculate positive percentage
$positiveReviews = db()->count('seller_reviews', "seller_id = ? AND rating >= 4", [$sellerId]);
$positivePercent = $seller['rating_count'] > 0 ? round(($positiveReviews / $seller['rating_count']) * 100) : 0;

$pageTitle = $seller['shop_name'] . ' - ' . PLATFORM_NAME;
$bodyClass = 'page-seller-detail';
require_once INCLUDES_PATH . '/header.php';
?>

<!-- Seller Header -->
<section class="seller-header">
    <div class="container">
        <div class="seller-header-content">
            <div class="seller-avatar-large">
                <?php if ($seller['shop_logo']): ?>
                    <img src="<?= UPLOADS_URL ?>/<?= $seller['shop_logo'] ?>" alt="<?= sanitize($seller['shop_name']) ?>">
                <?php else: ?>
                    <?= strtoupper(substr($seller['shop_name'], 0, 1)) ?>
                <?php endif; ?>
                <?php if ($seller['verification_status'] === 'verified'): ?>
                    <div class="seller-verified-badge"><i class="fas fa-check"></i></div>
                <?php endif; ?>
            </div>
            <div class="seller-main-info">
                <div class="seller-name-row">
                    <h1 class="seller-name"><?= sanitize($seller['shop_name']) ?></h1>
                    <?php if ($seller['verification_status'] === 'verified'): ?>
                        <span class="seller-level-badge"><i class="fas fa-shield-alt"></i> <?= __('verified_seller') ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($seller['shop_description']): ?>
                    <p class="seller-tagline"><?= sanitize($seller['shop_description']) ?></p>
                <?php else: ?>
                    <p class="seller-tagline">@<?= sanitize($seller['username']) ?></p>
                <?php endif; ?>
                <div class="seller-stats-row">
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($seller['rating_average'], 1) ?></div>
                        <div class="stat-label"><?= __('rating') ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($seller['total_sales']) ?></div>
                        <div class="stat-label"><?= __('sales') ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($totalProducts) ?></div>
                        <div class="stat-label"><?= __('products') ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $positivePercent ?>%</div>
                        <div class="stat-label"><?= __('positive') ?></div>
                    </div>
                </div>
            </div>
            <div class="seller-actions">
                <a href="<?= BASE_URL ?>/support.php?seller=<?= $sellerId ?>" class="btn btn-primary">
                    <i class="fas fa-envelope"></i> <?= __('contact_seller') ?>
                </a>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <div class="main-content">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-card">
                <h3 class="sidebar-title"><i class="fas fa-info-circle"></i> <?= __('seller_info') ?></h3>
                <ul class="info-list">
                    <li>
                        <i class="fas fa-calendar"></i>
                        <span class="label"><?= __('member_since') ?></span>
                        <span class="value"><?= formatDate($seller['member_since'], 'M Y') ?></span>
                    </li>
                    <li>
                        <i class="fas fa-star"></i>
                        <span class="label"><?= __('reviews') ?></span>
                        <span class="value"><?= number_format($seller['rating_count']) ?></span>
                    </li>
                    <li>
                        <i class="fas fa-box"></i>
                        <span class="label"><?= __('products') ?></span>
                        <span class="value"><?= number_format($totalProducts) ?></span>
                    </li>
                    <li>
                        <i class="fas fa-shopping-cart"></i>
                        <span class="label"><?= __('total_sales') ?></span>
                        <span class="value"><?= number_format($seller['total_sales']) ?></span>
                    </li>
                </ul>
            </div>

            <?php if ($seller['verification_status'] === 'verified'): ?>
            <div class="sidebar-card">
                <h3 class="sidebar-title"><i class="fas fa-medal"></i> <?= __('badges') ?></h3>
                <div class="badge-list">
                    <span class="badge-item"><i class="fas fa-shield-alt"></i> <?= __('verified') ?></span>
                    <?php if ($seller['total_sales'] >= 100): ?>
                        <span class="badge-item"><i class="fas fa-star"></i> <?= __('top_rated') ?></span>
                    <?php endif; ?>
                    <?php if ($positivePercent >= 95): ?>
                        <span class="badge-item"><i class="fas fa-thumbs-up"></i> <?= __('trusted') ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </aside>

        <!-- Main Content -->
        <main>
            <!-- Products Section -->
            <div class="section-header">
                <h2 class="section-title"><?= __('seller') ?> <span><?= __('products') ?></span></h2>
                <?php if (!empty($categories)): ?>
                <div class="filter-tabs">
                    <a href="<?= storeUrl($sellerId) ?>" class="filter-tab <?= !$category ? 'active' : '' ?>"><?= __('all') ?></a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="<?= storeUrl($sellerId) ?>?category=<?= $cat['id'] ?>"
                           class="filter-tab <?= $category == $cat['id'] ? 'active' : '' ?>">
                            <?= sanitize($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3><?= __('no_products_yet') ?></h3>
                    <p><?= __('seller_no_products') ?></p>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <a href="<?= productUrl($product['id']) ?>" class="product-card">
                            <div class="product-image">
                                <?php if ($product['delivery_type'] === 'auto'): ?>
                                    <span class="product-badge hot"><?= __('instant') ?></span>
                                <?php endif; ?>
                                <?php if ($product['thumbnail']): ?>
                                    <img src="<?= UPLOADS_URL ?>/<?= $product['thumbnail'] ?>" alt="<?= sanitize($product['name']) ?>">
                                <?php else: ?>
                                    <i class="fas fa-box"></i>
                                <?php endif; ?>
                            </div>
                            <div class="product-content">
                                <div class="product-category"><?= sanitize($product['category_name']) ?></div>
                                <h3 class="product-title"><?= sanitize(truncate($product['name'], 50)) ?></h3>
                                <div class="product-meta">
                                    <span class="stock">
                                        <i class="fas fa-cubes"></i>
                                        <?= $product['stock_quantity'] == -1 ? __('unlimited') : number_format($product['stock_quantity']) ?>
                                    </span>
                                </div>
                                <div class="product-footer">
                                    <div class="product-price"><?= formatCurrency($product['price']) ?></div>
                                    <button class="product-btn"><i class="fas fa-shopping-cart"></i></button>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="pagination-wrapper">
                        <?= renderPagination($pagination, storeUrl($sellerId) . ($category ? '?category=' . $category . '&' : '?')) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Reviews Section -->
            <div class="reviews-section">
                <h2 class="section-title"><?= __('recent') ?> <span><?= __('reviews') ?></span></h2>

                <?php if (empty($reviews)): ?>
                    <div class="empty-state small">
                        <i class="fas fa-star"></i>
                        <p><?= __('no_reviews') ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="review-avatar"><?= strtoupper(substr($review['username'], 0, 1)) ?></div>
                                <div class="review-info">
                                    <h4><?= sanitize($review['username']) ?></h4>
                                    <span class="date"><?= timeAgo($review['created_at']) ?></span>
                                </div>
                                <div class="review-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= $review['rating'] ? 'active' : '' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php if ($review['comment']): ?>
                                <p class="review-text"><?= nl2br(sanitize($review['comment'])) ?></p>
                            <?php endif; ?>
                            <a href="<?= productUrl($review['product_id']) ?>" class="review-product">
                                <i class="fas fa-box"></i> <?= sanitize(truncate($review['product_name'], 40)) ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
