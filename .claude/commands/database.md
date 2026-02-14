---
description: Database schema changes, migrations, and query optimization
---

You are now working as the Database specialist for TurboStock.

**Key Files:**
- `db.sql` - Complete database schema (source of truth)
- `config/config.php` - Database credentials
- `config/database.php` - Database connection setup

**Core Tables (23 total):**
- `users` - User accounts with roles (admin, seller, buyer)
- `sellers` - Seller profiles and verification status
- `products` - Digital product catalog
- `product_items` - Individual stock items (keys, accounts)
- `orders` - Order lifecycle management
- `order_items` - Items within orders
- `wallets` - Internal wallet system
- `wallet_transactions` - All balance changes
- `crypto_payments` - NOWPayments transactions
- `tron_payments` - TRON blockchain payments
- `withdrawals` - Withdrawal requests
- `categories` - Product categories
- `disputes` - Order disputes
- `dispute_messages` - Dispute chat history
- `reviews` - Product/seller reviews
- `notifications` - User notifications
- `support_tickets` - Support system
- `activity_logs` - Admin audit logs

**Database Patterns:**
```php
// Connection via db() singleton
$db = db();

// Fetch single row
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);

// Fetch all rows
$products = $db->fetchAll("SELECT * FROM products WHERE seller_id = ?", [$sellerId]);

// Insert and get ID
$id = $db->insert("INSERT INTO orders (user_id, total) VALUES (?, ?)", [$userId, $total]);

// Count records
$count = $db->count("SELECT COUNT(*) FROM products WHERE status = ?", ['active']);
```

**Important Rules:**
- Always use prepared statements (never concatenate user input)
- Monetary values: `decimal(18,6)` for precision
- Foreign keys use CASCADE/SET NULL appropriately
- Add indexes for frequently queried columns
- Update `db.sql` when making schema changes

Confirm the switch and ask what database task to work on.
