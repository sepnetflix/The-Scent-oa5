# includes/SecurityMiddleware.php  
```php
<?php

class SecurityMiddleware {
    private static $ipTracker = [];
    private static $requestTracker = [];
    private static $encryptionKey;

    public static function apply() {
        // Set security headers
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");
	// TEMPORARRILY disabled
	// header("Content-Security-Policy: default-src 'self'; script-src 'self' https://js.stripe.com 'unsafe-inline'; style-src 'self' 'unsafe-inline'; frame-src https://js.stripe.com; img-src 'self' data: https:; connect-src 'self' https://api.stripe.com");
        
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
    
    public static function preventSQLInjection($value) {
        if (is_array($value)) {
            return array_map([self::class, 'preventSQLInjection'], $value);
        }
        
        if (is_string($value)) {
            // Remove common SQL injection patterns
            $patterns = [
                '/\bUNION\b/i',
                '/\bSELECT\b/i',
                '/\bINSERT\b/i',
                '/\bUPDATE\b/i',
                '/\bDELETE\b/i',
                '/\bDROP\b/i',
                '/\bTRUNCATE\b/i',
                '/\bOR\b\s+\d+\s*[=<>]/i',
                '/\bAND\b\s+\d+\s*[=<>]/i'
            ];
            
            $value = preg_replace($patterns, '', $value);
            return addslashes($value);
        }
        
        return $value;
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

# controllers/ProductController.php  
```php
<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';

