<?php
/**
 * Demostore - Main Configuration File
 */

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS

// Timezone (Pakistan Standard Time)
date_default_timezone_set('UTC');

// Base Paths
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('UPLOADS_PATH', BASE_PATH . '/uploads');

// Base URL (adjust for your environment)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $protocol . '://' . $host);
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');

// Database Configuration
$db_host = getenv('DB_HOST');
define('DB_HOST', $db_host ? $db_host : 'mysql');
define('DB_NAME', 'testshop');
define('DB_USER', 'testshop');
define('DB_PASS', '1p7.b=5rK$p!7]d7');
define('DB_CHARSET', 'utf8mb4');

// Security
define('ENCRYPTION_KEY', 'your-32-character-secret-key-here!'); // Change this!
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_COST', 12);

// NOWPayments Configuration (Multi-Crypto)
define('NOWPAYMENTS_API_KEY', '723EWET-S3NMP5G-QHW50QE-DJANYJY');
define('NOWPAYMENTS_IPN_SECRET', 'SZPDMeHBV40SWiTttvtGZqkn8YUBEysT');

// TRON Configuration (Legacy - kept for backward compatibility)
define('TRON_NETWORK', 'mainnet'); // mainnet or testnet
define('TRON_API_URL', TRON_NETWORK === 'mainnet' 
    ? 'https://api.trongrid.io' 
    : 'https://api.shasta.trongrid.io');
define('TRON_SCAN_URL', TRON_NETWORK === 'mainnet'
    ? 'https://tronscan.org'
    : 'https://shasta.tronscan.org');
define('USDT_CONTRACT_ADDRESS', 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'); // Mainnet USDT TRC20
define('TRON_API_KEY', '8651a590-8348-4f21-afd5-ed8fe52dddd5'); // Get from trongrid.io
define('QUICKNODE_TRON_URL', 'https://crimson-attentive-grass.tron-mainnet.quiknode.pro/6e901dbbb321671c03a91700ae56ff6ee1ee7e44/jsonrpc');

// Platform Settings
define('PLATFORM_NAME', 'Demostore');
define('PLATFORM_EMAIL', 'support@hstore.local');
define('DEFAULT_COMMISSION_RATE', 10); // 10%
define('VERIFICATION_FEE', 100); // 100 USDT
define('MIN_WITHDRAWAL', 100); // 100 USDT
define('PAYMENT_EXPIRY_HOURS', 24);

// Pagination
define('ITEMS_PER_PAGE', 12);
define('ADMIN_ITEMS_PER_PAGE', 20);

// File Upload
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_FILE_TYPES', ['application/pdf', 'application/zip', 'text/plain']);

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_SELLER', 'seller');
define('ROLE_BUYER', 'buyer');

// Order Status
define('ORDER_PENDING', 'pending');
define('ORDER_PROCESSING', 'processing');
define('ORDER_COMPLETED', 'completed');
define('ORDER_CANCELLED', 'cancelled');
define('ORDER_REFUNDED', 'refunded');
define('ORDER_DISPUTED', 'disputed');

// Verification Status
define('VERIFICATION_UNVERIFIED', 'unverified');
define('VERIFICATION_PENDING', 'pending');
define('VERIFICATION_VERIFIED', 'verified');
