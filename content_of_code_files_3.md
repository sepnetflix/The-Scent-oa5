# includes/SecurityMiddleware.php  
```php
<?php

class SecurityMiddleware {
    private static $ipTracker = [];
    private static $requestTracker = [];
    private static $encryptionKey;

    public static function apply() {
        // Set security headers from config
        if (defined('SECURITY_SETTINGS') && isset(SECURITY_SETTINGS['headers'])) {
            foreach (SECURITY_SETTINGS['headers'] as $header => $value) {
                header("$header: $value");
            }
        }

        // Set secure cookie parameters
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 3600,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 3600) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }

        // Initialize encryption key
        if (!isset($_ENV['ENCRYPTION_KEY'])) {
            self::$encryptionKey = self::generateSecureKey();
        } else {
            self::$encryptionKey = $_ENV['ENCRYPTION_KEY'];
        }
        
        // Track request patterns
        self::trackRequest();
    }

    private static function trackRequest() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $timestamp = time();
        $uri = $_SERVER['REQUEST_URI'];
        
        if (!isset(self::$requestTracker[$ip])) {
            self::$requestTracker[$ip] = [];
        }
        
        // Clean old entries
        self::$requestTracker[$ip] = array_filter(
            self::$requestTracker[$ip],
            fn($t) => $t > ($timestamp - 3600)
        );
        
        self::$requestTracker[$ip][] = $timestamp;
        
        // Check for anomalies
        if (self::detectAnomaly($ip)) {
            self::handleAnomaly($ip);
        }
    }

    private static function detectAnomaly($ip) {
        if (!isset(self::$requestTracker[$ip])) {
            return false;
        }

        $requests = self::$requestTracker[$ip];
        $count = count($requests);
        $timespan = end($requests) - reset($requests);

        // Detect rapid requests
        if ($count > 100 && $timespan < 60) { // More than 100 requests per minute
            return true;
        }

        // Detect pattern-based attacks
        if (self::detectPatternAttack($ip)) {
            return true;
        }

        return false;
    }

    private static function detectPatternAttack($ip) {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return false;
        }

        $patterns = [
            '/union\s+select/i',
            '/exec(\s|\+)+(x?p?\w+)/i',
            '/\.\.\//i',
            '/<(script|iframe|object|embed|applet)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $_SERVER['REQUEST_URI'])) {
                return true;
            }
        }

        return false;
    }

    private static function handleAnomaly($ip) {
        // Log the anomaly
        error_log("Security anomaly detected from IP: {$ip}");
        
        // Add to temporary blacklist
        self::$ipTracker[$ip] = time();
        
        // Return 403 response
        http_response_code(403);
        exit('Access denied due to suspicious activity');
    }

    public static function validateInput($input, $type, $options = []) {
        if ($input === null) {
            return null;
        }
        
        // Basic sanitization for all inputs
        if (is_string($input)) {
            $input = trim($input);
        }
        
        switch ($type) {
            case 'email':
                $email = filter_var($input, FILTER_VALIDATE_EMAIL);
                if ($email && strlen($email) <= 254) { // RFC 5321
                    return $email;
                }
                return false;
                
            case 'int':
                $min = $options['min'] ?? null;
                $max = $options['max'] ?? null;
                $int = filter_var($input, FILTER_VALIDATE_INT);
                if ($int === false) return false;
                if ($min !== null && $int < $min) return false;
                if ($max !== null && $int > $max) return false;
                return $int;
                
            case 'float':
                $min = $options['min'] ?? null;
                $max = $options['max'] ?? null;
                $float = filter_var($input, FILTER_VALIDATE_FLOAT);
                if ($float === false) return false;
                if ($min !== null && $float < $min) return false;
                if ($max !== null && $float > $max) return false;
                return $float;
                
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);
                
            case 'string':
                $min = $options['min'] ?? 0;
                $max = $options['max'] ?? 65535;
                $allowedTags = $options['allowTags'] ?? [];
                
                // Remove any tags not specifically allowed
                $cleaned = strip_tags($input, $allowedTags);
                $cleaned = htmlspecialchars($cleaned, ENT_QUOTES, 'UTF-8');
                
                if (strlen($cleaned) < $min || strlen($cleaned) > $max) {
                    return false;
                }
                return $cleaned;
                
            case 'password':
                $minLength = $options['minLength'] ?? 8;
                if (strlen($input) < $minLength) return false;
                
                // Check password strength
                $hasUpper = preg_match('/[A-Z]/', $input);
                $hasLower = preg_match('/[a-z]/', $input);
                $hasNumber = preg_match('/[0-9]/', $input);
                $hasSpecial = preg_match('/[^A-Za-z0-9]/', $input);
                
                return $hasUpper && $hasLower && $hasNumber && $hasSpecial;
                
            case 'date':
                $format = $options['format'] ?? 'Y-m-d';
                $date = DateTime::createFromFormat($format, $input);
                return $date && $date->format($format) === $input;
                
            case 'array':
                if (!is_array($input)) return false;
                $validItems = [];
                foreach ($input as $item) {
                    $validated = self::validateInput($item, $options['itemType'] ?? 'string');
                    if ($validated !== false) {
                        $validItems[] = $validated;
                    }
                }
                return $validItems;
                
            case 'filename':
                // Remove potentially dangerous characters
                $safe = preg_replace('/[^a-zA-Z0-9._-]/', '', $input);
                // Ensure no double extensions
                $parts = explode('.', $safe);
                if (count($parts) > 2) {
                    return false;
                }
                return $safe;

            case 'xml':
                return self::validateXML($input);
            case 'json':
                return self::validateJSON($input);
            case 'html':
                return self::validateHTML($input);
                
            default:
                return false;
        }
    }

    private static function validateXML($input) {
        // Prevent XML injection
        $dangerousElements = ['<!ENTITY', '<!ELEMENT', '<!DOCTYPE'];
        foreach ($dangerousElements as $element) {
            if (stripos($input, $element) !== false) {
                return false;
            }
        }
        
        // Validate XML structure
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($input);
        return $doc !== false;
    }

    private static function validateJSON($input) {
        json_decode($input);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private static function validateHTML($input) {
        // Strip dangerous HTML
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($input);
    }

    public static function validateCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
                !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                http_response_code(403);
                throw new Exception('CSRF token validation failed');
            }
        }
    }
    
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateFileUpload($file, $allowedTypes, $maxSize = 5242880) {
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
        
        // Scan file for malware (if ClamAV is installed)
        if (function_exists('clamav_scan')) {
            $scan = clamav_scan($file['tmp_name']);
            if ($scan !== true) {
                throw new Exception('File may be infected');
            }
        }

        return true;
    }
    
    public static function sanitizeFileName($filename) {
        // Remove any directory components
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Ensure single extension
        $parts = explode('.', $filename);
        if (count($parts) > 2) {
            $ext = array_pop($parts);
            $filename = implode('_', $parts) . '.' . $ext;
        }
        
        return $filename;
    }
    
    public static function generateSecurePassword($length = 16) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
        $password = '';
        
        try {
            for ($i = 0; $i < $length; $i++) {
                $password .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } catch (Exception $e) {
            // Fallback to less secure but still usable method
            for ($i = 0; $i < $length; $i++) {
                $password .= $chars[mt_rand(0, strlen($chars) - 1)];
            }
        }
        
        return $password;
    }
    
    private static function isBlacklisted($ip) {
        // Add IP blacklist check implementation
        $blacklist = [
            // Known bad IPs would go here
        ];
        
        return in_array($ip, $blacklist);
    }

    public static function encrypt($data) {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            $data,
            'AES-256-CBC',
            self::$encryptionKey,
            0,
            $iv
        );
        
        return base64_encode($iv . $encrypted);
    }

    public static function decrypt($data) {
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            self::$encryptionKey,
            0,
            $iv
        );
    }

    private static function generateSecureKey() {
        return bin2hex(random_bytes(32));
    }
}

```

# models/Cart.php  
```php
<?php
class Cart {
    private $pdo;
    private $userId;

    public function __construct($pdo, $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }

    public function addItem($productId, $quantity = 1) {
        // Check if item already exists
        $stmt = $this->pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$this->userId, $productId]);
        $item = $stmt->fetch();
        if ($item) {
            // Update quantity
            $newQty = $item['quantity'] + $quantity;
            $update = $this->pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $update->execute([$newQty, $item['id']]);
        } else {
            // Insert new item
            $insert = $this->pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
            $insert->execute([$this->userId, $productId, $quantity]);
        }
    }

    public function updateItem($productId, $quantity) {
        if ($quantity <= 0) {
            $this->removeItem($productId);
            return;
        }
        $stmt = $this->pdo->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$quantity, $this->userId, $productId]);
    }

    public function removeItem($productId) {
        $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$this->userId, $productId]);
    }

    public function getItems() {
        $stmt = $this->pdo->prepare("SELECT ci.product_id, ci.quantity, p.* FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = ?");
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clearCart() {
        $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $stmt->execute([$this->userId]);
    }

    public function mergeSessionCart($sessionCart) {
        if (!is_array($sessionCart)) return;
        foreach ($sessionCart as $productId => $item) {
            // Support both [productId => quantity] and [productId => ['quantity' => x]]
            $quantity = is_array($item) && isset($item['quantity']) ? $item['quantity'] : $item;
            $this->addItem($productId, $quantity);
        }
    }
}

```

