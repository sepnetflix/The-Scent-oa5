<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-products">

<!-- Output CSRF token for JS (for AJAX add-to-cart) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

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
                <!-- Horizontal Category Filter Bar -->
                <div class="category-filter-bar mb-6 pb-4 border-b border-gray-200" data-aos="fade-up">
                    <nav class="flex flex-wrap gap-x-4 gap-y-2 items-center">
                        <a href="index.php?page=products"
                           class="category-link <?= empty($_GET['category']) ? 'active' : '' ?>">
                            All Products
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="index.php?page=products&category=<?= urlencode($cat['id']) ?>"
                               class="category-link <?= (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'active' : '' ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
                
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
                        <?php if (!empty($searchQuery) || !empty($categoryId) || !empty($_GET['min_price']) || !empty($_GET['max_price'])): ?>
                            <p class="text-gray-500 mb-6">Try adjusting your search terms or filters in the sidebar.</p>
                            <a href="index.php?page=products" class="btn-secondary mr-2">Clear Filters</a>
                        <?php else: ?>
                            <p class="text-gray-500 mb-6">Explore our collections or try a different search.</p>
                        <?php endif; ?>
                        <a href="index.php?page=products" class="btn-primary">View All Products</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 px-6">
                        <?php foreach ($products as $index => $product): ?>
                            <!-- Modern product card structure, matching home.php/product_detail.php -->
                            <div class="product-card sample-card bg-white rounded-lg shadow-md overflow-hidden transition-shadow duration-300 hover:shadow-xl flex flex-col" data-aos="zoom-in" data-aos-delay="<?= $index * 100 ?>">
                                <div class="product-image relative h-64 overflow-hidden">
                                    <a href="index.php?page=product&id=<?= $product['id'] ?>">
                                        <img src="<?= htmlspecialchars($product['image_url'] ?? '/images/placeholder.jpg') ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                                    </a>
                                    <?php if (!empty($product['featured'])): ?>
                                        <span class="absolute top-2 left-2 bg-accent text-white text-xs font-semibold px-2 py-0.5 rounded-full">Featured</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info p-4 flex flex-col flex-grow text-center">
                                    <h3 class="text-lg font-semibold mb-1 font-heading text-primary hover:text-accent">
                                        <a href="index.php?page=product&id=<?= $product['id'] ?>">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </a>
                                    </h3>
                                    <?php if (!empty($product['short_description'])): ?>
                                        <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($product['short_description']) ?></p>
                                    <?php elseif (!empty($product['category_name'])): ?>
                                        <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($product['category_name']) ?></p>
                                    <?php endif; ?>
                                    <p class="price text-base font-semibold text-accent mb-4 mt-auto">$<?= isset($product['price']) ? number_format($product['price'], 2) : 'N/A' ?></p>
                                    <div class="product-actions mt-auto flex gap-2 justify-center">
                                        <a href="index.php?page=product&id=<?= $product['id'] ?>" class="btn btn-primary">View Details</a>
                                        <?php $isOutOfStock = (!isset($product['stock_quantity']) || $product['stock_quantity'] <= 0) && empty($product['backorder_allowed']); ?>
                                        <?php if (!$isOutOfStock): ?>
                                            <button class="btn btn-secondary add-to-cart" 
                                                    data-product-id="<?= $product['id'] ?>"
                                                    <?= isset($product['low_stock_threshold']) && isset($product['stock_quantity']) && $product['stock_quantity'] <= $product['low_stock_threshold'] ? 'data-low-stock="true"' : '' ?>>
                                                Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-disabled" disabled>Out of Stock</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <!-- Pagination block -->
                <?php if (isset($paginationData) && $paginationData['totalPages'] > 1): ?>
                    <nav aria-label="Page navigation" class="mt-12 flex justify-center" data-aos="fade-up">
                        <ul class="inline-flex items-center -space-x-px">
                            <!-- Previous Button -->
                            <li>
                                <a href="<?= $paginationData['currentPage'] > 1 ? htmlspecialchars($paginationData['baseUrl'] . '&page_num=' . ($paginationData['currentPage'] - 1)) : '#' ?>"
                                   class="py-2 px-3 ml-0 leading-tight text-gray-500 bg-white rounded-l-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 <?= $paginationData['currentPage'] <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php
                            $numLinks = 2;
                            $startPage = max(1, $paginationData['currentPage'] - $numLinks);
                            $endPage = min($paginationData['totalPages'], $paginationData['currentPage'] + $numLinks);
                            if ($startPage > 1) {
                                echo '<li><a href="'.htmlspecialchars($paginationData['baseUrl'].'&page_num=1').'" class="py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">1</a></li>';
                                if ($startPage > 2) {
                                     echo '<li><span class="py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
                                }
                            }
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li>
                                    <a href="<?= htmlspecialchars($paginationData['baseUrl'] . '&page_num=' . $i) ?>"
                                       class="py-2 px-3 leading-tight <?= $i == $paginationData['currentPage'] ? 'z-10 text-primary bg-secondary border-primary hover:bg-secondary hover:text-primary' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor;
                             if ($endPage < $paginationData['totalPages']) {
                                if ($endPage < $paginationData['totalPages'] - 1) {
                                     echo '<li><span class="py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
                                }
                                echo '<li><a href="'.htmlspecialchars($paginationData['baseUrl'].'&page_num='.$paginationData['totalPages']).'" class="py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">'.$paginationData['totalPages'].'</a></li>';
                            }
                            ?>
                            <!-- Next Button -->
                            <li>
                                <a href="<?= $paginationData['currentPage'] < $paginationData['totalPages'] ? htmlspecialchars($paginationData['baseUrl'] . '&page_num=' . ($paginationData['currentPage'] + 1)) : '#' ?>"
                                   class="py-2 px-3 leading-tight text-gray-500 bg-white rounded-r-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 <?= $paginationData['currentPage'] >= $paginationData['totalPages'] ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>