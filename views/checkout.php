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
                    <!-- NOTE: The form tag itself doesn't need action/method as JS handles the submission -->
                    <form id="checkoutForm">
                        <!-- ADD Standard CSRF Token for initial server-side check during processCheckout -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <!-- Hidden field to potentially store applied coupon code -->
                        <input type="hidden" id="applied_coupon_code" name="applied_coupon_code" value="">

                        <div class="form-group">
                            <label for="shipping_name">Full Name *</label>
                            <input type="text" id="shipping_name" name="shipping_name" required class="form-input"
                                   value="<?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="shipping_email">Email Address *</label>
                            <input type="email" id="shipping_email" name="shipping_email" required class="form-input"
                                   value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="shipping_address">Street Address *</label>
                            <input type="text" id="shipping_address" name="shipping_address" required class="form-input"
                                   value="<?= htmlspecialchars($userAddress['address_line1'] ?? '') ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_city">City *</label>
                                <input type="text" id="shipping_city" name="shipping_city" required class="form-input"
                                       value="<?= htmlspecialchars($userAddress['city'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="shipping_state">State/Province *</label>
                                <input type="text" id="shipping_state" name="shipping_state" required class="form-input"
                                       value="<?= htmlspecialchars($userAddress['state'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_zip">ZIP/Postal Code *</label>
                                <input type="text" id="shipping_zip" name="shipping_zip" required class="form-input"
                                       value="<?= htmlspecialchars($userAddress['postal_code'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="shipping_country">Country *</label>
                                <select id="shipping_country" name="shipping_country" required class="form-select">
                                    <option value="">Select Country</option>
                                    <option value="US" <?= (($userAddress['country'] ?? '') === 'US') ? 'selected' : '' ?>>United States</option>
                                    <option value="CA" <?= (($userAddress['country'] ?? '') === 'CA') ? 'selected' : '' ?>>Canada</option>
                                    <option value="GB" <?= (($userAddress['country'] ?? '') === 'GB') ? 'selected' : '' ?>>United Kingdom</option>
                                    <!-- Add more countries as needed -->
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="order_notes">Order Notes (Optional)</label>
                            <textarea id="order_notes" name="order_notes" rows="3" class="form-textarea"></textarea>
                        </div>
                        <!-- The submit button is now outside the form, controlled by JS -->
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
                                <input type="text" id="coupon_code" name="coupon_code_input" class="form-input"
                                       placeholder="Enter coupon code">
                                <button type="button" id="apply-coupon" class="btn-secondary">Apply</button>
                            </div>
                            <div id="coupon-message" class="hidden mt-2 text-sm"></div>
                        </div>
                    </div>

                    <div class="summary-items border-b border-gray-200 pb-4 mb-4">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="summary-item flex justify-between items-center text-sm py-1">
                                <div class="item-info flex items-center">
                                     <img src="<?= htmlspecialchars($item['product']['image'] ?? '/images/placeholder.jpg') ?>" alt="<?= htmlspecialchars($item['product']['name']) ?>" class="w-10 h-10 object-cover rounded mr-2">
                                     <div>
                                         <span class="item-name font-medium text-gray-800"><?= htmlspecialchars($item['product']['name']) ?></span>
                                         <span class="text-xs text-gray-500 block">Qty: <?= $item['quantity'] ?></span>
                                     </div>
                                </div>
                                <span class="item-price font-medium text-gray-700">$<?= number_format($item['subtotal'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-totals space-y-2">
                        <div class="summary-row flex justify-between items-center">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-medium text-gray-900">$<span id="summary-subtotal"><?= number_format($subtotal, 2) ?></span></span>
                        </div>
                         <div class="summary-row discount hidden flex justify-between items-center text-green-600">
                            <span>Discount (<span id="applied-coupon-code-display" class="font-mono text-xs bg-green-100 px-1 rounded"></span>):</span>
                            <span>-$<span id="discount-amount">0.00</span></span>
                        </div>
                        <div class="summary-row flex justify-between items-center">
                            <span class="text-gray-600">Shipping:</span>
                            <span class="font-medium text-gray-900" id="summary-shipping"><?= $shipping_cost > 0 ? '$' . number_format($shipping_cost, 2) : '<span class="text-green-600">FREE</span>' ?></span>
                        </div>
                        <div class="summary-row flex justify-between items-center">
                            <span class="text-gray-600">Tax (<span id="tax-rate" class="text-xs"><?= htmlspecialchars($tax_rate_formatted) ?></span>):</span>
                            <span class="font-medium text-gray-900" id="tax-amount">$<?= number_format($tax_amount, 2) ?></span>
                        </div>
                        <div class="summary-row total flex justify-between items-center border-t pt-3 mt-2">
                            <span class="text-lg font-bold text-gray-900">Total:</span>
                            <span class="text-lg font-bold text-primary">$<span id="summary-total"><?= number_format($total, 2) ?></span></span>
                        </div>
                    </div>

                    <div class="payment-section mt-6">
                        <h3 class="text-lg font-semibold mb-4">Payment Method</h3>
                        <!-- Stripe Payment Element -->
                        <div id="payment-element" class="mb-4 p-3 border rounded bg-gray-50"></div>
                        <!-- Used to display form errors -->
                        <div id="payment-message" class="hidden text-red-600 text-sm text-center mb-4"></div>
                    </div>

                    <!-- Button is outside the form, triggered by JS -->
                    <button type="button" id="submit-button" class="btn btn-primary w-full place-order">
                        <span id="button-text">Place Order & Pay</span>
                        <div class="spinner hidden" id="spinner"></div>
                    </button>

                    <div class="secure-checkout mt-4 text-center text-xs text-gray-500">
                        <i class="fas fa-lock mr-1"></i>Secure Checkout via Stripe
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Configuration ---
    const stripePublicKey = '<?= defined('STRIPE_PUBLIC_KEY') ? STRIPE_PUBLIC_KEY : '' ?>';
    const checkoutForm = document.getElementById('checkoutForm');
    const submitButton = document.getElementById('submit-button');
    const spinner = document.getElementById('spinner');
    const buttonText = document.getElementById('button-text');
    const paymentElementContainer = document.getElementById('payment-element');
    const paymentMessage = document.getElementById('payment-message');
    const csrfToken = document.getElementById('csrf-token-value').value;
    const couponCodeInput = document.getElementById('coupon_code');
    const applyCouponButton = document.getElementById('apply-coupon');
    const couponMessageEl = document.getElementById('coupon-message');
    const discountRow = document.querySelector('.summary-row.discount');
    const discountAmountEl = document.getElementById('discount-amount');
    const appliedCouponCodeDisplay = document.getElementById('applied-coupon-code-display');
    const appliedCouponHiddenInput = document.getElementById('applied_coupon_code'); // For sending with checkout
    const taxRateEl = document.getElementById('tax-rate');
    const taxAmountEl = document.getElementById('tax-amount');
    const shippingCountryEl = document.getElementById('shipping_country');
    const shippingStateEl = document.getElementById('shipping_state');
    const summarySubtotalEl = document.getElementById('summary-subtotal');
    const summaryShippingEl = document.getElementById('summary-shipping');
    const summaryTotalEl = document.getElementById('summary-total');

    let elements;
    let stripe;
    let currentSubtotal = <?= $subtotal ?? 0 ?>;
    let currentShippingCost = <?= $shipping_cost ?? 0 ?>;
    let currentTaxAmount = <?= $tax_amount ?? 0 ?>;
    let currentDiscountAmount = 0;

    if (!stripePublicKey) {
        showMessage("Stripe configuration error. Payment cannot proceed.");
        setLoading(false, true); // Disable button permanently
        return;
    }
    stripe = Stripe(stripePublicKey);

    // --- Initialize Stripe Elements ---
    const appearance = {
         theme: 'stripe',
         variables: {
             colorPrimary: '#1A4D5A', // Match theme
             colorBackground: '#ffffff',
             colorText: '#374151', // Tailwind gray-700
             colorDanger: '#dc2626', // Tailwind red-600
             fontFamily: 'Montserrat, sans-serif', // Match theme
             borderRadius: '0.375rem' // Tailwind rounded-md
         }
     };
    elements = stripe.elements({ appearance });
    const paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');

    // --- Helper Functions ---
    function setLoading(isLoading, disablePermanently = false) {
        if (isLoading) {
            submitButton.disabled = true;
            spinner.classList.remove('hidden');
            buttonText.classList.add('hidden');
        } else {
            submitButton.disabled = disablePermanently;
            spinner.classList.add('hidden');
            buttonText.classList.remove('hidden');
        }
    }

    function showMessage(message, isError = true) {
        paymentMessage.textContent = message;
        paymentMessage.className = `payment-message text-center text-sm my-4 ${isError ? 'text-red-600' : 'text-green-600'}`;
        paymentMessage.classList.remove('hidden');
        // Auto-hide?
        // setTimeout(() => { paymentMessage.classList.add('hidden'); }, 6000);
    }

    function showCouponMessage(message, type) { // type = 'success', 'error', 'info'
        couponMessageEl.textContent = message;
        couponMessageEl.className = `coupon-message mt-2 text-sm ${
            type === 'success' ? 'text-green-600' : (type === 'error' ? 'text-red-600' : 'text-gray-600')
        }`;
        couponMessageEl.classList.remove('hidden');
    }

    function updateOrderSummaryUI() {
        // Update subtotal (shouldn't change unless cart changes, which redirects)
        summarySubtotalEl.textContent = parseFloat(currentSubtotal).toFixed(2);

        // Update discount display
        if (currentDiscountAmount > 0 && appliedCouponHiddenInput.value) {
            discountAmountEl.textContent = parseFloat(currentDiscountAmount).toFixed(2);
            appliedCouponCodeDisplay.textContent = appliedCouponHiddenInput.value;
            discountRow.classList.remove('hidden');
        } else {
            discountAmountEl.textContent = '0.00';
            appliedCouponCodeDisplay.textContent = '';
            discountRow.classList.add('hidden');
        }

         // Update shipping cost display (based on subtotal AFTER discount)
         const subtotalAfterDiscount = currentSubtotal - currentDiscountAmount;
         currentShippingCost = subtotalAfterDiscount >= <?= FREE_SHIPPING_THRESHOLD ?> ? 0 : <?= SHIPPING_COST ?>;
         summaryShippingEl.innerHTML = currentShippingCost > 0 ? '$' + parseFloat(currentShippingCost).toFixed(2) : '<span class="text-green-600">FREE</span>';


        // Update tax amount display (based on AJAX call result)
        taxAmountEl.textContent = '$' + parseFloat(currentTaxAmount).toFixed(2);

        // Update total
        const grandTotal = (currentSubtotal - currentDiscountAmount) + currentShippingCost + currentTaxAmount;
        summaryTotalEl.textContent = parseFloat(Math.max(0, grandTotal)).toFixed(2); // Prevent negative total display
    }

    // --- Tax Calculation ---
    async function updateTax() {
        const country = shippingCountryEl.value;
        const state = shippingStateEl.value;

        if (!country) {
            // Reset tax if no country selected
             taxRateEl.textContent = '0%';
             currentTaxAmount = 0;
             updateOrderSummaryUI(); // Update total
            return;
        }

        try {
            // Add a subtle loading indicator? Maybe on the tax amount?
            taxAmountEl.textContent = '...';

            const response = await fetch('index.php?page=checkout&action=calculateTax', { // Correct action name from routing
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                 },
                body: JSON.stringify({ country, state })
            });

            if (!response.ok) throw new Error('Tax calculation failed');

            const data = await response.json();
            if (data.success) {
                taxRateEl.textContent = data.tax_rate_formatted || 'N/A';
                currentTaxAmount = parseFloat(data.tax_amount) || 0;
                // Don't update total directly here, let updateOrderSummaryUI handle it
            } else {
                 console.warn("Tax calculation error:", data.error);
                 taxRateEl.textContent = 'Error';
                 currentTaxAmount = 0;
            }
        } catch (e) {
            console.error('Error fetching tax:', e);
            taxRateEl.textContent = 'Error';
            currentTaxAmount = 0;
        } finally {
             updateOrderSummaryUI(); // Update totals after tax calculation attempt
        }
    }

    shippingCountryEl.addEventListener('change', updateTax);
    shippingStateEl.addEventListener('input', updateTax); // Use input for faster response if typing state

    // --- Coupon Application ---
    applyCouponButton.addEventListener('click', async function() {
        const couponCode = couponCodeInput.value.trim();
        if (!couponCode) {
            showCouponMessage('Please enter a coupon code.', 'error');
            return;
        }

        showCouponMessage('Applying...', 'info');
        applyCouponButton.disabled = true;

        try {
            const response = await fetch('index.php?page=checkout&action=applyCouponAjax', { // Use the new controller action
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    code: couponCode,
                    subtotal: currentSubtotal, // Send current subtotal for validation
                    csrf_token: csrfToken
                })
            });

             if (!response.ok) throw new Error(`Server error: ${response.status} ${response.statusText}`);

            const data = await response.json();

            if (data.success) {
                showCouponMessage(data.message || 'Coupon applied!', 'success');
                currentDiscountAmount = parseFloat(data.discount_amount) || 0;
                appliedCouponHiddenInput.value = data.coupon_code || couponCode; // Store applied code
                // Update tax and total based on server response if available
                // currentTaxAmount = parseFloat(data.new_tax_amount ?? currentTaxAmount); // Update tax if server recalculated it
                // Update totals based on new discount and potentially new tax
                 updateTax(); // Re-calculate tax and update summary after applying coupon discount
            } else {
                showCouponMessage(data.message || 'Invalid coupon code.', 'error');
                currentDiscountAmount = 0; // Reset discount
                appliedCouponHiddenInput.value = ''; // Clear applied code
                updateOrderSummaryUI(); // Update summary without discount
            }
        } catch (e) {
            console.error('Coupon Apply Error:', e);
            showCouponMessage('Failed to apply coupon. Please try again.', 'error');
            currentDiscountAmount = 0;
            appliedCouponHiddenInput.value = '';
            updateOrderSummaryUI();
        } finally {
            applyCouponButton.disabled = false;
        }
    });

    // --- Checkout Form Submission ---
    submitButton.addEventListener('click', async function(e) {
        // Use click on the button instead of form submit, as the form tag is mainly for structure now
        setLoading(true);
        showMessage(''); // Clear previous messages

        // 1. Client-side validation
        let isValid = true;
        const requiredFields = ['shipping_name', 'shipping_email', 'shipping_address', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];
        requiredFields.forEach(id => {
            const input = document.getElementById(id);
            if (!input || !input.value.trim()) {
                isValid = false;
                input?.classList.add('input-error'); // Add error class for styling
                 // Find label and add error state? More complex UI work.
            } else {
                input?.classList.remove('input-error');
            }
        });

        if (!isValid) {
            showMessage('Please fill in all required shipping fields.');
            setLoading(false);
             // Scroll to first error?
             const firstError = checkoutForm.querySelector('.input-error');
             firstError?.focus();
             firstError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // 2. Send checkout data to server to create order and get clientSecret
        let clientSecret = null;
        let orderId = null;
        try {
            const checkoutFormData = new FormData(checkoutForm); // Includes CSRF, applied coupon, shipping fields

            const response = await fetch('index.php?page=checkout&action=processCheckout', { // Use a unique action name
                method: 'POST',
                headers: {
                    // Content-Type is set automatically for FormData
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: checkoutFormData
            });

            const data = await response.json();

            if (response.ok && data.success && data.clientSecret && data.orderId) {
                clientSecret = data.clientSecret;
                orderId = data.orderId;
            } else {
                throw new Error(data.error || 'Failed to process order on server. Please check details or try again.');
            }

        } catch (serverError) {
            console.error('Server processing error:', serverError);
            showMessage(serverError.message);
            setLoading(false);
            return; // Stop checkout
        }

        // 3. Confirm payment with Stripe using the obtained clientSecret
        if (clientSecret) {
            const { error: stripeError } = await stripe.confirmPayment({
                elements,
                clientSecret: clientSecret,
                confirmParams: {
                     // IMPORTANT: Use the correct BASE_URL constant here
                    return_url: `${window.location.origin}<?= rtrim(BASE_URL, '/') ?>/index.php?page=checkout&action=confirmation`,
                     // Optional: Send billing details again, though Stripe might capture from element
                     payment_method_data: {
                         billing_details: {
                             name: document.getElementById('shipping_name').value,
                             email: document.getElementById('shipping_email').value,
                             address: {
                                 line1: document.getElementById('shipping_address').value,
                                 city: document.getElementById('shipping_city').value,
                                 state: document.getElementById('shipping_state').value,
                                 postal_code: document.getElementById('shipping_zip').value,
                                 country: document.getElementById('shipping_country').value,
                             }
                         }
                     }
                },
                // Redirect 'if_required' handles 3DS etc. Stripe redirects on success.
                redirect: 'if_required'
            });

            // If we reach here, confirmPayment failed or requires manual action
            if (stripeError) {
                 console.error("Stripe Error:", stripeError);
                 showMessage(stripeError.message || "Payment failed. Please check your card details or try another method.");
                 setLoading(false); // Re-enable button on failure
            }
            // No explicit success redirect needed here as Stripe handles it via return_url
        } else {
            // Should have been caught earlier, but safety check
            showMessage('Failed to get payment details from server.');
            setLoading(false);
        }
    });

    // --- Initial UI Update ---
    updateOrderSummaryUI(); // Calculate initial totals
    // Optionally trigger initial tax calculation if country is pre-filled
    if (shippingCountryEl.value) {
        updateTax();
    }

});
</script>

<style>
/* Basic styles for loading/error states */
.spinner {
    width: 1.25em; /* 20px */
    height: 1.25em; /* 20px */
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #ffffff;
    animation: spin 1s linear infinite;
    display: inline-block; /* Changed from margin auto */
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.hidden {
    display: none;
}

.place-order:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.input-error {
    border-color: #dc2626; /* Tailwind red-600 */
    box-shadow: 0 0 0 1px #dc2626;
}
.form-input, .form-select, .form-textarea {
     /* Add some base styling for form elements if not globally defined */
     width: 100%;
     padding: 0.65rem 0.75rem;
     border: 1px solid #d1d5db; /* Tailwind gray-300 */
     border-radius: 0.375rem; /* Tailwind rounded-md */
     transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.form-input:focus, .form-select:focus, .form-textarea:focus {
     outline: none;
     border-color: #1A4D5A; /* primary */
     box-shadow: 0 0 0 2px rgba(26, 77, 90, 0.3);
}

</style>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
