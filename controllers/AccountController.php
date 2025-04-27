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
