# controllers/AccountController.php  
```php
<?php

// Ensure all required files are loaded. BaseController should handle session start.
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Quiz.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/EmailService.php';
require_once __DIR__ . '/../includes/SecurityMiddleware.php'; // Needed for static methods
require_once __DIR__ . '/../controllers/CartController.php'; // Needed for mergeSessionCartOnLogin (ensure file exists)
require_once __DIR__ . '/../config.php'; // Needed for BASE_URL

class AccountController extends BaseController {
    private EmailService $emailService;
    private User $userModel;
    private Order $orderModel;
    private Quiz $quizModel;

    // Constants from SECURITY_SETTINGS in config.php can be used if needed,
    // but keeping specific controller logic separate can also be fine.
    // private int $maxLoginAttempts = SECURITY_SETTINGS['password']['max_attempts'] ?? 5;
    // private int $lockoutDuration = SECURITY_SETTINGS['password']['lockout_duration'] ?? 900;
    private int $resetTokenExpiry = 3600; // 1 hour

    // Security Headers are likely handled by SecurityMiddleware::apply() called in index.php
    // Or potentially set via BaseController. Avoid duplicating header() calls here.

    public function __construct(PDO $pdo) {
        parent::__construct($pdo); // Calls BaseController constructor
        // Initialize models and services (EmailService is already initialized in BaseController)
        $this->userModel = new User($pdo);
        $this->orderModel = new Order($pdo);
        $this->quizModel = new Quiz($pdo);
    }

    // --- Account Management Pages ---

    /**
     * Displays the user's account dashboard.
     * Assumes route like: index.php?page=account&action=dashboard (or similar)
     */
    public function showDashboard() {
        try {
            $this->requireLogin(); // From BaseController
            $userId = $this->getUserId(); // From BaseController

            // Use transaction for consistency if needed, though reads might not require it strictly.
            $this->beginTransaction(); // From BaseController

            try {
                $recentOrders = $this->orderModel->getRecentByUserId($userId, 5);
                $quizResults = $this->quizModel->getResultsByUserId($userId);

                $this->commit(); // From BaseController

                // Use renderView from BaseController - Adjusted path
                echo $this->renderView('account_dashboard', [
                    'pageTitle' => 'My Account - The Scent',
                    'recentOrders' => $recentOrders,
                    'quizResults' => $quizResults,
                    // BaseController might automatically add csrfToken, or add it here if needed
                    'csrfToken' => $this->generateCSRFToken()
                ]);
                return; // Stop execution after rendering

            } catch (Exception $e) {
                $this->rollback(); // From BaseController
                throw $e; // Re-throw to be caught by the outer catch block
            }

        } catch (Exception $e) {
            error_log("Account Dashboard error for user {$userId}: " . $e->getMessage());
            $this->setFlashMessage('Error loading dashboard. Please try again later.', 'error'); // From BaseController
            $this->redirect('error'); // From BaseController (assuming 'error' page/route exists)
        }
    }

    /**
     * Displays the user's order history with pagination.
     * Assumes route like: index.php?page=account&action=orders
     */
    public function showOrders() {
        try {
            $this->requireLogin();
            $userId = $this->getUserId();

            // Validate and sanitize pagination params using SecurityMiddleware
            $page = SecurityMiddleware::validateInput($_GET['p'] ?? 1, 'int', ['min' => 1]) ?: 1;
            $perPage = 10; // Consider making this configurable

            // Get paginated orders
            $orders = $this->orderModel->getAllByUserId($userId, $page, $perPage);
            $totalOrders = $this->orderModel->getTotalOrdersByUserId($userId);
            $totalPages = ceil($totalOrders / $perPage);

            // Render view - Adjusted path
            echo $this->renderView('account_orders', [
                'pageTitle' => 'My Orders - The Scent',
                'orders' => $orders,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'csrfToken' => $this->generateCSRFToken()
            ]);
            return;

        } catch (Exception $e) {
            error_log("Account Orders error for user {$userId}: " . $e->getMessage());
            $this->setFlashMessage('Error loading orders. Please try again later.', 'error');
            $this->redirect('error');
        }
    }

    /**
     * Displays the details of a specific order.
     * Assumes route like: index.php?page=account&action=order_details&id=123
     */
    public function showOrderDetails(int $orderId) { // Type hint parameter
        try {
            $this->requireLogin();
            $userId = $this->getUserId();

            // Basic validation (Type hint helps, also check > 0)
            if ($orderId <= 0) {
                 $this->setFlashMessage('Invalid order ID.', 'error');
                 $this->redirect(BASE_URL . 'index.php?page=account&action=orders'); // Redirect to orders list
                 return; // Stop execution
            }

            // Get order with auth check
            $order = $this->orderModel->getByIdAndUserId($orderId, $userId);
            if (!$order) {
                // Log attempt to access unauthorized/non-existent order
                error_log("User {$userId} failed to access order {$orderId}");
                $this->setFlashMessage('Order not found or access denied.', 'error');
                 // Render 404 view - using BaseController method
                 http_response_code(404);
                 echo $this->renderView('404', ['pageTitle' => 'Order Not Found']);
                 return;
            }

            // Render view - Adjusted path
            echo $this->renderView('account_order_details', [
                'pageTitle' => "Order #" . htmlspecialchars(str_pad($order['id'], 6, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8') . " - The Scent",
                'order' => $order,
                'csrfToken' => $this->generateCSRFToken()
            ]);
            return;

        } catch (Exception $e) {
            error_log("Order details error for user {$userId}, order {$orderId}: " . $e->getMessage());
            $this->setFlashMessage('Error loading order details. Please try again later.', 'error');
            $this->redirect(BASE_URL . 'index.php?page=account&action=orders');
        }
    }

    /**
     * Displays the user profile editing form.
     * Assumes route like: index.php?page=account&action=profile
     */
    public function showProfile() {
        try {
            $this->requireLogin();
            $user = $this->getCurrentUser(); // Assumes BaseController provides this securely

            if (!$user) {
                 $this->setFlashMessage('Could not load user profile data.', 'error');
                 $this->redirect('login'); // Redirect to login if user somehow lost
                 return;
            }

            // Render view - Adjusted path
            echo $this->renderView('account_profile', [
                'pageTitle' => 'My Profile - The Scent',
                'user' => $user,
                'csrfToken' => $this->generateCSRFToken()
            ]);
            return;

        } catch (Exception $e) {
            $userId = $this->getUserId() ?? 'unknown';
            error_log("Show Profile error for user {$userId}: " . $e->getMessage());
            $this->setFlashMessage('Error loading profile. Please try again later.', 'error');
            $this->redirect('error');
        }
    }

    /**
     * Handles the submission of the profile update form.
     * Assumes route like: index.php?page=account&action=update_profile (POST)
     */
    public function updateProfile() {
        $userId = null; // Initialize for error logging
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            $this->validateCSRF(); // From BaseController, checks POST token

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->setFlashMessage('Invalid request method.', 'warning');
                $this->redirect(BASE_URL . 'index.php?page=account&action=profile');
                return;
            }


            // Validate inputs using SecurityMiddleware
            $name = SecurityMiddleware::validateInput($_POST['name'] ?? '', 'string', ['min' => 1, 'max' => 100]); // Ensure name is not empty
            $email = SecurityMiddleware::validateInput($_POST['email'] ?? '', 'email');
            $currentPassword = $_POST['current_password'] ?? ''; // Keep direct access for password checking
            $newPassword = $_POST['new_password'] ?? '';         // Keep direct access

            // Validation checks
            if ($name === false || empty(trim($name))) { // SecurityMiddleware returns false on fail
                throw new Exception('Name is required and cannot be empty.');
            }
            if ($email === false) {
                 throw new Exception('A valid email address is required.');
            }

            $this->beginTransaction();

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
                        // Log security event for failed password attempt during profile update
                        $this->logSecurityEvent('profile_update_password_fail', ['user_id' => $userId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                        // Monitor suspicious activity? (Optional)
                        // $this->monitorSuspiciousActivity($userId, 'profile_update_failed_password');
                        throw new Exception('Current password provided is incorrect.');
                    }

                    // Validate new password strength
                    if (!$this->isPasswordStrong($newPassword)) {
                        throw new Exception('New password does not meet the security requirements (min 12 chars, upper, lower, number, special).');
                    }

                    $this->userModel->updatePassword($userId, $newPassword);
                    $this->setFlashMessage('Password updated successfully.', 'info'); // Add separate message
                }

                $this->commit();

                // IMPORTANT: Update session data after successful update
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;

                $this->setFlashMessage('Profile updated successfully.', 'success');

                // Log the profile update using BaseController method
                $this->logAuditTrail('profile_update', $userId, ['name' => $name, 'email' => $email]);

                // Monitor activity after successful update
                // $this->monitorSuspiciousActivity($userId, 'profile_updates'); // Uncomment if monitorSuspiciousActivity is needed/implemented

                $this->redirect(BASE_URL . 'index.php?page=account&action=profile');
                return;

            } catch (Exception $e) {
                $this->rollback();
                // Log the specific error during the transaction
                error_log("Profile update transaction error for user {$userId}: " . $e->getMessage());
                throw $e; // Rethrow to be caught by the outer catch
            }

        } catch (Exception $e) {
            $userId = $userId ?? ($this->getUserId() ?? 'unknown'); // Ensure userId is set for logging
            error_log("Profile update failed for user {$userId}: " . $e->getMessage());
            $this->setFlashMessage($e->getMessage(), 'error'); // Show specific error message from exception
            $this->redirect(BASE_URL . 'index.php?page=account&action=profile'); // Redirect back to profile page
        }
    }

    // --- Password Reset ---

    /**
     * Handles the request to reset a password (submitting email).
     * Assumes route like: index.php?page=request_password_reset (POST)
     * Also handles GET request to show the form.
     */
    public function requestPasswordReset() {
        // Handle showing the form on GET
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Render the 'forgot_password' view using BaseController method
            echo $this->renderView('forgot_password', [
                'pageTitle' => 'Forgot Password - The Scent',
                'csrfToken' => $this->generateCSRFToken()
            ]);
            return;
        }

        // Handle POST request
        try {
            $this->validateCSRF();
            $this->validateRateLimit('password_reset_request'); // Use a specific key

            $email = SecurityMiddleware::validateInput($_POST['email'] ?? null, 'email');

            // Don't reveal if email is valid or not here for security
            if ($email === false) {
                 // Log invalid email format attempt
                 $this->logSecurityEvent('password_reset_invalid_email_format', ['submitted_email' => $_POST['email'] ?? '', 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                 // Still show generic message
                 $this->setFlashMessage('If an account exists with that email, we have sent password reset instructions.', 'success');
                 $this->redirect('forgot_password'); // Redirect back to form
                 return;
            }

            $this->beginTransaction();

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

                        // Send password reset email using EmailService from BaseController
                        $this->emailService->sendPasswordReset($user, $token, $resetLink);

                        // Log the password reset request using BaseController method
                        $this->logAuditTrail('password_reset_request', $user['id']);

                        // Monitor potential abuse (optional)
                        // $this->monitorSuspiciousActivity($user['id'], 'password_resets');
                    } else {
                        // Log failure to update token (DB issue?)
                        error_log("Failed to set password reset token for user {$user['id']}");
                        // Don't reveal error to user
                    }
                } else {
                    // Log attempt for non-existent email, but don't reveal to user
                    $this->logSecurityEvent('password_reset_nonexistent_email', ['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                }

                // Commit transaction regardless of whether user was found or email sent
                // This prevents timing attacks revealing valid emails.
                $this->commit();

            } catch (Exception $e) {
                $this->rollback();
                // Log internal error but don't reveal details
                error_log("Password reset request internal error: " . $e->getMessage());
                // Fall through to generic success message
            }

            // Always show the same message to prevent email enumeration
            $this->setFlashMessage('If an account exists with that email, we have sent password reset instructions.', 'success');

        } catch (Exception $e) {
            // Catch CSRF or Rate Limit errors specifically if needed
            error_log("Password reset request processing error: " . $e->getMessage());
            $this->logSecurityEvent('password_reset_request_error', ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            $this->setFlashMessage('An error occurred while processing your request. Please try again later.', 'error');
        }

        $this->redirect('forgot_password'); // Redirect back to the form page
    }

    /**
     * Handles both showing the reset form (GET) and processing the reset (POST).
     * Assumes route like: index.php?page=reset_password
     * GET requires a 'token' parameter.
     * POST requires 'token', 'password', 'confirm_password'.
     */
    public function resetPassword() {
        // Get token from GET or POST, sanitize it
        $token = SecurityMiddleware::validateInput($_REQUEST['token'] ?? '', 'string', ['max' => 64]); // Validate as string, check length

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCSRF();
                $this->validateRateLimit('password_reset_attempt'); // Specific key

                // Re-validate token from POST data specifically
                $token = SecurityMiddleware::validateInput($_POST['token'] ?? '', 'string', ['max' => 64]);
                $password = $_POST['password'] ?? ''; // Keep direct access for password checking
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if ($token === false || empty($token)) { // Check validation result
                    throw new Exception('Invalid or missing password reset token.');
                }

                if (empty($password)) {
                    throw new Exception('Password cannot be empty.');
                }

                if ($password !== $confirmPassword) {
                    throw new Exception('Passwords do not match.');
                }

                if (!$this->isPasswordStrong($password)) {
                    throw new Exception('Password does not meet security requirements (min 12 chars, upper, lower, number, special).');
                }

                $this->beginTransaction();

                try {
                    // Verify token and get user - Ensure token is checked against expiry *now*
                    $user = $this->userModel->getUserByValidResetToken($token);
                    if (!$user) {
                        // Log invalid/expired token usage
                        $this->logSecurityEvent('password_reset_invalid_token', ['token' => $token, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                        throw new Exception('This password reset link is invalid or has expired. Please request a new one.');
                    }

                    // Update password and clear reset token (important!)
                    $this->userModel->resetPassword($user['id'], $password);

                    // Log the successful password reset using BaseController method
                    $this->logAuditTrail('password_reset_complete', $user['id']);

                    $this->commit();

                    $this->setFlashMessage('Your password has been successfully reset. Please log in with your new password.', 'success');
                    $this->redirect('login'); // Redirect to login page
                    return;

                } catch (Exception $e) {
                    $this->rollback();
                    // Log the specific transaction error
                    error_log("Password reset transaction error for token {$token}: " . $e->getMessage());
                    throw $e; // Re-throw to be caught by outer catch
                }

            } catch (Exception $e) {
                error_log("Password reset processing error: " . $e->getMessage());
                $this->logSecurityEvent('password_reset_error', ['error' => $e->getMessage(), 'token' => $token, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                $this->setFlashMessage($e->getMessage(), 'error');
                // Redirect back to the reset form, preserving the token in the URL
                $redirectUrl = BASE_URL . 'index.php?page=reset_password&token=' . urlencode($token ?: '');
                $this->redirect($redirectUrl);
                return;
            }
        } else {
            // --- GET request: Show the password reset form ---
            if ($token === false || empty($token)) {
                $this->setFlashMessage('Invalid password reset link.', 'error');
                $this->redirect('forgot_password');
                return;
            }

            // Optional: Check if token is valid *before* showing the form
            // $user = $this->userModel->getUserByValidResetToken($token);
            // if (!$user) {
            //     $this->logSecurityEvent('password_reset_invalid_token_get', ['token' => $token, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            //     $this->setFlashMessage('This password reset link is invalid or has expired.', 'error');
            //     $this->redirect('forgot_password');
            //     return;
            // }

            // Render the reset password form view using BaseController method
            echo $this->renderView('reset_password', [ // View name matches file
                'pageTitle' => 'Reset Your Password - The Scent',
                'token' => $token, // Pass token to the view's form
                'csrfToken' => $this->generateCSRFToken()
            ]);
            return;
        }
    }


    // --- Newsletter Preferences ---

    /**
     * Updates the user's newsletter subscription preference.
     * Assumes route like: index.php?page=account&action=update_newsletter (POST)
     */
    public function updateNewsletterPreferences() {
        $userId = null; // Initialize for logging
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            $this->validateCSRF();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                 $this->setFlashMessage('Invalid request method.', 'warning');
                 $this->redirect(BASE_URL . 'index.php?page=account&action=profile');
                 return;
            }

            // Use filter_var for explicit boolean conversion from POST data
            $newsletterSubscribed = filter_input(INPUT_POST, 'newsletter', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            // Handle case where checkbox isn't sent if unchecked (value becomes false)
            $newsletterSubscribed = ($newsletterSubscribed === true);

            $this->beginTransaction();

            try {
                $this->userModel->updateNewsletterPreference($userId, $newsletterSubscribed);

                // Log the preference update using BaseController method
                $action = $newsletterSubscribed ? 'newsletter_subscribe_profile' : 'newsletter_unsubscribe_profile';
                $this->logAuditTrail($action, $userId);

                $this->commit();

                $this->setFlashMessage('Newsletter preferences updated successfully.', 'success');

            } catch (Exception $e) {
                $this->rollback();
                error_log("Newsletter preference update transaction error for user {$userId}: " . $e->getMessage());
                throw $e; // Re-throw
            }

        } catch (Exception $e) {
            $userId = $userId ?? ($this->getUserId() ?? 'unknown');
            error_log("Newsletter preference update failed for user {$userId}: " . $e->getMessage());
            $this->logSecurityEvent('newsletter_update_fail', ['user_id' => $userId, 'error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            $this->setFlashMessage('Failed to update newsletter preferences. Please try again.', 'error');
        }

        $this->redirect(BASE_URL . 'index.php?page=account&action=profile'); // Always redirect back
    }


    // --- Authentication (Login / Register) ---
    // These primarily return JSON for AJAX forms but also handle GET requests to show the form.

    /**
     * Handles user login (GET shows form, POST processes).
     * Assumes route: index.php?page=login
     */
    public function login() {
        // Handle showing the login form on GET
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             echo $this->renderView('login', [ // Use BaseController render
                 'pageTitle' => 'Login - The Scent',
                 'csrfToken' => $this->generateCSRFToken()
             ]);
             return;
        }

        // Handle POST request for login attempt
        $emailSubmitted = $_POST['email'] ?? ''; // Get email for logging even if invalid
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        try {
            $this->validateCSRFToken(); // From BaseController
            $this->validateRateLimit('login'); // From BaseController

            // Validate input using SecurityMiddleware
            $email = SecurityMiddleware::validateInput($emailSubmitted, 'email');
            $password = $_POST['password'] ?? ''; // Keep direct access

            if ($email === false || empty($password)) {
                $this->logSecurityEvent('login_invalid_input', ['email' => $emailSubmitted, 'ip' => $ipAddress]);
                throw new Exception('Invalid email or password format.'); // More specific error
            }

            // Attempt login
            $user = $this->userModel->findByEmail($email);

            // Use password_verify - crucial!
            if (!$user || !password_verify($password, $user['password'])) {
                $userId = $user['id'] ?? null; // Get user ID if user exists, for logging
                // Log failed login attempt using BaseController method
                $this->logSecurityEvent('login_failure', ['email' => $email, 'ip' => $ipAddress, 'user_id' => $userId]);
                // Monitor suspicious activity? (Optional) Needs implementation in UserModel.
                // $this->monitorSuspiciousActivity($userId, 'multiple_failed_logins');
                throw new Exception('Invalid email or password.'); // Generic error for security
            }

            // Check if account is locked (assuming 'status' column exists)
            if (isset($user['status']) && $user['status'] === 'locked') {
                 $this->logSecurityEvent('login_attempt_locked', ['user_id' => $user['id'], 'email' => $email, 'ip' => $ipAddress]);
                 throw new Exception('Your account is currently locked. Please contact support.');
            }

            // --- Success ---
            // Regenerate session ID and set user data in session
            // This should ideally be handled within a dedicated auth service/helper
            // For now, mimic expected session setup. BaseController regenerateSession handles the ID.
            $this->regenerateSession(); // Call BaseController method explicitly here after successful login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'] ?? 'customer'; // Assuming a role column
            $_SESSION['user'] = [ // Store essential, non-sensitive data
                 'id' => $user['id'],
                 'name' => $user['name'],
                 'email' => $user['email'],
                 'role' => $_SESSION['user_role']
            ];
            // Set session integrity markers (done by BaseController::regenerateSession or here)
             $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
             $_SESSION['ip_address'] = $ipAddress;
             $_SESSION['last_login'] = time();


            // Merge session cart into DB cart (Ensure CartController and method exist)
            CartController::mergeSessionCartOnLogin($this->pdo, $user['id']);

            // Log successful login using BaseController method
            $this->logAuditTrail('login_success', $user['id']);

            // Clear any previous failed login attempts tracking for this user/IP if implemented

            // Determine redirect URL (e.g., intended page or dashboard)
             $redirectUrl = $_SESSION['redirect_after_login'] ?? (BASE_URL . 'index.php?page=account&action=dashboard'); // Use constant/helper
             unset($_SESSION['redirect_after_login']); // Clear redirect destination


            // Use jsonResponse from BaseController
            return $this->jsonResponse(['success' => true, 'redirect' => $redirectUrl]);

        } catch (Exception $e) {
            // Log specific error for debugging
            error_log("Login failed for email '{$emailSubmitted}' from IP {$ipAddress}: " . $e->getMessage());
            // Return generic error in JSON response using BaseController method
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage() // Show specific validation/auth message
            ], 401); // Unauthorized status code
        }
    }

     /**
     * Handles user registration (GET shows form, POST processes).
     * Assumes route: index.php?page=register
     */
    public function register() {
         // Handle showing the registration form on GET
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             echo $this->renderView('register', [ // Use BaseController render
                 'pageTitle' => 'Register - The Scent',
                 'csrfToken' => $this->generateCSRFToken()
             ]);
             return;
         }

        // Handle POST request for registration attempt
        $emailSubmitted = $_POST['email'] ?? ''; // Get for logging
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        try {
            $this->validateRateLimit('register'); // From BaseController
            $this->validateCSRFToken(); // From BaseController

            // Validate input using SecurityMiddleware
            $email = SecurityMiddleware::validateInput($emailSubmitted, 'email');
            $password = $_POST['password'] ?? ''; // Keep direct access for strength check
            $name = SecurityMiddleware::validateInput($_POST['name'] ?? '', 'string', ['min' => 2, 'max' => 100]);

            // Explicit check after validation
            if ($email === false || empty($password) || $name === false) {
                // Log invalid input attempt
                 $this->logSecurityEvent('register_invalid_input', ['email' => $emailSubmitted, 'name_valid' => ($name !== false), 'ip' => $ipAddress]);
                 throw new Exception('Invalid input provided. Please check email, name, and password.');
            }

            // Check if email exists *before* hashing password
            if ($this->userModel->findByEmail($email)) {
                 throw new Exception('This email address is already registered.');
            }

            // Validate password strength explicitly here
            if (!$this->isPasswordStrong($password)) {
                throw new Exception('Password does not meet security requirements (min 12 chars, upper, lower, number, special).');
            }

            // Hash the password securely
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            if ($hashedPassword === false) {
                 error_log("Password hashing failed for registration: " . print_r(error_get_last(), true));
                throw new Exception('Could not process password securely.'); // Handle hashing failure
            }

            // Create user within a transaction
            $this->beginTransaction();
            try {
                $userId = $this->userModel->create([
                    'email' => $email,
                    'password' => $hashedPassword, // Use the securely hashed password
                    'name' => $name,
                    'role' => 'customer' // Default role
                    // Add other fields like 'newsletter' preference if applicable from form
                    // 'newsletter' => filter_input(INPUT_POST, 'newsletter', FILTER_VALIDATE_BOOLEAN) ?? false,
                ]);

                 if (!$userId) {
                     throw new Exception('Failed to create user account in database.');
                 }

                 // Send welcome email (consider doing this outside transaction or async)
                 $this->emailService->sendWelcome($email, $name); // Use EmailService from BaseController

                 // Log successful registration using BaseController method
                 $this->logAuditTrail('user_registered', $userId);

                 $this->commit();

                 $this->setFlashMessage('Registration successful! Please log in.', 'success');
                 return $this->jsonResponse(['success' => true, 'redirect' => BASE_URL . 'index.php?page=login']); // Use constant/helper

            } catch (Exception $e) {
                 $this->rollback();
                 error_log("User creation transaction error: " . $e->getMessage());
                 // Don't leak DB errors, rethrow a generic message if needed
                 throw new Exception('An error occurred during registration. Please try again.');
            }


        } catch (Exception $e) {
            error_log("Registration failed for email '{$emailSubmitted}' from IP {$ipAddress}: " . $e->getMessage());
            $this->logSecurityEvent('register_failure', ['email' => $emailSubmitted, 'error' => $e->getMessage(), 'ip' => $ipAddress]);
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage() // Show specific error message
            ], 400); // Bad Request status code
        }
    }

    // --- Private Helper Methods ---

    /**
     * Checks if a password meets the defined strength requirements.
     * Aligned with potential checks in SecurityMiddleware/config.php
     */
    private function isPasswordStrong(string $password): bool {
        $minLength = SECURITY_SETTINGS['password']['min_length'] ?? 12;
        $reqSpecial = SECURITY_SETTINGS['password']['require_special'] ?? true;
        $reqNumber = SECURITY_SETTINGS['password']['require_number'] ?? true;
        $reqMixedCase = SECURITY_SETTINGS['password']['require_mixed_case'] ?? true;

        if (strlen($password) < $minLength) return false;
        if ($reqMixedCase && (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password))) return false;
        if ($reqNumber && !preg_match('/[0-9]/', $password)) return false;
        // Adjusted regex slightly for common special chars, ensure it matches config intent
        if ($reqSpecial && !preg_match('/[^A-Za-z0-9]/', $password)) return false;
        // Optional: Check for repeated characters (e.g., aaa, 111)
        // if (preg_match('/(.)\1{2,}/', $password)) { return false; }
        return true;
    }

    /**
     * Generates the full URL for the password reset link.
     */
    private function getResetPasswordUrl(string $token): string {
        // Ensure HTTPS is used if appropriate (check server vars or config)
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // Use BASE_URL constant from config.php for path robustness
        $basePath = rtrim(BASE_URL, '/');
        // Construct the URL carefully using the standard routing parameter
        return $scheme . "://" . $host . $basePath . "/index.php?page=reset_password&token=" . urlencode($token);
    }

    // logAuditTrail is inherited from BaseController
    // monitorSuspiciousActivity - Requires implementation in UserModel (getRecentActivityCount) and EmailService (notifyAdminOfSuspiciousActivity)
    // lockAccount - Requires implementation in UserModel (updateAccountStatus) and EmailService (sendAccountLockoutNotification)

    // Other helpers like regenerateSession, logSecurityEvent are in BaseController.

} // End of AccountController class

```

