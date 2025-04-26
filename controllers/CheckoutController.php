<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/InventoryController.php';
require_once __DIR__ . '/../controllers/TaxController.php';
require_once __DIR__ . '/../includes/EmailService.php';
// Potentially need Cart model if checking cart items directly
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/User.php'; // Needed for user details

class CheckoutController extends BaseController {
    // Properties remain the same...
    private $productModel;
    private $orderModel;
    private $inventoryController;
    private $taxController;
    private $paymentController;
    private $emailService;

    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->orderModel = new Order($pdo);
        $this->inventoryController = new InventoryController($pdo);
        $this->taxController = new TaxController($pdo);
        $this->paymentController = new PaymentController();
        // $this->emailService is initialized in parent
    }

    public function showCheckout() {
        // Ensure user is logged in for checkout
        $this->requireLogin(); // Use BaseController method, handles redirect/exit if not logged in

        $userId = $this->getUserId();
        $cartItems = [];
        $subtotal = 0;

        // Fetch cart items from DB for logged-in user
        $cartModel = new Cart($this->pdo, $userId);
        $items = $cartModel->getItems();

        // If cart is empty, redirect
        if (empty($items)) {
             $this->setFlashMessage('Your cart is empty. Add some products before checking out.', 'info');
            $this->redirect('products'); // Redirect to products page
            return; // Exit
        }

        foreach ($items as $item) {
            // Basic stock check before showing checkout page (optional but good UX)
            if (!$this->productModel->isInStock($item['product_id'], $item['quantity'])) {
                $this->setFlashMessage("Item '{$item['name']}' is out of stock. Please update your cart.", 'error');
                $this->redirect('cart'); // Redirect back to cart to resolve
                return;
            }
            $cartItems[] = [
                'product' => $item, // Assumes getItems() joins product data
                'quantity' => $item['quantity'],
                'subtotal' => $item['price'] * $item['quantity']
            ];
            $subtotal += $item['price'] * $item['quantity'];
        }

        // Initial calculations (Tax/Total might be updated via JS later)
        $tax_rate_formatted = '0%'; // Initial placeholder
        $tax_amount = 0; // Initial placeholder
        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $total = $subtotal + $shipping_cost + $tax_amount;

        // Fetch user address details if available (assuming User model has getAddress)
        $userModel = new User($this->pdo);
        $userAddress = $userModel->getAddress($userId); // Implement this in UserModel

        // Prepare data for the view
        $csrfToken = $this->generateCSRFToken();
        $bodyClass = 'page-checkout';
        $pageTitle = 'Checkout - The Scent';

        // Use require_once, so define variables directly
        extract([
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'tax_rate_formatted' => $tax_rate_formatted,
            'tax_amount' => $tax_amount,
            'shipping_cost' => $shipping_cost,
            'total' => $total,
            'csrfToken' => $csrfToken,
            'bodyClass' => $bodyClass,
            'pageTitle' => $pageTitle,
            'userAddress' => $userAddress // Pass user address to pre-fill form
        ]);

        // Load the view file
        require_once __DIR__ . '/../views/checkout.php';
    }


    // calculateTax remains unchanged (uses jsonResponse)
    public function calculateTax() {
        // Ensure user is logged in to calculate tax based on their potential address context
        $this->requireLogin();
        // CSRF might not be strictly needed if just calculating, but good practice if form interaction triggers it
        // $this->validateCSRF(); // Consider if this is needed based on how JS calls it

        $data = json_decode(file_get_contents('php://input'), true);
        $country = $this->validateInput($data['country'] ?? '', 'string');
        $state = $this->validateInput($data['state'] ?? '', 'string');

        if (empty($country)) {
           return $this->jsonResponse(['success' => false, 'error' => 'Country is required'], 400);
        }

        $subtotal = $this->calculateCartSubtotal(); // Recalculate based on current DB cart
        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $tax_amount = $this->taxController->calculateTax($subtotal, $country, $state);
        $tax_rate = $this->taxController->getTaxRate($country, $state); // Assuming TaxController has this
        $total = $subtotal + $shipping_cost + $tax_amount;

        return $this->jsonResponse([
            'success' => true,
            'tax_rate_formatted' => $this->taxController->formatTaxRate($tax_rate), // Assuming TaxController has this
            'tax_amount' => number_format($tax_amount, 2),
            'total' => number_format($total, 2)
        ]);
    }

    // Helper to get cart subtotal for logged-in user
    private function calculateCartSubtotal() {
         // Recalculate subtotal based on current DB cart for accuracy
         $userId = $this->getUserId();
         if (!$userId) return 0; // Should not happen if requireLogin is used, but defensive check

         $cartModel = new Cart($this->pdo, $userId);
         $items = $cartModel->getItems();
         $subtotal = 0;
         foreach ($items as $item) {
             $subtotal += $item['price'] * $item['quantity'];
         }
         return $subtotal;
    }

    // processCheckout remains unchanged (uses jsonResponse/redirect)
    public function processCheckout() {
        $this->validateRateLimit('checkout_submit');
        $this->requireLogin(); // Ensure user is logged in
        $this->validateCSRF(); // Validate CSRF token from the form submission

        $userId = $this->getUserId();
        $cartModel = new Cart($this->pdo, $userId);
        $items = $cartModel->getItems();

        // Check if cart is empty *before* processing
        if (empty($items)) {
             $this->setFlashMessage('Your cart is empty.', 'error');
             $this->redirect('cart');
             return;
        }

        // Collect cart items for order creation and stock validation
        $cartItemsForOrder = [];
        $subtotal = 0;
        foreach ($items as $item) {
            $cartItemsForOrder[$item['product_id']] = $item['quantity']; // Use product_id as key
            $subtotal += $item['price'] * $item['quantity'];
        }

        // Validate required POST fields
        $requiredFields = [
            'shipping_name', 'shipping_email', 'shipping_address', 'shipping_city',
            'shipping_state', 'shipping_zip', 'shipping_country',
            // Add payment fields if necessary (e.g., payment_method_id from Stripe Elements)
             'payment_method_id' // Example for Stripe
        ];
        $missingFields = [];
        $postData = [];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $missingFields[] = ucwords(str_replace('_', ' ', $field));
            } else {
                 // Sanitize and validate input here before using it
                 // Example using a simple validation type map
                 $type = (strpos($field, 'email') !== false) ? 'email' : 'string';
                 $validatedValue = $this->validateInput($_POST[$field], $type);
                 if ($validatedValue === false) {
                     $missingFields[] = ucwords(str_replace('_', ' ', $field)) . " (Invalid)";
                 } else {
                     $postData[$field] = $validatedValue;
                 }
            }
        }

        if (!empty($missingFields)) {
            $this->setFlashMessage('Please fill in all required fields correctly: ' . implode(', ', $missingFields) . '.', 'error');
            // Consider returning JSON if the checkout form submits via AJAX
            // For standard POST, redirect back
             $this->redirect('checkout');
            return;
        }


        try {
            $this->beginTransaction();

            // Final stock check within transaction
            $stockErrors = $this->validateCartStock($cartItemsForOrder);
            if (!empty($stockErrors)) {
                // Rollback immediately if stock issue found
                $this->rollback();
                $this->setFlashMessage('Some items went out of stock while checking out: ' . implode(', ', $stockErrors) . '. Please review your cart.', 'error');
                // Consider JSON response if AJAX
                 $this->redirect('cart'); // Redirect to cart to resolve stock issues
                return;
            }

            // Calculate final totals
            $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
            $tax_amount = $this->taxController->calculateTax(
                $subtotal,
                $postData['shipping_country'], // Use validated data
                $postData['shipping_state']    // Use validated data
            );
            $total = $subtotal + $shipping_cost + $tax_amount;

            // Create Order
            $orderData = [
                'user_id' => $userId,
                'subtotal' => $subtotal,
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
                 'status' => 'pending_payment' // Initial status before payment attempt
            ];
            $orderId = $this->orderModel->create($orderData);
             if (!$orderId) throw new Exception("Failed to create order record.");


            // Create Order Items & Update Inventory
            $stmt = $this->pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($cartItemsForOrder as $productId => $quantity) {
                $product = $this->productModel->getById($productId); // Fetch price again for safety
                if ($product) {
                    $stmt->execute([$orderId, $productId, $quantity, $product['price']]);
                    // Update inventory
                    if (!$this->inventoryController->updateStock($productId, -$quantity, 'order', $orderId, "Order #{$orderId}")) {
                        throw new Exception("Failed to update inventory for product ID {$productId}");
                    }
                } else {
                    // Should not happen due to earlier checks, but handle defensively
                     throw new Exception("Product ID {$productId} not found during order item creation.");
                }
            }

            // Process Payment (Example with Stripe Payment Intent)
            $paymentResult = $this->paymentController->createPaymentIntent($orderId, $total, $postData['shipping_email']);
            if (!$paymentResult['success']) {
                 // Payment Intent creation failed *before* charging customer
                 $this->orderModel->updateStatus($orderId, 'failed'); // Mark order as failed
                throw new Exception($paymentResult['error'] ?? 'Could not initiate payment.');
            }

            // Payment Intent created successfully, need confirmation from client-side (Stripe Elements)

            $this->commit(); // Commit order creation BEFORE sending client secret

            // Log order placement attempt (status is pending_payment)
            $this->logAuditTrail('order_pending_payment', $userId, [
                'order_id' => $orderId, 'total_amount' => $total, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            // Send client secret back to the client-side JavaScript for payment confirmation
            return $this->jsonResponse([
                'success' => true,
                'orderId' => $orderId,
                'clientSecret' => $paymentResult['client_secret'] // Send client secret
                // Add publishableKey if needed by JS: 'publishableKey' => STRIPE_PUBLIC_KEY
            ]);

        } catch (Exception $e) {
            $this->rollback(); // Rollback transaction on any error
            error_log("Checkout processing error: " . $e->getMessage());
            // Return JSON error
            return $this->jsonResponse([
                'success' => false,
                'error' => 'An error occurred while processing your order. Please try again.' // Keep error generic for security
            ], 500);
        }
    }

    // showOrderConfirmation remains largely the same, just add variables
     public function showOrderConfirmation() {
         // Security: Ensure user is logged in to view their confirmation
         $this->requireLogin();
         $userId = $this->getUserId();

         // Use the order ID stored in the session after successful payment processing
         if (!isset($_SESSION['last_order_id'])) {
             // If no recent order ID, redirect to account/orders
             $this->setFlashMessage('Could not find recent order details.', 'warning');
             $this->redirect('account&action=orders'); // Redirect to orders list
             return;
         }

         $lastOrderId = $_SESSION['last_order_id'];

         // Fetch the specific order, ensuring it belongs to the current user
         $order = $this->orderModel->getByIdAndUserId($lastOrderId, $userId);

         // If order not found or doesn't belong to user, redirect
         if (!$order) {
             unset($_SESSION['last_order_id']); // Clear invalid session data
             $this->setFlashMessage('Order details not found or access denied.', 'error');
             $this->redirect('account&action=orders');
             return;
         }

         // Clear the session variable after successfully retrieving the order
         unset($_SESSION['last_order_id']);

         // Prepare data for the view
         $csrfToken = $this->generateCSRFToken(); // Still good practice for any potential forms/actions
         $bodyClass = 'page-order-confirmation';
         $pageTitle = 'Order Confirmation - The Scent';

         // Use require_once, so define variables directly
         extract([
             'order' => $order, // Contains order details and items
             'csrfToken' => $csrfToken,
             'bodyClass' => $bodyClass,
             'pageTitle' => $pageTitle
         ]);

         require_once __DIR__ . '/../views/order_confirmation.php';
     }

    // updateOrderStatus remains unchanged (uses jsonResponse, admin context)
    public function updateOrderStatus($orderId, $status, $trackingInfo = null) {
        $this->requireAdmin(); // Ensure only admin can update status
        $this->validateCSRF(); // Validate CSRF if called via POST form

        // Validate input
        $orderId = $this->validateInput($orderId, 'int');
        $status = $this->validateInput($status, 'string'); // Add more specific validation if needed (e.g., enum check)
        // Further validation for trackingInfo structure if provided

        if (!$orderId || !$status) {
            return $this->jsonResponse(['success' => false, 'error' => 'Invalid input.'], 400);
        }

        $order = $this->orderModel->getById($orderId);
        if (!$order) {
           return $this->jsonResponse(['success' => false, 'error' => 'Order not found'], 404);
        }

        try {
            $this->beginTransaction();

            $updated = $this->orderModel->updateStatus($orderId, $status);
            if (!$updated) throw new Exception("Failed to update order status.");


            // If status is 'shipped' and tracking info is provided, update tracking and notify user
            if ($status === 'shipped' && $trackingInfo && !empty($trackingInfo['number'])) {
                 $trackingUpdated = $this->orderModel->updateTracking(
                     $orderId,
                     $this->validateInput($trackingInfo['number'], 'string'),
                     $this->validateInput($trackingInfo['carrier'] ?? null, 'string'), // Optional carrier
                     $this->validateInput($trackingInfo['url'] ?? null, 'url') // Optional URL
                 );

                 if ($trackingUpdated) {
                     // Fetch user details to send email
                     $userModel = new User($this->pdo);
                     $user = $userModel->getById($order['user_id']);
                     if ($user) {
                         $this->emailService->sendShippingUpdate(
                             $order, // Pass full order details
                             $user,
                             $trackingInfo['number'],
                             $trackingInfo['carrier'] ?? ''
                         );
                     } else {
                          error_log("Could not find user {$order['user_id']} to send shipping update for order {$orderId}");
                     }
                 } else {
                     error_log("Failed to update tracking info for order {$orderId}");
                     // Decide if this should be a fatal error for the transaction
                 }
            }

            $this->commit();

            // Log status update
            $adminUserId = $this->getUserId();
            $this->logAuditTrail('order_status_update', $adminUserId, [
                 'order_id' => $orderId, 'new_status' => $status, 'tracking_provided' => !empty($trackingInfo)
            ]);

            return $this->jsonResponse(['success' => true, 'message' => 'Order status updated successfully.']);

        } catch (Exception $e) {
            $this->rollback();
            error_log("Error updating order status for {$orderId}: " . $e->getMessage());
           return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }


    // validateCartStock remains the same
     private function validateCartStock($cartItems = null) {
         $errors = [];
         // Use the provided cartItems if available (during checkout processing)
         // Otherwise, fetch the current user's cart (e.g., for display on checkout page)
         if ($cartItems === null) {
             $userId = $this->getUserId();
             if (!$userId) return ['User not logged in']; // Should be caught by requireLogin earlier

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
                 $errors[] = ($product ? $product['name'] : "Product ID {$productId}") . " has insufficient stock";
             }
         }
         return $errors;
     }


} // End of CheckoutController class
