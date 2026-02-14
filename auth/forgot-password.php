<?php
/**
 * HStore - Forgot Password Page
 */
require_once __DIR__ . '/../includes/init.php';

if (isLoggedIn()) {
    redirect(BASE_URL);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = clean($_POST['email'] ?? '');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $user = db()->fetch("SELECT id, username FROM users WHERE email = ?", [$email]);
            
            // Always show success to prevent email enumeration
            $success = 'If an account exists with this email, you will receive password reset instructions.';
            
            if ($user) {
                // In production, send email with reset link
                // For now, just log it
                error_log("Password reset requested for user: " . $user['username']);
            }
        }
    }
}

$pageTitle = 'Forgot Password - ' . PLATFORM_NAME;
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
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(14, 165, 233, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 50%);
        }
        .forgot-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 460px;
            padding: 20px;
        }
        .forgot-card {
            background: #fff;
            border-radius: 24px;
            padding: 50px 40px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
        }
        .forgot-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            border-radius: 50%;
            color: #fff;
            font-size: 2rem;
        }
        .forgot-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .forgot-header h2 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 10px;
        }
        .forgot-header p {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .forgot-input-group {
            margin-bottom: 24px;
        }
        .forgot-input-group label {
            display: block;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        .forgot-input-wrapper {
            position: relative;
        }
        .forgot-input-wrapper i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
        }
        .forgot-input {
            width: 100%;
            padding: 16px 18px 16px 52px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        .forgot-input:focus {
            outline: none;
            border-color: #0ea5e9;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
        }
        .forgot-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            border: none;
            border-radius: 14px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 24px;
        }
        .forgot-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.4);
        }
        .forgot-footer {
            text-align: center;
            color: #64748b;
            font-size: 0.95rem;
        }
        .forgot-footer a {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 600;
        }
        .forgot-footer a:hover {
            text-decoration: underline;
        }
        .forgot-alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
        }
        .forgot-alert.error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .forgot-alert.success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 30px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        .back-link:hover {
            color: #fff;
        }
        .back-link i {
            margin-right: 6px;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-card">
            <div class="forgot-icon">
                <i class="fas fa-key"></i>
            </div>
            
            <div class="forgot-header">
                <h2><?= __('forgot_password') ?? 'Forgot Password?' ?></h2>
                <p><?= __('forgot_password_desc') ?? 'No worries! Enter your email address and we\'ll send you instructions to reset your password.' ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="forgot-alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= sanitize($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="forgot-alert success">
                    <i class="fas fa-check-circle"></i>
                    <?= sanitize($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?= csrfField() ?>
                
                <div class="forgot-input-group">
                    <label><?= __('email') ?? 'Email Address' ?></label>
                    <div class="forgot-input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" class="forgot-input" name="email" 
                               placeholder="<?= __('enter_email') ?? 'Enter your email address' ?>" required>
                    </div>
                </div>
                
                <button type="submit" class="forgot-btn">
                    <i class="fas fa-paper-plane"></i>
                    <?= __('send_reset_link') ?? 'Send Reset Link' ?>
                </button>
                
                <p class="forgot-footer">
                    <?= __('remember_password') ?? 'Remember your password?' ?> 
                    <a href="<?= BASE_URL ?>/auth/login.php"><?= __('sign_in') ?? 'Sign In' ?></a>
                </p>
            </form>
        </div>
        
        <a href="<?= BASE_URL ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> <?= __('back_to_home') ?? 'Back to Home' ?>
        </a>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
