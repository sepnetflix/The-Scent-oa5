<?php
require_once __DIR__ . '/BaseController.php';

class CouponController extends BaseController {
    private $pdo;
    private $rateLimit = 10; // Maximum validation attempts per hour
    private $rateLimitWindow = 3600; // 1 hour in seconds
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function validateCoupon($code, $subtotal) {
        try {
            $this->validateCSRF();
            
            // Rate limiting
            if (!$this->checkRateLimit($_SERVER['REMOTE_ADDR'])) {
                return $this->jsonResponse([
                    'valid' => false,
                    'message' => 'Too many attempts. Please try again later.'
                ], 429);
            }
            
            $code = $this->validateInput($code, 'string');
            $subtotal = $this->validateInput($subtotal, 'float');
            $userId = $this->getUserId();
            
            if (!$code || $subtotal <= 0) {
                return $this->jsonResponse([
                    'valid' => false,
                    'message' => 'Invalid coupon or order amount'
                ], 400);
            }
            
            $stmt = $this->pdo->prepare("
                SELECT * FROM coupons 
                WHERE code = ? 
                AND is_active = TRUE
                AND (start_date IS NULL OR start_date <= NOW())
                AND (end_date IS NULL OR end_date >= NOW())
                AND (usage_limit IS NULL OR usage_count < usage_limit)
                AND min_purchase_amount <= ?
            ");
            $stmt->execute([$code, $subtotal]);
            $coupon = $stmt->fetch();
            
            if (!$coupon) {
                return $this->jsonResponse([
                    'valid' => false,
                    'message' => 'Invalid or expired coupon code'
                ]);
            }
            
            // Check if user has already used this coupon
            if ($userId) {
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) FROM coupon_usage 
                    WHERE coupon_id = ? AND user_id = ?
                ");
                $stmt->execute([$coupon['id'], $userId]);
                $usageCount = $stmt->fetchColumn();
                
                if ($usageCount > 0) {
                    return $this->jsonResponse([
                        'valid' => false,
                        'message' => 'You have already used this coupon'
                    ]);
                }
            }
            
            // Calculate discount
            $discountAmount = $this->calculateDiscount($coupon, $subtotal);
            
            return $this->jsonResponse([
                'valid' => true,
                'coupon' => $coupon,
                'discount_amount' => $discountAmount,
                'message' => 'Coupon applied successfully'
            ]);
            
        } catch (Exception $e) {
            error_log("Coupon validation error: " . $e->getMessage());
            return $this->jsonResponse([
                'valid' => false,
                'message' => 'An error occurred while validating the coupon'
            ], 500);
        }
    }
    
    private function calculateDiscount($coupon, $subtotal) {
        $discountAmount = 0;
        
        if ($coupon['discount_type'] === 'percentage') {
            $discountAmount = $subtotal * ($coupon['discount_value'] / 100);
        } else { // fixed amount
            $discountAmount = $coupon['discount_value'];
        }
        
        // Apply maximum discount limit if set
        if ($coupon['max_discount_amount'] !== null) {
            $discountAmount = min($discountAmount, $coupon['max_discount_amount']);
        }
        
        return round($discountAmount, 2);
    }
    
