# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

TurboStock is a PHP-based digital marketplace for selling digital products (gaming accounts, software licenses, digital keys) with multi-cryptocurrency payment support (NOWPayments multi-crypto + direct TRON TRX/USDT). Internal branding varies across files ("Demostore", "HStore", "TurboStock").

**Tech Stack:** PHP 8.2 (Docker) / PHP 8.0+ (bare metal, uses `match` expressions), MySQL 8.0, Vanilla JS, no framework — spaghetti PHP architecture with mixed HTML/CSS/PHP in page files.

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

### Bootstrap Chain (`includes/init.php`)

1. `config/config.php` — all constants (DB creds, API keys, platform settings)
2. `config/database.php` — PDO singleton `Database` class
3. `includes/functions.php` — global helpers (auth, sanitization, pagination, file upload)
4. `includes/TronAPI.php`, `PaymentHandler.php`, `NowPaymentsAPI.php`, `Language.php`
5. Starts session, generates CSRF token
6. Runs background payment check (10% probability per page load, max once/minute)

### Database Layer — PDO (NOT mysqli)

The `Database` class in `config/database.php` is a PDO singleton accessed via the `db()` helper:

```php
$user = db()->fetch("SELECT * FROM users WHERE id = ?", [$id]);
$rows = db()->fetchAll("SELECT * FROM products WHERE status = ?", ['active']);
$newId = db()->insert('products', ['title' => $title, 'price' => $price]);  // table + assoc array
db()->update('users', ['name' => $name], 'id = ?', [$userId]);  // table, data, where, params
db()->delete('orders', 'id = ?', [$orderId]);
$count = db()->count('products', 'seller_id = ?', [$sellerId]);

// Transactions
db()->beginTransaction();
db()->commit();
db()->rollback();
```

**IMPORTANT:** `db()->insert()` takes `($table, $dataArray)` — NOT a SQL string. Same for `update()` and `delete()`.

### Key Singletons & Helpers

| Function | Returns | Source |
|---|---|---|
| `db()` | `Database` instance (PDO) | `config/database.php` |
| `paymentHandler()` | `PaymentHandler` | `includes/PaymentHandler.php` |
| `tronApi()` | `TronAPI` | `includes/TronAPI.php` |
| `nowPayments()` | `NowPaymentsAPI` | `includes/NowPaymentsAPI.php` |
| `lang()` / `__('key')` | `Language` / translated string | `includes/Language.php` |

### Auth & Session

Session-based auth. Session variables set on login:
- `$_SESSION['user_id']` — user ID
- `$_SESSION['user_role']` — role string (`admin`, `seller`, `buyer`)

Key functions in `includes/functions.php`:
- `isLoggedIn()`, `getCurrentUserId()`, `getCurrentUser()`
- `hasRole($role)`, `isAdmin()`, `isSeller()`, `isBuyer()`
- `requireLogin()`, `requireAdmin()`, `requireSeller()` — redirect if unauthorized
- Role constants: `ROLE_ADMIN`, `ROLE_SELLER`, `ROLE_BUYER`

### CSRF Protection

Forms: include `<?= csrfField() ?>`. Validation: `verifyCSRFToken($_POST['csrf_token'])`.

### Flash Messages

`redirectWithMessage($url, $message, $type)` sets session flash. `displayFlashMessage()` renders and clears it. Types: `success`, `error`, `warning`, `info`.

### API Endpoints (`api/`)

Return JSON via `jsonSuccess($data)`, `jsonError($message)`, or `jsonResponse($data, $code)`. POST endpoints read body via `json_decode(file_get_contents('php://input'), true)`.

Files: `chat.php`, `check-deposit.php`, `check-payment.php`, `mark-notification-read.php`, `nowpayments-ipn.php`

### URL Routing

SEO-friendly URLs via `.htaccess` (Apache) / `nginx.conf` (Docker):
- `/product/{id}` → `product.php?id=`
- `/store/{id}` → `store.php?id=`
- `/category/{slug}` → `browse.php?category=`
- `/order/{id}` → `orders/view.php?id=`
- `/seller/order/{id}` → `seller/orders.php?view=`

URL helpers: `productUrl($id)`, `storeUrl($id)`, `categoryUrl($slug)`, `orderUrl($id)`, `sellerOrderUrl($id)`

### Page Template Pattern

Pages set variables before including header/footer:
```php
$pageTitle = 'My Page - TurboStock';
$bodyClass = 'page-name';
$pageDescription = 'SEO description';  // optional
$extraCss = '<link ...>';              // optional
require_once 'includes/header.php';
// ... page content ...
require_once 'includes/footer.php';
```

### CSS Architecture

- `assets/css/style-new-design.css` — **active stylesheet** (loaded by header.php)
- `assets/css/style.css` — original design (legacy)
- `assets/css/style-new.css`, `style-backup.css` — older iterations
- Theme support: `theme-light` / `theme-dark` classes on `<body>`, toggled via JS with `localStorage`

### i18n

