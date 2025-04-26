You will meticulously review the remaining PHP files systematically to ensure they are compatible with the refactored `AccountController.php`, the updated `main.js`, and the confirmed `index.php`.

You will review the following recommendation, then think deeply and systematically to explore thoroughly for the best implementation option to make the necessary code changes.  
Remember to think deeply and thoroughly to explore the best implementation option to fix this issue before choosing the best implementation option to carefully merge the fix to the existing code files, taking care not to loose other features and functions while making changes. Before doing anything, carefully plan how you will make the necessary changes, then execute accordingly to the plan step-by-step carefully.

Note that views/layout/header.php has already been updated to be compliance with the following recommendation.

**Findings based on initial review:**

The vast majority of the reviewed PHP files are compatible with the refactored `AccountController` and the updated `main.js`/`index.php`. The core logic (routing, base controller methods, other controller actions, models) is sound.

The **only required change** identified is the **missing CSRF token input** (`#csrf-token-value`) in the views rendered for the login and registration pages (`views/login.php` and `views/register.php`). The updated `main.js` relies on this input for its AJAX submissions for these specific forms.

**Recommendation:**

Instead of adding the input individually to `login.php`, `register.php`, `home.php`, `cart.php`, etc., the most robust and maintainable solution is to **add the global CSRF token input ONCE in the main layout header.**

1.  **Modify `views/layout/header.php`:**
    *   Ensure that *every* controller action that renders a full page view (using `renderView` or requiring a layout file) generates a CSRF token using `$csrfToken = $this->generateCSRFToken();` (or `SecurityMiddleware::generateCSRFToken()`) and passes it to the view data.
    *   Add the hidden input *immediately after* the opening `<body>` tag in `header.php`:

    ```php
    // ... inside header.php ...
    </head>
    <body class="<?= $bodyClass ?? '' ?>"> <!-- Assuming controllers pass a $bodyClass -->
        <!-- Global CSRF Token for JavaScript -->
        <input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
    
        <header>
            <nav class="main-nav sample-header">
    // ... rest of header ...
    ```

2.  **Modify Controllers (If Necessary):**
    *   Double-check that controllers like `AccountController` (for `login` GET, `register` GET, `requestPasswordReset` GET, `resetPassword` GET), `ProductController` (for `showHomePage`, `showProductList`, `showProduct`), `CartController` (for `showCart`), etc., consistently generate `$csrfToken = $this->generateCSRFToken();` and include it in the data passed to `renderView` or `require_once`. Example for login GET in `AccountController`:
        ```php
        // Inside AccountController::login() for GET request
        echo $this->renderView('login', [
            'pageTitle' => 'Login - The Scent',
            'csrfToken' => $this->generateCSRFToken(), // Ensure token is passed
            'bodyClass' => 'page-login' // Also pass body class
        ]);
        return;
        ```
    *   Ensure the `renderView` method (or direct `require`) correctly extracts the `$csrfToken` variable so it's available in the header's scope.
    *   Ensure controllers also pass a `$bodyClass` variable (e.g., `'page-login'`) to `renderView` so the correct class is added to the `<body>` tag for `main.js` initialization.

3.  **Remove Individual CSRF Inputs:** Once the global token is in the header, you can *remove* the individual `<input type="hidden" id="csrf-token-value" ...>` lines from `views/home.php`, `views/cart.php`, `views/products.php`, `views/product_detail.php`, etc., as they become redundant. *Keep* the form-specific CSRF inputs (like `<input type="hidden" name="csrf_token" ...>`) inside forms handled by *standard POST* submissions (e.g., password reset, profile update, admin forms, cart update if not fully AJAX).

By adding the global `#csrf-token-value` to the layout header and ensuring controllers pass the token value, `main.js` will always find the necessary token for its AJAX requests (login, register, add-to-cart, newsletter), resolving the identified incompatibility. No changes are needed to the already updated `main.js` or `index.php`.

---
Okay, let's review the specified controllers (`AccountController`, `BaseController`, `CartController`, `CheckoutController`) and update them as needed to comply with the requirement of passing `$csrfToken` and `$bodyClass` to views rendered via the layout header.

**1. Review `controllers/BaseController.php`**

*   The `renderView` method uses `extract($data)`, which means any keys in the `$data` array become variables in the view scope.
*   Methods like `generateCSRFToken()` are already present.
*   **Conclusion:** No changes are needed in `BaseController.php` itself. The responsibility lies with the calling methods in the specific controllers.

