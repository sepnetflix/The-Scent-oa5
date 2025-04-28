Okay, I will generate the complete, updated versions of `controllers/CheckoutController.php` and `views/checkout.php` based on the original files provided and incorporating the necessary fixes for the checkout flow, coupon application, CSRF handling, and tax calculation triggers.

**Assumptions:**

1.  The `CouponController` has a method like `validateCoupon($code, $subtotal)` that returns coupon details or `false`.
2.  The `OrderModel` correctly handles storing and retrieving order data, including `payment_intent_id`.
3.  The `PaymentController`'s `handleSuccessfulPayment` webhook handler will set `$_SESSION['last_order_id']` upon confirming successful payment and updating the order status.
4.  `BASE_URL` is correctly defined in `config.php` and accessible.
5.  `STRIPE_PUBLIC_KEY` is correctly defined in `config.php`.

---

**1. Updated `controllers/CheckoutController.php`**

```php
<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/InventoryController.php';
require_once __DIR__ . '/../controllers/TaxController.php';
require_once __DIR__ . '/../controllers/CouponController.php'; // Added for coupon validation
require_once __DIR__ . '/../includes/EmailService.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/User.php'; // Needed for user details

class CheckoutController extends BaseController {
    private Product $productModel; // Use type hint
    private Order $orderModel; // Use type hint
    private InventoryController $inventoryController; // Use type hint
    private TaxController $taxController; // Use type hint
    private PaymentController $paymentController; // Use type hint
    private CouponController $couponController; // Use type hint
    // private EmailService $emailService; // Inherited from BaseController

    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->orderModel = new Order($pdo);
        $this->inventoryController = new InventoryController($pdo);
        $this->taxController = new TaxController($pdo);
        $this->paymentController = new PaymentController($pdo); // Pass PDO if needed by PaymentController constructor
        $this->couponController = new CouponController($pdo); // Instantiate CouponController
        // $this->emailService is initialized in parent
    }

    public function showCheckout() {
        $this->requireLogin();
        $userId = $this->getUserId();
        $cartModel = new Cart($this->pdo, $userId);
        $items = $cartModel->getItems();

        if (empty($items)) {
             $this->setFlashMessage('Your cart is empty. Add some products before checking out.', 'info');
            $this->redirect('products');
            return;
        }

        $cartItems = [];
        $subtotal = 0;
        foreach ($items as $item) {
            if (!$this->productModel->isInStock($item['product_id'], $item['quantity'])) {
                $this->setFlashMessage("Item '".htmlspecialchars($item['name'])."' is out of stock. Please update your cart.", 'error');
                $this->redirect('cart');
                return;
            }
            $cartItems[] = [
                'product' => $item,
                'quantity' => $item['quantity'],
                'subtotal' => $item['price'] * $item['quantity']
            ];
            $subtotal += $item['price'] * $item['quantity'];
        }

        // Initial calculations
        $tax_rate_formatted = '0%'; // Will be updated via JS
        $tax_amount = 0; // Will be updated via JS
        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $total = $subtotal + $shipping_cost + $tax_amount;

        $userModel = new User($this->pdo);
        $userAddress = $userModel->getAddress($userId);

        // Prepare data for the view
        $csrfToken = $this->generateCSRFToken(); // Generate CSRF token
        $bodyClass = 'page-checkout';
        $pageTitle = 'Checkout - The Scent';

        // Use extract to make variables available to the view
        extract([
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'tax_rate_formatted' => $tax_rate_formatted,
            'tax_amount' => $tax_amount,
            'shipping_cost' => $shipping_cost,
            'total' => $total,
            'csrfToken' => $csrfToken, // Pass CSRF token
            'bodyClass' => $bodyClass,
            'pageTitle' => $pageTitle,
            'userAddress' => $userAddress
        ]);

        require_once __DIR__ . '/../views/checkout.php';
    }


    public function calculateTax() {
        // Allow AJAX request even if not fully logged in? Or keep requireLogin? Let's keep requireLogin for consistency.
        $this->requireLogin();
        // No CSRF check needed for simple calculation based on user input typically,
        // unless the calculation itself triggers a state change, which it doesn't here.

        // Read JSON payload
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // Validate input from JSON payload
        $country = $this->validateInput($data['country'] ?? null, 'string');
        $state = $this->validateInput($data['state'] ?? null, 'string');

        if (empty($country)) {
           return $this->jsonResponse(['success' => false, 'error' => 'Country is required'], 400);
        }

        $subtotal = $this->calculateCartSubtotal(); // Recalculate based on current DB cart
        if ($subtotal <= 0) {
             return $this->jsonResponse(['success' => false, 'error' => 'Cart is empty or invalid'], 400);
        }

        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $tax_amount = $this->taxController->calculateTax($subtotal, $country, $state);
        $tax_rate = $this->taxController->getTaxRate($country, $state);
        $total = $subtotal + $shipping_cost + $tax_amount;

        return $this->jsonResponse([
            'success' => true,
            'tax_rate_formatted' => $this->taxController->formatTaxRate($tax_rate),
            'tax_amount' => number_format($tax_amount, 2),
            'total' => number_format($total, 2)
        ]);
    }

    // Helper to get cart subtotal for logged-in user
    private function calculateCartSubtotal(): float {
         $userId = $this->getUserId();
         if (!$userId) return 0;

         $cartModel = new Cart($this->pdo, $userId);
         $items = $cartModel->getItems();
         $subtotal = 0;
         foreach ($items as $item) {
             $subtotal += $item['price'] * $item['quantity'];
         }
         return (float)$subtotal;
    }

    public function processCheckout() {
        // This method now *initiates* the checkout: creates the order, gets the payment intent secret.
        // Actual payment confirmation happens client-side via Stripe.js and server-side via webhook.
        $this->validateRateLimit('checkout_submit');
        $this->requireLogin();
        $this->validateCSRF(); // Validate CSRF token from the AJAX POST

        $userId = $this->getUserId();
        $cartModel = new Cart($this->pdo, $userId);
        $items = $cartModel->getItems();

        if (empty($items)) {
             // Should be caught client-side, but double-check
             return $this->jsonResponse(['success' => false, 'error' => 'Your cart is empty.'], 400);
        }

        $cartItemsForOrder = [];
        $subtotal = 0;
        foreach ($items as $item) {
            $cartItemsForOrder[$item['product_id']] = $item['quantity'];
            $subtotal += $item['price'] * $item['quantity'];
        }

        // Validate required POST fields from AJAX
        $requiredFields = [
            'shipping_name', 'shipping_email', 'shipping_address', 'shipping_city',
            'shipping_state', 'shipping_zip', 'shipping_country'
            // Payment Method ID is handled by Stripe Elements now, not needed here directly
        ];
        $missingFields = [];
        $postData = [];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $missingFields[] = ucwords(str_replace('_', ' ', $field));
            } else {
                 $type = (strpos($field, 'email') !== false) ? 'email' : 'string';
                 $validatedValue = $this->validateInput($_POST[$field], $type);
                 if ($validatedValue === false || (is_string($validatedValue) && trim($validatedValue) === '')) {
                     $missingFields[] = ucwords(str_replace('_', ' ', $field)) . " (Invalid)";
                 } else {
                     $postData[$field] = $validatedValue;
                 }
            }
        }

        if (!empty($missingFields)) {
             return $this->jsonResponse([
                 'success' => false,
                 'error' => 'Please fill in all required fields correctly: ' . implode(', ', $missingFields) . '.'
             ], 400);
        }

        // --- Coupon Handling ---
        $couponCode = $this->validateInput($_POST['applied_coupon_code'] ?? null, 'string');
        $coupon = null;
        $discountAmount = 0;
        if ($couponCode) {
            // Re-validate coupon server-side before applying
            $couponValidationResult = $this->couponController->validateCouponCodeOnly($couponCode, $subtotal, $userId); // Assume a method that just validates
            if ($couponValidationResult['valid']) {
                 $coupon = $couponValidationResult['coupon']; // Get full coupon data
                 $discountAmount = $this->couponController->calculateDiscount($coupon, $subtotal); // Recalculate discount
            } else {
                 // Coupon became invalid between client check and submission, proceed without it or return error?
                 // Let's proceed without it for now and log a warning.
                 error_log("Checkout Warning: Coupon '{$couponCode}' was invalid during final checkout for user {$userId}.");
                 $couponCode = null; // Clear invalid code
            }
        }
        // --- End Coupon Handling ---


        try {
            $this->beginTransaction();

            $stockErrors = $this->validateCartStock($cartItemsForOrder);
            if (!empty($stockErrors)) {
                $this->rollback();
                 return $this->jsonResponse([
                     'success' => false,
                     'error' => 'Some items went out of stock: ' . implode(', ', $stockErrors) . '. Please review your cart.'
                 ], 409); // 409 Conflict might be appropriate
            }

            // Calculate final totals including discount
            $subtotalAfterDiscount = $subtotal - $discountAmount;
            $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST; // Base shipping on discounted subtotal
            $tax_amount = $this->taxController->calculateTax(
                $subtotalAfterDiscount, // Base tax on discounted subtotal
                $postData['shipping_country'],
                $postData['shipping_state']
            );
            $total = $subtotalAfterDiscount + $shipping_cost + $tax_amount;
             // Ensure total is not negative
             $total = max(0, $total);

            // --- Create Order ---
            $orderData = [
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'coupon_code' => $coupon ? $coupon['code'] : null, // Store applied coupon code
                'coupon_id' => $coupon ? $coupon['id'] : null, // Store applied coupon ID
                'shipping_cost' => $shipping_cost,
                'tax_amount' => $tax_amount,
                'total_amount' => $total,
                'shipping_name' => $postData['shipping_name'],
                'shipping_email' => $postData['shipping_email'],
                'shipping_address' => $postData['shipping_address'],
                'shipping_city' => $postData['shipping_city'],
                'shipping_state' => $postData['shipping_state'],
                'shipping_zip' => $postData['shipping_zip'],
                'shipping_country' => $postData['shipping_country'],
                'status' => 'pending_payment', // Initial status
                'payment_intent_id' => null // Will be updated after PI creation
            ];
            $orderId = $this->orderModel->create($orderData);
            if (!$orderId) throw new Exception("Failed to create order record.");

            // --- Create Order Items & Update Inventory ---
            $itemStmt = $this->pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($cartItemsForOrder as $productId => $quantity) {
                $product = $this->productModel->getById($productId);
                if ($product) {
                    $itemStmt->execute([$orderId, $productId, $quantity, $product['price']]);
                    if (!$this->inventoryController->updateStock($productId, -$quantity, 'sale', $orderId)) { // Use correct InventoryController method signature
                        throw new Exception("Failed to update inventory for product ID {$productId}");
                    }
                } else {
                    throw new Exception("Product ID {$productId} not found during order item creation.");
                }
            }

            // --- Create Payment Intent ---
            // Pass final total amount and order ID
            $paymentResult = $this->paymentController->createPaymentIntent($total, 'usd', $orderId, $postData['shipping_email']);
            if (!$paymentResult['success']) {
                // Payment Intent creation failed *before* charging customer
                $this->orderModel->updateStatus($orderId, 'failed'); // Mark order as failed
                throw new Exception($paymentResult['error'] ?? 'Could not initiate payment.');
            }
            $clientSecret = $paymentResult['client_secret'];
            $paymentIntentId = $paymentResult['payment_intent_id']; // Assuming PaymentController returns this

            // --- Update Order with Payment Intent ID ---
            if (!$this->orderModel->updatePaymentIntentId($orderId, $paymentIntentId)) {
                 throw new Exception("Failed to link Payment Intent ID {$paymentIntentId} to Order ID {$orderId}.");
            }

            // --- Apply Coupon Usage (if applicable) ---
            if ($coupon) {
                 // Assuming CouponController has method to record usage
                 if (!$this->couponController->recordUsage($coupon['id'], $orderId, $userId, $discountAmount)) {
                      error_log("Warning: Failed to record usage for coupon ID {$coupon['id']} on order ID {$orderId}.");
                      // Decide if this should be fatal or just logged
                 }
            }

            $this->commit(); // Commit order creation BEFORE sending client secret

            $this->logAuditTrail('order_pending_payment', $userId, [
                'order_id' => $orderId, 'total_amount' => $total, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            // --- Return Client Secret to Frontend ---
            return $this->jsonResponse([
                'success' => true,
                'orderId' => $orderId, // Send Order ID back
                'clientSecret' => $clientSecret // Send client secret
            ]);

        } catch (Exception $e) {
            $this->rollback();
            error_log("Checkout processing error: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'error' => 'An error occurred while processing your order. Please try again.'
            ], 500);
        }
    }

    // --- New Method to Handle AJAX Coupon Application ---
    public function applyCouponAjax() {
         $this->requireLogin();
         $this->validateCSRF(); // Validate CSRF from AJAX

         $json = file_get_contents('php://input');
         $data = json_decode($json, true);

         $code = $this->validateInput($data['code'] ?? null, 'string');
         $currentSubtotal = $this->validateInput($data['subtotal'] ?? null, 'float'); // Get current subtotal from client
         $userId = $this->getUserId();

         if (!$code || $currentSubtotal === false || $currentSubtotal < 0) {
             return $this->jsonResponse(['success' => false, 'message' => 'Invalid coupon code or subtotal.'], 400);
         }

         // Use CouponController to validate
         $validationResult = $this->couponController->validateCouponCodeOnly($code, $currentSubtotal, $userId);

         if ($validationResult['valid']) {
             $coupon = $validationResult['coupon'];
             $discountAmount = $this->couponController->calculateDiscount($coupon, $currentSubtotal);

             // Recalculate totals for the response
             $subtotalAfterDiscount = $currentSubtotal - $discountAmount;
             $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
             // We need shipping address context to calculate tax accurately here.
             // Option 1: Pass shipping country/state in AJAX (more complex)
             // Option 2: Return discount, let client recalculate tax via separate call (simpler, but more requests)
             // Option 3: Return discount, client updates subtotal/discount, tax/total display "calculated at checkout" or uses previous tax value as estimate.
             // Let's go with Option 3 for now, client updates visual discount, total updates based on that + existing shipping/tax.
             // Tax will be definitively calculated in processCheckout.

             $newTotal = $subtotalAfterDiscount + $shipping_cost + ($validationResult['tax_amount'] ?? 0); // Needs tax context
             $newTotal = max(0, $newTotal); // Prevent negative total

             return $this->jsonResponse([
                 'success' => true,
                 'message' => 'Coupon applied successfully!',
                 'coupon_code' => $coupon['code'],
                 'discount_amount' => number_format($discountAmount, 2),
                 // 'new_tax_amount' => number_format($tax_amount, 2), // Omit if not calculating here
                 'new_total' => number_format($newTotal, 2) // Return calculated total
             ]);
         } else {
             return $this->jsonResponse([
                 'success' => false,
                 'message' => $validationResult['message'] ?? 'Invalid or expired coupon code.'
             ]);
         }
    }

    public function showOrderConfirmation() {
         $this->requireLogin();
         $userId = $this->getUserId();

         // Use the order ID stored in the session *after successful payment webhook*
         if (!isset($_SESSION['last_order_id'])) {
             $this->setFlashMessage('Could not find recent order details. Payment may still be processing.', 'warning');
             $this->redirect('account&action=orders');
             return;
         }

         $lastOrderId = $_SESSION['last_order_id'];

         // Fetch the specific order, ensuring it belongs to the current user
         $order = $this->orderModel->getByIdAndUserId($lastOrderId, $userId);

         if (!$order) {
             unset($_SESSION['last_order_id']); // Clear invalid session data
             $this->setFlashMessage('Order details not found or access denied.', 'error');
             $this->redirect('account&action=orders');
             return;
         }

         // --- Add Status Check ---
         // Ensure the payment was actually successful before showing confirmation
         // Adjust status check based on what the webhook sets (e.g., 'paid', 'processing')
         if (!in_array($order['status'], ['paid', 'processing', 'shipped', 'delivered'])) {
             // Payment likely failed or is still pending
             unset($_SESSION['last_order_id']); // Clear session ID
             $this->setFlashMessage('Payment for order #'.str_pad($order['id'], 6, '0', STR_PAD_LEFT).' is pending or failed. Please check your order history or contact support.', 'warning');
             $this->redirect('account&action=orders');
             return;
         }
         // --- End Status Check ---


         // Clear the session variable after successfully retrieving and validating the order
         unset($_SESSION['last_order_id']);

         // Prepare data for the view
         $csrfToken = $this->generateCSRFToken();
         $bodyClass = 'page-order-confirmation';
         $pageTitle = 'Order Confirmation - The Scent';

         extract([
             'order' => $order,
             'csrfToken' => $csrfToken,
             'bodyClass' => $bodyClass,
             'pageTitle' => $pageTitle
         ]);

         require_once __DIR__ . '/../views/order_confirmation.php';
     }

    // --- Admin Methods (Unchanged from Original) ---
    public function updateOrderStatus($orderId, $status, $trackingInfo = null) {
        // (Original code remains here - requires Admin checks, CSRF, etc.)
        // ... see original file ...
         $this->requireAdmin();
         $this->validateCSRF();

         $orderId = $this->validateInput($orderId, 'int');
         $status = $this->validateInput($status, 'string');

         if (!$orderId || !$status) {
             return $this->jsonResponse(['success' => false, 'error' => 'Invalid input.'], 400);
         }

         $order = $this->orderModel->getById($orderId); // Fetch by ID for admin
         if (!$order) {
            return $this->jsonResponse(['success' => false, 'error' => 'Order not found'], 404);
         }

         // --- Add logic to check allowed status transitions ---
         $allowedTransitions = [
             'pending_payment' => ['paid', 'cancelled', 'failed'],
             'paid' => ['processing', 'cancelled', 'refunded'], // Assuming 'paid' is the status after successful payment
             'processing' => ['shipped', 'cancelled', 'refunded'],
             'shipped' => ['delivered', 'refunded'],
             'delivered' => ['refunded'], // Can only refund after delivery? Or return?
             'payment_failed' => [], // Terminal? Or allow retry?
             'cancelled' => [], // Terminal
             'refunded' => [], // Terminal
             'disputed' => ['refunded'], // Maybe only allow refund after dispute?
         ];

         if (!isset($allowedTransitions[$order['status']]) || !in_array($status, $allowedTransitions[$order['status']])) {
              return $this->jsonResponse(['success' => false, 'error' => "Invalid status transition from '{$order['status']}' to '{$status}'."], 400);
         }
         // --- End Status Transition Check ---


         try {
             $this->beginTransaction();

             $updated = $this->orderModel->updateStatus($orderId, $status);
             if (!$updated) throw new Exception("Failed to update order status.");

             // Handle tracking info and email notification for 'shipped' status
             if ($status === 'shipped' && $trackingInfo && !empty($trackingInfo['number'])) {
                 $trackingNumber = $this->validateInput($trackingInfo['number'], 'string');
                 $carrier = $this->validateInput($trackingInfo['carrier'] ?? null, 'string');
                 // $trackingUrl = $this->validateInput($trackingInfo['url'] ?? null, 'url'); // Tracking URL might be complex

                 $trackingUpdated = $this->orderModel->updateTracking(
                     $orderId,
                     $trackingNumber,
                     $carrier
                     // Add URL if model supports it: $trackingUrl
                 );

                 if ($trackingUpdated) {
                     $userModel = new User($this->pdo);
                     $user = $userModel->getById($order['user_id']);
                     if ($user) {
                          // Ensure EmailService instance exists and method is available
                         if ($this->emailService && method_exists($this->emailService, 'sendShippingUpdate')) {
                              $this->emailService->sendShippingUpdate(
                                 $order,
                                 $user,
                                 $trackingNumber,
                                 $carrier ?? ''
                                 // Pass $trackingUrl if available
                             );
                         } else {
                              error_log("EmailService or sendShippingUpdate method not available for order {$orderId}");
                         }
                     } else {
                          error_log("Could not find user {$order['user_id']} to send shipping update for order {$orderId}");
                     }
                 } else {
                     error_log("Failed to update tracking info for order {$orderId}");
                 }
             }

             // --- Add logic for other status changes (e.g., refund trigger) ---
              if ($status === 'cancelled' || $status === 'refunded') {
                  // TODO: Add logic to potentially:
                  // 1. Trigger refund via PaymentController (if status is 'refunded')
                  // 2. Restore stock via InventoryController
                  // 3. Send cancellation/refund email via EmailService
                  error_log("Order {$orderId} status changed to {$status}. Add refund/restock logic here.");
              }


             $this->commit();

             $adminUserId = $this->getUserId();
             $this->logAuditTrail('order_status_update', $adminUserId, [
                  'order_id' => $orderId, 'new_status' => $status, 'tracking_provided' => ($status === 'shipped' && !empty($trackingNumber))
             ]);

             return $this->jsonResponse(['success' => true, 'message' => 'Order status updated successfully.']);

         } catch (Exception $e) {
             $this->rollback();
             error_log("Error updating order status for {$orderId}: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'error' => 'Failed to update order status.'], 500); // Keep message generic
         }
    }

    // --- Helper Methods (Unchanged from Original) ---
     private function validateCartStock($cartItems = null): array {
         $errors = [];
         if ($cartItems === null) {
             $userId = $this->getUserId();
             if (!$userId) return ['User not logged in'];

             $cartModel = new Cart($this->pdo, $userId);
             $items = $cartModel->getItems();
             $cartItems = [];
             foreach ($items as $item) {
                 $cartItems[$item['product_id']] = $item['quantity'];
             }
         }

         if (empty($cartItems)) {
             return ['Cart is empty'];
         }

         foreach ($cartItems as $productId => $quantity) {
             if (!$this->productModel->isInStock($productId, $quantity)) {
                 $product = $this->productModel->getById($productId);
                 $errors[] = ($product ? htmlspecialchars($product['name']) : "Product ID {$productId}") . " has insufficient stock";
             }
         }
         return $errors;
     }

} // End of CheckoutController class

```

