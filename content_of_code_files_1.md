# index.php  
```php
<?php
define('ROOT_PATH', __DIR__);
require_once __DIR__ . '/config.php'; // Defines BASE_URL, etc.

// --- START: Added Composer Autoloader ---
// Ensure the vendor directory exists (check after running 'composer install' or 'composer require')
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Log a fatal error or display a message if Composer dependencies are missing
    error_log("FATAL ERROR: Composer autoloader not found. Run 'composer install'.");
    // Optionally display a user-friendly error page if possible, but dependencies might be needed for that too.
    echo "Internal Server Error: Application dependencies are missing. Please contact support.";
    exit(1); // Stop execution
}
// --- END: Added Composer Autoloader ---

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php'; // Provides isLoggedIn(), isAdmin(), logoutUser()
require_once __DIR__ . '/includes/SecurityMiddleware.php';
require_once __DIR__ . '/includes/ErrorHandler.php';

// Initialize error handling
ErrorHandler::init();

// Apply security middleware (handles session start, base headers)
SecurityMiddleware::apply();

try {
    // Handle routing - Use SecurityMiddleware for validation
    $page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string') ?: 'home';
    $action = SecurityMiddleware::validateInput($_GET['action'] ?? null, 'string') ?: null; // Allow null action
    $id = SecurityMiddleware::validateInput($_GET['id'] ?? null, 'int'); // Validate ID if present

    // Validate CSRF token for POST requests globally
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Ensure CSRF token is generated if not already (e.g., first POST in session)
        SecurityMiddleware::generateCSRFToken();
        // Validate submitted token
        SecurityMiddleware::validateCSRF(); // Throws exception on failure
    }

    // Route to appropriate controller/action
    switch ($page) {
        case 'home':
            require_once __DIR__ . '/controllers/ProductController.php';
            $controller = new ProductController($pdo);
            $controller->showHomePage();
            break;

        case 'product':
            require_once __DIR__ . '/controllers/ProductController.php';
            $controller = new ProductController($pdo);
            if ($id) {
                $controller->showProduct($id);
            } else {
                // Handle missing ID, maybe redirect or show error
                require_once __DIR__ . '/views/404.php';
            }
            break;

        case 'products':
            require_once __DIR__ . '/controllers/ProductController.php';
            $controller = new ProductController($pdo);
            $controller->showProductList();
            break;

        case 'cart':
            require_once __DIR__ . '/controllers/CartController.php';
            $controller = new CartController($pdo);
            // Actions handled via POST/GET checks within controller methods now often return JSON
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                 if ($action === 'add') {
                    $controller->addToCart(); // Exits via jsonResponse
                 } elseif ($action === 'update') {
                     $controller->updateCart(); // Exits via jsonResponse
                 } elseif ($action === 'remove') {
                     $controller->removeFromCart(); // Exits via jsonResponse
                 } elseif ($action === 'clear') {
                    $controller->clearCart(); // Exits via jsonResponse or redirect
                 } else {
                    $controller->showCart(); // Default POST? Or show cart page.
                 }
            } elseif ($action === 'mini') { // GET request for mini cart data
                 $controller->mini(); // Exits via jsonResponse
            } else {
                // Default GET request: Show the full cart page
                $controller->showCart();
            }
            break;

        case 'checkout':
             // Login check before proceeding
            if (!isLoggedIn()) {
                $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=checkout'; // Store intended page
                header('Location: ' . BASE_URL . 'index.php?page=login'); // Redirect to login
                exit;
            }
            require_once __DIR__ . '/controllers/CheckoutController.php';
            require_once __DIR__ . '/controllers/CartController.php'; // Need CartController to check items
            $cartCtrl = new CartController($pdo); // Instantiate to check cart
            $cartItems = $cartCtrl->getCartItems();

            if (empty($cartItems) && $action !== 'confirmation') { // Allow confirmation page even if cart is now empty
                // If cart is empty, redirect to products page (or cart page)
                header('Location: ' . BASE_URL . 'index.php?page=products');
                exit;
            }

            $controller = new CheckoutController($pdo);
            if ($action === 'process' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->processCheckout(); // Exits via jsonResponse
            } elseif ($action === 'confirmation') {
                 $controller->showOrderConfirmation(); // Shows the confirmation view
            } elseif ($action === 'calculate_tax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->calculateTax(); // Exits via jsonResponse
            }
            else {
                // Default GET request: show the checkout page
                $controller->showCheckout();
            }
            break;

        // --- Account Related Routes ---
        case 'login':
            if (isLoggedIn()) { // Redirect if already logged in
                 header('Location: ' . BASE_URL . 'index.php?page=account'); // Redirect to dashboard
                 exit;
            }
            require_once __DIR__ . '/controllers/AccountController.php';
            $controller = new AccountController($pdo);
            $controller->login(); // Handles both GET (show form) and POST (process login)
            break;

        case 'register':
            if (isLoggedIn()) { // Redirect if already logged in
                 header('Location: ' . BASE_URL . 'index.php?page=account');
                 exit;
            }
            require_once __DIR__ . '/controllers/AccountController.php';
            $controller = new AccountController($pdo);
            // Controller handles GET (show form) and POST (process registration)
            $controller->register();
            break;

        case 'logout':
             // Use logout function from auth.php
             logoutUser();
             header('Location: ' . BASE_URL . 'index.php?page=login&loggedout=1'); // Redirect to login with a param
             exit;
             break;

        case 'account':
             if (!isLoggedIn()) { // Ensure user is logged in for all account pages
                 $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=account' . ($action ? '&action=' . $action : '');
                 header('Location: ' . BASE_URL . 'index.php?page=login');
                 exit;
             }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo);

             switch ($action) {
                 case 'profile':
                     $controller->showProfile();
                     break;
                 case 'update_profile': // POST only
                     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                         $controller->updateProfile(); // Handles redirect internally
                     } else {
                          header('Location: ' . BASE_URL . 'index.php?page=account&action=profile'); // Redirect GET requests
                          exit;
                     }
                     break;
                 case 'orders':
                     $controller->showOrders();
                     break;
                 case 'order_details':
                     if ($id) { // Ensure ID is present
                         $controller->showOrderDetails($id);
                     } else {
                          header('Location: ' . BASE_URL . 'index.php?page=account&action=orders'); // Redirect if no ID
                          exit;
                     }
                     break;
                 case 'update_newsletter': // POST only
                     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                         $controller->updateNewsletterPreferences(); // Handles redirect internally
                     } else {
                          header('Location: ' . BASE_URL . 'index.php?page=account&action=profile'); // Redirect GET to profile
                          exit;
                     }
                     break;
                 case 'dashboard': // Explicit dashboard action
                 default: // Default action for 'account' page is dashboard
                     $controller->showDashboard();
                     break;
             }
             break; // End of 'account' page case

        case 'forgot_password':
            if (isLoggedIn()) { // Redirect if already logged in
                 header('Location: ' . BASE_URL . 'index.php?page=account');
                 exit;
            }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo);
             $controller->requestPasswordReset(); // Handles GET (show form) and POST (process request)
             break;

        case 'reset_password':
             if (isLoggedIn()) { // Redirect if already logged in
                 header('Location: ' . BASE_URL . 'index.php?page=account');
                 exit;
             }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo);
             $controller->resetPassword(); // Handles GET (show form with token) and POST (process reset)
             break;

        // --- Other Routes ---
        case 'quiz':
            require_once __DIR__ . '/controllers/QuizController.php';
            $controller = new QuizController($pdo);
            if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->processQuiz(); // Assumes method handles view rendering or redirect
            } else {
                $controller->showQuiz(); // Assumes method handles view rendering
            }
            break;

        case 'newsletter': // Dedicated route for newsletter subscription
             require_once __DIR__ . '/controllers/NewsletterController.php';
             $controller = new NewsletterController($pdo);
             if ($action === 'subscribe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->subscribe(); // Exits via jsonResponse
             } elseif ($action === 'unsubscribe') {
                 $controller->unsubscribe(); // Handles GET request with token, exits via jsonResponse
             } else {
                  http_response_code(404);
                  require_once __DIR__ . '/views/404.php'; // Directly include 404 view
             }
             break;

        case 'admin':
            // Admin check happens first
            if (!isAdmin()) {
                $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=admin';
                header('Location: ' . BASE_URL . 'index.php?page=login');
                exit;
            }

            $section = SecurityMiddleware::validateInput($_GET['section'] ?? 'dashboard', 'string');
            $task = SecurityMiddleware::validateInput($_GET['task'] ?? null, 'string'); // For actions within sections

            switch ($section) {
                 case 'quiz_analytics':
                     require_once __DIR__ . '/controllers/QuizController.php';
                     $controller = new QuizController($pdo);
                     $controller->showAnalytics(); // Assumes method handles rendering view
                     break;
                 case 'coupons':
                    require_once __DIR__ . '/controllers/CouponController.php';
                    $controller = new CouponController($pdo);
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        if ($task === 'save') {
                            // Controller should handle create/update logic and redirection/response
                            // E.g., $controller->saveCoupon();
                        } elseif ($task === 'toggle_status' && $id) {
                            // Controller should handle status toggle and response
                            // E.g., $controller->toggleCouponStatus($id);
                        } elseif ($task === 'delete' && $id) {
                           // Controller should handle deletion and response
                           // E.g., $controller->deleteCoupon($id);
                        } else {
                             // Default POST for coupons? Maybe just list again or show error.
                             $controller->listCoupons();
                        }
                    } else { // GET Requests for coupons section
                         if ($task === 'edit' && $id) {
                              // Controller should show edit form
                              // E.g., $controller->showEditForm($id);
                         } elseif ($task === 'create') {
                              // Controller should show create form
                              // E.g., $controller->showCreateForm();
                         } else {
                             // Default GET action: show list
                             $controller->listCoupons(); // Method needs to exist in CouponController
                         }
                    }
                    break;
                // Add other admin sections (products, orders, users) here...
                // case 'products': ...
                // case 'orders': ...
                default: // Admin Dashboard
                    // Load admin dashboard view directly or via a controller method
                     $pageTitle = "Admin Dashboard"; // Example title
                     $bodyClass = "page-admin-dashboard"; // Example class
                     $csrfToken = SecurityMiddleware::generateCSRFToken(); // Needed if dashboard has actions
                     extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
                     require_once __DIR__ . '/views/admin/dashboard.php'; // Direct include for simple dashboard
                    break;
            }
            break; // End of 'admin' page case

        // --- Static Pages ---
        case 'contact':
            $pageTitle = 'Contact Us';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            $bodyClass = 'page-contact';
             extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/contact.php';
            break;
        case 'faq':
            $pageTitle = 'FAQs';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
             $bodyClass = 'page-faq';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/faq.php';
            break;
        case 'shipping':
            $pageTitle = 'Shipping & Returns';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            $bodyClass = 'page-shipping';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/shipping.php';
            break;
        case 'order-tracking': // Maybe needs a controller if dynamic
            $pageTitle = 'Track Your Order';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            $bodyClass = 'page-order-tracking';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/order-tracking.php';
            break;
        case 'privacy':
            $pageTitle = 'Privacy Policy';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            $bodyClass = 'page-privacy';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/privacy.php';
            break;
        case 'about': // Add route for about page if needed
             $pageTitle = 'About Us - The Scent'; // Set here for consistency
             $csrfToken = SecurityMiddleware::generateCSRFToken(); // Generate token
             $bodyClass = 'page-about'; // Set body class
             // Make variables available to the view scope before including it
             extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
             require_once __DIR__ . '/views/about.php'; // Require the view file
             break;
        case 'error': // Explicit error page route
            $pageTitle = 'Error';
            $bodyClass = 'page-error';
            $csrfToken = SecurityMiddleware::generateCSRFToken(); // For consistency if layout needs it
            http_response_code(500); // Set appropriate code if possible
            extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
            require_once __DIR__ . '/views/error.php';
            break;

        default: // 404 Not Found
            http_response_code(404);
            $pageTitle = 'Page Not Found';
            $bodyClass = 'page-404';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
            require_once __DIR__ . '/views/404.php';
            break;
    }
} catch (PDOException $e) {
    // Delegate to ErrorHandler (which should exist now)
    ErrorHandler::handleException($e); // Let the handler manage display/logging
    exit(1);
} catch (Throwable $e) { // Catch Throwable for broader coverage (PHP 7+)
    // Catch other exceptions (e.g., CSRF, routing errors, general errors)
    error_log("General error/exception in index.php: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
    // Delegate to ErrorHandler
    ErrorHandler::handleException($e);
    exit(1);
}

```

