<?php require_once __DIR__ . '/layout/header.php'; ?>
<!-- Output CSRF token for JS (for AJAX checkout/coupon/tax) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

<!-- Add Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>

<section class="checkout-section">
    <div class="container">
        <div class="checkout-container" data-aos="fade-up">
            <h1>Checkout</h1>
            
            <div class="checkout-grid">
                <!-- Shipping Form -->
                <div class="shipping-details">
                    <h2>Shipping Details</h2>
                    <form id="checkoutForm" action="index.php?page=checkout" method="POST">
                        <div class="form-group">
                            <label for="shipping_name">Full Name *</label>
                            <input type="text" id="shipping_name" name="shipping_name" required
                                   value="<?= htmlspecialchars(getCurrentUser()['name'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_email">Email Address *</label>
                            <input type="email" id="shipping_email" name="shipping_email" required
                                   value="<?= htmlspecialchars(getCurrentUser()['email'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_address">Street Address *</label>
                            <input type="text" id="shipping_address" name="shipping_address" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_city">City *</label>
                                <input type="text" id="shipping_city" name="shipping_city" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="shipping_state">State/Province *</label>
                                <input type="text" id="shipping_state" name="shipping_state" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_zip">ZIP/Postal Code *</label>
                                <input type="text" id="shipping_zip" name="shipping_zip" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="shipping_country">Country *</label>
                                <select id="shipping_country" name="shipping_country" required>
                                    <option value="">Select Country</option>
                                    <option value="US">United States</option>
                                    <option value="CA">Canada</option>
                                    <option value="GB">United Kingdom</option>
                                    <!-- Add more countries as needed -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="order_notes">Order Notes (Optional)</label>
                            <textarea id="order_notes" name="order_notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                
                <!-- Order Summary -->
                <div class="order-summary">
                    <h2>Order Summary</h2>
                    
                    <!-- Coupon Code Section -->
                    <div class="coupon-section">
                        <div class="form-group">
                            <label for="coupon_code">Have a coupon?</label>
                            <div class="coupon-input">
                                <input type="text" id="coupon_code" name="coupon_code" 
                                       placeholder="Enter coupon code">
                                <button type="button" id="apply-coupon" class="btn-secondary">Apply</button>
                            </div>
                            <div id="coupon-message" class="hidden"></div>
                        </div>
                    </div>
                    
                    <div class="summary-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="summary-item">
                                <div class="item-info">
                                    <span class="item-quantity"><?= $item['quantity'] ?>×</span>
                                    <span class="item-name"><?= htmlspecialchars($item['product']['name']) ?></span>
                                </div>
                                <span class="item-price">$<?= number_format($item['subtotal'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-totals">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>$<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span><?= $shipping_cost > 0 ? '$' . number_format($shipping_cost, 2) : 'FREE' ?></span>
                        </div>
                        <div class="summary-row discount hidden">
                            <span>Discount:</span>
                            <span>-$<span id="discount-amount">0.00</span></span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (<span id="tax-rate"><?= $tax_rate_formatted ?></span>):</span>
                            <span id="tax-amount">$<?= number_format($tax_amount, 2) ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>
                    </div>
                    
                    <div class="payment-section">
                        <h3>Payment Method</h3>
                        <div id="payment-element"></div>
                        <div id="payment-message" class="hidden"></div>
                    </div>
                    
                    <button type="submit" id="submit-button" class="btn-primary place-order">
                        <span id="button-text">Pay Now</span>
                        <div class="spinner hidden" id="spinner"></div>
                    </button>
                    
                    <div class="secure-checkout">
                        <i class="fas fa-lock"></i>
                        Secure Checkout via Stripe
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Initialize Stripe
const stripe = Stripe('<?= STRIPE_PUBLIC_KEY ?>');
let elements;
let paymentElement;

// Add real-time tax calculation when country/state changes
document.getElementById('shipping_country').addEventListener('change', updateTax);
document.getElementById('shipping_state').addEventListener('change', updateTax);

async function updateTax() {
    const country = document.getElementById('shipping_country').value;
    const state = document.getElementById('shipping_state').value;
    
    if (!country) return;
    
    try {
        const response = await fetch('index.php?page=checkout&action=calculate-tax', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ country, state })
        });
        
        const data = await response.json();
        if (data.success) {
            document.getElementById('tax-rate').textContent = data.tax_rate_formatted;
            document.getElementById('tax-amount').textContent = '$' + data.tax_amount;
            document.querySelector('.summary-row.total span:last-child').textContent = 
                '$' + data.total;
        }
    } catch (e) {
        console.error('Error updating tax:', e);
    }
}

