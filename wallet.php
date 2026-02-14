<?php
/**
 * TurboStock - Wallet Page (New Design)
 */
require_once __DIR__ . '/includes/init.php';
requireLogin();

$userId = getCurrentUserId();
$wallet = paymentHandler()->getWallet($userId);

// Get pending withdrawal requests
$pendingWithdrawals = db()->fetchAll("
    SELECT * FROM withdrawal_requests
    WHERE user_id = ? AND status IN ('pending', 'processing')
    ORDER BY created_at DESC
", [$userId]);

// Get recent transactions
$transactions = db()->fetchAll("
    SELECT * FROM wallet_transactions
    WHERE wallet_id = ?
    ORDER BY created_at DESC
    LIMIT 20
", [$wallet['id']]);

// Get transaction count
$transactionCount = db()->fetch("SELECT COUNT(*) as count FROM wallet_transactions WHERE wallet_id = ?", [$wallet['id']]);

// Handle deposit request
$depositPayment = null;
$depositError = '';
$withdrawalError = '';
$withdrawalSuccess = '';
$cryptoCurrencies = nowPayments()->getPopularCurrencies();

// Handle cancel deposit
if (isset($_GET['cancel_deposit'])) {
    db()->query("UPDATE crypto_payments SET status = 'cancelled' WHERE user_id = ? AND payment_type = 'deposit' AND status = 'waiting'", [$userId]);
    header('Location: ' . BASE_URL . '/wallet.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit'])) {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $depositError = 'Invalid request. Please try again.';
    } else {
        $amount = (float) ($_POST['amount'] ?? 0);
        $payCurrency = clean($_POST['pay_currency'] ?? 'usdttrc20');

        if ($amount < 10) {
            $depositError = 'Minimum deposit is $10 USD';
        } elseif ($amount > 10000) {
            $depositError = 'Maximum deposit is 10,000 USD';
        } else {
            try {
                // Create order ID
                $orderId = 'DEP-' . $userId . '-' . time();

                // Create payment via NOWPayments
                $payment = nowPayments()->createPayment(
                    $amount,
                    'usd',
                    $payCurrency,
                    $orderId,
                    'Wallet deposit for user #' . $userId
                );

                if ($payment && isset($payment['payment_id'])) {
                    // Save to database
                    db()->insert('crypto_payments', [
                        'user_id' => $userId,
                        'payment_id' => $payment['payment_id'],
                        'order_id' => $orderId,
                        'payment_type' => 'deposit',
                        'price_amount' => $amount,
                        'price_currency' => 'usd',
                        'pay_amount' => $payment['pay_amount'],
                        'pay_currency' => $payCurrency,
                        'pay_address' => $payment['pay_address'],
                        'status' => 'waiting',
                        'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
                    ]);

                    $depositPayment = [
                        'payment_id' => $payment['payment_id'],
                        'wallet_address' => $payment['pay_address'],
                        'amount' => $payment['pay_amount'],
                        'currency' => strtoupper($payCurrency),
                        'usd_amount' => $amount,
                        'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
                        'payin_extra_id' => $payment['payin_extra_id'] ?? null
                    ];
                } else {
                    $depositError = 'Failed to create payment. Please try again.';
                }
            } catch (Exception $e) {
                $depositError = $e->getMessage();
            }
        }
    }
}

// Handle withdrawal request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $withdrawalError = 'Invalid request. Please try again.';
    } else {
        $amount = (float) ($_POST['withdraw_amount'] ?? 0);
        $walletAddress = clean($_POST['wallet_address'] ?? '');

        $minWithdrawal = 50; // Minimum 50 USDT

        if ($amount < $minWithdrawal) {
            $withdrawalError = 'Minimum withdrawal is ' . formatCurrency($minWithdrawal);
        } elseif ($amount > $wallet['balance']) {
            $withdrawalError = 'Insufficient balance. Available: ' . formatCurrency($wallet['balance']);
        } elseif (empty($walletAddress) || strlen($walletAddress) < 30) {
            $withdrawalError = 'Please enter a valid TRON wallet address';
        } elseif (!empty($pendingWithdrawals)) {
            $withdrawalError = 'You already have a pending withdrawal request';
        } else {
            // Create withdrawal request
            db()->insert('withdrawal_requests', [
                'user_id' => $userId,
                'amount' => $amount,
                'wallet_address' => $walletAddress,
                'status' => 'pending'
            ]);

            // Deduct from balance (hold until processed)
            db()->query("UPDATE wallets SET balance = balance - ? WHERE user_id = ?", [$amount, $userId]);

            // Record transaction
            db()->insert('wallet_transactions', [
                'wallet_id' => $wallet['id'],
                'type' => 'withdrawal',
                'amount' => -$amount,
                'balance_before' => $wallet['balance'],
                'balance_after' => $wallet['balance'] - $amount,
                'description' => 'Withdrawal request to ' . substr($walletAddress, 0, 10) . '...',
                'status' => 'pending'
            ]);

            $wallet['balance'] -= $amount;
            $withdrawalSuccess = 'Withdrawal request submitted! It will be processed within 24-48 hours.';

            // Refresh pending withdrawals
            $pendingWithdrawals = db()->fetchAll("
                SELECT * FROM withdrawal_requests
                WHERE user_id = ? AND status IN ('pending', 'processing')
                ORDER BY created_at DESC
            ", [$userId]);
        }
    }
}

