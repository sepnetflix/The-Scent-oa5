<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

class PaymentController extends BaseController {
    private $stripe;
    private $webhookSecret;
    
    public function __construct($pdo = null) {
        parent::__construct($pdo);
        $this->stripe = new \Stripe\StripeClient(STRIPE_SECRET_KEY);
        $this->webhookSecret = STRIPE_WEBHOOK_SECRET;
    }
    
    public function createPaymentIntent($amount, $currency = 'usd') {
        try {
            // Validate input
            $amount = $this->validateInput($amount, 'float');
            $currency = $this->validateInput($currency, 'string');
            
            if ($amount <= 0) {
                throw new Exception('Invalid payment amount');
            }
            
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int)($amount * 100), // Convert to cents
                'currency' => strtolower($currency),
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'user_id' => $this->getUserId() ?? 'guest',
                    'ip_address' => $_SERVER['REMOTE_ADDR']
                ]
            ]);
            
            return [
                'success' => true,
                'clientSecret' => $paymentIntent->client_secret
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe API Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment processing failed'
            ];
        } catch (Exception $e) {
            error_log("Payment Intent Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Invalid payment request'
            ];
        }
    }
    
    public function processPayment($orderId) {
        try {
            $this->validateCSRF();
            $orderId = $this->validateInput($orderId, 'int');
            
            if (!$orderId) {
                throw new Exception('Invalid order ID');
            }
            
            $this->beginTransaction();
            
            // Get order details
            $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            // Verify order belongs to current user
            if ($order['user_id'] !== $this->getUserId()) {
                throw new Exception('Unauthorized access to order');
            }
            
            // Create payment intent
            $result = $this->createPaymentIntent($order['total_amount']);
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Update order with payment intent
            $stmt = $this->pdo->prepare("
                UPDATE orders 
                SET payment_intent_id = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$result['clientSecret'], $orderId]);
            
            $this->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Payment Processing Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment processing failed'
            ];
        }
    }
    
    public function handleWebhook() {
        try {
            $payload = @file_get_contents('php://input');
            $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'];
            
            // Verify webhook signature
            try {
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $sigHeader,
                    $this->webhookSecret
                );
            } catch (\UnexpectedValueException $e) {
                throw new Exception('Invalid payload');
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                throw new Exception('Invalid signature');
            }
            
            $this->beginTransaction();
            
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handleSuccessfulPayment($event->data->object);
                    break;
                    
                case 'payment_intent.payment_failed':
                    $this->handleFailedPayment($event->data->object);
                    break;
                    
                case 'charge.dispute.created':
                    $this->handleDisputeCreated($event->data->object);
                    break;
                    
                case 'charge.refunded':
                    $this->handleRefund($event->data->object);
                    break;
            }
            
            $this->commit();
            return $this->jsonResponse(['success' => true]);
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Webhook Error: " . $e->getMessage());
            return $this->jsonResponse(
                ['success' => false, 'error' => $e->getMessage()],
                400
            );
        }
    }
    
    private function handleSuccessfulPayment($paymentIntent) {
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET status = 'paid', 
                payment_status = 'completed',
                paid_at = NOW(),
                updated_at = NOW()
            WHERE payment_intent_id = ?
        ");
        
        if (!$stmt->execute([$paymentIntent->client_secret])) {
            throw new Exception('Failed to update order payment status');
        }
        
        // Get order details for notification
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.email 
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.payment_intent_id = ?
        ");
        $stmt->execute([$paymentIntent->client_secret]);
        $order = $stmt->fetch();
        
        if ($order) {
            // Send payment confirmation email
            $this->emailService->sendPaymentConfirmation($order);
        }
    }
    
    private function handleFailedPayment($paymentIntent) {
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET status = 'payment_failed',
                payment_status = 'failed',
                updated_at = NOW()
            WHERE payment_intent_id = ?
        ");
        
        if (!$stmt->execute([$paymentIntent->client_secret])) {
            throw new Exception('Failed to update order payment status');
        }
        
        // Get order details for notification
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.email 
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.payment_intent_id = ?
        ");
        $stmt->execute([$paymentIntent->client_secret]);
        $order = $stmt->fetch();
        
        if ($order) {
            // Send payment failed notification
            $this->emailService->sendPaymentFailedNotification($order);
        }
    }
    
    private function handleDisputeCreated($dispute) {
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET status = 'disputed',
                dispute_id = ?,
                disputed_at = NOW(),
                updated_at = NOW()
            WHERE payment_intent_id = ?
        ");
        
        if (!$stmt->execute([$dispute->id, $dispute->payment_intent])) {
            throw new Exception('Failed to update order dispute status');
        }
        
        // Log dispute details for review
        error_log("Dispute created for payment: " . $dispute->payment_intent);
    }
    
    private function handleRefund($charge) {
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET status = 'refunded',
                refund_id = ?,
                refunded_at = NOW(),
                updated_at = NOW()
            WHERE payment_intent_id = ?
        ");
        
        if (!$stmt->execute([$charge->refunds->data[0]->id, $charge->payment_intent])) {
            throw new Exception('Failed to update order refund status');
        }
        
        // Get order details for notification
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.email 
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.payment_intent_id = ?
        ");
        $stmt->execute([$charge->payment_intent]);
        $order = $stmt->fetch();
        
        if ($order) {
            // Send refund confirmation email
            $this->emailService->sendRefundConfirmation($order);
        }
    }
}