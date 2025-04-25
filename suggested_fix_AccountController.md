Okay, I understand. The provided `AccountController.php` file clearly suffers from repetition, likely due to an automated process gone wrong.

I will now analyze the different sections, identify the unique and duplicated methods, and synthesize them into a single, clean, and functional `AccountController` class based on the apparent intended logic.

```php
<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Quiz.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/EmailService.php';
require_once __DIR__ . '/../includes/SecurityMiddleware.php'; // Assuming SecurityMiddleware is used for static methods
require_once __DIR__ . '/CartController.php'; // Needed for mergeSessionCartOnLogin

class AccountController extends BaseController {
    private EmailService $emailService;
    private User $userModel;
    private Order $orderModel;
    private Quiz $quizModel;

    // Constants (moved from duplicate definitions)
    private int $maxLoginAttempts = 5; // Example value, might need adjustment
    private int $lockoutDuration = 900; // 15 minutes
    private int $resetTokenExpiry = 3600; // 1 hour

    // Security Headers (applied in constructor)
    private array $securityHeaders = [
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        // Note: CSP might need refinement based on actual scripts/styles used
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';",
        'Referrer-Policy' => 'strict-origin-when-cross-origin'
    ];

    public function __construct(PDO $pdo) {
        parent::__construct($pdo);
        $this->emailService = new EmailService();
        $this->userModel = new User($pdo);
        $this->orderModel = new Order($pdo);
        $this->quizModel = new Quiz($pdo);

        // Set security headers - Ensure headers are not already sent
        if (!headers_sent()) {
            foreach ($this->securityHeaders as $header => $value) {
                header("$header: $value");
            }
        }
    }

    // --- Account Management Pages ---

    public function showDashboard() {
        try {
            $this->requireLogin();
            $userId = $this->getUserId();

            // Use transaction for consistency if multiple reads depend on each other
            // Though for simple reads like this, it might be optional unless high consistency is required.
            $this->pdo->beginTransaction();

            try {
                $recentOrders = $this->orderModel->getRecentByUserId($userId, 5);
                $quizResults = $this->quizModel->getResultsByUserId($userId);

                $this->pdo->commit();

                return $this->renderView('account/dashboard', [
                    'pageTitle' => 'My Account - The Scent',
                    'recentOrders' => $recentOrders,
                    'quizResults' => $quizResults
                ]);

            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e; // Re-throw to be caught by the outer catch block
            }

        } catch (Exception $e) {
            error_log("Dashboard error: " . $e->getMessage());
            $this->setFlashMessage('Error loading dashboard. Please try again later.', 'error');
            return $this->redirect('error'); // Assuming an 'error' route exists
        }
    }

    public function showOrders() {
        try {
            $this->requireLogin();
            $userId = $this->getUserId();

            // Validate and sanitize pagination params
            // Using filter_input for better security
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
            $perPage = 10; // Consider making this configurable

            // Get paginated orders
            $orders = $this->orderModel->getAllByUserId($userId, $page, $perPage);
            $totalOrders = $this->orderModel->getTotalOrdersByUserId($userId);
            $totalPages = ceil($totalOrders / $perPage);

            return $this->renderView('account/orders', [
                'pageTitle' => 'My Orders - The Scent',
                'orders' => $orders,
                'currentPage' => $page,
                'totalPages' => $totalPages
            ]);

        } catch (Exception $e) {
            error_log("Orders error: " . $e->getMessage());
            $this->setFlashMessage('Error loading orders. Please try again later.', 'error');
            return $this->redirect('error'); // Assuming an 'error' route exists
        }
    }

    public function showOrderDetails(int $orderId) { // Type hint for clarity
        try {
            $this->requireLogin();
            $userId = $this->getUserId();

            // Basic validation (Type hint helps, but check > 0)
            if ($orderId <= 0) {
                 $this->setFlashMessage('Invalid order ID.', 'error');
                 return $this->redirect('account/orders');
            }

            // Get order with auth check
            $order = $this->orderModel->getByIdAndUserId($orderId, $userId);
            if (!$order) {
                // Log attempt to access unauthorized/non-existent order
                error_log("User {$userId} failed to access order {$orderId}");
                // Consider rendering a specific "Not Found" or "Access Denied" view
                return $this->renderView('404', ['pageTitle' => 'Order Not Found']);
            }

            return $this->renderView('account/order_details', [
                'pageTitle' => "Order #" . htmlspecialchars(str_pad($order['id'], 6, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8') . " - The Scent",
                'order' => $order
            ]);

        } catch (Exception $e) {
            error_log("Order details error for order {$orderId}: " . $e->getMessage());
            $this->setFlashMessage('Error loading order details. Please try again later.', 'error');
            return $this->redirect('account/orders');
        }
    }

    public function showProfile() {
        try {
            $this->requireLogin();
            $user = $this->getCurrentUser(); // Assumes BaseController provides this securely

            if (!$user) {
                 $this->setFlashMessage('Could not load user profile.', 'error');
                 return $this->redirect('login'); // Redirect to login if user somehow lost
            }

            return $this->renderView('account/profile', [
                'pageTitle' => 'My Profile - The Scent',
                'user' => $user
            ]);

        } catch (Exception $e) {
            error_log("Profile show error: " . $e->getMessage());
            $this->setFlashMessage('Error loading profile. Please try again later.', 'error');
            return $this->redirect('error'); // Assuming an 'error' route exists
        }
    }

    public function updateProfile() {
        try {
            $this->requireLogin();
            $this->validateCSRF(); // Assumes BaseController provides this

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                // Redirect if not a POST request, maybe with a message
                $this->setFlashMessage('Invalid request method.', 'warning');
                return $this->redirect('account/profile');
            }

            $userId = $this->getUserId();

            // Validate inputs using a more robust method if possible (e.g., filter_input)
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $currentPassword = $_POST['current_password'] ?? ''; // Keep direct access for password
            $newPassword = $_POST['new_password'] ?? '';         // Keep direct access for password

            // Basic validation checks
            if (empty($name)) {
                throw new Exception('Name is required.');
            }
            if ($email === false || $email === null) { // Check filter_input result properly
                 throw new Exception('A valid email address is required.');
            }

            $this->pdo->beginTransaction();

            try {
                // Check if email is taken by another user
                if ($this->userModel->isEmailTakenByOthers($email, $userId)) {
                    throw new Exception('Email address is already in use by another account.');
                }

                // Update basic info
                $this->userModel->updateBasicInfo($userId, $name, $email);

                // Update password if a new one is provided
                if (!empty($newPassword)) {
                    if (empty($currentPassword)) {
                        throw new Exception('Current password is required to set a new password.');
                    }
                    if (!$this->userModel->verifyPassword($userId, $currentPassword)) {
                        // Monitor suspicious activity? (Optional)
                        // $this->monitorSuspiciousActivity($userId, 'profile_update_failed_password');
                        throw new Exception('Current password provided is incorrect.');
                    }

                    // Validate password strength
                    if (!$this->isPasswordStrong($newPassword)) {
                        // Provide specific feedback if possible, but avoid too much detail
                        throw new Exception('New password does not meet the security requirements. It must be at least 12 characters long and include uppercase, lowercase, number, and special characters.');
                    }

                    $this->userModel->updatePassword($userId, $newPassword);
                     $this->setFlashMessage('Password updated successfully.', 'info'); // Add separate message
                }

                $this->pdo->commit();

                // Update session data if necessary (important!)
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;

                $this->setFlashMessage('Profile updated successfully.', 'success');

                // Log the profile update
                $this->logAuditTrail('profile_update', $userId);

                // Monitor activity after successful update
                $this->monitorSuspiciousActivity($userId, 'profile_updates');

                return $this->redirect('account/profile');

            } catch (Exception $e) {
                $this->pdo->rollBack();
                // Log the specific error during the transaction
                error_log("Profile update transaction error for user {$userId}: " . $e->getMessage());
                // Rethrow to be caught by the outer catch, which sets flash message
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Profile update failed for user " . ($this->getUserId() ?? 'unknown') . ": " . $e->getMessage());
            $this->setFlashMessage($e->getMessage(), 'error'); // Show specific error message from exception
            // Redirect back to profile page so user can correct errors
            return $this->redirect('account/profile');
        }
    }

    // --- Password Reset ---

    public function requestPasswordReset() {
        // Should only be accessible via POST from the forgot password form
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Simply show the form view or redirect, don't process GET
            return $this->renderView('forgot_password', ['pageTitle' => 'Forgot Password']); // Assuming view exists
        }

        try {
            $this->validateCSRF();
            $this->validateRateLimit('password_reset_request'); // Use a specific key

            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

            // Don't reveal if email is valid or not here for security
            if ($email === false || $email === null) {
                 // Log invalid email format attempt
                 error_log("Invalid email format submitted for password reset: " . filter_input(INPUT_POST, 'email'));
                 // Still show generic message
                 $this->setFlashMessage('If an account exists with that email, we have sent password reset instructions.', 'success');
                 return $this->redirect('forgot_password'); // Redirect back to form
            }

            $this->pdo->beginTransaction();

            try {
                // Find user *before* generating token to avoid unnecessary token generation
                $user = $this->userModel->getByEmail($email);

                if ($user) {
                    // Generate a secure random token
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', time() + $this->resetTokenExpiry);

                    // Update user record with reset token
                    $updated = $this->userModel->setResetToken($user['id'], $token, $expiry);

                    if ($updated) {
                        $resetLink = $this->getResetPasswordUrl($token);

                        // Send password reset email
                        $this->emailService->sendPasswordReset($user, $token, $resetLink);

                        // Log the password reset request
                        $this->logAuditTrail('password_reset_request', $user['id']);

                        // Monitor potential abuse
                        $this->monitorSuspiciousActivity($user['id'], 'password_resets');
                    } else {
                        // Log failure to update token (DB issue?)
                        error_log("Failed to set password reset token for user {$user['id']}");
                        // Don't reveal error to user
                    }
                } else {
                    // Log attempt for non-existent email, but don't reveal to user
                    error_log("Password reset requested for non-existent email: " . $email);
                }

                // Commit transaction regardless of whether user was found or email sent
                // This prevents timing attacks revealing valid emails.
                $this->pdo->commit();

            } catch (Exception $e) {
                $this->pdo->rollBack();
                // Log internal error but don't reveal details
                error_log("Password reset request internal error: " . $e->getMessage());
                // Fall through to generic success message
            }

            // Always show the same message to prevent email enumeration
            $this->setFlashMessage('If an account exists with that email, we have sent password reset instructions.', 'success');

        } catch (Exception $e) {
            // Catch CSRF or Rate Limit errors specifically if needed
            error_log("Password reset request processing error: " . $e->getMessage());
            // Show a generic error message
            $this->setFlashMessage('An error occurred while processing your request. Please try again later.', 'error');
        }

        return $this->redirect('forgot_password'); // Redirect back to the form page
    }

    // Handles both showing the reset form (GET) and processing the reset (POST)
    public function resetPassword() {
        $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_SPECIAL_CHARS)
               ?: filter_input(INPUT_POST, 'token', FILTER_SANITIZE_SPECIAL_CHARS);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCSRF();
                $this->validateRateLimit('password_reset_attempt'); // Specific key

                // Re-validate token from POST data
                $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_SPECIAL_CHARS);
                $password = $_POST['password'] ?? ''; // Keep direct access for password
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if (empty($token)) {
                    throw new Exception('Invalid or missing password reset token.');
                }

                if (empty($password) || $password !== $confirmPassword) {
                    throw new Exception('Passwords do not match or are empty.');
                }

                if (!$this->isPasswordStrong($password)) {
                    throw new Exception('Password does not meet security requirements. It must be at least 12 characters long and include uppercase, lowercase, number, and special characters.');
                }

                $this->pdo->beginTransaction();

                try {
                    // Verify token and get user - Ensure token is checked against expiry *now*
                    $user = $this->userModel->getUserByValidResetToken($token);
                    if (!$user) {
                        // Log invalid/expired token usage
                        error_log("Invalid or expired password reset token used: " . $token);
                        throw new Exception('This password reset link is invalid or has expired. Please request a new one.');
                    }

                    // Update password and clear reset token (important!)
                    $this->userModel->resetPassword($user['id'], $password);

                    // Log the successful password reset
                    $this->logAuditTrail('password_reset_complete', $user['id']);

                    $this->pdo->commit();

                    $this->setFlashMessage('Your password has been successfully reset. Please log in with your new password.', 'success');
                    return $this->redirect('login'); // Redirect to login page

                } catch (Exception $e) {
                    $this->pdo->rollBack();
                    // Log the specific transaction error
                    error_log("Password reset transaction error for token {$token}: " . $e->getMessage());
                    throw $e; // Re-throw to be caught by outer catch
                }

            } catch (Exception $e) {
                error_log("Password reset processing error: " . $e->getMessage());
                $this->setFlashMessage($e->getMessage(), 'error');
                // Redirect back to the reset form, preserving the token in the URL
                return $this->redirect("reset_password?token=" . urlencode($token ?: ''));
            }
        } else {
            // GET request: Show the password reset form
            if (empty($token)) {
                $this->setFlashMessage('Invalid password reset link.', 'error');
                return $this->redirect('forgot_password');
            }

            // Optional: Check if token is valid *before* showing the form
            // $user = $this->userModel->getUserByValidResetToken($token);
            // if (!$user) {
            //     $this->setFlashMessage('This password reset link is invalid or has expired.', 'error');
            //     return $this->redirect('forgot_password');
            // }

            return $this->renderView('reset_password_form', [ // Assuming view exists
                'pageTitle' => 'Reset Your Password',
                'token' => $token,
                'csrfToken' => $this->generateCSRFToken() // Pass CSRF token to the form
            ]);
        }
    }


    // --- Newsletter Preferences ---

    public function updateNewsletterPreferences() {
        try {
            $this->requireLogin();
            $this->validateCSRF();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                 $this->setFlashMessage('Invalid request method.', 'warning');
                return $this->redirect('account/profile');
            }

            $userId = $this->getUserId();
            // Use filter_var for explicit boolean conversion
            $newsletterSubscribed = filter_input(INPUT_POST, 'newsletter', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            // Handle case where checkbox might not be sent if unchecked
             if ($newsletterSubscribed === null) {
                 $newsletterSubscribed = false;
             }


            $this->pdo->beginTransaction();

            try {
                $this->userModel->updateNewsletterPreference($userId, $newsletterSubscribed);

                // Log the preference update
                $action = $newsletterSubscribed ? 'newsletter_subscribe' : 'newsletter_unsubscribe';
                $this->logAuditTrail($action, $userId);

                $this->pdo->commit();

                $this->setFlashMessage('Newsletter preferences updated successfully.', 'success');

            } catch (Exception $e) {
                $this->pdo->rollBack();
                error_log("Newsletter preference update transaction error for user {$userId}: " . $e->getMessage());
                throw $e; // Re-throw
            }

        } catch (Exception $e) {
            error_log("Newsletter preference update failed for user " . ($this->getUserId() ?? 'unknown') . ": " . $e->getMessage());
            $this->setFlashMessage('Failed to update newsletter preferences. Please try again.', 'error');
        }

        return $this->redirect('account/profile'); // Always redirect back
    }


    // --- Authentication (Login / Register) ---
    // These often return JSON for AJAX forms

    public function login() {
        // Handle showing the login form on GET
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             return $this->renderView('login', [ // Changed from render to renderView for consistency
                 'pageTitle' => 'Login - The Scent',
                 'csrfToken' => $this->generateCSRFToken()
             ]);
        }

        // Handle POST request for login attempt
        try {
            $this->validateCSRFToken(); // Assumes BaseController method
            $this->validateRateLimit('login'); // Assumes BaseController method

            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'] ?? ''; // Keep direct access for password

            if ($email === false || $email === null || empty($password)) {
                // Log invalid format attempt without revealing details
                error_log("Invalid email format or empty password during login attempt.");
                throw new Exception('Invalid email or password.'); // Generic error
            }

            // Attempt login
            $user = $this->userModel->findByEmail($email); // Ensure findByEmail exists

            // Use password_verify - crucial!
            if (!$user || !password_verify($password, $user['password'])) {
                $userId = $user['id'] ?? null; // Get user ID if user exists, for logging
                $this->logFailedLogin($email, $_SERVER['REMOTE_ADDR'], $userId); // Assumes BaseController method
                $this->monitorSuspiciousActivity($userId, 'multiple_failed_logins'); // Monitor after failure
                throw new Exception('Invalid email or password.'); // Generic error
            }

            // Check if account is locked
             if (isset($user['status']) && $user['status'] === 'locked') {
                 $this->logAuditTrail('login_attempt_locked_account', $user['id']);
                 throw new Exception('Your account is locked. Please contact support.');
             }


            // Success - create session
            $this->createSecureSession($user); // Assumes BaseController method

            // Merge session cart into DB cart (Make sure CartController and method exist)
            CartController::mergeSessionCartOnLogin($this->pdo, $user['id']);

            // Audit log for success
            $this->logAuditEvent('login_success', $user['id']); // Assumes BaseController method

            // Clear any previous failed login attempts tracking for this user/IP if applicable
            // $this->clearFailedLoginAttempts($email, $_SERVER['REMOTE_ADDR']);

            return $this->jsonResponse(['success' => true, 'redirect' => $this->getBaseUrl() . '/account/dashboard']); // Use absolute URL

        } catch (Exception $e) {
            // Log specific error for debugging
            error_log("Login failed: " . $e->getMessage());
            // Return generic error in JSON response
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage() // Keep showing specific message from Exception for now
            ], 401); // Unauthorized status code
        }
    }

    public function register() {
         // Handle showing the registration form on GET
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             return $this->renderView('register', [ // Changed from render to renderView
                 'pageTitle' => 'Register - The Scent',
                 'csrfToken' => $this->generateCSRFToken()
             ]);
         }

        // Handle POST request for registration attempt
        try {
            $this->validateRateLimit('register');
            $this->validateCSRFToken();

            // Validate input using SecurityMiddleware or filter_input
            // Assuming SecurityMiddleware exists and works as intended
            $email = SecurityMiddleware::validateInput($_POST['email'] ?? '', 'email');
            $password = SecurityMiddleware::validateInput($_POST['password'] ?? '', 'password'); // Type 'password' implies strength check maybe?
            $name = SecurityMiddleware::validateInput($_POST['name'] ?? '', 'string', ['min' => 2, 'max' => 100]);

            // Explicit check after validation
            if (!$email || !$password || !$name) {
                // Log invalid input attempt
                error_log("Registration attempt with invalid input: Email=" . ($email ?: 'fail') . ", Name=" . ($name ?: 'fail'));
                // Collect specific errors if SecurityMiddleware provides them, else generic
                throw new Exception('Invalid input provided. Please check email, name, and password.');
            }

            // Check if email exists *before* hashing password
            if ($this->userModel->findByEmail($email)) { // Ensure findByEmail exists
                 throw new Exception('This email address is already registered.');
            }

            // Validate password strength explicitly here if not done by SecurityMiddleware
            if (!$this->isPasswordStrong($password)) {
                throw new Exception('Password does not meet security requirements. It must be at least 12 characters long and include uppercase, lowercase, number, and special characters.');
            }


            // Hash the password securely
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            if ($hashedPassword === false) {
                throw new Exception('Could not process password.'); // Handle hashing failure
            }

            // Create user within a transaction
            $this->pdo->beginTransaction();
            try {
                $userId = $this->userModel->create([
                    'email' => $email,
                    'password' => $hashedPassword, // Use the securely hashed password
                    'name' => $name
                    // Add other fields like 'newsletter' preference if applicable
                    // 'newsletter' => filter_input(INPUT_POST, 'newsletter', FILTER_VALIDATE_BOOLEAN) ?? false,
                ]);

                 if (!$userId) {
                     throw new Exception('Failed to create user account.');
                 }

                 // Send welcome email (consider doing this outside transaction or async)
                 $this->sendWelcomeEmail($email, $name); // Ensure this method exists

                 // Audit log
                 $this->logAuditEvent('user_registered', $userId);

                 $this->pdo->commit();

                 $this->setFlashMessage('Registration successful! Please log in.', 'success');
                 return $this->jsonResponse(['success' => true, 'redirect' => $this->getBaseUrl() . '/login']); // Use absolute URL

            } catch (Exception $e) {
                $this->pdo->rollBack();
                 error_log("User creation transaction error: " . $e->getMessage());
                // Don't leak DB errors, rethrow a generic message if needed
                throw new Exception('An error occurred during registration. Please try again.');
            }


        } catch (Exception $e) {
            error_log("Registration failed: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage() // Show specific error message for now
            ], 400); // Bad Request status code
        }
    }

    // --- Private Helper Methods ---

    /**
     * Checks if a password meets the defined strength requirements.
     */
    private function isPasswordStrong(string $password): bool {
        if (strlen($password) < 12) {
            return false;
        }
        if (!preg_match('/[A-Z]/', $password)) { // Uppercase
            return false;
        }
        if (!preg_match('/[a-z]/', $password)) { // Lowercase
            return false;
        }
        if (!preg_match('/[0-9]/', $password)) { // Number
            return false;
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) { // Special character
            return false;
        }
        // Optional: Check for repeated characters (e.g., aaa, 111)
        if (preg_match('/(.)\1{2,}/', $password)) {
             // error_log("Password check failed: repeated characters"); // DEBUG
             // return false; // Uncomment to enforce this rule
        }
        return true;
    }

    /**
     * Generates the full URL for the password reset link.
     */
    private function getResetPasswordUrl(string $token): string {
        // Ensure HTTPS is used if appropriate
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost'; // Fallback for CLI or misconfigured server
        // Assuming index.php is the entry point and handles routing via 'page' parameter
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // Get base path dynamically
        // Construct the URL carefully
        return $scheme . "://" . $host . $basePath . "/index.php?page=reset_password&token=" . urlencode($token);
        // Alternative if using cleaner URLs (e.g., /reset-password/{token})
        // return $scheme . "://" . $host . $basePath . "/reset-password/" . urlencode($token);
    }

    /**
     * Logs an action to the audit trail.
     * Assumes $this->db->insert() or similar exists via BaseController.
     * Consider adding more context (e.g., success/failure).
     */
    private function logAuditTrail(string $action, ?int $userId, array $details = []): void {
         // If userId is null (e.g., failed login for non-existent user), log without it or log IP?
         if ($userId === null) {
              error_log("Audit trail action '{$action}' attempted without userId.");
              // Decide whether to log anyway with IP or skip
              // return;
         }

        try {
            // Basic data
            $data = [
                'user_id' => $userId, // Make sure your table allows NULL if userId can be null
                'action' => $action,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN', 0, 255), // Limit length
                'timestamp' => gmdate('Y-m-d H:i:s') // Use UTC
                // Consider adding JSON column for 'details' if DB supports it
                // 'details' => !empty($details) ? json_encode($details) : null
            ];

            // Use PDO directly for better control and assuming BaseController provides $this->pdo
             $sql = "INSERT INTO audit_trail (user_id, action, ip_address, user_agent, timestamp)
                     VALUES (:user_id, :action, :ip_address, :user_agent, :timestamp)";
             $stmt = $this->pdo->prepare($sql);
             $stmt->execute($data);

            // --- Original code used $this->db->insert ---
            // If BaseController provides a db helper like this, use it:
            // $this->db->insert('audit_trail', $data);
            // Note: The original call in lockAccount passed a 3rd argument, which is ignored here.
            // If details need logging, this method or the db layer needs modification.

        } catch (Exception $e) {
            error_log("Audit trail error logging action '{$action}' for user {$userId}: " . $e->getMessage());
            // Don't let audit logging failure break the main flow
        }
    }

    /**
     * Monitors user activity for patterns defined as suspicious.
     * Notifies admin and potentially locks account.
     */
    private function monitorSuspiciousActivity(?int $userId, string $activityType): void {
         if ($userId === null) return; // Cannot monitor without a user ID

        $suspiciousPatterns = [
            // Define thresholds: action => count within period
            'multiple_failed_logins' => 3,  // e.g., 3 failed logins within 15 minutes
            'password_resets' => 2,         // e.g., 2 reset requests within 24 hours
            'profile_updates' => 5,         // e.g., 5 profile updates within 1 hour
            // Add more patterns as needed
        ];

        $activityPeriods = [
             // Define time periods for each action
            'multiple_failed_logins' => 'PT15M', // ISO 8601 duration: 15 minutes
            'password_resets' => 'P1D',         // 1 day
            'profile_updates' => 'PT1H',        // 1 hour
        ];


        if (!array_key_exists($activityType, $suspiciousPatterns)) {
            error_log("Attempted to monitor unknown activity type: {$activityType}");
            return;
        }

        $threshold = $suspiciousPatterns[$activityType];
        $period = $activityPeriods[$activityType] ?? 'P1D'; // Default period if not specified

        try {
            // This method needs to be implemented in UserModel
            $activityCount = $this->userModel->getRecentActivityCount(
                $userId,
                $activityType, // The specific action to count (e.g., 'login_fail', 'password_reset_request')
                $period        // The time window (e.g., 'PT15M', 'P1D')
            );

            if ($activityCount !== false && $activityCount >= $threshold) {
                // Log suspicious activity detected
                error_log("Suspicious activity detected: {$activityType} (Count: {$activityCount}) for user {$userId}");

                // Notify admin (Implement in EmailService)
                $this->emailService->notifyAdminOfSuspiciousActivity(
                    $userId,
                    $activityType,
                    $activityCount,
                    $period
                );

                // Optional: Take defensive action like temporary lockout
                // Be careful with lockouts - could be abused for DoS against users
                // if ($activityType === 'multiple_failed_logins') {
                //     $this->lockAccount($userId, 'suspicious_login_activity');
                // }
            }
        } catch (Exception $e) {
            // Log error but don't block the main flow
            error_log("Error monitoring suspicious activity for user {$userId}, type {$activityType}: " . $e->getMessage());
        }
    }

    /**
     * Locks a user account.
     */
    private function lockAccount(int $userId, string $reason): void {
         error_log("Attempting to lock account for user {$userId}, reason: {$reason}"); // Log intent

        $this->pdo->beginTransaction();
        try {
            // Set account status to locked (Implement in UserModel)
            $updated = $this->userModel->updateAccountStatus($userId, 'locked');

            if ($updated) {
                // Log the lockout action clearly
                $this->logAuditTrail('account_lockout', $userId, ['reason' => $reason]); // Pass reason if logAuditTrail supports details

                // Notify user (Implement in EmailService)
                $user = $this->userModel->getById($userId); // Fetch user details for notification
                if ($user) {
                    $this->emailService->sendAccountLockoutNotification($user, $reason);
                } else {
                     error_log("Could not fetch user details for lockout notification: User ID {$userId}");
                }

                $this->pdo->commit();
                 error_log("Account locked successfully for user {$userId}"); // Log success
            } else {
                 $this->pdo->rollBack(); // Rollback if status update failed
                 error_log("Failed to update account status to locked for user {$userId}");
            }

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error locking account for user {$userId}: " . $e->getMessage());
            // Potentially re-throw if this failure should stop the parent process
            // throw $e;
        }
    }

    /**
     * Sends a welcome email to a newly registered user.
     * Placeholder - Implementation depends on EmailService.
     */
    private function sendWelcomeEmail(string $email, string $name): void {
        try {
            // Assuming EmailService has a sendWelcome method
            $this->emailService->sendWelcome($email, $name);
             error_log("Welcome email sent to {$email}");
        } catch (Exception $e) {
            error_log("Failed to send welcome email to {$email}: " . $e->getMessage());
            // Don't let email failure block registration flow, just log it.
        }
    }

     // --- Methods assumed to be in BaseController (add stubs if needed for clarity) ---
     /*
     protected function requireLogin(): void { ... }
     protected function getUserId(): ?int { ... }
     protected function getCurrentUser(): ?array { ... }
     protected function validateCSRF(): void { ... }
     protected function validateCSRFToken(): void { ... } // Used in login/register/reset
     protected function generateCSRFToken(): string { ... }
     protected function setFlashMessage(string $message, string $type): void { ... }
     protected function redirect(string $location): void { ... }
     protected function renderView(string $view, array $data = []): void { ... }
     protected function jsonResponse(array $data, int $statusCode = 200): void { ... }
     protected function validateRateLimit(string $action): void { ... }
     protected function logFailedLogin(string $email, string $ip, ?int $userId = null): void { ... }
     protected function createSecureSession(array $user): void { ... }
     protected function logAuditEvent(string $event, int $userId): void { ... } // Different from logAuditTrail? Maybe more specific.
     protected function getBaseUrl(): string { ... } // Helper for redirects
     */

} // End of AccountController class

// Note: The closing ?> tag is omitted as per PSR-12 recommendation for files containing only PHP code.
```

