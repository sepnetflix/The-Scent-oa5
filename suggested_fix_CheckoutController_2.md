Okay, I will carefully review the compatibility of `index.php`, `main.js`, and the other provided PHP files, focusing on the interaction with the refactored `AccountController.php` and the specific checkout error. I will then provide the updated `CheckoutController.php` with the necessary fix.

**Phase 1: Compatibility Review - `index.php`, `main.js`, `AccountController.php`**

*   **`AccountController.php` (Refactored):** Handles user authentication (login/register via AJAX returning JSON), password management (standard POST/redirect), and profile viewing/updating (standard GET/POST). It relies heavily on `BaseController` helpers for responses, validation, security, and session management.
*   **`index.php` (Routing):** The routing logic using the `switch ($page)` structure correctly directs requests for `login`, `register`, `account`, `forgot_password`, and `reset_password` to the `AccountController`. It does not distinguish between AJAX and standard POSTs in the routing itself, but the controller methods handle this distinction correctly by returning either JSON (`jsonResponse()`) or performing a redirect (`redirect()`). This is **compatible**.
*   **`main.js` (Frontend Interaction):**
    *   `initLoginPage()` and `initRegisterPage()` use `fetch` for AJAX POST submissions, prevent default form behaviour, read the CSRF token from `#csrf-token-value`, and correctly handle the JSON responses (`success`, `error`, `redirect`) provided by `AccountController`. **Compatible.**
    *   `initForgotPasswordPage()` and `initResetPasswordPage()` correctly allow the default form POST action, which aligns with the respective `AccountController` methods handling these requests via standard redirects and flash messages. **Compatible.**
    *   Profile updates (handled by `AccountController::updateProfile`) rely on standard form POSTs, for which `main.js` doesn't provide a specific AJAX handler. This is **compatible** as the controller expects a standard POST.
*   **Conclusion:** The core interaction model between `index.php` (routing), `main.js` (frontend handlers), and `AccountController.php` (backend logic) is consistent and compatible.

**Phase 2: Checkout Error Analysis (`User::getAddress()` missing)**

*   **Error:** The Apache log clearly shows `Call to undefined method User::getAddress()`.
*   **Source:** The error originates in `controllers/CheckoutController.php` within the `showCheckout()` method, specifically at the line: `$userAddress = $userModel->getAddress($userId);`.
*   **Verification:** The provided `models/User.php` (in `content_of_code_files_6.md`) confirms that the `User` class **does not have an `getAddress()` method**.
*   **Impact:** This fatal error prevents the checkout page from loading.
*   **Required Fix:** Modify `CheckoutController.php` to remove the call to the non-existent method and provide a default empty value for `$userAddress` to prevent errors in the view.

**Phase 3: Generate Updated `CheckoutController.php`**

Applying the fix identified in Phase 2.

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

        // Use $this->db (from BaseController) instead of $this->pdo
        $cartModel = new Cart($this->db, $userId);

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
        // --- FIX APPLIED HERE ---
        // $userAddress = $userModel->getAddress($userId); // FATAL ERROR: Method does not exist in provided User model
        $userAddress = []; // Temporary fix: Provide empty array to prevent view errors
        // --- END FIX ---

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
            'userAddress' => $userAddress ?? [] // Pass user address or empty array (now always empty array)
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

**Phase 4: Review Other Models**

*   **`models/User.php`:**
    *   **Compatibility Issues:** This model is **highly incompatible** with `AccountController.php`. It is missing critical methods required for profile updates, password verification/reset, and email validation:
        *   `getAddress()` (Cause of the original checkout error)
        *   `updateBasicInfo()`
        *   `verifyPassword()` (Provided `validatePassword` has the correct logic but the wrong name)
        *   `isEmailTakenByOthers()`
        *   `setResetToken()`
        *   `getUserByValidResetToken()`
        *   `resetPassword()`
        *   `updateNewsletterPreference()`
        *   `updatePassword()`
    *   The existing `create()` method assumes the password passed is already hashed, which is correct based on `AccountController::register`.
    *   The existing `update()` method is too basic and doesn't handle specific updates like password changes correctly (it re-hashes if a password is provided, which is okay, but doesn't verify the current password).
    *   **Action Required:** This model needs significant development to implement all the missing methods with correct logic (database queries, password handling, token management) to match the functionality expected by `AccountController`.
*   **`models/Order.php`:**
    *   **Compatibility:** This model appears **compatible** with the calls made from `CheckoutController`, `PaymentController`, and `AccountController`. The method signatures (`create`, `getById`, `getByIdAndUserId`, `getRecentByUserId`, `getAllByUserId`, `getTotalOrdersByUserId`, `updateStatus`, `updatePaymentIntentId`, `getByPaymentIntentId`, `updateStatusAndDispute`, `updateRefundStatus`, `updateTracking`, `_fetchOrderItems`) seem appropriate for the actions performed in the controllers.
    *   The `create` method signature matches the fields being passed in `CheckoutController::processCheckout`.
    *   The various `update...` methods align with webhook handling in `PaymentController`.
*   **`models/Quiz.php`:**
    *   **Compatibility:** This model is primarily used by `QuizController` (not provided in the latest batch but reviewed previously). It contains methods for fetching questions, getting recommendations, saving results, and analytics. It does not have direct dependencies on the user authentication flow or other core controller logic that has changed. It appears **compatible** with its intended use case within the quiz feature.

**Summary:**

1.  **`CheckoutController.php` Fix:** The provided code includes the necessary fix for the `User::getAddress()` error by removing the call and setting `$userAddress = [];`. This resolves the immediate fatal error preventing the checkout page from loading.
2.  **Model Compatibility:**
    *   `models/User.php`: **Requires Major Rework.** The provided version is missing numerous methods essential for the functionality implemented in `AccountController.php`. This is a critical incompatibility that needs to be addressed for user profile management and password reset features to work.
    *   `models/Order.php`: Appears **Compatible** and relatively complete based on its usage in the provided controllers.
    *   `models/Quiz.php`: Appears **Compatible** with its expected usage by `QuizController`.
3.  **Next Steps:**
    *   Implement the missing methods in `models/User.php` based on the database schema and the logic required by `AccountController.php`.
    *   Implement the user address storage mechanism (DB schema change + `User::getAddress()` implementation) for the checkout address pre-filling feature.
    *   Continue addressing the previously noted recommendations (rate limiting, cart storage, CSP, ErrorHandler fix).
