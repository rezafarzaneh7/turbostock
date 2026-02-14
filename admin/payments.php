<?php
/**
 * HStore - Admin Payments Management
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

// Get payments
$page = max(1, (int) ($_GET['page'] ?? 1));
$typeFilter = clean($_GET['type'] ?? '');
$statusFilter = clean($_GET['status'] ?? '');

$where = "1=1";
$params = [];

if ($typeFilter) {
    $where .= " AND tp.payment_type = ?";
    $params[] = $typeFilter;
}

if ($statusFilter) {
    $where .= " AND tp.status = ?";
    $params[] = $statusFilter;
}

$totalPayments = db()->fetch("SELECT COUNT(*) as count FROM tron_payments tp WHERE {$where}", $params)['count'];
$pagination = paginate($totalPayments, $page, ADMIN_ITEMS_PER_PAGE);

$payments = db()->fetchAll("
    SELECT tp.*, u.username, u.email
    FROM tron_payments tp
    JOIN users u ON tp.user_id = u.id
    WHERE {$where}
    ORDER BY tp.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

// Stats
$totalDeposits = db()->fetch("SELECT COALESCE(SUM(received_amount), 0) as total FROM tron_payments WHERE payment_type = 'deposit' AND status = 'confirmed'")['total'];
$totalVerifications = db()->fetch("SELECT COALESCE(SUM(received_amount), 0) as total FROM tron_payments WHERE payment_type = 'verification' AND status = 'confirmed'")['total'];
$pendingPayments = db()->count('tron_payments', "status = 'pending'");

$pageTitle = 'Payments - Admin';
require_once __DIR__ . '/header.php';
?>

<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4">Payments & Transactions</h4>
    
    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Total Deposits</h6>
                    <h3 class="fw-bold mb-0"><?= formatCurrency($totalDeposits) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Verification Fees</h6>
                    <h3 class="fw-bold mb-0"><?= formatCurrency($totalVerifications) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="opacity-75">Pending Payments</h6>
                    <h3 class="fw-bold mb-0"><?= $pendingPayments ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <option value="deposit" <?= $typeFilter === 'deposit' ? 'selected' : '' ?>>Deposit</option>
                        <option value="verification" <?= $typeFilter === 'verification' ? 'selected' : '' ?>>Verification</option>
                        <option value="withdrawal" <?= $typeFilter === 'withdrawal' ? 'selected' : '' ?>>Withdrawal</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
                        <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="<?= BASE_URL ?>/xadmincp11/payments.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Payments Table -->
    <div class="card">
        <div class="card-header">
            <span><?= number_format($totalPayments) ?> payments</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Expected</th>
                            <th>Received</th>
                            <th>Wallet</th>
                            <th>TX Hash</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= $payment['id'] ?></td>
                                <td>
                                    <?= sanitize($payment['username']) ?>
                                    <small class="text-muted d-block"><?= sanitize($payment['email']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $payment['payment_type'] === 'deposit' ? 'success' : ($payment['payment_type'] === 'verification' ? 'primary' : 'info') ?>">
                                        <?= ucfirst($payment['payment_type']) ?>
                                    </span>
                                </td>
                                <td><?= formatCurrency($payment['expected_amount']) ?></td>
                                <td>
                                    <?php if ($payment['received_amount'] > 0): ?>
                                        <span class="text-success"><?= formatCurrency($payment['received_amount']) ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code class="small"><?= substr($payment['wallet_address'], 0, 12) ?>...</code>
                                </td>
                                <td>
                                    <?php if ($payment['tx_hash']): ?>
                                        <a href="<?= TRON_SCAN_URL ?>/#/transaction/<?= $payment['tx_hash'] ?>" target="_blank" class="small">
                                            <?= substr($payment['tx_hash'], 0, 10) ?>...
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = match($payment['status']) {
                                        'confirmed' => 'success',
                                        'pending', 'confirming' => 'warning',
                                        'expired', 'failed' => 'danger',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>">
                                        <?= ucfirst($payment['status']) ?>
                                    </span>
                                </td>
                                <td><?= formatDateTime($payment['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <?= renderPagination($pagination, BASE_URL . '/xadmincp11/payments.php?' . http_build_query(array_filter(['type' => $typeFilter, 'status' => $statusFilter]))) ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
