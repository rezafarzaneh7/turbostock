# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

TurboStock is a PHP-based digital marketplace for selling digital products (gaming accounts, software licenses, digital keys) with multi-cryptocurrency payment support (NOWPayments multi-crypto + direct TRON TRX/USDT).

**Tech Stack:** PHP 8.2 (Docker) / PHP 7.4+ (bare metal), MySQL 8.0, Vanilla JS, no framework — spaghetti PHP architecture with mixed HTML/CSS/PHP in page files.

## Commands

```bash
# Docker (recommended) — serves on http://localhost:8080
docker-compose up -d

# Without Docker (Apache required for URL rewrites)
composer install
php -S localhost:8000    # Note: SEO-friendly URLs won't work without Apache mod_rewrite

# Database import (auto-imported by Docker via db.sql)
mysql -u root -p testshop < db.sql

# Payment cron (every 5 minutes)
php cron/check-payments.php
```

Docker stack: nginx (port 8080) → php-fpm (8.2) → mysql (port 3307 externally). DB auto-seeds from `db.sql` on first run.

## Architecture

No MVC framework. Each PHP file is a standalone page that mixes logic and presentation. Every public page begins with `require_once 'includes/init.php'` which loads config, database, functions, payment handlers, and language system, then starts the session.

### Database Layer — PDO (NOT mysqli)

The `Database` class in `config/database.php` is a PDO singleton accessed via the `db()` helper:

```php
// Correct patterns — use these:
$user = db()->fetch("SELECT * FROM users WHERE id = ?", [$id]);
$rows = db()->fetchAll("SELECT * FROM products WHERE status = ?", ['active']);
$newId = db()->insert('products', ['title' => $title, 'price' => $price]);
db()->update('users', ['name' => $name], 'id = ?', [$userId]);
db()->delete('orders', 'id = ?', [$orderId]);
$count = db()->count('products', 'seller_id = ?', [$sellerId]);

// Transactions
db()->beginTransaction();
db()->commit();
db()->rollback();
```

### Key Singletons & Helpers

| Function | Returns | Source |
|---|---|---|
| `db()` | `Database` instance (PDO) | `config/database.php` |
| `paymentHandler()` | `PaymentHandler` | `includes/PaymentHandler.php` |
| `tronApi()` | `TronAPI` | `includes/TronAPI.php` |
| `nowPayments()` | `NowPaymentsAPI` | `includes/NowPaymentsAPI.php` |
| `lang()` / `__('key')` | `Language` / translated string | `includes/Language.php` |

### Auth & Access Control

Session-based. Key functions in `includes/functions.php`:
- `isLoggedIn()`, `getCurrentUserId()`, `getCurrentUser()`
- `hasRole($role)`, `isAdmin()`, `isSeller()`, `isBuyer()`
- `requireLogin()`, `requireAdmin()`, `requireSeller()` — redirect if unauthorized
- Roles: `admin`, `seller`, `buyer` (constants: `ROLE_ADMIN`, `ROLE_SELLER`, `ROLE_BUYER`)

### CSRF Protection

Forms: include `<?= csrfField() ?>`. Validation: `verifyCSRFToken($_POST['csrf_token'])`.

### Flash Messages

`redirectWithMessage($url, $message, $type)` sets session flash. `displayFlashMessage()` renders and clears it.

### API Endpoints (`api/`)

Return JSON via `jsonSuccess($data)`, `jsonError($message)`, or `jsonResponse($data, $code)`. Used by frontend `fetch()` calls.

### URL Routing

SEO-friendly URLs via `.htaccess` (Apache) / `nginx.conf` (Docker):
- `/product/123` → `product.php?id=123`
- `/store/123` → `store.php?id=123`
- `/category/slug` → `browse.php?category=slug`
- `/order/123` → `orders/view.php?id=123`

URL helpers: `productUrl($id)`, `storeUrl($id)`, `categoryUrl($slug)`, `orderUrl($id)`

### i18n

Three languages: `lang/en.php`, `lang/ru.php`, `lang/cn.php`. Use `__('translation_key')` in templates. Language detected from `?lang=` param → cookie → session → default English.

### Directory Roles

- `admin/` — Admin panel pages (own `header.php`/`footer.php` layout)
- `seller/` — Seller dashboard (products, orders, settings, verification)
- `auth/` — Login, register, forgot-password, logout
- `api/` — AJAX endpoints (chat, payments, notifications)
- `includes/` — Core PHP classes and shared templates (header/footer)
- `config/` — `config.php` (constants/credentials) + `database.php` (PDO singleton)
- `new-design/` — HTML mockups for UI redesign (reference only, not live)
- `uploads/` — User-uploaded files (products, avatars, shops)

### Order Lifecycle

Two delivery types with different flows:

- **Auto delivery:** Order created → immediately completed. Each line in `products.delivery_data` is one stock item; lines are consumed per-order and removed. Seller credited instantly.
- **Manual delivery:** Order created (processing) → seller delivers (delivered) → buyer releases payment (completed). Seller funds held in `wallets.pending_balance` until buyer confirms via `PaymentHandler::releasePayment()`.

### Payment Flow

1. User creates deposit → `api/create-deposit.php` → `PaymentHandler::createDepositRequest()`
2. Generates a TRON wallet or NOWPayments invoice
3. `cron/check-payments.php` polls for confirmations (also runs probabilistically on page loads via `init.php`)
4. On confirmation → credits user wallet → wallet balance used for purchases

### Config Constants (`config/config.php`)

All platform settings are PHP constants: `PLATFORM_NAME`, `DEFAULT_COMMISSION_RATE` (10%), `VERIFICATION_FEE` (100 USDT), `MIN_WITHDRAWAL` (100 USDT), `PAYMENT_EXPIRY_HOURS` (24), `ITEMS_PER_PAGE` (12). Runtime settings stored in `settings` DB table, accessed via `getSetting('key')`.

### Database Notes

All monetary values use `decimal(18,6)`. Core tables: `users` (role-based: admin/seller/buyer), `sellers` (verification system), `products`, `orders`, `wallets`, `wallet_transactions`, `tron_payments`, `crypto_payments`, `disputes`, `notifications`, `settings`, `admin_logs`. Foreign keys with CASCADE/SET NULL.

## No Automated Tests

No test suite exists. All testing is manual.
