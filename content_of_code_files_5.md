# config/auth.php  
```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | If you have multiple user tables or models you may configure multiple
    | providers to represent the model / table. These providers may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage
    | and the user provider that is invoked to actually retrieve users.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];

```

# includes/EmailService.php  
```php
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
                (user_id, email_type, to_email, subject, status, error_message, sent_at)
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

```

# views/account/dashboard.php  
```php
<?php require_once __DIR__ . '/../layout/header.php'; ?>

<section class="account-section">
    <div class="container">
        <div class="account-grid">
            <!-- Sidebar Navigation -->
            <aside class="account-sidebar" data-aos="fade-right">
                <div class="account-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <h3><?= htmlspecialchars($user['name']) ?></h3>
                        <p><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    
                    <nav>
                        <ul>
                            <li>
                                <a href="index.php?page=account" class="active">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=orders">
                                    <i class="fas fa-shopping-bag"></i> My Orders
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=profile">
                                    <i class="fas fa-user"></i> Profile Settings
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=quiz">
                                    <i class="fas fa-clipboard-list"></i> Quiz History
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=logout">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </aside>
            
            <!-- Main Content -->
            <div class="account-content">
                <h1 class="page-title" data-aos="fade-up">My Account Dashboard</h1>
                
                <!-- Account Overview -->
                <div class="dashboard-grid">
                    <!-- Quick Stats -->
                    <div class="dashboard-card stats" data-aos="fade-up">
                        <div class="stat-item">
                            <i class="fas fa-shopping-bag"></i>
                            <div class="stat-info">
                                <span class="stat-value"><?= count($recentOrders) ?></span>
                                <span class="stat-label">Recent Orders</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-box"></i>
                            <div class="stat-info">
                                <span class="stat-value"><?= count($quizResults) ?></span>
                                <span class="stat-label">Saved Preferences</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Orders -->
                    <div class="dashboard-card orders" data-aos="fade-up">
                        <div class="card-header">
                            <h2>Recent Orders</h2>
                            <a href="index.php?page=account&section=orders" class="btn-link">
                                View All <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        
                        <?php if (empty($recentOrders)): ?>
                            <div class="empty-state">
                                <i class="fas fa-shopping-bag"></i>
                                <p>No orders yet</p>
                                <a href="index.php?page=products" class="btn-primary">Start Shopping</a>
                            </div>
                        <?php else: ?>
                            <div class="orders-list">
                                <?php foreach ($recentOrders as $order): ?>
                                    <div class="order-item">
                                        <div class="order-info">
                                            <span class="order-number">
                                                #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?>
                                            </span>
                                            <span class="order-date">
                                                <?= date('M j, Y', strtotime($order['created_at'])) ?>
                                            </span>
                                        </div>
                                        <div class="order-details">
                                            <span class="order-status <?= $order['status'] ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                            <span class="order-total">
                                                $<?= number_format($order['total_amount'], 2) ?>
                                            </span>
                                        </div>
                                        <a href="index.php?page=account&section=orders&id=<?= $order['id'] ?>" 
                                           class="btn-secondary">View Details</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Scent Quiz Results -->
                    <div class="dashboard-card quiz" data-aos="fade-up">
                        <div class="card-header">
                            <h2>Your Scent Profile</h2>
                            <a href="index.php?page=account&section=quiz" class="btn-link">
                                View History <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        
                        <?php if (empty($quizResults)): ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-list"></i>
                                <p>Take our scent quiz to discover your perfect match</p>
                                <a href="index.php?page=quiz" class="btn-primary">Take Quiz</a>
                            </div>
                        <?php else: ?>
                            <?php $latestQuiz = $quizResults[0]; ?>
                            <div class="quiz-results">
                                <div class="scent-preferences">
                                    <h3>Your Preferences</h3>
                                    <ul>
                                        <?php foreach (json_decode($latestQuiz['preferences'], true) as $pref): ?>
                                            <li>
                                                <i class="fas fa-check"></i>
                                                <?= htmlspecialchars($pref) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                
                                <div class="recommended-products">
                                    <h3>Recommended Products</h3>
                                    <div class="product-recommendations">
                                        <?php 
                                        $recommendedIds = json_decode($latestQuiz['recommended_products'], true);
                                        $productModel = new Product($pdo);
                                        $recommendations = $productModel->getProductsByIds($recommendedIds);
                                        foreach ($recommendations as $product): 
                                        ?>
                                            <div class="recommended-product">
                                                <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                                                     alt="<?= htmlspecialchars($product['name']) ?>">
                                                <div class="product-info">
                                                    <h4><?= htmlspecialchars($product['name']) ?></h4>
                                                    <p class="price">$<?= number_format($product['price'], 2) ?></p>
                                                    <a href="index.php?page=products&id=<?= $product['id'] ?>" 
                                                       class="btn-secondary">View Product</a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="dashboard-card actions" data-aos="fade-up">
                        <h2>Quick Actions</h2>
                        <div class="action-buttons">
                            <a href="index.php?page=quiz" class="btn-action">
                                <i class="fas fa-sync"></i>
                                Retake Quiz
                            </a>
                            <a href="index.php?page=account&section=profile" class="btn-action">
                                <i class="fas fa-user-edit"></i>
                                Edit Profile
                            </a>
                            <a href="index.php?page=products" class="btn-action">
                                <i class="fas fa-shopping-cart"></i>
                                Shop Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
```

