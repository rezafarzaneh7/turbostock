    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <?php
            // Check if we're on homepage (elaborate footer) or other pages (simple footer)
            $isHomepage = ($bodyClass ?? '') === 'page-turbostock-source';
            ?>

            <?php if ($isHomepage): ?>
                <!-- Full Footer for Homepage -->
                <div class="footer-grid">
                    <div class="footer-about">
                        <div class="footer-brand">
                            <div class="brand-icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <span class="brand-text">Turbo<span>Stock</span></span>
                        </div>
                        <p class="footer-desc"><?= __('footer_description') ?? 'The fastest and most secure marketplace for digital products. Buy and sell with confidence.' ?></p>
                        <div class="footer-social">
                            <a href="https://t.me/TurboStock" class="social-link" title="Telegram"><i class="fab fa-telegram"></i></a>
                            <a href="#" class="social-link" title="Discord"><i class="fab fa-discord"></i></a>
                            <a href="#" class="social-link" title="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-link" title="Instagram"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>

                    <div class="footer-column">
                        <h4 class="footer-title"><?= __('marketplace') ?? 'Marketplace' ?></h4>
                        <ul class="footer-links">
                            <li><a href="<?= BASE_URL ?>/browse.php"><?= __('browse') ?? 'All Products' ?></a></li>
                            <li><a href="<?= BASE_URL ?>/browse.php?category=gaming"><?= __('gaming') ?? 'Gaming' ?></a></li>
                            <li><a href="<?= BASE_URL ?>/browse.php?category=software"><?= __('software') ?? 'Software' ?></a></li>
                            <li><a href="<?= BASE_URL ?>/browse.php?category=accounts"><?= __('accounts') ?? 'Accounts' ?></a></li>
                            <li><a href="<?= BASE_URL ?>/browse.php?category=gift-cards"><?= __('gift_cards') ?? 'Gift Cards' ?></a></li>
                        </ul>
                    </div>

                    <div class="footer-column">
                        <h4 class="footer-title"><?= __('company') ?? 'Company' ?></h4>
                        <ul class="footer-links">
                            <li><a href="<?= BASE_URL ?>/about.php"><?= __('about_us') ?? 'About Us' ?></a></li>
                            <li><a href="<?= BASE_URL ?>/become-seller.php"><?= __('become_seller') ?? 'Become a Seller' ?></a></li>
                            <li><a href="<?= BASE_URL ?>/seller-guide.php"><?= __('seller_guide') ?? 'Seller Guide' ?></a></li>
                            <li><a href="<?= BASE_URL ?>/fees.php"><?= __('fees_pricing') ?? 'Fees & Pricing' ?></a></li>
                            <li><a href="<?= BASE_URL ?>/contact.php"><?= __('contact') ?? 'Contact' ?></a></li>
                        </ul>
                    </div>

                    <div class="footer-column">
                        <h4 class="footer-title"><?= __('support') ?? 'Support' ?></h4>
                        <ul class="footer-links">
                            <li><a href="<?= BASE_URL ?>/faq.php"><?= __('help_center') ?? 'Help Center' ?></a></li>
                            <li><a href="<?= BASE_URL ?>/terms.php"><?= __('terms_of_service') ?? 'Terms of Service' ?></a></li>
                            <li><a href="<?= BASE_URL ?>/privacy.php"><?= __('privacy_policy') ?? 'Privacy Policy' ?></a></li>
                            <li><a href="<?= BASE_URL ?>/refund.php"><?= __('refund_policy') ?? 'Refund Policy' ?></a></li>
                            <li><a href="<?= BASE_URL ?>/support.php"><?= __('report_issue') ?? 'Report Issue' ?></a></li>
                        </ul>
                    </div>
                </div>

                <div class="footer-bottom">
                    <p class="footer-copyright">&copy; <?= date('Y') ?> <?= getSetting('site_name', 'TurboStock') ?>. <?= __('all_rights_reserved') ?? 'All rights reserved.' ?></p>
                    <div class="footer-payments">
                        <div class="payment-icon" title="Bitcoin"><i class="fab fa-bitcoin"></i></div>
                        <div class="payment-icon" title="Ethereum"><i class="fab fa-ethereum"></i></div>
                        <div class="payment-icon" title="USDT">USDT</div>
                        <div class="payment-icon" title="TRON">TRX</div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Simple Footer for Other Pages -->
                <div class="container">
                    <div class="footer-content">
                        <div class="footer-brand">
                            <div class="brand-icon"><i class="fas fa-bolt"></i></div>
                            <span class="brand-text">Turbo<span>Stock</span></span>
                        </div>
                        <div class="footer-links">
                            <a href="<?= BASE_URL ?>/about.php">About</a>
                            <a href="<?= BASE_URL ?>/terms.php">Terms</a>
                            <a href="<?= BASE_URL ?>/privacy.php">Privacy</a>
                            <a href="<?= BASE_URL ?>/support.php">Support</a>
                        </div>
                        <p class="footer-copyright">&copy; <?= date('Y') ?> TurboStock. All rights reserved.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </footer>

    <!-- Custom JS -->
    <script src="<?= ASSETS_URL ?>/js/main.js"></script>

    <?php if (isset($extraJs)): ?>
        <?= $extraJs ?>
    <?php endif; ?>
</body>
</html>
