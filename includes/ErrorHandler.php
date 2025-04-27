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
