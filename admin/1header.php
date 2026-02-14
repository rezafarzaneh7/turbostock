<?php
/**
 * HStore - Admin Header
 */
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../includes/init.php';
}
requireAdmin();

$currentUser = getCurrentUser();
$pageTitle = $pageTitle ?? 'Admin - ' . PLATFORM_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
    <style>
        body { background-color: #f1f5f9; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .admin-sidebar {
            width: 250px;
            background: #1e293b;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .admin-content { margin-left: 250px; flex: 1; }
        .admin-sidebar .brand {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 0.75rem 1.5rem;
            border-left: 3px solid transparent;
        }
        .admin-sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .admin-sidebar .nav-link.active {
            color: white;
            background: rgba(249, 115, 22, 0.3);
            border-left-color: #f97316;
        }
        .admin-sidebar .nav-link i { width: 20px; margin-right: 10px; }
        .admin-topbar {
            background: white;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        @media (max-width: 991px) {
            .admin-sidebar { width: 70px; }
            .admin-sidebar .brand span,
            .admin-sidebar .nav-link span { display: none; }
            .admin-sidebar .nav-link { text-align: center; padding: 1rem; }
            .admin-sidebar .nav-link i { margin: 0; }
            .admin-content { margin-left: 70px; }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="brand">
                <a href="<?= BASE_URL ?>/admin/" class="text-white text-decoration-none">
                    <i class="fas fa-store me-2"></i>
                    <span><?= PLATFORM_NAME ?> Admin</span>
                </a>
            </div>
            <nav class="nav flex-column py-3">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/users.php">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'sellers.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/sellers.php">
                    <i class="fas fa-store"></i>
                    <span>Sellers</span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'verifications.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/verifications.php">
                    <i class="fas fa-check-circle"></i>
                    <span>Verifications</span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/products.php">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/orders.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'payments.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/payments.php">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'withdrawals.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/withdrawals.php">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Withdrawals</span>
                    <?php 
                    $pendingWR = db()->count('withdrawal_requests', "status = 'pending'");
                    if ($pendingWR > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $pendingWR ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'disputes.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/disputes.php">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Disputes</span>
                </a>
                  <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'support.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/support.php">
                    <i class="fas fa-headset"></i>
                    <span>Support</span>
                    <?php 
                    try {
                        $openTickets = db()->count('support_tickets', "status IN ('open', 'in_progress')");
                        if ($openTickets > 0): ?>
                            <span class="badge bg-danger ms-1"><?= $openTickets ?></span>
                        <?php endif;
                    } catch (Exception $e) {} ?>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/categories.php">
                    <i class="fas fa-folder"></i>
                    <span>Categories</span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'logs.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/logs.php">
                    <i class="fas fa-history"></i>
                    <span>Logs</span>
                </a>
                <hr class="my-3 border-secondary">
                <a class="nav-link" href="<?= BASE_URL ?>">
                    <i class="fas fa-external-link-alt"></i>
                    <span>View Site</span>
                </a>
                <a class="nav-link text-danger" href="<?= BASE_URL ?>/auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-topbar d-flex justify-content-between align-items-center">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/">Admin</a></li>
                            <?php if (basename($_SERVER['PHP_SELF']) !== 'index.php'): ?>
                                <li class="breadcrumb-item active"><?= ucfirst(str_replace('.php', '', basename($_SERVER['PHP_SELF']))) ?></li>
                            <?php endif; ?>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-3">
                        <i class="fas fa-user-shield me-1"></i>
                        <?= sanitize($currentUser['username']) ?>
                    </span>
                </div>
            </div>
            
            <?php displayFlashMessage(); ?>