# includes/ErrorHandler.php  
```php
<?php

class ErrorHandler {
    private static $logger;
    private static $securityLogger;
    private static $errorCount = [];
    private static $lastErrorTime = [];
    
    public static function init($logger = null) {
        self::$logger = $logger;
        self::$securityLogger = new SecurityLogger();
        
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
        
        // Set up error log rotation
        if (!is_dir(__DIR__ . '/../logs')) {
            mkdir(__DIR__ . '/../logs', 0750, true);
        }
    }
    
    public static function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $error = [
            'type' => self::getErrorType($errno),
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'context' => self::getSecureContext()
        ];
        
        self::trackError($error);
        self::logError($error);
        
        if (ENVIRONMENT === 'development') {
            self::displayError($error);
        } else {
            self::displayProductionError();
        }
        
        return true;
    }
    
    private static function trackError($error) {
        $errorKey = md5($error['file'] . $error['line'] . $error['type']);
        $now = time();
        
        if (!isset(self::$errorCount[$errorKey])) {
            self::$errorCount[$errorKey] = 0;
            self::$lastErrorTime[$errorKey] = $now;
        }
        
        // Reset count if more than an hour has passed
        if ($now - self::$lastErrorTime[$errorKey] > 3600) {
            self::$errorCount[$errorKey] = 0;
        }
        
        self::$errorCount[$errorKey]++;
        self::$lastErrorTime[$errorKey] = $now;
        
        // Alert on high frequency errors
        if (self::$errorCount[$errorKey] > 10) {
            self::$securityLogger->alert("High frequency error detected", [
                'error' => $error,
                'count' => self::$errorCount[$errorKey],
                'timespan' => $now - self::$lastErrorTime[$errorKey]
            ]);
        }
    }
    
    public static function handleException($exception) {
        $error = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => self::getSecureContext()
        ];
        
        if ($exception instanceof SecurityException) {
            self::$securityLogger->critical("Security exception occurred", $error);
        }
        
        self::logError($error);
        
        if (ENVIRONMENT === 'development') {
            self::displayError($error);
        } else {
            self::displayProductionError();
        }
    }
    
    public static function handleFatalError() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::handleError(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        }
    }
    
    private static function getErrorType($errno) {
        switch ($errno) {
            case E_ERROR:
                return 'Fatal Error';
            case E_WARNING:
                return 'Warning';
            case E_PARSE:
                return 'Parse Error';
            case E_NOTICE:
                return 'Notice';
            case E_CORE_ERROR:
                return 'Core Error';
            case E_CORE_WARNING:
                return 'Core Warning';
            case E_COMPILE_ERROR:
                return 'Compile Error';
            case E_COMPILE_WARNING:
                return 'Compile Warning';
            case E_USER_ERROR:
                return 'User Error';
            case E_USER_WARNING:
                return 'User Warning';
            case E_USER_NOTICE:
                return 'User Notice';
            case E_STRICT:
                return 'Strict Notice';
            case E_RECOVERABLE_ERROR:
                return 'Recoverable Error';
            case E_DEPRECATED:
                return 'Deprecated';
            case E_USER_DEPRECATED:
                return 'User Deprecated';
            default:
                return 'Unknown Error';
        }
    }
    
    private static function getSecureContext() {
        $context = [
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Add user context if available
        if (isset($_SESSION['user_id'])) {
            $context['user_id'] = $_SESSION['user_id'];
        }
        
        return $context;
    }
    
    private static function logError($error) {
        $message = sprintf(
            "[%s] %s: %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line']
        );
        
        if (isset($error['trace'])) {
            $message .= "\nStack trace:\n" . $error['trace'];
        }
        
        if (isset($error['context'])) {
            $message .= "\nContext: " . json_encode($error['context']);
        }
        
        if (self::$logger) {
            self::$logger->error($message);
        } else {
            error_log($message);
        }
        
        // Log to security log if it's a security-related error
        if (self::isSecurityError($error)) {
            self::$securityLogger->warning("Security-related error detected", $error);
        }
    }
    
    private static function isSecurityError($error) {
        $securityKeywords = [
            'injection', 'xss', 'csrf', 'auth', 'password',
            'login', 'permission', 'access', 'token', 'ssl',
            'encryption', 'sql', 'database', 'overflow'
        ];
        
        foreach ($securityKeywords as $keyword) {
            if (stripos($error['message'], $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private static function displayError($error) {
        http_response_code(500);
        if (php_sapi_name() === 'cli') {
            echo "\nError: {$error['message']}\n";
            echo "Type: {$error['type']}\n";
            echo "File: {$error['file']}\n";
            echo "Line: {$error['line']}\n";
            if (isset($error['trace'])) {
                echo "\nStack trace:\n{$error['trace']}\n";
            }
        } else {
            ob_start();
            $errorVar = $error; // for compact/extract
            extract(['error' => $errorVar]);
            require __DIR__ . '/../views/error.php';
            ob_end_flush();
        }
    }
    
    private static function displayProductionError() {
        http_response_code(500);
        if (php_sapi_name() === 'cli') {
            echo "\nAn error occurred. Please check the error logs for details.\n";
        } else {
            ob_start();
            require __DIR__ . '/../views/error.php';
            ob_end_flush();
        }
    }
}

class SecurityLogger {
    private $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/../logs/security.log';
    }
    
    public function emergency($message, $context = []) {
        $this->log('EMERGENCY', $message, $context);
    }
    
    public function alert($message, $context = []) {
        $this->log('ALERT', $message, $context);
    }
    
    public function critical($message, $context = []) {
        $this->log('CRITICAL', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    private function log($level, $message, $context) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = json_encode($context);
        $logMessage = "[$timestamp] [$level] $message | $contextStr\n";
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        
        // Alert admins on critical issues
        if (in_array($level, ['EMERGENCY', 'ALERT', 'CRITICAL'])) {
            $this->alertAdmins($level, $message, $context);
        }
    }
    
    private function alertAdmins($level, $message, $context) {
        // Implementation for alerting admins (email, SMS, etc.)
        if (class_exists('EmailService')) {
            $emailService = new EmailService();
            $emailService->sendSecurityAlert($level, $message, $context);
        }
    }
}
```

# js/main.js  
```php
// --- START OF FILE main.js ---

// --- START OF UPDATED main.js ---

// Mobile menu toggle
window.addEventListener('DOMContentLoaded', function() {
    var menuToggle = document.querySelector('.mobile-menu-toggle');
    var navLinks = document.querySelector('.nav-links');
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function() {
            // Toggle navigation visibility
            navLinks.classList.toggle('active');
            // Toggle body class to prevent scrolling when menu is open
            document.body.classList.toggle('menu-open');
            // Toggle icon class (optional, if you want fa-times)
             const icon = menuToggle.querySelector('i');
             if (icon) {
                 icon.classList.toggle('fa-bars');
                 icon.classList.toggle('fa-times');
             }
        });
    }
    // Close menu if clicking outside of it on mobile
    document.addEventListener('click', function(e) {
        if (navLinks && navLinks.classList.contains('active') && menuToggle && !menuToggle.contains(e.target) && !navLinks.contains(e.target)) {
             navLinks.classList.remove('active');
             document.body.classList.remove('menu-open');
             const icon = menuToggle.querySelector('i');
             if (icon) {
                 icon.classList.remove('fa-times');
                 icon.classList.add('fa-bars');
             }
        }
    });
});

// showFlashMessage utility
window.showFlashMessage = function(message, type = 'info') {
    let flashContainer = document.querySelector('.flash-message-container');
    // Create container if it doesn't exist
    if (!flashContainer) {
        flashContainer = document.createElement('div');
        // Apply Tailwind classes for positioning and styling the container
        flashContainer.className = 'flash-message-container fixed top-5 right-5 z-[1100] max-w-sm w-full space-y-2';
        document.body.appendChild(flashContainer);
    }

    const flashDiv = document.createElement('div');
    // Define color mapping using Tailwind classes
    const colorMap = {
        success: 'bg-green-100 border-green-400 text-green-700',
        error: 'bg-red-100 border-red-400 text-red-700',
        info: 'bg-blue-100 border-blue-400 text-blue-700',
        warning: 'bg-yellow-100 border-yellow-400 text-yellow-700'
    };
    // Apply Tailwind classes for the message appearance
    flashDiv.className = `flash-message border px-4 py-3 rounded relative shadow-md flex justify-between items-center transition-opacity duration-300 ease-out opacity-0 ${colorMap[type] || colorMap['info']}`;
    flashDiv.setAttribute('role', 'alert');

    const messageSpan = document.createElement('span');
    messageSpan.className = 'block sm:inline';
    messageSpan.textContent = message;
    flashDiv.appendChild(messageSpan);

    const closeButton = document.createElement('button'); // Use button for accessibility
    closeButton.className = 'ml-4 text-xl leading-none font-semibold hover:text-black';
    closeButton.innerHTML = '&times;';
    closeButton.setAttribute('aria-label', 'Close message');
    closeButton.onclick = () => {
        flashDiv.style.opacity = '0';
        // Remove after transition
        setTimeout(() => flashDiv.remove(), 300);
    };
    flashDiv.appendChild(closeButton);

    // Add to container and fade in
    flashContainer.appendChild(flashDiv);
    // Force reflow before adding opacity class for transition
    void flashDiv.offsetWidth;
    flashDiv.style.opacity = '1';


    // Auto-dismiss timer
    setTimeout(() => {
        if (flashDiv && flashDiv.parentNode) { // Check if it wasn't already closed
             flashDiv.style.opacity = '0';
             setTimeout(() => flashDiv.remove(), 300); // Remove after fade out
        }
    }, 5000); // Keep message for 5 seconds
};


// Global AJAX handlers (Add-to-Cart, Newsletter, etc.)
window.addEventListener('DOMContentLoaded', function() {
    // Add-to-Cart handler (using event delegation on the body)
    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.add-to-cart');
        // Specific exclusion for related products button to prevent double handling if form also submits
        // We now rely solely on the global handler for *all* add-to-cart buttons.
        // const btnRelated = e.target.closest('.add-to-cart-related');

        if (!btn) return; // Exit if the clicked element is not an add-to-cart button or its child

        e.preventDefault(); // Prevent default behavior (like form submission if button is type=submit)
        if (btn.disabled) return; // Prevent multiple clicks while processing

        const productId = btn.dataset.productId;
        const csrfTokenInput = document.getElementById('csrf-token-value');
        const csrfToken = csrfTokenInput?.value;

        // Check if this button is inside the main product detail form to get quantity
        const productForm = btn.closest('#product-detail-add-cart-form');
        let quantity = 1; // Default quantity
        if (productForm) {
            const quantityInput = productForm.querySelector('input[name="quantity"]');
            if (quantityInput) {
                 quantity = parseInt(quantityInput.value) || 1;
            }
        }


        if (!productId || !csrfToken) {
            showFlashMessage('Cannot add to cart. Missing product or security token. Please refresh.', 'error');
            console.error('Add to Cart Error: Missing productId or CSRF token input.');
            return;
        }

        btn.disabled = true;
        const originalText = btn.textContent;
        // Check if the button already contains an icon or just text
        const hasIcon = btn.querySelector('i');
        const loadingHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
        const originalHTML = btn.innerHTML; // Store original HTML if it contains icons

        btn.innerHTML = loadingHTML; // Adding state with spinner

        fetch('index.php?page=cart&action=add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            // Ensure quantity is sent based on whether it's from the main form or a simple button
            body: `product_id=${encodeURIComponent(productId)}&quantity=${encodeURIComponent(quantity)}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => {
            const contentType = response.headers.get("content-type");
            if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                return response.json();
            }
            return response.text().then(text => {
                 console.error('Add to Cart - Non-JSON response:', response.status, text);
                 throw new Error(`Server returned status ${response.status}. Check server logs or network response.`);
            });
        })
        .then(data => {
            if (data.success) {
                showFlashMessage(data.message || 'Product added to cart!', 'success');
                const cartCountSpan = document.querySelector('.cart-count');
                if (cartCountSpan) {
                    cartCountSpan.textContent = data.cart_count || 0;
                    cartCountSpan.style.display = (data.cart_count || 0) > 0 ? 'flex' : 'none';
                }
                 // Optionally change button text briefly or add a checkmark icon
                 btn.innerHTML = '<i class="fas fa-check mr-2"></i>Added!';
                 setTimeout(() => {
                     // Restore original HTML or text
                     btn.innerHTML = originalHTML;
                     // Re-enable button unless out of stock now
                     if (data.stock_status !== 'out_of_stock') {
                        btn.disabled = false;
                     } else {
                         // Keep disabled and update text if out of stock now
                         btn.innerHTML = '<i class="fas fa-times-circle mr-2"></i>Out of Stock';
                         btn.classList.add('btn-disabled'); // Add a class if needed
                     }
                 }, 1500); // Reset after 1.5 seconds

                 // Update mini cart if applicable
                 if (typeof fetchMiniCart === 'function') {
                     fetchMiniCart();
                 }
            } else {
                showFlashMessage(data.message || 'Could not add product to cart.', 'error');
                btn.innerHTML = originalHTML; // Reset button immediately on failure
                btn.disabled = false;
            }
        })
        .catch((error) => {
            console.error('Add to Cart Fetch Error:', error);
            showFlashMessage(error.message || 'Error adding to cart. Please try again.', 'error');
            btn.innerHTML = originalHTML; // Reset button
            btn.disabled = false;
        });
    });

    // Newsletter AJAX handler (if present)
    var newsletterForm = document.getElementById('newsletter-form'); // Main newsletter form
    var newsletterFormFooter = document.getElementById('newsletter-form-footer'); // Footer newsletter form

    function handleNewsletterSubmit(formElement) {
        formElement.addEventListener('submit', function(e) {
            e.preventDefault();
            const emailInput = formElement.querySelector('input[name="email"]');
            const submitButton = formElement.querySelector('button[type="submit"]');
            const csrfTokenInput = formElement.querySelector('input[name="csrf_token"]'); // Get token from specific form

            if (!emailInput || !submitButton || !csrfTokenInput) {
                 console.error("Newsletter form elements missing.");
                 showFlashMessage('An error occurred. Please try again.', 'error');
                 return;
            }

            const email = emailInput.value.trim();
            const csrfToken = csrfTokenInput.value;

            if (!email || !/\S+@\S+\.\S+/.test(email)) {
                showFlashMessage('Please enter a valid email address.', 'error');
                return;
            }
            if (!csrfToken) {
                 showFlashMessage('Security token missing. Please refresh the page.', 'error');
                 return;
            }

            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Subscribing...';

            fetch('index.php?page=newsletter&action=subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `email=${encodeURIComponent(email)}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(res => {
                 const contentType = res.headers.get("content-type");
                 if (res.ok && contentType && contentType.indexOf("application/json") !== -1) {
                     return res.json();
                 }
                 return res.text().then(text => {
                     console.error('Newsletter - Non-JSON response:', res.status, text);
                     throw new Error(`Server returned status ${res.status}.`);
                 });
            })
            .then(data => {
                showFlashMessage(data.message || (data.success ? 'Subscription successful!' : 'Subscription failed.'), data.success ? 'success' : 'error');
                if (data.success) {
                    formElement.reset();
                }
            })
            .catch((error) => {
                console.error('Newsletter Fetch Error:', error);
                showFlashMessage(error.message || 'Error subscribing. Please try again later.', 'error');
            })
            .finally(() => {
                 submitButton.disabled = false;
                 submitButton.textContent = originalButtonText;
            });
        });
    }

    if (newsletterForm) {
        handleNewsletterSubmit(newsletterForm);
    }
    if (newsletterFormFooter) {
        handleNewsletterSubmit(newsletterFormFooter);
    }
});


