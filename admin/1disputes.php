<?php
/**
 * HStore - Admin Disputes Management
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request';
    } else {
        $disputeId = (int) ($_POST['dispute_id'] ?? 0);
        
        if (isset($_POST['resolve'])) {
            $resolution = clean($_POST['resolution'] ?? '');
            $notes = clean($_POST['admin_notes'] ?? '');
            
            if (!in_array($resolution, ['resolved_buyer', 'resolved_seller', 'closed'])) {
                $error = 'Invalid resolution';
            } else {
                $dispute = db()->fetch("SELECT * FROM disputes WHERE id = ?", [$disputeId]);
                
                db()->update('disputes', [
                    'status' => $resolution,
                    'admin_notes' => $notes,
                    'resolved_by' => getCurrentUserId(),
                    'resolved_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$disputeId]);
                
                // Update order status based on resolution
                $orderStatus = 'completed'; // default
                
                if ($resolution === 'resolved_buyer') {
                    // Refund buyer
                    try {
                        paymentHandler()->processRefund($dispute['order_id'], getCurrentUserId());
                    } catch (Exception $e) {
                        error_log("Refund error during dispute resolution: " . $e->getMessage());
                    }
                    $orderStatus = 'refunded';
                } elseif ($resolution === 'resolved_seller') {
                    $orderStatus = 'completed';
                } else {
                    $orderStatus = 'completed'; // closed without action = completed
                }
                
                db()->update('orders', ['status' => $orderStatus], 'id = ?', [$dispute['order_id']]);
                
                // Notify both buyer and seller
                $order = db()->fetch("SELECT o.*, s.user_id as seller_user_id FROM orders o JOIN sellers s ON o.seller_id = s.id WHERE o.id = ?", [$dispute['order_id']]);
                
                $resolutionText = match($resolution) {
                    'resolved_buyer' => 'in your favor (refunded)',
                    'resolved_seller' => 'in seller\'s favor',
                    default => 'closed'
                };
                
                createNotification(
                    $order['buyer_id'],
                    'dispute',
                    'Dispute Resolved',
                    'Your dispute for order #' . $order['order_number'] . ' has been resolved ' . $resolutionText,
                    BASE_URL . '/orders.php?view=' . $dispute['order_id']
                );
                
                createNotification(
                    $order['seller_user_id'],
                    'dispute',
                    'Dispute Resolved',
                    'Dispute for order #' . $order['order_number'] . ' has been resolved',
                    sellerOrderUrl($dispute['order_id'])
                );
                
                logAdminAction('resolve_dispute', 'dispute', $disputeId, null, ['resolution' => $resolution]);
                $success = 'Dispute resolved';
            }
        }
    }
}

// Get disputes
$page = max(1, (int) ($_GET['page'] ?? 1));
$statusFilter = clean($_GET['status'] ?? '');

$where = "1=1";
$params = [];

if ($statusFilter) {
    $where .= " AND d.status = ?";
    $params[] = $statusFilter;
}

$totalDisputes = db()->fetch("SELECT COUNT(*) as count FROM disputes d WHERE {$where}", $params)['count'];
$pagination = paginate($totalDisputes, $page, ADMIN_ITEMS_PER_PAGE);

$disputes = db()->fetchAll("
    SELECT d.*, o.order_number, o.total_amount,
           u.username as initiated_by_username,
           p.name as product_name,
           s.shop_name
    FROM disputes d
    JOIN orders o ON d.order_id = o.id
    JOIN users u ON d.initiated_by = u.id
    JOIN products p ON o.product_id = p.id
    JOIN sellers s ON o.seller_id = s.id
    WHERE {$where}
    ORDER BY d.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

$openDisputes = db()->count('disputes', "status = 'open'");

$pageTitle = 'Disputes - Admin';
require_once __DIR__ . '/header.php';
?>

<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4">Disputes Management</h4>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= sanitize($success) ?></div>
    <?php endif; ?>
    
    <?php if ($openDisputes > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong><?= $openDisputes ?></strong> open disputes require attention
        </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open</option>
                        <option value="under_review" <?= $statusFilter === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                        <option value="resolved_buyer" <?= $statusFilter === 'resolved_buyer' ? 'selected' : '' ?>>Resolved (Buyer)</option>
                        <option value="resolved_seller" <?= $statusFilter === 'resolved_seller' ? 'selected' : '' ?>>Resolved (Seller)</option>
                        <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Disputes Table -->
    <div class="card">
        <div class="card-header">
            <span><?= number_format($totalDisputes) ?> disputes</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($disputes)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">No disputes found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Order</th>
                                <th>Product</th>
                                <th>Seller</th>
                                <th>Initiated By</th>
                                <th>Reason</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($disputes as $dispute): ?>
                                <tr>
                                    <td><?= $dispute['id'] ?></td>
                                    <td>#<?= $dispute['order_number'] ?></td>
                                    <td><?= sanitize(substr($dispute['product_name'], 0, 20)) ?>...</td>
                                    <td><?= sanitize($dispute['shop_name']) ?></td>
                                    <td><?= sanitize($dispute['initiated_by_username']) ?></td>
                                    <td><?= sanitize(substr($dispute['reason'], 0, 30)) ?>...</td>
                                    <td><?= formatCurrency($dispute['total_amount']) ?></td>
                                    <td>
                                        <?php
                                        $statusClass = match($dispute['status']) {
                                            'open' => 'danger',
                                            'under_review' => 'warning',
                                            'resolved_buyer', 'resolved_seller' => 'success',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>">
                                            <?= ucfirst(str_replace('_', ' ', $dispute['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= formatDate($dispute['created_at']) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?= BASE_URL ?>/xadmincp11/dispute-chat.php?id=<?= $dispute['order_id'] ?>" 
                                               class="btn btn-sm btn-success" title="View Chat">
                                                <i class="fas fa-comments"></i>
                                            </a>
                                            <?php if (in_array($dispute['status'], ['open', 'under_review'])): ?>
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="modal" data-bs-target="#resolveModal<?= $dispute['id'] ?>">
                                                    Resolve
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Resolve Modal -->
                                <div class="modal fade" id="resolveModal<?= $dispute['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="dispute_id" value="<?= $dispute['id'] ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Resolve Dispute #<?= $dispute['id'] ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <strong>Reason:</strong>
                                                        <p><?= sanitize($dispute['reason']) ?></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong>Description:</strong>
                                                        <p><?= nl2br(sanitize($dispute['description'])) ?></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Resolution</label>
                                                        <select class="form-select" name="resolution" required>
                                                            <option value="">Select resolution</option>
                                                            <option value="resolved_buyer">Resolve in Buyer's Favor (Refund)</option>
                                                            <option value="resolved_seller">Resolve in Seller's Favor</option>
                                                            <option value="closed">Close Without Action</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Admin Notes</label>
                                                        <textarea class="form-control" name="admin_notes" rows="3"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="resolve" class="btn btn-primary">Resolve Dispute</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="mt-4">
        <?= renderPagination($pagination, BASE_URL . '/xadmincp11/disputes.php' . ($statusFilter ? '?status=' . $statusFilter : '')) ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
