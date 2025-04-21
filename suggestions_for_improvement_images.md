After carefully reviewing the previously suggested fixes and the redesigned `views/product_detail.php` against the provided database schema in `the_scent_schema.sql`. Here's an updated validation and refinement of the suggestions:

# Product Detail Page: Schema Validation & Refined Fixes (v1.1)

## 1. Validation Summary

The previous analysis suggested fixes for the product detail page, including resolving image path issues and enhancing the layout. This document validates those suggestions against the provided `the_scent_schema.sql.txt` database schema.

**Confirmed Consistencies:**

*   **Image Path:** The fix to use `$product['image']` instead of `$product['image_url']` in `views/product_detail.php` **is consistent** with the schema, which defines an `image` column in the `products` table. This remains the correct fix for the placeholder image issue.
*   **Core Fields:** Fields like `id`, `name`, `description`, `price`, `category_id`, `is_featured`, `stock_quantity`, and `low_stock_threshold` used in the revised view exist in the `products` schema.
*   **Category Name:** The use of `$product['category_name']` is achievable via a `LEFT JOIN` from `products.category_id` to `categories.id` and selecting `categories.name`.

**Identified Inconsistencies & Missing Fields:**

Several fields used in the *enhanced* `views/product_detail.php` suggestion are **missing** from the provided `products` table schema:

1.  `short_description`: Schema only has `description`.
2.  `benefits`: Schema does not have a dedicated field for benefits (expected to be JSON/text).
3.  `ingredients`: Schema does not have this field (expected to be TEXT).
4.  `gallery_images`: Schema does not have this field (expected to be JSON/text).
5.  `size`: Schema does not have this field (expected to be VARCHAR).
6.  `scent_profile`: Schema does not have this field directly. The `product_attributes` table has `scent_type`, `mood_effect`, `intensity_level` which could be combined or used instead, requiring a JOIN.
7.  `origin`: Schema does not have this field (expected to be VARCHAR).
8.  `sku`: Schema does not have this field (expected to be VARCHAR).
9.  `usage_instructions`: Schema does not have this field (expected to be TEXT).
10. `backorder_allowed`: Schema does not have this field (expected to be TINYINT). Relied upon by the suggested view's stock logic.
11. `benefits_list`: Used in the older `product_detail.php`, seems redundant if `benefits` is added.

**Redundancy:**

*   The `products` table contains both `stock` and `stock_quantity` columns. This is redundant. Based on the view code using `stock_quantity` and `low_stock_threshold`, it's likely `stock_quantity` is the intended primary field. The `stock` column should probably be removed.

## 2. Refined Solutions

To fully implement the *enhanced* product detail page as suggested previously, the database schema and controller queries need updates.

### Solution Option A: Modify Schema (Recommended for Full Feature Set)

This approach adds the missing fields to the database for the richest product detail display.

1.  **Update Database Schema:** Execute the following `ALTER TABLE` statements (or integrate them into your schema management).

    ```sql
    -- Add missing columns to the products table
    ALTER TABLE products
      ADD COLUMN short_description TEXT COLLATE utf8mb4_unicode_ci NULL AFTER description,
      ADD COLUMN benefits JSON NULL AFTER price, -- Or TEXT if JSON type not supported/preferred
      ADD COLUMN ingredients TEXT COLLATE utf8mb4_unicode_ci NULL AFTER benefits,
      ADD COLUMN usage_instructions TEXT COLLATE utf8mb4_unicode_ci NULL AFTER ingredients,
      ADD COLUMN gallery_images JSON NULL AFTER image, -- Or TEXT
      ADD COLUMN size VARCHAR(50) COLLATE utf8mb4_unicode_ci NULL AFTER stock_quantity,
      ADD COLUMN scent_profile VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL AFTER size, -- Add if a simple text profile is preferred over joining product_attributes
      ADD COLUMN origin VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL AFTER scent_profile,
      ADD COLUMN sku VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL AFTER origin,
      ADD COLUMN backorder_allowed TINYINT(1) DEFAULT 0 NULL AFTER reorder_point; -- Added nullable for existing rows

    -- Add a unique constraint to SKU if desired
    ALTER TABLE products
      ADD CONSTRAINT sku_unique UNIQUE (sku);

    -- Remove the redundant 'stock' column if 'stock_quantity' is authoritative
    ALTER TABLE products
      DROP COLUMN stock;

    -- Optional: Add indexes for new filterable/searchable fields if needed
    -- ALTER TABLE products ADD INDEX idx_sku (sku);
    ```

