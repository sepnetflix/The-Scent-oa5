<think>
**Plan:**

1.  **Identify the Target File:** `controllers/AccountController.php` (original from `content_of_code_files_4.md`).
2.  **Identify the Required Fix:** Change the calls `$this->generateCSRFToken()` to `$this->getCsrfToken()` within the `login()` and `register()` methods for the GET request handlers.
3.  **Review Previous Refinements:** Recall other improvements made to `BaseController` and ensure `AccountController` utilizes them appropriately (e.g., `requireLogin`, `validateRateLimit`, `validateInput`, `redirect`, `renderView`, logging methods).
4.  **Apply the Fix:** Modify the specific lines identified in the previous step.
5.  **Apply Refinements:**
    *   Ensure `requireLogin()` is called at the start of protected methods.
    *   Replace `SecurityMiddleware::validateInput` with `$this->validateInput`.
    *   Replace `SecurityMiddleware::validateCSRF` / `validateCSRFToken` with `$this->validateCSRF`.
    *   Replace direct `header()` redirects with `$this->redirect()`.
    *   Replace direct `require_once __DIR__ . '/../views/...'` with `$this->renderView()`.
    *   Use `$this->logAuditTrail`, `$this->logSecurityEvent` for logging.
    *   Use `$this->validateRateLimit`.
    *   Ensure `$this->emailService` is used correctly (inherited).
    *   Ensure `$currentUser` or `$user` data is passed to views that need it (e.g., for layout).
    *   Improve password strength error message generation.
    *   Update session data setting after login/profile update to use correct structure/markers from BaseController.
    *   Refine exception handling messages.
    *   Update password reset expiry to use config.
    *   Refine newsletter preference update logic.
6.  **Compare:** Use `diff -u` mentally or literally against the original version (`controllers/AccountController.php-old`) to ensure only intended changes are made and no original logic is lost without replacement.
7.  **Generate Output:** Create the complete updated file content.
8.  **Final Review:** Re-read the original and the new version side-by-side.

**Pre-computation/Pre-analysis:**

*   The core fix is changing `$this->generateCSRFToken()` to `$this->getCsrfToken()` in `login()` and `register()`.
*   Need to update calls to `validateCSRF`, `requireLogin`, `validateRateLimit`, `validateInput`.
*   Need to update `renderView` calls.
*   Need to update `redirect` calls.
*   Need to update session setting logic in `login` and `updateProfile`.
*   Need to refine `isPasswordStrong` error message.
*   Need to refine newsletter update logic.
*   Need to ensure `logAuditTrail` and `logSecurityEvent` are used appropriately.

**Execution based on plan:** Apply the CSRF call fix and the systematic refinements using BaseController methods.
</think>

