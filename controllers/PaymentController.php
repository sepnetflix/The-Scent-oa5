<?php
require_once __DIR__ . '/BaseController.php';
// Remove this line: require_once __DIR__ . '/../vendor/autoload.php'; // Now loaded globally
require_once __DIR__ . '/../config.php'; // Keep config include

// Use statement for StripeClient if not already present
use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;


class PaymentController extends BaseController {
    private $stripe;
    private $webhookSecret;

    public function __construct($pdo = null) {
        parent::__construct($pdo); // BaseController handles EmailService init now

        // Ensure Stripe keys are defined
        if (!defined('STRIPE_SECRET_KEY') || !defined('STRIPE_WEBHOOK_SECRET')) {
            error_log("Stripe keys are not defined in config.php");
            // Depending on context, might throw an exception or handle gracefully
            // For now, let StripeClient constructor potentially fail if key is missing
        }

        // Use try-catch for external service initialization
        try {
            $this->stripe = new StripeClient(STRIPE_SECRET_KEY);
            $this->webhookSecret = STRIPE_WEBHOOK_SECRET;
        } catch (\Exception $e) {
             error_log("Failed to initialize Stripe client: " . $e->getMessage());
             // Handle error appropriately, maybe throw exception to be caught by ErrorHandler
             // throw new Exception("Payment system configuration error.");
             $this->stripe = null; // Ensure stripe is null if init fails
             $this->webhookSecret = null;
        }
    }

