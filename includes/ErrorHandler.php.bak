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