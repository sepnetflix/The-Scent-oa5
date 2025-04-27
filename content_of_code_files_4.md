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
    // Properties remain the same...
    // private EmailService $emailService;
    private User $userModel;
    private Order $orderModel;
    private Quiz $quizModel;
    private int $resetTokenExpiry = 3600;

    public function __construct(PDO $pdo) {
        parent::__construct($pdo);
        $this->userModel = new User($pdo);
        $this->orderModel = new Order($pdo);
        $this->quizModel = new Quiz($pdo);
        // $this->emailService is initialized in parent constructor
    }

    // --- Account Management Pages ---

    public function showDashboard() {
        try {
            $this->requireLogin();
            $userId = $this->getUserId();

            // Fetch data (using transaction is optional for reads like this)
            $recentOrders = $this->orderModel->getRecentByUserId($userId, 5);
            $quizResults = $this->quizModel->getResultsByUserId($userId);

            // Data for the view
            $data = [
                'pageTitle' => 'My Account - The Scent',
                'recentOrders' => $recentOrders,
                'quizResults' => $quizResults,
                'csrfToken' => $this->generateCSRFToken(),
                'bodyClass' => 'page-account-dashboard' // Added body class
            ];
            echo $this->renderView('account_dashboard', $data);
            return;

        } catch (Exception $e) {
            // Error handling remains the same...
             $userId = $this->getUserId() ?? 'unknown'; // Ensure userId for logging
            error_log("Account Dashboard error for user {$userId}: " . $e->getMessage());
            $this->setFlashMessage('Error loading dashboard. Please try again later.', 'error');
            $this->redirect('error');
        }
    }

    public function showOrders() {
        try {
            $this->requireLogin();
            $userId = $this->getUserId();

            $page = SecurityMiddleware::validateInput($_GET['p'] ?? 1, 'int', ['min' => 1]) ?: 1;
            $perPage = 10;
            $orders = $this->orderModel->getAllByUserId($userId, $page, $perPage);
            $totalOrders = $this->orderModel->getTotalOrdersByUserId($userId);
            $totalPages = ceil($totalOrders / $perPage);

            // Data for the view
            $data = [
                'pageTitle' => 'My Orders - The Scent',
                'orders' => $orders,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'csrfToken' => $this->generateCSRFToken(),
                'bodyClass' => 'page-account-orders' // Added body class
            ];
            echo $this->renderView('account_orders', $data);
            return;

        } catch (Exception $e) {
             $userId = $this->getUserId() ?? 'unknown'; // Ensure userId for logging
            error_log("Account Orders error for user {$userId}: " . $e->getMessage());
            $this->setFlashMessage('Error loading orders. Please try again later.', 'error');
            $this->redirect('error');
        }
    }

    public function showOrderDetails(int $orderId) {
        try {
            $this->requireLogin();
            $userId = $this->getUserId();

            if ($orderId <= 0) {
                 $this->setFlashMessage('Invalid order ID.', 'error');
                 $this->redirect(BASE_URL . 'index.php?page=account&action=orders');
                 return;
            }

            $order = $this->orderModel->getByIdAndUserId($orderId, $userId);
            if (!$order) {
                error_log("User {$userId} failed to access order {$orderId}");
                $this->setFlashMessage('Order not found or access denied.', 'error');
                 http_response_code(404);
                 // Data for the 404 view
                 $data = [
                     'pageTitle' => 'Order Not Found',
                     'csrfToken' => $this->generateCSRFToken(),
                     'bodyClass' => 'page-404' // Add body class for 404 page too
                 ];
                 echo $this->renderView('404', $data);
                 return;
            }

            // Data for the order details view
            $data = [
                'pageTitle' => "Order #" . htmlspecialchars(str_pad($order['id'], 6, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8') . " - The Scent",
                'order' => $order,
                'csrfToken' => $this->generateCSRFToken(),
                'bodyClass' => 'page-account-order-details' // Added body class
            ];
            echo $this->renderView('account_order_details', $data);
            return;

        } catch (Exception $e) {
            $userId = $this->getUserId() ?? 'unknown'; // Ensure userId for logging
            error_log("Order details error for user {$userId}, order {$orderId}: " . $e->getMessage());
            $this->setFlashMessage('Error loading order details. Please try again later.', 'error');
            $this->redirect(BASE_URL . 'index.php?page=account&action=orders');
        }
    }

    public function showProfile() {
        try {
            $this->requireLogin();
            $user = $this->getCurrentUser();

            if (!$user) {
                 $this->setFlashMessage('Could not load user profile data.', 'error');
                 $this->redirect('login');
                 return;
            }

            // Data for the view
            $data = [
                'pageTitle' => 'My Profile - The Scent',
                'user' => $user,
                'csrfToken' => $this->generateCSRFToken(),
                'bodyClass' => 'page-account-profile' // Added body class
            ];
            echo $this->renderView('account_profile', $data);
            return;

        } catch (Exception $e) {
            $userId = $this->getUserId() ?? 'unknown';
            error_log("Show Profile error for user {$userId}: " . $e->getMessage());
            $this->setFlashMessage('Error loading profile. Please try again later.', 'error');
            $this->redirect('error');
        }
    }

    // updateProfile() method remains unchanged (uses redirect)
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

    public function requestPasswordReset() {
        // Handle showing the form on GET
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Data for the view
            $data = [
                'pageTitle' => 'Forgot Password - The Scent',
                'csrfToken' => $this->generateCSRFToken(),
                'bodyClass' => 'page-forgot-password' // Added body class
            ];
            echo $this->renderView('forgot_password', $data);
            return;
        }

        // POST logic remains unchanged (uses redirect)
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
                $user = $this->userModel->getByEmail($email); // Correct method name used

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


    public function resetPassword() {
        $token = SecurityMiddleware::validateInput($_REQUEST['token'] ?? '', 'string', ['max' => 64]);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // POST logic remains unchanged (uses redirect)
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

            // Data for the view
            $data = [
                'pageTitle' => 'Reset Your Password - The Scent',
                'token' => $token,
                'csrfToken' => $this->generateCSRFToken(),
                'bodyClass' => 'page-reset-password' // Added body class
            ];
            echo $this->renderView('reset_password', $data);
            return;
        }
    }


    // updateNewsletterPreferences() method remains unchanged (uses redirect)
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

    public function login() {
        // Handle showing the login form on GET
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             // Data for the view
             $data = [
                 'pageTitle' => 'Login - The Scent',
                 'csrfToken' => $this->generateCSRFToken(),
                 'bodyClass' => 'page-login bg-gradient-to-br from-light to-secondary/20'
             ];
             // Use renderView to pass data correctly
             echo $this->renderView('login', $data);
             return;
        }

        // POST logic remains unchanged (uses jsonResponse)
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

            // --- FIX APPLIED HERE ---
            // Attempt login using the correct method name
            $user = $this->userModel->getByEmail($email); // Use getByEmail instead of findByEmail

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
            $this->regenerateSession(); // Call BaseController method explicitly here after successful login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'] ?? 'customer'; // Assuming a role column
            $_SESSION['user'] = [ // Store essential, non-sensitive data
                 'id' => $user['id'],
                 'name' => $user['name'],
                 'email' => $user['email'],
                 'role' => $_SESSION['user_role']
            ];
            // Set session integrity markers
             $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
             $_SESSION['ip_address'] = $ipAddress;
             $_SESSION['last_login'] = time();


            // Merge session cart into DB cart
            CartController::mergeSessionCartOnLogin($this->pdo, $user['id']);

            // Log successful login using BaseController method
            $this->logAuditTrail('login_success', $user['id']);

            // Clear any previous failed login attempts tracking for this user/IP if implemented

            // Determine redirect URL
             $redirectUrl = $_SESSION['redirect_after_login'] ?? (BASE_URL . 'index.php?page=account&action=dashboard');
             unset($_SESSION['redirect_after_login']);


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


     public function register() {
         // Handle showing the registration form on GET
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
              // Data for the view
              $data = [
                  'pageTitle' => 'Register - The Scent',
                  'csrfToken' => $this->generateCSRFToken(),
                  'bodyClass' => 'page-register' // Added body class
              ];
              echo $this->renderView('register', $data);
             return;
         }

        // POST logic remains unchanged (uses jsonResponse)
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

            // --- FIX APPLIED HERE ---
            // Check if email exists *before* hashing password using the correct method name
            if ($this->userModel->getByEmail($email)) { // Use getByEmail instead of findByEmail
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
                    'role' => 'user' // Default role, changed from customer to user
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

    // isPasswordStrong() method remains unchanged
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


    // getResetPasswordUrl() method remains unchanged
    private function getResetPasswordUrl(string $token): string {
        // Ensure HTTPS is used if appropriate (check server vars or config)
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // Use BASE_URL constant from config.php for path robustness
        $basePath = rtrim(BASE_URL, '/');
        // Construct the URL carefully using the standard routing parameter
        return $scheme . "://" . $host . $basePath . "/index.php?page=reset_password&token=" . urlencode($token);
    }


} // End of AccountController class

```

# controllers/NewsletterController.php  
```php
<?php
// controllers/NewsletterController.php (Updated)

require_once __DIR__ . '/BaseController.php';
// EmailService is included via BaseController's include

class NewsletterController extends BaseController {
    // private $emailService; // Removed - Inherited from BaseController

    // Constructor now only needs PDO, EmailService is handled by parent
    public function __construct(PDO $pdo) { // Use type hint PDO $pdo
        parent::__construct($pdo); // Calls parent constructor
    }

    public function subscribe() {
        try {
            $this->validateCSRF();
            $this->validateRateLimit('newsletter');

            // Use validateInput from BaseController which uses SecurityMiddleware
            $email = $this->validateInput($_POST['email'] ?? null, 'email');
            if ($email === false) { // validateInput returns false on failure
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Please provide a valid email address.'
                ], 400);
            }

            $this->beginTransaction();

            // Use $this->db for database operations
            $stmt = $this->db->prepare("
                SELECT id, status, unsubscribe_token
                FROM newsletter_subscribers
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $subscriber = $stmt->fetch();

            $isNewSubscriber = false;
            $subscriberId = null;
            $token = null;

            if ($subscriber) {
                $subscriberId = $subscriber['id'];
                $token = $subscriber['unsubscribe_token']; // Get existing token
                if ($subscriber['status'] === 'active') {
                    $this->rollback(); // No changes needed
                    return $this->jsonResponse([
                        'success' => true, // Return true, but indicate already subscribed
                        'message' => 'This email is already subscribed.'
                    ]);
                }

                // Reactivate unsubscribed user & ensure token exists
                $token = $token ?: $this->generateUnsubscribeToken($email); // Generate if missing
                $updateStmt = $this->db->prepare("
                    UPDATE newsletter_subscribers
                    SET status = 'active',
                        updated_at = NOW(),
                        unsubscribed_at = NULL,
                        unsubscribe_token = ? -- Update token just in case
                    WHERE id = ?
                ");
                $updateStmt->execute([$token, $subscriber['id']]);
            } else {
                // Add new subscriber
                $isNewSubscriber = true;
                $token = $this->generateUnsubscribeToken($email); // Generate new token
                $insertStmt = $this->db->prepare("
                    INSERT INTO newsletter_subscribers (
                        email,
                        status,
                        ip_address,
                        unsubscribe_token,
                        created_at,
                        updated_at
                    ) VALUES (?, 'active', ?, ?, NOW(), NOW())
                ");
                $insertStmt->execute([
                    $email,
                    $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                    $token
                ]);
                $subscriberId = $this->db->lastInsertId();
            }

            // Send Welcome/Confirmation Email (using correct method)
            $unsubscribeLink = $this->getUnsubscribeUrl($email, $token);
            $emailSubject = $isNewSubscriber ? 'Welcome to The Scent Newsletter!' : 'You are now subscribed again!';
            $emailTemplate = 'newsletter_welcome'; // Use a consistent template name
            $emailData = [
                'email' => $email,
                'unsubscribe_link' => $unsubscribeLink,
                'is_reactivation' => !$isNewSubscriber
            ];

            // Use the inherited emailService instance and its sendEmail method
            $emailSent = $this->emailService->sendEmail(
                $email,
                $emailSubject,
                $emailTemplate,
                $emailData,
                false, // Not high priority
                null, // No specific user ID associated with newsletter signup itself
                'newsletter_welcome' // Email type for logging
            );

            if (!$emailSent) {
                 // Log but don't necessarily fail the whole subscription if email fails
                 error_log("Failed to send newsletter welcome email to {$email}");
            }

            $this->commit();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Thank you for subscribing!'
            ]);

        } catch (Exception $e) {
            $this->rollback();
            error_log("Newsletter subscription error: " . $e->getMessage());
            $this->logSecurityEvent('newsletter_subscribe_error', ['error' => $e->getMessage(), 'email' => $email ?? null]);

            return $this->jsonResponse([
                'success' => false,
                'message' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }

    public function unsubscribe() {
        try {
            // Validate inputs using BaseController method
            $email = $this->validateInput($_GET['email'] ?? null, 'email');
            $token = $this->validateInput($_GET['token'] ?? null, 'string', ['max' => 64]); // Basic validation

            if ($email === false || $token === false || empty($token)) {
                throw new Exception('Invalid unsubscribe link parameters.');
            }

            $this->beginTransaction();

            // Use $this->db
            $stmt = $this->db->prepare("
                UPDATE newsletter_subscribers
                SET status = 'unsubscribed',
                    unsubscribed_at = NOW(),
                    updated_at = NOW()
                WHERE email = ?
                AND unsubscribe_token = ?
                AND status = 'active' -- Only unsubscribe active users
            ");
            $stmt->execute([$email, $token]);

            // Check if any row was actually updated
            if ($stmt->rowCount() === 0) {
                 // Could be already unsubscribed, or invalid link
                 // Check if the user exists but is already unsubscribed
                 $checkStmt = $this->db->prepare("SELECT status FROM newsletter_subscribers WHERE email = ? AND unsubscribe_token = ?");
                 $checkStmt->execute([$email, $token]);
                 $currentStatus = $checkStmt->fetchColumn();
                 if ($currentStatus === 'unsubscribed') {
                     // Already done, treat as success? Or specific message?
                     $this->commit(); // Commit as no change needed
                     return $this->jsonResponse([
                         'success' => true, // Indicate success as they are unsubscribed
                         'message' => 'You are already unsubscribed.'
                     ]);
                 } else {
                    // Invalid link / email / token combo
                     throw new Exception('Invalid or expired unsubscribe link.');
                 }
            }

             // Log successful unsubscribe using BaseController method
             $this->logAuditTrail('newsletter_unsubscribe', null, ['email' => $email]);


            $this->commit();

            // Consider showing a simple confirmation page instead of JSON for GET request
            // For now, returning JSON as per original structure
            return $this->jsonResponse([
                'success' => true,
                'message' => 'You have been successfully unsubscribed.'
            ]);

        } catch (Exception $e) {
            $this->rollback();
            error_log("Newsletter unsubscribe error: " . $e->getMessage());
            $this->logSecurityEvent('newsletter_unsubscribe_error', ['error' => $e->getMessage(), 'email' => $email ?? null]);

            // Return error JSON
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage() // Show specific error message
            ], 400);
        }
    }

    private function generateUnsubscribeToken(string $email): string {
         // Use a more secure method if possible, but HMAC is reasonable
         // Ensure NEWSLETTER_SECRET_KEY is defined and strong in config.php
         if (!defined('NEWSLETTER_SECRET_KEY')) {
             error_log("NEWSLETTER_SECRET_KEY is not defined in config.php!");
             // Fallback, but highly insecure
             return bin2hex(random_bytes(16));
         }
         return hash_hmac(
             'sha256',
             $email . microtime(), // Add microtime for more uniqueness
             NEWSLETTER_SECRET_KEY
         );
     }

     private function getUnsubscribeUrl(string $email, string $token): string {
         // Construct the unsubscribe URL using BASE_URL
         $baseUrl = rtrim(BASE_URL, '/');
         return $baseUrl . '/index.php?page=newsletter&action=unsubscribe&email=' . urlencode($email) . '&token=' . urlencode($token);
     }

    // Remove logEmail method - it's inherited from BaseController
    // Remove getWelcomeEmailContent - welcome email content generated via renderTemplate

    // getSubscriberCount uses $this->db (inherited) - OK
    public function getSubscriberCount() {
        $this->requireAdmin();
        $stmt = $this->db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'");
        return $stmt->fetchColumn();
    }

    // getRecentSubscribers uses $this->db (inherited) - OK
    public function getRecentSubscribers($limit = 10) {
        $this->requireAdmin();
        // Use prepare statement for limit
        $stmt = $this->db->prepare("
            SELECT email, created_at FROM newsletter_subscribers
            WHERE status = 'active' ORDER BY created_at DESC LIMIT ?
        ");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

} // End of NewsletterController class

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
// Potentially need Cart model if checking cart items directly
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/User.php'; // Needed for user details

class CheckoutController extends BaseController {
    // Properties remain the same...
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
        // $this->emailService is initialized in parent
    }

    public function showCheckout() {
        // Ensure user is logged in for checkout
        $this->requireLogin(); // Use BaseController method, handles redirect/exit if not logged in

        $userId = $this->getUserId();
        $cartItems = [];
        $subtotal = 0;

        // Fetch cart items from DB for logged-in user
        $cartModel = new Cart($this->pdo, $userId);
        $items = $cartModel->getItems();

        // If cart is empty, redirect
        if (empty($items)) {
             $this->setFlashMessage('Your cart is empty. Add some products before checking out.', 'info');
            $this->redirect('products'); // Redirect to products page
            return; // Exit
        }

        foreach ($items as $item) {
            // Basic stock check before showing checkout page (optional but good UX)
            if (!$this->productModel->isInStock($item['product_id'], $item['quantity'])) {
                $this->setFlashMessage("Item '{$item['name']}' is out of stock. Please update your cart.", 'error');
                $this->redirect('cart'); // Redirect back to cart to resolve
                return;
            }
            $cartItems[] = [
                'product' => $item, // Assumes getItems() joins product data
                'quantity' => $item['quantity'],
                'subtotal' => $item['price'] * $item['quantity']
            ];
            $subtotal += $item['price'] * $item['quantity'];
        }

        // Initial calculations (Tax/Total might be updated via JS later)
        $tax_rate_formatted = '0%'; // Initial placeholder
        $tax_amount = 0; // Initial placeholder
        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $total = $subtotal + $shipping_cost + $tax_amount;

        // Fetch user address details if available (assuming User model has getAddress)
        $userModel = new User($this->pdo);
        $userAddress = $userModel->getAddress($userId); // Implement this in UserModel

        // Prepare data for the view
        $csrfToken = $this->generateCSRFToken();
        $bodyClass = 'page-checkout';
        $pageTitle = 'Checkout - The Scent';

        // Use require_once, so define variables directly
        extract([
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'tax_rate_formatted' => $tax_rate_formatted,
            'tax_amount' => $tax_amount,
            'shipping_cost' => $shipping_cost,
            'total' => $total,
            'csrfToken' => $csrfToken,
            'bodyClass' => $bodyClass,
            'pageTitle' => $pageTitle,
            'userAddress' => $userAddress // Pass user address to pre-fill form
        ]);

        // Load the view file
        require_once __DIR__ . '/../views/checkout.php';
    }


    // calculateTax remains unchanged (uses jsonResponse)
    public function calculateTax() {
        // Ensure user is logged in to calculate tax based on their potential address context
        $this->requireLogin();
        // CSRF might not be strictly needed if just calculating, but good practice if form interaction triggers it
        // $this->validateCSRF(); // Consider if this is needed based on how JS calls it

        $data = json_decode(file_get_contents('php://input'), true);
        $country = $this->validateInput($data['country'] ?? '', 'string');
        $state = $this->validateInput($data['state'] ?? '', 'string');

        if (empty($country)) {
           return $this->jsonResponse(['success' => false, 'error' => 'Country is required'], 400);
        }

        $subtotal = $this->calculateCartSubtotal(); // Recalculate based on current DB cart
        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $tax_amount = $this->taxController->calculateTax($subtotal, $country, $state);
        $tax_rate = $this->taxController->getTaxRate($country, $state); // Assuming TaxController has this
        $total = $subtotal + $shipping_cost + $tax_amount;

        return $this->jsonResponse([
            'success' => true,
            'tax_rate_formatted' => $this->taxController->formatTaxRate($tax_rate), // Assuming TaxController has this
            'tax_amount' => number_format($tax_amount, 2),
            'total' => number_format($total, 2)
        ]);
    }

    // Helper to get cart subtotal for logged-in user
    private function calculateCartSubtotal() {
         // Recalculate subtotal based on current DB cart for accuracy
         $userId = $this->getUserId();
         if (!$userId) return 0; // Should not happen if requireLogin is used, but defensive check

         $cartModel = new Cart($this->pdo, $userId);
         $items = $cartModel->getItems();
         $subtotal = 0;
         foreach ($items as $item) {
             $subtotal += $item['price'] * $item['quantity'];
         }
         return $subtotal;
    }

    // processCheckout remains unchanged (uses jsonResponse/redirect)
    public function processCheckout() {
        $this->validateRateLimit('checkout_submit');
        $this->requireLogin(); // Ensure user is logged in
        $this->validateCSRF(); // Validate CSRF token from the form submission

        $userId = $this->getUserId();
        $cartModel = new Cart($this->pdo, $userId);
        $items = $cartModel->getItems();

        // Check if cart is empty *before* processing
        if (empty($items)) {
             $this->setFlashMessage('Your cart is empty.', 'error');
             $this->redirect('cart');
             return;
        }

        // Collect cart items for order creation and stock validation
        $cartItemsForOrder = [];
        $subtotal = 0;
        foreach ($items as $item) {
            $cartItemsForOrder[$item['product_id']] = $item['quantity']; // Use product_id as key
            $subtotal += $item['price'] * $item['quantity'];
        }

        // Validate required POST fields
        $requiredFields = [
            'shipping_name', 'shipping_email', 'shipping_address', 'shipping_city',
            'shipping_state', 'shipping_zip', 'shipping_country',
            // Add payment fields if necessary (e.g., payment_method_id from Stripe Elements)
             'payment_method_id' // Example for Stripe
        ];
        $missingFields = [];
        $postData = [];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $missingFields[] = ucwords(str_replace('_', ' ', $field));
            } else {
                 // Sanitize and validate input here before using it
                 // Example using a simple validation type map
                 $type = (strpos($field, 'email') !== false) ? 'email' : 'string';
                 $validatedValue = $this->validateInput($_POST[$field], $type);
                 if ($validatedValue === false) {
                     $missingFields[] = ucwords(str_replace('_', ' ', $field)) . " (Invalid)";
                 } else {
                     $postData[$field] = $validatedValue;
                 }
            }
        }

        if (!empty($missingFields)) {
            $this->setFlashMessage('Please fill in all required fields correctly: ' . implode(', ', $missingFields) . '.', 'error');
            // Consider returning JSON if the checkout form submits via AJAX
            // For standard POST, redirect back
             $this->redirect('checkout');
            return;
        }


        try {
            $this->beginTransaction();

            // Final stock check within transaction
            $stockErrors = $this->validateCartStock($cartItemsForOrder);
            if (!empty($stockErrors)) {
                // Rollback immediately if stock issue found
                $this->rollback();
                $this->setFlashMessage('Some items went out of stock while checking out: ' . implode(', ', $stockErrors) . '. Please review your cart.', 'error');
                // Consider JSON response if AJAX
                 $this->redirect('cart'); // Redirect to cart to resolve stock issues
                return;
            }

            // Calculate final totals
            $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
            $tax_amount = $this->taxController->calculateTax(
                $subtotal,
                $postData['shipping_country'], // Use validated data
                $postData['shipping_state']    // Use validated data
            );
            $total = $subtotal + $shipping_cost + $tax_amount;

            // Create Order
            $orderData = [
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'shipping_cost' => $shipping_cost,
                'tax_amount' => $tax_amount,
                'total_amount' => $total,
                'shipping_name' => $postData['shipping_name'],
                'shipping_email' => $postData['shipping_email'],
                'shipping_address' => $postData['shipping_address'],
                'shipping_city' => $postData['shipping_city'],
                'shipping_state' => $postData['shipping_state'],
                'shipping_zip' => $postData['shipping_zip'],
                'shipping_country' => $postData['shipping_country'],
                 'status' => 'pending_payment' // Initial status before payment attempt
            ];
            $orderId = $this->orderModel->create($orderData);
             if (!$orderId) throw new Exception("Failed to create order record.");


            // Create Order Items & Update Inventory
            $stmt = $this->pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($cartItemsForOrder as $productId => $quantity) {
                $product = $this->productModel->getById($productId); // Fetch price again for safety
                if ($product) {
                    $stmt->execute([$orderId, $productId, $quantity, $product['price']]);
                    // Update inventory
                    if (!$this->inventoryController->updateStock($productId, -$quantity, 'order', $orderId, "Order #{$orderId}")) {
                        throw new Exception("Failed to update inventory for product ID {$productId}");
                    }
                } else {
                    // Should not happen due to earlier checks, but handle defensively
                     throw new Exception("Product ID {$productId} not found during order item creation.");
                }
            }

            // Process Payment (Example with Stripe Payment Intent)
            $paymentResult = $this->paymentController->createPaymentIntent($orderId, $total, $postData['shipping_email']);
            if (!$paymentResult['success']) {
                 // Payment Intent creation failed *before* charging customer
                 $this->orderModel->updateStatus($orderId, 'failed'); // Mark order as failed
                throw new Exception($paymentResult['error'] ?? 'Could not initiate payment.');
            }

            // Payment Intent created successfully, need confirmation from client-side (Stripe Elements)

            $this->commit(); // Commit order creation BEFORE sending client secret

            // Log order placement attempt (status is pending_payment)
            $this->logAuditTrail('order_pending_payment', $userId, [
                'order_id' => $orderId, 'total_amount' => $total, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            // Send client secret back to the client-side JavaScript for payment confirmation
            return $this->jsonResponse([
                'success' => true,
                'orderId' => $orderId,
                'clientSecret' => $paymentResult['client_secret'] // Send client secret
                // Add publishableKey if needed by JS: 'publishableKey' => STRIPE_PUBLIC_KEY
            ]);

        } catch (Exception $e) {
            $this->rollback(); // Rollback transaction on any error
            error_log("Checkout processing error: " . $e->getMessage());
            // Return JSON error
            return $this->jsonResponse([
                'success' => false,
                'error' => 'An error occurred while processing your order. Please try again.' // Keep error generic for security
            ], 500);
        }
    }

    // showOrderConfirmation remains largely the same, just add variables
     public function showOrderConfirmation() {
         // Security: Ensure user is logged in to view their confirmation
         $this->requireLogin();
         $userId = $this->getUserId();

         // Use the order ID stored in the session after successful payment processing
         if (!isset($_SESSION['last_order_id'])) {
             // If no recent order ID, redirect to account/orders
             $this->setFlashMessage('Could not find recent order details.', 'warning');
             $this->redirect('account&action=orders'); // Redirect to orders list
             return;
         }

         $lastOrderId = $_SESSION['last_order_id'];

         // Fetch the specific order, ensuring it belongs to the current user
         $order = $this->orderModel->getByIdAndUserId($lastOrderId, $userId);

         // If order not found or doesn't belong to user, redirect
         if (!$order) {
             unset($_SESSION['last_order_id']); // Clear invalid session data
             $this->setFlashMessage('Order details not found or access denied.', 'error');
             $this->redirect('account&action=orders');
             return;
         }

         // Clear the session variable after successfully retrieving the order
         unset($_SESSION['last_order_id']);

         // Prepare data for the view
         $csrfToken = $this->generateCSRFToken(); // Still good practice for any potential forms/actions
         $bodyClass = 'page-order-confirmation';
         $pageTitle = 'Order Confirmation - The Scent';

         // Use require_once, so define variables directly
         extract([
             'order' => $order, // Contains order details and items
             'csrfToken' => $csrfToken,
             'bodyClass' => $bodyClass,
             'pageTitle' => $pageTitle
         ]);

         require_once __DIR__ . '/../views/order_confirmation.php';
     }

    // updateOrderStatus remains unchanged (uses jsonResponse, admin context)
    public function updateOrderStatus($orderId, $status, $trackingInfo = null) {
        $this->requireAdmin(); // Ensure only admin can update status
        $this->validateCSRF(); // Validate CSRF if called via POST form

        // Validate input
        $orderId = $this->validateInput($orderId, 'int');
        $status = $this->validateInput($status, 'string'); // Add more specific validation if needed (e.g., enum check)
        // Further validation for trackingInfo structure if provided

        if (!$orderId || !$status) {
            return $this->jsonResponse(['success' => false, 'error' => 'Invalid input.'], 400);
        }

        $order = $this->orderModel->getById($orderId);
        if (!$order) {
           return $this->jsonResponse(['success' => false, 'error' => 'Order not found'], 404);
        }

        try {
            $this->beginTransaction();

            $updated = $this->orderModel->updateStatus($orderId, $status);
            if (!$updated) throw new Exception("Failed to update order status.");


            // If status is 'shipped' and tracking info is provided, update tracking and notify user
            if ($status === 'shipped' && $trackingInfo && !empty($trackingInfo['number'])) {
                 $trackingUpdated = $this->orderModel->updateTracking(
                     $orderId,
                     $this->validateInput($trackingInfo['number'], 'string'),
                     $this->validateInput($trackingInfo['carrier'] ?? null, 'string'), // Optional carrier
                     $this->validateInput($trackingInfo['url'] ?? null, 'url') // Optional URL
                 );

                 if ($trackingUpdated) {
                     // Fetch user details to send email
                     $userModel = new User($this->pdo);
                     $user = $userModel->getById($order['user_id']);
                     if ($user) {
                         $this->emailService->sendShippingUpdate(
                             $order, // Pass full order details
                             $user,
                             $trackingInfo['number'],
                             $trackingInfo['carrier'] ?? ''
                         );
                     } else {
                          error_log("Could not find user {$order['user_id']} to send shipping update for order {$orderId}");
                     }
                 } else {
                     error_log("Failed to update tracking info for order {$orderId}");
                     // Decide if this should be a fatal error for the transaction
                 }
            }

            $this->commit();

            // Log status update
            $adminUserId = $this->getUserId();
            $this->logAuditTrail('order_status_update', $adminUserId, [
                 'order_id' => $orderId, 'new_status' => $status, 'tracking_provided' => !empty($trackingInfo)
            ]);

            return $this->jsonResponse(['success' => true, 'message' => 'Order status updated successfully.']);

        } catch (Exception $e) {
            $this->rollback();
            error_log("Error updating order status for {$orderId}: " . $e->getMessage());
           return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }


    // validateCartStock remains the same
     private function validateCartStock($cartItems = null) {
         $errors = [];
         // Use the provided cartItems if available (during checkout processing)
         // Otherwise, fetch the current user's cart (e.g., for display on checkout page)
         if ($cartItems === null) {
             $userId = $this->getUserId();
             if (!$userId) return ['User not logged in']; // Should be caught by requireLogin earlier

             $cartModel = new Cart($this->pdo, $userId);
             $items = $cartModel->getItems();
             $cartItems = [];
             foreach ($items as $item) {
                 $cartItems[$item['product_id']] = $item['quantity'];
             }
         }

         if (empty($cartItems)) {
             return ['Cart is empty'];
         }

         foreach ($cartItems as $productId => $quantity) {
             if (!$this->productModel->isInStock($productId, $quantity)) {
                 $product = $this->productModel->getById($productId);
                 $errors[] = ($product ? $product['name'] : "Product ID {$productId}") . " has insufficient stock";
             }
         }
         return $errors;
     }


} // End of CheckoutController class

```

# views/register.php  
```php
<?php
// views/register.php (Corrected)
$pageTitle = 'Create Account - The Scent';
// Apply consistent gradient background and page identifier class
$bodyClass = 'page-register bg-gradient-to-br from-light to-secondary/20';

require_once __DIR__ . '/layout/header.php'; // Includes CSRF token output globally
?>

<section class="auth-section flex items-center justify-center min-h-[calc(100vh-80px)] py-12 px-4">
    <div class="container max-w-md mx-auto">
        <div class="auth-container bg-white p-8 md:p-12 rounded-xl shadow-2xl" data-aos="fade-up" data-aos-delay="100">
            <div class="text-center mb-10">
                <h1 class="text-3xl lg:text-4xl font-bold font-heading text-primary mb-3">Create Account</h1>
                <p class="text-gray-600 font-body">Join The Scent community to discover your perfect fragrance.</p>
            </div>

            <?php // Standard Flash Message Display (from header or dynamic)
                // This relies on the flash message container in the header/footer layout
            ?>
            <?php if (isset($_SESSION['flash_message'])): ?>
                <script>
                    // Use the JS function immediately if available, or queue it
                    document.addEventListener('DOMContentLoaded', function() {
                        if (typeof window.showFlashMessage === 'function') {
                            window.showFlashMessage(<?= json_encode($_SESSION['flash_message']) ?>, <?= json_encode($_SESSION['flash_type'] ?? 'info') ?>);
                        }
                    });
                </script>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>

            <form action="index.php?page=register" method="POST" class="auth-form space-y-6" id="registerForm">
                 <!-- CSRF Token is handled globally by JS reading #csrf-token-value -->
                <div class="form-group">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1 font-body">Full Name</label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out font-body"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                           placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1 font-body">Email Address</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out font-body"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="Enter your email address"
                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                           title="Please enter a valid email address">
                </div>

                <div class="form-group">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1 font-body">Password</label>
                    <div class="password-input relative">
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out font-body"
                               minlength="12"
                               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&amp;_~`#^()=+[\]{}|\\:;&quot;'&lt;&gt;,.?/])[A-Za-z\d@$!%*?&amp;_~`#^()=+[\]{}|\\:;&quot;'&lt;&gt;,.?/]{12,}$"
                               title="Password must meet all requirements below"
                               placeholder="Create a strong password">
                        <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary transition duration-150 ease-in-out" aria-label="Toggle password visibility">
                            <i class="fas fa-eye text-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1 font-body">Confirm Password</label>
                    <div class="password-input relative">
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out font-body"
                               placeholder="Confirm your password">
                        <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary transition duration-150 ease-in-out" aria-label="Toggle password visibility">
                            <i class="fas fa-eye text-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Password Requirements Section - Styled -->
                <div class="password-requirements mt-4 p-4 border border-gray-200 rounded-md bg-gray-50/50" id="passwordRequirements">
                    <h4 class="text-sm font-medium text-gray-700 mb-2 font-body">Password must contain:</h4>
                    <ul class="space-y-1 text-xs text-gray-600 font-body">
                        <li id="req-length" class="requirement flex items-center not-met"> <!-- ID matches JS -->
                            <i class="fas fa-times-circle text-red-500 mr-2 w-4 text-center"></i> At least 12 characters
                        </li>
                        <li id="req-uppercase" class="requirement flex items-center not-met"> <!-- ID matches JS -->
                            <i class="fas fa-times-circle text-red-500 mr-2 w-4 text-center"></i> One uppercase letter (A-Z)
                        </li>
                        <li id="req-lowercase" class="requirement flex items-center not-met"> <!-- ID matches JS -->
                            <i class="fas fa-times-circle text-red-500 mr-2 w-4 text-center"></i> One lowercase letter (a-z)
                        </li>
                        <li id="req-number" class="requirement flex items-center not-met"> <!-- ID matches JS -->
                            <i class="fas fa-times-circle text-red-500 mr-2 w-4 text-center"></i> One number (0-9)
                        </li>
                        <li id="req-special" class="requirement flex items-center not-met"> <!-- ID matches JS -->
                            <i class="fas fa-times-circle text-red-500 mr-2 w-4 text-center"></i> One special character (e.g., !@#$)
                        </li>
                        <li id="req-match" class="requirement flex items-center not-met"> <!-- ID matches JS -->
                            <i class="fas fa-times-circle text-red-500 mr-2 w-4 text-center"></i> Passwords match
                        </li>
                    </ul>
                </div>


                <div class="form-group pt-2">
                    <label class="checkbox-label flex items-center text-sm text-gray-700 cursor-pointer font-body">
                        <input type="checkbox" name="newsletter_signup" value="1"
                               class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary mr-2"
                               checked>
                        <span>Sign up for newsletter & exclusive offers</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-full py-3 text-lg font-semibold rounded-md shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-dark transition duration-150 ease-in-out flex items-center justify-center font-body" id="submitButton" disabled>
                    <span class="button-text">Create Account</span>
                    <span class="button-loader hidden ml-2">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>

            <div class="auth-links mt-8 pt-6 border-t border-gray-200 text-center">
                <p class="text-sm text-gray-600 font-body">Already have an account?
                    <a href="index.php?page=login" class="font-medium text-primary hover:text-primary-dark transition duration-150 ease-in-out">Login here</a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

```

# views/quiz.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-quiz">

<div class="quiz-container min-h-screen bg-gradient-to-br from-primary/5 to-secondary/5 py-20">
    <!-- Particles Background -->
    <div id="particles-js" class="absolute inset-0 z-0"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-xl p-8" data-aos="fade-up">
            <h1 class="text-4xl font-heading font-semibold text-center mb-8">Find Your Perfect Scent</h1>
            <p class="text-center text-gray-600 mb-12">Let us guide you to the perfect aromatherapy products for your needs.</p>

            <form id="scent-quiz" method="POST" action="quiz" class="space-y-8">
                <div class="quiz-step" data-step="1">
                    <h3 class="text-2xl font-heading mb-6">What are you looking for today?</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="quiz-option group">
                            <input type="radio" name="mood" value="relaxation" class="hidden" required>
                            <div class="p-6 border-2 border-gray-200 rounded-xl cursor-pointer transition-all duration-300 group-hover:border-primary group-hover:bg-primary/5">
                                <i class="fas fa-spa text-3xl mb-4 text-primary"></i>
                                <h4 class="font-heading text-xl mb-2">Relaxation</h4>
                                <p class="text-sm text-gray-600">Find calm and peace in your daily routine</p>
                            </div>
                        </label>

                        <label class="quiz-option group">
                            <input type="radio" name="mood" value="energy" class="hidden">
                            <div class="p-6 border-2 border-gray-200 rounded-xl cursor-pointer transition-all duration-300 group-hover:border-primary group-hover:bg-primary/5">
                                <i class="fas fa-bolt text-3xl mb-4 text-primary"></i>
                                <h4 class="font-heading text-xl mb-2">Energy</h4>
                                <p class="text-sm text-gray-600">Boost your vitality and motivation</p>
                            </div>
                        </label>

                        <label class="quiz-option group">
                            <input type="radio" name="mood" value="focus" class="hidden">
                            <div class="p-6 border-2 border-gray-200 rounded-xl cursor-pointer transition-all duration-300 group-hover:border-primary group-hover:bg-primary/5">
                                <i class="fas fa-brain text-3xl mb-4 text-primary"></i>
                                <h4 class="font-heading text-xl mb-2">Focus</h4>
                                <p class="text-sm text-gray-600">Enhance concentration and clarity</p>
                            </div>
                        </label>

                        <label class="quiz-option group">
                            <input type="radio" name="mood" value="balance" class="hidden">
                            <div class="p-6 border-2 border-gray-200 rounded-xl cursor-pointer transition-all duration-300 group-hover:border-primary group-hover:bg-primary/5">
                                <i class="fas fa-yin-yang text-3xl mb-4 text-primary"></i>
                                <h4 class="font-heading text-xl mb-2">Balance</h4>
                                <p class="text-sm text-gray-600">Find harmony in body and mind</p>
                            </div>
                        </label>
                    </div>

                    <div class="mt-8 text-center">
                        <button type="submit" class="btn-primary inline-flex items-center space-x-2">
                            <span>Find My Perfect Scent</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

# views/quiz_results.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-quiz-results">
<div class="min-h-screen bg-gradient-to-br from-primary/5 to-secondary/5 py-20">
    <!-- Particles Background -->
    <div id="particles-js" class="absolute inset-0 z-0"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-4xl mx-auto">
            <!-- Results Header -->
            <div class="text-center mb-12" data-aos="fade-down">
                <h1 class="text-4xl font-heading font-semibold mb-4">Your Perfect Scent Match</h1>
                <p class="text-xl text-gray-600">Based on your preferences, we've curated these perfect matches for you.</p>
            </div>

            <!-- Product Recommendations -->
            <div class="grid md:grid-cols-3 gap-8 mb-12">
                <?php if (!isset($products) || !is_array($products)): $products = []; endif; ?>
                <?php foreach ($products as $product): ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden" data-aos="fade-up">
                        <div class="aspect-w-1 aspect-h-1">
                            <img 
                                src="<?= htmlspecialchars($product['image']) ?>" 
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                class="w-full h-full object-cover"
                                loading="lazy"
                            >
                        </div>
                        <div class="p-6">
                            <h3 class="font-heading text-xl mb-2"><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="text-gray-600 text-sm mb-4"><?= htmlspecialchars($product['description']) ?></p>
                            <div class="flex justify-between items-center">
                                <span class="text-primary font-semibold">$<?= number_format($product['price'], 2) ?></span>
                                <a 
                                    href="index.php?page=product&id=<?= $product['id'] ?>" 
                                    class="btn-primary text-sm"
                                >
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Action Buttons -->
            <div class="text-center space-x-4" data-aos="fade-up">
                <a href="index.php?page=quiz" class="btn-secondary">
                    Retake Quiz
                </a>
                <a href="index.php?page=products" class="btn-primary">
                    Shop All Products
                </a>
            </div>

            <!-- Newsletter Signup -->
            <div class="mt-16 bg-white rounded-xl shadow-lg p-8 text-center" data-aos="fade-up">
                <h3 class="font-heading text-2xl mb-4">Stay Updated</h3>
                <p class="text-gray-600 mb-6">Sign up for our newsletter to receive personalized aromatherapy tips and exclusive offers.</p>
                
                <form action="index.php?page=newsletter&action=subscribe" method="POST" class="flex flex-col md:flex-row gap-4 justify-center">
                    <input 
                        type="email" 
                        name="email" 
                        placeholder="Enter your email address"
                        class="flex-1 max-w-md px-4 py-2 rounded-lg border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none"
                        required
                    >
                    <button type="submit" class="btn-primary whitespace-nowrap">
                        Subscribe Now
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

# views/order_confirmation.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>

<section class="confirmation-section">
    <div class="container">
        <div class="confirmation-container" data-aos="fade-up">
            <div class="confirmation-header">
                <i class="fas fa-check-circle"></i>
                <h1>Order Confirmed!</h1>
                <p>Thank you for your purchase. Your order has been successfully placed.</p>
            </div>
            
            <div class="order-details">
                <div class="order-info">
                    <h2>Order Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="label">Order Number:</span>
                            <span class="value">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Order Date:</span>
                            <span class="value"><?= date('F j, Y', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Order Status:</span>
                            <span class="value status-pending">Processing</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Estimated Delivery:</span>
                            <span class="value"><?= date('F j, Y', strtotime('+5 days')) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="shipping-info">
                    <h2>Shipping Address</h2>
                    <p><?= htmlspecialchars($order['shipping_name']) ?></p>
                    <p><?= htmlspecialchars($order['shipping_address']) ?></p>
                    <p><?= htmlspecialchars($order['shipping_city']) . ', ' . 
                         htmlspecialchars($order['shipping_state']) . ' ' . 
                         htmlspecialchars($order['shipping_zip']) ?></p>
                    <p><?= htmlspecialchars($order['shipping_country']) ?></p>
                </div>
            </div>
            
            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="summary-items">
                    <?php foreach ($order['items'] as $item): ?>
                        <div class="summary-item">
                            <div class="item-info">
                                <span class="item-quantity"><?= $item['quantity'] ?>×</span>
                                <span class="item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                            </div>
                            <span class="item-price">$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?= number_format($order['subtotal'], 2) ?></span>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div class="summary-row discount">
                            <span>Discount (<?= htmlspecialchars($order['coupon_code']) ?>):</span>
                            <span>-$<?= number_format($order['discount_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span><?= $order['shipping_cost'] > 0 ? '$' . number_format($order['shipping_cost'], 2) : 'FREE' ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax:</span>
                        <span>$<?= number_format($order['tax_amount'], 2) ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>$<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="next-steps" data-aos="fade-up">
                <h2>What's Next?</h2>
                <div class="steps-grid">
                    <div class="step">
                        <i class="fas fa-envelope"></i>
                        <h3>Order Confirmation Email</h3>
                        <p>We've sent a confirmation email to <?= htmlspecialchars($order['shipping_email']) ?></p>
                    </div>
                    <div class="step">
                        <i class="fas fa-truck"></i>
                        <h3>Shipping Updates</h3>
                        <p>You'll receive shipping updates and tracking information once your order ships.</p>
                    </div>
                    <div class="step">
                        <i class="fas fa-user"></i>
                        <h3>Track Your Order</h3>
                        <p>Visit your account dashboard to track your order and view order history.</p>
                    </div>
                </div>
                
                <div class="confirmation-actions">
                    <a href="index.php?page=account" class="btn-secondary">View Order Status</a>
                    <a href="index.php?page=products" class="btn-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

# views/order-tracking.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>
<main class="container py-12">
    <h1 class="text-3xl font-heading mb-6">Track Your Order</h1>
    <form method="post" class="max-w-lg bg-white p-6 rounded shadow" action="#">
        <input type="hidden" name="csrf_token" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <div class="mb-4">
            <label for="order_id" class="block font-medium mb-1">Order ID</label>
            <input type="text" id="order_id" name="order_id" class="w-full border rounded px-3 py-2" required>
        </div>
        <div class="mb-4">
            <label for="email" class="block font-medium mb-1">Email</label>
            <input type="email" id="email" name="email" class="w-full border rounded px-3 py-2" required>
        </div>
        <button type="submit" class="btn-primary">Track Order</button>
    </form>
</main>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

