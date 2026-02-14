<?php
/**
 * HStore - Admin Seller Verifications
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
        $verificationId = (int) ($_POST['verification_id'] ?? 0);
        $sellerId = (int) ($_POST['seller_id'] ?? 0);
        
        if (isset($_POST['approve'])) {
            // Manual approve
            db()->update('seller_verifications', [
                'status' => 'manual_approved',
                'admin_notes' => clean($_POST['notes'] ?? ''),
                'confirmed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$verificationId]);
            
            db()->update('sellers', [
                'verification_status' => 'verified',
                'verified_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$sellerId]);
            
            $seller = db()->fetch("SELECT user_id FROM sellers WHERE id = ?", [$sellerId]);
            createNotification($seller['user_id'], 'verification', 'Verification Approved', 'Your seller account has been verified!');
            
            logAdminAction('approve_verification', 'seller', $sellerId);
            $success = 'Seller verified successfully';
        }
        
        if (isset($_POST['reject'])) {
            db()->update('seller_verifications', [
                'status' => 'manual_rejected',
                'admin_notes' => clean($_POST['notes'] ?? '')
            ], 'id = ?', [$verificationId]);
            
            db()->update('sellers', ['verification_status' => 'unverified'], 'id = ?', [$sellerId]);
            
            logAdminAction('reject_verification', 'seller', $sellerId);
            $success = 'Verification rejected';
        }
        
        if (isset($_POST['revoke'])) {
            db()->update('sellers', [
                'verification_status' => 'unverified',
                'verified_at' => null
            ], 'id = ?', [$sellerId]);
            
            $seller = db()->fetch("SELECT user_id FROM sellers WHERE id = ?", [$sellerId]);
            createNotification($seller['user_id'], 'verification', 'Verification Revoked', 'Your verified status has been revoked.');
            
            logAdminAction('revoke_verification', 'seller', $sellerId);
            $success = 'Verification revoked';
        }
    }
}

// Get pending verifications
$pendingVerifications = db()->fetchAll("
    SELECT sv.*, s.shop_name, s.user_id, u.username, u.email
    FROM seller_verifications sv
    JOIN sellers s ON sv.seller_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE sv.status = 'pending'
    ORDER BY sv.created_at DESC
");

// Get all verifications
$page = max(1, (int) ($_GET['page'] ?? 1));
$statusFilter = clean($_GET['status'] ?? '');

$where = "1=1";
$params = [];

if ($statusFilter) {
    $where .= " AND sv.status = ?";
    $params[] = $statusFilter;
}

$totalVerifications = db()->fetch("
    SELECT COUNT(*) as count FROM seller_verifications sv WHERE {$where}
", $params)['count'];

$pagination = paginate($totalVerifications, $page, 20);

$verifications = db()->fetchAll("
    SELECT sv.*, s.shop_name, s.verification_status as seller_status, u.username, u.email
    FROM seller_verifications sv
    JOIN sellers s ON sv.seller_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE {$where}
    ORDER BY sv.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

$pageTitle = 'Verifications - Admin';
require_once __DIR__ . '/header.php';
?>

<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4">Seller Verifications</h4>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= sanitize($success) ?></div>
    <?php endif; ?>
    
    <!-- Pending Verifications -->
    <?php if (!empty($pendingVerifications)): ?>
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pending Verifications (<?= count($pendingVerifications) ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Seller</th>
                            <th>Shop</th>
                            <th>Amount</th>
                            <th>Wallet</th>
                            <th>Received</th>
                            <th>TX Hash</th>
                            <th>Requested</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingVerifications as $v): ?>
                            <tr>
                                <td>
                                    <strong><?= sanitize($v['username']) ?></strong>
                                    <small class="text-muted d-block"><?= sanitize($v['email']) ?></small>
                                </td>
                                <td><?= sanitize($v['shop_name']) ?></td>
                                <td><?= formatCurrency($v['amount_required']) ?></td>
                                <td>
                                    <code class="small"><?= substr($v['tron_wallet_address'], 0, 15) ?>...</code>
                                </td>
                                <td>
                                    <?php if ($v['amount_received'] > 0): ?>
                                        <span class="text-success"><?= formatCurrency($v['amount_received']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($v['tx_hash']): ?>
                                        <a href="<?= TRON_SCAN_URL ?>/#/transaction/<?= $v['tx_hash'] ?>" target="_blank" class="small">
                                            <?= substr($v['tx_hash'], 0, 10) ?>...
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= timeAgo($v['created_at']) ?></td>
                                <td>
                                    <form method="POST" action="" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="verification_id" value="<?= $v['id'] ?>">
                                        <input type="hidden" name="seller_id" value="<?= $v['seller_id'] ?>">
                                        <button type="submit" name="approve" class="btn btn-sm btn-success" 
                                                onclick="return confirm('Manually approve this verification?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button type="submit" name="reject" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Reject this verification?')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- All Verifications -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Verification Requests</h5>
            <select class="form-select form-select-sm" style="width: auto;" 
                    onchange="window.location.href='<?= BASE_URL ?>/xadmincp11/verifications.php?status='+this.value">
                <option value="">All Status</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="manual_approved" <?= $statusFilter === 'manual_approved' ? 'selected' : '' ?>>Manual Approved</option>
                <option value="manual_rejected" <?= $statusFilter === 'manual_rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
            </select>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Seller</th>
                            <th>Shop</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>TX Hash</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($verifications as $v): ?>
                            <tr>
                                <td><?= $v['id'] ?></td>
                                <td><?= sanitize($v['username']) ?></td>
                                <td><?= sanitize($v['shop_name']) ?></td>
                                <td><?= formatCurrency($v['amount_required']) ?></td>
                                <td>
                                    <?php
                                    $statusClass = match($v['status']) {
                                        'confirmed', 'manual_approved' => 'success',
                                        'pending' => 'warning',
                                        'manual_rejected', 'failed' => 'danger',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $v['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($v['tx_hash']): ?>
                                        <a href="<?= TRON_SCAN_URL ?>/#/transaction/<?= $v['tx_hash'] ?>" target="_blank" class="small">
                                            <?= substr($v['tx_hash'], 0, 10) ?>...
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= formatDateTime($v['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <?= renderPagination($pagination, BASE_URL . '/xadmincp11/verifications.php' . ($statusFilter ? '?status=' . $statusFilter : '')) ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
