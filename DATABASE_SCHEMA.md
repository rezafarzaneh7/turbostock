# TurboStock Database Schema Documentation

## Database Overview
TurboStock uses a comprehensive MySQL/MariaDB database with 23 interconnected tables supporting a complete digital marketplace ecosystem. The schema handles user management, product sales, cryptocurrency payments, order processing, and administrative functions.

## Entity Relationship Summary

```
Users System:
users (1) ↔ (1) wallets
users (1) ↔ (0..1) sellers
users (1) ↔ (*) sessions
users (1) ↔ (*) notifications

Product System:
categories (1) ↔ (*) products
sellers (1) ↔ (*) products
products (1) ↔ (*) product_reviews

Order System:
users (buyer) → orders ← products ← sellers
orders (1) ↔ (*) order_messages
orders (1) ↔ (0..1) disputes

Financial System:
wallets (1) ↔ (*) wallet_transactions
users (1) ↔ (*) crypto_payments
users (1) ↔ (*) tron_payments
users (1) ↔ (*) withdrawal_requests

Administrative:
users (admins) (1) ↔ (*) admin_logs
support_tickets (1) ↔ (*) ticket_messages
```

## Detailed Table Specifications

### 1. User Management Tables

#### **users** - Core user accounts
```sql
CREATE TABLE users (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username varchar(50) UNIQUE NOT NULL,
    email varchar(100) UNIQUE NOT NULL,
    password varchar(255) NOT NULL,
    role enum('admin','seller','buyer') DEFAULT 'buyer',
    status enum('active','suspended','banned') DEFAULT 'active',
    avatar varchar(255),
    email_verified_at timestamp NULL,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login timestamp NULL
);
```

