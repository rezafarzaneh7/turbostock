<?php
/**
 * HStore - Chat API for Live Messages
 */
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$orderId = (int) ($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

if (!$orderId) {
    echo json_encode(['error' => 'Order ID required']);
    exit;
}

// Get order to verify access
$order = db()->fetch("
    SELECT o.*, s.user_id as seller_user_id
    FROM orders o
    JOIN sellers s ON o.seller_id = s.id
    WHERE o.id = ?
", [$orderId]);

if (!$order) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}

$userId = getCurrentUserId();
$isBuyer = ($order['buyer_id'] == $userId);
$isSeller = ($order['seller_user_id'] == $userId);
$isAdmin = isAdmin();

if (!$isBuyer && !$isSeller && !$isAdmin) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// GET MESSAGES
if ($action === 'get') {
    $lastId = (int) ($_GET['last_id'] ?? 0);
    
    $where = "om.order_id = ?";
    $params = [$orderId];
    
    if ($lastId > 0) {
        $where .= " AND om.id > ?";
        $params[] = $lastId;
    }
    
    $messages = db()->fetchAll("
        SELECT om.*, u.username, u.role
        FROM order_messages om
        JOIN users u ON om.sender_id = u.id
        WHERE {$where}
        ORDER BY om.created_at ASC
    ", $params);
    
    // Mark as read
    if (!empty($messages)) {
        db()->query("
            UPDATE order_messages 
            SET is_read = 1 
            WHERE order_id = ? AND sender_id != ?
        ", [$orderId, $userId]);
    }
    
    // Format messages for response
    $formattedMessages = [];
    foreach ($messages as $msg) {
        $isAdmin = strpos($msg['message'], '[ADMIN]') === 0;
        $displayMessage = $isAdmin ? trim(substr($msg['message'], 7)) : $msg['message'];
        
        $formattedMessages[] = [
            'id' => $msg['id'],
            'sender' => $msg['username'],
            'role' => $isAdmin ? 'admin' : $msg['role'],
            'message' => htmlspecialchars($displayMessage),
            'attachment' => $msg['attachment'],
            'attachment_name' => $msg['attachment_name'],
            'is_mine' => ($msg['sender_id'] == $userId),
            'time' => timeAgo($msg['created_at'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $formattedMessages,
        'last_id' => !empty($messages) ? end($messages)['id'] : $lastId
    ]);
    exit;
}

// SEND MESSAGE
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message)) {
        echo json_encode(['error' => 'Message required']);
        exit;
    }
    
    // Add [ADMIN] prefix for admin messages
    if ($isAdmin) {
        $message = '[ADMIN] ' . $message;
    }
    
    $msgId = db()->insert('order_messages', [
        'order_id' => $orderId,
        'sender_id' => $userId,
        'message' => $message
    ]);
    
    // Send notification to other party
    if ($isBuyer) {
        $recipientId = $order['seller_user_id'];
    } elseif ($isSeller) {
        $recipientId = $order['buyer_id'];
    } else {
        // Admin - notify both
        createNotification($order['buyer_id'], 'message', 'New Message', 'Admin sent a message', BASE_URL . '/order-chat.php?id=' . $orderId);
        $recipientId = $order['seller_user_id'];
    }
    
    createNotification(
        $recipientId,
        'message',
        'New Message',
        'You have a new message for order #' . $order['order_number'],
        BASE_URL . '/order-chat.php?id=' . $orderId
    );
    
    echo json_encode([
        'success' => true,
        'message_id' => $msgId
    ]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
