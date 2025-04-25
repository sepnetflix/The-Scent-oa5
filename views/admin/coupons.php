<?php 
$section = 'coupons';
require_once __DIR__ . '/../layout/admin_header.php'; 
?>
<body class="page-admin-coupons">
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

<?php require_once __DIR__ . '/../layout/admin_footer.php'; ?>