2.  **Update Controller Query (`ProductController::showProduct` / `Product::getById`):** Modify the database query to select the newly added fields and perform the JOIN for `category_name`.

    ```php
    // Inside ProductController::showProduct() or Model Product::getById()
    // Conceptual Query Update:
    $stmt = $this->pdo->prepare("
        SELECT
            p.id, p.name, p.description, p.image, p.price, p.category_id,
            p.is_featured, p.created_at, p.low_stock_threshold, p.updated_at,
            p.highlight_text, p.stock_quantity,
            -- Newly added fields for the enhanced view --
            p.short_description,
            p.benefits, -- Fetched as JSON string or TEXT
            p.ingredients,
            p.usage_instructions,
            p.gallery_images, -- Fetched as JSON string or TEXT
            p.size,
            p.scent_profile, -- If added directly to products table
            p.origin,
            p.sku,
            p.backorder_allowed,
            -- Joined field --
            c.name as category_name
            -- Optionally JOIN product_attributes if not using p.scent_profile --
            -- pa.scent_type, pa.mood_effect, pa.intensity_level --
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        -- Optional JOIN for product_attributes if needed:
        -- LEFT JOIN product_attributes pa ON p.id = pa.product_id
        WHERE p.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Potentially decode JSON fields after fetching if stored as JSON
    if ($product && isset($product['benefits'])) {
        $product['benefits'] = json_decode($product['benefits'], true) ?? []; // Handle potential decode errors
    }
    if ($product && isset($product['gallery_images'])) {
        $product['gallery_images'] = json_decode($product['gallery_images'], true) ?? [];
    }

    return $product; // Return the comprehensive product data
    ```

3.  **Update View (`views/product_detail.php`):** The enhanced view provided previously should now work correctly as it expects these fields (e.g., `$product['short_description']`, `$product['benefits']`, `$product['ingredients']`, etc.). Ensure JSON fields like `benefits` and `gallery_images` are handled appropriately (e.g., checking `is_array` before looping).

### Solution Option B: Adapt View to Existing Schema (Less Ideal)

If modifying the database schema is not feasible immediately, adapt the enhanced `views/product_detail.php` to work *without* the missing fields.

1.  **No Schema Changes.**
2.  **Update Controller Query:** Only select existing fields (`id`, `name`, `description`, `image`, `price`, `category_id`, `is_featured`, `stock_quantity`, `low_stock_threshold`, etc.) and JOIN `categories` for `category_name`. Optionally JOIN `product_attributes`.
3.  **Update View (`views/product_detail.php`):**
    *   Remove sections or display placeholder text for missing data (`short_description`, `benefits`, `ingredients`, `gallery_images`, `size`, `scent_profile`, `origin`, `sku`, `usage_instructions`).
    *   Remove or adapt logic relying on `$product['backorder_allowed']`.
    *   If joining `product_attributes`, display `scent_type`, `mood_effect`, `intensity_level` instead of `scent_profile`.
    *   Example Adaptation (removing `short_description` section):
        ```php
        <!-- Remove this section if short_description column is not added -->
        <?php /* if (!empty($product['short_description'])): ?>
        <p class="text-gray-700 text-lg"><?= nl2br(htmlspecialchars($product['short_description'])) ?></p>
        <?php endif; */ ?>

        <!-- Modify stock logic if backorder_allowed is not available -->
        <?php if (isset($product['stock_quantity']) && $product['stock_quantity'] > 0): ?>
            <button type="submit" class="btn btn-primary ...">Add to Cart</button>
             <?php if (isset($product['low_stock_threshold']) && $product['stock_quantity'] <= $product['low_stock_threshold']): ?>
                 <p class="text-sm text-yellow-600 ...">Low Stock!</p>
             <?php endif; ?>
        <?php else: ?>
             <button type="button" class="btn btn-disabled ..." disabled>Out of Stock</button>
        <?php endif; ?>
        ```

## 3. Recommendation

**Option A (Modify Schema)** is strongly recommended. It allows the product detail page to be truly informative and utilize the enhanced design effectively. It brings the database structure in line with the data requirements of a modern e-commerce product display.

