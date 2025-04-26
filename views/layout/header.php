<?php
require_once __DIR__ . '/../../includes/auth.php'; // Provides isLoggedIn()
// It's assumed the controller rendering this view has already generated
// and passed $csrfToken and $bodyClass variables into the view's scope.
// Example in controller:
// $csrfToken = $this->generateCSRFToken();
// $bodyClass = 'page-whatever';
// echo $this->renderView('view_name', compact('csrfToken', 'bodyClass', 'pageTitle', ...));
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
    <!-- Tailwind CSS custom config -->
    <script>
        window.tailwind = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1A4D5A',
                        'primary-dark': '#164249',
                        secondary: '#A0C1B1',
                        accent: '#D4A76A',
                    },
                    fontFamily: {
                        heading: ['Cormorant Garamond', 'serif'],
                        body: ['Montserrat', 'sans-serif'],
                        accent: ['Raleway', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="<?= isset($bodyClass) ? htmlspecialchars($bodyClass) : '' ?>">

    <!-- Global CSRF Token Input for JavaScript AJAX Requests -->
    <input type="hidden" id="csrf-token-value" value="<?= isset($csrfToken) ? htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') : '' ?>">

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
                        <?php // Calculate cart count based on session/DB depending on login state
                            $cartCount = 0;
                            if (isLoggedIn()) {
                                // If logged in, the count might be updated via AJAX later,
                                // but we could fetch it initially if CartController is available here.
                                // For simplicity, often rely on session or JS update.
                                // Let's assume $_SESSION['cart_count'] is updated appropriately on cart actions.
                                $cartCount = $_SESSION['cart_count'] ?? 0;
                            } else {
                                $cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
                            }
                        ?>
                        <span class="cart-count" style="display: <?= $cartCount > 0 ? 'flex' : 'none' ?>;">
                            <?= $cartCount ?>
                        </span>
                        <!-- Mini-cart dropdown -->
                        <div class="mini-cart-dropdown absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg z-50 hidden group-hover:block group-focus-within:block transition-all duration-200" style="min-width:320px;">
                            <div id="mini-cart-content" class="p-4">
                                <!-- Content loaded via fetchMiniCart() in main.js -->
                                <div class="text-center text-gray-500 py-6">Loading cart...</div>
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
    <main class="pt-[80px]"> <!-- Add padding-top to main content to offset fixed header -->

        <!-- Flash message display area (consider moving if needed, but often okay here) -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <script>
                // Use the JS function immediately if available, or queue it
                // This handles flash messages set by non-AJAX requests (like redirects)
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof window.showFlashMessage === 'function') {
                        window.showFlashMessage(<?= json_encode($_SESSION['flash_message']) ?>, <?= json_encode($_SESSION['flash_type'] ?? 'info') ?>);
                    } else {
                        // Fallback or queue if main.js loads later somehow
                        console.warn('showFlashMessage not ready for server-side flash.');
                    }
                });
            </script>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Container for dynamically added flash messages by JS -->
        <div class="flash-message-container fixed top-5 right-5 z-[1100] max-w-sm w-full space-y-2"></div>

