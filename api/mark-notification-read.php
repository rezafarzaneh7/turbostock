<?php
/**
 * HStore - Mark Notification as Read API
 */
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonError('Unauthorized', 401);
}

$notificationId = (int) ($_GET['id'] ?? 0);

if (!$notificationId) {
    jsonError('Notification ID required');
}

// Update notification
$updated = db()->update(
    'notifications', 
    ['is_read' => 1], 
    'id = ? AND user_id = ?', 
    [$notificationId, getCurrentUserId()]
);

if ($updated) {
    jsonSuccess([], 'Notification marked as read');
} else {
    jsonError('Notification not found', 404);
}
