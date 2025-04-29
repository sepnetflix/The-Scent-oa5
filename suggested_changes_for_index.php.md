1.  **`models/Order.php`:**
    *   `create` method updated to accept and store all required fields from `CheckoutController`, including `subtotal`, `discount_amount`, `coupon_code`, `coupon_id`, `shipping_cost`, `tax_amount`, `total_amount`, `status`, `payment_status`, `payment_intent_id`, `order_notes`.
    *   `_fetchOrderItems` updated to join `products` and fetch `product_name` and `image_url` for display/emails.
    *   Other methods (`getById`, `getByIdAndUserId`, `getByPaymentIntentId`, `updateStatus`, `updatePaymentIntentId`, etc.) verified for compatibility.

2.  **`controllers/PaymentController.php`:**
    *   `createPaymentIntent` updated to return `payment_intent_id`.
    *   `handleWebhook` implemented robustly:
        *   Starts/commits/rolls back transactions.
        *   `handleSuccessfulPayment` correctly finds the order, checks idempotency, updates status via `OrderModel`, crucially attempts to set `$_SESSION['last_order_id']`, sends confirmation email via `EmailService`, clears cart via `CartModel`. Includes enhanced logging.
        *   `handleFailedPayment` implemented to update status and optionally send email.
        *   Other handlers (`dispute`, `refund`) use appropriate `OrderModel` methods.

3.  **`controllers/CheckoutController.php`:**
    *   `processCheckout` fully implemented:
        *   Validates input, cart, stock, and coupon (including user usage limit check) within a transaction.
        *   Correctly calculates final totals (discount, tax, shipping).
        *   Calls `OrderModel::create` with all necessary data.
        *   Calls `InventoryController::updateStock` for each item.
        *   Calls `PaymentController::createPaymentIntent`.
        *   Calls `OrderModel::updatePaymentIntentId`.
        *   Calls `CouponController::recordUsage` if applicable.
        *   Uses transaction handling.
        *   Returns correct JSON (`clientSecret`, `orderId`).
    *   `showOrderConfirmation` updated:
        *   Checks `$_SESSION['last_order_id']`.
        *   Fetches order via `getByIdAndUserId`.
        *   Checks order status against acceptable post-payment statuses.
        *   Clears session variable.
        *   Renders view with order data.
    *   `calculateTax` and `applyCouponAjax` verified for correct endpoints and logic.

4.  **`controllers/CouponController.php`:**
    *   `recordUsage` method reviewed; transaction management correctly removed (handled by `processCheckout`).
    *   `hasUserUsedCoupon` made public for use in `CheckoutController`.

5.  **`index.php`:**
    *   Added route `page=payment&action=webhook` (POST) targeting `PaymentController::handleWebhook`.
    *   Verified checkout action routes (`processCheckout`, `confirmation`, `calculateTax`, `applyCouponAjax`).
    *   Excluded webhook route from automatic CSRF validation.

6.  **`js/main.js`:**
    *   Verified AJAX endpoints (`processCheckout`, `calculateTax`, `applyCouponAjax`) match `index.php` routes.
    *   Verified `return_url` construction in `stripe.confirmPayment` uses `BASE_URL` correctly (via `window.location.origin + document.body.dataset.baseUrl`).
    *   Added data attributes to body tag in `header.php` and updated JS to read them (`stripePublicKey`, `baseUrl`, `freeShippingThreshold`, `baseShippingCost`) for better configuration management.

7.  **`views/order_confirmation.php`:**
    *   Updated to display more details from the fetched `$order` array (status, payment status, items with names/prices). Ensures it uses the data passed correctly.


