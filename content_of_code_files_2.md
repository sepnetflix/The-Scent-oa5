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
<?php require_once __DIR__ . '/layout/header.php'; ?>

<section class="product-detail">
    <div class="container">
        <div class="product-container">
            <!-- Product Gallery -->
            <div class="product-gallery" data-aos="fade-right">
                <div class="main-image">
                    <img src="<?= htmlspecialchars($product['image_url'] ?? '/images/placeholder.jpg') ?>" 
                         alt="<?= htmlspecialchars($product['name'] ?? 'Product') ?>"
                         id="mainImage">
                    <?php if (!empty($product['featured'])): ?>
                        <span class="featured-badge">Featured</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($product['gallery_images'])): ?>
                    <div class="thumbnail-grid">
                        <img src="<?= htmlspecialchars($product['image_url'] ?? '/images/placeholder.jpg') ?>" 
                             alt="Main view"
                             class="active"
                             onclick="updateMainImage(this)">
                        <?php foreach ((array)json_decode($product['gallery_images'], true) as $image): ?>
                            <img src="<?= htmlspecialchars($image ?? '/images/placeholder.jpg') ?>" 
                                 alt="Additional view"
                                 onclick="updateMainImage(this)">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Info -->
            <div class="product-info" data-aos="fade-left">
                <nav class="breadcrumb">
                    <a href="index.php?page=products">Products</a> /
                    <a href="index.php?page=products&category=<?= urlencode($product['category'] ?? '') ?>">
                        <?= htmlspecialchars($product['category'] ?? 'N/A') ?>
                    </a> /
                    <span><?= htmlspecialchars($product['name'] ?? 'Product') ?></span>
                </nav>
                
                <h1><?= htmlspecialchars($product['name'] ?? 'Product') ?></h1>
                <p class="price">$<?= isset($product['price']) ? number_format($product['price'], 2) : 'N/A' ?></p>
                
                <div class="product-description">
                    <?= nl2br(htmlspecialchars($product['description'] ?? '')) ?>
                </div>
                
                <div class="benefits">
                    <h3>Benefits</h3>
                    <ul>
                        <?php foreach ((array)json_decode($product['benefits'] ?? '[]', true) as $benefit): ?>
                            <li><i class="fas fa-check"></i> <?= htmlspecialchars($benefit) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="ingredients">
                    <h3>Key Ingredients</h3>
                    <p><?= htmlspecialchars($product['ingredients'] ?? '') ?></p>
                </div>
                
                <form class="add-to-cart-form" action="index.php?page=cart&action=add" method="POST">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?? '' ?>">
                    <div class="quantity-selector">
                        <button type="button" class="quantity-btn minus">-</button>
                        <input type="number" name="quantity" value="1" min="1" max="99">
                        <button type="button" class="quantity-btn plus">+</button>
                    </div>
                    <button type="submit" class="btn-primary add-to-cart">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                </form>
                
                <div class="additional-info">
                    <div class="info-item">
                        <i class="fas fa-shipping-fast"></i>
                        <span>Free shipping on orders over $50</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-undo"></i>
                        <span>30-day return policy</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-lock"></i>
                        <span>Secure checkout</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Details Tabs -->
        <div class="product-tabs" data-aos="fade-up">
            <div class="tabs-header">
                <button class="tab-btn active" data-tab="usage">How to Use</button>
                <button class="tab-btn" data-tab="details">Details</button>
                <button class="tab-btn" data-tab="shipping">Shipping</button>
                <button class="tab-btn" data-tab="reviews">Reviews</button>
            </div>
            
            <div class="tab-content">
                <div id="usage" class="tab-pane active">
                    <h3>How to Use</h3>
                    <?= nl2br(htmlspecialchars($product['usage_instructions'] ?? '')) ?>
                </div>
                
                <div id="details" class="tab-pane">
                    <h3>Product Details</h3>
                    <table class="details-table">
                        <tr>
                            <th>Size</th>
                            <td><?= htmlspecialchars($product['size'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <th>Scent Profile</th>
                            <td><?= htmlspecialchars($product['scent_profile'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <th>Benefits</th>
                            <td><?= htmlspecialchars($product['benefits_list'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <th>Origin</th>
                            <td><?= htmlspecialchars($product['origin'] ?? '') ?></td>
                        </tr>
                    </table>
                </div>
                
                <div id="shipping" class="tab-pane">
                    <h3>Shipping Information</h3>
                    <div class="shipping-info">
                        <p><strong>Free Standard Shipping</strong> on orders over $50</p>
                        <ul>
                            <li>Standard Shipping (5-7 business days): $5.99</li>
                            <li>Express Shipping (2-3 business days): $12.99</li>
                            <li>Next Day Delivery (order before 2pm): $19.99</li>
                        </ul>
                        <p>International shipping available to select countries.</p>
                    </div>
                </div>
                
                <div id="reviews" class="tab-pane">
                    <h3>Customer Reviews</h3>
                    <div class="reviews-summary">
                        <div class="average-rating">
                            <span class="rating">4.8</span>
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="review-count">Based on 24 reviews</span>
                        </div>
                    </div>
                    <!-- Reviews would be loaded dynamically -->
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
            <div class="related-products" data-aos="fade-up">
                <h2>You May Also Like</h2>
                <div class="products-grid">
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <a href="index.php?page=products&id=<?= $relatedProduct['id'] ?? '' ?>">
                                    <img src="<?= htmlspecialchars($relatedProduct['image_url'] ?? '/images/placeholder.jpg') ?>" 
                                         alt="<?= htmlspecialchars($relatedProduct['name'] ?? 'Product') ?>">
                                </a>
                            </div>
                            <div class="product-info">
                                <h3>
                                    <a href="index.php?page=products&id=<?= $relatedProduct['id'] ?? '' ?>">
                                        <?= htmlspecialchars($relatedProduct['name'] ?? 'Product') ?>
                                    </a>
                                </h3>
                                <p class="price">$<?= isset($relatedProduct['price']) ? number_format($relatedProduct['price'], 2) : 'N/A' ?></p>
                                <button class="btn-primary add-to-cart" 
                                        data-product-id="<?= $relatedProduct['id'] ?? '' ?>">
                                    Add to Cart
                                </button>
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
    // Product image gallery
    function updateMainImage(thumbnail) {
        const mainImage = document.getElementById('mainImage');
        mainImage.src = thumbnail.src;
        
        // Update active state
        document.querySelectorAll('.thumbnail-grid img').forEach(img => {
            img.classList.remove('active');
        });
        thumbnail.classList.add('active');
    }
    
    // Quantity selector
    const quantityInput = document.querySelector('.quantity-selector input');
    document.querySelectorAll('.quantity-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            let value = parseInt(quantityInput.value);
            
            if (this.classList.contains('plus') && value < 99) {
                quantityInput.value = value + 1;
            } else if (this.classList.contains('minus') && value > 1) {
                quantityInput.value = value - 1;
            }
        });
    });
    
    // Product tabs
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            
            // Update active states
            tabBtns.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

# views/products.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>

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
                        <a href="index.php?page=products" class="btn-primary">View All Products</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $index => $product): ?>
                            <div class="product-card" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                                <div class="product-image">
                                    <a href="index.php?page=products&id=<?= $product['id'] ?>">
                                        <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>">
                                    </a>
                                    <?php if ($product['featured']): ?>
                                        <span class="featured-badge">Featured</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-info">
                                    <h3>
                                        <a href="index.php?page=products&id=<?= $product['id'] ?>">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </a>
                                    </h3>
                                    <p class="product-category">
                                        <?= htmlspecialchars($product['category_name'] ?? '') ?>
                                    </p>
                                    <p class="product-price">$<?= number_format($product['price'], 2) ?></p>
                                    <div class="product-actions">
                                        <a href="index.php?page=products&id=<?= $product['id'] ?>" 
                                           class="btn-secondary">View Details</a>
                                        <button class="btn-primary add-to-cart" 
                                                data-product-id="<?= $product['id'] ?>">
                                            Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
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
    
    // Handle add to cart
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            
            fetch('index.php?page=cart&action=add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cartCount;
                        cartCount.style.display = 'inline';
                    }
                    
                    // Show success message
                    alert('Product added to cart!');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
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
        return $stmt->fetch();
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
            "SELECT * FROM products WHERE category_id = ? AND id != ? ORDER BY RAND() LIMIT ?"
        );
        $stmt->bindValue(1, $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(2, $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
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

