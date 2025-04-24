You will thoroughly review the project description, design documents (`README.md`, `technical_design_specification.md v3`), the codebase file structure, database schema in `the_scent_schema.sql`), the provided PHP code files in includes
, views, controllers and models sub-directories, starting with index.php and .htaccess files, and the current HTML outputs (`current_landing_page.html`, `view_details_product_id-1.html`, `shop_products.html`).

Use the following information as your guide to confirm your own independent careful code review and findings. After validating the issues and suggestions mentioned below, you can use the information below as your guide to think deeply a
nd systematically to explore thoroughly the best implementation option before choosing the most optimal implementation option for making the necessary code changes. But before doing anything, first create a detailed step-by-step executi
on plan. Then proceed cautiously according to your well-thought-out execution plan to effect the code changes, taking care not to affect any of the existing features and functionalities of the application:

You will carefully analyze the project project documentation, code files, HTML outputs, and logs. Then compare your independent code review and problem analysis against the suggested improvements below.

Current Known Issues:  
1. I can now "Add to cart" with the button on the main landing page, but when I click on the cart icon on the top right of the header (top) navigation bar. I got an error "Oops something went wrong".
2.  **Features Section:** Reflect that the Cart Page display is now functional. Explicitly mention the known issue with Product List Pagination.
3.  **Security Section:** Clarify the status of Rate Limiting (implemented but inconsistent).
4.  **Testing Scenarios Section:** Update the status for Cart Page (working) and Product Pagination (known issue).

$ curl -Lk https://the-scent.com/ -o current_landing_page.html
curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html
curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html
curl -Lk 'https://the-scent.com/index.php?page=contact' -o contact_page.html
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=1' -o products_page_1.html
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=2' -o products_page_2.html
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 40531    0 40531    0     0  1196k      0 --:--:-- --:--:-- --:--:-- 1236k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 46482    0 46482    0     0  2990k      0 --:--:-- --:--:-- --:--:-- 3026k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 55239    0 55239    0     0  1852k      0 --:--:-- --:--:-- --:--:-- 1860k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 19582    0 19582    0     0  1972k      0 --:--:-- --:--:-- --:--:-- 2124k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 55239    0 55239    0     0  3898k      0 --:--:-- --:--:-- --:--:-- 4149k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 55239    0 55239    0     0  5519k      0 --:--:-- --:--:-- --:--:-- 5993k

$ cat apache_logs/apache-access.log 
127.0.0.1 - - [24/Apr/2025:20:18:09 +0800] "GET / HTTP/1.1" 200 43575 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:18:09 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 49526 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:18:10 +0800] "GET /index.php?page=products HTTP/1.1" 200 58431 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:18:10 +0800] "GET /index.php?page=contact HTTP/1.1" 200 22478 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:18:10 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 58431 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:18:10 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 58431 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:19:25 +0800] "GET / HTTP/1.1" 200 10893 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:34 +0800] "GET /index.php?page=contact HTTP/1.1" 200 6582 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:38 +0800] "GET /index.php?page=faq HTTP/1.1" 200 6286 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:40 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 6207 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:41 +0800] "GET /index.php?page=order-tracking HTTP/1.1" 200 6142 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:47 +0800] "GET /index.php?page=products HTTP/1.1" 200 8978 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:20:27 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 10643 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:20:47 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 9302 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:20:55 +0800] "GET /index.php?page=product&id=7 HTTP/1.1" 200 11880 "https://the-scent.com/index.php?page=products&page_num=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:20:55 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=7" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:21:06 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 1392 "https://the-scent.com/index.php?page=product&id=7" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [24/Apr/2025:20:21:13 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"

$ cat apache_logs/apache-error.log
[Thu Apr 24 20:17:12.391297 2025] [ssl:warn] [pid 281196] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Thu Apr 24 20:17:12.429293 2025] [ssl:warn] [pid 281197] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)


# Suggested Improvements and Fixes for "The Scent" E-commerce Platform (use as your reference for your own independent code review and validation)

## 1. Introduction

