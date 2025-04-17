<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../includes/EmailService.php';

class InventoryController extends BaseController {
    private $emailService;
    private $alertThreshold = 5; // Alert when stock drops below this percentage of initial stock
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->emailService = new EmailService();
    }
    
    public function updateStock($productId, $quantity, $type = 'adjustment', $referenceId = null, $notes = null) {
        try {
            $this->requireAdmin();
            $this->validateCSRF();
            
            // Validate inputs
            $productId = $this->validateInput($productId, 'int');
            $quantity = $this->validateInput($quantity, 'float');
            $type = $this->validateInput($type, 'string');
            $referenceId = $this->validateInput($referenceId, 'int');
            $notes = $this->validateInput($notes, 'string');
            
            if (!$productId || !$quantity) {
                throw new Exception('Invalid product or quantity');
            }
            
            $this->beginTransaction();
            
            // Get current stock with locking
            $stmt = $this->pdo->prepare("
                SELECT id, name, stock_quantity, initial_stock, 
                       backorder_allowed, low_stock_threshold
                FROM products 
                WHERE id = ? 
                FOR UPDATE
            ");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product) {
                throw new Exception('Product not found');
            }
            
            // Check if we have enough stock for reduction
            if ($quantity < 0 && !$product['backorder_allowed'] && 
                ($product['stock_quantity'] + $quantity) < 0) {
                throw new Exception('Insufficient stock');
            }
            
            // Update product stock
            $stmt = $this->pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity + ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$quantity, $productId]);
            
            // Record movement with audit trail
            $stmt = $this->pdo->prepare("
                INSERT INTO inventory_movements (
                    product_id, 
                    quantity_change, 
                    previous_quantity,
                    new_quantity,
                    type, 
                    reference_id, 
                    notes, 
                    created_by,
                    ip_address
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $productId,
                $quantity,
                $product['stock_quantity'],
                $product['stock_quantity'] + $quantity,
                $type,
                $referenceId,
                $notes,
                $this->getUserId(),
                $_SERVER['REMOTE_ADDR']
            ]);
            
            // Check stock levels and send alerts if needed
            $newQuantity = $product['stock_quantity'] + $quantity;
            $this->checkStockLevels($product, $newQuantity);
            
            $this->commit();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Stock updated successfully',
                'new_quantity' => $newQuantity
            ]);
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Stock update error: " . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    private function checkStockLevels($product, $newQuantity) {
        // Check if stock is below threshold
        if ($newQuantity <= $product['low_stock_threshold']) {
            // Log low stock alert
            error_log("Low stock alert: {$product['name']} has only {$newQuantity} units left");
            
            // Calculate stock percentage
            $stockPercentage = $product['initial_stock'] > 0 
                ? ($newQuantity / $product['initial_stock']) * 100 
                : 0;
            
            // Send alert if stock is critically low
            if ($stockPercentage <= $this->alertThreshold) {
                $this->emailService->sendLowStockAlert(
                    $product['name'],
                    $newQuantity,
                    $product['initial_stock'],
                    $stockPercentage
                );
            }
        }
    }
    
    public function getInventoryMovements($productId, $startDate = null, $endDate = null, $type = null) {
        try {
            $this->requireAdmin();
            
            $productId = $this->validateInput($productId, 'int');
            $startDate = $this->validateInput($startDate, 'string');
            $endDate = $this->validateInput($endDate, 'string');
            $type = $this->validateInput($type, 'string');
            
            if (!$productId) {
                throw new Exception('Invalid product ID');
            }
            
            $params = [$productId];
            $sql = "
                SELECT 
                    m.*,
                    u.name as user_name,
                    p.name as product_name
                FROM inventory_movements m
                LEFT JOIN users u ON m.created_by = u.id
                JOIN products p ON m.product_id = p.id
                WHERE m.product_id = ?
            ";
            
            if ($startDate) {
                $sql .= " AND m.created_at >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND m.created_at <= ?";
                $params[] = $endDate;
            }
            
            if ($type) {
                $sql .= " AND m.type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY m.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $this->jsonResponse([
                'success' => true,
                'movements' => $stmt->fetchAll()
            ]);
            
        } catch (Exception $e) {
            error_log("Error fetching inventory movements: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve inventory movements'
            ], 500);
        }
    }
    
    public function getStockReport($categoryId = null) {
        try {
            $this->requireAdmin();
            
            $params = [];
            $sql = "
                SELECT 
                    p.id,
                    p.name,
                    p.stock_quantity,
                    p.initial_stock,
                    p.low_stock_threshold,
                    p.backorder_allowed,
                    COALESCE(SUM(CASE WHEN m.type = 'sale' THEN ABS(m.quantity_change) ELSE 0 END), 0) as total_sales,
                    COALESCE(SUM(CASE WHEN m.type = 'return' THEN m.quantity_change ELSE 0 END), 0) as total_returns,
                    CASE 
                        WHEN p.initial_stock > 0 THEN (p.stock_quantity / p.initial_stock) * 100 
                        ELSE 0 
                    END as stock_percentage
                FROM products p
                LEFT JOIN inventory_movements m ON p.id = m.product_id
            ";
            
            if ($categoryId) {
                $sql .= " WHERE p.category_id = ?";
                $params[] = $this->validateInput($categoryId, 'int');
            }
            
            $sql .= " GROUP BY p.id ORDER BY stock_percentage ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $this->jsonResponse([
                'success' => true,
                'report' => $stmt->fetchAll()
            ]);
            
        } catch (Exception $e) {
            error_log("Error generating stock report: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to generate stock report'
            ], 500);
        }
    }
    
    public function adjustStockThreshold($productId, $threshold) {
        try {
            $this->requireAdmin();
            $this->validateCSRF();
            
            $productId = $this->validateInput($productId, 'int');
            $threshold = $this->validateInput($threshold, 'int');
            
            if (!$productId || $threshold < 0) {
                throw new Exception('Invalid product ID or threshold');
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE products 
                SET low_stock_threshold = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$threshold, $productId]);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Stock threshold updated successfully'
            ]);
            
        } catch (Exception $e) {
            error_log("Error updating stock threshold: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to update stock threshold'
            ], 500);
        }
    }
}