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