# Product Detail Page: Image Fix and Layout Enhancement (v2.0)

**Document Version:** 2.0
**Date:** 2024-05-16

## 1. Problem Summary

The product detail page, specifically `/index.php?page=product&id=1` (output shown in `view_details_product_id-1.html`), has two primary issues:

1.  **Missing Product Image:** The page displays the generic `/images/placeholder.jpg` instead of the actual product image stored in the database.
2.  **Inconsistent & Suboptimal Layout:** The page's design and structure are basic, lack visual appeal, don't effectively present information (many fields appear empty), and are inconsistent with the modern, polished design of the main landing page (`current_landing_page.html`).

This document provides the solution for the image path issue and recommends adopting the previously suggested enhanced layout for a consistent and improved user experience.

## 2. Issue 1: Fixing the Missing Product Image

### Analysis

*   **Database:** The `the_scent_schema.sql.txt` schema defines the product image column as `image` in the `products` table. Database updates likely populated this column with paths like `/images/products/1.jpg`.
*   **View Code (`views/product_detail.php` - Original):** The code attempts to display the image using `$product['image_url']`.
*   **Cause:** There's a mismatch. The code looks for `$product['image_url']`, but the database query (presumably fetching data based on the `products` table schema via `Product::getById`) provides the data under the key `$product['image']`. Since `$product['image_url']` is unset, the code falls back to the placeholder image (`?? '/images/placeholder.jpg'`).
*   **Consistency Check:** Other views like `views/products.php` might also incorrectly use `image_url`, while `views/home.php` correctly uses `image`.

### Solution

Standardize the variable key used in all view templates to match the database schema column name: `image`.

1.  **Modify `views/product_detail.php` (Original or Redesigned):** Ensure the `img` tag uses `$product['image']`. *Note: The redesigned code snippet in section 3 already incorporates this fix.*

    ```php
    // Change this:
    // <img src="<?= htmlspecialchars($product['image_url'] ?? '/images/placeholder.jpg') ?>" ... >

    // To this:
    <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>" ... >
    ```
    Apply this change to the main image display, the main image thumbnail (if present), and the related product image loop.

2.  **Modify `views/products.php`:** Update the product grid loop to use `$product['image']`.

    ```php
    // In views/products.php - Products Grid Loop
    // Change this:
    // <img src="<?= htmlspecialchars($product['image_url']) ?>" ... >

    // To this:
    <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
         alt="<?= htmlspecialchars($product['name'] ?? 'Product') ?>">
    ```

3.  **Verify `views/home.php`:** The provided code for `home.php` already uses `$product['image']` correctly.

### Verification Steps

*   Confirm the `image` column exists in your `products` table.
*   Verify that the `image` column contains the correct, *relative* web paths (e.g., `/images/products/1.jpg`).
*   Ensure the actual image files (e.g., `1.jpg`, `2.jpg`) exist in the `/images/products/` directory within your project's web root.
*   Check that the database query within `ProductController::showProduct` (or `Product::getById` model method) selects the `image` column.

## 3. Issue 2: Enhancing Layout and Design

### Analysis

The current product detail page lacks the structure, styling, and modern feel of the landing page. It doesn't effectively use Tailwind CSS and presents information poorly.

### Solution: Adopt Redesigned View

The most effective way to achieve a consistent and improved design is to **replace the content of your existing `views/product_detail.php` with the enhanced version provided in the reference document `suggestions_for_improvement_images.md` (section 3, "Code Snippet: Revised `views/product_detail.php`")**.

This redesigned code provides:

