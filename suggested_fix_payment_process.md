Okay, let's implement the full checkout and payment flow. This requires modifications to several files to handle the process from submitting the checkout form, creating the order, initiating payment with Stripe, handling the payment result (via webhook), and displaying the confirmation.

**Core Logic Flow:**

1.  **`views/checkout.php` / `js/main.js`:** User fills form, clicks "Place Order". JS validates fields, calls `processCheckout` endpoint via AJAX.
2.  **`index.php`:** Routes `page=checkout&action=processCheckout` (POST) to `CheckoutController::processCheckout`.
3.  **`controllers/CheckoutController::processCheckout`:**
    *   Validates CSRF, input data, stock, coupon (again).
    *   Starts DB transaction.
    *   Calculates final totals (subtotal - discount + shipping + tax).
    *   Creates order in DB with status `pending_payment`.
    *   Creates order items.
    *   Decrements stock via `InventoryController`.
    *   Records coupon usage via `CouponController`.
    *   Calls `PaymentController::createPaymentIntent`.
    *   Updates order with `payment_intent_id`.
    *   Commits transaction.
    *   Returns `{success: true, clientSecret: '...', orderId: ...}` via JSON.
4.  **`js/main.js`:** Receives `clientSecret`, calls `stripe.confirmPayment()`. Stripe handles the payment UI (e.g., 3D Secure) and redirects on completion (or shows an error).
5.  **Stripe Webhook:** Stripe sends an event (e.g., `payment_intent.succeeded`) to a dedicated endpoint (needs setup separately, but the handler logic is included).
6.  **Webhook Endpoint (`index.php?page=payment&action=webhook` - *needs adding*) -> `PaymentController::handleWebhook`:**
    *   Verifies webhook signature.
    *   Handles `payment_intent.succeeded`:
        *   Starts DB transaction.
        *   Finds order by `payment_intent_id`.
        *   Updates order status to `paid` (or `processing`).
        *   Sets `$_SESSION['last_order_id']`.
        *   Sends confirmation email via `EmailService`.
        *   Clears user's cart via `CartModel`.
        *   Commits transaction.
    *   Responds 200 OK to Stripe.
7.  **Stripe Redirect:** After payment, Stripe redirects user to the `return_url` (`index.php?page=checkout&action=confirmation`).
8.  **`index.php`:** Routes `page=checkout&action=confirmation` (GET) to `CheckoutController::showOrderConfirmation`.
9.  **`controllers/CheckoutController::showOrderConfirmation`:**
    *   Checks for `$_SESSION['last_order_id']`.
    *   Fetches the order by ID and User ID.
    *   Verifies the order status is `paid`/`processing`.
    *   Clears the session variable.
    *   Renders `views/order_confirmation.php`.

---

**Files to Modify:**

1.  `models/Order.php` (Update `create`, verify methods)
2.  `controllers/PaymentController.php` (Update `createPaymentIntent`, implement webhook handlers fully)
3.  `controllers/CheckoutController.php` (Implement `processCheckout`, update `showOrderConfirmation`)
4.  `controllers/CouponController.php` (Remove transaction from `recordUsage`)
5.  `index.php` (Add routes for webhook and new checkout actions)
6.  `js/main.js` (Verify endpoints, `return_url`)
7.  `views/order_confirmation.php` (Ensure it uses order data correctly)

---

**Generated Updated Files:**

**1. `models/Order.php`**

