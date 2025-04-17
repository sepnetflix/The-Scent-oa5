<?php
require_once __DIR__ . '/BaseController.php';

class TaxController extends BaseController {
    private $cache = [];
    
    public function calculateTax($subtotal, $country, $state = null) {
        try {
            $subtotal = $this->validateInput($subtotal, 'float');
            $country = $this->validateInput($country, 'string');
            $state = $this->validateInput($state, 'string');
            
            if (!$subtotal || !$country) {
                throw new Exception('Invalid tax calculation parameters');
            }
            
            // Check cache first
            $cacheKey = "{$country}_{$state}";
            if (isset($this->cache[$cacheKey])) {
                return round($subtotal * $this->cache[$cacheKey], 2);
            }
            
            // Get tax rate from database
            $stmt = $this->pdo->prepare("
                SELECT rate 
                FROM tax_rates 
                WHERE country_code = ? 
                AND (state_code = ? OR state_code IS NULL)
                AND is_active = TRUE
                AND start_date <= NOW()
                AND (end_date IS NULL OR end_date > NOW())
                ORDER BY state_code IS NULL
                LIMIT 1
            ");
            $stmt->execute([$country, $state]);
            $result = $stmt->fetch();
            
            $rate = $result ? $result['rate'] : 0;
            $this->cache[$cacheKey] = $rate;
            
            return round($subtotal * $rate, 2);
            
        } catch (Exception $e) {
            error_log("Tax calculation error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTaxRate($country, $state = null) {
        try {
            $country = $this->validateInput($country, 'string');
            $state = $this->validateInput($state, 'string');
            
            if (!$country) return 0;
            
            // Check cache first
            $cacheKey = "{$country}_{$state}";
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT rate 
                FROM tax_rates 
                WHERE country_code = ? 
                AND (state_code = ? OR state_code IS NULL)
                AND is_active = TRUE
                AND start_date <= NOW()
                AND (end_date IS NULL OR end_date > NOW())
                ORDER BY state_code IS NULL
                LIMIT 1
            ");
            $stmt->execute([$country, $state]);
            $result = $stmt->fetch();
            
            $rate = $result ? $result['rate'] : 0;
            $this->cache[$cacheKey] = $rate;
            
            return $rate;
            
        } catch (Exception $e) {
            error_log("Tax rate lookup error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function formatTaxRate($rate) {
        return number_format($rate * 100, 2) . '%';
    }
    
    public function getAllTaxRates() {
        try {
            $this->requireAdmin();
            
            $stmt = $this->pdo->query("
                SELECT 
                    tr.*,
                    COUNT(th.id) as change_count,
                    MAX(th.created_at) as last_modified
                FROM tax_rates tr
                LEFT JOIN tax_rate_history th ON tr.id = th.tax_rate_id
                GROUP BY tr.id
                ORDER BY tr.country_code, tr.state_code
            ");
            
            return $this->jsonResponse([
                'success' => true,
                'rates' => $stmt->fetchAll()
            ]);
            
        } catch (Exception $e) {
            error_log("Error fetching tax rates: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve tax rates'
            ], 500);
        }
    }
    
    public function updateTaxRate() {
        try {
            $this->requireAdmin();
            $this->validateCSRF();
            
            $data = [
                'country_code' => $this->validateInput($_POST['country_code'], 'string'),
                'state_code' => $this->validateInput($_POST['state_code'] ?? null, 'string'),
                'rate' => $this->validateInput($_POST['rate'], 'float'),
                'start_date' => $this->validateInput($_POST['start_date'] ?? date('Y-m-d'), 'string'),
                'end_date' => $this->validateInput($_POST['end_date'] ?? null, 'string'),
                'is_active' => isset($_POST['is_active']) ? true : false
            ];
            
            if (!$data['country_code'] || $data['rate'] < 0) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Invalid tax rate data'
                ], 400);
            }
            
            $this->beginTransaction();
            
            // Get existing rate if any
            $stmt = $this->pdo->prepare("
                SELECT id, rate 
                FROM tax_rates 
                WHERE country_code = ? 
                AND (state_code = ? OR (state_code IS NULL AND ? IS NULL))
            ");
            $stmt->execute([
                $data['country_code'],
                $data['state_code'],
                $data['state_code']
            ]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing rate
                $stmt = $this->pdo->prepare("
                    UPDATE tax_rates 
                    SET rate = ?,
                        start_date = ?,
                        end_date = ?,
                        is_active = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['rate'],
                    $data['start_date'],
                    $data['end_date'],
                    $data['is_active'],
                    $existing['id']
                ]);
                
                // Log the change
                if ($existing['rate'] != $data['rate']) {
                    $this->logRateChange(
                        $existing['id'],
                        $existing['rate'],
                        $data['rate']
                    );
                }
            } else {
                // Insert new rate
                $stmt = $this->pdo->prepare("
                    INSERT INTO tax_rates (
                        country_code,
                        state_code,
                        rate,
                        start_date,
                        end_date,
                        is_active,
                        created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['country_code'],
                    $data['state_code'],
                    $data['rate'],
                    $data['start_date'],
                    $data['end_date'],
                    $data['is_active'],
                    $this->getUserId()
                ]);
                
                $rateId = $this->pdo->lastInsertId();
                $this->logRateChange($rateId, 0, $data['rate']);
            }
            
            // Clear cache for this region
            $cacheKey = "{$data['country_code']}_{$data['state_code']}";
            unset($this->cache[$cacheKey]);
            
            $this->commit();
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Tax rate updated successfully'
            ]);
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Tax rate update error: " . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to update tax rate'
            ], 500);
        }
    }
    
    private function logRateChange($rateId, $oldRate, $newRate) {
        $stmt = $this->pdo->prepare("
            INSERT INTO tax_rate_history (
                tax_rate_id,
                old_rate,
                new_rate,
                changed_by
            ) VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $rateId,
            $oldRate,
            $newRate,
            $this->getUserId()
        ]);
    }
    
    public function getTaxRateHistory($rateId) {
        try {
            $this->requireAdmin();
            
            $rateId = $this->validateInput($rateId, 'int');
            if (!$rateId) {
                throw new Exception('Invalid tax rate ID');
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    th.*,
                    u.name as changed_by_name
                FROM tax_rate_history th
                LEFT JOIN users u ON th.changed_by = u.id
                WHERE th.tax_rate_id = ?
                ORDER BY th.created_at DESC
            ");
            $stmt->execute([$rateId]);
            
            return $this->jsonResponse([
                'success' => true,
                'history' => $stmt->fetchAll()
            ]);
            
        } catch (Exception $e) {
            error_log("Error fetching tax rate history: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve tax rate history'
            ], 500);
        }
    }
}