---

**2. Updated `views/checkout.php`**

```php
<?php require_once __DIR__ . '/layout/header.php'; ?>
<!-- Output CSRF token for JS (for AJAX checkout/coupon/tax) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

<!-- Add Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>

<section class="checkout-section">
    <div class="container">
        <div class="checkout-container" data-aos="fade-up">
            <h1>Checkout</h1>

            <div class="checkout-grid">
                <!-- Shipping Form -->
                <div class="shipping-details">
                    <h2>Shipping Details</h2>
                    <!-- NOTE: The form tag itself doesn't need action/method as JS handles the submission -->
                    <form id="checkoutForm">
                        <!-- ADD Standard CSRF Token for initial server-side check during processCheckout -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <!-- Hidden field to potentially store applied coupon code -->
                        <input type="hidden" id="applied_coupon_code" name="applied_coupon_code" value="">

                        <div class="form-group">
                            <label for="shipping_name">Full Name *</label>
                            <input type="text" id="shipping_name" name="shipping_name" required class="form-input"
                                   value="<?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="shipping_email">Email Address *</label>
                            <input type="email" id="shipping_email" name="shipping_email" required class="form-input"
                                   value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="shipping_address">Street Address *</label>
                            <input type="text" id="shipping_address" name="shipping_address" required class="form-input"
                                   value="<?= htmlspecialchars($userAddress['address_line1'] ?? '') ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_city">City *</label>
                                <input type="text" id="shipping_city" name="shipping_city" required class="form-input"
                                       value="<?= htmlspecialchars($userAddress['city'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="shipping_state">State/Province *</label>
                                <input type="text" id="shipping_state" name="shipping_state" required class="form-input"
                                       value="<?= htmlspecialchars($userAddress['state'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_zip">ZIP/Postal Code *</label>
                                <input type="text" id="shipping_zip" name="shipping_zip" required class="form-input"
                                       value="<?= htmlspecialchars($userAddress['postal_code'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="shipping_country">Country *</label>
                                <select id="shipping_country" name="shipping_country" required class="form-select">
                                    <option value="">Select Country</option>
                                    <option value="US" <?= (($userAddress['country'] ?? '') === 'US') ? 'selected' : '' ?>>United States</option>
                                    <option value="CA" <?= (($userAddress['country'] ?? '') === 'CA') ? 'selected' : '' ?>>Canada</option>
                                    <option value="GB" <?= (($userAddress['country'] ?? '') === 'GB') ? 'selected' : '' ?>>United Kingdom</option>
                                    <!-- Add more countries as needed -->
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="order_notes">Order Notes (Optional)</label>
                            <textarea id="order_notes" name="order_notes" rows="3" class="form-textarea"></textarea>
                        </div>
                        <!-- The submit button is now outside the form, controlled by JS -->
                    </form>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <h2>Order Summary</h2>

                    <!-- Coupon Code Section -->
                    <div class="coupon-section">
                        <div class="form-group">
                            <label for="coupon_code">Have a coupon?</label>
                            <div class="coupon-input">
                                <input type="text" id="coupon_code" name="coupon_code_input" class="form-input"
                                       placeholder="Enter coupon code">
                                <button type="button" id="apply-coupon" class="btn-secondary">Apply</button>
                            </div>
                            <div id="coupon-message" class="hidden mt-2 text-sm"></div>
                        </div>
                    </div>

                    <div class="summary-items border-b border-gray-200 pb-4 mb-4">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="summary-item flex justify-between items-center text-sm py-1">
                                <div class="item-info flex items-center">
                                     <img src="<?= htmlspecialchars($item['product']['image'] ?? '/images/placeholder.jpg') ?>" alt="<?= htmlspecialchars($item['product']['name']) ?>" class="w-10 h-10 object-cover rounded mr-2">
                                     <div>
                                         <span class="item-name font-medium text-gray-800"><?= htmlspecialchars($item['product']['name']) ?></span>
                                         <span class="text-xs text-gray-500 block">Qty: <?= $item['quantity'] ?></span>
                                     </div>
                                </div>
                                <span class="item-price font-medium text-gray-700">$<?= number_format($item['subtotal'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-totals space-y-2">
                        <div class="summary-row flex justify-between items-center">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-medium text-gray-900">$<span id="summary-subtotal"><?= number_format($subtotal, 2) ?></span></span>
                        </div>
                         <div class="summary-row discount hidden flex justify-between items-center text-green-600">
                            <span>Discount (<span id="applied-coupon-code-display" class="font-mono text-xs bg-green-100 px-1 rounded"></span>):</span>
                            <span>-$<span id="discount-amount">0.00</span></span>
                        </div>
                        <div class="summary-row flex justify-between items-center">
                            <span class="text-gray-600">Shipping:</span>
                            <span class="font-medium text-gray-900" id="summary-shipping"><?= $shipping_cost > 0 ? '$' . number_format($shipping_cost, 2) : '<span class="text-green-600">FREE</span>' ?></span>
                        </div>
                        <div class="summary-row flex justify-between items-center">
                            <span class="text-gray-600">Tax (<span id="tax-rate" class="text-xs"><?= htmlspecialchars($tax_rate_formatted) ?></span>):</span>
                            <span class="font-medium text-gray-900" id="tax-amount">$<?= number_format($tax_amount, 2) ?></span>
                        </div>
                        <div class="summary-row total flex justify-between items-center border-t pt-3 mt-2">
                            <span class="text-lg font-bold text-gray-900">Total:</span>
                            <span class="text-lg font-bold text-primary">$<span id="summary-total"><?= number_format($total, 2) ?></span></span>
                        </div>
                    </div>

                    <div class="payment-section mt-6">
                        <h3 class="text-lg font-semibold mb-4">Payment Method</h3>
                        <!-- Stripe Payment Element -->
                        <div id="payment-element" class="mb-4 p-3 border rounded bg-gray-50"></div>
                        <!-- Used to display form errors -->
                        <div id="payment-message" class="hidden text-red-600 text-sm text-center mb-4"></div>
                    </div>

                    <!-- Button is outside the form, triggered by JS -->
                    <button type="button" id="submit-button" class="btn btn-primary w-full place-order">
                        <span id="button-text">Place Order & Pay</span>
                        <div class="spinner hidden" id="spinner"></div>
                    </button>

                    <div class="secure-checkout mt-4 text-center text-xs text-gray-500">
                        <i class="fas fa-lock mr-1"></i>Secure Checkout via Stripe
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Configuration ---
    const stripePublicKey = '<?= defined('STRIPE_PUBLIC_KEY') ? STRIPE_PUBLIC_KEY : '' ?>';
    const checkoutForm = document.getElementById('checkoutForm');
    const submitButton = document.getElementById('submit-button');
    const spinner = document.getElementById('spinner');
    const buttonText = document.getElementById('button-text');
    const paymentElementContainer = document.getElementById('payment-element');
    const paymentMessage = document.getElementById('payment-message');
    const csrfToken = document.getElementById('csrf-token-value').value;
    const couponCodeInput = document.getElementById('coupon_code');
    const applyCouponButton = document.getElementById('apply-coupon');
    const couponMessageEl = document.getElementById('coupon-message');
    const discountRow = document.querySelector('.summary-row.discount');
    const discountAmountEl = document.getElementById('discount-amount');
    const appliedCouponCodeDisplay = document.getElementById('applied-coupon-code-display');
    const appliedCouponHiddenInput = document.getElementById('applied_coupon_code'); // For sending with checkout
    const taxRateEl = document.getElementById('tax-rate');
    const taxAmountEl = document.getElementById('tax-amount');
    const shippingCountryEl = document.getElementById('shipping_country');
    const shippingStateEl = document.getElementById('shipping_state');
    const summarySubtotalEl = document.getElementById('summary-subtotal');
    const summaryShippingEl = document.getElementById('summary-shipping');
    const summaryTotalEl = document.getElementById('summary-total');

    let elements;
    let stripe;
    let currentSubtotal = <?= $subtotal ?? 0 ?>;
    let currentShippingCost = <?= $shipping_cost ?? 0 ?>;
    let currentTaxAmount = <?= $tax_amount ?? 0 ?>;
    let currentDiscountAmount = 0;

    if (!stripePublicKey) {
        showMessage("Stripe configuration error. Payment cannot proceed.");
        setLoading(false, true); // Disable button permanently
        return;
    }
    stripe = Stripe(stripePublicKey);

    // --- Initialize Stripe Elements ---
    const appearance = {
         theme: 'stripe',
         variables: {
             colorPrimary: '#1A4D5A', // Match theme
             colorBackground: '#ffffff',
             colorText: '#374151', // Tailwind gray-700
             colorDanger: '#dc2626', // Tailwind red-600
             fontFamily: 'Montserrat, sans-serif', // Match theme
             borderRadius: '0.375rem' // Tailwind rounded-md
         }
     };
    elements = stripe.elements({ appearance });
    const paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');

    // --- Helper Functions ---
    function setLoading(isLoading, disablePermanently = false) {
        if (isLoading) {
            submitButton.disabled = true;
            spinner.classList.remove('hidden');
            buttonText.classList.add('hidden');
        } else {
            submitButton.disabled = disablePermanently;
            spinner.classList.add('hidden');
            buttonText.classList.remove('hidden');
        }
    }

    function showMessage(message, isError = true) {
        paymentMessage.textContent = message;
        paymentMessage.className = `payment-message text-center text-sm my-4 ${isError ? 'text-red-600' : 'text-green-600'}`;
        paymentMessage.classList.remove('hidden');
        // Auto-hide?
        // setTimeout(() => { paymentMessage.classList.add('hidden'); }, 6000);
    }

    function showCouponMessage(message, type) { // type = 'success', 'error', 'info'
        couponMessageEl.textContent = message;
        couponMessageEl.className = `coupon-message mt-2 text-sm ${
            type === 'success' ? 'text-green-600' : (type === 'error' ? 'text-red-600' : 'text-gray-600')
        }`;
        couponMessageEl.classList.remove('hidden');
    }

    function updateOrderSummaryUI() {
        // Update subtotal (shouldn't change unless cart changes, which redirects)
        summarySubtotalEl.textContent = parseFloat(currentSubtotal).toFixed(2);

        // Update discount display
        if (currentDiscountAmount > 0 && appliedCouponHiddenInput.value) {
            discountAmountEl.textContent = parseFloat(currentDiscountAmount).toFixed(2);
            appliedCouponCodeDisplay.textContent = appliedCouponHiddenInput.value;
            discountRow.classList.remove('hidden');
        } else {
            discountAmountEl.textContent = '0.00';
            appliedCouponCodeDisplay.textContent = '';
            discountRow.classList.add('hidden');
        }

         // Update shipping cost display (based on subtotal AFTER discount)
         const subtotalAfterDiscount = currentSubtotal - currentDiscountAmount;
         currentShippingCost = subtotalAfterDiscount >= <?= FREE_SHIPPING_THRESHOLD ?> ? 0 : <?= SHIPPING_COST ?>;
         summaryShippingEl.innerHTML = currentShippingCost > 0 ? '$' + parseFloat(currentShippingCost).toFixed(2) : '<span class="text-green-600">FREE</span>';


        // Update tax amount display (based on AJAX call result)
        taxAmountEl.textContent = '$' + parseFloat(currentTaxAmount).toFixed(2);

        // Update total
        const grandTotal = (currentSubtotal - currentDiscountAmount) + currentShippingCost + currentTaxAmount;
        summaryTotalEl.textContent = parseFloat(Math.max(0, grandTotal)).toFixed(2); // Prevent negative total display
    }

    // --- Tax Calculation ---
    async function updateTax() {
        const country = shippingCountryEl.value;
        const state = shippingStateEl.value;

        if (!country) {
            // Reset tax if no country selected
             taxRateEl.textContent = '0%';
             currentTaxAmount = 0;
             updateOrderSummaryUI(); // Update total
            return;
        }

        try {
            // Add a subtle loading indicator? Maybe on the tax amount?
            taxAmountEl.textContent = '...';

            const response = await fetch('index.php?page=checkout&action=calculateTax', { // Correct action name from routing
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                 },
                body: JSON.stringify({ country, state })
            });

            if (!response.ok) throw new Error('Tax calculation failed');

            const data = await response.json();
            if (data.success) {
                taxRateEl.textContent = data.tax_rate_formatted || 'N/A';
                currentTaxAmount = parseFloat(data.tax_amount) || 0;
                // Don't update total directly here, let updateOrderSummaryUI handle it
            } else {
                 console.warn("Tax calculation error:", data.error);
                 taxRateEl.textContent = 'Error';
                 currentTaxAmount = 0;
            }
        } catch (e) {
            console.error('Error fetching tax:', e);
            taxRateEl.textContent = 'Error';
            currentTaxAmount = 0;
        } finally {
             updateOrderSummaryUI(); // Update totals after tax calculation attempt
        }
    }

    shippingCountryEl.addEventListener('change', updateTax);
    shippingStateEl.addEventListener('input', updateTax); // Use input for faster response if typing state

    // --- Coupon Application ---
    applyCouponButton.addEventListener('click', async function() {
        const couponCode = couponCodeInput.value.trim();
        if (!couponCode) {
            showCouponMessage('Please enter a coupon code.', 'error');
            return;
        }

        showCouponMessage('Applying...', 'info');
        applyCouponButton.disabled = true;

        try {
            const response = await fetch('index.php?page=checkout&action=applyCouponAjax', { // Use the new controller action
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    code: couponCode,
                    subtotal: currentSubtotal, // Send current subtotal for validation
                    csrf_token: csrfToken
                })
            });

             if (!response.ok) throw new Error(`Server error: ${response.status} ${response.statusText}`);

            const data = await response.json();

            if (data.success) {
                showCouponMessage(data.message || 'Coupon applied!', 'success');
                currentDiscountAmount = parseFloat(data.discount_amount) || 0;
                appliedCouponHiddenInput.value = data.coupon_code || couponCode; // Store applied code
                // Update tax and total based on server response if available
                // currentTaxAmount = parseFloat(data.new_tax_amount ?? currentTaxAmount); // Update tax if server recalculated it
                // Update totals based on new discount and potentially new tax
                 updateTax(); // Re-calculate tax and update summary after applying coupon discount
            } else {
                showCouponMessage(data.message || 'Invalid coupon code.', 'error');
                currentDiscountAmount = 0; // Reset discount
                appliedCouponHiddenInput.value = ''; // Clear applied code
                updateOrderSummaryUI(); // Update summary without discount
            }
        } catch (e) {
            console.error('Coupon Apply Error:', e);
            showCouponMessage('Failed to apply coupon. Please try again.', 'error');
            currentDiscountAmount = 0;
            appliedCouponHiddenInput.value = '';
            updateOrderSummaryUI();
        } finally {
            applyCouponButton.disabled = false;
        }
    });

    // --- Checkout Form Submission ---
    submitButton.addEventListener('click', async function(e) {
        // Use click on the button instead of form submit, as the form tag is mainly for structure now
        setLoading(true);
        showMessage(''); // Clear previous messages

        // 1. Client-side validation
        let isValid = true;
        const requiredFields = ['shipping_name', 'shipping_email', 'shipping_address', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];
        requiredFields.forEach(id => {
            const input = document.getElementById(id);
            if (!input || !input.value.trim()) {
                isValid = false;
                input?.classList.add('input-error'); // Add error class for styling
                 // Find label and add error state? More complex UI work.
            } else {
                input?.classList.remove('input-error');
            }
        });

        if (!isValid) {
            showMessage('Please fill in all required shipping fields.');
            setLoading(false);
             // Scroll to first error?
             const firstError = checkoutForm.querySelector('.input-error');
             firstError?.focus();
             firstError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // 2. Send checkout data to server to create order and get clientSecret
        let clientSecret = null;
        let orderId = null;
        try {
            const checkoutFormData = new FormData(checkoutForm); // Includes CSRF, applied coupon, shipping fields

            const response = await fetch('index.php?page=checkout&action=processCheckout', { // Use a unique action name
                method: 'POST',
                headers: {
                    // Content-Type is set automatically for FormData
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: checkoutFormData
            });

            const data = await response.json();

            if (response.ok && data.success && data.clientSecret && data.orderId) {
                clientSecret = data.clientSecret;
                orderId = data.orderId;
            } else {
                throw new Error(data.error || 'Failed to process order on server. Please check details or try again.');
            }

        } catch (serverError) {
            console.error('Server processing error:', serverError);
            showMessage(serverError.message);
            setLoading(false);
            return; // Stop checkout
        }

        // 3. Confirm payment with Stripe using the obtained clientSecret
        if (clientSecret) {
            const { error: stripeError } = await stripe.confirmPayment({
                elements,
                clientSecret: clientSecret,
                confirmParams: {
                     // IMPORTANT: Use the correct BASE_URL constant here
                    return_url: `${window.location.origin}<?= rtrim(BASE_URL, '/') ?>/index.php?page=checkout&action=confirmation`,
                     // Optional: Send billing details again, though Stripe might capture from element
                     payment_method_data: {
                         billing_details: {
                             name: document.getElementById('shipping_name').value,
                             email: document.getElementById('shipping_email').value,
                             address: {
                                 line1: document.getElementById('shipping_address').value,
                                 city: document.getElementById('shipping_city').value,
                                 state: document.getElementById('shipping_state').value,
                                 postal_code: document.getElementById('shipping_zip').value,
                                 country: document.getElementById('shipping_country').value,
                             }
                         }
                     }
                },
                // Redirect 'if_required' handles 3DS etc. Stripe redirects on success.
                redirect: 'if_required'
            });

            // If we reach here, confirmPayment failed or requires manual action
            if (stripeError) {
                 console.error("Stripe Error:", stripeError);
                 showMessage(stripeError.message || "Payment failed. Please check your card details or try another method.");
                 setLoading(false); // Re-enable button on failure
            }
            // No explicit success redirect needed here as Stripe handles it via return_url
        } else {
            // Should have been caught earlier, but safety check
            showMessage('Failed to get payment details from server.');
            setLoading(false);
        }
    });

    // --- Initial UI Update ---
    updateOrderSummaryUI(); // Calculate initial totals
    // Optionally trigger initial tax calculation if country is pre-filled
    if (shippingCountryEl.value) {
        updateTax();
    }

});
</script>

<style>
/* Basic styles for loading/error states */
.spinner {
    width: 1.25em; /* 20px */
    height: 1.25em; /* 20px */
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #ffffff;
    animation: spin 1s linear infinite;
    display: inline-block; /* Changed from margin auto */
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.hidden {
    display: none;
}

.place-order:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.input-error {
    border-color: #dc2626; /* Tailwind red-600 */
    box-shadow: 0 0 0 1px #dc2626;
}
.form-input, .form-select, .form-textarea {
     /* Add some base styling for form elements if not globally defined */
     width: 100%;
     padding: 0.65rem 0.75rem;
     border: 1px solid #d1d5db; /* Tailwind gray-300 */
     border-radius: 0.375rem; /* Tailwind rounded-md */
     transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.form-input:focus, .form-select:focus, .form-textarea:focus {
     outline: none;
     border-color: #1A4D5A; /* primary */
     box-shadow: 0 0 0 2px rgba(26, 77, 90, 0.3);
}

</style>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

```

