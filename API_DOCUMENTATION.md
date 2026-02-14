# TurboStock API Documentation

## API Overview
TurboStock exposes a RESTful API for real-time communication, payment processing, and notification management. All API endpoints require authentication and return JSON responses.

## Authentication
All API endpoints require a valid user session. Users must be logged in via the web interface before making API calls.

### Authentication Headers
```
Cookie: PHPSESSID=[session_id]
```

### Response Format
All endpoints return JSON with consistent structure:

```json
// Success Response
{
    "success": true,
    "data": {...},
    "message": "Operation completed"
}

// Error Response
{
    "error": "Error description",
    "code": 400
}
```

## API Endpoints

### 1. Chat API
**Endpoint**: `/api/chat.php`
**Purpose**: Real-time messaging for order communication between buyers, sellers, and admins

#### Get Messages
```http
GET /api/chat.php?action=get&order_id={order_id}&last_id={last_message_id}
```

**Parameters:**
- `order_id` (int, required): Order ID to get messages for
- `last_id` (int, optional): Get only messages after this ID for polling

**Authorization:**
- Buyer: Can access their order messages
- Seller: Can access their order messages
- Admin: Can access all order messages

**Response:**
```json
{
    "success": true,
    "messages": [
        {
            "id": 123,
            "sender": "username",
            "role": "buyer|seller|admin",
            "message": "Message content",
            "attachment": "file.jpg",
            "attachment_name": "Original filename.jpg",
            "is_mine": true,
            "time": "2 hours ago"
        }
    ],
    "last_id": 125
}
```

#### Send Message
```http
POST /api/chat.php
Content-Type: application/x-www-form-urlencoded

action=send&order_id={order_id}&message={message_content}
```

**Parameters:**
- `action` (string): "send"
- `order_id` (int, required): Order ID to send message to
- `message` (string, required): Message content (max length enforced by DB)

**Authorization:** Same as Get Messages

**Response:**
```json
{
    "success": true,
    "message_id": 456
}
```

**Features:**
- Auto-marks messages as read when retrieved
- Admin messages are prefixed with `[ADMIN]`
- Automatic notifications to recipients
- File attachment support (future enhancement)

---

### 2. Payment Status Check API
**Endpoint**: `/api/check-payment.php`
**Purpose**: Check TRON blockchain payment confirmation status

```http
GET /api/check-payment.php?id={payment_id}
```

**Parameters:**
- `id` (int, required): TRON payment ID from `tron_payments` table

**Authorization:** User can only check their own payments

**Response:**
```json
// Payment Confirmed
{
    "confirmed": true,
    "tx_hash": "0x1234567890abcdef...",
    "amount": "10.500000"
}

// Payment Pending
{
    "confirmed": false,
    "status": "pending"
}

// Payment Failed/Expired
{
    "confirmed": false,
    "status": "expired|failed"
}
```

**Implementation Details:**
- Calls `paymentHandler()->checkPayment()` to verify on blockchain
- Updates payment status in database when confirmed
- Handles expired/failed payments gracefully

---

### 3. Deposit Status Check API
**Endpoint**: `/api/check-deposit.php`
**Purpose**: Check deposit confirmation for both NOWPayments and TRON payments

```http
GET /api/check-deposit.php?payment_id={payment_id}
```

**Parameters:**
- `payment_id` (string, required): Payment ID (NOWPayments ID or TRON payment ID)

**Authorization:** User can only check their own deposits

**Response:**
```json
// Deposit Confirmed
{
    "status": "confirmed",
    "message": "Deposit confirmed!",
    "amount": "25.50"
}

// Deposit Pending
{
    "status": "pending",
    "message": "Waiting for payment..."
}

// Deposit Confirming
{
    "status": "confirming",
    "message": "Payment received, confirming on blockchain..."
}

// Deposit Expired
{
    "status": "expired",
    "message": "Payment expired"
}

// Deposit Failed
{
    "status": "failed",
    "message": "Payment failed"
}
```

**Payment Gateway Support:**
- **NOWPayments**: Multi-cryptocurrency deposits
- **TRON**: Direct TRX/USDT deposits
- Automatic fallback to legacy TRON system

