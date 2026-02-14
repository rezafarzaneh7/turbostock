<?php
/**
 * HStore - Privacy Policy Page
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Privacy Policy - ' . PLATFORM_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-body p-5">
                    <h1 class="fw-bold mb-4">Privacy Policy</h1>
                    <p class="text-muted mb-4">Last updated: <?= date('F d, Y') ?></p>
                    
                    <div class="privacy-content">
                        <section class="mb-5">
                            <h4 class="fw-bold mb-3">1. Introduction</h4>
                            <p>Welcome to <?= PLATFORM_NAME ?> ("we," "our," or "us"). We are committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website and use our services.</p>
                            <p>Please read this privacy policy carefully. If you do not agree with the terms of this privacy policy, please do not access the site.</p>
                        </section>
                        
                        <section class="mb-5">
                            <h4 class="fw-bold mb-3">2. Information We Collect</h4>
                            <p>We collect information that you provide directly to us, including:</p>
                            <ul>
                                <li><strong>Account Information:</strong> Username, email address, and password when you register</li>
                                <li><strong>Profile Information:</strong> Avatar, shop name, and description for sellers</li>
                                <li><strong>Transaction Information:</strong> Purchase history, wallet addresses, and payment details</li>
                                <li><strong>Communication Data:</strong> Messages sent through our platform's chat system</li>
                                <li><strong>Usage Data:</strong> IP address, browser type, pages visited, and access times</li>
                            </ul>
                        </section>
                        
                        <section class="mb-5">
                            <h4 class="fw-bold mb-3">3. How We Use Your Information</h4>
                            <p>We use the information we collect to:</p>
                            <ul>
                                <li>Create and manage your account</li>
                                <li>Process transactions and send related information</li>
                                <li>Facilitate communication between buyers and sellers</li>
                                <li>Send administrative information, updates, and security alerts</li>
                                <li>Respond to your comments, questions, and customer service requests</li>
                                <li>Monitor and analyze usage patterns and trends</li>
                                <li>Detect, prevent, and address fraud and security issues</li>
                                <li>Comply with legal obligations</li>
                            </ul>
                        </section>
                        
                        <section class="mb-5">
                            <h4 class="fw-bold mb-3">4. Information Sharing</h4>
                            <p>We may share your information in the following situations:</p>
                            <ul>
                                <li><strong>With Sellers/Buyers:</strong> To facilitate transactions, we share necessary information between parties</li>
                                <li><strong>For Legal Purposes:</strong> When required by law or to protect our rights</li>
                                <li><strong>Business Transfers:</strong> In connection with any merger, sale, or acquisition</li>
                                <li><strong>With Your Consent:</strong> When you have given us permission to share</li>
                            </ul>
                            <p>We do not sell your personal information to third parties.</p>
                        </section>
                        
                        <section class="mb-5">
                            <h4 class="fw-bold mb-3">5. Cryptocurrency Transactions</h4>
                            <p>Our platform uses TRON blockchain for USDT TRC20 payments. Please note:</p>
                            <ul>
                                <li>Blockchain transactions are public and permanently recorded</li>
                                <li>Wallet addresses used for deposits are generated specifically for your account</li>
                                <li>We store transaction hashes for verification purposes</li>
                                <li>We do not have access to your external wallet private keys</li>
                            </ul>
                        </section>
                        
                        <section class="mb-5">
                            <h4 class="fw-bold mb-3">6. Data Security</h4>
                            <p>We implement appropriate technical and organizational security measures to protect your personal information, including:</p>
                            <ul>
                                <li>Encryption of sensitive data</li>
                                <li>Secure password hashing (bcrypt)</li>
                                <li>CSRF protection on all forms</li>
                                <li>Regular security audits</li>
                                <li>Access controls and authentication</li>
                            </ul>
                            <p>However, no method of transmission over the Internet is 100% secure. We cannot guarantee absolute security.</p>
                        </section>
                        
                        <section class="mb-5">
                            <h4 class="fw-bold mb-3">7. Data Retention</h4>
                            <p>We retain your personal information for as long as your account is active or as needed to provide you services. We may retain certain information as required by law or for legitimate business purposes.</p>
                        </section>
                        
                        <section class="mb-5">
                            <h4 class="fw-bold mb-3">8. Your Rights</h4>
                            <p>You have the right to:</p>
                            <ul>
                                <li>Access your personal information</li>
                                <li>Correct inaccurate data</li>
                                <li>Request deletion of your account</li>
                                <li>Withdraw consent where applicable</li>
                                <li>Export your data</li>
                            </ul>
                            <p>To exercise these rights, please contact us through our support channels.</p>
                        </section>
                        
                        <section class="mb-5">
                            <h4 class="fw-bold mb-3">9. Cookies</h4>
                            <p>We use cookies and similar tracking technologies to:</p>
                            <ul>
                                <li>Keep you logged in</li>
                                <li>Remember your preferences</li>
                                <li>Understand how you use our platform</li>
                                <li>Improve our services</li>
                            </ul>
                            <p>You can control cookies through your browser settings.</p>
                        </section>
                        
                        <section class="mb-5">
                            <h4 class="fw-bold mb-3">10. Third-Party Services</h4>
                            <p>Our platform may contain links to third-party websites or services. We are not responsible for the privacy practices of these external sites. We encourage you to read their privacy policies.</p>
                        </section>
                        
                        <section class="mb-5">
                            <h4 class="fw-bold mb-3">11. Children's Privacy</h4>
                            <p>Our services are not intended for individuals under the age of 18. We do not knowingly collect personal information from children. If you believe we have collected information from a minor, please contact us immediately.</p>
                        </section>
                        
                        <section class="mb-5">
                            <h4 class="fw-bold mb-3">12. Changes to This Policy</h4>
                            <p>We may update this privacy policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last updated" date. You are advised to review this policy periodically.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h4 class="fw-bold mb-3">13. Contact Us</h4>
                            <p>If you have questions or concerns about this Privacy Policy, please contact us:</p>
                            <ul>
                                <li>Email: <?= PLATFORM_EMAIL ?></li>
                                <li>Through our <a href="<?= BASE_URL ?>/contact.php">Contact Page</a></li>
                            </ul>
                        </section>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <a href="<?= BASE_URL ?>" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>Back to Home
                        </a>
                        <a href="<?= BASE_URL ?>/terms.php" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-file-contract me-2"></i>Terms of Service
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