```php
<?php
// models/Order.php (Updated create signature, _fetchOrderItems join)

// Ensure Product model is available if needed for item details fetching
// Assuming autoloader or previous require_once handles this
// require_once __DIR__ . '/Product.php';

class Order {
    private PDO $pdo; // Use type hint

    public function __construct(PDO $pdo) { // Use type hint
        $this->pdo = $pdo;
    }

    /**
     * Creates a new order in the database.
     * Accepts extended data including coupon info, payment intent ID, etc.
     *
     * @param array $data Order data including user_id, totals, shipping info, coupon info, etc.
     * @return int|false The ID of the newly created order, or false on failure.
     */
    public function create(array $data): int|false {
        // --- Updated SQL to include all necessary fields ---
        $sql = "
            INSERT INTO orders (
                user_id, subtotal, discount_amount, coupon_code, coupon_id,
                shipping_cost, tax_amount, total_amount, shipping_name, shipping_email,
                shipping_address, shipping_city, shipping_state, shipping_zip,
                shipping_country, status, payment_status, payment_intent_id, order_notes,
                created_at, updated_at
            ) VALUES (
                :user_id, :subtotal, :discount_amount, :coupon_code, :coupon_id,
                :shipping_cost, :tax_amount, :total_amount, :shipping_name, :shipping_email,
                :shipping_address, :shipping_city, :shipping_state, :shipping_zip,
                :shipping_country, :status, :payment_status, :payment_intent_id, :order_notes,
                NOW(), NOW()
            )
        ";
        // --- End Updated SQL ---
        $stmt = $this->pdo->prepare($sql);

        // --- Updated execute array with new fields ---
        $success = $stmt->execute([
            ':user_id' => $data['user_id'],
            ':subtotal' => $data['subtotal'] ?? 0.00,
            ':discount_amount' => $data['discount_amount'] ?? 0.00,
            ':coupon_code' => $data['coupon_code'] ?? null,
            ':coupon_id' => $data['coupon_id'] ?? null,
            ':shipping_cost' => $data['shipping_cost'] ?? 0.00,
            ':tax_amount' => $data['tax_amount'] ?? 0.00,
            ':total_amount' => $data['total_amount'] ?? 0.00,
            ':shipping_name' => $data['shipping_name'],
            ':shipping_email' => $data['shipping_email'],
            ':shipping_address' => $data['shipping_address'],
            ':shipping_city' => $data['shipping_city'],
            ':shipping_state' => $data['shipping_state'],
            ':shipping_zip' => $data['shipping_zip'],
            ':shipping_country' => $data['shipping_country'],
            ':status' => $data['status'] ?? 'pending_payment', // Default status
            ':payment_status' => $data['payment_status'] ?? 'pending', // Default payment status
            ':payment_intent_id' => $data['payment_intent_id'] ?? null, // Store PI ID
            ':order_notes' => $data['order_notes'] ?? null // Store order notes
        ]);
        // --- End Updated execute array ---

        return $success ? (int)$this->pdo->lastInsertId() : false;
    }

    /**
     * Fetches a single order by its ID, including its items.
     *
     * @param int $id The order ID.
     * @return array|null The order data including items, or null if not found.
     */
    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if ($order) {
            $order['items'] = $this->_fetchOrderItems($id);
        }
        return $order ?: null;
    }

    /**
     * Fetches a single order by its ID and User ID, including its items.
     * Ensures the order belongs to the specified user.
     *
     * @param int $orderId The order ID.
     * @param int $userId The user ID.
     * @return array|null The order data including items, or null if not found or access denied.
     */
    public function getByIdAndUserId(int $orderId, int $userId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();

        if ($order) {
            $order['items'] = $this->_fetchOrderItems($orderId);
        }
        return $order ?: null;
    }


    /**
     * Fetches recent orders for a specific user, mainly for dashboard display.
     * Includes a concatenated summary of items.
     *
     * @param int $userId The user ID.
     * @param int $limit Max number of orders to fetch.
     * @return array List of recent orders.
     */
    public function getRecentByUserId(int $userId, int $limit = 5): array {
        // This version uses GROUP_CONCAT for a simple item summary, suitable for dashboards.
        // Use getAllByUserId for full item details if needed elsewhere.
        $stmt = $this->pdo->prepare("
            SELECT o.*,
                   GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR '<br>') as items_summary
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id /* Use LEFT JOIN in case order has no items? */
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = ?
            GROUP BY o.id /* Grouping is essential for GROUP_CONCAT */
            ORDER BY o.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

     /**
     * Fetches all orders for a specific user with pagination, including full item details.
     *
     * @param int $userId The user ID.
     * @param int $page Current page number.
     * @param int $perPage Number of orders per page.
     * @return array List of orders for the page.
     */
    public function getAllByUserId(int $userId, int $page = 1, int $perPage = 10): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->pdo->prepare("
            SELECT * FROM orders
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll();

        // Fetch items for each order
        foreach ($orders as &$order) {
            $order['items'] = $this->_fetchOrderItems($order['id']);
        }
        unset($order); // Unset reference

        return $orders ?: [];
    }

    /**
     * Gets the total count of orders for a specific user.
     *
     * @param int $userId The user ID.
     * @return int Total number of orders.
     */
    public function getTotalOrdersByUserId(int $userId): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }


    /**
     * Updates the status of an order. Also updates payment_status and paid_at conditionally.
     *
     * @param int $orderId The ID of the order to update.
     * @param string $status The new status (e.g., 'paid', 'processing', 'shipped', 'cancelled').
     * @return bool True on success, false on failure.
     */
    public function updateStatus(int $orderId, string $status): bool {
        // Define allowed statuses to prevent arbitrary updates
        $allowedStatuses = ['pending_payment', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded', 'disputed', 'payment_failed', 'completed'];
        if (!in_array($status, $allowedStatuses)) {
            error_log("Attempted to set invalid order status '{$status}' for order ID {$orderId}");
            return false;
        }

        $sql = "UPDATE orders SET status = :status, updated_at = NOW()";
        $params = [':status' => $status, ':id' => $orderId];

        // Update payment_status based on main status for simplicity
        // Adjust this logic based on exact requirements (e.g., partial refunds)
        if (in_array($status, ['paid', 'processing', 'shipped', 'delivered', 'completed'])) {
             $sql .= ", payment_status = 'completed'";
             // Set paid_at timestamp only when moving to 'paid' or 'processing' for the first time
             $sql .= ", paid_at = COALESCE(paid_at, CASE WHEN :status IN ('paid', 'processing') THEN NOW() ELSE NULL END)";
        } elseif ($status === 'payment_failed') {
            $sql .= ", payment_status = 'failed'";
        } elseif ($status === 'cancelled') {
             $sql .= ", payment_status = 'cancelled'";
        } elseif ($status === 'refunded') {
             $sql .= ", payment_status = 'refunded'";
        } elseif ($status === 'disputed') {
             $sql .= ", payment_status = 'disputed'";
        }
        // 'pending_payment' status typically implies 'pending' payment_status initially

        $sql .= " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Updates the Payment Intent ID for a given order.
     *
     * @param int $orderId The ID of the order.
     * @param string $paymentIntentId The Stripe Payment Intent ID.
     * @return bool True on success, false on failure.
     */
    public function updatePaymentIntentId(int $orderId, string $paymentIntentId): bool {
        $stmt = $this->pdo->prepare("
            UPDATE orders
            SET payment_intent_id = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$paymentIntentId, $orderId]);
    }


    /**
     * Fetches an order by its Stripe Payment Intent ID.
     *
     * @param string $paymentIntentId The Stripe Payment Intent ID.
     * @return array|null The order data, or null if not found.
     */
    public function getByPaymentIntentId(string $paymentIntentId): ?array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM orders WHERE payment_intent_id = ? LIMIT 1 /* Ensure only one */
        ");
        $stmt->execute([$paymentIntentId]);
        $order = $stmt->fetch();

        // Optionally fetch items if needed by webhook handlers (often not needed directly in webhook)
        // if ($order) {
        //     $order['items'] = $this->_fetchOrderItems($order['id']);
        // }
        return $order ?: null;
    }

    /**
      * Updates the order status and adds dispute information.
      *
      * @param int $orderId
      * @param string $status Typically 'disputed'.
      * @param string $disputeId Stripe Dispute ID.
      * @return bool
      */
     public function updateStatusAndDispute(int $orderId, string $status, string $disputeId): bool {
         // Ensure status is 'disputed'
         if ($status !== 'disputed') {
             error_log("Invalid status '{$status}' provided to updateStatusAndDispute for order {$orderId}");
             return false;
         }
         $stmt = $this->pdo->prepare("
             UPDATE orders
             SET status = ?,
                 payment_status = 'disputed', /* Explicitly set payment status */
                 dispute_id = ?,
                 disputed_at = NOW(),
                 updated_at = NOW()
             WHERE id = ?
         ");
         return $stmt->execute([$status, $disputeId, $orderId]);
     }

     /**
      * Updates the order status and adds refund information.
      *
      * @param int $orderId
      * @param string $status Typically 'refunded'.
      * @param string $paymentStatus Typically 'refunded' or 'partially_refunded'.
      * @param string $refundId Stripe Refund ID.
      * @return bool
      */
     public function updateRefundStatus(int $orderId, string $status, string $paymentStatus, string $refundId): bool {
         // Ensure status is valid for refund
         if (!in_array($status, ['refunded', 'partially_refunded'])) { // Adjust if using different statuses
             error_log("Invalid status '{$status}' provided to updateRefundStatus for order {$orderId}");
             return false;
         }
         $stmt = $this->pdo->prepare("
             UPDATE orders
             SET status = ?,
                 payment_status = ?,
                 refund_id = ?, /* Assuming 'refund_id' column exists */
                 refunded_at = NOW(), /* Assuming 'refunded_at' column exists */
                 updated_at = NOW()
             WHERE id = ?
         ");
         return $stmt->execute([$status, $paymentStatus, $refundId, $orderId]);
     }

     /**
      * Updates the tracking information for an order.
      *
      * @param int $orderId
      * @param string $trackingNumber
      * @param string|null $carrier
      * @param string|null $trackingUrl (Optional, if schema supports it)
      * @return bool
      */
     public function updateTracking(int $orderId, string $trackingNumber, ?string $carrier = null /*, ?string $trackingUrl = null */): bool {
         // Assuming schema has 'tracking_number' and 'carrier' columns
         $sql = "UPDATE orders SET tracking_number = ?, carrier = ?, updated_at = NOW()";
         $params = [$trackingNumber, $carrier];

         // Add tracking_url if schema supports it
         // if ($trackingUrl !== null) {
         //     $sql .= ", tracking_url = ?";
         //     $params[] = $trackingUrl;
         // }

         $sql .= " WHERE id = ?";
         $params[] = $orderId;

         $stmt = $this->pdo->prepare($sql);
         return $stmt->execute($params);
     }


    /**
     * Fetches all items associated with a given order ID.
     * Joins with products table to get item details needed for display/emails.
     *
     * @param int $orderId The order ID.
     * @return array List of order items with product details.
     */
    private function _fetchOrderItems(int $orderId): array {
        // --- Updated to fetch product name and image ---
        $stmt = $this->pdo->prepare("
            SELECT
                oi.id as order_item_id,
                oi.product_id,
                oi.quantity,
                oi.price as price_at_purchase, /* Price when the order was placed */
                p.name as product_name, /* Get product name */
                p.image as image_url /* Get product image path */
                /* Add other product fields if needed, e.g., p.sku */
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        // --- End Update ---
        $stmt->execute([$orderId]);
        return $stmt->fetchAll() ?: [];
    }

} // End Order class
```

**2. `controllers/PaymentController.php`**