**Status Flow:**
1. `pending` → Payment awaiting confirmation
2. `confirming` → Payment detected, awaiting blockchain confirmations
3. `confirmed` → Payment completed, wallet credited
4. `expired` → Payment window expired
5. `failed` → Payment processing failed

---

### 4. Notification Management API
**Endpoint**: `/api/mark-notification-read.php`
**Purpose**: Mark user notifications as read

```http
GET /api/mark-notification-read.php?id={notification_id}
```

**Parameters:**
- `id` (int, required): Notification ID from `notifications` table

**Authorization:** User can only mark their own notifications

**Response:**
```json
// Success
{
    "success": true,
    "data": [],
    "message": "Notification marked as read"
}

// Error - Not Found
{
    "error": "Notification not found",
    "code": 404
}
```

---

### 5. NOWPayments Webhook (IPN)
**Endpoint**: `/api/nowpayments-ipn.php`
**Purpose**: Handle NOWPayments instant payment notifications

```http
POST /api/nowpayments-ipn.php
Content-Type: application/json
X-NOWPayments-Sig: {signature}

{
    "payment_id": "123456789",
    "payment_status": "finished",
    "order_id": "deposit_user123_timestamp",
    "price_amount": 25.00,
    "price_currency": "usd",
    "pay_amount": 0.00045,
    "pay_currency": "btc",
    "actually_paid": 0.00045
}
```

**Webhook Verification:**
- Validates signature using NOWPayments secret
- Logs all requests for debugging and audit

**Processing Logic:**
1. **Signature Verification**: Validates HMAC signature
2. **Payment Lookup**: Finds payment record in `crypto_payments` table
3. **Status Update**: Updates payment status in database
4. **Balance Credit**: For confirmed payments, credits user wallet
5. **Notification**: Sends notification to user
6. **Transaction Log**: Records transaction in `wallet_transactions`

**Payment Status Handling:**
- `finished/confirmed` → Credit user balance
- `partially_paid` → Notify user of partial payment
- `expired` → Mark as expired
- `failed` → Mark as failed

**Security Features:**
- HMAC signature validation
- Idempotency (prevents double-processing)
- Comprehensive logging
- Rate limiting (via web server)

---

## Integration Examples

### JavaScript Frontend Integration

#### Real-time Chat
```javascript
class OrderChat {
    constructor(orderId) {
        this.orderId = orderId;
        this.lastMessageId = 0;
        this.polling = null;
    }

    // Start polling for new messages
    startPolling() {
        this.polling = setInterval(() => {
            this.fetchMessages();
        }, 2000);
    }

    // Fetch messages since last check
    async fetchMessages() {
        try {
            const response = await fetch(`/api/chat.php?action=get&order_id=${this.orderId}&last_id=${this.lastMessageId}`);
            const data = await response.json();

            if (data.success && data.messages.length > 0) {
                this.displayMessages(data.messages);
                this.lastMessageId = data.last_id;
            }
        } catch (error) {
            console.error('Failed to fetch messages:', error);
        }
    }

    // Send new message
    async sendMessage(message) {
        const formData = new FormData();
        formData.append('action', 'send');
        formData.append('order_id', this.orderId);
        formData.append('message', message);

        try {
            const response = await fetch('/api/chat.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                this.fetchMessages(); // Refresh to show new message
            }
        } catch (error) {
            console.error('Failed to send message:', error);
        }
    }
}

// Usage
const chat = new OrderChat(123);
chat.startPolling();
```

