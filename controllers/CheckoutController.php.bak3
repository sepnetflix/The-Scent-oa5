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
