<?php
/**
 * HStore - Admin Dispute Chat View
 * Admin can view and participate in order chat for disputed orders
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$orderId = (int) ($_GET['id'] ?? 0);

if (!$orderId) {
    redirectWithMessage(BASE_URL . '/xadmincp11/disputes.php', 'Order not found', 'error');
}

// Get order with dispute info
$order = db()->fetch("
    SELECT o.*, p.name as product_name, p.delivery_type, p.thumbnail,
           s.id as seller_id, s.shop_name, s.user_id as seller_user_id,
           u.username as buyer_username,
           d.id as dispute_id, d.reason as dispute_reason, d.description as dispute_description, 
           d.status as dispute_status, d.created_at as dispute_date
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN sellers s ON o.seller_id = s.id
    JOIN users u ON o.buyer_id = u.id
    LEFT JOIN disputes d ON d.order_id = o.id
    WHERE o.id = ?
", [$orderId]);

if (!$order) {
    redirectWithMessage(BASE_URL . '/xadmincp11/disputes.php', 'Order not found', 'error');
}

$userId = getCurrentUserId();
$error = '';

// Handle admin message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request';
    } else {
        $message = trim($_POST['message'] ?? '');
        $attachment = null;
        $attachmentName = null;
        
        // Handle file upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/zip'];
            $maxSize = 10 * 1024 * 1024;
            
            if (!in_array($file['type'], $allowedTypes)) {
                $error = 'Invalid file type';
            } elseif ($file['size'] > $maxSize) {
                $error = 'File too large (max 10MB)';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'admin_chat_' . $orderId . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $uploadPath = UPLOADS_PATH . '/chat/' . $filename;
                
                if (!is_dir(UPLOADS_PATH . '/chat')) {
                    mkdir(UPLOADS_PATH . '/chat', 0755, true);
                }
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $attachment = 'chat/' . $filename;
                    $attachmentName = $file['name'];
                }
            }
        }
        
        if (!$error && (trim($message) || $attachment)) {
            db()->insert('order_messages', [
                'order_id' => $orderId,
                'sender_id' => $userId,
                'message' => '[ADMIN] ' . ($message ?: ''),
                'attachment' => $attachment,
                'attachment_name' => $attachmentName
            ]);
            
            // Notify both buyer and seller
            createNotification(
                $order['buyer_id'],
                'message',
                'Admin Message',
                'Admin sent a message regarding your dispute for order #' . $order['order_number'],
                BASE_URL . '/order-chat.php?id=' . $orderId
            );
            
            createNotification(
                $order['seller_user_id'],
                'message',
                'Admin Message',
                'Admin sent a message regarding dispute for order #' . $order['order_number'],
                BASE_URL . '/order-chat.php?id=' . $orderId
            );
            
            header('Location: ' . BASE_URL . '/xadmincp11/dispute-chat.php?id=' . $orderId);
            exit;
        }
    }
}

// Get messages
$messages = db()->fetchAll("
    SELECT om.*, u.username, u.role,
           CASE WHEN u.id = ? THEN 1 ELSE 0 END as is_mine
    FROM order_messages om
    JOIN users u ON om.sender_id = u.id
    WHERE om.order_id = ?
    ORDER BY om.created_at ASC
", [$userId, $orderId]);

$pageTitle = 'Dispute Chat - Order #' . $order['order_number'];
require_once __DIR__ . '/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Chat Area -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="fas fa-comments me-2"></i>Dispute Chat
                        </h5>
                        <small class="text-muted">Order #<?= $order['order_number'] ?></small>
                    </div>
                    <a href="<?= BASE_URL ?>/xadmincp11/disputes.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to Disputes
                    </a>
                </div>
                
                <div class="card-body chat-messages" id="chatMessages" style="height: 450px; overflow-y: auto; background: #f8f9fa;">
                    <?php if (empty($messages)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <p>No messages yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <?php 
                            $isAdmin = strpos($msg['message'], '[ADMIN]') === 0;
                            $displayMessage = $isAdmin ? substr($msg['message'], 7) : $msg['message'];
                            ?>
                            <div class="message-bubble mb-3">
                                <div class="message-content p-3 rounded-3 <?= $isAdmin ? 'bg-danger text-white' : ($msg['role'] === 'seller' ? 'bg-primary text-white' : 'bg-white') ?>" 
                                     style="max-width: 75%;">
                                    <div class="message-header mb-1">
                                        <small class="<?= $isAdmin || $msg['role'] === 'seller' ? 'text-white-50' : 'text-muted' ?>">
                                            <strong>
                                                <?php if ($isAdmin): ?>
                                                    <i class="fas fa-shield-alt me-1"></i>Admin
                                                <?php elseif ($msg['role'] === 'seller'): ?>
                                                    <i class="fas fa-store me-1"></i><?= sanitize($msg['username']) ?> (Seller)
                                                <?php else: ?>
                                                    <i class="fas fa-user me-1"></i><?= sanitize($msg['username']) ?> (Buyer)
                                                <?php endif; ?>
                                            </strong>
                                            • <?= timeAgo($msg['created_at']) ?>
                                        </small>
                                    </div>
                                    <?php if ($displayMessage): ?>
                                        <p class="mb-0"><?= nl2br(sanitize(trim($displayMessage))) ?></p>
                                    <?php endif; ?>
                                    <?php if ($msg['attachment']): ?>
                                        <div class="attachment mt-2">
                                            <?php 
                                            $ext = strtolower(pathinfo($msg['attachment'], PATHINFO_EXTENSION));
                                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                                            ?>
                                            <?php if ($isImage): ?>
                                                <a href="<?= UPLOADS_URL ?>/<?= $msg['attachment'] ?>" target="_blank">
                                                    <img src="<?= UPLOADS_URL ?>/<?= $msg['attachment'] ?>" class="img-fluid rounded" style="max-height: 200px;">
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= UPLOADS_URL ?>/<?= $msg['attachment'] ?>" 
                                                   class="btn btn-sm btn-light" download>
                                                    <i class="fas fa-download me-1"></i><?= sanitize($msg['attachment_name']) ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="card-footer">
                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 mb-3"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data" id="messageForm">
                        <?= csrfField() ?>
                        <div class="input-group">
                            <span class="input-group-text bg-danger text-white">
                                <i class="fas fa-shield-alt"></i>
                            </span>
                            <input type="text" class="form-control" name="message" id="messageInput" placeholder="Send message as Admin..." autocomplete="off">
                            <label class="btn btn-outline-secondary" for="attachmentInput">
                                <i class="fas fa-paperclip"></i>
                            </label>
                            <input type="file" id="attachmentInput" name="attachment" class="d-none" accept=".jpg,.jpeg,.png,.gif,.pdf,.txt,.zip">
                            <button type="submit" name="send_message" class="btn btn-danger">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <small class="text-muted">Messages will be marked as [ADMIN]</small>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Order & Dispute Info -->
        <div class="col-lg-4">
            <?php if ($order['dispute_id']): ?>
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Dispute Details</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Status</small>
                        <div>
                            <?php
                            $statusClass = match($order['dispute_status']) {
                                'open' => 'danger',
                                'under_review' => 'warning',
                                'resolved_buyer', 'resolved_seller' => 'success',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $statusClass ?>">
                                <?= ucfirst(str_replace('_', ' ', $order['dispute_status'])) ?>
                            </span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Reason</small>
                        <div class="fw-bold"><?= sanitize($order['dispute_reason']) ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Description</small>
                        <div><?= nl2br(sanitize($order['dispute_description'])) ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Opened</small>
                        <div><?= formatDateTime($order['dispute_date']) ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-box me-2"></i>Order Details</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <?php if ($order['thumbnail']): ?>
                            <img src="<?= UPLOADS_URL ?>/<?= $order['thumbnail'] ?>" class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                        <?php endif; ?>
                        <div>
                            <h6 class="mb-1"><?= sanitize($order['product_name']) ?></h6>
                            <small class="text-muted">Qty: <?= $order['quantity'] ?></small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-2">
                        <small class="text-muted">Order Number</small>
                        <div class="fw-bold">#<?= $order['order_number'] ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Amount</small>
                        <div class="fw-bold text-success"><?= formatCurrency($order['total_amount']) ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Buyer</small>
                        <div class="fw-bold"><?= sanitize($order['buyer_username']) ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Seller</small>
                        <div class="fw-bold"><?= sanitize($order['shop_name']) ?></div>
                    </div>
                </div>
            </div>
            
            <?php if ($order['dispute_status'] === 'open' || $order['dispute_status'] === 'under_review'): ?>
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-gavel me-2"></i>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/xadmincp11/disputes.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="dispute_id" value="<?= $order['dispute_id'] ?>">
                        <div class="mb-3">
                            <select class="form-select" name="resolution" required>
                                <option value="">Select resolution</option>
                                <option value="resolved_buyer">Refund to Buyer</option>
                                <option value="resolved_seller">In Seller's Favor</option>
                                <option value="closed">Close Without Action</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" name="admin_notes" rows="2" placeholder="Admin notes..."></textarea>
                        </div>
                        <button type="submit" name="resolve" class="btn btn-primary w-100">
                            <i class="fas fa-check me-2"></i>Resolve Dispute
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const orderId = <?= $orderId ?>;
const baseUrl = '<?= BASE_URL ?>';
let lastMessageId = <?= !empty($messages) ? end($messages)['id'] : 0 ?>;
const chatMessages = document.getElementById('chatMessages');
const messageForm = document.getElementById('messageForm');
const messageInput = document.getElementById('messageInput');

// Scroll to bottom of chat
function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

document.addEventListener('DOMContentLoaded', scrollToBottom);

// Create message HTML
function createMessageHTML(msg) {
    let bgClass = 'bg-white';
    let textClass = 'text-muted';
    let icon = '<i class="fas fa-user me-1"></i>';
    let roleLabel = '(Buyer)';
    
    if (msg.role === 'admin') {
        bgClass = 'bg-danger text-white';
        textClass = 'text-white-50';
        icon = '<i class="fas fa-shield-alt me-1"></i>';
        roleLabel = 'Admin';
    } else if (msg.role === 'seller') {
        bgClass = 'bg-primary text-white';
        textClass = 'text-white-50';
        icon = '<i class="fas fa-store me-1"></i>';
        roleLabel = '(Seller)';
    }
    
    let attachmentHTML = '';
    if (msg.attachment) {
        const ext = msg.attachment.split('.').pop().toLowerCase();
        const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(ext);
        if (isImage) {
            attachmentHTML = `<div class="attachment mt-2"><a href="${baseUrl}/uploads/${msg.attachment}" target="_blank"><img src="${baseUrl}/uploads/${msg.attachment}" class="img-fluid rounded" style="max-height: 200px;"></a></div>`;
        } else {
            attachmentHTML = `<div class="attachment mt-2"><a href="${baseUrl}/uploads/${msg.attachment}" class="btn btn-sm btn-light" download><i class="fas fa-download me-1"></i>${msg.attachment_name}</a></div>`;
        }
    }
    
    return `
        <div class="message-bubble mb-3" data-id="${msg.id}">
            <div class="message-content p-3 rounded-3 ${bgClass}" style="max-width: 75%;">
                <div class="message-header mb-1">
                    <small class="${textClass}">
                        <strong>${icon}${msg.sender} ${msg.role !== 'admin' ? roleLabel : ''}</strong>
                        • ${msg.time}
                    </small>
                </div>
                ${msg.message ? `<p class="mb-0">${msg.message.replace(/\n/g, '<br>')}</p>` : ''}
                ${attachmentHTML}
            </div>
        </div>
    `;
}

// Fetch new messages
async function fetchNewMessages() {
    try {
        const response = await fetch(`${baseUrl}/api/chat.php?action=get&order_id=${orderId}&last_id=${lastMessageId}`);
        const data = await response.json();
        
        if (data.success && data.messages.length > 0) {
            data.messages.forEach(msg => {
                chatMessages.insertAdjacentHTML('beforeend', createMessageHTML(msg));
            });
            lastMessageId = data.last_id;
            scrollToBottom();
        }
    } catch (error) {
        console.error('Error fetching messages:', error);
    }
}

// Send message via AJAX
messageForm.addEventListener('submit', async function(e) {
    const fileInput = document.getElementById('attachmentInput');
    if (fileInput.files.length > 0) {
        return true;
    }
    
    e.preventDefault();
    
    const message = messageInput.value.trim();
    if (!message) return;
    
    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('order_id', orderId);
    formData.append('message', message);
    
    try {
        const response = await fetch(`${baseUrl}/api/chat.php`, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            messageInput.value = '';
            fetchNewMessages();
        }
    } catch (error) {
        console.error('Error sending message:', error);
    }
});

// Poll for new messages every 3 seconds
setInterval(fetchNewMessages, 3000);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