This document outlines the findings from a code review of "The Scent" e-commerce platform. The review process involved examining the provided PHP code files, database schema, design documents (`technical_design_specification.md`, `README.md`), web server logs, and HTML output captures.

The Scent is a custom-built PHP application using an MVC-inspired architecture without a major framework. It aims to provide a secure, modern, and extensible platform for selling aromatherapy products, featuring a personalized scent quiz and AJAX-enhanced user interactions.

The primary goal of this review is to identify functional issues, security weaknesses, inconsistencies, and areas for improvement, with a specific focus on resolving the reported error when accessing the shopping cart page. Fixes are proposed with code examples to enhance stability, security, and maintainability.

## 2. Current Project State Summary

*   **Architecture:** Custom PHP, MVC-like structure. Routing handled by `index.php`. Controllers (`controllers/`) manage business logic, extending `BaseController`. Views (`views/`) handle presentation. Database interaction via PDO (`includes/db.php`) and Models (`models/`). Security managed by `includes/SecurityMiddleware.php`.
*   **Frontend:** HTML, Tailwind CSS (CDN), custom CSS (`css/style.css`), JavaScript (AOS, Particles, custom handlers in `footer.php`).
*   **Key Features:** Product display (list, detail, featured), Scent Quiz, User Auth (Register/Login), AJAX Cart Add/Mini-Cart, AJAX Newsletter Signup.
*   **Functionality Status:**
    *   **Add-to-Cart (AJAX):** Reported as functional (initial bug resolved). Relies on strict CSRF token handling pattern.
    *   **Cart Page Display (`?page=cart`):** **Broken.** Displays "Oops! Something went wrong".
    *   **Product Pagination (`?page=products&page_num=X`):** **Broken.** Displays the same set of products regardless of the `page_num` parameter. Apache logs confirm identical byte sizes for page 1 and page 2 responses.
    *   **Category Filtering:** Functioning, layout improved (horizontal), duplicate category names handled in model query.
    *   **CSRF Protection:** Mechanism implemented and enforced via `index.php` for POST requests and handled correctly via JS for AJAX.
    *   **Rate Limiting:** Implementation exists but is inconsistent across controllers.
    *   **Cart Storage:** Uses `$_SESSION['cart']` primarily; `models/Cart.php` and `cart_items` table exist but seem underutilized or only for logged-in users.
*   **Security:** Standard headers applied via `config.php`. CSRF protection active. Input validation used. Prepared statements prevent SQLi. Session management appears secure. CSP policy could be stricter.

## 3. Identified Issues

Based on the code review, logs, and reported problems, the following issues have been identified:

1.  **Critical: Cart Page Error ("Oops! Something went wrong")**
    *   **Symptom:** Accessing `index.php?page=cart` results in a generic error page instead of displaying the cart contents.
    *   **Root Cause:** The router in `index.php` for the `case 'cart':` (default action) directly calls `$cartItems = $controller->getCartItems();` and then includes `views/cart.php`. However, `views/cart.php` requires additional variables (`$total`, `$csrfToken`) which are *not* set by `$controller->getCartItems()`. They are correctly set within the `CartController::showCart()` method, but this method is bypassed by the current routing logic for the main cart view. This leads to "Undefined variable" PHP errors, causing the failure.

2.  **Critical: Product List Pagination Not Working**
    *   **Symptom:** Navigating to `index.php?page=products&page_num=2` (or higher) displays the same products as page 1. Apache logs confirm identical response sizes.
    *   **Root Cause:** While the `ProductController::showProductList` calculates the `$offset` correctly, the issue likely lies within the `Product::getFiltered` method or how its parameters are bound. Despite the SQL query including `LIMIT ? OFFSET ?`, the `OFFSET` seems ineffective. The most likely cause is subtle issue in PDO parameter binding for LIMIT/OFFSET, or potentially the query logic itself when combined with specific filters/sorting.

3.  **Inconsistency: Cart Storage Mechanism**
    *   **Symptom:** The `CartController` primarily manipulates `$_SESSION['cart']`. It initializes a DB-based `Cart` model (`models/Cart.php`) only when a user is logged in, but the core display logic (`getCartItems`) seems to prioritize the session even then. The `cart_items` DB table exists but isn't the primary storage, leading to potential data loss for non-logged-in users or inconsistencies.
    *   **Impact:** Cart contents are lost when the session ends for guest users. Merging logic exists but isn't fully utilized for a persistent experience.