**Key Changes and Considerations:**

1.  **Deduplication:** All methods (`login`, `register`, `resetPassword`, `updateNewsletterPreferences`, `isPasswordStrong`, `getResetPasswordUrl`, `logAuditTrail`, `monitorSuspiciousActivity`, `lockAccount`) now appear only once.
2.  **Structure:** Methods grouped logically (Account Pages, Password Reset, Newsletter, Authentication, Private Helpers).
3.  **Dependencies:** `require_once` statements are consolidated at the top. Added `CartController.php` as it's used in `login`.
4.  **Constructor:** Security headers are applied here. Added a `headers_sent()` check as a safeguard. Dependencies are type-hinted (PHP 7.4+).
5.  **Input Validation:** Switched to `filter_input` where appropriate for better security and clarity than direct `$_POST`/`$_GET` access combined with `validateInput` (unless `validateInput` *was* a wrapper for `filter_input`). Passwords still use `$_POST` as `filter_input` isn't ideal for them.
6.  **Security:**
    *   Password Hashing: Ensured `password_hash` is used in `register` and `password_verify` in `login`.
    *   Email Enumeration: `requestPasswordReset` avoids confirming if an email exists by always showing a generic success message and committing the transaction regardless.
    *   CSRF/Rate Limiting: Kept calls like `validateCSRF`, `validateCSRFToken`, `validateRateLimit`, assuming they are correctly implemented in `BaseController`.
    *   Output Encoding: Added basic `htmlspecialchars` example in `showOrderDetails` title. Ensure all user-provided data rendered in views is properly encoded.
