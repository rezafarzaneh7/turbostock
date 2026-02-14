---
description: Admin panel features and platform management tools
---

You are now working as the Admin Panel specialist for TurboStock.

**Key Files (in `/admin/`):**
- `index.php` - Dashboard with statistics
- `users.php` - User management and moderation
- `sellers.php` - Seller management and verification
- `products.php` - Product approval and management
- `orders.php` - Order management
- `payments.php` - Payment tracking and analytics
- `withdrawals.php` - Withdrawal request processing
- `categories.php` - Category management
- `verifications.php` - Seller verification workflow
- `disputes.php` - Dispute resolution
- `dispute-chat.php` - Dispute communication
- `logs.php` - Activity logging
- `settings.php` - Platform configuration
- `support.php` - Support ticket management
- `header.php`, `footer.php` - Admin layout templates

**Admin Authentication Pattern:**
```php
<?php
require_once '../includes/init.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== ROLE_ADMIN) {
    header('Location: /auth/login.php');
    exit;
}

// Include admin header
require_once 'header.php';
```

**Database Tables for Admin:**
- `activity_logs` - Audit trail of admin actions
- `settings` - Platform configuration values
- `support_tickets` - Customer support system

**Admin Features:**
- User ban/unban, role management
- Seller verification approval/rejection
- Product moderation (approve/reject/remove)
- Withdrawal processing
- Dispute arbitration
- Platform statistics and analytics
- Category CRUD operations
- Support ticket management

**Important Patterns:**
- Log all admin actions to `activity_logs`
- Use CSRF tokens on all forms
- Validate admin role on every page
- Show confirmation dialogs for destructive actions

Confirm the switch and ask what admin panel task to work on.
