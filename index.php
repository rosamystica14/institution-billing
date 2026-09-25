<?php
/**
 * Main Application Router
 * Institution Billing & Fee Management System
 *
 * Since this is a single-staff application, there is no login system.
 * The app opens directly to the Dashboard.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Whitelist of allowed pages to prevent directory traversal / LFI
$allowedPages = ['dashboard', 'students', 'student_details', 'courses', 'payments', 'receipt', 'application', 'reports', 'settings'];

$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

// Receipt / Application pages are rendered standalone (print-friendly, no sidebar/navbar)
if ($page === 'receipt') {
    require __DIR__ . '/pages/receipt.php';
    exit;
}

if ($page === 'application') {
    require __DIR__ . '/pages/application.php';
    exit;
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>
<main class="app-content">
    <div class="container-fluid py-3">
        <?php require __DIR__ . '/pages/' . $page . '.php'; ?>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
