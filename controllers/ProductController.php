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
