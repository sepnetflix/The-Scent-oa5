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
                <h1 class="page-title" data-aos="fade-up">Order History</h1>
                
                <?php if (empty($orders)): ?>
                    <div class="empty-state" data-aos="fade-up">
                        <i class="fas fa-shopping-bag"></i>
                        <p>You haven't placed any orders yet</p>
                        <a href="index.php?page=products" class="btn-primary">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="orders-container">
                        <!-- Order Filter -->
                        <div class="order-filters" data-aos="fade-up">
                            <select id="orderStatus" class="form-select">
                                <option value="">All Orders</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            
                            <select id="orderTime" class="form-select">
                                <option value="">All Time</option>
                                <option value="30">Last 30 Days</option>
                                <option value="90">Last 3 Months</option>
                                <option value="365">Last Year</option>
                            </select>
                        </div>
                        
                        <!-- Orders List -->
                        <div class="orders-list" data-aos="fade-up">
                            <?php foreach ($orders as $order): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div class="order-meta">
                                            <h3>Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                                            <span class="order-date">
                                                <?= date('F j, Y', strtotime($order['created_at'])) ?>
                                            </span>
                                        </div>
                                        <span class="order-status <?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="order-items">
                                        <?php foreach (json_decode($order['items'], true) as $item): ?>
                                            <div class="order-item">
                                                <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                                     alt="<?= htmlspecialchars($item['name']) ?>">
                                                <div class="item-details">
                                                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                                                    <p class="item-meta">
                                                        Quantity: <?= $item['quantity'] ?> |
                                                        Price: $<?= number_format($item['price'], 2) ?>
                                                    </p>
                                                </div>
                                                <div class="item-total">
                                                    $<?= number_format($item['quantity'] * $item['price'], 2) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="order-footer">
                                        <div class="order-summary">
                                            <div class="summary-row">
                                                <span>Subtotal:</span>
                                                <span>$<?= number_format($order['subtotal'], 2) ?></span>
                                            </div>
                                            <div class="summary-row">
                                                <span>Shipping:</span>
                                                <span>$<?= number_format($order['shipping_cost'], 2) ?></span>
                                            </div>
                                            <?php if ($order['discount_amount'] > 0): ?>
                                                <div class="summary-row discount">
                                                    <span>Discount:</span>
                                                    <span>-$<?= number_format($order['discount_amount'], 2) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="summary-row total">
                                                <span>Total:</span>
                                                <span>$<?= number_format($order['total_amount'], 2) ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="order-actions">
                                            <a href="index.php?page=account&section=orders&id=<?= $order['id'] ?>" 
                                               class="btn-secondary">View Details</a>
                                            <?php if ($order['status'] === 'shipped'): ?>
                                                <a href="<?= htmlspecialchars($order['tracking_url']) ?>" 
                                                   class="btn-primary" target="_blank">
                                                    Track Package
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination" data-aos="fade-up">
                                <?php if ($page > 1): ?>
                                    <a href="?page=account&section=orders&p=<?= $page - 1 ?>" 
                                       class="pagination-link">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <a href="?page=account&section=orders&p=<?= $i ?>" 
                                       class="pagination-link <?= ($i === $page) ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=account&section=orders&p=<?= $page + 1 ?>" 
                                       class="pagination-link">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Order filtering
    const orderStatus = document.getElementById('orderStatus');
    const orderTime = document.getElementById('orderTime');
    
    function filterOrders() {
        const url = new URL(window.location.href);
        
        if (orderStatus.value) {
            url.searchParams.set('status', orderStatus.value);
        } else {
            url.searchParams.delete('status');
        }
        
        if (orderTime.value) {
            url.searchParams.set('time', orderTime.value);
        } else {
            url.searchParams.delete('time');
        }
        
        window.location.href = url.toString();
    }
    
    orderStatus.addEventListener('change', filterOrders);
    orderTime.addEventListener('change', filterOrders);
    
    // Set initial filter values from URL
    const params = new URLSearchParams(window.location.search);
    if (params.has('status')) {
        orderStatus.value = params.get('status');
    }
    if (params.has('time')) {
        orderTime.value = params.get('time');
    }
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>