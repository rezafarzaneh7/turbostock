<?php
/**
 * HStore - Seller Orders Management
 */
require_once __DIR__ . '/../includes/init.php';
requireSeller();

$userId = getCurrentUserId();
$seller = db()->fetch("SELECT * FROM sellers WHERE user_id = ?", [$userId]);

$viewOrderId = (int) ($_GET['view'] ?? 0);
$error = '';
$success = '';

// Handle order completion (manual delivery)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request';
    } else {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $deliveryData = clean($_POST['delivery_data'] ?? '');
        
        if (empty($deliveryData)) {
            $error = 'Please provide delivery data';
        } else {
            try {
                paymentHandler()->completeOrder($orderId, $deliveryData, $seller['id']);
                $success = 'Order delivered! Waiting for buyer to release payment.';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

// View single order
if ($viewOrderId) {
    $order = db()->fetch("
        SELECT o.*, p.name as product_name, p.thumbnail as product_thumbnail,
               u.username as buyer_username, u.email as buyer_email
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN users u ON o.buyer_id = u.id
        WHERE o.id = ? AND o.seller_id = ?
    ", [$viewOrderId, $seller['id']]);
    
    if (!$order) {
        redirectWithMessage(BASE_URL . '/seller/orders.php', 'Order not found', 'error');
    }
}

// Get orders list
$page = max(1, (int) ($_GET['page'] ?? 1));
$statusFilter = clean($_GET['status'] ?? '');

$where = "o.seller_id = ?";
$params = [$seller['id']];

if ($statusFilter) {
    $where .= " AND o.status = ?";
    $params[] = $statusFilter;
}

$totalOrders = db()->fetch("SELECT COUNT(*) as count FROM orders o WHERE {$where}", $params)['count'];
$pagination = paginate($totalOrders, $page, 15);

$orders = db()->fetchAll("
    SELECT o.*, p.name as product_name, p.thumbnail as product_thumbnail, p.delivery_type,
           u.username as buyer_username,
           (SELECT COUNT(*) FROM order_messages om WHERE om.order_id = o.id AND om.sender_id = o.buyer_id AND om.is_read = 0) as unread_messages
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON o.buyer_id = u.id
    WHERE {$where}
    ORDER BY o.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

$pageTitle = 'Orders - Seller Dashboard';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 col-xl-2 mb-4">
            <div class="sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link" href="<?= BASE_URL ?>/seller/">
                        <i class="fas fa-tachometer-alt"></i> <?= __('dashboard') ?>
                    </a>
                    <a class="nav-link" href="<?= BASE_URL ?>/seller/products.php">
                        <i class="fas fa-box"></i> <?= __('products') ?>
                    </a>
                    <a class="nav-link active" href="<?= BASE_URL ?>/seller/orders.php">
                        <i class="fas fa-shopping-cart"></i> <?= __('orders') ?>
                    </a>
                    <a class="nav-link" href="<?= BASE_URL ?>/seller/verification.php">
                        <i class="fas fa-check-circle"></i> <?= __('verification') ?>
                    </a>
                    <a class="nav-link" href="<?= BASE_URL ?>/seller/settings.php">
                        <i class="fas fa-cog"></i> <?= __('shop_settings') ?>
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9 col-xl-10">
            <?php if ($viewOrderId && $order): ?>
                <!-- View Single Order -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0">Order #<?= $order['order_number'] ?></h4>
                    <a href="<?= BASE_URL ?>/seller/orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i><?= __('back_to_orders') ?>
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
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?= __('order_details') ?></h5>
                                <span class="order-status <?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <?php if ($order['product_thumbnail']): ?>
                                            <img src="<?= UPLOADS_URL ?>/<?= $order['product_thumbnail'] ?>" class="img-fluid rounded">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-9">
                                        <h5 class="fw-bold"><?= sanitize($order['product_name']) ?></h5>
                                        <div class="row mt-3">
                                            <div class="col-6">
                                                <small class="text-muted"><?= __('quantity') ?></small>
                                                <p class="fw-bold mb-0"><?= $order['quantity'] ?></p>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted"><?= __('unit_price') ?></small>
                                                <p class="fw-bold mb-0"><?= formatCurrency($order['unit_price']) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Complete Order Form (for manual delivery) -->
                        <?php if ($order['status'] === 'processing' && $order['delivery_type'] === 'manual'): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0"><i class="fas fa-truck me-2"></i><?= __('deliver_order') ?></h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label"><?= __('delivery_data') ?> <span class="text-danger">*</span></label>
                                            <textarea class="form-control" name="delivery_data" rows="5" required
                                                      placeholder="Enter the product data to deliver (credentials, links, keys, etc.)"></textarea>
                                            <small class="text-muted">This will be shown to the buyer</small>
                                        </div>
                                        
                                        <button type="submit" name="complete_order" class="btn btn-success">
                                            <i class="fas fa-check me-2"></i><?= __('complete_order_deliver') ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Delivered - Waiting for buyer to release payment -->
                        <?php if ($order['status'] === 'delivered' && $order['delivery_data']): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i><?= __('awaiting_payment_release') ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong><?= __('payment_held_info') ?></strong><br>
                                        <small><?= __('payment_held_desc') ?></small>
                                    </div>
                                    <h6 class="fw-bold"><?= __('delivery_data_sent') ?></h6>
                                    <div class="delivery-data-box bg-light p-3 rounded">
                                        <?= nl2br(sanitize($order['delivery_data'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Completed - Payment Released -->
                        <?php if ($order['status'] === 'completed' && $order['delivery_data']): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i><?= __('payment_released') ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-success mb-3">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <?= __('payment_credited_to_wallet') ?>
                                    </div>
                                    <h6 class="fw-bold"><?= __('delivery_data_sent') ?></h6>
                                    <div class="delivery-data-box bg-light p-3 rounded">
                                        <?= nl2br(sanitize($order['delivery_data'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><?= __('order_summary') ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?= __('subtotal') ?></span>
                                    <span><?= formatCurrency($order['total_amount']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?= __('platform_fee') ?> (<?= $order['commission_rate'] ?>%)</span>
                                    <span class="text-danger">-<?= formatCurrency($order['commission_amount']) ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span><?= __('your_earnings') ?></span>
                                    <span class="text-success"><?= formatCurrency($order['seller_amount']) ?></span>
                                </div>
                                
                                <?php if ($order['status'] === 'delivered'): ?>
                                    <div class="alert alert-warning mt-3 mb-0 py-2">
                                        <small><i class="fas fa-clock me-1"></i><?= __('payment_pending_buyer_release') ?></small>
                                    </div>
                                <?php elseif ($order['status'] === 'completed'): ?>
                                    <div class="alert alert-success mt-3 mb-0 py-2">
                                        <small><i class="fas fa-check-circle me-1"></i><?= __('payment_received') ?></small>
                                    </div>
                                <?php elseif ($order['status'] === 'processing'): ?>
                                    <div class="alert alert-info mt-3 mb-0 py-2">
                                        <small><i class="fas fa-info-circle me-1"></i><?= __('deliver_to_receive_payment') ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><?= __('buyer_info') ?></h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong><?= sanitize($order['buyer_username']) ?></strong></p>
                                <p class="text-muted mb-0"><?= sanitize($order['buyer_email']) ?></p>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><?= __('timeline') ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <small class="text-muted"><?= __('order_placed') ?></small>
                                    <p class="mb-0"><?= formatDateTime($order['created_at']) ?></p>
                                </div>
                                <?php if (isset($order['delivered_at']) && $order['delivered_at']): ?>
                                    <div class="mb-2">
                                        <small class="text-muted"><?= __('delivered') ?></small>
                                        <p class="mb-0"><?= formatDateTime($order['delivered_at']) ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if ($order['completed_at']): ?>
                                    <div>
                                        <small class="text-muted"><?= __('payment_released') ?></small>
                                        <p class="mb-0"><?= formatDateTime($order['completed_at']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Orders List -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0"><?= __('orders') ?></h4>
                    <select class="form-select" style="width: auto;" 
                            onchange="window.location.href='<?= BASE_URL ?>/seller/orders.php?status='+this.value">
                        <option value=""><?= __('all_orders') ?></option>
                        <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>><?= __('processing') ?></option>
                        <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>><?= __('delivered') ?></option>
                        <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>><?= __('completed') ?></option>
                        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>><?= __('cancelled') ?></option>
                        <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>><?= __('refunded') ?></option>
                        <option value="disputed" <?= $statusFilter === 'disputed' ? 'selected' : '' ?>><?= __('disputed') ?></option>
                    </select>
                </div>
                
                <div class="card">
                    <div class="card-body p-0">
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <p class="text-muted"><?= __('no_orders') ?></p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th><?= __('order') ?></th>
                                            <th><?= __('product') ?></th>
                                            <th><?= __('buyer') ?></th>
                                            <th><?= __('amount') ?></th>
                                            <th><?= __('your_earnings') ?></th>
                                            <th><?= __('status') ?></th>
                                            <th><?= __('date') ?></th>
                                            <th><?= __('action') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <a href="<?= sellerOrderUrl($order['id']) ?>" class="fw-bold">
                                                        #<?= $order['order_number'] ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($order['product_thumbnail']): ?>
                                                            <img src="<?= UPLOADS_URL ?>/<?= $order['product_thumbnail'] ?>" 
                                                                 class="rounded me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                                        <?php endif; ?>
                                                        <?= sanitize(substr($order['product_name'], 0, 25)) ?>...
                                                    </div>
                                                </td>
                                                <td><?= sanitize($order['buyer_username']) ?></td>
                                                <td><?= formatCurrency($order['total_amount']) ?></td>
                                                <td class="text-success fw-bold"><?= formatCurrency($order['seller_amount']) ?></td>
                                                <td>
                                                    <span class="order-status <?= $order['status'] ?>">
                                                        <?= ucfirst($order['status']) ?>
                                                    </span>
                                                    <?php if ($order['status'] === 'delivered'): ?>
                                                        <br><small class="text-warning"><i class="fas fa-clock"></i> <?= __('awaiting_release') ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= formatDate($order['created_at']) ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="<?= sellerOrderUrl($order['id']) ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <?= $order['status'] === 'processing' ? __('deliver') : __('view') ?>
                                                        </a>
                                                        <?php if ($order['delivery_type'] === 'manual' && in_array($order['status'], ['processing', 'delivered', 'completed', 'disputed'])): ?>
                                                            <a href="<?= BASE_URL ?>/order-chat.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-success position-relative">
                                                                <i class="fas fa-comments"></i>
                                                                <?php if ($order['unread_messages'] > 0): ?>
                                                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                                                        <?= $order['unread_messages'] ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-4">
                    <?= renderPagination($pagination, BASE_URL . '/seller/orders.php' . ($statusFilter ? '?status=' . $statusFilter : '')) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
