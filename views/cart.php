<?php require_once 'layout/header.php'; ?>

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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cartForm = document.getElementById('cartForm');
    
    // Handle quantity buttons
    document.querySelectorAll('.quantity-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            let value = parseInt(input.value);
            
            if (this.classList.contains('plus')) {
                if (value < 99) input.value = value + 1;
            } else {
                if (value > 1) input.value = value - 1;
            }
            
            // Trigger form change
            input.dispatchEvent(new Event('change'));
        });
    });
    
    // Handle quantity input changes
    document.querySelectorAll('.item-quantity input').forEach(input => {
        input.addEventListener('change', function() {
            updateCartItem(this.closest('.cart-item'));
        });
    });
    
    // Handle remove item buttons
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            
            fetch('index.php?page=cart&action=remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.closest('.cart-item').remove();
                    updateCartTotal();
                    updateCartCount(data.cartCount);
                    
                    // If cart is empty, refresh page
                    if (data.cartCount === 0) {
                        location.reload();
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
    
    // Update cart total
    function updateCartTotal() {
        let total = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            const price = parseFloat(item.querySelector('.item-price').textContent.replace('$', ''));
            const quantity = parseInt(item.querySelector('.item-quantity input').value);
            total += price * quantity;
            
            // Update item subtotal
            item.querySelector('.item-subtotal').textContent = 
                '$' + (price * quantity).toFixed(2);
        });
        
        // Update summary totals
        document.querySelector('.summary-row.total span:last-child').textContent = 
            '$' + total.toFixed(2);
    }
    
    // Update cart count in header
    function updateCartCount(count) {
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            if (count > 0) {
                cartCount.textContent = count;
                cartCount.style.display = 'inline';
            } else {
                cartCount.style.display = 'none';
            }
        }
    }
    
    // Handle form submission
    cartForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        fetch('index.php?page=cart&action=update', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartTotal();
                updateCartCount(data.cartCount);
            }
        })
        .catch(error => console.error('Error:', error));
    });
});
</script>

<?php require_once 'layout/footer.php'; ?>