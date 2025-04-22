Let's analyze the project files and address the identified issues.

1.  **Analyze Project State:**
    *   Architecture: Custom PHP, MVC-inspired (Controllers, Models, Views), front controller (`index.php`), Apache rewrite (`.htaccess`), PDO for DB, Tailwind CSS (CDN), vanilla JS + libraries (AOS, Particles).
    *   Core Features: Product listing, product detail, cart (session-based), AJAX add-to-cart/cart updates/newsletter, basic auth, quiz.
    *   Database: Schema seems reasonably defined in `the_scent_schema.sql.txt`, includes products, categories, users, orders, etc. Note the `product_attributes` and `inventory_movements` tables. `products` table has necessary columns like `name`, `description`, `image`, `price`, `category_id`, `stock_quantity`.
    *   Code Structure: Matches `PHP_e-commerce_project_file_structure.md`. Key files like `index.php`, `BaseController.php`, `ProductController.php`, `CartController.php`, `Product.php`, `views/layout/header.php`, `views/layout/footer.php`, `views/products.php`, `views/home.php`, `views/product_detail.php` are present.
    *   Known Issues (from TDS & Prompt): CSRF token implementation failure, potential CSP issues (`unsafe-inline`), rate limiting ambiguity, UI inconsistency on `products.php`.
    *   Specific Issues to Address:
        *   UI of `shop_products.html` (output of `views/products.php`) is inconsistent.
        *   "Add to Cart" fails with "Error adding to cart" message (client-side) and 400 Bad Request (server-side logs).

2.  **Investigate Issue 1: Messy Shop Page UI (`shop_products.html`)**
    *   Compare `shop_products.html` structure with `current_landing_page.html` (homepage featured products) and `view_details_product_id-1.html` (related products section).
    *   Homepage (`views/home.php` / `current_landing_page.html`): Uses `<div class="product-card sample-card">` with inline styles for border-radius/shadow, an `<img>` tag, and a `div.product-info` containing `h3`, `p` (category), and `div.product-actions` with buttons. Uses Tailwind implicitly via CDN.
    *   Product Detail (`views/product_detail.php` / `view_details_product_id-1.html` related products): Uses `<div class="product-card sample-card bg-white rounded-lg shadow-md ...">`, `div.product-image > a > img`, `div.product-info > h3 > a`, `p.text-sm.text-gray-500` (category), `p.price`, `div.product-actions` with buttons. Leverages Tailwind classes heavily.
    *   Shop Page (`views/products.php` / `shop_products.html`):
        *   Outer grid: `<div class="products-grid">`
        *   Card structure: `<div class="product-card">` -> `div.product-image > a > img` -> `div.product-info > h3 > a`, `p.product-category`, `p.product-price`, `div.product-actions > a.btn-secondary`, `button.btn-primary.add-to-cart`.
    *   Analysis: The structure in `views/products.php` is *different* from the homepage and the (improved) product detail page's related products. It uses custom classes like `product-category`, `product-price` which might not be styled as richly as the Tailwind utility classes used elsewhere. It lacks the `sample-card`, `bg-white`, `rounded-lg`, `shadow-md`, `overflow-hidden`, `transition-shadow`, `hover:shadow-xl` classes seen in the improved product detail view, which contribute significantly to the appearance.
    *   Solution: Modify `views/products.php` to use a consistent card structure and Tailwind classes, similar to the related products section in the *improved* `views/product_detail.php`.

