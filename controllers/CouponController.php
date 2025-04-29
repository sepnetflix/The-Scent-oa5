<?php
// controllers/CouponController.php (Corrected based on diff analysis)

require_once __DIR__ . '/BaseController.php';

class CouponController extends BaseController {

    public function __construct($pdo) {
        parent::__construct($pdo);
    }

    /**
     * Core validation logic for a coupon code.
     * Checks active status, dates, usage limits, minimum purchase.
     * Does NOT check user-specific usage here.
     * Provides specific error messages.
     *
     * @param string $code
     * @param float $subtotal
     * @return array ['valid' => bool, 'message' => string, 'coupon' => array|null]
     */
    public function validateCouponCodeOnly(string $code, float $subtotal): array {
        $code = $this->validateInput($code, 'string');
        $subtotal = $this->validateInput($subtotal, 'float');

        if (!$code || $subtotal === false || $subtotal < 0) {
            return ['valid' => false, 'message' => 'Invalid coupon code or subtotal amount.', 'coupon' => null];
        }

        try {
            // First, try to fetch an active, valid coupon meeting all criteria
            $stmt = $this->db->prepare("
                SELECT * FROM coupons
                WHERE code = ?
                AND is_active = TRUE
                AND (valid_from IS NULL OR valid_from <= CURDATE())
                AND (valid_to IS NULL OR valid_to >= CURDATE())
                AND (usage_limit IS NULL OR usage_count < usage_limit)
                AND (min_purchase_amount IS NULL OR min_purchase_amount <= ?)
            ");
            $stmt->execute([$code, $subtotal]);
            $coupon = $stmt->fetch();

            // If a perfectly valid coupon is found, return it
            if ($coupon) {
                return ['valid' => true, 'message' => 'Coupon code is potentially valid.', 'coupon' => $coupon];
            }

            // If no perfectly valid coupon found, check *why* it might be invalid
            $stmtCheck = $this->db->prepare("SELECT * FROM coupons WHERE code = ?");
            $stmtCheck->execute([$code]);
            $existingCoupon = $stmtCheck->fetch();

            if (!$existingCoupon) {
                return ['valid' => false, 'message' => 'Coupon code not found.', 'coupon' => null];
            } elseif (!$existingCoupon['is_active']) {
                return ['valid' => false, 'message' => 'Coupon is not active.', 'coupon' => null];
            } elseif ($existingCoupon['valid_from'] && $existingCoupon['valid_from'] > date('Y-m-d')) {
                return ['valid' => false, 'message' => 'Coupon is not yet valid.', 'coupon' => null];
            } elseif ($existingCoupon['valid_to'] && $existingCoupon['valid_to'] < date('Y-m-d')) {
                return ['valid' => false, 'message' => 'Coupon has expired.', 'coupon' => null];
            } elseif ($existingCoupon['usage_limit'] !== null && $existingCoupon['usage_count'] >= $existingCoupon['usage_limit']) {
                return ['valid' => false, 'message' => 'Coupon usage limit reached.', 'coupon' => null];
            } elseif ($existingCoupon['min_purchase_amount'] !== null && $subtotal < $existingCoupon['min_purchase_amount']) {
                return ['valid' => false, 'message' => 'Minimum spend requirement not met.', 'coupon' => null];
            } else {
                // Default invalid message if no specific reason above matches
                return ['valid' => false, 'message' => 'Coupon is invalid or cannot be applied.', 'coupon' => null];
            }

        } catch (Exception $e) {
            error_log("Coupon Code Validation DB Error: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Error validating coupon code.', 'coupon' => null];
        }
    }

    /**
     * Check if a specific user has already used a specific coupon.
     * Public access needed by CheckoutController.
     *
     * @param int $couponId
     * @param int $userId
     * @return bool True if used, False otherwise.
     */
    public function hasUserUsedCoupon(int $couponId, int $userId): bool { // Changed to public
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM coupon_usage
                WHERE coupon_id = ? AND user_id = ?
            ");
            $stmt->execute([$couponId, $userId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
             error_log("Error checking user coupon usage (Coupon: {$couponId}, User: {$userId}): " . $e->getMessage());
             return false; // Fail open - assume not used if DB error, let checkout attempt proceed
        }
    }


    /**
     * Handles AJAX request from checkout page to validate a coupon.
     * Includes user-specific checks.
     * Returns JSON response for the frontend.
     */
    public function applyCouponAjax() {
        $this->requireLogin(true); // Ensure user is logged in (AJAX)
        $this->validateCSRF(); // Validate CSRF from AJAX

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $code = $this->validateInput($data['code'] ?? null, 'string');
        $currentSubtotal = $this->validateInput($data['subtotal'] ?? null, 'float'); // Get subtotal from client
        $userId = $this->getUserId();

        if (!$code || $currentSubtotal === false || $currentSubtotal < 0) {
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid coupon code or subtotal amount provided.'], 400);
        }

        // Step 1: Core validation
        $validationResult = $this->validateCouponCodeOnly($code, $currentSubtotal);
        if (!$validationResult['valid']) {
             return $this->jsonResponse(['success' => false, 'message' => $validationResult['message']]);
        }
        $coupon = $validationResult['coupon'];

        // Step 2: User-specific validation
        if ($this->hasUserUsedCoupon($coupon['id'], $userId)) {
            return $this->jsonResponse(['success' => false, 'message' => 'You have already used this coupon.']);
        }

        // Step 3: Calculate discount and return success
        $discountAmount = $this->calculateDiscount($coupon, $currentSubtotal);
        $subtotalAfterDiscount = max(0, $currentSubtotal - $discountAmount);
        $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $newTotalEstimate = $subtotalAfterDiscount + $shipping_cost; // Excludes tax

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Coupon applied successfully!',
            'coupon_code' => $coupon['code'],
            'discount_amount' => number_format($discountAmount, 2),
            'new_total_estimate' => number_format($newTotalEstimate, 2) // Estimate for UI update
        ]);
    }


    /**
     * Records the usage of a coupon for a specific order and user.
     * Increments the coupon's usage count.
     * Assumes it's called within the main checkout transaction.
     *
     * @param int $couponId
     * @param int $orderId
     * @param int $userId
     * @param float $discountAmount
     * @return bool True on success, false on failure.
     */
    public function recordUsage(int $couponId, int $orderId, int $userId, float $discountAmount): bool {
         // --- Removed transaction management ---
        try {
             if ($couponId <= 0 || $orderId <= 0 || $userId <= 0 || $discountAmount < 0) {
                 throw new InvalidArgumentException('Invalid parameters for recording coupon usage.');
             }
             // Record usage in coupon_usage table
             $stmtUsage = $this->db->prepare("
                 INSERT INTO coupon_usage (coupon_id, order_id, user_id, discount_amount, used_at)
                 VALUES (?, ?, ?, ?, NOW())
             ");
             $usageInserted = $stmtUsage->execute([$couponId, $orderId, $userId, $discountAmount]);
             if (!$usageInserted) { throw new Exception("Failed to insert into coupon_usage table."); }

             // Update usage_count in coupons table
             $stmtUpdate = $this->db->prepare("
                 UPDATE coupons SET usage_count = usage_count + 1, updated_at = NOW() WHERE id = ?
             ");
             $countUpdated = $stmtUpdate->execute([$couponId]);
             if (!$countUpdated || $stmtUpdate->rowCount() === 0) {
                 error_log("Warning: Failed to increment usage_count for coupon ID {$couponId} on order ID {$orderId}, but usage was recorded.");
             }
            return true;
        } catch (Exception $e) {
            error_log("Coupon usage recording error for CouponID {$couponId}, OrderID {$orderId}: " . $e->getMessage());
            return false; // Indicate failure to the calling transaction
        }
    }


    /**
     * Calculates the discount amount based on coupon type and subtotal.
     * Public access needed by CheckoutController.
     *
     * @param array $coupon Coupon data array.
     * @param float $subtotal Order subtotal.
     * @return float Calculated discount amount.
     */
    public function calculateDiscount(array $coupon, float $subtotal): float { // Made public
        $discountAmount = 0;
        $discountValue = $coupon['discount_value'] ?? 0;
        $discountType = $coupon['discount_type'] ?? null;

        if ($discountType === 'percentage') {
            $discountAmount = $subtotal * ($discountValue / 100);
        } elseif ($discountType === 'fixed') {
            $discountAmount = $discountValue;
        } else {
             error_log("Unknown discount type '{$discountType}' for coupon ID {$coupon['id']}");
             return 0;
        }
        // Apply maximum discount limit if set and numeric
        // Corrected check for > 0
        if (isset($coupon['max_discount_amount']) && is_numeric($coupon['max_discount_amount']) && $coupon['max_discount_amount'] > 0) {
            $discountAmount = min($discountAmount, (float)$coupon['max_discount_amount']);
        }
         // Ensure discount doesn't exceed subtotal
         $discountAmount = min($discountAmount, $subtotal);
        return round(max(0, $discountAmount), 2); // Ensure non-negative and round
    }

    // --- Admin CRUD methods (Unchanged from original) ---
    public function listCoupons() {
         $this->requireAdmin();
         try {
             $stmt = $this->db->query("
                 SELECT c.*, COUNT(cu.id) as total_uses, COALESCE(SUM(cu.discount_amount), 0) as total_discount_given
                 FROM coupons c LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id
                 GROUP BY c.id ORDER BY c.created_at DESC
             ");
             $coupons = $stmt->fetchAll();
             $data = [
                 'pageTitle' => 'Manage Coupons', 'coupons' => $coupons,
                 'csrfToken' => $this->generateCSRFToken(), 'bodyClass' => 'page-admin-coupons'
             ];
             echo $this->renderView('admin/coupons', $data);
         } catch (Exception $e) {
             error_log("Error fetching coupons for admin: " . $e->getMessage());
             $this->setFlashMessage('Failed to load coupons.', 'error');
             $this->redirect('admin');
         }
     }
     public function showCreateForm() {
          $this->requireAdmin();
          $data = [
               'pageTitle' => 'Create Coupon', 'coupon' => null,
               'csrfToken' => $this->generateCSRFToken(), 'bodyClass' => 'page-admin-coupon-form'
          ];
          echo $this->renderView('admin/coupon_form', $data);
     }
      public function showEditForm(int $id) {
          $this->requireAdmin();
          $stmt = $this->db->prepare("SELECT * FROM coupons WHERE id = ?");
          $stmt->execute([$id]);
          $coupon = $stmt->fetch();
          if (!$coupon) {
               $this->setFlashMessage('Coupon not found.', 'error'); $this->redirect('admin&section=coupons'); return;
          }
          $data = [
               'pageTitle' => 'Edit Coupon', 'coupon' => $coupon,
               'csrfToken' => $this->generateCSRFToken(), 'bodyClass' => 'page-admin-coupon-form'
           ];
           echo $this->renderView('admin/coupon_form', $data);
      }
      public function saveCoupon() {
           $this->requireAdmin(); $this->validateCSRF();
           $couponId = $this->validateInput($_POST['coupon_id'] ?? null, 'int');
           $data = [
                'code' => $this->validateInput($_POST['code'] ?? null, 'string', ['min' => 3, 'max' => 50]),
                'description' => $this->validateInput($_POST['description'] ?? null, 'string', ['max' => 255]),
                'discount_type' => $this->validateInput($_POST['discount_type'] ?? null, 'string'),
                'discount_value' => $this->validateInput($_POST['discount_value'] ?? null, 'float'),
                'min_purchase_amount' => $this->validateInput($_POST['min_purchase_amount'] ?? 0, 'float', ['min' => 0]),
                'max_discount_amount' => $this->validateInput($_POST['max_discount_amount'] ?? null, 'float', ['min' => 0]),
                'valid_from' => $this->validateInput($_POST['valid_from'] ?? null, 'date'),
                'valid_to' => $this->validateInput($_POST['valid_to'] ?? null, 'date'),
                'usage_limit' => $this->validateInput($_POST['usage_limit'] ?? null, 'int', ['min' => 0]),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
           ];
           if (!$data['code'] || !$data['discount_type'] || $data['discount_value'] === false || $data['discount_value'] <= 0) {
                $this->setFlashMessage('Missing required fields (Code, Type, Value).', 'error'); $this->redirect('admin&section=coupons' . ($couponId ? '&task=edit&id='.$couponId : '&task=create')); return;
           }
            if (!in_array($data['discount_type'], ['percentage', 'fixed'])) {
                 $this->setFlashMessage('Invalid discount type.', 'error'); $this->redirect('admin&section=coupons' . ($couponId ? '&task=edit&id='.$couponId : '&task=create')); return;
            }
            if ($data['discount_type'] === 'percentage' && ($data['discount_value'] > 100)) {
                 $this->setFlashMessage('Percentage discount cannot exceed 100.', 'error'); $this->redirect('admin&section=coupons' . ($couponId ? '&task=edit&id='.$couponId : '&task=create')); return;
            }
           try {
                $this->beginTransaction();
                $checkStmt = $this->db->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
                $checkStmt->execute([$data['code'], $couponId ?: 0]);
                if ($checkStmt->fetch()) { throw new Exception('Coupon code already exists.'); }
                if ($couponId) {
                    $stmt = $this->db->prepare("UPDATE coupons SET code = ?, description = ?, discount_type = ?, discount_value = ?, min_purchase_amount = ?, max_discount_amount = ?, valid_from = ?, valid_to = ?, usage_limit = ?, is_active = ?, updated_at = NOW(), updated_by = ? WHERE id = ?");
                     $success = $stmt->execute([ $data['code'], $data['description'], $data['discount_type'], $data['discount_value'], $data['min_purchase_amount'], $data['max_discount_amount'] ?: null, $data['valid_from'] ?: null, $data['valid_to'] ?: null, $data['usage_limit'] ?: null, $data['is_active'], $this->getUserId(), $couponId ]);
                     $message = 'Coupon updated successfully.';
                } else {
                    $stmt = $this->db->prepare("INSERT INTO coupons ( code, description, discount_type, discount_value, min_purchase_amount, max_discount_amount, valid_from, valid_to, usage_limit, is_active, created_by, updated_by ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                      $userId = $this->getUserId();
                      $success = $stmt->execute([ $data['code'], $data['description'], $data['discount_type'], $data['discount_value'], $data['min_purchase_amount'], $data['max_discount_amount'] ?: null, $data['valid_from'] ?: null, $data['valid_to'] ?: null, $data['usage_limit'] ?: null, $data['is_active'], $userId, $userId ]);
                      $message = 'Coupon created successfully.';
                }
                if (!$success) { throw new Exception("Database operation failed."); }
                $this->commit(); $this->setFlashMessage($message, 'success');
           } catch (Exception $e) {
                $this->rollback(); error_log("Coupon save error: " . $e->getMessage()); $this->setFlashMessage('Failed to save coupon: ' . $e->getMessage(), 'error');
           }
           $this->redirect('admin&section=coupons');
      }
     public function toggleCouponStatus(int $id) {
           $this->requireAdmin(); $this->validateCSRF();
           try {
                $stmt = $this->db->prepare("UPDATE coupons SET is_active = !is_active, updated_at = NOW(), updated_by = ? WHERE id = ?");
                $success = $stmt->execute([$this->getUserId(), $id]);
                if ($success && $stmt->rowCount() > 0) { return $this->jsonResponse(['success' => true, 'message' => 'Coupon status toggled.']); }
                else { return $this->jsonResponse(['success' => false, 'message' => 'Coupon not found or status unchanged.'], 404); }
           } catch (Exception $e) {
                error_log("Coupon toggle error: " . $e->getMessage()); return $this->jsonResponse(['success' => false, 'message' => 'Failed to toggle coupon status.'], 500);
           }
     }
     public function deleteCoupon(int $id) {
           $this->requireAdmin(); $this->validateCSRF();
           try {
                $this->beginTransaction();
                $stmtUsage = $this->db->prepare("DELETE FROM coupon_usage WHERE coupon_id = ?"); $stmtUsage->execute([$id]);
                $stmt = $this->db->prepare("DELETE FROM coupons WHERE id = ?"); $success = $stmt->execute([$id]);
                if ($success && $stmt->rowCount() > 0) { $this->commit(); return $this->jsonResponse(['success' => true, 'message' => 'Coupon deleted successfully.']); }
                else { $this->rollback(); return $this->jsonResponse(['success' => false, 'message' => 'Coupon not found.'], 404); }
           } catch (Exception $e) {
                $this->rollback(); error_log("Coupon delete error: " . $e->getMessage()); return $this->jsonResponse(['success' => false, 'message' => 'Failed to delete coupon.'], 500);
           }
     }

} // End CouponController class
