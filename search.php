<?php
/**
 * HStore - Search Page
 */
require_once __DIR__ . '/includes/init.php';

$query = clean($_GET['q'] ?? '');

if (empty($query)) {
    redirect(BASE_URL . '/browse.php');
}

// Redirect to browse with search query
redirect(BASE_URL . '/browse.php?q=' . urlencode($query));