*   **Consistent Styling:** Uses Tailwind CSS extensively, matching the landing page's fonts, colors (`primary`, `secondary`, `accent`), spacing, and button styles.
*   **Modern Layout:** Implements a responsive two-column layout (gallery left, info right) using Tailwind Grid.
*   **Improved Gallery:** Features a main image display with optional badges (Featured, Out of Stock, Low Stock) and interactive thumbnails below.
*   **Clear Information Hierarchy:** Better organization of title, price, descriptions, benefits, ingredients, etc., using appropriate typography and spacing.
*   **Enhanced Tabs:** Styles the product detail tabs (Details, Usage, Shipping, Reviews) for better usability.
*   **AJAX Add-to-Cart:** Includes JavaScript for the main "Add to Cart" button to submit via AJAX for a smoother experience (requires backend JSON response).
*   **Styled Related Products:** Uses the same visually appealing card style as the landing page for related products.
*   **Image Path Fix:** Already incorporates the necessary `$product['image']` fix identified in Issue 1.

### Code Implementation

**Replace the entire content** of your current `views/product_detail.php` file with the code block found under "Code Snippet: Revised `views/product_detail.php`" in the `suggestions_for_improvement_images.md` file.

**(The full code snippet from that document is embedded here again for convenience):**

```php
<?php
// Ensure BaseController or similar provides $this->getCsrfToken() or make $_SESSION['csrf_token'] available directly
// *** IMPORTANT: Ensure CSRF token generation is fixed first (see suggested_improvements_and_fixes.md) ***
$csrfToken = $_SESSION['csrf_token'] ?? ''; // Make sure this actually has a value
require_once __DIR__ . '/layout/header.php'; // Includes <head>, Tailwind config, main nav
?>

<section class="product-detail py-12 md:py-20 bg-white">
    <div class="container mx-auto px-4">

        <!-- Breadcrumbs -->
        <nav class="breadcrumb text-sm text-gray-600 mb-8" aria-label="Breadcrumb" data-aos="fade-down">
            <ol class="list-none p-0 inline-flex">
                <li class="flex items-center">
                    <a href="index.php?page=products" class="hover:text-primary">Products</a>
                    <svg class="fill-current w-3 h-3 mx-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"/></svg>
                </li>
                <?php // Use category_id for link consistency, category_name for display ?>
                <?php if (!empty($product['category_name']) && !empty($product['category_id'])): ?>
                <li class="flex items-center">
                    <a href="index.php?page=products&category=<?= urlencode($product['category_id']) ?>" class="hover:text-primary">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </a>
                    <svg class="fill-current w-3 h-3 mx-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"/></svg>
                </li>
                <?php endif; ?>
                <li class="flex items-center">
                    <span class="text-gray-500"><?= htmlspecialchars($product['name'] ?? 'Product') ?></span>
                </li>
            </ol>
        </nav>

        <div class="product-container grid grid-cols-1 md:grid-cols-2 gap-12 items-start">

            <!-- Product Gallery -->
            <div class="product-gallery space-y-4" data-aos="fade-right">
                <div class="main-image relative overflow-hidden rounded-lg shadow-lg aspect-square">
                    <?php // *** IMAGE PATH FIX APPLIED HERE *** ?>
                    <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
                         alt="<?= htmlspecialchars($product['name'] ?? 'Product') ?>"
                         id="mainImage" class="w-full h-full object-cover transition-transform duration-300 ease-in-out hover:scale-105">
                    <?php if (!empty($product['is_featured'])): ?>
                        <span class="absolute top-3 left-3 bg-accent text-white text-xs font-semibold px-3 py-1 rounded-full shadow">Featured</span>
                    <?php endif; ?>
                     <?php // Check using backorder_allowed if available, otherwise just stock_quantity ?>
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
                    // Attempt to decode gallery images - requires 'gallery_images' column in DB (see Dependencies)
                    $galleryImages = isset($product['gallery_images']) ? (json_decode($product['gallery_images'], true) ?? []) : [];
                ?>
                <?php if (!empty($galleryImages) && is_array($galleryImages)): ?>
                    <div class="thumbnail-grid grid grid-cols-4 gap-3">
                        <!-- Thumbnail for Main Image -->
                        <div class="border-2 border-primary rounded overflow-hidden cursor-pointer aspect-square">
                             <?php // *** IMAGE PATH FIX APPLIED HERE *** ?>
                             <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
                                  alt="View 1"
                                  class="w-full h-full object-cover active"
                                  onclick="updateMainImage(this)">
                         </div>
                        <!-- Thumbnails for Gallery Images -->
                        <?php foreach ($galleryImages as $index => $imagePath): ?>
                            <?php if (!empty($imagePath) && is_string($imagePath)): // Basic validation ?>
                            <div class="border rounded overflow-hidden cursor-pointer aspect-square">
                                <img src="<?= htmlspecialchars($imagePath) ?>"
                                     alt="View <?= $index + 2 ?>"
                                     class="w-full h-full object-cover"
                                     onclick="updateMainImage(this)">
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                 <?php // Optionally show a single thumbnail if gallery is empty but main image exists ?>
                 <?php elseif (empty($galleryImages) && !empty($product['image']) && $product['image'] !== '/images/placeholder.jpg'): ?>
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

                <!-- Short Description - Requires 'short_description' column in DB -->
                <?php if (!empty($product['short_description'])): ?>
                <p class="text-gray-700 text-lg"><?= nl2br(htmlspecialchars($product['short_description'])) ?></p>
                <?php endif; ?>

                <!-- Full Description (Show if different from short, or if short is empty) -->
                 <?php if (!empty($product['description']) && (empty($product['short_description']) || $product['description'] !== $product['short_description'])): ?>
                 <div class="prose max-w-none text-gray-600">
                    <?= nl2br(htmlspecialchars($product['description'])) ?>
                 </div>
                 <?php elseif (empty($product['short_description']) && empty($product['description'])): ?>
                     <p class="text-gray-500 italic">No description available.</p>
                 <?php endif; ?>


                <!-- Benefits - Requires 'benefits' column (JSON/TEXT) in DB -->
                <?php $benefits = isset($product['benefits']) ? (json_decode($product['benefits'], true) ?? []) : []; ?>
                <?php if (!empty($benefits) && is_array($benefits)): ?>
                <div class="benefits border-t pt-4">
                    <h3 class="text-lg font-semibold mb-3 text-primary-dark">Benefits</h3>
                    <ul class="list-none space-y-1 text-gray-600">
                        <?php foreach ($benefits as $benefit): ?>
                            <?php if(!empty($benefit) && is_string($benefit)): // Basic validation ?>
                            <li class="flex items-start"><i class="fas fa-check-circle text-secondary mr-2 mt-1 flex-shrink-0"></i><span><?= htmlspecialchars($benefit) ?></span></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Ingredients - Requires 'ingredients' column (TEXT) in DB -->
                 <?php if (!empty($product['ingredients'])): ?>
                 <div class="ingredients border-t pt-4">
                     <h3 class="text-lg font-semibold mb-3 text-primary-dark">Key Ingredients</h3>
                     <p class="text-gray-600"><?= nl2br(htmlspecialchars($product['ingredients'])) ?></p>
                 </div>
                 <?php endif; ?>


                <!-- Add to Cart Form -->
                <form class="add-to-cart-form space-y-4 border-t pt-6" action="index.php?page=cart&action=add" method="POST" id="product-detail-add-cart-form">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?? '' ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) // Ensure $csrfToken has value ?>">

                    <div class="flex items-center space-x-4">
                        <label for="quantity" class="font-semibold text-gray-700">Quantity:</label>
                        <div class="quantity-selector flex items-center border border-gray-300 rounded">
                            <button type="button" class="quantity-btn minus w-10 h-10 text-xl font-light text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out rounded-l" aria-label="Decrease quantity">-</button>
                            <?php // Set max based on stock if not backorderable ?>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= (!empty($product['backorder_allowed']) || !isset($product['stock_quantity'])) ? 99 : max(1, $product['stock_quantity']) ?>" class="w-16 h-10 text-center border-l border-r border-gray-300 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary" aria-label="Product quantity">
                            <button type="button" class="quantity-btn plus w-10 h-10 text-xl font-light text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out rounded-r" aria-label="Increase quantity">+</button>
                        </div>
                    </div>

                    <?php // Use $isOutOfStock flag calculated earlier ?>
                    <?php if (!$isOutOfStock): ?>
                        <button type="submit" class="btn btn-primary w-full py-3 text-lg add-to-cart">
                            <i class="fas fa-shopping-cart mr-2"></i> Add to Cart
                        </button>
                        <?php // Use $isLowStock flag calculated earlier ?>
                        <?php if ($isLowStock): ?>
                             <p class="text-sm text-yellow-600 text-center mt-2">Limited quantity available!</p>
                         <?php endif; ?>
                    <?php else: ?>
                        <button type="button" class="btn btn-disabled w-full py-3 text-lg" disabled>
                            <i class="fas fa-times-circle mr-2"></i> Out of Stock
                        </button>
                    <?php endif; ?>
                </form>

                <!-- Additional Info Icons -->
                <div class="additional-info flex flex-wrap justify-around border-t pt-6 text-center text-sm text-gray-600">
                    <div class="info-item p-2 w-1/3">
                        <i class="fas fa-shipping-fast text-2xl text-secondary mb-2 block"></i>
                        <span>Free shipping over $<?= number_format(FREE_SHIPPING_THRESHOLD ?? 50, 2) ?></span>
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
                <?php // Determine which tabs have content to show ?>
                <?php $hasDetails = !empty($product['size']) || !empty($product['scent_profile']) || !empty($product['origin']) || !empty($product['sku']); ?>
                <?php $hasUsage = !empty($product['usage_instructions']); ?>
                <?php $hasShipping = true; // Always show shipping info ?>
                <?php $hasReviews = true; // Always show reviews section (even if empty) ?>

                <?php $activeTab = $hasDetails ? 'details' : ($hasUsage ? 'usage' : ($hasShipping ? 'shipping' : 'reviews')); // Set default active tab ?>

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
                <?php // Details Tab Pane ?>
                <div id="details" class="tab-pane prose max-w-none text-gray-700 <?= $activeTab === 'details' ? 'active' : '' ?>">
                    <?php if ($hasDetails): ?>
                        <h3 class="text-xl font-semibold mb-4 text-primary-dark sr-only">Product Details</h3>
                        <table class="details-table w-full text-left">
                            <tbody>
                                <?php // Requires 'size' column ?>
                                <?php if (!empty($product['size'])): ?>
                                <tr class="border-b border-gray-200"><th class="py-2 pr-4 font-medium text-gray-600 w-1/4">Size</th><td class="py-2"><?= htmlspecialchars($product['size']) ?></td></tr>
                                <?php endif; ?>
                                <?php // Requires 'scent_profile' column or join from product_attributes ?>
                                <?php if (!empty($product['scent_profile'])): ?>
                                <tr class="border-b border-gray-200"><th class="py-2 pr-4 font-medium text-gray-600 w-1/4">Scent Profile</th><td class="py-2"><?= htmlspecialchars($product['scent_profile']) ?></td></tr>
                                <?php endif; ?>
                                <?php // Requires 'origin' column ?>
                                <?php if (!empty($product['origin'])): ?>
                                <tr class="border-b border-gray-200"><th class="py-2 pr-4 font-medium text-gray-600 w-1/4">Origin</th><td class="py-2"><?= htmlspecialchars($product['origin']) ?></td></tr>
                                <?php endif; ?>
                                <?php // Requires 'sku' column ?>
                                <?php if (!empty($product['sku'])): ?>
                                <tr class="border-b border-gray-200"><th class="py-2 pr-4 font-medium text-gray-600 w-1/4">SKU</th><td class="py-2"><?= htmlspecialchars($product['sku']) ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php // Display full description here if not shown above and short desc exists ?>
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

                 <?php // Usage Tab Pane - Requires 'usage_instructions' column ?>
                <div id="usage" class="tab-pane prose max-w-none text-gray-700 <?= $activeTab === 'usage' ? 'active' : '' ?>">
                    <h3 class="text-xl font-semibold mb-4 text-primary-dark sr-only">How to Use</h3>
                    <?php if ($hasUsage): ?>
                        <?= nl2br(htmlspecialchars($product['usage_instructions'])) ?>
                    <?php else: ?>
                        <p class="text-gray-500 italic">Usage instructions are not available for this product.</p>
                    <?php endif; ?>
                </div>

                <?php // Shipping Tab Pane ?>
                <div id="shipping" class="tab-pane prose max-w-none text-gray-700 <?= $activeTab === 'shipping' ? 'active' : '' ?>">
                    <h3 class="text-xl font-semibold mb-4 text-primary-dark sr-only">Shipping Information</h3>
                    <div class="shipping-info">
                        <p><strong>Free Standard Shipping</strong> on orders over $<?= number_format(FREE_SHIPPING_THRESHOLD ?? 50, 2) ?>.</p>
                        <ul class="list-disc list-inside space-y-1 my-4">
                            <li>Standard Shipping (5-7 business days): $<?= number_format(SHIPPING_COST ?? 5.99, 2) ?></li>
                            <li>Express Shipping (2-3 business days): $12.99</li>
                            <li>Next Day Delivery (order before 2pm): $19.99</li>
                        </ul>
                        <p>We ship with care to ensure your products arrive safely. Tracking information will be provided once your order ships. International shipping available to select countries (rates calculated at checkout).</p>
                        <p class="mt-4"><a href="index.php?page=shipping" class="text-primary hover:underline">View Full Shipping Policy</a></p>
                    </div>
                </div>

                <?php // Reviews Tab Pane ?>
                <div id="reviews" class="tab-pane <?= $activeTab === 'reviews' ? 'active' : '' ?>">
                    <h3 class="text-xl font-semibold mb-4 text-primary-dark sr-only">Customer Reviews</h3>
                    <div class="reviews-summary mb-8 p-6 bg-light rounded-lg flex flex-col sm:flex-row items-center gap-6">
                        <div class="average-rating text-center">
                            <?php // TODO: Fetch actual average rating and count ?>
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
                        <!-- Reviews would be loaded dynamically here -->
                        <p class="text-center text-gray-500 italic py-4">There are no reviews for this product yet.</p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Related Products -->
        <?php // *** IMAGE PATH FIX APPLIED IN RELATED PRODUCTS LOOP *** ?>
        <?php if (!empty($relatedProducts)): ?>
            <div class="related-products mt-16 md:mt-24 border-t border-gray-200 pt-12" data-aos="fade-up">
                <h2 class="text-3xl font-bold text-center mb-12 font-heading text-primary">You May Also Like</h2>
                <div class="products-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                        <!-- Use the consistent product card style -->
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
                                 <?php if (!empty($relatedProduct['category_name'])): // Assuming JOIN provides this ?>
                                     <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($relatedProduct['category_name']) ?></p>
                                 <?php endif; ?>
                                 <p class="price text-base font-semibold text-accent mb-4 mt-auto">$<?= isset($relatedProduct['price']) ? number_format($relatedProduct['price'], 2) : 'N/A' ?></p>
                                 <div class="product-actions mt-auto">
                                     <?php // Calculate stock status for related product ?>
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

<!-- JavaScript (Ensure necessary functions are available) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Gallery Logic ---
    const mainImage = document.getElementById('mainImage');
    const thumbnails = document.querySelectorAll('.thumbnail-grid img');
    const thumbnailContainers = document.querySelectorAll('.thumbnail-grid div'); // Select container divs

    function updateMainImage(thumbnailElement) {
        if (mainImage && thumbnailElement) {
            mainImage.src = thumbnailElement.src;
            mainImage.alt = thumbnailElement.alt.replace('View', 'Main view');

            // Reset borders on all containers
            thumbnailContainers.forEach(div => {
                div.classList.remove('border-primary', 'border-2');
                div.classList.add('border'); // Ensure default border class if needed
            });
             // Apply active border to the clicked thumbnail's container
            thumbnailElement.closest('div').classList.add('border-primary', 'border-2');
            thumbnailElement.closest('div').classList.remove('border');

            // Optional: Keep active class on image itself if styles depend on it
             thumbnails.forEach(img => img.classList.remove('active'));
             thumbnailElement.classList.add('active');
        }
    }
    // Make updateMainImage globally accessible for inline onclick
    window.updateMainImage = updateMainImage;

    // --- Quantity Selector Logic ---
    const quantityInput = document.querySelector('.quantity-selector input');
    if (quantityInput) {
        const quantityMax = parseInt(quantityInput.getAttribute('max') || '99');
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                if (isNaN(value)) value = 1; // Handle non-numeric input

                if (this.classList.contains('plus')) {
                    if (value < quantityMax) quantityInput.value = value + 1;
                     else quantityInput.value = quantityMax; // Prevent exceeding max
                } else if (this.classList.contains('minus')) {
                    if (value > 1) quantityInput.value = value - 1;
                }
            });
        });
         // Validate input on change
         quantityInput.addEventListener('change', function() {
              let value = parseInt(this.value);
              if (isNaN(value) || value < 1) {
                  this.value = 1;
              } else if (value > quantityMax) {
                   this.value = quantityMax;
              }
         });
    }


    // --- Tab Switching Logic ---
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent potential page jump if buttons were links
            const tabId = this.dataset.tab;

            // Update button active states
            tabBtns.forEach(b => {
                b.classList.remove('active', 'text-primary', 'border-primary');
                b.classList.add('text-gray-500', 'border-transparent', 'hover:text-primary', 'hover:border-gray-300');
            });
            this.classList.add('active', 'text-primary', 'border-primary');
            this.classList.remove('text-gray-500', 'border-transparent', 'hover:text-primary', 'hover:border-gray-300');


            // Update pane active states
            tabPanes.forEach(pane => {
                // Use a class like 'hidden' or 'active' toggling
                // Assuming 'active' class controls visibility (e.g., via CSS display block/none)
                if (pane.id === tabId) {
                    pane.classList.add('active');
                     pane.classList.remove('hidden'); // Example if using hidden
                } else {
                    pane.classList.remove('active');
                     pane.classList.add('hidden'); // Example if using hidden
                }
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
    });

    // --- Add to Cart Form Submission (AJAX) ---
    // (Ensure the fetch code provided previously is included here)
    // ... fetch logic for #product-detail-add-cart-form ...
    const addToCartForm = document.getElementById('product-detail-add-cart-form');
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"].add-to-cart');
            if (!submitButton || submitButton.disabled) return; // Exit if no button or already submitting

            const originalButtonHtml = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...';

            fetch(this.action, { // action is index.php?page=cart&action=add
                method: 'POST',
                headers: { 'Accept': 'application/json' }, // Indicate we expect JSON back
                body: new URLSearchParams(formData) // Send as form-encoded
            })
            .then(response => {
                const contentType = response.headers.get("content-type");
                if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                }
                // Handle non-JSON or error responses
                return response.text().then(text => {
                    throw new Error(`Server error ${response.status}: ${text.substring(0, 200)}`);
                });
             })
            .then(data => {
                if (data.success) {
                    const cartCountSpan = document.querySelector('.cart-count');
                    if (cartCountSpan) {
                        cartCountSpan.textContent = data.cart_count;
                        cartCountSpan.style.display = data.cart_count > 0 ? 'inline-block' : 'none'; // Use inline-block or similar
                    }
                    showFlashMessage(data.message || 'Product added to cart!', 'success');
                     // Optionally update stock status display based on data.stock_status
                     if(data.stock_status === 'out_of_stock') {
                        submitButton.classList.remove('btn-primary');
                        submitButton.classList.add('btn-disabled');
                        submitButton.innerHTML = '<i class="fas fa-times-circle mr-2"></i> Out of Stock';
                        // Keep disabled
                     } else {
                          submitButton.innerHTML = originalButtonHtml; // Restore button text
                          submitButton.disabled = false; // Re-enable ONLY if not out of stock
                     }
                } else {
                     showFlashMessage(data.message || 'Could not add product.', 'error');
                     submitButton.innerHTML = originalButtonHtml; // Restore button text
                     submitButton.disabled = false; // Re-enable on failure
                }
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
                showFlashMessage('An error occurred. Please try again.', 'error');
                 if (submitButton) {
                    submitButton.innerHTML = originalButtonHtml; // Restore button text
                    submitButton.disabled = false; // Re-enable on error
                 }
            });
        });
    }


    // --- Related Products Add to Cart (AJAX) ---
    // (Ensure the fetch code provided previously is included here)
    // ... fetch logic for .add-to-cart-related ...
    document.querySelectorAll('.add-to-cart-related').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const csrfTokenInput = document.querySelector('input[name="csrf_token"]'); // Get token from main form
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
                 headers: { 'Accept': 'application/json' },
                body: formData
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
                    // Update button if stock runs out
                    if(data.stock_status === 'out_of_stock') {
                        this.classList.remove('btn-secondary');
                        this.classList.add('btn-disabled');
                        this.innerHTML = 'Out of Stock';
                        // Keep disabled
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

    // --- Flash Message Helper Function (Ensure this is defined) ---
    // (Ensure the function provided previously is included here or globally)
    window.showFlashMessage = function(message, type = 'info') { // Make it global
        let flashContainer = document.querySelector('.flash-message-container');
        if (!flashContainer) {
            flashContainer = document.createElement('div');
            // Apply Tailwind classes for positioning and styling
            flashContainer.className = 'flash-message-container fixed top-5 right-5 z-[1100] w-full max-w-sm space-y-2';
            document.body.appendChild(flashContainer);
        }
        const flashId = 'flash-' + Date.now() + Math.random().toString(36).substring(2); // Unique ID
        const flashDiv = document.createElement('div');
        flashDiv.id = flashId;
        const colorMap = {
            success: 'bg-green-100 border-green-400 text-green-800', // Adjusted colors for better contrast
            error: 'bg-red-100 border-red-400 text-red-800',
            info: 'bg-blue-100 border-blue-400 text-blue-800',
            warning: 'bg-yellow-100 border-yellow-400 text-yellow-800'
        };
        // Add Tailwind classes for base styling, border, padding, shadow, and transition
        flashDiv.className = `border px-4 py-3 rounded-md relative shadow-lg mb-2 ${colorMap[type] || colorMap['info']} transition-all duration-300 ease-out opacity-0 transform translate-x-4`; // Start off-screen and transparent
        flashDiv.setAttribute('role', 'alert');
        flashDiv.setAttribute('aria-live', 'polite'); // Announce message

        const messageSpan = document.createElement('span');
        messageSpan.className = 'block sm:inline';
        messageSpan.textContent = message;
        flashDiv.appendChild(messageSpan);

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'absolute top-1 right-1 p-1 text-inherit opacity-75 hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-current rounded-full'; // Subtle styling
        closeButton.setAttribute('aria-label', 'Close');
         closeButton.innerHTML = '<svg class="fill-current h-5 w-5" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>';

         // Close handler
        const closeFlash = () => {
             flashDiv.style.opacity = '0';
             flashDiv.style.transform = 'translateX(1rem)'; // Move out
             setTimeout(() => flashDiv.remove(), 300); // Remove after fade/move out
        };
        closeButton.onclick = closeFlash;
        flashDiv.appendChild(closeButton);

        flashContainer.appendChild(flashDiv);

        // Trigger fade-in and slide-in animation
        requestAnimationFrame(() => {
            flashDiv.style.opacity = '1';
            flashDiv.style.transform = 'translateX(0)';
        });

        // Auto-remove after 5 seconds
        const timeoutId = setTimeout(closeFlash, 5000);

        // Optional: Clear timeout if closed manually
         flashDiv.addEventListener('mouseenter', () => clearTimeout(timeoutId)); // Pause on hover
         flashDiv.addEventListener('mouseleave', () => setTimeout(closeFlash, 5000)); // Resume timer on leave

    }; // End of showFlashMessage

});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; // Includes closing tags, potentially AOS init ?>
```

