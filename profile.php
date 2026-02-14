<?php
/**
 * TurboStock - User Profile Page (New Design)
 */
require_once __DIR__ . '/includes/init.php';
requireLogin();

$userId = getCurrentUserId();
$user = getCurrentUser();

$error = '';
$success = '';

// Get user stats
$orderCount = db()->fetch("SELECT COUNT(*) as count FROM orders WHERE buyer_id = ?", [$userId])['count'] ?? 0;
$totalSpent = db()->fetch("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE buyer_id = ? AND status = 'completed'", [$userId])['total'] ?? 0;
$memberSince = date('M Y', strtotime($user['created_at']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request';
    } else {
        if (isset($_POST['update_profile'])) {
            $username = clean($_POST['username'] ?? '');
            $email = clean($_POST['email'] ?? '');

            if (empty($username) || empty($email)) {
                $error = 'Username and email are required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address';
            } else {
                // Check if username taken
                $existing = db()->fetch("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $userId]);
                if ($existing) {
                    $error = 'Username is already taken';
                } else {
                    // Check if email taken
                    $existing = db()->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId]);
                    if ($existing) {
                        $error = 'Email is already registered';
                    } else {
                        $updateData = [
                            'username' => $username,
                            'email' => $email
                        ];

                        // Handle avatar upload
                        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                            $result = uploadFile($_FILES['avatar'], 'avatars');
                            if ($result['success']) {
                                $updateData['avatar'] = $result['path'];
                            } else {
                                $error = $result['error'];
                            }
                        }

                        if (!$error) {
                            db()->update('users', $updateData, 'id = ?', [$userId]);
                            $_SESSION['username'] = $username;
                            $user = getCurrentUser();
                            $success = 'Profile updated successfully';
                        }
                    }
                }
            }
        }

        if (isset($_POST['change_password'])) {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword)) {
                $error = 'All password fields are required';
            } elseif (!verifyPassword($currentPassword, $user['password'])) {
                $error = 'Current password is incorrect';
            } elseif (strlen($newPassword) < 6) {
                $error = 'New password must be at least 6 characters';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match';
            } else {
                db()->update('users', ['password' => hashPassword($newPassword)], 'id = ?', [$userId]);
                $success = 'Password changed successfully';
            }
        }
    }
}

$pageTitle = 'My Profile - ' . PLATFORM_NAME;
$bodyClass = 'page-my-profile';
require_once INCLUDES_PATH . '/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>"><?= __('home') ?></a><span>/</span>
            <span class="current"><?= __('my_profile') ?></span>
        </div>
        <h1 class="page-title"><?= __('my') ?> <span><?= __('profile') ?></span></h1>
    </div>
</div>

