<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class EmailService {
    private $mailer;
    private $templatePath;
    private $securityLogger;
    private $dkimPrivateKey;
    private $emailQueue = [];
    private $pdo;

    public function __construct() {
        $this->templatePath = __DIR__ . '/../views/emails/';
        $this->securityLogger = new SecurityLogger();
        $this->initializeMailer();
        $this->loadDKIMKey();

        global $pdo;
        $this->pdo = $pdo;
    }

    private function initializeMailer() {
        $this->mailer = new PHPMailer(true);

        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USER;
            $this->mailer->Password = SMTP_PASS;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = SMTP_PORT;
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Encoding = 'base64';
            $this->mailer->setFrom(SMTP_FROM, SMTP_FROM_NAME);

            // Enable DKIM signing
            if ($this->dkimPrivateKey) {
                $this->mailer->DKIM_domain = parse_url(BASE_URL, PHP_URL_HOST);
                $this->mailer->DKIM_private = $this->dkimPrivateKey;
                $this->mailer->DKIM_selector = 'thescent';
                $this->mailer->DKIM_passphrase = '';
                $this->mailer->DKIM_identity = $this->mailer->From;
            }

        } catch (Exception $e) {
            $this->logError('Mailer initialization failed: ' . $e->getMessage());
            throw new Exception('Email service initialization failed');
        }
    }

    private function loadDKIMKey() {
        $keyPath = __DIR__ . '/../config/dkim/private.key';
        if (file_exists($keyPath)) {
            $this->dkimPrivateKey = file_get_contents($keyPath);
        }
    }

    private function logEmail($userId, $emailType, $recipientEmail, $subject, $status, $errorMessage = null) {
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
        } catch (Exception $e) {
            error_log("Email logging error: " . $e->getMessage());
        }
    }

    public function sendOrderConfirmation($order, $user) {
        if (!isset($user['email']) || !isset($order['id'])) {
            throw new InvalidArgumentException("Invalid order or user data for confirmation email");
        }

        try {
            ob_start();
            require __DIR__ . '/../views/emails/order_confirmation.php';
            $emailContent = ob_get_clean();

            $this->mailer->clearAddresses();
            $this->mailer->addAddress($user['email'], $user['name']);
            $this->mailer->Subject = 'Order Confirmation #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
            $this->mailer->Body = $emailContent;

            $success = $this->mailer->send();
            $this->logEmail(
                $user['id'],
                'order_confirmation',
                $user['email'],
                'Order Confirmation #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT),
                $success ? 'sent' : 'failed'
            );

            return $success;
        } catch (Exception $e) {
            $this->logEmail(
                $user['id'],
                'order_confirmation',
                $user['email'],
                'Order Confirmation #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT),
                'failed',
                $e->getMessage()
            );
            error_log("Failed to send order confirmation: " . $e->getMessage());
            throw new Exception("Failed to send order confirmation email");
        }
    }

    public function sendPasswordReset($user, $token) {
        if (!isset($user['email']) || !isset($user['name'])) {
            throw new InvalidArgumentException("Invalid user data for password reset email");
        }

        try {
            $resetLink = BASE_URL . "index.php?page=reset-password&token=" . urlencode($token);

            ob_start();
            $name = $user['name'];
            require __DIR__ . '/../views/emails/password_reset.php';
            $emailContent = ob_get_clean();

            $this->mailer->clearAddresses();
            $this->mailer->addAddress($user['email'], $user['name']);
            $this->mailer->Subject = 'Reset Your Password - The Scent';
            $this->mailer->Body = $emailContent;
            $this->mailer->AltBody = "Reset your password by clicking this link: " . $resetLink;

            $success = $this->mailer->send();
            $this->logEmail(
                $user['id'] ?? null,
                'password_reset',
                $user['email'],
                'Reset Your Password - The Scent',
                $success ? 'sent' : 'failed'
            );

            return $success;
        } catch (Exception $e) {
            $this->logEmail(
                $user['id'] ?? null,
                'password_reset',
                $user['email'],
                'Reset Your Password - The Scent',
                'failed',
                $e->getMessage()
            );
            error_log("Failed to send password reset email: " . $e->getMessage());
            throw new Exception("Failed to send password reset email");
        }
    }

    public function sendPasswordResetEmail($to, $data) {
        try {
            $mail = $this->createMailer();
            $mail->addAddress($to);
            
            $mail->Subject = 'Reset Your Password - The Scent';
            
            // Get email template content
            ob_start();
            extract($data);
            require __DIR__ . '/../views/emails/password_reset.php';
            $body = ob_get_clean();
            
            $mail->Body = $body;
            $mail->AltBody = "Reset your password by clicking this link: " . $data['resetLink'];
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Failed to send password reset email: " . $e->getMessage());
            return false;
        }
    }

    public function sendNewsletter($email, $content) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = 'Welcome to The Scent Newsletter';
            $this->mailer->Body = $content;

            $success = $this->mailer->send();
            $this->logEmail(
                null,
                'newsletter',
                $email,
                'Welcome to The Scent Newsletter',
                $success ? 'sent' : 'failed'
            );

            return $success;
        } catch (Exception $e) {
            $this->logEmail(
                null,
                'newsletter',
                $email,
                'Welcome to The Scent Newsletter',
                'failed',
                $e->getMessage()
            );
            return false;
        }
    }

    public function sendShippingUpdate($order, $user, $status) {
        if (!isset($user['email']) || !isset($order['id'])) {
            throw new InvalidArgumentException("Invalid order or user data for shipping update");
        }

        try {
            ob_start();
            require __DIR__ . '/../views/emails/shipping_update.php';
            $emailContent = ob_get_clean();

            $this->mailer->clearAddresses();
            $this->mailer->addAddress($user['email'], $user['name']);
            $this->mailer->Subject = 'Shipping Update - Order #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
            $this->mailer->Body = $emailContent;

            $success = $this->mailer->send();
            $this->logEmail(
                $user['id'],
                'shipping_update',
                $user['email'],
                'Shipping Update - Order #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT),
                $success ? 'sent' : 'failed'
            );

            return $success;
        } catch (Exception $e) {
            $this->logEmail(
                $user['id'],
                'shipping_update',
                $user['email'],
                'Shipping Update - Order #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT),
                'failed',
                $e->getMessage()
            );
            error_log("Failed to send shipping update: " . $e->getMessage());
            throw new Exception("Failed to send shipping update email");
        }
    }

    private function getOrderConfirmationTemplate($order, $user) {
        ob_start();
        include __DIR__ . '/../views/emails/order_confirmation.php';
        return ob_get_clean();
    }

    private function getPasswordResetTemplate($name, $resetLink) {
        ob_start();
        include __DIR__ . '/../views/emails/password_reset.php';
        return ob_get_clean();
    }

    private function getShippingUpdateTemplate($order, $trackingNumber, $carrier) {
        ob_start();
        include __DIR__ . '/../views/emails/shipping_update.php';
        return ob_get_clean();
    }

    public function sendSecurityAlert($level, $message, $context) {
        $template = 'security_alert';
        $subject = "Security Alert: {$level}";
        $to = SECURITY_ALERT_EMAIL;

        $data = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $this->sendEmail($to, $subject, $template, $data, true);
    }

    public function sendEmail($to, $subject, $template, $data = [], $priority = false) {
        try {
            $this->validateEmailAddress($to);
            $this->validateTemplate($template);

            $html = $this->renderTemplate($template, $data);
            $text = $this->convertToPlainText($html);

            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $this->sanitizeSubject($subject);
            $this->mailer->isHTML(true);
            $this->mailer->Body = $html;
            $this->mailer->AltBody = $text;

            // Set message priority
            if ($priority) {
                $this->mailer->Priority = 1;
                $this->mailer->AddCustomHeader('X-Priority', '1');
            }

            // Add message ID and other security headers
            $this->addSecurityHeaders();

            if ($priority) {
                $sent = $this->mailer->send();
            } else {
                $this->queueEmail($to, $subject, $html, $text);
                $sent = true;
            }

            if ($sent) {
                $this->logEmailSent($to, $subject, $template);
                return true;
            }

        } catch (Exception $e) {
            $this->logError('Email sending failed: ' . $e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
                'template' => $template
            ]);
            throw new Exception('Failed to send email');
        }

        return false;
    }

    private function validateEmailAddress($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }

        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/\.{2,}/', // Multiple dots
            '/[<>]/',   // HTML tags
            '/\s/'      // Whitespace
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $email)) {
                $this->securityLogger->warning('Suspicious email address detected', [
                    'email' => $email,
                    'pattern' => $pattern
                ]);
                throw new Exception('Invalid email address');
            }
        }
    }

    private function validateTemplate($template) {
        $templateFile = $this->templatePath . $template . '.php';
        if (!file_exists($templateFile)) {
            throw new Exception('Email template not found');
        }

        // Validate template file permissions
        $perms = fileperms($templateFile);
        if (($perms & 0x0002) || ($perms & 0x0020)) {
            $this->securityLogger->critical('Insecure template file permissions', [
                'template' => $template,
                'permissions' => substr(sprintf('%o', $perms), -4)
            ]);
            throw new Exception('Insecure template configuration');
        }
    }

    private function renderTemplate($template, $data) {
        ob_start();
        extract($this->sanitizeTemplateData($data));
        include $this->templatePath . $template . '.php';
        return ob_get_clean();
    }

    private function sanitizeTemplateData($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeTemplateData($value);
            } else {
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }
        return $sanitized;
    }

    private function sanitizeSubject($subject) {
        return preg_replace('/[\r\n]+/', '', trim($subject));
    }

    private function convertToPlainText($html) {
        // Remove HTML tags while preserving structure
        $text = strip_tags(str_replace(
            ['<br>', '<br/>', '<br />', '<p>', '</p>', '<div>', '</div>'],
            ["\n", "\n", "\n", "\n\n", "\n", "\n", "\n"],
            $html
        ));

        // Clean up whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n\s+\n/', "\n\n", $text);

        return trim($text);
    }

    private function addSecurityHeaders() {
        $messageId = sprintf(
            '<%s@%s>',
            uniqid('email-', true),
            parse_url(BASE_URL, PHP_URL_HOST)
        );

        $this->mailer->MessageID = $messageId;
        $this->mailer->AddCustomHeader('X-Mailer', 'TheScent-Secure-Mailer');
        $this->mailer->AddCustomHeader('X-Content-Type-Options', 'nosniff');
        $this->mailer->AddCustomHeader('X-XSS-Protection', '1; mode=block');
    }

    private function queueEmail($to, $subject, $html, $text) {
        $this->emailQueue[] = [
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
            'attempts' => 0,
            'created' => time()
        ];

        // Process queue if it reaches threshold or periodically
        if (count($this->emailQueue) >= 10) {
            $this->processEmailQueue();
        }
    }

    public function processEmailQueue() {
        foreach ($this->emailQueue as $key => $email) {
            try {
                $this->mailer->clearAddresses();
                $this->mailer->addAddress($email['to']);
                $this->mailer->Subject = $email['subject'];
                $this->mailer->Body = $email['html'];
                $this->mailer->AltBody = $email['text'];

                if ($this->mailer->send()) {
                    unset($this->emailQueue[$key]);
                    $this->logEmailSent($email['to'], $email['subject'], 'queued');
                } else {
                    $this->emailQueue[$key]['attempts']++;
                    if ($this->emailQueue[$key]['attempts'] >= 3) {
                        $this->logError('Email queue processing failed after 3 attempts', [
                            'to' => $email['to'],
                            'subject' => $email['subject']
                        ]);
                        unset($this->emailQueue[$key]);
                    }
                }
            } catch (Exception $e) {
                $this->logError('Queue processing error: ' . $e->getMessage(), [
                    'to' => $email['to'],
                    'subject' => $email['subject']
                ]);
            }
        }
    }

    private function logEmailSent($to, $subject, $template) {
        $this->securityLogger->info('Email sent successfully', [
            'to' => $to,
            'subject' => $subject,
            'template' => $template,
            'message_id' => $this->mailer->MessageID
        ]);
    }

    private function logError($message, $context = []) {
        $this->securityLogger->error($message, $context);
    }
}