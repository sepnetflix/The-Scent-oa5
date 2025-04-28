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
