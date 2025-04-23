<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../includes/EmailService.php';

class NewsletterController extends BaseController {
    private $emailService;

    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->emailService = new EmailService();
    }
    
    public function subscribe() {
        try {
            $this->validateCSRF();
            
            // Standardized rate limiting
            $this->validateRateLimit('newsletter');
            
            $email = $this->validateInput($_POST['email'] ?? null, 'email');
            if (!$email) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Please provide a valid email address.'
                ], 400);
            }
            
            $this->beginTransaction();
            
            // Check if already subscribed
            $stmt = $this->pdo->prepare("
                SELECT id, status 
                FROM newsletter_subscribers 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $subscriber = $stmt->fetch();
            
            if ($subscriber) {
                if ($subscriber['status'] === 'active') {
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'This email is already subscribed to our newsletter.'
                    ]);
                }
                
                // Reactivate unsubscribed user
                $stmt = $this->pdo->prepare("
                    UPDATE newsletter_subscribers
                    SET status = 'active',
                        updated_at = NOW(),
                        unsubscribed_at = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$subscriber['id']]);
            } else {
                // Add new subscriber
                $stmt = $this->pdo->prepare("
                    INSERT INTO newsletter_subscribers (
                        email, 
                        status, 
                        ip_address,
                        unsubscribe_token
                    ) VALUES (?, 'active', ?, ?)
                ");
                $stmt->execute([
                    $email,
                    $_SERVER['REMOTE_ADDR'],
                    $this->generateUnsubscribeToken($email)
                ]);
            }
            
            // Send welcome email
            $content = $this->getWelcomeEmailContent();
            $this->emailService->sendNewsletter($email, $content);
            
            // Log the email
            $this->logEmail(
                $this->getUserId(),
                'newsletter_welcome',
                $email,
                'Welcome to The Scent Newsletter',
                'sent'
            );
            
            $this->commit();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Thank you for subscribing to our newsletter!'
            ]);
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Newsletter subscription error: " . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'message' => 'An error occurred while processing your subscription.'
            ], 500);
        }
    }
    
    public function unsubscribe() {
        try {
            $email = $this->validateInput($_GET['email'] ?? null, 'email');
            $token = $this->validateInput($_GET['token'] ?? null, 'string');
            
            if (!$email || !$token) {
                throw new Exception('Invalid unsubscribe request');
            }
            
            $this->beginTransaction();
            
            $stmt = $this->pdo->prepare("
                UPDATE newsletter_subscribers 
                SET status = 'unsubscribed',
                    unsubscribed_at = NOW(),
                    updated_at = NOW()
                WHERE email = ? 
                AND unsubscribe_token = ?
                AND status = 'active'
            ");
            $stmt->execute([$email, $token]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Invalid unsubscribe request');
            }
            
            // Log unsubscribe
            $this->logEmail(
                null,
                'newsletter_unsubscribe',
                $email,
                'Newsletter Unsubscription',
                'processed'
            );
            
            $this->commit();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'You have been successfully unsubscribed.'
            ]);
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Newsletter unsubscribe error: " . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid unsubscribe request.'
            ], 400);
        }
    }
    
    private function generateUnsubscribeToken($email) {
        return hash_hmac(
            'sha256',
            $email . time(),
            NEWSLETTER_SECRET_KEY
        );
    }
    
    public function logEmail($userId, $emailType, $recipientEmail, $subject, $status, $errorMessage = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO email_log 
                (user_id, email_type, recipient_email, subject, status, error_message)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $emailType,
                $recipientEmail,
                $subject,
                $status,
                $errorMessage
            ]);
            return true;
        } catch (Exception $e) {
            error_log("Email logging error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getWelcomeEmailContent() {
        ob_start();
        include __DIR__ . '/../views/emails/newsletter_welcome.php';
        return ob_get_clean();
    }
    
    public function getSubscriberCount() {
        $this->requireAdmin();
        
        $stmt = $this->pdo->query("
            SELECT COUNT(*) 
            FROM newsletter_subscribers 
            WHERE status = 'active'
        ");
        return $stmt->fetchColumn();
    }
    
    public function getRecentSubscribers($limit = 10) {
        $this->requireAdmin();
        
        $stmt = $this->pdo->prepare("
            SELECT email, created_at
            FROM newsletter_subscribers
            WHERE status = 'active'
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}