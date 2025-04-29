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
        // Note: Some controllers might re-validate CSRF internally for specific actions
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
                 http_response_code(404);
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
                    // Default POST to cart page is unusual, maybe show 405 or redirect?
                    http_response_code(405); // Method Not Allowed
                    echo "Method not allowed for this resource.";
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

             // Check if cart is empty only for the main checkout page display
             if (empty($action)) { // Only check if no action specified (i.e., loading the main page)
                $cartCtrl = new CartController($pdo); // Instantiate to check cart
                $cartItems = $cartCtrl->getCartItems();
                if (empty($cartItems)) {
                    header('Location: ' . BASE_URL . 'index.php?page=products');
                    exit;
                }
            }

            $controller = new CheckoutController($pdo);
            if ($action === 'processCheckout' && $_SERVER['REQUEST_METHOD'] === 'POST') { // Matched JS call action name
                $controller->processCheckout(); // Exits via jsonResponse
            } elseif ($action === 'confirmation') { // GET request typically after payment redirect
                 $controller->showOrderConfirmation(); // Shows the confirmation view
            } elseif ($action === 'calculateTax' && $_SERVER['REQUEST_METHOD'] === 'POST') { // Matched JS call action name
                $controller->calculateTax(); // Exits via jsonResponse
            } elseif ($action === 'applyCouponAjax' && $_SERVER['REQUEST_METHOD'] === 'POST') { // Matched JS call action name and added route
                 // No need to include CheckoutController again if already included
                 $controller->applyCouponAjax(); // Call method in CheckoutController
                 // **Alternative:** Call CouponController directly if preferred
                 // require_once __DIR__ . '/controllers/CouponController.php';
                 // $couponController = new CouponController($pdo);
                 // $couponController->applyCouponAjax(); // Assumes method exists there
            }
            else {
                // Default GET request: show the checkout page
                $controller->showCheckout();
            }
            break;

        // --- Account Related Routes (No changes from previous version) ---
        case 'login':
            if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
            require_once __DIR__ . '/controllers/AccountController.php';
            $controller = new AccountController($pdo);
            $controller->login();
            break;
        case 'register':
            if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
            require_once __DIR__ . '/controllers/AccountController.php';
            $controller = new AccountController($pdo);
            $controller->register();
            break;
        case 'logout':
             logoutUser();
             header('Location: ' . BASE_URL . 'index.php?page=login&loggedout=1');
             exit;
        case 'account':
             if (!isLoggedIn()) {
                 $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=account' . ($action ? '&action=' . $action : '');
                 header('Location: ' . BASE_URL . 'index.php?page=login');
                 exit;
             }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo);
             switch ($action) {
                 case 'profile': $controller->showProfile(); break;
                 case 'update_profile':
                     if ($_SERVER['REQUEST_METHOD'] === 'POST') { $controller->updateProfile(); }
                     else { header('Location: ' . BASE_URL . 'index.php?page=account&action=profile'); exit; }
                     break;
                 case 'orders': $controller->showOrders(); break;
                 case 'order_details':
                     if ($id) { $controller->showOrderDetails($id); }
                     else { header('Location: ' . BASE_URL . 'index.php?page=account&action=orders'); exit; }
                     break;
                 case 'update_newsletter':
                     if ($_SERVER['REQUEST_METHOD'] === 'POST') { $controller->updateNewsletterPreferences(); }
                     else { header('Location: ' . BASE_URL . 'index.php?page=account&action=profile'); exit; }
                     break;
                 case 'dashboard': default: $controller->showDashboard(); break;
             }
             break;
        case 'forgot_password':
            if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo);
             $controller->requestPasswordReset();
             break;
        case 'reset_password':
             if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo);
             $controller->resetPassword();
             break;

        // --- Other Routes (No changes from previous version) ---
        case 'quiz':
            require_once __DIR__ . '/controllers/QuizController.php';
            $controller = new QuizController($pdo);
            if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') { $controller->processQuiz(); }
            else { $controller->showQuiz(); }
            break;
        case 'newsletter':
             require_once __DIR__ . '/controllers/NewsletterController.php';
             $controller = new NewsletterController($pdo);
             if ($action === 'subscribe' && $_SERVER['REQUEST_METHOD'] === 'POST') { $controller->subscribe(); }
             elseif ($action === 'unsubscribe') { $controller->unsubscribe(); }
             else { http_response_code(404); require_once __DIR__ . '/views/404.php'; }
             break;
        case 'admin':
             if (!isAdmin()) {
                 $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=admin';
                 header('Location: ' . BASE_URL . 'index.php?page=login'); exit;
             }
             $section = SecurityMiddleware::validateInput($_GET['section'] ?? 'dashboard', 'string');
             $task = SecurityMiddleware::validateInput($_GET['task'] ?? null, 'string');
             switch ($section) {
                 case 'quiz_analytics':
                     require_once __DIR__ . '/controllers/QuizController.php';
                     $controller = new QuizController($pdo); $controller->showAnalytics(); break;
                 case 'coupons':
                    require_once __DIR__ . '/controllers/CouponController.php';
                    $controller = new CouponController($pdo);
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                         if ($task === 'save') { $controller->saveCoupon(); } // Assume this method handles create/update and redirects/responds
                         elseif ($task === 'toggle_status' && $id) { $controller->toggleCouponStatus($id); } // Assume this responds (e.g., JSON)
                         elseif ($task === 'delete' && $id) { $controller->deleteCoupon($id); } // Assume this responds (e.g., JSON)
                         else { $controller->listCoupons(); } // Default POST? Redirect likely better
                    } else { // GET
                         if ($task === 'edit' && $id) { $controller->showEditForm($id); } // Assume renders view
                         elseif ($task === 'create') { $controller->showCreateForm(); } // Assume renders view
                         else { $controller->listCoupons(); } // Assume renders view
                    }
                    break;
                 // Add other admin sections...
                 default: // Admin Dashboard
                      $pageTitle = "Admin Dashboard"; $bodyClass = "page-admin-dashboard";
                      $csrfToken = SecurityMiddleware::generateCSRFToken();
                      extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
                      require_once __DIR__ . '/views/admin/dashboard.php'; break;
             }
             break;

        // --- Static Pages (No changes from previous version) ---
        case 'contact':
            $pageTitle = 'Contact Us'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-contact';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/contact.php'; break;
        case 'faq':
            $pageTitle = 'FAQs'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-faq';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/faq.php'; break;
        case 'shipping':
            $pageTitle = 'Shipping & Returns'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-shipping';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/shipping.php'; break;
        case 'order-tracking':
            $pageTitle = 'Track Your Order'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-order-tracking';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/order-tracking.php'; break;
        case 'privacy':
            $pageTitle = 'Privacy Policy'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-privacy';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/privacy.php'; break;
        case 'about':
             $pageTitle = 'About Us - The Scent'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-about';
             extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
             require_once __DIR__ . '/views/about.php'; break;
        case 'error':
            $pageTitle = 'Error'; $bodyClass = 'page-error'; $csrfToken = SecurityMiddleware::generateCSRFToken();
            http_response_code(500);
            extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
            require_once __DIR__ . '/views/error.php'; break;

        default: // 404 Not Found
            http_response_code(404);
            $pageTitle = 'Page Not Found'; $bodyClass = 'page-404'; $csrfToken = SecurityMiddleware::generateCSRFToken();
            extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
            require_once __DIR__ . '/views/404.php'; break;
    }
} catch (PDOException $e) {
    ErrorHandler::handleException($e);
    exit(1);
} catch (\Stripe\Exception\ApiErrorException $e) { // Catch Stripe API errors specifically
     error_log("Stripe API error in routing: " . $e->getMessage());
     ErrorHandler::handleException($e); // Let ErrorHandler manage display
     exit(1);
} catch (Throwable $e) { // Catch other exceptions
    error_log("General error/exception in index.php: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
    ErrorHandler::handleException($e);
    exit(1);
}
