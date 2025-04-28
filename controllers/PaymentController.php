<?php
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

    public function __construct($pdo = null) {
        parent::__construct($pdo); // BaseController handles EmailService init now

        // Ensure PDO is available if needed directly or via BaseController
        if (!$this->db) {
             error_log("PDO connection not available in PaymentController constructor.");
             // Handle appropriately - maybe throw exception
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
            // Set API key globally (optional but common practice)
            // Stripe::setApiKey(STRIPE_SECRET_KEY); // Uncomment if preferred over instance key
            $this->stripe = new StripeClient(STRIPE_SECRET_KEY);
            $this->webhookSecret = STRIPE_WEBHOOK_SECRET;
        } catch (\Exception $e) {
             error_log("Failed to initialize Stripe client: " . $e->getMessage());
             $this->stripe = null; // Ensure stripe is null if init fails
             $this->webhookSecret = null;
             // Consider throwing Exception("Payment system configuration error.");
        }
    }

    /**
     * Create a Stripe Payment Intent.
     *
     * @param float $amount Amount in major currency unit (e.g., dollars).
     * @param string $currency 3-letter ISO currency code.
     * @param int $orderId Internal order ID for metadata.
     * @param string $customerEmail Email for receipt/customer matching.
     * @return array ['success' => bool, 'client_secret' => string|null, 'payment_intent_id' => string|null, 'error' => string|null]
     */
    public function createPaymentIntent(float $amount, string $currency = 'usd', int $orderId = 0, string $customerEmail = ''): array {
        // Ensure Stripe client is initialized
        if (!$this->stripe) {
             return ['success' => false, 'error' => 'Payment system unavailable.'];
        }

        // Prepare parameters (moved inside try block)
        $paymentIntentParams = [];

        try {
            // Basic validation
            if ($amount <= 0) {
                throw new InvalidArgumentException('Invalid payment amount.');
            }
            $currency = strtolower(trim($currency));
            if (strlen($currency) !== 3) {
                 throw new InvalidArgumentException('Invalid currency code.');
            }
            if ($orderId <= 0) {
                 throw new InvalidArgumentException('Invalid Order ID for Payment Intent.');
            }

            $paymentIntentParams = [
                'amount' => (int)round($amount * 100), // Convert to cents
                'currency' => $currency,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'user_id' => $this->getUserId() ?? 'guest',
                    'order_id' => $orderId,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
                ]
            ];

             if (!empty($customerEmail)) {
                 $paymentIntentParams['receipt_email'] = $customerEmail;
             }

            $paymentIntent = $this->stripe->paymentIntents->create($paymentIntentParams);

            // --- MODIFIED: Return Payment Intent ID ---
            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id // Include the Payment Intent ID
            ];
            // --- END MODIFICATION ---

        } catch (ApiErrorException $e) {
            error_log("Stripe API Error creating PaymentIntent: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
            return [
                'success' => false,
                'error' => 'Payment processing failed. Please try again or contact support.',
                'client_secret' => null,
                'payment_intent_id' => null
            ];
        } catch (InvalidArgumentException $e) { // Catch specific validation errors
             error_log("Payment Intent Creation Invalid Argument: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
             return [
                 'success' => false,
                 'error' => $e->getMessage(), // Show specific validation error
                 'client_secret' => null,
                 'payment_intent_id' => null
             ];
         } catch (Exception $e) {
            error_log("Payment Intent Creation Error: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
            return [
                'success' => false,
                'error' => 'Could not initialize payment. Please try again later.', // Generic internal error
                'client_secret' => null,
                'payment_intent_id' => null
            ];
        }
    }


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
             // Use jsonResponse for consistency
             $this->jsonResponse(['error' => 'Missing signature'], 400); // Exit handled by jsonResponse
             return; // For clarity, though exit happens
        }
        if (empty($payload)) {
             error_log("Webhook Error: Empty payload received.");
             $this->jsonResponse(['error' => 'Empty payload'], 400);
             return;
        }


        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $this->webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            error_log("Webhook Error: Invalid payload. " . $e->getMessage());
            $this->jsonResponse(['error' => 'Invalid payload'], 400);
            return;
        } catch (SignatureVerificationException $e) {
            error_log("Webhook Error: Invalid signature. " . $e->getMessage());
            $this->jsonResponse(['error' => 'Invalid signature'], 400);
            return;
        } catch (\Exception $e) {
            error_log("Webhook Error: Event construction failed. " . $e->getMessage());
            $this->jsonResponse(['error' => 'Webhook processing error'], 400);
            return;
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
                     $this->handleChargeSucceeded($event->data->object);
                     break;

                case 'charge.dispute.created':
                    $this->handleDisputeCreated($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleRefund($event->data->object);
                    break;

                // Add other event types as needed

                default:
                    error_log('Webhook Warning: Received unhandled event type ' . $event->type);
            }

            $this->commit(); // Commit DB changes if no exceptions
            $this->jsonResponse(['success' => true, 'message' => 'Webhook received']); // Exit handled by jsonResponse

        } catch (Exception $e) {
            $this->rollback(); // Rollback DB changes on error
            error_log("Webhook Handling Error (Event: {$event->type}): " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            $this->jsonResponse(
                ['success' => false, 'error' => 'Internal server error handling webhook.'],
                500 // Use 500 for internal errors where retry might help
            ); // Exit handled by jsonResponse
        }
    }

    private function handleSuccessfulPayment(\Stripe\PaymentIntent $paymentIntent): void {
         // Find order by payment_intent_id using OrderModel
         $order = $this->orderModel->getByPaymentIntentId($paymentIntent->id); // Assume this method exists

         if (!$order) {
              $errorMessage = "Webhook Critical: PaymentIntent {$paymentIntent->id} succeeded but no matching order found.";
              error_log($errorMessage);
              $this->logSecurityEvent('webhook_order_mismatch', ['payment_intent_id' => $paymentIntent->id, 'event_type' => 'payment_intent.succeeded']);
              // Do not throw exception to acknowledge webhook receipt, but log heavily.
              return;
         }

         // Idempotency check
         if (in_array($order['status'], ['paid', 'processing', 'shipped', 'delivered', 'completed'])) { // Added 'processing'
             error_log("Webhook Info: Received successful payment event for already processed order ID {$order['id']}. Status: {$order['status']}");
             return;
         }

        // Update order status using OrderModel
        $updated = $this->orderModel->updateStatus($order['id'], 'paid'); // Or 'processing'

        if (!$updated) {
            // Maybe the status was already updated by another process? Re-fetch and check again before throwing.
            $currentOrder = $this->orderModel->getById($order['id']);
            if (!$currentOrder || !in_array($currentOrder['status'], ['paid', 'processing', 'shipped', 'delivered', 'completed'])) {
                throw new Exception("Failed to update order ID {$order['id']} payment status to 'paid'.");
            } else {
                 error_log("Webhook Info: Order ID {$order['id']} status already updated, skipping redundant update.");
            }
        } else {
             // --- MODIFIED: Set last_order_id in session ---
             // Ensure session is started (it should be by SecurityMiddleware::apply)
             if (session_status() === PHP_SESSION_ACTIVE) {
                 $_SESSION['last_order_id'] = $order['id'];
             } else {
                 error_log("Webhook Warning: Session not active, cannot set last_order_id for order {$order['id']}");
             }
             // --- END MODIFICATION ---
             error_log("Webhook Success: Updated order ID {$order['id']} status to 'paid' for PaymentIntent {$paymentIntent->id}. Session last_order_id set.");
        }


        // Fetch full details for email (or enhance getByPaymentIntentId to return user info)
        $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $order['user_id']); // Use existing method

        if ($fullOrder) {
             // Send payment confirmation email
             if ($this->emailService && method_exists($this->emailService, 'sendOrderConfirmation')) {
                  // Prepare user data array structure if needed by sendOrderConfirmation
                  $userModel = new User($this->db);
                  $user = $userModel->getById($fullOrder['user_id']);
                  if ($user) {
                       $this->emailService->sendOrderConfirmation($fullOrder, $user); // Pass user data if needed
                       error_log("Webhook Success: Order confirmation email queued for order ID {$fullOrder['id']}.");
                  } else {
                       error_log("Webhook Warning: Could not fetch user data for order confirmation email (Order ID: {$fullOrder['id']}).");
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
                $cartModel = new Cart($this->db, $order['user_id']);
                $cartModel->clearCart();
                error_log("Webhook Success: Cart cleared for user ID {$order['user_id']} after order {$order['id']} payment.");
            } catch (Exception $cartError) {
                 error_log("Webhook Warning: Failed to clear cart for user ID {$order['user_id']} after order {$order['id']} payment: " . $cartError->getMessage());
            }
        }
    }

    // --- Other Webhook Handlers (handleFailedPayment, handleChargeSucceeded, etc.) ---
    // These remain largely the same, but should ideally use OrderModel methods for updates.

    private function handleFailedPayment(\Stripe\PaymentIntent $paymentIntent): void {
         $order = $this->orderModel->getByPaymentIntentId($paymentIntent->id);
         if (!$order) {
              error_log("Webhook Warning: PaymentIntent {$paymentIntent->id} failed but no matching order found.");
              return;
         }
          if (in_array($order['status'], ['cancelled', 'paid', 'shipped', 'delivered', 'completed'])) {
              error_log("Webhook Info: Received failed payment event for already resolved order ID {$order['id']}.");
              return;
          }

        $updated = $this->orderModel->updateStatus($order['id'], 'payment_failed');
        if (!$updated) {
             // Re-fetch and check
            $currentOrder = $this->orderModel->getById($order['id']);
            if (!$currentOrder || $currentOrder['status'] !== 'payment_failed') {
                 throw new Exception("Failed to update order ID {$order['id']} status to 'payment_failed'.");
            }
        } else {
            error_log("Webhook Info: Updated order ID {$order['id']} status to 'payment_failed' for PaymentIntent {$paymentIntent->id}.");
        }

        // Send payment failed notification (fetch full order details first)
        $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $order['user_id']);
        if ($fullOrder) {
             if ($this->emailService && method_exists($this->emailService, 'sendPaymentFailedNotification')) {
                  $userModel = new User($this->db);
                  $user = $userModel->getById($fullOrder['user_id']);
                  if ($user) {
                        // Assuming sendPaymentFailedNotification takes order and user arrays
                       $this->emailService->sendPaymentFailedNotification($fullOrder, $user);
                       error_log("Webhook Info: Payment failed email queued for order ID {$fullOrder['id']}.");
                  } else {
                       error_log("Webhook Warning: Could not fetch user data for failed payment email (Order ID: {$fullOrder['id']}).");
                  }

             } else {
                  error_log("Webhook Warning: EmailService or sendPaymentFailedNotification method not available for order ID {$fullOrder['id']}.");
             }
        } else {
             error_log("Webhook Warning: Could not fetch full order details for failed payment notification (Order ID: {$order['id']}).");
        }
    }

     private function handleChargeSucceeded(\Stripe\Charge $charge): void {
         error_log("Webhook Info: Charge {$charge->id} succeeded for PaymentIntent {$charge->payment_intent} (Order linked via PI).");
     }

    private function handleDisputeCreated(\Stripe\Dispute $dispute): void {
        $order = $this->orderModel->getByPaymentIntentId($dispute->payment_intent);
         if (!$order) {
              error_log("Webhook Warning: Dispute {$dispute->id} created for PaymentIntent {$dispute->payment_intent} but no matching order found.");
              return;
         }

        // Update order status and store dispute ID using OrderModel
        $updated = $this->orderModel->updateStatusAndDispute($order['id'], 'disputed', $dispute->id); // Assume method exists

        if (!$updated) {
             // Re-fetch and check
            $currentOrder = $this->orderModel->getById($order['id']);
            if (!$currentOrder || $currentOrder['status'] !== 'disputed') {
                 throw new Exception("Failed to update order ID {$order['id']} dispute status.");
            }
        } else {
             error_log("Webhook Alert: Order ID {$order['id']} status updated to 'disputed' due to Dispute {$dispute->id}.");
        }

        // Log and alert admin (existing logic is okay)
        $this->logSecurityEvent('stripe_dispute_created', [ /* ... */ ]);
        if ($this->emailService && method_exists($this->emailService, 'sendAdminDisputeAlert')) {
             $this->emailService->sendAdminDisputeAlert($order['id'], $dispute->id, $dispute->reason, $dispute->amount);
        }
    }

    private function handleRefund(\Stripe\Charge $charge): void {
         $refund = $charge->refunds->data[0] ?? null;
         if (!$refund) {
             error_log("Webhook Warning: Received charge.refunded event for Charge {$charge->id} but no refund data found.");
             return;
         }

         $order = $this->orderModel->getByPaymentIntentId($charge->payment_intent);
         if (!$order) {
              error_log("Webhook Warning: Refund {$refund->id} processed for PaymentIntent {$charge->payment_intent} but no matching order found.");
              return;
         }

          $newStatus = 'refunded';
          $paymentStatus = ($charge->amount_refunded === $charge->amount) ? 'refunded' : 'partially_refunded';

          // Update using OrderModel
         $updated = $this->orderModel->updateRefundStatus($order['id'], $newStatus, $paymentStatus, $refund->id); // Assume method exists

        if (!$updated) {
             // Re-fetch and check
             $currentOrder = $this->orderModel->getById($order['id']);
             if (!$currentOrder || !in_array($currentOrder['status'], ['refunded', 'partially_refunded'])) { // Check possible statuses
                 throw new Exception("Failed to update order ID {$order['id']} refund status.");
             }
        } else {
            error_log("Webhook Info: Order ID {$order['id']} status updated to '{$newStatus}' due to Refund {$refund->id}.");
        }

        // Send refund confirmation email (fetch full order details first)
        $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $order['user_id']);
        if ($fullOrder) {
             if ($this->emailService && method_exists($this->emailService, 'sendRefundConfirmation')) {
                   $userModel = new User($this->db);
                   $user = $userModel->getById($fullOrder['user_id']);
                   if ($user) {
                        $this->emailService->sendRefundConfirmation($fullOrder, $user, $refund->amount / 100.0); // Pass user array if needed
                        error_log("Webhook Info: Refund confirmation email queued for order ID {$fullOrder['id']}.");
                   } else {
                        error_log("Webhook Warning: Could not fetch user for refund confirmation email (Order ID: {$fullOrder['id']}).");
                   }

             } else {
                  error_log("Webhook Warning: EmailService or sendRefundConfirmation method not available for order ID {$fullOrder['id']}.");
             }
        } else {
             error_log("Webhook Warning: Could not fetch full order details for refund notification (Order ID: {$order['id']}).");
        }
    }
}
