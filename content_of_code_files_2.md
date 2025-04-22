# controllers/CartController.php  
```php
<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';

class CartController extends BaseController {
    private $productModel;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->initCart();
    }
    
    private function initCart() {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }
    
    public function showCart() {
        $cartItems = [];
        $total = 0;
        
        // Get full product details for cart items
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $product = $this->productModel->getById($productId);
            if ($product) {
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product['price'] * $quantity
                ];
                $total += $product['price'] * $quantity;
            }
        }
        
        $csrfToken = $this->getCsrfToken();
        require_once __DIR__ . '/../views/cart.php';
    }
    
    public function addToCart() {
        $this->validateAjax();
        $this->validateCSRF();
        
        $productId = $this->validateInput($_POST['product_id'] ?? null, 'int');
        $quantity = (int)$this->validateInput($_POST['quantity'] ?? 1, 'int');
        
        if (!$productId || $quantity < 1) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid product or quantity'], 400);
        }
        
        $product = $this->productModel->getById($productId);
        if (!$product) {
            $this->jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
        }
        
        // Check stock availability
        if (!$this->productModel->isInStock($productId, $quantity)) {
            $stockInfo = $this->productModel->checkStock($productId);
            $stockStatus = 'out_of_stock';
            $this->jsonResponse([
                'success' => false,
                'message' => 'Insufficient stock',
                'cart_count' => array_sum($_SESSION['cart']),
                'stock_status' => $stockStatus
            ], 400);
        }
        
        // Add or update quantity
        if (isset($_SESSION['cart'][$productId])) {
            $newQuantity = $_SESSION['cart'][$productId] + $quantity;
            // Recheck stock for total quantity
            if (!$this->productModel->isInStock($productId, $newQuantity)) {
                $stockInfo = $this->productModel->checkStock($productId);
                $stockStatus = 'out_of_stock';
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Insufficient stock for requested quantity',
                    'cart_count' => array_sum($_SESSION['cart']),
                    'stock_status' => $stockStatus
                ], 400);
            }
            $_SESSION['cart'][$productId] = $newQuantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
        
        $cartCount = array_sum($_SESSION['cart']);
        $_SESSION['cart_count'] = $cartCount;
        
        // Determine stock status for response
        $stockInfo = $this->productModel->checkStock($productId);
        $currentStock = $stockInfo ? ($stockInfo['stock_quantity'] - $_SESSION['cart'][$productId]) : 0;
        $stockStatus = 'in_stock';
        if ($stockInfo) {
            if (!$stockInfo['backorder_allowed']) {
                if ($currentStock <= 0) {
                    $stockStatus = 'out_of_stock';
                } elseif ($stockInfo['low_stock_threshold'] && $currentStock <= $stockInfo['low_stock_threshold']) {
                    $stockStatus = 'low_stock';
                }
            }
        }
        
        $this->jsonResponse([
            'success' => true,
            'message' => htmlspecialchars($product['name']) . ' added to cart',
            'cart_count' => $cartCount,
            'stock_status' => $stockStatus
        ]);
    }
    
    public function updateCart() {
        $this->validateAjax();
        $this->validateCSRF();
        
        $updates = $_POST['updates'] ?? [];
        $stockErrors = [];
        
        foreach ($updates as $productId => $quantity) {
            $productId = $this->validateInput($productId, 'int');
            $quantity = (int)$this->validateInput($quantity, 'int');
            
            if ($quantity > 0) {
                // Validate stock
                if (!$this->productModel->isInStock($productId, $quantity)) {
                    $product = $this->productModel->getById($productId);
                    $stockErrors[] = "{$product['name']} has insufficient stock";
                    continue;
                }
                $_SESSION['cart'][$productId] = $quantity;
            } else {
                unset($_SESSION['cart'][$productId]);
            }
        }
        
        $this->jsonResponse([
            'success' => empty($stockErrors),
            'message' => empty($stockErrors) ? 'Cart updated' : 'Some items have insufficient stock',
            'cartCount' => array_sum($_SESSION['cart']),
            'errors' => $stockErrors
        ]);
    }
    
    public function removeFromCart() {
        $this->validateAjax();
        $this->validateCSRF();
        
        $productId = $this->validateInput($_POST['product_id'] ?? null, 'int');
        
        if (!$productId || !isset($_SESSION['cart'][$productId])) {
            $this->jsonResponse(['success' => false, 'message' => 'Product not found in cart'], 404);
        }
        
        unset($_SESSION['cart'][$productId]);
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Product removed from cart',
            'cartCount' => array_sum($_SESSION['cart'])
        ]);
    }
    
    public function clearCart() {
        $_SESSION['cart'] = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateAjax();
            $this->validateCSRF();
            $this->jsonResponse([
                'success' => true,
                'message' => 'Cart cleared',
                'cartCount' => 0
            ]);
        } else {
            $this->redirect('cart');
        }
    }
    
    public function validateCartStock() {
        $errors = [];
        
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            if (!$this->productModel->isInStock($productId, $quantity)) {
                $product = $this->productModel->getById($productId);
                $errors[] = "{$product['name']} has insufficient stock";
            }
        }
        
        return $errors;
    }
    
    public function getCartItems() {
        $this->initCart();
        $cartItems = [];
        
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
        
        return $cartItems;
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
            unset($queryParams['page']);
            if (!empty($queryParams)) {
                $paginationData['baseUrl'] .= '&' . http_build_query($queryParams);
            }
            
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

# views/product_detail.php  
```php
<?php
// Enhanced product detail view with image path fix, robust data handling, AJAX add-to-cart, and modern layout
require_once __DIR__ . '/layout/header.php';
?>
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
                        <button type="submit" class="btn btn-primary w-full py-3 text-lg add-to-cart">
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Gallery Logic ---
    const mainImage = document.getElementById('mainImage');
    const thumbnails = document.querySelectorAll('.thumbnail-grid img');
    function updateMainImage(thumbnailElement) {
        if (mainImage && thumbnailElement) {
            mainImage.src = thumbnailElement.src;
            mainImage.alt = thumbnailElement.alt.replace('View', 'Main view');
            thumbnails.forEach(img => {
                img.classList.remove('active');
            });
            thumbnailElement.classList.add('active');
        }
    }
    window.updateMainImage = updateMainImage;
    // --- Quantity Selector Logic ---
    const quantityInput = document.querySelector('.quantity-selector input');
    if (quantityInput) {
        const quantityMax = parseInt(quantityInput.getAttribute('max') || '99');
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                if (isNaN(value)) value = 1;
                if (this.classList.contains('plus')) {
                    if (value < quantityMax) quantityInput.value = value + 1;
                    else quantityInput.value = quantityMax;
                } else if (this.classList.contains('minus')) {
                    if (value > 1) quantityInput.value = value - 1;
                }
            });
        });
    }
    // --- Tab Switching Logic ---
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            tabBtns.forEach(b => b.classList.remove('active', 'text-primary', 'border-primary'));
            tabBtns.forEach(b => b.classList.add('text-gray-500', 'border-transparent'));
            this.classList.add('active', 'text-primary', 'border-primary');
            this.classList.remove('text-gray-500', 'border-transparent');
            tabPanes.forEach(pane => {
                if (pane.id === tabId) {
                    pane.classList.add('active');
                    pane.classList.remove('hidden');
                } else {
                    pane.classList.remove('active');
                    pane.classList.add('hidden');
                }
            });
        });
    });
    // Ensure initial active tab's pane is visible
    const initialActiveTab = document.querySelector('.tab-btn.active');
    if(initialActiveTab) {
        const initialTabId = initialActiveTab.dataset.tab;
        tabPanes.forEach(pane => {
            if (pane.id === initialTabId) {
                pane.classList.add('active');
                pane.classList.remove('hidden');
            } else {
                pane.classList.remove('active');
                pane.classList.add('hidden');
            }
        });
    }
    // --- Add to Cart Form Submission (AJAX) ---
    const addToCartForm = document.getElementById('product-detail-add-cart-form');
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"].add-to-cart');
            if (!submitButton || submitButton.disabled) return;
            const originalButtonHtml = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...';
            fetch(this.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new URLSearchParams(formData)
            })
            .then(response => {
                const contentType = response.headers.get("content-type");
                if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                }
                return response.text().then(text => {
                    throw new Error(`Server error ${response.status}: ${text.substring(0, 200)}`);
                });
            })
            .then(data => {
                if (data.success) {
                    const cartCountSpan = document.querySelector('.cart-count');
                    if (cartCountSpan) {
                        cartCountSpan.textContent = data.cart_count;
                        cartCountSpan.style.display = data.cart_count > 0 ? 'inline-block' : 'none';
                    }
                    showFlashMessage(data.message || 'Product added to cart!', 'success');
                    if(data.stock_status === 'out_of_stock') {
                        submitButton.classList.remove('btn-primary');
                        submitButton.classList.add('btn-disabled');
                        submitButton.innerHTML = '<i class="fas fa-times-circle mr-2"></i> Out of Stock';
                    } else {
                        submitButton.innerHTML = originalButtonHtml;
                        submitButton.disabled = false;
                    }
                } else {
                    showFlashMessage(data.message || 'Could not add product.', 'error');
                    submitButton.innerHTML = originalButtonHtml;
                    submitButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
                showFlashMessage('An error occurred. Please try again.', 'error');
                if (submitButton) {
                    submitButton.innerHTML = originalButtonHtml;
                    submitButton.disabled = false;
                }
            });
        });
    }
    // --- Related Products Add to Cart (AJAX) ---
    document.querySelectorAll('.add-to-cart-related').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const csrfTokenInput = document.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';
            if (!csrfToken) {
                showFlashMessage('Security token not found. Please refresh.', 'error');
                return;
            }
            if (this.disabled) return;
            const originalButtonText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            const formData = new URLSearchParams();
            formData.append('product_id', productId);
            formData.append('quantity', '1');
            formData.append('csrf_token', csrfToken);
            fetch('index.php?page=cart&action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(response => {
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        throw new Error("Received non-JSON response: " + text.substring(0, 200));
                    });
                }
            })
            .then(data => {
                if (data.success) {
                    const cartCountSpan = document.querySelector('.cart-count');
                    if (cartCountSpan) {
                        cartCountSpan.textContent = data.cart_count;
                        cartCountSpan.style.display = data.cart_count > 0 ? 'inline-block' : 'none';
                    }
                    showFlashMessage(data.message || 'Product added to cart!', 'success');
                    if(data.stock_status === 'out_of_stock') {
                        this.classList.remove('btn-secondary');
                        this.classList.add('btn-disabled');
                        this.innerHTML = 'Out of Stock';
                    } else {
                        this.innerHTML = originalButtonText;
                        this.disabled = false;
                    }
                } else {
                    showFlashMessage(data.message || 'Could not add product.', 'error');
                    this.innerHTML = originalButtonText;
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error adding related product:', error);
                showFlashMessage('An error occurred. Please try again.', 'error');
                this.innerHTML = originalButtonText;
                this.disabled = false;
            });
        });
    });
});
</script>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