```php
<?php

// Ensure all required files are loaded. BaseController should handle session start.
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Quiz.php';
require_once __DIR__ . '/../models/User.php';
// EmailService is included via BaseController
// SecurityMiddleware is included via BaseController (for static methods)
require_once __DIR__ . '/../controllers/CartController.php'; // Needed for mergeSessionCartOnLogin (ensure file exists)
require_once __DIR__ . '/../config.php'; // Needed for BASE_URL, SECURITY_SETTINGS

class AccountController extends BaseController {
    // private EmailService $emailService; // Removed - Inherited from BaseController
    private User $userModel;
    private Order $orderModel;
    private Quiz $quizModel;
    // Use config for expiry
    private int $resetTokenExpiry; // Set in constructor

    public function __construct(PDO $pdo) {
        parent::__construct($pdo);
        $this->userModel = new User($pdo);
        $this->orderModel = new Order($pdo);
        $this->quizModel = new Quiz($pdo);
        // $this->emailService is initialized in parent constructor
        // Default to 1 hour (3600 seconds) if constant not defined
        $this->resetTokenExpiry = defined('PASSWORD_RESET_EXPIRY_SECONDS') ? PASSWORD_RESET_EXPIRY_SECONDS : 3600;
    }

    // --- Account Management Pages ---

    public function showDashboard() {
        try {
            $this->requireLogin(); // Checks login, session integrity, handles regeneration
            $userId = $this->getUserId();
            $currentUser = $this->getCurrentUser(); // Get user data for view

            // Fetch data
            $recentOrders = $this->orderModel->getRecentByUserId($userId, 5);
            $quizResults = $this->quizModel->getResultsByUserId($userId); // Assuming this method exists

            // Data for the view
            $data = [
                'pageTitle' => 'My Account - The Scent',
                'recentOrders' => $recentOrders,
                'quizResults' => $quizResults,
                'user' => $currentUser, // Pass user data to the view
                'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                'bodyClass' => 'page-account-dashboard'
            ];
            // Render using BaseController method
            echo $this->renderView('account/dashboard', $data); // Assuming view is in views/account/
            return;

        } catch (Exception $e) {
             $userId = $this->getUserId() ?? 'unknown';
             error_log("Account Dashboard error for user {$userId}: " . $e->getMessage());
             $this->setFlashMessage('Error loading dashboard. Please try again later.', 'error');
             $this->redirect('index.php?page=error'); // Redirect to a generic error page
        }
    }

    public function showOrders() {
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            $currentUser = $this->getCurrentUser();

            // Use BaseController validation helper
            $page = $this->validateInput($_GET['p'] ?? 1, 'int', ['min' => 1]) ?: 1;
            $perPage = 10; // Make configurable?

            // Use OrderModel methods updated previously
            $orders = $this->orderModel->getAllByUserId($userId, $page, $perPage);
            $totalOrders = $this->orderModel->getTotalOrdersByUserId($userId);
            $totalPages = ($totalOrders > 0 && $perPage > 0) ? ceil($totalOrders / $perPage) : 1;

            // Data for the view
            $data = [
                'pageTitle' => 'My Orders - The Scent',
                'orders' => $orders,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'user' => $currentUser, // Pass user data for layout/sidebar
                'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                'bodyClass' => 'page-account-orders'
            ];
            // Use BaseController render helper
            echo $this->renderView('account/orders', $data); // Assuming view is in views/account/
            return;

        } catch (Exception $e) {
             $userId = $this->getUserId() ?? 'unknown';
             error_log("Account Orders error for user {$userId}: " . $e->getMessage());
             $this->setFlashMessage('Error loading orders. Please try again later.', 'error');
             $this->redirect('index.php?page=error');
        }
    }

    public function showOrderDetails(int $orderId) {
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            $currentUser = $this->getCurrentUser();

            if ($orderId <= 0) {
                 $this->setFlashMessage('Invalid order ID.', 'error');
                 // Use BaseController redirect helper
                 $this->redirect('index.php?page=account&action=orders');
                 return;
            }

            // Use method that checks user ID and fetches items
            $order = $this->orderModel->getByIdAndUserId($orderId, $userId);

            if (!$order) {
                error_log("User {$userId} failed to access order {$orderId}");
                $this->setFlashMessage('Order not found or access denied.', 'error');
                 http_response_code(404);
                 // Render 404 view via BaseController
                 $data = [
                     'pageTitle' => 'Order Not Found',
                     'user' => $currentUser, // Pass user if needed by 404 layout
                     'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                     'bodyClass' => 'page-404'
                 ];
                 echo $this->renderView('404', $data); // Use renderView helper
                 return;
            }

            // Data for the order details view
            $data = [
                // Use htmlspecialchars on dynamic output within the view itself is better practice
                'pageTitle' => "Order #" . str_pad($order['id'], 6, '0', STR_PAD_LEFT) . " - The Scent",
                'order' => $order, // Pass the fetched order data
                'user' => $currentUser, // Pass user data for layout/sidebar
                'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                'bodyClass' => 'page-account-order-details'
            ];
            // Use BaseController render helper
            echo $this->renderView('account/order_details', $data); // Assuming view is in views/account/
            return;

        } catch (Exception $e) {
            $userId = $this->getUserId() ?? 'unknown';
            error_log("Order details error for user {$userId}, order {$orderId}: " . $e->getMessage());
            $this->setFlashMessage('Error loading order details. Please try again later.', 'error');
            $this->redirect('index.php?page=account&action=orders');
        }
    }

    public function showProfile() {
        try {
            $this->requireLogin();
            $currentUser = $this->getCurrentUser(); // Use BaseController helper

            if (!$currentUser) {
                 // Should be caught by requireLogin, but safety check
                 $this->setFlashMessage('Could not load user profile data.', 'error');
                 $this->redirect('index.php?page=login');
                 return;
            }

            // Data for the view
            $data = [
                'pageTitle' => 'My Profile - The Scent',
                'user' => $currentUser,
                'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                'bodyClass' => 'page-account-profile'
            ];
            // Use BaseController render helper
            echo $this->renderView('account/profile', $data); // Assuming view is in views/account/
            return;

        } catch (Exception $e) {
            $userId = $this->getUserId() ?? 'unknown';
            error_log("Show Profile error for user {$userId}: " . $e->getMessage());
            $this->setFlashMessage('Error loading profile. Please try again later.', 'error');
            $this->redirect('index.php?page=error');
        }
    }

    public function updateProfile() {
        $userId = null; // Initialize for error logging
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            $this->validateCSRF(); // Use BaseController method, checks POST token

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->setFlashMessage('Invalid request method.', 'warning');
                $this->redirect('index.php?page=account&action=profile');
                return;
            }

            // Validate inputs using SecurityMiddleware via BaseController helper
            $name = $this->validateInput($_POST['name'] ?? '', 'string', ['min' => 1, 'max' => 100]);
            $email = $this->validateInput($_POST['email'] ?? '', 'email');
            // Passwords are not validated here for format, only checked if new one meets requirements later
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? ''; // Need confirm password

            // Validation checks
            if ($name === false || trim($name) === '') { // Check validation result and empty string
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
                $this->setFlashMessage('Profile information updated successfully.', 'success'); // Separate message

                // Update password logic
                $passwordChanged = false;
                if (!empty($newPassword)) {
                    if (empty($currentPassword)) {
                        throw new Exception('Current password is required to set a new password.');
                    }
                    // Verify current password using UserModel method
                    if (!$this->userModel->verifyPassword($userId, $currentPassword)) {
                        $this->logSecurityEvent('profile_update_password_fail', ['user_id' => $userId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                        throw new Exception('Current password provided is incorrect.');
                    }

                    // Validate new password strength using helper
                    if (!$this->isPasswordStrong($newPassword)) {
                         // Fetch requirements from config for error message
                         $minLength = SECURITY_SETTINGS['password']['min_length'] ?? 12;
                         $reqs = [];
                         if (SECURITY_SETTINGS['password']['require_mixed_case'] ?? true) $reqs[] = "upper & lower case";
                         if (SECURITY_SETTINGS['password']['require_number'] ?? true) $reqs[] = "number";
                         if (SECURITY_SETTINGS['password']['require_special'] ?? true) $reqs[] = "special char";
                         $errMsg = sprintf('New password must be at least %d characters long and contain %s.', $minLength, implode(', ', $reqs));
                        throw new Exception($errMsg);
                    }

                    // Check if new passwords match
                    if ($newPassword !== $confirmPassword) {
                         throw new Exception('New passwords do not match.');
                    }

                    // Update password using UserModel method
                    $this->userModel->updatePassword($userId, $newPassword);
                    $this->setFlashMessage('Password updated successfully.', 'success'); // Add separate message for password
                    $passwordChanged = true;
                }

                $this->commit();

                // IMPORTANT: Update session data after successful update
                if (isset($_SESSION['user'])) {
                     $_SESSION['user']['name'] = $name;
                     $_SESSION['user']['email'] = $email;
                     // Note: Role is not updated here
                }

                $this->logAuditTrail('profile_update', $userId, ['name' => $name, 'email' => $email, 'password_changed' => $passwordChanged]);

                // Redirect back to profile page
                $this->redirect('index.php?page=account&action=profile');
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
            $this->redirect('index.php?page=account&action=profile'); // Redirect back to profile page
        }
    }

    // --- Password Reset ---

    public function requestPasswordReset() {
        // Handle showing the form on GET
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             $data = [
                 'pageTitle' => 'Forgot Password - The Scent',
                 'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                 'bodyClass' => 'page-forgot-password'
             ];
             echo $this->renderView('forgot_password', $data);
             return;
        }

        // --- POST logic ---
        $emailSubmitted = $_POST['email'] ?? ''; // For logging
        try {
            $this->validateCSRF(); // Use BaseController method
            $this->validateRateLimit('password_reset_request'); // Use BaseController method

            $email = $this->validateInput($emailSubmitted, 'email'); // Use BaseController helper

            if ($email === false) {
                 $this->logSecurityEvent('password_reset_invalid_email_format', ['submitted_email' => $emailSubmitted, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                 $this->setFlashMessage('If an account exists with that email, password reset instructions have been sent.', 'success');
                 $this->redirect('index.php?page=forgot_password');
                 return;
            }

            $this->beginTransaction();
            try {
                $user = $this->userModel->getByEmail($email);

                if ($user) {
                    $token = bin2hex(random_bytes(32)); // Generate secure token
                    $expiry = date('Y-m-d H:i:s', time() + $this->resetTokenExpiry);

                    $updated = $this->userModel->setResetToken($user['id'], $token, $expiry);

                    if ($updated) {
                        $resetLink = $this->getResetPasswordUrl($token);
                        // Use EmailService from BaseController
                        $this->emailService->sendPasswordReset($user, $token, $resetLink);
                        $this->logAuditTrail('password_reset_request', $user['id']);
                    } else {
                        error_log("Failed to set password reset token for user {$user['id']}. DB issue?");
                    }
                } else {
                    $this->logSecurityEvent('password_reset_nonexistent_email', ['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                }

                $this->commit();

            } catch (Exception $e) {
                $this->rollback();
                error_log("Password reset request internal DB/transaction error: " . $e->getMessage());
                // Fall through to generic success message
            }

            $this->setFlashMessage('If an account exists with that email, password reset instructions have been sent.', 'success');

        } catch (Exception $e) { // Catch CSRF or Rate Limit exceptions etc.
            error_log("Password reset request processing error: " . $e->getMessage());
            $this->logSecurityEvent('password_reset_request_error', ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN', 'email' => $emailSubmitted]);
            $this->setFlashMessage('An error occurred processing your request. Please try again.', 'error');
        }
        $this->redirect('index.php?page=forgot_password');
    }


    public function resetPassword() {
        // --- GET request: Show the password reset form ---
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $token = $this->validateInput($_GET['token'] ?? '', 'string', ['max' => 64]);

            if ($token === false || empty($token)) {
                $this->setFlashMessage('Invalid password reset link.', 'error');
                $this->redirect('index.php?page=forgot_password');
                return;
            }

            $user = $this->userModel->getUserByValidResetToken($token);
            if (!$user) {
                $this->logSecurityEvent('password_reset_invalid_token_on_get', ['token' => $token, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                $this->setFlashMessage('This password reset link is invalid or has expired. Please request a new one.', 'error');
                $this->redirect('index.php?page=forgot_password');
                return;
            }

            $data = [
                'pageTitle' => 'Reset Your Password - The Scent',
                'token' => $token,
                'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                'bodyClass' => 'page-reset-password'
            ];
            echo $this->renderView('reset_password', $data);
            return;
        }

        // --- POST logic: Process the password reset ---
        $token = $this->validateInput($_POST['token'] ?? '', 'string', ['max' => 64]);
        try {
            $this->validateCSRF(); // Use BaseController method
            $this->validateRateLimit('password_reset_attempt'); // Use BaseController method

            if ($token === false || empty($token)) {
                throw new Exception('Invalid or missing password reset token submitted.');
            }

            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($password)) {
                throw new Exception('Password cannot be empty.');
            }
            if ($password !== $confirmPassword) {
                throw new Exception('Passwords do not match.');
            }
            if (!$this->isPasswordStrong($password)) {
                 $minLength = SECURITY_SETTINGS['password']['min_length'] ?? 12;
                 $reqs = [];
                 if (SECURITY_SETTINGS['password']['require_mixed_case'] ?? true) $reqs[] = "upper & lower case";
                 if (SECURITY_SETTINGS['password']['require_number'] ?? true) $reqs[] = "number";
                 if (SECURITY_SETTINGS['password']['require_special'] ?? true) $reqs[] = "special char";
                 $errMsg = sprintf('Password must be at least %d characters long and contain %s.', $minLength, implode(', ', $reqs));
                 throw new Exception($errMsg);
             }

            $this->beginTransaction();
            try {
                $user = $this->userModel->getUserByValidResetToken($token);
                if (!$user) {
                    $this->logSecurityEvent('password_reset_invalid_token_on_post', ['token' => $token, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                    throw new Exception('This password reset link is invalid or has expired. Please request a new one.');
                }
                $this->userModel->resetPassword($user['id'], $password);
                $this->logAuditTrail('password_reset_complete', $user['id']);
                $this->commit();

                $this->setFlashMessage('Your password has been successfully reset. Please log in.', 'success');
                $this->redirect('index.php?page=login');
                return;

            } catch (Exception $e) {
                $this->rollback();
                error_log("Password reset transaction error for token {$token}: " . $e->getMessage());
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Password reset processing error: " . $e->getMessage());
            $this->logSecurityEvent('password_reset_error', ['error' => $e->getMessage(), 'token' => $token, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            $this->setFlashMessage($e->getMessage(), 'error');
            $this->redirect('index.php?page=reset_password&token=' . urlencode($token ?: ''));
            return;
        }
    }


    public function updateNewsletterPreferences() {
        $userId = null; // Initialize for logging
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            $this->validateCSRF();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                 $this->setFlashMessage('Invalid request method.', 'warning');
                 $this->redirect('index.php?page=account&action=profile');
                 return;
            }

            // --- MODIFIED: Checkbox handling ---
            // Checkbox value is only sent if checked. Check its existence.
            $newsletterSubscribed = isset($_POST['newsletter_subscribed']); // True if checked, false if not present
            // --- END MODIFICATION ---

            $this->beginTransaction();
            try {
                // Assuming UserModel handles boolean correctly
                $this->userModel->updateNewsletterPreference($userId, $newsletterSubscribed);

                $action = $newsletterSubscribed ? 'newsletter_subscribe_profile' : 'newsletter_unsubscribe_profile';
                $this->logAuditTrail($action, $userId);

                $this->commit();
                $this->setFlashMessage('Newsletter preferences updated.', 'success');

            } catch (Exception $e) {
                $this->rollback();
                error_log("Newsletter preference update transaction error for user {$userId}: " . $e->getMessage());
                // Throw more specific or generic error as needed
                throw new Exception('Failed to update preferences. Database error.');
            }

        } catch (Exception $e) {
            $userId = $userId ?? ($this->getUserId() ?? 'unknown');
            error_log("Newsletter preference update failed for user {$userId}: " . $e->getMessage());
            $this->logSecurityEvent('newsletter_update_fail', ['user_id' => $userId, 'error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            $this->setFlashMessage('Failed to update newsletter preferences. Please try again.', 'error');
        }
        $this->redirect('index.php?page=account&action=profile');
    }

    // --- Authentication (Login / Register) ---

    public function login() {
        // --- GET request: Show the login form ---
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             $data = [
                 'pageTitle' => 'Login - The Scent',
                 'csrfToken' => $this->getCsrfToken(), // CORRECTED: Use BaseController method
                 'bodyClass' => 'page-login bg-gradient-to-br from-light to-secondary/20'
             ];
             echo $this->renderView('login', $data);
             return;
        }

        // --- POST logic: Process login via AJAX ---
        $emailSubmitted = $_POST['email'] ?? ''; // For logging
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        try {
            $this->validateCSRF(); // Use BaseController method
            $this->validateRateLimit('login'); // Use BaseController method

            $email = $this->validateInput($emailSubmitted, 'email');
            $password = $_POST['password'] ?? '';

            if ($email === false || empty($password)) {
                $this->logSecurityEvent('login_invalid_input', ['email' => $emailSubmitted, 'ip' => $ipAddress]);
                throw new Exception('Invalid email or password format.');
            }

            $user = $this->userModel->getByEmail($email);

            if (!$user || !password_verify($password, $user['password'])) {
                $userId = $user['id'] ?? null;
                $this->logSecurityEvent('login_failure', ['email' => $email, 'ip' => $ipAddress, 'user_id' => $userId]);
                throw new Exception('Invalid email or password.');
            }

            if (isset($user['status']) && $user['status'] === 'locked') {
                 $this->logSecurityEvent('login_attempt_locked', ['user_id' => $user['id'], 'email' => $email, 'ip' => $ipAddress]);
                 throw new Exception('Your account is currently locked. Please contact support.');
            }

            // --- Login Success ---
            $this->regenerateSession(); // Use BaseController protected method

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            $_SESSION['user'] = [
                 'id' => $user['id'],
                 'name' => $user['name'],
                 'email' => $user['email'],
                 'role' => $_SESSION['user_role']
            ];
             $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
             $_SESSION['ip_address'] = $ipAddress;
             $_SESSION['last_login'] = time();
             $_SESSION['last_regeneration'] = time(); // Update regeneration time

             // Merge cart
             if (class_exists('CartController')) {
                 CartController::mergeSessionCartOnLogin($this->db, $user['id']);
                 if (class_exists('Cart')) {
                     $cartModel = new Cart($this->db, $user['id']);
                     $_SESSION['cart_count'] = $cartModel->getCartCount();
                 } else { $_SESSION['cart_count'] = 0; }
             } else { error_log("CartController class not found, cannot merge session cart."); }

            $this->logAuditTrail('login_success', $user['id']);

            $redirectUrl = $_SESSION['redirect_after_login'] ?? (BASE_URL . 'index.php?page=account&action=dashboard');
            unset($_SESSION['redirect_after_login']);

            $this->jsonResponse(['success' => true, 'redirect' => $redirectUrl]); // Exit

        } catch (Exception $e) {
            error_log("Login failed for email '{$emailSubmitted}' from IP {$ipAddress}: " . $e->getMessage());
             $statusCode = ($e->getCode() === 429) ? 429 : 401;
             $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], $statusCode); // Exit
        }
    }


     public function register() {
         // --- GET request: Show the registration form ---
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
              $data = [
                  'pageTitle' => 'Register - The Scent',
                  'csrfToken' => $this->getCsrfToken(), // CORRECTED: Use BaseController method
                  'bodyClass' => 'page-register'
              ];
              echo $this->renderView('register', $data);
             return;
         }

         // --- POST logic: Process registration via AJAX ---
        $emailSubmitted = $_POST['email'] ?? '';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        try {
            $this->validateRateLimit('register');
            $this->validateCSRF();

            $email = $this->validateInput($emailSubmitted, 'email');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $name = $this->validateInput($_POST['name'] ?? '', 'string', ['min' => 2, 'max' => 100]);
            $newsletterPref = isset($_POST['newsletter_signup']) && $_POST['newsletter_signup'] === '1'; // Checkbox presence

            if ($email === false || empty($password) || $name === false) {
                 $this->logSecurityEvent('register_invalid_input', ['email' => $emailSubmitted, 'name_valid' => ($name !== false), 'ip' => $ipAddress]);
                 throw new Exception('Invalid input provided. Please check email, name, and password.');
            }
            if ($this->userModel->getByEmail($email)) {
                 throw new Exception('This email address is already registered.');
            }
            if (!$this->isPasswordStrong($password)) {
                 $minLength = SECURITY_SETTINGS['password']['min_length'] ?? 12;
                 $reqs = [];
                 if (SECURITY_SETTINGS['password']['require_mixed_case'] ?? true) $reqs[] = "upper & lower case";
                 if (SECURITY_SETTINGS['password']['require_number'] ?? true) $reqs[] = "number";
                 if (SECURITY_SETTINGS['password']['require_special'] ?? true) $reqs[] = "special char";
                 $errMsg = sprintf('Password must be at least %d characters long and contain %s.', $minLength, implode(', ', $reqs));
                 throw new Exception($errMsg);
             }
             if ($password !== $confirmPassword) {
                  throw new Exception('Passwords do not match.');
             }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            if ($hashedPassword === false) {
                 error_log("Password hashing failed during registration: " . print_r(error_get_last(), true));
                 throw new Exception('Could not process password securely.');
            }

            $this->beginTransaction();
            try {
                $userData = [
                    'email' => $email,
                    'password' => $hashedPassword,
                    'name' => $name,
                    'role' => 'user',
                    'newsletter' => $newsletterPref // Pass preference to model
                ];
                $userId = $this->userModel->create($userData);

                 if (!$userId) {
                     throw new Exception('Failed to create user account in database.');
                 }

                 // Send welcome email
                 if ($this->emailService && method_exists($this->emailService, 'sendWelcome')) {
                     $emailSent = $this->emailService->sendWelcome($email, $name);
                     if (!$emailSent) {
                          error_log("Failed to send welcome email to {$email} for new user ID {$userId}, but registration succeeded.");
                     }
                 } else {
                      error_log("EmailService or sendWelcome method not available. Cannot send welcome email.");
                 }

                 $this->logAuditTrail('user_registered', $userId);
                 $this->commit();

                 $this->setFlashMessage('Registration successful! Please log in.', 'success');
                 $this->jsonResponse(['success' => true, 'redirect' => BASE_URL . 'index.php?page=login']); // Exit

            } catch (Exception $e) {
                 $this->rollback();
                 error_log("User creation transaction error: " . $e->getMessage());
                 throw new Exception('An error occurred during registration. Please try again.');
            }

        } catch (Exception $e) {
            error_log("Registration failed for email '{$emailSubmitted}' from IP {$ipAddress}: " . $e->getMessage());
            $this->logSecurityEvent('register_failure', ['email' => $emailSubmitted, 'error' => $e->getMessage(), 'ip' => $ipAddress]);
            $statusCode = ($e->getCode() === 429) ? 429 : 400;
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $statusCode); // Exit
        }
    }

    // --- Private Helper Methods ---

    /**
     * Checks if a password meets the defined security requirements.
     *
     * @param string $password The password to check.
     * @return bool True if strong, false otherwise.
     */
    private function isPasswordStrong(string $password): bool {
        $settings = SECURITY_SETTINGS['password'] ?? [];
        $minLength = $settings['min_length'] ?? 12;
        $reqSpecial = $settings['require_special'] ?? true;
        $reqNumber = $settings['require_number'] ?? true;
        $reqMixedCase = $settings['require_mixed_case'] ?? true;

        if (mb_strlen($password) < $minLength) { return false; }
        if ($reqMixedCase && (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password))) { return false; }
        if ($reqNumber && !preg_match('/[0-9]/', $password)) { return false; }
        if ($reqSpecial && !preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $password)) { return false; }
        return true;
    }

    /**
     * Generates the full URL for the password reset link.
     *
     * @param string $token The password reset token.
     * @return string The absolute URL.
     */
    private function getResetPasswordUrl(string $token): string {
        $baseUrl = rtrim(BASE_URL, '/');
        return $baseUrl . "/index.php?page=reset_password&token=" . urlencode($token);
    }

} // End of AccountController class
```

