<?php
/**
 * TurboStock - Product Detail Page (New Design)
 */
require_once __DIR__ . '/includes/init.php';

$productId = (int) ($_GET['id'] ?? 0);

if (!$productId) {
    redirectWithMessage(BASE_URL, 'Product not found', 'error');
}

// Get product with seller info
$product = db()->fetch("
    SELECT p.*, s.shop_name, s.shop_slug, s.verification_status, s.rating_average as seller_rating,
           s.rating_count as seller_reviews, s.id as seller_id, s.user_id as seller_user_id,
           s.total_sales as seller_total_sales,
           c.name as category_name, c.slug as category_slug, c.icon as category_icon,
           u.username as seller_username
    FROM products p
    JOIN sellers s ON p.seller_id = s.id
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON s.user_id = u.id
    WHERE p.id = ? AND p.status = 'active'
", [$productId]);

if (!$product) {
    redirectWithMessage(BASE_URL, 'Product not found or unavailable', 'error');
}

// Get product reviews with stats
$reviews = db()->fetchAll("
    SELECT pr.*, u.username, u.avatar
    FROM product_reviews pr
    JOIN users u ON pr.buyer_id = u.id
    WHERE pr.product_id = ? AND pr.status = 'active'
    ORDER BY pr.created_at DESC
    LIMIT 10
", [$productId]);

// Calculate review breakdown
$reviewStats = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $review) {
    $rating = (int) $review['rating'];
    if (isset($reviewStats[$rating])) {
        $reviewStats[$rating]++;
    }
}
$totalReviews = array_sum($reviewStats);

// Get related products
$relatedProducts = db()->fetchAll("
    SELECT p.*, s.shop_name, s.verification_status, s.rating_average as seller_rating
    FROM products p
    JOIN sellers s ON p.seller_id = s.id
    WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
    ORDER BY p.total_sales DESC
    LIMIT 4
", [$product['category_id'], $productId]);

// Check if user can purchase
$canPurchase = isLoggedIn() && getCurrentUserId() != $product['seller_user_id'];
$userBalance = isLoggedIn() ? paymentHandler()->getBalance(getCurrentUserId()) : 0;

// Handle purchase
$purchaseError = '';
$purchaseSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase'])) {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirectWithMessage(BASE_URL . '/auth/login.php', 'Please login to purchase', 'warning');
    }

    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $purchaseError = 'Invalid request. Please try again.';
    } elseif (!$canPurchase) {
        $purchaseError = 'You cannot purchase your own product.';
    } else {
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

        try {
            $result = paymentHandler()->processPurchase(getCurrentUserId(), $productId, $quantity);

            if ($result['success']) {
                redirectWithMessage(
                    orderUrl($result['order_id']),
                    'Purchase successful! Order #' . $result['order_number'],
                    'success'
                );
            }
        } catch (Exception $e) {
            $purchaseError = $e->getMessage();
        }
    }
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
    'twitter' => 'fab fa-twitter',
    'facebook' => 'fab fa-facebook',
    'instagram' => 'fab fa-instagram',
];

$catIcon = $product['category_icon'] ?? $categoryIcons[$product['category_slug']] ?? 'fa-box';

$pageTitle = $product['name'] . ' - ' . PLATFORM_NAME;
$bodyClass = 'page-product-detail';
$themeClass = 'theme-light';
require_once INCLUDES_PATH . '/header.php';
?>

<!-- Breadcrumb Section -->
<div class="breadcrumb-section">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>"><?= __('home') ?? 'Home' ?></a>
            <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
            <a href="<?= BASE_URL ?>/browse.php"><?= __('products') ?? 'Products' ?></a>
            <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
            <a href="<?= BASE_URL ?>/browse.php?category=<?= $product['category_slug'] ?>"><?= sanitize($product['category_name']) ?></a>
            <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
            <span class="current"><?= sanitize(mb_strimwidth($product['name'], 0, 50, '...')) ?></span>
        </div>
    </div>
</div>