async function initialize() {
    try {
        const response = await fetch('index.php?page=payment&action=create-intent', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                amount: <?= $total ?>,
                currency: 'usd'
            })
        });
        
        const { clientSecret } = await response.json();
        
        const options = {
            clientSecret,
            appearance: {
                theme: 'stripe',
                // Customize to match your site's design
                variables: {
                    colorPrimary: '#6366f1',
                    colorBackground: '#ffffff',
                    colorText: '#1f2937',
                    colorDanger: '#ef4444',
                    fontFamily: 'system-ui, -apple-system, sans-serif',
                    borderRadius: '0.5rem'
                }
            }
        };
        
        elements = stripe.elements(options);
        paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');
    } catch (e) {
        console.error('Error:', e);
        document.getElementById('payment-message').textContent = 'Failed to initialize payment form.';
    }
}

document.getElementById('checkoutForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    setLoading(true);
    
    // Validate shipping fields
    const requiredFields = [
        'shipping_name',
        'shipping_email',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country'
    ];
    
    let valid = true;
    requiredFields.forEach(field => {
        const input = document.getElementById(field);
        if (!input.value.trim()) {
            valid = false;
            input.classList.add('error');
        } else {
            input.classList.remove('error');
        }
    });
    
    if (!valid) {
        setLoading(false);
        alert('Please fill in all required fields.');
        return;
    }
    
    const { error } = await stripe.confirmPayment({
        elements,
        confirmParams: {
            return_url: window.location.origin + '/index.php?page=order_confirmation'
        }
    });
    
    if (error) {
        const messageContainer = document.getElementById('payment-message');
        messageContainer.textContent = error.message;
        messageContainer.classList.add('error');
        
        // Re-enable form
        setLoading(false);
        
        // Scroll to error message
        messageContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Handle specific error types
        switch (error.type) {
            case 'card_error':
            case 'validation_error':
                // These errors should show in the payment form UI
                elements.getElement('card').focus();
                break;
            
            case 'invalid_request_error':
                // Reload the page if the PaymentIntent was invalid
                window.location.reload();
                break;
            
            default:
                // For all other errors, allow retry
                submitButton.disabled = false;
                break;
        }
    } else {
        // Payment successful, redirect to confirmation page handled by return_url
    }
});

document.getElementById('apply-coupon').addEventListener('click', async function() {
    const couponCode = document.getElementById('coupon_code').value.trim();
    if (!couponCode) {
        showCouponMessage('Please enter a coupon code', 'error');
        return;
    }
    
    try {
        const response = await fetch('index.php?page=checkout&action=apply-coupon', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: couponCode })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showCouponMessage(data.message, 'success');
            updateOrderSummary(data);
        } else {
            showCouponMessage(data.message, 'error');
            removeCouponDiscount();
        }
    } catch (e) {
        console.error('Error:', e);
        showCouponMessage('Failed to apply coupon', 'error');
    }
});

function showCouponMessage(message, type) {
    const messageEl = document.getElementById('coupon-message');
    messageEl.textContent = message;
    messageEl.className = `coupon-message ${type}`;
    messageEl.classList.remove('hidden');
}

function updateOrderSummary(data) {
    const discountRow = document.querySelector('.summary-row.discount');
    const discountAmount = document.getElementById('discount-amount');
    const total = document.querySelector('.summary-row.total span:last-child');
    
    if (data.discount_amount > 0) {
        discountRow.classList.remove('hidden');
        discountAmount.textContent = data.discount_amount;
    } else {
        discountRow.classList.add('hidden');
    }
    
    total.textContent = '$' + data.total;
}

function removeCouponDiscount() {
    document.querySelector('.summary-row.discount').classList.add('hidden');
    document.getElementById('discount-amount').textContent = '0.00';
    // Recalculate total without discount
    updateTax();
}

function setLoading(isLoading) {
    const submitButton = document.getElementById('submit-button');
    const spinner = document.getElementById('spinner');
    const buttonText = document.getElementById('button-text');
    
    if (isLoading) {
        submitButton.disabled = true;
        spinner.classList.remove('hidden');
        buttonText.classList.add('hidden');
    } else {
        submitButton.disabled = false;
        spinner.classList.add('hidden');
        buttonText.classList.remove('hidden');
    }
}

initialize();</script>

<style>
.spinner {
    width: 20px;
    height: 20px;
    border: 3px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.hidden {
    display: none;
}

#payment-message {
    color: #ef4444;
    margin-top: 0.5rem;
    text-align: center;
}

#payment-element {
    margin-bottom: 1.5rem;
}

.coupon-section {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background-color: #f9fafb;
    border-radius: 0.5rem;
}

.coupon-input {
    display: flex;
    gap: 0.5rem;
}

.coupon-message {
    margin-top: 0.5rem;
    font-size: 0.875rem;
}

.coupon-message.success {
    color: #059669;
}

.coupon-message.error {
    color: #dc2626;
}

.summary-row.discount {
    color: #059669;
}
</style>

<?php require_once __DIR__ . '/layout/footer.php'; ?>