// --- Page Specific Initializers ---

function initHomePage() {
    // console.log("Initializing Home Page");
    // Particles.js initialization for hero section (if using)
    if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) {
        particlesJS.load('particles-js', '/particles.json', function() {
            // console.log('particles.js loaded - callback');
        });
    }
}

function initProductsPage() {
    // console.log("Initializing Products Page");
    const sortSelect = document.getElementById('sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', this.value);
            url.searchParams.delete('page_num');
            window.location.href = url.toString();
        });
    }

    const applyPriceFilter = document.querySelector('.apply-price-filter');
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');

    if (applyPriceFilter && minPriceInput && maxPriceInput) {
        applyPriceFilter.addEventListener('click', function() {
            const minPrice = minPriceInput.value.trim();
            const maxPrice = maxPriceInput.value.trim();
            const url = new URL(window.location.href);

            if (minPrice) url.searchParams.set('min_price', minPrice);
            else url.searchParams.delete('min_price');

            if (maxPrice) url.searchParams.set('max_price', maxPrice);
            else url.searchParams.delete('max_price');

            url.searchParams.delete('page_num');
            window.location.href = url.toString();
        });
    }
}

function initProductDetailPage() {
    // console.log("Initializing Product Detail Page");
    const mainImage = document.getElementById('mainImage');
    const thumbnails = document.querySelectorAll('.thumbnail-grid img');

    // Make updateMainImage function available globally for inline onclick
    // Note: Using event delegation below is generally preferred over inline onclick
    window.updateMainImage = function(thumbnailElement) {
        if (mainImage && thumbnailElement) {
            mainImage.src = thumbnailElement.dataset.largeImage || thumbnailElement.src;
            mainImage.alt = thumbnailElement.alt.replace('Thumbnail', 'Main view');

            thumbnails.forEach(img => img.parentElement.classList.remove('border-primary', 'border-2')); // Remove active style from parent div
            thumbnailElement.parentElement.classList.add('border-primary', 'border-2'); // Add active style to parent div
        }
    }

    // Set initial active thumbnail based on class (more reliable if structure changes)
    const activeThumbnailDiv = document.querySelector('.thumbnail-grid .border-primary');
    if (activeThumbnailDiv && !mainImage.src.endsWith('placeholder.jpg')) { // Ensure first image isn't placeholder before potentially resetting
        const activeThumbImg = activeThumbnailDiv.querySelector('img');
        // Optional: Set main image source based on initially active thumb if needed
        // if (activeThumbImg) updateMainImage(activeThumbImg);
    } else if (thumbnails.length > 0) {
        // If no thumb is marked active, activate the first one
        thumbnails[0].parentElement.classList.add('border-primary', 'border-2');
    }


    // Quantity Selector Logic
    const quantityInput = document.querySelector('.quantity-selector input[name="quantity"]');
    if (quantityInput) {
        const quantityMax = parseInt(quantityInput.getAttribute('max') || '99');
        const quantityMin = parseInt(quantityInput.getAttribute('min') || '1');

        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                let currentValue = parseInt(quantityInput.value);
                if (isNaN(currentValue)) currentValue = quantityMin;

                if (this.classList.contains('plus')) {
                    if (currentValue < quantityMax) quantityInput.value = currentValue + 1;
                    else quantityInput.value = quantityMax;
                } else if (this.classList.contains('minus')) {
                    if (currentValue > quantityMin) quantityInput.value = currentValue - 1;
                    else quantityInput.value = quantityMin;
                }
            });
        });
         quantityInput.addEventListener('change', function() {
             let value = parseInt(this.value);
             if (isNaN(value) || value < quantityMin) this.value = quantityMin;
             if (value > quantityMax) this.value = quantityMax;
         });
     }


    // Tab Switching Logic
    const tabContainer = document.querySelector('.product-tabs'); // Adjusted selector
    if (tabContainer) {
         const tabBtns = tabContainer.querySelectorAll('.tab-btn');
         const tabPanes = tabContainer.querySelectorAll('.tab-pane');

         tabContainer.addEventListener('click', function(e) {
             const clickedButton = e.target.closest('.tab-btn');
             if (!clickedButton || clickedButton.classList.contains('text-primary')) return; // Check active style

             const tabId = clickedButton.dataset.tab;

             tabBtns.forEach(b => {
                 b.classList.remove('text-primary', 'border-primary');
                 b.classList.add('text-gray-500', 'border-transparent', 'hover:text-primary', 'hover:border-gray-300');
             });
             tabPanes.forEach(pane => pane.classList.remove('active')); // Assuming 'active' class controls visibility

             clickedButton.classList.add('text-primary', 'border-primary');
             clickedButton.classList.remove('text-gray-500', 'border-transparent', 'hover:text-primary', 'hover:border-gray-300');

             const activePane = tabContainer.querySelector(`.tab-pane#${tabId}`);
             if (activePane) {
                 activePane.classList.add('active');
             }
         });

         // Ensure initial active tab's pane is visible on load
         const initialActiveTab = tabContainer.querySelector('.tab-btn.text-primary');
         if (initialActiveTab) {
             const initialTabId = initialActiveTab.dataset.tab;
             const initialActivePane = tabContainer.querySelector(`.tab-pane#${initialTabId}`);
             if (initialActivePane) {
                 initialActivePane.classList.add('active');
             }
         } else {
            // If no tab is active by default, activate the first one
            const firstTab = tabContainer.querySelector('.tab-btn');
            const firstPane = tabContainer.querySelector('.tab-pane');
            if (firstTab && firstPane) {
                 firstTab.classList.add('text-primary', 'border-primary');
                 firstTab.classList.remove('text-gray-500', 'border-transparent', 'hover:text-primary', 'hover:border-gray-300');
                 firstPane.classList.add('active');
            }
         }
         // Add 'active' class styles to style.css if not already present
         // .tab-pane { display: none; }
         // .tab-pane.active { display: block; }
    }

    // Note: The main add-to-cart button now uses the global handler, including quantity.
    // Related product add-to-cart buttons also use the global handler (default quantity 1).
}


function initCartPage() {
    // console.log("Initializing Cart Page");
    const cartForm = document.getElementById('cartForm');
    if (!cartForm) return;

    // --- Helper Functions for Cart ---
    function updateCartTotalsDisplay() {
        let subtotal = 0;
        let itemCount = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            const priceElement = item.querySelector('.item-price');
            const quantityInput = item.querySelector('.item-quantity input');
            const subtotalElement = item.querySelector('.item-subtotal');

            if (priceElement && quantityInput) {
                // Extract price reliably, removing currency symbols etc.
                const priceText = priceElement.dataset.price || priceElement.textContent;
                const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
                const quantity = parseInt(quantityInput.value);

                if (!isNaN(price) && !isNaN(quantity)) {
                    const lineTotal = price * quantity;
                    subtotal += lineTotal;
                    itemCount += quantity;
                    if (subtotalElement) {
                        subtotalElement.textContent = '$' + lineTotal.toFixed(2);
                    }
                }
            }
        });

        // Update summary totals
        const subtotalDisplay = cartForm.querySelector('.cart-summary .summary-row:nth-child(1) span:last-child');
        const totalDisplay = cartForm.querySelector('.cart-summary .summary-row.total span:last-child');
        const shippingDisplay = cartForm.querySelector('.cart-summary .summary-row.shipping span:last-child'); // Assume FREE for now

        if (subtotalDisplay) subtotalDisplay.textContent = '$' + subtotal.toFixed(2);
        if (shippingDisplay) shippingDisplay.textContent = 'FREE'; // Add logic if shipping cost changes
        if (totalDisplay) totalDisplay.textContent = '$' + subtotal.toFixed(2); // Add shipping/tax if applicable

        updateCartCountHeader(itemCount);

        // Handle empty cart state (find elements by class/ID)
        const emptyCartMessage = document.querySelector('.empty-cart'); // Needs an element with this class/ID
        const cartItemsContainer = document.querySelector('.cart-items'); // Container holding items
        const cartSummary = document.querySelector('.cart-summary'); // Summary section
        const cartActions = document.querySelector('.cart-actions'); // Buttons section
        const checkoutButton = document.querySelector('.checkout'); // Checkout button

        if (itemCount === 0) {
            if (cartItemsContainer) cartItemsContainer.classList.add('hidden');
            if (cartSummary) cartSummary.classList.add('hidden');
            if (cartActions) cartActions.classList.add('hidden');
            if (emptyCartMessage) emptyCartMessage.classList.remove('hidden');
        } else {
             if (cartItemsContainer) cartItemsContainer.classList.remove('hidden');
             if (cartSummary) cartSummary.classList.remove('hidden');
             if (cartActions) cartActions.classList.remove('hidden');
            if (emptyCartMessage) emptyCartMessage.classList.add('hidden');
        }

        if (checkoutButton) {
            checkoutButton.classList.toggle('opacity-50', itemCount === 0);
            checkoutButton.classList.toggle('cursor-not-allowed', itemCount === 0);
            if(itemCount === 0) checkoutButton.setAttribute('disabled', 'disabled');
            else checkoutButton.removeAttribute('disabled');
        }
    }

    function updateCartCountHeader(count) {
        const cartCountSpan = document.querySelector('.cart-count');
        if (cartCountSpan) {
            cartCountSpan.textContent = count;
            cartCountSpan.style.display = count > 0 ? 'flex' : 'none';
            cartCountSpan.classList.toggle('animate-pulse', count > 0);
            setTimeout(() => cartCountSpan.classList.remove('animate-pulse'), 1000);
        }
    }

    // --- Event Listeners for Cart Actions ---
    cartForm.addEventListener('click', function(e) {
        const quantityBtn = e.target.closest('.quantity-btn');
        if (quantityBtn) {
            const input = quantityBtn.parentElement.querySelector('input[name^="updates["]'); // Target input by name pattern
            if (!input) return;

            const max = parseInt(input.getAttribute('max') || '99');
            const min = parseInt(input.getAttribute('min') || '1');
            let value = parseInt(input.value);
            if (isNaN(value)) value = min;

            if (quantityBtn.classList.contains('plus')) {
                if (value < max) input.value = value + 1;
                else input.value = max;
            } else if (quantityBtn.classList.contains('minus')) {
                if (value > min) input.value = value - 1;
                else input.value = min;
            }
            // Trigger change event to update totals display immediately
            input.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        const removeItemBtn = e.target.closest('.remove-item');
        if (removeItemBtn) {
            e.preventDefault();
            const cartItemRow = removeItemBtn.closest('.cart-item');
            if (!cartItemRow) return;

            const productId = removeItemBtn.dataset.productId;
            const csrfTokenInput = cartForm.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfTokenInput?.value;


            if (!productId || !csrfToken) {
                showFlashMessage('Error removing item: Missing data.', 'error');
                return;
            }

            if (confirm('Are you sure you want to remove this item?')) {
                cartItemRow.style.opacity = '0';
                cartItemRow.style.transition = 'opacity 0.3s ease-out';
                setTimeout(() => {
                    cartItemRow.remove();
                    updateCartTotalsDisplay(); // Update totals after removing element visually
                }, 300);

                fetch('index.php?page=cart&action=remove', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `product_id=${encodeURIComponent(productId)}&csrf_token=${encodeURIComponent(csrfToken)}`
                })
                .then(response => response.json().catch(() => ({ success: false, message: 'Invalid server response.' })))
                .then(data => {
                    if (data.success) {
                        showFlashMessage(data.message || 'Item removed.', 'success');
                        // Totals already updated visually. Header count updated by totals function.
                        if (typeof fetchMiniCart === 'function') fetchMiniCart();
                    } else {
                        showFlashMessage(data.message || 'Error removing item.', 'error');
                        // Revert optimistic UI update is complex, maybe force reload or rely on update button
                        updateCartTotalsDisplay(); // Re-run totals to ensure consistency
                    }
                })
                .catch(error => {
                    console.error('Error removing item:', error);
                    showFlashMessage('Failed to remove item.', 'error');
                    updateCartTotalsDisplay();
                });
            }
            return;
        }
    });

    cartForm.addEventListener('change', function(e) {
        if (e.target.matches('.item-quantity input')) {
            const input = e.target;
            const max = parseInt(input.getAttribute('max') || '99');
            const min = parseInt(input.getAttribute('min') || '1');
            let value = parseInt(input.value);

            if (isNaN(value) || value < min) input.value = min;
            if (value > max) {
                input.value = max;
                showFlashMessage(`Quantity cannot exceed ${max}.`, 'warning');
            }
            updateCartTotalsDisplay(); // Update totals on manual input change
        }
    });

    // AJAX Update Cart Button
    const updateCartButton = cartForm.querySelector('.update-cart'); // More specific selector
    if (updateCartButton) {
        updateCartButton.addEventListener('click', function(e) {
            e.preventDefault();
            const formData = new FormData(cartForm);
            const submitButton = this;
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';

            fetch('index.php?page=cart&action=update', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json().catch(() => ({ success: false, message: 'Invalid response from server.' })))
            .then(data => {
                if (data.success) {
                    showFlashMessage(data.message || 'Cart updated!', 'success');
                    updateCartTotalsDisplay(); // Recalculate totals visually
                    if (typeof fetchMiniCart === 'function') fetchMiniCart();
                } else {
                     // Display specific stock errors if provided
                    let errorMessage = data.message || 'Failed to update cart.';
                    if (data.errors && data.errors.length > 0) {
                        errorMessage += ' ' + data.errors.join('; ');
                    }
                    showFlashMessage(errorMessage, 'error');
                    // Optionally reload or revert changes if update fails significantly
                    updateCartTotalsDisplay(); // Refresh totals again
                }
            })
            .catch(error => {
                console.error('Error updating cart:', error);
                showFlashMessage('Network error updating cart.', 'error');
                 updateCartTotalsDisplay(); // Refresh totals again
            })
            .finally(() => {
                 submitButton.disabled = false;
                 submitButton.textContent = originalButtonText;
            });
        });
    }

     updateCartTotalsDisplay(); // Initial calculation
}


