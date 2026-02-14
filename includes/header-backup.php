<?php
/**
 * HStore - Header Template
 */
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/init.php';
}

$currentUser = getCurrentUser();
$pageTitle = $pageTitle ?? 'HStore - Digital Marketplace';
?>
<!DOCTYPE html>
<html lang="<?= lang()->getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
     <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/favicon.png">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/favicon.png">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= sanitize($pageDescription ?? 'HStore - Your trusted digital marketplace for accounts, software, and digital products. Secure payments with crypto.') ?>">
    <meta name="keywords" content="<?= sanitize($pageKeywords ?? 'digital marketplace, buy accounts, sell accounts, crypto payments, digital products, HStore') ?>">
    <meta name="author" content="<?= getSetting('site_name', PLATFORM_NAME) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $canonicalUrl ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?= $ogType ?? 'website' ?>">
    <meta property="og:url" content="<?= $canonicalUrl ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:title" content="<?= sanitize($pageTitle) ?>">
    <meta property="og:description" content="<?= sanitize($pageDescription ?? 'HStore - Your trusted digital marketplace for accounts, software, and digital products.') ?>">
    <meta property="og:image" content="<?= $ogImage ?? ASSETS_URL . '/images/og-default.png' ?>">
    <meta property="og:site_name" content="<?= getSetting('site_name', PLATFORM_NAME) ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= sanitize($pageTitle) ?>">
    <meta name="twitter:description" content="<?= sanitize($pageDescription ?? 'HStore - Your trusted digital marketplace for accounts, software, and digital products.') ?>">
    <meta name="twitter:image" content="<?= $ogImage ?? ASSETS_URL . '/images/og-default.png' ?>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
    
    <?php if (isset($extraCss)): ?>
        <?= $extraCss ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
              <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>">
                <img src="<?= BASE_URL ?>/logo.png" alt="<?= getSetting('site_name', PLATFORM_NAME) ?>" height="35" class="me-2">
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <!-- Search Form -->
                <form class="d-flex mx-auto my-2 my-lg-0" action="<?= BASE_URL ?>/search.php" method="GET" style="max-width: 400px; width: 100%;">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" placeholder="<?= __('search_placeholder') ?>" 
                               value="<?= sanitize($_GET['q'] ?? '') ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                <!-- Navigation Links -->
                <ul class="navbar-nav ms-auto align-items-center">
                    <!-- Language Selector -->
                    <li class="nav-item dropdown">
                        <?php $currentLang = lang()->getLanguageInfo(); ?>
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <span class="me-1"><?= $currentLang['flag'] ?></span>
                            <span class="d-none d-md-inline"><?= $currentLang['native'] ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach (lang()->getAvailableLanguages() as $code => $langInfo): ?>
                                <li>
                                    <a class="dropdown-item <?= $code === lang()->getCurrentLanguage() ? 'active' : '' ?>" 
                                       href="?lang=<?= $code ?>">
                                        <span class="me-2"><?= $langInfo['flag'] ?></span>
                                        <?= $langInfo['native'] ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/browse.php">
                            <i class="fas fa-th-large me-1"></i> <?= __('browse') ?>
                        </a>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                        <!-- Notifications -->
                        <li class="nav-item dropdown">
                            <?php $unreadCount = getUnreadNotificationsCount(getCurrentUserId()); ?>
                            <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <?php if ($unreadCount > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end notification-dropdown">
                                <li><h6 class="dropdown-header"><?= __('notifications') ?></h6></li>
                                <?php 
                                $notifications = getUserNotifications(getCurrentUserId(), 5);
                                if (empty($notifications)): 
                                ?>
                                    <li><span class="dropdown-item-text text-muted"><?= __('no_notifications') ?></span></li>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notif): ?>
                                        <li>
                                            <a class="dropdown-item <?= $notif['is_read'] ? '' : 'fw-bold' ?>" 
                                               href="<?= $notif['link'] ?? '#' ?>">
                                                <small class="text-muted"><?= timeAgo($notif['created_at']) ?></small><br>
                                                <?= sanitize($notif['title']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-center" href="<?= BASE_URL ?>/notifications.php">
                                            <?= __('view_all') ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        
                        <!-- Wallet -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/wallet.php">
                                <i class="fas fa-wallet me-1"></i>
                                <span class="badge bg-success">
                                    <?= formatCurrency(paymentHandler()->getBalance(getCurrentUserId())) ?>
                                </span>
                            </a>
                        </li>
                        
                        <!-- User Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>
                                <?= sanitize($currentUser['username']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if (isAdmin()): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?= BASE_URL ?>/admin/">
                                            <i class="fas fa-cog me-2"></i> <?= __('admin_panel') ?>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                
                                <?php if (isSeller()): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?= BASE_URL ?>/seller/">
                                            <i class="fas fa-store me-2"></i> <?= __('dashboard') ?>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/profile.php">
                                        <i class="fas fa-user me-2"></i> <?= __('settings') ?>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/orders.php">
                                        <i class="fas fa-shopping-bag me-2"></i> <?= __('my_orders') ?>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/wallet.php">
                                        <i class="fas fa-wallet me-2"></i> <?= __('wallet') ?>
                                    </a>
                                </li>
                                 <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/support.php">
                                        <i class="fas fa-headset me-2"></i> <?= __('support') ?>
                                    </a>
                                </li>
                                
                                <?php if (isBuyer()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?= BASE_URL ?>/become-seller.php">
                                            <i class="fas fa-user-plus me-2"></i> <?= __('become_seller') ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> <?= __('logout') ?>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> <?= __('login') ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm ms-2" href="<?= BASE_URL ?>/auth/register.php">
                                <i class="fas fa-user-plus me-1"></i> <?= __('register') ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="main-content">
        <?php displayFlashMessage(); ?>
