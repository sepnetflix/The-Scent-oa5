Okay, the previous fatal error regarding `ErrorHandler` is resolved, as indicated by the fact that some pages now return HTTP 200 (`contact`, `about`) and others return HTTP 500 with a *different* error message.

**1. Analyze the New Error:**

The Apache error log (`apache-error.log`) now consistently shows this error for pages handled by controllers inheriting from `BaseController` (like home, product, products, login, register):

```
PHP message: Uncaught Exception 'Error': "Class "PHPMailer\\PHPMailer\\PHPMailer" not found" in /cdrom/project/The-Scent-oa5/includes/EmailService.php:45
```

**Stack Trace Breakdown:**

1.  The error happens in `EmailService.php` on line 45, likely where it tries `new PHPMailer(true);`.
2.  This is called from the `EmailService->__construct()` method (line 41).
3.  `EmailService` is instantiated in the `BaseController->__construct()` method (line 14).
4.  Controllers like `ProductController` (line 11) and `AccountController` (line 22) call the `BaseController` constructor when they are instantiated.
5.  These controllers are instantiated in `index.php` (lines 33, 39, 50, 117, 127, etc.).

**Root Cause:**

The application uses PHPMailer (a third-party library) for sending emails via the `EmailService`. PHP cannot find the `PHPMailer\PHPMailer\PHPMailer` class definition. This almost always means:

*   **Composer Dependency Missing:** PHPMailer was likely intended to be installed using Composer, a PHP dependency manager.
*   **Composer Autoloader Not Included:** Even if installed, the file that tells PHP *how* to find Composer-managed classes (`vendor/autoload.php`) is not being included early enough in the execution flow. `BaseController` instantiates `EmailService` *before* the autoloader is included (some controllers like `PaymentController` include it, but too late for `BaseController`).

**2. Solution:**

1.  **Ensure PHPMailer is Installed:** If you haven't already, you need Composer installed. Navigate to your project root (`/cdrom/project/The-Scent-oa5`) in the terminal and run:
    ```bash
    composer require phpmailer/phpmailer
    ```
    This will create/update `composer.json`, `composer.lock`, and install the library into a `vendor/` directory, including the crucial `vendor/autoload.php` file.

2.  **Include Composer Autoloader Globally:** Modify `index.php` to include the autoloader *before* any controllers are instantiated.

**3. Code Implementation:**

**Modify `index.php`:**

Add the Composer autoloader include near the top, after the other initial requires.

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

**Modify `controllers/PaymentController.php`:**

Remove the redundant autoloader include.