# config.php  
```php
<?php
// Environment
define('ENVIRONMENT', getenv('APP_ENV') ?: 'production');

// Security Settings
define('SECURITY_SETTINGS', [
    'session' => [
        'lifetime' => 3600,
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
        'regenerate_id_interval' => 900 // 15 minutes
    ],
    'rate_limiting' => [
        'enabled' => true,
        'default_window' => 3600,
        'default_max_requests' => 100,
        'ip_whitelist' => [], // Add trusted IPs here
        'endpoints' => [
            'login' => ['window' => 300, 'max_requests' => 5],
            'reset_password' => ['window' => 3600, 'max_requests' => 3],
            'register' => ['window' => 3600, 'max_requests' => 5]
        ]
    ],
    'encryption' => [
        'algorithm' => 'AES-256-CBC',
        'key_length' => 32
    ],
    'password' => [
        'min_length' => 12,
        'require_special' => true,
        'require_number' => true,
        'require_mixed_case' => true,
        'max_attempts' => 5,
        'lockout_duration' => 900
    ],
    'logging' => [
        'security_log' => __DIR__ . '/logs/security.log',
        'error_log' => __DIR__ . '/logs/error.log',
        'audit_log' => __DIR__ . '/logs/audit.log',
        'rotation_size' => 10485760, // 10MB
        'max_files' => 10
    ],
    'cors' => [
        'allowed_origins' => ['https://the-scent.com'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
        'allowed_headers' => ['Content-Type', 'Authorization'],
        'expose_headers' => ['X-Request-ID'],
        'max_age' => 3600
    ],
    'csrf' => [
        'enabled' => true,
        'token_length' => 32,
        'token_lifetime' => 3600
    ],
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        // CSP tightened: removed 'unsafe-inline' from script-src and style-src
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://js.stripe.com; style-src 'self'; frame-src https://js.stripe.com; img-src 'self' data: https:; connect-src 'self' https://api.stripe.com",
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'
    ],
    'file_upload' => [
        'max_size' => 5242880, // 5MB
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf'
        ],
        'scan_malware' => true
    ]
]);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'the_scent');
define('DB_USER', 'scent_user');
define('DB_PASS', 'StrongPassword123');
define('BASE_URL', '/');

// Stripe Configuration
define('STRIPE_PUBLIC_KEY', 'pk_test_your_stripe_public_key');
define('STRIPE_SECRET_KEY', 'sk_test_your_stripe_secret_key');
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_stripe_webhook_secret');

// Email Configuration (for next phase)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_email_app_password');
define('SMTP_FROM', 'noreply@thescent.com');
define('SMTP_FROM_NAME', 'The Scent');

// Application Settings
define('TAX_RATE', 0.10); // 10% tax rate
define('FREE_SHIPPING_THRESHOLD', 50.00); // Free shipping on orders over $50
define('SHIPPING_COST', 5.99); // Standard shipping cost

// Error Logging Configuration
define('ERROR_LOG_PATH', '/var/log/thescent/');
define('ERROR_LOG_LEVEL', E_ALL);

// Quiz Configuration
define('QUIZ_MAX_ATTEMPTS', 3);
define('QUIZ_RESULT_EXPIRY_DAYS', 30);
define('RECOMMENDATION_LIMIT', 5);

```

