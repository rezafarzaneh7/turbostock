<?php
/**
 * HStore - Core Functions
 */

// URL Helper Functions
function productUrl($id) {
    return BASE_URL . '/product/' . $id;
}

function storeUrl($id) {
    return BASE_URL . '/store/' . $id;
}

function categoryUrl($slug) {
    return BASE_URL . '/category/' . $slug;
}

function orderUrl($id) {
    return BASE_URL . '/order/' . $id;
}

function sellerOrderUrl($id) {
    return BASE_URL . '/seller/order/' . $id;
}

// Start session if not already started
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Generate CSRF Token
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// Verify CSRF Token
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// CSRF Token Input Field
function csrfField() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generateCSRFToken() . '">';
}

// Sanitize Input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Clean input for database
function clean($input) {
    if (is_array($input)) {
        return array_map('clean', $input);
    }
    return trim($input);
}

// Truncate string to specified length
function truncate($string, $length = 100, $append = '...') {
    $string = trim($string);
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length) . $append;
}

// Hash Password
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
}

// Verify Password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate Random String
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Generate Order Number
function generateOrderNumber() {
    return 'ORD-' . strtoupper(substr(uniqid(), -6)) . '-' . rand(1000, 9999);
}

// Generate Slug
function generateSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check user role
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Check if admin
function isAdmin() {
    return hasRole(ROLE_ADMIN);
}

// Check if seller
function isSeller() {
    return hasRole(ROLE_SELLER);
}

// Check if buyer
function isBuyer() {
    return hasRole(ROLE_BUYER);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return db()->fetch("SELECT * FROM users WHERE id = ?", [getCurrentUserId()]);
}

// Redirect
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Redirect with message
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    redirect($url);
}

// Get flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// Display flash message
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $type = $flash['type'];
        $alertClass = match($type) {
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            default => 'alert-info'
        };
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        echo sanitize($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

// Format currency
function formatCurrency($amount, $decimals = 2) {
    return number_format((float)$amount, $decimals) . ' USDT';
}

// Format date
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Format datetime
function formatDateTime($date, $format = 'M d, Y H:i') {
    return date($format, strtotime($date));
}

// Time ago
function timeAgo($datetime) {
    if (empty($datetime)) return 'unknown';
    
    $time = strtotime($datetime);
    if ($time === false) return 'unknown';
    
    $now = time();
    $diff = $now - $time;
    
    // Handle future dates or negative diff (timezone issues)
    if ($diff < 0) $diff = 0;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    if ($diff < 2592000) return floor($diff / 604800) . ' weeks ago';
    if ($diff < 31536000) return floor($diff / 2592000) . ' months ago';
    
    return formatDate($datetime);
}

// Get setting
function getSetting($key, $default = null) {
    static $settings = null;
    
    if ($settings === null) {
        $results = db()->fetchAll("SELECT setting_key, setting_value, setting_type FROM settings");
        $settings = [];
        foreach ($results as $row) {
            $value = $row['setting_value'];
            switch ($row['setting_type']) {
                case 'number':
                    $value = (float) $value;
                    break;
                case 'boolean':
                    $value = (bool) $value;
                    break;
                case 'json':
                    $value = json_decode($value, true);
                    break;
            }
            $settings[$row['setting_key']] = $value;
        }
    }
    
    return $settings[$key] ?? $default;
}

// Update setting
function updateSetting($key, $value) {
    $exists = db()->fetch("SELECT id FROM settings WHERE setting_key = ?", [$key]);
    if ($exists) {
        db()->update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
    }
}

// Upload file
function uploadFile($file, $directory, $allowedTypes = null) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error: ' . $file['error']];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedTypes = $allowedTypes ?? ALLOWED_IMAGE_TYPES;
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    $uploadDir = UPLOADS_PATH . '/' . $directory;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generateRandomString(16) . '.' . strtolower($extension);
    $filepath = $uploadDir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $directory . '/' . $filename,
            'url' => UPLOADS_URL . '/' . $directory . '/' . $filename
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

// Delete file
function deleteFile($path) {
    $fullPath = UPLOADS_PATH . '/' . $path;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

// Encrypt data
function encrypt($data) {
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
    return base64_encode($iv . $encrypted);
}

// Decrypt data
function decrypt($data) {
    $data = base64_decode($data);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
}

// Log admin action
function logAdminAction($action, $targetType = null, $targetId = null, $oldValue = null, $newValue = null) {
    if (!isAdmin()) return;
    
    db()->insert('admin_logs', [
        'admin_id' => getCurrentUserId(),
        'action' => $action,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'old_value' => $oldValue ? json_encode($oldValue) : null,
        'new_value' => $newValue ? json_encode($newValue) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

// Create notification
function createNotification($userId, $type, $title, $message = null, $link = null) {
    db()->insert('notifications', [
        'user_id' => $userId,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'link' => $link
    ]);
}

// Get unread notifications count
function getUnreadNotificationsCount($userId) {
    return db()->count('notifications', 'user_id = ? AND is_read = 0', [$userId]);
}

// Get user notifications
function getUserNotifications($userId, $limit = 10) {
    return db()->fetchAll(
        "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
        [$userId, $limit]
    );
}

// Pagination helper
function paginate($totalItems, $currentPage, $perPage = ITEMS_PER_PAGE) {
    $totalPages = ceil($totalItems / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

// Render pagination
function renderPagination($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) return '';
    
    $html = '<nav><ul class="pagination justify-content-center">';
    
    // Previous
    if ($pagination['has_prev']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($pagination['current_page'] - 1) . '">&laquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
    }
    
    // Pages
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=1">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $pagination['current_page']) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    if ($end < $pagination['total_pages']) {
        if ($end < $pagination['total_pages'] - 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $pagination['total_pages'] . '">' . $pagination['total_pages'] . '</a></li>';
    }
    
    // Next
    if ($pagination['has_next']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($pagination['current_page'] + 1) . '">&raquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

// Render star rating
function renderStars($rating, $showNumber = true) {
    $rating = (float) $rating;
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    
    $html = '<span class="star-rating">';
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fas fa-star text-warning"></i>';
    }
    if ($halfStar) {
        $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="far fa-star text-warning"></i>';
    }
    if ($showNumber) {
        $html .= ' <span class="rating-number">(' . number_format($rating, 1) . ')</span>';
    }
    $html .= '</span>';
    
    return $html;
}

// Get seller verification badge
function getVerificationBadge($status) {
    return match($status) {
        'verified' => '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Verified Seller</span>',
        'pending' => '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Verification Pending</span>',
        default => '<span class="badge bg-secondary"><i class="fas fa-user"></i> Unverified Seller</span>'
    };
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirectWithMessage(BASE_URL . '/auth/login.php', 'Please login to continue', 'warning');
    }
}

// Require role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        redirectWithMessage(BASE_URL, 'You do not have permission to access this page', 'error');
    }
}

// Require admin
function requireAdmin() {
    requireRole(ROLE_ADMIN);
}

// Require seller
function requireSeller() {
    requireRole(ROLE_SELLER);
}

// JSON response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Error JSON response
function jsonError($message, $statusCode = 400) {
    jsonResponse(['success' => false, 'error' => $message], $statusCode);
}

// Success JSON response
function jsonSuccess($data = [], $message = 'Success') {
    jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}
