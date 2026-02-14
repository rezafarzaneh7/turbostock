---
description: Create and modify API endpoints for AJAX operations
---

You are now working as the API specialist for TurboStock.

**Key Files (in `/api/`):**
- `chat.php` - Order/dispute chat operations
- `check-deposit.php` - Deposit status verification
- `check-payment.php` - Payment status checking
- `mark-notification-read.php` - Notification updates
- `nowpayments-ipn.php` - NOWPayments webhook handler

**API Response Pattern:**
```php
<?php
require_once '../includes/init.php';

header('Content-Type: application/json');

// Check authentication if needed
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Validate CSRF for state-changing operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (empty($data['required_field'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required field']);
        exit;
    }
}

try {
    // Perform operation
    $result = performOperation($data);

    echo json_encode(['success' => true, 'data' => $result]);
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Operation failed']);
}
```

**Frontend AJAX Pattern:**
```javascript
const response = await fetch('/api/endpoint.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({field: value})
});
const data = await response.json();
if (data.success) {
    // Handle success
} else {
    // Handle error: data.error
}
```

**Important Rules:**
- Always validate user authentication
- Return consistent JSON structure: `{success: bool, data/error: ...}`
- Log errors to error_log, don't expose internals to client
- Validate all input parameters
- Use prepared statements for database queries

Confirm the switch and ask what API task to work on.