## 4. Crucial Dependencies & Next Steps

For the redesigned product detail page to function correctly:

1.  **Database Schema:** You **must** ensure the `products` table contains the necessary columns expected by the view code. This includes `image` (corrected from `image_url`), `name`, `price`, `description`, `stock_quantity`, `low_stock_threshold`, `is_featured`, `category_id`.
    *   **Optional but Recommended:** To enable the *full* potential of the enhanced design, add the *missing* columns (`short_description`, `benefits` (JSON/TEXT), `ingredients`, `usage_instructions`, `gallery_images` (JSON/TEXT), `size`, `scent_profile`, `origin`, `sku`, `backorder_allowed`) to your `products` table as detailed in `suggestions_for_improvement_images.md` (Solution Option A).
    *   **Stock Column:** Remove the redundant `stock` column if `stock_quantity` is the authoritative source.
2.  **Controller Data:** Update `ProductController::showProduct` (and the `Product::getById` model method) to SELECT and fetch all the required columns (including any newly added ones and joining `categories` for `category_name`) and pass the complete `$product` array (and `$relatedProducts`) to the view. Handle potential JSON decoding for fields like `benefits` and `gallery_images` in the controller after fetching.
3.  **AJAX Backend:** Verify the `index.php?page=cart&action=add` endpoint (handled by `CartController::addToCart`) correctly processes the request and **returns a JSON response** indicating success/failure, message, and the updated `cart_count`. The `index.php` provided suggests this is already the case via `jsonResponse()`.
4.  **CSRF Protection:** The forms and AJAX requests in the redesigned view include a `csrf_token` field. You **must** implement the fix suggested in `suggested_improvements_and_fixes.md` to ensure a valid CSRF token is generated and outputted in the form. Without this, the AJAX requests and form submissions will fail CSRF validation.

