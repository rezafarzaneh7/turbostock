<?php
/**
 * HStore - Admin Settings
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request';
    } else {
        $settings = [
            'site_name' => clean($_POST['site_name'] ?? ''),
            'site_description' => clean($_POST['site_description'] ?? ''),
            'commission_rate' => (float) ($_POST['commission_rate'] ?? 10),
            'verification_fee' => (float) ($_POST['verification_fee'] ?? 100),
            'min_withdrawal' => (float) ($_POST['min_withdrawal'] ?? 10),
            'payment_expiry_hours' => (int) ($_POST['payment_expiry_hours'] ?? 24),
            'tron_api_key' => clean($_POST['tron_api_key'] ?? ''),
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
            'auto_approve_products' => isset($_POST['auto_approve_products']) ? '1' : '0'
        ];
        
        foreach ($settings as $key => $value) {
            updateSetting($key, $value);
        }
        
        logAdminAction('update_settings', 'settings', null, null, $settings);
        $success = 'Settings updated successfully!';
    }
}

// Get current settings
$settings = [];
$settingsRows = db()->fetchAll("SELECT setting_key, setting_value FROM settings");
foreach ($settingsRows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Settings - Admin';
require_once __DIR__ . '/header.php';
?>

<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4">Platform Settings</h4>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= sanitize($success) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <?= csrfField() ?>
        
        <div class="row">
            <div class="col-lg-6">
                <!-- General Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>General Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Site Name</label>
                            <input type="text" class="form-control" name="site_name" 
                                   value="<?= sanitize($settings['site_name'] ?? PLATFORM_NAME) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Site Description</label>
                            <textarea class="form-control" name="site_description" rows="2"><?= sanitize($settings['site_description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" name="maintenance_mode" id="maintenance_mode"
                                   <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="maintenance_mode">
                                Enable Maintenance Mode
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="auto_approve_products" id="auto_approve_products"
                                   <?= ($settings['auto_approve_products'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="auto_approve_products">
                                Auto-approve New Products
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Financial Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Financial Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Commission Rate (%)</label>
                            <input type="number" class="form-control" name="commission_rate" step="0.1" min="0" max="100"
                                   value="<?= $settings['commission_rate'] ?? DEFAULT_COMMISSION_RATE ?>">
                            <small class="text-muted">Platform commission on each sale</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Verification Fee (USDT)</label>
                            <input type="number" class="form-control" name="verification_fee" step="1" min="0"
                                   value="<?= $settings['verification_fee'] ?? VERIFICATION_FEE ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Minimum Withdrawal (USDT)</label>
                            <input type="number" class="form-control" name="min_withdrawal" step="1" min="0"
                                   value="<?= $settings['min_withdrawal'] ?? MIN_WITHDRAWAL ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <!-- Payment Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Payment Expiry (Hours)</label>
                            <input type="number" class="form-control" name="payment_expiry_hours" min="1" max="168"
                                   value="<?= $settings['payment_expiry_hours'] ?? PAYMENT_EXPIRY_HOURS ?>">
                            <small class="text-muted">Time before payment request expires</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">TronGrid API Key</label>
                            <input type="text" class="form-control" name="tron_api_key" 
                                   value="<?= sanitize($settings['tron_api_key'] ?? '') ?>"
                                   placeholder="Get from trongrid.io">
                            <small class="text-muted">Required for blockchain API calls</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">USDT Contract Address</label>
                            <input type="text" class="form-control" value="<?= USDT_CONTRACT_ADDRESS ?>" readonly>
                            <small class="text-muted">TRC20 USDT contract (configured in config.php)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Network</label>
                            <input type="text" class="form-control" value="<?= ucfirst(TRON_NETWORK) ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Platform Stats</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stats = [
                            'Total Users' => db()->count('users'),
                            'Total Sellers' => db()->count('sellers'),
                            'Verified Sellers' => db()->count('sellers', "verification_status = 'verified'"),
                            'Total Products' => db()->count('products'),
                            'Active Products' => db()->count('products', "status = 'active'"),
                            'Total Orders' => db()->count('orders'),
                            'Completed Orders' => db()->count('orders', "status = 'completed'"),
                        ];
                        ?>
                        <?php foreach ($stats as $label => $value): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?= $label ?></span>
                                <strong><?= number_format($value) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save me-2"></i>Save Settings
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