# controllers/NewsletterController.php  
```php
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
```

# controllers/CheckoutController.php  
```php
<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/InventoryController.php';
require_once __DIR__ . '/../controllers/TaxController.php';
require_once __DIR__ . '/../includes/EmailService.php';

class CheckoutController extends BaseController {
    private $productModel;
    private $orderModel;
    private $inventoryController;
    private $taxController;
    private $paymentController;
    private $emailService;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->orderModel = new Order($pdo);
        $this->inventoryController = new InventoryController($pdo);
        $this->taxController = new TaxController($pdo);
        $this->paymentController = new PaymentController();
        $this->emailService = new EmailService();
    }
    
    public function showCheckout() {
        $isLoggedIn = isset($_SESSION['user_id']);
        $cartItems = [];
        $subtotal = 0;
        if ($isLoggedIn) {
            require_once __DIR__ . '/../models/Cart.php';
            $cartModel = new \Cart($this->pdo, $_SESSION['user_id']);
            $items = $cartModel->getItems();
            foreach ($items as $item) {
                $cartItems[] = [
                    'product' => $item,
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['price'] * $item['quantity']
                ];
                $subtotal += $item['price'] * $item['quantity'];
            }
        } else {
            if (empty($_SESSION['cart'])) {
                $this->redirect('cart');
            }
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $product = $this->productModel->getById($productId);
                if ($product) {
                    $cartItems[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'subtotal' => $product['price'] * $quantity
                    ];
                    $subtotal += $product['price'] * $quantity;
                }
            }
        }
        $tax_rate_formatted = '0%';
        $tax_amount = 0;
        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $total = $subtotal + $shipping_cost + $tax_amount;
        require_once __DIR__ . '/../views/checkout.php';
    }
    
    public function calculateTax() {
        $this->validateCSRF();
        $data = json_decode(file_get_contents('php://input'), true);
        $country = $this->validateInput($data['country'] ?? '', 'string');
        $state = $this->validateInput($data['state'] ?? '', 'string');
        if (empty($country)) {
            $this->jsonResponse(['success' => false, 'error' => 'Country is required'], 400);
        }
        $subtotal = $this->calculateCartSubtotal();
        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $tax_amount = $this->taxController->calculateTax($subtotal, $country, $state);
        $tax_rate = $this->taxController->getTaxRate($country, $state);
        $total = $subtotal + $shipping_cost + $tax_amount;
        $this->jsonResponse([
            'success' => true,
            'tax_rate_formatted' => $this->taxController->formatTaxRate($tax_rate),
            'tax_amount' => number_format($tax_amount, 2),
            'total' => number_format($total, 2)
        ]);
    }
    
    private function calculateCartSubtotal() {
        $isLoggedIn = isset($_SESSION['user_id']);
        $subtotal = 0;
        if ($isLoggedIn) {
            require_once __DIR__ . '/../models/Cart.php';
            $cartModel = new \Cart($this->pdo, $_SESSION['user_id']);
            $items = $cartModel->getItems();
            foreach ($items as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
        } else {
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $product = $this->productModel->getById($productId);
                if ($product) {
                    $subtotal += $product['price'] * $quantity;
                }
            }
        }
        return $subtotal;
    }
    
    public function processCheckout() {
        $this->validateRateLimit('checkout_submit');
        $this->requireLogin();
        $this->validateCSRF();
        $isLoggedIn = isset($_SESSION['user_id']);
        $cartItems = [];
        $subtotal = 0;
        if ($isLoggedIn) {
            require_once __DIR__ . '/../models/Cart.php';
            $cartModel = new \Cart($this->pdo, $_SESSION['user_id']);
            $items = $cartModel->getItems();
            foreach ($items as $item) {
                $cartItems[$item['id']] = $item['quantity'];
                $subtotal += $item['price'] * $item['quantity'];
            }
        } else {
            if (empty($_SESSION['cart'])) {
                $this->redirect('cart');
            }
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $cartItems[$productId] = $quantity;
                $product = $this->productModel->getById($productId);
                if ($product) {
                    $subtotal += $product['price'] * $quantity;
                }
            }
        }
        $required = ['shipping_name', 'shipping_email', 'shipping_address', 'shipping_city', 
                    'shipping_state', 'shipping_zip', 'shipping_country'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $this->setFlashMessage('Please fill in all required fields.', 'error');
                $this->redirect('checkout');
            }
        }
        try {
            $this->beginTransaction();
            $stockErrors = $this->validateCartStock($cartItems);
            if (!empty($stockErrors)) {
                throw new Exception('Some items are out of stock: ' . implode(', ', $stockErrors));
            }
            $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
            $tax_amount = $this->taxController->calculateTax(
                $subtotal,
                $this->validateInput($_POST['shipping_country'], 'string'),
                $this->validateInput($_POST['shipping_state'], 'string')
            );
            $total = $subtotal + $shipping_cost + $tax_amount;
            $userId = $this->getUserId();
            $orderData = [
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'shipping_cost' => $shipping_cost,
                'tax_amount' => $tax_amount,
                'total_amount' => $total,
                'shipping_name' => $this->validateInput($_POST['shipping_name'], 'string'),
                'shipping_email' => $this->validateInput($_POST['shipping_email'], 'email'),
                'shipping_address' => $this->validateInput($_POST['shipping_address'], 'string'),
                'shipping_city' => $this->validateInput($_POST['shipping_city'], 'string'),
                'shipping_state' => $this->validateInput($_POST['shipping_state'], 'string'),
                'shipping_zip' => $this->validateInput($_POST['shipping_zip'], 'string'),
                'shipping_country' => $this->validateInput($_POST['shipping_country'], 'string')
            ];
            $orderId = $this->orderModel->create($orderData);
            $stmt = $this->pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($cartItems as $productId => $quantity) {
                $product = $this->productModel->getById($productId);
                if ($product) {
                    $stmt->execute([
                        $orderId,
                        $productId,
                        $quantity,
                        $product['price']
                    ]);
                    if (!$this->inventoryController->updateStock(
                        $productId,
                        -$quantity,
                        'order',
                        $orderId,
                        "Order #{$orderId}"
                    )) {
                        throw new Exception("Failed to update inventory for product {$product['name']}");
                    }
                }
            }
            $paymentResult = $this->paymentController->processPayment($orderId);
            if (!$paymentResult['success']) {
                throw new Exception($paymentResult['error']);
            }
            $this->commit();
            $this->logAuditTrail('order_placed', $userId, [
                'order_id' => $orderId,
                'total_amount' => $total,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
            $user = $this->getCurrentUser();
            $order = $this->orderModel->getById($orderId);
            $this->emailService->sendOrderConfirmation($order, $user);
            $_SESSION['last_order_id'] = $orderId;
            if ($isLoggedIn) {
                $cartModel->clearCart();
            } else {
                $_SESSION['cart'] = [];
            }
            $this->jsonResponse([
                'success' => true,
                'orderId' => $orderId,
                'clientSecret' => $paymentResult['clientSecret']
            ]);
        } catch (Exception $e) {
            $this->rollback();
            error_log($e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'error' => 'An error occurred while processing your order. Please try again.'
            ], 500);
        }
    }
    
    private function validateCartStock($cartItems = null) {
        $errors = [];
        if ($cartItems === null) {
            $isLoggedIn = isset($_SESSION['user_id']);
            if ($isLoggedIn) {
                require_once __DIR__ . '/../models/Cart.php';
                $cartModel = new \Cart($this->pdo, $_SESSION['user_id']);
                $items = $cartModel->getItems();
                foreach ($items as $item) {
                    if (!$this->productModel->isInStock($item['product_id'], $item['quantity'])) {
                        $errors[] = "{$item['name']} has insufficient stock";
                    }
                }
            } else {
                foreach ($_SESSION['cart'] as $productId => $quantity) {
                    if (!$this->productModel->isInStock($productId, $quantity)) {
                        $product = $this->productModel->getById($productId);
                        $errors[] = "{$product['name']} has insufficient stock";
                    }
                }
            }
        } else {
            foreach ($cartItems as $productId => $quantity) {
                if (!$this->productModel->isInStock($productId, $quantity)) {
                    $product = $this->productModel->getById($productId);
                    $errors[] = "{$product['name']} has insufficient stock";
                }
            }
        }
        return $errors;
    }
    
    public function showOrderConfirmation() {
        $this->requireLogin();
        
        if (!isset($_SESSION['last_order_id'])) {
            $this->redirect('products');
        }
        
        $stmt = $this->pdo->prepare("
            SELECT o.*, oi.product_id, oi.quantity, oi.price, p.name as product_name
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE o.id = ? AND o.user_id = ?
        ");
        
        $stmt->execute([$_SESSION['last_order_id'], $this->getUserId()]);
        $orderItems = $stmt->fetchAll();
        
        if (empty($orderItems)) {
            $this->redirect('products');
        }
        
        $order = [
            'id' => $orderItems[0]['id'],
            'total_amount' => $orderItems[0]['total_amount'],
            'shipping_name' => $orderItems[0]['shipping_name'],
            'shipping_email' => $orderItems[0]['shipping_email'],
            'shipping_address' => $orderItems[0]['shipping_address'],
            'shipping_city' => $orderItems[0]['shipping_city'],
            'shipping_state' => $orderItems[0]['shipping_state'],
            'shipping_zip' => $orderItems[0]['shipping_zip'],
            'shipping_country' => $orderItems[0]['shipping_country'],
            'created_at' => $orderItems[0]['created_at'],
            'items' => $orderItems
        ];
        
        unset($_SESSION['last_order_id']);
        
        require_once __DIR__ . '/../views/order_confirmation.php';
    }
    
    public function updateOrderStatus($orderId, $status, $trackingInfo = null) {
        $this->requireAdmin();
        $this->validateCSRF();
        
        $order = $this->orderModel->getById($orderId);
        
        if (!$order) {
            $this->jsonResponse(['success' => false, 'error' => 'Order not found'], 404);
        }
        
        try {
            $this->beginTransaction();
            
            $this->orderModel->updateStatus($orderId, $status);
            
            if ($status === 'shipped' && $trackingInfo) {
                $this->orderModel->updateTracking(
                    $orderId,
                    $trackingInfo['number'],
                    $trackingInfo['carrier'],
                    $trackingInfo['url']
                );
                
                $user = (new User($this->pdo))->getById($order['user_id']);
                $this->emailService->sendShippingUpdate(
                    $order,
                    $user,
                    $trackingInfo['number'],
                    $trackingInfo['carrier']
                );
            }
            
            $this->commit();
            $this->jsonResponse(['success' => true]);
            
        } catch (Exception $e) {
            $this->rollback();
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
```

