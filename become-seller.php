<?php
/**
 * TurboStock - Become a Seller Page (New Design)
 */
require_once __DIR__ . '/includes/init.php';
requireLogin();

// Check if already a seller
if (isSeller()) {
    redirect(BASE_URL . '/seller/');
}

$error = '';
$success = '';

// Get commission rate
$commissionRate = getSetting('commission_rate', DEFAULT_COMMISSION_RATE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $shopName = clean($_POST['shop_name'] ?? '');
        $shopDescription = clean($_POST['shop_description'] ?? '');

        if (empty($shopName)) {
            $error = 'Please enter a shop name.';
        } elseif (strlen($shopName) < 3 || strlen($shopName) > 100) {
            $error = 'Shop name must be between 3 and 100 characters.';
        } else {
            $shopSlug = generateSlug($shopName);

            // Check if slug exists
            $existing = db()->fetch("SELECT id FROM sellers WHERE shop_slug = ?", [$shopSlug]);
            if ($existing) {
                $shopSlug .= '-' . rand(100, 999);
            }

            try {
                db()->beginTransaction();

                // Create seller record
                db()->insert('sellers', [
                    'user_id' => getCurrentUserId(),
                    'shop_name' => $shopName,
                    'shop_slug' => $shopSlug,
                    'shop_description' => $shopDescription,
                    'verification_status' => 'unverified'
                ]);

                // Update user role
                db()->update('users', ['role' => 'seller'], 'id = ?', [getCurrentUserId()]);

                // Update session
                $_SESSION['user_role'] = 'seller';

                db()->commit();

                redirectWithMessage(BASE_URL . '/seller/', 'Congratulations! Your seller account has been created.', 'success');

            } catch (Exception $e) {
                db()->rollback();
                error_log("Become seller error: " . $e->getMessage());
                $error = 'An error occurred. Please try again.';
            }
        }
    }
}

$pageTitle = 'Become a Seller - ' . PLATFORM_NAME;
$bodyClass = 'page-become-seller';
require_once INCLUDES_PATH . '/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-badge"><i class="fas fa-rocket"></i> <?= __('start_earning_today') ?></div>
            <h1 class="hero-title"><?= __('turn_digital_products') ?> <span><?= __('profit') ?></span></h1>
            <p class="hero-desc"><?= __('seller_hero_desc') ?></p>
            <a href="#apply" class="btn btn-primary"><i class="fas fa-store"></i> <?= __('start_selling_now') ?></a>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">5K+</div>
                <div class="stat-label"><?= __('active_sellers') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$2M+</div>
                <div class="stat-label"><?= __('monthly_payouts') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value">50K+</div>
                <div class="stat-label"><?= __('happy_customers') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $commissionRate ?>%</div>
                <div class="stat-label"><?= __('low_commission') ?></div>
            </div>
        </div>
    </div>
</section>

<!-- Benefits Section -->
<section class="benefits">
    <div class="container">
        <h2 class="section-title"><?= __('why_sell_on') ?> <span><?= PLATFORM_NAME ?></span>?</h2>
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-bolt"></i></div>
                <h3 class="benefit-title"><?= __('instant_payouts') ?></h3>
                <p class="benefit-desc"><?= __('instant_payouts_desc') ?></p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-globe"></i></div>
                <h3 class="benefit-title"><?= __('global_reach') ?></h3>
                <p class="benefit-desc"><?= __('global_reach_desc') ?></p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-shield-alt"></i></div>
                <h3 class="benefit-title"><?= __('secure_platform') ?></h3>
                <p class="benefit-desc"><?= __('secure_platform_desc') ?></p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-percentage"></i></div>
                <h3 class="benefit-title"><?= __('low_fees') ?></h3>
                <p class="benefit-desc"><?= sprintf(__('low_fees_desc'), $commissionRate) ?></p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-chart-line"></i></div>
                <h3 class="benefit-title"><?= __('analytics_dashboard') ?></h3>
                <p class="benefit-desc"><?= __('analytics_dashboard_desc') ?></p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-headset"></i></div>
                <h3 class="benefit-title"><?= __('support_24_7') ?></h3>
                <p class="benefit-desc"><?= __('support_24_7_desc') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Steps Section -->
<section class="steps">
    <div class="container">
        <h2 class="section-title"><?= __('how_it') ?> <span><?= __('works') ?></span></h2>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3 class="step-title"><?= __('step_apply') ?></h3>
                <p class="step-desc"><?= __('step_apply_desc') ?></p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <h3 class="step-title"><?= __('step_approved') ?></h3>
                <p class="step-desc"><?= __('step_approved_desc') ?></p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <h3 class="step-title"><?= __('step_list') ?></h3>
                <p class="step-desc"><?= __('step_list_desc') ?></p>
            </div>
            <div class="step-card">
                <div class="step-number">4</div>
                <h3 class="step-title"><?= __('step_earn') ?></h3>
                <p class="step-desc"><?= __('step_earn_desc') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Application Form Section -->
<section class="application" id="apply">
    <div class="container">
        <div class="form-card">
            <h2 class="form-title"><?= __('become_a') ?> <span><?= __('seller') ?></span></h2>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?= csrfField() ?>

                <div class="form-group">
                    <label class="form-label"><?= __('shop_name') ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-input" name="shop_name"
                           value="<?= sanitize($_POST['shop_name'] ?? '') ?>"
                           placeholder="<?= __('enter_shop_name') ?>" required minlength="3" maxlength="100">
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __('shop_description') ?></label>
                    <textarea class="form-textarea" name="shop_description" rows="4"
                              placeholder="<?= __('shop_description_placeholder') ?>"><?= sanitize($_POST['shop_description'] ?? '') ?></textarea>
                </div>

                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> <?= __('what_happens_next') ?></h4>
                    <ul>
                        <li><?= __('shop_unverified_info') ?></li>
                        <li><?= __('start_listing_info') ?></li>
                        <li><?= sprintf(__('verification_fee_info'), formatCurrency(VERIFICATION_FEE)) ?></li>
                    </ul>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" required>
                        <span class="custom-checkbox"></span>
                        <?= sprintf(__('agree_seller_terms'), '<a href="' . BASE_URL . '/seller-terms.php" target="_blank">' . __('seller_terms') . '</a>', $commissionRate) ?>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-submit">
                    <i class="fas fa-rocket"></i> <?= __('create_my_shop') ?>
                </button>
            </form>
        </div>
    </div>
</section>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