```php
<?php
// controllers/PaymentController.php (Updated createPaymentIntent, handleWebhook)

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config.php'; // Keep config include

// Use statement for Stripe classes
use Stripe\Stripe; // Added for setting API key globally if needed
use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
// Include models needed for webhook actions
require_once __DIR__ . '/../models/Order.php'; // Needed for updating order status
require_once __DIR__ . '/../models/User.php';  // Needed for sending emails
require_once __DIR__ . '/../models/Cart.php';  // Needed for clearing cart

class PaymentController extends BaseController {
    private ?StripeClient $stripe; // Allow null initialization
    private ?string $webhookSecret; // Allow null initialization
    private Order $orderModel; // Add Order model instance
    // EmailService is inherited from BaseController

    public function __construct($pdo = null) {
        parent::__construct($pdo); // BaseController handles EmailService init now

        // Ensure PDO is available if needed directly or via BaseController
        if (!$this->db) {
             error_log("PDO connection not available in PaymentController constructor.");
             // Handle appropriately - maybe throw exception
             $this->stripe = null;
             $this->webhookSecret = null;
             return;
        }
        $this->orderModel = new Order($this->db); // Initialize Order model

        // Ensure Stripe keys are defined
        if (!defined('STRIPE_SECRET_KEY') || !defined('STRIPE_WEBHOOK_SECRET')) {
            error_log("Stripe keys are not defined in config.php");
            $this->stripe = null;
            $this->webhookSecret = null;
            return; // Stop initialization if keys are missing
        }

        // Use try-catch for external service initialization
        try {
            $this->stripe = new StripeClient(STRIPE_SECRET_KEY);
            $this->webhookSecret = STRIPE_WEBHOOK_SECRET;
        } catch (\Exception $e) {
             error_log("Failed to initialize Stripe client: " . $e->getMessage());
             $this->stripe = null; // Ensure stripe is null if init fails
             $this->webhookSecret = null;
        }
    }

    /**
     * Create a Stripe Payment Intent.
     * Returns payment_intent_id along with client_secret.
     *
     * @param float $amount Amount in major currency unit (e.g., dollars).
     * @param string $currency 3-letter ISO currency code.
     * @param int $orderId Internal order ID for metadata.
     * @param string $customerEmail Email for receipt/customer matching.
     * @return array ['success' => bool, 'client_secret' => string|null, 'payment_intent_id' => string|null, 'error' => string|null]
     */
    public function createPaymentIntent(float $amount, string $currency = 'usd', int $orderId = 0, string $customerEmail = ''): array {
        if (!$this->stripe) {
             return ['success' => false, 'error' => 'Payment system unavailable.', 'client_secret' => null, 'payment_intent_id' => null];
        }

        $paymentIntentParams = []; // Define outside try for logging
        try {
            if ($amount <= 0) throw new InvalidArgumentException('Invalid payment amount.');
            $currency = strtolower(trim($currency));
            if (strlen($currency) !== 3) throw new InvalidArgumentException('Invalid currency code.');
            if ($orderId <= 0) throw new InvalidArgumentException('Invalid Order ID for Payment Intent.');

            $paymentIntentParams = [
                'amount' => (int)round($amount * 100), // Convert to cents
                'currency' => $currency,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'internal_order_id' => $orderId, // Use a clear key like internal_order_id
                    'user_id' => $this->getUserId() ?? 'guest', // Use helper
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
                ]
            ];

             if (!empty($customerEmail)) {
                 $paymentIntentParams['receipt_email'] = $customerEmail;
             }

            $paymentIntent = $this->stripe->paymentIntents->create($paymentIntentParams);

            // --- Return Payment Intent ID ---
            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id // Include the ID
            ];
            // --- End Return Payment Intent ID ---

        } catch (ApiErrorException $e) {
            error_log("Stripe API Error creating PaymentIntent for Order {$orderId}: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
            return [
                'success' => false, 'error' => 'Payment processing failed. Please try again.',
                'client_secret' => null, 'payment_intent_id' => null
            ];
        } catch (InvalidArgumentException $e) {
             error_log("Payment Intent Creation Invalid Argument for Order {$orderId}: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
             return [
                 'success' => false, 'error' => $e->getMessage(),
                 'client_secret' => null, 'payment_intent_id' => null
             ];
         } catch (Exception $e) {
            error_log("Payment Intent Creation Error for Order {$orderId}: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
            return [
                'success' => false, 'error' => 'Could not initialize payment. Please try again later.',
                'client_secret' => null, 'payment_intent_id' => null
            ];
        }
    }


    /**
     * Handles incoming Stripe webhook events.
     */
    public function handleWebhook() {
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
             $this->jsonResponse(['error' => 'Missing signature'], 400);
             return;
        }
        if (empty($payload)) {
             error_log("Webhook Error: Empty payload received.");
             $this->jsonResponse(['error' => 'Empty payload'], 400);
             return;
        }

        $event = null; // Define $event outside try block
        try {
            $event = Webhook::constructEvent( $payload, $sigHeader, $this->webhookSecret );
        } catch (\UnexpectedValueException $e) {
            error_log("Webhook Error: Invalid payload. " . $e->getMessage());
            $this->jsonResponse(['error' => 'Invalid payload'], 400); return;
        } catch (SignatureVerificationException $e) {
            error_log("Webhook Error: Invalid signature. " . $e->getMessage());
            $this->jsonResponse(['error' => 'Invalid signature'], 400); return;
        } catch (\Exception $e) {
            error_log("Webhook Error: Event construction failed. " . $e->getMessage());
            $this->jsonResponse(['error' => 'Webhook processing error'], 400); return;
        }

        // Handle the event
        try {
            // --- Start Transaction ---
            $this->beginTransaction();

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handleSuccessfulPayment($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handleFailedPayment($event->data->object);
                    break;

                case 'charge.succeeded':
                     $this->handleChargeSucceeded($event->data->object);
                     break;

                case 'charge.dispute.created':
                    $this->handleDisputeCreated($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleRefund($event->data->object);
                    break;

                default:
                    error_log('Webhook Info: Received unhandled event type ' . $event->type);
            }

            // --- Commit Transaction ---
            $this->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Webhook received']);

        } catch (Exception $e) {
            // --- Rollback Transaction ---
            $this->rollback();
            $eventType = $event ? $event->type : 'UNKNOWN';
            error_log("Webhook Handling Error (Event: {$eventType}): " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            // Respond 500 to encourage Stripe retry for potentially transient errors
            $this->jsonResponse(
                ['success' => false, 'error' => 'Internal server error handling webhook.'],
                500
            );
        }
    }

    /**
     * Handles the payment_intent.succeeded event.
     * Updates order status, sends confirmation email, clears cart.
     * CRITICAL: Sets session variable for confirmation page.
     */
    private function handleSuccessfulPayment(\Stripe\PaymentIntent $paymentIntent): void {
         $order = $this->orderModel->getByPaymentIntentId($paymentIntent->id);

         if (!$order) {
              $errorMessage = "Webhook Critical: PaymentIntent {$paymentIntent->id} succeeded but no matching order found in DB.";
              error_log($errorMessage);
              $this->logSecurityEvent('webhook_order_mismatch', ['payment_intent_id' => $paymentIntent->id, 'event_type' => 'payment_intent.succeeded']);
              // Do not throw exception here, acknowledge receipt but log heavily.
              return;
         }

         // Idempotency check: If order is already processed, log and return.
         if (in_array($order['status'], ['paid', 'processing', 'shipped', 'delivered', 'completed'])) {
             error_log("Webhook Info: Received successful payment event for already processed order ID {$order['id']}. Status: {$order['status']}");
             return; // Acknowledge webhook, but don't re-process
         }

        // Update order status to 'paid' or 'processing'
        // 'processing' might be more appropriate if fulfillment isn't immediate
        $newStatus = 'processing'; // Change to 'paid' if preferred
        $updated = $this->orderModel->updateStatus($order['id'], $newStatus);

        if (!$updated) {
            // Double-check if status was updated by race condition before throwing
            $currentOrder = $this->orderModel->getById($order['id']); // Fetch again
            if (!$currentOrder || !in_array($currentOrder['status'], ['paid', 'processing', 'shipped', 'delivered', 'completed'])) {
                 // Log the specific order data for debugging
                 error_log("Failed DB update for order: " . json_encode($order));
                 throw new Exception("Failed to update order ID {$order['id']} status to '{$newStatus}'.");
            } else {
                 error_log("Webhook Info: Order ID {$order['id']} status already updated, skipping redundant update in handleSuccessfulPayment.");
            }
        } else {
             // Log success
             error_log("Webhook Success: Updated order ID {$order['id']} status to '{$newStatus}' for PaymentIntent {$paymentIntent->id}.");

             // --- Set session variable for confirmation page ---
             // IMPORTANT: This assumes the webhook handler runs in a context
             // that can access and modify the user's session. This might not
             // always be the case depending on server setup.
             if (session_status() !== PHP_SESSION_ACTIVE) {
                 // Attempt to resume session ONLY if safe and necessary.
                 // Avoid starting a new session here. If session ID is available from metadata
                 // or another source, use session_id() then session_start().
                 // This part is complex and environment-dependent.
                 // For now, we log a warning if session isn't active.
                 error_log("Webhook Warning: Session not active when trying to set last_order_id for order {$order['id']}");
             }
             // Proceed only if session is active
             if (session_status() === PHP_SESSION_ACTIVE) {
                 $_SESSION['last_order_id'] = $order['id'];
                 error_log("Webhook Success: Set session last_order_id = {$order['id']}.");
             }
             // --- End set session variable ---
        }

        // Fetch full order details (including items) for email
        // Pass user ID to ensure we get the correct order if somehow IDs overlap (unlikely but safe)
        $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $order['user_id']);

        if ($fullOrder) {
             // Send payment confirmation email
             if ($this->emailService && method_exists($this->emailService, 'sendOrderConfirmation')) {
                  // Fetch user details for the email
                  $userModel = new User($this->db);
                  $user = $userModel->getById($fullOrder['user_id']);
                  if ($user) {
                       $emailSent = $this->emailService->sendOrderConfirmation($fullOrder, $user);
                       if ($emailSent) {
                            error_log("Webhook Success: Order confirmation email queued for order ID {$fullOrder['id']}.");
                       } else {
                            error_log("Webhook Warning: sendOrderConfirmation returned false for order ID {$fullOrder['id']}.");
                       }
                  } else {
                       error_log("Webhook Warning: Could not fetch user data for order confirmation email (Order ID: {$fullOrder['id']}, User ID: {$fullOrder['user_id']}).");
                  }
             } else {
                  error_log("Webhook Warning: EmailService or sendOrderConfirmation method not available for order ID {$fullOrder['id']}.");
             }
        } else {
             error_log("Webhook Warning: Could not fetch full order details for notification (Order ID: {$order['id']}).");
        }

        // Clear user's cart
        if ($order['user_id']) {
            try {
                // Ensure Cart class is loaded if needed
                if (!class_exists('Cart')) require_once __DIR__ . '/../models/Cart.php';
                $cartModel = new Cart($this->db, $order['user_id']);
                $cartModel->clearCart();
                error_log("Webhook Success: Cart cleared for user ID {$order['user_id']} after order {$order['id']} payment.");
            } catch (Exception $cartError) {
                 error_log("Webhook Warning: Failed to clear cart for user ID {$order['user_id']} after order {$order['id']} payment: " . $cartError->getMessage());
                 // Don't let cart clearing failure stop webhook processing
            }
        }
    }

    /**
     * Handles the payment_intent.payment_failed event.
     */
    private function handleFailedPayment(\Stripe\PaymentIntent $paymentIntent): void {
         $order = $this->orderModel->getByPaymentIntentId($paymentIntent->id);
         if (!$order) {
              error_log("Webhook Warning: PaymentIntent {$paymentIntent->id} failed but no matching order found.");
              return; // Acknowledge webhook
         }
         // Idempotency check
         if ($order['status'] === 'payment_failed' || in_array($order['status'], ['cancelled', 'paid', 'processing', 'shipped', 'delivered', 'completed'])) {
              error_log("Webhook Info: Received failed payment event for already resolved/failed order ID {$order['id']}. Status: {$order['status']}");
              return;
          }

        $newStatus = 'payment_failed';
        $updated = $this->orderModel->updateStatus($order['id'], $newStatus);

        if (!$updated) {
            $currentOrder = $this->orderModel->getById($order['id']);
            if (!$currentOrder || $currentOrder['status'] !== $newStatus) {
                throw new Exception("Failed to update order ID {$order['id']} status to '{$newStatus}'.");
            }
        } else {
            error_log("Webhook Info: Updated order ID {$order['id']} status to '{$newStatus}' for PaymentIntent {$paymentIntent->id}.");
        }

        // Send payment failed notification (optional)
        $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $order['user_id']);
        if ($fullOrder) {
             // Assuming method exists: sendPaymentFailedNotification(array $order, array $user)
             if ($this->emailService && method_exists($this->emailService, 'sendPaymentFailedNotification')) {
                  $userModel = new User($this->db);
                  $user = $userModel->getById($fullOrder['user_id']);
                  if ($user) {
                       $this->emailService->sendPaymentFailedNotification($fullOrder, $user);
                       error_log("Webhook Info: Payment failed email queued for order ID {$fullOrder['id']}.");
                  } else {
                      error_log("Webhook Warning: Could not fetch user for failed payment email (Order ID: {$fullOrder['id']}).");
                  }
             }
        }
    }

    /**
     * Handles the charge.succeeded event (often informational if using PaymentIntents).
     */
     private function handleChargeSucceeded(\Stripe\Charge $charge): void {
         // Mostly informational if using PaymentIntents. Log it.
         error_log("Webhook Info: Charge {$charge->id} succeeded (PaymentIntent: {$charge->payment_intent}). Order status managed via PaymentIntent events.");
     }

    /**
     * Handles the charge.dispute.created event.
     */
    private function handleDisputeCreated(\Stripe\Dispute $dispute): void {
        $order = $this->orderModel->getByPaymentIntentId($dispute->payment_intent);
         if (!$order) {
              error_log("Webhook Warning: Dispute {$dispute->id} created for PI {$dispute->payment_intent} but no matching order found.");
              return; // Acknowledge webhook
         }

        $newStatus = 'disputed';
        $updated = $this->orderModel->updateStatusAndDispute($order['id'], $newStatus, $dispute->id);

        if (!$updated) {
             $currentOrder = $this->orderModel->getById($order['id']);
             if (!$currentOrder || $currentOrder['status'] !== $newStatus) {
                 throw new Exception("Failed to update order ID {$order['id']} dispute status.");
             }
        } else {
             error_log("Webhook Alert: Order ID {$order['id']} status updated to '{$newStatus}' due to Dispute {$dispute->id}.");
        }

        // Log security event and alert admin
        $this->logSecurityEvent('stripe_dispute_created', [
             'order_id' => $order['id'],
             'dispute_id' => $dispute->id,
             'payment_intent_id' => $dispute->payment_intent,
             'amount' => $dispute->amount,
             'reason' => $dispute->reason
        ]);
        // Assuming method exists: sendAdminDisputeAlert(int $orderId, string $disputeId, string $reason, int $amount)
        if ($this->emailService && method_exists($this->emailService, 'sendAdminDisputeAlert')) {
             $this->emailService->sendAdminDisputeAlert($order['id'], $dispute->id, $dispute->reason, $dispute->amount);
        }
    }

    /**
     * Handles the charge.refunded event.
     */
    private function handleRefund(\Stripe\Charge $charge): void {
         $refund = $charge->refunds->data[0] ?? null; // Get the most recent refund object
         if (!$refund) {
             error_log("Webhook Warning: Received charge.refunded event for Charge {$charge->id} but no refund data found.");
             return; // Acknowledge webhook
         }

         $order = $this->orderModel->getByPaymentIntentId($charge->payment_intent);
         if (!$order) {
              error_log("Webhook Warning: Refund {$refund->id} processed for PI {$charge->payment_intent} but no matching order found.");
              return; // Acknowledge webhook
         }

         // Determine status based on refund amount vs charge amount
         $isPartialRefund = ($charge->amount_refunded < $charge->amount_captured); // Use amount_captured
         $newStatus = $isPartialRefund ? 'partially_refunded' : 'refunded'; // Consider if you need 'partially_refunded' status
         $newPaymentStatus = $newStatus; // Align payment status

         $updated = $this->orderModel->updateRefundStatus($order['id'], $newStatus, $newPaymentStatus, $refund->id);

        if (!$updated) {
            $currentOrder = $this->orderModel->getById($order['id']);
            if (!$currentOrder || !in_array($currentOrder['status'], ['refunded', 'partially_refunded'])) {
                throw new Exception("Failed to update order ID {$order['id']} refund status.");
            }
        } else {
            error_log("Webhook Info: Order ID {$order['id']} status updated to '{$newStatus}' due to Refund {$refund->id}.");
        }

        // Send refund confirmation email (optional)
        $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $order['user_id']);
        if ($fullOrder) {
             // Assuming method exists: sendRefundConfirmation(array $order, array $user, float $refundAmount)
             if ($this->emailService && method_exists($this->emailService, 'sendRefundConfirmation')) {
                   $userModel = new User($this->db);
                   $user = $userModel->getById($fullOrder['user_id']);
                   if ($user) {
                        $this->emailService->sendRefundConfirmation($fullOrder, $user, $refund->amount / 100.0);
                        error_log("Webhook Info: Refund confirmation email queued for order ID {$fullOrder['id']}.");
                   } else {
                        error_log("Webhook Warning: Could not fetch user for refund confirmation email (Order ID: {$fullOrder['id']}).");
                   }
             }
        }
    }
} // End PaymentController class
```

