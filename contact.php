<?php
/**
 * Demostore - Contact Page
 */
require_once __DIR__ . '/includes/init.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request';
    } else {
        $name = clean($_POST['name'] ?? '');
        $email = clean($_POST['email'] ?? '');
        $subject = clean($_POST['subject'] ?? '');
        $message = clean($_POST['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'Please fill in all fields';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            // In production, send email or store in database
            $success = 'Thank you for your message. We will get back to you soon!';
        }
    }
}

$pageTitle = 'Contact Us - ' . PLATFORM_NAME;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-4">Contact Us</h1>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                            <h5>Email</h5>
                            <p class="text-muted"><?= PLATFORM_EMAIL ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fab fa-telegram fa-3x text-primary mb-3"></i>
                            <h5>Telegram</h5>
                            <p class="text-muted">@hstore_support</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                            <h5>Response Time</h5>
                            <p class="text-muted">Within 24 hours</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Send us a Message</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= sanitize($success) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= sanitize($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <?= csrfField() ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Your Name</label>
                                <input type="text" class="form-control" name="name" required
                                       value="<?= isLoggedIn() ? sanitize(getCurrentUser()['username']) : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" required
                                       value="<?= isLoggedIn() ? sanitize(getCurrentUser()['email']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select class="form-select" name="subject" required>
                                <option value="">Select a topic</option>
                                <option value="General Inquiry">General Inquiry</option>
                                <option value="Technical Support">Technical Support</option>
                                <option value="Payment Issue">Payment Issue</option>
                                <option value="Order Problem">Order Problem</option>
                                <option value="Seller Support">Seller Support</option>
                                <option value="Report Abuse">Report Abuse</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="message" rows="5" required
                                      placeholder="Describe your issue or question..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
