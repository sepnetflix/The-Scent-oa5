<?php
class Order {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO orders (
                user_id, total_amount, shipping_name, shipping_email,
                shipping_address, shipping_city, shipping_state,
                shipping_zip, shipping_country, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['user_id'],
            $data['total_amount'],
            $data['shipping_name'],
            $data['shipping_email'],
            $data['shipping_address'],
            $data['shipping_city'],
            $data['shipping_state'],
            $data['shipping_zip'],
            $data['shipping_country'],
            'pending'
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    public function getById($id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM orders WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getRecentByUserId($userId, $limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT o.*, 
                   GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as items
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function updateStatus($orderId, $status) {
        $stmt = $this->pdo->prepare("
            UPDATE orders SET status = ? WHERE id = ?
        ");
        return $stmt->execute([$status, $orderId]);
    }

    public function updatePaymentIntent($orderId, $paymentIntentId) {
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET payment_intent_id = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$paymentIntentId, $orderId]);
    }
    
    public function updatePaymentStatus($orderId, $status, $stripeCustomerId = null) {
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET payment_status = ?,
                paid_at = CASE WHEN ? = 'completed' THEN NOW() ELSE NULL END,
                stripe_customer_id = COALESCE(?, stripe_customer_id)
            WHERE id = ?
        ");
        return $stmt->execute([$status, $status, $stripeCustomerId, $orderId]);
    }
    
    public function getByPaymentIntent($paymentIntentId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM orders WHERE payment_intent_id = ?
        ");
        $stmt->execute([$paymentIntentId]);
        return $stmt->fetch();
    }
}