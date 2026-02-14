<?php
/**
 * HStore - Registration Page
 */
require_once __DIR__ . '/../includes/init.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = clean($_POST['username'] ?? '');
        $email = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $agreeTerms = isset($_POST['agree_terms']);
        
        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $error = 'Username must be between 3 and 50 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = 'Username can only contain letters, numbers, and underscores.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (!$agreeTerms) {
            $error = 'You must agree to the Terms of Service.';
        } else {
            // Check if username exists
            $existingUser = db()->fetch("SELECT id FROM users WHERE username = ?", [$username]);
            if ($existingUser) {
                $error = 'Username is already taken.';
            } else {
                // Check if email exists
                $existingEmail = db()->fetch("SELECT id FROM users WHERE email = ?", [$email]);
                if ($existingEmail) {
                    $error = 'Email is already registered.';
                } else {
                    // Create user
                    try {
                        db()->beginTransaction();
                        
                        $userId = db()->insert('users', [
                            'username' => $username,
                            'email' => $email,
                            'password' => hashPassword($password),
                            'role' => 'buyer',
                            'status' => 'active'
                        ]);
                        
                        // Create wallet
                        db()->insert('wallets', ['user_id' => $userId]);
                        
                        db()->commit();
                        
                        // Auto login
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['user_role'] = 'buyer';
                        $_SESSION['username'] = $username;
                        
                        redirectWithMessage(BASE_URL, 'Welcome to ' . PLATFORM_NAME . '! Your account has been created.', 'success');
                        
                    } catch (Exception $e) {
                        db()->rollback();
                        error_log("Registration error: " . $e->getMessage());
                        $error = 'An error occurred. Please try again.';
                    }
                }
            }
        }
    }
}

