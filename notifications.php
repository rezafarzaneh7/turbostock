<?php
/**
 * HStore - Notifications Page
 */
require_once __DIR__ . '/includes/init.php';
requireLogin();

$userId = getCurrentUserId();

// Mark all as read if requested
if (isset($_GET['mark_read'])) {
    db()->update('notifications', ['is_read' => 1], 'user_id = ?', [$userId]);
    redirectWithMessage(BASE_URL . '/notifications.php', 'All notifications marked as read', 'success');
}

// Get notifications
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalNotifications = db()->count('notifications', 'user_id = ?', [$userId]);
$pagination = paginate($totalNotifications, $page, 20);

$notifications = db()->fetchAll("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", [$userId]);

$unreadCount = db()->count('notifications', 'user_id = ? AND is_read = 0', [$userId]);

$pageTitle = 'Notifications - ' . PLATFORM_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">
            <i class="fas fa-bell me-2"></i>Notifications
            <?php if ($unreadCount > 0): ?>
                <span class="badge bg-danger"><?= $unreadCount ?> unread</span>
            <?php endif; ?>
        </h4>
        <?php if ($unreadCount > 0): ?>
            <a href="<?= BASE_URL ?>/notifications.php?mark_read=1" class="btn btn-outline-primary">
                <i class="fas fa-check-double me-2"></i>Mark All as Read
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($notifications)): ?>
        <div class="card">
            <div class="card-body empty-state">
                <i class="fas fa-bell-slash"></i>
                <h5>No notifications</h5>
                <p class="text-muted">You don't have any notifications yet</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $notif): ?>
                    <a href="<?= $notif['link'] ?? '#' ?>" 
                       class="list-group-item list-group-item-action <?= $notif['is_read'] ? '' : 'bg-light' ?>"
                       onclick="<?= !$notif['is_read'] ? "fetch('" . BASE_URL . "/api/mark-notification-read.php?id=" . $notif['id'] . "')" : '' ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <?php if (!$notif['is_read']): ?>
                                    <span class="badge bg-primary me-2">New</span>
                                <?php endif; ?>
                                <strong><?= sanitize($notif['title']) ?></strong>
                                <?php if ($notif['message']): ?>
                                    <p class="mb-0 text-muted"><?= sanitize($notif['message']) ?></p>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?= timeAgo($notif['created_at']) ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="mt-4">
            <?= renderPagination($pagination, BASE_URL . '/notifications.php') ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