3.  **Investigate Issue 2: Failing "Add to Cart" (400 Error)**
    *   Logs confirm `POST /index.php?page=cart&action=add` results in `HTTP 400`.
    *   Examine the JS handler in `views/layout/footer.php` (canonical handler).
        *   It correctly selects the button (`.add-to-cart`).
        *   It extracts `productId` from `dataset.productId`.
        *   It constructs the fetch body: `product_id=${encodeURIComponent(productId)}&quantity=1&csrf_token=${encodeURIComponent(csrfToken)}`.
        *   Crucially, it tries to get `csrfToken` from `document.querySelector('input[name="csrf_token"]')`.
    *   Examine the HTML outputs (`current_landing_page.html`, `view_details_product_id-1.html`, `shop_products.html`). Search for `<input type="hidden" name="csrf_token" ...>`.
        *   `current_landing_page.html`: Contains `<input type="hidden" name="csrf_token" value="fe354afd60de2027f1f1335263371be220166f5f7408fffc2b14d27856f6c6a6">` inside the newsletter form. *This token might be picked up by the JS*, but it's associated with the newsletter form, not necessarily globally available or intended for the cart action. There isn't one near the product cards.
        *   `view_details_product_id-1.html`: Contains `<input type="hidden" name="csrf_token" value="739c29932f3a17cae34b37adcba2b0255e3bddd93e7a3202dc7128626a105491">` inside the main add-to-cart form (`#product-detail-add-cart-form`). This is correct for the main button *within that form*. However, the *related products* add-to-cart buttons (`.add-to-cart-related`) outside this form would *also* rely on finding *some* CSRF token on the page. The current JS for related products *does* attempt to find *a* token: `const csrfTokenInput = document.querySelector('input[name="csrf_token"]');`. This would likely find the token from the main form.
        *   `shop_products.html`: *Does not contain any* `<input type="hidden" name="csrf_token" ...>` anywhere.
    *   Analyze `CartController::addToCart()`:
        *   Calls `validateAjax()`. Should pass for `fetch`.
        *   Calls `validateCSRF()`. This is the key suspect. `BaseController::validateCSRF()` likely calls `SecurityMiddleware::validateCSRFToken()` (or similar). This method probably checks `$_POST['csrf_token']` against `$_SESSION['csrf_token']`.
        *   If the JS finds *no* CSRF token (like on `shop_products.html`), `csrfToken` variable in JS will be null/undefined. The fetch body will be `product_id=X&quantity=1&csrf_token=null`. The server-side `$_POST['csrf_token']` will be missing or literally "null", causing validation to fail.
        *   If the JS *does* find a token (like on the homepage or product detail page, potentially picking up a token from a different form like newsletter), but that token doesn't match the one stored in the *session* for the current user, validation will also fail.
        *   After CSRF check, it validates `product_id` and `quantity`. A missing `product_id` in POST would also cause a failure (likely the "Invalid product or quantity" message, leading to a 400). Let's re-check the JS body construction: `product_id=${encodeURIComponent(productId)}...`. This looks correct. The `productId` comes from `data-product-id`, which *is* present on the buttons in the HTML. So, unless the JS fails to extract it, the `product_id` should be sent.
        *   Stock check: If CSRF *passed* but stock was insufficient, it *should* return a JSON response with `success: false` and `message: 'Insufficient stock'`, potentially still with a 400 status but a different message structure than just "Error adding to cart". The generic error message suggests the failure happens earlier, likely CSRF.
    *   Conclusion: The root cause is the **failure to consistently output a valid CSRF token on pages with Add-to-Cart buttons** and ensure the **JavaScript handler correctly reads and sends this token**. The 400 Bad Request stems from the server-side CSRF validation failing because the token is missing or mismatched in the POST request.

4.  **Plan Fixes:**
    *   **CSRF:**
        *   Modify `BaseController` or relevant controllers (`ProductController`, `CartController`) to ensure `$csrfToken = $this->getCsrfToken();` is generated/retrieved *before* rendering views that need it (`home.php`, `products.php`, `product_detail.php`).
        *   Pass the `$csrfToken` variable to these views.
        *   In `views/home.php`, `views/products.php`, `views/product_detail.php`, add a *single, reliable* hidden CSRF input field, ideally within the main content area or near the elements the JS interacts with. A good place might be just inside the `<main>` tag or within the main section container. Example: `<input type="hidden" name="csrf_token" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">`. Use an ID for easy selection.
        *   Modify the *global* Add-to-Cart JS handler in `footer.php` to reliably select this specific token: `const csrfToken = document.getElementById('csrf-token-value')?.value;`. Add a check: `if (!csrfToken) { showFlashMessage('Security token missing. Please refresh.', 'error'); return; }`. Ensure the token is correctly appended to the fetch body.
    *   **Shop Page UI:**
        *   Modify the loop in `views/products.php`. Replace the existing `<div class="product-card">...</div>` structure with one based on the related products section in `views/product_detail.php`. Use Tailwind classes for styling (`bg-white`, `rounded-lg`, `shadow-md`, `overflow-hidden`, `transition-shadow`, `hover:shadow-xl`, etc.). Ensure consistency in displaying image, name, category, price, and action buttons. Include stock checking for the button state.

5.  **Generate Markdown:** Structure the findings and solutions logically in the requested `suggested_improvements_and_fixes.md` format. Include detailed code snippets for the fixes.

