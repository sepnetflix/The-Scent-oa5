<?php
require_once __DIR__ . '/../../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'The Scent - Premium Aromatherapy Products' ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Montserrat:wght@400;500;600&family=Raleway:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom JS: Tailwind config and all custom JS (must be before Tailwind CDN) -->
    <script src="/js/main.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <nav class="main-nav sample-header">
            <div class="container header-container">
                <div class="logo">
                    <a href="index.php" style="text-transform:uppercase; letter-spacing:1px;">The Scent</a>
                    <span style="display:block; font-family:'Raleway',sans-serif; font-size:0.7rem; letter-spacing:2px; text-transform:uppercase; color:#A0C1B1; margin-top:-5px; opacity:0.8;">AROMATHERAPY</span>
                </div>
                <div class="nav-links" id="mobile-menu">
                    <a href="index.php">Home</a>
                    <a href="index.php?page=products">Shop</a>
                    <a href="index.php?page=quiz">Scent Finder</a>
                    <a href="index.php?page=about">About</a>
                    <a href="index.php?page=contact">Contact</a>
                </div>
                <div class="header-icons">
                    <a href="#" aria-label="Search"><i class="fas fa-search"></i></a>
                    <?php if (isLoggedIn()): ?>
                        <a href="index.php?page=account" aria-label="Account"><i class="fas fa-user"></i></a>
                    <?php else: ?>
                        <a href="index.php?page=login" aria-label="Login"><i class="fas fa-user"></i></a>
                    <?php endif; ?>
                    <a href="index.php?page=cart" class="cart-link relative group" aria-label="Cart">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="cart-count"><?= isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : 0 ?></span>
                        <!-- Mini-cart dropdown -->
                        <div class="mini-cart-dropdown absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg z-50 hidden group-hover:block group-focus-within:block transition-all duration-200" style="min-width:320px;">
                            <div id="mini-cart-content" class="p-4">
                                <div class="text-center text-gray-500 py-6">Your cart is empty.</div>
                            </div>
                        </div>
                    </a>
                </div>
                <button class="mobile-menu-toggle md:hidden" aria-label="Toggle Menu">
                    <i class="fas fa-bars"></i>
                </button>
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
