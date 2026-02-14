# TurboStock Development Guide

## Development Environment Setup

### Prerequisites
- PHP 7.4+ with extensions: mysqli, curl, json, mbstring, openssl
- MySQL 5.7+ or MariaDB 10.3+
- Apache 2.4+ with mod_rewrite enabled
- Composer for dependency management
- Git for version control

### Local Setup
```bash
# Clone repository
git clone [repository-url]
cd turbostock

# Install dependencies
composer install

# Import database
mysql -u root -p < db.sql

# Configure environment
cp config/config.sample.php config/config.php
# Edit config/config.php with your settings

# Set permissions
chmod 755 uploads/
chmod 755 uploads/products/
chmod 755 uploads/avatars/
chmod 755 uploads/shops/

# Start local server
php -S localhost:8000
```

## Coding Standards

### PHP Guidelines
```php
// File header template
<?php
/**
 * File: filename.php
 * Purpose: Brief description
 * Author: Developer Name
 * Date: YYYY-MM-DD
 */

// Include required files
require_once 'config/config.php';
require_once 'includes/init.php';

// Use meaningful variable names
$user_id = $_SESSION['user_id'] ?? 0;
$product_data = [];

// Function naming convention
function getUserProducts($userId, $status = 'active') {
    // Implementation
}

// Class naming (PascalCase)
class ProductManager {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }
}

// Constants (UPPERCASE)
define('MAX_UPLOAD_SIZE', 5242880); // 5MB

// SQL queries with prepared statements
$stmt = $db->prepare("SELECT * FROM products WHERE seller_id = ? AND status = ?");
$stmt->bind_param("is", $seller_id, $status);
$stmt->execute();
```

### HTML/CSS Standards
```html
<!-- Semantic HTML5 -->
<article class="product-card">
    <header class="product-header">
        <h3 class="product-title">Product Name</h3>
    </header>
    <div class="product-content">
        <!-- Content -->
    </div>
</article>

<!-- CSS class naming (BEM-like) -->
<div class="product-list">
    <div class="product-list__item">
        <div class="product-list__item-title"></div>
    </div>
</div>
```

### JavaScript Standards
```javascript
// Modern ES6+ syntax preferred
const initializeProduct = (productId) => {
    // Use const/let, avoid var
    const productData = {
        id: productId,
        quantity: 1
    };

    // Async/await for promises
    const loadProduct = async () => {
        try {
            const response = await fetch(`/api/product/${productId}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error loading product:', error);
        }
    };
};

// Event delegation
document.addEventListener('click', (e) => {
    if (e.target.matches('.add-to-cart')) {
        // Handle cart addition
    }
});
```

## Database Conventions

### Table Naming
- Use lowercase with underscores: `user_products`, `order_items`
- Singular for entities: `user`, `product`, `order`
- Junction tables: `user_roles`, `product_categories`

### Column Naming
```sql
-- Primary keys
id INT AUTO_INCREMENT PRIMARY KEY

-- Foreign keys
user_id INT,
product_id INT,
FOREIGN KEY (user_id) REFERENCES users(id)

-- Timestamps
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

-- Status fields
status ENUM('active', 'inactive', 'deleted'),
is_verified TINYINT(1) DEFAULT 0
```

### Query Optimization
```php
// Use indexes effectively
$query = "SELECT p.*, c.name as category_name
          FROM products p
          JOIN categories c ON p.category_id = c.id
          WHERE p.status = 'active'
          AND p.seller_id = ?
          ORDER BY p.created_at DESC
          LIMIT 20";

// Avoid N+1 queries
// Bad: Multiple queries in loop
foreach ($products as $product) {
    $seller = getSellerById($product['seller_id']);
}

// Good: Single query with JOIN
$query = "SELECT p.*, s.username as seller_name
          FROM products p
          JOIN sellers s ON p.seller_id = s.id";
```

## Git Workflow

### Branch Strategy
```bash
main/               # Production-ready code
├── develop/        # Development branch
├── feature/        # New features (feature/user-auth)
├── bugfix/         # Bug fixes (bugfix/payment-issue)
├── hotfix/         # Urgent fixes (hotfix/security-patch)
└── release/        # Release preparation (release/v2.0)
```

### Commit Messages
```bash
# Format: <type>(<scope>): <subject>

feat(payments): add Bitcoin payment support
fix(auth): resolve session timeout issue
docs(api): update endpoint documentation
style(dashboard): improve responsive layout
refactor(database): optimize product queries
test(orders): add unit tests for order processing
chore(deps): update composer dependencies
```

### Pull Request Template
```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Code follows project standards
- [ ] Self-review completed
- [ ] Tests pass locally
- [ ] No console errors