6.  **Final Review:** Read through the generated Markdown file. Ensure it accurately reflects the analysis, clearly explains the issues and solutions, and provides actionable code examples. Check for consistency and clarity. Ensure it directly addresses the two specific issues raised in the prompt.```markdown
# Suggested Improvements and Fixes for The Scent E-commerce Platform

**Document Version:** 1.0
**Date:** 2025-04-22

## 1. Project Overview & Current State

**The Scent** is a custom-built PHP e-commerce platform designed for selling aromatherapy products. It employs an MVC-inspired architecture without a formal framework, relying on a front controller (`index.php`), controllers (`controllers/`), models (`models/`), and PHP-based views (`views/`). Key technologies include PHP, MySQL, Apache (`.htaccess` for routing), Tailwind CSS (via CDN), and JavaScript libraries like AOS.js and Particles.js for frontend effects.

**Core functionalities observed:**
*   Product browsing (Homepage featured, Product Listing, Product Detail).
*   Session-based shopping cart with AJAX updates (Add, Update, Remove).
*   User Authentication (Login/Register - basic structure present).
*   Scent Finder Quiz.
*   Newsletter Subscription (AJAX).

**Code Analysis indicates:**
*   Modular structure with controllers extending a `BaseController`.
*   PDO used for database interaction, often within Model classes (`Product.php`).
*   Session management is handled, including cart data (`$_SESSION['cart']`).
*   Security measures like input validation (`SecurityMiddleware.php`, `BaseController`) and prepared statements are used.
*   AJAX is used for enhancing user experience in the cart and newsletter signup.
*   Significant reliance on JavaScript within `views/layout/footer.php` for global event handling (Add-to-Cart, Newsletter).

**Known Issues (from previous analysis/TDS & current prompt):**
*   **CSRF Protection:** Intended but incorrectly implemented (tokens not consistently outputted/sent). This is identified as critical.
*   **UI Inconsistencies:** Styling differences exist between pages (specifically the product listing page).
*   **Add-to-Cart Failure:** Users experience errors, and server logs show `400 Bad Request` errors for the cart addition endpoint.

This document focuses on addressing the two specific issues raised: the inconsistent UI of the Product Listing page (`shop_products.html`) and the failing "Add to Cart" functionality.

## 2. Issue 1: Product Listing Page UI Inconsistency

**Problem:**
The product listing page (`index.php?page=products`, rendered by `views/products.php`, resulting in `shop_products.html`) displays product cards that are visually inconsistent with the cards shown on the homepage (`current_landing_page.html`) and in the "Related Products" section of the product detail page (`view_details_product_id-1.html`). The cards on the shop page appear less styled, lacking the rounded corners, shadows, and refined layout seen elsewhere.

**Analysis:**
Comparing `views/products.php` with `views/home.php` and `views/product_detail.php` reveals different HTML structures and CSS classes used for the product cards:
*   `views/products.php`: Uses a basic `<div class="product-card">` with custom classes like `product-category` and `product-price`. It lacks the more descriptive Tailwind utility classes for background, padding, shadow, rounded corners, and hover effects used in other views.
*   `views/home.php` & `views/product_detail.php` (Related Products): Utilize Tailwind classes extensively (e.g., `bg-white`, `rounded-lg`, `shadow-md`, `overflow-hidden`, `transition-shadow`, `hover:shadow-xl`) applied to a structure like `<div class="product-card sample-card ...">`, resulting in a more polished appearance.

**Suggestion:**
Refactor the product card loop within `views/products.php` to adopt the more modern and consistent structure and styling used in the related products section of `views/product_detail.php`. This involves using Tailwind utility classes directly.

**Code Fix (`views/products.php`):**

Replace the `products-grid` loop:

```php
<?php // Existing code before the loop... ?>

<?php if (empty($products)): ?>
    <div class="no-products" data-aos="fade-up">
        <i class="fas fa-search"></i>
        <p>No products found matching your criteria.</p>
        <a href="index.php?page=products" class="btn-primary">View All Products</a>
    </div>
