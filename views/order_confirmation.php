<?php require_once 'layout/header.php'; ?>

<section class="confirmation-section">
    <div class="container">
        <div class="confirmation-container" data-aos="fade-up">
            <div class="confirmation-header">
                <i class="fas fa-check-circle"></i>
                <h1>Order Confirmed!</h1>
                <p>Thank you for your purchase. Your order has been successfully placed.</p>
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
                            <span class="value status-pending">Processing</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Estimated Delivery:</span>
                            <span class="value"><?= date('F j, Y', strtotime('+5 days')) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="shipping-info">
                    <h2>Shipping Address</h2>
                    <p><?= htmlspecialchars($order['shipping_name']) ?></p>
                    <p><?= htmlspecialchars($order['shipping_address']) ?></p>
                    <p><?= htmlspecialchars($order['shipping_city']) . ', ' . 
                         htmlspecialchars($order['shipping_state']) . ' ' . 
                         htmlspecialchars($order['shipping_zip']) ?></p>
                    <p><?= htmlspecialchars($order['shipping_country']) ?></p>
                </div>
            </div>
            
            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="summary-items">
                    <?php foreach ($order['items'] as $item): ?>
                        <div class="summary-item">
                            <div class="item-info">
                                <span class="item-quantity"><?= $item['quantity'] ?>×</span>
                                <span class="item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                            </div>
                            <span class="item-price">$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?= number_format($order['subtotal'], 2) ?></span>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div class="summary-row discount">
                            <span>Discount (<?= htmlspecialchars($order['coupon_code']) ?>):</span>
                            <span>-$<?= number_format($order['discount_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span><?= $order['shipping_cost'] > 0 ? '$' . number_format($order['shipping_cost'], 2) : 'FREE' ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax:</span>
                        <span>$<?= number_format($order['tax_amount'], 2) ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>$<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="next-steps" data-aos="fade-up">
                <h2>What's Next?</h2>
                <div class="steps-grid">
                    <div class="step">
                        <i class="fas fa-envelope"></i>
                        <h3>Order Confirmation Email</h3>
                        <p>We've sent a confirmation email to <?= htmlspecialchars($order['shipping_email']) ?></p>
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
                    <a href="index.php?page=account" class="btn-secondary">View Order Status</a>
                    <a href="index.php?page=products" class="btn-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'layout/footer.php'; ?>