4.  **Inconsistency: Rate Limiting Implementation**
    *   **Symptom:** `BaseController` provides a `validateRateLimit` method intended for standardization, but `AccountController` and `NewsletterController` use custom rate-limiting logic. The base method also relies on APCu, which might not be installed or enabled, causing it to fail open.
    *   **Impact:** Inconsistent protection against brute-force attacks across different endpoints. Potential lack of effective rate limiting if APCu is unavailable.

5.  **Improvement Opportunity: Content Security Policy (CSP)**
    *   **Symptom:** The current CSP defined in `config.php` includes `'unsafe-inline'` and `'unsafe-eval'`, primarily for script and style sources.
    *   **Impact:** Reduces the effectiveness of CSP against certain types of cross-site scripting (XSS) attacks.

## 4. Suggested Fixes and Improvements

Here are the suggested fixes and improvements for the identified issues:

---

### Fix 1: Resolve Cart Page Error

*   **Goal:** Ensure the cart page (`index.php?page=cart`) renders correctly by calling the appropriate controller method that prepares all necessary data.
*   **Solution:** Modify the routing logic in `index.php` for the `cart` page to call the `showCart()` method within the `CartController`, instead of manually getting items and including the view.

*   **File:** `index.php`
*   **Code Change:**

    ```diff
    --- a/index.php
    +++ b/index.php
    @@ -40,17 +40,18 @@
             require_once __DIR__ . '/controllers/CartController.php';
             $controller = new CartController($pdo);

             if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                 // AJAX Add to Cart endpoint
                 $controller->addToCart();
-                // jsonResponse will exit
+                exit; // Ensure script termination after JSON response
             }

             if ($action === 'mini') {
                 // AJAX Mini Cart endpoint
                 $controller->mini();
-                // jsonResponse will exit
+                exit; // Ensure script termination after JSON response
             }

-            $cartItems = $controller->getCartItems();
-            require_once __DIR__ . '/views/cart.php';
+            // Call the controller method responsible for displaying the cart
+            $controller->showCart();
+            // The showCart method will handle fetching data and including the view.
             break;

         case 'checkout':
    ```

*   **Explanation:** This change delegates the responsibility of preparing data (`$cartItems`, `$total`, `$csrfToken`) and including the view (`views/cart.php`) entirely to the `CartController::showCart()` method, which already contains the correct logic. This ensures all required variables are available to the view template, resolving the "Undefined variable" errors.

---

### Fix 2: Correct Product List Pagination

*   **Goal:** Ensure that navigating to different pages in the product list displays the correct subset of products based on the calculated offset.
*   **Solution:** While the exact cause is subtle, the most common issue with LIMIT/OFFSET binding in PDO involves data types. Explicitly bind the `limit` and `offset` parameters as integers (`PDO::PARAM_INT`) within the `Product::getFiltered` method. Add debugging logs to confirm the SQL and parameters being executed.

*   **File:** `models/Product.php`
*   **Code Change (Inside `getFiltered` method):**

    ```diff
    --- a/models/Product.php
    +++ b/models/Product.php
    @@ -112,10 +112,19 @@
                 break;
         }
         $sql .= " LIMIT ? OFFSET ?";
+
+        // Prepare parameters for execution - explicitly cast limit/offset
         $params[] = (int)$limit;
         $params[] = (int)$offset;
+
+        // --- DEBUGGING: Log the final SQL and params ---
+        // error_log("Product::getFiltered SQL: " . $sql);
+        // error_log("Product::getFiltered Params: " . print_r($params, true));
+        // --- END DEBUGGING ---
+
         $stmt = $this->pdo->prepare($sql);
-        $stmt->execute($params);
+        // Bind parameters with explicit types for limit and offset
+        $stmt->execute($params); // PDO often handles ints correctly, but explicit binding below is safer
+        /* Alternatively, use explicit binding:
+        $paramCount = count($params);
+        foreach ($params as $key => $value) {
+            $paramIndex = $key + 1;
+            if ($paramIndex === $paramCount - 1) { // Limit parameter
+                $stmt->bindValue($paramIndex, (int)$value, PDO::PARAM_INT);
+            } elseif ($paramIndex === $paramCount) { // Offset parameter
+                $stmt->bindValue($paramIndex, (int)$value, PDO::PARAM_INT);
+            } else {
+                $stmt->bindValue($paramIndex, $value); // Let PDO determine type for others
+            }
+        }
+        $stmt->execute();
+        */
         $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
         // Decode JSON fields if present
         foreach ($products as &$product) {

    ```
