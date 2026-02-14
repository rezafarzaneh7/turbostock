<?php
/**
 * HStore - Order Chat (Buyer-Seller Communication for Manual Delivery)
 */
require_once __DIR__ . '/includes/init.php';

requireLogin();

$orderId = (int) ($_GET['id'] ?? 0);

if (!$orderId) {
    redirectWithMessage(BASE_URL . '/orders.php', 'Order not found', 'error');
}

// Get order with product and seller info
$order = db()->fetch("
    SELECT o.*, p.name as product_name, p.delivery_type, p.thumbnail,
           s.id as seller_id, s.shop_name, s.user_id as seller_user_id,
           u.username as buyer_username
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN sellers s ON o.seller_id = s.id
    JOIN users u ON o.buyer_id = u.id
    WHERE o.id = ?
", [$orderId]);

if (!$order) {
    redirectWithMessage(BASE_URL . '/orders.php', 'Order not found', 'error');
}

// Check if user is buyer, seller, or admin of this order
$userId = getCurrentUserId();
$isBuyer = ($order['buyer_id'] == $userId);
$isSeller = ($order['seller_user_id'] == $userId);
$isAdmin = isAdmin();

if (!$isBuyer && !$isSeller && !$isAdmin) {
    redirectWithMessage(BASE_URL, 'Access denied', 'error');
}

// Only allow chat for manual delivery orders or disputed orders
if ($order['delivery_type'] !== 'manual' && $order['status'] !== 'disputed') {
    redirectWithMessage(BASE_URL . '/orders.php?view=' . $orderId, 'Chat is only available for manual delivery or disputed orders', 'info');
}

// Handle message send
$error = '';
$success = '';

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
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/zip', 'application/x-rar-compressed'];
            $maxSize = 10 * 1024 * 1024; // 10MB
            
            if (!in_array($file['type'], $allowedTypes)) {
                $error = 'Invalid file type. Allowed: JPG, PNG, GIF, PDF, TXT, ZIP, RAR';
            } elseif ($file['size'] > $maxSize) {
                $error = 'File too large. Maximum 10MB allowed';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'chat_' . $orderId . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $uploadPath = UPLOADS_PATH . '/chat/' . $filename;
                
                // Create chat uploads directory if not exists
                if (!is_dir(UPLOADS_PATH . '/chat')) {
                    mkdir(UPLOADS_PATH . '/chat', 0755, true);
                }
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $attachment = 'chat/' . $filename;
                    $attachmentName = $file['name'];
                } else {
                    $error = 'Failed to upload file';
                }
            }
        }
        
        if (!$error && (trim($message) || $attachment)) {
            db()->insert('order_messages', [
                'order_id' => $orderId,
                'sender_id' => $userId,
                'message' => $message ?: null,
                'attachment' => $attachment,
                'attachment_name' => $attachmentName
            ]);
            
            // Send notification to other party
            $recipientId = $isBuyer ? $order['seller_user_id'] : $order['buyer_id'];
            $senderName = $isBuyer ? $order['buyer_username'] : $order['shop_name'];
            
            createNotification(
                $recipientId,
                'message',
                'New Message',
                $senderName . ' sent you a message for order #' . $order['order_number'],
                BASE_URL . '/order-chat.php?id=' . $orderId
            );
            
            // Redirect to prevent form resubmission
            header('Location: ' . BASE_URL . '/order-chat.php?id=' . $orderId);
            exit;
        } elseif (!$error) {
            $error = 'Please enter a message or attach a file';
        }
    }
}

