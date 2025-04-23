# controllers/AccountController.php  
```php
<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Quiz.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/EmailService.php';
require_once __DIR__ . '/../includes/SecurityMiddleware.php';

class AccountController extends BaseController {
    private $emailService;
    private $userModel;
    private $orderModel;
    private $quizModel;
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 900; // 15 minutes
    private $resetTokenExpiry = 3600; // 1 hour
    private $securityHeaders = [
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
        'Referrer-Policy' => 'strict-origin-when-cross-origin'
    ];

    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->emailService = new EmailService();
        $this->userModel = new User($pdo);
        $this->orderModel = new Order($pdo);
        $this->quizModel = new Quiz($pdo);
        
        // Set security headers
        foreach ($this->securityHeaders as $header => $value) {
            header("$header: $value");
        }
    }
    
    public function showDashboard() {
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            
            // Get recent orders with transaction
            $this->beginTransaction();
            
            try {
                $recentOrders = $this->orderModel->getRecentByUserId($userId, 5);
                $quizResults = $this->quizModel->getResultsByUserId($userId);
                
                $this->commit();
                
                return $this->renderView('account/dashboard', [
                    'pageTitle' => 'My Account - The Scent',
                    'recentOrders' => $recentOrders,
                    'quizResults' => $quizResults
                ]);
                
            } catch (Exception $e) {
                $this->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Dashboard error: " . $e->getMessage());
            $this->setFlashMessage('Error loading dashboard', 'error');
            return $this->redirect('error');
        }
    }
    
    public function showOrders() {
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            
            // Validate and sanitize pagination params
            $page = max(1, (int)$this->validateInput($_GET['p'] ?? 1, 'int'));
            $perPage = 10;
            
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
            $this->setFlashMessage('Error loading orders', 'error');
            return $this->redirect('error');
        }
    }
    
    public function showOrderDetails($orderId) {
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            
            // Validate input
            $orderId = $this->validateInput($orderId, 'int');
            if (!$orderId) {
                throw new Exception('Invalid order ID');
            }
            
            // Get order with auth check
            $order = $this->orderModel->getByIdAndUserId($orderId, $userId);
            if (!$order) {
                return $this->renderView('404');
            }
            
            return $this->renderView('account/order_details', [
                'pageTitle' => "Order #" . str_pad($order['id'], 6, '0', STR_PAD_LEFT) . " - The Scent",
                'order' => $order
            ]);
            
        } catch (Exception $e) {
            error_log("Order details error: " . $e->getMessage());
            $this->setFlashMessage('Error loading order details', 'error');
            return $this->redirect('account/orders');
        }
    }
    
    public function showProfile() {
        try {
            $this->requireLogin();
            $user = $this->getCurrentUser();
            
            return $this->renderView('account/profile', [
                'pageTitle' => 'My Profile - The Scent',
                'user' => $user
            ]);
            
        } catch (Exception $e) {
            error_log("Profile error: " . $e->getMessage());
            $this->setFlashMessage('Error loading profile', 'error');
            return $this->redirect('error');
        }
    }
    
    public function updateProfile() {
        try {
            $this->requireLogin();
            $this->validateCSRF();
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->redirect('account/profile');
            }
            
            $userId = $this->getUserId();
            
            // Validate inputs
            $name = $this->validateInput($_POST['name'] ?? '', 'string');
            $email = $this->validateInput($_POST['email'] ?? '', 'email');
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            
            if (empty($name) || empty($email)) {
                throw new Exception('Name and email are required.');
            }
            
            $this->beginTransaction();
            
            try {
                // Check if email is taken by another user
                if ($this->userModel->isEmailTakenByOthers($email, $userId)) {
                    throw new Exception('Email already in use.');
                }
                
                // Update basic info
                $this->userModel->updateBasicInfo($userId, $name, $email);
                
                // Update password if provided
                if ($newPassword) {
                    if (!$this->userModel->verifyPassword($userId, $currentPassword)) {
                        throw new Exception('Current password is incorrect.');
                    }
                    
                    // Validate password strength
                    if (!$this->isPasswordStrong($newPassword)) {
                        throw new Exception('Password must be at least 12 characters, contain uppercase, lowercase, number, special character, and no character repeated 3+ times.');
                    }
                    
                    $this->userModel->updatePassword($userId, $newPassword);
                }
                
                $this->commit();
                
                // Update session
                $_SESSION['user'] = array_merge(
                    $_SESSION['user'], 
                    ['name' => $name, 'email' => $email]
                );
                
                $this->setFlashMessage('Profile updated successfully.', 'success');
                
                // Log the profile update
                $this->logAuditTrail('profile_update', $userId);
                
                return $this->redirect('account/profile');
                
            } catch (Exception $e) {
                $this->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $this->setFlashMessage($e->getMessage(), 'error');
            return $this->redirect('account/profile');
        }
    }
    
    public function requestPasswordReset() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return;
            }
            
            $this->validateCSRF();
            
            // Rate limit password reset requests
            $this->validateRateLimit('reset');
            
            $email = $this->validateInput($_POST['email'] ?? '', 'email');
            if (!$email) {
                throw new Exception('Please enter a valid email address.');
            }
            
            $this->beginTransaction();
            
            try {
                // Generate a secure random token
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', time() + $this->resetTokenExpiry);
                
                // Update user record with reset token
                $updated = $this->userModel->setResetToken($email, $token, $expiry);
                
                if ($updated) {
                    // Get user details for the email
                    $user = $this->userModel->getByEmail($email);
                    
                    $resetLink = $this->getResetPasswordUrl($token);
                    
                    // Send password reset email
                    $this->emailService->sendPasswordReset($user, $token, $resetLink);
                    
                    // Log the password reset request
                    $this->logAuditTrail('password_reset_request', $user['id']);
                }
                
                $this->commit();
                
                // Always show same message to prevent email enumeration
                $this->setFlashMessage('If an account exists with that email, we have sent password reset instructions.', 'success');
                
            } catch (Exception $e) {
                $this->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Password reset request error: " . $e->getMessage());
            $this->setFlashMessage('An error occurred. Please try again later.', 'error');
        }
        
        return $this->redirect('forgot_password');
    }
    
    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCSRF();
                
                // Standardized rate limit check
                $this->validateRateLimit('reset');
                
                // Validate inputs
                $token = $this->validateInput($_POST['token'] ?? '', 'string');
                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                if (!$token) {
                    throw new Exception('Invalid password reset token.');
                }
                
                if ($password !== $confirmPassword) {
                    throw new Exception('Passwords do not match.');
                }
                
                if (!$this->isPasswordStrong($password)) {
                    throw new Exception('Password must be at least 12 characters, contain uppercase, lowercase, number, special character, and no character repeated 3+ times.');
                }
                
                $this->beginTransaction();
                
                try {
                    // Verify token and get user
                    $user = $this->userModel->getUserByValidResetToken($token);
                    if (!$user) {
                        throw new Exception('This password reset link has expired or is invalid.');
                    }
                    
                    // Update password and clear reset token
                    $this->userModel->resetPassword($user['id'], $password);
                    
                    // Log the password reset
                    $this->logAuditTrail('password_reset_complete', $user['id']);
                    
                    $this->commit();
                    
                    $this->setFlashMessage('Your password has been successfully reset. Please log in with your new password.', 'success');
                    return $this->redirect('login');
                    
                } catch (Exception $e) {
                    $this->rollback();
                    throw $e;
                }
                
            } catch (Exception $e) {
                error_log("Password reset error: " . $e->getMessage());
                $this->setFlashMessage($e->getMessage(), 'error');
                return $this->redirect("reset_password?token=" . urlencode($token));
            }
        }
    }
    
    public function updateNewsletterPreferences() {
        try {
            $this->requireLogin();
            $this->validateCSRF();
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->redirect('account/profile');
            }
            
            $userId = $this->getUserId();
            $newsletter = filter_var($_POST['newsletter'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            $this->beginTransaction();
            
            try {
                $this->userModel->updateNewsletterPreference($userId, $newsletter);
                
                // Log the preference update
                $this->logAuditTrail('newsletter_preference_update', $userId);
                
                $this->commit();
                
                $this->setFlashMessage('Newsletter preferences updated successfully.', 'success');
                
            } catch (Exception $e) {
                $this->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Newsletter preference update error: " . $e->getMessage());
            $this->setFlashMessage('Failed to update newsletter preferences.', 'error');
        }
        
        return $this->redirect('account/profile');
    }
    
    private function isPasswordStrong($password) {
        // Enhanced password requirements
        return strlen($password) >= 12 &&           // Minimum length
               preg_match('/[A-Z]/', $password) &&  // Uppercase
               preg_match('/[a-z]/', $password) &&  // Lowercase
               preg_match('/[0-9]/', $password) &&  // Number
               preg_match('/[^A-Za-z0-9]/', $password) && // Special char
               !preg_match('/(.)\1{2,}/', $password);    // No character repeated 3+ times
    }
    
    private function getResetPasswordUrl($token) {
        return "https://" . $_SERVER['HTTP_HOST'] . "/index.php?page=reset_password&token=" . urlencode($token);
    }
    
    private function logAuditTrail($action, $userId) {
        try {
            $data = [
                'user_id' => $userId,
                'action' => $action,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ];
            
            $this->db->insert('audit_trail', $data);
        } catch (Exception $e) {
            error_log("Audit trail error: " . $e->getMessage());
        }
    }

    private function monitorSuspiciousActivity($userId, $activityType) {
        $suspiciousPatterns = [
            'multiple_failed_logins' => 3,    // 3 failed attempts
            'password_resets' => 2,           // 2 resets in 24h
            'profile_updates' => 5            // 5 updates in 24h
        ];

        try {
            // Check activity count in last 24h
            $activityCount = $this->userModel->getRecentActivityCount(
                $userId, 
                $activityType, 
                'P1D'  // Last 24 hours
            );

            if ($activityCount >= $suspiciousPatterns[$activityType]) {
                // Log suspicious activity
                error_log("Suspicious activity detected: {$activityType} for user {$userId}");
                
                // Notify admin
                $this->emailService->notifyAdminOfSuspiciousActivity(
                    $userId, 
                    $activityType, 
                    $activityCount
                );

                // Optional: Take defensive action like temporary lockout
                if ($activityType === 'multiple_failed_logins') {
                    $this->lockAccount($userId, 'suspicious_activity');
                }
            }
        } catch (Exception $e) {
            // Log error but don't block the main flow
            error_log("Error monitoring activity: " . $e->getMessage());
        }
    }

    private function lockAccount($userId, $reason) {
        try {
            $this->beginTransaction();
            
            // Set account status to locked
            $this->userModel->updateAccountStatus($userId, 'locked');
            
            // Log the lockout
            $this->logAuditTrail('account_lockout', $userId, ['reason' => $reason]);
            
            // Notify user
            $user = $this->userModel->getById($userId);
            $this->emailService->sendAccountLockoutNotification($user, $reason);
            
            $this->commit();
            
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Validate CSRF token
                $this->validateCSRFToken();
                
                // Standardized rate limit check
                $this->validateRateLimit('login');
                
                // Validate input
                $email = SecurityMiddleware::validateInput($_POST['email'] ?? '', 'email');
                $password = SecurityMiddleware::validateInput($_POST['password'] ?? '', 'string', ['min' => 8]);
                
                if (!$email || !$password) {
                    throw new Exception('Invalid credentials');
                }
                
                // Attempt login
                $user = $this->userModel->findByEmail($email);
                if (!$user || !password_verify($password, $user['password'])) {
                    $this->logFailedLogin($email, $_SERVER['REMOTE_ADDR']);
                    throw new Exception('Invalid credentials');
                }
                
                // Success - create session
                $this->createSecureSession($user);
                
                // Audit log
                $this->logAuditEvent('login_success', $user['id']);
                
                return $this->jsonResponse(['success' => true, 'redirect' => '/dashboard']);
                
            } catch (Exception $e) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 401);
            }
        }
        
        return $this->render('login', [
            'csrfToken' => $this->generateCSRFToken()
        ]);
    }
    
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCSRFToken();
                
                // Validate input
                $email = SecurityMiddleware::validateInput($_POST['email'] ?? '', 'email');
                $password = SecurityMiddleware::validateInput($_POST['password'] ?? '', 'password');
                $name = SecurityMiddleware::validateInput($_POST['name'] ?? '', 'string', ['min' => 2, 'max' => 100]);
                
                if (!$email || !$password || !$name) {
                    throw new Exception('Invalid input');
                }
                
                // Check if email exists
                if ($this->userModel->findByEmail($email)) {
                    throw new Exception('Email already registered');
                }
                
                // Create user
                $userId = $this->userModel->create([
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'name' => $name
                ]);
                
                // Send welcome email
                $this->sendWelcomeEmail($email, $name);
                
                // Audit log
                $this->logAuditEvent('user_registered', $userId);
                
                return $this->jsonResponse(['success' => true, 'redirect' => '/login']);
                
            } catch (Exception $e) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }
        }
        
        return $this->render('register', [
            'csrfToken' => $this->generateCSRFToken()
        ]);
    }
    
    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCSRFToken();
                
                // Standardized rate limit check
                $this->validateRateLimit('reset');
                
                $email = SecurityMiddleware::validateInput($_POST['email'] ?? '', 'email');
                if (!$email) {
                    throw new Exception('Invalid email');
                }
                
                $user = $this->userModel->findByEmail($email);
                if (!$user) {
                    // Don't reveal if email exists
                    return $this->jsonResponse(['success' => true]);
                }
                
                // Generate secure token
                $token = bin2hex(random_bytes(32));
                $expires = time() + 3600; // 1 hour
                
                // Store reset token
                $this->userModel->storeResetToken($user['id'], $token, $expires);
                
                // Send reset email
                $this->sendPasswordResetEmail($email, $token);
                
                // Audit log
                $this->logAuditEvent('password_reset_requested', $user['id']);
                
                return $this->jsonResponse(['success' => true]);
                
            } catch (Exception $e) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Unable to process request'
                ], 400);
            }
        }
        
        return $this->render('reset_password', [
            'csrfToken' => $this->generateCSRFToken()
        ]);
    }
    
    public function logout() {
        if (isset($_SESSION['user'])) {
            $userId = $_SESSION['user']['id'];
            
            // Destroy session
            session_destroy();
            
            // Generate new session id
            session_start();
            session_regenerate_id(true);
            
            // Audit log
            $this->logAuditEvent('logout', $userId);
        }
        
        return $this->redirect('/login');
    }
    
    private function createSecureSession($user) {
        // Start fresh session
        if (session_status() !== PHP_SESSION_NONE) {
            session_destroy();
        }
        session_start();
        
        // Regenerate session ID
        session_regenerate_id(true);
        
        // Set session data
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
            'created_at' => time()
        ];
        
        // Set additional security headers
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
    }
    
    private function logFailedLogin($email, $ip) {
        // Implementation for logging failed login attempts
        // This could write to a database or log file
    }
    
    private function logAuditEvent($action, $userId) {
        // Implementation for audit logging
        // This would typically write to a separate audit log table
    }
}
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
        $this->requireLogin();
        
        if (empty($_SESSION['cart'])) {
            $this->redirect('cart');
        }
        
        $cartItems = [];
        $subtotal = 0;
        
        // Get cart items
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
        
        // Calculate initial tax (0% until country is selected)
        $tax_rate_formatted = '0%';
        $tax_amount = 0;
        
        // Calculate shipping cost
        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        
        // Calculate total
        $total = $subtotal + $shipping_cost + $tax_amount;
        
        require_once __DIR__ . '/../views/checkout.php';
    }
    
    public function calculateTax() {
        $this->validateCSRF(); // Enforce CSRF validation for AJAX tax calculation
        
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
        $subtotal = 0;
        
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $product = $this->productModel->getById($productId);
            if ($product) {
                $subtotal += $product['price'] * $quantity;
            }
        }
        
        return $subtotal;
    }
    
    public function processCheckout() {
        $this->requireLogin();
        $this->validateCSRF();
        
        if (empty($_SESSION['cart'])) {
            $this->redirect('cart');
        }
        
        // Validate form data
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
            
            // Validate stock levels before proceeding
            $stockErrors = $this->validateCartStock();
            if (!empty($stockErrors)) {
                throw new Exception('Some items are out of stock: ' . implode(', ', $stockErrors));
            }
            
            $subtotal = $this->calculateCartSubtotal();
            $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
            
            // Calculate tax
            $tax_amount = $this->taxController->calculateTax(
                $subtotal,
                $this->validateInput($_POST['shipping_country'], 'string'),
                $this->validateInput($_POST['shipping_state'], 'string')
            );
            
            $total = $subtotal + $shipping_cost + $tax_amount;
            
            // Create order
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
            
            // Create order items and update inventory
            $stmt = $this->pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $product = $this->productModel->getById($productId);
                if ($product) {
                    // Add order item
                    $stmt->execute([
                        $orderId,
                        $productId,
                        $quantity,
                        $product['price']
                    ]);
                    
                    // Update inventory
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
            
            // Process payment
            $paymentResult = $this->paymentController->processPayment($orderId);
            
            if (!$paymentResult['success']) {
                throw new Exception($paymentResult['error']);
            }
            
            $this->commit();
            // Audit log for order placement
            $this->logAuditTrail('order_placed', $userId, [
                'order_id' => $orderId,
                'total_amount' => $total,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
            // Send order confirmation email
            $user = $this->getCurrentUser();
            $order = $this->orderModel->getById($orderId);
            
            $this->emailService->sendOrderConfirmation($order, $user);
            
            // Store order ID for confirmation
            $_SESSION['last_order_id'] = $orderId;
            $_SESSION['cart'] = [];
            
            // Return payment intent client secret for Stripe.js
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
    
    private function validateCartStock() {
        $errors = [];
        
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            if (!$this->productModel->isInStock($productId, $quantity)) {
                $product = $this->productModel->getById($productId);
                $errors[] = "{$product['name']} has insufficient stock";
            }
        }
        
        return $errors;
    }
    
    public function showOrderConfirmation() {
        $this->requireLogin();
        
        if (!isset($_SESSION['last_order_id'])) {
            $this->redirect('products');
        }
        
        // Get order details
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
        
        // Clear the stored order ID
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
            
            // Update order status
            $this->orderModel->updateStatus($orderId, $status);
            
            // If order is shipped and tracking info provided, update tracking
            if ($status === 'shipped' && $trackingInfo) {
                $this->orderModel->updateTracking(
                    $orderId,
                    $trackingInfo['number'],
                    $trackingInfo['carrier'],
                    $trackingInfo['url']
                );
                
                // Send shipping notification email
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

