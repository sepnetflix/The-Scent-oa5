<?php require_once __DIR__ . '/layout/header.php'; ?>

<section class="product-detail">
    <div class="container">
        <div class="product-container">
            <!-- Product Gallery -->
            <div class="product-gallery" data-aos="fade-right">
                <div class="main-image">
                    <img src="<?= htmlspecialchars($product['image_url'] ?? '/images/placeholder.jpg') ?>" 
                         alt="<?= htmlspecialchars($product['name'] ?? 'Product') ?>"
                         id="mainImage">
                    <?php if (!empty($product['featured'])): ?>
                        <span class="featured-badge">Featured</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($product['gallery_images'])): ?>
                    <div class="thumbnail-grid">
                        <img src="<?= htmlspecialchars($product['image_url'] ?? '/images/placeholder.jpg') ?>" 
                             alt="Main view"
                             class="active"
                             onclick="updateMainImage(this)">
                        <?php foreach ((array)json_decode($product['gallery_images'], true) as $image): ?>
                            <img src="<?= htmlspecialchars($image ?? '/images/placeholder.jpg') ?>" 
                                 alt="Additional view"
                                 onclick="updateMainImage(this)">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Info -->
            <div class="product-info" data-aos="fade-left">
                <nav class="breadcrumb">
                    <a href="index.php?page=products">Products</a> /
                    <a href="index.php?page=products&category=<?= urlencode($product['category'] ?? '') ?>">
                        <?= htmlspecialchars($product['category'] ?? 'N/A') ?>
                    </a> /
                    <span><?= htmlspecialchars($product['name'] ?? 'Product') ?></span>
                </nav>
                
                <h1><?= htmlspecialchars($product['name'] ?? 'Product') ?></h1>
                <p class="price">$<?= isset($product['price']) ? number_format($product['price'], 2) : 'N/A' ?></p>
                
                <div class="product-description">
                    <?= nl2br(htmlspecialchars($product['description'] ?? '')) ?>
                </div>
                
                <div class="benefits">
                    <h3>Benefits</h3>
                    <ul>
                        <?php foreach ((array)json_decode($product['benefits'] ?? '[]', true) as $benefit): ?>
                            <li><i class="fas fa-check"></i> <?= htmlspecialchars($benefit) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="ingredients">
                    <h3>Key Ingredients</h3>
                    <p><?= htmlspecialchars($product['ingredients'] ?? '') ?></p>
                </div>
                
                <form class="add-to-cart-form" action="index.php?page=cart&action=add" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?? '' ?>">
                    <div class="quantity-selector">
                        <button type="button" class="quantity-btn minus">-</button>
                        <input type="number" name="quantity" value="1" min="1" max="99">
                        <button type="button" class="quantity-btn plus">+</button>
                    </div>
                    <button type="submit" class="btn-primary add-to-cart">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                </form>
                
                <div class="additional-info">
                    <div class="info-item">
                        <i class="fas fa-shipping-fast"></i>
                        <span>Free shipping on orders over $50</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-undo"></i>
                        <span>30-day return policy</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-lock"></i>
                        <span>Secure checkout</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Details Tabs -->
        <div class="product-tabs" data-aos="fade-up">
            <div class="tabs-header">
                <button class="tab-btn active" data-tab="usage">How to Use</button>
                <button class="tab-btn" data-tab="details">Details</button>
                <button class="tab-btn" data-tab="shipping">Shipping</button>
                <button class="tab-btn" data-tab="reviews">Reviews</button>
            </div>
            
            <div class="tab-content">
                <div id="usage" class="tab-pane active">
                    <h3>How to Use</h3>
                    <?= nl2br(htmlspecialchars($product['usage_instructions'] ?? '')) ?>
                </div>
                
                <div id="details" class="tab-pane">
                    <h3>Product Details</h3>
                    <table class="details-table">
                        <tr>
                            <th>Size</th>
                            <td><?= htmlspecialchars($product['size'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <th>Scent Profile</th>
                            <td><?= htmlspecialchars($product['scent_profile'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <th>Benefits</th>
                            <td><?= htmlspecialchars($product['benefits_list'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <th>Origin</th>
                            <td><?= htmlspecialchars($product['origin'] ?? '') ?></td>
                        </tr>
                    </table>
                </div>
                
                <div id="shipping" class="tab-pane">
                    <h3>Shipping Information</h3>
                    <div class="shipping-info">
                        <p><strong>Free Standard Shipping</strong> on orders over $50</p>
                        <ul>
                            <li>Standard Shipping (5-7 business days): $5.99</li>
                            <li>Express Shipping (2-3 business days): $12.99</li>
                            <li>Next Day Delivery (order before 2pm): $19.99</li>
                        </ul>
                        <p>International shipping available to select countries.</p>
                    </div>
                </div>
                
                <div id="reviews" class="tab-pane">
                    <h3>Customer Reviews</h3>
                    <div class="reviews-summary">
                        <div class="average-rating">
                            <span class="rating">4.8</span>
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="review-count">Based on 24 reviews</span>
                        </div>
                    </div>
                    <!-- Reviews would be loaded dynamically -->
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
            <div class="related-products" data-aos="fade-up">
                <h2>You May Also Like</h2>
                <div class="products-grid">
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <a href="index.php?page=products&id=<?= $relatedProduct['id'] ?? '' ?>">
                                    <img src="<?= htmlspecialchars($relatedProduct['image_url'] ?? '/images/placeholder.jpg') ?>" 
                                         alt="<?= htmlspecialchars($relatedProduct['name'] ?? 'Product') ?>">
                                </a>
                            </div>
                            <div class="product-info">
                                <h3>
                                    <a href="index.php?page=products&id=<?= $relatedProduct['id'] ?? '' ?>">
                                        <?= htmlspecialchars($relatedProduct['name'] ?? 'Product') ?>
                                    </a>
                                </h3>
                                <p class="price">$<?= isset($relatedProduct['price']) ? number_format($relatedProduct['price'], 2) : 'N/A' ?></p>
                                <button class="btn-primary add-to-cart" 
                                        data-product-id="<?= $relatedProduct['id'] ?? '' ?>">
                                    Add to Cart
                                </button>
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
    // Product image gallery
    function updateMainImage(thumbnail) {
        const mainImage = document.getElementById('mainImage');
        mainImage.src = thumbnail.src;
        
        // Update active state
        document.querySelectorAll('.thumbnail-grid img').forEach(img => {
            img.classList.remove('active');
        });
        thumbnail.classList.add('active');
    }
    
    // Quantity selector
    const quantityInput = document.querySelector('.quantity-selector input');
    document.querySelectorAll('.quantity-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            let value = parseInt(quantityInput.value);
            
            if (this.classList.contains('plus') && value < 99) {
                quantityInput.value = value + 1;
            } else if (this.classList.contains('minus') && value > 1) {
                quantityInput.value = value - 1;
            }
        });
    });
    
    // Product tabs
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            
            // Update active states
            tabBtns.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>