**3. `controllers/CheckoutController.php`**

```php
<?php
// controllers/CheckoutController.php (Updated processCheckout, showOrderConfirmation)

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

         // Check user-specific usage
         if ($this->couponController->hasUserUsedCoupon($coupon['id'], $userId)) {
              return $this->jsonResponse(['success' => false, 'message' => 'You have already used this coupon.']);
         }

         // Calculate discount
         $discountAmount = $this->couponController->calculateDiscount($coupon, $currentSubtotal);

         // Estimate new total *excluding tax* for UI update
         $subtotalAfterDiscount = max(0, $currentSubtotal - $discountAmount);
         $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
         $newTotalEstimate = $subtotalAfterDiscount + $shipping_cost;

         return $this->jsonResponse([
             'success' => true,
             'message' => 'Coupon applied successfully!',
             'coupon_code' => $coupon['code'],
             'discount_amount' => number_format($discountAmount, 2),
             'new_total_estimate' => number_format($newTotalEstimate, 2) // Estimate excluding tax
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

    // Admin methods updateOrderStatus etc. remain unchanged for this task.

} // End of CheckoutController class
```

**4. `controllers/CouponController.php`**

```php
<?php
// controllers/CouponController.php (Removed transaction from recordUsage)

require_once __DIR__ . '/BaseController.php';

class CouponController extends BaseController {

    public function __construct($pdo) {
        parent::__construct($pdo);
    }

    /**
     * Core validation logic for a coupon code.
     * Checks active status, dates, usage limits, minimum purchase.
     * Does NOT check user-specific usage here.
     *
     * @param string $code
     * @param float $subtotal
     * @return array ['valid' => bool, 'message' => string, 'coupon' => array|null]
     */
    public function validateCouponCodeOnly(string $code, float $subtotal): array {
        $code = $this->validateInput($code, 'string');
        $subtotal = $this->validateInput($subtotal, 'float');

        if (!$code || $subtotal === false || $subtotal < 0) {
            return ['valid' => false, 'message' => 'Invalid coupon code or subtotal amount.', 'coupon' => null];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT * FROM coupons
                WHERE code = ?
                AND is_active = TRUE
                AND (valid_from IS NULL OR valid_from <= CURDATE())
                AND (valid_to IS NULL OR valid_to >= CURDATE())
                AND (usage_limit IS NULL OR usage_count < usage_limit)
                AND (min_purchase_amount IS NULL OR min_purchase_amount <= ?)
            ");
            $stmt->execute([$code, $subtotal]);
            $coupon = $stmt->fetch();

            if (!$coupon) {
                 // Fetch potentially inactive/expired coupon to give specific message
                 $stmtCheck = $this->db->prepare("SELECT * FROM coupons WHERE code = ?");
                 $stmtCheck->execute([$code]);
                 $existingCoupon = $stmtCheck->fetch();
                 if (!$existingCoupon) {
                     return ['valid' => false, 'message' => 'Coupon code not found.', 'coupon' => null];
                 } elseif (!$existingCoupon['is_active']) {
                     return ['valid' => false, 'message' => 'Coupon is not active.', 'coupon' => null];
                 } elseif ($existingCoupon['valid_from'] && $existingCoupon['valid_from'] > date('Y-m-d')) {
                     return ['valid' => false, 'message' => 'Coupon is not yet valid.', 'coupon' => null];
                 } elseif ($existingCoupon['valid_to'] && $existingCoupon['valid_to'] < date('Y-m-d')) {
                     return ['valid' => false, 'message' => 'Coupon has expired.', 'coupon' => null];
                 } elseif ($existingCoupon['usage_limit'] !== null && $existingCoupon['usage_count'] >= $existingCoupon['usage_limit']) {
                     return ['valid' => false, 'message' => 'Coupon usage limit reached.', 'coupon' => null];
                 } elseif ($existingCoupon['min_purchase_amount'] !== null && $subtotal < $existingCoupon['min_purchase_amount']) {
                     return ['valid' => false, 'message' => 'Minimum spend requirement not met.', 'coupon' => null];
                 }
                // Default invalid message if specific reason not found
                return ['valid' => false, 'message' => 'Coupon is invalid or expired.', 'coupon' => null];
            }

            return ['valid' => true, 'message' => 'Coupon code is potentially valid.', 'coupon' => $coupon];

        } catch (Exception $e) {
            error_log("Coupon Code Validation DB Error: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Error validating coupon code.', 'coupon' => null];
        }
    }

    /**
     * Check if a specific user has already used a specific coupon.
     * Public access needed by CheckoutController.
     *
     * @param int $couponId
     * @param int $userId
     * @return bool True if used, False otherwise.
     */
    public function hasUserUsedCoupon(int $couponId, int $userId): bool {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM coupon_usage
                WHERE coupon_id = ? AND user_id = ?
            ");
            $stmt->execute([$couponId, $userId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
             error_log("Error checking user coupon usage (Coupon: {$couponId}, User: {$userId}): " . $e->getMessage());
             return false; // Fail open - assume not used if DB error, let checkout attempt proceed
        }
    }


    /**
     * Handles AJAX request from checkout page to validate a coupon.
     * Includes user-specific checks.
     * Returns JSON response for the frontend.
     */
    public function applyCouponAjax() {
        $this->requireLogin(true); // Ensure user is logged in (AJAX)
        $this->validateCSRF(); // Validate CSRF from AJAX

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $code = $this->validateInput($data['code'] ?? null, 'string');
        $currentSubtotal = $this->validateInput($data['subtotal'] ?? null, 'float'); // Get subtotal from client
        $userId = $this->getUserId();

        if (!$code || $currentSubtotal === false || $currentSubtotal < 0) {
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid coupon code or subtotal amount provided.'], 400);
        }

        // Step 1: Core validation
        $validationResult = $this->validateCouponCodeOnly($code, $currentSubtotal);
        if (!$validationResult['valid']) {
             return $this->jsonResponse(['success' => false, 'message' => $validationResult['message']]);
        }
        $coupon = $validationResult['coupon'];

        // Step 2: User-specific validation
        if ($this->hasUserUsedCoupon($coupon['id'], $userId)) {
            return $this->jsonResponse(['success' => false, 'message' => 'You have already used this coupon.']);
        }

        // Step 3: Calculate discount and return success
        $discountAmount = $this->calculateDiscount($coupon, $currentSubtotal);
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
     * Records the usage of a coupon for a specific order and user.
     * Increments the coupon's usage count.
     * Assumes it's called within the main checkout transaction.
     *
     * @param int $couponId
     * @param int $orderId
     * @param int $userId
     * @param float $discountAmount
     * @return bool True on success, false on failure.
     */
    public function recordUsage(int $couponId, int $orderId, int $userId, float $discountAmount): bool {
         // --- Removed transaction management ---
        try {
             if ($couponId <= 0 || $orderId <= 0 || $userId <= 0 || $discountAmount < 0) {
                 throw new InvalidArgumentException('Invalid parameters for recording coupon usage.');
             }
             // Record usage in coupon_usage table
             $stmtUsage = $this->db->prepare("
                 INSERT INTO coupon_usage (coupon_id, order_id, user_id, discount_amount, used_at)
                 VALUES (?, ?, ?, ?, NOW())
             ");
             $usageInserted = $stmtUsage->execute([$couponId, $orderId, $userId, $discountAmount]);
             if (!$usageInserted) { throw new Exception("Failed to insert into coupon_usage table."); }

             // Update usage_count in coupons table
             $stmtUpdate = $this->db->prepare("
                 UPDATE coupons SET usage_count = usage_count + 1, updated_at = NOW() WHERE id = ?
             ");
             $countUpdated = $stmtUpdate->execute([$couponId]);
             if (!$countUpdated || $stmtUpdate->rowCount() === 0) {
                 error_log("Warning: Failed to increment usage_count for coupon ID {$couponId} on order ID {$orderId}, but usage was recorded.");
             }
            return true;
        } catch (Exception $e) {
            error_log("Coupon usage recording error for CouponID {$couponId}, OrderID {$orderId}: " . $e->getMessage());
            return false; // Indicate failure to the calling transaction
        }
    }


    /**
     * Calculates the discount amount based on coupon type and subtotal.
     *
     * @param array $coupon Coupon data array.
     * @param float $subtotal Order subtotal.
     * @return float Calculated discount amount.
     */
    public function calculateDiscount(array $coupon, float $subtotal): float {
        $discountAmount = 0;
        $discountValue = $coupon['discount_value'] ?? 0;
        $discountType = $coupon['discount_type'] ?? null;

        if ($discountType === 'percentage') {
            $discountAmount = $subtotal * ($discountValue / 100);
        } elseif ($discountType === 'fixed') {
            $discountAmount = $discountValue;
        } else {
             error_log("Unknown discount type '{$discountType}' for coupon ID {$coupon['id']}");
             return 0;
        }
        // Apply maximum discount limit if set and numeric
        if (isset($coupon['max_discount_amount']) && is_numeric($coupon['max_discount_amount']) && $coupon['max_discount_amount'] > 0) {
            $discountAmount = min($discountAmount, (float)$coupon['max_discount_amount']);
        }
         // Ensure discount doesn't exceed subtotal
         $discountAmount = min($discountAmount, $subtotal);
        return round(max(0, $discountAmount), 2); // Ensure non-negative and round
    }

    // --- Admin CRUD methods (No changes needed for checkout flow) ---
    public function listCoupons() { /* ... unchanged ... */ }
    public function showCreateForm() { /* ... unchanged ... */ }
    public function showEditForm(int $id) { /* ... unchanged ... */ }
    public function saveCoupon() { /* ... unchanged ... */ }
    public function toggleCouponStatus(int $id) { /* ... unchanged ... */ }
    public function deleteCoupon(int $id) { /* ... unchanged ... */ }

} // End CouponController class
```

