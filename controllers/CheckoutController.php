<?php
// controllers/CheckoutController.php (Corrected - Restored updateOrderStatus, kept checkout flow updates)

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/InventoryController.php';
require_once __DIR__ . '/../controllers/TaxController.php';
require_once __DIR__ . '/../controllers/CouponController.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/User.php';

class CheckoutController extends BaseController {
    private Product $productModel;
    private Order $orderModel;
    private InventoryController $inventoryController;
    private TaxController $taxController;
    private PaymentController $paymentController;
    private CouponController $couponController;
    // EmailService is inherited from BaseController

    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->orderModel = new Order($pdo);
        $this->inventoryController = new InventoryController($pdo);
        $this->taxController = new TaxController($pdo);
        $this->paymentController = new PaymentController($pdo);
        $this->couponController = new CouponController($pdo);
    }

    /**
     * Display the checkout page.
     * Pre-fills address if available.
     * Calculates initial totals.
     */
    public function showCheckout() {
        $this->requireLogin();
        $userId = $this->getUserId();

        $cartModel = new Cart($this->db, $userId);
        $items = $cartModel->getItems();

        if (empty($items)) {
             $this->setFlashMessage('Your cart is empty. Add some products before checking out.', 'info');
             $this->redirect('index.php?page=products');
             return;
        }

        $cartItems = [];
        $subtotal = 0.0;
        foreach ($items as $item) {
            // Validate stock before displaying checkout
            if (!$this->productModel->isInStock($item['product_id'], $item['quantity'])) {
                $this->setFlashMessage("Item '".htmlspecialchars($item['name'])."' is out of stock. Please update your cart.", 'error');
                $this->redirect('index.php?page=cart');
                return;
            }
            $price = $item['price'] ?? 0;
            $quantity = $item['quantity'] ?? 0;
            $lineSubtotal = $price * $quantity;
            $cartItems[] = [
                'product' => $item,
                'quantity' => $quantity,
                'subtotal' => $lineSubtotal
            ];
            $subtotal += $lineSubtotal;
        }

        // Initial calculations (updated by JS/AJAX)
        $tax_rate_formatted = '0%'; // Placeholder
        $tax_amount = 0.0; // Placeholder
        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $total = $subtotal + $shipping_cost + $tax_amount;

        $userModel = new User($this->db);
        $userAddress = $userModel->getAddress($userId); // Fetches address data or null

        $csrfToken = $this->getCsrfToken();
        $bodyClass = 'page-checkout';
        $pageTitle = 'Checkout - The Scent';

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
            'userAddress' => $userAddress ?? [] // Pass address data or empty array
        ]);
    }

    /**
     * AJAX endpoint to calculate tax based on country/state.
     */
    public function calculateTax() {
        $this->requireLogin(true); // AJAX request

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $country = $this->validateInput($data['country'] ?? null, 'string');
        $state = $this->validateInput($data['state'] ?? null, 'string');
        // Get subtotal AFTER potential coupon discount (important for accurate tax)
        // Client JS should pass current subtotal and discount amount, or recalculate subtotal here.
        // Let's recalculate subtotal for safety.
        $currentSubtotal = $this->calculateCartSubtotal(); // Fetch current subtotal
        // Note: This doesn't account for coupon discount applied client-side only yet.
        // Tax calculation should ideally happen server-side during processCheckout for accuracy.
        // This AJAX endpoint provides an *estimate*.

        if (empty($country)) {
           return $this->jsonResponse(['success' => false, 'error' => 'Country is required'], 400);
        }
        if ($currentSubtotal <= 0) {
             return $this->jsonResponse(['success' => false, 'error' => 'Cart is empty or invalid'], 400);
        }

        $shipping_cost = $currentSubtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST; // Based on original subtotal
        $tax_amount = $this->taxController->calculateTax($currentSubtotal, $country, $state);
        $tax_rate = $this->taxController->getTaxRate($country, $state);
        $total = $currentSubtotal + $shipping_cost + $tax_amount; // Estimate

        return $this->jsonResponse([
            'success' => true,
            'tax_rate_formatted' => $this->taxController->formatTaxRate($tax_rate),
            'tax_amount' => number_format($tax_amount, 2), // Send formatted
            'total' => number_format($total, 2) // Send formatted estimate
        ]);
    }

    // Helper to get cart subtotal for logged-in user (unchanged)
    private function calculateCartSubtotal(): float {
         $userId = $this->getUserId();
         if (!$userId) return 0.0;
         $cartModel = new Cart($this->db, $userId);
         $items = $cartModel->getItems();
         $subtotal = 0.0;
         foreach ($items as $item) { $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 0); }
         return (float)$subtotal;
    }

    /**
     * Processes the checkout form submission via AJAX.
     * Creates order, handles inventory, coupons, and initiates payment intent.
     */
    public function processCheckout() {
        $this->validateRateLimit('checkout_submit');
        $this->requireLogin(true); // AJAX request
        $this->validateCSRF();

        $userId = $this->getUserId();
        $cartModel = new Cart($this->db, $userId);
        $items = $cartModel->getItems();

        if (empty($items)) {
             return $this->jsonResponse(['success' => false, 'error' => 'Your cart is empty.'], 400);
        }

        // --- Collect Cart Details ---
        $cartItemsForOrder = []; // Store as [productId => ['quantity' => q, 'price' => p, 'name' => n]]
        $subtotal = 0.0;
        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? 0;
            $price = $item['price'] ?? 0;
            $name = $item['name'] ?? 'Unknown Product';
            if (!$productId || $quantity <= 0) continue;

            $cartItemsForOrder[$productId] = ['quantity' => $quantity, 'price' => $price, 'name' => $name];
            $subtotal += $price * $quantity;
        }

        // --- Validate Shipping Input ---
        $requiredFields = [
            'shipping_name', 'shipping_email', 'shipping_address', 'shipping_city',
            'shipping_state', 'shipping_zip', 'shipping_country'
        ];
        $missingFields = [];
        $postData = [];
        foreach ($requiredFields as $field) {
            $value = $_POST[$field] ?? '';
            if (empty(trim($value))) {
                $missingFields[] = ucwords(str_replace('_', ' ', $field));
            } else {
                 $type = (strpos($field, 'email') !== false) ? 'email' : 'string';
                 $validatedValue = $this->validateInput($value, $type);
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
                 'error' => 'Please fill required shipping fields: ' . implode(', ', $missingFields) . '.'
             ], 400);
        }
        $orderNotes = $this->validateInput($_POST['order_notes'] ?? null, 'string', ['max' => 1000]);

        // --- Validate Coupon (Again, server-side) ---
        $couponCode = $this->validateInput($_POST['applied_coupon_code'] ?? null, 'string');
        $coupon = null;
        $discountAmount = 0.0;
        if ($couponCode) {
            // Re-validate fully including user usage limit before applying
            $validationResult = $this->couponController->validateCouponCodeOnly($couponCode, $subtotal);
            if ($validationResult['valid']) {
                 $coupon = $validationResult['coupon'];
                 if ($this->couponController->hasUserUsedCoupon($coupon['id'], $userId)) {
                     error_log("Checkout Warning: User {$userId} tried applying already used coupon '{$couponCode}' during final processing.");
                     $coupon = null; // Invalidate coupon
                     $couponCode = null;
                 } else {
                     $discountAmount = $this->couponController->calculateDiscount($coupon, $subtotal);
                 }
            } else {
                 error_log("Checkout Warning: Coupon '{$couponCode}' became invalid during final checkout for user {$userId}. Message: " . ($validationResult['message'] ?? 'N/A'));
                 $couponCode = null; // Clear invalid code
                 $coupon = null;
            }
        }

        // --- Calculate Final Totals ---
        $subtotalAfterDiscount = max(0, $subtotal - $discountAmount); // Ensure non-negative
        $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $tax_amount = $this->taxController->calculateTax(
            $subtotalAfterDiscount, // Calculate tax on discounted subtotal
            $postData['shipping_country'],
            $postData['shipping_state']
        );
        $total = $subtotalAfterDiscount + $shipping_cost + $tax_amount;
        $total = max(0.50, round($total, 2)); // Ensure total is at least $0.50 for Stripe, round correctly

        // --- Start Transaction ---
        try {
            $this->beginTransaction();

            // --- Re-validate Stock within Transaction ---
            $stockErrors = $this->validateCartStock($cartItemsForOrder); // Use internal helper
            if (!empty($stockErrors)) {
                $this->rollback();
                 return $this->jsonResponse([
                     'success' => false,
                     'error' => 'Some items went out of stock: ' . implode(', ', $stockErrors) . '. Please review your cart.'
                 ], 409); // 409 Conflict
            }

            // --- Create Order Record ---
            $orderData = [
                'user_id' => $userId,
                'subtotal' => $subtotal, // Original subtotal
                'discount_amount' => $discountAmount,
                'coupon_code' => $coupon ? $coupon['code'] : null,
                'coupon_id' => $coupon ? $coupon['id'] : null,
                'shipping_cost' => $shipping_cost,
                'tax_amount' => $tax_amount,
                'total_amount' => $total, // Final calculated total
                'shipping_name' => $postData['shipping_name'],
                'shipping_email' => $postData['shipping_email'],
                'shipping_address' => $postData['shipping_address'],
                'shipping_city' => $postData['shipping_city'],
                'shipping_state' => $postData['shipping_state'],
                'shipping_zip' => $postData['shipping_zip'],
                'shipping_country' => $postData['shipping_country'],
                'status' => 'pending_payment', // Initial status
                'payment_status' => 'pending', // Initial payment status
                'order_notes' => $orderNotes,
                'payment_intent_id' => null // Will be updated later
            ];
            $orderId = $this->orderModel->create($orderData);
            if (!$orderId) throw new Exception("Failed to create order record.");

            // --- Create Order Items & Decrement Inventory ---
            $itemStmt = $this->db->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($cartItemsForOrder as $productId => $itemData) {
                // Insert order item
                $itemStmt->execute([$orderId, $productId, $itemData['quantity'], $itemData['price']]);

                // Decrement stock using InventoryController
                // Note: Type 'sale', reference is the new order ID
                if (!$this->inventoryController->updateStock($productId, -$itemData['quantity'], 'sale', $orderId)) {
                    // InventoryController::updateStock should throw an exception on failure now
                    throw new Exception("Failed to update inventory for product ID {$productId}");
                }
            }

            // --- Create Payment Intent ---
            // Pass the final calculated total
            $paymentResult = $this->paymentController->createPaymentIntent($total, 'usd', $orderId, $postData['shipping_email']);
            if (!$paymentResult['success'] || empty($paymentResult['client_secret']) || empty($paymentResult['payment_intent_id'])) {
                // Attempt to update order status to failed, but don't rollback transaction yet
                $this->orderModel->updateStatus($orderId, 'payment_failed');
                throw new Exception($paymentResult['error'] ?? 'Could not initiate payment.');
            }
            $clientSecret = $paymentResult['client_secret'];
            $paymentIntentId = $paymentResult['payment_intent_id'];

            // --- Update Order with Payment Intent ID ---
            if (!$this->orderModel->updatePaymentIntentId($orderId, $paymentIntentId)) {
                 throw new Exception("Failed to link Payment Intent ID {$paymentIntentId} to Order ID {$orderId}.");
            }

            // --- Record Coupon Usage ---
            if ($coupon) {
                 if (!$this->couponController->recordUsage($coupon['id'], $orderId, $userId, $discountAmount)) {
                      // Log failure but don't necessarily stop the checkout
                      error_log("Warning: Failed to record usage for coupon ID {$coupon['id']} on order ID {$orderId}.");
                 }
            }

            // --- Commit Transaction ---
            $this->commit();

            $this->logAuditTrail('order_pending_payment', $userId, [
                'order_id' => $orderId, 'total_amount' => $total, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
            ]);

            // --- Return Client Secret and Order ID to Frontend ---
            return $this->jsonResponse([
                'success' => true,
                'orderId' => $orderId,
                'clientSecret' => $clientSecret
            ]);

        } catch (Exception $e) {
            $this->rollback(); // Rollback on any exception during the process
            error_log("Checkout processing error: User {$userId} - " . $e->getMessage());
            $errorMessage = ($e instanceof PDOException) ? 'A database error occurred.' : $e->getMessage();
            // Return specific error if known (like stock issue), else generic
            return $this->jsonResponse([
                'success' => false,
                'error' => $errorMessage
            ], 500);
        }
    }


    /**
     * Handles AJAX request from checkout page to validate and apply a coupon.
     * Returns discount info for UI update. Final validation happens in processCheckout.
     */
    public function applyCouponAjax() {
         $this->requireLogin(true); // AJAX
         $this->validateCSRF();

         $json = file_get_contents('php://input');
         $data = json_decode($json, true);

         $code = $this->validateInput($data['code'] ?? null, 'string');
         $currentSubtotal = $this->validateInput($data['subtotal'] ?? null, 'float'); // Get subtotal from client
         $userId = $this->getUserId();

         if (!$code || $currentSubtotal === false || $currentSubtotal < 0) {
             return $this->jsonResponse(['success' => false, 'message' => 'Invalid coupon code or subtotal amount provided.'], 400);
         }

         // Use CouponController to validate code only first
         $validationResult = $this->couponController->validateCouponCodeOnly($code, $currentSubtotal);
         if (!$validationResult['valid']) {
             return $this->jsonResponse(['success' => false, 'message' => $validationResult['message']]);
         }
         $coupon = $validationResult['coupon'];

         // Check user-specific usage
         if ($this->couponController->hasUserUsedCoupon($coupon['id'], $userId)) {
              return $this->jsonResponse(['success' => false, 'message' => 'You have already used this coupon.']);
         }

         // Calculate discount
         $discountAmount = $this->couponController->calculateDiscount($coupon, $currentSubtotal);
         $subtotalAfterDiscount = max(0, $currentSubtotal - $discountAmount);
         $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
         $newTotalEstimate = $subtotalAfterDiscount + $shipping_cost; // Excludes tax

         return $this->jsonResponse([
             'success' => true,
             'message' => 'Coupon applied successfully!',
             'coupon_code' => $coupon['code'],
             'discount_amount' => number_format($discountAmount, 2),
             'new_total_estimate' => number_format($newTotalEstimate, 2) // Estimate for UI update
         ]);
    }

    /**
     * Displays the order confirmation page.
     * Relies on 'last_order_id' being set in the session by the webhook handler.
     */
    public function showOrderConfirmation() {
         $this->requireLogin();
         $userId = $this->getUserId();

         // Check if last_order_id is set in session
         if (!isset($_SESSION['last_order_id'])) {
             $this->setFlashMessage('Order confirmation details not found. View your orders below.', 'warning');
             $this->redirect('index.php?page=account&section=orders'); // Redirect to order list
             return;
         }

         $lastOrderId = filter_var($_SESSION['last_order_id'], FILTER_VALIDATE_INT);
         if (!$lastOrderId) {
             unset($_SESSION['last_order_id']); // Clear invalid ID
             $this->setFlashMessage('Invalid order identifier found.', 'error');
             $this->redirect('index.php?page=account&section=orders');
             return;
         }

         // Fetch the order, ensuring it belongs to the current user AND has items
         $order = $this->orderModel->getByIdAndUserId($lastOrderId, $userId); // Fetches items via _fetchOrderItems

         if (!$order || empty($order['items'])) { // Check if order exists and has items
             unset($_SESSION['last_order_id']); // Clear session ID
             $this->setFlashMessage('Order details not found or access denied.', 'error');
             $this->redirect('index.php?page=account&section=orders');
             return;
         }

         // Verify order status - should be 'paid' or 'processing' (or maybe shipped/delivered if slow redirect)
         // Use a set of acceptable post-payment statuses
         $acceptableStatuses = ['paid', 'processing', 'shipped', 'delivered', 'completed'];
         if (!in_array($order['status'], $acceptableStatuses)) {
             // Don't clear session ID yet, maybe webhook hasn't run?
             $this->setFlashMessage('Payment for order #'.str_pad($order['id'], 6, '0', STR_PAD_LEFT).' may still be processing or failed.', 'warning');
             $this->redirect('index.php?page=account&section=orders'); // Redirect to list
             return;
         }

         // If everything is valid, clear the session variable
         unset($_SESSION['last_order_id']);

         $csrfToken = $this->getCsrfToken();
         $bodyClass = 'page-order-confirmation';
         $pageTitle = 'Order Confirmation - The Scent';

         echo $this->renderView('order_confirmation', [
             'order' => $order, // Pass the order data (including items)
             'csrfToken' => $csrfToken,
             'bodyClass' => $bodyClass,
             'pageTitle' => $pageTitle
         ]);
     }


    // --- Admin Method (Restored) ---
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
         $allowedTransitions = [
             'pending_payment' => ['paid', 'cancelled', 'payment_failed'], // Use payment_failed
             'paid' => ['processing', 'cancelled', 'refunded'],
             'processing' => ['shipped', 'cancelled', 'refunded'],
             'shipped' => ['delivered', 'refunded'], // Consider returns separate?
             'delivered' => ['refunded', 'completed'], // Add completed?
             'payment_failed' => ['pending_payment', 'cancelled'], // Allow retry or cancel
             'cancelled' => [],
             'refunded' => [],
             'partially_refunded' => ['refunded'], // Allow full refund after partial
             'disputed' => ['refunded'], // Allow refunding after dispute
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
                          // Fetch full order details for email context
                          $fullOrder = $this->orderModel->getByIdAndUserId($orderId, $order['user_id']);

                          if ($user && $fullOrder && $this->emailService && method_exists($this->emailService, 'sendShippingUpdate')) {
                               $this->emailService->sendShippingUpdate(
                                  $fullOrder, // Pass full order data
                                  $user,
                                  $trackingNumber,
                                  $carrier ?? ''
                              );
                          } elseif (!$user) {
                               error_log("Could not find user {$order['user_id']} to send shipping update for order {$orderId}");
                          } elseif (!$fullOrder) {
                               error_log("Could not find full order details for shipping update email (Order ID: {$orderId})");
                          } else {
                               error_log("EmailService or sendShippingUpdate method not available for order {$orderId}");
                          }
                      } else {
                          error_log("Failed to update tracking info for order {$orderId}");
                      }
                 }
             }

             // TODO: Add more logic for other status changes (e.g., refund trigger, restock on cancel/refund)
             if ($status === 'cancelled' || $status === 'refunded') {
                  error_log("Order {$orderId} status changed to {$status}. Add refund/restock logic here.");
             }

             $this->commit();

             $adminUserId = $this->getUserId(); // Assumes admin is logged in
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
    // --- End Admin Method (Restored) ---


    // --- Helper Methods ---
    /**
     * Internal helper to validate stock for items in the cart.
     * Expects $cartItems as [productId => ['quantity' => q, ...]]
     */
     private function validateCartStock(array $cartItems): array {
         $errors = [];
         if (empty($cartItems)) { return ['Cart is empty']; }

         foreach ($cartItems as $productId => $itemData) {
             $product = $this->productModel->getById($productId); // Fetch product details
             if (!$product) {
                 $errors[] = "Product ID {$productId} not found.";
                 continue;
             }
             if (!$this->productModel->isInStock($productId, $itemData['quantity'])) {
                 $errors[] = htmlspecialchars($product['name'] ?? "Product ID {$productId}") . " has insufficient stock";
             }
         }
         return $errors;
     }

} // End of CheckoutController class
