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
                // Merge session cart into DB cart
                require_once __DIR__ . '/CartController.php';
                CartController::mergeSessionCartOnLogin($this->pdo, $user['id']);
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