class ProductController extends BaseController {
    private $productModel;
    private $itemsPerPage = 12;
    private $cache = [];
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
    }
    
    public function showHomePage() {
        try {
            $featuredProducts = $this->productModel->getFeatured();
            
            if (empty($featuredProducts)) {
                error_log("No featured products found");
            }
            
            $csrfToken = $this->getCsrfToken();
            require_once __DIR__ . '/../views/home.php';
        } catch (Exception $e) {
            error_log("Error in showHomePage: " . $e->getMessage());
            $this->setFlashMessage('An error occurred while loading the page', 'error');
            $this->redirect('error');
        }
    }
    
    public function showProductList() {
        try {
            // Validate and sanitize inputs
            $page = max(1, (int)($this->validateInput($_GET['page'] ?? 1, 'int')));
            $searchQuery = $this->validateInput($_GET['search'] ?? '', 'string');
            $categoryId = $this->validateInput($_GET['category'] ?? '', 'int');
            $sortBy = $this->validateInput($_GET['sort'] ?? 'name_asc', 'string');
            $minPrice = $this->validateInput($_GET['min_price'] ?? null, 'float');
            $maxPrice = $this->validateInput($_GET['max_price'] ?? null, 'float');
            
            // Calculate pagination
            $offset = ($page - 1) * $this->itemsPerPage;
            
            // Get products based on filters
            $conditions = [];
            $params = [];
            
            if ($searchQuery) {
                $conditions[] = "(name LIKE ? OR description LIKE ?)";
                $params[] = "%{$searchQuery}%";
                $params[] = "%{$searchQuery}%";
            }
            
            if ($categoryId) {
                $conditions[] = "category_id = ?";
                $params[] = $categoryId;
            }
            
            if ($minPrice !== null) {
                $conditions[] = "price >= ?";
                $params[] = $minPrice;
            }
            
            if ($maxPrice !== null) {
                $conditions[] = "price <= ?";
                $params[] = $maxPrice;
            }
            
            // Get total count for pagination
            $totalProducts = $this->productModel->getCount($conditions, $params);
            $totalPages = ceil($totalProducts / $this->itemsPerPage);
            
            // Get paginated products
            $products = $this->productModel->getFiltered(
                $conditions,
                $params,
                $sortBy,
                $this->itemsPerPage,
                $offset
            );
            
            // Get categories for filter menu
            $categories = $this->productModel->getAllCategories();
            
            // Set page title
            $pageTitle = $searchQuery ? 
                "Search Results for \"" . htmlspecialchars($searchQuery) . "\"" : 
                ($categoryId ? "Category Products" : "All Products");
            
            require_once __DIR__ . '/../views/products.php';
            
        } catch (Exception $e) {
            error_log("Error loading product list: " . $e->getMessage());
            $this->setFlashMessage('Error loading products', 'error');
            $this->redirect('error');
        }
    }
    
    public function showProduct($id) {
        try {
            $id = $this->validateInput($id, 'int');
            if (!$id) {
                throw new Exception('Invalid product ID');
            }
            
            // Check cache
            $cacheKey = "product_{$id}";
            if (!isset($this->cache[$cacheKey])) {
                $this->cache[$cacheKey] = $this->productModel->getById($id);
            }
            
            $product = $this->cache[$cacheKey];
            
            if (!$product) {
                require_once __DIR__ . '/../views/404.php';
                return;
            }
            
            // Use category_id for related products
            $categoryId = isset($product['category_id']) ? $product['category_id'] : null;
            $relatedProducts = [];
            if ($categoryId) {
                $relatedProducts = $this->productModel->getRelated($categoryId, $id, 4);
            }
            
            $csrfToken = $this->getCsrfToken();
            require_once __DIR__ . '/../views/product_detail.php';
        } catch (Exception $e) {
            error_log("Error loading product details: " . $e->getMessage());
            $this->setFlashMessage('Error loading product details', 'error');
            $this->redirect('products');
        }
    }
    
    public function createProduct() {
        try {
            $this->requireAdmin();
            $this->validateCSRF();
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = [
                    'name' => $this->validateInput($_POST['name'], 'string'),
                    'description' => $this->validateInput($_POST['description'], 'string'),
                    'price' => $this->validateInput($_POST['price'], 'float'),
                    'category' => $this->validateInput($_POST['category'], 'string'),
                    'image_url' => $this->validateInput($_POST['image_url'], 'url'),
                    'stock_quantity' => $this->validateInput($_POST['stock_quantity'] ?? 0, 'int'),
                    'low_stock_threshold' => $this->validateInput($_POST['low_stock_threshold'] ?? 5, 'int'),
                    'featured' => isset($_POST['featured']) ? 1 : 0,
                    'created_by' => $this->getUserId()
                ];
                
                // Validate required fields
                foreach (['name', 'price', 'category'] as $field) {
                    if (empty($data[$field])) {
                        throw new Exception("Missing required field: {$field}");
                    }
                }
                
                $this->beginTransaction();
                
                $productId = $this->productModel->create($data);
                
                if ($productId) {
                    // Clear cache
                    $this->clearProductCache();
                    
                    $this->commit();
                    $this->setFlashMessage('Product created successfully', 'success');
                    $this->redirect('admin/products');
                }
            }
            
            $categories = $this->productModel->getAllCategories();
            require_once __DIR__ . '/../views/admin/product_form.php';
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error creating product: " . $e->getMessage());
            $this->setFlashMessage($e->getMessage(), 'error');
            $this->redirect('admin/products/create');
        }
    }
    
    public function updateProduct($id) {
        try {
            $this->requireAdmin();
            $this->validateCSRF();
            
            $id = $this->validateInput($id, 'int');
            if (!$id) {
                throw new Exception('Invalid product ID');
            }
            
            $product = $this->productModel->getById($id);
            if (!$product) {
                throw new Exception('Product not found');
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = [
                    'name' => $this->validateInput($_POST['name'], 'string'),
                    'description' => $this->validateInput($_POST['description'], 'string'),
                    'price' => $this->validateInput($_POST['price'], 'float'),
                    'category' => $this->validateInput($_POST['category'], 'string'),
                    'image_url' => $this->validateInput($_POST['image_url'], 'url'),
                    'stock_quantity' => $this->validateInput($_POST['stock_quantity'] ?? 0, 'int'),
                    'low_stock_threshold' => $this->validateInput($_POST['low_stock_threshold'] ?? 5, 'int'),
                    'featured' => isset($_POST['featured']) ? 1 : 0,
                    'updated_by' => $this->getUserId()
                ];
                
                $this->beginTransaction();
                
                if ($this->productModel->update($id, $data)) {
                    // Clear cache
                    $this->clearProductCache();
                    
                    $this->commit();
                    $this->setFlashMessage('Product updated successfully', 'success');
                    $this->redirect('admin/products');
                }
            }
            
            $categories = $this->productModel->getAllCategories();
            require_once __DIR__ . '/../views/admin/product_form.php';
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error updating product: " . $e->getMessage());
            $this->setFlashMessage($e->getMessage(), 'error');
            $this->redirect("admin/products/edit/{$id}");
        }
    }
    
    public function deleteProduct($id) {
        try {
            $this->requireAdmin();
            $this->validateCSRF();
            
            $id = $this->validateInput($id, 'int');
            if (!$id) {
                throw new Exception('Invalid product ID');
            }
            
            $this->beginTransaction();
            
            if ($this->productModel->delete($id)) {
                // Clear cache
                $this->clearProductCache();
                
                $this->commit();
                $this->setFlashMessage('Product deleted successfully', 'success');
            }
            
            $this->redirect('admin/products');
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error deleting product: " . $e->getMessage());
            $this->setFlashMessage($e->getMessage(), 'error');
            $this->redirect('admin/products');
        }
    }
    
    private function clearProductCache() {
        $this->cache = [];
    }
    
    public function searchProducts() {
        try {
            $query = $this->validateInput($_GET['q'] ?? '', 'string');
            if (strlen($query) < 2) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Search query too short'
                ], 400);
            }
            
            $results = $this->productModel->search($query, 10);
            
            return $this->jsonResponse([
                'success' => true,
                'results' => $results
            ]);
            
        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error performing search'
            ], 500);
        }
    }
    
    public function getProduct($id) {
        try {
            return $this->productModel->getById($id);
        } catch (Exception $e) {
            error_log("Error getting product: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getAllProducts() {
        try {
            return $this->productModel->getAll();
        } catch (Exception $e) {
            error_log("Error getting all products: " . $e->getMessage());
            throw $e;
        }
    }
}

```

