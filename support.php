<?php
/**
 * HStore - Support Tickets Page
 */
require_once __DIR__ . '/includes/init.php';
requireLogin();

$userId = getCurrentUserId();
$user = getCurrentUser();
$error = '';
$success = '';

// Handle new ticket creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = __('invalid_request');
    } else {
        $subject = clean($_POST['subject'] ?? '');
        $category = clean($_POST['category'] ?? 'general');
        $priority = clean($_POST['priority'] ?? 'medium');
        $message = clean($_POST['message'] ?? '');
        
        if (empty($subject) || empty($message)) {
            $error = __('please_fill_all_fields');
        } elseif (strlen($subject) < 5) {
            $error = 'Subject must be at least 5 characters';
        } elseif (strlen($message) < 20) {
            $error = 'Message must be at least 20 characters';
        } else {
            // Generate ticket number
            $ticketNumber = 'TKT-' . strtoupper(substr(md5(uniqid()), 0, 8));
            
            // Create ticket
            $ticketId = db()->insert('support_tickets', [
                'ticket_number' => $ticketNumber,
                'user_id' => $userId,
                'subject' => $subject,
                'category' => $category,
                'priority' => $priority,
                'status' => 'open'
            ]);
            
            // Add first message
            db()->insert('ticket_messages', [
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'message' => $message,
                'is_admin' => 0
            ]);
            
            // Notify admin
            $admins = db()->fetchAll("SELECT id FROM users WHERE role = 'admin'");
            foreach ($admins as $admin) {
                createNotification(
                    $admin['id'],
                    'support',
                    'New Support Ticket',
                    'New ticket #' . $ticketNumber . ': ' . $subject,
                    BASE_URL . '/admin/support.php?view=' . $ticketId
                );
            }
            
            $success = 'Ticket created successfully! Your ticket number is: ' . $ticketNumber;
        }
    }
}

// Handle reply to ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ticket'])) {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = __('invalid_request');
    } else {
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = clean($_POST['message'] ?? '');
        
        // Verify ticket belongs to user
        $ticket = db()->fetch("SELECT * FROM support_tickets WHERE id = ? AND user_id = ?", [$ticketId, $userId]);
        
        if (!$ticket) {
            $error = 'Ticket not found';
        } elseif (empty($message)) {
            $error = 'Please enter a message';
        } elseif ($ticket['status'] === 'closed') {
            $error = 'This ticket is closed';
        } else {
            // Add reply
            db()->insert('ticket_messages', [
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'message' => $message,
                'is_admin' => 0
            ]);
            
            // Update ticket status if it was waiting
            if ($ticket['status'] === 'waiting') {
                db()->update('support_tickets', ['status' => 'open'], 'id = ?', [$ticketId]);
            }
            
            // Update ticket timestamp
            db()->query("UPDATE support_tickets SET updated_at = NOW() WHERE id = ?", [$ticketId]);
            
            // Notify admin
            $admins = db()->fetchAll("SELECT id FROM users WHERE role = 'admin'");
            foreach ($admins as $admin) {
                createNotification(
                    $admin['id'],
                    'support',
                    'Ticket Reply',
                    'New reply on ticket #' . $ticket['ticket_number'],
                    BASE_URL . '/admin/support.php?view=' . $ticketId
                );
            }
            
            $success = 'Reply sent successfully!';
        }
    }
}

