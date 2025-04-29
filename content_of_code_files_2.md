# controllers/CartController.php  
```php
<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Cart.php';

class CartController extends BaseController {
    private Product $productModel; // Use type hint
    private ?Cart $cartModel = null; // Allow null, use type hint
    private bool $isLoggedIn = false; // Use type hint
    private ?int $userId = null; // Allow null, use type hint

    public function __construct(PDO $pdo) { // Use type hint
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);

        // Ensure session is started before accessing $_SESSION
        if (session_status() === PHP_SESSION_NONE) {
             session_start();
        }

        // Check login status using BaseController method for consistency, if available, otherwise use session directly
        // Assuming BaseController doesn't have an isActiveLogin check, use session directly
        $this->userId = $_SESSION['user_id'] ?? null; // More direct check
        $this->isLoggedIn = ($this->userId !== null); // Set boolean based on userId

        if ($this->isLoggedIn) {
            // Ensure Cart model is loaded
            if (!class_exists('Cart')) require_once __DIR__ . '/../models/Cart.php';
            $this->cartModel = new Cart($pdo, $this->userId);
        } else {
            $this->initCart(); // Ensures session cart exists for guests
        }
    }

    private function initCart(): void { // Add return type hint
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
         // Ensure session cart count exists for guests
         if (!isset($_SESSION['cart_count'])) {
             $_SESSION['cart_count'] = 0;
         }
    }

    // Static method called during login process in AccountController
    public static function mergeSessionCartOnLogin(PDO $pdo, int $userId): void { // Added type hints
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
             session_start();
        }
        if (!empty($_SESSION['cart'])) {
            // Ensure Cart model is loaded if called statically
            if (!class_exists('Cart')) {
                require_once __DIR__ . '/../models/Cart.php';
            }
            $cartModel = new Cart($pdo, $userId);
            $cartModel->mergeSessionCart($_SESSION['cart']);
        }
        // Always clear session cart after merging attempt
        $_SESSION['cart'] = [];
        $_SESSION['cart_count'] = 0; // Reset guest count
        // Optionally, immediately fetch and set the DB cart count in session here
        if (isset($cartModel) && method_exists($cartModel, 'getCartCount')) {
             $_SESSION['cart_count'] = $cartModel->getCartCount();
        }
    }


    public function showCart() {
        $cartItems = [];
        $total = 0.0; // Initialize as float
        $cartCount = 0; // Initialize count

        if ($this->isLoggedIn && $this->cartModel) { // Check if cartModel is initialized
            // Fetch items for logged-in user
            $items = $this->cartModel->getItems();
            foreach ($items as $item) {
                // Ensure required keys exist before calculation
                $price = $item['price'] ?? 0;
                $quantity = $item['quantity'] ?? 0;
                $subtotal = $price * $quantity;

                $cartItems[] = [
                    'product' => $item, // Assumes getItems joins product data
                    'quantity' => $quantity,
                    'subtotal' => $subtotal
                ];
                $total += $subtotal;
                $cartCount += $quantity;
            }
            // Update session count for logged-in user for consistency
             $_SESSION['cart_count'] = $cartCount;
        } else {
            // Fetch items for guest from session
            $this->initCart(); // Ensure session cart array exists
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $product = $this->productModel->getById($productId);
                if ($product) {
                     $price = $product['price'] ?? 0;
                     $subtotal = $price * $quantity;
                    $cartItems[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'subtotal' => $subtotal
                    ];
                    $total += $subtotal;
                    $cartCount += $quantity;
                } else {
                    // Product might have been deleted, remove from session cart
                    unset($_SESSION['cart'][$productId]);
                }
            }
             // Update session count for guest
             $_SESSION['cart_count'] = $cartCount;
        }

        // Prepare data for the view
        // --- FIX APPLIED HERE ---
        $csrfToken = $this->getCsrfToken(); // Use the correct BaseController method
        // --- END FIX ---
        $bodyClass = 'page-cart';
        $pageTitle = 'Your Shopping Cart';

        // Use renderView helper from BaseController
        echo $this->renderView('cart', [
            'cartItems' => $cartItems,
            'total' => $total,
            'csrfToken' => $csrfToken,
            'bodyClass' => $bodyClass,
            'pageTitle' => $pageTitle
        ]);
    }


    // --- AJAX Methods ---

    public function addToCart() {
        $this->validateCSRF(); // Use BaseController method
        $productId = $this->validateInput($_POST['product_id'] ?? null, 'int'); // Use BaseController helper
        $quantity = (int)$this->validateInput($_POST['quantity'] ?? 1, 'int'); // Use BaseController helper

        if (!$productId || $quantity < 1) {
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid product or quantity'], 400);
        }
        $product = $this->productModel->getById($productId);
        if (!$product) {
            return $this->jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
        }

        $currentQuantityInCart = 0;
        if ($this->isLoggedIn && $this->cartModel) {
             // Assuming Cart model has getItem($productId) or similar logic within getItems()
             $items = $this->cartModel->getItems();
             foreach ($items as $item) {
                 if ($item['product_id'] == $productId) {
                      $currentQuantityInCart = $item['quantity'];
                      break;
                 }
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
            $availableStock = $stockInfo ? max(0, $stockInfo['stock_quantity']) : 0;
            $message = $availableStock > 0 ? "Only {$availableStock} left in stock." : "Insufficient stock.";

            return $this->jsonResponse([
                'success' => false,
                'message' => $message,
                'cart_count' => $this->getCartCount(),
                'stock_status' => $stockStatus
            ], 400);
        }

        // Add item
        $cartCount = 0;
        if ($this->isLoggedIn && $this->cartModel) {
            $this->cartModel->addItem($productId, $quantity);
            $cartCount = $this->getCartCount(true); // Force DB count update
        } else {
            $this->initCart();
            $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
            $cartCount = array_sum($_SESSION['cart']);
            $_SESSION['cart_count'] = $cartCount;
        }

        // Check stock status *after* adding
        $stockInfo = $this->productModel->checkStock($productId);
        $stockStatus = 'in_stock';
        if ($stockInfo) {
             $finalCartQuantity = 0;
              if ($this->isLoggedIn && $this->cartModel) {
                  $items = $this->cartModel->getItems();
                  foreach ($items as $item) { if ($item['product_id'] == $productId) {$finalCartQuantity = $item['quantity']; break;} }
              } else {
                  $finalCartQuantity = $_SESSION['cart'][$productId] ?? 0;
              }
             $remainingStock = $stockInfo['stock_quantity'] - $finalCartQuantity;

             if (!$stockInfo['backorder_allowed'] && $remainingStock <= 0) {
                  $stockStatus = 'out_of_stock';
             } elseif ($stockInfo['low_stock_threshold'] && $remainingStock <= $stockInfo['low_stock_threshold']) {
                  $stockStatus = 'low_stock';
             }
        } else {
            $stockStatus = 'unknown';
        }

        // Use BaseController logging helper
        $this->logAuditTrail('cart_add', $this->userId, [
            'product_id' => $productId,
            'quantity' => $quantity,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);

        return $this->jsonResponse([
            'success' => true,
            'message' => htmlspecialchars($product['name']) . ' added to cart',
            'cart_count' => $cartCount,
            'stock_status' => $stockStatus
        ]);
    }

    public function updateCart() {
        $this->validateCSRF(); // Use BaseController method
        $updates = $_POST['updates'] ?? [];
        $stockErrors = [];
        $cartCount = 0;

        if ($this->isLoggedIn && $this->cartModel) {
            foreach ($updates as $productId => $quantity) {
                // Use BaseController validation helper
                $productId = $this->validateInput($productId, 'int');
                $quantity = (int)$this->validateInput($quantity, 'int');
                if ($productId === false || $quantity === false) continue;

                if ($quantity > 0) {
                    if (!$this->productModel->isInStock($productId, $quantity)) {
                        $product = $this->productModel->getById($productId);
                        // Use htmlspecialchars for output safety
                        $stockErrors[] = htmlspecialchars($product['name'] ?? "Product ID {$productId}") . " has insufficient stock";
                        continue;
                    }
                    $this->cartModel->updateItem($productId, $quantity);
                } else {
                    $this->cartModel->removeItem($productId);
                }
            }
            $cartCount = $this->getCartCount(true);
        } else {
            $this->initCart();
            foreach ($updates as $productId => $quantity) {
                 $productId = $this->validateInput($productId, 'int');
                 $quantity = (int)$this->validateInput($quantity, 'int');
                 if ($productId === false || $quantity === false) continue;

                if ($quantity > 0) {
                    if (!$this->productModel->isInStock($productId, $quantity)) {
                        $product = $this->productModel->getById($productId);
                         $stockErrors[] = htmlspecialchars($product['name'] ?? "Product ID {$productId}") . " has insufficient stock";
                        continue;
                    }
                    $_SESSION['cart'][$productId] = $quantity;
                } else {
                    unset($_SESSION['cart'][$productId]);
                }
            }
            $cartCount = array_sum($_SESSION['cart']);
            $_SESSION['cart_count'] = $cartCount;
        }

        // Use BaseController logging helper
        $this->logAuditTrail('cart_update', $this->userId, [
            'updates' => $updates,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);

        return $this->jsonResponse([
            'success' => empty($stockErrors),
            'message' => empty($stockErrors) ? 'Cart updated' : 'Some items have insufficient stock. Cart partially updated.',
            'cart_count' => $cartCount,
            'errors' => $stockErrors
        ]);
    }


    public function removeFromCart() {
        $this->validateCSRF(); // Use BaseController method
        $productId = $this->validateInput($_POST['product_id'] ?? null, 'int'); // Use BaseController helper
        if ($productId === false) {
             return $this->jsonResponse(['success' => false, 'message' => 'Invalid product ID'], 400);
        }

        $cartCount = 0;
        if ($this->isLoggedIn && $this->cartModel) {
            $this->cartModel->removeItem($productId);
            $cartCount = $this->getCartCount(true);
        } else {
            $this->initCart();
            unset($_SESSION['cart'][$productId]);
            $cartCount = array_sum($_SESSION['cart']);
            $_SESSION['cart_count'] = $cartCount;
        }

        // Use BaseController logging helper
        $this->logAuditTrail('cart_remove', $this->userId, [
            'product_id' => $productId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Product removed from cart',
            'cart_count' => $cartCount
        ]);
    }

     public function clearCart() {
        // Validate CSRF only if it's a POST request intended to clear via AJAX/Form
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCSRF();
        }

        $cartCount = 0;
        if ($this->isLoggedIn && $this->cartModel) {
            $this->cartModel->clearCart();
            // Count is implicitly 0
        } else {
            $this->initCart(); // Ensure session exists before clearing
            $_SESSION['cart'] = [];
            $_SESSION['cart_count'] = 0;
            // Count is 0
        }
         // Use BaseController logging helper
         $this->logAuditTrail('cart_clear', $this->userId, ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);

        // Respond based on request type
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Cart cleared',
                'cart_count' => $cartCount
            ]);
        } else {
             // For GET request (e.g., link click), redirect using BaseController helper
             $this->setFlashMessage('Cart cleared successfully.', 'success');
             $this->redirect('index.php?page=cart'); // Redirect to cart page
        }
    }

     /**
      * Helper to get cart count consistently.
      * @param bool $forceDbCheck Force fetching count from DB for logged-in users.
      * @return int
      */
     private function getCartCount(bool $forceDbCheck = false): int {
         if ($this->isLoggedIn && $this->cartModel) {
             // Optimization: Use session count if available and not forcing DB check
             if (!$forceDbCheck && isset($_SESSION['cart_count'])) {
                 // Ensure the session count is numeric before returning
                 return is_numeric($_SESSION['cart_count']) ? (int)$_SESSION['cart_count'] : 0;
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
         $subtotal = 0.0; // Use float
         $cartCount = 0;

         if ($this->isLoggedIn && $this->cartModel) {
             $cartItems = $this->cartModel->getItems();
             foreach ($cartItems as $item) {
                 $price = $item['price'] ?? 0;
                 $quantity = $item['quantity'] ?? 0;
                 $items[] = [
                     'product' => [ // Nest product data under 'product' key as expected by JS
                         'id' => $item['product_id'] ?? $item['id'], // Use correct ID key
                         'name' => $item['name'] ?? 'Unknown',
                         'image' => $item['image'] ?? '/images/placeholder.jpg',
                         'price' => $price
                     ],
                     'quantity' => $quantity
                 ];
                 $subtotal += $price * $quantity;
             }
             $cartCount = $this->getCartCount(true); // Force DB check
         } else {
             $this->initCart();
             foreach ($_SESSION['cart'] as $productId => $quantity) {
                 $product = $this->productModel->getById($productId);
                 if ($product) {
                      $price = $product['price'] ?? 0;
                     $items[] = [
                         'product' => [
                             'id' => $product['id'],
                             'name' => $product['name'] ?? 'Unknown',
                             'image' => $product['image'] ?? '/images/placeholder.jpg',
                             'price' => $price
                         ],
                         'quantity' => $quantity
                     ];
                     $subtotal += $price * $quantity;
                 }
             }
             $cartCount = $this->getCartCount();
         }

         return $this->jsonResponse([
             'success' => true,
             'items' => $items,
             'subtotal' => number_format($subtotal, 2), // Format for display consistency
             'cart_count' => $cartCount
         ]);
     }


     // validateCartStock and getCartItems remain largely the same, ensure validation is correct
     // Made public as used by CheckoutController potentially
     public function validateCartStock(): array {
         $errors = [];
         $cart = $this->getCartItemsInternal(); // Use internal helper

         if (empty($cart)) {
              return []; // Not an error if cart is empty
         }

         foreach ($cart as $item) {
             // Use $item['product']['id'] and $item['quantity']
             if (!$this->productModel->isInStock($item['product']['id'], $item['quantity'])) {
                 $errors[] = htmlspecialchars($item['product']['name'] ?? "Product ID {$item['product']['id']}") . " has insufficient stock";
             }
         }
         return $errors;
     }

      // Made public as used by CheckoutController
     public function getCartItems(): array {
         return $this->getCartItemsInternal(); // Use internal helper
     }

     // Internal helper to avoid code duplication between validateCartStock and getCartItems
     private function getCartItemsInternal(): array {
         $cartItems = [];
         if ($this->isLoggedIn && $this->cartModel) {
             $items = $this->cartModel->getItems();
             foreach ($items as $item) {
                 $price = $item['price'] ?? 0;
                 $quantity = $item['quantity'] ?? 0;
                 $cartItems[] = [
                     'product' => $item, // Assume getItems returns joined product data
                     'quantity' => $quantity,
                     'subtotal' => $price * $quantity
                 ];
             }
         } else {
             $this->initCart();
             foreach ($_SESSION['cart'] as $productId => $quantity) {
                 $product = $this->productModel->getById($productId);
                 if ($product) {
                      $price = $product['price'] ?? 0;
                     $cartItems[] = [
                         'product' => $product,
                         'quantity' => $quantity,
                         'subtotal' => $price * $quantity
                     ];
                 }
             }
         }
         return $cartItems;
     }

} // End of CartController class

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
                $this->logSecurityEvent('no_featured_products', null, ['ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
            }
            $csrfToken = $this->getCsrfToken();
            extract([
                'featuredProducts' => $featuredProducts,
                'csrfToken' => $csrfToken
            ]);
            require_once __DIR__ . '/../views/home.php';
        } catch (Exception $e) {
            $this->logSecurityEvent('error_show_home', null, ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
            $this->setFlashMessage('An error occurred while loading the page', 'error');
            $this->redirect('error');
        }
    }
    
    public function showProductList() {
        try {
            $page = 1;
            if (isset($_GET['page_num']) && is_numeric($_GET['page_num']) && (int)$_GET['page_num'] > 0) {
                $page = (int)$_GET['page_num'];
            }
            $categoryId = isset($_GET['category']) ? $this->validateInput($_GET['category'], 'int') : null;
            $sortBy = $this->validateInput($_GET['sort'] ?? 'name_asc', 'string');
            $minPrice = $this->validateInput($_GET['min_price'] ?? null, 'float');
            $maxPrice = $this->validateInput($_GET['max_price'] ?? null, 'float');
            // Calculate pagination
            $offset = ($page - 1) * $this->itemsPerPage;
            
            // Get products based on filters
            $conditions = [];
            $params = [];
            
            // Only add search condition if 'search' is present in GET and is not empty
            if (isset($_GET['search']) && trim($_GET['search']) !== '') {
                $searchQuery = $this->validateInput($_GET['search'], 'string');
                if (!empty($searchQuery)) {
                    $conditions[] = "(name LIKE ? OR description LIKE ?)";
                    $params[] = "%{$searchQuery}%";
                    $params[] = "%{$searchQuery}%";
                }
            } else {
                $searchQuery = '';
            }
            
            // Only add category filter if $categoryId is a valid, non-zero integer
            if ($categoryId !== null && $categoryId !== false && is_numeric($categoryId) && (int)$categoryId > 0) {
                $conditions[] = "category_id = ?";
                $params[] = (int)$categoryId;
            }
            
            // Only add min price filter if $minPrice is not null and is numeric
            if ($minPrice !== null && is_numeric($minPrice)) {
                $conditions[] = "price >= ?";
                $params[] = $minPrice;
            }
            
            // Only add max price filter if $maxPrice is not null and is numeric
            if ($maxPrice !== null && is_numeric($maxPrice)) {
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
            $categoryName = null;
            if ($categoryId) {
                foreach ($categories as $cat) {
                    if ($cat['id'] == $categoryId) {
                        $categoryName = $cat['name'];
                        break;
                    }
                }
            }
            $pageTitle = $searchQuery ?
                "Search Results for \"" . htmlspecialchars($searchQuery) . "\"" :
                ($categoryId ? ($categoryName ? htmlspecialchars($categoryName) . " Products" : "Category Products") : "All Products");
            
            $csrfToken = $this->getCsrfToken();
            // Prepare pagination data
            $paginationData = [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'baseUrl' => 'index.php?page=products'
            ];
            $queryParams = $_GET;
            unset($queryParams['page'], $queryParams['page_num']);
            if (!empty($queryParams)) {
                $paginationData['baseUrl'] .= '&' . http_build_query($queryParams);
            }
            extract([
                'products' => $products,
                'categories' => $categories,
                'csrfToken' => $csrfToken,
                'pageTitle' => $pageTitle,
                'searchQuery' => $searchQuery,
                'sortBy' => $sortBy,
                'paginationData' => $paginationData,
                'categoryId' => $categoryId ?? null
            ]);
            require_once __DIR__ . '/../views/products.php';
        } catch (Exception $e) {
            $this->logSecurityEvent('error_show_product_list', null, ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
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
                    
                    // Audit log for product creation
                    $userId = $this->getUserId();
                    $this->logAuditTrail('product_create', $userId, [
                        'product_id' => $productId,
                        'name' => $data['name'],
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
                    ]);
                    
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
                    
                    // Audit log for product update
                    $userId = $this->getUserId();
                    $this->logAuditTrail('product_update', $userId, [
                        'product_id' => $id,
                        'name' => $data['name'],
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
                    ]);
                    
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
                
                // Audit log for product deletion
                $userId = $this->getUserId();
                $this->logAuditTrail('product_delete', $userId, [
                    'product_id' => $id,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null
                ]);
                
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

# views/product_detail.php  
```php
<?php
// Enhanced product detail view with image path fix, robust data handling, AJAX add-to-cart, and modern layout
require_once __DIR__ . '/layout/header.php';
?>
<body class="page-product-detail">
<!-- Output CSRF token for JS (for AJAX add-to-cart) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
<section class="product-detail py-12 md:py-20 bg-white">
    <div class="container mx-auto px-4">
        <!-- Breadcrumbs -->
        <nav class="breadcrumb text-sm text-gray-600 mb-8" aria-label="Breadcrumb" data-aos="fade-down">
            <ol class="list-none p-0 inline-flex">
                <li class="flex items-center">
                    <a href="index.php?page=products" class="hover:text-primary">Products</a>
                </li>
                <?php if (!empty($product['category_name']) && !empty($product['category_id'])): ?>
                <li class="flex items-center">
                    <svg class="fill-current w-3 h-3 mx-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c-9.373-9.373-24.569-9.373-33.941 0L285.475 239.03c-9.373 9.373-9.373 24.569 0 33.941z"/></svg>
                    <a href="index.php?page=products&category=<?= urlencode($product['category_id']) ?>" class="hover:text-primary">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </a>
                </li>
                <?php endif; ?>
                <li class="flex items-center">
                    <svg class="fill-current w-3 h-3 mx-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c-9.373-9.373-24.569-9.373-33.941 0L285.475 239.03c-9.373 9.373-9.373 24.569 0 33.941z"/></svg>
                    <span class="text-gray-500"><?= htmlspecialchars($product['name'] ?? 'Product') ?></span>
                </li>
            </ol>
        </nav>
        <div class="product-container grid grid-cols-1 md:grid-cols-2 gap-12 items-start">
            <!-- Product Gallery -->
            <div class="product-gallery space-y-4" data-aos="fade-right">
                <div class="main-image relative overflow-hidden rounded-lg shadow-lg aspect-square">
                    <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
                         alt="<?= htmlspecialchars($product['name'] ?? 'Product') ?>"
                         id="mainImage" class="w-full h-full object-cover transition-transform duration-300 ease-in-out hover:scale-105">
                    <?php if (!empty($product['is_featured'])): ?>
                        <span class="absolute top-3 left-3 bg-accent text-white text-xs font-semibold px-3 py-1 rounded-full shadow">Featured</span>
                    <?php endif; ?>
                    <?php
                      $isOutOfStock = (!isset($product['stock_quantity']) || $product['stock_quantity'] <= 0) && empty($product['backorder_allowed']);
                      $isLowStock = !$isOutOfStock && isset($product['low_stock_threshold']) && isset($product['stock_quantity']) && $product['stock_quantity'] <= $product['low_stock_threshold'];
                    ?>
                    <?php if ($isOutOfStock): ?>
                        <span class="absolute top-3 right-3 bg-red-600 text-white text-xs font-semibold px-3 py-1 rounded-full shadow">Out of Stock</span>
                    <?php elseif ($isLowStock): ?>
                        <span class="absolute top-3 right-3 bg-yellow-500 text-white text-xs font-semibold px-3 py-1 rounded-full shadow">Low Stock</span>
                    <?php endif; ?>
                </div>
                <?php
                    $galleryImages = isset($product['gallery_images']) && is_array($product['gallery_images']) ? $product['gallery_images'] : [];
                ?>
                <?php if (!empty($galleryImages)): ?>
                    <div class="thumbnail-grid grid grid-cols-4 gap-3">
                        <div class="border-2 border-primary rounded overflow-hidden cursor-pointer aspect-square">
                            <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
                                 alt="View 1"
                                 class="w-full h-full object-cover active"
                                 onclick="updateMainImage(this)">
                        </div>
                        <?php foreach ($galleryImages as $index => $imagePath): ?>
                            <?php if (!empty($imagePath) && is_string($imagePath)): ?>
                            <div class="border rounded overflow-hidden cursor-pointer aspect-square">
                                <img src="<?= htmlspecialchars($imagePath) ?>"
                                     alt="View <?= $index + 2 ?>"
                                     class="w-full h-full object-cover"
                                     onclick="updateMainImage(this)">
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (!empty($product['image']) && $product['image'] !== '/images/placeholder.jpg'): ?>
                    <div class="thumbnail-grid grid grid-cols-4 gap-3">
                        <div class="border-2 border-primary rounded overflow-hidden cursor-pointer aspect-square">
                            <img src="<?= htmlspecialchars($product['image']) ?>" alt="View 1" class="w-full h-full object-cover active">
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Product Info -->
            <div class="product-info space-y-6" data-aos="fade-left">
                <h1 class="text-3xl md:text-4xl font-bold font-heading text-primary"><?= htmlspecialchars($product['name'] ?? 'Product Name Unavailable') ?></h1>
                <p class="text-2xl font-semibold text-accent font-accent">$<?= isset($product['price']) ? number_format($product['price'], 2) : 'N/A' ?></p>
                <?php if (!empty($product['short_description'])): ?>
                <p class="text-gray-700 text-lg"><?= nl2br(htmlspecialchars($product['short_description'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($product['description']) && (empty($product['short_description']) || $product['description'] !== $product['short_description'])): ?>
                <div class="prose max-w-none text-gray-600">
                    <?= nl2br(htmlspecialchars($product['description'])) ?>
                </div>
                <?php elseif (empty($product['short_description']) && empty($product['description'])): ?>
                <p class="text-gray-500 italic">No description available.</p>
                <?php endif; ?>
                <?php $benefits = isset($product['benefits']) && is_array($product['benefits']) ? $product['benefits'] : []; ?>
                <?php if (!empty($benefits)): ?>
                <div class="benefits border-t pt-4">
                    <h3 class="text-lg font-semibold mb-3 text-primary-dark">Benefits</h3>
                    <ul class="list-none space-y-1 text-gray-600">
                        <?php foreach ($benefits as $benefit): ?>
                            <?php if(!empty($benefit) && is_string($benefit)): ?>
                            <li class="flex items-start"><i class="fas fa-check-circle text-secondary mr-2 mt-1 flex-shrink-0"></i><span><?= htmlspecialchars($benefit) ?></span></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if (!empty($product['ingredients'])): ?>
                <div class="ingredients border-t pt-4">
                    <h3 class="text-lg font-semibold mb-3 text-primary-dark">Key Ingredients</h3>
                    <p class="text-gray-600"><?= nl2br(htmlspecialchars($product['ingredients'])) ?></p>
                </div>
                <?php endif; ?>
                <form class="add-to-cart-form space-y-4 border-t pt-6" action="index.php?page=cart&action=add" method="POST" id="product-detail-add-cart-form">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?? '' ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="flex items-center space-x-4">
                        <label for="quantity" class="font-semibold text-gray-700">Quantity:</label>
                        <div class="quantity-selector flex items-center border border-gray-300 rounded">
                            <button type="button" class="quantity-btn minus w-10 h-10 text-xl font-light text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out rounded-l" aria-label="Decrease quantity">-</button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= (!empty($product['backorder_allowed']) || !isset($product['stock_quantity'])) ? 99 : max(1, $product['stock_quantity']) ?>" class="w-16 h-10 text-center border-l border-r border-gray-300 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary" aria-label="Product quantity">
                            <button type="button" class="quantity-btn plus w-10 h-10 text-xl font-light text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out rounded-r" aria-label="Increase quantity">+</button>
                        </div>
                    </div>
                    <?php if (!$isOutOfStock): ?>
                        <button type="submit" class="btn btn-primary w-full py-3 text-lg add-to-cart"
                                data-product-id="<?= $product['id'] ?? '' ?>">
                            <i class="fas fa-shopping-cart mr-2"></i> Add to Cart
                        </button>
                        <?php if ($isLowStock): ?>
                            <p class="text-sm text-yellow-600 text-center mt-2">Limited quantity available!</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <button type="button" class="btn btn-disabled w-full py-3 text-lg" disabled>
                            <i class="fas fa-times-circle mr-2"></i> Out of Stock
                        </button>
                    <?php endif; ?>
                </form>
                <div class="additional-info flex flex-wrap justify-around border-t pt-6 text-center text-sm text-gray-600">
                    <div class="info-item p-2 w-1/3">
                        <i class="fas fa-shipping-fast text-2xl text-secondary mb-2 block"></i>
                        <span>Free shipping over $50</span>
                    </div>
                    <div class="info-item p-2 w-1/3">
                        <i class="fas fa-undo text-2xl text-secondary mb-2 block"></i>
                        <span>30-day returns</span>
                    </div>
                    <div class="info-item p-2 w-1/3">
                        <i class="fas fa-lock text-2xl text-secondary mb-2 block"></i>
                        <span>Secure checkout</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Product Details Tabs -->
        <div class="product-tabs mt-16 md:mt-24" data-aos="fade-up">
            <div class="tabs-header border-b border-gray-200 mb-8 flex space-x-8">
                <?php $hasDetails = !empty($product['size']) || !empty($product['scent_profile']) || !empty($product['origin']) || !empty($product['sku']); ?>
                <?php $hasUsage = !empty($product['usage_instructions']); ?>
                <?php $hasShipping = true; ?>
                <?php $hasReviews = true; ?>
                <?php $activeTab = $hasDetails ? 'details' : ($hasUsage ? 'usage' : ($hasShipping ? 'shipping' : 'reviews')); ?>
                <?php if ($hasDetails): ?>
                <button class="tab-btn py-3 px-1 border-b-2 text-lg font-medium focus:outline-none <?= $activeTab === 'details' ? 'text-primary border-primary' : 'text-gray-500 border-transparent hover:text-primary hover:border-gray-300' ?>" data-tab="details">Details</button>
                <?php endif; ?>
                <?php if ($hasUsage): ?>
                <button class="tab-btn py-3 px-1 border-b-2 text-lg font-medium focus:outline-none <?= $activeTab === 'usage' ? 'text-primary border-primary' : 'text-gray-500 border-transparent hover:text-primary hover:border-gray-300' ?>" data-tab="usage">How to Use</button>
                <?php endif; ?>
                <?php if ($hasShipping): ?>
                <button class="tab-btn py-3 px-1 border-b-2 text-lg font-medium focus:outline-none <?= $activeTab === 'shipping' ? 'text-primary border-primary' : 'text-gray-500 border-transparent hover:text-primary hover:border-gray-300' ?>" data-tab="shipping">Shipping</button>
                <?php endif; ?>
                <?php if ($hasReviews): ?>
                <button class="tab-btn py-3 px-1 border-b-2 text-lg font-medium focus:outline-none <?= $activeTab === 'reviews' ? 'text-primary border-primary' : 'text-gray-500 border-transparent hover:text-primary hover:border-gray-300' ?>" data-tab="reviews">Reviews</button>
                <?php endif; ?>
            </div>
            <div class="tab-content min-h-[200px]">
                <div id="details" class="tab-pane prose max-w-none text-gray-700 <?= $activeTab === 'details' ? 'active' : '' ?>">
                    <?php if ($hasDetails): ?>
                        <h3 class="text-xl font-semibold mb-4 text-primary-dark sr-only">Product Details</h3>
                        <table class="details-table w-full text-left">
                            <tbody>
                                <?php if (!empty($product['size'])): ?>
                                <tr class="border-b border-gray-200"><th class="py-2 pr-4 font-medium text-gray-600 w-1/4">Size</th><td class="py-2"><?= htmlspecialchars($product['size']) ?></td></tr>
                                <?php endif; ?>
                                <?php if (!empty($product['scent_profile'])): ?>
                                <tr class="border-b border-gray-200"><th class="py-2 pr-4 font-medium text-gray-600 w-1/4">Scent Profile</th><td class="py-2"><?= htmlspecialchars($product['scent_profile']) ?></td></tr>
                                <?php endif; ?>
                                <?php if (!empty($product['origin'])): ?>
                                <tr class="border-b border-gray-200"><th class="py-2 pr-4 font-medium text-gray-600 w-1/4">Origin</th><td class="py-2"><?= htmlspecialchars($product['origin']) ?></td></tr>
                                <?php endif; ?>
                                <?php if (!empty($product['sku'])): ?>
                                <tr class="border-b border-gray-200"><th class="py-2 pr-4 font-medium text-gray-600 w-1/4">SKU</th><td class="py-2"><?= htmlspecialchars($product['sku']) ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php if (!empty($product['short_description']) && !empty($product['description']) && $product['description'] !== $product['short_description']): ?>
                        <div class="mt-6">
                            <h4 class="text-lg font-semibold mb-2 text-primary-dark">Full Description</h4>
                            <?= nl2br(htmlspecialchars($product['description'])) ?>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-gray-500 italic">Detailed specifications not available.</p>
                    <?php endif; ?>
                </div>
                <div id="usage" class="tab-pane prose max-w-none text-gray-700 <?= $activeTab === 'usage' ? 'active' : '' ?>">
                    <h3 class="text-xl font-semibold mb-4 text-primary-dark sr-only">How to Use</h3>
                    <?php if ($hasUsage): ?>
                        <?= nl2br(htmlspecialchars($product['usage_instructions'])) ?>
                    <?php else: ?>
                        <p class="text-gray-500 italic">Usage instructions are not available for this product.</p>
                    <?php endif; ?>
                </div>
                <div id="shipping" class="tab-pane prose max-w-none text-gray-700 <?= $activeTab === 'shipping' ? 'active' : '' ?>">
                    <h3 class="text-xl font-semibold mb-4 text-primary-dark sr-only">Shipping Information</h3>
                    <div class="shipping-info">
                        <p><strong>Free Standard Shipping</strong> on orders over $50.</p>
                        <ul class="list-disc list-inside space-y-1 my-4">
                            <li>Standard Shipping (5-7 business days): $5.99</li>
                            <li>Express Shipping (2-3 business days): $12.99</li>
                            <li>Next Day Delivery (order before 2pm): $19.99</li>
                        </ul>
                        <p>We ship with care to ensure your products arrive safely. Tracking information will be provided once your order ships. International shipping available to select countries (rates calculated at checkout).</p>
                        <p class="mt-4"><a href="index.php?page=shipping" class="text-primary hover:underline">View Full Shipping Policy</a></p>
                    </div>
                </div>
                <div id="reviews" class="tab-pane <?= $activeTab === 'reviews' ? 'active' : '' ?>">
                    <h3 class="text-xl font-semibold mb-4 text-primary-dark sr-only">Customer Reviews</h3>
                    <div class="reviews-summary mb-8 p-6 bg-light rounded-lg flex flex-col sm:flex-row items-center gap-6">
                        <div class="average-rating text-center">
                            <span class="block text-4xl font-bold text-accent">N/A</span>
                            <div class="stars text-gray-300 text-xl my-1" title="No reviews yet">
                                <i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                            </div>
                            <span class="text-sm text-gray-600">Based on 0 reviews</span>
                        </div>
                        <div class="flex-grow text-center sm:text-left">
                            <p class="mb-3 text-gray-700">Share your thoughts with other customers!</p>
                            <button class="btn btn-secondary">Write a Review</button>
                        </div>
                    </div>
                    <div class="reviews-list space-y-6">
                        <p class="text-center text-gray-500 italic py-4">There are no reviews for this product yet.</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
            <div class="related-products mt-16 md:mt-24 border-t border-gray-200 pt-12" data-aos="fade-up">
                <h2 class="text-3xl font-bold text-center mb-12 font-heading text-primary">You May Also Like</h2>
                <div class="products-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                        <div class="product-card sample-card bg-white rounded-lg shadow-md overflow-hidden transition-shadow duration-300 hover:shadow-xl flex flex-col">
                            <div class="product-image relative h-64 overflow-hidden">
                                <a href="index.php?page=product&id=<?= $relatedProduct['id'] ?? '' ?>">
                                    <img src="<?= htmlspecialchars($relatedProduct['image'] ?? '/images/placeholder.jpg') ?>"
                                         alt="<?= htmlspecialchars($relatedProduct['name'] ?? 'Product') ?>" class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                                </a>
                                <?php if (!empty($relatedProduct['is_featured'])): ?>
                                    <span class="absolute top-2 left-2 bg-accent text-white text-xs font-semibold px-2 py-0.5 rounded-full">Featured</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info p-4 flex flex-col flex-grow text-center">
                                <h3 class="text-lg font-semibold mb-1 font-heading text-primary hover:text-accent">
                                    <a href="index.php?page=product&id=<?= $relatedProduct['id'] ?? '' ?>">
                                        <?= htmlspecialchars($relatedProduct['name'] ?? 'Product') ?>
                                    </a>
                                </h3>
                                <?php if (!empty($relatedProduct['category_name'])): ?>
                                    <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($relatedProduct['category_name']) ?></p>
                                <?php endif; ?>
                                <p class="price text-base font-semibold text-accent mb-4 mt-auto">$<?= isset($relatedProduct['price']) ? number_format($relatedProduct['price'], 2) : 'N/A' ?></p>
                                <div class="product-actions mt-auto">
                                    <?php $relatedIsOutOfStock = (!isset($relatedProduct['stock_quantity']) || $relatedProduct['stock_quantity'] <= 0) && empty($relatedProduct['backorder_allowed']); ?>
                                    <?php if (!$relatedIsOutOfStock): ?>
                                        <button class="btn btn-secondary add-to-cart-related w-full"
                                                data-product-id="<?= $relatedProduct['id'] ?? '' ?>">
                                            Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-disabled w-full" disabled>Out of Stock</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

# views/login.php  
```php
<?php
// views/login.php (Updated)
$pageTitle = 'Login - The Scent';
// Apply consistent gradient background and page identifier class
$bodyClass = 'page-login bg-gradient-to-br from-light to-secondary/20';

require_once __DIR__ . '/layout/header.php'; // Includes CSRF token output globally
?>

<section class="auth-section flex items-center justify-center min-h-[calc(100vh-80px)] py-12 px-4">
    <div class="container max-w-md mx-auto">
        <div class="auth-container bg-white p-8 md:p-12 rounded-xl shadow-2xl" data-aos="fade-up" data-aos-delay="100">
            <div class="text-center mb-10">
                <h1 class="text-3xl lg:text-4xl font-bold font-heading text-primary mb-3">Welcome Back</h1>
                <p class="text-gray-600 font-body">Log in to continue your journey with The Scent.</p>
            </div>

            <?php // Standard Flash Message Display (from header or dynamic)
                // This relies on the flash message container in the header/footer layout
            ?>
            <?php if (isset($_SESSION['flash_message'])): ?>
                <script>
                    // Use the JS function immediately if available, or queue it
                    document.addEventListener('DOMContentLoaded', function() {
                        if (typeof window.showFlashMessage === 'function') {
                            window.showFlashMessage(<?= json_encode($_SESSION['flash_message']) ?>, <?= json_encode($_SESSION['flash_type'] ?? 'info') ?>);
                        }
                    });
                </script>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>


            <form action="index.php?page=login" method="POST" class="auth-form space-y-6" id="loginForm">
                 <!-- CSRF Token is handled globally by JS reading #csrf-token-value -->
                <div class="form-group">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1 font-body">Email Address</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out font-body"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="you@example.com">
                </div>

                <div class="form-group">
                    <div class="flex justify-between items-baseline mb-1">
                         <label for="password" class="block text-sm font-medium text-gray-700 font-body">Password</label>
                         <a href="index.php?page=forgot_password" class="text-sm text-primary hover:text-primary-dark font-medium transition duration-150 ease-in-out font-body">Forgot Password?</a>
                    </div>
                    <div class="password-input relative">
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out font-body"
                               placeholder="Enter your password">
                        <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary transition duration-150 ease-in-out" aria-label="Toggle password visibility">
                            <i class="fas fa-eye text-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group flex items-center justify-between">
                    <label class="checkbox-label flex items-center text-sm text-gray-700 cursor-pointer font-body">
                        <input type="checkbox" name="remember_me" value="1"
                               class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary mr-2"
                               <?= isset($_POST['remember_me']) ? 'checked' : '' ?>>
                        <span>Keep me logged in</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-full py-3 text-lg font-semibold rounded-md shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-dark transition duration-150 ease-in-out flex items-center justify-center font-body" id="submitButton">
                    <span class="button-text">Log In</span>
                    <span class="button-loader hidden ml-2">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>

            <div class="auth-links mt-8 pt-6 border-t border-gray-200 text-center">
                <p class="text-sm text-gray-600 font-body">Don't have an account?
                     <a href="index.php?page=register" class="font-medium text-primary hover:text-primary-dark transition duration-150 ease-in-out">Create one now</a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

```

# views/products.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-products">

<!-- Output CSRF token for JS (for AJAX add-to-cart) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

<section class="products-section">
    <div class="container">
        <div class="products-header" data-aos="fade-up">
            <h1><?= $pageTitle ?></h1>
            
            <!-- Search Bar -->
            <form action="index.php" method="GET" class="search-form">
                <input type="hidden" name="page" value="products">
                <div class="search-input">
                    <input type="text" name="search" placeholder="Search products..."
                           value="<?= htmlspecialchars($searchQuery ?? '') ?>">
                    <button type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="products-grid-container">
            <!-- Filters Sidebar -->
            <aside class="filters-sidebar" data-aos="fade-right">
                <div class="filters-section">
                    <h2>Price Range</h2>
                    <div class="price-range">
                        <div class="range-inputs">
                            <input type="number" id="minPrice" placeholder="Min" min="0"
                                   value="<?= $_GET['min_price'] ?? '' ?>">
                            <span>to</span>
                            <input type="number" id="maxPrice" placeholder="Max" min="0"
                                   value="<?= $_GET['max_price'] ?? '' ?>">
                        </div>
                        <button type="button" class="btn-secondary apply-price-filter">Apply</button>
                    </div>
                </div>
            </aside>
            
            <!-- Products Grid -->
            <div class="products-content">
                <!-- Horizontal Category Filter Bar -->
                <div class="category-filter-bar mb-6 pb-4 border-b border-gray-200" data-aos="fade-up">
                    <nav class="flex flex-wrap gap-x-4 gap-y-2 items-center">
                        <a href="index.php?page=products"
                           class="category-link <?= empty($_GET['category']) ? 'active' : '' ?>">
                            All Products
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="index.php?page=products&category=<?= urlencode($cat['id']) ?>"
                               class="category-link <?= (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'active' : '' ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
                
                <div class="products-toolbar" data-aos="fade-up">
                    <div class="showing-products">
                        Showing <?= count($products) ?> products
                    </div>
                    
                    <div class="sort-options">
                        <label for="sort">Sort by:</label>
                        <select id="sort" name="sort">
                            <option value="name_asc" <?= ($sortBy === 'name_asc') ? 'selected' : '' ?>>
                                Name (A-Z)
                            </option>
                            <option value="name_desc" <?= ($sortBy === 'name_desc') ? 'selected' : '' ?>>
                                Name (Z-A)
                            </option>
                            <option value="price_asc" <?= ($sortBy === 'price_asc') ? 'selected' : '' ?>>
                                Price (Low to High)
                            </option>
                            <option value="price_desc" <?= ($sortBy === 'price_desc') ? 'selected' : '' ?>>
                                Price (High to Low)
                            </option>
                        </select>
                    </div>
                </div>
                
                <?php if (empty($products)): ?>
                    <div class="no-products" data-aos="fade-up">
                        <i class="fas fa-search"></i>
                        <p>No products found matching your criteria.</p>
                        <?php if (!empty($searchQuery) || !empty($categoryId) || !empty($_GET['min_price']) || !empty($_GET['max_price'])): ?>
                            <p class="text-gray-500 mb-6">Try adjusting your search terms or filters in the sidebar.</p>
                            <a href="index.php?page=products" class="btn-secondary mr-2">Clear Filters</a>
                        <?php else: ?>
                            <p class="text-gray-500 mb-6">Explore our collections or try a different search.</p>
                        <?php endif; ?>
                        <a href="index.php?page=products" class="btn-primary">View All Products</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 px-6">
                        <?php foreach ($products as $index => $product): ?>
                            <!-- Modern product card structure, matching home.php/product_detail.php -->
                            <div class="product-card sample-card bg-white rounded-lg shadow-md overflow-hidden transition-shadow duration-300 hover:shadow-xl flex flex-col" data-aos="zoom-in" data-aos-delay="<?= $index * 100 ?>">
                                <div class="product-image relative h-64 overflow-hidden">
                                    <a href="index.php?page=product&id=<?= $product['id'] ?>">
                                        <img src="<?= htmlspecialchars($product['image_url'] ?? '/images/placeholder.jpg') ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                                    </a>
                                    <?php if (!empty($product['featured'])): ?>
                                        <span class="absolute top-2 left-2 bg-accent text-white text-xs font-semibold px-2 py-0.5 rounded-full">Featured</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info p-4 flex flex-col flex-grow text-center">
                                    <h3 class="text-lg font-semibold mb-1 font-heading text-primary hover:text-accent">
                                        <a href="index.php?page=product&id=<?= $product['id'] ?>">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </a>
                                    </h3>
                                    <?php if (!empty($product['short_description'])): ?>
                                        <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($product['short_description']) ?></p>
                                    <?php elseif (!empty($product['category_name'])): ?>
                                        <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($product['category_name']) ?></p>
                                    <?php endif; ?>
                                    <p class="price text-base font-semibold text-accent mb-4 mt-auto">$<?= isset($product['price']) ? number_format($product['price'], 2) : 'N/A' ?></p>
                                    <div class="product-actions mt-auto flex gap-2 justify-center">
                                        <a href="index.php?page=product&id=<?= $product['id'] ?>" class="btn btn-primary">View Details</a>
                                        <?php $isOutOfStock = (!isset($product['stock_quantity']) || $product['stock_quantity'] <= 0) && empty($product['backorder_allowed']); ?>
                                        <?php if (!$isOutOfStock): ?>
                                            <button class="btn btn-secondary add-to-cart" 
                                                    data-product-id="<?= $product['id'] ?>"
                                                    <?= isset($product['low_stock_threshold']) && isset($product['stock_quantity']) && $product['stock_quantity'] <= $product['low_stock_threshold'] ? 'data-low-stock="true"' : '' ?>>
                                                Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-disabled" disabled>Out of Stock</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <!-- Pagination block -->
                <?php if (isset($paginationData) && $paginationData['totalPages'] > 1): ?>
                    <nav aria-label="Page navigation" class="mt-12 flex justify-center" data-aos="fade-up">
                        <ul class="inline-flex items-center -space-x-px">
                            <!-- Previous Button -->
                            <li>
                                <a href="<?= $paginationData['currentPage'] > 1 ? htmlspecialchars($paginationData['baseUrl'] . '&page_num=' . ($paginationData['currentPage'] - 1)) : '#' ?>"
                                   class="py-2 px-3 ml-0 leading-tight text-gray-500 bg-white rounded-l-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 <?= $paginationData['currentPage'] <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php
                            $numLinks = 2;
                            $startPage = max(1, $paginationData['currentPage'] - $numLinks);
                            $endPage = min($paginationData['totalPages'], $paginationData['currentPage'] + $numLinks);
                            if ($startPage > 1) {
                                echo '<li><a href="'.htmlspecialchars($paginationData['baseUrl'].'&page_num=1').'" class="py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">1</a></li>';
                                if ($startPage > 2) {
                                     echo '<li><span class="py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
                                }
                            }
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li>
                                    <a href="<?= htmlspecialchars($paginationData['baseUrl'] . '&page_num=' . $i) ?>"
                                       class="py-2 px-3 leading-tight <?= $i == $paginationData['currentPage'] ? 'z-10 text-primary bg-secondary border-primary hover:bg-secondary hover:text-primary' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor;
                             if ($endPage < $paginationData['totalPages']) {
                                if ($endPage < $paginationData['totalPages'] - 1) {
                                     echo '<li><span class="py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
                                }
                                echo '<li><a href="'.htmlspecialchars($paginationData['baseUrl'].'&page_num='.$paginationData['totalPages']).'" class="py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">'.$paginationData['totalPages'].'</a></li>';
                            }
                            ?>
                            <!-- Next Button -->
                            <li>
                                <a href="<?= $paginationData['currentPage'] < $paginationData['totalPages'] ? htmlspecialchars($paginationData['baseUrl'] . '&page_num=' . ($paginationData['currentPage'] + 1)) : '#' ?>"
                                   class="py-2 px-3 leading-tight text-gray-500 bg-white rounded-r-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 <?= $paginationData['currentPage'] >= $paginationData['totalPages'] ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

# models/Product.php  
```php
<?php
class Product {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM products ORDER BY id DESC");
        return $stmt->fetchAll();
    }
    
    public function getFeatured() {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.name as category_name,
                   CASE 
                       WHEN p.highlight_text IS NOT NULL THEN p.highlight_text
                       WHEN p.stock_quantity <= p.low_stock_threshold THEN 'Low Stock'
                       WHEN DATEDIFF(NOW(), p.created_at) <= 30 THEN 'New'
                       ELSE NULL 
                   END as display_badge
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_featured = 1
            ORDER BY p.created_at DESC
            LIMIT 6
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        // Decode JSON fields if present
        if ($product) {
            if (isset($product['benefits'])) {
                $product['benefits'] = json_decode($product['benefits'], true) ?? [];
            }
            if (isset($product['gallery_images'])) {
                $product['gallery_images'] = json_decode($product['gallery_images'], true) ?? [];
            }
        }
        return $product;
    }
    
    public function getByCategory($categoryId) {
        $stmt = $this->pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? ORDER BY p.id DESC");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO products (name, description, price, category_id, image_url, featured)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category_id'],
            $data['image_url'],
            $data['featured'] ?? 0
        ]);
    }
    
    public function update($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE products 
            SET name = ?, description = ?, price = ?, 
                category_id = ?, image_url = ?, featured = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category_id'],
            $data['image_url'],
            $data['featured'] ?? 0,
            $id
        ]);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function search($query) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM products 
            WHERE name LIKE ? OR description LIKE ?
            ORDER BY id DESC
        ");
        $searchTerm = "%{$query}%";
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
    
    public function getAllCategories() {
        // Select distinct names and the minimum ID associated with each unique name
        $stmt = $this->pdo->query("
            SELECT MIN(id) as id, name
            FROM categories
            GROUP BY name
            ORDER BY name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getFiltered($conditions = [], $params = [], $sortBy = 'name_asc', $limit = 12, $offset = 0) {
        $fixedConditions = array_map(function($cond) {
            $cond = preg_replace('/\bname\b/', 'p.name', $cond);
            $cond = preg_replace('/\bdescription\b/', 'p.description', $cond);
            $cond = preg_replace('/\bprice\b/', 'p.price', $cond);
            $cond = preg_replace('/\bcategory_id\b/', 'p.category_id', $cond);
            return $cond;
        }, $conditions);
        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";
        if (!empty($fixedConditions)) {
            $sql .= " WHERE " . implode(" AND ", $fixedConditions);
        }
        // Sorting
        switch ($sortBy) {
            case 'price_asc':
                $sql .= " ORDER BY p.price ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY p.price DESC";
                break;
            case 'name_desc':
                $sql .= " ORDER BY p.name DESC";
                break;
            case 'name_asc':
            default:
                $sql .= " ORDER BY p.name ASC";
                break;
        }
        $sql .= " LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $paramIndex = 1;
        foreach ($params as $value) {
            $stmt->bindValue($paramIndex++, $value);
        }
        $stmt->bindValue($paramIndex++, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex++, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products as &$product) {
            if (isset($product['benefits'])) {
                $product['benefits'] = json_decode($product['benefits'], true) ?? [];
            }
            if (isset($product['gallery_images'])) {
                $product['gallery_images'] = json_decode($product['gallery_images'], true) ?? [];
            }
        }
        return $products;
    }

    public function getPriceRange() {
        $stmt = $this->pdo->query("
            SELECT MIN(price) as min_price, MAX(price) as max_price 
            FROM products
        ");
        return $stmt->fetch();
    }
    
    public function getProductsByIds($ids) {
        if (empty($ids)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $this->pdo->prepare("
            SELECT * FROM products 
            WHERE id IN ($placeholders)
            ORDER BY FIELD(id, $placeholders)
        ");
        
        // Double the IDs array since we need it twice in the query
        $params = array_merge($ids, $ids);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function searchWithFilters($query, $categoryId = null, $minPrice = null, $maxPrice = null) {
        $conditions = ["(name LIKE ? OR description LIKE ?)"];
        $params = ["%$query%", "%$query%"];
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
        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE " . implode(" AND ", $conditions);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getRelatedProducts($productId, $categoryId, $limit = 4) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? ORDER BY RAND() LIMIT ?
        ");
        $stmt->execute([$categoryId, $productId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get related products by category_id, excluding the current product.
     * @param int $categoryId
     * @param int $excludeId
     * @param int $limit
     * @return array
     */
    public function getRelated($categoryId, $excludeId, $limit = 4) {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? ORDER BY RAND() LIMIT ?"
        );
        $stmt->bindValue(1, $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(2, $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Decode JSON fields for related products
        foreach ($products as &$product) {
            if (isset($product['benefits'])) {
                $product['benefits'] = json_decode($product['benefits'], true) ?? [];
            }
            if (isset($product['gallery_images'])) {
                $product['gallery_images'] = json_decode($product['gallery_images'], true) ?? [];
            }
        }
        return $products;
    }

    public function updateStock($id, $quantity) {
        $stmt = $this->pdo->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity + ? 
            WHERE id = ?
        ");
        return $stmt->execute([$quantity, $id]);
    }
    
    public function checkStock($id) {
        $stmt = $this->pdo->prepare("
            SELECT stock_quantity, backorder_allowed, low_stock_threshold 
            FROM products 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function isInStock($id, $requestedQuantity = 1) {
        $stock = $this->checkStock($id);
        if (!$stock) {
            return false;
        }
        
        return $stock['backorder_allowed'] || $stock['stock_quantity'] >= $requestedQuantity;
    }
    
    public function getLowStockProducts($threshold = null) {
        $sql = "
            SELECT * FROM products 
            WHERE stock_quantity <= COALESCE(?, low_stock_threshold)
            ORDER BY stock_quantity ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$threshold]);
        return $stmt->fetchAll();
    }
    
    public function updateStockSettings($id, $threshold, $backorderAllowed) {
        $stmt = $this->pdo->prepare("
            UPDATE products 
            SET low_stock_threshold = ?,
                backorder_allowed = ?
            WHERE id = ?
        ");
        return $stmt->execute([$threshold, $backorderAllowed, $id]);
    }

    public function getCount($conditions = [], $params = []) {
        // Ensure this prefixing matches getFiltered if ambiguous columns are possible
        $fixedConditions = array_map(function($cond) {
            $cond = preg_replace('/\\bname\\b/', 'p.name', $cond);
            $cond = preg_replace('/\\bdescription\\b/', 'p.description', $cond);
            $cond = preg_replace('/\\bprice\\b/', 'p.price', $cond);
            $cond = preg_replace('/\\bcategory_id\\b/', 'p.category_id', $cond);
            return $cond;
        }, $conditions);
        $needsCategoryJoin = false;
        foreach($fixedConditions as $cond) {
            if (strpos($cond, 'c.') !== false) {
                $needsCategoryJoin = true;
                break;
            }
        }
        $sql = "SELECT COUNT(p.id) as count FROM products p";
        if ($needsCategoryJoin) {
            $sql .= " LEFT JOIN categories c ON p.category_id = c.id";
        }
        if (!empty($fixedConditions)) {
            $sql .= " WHERE " . implode(" AND ", $fixedConditions);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? (int)$row['count'] : 0;
    }
}
```

# models/Order.php  
```php
<?php
// models/Order.php (Updated create signature, _fetchOrderItems join)

// Ensure Product model is available if needed for item details fetching
// Assuming autoloader or previous require_once handles this
// require_once __DIR__ . '/Product.php';

class Order {
    private PDO $pdo; // Use type hint

    public function __construct(PDO $pdo) { // Use type hint
        $this->pdo = $pdo;
    }

    /**
     * Creates a new order in the database.
     * Accepts extended data including coupon info, payment intent ID, etc.
     *
     * @param array $data Order data including user_id, totals, shipping info, coupon info, etc.
     * @return int|false The ID of the newly created order, or false on failure.
     */
    public function create(array $data): int|false {
        // --- Updated SQL to include all necessary fields ---
        $sql = "
            INSERT INTO orders (
                user_id, subtotal, discount_amount, coupon_code, coupon_id,
                shipping_cost, tax_amount, total_amount, shipping_name, shipping_email,
                shipping_address, shipping_city, shipping_state, shipping_zip,
                shipping_country, status, payment_status, payment_intent_id, order_notes,
                created_at, updated_at
            ) VALUES (
                :user_id, :subtotal, :discount_amount, :coupon_code, :coupon_id,
                :shipping_cost, :tax_amount, :total_amount, :shipping_name, :shipping_email,
                :shipping_address, :shipping_city, :shipping_state, :shipping_zip,
                :shipping_country, :status, :payment_status, :payment_intent_id, :order_notes,
                NOW(), NOW()
            )
        ";
        // --- End Updated SQL ---
        $stmt = $this->pdo->prepare($sql);

        // --- Updated execute array with new fields ---
        $success = $stmt->execute([
            ':user_id' => $data['user_id'],
            ':subtotal' => $data['subtotal'] ?? 0.00,
            ':discount_amount' => $data['discount_amount'] ?? 0.00,
            ':coupon_code' => $data['coupon_code'] ?? null,
            ':coupon_id' => $data['coupon_id'] ?? null,
            ':shipping_cost' => $data['shipping_cost'] ?? 0.00,
            ':tax_amount' => $data['tax_amount'] ?? 0.00,
            ':total_amount' => $data['total_amount'] ?? 0.00,
            ':shipping_name' => $data['shipping_name'],
            ':shipping_email' => $data['shipping_email'],
            ':shipping_address' => $data['shipping_address'],
            ':shipping_city' => $data['shipping_city'],
            ':shipping_state' => $data['shipping_state'],
            ':shipping_zip' => $data['shipping_zip'],
            ':shipping_country' => $data['shipping_country'],
            ':status' => $data['status'] ?? 'pending_payment', // Default status
            ':payment_status' => $data['payment_status'] ?? 'pending', // Default payment status
            ':payment_intent_id' => $data['payment_intent_id'] ?? null, // Store PI ID
            ':order_notes' => $data['order_notes'] ?? null // Store order notes
        ]);
        // --- End Updated execute array ---

        return $success ? (int)$this->pdo->lastInsertId() : false;
    }

    /**
     * Fetches a single order by its ID, including its items.
     *
     * @param int $id The order ID.
     * @return array|null The order data including items, or null if not found.
     */
    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if ($order) {
            $order['items'] = $this->_fetchOrderItems($id);
        }
        return $order ?: null;
    }

    /**
     * Fetches a single order by its ID and User ID, including its items.
     * Ensures the order belongs to the specified user.
     *
     * @param int $orderId The order ID.
     * @param int $userId The user ID.
     * @return array|null The order data including items, or null if not found or access denied.
     */
    public function getByIdAndUserId(int $orderId, int $userId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();

        if ($order) {
            $order['items'] = $this->_fetchOrderItems($orderId);
        }
        return $order ?: null;
    }


    /**
     * Fetches recent orders for a specific user, mainly for dashboard display.
     * Includes a concatenated summary of items.
     *
     * @param int $userId The user ID.
     * @param int $limit Max number of orders to fetch.
     * @return array List of recent orders.
     */
    public function getRecentByUserId(int $userId, int $limit = 5): array {
        // This version uses GROUP_CONCAT for a simple item summary, suitable for dashboards.
        // Use getAllByUserId for full item details if needed elsewhere.
        $stmt = $this->pdo->prepare("
            SELECT o.*,
                   GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR '<br>') as items_summary
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id /* Use LEFT JOIN in case order has no items? */
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = ?
            GROUP BY o.id /* Grouping is essential for GROUP_CONCAT */
            ORDER BY o.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

     /**
     * Fetches all orders for a specific user with pagination, including full item details.
     *
     * @param int $userId The user ID.
     * @param int $page Current page number.
     * @param int $perPage Number of orders per page.
     * @return array List of orders for the page.
     */
    public function getAllByUserId(int $userId, int $page = 1, int $perPage = 10): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->pdo->prepare("
            SELECT * FROM orders
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll();

        // Fetch items for each order
        foreach ($orders as &$order) {
            $order['items'] = $this->_fetchOrderItems($order['id']);
        }
        unset($order); // Unset reference

        return $orders ?: [];
    }

    /**
     * Gets the total count of orders for a specific user.
     *
     * @param int $userId The user ID.
     * @return int Total number of orders.
     */
    public function getTotalOrdersByUserId(int $userId): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }


    /**
     * Updates the status of an order. Also updates payment_status and paid_at conditionally.
     *
     * @param int $orderId The ID of the order to update.
     * @param string $status The new status (e.g., 'paid', 'processing', 'shipped', 'cancelled').
     * @return bool True on success, false on failure.
     */
    public function updateStatus(int $orderId, string $status): bool {
        // Define allowed statuses to prevent arbitrary updates
        $allowedStatuses = ['pending_payment', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded', 'disputed', 'payment_failed', 'completed'];
        if (!in_array($status, $allowedStatuses)) {
            error_log("Attempted to set invalid order status '{$status}' for order ID {$orderId}");
            return false;
        }

        $sql = "UPDATE orders SET status = :status, updated_at = NOW()";
        $params = [':status' => $status, ':id' => $orderId];

        // Update payment_status based on main status for simplicity
        // Adjust this logic based on exact requirements (e.g., partial refunds)
        if (in_array($status, ['paid', 'processing', 'shipped', 'delivered', 'completed'])) {
             $sql .= ", payment_status = 'completed'";
             // Set paid_at timestamp only when moving to 'paid' or 'processing' for the first time
             $sql .= ", paid_at = COALESCE(paid_at, CASE WHEN :status IN ('paid', 'processing') THEN NOW() ELSE NULL END)";
        } elseif ($status === 'payment_failed') {
            $sql .= ", payment_status = 'failed'";
        } elseif ($status === 'cancelled') {
             $sql .= ", payment_status = 'cancelled'";
        } elseif ($status === 'refunded') {
             $sql .= ", payment_status = 'refunded'";
        } elseif ($status === 'disputed') {
             $sql .= ", payment_status = 'disputed'";
        }
        // 'pending_payment' status typically implies 'pending' payment_status initially

        $sql .= " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Updates the Payment Intent ID for a given order.
     *
     * @param int $orderId The ID of the order.
     * @param string $paymentIntentId The Stripe Payment Intent ID.
     * @return bool True on success, false on failure.
     */
    public function updatePaymentIntentId(int $orderId, string $paymentIntentId): bool {
        $stmt = $this->pdo->prepare("
            UPDATE orders
            SET payment_intent_id = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$paymentIntentId, $orderId]);
    }


    /**
     * Fetches an order by its Stripe Payment Intent ID.
     *
     * @param string $paymentIntentId The Stripe Payment Intent ID.
     * @return array|null The order data, or null if not found.
     */
    public function getByPaymentIntentId(string $paymentIntentId): ?array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM orders WHERE payment_intent_id = ? LIMIT 1 /* Ensure only one */
        ");
        $stmt->execute([$paymentIntentId]);
        $order = $stmt->fetch();

        // Optionally fetch items if needed by webhook handlers (often not needed directly in webhook)
        // if ($order) {
        //     $order['items'] = $this->_fetchOrderItems($order['id']);
        // }
        return $order ?: null;
    }

    /**
      * Updates the order status and adds dispute information.
      *
      * @param int $orderId
      * @param string $status Typically 'disputed'.
      * @param string $disputeId Stripe Dispute ID.
      * @return bool
      */
     public function updateStatusAndDispute(int $orderId, string $status, string $disputeId): bool {
         // Ensure status is 'disputed'
         if ($status !== 'disputed') {
             error_log("Invalid status '{$status}' provided to updateStatusAndDispute for order {$orderId}");
             return false;
         }
         $stmt = $this->pdo->prepare("
             UPDATE orders
             SET status = ?,
                 payment_status = 'disputed', /* Explicitly set payment status */
                 dispute_id = ?,
                 disputed_at = NOW(),
                 updated_at = NOW()
             WHERE id = ?
         ");
         return $stmt->execute([$status, $disputeId, $orderId]);
     }

     /**
      * Updates the order status and adds refund information.
      *
      * @param int $orderId
      * @param string $status Typically 'refunded'.
      * @param string $paymentStatus Typically 'refunded' or 'partially_refunded'.
      * @param string $refundId Stripe Refund ID.
      * @return bool
      */
     public function updateRefundStatus(int $orderId, string $status, string $paymentStatus, string $refundId): bool {
         // Ensure status is valid for refund
         if (!in_array($status, ['refunded', 'partially_refunded'])) { // Adjust if using different statuses
             error_log("Invalid status '{$status}' provided to updateRefundStatus for order {$orderId}");
             return false;
         }
         $stmt = $this->pdo->prepare("
             UPDATE orders
             SET status = ?,
                 payment_status = ?,
                 refund_id = ?, /* Assuming 'refund_id' column exists */
                 refunded_at = NOW(), /* Assuming 'refunded_at' column exists */
                 updated_at = NOW()
             WHERE id = ?
         ");
         return $stmt->execute([$status, $paymentStatus, $refundId, $orderId]);
     }

     /**
      * Updates the tracking information for an order.
      *
      * @param int $orderId
      * @param string $trackingNumber
      * @param string|null $carrier
      * @param string|null $trackingUrl (Optional, if schema supports it)
      * @return bool
      */
     public function updateTracking(int $orderId, string $trackingNumber, ?string $carrier = null /*, ?string $trackingUrl = null */): bool {
         // Assuming schema has 'tracking_number' and 'carrier' columns
         $sql = "UPDATE orders SET tracking_number = ?, carrier = ?, updated_at = NOW()";
         $params = [$trackingNumber, $carrier];

         // Add tracking_url if schema supports it
         // if ($trackingUrl !== null) {
         //     $sql .= ", tracking_url = ?";
         //     $params[] = $trackingUrl;
         // }

         $sql .= " WHERE id = ?";
         $params[] = $orderId;

         $stmt = $this->pdo->prepare($sql);
         return $stmt->execute($params);
     }


    /**
     * Fetches all items associated with a given order ID.
     * Joins with products table to get item details needed for display/emails.
     *
     * @param int $orderId The order ID.
     * @return array List of order items with product details.
     */
    private function _fetchOrderItems(int $orderId): array {
        // --- Updated to fetch product name and image ---
        $stmt = $this->pdo->prepare("
            SELECT
                oi.id as order_item_id,
                oi.product_id,
                oi.quantity,
                oi.price as price_at_purchase, /* Price when the order was placed */
                p.name as product_name, /* Get product name */
                p.image as image_url /* Get product image path */
                /* Add other product fields if needed, e.g., p.sku */
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        // --- End Update ---
        $stmt->execute([$orderId]);
        return $stmt->fetchAll() ?: [];
    }

} // End Order class

```