function initLoginPage() {
    // console.log("Initializing Login Page");
    const form = document.getElementById('loginForm');
    if (!form) return;

    const submitButton = form.querySelector('button[type="submit"]');
    const buttonText = submitButton?.querySelector('.button-text');
    const buttonLoader = submitButton?.querySelector('.button-loader');

    // Password visibility toggle
    form.querySelectorAll('.toggle-password').forEach(toggleBtn => {
        toggleBtn.addEventListener('click', function() {
            const passwordInput = this.previousElementSibling;
            if (passwordInput && passwordInput.type) {
                 const icon = this.querySelector('i');
                 if (passwordInput.type === 'password') {
                     passwordInput.type = 'text';
                     icon?.classList.remove('fa-eye');
                     icon?.classList.add('fa-eye-slash');
                 } else {
                     passwordInput.type = 'password';
                     icon?.classList.remove('fa-eye-slash');
                     icon?.classList.add('fa-eye');
                 }
            }
        });
    });

    // AJAX form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent standard form submission

        const emailInput = form.querySelector('#email');
        const passwordInput = form.querySelector('#password');
        const csrfTokenInput = document.getElementById('csrf-token-value'); // Get global CSRF

        if (!emailInput || !passwordInput || !submitButton || !csrfTokenInput) {
            console.error("Login form elements missing.");
            showFlashMessage('An error occurred submitting the form.', 'error');
            return;
        }
         const email = emailInput.value.trim();
         const password = passwordInput.value;
         const csrfToken = csrfTokenInput.value;


        if (!email || !password) {
             showFlashMessage('Please enter both email and password.', 'warning');
             return;
        }
         if (!csrfToken) {
             showFlashMessage('Security token missing. Please refresh.', 'error');
             return;
         }


        // Show loading state
        if(buttonText) buttonText.classList.add('hidden');
        if(buttonLoader) buttonLoader.classList.remove('hidden');
        submitButton.disabled = true;

        // Prepare data for fetch
        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);
        formData.append('csrf_token', csrfToken);
        // Append remember_me if needed
        const rememberMe = form.querySelector('input[name="remember_me"]');
        if (rememberMe && rememberMe.checked) {
            formData.append('remember_me', '1');
        }


        fetch('index.php?page=login', {
            method: 'POST',
            body: formData
        })
        .then(response => {
             // Check content type before parsing JSON
             const contentType = response.headers.get("content-type");
             if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                 return response.json();
             }
             // Handle non-JSON or error responses
             return response.text().then(text => {
                  console.error("Login error - non-JSON response:", response.status, text);
                  throw new Error(`Login failed. Server responded with status ${response.status}.`);
             });
         })
        .then(data => {
            if (data.success && data.redirect) {
                // Optional: show success message before redirect?
                // showFlashMessage('Login successful! Redirecting...', 'success');
                window.location.href = data.redirect; // Redirect on success
            } else {
                // Show error message from backend
                showFlashMessage(data.error || 'Login failed. Please check your credentials.', 'error');
            }
        })
        .catch(error => {
            console.error('Login Fetch Error:', error);
            showFlashMessage(error.message || 'An error occurred during login. Please try again.', 'error');
        })
        .finally(() => {
            // Hide loading state only if login failed (page redirects on success)
            if (buttonText) buttonText.classList.remove('hidden');
            if (buttonLoader) buttonLoader.classList.add('hidden');
            submitButton.disabled = false;
        });
    });
}


function initRegisterPage() {
    // console.log("Initializing Register Page");
    const form = document.getElementById('registerForm');
    if (!form) return;

    const passwordInput = form.querySelector('#password');
    const confirmPasswordInput = form.querySelector('#confirm_password');
    const submitButton = form.querySelector('button[type="submit"]');
    const buttonText = submitButton?.querySelector('.button-text');
    const buttonLoader = submitButton?.querySelector('.button-loader');

    const requirements = {
        length: { regex: /.{12,}/, element: document.getElementById('req-length') },
        uppercase: { regex: /[A-Z]/, element: document.getElementById('req-uppercase') },
        lowercase: { regex: /[a-z]/, element: document.getElementById('req-lowercase') },
        number: { regex: /[0-9]/, element: document.getElementById('req-number') },
        special: { regex: /[^A-Za-z0-9]/, element: document.getElementById('req-special') }, // More general special char check
        match: { element: document.getElementById('req-match') }
    };

    function validatePassword() {
        if (!passwordInput || !confirmPasswordInput || !submitButton) return true; // Return true if elements missing

        let allMet = true;
        const passwordValue = passwordInput.value;
        const confirmPasswordValue = confirmPasswordInput.value;

        for (const reqKey in requirements) {
            const req = requirements[reqKey];
            if (!req.element) continue;

            let isMet = false;
            if (reqKey === 'match') {
                isMet = passwordValue && passwordValue === confirmPasswordValue;
            } else if (req.regex) {
                isMet = req.regex.test(passwordValue);
            }

            req.element.classList.toggle('met', isMet);
            req.element.classList.toggle('not-met', !isMet);
            const icon = req.element.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-check-circle', isMet);
                icon.classList.toggle('fa-times-circle', !isMet);
                 icon.classList.toggle('text-green-500', isMet); // Add color classes
                 icon.classList.toggle('text-red-500', !isMet);
            }
            if (!isMet) allMet = false;
        }
        submitButton.disabled = !allMet;
        submitButton.classList.toggle('opacity-50', !allMet);
        submitButton.classList.toggle('cursor-not-allowed', !allMet);
        return allMet; // Return validation status
    }

    if (passwordInput && confirmPasswordInput) {
        passwordInput.addEventListener('input', validatePassword);
        confirmPasswordInput.addEventListener('input', validatePassword);
        validatePassword();
    }

    form.querySelectorAll('.toggle-password').forEach(toggleBtn => {
        toggleBtn.addEventListener('click', function() {
            const passwordInputEl = this.previousElementSibling;
            if (passwordInputEl && passwordInputEl.type) {
                 const icon = this.querySelector('i');
                 if (passwordInputEl.type === 'password') {
                     passwordInputEl.type = 'text';
                     icon?.classList.remove('fa-eye'); icon?.classList.add('fa-eye-slash');
                 } else {
                     passwordInputEl.type = 'password';
                     icon?.classList.remove('fa-eye-slash'); icon?.classList.add('fa-eye');
                 }
            }
        });
    });

    // AJAX form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Always prevent standard submission

        if (!validatePassword()) { // Re-validate before submit
            showFlashMessage('Please ensure all password requirements are met.', 'warning');
            passwordInput?.focus(); // Focus on the first password field
            return;
        }

         const nameInput = form.querySelector('#name');
         const emailInput = form.querySelector('#email');
         const csrfTokenInput = document.getElementById('csrf-token-value'); // Global CSRF

        if (!nameInput || !emailInput || !passwordInput || !confirmPasswordInput || !submitButton || !csrfTokenInput) {
            console.error("Register form elements missing.");
            showFlashMessage('An error occurred submitting the form.', 'error');
            return;
        }

        const name = nameInput.value.trim();
        const email = emailInput.value.trim();
        const password = passwordInput.value; // Already validated
        const csrfToken = csrfTokenInput.value;


         if (!name || !email) {
             showFlashMessage('Please fill in all required fields.', 'warning');
             return;
         }
         if (!csrfToken) {
             showFlashMessage('Security token missing. Please refresh.', 'error');
             return;
         }


        // Show loading state
        if(buttonText) buttonText.classList.add('hidden');
        if(buttonLoader) buttonLoader.classList.remove('hidden');
        submitButton.disabled = true;

        // Prepare data for fetch
        const formData = new FormData();
        formData.append('name', name);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('confirm_password', confirmPasswordInput.value); // Send confirmation for backend double check if needed
        formData.append('csrf_token', csrfToken);


        fetch('index.php?page=register', {
            method: 'POST',
            body: formData
        })
        .then(response => {
             const contentType = response.headers.get("content-type");
             if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                 return response.json();
             }
             return response.text().then(text => {
                  console.error("Register error - non-JSON response:", response.status, text);
                  throw new Error(`Registration failed. Server responded with status ${response.status}.`);
             });
         })
        .then(data => {
            if (data.success && data.redirect) {
                 // Controller sets flash message for next page load, just redirect
                 window.location.href = data.redirect;
            } else {
                showFlashMessage(data.error || 'Registration failed. Please check your input and try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Register Fetch Error:', error);
            showFlashMessage(error.message || 'An error occurred during registration. Please try again.', 'error');
        })
        .finally(() => {
            // Hide loading state only if registration failed (page redirects on success)
            if (buttonText) buttonText.classList.remove('hidden');
            if (buttonLoader) buttonLoader.classList.add('hidden');
            // Re-enable button only if it failed, and re-validate password state
            validatePassword();
        });
    });
}


