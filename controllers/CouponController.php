<?php
require_once __DIR__ . '/BaseController.php';
// No need to require OrderModel etc. here if methods don't directly use them

class CouponController extends BaseController {
    // No direct need for $pdo property if inheriting from BaseController which has $this->db
    // private $pdo; // Remove this if using $this->db from BaseController

    public function __construct($pdo) {
        parent::__construct($pdo); // Pass PDO to BaseController constructor
        // $this->pdo = $pdo; // Remove this line, use $this->db instead
    }

    /**
     * Core validation logic for a coupon code.
     * Checks active status, dates, usage limits, minimum purchase.
     * Does NOT check user-specific usage here.
     *
     * @param string $code
     * @param float $subtotal
     * @return array ['valid' => bool, 'message' => string, 'coupon' => array|null]
     */
    public function validateCouponCodeOnly(string $code, float $subtotal): array {
        $code = $this->validateInput($code, 'string'); // Already validated? Double check is ok.
        $subtotal = $this->validateInput($subtotal, 'float');

        if (!$code || $subtotal === false || $subtotal < 0) {
            return ['valid' => false, 'message' => 'Invalid coupon code or subtotal amount.', 'coupon' => null];
        }

        try {
            // Use $this->db (from BaseController)
            $stmt = $this->db->prepare("
                SELECT * FROM coupons
                WHERE code = ?
                AND is_active = TRUE
                AND (valid_from IS NULL OR valid_from <= CURDATE()) -- Changed start_date/end_date to valid_from/valid_to based on sample schema if present, else adjust
                AND (valid_to IS NULL OR valid_to >= CURDATE())     -- Changed start_date/end_date to valid_from/valid_to
                AND (usage_limit IS NULL OR usage_count < usage_limit)
                AND (min_purchase_amount IS NULL OR min_purchase_amount <= ?) -- Check if min_purchase_amount is NULL too
            ");
            $stmt->execute([$code, $subtotal]);
            $coupon = $stmt->fetch();

            if (!$coupon) {
                 // More specific messages based on why it failed could be added here by checking coupon data if found but inactive/expired etc.
                return ['valid' => false, 'message' => 'Coupon is invalid, expired, or minimum spend not met.', 'coupon' => null];
            }

            // Coupon exists and meets basic criteria
            return ['valid' => true, 'message' => 'Coupon code is potentially valid.', 'coupon' => $coupon];

        } catch (Exception $e) {
            error_log("Coupon Code Validation DB Error: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Error validating coupon code.', 'coupon' => null];
        }
    }

    /**
     * Check if a specific user has already used a specific coupon.
     *
     * @param int $couponId
     * @param int $userId
     * @return bool True if used, False otherwise.
     */
    private function hasUserUsedCoupon(int $couponId, int $userId): bool {
        try {
            // Use $this->db
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM coupon_usage
                WHERE coupon_id = ? AND user_id = ?
            ");
            $stmt->execute([$couponId, $userId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
             error_log("Error checking user coupon usage: " . $e->getMessage());
             return true; // Fail safe - assume used if DB error occurs? Or false? Let's assume false to allow attempt.
        }
    }


    /**
     * Handles AJAX request from checkout page to validate a coupon.
     * Includes user-specific checks.
     * Returns JSON response for the frontend.
     */
    public function applyCouponAjax() {
        $this->requireLogin(); // Ensure user is logged in
        $this->validateCSRF(); // Validate CSRF from AJAX

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $code = $this->validateInput($data['code'] ?? null, 'string');
        $subtotal = $this->validateInput($data['subtotal'] ?? null, 'float');
        $userId = $this->getUserId();

        if (!$code || $subtotal === false || $subtotal < 0) {
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid coupon code or subtotal amount provided.'], 400);
        }

        // Step 1: Core validation
        $validationResult = $this->validateCouponCodeOnly($code, $subtotal);

        if (!$validationResult['valid']) {
             return $this->jsonResponse([
                 'success' => false,
                 'message' => $validationResult['message'] // Provide the specific validation message
             ]);
        }

        $coupon = $validationResult['coupon'];

        // Step 2: User-specific validation
        if ($this->hasUserUsedCoupon($coupon['id'], $userId)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'You have already used this coupon.'
            ]);
        }

        // Step 3: Calculate discount and return success
        $discountAmount = $this->calculateDiscount($coupon, $subtotal);

         // Recalculate totals needed for the response accurately
         $subtotalAfterDiscount = $subtotal - $discountAmount;
         $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
         // Tax requires shipping context - cannot reliably calculate here without client sending address.
         // Let's calculate final total based on discount + shipping, tax added client-side or later.
         $newTotal = $subtotalAfterDiscount + $shipping_cost; // Tax will be added later
         $newTotal = max(0, $newTotal); // Ensure non-negative

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Coupon applied successfully!',
            'coupon_code' => $coupon['code'], // Send back the code for display
            'discount_amount' => number_format($discountAmount, 2),
            // 'new_tax_amount' => number_format($tax_amount, 2), // Omit tax calculation here
            'new_total' => number_format($newTotal, 2) // Send the new total (excluding tax for now)
        ]);
    }


