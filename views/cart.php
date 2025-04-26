<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-cart">
<!-- Output CSRF token for JS (for AJAX cart actions) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

<section class="cart-section">
    <div class="container">
        <div class="cart-container" data-aos="fade-up">
            <h1>Your Shopping Cart</h1>

            <?php if (empty($cartItems)): ?>
                <div class="empty-cart text-center py-16">
                    <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                    <p class="text-xl text-gray-700 mb-6">Your cart is currently empty.</p>
                    <a href="index.php?page=products" class="btn btn-primary">Continue Shopping</a>
                </div>
            <?php else: ?>
                <form id="cartForm" action="index.php?page=cart&action=update" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

                    <!-- Cart Items Column -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="cart-items bg-white shadow rounded-lg overflow-hidden">
                             <div class="hidden md:flex px-6 py-3 bg-gray-50 border-b border-gray-200 text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                 <div class="w-2/5">Product</div>
                                 <div class="w-1/5 text-center">Price</div>
                                 <div class="w-1/5 text-center">Quantity</div>
                                 <div class="w-1/5 text-right">Subtotal</div>
                                 <div class="w-10"></div> <!-- Spacer for remove button -->
                             </div>
                            <?php foreach ($cartItems as $item): ?>
                                <div class="cart-item flex flex-wrap md:flex-nowrap items-center px-4 py-4 md:px-6 md:py-4 border-b border-gray-200 last:border-b-0" data-product-id="<?= $item['product']['id'] ?>">
                                    <!-- Product Details (Image & Name) -->
                                    <div class="w-full md:w-2/5 flex items-center mb-4 md:mb-0">
                                        <div class="item-image w-16 h-16 md:w-20 md:h-20 mr-4 flex-shrink-0">
                                            <?php
                                                // Uses the correct 'image' key. Default placeholder if null.
                                                $image_path = $item['product']['image'] ?? '/images/placeholder.jpg';
                                            ?>
                                            <img src="<?= htmlspecialchars($image_path) ?>"
                                                 alt="<?= htmlspecialchars($item['product']['name'] ?? 'Product Image') ?>"
                                                 class="w-full h-full object-cover rounded border">
                                        </div>
                                        <div class="item-details flex-grow">
                                            <h3 class="font-semibold text-primary hover:text-accent text-sm md:text-base">
                                                <a href="index.php?page=product&id=<?= $item['product']['id'] ?>">
                                                    <?= htmlspecialchars($item['product']['name']) ?>
                                                </a>
                                            </h3>
                                            <!-- Optional: Display category or short desc -->
                                            <?php if (!empty($item['product']['category_name'])): ?>
                                                <p class="text-xs text-gray-500 hidden md:block"><?= htmlspecialchars($item['product']['category_name']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Price (Mobile Hidden, shown inline on desktop) -->
                                    <div class="item-price w-1/3 md:w-1/5 text-center md:text-base text-gray-700">
                                        <span class="md:hidden text-xs text-gray-500 mr-1">Price:</span>
                                        $<?= number_format($item['product']['price'], 2) ?>
                                    </div>

                                    <!-- Quantity -->
                                    <div class="item-quantity w-1/3 md:w-1/5 text-center flex justify-center items-center my-2 md:my-0">
                                        <div class="quantity-selector flex items-center border border-gray-300 rounded">
                                             <button type="button" class="quantity-btn minus w-8 h-8 md:w-10 md:h-10 text-lg md:text-xl font-light text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out rounded-l" aria-label="Decrease quantity">-</button>
                                             <input type="number" name="updates[<?= $item['product']['id'] ?>]"
                                                    value="<?= $item['quantity'] ?>" min="1" max="<?= (!empty($item['product']['backorder_allowed']) || !isset($item['product']['stock_quantity'])) ? 99 : max(1, $item['product']['stock_quantity']) ?>"
                                                    class="w-10 h-8 md:w-12 md:h-10 text-center border-l border-r border-gray-300 focus:outline-none focus:ring-1 focus:ring-primary text-sm"
                                                    aria-label="Product quantity">
                                             <button type="button" class="quantity-btn plus w-8 h-8 md:w-10 md:h-10 text-lg md:text-xl font-light text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out rounded-r" aria-label="Increase quantity">+</button>
                                        </div>
                                    </div>

                                    <!-- Subtotal -->
                                    <div class="item-subtotal w-1/3 md:w-1/5 text-right font-semibold md:text-base text-gray-900">
                                         <span class="md:hidden text-xs text-gray-500 mr-1">Subtotal:</span>
                                        $<?= number_format($item['subtotal'], 2) ?>
                                    </div>

                                    <!-- Remove Button -->
                                    <div class="w-full md:w-10 text-center md:text-right mt-2 md:mt-0 md:pl-2">
                                        <button type="button" class="remove-item text-gray-400 hover:text-red-600 transition duration-150 ease-in-out"
                                                data-product-id="<?= $item['product']['id'] ?>" title="Remove item">
                                            <i class="fas fa-times-circle text-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Cart Actions (Update Cart moved near items) -->
                        <div class="cart-actions text-right mt-4">
                            <button type="submit" class="btn btn-secondary update-cart">
                                <i class="fas fa-sync-alt mr-1"></i> Update Cart
                            </button>
                        </div>
                    </div>


                    <!-- Cart Summary Column -->
                    <div class="lg:col-span-1">
                        <div class="cart-summary bg-white shadow rounded-lg p-6 sticky top-24">
                            <h2 class="text-xl font-semibold mb-6 border-b pb-3">Order Summary</h2>
                            <div class="space-y-3 mb-6">
                                <div class="summary-row flex justify-between items-center">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="font-medium text-gray-900">$<?= number_format($total ?? 0, 2) ?></span>
                                </div>
                                <div class="summary-row shipping flex justify-between items-center">
                                    <span class="text-gray-600">Shipping:</span>
                                    <?php $shipping_cost = ($total ?? 0) >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST; ?>
                                    <span class="font-medium text-gray-900">
                                        <?= $shipping_cost == 0 ? '<span class="text-green-600">FREE</span>' : '$' . number_format($shipping_cost, 2) ?>
                                    </span>
                                </div>
                                <!-- Tax can be added here if calculated server-side initially or via JS -->
                                <!--
                                <div class="summary-row tax flex justify-between items-center">
                                    <span class="text-gray-600">Tax:</span>
                                    <span class="font-medium text-gray-900" id="cart-tax-amount">$0.00</span>
                                </div>
                                -->
                            </div>
                            <div class="summary-row total flex justify-between items-center border-t pt-4">
                                <span class="text-lg font-bold text-gray-900">Total:</span>
                                <span class="text-lg font-bold text-primary" id="cart-grand-total">
                                    $<?= number_format(($total ?? 0) + $shipping_cost, 2) ?>
                                </span>
                            </div>
                            <div class="mt-8">
                                <a href="index.php?page=checkout" class="btn btn-primary w-full text-center checkout <?= empty($cartItems) ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                    Proceed to Checkout
                                </a>
                            </div>
                            <p class="text-xs text-gray-500 text-center mt-4">Shipping & taxes calculated at checkout.</p>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
