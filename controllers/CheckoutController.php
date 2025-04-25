<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/InventoryController.php';
require_once __DIR__ . '/../controllers/TaxController.php';
require_once __DIR__ . '/../includes/EmailService.php';

class CheckoutController extends BaseController {
    private $productModel;
    private $orderModel;
    private $inventoryController;
    private $taxController;
    private $paymentController;
    private $emailService;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->orderModel = new Order($pdo);
        $this->inventoryController = new InventoryController($pdo);
        $this->taxController = new TaxController($pdo);
        $this->paymentController = new PaymentController();
        $this->emailService = new EmailService();
    }
    
    public function showCheckout() {
        $isLoggedIn = isset($_SESSION['user_id']);
        $cartItems = [];
        $subtotal = 0;
        if ($isLoggedIn) {
            require_once __DIR__ . '/../models/Cart.php';
            $cartModel = new \Cart($this->pdo, $_SESSION['user_id']);
            $items = $cartModel->getItems();
            foreach ($items as $item) {
                $cartItems[] = [
                    'product' => $item,
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['price'] * $item['quantity']
                ];
                $subtotal += $item['price'] * $item['quantity'];
            }
        } else {
            if (empty($_SESSION['cart'])) {
                $this->redirect('cart');
            }
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $product = $this->productModel->getById($productId);
                if ($product) {
                    $cartItems[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'subtotal' => $product['price'] * $quantity
                    ];
                    $subtotal += $product['price'] * $quantity;
                }
            }
        }
        $tax_rate_formatted = '0%';
        $tax_amount = 0;
        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $total = $subtotal + $shipping_cost + $tax_amount;
        require_once __DIR__ . '/../views/checkout.php';
    }
    
    public function calculateTax() {
        $this->validateCSRF();
        $data = json_decode(file_get_contents('php://input'), true);
        $country = $this->validateInput($data['country'] ?? '', 'string');
        $state = $this->validateInput($data['state'] ?? '', 'string');
        if (empty($country)) {
            $this->jsonResponse(['success' => false, 'error' => 'Country is required'], 400);
        }
        $subtotal = $this->calculateCartSubtotal();
        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $tax_amount = $this->taxController->calculateTax($subtotal, $country, $state);
        $tax_rate = $this->taxController->getTaxRate($country, $state);
        $total = $subtotal + $shipping_cost + $tax_amount;
        $this->jsonResponse([
            'success' => true,
            'tax_rate_formatted' => $this->taxController->formatTaxRate($tax_rate),
            'tax_amount' => number_format($tax_amount, 2),
            'total' => number_format($total, 2)
        ]);
    }
    
    private function calculateCartSubtotal() {
        $isLoggedIn = isset($_SESSION['user_id']);
        $subtotal = 0;
        if ($isLoggedIn) {
            require_once __DIR__ . '/../models/Cart.php';
            $cartModel = new \Cart($this->pdo, $_SESSION['user_id']);
            $items = $cartModel->getItems();
            foreach ($items as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
        } else {
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $product = $this->productModel->getById($productId);
                if ($product) {
                    $subtotal += $product['price'] * $quantity;
                }
            }
        }
        return $subtotal;
    }
    
    public function processCheckout() {
        $this->validateRateLimit('checkout_submit');
        $this->requireLogin();
        $this->validateCSRF();
        $isLoggedIn = isset($_SESSION['user_id']);
        $cartItems = [];
        $subtotal = 0;
        if ($isLoggedIn) {
            require_once __DIR__ . '/../models/Cart.php';
            $cartModel = new \Cart($this->pdo, $_SESSION['user_id']);
            $items = $cartModel->getItems();
            foreach ($items as $item) {
                $cartItems[$item['id']] = $item['quantity'];
                $subtotal += $item['price'] * $item['quantity'];
            }
        } else {
            if (empty($_SESSION['cart'])) {
                $this->redirect('cart');
            }
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $cartItems[$productId] = $quantity;
                $product = $this->productModel->getById($productId);
                if ($product) {
                    $subtotal += $product['price'] * $quantity;
                }
            }
        }
        $required = ['shipping_name', 'shipping_email', 'shipping_address', 'shipping_city', 
                    'shipping_state', 'shipping_zip', 'shipping_country'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $this->setFlashMessage('Please fill in all required fields.', 'error');
                $this->redirect('checkout');
            }
        }
        try {
            $this->beginTransaction();
            $stockErrors = $this->validateCartStock($cartItems);
            if (!empty($stockErrors)) {
                throw new Exception('Some items are out of stock: ' . implode(', ', $stockErrors));
            }
            $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
            $tax_amount = $this->taxController->calculateTax(
                $subtotal,
                $this->validateInput($_POST['shipping_country'], 'string'),
                $this->validateInput($_POST['shipping_state'], 'string')
            );
            $total = $subtotal + $shipping_cost + $tax_amount;
            $userId = $this->getUserId();
            $orderData = [
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'shipping_cost' => $shipping_cost,
                'tax_amount' => $tax_amount,
                'total_amount' => $total,
                'shipping_name' => $this->validateInput($_POST['shipping_name'], 'string'),
                'shipping_email' => $this->validateInput($_POST['shipping_email'], 'email'),
                'shipping_address' => $this->validateInput($_POST['shipping_address'], 'string'),
                'shipping_city' => $this->validateInput($_POST['shipping_city'], 'string'),
                'shipping_state' => $this->validateInput($_POST['shipping_state'], 'string'),
                'shipping_zip' => $this->validateInput($_POST['shipping_zip'], 'string'),
                'shipping_country' => $this->validateInput($_POST['shipping_country'], 'string')
            ];
            $orderId = $this->orderModel->create($orderData);
            $stmt = $this->pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($cartItems as $productId => $quantity) {
                $product = $this->productModel->getById($productId);
                if ($product) {
                    $stmt->execute([
                        $orderId,
                        $productId,
                        $quantity,
                        $product['price']
                    ]);
                    if (!$this->inventoryController->updateStock(
                        $productId,
                        -$quantity,
                        'order',
                        $orderId,
                        "Order #{$orderId}"
                    )) {
                        throw new Exception("Failed to update inventory for product {$product['name']}");
                    }
                }
            }
            $paymentResult = $this->paymentController->processPayment($orderId);
            if (!$paymentResult['success']) {
                throw new Exception($paymentResult['error']);
            }
            $this->commit();
            $this->logAuditTrail('order_placed', $userId, [
                'order_id' => $orderId,
                'total_amount' => $total,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
            $user = $this->getCurrentUser();
            $order = $this->orderModel->getById($orderId);
            $this->emailService->sendOrderConfirmation($order, $user);
            $_SESSION['last_order_id'] = $orderId;
            if ($isLoggedIn) {
                $cartModel->clearCart();
            } else {
                $_SESSION['cart'] = [];
            }
            $this->jsonResponse([
                'success' => true,
                'orderId' => $orderId,
                'clientSecret' => $paymentResult['clientSecret']
            ]);
        } catch (Exception $e) {
            $this->rollback();
            error_log($e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'error' => 'An error occurred while processing your order. Please try again.'
            ], 500);
        }
    }
    
    private function validateCartStock($cartItems = null) {
        $errors = [];
        if ($cartItems === null) {
            $isLoggedIn = isset($_SESSION['user_id']);
            if ($isLoggedIn) {
                require_once __DIR__ . '/../models/Cart.php';
                $cartModel = new \Cart($this->pdo, $_SESSION['user_id']);
                $items = $cartModel->getItems();
                foreach ($items as $item) {
                    if (!$this->productModel->isInStock($item['product_id'], $item['quantity'])) {
                        $errors[] = "{$item['name']} has insufficient stock";
                    }
                }
            } else {
                foreach ($_SESSION['cart'] as $productId => $quantity) {
                    if (!$this->productModel->isInStock($productId, $quantity)) {
                        $product = $this->productModel->getById($productId);
                        $errors[] = "{$product['name']} has insufficient stock";
                    }
                }
            }
        } else {
            foreach ($cartItems as $productId => $quantity) {
                if (!$this->productModel->isInStock($productId, $quantity)) {
                    $product = $this->productModel->getById($productId);
                    $errors[] = "{$product['name']} has insufficient stock";
                }
            }
        }
        return $errors;
    }
    
    public function showOrderConfirmation() {
        $this->requireLogin();
        
        if (!isset($_SESSION['last_order_id'])) {
            $this->redirect('products');
        }
        
        $stmt = $this->pdo->prepare("
            SELECT o.*, oi.product_id, oi.quantity, oi.price, p.name as product_name
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE o.id = ? AND o.user_id = ?
        ");
        
        $stmt->execute([$_SESSION['last_order_id'], $this->getUserId()]);
        $orderItems = $stmt->fetchAll();
        
        if (empty($orderItems)) {
            $this->redirect('products');
        }
        
        $order = [
            'id' => $orderItems[0]['id'],
            'total_amount' => $orderItems[0]['total_amount'],
            'shipping_name' => $orderItems[0]['shipping_name'],
            'shipping_email' => $orderItems[0]['shipping_email'],
            'shipping_address' => $orderItems[0]['shipping_address'],
            'shipping_city' => $orderItems[0]['shipping_city'],
            'shipping_state' => $orderItems[0]['shipping_state'],
            'shipping_zip' => $orderItems[0]['shipping_zip'],
            'shipping_country' => $orderItems[0]['shipping_country'],
            'created_at' => $orderItems[0]['created_at'],
            'items' => $orderItems
        ];
        
        unset($_SESSION['last_order_id']);
        
        require_once __DIR__ . '/../views/order_confirmation.php';
    }
    
    public function updateOrderStatus($orderId, $status, $trackingInfo = null) {
        $this->requireAdmin();
        $this->validateCSRF();
        
        $order = $this->orderModel->getById($orderId);
        
        if (!$order) {
            $this->jsonResponse(['success' => false, 'error' => 'Order not found'], 404);
        }
        
        try {
            $this->beginTransaction();
            
            $this->orderModel->updateStatus($orderId, $status);
            
            if ($status === 'shipped' && $trackingInfo) {
                $this->orderModel->updateTracking(
                    $orderId,
                    $trackingInfo['number'],
                    $trackingInfo['carrier'],
                    $trackingInfo['url']
                );
                
                $user = (new User($this->pdo))->getById($order['user_id']);
                $this->emailService->sendShippingUpdate(
                    $order,
                    $user,
                    $trackingInfo['number'],
                    $trackingInfo['carrier']
                );
            }
            
            $this->commit();
            $this->jsonResponse(['success' => true]);
            
        } catch (Exception $e) {
            $this->rollback();
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}