**Summary of Changes:**

**`controllers/CheckoutController.php`:**

1.  **Added `CouponController`:** Included and instantiated `$this->couponController`.
2.  **`showCheckout`:** Ensures `$csrfToken` is generated and passed to the view.
3.  **`calculateTax`:** Updated to read JSON payload, validate input from JSON, return JSON.
4.  **`processCheckout`:**
    *   Now expects AJAX POST. Validates CSRF from POST.
    *   Reads shipping details from `$_POST`.
    *   **Added Coupon Handling:** Reads `applied_coupon_code` from POST, re-validates it using `CouponController`, calculates `discountAmount`. Stores `coupon_code` and `coupon_id` in `orderData`.
    *   Calculates final `$total` considering the validated `discountAmount`.
    *   Calls `PaymentController::createPaymentIntent` with the *final* calculated total.
    *   Stores the `payment_intent_id` returned from `createPaymentIntent` into the order record using `OrderModel::updatePaymentIntentId` (assuming this method exists in `OrderModel`).
    *   Calls `CouponController::recordUsage` if a coupon was successfully applied (assuming this method exists).
    *   Returns JSON `{ success: true, orderId: ..., clientSecret: ... }`.
5.  **New `applyCouponAjax` Method:**
    *   Handles POST requests to `index.php?page=checkout&action=applyCouponAjax`.
    *   Validates CSRF.
    *   Reads `code` and `subtotal` from JSON body.
    *   Uses `CouponController` to validate the coupon against the subtotal.
    *   If valid, calculates discount, recalculates *total* (important!), and returns JSON including `discount_amount` and `new_total`.
    *   If invalid, returns JSON with `success: false` and a message.