# views/account/order_details.php  
```php
<?php require_once __DIR__ . '/../layout/header.php'; ?>

<section class="account-section">
    <div class="container">
        <div class="account-grid">
            <!-- Sidebar Navigation -->
            <aside class="account-sidebar" data-aos="fade-right">
                <div class="account-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <h3><?= htmlspecialchars($user['name']) ?></h3>
                        <p><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    
                    <nav>
                        <ul>
                            <li>
                                <a href="index.php?page=account">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=orders" class="active">
                                    <i class="fas fa-shopping-bag"></i> My Orders
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=profile">
                                    <i class="fas fa-user"></i> Profile Settings
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=quiz">
                                    <i class="fas fa-clipboard-list"></i> Quiz History
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=logout">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </aside>
            
            <!-- Main Content -->
            <div class="account-content">
                <div class="order-details-header" data-aos="fade-up">
                    <div class="header-left">
                        <a href="index.php?page=account&section=orders" class="back-link">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                        <h1>Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h1>
                    </div>
                    <div class="header-right">
                        <span class="order-date">
                            <?= date('F j, Y', strtotime($order['created_at'])) ?>
                        </span>
                        <span class="order-status <?= $order['status'] ?>">
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </div>
                </div>
                
                <!-- Order Progress -->
                <?php if ($order['status'] !== 'cancelled'): ?>
                    <div class="order-progress" data-aos="fade-up">
                        <?php
                        $statuses = ['processing', 'confirmed', 'shipped', 'delivered'];
                        $currentIndex = array_search($order['status'], $statuses);
                        foreach ($statuses as $index => $status):
                            $isActive = $index <= $currentIndex;
                            $isCompleted = $index < $currentIndex;
                        ?>
                            <div class="progress-step <?= $isActive ? 'active' : '' ?>">
                                <div class="step-icon">
                                    <?php if ($isCompleted): ?>
                                        <i class="fas fa-check"></i>
                                    <?php else: ?>
                                        <i class="fas fa-<?= $status === 'processing' ? 'clock' : 
                                                          ($status === 'confirmed' ? 'check' :
                                                          ($status === 'shipped' ? 'truck' : 'box')) ?>"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="step-label">
                                    <?= ucfirst($status) ?>
                                    <?php if ($status === $order['status']): ?>
                                        <span class="step-date">
                                            <?= date('M j', strtotime($order[$status . '_date'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($index < count($statuses) - 1): ?>
                                <div class="progress-line <?= $isActive ? 'active' : '' ?>"></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="order-details-grid">
                    <!-- Order Items -->
                    <div class="order-items-card" data-aos="fade-up">
                        <h2>Order Items</h2>
                        <div class="items-list">
                            <?php foreach (json_decode($order['items'], true) as $item): ?>
                                <div class="order-item">
                                    <div class="item-image">
                                        <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                             alt="<?= htmlspecialchars($item['name']) ?>">
                                    </div>
                                    <div class="item-details">
                                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                                        <p class="item-meta">
                                            Quantity: <?= $item['quantity'] ?> |
                                            Price: $<?= number_format($item['price'], 2) ?>
                                        </p>
                                        <?php if (!empty($item['options'])): ?>
                                            <p class="item-options">
                                                Options: <?= htmlspecialchars(implode(', ', $item['options'])) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-actions">
                                        <span class="item-total">
                                            $<?= number_format($item['quantity'] * $item['price'], 2) ?>
                                        </span>
                                        <form action="index.php?page=cart&action=add" method="POST">
                                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="quantity" value="<?= $item['quantity'] ?>">
                                            <button type="submit" class="btn-secondary">Buy Again</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="order-summary-card" data-aos="fade-up">
                        <h2>Order Summary</h2>
                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>$<?= number_format($order['subtotal'], 2) ?></span>
                            </div>
                            <?php if ($order['discount_amount'] > 0): ?>
                                <div class="summary-row discount">
                                    <span>
                                        Discount 
                                        <?php if ($order['coupon_code']): ?>
                                            <div class="coupon-tag">
                                                <i class="fas fa-tag"></i>
                                                <?= htmlspecialchars($order['coupon_code']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </span>
                                    <span>-$<?= number_format($order['discount_amount'], 2) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="summary-row">
                                <span>Shipping</span>
                                <span><?= $order['shipping_cost'] > 0 ? '$' . number_format($order['shipping_cost'], 2) : 'FREE' ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Tax</span>
                                <span>$<?= number_format($order['tax_amount'], 2) ?></span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span>$<?= number_format($order['total_amount'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping Information -->
                    <div class="shipping-info-card" data-aos="fade-up">
                        <h2>Shipping Information</h2>
                        <div class="shipping-details">
                            <div class="address-section">
                                <h3>Delivery Address</h3>
                                <address>
                                    <?= htmlspecialchars($order['shipping_name']) ?><br>
                                    <?= nl2br(htmlspecialchars($order['shipping_address'])) ?><br>
                                    <?= htmlspecialchars($order['shipping_city']) ?>, 
                                    <?= htmlspecialchars($order['shipping_state']) ?> 
                                    <?= htmlspecialchars($order['shipping_zip']) ?><br>
                                    <?= htmlspecialchars($order['shipping_country']) ?>
                                </address>
                            </div>
                            
                            <?php if ($order['status'] === 'shipped'): ?>
                                <div class="tracking-section">
                                    <h3>Tracking Information</h3>
                                    <p class="tracking-number">
                                        <i class="fas fa-truck"></i>
                                        Tracking Number: <?= htmlspecialchars($order['tracking_number']) ?>
                                    </p>
                                    <a href="<?= htmlspecialchars($order['tracking_url']) ?>" 
                                       class="btn-primary" target="_blank">
                                        Track Package
                                    </a>
                                    <p class="estimated-delivery">
                                        Estimated Delivery: <?= date('F j, Y', strtotime($order['estimated_delivery'])) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Additional Actions -->
                    <div class="order-actions-card" data-aos="fade-up">
                        <h2>Need Help?</h2>
                        <div class="action-buttons">
                            <a href="index.php?page=support&order=<?= $order['id'] ?>" class="btn-secondary">
                                <i class="fas fa-question-circle"></i>
                                Contact Support
                            </a>
                            <?php if ($order['status'] === 'processing'): ?>
                                <a href="index.php?page=account&section=orders&id=<?= $order['id'] ?>&action=cancel" 
                                   class="btn-danger" 
                                   onclick="return confirm('Are you sure you want to cancel this order?')">
                                    <i class="fas fa-times"></i>
                                    Cancel Order
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.coupon-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background-color: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    color: #374151;
    margin-left: 0.5rem;
}

.coupon-tag i {
    color: #059669;
}

.summary-row.discount {
    color: #059669;
}
</style>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
```