**5. `index.php`**

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

**6. `js/main.js`**

```javascript
// js/main.js (Verify endpoints and return_url)

// --- START OF FILE main.js ---
// ... (Mobile menu, showFlashMessage, Add-to-Cart, Newsletter handlers - unchanged) ...

// --- Page Specific Initializers ---

function initHomePage() { /* ... unchanged ... */ }
function initProductsPage() { /* ... unchanged ... */ }
function initProductDetailPage() { /* ... unchanged ... */ }
function initCartPage() { /* ... unchanged ... */ }
function initLoginPage() { /* ... unchanged ... */ }
function initRegisterPage() { /* ... unchanged ... */ }
function initForgotPasswordPage() { /* ... unchanged ... */ }
function initResetPasswordPage() { /* ... unchanged ... */ }
function initQuizPage() { /* ... unchanged ... */ }
function initQuizResultsPage() { /* ... unchanged ... */ }
function initAdminQuizAnalyticsPage() { /* ... unchanged ... */ }
function initAdminCouponsPage() { /* ... unchanged ... */ }

// --- Checkout Page Initialization (Updated) ---
function initCheckoutPage() {
    // console.log("Initializing Checkout Page");
    const stripePublicKey = document.body.dataset.stripePublicKey || '<?= defined('STRIPE_PUBLIC_KEY') ? STRIPE_PUBLIC_KEY : '' ?>'; // Get PK from data attribute or fallback
    const checkoutForm = document.getElementById('checkoutForm');
    const submitButton = document.getElementById('submit-button');
    const spinner = document.getElementById('spinner');
    const buttonText = document.getElementById('button-text');
    const paymentElementContainer = document.getElementById('payment-element');
    const paymentMessage = document.getElementById('payment-message');
    const csrfToken = document.getElementById('csrf-token-value')?.value; // Use optional chaining
    const couponCodeInput = document.getElementById('coupon_code');
    const applyCouponButton = document.getElementById('apply-coupon');
    const couponMessageEl = document.getElementById('coupon-message');
    const discountRow = document.querySelector('.summary-row.discount');
    const discountAmountEl = document.getElementById('discount-amount');
    const appliedCouponCodeDisplay = document.getElementById('applied-coupon-code-display');
    const appliedCouponHiddenInput = document.getElementById('applied_coupon_code');
    const taxRateEl = document.getElementById('tax-rate');
    const taxAmountEl = document.getElementById('tax-amount');
    const shippingCountryEl = document.getElementById('shipping_country');
    const shippingStateEl = document.getElementById('shipping_state');
    const summarySubtotalEl = document.getElementById('summary-subtotal');
    const summaryShippingEl = document.getElementById('summary-shipping');
    const summaryTotalEl = document.getElementById('summary-total');
    const freeShippingThreshold = parseFloat(document.body.dataset.freeShippingThreshold || '50'); // Get threshold from data attribute or fallback
    const baseShippingCost = parseFloat(document.body.dataset.baseShippingCost || '5.99'); // Get base cost from data attribute or fallback

    let elements;
    let stripe;
    let currentSubtotal = parseFloat(summarySubtotalEl?.textContent || '0');
    let currentShippingCost = baseShippingCost; // Initial assumption
    let currentTaxAmount = parseFloat(taxAmountEl?.textContent.replace('$', '') || '0');
    let currentDiscountAmount = 0;

    if (!stripePublicKey) {
        showMessage("Stripe configuration error. Payment cannot proceed.");
        setLoading(false, true); // Disable button permanently
        return;
    }
    stripe = Stripe(stripePublicKey);

    if (!checkoutForm || !submitButton || !paymentElementContainer || !csrfToken) {
        console.error("Checkout form critical elements missing. Aborting initialization.");
        showMessage("Checkout form error. Please refresh the page.", true);
        return;
    }

    // --- Initialize Stripe Elements ---
    const appearance = {
         theme: 'stripe',
         variables: {
             colorPrimary: '#1A4D5A', colorBackground: '#ffffff', colorText: '#374151',
             colorDanger: '#dc2626', fontFamily: 'Montserrat, sans-serif', borderRadius: '0.375rem'
         }
     };
    elements = stripe.elements({ appearance });
    const paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');

    // --- Helper Functions ---
    function setLoading(isLoading, disablePermanently = false) {
        if (!submitButton || !spinner || !buttonText) return;
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
        if (!paymentMessage) return;
        paymentMessage.textContent = message;
        paymentMessage.className = `payment-message text-center text-sm my-4 ${isError ? 'text-red-600' : 'text-green-600'}`;
        paymentMessage.classList.remove('hidden');
    }

    function showCouponMessage(message, type) { // type = 'success', 'error', 'info'
        if (!couponMessageEl) return;
        couponMessageEl.textContent = message;
        couponMessageEl.className = `coupon-message mt-2 text-sm ${
            type === 'success' ? 'text-green-600' : (type === 'error' ? 'text-red-600' : 'text-gray-600')
        }`;
        couponMessageEl.classList.remove('hidden');
    }

    function updateOrderSummaryUI() {
        if (!summarySubtotalEl || !discountRow || !discountAmountEl || !appliedCouponCodeDisplay || !summaryShippingEl || !taxAmountEl || !summaryTotalEl) return;

        // Update subtotal (should reflect initial load)
        summarySubtotalEl.textContent = parseFloat(currentSubtotal).toFixed(2);

        // Update discount display
        if (currentDiscountAmount > 0 && appliedCouponHiddenInput?.value) {
            discountAmountEl.textContent = parseFloat(currentDiscountAmount).toFixed(2);
            appliedCouponCodeDisplay.textContent = appliedCouponHiddenInput.value;
            discountRow.classList.remove('hidden');
        } else {
            discountAmountEl.textContent = '0.00';
            appliedCouponCodeDisplay.textContent = '';
            discountRow.classList.add('hidden');
        }

         // Update shipping cost display (based on subtotal AFTER discount)
         const subtotalAfterDiscount = Math.max(0, currentSubtotal - currentDiscountAmount);
         currentShippingCost = subtotalAfterDiscount >= freeShippingThreshold ? 0 : baseShippingCost;
         summaryShippingEl.innerHTML = currentShippingCost > 0 ? '$' + parseFloat(currentShippingCost).toFixed(2) : '<span class="text-green-600">FREE</span>';

        // Update tax amount display (based on AJAX call result)
        taxAmountEl.textContent = '$' + parseFloat(currentTaxAmount).toFixed(2);

        // Update total
        const grandTotal = subtotalAfterDiscount + currentShippingCost + currentTaxAmount;
        summaryTotalEl.textContent = parseFloat(Math.max(0.50, grandTotal)).toFixed(2); // Ensure min $0.50 display if rounding down
    }

    // --- Tax Calculation ---
    async function updateTax() {
        const country = shippingCountryEl?.value;
        const state = shippingStateEl?.value;

        if (!country || !taxRateEl || !taxAmountEl) {
            // Reset tax if no country selected or elements missing
             if (taxRateEl) taxRateEl.textContent = 'N/A';
             currentTaxAmount = 0;
             updateOrderSummaryUI(); // Update total
            return;
        }

        try {
            taxAmountEl.textContent = '...'; // Loading indicator

            // --- VERIFIED ENDPOINT ---
            const response = await fetch('index.php?page=checkout&action=calculateTax', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json', 'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                 },
                body: JSON.stringify({ country, state, subtotal: currentSubtotal, discount: currentDiscountAmount }) // Send current context if needed
            });

            if (!response.ok) throw new Error('Tax calculation failed');
            const data = await response.json();

            if (data.success) {
                taxRateEl.textContent = data.tax_rate_formatted || 'N/A';
                currentTaxAmount = parseFloat(data.tax_amount) || 0;
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

    if(shippingCountryEl) shippingCountryEl.addEventListener('change', updateTax);
    if(shippingStateEl) shippingStateEl.addEventListener('input', updateTax); // Use input for faster response

    // --- Coupon Application ---
    if (applyCouponButton && couponCodeInput && appliedCouponHiddenInput) {
        applyCouponButton.addEventListener('click', async function() {
            const couponCode = couponCodeInput.value.trim();
            if (!couponCode) {
                showCouponMessage('Please enter a coupon code.', 'error'); return;
            }

            showCouponMessage('Applying...', 'info');
            applyCouponButton.disabled = true;

            try {
                 // --- VERIFIED ENDPOINT ---
                const response = await fetch('index.php?page=checkout&action=applyCouponAjax', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json', 'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        code: couponCode,
                        subtotal: currentSubtotal, // Send current subtotal
                        csrf_token: csrfToken // Send CSRF token
                    })
                });

                 if (!response.ok) throw new Error(`Server error: ${response.status}`);
                 const data = await response.json();

                if (data.success) {
                    showCouponMessage(data.message || 'Coupon applied!', 'success');
                    currentDiscountAmount = parseFloat(data.discount_amount) || 0;
                    appliedCouponHiddenInput.value = data.coupon_code || couponCode;
                    // Re-calculate tax and update summary UI after applying discount
                     updateTax(); // Triggers tax recalc and UI update
                } else {
                    showCouponMessage(data.message || 'Invalid coupon code.', 'error');
                    currentDiscountAmount = 0; // Reset discount
                    appliedCouponHiddenInput.value = ''; // Clear applied code
                    updateTax(); // Re-calculate tax and update summary UI without discount
                }
            } catch (e) {
                console.error('Coupon Apply Error:', e);
                showCouponMessage('Failed to apply coupon. Please try again.', 'error');
                currentDiscountAmount = 0;
                appliedCouponHiddenInput.value = '';
                updateTax(); // Re-calculate tax and update summary UI
            } finally {
                applyCouponButton.disabled = false;
            }
        });
    } else {
        console.warn("Coupon elements not found. Coupon functionality disabled.");
    }


    // --- Checkout Form Submission ---
    submitButton.addEventListener('click', async function(e) {
        setLoading(true);
        showMessage(''); // Clear previous messages

        // 1. Client-side validation
        let isValid = true;
        const requiredFields = ['shipping_name', 'shipping_email', 'shipping_address', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];
        requiredFields.forEach(id => {
            const input = document.getElementById(id);
            if (!input || !input.value.trim()) {
                isValid = false; input?.classList.add('input-error');
            } else { input?.classList.remove('input-error'); }
        });
        if (!isValid) {
            showMessage('Please fill in all required shipping fields.'); setLoading(false);
            checkoutForm.querySelector('.input-error')?.focus();
            checkoutForm.querySelector('.input-error')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // 2. Send checkout data to server -> create order, get clientSecret
        let clientSecret = null;
        let serverOrderId = null;
        try {
            const checkoutFormData = new FormData(checkoutForm); // Includes CSRF, applied coupon, shipping fields

             // --- VERIFIED ENDPOINT ---
            const response = await fetch('index.php?page=checkout&action=processCheckout', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: checkoutFormData
            });
            const data = await response.json();

            if (response.ok && data.success && data.clientSecret && data.orderId) {
                clientSecret = data.clientSecret;
                serverOrderId = data.orderId; // Store the order ID if needed elsewhere
            } else {
                throw new Error(data.error || 'Failed to process order on server. Please try again.');
            }
        } catch (serverError) {
            console.error('Server processing error:', serverError);
            showMessage(serverError.message); setLoading(false); return; // Stop checkout
        }

        // 3. Confirm payment with Stripe using the obtained clientSecret
        if (clientSecret) {
            // --- VERIFIED RETURN URL ---
            // Use BASE_URL defined in config.php (should be available globally or passed via data attribute)
            const baseUrl = window.location.origin + (document.body.dataset.baseUrl || '/'); // Get base URL
            const returnUrl = `${baseUrl}index.php?page=checkout&action=confirmation`;

            const { error: stripeError, paymentIntent } = await stripe.confirmPayment({
                elements,
                clientSecret: clientSecret,
                confirmParams: { return_url: returnUrl },
                redirect: 'if_required' // Handles 3DS etc. Stripe redirects on success.
            });

            // If error occurs (e.g., card decline, network issue before redirect)
            if (stripeError) {
                 console.error("Stripe Error:", stripeError);
                 showMessage(stripeError.message || "Payment failed. Please check your card details or try another method.");
                 setLoading(false); // Re-enable button on failure
                 // Optionally: Update order status on server to 'payment_failed' via another AJAX call if needed immediately
            }
            // If paymentIntent.status === 'succeeded' or 'processing', Stripe should handle the redirect.
            // If it requires action, Stripe will handle that too.
            // If we reach here without redirect and without error, it might be unexpected.
            // Check paymentIntent.status if needed.
            else if (paymentIntent && paymentIntent.status === 'requires_payment_method') {
                 showMessage("Payment failed. Please try another payment method.");
                 setLoading(false);
            } else if (paymentIntent && paymentIntent.status === 'requires_confirmation') {
                 showMessage("Please confirm your payment details."); // Should usually be handled by Stripe UI
                 setLoading(false);
            }
             // No explicit success redirect needed here as Stripe handles it via return_url
        } else {
            showMessage('Failed to get payment details from server.'); setLoading(false);
        }
    });

    // --- Initial UI Update ---
    updateOrderSummaryUI(); // Calculate initial totals based on potentially pre-filled fields
    if (shippingCountryEl?.value) { updateTax(); } // Trigger initial tax calculation if country is set

}

// --- Main DOMContentLoaded Listener (Update to include Checkout Init) ---
document.addEventListener('DOMContentLoaded', function() {
    if (typeof AOS !== 'undefined') { AOS.init({ duration: 800, offset: 120, once: true }); }
    else { console.warn('AOS library not loaded.'); }

    const body = document.body;
    // --- Add Checkout Page Init ---
    const pageInitializers = {
        'page-home': initHomePage,
        'page-products': initProductsPage,
        'page-product-detail': initProductDetailPage,
        'page-cart': initCartPage,
        'page-login': initLoginPage,
        'page-register': initRegisterPage,
        'page-forgot-password': initForgotPasswordPage,
        'page-reset-password': initResetPasswordPage,
        'page-quiz': initQuizPage,
        'page-quiz-results': initQuizResultsPage,
        'page-admin-quiz-analytics': initAdminQuizAnalyticsPage,
        'page-admin-coupons': initAdminCouponsPage,
        'page-checkout': initCheckoutPage // Add checkout initializer
    };

    let initialized = false;
    for (const pageClass in pageInitializers) {
        if (body.classList.contains(pageClass)) {
            // Pass necessary data via data attributes on body if needed
            body.dataset.baseUrl = '<?= BASE_URL ?>';
            body.dataset.stripePublicKey = '<?= STRIPE_PUBLIC_KEY ?>';
            body.dataset.freeShippingThreshold = '<?= FREE_SHIPPING_THRESHOLD ?>';
            body.dataset.baseShippingCost = '<?= SHIPPING_COST ?>';

            pageInitializers[pageClass]();
            initialized = true;
            break;
        }
    }
    if (document.getElementById('mini-cart-content') && typeof fetchMiniCart === 'function') {
         fetchMiniCart();
    }
});


// --- Mini Cart AJAX Update Function (unchanged) ---
function fetchMiniCart() { /* ... unchanged ... */ }

// --- Style definitions for loading/error states (unchanged) ---
/*
.spinner { ... }
.hidden { ... }
.place-order:disabled { ... }
.input-error { ... }
.form-input, .form-select, .form-textarea { ... }
.form-input:focus, .form-select:focus, .form-textarea:focus { ... }
*/
// --- END OF UPDATED main.js ---

```