function initForgotPasswordPage() {
    // console.log("Initializing Forgot Password Page");
    const form = document.getElementById('forgotPasswordForm');
    if (!form) return;
    const submitButton = form.querySelector('button[type="submit"]');

    if (form && submitButton) {
        form.addEventListener('submit', function(e) {
             // Keep standard form submission as controller handles redirect
             const email = form.querySelector('#email')?.value.trim();
             if (!email || !/\S+@\S+\.\S+/.test(email)) {
                 showFlashMessage('Please enter a valid email address.', 'error');
                 e.preventDefault();
                 return;
             }

            const buttonText = submitButton.querySelector('.button-text');
            const buttonLoader = submitButton.querySelector('.button-loader');
            if(buttonText) buttonText.classList.add('hidden');
            if(buttonLoader) buttonLoader.classList.remove('hidden');
            submitButton.disabled = true;
            // Allows standard POST
        });
    }
}


function initResetPasswordPage() {
    // console.log("Initializing Reset Password Page");
    const form = document.getElementById('resetPasswordForm');
    if (!form) return;

    const passwordInput = form.querySelector('#password');
    const confirmPasswordInput = form.querySelector('#password_confirm');
    const submitButton = form.querySelector('button[type="submit"]');

    const requirements = {
        length: { regex: /.{12,}/, element: document.getElementById('req-length') },
        uppercase: { regex: /[A-Z]/, element: document.getElementById('req-uppercase') },
        lowercase: { regex: /[a-z]/, element: document.getElementById('req-lowercase') },
        number: { regex: /[0-9]/, element: document.getElementById('req-number') },
        special: { regex: /[^A-Za-z0-9]/, element: document.getElementById('req-special') },
        match: { element: document.getElementById('req-match') }
    };

    function validateResetPassword() {
        if (!passwordInput || !confirmPasswordInput || !submitButton) return true; // Return true if elements missing

        let allMet = true;
        const passwordValue = passwordInput.value;
        const confirmPasswordValue = confirmPasswordInput.value;

        for (const reqKey in requirements) {
            const req = requirements[reqKey];
            if (!req.element) continue;
            let isMet = false;
            if (reqKey === 'match') {
                isMet = passwordValue && passwordValue === confirmPasswordValue;
            } else if (req.regex) {
                isMet = req.regex.test(passwordValue);
            }
            req.element.classList.toggle('met', isMet);
            req.element.classList.toggle('not-met', !isMet);
            const icon = req.element.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-check-circle', isMet);
                icon.classList.toggle('fa-times-circle', !isMet);
                icon.classList.toggle('text-green-500', isMet); // Add color classes
                icon.classList.toggle('text-red-500', !isMet);
            }
            if (!isMet) allMet = false;
        }
        submitButton.disabled = !allMet;
        submitButton.classList.toggle('opacity-50', !allMet);
        submitButton.classList.toggle('cursor-not-allowed', !allMet);
        return allMet; // Return validation status
    }

    if (passwordInput && confirmPasswordInput) {
        passwordInput.addEventListener('input', validateResetPassword);
        confirmPasswordInput.addEventListener('input', validateResetPassword);
        validateResetPassword();
    }

    form.querySelectorAll('.toggle-password').forEach(toggleBtn => {
         toggleBtn.addEventListener('click', function() {
             const passwordInputEl = this.previousElementSibling;
             if (passwordInputEl && passwordInputEl.type) {
                  const icon = this.querySelector('i');
                  if (passwordInputEl.type === 'password') {
                      passwordInputEl.type = 'text';
                      icon?.classList.remove('fa-eye'); icon?.classList.add('fa-eye-slash');
                  } else {
                      passwordInputEl.type = 'password';
                      icon?.classList.remove('fa-eye-slash'); icon?.classList.add('fa-eye');
                  }
             }
         });
     });

    if (form && submitButton) {
        form.addEventListener('submit', function(e) {
            // Keep standard form submission as controller handles redirects
            if (!validateResetPassword()) { // Final validation check
                e.preventDefault();
                showFlashMessage('Please ensure all password requirements are met.', 'error');
                return;
            }
            const buttonText = submitButton.querySelector('.button-text');
            const buttonLoader = submitButton.querySelector('.button-loader');
             if(buttonText) buttonText.classList.add('hidden');
             if(buttonLoader) buttonLoader.classList.remove('hidden');
            submitButton.disabled = true;
            // Allows standard POST
        });
    }
}


function initQuizPage() {
    // console.log("Initializing Quiz Page");
    if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) {
        particlesJS.load('particles-js', '/particles.json');
    }

    const quizForm = document.getElementById('scent-quiz');
    if (quizForm) {
         const optionsContainer = quizForm.querySelector('.quiz-options-container');
         if (optionsContainer) {
             optionsContainer.addEventListener('click', (e) => {
                 const selectedOption = e.target.closest('.quiz-option');
                 if (!selectedOption) return;

                 optionsContainer.querySelectorAll('.quiz-option').forEach(opt => {
                     const innerDiv = opt.querySelector('div');
                     innerDiv?.classList.remove('border-primary', 'bg-primary/10', 'ring-2', 'ring-primary');
                     innerDiv?.classList.add('border-gray-300');
                 });

                 const selectedInnerDiv = selectedOption.querySelector('div');
                 selectedInnerDiv?.classList.add('border-primary', 'bg-primary/10', 'ring-2', 'ring-primary');
                 selectedInnerDiv?.classList.remove('border-gray-300');

                 const hiddenInput = quizForm.querySelector('input[name="mood"]');
                 if (hiddenInput) {
                    hiddenInput.value = selectedOption.dataset.value;
                 }
             });
         }

        quizForm.addEventListener('submit', (e) => {
             const selectedValue = quizForm.querySelector('input[name="mood"]')?.value;
             const selectedRadio = quizForm.querySelector('input[name="mood_radio"]:checked');

             if (!selectedValue && !selectedRadio) {
                 e.preventDefault();
                 showFlashMessage('Please select an option.', 'warning');
                 optionsContainer?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                 return;
             }
              const submitButton = quizForm.querySelector('button[type="submit"]');
              if (submitButton) {
                  submitButton.disabled = true;
                  submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Finding your scent...';
              }
             // Allows standard POST as controller handles rendering/redirect
        });
    }
}


function initQuizResultsPage() {
    // console.log("Initializing Quiz Results Page");
    if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) {
        particlesJS.load('particles-js', '/particles.json');
    }
}


