<?php
/**
 * TurboStock - Seller Dashboard (New Design)
 */
require_once __DIR__ . '/../includes/init.php';
requireSeller();

$userId = getCurrentUserId();

// Get seller info
$seller = db()->fetch("SELECT * FROM sellers WHERE user_id = ?", [$userId]);
if (!$seller) {
    redirectWithMessage(BASE_URL . '/become-seller.php', 'Please create a seller account first', 'warning');
}

$wallet = paymentHandler()->getWallet($userId);

// Get stats
$totalProducts = db()->count('products', 'seller_id = ?', [$seller['id']]);
$activeProducts = db()->count('products', "seller_id = ? AND status = 'active'", [$seller['id']]);
$pendingOrders = db()->count('orders', "seller_id = ? AND status = 'processing'", [$seller['id']]);
$completedOrders = db()->count('orders', "seller_id = ? AND status = 'completed'", [$seller['id']]);

// Recent orders
$recentOrders = db()->fetchAll("
    SELECT o.*, p.name as product_name, u.username as buyer_username
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON o.buyer_id = u.id
    WHERE o.seller_id = ?
    ORDER BY o.created_at DESC
    LIMIT 5
", [$seller['id']]);

// Monthly earnings
$monthlyEarnings = db()->fetch("
    SELECT COALESCE(SUM(seller_amount), 0) as total
    FROM orders
    WHERE seller_id = ? AND status = 'completed'
    AND MONTH(created_at) = MONTH(CURRENT_DATE())
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
", [$seller['id']])['total'];

$pageTitle = 'Seller Dashboard - ' . PLATFORM_NAME;
$bodyClass = 'page-seller-dashboard';
require_once INCLUDES_PATH . '/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>"><?= __('home') ?></a><span>/</span>
            <span class="current"><?= __('seller_dashboard') ?></span>
        </div>
        <h1 class="page-title"><?= __('seller') ?> <span><?= __('dashboard') ?></span></h1>
    </div>
</div>

<div class="container">
    <div class="main-content">
        <!-- Sidebar Menu -->
        <aside class="sidebar-menu">
            <a href="<?= BASE_URL ?>/seller/" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i> <?= __('dashboard') ?>
            </a>
            <a href="<?= BASE_URL ?>/seller/products.php" class="menu-item">
                <i class="fas fa-box"></i> <?= __('products') ?>
            </a>
            <a href="<?= BASE_URL ?>/seller/orders.php" class="menu-item">
                <i class="fas fa-shopping-cart"></i> <?= __('orders') ?>
                <?php if ($pendingOrders > 0): ?>
                    <span class="menu-badge"><?= $pendingOrders ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/seller/verification.php" class="menu-item">
                <i class="fas fa-check-circle"></i> <?= __('verification') ?>
            </a>
            <a href="<?= BASE_URL ?>/seller/settings.php" class="menu-item">
                <i class="fas fa-cog"></i> <?= __('shop_settings') ?>
            </a>
            <a href="<?= storeUrl($seller['id']) ?>" class="menu-item" target="_blank">
                <i class="fas fa-external-link-alt"></i> <?= __('view_store') ?>
            </a>
            <a href="<?= BASE_URL ?>/wallet.php" class="menu-item">
                <i class="fas fa-wallet"></i> <?= __('wallet') ?>
            </a>
        </aside>

        <!-- Main Content -->
        <main>
            <!-- Shop Header -->
            <div class="shop-header">
                <div class="shop-avatar">
                    <?php if ($seller['shop_logo']): ?>
                        <img src="<?= UPLOADS_URL ?>/<?= $seller['shop_logo'] ?>" alt="<?= sanitize($seller['shop_name']) ?>">
                    <?php else: ?>
                        <?= strtoupper(substr($seller['shop_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="shop-info">
                    <h2 class="shop-name"><?= sanitize($seller['shop_name']) ?></h2>
                    <div class="shop-meta">
                        <?= getVerificationBadge($seller['verification_status']) ?>
                        <?php if ($seller['verification_status'] !== 'verified'): ?>
                            <a href="<?= BASE_URL ?>/seller/verification.php" class="get-verified-link">
                                <i class="fas fa-arrow-right"></i> <?= __('get_verified') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="shop-rating">
                        <?= renderStars($seller['rating_average']) ?>
                        <span class="rating-text"><?= number_format($seller['rating_average'], 1) ?> (<?= $seller['rating_count'] ?> <?= __('reviews') ?>)</span>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/seller/products.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?= __('add_product') ?>
                </a>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon balance">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= formatCurrency($wallet['balance']) ?></div>
                        <div class="stat-label"><?= __('balance') ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon earnings">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= formatCurrency($monthlyEarnings) ?></div>
                        <div class="stat-label"><?= __('this_month') ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon sales">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= number_format($seller['total_sales']) ?></div>
                        <div class="stat-label"><?= __('total_sales') ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon products">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= number_format($activeProducts) ?></div>
                        <div class="stat-label"><?= __('active_products') ?></div>
                    </div>
                </div>
            </div>

            <!-- Pending Balance Alert -->
            <?php if ($wallet['pending_balance'] > 0): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-clock"></i>
                    <div>
                        <strong><?= __('pending_balance') ?>: <?= formatCurrency($wallet['pending_balance']) ?></strong>
                        <span><?= __('pending_balance_info') ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <!-- Recent Orders -->
                <div class="card orders-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-shopping-bag"></i> <?= __('recent_orders') ?>
                        </h3>
                        <a href="<?= BASE_URL ?>/seller/orders.php" class="view-all-link">
                            <?= __('view_all') ?> <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentOrders)): ?>
                            <div class="empty-state">
                                <i class="fas fa-shopping-cart"></i>
                                <p><?= __('no_orders') ?></p>
                            </div>
                        <?php else: ?>
                            <div class="orders-list">
                                <?php foreach ($recentOrders as $order): ?>
                                    <a href="<?= sellerOrderUrl($order['id']) ?>" class="order-item">
                                        <div class="order-info">
                                            <span class="order-number">#<?= $order['order_number'] ?></span>
                                            <span class="order-product"><?= sanitize(truncate($order['product_name'], 30)) ?></span>
                                            <span class="order-buyer">
                                                <i class="fas fa-user"></i> <?= sanitize($order['buyer_username']) ?>
                                            </span>
                                        </div>
                                        <div class="order-meta">
                                            <span class="order-amount"><?= formatCurrency($order['seller_amount']) ?></span>
                                            <span class="order-status status-<?= $order['status'] ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card actions-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-bolt"></i> <?= __('quick_actions') ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="<?= BASE_URL ?>/seller/products.php?action=add" class="quick-action">
                                <div class="action-icon add">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <span><?= __('add_product') ?></span>
                            </a>
                            <?php if ($pendingOrders > 0): ?>
                                <a href="<?= BASE_URL ?>/seller/orders.php?status=processing" class="quick-action">
                                    <div class="action-icon pending">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <span><?= __('pending_orders') ?> (<?= $pendingOrders ?>)</span>
                                </a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/wallet.php" class="quick-action">
                                <div class="action-icon wallet">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <span><?= __('manage_wallet') ?></span>
                            </a>
                            <a href="<?= BASE_URL ?>/seller/settings.php" class="quick-action">
                                <div class="action-icon settings">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <span><?= __('shop_settings') ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