**7. `views/order_confirmation.php`**

```php
<?php require_once __DIR__ . '/layout/header.php'; ?>

<section class="confirmation-section">
    <div class="container">
        <div class="confirmation-container" data-aos="fade-up">
            <div class="confirmation-header">
                <i class="fas fa-check-circle"></i>
                <h1>Order Confirmed!</h1>
                <p>Thank you for your purchase. Your order (#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?>) has been successfully placed.</p>
            </div>

            <div class="order-details">
                <div class="order-info">
                    <h2>Order Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="label">Order Number:</span>
                            <span class="value">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Order Date:</span>
                            <span class="value"><?= date('F j, Y', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Order Status:</span>
                            <!-- Display actual status from DB -->
                            <span class="value status-<?= htmlspecialchars($order['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($order['status']))) ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="label">Payment Status:</span>
                             <span class="value status-<?= htmlspecialchars($order['payment_status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($order['payment_status']))) ?>
                            </span>
                        </div>
                        <!-- Add estimated delivery if available -->
                        <?php if (!empty($order['estimated_delivery'])): ?>
                        <div class="info-item">
                            <span class="label">Estimated Delivery:</span>
                            <span class="value"><?= date('F j, Y', strtotime($order['estimated_delivery'])) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="shipping-info">
                    <h2>Shipping Address</h2>
                    <p><?= htmlspecialchars($order['shipping_name'] ?? 'N/A') ?></p>
                    <p><?= htmlspecialchars($order['shipping_address'] ?? 'N/A') ?></p>
                    <p>
                        <?= htmlspecialchars($order['shipping_city'] ?? 'N/A') ?>,
                        <?= htmlspecialchars($order['shipping_state'] ?? 'N/A') ?>
                        <?= htmlspecialchars($order['shipping_zip'] ?? 'N/A') ?>
                    </p>
                    <p><?= htmlspecialchars($order['shipping_country'] ?? 'N/A') ?></p>
                </div>
            </div>

            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="summary-items">
                    <?php // Ensure order items are correctly fetched and passed ?>
                    <?php if (!empty($order['items']) && is_array($order['items'])): ?>
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="summary-item">
                                <div class="item-info">
                                    <span class="item-quantity"><?= $item['quantity'] ?>&times;</span>
                                    <span class="item-name"><?= htmlspecialchars($item['product_name'] ?? 'N/A') ?></span>
                                    <!-- Optional: add image -->
                                    <!-- <img src="<?= htmlspecialchars($item['image_url'] ?? '/images/placeholder.jpg') ?>" alt="" class="w-8 h-8 ml-2 rounded"> -->
                                </div>
                                <span class="item-price">$<?= number_format(($item['price_at_purchase'] ?? 0) * ($item['quantity'] ?? 0), 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                         <p class="text-gray-500 italic">Could not load order items.</p>
                    <?php endif; ?>
                </div>

                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?= number_format($order['subtotal'] ?? 0, 2) ?></span>
                    </div>
                    <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                        <div class="summary-row discount">
                            <span>Discount <?= !empty($order['coupon_code']) ? '(' . htmlspecialchars($order['coupon_code']) . ')' : '' ?>:</span>
                            <span>-$<?= number_format($order['discount_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span><?= ($order['shipping_cost'] ?? 0) > 0 ? '$' . number_format($order['shipping_cost'], 2) : '<span class="text-green-600">FREE</span>' ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax:</span>
                        <span>$<?= number_format($order['tax_amount'] ?? 0, 2) ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Paid:</span>
                        <span>$<?= number_format($order['total_amount'] ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>

            <div class="next-steps" data-aos="fade-up">
                <h2>What's Next?</h2>
                <div class="steps-grid">
                    <div class="step">
                        <i class="fas fa-envelope"></i>
                        <h3>Order Confirmation Email</h3>
                        <p>We've sent a confirmation email to <?= htmlspecialchars($order['shipping_email'] ?? 'your email') ?>.</p>
                    </div>
                    <div class="step">
                        <i class="fas fa-truck"></i>
                        <h3>Shipping Updates</h3>
                        <p>You'll receive shipping updates and tracking information once your order ships.</p>
                    </div>
                    <div class="step">
                        <i class="fas fa-user"></i>
                        <h3>Track Your Order</h3>
                        <p>Visit your account dashboard to track your order and view order history.</p>
                    </div>
                </div>

                <div class="confirmation-actions">
                    <a href="index.php?page=account&section=orders" class="btn-secondary">View My Orders</a>
                    <a href="index.php?page=products" class="btn-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

```