    public function applyCoupon($couponId, $orderId, $discountAmount) {
        try {
            $this->validateCSRF();
            $userId = $this->getUserId();
            
            $couponId = $this->validateInput($couponId, 'int');
            $orderId = $this->validateInput($orderId, 'int');
            $discountAmount = $this->validateInput($discountAmount, 'float');
            
            if (!$couponId || !$orderId || $discountAmount <= 0) {
                throw new Exception('Invalid coupon application data');
            }
            
            $this->beginTransaction();
            
            // Verify coupon is still valid
            $stmt = $this->pdo->prepare("
                SELECT * FROM coupons 
                WHERE id = ? 
                AND is_active = TRUE
                AND (usage_limit IS NULL OR usage_count < usage_limit)
            ");
            $stmt->execute([$couponId]);
            if (!$stmt->fetch()) {
                throw new Exception('Coupon is no longer valid');
            }
            
            // Record coupon usage
            $stmt = $this->pdo->prepare("
                INSERT INTO coupon_usage (coupon_id, order_id, user_id, discount_amount)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$couponId, $orderId, $userId, $discountAmount]);
            
            // Update coupon usage count
            $stmt = $this->pdo->prepare("
                UPDATE coupons 
                SET usage_count = usage_count + 1,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$couponId]);
            
            $this->commit();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Coupon applied successfully'
            ]);
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Coupon application error: " . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to apply coupon'
            ], 500);
        }
    }
    
    public function getAllCoupons() {
        try {
            $this->requireAdmin();
            
            $stmt = $this->pdo->query("
                SELECT 
                    c.*,
                    COUNT(cu.id) as total_uses,
                    SUM(cu.discount_amount) as total_discount_given
                FROM coupons c
                LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id
                GROUP BY c.id
                ORDER BY c.created_at DESC
            ");
            
            return $this->jsonResponse([
                'success' => true,
                'coupons' => $stmt->fetchAll()
            ]);
            
        } catch (Exception $e) {
            error_log("Error fetching coupons: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve coupons'
            ], 500);
        }
    }
    
    public function createCoupon() {
        try {
            $this->requireAdmin();
            $this->validateCSRF();
            
            $data = [
                'code' => $this->validateInput($_POST['code'], 'string'),
                'description' => $this->validateInput($_POST['description'], 'string'),
                'discount_type' => $this->validateInput($_POST['discount_type'], 'string'),
                'discount_value' => $this->validateInput($_POST['discount_value'], 'float'),
                'min_purchase_amount' => $this->validateInput($_POST['min_purchase_amount'] ?? 0, 'float'),
                'max_discount_amount' => $this->validateInput($_POST['max_discount_amount'] ?? null, 'float'),
                'start_date' => $this->validateInput($_POST['start_date'] ?? null, 'string'),
                'end_date' => $this->validateInput($_POST['end_date'] ?? null, 'string'),
                'usage_limit' => $this->validateInput($_POST['usage_limit'] ?? null, 'int'),
                'is_active' => isset($_POST['is_active']) ? true : false
            ];
            
            // Validate required fields
            if (!$data['code'] || !$data['discount_type'] || $data['discount_value'] <= 0) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Missing required fields'
                ], 400);
            }
            
            // Validate discount type
            if (!in_array($data['discount_type'], ['percentage', 'fixed'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Invalid discount type'
                ], 400);
            }
            
            $this->beginTransaction();
            
            // Check if code already exists
            $stmt = $this->pdo->prepare("SELECT id FROM coupons WHERE code = ?");
            $stmt->execute([$data['code']]);
            if ($stmt->fetch()) {
                throw new Exception('Coupon code already exists');
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO coupons (
                    code, description, discount_type, discount_value,
                    min_purchase_amount, max_discount_amount,
                    start_date, end_date, usage_limit, is_active,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['code'],
                $data['description'],
                $data['discount_type'],
                $data['discount_value'],
                $data['min_purchase_amount'],
                $data['max_discount_amount'],
                $data['start_date'],
                $data['end_date'],
                $data['usage_limit'],
                $data['is_active'],
                $this->getUserId()
            ]);
            
            $this->commit();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Coupon created successfully'
            ]);
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Coupon creation error: " . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to create coupon'
            ], 500);
        }
    }
    
    private function checkRateLimit($ip) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM coupon_validation_attempts
            WHERE ip_address = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$ip]);
        
        return $stmt->fetchColumn() < $this->rateLimit;
    }
    
    private function logValidationAttempt($ip, $code, $success) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO coupon_validation_attempts 
                (ip_address, coupon_code, success)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$ip, $code, $success]);
        } catch (Exception $e) {
            error_log("Failed to log coupon validation attempt: " . $e->getMessage());
        }
    }
}