function initAdminQuizAnalyticsPage() {
    // console.log("Initializing Admin Quiz Analytics");
    if (typeof Chart === 'undefined') {
        console.error('Chart.js library is not loaded.');
        return;
    }
    let charts = {};
    const timeRangeSelect = document.getElementById('timeRange');
    const statsContainer = document.getElementById('statsContainer');
    const chartsContainer = document.getElementById('chartsContainer');
    const recommendationsTableBody = document.getElementById('recommendationsTableBody');

    Chart.defaults.font.family = "'Montserrat', sans-serif";
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.7)';
    Chart.defaults.plugins.tooltip.titleFont = { size: 14, weight: 'bold' };
    Chart.defaults.plugins.tooltip.bodyFont = { size: 12 };
    Chart.defaults.plugins.legend.position = 'bottom';

    async function updateAnalytics() {
        const timeRange = timeRangeSelect ? timeRangeSelect.value : '7d';
        statsContainer?.classList.add('opacity-50');
        chartsContainer?.classList.add('opacity-50');
        recommendationsTableBody?.classList.add('opacity-50');

        try {
            // Use correct Admin route: index.php?page=admin&section=quiz_analytics
            const response = await fetch(`index.php?page=admin&section=quiz_analytics&range=${timeRange}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
             if (!response.ok) {
                  const errorText = await response.text();
                  throw new Error(`Network response was not ok (${response.status}): ${errorText}`);
             }
            const data = await response.json();

            // Adjust based on expected JSON structure from QuizController::showAnalytics
            if (data.success) {
                updateStatCards(data.data?.statistics);
                updateCharts(data.data?.preferences);
                updateRecommendationsTable(data.data?.recommendations);
            } else {
                 throw new Error(data.error || 'Failed to fetch analytics data from the server.');
            }
        } catch (error) {
            console.error('Error fetching or processing analytics data:', error);
            showFlashMessage(`Failed to load analytics: ${error.message}`, 'error');
            if (statsContainer) statsContainer.innerHTML = '<p class="text-red-500">Could not load stats.</p>';
            if (chartsContainer) chartsContainer.innerHTML = '<p class="text-red-500">Could not load charts.</p>';
            if (recommendationsTableBody) recommendationsTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-red-500">Could not load recommendations.</td></tr>';
        } finally {
             statsContainer?.classList.remove('opacity-50');
             chartsContainer?.classList.remove('opacity-50');
             recommendationsTableBody?.classList.remove('opacity-50');
        }
    }

    function updateStatCards(stats) {
        if (!stats || !statsContainer) return;
        document.getElementById('totalParticipants').textContent = stats.total_quizzes ?? 'N/A';
        document.getElementById('conversionRate').textContent = stats.conversion_rate != null ? `${stats.conversion_rate}%` : 'N/A';
        document.getElementById('avgCompletionTime').textContent = stats.avg_completion_time != null ? `${stats.avg_completion_time}s` : 'N/A';
    }

    function updateCharts(preferences) {
         if (!preferences || !chartsContainer) return;
         Object.values(charts).forEach(chart => chart?.destroy());
         charts = {};
         const chartColors = ['#1A4D5A', '#A0C1B1', '#D4A76A', '#6B7280', '#F59E0B', '#10B981'];

         // Scent Preference Chart
         const scentCtx = document.getElementById('scentChart')?.getContext('2d');
         if (scentCtx && preferences.scent_types?.length > 0) {
             charts.scent = new Chart(scentCtx, {
                 type: 'doughnut',
                 data: { labels: preferences.scent_types.map(p => p.type), datasets: [{ data: preferences.scent_types.map(p => p.count), backgroundColor: chartColors, hoverOffset: 4 }] },
                 options: { responsive: true, plugins: { legend: { display: true }, title: { display: true, text: 'Scent Type Preferences' } } }
             });
         } else if (scentCtx) { scentCtx.canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No scent preference data.</p>'; }

         // Mood Effect Chart
         const moodCtx = document.getElementById('moodChart')?.getContext('2d');
         if (moodCtx && preferences.mood_effects?.length > 0) {
            charts.mood = new Chart(moodCtx, {
                type: 'bar',
                data: { labels: preferences.mood_effects.map(p => p.effect), datasets: [{ data: preferences.mood_effects.map(p => p.count), backgroundColor: chartColors[1], borderColor: chartColors[1], borderWidth: 1 }] },
                options: { indexAxis: 'y', responsive: true, scales: { x: { beginAtZero: true } }, plugins: { legend: { display: false }, title: { display: true, text: 'Desired Mood Effects' } } }
            });
         } else if (moodCtx) { moodCtx.canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No mood effect data.</p>'; }

         // Daily Completions Chart
          const completionsCtx = document.getElementById('completionsChart')?.getContext('2d');
          if (completionsCtx && preferences.daily_completions?.length > 0) {
             charts.completions = new Chart(completionsCtx, {
                 type: 'line',
                 data: { labels: preferences.daily_completions.map(d => d.date), datasets: [{ data: preferences.daily_completions.map(d => d.count), borderColor: chartColors[0], backgroundColor: 'rgba(26, 77, 90, 0.1)', fill: true, tension: 0.1 }] },
                 options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false }, title: { display: true, text: 'Quiz Completions Over Time' } } }
             });
         } else if (completionsCtx) { completionsCtx.canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No completion data for this period.</p>'; }
    }

    function updateRecommendationsTable(recommendations) {
        if (!recommendations || !recommendationsTableBody) return;
        if (recommendations.length === 0) {
            recommendationsTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-500">No recommendations data.</td></tr>';
            return;
        }
        recommendationsTableBody.innerHTML = recommendations.map(product => `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${product.name || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${product.category || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">${product.recommendation_count ?? 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">${product.conversion_rate != null ? `${product.conversion_rate}%` : 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                    <a href="index.php?page=admin&action=products&view=${product.id}" class="text-indigo-600 hover:text-indigo-900" title="View Details"><i class="fas fa-eye"></i></a>
                </td>
            </tr>`).join('');
    }

    if (timeRangeSelect) {
        timeRangeSelect.addEventListener('change', updateAnalytics);
        updateAnalytics();
    } else {
        console.warn("Time range selector not found. Loading default analytics.");
        updateAnalytics();
    }
}


function initAdminCouponsPage() {
    // console.log("Initializing Admin Coupons Page");
    const createButton = document.getElementById('createCouponBtn');
    const couponFormContainer = document.getElementById('couponFormContainer');
    const couponForm = document.getElementById('couponForm');
    const cancelFormButton = document.getElementById('cancelCouponForm');
    const couponListTable = document.getElementById('couponListTable'); // Table body
    const discountTypeSelect = document.getElementById('discount_type');
    const valueHint = document.getElementById('valueHint');

    function showCouponForm(couponData = null) {
        if (!couponForm || !couponFormContainer) return;
        couponForm.reset();
        couponForm.querySelector('input[name="coupon_id"]').value = '';
        const formTitle = couponFormContainer.querySelector('h2');
        const submitBtn = couponForm.querySelector('button[type="submit"]');

        if (couponData) {
            // Populate form for editing
            couponForm.querySelector('input[name="coupon_id"]').value = couponData.id || '';
            couponForm.querySelector('input[name="code"]').value = couponData.code || '';
            couponForm.querySelector('textarea[name="description"]').value = couponData.description || '';
            couponForm.querySelector('select[name="discount_type"]').value = couponData.discount_type || 'fixed';
            couponForm.querySelector('input[name="value"]').value = couponData.value || '';
            couponForm.querySelector('input[name="min_spend"]').value = couponData.min_spend || '';
            couponForm.querySelector('input[name="usage_limit"]').value = couponData.usage_limit || '';
            if (couponData.valid_from) couponForm.querySelector('input[name="valid_from"]').value = couponData.valid_from.replace(' ', 'T').substring(0, 16);
            if (couponData.valid_to) couponForm.querySelector('input[name="valid_to"]').value = couponData.valid_to.replace(' ', 'T').substring(0, 16);
             couponForm.querySelector('input[name="is_active"][value="1"]').checked = couponData.is_active == 1;
             couponForm.querySelector('input[name="is_active"][value="0"]').checked = couponData.is_active == 0;

             if(formTitle) formTitle.textContent = 'Edit Coupon';
             if(submitBtn) submitBtn.textContent = 'Update Coupon';
        } else {
             if(formTitle) formTitle.textContent = 'Create New Coupon';
             if(submitBtn) submitBtn.textContent = 'Create Coupon';
             // Set default active status for new coupons
             couponForm.querySelector('input[name="is_active"][value="1"]').checked = true;
        }

        updateValueHint();
        couponFormContainer.classList.remove('hidden');
        couponForm.scrollIntoView({ behavior: 'smooth' });
    }

    function hideCouponForm() {
        if (!couponForm || !couponFormContainer) return;
        couponForm.reset();
        couponFormContainer.classList.add('hidden');
    }

    function updateValueHint() {
        if (!discountTypeSelect || !valueHint) return;
        const selectedType = discountTypeSelect.value;
        if (selectedType === 'percentage') valueHint.textContent = 'Enter % (e.g., 10 for 10%). Max 100.';
        else if (selectedType === 'fixed') valueHint.textContent = 'Enter fixed amount (e.g., 15.50 for $15.50).';
        else valueHint.textContent = '';
    }

    // Function to handle AJAX actions for Toggle/Delete
    function handleCouponAction(url, successMessage, errorMessage, confirmationMessage) {
        if (confirmationMessage && !confirm(confirmationMessage)) {
            return; // Abort if user cancels confirmation
        }
        const csrfToken = document.querySelector('input[name="csrf_token_list"]')?.value; // Get CSRF from list area if needed

        fetch(url, {
            method: 'POST', // Use POST for actions that change state
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded' // Send CSRF in body
            },
            body: csrfToken ? `csrf_token=${encodeURIComponent(csrfToken)}` : ''
        })
        .then(response => response.json().catch(() => ({ success: false, message: 'Invalid server response.' })))
        .then(data => {
            if (data.success) {
                showFlashMessage(successMessage, 'success');
                location.reload(); // Reload to see changes
            } else {
                showFlashMessage(data.message || errorMessage, 'error');
            }
        })
        .catch(error => {
            console.error('Coupon action error:', error);
            showFlashMessage('An error occurred. Please try again.', 'error');
        });
    }

    if (createButton) createButton.addEventListener('click', () => showCouponForm());
    if (cancelFormButton) cancelFormButton.addEventListener('click', hideCouponForm);
    if (discountTypeSelect) discountTypeSelect.addEventListener('change', updateValueHint);

    // Initial call for hint
    updateValueHint();

    // Event delegation for table buttons
    if (couponListTable) {
         couponListTable.addEventListener('click', function(e) {
             const editButton = e.target.closest('.edit-coupon');
             const toggleButton = e.target.closest('.toggle-status');
             const deleteButton = e.target.closest('.delete-coupon');

             if (editButton) {
                 e.preventDefault();
                 try {
                     const couponData = JSON.parse(editButton.dataset.coupon || '{}');
                     if (couponData.id) showCouponForm(couponData);
                     else console.error("Could not parse coupon data for editing.");
                 } catch (err) {
                     console.error("Error parsing coupon data:", err);
                     showFlashMessage('Could not load coupon data.', 'error');
                 }
                 return;
             }
             if (toggleButton) {
                 e.preventDefault();
                 const couponId = toggleButton.dataset.couponId;
                 if (couponId) {
                     handleCouponAction(
                         `index.php?page=admin&section=coupons&task=toggle_status&id=${couponId}`,
                         'Status updated.',
                         'Failed to update status.',
                         'Toggle status for this coupon?' // Confirmation message
                     );
                 }
                 return;
             }
             if (deleteButton) {
                 e.preventDefault();
                 const couponId = deleteButton.dataset.couponId;
                 if (couponId) {
                     handleCouponAction(
                         `index.php?page=admin&section=coupons&task=delete&id=${couponId}`,
                         'Coupon deleted.',
                         'Failed to delete coupon.',
                         'Permanently delete this coupon?' // Confirmation message
                     );
                 }
                 return;
             }
         });
    }

     // Handle form submission (standard POST, controller handles redirect)
     if (couponForm) {
         couponForm.addEventListener('submit', function() {
             const submitBtn = couponForm.querySelector('button[type="submit"]');
             if (submitBtn) {
                 submitBtn.disabled = true;
                 submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
             }
         });
     }
}


// --- Main DOMContentLoaded Listener ---
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS globally
    if (typeof AOS !== 'undefined') {
        AOS.init({ duration: 800, offset: 120, once: true });
        // console.log('AOS Initialized Globally');
    } else {
        console.warn('AOS library not loaded.');
    }

    const body = document.body;
    const pageInitializers = {
        'page-home': initHomePage,
        'page-products': initProductsPage,
        'page-product-detail': initProductDetailPage,
        'page-cart': initCartPage,
        'page-login': initLoginPage,
        'page-register': initRegisterPage,
        'page-forgot-password': initForgotPasswordPage,
        'page-reset-password': initResetPasswordPage,
        'page-quiz': initQuizPage,
        'page-quiz-results': initQuizResultsPage,
        'page-admin-quiz-analytics': initAdminQuizAnalyticsPage,
        'page-admin-coupons': initAdminCouponsPage,
         // Add other page classes and their init functions here
         // 'page-account-dashboard': initAccountDashboardPage, // Example if needed
         // 'page-account-profile': initAccountProfilePage, // Example if needed
    };

    let initialized = false;
    for (const pageClass in pageInitializers) {
        if (body.classList.contains(pageClass)) {
            pageInitializers[pageClass]();
            initialized = true;
            // console.log(`Initialized: ${pageClass}`); // For debugging
            break; // Assume only one main page class per body
        }
    }
    // if (!initialized) {
    //     console.log('No specific page initialization class found on body.');
    // }

    // Fetch mini cart content on initial load (if element exists)
    if (document.getElementById('mini-cart-content') && typeof fetchMiniCart === 'function') {
         fetchMiniCart();
    }
});


// --- Mini Cart AJAX Update Function ---
function fetchMiniCart() {
    const miniCartContent = document.getElementById('mini-cart-content');
    if (!miniCartContent) return;

    // Optional: Show a subtle loading state inside the dropdown
    // miniCartContent.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>';

    fetch('index.php?page=cart&action=mini', {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => {
        if (!response.ok) throw new Error(`Network response was not ok (${response.status})`);
        return response.json();
    })
    .then(data => {
        // Renders items or empty message based on data structure from CartController::mini
        if (data.items && data.items.length > 0) {
            let html = '<ul class="divide-y divide-gray-200 max-h-60 overflow-y-auto">';
             data.items.forEach(item => {
                 const imageUrl = item.product?.image || '/images/placeholder.jpg';
                 const productName = item.product?.name || 'Unknown';
                 const productPrice = parseFloat(item.product?.price || 0);
                 const quantity = parseInt(item.quantity || 0);
                 const lineTotal = productPrice * quantity;
                 html += `
                    <li class="flex items-center gap-3 py-3 px-1">
                         <img src="${imageUrl}" alt="${productName}" class="w-12 h-12 object-cover rounded border flex-shrink-0">
                         <div class="flex-1 min-w-0">
                             <a href="index.php?page=product&id=${item.product?.id}" class="font-medium text-sm text-gray-800 hover:text-primary truncate block" title="${productName}">${productName}</a>
                             <div class="text-xs text-gray-500">Qty: ${quantity} &times; $${productPrice.toFixed(2)}</div>
                         </div>
                         <div class="text-sm font-semibold text-gray-700">$${lineTotal.toFixed(2)}</div>
                     </li>`;
            });
            html += '</ul>';
            const subtotal = parseFloat(data.subtotal || 0);
            html += `<div class="border-t border-gray-200 pt-4 mt-4">
                 <div class="flex justify-between items-center mb-4">
                     <span class="font-semibold text-gray-700">Subtotal:</span>
                     <span class="font-bold text-primary text-lg">$${subtotal.toFixed(2)}</span>
                 </div>
                 <div class="flex flex-col gap-2">
                     <a href="index.php?page=cart" class="btn btn-secondary w-full text-center">View Cart</a>
                     <a href="index.php?page=checkout" class="btn btn-primary w-full text-center ${subtotal === 0 ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''}">Checkout</a>
                 </div>
             </div>`;
            miniCartContent.innerHTML = html;
        } else {
            miniCartContent.innerHTML = '<div class="text-center text-gray-500 py-6 px-4">Your cart is empty.</div>';
        }
    })
    .catch(error => {
        console.error('Error fetching mini cart:', error);
        miniCartContent.innerHTML = '<div class="text-center text-red-500 py-6 px-4">Could not load cart.</div>';
    });
}

// --- END OF UPDATED main.js ---

```

# controllers/PaymentController.php  
```php
<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

class PaymentController extends BaseController {
    private $stripe;
    private $webhookSecret;
    
    public function __construct($pdo = null) {
        parent::__construct($pdo);
        $this->stripe = new \Stripe\StripeClient(STRIPE_SECRET_KEY);
        $this->webhookSecret = STRIPE_WEBHOOK_SECRET;
    }
    
    public function createPaymentIntent($amount, $currency = 'usd') {
        try {
            // Validate input
            $amount = $this->validateInput($amount, 'float');
            $currency = $this->validateInput($currency, 'string');
            
            if ($amount <= 0) {
                throw new Exception('Invalid payment amount');
            }
            
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int)($amount * 100), // Convert to cents
                'currency' => strtolower($currency),
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'user_id' => $this->getUserId() ?? 'guest',
                    'ip_address' => $_SERVER['REMOTE_ADDR']
                ]
            ]);
            
            return [
                'success' => true,
                'clientSecret' => $paymentIntent->client_secret
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe API Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment processing failed'
            ];
        } catch (Exception $e) {
            error_log("Payment Intent Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Invalid payment request'
            ];
        }
    }
    
    public function processPayment($orderId) {
        try {
            $this->validateCSRF();
            $orderId = $this->validateInput($orderId, 'int');
            
            if (!$orderId) {
                throw new Exception('Invalid order ID');
            }
            
            $this->beginTransaction();
            
            // Get order details
            $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            // Verify order belongs to current user
            if ($order['user_id'] !== $this->getUserId()) {
                throw new Exception('Unauthorized access to order');
            }
            
            // Create payment intent
            $result = $this->createPaymentIntent($order['total_amount']);
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Update order with payment intent
            $stmt = $this->pdo->prepare("
                UPDATE orders 
                SET payment_intent_id = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$result['clientSecret'], $orderId]);
            
            $this->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Payment Processing Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment processing failed'
            ];
        }
    }
    
    public function handleWebhook() {
        try {
            $payload = @file_get_contents('php://input');
            $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'];
            
            // Verify webhook signature
            try {
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $sigHeader,
                    $this->webhookSecret
                );
            } catch (\UnexpectedValueException $e) {
                throw new Exception('Invalid payload');
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                throw new Exception('Invalid signature');
            }
            
            $this->beginTransaction();
            
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handleSuccessfulPayment($event->data->object);
                    break;
                    
                case 'payment_intent.payment_failed':
                    $this->handleFailedPayment($event->data->object);
                    break;
                    
                case 'charge.dispute.created':
                    $this->handleDisputeCreated($event->data->object);
                    break;
                    
                case 'charge.refunded':
                    $this->handleRefund($event->data->object);
                    break;
            }
            
            $this->commit();
            return $this->jsonResponse(['success' => true]);
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Webhook Error: " . $e->getMessage());
            return $this->jsonResponse(
                ['success' => false, 'error' => $e->getMessage()],
                400
            );
        }
    }
    
    private function handleSuccessfulPayment($paymentIntent) {
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET status = 'paid', 
                payment_status = 'completed',
                paid_at = NOW(),
                updated_at = NOW()
            WHERE payment_intent_id = ?
        ");
        
        if (!$stmt->execute([$paymentIntent->client_secret])) {
            throw new Exception('Failed to update order payment status');
        }
        
        // Get order details for notification
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.email 
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.payment_intent_id = ?
        ");
        $stmt->execute([$paymentIntent->client_secret]);
        $order = $stmt->fetch();
        
        if ($order) {
            // Send payment confirmation email
            $this->emailService->sendPaymentConfirmation($order);
        }
    }
    
    private function handleFailedPayment($paymentIntent) {
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET status = 'payment_failed',
                payment_status = 'failed',
                updated_at = NOW()
            WHERE payment_intent_id = ?
        ");
        
        if (!$stmt->execute([$paymentIntent->client_secret])) {
            throw new Exception('Failed to update order payment status');
        }
        
        // Get order details for notification
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.email 
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.payment_intent_id = ?
        ");
        $stmt->execute([$paymentIntent->client_secret]);
        $order = $stmt->fetch();
        
        if ($order) {
            // Send payment failed notification
            $this->emailService->sendPaymentFailedNotification($order);
        }
    }
    
    private function handleDisputeCreated($dispute) {
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET status = 'disputed',
                dispute_id = ?,
                disputed_at = NOW(),
                updated_at = NOW()
            WHERE payment_intent_id = ?
        ");
        
        if (!$stmt->execute([$dispute->id, $dispute->payment_intent])) {
            throw new Exception('Failed to update order dispute status');
        }
        
        // Log dispute details for review
        error_log("Dispute created for payment: " . $dispute->payment_intent);
    }
    
    private function handleRefund($charge) {
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET status = 'refunded',
                refund_id = ?,
                refunded_at = NOW(),
                updated_at = NOW()
            WHERE payment_intent_id = ?
        ");
        
        if (!$stmt->execute([$charge->refunds->data[0]->id, $charge->payment_intent])) {
            throw new Exception('Failed to update order refund status');
        }
        
        // Get order details for notification
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.email 
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.payment_intent_id = ?
        ");
        $stmt->execute([$charge->payment_intent]);
        $order = $stmt->fetch();
        
        if ($order) {
            // Send refund confirmation email
            $this->emailService->sendRefundConfirmation($order);
        }
    }
}
```

# controllers/TaxController.php  
```php
<?php
require_once __DIR__ . '/BaseController.php';

class TaxController extends BaseController {
    private $cache = [];
    
    public function calculateTax($subtotal, $country, $state = null) {
        try {
            $subtotal = $this->validateInput($subtotal, 'float');
            $country = $this->validateInput($country, 'string');
            $state = $this->validateInput($state, 'string');
            
            if (!$subtotal || !$country) {
                throw new Exception('Invalid tax calculation parameters');
            }
            
            // Check cache first
            $cacheKey = "{$country}_{$state}";
            if (isset($this->cache[$cacheKey])) {
                return round($subtotal * $this->cache[$cacheKey], 2);
            }
            
            // Get tax rate from database
            $stmt = $this->pdo->prepare("
                SELECT rate 
                FROM tax_rates 
                WHERE country_code = ? 
                AND (state_code = ? OR state_code IS NULL)
                AND is_active = TRUE
                AND start_date <= NOW()
                AND (end_date IS NULL OR end_date > NOW())
                ORDER BY state_code IS NULL
                LIMIT 1
            ");
            $stmt->execute([$country, $state]);
            $result = $stmt->fetch();
            
            $rate = $result ? $result['rate'] : 0;
            $this->cache[$cacheKey] = $rate;
            
            return round($subtotal * $rate, 2);
            
        } catch (Exception $e) {
            error_log("Tax calculation error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTaxRate($country, $state = null) {
        try {
            $country = $this->validateInput($country, 'string');
            $state = $this->validateInput($state, 'string');
            
            if (!$country) return 0;
            
            // Check cache first
            $cacheKey = "{$country}_{$state}";
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT rate 
                FROM tax_rates 
                WHERE country_code = ? 
                AND (state_code = ? OR state_code IS NULL)
                AND is_active = TRUE
                AND start_date <= NOW()
                AND (end_date IS NULL OR end_date > NOW())
                ORDER BY state_code IS NULL
                LIMIT 1
            ");
            $stmt->execute([$country, $state]);
            $result = $stmt->fetch();
            
            $rate = $result ? $result['rate'] : 0;
            $this->cache[$cacheKey] = $rate;
            
            return $rate;
            
        } catch (Exception $e) {
            error_log("Tax rate lookup error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function formatTaxRate($rate) {
        return number_format($rate * 100, 2) . '%';
    }
    
    public function getAllTaxRates() {
        try {
            $this->requireAdmin();
            
            $stmt = $this->pdo->query("
                SELECT 
                    tr.*,
                    COUNT(th.id) as change_count,
                    MAX(th.created_at) as last_modified
                FROM tax_rates tr
                LEFT JOIN tax_rate_history th ON tr.id = th.tax_rate_id
                GROUP BY tr.id
                ORDER BY tr.country_code, tr.state_code
            ");
            
            return $this->jsonResponse([
                'success' => true,
                'rates' => $stmt->fetchAll()
            ]);
            
        } catch (Exception $e) {
            error_log("Error fetching tax rates: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve tax rates'
            ], 500);
        }
    }
    
    public function updateTaxRate() {
        try {
            $this->requireAdmin();
            $this->validateCSRF();
            
            $data = [
                'country_code' => $this->validateInput($_POST['country_code'], 'string'),
                'state_code' => $this->validateInput($_POST['state_code'] ?? null, 'string'),
                'rate' => $this->validateInput($_POST['rate'], 'float'),
                'start_date' => $this->validateInput($_POST['start_date'] ?? date('Y-m-d'), 'string'),
                'end_date' => $this->validateInput($_POST['end_date'] ?? null, 'string'),
                'is_active' => isset($_POST['is_active']) ? true : false
            ];
            
            if (!$data['country_code'] || $data['rate'] < 0) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Invalid tax rate data'
                ], 400);
            }
            
            $this->beginTransaction();
            
            // Get existing rate if any
            $stmt = $this->pdo->prepare("
                SELECT id, rate 
                FROM tax_rates 
                WHERE country_code = ? 
                AND (state_code = ? OR (state_code IS NULL AND ? IS NULL))
            ");
            $stmt->execute([
                $data['country_code'],
                $data['state_code'],
                $data['state_code']
            ]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing rate
                $stmt = $this->pdo->prepare("
                    UPDATE tax_rates 
                    SET rate = ?,
                        start_date = ?,
                        end_date = ?,
                        is_active = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['rate'],
                    $data['start_date'],
                    $data['end_date'],
                    $data['is_active'],
                    $existing['id']
                ]);
                
                // Log the change
                if ($existing['rate'] != $data['rate']) {
                    $this->logRateChange(
                        $existing['id'],
                        $existing['rate'],
                        $data['rate']
                    );
                }
            } else {
                // Insert new rate
                $stmt = $this->pdo->prepare("
                    INSERT INTO tax_rates (
                        country_code,
                        state_code,
                        rate,
                        start_date,
                        end_date,
                        is_active,
                        created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['country_code'],
                    $data['state_code'],
                    $data['rate'],
                    $data['start_date'],
                    $data['end_date'],
                    $data['is_active'],
                    $this->getUserId()
                ]);
                
                $rateId = $this->pdo->lastInsertId();
                $this->logRateChange($rateId, 0, $data['rate']);
            }
            
            // Clear cache for this region
            $cacheKey = "{$data['country_code']}_{$data['state_code']}";
            unset($this->cache[$cacheKey]);
            
            $this->commit();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Tax rate updated successfully'
            ]);
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Tax rate update error: " . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to update tax rate'
            ], 500);
        }
    }
    
    private function logRateChange($rateId, $oldRate, $newRate) {
        $stmt = $this->pdo->prepare("
            INSERT INTO tax_rate_history (
                tax_rate_id,
                old_rate,
                new_rate,
                changed_by
            ) VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $rateId,
            $oldRate,
            $newRate,
            $this->getUserId()
        ]);
    }
    
    public function getTaxRateHistory($rateId) {
        try {
            $this->requireAdmin();
            
            $rateId = $this->validateInput($rateId, 'int');
            if (!$rateId) {
                throw new Exception('Invalid tax rate ID');
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    th.*,
                    u.name as changed_by_name
                FROM tax_rate_history th
                LEFT JOIN users u ON th.changed_by = u.id
                WHERE th.tax_rate_id = ?
                ORDER BY th.created_at DESC
            ");
            $stmt->execute([$rateId]);
            
            return $this->jsonResponse([
                'success' => true,
                'history' => $stmt->fetchAll()
            ]);
            
        } catch (Exception $e) {
            error_log("Error fetching tax rate history: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve tax rate history'
            ], 500);
        }
    }
}
```

# controllers/InventoryController.php  
```php
<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../includes/EmailService.php';

class InventoryController extends BaseController {
    private $emailService;
    private $alertThreshold = 5; // Alert when stock drops below this percentage of initial stock
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->emailService = new EmailService();
    }
    
    public function updateStock($productId, $quantity, $type = 'adjustment', $referenceId = null, $notes = null) {
        try {
            $this->requireAdmin();
            $this->validateCSRF();
            
            // Validate inputs
            $productId = $this->validateInput($productId, 'int');
            $quantity = $this->validateInput($quantity, 'float');
            $type = $this->validateInput($type, 'string');
            $referenceId = $this->validateInput($referenceId, 'int');
            $notes = $this->validateInput($notes, 'string');
            
            if (!$productId || !$quantity) {
                throw new Exception('Invalid product or quantity');
            }
            
            $this->beginTransaction();
            
            // Get current stock with locking
            $stmt = $this->pdo->prepare("
                SELECT id, name, stock_quantity, initial_stock, 
                       backorder_allowed, low_stock_threshold
                FROM products 
                WHERE id = ? 
                FOR UPDATE
            ");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product) {
                throw new Exception('Product not found');
            }
            
            // Check if we have enough stock for reduction
            if ($quantity < 0 && !$product['backorder_allowed'] && 
                ($product['stock_quantity'] + $quantity) < 0) {
                throw new Exception('Insufficient stock');
            }
            
            // Update product stock
            $stmt = $this->pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity + ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$quantity, $productId]);
            
            // Record movement with audit trail
            $stmt = $this->pdo->prepare("
                INSERT INTO inventory_movements (
                    product_id, 
                    quantity_change, 
                    previous_quantity,
                    new_quantity,
                    type, 
                    reference_id, 
                    notes, 
                    created_by,
                    ip_address
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $productId,
                $quantity,
                $product['stock_quantity'],
                $product['stock_quantity'] + $quantity,
                $type,
                $referenceId,
                $notes,
                $this->getUserId(),
                $_SERVER['REMOTE_ADDR']
            ]);
            
            // Check stock levels and send alerts if needed
            $newQuantity = $product['stock_quantity'] + $quantity;
            $this->checkStockLevels($product, $newQuantity);
            
            $this->commit();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Stock updated successfully',
                'new_quantity' => $newQuantity
            ]);
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Stock update error: " . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    private function checkStockLevels($product, $newQuantity) {
        // Check if stock is below threshold
        if ($newQuantity <= $product['low_stock_threshold']) {
            // Log low stock alert
            error_log("Low stock alert: {$product['name']} has only {$newQuantity} units left");
            
            // Calculate stock percentage
            $stockPercentage = $product['initial_stock'] > 0 
                ? ($newQuantity / $product['initial_stock']) * 100 
                : 0;
            
            // Send alert if stock is critically low
            if ($stockPercentage <= $this->alertThreshold) {
                $this->emailService->sendLowStockAlert(
                    $product['name'],
                    $newQuantity,
                    $product['initial_stock'],
                    $stockPercentage
                );
            }
        }
    }
    
    public function getInventoryMovements($productId, $startDate = null, $endDate = null, $type = null) {
        try {
            $this->requireAdmin();
            
            $productId = $this->validateInput($productId, 'int');
            $startDate = $this->validateInput($startDate, 'string');
            $endDate = $this->validateInput($endDate, 'string');
            $type = $this->validateInput($type, 'string');
            
            if (!$productId) {
                throw new Exception('Invalid product ID');
            }
            
            $params = [$productId];
            $sql = "
                SELECT 
                    m.*,
                    u.name as user_name,
                    p.name as product_name
                FROM inventory_movements m
                LEFT JOIN users u ON m.created_by = u.id
                JOIN products p ON m.product_id = p.id
                WHERE m.product_id = ?
            ";
            
            if ($startDate) {
                $sql .= " AND m.created_at >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND m.created_at <= ?";
                $params[] = $endDate;
            }
            
            if ($type) {
                $sql .= " AND m.type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY m.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $this->jsonResponse([
                'success' => true,
                'movements' => $stmt->fetchAll()
            ]);
            
        } catch (Exception $e) {
            error_log("Error fetching inventory movements: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve inventory movements'
            ], 500);
        }
    }
    
    public function getStockReport($categoryId = null) {
        try {
            $this->requireAdmin();
            
            $params = [];
            $sql = "
                SELECT 
                    p.id,
                    p.name,
                    p.stock_quantity,
                    p.initial_stock,
                    p.low_stock_threshold,
                    p.backorder_allowed,
                    COALESCE(SUM(CASE WHEN m.type = 'sale' THEN ABS(m.quantity_change) ELSE 0 END), 0) as total_sales,
                    COALESCE(SUM(CASE WHEN m.type = 'return' THEN m.quantity_change ELSE 0 END), 0) as total_returns,
                    CASE 
                        WHEN p.initial_stock > 0 THEN (p.stock_quantity / p.initial_stock) * 100 
                        ELSE 0 
                    END as stock_percentage
                FROM products p
                LEFT JOIN inventory_movements m ON p.id = m.product_id
            ";
            
            if ($categoryId) {
                $sql .= " WHERE p.category_id = ?";
                $params[] = $this->validateInput($categoryId, 'int');
            }
            
            $sql .= " GROUP BY p.id ORDER BY stock_percentage ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $this->jsonResponse([
                'success' => true,
                'report' => $stmt->fetchAll()
            ]);
            
        } catch (Exception $e) {
            error_log("Error generating stock report: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to generate stock report'
            ], 500);
        }
    }
    
    public function adjustStockThreshold($productId, $threshold) {
        try {
            $this->requireAdmin();
            $this->validateCSRF();
            
            $productId = $this->validateInput($productId, 'int');
            $threshold = $this->validateInput($threshold, 'int');
            
            if (!$productId || $threshold < 0) {
                throw new Exception('Invalid product ID or threshold');
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE products 
                SET low_stock_threshold = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$threshold, $productId]);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Stock threshold updated successfully'
            ]);
            
        } catch (Exception $e) {
            error_log("Error updating stock threshold: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to update stock threshold'
            ], 500);
        }
    }
}
```

# controllers/CouponController.php  
```php
<?php
require_once __DIR__ . '/BaseController.php';

class CouponController extends BaseController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function validateCoupon($code, $subtotal) {
        try {
            $this->validateRateLimit('coupon_validate');
            $this->validateCSRF();
            
            $code = $this->validateInput($code, 'string');
            $subtotal = $this->validateInput($subtotal, 'float');
            $userId = $this->getUserId();
            
            if (!$code || $subtotal <= 0) {
                return $this->jsonResponse([
                    'valid' => false,
                    'message' => 'Invalid coupon or order amount'
                ], 400);
            }
            
            $stmt = $this->pdo->prepare("
                SELECT * FROM coupons 
                WHERE code = ? 
                AND is_active = TRUE
                AND (start_date IS NULL OR start_date <= NOW())
                AND (end_date IS NULL OR end_date >= NOW())
                AND (usage_limit IS NULL OR usage_count < usage_limit)
                AND min_purchase_amount <= ?
            ");
            $stmt->execute([$code, $subtotal]);
            $coupon = $stmt->fetch();
            
            if (!$coupon) {
                return $this->jsonResponse([
                    'valid' => false,
                    'message' => 'Invalid or expired coupon code'
                ]);
            }
            
            // Check if user has already used this coupon
            if ($userId) {
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) FROM coupon_usage 
                    WHERE coupon_id = ? AND user_id = ?
                ");
                $stmt->execute([$coupon['id'], $userId]);
                $usageCount = $stmt->fetchColumn();
                if ($usageCount > 0) {
                    return $this->jsonResponse([
                        'valid' => false,
                        'message' => 'You have already used this coupon'
                    ]);
                }
            }
            
            // Calculate discount
            $discountAmount = $this->calculateDiscount($coupon, $subtotal);
            
            return $this->jsonResponse([
                'valid' => true,
                'coupon' => $coupon,
                'discount_amount' => $discountAmount,
                'message' => 'Coupon applied successfully'
            ]);
            
        } catch (Exception $e) {
            error_log("Coupon validation error: " . $e->getMessage());
            return $this->jsonResponse([
                'valid' => false,
                'message' => 'An error occurred while validating the coupon'
            ], 500);
        }
    }
    
    private function calculateDiscount($coupon, $subtotal) {
        $discountAmount = 0;
        
        if ($coupon['discount_type'] === 'percentage') {
            $discountAmount = $subtotal * ($coupon['discount_value'] / 100);
        } else { // fixed amount
            $discountAmount = $coupon['discount_value'];
        }
        
        // Apply maximum discount limit if set
        if ($coupon['max_discount_amount'] !== null) {
            $discountAmount = min($discountAmount, $coupon['max_discount_amount']);
        }
        
        return round($discountAmount, 2);
    }
    
    public function applyCoupon($couponId, $orderId, $discountAmount) {
        try {
            $this->validateCSRF();
            $userId = $this->getUserId();
            
            $couponId = $this->validateInput($couponId, 'int');
            $orderId = $this->validateInput($orderId, 'int');
            $discountAmount = $this->validateInput($discountAmount, 'float');
            
            if (!$couponId || !$orderId || $discountAmount <= 0) {
                throw new Exception('Invalid coupon application data');
            }
            
            $this->beginTransaction();
            
            // Verify coupon is still valid
            $stmt = $this->pdo->prepare("
                SELECT * FROM coupons 
                WHERE id = ? 
                AND is_active = TRUE
                AND (usage_limit IS NULL OR usage_count < usage_limit)
            ");
            $stmt->execute([$couponId]);
            if (!$stmt->fetch()) {
                throw new Exception('Coupon is no longer valid');
            }
            
            // Record coupon usage
            $stmt = $this->pdo->prepare("
                INSERT INTO coupon_usage (coupon_id, order_id, user_id, discount_amount)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$couponId, $orderId, $userId, $discountAmount]);
            
            // Update coupon usage count
            $stmt = $this->pdo->prepare("
                UPDATE coupons 
                SET usage_count = usage_count + 1,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$couponId]);
            
            $this->commit();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Coupon applied successfully'
            ]);
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Coupon application error: " . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to apply coupon'
            ], 500);
        }
    }
    
    public function getAllCoupons() {
        try {
            $this->requireAdmin();
            
            $stmt = $this->pdo->query("
                SELECT 
                    c.*,
                    COUNT(cu.id) as total_uses,
                    SUM(cu.discount_amount) as total_discount_given
                FROM coupons c
                LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id
                GROUP BY c.id
                ORDER BY c.created_at DESC
            ");
            
            return $this->jsonResponse([
                'success' => true,
                'coupons' => $stmt->fetchAll()
            ]);
            
        } catch (Exception $e) {
            error_log("Error fetching coupons: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve coupons'
            ], 500);
        }
    }
    
    public function createCoupon() {
        try {
            $this->requireAdmin();
            $this->validateCSRF();
            
            $data = [
                'code' => $this->validateInput($_POST['code'], 'string'),
                'description' => $this->validateInput($_POST['description'], 'string'),
                'discount_type' => $this->validateInput($_POST['discount_type'], 'string'),
                'discount_value' => $this->validateInput($_POST['discount_value'], 'float'),
                'min_purchase_amount' => $this->validateInput($_POST['min_purchase_amount'] ?? 0, 'float'),
                'max_discount_amount' => $this->validateInput($_POST['max_discount_amount'] ?? null, 'float'),
                'start_date' => $this->validateInput($_POST['start_date'] ?? null, 'string'),
                'end_date' => $this->validateInput($_POST['end_date'] ?? null, 'string'),
                'usage_limit' => $this->validateInput($_POST['usage_limit'] ?? null, 'int'),
                'is_active' => isset($_POST['is_active']) ? true : false
            ];
            
            // Validate required fields
            if (!$data['code'] || !$data['discount_type'] || $data['discount_value'] <= 0) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Missing required fields'
                ], 400);
            }
            
            // Validate discount type
            if (!in_array($data['discount_type'], ['percentage', 'fixed'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Invalid discount type'
                ], 400);
            }
            
            $this->beginTransaction();
            
            // Check if code already exists
            $stmt = $this->pdo->prepare("SELECT id FROM coupons WHERE code = ?");
            $stmt->execute([$data['code']]);
            if ($stmt->fetch()) {
                throw new Exception('Coupon code already exists');
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO coupons (
                    code, description, discount_type, discount_value,
                    min_purchase_amount, max_discount_amount,
                    start_date, end_date, usage_limit, is_active,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['code'],
                $data['description'],
                $data['discount_type'],
                $data['discount_value'],
                $data['min_purchase_amount'],
                $data['max_discount_amount'],
                $data['start_date'],
                $data['end_date'],
                $data['usage_limit'],
                $data['is_active'],
                $this->getUserId()
            ]);
            
            $this->commit();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Coupon created successfully'
            ]);
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Coupon creation error: " . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to create coupon'
            ], 500);
        }
    }
}
```

