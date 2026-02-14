<?php
/**
 * HStore - Logout
 */
require_once __DIR__ . '/../includes/init.php';

// Destroy session
session_unset();
session_destroy();

// Redirect to home
redirectWithMessage(BASE_URL, 'You have been logged out successfully.', 'success');
