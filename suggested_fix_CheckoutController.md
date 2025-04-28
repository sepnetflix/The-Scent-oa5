<think>
**Plan:**

1.  **Identify the New Error:** Analyze the latest Apache error log entries (`apache-error.log`).
2.  **Pinpoint the Error Location:** Determine the exact file and line number where the error originates based on the log message.
3.  **Understand the Error:** Decipher the error message (`Undefined property: CheckoutController::$pdo` and `Cart::__construct(): Argument #1 ($pdo) must be of type PDO, null given`). Note that the first is a Warning, and the second is a fatal TypeError. Understand the sequence: the warning happens first, then the fatal error occurs because the warning indicates the variable needed for the next step is missing/wrong.
4.  **Trace the Code Path:** Follow the request flow (`GET /index.php?page=checkout` *after* successful login).
    *   `index.php` routes to `CheckoutController`.
    *   The `CheckoutController` constructor (`__construct`) runs.
    *   The `CheckoutController::showCheckout` method runs.
    *   Inside `showCheckout`, the line `$cartModel = new Cart($this->pdo, $userId);` is executed (around line 36 according to the error trace).
5.  **Analyze the Cause:**
    *   The warning `Undefined property: CheckoutController::$pdo` indicates that `$this->pdo` is being accessed within `CheckoutController` but it hasn't been defined as a property *of that class*. Controllers should be using `$this->db` which is inherited from `BaseController`.
    *   The fatal error `Cart::__construct(): Argument #1 ($pdo) must be of type PDO, null given` confirms the issue. When `new Cart($this->pdo, $userId)` is called, `$this->pdo` doesn't exist in `CheckoutController`, so `null` is passed to the `Cart` constructor, violating its type hint (`PDO $pdo`).
6.  **Identify the Regression:** Review the previous change to `controllers/CheckoutController.php`. Was `$this->pdo` introduced accidentally, or was a necessary property/initialization missed? Check the `CheckoutController` constructor and method implementations provided in the previous correct version.
    *   Looking back at the *correctly generated* `CheckoutController.php` from the previous step, it *does not* have a `$pdo` property. It relies entirely on `$this->db` inherited from `BaseController`.
    *   The error must be occurring because the code being executed *on the server* is trying to use `$this->pdo` instead of `$this->db` when instantiating the `Cart` model within the `CheckoutController::showCheckout` method.
7.  **Formulate the Fix:** Modify `controllers/CheckoutController.php` where `new Cart(...)` is called. Change `$this->pdo` to `$this->db`. Review the rest of `CheckoutController` for any other accidental uses of `$this->pdo`.
8.  **Generate Updated File:** Provide the complete, corrected version of `controllers/CheckoutController.php`.
9.  **Review and Confirm:** Compare the newly generated file with the version *that was intended* in the previous step to ensure only the `$this->pdo` to `$this->db` correction is made.

**Execution based on plan:**

*   Locate the instantiation of `Cart` within `CheckoutController::showCheckout`.
*   Change `new Cart($this->pdo, $userId)` to `new Cart($this->db, $userId)`.
*   Scan the rest of `CheckoutController` for `$this->pdo` and replace with `$this->db` if found (none likely based on previous version).
</think>

You are absolutely right to push for careful review. My apologies again, it seems an incorrect property name was used when updating `CheckoutController.php` previously. Let's fix that specific error.

**Error Analysis:**

*   **Log Entry 1 (Warning):** `[Mon Apr 28 14:13:29.691780 2025] ... Undefined property: CheckoutController::$pdo in /cdrom/project/The-Scent-oa5/controllers/CheckoutController.php on line 36`
*   **Log Entry 2 (Fatal Error):** `[Mon Apr 28 14:13:29.692148 2025] ... TypeError: Cart::__construct(): Argument #1 ($pdo) must be of type PDO, null given, called in /cdrom/project/The-Scent-oa5/controllers/CheckoutController.php on line 36`
*   **File & Line:** `controllers/CheckoutController.php`, line 36.
*   **Cause:** The code on line 36 of `CheckoutController.php` is trying to access a property named `$pdo` within the `CheckoutController` instance (`$this->pdo`). However, `CheckoutController` inherits from `BaseController`, and the established property name for the database connection in `BaseController` is `$db`. Therefore, `$this->pdo` is undefined (hence the warning), and `null` is passed to the `Cart` constructor, causing the fatal `TypeError` because the `Cart` constructor explicitly requires a `PDO` object.

**Tracing the Regression:**

This error was likely introduced during the generation of the *updated* `CheckoutController.php` in a previous step (the one intended to fix the AJAX coupon flow). I mistakenly used `$this->pdo` instead of the correct inherited property `$this->db` when instantiating the `Cart` model inside the `showCheckout` method.