    public function createPaymentIntent(float $amount, string $currency = 'usd', int $orderId = 0, string $customerEmail = '') {
        // Ensure Stripe client is initialized
        if (!$this->stripe) {
             return ['success' => false, 'error' => 'Payment system unavailable.'];
        }

        try {
            // Basic validation (more robust validation might be needed)
            if ($amount <= 0) {
                throw new Exception('Invalid payment amount.');
            }
            $currency = strtolower(trim($currency));
            if (strlen($currency) !== 3) {
                 throw new Exception('Invalid currency code.');
            }

            $paymentIntentParams = [
                'amount' => (int)round($amount * 100), // Convert to cents, use round() for precision
                'currency' => $currency,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'user_id' => $this->getUserId() ?? 'guest',
                    'order_id' => $orderId, // Include order ID
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
                ]
            ];

             // Add customer email if provided (helps Stripe Radar, guest checkout)
             if (!empty($customerEmail)) {
                 // Optional: Find or create Stripe Customer ID first for better tracking
                 // $paymentIntentParams['customer'] = $this->getStripeCustomerId($customerEmail);
                 // Or just add receipt email
                 $paymentIntentParams['receipt_email'] = $customerEmail;
             }


            $paymentIntent = $this->stripe->paymentIntents->create($paymentIntentParams);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret // Correct key name
            ];

        } catch (ApiErrorException $e) {
            error_log("Stripe API Error creating PaymentIntent: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
            return [
                'success' => false,
                'error' => 'Payment processing failed. Please try again or contact support.' // User-friendly
            ];
        } catch (Exception $e) {
            error_log("Payment Intent Creation Error: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
            return [
                'success' => false,
                'error' => 'Invalid payment request. Please check details and try again.' // User-friendly
            ];
        }
    }

    // processPayment seems deprecated by the CheckoutController logic which calls createPaymentIntent directly
    // If needed, it should be updated to fit the current checkout flow.
    /*
    public function processPayment($orderId) {
        // ... (Needs review based on actual usage) ...
    }
    */

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
             return $this->jsonResponse(['error' => 'Missing signature'], 400);
        }
        if (empty($payload)) {
             error_log("Webhook Error: Empty payload received.");
             return $this->jsonResponse(['error' => 'Empty payload'], 400);
        }


        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $this->webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            error_log("Webhook Error: Invalid payload. " . $e->getMessage());
            return $this->jsonResponse(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            error_log("Webhook Error: Invalid signature. " . $e->getMessage());
            return $this->jsonResponse(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            // Other construction error
            error_log("Webhook Error: Event construction failed. " . $e->getMessage());
            return $this->jsonResponse(['error' => 'Webhook processing error'], 400);
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
                     // Often redundant if using Payment Intents, but can be useful for logging charge details
                     $this->handleChargeSucceeded($event->data->object);
                     break;

                case 'charge.dispute.created':
                    $this->handleDisputeCreated($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleRefund($event->data->object);
                    break;

                // Add other event types as needed (e.g., checkout.session.completed for Stripe Checkout)

                default:
                    error_log('Webhook Warning: Received unhandled event type ' . $event->type);
            }

            $this->commit(); // Commit DB changes if no exceptions
            return $this->jsonResponse(['success' => true, 'message' => 'Webhook received']);

        } catch (Exception $e) {
            $this->rollback(); // Rollback DB changes on error
            error_log("Webhook Handling Error (Event: {$event->type}): " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            // Return 500 to signal Stripe to retry (if applicable for the error type)
            return $this->jsonResponse(
                ['success' => false, 'error' => 'Internal server error handling webhook.'],
                500 // Use 500 for internal errors where retry might help
            );
        }
    }

    private function handleSuccessfulPayment(\Stripe\PaymentIntent $paymentIntent): void {
        // Find order by payment_intent_id (ensure this column exists and is set)
         $stmt = $this->db->prepare("
             SELECT id, user_id, total_amount, status
             FROM orders
             WHERE payment_intent_id = ?
             LIMIT 1
         ");
         $stmt->execute([$paymentIntent->id]);
         $order = $stmt->fetch();

         if (!$order) {
              // Critical: Payment succeeded but no matching order found in DB
              $errorMessage = "Webhook Critical: PaymentIntent {$paymentIntent->id} succeeded but no matching order found.";
              error_log($errorMessage);
              $this->logSecurityEvent('webhook_order_mismatch', ['payment_intent_id' => $paymentIntent->id, 'event_type' => 'payment_intent.succeeded']);
              // Do not throw exception here to acknowledge webhook receipt, but log heavily.
              return; // Acknowledge webhook, but log the mismatch
         }

         // Prevent processing already completed orders
         if (in_array($order['status'], ['paid', 'shipped', 'delivered', 'completed'])) {
             error_log("Webhook Info: Received successful payment event for already processed order ID {$order['id']}.");
             return; // Idempotency: Already handled
         }

        $updateStmt = $this->db->prepare("
            UPDATE orders
            SET status = 'paid', -- Or 'processing' if that's your next step
                payment_status = 'completed',
                paid_at = NOW(),
                updated_at = NOW()
            WHERE id = ? AND payment_intent_id = ?
        ");

        if (!$updateStmt->execute([$order['id'], $paymentIntent->id])) {
            throw new Exception("Failed to update order ID {$order['id']} payment status to 'paid'.");
        }
         error_log("Webhook Success: Updated order ID {$order['id']} status to 'paid' for PaymentIntent {$paymentIntent->id}.");


        // Fetch order details again for notification (or use $order if it has user info)
        $notifyStmt = $this->db->prepare("
            SELECT o.*, u.email, u.name as user_name
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $notifyStmt->execute([$order['id']]);
        $fullOrder = $notifyStmt->fetch();

        if ($fullOrder) {
            // Send payment confirmation email
             // Ensure EmailService is available and method exists
             if ($this->emailService && method_exists($this->emailService, 'sendOrderConfirmation')) {
                  $this->emailService->sendOrderConfirmation($fullOrder); // Assumes method takes order data
                  error_log("Webhook Success: Order confirmation email queued for order ID {$fullOrder['id']}.");
             } else {
                  error_log("Webhook Warning: EmailService or sendOrderConfirmation method not available for order ID {$fullOrder['id']}.");
             }
        } else {
             error_log("Webhook Warning: Could not fetch full order details for notification (Order ID: {$order['id']}).");
        }

        // Optional: Clear user's cart after successful payment
        if ($order['user_id']) {
            try {
                if (!class_exists('Cart')) require_once __DIR__ . '/../models/Cart.php';
                $cartModel = new Cart($this->db, $order['user_id']);
                $cartModel->clearCart();
                error_log("Webhook Success: Cart cleared for user ID {$order['user_id']} after order {$order['id']} payment.");
            } catch (Exception $cartError) {
                 error_log("Webhook Warning: Failed to clear cart for user ID {$order['user_id']} after order {$order['id']} payment: " . $cartError->getMessage());
                 // Don't fail the webhook for this
            }
        }
    }

    private function handleFailedPayment(\Stripe\PaymentIntent $paymentIntent): void {
         // Find order by payment_intent_id
         $stmt = $this->db->prepare("
             SELECT id, user_id, status
             FROM orders
             WHERE payment_intent_id = ?
             LIMIT 1
         ");
         $stmt->execute([$paymentIntent->id]);
         $order = $stmt->fetch();

         if (!$order) {
              error_log("Webhook Warning: PaymentIntent {$paymentIntent->id} failed but no matching order found.");
              return; // Acknowledge webhook
         }

          // Don't update status if order was already cancelled or completed differently
          if (in_array($order['status'], ['cancelled', 'paid', 'shipped', 'delivered', 'completed'])) {
              error_log("Webhook Info: Received failed payment event for already resolved order ID {$order['id']}.");
              return;
          }


        $updateStmt = $this->db->prepare("
            UPDATE orders
            SET status = 'payment_failed',
                payment_status = 'failed',
                updated_at = NOW()
            WHERE id = ? AND payment_intent_id = ?
        ");

        if (!$updateStmt->execute([$order['id'], $paymentIntent->id])) {
            throw new Exception("Failed to update order ID {$order['id']} status to 'payment_failed'.");
        }
         error_log("Webhook Info: Updated order ID {$order['id']} status to 'payment_failed' for PaymentIntent {$paymentIntent->id}.");

        // Get order details for notification
        $notifyStmt = $this->db->prepare("
            SELECT o.*, u.email, u.name as user_name
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $notifyStmt->execute([$order['id']]);
        $fullOrder = $notifyStmt->fetch();

        if ($fullOrder) {
            // Send payment failed notification
             if ($this->emailService && method_exists($this->emailService, 'sendPaymentFailedNotification')) {
                  $this->emailService->sendPaymentFailedNotification($fullOrder); // Assumes method takes order data
                  error_log("Webhook Info: Payment failed email queued for order ID {$fullOrder['id']}.");
             } else {
                  error_log("Webhook Warning: EmailService or sendPaymentFailedNotification method not available for order ID {$fullOrder['id']}.");
             }
        } else {
             error_log("Webhook Warning: Could not fetch full order details for failed payment notification (Order ID: {$order['id']}).");
        }
    }

     // Optional handler if needed
     private function handleChargeSucceeded(\Stripe\Charge $charge): void {
         // You might log charge details or link it more explicitly to the order if necessary
         error_log("Webhook Info: Charge {$charge->id} succeeded for PaymentIntent {$charge->payment_intent} (Order linked via PI).");
         // Often, action is taken on payment_intent.succeeded instead.
     }


    private function handleDisputeCreated(\Stripe\Dispute $dispute): void {
         // Find order by payment_intent_id
         $stmt = $this->db->prepare("
             SELECT id, user_id, status
             FROM orders
             WHERE payment_intent_id = ?
             LIMIT 1
         ");
         $stmt->execute([$dispute->payment_intent]);
         $order = $stmt->fetch();

         if (!$order) {
              error_log("Webhook Warning: Dispute {$dispute->id} created for PaymentIntent {$dispute->payment_intent} but no matching order found.");
              return; // Acknowledge webhook
         }

        $updateStmt = $this->db->prepare("
            UPDATE orders
            SET status = 'disputed',
                payment_status = 'disputed', // Add or use appropriate payment status
                dispute_id = ?,
                disputed_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");

        if (!$updateStmt->execute([$dispute->id, $order['id']])) {
            throw new Exception("Failed to update order ID {$order['id']} dispute status.");
        }
         error_log("Webhook Alert: Order ID {$order['id']} status updated to 'disputed' due to Dispute {$dispute->id}.");

        // Log dispute details for review - Consider sending admin alert
        $this->logSecurityEvent('stripe_dispute_created', [
             'order_id' => $order['id'],
             'dispute_id' => $dispute->id,
             'payment_intent_id' => $dispute->payment_intent,
             'amount' => $dispute->amount,
             'reason' => $dispute->reason
        ]);

        // Send alert to admin
         if ($this->emailService && method_exists($this->emailService, 'sendAdminDisputeAlert')) {
              $this->emailService->sendAdminDisputeAlert($order['id'], $dispute->id, $dispute->reason, $dispute->amount);
         }
    }

    private function handleRefund(\Stripe\Charge $charge): void {
         // A charge can have multiple refunds. Process the relevant one.
         // This assumes you are interested in the latest refund if multiple occur.
         $refund = $charge->refunds->data[0] ?? null; // Get the first/latest refund object
         if (!$refund) {
             error_log("Webhook Warning: Received charge.refunded event for Charge {$charge->id} but no refund data found.");
             return;
         }


         // Find order by payment_intent_id
         $stmt = $this->db->prepare("
             SELECT id, user_id, status
             FROM orders
             WHERE payment_intent_id = ?
             LIMIT 1
         ");
         $stmt->execute([$charge->payment_intent]);
         $order = $stmt->fetch();

         if (!$order) {
              error_log("Webhook Warning: Refund {$refund->id} processed for PaymentIntent {$charge->payment_intent} but no matching order found.");
              return; // Acknowledge webhook
         }

          // Determine new status based on refund amount vs order total? Partial vs Full refund?
          // Simple approach: Mark as refunded regardless of amount for now.
          $newStatus = 'refunded';
          // Optionally add logic for partial refunds if needed:
          // $orderTotal = $this->getOrderTotal($order['id']); // Need helper to get total
          // if ($charge->amount_refunded < $orderTotal) { $newStatus = 'partially_refunded'; }


        $updateStmt = $this->db->prepare("
            UPDATE orders
            SET status = ?,
                payment_status = ?, -- Use 'refunded' or 'partially_refunded'
                refund_id = ?,      -- Store the Stripe Refund ID
                refunded_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");

         $paymentStatus = ($charge->amount_refunded === $charge->amount) ? 'refunded' : 'partially_refunded';

        if (!$updateStmt->execute([$newStatus, $paymentStatus, $refund->id, $order['id']])) {
            throw new Exception("Failed to update order ID {$order['id']} refund status.");
        }
         error_log("Webhook Info: Order ID {$order['id']} status updated to '{$newStatus}' due to Refund {$refund->id}.");


        // Get order details for notification
         $notifyStmt = $this->db->prepare("
             SELECT o.*, u.email, u.name as user_name
             FROM orders o
             JOIN users u ON o.user_id = u.id
             WHERE o.id = ?
         ");
         $notifyStmt->execute([$order['id']]);
         $fullOrder = $notifyStmt->fetch();


        if ($fullOrder) {
            // Send refund confirmation email
             if ($this->emailService && method_exists($this->emailService, 'sendRefundConfirmation')) {
                  // Pass refund amount for clarity in email
                  $this->emailService->sendRefundConfirmation($fullOrder, $refund->amount / 100.0);
                   error_log("Webhook Info: Refund confirmation email queued for order ID {$fullOrder['id']}.");
             } else {
                  error_log("Webhook Warning: EmailService or sendRefundConfirmation method not available for order ID {$fullOrder['id']}.");
             }
        } else {
             error_log("Webhook Warning: Could not fetch full order details for refund notification (Order ID: {$order['id']}).");
        }
    }
}