# views/products.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>

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
                    <h2>Categories</h2>
                    <ul class="category-list">
                        <li>
                            <a href="index.php?page=products" 
                               class="<?= empty($_GET['category']) ? 'active' : '' ?>">
                                All Products
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="index.php?page=products&category=<?= urlencode($cat['id']) ?>"
                                   class="<?= (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'active' : '' ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
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
                                <a href="<?= $paginationData['currentPage'] > 1 ? htmlspecialchars($paginationData['baseUrl'] . '&page=' . ($paginationData['currentPage'] - 1)) : '#' ?>"
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
                                echo '<li><a href="'.htmlspecialchars($paginationData['baseUrl'].'&page=1').'" class="py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">1</a></li>';
                                if ($startPage > 2) {
                                     echo '<li><span class="py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
                                }
                            }
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li>
                                    <a href="<?= htmlspecialchars($paginationData['baseUrl'] . '&page=' . $i) ?>"
                                       class="py-2 px-3 leading-tight <?= $i == $paginationData['currentPage'] ? 'z-10 text-primary bg-secondary border-primary hover:bg-secondary hover:text-primary' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor;
                             if ($endPage < $paginationData['totalPages']) {
                                if ($endPage < $paginationData['totalPages'] - 1) {
                                     echo '<li><span class="py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
                                }
                                echo '<li><a href="'.htmlspecialchars($paginationData['baseUrl'].'&page='.$paginationData['totalPages']).'" class="py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">'.$paginationData['totalPages'].'</a></li>';
                            }
                            ?>
                            <!-- Next Button -->
                            <li>
                                <a href="<?= $paginationData['currentPage'] < $paginationData['totalPages'] ? htmlspecialchars($paginationData['baseUrl'] . '&page=' . ($paginationData['currentPage'] + 1)) : '#' ?>"
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle sorting
    const sortSelect = document.getElementById('sort');
    sortSelect.addEventListener('change', function() {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', this.value);
        window.location.href = url.toString();
    });
    
    // Handle price filter
    const applyPriceFilter = document.querySelector('.apply-price-filter');
    applyPriceFilter.addEventListener('click', function() {
        const minPrice = document.getElementById('minPrice').value;
        const maxPrice = document.getElementById('maxPrice').value;
        
        const url = new URL(window.location.href);
        if (minPrice) url.searchParams.set('min_price', minPrice);
        if (maxPrice) url.searchParams.set('max_price', maxPrice);
        window.location.href = url.toString();
    });
    
    // Handle add to cart (delegated to footer.js, but fallback for legacy)
});
</script>

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
        $stmt = $this->pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getFiltered($conditions = [], $params = [], $sortBy = 'name_asc', $limit = null, $offset = null) {
        // Prefix ambiguous columns in conditions
        $fixedConditions = array_map(function($cond) {
            $cond = preg_replace('/\bname\b/', 'p.name', $cond);
            $cond = preg_replace('/\bdescription\b/', 'p.description', $cond);
            return $cond;
        }, $conditions);
        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";
        if (!empty($fixedConditions)) {
            $sql .= " WHERE " . implode(" AND ", $fixedConditions);
        }
        // Add sorting
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
        }
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
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
        $sql = "SELECT COUNT(*) as count FROM products";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? (int)$row['count'] : 0;
    }
}
```

