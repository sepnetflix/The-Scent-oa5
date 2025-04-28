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
    private PDO $pdo; // Use type hint
    private int $userId; // Use type hint

    // Constructor accepts PDO connection and User ID
    public function __construct(PDO $pdo, int $userId) { // Use type hints
        $this->pdo = $pdo;
        $this->userId = $userId;
    }

    /**
     * Adds an item to the user's cart or updates quantity if it exists.
     *
     * @param int $productId The ID of the product.
     * @param int $quantity The quantity to add (default: 1).
     * @return bool True on success, false on failure.
     */
    public function addItem(int $productId, int $quantity = 1): bool {
        try {
            // Check if item already exists
            $stmt = $this->pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$this->userId, $productId]);
            $item = $stmt->fetch();

            if ($item) {
                // Update quantity
                $newQty = $item['quantity'] + $quantity;
                $update = $this->pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?"); // Added user_id check for safety
                return $update->execute([$newQty, $item['id'], $this->userId]);
            } else {
                // Insert new item
                // --- FIX APPLIED HERE: Removed 'added_at' column ---
                $insert = $this->pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
                return $insert->execute([$this->userId, $productId, $quantity]);
                // --- END FIX ---
            }
        } catch (PDOException $e) {
            error_log("Error adding/updating cart item for user {$this->userId}, product {$productId}: " . $e->getMessage());
            return false; // Indicate failure
        }
    }

    /**
     * Updates the quantity of an item in the cart. Removes if quantity <= 0.
     *
     * @param int $productId The ID of the product.
     * @param int $quantity The new quantity.
     * @return bool True on success, false on failure.
     */
    public function updateItem(int $productId, int $quantity): bool {
        if ($quantity <= 0) {
            return $this->removeItem($productId); // Delegate to remove function
        }
        try {
            $stmt = $this->pdo->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
            return $stmt->execute([$quantity, $this->userId, $productId]);
        } catch (PDOException $e) {
             error_log("Error updating cart item quantity for user {$this->userId}, product {$productId}: " . $e->getMessage());
             return false;
        }
    }

    /**
     * Removes an item completely from the cart.
     *
     * @param int $productId The ID of the product.
     * @return bool True on success, false on failure.
     */
    public function removeItem(int $productId): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
            return $stmt->execute([$this->userId, $productId]);
        } catch (PDOException $e) {
             error_log("Error removing cart item for user {$this->userId}, product {$productId}: " . $e->getMessage());
             return false;
        }
    }

    /**
     * Retrieves all items in the user's cart, joined with product details.
     *
     * @return array An array of cart items.
     */
    public function getItems(): array {
        try {
            // Join with products to get details needed for display/calculations
            $stmt = $this->pdo->prepare("
                SELECT ci.product_id, ci.quantity, p.name, p.price, p.image, p.stock_quantity, p.backorder_allowed, p.low_stock_threshold
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.user_id = ?
            ");
            $stmt->execute([$this->userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []; // Return empty array if no items
        } catch (PDOException $e) {
            error_log("Error getting cart items for user {$this->userId}: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }

    /**
     * Removes all items from the user's cart.
     *
     * @return bool True on success, false on failure.
     */
    public function clearCart(): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
            return $stmt->execute([$this->userId]);
        } catch (PDOException $e) {
             error_log("Error clearing cart for user {$this->userId}: " . $e->getMessage());
             return false;
        }
    }

    /**
     * Merges items from a session cart into the user's database cart.
     * Uses addItem which handles quantity updates for existing items.
     *
     * @param array $sessionCart Associative array [productId => quantity].
     */
    public function mergeSessionCart(array $sessionCart): void {
        if (empty($sessionCart)) return;

        // Use transaction for merging multiple items
        $this->pdo->beginTransaction();
        try {
             foreach ($sessionCart as $productId => $item) {
                 // Ensure productId is int
                 $productId = filter_var($productId, FILTER_VALIDATE_INT);
                 if ($productId === false || $productId <= 0) continue; // Skip invalid product IDs

                 // Support both [productId => quantity] and potentially [productId => ['quantity' => x]]
                 $quantity = is_array($item) && isset($item['quantity'])
                              ? filter_var($item['quantity'], FILTER_VALIDATE_INT)
                              : filter_var($item, FILTER_VALIDATE_INT);

                 if ($quantity === false || $quantity <= 0) continue; // Skip invalid quantities

                 // addItem handles checking existing items and adding/updating quantity
                 $this->addItem($productId, $quantity);
             }
             $this->pdo->commit();
        } catch (Exception $e) {
             $this->pdo->rollBack();
             error_log("Error merging session cart for user {$this->userId}: " . $e->getMessage());
             // Decide how to handle merge failure - maybe log specific items that failed?
        }
    }

     /**
     * Gets the total number of items (sum of quantities) in the user's cart.
     *
     * @return int Total item count.
     */
    public function getCartCount(): int {
        try {
            $stmt = $this->pdo->prepare("SELECT SUM(quantity) as total_items FROM cart_items WHERE user_id = ?");
            $stmt->execute([$this->userId]);
            $result = $stmt->fetchColumn();
            return $result ? (int)$result : 0;
        } catch (PDOException $e) {
            error_log("Error getting cart count for user {$this->userId}: " . $e->getMessage());
            return 0; // Return 0 on error
        }
    }

} // End of Cart class

```

# includes/ErrorHandler.php  
```php
<?php
// includes/ErrorHandler.php (Corrected - Re-added missing handler methods)

// Ensure SecurityLogger class is defined before ErrorHandler uses it.
// (It's defined below in this same file)

class ErrorHandler {
    private static $logger; // For optional external PSR logger
    private static ?SecurityLogger $securityLogger = null; // Use type hint, initialize as null
    private static array $errorCount = []; // Use type hint
    private static array $lastErrorTime = []; // Use type hint

    public static function init($logger = null): void { // Add void return type hint
        self::$logger = $logger;

        // Instantiate SecurityLogger - PDO injection needs careful handling here
        // Since init is static and called early, we rely on the logger's fallback
        if (self::$securityLogger === null) {
            self::$securityLogger = new SecurityLogger(); // Instantiated without PDO initially
        }

        // --- Set up handlers ---
        // Ensure these methods exist below before setting handlers!
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
        // --- End of Set up handlers ---


        // Log rotation setup (Improved checks)
        $logDir = realpath(__DIR__ . '/../logs');
        if ($logDir === false) {
             if (!is_dir(__DIR__ . '/../logs')) { // Check if directory creation is needed
                if (!@mkdir(__DIR__ . '/../logs', 0750, true)) { // Attempt creation, suppress errors for logging
                      error_log("FATAL: Failed to create log directory: " . __DIR__ . '/../logs' . " - Check parent directory permissions.");
                      // Potentially terminate or throw exception if logging is critical
                 } else {
                     @chmod(__DIR__ . '/../logs', 0750); // Try setting permissions after creation
                 }
            } else {
                 // Directory exists but realpath failed (symlink issue?)
                 error_log("Warning: Log directory path resolution failed for: " . __DIR__ . '/../logs');
            }
        } elseif (!is_writable($logDir)) {
             error_log("FATAL: Log directory is not writable: " . $logDir . " - Check permissions.");
             // Potentially terminate or throw exception
        }
    }

    // --- START: Missing Handler Methods Added Back ---

    /**
     * Custom error handler. Converts PHP errors to exceptions (optional) or logs and displays them.
     */
    public static function handleError(int $errno, string $errstr, string $errfile = '', int $errline = 0): bool {
        // Check if error reporting is suppressed with @
        if (!(error_reporting() & $errno)) {
            return false; // Don't execute the PHP internal error handler
        }

        $error = [
            'type' => self::getErrorType($errno),
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'context' => self::getSecureContext()
        ];

        self::trackError($error); // Track frequency
        self::logErrorToFile($error); // Log to file/logger

        // Display error only in development, hide details in production
        // Using output buffering inside displayErrorPage for safety
        if (!headers_sent()) {
             http_response_code(500);
        } else {
            error_log("ErrorHandler Warning: Cannot set HTTP 500 status code, headers already sent before error handling (errno: {$errno}).");
        }

        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            self::displayErrorPage($error);
        } else {
            self::displayErrorPage(null); // Display generic error page
        }

        // Returning true prevents PHP's default error handler.
        // Usually desired, but might want to return false for certain non-fatal errors
        // if you want PHP's logging to also occur.
        // For E_USER_ERROR, returning true *might* prevent script termination, depending on PHP version/config.
        // It's safer to exit explicitly in handleException for uncaught exceptions.
        // If this error is fatal (E_ERROR, E_PARSE, etc.), PHP will likely terminate anyway.
        // Let's return true to indicate we've handled it.
        return true;
    }

     /**
      * Custom exception handler. Logs uncaught exceptions and displays an error page.
      */
     public static function handleException(Throwable $exception): void { // Use Throwable type hint (PHP 7+)
        // --- Enhanced Logging ---
        $errorMessage = sprintf(
            "Uncaught Exception '%s': \"%s\" in %s:%d",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        error_log($errorMessage); // Log basic info immediately
        error_log("Stack trace:\n" . $exception->getTraceAsString()); // Log trace separately

        $error = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(), // Include trace
            'context' => self::getSecureContext()
        ];

        // Log security exceptions specifically
        if (self::isSecurityError($error)) { // Check keywords for security relevance
             if(self::$securityLogger) self::$securityLogger->warning("Potentially security-related exception caught", $error);
        }

        self::logErrorToFile($error); // Log all exceptions with more detail

         // Display error only in development, hide details in production
         if (!headers_sent()) {
              http_response_code(500);
         } else {
             error_log("ErrorHandler Warning: Cannot set HTTP 500 status code, headers already sent before exception handling.");
         }

         // Use output buffering to capture the error page output safely
         ob_start();
         try {
             if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                 self::displayErrorPage($error);
             } else {
                 self::displayErrorPage(null);
             }
             echo ob_get_clean(); // Send buffered output
         } catch (Throwable $displayError) {
              ob_end_clean(); // Discard buffer if error page itself fails
              // Fallback to plain text if error page fails
              if (!headers_sent()) { // Check again before sending fallback header
                   header('Content-Type: text/plain; charset=UTF-8', true, 500);
              }
              echo "A critical error occurred, and the error page could not be displayed.\n";
              echo "Please check the server error logs for details.\n";
              error_log("FATAL: Failed to display error page. Original error: " . print_r($error, true) . ". Display error: " . $displayError->getMessage());
         }

         exit(1); // Ensure script terminates after handling uncaught exception
     }

     /**
      * Shutdown handler to catch fatal errors that aren't caught by set_error_handler.
      */
     public static function handleFatalError(): void {
         $error = error_get_last();
         // Check if it's a fatal error type we want to handle
         if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
              // Create a structured error array similar to handleError/handleException
             $fatalError = [
                 'type' => self::getErrorType($error['type']),
                 'message' => $error['message'],
                 'file' => $error['file'],
                 'line' => $error['line'],
                 'context' => self::getSecureContext(),
                 'trace' => "N/A (Fatal Error)" // No trace available for most fatal errors
             ];

             self::logErrorToFile($fatalError); // Log the fatal error

              // Avoid double display if headers already sent by previous output/error
              // Use output buffering for safety
              ob_start();
              try {
                   if (!headers_sent()) {
                       http_response_code(500);
                       // We might be mid-output, but try to set HTML type if possible
                       // Avoid this if displaying a plain text fallback later
                       // header('Content-Type: text/html; charset=UTF-8');
                   } else {
                        error_log("ErrorHandler Warning: Cannot set HTTP 500 status code, headers already sent before fatal error handling.");
                   }

                   if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                       self::displayErrorPage($fatalError);
                   } else {
                       self::displayErrorPage(null); // Generic error page
                   }
                   echo ob_get_clean(); // Send buffered output
               } catch (Throwable $displayError) {
                   ob_end_clean(); // Discard buffer if error page itself fails
                   if (!headers_sent()) { // Check again before sending fallback header
                       header('Content-Type: text/plain; charset=UTF-8', true, 500);
                   }
                   // If headers WERE sent, this plain text might interleave badly, but it's a last resort
                   echo "\n\nA critical fatal error occurred, and the error page could not be displayed.\n";
                   echo "Please check the server error logs for details.\n";
                   error_log("FATAL: Failed to display fatal error page. Original error: " . print_r($fatalError, true) . ". Display error: " . $displayError->getMessage());
               }
              // No exit() here, as shutdown function runs after script execution theoretically finishes.
         }
     }

     private static function getErrorType(int $errno): string {
        switch ($errno) {
            case E_ERROR: return 'E_ERROR (Fatal Error)';
            case E_WARNING: return 'E_WARNING (Warning)';
            case E_PARSE: return 'E_PARSE (Parse Error)';
            case E_NOTICE: return 'E_NOTICE (Notice)';
            case E_CORE_ERROR: return 'E_CORE_ERROR (Core Error)';
            case E_CORE_WARNING: return 'E_CORE_WARNING (Core Warning)';
            case E_COMPILE_ERROR: return 'E_COMPILE_ERROR (Compile Error)';
            case E_COMPILE_WARNING: return 'E_COMPILE_WARNING (Compile Warning)';
            case E_USER_ERROR: return 'E_USER_ERROR (User Error)';
            case E_USER_WARNING: return 'E_USER_WARNING (User Warning)';
            case E_USER_NOTICE: return 'E_USER_NOTICE (User Notice)';
            case E_STRICT: return 'E_STRICT (Strict Notice)';
            case E_RECOVERABLE_ERROR: return 'E_RECOVERABLE_ERROR (Recoverable Error)';
            case E_DEPRECATED: return 'E_DEPRECATED (Deprecated)';
            case E_USER_DEPRECATED: return 'E_USER_DEPRECATED (User Deprecated)';
            default: return 'Unknown Error Type (' . $errno . ')';
        }
    }

     private static function getSecureContext(): array {
        $context = [
            'url' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
            'timestamp' => date('Y-m-d H:i:s T') // Add timezone
        ];

        // Add user context if available and session started
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            $context['user_id'] = $_SESSION['user_id'];
        }
         if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user']['id'])) { // Check preferred structure
             $context['user_id'] = $_SESSION['user']['id'];
         }

        return $context;
    }

     // Renamed logError to logErrorToFile to avoid confusion with SecurityLogger::error
     private static function logErrorToFile(array $error): void {
        $message = sprintf(
            "[%s] [%s] %s in %s on line %d",
            date('Y-m-d H:i:s T'), // Add timezone
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line']
        );

        // Append trace if available and relevant (not for notices/warnings usually)
        if (!empty($error['trace']) && !in_array($error['type'], ['E_NOTICE', 'E_USER_NOTICE', 'E_WARNING', 'E_USER_WARNING', 'E_DEPRECATED', 'E_USER_DEPRECATED', 'E_STRICT'])) {
            $message .= "\nStack trace:\n" . $error['trace'];
        }

        // Append context if available
        if (!empty($error['context'])) {
            // Use pretty print for readability in logs
            $contextJson = json_encode($error['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($contextJson === false) {
                 $contextJson = "Failed to encode context: " . json_last_error_msg();
            }
            $message .= "\nContext: " . $contextJson;
        }

        // Log to external logger if provided, otherwise use PHP's error_log
        if (self::$logger) {
            // Map error type to PSR log level (simplified mapping)
            $level = match (substr($error['type'], 0, 7)) {
                 'E_ERROR', 'E_PARSE', 'E_CORE_', 'E_COMPI' => 'critical', // Treat fatal/compile/parse as critical
                 'E_USER_' => 'error', // User errors treated as errors
                 'E_WARNI', 'E_RECOV' => 'warning', // Warnings, recoverable
                 'E_DEPRE' => 'notice', // Deprecated as notice
                 'E_NOTIC', 'E_STRIC' => 'notice', // Notices, strict standards
                 default => 'error' // Default to error
            };
            // Ensure logger implements PSR-3 or adapt call accordingly
            if (method_exists(self::$logger, $level)) {
                 self::$logger->{$level}($message); // Assumes PSR-3 compatible logger
            } else {
                self::$logger->log('error', $message); // Fallback PSR log level
            }
        } else {
             // Log to PHP's configured error log
             error_log($message);
        }

        // Log to security log if it seems security-related
        if (self::isSecurityError($error)) {
             if(self::$securityLogger) self::$securityLogger->warning("Security-related error detected", $error);
        }
    }


    private static function isSecurityError(array $error): bool {
        // Keep this simple keyword check
        $securityKeywords = [
            'sql', 'database', 'injection', // Common DB/Injection terms
            'xss', 'cross-site', 'script', // XSS related
            'csrf', 'token', // CSRF related
            'auth', 'password', 'login', 'permission', 'credentials', 'unauthorized', // Auth/Access
            'ssl', 'tls', 'certificate', 'encryption', // Security transport/crypto
            'overflow', 'upload', 'file inclusion', 'directory traversal', // Common vulnerabilities
            'session fixation', 'hijack' // Session issues
        ];

        $errorMessageLower = strtolower($error['message']);
        $errorFileLower = isset($error['file']) ? strtolower($error['file']) : '';

        foreach ($securityKeywords as $keyword) {
            if (str_contains($errorMessageLower, $keyword)) { // Use str_contains (PHP 8+)
                return true;
            }
        }
         // Check if error occurs in sensitive files
         if (str_contains($errorFileLower, 'securitymiddleware.php') || str_contains($errorFileLower, 'auth.php')) {
             return true;
         }

        return false;
    }


     // Renamed displayError to displayErrorPage for clarity
     private static function displayErrorPage(?array $error = null): void { // Allow null for production
        // This method now assumes it's called *within* output buffering
        // in the handler methods (handleError, handleException, handleFatalError)
        try {
            $pageTitle = 'Error'; // Define variables needed by the view
            $bodyClass = 'page-error';
            $isDevelopment = defined('ENVIRONMENT') && ENVIRONMENT === 'development';

            // Prepare data for extraction
            $viewData = [
                'pageTitle' => $pageTitle,
                'bodyClass' => $bodyClass,
                // Only pass detailed error info if in development
                'error' => ($isDevelopment && $error !== null) ? $error : null
            ];

            extract($viewData); // Extract variables into the current scope

            // Define ROOT_PATH if not already defined globally
            if (!defined('ROOT_PATH')) {
                define('ROOT_PATH', realpath(__DIR__ . '/..'));
            }

            // Use a dedicated, self-contained error view
            $errorViewPath = ROOT_PATH . '/views/error.php';

            if (file_exists($errorViewPath)) {
                // Include the error view - This view should NOT include header/footer
                include $errorViewPath;
            } else {
                 // Fallback INLINE HTML if error view is missing
                 echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Error</title>';
                 echo '<style>body { font-family: sans-serif; padding: 20px; } .error-details { margin-top: 20px; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px; white-space: pre-wrap; word-wrap: break-word; }</style>';
                 echo '</head><body><h1>Application Error</h1>';
                 echo '<p>An unexpected error occurred. Please try again later or contact support.</p>';
                 if ($isDevelopment && isset($error)) { // Only show details in dev
                     echo '<div class="error-details"><strong>Details (Development Mode):</strong><br>';
                     echo htmlspecialchars(print_r($error, true));
                     echo '</div>';
                 }
                 echo '</body></html>';
                 error_log("FATAL: Error view file not found at: " . $errorViewPath);
            }

        } catch (Throwable $t) {
            // If displaying the error page ITSELF throws an error, fallback to text
             if (!headers_sent()) {
                  header('Content-Type: text/plain; charset=UTF-8', true, 500);
             }
            echo "A critical error occurred while trying to display the error page.\n";
            echo "Original Error: " . ($error['message'] ?? 'N/A') . "\n";
            echo "Error Display Error: " . $t->getMessage() . "\n";
            error_log("FATAL: Failed to display error page itself. Original error: " . print_r($error, true) . ". Display error: " . $t->getMessage());
        }
     }

    // --- END: Missing Handler Methods Added Back ---


    // --- TrackError method from new version ---
    private static function trackError(array $error): void { // Use type hint, keep private
         $errorKey = md5(($error['file'] ?? 'unknown_file') . ($error['line'] ?? '0') . ($error['type'] ?? 'unknown_type'));
         $now = time();

         // Initialize if not set
         self::$errorCount[$errorKey] = self::$errorCount[$errorKey] ?? 0;
         self::$lastErrorTime[$errorKey] = self::$lastErrorTime[$errorKey] ?? $now;


         // Reset count if more than an hour has passed
         if ($now - self::$lastErrorTime[$errorKey] > 3600) {
             self::$errorCount[$errorKey] = 0;
             self::$lastErrorTime[$errorKey] = $now; // Reset time as well
         }

         self::$errorCount[$errorKey]++;
         // Update last error time on each occurrence within the window
         // self::$lastErrorTime[$errorKey] = $now; // Decide if you want last time or first time in window

         // Alert on high frequency errors
         // Use constant or configurable value
         $alertThreshold = defined('ERROR_ALERT_THRESHOLD') ? (int)ERROR_ALERT_THRESHOLD : 10;
         if (self::$errorCount[$errorKey] > $alertThreshold) {
             // Ensure securityLogger is initialized
             if (isset(self::$securityLogger)) {
                 self::$securityLogger->alert("High frequency error detected", [
                     'error_type' => $error['type'] ?? 'Unknown', // Use specific fields
                     'error_message' => $error['message'] ?? 'N/A',
                     'file' => $error['file'] ?? 'N/A',
                     'line' => $error['line'] ?? 'N/A',
                     'count_in_window' => self::$errorCount[$errorKey],
                     'window_start_time' => date('Y-m-d H:i:s T', self::$lastErrorTime[$errorKey]) // Time window started
                 ]);
                 // Optionally reset count after alerting to prevent spamming
                 // self::$errorCount[$errorKey] = 0; // Reset immediately after alert
             } else {
                 error_log("High frequency error detected but SecurityLogger not available: " . print_r($error, true));
             }
         }
     }

} // End of ErrorHandler class


