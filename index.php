<?php
echo '<!-- DEBUG: index.php loaded -->';
define('ROOT_PATH', dirname(__DIR__));
require_once './config.php';
require_once './includes/db.php';
require_once './includes/auth.php';
require_once './includes/SecurityMiddleware.php';
require_once './includes/ErrorHandler.php';

// Initialize error handling
ErrorHandler::init();

// Apply security middleware
SecurityMiddleware::apply();

try {
    // Load core dependencies
    require_once './controllers/ProductController.php';
    
    // Handle routing
    $page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string');
    $action = SecurityMiddleware::validateInput($_GET['action'] ?? 'index', 'string');
    
    // Validate CSRF token for POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        SecurityMiddleware::validateCSRF();
    }
    
    // Route to appropriate controller/action
    $productController = new ProductController($pdo);
    switch ($page) {
        case 'home':
            $productController->showHomePage();
            break;
        case 'product':
            $productController->showProduct($_GET['id'] ?? null);
            break;
        case 'products':
            $productController->showProductList();
            break;
            
        case 'cart':
            require_once './controllers/CartController.php';
            $controller = new CartController($pdo);
            
            if ($action === 'add') {
                $productId = SecurityMiddleware::validateInput($_POST['product_id'] ?? null, 'int');
                $quantity = SecurityMiddleware::validateInput($_POST['quantity'] ?? 1, 'int');
                $controller->addToCart($productId, $quantity);
                header('Location: index.php?page=cart');
                exit;
            }
            
            $cartItems = $controller->getCartItems();
            require_once './views/cart.php';
            break;
            
        case 'checkout':
            if (!isLoggedIn()) {
                $_SESSION['redirect_after_login'] = 'checkout';
                header('Location: index.php?page=login');
                exit;
            }
            
            require_once './controllers/CheckoutController.php';
            $controller = new CheckoutController($pdo);
            
            if ($action === 'process') {
                $controller->processCheckout($_POST);
            } else {
                $cartItems = (new CartController($pdo))->getCartItems();
                if (empty($cartItems)) {
                    header('Location: index.php?page=cart');
                    exit;
                }
                require_once './views/checkout.php';
            }
            break;
            
        case 'register':
            if (isLoggedIn()) {
                header('Location: index.php?page=account');
                exit;
            }
            require_once './controllers/AccountController.php';
            $controller = new AccountController($pdo);
            
            if ($action === 'submit') {
                $controller->register();
            } else {
                require_once './views/register.php';
            }
            break;
            
        case 'quiz':
            require_once './controllers/QuizController.php';
            $controller = new QuizController($pdo);
            
            if ($action === 'submit') {
                $results = $controller->processQuiz($_POST);
                require_once './views/quiz_results.php';
            } else {
                $questions = $controller->getQuestions();
                require_once './views/quiz.php';
            }
            break;
            
        case 'admin':
            require_once './includes/auth.php';
            if (!isAdmin()) {
                header('Location: index.php?page=login');
                exit;
            }

            $section = SecurityMiddleware::validateInput($_GET['section'] ?? 'dashboard', 'string');
            
            switch ($section) {
                // ...existing sections...
                case 'quiz_analytics':
                    require_once './controllers/QuizController.php';
                    $controller = new QuizController($pdo);
                    $controller->getAnalytics();
                    break;
                    
                default:
                    require_once './views/admin/dashboard.php';
                    break;
            }
            break;
            
        // Add other routes as needed...
            
        default:
            http_response_code(404);
            require_once './views/404.php';
            break;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    throw new Exception('A database error occurred');
} catch (Exception $e) {
    // ErrorHandler will catch and handle the exception
    throw $e;
}