$pageTitle = 'Register - ' . PLATFORM_NAME;
?>
<!DOCTYPE html>
<html lang="<?= lang()->getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        body {
            display: flex;
            flex-direction: row;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .auth-left {
            width: 50%;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }
        .auth-left::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(14, 165, 233, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 50%);
        }
        .auth-left-content {
            position: relative;
            z-index: 1;
            max-width: 400px;
            text-align: center;
        }
        .auth-logo { margin-bottom: 40px; }
        .auth-logo img { height: 60px; }
        .auth-logo-text {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0ea5e9, #14b8a6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .auth-left h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 20px;
        }
        .auth-left p {
            color: rgba(255,255,255,0.7);
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 40px;
        }
        .auth-features { display: flex; flex-direction: column; gap: 20px; }
        .auth-feature { display: flex; align-items: center; gap: 15px; text-align: left; }
        .auth-feature-icon {
            width: 50px; height: 50px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(14, 165, 233, 0.2);
            border-radius: 12px;
            color: #38bdf8;
            font-size: 1.25rem;
        }
        .auth-feature-text { color: rgba(255,255,255,0.9); font-weight: 500; }
        .auth-right {
            width: 50%;
            min-height: 100vh;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 50px;
            overflow-y: auto;
        }
        .auth-form-header { margin-bottom: 30px; }
        .auth-form-header h2 { font-size: 1.75rem; font-weight: 800; color: #1e293b; margin-bottom: 8px; }
        .auth-form-header p { color: #64748b; font-size: 0.95rem; }
        .auth-input-group { margin-bottom: 20px; }
        .auth-input-group label { display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 0.9rem; }
        .auth-input-group small { display: block; color: #94a3b8; font-size: 0.8rem; margin-top: 4px; }
        .auth-input-wrapper { position: relative; }
        .auth-input-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .auth-input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        .auth-input:focus { outline: none; border-color: #0ea5e9; background: #fff; box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1); }
        .auth-row { display: flex; gap: 15px; }
        .auth-row .auth-input-group { flex: 1; }
        .auth-checkbox { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 24px; }
        .auth-checkbox input { width: 18px; height: 18px; margin-top: 2px; accent-color: #0ea5e9; }
        .auth-checkbox label { color: #64748b; font-size: 0.85rem; line-height: 1.5; }
        .auth-checkbox a { color: #0ea5e9; text-decoration: none; }
        .auth-checkbox a:hover { text-decoration: underline; }
        .auth-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .auth-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(14, 165, 233, 0.4); }
        .auth-divider { display: flex; align-items: center; margin: 25px 0; color: #94a3b8; font-size: 0.85rem; }
        .auth-divider::before, .auth-divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
        .auth-divider span { padding: 0 15px; }
        .auth-footer { text-align: center; color: #64748b; font-size: 0.95rem; }
        .auth-footer a { color: #0ea5e9; text-decoration: none; font-weight: 600; }
        .auth-footer a:hover { text-decoration: underline; }
        .auth-back {
            position: absolute; top: 30px; left: 30px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            display: flex; align-items: center; gap: 8px;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        .auth-back:hover { color: #fff; }
        .auth-alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
        }
        .auth-alert.error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        @media (max-width: 991px) {
            .auth-left { display: none; }
            .auth-right { width: 100%; max-width: 500px; margin: 0 auto; min-height: auto; }
            body { background: #f8fafc; padding: 20px; flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="auth-left">
        <a href="<?= BASE_URL ?>" class="auth-back">
            <i class="fas fa-arrow-left"></i> <?= __('back_to_home') ?? 'Back to Home' ?>
        </a>
        <div class="auth-left-content">
            <div class="auth-logo">
                <img src="<?= BASE_URL ?>/logo.png" alt="<?= PLATFORM_NAME ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <span class="auth-logo-text" style="display:none;"><?= PLATFORM_NAME ?></span>
            </div>
            <h1><?= __('join_us') ?? 'Join Our Marketplace' ?></h1>
            <p><?= __('register_description') ?? 'Create an account to start buying or selling digital products on our secure platform.' ?></p>
            <div class="auth-features">
                <div class="auth-feature">
                    <div class="auth-feature-icon"><i class="fas fa-gift"></i></div>
                    <span class="auth-feature-text"><?= __('free_to_join') ?? 'Free to Join' ?></span>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon"><i class="fas fa-store"></i></div>
                    <span class="auth-feature-text"><?= __('become_seller') ?? 'Become a Seller' ?></span>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon"><i class="fas fa-headset"></i></div>
                    <span class="auth-feature-text"><?= __('support_24_7') ?? '24/7 Support' ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="auth-right">
        <div class="auth-form-header">
            <h2><?= __('create_account') ?? 'Create Account' ?></h2>
            <p><?= __('fill_details') ?? 'Fill in your details to get started' ?></p>
        </div>
        
        <?php if ($error): ?>
            <div class="auth-alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?= sanitize($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?= csrfField() ?>
            
            <div class="auth-input-group">
                <label><?= __('username') ?></label>
                <div class="auth-input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" class="auth-input" name="username" 
                           value="<?= sanitize($_POST['username'] ?? '') ?>" 
                           placeholder="<?= __('choose_username') ?? 'Choose a username' ?>" required
                           pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="50">
                </div>
                <small><?= __('username_hint') ?? 'Letters, numbers, and underscores only' ?></small>
            </div>
            
            <div class="auth-input-group">
                <label><?= __('email') ?></label>
                <div class="auth-input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" class="auth-input" name="email" 
                           value="<?= sanitize($_POST['email'] ?? '') ?>" 
                           placeholder="<?= __('enter_email') ?? 'Enter your email' ?>" required>
                </div>
            </div>
            
            <div class="auth-row">
                <div class="auth-input-group">
                    <label><?= __('password') ?></label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="auth-input" name="password" 
                               placeholder="<?= __('create_password') ?? 'Create password' ?>" required minlength="6">
                    </div>
                </div>
                <div class="auth-input-group">
                    <label><?= __('confirm_password') ?></label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="auth-input" name="confirm_password" 
                               placeholder="<?= __('confirm') ?? 'Confirm' ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="auth-checkbox">
                <input type="checkbox" name="agree_terms" id="agree_terms" required>
                <label for="agree_terms">
                    <?= __('agree_to') ?? 'I agree to the' ?> 
                    <a href="<?= BASE_URL ?>/terms.php" target="_blank"><?= __('terms_of_service') ?? 'Terms of Service' ?></a>
                    <?= __('and') ?? 'and' ?> 
                    <a href="<?= BASE_URL ?>/privacy.php" target="_blank"><?= __('privacy_policy') ?? 'Privacy Policy' ?></a>
                </label>
            </div>
            
            <button type="submit" class="auth-btn">
                <i class="fas fa-user-plus"></i>
                <?= __('create_account') ?? 'Create Account' ?>
            </button>
        </form>
        
        <div class="auth-divider"><span><?= __('or') ?? 'or' ?></span></div>
        
        <p class="auth-footer">
            <?= __('already_have_account') ?? 'Already have an account?' ?> 
            <a href="<?= BASE_URL ?>/auth/login.php"><?= __('sign_in') ?? 'Sign In' ?></a>
        </p>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
