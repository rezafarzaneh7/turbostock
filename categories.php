<?php
/**
 * HStore - Categories Page
 */
require_once __DIR__ . '/includes/init.php';

$categories = db()->fetchAll("
    SELECT c.*, 
           (SELECT COUNT(*) FROM products WHERE category_id = c.id AND status = 'active') as product_count
    FROM categories c
    WHERE c.status = 'active'
    ORDER BY c.sort_order, c.name
");

$pageTitle = 'Categories - ' . PLATFORM_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-4">
    <h4 class="fw-bold mb-4"><i class="fas fa-th-large me-2"></i>Browse Categories</h4>
    
    <div class="row g-4">
        <?php foreach ($categories as $cat): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= BASE_URL ?>/browse.php?category=<?= $cat['slug'] ?>" class="card h-100 text-decoration-none">
                    <div class="card-body text-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" 
                             style="width: 80px; height: 80px;">
                            <i class="fas <?= $cat['icon'] ?? 'fa-folder' ?> fa-2x text-primary"></i>
                        </div>
                        <h5 class="fw-bold text-dark"><?= sanitize($cat['name']) ?></h5>
                        <p class="text-muted small mb-0"><?= $cat['product_count'] ?> products</p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