// --- SecurityLogger Class Update (from new version) ---

class SecurityLogger {
    private string $logFile; // Use type hint
    private ?PDO $pdo = null; // Allow PDO to be nullable or set later

    public function __construct(?PDO $pdo = null) { // Make PDO optional for flexibility
         $this->pdo = $pdo; // Store PDO if provided
        // Define log path using config or default
         $logDir = defined('SECURITY_SETTINGS') && isset(SECURITY_SETTINGS['logging']['security_log'])
                 ? dirname(SECURITY_SETTINGS['logging']['security_log'])
                 : realpath(__DIR__ . '/../logs');

         // Corrected directory check and creation logic
         if ($logDir === false) {
             $potentialLogDir = __DIR__ . '/../logs';
             // Attempt to create if directory check itself failed (e.g. doesn't exist)
             if (!is_dir($potentialLogDir)) {
                 if (!@mkdir($potentialLogDir, 0750, true)) {
                      error_log("SecurityLogger FATAL: Failed to create log directory: " . $potentialLogDir);
                      $this->logFile = '/tmp/security_fallback.log'; // Use fallback
                 } else {
                      @chmod($potentialLogDir, 0750);
                      $logDir = realpath($potentialLogDir); // Try realpath again
                      if (!$logDir) $logDir = $potentialLogDir; // Use path even if realpath fails after creation
                 }
             } else {
                  // Directory exists but realpath failed? Log warning.
                  error_log("SecurityLogger Warning: Log directory path resolution failed for: " . $potentialLogDir);
                  $logDir = $potentialLogDir; // Use the path directly
             }
         }

         if (!$logDir || !is_writable($logDir)) {
             error_log("SecurityLogger FATAL: Log directory is not writable: " . ($logDir ?: 'Not Found'));
             $this->logFile = '/tmp/security_fallback.log'; // Use fallback
         } else {
             $logFileName = defined('SECURITY_SETTINGS') && isset(SECURITY_SETTINGS['logging']['security_log'])
                           ? basename(SECURITY_SETTINGS['logging']['security_log'])
                           : 'security.log'; // Default filename
             $this->logFile = $logDir . '/' . $logFileName;
         }
    }