<div class="container">
    <div class="main-content">
        <!-- Sidebar Menu -->
        <aside class="sidebar-menu">
            <a href="<?= BASE_URL ?>/profile.php" class="menu-item active">
                <i class="fas fa-user"></i> <?= __('my_profile') ?>
            </a>
            <a href="<?= BASE_URL ?>/orders.php" class="menu-item">
                <i class="fas fa-shopping-bag"></i> <?= __('my_orders') ?>
            </a>
            <a href="<?= BASE_URL ?>/wallet.php" class="menu-item">
                <i class="fas fa-wallet"></i> <?= __('wallet') ?>
            </a>
            <a href="<?= BASE_URL ?>/wishlist.php" class="menu-item">
                <i class="fas fa-heart"></i> <?= __('wishlist') ?>
            </a>
            <?php if (isSeller()): ?>
                <a href="<?= BASE_URL ?>/seller/" class="menu-item">
                    <i class="fas fa-store"></i> <?= __('seller_dashboard') ?>
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/become-seller.php" class="menu-item">
                    <i class="fas fa-store"></i> <?= __('become_seller') ?>
                </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i> <?= __('logout') ?>
            </a>
        </aside>

        <!-- Main Content -->
        <main>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= sanitize($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= sanitize($success) ?></div>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php if ($user['avatar']): ?>
                        <img src="<?= UPLOADS_URL ?>/<?= $user['avatar'] ?>" alt="<?= sanitize($user['username']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                    <?php endif; ?>
                    <label for="avatarUpload" class="avatar-edit">
                        <i class="fas fa-camera"></i>
                    </label>
                </div>
                <div class="profile-info">
                    <h2 class="profile-name"><?= sanitize($user['username']) ?></h2>
                    <p class="profile-email"><?= sanitize($user['email']) ?></p>
                    <div class="profile-badges">
                        <?php if ($user['status'] === 'active'): ?>
                            <span class="profile-badge"><i class="fas fa-check-circle"></i> <?= __('active') ?></span>
                        <?php endif; ?>
                        <span class="profile-badge role-<?= $user['role'] ?>">
                            <i class="fas fa-<?= $user['role'] === 'admin' ? 'shield-alt' : ($user['role'] === 'seller' ? 'store' : 'user') ?>"></i>
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </div>
                </div>
                <div class="profile-stats">
                    <div class="profile-stat">
                        <div class="profile-stat-value"><?= number_format($orderCount) ?></div>
                        <div class="profile-stat-label"><?= __('orders') ?></div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-value"><?= formatCurrency($totalSpent) ?></div>
                        <div class="profile-stat-label"><?= __('spent') ?></div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-value"><?= $memberSince ?></div>
                        <div class="profile-stat-label"><?= __('member_since') ?></div>
                    </div>
                </div>
            </div>

            <!-- Personal Information Card -->
            <div class="card">
                <h3 class="card-title">
                    <span><i class="fas fa-user-edit"></i> <?= __('personal_information') ?></span>
                    <span class="edit-btn" onclick="toggleEditMode('personal')">
                        <i class="fas fa-pen"></i> <?= __('edit') ?>
                    </span>
                </h3>
                <form method="POST" action="" enctype="multipart/form-data" id="personalForm">
                    <?= csrfField() ?>
                    <input type="file" id="avatarUpload" name="avatar" accept="image/*" style="display: none;" onchange="this.form.submit()">
                    <input type="hidden" name="update_profile" value="1">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?= __('username') ?></label>
                            <input type="text" class="form-input" name="username" value="<?= sanitize($user['username']) ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= __('email_address') ?></label>
                            <input type="email" class="form-input" name="email" value="<?= sanitize($user['email']) ?>" disabled>
                        </div>
                    </div>
                    <div class="form-group" id="savePersonalBtn" style="display: none;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?= __('save_changes') ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security Card -->
            <div class="card">
                <h3 class="card-title">
                    <span><i class="fas fa-lock"></i> <?= __('security') ?></span>
                </h3>
                <form method="POST" action="" id="securityForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="change_password" value="1">

                    <div class="form-group">
                        <label class="form-label"><?= __('current_password') ?></label>
                        <input type="password" class="form-input" name="current_password" placeholder="<?= __('enter_current_password') ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?= __('new_password') ?></label>
                            <input type="password" class="form-input" name="new_password" placeholder="<?= __('enter_new_password') ?>" minlength="6">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= __('confirm_password') ?></label>
                            <input type="password" class="form-input" name="confirm_password" placeholder="<?= __('confirm_new_password') ?>">
                        </div>
                    </div>
                    <div class="profile-action-row">
                        <button type="submit" class="btn btn-outline">
                            <i class="fas fa-key"></i> <?= __('change_password') ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Account Actions Card -->
            <div class="card">
                <h3 class="card-title">
                    <span><i class="fas fa-exclamation-triangle text-danger"></i> <?= __('danger_zone') ?></span>
                </h3>
                <p class="danger-text"><?= __('delete_account_warning') ?></p>
                <button class="btn btn-danger" onclick="confirmDeleteAccount()">
                    <i class="fas fa-trash"></i> <?= __('delete_account') ?>
                </button>
            </div>
        </main>
    </div>
</div>

<script>
function toggleEditMode(section) {
    const form = document.getElementById(section + 'Form');
    const inputs = form.querySelectorAll('.form-input');
    const saveBtn = document.getElementById('save' + section.charAt(0).toUpperCase() + section.slice(1) + 'Btn');

    inputs.forEach(input => {
        input.disabled = !input.disabled;
        if (!input.disabled) {
            input.focus();
        }
    });

    if (saveBtn) {
        saveBtn.style.display = saveBtn.style.display === 'none' ? 'block' : 'none';
    }
}

function confirmDeleteAccount() {
    if (confirm('<?= __('delete_account_confirm') ?>')) {
        window.location.href = '<?= BASE_URL ?>/api/delete-account.php';
    }
}

// Avatar upload preview
document.getElementById('avatarUpload').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const avatar = document.querySelector('.profile-avatar');
            avatar.innerHTML = '<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">' +
                             '<label for="avatarUpload" class="avatar-edit"><i class="fas fa-camera"></i></label>';
        };
        reader.readAsDataURL(this.files[0]);
    }
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
