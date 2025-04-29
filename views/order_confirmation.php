<?php require_once __DIR__ . '/layout/header.php'; ?>

<section class="confirmation-section">
    <div class="container">
        <div class="confirmation-container" data-aos="fade-up">
            <div class="confirmation-header">
                <i class="fas fa-check-circle"></i>
                <h1>Order Confirmed!</h1>
                <p>Thank you for your purchase. Your order (#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?>) has been successfully placed.</p>
            </div>

            <div class="order-details">
                <div class="order-info">
                    <h2>Order Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="label">Order Number:</span>
                            <span class="value">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Order Date:</span>
                            <span class="value"><?= date('F j, Y', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Order Status:</span>
                            <!-- Display actual status from DB -->
                            <span class="value status-<?= htmlspecialchars($order['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($order['status']))) ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="label">Payment Status:</span>
                             <span class="value status-<?= htmlspecialchars($order['payment_status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($order['payment_status']))) ?>
                            </span>
                        </div>
                        <!-- Add estimated delivery if available -->
                        <?php if (!empty($order['estimated_delivery'])): ?>
                        <div class="info-item">
                            <span class="label">Estimated Delivery:</span>
                            <span class="value"><?= date('F j, Y', strtotime($order['estimated_delivery'])) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="shipping-info">
                    <h2>Shipping Address</h2>
                    <p><?= htmlspecialchars($order['shipping_name'] ?? 'N/A') ?></p>
                    <p><?= htmlspecialchars($order['shipping_address'] ?? 'N/A') ?></p>
                    <p>
                        <?= htmlspecialchars($order['shipping_city'] ?? 'N/A') ?>,
                        <?= htmlspecialchars($order['shipping_state'] ?? 'N/A') ?>
                        <?= htmlspecialchars($order['shipping_zip'] ?? 'N/A') ?>
                    </p>
                    <p><?= htmlspecialchars($order['shipping_country'] ?? 'N/A') ?></p>
                </div>
            </div>

            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="summary-items">
                    <?php // Ensure order items are correctly fetched and passed ?>
                    <?php if (!empty($order['items']) && is_array($order['items'])): ?>
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="summary-item">
                                <div class="item-info">
                                    <span class="item-quantity"><?= $item['quantity'] ?>&times;</span>
                                    <span class="item-name"><?= htmlspecialchars($item['product_name'] ?? 'N/A') ?></span>
                                    <!-- Optional: add image -->
                                    <!-- <img src="<?= htmlspecialchars($item['image_url'] ?? '/images/placeholder.jpg') ?>" alt="" class="w-8 h-8 ml-2 rounded"> -->
                                </div>
                                <span class="item-price">$<?= number_format(($item['price_at_purchase'] ?? 0) * ($item['quantity'] ?? 0), 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                         <p class="text-gray-500 italic">Could not load order items.</p>
                    <?php endif; ?>
                </div>

                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?= number_format($order['subtotal'] ?? 0, 2) ?></span>
                    </div>
                    <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                        <div class="summary-row discount">
                            <span>Discount <?= !empty($order['coupon_code']) ? '(' . htmlspecialchars($order['coupon_code']) . ')' : '' ?>:</span>
                            <span>-$<?= number_format($order['discount_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span><?= ($order['shipping_cost'] ?? 0) > 0 ? '$' . number_format($order['shipping_cost'], 2) : '<span class="text-green-600">FREE</span>' ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax:</span>
                        <span>$<?= number_format($order['tax_amount'] ?? 0, 2) ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Paid:</span>
                        <span>$<?= number_format($order['total_amount'] ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>

            <div class="next-steps" data-aos="fade-up">
                <h2>What's Next?</h2>
                <div class="steps-grid">
                    <div class="step">
                        <i class="fas fa-envelope"></i>
                        <h3>Order Confirmation Email</h3>
                        <p>We've sent a confirmation email to <?= htmlspecialchars($order['shipping_email'] ?? 'your email') ?>.</p>
                    </div>
                    <div class="step">
                        <i class="fas fa-truck"></i>
                        <h3>Shipping Updates</h3>
                        <p>You'll receive shipping updates and tracking information once your order ships.</p>
                    </div>
                    <div class="step">
                        <i class="fas fa-user"></i>
                        <h3>Track Your Order</h3>
                        <p>Visit your account dashboard to track your order and view order history.</p>
                    </div>
                </div>

                <div class="confirmation-actions">
                    <a href="index.php?page=account&section=orders" class="btn-secondary">View My Orders</a>
                    <a href="index.php?page=products" class="btn-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