7.  **Error Handling:** Maintained the `try...catch` structure. Added more specific `error_log` messages. Ensured transactions are rolled back on error. User-facing error messages via `setFlashMessage` are generally kept informative but avoid revealing internal details.
8.  **Transactions:** Used PDO transactions (`beginTransaction`, `commit`, `rollBack`) consistently where database updates occur.
9.  **Clarity:** Added type hints where possible (e.g., `int $orderId`, method return types like `: bool`, `: string`, `: void`). Added comments explaining logic or assumptions.
10. **Audit Logging:** Refined `logAuditTrail` to use PDO directly (assuming `$this->pdo` from `BaseController`) and added a timestamp. Noted the discrepancy regarding the third argument in the original `lockAccount` call.
11. **Suspicious Activity:** Kept the `monitorSuspiciousActivity` logic but highlighted the need for `getRecentActivityCount` in `UserModel` and `notifyAdminOfSuspiciousActivity` in `EmailService`. Made the time periods configurable per activity type.
12. **Session Update:** Explicitly updated `$_SESSION['user']` after successful profile update.
13. **Redirects:** Updated redirects in `login` and `register` to use `$this->getBaseUrl()` for potentially cleaner/absolute URLs.
14. **Base Controller Assumptions:** Added a commented section listing methods assumed to exist in `BaseController` based on their usage in this controller. This clarifies dependencies.
15. **PSR Standards:** Removed the final `?>` tag.

This refactored version should be much cleaner, maintainable, and addresses the duplication issue while preserving the intended functionality and incorporating security best practices. Remember to ensure the methods assumed in `BaseController`, `UserModel`, `EmailService`, etc., actually exist and function as expected.