// Mark messages as read
db()->query("
    UPDATE order_messages 
    SET is_read = 1 
    WHERE order_id = ? AND sender_id != ?
", [$orderId, $userId]);

// Get messages
$messages = db()->fetchAll("
    SELECT om.*, u.username, 
           CASE WHEN u.id = ? THEN 1 ELSE 0 END as is_mine
    FROM order_messages om
    JOIN users u ON om.sender_id = u.id
    WHERE om.order_id = ?
    ORDER BY om.created_at ASC
", [$userId, $orderId]);

$pageTitle = 'Order Chat #' . $order['order_number'] . ' - ' . PLATFORM_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Chat Area -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="fas fa-comments me-2"></i>Order Chat
                        </h5>
                        <small class="text-muted">Order #<?= $order['order_number'] ?></small>
                    </div>
                    <a href="<?= BASE_URL ?>/orders.php?view=<?= $orderId ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to Order
                    </a>
                </div>
                
                <div class="card-body chat-messages" id="chatMessages" style="height: 400px; overflow-y: auto; background: #f8f9fa;">
                    <?php if (empty($messages)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <p>No messages yet. Start the conversation!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-bubble <?= $msg['is_mine'] ? 'mine' : 'theirs' ?> mb-3">
                                <div class="message-content p-3 rounded-3 <?= $msg['is_mine'] ? 'bg-primary text-white ms-auto' : 'bg-white' ?>" 
                                     style="max-width: 75%; <?= $msg['is_mine'] ? 'margin-left: auto;' : '' ?>">
                                    <div class="message-header mb-1">
                                        <small class="<?= $msg['is_mine'] ? 'text-white-50' : 'text-muted' ?>">
                                            <strong><?= $msg['is_mine'] ? 'You' : sanitize($msg['username']) ?></strong>
                                            • <?= timeAgo($msg['created_at']) ?>
                                        </small>
                                    </div>
                                    <?php if ($msg['message']): ?>
                                        <p class="mb-0"><?= nl2br(sanitize($msg['message'])) ?></p>
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
                                                   class="btn btn-sm <?= $msg['is_mine'] ? 'btn-light' : 'btn-outline-primary' ?>" 
                                                   download="<?= sanitize($msg['attachment_name']) ?>">
                                                    <i class="fas fa-download me-1"></i>
                                                    <?= sanitize($msg['attachment_name']) ?>
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
                    
                    <?php if ($order['status'] === 'completed' || $order['status'] === 'refunded'): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            This order is <?= $order['status'] ?>. Chat is now closed.
                        </div>
                    <?php else: ?>
                        <form method="POST" action="" enctype="multipart/form-data" id="messageForm">
                            <?= csrfField() ?>
                            <div class="input-group">
                                <input type="text" class="form-control" name="message" id="messageInput" placeholder="Type your message..." autocomplete="off">
                                <label class="btn btn-outline-secondary" for="attachmentInput">
                                    <i class="fas fa-paperclip"></i>
                                </label>
                                <input type="file" id="attachmentInput" name="attachment" class="d-none" 
                                       accept=".jpg,.jpeg,.png,.gif,.pdf,.txt,.zip,.rar">
                                <button type="submit" name="send_message" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                            <div id="filePreview" class="mt-2 d-none">
                                <span class="badge bg-secondary">
                                    <i class="fas fa-file me-1"></i>
                                    <span id="fileName"></span>
                                    <button type="button" class="btn-close btn-close-white ms-2" onclick="clearFile()" style="font-size: 0.6rem;"></button>
                                </span>
                            </div>
                            <small class="text-muted">Allowed: JPG, PNG, GIF, PDF, TXT, ZIP, RAR (max 10MB)</small>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Order Info Sidebar -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-box me-2"></i>Order Details</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <?php if ($order['thumbnail']): ?>
                            <img src="<?= UPLOADS_URL ?>/<?= $order['thumbnail'] ?>" class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-box text-muted"></i>
                            </div>
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
                        <small class="text-muted">Total Amount</small>
                        <div class="fw-bold text-success"><?= formatCurrency($order['total_amount']) ?></div>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">Status</small>
                        <div>
                            <?php
                            $statusClass = match($order['status']) {
                                'pending' => 'warning',
                                'processing' => 'info',
                                'completed' => 'success',
                                'refunded' => 'secondary',
                                'disputed' => 'danger',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($order['status']) ?></span>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">Delivery Type</small>
                        <div><span class="badge bg-info">Manual Delivery</span></div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-2">
                        <small class="text-muted"><?= $isBuyer ? 'Seller' : 'Buyer' ?></small>
                        <div class="fw-bold">
                            <?= $isBuyer ? sanitize($order['shop_name']) : sanitize($order['buyer_username']) ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($isSeller && $order['status'] === 'processing'): ?>
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-check-circle me-2"></i>Mark as Delivered</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Once you've sent the product/files to the buyer, mark this order as delivered.
                    </p>
                    <form method="POST" action="<?= BASE_URL ?>/seller/orders.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="order_id" value="<?= $orderId ?>">
                        <button type="submit" name="mark_delivered" class="btn btn-success w-100">
                            <i class="fas fa-check me-2"></i>Mark as Delivered
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

// File preview
document.getElementById('attachmentInput').addEventListener('change', function() {
    const preview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    if (this.files.length > 0) {
        fileName.textContent = this.files[0].name;
        preview.classList.remove('d-none');
    } else {
        preview.classList.add('d-none');
    }
});

function clearFile() {
    document.getElementById('attachmentInput').value = '';
    document.getElementById('filePreview').classList.add('d-none');
}

// Create message HTML
function createMessageHTML(msg) {
    let bgClass = 'bg-white';
    let textClass = '';
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
    
    if (msg.is_mine) {
        bgClass = 'bg-primary text-white';
        textClass = 'text-white-50';
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
            <div class="message-content p-3 rounded-3 ${bgClass}" style="max-width: 75%; ${msg.is_mine ? 'margin-left: auto;' : ''}">
                <div class="message-header mb-1">
                    <small class="${textClass || 'text-muted'}">
                        <strong>${icon}${msg.is_mine ? 'You' : msg.sender} ${msg.role !== 'admin' ? roleLabel : ''}</strong>
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
    // If there's a file, let the form submit normally
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

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
