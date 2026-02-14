<?php
/**
 * TurboStock - Header Template (New Design)
 */
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/init.php';
}

$currentUser = getCurrentUser();
$pageTitle = $pageTitle ?? 'TurboStock - Digital Marketplace';
$bodyClass = $bodyClass ?? 'page-turbostock-source';
$themeClass = $themeClass ?? 'theme-light';
$isHomepage = $bodyClass === 'page-turbostock-source';
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
    <meta name="description" content="<?= sanitize($pageDescription ?? 'TurboStock - The fastest marketplace for digital goods. Accounts, software, game items, and more. Instant delivery, secure payments.') ?>">
    <meta name="keywords" content="<?= sanitize($pageKeywords ?? 'digital marketplace, buy accounts, sell accounts, crypto payments, digital products, TurboStock') ?>">
    <meta name="author" content="<?= getSetting('site_name', PLATFORM_NAME) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $canonicalUrl ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?= $ogType ?? 'website' ?>">
    <meta property="og:url" content="<?= $canonicalUrl ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:title" content="<?= sanitize($pageTitle) ?>">
    <meta property="og:description" content="<?= sanitize($pageDescription ?? 'TurboStock - The fastest marketplace for digital goods.') ?>">
    <meta property="og:image" content="<?= $ogImage ?? ASSETS_URL . '/images/og-default.png' ?>">
    <meta property="og:site_name" content="<?= getSetting('site_name', PLATFORM_NAME) ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= sanitize($pageTitle) ?>">
    <meta name="twitter:description" content="<?= sanitize($pageDescription ?? 'TurboStock - The fastest marketplace for digital goods.') ?>">
    <meta name="twitter:image" content="<?= $ogImage ?? ASSETS_URL . '/images/og-default.png' ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- New Design CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style-new-design.css">

    <?php if (isset($extraCss)): ?>
        <?= $extraCss ?>
    <?php endif; ?>
</head>
<body class="<?= $bodyClass ?> <?= $themeClass ?>">
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="<?= BASE_URL ?>" class="navbar-brand">
                <div class="brand-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <span class="brand-text">Turbo<span>Stock</span></span>
            </a>

            <div class="navbar-search">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="<?= __('search_placeholder') ?? 'Search products...' ?>" id="searchInput">
                    <button class="search-btn" onclick="doSearch()"><?= __('search') ?? 'Search' ?></button>
                </div>
            </div>

            <div class="navbar-actions">
                <?php if (isLoggedIn()): ?>
                    <!-- Wallet Button -->
                    <a href="<?= BASE_URL ?>/wallet.php" class="nav-btn">
                        <i class="fas fa-wallet"></i>
                        <span><?= __('wallet') ?? 'Wallet' ?></span>
                        <span class="wallet-badge"><?= formatCurrency(paymentHandler()->getBalance(getCurrentUserId())) ?></span>
                    </a>
                    <!-- Cart Button -->
                    <a href="<?= BASE_URL ?>/orders.php" class="nav-btn">
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                    <!-- User Menu -->
                    <a href="<?= BASE_URL ?>/profile.php" class="nav-btn primary">
                        <i class="fas fa-user"></i>
                        <span><?= sanitize($currentUser['username']) ?></span>
                    </a>
                <?php else: ?>
                    <!-- Wallet Button (Guest) -->
                    <a href="<?= BASE_URL ?>/auth/login.php" class="nav-btn">
                        <i class="fas fa-wallet"></i>
                        <span><?= __('wallet') ?? 'Wallet' ?></span>
                    </a>
                    <!-- Cart Button -->
                    <a href="<?= BASE_URL ?>/auth/login.php" class="nav-btn">
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                    <!-- Sign In Button -->
                    <a href="<?= BASE_URL ?>/auth/login.php" class="nav-btn primary">
                        <i class="fas fa-user"></i>
                        <span><?= __('login') ?? 'Sign In' ?></span>
                    </a>
                <?php endif; ?>

                <div class="nav-extras">
                    <?php if ($isHomepage): ?>
                        <!-- Full Language Selector for Homepage -->
                        <?php
                        $currentLang = lang()->getLanguageInfo();
                        $allLanguages = lang()->getAvailableLanguages();
                        ?>
                        <div class="lang-selector">
                            <button class="lang-btn" onclick="toggleLangDropdown()">
                                <i class="fas fa-globe"></i>
                                <?= strtoupper(lang()->getCurrentLanguage()) ?>
                                <i class="fas fa-chevron-down lang-chevron"></i>
                            </button>
                            <div class="lang-dropdown" id="langDropdown">
                                <?php foreach ($allLanguages as $code => $langInfo): ?>
                                    <a href="?lang=<?= $code ?>" class="lang-option <?= $code === lang()->getCurrentLanguage() ? 'active' : '' ?>">
                                        <span><?= $langInfo['flag'] ?></span> <?= $langInfo['native'] ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Language Selector for Other Pages -->
                        <?php $allLanguages = lang()->getAvailableLanguages(); ?>
                        <div class="lang-selector">
                            <button class="lang-btn" onclick="toggleLangDropdown()">
                                <i class="fas fa-globe"></i> <?= strtoupper(lang()->getCurrentLanguage()) ?>
                            </button>
                            <div class="lang-dropdown" id="langDropdown">
                                <?php foreach ($allLanguages as $code => $langInfo): ?>
                                    <a href="?lang=<?= $code ?>" class="lang-option <?= $code === lang()->getCurrentLanguage() ? 'active' : '' ?>">
                                        <span><?= $langInfo['flag'] ?></span> <?= $langInfo['native'] ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Theme Toggle -->
                    <a href="#" class="theme-toggle" title="<?= __('switch_theme') ?? 'Switch Theme' ?>" onclick="toggleTheme(event)">
                        <i class="fas fa-moon"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php displayFlashMessage(); ?>

    <script>
        function toggleLangDropdown() {
            var dropdown = document.getElementById('langDropdown');
            if (dropdown) {
                dropdown.classList.toggle('active');
            }
        }

        function toggleTheme(e) {
            e.preventDefault();
            var body = document.body;
            var icon = document.querySelector('.theme-toggle i');

            if (body.classList.contains('theme-light')) {
                body.classList.remove('theme-light');
                body.classList.add('theme-dark');
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
                localStorage.setItem('theme', 'dark');
            } else {
                body.classList.remove('theme-dark');
                body.classList.add('theme-light');
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
                localStorage.setItem('theme', 'light');
            }
        }

        function doSearch() {
            var query = document.getElementById('searchInput').value;
            if (query.trim()) {
                window.location.href = '<?= BASE_URL ?>/search.php?q=' + encodeURIComponent(query);
            }
        }

        // Search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                doSearch();
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.lang-btn') && !e.target.closest('.lang-dropdown')) {
                var dropdown = document.getElementById('langDropdown');
                if (dropdown) {
                    dropdown.classList.remove('active');
                }
            }
        });

        // Apply saved theme on load
        (function() {
            var savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.remove('theme-light');
                document.body.classList.add('theme-dark');
                var icon = document.querySelector('.theme-toggle i');
                if (icon) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                }
            }
        })();
    </script>