Three languages: `lang/en.php`, `lang/ru.php`, `lang/cn.php`. Use `__('translation_key')` in templates. Language detected from `?lang=` param → cookie → session → default English.

### Directory Roles

- `admin/` — Admin panel pages (own `header.php`/`footer.php` layout)
- `seller/` — Seller dashboard (products, orders, settings, verification)
- `auth/` — Login, register, forgot-password, logout
- `api/` — AJAX endpoints (chat, payments, notifications)
- `includes/` — Core PHP classes and shared templates (header/footer)
- `config/` — `config.php` (constants/credentials) + `database.php` (PDO singleton)
- `new-design/` — HTML mockups for UI redesign (`tasarim4-light-*.html`, reference only)
- `uploads/` — User-uploaded files (products/, avatars/, shops/)
- `assets/` — Static files (css/, js/, images/)

### Order Lifecycle

Two delivery types with different flows:

- **Auto delivery:** Order created → immediately completed. Each line in `products.delivery_data` is one stock item; lines are consumed per-order and removed. Seller credited instantly.
- **Manual delivery:** Order created (processing) → seller delivers (delivered) → buyer releases payment (completed). Seller funds held in `wallets.pending_balance` until buyer confirms via `PaymentHandler::releasePayment()`.

Order statuses: `pending`, `processing`, `delivered`, `completed`, `cancelled`, `refunded`, `disputed`

### Payment Flow

1. User creates deposit → `api/create-deposit.php` → `PaymentHandler::createDepositRequest()`
2. Generates a TRON wallet (via `TronAPI::generateWallet()`) or NOWPayments invoice
3. `cron/check-payments.php` polls for confirmations (also runs probabilistically on page loads via `init.php`)
4. On confirmation → credits user wallet → wallet balance used for purchases
5. NOWPayments IPN webhook: `api/nowpayments-ipn.php`

### Config Constants (`config/config.php`)

Platform settings as PHP constants: `PLATFORM_NAME`, `DEFAULT_COMMISSION_RATE` (10%), `VERIFICATION_FEE` (100 USDT), `MIN_WITHDRAWAL` (100 USDT), `PAYMENT_EXPIRY_HOURS` (24), `ITEMS_PER_PAGE` (12), `ADMIN_ITEMS_PER_PAGE` (20), `MAX_FILE_SIZE` (5MB). Runtime settings stored in `settings` DB table, accessed via `getSetting('key', $default)`.

### Database Notes

All monetary values use `decimal(18,6)`. Core tables: `users`, `sellers`, `products`, `orders`, `wallets`, `wallet_transactions`, `tron_payments`, `crypto_payments`, `categories`, `disputes`, `dispute_messages`, `reviews`, `notifications`, `settings`, `admin_logs`, `support_tickets`, `seller_verifications`, `withdrawals`. Foreign keys with CASCADE/SET NULL.

### Slash Commands (`.claude/commands/`)

Available via `/command`: `/frontend` (UI redesign agent), `/backend` (legacy PHP agent), `/payment`, `/database`, `/api`, `/admin`, `/seller`, `/auth`, `/cron`, `/security`.

### Redesign Team (`.claude/agents/`)

Three specialized agents for the new-design implementation:

| Agent | Role | When to Use |
|---|---|---|
| `@design-to-page` | Converts mockup HTML → PHP pages | Page HAS a matching `new-design/tasarim4-light-*.html` mockup |
| `@design-adapter` | Creates consistent designs for unmocked pages | Page has NO mockup — uses design system patterns generically |
| `@design-reviewer` | QA review for design consistency & PHP correctness | After any page is redesigned, to verify quality |

**Redesign strategy:**
- Pages WITH mockups (11): Apply exact HTML from `new-design/`, inject PHP logic
- Pages WITHOUT mockups (~30): Use the design system (CSS vars, component patterns) to create matching UI
- Don't add features not in the original PHP page or mockup
- All CSS scoped with `body.page-{name}` prefix in `assets/css/style-new-design.css`

**Design Mockup Coverage:**

| Mockup | PHP Target | body class |
|---|---|---|
| `tasarim4-light-turbostock.html` | `index.php` | `page-turbostock-source` |
| `tasarim4-light-product-detail.html` | `product.php` | `page-product-detail` |
| `tasarim4-light-wallet.html` | `wallet.php` | `page-wallet` |
| `tasarim4-light-my-orders.html` | `orders.php` | `page-my-orders` |
| `tasarim4-light-login.html` | `auth/login.php` | `page-login` |
| `tasarim4-light-register.html` | `auth/register.php` | `page-register` |
| `tasarim4-light-my-profile.html` | `profile.php` | `page-my-profile` |
| `tasarim4-light-category.html` | `browse.php` | `page-category` |
| `tasarim4-light-seller-detail.html` | `store.php` | `page-seller-detail` |
| `tasarim4-light-become-seller.html` | `become-seller.php` | `page-become-seller` |
| `tasarim4-light-order-details.html` | `orders/view.php` | `page-order-details` |

## No Automated Tests

No test suite exists. All testing is manual.