# views/account/orders.php  
```php
<?php require_once __DIR__ . '/../layout/header.php'; ?>

<section class="account-section">
    <div class="container">
        <div class="account-grid">
            <!-- Sidebar Navigation -->
            <aside class="account-sidebar" data-aos="fade-right">
                <div class="account-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <h3><?= htmlspecialchars($user['name']) ?></h3>
                        <p><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    
                    <nav>
                        <ul>
                            <li>
                                <a href="index.php?page=account">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=orders" class="active">
                                    <i class="fas fa-shopping-bag"></i> My Orders
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=profile">
                                    <i class="fas fa-user"></i> Profile Settings
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=quiz">
                                    <i class="fas fa-clipboard-list"></i> Quiz History
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=logout">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </aside>
            
            <!-- Main Content -->
            <div class="account-content">
                <h1 class="page-title" data-aos="fade-up">Order History</h1>
                
                <?php if (empty($orders)): ?>
                    <div class="empty-state" data-aos="fade-up">
                        <i class="fas fa-shopping-bag"></i>
                        <p>You haven't placed any orders yet</p>
                        <a href="index.php?page=products" class="btn-primary">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="orders-container">
                        <!-- Order Filter -->
                        <div class="order-filters" data-aos="fade-up">
                            <select id="orderStatus" class="form-select">
                                <option value="">All Orders</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            
                            <select id="orderTime" class="form-select">
                                <option value="">All Time</option>
                                <option value="30">Last 30 Days</option>
                                <option value="90">Last 3 Months</option>
                                <option value="365">Last Year</option>
                            </select>
                        </div>
                        
                        <!-- Orders List -->
                        <div class="orders-list" data-aos="fade-up">
                            <?php foreach ($orders as $order): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div class="order-meta">
                                            <h3>Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                                            <span class="order-date">
                                                <?= date('F j, Y', strtotime($order['created_at'])) ?>
                                            </span>
                                        </div>
                                        <span class="order-status <?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="order-items">
                                        <?php foreach (json_decode($order['items'], true) as $item): ?>
                                            <div class="order-item">
                                                <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                                     alt="<?= htmlspecialchars($item['name']) ?>">
                                                <div class="item-details">
                                                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                                                    <p class="item-meta">
                                                        Quantity: <?= $item['quantity'] ?> |
                                                        Price: $<?= number_format($item['price'], 2) ?>
                                                    </p>
                                                </div>
                                                <div class="item-total">
                                                    $<?= number_format($item['quantity'] * $item['price'], 2) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="order-footer">
                                        <div class="order-summary">
                                            <div class="summary-row">
                                                <span>Subtotal:</span>
                                                <span>$<?= number_format($order['subtotal'], 2) ?></span>
                                            </div>
                                            <div class="summary-row">
                                                <span>Shipping:</span>
                                                <span>$<?= number_format($order['shipping_cost'], 2) ?></span>
                                            </div>
                                            <?php if ($order['discount_amount'] > 0): ?>
                                                <div class="summary-row discount">
                                                    <span>Discount:</span>
                                                    <span>-$<?= number_format($order['discount_amount'], 2) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="summary-row total">
                                                <span>Total:</span>
                                                <span>$<?= number_format($order['total_amount'], 2) ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="order-actions">
                                            <a href="index.php?page=account&section=orders&id=<?= $order['id'] ?>" 
                                               class="btn-secondary">View Details</a>
                                            <?php if ($order['status'] === 'shipped'): ?>
                                                <a href="<?= htmlspecialchars($order['tracking_url']) ?>" 
                                                   class="btn-primary" target="_blank">
                                                    Track Package
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination" data-aos="fade-up">
                                <?php if ($page > 1): ?>
                                    <a href="?page=account&section=orders&p=<?= $page - 1 ?>" 
                                       class="pagination-link">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <a href="?page=account&section=orders&p=<?= $i ?>" 
                                       class="pagination-link <?= ($i === $page) ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=account&section=orders&p=<?= $page + 1 ?>" 
                                       class="pagination-link">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Order filtering
    const orderStatus = document.getElementById('orderStatus');
    const orderTime = document.getElementById('orderTime');
    
    function filterOrders() {
        const url = new URL(window.location.href);
        
        if (orderStatus.value) {
            url.searchParams.set('status', orderStatus.value);
        } else {
            url.searchParams.delete('status');
        }
        
        if (orderTime.value) {
            url.searchParams.set('time', orderTime.value);
        } else {
            url.searchParams.delete('time');
        }
        
        window.location.href = url.toString();
    }
    
    orderStatus.addEventListener('change', filterOrders);
    orderTime.addEventListener('change', filterOrders);
    
    // Set initial filter values from URL
    const params = new URLSearchParams(window.location.search);
    if (params.has('status')) {
        orderStatus.value = params.get('status');
    }
    if (params.has('time')) {
        orderTime.value = params.get('time');
    }
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
```

