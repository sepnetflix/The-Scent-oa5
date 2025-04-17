<?php require_once __DIR__ . '/../layout/header.php'; ?>

<section class="account-section">
    <div class="container">
        <div class="account-grid">
            <!-- Sidebar Navigation -->
            <aside class="account-sidebar" data-aos="fade-right">
                <div class="account-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <h3><?= htmlspecialchars($user['name']) ?></h3>
                        <p><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    
                    <nav>
                        <ul>
                            <li>
                                <a href="index.php?page=account">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=orders" class="active">
                                    <i class="fas fa-shopping-bag"></i> My Orders
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=profile">
                                    <i class="fas fa-user"></i> Profile Settings
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=quiz">
                                    <i class="fas fa-clipboard-list"></i> Quiz History
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=logout">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </aside>
            
            <!-- Main Content -->
            <div class="account-content">
                <div class="order-details-header" data-aos="fade-up">
                    <div class="header-left">
                        <a href="index.php?page=account&section=orders" class="back-link">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                        <h1>Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h1>
                    </div>
                    <div class="header-right">
                        <span class="order-date">
                            <?= date('F j, Y', strtotime($order['created_at'])) ?>
                        </span>
                        <span class="order-status <?= $order['status'] ?>">
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </div>
                </div>
                
                <!-- Order Progress -->
                <?php if ($order['status'] !== 'cancelled'): ?>
                    <div class="order-progress" data-aos="fade-up">
                        <?php
                        $statuses = ['processing', 'confirmed', 'shipped', 'delivered'];
                        $currentIndex = array_search($order['status'], $statuses);
                        foreach ($statuses as $index => $status):
                            $isActive = $index <= $currentIndex;
                            $isCompleted = $index < $currentIndex;
                        ?>
                            <div class="progress-step <?= $isActive ? 'active' : '' ?>">
                                <div class="step-icon">
                                    <?php if ($isCompleted): ?>
                                        <i class="fas fa-check"></i>
                                    <?php else: ?>
                                        <i class="fas fa-<?= $status === 'processing' ? 'clock' : 
                                                          ($status === 'confirmed' ? 'check' :
                                                          ($status === 'shipped' ? 'truck' : 'box')) ?>"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="step-label">
                                    <?= ucfirst($status) ?>
                                    <?php if ($status === $order['status']): ?>
                                        <span class="step-date">
                                            <?= date('M j', strtotime($order[$status . '_date'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($index < count($statuses) - 1): ?>
                                <div class="progress-line <?= $isActive ? 'active' : '' ?>"></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="order-details-grid">
                    <!-- Order Items -->
                    <div class="order-items-card" data-aos="fade-up">
                        <h2>Order Items</h2>
                        <div class="items-list">
                            <?php foreach (json_decode($order['items'], true) as $item): ?>
                                <div class="order-item">
                                    <div class="item-image">
                                        <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                             alt="<?= htmlspecialchars($item['name']) ?>">
                                    </div>
                                    <div class="item-details">
                                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                                        <p class="item-meta">
                                            Quantity: <?= $item['quantity'] ?> |
                                            Price: $<?= number_format($item['price'], 2) ?>
                                        </p>
                                        <?php if (!empty($item['options'])): ?>
                                            <p class="item-options">
                                                Options: <?= htmlspecialchars(implode(', ', $item['options'])) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-actions">
                                        <span class="item-total">
                                            $<?= number_format($item['quantity'] * $item['price'], 2) ?>
                                        </span>
                                        <form action="index.php?page=cart&action=add" method="POST">
                                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="quantity" value="<?= $item['quantity'] ?>">
                                            <button type="submit" class="btn-secondary">Buy Again</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="order-summary-card" data-aos="fade-up">
                        <h2>Order Summary</h2>
                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>$<?= number_format($order['subtotal'], 2) ?></span>
                            </div>
                            <?php if ($order['discount_amount'] > 0): ?>
                                <div class="summary-row discount">
                                    <span>
                                        Discount 
                                        <?php if ($order['coupon_code']): ?>
                                            <div class="coupon-tag">
                                                <i class="fas fa-tag"></i>
                                                <?= htmlspecialchars($order['coupon_code']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </span>
                                    <span>-$<?= number_format($order['discount_amount'], 2) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="summary-row">
                                <span>Shipping</span>
                                <span><?= $order['shipping_cost'] > 0 ? '$' . number_format($order['shipping_cost'], 2) : 'FREE' ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Tax</span>
                                <span>$<?= number_format($order['tax_amount'], 2) ?></span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span>$<?= number_format($order['total_amount'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping Information -->
                    <div class="shipping-info-card" data-aos="fade-up">
                        <h2>Shipping Information</h2>
                        <div class="shipping-details">
                            <div class="address-section">
                                <h3>Delivery Address</h3>
                                <address>
                                    <?= htmlspecialchars($order['shipping_name']) ?><br>
                                    <?= nl2br(htmlspecialchars($order['shipping_address'])) ?><br>
                                    <?= htmlspecialchars($order['shipping_city']) ?>, 
                                    <?= htmlspecialchars($order['shipping_state']) ?> 
                                    <?= htmlspecialchars($order['shipping_zip']) ?><br>
                                    <?= htmlspecialchars($order['shipping_country']) ?>
                                </address>
                            </div>
                            
                            <?php if ($order['status'] === 'shipped'): ?>
                                <div class="tracking-section">
                                    <h3>Tracking Information</h3>
                                    <p class="tracking-number">
                                        <i class="fas fa-truck"></i>
                                        Tracking Number: <?= htmlspecialchars($order['tracking_number']) ?>
                                    </p>
                                    <a href="<?= htmlspecialchars($order['tracking_url']) ?>" 
                                       class="btn-primary" target="_blank">
                                        Track Package
                                    </a>
                                    <p class="estimated-delivery">
                                        Estimated Delivery: <?= date('F j, Y', strtotime($order['estimated_delivery'])) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Additional Actions -->
                    <div class="order-actions-card" data-aos="fade-up">
                        <h2>Need Help?</h2>
                        <div class="action-buttons">
                            <a href="index.php?page=support&order=<?= $order['id'] ?>" class="btn-secondary">
                                <i class="fas fa-question-circle"></i>
                                Contact Support
                            </a>
                            <?php if ($order['status'] === 'processing'): ?>
                                <a href="index.php?page=account&section=orders&id=<?= $order['id'] ?>&action=cancel" 
                                   class="btn-danger" 
                                   onclick="return confirm('Are you sure you want to cancel this order?')">
                                    <i class="fas fa-times"></i>
                                    Cancel Order
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.coupon-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background-color: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    color: #374151;
    margin-left: 0.5rem;
}

.coupon-tag i {
    color: #059669;
}

.summary-row.discount {
    color: #059669;
}
</style>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>