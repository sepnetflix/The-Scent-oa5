<?php
class Cart {
    private PDO $pdo; // Use type hint
    private int $userId; // Use type hint

    // Constructor accepts PDO connection and User ID
    public function __construct(PDO $pdo, int $userId) { // Use type hints
        $this->pdo = $pdo;
        $this->userId = $userId;
    }

    /**
     * Adds an item to the user's cart or updates quantity if it exists.
     *
     * @param int $productId The ID of the product.
     * @param int $quantity The quantity to add (default: 1).
     * @return bool True on success, false on failure.
     */
    public function addItem(int $productId, int $quantity = 1): bool {
        try {
            // Check if item already exists
            $stmt = $this->pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$this->userId, $productId]);
            $item = $stmt->fetch();

            if ($item) {
                // Update quantity
                $newQty = $item['quantity'] + $quantity;
                $update = $this->pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?"); // Added user_id check for safety
                return $update->execute([$newQty, $item['id'], $this->userId]);
            } else {
                // Insert new item
                // --- FIX APPLIED HERE: Removed 'added_at' column ---
                $insert = $this->pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
                return $insert->execute([$this->userId, $productId, $quantity]);
                // --- END FIX ---
            }
        } catch (PDOException $e) {
            error_log("Error adding/updating cart item for user {$this->userId}, product {$productId}: " . $e->getMessage());
            return false; // Indicate failure
        }
    }

    /**
     * Updates the quantity of an item in the cart. Removes if quantity <= 0.
     *
     * @param int $productId The ID of the product.
     * @param int $quantity The new quantity.
     * @return bool True on success, false on failure.
     */
    public function updateItem(int $productId, int $quantity): bool {
        if ($quantity <= 0) {
            return $this->removeItem($productId); // Delegate to remove function
        }
        try {
            $stmt = $this->pdo->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
            return $stmt->execute([$quantity, $this->userId, $productId]);
        } catch (PDOException $e) {
             error_log("Error updating cart item quantity for user {$this->userId}, product {$productId}: " . $e->getMessage());
             return false;
        }
    }

    /**
     * Removes an item completely from the cart.
     *
     * @param int $productId The ID of the product.
     * @return bool True on success, false on failure.
     */
    public function removeItem(int $productId): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
            return $stmt->execute([$this->userId, $productId]);
        } catch (PDOException $e) {
             error_log("Error removing cart item for user {$this->userId}, product {$productId}: " . $e->getMessage());
             return false;
        }
    }

    /**
     * Retrieves all items in the user's cart, joined with product details.
     *
     * @return array An array of cart items.
     */
    public function getItems(): array {
        try {
            // Join with products to get details needed for display/calculations
            $stmt = $this->pdo->prepare("
                SELECT ci.product_id, ci.quantity, p.name, p.price, p.image, p.stock_quantity, p.backorder_allowed, p.low_stock_threshold
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.user_id = ?
            ");
            $stmt->execute([$this->userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []; // Return empty array if no items
        } catch (PDOException $e) {
            error_log("Error getting cart items for user {$this->userId}: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }

    /**
     * Removes all items from the user's cart.
     *
     * @return bool True on success, false on failure.
     */
    public function clearCart(): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
            return $stmt->execute([$this->userId]);
        } catch (PDOException $e) {
             error_log("Error clearing cart for user {$this->userId}: " . $e->getMessage());
             return false;
        }
    }

    /**
     * Merges items from a session cart into the user's database cart.
     * Uses addItem which handles quantity updates for existing items.
     *
     * @param array $sessionCart Associative array [productId => quantity].
     */
    public function mergeSessionCart(array $sessionCart): void {
        if (empty($sessionCart)) return;

        // Use transaction for merging multiple items
        $this->pdo->beginTransaction();
        try {
             foreach ($sessionCart as $productId => $item) {
                 // Ensure productId is int
                 $productId = filter_var($productId, FILTER_VALIDATE_INT);
                 if ($productId === false || $productId <= 0) continue; // Skip invalid product IDs

                 // Support both [productId => quantity] and potentially [productId => ['quantity' => x]]
                 $quantity = is_array($item) && isset($item['quantity'])
                              ? filter_var($item['quantity'], FILTER_VALIDATE_INT)
                              : filter_var($item, FILTER_VALIDATE_INT);

                 if ($quantity === false || $quantity <= 0) continue; // Skip invalid quantities

                 // addItem handles checking existing items and adding/updating quantity
                 $this->addItem($productId, $quantity);
             }
             $this->pdo->commit();
        } catch (Exception $e) {
             $this->pdo->rollBack();
             error_log("Error merging session cart for user {$this->userId}: " . $e->getMessage());
             // Decide how to handle merge failure - maybe log specific items that failed?
        }
    }

     /**
     * Gets the total number of items (sum of quantities) in the user's cart.
     *
     * @return int Total item count.
     */
    public function getCartCount(): int {
        try {
            $stmt = $this->pdo->prepare("SELECT SUM(quantity) as total_items FROM cart_items WHERE user_id = ?");
            $stmt->execute([$this->userId]);
            $result = $stmt->fetchColumn();
            return $result ? (int)$result : 0;
        } catch (PDOException $e) {
            error_log("Error getting cart count for user {$this->userId}: " . $e->getMessage());
            return 0; // Return 0 on error
        }
    }

} // End of Cart class