```php
<?php
require_once __DIR__ . '/BaseController.php';
// Remove this line: require_once __DIR__ . '/../vendor/autoload.php'; // Now loaded globally
require_once __DIR__ . '/../config.php'; // Keep config include

// Use statement for StripeClient if not already present
use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;


class PaymentController extends BaseController {
    private $stripe;
    private $webhookSecret;

    public function __construct($pdo = null) {
        parent::__construct($pdo); // BaseController handles EmailService init now

        // Ensure Stripe keys are defined
        if (!defined('STRIPE_SECRET_KEY') || !defined('STRIPE_WEBHOOK_SECRET')) {
            error_log("Stripe keys are not defined in config.php");
            // Depending on context, might throw an exception or handle gracefully
            // For now, let StripeClient constructor potentially fail if key is missing
        }

        // Use try-catch for external service initialization
        try {
            $this->stripe = new StripeClient(STRIPE_SECRET_KEY);
            $this->webhookSecret = STRIPE_WEBHOOK_SECRET;
        } catch (\Exception $e) {
             error_log("Failed to initialize Stripe client: " . $e->getMessage());
             // Handle error appropriately, maybe throw exception to be caught by ErrorHandler
             // throw new Exception("Payment system configuration error.");
             $this->stripe = null; // Ensure stripe is null if init fails
             $this->webhookSecret = null;
        }
    }

    public function createPaymentIntent(float $amount, string $currency = 'usd', int $orderId = 0, string $customerEmail = '') {
        // Ensure Stripe client is initialized
        if (!$this->stripe) {
             return ['success' => false, 'error' => 'Payment system unavailable.'];
        }

        try {
            // Basic validation (more robust validation might be needed)
            if ($amount <= 0) {
                throw new Exception('Invalid payment amount.');
            }
            $currency = strtolower(trim($currency));
            if (strlen($currency) !== 3) {
                 throw new Exception('Invalid currency code.');
            }

            $paymentIntentParams = [
                'amount' => (int)round($amount * 100), // Convert to cents, use round() for precision
                'currency' => $currency,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'user_id' => $this->getUserId() ?? 'guest',
                    'order_id' => $orderId, // Include order ID
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
                ]
            ];

             // Add customer email if provided (helps Stripe Radar, guest checkout)
             if (!empty($customerEmail)) {
                 // Optional: Find or create Stripe Customer ID first for better tracking
                 // $paymentIntentParams['customer'] = $this->getStripeCustomerId($customerEmail);
                 // Or just add receipt email
                 $paymentIntentParams['receipt_email'] = $customerEmail;
             }


            $paymentIntent = $this->stripe->paymentIntents->create($paymentIntentParams);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret // Correct key name
            ];

        } catch (ApiErrorException $e) {
            error_log("Stripe API Error creating PaymentIntent: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
            return [
                'success' => false,
                'error' => 'Payment processing failed. Please try again or contact support.' // User-friendly
            ];
        } catch (Exception $e) {
            error_log("Payment Intent Creation Error: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
            return [
                'success' => false,
                'error' => 'Invalid payment request. Please check details and try again.' // User-friendly
            ];
        }
    }

    // processPayment seems deprecated by the CheckoutController logic which calls createPaymentIntent directly
    // If needed, it should be updated to fit the current checkout flow.
    /*
    public function processPayment($orderId) {
        // ... (Needs review based on actual usage) ...
    }
    */

    public function handleWebhook() {
         // Ensure Stripe client is initialized
        if (!$this->stripe || !$this->webhookSecret) {
             http_response_code(503); // Service Unavailable
             error_log("Webhook handler cannot run: Stripe client or secret not initialized.");
             echo json_encode(['error' => 'Webhook configuration error.']);
             exit;
        }

        $payload = @file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;

        if (!$sigHeader) {
             error_log("Webhook Error: Missing Stripe signature header.");
             return $this->jsonResponse(['error' => 'Missing signature'], 400);
        }
        if (empty($payload)) {
             error_log("Webhook Error: Empty payload received.");
             return $this->jsonResponse(['error' => 'Empty payload'], 400);
        }


        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $this->webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            error_log("Webhook Error: Invalid payload. " . $e->getMessage());
            return $this->jsonResponse(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            error_log("Webhook Error: Invalid signature. " . $e->getMessage());
            return $this->jsonResponse(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            // Other construction error
            error_log("Webhook Error: Event construction failed. " . $e->getMessage());
            return $this->jsonResponse(['error' => 'Webhook processing error'], 400);
        }

        // Handle the event
        try {
            $this->beginTransaction(); // Start transaction for DB updates

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handleSuccessfulPayment($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handleFailedPayment($event->data->object);
                    break;

                case 'charge.succeeded':
                     // Often redundant if using Payment Intents, but can be useful for logging charge details
                     $this->handleChargeSucceeded($event->data->object);
                     break;

                case 'charge.dispute.created':
                    $this->handleDisputeCreated($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleRefund($event->data->object);
                    break;

                // Add other event types as needed (e.g., checkout.session.completed for Stripe Checkout)

                default:
                    error_log('Webhook Warning: Received unhandled event type ' . $event->type);
            }

            $this->commit(); // Commit DB changes if no exceptions
            return $this->jsonResponse(['success' => true, 'message' => 'Webhook received']);

        } catch (Exception $e) {
            $this->rollback(); // Rollback DB changes on error
            error_log("Webhook Handling Error (Event: {$event->type}): " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            // Return 500 to signal Stripe to retry (if applicable for the error type)
            return $this->jsonResponse(
                ['success' => false, 'error' => 'Internal server error handling webhook.'],
                500 // Use 500 for internal errors where retry might help
            );
        }
    }

    private function handleSuccessfulPayment(\Stripe\PaymentIntent $paymentIntent): void {
        // Find order by payment_intent_id (ensure this column exists and is set)
         $stmt = $this->db->prepare("
             SELECT id, user_id, total_amount, status
             FROM orders
             WHERE payment_intent_id = ?
             LIMIT 1
         ");
         $stmt->execute([$paymentIntent->id]);
         $order = $stmt->fetch();

         if (!$order) {
              // Critical: Payment succeeded but no matching order found in DB
              $errorMessage = "Webhook Critical: PaymentIntent {$paymentIntent->id} succeeded but no matching order found.";
              error_log($errorMessage);
              $this->logSecurityEvent('webhook_order_mismatch', ['payment_intent_id' => $paymentIntent->id, 'event_type' => 'payment_intent.succeeded']);
              // Do not throw exception here to acknowledge webhook receipt, but log heavily.
              return; // Acknowledge webhook, but log the mismatch
         }

         // Prevent processing already completed orders
         if (in_array($order['status'], ['paid', 'shipped', 'delivered', 'completed'])) {
             error_log("Webhook Info: Received successful payment event for already processed order ID {$order['id']}.");
             return; // Idempotency: Already handled
         }

        $updateStmt = $this->db->prepare("
            UPDATE orders
            SET status = 'paid', -- Or 'processing' if that's your next step
                payment_status = 'completed',
                paid_at = NOW(),
                updated_at = NOW()
            WHERE id = ? AND payment_intent_id = ?
        ");

        if (!$updateStmt->execute([$order['id'], $paymentIntent->id])) {
            throw new Exception("Failed to update order ID {$order['id']} payment status to 'paid'.");
        }
         error_log("Webhook Success: Updated order ID {$order['id']} status to 'paid' for PaymentIntent {$paymentIntent->id}.");


        // Fetch order details again for notification (or use $order if it has user info)
        $notifyStmt = $this->db->prepare("
            SELECT o.*, u.email, u.name as user_name
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $notifyStmt->execute([$order['id']]);
        $fullOrder = $notifyStmt->fetch();

        if ($fullOrder) {
            // Send payment confirmation email
             // Ensure EmailService is available and method exists
             if ($this->emailService && method_exists($this->emailService, 'sendOrderConfirmation')) {
                  $this->emailService->sendOrderConfirmation($fullOrder); // Assumes method takes order data
                  error_log("Webhook Success: Order confirmation email queued for order ID {$fullOrder['id']}.");
             } else {
                  error_log("Webhook Warning: EmailService or sendOrderConfirmation method not available for order ID {$fullOrder['id']}.");
             }
        } else {
             error_log("Webhook Warning: Could not fetch full order details for notification (Order ID: {$order['id']}).");
        }

        // Optional: Clear user's cart after successful payment
        if ($order['user_id']) {
            try {
                if (!class_exists('Cart')) require_once __DIR__ . '/../models/Cart.php';
                $cartModel = new Cart($this->db, $order['user_id']);
                $cartModel->clearCart();
                error_log("Webhook Success: Cart cleared for user ID {$order['user_id']} after order {$order['id']} payment.");
            } catch (Exception $cartError) {
                 error_log("Webhook Warning: Failed to clear cart for user ID {$order['user_id']} after order {$order['id']} payment: " . $cartError->getMessage());
                 // Don't fail the webhook for this
            }
        }
    }

    private function handleFailedPayment(\Stripe\PaymentIntent $paymentIntent): void {
         // Find order by payment_intent_id
         $stmt = $this->db->prepare("
             SELECT id, user_id, status
             FROM orders
             WHERE payment_intent_id = ?
             LIMIT 1
         ");
         $stmt->execute([$paymentIntent->id]);
         $order = $stmt->fetch();

         if (!$order) {
              error_log("Webhook Warning: PaymentIntent {$paymentIntent->id} failed but no matching order found.");
              return; // Acknowledge webhook
         }

          // Don't update status if order was already cancelled or completed differently
          if (in_array($order['status'], ['cancelled', 'paid', 'shipped', 'delivered', 'completed'])) {
              error_log("Webhook Info: Received failed payment event for already resolved order ID {$order['id']}.");
              return;
          }


        $updateStmt = $this->db->prepare("
            UPDATE orders
            SET status = 'payment_failed',
                payment_status = 'failed',
                updated_at = NOW()
            WHERE id = ? AND payment_intent_id = ?
        ");

        if (!$updateStmt->execute([$order['id'], $paymentIntent->id])) {
            throw new Exception("Failed to update order ID {$order['id']} status to 'payment_failed'.");
        }
         error_log("Webhook Info: Updated order ID {$order['id']} status to 'payment_failed' for PaymentIntent {$paymentIntent->id}.");

        // Get order details for notification
        $notifyStmt = $this->db->prepare("
            SELECT o.*, u.email, u.name as user_name
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $notifyStmt->execute([$order['id']]);
        $fullOrder = $notifyStmt->fetch();

        if ($fullOrder) {
            // Send payment failed notification
             if ($this->emailService && method_exists($this->emailService, 'sendPaymentFailedNotification')) {
                  $this->emailService->sendPaymentFailedNotification($fullOrder); // Assumes method takes order data
                  error_log("Webhook Info: Payment failed email queued for order ID {$fullOrder['id']}.");
             } else {
                  error_log("Webhook Warning: EmailService or sendPaymentFailedNotification method not available for order ID {$fullOrder['id']}.");
             }
        } else {
             error_log("Webhook Warning: Could not fetch full order details for failed payment notification (Order ID: {$order['id']}).");
        }
    }

     // Optional handler if needed
     private function handleChargeSucceeded(\Stripe\Charge $charge): void {
         // You might log charge details or link it more explicitly to the order if necessary
         error_log("Webhook Info: Charge {$charge->id} succeeded for PaymentIntent {$charge->payment_intent} (Order linked via PI).");
         // Often, action is taken on payment_intent.succeeded instead.
     }


    private function handleDisputeCreated(\Stripe\Dispute $dispute): void {
         // Find order by payment_intent_id
         $stmt = $this->db->prepare("
             SELECT id, user_id, status
             FROM orders
             WHERE payment_intent_id = ?
             LIMIT 1
         ");
         $stmt->execute([$dispute->payment_intent]);
         $order = $stmt->fetch();

         if (!$order) {
              error_log("Webhook Warning: Dispute {$dispute->id} created for PaymentIntent {$dispute->payment_intent} but no matching order found.");
              return; // Acknowledge webhook
         }

        $updateStmt = $this->db->prepare("
            UPDATE orders
            SET status = 'disputed',
                payment_status = 'disputed', // Add or use appropriate payment status
                dispute_id = ?,
                disputed_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");

        if (!$updateStmt->execute([$dispute->id, $order['id']])) {
            throw new Exception("Failed to update order ID {$order['id']} dispute status.");
        }
         error_log("Webhook Alert: Order ID {$order['id']} status updated to 'disputed' due to Dispute {$dispute->id}.");

        // Log dispute details for review - Consider sending admin alert
        $this->logSecurityEvent('stripe_dispute_created', [
             'order_id' => $order['id'],
             'dispute_id' => $dispute->id,
             'payment_intent_id' => $dispute->payment_intent,
             'amount' => $dispute->amount,
             'reason' => $dispute->reason
        ]);

        // Send alert to admin
         if ($this->emailService && method_exists($this->emailService, 'sendAdminDisputeAlert')) {
              $this->emailService->sendAdminDisputeAlert($order['id'], $dispute->id, $dispute->reason, $dispute->amount);
         }
    }

    private function handleRefund(\Stripe\Charge $charge): void {
         // A charge can have multiple refunds. Process the relevant one.
         // This assumes you are interested in the latest refund if multiple occur.
         $refund = $charge->refunds->data[0] ?? null; // Get the first/latest refund object
         if (!$refund) {
             error_log("Webhook Warning: Received charge.refunded event for Charge {$charge->id} but no refund data found.");
             return;
         }


         // Find order by payment_intent_id
         $stmt = $this->db->prepare("
             SELECT id, user_id, status
             FROM orders
             WHERE payment_intent_id = ?
             LIMIT 1
         ");
         $stmt->execute([$charge->payment_intent]);
         $order = $stmt->fetch();

         if (!$order) {
              error_log("Webhook Warning: Refund {$refund->id} processed for PaymentIntent {$charge->payment_intent} but no matching order found.");
              return; // Acknowledge webhook
         }

          // Determine new status based on refund amount vs order total? Partial vs Full refund?
          // Simple approach: Mark as refunded regardless of amount for now.
          $newStatus = 'refunded';
          // Optionally add logic for partial refunds if needed:
          // $orderTotal = $this->getOrderTotal($order['id']); // Need helper to get total
          // if ($charge->amount_refunded < $orderTotal) { $newStatus = 'partially_refunded'; }


        $updateStmt = $this->db->prepare("
            UPDATE orders
            SET status = ?,
                payment_status = ?, -- Use 'refunded' or 'partially_refunded'
                refund_id = ?,      -- Store the Stripe Refund ID
                refunded_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");

         $paymentStatus = ($charge->amount_refunded === $charge->amount) ? 'refunded' : 'partially_refunded';

        if (!$updateStmt->execute([$newStatus, $paymentStatus, $refund->id, $order['id']])) {
            throw new Exception("Failed to update order ID {$order['id']} refund status.");
        }
         error_log("Webhook Info: Order ID {$order['id']} status updated to '{$newStatus}' due to Refund {$refund->id}.");


        // Get order details for notification
         $notifyStmt = $this->db->prepare("
             SELECT o.*, u.email, u.name as user_name
             FROM orders o
             JOIN users u ON o.user_id = u.id
             WHERE o.id = ?
         ");
         $notifyStmt->execute([$order['id']]);
         $fullOrder = $notifyStmt->fetch();


        if ($fullOrder) {
            // Send refund confirmation email
             if ($this->emailService && method_exists($this->emailService, 'sendRefundConfirmation')) {
                  // Pass refund amount for clarity in email
                  $this->emailService->sendRefundConfirmation($fullOrder, $refund->amount / 100.0);
                   error_log("Webhook Info: Refund confirmation email queued for order ID {$fullOrder['id']}.");
             } else {
                  error_log("Webhook Warning: EmailService or sendRefundConfirmation method not available for order ID {$fullOrder['id']}.");
             }
        } else {
             error_log("Webhook Warning: Could not fetch full order details for refund notification (Order ID: {$order['id']}).");
        }
    }
}
```