// View single ticket
$viewTicket = null;
$ticketMessages = [];
if (isset($_GET['view'])) {
    $ticketId = (int)$_GET['view'];
    $viewTicket = db()->fetch("SELECT * FROM support_tickets WHERE id = ? AND user_id = ?", [$ticketId, $userId]);
    
    if ($viewTicket) {
        $ticketMessages = db()->fetchAll("
            SELECT tm.*, u.username, u.role 
            FROM ticket_messages tm 
            JOIN users u ON tm.user_id = u.id 
            WHERE tm.ticket_id = ? 
            ORDER BY tm.created_at ASC
        ", [$ticketId]);
    }
}

// Get user's tickets
$tickets = db()->fetchAll("
    SELECT st.*, 
           (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = st.id) as message_count,
           (SELECT created_at FROM ticket_messages WHERE ticket_id = st.id ORDER BY created_at DESC LIMIT 1) as last_message
    FROM support_tickets st 
    WHERE st.user_id = ? 
    ORDER BY st.updated_at DESC
", [$userId]);

$pageTitle = __('support') . ' - ' . PLATFORM_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-headset me-2"></i><?= __('support') ?></h4>
        <?php if (!$viewTicket): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                <i class="fas fa-plus me-2"></i><?= __('new_ticket') ?>
            </button>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/support.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i><?= __('back_to_tickets') ?>
            </a>
        <?php endif; ?>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= sanitize($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= sanitize($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($viewTicket): ?>
        <!-- View Single Ticket -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1"><?= sanitize($viewTicket['subject']) ?></h5>
                    <small class="text-muted">
                        #<?= $viewTicket['ticket_number'] ?> • 
                        <?= ucfirst($viewTicket['category']) ?> • 
                        Created <?= timeAgo($viewTicket['created_at']) ?>
                    </small>
                </div>
                <div>
                    <?php
                    $statusColors = [
                        'open' => 'primary',
                        'in_progress' => 'info',
                        'waiting' => 'warning',
                        'resolved' => 'success',
                        'closed' => 'secondary'
                    ];
                    $statusColor = $statusColors[$viewTicket['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $statusColor ?>"><?= ucfirst(str_replace('_', ' ', $viewTicket['status'])) ?></span>
                    <?php
                    $priorityColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'orange', 'urgent' => 'danger'];
                    $priorityColor = $priorityColors[$viewTicket['priority']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $priorityColor ?>"><?= ucfirst($viewTicket['priority']) ?></span>
                </div>
            </div>
            <div class="card-body">
                <!-- Messages -->
                <div class="ticket-messages" style="max-height: 500px; overflow-y: auto;">
                    <?php foreach ($ticketMessages as $msg): ?>
                        <div class="message mb-3 <?= $msg['is_admin'] ? 'admin-message' : 'user-message' ?>">
                            <div class="d-flex <?= $msg['is_admin'] ? '' : 'flex-row-reverse' ?>">
                                <div class="message-content p-3 rounded <?= $msg['is_admin'] ? 'bg-primary text-white' : 'bg-light' ?>" 
                                     style="max-width: 80%;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>
                                            <?php if ($msg['is_admin']): ?>
                                                <i class="fas fa-shield-alt me-1"></i>Support Team
                                            <?php else: ?>
                                                <?= sanitize($msg['username']) ?>
                                            <?php endif; ?>
                                        </strong>
                                        <small class="<?= $msg['is_admin'] ? 'text-white-50' : 'text-muted' ?> ms-3">
                                            <?= timeAgo($msg['created_at']) ?>
                                        </small>
                                    </div>
                                    <div class="message-text"><?= nl2br(sanitize($msg['message'])) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($viewTicket['status'] !== 'closed'): ?>
                    <!-- Reply Form -->
                    <hr>
                    <form method="POST" action="">
                        <?= csrfField() ?>
                        <input type="hidden" name="ticket_id" value="<?= $viewTicket['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label"><?= __('your_reply') ?></label>
                            <textarea class="form-control" name="message" rows="3" required 
                                      placeholder="Type your reply here..."></textarea>
                        </div>
                        <button type="submit" name="reply_ticket" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i><?= __('send_reply') ?>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-secondary mb-0">
                        <i class="fas fa-lock me-2"></i>This ticket is closed. If you need further assistance, please create a new ticket.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Tickets List -->
        <?php if (empty($tickets)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
                    <h5><?= __('no_tickets') ?></h5>
                    <p class="text-muted">You haven't created any support tickets yet.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                        <i class="fas fa-plus me-2"></i><?= __('create_first_ticket') ?>
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?= __('ticket') ?></th>
                                <th><?= __('subject') ?></th>
                                <th><?= __('category') ?></th>
                                <th><?= __('status') ?></th>
                                <th><?= __('priority') ?></th>
                                <th><?= __('last_update') ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td>
                                        <code>#<?= $ticket['ticket_number'] ?></code>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/support.php?view=<?= $ticket['id'] ?>" class="text-decoration-none">
                                            <?= sanitize(truncate($ticket['subject'], 40)) ?>
                                        </a>
                                        <small class="text-muted d-block"><?= $ticket['message_count'] ?> messages</small>
                                    </td>
                                    <td><?= ucfirst($ticket['category']) ?></td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'open' => 'primary',
                                            'in_progress' => 'info',
                                            'waiting' => 'warning',
                                            'resolved' => 'success',
                                            'closed' => 'secondary'
                                        ];
                                        $statusColor = $statusColors[$ticket['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $statusColor ?>"><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $priorityColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'orange', 'urgent' => 'danger'];
                                        $priorityColor = $priorityColors[$ticket['priority']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $priorityColor ?>"><?= ucfirst($ticket['priority']) ?></span>
                                    </td>
                                    <td>
                                        <small><?= timeAgo($ticket['updated_at']) ?></small>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/support.php?view=<?= $ticket['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- New Ticket Modal -->
<div class="modal fade" id="newTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i><?= __('create_new_ticket') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <?= csrfField() ?>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label"><?= __('subject') ?> *</label>
                            <input type="text" class="form-control" name="subject" required 
                                   placeholder="Brief description of your issue" minlength="5">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= __('category') ?></label>
                            <select class="form-select" name="category">
                                <option value="general">General Inquiry</option>
                                <option value="payment">Payment Issue</option>
                                <option value="order">Order Problem</option>
                                <option value="technical">Technical Support</option>
                                <option value="seller">Seller Support</option>
                                <option value="report">Report Abuse</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= __('priority') ?></label>
                        <select class="form-select" name="priority">
                            <option value="low">Low - General question</option>
                            <option value="medium" selected>Medium - Need help soon</option>
                            <option value="high">High - Urgent issue</option>
                            <option value="urgent">Urgent - Critical problem</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= __('message') ?> *</label>
                        <textarea class="form-control" name="message" rows="6" required 
                                  placeholder="Please describe your issue in detail. Include any relevant order numbers, screenshots, or other information that might help us assist you better."
                                  minlength="20"></textarea>
                        <small class="text-muted">Minimum 20 characters</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                    <button type="submit" name="create_ticket" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i><?= __('submit_ticket') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.ticket-messages .admin-message .message-content {
    border-left: 3px solid var(--bs-primary);
}
.ticket-messages .user-message .message-content {
    border-right: 3px solid var(--bs-secondary);
}
.bg-orange {
    background-color: #fd7e14 !important;
}
</style>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
