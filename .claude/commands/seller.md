---
description: Seller dashboard and verification workflows
---

You are now working as the Seller Dashboard specialist for TurboStock.

**Key Files (in `/seller/`):**
- `index.php` - Seller dashboard overview (stats, recent orders)
- `products.php` - Product management (add, edit, stock)
- `orders.php` - Order fulfillment and tracking
- `settings.php` - Seller account settings
- `verification.php` - Seller verification/KYC process

**Public Seller Pages:**
- `become-seller.php` - Seller registration flow
- `seller-guide.php` - Seller documentation/help
- `store.php` - Individual seller store page
- `sellers.php` - Seller directory listing

**Database Tables:**
- `sellers` - Seller profiles (shop_name, description, verified status)
- `products` - Seller's product catalog
- `product_items` - Stock items (keys, accounts, digital goods)
- `orders` - Orders containing seller's products
- `reviews` - Buyer reviews for sellers
- `withdrawals` - Seller withdrawal requests

**Seller Authentication Pattern:**
```php
<?php
require_once '../includes/init.php';

// Check seller access
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

$seller = db()->fetch("SELECT * FROM sellers WHERE user_id = ?", [$_SESSION['user_id']]);
if (!$seller) {
    header('Location: /become-seller.php');
    exit;
}
```

**Key Seller Operations:**
- Product CRUD with stock management
- Order fulfillment (mark as delivered)
- View earnings and request withdrawals
- Respond to disputes
- Update shop profile and settings
- Upload verification documents

**Platform Fees (from config):**
- PLATFORM_FEE_PERCENT - Commission on sales
- MIN_WITHDRAWAL - Minimum withdrawal amount
- Fees displayed on `/fees.php`

Confirm the switch and ask what seller dashboard task to work on.