# views/account/profile.php  
```php
<?php require_once __DIR__ . '/../layout/header.php'; ?>

<section class="account-section">
    <div class="container">
        <div class="account-grid">
            <!-- Sidebar Navigation -->
            <aside class="account-sidebar" data-aos="fade-right">
                <div class="account-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <h3><?= htmlspecialchars($user['name']) ?></h3>
                        <p><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    
                    <nav>
                        <ul>
                            <li>
                                <a href="index.php?page=account">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=orders">
                                    <i class="fas fa-shopping-bag"></i> My Orders
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=profile" class="active">
                                    <i class="fas fa-user"></i> Profile Settings
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=quiz">
                                    <i class="fas fa-clipboard-list"></i> Quiz History
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=logout">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </aside>
            
            <!-- Main Content -->
            <div class="account-content">
                <h1 class="page-title" data-aos="fade-up">Profile Settings</h1>
                
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash_type'] ?>" data-aos="fade-up">
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                    </div>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>
                
                <div class="profile-grid">
                    <!-- Personal Information -->
                    <div class="profile-card" data-aos="fade-up">
                        <h2>Personal Information</h2>
                        <form action="index.php?page=account&section=profile&action=update" method="POST" 
                              class="profile-form" id="profileForm">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" required
                                       value="<?= htmlspecialchars($user['name']) ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" required
                                       value="<?= htmlspecialchars($user['email']) ?>">
                            </div>
                            
                            <button type="submit" class="btn-primary">Save Changes</button>
                        </form>
                    </div>
                    
                    <!-- Change Password -->
                    <div class="profile-card" data-aos="fade-up">
                        <h2>Change Password</h2>
                        <form action="index.php?page=account&section=profile&action=update" method="POST" 
                              class="password-form" id="passwordForm">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <div class="password-input">
                                    <input type="password" id="current_password" name="current_password">
                                    <button type="button" class="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <div class="password-input">
                                    <input type="password" id="new_password" name="new_password"
                                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                           title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                                    <button type="button" class="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <div class="password-input">
                                    <input type="password" id="confirm_password" name="confirm_password">
                                    <button type="button" class="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="password-requirements">
                                <h4>Password Requirements:</h4>
                                <ul>
                                    <li id="length">At least 8 characters</li>
                                    <li id="uppercase">One uppercase letter</li>
                                    <li id="lowercase">One lowercase letter</li>
                                    <li id="number">One number</li>
                                </ul>
                            </div>
                            
                            <button type="submit" class="btn-primary">Update Password</button>
                        </form>
                    </div>
                    
                    <!-- Communication Preferences -->
                    <div class="profile-card" data-aos="fade-up">
                        <h2>Communication Preferences</h2>
                        <form action="index.php?page=account&section=profile&action=update" method="POST" 
                              class="preferences-form">
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="email_marketing" 
                                           <?= $user['email_marketing'] ? 'checked' : '' ?>>
                                    <span>Promotional emails about new products and special offers</span>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="email_orders" 
                                           <?= $user['email_orders'] ? 'checked' : '' ?>>
                                    <span>Order status updates and shipping notifications</span>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="email_newsletter" 
                                           <?= $user['email_newsletter'] ? 'checked' : '' ?>>
                                    <span>Monthly newsletter with aromatherapy tips and trends</span>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn-primary">Update Preferences</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });
    
    // Password validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const requirements = {
        length: document.getElementById('length'),
        uppercase: document.getElementById('uppercase'),
        lowercase: document.getElementById('lowercase'),
        number: document.getElementById('number')
    };
    
    newPassword.addEventListener('input', function() {
        const password = this.value;
        
        // Update requirement indicators
        requirements.length.classList.toggle('met', password.length >= 8);
        requirements.uppercase.classList.toggle('met', /[A-Z]/.test(password));
        requirements.lowercase.classList.toggle('met', /[a-z]/.test(password));
        requirements.number.classList.toggle('met', /\d/.test(password));
    });
    
    // Form validation
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        if (newPassword.value !== confirmPassword.value) {
            e.preventDefault();
            alert('New passwords do not match.');
            return;
        }
        
        if (newPassword.value && !newPassword.checkValidity()) {
            e.preventDefault();
            alert('Please meet all password requirements.');
            return;
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
```

