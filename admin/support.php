<?php
/**
 * HStore - Admin Support Tickets Management
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$adminId = getCurrentUserId();
$error = '';
$success = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request';
    } else {
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $newStatus = clean($_POST['status'] ?? '');
        
        $validStatuses = ['open', 'in_progress', 'waiting', 'resolved', 'closed'];
        if (!in_array($newStatus, $validStatuses)) {
            $error = 'Invalid status';
        } else {
            $ticket = db()->fetch("SELECT * FROM support_tickets WHERE id = ?", [$ticketId]);
            if ($ticket) {
                $updateData = ['status' => $newStatus];
                if ($newStatus === 'closed') {
                    $updateData['closed_at'] = date('Y-m-d H:i:s');
                }
                db()->update('support_tickets', $updateData, 'id = ?', [$ticketId]);
                
                // Notify user
                createNotification(
                    $ticket['user_id'],
                    'support',
                    'Ticket Status Updated',
                    'Your ticket #' . $ticket['ticket_number'] . ' status changed to: ' . ucfirst($newStatus),
                    BASE_URL . '/support.php?view=' . $ticketId
                );
                
                $success = 'Ticket status updated successfully';
            }
        }
    }
}

// Handle admin reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_reply'])) {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request';
    } else {
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = clean($_POST['message'] ?? '');
        
        $ticket = db()->fetch("SELECT * FROM support_tickets WHERE id = ?", [$ticketId]);
        
        if (!$ticket) {
            $error = 'Ticket not found';
        } elseif (empty($message)) {
            $error = 'Please enter a message';
        } else {
            // Add admin reply
            db()->insert('ticket_messages', [
                'ticket_id' => $ticketId,
                'user_id' => $adminId,
                'message' => $message,
                'is_admin' => 1
            ]);
            
            // Update ticket status to waiting (waiting for user response)
            db()->update('support_tickets', [
                'status' => 'waiting',
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$ticketId]);
            
            // Notify user
            createNotification(
                $ticket['user_id'],
                'support',
                'Support Reply',
                'You have a new reply on ticket #' . $ticket['ticket_number'],
                BASE_URL . '/support.php?view=' . $ticketId
            );
            
            $success = 'Reply sent successfully!';
        }
    }
}

// View single ticket
$viewTicket = null;
$ticketMessages = [];
$ticketUser = null;
if (isset($_GET['view'])) {
    $ticketId = (int)$_GET['view'];
    $viewTicket = db()->fetch("SELECT * FROM support_tickets WHERE id = ?", [$ticketId]);
    
    if ($viewTicket) {
        $ticketUser = db()->fetch("SELECT * FROM users WHERE id = ?", [$viewTicket['user_id']]);
        $ticketMessages = db()->fetchAll("
            SELECT tm.*, u.username, u.role 
            FROM ticket_messages tm 
            JOIN users u ON tm.user_id = u.id 
            WHERE tm.ticket_id = ? 
            ORDER BY tm.created_at ASC
        ", [$ticketId]);
    }
}

// Filter tickets
$statusFilter = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';

$whereClause = "1=1";
$params = [];

if ($statusFilter) {
    $whereClause .= " AND st.status = ?";
    $params[] = $statusFilter;
}
if ($categoryFilter) {
    $whereClause .= " AND st.category = ?";
    $params[] = $categoryFilter;
}
if ($priorityFilter) {
    $whereClause .= " AND st.priority = ?";
    $params[] = $priorityFilter;
}

// Get tickets
$tickets = db()->fetchAll("
    SELECT st.*, u.username, u.email,
           (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = st.id) as message_count,
           (SELECT created_at FROM ticket_messages WHERE ticket_id = st.id ORDER BY created_at DESC LIMIT 1) as last_message
    FROM support_tickets st 
    JOIN users u ON st.user_id = u.id
    WHERE {$whereClause}
    ORDER BY 
        CASE st.priority 
            WHEN 'urgent' THEN 1 
            WHEN 'high' THEN 2 
            WHEN 'medium' THEN 3 
            WHEN 'low' THEN 4 
        END,
        st.updated_at DESC
", $params);

// Stats
$openTickets = db()->count('support_tickets', "status = 'open'");
$inProgressTickets = db()->count('support_tickets', "status = 'in_progress'");
$waitingTickets = db()->count('support_tickets', "status = 'waiting'");
$resolvedTickets = db()->count('support_tickets', "status = 'resolved'");

$pageTitle = 'Support Tickets - Admin';
require_once __DIR__ . '/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-headset me-2"></i>Support Tickets</h4>
        <?php if ($viewTicket): ?>
            <a href="<?= BASE_URL ?>/xadmincp11/support.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Tickets
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
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-1"><?= sanitize($viewTicket['subject']) ?></h5>
                        <small class="text-muted">
                            #<?= $viewTicket['ticket_number'] ?> • 
                            Created <?= timeAgo($viewTicket['created_at']) ?>
                        </small>
                    </div>
                    <div class="card-body">
                        <!-- Messages -->
                        <div class="ticket-messages" style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($ticketMessages as $msg): ?>
                                <div class="message mb-3 <?= $msg['is_admin'] ? 'admin-message' : 'user-message' ?>">
                                    <div class="d-flex <?= $msg['is_admin'] ? 'flex-row-reverse' : '' ?>">
                                        <div class="message-content p-3 rounded <?= $msg['is_admin'] ? 'bg-primary text-white' : 'bg-light' ?>" 
                                             style="max-width: 80%;">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong>
                                                    <?php if ($msg['is_admin']): ?>
                                                        <i class="fas fa-shield-alt me-1"></i>Admin
                                                    <?php else: ?>
                                                        <i class="fas fa-user me-1"></i><?= sanitize($msg['username']) ?>
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
                            <!-- Admin Reply Form -->
                            <hr>
                            <form method="POST" action="">
                                <?= csrfField() ?>
                                <input type="hidden" name="ticket_id" value="<?= $viewTicket['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">Admin Reply</label>
                                    <textarea class="form-control" name="message" rows="3" required 
                                              placeholder="Type your reply..."></textarea>
                                </div>
                                <button type="submit" name="admin_reply" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Send Reply
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Ticket Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Ticket Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td class="text-muted">Status</td>
                                <td>
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
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Priority</td>
                                <td>
                                    <?php
                                    $priorityColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'orange', 'urgent' => 'danger'];
                                    $priorityColor = $priorityColors[$viewTicket['priority']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $priorityColor ?>"><?= ucfirst($viewTicket['priority']) ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Category</td>
                                <td><?= ucfirst($viewTicket['category']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Created</td>
                                <td><?= formatDate($viewTicket['created_at']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Last Update</td>
                                <td><?= timeAgo($viewTicket['updated_at']) ?></td>
                            </tr>
                        </table>
                        
                        <!-- Update Status -->
                        <form method="POST" action="" class="mt-3">
                            <?= csrfField() ?>
                            <input type="hidden" name="ticket_id" value="<?= $viewTicket['id'] ?>">
                            <label class="form-label">Update Status</label>
                            <div class="input-group">
                                <select class="form-select" name="status">
                                    <option value="open" <?= $viewTicket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                    <option value="in_progress" <?= $viewTicket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="waiting" <?= $viewTicket['status'] === 'waiting' ? 'selected' : '' ?>>Waiting</option>
                                    <option value="resolved" <?= $viewTicket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                    <option value="closed" <?= $viewTicket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-outline-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- User Info -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">User Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 50px; height: 50px;">
                                <?= strtoupper(substr($ticketUser['username'], 0, 1)) ?>
                            </div>
                            <div>
                                <strong><?= sanitize($ticketUser['username']) ?></strong>
                                <small class="text-muted d-block"><?= sanitize($ticketUser['email']) ?></small>
                            </div>
                        </div>
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="text-muted">Role</td>
                                <td><span class="badge bg-secondary"><?= ucfirst($ticketUser['role']) ?></span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Joined</td>
                                <td><?= formatDate($ticketUser['created_at']) ?></td>
                            </tr>
                        </table>
                        <a href="<?= BASE_URL ?>/xadmincp11/users.php?view=<?= $ticketUser['id'] ?>" class="btn btn-sm btn-outline-primary mt-3 w-100">
                            View User Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3 class="mb-0"><?= $openTickets ?></h3>
                                <small>Open Tickets</small>
                            </div>
                            <i class="fas fa-envelope-open fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3 class="mb-0"><?= $inProgressTickets ?></h3>
                                <small>In Progress</small>
                            </div>
                            <i class="fas fa-spinner fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3 class="mb-0"><?= $waitingTickets ?></h3>
                                <small>Waiting Reply</small>
                            </div>
                            <i class="fas fa-clock fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3 class="mb-0"><?= $resolvedTickets ?></h3>
                                <small>Resolved</small>
                            </div>
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="waiting" <?= $statusFilter === 'waiting' ? 'selected' : '' ?>>Waiting</option>
                            <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <option value="general" <?= $categoryFilter === 'general' ? 'selected' : '' ?>>General</option>
                            <option value="payment" <?= $categoryFilter === 'payment' ? 'selected' : '' ?>>Payment</option>
                            <option value="order" <?= $categoryFilter === 'order' ? 'selected' : '' ?>>Order</option>
                            <option value="technical" <?= $categoryFilter === 'technical' ? 'selected' : '' ?>>Technical</option>
                            <option value="seller" <?= $categoryFilter === 'seller' ? 'selected' : '' ?>>Seller</option>
                            <option value="report" <?= $categoryFilter === 'report' ? 'selected' : '' ?>>Report</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority">
                            <option value="">All Priorities</option>
                            <option value="urgent" <?= $priorityFilter === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                            <option value="high" <?= $priorityFilter === 'high' ? 'selected' : '' ?>>High</option>
                            <option value="medium" <?= $priorityFilter === 'medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="low" <?= $priorityFilter === 'low' ? 'selected' : '' ?>>Low</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <a href="<?= BASE_URL ?>/xadmincp11/support.php" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tickets Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Last Update</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No tickets found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td>
                                        <code>#<?= $ticket['ticket_number'] ?></code>
                                    </td>
                                    <td>
                                        <strong><?= sanitize($ticket['username']) ?></strong>
                                        <small class="text-muted d-block"><?= sanitize($ticket['email']) ?></small>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/xadmincp11/support.php?view=<?= $ticket['id'] ?>" class="text-decoration-none">
                                            <?= sanitize(truncate($ticket['subject'], 35)) ?>
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
                                        <a href="<?= BASE_URL ?>/xadmincp11/support.php?view=<?= $ticket['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.ticket-messages .admin-message .message-content {
    border-right: 3px solid var(--bs-primary);
}
.ticket-messages .user-message .message-content {
    border-left: 3px solid var(--bs-secondary);
}
.bg-orange {
    background-color: #fd7e14 !important;
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
