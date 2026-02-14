<?php
/**
 * HStore - Open Dispute Page
 */
require_once __DIR__ . '/includes/init.php';
requireLogin();

$orderId = (int) ($_GET['order'] ?? 0);
$userId = getCurrentUserId();

if (!$orderId) {
    redirectWithMessage(BASE_URL . '/orders.php', 'Order not found', 'error');
}

// Get order
$order = db()->fetch("
    SELECT o.*, p.name as product_name, s.shop_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN sellers s ON o.seller_id = s.id
    WHERE o.id = ? AND o.buyer_id = ? AND o.status IN ('processing', 'completed')
", [$orderId, $userId]);

if (!$order) {
    redirectWithMessage(BASE_URL . '/orders.php', 'Order not found or cannot be disputed', 'error');
}

// Check if dispute already exists
$existingDispute = db()->fetch("SELECT id FROM disputes WHERE order_id = ?", [$orderId]);
if ($existingDispute) {
    redirectWithMessage(BASE_URL . '/orders.php', 'A dispute already exists for this order', 'warning');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request';
    } else {
        $reason = clean($_POST['reason'] ?? '');
        $description = clean($_POST['description'] ?? '');
        
        if (empty($reason)) {
            $error = 'Please select a reason';
        } elseif (empty($description)) {
            $error = 'Please describe your issue';
        } else {
            db()->insert('disputes', [
                'order_id' => $orderId,
                'initiated_by' => $userId,
                'reason' => $reason,
                'description' => $description,
                'status' => 'open'
            ]);
            
            // Update order status
            db()->update('orders', ['status' => 'disputed'], 'id = ?', [$orderId]);
            
            // Notify seller
            $seller = db()->fetch("SELECT user_id FROM sellers WHERE id = ?", [$order['seller_id']]);
            createNotification(
                $seller['user_id'],
                'dispute',
                'Dispute Opened',
                'A dispute has been opened for order #' . $order['order_number'],
                sellerOrderUrl($orderId)
            );
            
            // Notify all admins
            $admins = db()->fetchAll("SELECT id FROM users WHERE role = 'admin'");
            foreach ($admins as $admin) {
                createNotification(
                    $admin['id'],
                    'dispute',
                    'New Dispute',
                    'A dispute has been opened for order #' . $order['order_number'] . ' - Requires attention',
                    BASE_URL . '/xadmincp11/disputes.php?view=' . $orderId
                );
            }
            
            redirectWithMessage(BASE_URL . '/orders.php', 'Dispute opened successfully. Our team will review it.', 'success');
        }
    }
}

$pageTitle = 'Open Dispute - ' . PLATFORM_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Open Dispute</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <strong>Order:</strong> #<?= $order['order_number'] ?><br>
                        <strong>Product:</strong> <?= sanitize($order['product_name']) ?><br>
                        <strong>Seller:</strong> <?= sanitize($order['shop_name']) ?><br>
                        <strong>Amount:</strong> <?= formatCurrency($order['total_amount']) ?>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= sanitize($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <?= csrfField() ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Reason for Dispute <span class="text-danger">*</span></label>
                            <select class="form-select" name="reason" required>
                                <option value="">Select a reason</option>
                                <option value="Product not delivered">Product not delivered</option>
                                <option value="Product not as described">Product not as described</option>
                                <option value="Product doesn't work">Product doesn't work</option>
                                <option value="Wrong product received">Wrong product received</option>
                                <option value="Seller not responding">Seller not responding</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Describe Your Issue <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="description" rows="5" required
                                      placeholder="Please provide details about your issue..."></textarea>
                        </div>
                        
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Important:</strong> Our team will review your dispute and may contact both parties. 
                            Please provide accurate information.
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>Submit Dispute
                            </button>
                            <a href="<?= orderUrl($orderId) ?>" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
