<?php
/**
 * HStore - FAQ Page
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'FAQ - ' . PLATFORM_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-4">Frequently Asked Questions</h1>
            
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            How do I make a purchase?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <ol>
                                <li>Create an account or log in</li>
                                <li>Deposit USDT (TRC20) to your wallet</li>
                                <li>Browse products and click "Buy Now"</li>
                                <li>Confirm your purchase</li>
                                <li>Receive your digital product instantly (auto delivery) or wait for seller delivery (manual)</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            How do I deposit funds?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>Go to your Wallet page and click "Deposit". You'll receive a unique TRON wallet address. Send USDT (TRC20) to this address. Your balance will be credited automatically after blockchain confirmation (usually 1-3 minutes).</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            How do I become a seller?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>Click "Become a Seller" in your account menu. Fill in your shop details and you can start listing products immediately. To get a verified badge, pay the verification fee of <?= formatCurrency(VERIFICATION_FEE) ?>.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            What is seller verification?
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>Verified sellers have paid a one-time fee of <?= formatCurrency(VERIFICATION_FEE) ?> to prove their commitment. They display a green "Verified Seller" badge, appear higher in search results, and receive priority support.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                            What are the platform fees?
                        </button>
                    </h2>
                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <ul>
                                <li><strong>Commission:</strong> <?= getSetting('commission_rate', DEFAULT_COMMISSION_RATE) ?>% on each sale</li>
                                <li><strong>Verification Fee:</strong> <?= formatCurrency(VERIFICATION_FEE) ?> (one-time)</li>
                                <li><strong>Deposits:</strong> Free (only blockchain fees apply)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                            What if I have a problem with my order?
                        </button>
                    </h2>
                    <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>First, try contacting the seller. If the issue isn't resolved, you can open a dispute from your order page. Our team will review the case and make a fair decision.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                            How does auto delivery work?
                        </button>
                    </h2>
                    <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>Products with "Instant Delivery" are delivered automatically after purchase. The product data (credentials, links, keys, etc.) is shown immediately on your order page.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                            Is my payment secure?
                        </button>
                    </h2>
                    <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p>Yes! All payments are processed on the TRON blockchain using USDT (TRC20). Transactions are transparent, immutable, and secured by blockchain technology.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-body text-center">
                    <h5>Still have questions?</h5>
                    <p class="text-muted">Contact our support team</p>
                    <a href="<?= BASE_URL ?>/contact.php" class="btn btn-primary">Contact Support</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
