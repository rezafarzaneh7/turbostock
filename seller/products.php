<?php
/**
 * HStore - Seller Products Management
 */
require_once __DIR__ . '/../includes/init.php';
requireSeller();

$userId = getCurrentUserId();
$seller = db()->fetch("SELECT * FROM sellers WHERE user_id = ?", [$userId]);

$action = clean($_GET['action'] ?? '');
$productId = (int) ($_GET['id'] ?? 0);

// Get categories
$categories = db()->fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order");

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request';
    } else {
        $name = clean($_POST['name'] ?? '');
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $price = (float) ($_POST['price'] ?? 0);
        $description = clean($_POST['description'] ?? '');
        $shortDescription = clean($_POST['short_description'] ?? '');
        $stockQuantity = (int) ($_POST['stock_quantity'] ?? -1);
        $deliveryType = clean($_POST['delivery_type'] ?? 'manual');
        $deliveryData = clean($_POST['delivery_data'] ?? '');
        // Sellers cannot set status to active directly - needs admin approval
        $status = 'pending';
        
        // Validation
        if (empty($name)) {
            $error = 'Product name is required';
        } elseif ($categoryId <= 0) {
            $error = 'Please select a category';
        } elseif ($price <= 0) {
            $error = 'Price must be greater than 0';
        } elseif ($deliveryType === 'auto' && empty($deliveryData)) {
            $error = 'Delivery data is required for auto delivery';
        } else {
            $slug = generateSlug($name);
            
            // Handle thumbnail upload
            $thumbnail = null;
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadFile($_FILES['thumbnail'], 'products');
                if ($uploadResult['success']) {
                    $thumbnail = $uploadResult['path'];
                } else {
                    $error = $uploadResult['error'];
                }
            }
            
            if (!$error) {
                // For auto delivery, auto-calculate stock from delivery data lines
                if ($deliveryType === 'auto' && !empty($deliveryData)) {
                    $lines = array_filter(array_map('trim', explode("\n", $deliveryData)));
                    $stockQuantity = count($lines); // Each line = 1 stock item
                } elseif ($deliveryType === 'manual') {
                    // Manual delivery = unlimited stock (seller delivers manually)
                    $stockQuantity = -1;
                }
                
                $productData = [
                    'seller_id' => $seller['id'],
                    'category_id' => $categoryId,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'short_description' => $shortDescription,
                    'price' => $price,
                    'stock_quantity' => $stockQuantity,
                    'delivery_type' => $deliveryType,
                    'delivery_data' => $deliveryData,
                    'status' => $status
                ];
                
                if ($thumbnail) {
                    $productData['thumbnail'] = $thumbnail;
                }
                
                try {
                    if ($action === 'edit' && $productId) {
                        // Update existing product
                        $existing = db()->fetch("SELECT * FROM products WHERE id = ? AND seller_id = ?", [$productId, $seller['id']]);
                        if (!$existing) {
                            $error = 'Product not found';
                        } else {
                            unset($productData['seller_id']);
                            // If product was active and is being edited, set back to pending for re-approval
                            if ($existing['status'] === 'active') {
                                $productData['status'] = 'pending';
                            }
                            db()->update('products', $productData, 'id = ?', [$productId]);
                            $success = 'Product updated successfully! It will be reviewed by admin.';
                        }
                    } else {
                        // Create new product - always pending for admin approval
                        $productData['status'] = 'pending';
                        $newId = db()->insert('products', $productData);
                        redirectWithMessage(BASE_URL . '/seller/products.php', 'Product created successfully! It will be reviewed by admin before going live.', 'success');
                    }
                } catch (Exception $e) {
                    error_log("Product save error: " . $e->getMessage());
                    $error = 'Failed to save product';
                }
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && $productId) {
    $product = db()->fetch("SELECT * FROM products WHERE id = ? AND seller_id = ?", [$productId, $seller['id']]);
    if ($product) {
        db()->delete('products', 'id = ?', [$productId]);
        redirectWithMessage(BASE_URL . '/seller/products.php', 'Product deleted successfully', 'success');
    }
}

// Get product for editing
$editProduct = null;
if ($action === 'edit' && $productId) {
    $editProduct = db()->fetch("SELECT * FROM products WHERE id = ? AND seller_id = ?", [$productId, $seller['id']]);
    if (!$editProduct) {
        redirectWithMessage(BASE_URL . '/seller/products.php', 'Product not found', 'error');
    }
}

// Get products list
$page = max(1, (int) ($_GET['page'] ?? 1));
$statusFilter = clean($_GET['status'] ?? '');

$where = "seller_id = ?";
$params = [$seller['id']];

if ($statusFilter) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}