Regardless of the option chosen, ensure the following are addressed:

*   **Image Path:** Consistently use `$product['image']` in all relevant view files (`product_detail.php`, `products.php`, verify `home.php`).
*   **Stock Redundancy:** Decide whether `stock` or `stock_quantity` is the correct field and remove the other from the `products` table and any code referencing it. (`stock_quantity` seems preferred).
*   **Controller Queries:** Ensure the queries in `ProductController` and the `Product` model fetch all required fields for the chosen display option.
*   **AJAX Fix:** Implement the previously suggested fix for the Add-to-Cart AJAX handling (`index.php` returning JSON) as the enhanced view's JavaScript relies on it.

By aligning the database schema, controller queries, and view code, you can resolve the image issue and successfully implement the improved, visually consistent, and informative product detail page.

---
# Product Detail Page: Image and Layout Fixes (v1.0)

## 1. Problem Summary

The product detail page (e.g., for product ID 1) currently exhibits two main issues:

1.  **Missing Product Images:** It displays a generic `/images/placeholder.jpg` instead of the actual product image, despite efforts to update image paths in the database.
2.  **Suboptimal Layout & Design:** The page layout is basic, lacks visual appeal, doesn't present product information effectively (many fields are empty in the example output), and is inconsistent with the more polished design of the main landing page.

This document outlines the causes and suggests fixes to resolve the image issue and significantly enhance the layout and design of the product detail page.

## 2. Issue 1: Missing Product Images

### Analysis

1.  **Database Path Update:** The provided SQL (`modify_product_image_paths.sql`) updates a database column named `image` to a path like `/images/products/1.jpg`. This assumes the `products` table has an `image` column.
2.  **View Code Expectation:** The `views/product_detail.php` file attempts to display the main product image using the variable `$product['image_url']`:
    ```php
    <img src="<?= htmlspecialchars($product['image_url'] ?? '/images/placeholder.jpg') ?>" ... >
    ```
3.  **Related Files:** Other files like `views/products.php` and `views/home.php` (based on its code structure using `$product['image']`) might also be referencing product images. The `views/products.php` file currently uses `$product['image_url']`, while `views/home.php` uses `$product['image']`. This inconsistency needs to be addressed.
4.  **Likely Cause:** The core issue is a mismatch between the database column name (`image`) and the variable key used in the product detail view (`image_url`). Since `$product['image_url']` is likely `null` or not set based on the database query result (which fetches the `image` column), the null coalescing operator (`??`) correctly falls back to `/images/placeholder.jpg`.

### Solution

Align the PHP code with the actual database column name (`image`). This requires modifying the view files that display product images.

1.  **Modify `views/product_detail.php`:** Change `$product['image_url']` to `$product['image']`.

    ```php
    // Inside views/product_detail.php - Main Image
    <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
         alt="<?= htmlspecialchars($product['name'] ?? 'Product') ?>"
         id="mainImage">

    // Inside views/product_detail.php - Thumbnail Grid (Main Image Thumbnail)
    <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
         alt="Main view"
         class="active"
         onclick="updateMainImage(this)">

    // Inside views/product_detail.php - Related Products Loop
    <img src="<?= htmlspecialchars($relatedProduct['image'] ?? '/images/placeholder.jpg') ?>"
         alt="<?= htmlspecialchars($relatedProduct['name'] ?? 'Product') ?>">
    ```

2.  **Modify `views/products.php`:** Change `$product['image_url']` to `$product['image']` for consistency.

    ```php
    // Inside views/products.php - Products Grid Loop
    <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
         alt="<?= htmlspecialchars($product['name']) ?>">
    ```

3.  **Verify `views/home.php`:** The provided `views/home.php` code already seems to correctly use `$product['image']`. No change needed there based on the provided snippet.

    ```php
    // Inside views/home.php - Featured Products Loop (Already correct)
    <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
         alt="<?= htmlspecialchars($product['name']) ?>"
         class="w-full h-64 object-cover" loading="lazy">
    ```

### Database Consideration

*   Ensure the `image` column in the `products` table exists and contains the correct *relative* web paths to the images (e.g., `/images/products/1.jpg`, `/images/products/2.jpg`, etc.).
*   Ensure the image files themselves exist at these locations within your web root's `images/products/` directory.

## 3. Issue 2: Layout and Design Enhancement