**Suggested changes to `index.php` (use as reference, don't copy wholesale)**

```php
<?php
// index.php (Added webhook route, verified checkout actions)

define('ROOT_PATH', __DIR__);
require_once __DIR__ . '/config.php';

// --- START: Added Composer Autoloader ---
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    error_log("FATAL ERROR: Composer autoloader not found. Run 'composer install'.");
    echo "Internal Server Error: Application dependencies are missing. Please contact support.";
    exit(1);
}
// --- END: Added Composer Autoloader ---

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/SecurityMiddleware.php';
require_once __DIR__ . '/includes/ErrorHandler.php';

ErrorHandler::init();
SecurityMiddleware::apply(); // Handles session start, base headers

try {
    $page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string') ?: 'home';
    $action = SecurityMiddleware::validateInput($_GET['action'] ?? null, 'string') ?: null;
    $id = SecurityMiddleware::validateInput($_GET['id'] ?? null, 'int');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page !== 'payment' && $action !== 'webhook') { // Don't validate CSRF for Stripe webhooks
        SecurityMiddleware::generateCSRFToken();
        SecurityMiddleware::validateCSRF();
    }

    switch ($page) {
        // Home, Product, Products routes... (unchanged)
        case 'home':
            require_once __DIR__ . '/controllers/ProductController.php';
            $controller = new ProductController($pdo); $controller->showHomePage(); break;
        case 'product':
            require_once __DIR__ . '/controllers/ProductController.php';
            $controller = new ProductController($pdo);
            if ($id) { $controller->showProduct($id); }
            else { http_response_code(404); require_once __DIR__ . '/views/404.php'; }
            break;
        case 'products':
            require_once __DIR__ . '/controllers/ProductController.php';
            $controller = new ProductController($pdo); $controller->showProductList(); break;

        // Cart route... (unchanged)
        case 'cart':
            require_once __DIR__ . '/controllers/CartController.php';
            $controller = new CartController($pdo);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                 if ($action === 'add') { $controller->addToCart(); }
                 elseif ($action === 'update') { $controller->updateCart(); }
                 elseif ($action === 'remove') { $controller->removeFromCart(); }
                 elseif ($action === 'clear') { $controller->clearCart(); }
                 else { http_response_code(405); echo "Method not allowed."; }
            } elseif ($action === 'mini') { $controller->mini(); }
            else { $controller->showCart(); }
            break;

        // --- Updated Checkout Route ---
        case 'checkout':
             if (!isLoggedIn() && $action !== 'confirmation') { // Allow confirmation page check without login initially
                 $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=checkout' . ($action ? '&action='.$action : '');
                 header('Location: ' . BASE_URL . 'index.php?page=login'); exit;
             }
             // Always include controller for checkout page access
             require_once __DIR__ . '/controllers/CheckoutController.php';
             require_once __DIR__ . '/controllers/CartController.php'; // Needed for checks

             // --- Ensure cart isn't empty only when showing the initial page ---
             if (empty($action)) {
                $cartCtrl = new CartController($pdo); // Check cart only for main page load
                if (empty($cartCtrl->getCartItems())) {
                    $this->setFlashMessage('Your cart is empty.', 'info'); // Add flash message
                    header('Location: ' . BASE_URL . 'index.php?page=products'); exit;
                }
            }
            // --- End cart check ---

             $controller = new CheckoutController($pdo);
             if ($action === 'processCheckout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                 $controller->processCheckout(); // Handles AJAX request, returns JSON
             } elseif ($action === 'confirmation') { // Handles GET request after payment redirect
                  $controller->showOrderConfirmation(); // Renders confirmation view
             } elseif ($action === 'calculateTax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                 $controller->calculateTax(); // Handles AJAX, returns JSON
             } elseif ($action === 'applyCouponAjax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                  $controller->applyCouponAjax(); // Handles AJAX, returns JSON
             } else {
                 $controller->showCheckout(); // Default GET: Renders checkout view
             }
             break;
         // --- End Updated Checkout Route ---

        // Account routes... (unchanged)
        case 'login': /* ... */ break;
        case 'register': /* ... */ break;
        case 'logout': /* ... */ break;
        case 'account': /* ... */ break;
        case 'forgot_password': /* ... */ break;
        case 'reset_password': /* ... */ break;

        // Other routes... (unchanged)
        case 'quiz': /* ... */ break;
        case 'newsletter': /* ... */ break;
        case 'admin': /* ... */ break;
        case 'contact': /* ... */ break;
        case 'faq': /* ... */ break;
        case 'shipping': /* ... */ break;
        case 'order-tracking': /* ... */ break;
        case 'privacy': /* ... */ break;
        case 'about': /* ... */ break;

        // --- Added Payment Webhook Route ---
        case 'payment':
             if ($action === 'webhook' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                 require_once __DIR__ . '/controllers/PaymentController.php';
                 $controller = new PaymentController($pdo);
                 $controller->handleWebhook(); // Handles Stripe POST, returns JSON
             } else {
                 // Any other action under /payment is invalid
                 http_response_code(404);
                 require_once __DIR__ . '/views/404.php';
             }
             break;
        // --- End Added Payment Webhook Route ---

        case 'error': /* ... */ break;
        default: /* ... */ break;
    }
} catch (PDOException $e) {
    ErrorHandler::handleException($e); exit(1);
} catch (\Stripe\Exception\ApiErrorException $e) {
     error_log("Stripe API error in routing: " . $e->getMessage());
     ErrorHandler::handleException($e); exit(1);
} catch (Throwable $e) {
    error_log("General error/exception in index.php: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
    ErrorHandler::handleException($e); exit(1);
}
```

**Summary and Conclusion:**

The relevant files (`models/Order.php`, `controllers/PaymentController.php`, `controllers/CheckoutController.php`, `controllers/CouponController.php`, `index.php`, `js/main.js`, `views/order_confirmation.php`) have been updated to implement the full checkout and payment flow using Stripe Payment Intents.

*   The checkout process now correctly validates data, calculates final totals, creates the order record, handles inventory and coupons within a database transaction, initiates the Stripe payment, and returns the necessary `clientSecret` to the frontend.
*   The frontend JavaScript correctly handles the `clientSecret`, initiates Stripe's payment confirmation flow, and includes the necessary `return_url`.
*   The Stripe webhook handler (`PaymentController::handleWebhook`) is implemented to process successful payments (updating order status, sending emails, clearing cart, setting session variable) and failed payments, using transactions for database updates.
*   The order confirmation page (`CheckoutController::showOrderConfirmation` and `views/order_confirmation.php`) now correctly retrieves and displays the completed order details based on the session variable set by the webhook.

