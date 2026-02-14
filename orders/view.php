<?php
/**
 * TurboStock - Order View Page (New Design)
 */
require_once __DIR__ . '/../includes/init.php';
requireLogin();

$orderId = (int) ($_GET['id'] ?? 0);
$userId = getCurrentUserId();

if (!$orderId) {
    redirectWithMessage(BASE_URL . '/orders.php', 'Order not found', 'error');
}

// Get order
$order = db()->fetch("
    SELECT o.*, p.name as product_name, p.thumbnail as product_thumbnail, p.description as product_description,
           p.delivery_type, c.name as category_name, c.slug as category_slug, c.icon as category_icon,
           s.shop_name, s.verification_status, s.user_id as seller_user_id, s.id as seller_id,
           u.username as seller_username
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN sellers s ON o.seller_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE o.id = ? AND (o.buyer_id = ? OR s.user_id = ?)
", [$orderId, $userId, $userId]);

if (!$order) {
    redirectWithMessage(BASE_URL . '/orders.php', 'Order not found or access denied', 'error');
}

$isBuyer = $order['buyer_id'] == $userId;
$isSeller = $order['seller_user_id'] == $userId;

// Handle actions
$reviewError = '';
$reviewSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $reviewError = 'Invalid request';
    } elseif (isset($_POST['submit_product_review']) && $isBuyer && $order['status'] === 'completed' && !$order['buyer_reviewed']) {
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = clean($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $reviewError = 'Please select a rating';
        } else {
            try {
                db()->insert('product_reviews', [
                    'product_id' => $order['product_id'],
                    'buyer_id' => $userId,
                    'order_id' => $orderId,
                    'rating' => $rating,
                    'comment' => $comment
                ]);

                $avgRating = db()->fetch(
                    "SELECT AVG(rating) as avg, COUNT(*) as count FROM product_reviews WHERE product_id = ? AND status = 'active'",
                    [$order['product_id']]
                );
                db()->update('products', [
                    'rating_average' => $avgRating['avg'],
                    'rating_count' => $avgRating['count']
                ], 'id = ?', [$order['product_id']]);

                db()->update('orders', ['buyer_reviewed' => 1], 'id = ?', [$orderId]);

                $reviewSuccess = 'Product review submitted successfully!';
                $order['buyer_reviewed'] = 1;
            } catch (Exception $e) {
                $reviewError = 'Failed to submit review';
            }
        }
    } elseif (isset($_POST['release_payment']) && $isBuyer && $order['status'] === 'delivered') {
        try {
            paymentHandler()->releasePayment($orderId, $userId);
            redirectWithMessage(orderUrl($orderId), 'Payment released successfully! The seller has received the funds.', 'success');
        } catch (Exception $e) {
            $reviewError = $e->getMessage();
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
];
$catIcon = $order['category_icon'] ?? $categoryIcons[$order['category_slug']] ?? 'fa-box';

// Status info
$statusInfo = [
    'pending' => ['class' => 'pending', 'icon' => 'fa-clock', 'label' => __('pending') ?? 'Pending'],
    'processing' => ['class' => 'processing', 'icon' => 'fa-spinner', 'label' => __('processing') ?? 'Processing'],
    'delivered' => ['class' => 'delivered', 'icon' => 'fa-truck', 'label' => __('delivered') ?? 'Delivered'],
    'completed' => ['class' => 'completed', 'icon' => 'fa-check-circle', 'label' => __('completed') ?? 'Completed'],
    'cancelled' => ['class' => 'cancelled', 'icon' => 'fa-times-circle', 'label' => __('cancelled') ?? 'Cancelled'],
    'refunded' => ['class' => 'refunded', 'icon' => 'fa-undo', 'label' => __('refunded') ?? 'Refunded'],
    'disputed' => ['class' => 'disputed', 'icon' => 'fa-exclamation-triangle', 'label' => __('disputed') ?? 'Disputed'],
];
$currentStatus = $statusInfo[$order['status']] ?? $statusInfo['pending'];

$pageTitle = 'Order #' . $order['order_number'] . ' - ' . PLATFORM_NAME;
$bodyClass = 'page-order-details';
$themeClass = 'theme-light';
require_once INCLUDES_PATH . '/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>"><?= __('home') ?? 'Home' ?></a>
            <span>/</span>
            <a href="<?= BASE_URL ?>/orders.php"><?= __('my_orders') ?? 'My Orders' ?></a>
            <span>/</span>
            <span class="current">#<?= $order['order_number'] ?></span>
        </div>
        <h1 class="page-title">
            <?= __('order') ?? 'Order' ?> <span>#<?= $order['order_number'] ?></span>
            <span class="order-status-badge <?= $currentStatus['class'] ?>">
                <i class="fas <?= $currentStatus['icon'] ?>"></i> <?= $currentStatus['label'] ?>
            </span>
        </h1>
    </div>
</div>

<!-- Main Content -->
<div class="container">
    <?php if ($reviewSuccess): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $reviewSuccess ?></div>
    <?php endif; ?>
    <?php if ($reviewError): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $reviewError ?></div>
    <?php endif; ?>

    <div class="main-content">
        <!-- Left Column: Order Details -->
        <div class="order-details">
            <!-- Product Information -->
            <div class="card">
                <h3 class="card-title"><i class="fas fa-box"></i> <?= __('product_information') ?? 'Product Information' ?></h3>
                <div class="product-info">
                    <div class="product-image">
                        <?php if ($order['product_thumbnail']): ?>
                            <img src="<?= UPLOADS_URL ?>/<?= $order['product_thumbnail'] ?>" alt="<?= sanitize($order['product_name']) ?>">
                        <?php else: ?>
                            <i class="fas <?= $catIcon ?>"></i>
                        <?php endif; ?>
                    </div>
                    <div class="product-details">
                        <div class="category"><?= sanitize($order['category_name']) ?></div>
                        <h3><a href="<?= productUrl($order['product_id']) ?>"><?= sanitize($order['product_name']) ?></a></h3>
                        <p class="seller">
                            <?= __('sold_by') ?? 'Sold by' ?>
                            <a href="<?= storeUrl($order['seller_id']) ?>">
                                <?= sanitize($order['shop_name']) ?>
                                <?php if ($order['verification_status'] === 'verified'): ?>
                                    <i class="fas fa-check-circle"></i>
                                <?php endif; ?>
                            </a>
                        </p>
                        <div class="product-meta">
                            <span><strong><?= __('quantity') ?? 'Quantity' ?>:</strong> <?= $order['quantity'] ?></span>
                            <span><strong><?= __('unit_price') ?? 'Unit Price' ?>:</strong> <?= formatCurrency($order['unit_price']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery Content (for completed orders) -->
            <?php if (in_array($order['status'], ['delivered', 'completed']) && $order['delivery_data'] && $isBuyer): ?>
                <div class="card">
                    <h3 class="card-title"><i class="fas fa-key"></i> <?= __('delivery_content') ?? 'Delivery Content' ?></h3>
                    <div class="delivery-content">
                        <h4><?= __('account_credentials') ?? 'Account Credentials' ?></h4>
                        <div class="delivery-code">
                            <button class="copy-btn" onclick="copyDeliveryData()"><i class="fas fa-copy"></i> <?= __('copy') ?? 'Copy' ?></button>
                            <pre id="deliveryData"><?= sanitize($order['delivery_data']) ?></pre>
                        </div>
                    </div>

                    <?php if ($order['status'] === 'delivered'): ?>
                        <div class="release-payment-notice">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong><?= __('confirm_receipt') ?? 'Please confirm receipt' ?></strong>
                                <p><?= __('confirm_receipt_desc') ?? 'If you have received your product and everything is correct, please release the payment to the seller.' ?></p>
                            </div>
                        </div>
                        <form method="POST" action="" onsubmit="return confirm('<?= __('confirm_release_payment') ?? 'Are you sure you want to release the payment? This action cannot be undone.' ?>');">
                            <?= csrfField() ?>
                            <button type="submit" name="release_payment" class="btn btn-success btn-release">
                                <i class="fas fa-check-circle"></i> <?= __('release_payment') ?? 'Release Payment' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Order Timeline -->
            <div class="card">
                <h3 class="card-title"><i class="fas fa-history"></i> <?= __('order_timeline') ?? 'Order Timeline' ?></h3>
                <div class="timeline">
                    <?php if (!empty($order['completed_at'])): ?>
                        <div class="timeline-item completed">
                            <div class="timeline-dot"></div>
                            <div class="timeline-time"><?= formatDateTime($order['completed_at']) ?></div>
                            <div class="timeline-title"><?= __('completed') ?? 'Completed' ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($order['delivered_at'])): ?>
                        <div class="timeline-item completed">
                            <div class="timeline-dot"></div>
                            <div class="timeline-time"><?= formatDateTime($order['delivered_at']) ?></div>
                            <div class="timeline-title"><?= __('delivered') ?? 'Delivered' ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($order['payment_released_at'])): ?>
                        <div class="timeline-item completed">
                            <div class="timeline-dot"></div>
                            <div class="timeline-time"><?= formatDateTime($order['payment_released_at']) ?></div>
                            <div class="timeline-title"><?= __('payment_released') ?? 'Payment Released' ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="timeline-item completed">
                        <div class="timeline-dot"></div>
                        <div class="timeline-time"><?= formatDateTime($order['created_at']) ?></div>
                        <div class="timeline-title"><?= __('order_placed') ?? 'Order Placed' ?></div>
                    </div>
                </div>
            </div>

            <!-- Review Section (for completed orders) -->
            <?php if ($isBuyer && $order['status'] === 'completed' && !$order['buyer_reviewed']): ?>
                <div class="card">
                    <h3 class="card-title"><i class="fas fa-star"></i> <?= __('write_review') ?? 'Write a Review' ?></h3>
                    <div class="review-form">
                        <form method="POST" action="">
                            <?= csrfField() ?>
                            <div class="form-group">
                                <label><?= __('your_rating') ?? 'Your Rating' ?></label>
                                <div class="star-rating-input" id="starRating">
                                    <input type="hidden" name="rating" id="ratingInput" value="0">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="far fa-star" data-rating="<?= $i ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><?= __('your_comment') ?? 'Your Comment' ?> (<?= __('optional') ?? 'optional' ?>)</label>
                                <textarea name="comment" class="form-input" rows="4" placeholder="<?= __('share_experience') ?? 'Share your experience with this product...' ?>"></textarea>
                            </div>
                            <button type="submit" name="submit_product_review" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> <?= __('submit_review') ?? 'Submit Review' ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Sidebar -->
        <aside>
            <!-- Order Summary -->
            <div class="card">
                <h3 class="card-title"><i class="fas fa-receipt"></i> <?= __('order_summary') ?? 'Order Summary' ?></h3>
                <div class="info-row">
                    <span class="info-label"><?= __('order_id') ?? 'Order ID' ?></span>
                    <span class="info-value">#<?= $order['order_number'] ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?= __('date') ?? 'Date' ?></span>
                    <span class="info-value"><?= date('M d, Y', strtotime($order['created_at'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?= __('payment_method') ?? 'Payment Method' ?></span>
                    <span class="info-value"><?= __('wallet') ?? 'Wallet' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?= __('quantity') ?? 'Quantity' ?></span>
                    <span class="info-value"><?= $order['quantity'] ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?= __('subtotal') ?? 'Subtotal' ?></span>
                    <span class="info-value"><?= formatCurrency($order['unit_price'] * $order['quantity']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?= __('fee') ?? 'Fee' ?></span>
                    <span class="info-value"><?= formatCurrency(0) ?></span>
                </div>
                <div class="info-row total">
                    <span class="info-label"><?= __('total') ?? 'Total' ?></span>
                    <span class="info-value price"><?= formatCurrency($order['total_amount']) ?></span>
                </div>
            </div>

            <!-- Need Help -->
            <div class="card">
                <h3 class="card-title"><i class="fas fa-headset"></i> <?= __('need_help') ?? 'Need Help?' ?></h3>
                <?php if ($order['delivery_type'] === 'manual' && in_array($order['status'], ['processing', 'delivered', 'completed', 'disputed'])): ?>
                    <a href="<?= BASE_URL ?>/order-chat.php?id=<?= $order['id'] ?>" class="btn btn-primary">
                        <i class="fas fa-comments"></i> <?= __('chat_with_seller') ?? 'Chat with Seller' ?>
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/support.php?order=<?= $order['id'] ?>" class="btn btn-primary">
                        <i class="fas fa-envelope"></i> <?= __('contact_seller') ?? 'Contact Seller' ?>
                    </a>
                <?php endif; ?>

                <?php if (in_array($order['status'], ['processing', 'delivered'])): ?>
                    <a href="<?= BASE_URL ?>/dispute.php?order=<?= $order['id'] ?>" class="btn btn-outline">
                        <i class="fas fa-flag"></i> <?= __('report_issue') ?? 'Report Issue' ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Review Status (if already reviewed) -->
            <?php if ($isBuyer && $order['status'] === 'completed' && $order['buyer_reviewed']): ?>
                <div class="card">
                    <h3 class="card-title"><i class="fas fa-star"></i> <?= __('review_submitted') ?? 'Review Submitted' ?></h3>
                    <div class="review-submitted">
                        <i class="fas fa-check-circle"></i>
                        <p><?= __('thank_you_review') ?? 'Thank you for your review!' ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</div>

<script>
    function copyDeliveryData() {
        const data = document.getElementById('deliveryData').innerText;
        navigator.clipboard.writeText(data).then(() => {
            alert('<?= __('copied_clipboard') ?? 'Copied to clipboard!' ?>');
        });
    }

    // Star rating functionality
    const starContainer = document.getElementById('starRating');
    if (starContainer) {
        const stars = starContainer.querySelectorAll('i');
        const ratingInput = document.getElementById('ratingInput');

        stars.forEach((star, index) => {
            star.addEventListener('click', () => {
                const rating = index + 1;
                ratingInput.value = rating;

                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });

            star.addEventListener('mouseenter', () => {
                stars.forEach((s, i) => {
                    if (i <= index) {
                        s.classList.add('hover');
                    }
                });
            });

            star.addEventListener('mouseleave', () => {
                stars.forEach(s => s.classList.remove('hover'));
            });
        });
    }
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
