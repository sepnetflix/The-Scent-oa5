<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Cart.php';

class CartController extends BaseController {
    // Properties remain the same...
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
        } else {
            $this->initCart(); // Ensures session cart exists for guests
        }
    }

    private function initCart() {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
         // Ensure session cart count exists for guests
         if (!isset($_SESSION['cart_count'])) {
             $_SESSION['cart_count'] = 0;
         }
    }

    // mergeSessionCartOnLogin remains unchanged
    public static function mergeSessionCartOnLogin($pdo, $userId) {
        if (!empty($_SESSION['cart'])) {
            // Ensure Cart model is loaded if called statically
            if (!class_exists('Cart')) {
                require_once __DIR__ . '/../models/Cart.php';
            }
            $cartModel = new Cart($pdo, $userId);
            $cartModel->mergeSessionCart($_SESSION['cart']);
            // After successful merge, update DB cart count? Potentially done by addItem/updateItem logic
        }
        // Always clear session cart after merging attempt
        $_SESSION['cart'] = [];
        $_SESSION['cart_count'] = 0; // Reset guest count
        // Optionally, immediately fetch and set the DB cart count in session here
        // $_SESSION['cart_count'] = $cartModel->getCartCount(); // Assuming Cart model has getCartCount method
    }


    public function showCart() {
        $cartItems = [];
        $total = 0;

        if ($this->isLoggedIn) {
            // Fetch items for logged-in user
            $items = $this->cartModel->getItems();
            foreach ($items as $item) {
                $cartItems[] = [
                    'product' => $item, // Assumes getItems joins product data
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['price'] * $item['quantity']
                ];
                $total += $item['price'] * $item['quantity'];
            }
            // Update session count for logged-in user (optional, but good for consistency)
             $_SESSION['cart_count'] = array_reduce($items, fn($sum, $item) => $sum + $item['quantity'], 0);
        } else {
            // Fetch items for guest from session
            $this->initCart(); // Ensure session cart array exists
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $product = $this->productModel->getById($productId);
                if ($product) {
                    $cartItems[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'subtotal' => $product['price'] * $quantity
                    ];
                    $total += $product['price'] * $quantity;
                } else {
                    // Product might have been deleted, remove from session cart
                    unset($_SESSION['cart'][$productId]);
                }
            }
             // Update session count for guest
             $_SESSION['cart_count'] = array_sum($_SESSION['cart']);
        }

        // Prepare data for the view
        $csrfToken = $this->generateCSRFToken();
        $bodyClass = 'page-cart'; // Define body class
        $pageTitle = 'Your Shopping Cart'; // Define page title

        // Make variables available to the view file
        // Since using require_once, define variables directly in this scope
        extract([
            'cartItems' => $cartItems,
            'total' => $total,
            'csrfToken' => $csrfToken,
            'bodyClass' => $bodyClass,
            'pageTitle' => $pageTitle
        ]);

        // Load the view file
        require_once __DIR__ . '/../views/cart.php';
        // No return needed after require_once for a full page view
    }


    // --- AJAX Methods ---
    // addToCart, updateCart, removeFromCart, clearCart (POST), mini
    // These methods use jsonResponse and don't need modification for this specific requirement.

    // Example: addToCart - remains unchanged
    public function addToCart() {
        $this->validateCSRF(); // Ensures POST has valid form CSRF
        $productId = $this->validateInput($_POST['product_id'] ?? null, 'int');
        $quantity = (int)$this->validateInput($_POST['quantity'] ?? 1, 'int');

        if (!$productId || $quantity < 1) {
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid product or quantity'], 400);
        }
        $product = $this->productModel->getById($productId);
        if (!$product) {
            return $this->jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
        }

        // Calculate current quantity in cart to check against stock
        $currentQuantityInCart = 0;
        if ($this->isLoggedIn) {
            $existingItem = $this->cartModel->getItem($productId); // Assuming Cart model has getItem($productId)
            if ($existingItem) {
                $currentQuantityInCart = $existingItem['quantity'];
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
            $availableStock = $stockInfo ? max(0, $stockInfo['stock_quantity']) : 0; // Get actual available stock
            $message = $availableStock > 0 ? "Only {$availableStock} left in stock." : "Insufficient stock.";

            return $this->jsonResponse([
                'success' => false,
                'message' => $message,
                'cart_count' => $this->getCartCount(),
                'stock_status' => $stockStatus
            ], 400); // Use 400 Bad Request for stock issues
        }

        // Add item
        if ($this->isLoggedIn) {
            $this->cartModel->addItem($productId, $quantity);
            $cartCount = $this->getCartCount(true); // Force DB count update
        } else {
            // Session cart standardized to [productId => quantity]
            $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
            $cartCount = array_sum($_SESSION['cart']);
            $_SESSION['cart_count'] = $cartCount; // Update session count
        }

        // Check stock status *after* adding (for button state feedback)
        $stockInfo = $this->productModel->checkStock($productId);
        $stockStatus = 'in_stock'; // Default
        if ($stockInfo) {
            $remainingStock = $stockInfo['stock_quantity'] - ($this->isLoggedIn ? $this->cartModel->getItem($productId)['quantity'] : $_SESSION['cart'][$productId]);
            if (!$stockInfo['backorder_allowed']) {
                if ($remainingStock <= 0) {
                    $stockStatus = 'out_of_stock';
                } elseif ($stockInfo['low_stock_threshold'] && $remainingStock <= $stockInfo['low_stock_threshold']) {
                    $stockStatus = 'low_stock';
                }
            }
        } else {
            // Should not happen if product exists, but handle defensively
            $stockStatus = 'unknown';
        }


        $userId = $this->userId;
        $this->logAuditTrail('cart_add', $userId, [
            'product_id' => $productId,
            'quantity' => $quantity,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        return $this->jsonResponse([
            'success' => true,
            'message' => htmlspecialchars($product['name']) . ' added to cart',
            'cart_count' => $cartCount,
            'stock_status' => $stockStatus // Provide current stock status after add
        ]);
    }

    // updateCart, removeFromCart, etc. remain largely the same...
    // Ensure they correctly update/return cart_count and handle session vs DB logic
    public function updateCart() {
        $this->validateCSRF();
        $updates = $_POST['updates'] ?? [];
        $stockErrors = [];
        $cartCount = 0; // Initialize cart count

        if ($this->isLoggedIn) {
            foreach ($updates as $productId => $quantity) {
                $productId = $this->validateInput($productId, 'int');
                $quantity = (int)$this->validateInput($quantity, 'int');
                if ($productId === false || $quantity === false) continue; // Skip invalid input

                if ($quantity > 0) {
                    if (!$this->productModel->isInStock($productId, $quantity)) {
                        $product = $this->productModel->getById($productId);
                        $stockErrors[] = "{$product['name']} has insufficient stock";
                        continue; // Skip update for this item
                    }
                    $this->cartModel->updateItem($productId, $quantity);
                } else {
                    $this->cartModel->removeItem($productId);
                }
            }
            $cartCount = $this->getCartCount(true); // Force DB count update
        } else {
            $this->initCart();
            foreach ($updates as $productId => $quantity) {
                 $productId = $this->validateInput($productId, 'int');
                 $quantity = (int)$this->validateInput($quantity, 'int');
                 if ($productId === false || $quantity === false) continue; // Skip invalid input

                if ($quantity > 0) {
                    if (!$this->productModel->isInStock($productId, $quantity)) {
                        $product = $this->productModel->getById($productId);
                         $stockErrors[] = "{$product['name']} has insufficient stock";
                        continue; // Skip update for this item
                    }
                    $_SESSION['cart'][$productId] = $quantity;
                } else {
                    unset($_SESSION['cart'][$productId]);
                }
            }
            $cartCount = array_sum($_SESSION['cart']);
            $_SESSION['cart_count'] = $cartCount; // Update session count
        }

        $userId = $this->userId;
        $this->logAuditTrail('cart_update', $userId, [
            'updates' => $updates, // Log the requested updates
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        return $this->jsonResponse([
            'success' => empty($stockErrors),
            'message' => empty($stockErrors) ? 'Cart updated' : 'Some items have insufficient stock. Cart partially updated.',
            'cart_count' => $cartCount, // Return updated count
            'errors' => $stockErrors
        ]);
    }


    public function removeFromCart() {
        $this->validateCSRF();
        $productId = $this->validateInput($_POST['product_id'] ?? null, 'int');
        if ($productId === false) {
             return $this->jsonResponse(['success' => false, 'message' => 'Invalid product ID'], 400);
        }

        $cartCount = 0; // Initialize
        if ($this->isLoggedIn) {
            $this->cartModel->removeItem($productId);
            $cartCount = $this->getCartCount(true); // Force DB count update
        } else {
            $this->initCart();
            if (!isset($_SESSION['cart'][$productId])) {
                // Optional: return error if not found, or just proceed silently
                // return $this->jsonResponse(['success' => false, 'message' => 'Product not found in cart'], 404);
            }
            unset($_SESSION['cart'][$productId]);
            $cartCount = array_sum($_SESSION['cart']);
            $_SESSION['cart_count'] = $cartCount; // Update session count
        }

        $userId = $this->userId;
        $this->logAuditTrail('cart_remove', $userId, [
            'product_id' => $productId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Product removed from cart',
            'cart_count' => $cartCount // Return updated count
        ]);
    }

     public function clearCart() {
        $cartCount = 0;
        if ($this->isLoggedIn) {
            $this->cartModel->clearCart();
            // Count is implicitly 0
        } else {
            $_SESSION['cart'] = [];
            $_SESSION['cart_count'] = 0;
            // Count is 0
        }
         $userId = $this->userId;
         $this->logAuditTrail('cart_clear', $userId, ['ip' => $_SERVER['REMOTE_ADDR'] ?? null]);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCSRF(); // Validate CSRF for POST request
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Cart cleared',
                'cart_count' => $cartCount
            ]);
        } else {
             // For GET request (e.g., link click), redirect
             $this->setFlashMessage('Cart cleared successfully.', 'success');
            $this->redirect('cart');
        }
    }

     // Helper to get cart count consistently
     private function getCartCount($forceDbCheck = false) {
         if ($this->isLoggedIn) {
             // Optimization: Use session count if available and not forcing DB check
             if (!$forceDbCheck && isset($_SESSION['cart_count'])) {
                 return $_SESSION['cart_count'];
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
         $subtotal = 0;
         $cartCount = 0; // Get current count

         if ($this->isLoggedIn) {
             $cartItems = $this->cartModel->getItems(); // Assumes getItems joins products
             foreach ($cartItems as $item) {
                 // Construct item data exactly as expected by JS
                 $items[] = [
                     'product' => [
                         'id' => $item['product_id'], // Use product_id from cart_items table join
                         'name' => $item['name'],
                         'image' => $item['image'], // Assuming image is directly on product row
                         'price' => $item['price']
                     ],
                     'quantity' => $item['quantity']
                 ];
                 $subtotal += $item['price'] * $item['quantity'];
             }
             $cartCount = $this->getCartCount(true); // Force DB check for accuracy
         } else {
             $this->initCart();
             foreach ($_SESSION['cart'] as $productId => $quantity) {
                 $product = $this->productModel->getById($productId);
                 if ($product) {
                     $items[] = [
                         'product' => [
                             'id' => $product['id'],
                             'name' => $product['name'],
                             'image' => $product['image'],
                             'price' => $product['price']
                         ],
                         'quantity' => $quantity
                     ];
                     $subtotal += $product['price'] * $quantity;
                 }
             }
             $cartCount = $this->getCartCount(); // Get session count
         }

         // Always return success true, even if empty, but indicate via items array
         return $this->jsonResponse([
             'success' => true, // Indicate successful fetch
             'items' => $items,
             'subtotal' => $subtotal,
             'cart_count' => $cartCount // Include count for header update if needed
         ]);
     }


    // validateCartStock and getCartItems remain largely the same
     public function validateCartStock() {
         $errors = [];
         $cart = $this->isLoggedIn ? $this->cartModel->getItems() : ($_SESSION['cart'] ?? []);
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
                     $errors[] = ($product ? $product['name'] : "Product ID {$productId}") . " has insufficient stock";
                 }
             }
         }
         return $errors;
     }

     public function getCartItems() {
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
             $this->initCart();
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


} // End of CartController class

// Assume Cart model needs methods:
// getItem($productId)
// getCartCount()
