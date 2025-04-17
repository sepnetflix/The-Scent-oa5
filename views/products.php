<?php require_once 'layout/header.php'; ?>

<section class="products-section">
    <div class="container">
        <div class="products-header" data-aos="fade-up">
            <h1><?= $pageTitle ?></h1>
            
            <!-- Search Bar -->
            <form action="index.php" method="GET" class="search-form">
                <input type="hidden" name="page" value="products">
                <div class="search-input">
                    <input type="text" name="search" placeholder="Search products..."
                           value="<?= htmlspecialchars($searchQuery ?? '') ?>">
                    <button type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="products-grid-container">
            <!-- Filters Sidebar -->
            <aside class="filters-sidebar" data-aos="fade-right">
                <div class="filters-section">
                    <h2>Categories</h2>
                    <ul class="category-list">
                        <li>
                            <a href="index.php?page=products" 
                               class="<?= !isset($_GET['category']) ? 'active' : '' ?>">
                                All Products
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="index.php?page=products&category=<?= urlencode($cat) ?>"
                                   class="<?= ($category === $cat) ? 'active' : '' ?>">
                                    <?= htmlspecialchars($cat) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="filters-section">
                    <h2>Price Range</h2>
                    <div class="price-range">
                        <div class="range-inputs">
                            <input type="number" id="minPrice" placeholder="Min" min="0"
                                   value="<?= $_GET['min_price'] ?? '' ?>">
                            <span>to</span>
                            <input type="number" id="maxPrice" placeholder="Max" min="0"
                                   value="<?= $_GET['max_price'] ?? '' ?>">
                        </div>
                        <button type="button" class="btn-secondary apply-price-filter">Apply</button>
                    </div>
                </div>
            </aside>
            
            <!-- Products Grid -->
            <div class="products-content">
                <div class="products-toolbar" data-aos="fade-up">
                    <div class="showing-products">
                        Showing <?= count($products) ?> products
                    </div>
                    
                    <div class="sort-options">
                        <label for="sort">Sort by:</label>
                        <select id="sort" name="sort">
                            <option value="name_asc" <?= ($sortBy === 'name_asc') ? 'selected' : '' ?>>
                                Name (A-Z)
                            </option>
                            <option value="name_desc" <?= ($sortBy === 'name_desc') ? 'selected' : '' ?>>
                                Name (Z-A)
                            </option>
                            <option value="price_asc" <?= ($sortBy === 'price_asc') ? 'selected' : '' ?>>
                                Price (Low to High)
                            </option>
                            <option value="price_desc" <?= ($sortBy === 'price_desc') ? 'selected' : '' ?>>
                                Price (High to Low)
                            </option>
                        </select>
                    </div>
                </div>
                
                <?php if (empty($products)): ?>
                    <div class="no-products" data-aos="fade-up">
                        <i class="fas fa-search"></i>
                        <p>No products found matching your criteria.</p>
                        <a href="index.php?page=products" class="btn-primary">View All Products</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $index => $product): ?>
                            <div class="product-card" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                                <div class="product-image">
                                    <a href="index.php?page=products&id=<?= $product['id'] ?>">
                                        <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>">
                                    </a>
                                    <?php if ($product['featured']): ?>
                                        <span class="featured-badge">Featured</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-info">
                                    <h3>
                                        <a href="index.php?page=products&id=<?= $product['id'] ?>">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </a>
                                    </h3>
                                    <p class="product-category"><?= htmlspecialchars($product['category']) ?></p>
                                    <p class="product-price">$<?= number_format($product['price'], 2) ?></p>
                                    <div class="product-actions">
                                        <a href="index.php?page=products&id=<?= $product['id'] ?>" 
                                           class="btn-secondary">View Details</a>
                                        <button class="btn-primary add-to-cart" 
                                                data-product-id="<?= $product['id'] ?>">
                                            Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle sorting
    const sortSelect = document.getElementById('sort');
    sortSelect.addEventListener('change', function() {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', this.value);
        window.location.href = url.toString();
    });
    
    // Handle price filter
    const applyPriceFilter = document.querySelector('.apply-price-filter');
    applyPriceFilter.addEventListener('click', function() {
        const minPrice = document.getElementById('minPrice').value;
        const maxPrice = document.getElementById('maxPrice').value;
        
        const url = new URL(window.location.href);
        if (minPrice) url.searchParams.set('min_price', minPrice);
        if (maxPrice) url.searchParams.set('max_price', maxPrice);
        window.location.href = url.toString();
    });
    
    // Handle add to cart
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            
            fetch('index.php?page=cart&action=add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cartCount;
                        cartCount.style.display = 'inline';
                    }
                    
                    // Show success message
                    alert('Product added to cart!');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
});
</script>

<?php require_once 'layout/footer.php'; ?>