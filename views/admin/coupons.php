<?php 
$section = 'coupons';
require_once __DIR__ . '/../layout/admin_header.php'; 
?>

<section class="admin-section">
    <div class="container">
        <div class="admin-container" data-aos="fade-up">
            <div class="admin-header">
                <h1>Manage Coupons</h1>
                <button type="button" class="btn-primary" onclick="showCreateCouponForm()">
                    <i class="fas fa-plus"></i> Create New Coupon
                </button>
            </div>

            <!-- Create/Edit Coupon Form (Hidden by default) -->
            <div id="couponForm" class="admin-form hidden">
                <h2>Create New Coupon</h2>
                <form action="index.php?page=admin&action=coupons" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="code">Coupon Code *</label>
                            <input type="text" id="code" name="code" required pattern="[A-Za-z0-9_-]+" 
                                   title="Only letters, numbers, hyphens and underscores allowed">
                        </div>
                        <div class="form-group">
                            <label for="discount_type">Discount Type *</label>
                            <select id="discount_type" name="discount_type" required>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="discount_value">Discount Value *</label>
                            <input type="number" id="discount_value" name="discount_value" required 
                                   step="0.01" min="0" max="100">
                            <span id="valueHint">(Enter percentage between 0-100)</span>
                        </div>
                        <div class="form-group">
                            <label for="min_purchase_amount">Minimum Purchase Amount</label>
                            <input type="number" id="min_purchase_amount" name="min_purchase_amount" 
                                   step="0.01" min="0">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="datetime-local" id="start_date" name="start_date">
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="datetime-local" id="end_date" name="end_date">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="usage_limit">Usage Limit</label>
                            <input type="number" id="usage_limit" name="usage_limit" min="1">
                        </div>
                        <div class="form-group">
                            <label for="max_discount_amount">Maximum Discount Amount</label>
                            <input type="number" id="max_discount_amount" name="max_discount_amount" 
                                   step="0.01" min="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Save Coupon</button>
                        <button type="button" class="btn-secondary" onclick="hideCouponForm()">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Coupons List -->
            <div class="coupons-list">
                <?php if (empty($coupons)): ?>
                    <div class="empty-state">
                        <i class="fas fa-ticket-alt"></i>
                        <p>No coupons created yet</p>
                    </div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Usage</th>
                                <th>Status</th>
                                <th>Valid Until</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coupons as $coupon): ?>
                                <tr>
                                    <td><?= htmlspecialchars($coupon['code']) ?></td>
                                    <td>
                                        <?= $coupon['discount_type'] === 'percentage' ? 
                                            $coupon['discount_value'] . '%' : 
                                            '$' . number_format($coupon['discount_value'], 2) ?>
                                    </td>
                                    <td>
                                        <?= $coupon['usage_count'] ?><?= $coupon['usage_limit'] ? 
                                            '/' . $coupon['usage_limit'] : '' ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $coupon['is_active'] ? 'active' : 'inactive' ?>">
                                            <?= $coupon['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $coupon['end_date'] ? 
                                            date('M j, Y', strtotime($coupon['end_date'])) : 
                                            'No expiry' ?>
                                    </td>
                                    <td class="actions">
                                        <button type="button" class="btn-icon" 
                                                onclick="editCoupon(<?= htmlspecialchars(json_encode($coupon)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn-icon" 
                                                onclick="toggleCouponStatus(<?= $coupon['id'] ?>)">
                                            <i class="fas fa-<?= $coupon['is_active'] ? 'ban' : 'check' ?>"></i>
                                        </button>
                                        <button type="button" class="btn-icon delete" 
                                                onclick="deleteCoupon(<?= $coupon['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
function showCreateCouponForm() {
    document.getElementById('couponForm').classList.remove('hidden');
}

function hideCouponForm() {
    document.getElementById('couponForm').classList.add('hidden');
    document.querySelector('#couponForm form').reset();
}

function editCoupon(coupon) {
    const form = document.querySelector('#couponForm form');
    form.reset();
    
    // Fill form with coupon data
    Object.keys(coupon).forEach(key => {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
            if (input.type === 'datetime-local' && coupon[key]) {
                input.value = new Date(coupon[key]).toISOString().slice(0, 16);
            } else {
                input.value = coupon[key];
            }
        }
    });
    
    document.getElementById('couponForm').classList.remove('hidden');
}

function toggleCouponStatus(couponId) {
    if (confirm('Are you sure you want to toggle this coupon\'s status?')) {
        fetch(`index.php?page=admin&action=coupons&id=${couponId}&toggle=status`, {
            method: 'POST'
        }).then(() => location.reload());
    }
}

function deleteCoupon(couponId) {
    if (confirm('Are you sure you want to delete this coupon?')) {
        fetch(`index.php?page=admin&action=coupons&id=${couponId}&delete=1`, {
            method: 'POST'
        }).then(() => location.reload());
    }
}

// Update value hint based on discount type
document.getElementById('discount_type').addEventListener('change', function() {
    const hint = document.getElementById('valueHint');
    hint.textContent = this.value === 'percentage' ? 
        '(Enter percentage between 0-100)' : 
        '(Enter amount in dollars)';
});
</script>

<style>
.admin-section {
    padding: 2rem 0;
}

.admin-container {
    max-width: 1200px;
    margin: 0 auto;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.admin-form {
    background: #fff;
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 0.5rem;
    overflow: hidden;
}

.admin-table th,
.admin-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.admin-table th {
    background: #f9fafb;
    font-weight: 600;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.875rem;
}

.status-badge.active {
    background: #dcfce7;
    color: #166534;
}

.status-badge.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.actions {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    padding: 0.5rem;
    border: none;
    background: none;
    cursor: pointer;
    color: #6b7280;
    transition: color 0.2s;
}

.btn-icon:hover {
    color: #4b5563;
}

.btn-icon.delete:hover {
    color: #dc2626;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    background: #fff;
    border-radius: 0.5rem;
    color: #6b7280;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
}
</style>

<?php require_once __DIR__ . '/../layout/admin_footer.php'; ?>