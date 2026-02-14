<?php
/**
 * TurboStock - Orders List Page (New Design)
 */
require_once __DIR__ . '/includes/init.php';
requireLogin();

$userId = getCurrentUserId();

// Handle release payment action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['release_payment'])) {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        redirectWithMessage(BASE_URL . '/orders.php', 'Invalid request', 'error');
    }

    $orderId = (int) ($_POST['order_id'] ?? 0);

    try {
        paymentHandler()->releasePayment($orderId, $userId);
        redirectWithMessage(BASE_URL . '/order/' . $orderId, 'Payment released successfully! The seller has received the funds.', 'success');
    } catch (Exception $e) {
        redirectWithMessage(BASE_URL . '/order/' . $orderId, $e->getMessage(), 'error');
    }
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$status = clean($_GET['status'] ?? '');
$search = clean($_GET['q'] ?? '');

// Build query
$where = "o.buyer_id = ?";
$params = [$userId];

if ($status && in_array($status, ['pending', 'processing', 'delivered', 'completed', 'cancelled', 'refunded', 'disputed'])) {
    $where .= " AND o.status = ?";
    $params[] = $status;
}

if ($search) {
    $where .= " AND (o.order_number LIKE ? OR p.name LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$totalOrders = db()->fetch("SELECT COUNT(*) as count FROM orders o JOIN products p ON o.product_id = p.id WHERE {$where}", $params)['count'];
$pagination = paginate($totalOrders, $page, 10);

$orders = db()->fetchAll("
    SELECT o.*, p.name as product_name, p.thumbnail as product_thumbnail, p.delivery_type,
           s.shop_name, s.verification_status, s.id as seller_id,
           c.name as category_name, c.icon as category_icon,
           (SELECT COUNT(*) FROM order_messages om WHERE om.order_id = o.id AND om.sender_id != o.buyer_id AND om.is_read = 0) as unread_messages
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN sellers s ON o.seller_id = s.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE {$where}
    ORDER BY o.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

// Get order counts by status
$orderCounts = db()->fetchAll("
    SELECT status, COUNT(*) as count
    FROM orders
    WHERE buyer_id = ?
    GROUP BY status
", [$userId]);
$statusCounts = [];
foreach ($orderCounts as $row) {
    $statusCounts[$row['status']] = $row['count'];
}
$totalCount = array_sum($statusCounts);

$pageTitle = __('my_orders') . ' - ' . PLATFORM_NAME;
$bodyClass = 'page-my-orders';
$themeClass = 'theme-light';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>"><?= __('home') ?? 'Home' ?></a>
            <span>/</span>
            <span class="current"><?= __('my_orders') ?? 'My Orders' ?></span>
        </div>
        <h1 class="page-title"><?= __('my') ?? 'My' ?> <span><?= __('orders') ?? 'Orders' ?></span></h1>
    </div>
</div>

<div class="container">
    <div class="main-content">
        <aside class="sidebar-menu">
            <a href="<?= BASE_URL ?>/profile.php" class="menu-item"><i class="fas fa-user"></i> <?= __('my_profile') ?? 'My Profile' ?></a>
            <a href="<?= BASE_URL ?>/orders.php" class="menu-item active"><i class="fas fa-shopping-bag"></i> <?= __('my_orders') ?? 'My Orders' ?></a>
            <a href="<?= BASE_URL ?>/wallet.php" class="menu-item"><i class="fas fa-wallet"></i> <?= __('wallet') ?? 'Wallet' ?></a>
            <a href="<?= BASE_URL ?>/wishlist.php" class="menu-item"><i class="fas fa-heart"></i> <?= __('wishlist') ?? 'Wishlist' ?></a>
            <a href="<?= BASE_URL ?>/settings.php" class="menu-item"><i class="fas fa-cog"></i> <?= __('settings') ?? 'Settings' ?></a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> <?= __('logout') ?? 'Logout' ?></a>
        </aside>

        <main>
            <!-- Orders Header -->
            <div class="orders-header">
                <div class="filter-tabs">
                    <a href="<?= BASE_URL ?>/orders.php" class="filter-tab <?= !$status ? 'active' : '' ?>">
                        <?= __('all_orders') ?? 'All Orders' ?>
                        <span class="count"><?= $totalCount ?></span>
                    </a>
                    <a href="<?= BASE_URL ?>/orders.php?status=completed" class="filter-tab <?= $status === 'completed' ? 'active' : '' ?>">
                        <?= __('completed') ?? 'Completed' ?>
                        <span class="count"><?= $statusCounts['completed'] ?? 0 ?></span>
                    </a>
                    <a href="<?= BASE_URL ?>/orders.php?status=pending" class="filter-tab <?= $status === 'pending' ? 'active' : '' ?>">
                        <?= __('pending') ?? 'Pending' ?>
                        <span class="count"><?= $statusCounts['pending'] ?? 0 ?></span>
                    </a>
                    <a href="<?= BASE_URL ?>/orders.php?status=delivered" class="filter-tab <?= $status === 'delivered' ? 'active' : '' ?>">
                        <?= __('delivered') ?? 'Delivered' ?>
                        <span class="count"><?= $statusCounts['delivered'] ?? 0 ?></span>
                    </a>
                    <a href="<?= BASE_URL ?>/orders.php?status=disputed" class="filter-tab <?= $status === 'disputed' ? 'active' : '' ?>">
                        <?= __('disputed') ?? 'Disputed' ?>
                        <span class="count"><?= $statusCounts['disputed'] ?? 0 ?></span>
                    </a>
                </div>
                <form action="" method="GET" class="search-form">
                    <?php if ($status): ?>
                        <input type="hidden" name="status" value="<?= $status ?>">
                    <?php endif; ?>
                    <input type="text" name="q" class="search-orders" placeholder="<?= __('search_orders') ?? 'Search orders...' ?>" value="<?= sanitize($search) ?>">
                </form>
            </div>

            <?php if (empty($orders)): ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-shopping-bag"></i></div>
                    <h3><?= __('no_orders') ?? 'No orders found' ?></h3>
                    <p><?= __('no_orders_desc') ?? 'You haven\'t made any purchases yet.' ?></p>
                    <a href="<?= BASE_URL ?>/browse.php" class="btn-primary">
                        <i class="fas fa-store"></i> <?= __('browse_products') ?? 'Browse Products' ?>
                    </a>
                </div>
            <?php else: ?>
                <!-- Orders List -->
                <?php foreach ($orders as $order): ?>
                    <?php
                    $statusIcon = match($order['status']) {
                        'pending' => 'fa-clock',
                        'processing' => 'fa-spinner',
                        'delivered' => 'fa-truck',
                        'completed' => 'fa-check-circle',
                        'cancelled' => 'fa-times-circle',
                        'refunded' => 'fa-undo',
                        'disputed' => 'fa-exclamation-triangle',
                        default => 'fa-circle'
                    };
                    $categoryIcon = $order['category_icon'] ?? 'fa-box';
                    ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-meta">
                                <div class="order-id"><?= __('order') ?? 'Order' ?> <span>#<?= $order['order_number'] ?></span></div>
                                <div class="order-date"><?= formatDateTime($order['created_at']) ?></div>
                            </div>
                            <span class="order-status <?= $order['status'] ?>">
                                <i class="fas <?= $statusIcon ?>"></i> <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                        <div class="order-body">
                            <div class="order-image">
                                <?php if ($order['product_thumbnail']): ?>
                                    <img src="<?= UPLOADS_URL ?>/<?= $order['product_thumbnail'] ?>" alt="<?= sanitize($order['product_name']) ?>">
                                <?php else: ?>
                                    <i class="fas <?= $categoryIcon ?>"></i>
                                <?php endif; ?>
                            </div>
                            <div class="order-info">
                                <h3 class="order-title">
                                    <a href="<?= productUrl($order['product_id']) ?>"><?= sanitize($order['product_name']) ?></a>
                                    <?php if ($order['quantity'] > 1): ?>
                                        <span class="qty">x<?= $order['quantity'] ?></span>
                                    <?php endif; ?>
                                </h3>
                                <p class="order-seller">
                                    <?= __('sold_by') ?? 'Sold by' ?>
                                    <a href="<?= storeUrl($order['seller_id']) ?>">
                                        <?= sanitize($order['shop_name']) ?>
                                        <?php if ($order['verification_status'] === 'verified'): ?>
                                            <i class="fas fa-check-circle verified"></i>
                                        <?php endif; ?>
                                    </a>
                                </p>
                                <?php if ($order['delivery_type'] === 'auto'): ?>
                                    <span class="delivery-badge instant"><i class="fas fa-bolt"></i> <?= __('instant_delivery') ?? 'Instant' ?></span>
                                <?php else: ?>
                                    <span class="delivery-badge manual"><i class="fas fa-user"></i> <?= __('manual_delivery') ?? 'Manual' ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="order-price">
                                <div class="amount"><?= formatCurrency($order['total_amount']) ?></div>
                                <div class="label"><?= __('total_paid') ?? 'Total Paid' ?></div>
                            </div>
                        </div>
                        <div class="order-footer">
                            <a href="<?= BASE_URL ?>/order/<?= $order['id'] ?>" class="btn btn-outline">
                                <i class="fas fa-eye"></i> <?= __('view_details') ?? 'View Details' ?>
                            </a>

                            <?php if ($order['delivery_type'] === 'manual' && in_array($order['status'], ['processing', 'delivered', 'completed', 'disputed'])): ?>
                                <a href="<?= BASE_URL ?>/order-chat.php?id=<?= $order['id'] ?>" class="btn btn-outline <?= $order['unread_messages'] > 0 ? 'has-notification' : '' ?>">
                                    <i class="fas fa-comments"></i> <?= __('contact_seller') ?? 'Contact Seller' ?>
                                    <?php if ($order['unread_messages'] > 0): ?>
                                        <span class="notification-badge"><?= $order['unread_messages'] ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>

                            <?php if ($order['status'] === 'delivered'): ?>
                                <a href="<?= BASE_URL ?>/order/<?= $order['id'] ?>" class="btn btn-success">
                                    <i class="fas fa-check"></i> <?= __('confirm_delivery') ?? 'Confirm Delivery' ?>
                                </a>
                            <?php elseif ($order['status'] === 'completed'): ?>
                                <a href="<?= BASE_URL ?>/order/<?= $order['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-star"></i> <?= __('leave_review') ?? 'Leave Review' ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <a href="<?= BASE_URL ?>/orders.php?page=<?= $pagination['current_page'] - 1 ?><?= $status ? '&status=' . $status : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="page-btn">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $pagination['current_page'] - 2);
                        $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
                        ?>

                        <?php if ($startPage > 1): ?>
                            <a href="<?= BASE_URL ?>/orders.php?page=1<?= $status ? '&status=' . $status : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="page-btn">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="<?= BASE_URL ?>/orders.php?page=<?= $i ?><?= $status ? '&status=' . $status : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="page-btn <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($endPage < $pagination['total_pages']): ?>
                            <?php if ($endPage < $pagination['total_pages'] - 1): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/orders.php?page=<?= $pagination['total_pages'] ?><?= $status ? '&status=' . $status : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="page-btn"><?= $pagination['total_pages'] ?></a>
                        <?php endif; ?>

                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <a href="<?= BASE_URL ?>/orders.php?page=<?= $pagination['current_page'] + 1 ?><?= $status ? '&status=' . $status : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="page-btn">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
