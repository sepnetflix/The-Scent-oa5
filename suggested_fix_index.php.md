Okay, let's update `index.php` to correctly route requests to the refactored `AccountController` methods and address potential impacts on other files.

**Updated `index.php`:**

```php
<?php
define('ROOT_PATH', __DIR__);
require_once __DIR__ . '/config.php'; // Defines BASE_URL, etc.
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
                  echo $this->renderView('404', ['pageTitle' => 'Not Found']); // Use renderView if available in this context
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
                            $controller->saveCoupon(); // Handles create/update, redirects
                        }
                    } elseif ($task === 'toggle_status' && $id) {
                        $controller->toggleCouponStatus($id); // Handles JSON response or redirect
                    } elseif ($task === 'delete' && $id) {
                        $controller->deleteCoupon($id); // Handles JSON response or redirect
                    } else {
                        $controller->listCoupons(); // Default action: show list
                    }
                    break;
                // Add other admin sections (products, orders, users) here...
                // case 'products': ...
                // case 'orders': ...
                default: // Admin Dashboard
                    // Load admin dashboard view directly or via a controller method
                     $pageTitle = "Admin Dashboard"; // Example title
                     require_once __DIR__ . '/views/admin/dashboard.php'; // Direct include for simple dashboard
                    break;
            }
            break; // End of 'admin' page case

        // --- Static Pages ---
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
        case 'order-tracking': // Maybe needs a controller if dynamic
            $pageTitle = 'Track Your Order';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            require_once __DIR__ . '/views/order-tracking.php';
            break;
        case 'privacy':
            $pageTitle = 'Privacy Policy';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            require_once __DIR__ . '/views/privacy.php';
            break;
        case 'about': // Add route for about page if needed
             $pageTitle = 'About Us - The Scent';
             $csrfToken = SecurityMiddleware::generateCSRFToken();
             require_once __DIR__ . '/views/about.php'; // Assuming views/about.php exists
             break;
        case 'error': // Explicit error page route
            $pageTitle = 'Error';
            http_response_code(500); // Set appropriate code if possible
            require_once __DIR__ . '/views/error.php';
            break;

        default: // 404 Not Found
            http_response_code(404);
            require_once __DIR__ . '/views/404.php';
            break;
    }
} catch (PDOException $e) {
    error_log("Database error in index.php: " . $e->getMessage());
    // Delegate to ErrorHandler or display generic error view
    ErrorHandler::handleException($e); // Let the handler manage display/logging
    // require_once __DIR__ . '/views/error.php'; // Fallback if handler doesn't exit
    exit();
} catch (Exception $e) {
    // Catch other exceptions (e.g., CSRF, routing errors)
    error_log("General error in index.php: " . $e->getMessage());
    // Delegate to ErrorHandler
    ErrorHandler::handleException($e);
    // require_once __DIR__ . '/views/error.php'; // Fallback
    exit();
}
```

**Explanation of Changes in `index.php`:**

1.  **Account Routes Added:**
    *   `case 'login'`: Calls `AccountController->login()`.
    *   `case 'register'`: Simplified to call `AccountController->register()`.
    *   `case 'logout'`: Added a simple logout mechanism calling `logoutUser()` (from `includes/auth.php`) and redirecting.
    *   `case 'account'`: Added a new block with an inner `switch ($action)` to handle dashboard, profile, orders, etc., calling the respective methods in `AccountController`. Includes checks for logged-in status and required IDs.
    *   `case 'forgot_password'`: Calls `AccountController->requestPasswordReset()`.
    *   `case 'reset_password'`: Calls `AccountController->resetPassword()`.
2.  **Login Checks:** Added `isLoggedIn()` checks before accessing account pages or checkout, redirecting to login if necessary. Also added checks to redirect *away* from login/register/forgot/reset if the user *is* already logged in.
3.  **Parameter Handling:** Ensured `$id` is validated and passed where needed (e.g., `order_details`).
4.  **Base URL:** Used the `BASE_URL` constant from `config.php` for more reliable redirects.
5.  **Newsletter Route:** Added a dedicated `case 'newsletter':` for clarity, calling the `NewsletterController`.
6.  **Admin Route:** Refined the admin section slightly, adding a `$task` variable concept for actions within a section (example shown for coupons).
7.  **Error Handling:** Ensured the main `try...catch` delegates to the `ErrorHandler` for consistency.
8.  **Dependencies:** Included `AccountController.php` where needed.
9.  **Code Style:** Minor adjustments for consistency.

**Other Files Needing Updates/Verification:**

1.  **`js/main.js` (CRITICAL):**
    *   The `login()`, `register()`, `requestPasswordReset()`, and `resetPassword()` methods in `AccountController` now primarily return JSON.
    *   The corresponding event listeners in `main.js` (within `initLoginPage`, `initRegisterPage`, `initForgotPasswordPage`, `initResetPasswordPage`) need to be **rewritten** to:
        *   Use `fetch` to submit the form data asynchronously.
        *   Call `event.preventDefault()` to stop the default form submission.
        *   Handle the JSON response (`{success: true/false, message: '...', redirect: '...'}`).
        *   Show success/error messages using `showFlashMessage()`.
        *   Perform the redirect using `window.location.href = data.redirect;` on success.
        *   Handle potential network errors.
        *   Update the button's loading state correctly during and after the AJAX request.
    *   The `updateNewsletterPreferences()` method also likely expects an AJAX submission if the profile form updates parts of the page dynamically, otherwise, its standard form post + redirect might be acceptable. The current JS doesn't seem to handle profile updates via AJAX.

2.  **`views/` Directory:**
    *   **New Account Views:** You need to create the view files corresponding to the `renderView` calls in `AccountController`:
        *   `views/account_dashboard.php`
        *   `views/account_profile.php` (Must contain forms submitting to `action=update_profile` and `action=update_newsletter` with CSRF tokens)
        *   `views/account_orders.php`
        *   `views/account_order_details.php`
    *   **Existing Views:** Ensure `login.php`, `register.php`, `forgot_password.php`, `reset_password.php` have the necessary form elements (including CSRF token inputs) and potentially elements for displaying AJAX validation errors if you implement that in JS.

3.  **`includes/auth.php`:**
    *   Verify it provides `isLoggedIn()`, `isAdmin()`, and `logoutUser()` functions that work correctly with the session structure set by `AccountController::login()`.

4.  **Models (`User.php`, `Order.php`, etc.):**
    *   Double-check that all methods called by `AccountController` (e.g., `isEmailTakenByOthers`, `updateBasicInfo`, `verifyPassword`, `setResetToken`, `getUserByValidResetToken`, `resetPassword`, `updateNewsletterPreference`, `getRecentByUserId`, `getAllByUserId`, `getTotalOrdersByUserId`, `getByIdAndUserId`) exist and function as expected.

The `index.php` update provides the necessary routing structure. The **most critical next step** is updating `js/main.js` to handle the AJAX form submissions for login, registration, and password reset to work seamlessly with the controller's JSON responses.