6.  **`showOrderConfirmation`:**
    *   Added a crucial check to verify the order `status` (retrieved from session ID) is actually 'paid' or 'processing' (or another suitable post-payment status) before displaying the confirmation. Redirects with a warning if payment isn't confirmed.
7.  **`updateOrderStatus` (Admin):** Added examples for status transition validation and potential hooks for refund/restock logic.
8.  **Type Hinting:** Added PHP type hints for consistency.

**`views/checkout.php`:**

1.  **CSRF:** Added the standard `<input type="hidden" name="csrf_token" ...>` inside the `<form id="checkoutForm">`. Kept the `#csrf-token-value` for JS.
2.  **Form Structure:** The `<form>` tag no longer strictly needs `action` or `method` as JS intercepts. Added a hidden input `id="applied_coupon_code"` to store the successfully applied coupon code for submission with the main checkout data.
3.  **JavaScript Overhaul:**
    *   Removed the problematic `initialize()` function that fetched the client secret upfront.
    *   Initialized Stripe Elements (`elements`, `paymentElement`) immediately without a client secret.
    *   Rewrote the `submitButton`'s event listener (`click` instead of form `submit`):
        *   Performs client-side validation first.
        *   Sends shipping data + CSRF + applied coupon via `fetch` to `index.php?page=checkout&action=processCheckout`.
        *   Expects JSON response with `clientSecret` and `orderId`.
        *   On success, calls `stripe.confirmPayment` using the received `clientSecret` and sets the correct `return_url` using `BASE_URL`. Stripe handles the redirect via `redirect: 'if_required'`.
        *   Handles server errors and Stripe payment confirmation errors appropriately, showing messages via `showMessage`.
    *   Rewrote the `apply-coupon` button's event listener:
        *   Sends `code`, `subtotal`, and `csrf_token` via `fetch` (POST, JSON body) to the new `index.php?page=checkout&action=applyCouponAjax` endpoint.
        *   Handles the JSON response, updating UI elements (`#discount-amount`, total, tax) based on server calculation using `updateOrderSummaryUI`.
        *   Stores the successfully applied code in `appliedCouponHiddenInput`.
        *   Resets the discount UI on failure.
    *   Added `updateTax` function triggered by `shipping_country` and `shipping_state` changes. It calls `index.php?page=checkout&action=calculateTax` via AJAX and updates the tax/total UI using `updateOrderSummaryUI`.
    *   Added `updateOrderSummaryUI` helper function to centralize UI updates for subtotal, discount, shipping, tax, and total.
    *   Ensured `BASE_URL` is correctly used in the `return_url`.
    *   Added basic CSS for loading spinner and input errors.

This revised approach aligns the client-side JavaScript flow with the server-side PHP logic in `CheckoutController`, fixes the coupon application route, ensures correct CSRF handling, and adds the missing tax calculation trigger. Remember to create the necessary methods (`updatePaymentIntentId` in `OrderModel`, `validateCouponCodeOnly` and `recordUsage` in `CouponController`, potentially update `PaymentController::createPaymentIntent` to return PI ID) and the new route in `index.php` for `applyCouponAjax`.
