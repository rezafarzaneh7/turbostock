<?php
/**
 * HStore - Admin Withdrawals Management
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
        $withdrawalId = (int) ($_POST['withdrawal_id'] ?? 0);
        $withdrawal = db()->fetch("SELECT * FROM withdrawal_requests WHERE id = ?", [$withdrawalId]);
        
        if (!$withdrawal) {
            $error = 'Withdrawal request not found';
        } else {
            if (isset($_POST['approve'])) {
                $txHash = clean($_POST['tx_hash'] ?? '');
                
                if (empty($txHash)) {
                    $error = 'Transaction hash is required';
                } else {
                    // Update withdrawal status
                    db()->update('withdrawal_requests', [
                        'status' => 'completed',
                        'tx_hash' => $txHash,
                        'processed_by' => getCurrentUserId(),
                        'processed_at' => date('Y-m-d H:i:s')
                    ], 'id = ?', [$withdrawalId]);
                    
                    // Update wallet total_withdrawn
                    db()->query("UPDATE wallets SET total_withdrawn = total_withdrawn + ? WHERE user_id = ?", 
                        [$withdrawal['amount'], $withdrawal['user_id']]);
                    
                    // Update transaction status
                    db()->query("
                        UPDATE wallet_transactions 
                        SET status = 'completed' 
                        WHERE wallet_id = (SELECT id FROM wallets WHERE user_id = ?) 
                        AND type = 'withdrawal' AND status = 'pending'
                        ORDER BY created_at DESC LIMIT 1
                    ", [$withdrawal['user_id']]);
                    
                    // Notify user
                    createNotification(
                        $withdrawal['user_id'],
                        'wallet',
                        'Withdrawal Completed',
                        'Your withdrawal of ' . formatCurrency($withdrawal['amount']) . ' has been sent to your wallet.',
                        BASE_URL . '/wallet.php'
                    );
                    
                    logAdminAction('approve_withdrawal', 'withdrawal', $withdrawalId, null, ['tx_hash' => $txHash]);
                    $success = 'Withdrawal approved and marked as completed';
                }
            }
            
            if (isset($_POST['reject'])) {
                $reason = clean($_POST['reject_reason'] ?? 'Request rejected by admin');
                
                // Refund the amount back to user's wallet
                db()->query("UPDATE wallets SET balance = balance + ? WHERE user_id = ?", 
                    [$withdrawal['amount'], $withdrawal['user_id']]);
                
                // Update withdrawal status
                db()->update('withdrawal_requests', [
                    'status' => 'rejected',
                    'admin_notes' => $reason,
                    'processed_by' => getCurrentUserId(),
                    'processed_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$withdrawalId]);
                
                // Update transaction status
                db()->query("
                    UPDATE wallet_transactions 
                    SET status = 'cancelled', description = CONCAT(description, ' - Rejected: ', ?)
                    WHERE wallet_id = (SELECT id FROM wallets WHERE user_id = ?) 
                    AND type = 'withdrawal' AND status = 'pending'
                    ORDER BY created_at DESC LIMIT 1
                ", [$reason, $withdrawal['user_id']]);
                
                // Notify user
                createNotification(
                    $withdrawal['user_id'],
                    'wallet',
                    'Withdrawal Rejected',
                    'Your withdrawal request was rejected. Reason: ' . $reason . '. Amount has been refunded.',
                    BASE_URL . '/wallet.php'
                );
                
                logAdminAction('reject_withdrawal', 'withdrawal', $withdrawalId, null, ['reason' => $reason]);
                $success = 'Withdrawal rejected and amount refunded to user';
            }
        }
    }
}

// Get withdrawals
$page = max(1, (int) ($_GET['page'] ?? 1));
$statusFilter = clean($_GET['status'] ?? '');

$where = "1=1";
$params = [];

if ($statusFilter) {
    $where .= " AND wr.status = ?";
    $params[] = $statusFilter;
}

$totalWithdrawals = db()->fetch("SELECT COUNT(*) as count FROM withdrawal_requests wr WHERE {$where}", $params)['count'];
$pagination = paginate($totalWithdrawals, $page, ADMIN_ITEMS_PER_PAGE);

$withdrawals = db()->fetchAll("
    SELECT wr.*, u.username, u.email
    FROM withdrawal_requests wr
    JOIN users u ON wr.user_id = u.id
    WHERE {$where}
    ORDER BY wr.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

// Stats
$pendingCount = db()->count('withdrawal_requests', "status = 'pending'");
$pendingAmount = db()->fetch("SELECT COALESCE(SUM(amount), 0) as total FROM withdrawal_requests WHERE status = 'pending'")['total'];
$completedAmount = db()->fetch("SELECT COALESCE(SUM(amount), 0) as total FROM withdrawal_requests WHERE status = 'completed'")['total'];

$pageTitle = 'Withdrawals - Admin';
require_once __DIR__ . '/header.php';
?>

<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4">Withdrawal Requests</h4>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= sanitize($success) ?></div>
    <?php endif; ?>
    
    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="opacity-75">Pending Requests</h6>
                    <h3 class="fw-bold mb-0"><?= $pendingCount ?></h3>
                    <small><?= formatCurrency($pendingAmount) ?> total</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Total Paid Out</h6>
                    <h3 class="fw-bold mb-0"><?= formatCurrency($completedAmount) ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="<?= BASE_URL ?>/xadmincp11/withdrawals.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Withdrawals Table -->
    <div class="card">
        <div class="card-header">
            <span><?= number_format($totalWithdrawals) ?> withdrawal requests</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($withdrawals)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">No withdrawal requests</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Wallet Address</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withdrawals as $wr): ?>
                                <tr>
                                    <td><?= $wr['id'] ?></td>
                                    <td>
                                        <?= sanitize($wr['username']) ?>
                                        <small class="text-muted d-block"><?= sanitize($wr['email']) ?></small>
                                    </td>
                                    <td><strong><?= formatCurrency($wr['amount']) ?></strong></td>
                                    <td>
                                        <code class="small"><?= sanitize($wr['wallet_address']) ?></code>
                                        <button class="btn btn-sm btn-link p-0 ms-1" onclick="navigator.clipboard.writeText('<?= sanitize($wr['wallet_address']) ?>')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($wr['status']) {
                                            'completed' => 'success',
                                            'pending' => 'warning',
                                            'processing' => 'info',
                                            'rejected' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>">
                                            <?= ucfirst($wr['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= formatDateTime($wr['created_at']) ?></td>
                                    <td>
                                        <?php if ($wr['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    data-bs-toggle="modal" data-bs-target="#approveModal<?= $wr['id'] ?>">
                                                Approve
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" data-bs-target="#rejectModal<?= $wr['id'] ?>">
                                                Reject
                                            </button>
                                        <?php elseif ($wr['status'] === 'completed' && $wr['tx_hash']): ?>
                                            <a href="<?= TRON_SCAN_URL ?>/#/transaction/<?= $wr['tx_hash'] ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                View TX
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
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
        <?= renderPagination($pagination, BASE_URL . '/xadmincp11/withdrawals.php' . ($statusFilter ? '?status=' . $statusFilter : '')) ?>
    </div>
</div>

<!-- Withdrawal Modals - Outside of table for proper z-index -->
<?php foreach ($withdrawals as $wr): ?>
    <?php if ($wr['status'] === 'pending'): ?>
    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal<?= $wr['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <?= csrfField() ?>
                    <input type="hidden" name="withdrawal_id" value="<?= $wr['id'] ?>">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Approve Withdrawal</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>Amount:</strong> <?= formatCurrency($wr['amount']) ?><br>
                            <strong>To:</strong> <code><?= sanitize($wr['wallet_address']) ?></code>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction Hash (after sending)</label>
                            <input type="text" class="form-control" name="tx_hash" required 
                                   placeholder="Enter the TRON transaction hash">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="approve" class="btn btn-success">Confirm & Approve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal<?= $wr['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <?= csrfField() ?>
                    <input type="hidden" name="withdrawal_id" value="<?= $wr['id'] ?>">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Reject Withdrawal</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            Amount will be refunded to user's wallet.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason for Rejection</label>
                            <textarea class="form-control" name="reject_reason" rows="3" 
                                      placeholder="Enter reason..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reject" class="btn btn-danger">Reject & Refund</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