*   **Explanation:** Although PDO often correctly handles integer parameters in `execute()`, explicitly binding `LIMIT` and `OFFSET` values using `PDO::PARAM_INT` (shown in the commented alternative `bindValue` loop) eliminates potential type ambiguity that *might* cause issues with some database drivers or versions. Adding temporary `error_log` statements is crucial for debugging: compare the logged SQL and parameters for page 1 (`OFFSET 0`) vs. page 2 (`OFFSET 12`) to ensure they are different as expected. If the SQL and params look correct in the logs, the issue might be deeper within the database or query plan itself, but explicit binding is the first step.

---

### Recommendation 3: Implement Consistent Cart Storage

*   **Goal:** Provide a consistent and persistent shopping cart experience, especially for logged-in users.
*   **Solution:** Refactor `CartController` and related logic to *always* use the database (`cart_items` table via `models/Cart.php`) as the primary storage mechanism for logged-in users. Session cart should only be used for guests. Ensure the `mergeSessionCartOnLogin` function is reliably called during the login process.
*   **File(s):** `controllers/CartController.php`, `controllers/AccountController.php` (or wherever login occurs).
*   **Conceptual Code Snippets:**

    *   In `CartController` methods (e.g., `addItem`, `updateItem`, `getItems`, `getCartCount`):
        ```php
        if ($this->isLoggedIn) {
            // ALWAYS use $this->cartModel for DB operations
            // Example: $this->cartModel->addItem($productId, $quantity);
        } else {
            // Use $_SESSION['cart'] for guest users
            // Example: $_SESSION['cart'][$productId] = $quantity;
        }
        ```
    *   In the Login logic (`AccountController::login` or similar):
        ```php
        // After successful login and session creation...
        $userId = $_SESSION['user_id']; // Or $user['id']
        CartController::mergeSessionCartOnLogin($this->pdo, $userId);
        unset($_SESSION['cart']); // Clear session cart after merging
        ```
*   **Explanation:** This makes the cart persistent across sessions for logged-in users. Guest carts remain session-based. The `mergeSessionCartOnLogin` ensures guest cart items are transferred upon login.

---

### Recommendation 4: Standardize Rate Limiting

*   **Goal:** Apply consistent rate limiting across sensitive endpoints using the centralized mechanism.
*   **Solution:** Refactor `AccountController` (`requestPasswordReset`, `resetPassword`, `login`) and `NewsletterController` (`subscribe`) to remove custom rate-limiting logic and instead use `$this->validateRateLimit('action_key')`. Configure the limits for these actions in `config.php` under `SECURITY_SETTINGS['rate_limiting']['endpoints']`. Ensure the rate-limiting backend (e.g., APCu) is functional or implement a fallback.
*   **File(s):** `controllers/AccountController.php`, `controllers/NewsletterController.php`, `config.php`.
*   **Conceptual Code Snippet (e.g., in `AccountController::requestPasswordReset`):**
    ```php
    // Remove custom rate limit check using $_SESSION

    // Use the standardized base controller method
    $this->validateRateLimit('reset'); // Key 'reset' should match config

    // ... rest of the method logic ...
    ```
*   **Explanation:** Centralizes rate limit configuration and logic, making it easier to manage and ensuring consistent application of security policies.

---

### Recommendation 5: Tighten Content Security Policy (CSP)

