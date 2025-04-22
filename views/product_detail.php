<?php
// Enhanced product detail view with image path fix, robust data handling, AJAX add-to-cart, and modern layout
require_once __DIR__ . '/layout/header.php';
?>
<section class="product-detail py-12 md:py-20 bg-white">
    <div class="container mx-auto px-4">
        <!-- Breadcrumbs -->
        <nav class="breadcrumb text-sm text-gray-600 mb-8" aria-label="Breadcrumb" data-aos="fade-down">
            <ol class="list-none p-0 inline-flex">
                <li class="flex items-center">
                    <a href="index.php?page=products" class="hover:text-primary">Products</a>
                </li>
                <?php if (!empty($product['category_name']) && !empty($product['category_id'])): ?>
                <li class="flex items-center">
                    <svg class="fill-current w-3 h-3 mx-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c-9.373-9.373-24.569-9.373-33.941 0L285.475 239.03c-9.373 9.373-9.373 24.569 0 33.941z"/></svg>
                    <a href="index.php?page=products&category=<?= urlencode($product['category_id']) ?>" class="hover:text-primary">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </a>
                </li>
                <?php endif; ?>
                <li class="flex items-center">
                    <svg class="fill-current w-3 h-3 mx-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c-9.373-9.373-24.569-9.373-33.941 0L285.475 239.03c-9.373 9.373-9.373 24.569 0 33.941z"/></svg>
                    <span class="text-gray-500"><?= htmlspecialchars($product['name'] ?? 'Product') ?></span>
                </li>
            </ol>
        </nav>
        <div class="product-container grid grid-cols-1 md:grid-cols-2 gap-12 items-start">
            <!-- Product Gallery -->
            <div class="product-gallery space-y-4" data-aos="fade-right">
                <div class="main-image relative overflow-hidden rounded-lg shadow-lg aspect-square">
                    <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
                         alt="<?= htmlspecialchars($product['name'] ?? 'Product') ?>"
                         id="mainImage" class="w-full h-full object-cover transition-transform duration-300 ease-in-out hover:scale-105">
                    <?php if (!empty($product['is_featured'])): ?>
                        <span class="absolute top-3 left-3 bg-accent text-white text-xs font-semibold px-3 py-1 rounded-full shadow">Featured</span>
                    <?php endif; ?>
                    <?php
                      $isOutOfStock = (!isset($product['stock_quantity']) || $product['stock_quantity'] <= 0) && empty($product['backorder_allowed']);
                      $isLowStock = !$isOutOfStock && isset($product['low_stock_threshold']) && isset($product['stock_quantity']) && $product['stock_quantity'] <= $product['low_stock_threshold'];
                    ?>
                    <?php if ($isOutOfStock): ?>
                        <span class="absolute top-3 right-3 bg-red-600 text-white text-xs font-semibold px-3 py-1 rounded-full shadow">Out of Stock</span>
                    <?php elseif ($isLowStock): ?>
                        <span class="absolute top-3 right-3 bg-yellow-500 text-white text-xs font-semibold px-3 py-1 rounded-full shadow">Low Stock</span>
                    <?php endif; ?>
                </div>
                <?php
                    $galleryImages = isset($product['gallery_images']) && is_array($product['gallery_images']) ? $product['gallery_images'] : [];
                ?>
                <?php if (!empty($galleryImages)): ?>
                    <div class="thumbnail-grid grid grid-cols-4 gap-3">
                        <div class="border-2 border-primary rounded overflow-hidden cursor-pointer aspect-square">
                            <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
                                 alt="View 1"
                                 class="w-full h-full object-cover active"
                                 onclick="updateMainImage(this)">
                        </div>
                        <?php foreach ($galleryImages as $index => $imagePath): ?>
                            <?php if (!empty($imagePath) && is_string($imagePath)): ?>
                            <div class="border rounded overflow-hidden cursor-pointer aspect-square">
                                <img src="<?= htmlspecialchars($imagePath) ?>"
                                     alt="View <?= $index + 2 ?>"
                                     class="w-full h-full object-cover"
                                     onclick="updateMainImage(this)">
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (!empty($product['image']) && $product['image'] !== '/images/placeholder.jpg'): ?>
                    <div class="thumbnail-grid grid grid-cols-4 gap-3">
                        <div class="border-2 border-primary rounded overflow-hidden cursor-pointer aspect-square">
                            <img src="<?= htmlspecialchars($product['image']) ?>" alt="View 1" class="w-full h-full object-cover active">
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Product Info -->
            <div class="product-info space-y-6" data-aos="fade-left">
                <h1 class="text-3xl md:text-4xl font-bold font-heading text-primary"><?= htmlspecialchars($product['name'] ?? 'Product Name Unavailable') ?></h1>
                <p class="text-2xl font-semibold text-accent font-accent">$<?= isset($product['price']) ? number_format($product['price'], 2) : 'N/A' ?></p>
                <?php if (!empty($product['short_description'])): ?>
                <p class="text-gray-700 text-lg"><?= nl2br(htmlspecialchars($product['short_description'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($product['description']) && (empty($product['short_description']) || $product['description'] !== $product['short_description'])): ?>
                <div class="prose max-w-none text-gray-600">
                    <?= nl2br(htmlspecialchars($product['description'])) ?>
                </div>
                <?php elseif (empty($product['short_description']) && empty($product['description'])): ?>
                <p class="text-gray-500 italic">No description available.</p>
                <?php endif; ?>
                <?php $benefits = isset($product['benefits']) && is_array($product['benefits']) ? $product['benefits'] : []; ?>
                <?php if (!empty($benefits)): ?>
                <div class="benefits border-t pt-4">
                    <h3 class="text-lg font-semibold mb-3 text-primary-dark">Benefits</h3>
                    <ul class="list-none space-y-1 text-gray-600">
                        <?php foreach ($benefits as $benefit): ?>
                            <?php if(!empty($benefit) && is_string($benefit)): ?>
                            <li class="flex items-start"><i class="fas fa-check-circle text-secondary mr-2 mt-1 flex-shrink-0"></i><span><?= htmlspecialchars($benefit) ?></span></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if (!empty($product['ingredients'])): ?>
                <div class="ingredients border-t pt-4">
                    <h3 class="text-lg font-semibold mb-3 text-primary-dark">Key Ingredients</h3>
                    <p class="text-gray-600"><?= nl2br(htmlspecialchars($product['ingredients'])) ?></p>
                </div>
                <?php endif; ?>
                <form class="add-to-cart-form space-y-4 border-t pt-6" action="index.php?page=cart&action=add" method="POST" id="product-detail-add-cart-form">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?? '' ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="flex items-center space-x-4">
                        <label for="quantity" class="font-semibold text-gray-700">Quantity:</label>
                        <div class="quantity-selector flex items-center border border-gray-300 rounded">
                            <button type="button" class="quantity-btn minus w-10 h-10 text-xl font-light text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out rounded-l" aria-label="Decrease quantity">-</button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= (!empty($product['backorder_allowed']) || !isset($product['stock_quantity'])) ? 99 : max(1, $product['stock_quantity']) ?>" class="w-16 h-10 text-center border-l border-r border-gray-300 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary" aria-label="Product quantity">
                            <button type="button" class="quantity-btn plus w-10 h-10 text-xl font-light text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out rounded-r" aria-label="Increase quantity">+</button>
                        </div>
                    </div>
                    <?php if (!$isOutOfStock): ?>
                        <button type="submit" class="btn btn-primary w-full py-3 text-lg add-to-cart">
                            <i class="fas fa-shopping-cart mr-2"></i> Add to Cart
                        </button>
                        <?php if ($isLowStock): ?>
                            <p class="text-sm text-yellow-600 text-center mt-2">Limited quantity available!</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <button type="button" class="btn btn-disabled w-full py-3 text-lg" disabled>
                            <i class="fas fa-times-circle mr-2"></i> Out of Stock
                        </button>
                    <?php endif; ?>
                </form>
                <div class="additional-info flex flex-wrap justify-around border-t pt-6 text-center text-sm text-gray-600">
                    <div class="info-item p-2 w-1/3">
                        <i class="fas fa-shipping-fast text-2xl text-secondary mb-2 block"></i>
                        <span>Free shipping over $50</span>
                    </div>
                    <div class="info-item p-2 w-1/3">
                        <i class="fas fa-undo text-2xl text-secondary mb-2 block"></i>
                        <span>30-day returns</span>
                    </div>
                    <div class="info-item p-2 w-1/3">
                        <i class="fas fa-lock text-2xl text-secondary mb-2 block"></i>
                        <span>Secure checkout</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Product Details Tabs -->
        <div class="product-tabs mt-16 md:mt-24" data-aos="fade-up">
            <div class="tabs-header border-b border-gray-200 mb-8 flex space-x-8">
                <?php $hasDetails = !empty($product['size']) || !empty($product['scent_profile']) || !empty($product['origin']) || !empty($product['sku']); ?>
                <?php $hasUsage = !empty($product['usage_instructions']); ?>
                <?php $hasShipping = true; ?>
                <?php $hasReviews = true; ?>
                <?php $activeTab = $hasDetails ? 'details' : ($hasUsage ? 'usage' : ($hasShipping ? 'shipping' : 'reviews')); ?>
                <?php if ($hasDetails): ?>
                <button class="tab-btn py-3 px-1 border-b-2 text-lg font-medium focus:outline-none <?= $activeTab === 'details' ? 'text-primary border-primary' : 'text-gray-500 border-transparent hover:text-primary hover:border-gray-300' ?>" data-tab="details">Details</button>
                <?php endif; ?>
                <?php if ($hasUsage): ?>
                <button class="tab-btn py-3 px-1 border-b-2 text-lg font-medium focus:outline-none <?= $activeTab === 'usage' ? 'text-primary border-primary' : 'text-gray-500 border-transparent hover:text-primary hover:border-gray-300' ?>" data-tab="usage">How to Use</button>
                <?php endif; ?>
                <?php if ($hasShipping): ?>
                <button class="tab-btn py-3 px-1 border-b-2 text-lg font-medium focus:outline-none <?= $activeTab === 'shipping' ? 'text-primary border-primary' : 'text-gray-500 border-transparent hover:text-primary hover:border-gray-300' ?>" data-tab="shipping">Shipping</button>
                <?php endif; ?>
                <?php if ($hasReviews): ?>
                <button class="tab-btn py-3 px-1 border-b-2 text-lg font-medium focus:outline-none <?= $activeTab === 'reviews' ? 'text-primary border-primary' : 'text-gray-500 border-transparent hover:text-primary hover:border-gray-300' ?>" data-tab="reviews">Reviews</button>
                <?php endif; ?>
            </div>
            <div class="tab-content min-h-[200px]">
                <div id="details" class="tab-pane prose max-w-none text-gray-700 <?= $activeTab === 'details' ? 'active' : '' ?>">
                    <?php if ($hasDetails): ?>
                        <h3 class="text-xl font-semibold mb-4 text-primary-dark sr-only">Product Details</h3>
                        <table class="details-table w-full text-left">
                            <tbody>
                                <?php if (!empty($product['size'])): ?>
                                <tr class="border-b border-gray-200"><th class="py-2 pr-4 font-medium text-gray-600 w-1/4">Size</th><td class="py-2"><?= htmlspecialchars($product['size']) ?></td></tr>
                                <?php endif; ?>
                                <?php if (!empty($product['scent_profile'])): ?>
                                <tr class="border-b border-gray-200"><th class="py-2 pr-4 font-medium text-gray-600 w-1/4">Scent Profile</th><td class="py-2"><?= htmlspecialchars($product['scent_profile']) ?></td></tr>
                                <?php endif; ?>
                                <?php if (!empty($product['origin'])): ?>
                                <tr class="border-b border-gray-200"><th class="py-2 pr-4 font-medium text-gray-600 w-1/4">Origin</th><td class="py-2"><?= htmlspecialchars($product['origin']) ?></td></tr>
                                <?php endif; ?>
                                <?php if (!empty($product['sku'])): ?>
                                <tr class="border-b border-gray-200"><th class="py-2 pr-4 font-medium text-gray-600 w-1/4">SKU</th><td class="py-2"><?= htmlspecialchars($product['sku']) ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php if (!empty($product['short_description']) && !empty($product['description']) && $product['description'] !== $product['short_description']): ?>
                        <div class="mt-6">
                            <h4 class="text-lg font-semibold mb-2 text-primary-dark">Full Description</h4>
                            <?= nl2br(htmlspecialchars($product['description'])) ?>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-gray-500 italic">Detailed specifications not available.</p>
                    <?php endif; ?>
                </div>
                <div id="usage" class="tab-pane prose max-w-none text-gray-700 <?= $activeTab === 'usage' ? 'active' : '' ?>">
                    <h3 class="text-xl font-semibold mb-4 text-primary-dark sr-only">How to Use</h3>
                    <?php if ($hasUsage): ?>
                        <?= nl2br(htmlspecialchars($product['usage_instructions'])) ?>
                    <?php else: ?>
                        <p class="text-gray-500 italic">Usage instructions are not available for this product.</p>
                    <?php endif; ?>
                </div>
                <div id="shipping" class="tab-pane prose max-w-none text-gray-700 <?= $activeTab === 'shipping' ? 'active' : '' ?>">
                    <h3 class="text-xl font-semibold mb-4 text-primary-dark sr-only">Shipping Information</h3>
                    <div class="shipping-info">
                        <p><strong>Free Standard Shipping</strong> on orders over $50.</p>
                        <ul class="list-disc list-inside space-y-1 my-4">
                            <li>Standard Shipping (5-7 business days): $5.99</li>
                            <li>Express Shipping (2-3 business days): $12.99</li>
                            <li>Next Day Delivery (order before 2pm): $19.99</li>
                        </ul>
                        <p>We ship with care to ensure your products arrive safely. Tracking information will be provided once your order ships. International shipping available to select countries (rates calculated at checkout).</p>
                        <p class="mt-4"><a href="index.php?page=shipping" class="text-primary hover:underline">View Full Shipping Policy</a></p>
                    </div>
                </div>
                <div id="reviews" class="tab-pane <?= $activeTab === 'reviews' ? 'active' : '' ?>">
                    <h3 class="text-xl font-semibold mb-4 text-primary-dark sr-only">Customer Reviews</h3>
                    <div class="reviews-summary mb-8 p-6 bg-light rounded-lg flex flex-col sm:flex-row items-center gap-6">
                        <div class="average-rating text-center">
                            <span class="block text-4xl font-bold text-accent">N/A</span>
                            <div class="stars text-gray-300 text-xl my-1" title="No reviews yet">
                                <i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                            </div>
                            <span class="text-sm text-gray-600">Based on 0 reviews</span>
                        </div>
                        <div class="flex-grow text-center sm:text-left">
                            <p class="mb-3 text-gray-700">Share your thoughts with other customers!</p>
                            <button class="btn btn-secondary">Write a Review</button>
                        </div>
                    </div>
                    <div class="reviews-list space-y-6">
                        <p class="text-center text-gray-500 italic py-4">There are no reviews for this product yet.</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
            <div class="related-products mt-16 md:mt-24 border-t border-gray-200 pt-12" data-aos="fade-up">
                <h2 class="text-3xl font-bold text-center mb-12 font-heading text-primary">You May Also Like</h2>
                <div class="products-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                        <div class="product-card sample-card bg-white rounded-lg shadow-md overflow-hidden transition-shadow duration-300 hover:shadow-xl flex flex-col">
                            <div class="product-image relative h-64 overflow-hidden">
                                <a href="index.php?page=product&id=<?= $relatedProduct['id'] ?? '' ?>">
                                    <img src="<?= htmlspecialchars($relatedProduct['image'] ?? '/images/placeholder.jpg') ?>"
                                         alt="<?= htmlspecialchars($relatedProduct['name'] ?? 'Product') ?>" class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                                </a>
                                <?php if (!empty($relatedProduct['is_featured'])): ?>
                                    <span class="absolute top-2 left-2 bg-accent text-white text-xs font-semibold px-2 py-0.5 rounded-full">Featured</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info p-4 flex flex-col flex-grow text-center">
                                <h3 class="text-lg font-semibold mb-1 font-heading text-primary hover:text-accent">
                                    <a href="index.php?page=product&id=<?= $relatedProduct['id'] ?? '' ?>">
                                        <?= htmlspecialchars($relatedProduct['name'] ?? 'Product') ?>
                                    </a>
                                </h3>
                                <?php if (!empty($relatedProduct['category_name'])): ?>
                                    <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($relatedProduct['category_name']) ?></p>
                                <?php endif; ?>
                                <p class="price text-base font-semibold text-accent mb-4 mt-auto">$<?= isset($relatedProduct['price']) ? number_format($relatedProduct['price'], 2) : 'N/A' ?></p>
                                <div class="product-actions mt-auto">
                                    <?php $relatedIsOutOfStock = (!isset($relatedProduct['stock_quantity']) || $relatedProduct['stock_quantity'] <= 0) && empty($relatedProduct['backorder_allowed']); ?>
                                    <?php if (!$relatedIsOutOfStock): ?>
                                        <button class="btn btn-secondary add-to-cart-related w-full"
                                                data-product-id="<?= $relatedProduct['id'] ?? '' ?>">
                                            Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-disabled w-full" disabled>Out of Stock</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Gallery Logic ---
    const mainImage = document.getElementById('mainImage');
    const thumbnails = document.querySelectorAll('.thumbnail-grid img');
    function updateMainImage(thumbnailElement) {
        if (mainImage && thumbnailElement) {
            mainImage.src = thumbnailElement.src;
            mainImage.alt = thumbnailElement.alt.replace('View', 'Main view');
            thumbnails.forEach(img => {
                img.classList.remove('active');
            });
            thumbnailElement.classList.add('active');
        }
    }
    window.updateMainImage = updateMainImage;
    // --- Quantity Selector Logic ---
    const quantityInput = document.querySelector('.quantity-selector input');
    if (quantityInput) {
        const quantityMax = parseInt(quantityInput.getAttribute('max') || '99');
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                if (isNaN(value)) value = 1;
                if (this.classList.contains('plus')) {
                    if (value < quantityMax) quantityInput.value = value + 1;
                    else quantityInput.value = quantityMax;
                } else if (this.classList.contains('minus')) {
                    if (value > 1) quantityInput.value = value - 1;
                }
            });
        });
    }
    // --- Tab Switching Logic ---
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            tabBtns.forEach(b => b.classList.remove('active', 'text-primary', 'border-primary'));
            tabBtns.forEach(b => b.classList.add('text-gray-500', 'border-transparent'));
            this.classList.add('active', 'text-primary', 'border-primary');
            this.classList.remove('text-gray-500', 'border-transparent');
            tabPanes.forEach(pane => {
                if (pane.id === tabId) {
                    pane.classList.add('active');
                    pane.classList.remove('hidden');
                } else {
                    pane.classList.remove('active');
                    pane.classList.add('hidden');
                }
            });
        });
    });
    // Ensure initial active tab's pane is visible
    const initialActiveTab = document.querySelector('.tab-btn.active');
    if(initialActiveTab) {
        const initialTabId = initialActiveTab.dataset.tab;
        tabPanes.forEach(pane => {
            if (pane.id === initialTabId) {
                pane.classList.add('active');
                pane.classList.remove('hidden');
            } else {
                pane.classList.remove('active');
                pane.classList.add('hidden');
            }
        });
    }
    // --- Add to Cart Form Submission (AJAX) ---
    const addToCartForm = document.getElementById('product-detail-add-cart-form');
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"].add-to-cart');
            if (!submitButton || submitButton.disabled) return;
            const originalButtonHtml = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...';
            fetch(this.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new URLSearchParams(formData)
            })
            .then(response => {
                const contentType = response.headers.get("content-type");
                if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                }
                return response.text().then(text => {
                    throw new Error(`Server error ${response.status}: ${text.substring(0, 200)}`);
                });
            })
            .then(data => {
                if (data.success) {
                    const cartCountSpan = document.querySelector('.cart-count');
                    if (cartCountSpan) {
                        cartCountSpan.textContent = data.cart_count;
                        cartCountSpan.style.display = data.cart_count > 0 ? 'inline-block' : 'none';
                    }
                    showFlashMessage(data.message || 'Product added to cart!', 'success');
                    if(data.stock_status === 'out_of_stock') {
                        submitButton.classList.remove('btn-primary');
                        submitButton.classList.add('btn-disabled');
                        submitButton.innerHTML = '<i class="fas fa-times-circle mr-2"></i> Out of Stock';
                    } else {
                        submitButton.innerHTML = originalButtonHtml;
                        submitButton.disabled = false;
                    }
                } else {
                    showFlashMessage(data.message || 'Could not add product.', 'error');
                    submitButton.innerHTML = originalButtonHtml;
                    submitButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
                showFlashMessage('An error occurred. Please try again.', 'error');
                if (submitButton) {
                    submitButton.innerHTML = originalButtonHtml;
                    submitButton.disabled = false;
                }
            });
        });
    }
    // --- Related Products Add to Cart (AJAX) ---
    document.querySelectorAll('.add-to-cart-related').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const csrfTokenInput = document.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';
            if (!csrfToken) {
                showFlashMessage('Security token not found. Please refresh.', 'error');
                return;
            }
            if (this.disabled) return;
            const originalButtonText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            const formData = new URLSearchParams();
            formData.append('product_id', productId);
            formData.append('quantity', '1');
            formData.append('csrf_token', csrfToken);
            fetch('index.php?page=cart&action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(response => {
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        throw new Error("Received non-JSON response: " + text.substring(0, 200));
                    });
                }
            })
            .then(data => {
                if (data.success) {
                    const cartCountSpan = document.querySelector('.cart-count');
                    if (cartCountSpan) {
                        cartCountSpan.textContent = data.cart_count;
                        cartCountSpan.style.display = data.cart_count > 0 ? 'inline-block' : 'none';
                    }
                    showFlashMessage(data.message || 'Product added to cart!', 'success');
                    if(data.stock_status === 'out_of_stock') {
                        this.classList.remove('btn-secondary');
                        this.classList.add('btn-disabled');
                        this.innerHTML = 'Out of Stock';
                    } else {
                        this.innerHTML = originalButtonText;
                        this.disabled = false;
                    }
                } else {
                    showFlashMessage(data.message || 'Could not add product.', 'error');
                    this.innerHTML = originalButtonText;
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error adding related product:', error);
                showFlashMessage('An error occurred. Please try again.', 'error');
                this.innerHTML = originalButtonText;
                this.disabled = false;
            });
        });
    });
});
</script>
<?php require_once __DIR__ . '/layout/footer.php'; ?>