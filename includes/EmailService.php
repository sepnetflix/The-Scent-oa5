<?php
// includes/EmailService.php (Updated)

// Assuming PHPMailer is managed via Composer autoload
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure the path to autoload.php is correct if using Composer
// If not using Composer, ensure PHPMailer classes are included manually before this class.
// require_once __DIR__ . '/../vendor/autoload.php'; // Uncomment if using Composer

// Include dependencies manually if not using Composer
// require_once __DIR__ . '/../path/to/PHPMailer/src/Exception.php';
// require_once __DIR__ . '/../path/to/PHPMailer/src/PHPMailer.php';
// require_once __DIR__ . '/../path/to/PHPMailer/src/SMTP.php';

require_once __DIR__ . '/../config.php'; // For SMTP constants, BASE_URL
require_once __DIR__ . '/../includes/ErrorHandler.php'; // For SecurityLogger (assuming it's defined there)

class EmailService {
    private PHPMailer $mailer;
    private string $templatePath;
    private SecurityLogger $securityLogger;
    private ?string $dkimPrivateKey = null;
    private array $emailQueue = [];
    private PDO $pdo; // Changed from global to instance property

    // Constructor now accepts PDO dependency
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo; // Store PDO instance
        $this->templatePath = realpath(__DIR__ . '/../views/emails/');
        if ($this->templatePath === false || !is_dir($this->templatePath)) {
             error_log("Email template path invalid or not found: " . __DIR__ . '/../views/emails/');
             // Consider throwing an exception or handling this more gracefully
             $this->templatePath = ''; // Prevent errors later, but emails with templates will fail
        } else {
            $this->templatePath .= '/'; // Ensure trailing slash
        }
        $this->securityLogger = new SecurityLogger(); // Assumes SecurityLogger doesn't need PDO
        $this->loadDKIMKey();
        $this->initializeMailer();
    }

    private function initializeMailer(): void {
        $this->mailer = new PHPMailer(true); // Enable exceptions

        try {
            // Server settings
            if (defined('SMTP_DEBUG') && SMTP_DEBUG) { // Optional debug configuration
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            }
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USER;
            $this->mailer->Password = SMTP_PASS;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = SMTP_PORT;
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Encoding = 'base64';

            // Sender
             if (defined('SMTP_FROM') && defined('SMTP_FROM_NAME')) {
                 $this->mailer->setFrom(SMTP_FROM, SMTP_FROM_NAME);
             } else {
                 error_log("SMTP_FROM or SMTP_FROM_NAME not defined in config.php");
                 // Set a default fallback if needed
                 // $this->mailer->setFrom('noreply@example.com', 'The Scent');
             }


            // Enable DKIM signing if configured
            if ($this->dkimPrivateKey && defined('BASE_URL')) {
                 $domain = parse_url(BASE_URL, PHP_URL_HOST) ?: 'the-scent.com'; // Fallback domain
                $this->mailer->DKIM_domain = $domain;
                $this->mailer->DKIM_private = $this->dkimPrivateKey;
                $this->mailer->DKIM_selector = 'thescent'; // Make this configurable?
                $this->mailer->DKIM_passphrase = ''; // Assuming no passphrase
                $this->mailer->DKIM_identity = $this->mailer->From;
            }

        } catch (Exception $e) {
            $this->logError('Mailer initialization failed: ' . $this->mailer->ErrorInfo);
            // Throwing here might prevent the app from loading if email is critical on startup
            // Consider just logging and letting sending fail later.
            // throw new Exception('Email service initialization failed');
        }
    }

    private function loadDKIMKey(): void {
        // Make DKIM path configurable?
        $keyPath = realpath(__DIR__ . '/../config/dkim/private.key'); // Use realpath for robustness
        if ($keyPath && file_exists($keyPath) && is_readable($keyPath)) {
            $this->dkimPrivateKey = file_get_contents($keyPath);
        } else {
            // Log if DKIM key is expected but not found/readable
            // error_log("DKIM private key not found or not readable at: " . $keyPath);
            $this->dkimPrivateKey = null;
        }
    }

    // Now uses $this->pdo
    private function logEmail(?int $userId, string $emailType, string $recipientEmail, string $subject, string $status, ?string $errorMessage = null): void {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO email_log
                (user_id, email_type, recipient_email, subject, status, error_message, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                $emailType,
                $recipientEmail,
                $subject,
                $status,
                $errorMessage
            ]);
        } catch (Exception $e) {
            // Log to PHP error log if DB logging fails
            error_log("DB Email logging failed for '{$emailType}' to '{$recipientEmail}': " . $e->getMessage());
        }
    }

    // --- START OF ADDED METHOD ---
    /**
     * Sends a welcome email to a newly registered user.
     *
     * @param string $recipientEmail The email address of the new user.
     * @param string $recipientName The name of the new user.
     * @return bool True on success, false on failure.
     */
    public function sendWelcome(string $recipientEmail, string $recipientName): bool {
        $subject = 'Welcome to The Scent!';
        $template = 'welcome'; // Assumes views/emails/welcome.php exists
        $userId = null; // Usually no user ID known *yet* when sending welcome

        // Data for the email template
        $data = [
            'name' => $recipientName,
            'store_url' => BASE_URL,
            'login_url' => BASE_URL . 'index.php?page=login'
        ];

        try {
            $this->validateEmailAddress($recipientEmail); // Validate recipient

            // Use the generic sendEmail method for consistency
            return $this->sendEmail($recipientEmail, $subject, $template, $data, false, $userId, 'welcome_email');

        } catch (Exception $e) {
            // Error already logged within sendEmail or validation methods
            // Log specific context if needed
            error_log("Failed to initiate welcome email to {$recipientEmail}: " . $e->getMessage());
            return false;
        }
    }
    // --- END OF ADDED METHOD ---


    // Updated sendPasswordReset to use sendEmail method
    public function sendPasswordReset(array $user, string $token, string $resetLink): bool {
         if (!isset($user['email']) || !isset($user['name'])) {
             $this->logError('Invalid user data for password reset', ['user_id' => $user['id'] ?? null]);
             return false; // Or throw exception
         }
         $subject = 'Reset Your Password - The Scent';
         $template = 'password_reset';
         $data = [
             'name' => $user['name'],
             'resetLink' => $resetLink // Pass the pre-generated link
         ];
         // Send with high priority maybe?
         return $this->sendEmail($user['email'], $subject, $template, $data, true, $user['id'], 'password_reset');
     }

    // Updated sendOrderConfirmation to use sendEmail method
    public function sendOrderConfirmation(array $order, array $user): bool {
         if (!isset($user['email']) || !isset($order['id'])) {
             $this->logError('Invalid order/user data for confirmation', ['user_id' => $user['id'] ?? null, 'order_id' => $order['id'] ?? null]);
             return false; // Or throw exception
         }
         $subject = 'Order Confirmation #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
         $template = 'order_confirmation';
         $data = [
             'user' => $user,
             'order' => $order
         ];
         return $this->sendEmail($user['email'], $subject, $template, $data, false, $user['id'], 'order_confirmation');
     }

     // Updated sendShippingUpdate to use sendEmail method
     public function sendShippingUpdate(array $order, array $user, string $trackingNumber, string $carrier): bool {
         if (!isset($user['email']) || !isset($order['id'])) {
             $this->logError('Invalid order/user data for shipping update', ['user_id' => $user['id'] ?? null, 'order_id' => $order['id'] ?? null]);
             return false; // Or throw exception
         }
         $subject = 'Shipping Update - Order #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
         $template = 'shipping_update'; // Assumes views/emails/shipping_update.php exists
         $data = [
             'user' => $user,
             'order' => $order,
             'trackingNumber' => $trackingNumber,
             'carrier' => $carrier
             // Add tracking URL if available/needed
         ];
         return $this->sendEmail($user['email'], $subject, $template, $data, false, $user['id'], 'shipping_update');
     }

     // Updated sendNewsletter to use sendEmail method (for consistency, though it was similar)
     public function sendNewsletter(string $email, string $subject, string $template = 'newsletter_general', array $data = []): bool {
         // Assuming a generic newsletter template exists
         return $this->sendEmail($email, $subject, $template, $data, false, null, 'newsletter');
     }

     // Keep sendSecurityAlert as it might have specific formatting/recipient needs
     public function sendSecurityAlert(string $level, string $message, array $context): bool {
        $template = 'security_alert'; // Assumes views/emails/security_alert.php exists
        $subject = "Security Alert [{$level}]: The Scent";
        $recipient = defined('SECURITY_ALERT_EMAIL') ? SECURITY_ALERT_EMAIL : null; // Get recipient from config

        if (!$recipient) {
            $this->logError('SECURITY_ALERT_EMAIL not configured. Cannot send alert.', $context);
            return false;
        }

        $data = [
            'level' => $level,
            'alert_message' => $message, // Use different key to avoid clash if 'message' is in context
            'context' => print_r($context, true), // Format context for email body
            'timestamp' => date('Y-m-d H:i:s T')
        ];

        // Send with high priority
        return $this->sendEmail($recipient, $subject, $template, $data, true, null, 'security_alert');
    }

    // Generic send method - The core sending logic
    // Added $userId and $emailType for centralized logging
    public function sendEmail(string $to, string $subject, string $template, array $data = [], bool $priority = false, ?int $userId = null, string $emailType = 'general'): bool {
        try {
            $this->validateEmailAddress($to);
            $this->validateTemplate($template);

            $html = $this->renderTemplate($template, $data);
            $text = $this->convertToPlainText($html); // Generate plain text version

            // Reset mailer state for this specific email
            $this->mailer->clearAllRecipients(); // Clears all types of recipients (To, CC, BCC)
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();

            // Re-apply necessary headers and settings
            $this->addSecurityHeaders(); // Add custom security headers
            if ($this->dkimPrivateKey) { // Re-apply DKIM if needed
                 $domain = parse_url(BASE_URL, PHP_URL_HOST) ?: 'the-scent.com';
                 $this->mailer->DKIM_domain = $domain;
                 $this->mailer->DKIM_private = $this->dkimPrivateKey;
                 $this->mailer->DKIM_selector = 'thescent';
                 $this->mailer->DKIM_passphrase = '';
                 $this->mailer->DKIM_identity = $this->mailer->From;
             }


            // Add recipient
            $this->mailer->addAddress($to);

            // Set content
            $this->mailer->Subject = $this->sanitizeSubject($subject);
            $this->mailer->isHTML(true);
            $this->mailer->Body = $html;
            $this->mailer->AltBody = $text;

            // Set message priority if requested
            if ($priority) {
                $this->mailer->Priority = 1; // 1 = High, 3 = Normal, 5 = Low
                $this->mailer->AddCustomHeader('X-Priority', '1 (Highest)');
                $this->mailer->AddCustomHeader('Importance', 'High');
            } else {
                 $this->mailer->Priority = 3;
                 $this->mailer->AddCustomHeader('X-Priority', '3 (Normal)');
                 $this->mailer->AddCustomHeader('Importance', 'Normal');
            }

            $sent = $this->mailer->send();

            if ($sent) {
                // Log success to DB
                $this->logEmail($userId, $emailType, $to, $subject, 'sent');
                return true;
            } else {
                // Log failure to DB (PHPMailer exception usually caught below)
                 $this->logEmail($userId, $emailType, $to, $subject, 'failed', $this->mailer->ErrorInfo);
                $this->logError("Email sending failed (Mailer Error): {$this->mailer->ErrorInfo}", ['to' => $to, 'subject' => $subject]);
                 return false;
            }

        } catch (Exception $e) {
            // Log general exception during sending or setup
            $errorMessage = $e->getMessage() . (isset($this->mailer->ErrorInfo) ? " | Mailer Error: " . $this->mailer->ErrorInfo : "");
            $this->logError("Email sending failed (Exception): " . $errorMessage, [
                'to' => $to,
                'subject' => $subject,
                'template' => $template
            ]);
            // Log failure to DB
            $this->logEmail($userId, $emailType, $to, $subject, 'failed', $errorMessage);
            // Optionally re-throw or return false based on desired application flow
            // throw new Exception('Failed to send email');
             return false;
        }
    }


    // --- Helper Methods --- (validateEmailAddress, validateTemplate, renderTemplate, sanitize*, convertToPlainText, addSecurityHeaders)

    private function validateEmailAddress(string $email): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->securityLogger->warning('Invalid email format attempted', ['email' => $email]);
            throw new Exception('Invalid email address format');
        }
        // Add domain/MX record check optionally here if needed
    }

    private function validateTemplate(string $template): void {
         if (empty($this->templatePath)) {
             throw new Exception('Email template path is not configured.');
         }
        // Basic check for directory traversal
        if (strpos($template, '..') !== false || strpos($template, '/') !== false || strpos($template, '\\') !== false) {
             $this->securityLogger->error('Potential directory traversal in email template name', ['template' => $template]);
            throw new Exception('Invalid email template name.');
        }
        $templateFile = $this->templatePath . $template . '.php';
        if (!file_exists($templateFile) || !is_readable($templateFile)) {
             $this->logError('Email template not found or not readable', ['template_file' => $templateFile]);
            throw new Exception('Email template not found: ' . $template);
        }
        // Permission check removed - focus on readability and existence. Filesystem permissions are server config.
    }

    private function renderTemplate(string $template, array $data): string {
        if (empty($this->templatePath)) return "Error: Email template path missing."; // Graceful fallback

        $templateFile = $this->templatePath . $template . '.php';
         // Double check existence just before include
         if (!file_exists($templateFile) || !is_readable($templateFile)) {
             error_log("Error: Template file missing or unreadable in renderTemplate: $templateFile");
             return "Error rendering email content."; // Fallback content
         }

        // Sanitize data before extracting
        extract($this->sanitizeTemplateData($data));
        ob_start();
        try {
            include $templateFile;
        } catch (Throwable $t) { // Catch parse errors etc. in template
            error_log("Error including email template ($templateFile): " . $t->getMessage());
            ob_end_clean(); // Clean buffer if include failed
            return "Error rendering email content."; // Fallback content
        }
        return ob_get_clean();
    }

    private function sanitizeTemplateData(array $data): array {
        $sanitized = [];
        foreach ($data as $key => $value) {
            // Allow arrays/objects to pass through for structured data in templates,
            // but ensure strings are escaped. Individual templates must handle nested data safely.
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } elseif (is_scalar($value) || is_null($value)) {
                $sanitized[$key] = $value; // Allow numbers, bools, null
            } else {
                $sanitized[$key] = $value; // Pass arrays/objects as is - template must handle
            }
        }
        return $sanitized;
    }

    private function sanitizeSubject(string $subject): string {
        // Remove characters that could interfere with email headers
        return preg_replace('/[\r\n\t]+/', '', trim($subject));
    }

    private function convertToPlainText(string $html): string {
        // More robust conversion
        $text = $html;
        // Convert links
        $text = preg_replace('/<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*?)<\/a>/si', '$3 [$2]', $text);
        // Convert line breaks
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        // Convert paragraphs
        $text = preg_replace('/<\/?p\s*\/?>/i', "\n\n", $text);
        // Remove remaining tags
        $text = strip_tags($text);
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        // Normalize whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/(\s*\n\s*){3,}/', "\n\n", $text); // Max 2 consecutive newlines
        return trim($text);
    }

    private function addSecurityHeaders(): void {
        // Generate a unique message ID if not already set by PHPMailer
        if (empty($this->mailer->MessageID)) {
            $messageId = sprintf(
                '<%s@%s>',
                bin2hex(random_bytes(16)), // More standard unique ID format
                parse_url(BASE_URL, PHP_URL_HOST) ?: 'the-scent.com'
            );
            $this->mailer->MessageID = $messageId;
        }
        // These are less common/standard for basic emails, but can add minor obscurity
        // $this->mailer->AddCustomHeader('X-Mailer', 'TheScent-SecureMailer/1.0');
        // $this->mailer->AddCustomHeader('X-Content-Type-Options', 'nosniff');
        // $this->mailer->AddCustomHeader('X-XSS-Protection', '1; mode=block');
    }

    // --- Removed Methods ---
    // Removed specific template getter functions like getOrderConfirmationTemplate as renderTemplate is used.
    // Removed queueing logic for simplicity - emails are sent directly. Add back if needed.
    // Removed duplicate sendPasswordResetEmail - use sendPasswordReset.

    // Log Error Helper (uses SecurityLogger)
    private function logError(string $message, array $context = []): void {
        // Log using the SecurityLogger instance
        $this->securityLogger->error($message, $context); // Assuming SecurityLogger has an error method
        // Also log to PHP's error log for visibility
        error_log("EmailService Error: " . $message . " | Context: " . json_encode($context));
    }

    // Ensure processEmailQueue is removed if queueing is removed
    // public function processEmailQueue() { ... }

} // End of EmailService class
