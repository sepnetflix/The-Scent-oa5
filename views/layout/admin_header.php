<?php
require_once __DIR__ . '/../../includes/auth.php';
if (!isAdmin()) {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Admin Dashboard - The Scent' ?></title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="admin-layout">
    <header class="admin-header">
        <nav class="admin-nav">
            <div class="container">
                <div class="admin-nav-left">
                    <a href="index.php?page=admin" class="admin-logo">
                        <i class="fas fa-leaf"></i>
                        The Scent Admin
                    </a>
                </div>
                <div class="admin-nav-center">
                    <a href="index.php?page=admin&section=products" class="<?= $section === 'products' ? 'active' : '' ?>">
                        <i class="fas fa-box"></i> Products
                    </a>
                    <a href="index.php?page=admin&section=orders" class="<?= $section === 'orders' ? 'active' : '' ?>">
                        <i class="fas fa-shopping-bag"></i> Orders
                    </a>
                    <a href="index.php?page=admin&section=coupons" class="<?= $section === 'coupons' ? 'active' : '' ?>">
                        <i class="fas fa-ticket-alt"></i> Coupons
                    </a>
                    <a href="index.php?page=admin&section=quiz_analytics" class="<?= $section === 'quiz_analytics' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie"></i> Quiz Analytics
                    </a>
                </div>
                <div class="admin-nav-right">
                    <a href="index.php" class="view-site">
                        <i class="fas fa-external-link-alt"></i>
                        View Site
                    </a>
                    <a href="index.php?page=logout" class="logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </nav>
    </header>
    <main>
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="flash-message <?= $_SESSION['flash_type'] ?? 'info' ?>">
                <?= $_SESSION['flash_message'] ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>