### Analysis

The current product detail page (`view_details_product_id-1.html`) lacks the visual polish and structure of the landing page (`current_landing_page.html`). It uses basic HTML structure without leveraging Tailwind CSS effectively for layout, typography, and styling. Key information sections (Benefits, Ingredients, Usage) are present in the PHP file but appear empty in the output, indicating missing data, but the layout itself needs improvement regardless.

### Solution

Revamp `views/product_detail.php` to use Tailwind CSS classes extensively, mirroring the design language of the landing page. This involves restructuring the HTML, applying utility classes for layout, spacing, typography, colors, and incorporating elements like improved tabs and styled related product cards.

### Code Snippet: Revised `views/product_detail.php`

This revised version aims for a modern, informative, and visually appealing layout consistent with the landing page.

```php
<?php
// Ensure BaseController or similar provides $this->getCsrfToken() or make $_SESSION['csrf_token'] available directly
$csrfToken = $_SESSION['csrf_token'] ?? '';
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
                <?php if (!empty($product['category_name'])): ?>
                <li class="flex items-center">
                    <a href="index.php?page=products&category=<?= urlencode($product['category_name']) ?>" class="hover:text-primary">
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
                    <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
                         alt="<?= htmlspecialchars($product['name'] ?? 'Product') ?>"
                         id="mainImage" class="w-full h-full object-cover transition-transform duration-300 ease-in-out hover:scale-105">
                    <?php if (!empty($product['is_featured'])): ?>
                        <span class="absolute top-3 left-3 bg-accent text-white text-xs font-semibold px-3 py-1 rounded-full shadow">Featured</span>
                    <?php endif; ?>
                     <?php if (isset($product['stock_quantity']) && $product['stock_quantity'] <= 0 && empty($product['backorder_allowed'])): ?>
                         <span class="absolute top-3 right-3 bg-red-600 text-white text-xs font-semibold px-3 py-1 rounded-full shadow">Out of Stock</span>
                    <?php elseif (isset($product['low_stock_threshold']) && isset($product['stock_quantity']) && $product['stock_quantity'] <= $product['low_stock_threshold']): ?>
                         <span class="absolute top-3 right-3 bg-yellow-500 text-white text-xs font-semibold px-3 py-1 rounded-full shadow">Low Stock</span>
                    <?php endif; ?>
                </div>
                <?php
                    // Attempt to decode gallery images - assumes JSON array string or null
                    $galleryImages = json_decode($product['gallery_images'] ?? '[]', true);
                ?>
                <?php if (!empty($galleryImages) && is_array($galleryImages)): ?>
                    <div class="thumbnail-grid grid grid-cols-4 gap-3">
                        <!-- Thumbnail for Main Image -->
                        <div class="border-2 border-primary rounded overflow-hidden cursor-pointer aspect-square">
                             <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
                                  alt="View 1"
                                  class="w-full h-full object-cover active"
                                  onclick="updateMainImage(this)">
                         </div>
                        <!-- Thumbnails for Gallery Images -->
                        <?php foreach ($galleryImages as $index => $imagePath): ?>
                            <?php if (!empty($imagePath)): ?>
                            <div class="border rounded overflow-hidden cursor-pointer aspect-square">
                                <img src="<?= htmlspecialchars($imagePath) ?>"
                                     alt="View <?= $index + 2 ?>"
                                     class="w-full h-full object-cover"
                                     onclick="updateMainImage(this)">
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div class="product-info space-y-6" data-aos="fade-left">
                <h1 class="text-3xl md:text-4xl font-bold font-heading text-primary"><?= htmlspecialchars($product['name'] ?? 'Product') ?></h1>

                <p class="text-2xl font-semibold text-accent font-accent">$<?= isset($product['price']) ? number_format($product['price'], 2) : 'N/A' ?></p>

                <!-- Short Description -->
                <?php if (!empty($product['short_description'])): ?>
                <p class="text-gray-700 text-lg"><?= nl2br(htmlspecialchars($product['short_description'])) ?></p>
                <?php endif; ?>

                <!-- Full Description (if different from short) -->
                 <?php if (!empty($product['description']) && $product['description'] !== ($product['short_description'] ?? '')): ?>
                 <div class="prose max-w-none text-gray-600">
                    <?= nl2br(htmlspecialchars($product['description'])) ?>
                 </div>
                 <?php endif; ?>


                <!-- Benefits (Example Display) -->
                <?php $benefits = json_decode($product['benefits'] ?? '[]', true); ?>
                <?php if (!empty($benefits) && is_array($benefits)): ?>
                <div class="benefits border-t pt-4">
                    <h3 class="text-lg font-semibold mb-3 text-primary-dark">Benefits</h3>
                    <ul class="list-disc list-inside space-y-1 text-gray-600">
                        <?php foreach ($benefits as $benefit): ?>
                            <li><i class="fas fa-check-circle text-secondary mr-2"></i><?= htmlspecialchars($benefit) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Ingredients (Example Display) -->
                 <?php if (!empty($product['ingredients'])): ?>
                 <div class="ingredients border-t pt-4">
                     <h3 class="text-lg font-semibold mb-3 text-primary-dark">Key Ingredients</h3>
                     <p class="text-gray-600"><?= htmlspecialchars($product['ingredients']) ?></p>
                 </div>
                 <?php endif; ?>


                <!-- Add to Cart Form -->
                <form class="add-to-cart-form space-y-4 border-t pt-6" action="index.php?page=cart&action=add" method="POST" id="product-detail-add-cart-form">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?? '' ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="flex items-center space-x-4">
                        <label for="quantity" class="font-semibold">Quantity:</label>
                        <div class="quantity-selector flex items-center border rounded">
                            <button type="button" class="quantity-btn minus w-10 h-10 text-lg text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out">-</button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= $product['stock_quantity'] ?? 99 ?>" class="w-16 text-center border-l border-r focus:outline-none focus:ring-1 focus:ring-primary">
                            <button type="button" class="quantity-btn plus w-10 h-10 text-lg text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out">+</button>
                        </div>
                    </div>

                    <?php if (isset($product['stock_quantity']) && $product['stock_quantity'] > 0): ?>
                        <button type="submit" class="btn btn-primary w-full py-3 text-lg add-to-cart">
                            <i class="fas fa-shopping-cart mr-2"></i> Add to Cart
                        </button>
                        <?php if (isset($product['low_stock_threshold']) && $product['stock_quantity'] <= $product['low_stock_threshold']): ?>
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
            <div class="tabs-header border-b mb-8 flex space-x-8">
                <button class="tab-btn py-3 px-1 border-b-2 border-transparent text-lg font-medium text-gray-500 hover:text-primary hover:border-primary focus:outline-none active" data-tab="details">Details</button>
                <button class="tab-btn py-3 px-1 border-b-2 border-transparent text-lg font-medium text-gray-500 hover:text-primary hover:border-primary focus:outline-none" data-tab="usage">How to Use</button>
                <button class="tab-btn py-3 px-1 border-b-2 border-transparent text-lg font-medium text-gray-500 hover:text-primary hover:border-primary focus:outline-none" data-tab="shipping">Shipping</button>
                <button class="tab-btn py-3 px-1 border-b-2 border-transparent text-lg font-medium text-gray-500 hover:text-primary hover:border-primary focus:outline-none" data-tab="reviews">Reviews</button>
            </div>

            <div class="tab-content">
                <div id="details" class="tab-pane active prose max-w-none">
                    <h3 class="text-xl font-semibold mb-4 text-primary-dark">Product Details</h3>
                    <table class="details-table w-full text-left text-gray-600">
                        <tbody>
                            <?php if (!empty($product['size'])): ?>
                            <tr class="border-b"><th class="py-2 pr-4 font-medium">Size</th><td class="py-2"><?= htmlspecialchars($product['size']) ?></td></tr>
                            <?php endif; ?>
                             <?php if (!empty($product['scent_profile'])): ?>
                            <tr class="border-b"><th class="py-2 pr-4 font-medium">Scent Profile</th><td class="py-2"><?= htmlspecialchars($product['scent_profile']) ?></td></tr>
                             <?php endif; ?>
                             <?php if (!empty($product['benefits_list'])): ?>
                            <tr class="border-b"><th class="py-2 pr-4 font-medium">Key Benefits</th><td class="py-2"><?= htmlspecialchars($product['benefits_list']) ?></td></tr>
                             <?php endif; ?>
                            <?php if (!empty($product['origin'])): ?>
                            <tr class="border-b"><th class="py-2 pr-4 font-medium">Origin</th><td class="py-2"><?= htmlspecialchars($product['origin']) ?></td></tr>
                             <?php endif; ?>
                             <?php if (!empty($product['sku'])): ?>
                             <tr class="border-b"><th class="py-2 pr-4 font-medium">SKU</th><td class="py-2"><?= htmlspecialchars($product['sku']) ?></td></tr>
                             <?php endif; ?>
                        </tbody>
                    </table>
                    <!-- Display full description here if not shown above -->
                     <?php if (empty($product['short_description']) && !empty($product['description'])): ?>
                     <div class="mt-6">
                        <?= nl2br(htmlspecialchars($product['description'])) ?>
                     </div>
                     <?php endif; ?>
                </div>

                <div id="usage" class="tab-pane prose max-w-none">
                    <h3 class="text-xl font-semibold mb-4 text-primary-dark">How to Use</h3>
                    <?php if (!empty($product['usage_instructions'])): ?>
                        <?= nl2br(htmlspecialchars($product['usage_instructions'])) ?>
                    <?php else: ?>
                        <p>Usage instructions are not available for this product.</p>
                    <?php endif; ?>
                </div>

                <div id="shipping" class="tab-pane prose max-w-none">
                    <h3 class="text-xl font-semibold mb-4 text-primary-dark">Shipping Information</h3>
                    <div class="shipping-info">
                        <p><strong>Free Standard Shipping</strong> on orders over $50.</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Standard Shipping (5-7 business days): $<?= number_format(SHIPPING_COST, 2) ?></li>
                            <li>Express Shipping (2-3 business days): $12.99</li>
                            <li>Next Day Delivery (order before 2pm): $19.99</li>
                        </ul>
                        <p>We ship with care to ensure your products arrive safely. International shipping available to select countries (calculated at checkout).</p>
                    </div>
                </div>

                <div id="reviews" class="tab-pane">
                    <h3 class="text-xl font-semibold mb-4 text-primary-dark">Customer Reviews</h3>
                    <div class="reviews-summary mb-8 p-6 bg-light rounded-lg flex items-center space-x-6">
                        <div class="average-rating text-center">
                            <span class="block text-4xl font-bold text-accent">4.8</span>
                             <div class="stars text-yellow-400 text-xl my-1">
                                 <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                             </div>
                             <span class="text-sm text-gray-600">Based on 24 reviews</span>
                        </div>
                        <div class="flex-grow">
                            <button class="btn btn-secondary">Write a Review</button>
                            <!-- Placeholder for review distribution bars -->
                        </div>
                    </div>
                    <div class="reviews-list space-y-6">
                        <!-- Reviews would be loaded dynamically here -->
                        <p class="text-center text-gray-500">Be the first to review this product!</p>
                        <!-- Example review structure:
                        <div class="review border-b pb-4">
                            <div class="stars text-yellow-400 mb-1">★★★★★</div>
                            <h4 class="font-semibold">Amazing scent!</h4>
                            <p class="text-gray-600 text-sm mb-2">Posted by Jane D. on March 15, 2025</p>
                            <p class="text-gray-700">This oil is incredibly relaxing. Perfect for my diffuser before bed.</p>
                        </div>
                        -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
            <div class="related-products mt-16 md:mt-24 border-t pt-12" data-aos="fade-up">
                <h2 class="text-3xl font-bold text-center mb-12 font-heading text-primary">You May Also Like</h2>
                <div class="products-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                        <!-- Use the consistent product card style from home page -->
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
                                     <?php if (isset($relatedProduct['stock_quantity']) && $relatedProduct['stock_quantity'] > 0): ?>
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

<!-- Keep existing JS block, ensure selectors still match -->
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
                img.parentElement.classList.remove('border-primary'); // Target parent div for border
                img.parentElement.classList.add('border');
                img.classList.remove('active'); // Keep active class on img if needed elsewhere
            });
            thumbnailElement.parentElement.classList.add('border-primary');
            thumbnailElement.parentElement.classList.remove('border');
             thumbnailElement.classList.add('active');
        }
    }
    // Make updateMainImage globally accessible if called via onclick attribute
    window.updateMainImage = updateMainImage;

    // --- Quantity Selector Logic ---
    const quantityInput = document.querySelector('.quantity-selector input');
    const quantityMax = parseInt(quantityInput?.getAttribute('max') || '99');
    document.querySelectorAll('.quantity-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!quantityInput) return;
            let value = parseInt(quantityInput.value);
            if (this.classList.contains('plus')) {
                if (value < quantityMax) quantityInput.value = value + 1;
            } else if (this.classList.contains('minus')) {
                if (value > 1) quantityInput.value = value - 1;
            }
        });
    });

    // --- Tab Switching Logic ---
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            // Update button active states
            tabBtns.forEach(b => b.classList.remove('active', 'text-primary', 'border-primary'));
            tabBtns.forEach(b => b.classList.add('text-gray-500', 'border-transparent')); // Reset all buttons
            this.classList.add('active', 'text-primary', 'border-primary'); // Activate clicked button
            this.classList.remove('text-gray-500', 'border-transparent');

            // Update pane active states
            tabPanes.forEach(pane => pane.classList.remove('active'));
            const activePane = document.getElementById(tabId);
            if(activePane) activePane.classList.add('active');
        });
    });

    // --- Add to Cart Form Submission (AJAX) ---
    const addToCartForm = document.getElementById('product-detail-add-cart-form');
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true; // Prevent double-clicks
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...';

            fetch(this.action, {
                method: 'POST',
                body: new URLSearchParams(formData) // Send as form-encoded
            })
            .then(response => {
                // Check if response is JSON before parsing
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else {
                    // If not JSON, likely the redirect is still happening or an error page
                    return response.text().then(text => {
                        throw new Error("Received non-JSON response: " + text.substring(0, 200));
                    });
                }
             })
            .then(data => {
                if (data.success) {
                    // Update cart count in header
                    const cartCountSpan = document.querySelector('.cart-count');
                    if (cartCountSpan) {
                        cartCountSpan.textContent = data.cart_count;
                        cartCountSpan.style.display = data.cart_count > 0 ? 'inline' : 'none';
                    }
                    // Use the standardized flash message function
                    showFlashMessage(data.message || 'Product added to cart!', 'success');
                    // Optionally: Update stock status display if needed based on data.stock_status
                } else {
                     showFlashMessage(data.message || 'Could not add product.', 'error');
                }
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
                showFlashMessage('An error occurred while adding the product. Please try again.', 'error');
            })
            .finally(() => {
                 // Re-enable button
                 submitButton.disabled = false;
                 submitButton.innerHTML = '<i class="fas fa-shopping-cart mr-2"></i> Add to Cart';
            });
        });
    }


    // --- Related Products Add to Cart (AJAX) ---
    document.querySelectorAll('.add-to-cart-related').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const csrfTokenInput = document.querySelector('input[name="csrf_token"]'); // Get token from main form
            const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';

            if (!csrfToken) {
                showFlashMessage('Security token not found. Please refresh.', 'error');
                return;
            }

            const originalButtonText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const formData = new URLSearchParams();
            formData.append('product_id', productId);
            formData.append('quantity', '1'); // Add quantity 1 for related products
            formData.append('csrf_token', csrfToken);

            fetch('index.php?page=cart&action=add', { // Point to the correct endpoint
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            })
            .then(response => {
                // Similar JSON check as above
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
                         cartCountSpan.style.display = data.cart_count > 0 ? 'inline' : 'none';
                    }
                    showFlashMessage(data.message || 'Product added to cart!', 'success');
                } else {
                     showFlashMessage(data.message || 'Could not add product.', 'error');
                }
            })
            .catch(error => {
                console.error('Error adding related product:', error);
                showFlashMessage('An error occurred. Please try again.', 'error');
            })
             .finally(() => {
                 // Re-enable button
                 this.disabled = false;
                 this.innerHTML = originalButtonText;
             });
        });
    });

    // --- Flash Message Helper Function (Ensure this is loaded, potentially from a global JS file) ---
    function showFlashMessage(message, type = 'info') {
        let flashContainer = document.querySelector('.flash-message-container');
        if (!flashContainer) {
            flashContainer = document.createElement('div');
            flashContainer.className = 'flash-message-container fixed top-5 right-5 z-[1100] max-w-sm w-full'; // Added max-w etc.
            document.body.appendChild(flashContainer);
        }
        const flashDiv = document.createElement('div');
        const colorMap = {
            success: 'bg-green-100 border-green-400 text-green-700',
            error: 'bg-red-100 border-red-400 text-red-700',
            info: 'bg-blue-100 border-blue-400 text-blue-700',
            warning: 'bg-yellow-100 border-yellow-400 text-yellow-700'
        };
        flashDiv.className = `border px-4 py-3 rounded relative shadow-md mb-2 ${colorMap[type] || colorMap['info']} transition-opacity duration-300 ease-out opacity-0`; // Start hidden
        flashDiv.setAttribute('role', 'alert');

        const messageSpan = document.createElement('span');
        messageSpan.className = 'block sm:inline';
        messageSpan.textContent = message;
        flashDiv.appendChild(messageSpan);

        const closeButton = document.createElement('button'); // Use button for accessibility
        closeButton.type = 'button';
        closeButton.className = 'absolute top-0 bottom-0 right-0 px-4 py-3 text-inherit'; // Inherit color
        closeButton.setAttribute('aria-label', 'Close');
        closeButton.innerHTML = '<svg class="fill-current h-6 w-6" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>';
        closeButton.onclick = () => {
             flashDiv.style.opacity = '0';
             setTimeout(() => flashDiv.remove(), 300); // Remove after fade out
        };
        flashDiv.appendChild(closeButton);

        flashContainer.appendChild(flashDiv);

        // Fade in
        requestAnimationFrame(() => {
           flashDiv.style.opacity = '1';
        });

        // Auto-remove after 5 seconds
        setTimeout(() => {
             if (flashDiv && flashDiv.parentElement) { // Check if still exists
                 flashDiv.style.opacity = '0';
                 setTimeout(() => flashDiv.remove(), 300);
             }
        }, 5000);
    }
});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; // Includes closing tags, scripts like AOS init ?>

```