# includes/db.php  
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display in production

if (!file_exists(__DIR__ . '/../config.php')) {
    error_log("Config file not found");
    require_once __DIR__ . '/../views/error.php';
    exit();
}

require_once __DIR__ . '/../config.php';

if (!defined('DB_HOST')) {
    error_log("DB_HOST not defined");
    require_once __DIR__ . '/../views/error.php';
    exit();
}

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    require_once __DIR__ . '/../views/error.php';
    exit();
}

```

# views/home.php  
```php
<?php 
// home.php (v3 - Merged UI Fixes + Preserved Functionality)
require_once __DIR__ . '/layout/header.php'; // Uses header-fixed.php content implicitly
echo '<!-- DEBUG: home.php loaded -->';
$delay = 0; // Initialize delay counter for animations
?>
<!-- Output CSRF token for JS (for AJAX add-to-cart/newsletter) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars(
    $csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

<!-- Hero Section with Video Background -->
<section class="hero-section relative min-h-screen flex items-center justify-center overflow-hidden">
    <div id="particles-js" class="absolute inset-0 z-0"></div>
    <video class="absolute inset-0 w-full h-full object-cover z-[-1]" autoplay muted loop playsinline poster="/images/scent5.jpg">
        <source src="/videos/hero.mp4" type="video/mp4">
        <img src="/images/scent5.jpg" alt="Calming Nature" class="w-full h-full object-cover" />
    </video>
    <div class="absolute inset-0 bg-gradient-to-br from-primary/40 to-primary-dark/50 z-10"></div>
    <div class="container relative z-20 flex flex-col items-center justify-center text-center text-white px-6">
        <div data-aos="fade-down">
            <h1 class="text-4xl md:text-5xl font-bold mb-6 font-heading" style="text-shadow: 0 2px 4px rgba(0,0,0,0.7);">Find Your Moment of Calm</h1>
            <p class="text-lg md:text-xl mb-8 max-w-2xl mx-auto font-body">Experience premium, natural aromatherapy crafted to enhance well-being and restore balance.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#featured-products" class="btn btn-primary">Explore Our Collections</a>
            </div>
        </div>
    </div>
</section>

<!-- About/Mission Section (Keep existing) -->
<section class="about-section py-20 bg-white" id="about">
    <div class="container">
        <div class="about-container grid md:grid-cols-2 gap-12 items-center">
            <div class="about-image" data-aos="fade-left">
                <img src="<?= file_exists($_SERVER['DOCUMENT_ROOT'] . '/images/about.jpg') ? '/images/about.jpg' : 'https://placehold.co/800x600/e9ecef/495057?text=About+The+Scent' ?>"
                     alt="About The Scent" 
                     class="rounded-lg shadow-xl w-full">
            </div>
            <div class="about-content" data-aos="fade-right">
                <h2 class="text-3xl font-bold mb-6">Rooted in Nature, Crafted with Care</h2>
                <p class="mb-6">At The Scent, we harness the power of nature to nurture your mental and physical well-being. Our high-quality, sustainably sourced ingredients are transformed into exquisite aromatherapy products by expert hands.</p>
                <p class="mb-6">Our unique and creative formulations are crafted with expertise to create harmonious, balanced, and well-rounded aromatherapy products that enhance both mental and physical health.</p>
                <a href="index.php?page=about" class="btn btn-secondary">Learn Our Story</a>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products Section (Merged UI) -->
<section class="featured-section py-16 bg-light" id="featured-products">
    <div class="container mx-auto text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-12" data-aos="fade-up">Featured Collections</h2>
        <div class="featured-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 px-6">
            <?php if (isset($featuredProducts) && is_array($featuredProducts) && !empty($featuredProducts)): ?>
                <?php foreach ($featuredProducts as $product): ?>
                    <!-- Apply suggested card structure/style -->
                    <div class="product-card sample-card" data-aos="zoom-in" style="border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.05); overflow:hidden;">
                        <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             class="w-full h-64 object-cover" loading="lazy">
                        <div class="product-info" style="padding:1.5rem; text-align:center;">
                            <h3 style="margin-bottom:0.5rem; font-size:1.3rem;"><?= htmlspecialchars($product['name']) ?></h3>
                            
                            <!-- Apply suggested short description / category display logic -->
                            <?php if (!empty($product['short_description'])): ?>
                                <p style="font-size:0.9rem; color:#666; margin-bottom:1rem;"><?= htmlspecialchars($product['short_description']) ?></p>
                            <?php elseif (!empty($product['category_name'])): ?>
                                <p style="font-size:0.9rem; color:#666; margin-bottom:1rem;"><?= htmlspecialchars($product['category_name']) ?></p>
                            <?php endif; ?>

                            <!-- *** Re-integrate existing actions to preserve functionality *** -->
                            <div class="product-actions flex gap-2 justify-center mt-4">
                                <a href="index.php?page=product&id=<?= $product['id'] ?>" class="btn btn-primary">View Details</a> 
                                <?php if (isset($product['stock_quantity']) && $product['stock_quantity'] > 0): ?>
                                    <button class="btn btn-secondary add-to-cart" 
                                            data-product-id="<?= $product['id'] ?>"
                                            <?= isset($product['low_stock_threshold']) && $product['stock_quantity'] <= $product['low_stock_threshold'] ? 'data-low-stock="true"' : '' ?>>
                                        Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-disabled" disabled>Out of Stock</button>
                                <?php endif; ?>
                            </div>
                            <!-- *** End of re-integrated actions *** -->
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center text-gray-600">
                    <p>Discover our curated collection of premium aromatherapy products.</p>
                    <a href="index.php?page=products" class="inline-block mt-4 text-primary hover:underline">Browse All Products</a>
                </div>
            <?php endif; ?>
        </div>
        <!-- Apply suggested "Shop All" CTA below grid -->
        <div class="view-all-cta" style="text-align:center; margin-top:3rem;">
            <a href="index.php?page=products" class="btn btn-primary">Shop All Products</a>
        </div>
    </div>
</section>

<!-- Benefits Section (Keep existing) -->
<section class="py-20 bg-white">
    <div class="container">
        <h2 class="text-3xl font-bold text-center mb-12" data-aos="fade-up">Why Choose The Scent</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="0">
                <i class="fas fa-leaf text-4xl text-primary mb-4"></i>
                <h3 class="text-xl font-semibold mb-4">Natural Ingredients</h3>
                <p>Premium quality raw materials sourced from around the world.</p>
            </div>
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-heart text-4xl text-primary mb-4"></i>
                <h3 class="text-xl font-semibold mb-4">Wellness Focus</h3>
                <p>Products designed to enhance both mental and physical well-being.</p>
            </div>
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-certificate text-4xl text-primary mb-4"></i>
                <h3 class="text-xl font-semibold mb-4">Expert Crafted</h3>
                <p>Unique formulations created by aromatherapy experts.</p>
            </div>
        </div>
    </div>
</section>

<!-- Quiz/Finder Section (Keep existing) -->
<section class="quiz-section py-20 bg-light" id="finder">
    <div class="container">
        <h2 class="text-3xl font-bold text-center mb-8" data-aos="fade-up">Discover Your Perfect Scent</h2>
        <p class="text-center mb-12 text-lg" data-aos="fade-up" data-aos-delay="100">Tailor your aromatherapy experience to your mood and needs.</p>
        <div class="grid md:grid-cols-5 gap-6 mb-8 finder-grid">
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="0">
                <i class="fas fa-leaf text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Relaxation</h3>
                <p class="text-sm text-gray-600 text-center">Calming scents to help you unwind.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-bolt text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Energy</h3>
                <p class="text-sm text-gray-600 text-center">Invigorating aromas to uplift your day.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-brain text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Focus</h3>
                <p class="text-sm text-gray-600 text-center">Clarifying blends for a clear mind.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="300">
                <i class="fas fa-moon text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Sleep</h3>
                <p class="text-sm text-gray-600 text-center">Soothing scents for a peaceful night's rest.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="400">
                <i class="fas fa-balance-scale text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Balance</h3>
                <p class="text-sm text-gray-600 text-center">Harmonious aromas to center you.</p>
            </div>
        </div>
        <div class="text-center" data-aos="fade-up" data-aos-delay="500">
            <a href="index.php?page=quiz" class="btn btn-secondary">Take the Full Scent Quiz</a>
        </div>
    </div>
</section>

<!-- Newsletter Section (Merged UI) -->
<section class="newsletter-section py-20 bg-light" id="newsletter">
    <div class="container">
        <div class="max-w-2xl mx-auto text-center" data-aos="fade-up">
            <h2 class="text-3xl font-bold mb-6">Stay Connected</h2>
            <p class="mb-8">Subscribe to receive updates, exclusive offers, and aromatherapy tips.</p>
            <!-- Apply suggested form structure/style -->
            <form id="newsletter-form" class="newsletter-form flex flex-col sm:flex-row gap-4 justify-center">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="email" name="email" placeholder="Enter your email" required class="newsletter-input flex-1 px-4 py-2 rounded-full border border-gray-300 focus:border-primary">
                <button type="submit" class="btn btn-primary newsletter-btn">Subscribe</button>
            </form>
            <p class="newsletter-consent" style="font-size:0.8rem;opacity:0.7; margin-top:1rem;">By subscribing, you agree to our <a href="index.php?page=privacy" style="color:#A0C1B1;text-decoration:underline;">Privacy Policy</a>.</p>
        </div>
    </div>
</section>

<!-- Testimonials Section (Keep existing) -->
<section class="py-20 bg-white" id="testimonials">
    <div class="container">
        <h2 class="text-3xl font-bold text-center mb-12" data-aos="fade-up">What Our Community Says</h2>
        <div class="testimonial-grid grid md:grid-cols-3 gap-8">
            <div class="testimonial-card bg-light p-8 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="0">
                <p class="mb-4 italic">"The Lavender Essential Oil transformed my bedtime routine—its calming aroma truly helps me unwind."</p>
                <span class="block font-semibold mb-2">- Sarah L., Los Angeles</span>
                <div class="text-accent text-lg">★★★★★</div>
            </div>
            <div class="testimonial-card bg-light p-8 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="100">
                <p class="mb-4 italic">"The Focus Blend oil improved my concentration at home without overwhelming my senses."</p>
                <span class="block font-semibold mb-2">- Michael T., Chicago</span>
                <div class="text-accent text-lg">★★★★★</div>
            </div>
            <div class="testimonial-card bg-light p-8 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="200">
                <p class="mb-4 italic">"Handcrafted soaps that feel divine and truly nourish sensitive skin. A luxurious experience."</p>
                <span class="block font-semibold mb-2">- Emma R., Seattle</span>
                <div class="text-accent text-lg">★★★★★</div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; // Uses footer-fixed.php content implicitly ?>

```

# views/layout/header.php  
```php
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


```

# views/layout/footer.php  
```php
</main>
    <footer>
        <div class="container">
            <div class="footer-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:3rem;margin-bottom:3rem;">
                <div class="footer-about">
                    <h3>About The Scent</h3>
                    <p>Creating premium aromatherapy products to enhance mental and physical well-being through the power of nature.</p>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Pinterest"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                <div class="footer-links">
                    <h3>Shop</h3>
                    <ul>
                        <li><a href="index.php?page=products">Essential Oils</a></li>
                        <li><a href="index.php?page=products">Natural Soaps</a></li>
                        <li><a href="index.php?page=products">Gift Sets</a></li>
                        <li><a href="index.php?page=products">New Arrivals</a></li>
                        <li><a href="index.php?page=products">Bestsellers</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h3>Help</h3>
                    <ul>
                        <li><a href="index.php?page=contact">Contact Us</a></li>
                        <li><a href="index.php?page=faq">FAQs</a></li>
                        <li><a href="index.php?page=shipping">Shipping & Returns</a></li>
                        <li><a href="index.php?page=order-tracking">Track Your Order</a></li>
                        <li><a href="index.php?page=privacy">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Aromatherapy Lane, Wellness City, WB 12345</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> hello@thescent.com</p>
                    <form id="newsletter-form-footer" class="newsletter-form" style="margin-top:1rem;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="email" name="email" placeholder="Enter your email" required class="newsletter-input">
                        <button type="submit" class="btn btn-primary newsletter-btn">Subscribe</button>
                    </form>
                    <p class="newsletter-consent" style="font-size:0.8rem;opacity:0.7; margin-top:1rem;">By subscribing, you agree to our <a href="index.php?page=privacy" style="color:#A0C1B1; text-decoration:underline;">Privacy Policy</a> and consent to receive emails from The Scent.</p>
                </div>
            </div>
            <div class="footer-bottom" style="background-color:#222b2e; padding:1.5rem 0; margin-top:2rem;">
                <div class="container" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;font-size:0.85rem;">
                    <p>&copy; <?= date('Y') ?> The Scent. All rights reserved.</p>
                    <div class="payment-methods" style="display:flex;align-items:center;gap:0.8rem;">
                        <span>Accepted Payments:</span>
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-paypal"></i>
                        <i class="fab fa-cc-amex"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="/js/main.js"></script>
</body>
</html>

```

# views/cart.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-cart">
<!-- Output CSRF token for JS (for AJAX cart actions) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

<section class="cart-section">
    <div class="container">
        <div class="cart-container" data-aos="fade-up">
            <h1>Your Shopping Cart</h1>

            <?php if (empty($cartItems)): ?>
                <div class="empty-cart text-center py-16">
                    <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                    <p class="text-xl text-gray-700 mb-6">Your cart is currently empty.</p>
                    <a href="index.php?page=products" class="btn btn-primary">Continue Shopping</a>
                </div>
            <?php else: ?>
                <form id="cartForm" action="index.php?page=cart&action=update" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

                    <!-- Cart Items Column -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="cart-items bg-white shadow rounded-lg overflow-hidden">
                             <div class="hidden md:flex px-6 py-3 bg-gray-50 border-b border-gray-200 text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                 <div class="w-2/5">Product</div>
                                 <div class="w-1/5 text-center">Price</div>
                                 <div class="w-1/5 text-center">Quantity</div>
                                 <div class="w-1/5 text-right">Subtotal</div>
                                 <div class="w-10"></div> <!-- Spacer for remove button -->
                             </div>
                            <?php foreach ($cartItems as $item): ?>
                                <div class="cart-item flex flex-wrap md:flex-nowrap items-center px-4 py-4 md:px-6 md:py-4 border-b border-gray-200 last:border-b-0" data-product-id="<?= $item['product']['id'] ?>">
                                    <!-- Product Details (Image & Name) -->
                                    <div class="w-full md:w-2/5 flex items-center mb-4 md:mb-0">
                                        <div class="item-image w-16 h-16 md:w-20 md:h-20 mr-4 flex-shrink-0">
                                            <?php
                                                // Uses the correct 'image' key. Default placeholder if null.
                                                $image_path = $item['product']['image'] ?? '/images/placeholder.jpg';
                                            ?>
                                            <img src="<?= htmlspecialchars($image_path) ?>"
                                                 alt="<?= htmlspecialchars($item['product']['name'] ?? 'Product Image') ?>"
                                                 class="w-full h-full object-cover rounded border">
                                        </div>
                                        <div class="item-details flex-grow">
                                            <h3 class="font-semibold text-primary hover:text-accent text-sm md:text-base">
                                                <a href="index.php?page=product&id=<?= $item['product']['id'] ?>">
                                                    <?= htmlspecialchars($item['product']['name']) ?>
                                                </a>
                                            </h3>
                                            <!-- Optional: Display category or short desc -->
                                            <?php if (!empty($item['product']['category_name'])): ?>
                                                <p class="text-xs text-gray-500 hidden md:block"><?= htmlspecialchars($item['product']['category_name']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Price (Mobile Hidden, shown inline on desktop) -->
                                    <div class="item-price w-1/3 md:w-1/5 text-center md:text-base text-gray-700">
                                        <span class="md:hidden text-xs text-gray-500 mr-1">Price:</span>
                                        $<?= number_format($item['product']['price'], 2) ?>
                                    </div>

                                    <!-- Quantity -->
                                    <div class="item-quantity w-1/3 md:w-1/5 text-center flex justify-center items-center my-2 md:my-0">
                                        <div class="quantity-selector flex items-center border border-gray-300 rounded">
                                             <button type="button" class="quantity-btn minus w-8 h-8 md:w-10 md:h-10 text-lg md:text-xl font-light text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out rounded-l" aria-label="Decrease quantity">-</button>
                                             <input type="number" name="updates[<?= $item['product']['id'] ?>]"
                                                    value="<?= $item['quantity'] ?>" min="1" max="<?= (!empty($item['product']['backorder_allowed']) || !isset($item['product']['stock_quantity'])) ? 99 : max(1, $item['product']['stock_quantity']) ?>"
                                                    class="w-10 h-8 md:w-12 md:h-10 text-center border-l border-r border-gray-300 focus:outline-none focus:ring-1 focus:ring-primary text-sm"
                                                    aria-label="Product quantity">
                                             <button type="button" class="quantity-btn plus w-8 h-8 md:w-10 md:h-10 text-lg md:text-xl font-light text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out rounded-r" aria-label="Increase quantity">+</button>
                                        </div>
                                    </div>

                                    <!-- Subtotal -->
                                    <div class="item-subtotal w-1/3 md:w-1/5 text-right font-semibold md:text-base text-gray-900">
                                         <span class="md:hidden text-xs text-gray-500 mr-1">Subtotal:</span>
                                        $<?= number_format($item['subtotal'], 2) ?>
                                    </div>

                                    <!-- Remove Button -->
                                    <div class="w-full md:w-10 text-center md:text-right mt-2 md:mt-0 md:pl-2">
                                        <button type="button" class="remove-item text-gray-400 hover:text-red-600 transition duration-150 ease-in-out"
                                                data-product-id="<?= $item['product']['id'] ?>" title="Remove item">
                                            <i class="fas fa-times-circle text-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Cart Actions (Update Cart moved near items) -->
                        <div class="cart-actions text-right mt-4">
                            <button type="submit" class="btn btn-secondary update-cart">
                                <i class="fas fa-sync-alt mr-1"></i> Update Cart
                            </button>
                        </div>
                    </div>


                    <!-- Cart Summary Column -->
                    <div class="lg:col-span-1">
                        <div class="cart-summary bg-white shadow rounded-lg p-6 sticky top-24">
                            <h2 class="text-xl font-semibold mb-6 border-b pb-3">Order Summary</h2>
                            <div class="space-y-3 mb-6">
                                <div class="summary-row flex justify-between items-center">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="font-medium text-gray-900">$<?= number_format($total ?? 0, 2) ?></span>
                                </div>
                                <div class="summary-row shipping flex justify-between items-center">
                                    <span class="text-gray-600">Shipping:</span>
                                    <?php $shipping_cost = ($total ?? 0) >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST; ?>
                                    <span class="font-medium text-gray-900">
                                        <?= $shipping_cost == 0 ? '<span class="text-green-600">FREE</span>' : '$' . number_format($shipping_cost, 2) ?>
                                    </span>
                                </div>
                                <!-- Tax can be added here if calculated server-side initially or via JS -->
                                <!--
                                <div class="summary-row tax flex justify-between items-center">
                                    <span class="text-gray-600">Tax:</span>
                                    <span class="font-medium text-gray-900" id="cart-tax-amount">$0.00</span>
                                </div>
                                -->
                            </div>
                            <div class="summary-row total flex justify-between items-center border-t pt-4">
                                <span class="text-lg font-bold text-gray-900">Total:</span>
                                <span class="text-lg font-bold text-primary" id="cart-grand-total">
                                    $<?= number_format(($total ?? 0) + $shipping_cost, 2) ?>
                                </span>
                            </div>
                            <div class="mt-8">
                                <a href="index.php?page=checkout" class="btn btn-primary w-full text-center checkout <?= empty($cartItems) ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                    Proceed to Checkout
                                </a>
                            </div>
                            <p class="text-xs text-gray-500 text-center mt-4">Shipping & taxes calculated at checkout.</p>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

```

# controllers/BaseController.php  
```php
<?php
require_once __DIR__ . '/../includes/EmailService.php';

abstract class BaseController {
    protected $db;
    protected $securityMiddleware;
    protected $emailService;
    protected $responseHeaders = [];
    
    public function __construct($pdo) {
        $this->db = $pdo;
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->securityMiddleware = new SecurityMiddleware();
        $this->emailService = new EmailService($this->db); // Pass the PDO connection
        $this->initializeSecurityHeaders();
    }
    
    protected function initializeSecurityHeaders() {
        $this->responseHeaders = [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()'
        ];
    }
    
    protected function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        
        // Set security headers
        foreach ($this->responseHeaders as $header => $value) {
            header("$header: $value");
        }
        
        // Add CSRF token to responses that might lead to forms
        if ($this->shouldIncludeCSRFToken()) {
            $data['csrf_token'] = $this->securityMiddleware->generateCSRFToken();
        }
        
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($this->sanitizeOutput($data));
    }
    
    protected function sendError($message, $statusCode = 400, $context = []) {
        $errorResponse = [
            'error' => true,
            'message' => $message,
            'code' => $statusCode
        ];
        
        // Log error with context for monitoring
        ErrorHandler::logError($message, $context);
        
        // Only include debug info in development
        if (DEBUG_MODE && !empty($context)) {
            $errorResponse['debug'] = $context;
        }
        
        $this->sendResponse($errorResponse, $statusCode);
    }
    
    protected function validateRequest($rules) {
        $errors = [];
        $input = $this->getRequestInput();
        
        if (!is_array($rules) && !is_object($rules)) {
            // Defensive: if rules is not array/object, skip validation
            return true;
        }
        foreach ($rules as $field => $validations) {
            if (!isset($input[$field]) && strpos($validations, 'required') !== false) {
                $errors[$field] = "The {$field} field is required";
                continue;
            }
            
            if (isset($input[$field])) {
                $value = $input[$field];
                $validationArray = explode('|', $validations);
                
                foreach ($validationArray as $validation) {
                    if (!$this->validateField($value, $validation)) {
                        $errors[$field] = "The {$field} field failed {$validation} validation";
                    }
                }
            }
        }
        
        if (!empty($errors)) {
            $this->sendError('Validation failed', 422, ['validation_errors' => $errors]);
            return false;
        }
        
        return true;
    }
    
    protected function validateField($value, $rule) {
        switch ($rule) {
            case 'required':
                return !empty($value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL);
            case 'numeric':
                return is_numeric($value);
            case 'array':
                return is_array($value);
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL);
            // Add more validation rules as needed
        }
        
        // Check for min:x, max:x patterns
        if (preg_match('/^(min|max):(\d+)$/', $rule, $matches)) {
            $type = $matches[1];
            $limit = (int)$matches[2];
            
            if ($type === 'min') {
                return strlen($value) >= $limit;
            } else {
                return strlen($value) <= $limit;
            }
        }
        
        return true;
    }
    
    protected function getRequestInput() {
        $input = [];
        
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $input = $_GET;
                break;
            case 'POST':
            case 'PUT':
            case 'DELETE':
                $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
                
                if (strpos($contentType, 'application/json') !== false) {
                    $input = json_decode(file_get_contents('php://input'), true) ?? [];
                } else {
                    $input = $_POST;
                }
                break;
        }
        
        return $this->sanitizeInput($input);
    }
    
    protected function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        // Remove NULL bytes
        $data = str_replace(chr(0), '', $data);
        
        // Convert special characters to HTML entities
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    protected function sanitizeOutput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeOutput'], $data);
        }
        
        if (is_string($data)) {
            // Ensure proper UTF-8 encoding
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
        
        return $data;
    }
    
    protected function shouldIncludeCSRFToken() {
        $safeRoutes = [
            'login',
            'register',
            'password/reset',
            'checkout'
        ];
        
        $currentRoute = strtolower($_SERVER['REQUEST_URI']);
        
        foreach ($safeRoutes as $route) {
            if (strpos($currentRoute, $route) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function requireAuthentication() {
        if (!$this->securityMiddleware->isAuthenticated()) {
            $this->sendError('Unauthorized', 401);
            return false;
        }
        return true;
    }
    
    protected function requireCSRFToken() {
        if (!$this->securityMiddleware->validateCSRFToken()) {
            $this->sendError('Invalid CSRF token', 403);
            return false;
        }
        return true;
    }
    
    protected function rateLimit($key, $maxAttempts = 60, $decayMinutes = 1) {
        if (!$this->securityMiddleware->checkRateLimit($key, $maxAttempts, $decayMinutes)) {
            $this->sendError('Too many requests', 429);
            return false;
        }
        return true;
    }
    
    protected function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            $this->logSecurityEvent('unauthorized_access_attempt', [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'uri' => $_SERVER['REQUEST_URI']
            ]);
            $this->jsonResponse(['error' => 'Authentication required'], 401);
        }
        
        // Verify session integrity
        if (!$this->validateSessionIntegrity()) {
            $this->terminateSession('Session integrity check failed');
        }
        
        // Check session age and regenerate if needed
        if ($this->shouldRegenerateSession()) {
            $this->regenerateSession();
        }
    }
    
    protected function requireAdmin() {
        $this->requireLogin();
        
        if ($_SESSION['user_role'] !== 'admin') {
            $this->logSecurityEvent('unauthorized_admin_attempt', [
                'user_id' => $_SESSION['user_id'],
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            $this->jsonResponse(['error' => 'Admin access required'], 403);
        }
    }
    
    protected function validateInput($data, $rules) {
        $errors = [];
        if (!is_array($rules) && !is_object($rules)) {
            // Defensive: if rules is not array/object, skip validation
            return true;
        }
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field]) && $rule['required'] ?? false) {
                $errors[$field] = 'Field is required';
                continue;
            }
            
            if (isset($data[$field])) {
                $value = $data[$field];
                $error = $this->securityMiddleware->validateInput($value, $rule['type'], $rule);
                if ($error !== true) {
                    $errors[$field] = $error;
                }
            }
        }
        
        if (!empty($errors)) {
            $this->jsonResponse(['errors' => $errors], 422);
        }
        
        return true;
    }
    
    protected function getCsrfToken() {
        return SecurityMiddleware::generateCSRFToken();
    }
    
    protected function validateCSRF() {
        SecurityMiddleware::validateCSRF();
    }
    
    protected function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    protected function redirect($url, $statusCode = 302) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $url = BASE_URL . ltrim($url, '/');
        }
        
        header('Location: ' . $url, true, $statusCode);
        exit;
    }
    
    protected function setFlashMessage($message, $type = 'info') {
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type,
            'created' => time()
        ];
    }
    
    protected function getFlashMessage() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            
            // Ensure flash messages don't persist too long
            if (time() - $flash['created'] < 300) { // 5 minutes
                return $flash;
            }
        }
        return null;
    }
    
    protected function beginTransaction() {
        $this->db->beginTransaction();
    }
    
    protected function commit() {
        $this->db->commit();
    }
    
    protected function rollback() {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
    
    protected function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
    
    protected function getUserId() {
        return $_SESSION['user']['id'] ?? null;
    }
    
    protected function validateAjax() {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
        }
    }
    
    protected function isRateLimited($key, $maxAttempts, $timeWindow) {
        $rateLimitKey = "rate_limit:{$key}:" . $_SERVER['REMOTE_ADDR'];
        $attempts = $_SESSION[$rateLimitKey] ?? ['count' => 0, 'first_attempt' => time()];
        
        if (time() - $attempts['first_attempt'] > $timeWindow) {
            // Reset if time window has passed
            $attempts = ['count' => 1, 'first_attempt' => time()];
        } else {
            $attempts['count']++;
        }
        
        $_SESSION[$rateLimitKey] = $attempts;
        
        return $attempts['count'] > $maxAttempts;
    }
    
    protected function renderView($viewPath, $data = []) {
        // Extract data to make it available in view
        extract($data);
        
        // Start output buffering
        ob_start();
        
        $viewFile = __DIR__ . '/../views/' . $viewPath . '.php';
        if (!file_exists($viewFile)) {
            throw new Exception("View not found: {$viewPath}");
        }
        
        require $viewFile;
        
        return ob_get_clean();
    }
    
    protected function validateFileUpload($file, $allowedTypes, $maxSize = 5242880) { // 5MB default
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid file upload');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File too large');
            case UPLOAD_ERR_PARTIAL:
                throw new Exception('File upload interrupted');
            default:
                throw new Exception('Unknown upload error');
        }

        if ($file['size'] > $maxSize) {
            throw new Exception('File too large');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type');
        }

        return true;
    }
    
    protected function log($message, $level = 'info') {
        $logFile = __DIR__ . '/../logs/' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        error_log($formattedMessage, 3, $logFile);
    }

    protected function checkRateLimit($key, $limit = null, $window = null) {
        $limit = $limit ?? $this->rateLimit['max_requests'];
        $window = $window ?? $this->rateLimit['window'];
        
        $redis = RedisConnection::getInstance();
        $requests = $redis->incr("rate_limit:{$key}");
        
        if ($requests === 1) {
            $redis->expire("rate_limit:{$key}", $window);
        }
        
        return $requests <= $limit;
    }

    protected function logAuditTrail($action, $userId, $details = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_log (
                    action, user_id, ip_address, user_agent, details
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $action,
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                json_encode($details)
            ]);
        } catch (Exception $e) {
            error_log("Audit logging failed: " . $e->getMessage());
        }
    }

    private function validateSessionIntegrity() {
        if (!isset($_SESSION['user_agent']) || !isset($_SESSION['ip_address'])) {
            return false;
        }
        
        return $_SESSION['user_agent'] === $_SERVER['HTTP_USER_AGENT'] &&
               $_SESSION['ip_address'] === $_SERVER['REMOTE_ADDR'];
    }
    
    private function shouldRegenerateSession() {
        return !isset($_SESSION['last_regeneration']) ||
               (time() - $_SESSION['last_regeneration']) > SECURITY_SETTINGS['session']['regenerate_id_interval'];
    }
    
    private function regenerateSession() {
        $oldSession = $_SESSION;
        session_regenerate_id(true);
        $_SESSION = $oldSession;
        $_SESSION['last_regeneration'] = time();
    }
    
    protected function terminateSession($reason) {
        $userId = $_SESSION['user_id'] ?? null;
        $this->logSecurityEvent('session_terminated', [
            'reason' => $reason,
            'user_id' => $userId
        ]);
        
        session_destroy();
        $this->jsonResponse(['error' => 'Session terminated for security reasons'], 401);
    }
    
    protected function validateRateLimit($action) {
        $settings = SECURITY_SETTINGS['rate_limiting']['endpoints'][$action] ?? [
            'window' => SECURITY_SETTINGS['rate_limiting']['default_window'],
            'max_requests' => SECURITY_SETTINGS['rate_limiting']['default_max_requests']
        ];
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = "rate_limit:{$action}:{$ip}";
        // Check whitelist
        if (in_array($ip, SECURITY_SETTINGS['rate_limiting']['ip_whitelist'] ?? [])) {
            return true;
        }
        // Fail closed if APCu is unavailable
        if (!function_exists('apcu_fetch') || !ini_get('apc.enabled')) {
            $this->logSecurityEvent('rate_limit_backend_unavailable', [
                'action' => $action,
                'ip' => $ip
            ]);
            $this->jsonResponse(['error' => 'Rate limiting backend unavailable. Please try again later.'], 503);
        }
        $attempts = apcu_fetch($key) ?: 0;
        if ($attempts >= $settings['max_requests']) {
            $this->logSecurityEvent('rate_limit_exceeded', [
                'action' => $action,
                'ip' => $ip,
                'attempts' => $attempts
            ]);
            $this->jsonResponse(['error' => 'Rate limit exceeded. Please try again later.'], 429);
        }
        // Increment the counter or add it if it doesn't exist
        if ($attempts === 0) {
            apcu_store($key, 1, $settings['window']);
        } else {
            apcu_inc($key);
        }
        return true;
    }
    
    protected function validateCSRFToken() {
        if (!SECURITY_SETTINGS['csrf']['enabled']) {
            return true;
        }
        
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->logSecurityEvent('csrf_validation_failed', [
                'user_id' => $_SESSION['user_id'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            $this->jsonResponse(['error' => 'CSRF token validation failed'], 403);
        }
        
        return true;
    }
    
    protected function generateCSRFToken() {
        if (!SECURITY_SETTINGS['csrf']['enabled']) {
            return '';
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(SECURITY_SETTINGS['csrf']['token_length']));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    protected function logSecurityEvent($event, $details = []) {
        $details = array_merge($details, [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        error_log(
            sprintf(
                "[SECURITY] %s | %s",
                $event,
                json_encode($details)
            ),
            3,
            SECURITY_SETTINGS['logging']['security_log']
        );
    }
}

```

