<?php
require_once __DIR__ . '/../includes/EmailService.php';

abstract class BaseController {
    protected $db;
    protected $securityMiddleware;
    protected $emailService;
    protected $responseHeaders = [];
    
    public function __construct($pdo) {
        $this->db = $pdo;
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->securityMiddleware = new SecurityMiddleware();
        $this->emailService = new EmailService();
        $this->initializeSecurityHeaders();
    }
    
    protected function initializeSecurityHeaders() {
        $this->responseHeaders = [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()'
        ];
    }
    
    protected function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        
        // Set security headers
        foreach ($this->responseHeaders as $header => $value) {
            header("$header: $value");
        }
        
        // Add CSRF token to responses that might lead to forms
        if ($this->shouldIncludeCSRFToken()) {
            $data['csrf_token'] = $this->securityMiddleware->generateCSRFToken();
        }
        
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($this->sanitizeOutput($data));
    }
    
    protected function sendError($message, $statusCode = 400, $context = []) {
        $errorResponse = [
            'error' => true,
            'message' => $message,
            'code' => $statusCode
        ];
        
        // Log error with context for monitoring
        ErrorHandler::logError($message, $context);
        
        // Only include debug info in development
        if (DEBUG_MODE && !empty($context)) {
            $errorResponse['debug'] = $context;
        }
        
        $this->sendResponse($errorResponse, $statusCode);
    }
    
    protected function validateRequest($rules) {
        $errors = [];
        $input = $this->getRequestInput();
        
        if (!is_array($rules) && !is_object($rules)) {
            // Defensive: if rules is not array/object, skip validation
            return true;
        }
        foreach ($rules as $field => $validations) {
            if (!isset($input[$field]) && strpos($validations, 'required') !== false) {
                $errors[$field] = "The {$field} field is required";
                continue;
            }
            
            if (isset($input[$field])) {
                $value = $input[$field];
                $validationArray = explode('|', $validations);
                
                foreach ($validationArray as $validation) {
                    if (!$this->validateField($value, $validation)) {
                        $errors[$field] = "The {$field} field failed {$validation} validation";
                    }
                }
            }
        }
        
        if (!empty($errors)) {
            $this->sendError('Validation failed', 422, ['validation_errors' => $errors]);
            return false;
        }
        
        return true;
    }
    
    protected function validateField($value, $rule) {
        switch ($rule) {
            case 'required':
                return !empty($value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL);
            case 'numeric':
                return is_numeric($value);
            case 'array':
                return is_array($value);
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL);
            // Add more validation rules as needed
        }
        
        // Check for min:x, max:x patterns
        if (preg_match('/^(min|max):(\d+)$/', $rule, $matches)) {
            $type = $matches[1];
            $limit = (int)$matches[2];
            
            if ($type === 'min') {
                return strlen($value) >= $limit;
            } else {
                return strlen($value) <= $limit;
            }
        }
        
        return true;
    }
    
    protected function getRequestInput() {
        $input = [];
        
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $input = $_GET;
                break;
            case 'POST':
            case 'PUT':
            case 'DELETE':
                $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
                
                if (strpos($contentType, 'application/json') !== false) {
                    $input = json_decode(file_get_contents('php://input'), true) ?? [];
                } else {
                    $input = $_POST;
                }
                break;
        }
        
        return $this->sanitizeInput($input);
    }
    
    protected function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        // Remove NULL bytes
        $data = str_replace(chr(0), '', $data);
        
        // Convert special characters to HTML entities
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    protected function sanitizeOutput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeOutput'], $data);
        }
        
        if (is_string($data)) {
            // Ensure proper UTF-8 encoding
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
        
        return $data;
    }
    
    protected function shouldIncludeCSRFToken() {
        $safeRoutes = [
            'login',
            'register',
            'password/reset',
            'checkout'
        ];
        
        $currentRoute = strtolower($_SERVER['REQUEST_URI']);
        
        foreach ($safeRoutes as $route) {
            if (strpos($currentRoute, $route) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function requireAuthentication() {
        if (!$this->securityMiddleware->isAuthenticated()) {
            $this->sendError('Unauthorized', 401);
            return false;
        }
        return true;
    }
    
    protected function requireCSRFToken() {
        if (!$this->securityMiddleware->validateCSRFToken()) {
            $this->sendError('Invalid CSRF token', 403);
            return false;
        }
        return true;
    }
    
    protected function rateLimit($key, $maxAttempts = 60, $decayMinutes = 1) {
        if (!$this->securityMiddleware->checkRateLimit($key, $maxAttempts, $decayMinutes)) {
            $this->sendError('Too many requests', 429);
            return false;
        }
        return true;
    }
    
    protected function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            $this->logSecurityEvent('unauthorized_access_attempt', [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'uri' => $_SERVER['REQUEST_URI']
            ]);
            $this->jsonResponse(['error' => 'Authentication required'], 401);
        }
        
        // Verify session integrity
        if (!$this->validateSessionIntegrity()) {
            $this->terminateSession('Session integrity check failed');
        }
        
        // Check session age and regenerate if needed
        if ($this->shouldRegenerateSession()) {
            $this->regenerateSession();
        }
    }
    
    protected function requireAdmin() {
        $this->requireLogin();
        
        if ($_SESSION['user_role'] !== 'admin') {
            $this->logSecurityEvent('unauthorized_admin_attempt', [
                'user_id' => $_SESSION['user_id'],
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            $this->jsonResponse(['error' => 'Admin access required'], 403);
        }
    }
    
    protected function validateInput($data, $rules) {
        $errors = [];
        if (!is_array($rules) && !is_object($rules)) {
            // Defensive: if rules is not array/object, skip validation
            return true;
        }
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field]) && $rule['required'] ?? false) {
                $errors[$field] = 'Field is required';
                continue;
            }
            
            if (isset($data[$field])) {
                $value = $data[$field];
                $error = $this->securityMiddleware->validateInput($value, $rule['type'], $rule);
                if ($error !== true) {
                    $errors[$field] = $error;
                }
            }
        }
        
        if (!empty($errors)) {
            $this->jsonResponse(['errors' => $errors], 422);
        }
        
        return true;
    }
    
    protected function getCsrfToken() {
        return SecurityMiddleware::generateCSRFToken();
    }
    
    protected function validateCSRF() {
        SecurityMiddleware::validateCSRF();
    }
    
    protected function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    protected function redirect($url, $statusCode = 302) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $url = BASE_URL . ltrim($url, '/');
        }
        
        header('Location: ' . $url, true, $statusCode);
        exit;
    }
    
    protected function setFlashMessage($message, $type = 'info') {
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type,
            'created' => time()
        ];
    }
    
    protected function getFlashMessage() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            
            // Ensure flash messages don't persist too long
            if (time() - $flash['created'] < 300) { // 5 minutes
                return $flash;
            }
        }
        return null;
    }
    
    protected function beginTransaction() {
        $this->db->beginTransaction();
    }
    
    protected function commit() {
        $this->db->commit();
    }
    
    protected function rollback() {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
    
    protected function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
    
    protected function getUserId() {
        return $_SESSION['user']['id'] ?? null;
    }
    
    protected function validateAjax() {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
        }
    }
    
    protected function isRateLimited($key, $maxAttempts, $timeWindow) {
        $rateLimitKey = "rate_limit:{$key}:" . $_SERVER['REMOTE_ADDR'];
        $attempts = $_SESSION[$rateLimitKey] ?? ['count' => 0, 'first_attempt' => time()];
        
        if (time() - $attempts['first_attempt'] > $timeWindow) {
            // Reset if time window has passed
            $attempts = ['count' => 1, 'first_attempt' => time()];
        } else {
            $attempts['count']++;
        }
        
        $_SESSION[$rateLimitKey] = $attempts;
        
        return $attempts['count'] > $maxAttempts;
    }
    
    protected function renderView($viewPath, $data = []) {
        // Extract data to make it available in view
        extract($data);
        
        // Start output buffering
        ob_start();
        
        $viewFile = __DIR__ . '/../views/' . $viewPath . '.php';
        if (!file_exists($viewFile)) {
            throw new Exception("View not found: {$viewPath}");
        }
        
        require $viewFile;
        
        return ob_get_clean();
    }
    
    protected function validateFileUpload($file, $allowedTypes, $maxSize = 5242880) { // 5MB default
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid file upload');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File too large');
            case UPLOAD_ERR_PARTIAL:
                throw new Exception('File upload interrupted');
            default:
                throw new Exception('Unknown upload error');
        }

        if ($file['size'] > $maxSize) {
            throw new Exception('File too large');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type');
        }

        return true;
    }
    
    protected function log($message, $level = 'info') {
        $logFile = __DIR__ . '/../logs/' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        error_log($formattedMessage, 3, $logFile);
    }

    protected function checkRateLimit($key, $limit = null, $window = null) {
        $limit = $limit ?? $this->rateLimit['max_requests'];
        $window = $window ?? $this->rateLimit['window'];
        
        $redis = RedisConnection::getInstance();
        $requests = $redis->incr("rate_limit:{$key}");
        
        if ($requests === 1) {
            $redis->expire("rate_limit:{$key}", $window);
        }
        
        return $requests <= $limit;
    }

    protected function logAuditTrail($action, $userId, $details = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_log (
                    action, user_id, ip_address, user_agent, details
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $action,
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                json_encode($details)
            ]);
        } catch (Exception $e) {
            error_log("Audit logging failed: " . $e->getMessage());
        }
    }

    private function validateSessionIntegrity() {
        if (!isset($_SESSION['user_agent']) || !isset($_SESSION['ip_address'])) {
            return false;
        }
        
        return $_SESSION['user_agent'] === $_SERVER['HTTP_USER_AGENT'] &&
               $_SESSION['ip_address'] === $_SERVER['REMOTE_ADDR'];
    }
    
    private function shouldRegenerateSession() {
        return !isset($_SESSION['last_regeneration']) ||
               (time() - $_SESSION['last_regeneration']) > SECURITY_SETTINGS['session']['regenerate_id_interval'];
    }
    
    private function regenerateSession() {
        $oldSession = $_SESSION;
        session_regenerate_id(true);
        $_SESSION = $oldSession;
        $_SESSION['last_regeneration'] = time();
    }
    
    protected function terminateSession($reason) {
        $userId = $_SESSION['user_id'] ?? null;
        $this->logSecurityEvent('session_terminated', [
            'reason' => $reason,
            'user_id' => $userId
        ]);
        
        session_destroy();
        $this->jsonResponse(['error' => 'Session terminated for security reasons'], 401);
    }
    
    protected function validateRateLimit($action) {
        $settings = SECURITY_SETTINGS['rate_limiting']['endpoints'][$action] ?? [
            'window' => SECURITY_SETTINGS['rate_limiting']['default_window'],
            'max_requests' => SECURITY_SETTINGS['rate_limiting']['default_max_requests']
        ];
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = "rate_limit:{$action}:{$ip}";
        // Check whitelist
        if (in_array($ip, SECURITY_SETTINGS['rate_limiting']['ip_whitelist'] ?? [])) {
            return true;
        }
        // Fail closed if APCu is unavailable
        if (!function_exists('apcu_fetch') || !ini_get('apc.enabled')) {
            $this->logSecurityEvent('rate_limit_backend_unavailable', [
                'action' => $action,
                'ip' => $ip
            ]);
            $this->jsonResponse(['error' => 'Rate limiting backend unavailable. Please try again later.'], 503);
        }
        $attempts = apcu_fetch($key) ?: 0;
        if ($attempts >= $settings['max_requests']) {
            $this->logSecurityEvent('rate_limit_exceeded', [
                'action' => $action,
                'ip' => $ip,
                'attempts' => $attempts
            ]);
            $this->jsonResponse(['error' => 'Rate limit exceeded. Please try again later.'], 429);
        }
        // Increment the counter or add it if it doesn't exist
        if ($attempts === 0) {
            apcu_store($key, 1, $settings['window']);
        } else {
            apcu_inc($key);
        }
        return true;
    }
    
    protected function validateCSRFToken() {
        if (!SECURITY_SETTINGS['csrf']['enabled']) {
            return true;
        }
        
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->logSecurityEvent('csrf_validation_failed', [
                'user_id' => $_SESSION['user_id'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            $this->jsonResponse(['error' => 'CSRF token validation failed'], 403);
        }
        
        return true;
    }
    
    protected function generateCSRFToken() {
        if (!SECURITY_SETTINGS['csrf']['enabled']) {
            return '';
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(SECURITY_SETTINGS['csrf']['token_length']));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    protected function logSecurityEvent($event, $details = []) {
        $details = array_merge($details, [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        error_log(
            sprintf(
                "[SECURITY] %s | %s",
                $event,
                json_encode($details)
            ),
            3,
            SECURITY_SETTINGS['logging']['security_log']
        );
    }
}