# views/checkout.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>
<!-- Output CSRF token for JS (for AJAX checkout/coupon/tax) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

<!-- Add Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>

<section class="checkout-section">
    <div class="container">
        <div class="checkout-container" data-aos="fade-up">
            <h1>Checkout</h1>

            <div class="checkout-grid">
                <!-- Shipping Form -->
                <div class="shipping-details">
                    <h2>Shipping Details</h2>
                    <!-- NOTE: The form tag itself doesn't need action/method as JS handles the submission -->
                    <form id="checkoutForm">
                        <!-- ADD Standard CSRF Token for initial server-side check during processCheckout -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <!-- Hidden field to potentially store applied coupon code -->
                        <input type="hidden" id="applied_coupon_code" name="applied_coupon_code" value="">

                        <div class="form-group">
                            <label for="shipping_name">Full Name *</label>
                            <input type="text" id="shipping_name" name="shipping_name" required class="form-input"
                                   value="<?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="shipping_email">Email Address *</label>
                            <input type="email" id="shipping_email" name="shipping_email" required class="form-input"
                                   value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="shipping_address">Street Address *</label>
                            <input type="text" id="shipping_address" name="shipping_address" required class="form-input"
                                   value="<?= htmlspecialchars($userAddress['address_line1'] ?? '') ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_city">City *</label>
                                <input type="text" id="shipping_city" name="shipping_city" required class="form-input"
                                       value="<?= htmlspecialchars($userAddress['city'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="shipping_state">State/Province *</label>
                                <input type="text" id="shipping_state" name="shipping_state" required class="form-input"
                                       value="<?= htmlspecialchars($userAddress['state'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_zip">ZIP/Postal Code *</label>
                                <input type="text" id="shipping_zip" name="shipping_zip" required class="form-input"
                                       value="<?= htmlspecialchars($userAddress['postal_code'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="shipping_country">Country *</label>
                                <select id="shipping_country" name="shipping_country" required class="form-select">
                                    <option value="">Select Country</option>
                                    <option value="US" <?= (($userAddress['country'] ?? '') === 'US') ? 'selected' : '' ?>>United States</option>
                                    <option value="CA" <?= (($userAddress['country'] ?? '') === 'CA') ? 'selected' : '' ?>>Canada</option>
                                    <option value="GB" <?= (($userAddress['country'] ?? '') === 'GB') ? 'selected' : '' ?>>United Kingdom</option>
                                    <!-- Add more countries as needed -->
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="order_notes">Order Notes (Optional)</label>
                            <textarea id="order_notes" name="order_notes" rows="3" class="form-textarea"></textarea>
                        </div>
                        <!-- The submit button is now outside the form, controlled by JS -->
                    </form>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <h2>Order Summary</h2>

                    <!-- Coupon Code Section -->
                    <div class="coupon-section">
                        <div class="form-group">
                            <label for="coupon_code">Have a coupon?</label>
                            <div class="coupon-input">
                                <input type="text" id="coupon_code" name="coupon_code_input" class="form-input"
                                       placeholder="Enter coupon code">
                                <button type="button" id="apply-coupon" class="btn-secondary">Apply</button>
                            </div>
                            <div id="coupon-message" class="hidden mt-2 text-sm"></div>
                        </div>
                    </div>

                    <div class="summary-items border-b border-gray-200 pb-4 mb-4">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="summary-item flex justify-between items-center text-sm py-1">
                                <div class="item-info flex items-center">
                                     <img src="<?= htmlspecialchars($item['product']['image'] ?? '/images/placeholder.jpg') ?>" alt="<?= htmlspecialchars($item['product']['name']) ?>" class="w-10 h-10 object-cover rounded mr-2">
                                     <div>
                                         <span class="item-name font-medium text-gray-800"><?= htmlspecialchars($item['product']['name']) ?></span>
                                         <span class="text-xs text-gray-500 block">Qty: <?= $item['quantity'] ?></span>
                                     </div>
                                </div>
                                <span class="item-price font-medium text-gray-700">$<?= number_format($item['subtotal'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-totals space-y-2">
                        <div class="summary-row flex justify-between items-center">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-medium text-gray-900">$<span id="summary-subtotal"><?= number_format($subtotal, 2) ?></span></span>
                        </div>
                         <div class="summary-row discount hidden flex justify-between items-center text-green-600">
                            <span>Discount (<span id="applied-coupon-code-display" class="font-mono text-xs bg-green-100 px-1 rounded"></span>):</span>
                            <span>-$<span id="discount-amount">0.00</span></span>
                        </div>
                        <div class="summary-row flex justify-between items-center">
                            <span class="text-gray-600">Shipping:</span>
                            <span class="font-medium text-gray-900" id="summary-shipping"><?= $shipping_cost > 0 ? '$' . number_format($shipping_cost, 2) : '<span class="text-green-600">FREE</span>' ?></span>
                        </div>
                        <div class="summary-row flex justify-between items-center">
                            <span class="text-gray-600">Tax (<span id="tax-rate" class="text-xs"><?= htmlspecialchars($tax_rate_formatted) ?></span>):</span>
                            <span class="font-medium text-gray-900" id="tax-amount">$<?= number_format($tax_amount, 2) ?></span>
                        </div>
                        <div class="summary-row total flex justify-between items-center border-t pt-3 mt-2">
                            <span class="text-lg font-bold text-gray-900">Total:</span>
                            <span class="text-lg font-bold text-primary">$<span id="summary-total"><?= number_format($total, 2) ?></span></span>
                        </div>
                    </div>

                    <div class="payment-section mt-6">
                        <h3 class="text-lg font-semibold mb-4">Payment Method</h3>
                        <!-- Stripe Payment Element -->
                        <div id="payment-element" class="mb-4 p-3 border rounded bg-gray-50"></div>
                        <!-- Used to display form errors -->
                        <div id="payment-message" class="hidden text-red-600 text-sm text-center mb-4"></div>
                    </div>

                    <!-- Button is outside the form, triggered by JS -->
                    <button type="button" id="submit-button" class="btn btn-primary w-full place-order">
                        <span id="button-text">Place Order & Pay</span>
                        <div class="spinner hidden" id="spinner"></div>
                    </button>

                    <div class="secure-checkout mt-4 text-center text-xs text-gray-500">
                        <i class="fas fa-lock mr-1"></i>Secure Checkout via Stripe
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Configuration ---
    const stripePublicKey = '<?= defined('STRIPE_PUBLIC_KEY') ? STRIPE_PUBLIC_KEY : '' ?>';
    const checkoutForm = document.getElementById('checkoutForm');
    const submitButton = document.getElementById('submit-button');
    const spinner = document.getElementById('spinner');
    const buttonText = document.getElementById('button-text');
    const paymentElementContainer = document.getElementById('payment-element');
    const paymentMessage = document.getElementById('payment-message');
    const csrfToken = document.getElementById('csrf-token-value').value;
    const couponCodeInput = document.getElementById('coupon_code');
    const applyCouponButton = document.getElementById('apply-coupon');
    const couponMessageEl = document.getElementById('coupon-message');
    const discountRow = document.querySelector('.summary-row.discount');
    const discountAmountEl = document.getElementById('discount-amount');
    const appliedCouponCodeDisplay = document.getElementById('applied-coupon-code-display');
    const appliedCouponHiddenInput = document.getElementById('applied_coupon_code'); // For sending with checkout
    const taxRateEl = document.getElementById('tax-rate');
    const taxAmountEl = document.getElementById('tax-amount');
    const shippingCountryEl = document.getElementById('shipping_country');
    const shippingStateEl = document.getElementById('shipping_state');
    const summarySubtotalEl = document.getElementById('summary-subtotal');
    const summaryShippingEl = document.getElementById('summary-shipping');
    const summaryTotalEl = document.getElementById('summary-total');

    let elements;
    let stripe;
    let currentSubtotal = <?= $subtotal ?? 0 ?>;
    let currentShippingCost = <?= $shipping_cost ?? 0 ?>;
    let currentTaxAmount = <?= $tax_amount ?? 0 ?>;
    let currentDiscountAmount = 0;

    if (!stripePublicKey) {
        showMessage("Stripe configuration error. Payment cannot proceed.");
        setLoading(false, true); // Disable button permanently
        return;
    }
    stripe = Stripe(stripePublicKey);

    // --- Initialize Stripe Elements ---
    const appearance = {
         theme: 'stripe',
         variables: {
             colorPrimary: '#1A4D5A', // Match theme
             colorBackground: '#ffffff',
             colorText: '#374151', // Tailwind gray-700
             colorDanger: '#dc2626', // Tailwind red-600
             fontFamily: 'Montserrat, sans-serif', // Match theme
             borderRadius: '0.375rem' // Tailwind rounded-md
         }
     };
    elements = stripe.elements({ appearance });
    const paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');

    // --- Helper Functions ---
    function setLoading(isLoading, disablePermanently = false) {
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
        paymentMessage.textContent = message;
        paymentMessage.className = `payment-message text-center text-sm my-4 ${isError ? 'text-red-600' : 'text-green-600'}`;
        paymentMessage.classList.remove('hidden');
        // Auto-hide?
        // setTimeout(() => { paymentMessage.classList.add('hidden'); }, 6000);
    }

    function showCouponMessage(message, type) { // type = 'success', 'error', 'info'
        couponMessageEl.textContent = message;
        couponMessageEl.className = `coupon-message mt-2 text-sm ${
            type === 'success' ? 'text-green-600' : (type === 'error' ? 'text-red-600' : 'text-gray-600')
        }`;
        couponMessageEl.classList.remove('hidden');
    }

    function updateOrderSummaryUI() {
        // Update subtotal (shouldn't change unless cart changes, which redirects)
        summarySubtotalEl.textContent = parseFloat(currentSubtotal).toFixed(2);

        // Update discount display
        if (currentDiscountAmount > 0 && appliedCouponHiddenInput.value) {
            discountAmountEl.textContent = parseFloat(currentDiscountAmount).toFixed(2);
            appliedCouponCodeDisplay.textContent = appliedCouponHiddenInput.value;
            discountRow.classList.remove('hidden');
        } else {
            discountAmountEl.textContent = '0.00';
            appliedCouponCodeDisplay.textContent = '';
            discountRow.classList.add('hidden');
        }

         // Update shipping cost display (based on subtotal AFTER discount)
         const subtotalAfterDiscount = currentSubtotal - currentDiscountAmount;
         currentShippingCost = subtotalAfterDiscount >= <?= FREE_SHIPPING_THRESHOLD ?> ? 0 : <?= SHIPPING_COST ?>;
         summaryShippingEl.innerHTML = currentShippingCost > 0 ? '$' + parseFloat(currentShippingCost).toFixed(2) : '<span class="text-green-600">FREE</span>';


        // Update tax amount display (based on AJAX call result)
        taxAmountEl.textContent = '$' + parseFloat(currentTaxAmount).toFixed(2);

        // Update total
        const grandTotal = (currentSubtotal - currentDiscountAmount) + currentShippingCost + currentTaxAmount;
        summaryTotalEl.textContent = parseFloat(Math.max(0, grandTotal)).toFixed(2); // Prevent negative total display
    }

    // --- Tax Calculation ---
    async function updateTax() {
        const country = shippingCountryEl.value;
        const state = shippingStateEl.value;

        if (!country) {
            // Reset tax if no country selected
             taxRateEl.textContent = '0%';
             currentTaxAmount = 0;
             updateOrderSummaryUI(); // Update total
            return;
        }

        try {
            // Add a subtle loading indicator? Maybe on the tax amount?
            taxAmountEl.textContent = '...';

            const response = await fetch('index.php?page=checkout&action=calculateTax', { // Correct action name from routing
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                 },
                body: JSON.stringify({ country, state })
            });

            if (!response.ok) throw new Error('Tax calculation failed');

            const data = await response.json();
            if (data.success) {
                taxRateEl.textContent = data.tax_rate_formatted || 'N/A';
                currentTaxAmount = parseFloat(data.tax_amount) || 0;
                // Don't update total directly here, let updateOrderSummaryUI handle it
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

    shippingCountryEl.addEventListener('change', updateTax);
    shippingStateEl.addEventListener('input', updateTax); // Use input for faster response if typing state

    // --- Coupon Application ---
    applyCouponButton.addEventListener('click', async function() {
        const couponCode = couponCodeInput.value.trim();
        if (!couponCode) {
            showCouponMessage('Please enter a coupon code.', 'error');
            return;
        }

        showCouponMessage('Applying...', 'info');
        applyCouponButton.disabled = true;

        try {
            const response = await fetch('index.php?page=checkout&action=applyCouponAjax', { // Use the new controller action
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    code: couponCode,
                    subtotal: currentSubtotal, // Send current subtotal for validation
                    csrf_token: csrfToken
                })
            });

             if (!response.ok) throw new Error(`Server error: ${response.status} ${response.statusText}`);

            const data = await response.json();

            if (data.success) {
                showCouponMessage(data.message || 'Coupon applied!', 'success');
                currentDiscountAmount = parseFloat(data.discount_amount) || 0;
                appliedCouponHiddenInput.value = data.coupon_code || couponCode; // Store applied code
                // Update tax and total based on server response if available
                // currentTaxAmount = parseFloat(data.new_tax_amount ?? currentTaxAmount); // Update tax if server recalculated it
                // Update totals based on new discount and potentially new tax
                 updateTax(); // Re-calculate tax and update summary after applying coupon discount
            } else {
                showCouponMessage(data.message || 'Invalid coupon code.', 'error');
                currentDiscountAmount = 0; // Reset discount
                appliedCouponHiddenInput.value = ''; // Clear applied code
                updateOrderSummaryUI(); // Update summary without discount
            }
        } catch (e) {
            console.error('Coupon Apply Error:', e);
            showCouponMessage('Failed to apply coupon. Please try again.', 'error');
            currentDiscountAmount = 0;
            appliedCouponHiddenInput.value = '';
            updateOrderSummaryUI();
        } finally {
            applyCouponButton.disabled = false;
        }
    });

    // --- Checkout Form Submission ---
    submitButton.addEventListener('click', async function(e) {
        // Use click on the button instead of form submit, as the form tag is mainly for structure now
        setLoading(true);
        showMessage(''); // Clear previous messages

        // 1. Client-side validation
        let isValid = true;
        const requiredFields = ['shipping_name', 'shipping_email', 'shipping_address', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];
        requiredFields.forEach(id => {
            const input = document.getElementById(id);
            if (!input || !input.value.trim()) {
                isValid = false;
                input?.classList.add('input-error'); // Add error class for styling
                 // Find label and add error state? More complex UI work.
            } else {
                input?.classList.remove('input-error');
            }
        });

        if (!isValid) {
            showMessage('Please fill in all required shipping fields.');
            setLoading(false);
             // Scroll to first error?
             const firstError = checkoutForm.querySelector('.input-error');
             firstError?.focus();
             firstError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // 2. Send checkout data to server to create order and get clientSecret
        let clientSecret = null;
        let orderId = null;
        try {
            const checkoutFormData = new FormData(checkoutForm); // Includes CSRF, applied coupon, shipping fields

            const response = await fetch('index.php?page=checkout&action=processCheckout', { // Use a unique action name
                method: 'POST',
                headers: {
                    // Content-Type is set automatically for FormData
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: checkoutFormData
            });

            const data = await response.json();

            if (response.ok && data.success && data.clientSecret && data.orderId) {
                clientSecret = data.clientSecret;
                orderId = data.orderId;
            } else {
                throw new Error(data.error || 'Failed to process order on server. Please check details or try again.');
            }

        } catch (serverError) {
            console.error('Server processing error:', serverError);
            showMessage(serverError.message);
            setLoading(false);
            return; // Stop checkout
        }

        // 3. Confirm payment with Stripe using the obtained clientSecret
        if (clientSecret) {
            const { error: stripeError } = await stripe.confirmPayment({
                elements,
                clientSecret: clientSecret,
                confirmParams: {
                     // IMPORTANT: Use the correct BASE_URL constant here
                    return_url: `${window.location.origin}<?= rtrim(BASE_URL, '/') ?>/index.php?page=checkout&action=confirmation`,
                     // Optional: Send billing details again, though Stripe might capture from element
                     payment_method_data: {
                         billing_details: {
                             name: document.getElementById('shipping_name').value,
                             email: document.getElementById('shipping_email').value,
                             address: {
                                 line1: document.getElementById('shipping_address').value,
                                 city: document.getElementById('shipping_city').value,
                                 state: document.getElementById('shipping_state').value,
                                 postal_code: document.getElementById('shipping_zip').value,
                                 country: document.getElementById('shipping_country').value,
                             }
                         }
                     }
                },
                // Redirect 'if_required' handles 3DS etc. Stripe redirects on success.
                redirect: 'if_required'
            });

            // If we reach here, confirmPayment failed or requires manual action
            if (stripeError) {
                 console.error("Stripe Error:", stripeError);
                 showMessage(stripeError.message || "Payment failed. Please check your card details or try another method.");
                 setLoading(false); // Re-enable button on failure
            }
            // No explicit success redirect needed here as Stripe handles it via return_url
        } else {
            // Should have been caught earlier, but safety check
            showMessage('Failed to get payment details from server.');
            setLoading(false);
        }
    });

    // --- Initial UI Update ---
    updateOrderSummaryUI(); // Calculate initial totals
    // Optionally trigger initial tax calculation if country is pre-filled
    if (shippingCountryEl.value) {
        updateTax();
    }

});
</script>

<style>
/* Basic styles for loading/error states */
.spinner {
    width: 1.25em; /* 20px */
    height: 1.25em; /* 20px */
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #ffffff;
    animation: spin 1s linear infinite;
    display: inline-block; /* Changed from margin auto */
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.hidden {
    display: none;
}

.place-order:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.input-error {
    border-color: #dc2626; /* Tailwind red-600 */
    box-shadow: 0 0 0 1px #dc2626;
}
.form-input, .form-select, .form-textarea {
     /* Add some base styling for form elements if not globally defined */
     width: 100%;
     padding: 0.65rem 0.75rem;
     border: 1px solid #d1d5db; /* Tailwind gray-300 */
     border-radius: 0.375rem; /* Tailwind rounded-md */
     transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.form-input:focus, .form-select:focus, .form-textarea:focus {
     outline: none;
     border-color: #1A4D5A; /* primary */
     box-shadow: 0 0 0 2px rgba(26, 77, 90, 0.3);
}

</style>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

```

