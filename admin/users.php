<?php
/**
 * HStore - Admin Users Management
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$action = clean($_GET['action'] ?? '');
$userId = (int) ($_GET['id'] ?? 0);
$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request';
    } else {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        
        if (isset($_POST['update_status'])) {
            $newStatus = clean($_POST['status'] ?? '');
            if (in_array($newStatus, ['active', 'suspended', 'banned'])) {
                $oldUser = db()->fetch("SELECT * FROM users WHERE id = ?", [$targetUserId]);
                db()->update('users', ['status' => $newStatus], 'id = ?', [$targetUserId]);
                logAdminAction('update_user_status', 'user', $targetUserId, ['status' => $oldUser['status']], ['status' => $newStatus]);
                $success = 'User status updated';
            }
        }
        
        if (isset($_POST['update_role'])) {
            $newRole = clean($_POST['role'] ?? '');
            if (in_array($newRole, ['buyer', 'seller', 'admin'])) {
                $oldUser = db()->fetch("SELECT * FROM users WHERE id = ?", [$targetUserId]);
                db()->update('users', ['role' => $newRole], 'id = ?', [$targetUserId]);
                
                // Create seller record if promoting to seller
                if ($newRole === 'seller' && $oldUser['role'] !== 'seller') {
                    $existingSeller = db()->fetch("SELECT id FROM sellers WHERE user_id = ?", [$targetUserId]);
                    if (!$existingSeller) {
                        db()->insert('sellers', [
                            'user_id' => $targetUserId,
                            'shop_name' => $oldUser['username'] . "'s Shop",
                            'shop_slug' => generateSlug($oldUser['username'] . '-shop')
                        ]);
                    }
                }
                
                logAdminAction('update_user_role', 'user', $targetUserId, ['role' => $oldUser['role']], ['role' => $newRole]);
                $success = 'User role updated';
            }
        }
        
        // Add credit to user wallet
        if (isset($_POST['add_credit'])) {
            $amount = (float) ($_POST['credit_amount'] ?? 0);
            $reason = clean($_POST['credit_reason'] ?? 'Admin credit');
            
            if ($amount <= 0) {
                $error = 'Amount must be greater than 0';
            } elseif ($amount > 100000) {
                $error = 'Maximum credit amount is 100,000 USDT';
            } else {
                // Get user's wallet
                $wallet = db()->fetch("SELECT * FROM wallets WHERE user_id = ?", [$targetUserId]);
                
                if (!$wallet) {
                    // Create wallet if not exists
                    db()->insert('wallets', ['user_id' => $targetUserId, 'balance' => 0]);
                    $wallet = db()->fetch("SELECT * FROM wallets WHERE user_id = ?", [$targetUserId]);
                }
                
                // Update balance
                db()->query("UPDATE wallets SET balance = balance + ?, total_deposited = total_deposited + ? WHERE user_id = ?", 
                    [$amount, $amount, $targetUserId]);
                
                // Create transaction record
                db()->insert('wallet_transactions', [
                    'wallet_id' => $wallet['id'],
                    'type' => 'deposit',
                    'amount' => $amount,
                    'description' => 'Admin Credit: ' . $reason,
                    'status' => 'completed'
                ]);
                
                // Notify user
                $user = db()->fetch("SELECT username FROM users WHERE id = ?", [$targetUserId]);
                createNotification(
                    $targetUserId,
                    'wallet',
                    'Credit Added',
                    formatCurrency($amount) . ' has been added to your wallet. Reason: ' . $reason,
                    BASE_URL . '/wallet.php'
                );
                
                logAdminAction('add_credit', 'user', $targetUserId, null, ['amount' => $amount, 'reason' => $reason]);
                $success = formatCurrency($amount) . ' credit added to ' . $user['username'] . "'s wallet";
            }
        }
        
        // Deduct credit from user wallet
        if (isset($_POST['deduct_credit'])) {
            $amount = (float) ($_POST['deduct_amount'] ?? 0);
            $reason = clean($_POST['deduct_reason'] ?? 'Admin deduction');
            
            if ($amount <= 0) {
                $error = 'Amount must be greater than 0';
            } else {
                $wallet = db()->fetch("SELECT * FROM wallets WHERE user_id = ?", [$targetUserId]);
                
                if (!$wallet || $wallet['balance'] < $amount) {
                    $error = 'Insufficient balance. User has ' . formatCurrency($wallet['balance'] ?? 0);
                } else {
                    // Update balance
                    db()->query("UPDATE wallets SET balance = balance - ? WHERE user_id = ?", [$amount, $targetUserId]);
                    
                    // Create transaction record
                    db()->insert('wallet_transactions', [
                        'wallet_id' => $wallet['id'],
                        'type' => 'withdrawal',
                        'amount' => -$amount,
                        'description' => 'Admin Deduction: ' . $reason,
                        'status' => 'completed'
                    ]);
                    
                    // Notify user
                    $user = db()->fetch("SELECT username FROM users WHERE id = ?", [$targetUserId]);
                    createNotification(
                        $targetUserId,
                        'wallet',
                        'Balance Deducted',
                        formatCurrency($amount) . ' has been deducted from your wallet. Reason: ' . $reason,
                        BASE_URL . '/wallet.php'
                    );
                    
                    logAdminAction('deduct_credit', 'user', $targetUserId, null, ['amount' => $amount, 'reason' => $reason]);
                    $success = formatCurrency($amount) . ' deducted from ' . $user['username'] . "'s wallet";
                }
            }
        }
    }
}

// Get users
$page = max(1, (int) ($_GET['page'] ?? 1));
$search = clean($_GET['search'] ?? '');
$roleFilter = clean($_GET['role'] ?? '');
$statusFilter = clean($_GET['status'] ?? '');

$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($roleFilter) {
    $where .= " AND role = ?";
    $params[] = $roleFilter;
}

if ($statusFilter) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}

$totalUsers = db()->fetch("SELECT COUNT(*) as count FROM users WHERE {$where}", $params)['count'];
$pagination = paginate($totalUsers, $page, ADMIN_ITEMS_PER_PAGE);

$users = db()->fetchAll("
    SELECT u.*, 
           (SELECT balance FROM wallets WHERE user_id = u.id) as wallet_balance
    FROM users u
    WHERE {$where}
    ORDER BY u.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

$pageTitle = 'Users - Admin';
require_once __DIR__ . '/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Users Management</h4>
    </div>
    
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
                    <input type="text" class="form-control" name="search" placeholder="Search username or email" 
                           value="<?= sanitize($search) ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="role">
                        <option value="">All Roles</option>
                        <option value="buyer" <?= $roleFilter === 'buyer' ? 'selected' : '' ?>>Buyer</option>
                        <option value="seller" <?= $roleFilter === 'seller' ? 'selected' : '' ?>>Seller</option>
                        <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        <option value="banned" <?= $statusFilter === 'banned' ? 'selected' : '' ?>>Banned</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="<?= BASE_URL ?>/xadmincp11/users.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="card" style="overflow: visible;">
        <div class="card-header">
            <span><?= number_format($totalUsers) ?> users</span>
        </div>
        <div class="card-body p-0" style="overflow: visible;">
            <div class="" style="overflow-x: auto;">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <strong><?= sanitize($user['username']) ?></strong>
                                </td>
                                <td><?= sanitize($user['email']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'seller' ? 'success' : 'primary') ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td><?= formatCurrency($user['wallet_balance'] ?? 0) ?></td>
                                <td>
                                    <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : ($user['status'] === 'suspended' ? 'warning' : 'danger') ?>">
                                        <?= ucfirst($user['status']) ?>
                                    </span>
                                </td>
                                <td><?= formatDate($user['created_at']) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <form method="POST" action="" class="px-3 py-2">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <label class="form-label small">Status</label>
                                                    <select class="form-select form-select-sm mb-2" name="status">
                                                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                        <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                                        <option value="banned" <?= $user['status'] === 'banned' ? 'selected' : '' ?>>Banned</option>
                                                    </select>
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary w-100">Update Status</button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" action="" class="px-3 py-2">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <label class="form-label small">Role</label>
                                                    <select class="form-select form-select-sm mb-2" name="role">
                                                        <option value="buyer" <?= $user['role'] === 'buyer' ? 'selected' : '' ?>>Buyer</option>
                                                        <option value="seller" <?= $user['role'] === 'seller' ? 'selected' : '' ?>>Seller</option>
                                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                    </select>
                                                    <button type="submit" name="update_role" class="btn btn-sm btn-warning w-100">Update Role</button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" action="" class="px-3 py-2">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <label class="form-label small text-success"><i class="fas fa-plus-circle me-1"></i>Add Credit</label>
                                                    <input type="number" class="form-control form-control-sm mb-1" name="credit_amount" placeholder="Amount" step="0.01" min="0.01" required>
                                                    <input type="text" class="form-control form-control-sm mb-2" name="credit_reason" placeholder="Reason (optional)">
                                                    <button type="submit" name="add_credit" class="btn btn-sm btn-success w-100">Add Credit</button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" action="" class="px-3 py-2">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <label class="form-label small text-danger"><i class="fas fa-minus-circle me-1"></i>Deduct Credit</label>
                                                    <input type="number" class="form-control form-control-sm mb-1" name="deduct_amount" placeholder="Amount" step="0.01" min="0.01" required>
                                                    <input type="text" class="form-control form-control-sm mb-2" name="deduct_reason" placeholder="Reason (optional)">
                                                    <button type="submit" name="deduct_credit" class="btn btn-sm btn-danger w-100">Deduct</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <?= renderPagination($pagination, BASE_URL . '/xadmincp11/users.php?' . http_build_query(array_filter(['search' => $search, 'role' => $roleFilter, 'status' => $statusFilter]))) ?>
    </div>
</div>

<style>
.btn-group .dropdown-menu {
    position: fixed !important;
    transform: none !important;
    top: auto !important;
    left: auto !important;
    right: 50px !important;
    z-index: 9999;
    min-width: 200px;
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