<!-- Main Content -->
<main class="main-content">
    <div class="container">
        <!-- Product Grid -->
        <div class="product-grid">
            <!-- Left: Gallery -->
            <div class="product-gallery">
                <div class="main-image-container">
                    <?php if ($product['thumbnail']): ?>
                        <img src="<?= UPLOADS_URL ?>/<?= $product['thumbnail'] ?>" alt="<?= sanitize($product['name']) ?>" class="main-image" id="mainImage">
                    <?php else: ?>
                        <div class="image-placeholder">
                            <i class="fas <?= $catIcon ?>"></i>
                            <span><?= sanitize($product['category_name']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
                $images = $product['images'] ? json_decode($product['images'], true) : [];
                if (!empty($images) || $product['thumbnail']):
                ?>
                <div class="thumbnail-grid">
                    <?php if ($product['thumbnail']): ?>
                        <div class="thumbnail active" onclick="changeImage('<?= UPLOADS_URL ?>/<?= $product['thumbnail'] ?>', this)">
                            <img src="<?= UPLOADS_URL ?>/<?= $product['thumbnail'] ?>" alt="Thumbnail">
                        </div>
                    <?php endif; ?>
                    <?php foreach ($images as $img): ?>
                        <div class="thumbnail" onclick="changeImage('<?= UPLOADS_URL ?>/<?= $img ?>', this)">
                            <img src="<?= UPLOADS_URL ?>/<?= $img ?>" alt="Product image">
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($images) && !$product['thumbnail']): ?>
                        <div class="thumbnail active">
                            <i class="fas <?= $catIcon ?>"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right: Info -->
            <div class="product-info">
                <div class="product-badges">
                    <span class="badge badge-category"><i class="fas <?= $catIcon ?>"></i> <?= sanitize($product['category_name']) ?></span>
                    <?php if ($product['verification_status'] === 'verified'): ?>
                        <span class="badge badge-verified"><i class="fas fa-check-circle"></i> <?= __('verified_seller') ?? 'Verified' ?></span>
                    <?php endif; ?>
                    <?php if ($product['delivery_type'] === 'auto'): ?>
                        <span class="badge badge-instant"><i class="fas fa-bolt"></i> <?= __('instant_delivery') ?? 'Instant Delivery' ?></span>
                    <?php endif; ?>
                    <?php if ($product['total_sales'] > 100): ?>
                        <span class="badge badge-hot"><i class="fas fa-fire"></i> <?= __('hot') ?? 'Hot' ?></span>
                    <?php endif; ?>
                </div>

                <h1 class="product-title"><?= sanitize($product['name']) ?></h1>

                <div class="product-meta">
                    <div class="meta-item">
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= floor($product['rating_average'])): ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif ($i - 0.5 <= $product['rating_average']): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <span><strong><?= number_format($product['rating_average'], 1) ?></strong> (<?= $product['rating_count'] ?> <?= __('reviews') ?? 'reviews' ?>)</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-shopping-bag"></i>
                        <span><strong><?= number_format($product['total_sales']) ?></strong> <?= __('sold') ?? 'sold' ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-eye"></i>
                        <span><strong><?= number_format($product['views'] ?? 0) ?></strong> <?= __('views') ?? 'views' ?></span>
                    </div>
                </div>

                <div class="price-section">
                    <div class="price-row">
                        <div>
                            <span class="price-label"><?= __('price_per_unit') ?? 'Price per unit' ?></span>
                            <div class="price-value"><?= formatCurrency($product['price']) ?></div>
                        </div>
                        <div class="stock-info">
                            <i class="fas fa-circle"></i>
                            <span>
                                <?php if ($product['stock_quantity'] == -1): ?>
                                    ∞ <?= __('in_stock') ?? 'In Stock' ?>
                                <?php elseif ($product['stock_quantity'] > 0): ?>
                                    <?= number_format($product['stock_quantity']) ?> <?= __('in_stock') ?? 'In Stock' ?>
                                <?php else: ?>
                                    <?= __('out_of_stock') ?? 'Out of Stock' ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($purchaseError): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?= sanitize($purchaseError) ?>
                        </div>
                    <?php endif; ?>

                    <div class="purchase-section">
                        <?php if (isLoggedIn()): ?>
                            <?php if ($canPurchase): ?>
                                <?php if ($product['stock_quantity'] == -1 || $product['stock_quantity'] > 0): ?>
                                    <form method="POST" action="">
                                        <?= csrfField() ?>
                                        <div class="quantity-row">
                                            <span class="quantity-label"><?= __('quantity') ?? 'Quantity' ?></span>
                                            <div class="quantity-control">
                                                <button type="button" class="qty-btn" onclick="decreaseQty()">−</button>
                                                <input type="number" class="qty-input" name="quantity" id="qtyInput" value="1" min="1"
                                                       max="<?= $product['stock_quantity'] > 0 ? $product['stock_quantity'] : 99 ?>">
                                                <button type="button" class="qty-btn" onclick="increaseQty()">+</button>
                                            </div>
                                            <button type="submit" name="purchase" class="btn-buy" <?= $userBalance < $product['price'] ? 'disabled' : '' ?>>
                                                <i class="fas fa-shopping-cart"></i>
                                                <span id="buyBtnText"><?= __('buy_now') ?? 'Buy Now' ?> - <?= formatCurrency($product['price']) ?></span>
                                            </button>
                                        </div>
                                        <?php if ($userBalance < $product['price']): ?>
                                            <div class="balance-warning">
                                                <i class="fas fa-wallet"></i>
                                                <?= __('insufficient_balance') ?? 'Insufficient balance' ?>. <?= __('your_balance') ?? 'Your balance' ?>: <?= formatCurrency($userBalance) ?>
                                                <a href="<?= BASE_URL ?>/wallet.php"><?= __('add_funds') ?? 'Add Funds' ?></a>
                                            </div>
                                        <?php else: ?>
                                            <div class="balance-info">
                                                <i class="fas fa-wallet"></i> <?= __('your_balance') ?? 'Your balance' ?>: <?= formatCurrency($userBalance) ?>
                                            </div>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-buy disabled" disabled>
                                        <i class="fas fa-times"></i> <?= __('out_of_stock') ?? 'Out of Stock' ?>
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="own-product-notice">
                                    <i class="fas fa-info-circle"></i> <?= __('this_is_your_product') ?? 'This is your own product' ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/auth/login.php" class="btn-buy">
                                <i class="fas fa-sign-in-alt"></i> <?= __('login_to_purchase') ?? 'Login to Purchase' ?>
                            </a>
                        <?php endif; ?>

                        <div class="action-buttons">
                            <button class="btn-secondary-action" onclick="toggleWishlist(this)">
                                <i class="far fa-heart"></i>
                                <?= __('add_to_wishlist') ?? 'Add to Wishlist' ?>
                            </button>
                            <button class="btn-secondary-action" onclick="shareProduct()">
                                <i class="fas fa-share-alt"></i>
                                <?= __('share') ?? 'Share' ?>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="product-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($product['total_sales']) ?></div>
                        <div class="stat-label"><?= __('total_sales') ?? 'Total Sales' ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($product['rating_average'], 1) ?></div>
                        <div class="stat-label"><?= __('rating') ?? 'Rating' ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $product['delivery_type'] === 'auto' ? (__('instant') ?? 'Instant') : (__('manual') ?? 'Manual') ?></div>
                        <div class="stat-label"><?= __('delivery') ?? 'Delivery' ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description Section -->
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-file-alt"></i>
                <h3><?= __('product_description') ?? 'Product Description' ?></h3>
            </div>
            <div class="section-body">
                <div class="description-content">
                    <?php if ($product['description']): ?>
                        <?= nl2br(sanitize($product['description'])) ?>
                    <?php else: ?>
                        <p class="no-description"><?= __('no_description') ?? 'No description available for this product.' ?></p>
                    <?php endif; ?>

                    <div class="description-features">
                        <div class="feature-item">
                            <i class="fas fa-shield-alt"></i>
                            <div class="feature-item-content">
                                <h4><?= __('secure_purchase') ?? 'Secure Purchase' ?></h4>
                                <p><?= __('buyer_protection') ?? 'Buyer protection guaranteed' ?></p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-clock"></i>
                            <div class="feature-item-content">
                                <h4><?= $product['delivery_type'] === 'auto' ? (__('instant_delivery') ?? 'Instant Delivery') : (__('manual_delivery') ?? 'Manual Delivery') ?></h4>
                                <p><?= $product['delivery_type'] === 'auto' ? (__('automatic_after_payment') ?? 'Automatic after payment') : (__('seller_will_deliver') ?? 'Seller will deliver manually') ?></p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-undo"></i>
                            <div class="feature-item-content">
                                <h4><?= __('refund_policy') ?? 'Refund Policy' ?></h4>
                                <p><?= __('dispute_if_issues') ?? 'Open dispute if any issues' ?></p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-headset"></i>
                            <div class="feature-item-content">
                                <h4><?= __('support') ?? '24/7 Support' ?></h4>
                                <p><?= __('contact_seller_anytime') ?? 'Contact seller anytime' ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seller Card -->
        <div class="seller-card">
            <div class="seller-header">
                <div class="seller-avatar">
                    <?= strtoupper(substr($product['shop_name'], 0, 2)) ?>
                    <?php if ($product['verification_status'] === 'verified'): ?>
                        <div class="seller-verified-badge">
                            <i class="fas fa-check"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="seller-info">
                    <h3><?= sanitize($product['shop_name']) ?></h3>
                    <?php if ($product['verification_status'] === 'verified'): ?>
                        <span class="seller-level"><i class="fas fa-crown"></i> <?= __('verified_seller') ?? 'Verified Seller' ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="seller-stats">
                <div class="seller-stat">
                    <div class="seller-stat-value"><?= number_format($product['seller_total_sales'] ?? 0) ?></div>
                    <div class="seller-stat-label"><?= __('total_sales') ?? 'Total Sales' ?></div>
                </div>
                <div class="seller-stat">
                    <div class="seller-stat-value"><i class="fas fa-star star"></i> <?= number_format($product['seller_rating'], 1) ?></div>
                    <div class="seller-stat-label"><?= __('rating') ?? 'Rating' ?></div>
                </div>
                <div class="seller-stat">
                    <div class="seller-stat-value">98%</div>
                    <div class="seller-stat-label"><?= __('response_rate') ?? 'Response Rate' ?></div>
                </div>
                <div class="seller-stat">
                    <div class="seller-stat-value">&lt;2h</div>
                    <div class="seller-stat-label"><?= __('avg_response') ?? 'Avg Response' ?></div>
                </div>
            </div>

            <div class="seller-actions">
                <a href="<?= BASE_URL ?>/support.php?seller=<?= $product['seller_id'] ?>" class="btn-contact-seller">
                    <i class="fas fa-comment-dots"></i>
                    <?= __('contact_seller') ?? 'Contact Seller' ?>
                </a>
                <a href="<?= storeUrl($product['seller_id']) ?>" class="btn-view-products">
                    <i class="fas fa-store"></i>
                    <?= __('view_all_products') ?? 'View All Products' ?>
                </a>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-star"></i>
                <h3><?= __('customer_reviews') ?? 'Customer Reviews' ?> (<?= $totalReviews ?>)</h3>
            </div>
            <div class="section-body">
                <?php if ($totalReviews > 0): ?>
                    <div class="reviews-summary">
                        <div class="reviews-average">
                            <div class="score"><?= number_format($product['rating_average'], 1) ?></div>
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= floor($product['rating_average'])): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif ($i - 0.5 <= $product['rating_average']): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <div class="count"><?= $totalReviews ?> <?= __('reviews') ?? 'reviews' ?></div>
                        </div>
                        <div class="reviews-breakdown">
                            <?php for ($star = 5; $star >= 1; $star--): ?>
                                <?php $percentage = $totalReviews > 0 ? ($reviewStats[$star] / $totalReviews) * 100 : 0; ?>
                                <div class="rating-bar">
                                    <span class="rating-bar-label"><?= $star ?> <?= __('stars') ?? 'stars' ?></span>
                                    <div class="rating-bar-track">
                                        <div class="rating-bar-fill" style="width: <?= $percentage ?>%"></div>
                                    </div>
                                    <span class="rating-bar-count"><?= $reviewStats[$star] ?></span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="review-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="review-author">
                                        <div class="review-avatar"><?= strtoupper(substr($review['username'], 0, 2)) ?></div>
                                        <div class="review-author-info">
                                            <h4><?= sanitize($review['username']) ?></h4>
                                            <span><?= __('purchased') ?? 'Purchased' ?> <?= timeAgo($review['created_at']) ?></span>
                                        </div>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['rating']): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <?php if ($review['comment']): ?>
                                    <div class="review-content">
                                        <?= nl2br(sanitize($review['comment'])) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="review-footer">
                                    <span class="review-helpful"><i class="far fa-thumbs-up"></i> <?= __('helpful') ?? 'Helpful' ?></span>
                                    <span class="review-helpful"><i class="far fa-flag"></i> <?= __('report') ?? 'Report' ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-reviews">
                        <i class="fas fa-star"></i>
                        <h4><?= __('no_reviews_yet') ?? 'No Reviews Yet' ?></h4>
                        <p><?= __('be_first_to_review') ?? 'Be the first to review this product' ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
        <div class="related-section">
            <h2 class="section-title"><i class="fas fa-th-large"></i> <?= __('related_products') ?? 'Related Products' ?></h2>
            <div class="products-grid">
                <?php foreach ($relatedProducts as $related): ?>
                    <a href="<?= productUrl($related['id']) ?>" class="product-card">
                        <div class="product-card-image">
                            <?php if ($related['thumbnail']): ?>
                                <img src="<?= UPLOADS_URL ?>/<?= $related['thumbnail'] ?>" alt="<?= sanitize($related['name']) ?>">
                            <?php else: ?>
                                <i class="fas <?= $categoryIcons[$product['category_slug']] ?? 'fa-box' ?>"></i>
                            <?php endif; ?>
                            <?php if ($related['delivery_type'] === 'auto'): ?>
                                <div class="product-card-badges">
                                    <span class="card-badge card-badge-instant"><i class="fas fa-bolt"></i> <?= __('instant') ?? 'Instant' ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-card-body">
                            <h3 class="product-card-title"><?= sanitize(mb_strimwidth($related['name'], 0, 50, '...')) ?></h3>
                            <div class="product-card-meta">
                                <div class="product-card-seller">
                                    <div class="avatar-placeholder"><?= strtoupper(substr($related['shop_name'], 0, 2)) ?></div>
                                    <span><?= sanitize($related['shop_name']) ?></span>
                                </div>
                                <div class="product-card-rating">
                                    <i class="fas fa-star"></i> <?= number_format($related['seller_rating'] ?? 5.0, 1) ?>
                                </div>
                            </div>
                            <div class="product-card-footer">
                                <span class="product-card-price"><?= formatCurrency($related['price']) ?></span>
                                <span class="product-card-stock">
                                    <?= $related['stock_quantity'] == -1 ? '∞' : number_format($related['stock_quantity']) ?> <?= __('in_stock') ?? 'in stock' ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
    const productPrice = <?= $product['price'] ?>;
    const maxQty = <?= $product['stock_quantity'] > 0 ? $product['stock_quantity'] : 99 ?>;

    function increaseQty() {
        const input = document.getElementById('qtyInput');
        const currentValue = parseInt(input.value) || 1;
        if (currentValue < maxQty) {
            input.value = currentValue + 1;
            updateBuyButton();
        }
    }

    function decreaseQty() {
        const input = document.getElementById('qtyInput');
        const currentValue = parseInt(input.value) || 1;
        if (currentValue > 1) {
            input.value = currentValue - 1;
            updateBuyButton();
        }
    }

    function updateBuyButton() {
        const qty = parseInt(document.getElementById('qtyInput').value) || 1;
        const total = (qty * productPrice).toFixed(2);
        const btnText = document.getElementById('buyBtnText');
        if (btnText) {
            btnText.innerHTML = '<?= __('buy_now') ?? 'Buy Now' ?> - $' + total;
        }
    }

    function changeImage(src, element) {
        const mainImage = document.getElementById('mainImage');
        if (mainImage) {
            mainImage.src = src;
        }
        document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
        element.classList.add('active');
    }

    function toggleWishlist(btn) {
        const icon = btn.querySelector('i');
        if (icon.classList.contains('far')) {
            icon.classList.remove('far');
            icon.classList.add('fas');
            btn.style.color = '#ef5350';
        } else {
            icon.classList.remove('fas');
            icon.classList.add('far');
            btn.style.color = '';
        }
    }

    function shareProduct() {
        if (navigator.share) {
            navigator.share({
                title: '<?= addslashes($product['name']) ?>',
                url: window.location.href
            });
        } else {
            navigator.clipboard.writeText(window.location.href);
            alert('<?= __('link_copied') ?? 'Link copied to clipboard!' ?>');
        }
    }

    // Update button on quantity input change
    document.getElementById('qtyInput')?.addEventListener('change', updateBuyButton);
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