#### Payment Status Monitoring
```javascript
class PaymentMonitor {
    constructor(paymentId, type = 'crypto') {
        this.paymentId = paymentId;
        this.type = type; // 'crypto' or 'tron'
        this.checking = false;
    }

    async checkStatus() {
        if (this.checking) return;
        this.checking = true;

        try {
            const endpoint = this.type === 'tron'
                ? `/api/check-payment.php?id=${this.paymentId}`
                : `/api/check-deposit.php?payment_id=${this.paymentId}`;

            const response = await fetch(endpoint);
            const data = await response.json();

            if (data.confirmed || data.status === 'confirmed') {
                this.onConfirmed(data);
                return true;
            } else if (data.status === 'expired' || data.status === 'failed') {
                this.onFailed(data);
                return false;
            } else {
                this.onPending(data);
                return null; // Continue checking
            }
        } catch (error) {
            console.error('Payment check failed:', error);
            return null;
        } finally {
            this.checking = false;
        }
    }

    // Start periodic monitoring
    startMonitoring(intervalMs = 5000) {
        const interval = setInterval(async () => {
            const result = await this.checkStatus();
            if (result !== null) {
                clearInterval(interval);
            }
        }, intervalMs);

        return interval;
    }

    // Event handlers (implement in subclass)
    onConfirmed(data) { console.log('Payment confirmed:', data); }
    onFailed(data) { console.log('Payment failed:', data); }
    onPending(data) { console.log('Payment pending:', data); }
}

// Usage
const monitor = new PaymentMonitor('payment123', 'crypto');
monitor.onConfirmed = (data) => {
    alert(`Payment confirmed! Amount: $${data.amount}`);
    window.location.reload();
};
monitor.startMonitoring();
```

### PHP Backend Integration

#### Creating a Payment
```php
// Create NOWPayments deposit
$payment = nowPayments()->createPayment([
    'price_amount' => 25.00,
    'price_currency' => 'usd',
    'pay_currency' => 'btc',
    'order_id' => 'deposit_' . $userId . '_' . time(),
    'order_description' => 'Wallet deposit'
]);

// Store in database
db()->insert('crypto_payments', [
    'user_id' => $userId,
    'payment_id' => $payment['payment_id'],
    'order_id' => $payment['order_id'],
    'price_amount' => $payment['price_amount'],
    'pay_amount' => $payment['pay_amount'],
    'pay_currency' => $payment['pay_currency'],
    'pay_address' => $payment['pay_address'],
    'status' => $payment['payment_status'],
    'invoice_url' => $payment['invoice_url'],
    'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
]);
```

#### Processing Order Messages
```php
// Get order messages for display
$messages = db()->fetchAll("
    SELECT om.*, u.username, u.role, u.avatar
    FROM order_messages om
    JOIN users u ON om.sender_id = u.id
    WHERE om.order_id = ?
    ORDER BY om.created_at ASC
", [$orderId]);

// Format for display
foreach ($messages as &$msg) {
    $msg['is_admin'] = strpos($msg['message'], '[ADMIN]') === 0;
    $msg['display_message'] = $msg['is_admin']
        ? trim(substr($msg['message'], 7))
        : $msg['message'];
    $msg['time_ago'] = timeAgo($msg['created_at']);
}
```

---

## Error Handling

### HTTP Status Codes
- `200` - Success
- `400` - Bad Request (missing/invalid parameters)
- `401` - Unauthorized (not logged in)
- `403` - Forbidden (access denied)
- `404` - Not Found (resource doesn't exist)
- `500` - Internal Server Error

### Common Error Responses
```json
// Missing authentication
{
    "error": "Unauthorized",
    "code": 401
}

// Invalid parameters
{
    "error": "Order ID required",
    "code": 400
}

// Access denied
{
    "error": "Access denied",
    "code": 403
}

// Resource not found
{
    "error": "Payment not found",
    "code": 404
}
```

---

## Rate Limiting
Currently, rate limiting is handled at the web server level. Future implementations may include:

- Per-user API call limits
- IP-based rate limiting
- Endpoint-specific limits
- Exponential backoff for failed requests

---

## Security Considerations

### Input Validation
- All inputs are sanitized using `clean()` function
- SQL injection prevention via prepared statements
- XSS prevention through output escaping

### Authorization
- Session-based authentication
- Role-based access control (buyer/seller/admin)
- Resource ownership verification

### Payment Security
- HMAC signature verification for webhooks
- Idempotency for payment processing
- Comprehensive audit logging
- Encrypted private key storage

---

## Monitoring and Logging

### Payment Logs
- NOWPayments IPN requests logged to `/logs/nowpayments_ipn.log`
- TRON payment checks logged via `paymentHandler()`
- Wallet transaction records for audit trail

### Error Logging
- PHP errors logged to system error log
- API errors logged with request context
- Failed payment attempts logged for investigation

### Performance Monitoring
Future implementations may include:
- API response time monitoring
- Request volume tracking
- Error rate alerts
- Database query performance

---

*Last Updated: January 2025*
*API Version: 1.0*