<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-cart">
<!-- Output CSRF token for JS (for AJAX cart actions) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

<section class="cart-section">
    <div class="container">
        <div class="cart-container" data-aos="fade-up">
            <h1>Your Shopping Cart</h1>
            
            <?php if (empty($cartItems)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty</p>
                    <a href="index.php?page=products" class="btn-primary">Continue Shopping</a>
                </div>
            <?php else: ?>
                <form id="cartForm" action="index.php?page=cart&action=update" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="cart-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item" data-product-id="<?= $item['product']['id'] ?>">
                                <div class="item-image">
                                    <img src="<?= htmlspecialchars($item['product']['image_url']) ?>" 
                                         alt="<?= htmlspecialchars($item['product']['name']) ?>">
                                </div>
                                <div class="item-details">
                                    <h3><?= htmlspecialchars($item['product']['name']) ?></h3>
                                    <p class="item-price">$<?= number_format($item['product']['price'], 2) ?></p>
                                </div>
                                <div class="item-quantity">
                                    <button type="button" class="quantity-btn minus">-</button>
                                    <input type="number" name="updates[<?= $item['product']['id'] ?>]" 
                                           value="<?= $item['quantity'] ?>" min="1" max="99">
                                    <button type="button" class="quantity-btn plus">+</button>
                                </div>
                                <div class="item-subtotal">
                                    $<?= number_format($item['subtotal'], 2) ?>
                                </div>
                                <button type="button" class="remove-item" 
                                        data-product-id="<?= $item['product']['id'] ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>
                        <div class="summary-row shipping">
                            <span>Shipping:</span>
                            <span>FREE</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>
                    </div>
                    
                    <div class="cart-actions">
                        <button type="submit" class="btn-secondary update-cart">Update Cart</button>
                        <a href="index.php?page=checkout" class="btn-primary checkout">Proceed to Checkout</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>