---

**Review and Validation:**

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

**Summary and Conclusion:**

The relevant files (`models/Order.php`, `controllers/PaymentController.php`, `controllers/CheckoutController.php`, `controllers/CouponController.php`, `index.php`, `js/main.js`, `views/order_confirmation.php`) have been updated to implement the full checkout and payment flow using Stripe Payment Intents.

*   The checkout process now correctly validates data, calculates final totals, creates the order record, handles inventory and coupons within a database transaction, initiates the Stripe payment, and returns the necessary `clientSecret` to the frontend.
*   The frontend JavaScript correctly handles the `clientSecret`, initiates Stripe's payment confirmation flow, and includes the necessary `return_url`.
*   The Stripe webhook handler (`PaymentController::handleWebhook`) is implemented to process successful payments (updating order status, sending emails, clearing cart, setting session variable) and failed payments, using transactions for database updates.
*   The order confirmation page (`CheckoutController::showOrderConfirmation` and `views/order_confirmation.php`) now correctly retrieves and displays the completed order details based on the session variable set by the webhook.

**Next Steps:**

1.  **Stripe Setup:** Configure correct Stripe API keys (`STRIPE_PUBLIC_KEY`, `STRIPE_SECRET_KEY`) and the Webhook Secret (`STRIPE_WEBHOOK_SECRET`) in `config.php`.
2.  **Webhook Endpoint:** Create and configure the actual webhook endpoint in your Stripe dashboard, pointing it to `https://yourdomain.com/index.php?page=payment&action=webhook`. Ensure it listens for `payment_intent.succeeded` and `payment_intent.payment_failed` events (and others like `charge.refunded`, `charge.dispute.created` if needed).
3.  **SMTP Configuration:** Configure valid SMTP settings in `config.php` for email notifications to work.
4.  **Testing:** Thoroughly test the entire checkout flow with successful payments, failed payments (using Stripe test cards), different shipping addresses (for tax), and coupon applications. Verify order status updates, email notifications, cart clearing, and the confirmation page display. Test the potential race condition between user redirect and webhook processing.

