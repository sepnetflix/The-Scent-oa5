<?php
// controllers/InventoryController.php (Updated)

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
// EmailService is included via BaseController's include

class InventoryController extends BaseController {
    // private $emailService; // Removed - Inherited from BaseController
    private Product $productModel; // Added type hint
    private $alertThreshold = 5;

    // Constructor now only needs PDO, EmailService is handled by parent
    public function __construct(PDO $pdo) { // Use type hint PDO $pdo
        parent::__construct($pdo); // Calls parent constructor which initializes $this->db and $this->emailService
        $this->productModel = new Product($pdo); // Initialize Product model
    }

    // --- updateStock Method ---
     // Added type hints and clarified variable usage
     public function updateStock(int $productId, float $quantity, string $type = 'adjustment', ?int $referenceId = null, ?string $notes = null) {
         try {
             $this->requireAdmin(); // Check admin role
             // CSRF validation needed if triggered by a form POST
             // Assuming this might be called internally or via secured API for now
             // $this->validateCSRF(); // Uncomment if called via form

             // Validate inputs (Basic validation done via type hints, add more if needed)
             $type = $this->validateInput($type, 'string'); // Validate type string further if needed
             $notes = $this->validateInput($notes, 'string'); // Ensure notes are safe

             if (!$type || !in_array($type, ['sale', 'restock', 'return', 'adjustment'])) {
                  throw new Exception('Invalid inventory movement type');
             }

             $this->beginTransaction();

             // Get current stock with locking (use $this->db)
             $stmt = $this->db->prepare("
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

             // Use stricter comparison and check backorder logic
             $newPotentialStock = $product['stock_quantity'] + $quantity;
             if ($quantity < 0 && !$product['backorder_allowed'] && $newPotentialStock < 0) {
                 throw new Exception('Insufficient stock for ' . htmlspecialchars($product['name']));
             }

             // Update product stock (use $this->db)
             $updateStmt = $this->db->prepare("
                 UPDATE products
                 SET stock_quantity = stock_quantity + ?,
                     updated_at = NOW()
                 WHERE id = ?
             ");
             $updateStmt->execute([$quantity, $productId]);

             // Record movement with audit trail (use $this->db)
             $movementStmt = $this->db->prepare("
                 INSERT INTO inventory_movements (
                     product_id,
                     quantity_change,
                     previous_quantity,
                     new_quantity,
                     type,
                     reference_id,
                     notes,
                     created_by,
                     ip_address,
                     created_at
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ");
             $movementStmt->execute([
                 $productId,
                 $quantity,
                 $product['stock_quantity'], // Previous quantity before update
                 $newPotentialStock, // New quantity after update
                 $type,
                 $referenceId,
                 $notes,
                 $this->getUserId(), // Get current admin user ID
                 $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
             ]);

             // Check stock levels and send alerts if needed
             $this->checkStockLevels($product, $newPotentialStock);

             $this->commit();

             // Use standardized jsonResponse
             return $this->jsonResponse([
                 'success' => true,
                 'message' => 'Stock updated successfully for ' . htmlspecialchars($product['name']),
                 'new_quantity' => $newPotentialStock
             ]);

         } catch (Exception $e) {
             $this->rollback();
             error_log("Stock update error for product {$productId}: " . $e->getMessage());

             // Use standardized jsonResponse for errors
             return $this->jsonResponse([
                 'success' => false,
                 'message' => $e->getMessage() // Provide specific error message
             ], 500);
         }
     }


    // Updated checkStockLevels to handle potential missing EmailService method
     private function checkStockLevels(array $product, float $newQuantity): void {
         // Defensive check for necessary keys
         if (!isset($product['low_stock_threshold']) || !isset($product['initial_stock'])) {
              error_log("Missing stock level data for product ID {$product['id']} in checkStockLevels");
              return;
         }

         // Ensure threshold is numeric
         $lowStockThreshold = filter_var($product['low_stock_threshold'], FILTER_VALIDATE_INT);
         if ($lowStockThreshold === false) $lowStockThreshold = 0; // Default to 0 if invalid


         // Check if stock is below threshold
         if ($newQuantity <= $lowStockThreshold && $lowStockThreshold > 0) { // Only alert if threshold > 0
             // Log low stock alert consistently
             $logMessage = "Low stock alert: Product '{$product['name']}' (ID: {$product['id']}) has only {$newQuantity} units left (Threshold: {$lowStockThreshold}).";
             error_log($logMessage);
             $this->logSecurityEvent('low_stock_alert', ['product_id' => $product['id'], 'product_name' => $product['name'], 'current_stock' => $newQuantity, 'threshold' => $lowStockThreshold]);

             // Calculate stock percentage if initial stock is valid
             $initialStock = filter_var($product['initial_stock'], FILTER_VALIDATE_INT);
             $stockPercentage = ($initialStock !== false && $initialStock > 0)
                 ? ($newQuantity / $initialStock) * 100
                 : 0; // Avoid division by zero

             // Ensure alert threshold is numeric
             $alertThresholdPercent = filter_var($this->alertThreshold, FILTER_VALIDATE_FLOAT);
             if ($alertThresholdPercent === false) $alertThresholdPercent = 5.0; // Default percentage

             // Send alert email if stock is critically low based on percentage
             // Check if the method exists before calling
             if ($stockPercentage <= $alertThresholdPercent && method_exists($this->emailService, 'sendLowStockAlert')) {
                 try {
                    $this->emailService->sendLowStockAlert(
                         $product['name'],
                         $newQuantity,
                         $initialStock > 0 ? $initialStock : 'N/A', // Handle case where initial stock might be 0 or invalid
                         $stockPercentage
                     );
                 } catch (Exception $e) {
                      error_log("Failed to send low stock alert email for product ID {$product['id']}: " . $e->getMessage());
                 }

             }
         }
     }


    // --- getInventoryMovements Method ---
     // Added type hints and PDO usage correction
     public function getInventoryMovements(int $productId, ?string $startDate = null, ?string $endDate = null, ?string $type = null) {
         try {
             $this->requireAdmin();

             // Validate optional parameters further if needed (e.g., date format)
             $type = $this->validateInput($type, 'string'); // Basic validation

             $params = [$productId];
             $sql = "
                 SELECT
                     m.id, m.quantity_change, m.previous_quantity, m.new_quantity,
                     m.type, m.reference_id, m.notes, m.created_at, m.ip_address,
                     u.name as user_name,
                     p.name as product_name
                 FROM inventory_movements m
                 LEFT JOIN users u ON m.created_by = u.id
                 JOIN products p ON m.product_id = p.id
                 WHERE m.product_id = ?
             ";

             if ($startDate) {
                 // Basic date validation attempt
                 if (DateTime::createFromFormat('Y-m-d', $startDate) !== false) {
                     $sql .= " AND DATE(m.created_at) >= ?";
                     $params[] = $startDate;
                 } else {
                     // Handle invalid date format? Log or ignore?
                      error_log("Invalid start date format provided: " . $startDate);
                 }
             }

             if ($endDate) {
                  if (DateTime::createFromFormat('Y-m-d', $endDate) !== false) {
                      $sql .= " AND DATE(m.created_at) <= ?";
                      $params[] = $endDate;
                  } else {
                      error_log("Invalid end date format provided: " . $endDate);
                  }
             }

             if ($type && in_array($type, ['sale', 'restock', 'return', 'adjustment'])) {
                 $sql .= " AND m.type = ?";
                 $params[] = $type;
             }

             $sql .= " ORDER BY m.created_at DESC";

             // Use $this->db
             $stmt = $this->db->prepare($sql);
             $stmt->execute($params);

             return $this->jsonResponse([
                 'success' => true,
                 'movements' => $stmt->fetchAll()
             ]);

         } catch (Exception $e) {
             error_log("Error fetching inventory movements for product {$productId}: " . $e->getMessage());
             return $this->jsonResponse([
                 'success' => false,
                 'message' => 'Failed to retrieve inventory movements'
             ], 500);
         }
     }


    // --- getStockReport Method ---
    // Added type hints and PDO usage correction
     public function getStockReport(?int $categoryId = null) {
         try {
             $this->requireAdmin();

             $params = [];
             // Added c.name for category name in report
             $sql = "
                 SELECT
                     p.id,
                     p.name,
                     p.sku, -- Added SKU
                     c.name as category_name, -- Added Category Name
                     p.stock_quantity,
                     p.initial_stock,
                     p.low_stock_threshold,
                     p.backorder_allowed,
                     -- Corrected SUM logic for movements (assuming quantity_change is negative for sales)
                     COALESCE(SUM(CASE WHEN m.type = 'sale' THEN ABS(m.quantity_change) ELSE 0 END), 0) as total_sales_units,
                     COALESCE(SUM(CASE WHEN m.type = 'return' THEN m.quantity_change ELSE 0 END), 0) as total_returns_units,
                     COALESCE(SUM(CASE WHEN m.type = 'restock' THEN m.quantity_change ELSE 0 END), 0) as total_restock_units,
                     COALESCE(SUM(CASE WHEN m.type = 'adjustment' THEN m.quantity_change ELSE 0 END), 0) as total_adjustment_units,
                     CASE
                         WHEN p.initial_stock > 0 THEN ROUND((p.stock_quantity / p.initial_stock) * 100, 2)
                         ELSE NULL -- Use NULL if initial stock is 0 or invalid
                     END as stock_percentage
                 FROM products p
                 LEFT JOIN inventory_movements m ON p.id = m.product_id
                 LEFT JOIN categories c ON p.category_id = c.id -- Join categories table
             ";

             if ($categoryId) {
                 $sql .= " WHERE p.category_id = ?";
                 $params[] = $categoryId; // Already validated if passed as int
             }

             $sql .= " GROUP BY p.id, c.name ORDER BY p.name ASC"; // Group by category name too, order by product name

             // Use $this->db
             $stmt = $this->db->prepare($sql);
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


    // --- adjustStockThreshold Method ---
     // Added type hints and PDO usage correction
     public function adjustStockThreshold(int $productId, int $threshold) {
         try {
             $this->requireAdmin();
             // Assuming called via AJAX POST
             $this->validateCSRF(); // Validate if called from a form/AJAX requiring CSRF

             if ($threshold < 0) { // Basic validation
                 throw new Exception('Threshold cannot be negative');
             }

             // Use $this->db
             $stmt = $this->db->prepare("
                 UPDATE products
                 SET low_stock_threshold = ?,
                     updated_at = NOW()
                 WHERE id = ?
             ");
             $stmt->execute([$threshold, $productId]);

             if ($stmt->rowCount() > 0) {
                  $this->logAuditTrail('stock_threshold_update', $this->getUserId(), ['product_id' => $productId, 'new_threshold' => $threshold]);
                  return $this->jsonResponse([
                     'success' => true,
                     'message' => 'Stock threshold updated successfully'
                  ]);
             } else {
                  // Product ID might not exist or threshold was already the same
                  // Check if product exists
                  $checkStmt = $this->db->prepare("SELECT id FROM products WHERE id = ?");
                  $checkStmt->execute([$productId]);
                  if (!$checkStmt->fetch()) {
                       throw new Exception('Product not found');
                  } else {
                       // Threshold was likely unchanged
                       return $this->jsonResponse([
                           'success' => true, // Or false depending on desired behavior
                           'message' => 'Stock threshold unchanged or product not found.'
                       ]);
                  }
             }

         } catch (Exception $e) {
             error_log("Error updating stock threshold for product {$productId}: " . $e->getMessage());
             return $this->jsonResponse([
                 'success' => false,
                 'message' => 'Failed to update stock threshold: ' . $e->getMessage()
             ], 500);
         }
     }


} // End of InventoryController class
