<?php
/**
 * HStore - Login Page
 */
require_once __DIR__ . '/../includes/init.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter your email and password.';
        } else {
            // Find user
            $user = db()->fetch(
                "SELECT * FROM users WHERE email = ? OR username = ?",
                [$email, $email]
            );
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Check if user is active
                if ($user['status'] !== 'active') {
                    $error = 'Your account has been ' . $user['status'] . '. Please contact support.';
                } else {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['username'] = $user['username'];
                    
                    // Update last login
                    db()->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
                    
                    // Redirect based on role or saved URL
                    $redirectUrl = $_SESSION['redirect_after_login'] ?? null;
                    unset($_SESSION['redirect_after_login']);
                    
                    if ($redirectUrl) {
                        redirect($redirectUrl);
                    } elseif ($user['role'] === 'admin') {
                        redirect(BASE_URL . '/admin/');
                    } elseif ($user['role'] === 'seller') {
                        redirect(BASE_URL . '/seller/');
                    } else {
                        redirect(BASE_URL);
                    }
                }
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}

$pageTitle = 'Login - ' . PLATFORM_NAME;
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
        .auth-logo {
            margin-bottom: 40px;
        }
        .auth-logo img {
            height: 60px;
        }
        .auth-logo-text {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0ea5e9, #14b8a6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .auth-left h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        .auth-left p {
            color: rgba(255,255,255,0.7);
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 40px;
        }
        .auth-features {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .auth-feature {
            display: flex;
            align-items: center;
            gap: 15px;
            text-align: left;
        }
        .auth-feature-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(14, 165, 233, 0.2);
            border-radius: 12px;
            color: #38bdf8;
            font-size: 1.25rem;
        }
        .auth-feature-text {
            color: rgba(255,255,255,0.9);
            font-weight: 500;
        }
        .auth-right {
            width: 50%;
            min-height: 100vh;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
        }
        .auth-form-header {
            margin-bottom: 40px;
        }
        .auth-form-header h2 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 8px;
        }
        .auth-form-header p {
            color: #64748b;
            font-size: 0.95rem;
        }
        .auth-input-group {
            margin-bottom: 24px;
        }
        .auth-input-group label {
            display: block;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        .auth-input-wrapper {
            position: relative;
        }
        .auth-input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
        }
        .auth-input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        .auth-input:focus {
            outline: none;
            border-color: #0ea5e9;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
        }
        .auth-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .auth-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .auth-checkbox input {
            width: 18px;
            height: 18px;
            accent-color: #0ea5e9;
        }
        .auth-checkbox span {
            color: #64748b;
            font-size: 0.9rem;
        }
        .auth-forgot {
            color: #0ea5e9;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .auth-forgot:hover {
            color: #0284c7;
            text-decoration: underline;
        }
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
        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.4);
        }
        .auth-divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: #94a3b8;
            font-size: 0.85rem;
        }
        .auth-divider::before, .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }
        .auth-divider span {
            padding: 0 15px;
        }
        .auth-footer {
            text-align: center;
            color: #64748b;
            font-size: 0.95rem;
        }
        .auth-footer a {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 600;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }
        .auth-back {
            position: absolute;
            top: 30px;
            left: 30px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        .auth-back:hover {
            color: #fff;
        }
        .auth-alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
        }
        .auth-alert.error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .auth-alert.success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        @media (max-width: 991px) {
            .auth-left { display: none; }
            .auth-right { width: 100%; max-width: 480px; margin: 0 auto; min-height: auto; }
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
            <h1><?= __('welcome_back') ?? 'Welcome Back!' ?></h1>
            <p><?= __('login_description') ?? 'Sign in to access your account, manage orders, and continue shopping on our secure marketplace.' ?></p>
            <div class="auth-features">
                <div class="auth-feature">
                    <div class="auth-feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <span class="auth-feature-text"><?= __('secure_payments') ?? 'Secure Crypto Payments' ?></span>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon"><i class="fas fa-bolt"></i></div>
                    <span class="auth-feature-text"><?= __('instant_delivery') ?? 'Instant Digital Delivery' ?></span>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon"><i class="fas fa-user-check"></i></div>
                    <span class="auth-feature-text"><?= __('verified_sellers') ?? 'Verified Sellers' ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="auth-right">
        <div class="auth-form-header">
            <h2><?= __('sign_in') ?? 'Sign In' ?></h2>
            <p><?= __('enter_credentials') ?? 'Enter your credentials to access your account' ?></p>
        </div>
        
        <?php if ($error): ?>
            <div class="auth-alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?= sanitize($error) ?>
            </div>
        <?php endif; ?>
        
        <?php displayFlashMessage(); ?>
        
        <form method="POST" action="">
            <?= csrfField() ?>
            
            <div class="auth-input-group">
                <label><?= __('email') ?> / <?= __('username') ?></label>
                <div class="auth-input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" class="auth-input" name="email" 
                           value="<?= sanitize($_POST['email'] ?? '') ?>" 
                           placeholder="<?= __('enter_email_username') ?? 'Enter your email or username' ?>" required>
                </div>
            </div>
            
            <div class="auth-input-group">
                <label><?= __('password') ?></label>
                <div class="auth-input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="auth-input" name="password" 
                           placeholder="<?= __('enter_password') ?? 'Enter your password' ?>" required>
                </div>
            </div>
            
            <div class="auth-options">
                <label class="auth-checkbox">
                    <input type="checkbox" name="remember">
                    <span><?= __('remember_me') ?? 'Remember me' ?></span>
                </label>
                <a href="<?= BASE_URL ?>/auth/forgot-password.php" class="auth-forgot">
                    <?= __('forgot_password') ?? 'Forgot password?' ?>
                </a>
            </div>
            
            <button type="submit" class="auth-btn">
                <i class="fas fa-sign-in-alt"></i>
                <?= __('sign_in') ?? 'Sign In' ?>
            </button>
        </form>
        
        <div class="auth-divider"><span><?= __('or') ?? 'or' ?></span></div>
        
        <p class="auth-footer">
            <?= __('dont_have_account') ?? "Don't have an account?" ?> 
            <a href="<?= BASE_URL ?>/auth/register.php"><?= __('create_account') ?? 'Create Account' ?></a>
        </p>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