// Check for pending deposit
$pendingDeposit = db()->fetch("
    SELECT * FROM crypto_payments
    WHERE user_id = ? AND payment_type = 'deposit' AND status = 'waiting' AND expires_at > NOW()
    ORDER BY created_at DESC LIMIT 1
", [$userId]);

if ($pendingDeposit && !$depositPayment) {
    $depositPayment = [
        'payment_id' => $pendingDeposit['payment_id'],
        'wallet_address' => $pendingDeposit['pay_address'],
        'amount' => $pendingDeposit['pay_amount'],
        'currency' => strtoupper($pendingDeposit['pay_currency']),
        'usd_amount' => $pendingDeposit['price_amount'],
        'expires_at' => $pendingDeposit['expires_at'],
        'payin_extra_id' => null
    ];
}

$pageTitle = __('wallet') . ' - ' . PLATFORM_NAME;
$bodyClass = 'page-wallet';
$themeClass = 'theme-light';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>"><?= __('home') ?? 'Home' ?></a>
            <span>/</span>
            <span class="current"><?= __('wallet') ?? 'Wallet' ?></span>
        </div>
        <h1 class="page-title"><?= __('my') ?? 'My' ?> <span><?= __('wallet') ?? 'Wallet' ?></span></h1>
    </div>
</div>

<div class="container">
    <div class="main-content">
        <aside class="sidebar-menu">
            <a href="<?= BASE_URL ?>/profile.php" class="menu-item"><i class="fas fa-user"></i> <?= __('my_profile') ?? 'My Profile' ?></a>
            <a href="<?= BASE_URL ?>/orders.php" class="menu-item"><i class="fas fa-shopping-bag"></i> <?= __('my_orders') ?? 'My Orders' ?></a>
            <a href="<?= BASE_URL ?>/wallet.php" class="menu-item active"><i class="fas fa-wallet"></i> <?= __('wallet') ?? 'Wallet' ?></a>
            <a href="<?= BASE_URL ?>/wishlist.php" class="menu-item"><i class="fas fa-heart"></i> <?= __('wishlist') ?? 'Wishlist' ?></a>
            <a href="<?= BASE_URL ?>/settings.php" class="menu-item"><i class="fas fa-cog"></i> <?= __('settings') ?? 'Settings' ?></a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> <?= __('logout') ?? 'Logout' ?></a>
        </aside>

        <main>
            <!-- Flash Messages -->
            <?php if ($withdrawalSuccess): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= $withdrawalSuccess ?>
                </div>
            <?php endif; ?>
            <?php if ($withdrawalError): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $withdrawalError ?>
                </div>
            <?php endif; ?>
            <?php if ($depositError): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= sanitize($depositError) ?>
                </div>
            <?php endif; ?>

            <!-- Balance Card -->
            <div class="balance-card">
                <div class="balance-label"><?= __('available_balance') ?? 'Available Balance' ?></div>
                <div class="balance-amount"><?= formatCurrency($wallet['balance']) ?></div>
                <?php if ($wallet['pending_balance'] > 0): ?>
                    <div class="pending-balance">
                        <i class="fas fa-clock"></i>
                        <?= __('pending') ?? 'Pending' ?>: <?= formatCurrency($wallet['pending_balance']) ?>
                    </div>
                <?php endif; ?>
                <div class="balance-actions">
                    <button class="balance-btn" onclick="showDepositModal()"><i class="fas fa-plus"></i> <?= __('add_funds') ?? 'Add Funds' ?></button>
                    <?php if (isSeller()): ?>
                        <button class="balance-btn outline" onclick="showWithdrawModal()"><i class="fas fa-arrow-down"></i> <?= __('withdraw') ?? 'Withdraw' ?></button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon deposit"><i class="fas fa-arrow-up"></i></div>
                    <div class="stat-value"><?= formatCurrency($wallet['total_deposited']) ?></div>
                    <div class="stat-label"><?= __('total_deposits') ?? 'Total Deposits' ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon spent"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-value"><?= formatCurrency($wallet['total_spent']) ?></div>
                    <div class="stat-label"><?= __('total_spent') ?? 'Total Spent' ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon transactions"><i class="fas fa-exchange-alt"></i></div>
                    <div class="stat-value"><?= $transactionCount['count'] ?? 0 ?></div>
                    <div class="stat-label"><?= __('transactions') ?? 'Transactions' ?></div>
                </div>
                <?php if (isSeller()): ?>
                <div class="stat-card">
                    <div class="stat-icon earned"><i class="fas fa-coins"></i></div>
                    <div class="stat-value"><?= formatCurrency($wallet['total_earned']) ?></div>
                    <div class="stat-label"><?= __('total_earned') ?? 'Total Earned' ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Pending Withdrawal Notice -->
            <?php if (!empty($pendingWithdrawals)): ?>
                <div class="card pending-withdrawal-card">
                    <h3 class="card-title"><i class="fas fa-clock"></i> <?= __('pending_withdrawal') ?? 'Pending Withdrawal' ?></h3>
                    <div class="withdrawal-info">
                        <div class="withdrawal-amount"><?= formatCurrency($pendingWithdrawals[0]['amount']) ?></div>
                        <div class="withdrawal-status">
                            <span class="status-badge <?= $pendingWithdrawals[0]['status'] ?>"><?= ucfirst($pendingWithdrawals[0]['status']) ?></span>
                        </div>
                        <div class="withdrawal-date"><?= __('requested') ?? 'Requested' ?>: <?= formatDateTime($pendingWithdrawals[0]['created_at']) ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Active Deposit -->
            <?php if ($depositPayment): ?>
                <div class="card deposit-active-card">
                    <h3 class="card-title"><i class="fas fa-clock"></i> <?= __('pending_deposit') ?? 'Pending Deposit' ?></h3>
                    <div class="deposit-payment-box">
                        <div class="crypto-badge"><?= $depositPayment['currency'] ?></div>

                        <div class="send-amount-section">
                            <div class="send-label"><?= __('send_exactly') ?? 'Send exactly this amount' ?>:</div>
                            <div class="send-amount"><?= $depositPayment['amount'] ?> <?= $depositPayment['currency'] ?></div>
                            <div class="send-usd">≈ $<?= number_format($depositPayment['usd_amount'], 2) ?> USD</div>
                        </div>

                        <div class="address-section">
                            <div class="address-label"><?= __('to_address') ?? 'To this address' ?>:</div>
                            <div class="address-box">
                                <code id="walletAddress"><?= $depositPayment['wallet_address'] ?></code>
                                <button type="button" class="copy-btn" onclick="copyToClipboard('<?= $depositPayment['wallet_address'] ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        <?php if ($depositPayment['payin_extra_id']): ?>
                            <div class="memo-section">
                                <div class="memo-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>MEMO/Tag Required:</strong>
                                </div>
                                <div class="memo-box">
                                    <code><?= $depositPayment['payin_extra_id'] ?></code>
                                    <button type="button" class="copy-btn" onclick="copyToClipboard('<?= $depositPayment['payin_extra_id'] ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <div class="memo-note"><?= __('memo_required_note') ?? 'You MUST include this memo/tag or your payment will be lost!' ?></div>
                            </div>
                        <?php endif; ?>

                        <!-- Important Notes -->
                        <div class="important-notes">
                            <div class="notes-title"><i class="fas fa-info-circle"></i> <?= __('important') ?? 'Important' ?>:</div>
                            <ul>
                                <li><?= __('send_only_crypto') ?? 'Send <strong>only ' . $depositPayment['currency'] . '</strong> to this address' ?></li>
                                <li><?= __('send_exact_amount') ?? 'Send the <strong>exact amount</strong> shown above' ?></li>
                                <li><?= __('balance_credited') ?? 'Your balance will be credited automatically after confirmation' ?></li>
                            </ul>
                        </div>

                        <div class="timer-section">
                            <span class="timer-label"><?= __('time_remaining') ?? 'Time Remaining' ?>:</span>
                            <span class="payment-timer" data-expires="<?= $depositPayment['expires_at'] ?>">--:--:--</span>
                        </div>

                        <div class="payment-status" data-payment-check="<?= $depositPayment['payment_id'] ?>">
                            <div class="spinner"></div>
                            <span><?= __('waiting_for_payment') ?? 'Waiting for payment...' ?></span>
                        </div>

                        <div class="cancel-section">
                            <p><?= __('different_crypto') ?? 'Want to use a different cryptocurrency?' ?></p>
                            <a href="<?= BASE_URL ?>/wallet.php?cancel_deposit=1" class="cancel-btn">
                                <i class="fas fa-times"></i> <?= __('cancel_start_over') ?? 'Cancel & Start Over' ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Transaction History -->
            <div class="card">
                <h3 class="card-title"><i class="fas fa-history"></i> <?= __('recent_transactions') ?? 'Recent Transactions' ?></h3>
                <?php if (empty($transactions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <p><?= __('no_transactions') ?? 'No transactions yet' ?></p>
                    </div>
                <?php else: ?>
                    <div class="transaction-list">
                        <?php foreach ($transactions as $tx): ?>
                            <?php
                            $txClass = match($tx['type']) {
                                'deposit' => 'deposit',
                                'withdrawal' => 'withdrawal',
                                'purchase' => 'purchase',
                                'sale' => 'earning',
                                'refund' => 'refund',
                                default => 'other'
                            };
                            $txIcon = match($tx['type']) {
                                'deposit' => 'fa-arrow-down',
                                'withdrawal' => 'fa-arrow-up',
                                'purchase' => 'fa-shopping-bag',
                                'sale' => 'fa-coins',
                                'refund' => 'fa-undo',
                                default => 'fa-exchange-alt'
                            };
                            ?>
                            <div class="transaction-item">
                                <div class="transaction-icon <?= $txClass ?>"><i class="fas <?= $txIcon ?>"></i></div>
                                <div class="transaction-info">
                                    <div class="transaction-title"><?= ucfirst($tx['type']) ?><?= $tx['description'] ? ': ' . sanitize($tx['description']) : '' ?></div>
                                    <div class="transaction-date"><?= formatDateTime($tx['created_at']) ?></div>
                                </div>
                                <div class="transaction-amount <?= $tx['amount'] >= 0 ? 'positive' : 'negative' ?>">
                                    <?= $tx['amount'] >= 0 ? '+' : '' ?><?= formatCurrency($tx['amount']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Deposit Modal -->
<div class="modal-overlay" id="depositModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> <?= __('deposit_crypto') ?? 'Deposit Crypto' ?></h3>
            <button class="modal-close" onclick="closeDepositModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <?= csrfField() ?>

                <div class="form-group">
                    <label><?= __('deposit_amount') ?? 'Deposit Amount' ?> (USD)</label>
                    <div class="amount-input-wrapper">
                        <span class="currency-symbol">$</span>
                        <input type="number" class="form-input" name="amount" placeholder="Enter amount" min="10" max="10000" step="0.01" required>
                    </div>
                    <div class="form-hint"><?= __('min_max_deposit') ?? 'Minimum: $10 | Maximum: $10,000' ?></div>
                </div>

                <div class="quick-amounts">
                    <button type="button" class="quick-amount-btn" onclick="setAmount(10)">$10</button>
                    <button type="button" class="quick-amount-btn" onclick="setAmount(50)">$50</button>
                    <button type="button" class="quick-amount-btn" onclick="setAmount(100)">$100</button>
                    <button type="button" class="quick-amount-btn" onclick="setAmount(500)">$500</button>
                </div>

                <div class="form-group">
                    <label><?= __('select_crypto') ?? 'Select Cryptocurrency' ?></label>
                    <div class="crypto-grid">
                        <?php foreach ($cryptoCurrencies as $code => $crypto): ?>
                            <label class="crypto-option">
                                <input type="radio" name="pay_currency" value="<?= $code ?>" <?= $code === 'usdttrc20' ? 'checked' : '' ?>>
                                <div class="crypto-option-content">
                                    <i class="<?= $crypto['icon'] ?>" style="color: <?= $crypto['color'] ?>"></i>
                                    <span><?= $crypto['name'] ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" name="deposit" class="btn-primary btn-full">
                    <i class="fas fa-wallet"></i> <?= __('continue_to_payment') ?? 'Continue to Payment' ?>
                </button>
            </form>

            <div class="deposit-features">
                <div class="feature">
                    <i class="fas fa-coins"></i>
                    <div>
                        <strong><?= __('multi_crypto') ?? 'Multi-Crypto' ?></strong>
                        <span><?= __('multi_crypto_desc') ?? 'Pay with 50+ cryptocurrencies' ?></span>
                    </div>
                </div>
                <div class="feature">
                    <i class="fas fa-shield-alt"></i>
                    <div>
                        <strong><?= __('secure') ?? 'Secure' ?></strong>
                        <span><?= __('secure_desc') ?? 'Processed via NOWPayments' ?></span>
                    </div>
                </div>
                <div class="feature">
                    <i class="fas fa-bolt"></i>
                    <div>
                        <strong><?= __('auto_credit') ?? 'Auto Credit' ?></strong>
                        <span><?= __('auto_credit_desc') ?? 'Instant balance update' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Withdraw Modal (Sellers Only) -->
<?php if (isSeller()): ?>
<div class="modal-overlay" id="withdrawModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-arrow-down"></i> <?= __('withdraw_usdt') ?? 'Withdraw USDT' ?></h3>
            <button class="modal-close" onclick="closeWithdrawModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <?= csrfField() ?>

                <div class="form-group">
                    <label><?= __('amount') ?? 'Amount' ?> (USDT)</label>
                    <input type="number" class="form-input" name="withdraw_amount" step="0.01" min="50" max="<?= $wallet['balance'] ?>" placeholder="Minimum 50 USDT" required>
                    <div class="form-hint"><?= __('available') ?? 'Available' ?>: <?= formatCurrency($wallet['balance']) ?> | <?= __('minimum') ?? 'Minimum' ?>: 50 USDT</div>
                </div>

                <div class="form-group">
                    <label><?= __('wallet_address') ?? 'Wallet Address' ?> (TRC20)</label>
                    <input type="text" class="form-input" name="wallet_address" placeholder="T..." required>
                    <div class="form-hint"><?= __('trc20_hint') ?? 'Make sure this is a valid TRC20 USDT address' ?></div>
                </div>

                <button type="submit" name="withdraw" class="btn-primary btn-full" <?= $wallet['balance'] < 50 ? 'disabled' : '' ?>>
                    <i class="fas fa-paper-plane"></i> <?= __('request_withdrawal') ?? 'Request Withdrawal' ?>
                </button>

                <?php if ($wallet['balance'] < 50): ?>
                    <div class="form-error"><?= __('min_balance_required') ?? 'Minimum balance of 50 USDT required for withdrawal' ?></div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
var BASE_URL = '<?= BASE_URL ?>';

function showDepositModal() {
    document.getElementById('depositModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeDepositModal() {
    document.getElementById('depositModal').classList.remove('active');
    document.body.style.overflow = '';
}

function showWithdrawModal() {
    document.getElementById('withdrawModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeWithdrawModal() {
    document.getElementById('withdrawModal').classList.remove('active');
    document.body.style.overflow = '';
}

function setAmount(amount) {
    document.querySelector('input[name="amount"]').value = amount;
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Copied to clipboard!');
    });
}

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(function(modal) {
            modal.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
});

// Payment timer
document.querySelectorAll('.payment-timer').forEach(function(timer) {
    var expires = new Date(timer.dataset.expires).getTime();

    setInterval(function() {
        var now = new Date().getTime();
        var distance = expires - now;

        if (distance < 0) {
            timer.textContent = 'Expired';
            timer.classList.add('expired');
            return;
        }

        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        timer.textContent = String(hours).padStart(2, '0') + ':' +
                           String(minutes).padStart(2, '0') + ':' +
                           String(seconds).padStart(2, '0');
    }, 1000);
});

<?php if ($depositPayment): ?>
// Auto-check deposit status every 10 seconds
var paymentId = '<?= $depositPayment['payment_id'] ?>';
var checkInterval = setInterval(function() {
    fetch(BASE_URL + '/api/check-deposit.php?payment_id=' + paymentId)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'confirmed') {
                clearInterval(checkInterval);
                alert('✅ ' + data.message);
                window.location.reload();
            } else if (data.status === 'confirming') {
                document.querySelector('[data-payment-check]').innerHTML =
                    '<div class="spinner"></div>' +
                    '<span class="confirming">' + data.message + '</span>';
            } else if (data.status === 'expired') {
                clearInterval(checkInterval);
                alert('⏰ Payment expired. Please try again.');
                window.location.reload();
            } else if (data.status === 'failed') {
                clearInterval(checkInterval);
                alert('❌ Payment failed. Please try again.');
                window.location.reload();
            }
        })
        .catch(err => console.log('Check error:', err));
}, 10000);
<?php endif; ?>
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
