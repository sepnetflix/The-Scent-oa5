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