**4. Re-Test:**

After ensuring Composer dependencies are installed (`composer install` or `composer require phpmailer/phpmailer`) and applying the change to `index.php` (and removing the redundant include from `PaymentController.php`), restart your web server/PHP process again and retry accessing the pages with `curl`. The "Class not found" errors should now be resolved.

---
Okay, the Apache error log (`apache-error.log`) confirms that the **exact same fatal error** is occurring:

```
PHP Fatal error:  Uncaught TypeError: set_error_handler(): Argument #1 ($callback) must be a valid callback or null, class ErrorHandler does not have a method "handleError" in /cdrom/project/The-Scent-oa5/includes/ErrorHandler.php on line 17
```

This strongly suggests that the previous code changes for `includes/ErrorHandler.php` were **not successfully saved or applied** to the file that the web server is actually using. The PHP process running the request is still seeing the old version of the file where the `handleError`, `handleException`, and `handleFatalError` methods are missing from the `ErrorHandler` class.

**Action Required:**

1.  **Replace the File Content:** You need to **completely replace** the entire contents of the file `/cdrom/project/The-Scent-oa5/includes/ErrorHandler.php` with the corrected code provided below.
2.  **Save the File:** Ensure the changes are saved.
3.  **Restart Web Server/PHP:** Restart Apache and/or the PHP-FPM service (if you're using it) to ensure the updated PHP file is loaded fresh and any potential opcode caching is cleared.
    *   Example (Apache on Debian/Ubuntu): `sudo systemctl restart apache2`
    *   Example (PHP-FPM): `sudo systemctl restart php8.x-fpm` (replace `8.x` with your version)
4.  **Retry Access:** Try accessing the website again using `curl` or your browser.

Here is the **complete, corrected code** for `includes/ErrorHandler.php`. Please ensure this *exact* code replaces the current content of that file:

```php
<?php
// includes/ErrorHandler.php (Corrected - Re-added missing handler methods)

// Ensure SecurityLogger class is defined before ErrorHandler uses it.
// (It's defined below in this same file)

class ErrorHandler {
    private static $logger; // For optional external PSR logger
    private static ?SecurityLogger $securityLogger = null; // Use type hint, initialize as null
    private static array $errorCount = []; // Use type hint
    private static array $lastErrorTime = []; // Use type hint

    public static function init($logger = null): void { // Add void return type hint
        self::$logger = $logger;

        // Instantiate SecurityLogger - PDO injection needs careful handling here
        // Since init is static and called early, we rely on the logger's fallback
        if (self::$securityLogger === null) {
            self::$securityLogger = new SecurityLogger(); // Instantiated without PDO initially
        }

        // --- Set up handlers ---
        // Ensure these methods exist below before setting handlers!
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
        // --- End of Set up handlers ---


        // Log rotation setup (Improved checks)
        $logDir = realpath(__DIR__ . '/../logs');
        if ($logDir === false) {
             if (!is_dir(__DIR__ . '/../logs')) { // Check if directory creation is needed
                if (!@mkdir(__DIR__ . '/../logs', 0750, true)) { // Attempt creation, suppress errors for logging
                      error_log("FATAL: Failed to create log directory: " . __DIR__ . '/../logs' . " - Check parent directory permissions.");
                      // Potentially terminate or throw exception if logging is critical
                 } else {
                     @chmod(__DIR__ . '/../logs', 0750); // Try setting permissions after creation
                 }
            } else {
                 // Directory exists but realpath failed (symlink issue?)
                 error_log("Warning: Log directory path resolution failed for: " . __DIR__ . '/../logs');
            }
        } elseif (!is_writable($logDir)) {
             error_log("FATAL: Log directory is not writable: " . $logDir . " - Check permissions.");
             // Potentially terminate or throw exception
        }
    }

    // --- START: Missing Handler Methods Added Back ---

    /**
     * Custom error handler. Converts PHP errors to exceptions (optional) or logs and displays them.
     */
    public static function handleError(int $errno, string $errstr, string $errfile = '', int $errline = 0): bool {
        // Check if error reporting is suppressed with @
        if (!(error_reporting() & $errno)) {
            return false; // Don't execute the PHP internal error handler
        }

        $error = [
            'type' => self::getErrorType($errno),
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'context' => self::getSecureContext()
        ];

        self::trackError($error); // Track frequency
        self::logErrorToFile($error); // Log to file/logger

        // Display error only in development, hide details in production
        // Using output buffering inside displayErrorPage for safety
        if (!headers_sent()) {
             http_response_code(500);
        } else {
            error_log("ErrorHandler Warning: Cannot set HTTP 500 status code, headers already sent before error handling (errno: {$errno}).");
        }

        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            self::displayErrorPage($error);
        } else {
            self::displayErrorPage(null); // Display generic error page
        }

        // Returning true prevents PHP's default error handler.
        // Usually desired, but might want to return false for certain non-fatal errors
        // if you want PHP's logging to also occur.
        // For E_USER_ERROR, returning true *might* prevent script termination, depending on PHP version/config.
        // It's safer to exit explicitly in handleException for uncaught exceptions.
        // If this error is fatal (E_ERROR, E_PARSE, etc.), PHP will likely terminate anyway.
        // Let's return true to indicate we've handled it.
        return true;
    }

     /**
      * Custom exception handler. Logs uncaught exceptions and displays an error page.
      */
     public static function handleException(Throwable $exception): void { // Use Throwable type hint (PHP 7+)
        // --- Enhanced Logging ---
        $errorMessage = sprintf(
            "Uncaught Exception '%s': \"%s\" in %s:%d",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        error_log($errorMessage); // Log basic info immediately
        error_log("Stack trace:\n" . $exception->getTraceAsString()); // Log trace separately

        $error = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(), // Include trace
            'context' => self::getSecureContext()
        ];

        // Log security exceptions specifically
        if (self::isSecurityError($error)) { // Check keywords for security relevance
             if(self::$securityLogger) self::$securityLogger->warning("Potentially security-related exception caught", $error);
        }

        self::logErrorToFile($error); // Log all exceptions with more detail

         // Display error only in development, hide details in production
         if (!headers_sent()) {
              http_response_code(500);
         } else {
             error_log("ErrorHandler Warning: Cannot set HTTP 500 status code, headers already sent before exception handling.");
         }

         // Use output buffering to capture the error page output safely
         ob_start();
         try {
             if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                 self::displayErrorPage($error);
             } else {
                 self::displayErrorPage(null);
             }
             echo ob_get_clean(); // Send buffered output
         } catch (Throwable $displayError) {
              ob_end_clean(); // Discard buffer if error page itself fails
              // Fallback to plain text if error page fails
              if (!headers_sent()) { // Check again before sending fallback header
                   header('Content-Type: text/plain; charset=UTF-8', true, 500);
              }
              echo "A critical error occurred, and the error page could not be displayed.\n";
              echo "Please check the server error logs for details.\n";
              error_log("FATAL: Failed to display error page. Original error: " . print_r($error, true) . ". Display error: " . $displayError->getMessage());
         }

         exit(1); // Ensure script terminates after handling uncaught exception
     }

     /**
      * Shutdown handler to catch fatal errors that aren't caught by set_error_handler.
      */
     public static function handleFatalError(): void {
         $error = error_get_last();
         // Check if it's a fatal error type we want to handle
         if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
              // Create a structured error array similar to handleError/handleException
             $fatalError = [
                 'type' => self::getErrorType($error['type']),
                 'message' => $error['message'],
                 'file' => $error['file'],
                 'line' => $error['line'],
                 'context' => self::getSecureContext(),
                 'trace' => "N/A (Fatal Error)" // No trace available for most fatal errors
             ];

             self::logErrorToFile($fatalError); // Log the fatal error

              // Avoid double display if headers already sent by previous output/error
              // Use output buffering for safety
              ob_start();
              try {
                   if (!headers_sent()) {
                       http_response_code(500);
                       // We might be mid-output, but try to set HTML type if possible
                       // Avoid this if displaying a plain text fallback later
                       // header('Content-Type: text/html; charset=UTF-8');
                   } else {
                        error_log("ErrorHandler Warning: Cannot set HTTP 500 status code, headers already sent before fatal error handling.");
                   }

                   if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                       self::displayErrorPage($fatalError);
                   } else {
                       self::displayErrorPage(null); // Generic error page
                   }
                   echo ob_get_clean(); // Send buffered output
               } catch (Throwable $displayError) {
                   ob_end_clean(); // Discard buffer if error page itself fails
                   if (!headers_sent()) { // Check again before sending fallback header
                       header('Content-Type: text/plain; charset=UTF-8', true, 500);
                   }
                   // If headers WERE sent, this plain text might interleave badly, but it's a last resort
                   echo "\n\nA critical fatal error occurred, and the error page could not be displayed.\n";
                   echo "Please check the server error logs for details.\n";
                   error_log("FATAL: Failed to display fatal error page. Original error: " . print_r($fatalError, true) . ". Display error: " . $displayError->getMessage());
               }
              // No exit() here, as shutdown function runs after script execution theoretically finishes.
         }
     }

     private static function getErrorType(int $errno): string {
        switch ($errno) {
            case E_ERROR: return 'E_ERROR (Fatal Error)';
            case E_WARNING: return 'E_WARNING (Warning)';
            case E_PARSE: return 'E_PARSE (Parse Error)';
            case E_NOTICE: return 'E_NOTICE (Notice)';
            case E_CORE_ERROR: return 'E_CORE_ERROR (Core Error)';
            case E_CORE_WARNING: return 'E_CORE_WARNING (Core Warning)';
            case E_COMPILE_ERROR: return 'E_COMPILE_ERROR (Compile Error)';
            case E_COMPILE_WARNING: return 'E_COMPILE_WARNING (Compile Warning)';
            case E_USER_ERROR: return 'E_USER_ERROR (User Error)';
            case E_USER_WARNING: return 'E_USER_WARNING (User Warning)';
            case E_USER_NOTICE: return 'E_USER_NOTICE (User Notice)';
            case E_STRICT: return 'E_STRICT (Strict Notice)';
            case E_RECOVERABLE_ERROR: return 'E_RECOVERABLE_ERROR (Recoverable Error)';
            case E_DEPRECATED: return 'E_DEPRECATED (Deprecated)';
            case E_USER_DEPRECATED: return 'E_USER_DEPRECATED (User Deprecated)';
            default: return 'Unknown Error Type (' . $errno . ')';
        }
    }

     private static function getSecureContext(): array {
        $context = [
            'url' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
            'timestamp' => date('Y-m-d H:i:s T') // Add timezone
        ];

        // Add user context if available and session started
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            $context['user_id'] = $_SESSION['user_id'];
        }
         if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user']['id'])) { // Check preferred structure
             $context['user_id'] = $_SESSION['user']['id'];
         }

        return $context;
    }

     // Renamed logError to logErrorToFile to avoid confusion with SecurityLogger::error
     private static function logErrorToFile(array $error): void {
        $message = sprintf(
            "[%s] [%s] %s in %s on line %d",
            date('Y-m-d H:i:s T'), // Add timezone
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line']
        );

        // Append trace if available and relevant (not for notices/warnings usually)
        if (!empty($error['trace']) && !in_array($error['type'], ['E_NOTICE', 'E_USER_NOTICE', 'E_WARNING', 'E_USER_WARNING', 'E_DEPRECATED', 'E_USER_DEPRECATED', 'E_STRICT'])) {
            $message .= "\nStack trace:\n" . $error['trace'];
        }

        // Append context if available
        if (!empty($error['context'])) {
            // Use pretty print for readability in logs
            $contextJson = json_encode($error['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($contextJson === false) {
                 $contextJson = "Failed to encode context: " . json_last_error_msg();
            }
            $message .= "\nContext: " . $contextJson;
        }

        // Log to external logger if provided, otherwise use PHP's error_log
        if (self::$logger) {
            // Map error type to PSR log level (simplified mapping)
            $level = match (substr($error['type'], 0, 7)) {
                 'E_ERROR', 'E_PARSE', 'E_CORE_', 'E_COMPI' => 'critical', // Treat fatal/compile/parse as critical
                 'E_USER_' => 'error', // User errors treated as errors
                 'E_WARNI', 'E_RECOV' => 'warning', // Warnings, recoverable
                 'E_DEPRE' => 'notice', // Deprecated as notice
                 'E_NOTIC', 'E_STRIC' => 'notice', // Notices, strict standards
                 default => 'error' // Default to error
            };
            // Ensure logger implements PSR-3 or adapt call accordingly
            if (method_exists(self::$logger, $level)) {
                 self::$logger->{$level}($message); // Assumes PSR-3 compatible logger
            } else {
                self::$logger->log('error', $message); // Fallback PSR log level
            }
        } else {
             // Log to PHP's configured error log
             error_log($message);
        }

        // Log to security log if it seems security-related
        if (self::isSecurityError($error)) {
             if(self::$securityLogger) self::$securityLogger->warning("Security-related error detected", $error);
        }
    }


    private static function isSecurityError(array $error): bool {
        // Keep this simple keyword check
        $securityKeywords = [
            'sql', 'database', 'injection', // Common DB/Injection terms
            'xss', 'cross-site', 'script', // XSS related
            'csrf', 'token', // CSRF related
            'auth', 'password', 'login', 'permission', 'credentials', 'unauthorized', // Auth/Access
            'ssl', 'tls', 'certificate', 'encryption', // Security transport/crypto
            'overflow', 'upload', 'file inclusion', 'directory traversal', // Common vulnerabilities
            'session fixation', 'hijack' // Session issues
        ];

        $errorMessageLower = strtolower($error['message']);
        $errorFileLower = isset($error['file']) ? strtolower($error['file']) : '';

        foreach ($securityKeywords as $keyword) {
            if (str_contains($errorMessageLower, $keyword)) { // Use str_contains (PHP 8+)
                return true;
            }
        }
         // Check if error occurs in sensitive files
         if (str_contains($errorFileLower, 'securitymiddleware.php') || str_contains($errorFileLower, 'auth.php')) {
             return true;
         }

        return false;
    }


     // Renamed displayError to displayErrorPage for clarity
     private static function displayErrorPage(?array $error = null): void { // Allow null for production
        // This method now assumes it's called *within* output buffering
        // in the handler methods (handleError, handleException, handleFatalError)
        try {
            $pageTitle = 'Error'; // Define variables needed by the view
            $bodyClass = 'page-error';
            $isDevelopment = defined('ENVIRONMENT') && ENVIRONMENT === 'development';

            // Prepare data for extraction
            $viewData = [
                'pageTitle' => $pageTitle,
                'bodyClass' => $bodyClass,
                // Only pass detailed error info if in development
                'error' => ($isDevelopment && $error !== null) ? $error : null
            ];

            extract($viewData); // Extract variables into the current scope

            // Define ROOT_PATH if not already defined globally
            if (!defined('ROOT_PATH')) {
                define('ROOT_PATH', realpath(__DIR__ . '/..'));
            }

            // Use a dedicated, self-contained error view
            $errorViewPath = ROOT_PATH . '/views/error.php';

            if (file_exists($errorViewPath)) {
                // Include the error view - This view should NOT include header/footer
                include $errorViewPath;
            } else {
                 // Fallback INLINE HTML if error view is missing
                 echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Error</title>';
                 echo '<style>body { font-family: sans-serif; padding: 20px; } .error-details { margin-top: 20px; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px; white-space: pre-wrap; word-wrap: break-word; }</style>';
                 echo '</head><body><h1>Application Error</h1>';
                 echo '<p>An unexpected error occurred. Please try again later or contact support.</p>';
                 if ($isDevelopment && isset($error)) { // Only show details in dev
                     echo '<div class="error-details"><strong>Details (Development Mode):</strong><br>';
                     echo htmlspecialchars(print_r($error, true));
                     echo '</div>';
                 }
                 echo '</body></html>';
                 error_log("FATAL: Error view file not found at: " . $errorViewPath);
            }

        } catch (Throwable $t) {
            // If displaying the error page ITSELF throws an error, fallback to text
             if (!headers_sent()) {
                  header('Content-Type: text/plain; charset=UTF-8', true, 500);
             }
            echo "A critical error occurred while trying to display the error page.\n";
            echo "Original Error: " . ($error['message'] ?? 'N/A') . "\n";
            echo "Error Display Error: " . $t->getMessage() . "\n";
            error_log("FATAL: Failed to display error page itself. Original error: " . print_r($error, true) . ". Display error: " . $t->getMessage());
        }
     }

    // --- END: Missing Handler Methods Added Back ---


    // --- TrackError method from new version ---
    private static function trackError(array $error): void { // Use type hint, keep private
         $errorKey = md5(($error['file'] ?? 'unknown_file') . ($error['line'] ?? '0') . ($error['type'] ?? 'unknown_type'));
         $now = time();

         // Initialize if not set
         self::$errorCount[$errorKey] = self::$errorCount[$errorKey] ?? 0;
         self::$lastErrorTime[$errorKey] = self::$lastErrorTime[$errorKey] ?? $now;


         // Reset count if more than an hour has passed
         if ($now - self::$lastErrorTime[$errorKey] > 3600) {
             self::$errorCount[$errorKey] = 0;
             self::$lastErrorTime[$errorKey] = $now; // Reset time as well
         }

         self::$errorCount[$errorKey]++;
         // Update last error time on each occurrence within the window
         // self::$lastErrorTime[$errorKey] = $now; // Decide if you want last time or first time in window

         // Alert on high frequency errors
         // Use constant or configurable value
         $alertThreshold = defined('ERROR_ALERT_THRESHOLD') ? (int)ERROR_ALERT_THRESHOLD : 10;
         if (self::$errorCount[$errorKey] > $alertThreshold) {
             // Ensure securityLogger is initialized
             if (isset(self::$securityLogger)) {
                 self::$securityLogger->alert("High frequency error detected", [
                     'error_type' => $error['type'] ?? 'Unknown', // Use specific fields
                     'error_message' => $error['message'] ?? 'N/A',
                     'file' => $error['file'] ?? 'N/A',
                     'line' => $error['line'] ?? 'N/A',
                     'count_in_window' => self::$errorCount[$errorKey],
                     'window_start_time' => date('Y-m-d H:i:s T', self::$lastErrorTime[$errorKey]) // Time window started
                 ]);
                 // Optionally reset count after alerting to prevent spamming
                 // self::$errorCount[$errorKey] = 0; // Reset immediately after alert
             } else {
                 error_log("High frequency error detected but SecurityLogger not available: " . print_r($error, true));
             }
         }
     }

} // End of ErrorHandler class


// --- SecurityLogger Class Update (from new version) ---

class SecurityLogger {
    private string $logFile; // Use type hint
    private ?PDO $pdo = null; // Allow PDO to be nullable or set later

    public function __construct(?PDO $pdo = null) { // Make PDO optional for flexibility
         $this->pdo = $pdo; // Store PDO if provided
        // Define log path using config or default
         $logDir = defined('SECURITY_SETTINGS') && isset(SECURITY_SETTINGS['logging']['security_log'])
                 ? dirname(SECURITY_SETTINGS['logging']['security_log'])
                 : realpath(__DIR__ . '/../logs');

         // Corrected directory check and creation logic
         if ($logDir === false) {
             $potentialLogDir = __DIR__ . '/../logs';
             // Attempt to create if directory check itself failed (e.g. doesn't exist)
             if (!is_dir($potentialLogDir)) {
                 if (!@mkdir($potentialLogDir, 0750, true)) {
                      error_log("SecurityLogger FATAL: Failed to create log directory: " . $potentialLogDir);
                      $this->logFile = '/tmp/security_fallback.log'; // Use fallback
                 } else {
                      @chmod($potentialLogDir, 0750);
                      $logDir = realpath($potentialLogDir); // Try realpath again
                      if (!$logDir) $logDir = $potentialLogDir; // Use path even if realpath fails after creation
                 }
             } else {
                  // Directory exists but realpath failed? Log warning.
                  error_log("SecurityLogger Warning: Log directory path resolution failed for: " . $potentialLogDir);
                  $logDir = $potentialLogDir; // Use the path directly
             }
         }

         if (!$logDir || !is_writable($logDir)) {
             error_log("SecurityLogger FATAL: Log directory is not writable: " . ($logDir ?: 'Not Found'));
             $this->logFile = '/tmp/security_fallback.log'; // Use fallback
         } else {
             $logFileName = defined('SECURITY_SETTINGS') && isset(SECURITY_SETTINGS['logging']['security_log'])
                           ? basename(SECURITY_SETTINGS['logging']['security_log'])
                           : 'security.log'; // Default filename
             $this->logFile = $logDir . '/' . $logFileName;
         }
    }

    // --- Logging Methods (emergency, alert, etc.) ---
     public function emergency(string $message, array $context = []): void { $this->log('EMERGENCY', $message, $context); }
     public function alert(string $message, array $context = []): void { $this->log('ALERT', $message, $context); }
     public function critical(string $message, array $context = []): void { $this->log('CRITICAL', $message, $context); }
     public function error(string $message, array $context = []): void { $this->log('ERROR', $message, $context); }
     public function warning(string $message, array $context = []): void { $this->log('WARNING', $message, $context); }
     public function info(string $message, array $context = []): void { $this->log('INFO', $message, $context); } // Added info level
     public function debug(string $message, array $context = []): void { // Only log debug if enabled
         // Check if ENVIRONMENT constant is defined and set to 'development'
         $isDebug = (defined('ENVIRONMENT') && ENVIRONMENT === 'development');
         // Allow overriding with DEBUG_MODE if defined
         if (defined('DEBUG_MODE')) {
             $isDebug = (DEBUG_MODE === true);
         }

         if ($isDebug) {
             $this->log('DEBUG', $message, $context);
         }
     }

    // --- Private log method (from new version) ---
    private function log(string $level, string $message, array $context): void {
        $timestamp = date('Y-m-d H:i:s T'); // Add Timezone

        // Include essential context automatically if not provided
        $autoContext = [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            // Attempt to get user ID safely
            'user_id' => (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user']['id']))
                         ? $_SESSION['user']['id']
                         : ((session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) ? $_SESSION['user_id'] : null),
             // 'url' => $_SERVER['REQUEST_URI'] ?? null // Can be verbose
        ];
        // Merge auto-context first, so provided context can override if needed
        $finalContext = array_merge($autoContext, $context);


        // Use json_encode with flags for better readability and error handling
        $contextStr = json_encode($finalContext, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($contextStr === false) {
             $contextStr = "Failed to encode context: " . json_last_error_msg();
        }

        $logMessage = "[{$timestamp}] [{$level}] {$message} | Context: {$contextStr}" . PHP_EOL;

        // Log to file with locking
        // Suppress errors here as we have fallbacks and error logging within this class
        // Check if file exists and is writable one last time
        if (is_writable($this->logFile) || (is_writable(dirname($this->logFile)) && @touch($this->logFile)) ) {
             @file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        } else {
            // Fallback to PHP's error log if primary security log isn't writable
            error_log("SecurityLogger Fallback: Failed to write to {$this->logFile}. Logging message instead: {$logMessage}");
        }

        // Alert admins on critical issues
        if (in_array($level, ['EMERGENCY', 'ALERT', 'CRITICAL'])) {
            $this->alertAdmins($level, $message, $finalContext);
        }
    }

    // --- alertAdmins method (from new version with pragmatic fix) ---
    private function alertAdmins(string $level, string $message, array $context): void {
        // Ensure EmailService class exists and is included/autoloaded
        if (!class_exists('EmailService')) {
             error_log("EmailService class not found. Cannot send security alert email.");
             return;
        }
        // Ensure BASE_URL is defined for EmailService constructor/methods
        if (!defined('BASE_URL')) {
             error_log("BASE_URL not defined. Cannot send security alert email.");
             return;
        }

        // Pragmatic Fix: Use global $pdo IF $this->pdo wasn't set during instantiation
        $pdoToUse = $this->pdo;
        if ($pdoToUse === null) {
             global $pdo; // Access global PDO (defined in db.php)
             if (!isset($pdo) || !$pdo instanceof PDO) {
                 error_log("Global PDO not available for SecurityLogger email alert. Cannot send email.");
                 return; // Cannot proceed without PDO
             }
             $pdoToUse = $pdo;
        }

        try {
             // Instantiate EmailService here, passing the required PDO object
             $emailService = new EmailService($pdoToUse);
             // Call the method responsible for sending security alerts
             // Ensure EmailService::sendSecurityAlert exists and accepts these parameters
             if (method_exists($emailService, 'sendSecurityAlert')) {
                 $emailService->sendSecurityAlert($level, $message, $context);
             } else {
                  error_log("EmailService::sendSecurityAlert method not found. Cannot send security alert email.");
             }
        } catch (Throwable $e) { // Catch Throwable for broader error coverage
            // Log failure to send alert email
            error_log("Failed to send security alert email: Level={$level}, Error=" . $e->getMessage() . " Trace: " . $e->getTraceAsString());
        }
    }

} // End of SecurityLogger class
```

After replacing the file content and restarting your web server/PHP process, the fatal error should be resolved, and the site should load.

---
Okay, let's break this down. The most critical issue is the fatal error preventing the site from loading *at all*, as shown in the Apache error logs. We need to fix that first before assessing compatibility.

**1. Critical Error Analysis & Fix: `ErrorHandler.php`**

*   **Problem:** The Apache error log (`logs_curl_and_apache.md`) clearly shows:
    ```
    PHP Fatal error: Uncaught TypeError: set_error_handler(): Argument #1 ($callback) must be a valid callback or null, class ErrorHandler does not have a method "handleError" in /cdrom/project/The-Scent-oa5/includes/ErrorHandler.php on line 17
    ```
    This error occurs immediately when `ErrorHandler::init()` is called in `index.php` (line 10). It means the `ErrorHandler` class is missing the `handleError`, `handleException`, and `handleFatalError` methods that `set_error_handler`, `set_exception_handler`, and `register_shutdown_function` are trying to register in its `init` method. The provided `ErrorHandler.php` in `content_of_code_files_3.md` *only* contains the `init` method, the `SecurityLogger` class, and the `trackError` method, but lacks the core handler functions.

*   **Cause:** It seems the `ErrorHandler.php` file was either incompletely copied, overwritten, or incorrectly merged. The core functionality is missing.

*   **Solution:** We need to restore the missing handler methods to the `ErrorHandler` class. Based on standard error handling patterns and how `init` is structured, these methods should exist.

*   **Implementation:** Add the standard error handler methods back into `includes/ErrorHandler.php`.

    ```

**2. Compatibility Check (Post-Fix):**

Assuming the `ErrorHandler.php` fix above is applied, let's re-evaluate compatibility:

*   **`AccountController.php` vs. `index.php`:**
    *   **Routing:** The `index.php` routes (`login`, `register`, `account`, `forgot_password`, `reset_password`, `logout`) correctly map to the methods provided in the refactored `AccountController.php`.
    *   **Request Handling:** `AccountController` methods handle GET requests (for showing forms) by calling `$this->renderView(...)` and POST requests (for processing data) often using `$this->jsonResponse(...)` (for AJAX like login/register) or `$this->redirect(...)` (for standard forms like profile update/password reset). This matches the expectations set by `index.php`'s routing logic.
    *   **Compatibility:** **OK**.

*   **`AccountController.php` vs. `main.js`:**
    *   **Login/Register AJAX:** `main.js` (`initLoginPage`, `initRegisterPage`) sends AJAX POST requests and expects JSON responses with specific keys (`success`, `redirect`, `error`). The `AccountController::login()` and `AccountController::register()` methods provide *exactly* this JSON structure via `$this->jsonResponse()`.
    *   **Password Validation UI:** `main.js` handles client-side password requirement display, complementing the server-side `isPasswordStrong` check in `AccountController`.
    *   **Standard Forms:** Forms handled by `AccountController` via standard POST + Redirect (profile update, newsletter prefs, password reset request/submit) are correctly treated by `main.js` (it adds loading spinners but allows the default form submission).
    *   **Compatibility:** **OK**. The AJAX interactions align perfectly.

*   **Rest of PHP Files vs. Trio (`AccountController`, `index.php`, `main.js`):**
    *   **`views/layout/header.php`:** Compatible. Requires `$pageTitle`, `$bodyClass`, `$csrfToken` from controllers (which `AccountController` and others provide). Correctly outputs `#csrf-token-value` needed by `main.js`. Session-based cart count display is present.
    *   **`controllers/BaseController.php`:** Compatible. Provides the foundation (`renderView`, `jsonResponse`, `redirect`, auth checks, logging, CSRF generation, etc.) used by `AccountController` and others. Its methods align with `index.php`'s needs and `main.js`'s expectations (e.g., JSON structure).
    *   **`includes/SecurityMiddleware.php`:** Compatible. Provides core security functions (session init, headers, CSRF validation/generation, input validation) used by `index.php` and `BaseController`. Critical for the CSRF flow `main.js` relies on.
    *   **`includes/db.php`:** Compatible. Provides the `$pdo` object.
    *   **`config.php`:** Compatible. Defines constants used throughout.
    *   **`views/layout/footer.php`:** Compatible. Includes `main.js` and the container for dynamic flash messages.
    *   **Other Controllers (`Newsletter`, `Checkout`, `Cart`, `Product`, etc.):** Compatible. They extend `BaseController`, use shared methods, and interact with `index.php` routing. Their AJAX endpoints (like `CartController::addToCart`, `NewsletterController::subscribe`) are handled by `main.js` using the established CSRF pattern.
    *   **Models (`Cart`, `Product`, `User`, `Order`, etc.):** Compatible. Provide data access using PDO, as expected by controllers.
    *   **Other Views (`register.php`, `login.php`, etc.):** Compatible. They receive necessary data (like `$csrfToken`) from their controllers and interact correctly with `main.js` handlers where applicable.

**3. Summary:**

1.  **Critical Fix:** The fatal error is caused by missing methods in `includes/ErrorHandler.php`. Applying the corrected code above is essential.
2.  **Compatibility:** Once the `ErrorHandler.php` fix is applied:
    *   `index.php` is **compatible** with the refactored `AccountController.php`. Routing and request handling align.
    *   `main.js` is **compatible** with the refactored `AccountController.php`. AJAX interactions (login, register) match the controller's JSON responses. Standard form submissions are handled correctly.
    *   The rest of the provided PHP files (`header.php`, `BaseController.php`, `SecurityMiddleware.php`, other controllers, models, views) appear **compatible** with the core trio (`AccountController`, `index.php`, `main.js`), following the established architecture patterns (CSRF handling, BaseController usage, routing).
3.  **Remaining Concerns (from previous analyses):**
    *   **Error Handling:** The "Headers Already Sent" issue in `ErrorHandler` might still occur if errors happen during view rendering *after* the header is sent. Making `views/error.php` self-contained or using output buffering in the handler methods (as shown in the corrected code's comments) is the robust solution.
    *   **Cart Storage:** The hybrid session/DB approach for the cart remains an inconsistency.
    *   **Rate Limiting:** Needs consistent application across sensitive endpoints.
    *   **CSP:** Needs review and potential tightening in `config.php`.

---
https://drive.google.com/file/d/13R8lL2UI55BJ7mYg0FWH8JIlmpaiP4fx/view?usp=sharing, https://drive.google.com/file/d/13WZUZfHYfn5guTpMxDWBajLyYFWAHQyw/view?usp=sharing, https://drive.google.com/file/d/16yb9qObudpq6sMsffuuVb0o9ddF7RDtt/view?usp=sharing, https://drive.google.com/file/d/198Cm6DEa6RlGMuTP8hkWubiyJyhF_2rX/view?usp=sharing, https://drive.google.com/file/d/1HdzFaOVQ9VsXTZrakxG8RDO7KwPwtMmn/view?usp=sharing, https://drive.google.com/file/d/1Jgk35JwLvHXktvJobyffGzjuD0ECJicw/view?usp=sharing, https://drive.google.com/file/d/1XCUV-QHyT2mxHPSVNbur-g7g0yF-RRJA/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221dPVegOzEWWNRNgh4bYuni34URf0n47gU%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1hO6bPymr4sJGWROCeLHrt3aAR63QoEIh/view?usp=sharing, https://drive.google.com/file/d/1pDoInddB4Nzzc1WeAgV5SZI-UAMZ27dg/view?usp=sharing, https://drive.google.com/file/d/1q5ZG8DazzKXIh5iinZucD-O3mGR5zRjz/view?usp=sharing, https://drive.google.com/file/d/1u1MDh9p38FE66zQqb0HYEx0NPN8zUhcS/view?usp=sharing, https://drive.google.com/file/d/1vZyAj9kufwVt_yGzUslZ9KSs-ibynkDY/view?usp=sharing