## Screenshots (if applicable)
[Add screenshots]
```

## Error Handling

### PHP Error Handling
```php
// Custom error handler
function handleError($errno, $errstr, $errfile, $errline) {
    $error = [
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline,
        'time' => date('Y-m-d H:i:s')
    ];

    // Log to file
    error_log(json_encode($error), 3, 'logs/errors.log');

    // Display user-friendly message
    if (!DEBUG_MODE) {
        include 'includes/error_page.php';
        exit;
    }
}

set_error_handler('handleError');

// Try-catch for exceptions
try {
    $result = processPayment($order_id);
} catch (PaymentException $e) {
    logError($e->getMessage());
    showError('Payment processing failed. Please try again.');
} catch (Exception $e) {
    logError($e->getMessage());
    showError('An unexpected error occurred.');
}
```

### JavaScript Error Handling
```javascript
// Global error handler
window.addEventListener('error', (e) => {
    console.error('Global error:', e.error);
    // Send to logging service
    logError({
        message: e.error.message,
        stack: e.error.stack,
        url: window.location.href
    });
});

// Promise rejection handler
window.addEventListener('unhandledrejection', (e) => {
    console.error('Unhandled promise rejection:', e.reason);
});
```

## Testing Guidelines

### Unit Testing Structure
```php
// tests/ProductTest.php
class ProductTest extends TestCase {
    public function testProductCreation() {
        $product = new Product();
        $product->setTitle('Test Product');
        $product->setPrice(99.99);

        $this->assertEquals('Test Product', $product->getTitle());
        $this->assertEquals(99.99, $product->getPrice());
    }
}
```

### Integration Testing
```php
// Test API endpoints
public function testProductApiEndpoint() {
    $response = $this->get('/api/products/1');
    $response->assertStatus(200);
    $response->assertJson(['id' => 1]);
}
```

## Performance Optimization

### Database Optimization
```php
// Use query caching
$cache_key = 'featured_products_' . $category_id;
if ($cached = getCache($cache_key)) {
    return $cached;
}

$products = fetchFeaturedProducts($category_id);
setCache($cache_key, $products, 3600); // Cache for 1 hour
return $products;
```

### Frontend Optimization
```javascript
// Lazy load images
const lazyImages = document.querySelectorAll('img[data-src]');
const imageObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            imageObserver.unobserve(img);
        }
    });
});

lazyImages.forEach(img => imageObserver.observe(img));
```

## Security Best Practices

### Input Validation
```php
// Sanitize user input
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);

// Validate before processing
if (!$email) {
    throw new ValidationException('Invalid email address');
}
```

### SQL Injection Prevention
```php
// Always use prepared statements
$stmt = $db->prepare("INSERT INTO products (title, price, seller_id) VALUES (?, ?, ?)");
$stmt->bind_param("sdi", $title, $price, $seller_id);
$stmt->execute();
```

### XSS Prevention
```php
// Escape output
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// For JavaScript context
echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
```

## Debugging Tools

### PHP Debugging
```php
// Development helper functions
function dd($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    die();
}

function log_debug($message, $data = []) {
    if (DEBUG_MODE) {
        error_log(date('[Y-m-d H:i:s] ') . $message . ' ' . json_encode($data), 3, 'logs/debug.log');
    }
}
```

### Browser Debugging
```javascript
// Console utilities
const debug = {
    log: (message, data) => {
        if (window.DEBUG_MODE) {
            console.log(`[DEBUG] ${message}`, data);
        }
    },
    table: (data) => {
        if (window.DEBUG_MODE) {
            console.table(data);
        }
    },
    time: (label) => {
        if (window.DEBUG_MODE) {
            console.time(label);
        }
    },
    timeEnd: (label) => {
        if (window.DEBUG_MODE) {
            console.timeEnd(label);
        }
    }
};
```

## Documentation Standards

### Code Documentation
```php
/**
 * Process a product order
 *
 * @param int $product_id Product ID to order
 * @param int $quantity Quantity to order
 * @param array $options Additional options
 * @return array Order result with status and order_id
 * @throws OrderException If order cannot be processed
 */
function processOrder($product_id, $quantity = 1, $options = []) {
    // Implementation
}
```

### API Documentation
```yaml
# GET /api/products/{id}
# Retrieve product details
# Parameters:
#   - id: Product ID (required)
# Response:
#   200 OK: Product data
#   404 Not Found: Product not found
# Example:
#   GET /api/products/123
#   Response: {"id": 123, "title": "Product Name", ...}
```

## Deployment Checklist

### Pre-deployment
- [ ] All tests passing
- [ ] Code review completed
- [ ] Documentation updated
- [ ] Database migrations prepared
- [ ] Environment variables configured
- [ ] Error logging configured
- [ ] SSL certificate valid

### Post-deployment
- [ ] Verify critical paths work
- [ ] Check payment processing
- [ ] Monitor error logs
- [ ] Test email notifications
- [ ] Verify cron jobs running
- [ ] Check performance metrics
- [ ] Backup verification

---

*Last Updated: January 2025*
*For questions: Contact CTO/Development Team*