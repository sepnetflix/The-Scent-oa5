<?php
define('ROOT_PATH', __DIR__);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/SecurityMiddleware.php';
require_once __DIR__ . '/includes/ErrorHandler.php';

// Initialize error handling
ErrorHandler::init();

// Apply security middleware
SecurityMiddleware::apply();

try {
    // Handle routing
    $page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string');
    $action = SecurityMiddleware::validateInput($_GET['action'] ?? 'index', 'string');
    
    // Validate CSRF token for POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        SecurityMiddleware::validateCSRF();
    }
    
    // Route to appropriate controller/action
    switch ($page) {
        case 'home':
            require_once __DIR__ . '/controllers/ProductController.php';
            $productController = new ProductController($pdo);
            $productController->showHomePage();
            break;
        case 'product':
            require_once __DIR__ . '/controllers/ProductController.php';
            $productController = new ProductController($pdo);
            $productController->showProduct($_GET['id'] ?? null);
            break;
        case 'products':
            require_once __DIR__ . '/controllers/ProductController.php';
            $productController = new ProductController($pdo);
            $productController->showProductList();
            break;
        case 'cart':
            require_once __DIR__ . '/controllers/CartController.php';
            $controller = new CartController($pdo);
            
            if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                // AJAX Add to Cart endpoint
                $controller->addToCart();
                // jsonResponse will exit
            }
            
            if ($action === 'mini') {
                // AJAX Mini Cart endpoint
                $controller->mini();
                // jsonResponse will exit
            }
            
            $cartItems = $controller->getCartItems();
            require_once __DIR__ . '/views/cart.php';
            break;
            
        case 'checkout':
            if (!isLoggedIn()) {
                $_SESSION['redirect_after_login'] = 'checkout';
                header('Location: index.php?page=login');
                exit;
            }
            
            require_once __DIR__ . '/controllers/CheckoutController.php';
            $controller = new CheckoutController($pdo);
            
            if ($action === 'process') {
                $controller->processCheckout($_POST);
            } else {
                $cartItems = (new CartController($pdo))->getCartItems();
                if (empty($cartItems)) {
                    header('Location: index.php?page=cart');
                    exit;
                }
                require_once __DIR__ . '/views/checkout.php';
            }
            break;
            
        case 'register':
            if (isLoggedIn()) {
                header('Location: index.php?page=account');
                exit;
            }
            require_once __DIR__ . '/controllers/AccountController.php';
            $controller = new AccountController($pdo);
            
            if ($action === 'submit') {
                $controller->register();
            } else {
                require_once __DIR__ . '/views/register.php';
            }
            break;
            
        case 'quiz':
            require_once __DIR__ . '/controllers/QuizController.php';
            $controller = new QuizController($pdo);
            
            if ($action === 'submit') {
                $results = $controller->processQuiz($_POST);
                require_once __DIR__ . '/views/quiz_results.php';
            } else {
                $questions = $controller->getQuestions();
                require_once __DIR__ . '/views/quiz.php';
            }
            break;
            
        case 'admin':
            require_once __DIR__ . '/includes/auth.php';
            if (!isAdmin()) {
                header('Location: index.php?page=login');
                exit;
            }

            $section = SecurityMiddleware::validateInput($_GET['section'] ?? 'dashboard', 'string');
            
            switch ($section) {
                case 'quiz_analytics':
                    require_once __DIR__ . '/controllers/QuizController.php';
                    $controller = new QuizController($pdo);
                    $controller->getAnalytics();
                    break;
                    
                default:
                    require_once __DIR__ . '/views/admin/dashboard.php';
                    break;
            }
            break;

        case 'contact':
            $pageTitle = 'Contact Us';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            require_once __DIR__ . '/views/contact.php';
            break;
        case 'faq':
            $pageTitle = 'FAQs';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            require_once __DIR__ . '/views/faq.php';
            break;
        case 'shipping':
            $pageTitle = 'Shipping & Returns';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            require_once __DIR__ . '/views/shipping.php';
            break;
        case 'order-tracking':
            $pageTitle = 'Track Your Order';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            require_once __DIR__ . '/views/order-tracking.php';
            break;
        case 'privacy':
            $pageTitle = 'Privacy Policy';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            require_once __DIR__ . '/views/privacy.php';
            break;
            
        default:
            http_response_code(404);
            require_once __DIR__ . '/views/404.php';
            break;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    throw new Exception('A database error occurred');
} catch (Exception $e) {
    // ErrorHandler will catch and handle the exception
    throw $e;
}