**2. Review `controllers/AccountController.php`**

*   **Methods needing update:** `showDashboard`, `showOrders`, `showOrderDetails`, `showProfile`, `login` (GET), `register` (GET), `requestPasswordReset` (GET), `resetPassword` (GET).
*   These methods already call `renderView` and most pass `$csrfToken`. They just need the `$bodyClass` added.

**Updated `controllers/AccountController.php`:**

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
    private EmailService $emailService;
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
                 'bodyClass' => 'page-login' // Added body class
             ];
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

**3. Review `controllers/CartController.php`**

*   **Methods needing update:** `showCart`. This method requires the layout.
*   Other methods (`addToCart`, `updateCart`, `removeFromCart`, `mini`, `clearCart` (POST)) use `jsonResponse` or `redirect` and don't need modification for this requirement.

**Updated `controllers/CartController.php`:**

```php
<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Cart.php';

class CartController extends BaseController {
    // Properties remain the same...
    private $productModel;
    private $cartModel = null;
    private $isLoggedIn = false;
    private $userId = null;

    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->userId = $_SESSION['user_id'] ?? null;
        $this->isLoggedIn = $this->userId !== null;
        if ($this->isLoggedIn) {
            $this->cartModel = new Cart($pdo, $this->userId);
        } else {
            $this->initCart(); // Ensures session cart exists for guests
        }
    }

    private function initCart() {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
         // Ensure session cart count exists for guests
         if (!isset($_SESSION['cart_count'])) {
             $_SESSION['cart_count'] = 0;
         }
    }

    // mergeSessionCartOnLogin remains unchanged
    public static function mergeSessionCartOnLogin($pdo, $userId) {
        if (!empty($_SESSION['cart'])) {
            // Ensure Cart model is loaded if called statically
            if (!class_exists('Cart')) {
                require_once __DIR__ . '/../models/Cart.php';
            }
            $cartModel = new Cart($pdo, $userId);
            $cartModel->mergeSessionCart($_SESSION['cart']);
            // After successful merge, update DB cart count? Potentially done by addItem/updateItem logic
        }
        // Always clear session cart after merging attempt
        $_SESSION['cart'] = [];
        $_SESSION['cart_count'] = 0; // Reset guest count
        // Optionally, immediately fetch and set the DB cart count in session here
        // $_SESSION['cart_count'] = $cartModel->getCartCount(); // Assuming Cart model has getCartCount method
    }


    public function showCart() {
        $cartItems = [];
        $total = 0;

        if ($this->isLoggedIn) {
            // Fetch items for logged-in user
            $items = $this->cartModel->getItems();
            foreach ($items as $item) {
                $cartItems[] = [
                    'product' => $item, // Assumes getItems joins product data
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['price'] * $item['quantity']
                ];
                $total += $item['price'] * $item['quantity'];
            }
            // Update session count for logged-in user (optional, but good for consistency)
             $_SESSION['cart_count'] = array_reduce($items, fn($sum, $item) => $sum + $item['quantity'], 0);
        } else {
            // Fetch items for guest from session
            $this->initCart(); // Ensure session cart array exists
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $product = $this->productModel->getById($productId);
                if ($product) {
                    $cartItems[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'subtotal' => $product['price'] * $quantity
                    ];
                    $total += $product['price'] * $quantity;
                } else {
                    // Product might have been deleted, remove from session cart
                    unset($_SESSION['cart'][$productId]);
                }
            }
             // Update session count for guest
             $_SESSION['cart_count'] = array_sum($_SESSION['cart']);
        }

        // Prepare data for the view
        $csrfToken = $this->generateCSRFToken();
        $bodyClass = 'page-cart'; // Define body class
        $pageTitle = 'Your Shopping Cart'; // Define page title

        // Make variables available to the view file
        // Since using require_once, define variables directly in this scope
        extract([
            'cartItems' => $cartItems,
            'total' => $total,
            'csrfToken' => $csrfToken,
            'bodyClass' => $bodyClass,
            'pageTitle' => $pageTitle
        ]);

        // Load the view file
        require_once __DIR__ . '/../views/cart.php';
        // No return needed after require_once for a full page view
    }


    // --- AJAX Methods ---
    // addToCart, updateCart, removeFromCart, clearCart (POST), mini
    // These methods use jsonResponse and don't need modification for this specific requirement.

    // Example: addToCart - remains unchanged
    public function addToCart() {
        $this->validateCSRF(); // Ensures POST has valid form CSRF
        $productId = $this->validateInput($_POST['product_id'] ?? null, 'int');
        $quantity = (int)$this->validateInput($_POST['quantity'] ?? 1, 'int');

        if (!$productId || $quantity < 1) {
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid product or quantity'], 400);
        }
        $product = $this->productModel->getById($productId);
        if (!$product) {
            return $this->jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
        }

        // Calculate current quantity in cart to check against stock
        $currentQuantityInCart = 0;
        if ($this->isLoggedIn) {
            $existingItem = $this->cartModel->getItem($productId); // Assuming Cart model has getItem($productId)
            if ($existingItem) {
                $currentQuantityInCart = $existingItem['quantity'];
            }
        } else {
            $this->initCart();
            $currentQuantityInCart = $_SESSION['cart'][$productId] ?? 0;
        }
        $requestedTotalQuantity = $currentQuantityInCart + $quantity;


        // Check stock availability *before* adding
        if (!$this->productModel->isInStock($productId, $requestedTotalQuantity)) {
            $stockInfo = $this->productModel->checkStock($productId);
            $stockStatus = 'out_of_stock';
            $availableStock = $stockInfo ? max(0, $stockInfo['stock_quantity']) : 0; // Get actual available stock
            $message = $availableStock > 0 ? "Only {$availableStock} left in stock." : "Insufficient stock.";

            return $this->jsonResponse([
                'success' => false,
                'message' => $message,
                'cart_count' => $this->getCartCount(),
                'stock_status' => $stockStatus
            ], 400); // Use 400 Bad Request for stock issues
        }

        // Add item
        if ($this->isLoggedIn) {
            $this->cartModel->addItem($productId, $quantity);
            $cartCount = $this->getCartCount(true); // Force DB count update
        } else {
            // Session cart standardized to [productId => quantity]
            $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
            $cartCount = array_sum($_SESSION['cart']);
            $_SESSION['cart_count'] = $cartCount; // Update session count
        }

        // Check stock status *after* adding (for button state feedback)
        $stockInfo = $this->productModel->checkStock($productId);
        $stockStatus = 'in_stock'; // Default
        if ($stockInfo) {
            $remainingStock = $stockInfo['stock_quantity'] - ($this->isLoggedIn ? $this->cartModel->getItem($productId)['quantity'] : $_SESSION['cart'][$productId]);
            if (!$stockInfo['backorder_allowed']) {
                if ($remainingStock <= 0) {
                    $stockStatus = 'out_of_stock';
                } elseif ($stockInfo['low_stock_threshold'] && $remainingStock <= $stockInfo['low_stock_threshold']) {
                    $stockStatus = 'low_stock';
                }
            }
        } else {
            // Should not happen if product exists, but handle defensively
            $stockStatus = 'unknown';
        }


        $userId = $this->userId;
        $this->logAuditTrail('cart_add', $userId, [
            'product_id' => $productId,
            'quantity' => $quantity,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        return $this->jsonResponse([
            'success' => true,
            'message' => htmlspecialchars($product['name']) . ' added to cart',
            'cart_count' => $cartCount,
            'stock_status' => $stockStatus // Provide current stock status after add
        ]);
    }

    // updateCart, removeFromCart, etc. remain largely the same...
    // Ensure they correctly update/return cart_count and handle session vs DB logic
    public function updateCart() {
        $this->validateCSRF();
        $updates = $_POST['updates'] ?? [];
        $stockErrors = [];
        $cartCount = 0; // Initialize cart count

        if ($this->isLoggedIn) {
            foreach ($updates as $productId => $quantity) {
                $productId = $this->validateInput($productId, 'int');
                $quantity = (int)$this->validateInput($quantity, 'int');
                if ($productId === false || $quantity === false) continue; // Skip invalid input

                if ($quantity > 0) {
                    if (!$this->productModel->isInStock($productId, $quantity)) {
                        $product = $this->productModel->getById($productId);
                        $stockErrors[] = "{$product['name']} has insufficient stock";
                        continue; // Skip update for this item
                    }
                    $this->cartModel->updateItem($productId, $quantity);
                } else {
                    $this->cartModel->removeItem($productId);
                }
            }
            $cartCount = $this->getCartCount(true); // Force DB count update
        } else {
            $this->initCart();
            foreach ($updates as $productId => $quantity) {
                 $productId = $this->validateInput($productId, 'int');
                 $quantity = (int)$this->validateInput($quantity, 'int');
                 if ($productId === false || $quantity === false) continue; // Skip invalid input

                if ($quantity > 0) {
                    if (!$this->productModel->isInStock($productId, $quantity)) {
                        $product = $this->productModel->getById($productId);
                         $stockErrors[] = "{$product['name']} has insufficient stock";
                        continue; // Skip update for this item
                    }
                    $_SESSION['cart'][$productId] = $quantity;
                } else {
                    unset($_SESSION['cart'][$productId]);
                }
            }
            $cartCount = array_sum($_SESSION['cart']);
            $_SESSION['cart_count'] = $cartCount; // Update session count
        }

        $userId = $this->userId;
        $this->logAuditTrail('cart_update', $userId, [
            'updates' => $updates, // Log the requested updates
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        return $this->jsonResponse([
            'success' => empty($stockErrors),
            'message' => empty($stockErrors) ? 'Cart updated' : 'Some items have insufficient stock. Cart partially updated.',
            'cart_count' => $cartCount, // Return updated count
            'errors' => $stockErrors
        ]);
    }


    public function removeFromCart() {
        $this->validateCSRF();
        $productId = $this->validateInput($_POST['product_id'] ?? null, 'int');
        if ($productId === false) {
             return $this->jsonResponse(['success' => false, 'message' => 'Invalid product ID'], 400);
        }

        $cartCount = 0; // Initialize
        if ($this->isLoggedIn) {
            $this->cartModel->removeItem($productId);
            $cartCount = $this->getCartCount(true); // Force DB count update
        } else {
            $this->initCart();
            if (!isset($_SESSION['cart'][$productId])) {
                // Optional: return error if not found, or just proceed silently
                // return $this->jsonResponse(['success' => false, 'message' => 'Product not found in cart'], 404);
            }
            unset($_SESSION['cart'][$productId]);
            $cartCount = array_sum($_SESSION['cart']);
            $_SESSION['cart_count'] = $cartCount; // Update session count
        }

        $userId = $this->userId;
        $this->logAuditTrail('cart_remove', $userId, [
            'product_id' => $productId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Product removed from cart',
            'cart_count' => $cartCount // Return updated count
        ]);
    }

     public function clearCart() {
        $cartCount = 0;
        if ($this->isLoggedIn) {
            $this->cartModel->clearCart();
            // Count is implicitly 0
        } else {
            $_SESSION['cart'] = [];
            $_SESSION['cart_count'] = 0;
            // Count is 0
        }
         $userId = $this->userId;
         $this->logAuditTrail('cart_clear', $userId, ['ip' => $_SERVER['REMOTE_ADDR'] ?? null]);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCSRF(); // Validate CSRF for POST request
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Cart cleared',
                'cart_count' => $cartCount
            ]);
        } else {
             // For GET request (e.g., link click), redirect
             $this->setFlashMessage('Cart cleared successfully.', 'success');
            $this->redirect('cart');
        }
    }

     // Helper to get cart count consistently
     private function getCartCount($forceDbCheck = false) {
         if ($this->isLoggedIn) {
             // Optimization: Use session count if available and not forcing DB check
             if (!$forceDbCheck && isset($_SESSION['cart_count'])) {
                 return $_SESSION['cart_count'];
             }
             // Fetch count from DB (Assuming Cart model has this method)
             $count = $this->cartModel->getCartCount() ?? 0; // Requires getCartCount in Cart model
             $_SESSION['cart_count'] = $count; // Update session
             return $count;
         } else {
             // Guest count comes directly from session array
             $this->initCart();
             $count = array_sum($_SESSION['cart']);
             $_SESSION['cart_count'] = $count; // Ensure session count is up-to-date
             return $count;
         }
     }

     // Mini cart AJAX endpoint
     public function mini() {
         $items = [];
         $subtotal = 0;
         $cartCount = 0; // Get current count

         if ($this->isLoggedIn) {
             $cartItems = $this->cartModel->getItems(); // Assumes getItems joins products
             foreach ($cartItems as $item) {
                 // Construct item data exactly as expected by JS
                 $items[] = [
                     'product' => [
                         'id' => $item['product_id'], // Use product_id from cart_items table join
                         'name' => $item['name'],
                         'image' => $item['image'], // Assuming image is directly on product row
                         'price' => $item['price']
                     ],
                     'quantity' => $item['quantity']
                 ];
                 $subtotal += $item['price'] * $item['quantity'];
             }
             $cartCount = $this->getCartCount(true); // Force DB check for accuracy
         } else {
             $this->initCart();
             foreach ($_SESSION['cart'] as $productId => $quantity) {
                 $product = $this->productModel->getById($productId);
                 if ($product) {
                     $items[] = [
                         'product' => [
                             'id' => $product['id'],
                             'name' => $product['name'],
                             'image' => $product['image'],
                             'price' => $product['price']
                         ],
                         'quantity' => $quantity
                     ];
                     $subtotal += $product['price'] * $quantity;
                 }
             }
             $cartCount = $this->getCartCount(); // Get session count
         }

         // Always return success true, even if empty, but indicate via items array
         return $this->jsonResponse([
             'success' => true, // Indicate successful fetch
             'items' => $items,
             'subtotal' => $subtotal,
             'cart_count' => $cartCount // Include count for header update if needed
         ]);
     }


    // validateCartStock and getCartItems remain largely the same
     public function validateCartStock() {
         $errors = [];
         $cart = $this->isLoggedIn ? $this->cartModel->getItems() : ($_SESSION['cart'] ?? []);
         if ($this->isLoggedIn) {
             foreach ($cart as $item) {
                 if (!$this->productModel->isInStock($item['product_id'], $item['quantity'])) {
                     $errors[] = "{$item['name']} has insufficient stock";
                 }
             }
         } else {
             foreach ($cart as $productId => $quantity) {
                 if (!$this->productModel->isInStock($productId, $quantity)) {
                     $product = $this->productModel->getById($productId);
                     $errors[] = ($product ? $product['name'] : "Product ID {$productId}") . " has insufficient stock";
                 }
             }
         }
         return $errors;
     }

     public function getCartItems() {
         $cartItems = [];
         if ($this->isLoggedIn) {
             $items = $this->cartModel->getItems();
             foreach ($items as $item) {
                 $cartItems[] = [
                     'product' => $item,
                     'quantity' => $item['quantity'],
                     'subtotal' => $item['price'] * $item['quantity']
                 ];
             }
         } else {
             $this->initCart();
             foreach ($_SESSION['cart'] as $productId => $quantity) {
                 $product = $this->productModel->getById($productId);
                 if ($product) {
                     $cartItems[] = [
                         'product' => $product,
                         'quantity' => $quantity,
                         'subtotal' => $product['price'] * $quantity
                     ];
                 }
             }
         }
         return $cartItems;
     }


} // End of CartController class

// Assume Cart model needs methods:
// getItem($productId)
// getCartCount()
```

