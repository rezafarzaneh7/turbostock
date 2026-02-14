<?php
/**
 * HStore - Admin Orders Management
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$error = '';
$success = '';
$viewOrderId = (int) ($_GET['view'] ?? 0);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request';
    } else {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        
        if (isset($_POST['refund'])) {
            try {
                paymentHandler()->processRefund($orderId, getCurrentUserId());
                logAdminAction('refund_order', 'order', $orderId);
                $success = 'Order refunded successfully';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        if (isset($_POST['update_status'])) {
            $newStatus = clean($_POST['status'] ?? '');
            if (in_array($newStatus, ['pending', 'processing', 'completed', 'cancelled'])) {
                $oldOrder = db()->fetch("SELECT status FROM orders WHERE id = ?", [$orderId]);
                db()->update('orders', ['status' => $newStatus], 'id = ?', [$orderId]);
                logAdminAction('update_order_status', 'order', $orderId, ['status' => $oldOrder['status']], ['status' => $newStatus]);
                $success = 'Order status updated';
            }
        }
    }
}

// View single order
if ($viewOrderId) {
    $order = db()->fetch("
        SELECT o.*, p.name as product_name, p.thumbnail as product_thumbnail,
               buyer.username as buyer_username, buyer.email as buyer_email,
               s.shop_name, seller.username as seller_username
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN users buyer ON o.buyer_id = buyer.id
        JOIN sellers s ON o.seller_id = s.id
        JOIN users seller ON s.user_id = seller.id
        WHERE o.id = ?
    ", [$viewOrderId]);
}

// Get orders list
$page = max(1, (int) ($_GET['page'] ?? 1));
$statusFilter = clean($_GET['status'] ?? '');
$search = clean($_GET['search'] ?? '');

$where = "1=1";
$params = [];

if ($statusFilter) {
    $where .= " AND o.status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $where .= " AND (o.order_number LIKE ? OR buyer.username LIKE ? OR s.shop_name LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$totalOrders = db()->fetch("
    SELECT COUNT(*) as count FROM orders o 
    JOIN users buyer ON o.buyer_id = buyer.id
    JOIN sellers s ON o.seller_id = s.id
    WHERE {$where}
", $params)['count'];

$pagination = paginate($totalOrders, $page, ADMIN_ITEMS_PER_PAGE);

$orders = db()->fetchAll("
    SELECT o.*, p.name as product_name,
           buyer.username as buyer_username,
           s.shop_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users buyer ON o.buyer_id = buyer.id
    JOIN sellers s ON o.seller_id = s.id
    WHERE {$where}
    ORDER BY o.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

$pageTitle = 'Orders - Admin';
require_once __DIR__ . '/header.php';
?>

<div class="container-fluid py-4">
    <?php if ($viewOrderId && $order): ?>
        <!-- View Single Order -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Order #<?= $order['order_number'] ?></h4>
            <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= sanitize($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= sanitize($success) ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="mb-0">Order Details</h5>
                        <span class="order-status <?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="fw-bold">Product</h6>
                                <p><?= sanitize($order['product_name']) ?></p>
                            </div>
                            <div class="col-md-3">
                                <h6 class="fw-bold">Quantity</h6>
                                <p><?= $order['quantity'] ?></p>
                            </div>
                            <div class="col-md-3">
                                <h6 class="fw-bold">Unit Price</h6>
                                <p><?= formatCurrency($order['unit_price']) ?></p>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="fw-bold">Buyer</h6>
                                <p><?= sanitize($order['buyer_username']) ?> (<?= sanitize($order['buyer_email']) ?>)</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">Seller</h6>
                                <p><?= sanitize($order['shop_name']) ?> (@<?= sanitize($order['seller_username']) ?>)</p>
                            </div>
                        </div>
                        
                        <?php if ($order['delivery_data']): ?>
                            <h6 class="fw-bold">Delivery Data</h6>
                            <div class="delivery-data-box mb-3">
                                <?= nl2br(sanitize($order['delivery_data'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Admin Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Admin Actions</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" class="row g-3">
                            <?= csrfField() ?>
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            
                            <div class="col-md-6">
                                <label class="form-label">Update Status</label>
                                <select class="form-select" name="status">
                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="submit" name="update_status" class="btn btn-primary me-2">Update Status</button>
                                <?php if ($order['status'] !== 'refunded'): ?>
                                    <button type="submit" name="refund" class="btn btn-danger" 
                                            onclick="return confirm('Process refund for this order?')">
                                        Refund Order
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Financial Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Amount</span>
                            <span class="fw-bold"><?= formatCurrency($order['total_amount']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Commission (<?= $order['commission_rate'] ?>%)</span>
                            <span class="text-success"><?= formatCurrency($order['commission_amount']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Seller Amount</span>
                            <span><?= formatCurrency($order['seller_amount']) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Timeline</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Created:</strong> <?= formatDateTime($order['created_at']) ?></p>
                        <?php if ($order['completed_at']): ?>
                            <p class="mb-0"><strong>Completed:</strong> <?= formatDateTime($order['completed_at']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Orders List -->
        <h4 class="fw-bold mb-4">Orders Management</h4>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= sanitize($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= sanitize($success) ?></div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Search order #, buyer, seller" 
                               value="<?= sanitize($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-2">
                        <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span><?= number_format($totalOrders) ?> orders</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Product</th>
                                <th>Buyer</th>
                                <th>Seller</th>
                                <th>Amount</th>
                                <th>Commission</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>/admin/orders.php?view=<?= $order['id'] ?>">
                                            #<?= $order['order_number'] ?>
                                        </a>
                                    </td>
                                    <td><?= sanitize(substr($order['product_name'], 0, 25)) ?>...</td>
                                    <td><?= sanitize($order['buyer_username']) ?></td>
                                    <td><?= sanitize($order['shop_name']) ?></td>
                                    <td><?= formatCurrency($order['total_amount']) ?></td>
                                    <td class="text-success"><?= formatCurrency($order['commission_amount']) ?></td>
                                    <td>
                                        <span class="order-status <?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= formatDate($order['created_at']) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/admin/orders.php?view=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <?= renderPagination($pagination, BASE_URL . '/admin/orders.php?' . http_build_query(array_filter(['search' => $search, 'status' => $statusFilter]))) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
