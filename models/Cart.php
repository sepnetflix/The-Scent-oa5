<?php
class Cart {
    private $pdo;
    private $userId;

    public function __construct($pdo, $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }

    public function addItem($productId, $quantity = 1) {
        // Check if item already exists
        $stmt = $this->pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$this->userId, $productId]);
        $item = $stmt->fetch();
        if ($item) {
            // Update quantity
            $newQty = $item['quantity'] + $quantity;
            $update = $this->pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $update->execute([$newQty, $item['id']]);
        } else {
            // Insert new item
            $insert = $this->pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
            $insert->execute([$this->userId, $productId, $quantity]);
        }
    }

    public function updateItem($productId, $quantity) {
        if ($quantity <= 0) {
            $this->removeItem($productId);
            return;
        }
        $stmt = $this->pdo->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$quantity, $this->userId, $productId]);
    }

    public function removeItem($productId) {
        $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$this->userId, $productId]);
    }

    public function getItems() {
        $stmt = $this->pdo->prepare("SELECT ci.product_id, ci.quantity, p.* FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = ?");
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clearCart() {
        $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $stmt->execute([$this->userId]);
    }

    public function mergeSessionCart($sessionCart) {
        if (!is_array($sessionCart)) return;
        foreach ($sessionCart as $productId => $item) {
            // Support both [productId => quantity] and [productId => ['quantity' => x]]
            $quantity = is_array($item) && isset($item['quantity']) ? $item['quantity'] : $item;
            $this->addItem($productId, $quantity);
        }
    }
}