## 5. Conclusion

By correcting the image path reference from `$product['image_url']` to `$product['image']` in your view files (`product_detail.php`, `products.php`), you will resolve the missing image issue.

Adopting the redesigned `views/product_detail.php` code provided will significantly enhance the page's layout and design, bringing it in line with the main landing page. However, realizing the full potential of this enhanced view requires ensuring the corresponding data fields exist in your database and are fetched by the controller. Addressing the dependencies, particularly fixing CSRF token handling and ensuring the controller provides the necessary data, is crucial for the redesigned page to function correctly and securely.  

---
https://drive.google.com/file/d/10gCnL8NJp79PUjHWxDtMcW4-Nj5In661/view?usp=sharing, https://drive.google.com/file/d/126KjzuTW6OQd1YXyc5oKi7XUArOEP96m/view?usp=sharing, https://drive.google.com/file/d/1BM2Pr-Q-dRs2lQtzFYIABmcqcFVllSsN/view?usp=sharing, https://drive.google.com/file/d/1Bp0-5HMlGKICNb4U_YbJ_mFD35T2YfOf/view?usp=sharing, https://drive.google.com/file/d/1FXsDOP7FCoP1cUYxDI4hEC4AXRGjQwAC/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221Tsva1prccYU-Un90emc34sB2sHhMLXja%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1XyxEK8Yb9GQZ0Ahk1P_Wf-FkfF965Omj/view?usp=sharing, https://drive.google.com/file/d/1bDNZgMUeBQNrCoO8Sr-w5Z0N0dCFDJjU/view?usp=sharing, https://drive.google.com/file/d/1eUiM9-m0SALwdiqcRWmeYkDz-17JUIoj/view?usp=sharing, https://drive.google.com/file/d/1tcI9kfjgyvoAe8xjYs0xfOxpCYYFYp0H/view?usp=sharing
