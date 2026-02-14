<?php
/**
 * TurboStock - Homepage
 */
require_once __DIR__ . '/includes/init.php';

// Get categories
$categories = db()->fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order LIMIT 6");

// Get featured products
$featuredProducts = db()->fetchAll("
    SELECT p.*, s.shop_name, s.verification_status, s.rating_average as seller_rating,
           c.name as category_name
    FROM products p
    JOIN sellers s ON p.seller_id = s.id
    JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active'
    ORDER BY p.total_sales DESC, p.created_at DESC
    LIMIT 4
");

// Get top sellers
$topSellers = db()->fetchAll("
    SELECT s.*, u.username
    FROM sellers s
    JOIN users u ON s.user_id = u.id
    WHERE s.verification_status = 'verified'
    ORDER BY s.rating_average DESC, s.total_sales DESC
    LIMIT 4
");

// Stats
$totalProducts = db()->count('products', "status = 'active'");
$totalSellers = db()->count('sellers');
$totalOrders = db()->count('orders', "status = 'completed'");

$pageTitle = 'TurboStock - Premium Digital Marketplace';
$bodyClass = 'page-turbostock-source';
$themeClass = 'theme-light';
require_once INCLUDES_PATH . '/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-container">
        <div class="hero-content">
            <div class="hero-text">
                <div class="hero-badge">
                    <i class="fas fa-fire"></i>
                    #1 Digital Marketplace
                </div>
                <h1 class="hero-title">
                    Buy & Sell <span>Digital Products</span> Instantly
                </h1>
                <p class="hero-description">
                    TurboStock is the fastest marketplace for digital goods. Accounts, software, game items, and more. Instant delivery, secure payments.
                </p>
                <div class="hero-buttons">
                    <a href="<?= BASE_URL ?>/browse.php" class="btn btn-primary">
                        <i class="fas fa-rocket"></i> Start Shopping
                    </a>
                    <a href="<?= BASE_URL ?>/become-seller.php" class="btn btn-outline">
                        <i class="fas fa-store"></i> Become a Seller
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($totalProducts) ?>+</div>
                        <div class="stat-label">Products</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($totalSellers) ?>+</div>
                        <div class="stat-label">Verified Sellers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($totalOrders) ?>+</div>
                        <div class="stat-label">Orders Completed</div>
                    </div>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-cards">
                    <div class="hero-card">
                        <div class="hero-card-image">
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <div class="hero-card-title">Gaming Accounts</div>
                        <div class="hero-card-price">From $9.99</div>
                    </div>
                    <div class="hero-card">
                        <div class="hero-card-image">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="hero-card-title">Software Keys</div>
                        <div class="hero-card-price">From $14.99</div>
                    </div>
                    <div class="hero-card">
                        <div class="hero-card-image">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div class="hero-card-title">Gift Cards</div>
                        <div class="hero-card-price">From $5.00</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories">
    <div class="section-container">
        <div class="section-header">
            <h2 class="section-title">Browse <span>Categories</span></h2>
            <a href="<?= BASE_URL ?>/categories.php" class="section-link">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="categories-grid">
            <?php
            $categoryIcons = [
                'gaming' => 'fa-gamepad',
                'software' => 'fa-code',
                'accounts' => 'fa-user-circle',
                'gift-cards' => 'fa-gift',
                'design' => 'fa-palette',
                'streaming' => 'fa-play-circle'
            ];
            foreach ($categories as $cat):
                $icon = $cat['icon'] ?? $categoryIcons[$cat['slug']] ?? 'fa-folder';
                $productCount = db()->count('products', "category_id = ? AND status = 'active'", [$cat['id']]);
            ?>
                <a href="<?= BASE_URL ?>/browse.php?category=<?= $cat['slug'] ?>" class="category-card">
                    <div class="category-icon">
                        <i class="fas <?= $icon ?>"></i>
                    </div>
                    <div class="category-name"><?= sanitize($cat['name']) ?></div>
                    <div class="category-count"><?= number_format($productCount) ?> products</div>
                </a>
            <?php endforeach; ?>
            <?php if (count($categories) < 6): ?>
                <a href="<?= BASE_URL ?>/categories.php" class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-ellipsis-h"></i>
                    </div>
                    <div class="category-name">More</div>
                    <div class="category-count"><?= number_format($totalProducts) ?>+ products</div>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<?php if (!empty($featuredProducts)): ?>
<section class="featured">
    <div class="section-container">
        <div class="section-header">
            <h2 class="section-title">Featured <span>Products</span></h2>
            <a href="<?= BASE_URL ?>/browse.php?sort=popular" class="section-link">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="products-grid">
            <?php foreach ($featuredProducts as $product): ?>
                <a href="<?= productUrl($product['id']) ?>" class="product-card">
                    <div class="product-image">
                        <?php if ($product['delivery_type'] === 'auto'): ?>
                            <span class="product-badge hot">INSTANT</span>
                        <?php elseif ($product['total_sales'] > 100): ?>
                            <span class="product-badge hot">HOT</span>
                        <?php endif; ?>
                        <div class="product-wishlist">
                            <i class="far fa-heart"></i>
                        </div>
                        <?php if ($product['thumbnail']): ?>
                            <img src="<?= UPLOADS_URL ?>/<?= $product['thumbnail'] ?>" alt="<?= sanitize($product['name']) ?>">
                        <?php else: ?>
                            <i class="fas fa-box"></i>
                        <?php endif; ?>
                    </div>
                    <div class="product-content">
                        <div class="product-category"><?= sanitize($product['category_name']) ?></div>
                        <h3 class="product-title"><?= sanitize(mb_strimwidth($product['name'], 0, 45, '...')) ?></h3>
                        <div class="product-meta">
                            <div class="product-rating">
                                <i class="fas fa-star"></i>
                                <?= number_format($product['seller_rating'] ?? 5.0, 1) ?>
                            </div>
                            <div class="product-sales">
                                <i class="fas fa-shopping-bag"></i> <?= number_format($product['total_sales']) ?> sold
                            </div>
                        </div>
                        <div class="product-footer">
                            <div class="product-price"><?= formatCurrency($product['price']) ?></div>
                            <button class="product-btn">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Top Sellers Section -->
<?php if (!empty($topSellers)): ?>
<section class="sellers">
    <div class="section-container">
        <div class="section-header">
            <h2 class="section-title">Top <span>Sellers</span></h2>
            <a href="<?= BASE_URL ?>/sellers.php" class="section-link">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="sellers-grid">
            <?php foreach ($topSellers as $seller): ?>
                <a href="<?= storeUrl($seller['id']) ?>" class="seller-card">
                    <div class="seller-avatar">
                        <?php if ($seller['shop_logo']): ?>
                            <img src="<?= UPLOADS_URL ?>/<?= $seller['shop_logo'] ?>" alt="<?= sanitize($seller['shop_name']) ?>">
                        <?php else: ?>
                            <?= strtoupper(substr($seller['shop_name'], 0, 1)) ?>
                        <?php endif; ?>
                        <?php if ($seller['verification_status'] === 'verified'): ?>
                            <div class="seller-verified">
                                <i class="fas fa-check"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="seller-name"><?= sanitize($seller['shop_name']) ?></div>
                    <div class="seller-level">
                        <?php
                        $level = 1;
                        if ($seller['total_sales'] >= 1000) $level = 5;
                        elseif ($seller['total_sales'] >= 500) $level = 4;
                        elseif ($seller['total_sales'] >= 100) $level = 3;
                        elseif ($seller['total_sales'] >= 50) $level = 2;
                        ?>
                        Level <?= $level ?> Seller
                    </div>
                    <div class="seller-stats">
                        <div class="seller-stat">
                            <div class="seller-stat-value"><?= number_format($seller['rating_average'], 1) ?></div>
                            <div class="seller-stat-label">Rating</div>
                        </div>
                        <div class="seller-stat">
                            <div class="seller-stat-value"><?= $seller['total_sales'] >= 1000 ? number_format($seller['total_sales'] / 1000, 1) . 'K' : number_format($seller['total_sales']) ?></div>
                            <div class="seller-stat-label">Sales</div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Features Section -->
<section class="features">
    <div class="section-container">
        <div class="section-header">
            <h2 class="section-title">Why Choose <span>TurboStock</span></h2>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3 class="feature-title">Instant Delivery</h3>
                <p class="feature-desc">Get your products delivered automatically within seconds after payment</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="feature-title">Secure Payments</h3>
                <p class="feature-desc">Multiple payment options with buyer protection on every purchase</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 class="feature-title">24/7 Support</h3>
                <p class="feature-desc">Our dedicated team is here to help you anytime, anywhere</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <h3 class="feature-title">Low Fees</h3>
                <p class="feature-desc">Only <?= DEFAULT_COMMISSION_RATE ?>% commission for sellers - one of the lowest in the market</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta">
    <div class="section-container">
        <div class="cta-card">
            <div class="cta-content">
                <div class="cta-badge">
                    <i class="fas fa-rocket"></i>
                    Start Earning Today
                </div>
                <h2 class="cta-title">Ready to Sell Your <span>Digital Products</span>?</h2>
                <p class="cta-desc">Join thousands of successful sellers on TurboStock. Set up your store in minutes and start earning with instant payouts.</p>
                <div class="cta-buttons">
                    <a href="<?= BASE_URL ?>/become-seller.php" class="btn btn-primary">
                        <i class="fas fa-store"></i> Open Your Store
                    </a>
                    <a href="<?= BASE_URL ?>/seller-guide.php" class="btn btn-outline">
                        <i class="fas fa-info-circle"></i> Learn More
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