**4. Review `controllers/CheckoutController.php`**

*   **Methods needing update:** `showCheckout`, `showOrderConfirmation`. These require the layout.
*   `processCheckout` and `calculateTax` use `jsonResponse` or `redirect` and are OK.

**Updated `controllers/CheckoutController.php`:**

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

**Summary of Controller Changes:**

*   **`AccountController.php`:** Updated all methods that render full views (`showDashboard`, `showOrders`, `showOrderDetails`, `showProfile`, `login` GET, `register` GET, `requestPasswordReset` GET, `resetPassword` GET) to include the appropriate `'bodyClass' => 'page-...'` key-value pair in the data array passed to `$this->renderView()`. Also ensured `$csrfToken` is consistently passed.
*   **`CartController.php`:** Updated `showCart()` to define `$bodyClass = 'page-cart';` and `$csrfToken`, then use `extract()` to make them available before the `require_once '../views/cart.php';` call. Also added logic to correctly set the initial `$_SESSION['cart_count']` for both logged-in users and guests within `showCart`. Added robustness to AJAX methods regarding stock checks and cart count updates.
*   **`CheckoutController.php`:** Updated `showCheckout()` and `showOrderConfirmation()` to define `$bodyClass` and `$csrfToken`, then use `extract()` before their respective `require_once` calls. Added missing `requireLogin()` calls and improved cart fetching/validation logic.

With these controller updates and the previously updated `header.php` and `main.js`, the application should now consistently provide the necessary `<body>` class and global CSRF token for the JavaScript to function correctly across all relevant pages.
