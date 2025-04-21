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
            $category = $this->validateInput($_GET['category'] ?? '', 'string');
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
            
            if ($category) {
                $conditions[] = "category = ?";
                $params[] = $category;
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
                ($category ? htmlspecialchars($category) . " Products" : "All Products");
            
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
    
    // Add to cart functionality
    const addToCartForm = document.querySelector('.add-to-cart-form');
    addToCartForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        fetch(this.action, {
            method: 'POST',
            body: formData
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
    
    // Related products add to cart
    document.querySelectorAll('.related-products .add-to-cart').forEach(button => {
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
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cartCount;
                        cartCount.style.display = 'inline';
                    }
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
    
    public function getByCategory($category) {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE category = ? ORDER BY id DESC");
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO products (name, description, price, category, image_url, featured)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category'],
            $data['image_url'],
            $data['featured'] ?? 0
        ]);
    }
    
    public function update($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE products 
            SET name = ?, description = ?, price = ?, 
                category = ?, image_url = ?, featured = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category'],
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
        $stmt = $this->pdo->query("
            SELECT DISTINCT category 
            FROM products 
            ORDER BY category ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    public function getFiltered($conditions = [], $params = [], $sortBy = 'name_asc') {
        $sql = "SELECT * FROM products";
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        // Add sorting
        switch ($sortBy) {
            case 'price_asc':
                $sql .= " ORDER BY price ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY price DESC";
                break;
            case 'name_desc':
                $sql .= " ORDER BY name DESC";
                break;
            case 'name_asc':
            default:
                $sql .= " ORDER BY name ASC";
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
    
    public function searchWithFilters($query, $category = null, $minPrice = null, $maxPrice = null) {
        $conditions = ["(name LIKE ? OR description LIKE ?)"]; 
        $params = ["%$query%", "%$query%"];
        
        if ($category) {
            $conditions[] = "category = ?";
            $params[] = $category;
        }
        
        if ($minPrice !== null) {
            $conditions[] = "price >= ?";
            $params[] = $minPrice;
        }
        
        if ($maxPrice !== null) {
            $conditions[] = "price <= ?";
            $params[] = $maxPrice;
        }
        
        $sql = "SELECT * FROM products WHERE " . implode(" AND ", $conditions);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getRelatedProducts($productId, $category, $limit = 4) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM products 
            WHERE category = ? AND id != ?
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->execute([$category, $productId, $limit]);
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
}
```

