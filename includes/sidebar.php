<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$page = $_GET['page'] ?? 'dashboard';

function navActive(string $pageKey, string $current): string
{
    return $pageKey === $current ? 'active' : '';
}
?>
<aside class="app-sidebar" id="appSidebar">
    <nav class="nav flex-column p-2">
        <a class="nav-link <?= navActive('dashboard', $page) ?>" href="<?= BASE_URL ?>/index.php?page=dashboard">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link <?= navActive('students', $page) ?>" href="<?= BASE_URL ?>/index.php?page=students">
            <i class="bi bi-people-fill"></i> Students
        </a>
        <a class="nav-link <?= navActive('courses', $page) ?>" href="<?= BASE_URL ?>/index.php?page=courses">
            <i class="bi bi-journal-bookmark-fill"></i> Courses
        </a>
        <a class="nav-link <?= navActive('payments', $page) ?>" href="<?= BASE_URL ?>/index.php?page=payments">
            <i class="bi bi-cash-coin"></i> Receive Fee
        </a>
        <a class="nav-link <?= navActive('reports', $page) ?>" href="<?= BASE_URL ?>/index.php?page=reports">
            <i class="bi bi-bar-chart-line-fill"></i> Reports
        </a>
        <a class="nav-link <?= navActive('settings', $page) ?>" href="<?= BASE_URL ?>/index.php?page=settings">
            <i class="bi bi-gear-fill"></i> Settings
        </a>
        <hr class="text-white-50">
        <div class="px-2 small text-white-50">
            <?= APP_NAME ?> v<?= APP_VERSION ?>
        </div>
    </nav>
</aside>
