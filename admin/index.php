<?php
/**
 * HStore - Admin Dashboard
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

// Get stats
$totalUsers = db()->count('users');
$totalSellers = db()->count('sellers');
$totalProducts = db()->count('products');
$totalOrders = db()->count('orders');
$pendingOrders = db()->count('orders', "status = 'processing'");
$completedOrders = db()->count('orders', "status = 'completed'");

// Revenue stats
$totalRevenue = db()->fetch("SELECT COALESCE(SUM(commission_amount), 0) as total FROM orders WHERE status = 'completed'")['total'];
$monthlyRevenue = db()->fetch("
    SELECT COALESCE(SUM(commission_amount), 0) as total FROM orders 
    WHERE status = 'completed' 
    AND MONTH(created_at) = MONTH(CURRENT_DATE())
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
")['total'];

// Pending verifications
$pendingVerifications = db()->count('seller_verifications', "status = 'pending'");

// Pending products (awaiting approval)
$pendingProducts = db()->count('products', "status = 'pending'");

// Recent orders
$recentOrders = db()->fetchAll("
    SELECT o.*, p.name as product_name, 
           buyer.username as buyer_username,
           s.shop_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users buyer ON o.buyer_id = buyer.id
    JOIN sellers s ON o.seller_id = s.id
    ORDER BY o.created_at DESC
    LIMIT 10
");

// Recent users
$recentUsers = db()->fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

$pageTitle = 'Admin Dashboard - ' . PLATFORM_NAME;
require_once __DIR__ . '/header.php';
?>

<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4">Dashboard</h4>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= number_format($totalUsers) ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="fas fa-store"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= number_format($totalSellers) ?></div>
                        <div class="stat-label">Sellers</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= number_format($totalProducts) ?></div>
                        <div class="stat-label">Products</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= number_format($totalOrders) ?></div>
                        <div class="stat-label">Orders</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Revenue Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Total Commission Earned</h6>
                    <h3 class="fw-bold mb-0"><?= formatCurrency($totalRevenue) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50">This Month</h6>
                    <h3 class="fw-bold mb-0"><?= formatCurrency($monthlyRevenue) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="opacity-75">Pending Actions</h6>
                    <h3 class="fw-bold mb-0">
                        <?= $pendingOrders ?> Orders | <?= $pendingProducts ?> Products | <?= $pendingVerifications ?> Verifications
                    </h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Orders</h5>
                    <a href="<?= BASE_URL ?>/xadmincp11/orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Product</th>
                                    <th>Buyer</th>
                                    <th>Seller</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= BASE_URL ?>/xadmincp11/orders.php?view=<?= $order['id'] ?>">
                                                #<?= $order['order_number'] ?>
                                            </a>
                                        </td>
                                        <td><?= sanitize(substr($order['product_name'], 0, 20)) ?>...</td>
                                        <td><?= sanitize($order['buyer_username']) ?></td>
                                        <td><?= sanitize($order['shop_name']) ?></td>
                                        <td><?= formatCurrency($order['total_amount']) ?></td>
                                        <td>
                                            <span class="order-status <?= $order['status'] ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Users -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Users</h5>
                    <a href="<?= BASE_URL ?>/xadmincp11/users.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentUsers as $user): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= sanitize($user['username']) ?></strong>
                                    <small class="text-muted d-block"><?= sanitize($user['email']) ?></small>
                                </div>
                                <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'seller' ? 'success' : 'primary') ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/xadmincp11/products.php?status=pending" class="btn btn-outline-primary">
                            <i class="fas fa-box me-2"></i>Pending Products (<?= $pendingProducts ?>)
                        </a>
                        <a href="<?= BASE_URL ?>/xadmincp11/verifications.php" class="btn btn-outline-success">
                            <i class="fas fa-check-circle me-2"></i>Pending Verifications (<?= $pendingVerifications ?>)
                        </a>
                        <a href="<?= BASE_URL ?>/xadmincp11/orders.php?status=processing" class="btn btn-outline-warning">
                            <i class="fas fa-clock me-2"></i>Pending Orders (<?= $pendingOrders ?>)
                        </a>
                        <a href="<?= BASE_URL ?>/xadmincp11/settings.php" class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
