<?php
/**
 * HStore - Admin Sellers Management
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
        $sellerId = (int) ($_POST['seller_id'] ?? 0);
        
        if (isset($_POST['revoke_verification'])) {
            db()->update('sellers', [
                'verification_status' => 'unverified',
                'verified_at' => null
            ], 'id = ?', [$sellerId]);
            
            $seller = db()->fetch("SELECT user_id FROM sellers WHERE id = ?", [$sellerId]);
            createNotification($seller['user_id'], 'verification', 'Verification Revoked', 'Your verified seller status has been revoked.');
            logAdminAction('revoke_seller_verification', 'seller', $sellerId);
            $success = 'Seller verification revoked';
        }
        
        if (isset($_POST['verify_seller'])) {
            db()->update('sellers', [
                'verification_status' => 'verified',
                'verified_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$sellerId]);
            
            $seller = db()->fetch("SELECT user_id FROM sellers WHERE id = ?", [$sellerId]);
            createNotification($seller['user_id'], 'verification', 'Account Verified', 'Your seller account has been verified by admin.');
            logAdminAction('verify_seller', 'seller', $sellerId);
            $success = 'Seller verified successfully';
        }
    }
}

// Get sellers
$page = max(1, (int) ($_GET['page'] ?? 1));
$search = clean($_GET['search'] ?? '');
$verificationFilter = clean($_GET['verification'] ?? '');

$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (s.shop_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($verificationFilter) {
    $where .= " AND s.verification_status = ?";
    $params[] = $verificationFilter;
}

$totalSellers = db()->fetch("
    SELECT COUNT(*) as count FROM sellers s JOIN users u ON s.user_id = u.id WHERE {$where}
", $params)['count'];

$pagination = paginate($totalSellers, $page, ADMIN_ITEMS_PER_PAGE);

$sellers = db()->fetchAll("
    SELECT s.*, u.username, u.email, u.status as user_status,
           (SELECT balance FROM wallets WHERE user_id = s.user_id) as wallet_balance,
           (SELECT COUNT(*) FROM products WHERE seller_id = s.id) as product_count
    FROM sellers s
    JOIN users u ON s.user_id = u.id
    WHERE {$where}
    ORDER BY s.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

$pageTitle = 'Sellers - Admin';
require_once __DIR__ . '/header.php';
?>

<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4">Sellers Management</h4>
    
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
                    <input type="text" class="form-control" name="search" placeholder="Search shop name, username, email" 
                           value="<?= sanitize($search) ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="verification">
                        <option value="">All Verification Status</option>
                        <option value="verified" <?= $verificationFilter === 'verified' ? 'selected' : '' ?>>Verified</option>
                        <option value="unverified" <?= $verificationFilter === 'unverified' ? 'selected' : '' ?>>Unverified</option>
                        <option value="pending" <?= $verificationFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="<?= BASE_URL ?>/xadmincp11/sellers.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Sellers Table -->
    <div class="card">
        <div class="card-header">
            <span><?= number_format($totalSellers) ?> sellers</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Shop</th>
                            <th>Owner</th>
                            <th>Products</th>
                            <th>Sales</th>
                            <th>Earnings</th>
                            <th>Rating</th>
                            <th>Verification</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sellers as $seller): ?>
                            <tr>
                                <td><?= $seller['id'] ?></td>
                                <td>
                                    <a href="<?= storeUrl($seller['id']) ?>" target="_blank">
                                        <strong><?= sanitize($seller['shop_name']) ?></strong>
                                    </a>
                                </td>
                                <td>
                                    <?= sanitize($seller['username']) ?>
                                    <small class="text-muted d-block"><?= sanitize($seller['email']) ?></small>
                                </td>
                                <td><?= $seller['product_count'] ?></td>
                                <td><?= $seller['total_sales'] ?></td>
                                <td><?= formatCurrency($seller['total_earnings']) ?></td>
                                <td>
                                    <?= renderStars($seller['rating_average'], false) ?>
                                    <small class="text-muted">(<?= $seller['rating_count'] ?>)</small>
                                </td>
                                <td><?= getVerificationBadge($seller['verification_status']) ?></td>
                                <td>
                                    <form method="POST" action="" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="seller_id" value="<?= $seller['id'] ?>">
                                        <?php if ($seller['verification_status'] === 'verified'): ?>
                                            <button type="submit" name="revoke_verification" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Revoke verification for this seller?')">
                                                Revoke
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="verify_seller" class="btn btn-sm btn-outline-success"
                                                    onclick="return confirm('Verify this seller?')">
                                                Verify
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                    <a href="<?= storeUrl($seller['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
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
        <?= renderPagination($pagination, BASE_URL . '/xadmincp11/sellers.php?' . http_build_query(array_filter(['search' => $search, 'verification' => $verificationFilter]))) ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