### Controller/Model Data Requirement

*   For the redesigned page to display fully, ensure the `ProductController::showProduct` method fetches and passes all necessary fields from the `products` table (or related tables) to the `$product` variable. This includes:
    *   `name`, `price`, `image`, `description`, `short_description`
    *   `category_name` (via JOIN)
    *   `benefits` (potentially JSON string)
    *   `ingredients` (text)
    *   `usage_instructions` (text)
    *   `size`, `scent_profile`, `origin`, `sku` (text/varchar)
    *   `gallery_images` (potentially JSON string containing an array of image paths)
    *   `stock_quantity`, `low_stock_threshold`, `backorder_allowed`
    *   `is_featured`
*   Update the `Product` model (`getById`, `getRelated`) methods if necessary to fetch these fields.

## 4. Conclusion

Fixing the image display requires a simple alignment between the database column name (`image`) and the PHP variable key used in the views (`$product['image']`). The layout enhancement involves replacing the existing `views/product_detail.php` content with the provided revised code, which utilizes Tailwind CSS for a modern, informative, and visually consistent presentation, matching the landing page's style.

Remember to populate the relevant database fields for the best results on the redesigned product detail page. Crucially, ensure the "Add to Cart" backend logic in `index.php` and `CartController` returns JSON as discussed in the previous analysis (`suggested_improvements_and_fixes.md`) for the AJAX functionality in the redesigned view to work correctly.
```

https://drive.google.com/file/d/1-WiLh5Zg2Mu0phUt5kYQqcFeEhVbpt6L/view?usp=sharing, https://drive.google.com/file/d/1AsS95XaSWY6WBf_jV9iW6bCHWpV7kp60/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221B3TJ1fceeZ-8JA4RDJFKQfOIAMmSZu3Y%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1DOZxEVVkEqGWgTuFu7vwc0tj6nghwQeo/view?usp=sharing, https://drive.google.com/file/d/1QxN9ViAFcryjDOKPnaabfOOL4VT2K07B/view?usp=sharing, https://drive.google.com/file/d/1nHO65WEA4y_lat7DFNFqjMli8_XTgBtT/view?usp=sharing, https://drive.google.com/file/d/1oUwfoXJkaLQgMDE7D8Bt8Ojm7NGGZVre/view?usp=sharing, https://drive.google.com/file/d/1ps_dzhh_IOZDfLhRiGpCJfiQR3p6Qrib/view?usp=sharing, https://drive.google.com/file/d/1vZ-b9E3ccEsuILfc619PbsrdVpvyZtaQ/view?usp=sharing
