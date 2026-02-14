<?php
/**
 * HStore - Admin Logs
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

// Get logs
$page = max(1, (int) ($_GET['page'] ?? 1));
$actionFilter = clean($_GET['action'] ?? '');

$where = "1=1";
$params = [];

if ($actionFilter) {
    $where .= " AND al.action LIKE ?";
    $params[] = "%{$actionFilter}%";
}

$totalLogs = db()->fetch("SELECT COUNT(*) as count FROM admin_logs al WHERE {$where}", $params)['count'];
$pagination = paginate($totalLogs, $page, 50);

$logs = db()->fetchAll("
    SELECT al.*, u.username as admin_username
    FROM admin_logs al
    JOIN users u ON al.admin_id = u.id
    WHERE {$where}
    ORDER BY al.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

$pageTitle = 'Admin Logs';
require_once __DIR__ . '/header.php';
?>

<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4">Admin Activity Logs</h4>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="action" placeholder="Filter by action" 
                           value="<?= sanitize($actionFilter) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="<?= BASE_URL ?>/xadmincp11/logs.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Logs Table -->
    <div class="card">
        <div class="card-header">
            <span><?= number_format($totalLogs) ?> log entries</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Target</th>
                            <th>IP Address</th>
                            <th>Date</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= $log['id'] ?></td>
                                <td><?= sanitize($log['admin_username']) ?></td>
                                <td><code><?= sanitize($log['action']) ?></code></td>
                                <td>
                                    <?php if ($log['target_type']): ?>
                                        <?= sanitize($log['target_type']) ?> #<?= $log['target_id'] ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><small><?= sanitize($log['ip_address']) ?></small></td>
                                <td><?= formatDateTime($log['created_at']) ?></td>
                                <td>
                                    <?php if ($log['new_value']): ?>
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                data-bs-toggle="modal" data-bs-target="#logModal<?= $log['id'] ?>">
                                            View
                                        </button>
                                        
                                        <div class="modal fade" id="logModal<?= $log['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Log Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <?php if ($log['old_value']): ?>
                                                            <h6>Old Value:</h6>
                                                            <pre class="bg-light p-2 rounded"><?= sanitize($log['old_value']) ?></pre>
                                                        <?php endif; ?>
                                                        <h6>New Value:</h6>
                                                        <pre class="bg-light p-2 rounded"><?= sanitize($log['new_value']) ?></pre>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <?= renderPagination($pagination, BASE_URL . '/xadmincp11/logs.php' . ($actionFilter ? '?action=' . urlencode($actionFilter) : '')) ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