    /**
     * Records the usage of a coupon for a specific order and user.
     * Increments the coupon's usage count.
     * Should be called within a transaction if part of a larger process like checkout.
     *
     * @param int $couponId
     * @param int $orderId
     * @param int $userId
     * @param float $discountAmount
     * @return bool True on success, false on failure.
     */
    public function recordUsage(int $couponId, int $orderId, int $userId, float $discountAmount): bool {
         // This method assumes it might be called outside a pre-existing transaction,
         // so it starts its own. If called within CheckoutController's transaction,
         // PDO might handle nested transactions gracefully depending on driver,
         // but it's safer if CheckoutController manages the main transaction.
         // Let's remove the transaction here and assume CheckoutController handles it.
         // $this->beginTransaction(); // Removed

        try {
            // Validate input (basic checks)
             if ($couponId <= 0 || $orderId <= 0 || $userId <= 0 || $discountAmount < 0) {
                 throw new InvalidArgumentException('Invalid parameters for recording coupon usage.');
             }

             // Record usage in coupon_usage table
             $stmtUsage = $this->db->prepare("
                 INSERT INTO coupon_usage (coupon_id, order_id, user_id, discount_amount, used_at)
                 VALUES (?, ?, ?, ?, NOW())
             ");
             $usageInserted = $stmtUsage->execute([$couponId, $orderId, $userId, $discountAmount]);

             if (!$usageInserted) {
                 throw new Exception("Failed to insert into coupon_usage table.");
             }

             // Update usage_count in coupons table
             $stmtUpdate = $this->db->prepare("
                 UPDATE coupons
                 SET usage_count = usage_count + 1,
                     updated_at = NOW()
                 WHERE id = ?
             ");
             $countUpdated = $stmtUpdate->execute([$couponId]);

             if (!$countUpdated || $stmtUpdate->rowCount() === 0) {
                 // Don't throw an exception if the count update fails, but log it.
                 // The usage was recorded, which is the primary goal. Count mismatch can be fixed.
                 error_log("Warning: Failed to increment usage_count for coupon ID {$couponId} on order ID {$orderId}, but usage was recorded.");
             }

            // $this->commit(); // Removed - Rely on calling method's transaction
            return true;

        } catch (Exception $e) {
            // $this->rollback(); // Removed
            error_log("Coupon usage recording error for CouponID {$couponId}, OrderID {$orderId}: " . $e->getMessage());
            return false;
        }
    }


    // --- Admin Methods (kept largely original, ensure $this->db is used) ---

    /**
     * Calculates the discount amount based on coupon type and subtotal.
     *
     * @param array $coupon Coupon data array.
     * @param float $subtotal Order subtotal.
     * @return float Calculated discount amount.
     */
    public function calculateDiscount(array $coupon, float $subtotal): float { // Made public for CheckoutController
        $discountAmount = 0;

        if ($coupon['discount_type'] === 'percentage') {
            $discountAmount = $subtotal * ($coupon['discount_value'] / 100);
        } elseif ($coupon['discount_type'] === 'fixed') { // Explicitly check for 'fixed'
            $discountAmount = $coupon['discount_value'];
        } else {
             error_log("Unknown discount type '{$coupon['discount_type']}' for coupon ID {$coupon['id']}");
             return 0; // Return 0 for unknown types
        }

        // Apply maximum discount limit if set and numeric
        if (isset($coupon['max_discount_amount']) && is_numeric($coupon['max_discount_amount'])) {
            $discountAmount = min($discountAmount, (float)$coupon['max_discount_amount']);
        }

         // Ensure discount doesn't exceed subtotal (prevent negative totals from discount alone)
         $discountAmount = min($discountAmount, $subtotal);

        return round(max(0, $discountAmount), 2); // Ensure non-negative and round
    }

    // --- Admin CRUD methods ---
    // These methods are typically called from admin routes and might render views or return JSON.
    // Ensure they use $this->db, $this->requireAdmin(), $this->validateCSRF() appropriately.

    // Example: Method to display coupons list in Admin (Called by GET request in index.php)
     public function listCoupons() {
         $this->requireAdmin();
         try {
             // Fetch all coupons with usage stats
             $stmt = $this->db->query("
                 SELECT
                     c.*,
                     COUNT(cu.id) as total_uses,
                     COALESCE(SUM(cu.discount_amount), 0) as total_discount_given
                 FROM coupons c
                 LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id
                 GROUP BY c.id
                 ORDER BY c.created_at DESC
             ");
             $coupons = $stmt->fetchAll();

             // Prepare data for the view
             $data = [
                 'pageTitle' => 'Manage Coupons',
                 'coupons' => $coupons,
                 'csrfToken' => $this->generateCSRFToken(),
                 'bodyClass' => 'page-admin-coupons'
             ];
             // Render the admin view
             echo $this->renderView('admin/coupons', $data);

         } catch (Exception $e) {
             error_log("Error fetching coupons for admin: " . $e->getMessage());
             $this->setFlashMessage('Failed to load coupons.', 'error');
             // Redirect to admin dashboard or show error view
             $this->redirect('admin'); // Redirect to admin dashboard
         }
     }

     // Example: Show create form (Called by GET request in index.php)
     public function showCreateForm() {
          $this->requireAdmin();
          $data = [
               'pageTitle' => 'Create Coupon',
               'coupon' => null, // No existing coupon data
               'csrfToken' => $this->generateCSRFToken(),
               'bodyClass' => 'page-admin-coupon-form'
          ];
          echo $this->renderView('admin/coupon_form', $data); // Assume view exists
     }

     // Example: Show edit form (Called by GET request in index.php)
      public function showEditForm(int $id) {
          $this->requireAdmin();
          $stmt = $this->db->prepare("SELECT * FROM coupons WHERE id = ?");
          $stmt->execute([$id]);
          $coupon = $stmt->fetch();

          if (!$coupon) {
               $this->setFlashMessage('Coupon not found.', 'error');
               $this->redirect('admin&section=coupons');
               return;
          }

          $data = [
               'pageTitle' => 'Edit Coupon',
               'coupon' => $coupon,
               'csrfToken' => $this->generateCSRFToken(),
               'bodyClass' => 'page-admin-coupon-form'
           ];
           echo $this->renderView('admin/coupon_form', $data); // Assume view exists
      }

     // Example: Save coupon (Called by POST request in index.php)
      public function saveCoupon() {
           $this->requireAdmin();
           $this->validateCSRF(); // Validates POST CSRF

           $couponId = $this->validateInput($_POST['coupon_id'] ?? null, 'int');
           // Extract and validate all other POST data similar to createCoupon below
           $data = [
                'code' => $this->validateInput($_POST['code'] ?? null, 'string', ['min' => 3, 'max' => 50]), // Add length validation
                'description' => $this->validateInput($_POST['description'] ?? null, 'string', ['max' => 255]),
                'discount_type' => $this->validateInput($_POST['discount_type'] ?? null, 'string'),
                'discount_value' => $this->validateInput($_POST['discount_value'] ?? null, 'float'),
                'min_purchase_amount' => $this->validateInput($_POST['min_purchase_amount'] ?? 0, 'float', ['min' => 0]),
                'max_discount_amount' => $this->validateInput($_POST['max_discount_amount'] ?? null, 'float', ['min' => 0]),
                'valid_from' => $this->validateInput($_POST['valid_from'] ?? null, 'date'), // Basic date check
                'valid_to' => $this->validateInput($_POST['valid_to'] ?? null, 'date'),
                'usage_limit' => $this->validateInput($_POST['usage_limit'] ?? null, 'int', ['min' => 0]),
                'is_active' => isset($_POST['is_active']) ? 1 : 0 // Convert checkbox to 1 or 0
           ];

           // --- Basic Server-side Validation ---
           if (!$data['code'] || !$data['discount_type'] || $data['discount_value'] === false || $data['discount_value'] <= 0) {
                $this->setFlashMessage('Missing required fields (Code, Type, Value).', 'error');
                $this->redirect('admin&section=coupons' . ($couponId ? '&task=edit&id='.$couponId : '&task=create'));
                return;
           }
            if (!in_array($data['discount_type'], ['percentage', 'fixed'])) {
                 $this->setFlashMessage('Invalid discount type.', 'error');
                 $this->redirect('admin&section=coupons' . ($couponId ? '&task=edit&id='.$couponId : '&task=create'));
                 return;
            }
            if ($data['discount_type'] === 'percentage' && ($data['discount_value'] > 100)) {
                 $this->setFlashMessage('Percentage discount cannot exceed 100.', 'error');
                 $this->redirect('admin&section=coupons' . ($couponId ? '&task=edit&id='.$couponId : '&task=create'));
                 return;
            }
            // --- End Validation ---

           try {
                $this->beginTransaction();

                // Check for duplicate code if creating or changing code
                $checkStmt = $this->db->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
                $checkStmt->execute([$data['code'], $couponId ?: 0]);
                if ($checkStmt->fetch()) {
                    throw new Exception('Coupon code already exists.');
                }


                if ($couponId) {
                    // Update existing coupon
                    $stmt = $this->db->prepare("
                        UPDATE coupons SET
                        code = ?, description = ?, discount_type = ?, discount_value = ?,
                        min_purchase_amount = ?, max_discount_amount = ?, valid_from = ?, valid_to = ?,
                        usage_limit = ?, is_active = ?, updated_at = NOW(), updated_by = ?
                        WHERE id = ?
                    ");
                     $success = $stmt->execute([
                          $data['code'], $data['description'], $data['discount_type'], $data['discount_value'],
                          $data['min_purchase_amount'], $data['max_discount_amount'] ?: null, $data['valid_from'] ?: null, $data['valid_to'] ?: null,
                          $data['usage_limit'] ?: null, $data['is_active'], $this->getUserId(), $couponId
                     ]);
                     $message = 'Coupon updated successfully.';
                } else {
                    // Create new coupon
                    $stmt = $this->db->prepare("
                         INSERT INTO coupons (
                             code, description, discount_type, discount_value, min_purchase_amount,
                             max_discount_amount, valid_from, valid_to, usage_limit, is_active,
                             created_by, updated_by
                         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                     ");
                      $userId = $this->getUserId();
                      $success = $stmt->execute([
                           $data['code'], $data['description'], $data['discount_type'], $data['discount_value'], $data['min_purchase_amount'],
                           $data['max_discount_amount'] ?: null, $data['valid_from'] ?: null, $data['valid_to'] ?: null, $data['usage_limit'] ?: null, $data['is_active'],
                           $userId, $userId
                      ]);
                      $message = 'Coupon created successfully.';
                }

                if (!$success) {
                     throw new Exception("Database operation failed.");
                }

                $this->commit();
                $this->setFlashMessage($message, 'success');

           } catch (Exception $e) {
                $this->rollback();
                error_log("Coupon save error: " . $e->getMessage());
                $this->setFlashMessage('Failed to save coupon: ' . $e->getMessage(), 'error');
           }

           // Redirect back to coupon list
            $this->redirect('admin&section=coupons');
      }

     // Example: Toggle Status (Called by POST request in index.php)
     public function toggleCouponStatus(int $id) {
           $this->requireAdmin();
           $this->validateCSRF(); // CSRF for state-changing action

           try {
                $stmt = $this->db->prepare("UPDATE coupons SET is_active = !is_active, updated_at = NOW(), updated_by = ? WHERE id = ?");
                $success = $stmt->execute([$this->getUserId(), $id]);

                if ($success && $stmt->rowCount() > 0) {
                     return $this->jsonResponse(['success' => true, 'message' => 'Coupon status toggled.']);
                } else {
                     return $this->jsonResponse(['success' => false, 'message' => 'Coupon not found or status unchanged.'], 404);
                }
           } catch (Exception $e) {
                error_log("Coupon toggle error: " . $e->getMessage());
                return $this->jsonResponse(['success' => false, 'message' => 'Failed to toggle coupon status.'], 500);
           }
     }

     // Example: Delete Coupon (Called by POST request in index.php)
     public function deleteCoupon(int $id) {
           $this->requireAdmin();
           $this->validateCSRF(); // CSRF for state-changing action

           try {
                $this->beginTransaction();
                // Optionally delete usage records first or handle via foreign key constraint
                $stmtUsage = $this->db->prepare("DELETE FROM coupon_usage WHERE coupon_id = ?");
                $stmtUsage->execute([$id]);

                $stmt = $this->db->prepare("DELETE FROM coupons WHERE id = ?");
                $success = $stmt->execute([$id]);

                if ($success && $stmt->rowCount() > 0) {
                     $this->commit();
                     return $this->jsonResponse(['success' => true, 'message' => 'Coupon deleted successfully.']);
                } else {
                     $this->rollback();
                     return $this->jsonResponse(['success' => false, 'message' => 'Coupon not found.'], 404);
                }
           } catch (Exception $e) {
                $this->rollback();
                error_log("Coupon delete error: " . $e->getMessage());
                return $this->jsonResponse(['success' => false, 'message' => 'Failed to delete coupon.'], 500);
           }
     }

}
