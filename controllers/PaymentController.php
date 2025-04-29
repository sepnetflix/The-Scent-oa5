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
