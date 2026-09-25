<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
$settings = getSettings();
$institutionName = $settings['institution_name'] ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($institutionName) ?> | Billing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: time() ?>" rel="stylesheet">
</head>
<body>

<!-- Top Navbar -->
<nav class="navbar navbar-dark app-navbar fixed-top">
    <div class="container-fluid">
        <button class="btn btn-link text-white d-md-none" id="sidebarToggle"><i class="bi bi-list fs-3"></i></button>
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/index.php">
            <?php if (!empty($settings['logo'])): ?>
                <img src="<?= BASE_URL ?>/assets/uploads/logo/<?= e($settings['logo']) ?>" alt="Logo" height="32" class="me-2 rounded">
            <?php else: ?>
                <i class="bi bi-mortarboard-fill me-2"></i>
            <?php endif; ?>
            <?= e($institutionName) ?>
        </a>
        <div class="d-flex align-items-center">
            <span class="text-white-50 small d-none d-sm-inline"><?= date('l, d M Y') ?></span>
        </div>
    </div>
</nav>

<div class="app-wrapper">