$totalProducts = db()->fetch("SELECT COUNT(*) as count FROM products WHERE {$where}", $params)['count'];
$pagination = paginate($totalProducts, $page, 10);

$products = db()->fetchAll("
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.{$where}
    ORDER BY p.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

$pageTitle = 'Products - Seller Dashboard';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 col-xl-2 mb-4">
            <div class="sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link" href="<?= BASE_URL ?>/seller/">
                        <i class="fas fa-tachometer-alt"></i> <?= __('dashboard') ?>
                    </a>
                    <a class="nav-link active" href="<?= BASE_URL ?>/seller/products.php">
                        <i class="fas fa-box"></i> <?= __('products') ?>
                    </a>
                    <a class="nav-link" href="<?= BASE_URL ?>/seller/orders.php">
                        <i class="fas fa-shopping-cart"></i> <?= __('orders') ?>
                    </a>
                    <a class="nav-link" href="<?= BASE_URL ?>/seller/verification.php">
                        <i class="fas fa-check-circle"></i> <?= __('verification') ?>
                    </a>
                    <a class="nav-link" href="<?= BASE_URL ?>/seller/settings.php">
                        <i class="fas fa-cog"></i> <?= __('shop_settings') ?>
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9 col-xl-10">
            <?php if ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit Product Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-<?= $action === 'edit' ? 'edit' : 'plus' ?> me-2"></i>
                            <?= $action === 'edit' ? __('edit_product') : __('add_new_product') ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= sanitize($error) ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= sanitize($success) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <?= csrfField() ?>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label"><?= __('product_name') ?> <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name" required
                                               value="<?= sanitize($editProduct['name'] ?? $_POST['name'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><?= __('category') ?> <span class="text-danger">*</span></label>
                                            <select class="form-select" name="category_id" required>
                                                <option value=""><?= __('select_category') ?></option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= $cat['id'] ?>" 
                                                        <?= ($editProduct['category_id'] ?? $_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                                        <?= sanitize($cat['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><?= __('price') ?> (USDT) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="price" step="0.01" min="0.01" required
                                                   value="<?= $editProduct['price'] ?? $_POST['price'] ?? '' ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><?= __('short_description') ?></label>
                                        <input type="text" class="form-control" name="short_description" maxlength="500"
                                               value="<?= sanitize($editProduct['short_description'] ?? $_POST['short_description'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><?= __('full_description') ?></label>
                                        <textarea class="form-control" name="description" rows="5"><?= sanitize($editProduct['description'] ?? $_POST['description'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?= __('product_image') ?></label>
                                        <input type="file" class="form-control" name="thumbnail" accept="image/*" data-preview="imagePreview">
                                        <?php if ($editProduct && $editProduct['thumbnail']): ?>
                                            <img src="<?= UPLOADS_URL ?>/<?= $editProduct['thumbnail'] ?>" class="img-fluid mt-2 rounded" id="imagePreview">
                                        <?php else: ?>
                                            <img src="" class="img-fluid mt-2 rounded" id="imagePreview" style="display: none;">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><?= __('status') ?></label>
                                        <?php if ($editProduct): ?>
                                            <div class="form-control-plaintext">
                                                <?php if ($editProduct['status'] === 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php elseif ($editProduct['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning">Pending Approval</span>
                                                <?php elseif ($editProduct['status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">Status is managed by admin</small>
                                        <?php else: ?>
                                            <div class="form-control-plaintext">
                                                <span class="badge bg-warning">Pending Approval</span>
                                            </div>
                                            <small class="text-muted">New products require admin approval</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h6 class="fw-bold mb-3"><?= __('delivery_settings') ?></h6>
                            
                            <div class="mb-3">
                                <label class="form-label"><?= __('delivery_type') ?> <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="delivery_type" value="auto" id="deliveryAuto"
                                           <?= ($editProduct['delivery_type'] ?? 'auto') === 'auto' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="deliveryAuto">
                                        <strong><?= __('auto_delivery') ?></strong> - <?= __('auto_delivery_desc') ?>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="delivery_type" value="manual" id="deliveryManual"
                                           <?= ($editProduct['delivery_type'] ?? '') === 'manual' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="deliveryManual">
                                        <strong><?= __('manual_delivery_type') ?></strong> - <?= __('manual_delivery_desc') ?>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="deliveryDataSection">
                                <label class="form-label"><?= __('delivery_data') ?></label>
                                <textarea class="form-control" name="delivery_data" rows="6" 
                                          placeholder="Enter one item per line. Example:&#10;account1@email.com:password123&#10;account2@email.com:password456&#10;account3@email.com:password789"><?= sanitize($editProduct['delivery_data'] ?? $_POST['delivery_data'] ?? '') ?></textarea>
                                <div class="alert alert-info mt-2 mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Per-Line Stock System:</strong>
                                    <ul class="mb-0 mt-1">
                                        <li>Each line = 1 stock item</li>
                                        <li>When buyer purchases, they receive only their line(s)</li>
                                        <li>Stock quantity is auto-calculated from number of lines</li>
                                        <li>Example: 10 lines = 10 items in stock</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i><?= $action === 'edit' ? __('update_product') : __('create_product') ?>
                                </button>
                                <a href="<?= BASE_URL ?>/seller/products.php" class="btn btn-outline-secondary"><?= __('cancel') ?></a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- Products List -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0"><?= __('my_products') ?></h4>
                    <a href="<?= BASE_URL ?>/seller/products.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i><?= __('add_product') ?>
                    </a>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><?= $totalProducts ?> products</span>
                            <select class="form-select form-select-sm" style="width: auto;" 
                                    onchange="window.location.href='<?= BASE_URL ?>/seller/products.php?status='+this.value">
                                <option value=""><?= __('all_status') ?></option>
                                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>><?= __('active') ?></option>
                                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>><?= __('inactive') ?></option>
                                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>><?= __('pending') ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($products)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted"><?= __('no_products_yet') ?></p>
                                <a href="<?= BASE_URL ?>/seller/products.php?action=add" class="btn btn-primary"><?= __('add_first_product') ?></a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th><?= __('product') ?></th>
                                            <th><?= __('category') ?></th>
                                            <th><?= __('price') ?></th>
                                            <th><?= __('stock') ?></th>
                                            <th><?= __('sales') ?></th>
                                            <th><?= __('status') ?></th>
                                            <th><?= __('actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($product['thumbnail']): ?>
                                                            <img src="<?= UPLOADS_URL ?>/<?= $product['thumbnail'] ?>" 
                                                                 class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                        <?php endif; ?>
                                                        <div>
                                                            <a href="<?= productUrl($product['id']) ?>" class="text-dark text-decoration-none fw-bold">
                                                                <?= sanitize(substr($product['name'], 0, 40)) ?>
                                                            </a>
                                                            <small class="text-muted d-block">
                                                                <?= $product['delivery_type'] === 'auto' ? 'Auto' : 'Manual' ?> delivery
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= sanitize($product['category_name']) ?></td>
                                                <td class="fw-bold"><?= formatCurrency($product['price']) ?></td>
                                                <td><?= $product['stock_quantity'] == -1 ? '∞' : $product['stock_quantity'] ?></td>
                                                <td><?= $product['total_sales'] ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                        <?= ucfirst($product['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="<?= BASE_URL ?>/seller/products.php?action=edit&id=<?= $product['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/seller/products.php?action=delete&id=<?= $product['id'] ?>" 
                                                       class="btn btn-sm btn-outline-danger" data-confirm="Are you sure you want to delete this product?">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-4">
                    <?= renderPagination($pagination, BASE_URL . '/seller/products.php' . ($statusFilter ? '?status=' . $statusFilter : '')) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