**Required Fix:**

Modify `controllers/CheckoutController.php` on line 36:

*   **Change:**
    ```php
    $cartModel = new Cart($this->pdo, $userId);
    ```
*   **To:**
    ```php
    $cartModel = new Cart($this->db, $userId); // Use the correct inherited property $db
    ```

**Updated `controllers/CheckoutController.php`**

Here is the complete updated version with the fix applied to line 36.

```php
<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/InventoryController.php';
require_once __DIR__ . '/../controllers/TaxController.php';
require_once __DIR__ . '/../controllers/CouponController.php'; // Added for coupon validation
// EmailService included via BaseController
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

        // --- FIX APPLIED HERE ---
        // Use $this->db (from BaseController) instead of $this->pdo
        $cartModel = new Cart($this->db, $userId);
        // --- END FIX ---

        $items = $cartModel->getItems();

        if (empty($items)) {
             $this->setFlashMessage('Your cart is empty. Add some products before checking out.', 'info');
            $this->redirect('index.php?page=products'); // Use BaseController redirect
            return;
        }

        $cartItems = [];
        $subtotal = 0;
        foreach ($items as $item) {
            // Validate stock before displaying checkout
            if (!$this->productModel->isInStock($item['product_id'], $item['quantity'])) {
                $this->setFlashMessage("Item '".htmlspecialchars($item['name'])."' is out of stock. Please update your cart.", 'error');
                $this->redirect('index.php?page=cart'); // Redirect to cart to resolve
                return;
            }
            $cartItems[] = [
                'product' => $item,
                'quantity' => $item['quantity'],
                'subtotal' => ($item['price'] ?? 0) * ($item['quantity'] ?? 0) // Safer calculation
            ];
            $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 0); // Safer calculation
        }

        // Initial calculations (will be updated via JS/AJAX)
        $tax_rate_formatted = '0%';
        $tax_amount = 0;
        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $total = $subtotal + $shipping_cost + $tax_amount;

        $userModel = new User($this->db); // Use $this->db
        $userAddress = $userModel->getAddress($userId); // Assuming this method exists

        // Prepare data for the view
        $csrfToken = $this->getCsrfToken();
        $bodyClass = 'page-checkout';
        $pageTitle = 'Checkout - The Scent';

        // Use renderView helper
        echo $this->renderView('checkout', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'tax_rate_formatted' => $tax_rate_formatted,
            'tax_amount' => $tax_amount,
            'shipping_cost' => $shipping_cost,
            'total' => $total,
            'csrfToken' => $csrfToken,
            'bodyClass' => $bodyClass,
            'pageTitle' => $pageTitle,
            'userAddress' => $userAddress ?? [] // Pass user address or empty array
        ]);
    }


    public function calculateTax() {
        $this->requireLogin(true); // Indicate AJAX request for JSON error response

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // Use BaseController validation helper
        $country = $this->validateInput($data['country'] ?? null, 'string');
        $state = $this->validateInput($data['state'] ?? null, 'string');

        if (empty($country)) {
           return $this->jsonResponse(['success' => false, 'error' => 'Country is required'], 400);
        }

        $subtotal = $this->calculateCartSubtotal();
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
         if (!$userId) return 0.0;

         $cartModel = new Cart($this->db, $userId); // Use $this->db
         $items = $cartModel->getItems();
         $subtotal = 0.0;
         foreach ($items as $item) {
             $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 0); // Safer calculation
         }
         return (float)$subtotal;
    }

    public function processCheckout() {
        $this->validateRateLimit('checkout_submit');
        $this->requireLogin(true); // Indicate AJAX request
        $this->validateCSRF();

        $userId = $this->getUserId();
        $cartModel = new Cart($this->db, $userId); // Use $this->db
        $items = $cartModel->getItems();

        if (empty($items)) {
             return $this->jsonResponse(['success' => false, 'error' => 'Your cart is empty.'], 400);
        }

        $cartItemsForOrder = [];
        $subtotal = 0.0;
        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? 0;
            if (!$productId || $quantity <= 0) continue; // Skip invalid items

            $cartItemsForOrder[$productId] = $quantity;
            $subtotal += ($item['price'] ?? 0) * $quantity;
        }

        // Validate required POST fields from AJAX
        $requiredFields = [
            'shipping_name', 'shipping_email', 'shipping_address', 'shipping_city',
            'shipping_state', 'shipping_zip', 'shipping_country'
        ];
        $missingFields = [];
        $postData = [];
        foreach ($requiredFields as $field) {
            $value = $_POST[$field] ?? '';
            if (empty(trim($value))) { // Check if empty after trimming
                $missingFields[] = ucwords(str_replace('_', ' ', $field));
            } else {
                 $type = (strpos($field, 'email') !== false) ? 'email' : 'string';
                 $validatedValue = $this->validateInput($value, $type); // Use BaseController helper
                 if ($validatedValue === false) {
                     $missingFields[] = ucwords(str_replace('_', ' ', $field)) . " (Invalid)";
                 } else {
                     $postData[$field] = $validatedValue;
                 }
            }
        }

        if (!empty($missingFields)) {
             return $this->jsonResponse([
                 'success' => false,
                 'error' => 'Please fill in all required shipping fields correctly: ' . implode(', ', $missingFields) . '.'
             ], 400);
        }
        $orderNotes = $this->validateInput($_POST['order_notes'] ?? null, 'string', ['max' => 1000]); // Optional notes


        // --- Coupon Handling ---
        $couponCode = $this->validateInput($_POST['applied_coupon_code'] ?? null, 'string');
        $coupon = null;
        $discountAmount = 0.0;
        if ($couponCode) {
            $couponValidationResult = $this->couponController->validateCouponCodeOnly($couponCode, $subtotal); // Removed userId check here, re-check during usage recording if needed
            if ($couponValidationResult['valid']) {
                 $coupon = $couponValidationResult['coupon'];
                 $discountAmount = $this->couponController->calculateDiscount($coupon, $subtotal);
            } else {
                 error_log("Checkout Warning: Coupon '{$couponCode}' was invalid during final checkout for user {$userId}. Message: " . ($couponValidationResult['message'] ?? 'N/A'));
                 $couponCode = null; // Clear invalid code
            }
        }
        // --- End Coupon Handling ---


        try {
            $this->beginTransaction();

            $stockErrors = $this->validateCartStock($cartItemsForOrder); // Use internal helper
            if (!empty($stockErrors)) {
                $this->rollback();
                 return $this->jsonResponse([
                     'success' => false,
                     'error' => 'Some items went out of stock: ' . implode(', ', $stockErrors) . '. Please review your cart.'
                 ], 409);
            }

            // Calculate final totals including discount
            $subtotalAfterDiscount = $subtotal - $discountAmount;
            $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
            $tax_amount = $this->taxController->calculateTax(
                $subtotalAfterDiscount,
                $postData['shipping_country'],
                $postData['shipping_state']
            );
            $total = $subtotalAfterDiscount + $shipping_cost + $tax_amount;
            $total = max(0, $total); // Ensure total is not negative

            // --- Create Order ---
            $orderData = [
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'coupon_code' => $coupon ? $coupon['code'] : null,
                'coupon_id' => $coupon ? $coupon['id'] : null,
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
                'status' => 'pending_payment',
                'payment_status' => 'pending', // Add payment_status
                'order_notes' => $orderNotes, // Add order notes
                'payment_intent_id' => null
            ];
            $orderId = $this->orderModel->create($orderData); // Assumes OrderModel handles these fields
            if (!$orderId) throw new Exception("Failed to create order record.");

            // --- Create Order Items & Update Inventory ---
            $itemStmt = $this->db->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($cartItemsForOrder as $productId => $quantity) {
                $product = $this->productModel->getById($productId);
                if ($product && isset($product['price'])) {
                    $itemStmt->execute([$orderId, $productId, $quantity, $product['price']]);
                    // Use correct InventoryController method signature - Assume it now takes PDO or is constructed with it
                    $inventoryController = new InventoryController($this->db); // Instantiate if not already property
                    if (!$inventoryController->updateStock($productId, -$quantity, 'sale', $orderId)) { // Pass type and referenceId
                        throw new Exception("Failed to update inventory for product ID {$productId}");
                    }
                } else {
                    throw new Exception("Product ID {$productId} not found or price missing during order item creation.");
                }
            }

            // --- Create Payment Intent ---
            $paymentResult = $this->paymentController->createPaymentIntent($total, 'usd', $orderId, $postData['shipping_email']);
            if (!$paymentResult['success'] || empty($paymentResult['client_secret']) || empty($paymentResult['payment_intent_id'])) {
                $this->orderModel->updateStatus($orderId, 'failed');
                throw new Exception($paymentResult['error'] ?? 'Could not initiate payment.');
            }
            $clientSecret = $paymentResult['client_secret'];
            $paymentIntentId = $paymentResult['payment_intent_id'];

            // --- Update Order with Payment Intent ID ---
            if (!$this->orderModel->updatePaymentIntentId($orderId, $paymentIntentId)) { // Use correct OrderModel method
                 throw new Exception("Failed to link Payment Intent ID {$paymentIntentId} to Order ID {$orderId}.");
            }

            // --- Apply Coupon Usage (if applicable) ---
            if ($coupon) {
                 // Re-check user usage limit just before recording (within transaction)
                 if ($this->couponController->hasUserUsedCoupon($coupon['id'], $userId)) {
                      error_log("Checkout Critical: User {$userId} attempted to reuse coupon {$coupon['id']} during final checkout for order {$orderId}.");
                      throw new Exception("Coupon {$coupon['code']} has already been used."); // Fail transaction
                 }
                 // Record usage
                 if (!$this->couponController->recordUsage($coupon['id'], $orderId, $userId, $discountAmount)) {
                      error_log("Warning: Failed to record usage for coupon ID {$coupon['id']} on order ID {$orderId}.");
                      // Decide if this should be fatal or just logged (non-fatal for now)
                 }
            }

            $this->commit();

            $this->logAuditTrail('order_pending_payment', $userId, [
                'order_id' => $orderId, 'total_amount' => $total, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
            ]);

            // --- Return Client Secret to Frontend ---
            return $this->jsonResponse([
                'success' => true,
                'orderId' => $orderId,
                'clientSecret' => $clientSecret
            ]);

        } catch (Exception $e) {
            $this->rollback();
            error_log("Checkout processing error: User {$userId} - " . $e->getMessage());
            // Provide specific error message if it's safe, otherwise generic
            $errorMessage = ($e instanceof PDOException) ? 'A database error occurred.' : $e->getMessage();
            return $this->jsonResponse([
                'success' => false,
                'error' => $errorMessage
            ], 500);
        }
    }

    // --- Method to Handle AJAX Coupon Application ---
    public function applyCouponAjax() {
         $this->requireLogin(true); // Indicate AJAX
         $this->validateCSRF();

         $json = file_get_contents('php://input');
         $data = json_decode($json, true);

         $code = $this->validateInput($data['code'] ?? null, 'string');
         $currentSubtotal = $this->validateInput($data['subtotal'] ?? null, 'float');
         $userId = $this->getUserId();

         if (!$code || $currentSubtotal === false || $currentSubtotal < 0) {
             return $this->jsonResponse(['success' => false, 'message' => 'Invalid coupon code or subtotal.'], 400);
         }

         // Use CouponController to validate code only first
         $validationResult = $this->couponController->validateCouponCodeOnly($code, $currentSubtotal);

         if (!$validationResult['valid']) {
             return $this->jsonResponse(['success' => false, 'message' => $validationResult['message']]);
         }

         $coupon = $validationResult['coupon'];

         // Now check user-specific usage
         if ($this->couponController->hasUserUsedCoupon($coupon['id'], $userId)) {
              return $this->jsonResponse(['success' => false, 'message' => 'You have already used this coupon.']);
         }

         // If valid and not used by user, calculate discount and new totals
         $discountAmount = $this->couponController->calculateDiscount($coupon, $currentSubtotal);

         // Recalculate totals for the response accurately (including tax)
         $subtotalAfterDiscount = $currentSubtotal - $discountAmount;
         $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;

         // We need shipping address to calculate tax - cannot reliably do it here without it.
         // Return discount, let client update display. Tax will be final on processCheckout.
         $newTotalEstimate = $subtotalAfterDiscount + $shipping_cost; // Excludes tax
         $newTotalEstimate = max(0, $newTotalEstimate);

         return $this->jsonResponse([
             'success' => true,
             'message' => 'Coupon applied successfully!',
             'coupon_code' => $coupon['code'],
             'discount_amount' => number_format($discountAmount, 2),
             // 'new_tax_amount' => ... // Can't calculate reliably here
             'new_total_estimate' => number_format($newTotalEstimate, 2) // Send estimate excluding tax
         ]);
    }

    public function showOrderConfirmation() {
         $this->requireLogin();
         $userId = $this->getUserId();

         // Use the order ID stored in the session *after successful payment webhook*
         if (!isset($_SESSION['last_order_id'])) {
             $this->setFlashMessage('Could not find recent order details. Payment may still be processing.', 'warning');
             $this->redirect('index.php?page=account&action=orders');
             return;
         }

         $lastOrderId = filter_var($_SESSION['last_order_id'], FILTER_VALIDATE_INT);
          if (!$lastOrderId) {
              unset($_SESSION['last_order_id']);
              $this->setFlashMessage('Invalid order identifier found.', 'error');
              $this->redirect('index.php?page=account&action=orders');
              return;
          }


         // Fetch the specific order, ensuring it belongs to the current user
         $order = $this->orderModel->getByIdAndUserId($lastOrderId, $userId); // Assumes this method exists and fetches items

         if (!$order) {
             unset($_SESSION['last_order_id']);
             $this->setFlashMessage('Order details not found or access denied.', 'error');
             $this->redirect('index.php?page=account&action=orders');
             return;
         }

         // Check status (must be post-payment)
         if (!in_array($order['status'], ['paid', 'processing', 'shipped', 'delivered'])) {
             unset($_SESSION['last_order_id']);
             $this->setFlashMessage('Payment for order #'.str_pad($order['id'], 6, '0', STR_PAD_LEFT).' is pending or failed.', 'warning');
             $this->redirect('index.php?page=account&action=orders');
             return;
         }

         // Clear the session variable after successfully retrieving and validating the order
         unset($_SESSION['last_order_id']);

         // Prepare data for the view
         $csrfToken = $this->getCsrfToken();
         $bodyClass = 'page-order-confirmation';
         $pageTitle = 'Order Confirmation - The Scent';

         // Use renderView helper
         echo $this->renderView('order_confirmation', [
             'order' => $order,
             'csrfToken' => $csrfToken,
             'bodyClass' => $bodyClass,
             'pageTitle' => $pageTitle
             // User data is automatically added by renderView if needed by layout
         ]);
     }

    // --- Admin Methods ---
    public function updateOrderStatus($orderId, $status, $trackingInfo = null) {
         $this->requireAdmin(true); // Indicate AJAX
         $this->validateCSRF();

         $orderId = $this->validateInput($orderId, 'int');
         $status = $this->validateInput($status, 'string'); // Basic validation

         if (!$orderId || !$status) {
             return $this->jsonResponse(['success' => false, 'error' => 'Invalid input.'], 400);
         }

         $order = $this->orderModel->getById($orderId); // Fetch by ID for admin
         if (!$order) {
            return $this->jsonResponse(['success' => false, 'error' => 'Order not found'], 404);
         }

         // --- Add logic to check allowed status transitions ---
          // Define allowed transitions based on your workflow
         $allowedTransitions = [
             'pending_payment' => ['paid', 'cancelled', 'failed'],
             'paid' => ['processing', 'cancelled', 'refunded'],
             'processing' => ['shipped', 'cancelled', 'refunded'],
             'shipped' => ['delivered', 'refunded'], // Consider returns separate?
             'delivered' => ['refunded', 'completed'], // Add completed?
             'payment_failed' => [], // Or perhaps 'pending_payment' to allow retry?
             'cancelled' => [],
             'refunded' => [],
             'disputed' => ['refunded'],
             'completed' => [], // Terminal state
         ];

         if (!isset($allowedTransitions[$order['status']]) || !in_array($status, $allowedTransitions[$order['status']])) {
              return $this->jsonResponse(['success' => false, 'error' => "Invalid status transition from '{$order['status']}' to '{$status}'."], 400);
         }
         // --- End Status Transition Check ---


         try {
             $this->beginTransaction();

             // Use OrderModel update method
             $updated = $this->orderModel->updateStatus($orderId, $status);
             if (!$updated) {
                 // Re-check if status is already set to prevent false failure
                 $currentOrder = $this->orderModel->getById($orderId);
                 if (!$currentOrder || $currentOrder['status'] !== $status) {
                     throw new Exception("Failed to update order status in DB.");
                 }
             }

             // Handle tracking info and email notification for 'shipped' status
             if ($status === 'shipped' && $trackingInfo && !empty($trackingInfo['number'])) {
                 $trackingNumber = $this->validateInput($trackingInfo['number'], 'string', ['max' => 100]);
                 $carrier = $this->validateInput($trackingInfo['carrier'] ?? null, 'string', ['max' => 100]);

                 if ($trackingNumber) {
                      $trackingUpdated = $this->orderModel->updateTracking(
                          $orderId,
                          $trackingNumber,
                          $carrier
                      );

                      if ($trackingUpdated) {
                          $userModel = new User($this->db);
                          $user = $userModel->getById($order['user_id']);
                          if ($user && $this->emailService && method_exists($this->emailService, 'sendShippingUpdate')) {
                               $this->emailService->sendShippingUpdate(
                                  $order, // Pass original order data
                                  $user,
                                  $trackingNumber,
                                  $carrier ?? ''
                              );
                          } elseif (!$user) {
                               error_log("Could not find user {$order['user_id']} to send shipping update for order {$orderId}");
                          } else {
                               error_log("EmailService or sendShippingUpdate method not available for order {$orderId}");
                          }
                      } else {
                          error_log("Failed to update tracking info for order {$orderId}");
                          // Decide if this should be a fatal error for the transaction? Non-fatal for now.
                      }
                 }
             }

             // --- TODO: Add logic for other status changes (e.g., refund trigger, restock on cancel/refund) ---
             if ($status === 'cancelled' || $status === 'refunded') {
                  error_log("Order {$orderId} status changed to {$status}. Add refund/restock logic here.");
                  // Example: Trigger refund (if refunded status set by admin)
                  // if ($status === 'refunded' && $order['payment_intent_id']) {
                  //     $refundSuccess = $this->paymentController->createRefund($order['payment_intent_id'], $order['total_amount']);
                  //     if (!$refundSuccess) error_log("Failed to automatically process refund for order {$orderId}");
                  // }
                   // Example: Restore stock
                  // foreach ($order['items'] ?? [] as $item) { // Requires getById to fetch items here
                  //     $inventoryController = new InventoryController($this->db);
                  //     $inventoryController->updateStock($item['product_id'], $item['quantity'], 'cancellation/refund', $orderId);
                  // }
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
            return $this->jsonResponse(['success' => false, 'error' => 'Failed to update order status.'], 500);
         }
    }

    // --- Helper Methods ---
     private function validateCartStock(array $cartItems): array { // Added type hint
         $errors = [];
         // $cartItems is now expected as product_id => quantity map
         if (empty($cartItems)) {
             return ['Cart is empty']; // Should not happen if checked before calling
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
<think>
**Plan:**

1.  **Identify the Core Task:** Compare the newly generated `controllers/CheckoutController.php` with the original version (`controllers/CheckoutController.php.bak-old` from the diff command, which corresponds to the version in `content_of_code_files_4.md`). Confirm that the required fix (changing `$this->pdo` to `$this->db` in `showCheckout`) was applied correctly. Verify that other changes related to coupon handling, checkout processing, AJAX responses, and method integration were implemented correctly and that no original essential features were omitted.
2.  **Analyze the Diff Output:** Systematically review each change block (`---`, `+++`, context lines).
3.  **Verify `$this->pdo` Fix:** Locate the line where `Cart` model is instantiated within `showCheckout`. Confirm `- $cartModel = new Cart($this->pdo, $userId);` was removed and `+ $cartModel = new Cart($this->db, $userId);` was added. Check other parts of the file for any remaining `$this->pdo` usages (there should be none if `BaseController` is used correctly).
4.  **Analyze Other Changes:**
    *   **Dependencies:** Check `require_once` statements (addition of `CouponController`).
    *   **Properties:** Check added properties (`CouponController`) and removal of inherited `EmailService`. Check type hints.
    *   **Constructor:** Verify instantiation of new controllers (`CouponController`, `PaymentController` with `$pdo`).
    *   **`showCheckout`:** Verify `$this->db` usage for `User` model, use of `getCsrfToken`, use of `renderView`.
    *   **`calculateTax`:** Verify reading JSON payload, use of BaseController helpers (`requireLogin(true)`, `validateInput`, `jsonResponse`).
    *   **`calculateCartSubtotal`:** Verify `$this->db` usage, safer calculations.
    *   **`processCheckout`:**
        *   Verify method signature, rate limit/login/CSRF checks.
        *   Verify `$this->db` usage for `Cart` model.
        *   Verify safer item iteration/calculation.
        *   Verify validation logic uses `$this->validateInput`.
        *   Verify **Coupon Handling block** was added correctly (validation, discount calculation).
        *   Verify `$this->db` usage within the transaction (for items, inventory).
        *   Verify **Order Data creation** includes new fields (coupon details, payment status, notes).
        *   Verify call to `InventoryController::updateStock` signature.
        *   Verify call to `PaymentController::createPaymentIntent`.
        *   Verify call to `OrderModel::updatePaymentIntentId`.
        *   Verify **Coupon Usage Recording block** was added (including re-check for usage).
        *   Verify `jsonResponse` usage on success and error.
    *   **`applyCouponAjax` Method:** Verify this **new method** was added correctly, including login/CSRF checks, JSON handling, calls to `CouponController`, and `jsonResponse`.
    *   **`showOrderConfirmation`:** Verify checks for `$_SESSION['last_order_id']`, validation of ID, call to `OrderModel::getByIdAndUserId`, **status check** (`paid`, `processing`, etc.), clearing session key, use of `renderView`.
    *   **`updateOrderStatus`:** Verify admin/CSRF checks, **status transition logic**, calls to `OrderModel::updateStatus`/`updateTracking`, calls to `EmailService`, logging, transaction handling, `jsonResponse`.
    *   **`validateCartStock`:** Verify the logic now expects the `product_id => quantity` map.
5.  **Omission Check:** Ensure all original public methods (`showCheckout`, `calculateTax`, `processCheckout`, `showOrderConfirmation`, `updateOrderStatus`, `validateCartStock`) are still present. Check if any core logic within these methods was accidentally removed during refactoring.
6.  **Synthesize Findings:** Formulate a conclusion based on the comparison, explicitly stating whether the `$this->pdo` fix is correct and whether other changes were implemented correctly without losing functionality.

**Execution based on plan:**

*   **`$this->pdo` Fix:** Diff confirms `- $cartModel = new Cart($this->pdo, $userId);` replaced by `+ $cartModel = new Cart($this->db, $userId);` in `showCheckout`. A similar change (`new User($this->db)`) is also confirmed. No other `$this->pdo` usages found. **Fix confirmed.**
*   **Dependencies & Properties:** `CouponController` added. Type hints added/corrected. `EmailService` property removed (correct, as inherited). **OK.**
*   **Constructor:** Instantiates added `CouponController` and passes `$pdo` to `PaymentController`. **OK.**
*   **`showCheckout`:** Uses `$this->db`, `getCsrfToken`, `renderView`. Stock check logic retained. **OK.**
*   **`calculateTax`:** Refactored to handle AJAX/JSON, use helpers. **OK.**
*   **`calculateCartSubtotal`:** Uses `$this->db`, safer calculations. **OK.**
*   **`processCheckout`:**
    *   Checks/validation use helpers. **OK.**
    *   Coupon handling block added. **OK.**
    *   Order Data includes new fields (`coupon_code`, `coupon_id`, `payment_status`, `order_notes`). **OK.**
    *   Calls to InventoryController, PaymentController, OrderModel use expected (updated) signatures. **OK.**
    *   Coupon usage recording block added (including re-check). **OK.**
    *   Uses `jsonResponse`. **OK.**
*   **`applyCouponAjax` Method:** New method added with correct logic structure (login/CSRF check, JSON handling, calls CouponController, returns JSON). **OK.**
*   **`showOrderConfirmation`:** Login check, Session ID validation, `OrderModel` call, **Status Check added**, `renderView` usage. **OK.**
*   **`updateOrderStatus`:** Admin/CSRF check, **Status Transition Check added**, model/service calls updated, logging/transaction handling retained, `jsonResponse`. **OK.**
*   **`validateCartStock`:** Logic updated for expected input format. **OK.**
*   **Omission Check:** All original public methods are present. The logic refinements enhance rather than remove functionality (e.g., adding coupon handling, status checks). The change from rendering views directly (`require_once`) to using `$this->renderView` is a correct pattern change. The change from redirecting via `header()` to `$this->redirect()` is also correct.

**Final Review:** The diff confirms the primary fix (`$this->pdo` to `$this->db`) is correctly applied. The extensive changes reflect the integration of coupon logic, the refined AJAX checkout flow, improved validation, and consistent use of BaseController helpers. All original functionalities are preserved within the updated structure.
</think>

Yes, I have performed a very careful comparison of the newly generated `controllers/CheckoutController.php` against the original version (`controllers/CheckoutController.php.bak-old`, corresponding to the one in `content_of_code_files_4.md`).

**Confirmation of Fixes and Changes:**

1.  **`$this->pdo` Fix (Primary Goal):**
    *   The critical fix on line 36 (original line number) within the `showCheckout` method, changing `new Cart($this->pdo, $userId)` to `new Cart($this->db, $userId)`, is **correctly applied**.
    *   The instantiation of the `User` model within `showCheckout` was also correctly updated to use `$this->db`.
    *   A thorough scan confirms no other instances of `$this->pdo` were incorrectly used; the code consistently uses `$this->db` inherited from `BaseController`. This resolves the `Undefined property: CheckoutController::$pdo` and the subsequent `TypeError: Cart::__construct(): Argument #1 ($pdo) must be of type PDO, null given` errors.

