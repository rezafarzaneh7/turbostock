<?php
/**
 * HStore - Admin Categories Management
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
        if (isset($_POST['add_category'])) {
            $name = clean($_POST['name'] ?? '');
            $description = clean($_POST['description'] ?? '');
            $icon = clean($_POST['icon'] ?? 'fa-folder');
            
            if (empty($name)) {
                $error = 'Category name is required';
            } else {
                $slug = generateSlug($name);
                $existing = db()->fetch("SELECT id FROM categories WHERE slug = ?", [$slug]);
                if ($existing) {
                    $slug .= '-' . rand(100, 999);
                }
                
                db()->insert('categories', [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'icon' => $icon,
                    'status' => 'active'
                ]);
                
                logAdminAction('add_category', 'category', db()->lastInsertId());
                $success = 'Category added successfully';
            }
        }
        
        if (isset($_POST['update_category'])) {
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $name = clean($_POST['name'] ?? '');
            $description = clean($_POST['description'] ?? '');
            $icon = clean($_POST['icon'] ?? 'fa-folder');
            $status = clean($_POST['status'] ?? 'active');
            
            if (empty($name)) {
                $error = 'Category name is required';
            } else {
                db()->update('categories', [
                    'name' => $name,
                    'description' => $description,
                    'icon' => $icon,
                    'status' => $status
                ], 'id = ?', [$categoryId]);
                
                logAdminAction('update_category', 'category', $categoryId);
                $success = 'Category updated successfully';
            }
        }
        
        if (isset($_POST['delete_category'])) {
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $productCount = db()->count('products', 'category_id = ?', [$categoryId]);
            
            if ($productCount > 0) {
                $error = "Cannot delete category with {$productCount} products";
            } else {
                db()->delete('categories', 'id = ?', [$categoryId]);
                logAdminAction('delete_category', 'category', $categoryId);
                $success = 'Category deleted';
            }
        }
    }
}

// Get categories
$categories = db()->fetchAll("
    SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count
    FROM categories c
    ORDER BY c.sort_order, c.name
");

$pageTitle = 'Categories - Admin';
require_once __DIR__ . '/header.php';
?>

<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4">Categories Management</h4>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= sanitize($success) ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Categories List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Categories</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><i class="fas <?= sanitize($cat['icon']) ?> fa-lg"></i></td>
                                        <td><strong><?= sanitize($cat['name']) ?></strong></td>
                                        <td><code><?= sanitize($cat['slug']) ?></code></td>
                                        <td><?= $cat['product_count'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= $cat['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($cat['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#editModal<?= $cat['id'] ?>">
                                                Edit
                                            </button>
                                            <?php if ($cat['product_count'] == 0): ?>
                                                <form method="POST" action="" class="d-inline">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                    <button type="submit" name="delete_category" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Delete this category?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?= $cat['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Category</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Name</label>
                                                            <input type="text" class="form-control" name="name" required
                                                                   value="<?= sanitize($cat['name']) ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Description</label>
                                                            <textarea class="form-control" name="description" rows="2"><?= sanitize($cat['description']) ?></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Icon (FontAwesome)</label>
                                                            <input type="text" class="form-control" name="icon" 
                                                                   value="<?= sanitize($cat['icon']) ?>" placeholder="fa-folder">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Status</label>
                                                            <select class="form-select" name="status">
                                                                <option value="active" <?= $cat['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                                <option value="inactive" <?= $cat['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_category" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Add Category -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add New Category</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <?= csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Icon (FontAwesome class)</label>
                            <input type="text" class="form-control" name="icon" value="fa-folder" placeholder="fa-folder">
                            <small class="text-muted">e.g., fa-laptop, fa-gamepad, fa-book</small>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>Add Category
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