<?php else: ?>
    <div class="products-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8"> <?php // Adjusted grid columns for consistency ?>
        <?php foreach ($products as $index => $product): ?>
            <?php
                // Determine stock status for button logic
                $isOutOfStock = (!isset($product['stock_quantity']) || $product['stock_quantity'] <= 0) && empty($product['backorder_allowed']);
                $isLowStock = !$isOutOfStock && isset($product['low_stock_threshold']) && isset($product['stock_quantity']) && $product['stock_quantity'] <= $product['low_stock_threshold'];
            ?>
            <div class="product-card sample-card bg-white rounded-lg shadow-md overflow-hidden transition-shadow duration-300 hover:shadow-xl flex flex-col"
                 data-aos="fade-up" data-aos-delay="<?= ($index % 4) * 100 ?>"> <?php // Use modulo for delay reset per row ?>

                <div class="product-image relative h-64 overflow-hidden">
                    <a href="index.php?page=product&id=<?= $product['id'] ?? '' ?>">
                        <?php // Use 'image' field based on schema/detail view, default to placeholder ?>
                        <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
                             alt="<?= htmlspecialchars($product['name'] ?? 'Product') ?>" class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                    </a>
                    <?php if (!empty($product['is_featured']) || !empty($product['featured'])): // Check both potential field names ?>
                        <span class="absolute top-2 left-2 bg-accent text-white text-xs font-semibold px-2 py-0.5 rounded-full shadow">Featured</span>
                    <?php endif; ?>
                    <?php if ($isOutOfStock): ?>
                        <span class="absolute top-2 right-2 bg-red-600 text-white text-xs font-semibold px-3 py-1 rounded-full shadow">Out of Stock</span>
                    <?php elseif ($isLowStock): ?>
                         <span class="absolute top-2 right-2 bg-yellow-500 text-white text-xs font-semibold px-3 py-1 rounded-full shadow">Low Stock</span>
                    <?php endif; ?>
                     <?php if (!empty($product['highlight_text'])): ?>
                         <span class="absolute bottom-2 left-2 bg-primary text-white text-xs font-semibold px-2 py-0.5 rounded-full shadow"><?= htmlspecialchars($product['highlight_text']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="product-info p-4 flex flex-col flex-grow text-center">
                    <h3 class="text-lg font-semibold mb-1 font-heading text-primary hover:text-accent">
                        <a href="index.php?page=product&id=<?= $product['id'] ?? '' ?>">
                            <?= htmlspecialchars($product['name'] ?? 'Product Name') ?>
                        </a>
                    </h3>
                    <?php if (!empty($product['category_name'])): ?>
                        <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($product['category_name']) ?></p>
                    <?php endif; ?>
                     <?php if (!empty($product['short_description'])): ?>
                        <p class="text-xs text-gray-600 mb-3 flex-grow"><?= htmlspecialchars($product['short_description']) ?></p>
                    <?php endif; ?>
                    <p class="price text-base font-semibold text-accent mb-4 mt-auto">$<?= isset($product['price']) ? number_format($product['price'], 2) : 'N/A' ?></p>

                    <div class="product-actions mt-auto space-y-2">
                        <a href="index.php?page=product&id=<?= $product['id'] ?? '' ?>" class="btn btn-primary w-full block">View Details</a>
                        <?php if (!$isOutOfStock): ?>
                            <button class="btn btn-secondary add-to-cart w-full block"
                                    data-product-id="<?= $product['id'] ?? '' ?>"
                                    <?= $isLowStock ? 'data-low-stock="true"' : '' ?>>
                                Add to Cart
                            </button>
                        <?php else: ?>
                            <button class="btn btn-disabled w-full block" disabled>Out of Stock</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php // Existing code after the loop... ?>
```

**Explanation:**
*   Applies Tailwind classes (`bg-white`, `rounded-lg`, `shadow-md`, hover effects, etc.) for consistent styling.
*   Uses the `sample-card` class if needed for further global styling.
*   Adjusts grid columns (`lg:grid-cols-3 xl:grid-cols-4`) for better layout.
*   Uses `htmlspecialchars()` consistently.
*   Adds stock status logic to disable the "Add to Cart" button when necessary, mirroring the behavior on the product detail page.
*   Uses `$product['image']` based on the field name observed in the detail view and schema.
*   Adds optional display for `short_description` and `highlight_text`.

## 3. Issue 2: Failing "Add to Cart" Functionality

**Problem:**
Clicking the "Add to Cart" button on the homepage, product listing page, or product detail page results in a client-side error message ("Error adding to cart"). Server logs (`apache-access.log`) show that the corresponding AJAX POST request to `index.php?page=cart&action=add` receives an `HTTP 400 Bad Request` response status code.

**Analysis:**
1.  **Client-Side JS (`views/layout/footer.php`):** The global event handler correctly identifies `.add-to-cart` clicks, extracts `data-product-id`, and attempts to fetch a CSRF token using `document.querySelector('input[name="csrf_token"]')`. It then constructs a `fetch` POST request with `product_id`, `quantity`, and the potentially found `csrf_token`.
2.  **Server-Side Controller (`CartController::addToCart()`):** This method first calls `validateAjax()` and `validateCSRF()`. It then validates the `product_id` and `quantity` from `$_POST`. A 400 status code is typically returned for client errors like invalid input or failed validation.
3.  **CSRF Token Implementation:**
    *   The Technical Design Specification (TDS) already highlighted CSRF implementation issues.
    *   Reviewing the provided HTML outputs (`current_landing_page.html`, `view_details_product_id-1.html`, `shop_products.html`) confirms that the CSRF token (`<input type="hidden" name="csrf_token" ...>`) is **missing entirely** from the product listing page (`shop_products.html`).
    *   On the homepage and product detail page, a token *is* present but primarily within specific forms (newsletter, main product form). The global JS handler might pick up an unrelated token, or none at all depending on the page structure near the button clicked.
4.  **Root Cause:** The `validateCSRF()` check within `CartController::addToCart()` (or potentially earlier in `index.php`'s POST handling) is failing because the CSRF token sent by the JavaScript is either missing or does not match the token stored in the user's session (`$_SESSION['csrf_token']`). This mismatch leads to the 400 Bad Request. The client-side "Error adding to cart" message is a generic response triggered by the failed HTTP request (`!response.ok` or `catch` block in the JS `fetch` handler).

**Suggestion:**
Implement CSRF token handling correctly across the application:

1.  **Generate & Pass Token:** Ensure the CSRF token is generated and made available to all views that contain forms or AJAX actions requiring protection.
2.  **Output Token Reliably:** Add a hidden input field with the CSRF token in a consistent location within the main content area of relevant views, easily selectable by JavaScript.
3.  **Update AJAX Handler:** Modify the global Add-to-Cart JavaScript handler to reliably find and send the correct CSRF token with the AJAX request.

**Code Fixes:**

**Step 1: Ensure Token Availability in Controllers (Example: `BaseController.php` or individual controllers)**

Modify controllers that render views needing CSRF protection (like `ProductController`, `CartController`) to fetch and pass the token. A good place is a helper method in `BaseController` or directly before including the view.

```php
<?php // In BaseController.php (add a helper or modify rendering logic)

// Option A: Helper Method
protected function getCommonViewData(array $data = []): array {
    $data['csrfToken'] = $this->getCsrfToken(); // Assumes getCsrfToken() is working
    // Add other common data like user info, cart count maybe...
    return $data;
}

// Option B: Directly in controller methods before rendering/including view
// Example in ProductController::showProductList()
public function showProductList() {
    try {
        // ... [existing code to fetch products, categories, etc.] ...
        $csrfToken = $this->getCsrfToken(); // Get the token

        // Now include the view, $csrfToken will be available
        require_once __DIR__ . '/../views/products.php';

    } catch (Exception $e) {
        // ... error handling ...
    }
}
// Apply similar logic in showHomePage(), showProduct(), etc.
?>
```

**Step 2: Output CSRF Token in Views**

Add a hidden input field in relevant views (`views/home.php`, `views/products.php`, `views/product_detail.php`, etc.). Place it somewhere consistent, e.g., inside the main container or section. Using an ID makes JS selection easier.

```html
<!-- Example placement in views/layout/header.php or near the start of <main> in specific views -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

<!-- OR place it inside the main container of each page -->
<!-- e.g., in views/products.php -->
<section class="products-section">
    <div class="container">
         <input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
         <?php // ... rest of the products page content ... ?>
    </div>
</section>
```

*Note: Ensure the `$csrfToken` variable is correctly passed from the controller as shown in Step 1.*

**Step 3: Update Global Add-to-Cart JavaScript Handler (`views/layout/footer.php`)**

Modify the existing event listener to find the token by ID and include it in the fetch body.

```javascript
        // Canonical Add-to-Cart handler (event delegation) - UPDATED
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.add-to-cart');
            if (!btn) return;
            e.preventDefault();
            if (btn.disabled) return;

            const productId = btn.dataset.productId;
            // *** MODIFICATION START ***
            const csrfTokenInput = document.getElementById('csrf-token-value'); // Select by ID
            const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';

            if (!csrfToken) {
                showFlashMessage('Security token missing. Please refresh the page.', 'error'); // More specific message
                return; // Stop execution if token is missing
            }
            // *** MODIFICATION END ***

            btn.disabled = true;
            const originalText = btn.textContent; // Use textContent for button text
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...'; // Improved loading state

            fetch('index.php?page=cart&action=add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json' // Indicate we expect JSON back
                 },
                // Ensure token is included in the body
                body: `product_id=${encodeURIComponent(productId)}&quantity=1&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            // Add check for response content type
            .then(response => {
                 if (!response.ok) {
                    // Try to parse error JSON if available, otherwise throw generic error
                    return response.json().catch(() => {
                       throw new Error(`HTTP error ${response.status}`);
                    }).then(errData => {
                       throw new Error(errData.message || `HTTP error ${response.status}`);
                    });
                 }
                 // Check if response is JSON before parsing
                 const contentType = response.headers.get("content-type");
                 if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                 } else {
                    throw new Error("Received non-JSON response from server");
                 }
            })
            .then(data => {
                // Restore button state regardless of success/failure before showing message
                btn.innerHTML = originalText; // Use innerHTML if icon was used
                btn.disabled = false;

                if (data.success) {
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                        cartCount.style.display = data.cart_count > 0 ? 'inline-block' : 'none'; // Use inline-block if needed
                    }
                    showFlashMessage(data.message || 'Product added to cart', 'success');

                    // Handle stock status display changes on the button if needed
                    if (data.stock_status === 'out_of_stock') {
                        btn.disabled = true;
                        btn.classList.remove('btn-secondary'); // Ensure correct classes are targeted
                        btn.classList.add('btn-disabled');
                        btn.innerHTML = '<i class="fas fa-times-circle mr-2"></i> Out of Stock';
                    } else if (data.stock_status === 'low_stock') {
                        showFlashMessage('Limited quantity available', 'info');
                        btn.dataset.lowStock = 'true'; // Set data attribute if needed
                    } else {
                         btn.dataset.lowStock = 'false'; // Reset if previously low stock
                    }

                } else {
                    // Server indicated failure (e.g., stock issue after CSRF passed)
                    showFlashMessage(data.message || 'Error adding product.', 'error');
                     if (data.stock_status === 'out_of_stock') { // Handle case where stock ran out between page load and click
                        btn.disabled = true;
                        btn.classList.remove('btn-secondary');
                        btn.classList.add('btn-disabled');
                        btn.innerHTML = '<i class="fas fa-times-circle mr-2"></i> Out of Stock';
                    }
                }
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
                // Restore button state on error
                btn.innerHTML = originalText;
                btn.disabled = false;
                // Show specific error from server if available, otherwise generic
                showFlashMessage(error.message || 'Could not connect or add product. Please try again.', 'error');
            });
        });
```

**Explanation of JS Changes:**
*   Selects the CSRF token using the reliable ID `csrf-token-value`.
*   Adds a check to ensure the token was found before proceeding.
*   Includes the token correctly in the `fetch` request body.
*   Adds `Accept: application/json` header.
*   Improves error handling in the `fetch` promise chain to better parse potential JSON error messages from the server or handle network errors.
*   Restores button text/state *before* showing the flash message for better UX.
*   Adjusts cart count display logic slightly (`inline-block`).
*   Handles potential stock status changes reflected in the JSON response.

**Verification:**
After implementing these changes:
1.  Clear browser cache and session cookies.
2.  Load the homepage, product listing page, and a product detail page.
3.  Inspect the HTML source to confirm the `<input type="hidden" id="csrf-token-value" ...>` is present and has a value.
4.  Use browser developer tools to monitor the Network tab when clicking "Add to Cart".
5.  Verify the POST request to `cart/add` now includes the `csrf_token` in its payload.
6.  Verify the request receives an `HTTP 200 OK` response with a JSON body indicating success (`{"success": true, ...}`).
7.  Verify the cart count in the header updates and a success message is shown.

## 4. Conclusion

Addressing the UI inconsistency on the product listing page and fixing the critical CSRF token implementation issue for the "Add to Cart" functionality will significantly improve the user experience and security of The Scent platform. Applying the suggested code changes should resolve the reported problems. Further review of other POST-based actions (login, register, cart updates, newsletter) is recommended to ensure CSRF protection is robust across the entire application.