2.  **Integration with BaseController Helpers:**
    *   The updated code now correctly uses methods from the refactored `BaseController`:
        *   `$this->requireLogin()` and `$this->requireAdmin()` (with `$isAjaxRequest = true` where appropriate).
        *   `$this->validateInput()` for validating POST/JSON data.
        *   `$this->validateCSRF()` for CSRF checks.
        *   `$this->getCsrfToken()` for retrieving the token.
        *   `$this->jsonResponse()` for all AJAX responses.
        *   `$this->redirect()` for redirects.
        *   `$this->renderView()` for rendering the main checkout page and confirmation page.
        *   `$this->logAuditTrail()` for logging.
        *   `$this->beginTransaction()`, `$this->commit()`, `$this->rollback()`.

3.  **Checkout Flow and Coupon Logic Implementation:**
    *   **`processCheckout`:** This method was significantly refactored to handle the *initiation* of the checkout via AJAX. It now:
        *   Validates shipping details.
        *   Validates and calculates the coupon discount server-side *before* creating the order.
        *   Calculates the final total amount accurately.
        *   Creates the order record in the DB with `status = 'pending_payment'` and includes coupon/discount details.
        *   Updates inventory.
        *   Calls `PaymentController::createPaymentIntent` to get the `clientSecret`.
        *   Updates the order record with the `payment_intent_id`.
        *   Records coupon usage via `CouponController::recordUsage`.
        *   Returns the `clientSecret` and `orderId` in a JSON response for the frontend JavaScript.
    *   **`applyCouponAjax`:** This **new method** was correctly added to handle the separate AJAX request for validating and applying a coupon code dynamically on the checkout page before final submission. It interacts with `CouponController` and returns the discount amount and estimated new total.
    *   **`calculateTax`:** Correctly refactored to handle AJAX/JSON input and output.

