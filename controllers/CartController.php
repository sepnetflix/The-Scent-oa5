<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Cart.php';

class CartController extends BaseController {
    private $productModel;
    private $cartModel = null;
    private $isLoggedIn = false;
    private $userId = null;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->userId = $_SESSION['user_id'] ?? null;
        $this->isLoggedIn = $this->userId !== null;
        if ($this->isLoggedIn) {
            $this->cartModel = new Cart($pdo, $this->userId);
        }
        $this->initCart();
    }
    
    private function initCart() {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    // Call this after login to merge session cart into DB cart
    public static function mergeSessionCartOnLogin($pdo, $userId) {
        if (!empty($_SESSION['cart'])) {
            require_once __DIR__ . '/../models/Cart.php';
            $cartModel = new Cart($pdo, $userId);
            $cartModel->mergeSessionCart($_SESSION['cart']);
            $_SESSION['cart'] = [];
        }
    }

    public function showCart() {
        $cartItems = [];
        $total = 0;
        if ($this->isLoggedIn) {
            $items = $this->cartModel->getItems();
            foreach ($items as $item) {
                $cartItems[] = [
                    'product' => $item,
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['price'] * $item['quantity']
                ];
                $total += $item['price'] * $item['quantity'];
            }
        } else {
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
        }
        $csrfToken = $this->getCsrfToken();
        require_once __DIR__ . '/../views/cart.php';
    }

    public function addToCart() {
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
                'cart_count' => $this->getCartCount(),
                'stock_status' => $stockStatus
            ], 400);
        }
        if ($this->isLoggedIn) {
            $this->cartModel->addItem($productId, $quantity);
        } else {
            if (isset($_SESSION['cart'][$productId])) {
                $newQuantity = $_SESSION['cart'][$productId] + $quantity;
                if (!$this->productModel->isInStock($productId, $newQuantity)) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Insufficient stock for requested quantity',
                        'cart_count' => $this->getCartCount(),
                        'stock_status' => 'out_of_stock'
                    ], 400);
                }
                $_SESSION['cart'][$productId] = $newQuantity;
            } else {
                $_SESSION['cart'][$productId] = $quantity;
            }
        }
        $cartCount = $this->getCartCount();
        $_SESSION['cart_count'] = $cartCount;
        $stockInfo = $this->productModel->checkStock($productId);
        $currentStock = $stockInfo ? ($stockInfo['stock_quantity'] - $quantity) : 0;
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
        $userId = $this->userId;
        $this->logAuditTrail('cart_add', $userId, [
            'product_id' => $productId,
            'quantity' => $quantity,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        $this->jsonResponse([
            'success' => true,
            'message' => htmlspecialchars($product['name']) . ' added to cart',
            'cart_count' => $cartCount,
            'stock_status' => $stockStatus
        ]);
    }

    public function updateCart() {
        $this->validateCSRF();
        $updates = $_POST['updates'] ?? [];
        $stockErrors = [];
        if ($this->isLoggedIn) {
            foreach ($updates as $productId => $quantity) {
                $productId = $this->validateInput($productId, 'int');
                $quantity = (int)$this->validateInput($quantity, 'int');
                if ($quantity > 0) {
                    if (!$this->productModel->isInStock($productId, $quantity)) {
                        $product = $this->productModel->getById($productId);
                        $stockErrors[] = "{$product['name']} has insufficient stock";
                        continue;
                    }
                    $this->cartModel->updateItem($productId, $quantity);
                } else {
                    $this->cartModel->removeItem($productId);
                }
            }
        } else {
            foreach ($updates as $productId => $quantity) {
                $productId = $this->validateInput($productId, 'int');
                $quantity = (int)$this->validateInput($quantity, 'int');
                if ($quantity > 0) {
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
        }
        $this->jsonResponse([
            'success' => empty($stockErrors),
            'message' => empty($stockErrors) ? 'Cart updated' : 'Some items have insufficient stock',
            'cartCount' => $this->getCartCount(),
            'errors' => $stockErrors
        ]);
    }

    public function removeFromCart() {
        $this->validateCSRF();
        $productId = $this->validateInput($_POST['product_id'] ?? null, 'int');
        if ($this->isLoggedIn) {
            $this->cartModel->removeItem($productId);
        } else {
            if (!$productId || !isset($_SESSION['cart'][$productId])) {
                $this->jsonResponse(['success' => false, 'message' => 'Product not found in cart'], 404);
            }
            unset($_SESSION['cart'][$productId]);
        }
        $userId = $this->userId;
        $this->logAuditTrail('cart_remove', $userId, [
            'product_id' => $productId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        $this->jsonResponse([
            'success' => true,
            'message' => 'Product removed from cart',
            'cartCount' => $this->getCartCount()
        ]);
    }

    public function clearCart() {
        if ($this->isLoggedIn) {
            $this->cartModel->clearCart();
        } else {
            $_SESSION['cart'] = [];
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $cart = $this->isLoggedIn ? $this->cartModel->getItems() : $_SESSION['cart'];
        if ($this->isLoggedIn) {
            foreach ($cart as $item) {
                if (!$this->productModel->isInStock($item['product_id'], $item['quantity'])) {
                    $errors[] = "{$item['name']} has insufficient stock";
                }
            }
        } else {
            foreach ($cart as $productId => $quantity) {
                if (!$this->productModel->isInStock($productId, $quantity)) {
                    $product = $this->productModel->getById($productId);
                    $errors[] = "{$product['name']} has insufficient stock";
                }
            }
        }
        return $errors;
    }

    public function getCartItems() {
        $this->initCart();
        $cartItems = [];
        if ($this->isLoggedIn) {
            $items = $this->cartModel->getItems();
            foreach ($items as $item) {
                $cartItems[] = [
                    'product' => $item,
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['price'] * $item['quantity']
                ];
            }
        } else {
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
        }
        return $cartItems;
    }

    private function getCartCount() {
        if ($this->isLoggedIn) {
            $items = $this->cartModel->getItems();
            $count = 0;
            foreach ($items as $item) {
                $count += $item['quantity'];
            }
            return $count;
        } else {
            return array_sum($_SESSION['cart']);
        }
    }

    public function mini() {
        $this->initCart();
        $items = [];
        $subtotal = 0;
        if ($this->isLoggedIn) {
            $cartItems = $this->cartModel->getItems();
            foreach ($cartItems as $item) {
                $items[] = [
                    'product' => [
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'image_url' => $item['image_url'],
                        'price' => $item['price']
                    ],
                    'quantity' => $item['quantity']
                ];
                $subtotal += $item['price'] * $item['quantity'];
            }
        } else {
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $product = $this->productModel->getById($productId);
                if ($product) {
                    $items[] = [
                        'product' => [
                            'id' => $product['id'],
                            'name' => $product['name'],
                            'image_url' => $product['image_url'],
                            'price' => $product['price']
                        ],
                        'quantity' => $quantity
                    ];
                    $subtotal += $product['price'] * $quantity;
                }
            }
        }
        $this->jsonResponse([
            'items' => $items,
            'subtotal' => $subtotal
        ]);
    }
}