---

**Summary of Changes and Review (`AccountController.php`)**

1.  **CSRF Token Call Fix:**
    *   In `login()` (GET handler): Changed `$this->generateCSRFToken()` to `$this->getCsrfToken()`. **Fix applied.**
    *   In `register()` (GET handler): Changed `$this->generateCSRFToken()` to `$this->getCsrfToken()`. **Fix applied.**

2.  **BaseController Method Usage:**
    *   Calls to `requireLogin`, `validateCSRF`, `validateRateLimit`, `validateInput`, `getCsrfToken`, `redirect`, `renderView`, `setFlashMessage`, `logAuditTrail`, `logSecurityEvent`, transaction methods (`beginTransaction`, `commit`, `rollback`), user helpers (`getUserId`, `getCurrentUser`) now correctly use the methods available in the updated `BaseController`.

3.  **Session Handling:**
    *   The explicit call to `$this->regenerateSession()` after successful login uses the (now `protected`) method correctly.
    *   Session data (`$_SESSION['user']`, `$_SESSION['user_id']`, `$_SESSION['user_role']`, integrity markers) is set using the structure expected by `BaseController`.

4.  **Code Refinements:**
    *   Error messages for password strength (`isPasswordStrong` check) are now more specific based on `SECURITY_SETTINGS`.
    *   Password confirmation check added to `updateProfile`.
    *   Newsletter preference update correctly checks `isset($_POST['newsletter_subscribed'])`.
    *   `renderView` is used consistently for showing pages.
    *   `redirect` is used consistently for redirects.
    *   `jsonResponse` is used consistently for AJAX responses in `login` and `register`.
    *   Password reset expiry uses `PASSWORD_RESET_EXPIRY_SECONDS` from `config.php` if defined.
    *   Added more specific exception handling messages in some places.
    *   Removed commented-out/redundant code (like old SecurityMiddleware direct calls).
    *   Ensured user data is passed to views (`account/dashboard`, `account/orders`, etc.) for use in sidebars or content.
    *   Corrected view paths (e.g., `account/dashboard` instead of `account_dashboard`).

