<?php
/**
 * HStore - Admin Products Management
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request';
    } else {
        $productId = (int) ($_POST['product_id'] ?? 0);
        
        if (isset($_POST['update_status'])) {
            $newStatus = clean($_POST['status'] ?? '');
            if (in_array($newStatus, ['active', 'inactive', 'pending', 'rejected'])) {
                // Get product and seller info for notification
                $product = db()->fetch("
                    SELECT p.*, s.user_id as seller_user_id 
                    FROM products p 
                    JOIN sellers s ON p.seller_id = s.id 
                    WHERE p.id = ?
                ", [$productId]);
                
                db()->update('products', ['status' => $newStatus], 'id = ?', [$productId]);
                logAdminAction('update_product_status', 'product', $productId, null, ['status' => $newStatus]);
                
                // Notify seller about status change
                if ($product) {
                    if ($newStatus === 'active') {
                        createNotification(
                            $product['seller_user_id'],
                            'product',
                            'Product Approved',
                            'Your product "' . $product['name'] . '" has been approved and is now live!',
                            productUrl($productId)
                        );
                    } elseif ($newStatus === 'rejected') {
                        createNotification(
                            $product['seller_user_id'],
                            'product',
                            'Product Rejected',
                            'Your product "' . $product['name'] . '" has been rejected. Please review and resubmit.',
                            BASE_URL . '/seller/products.php?action=edit&id=' . $productId
                        );
                    }
                }
                
                $success = 'Product status updated';
            }
        }
        
        if (isset($_POST['delete'])) {
            db()->delete('products', 'id = ?', [$productId]);
            logAdminAction('delete_product', 'product', $productId);
            $success = 'Product deleted';
        }
    }
}

// View single product
$viewProductId = (int) ($_GET['view'] ?? 0);
$viewProduct = null;

if ($viewProductId) {
    $viewProduct = db()->fetch("
        SELECT p.*, s.shop_name, s.user_id as seller_user_id, s.verification_status,
               c.name as category_name, u.username as seller_username, u.email as seller_email
        FROM products p
        JOIN sellers s ON p.seller_id = s.id
        JOIN categories c ON p.category_id = c.id
        JOIN users u ON s.user_id = u.id
        WHERE p.id = ?
    ", [$viewProductId]);
}

// Get products
$page = max(1, (int) ($_GET['page'] ?? 1));
$search = clean($_GET['search'] ?? '');
$statusFilter = clean($_GET['status'] ?? '');
$categoryFilter = (int) ($_GET['category'] ?? 0);

$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (p.name LIKE ? OR s.shop_name LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($statusFilter) {
    $where .= " AND p.status = ?";
    $params[] = $statusFilter;
}

if ($categoryFilter) {
    $where .= " AND p.category_id = ?";
    $params[] = $categoryFilter;
}

$totalProducts = db()->fetch("
    SELECT COUNT(*) as count FROM products p 
    JOIN sellers s ON p.seller_id = s.id
    WHERE {$where}
", $params)['count'];

$pagination = paginate($totalProducts, $page, ADMIN_ITEMS_PER_PAGE);

$products = db()->fetchAll("
    SELECT p.*, s.shop_name, s.user_id as seller_user_id, c.name as category_name, u.username as seller_username, u.email as seller_email
    FROM products p
    JOIN sellers s ON p.seller_id = s.id
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON s.user_id = u.id
    WHERE {$where}
    ORDER BY p.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

$categories = db()->fetchAll("SELECT * FROM categories ORDER BY name");

$pageTitle = 'Products - Admin';
require_once __DIR__ . '/header.php';
?>

<div class="container-fluid py-4">
    <?php if ($viewProduct): ?>
        <!-- View Single Product -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Review Product: <?= sanitize($viewProduct['name']) ?></h4>
            <a href="<?= BASE_URL ?>/admin/products.php<?= $statusFilter ? '?status=' . $statusFilter : '' ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Products
            </a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= sanitize($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= sanitize($success) ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Product Details -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Product Details</h5>
                        <span class="badge bg-<?= $viewProduct['status'] === 'active' ? 'success' : ($viewProduct['status'] === 'pending' ? 'warning' : 'secondary') ?>" style="font-size: 1rem;">
                            <?= ucfirst($viewProduct['status']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <?php if ($viewProduct['thumbnail']): ?>
                                    <img src="<?= UPLOADS_URL ?>/<?= $viewProduct['thumbnail'] ?>" class="img-fluid rounded" alt="Product">
                                <?php else: ?>
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="fas fa-box fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <h4 class="fw-bold"><?= sanitize($viewProduct['name']) ?></h4>
                                <p class="text-muted mb-2">
                                    <span class="badge bg-secondary"><?= sanitize($viewProduct['category_name']) ?></span>
                                    <span class="ms-2">Delivery: <?= $viewProduct['delivery_type'] === 'auto' ? 'Instant' : 'Manual' ?></span>
                                </p>
                                <h3 class="text-success fw-bold"><?= formatCurrency($viewProduct['price']) ?></h3>
                                
                                <div class="row mt-3">
                                    <div class="col-6">
                                        <small class="text-muted">Stock</small>
                                        <p class="fw-bold mb-0"><?= $viewProduct['stock_quantity'] == -1 ? 'Unlimited' : $viewProduct['stock_quantity'] ?></p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Total Sales</small>
                                        <p class="fw-bold mb-0"><?= $viewProduct['total_sales'] ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($viewProduct['short_description']): ?>
                            <hr>
                            <h6 class="fw-bold">Short Description</h6>
                            <p><?= sanitize($viewProduct['short_description']) ?></p>
                        <?php endif; ?>
                        
                        <?php if ($viewProduct['description']): ?>
                            <hr>
                            <h6 class="fw-bold">Full Description</h6>
                            <p><?= nl2br(sanitize($viewProduct['description'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- DELIVERY DATA - IMPORTANT FOR ADMIN REVIEW -->
                <div class="card mb-4 border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Delivery Data (What Buyer Will Receive)</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($viewProduct['delivery_data']): ?>
                            <div class="alert alert-warning mb-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Review this carefully!</strong> This is what the buyer will receive after purchase.
                            </div>
                            <div class="bg-dark text-light p-3 rounded" style="font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto;">
<?= sanitize($viewProduct['delivery_data']) ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                No delivery data set. This is a <strong>manual delivery</strong> product - seller will deliver manually.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Seller Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Seller Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong><?= sanitize($viewProduct['shop_name']) ?></strong></p>
                        <p class="text-muted mb-2">@<?= sanitize($viewProduct['seller_username']) ?></p>
                        <p class="text-muted mb-2"><?= sanitize($viewProduct['seller_email']) ?></p>
                        <?= getVerificationBadge($viewProduct['verification_status']) ?>
                        <hr>
                        <a href="<?= storeUrl($viewProduct['seller_id']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-external-link-alt me-2"></i>View Store
                        </a>
                    </div>
                </div>
                
                <!-- Admin Actions -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Admin Actions</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <?= csrfField() ?>
                            <input type="hidden" name="product_id" value="<?= $viewProduct['id'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Change Status</label>
                                <select class="form-select" name="status">
                                    <option value="active" <?= $viewProduct['status'] === 'active' ? 'selected' : '' ?>>✅ Approve (Active)</option>
                                    <option value="pending" <?= $viewProduct['status'] === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
                                    <option value="rejected" <?= $viewProduct['status'] === 'rejected' ? 'selected' : '' ?>>❌ Reject</option>
                                    <option value="inactive" <?= $viewProduct['status'] === 'inactive' ? 'selected' : '' ?>>🚫 Inactive</option>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="update_status" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Status
                                </button>
                                <?php if ($viewProduct['status'] === 'pending'): ?>
                                    <button type="submit" name="update_status" class="btn btn-success" onclick="this.form.status.value='active'">
                                        <i class="fas fa-check me-2"></i>Quick Approve
                                    </button>
                                    <button type="submit" name="update_status" class="btn btn-danger" onclick="this.form.status.value='rejected'">
                                        <i class="fas fa-times me-2"></i>Quick Reject
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to DELETE this product permanently?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="product_id" value="<?= $viewProduct['id'] ?>">
                            <button type="submit" name="delete" class="btn btn-outline-danger w-100">
                                <i class="fas fa-trash me-2"></i>Delete Product
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Product Meta -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Product Info</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">Product ID</small>
                            <p class="fw-bold mb-0">#<?= $viewProduct['id'] ?></p>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Created</small>
                            <p class="fw-bold mb-0"><?= formatDateTime($viewProduct['created_at']) ?></p>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Last Updated</small>
                            <p class="fw-bold mb-0"><?= formatDateTime($viewProduct['updated_at']) ?></p>
                        </div>
                        <div class="mb-0">
                            <small class="text-muted">Rating</small>
                            <p class="mb-0"><?= renderStars($viewProduct['rating_average']) ?> (<?= $viewProduct['rating_count'] ?>)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
    <!-- Products List -->
    <h4 class="fw-bold mb-4">Products Management</h4>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= sanitize($success) ?></div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" placeholder="Search product or seller" 
                           value="<?= sanitize($search) ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                                <?= sanitize($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Products Table -->
    <div class="card">
        <div class="card-header">
            <span><?= number_format($totalProducts) ?> products</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Seller</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Sales</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= $product['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($product['thumbnail']): ?>
                                            <img src="<?= UPLOADS_URL ?>/<?= $product['thumbnail'] ?>" 
                                                 class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php endif; ?>
                                        <a href="<?= productUrl($product['id']) ?>" target="_blank">
                                            <?= sanitize(substr($product['name'], 0, 30)) ?>...
                                        </a>
                                    </div>
                                </td>
                                <td><?= sanitize($product['shop_name']) ?></td>
                                <td><?= sanitize($product['category_name']) ?></td>
                                <td><?= formatCurrency($product['price']) ?></td>
                                <td><?= $product['total_sales'] ?></td>
                                <td><?= renderStars($product['rating_average'], false) ?></td>
                                <td>
                                    <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : ($product['status'] === 'pending' ? 'warning' : 'secondary') ?>">
                                        <?= ucfirst($product['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/admin/products.php?view=<?= $product['id'] ?>" class="btn btn-sm btn-outline-info me-1" title="Review Product">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" action="" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <select class="form-select form-select-sm d-inline-block" name="status" style="width: auto;">
                                            <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                            <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                            <option value="pending" <?= $product['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="rejected" <?= $product['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                                        <button type="submit" name="delete" class="btn btn-sm btn-danger" 
                                                onclick="return confirm('Delete this product?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <?= renderPagination($pagination, BASE_URL . '/admin/products.php?' . http_build_query(array_filter(['search' => $search, 'status' => $statusFilter, 'category' => $categoryFilter]))) ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
