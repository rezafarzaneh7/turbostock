<?php
/**
 * TurboStock - Browse Products
 */
require_once __DIR__ . '/includes/init.php';

// Get filters
$category = clean($_GET['category'] ?? '');
$search = clean($_GET['q'] ?? '');
$sort = clean($_GET['sort'] ?? 'newest');
$minPrice = (float) ($_GET['min_price'] ?? 0);
$maxPrice = (float) ($_GET['max_price'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));

// Build query
$where = ["p.status = 'active'"];
$params = [];

if ($category) {
    $where[] = "c.slug = ?";
    $params[] = $category;
}

if ($search) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ? OR s.shop_name LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($minPrice > 0) {
    $where[] = "p.price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $where[] = "p.price <= ?";
    $params[] = $maxPrice;
}

$whereClause = implode(' AND ', $where);

// Sort
$orderBy = match($sort) {
    'popular' => 'p.total_sales DESC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'rating' => 'p.rating_average DESC',
    default => 'p.created_at DESC'
};

// Count total
$totalProducts = db()->fetch("
    SELECT COUNT(*) as count
    FROM products p
    JOIN sellers s ON p.seller_id = s.id
    JOIN categories c ON p.category_id = c.id
    WHERE {$whereClause}
", $params)['count'];

$pagination = paginate($totalProducts, $page, ITEMS_PER_PAGE);

// Get products
$products = db()->fetchAll("
    SELECT p.*, s.shop_name, s.verification_status, s.id as seller_id, s.rating_average as seller_rating,
           c.name as category_name, c.slug as category_slug
    FROM products p
    JOIN sellers s ON p.seller_id = s.id
    JOIN categories c ON p.category_id = c.id
    WHERE {$whereClause}
    ORDER BY {$orderBy}
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

// Get categories for filter
$categories = db()->fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order");

// Get current category
$currentCategory = null;
if ($category) {
    $currentCategory = db()->fetch("SELECT * FROM categories WHERE slug = ?", [$category]);
}

// Category icons mapping
$categoryIcons = [
    'gaming' => 'fa-gamepad',
    'software' => 'fa-code',
    'accounts' => 'fa-user-circle',
    'gift-cards' => 'fa-gift',
    'design' => 'fa-palette',
    'streaming' => 'fa-play-circle',
    'e-books' => 'fa-book',
    'audio' => 'fa-music',
];

$pageTitle = ($currentCategory ? $currentCategory['name'] . ' - ' : ($search ? 'Search: ' . $search . ' - ' : '')) . 'Browse Products - TurboStock';
$bodyClass = 'page-category';
$themeClass = 'theme-light';
require_once INCLUDES_PATH . '/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>">Home</a>
            <span>/</span>
            <?php if ($currentCategory): ?>
                <a href="<?= BASE_URL ?>/browse.php">Browse</a>
                <span>/</span>
                <span class="current"><?= sanitize($currentCategory['name']) ?></span>
            <?php elseif ($search): ?>
                <span class="current">Search: <?= sanitize($search) ?></span>
            <?php else: ?>
                <span class="current">Browse Products</span>
            <?php endif; ?>
        </div>
        <h1 class="page-title">
            <?php if ($currentCategory): ?>
                <?= sanitize($currentCategory['name']) ?> <span>Products</span>
            <?php elseif ($search): ?>
                Search <span>Results</span>
            <?php else: ?>
                Browse <span>Products</span>
            <?php endif; ?>
        </h1>
    </div>
</div>

<!-- Main Content -->
<div class="container">
    <div class="main-content">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="filter-card">
                <h3 class="filter-title"><i class="fas fa-th-large"></i> Categories</h3>
                <div class="categories-list">
                    <a href="<?= BASE_URL ?>/browse.php<?= $search ? '?q=' . urlencode($search) : '' ?>" class="category-item <?= !$category ? 'active' : '' ?>">
                        <i class="fas fa-layer-group"></i> All Categories
                        <span class="count"><?= number_format($totalProducts) ?></span>
                    </a>
                    <?php foreach ($categories as $cat):
                        $catCount = db()->count('products', "category_id = ? AND status = 'active'", [$cat['id']]);
                        $icon = $cat['icon'] ?? $categoryIcons[$cat['slug']] ?? 'fa-folder';
                    ?>
                        <a href="<?= BASE_URL ?>/browse.php?category=<?= $cat['slug'] ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="category-item <?= $category === $cat['slug'] ? 'active' : '' ?>">
                            <i class="fas <?= $icon ?>"></i> <?= sanitize($cat['name']) ?>
                            <span class="count"><?= number_format($catCount) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="filter-card">
                <h3 class="filter-title"><i class="fas fa-filter"></i> Filters</h3>

                <form method="GET" action="<?= BASE_URL ?>/browse.php" id="filterForm">
                    <?php if ($category): ?>
                        <input type="hidden" name="category" value="<?= sanitize($category) ?>">
                    <?php endif; ?>
                    <?php if ($search): ?>
                        <input type="hidden" name="q" value="<?= sanitize($search) ?>">
                    <?php endif; ?>
                    <?php if ($sort && $sort !== 'newest'): ?>
                        <input type="hidden" name="sort" value="<?= sanitize($sort) ?>">
                    <?php endif; ?>

                    <div class="filter-group">
                        <div class="filter-group-title"><i class="fas fa-dollar-sign"></i> Price Range</div>
                        <div class="price-inputs">
                            <input type="number" name="min_price" class="price-input" placeholder="Min" value="<?= $minPrice > 0 ? $minPrice : '' ?>" min="0" step="0.01">
                            <span class="price-separator">-</span>
                            <input type="number" name="max_price" class="price-input" placeholder="Max" value="<?= $maxPrice > 0 ? $maxPrice : '' ?>" min="0" step="0.01">
                        </div>
                    </div>

                    <div class="filter-group">
                        <div class="filter-group-title"><i class="fas fa-star"></i> Rating</div>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="checkbox" name="rating[]" value="4.5">
                                <span class="custom-checkbox"></span>
                                <span class="stars">★★★★★</span>
                                <span class="label-text">(4.5+)</span>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="rating[]" value="4.0">
                                <span class="custom-checkbox"></span>
                                <span class="stars">★★★★☆</span>
                                <span class="label-text">(4.0+)</span>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="rating[]" value="3.0">
                                <span class="custom-checkbox"></span>
                                <span class="stars">★★★☆☆</span>
                                <span class="label-text">(3.0+)</span>
                            </label>
                        </div>
                    </div>

                    <div class="filter-group">
                        <div class="filter-group-title"><i class="fas fa-check-circle"></i> Seller Status</div>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="checkbox" name="verified" value="1">
                                <span class="custom-checkbox"></span>
                                <span class="label-text">Verified Sellers</span>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" name="top_rated" value="1">
                                <span class="custom-checkbox"></span>
                                <span class="label-text">Top Rated</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-apply">
                        <i class="fas fa-check"></i> Apply Filters
                    </button>
                </form>
            </div>
        </aside>

        <!-- Products -->
        <main class="products-section">
            <div class="products-header">
                <p class="results-count">
                    Showing <strong><?= $pagination['offset'] + 1 ?>-<?= min($pagination['offset'] + $pagination['per_page'], $totalProducts) ?></strong> of <strong><?= number_format($totalProducts) ?></strong> products
                </p>
                <div class="sort-wrapper">
                    <div class="view-toggle">
                        <button class="view-btn active"><i class="fas fa-th"></i></button>
                        <button class="view-btn"><i class="fas fa-list"></i></button>
                    </div>
                    <select class="sort-select" onchange="updateSort(this.value)">
                        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most Popular</option>
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Best Rating</option>
                    </select>
                </div>
            </div>

            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No Products Found</h3>
                    <p>Try adjusting your filters or search terms</p>
                    <a href="<?= BASE_URL ?>/browse.php" class="btn btn-primary">View All Products</a>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <a href="<?= productUrl($product['id']) ?>" class="product-card">
                            <div class="product-image">
                                <?php if ($product['delivery_type'] === 'auto'): ?>
                                    <span class="product-badge hot">INSTANT</span>
                                <?php elseif ($product['total_sales'] > 100): ?>
                                    <span class="product-badge hot">HOT</span>
                                <?php elseif (strtotime($product['created_at']) > strtotime('-7 days')): ?>
                                    <span class="product-badge new">NEW</span>
                                <?php endif; ?>
                                <div class="product-wishlist"><i class="far fa-heart"></i></div>
                                <?php if ($product['thumbnail']): ?>
                                    <img src="<?= UPLOADS_URL ?>/<?= $product['thumbnail'] ?>" alt="<?= sanitize($product['name']) ?>">
                                <?php else: ?>
                                    <?php
                                    $catIcon = $categoryIcons[$product['category_slug']] ?? 'fa-box';
                                    ?>
                                    <i class="fas <?= $catIcon ?>"></i>
                                <?php endif; ?>
                            </div>
                            <div class="product-content">
                                <div class="product-category"><?= sanitize($product['category_name']) ?></div>
                                <h3 class="product-title"><?= sanitize(mb_strimwidth($product['name'], 0, 45, '...')) ?></h3>
                                <div class="product-meta">
                                    <div class="product-rating">
                                        <i class="fas fa-star"></i> <?= number_format($product['seller_rating'] ?? 5.0, 1) ?>
                                        <span>(<?= number_format($product['total_reviews'] ?? 0) ?>)</span>
                                    </div>
                                    <div class="product-sales">
                                        <i class="fas fa-shopping-bag"></i>
                                        <?= $product['total_sales'] >= 1000 ? number_format($product['total_sales'] / 1000, 1) . 'K' : number_format($product['total_sales']) ?> sold
                                    </div>
                                </div>
                                <div class="product-footer">
                                    <div class="product-price"><?= formatCurrency($product['price']) ?></div>
                                    <button class="product-btn"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <a href="<?= BASE_URL ?>/browse.php?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>" class="page-btn">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $pagination['current_page'] - 2);
                        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);

                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <a href="<?= BASE_URL ?>/browse.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                               class="page-btn <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <a href="<?= BASE_URL ?>/browse.php?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>" class="page-btn">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
function updateSort(value) {
    var url = new URL(window.location.href);
    url.searchParams.set('sort', value);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