**Omission Check:**

Comparing the new version line-by-line with the original (`controllers/AccountController.php-old`), all original functionalities (dashboard display, orders display/details, profile display/update, password reset request/process, newsletter update, login, register) are preserved. The logic has been updated to use the refined `BaseController` methods, improving consistency and potentially security/robustness, but the core features remain intact. The calls to CartController and EmailService are preserved.

**Conclusion:**

The updated `controllers/AccountController.php` correctly fixes the `Call to undefined method AccountController::generateCSRFToken()` error by using `$this->getCsrfToken()` instead. It also integrates well with the updated `BaseController`, utilizing its helper methods for validation, responses, logging, and session management. The code is now more consistent and robust.

---
https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%2211gLlqjm9P5onq6O3bP03zvYHMR1OL5Q6%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/11pHAnzuQujVYyhZSqtRs_-L1pupNj2Mw/view?usp=sharing, https://drive.google.com/file/d/15PpD2JKuq752Pa1KKvmsCT5gh6cbmfAO/view?usp=sharing, https://drive.google.com/file/d/17Nvr39ffZlADMFvaM0r1S3DCn8p98v3r/view?usp=sharing, https://drive.google.com/file/d/1CqhbbX1wI4FejmRaQqt_LjVj6WPPFNFJ/view?usp=sharing, https://drive.google.com/file/d/1EO8T2FZeyfp__XpbDxQwQbU8T1eOsXUw/view?usp=sharing, https://drive.google.com/file/d/1Jro_incgi67_rNrokpTMrYT5MfmT3_ZH/view?usp=sharing, https://drive.google.com/file/d/1LeGw2tBB2CMwMmutAWcrmzIyoQtV3UnU/view?usp=sharing, https://drive.google.com/file/d/1NUz2D4sugn1UnJsOXqrwmsU06WHZq3fR/view?usp=sharing, https://drive.google.com/file/d/1S3jeejXCwisQeyTKfG-7JboP93qjvH2a/view?usp=sharing, https://drive.google.com/file/d/1Uk01MipQX9kWQ5f4HZOI6TP8Fr4s9Pcw/view?usp=sharing, https://drive.google.com/file/d/1V26FOntpGfQ51xF-ciKW3tvB0V0D_Aml/view?usp=sharing, https://drive.google.com/file/d/1WvJwy5ATfaPyJu2bHDQeKw0lc5EviQI8/view?usp=sharing, https://drive.google.com/file/d/1Xl-NqEG9c10oSmgJFcnSHl7mvQI31DBT/view?usp=sharing, https://drive.google.com/file/d/1aj7OFfCI5U6wcz1-PcCeRgaegP_8vkXS/view?usp=sharing, https://drive.google.com/file/d/1imW3TDAUBPz4ncXDYOiHdItw0-n4gW0f/view?usp=sharing, https://drive.google.com/file/d/1j_lF5-CNbPuX260HuoFwKOSwMs5m2AEY/view?usp=sharing, https://drive.google.com/file/d/1oWpHoVnF11ul_k05v0HNgtNEMpMOtb4h/view?usp=sharing, https://drive.google.com/file/d/1r7fvQrgps_6Rlr5SjmiOAWN7VK5QwzY6/view?usp=sharing, https://drive.google.com/file/d/1xbWNknkK86fxP2tjD9tY-PQOnP8dvw_m/view?usp=sharing