*   **Goal:** Enhance protection against XSS by restricting inline scripts and styles.
*   **Solution:** Review all inline `style` attributes and `<script>` tags (especially those using `unsafe-inline` or `unsafe-eval`). Refactor them into external CSS (`/css/style.css`) and JS files. Update the CSP policy in `config.php` to remove `'unsafe-inline'` and `'unsafe-eval'` if possible. This might require careful refactoring of existing JavaScript, especially related to libraries like Particles.js or dynamic content generation.
*   **File:** `config.php`
*   **Code Change (Potential - requires JS/CSS refactoring first):**
    ```diff
    --- a/config.php
    +++ b/config.php
    @@ -52,7 +52,7 @@
         'X-Content-Type-Options' => 'nosniff',
         'Referrer-Policy' => 'strict-origin-when-cross-origin',
         // CSP tightened: removed 'unsafe-inline' from script-src and style-src
-        'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://js.stripe.com; style-src 'self'; frame-src https://js.stripe.com; img-src 'self' data: https:; connect-src 'self' https://api.stripe.com",
+        'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://js.stripe.com; style-src 'self'; frame-src https://js.stripe.com; img-src 'self' data: https:; connect-src 'self' https://api.stripe.com; object-src 'none'; base-uri 'self';",
         'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'
     ],
     'file_upload' => [

    ```
*   **Explanation:** Removing `unsafe-inline` and `unsafe-eval` significantly hardens the application against XSS. Adding `object-src 'none'` and `base-uri 'self'` provides further protection. This requires careful testing after refactoring inline styles/scripts.

---

## 5. Conclusion

The "The Scent" platform has a solid foundation with key e-commerce features and security considerations built-in. The resolution of the Add-to-Cart bug and the strict adherence to the CSRF handling pattern are significant steps forward.

By addressing the critical issues identified in this document – specifically the **Cart Page Error** and the **Product Pagination Bug** – the platform's reliability and user experience will be substantially improved. Implementing the recommendations for consistent cart storage, standardized rate limiting, and a tighter CSP will further enhance security and maintainability. These changes will bring the application closer to a production-ready state, aligning functional behavior with the intended design.

---
https://drive.google.com/file/d/1-mmbmgDm5eSK9DRQTpI-kI952hZ6KMtW/view?usp=sharing, https://drive.google.com/file/d/193i6zLAWhG2Dk0oyRkr47Nv4F7lx5xYJ/view?usp=sharing, https://drive.google.com/file/d/1DPvAw1Fbd-1-nLNhfCCrYop93xjhhxt1/view?usp=sharing, https://drive.google.com/file/d/1H2FYaJ_6tuHGxYivfburbHWfAFVrEA3P/view?usp=sharing, https://drive.google.com/file/d/1MPZjnDevg2WhH4hnESIMgJ6leGZbSTwm/view?usp=sharing, https://drive.google.com/file/d/1MiH7a5XM_BRAz1MiZ5IxRz57692lwjw_/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221RU7dELKJT7Q8j9h7C4Y6IFMW8utfq_-q%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1Vj8BpOoSkyUxqgJWh4Qc0Yua4YvGE_s8/view?usp=sharing, https://drive.google.com/file/d/1VvlvagtoNe5qhFcKdSUBywUatxVWU115/view?usp=sharing, https://drive.google.com/file/d/1dKPcv55Jw-AthO8ZXsh0Jq0S6Yr1xM4s/view?usp=sharing, https://drive.google.com/file/d/1dj90s2V52aXob050iYyhmkw1tcG1NXXq/view?usp=sharing, https://drive.google.com/file/d/1iqMgMGVNI-qv0Yw-cVSoKnY1zoFyCIcg/view?usp=sharing, https://drive.google.com/file/d/1nEpjmkBuyn-Uf01U9r3PGHZeQIrCpEqc/view?usp=sharing, https://drive.google.com/file/d/1q83baNGu8o_BlHRLVw9bsSO9k8qzPa_4/view?usp=sharing, https://drive.google.com/file/d/1s1uKXO3KuOhpvM0g5WLXUE01N1AJEDZI/view?usp=sharing, https://drive.google.com/file/d/1ujfRAGbSlLrDcu-mX3kMaESDLyftE57E/view?usp=sharing