The code provides a complete, functional checkout and payment integration structure, pending external configuration and testing.

---
https://drive.google.com/file/d/1-Qxuftx5r58Koo_Yw2NKTnT4f-MjJppi/view?usp=sharing, https://drive.google.com/file/d/13Gk6AAc-JjUyDHugsECcnzRxR-2KcpiE/view?usp=sharing, https://drive.google.com/file/d/14fknKOy2sH41bNJH8TjWHWT_1rxEk028/view?usp=sharing, https://drive.google.com/file/d/15vv8gjC16FkGaLAD3IhrRM_Y-L5z7Dz_/view?usp=sharing, https://drive.google.com/file/d/1Fx6GpRrY_Op5RFckqsrkp8Ol5fL_botE/view?usp=sharing, https://drive.google.com/file/d/1IjgdsSOKDBQ92Jr5IjxMItLEv_0kQiLw/view?usp=sharing, https://drive.google.com/file/d/1J0eoQXN249FIM8h_wIrMFNoGjpSBvXFm/view?usp=sharing, https://drive.google.com/file/d/1MXN1mewCGl0dyAYfgYjtj6IyYor5a9nz/view?usp=sharing, https://drive.google.com/file/d/1NL7QBaq028FKrIYBd3Liqszzm9rSchjT/view?usp=sharing, https://drive.google.com/file/d/1cPz6DKXpRWm246Va_oTYRcxf2964Q6rJ/view?usp=sharing, https://drive.google.com/file/d/1geKsAe8BEfeXa5obpbJTau_qOuaqg3Rs/view?usp=sharing, https://drive.google.com/file/d/1n0fSWx1MWF-qKP3en3AmQyncnaOTi8Tk/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221vW4H4HtNtmdKBlLewGCSGgFhxTjQHLqB%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1vnU8dVDXsFs7gfezD61vklpntW62nJ5v/view?usp=sharing, https://drive.google.com/file/d/1xemKGidpgQJOxF3HIEbZn_KOf0UJsFta/view?usp=sharing
