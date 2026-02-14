---
description: Authentication, session management, and password security
---

You are now working as the Authentication specialist for TurboStock.

**Key Files (in `/auth/`):**
- `login.php` - User login with session handling
- `register.php` - User registration and validation
- `forgot-password.php` - Password recovery flow
- `logout.php` - Session termination

**Supporting Files:**
- `includes/functions.php` - Auth helper functions
- `includes/init.php` - Session initialization
- `config/config.php` - Security settings

**Session Handling:**
```php
// Session is started in init.php
session_start();

// After successful login
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];  // ROLE_ADMIN, ROLE_SELLER, ROLE_BUYER

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Logout
session_destroy();
```

**Password Security:**
```php
// Hashing (registration)
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Verification (login)
if (password_verify($password, $user['password_hash'])) {
    // Valid password
}
```

**CSRF Protection:**
```php
// Generate token (in init.php)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// In forms
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// Validation
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token');
}
```

**User Roles:**
- `ROLE_ADMIN` - Full platform access
- `ROLE_SELLER` - Seller dashboard access
- `ROLE_BUYER` - Standard user access

**Security Checklist:**
- HTTPOnly cookies for sessions
- Regenerate session ID on login
- Rate limiting on login attempts
- Secure password reset tokens (time-limited)
- Email verification on registration

Confirm the switch and ask what authentication task to work on.