4.  **Other Refinements:**
    *   Type hints added (`Product`, `Order`, etc.).
    *   Safer calculations using `?? 0`.
    *   Order creation includes new fields (`discount_amount`, `coupon_code`, `coupon_id`, `payment_status`, `order_notes`).
    *   `showOrderConfirmation` includes a crucial status check to ensure payment was successful before displaying the page.
    *   `updateOrderStatus` includes status transition validation logic.

**Omission Check:**

All original public methods (`showCheckout`, `calculateTax`, `processCheckout`, `showOrderConfirmation`, `updateOrderStatus`, `validateCartStock`) are present. The logic within them has been updated to match the new AJAX-driven checkout flow, integrate coupon handling, and utilize the BaseController helpers, but the core responsibilities are maintained. No features have been accidentally removed. The internal helper `calculateCartSubtotal` is also preserved.

**Conclusion:**

The updated `controllers/CheckoutController.php` correctly fixes the `$this->pdo` property error. It successfully implements the revised AJAX checkout flow, integrates the necessary coupon validation and application logic by interacting with `CouponController`, and consistently uses the helper methods from the updated `BaseController`. The changes are comprehensive and address the requirements identified previously without omitting original functionalities. This version should now function correctly within the application architecture.

---
https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%2211gLlqjm9P5onq6O3bP03zvYHMR1OL5Q6%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/11pHAnzuQujVYyhZSqtRs_-L1pupNj2Mw/view?usp=sharing, https://drive.google.com/file/d/15PpD2JKuq752Pa1KKvmsCT5gh6cbmfAO/view?usp=sharing, https://drive.google.com/file/d/17Nvr39ffZlADMFvaM0r1S3DCn8p98v3r/view?usp=sharing, https://drive.google.com/file/d/1CqhbbX1wI4FejmRaQqt_LjVj6WPPFNFJ/view?usp=sharing, https://drive.google.com/file/d/1EO8T2FZeyfp__XpbDxQwQbU8T1eOsXUw/view?usp=sharing, https://drive.google.com/file/d/1Jro_incgi67_rNrokpTMrYT5MfmT3_ZH/view?usp=sharing, https://drive.google.com/file/d/1LeGw2tBB2CMwMmutAWcrmzIyoQtV3UnU/view?usp=sharing, https://drive.google.com/file/d/1NUz2D4sugn1UnJsOXqrwmsU06WHZq3fR/view?usp=sharing, https://drive.google.com/file/d/1S3jeejXCwisQeyTKfG-7JboP93qjvH2a/view?usp=sharing, https://drive.google.com/file/d/1Uk01MipQX9kWQ5f4HZOI6TP8Fr4s9Pcw/view?usp=sharing, https://drive.google.com/file/d/1V26FOntpGfQ51xF-ciKW3tvB0V0D_Aml/view?usp=sharing, https://drive.google.com/file/d/1WvJwy5ATfaPyJu2bHDQeKw0lc5EviQI8/view?usp=sharing, https://drive.google.com/file/d/1Xl-NqEG9c10oSmgJFcnSHl7mvQI31DBT/view?usp=sharing, https://drive.google.com/file/d/1aj7OFfCI5U6wcz1-PcCeRgaegP_8vkXS/view?usp=sharing, https://drive.google.com/file/d/1imW3TDAUBPz4ncXDYOiHdItw0-n4gW0f/view?usp=sharing, https://drive.google.com/file/d/1j_lF5-CNbPuX260HuoFwKOSwMs5m2AEY/view?usp=sharing, https://drive.google.com/file/d/1oWpHoVnF11ul_k05v0HNgtNEMpMOtb4h/view?usp=sharing, https://drive.google.com/file/d/1r7fvQrgps_6Rlr5SjmiOAWN7VK5QwzY6/view?usp=sharing, https://drive.google.com/file/d/1xbWNknkK86fxP2tjD9tY-PQOnP8dvw_m/view?usp=sharing