#### **sessions** - User session management
```sql
CREATE TABLE sessions (
    id varchar(128) PRIMARY KEY,
    user_id int UNSIGNED,
    ip_address varchar(45),
    user_agent text,
    payload longtext,
    last_activity int,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### **notifications** - System notifications
```sql
CREATE TABLE notifications (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id int UNSIGNED NOT NULL,
    type varchar(50) NOT NULL,
    title varchar(255) NOT NULL,
    message text,
    link varchar(255),
    is_read tinyint(1) DEFAULT 0,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 2. Seller Management System

#### **sellers** - Seller profiles and shop information
```sql
CREATE TABLE sellers (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id int UNSIGNED UNIQUE NOT NULL,
    shop_name varchar(100) NOT NULL,
    shop_slug varchar(100) UNIQUE NOT NULL,
    shop_description text,
    shop_logo varchar(255),
    shop_banner varchar(255),
    verification_status enum('unverified','pending','verified') DEFAULT 'unverified',
    verified_at timestamp NULL,
    total_sales decimal(18,6) DEFAULT 0.000000,
    total_earnings decimal(18,6) DEFAULT 0.000000,
    rating_average decimal(2,1) DEFAULT 0.0,
    rating_count int DEFAULT 0,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### **seller_verifications** - Seller verification via cryptocurrency
```sql
CREATE TABLE seller_verifications (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id int UNSIGNED NOT NULL,
    tron_wallet_address varchar(100) NOT NULL,
    amount_required decimal(18,6) NOT NULL,
    amount_received decimal(18,6) DEFAULT 0.000000,
    tx_hash varchar(100),
    sender_wallet varchar(100),
    status enum('pending','confirmed','failed','expired','manual_approved','manual_rejected') DEFAULT 'pending',
    admin_notes text,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    confirmed_at timestamp NULL,
    expires_at timestamp NULL,
    FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE
);
```

#### **seller_reviews** - Seller rating and feedback
```sql
CREATE TABLE seller_reviews (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id int UNSIGNED NOT NULL,
    buyer_id int UNSIGNED NOT NULL,
    order_id int UNSIGNED UNIQUE NOT NULL,
    rating tinyint CHECK (rating >= 1 AND rating <= 5),
    comment text,
    status enum('active','hidden','deleted') DEFAULT 'active',
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
```

### 3. Product Management System

#### **categories** - Hierarchical product categories
```sql
CREATE TABLE categories (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name varchar(100) NOT NULL,
    slug varchar(100) UNIQUE NOT NULL,
    description text,
    icon varchar(50),
    parent_id int UNSIGNED,
    sort_order int DEFAULT 0,
    status enum('active','inactive') DEFAULT 'active',
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);
```

#### **products** - Digital product catalog
```sql
CREATE TABLE products (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id int UNSIGNED NOT NULL,
    category_id int UNSIGNED NOT NULL,
    name varchar(255) NOT NULL,
    slug varchar(255) NOT NULL,
    description text,
    short_description varchar(500),
    price decimal(18,6) NOT NULL,
    stock_quantity int DEFAULT -1, -- -1 for unlimited
    delivery_type enum('auto','manual') DEFAULT 'auto',
    delivery_data text, -- Auto-delivery content
    thumbnail varchar(255),
    images json,
    status enum('active','inactive','pending','rejected') DEFAULT 'pending',
    total_sales int DEFAULT 0,
    rating_average decimal(2,1) DEFAULT 0.0,
    rating_count int DEFAULT 0,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_status_category (status, category_id),
    INDEX idx_seller_status (seller_id, status)
);
```

#### **product_reviews** - Product ratings and reviews
```sql
CREATE TABLE product_reviews (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id int UNSIGNED NOT NULL,
    buyer_id int UNSIGNED NOT NULL,
    order_id int UNSIGNED NOT NULL,
    rating tinyint CHECK (rating >= 1 AND rating <= 5),
    comment text,
    status enum('active','hidden','deleted') DEFAULT 'active',
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    UNIQUE KEY unique_product_order (product_id, order_id)
);
```

### 4. Order Management System

#### **orders** - Complete order lifecycle management
```sql
CREATE TABLE orders (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number varchar(50) UNIQUE NOT NULL,
    buyer_id int UNSIGNED NOT NULL,
    seller_id int UNSIGNED NOT NULL,
    product_id int UNSIGNED NOT NULL,
    quantity int UNSIGNED NOT NULL,
    unit_price decimal(18,6) NOT NULL,
    total_amount decimal(18,6) NOT NULL,
    commission_rate decimal(5,2) NOT NULL,
    commission_amount decimal(18,6) NOT NULL,
    seller_amount decimal(18,6) NOT NULL,
    delivery_type enum('auto','manual') DEFAULT 'auto',
    delivery_data text,
    status enum('pending','processing','delivered','completed','cancelled','refunded','disputed') DEFAULT 'pending',
    buyer_reviewed tinyint(1) DEFAULT 0,
    seller_reviewed tinyint(1) DEFAULT 0,
    payment_released tinyint(1) DEFAULT 0,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    completed_at timestamp NULL,
    delivered_at timestamp NULL,
    payment_released_at timestamp NULL,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_buyer_status (buyer_id, status),
    INDEX idx_seller_status (seller_id, status),
    INDEX idx_status_created (status, created_at)
);
```

#### **order_messages** - Order-related communication
```sql
CREATE TABLE order_messages (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id int UNSIGNED NOT NULL,
    sender_id int UNSIGNED NOT NULL,
    message text NOT NULL,
    attachment varchar(255),
    attachment_name varchar(255),
    is_read tinyint(1) DEFAULT 0,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### **disputes** - Order dispute management
```sql
CREATE TABLE disputes (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id int UNSIGNED NOT NULL,
    initiated_by int UNSIGNED NOT NULL,
    reason varchar(255) NOT NULL,
    description text,
    status enum('open','under_review','resolved_buyer','resolved_seller','closed') DEFAULT 'open',
    admin_notes text,
    resolved_by int UNSIGNED,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    resolved_at timestamp NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (initiated_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### **frozen_payments** - Payment holding during disputes
```sql
CREATE TABLE frozen_payments (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id int UNSIGNED NOT NULL,
    order_id int UNSIGNED,
    amount decimal(18,6) NOT NULL,
    reason varchar(255),
    status enum('frozen','released','refunded') DEFAULT 'frozen',
    frozen_at timestamp DEFAULT CURRENT_TIMESTAMP,
    release_at timestamp NULL,
    released_at timestamp NULL,
    FOREIGN KEY (seller_id) REFERENCES sellers(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);
```

### 5. Financial System

#### **wallets** - User wallet management
```sql
CREATE TABLE wallets (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id int UNSIGNED UNIQUE NOT NULL,
    balance decimal(18,6) DEFAULT 0.000000,
    pending_balance decimal(18,6) DEFAULT 0.000000,
    total_deposited decimal(18,6) DEFAULT 0.000000,
    total_withdrawn decimal(18,6) DEFAULT 0.000000,
    total_spent decimal(18,6) DEFAULT 0.000000,
    total_earned decimal(18,6) DEFAULT 0.000000,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### **wallet_transactions** - Complete transaction history
```sql
CREATE TABLE wallet_transactions (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wallet_id int UNSIGNED NOT NULL,
    type enum('deposit','withdrawal','purchase','sale','refund','commission','verification_fee') NOT NULL,
    amount decimal(18,6) NOT NULL,
    balance_before decimal(18,6) NOT NULL,
    balance_after decimal(18,6) NOT NULL,
    reference_type varchar(50),
    reference_id int UNSIGNED,
    description varchar(255),
    status enum('pending','completed','failed','cancelled') DEFAULT 'completed',
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    INDEX idx_wallet_type_created (wallet_id, type, created_at),
    INDEX idx_reference (reference_type, reference_id)
);
```

#### **withdrawal_requests** - User withdrawal processing
```sql
CREATE TABLE withdrawal_requests (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id int UNSIGNED NOT NULL,
    amount decimal(18,6) NOT NULL,
    wallet_address varchar(100) NOT NULL,
    tx_hash varchar(100),
    status enum('pending','processing','completed','rejected') DEFAULT 'pending',
    admin_notes text,
    processed_by int UNSIGNED,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    processed_at timestamp NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_status_created (status, created_at)
);
```

### 6. Payment Processing System

#### **crypto_payments** - NOWPayments integration
```sql
CREATE TABLE crypto_payments (
    id int AUTO_INCREMENT PRIMARY KEY,
    user_id int NOT NULL,
    payment_id varchar(100), -- NOWPayments payment ID
    invoice_id varchar(100), -- NOWPayments invoice ID
    order_id varchar(100) NOT NULL, -- Internal order ID
    payment_type enum('deposit','verification','other') DEFAULT 'deposit',
    price_amount decimal(18,6) NOT NULL, -- Amount in USD
    price_currency varchar(10) DEFAULT 'usd',
    pay_amount decimal(18,8) NOT NULL, -- Amount in cryptocurrency
    pay_currency varchar(20) NOT NULL, -- Cryptocurrency code
    actually_paid decimal(18,8) DEFAULT 0.00000000,
    pay_address varchar(255), -- Payment address
    status varchar(50) NOT NULL,
    invoice_url varchar(500), -- Payment page URL
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at timestamp NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_payment_id (payment_id),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status)
);
```

#### **tron_payments** - Direct TRON blockchain integration
```sql
CREATE TABLE tron_payments (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id int UNSIGNED NOT NULL,
    wallet_address varchar(100) NOT NULL,
    private_key_encrypted text NOT NULL,
    payment_type enum('deposit','verification','withdrawal') NOT NULL,
    expected_amount decimal(18,6) NOT NULL,
    received_amount decimal(18,6) DEFAULT 0.000000,
    tx_hash varchar(100),
    sender_wallet varchar(100),
    confirmations int DEFAULT 0,
    status enum('pending','confirming','confirmed','failed','expired') DEFAULT 'pending',
    reference_type varchar(50),
    reference_id int UNSIGNED,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    confirmed_at timestamp NULL,
    expires_at timestamp NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_wallet_address (wallet_address),
    INDEX idx_status (status),
    INDEX idx_reference (reference_type, reference_id)
);
```

### 7. Communication System

#### **support_tickets** - Customer support system
```sql
CREATE TABLE support_tickets (
    id int AUTO_INCREMENT PRIMARY KEY,
    ticket_number varchar(20) UNIQUE NOT NULL,
    user_id int NOT NULL,
    subject varchar(255) NOT NULL,
    category enum('general','payment','order','technical','seller','report','other') DEFAULT 'general',
    priority enum('low','medium','high','urgent') DEFAULT 'medium',
    status enum('open','in_progress','waiting','resolved','closed') DEFAULT 'open',
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at timestamp NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_status_priority (status, priority),
    INDEX idx_user_status (user_id, status)
);
```

#### **ticket_messages** - Support ticket communication
```sql
CREATE TABLE ticket_messages (
    id int AUTO_INCREMENT PRIMARY KEY,
    ticket_id int NOT NULL,
    user_id int NOT NULL,
    message text NOT NULL,
    is_admin tinyint(1) DEFAULT 0,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id),
    INDEX idx_ticket_created (ticket_id, created_at)
);
```

### 8. Administrative System

#### **admin_logs** - Administrative action tracking
```sql
CREATE TABLE admin_logs (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id int UNSIGNED NOT NULL,
    action varchar(100) NOT NULL,
    target_type varchar(50),
    target_id int UNSIGNED,
    old_value longtext CHECK (json_valid(old_value)),
    new_value longtext CHECK (json_valid(new_value)),
    ip_address varchar(45),
    user_agent varchar(255),
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_admin_action_created (admin_id, action, created_at),
    INDEX idx_target (target_type, target_id)
);
```

#### **settings** - System configuration
```sql
CREATE TABLE settings (
    id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key varchar(100) UNIQUE NOT NULL,
    setting_value text,
    setting_type enum('string','number','boolean','json') DEFAULT 'string',
    description varchar(255),
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 9. Support Tables

#### **esim_countries** - Country support for eSIM services
```sql
CREATE TABLE esim_countries (
    id int AUTO_INCREMENT PRIMARY KEY,
    name varchar(100) NOT NULL,
    code varchar(10) NOT NULL,
    status enum('active','inactive') DEFAULT 'active',
    sort_order int DEFAULT 0,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP
);
```

## Key Database Features

### Performance Optimizations
- **Strategic Indexing**: All foreign keys, status fields, and frequently queried columns are indexed
- **Composite Indexes**: Multi-column indexes for complex queries (status + date, user + status)
- **Query Optimization**: Structured for efficient JOINs and subqueries

### Data Integrity
- **Foreign Key Constraints**: Maintain referential integrity with appropriate CASCADE/SET NULL actions
- **Unique Constraints**: Prevent data duplication (usernames, emails, order numbers, slugs)
- **Check Constraints**: Ensure valid rating ranges and enum values
- **JSON Validation**: Validate JSON structure in applicable fields

### Financial Security
- **Decimal Precision**: Use decimal(18,6) for all monetary values to prevent floating-point errors
- **Balance Snapshots**: Transaction history includes before/after balance states
- **Payment Freezing**: Ability to hold seller payments during disputes
- **Audit Trail**: Complete transaction history with references to source records

### Scalability Considerations
- **Partitioning Ready**: Large tables (transactions, logs) can be partitioned by date
- **Read Replicas**: Foreign key structure supports read-only replicas
- **Archive Strategy**: Old orders and transactions can be archived while maintaining references
- **Caching Layer**: Structure optimized for Redis/Memcached integration

### Security Features
- **Encrypted Storage**: Private keys stored with encryption
- **Session Management**: Secure session handling with IP/user agent tracking
- **Admin Auditing**: Complete log of administrative actions with before/after states
- **Status Controls**: Multiple status fields for granular access control

## Common Query Patterns

### Financial Reporting
```sql
-- User's transaction history
SELECT wt.*, w.user_id
FROM wallet_transactions wt
JOIN wallets w ON wt.wallet_id = w.id
WHERE w.user_id = ?
ORDER BY wt.created_at DESC;

-- Seller earnings summary
SELECT s.shop_name, w.total_earned, s.total_sales
FROM sellers s
JOIN users u ON s.user_id = u.id
JOIN wallets w ON u.id = w.user_id
WHERE s.verification_status = 'verified';
```

### Order Management
```sql
-- Order details with all related info
SELECT o.*, p.name as product_name, s.shop_name, u.username as buyer_name
FROM orders o
JOIN products p ON o.product_id = p.id
JOIN sellers s ON o.seller_id = s.id
JOIN users u ON o.buyer_id = u.id
WHERE o.order_number = ?;

-- Active disputes requiring attention
SELECT d.*, o.order_number, u.username as initiator
FROM disputes d
JOIN orders o ON d.order_id = o.id
JOIN users u ON d.initiated_by = u.id
WHERE d.status IN ('open', 'under_review');
```

### Product Analytics
```sql
-- Best selling products
SELECT p.name, p.total_sales, AVG(pr.rating) as avg_rating
FROM products p
LEFT JOIN product_reviews pr ON p.id = pr.product_id
WHERE p.status = 'active'
GROUP BY p.id
ORDER BY p.total_sales DESC;

-- Category performance
SELECT c.name, COUNT(p.id) as product_count, SUM(p.total_sales) as total_sales
FROM categories c
LEFT JOIN products p ON c.id = p.category_id
WHERE c.status = 'active'
GROUP BY c.id;
```

This database schema provides a robust foundation for a comprehensive digital marketplace with strong financial controls, audit capabilities, and scalability features.