    // --- Logging Methods (emergency, alert, etc.) ---
     public function emergency(string $message, array $context = []): void { $this->log('EMERGENCY', $message, $context); }
     public function alert(string $message, array $context = []): void { $this->log('ALERT', $message, $context); }
     public function critical(string $message, array $context = []): void { $this->log('CRITICAL', $message, $context); }
     public function error(string $message, array $context = []): void { $this->log('ERROR', $message, $context); }
     public function warning(string $message, array $context = []): void { $this->log('WARNING', $message, $context); }
     public function info(string $message, array $context = []): void { $this->log('INFO', $message, $context); } // Added info level
     public function debug(string $message, array $context = []): void { // Only log debug if enabled
         // Check if ENVIRONMENT constant is defined and set to 'development'
         $isDebug = (defined('ENVIRONMENT') && ENVIRONMENT === 'development');
         // Allow overriding with DEBUG_MODE if defined
         if (defined('DEBUG_MODE')) {
             $isDebug = (DEBUG_MODE === true);
         }

         if ($isDebug) {
             $this->log('DEBUG', $message, $context);
         }
     }

    // --- Private log method (from new version) ---
    private function log(string $level, string $message, array $context): void {
        $timestamp = date('Y-m-d H:i:s T'); // Add Timezone

        // Include essential context automatically if not provided
        $autoContext = [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            // Attempt to get user ID safely
            'user_id' => (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user']['id']))
                         ? $_SESSION['user']['id']
                         : ((session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) ? $_SESSION['user_id'] : null),
             // 'url' => $_SERVER['REQUEST_URI'] ?? null // Can be verbose
        ];
        // Merge auto-context first, so provided context can override if needed
        $finalContext = array_merge($autoContext, $context);


        // Use json_encode with flags for better readability and error handling
        $contextStr = json_encode($finalContext, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($contextStr === false) {
             $contextStr = "Failed to encode context: " . json_last_error_msg();
        }

        $logMessage = "[{$timestamp}] [{$level}] {$message} | Context: {$contextStr}" . PHP_EOL;

        // Log to file with locking
        // Suppress errors here as we have fallbacks and error logging within this class
        // Check if file exists and is writable one last time
        if (is_writable($this->logFile) || (is_writable(dirname($this->logFile)) && @touch($this->logFile)) ) {
             @file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        } else {
            // Fallback to PHP's error log if primary security log isn't writable
            error_log("SecurityLogger Fallback: Failed to write to {$this->logFile}. Logging message instead: {$logMessage}");
        }

        // Alert admins on critical issues
        if (in_array($level, ['EMERGENCY', 'ALERT', 'CRITICAL'])) {
            $this->alertAdmins($level, $message, $finalContext);
        }
    }

    // --- alertAdmins method (from new version with pragmatic fix) ---
    private function alertAdmins(string $level, string $message, array $context): void {
        // Ensure EmailService class exists and is included/autoloaded
        if (!class_exists('EmailService')) {
             error_log("EmailService class not found. Cannot send security alert email.");
             return;
        }
        // Ensure BASE_URL is defined for EmailService constructor/methods
        if (!defined('BASE_URL')) {
             error_log("BASE_URL not defined. Cannot send security alert email.");
             return;
        }

        // Pragmatic Fix: Use global $pdo IF $this->pdo wasn't set during instantiation
        $pdoToUse = $this->pdo;
        if ($pdoToUse === null) {
             global $pdo; // Access global PDO (defined in db.php)
             if (!isset($pdo) || !$pdo instanceof PDO) {
                 error_log("Global PDO not available for SecurityLogger email alert. Cannot send email.");
                 return; // Cannot proceed without PDO
             }
             $pdoToUse = $pdo;
        }

        try {
             // Instantiate EmailService here, passing the required PDO object
             $emailService = new EmailService($pdoToUse);
             // Call the method responsible for sending security alerts
             // Ensure EmailService::sendSecurityAlert exists and accepts these parameters
             if (method_exists($emailService, 'sendSecurityAlert')) {
                 $emailService->sendSecurityAlert($level, $message, $context);
             } else {
                  error_log("EmailService::sendSecurityAlert method not found. Cannot send security alert email.");
             }
        } catch (Throwable $e) { // Catch Throwable for broader error coverage
            // Log failure to send alert email
            error_log("Failed to send security alert email: Level={$level}, Error=" . $e->getMessage() . " Trace: " . $e->getTraceAsString());
        }
    }

} // End of SecurityLogger class

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
require_once __DIR__ . '/../config.php'; // Keep config include

// Use statement for Stripe classes
use Stripe\Stripe; // Added for setting API key globally if needed
use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
// Include models needed for webhook actions
require_once __DIR__ . '/../models/Order.php'; // Needed for updating order status
require_once __DIR__ . '/../models/User.php';  // Needed for sending emails
require_once __DIR__ . '/../models/Cart.php';  // Needed for clearing cart

class PaymentController extends BaseController {
    private ?StripeClient $stripe; // Allow null initialization
    private ?string $webhookSecret; // Allow null initialization
    private Order $orderModel; // Add Order model instance

    public function __construct($pdo = null) {
        parent::__construct($pdo); // BaseController handles EmailService init now

        // Ensure PDO is available if needed directly or via BaseController
        if (!$this->db) {
             error_log("PDO connection not available in PaymentController constructor.");
             // Handle appropriately - maybe throw exception
        }
        $this->orderModel = new Order($this->db); // Initialize Order model

        // Ensure Stripe keys are defined
        if (!defined('STRIPE_SECRET_KEY') || !defined('STRIPE_WEBHOOK_SECRET')) {
            error_log("Stripe keys are not defined in config.php");
            $this->stripe = null;
            $this->webhookSecret = null;
            return; // Stop initialization if keys are missing
        }

        // Use try-catch for external service initialization
        try {
            // Set API key globally (optional but common practice)
            // Stripe::setApiKey(STRIPE_SECRET_KEY); // Uncomment if preferred over instance key
            $this->stripe = new StripeClient(STRIPE_SECRET_KEY);
            $this->webhookSecret = STRIPE_WEBHOOK_SECRET;
        } catch (\Exception $e) {
             error_log("Failed to initialize Stripe client: " . $e->getMessage());
             $this->stripe = null; // Ensure stripe is null if init fails
             $this->webhookSecret = null;
             // Consider throwing Exception("Payment system configuration error.");
        }
    }

    /**
     * Create a Stripe Payment Intent.
     *
     * @param float $amount Amount in major currency unit (e.g., dollars).
     * @param string $currency 3-letter ISO currency code.
     * @param int $orderId Internal order ID for metadata.
     * @param string $customerEmail Email for receipt/customer matching.
     * @return array ['success' => bool, 'client_secret' => string|null, 'payment_intent_id' => string|null, 'error' => string|null]
     */
    public function createPaymentIntent(float $amount, string $currency = 'usd', int $orderId = 0, string $customerEmail = ''): array {
        // Ensure Stripe client is initialized
        if (!$this->stripe) {
             return ['success' => false, 'error' => 'Payment system unavailable.'];
        }

        // Prepare parameters (moved inside try block)
        $paymentIntentParams = [];

        try {
            // Basic validation
            if ($amount <= 0) {
                throw new InvalidArgumentException('Invalid payment amount.');
            }
            $currency = strtolower(trim($currency));
            if (strlen($currency) !== 3) {
                 throw new InvalidArgumentException('Invalid currency code.');
            }
            if ($orderId <= 0) {
                 throw new InvalidArgumentException('Invalid Order ID for Payment Intent.');
            }

            $paymentIntentParams = [
                'amount' => (int)round($amount * 100), // Convert to cents
                'currency' => $currency,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'user_id' => $this->getUserId() ?? 'guest',
                    'order_id' => $orderId,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
                ]
            ];

             if (!empty($customerEmail)) {
                 $paymentIntentParams['receipt_email'] = $customerEmail;
             }

            $paymentIntent = $this->stripe->paymentIntents->create($paymentIntentParams);

            // --- MODIFIED: Return Payment Intent ID ---
            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id // Include the Payment Intent ID
            ];
            // --- END MODIFICATION ---

        } catch (ApiErrorException $e) {
            error_log("Stripe API Error creating PaymentIntent: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
            return [
                'success' => false,
                'error' => 'Payment processing failed. Please try again or contact support.',
                'client_secret' => null,
                'payment_intent_id' => null
            ];
        } catch (InvalidArgumentException $e) { // Catch specific validation errors
             error_log("Payment Intent Creation Invalid Argument: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
             return [
                 'success' => false,
                 'error' => $e->getMessage(), // Show specific validation error
                 'client_secret' => null,
                 'payment_intent_id' => null
             ];
         } catch (Exception $e) {
            error_log("Payment Intent Creation Error: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
            return [
                'success' => false,
                'error' => 'Could not initialize payment. Please try again later.', // Generic internal error
                'client_secret' => null,
                'payment_intent_id' => null
            ];
        }
    }


    public function handleWebhook() {
         // Ensure Stripe client is initialized
        if (!$this->stripe || !$this->webhookSecret) {
             http_response_code(503); // Service Unavailable
             error_log("Webhook handler cannot run: Stripe client or secret not initialized.");
             echo json_encode(['error' => 'Webhook configuration error.']);
             exit;
        }

        $payload = @file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;

        if (!$sigHeader) {
             error_log("Webhook Error: Missing Stripe signature header.");
             // Use jsonResponse for consistency
             $this->jsonResponse(['error' => 'Missing signature'], 400); // Exit handled by jsonResponse
             return; // For clarity, though exit happens
        }
        if (empty($payload)) {
             error_log("Webhook Error: Empty payload received.");
             $this->jsonResponse(['error' => 'Empty payload'], 400);
             return;
        }


        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $this->webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            error_log("Webhook Error: Invalid payload. " . $e->getMessage());
            $this->jsonResponse(['error' => 'Invalid payload'], 400);
            return;
        } catch (SignatureVerificationException $e) {
            error_log("Webhook Error: Invalid signature. " . $e->getMessage());
            $this->jsonResponse(['error' => 'Invalid signature'], 400);
            return;
        } catch (\Exception $e) {
            error_log("Webhook Error: Event construction failed. " . $e->getMessage());
            $this->jsonResponse(['error' => 'Webhook processing error'], 400);
            return;
        }

        // Handle the event
        try {
            $this->beginTransaction(); // Start transaction for DB updates

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handleSuccessfulPayment($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handleFailedPayment($event->data->object);
                    break;

                case 'charge.succeeded':
                     $this->handleChargeSucceeded($event->data->object);
                     break;

                case 'charge.dispute.created':
                    $this->handleDisputeCreated($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleRefund($event->data->object);
                    break;

                // Add other event types as needed

                default:
                    error_log('Webhook Warning: Received unhandled event type ' . $event->type);
            }

            $this->commit(); // Commit DB changes if no exceptions
            $this->jsonResponse(['success' => true, 'message' => 'Webhook received']); // Exit handled by jsonResponse

        } catch (Exception $e) {
            $this->rollback(); // Rollback DB changes on error
            error_log("Webhook Handling Error (Event: {$event->type}): " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            $this->jsonResponse(
                ['success' => false, 'error' => 'Internal server error handling webhook.'],
                500 // Use 500 for internal errors where retry might help
            ); // Exit handled by jsonResponse
        }
    }

    private function handleSuccessfulPayment(\Stripe\PaymentIntent $paymentIntent): void {
         // Find order by payment_intent_id using OrderModel
         $order = $this->orderModel->getByPaymentIntentId($paymentIntent->id); // Assume this method exists

         if (!$order) {
              $errorMessage = "Webhook Critical: PaymentIntent {$paymentIntent->id} succeeded but no matching order found.";
              error_log($errorMessage);
              $this->logSecurityEvent('webhook_order_mismatch', ['payment_intent_id' => $paymentIntent->id, 'event_type' => 'payment_intent.succeeded']);
              // Do not throw exception to acknowledge webhook receipt, but log heavily.
              return;
         }

         // Idempotency check
         if (in_array($order['status'], ['paid', 'processing', 'shipped', 'delivered', 'completed'])) { // Added 'processing'
             error_log("Webhook Info: Received successful payment event for already processed order ID {$order['id']}. Status: {$order['status']}");
             return;
         }

        // Update order status using OrderModel
        $updated = $this->orderModel->updateStatus($order['id'], 'paid'); // Or 'processing'

        if (!$updated) {
            // Maybe the status was already updated by another process? Re-fetch and check again before throwing.
            $currentOrder = $this->orderModel->getById($order['id']);
            if (!$currentOrder || !in_array($currentOrder['status'], ['paid', 'processing', 'shipped', 'delivered', 'completed'])) {
                throw new Exception("Failed to update order ID {$order['id']} payment status to 'paid'.");
            } else {
                 error_log("Webhook Info: Order ID {$order['id']} status already updated, skipping redundant update.");
            }
        } else {
             // --- MODIFIED: Set last_order_id in session ---
             // Ensure session is started (it should be by SecurityMiddleware::apply)
             if (session_status() === PHP_SESSION_ACTIVE) {
                 $_SESSION['last_order_id'] = $order['id'];
             } else {
                 error_log("Webhook Warning: Session not active, cannot set last_order_id for order {$order['id']}");
             }
             // --- END MODIFICATION ---
             error_log("Webhook Success: Updated order ID {$order['id']} status to 'paid' for PaymentIntent {$paymentIntent->id}. Session last_order_id set.");
        }


        // Fetch full details for email (or enhance getByPaymentIntentId to return user info)
        $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $order['user_id']); // Use existing method

        if ($fullOrder) {
             // Send payment confirmation email
             if ($this->emailService && method_exists($this->emailService, 'sendOrderConfirmation')) {
                  // Prepare user data array structure if needed by sendOrderConfirmation
                  $userModel = new User($this->db);
                  $user = $userModel->getById($fullOrder['user_id']);
                  if ($user) {
                       $this->emailService->sendOrderConfirmation($fullOrder, $user); // Pass user data if needed
                       error_log("Webhook Success: Order confirmation email queued for order ID {$fullOrder['id']}.");
                  } else {
                       error_log("Webhook Warning: Could not fetch user data for order confirmation email (Order ID: {$fullOrder['id']}).");
                  }
             } else {
                  error_log("Webhook Warning: EmailService or sendOrderConfirmation method not available for order ID {$fullOrder['id']}.");
             }
        } else {
             error_log("Webhook Warning: Could not fetch full order details for notification (Order ID: {$order['id']}).");
        }

        // Clear user's cart
        if ($order['user_id']) {
            try {
                $cartModel = new Cart($this->db, $order['user_id']);
                $cartModel->clearCart();
                error_log("Webhook Success: Cart cleared for user ID {$order['user_id']} after order {$order['id']} payment.");
            } catch (Exception $cartError) {
                 error_log("Webhook Warning: Failed to clear cart for user ID {$order['user_id']} after order {$order['id']} payment: " . $cartError->getMessage());
            }
        }
    }

    // --- Other Webhook Handlers (handleFailedPayment, handleChargeSucceeded, etc.) ---
    // These remain largely the same, but should ideally use OrderModel methods for updates.

    private function handleFailedPayment(\Stripe\PaymentIntent $paymentIntent): void {
         $order = $this->orderModel->getByPaymentIntentId($paymentIntent->id);
         if (!$order) {
              error_log("Webhook Warning: PaymentIntent {$paymentIntent->id} failed but no matching order found.");
              return;
         }
          if (in_array($order['status'], ['cancelled', 'paid', 'shipped', 'delivered', 'completed'])) {
              error_log("Webhook Info: Received failed payment event for already resolved order ID {$order['id']}.");
              return;
          }

        $updated = $this->orderModel->updateStatus($order['id'], 'payment_failed');
        if (!$updated) {
             // Re-fetch and check
            $currentOrder = $this->orderModel->getById($order['id']);
            if (!$currentOrder || $currentOrder['status'] !== 'payment_failed') {
                 throw new Exception("Failed to update order ID {$order['id']} status to 'payment_failed'.");
            }
        } else {
            error_log("Webhook Info: Updated order ID {$order['id']} status to 'payment_failed' for PaymentIntent {$paymentIntent->id}.");
        }

        // Send payment failed notification (fetch full order details first)
        $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $order['user_id']);
        if ($fullOrder) {
             if ($this->emailService && method_exists($this->emailService, 'sendPaymentFailedNotification')) {
                  $userModel = new User($this->db);
                  $user = $userModel->getById($fullOrder['user_id']);
                  if ($user) {
                        // Assuming sendPaymentFailedNotification takes order and user arrays
                       $this->emailService->sendPaymentFailedNotification($fullOrder, $user);
                       error_log("Webhook Info: Payment failed email queued for order ID {$fullOrder['id']}.");
                  } else {
                       error_log("Webhook Warning: Could not fetch user data for failed payment email (Order ID: {$fullOrder['id']}).");
                  }

             } else {
                  error_log("Webhook Warning: EmailService or sendPaymentFailedNotification method not available for order ID {$fullOrder['id']}.");
             }
        } else {
             error_log("Webhook Warning: Could not fetch full order details for failed payment notification (Order ID: {$order['id']}).");
        }
    }

     private function handleChargeSucceeded(\Stripe\Charge $charge): void {
         error_log("Webhook Info: Charge {$charge->id} succeeded for PaymentIntent {$charge->payment_intent} (Order linked via PI).");
     }

    private function handleDisputeCreated(\Stripe\Dispute $dispute): void {
        $order = $this->orderModel->getByPaymentIntentId($dispute->payment_intent);
         if (!$order) {
              error_log("Webhook Warning: Dispute {$dispute->id} created for PaymentIntent {$dispute->payment_intent} but no matching order found.");
              return;
         }

        // Update order status and store dispute ID using OrderModel
        $updated = $this->orderModel->updateStatusAndDispute($order['id'], 'disputed', $dispute->id); // Assume method exists

        if (!$updated) {
             // Re-fetch and check
            $currentOrder = $this->orderModel->getById($order['id']);
            if (!$currentOrder || $currentOrder['status'] !== 'disputed') {
                 throw new Exception("Failed to update order ID {$order['id']} dispute status.");
            }
        } else {
             error_log("Webhook Alert: Order ID {$order['id']} status updated to 'disputed' due to Dispute {$dispute->id}.");
        }

        // Log and alert admin (existing logic is okay)
        $this->logSecurityEvent('stripe_dispute_created', [ /* ... */ ]);
        if ($this->emailService && method_exists($this->emailService, 'sendAdminDisputeAlert')) {
             $this->emailService->sendAdminDisputeAlert($order['id'], $dispute->id, $dispute->reason, $dispute->amount);
        }
    }

    private function handleRefund(\Stripe\Charge $charge): void {
         $refund = $charge->refunds->data[0] ?? null;
         if (!$refund) {
             error_log("Webhook Warning: Received charge.refunded event for Charge {$charge->id} but no refund data found.");
             return;
         }

         $order = $this->orderModel->getByPaymentIntentId($charge->payment_intent);
         if (!$order) {
              error_log("Webhook Warning: Refund {$refund->id} processed for PaymentIntent {$charge->payment_intent} but no matching order found.");
              return;
         }

          $newStatus = 'refunded';
          $paymentStatus = ($charge->amount_refunded === $charge->amount) ? 'refunded' : 'partially_refunded';

          // Update using OrderModel
         $updated = $this->orderModel->updateRefundStatus($order['id'], $newStatus, $paymentStatus, $refund->id); // Assume method exists

        if (!$updated) {
             // Re-fetch and check
             $currentOrder = $this->orderModel->getById($order['id']);
             if (!$currentOrder || !in_array($currentOrder['status'], ['refunded', 'partially_refunded'])) { // Check possible statuses
                 throw new Exception("Failed to update order ID {$order['id']} refund status.");
             }
        } else {
            error_log("Webhook Info: Order ID {$order['id']} status updated to '{$newStatus}' due to Refund {$refund->id}.");
        }

        // Send refund confirmation email (fetch full order details first)
        $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $order['user_id']);
        if ($fullOrder) {
             if ($this->emailService && method_exists($this->emailService, 'sendRefundConfirmation')) {
                   $userModel = new User($this->db);
                   $user = $userModel->getById($fullOrder['user_id']);
                   if ($user) {
                        $this->emailService->sendRefundConfirmation($fullOrder, $user, $refund->amount / 100.0); // Pass user array if needed
                        error_log("Webhook Info: Refund confirmation email queued for order ID {$fullOrder['id']}.");
                   } else {
                        error_log("Webhook Warning: Could not fetch user for refund confirmation email (Order ID: {$fullOrder['id']}).");
                   }

             } else {
                  error_log("Webhook Warning: EmailService or sendRefundConfirmation method not available for order ID {$fullOrder['id']}.");
             }
        } else {
             error_log("Webhook Warning: Could not fetch full order details for refund notification (Order ID: {$order['id']}).");
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
// controllers/InventoryController.php (Updated)

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
// EmailService is included via BaseController's include

class InventoryController extends BaseController {
    // private $emailService; // Removed - Inherited from BaseController
    private Product $productModel; // Added type hint
    private $alertThreshold = 5;

    // Constructor now only needs PDO, EmailService is handled by parent
    public function __construct(PDO $pdo) { // Use type hint PDO $pdo
        parent::__construct($pdo); // Calls parent constructor which initializes $this->db and $this->emailService
        $this->productModel = new Product($pdo); // Initialize Product model
    }

    // --- updateStock Method ---
     // Added type hints and clarified variable usage
     public function updateStock(int $productId, float $quantity, string $type = 'adjustment', ?int $referenceId = null, ?string $notes = null) {
         try {
             $this->requireAdmin(); // Check admin role
             // CSRF validation needed if triggered by a form POST
             // Assuming this might be called internally or via secured API for now
             // $this->validateCSRF(); // Uncomment if called via form

             // Validate inputs (Basic validation done via type hints, add more if needed)
             $type = $this->validateInput($type, 'string'); // Validate type string further if needed
             $notes = $this->validateInput($notes, 'string'); // Ensure notes are safe

             if (!$type || !in_array($type, ['sale', 'restock', 'return', 'adjustment'])) {
                  throw new Exception('Invalid inventory movement type');
             }

             $this->beginTransaction();

             // Get current stock with locking (use $this->db)
             $stmt = $this->db->prepare("
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

             // Use stricter comparison and check backorder logic
             $newPotentialStock = $product['stock_quantity'] + $quantity;
             if ($quantity < 0 && !$product['backorder_allowed'] && $newPotentialStock < 0) {
                 throw new Exception('Insufficient stock for ' . htmlspecialchars($product['name']));
             }

             // Update product stock (use $this->db)
             $updateStmt = $this->db->prepare("
                 UPDATE products
                 SET stock_quantity = stock_quantity + ?,
                     updated_at = NOW()
                 WHERE id = ?
             ");
             $updateStmt->execute([$quantity, $productId]);

             // Record movement with audit trail (use $this->db)
             $movementStmt = $this->db->prepare("
                 INSERT INTO inventory_movements (
                     product_id,
                     quantity_change,
                     previous_quantity,
                     new_quantity,
                     type,
                     reference_id,
                     notes,
                     created_by,
                     ip_address,
                     created_at
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ");
             $movementStmt->execute([
                 $productId,
                 $quantity,
                 $product['stock_quantity'], // Previous quantity before update
                 $newPotentialStock, // New quantity after update
                 $type,
                 $referenceId,
                 $notes,
                 $this->getUserId(), // Get current admin user ID
                 $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
             ]);

             // Check stock levels and send alerts if needed
             $this->checkStockLevels($product, $newPotentialStock);

             $this->commit();

             // Use standardized jsonResponse
             return $this->jsonResponse([
                 'success' => true,
                 'message' => 'Stock updated successfully for ' . htmlspecialchars($product['name']),
                 'new_quantity' => $newPotentialStock
             ]);

         } catch (Exception $e) {
             $this->rollback();
             error_log("Stock update error for product {$productId}: " . $e->getMessage());

             // Use standardized jsonResponse for errors
             return $this->jsonResponse([
                 'success' => false,
                 'message' => $e->getMessage() // Provide specific error message
             ], 500);
         }
     }


    // Updated checkStockLevels to handle potential missing EmailService method
     private function checkStockLevels(array $product, float $newQuantity): void {
         // Defensive check for necessary keys
         if (!isset($product['low_stock_threshold']) || !isset($product['initial_stock'])) {
              error_log("Missing stock level data for product ID {$product['id']} in checkStockLevels");
              return;
         }

         // Ensure threshold is numeric
         $lowStockThreshold = filter_var($product['low_stock_threshold'], FILTER_VALIDATE_INT);
         if ($lowStockThreshold === false) $lowStockThreshold = 0; // Default to 0 if invalid


         // Check if stock is below threshold
         if ($newQuantity <= $lowStockThreshold && $lowStockThreshold > 0) { // Only alert if threshold > 0
             // Log low stock alert consistently
             $logMessage = "Low stock alert: Product '{$product['name']}' (ID: {$product['id']}) has only {$newQuantity} units left (Threshold: {$lowStockThreshold}).";
             error_log($logMessage);
             $this->logSecurityEvent('low_stock_alert', ['product_id' => $product['id'], 'product_name' => $product['name'], 'current_stock' => $newQuantity, 'threshold' => $lowStockThreshold]);

             // Calculate stock percentage if initial stock is valid
             $initialStock = filter_var($product['initial_stock'], FILTER_VALIDATE_INT);
             $stockPercentage = ($initialStock !== false && $initialStock > 0)
                 ? ($newQuantity / $initialStock) * 100
                 : 0; // Avoid division by zero

             // Ensure alert threshold is numeric
             $alertThresholdPercent = filter_var($this->alertThreshold, FILTER_VALIDATE_FLOAT);
             if ($alertThresholdPercent === false) $alertThresholdPercent = 5.0; // Default percentage

             // Send alert email if stock is critically low based on percentage
             // Check if the method exists before calling
             if ($stockPercentage <= $alertThresholdPercent && method_exists($this->emailService, 'sendLowStockAlert')) {
                 try {
                    $this->emailService->sendLowStockAlert(
                         $product['name'],
                         $newQuantity,
                         $initialStock > 0 ? $initialStock : 'N/A', // Handle case where initial stock might be 0 or invalid
                         $stockPercentage
                     );
                 } catch (Exception $e) {
                      error_log("Failed to send low stock alert email for product ID {$product['id']}: " . $e->getMessage());
                 }

             }
         }
     }


    // --- getInventoryMovements Method ---
     // Added type hints and PDO usage correction
     public function getInventoryMovements(int $productId, ?string $startDate = null, ?string $endDate = null, ?string $type = null) {
         try {
             $this->requireAdmin();

             // Validate optional parameters further if needed (e.g., date format)
             $type = $this->validateInput($type, 'string'); // Basic validation

             $params = [$productId];
             $sql = "
                 SELECT
                     m.id, m.quantity_change, m.previous_quantity, m.new_quantity,
                     m.type, m.reference_id, m.notes, m.created_at, m.ip_address,
                     u.name as user_name,
                     p.name as product_name
                 FROM inventory_movements m
                 LEFT JOIN users u ON m.created_by = u.id
                 JOIN products p ON m.product_id = p.id
                 WHERE m.product_id = ?
             ";

             if ($startDate) {
                 // Basic date validation attempt
                 if (DateTime::createFromFormat('Y-m-d', $startDate) !== false) {
                     $sql .= " AND DATE(m.created_at) >= ?";
                     $params[] = $startDate;
                 } else {
                     // Handle invalid date format? Log or ignore?
                      error_log("Invalid start date format provided: " . $startDate);
                 }
             }

             if ($endDate) {
                  if (DateTime::createFromFormat('Y-m-d', $endDate) !== false) {
                      $sql .= " AND DATE(m.created_at) <= ?";
                      $params[] = $endDate;
                  } else {
                      error_log("Invalid end date format provided: " . $endDate);
                  }
             }

             if ($type && in_array($type, ['sale', 'restock', 'return', 'adjustment'])) {
                 $sql .= " AND m.type = ?";
                 $params[] = $type;
             }

             $sql .= " ORDER BY m.created_at DESC";

             // Use $this->db
             $stmt = $this->db->prepare($sql);
             $stmt->execute($params);

             return $this->jsonResponse([
                 'success' => true,
                 'movements' => $stmt->fetchAll()
             ]);

         } catch (Exception $e) {
             error_log("Error fetching inventory movements for product {$productId}: " . $e->getMessage());
             return $this->jsonResponse([
                 'success' => false,
                 'message' => 'Failed to retrieve inventory movements'
             ], 500);
         }
     }


    // --- getStockReport Method ---
    // Added type hints and PDO usage correction
     public function getStockReport(?int $categoryId = null) {
         try {
             $this->requireAdmin();

             $params = [];
             // Added c.name for category name in report
             $sql = "
                 SELECT
                     p.id,
                     p.name,
                     p.sku, -- Added SKU
                     c.name as category_name, -- Added Category Name
                     p.stock_quantity,
                     p.initial_stock,
                     p.low_stock_threshold,
                     p.backorder_allowed,
                     -- Corrected SUM logic for movements (assuming quantity_change is negative for sales)
                     COALESCE(SUM(CASE WHEN m.type = 'sale' THEN ABS(m.quantity_change) ELSE 0 END), 0) as total_sales_units,
                     COALESCE(SUM(CASE WHEN m.type = 'return' THEN m.quantity_change ELSE 0 END), 0) as total_returns_units,
                     COALESCE(SUM(CASE WHEN m.type = 'restock' THEN m.quantity_change ELSE 0 END), 0) as total_restock_units,
                     COALESCE(SUM(CASE WHEN m.type = 'adjustment' THEN m.quantity_change ELSE 0 END), 0) as total_adjustment_units,
                     CASE
                         WHEN p.initial_stock > 0 THEN ROUND((p.stock_quantity / p.initial_stock) * 100, 2)
                         ELSE NULL -- Use NULL if initial stock is 0 or invalid
                     END as stock_percentage
                 FROM products p
                 LEFT JOIN inventory_movements m ON p.id = m.product_id
                 LEFT JOIN categories c ON p.category_id = c.id -- Join categories table
             ";

             if ($categoryId) {
                 $sql .= " WHERE p.category_id = ?";
                 $params[] = $categoryId; // Already validated if passed as int
             }

             $sql .= " GROUP BY p.id, c.name ORDER BY p.name ASC"; // Group by category name too, order by product name

             // Use $this->db
             $stmt = $this->db->prepare($sql);
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


    // --- adjustStockThreshold Method ---
     // Added type hints and PDO usage correction
     public function adjustStockThreshold(int $productId, int $threshold) {
         try {
             $this->requireAdmin();
             // Assuming called via AJAX POST
             $this->validateCSRF(); // Validate if called from a form/AJAX requiring CSRF

             if ($threshold < 0) { // Basic validation
                 throw new Exception('Threshold cannot be negative');
             }

             // Use $this->db
             $stmt = $this->db->prepare("
                 UPDATE products
                 SET low_stock_threshold = ?,
                     updated_at = NOW()
                 WHERE id = ?
             ");
             $stmt->execute([$threshold, $productId]);

             if ($stmt->rowCount() > 0) {
                  $this->logAuditTrail('stock_threshold_update', $this->getUserId(), ['product_id' => $productId, 'new_threshold' => $threshold]);
                  return $this->jsonResponse([
                     'success' => true,
                     'message' => 'Stock threshold updated successfully'
                  ]);
             } else {
                  // Product ID might not exist or threshold was already the same
                  // Check if product exists
                  $checkStmt = $this->db->prepare("SELECT id FROM products WHERE id = ?");
                  $checkStmt->execute([$productId]);
                  if (!$checkStmt->fetch()) {
                       throw new Exception('Product not found');
                  } else {
                       // Threshold was likely unchanged
                       return $this->jsonResponse([
                           'success' => true, // Or false depending on desired behavior
                           'message' => 'Stock threshold unchanged or product not found.'
                       ]);
                  }
             }

         } catch (Exception $e) {
             error_log("Error updating stock threshold for product {$productId}: " . $e->getMessage());
             return $this->jsonResponse([
                 'success' => false,
                 'message' => 'Failed to update stock threshold: ' . $e->getMessage()
             ], 500);
         }
     }


} // End of InventoryController class

```

# controllers/CouponController.php  
```php
<?php
require_once __DIR__ . '/BaseController.php';
// No need to require OrderModel etc. here if methods don't directly use them

class CouponController extends BaseController {
    // No direct need for $pdo property if inheriting from BaseController which has $this->db
    // private $pdo; // Remove this if using $this->db from BaseController

    public function __construct($pdo) {
        parent::__construct($pdo); // Pass PDO to BaseController constructor
        // $this->pdo = $pdo; // Remove this line, use $this->db instead
    }

    /**
     * Core validation logic for a coupon code.
     * Checks active status, dates, usage limits, minimum purchase.
     * Does NOT check user-specific usage here.
     *
     * @param string $code
     * @param float $subtotal
     * @return array ['valid' => bool, 'message' => string, 'coupon' => array|null]
     */
    public function validateCouponCodeOnly(string $code, float $subtotal): array {
        $code = $this->validateInput($code, 'string'); // Already validated? Double check is ok.
        $subtotal = $this->validateInput($subtotal, 'float');

        if (!$code || $subtotal === false || $subtotal < 0) {
            return ['valid' => false, 'message' => 'Invalid coupon code or subtotal amount.', 'coupon' => null];
        }

        try {
            // Use $this->db (from BaseController)
            $stmt = $this->db->prepare("
                SELECT * FROM coupons
                WHERE code = ?
                AND is_active = TRUE
                AND (valid_from IS NULL OR valid_from <= CURDATE()) -- Changed start_date/end_date to valid_from/valid_to based on sample schema if present, else adjust
                AND (valid_to IS NULL OR valid_to >= CURDATE())     -- Changed start_date/end_date to valid_from/valid_to
                AND (usage_limit IS NULL OR usage_count < usage_limit)
                AND (min_purchase_amount IS NULL OR min_purchase_amount <= ?) -- Check if min_purchase_amount is NULL too
            ");
            $stmt->execute([$code, $subtotal]);
            $coupon = $stmt->fetch();

            if (!$coupon) {
                 // More specific messages based on why it failed could be added here by checking coupon data if found but inactive/expired etc.
                return ['valid' => false, 'message' => 'Coupon is invalid, expired, or minimum spend not met.', 'coupon' => null];
            }

            // Coupon exists and meets basic criteria
            return ['valid' => true, 'message' => 'Coupon code is potentially valid.', 'coupon' => $coupon];

        } catch (Exception $e) {
            error_log("Coupon Code Validation DB Error: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Error validating coupon code.', 'coupon' => null];
        }
    }

    /**
     * Check if a specific user has already used a specific coupon.
     *
     * @param int $couponId
     * @param int $userId
     * @return bool True if used, False otherwise.
     */
    private function hasUserUsedCoupon(int $couponId, int $userId): bool {
        try {
            // Use $this->db
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM coupon_usage
                WHERE coupon_id = ? AND user_id = ?
            ");
            $stmt->execute([$couponId, $userId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
             error_log("Error checking user coupon usage: " . $e->getMessage());
             return true; // Fail safe - assume used if DB error occurs? Or false? Let's assume false to allow attempt.
        }
    }


    /**
     * Handles AJAX request from checkout page to validate a coupon.
     * Includes user-specific checks.
     * Returns JSON response for the frontend.
     */
    public function applyCouponAjax() {
        $this->requireLogin(); // Ensure user is logged in
        $this->validateCSRF(); // Validate CSRF from AJAX

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $code = $this->validateInput($data['code'] ?? null, 'string');
        $subtotal = $this->validateInput($data['subtotal'] ?? null, 'float');
        $userId = $this->getUserId();

        if (!$code || $subtotal === false || $subtotal < 0) {
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid coupon code or subtotal amount provided.'], 400);
        }

        // Step 1: Core validation
        $validationResult = $this->validateCouponCodeOnly($code, $subtotal);

        if (!$validationResult['valid']) {
             return $this->jsonResponse([
                 'success' => false,
                 'message' => $validationResult['message'] // Provide the specific validation message
             ]);
        }

        $coupon = $validationResult['coupon'];

        // Step 2: User-specific validation
        if ($this->hasUserUsedCoupon($coupon['id'], $userId)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'You have already used this coupon.'
            ]);
        }

        // Step 3: Calculate discount and return success
        $discountAmount = $this->calculateDiscount($coupon, $subtotal);

         // Recalculate totals needed for the response accurately
         $subtotalAfterDiscount = $subtotal - $discountAmount;
         $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
         // Tax requires shipping context - cannot reliably calculate here without client sending address.
         // Let's calculate final total based on discount + shipping, tax added client-side or later.
         $newTotal = $subtotalAfterDiscount + $shipping_cost; // Tax will be added later
         $newTotal = max(0, $newTotal); // Ensure non-negative

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Coupon applied successfully!',
            'coupon_code' => $coupon['code'], // Send back the code for display
            'discount_amount' => number_format($discountAmount, 2),
            // 'new_tax_amount' => number_format($tax_amount, 2), // Omit tax calculation here
            'new_total' => number_format($newTotal, 2) // Send the new total (excluding tax for now)
        ]);
    }


    /**
     * Records the usage of a coupon for a specific order and user.
     * Increments the coupon's usage count.
     * Should be called within a transaction if part of a larger process like checkout.
     *
     * @param int $couponId
     * @param int $orderId
     * @param int $userId
     * @param float $discountAmount
     * @return bool True on success, false on failure.
     */
    public function recordUsage(int $couponId, int $orderId, int $userId, float $discountAmount): bool {
         // This method assumes it might be called outside a pre-existing transaction,
         // so it starts its own. If called within CheckoutController's transaction,
         // PDO might handle nested transactions gracefully depending on driver,
         // but it's safer if CheckoutController manages the main transaction.
         // Let's remove the transaction here and assume CheckoutController handles it.
         // $this->beginTransaction(); // Removed

        try {
            // Validate input (basic checks)
             if ($couponId <= 0 || $orderId <= 0 || $userId <= 0 || $discountAmount < 0) {
                 throw new InvalidArgumentException('Invalid parameters for recording coupon usage.');
             }

             // Record usage in coupon_usage table
             $stmtUsage = $this->db->prepare("
                 INSERT INTO coupon_usage (coupon_id, order_id, user_id, discount_amount, used_at)
                 VALUES (?, ?, ?, ?, NOW())
             ");
             $usageInserted = $stmtUsage->execute([$couponId, $orderId, $userId, $discountAmount]);

             if (!$usageInserted) {
                 throw new Exception("Failed to insert into coupon_usage table.");
             }

             // Update usage_count in coupons table
             $stmtUpdate = $this->db->prepare("
                 UPDATE coupons
                 SET usage_count = usage_count + 1,
                     updated_at = NOW()
                 WHERE id = ?
             ");
             $countUpdated = $stmtUpdate->execute([$couponId]);

             if (!$countUpdated || $stmtUpdate->rowCount() === 0) {
                 // Don't throw an exception if the count update fails, but log it.
                 // The usage was recorded, which is the primary goal. Count mismatch can be fixed.
                 error_log("Warning: Failed to increment usage_count for coupon ID {$couponId} on order ID {$orderId}, but usage was recorded.");
             }

            // $this->commit(); // Removed - Rely on calling method's transaction
            return true;

        } catch (Exception $e) {
            // $this->rollback(); // Removed
            error_log("Coupon usage recording error for CouponID {$couponId}, OrderID {$orderId}: " . $e->getMessage());
            return false;
        }
    }


    // --- Admin Methods (kept largely original, ensure $this->db is used) ---

    /**
     * Calculates the discount amount based on coupon type and subtotal.
     *
     * @param array $coupon Coupon data array.
     * @param float $subtotal Order subtotal.
     * @return float Calculated discount amount.
     */
    public function calculateDiscount(array $coupon, float $subtotal): float { // Made public for CheckoutController
        $discountAmount = 0;

        if ($coupon['discount_type'] === 'percentage') {
            $discountAmount = $subtotal * ($coupon['discount_value'] / 100);
        } elseif ($coupon['discount_type'] === 'fixed') { // Explicitly check for 'fixed'
            $discountAmount = $coupon['discount_value'];
        } else {
             error_log("Unknown discount type '{$coupon['discount_type']}' for coupon ID {$coupon['id']}");
             return 0; // Return 0 for unknown types
        }

        // Apply maximum discount limit if set and numeric
        if (isset($coupon['max_discount_amount']) && is_numeric($coupon['max_discount_amount'])) {
            $discountAmount = min($discountAmount, (float)$coupon['max_discount_amount']);
        }

         // Ensure discount doesn't exceed subtotal (prevent negative totals from discount alone)
         $discountAmount = min($discountAmount, $subtotal);

        return round(max(0, $discountAmount), 2); // Ensure non-negative and round
    }

    // --- Admin CRUD methods ---
    // These methods are typically called from admin routes and might render views or return JSON.
    // Ensure they use $this->db, $this->requireAdmin(), $this->validateCSRF() appropriately.

    // Example: Method to display coupons list in Admin (Called by GET request in index.php)
     public function listCoupons() {
         $this->requireAdmin();
         try {
             // Fetch all coupons with usage stats
             $stmt = $this->db->query("
                 SELECT
                     c.*,
                     COUNT(cu.id) as total_uses,
                     COALESCE(SUM(cu.discount_amount), 0) as total_discount_given
                 FROM coupons c
                 LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id
                 GROUP BY c.id
                 ORDER BY c.created_at DESC
             ");
             $coupons = $stmt->fetchAll();

             // Prepare data for the view
             $data = [
                 'pageTitle' => 'Manage Coupons',
                 'coupons' => $coupons,
                 'csrfToken' => $this->generateCSRFToken(),
                 'bodyClass' => 'page-admin-coupons'
             ];
             // Render the admin view
             echo $this->renderView('admin/coupons', $data);

         } catch (Exception $e) {
             error_log("Error fetching coupons for admin: " . $e->getMessage());
             $this->setFlashMessage('Failed to load coupons.', 'error');
             // Redirect to admin dashboard or show error view
             $this->redirect('admin'); // Redirect to admin dashboard
         }
     }

     // Example: Show create form (Called by GET request in index.php)
     public function showCreateForm() {
          $this->requireAdmin();
          $data = [
               'pageTitle' => 'Create Coupon',
               'coupon' => null, // No existing coupon data
               'csrfToken' => $this->generateCSRFToken(),
               'bodyClass' => 'page-admin-coupon-form'
          ];
          echo $this->renderView('admin/coupon_form', $data); // Assume view exists
     }

     // Example: Show edit form (Called by GET request in index.php)
      public function showEditForm(int $id) {
          $this->requireAdmin();
          $stmt = $this->db->prepare("SELECT * FROM coupons WHERE id = ?");
          $stmt->execute([$id]);
          $coupon = $stmt->fetch();

          if (!$coupon) {
               $this->setFlashMessage('Coupon not found.', 'error');
               $this->redirect('admin&section=coupons');
               return;
          }

          $data = [
               'pageTitle' => 'Edit Coupon',
               'coupon' => $coupon,
               'csrfToken' => $this->generateCSRFToken(),
               'bodyClass' => 'page-admin-coupon-form'
           ];
           echo $this->renderView('admin/coupon_form', $data); // Assume view exists
      }

     // Example: Save coupon (Called by POST request in index.php)
      public function saveCoupon() {
           $this->requireAdmin();
           $this->validateCSRF(); // Validates POST CSRF

           $couponId = $this->validateInput($_POST['coupon_id'] ?? null, 'int');
           // Extract and validate all other POST data similar to createCoupon below
           $data = [
                'code' => $this->validateInput($_POST['code'] ?? null, 'string', ['min' => 3, 'max' => 50]), // Add length validation
                'description' => $this->validateInput($_POST['description'] ?? null, 'string', ['max' => 255]),
                'discount_type' => $this->validateInput($_POST['discount_type'] ?? null, 'string'),
                'discount_value' => $this->validateInput($_POST['discount_value'] ?? null, 'float'),
                'min_purchase_amount' => $this->validateInput($_POST['min_purchase_amount'] ?? 0, 'float', ['min' => 0]),
                'max_discount_amount' => $this->validateInput($_POST['max_discount_amount'] ?? null, 'float', ['min' => 0]),
                'valid_from' => $this->validateInput($_POST['valid_from'] ?? null, 'date'), // Basic date check
                'valid_to' => $this->validateInput($_POST['valid_to'] ?? null, 'date'),
                'usage_limit' => $this->validateInput($_POST['usage_limit'] ?? null, 'int', ['min' => 0]),
                'is_active' => isset($_POST['is_active']) ? 1 : 0 // Convert checkbox to 1 or 0
           ];

           // --- Basic Server-side Validation ---
           if (!$data['code'] || !$data['discount_type'] || $data['discount_value'] === false || $data['discount_value'] <= 0) {
                $this->setFlashMessage('Missing required fields (Code, Type, Value).', 'error');
                $this->redirect('admin&section=coupons' . ($couponId ? '&task=edit&id='.$couponId : '&task=create'));
                return;
           }
            if (!in_array($data['discount_type'], ['percentage', 'fixed'])) {
                 $this->setFlashMessage('Invalid discount type.', 'error');
                 $this->redirect('admin&section=coupons' . ($couponId ? '&task=edit&id='.$couponId : '&task=create'));
                 return;
            }
            if ($data['discount_type'] === 'percentage' && ($data['discount_value'] > 100)) {
                 $this->setFlashMessage('Percentage discount cannot exceed 100.', 'error');
                 $this->redirect('admin&section=coupons' . ($couponId ? '&task=edit&id='.$couponId : '&task=create'));
                 return;
            }
            // --- End Validation ---

           try {
                $this->beginTransaction();

                // Check for duplicate code if creating or changing code
                $checkStmt = $this->db->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
                $checkStmt->execute([$data['code'], $couponId ?: 0]);
                if ($checkStmt->fetch()) {
                    throw new Exception('Coupon code already exists.');
                }


                if ($couponId) {
                    // Update existing coupon
                    $stmt = $this->db->prepare("
                        UPDATE coupons SET
                        code = ?, description = ?, discount_type = ?, discount_value = ?,
                        min_purchase_amount = ?, max_discount_amount = ?, valid_from = ?, valid_to = ?,
                        usage_limit = ?, is_active = ?, updated_at = NOW(), updated_by = ?
                        WHERE id = ?
                    ");
                     $success = $stmt->execute([
                          $data['code'], $data['description'], $data['discount_type'], $data['discount_value'],
                          $data['min_purchase_amount'], $data['max_discount_amount'] ?: null, $data['valid_from'] ?: null, $data['valid_to'] ?: null,
                          $data['usage_limit'] ?: null, $data['is_active'], $this->getUserId(), $couponId
                     ]);
                     $message = 'Coupon updated successfully.';
                } else {
                    // Create new coupon
                    $stmt = $this->db->prepare("
                         INSERT INTO coupons (
                             code, description, discount_type, discount_value, min_purchase_amount,
                             max_discount_amount, valid_from, valid_to, usage_limit, is_active,
                             created_by, updated_by
                         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                     ");
                      $userId = $this->getUserId();
                      $success = $stmt->execute([
                           $data['code'], $data['description'], $data['discount_type'], $data['discount_value'], $data['min_purchase_amount'],
                           $data['max_discount_amount'] ?: null, $data['valid_from'] ?: null, $data['valid_to'] ?: null, $data['usage_limit'] ?: null, $data['is_active'],
                           $userId, $userId
                      ]);
                      $message = 'Coupon created successfully.';
                }

                if (!$success) {
                     throw new Exception("Database operation failed.");
                }

                $this->commit();
                $this->setFlashMessage($message, 'success');

           } catch (Exception $e) {
                $this->rollback();
                error_log("Coupon save error: " . $e->getMessage());
                $this->setFlashMessage('Failed to save coupon: ' . $e->getMessage(), 'error');
           }

           // Redirect back to coupon list
            $this->redirect('admin&section=coupons');
      }

     // Example: Toggle Status (Called by POST request in index.php)
     public function toggleCouponStatus(int $id) {
           $this->requireAdmin();
           $this->validateCSRF(); // CSRF for state-changing action

           try {
                $stmt = $this->db->prepare("UPDATE coupons SET is_active = !is_active, updated_at = NOW(), updated_by = ? WHERE id = ?");
                $success = $stmt->execute([$this->getUserId(), $id]);

                if ($success && $stmt->rowCount() > 0) {
                     return $this->jsonResponse(['success' => true, 'message' => 'Coupon status toggled.']);
                } else {
                     return $this->jsonResponse(['success' => false, 'message' => 'Coupon not found or status unchanged.'], 404);
                }
           } catch (Exception $e) {
                error_log("Coupon toggle error: " . $e->getMessage());
                return $this->jsonResponse(['success' => false, 'message' => 'Failed to toggle coupon status.'], 500);
           }
     }

     // Example: Delete Coupon (Called by POST request in index.php)
     public function deleteCoupon(int $id) {
           $this->requireAdmin();
           $this->validateCSRF(); // CSRF for state-changing action

           try {
                $this->beginTransaction();
                // Optionally delete usage records first or handle via foreign key constraint
                $stmtUsage = $this->db->prepare("DELETE FROM coupon_usage WHERE coupon_id = ?");
                $stmtUsage->execute([$id]);

                $stmt = $this->db->prepare("DELETE FROM coupons WHERE id = ?");
                $success = $stmt->execute([$id]);

                if ($success && $stmt->rowCount() > 0) {
                     $this->commit();
                     return $this->jsonResponse(['success' => true, 'message' => 'Coupon deleted successfully.']);
                } else {
                     $this->rollback();
                     return $this->jsonResponse(['success' => false, 'message' => 'Coupon not found.'], 404);
                }
           } catch (Exception $e) {
                $this->rollback();
                error_log("Coupon delete error: " . $e->getMessage());
                return $this->jsonResponse(['success' => false, 'message' => 'Failed to delete coupon.'], 500);
           }
     }

}

```

