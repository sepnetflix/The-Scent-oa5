You will thoroughly review the project description, design documents (`README.md`, `technical_design_specification.md v3`), the codebase file structure, database schema in `the_scent_schema.sql`), the provided PHP code files in includes, views, controllers and models sub-directories, starting with index.php and .htaccess files, and the current HTML outputs (`current_landing_page.html`, `view_details_product_id-1.html`, `shop_products.html`).

Use the following information as your guide to confirm your own independent careful code review and findings. After validating the issues and suggestions mentioned below, you can use the information below as your guide to think deeply and systematically to explore thoroughly the best implementation option before choosing the most optimal implementation option for making the necessary code changes. But before doing anything, first create a detailed step-by-step execution plan. Then proceed cautiously according to your well-thought-out execution plan to effect the code changes, taking care not to affect any of the existing features and functionalities of the application:

*Recent Changes made:*
$ diff views/product_detail.php bak/views/product_detail.php
124,125c124
<                         <button type="submit" class="btn btn-primary w-full py-3 text-lg add-to-cart"
<                                 data-product-id="<?= $product['id'] ?? '' ?>">
---
>                         <button type="submit" class="btn btn-primary w-full py-3 text-lg add-to-cart">

*Issue still outstanding:* "Add to cart" button on the main landing page failed with the message "Error adding to cart" as shown in the attached screenshot.

You will use a systematic diagnosis and solution to carefully review the relevant project .php code files to analyze “Add to cart” error on the main landing page:

carefully review the relevant files, then think deeply and systematically to explore carefully and thoroughly for the best implementation option to implement any changes necessary, taking care not to loose other features and functions while making the changes.

Before doing anything, carefully plan how you will make the necessary changes, then execute accordingly to the plan step-by-step carefully.

Likely cause of "Error adding to cart" on the main landing page:

The CSRF token is missing from the DOM on the landing page, so the AJAX request fails CSRF validation.
5. Suggestion: Ensure that the main landing page (views/home.php) outputs the CSRF token in a hidden input with id="csrf-token-value" at the top of the page, just like on the products and product detail pages.
Next step:

Suggestion: carefully review the relevant project code files to check whether it will help to add the CSRF token hidden input to home.php to ensure the global AJAX handler can always find it, which will fix the "Add to cart" error on the main landing page.

# Use the following steps to guide you to arrive at the correct solution.

1. Diagnosis
Frontend:

The "Add to Cart" button on the landing page has the correct data-product-id attribute.
The global JS handler in footer.php should read this and send it via AJAX.
The CSRF token is present in a hidden input.
Backend:

The CartController::addToCart() method expects product_id and quantity in $_POST.
If product_id is missing or invalid, it returns a 400 error with "Invalid product or quantity".
Your screenshot and logs:

The error message is "Error adding to cart".
The POST request to /index.php?page=cart&action=add returns 200, but the error message is still shown.
This suggests the AJAX request is being made, but either:
The product ID is not being sent in the request body, or
The CSRF token is missing/invalid, or
The server is returning a JSON error response (not a 400, but a JSON with success: false).
2. Step-by-Step Plan
Add Defensive Logging and Error Output:

Add debug logging in CartController::addToCart() to log the incoming $_POST data and the error path taken.
This will confirm if product_id and csrf_token are being received.
Add Defensive JS Check:

In the global JS handler (likely in footer.php), add a check to ensure data-product-id is present and not empty before making the AJAX request.
If missing, show a clear error.
Ensure CSRF Token is Always Present:

Confirm that the CSRF token is present in the DOM as <input type="hidden" id="csrf-token-value" ...> on the landing page.
Test and Validate:

After making these changes, test the "Add to Cart" button on the landing page and check the browser network tab and server logs for details.
3. Implementation
Step 1: Add Debug Logging in CartController
Add at the start of addToCart():
```
<?php
error_log('[addToCart] POST: ' . json_encode($_POST));
```

Add before each error response:
```
<?php
error_log('[addToCart] Error: Invalid product or quantity');
error_log('[addToCart] Error: Product not found');
error_log('[addToCart] Error: Insufficient stock');
```

Step 2: Add Defensive JS Check
In the global JS handler for .add-to-cart (in footer.php), before making the AJAX request:
```
const productId = btn.getAttribute('data-product-id');
if (!productId) {
  showFlashMessage('Product ID missing. Please refresh the page.', 'error');
  return;
}
```

Step 3: Ensure CSRF Token is Present
Check that the landing page (views/home.php) includes:
```
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
```

If not, add it at the top of the file.

4. Next Steps
I will:

Add debug logging to CartController::addToCart().
Add a defensive check in the global JS handler for missing product ID.
Ensure the CSRF token is present in home.php.

Now you will proceed carefully step-by-step...

---
$ curl -Lk https://the-scent.com/ -o current_landing_page.html
curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html
curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html
curl -Lk 'https://the-scent.com/index.php?page=contact' -o contact_page.html
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=1' -o products_page_1.html
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=2' -o products_page_2.html
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 36778    0 36778    0     0   812k      0 --:--:-- --:--:-- --:--:--  816k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 42729    0 42729    0     0  2631k      0 --:--:-- --:--:-- --:--:-- 2781k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 54981    0 54981    0     0  3806k      0 --:--:-- --:--:-- --:--:-- 4130k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 15829    0 15829    0     0  1717k      0 --:--:-- --:--:-- --:--:-- 1932k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 54981    0 54981    0     0  3844k      0 --:--:-- --:--:-- --:--:-- 4130k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 54981    0 54981    0     0  5516k      0 --:--:-- --:--:-- --:--:-- 5965k

$ tail -70 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'
127.0.0.1 - - [23/Apr/2025:11:42:47 +0800] "GET / HTTP/1.1" 200 39483 "-" "curl/8.5.0"
127.0.0.1 - - [23/Apr/2025:11:42:47 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 45500 "-" "curl/8.5.0"
127.0.0.1 - - [23/Apr/2025:11:42:47 +0800] "GET /index.php?page=products HTTP/1.1" 200 57900 "-" "curl/8.5.0"
127.0.0.1 - - [23/Apr/2025:11:42:47 +0800] "GET /index.php?page=contact HTTP/1.1" 200 18386 "-" "curl/8.5.0"
127.0.0.1 - - [23/Apr/2025:11:42:48 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 57900 "-" "curl/8.5.0"
127.0.0.1 - - [23/Apr/2025:11:42:48 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 57900 "-" "curl/8.5.0"
127.0.0.1 - - [23/Apr/2025:11:43:37 +0800] "GET / HTTP/1.1" 200 9685 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:43:37 +0800] "GET /css/style.css HTTP/1.1" 200 6843 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:43:38 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [23/Apr/2025:11:43:46 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
::1 - - [23/Apr/2025:11:43:47 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [23/Apr/2025:11:43:50 +0800] "GET /index.php?page=contact HTTP/1.1" 200 5406 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:43:50 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:43:53 +0800] "GET /index.php?page=faq HTTP/1.1" 200 5112 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:43:53 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:43:55 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 5033 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:43:55 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:43:56 +0800] "GET /index.php?page=order-tracking HTTP/1.1" 200 4970 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:43:56 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:43:59 +0800] "GET /index.php?page=privacy HTTP/1.1" 200 5019 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:43:59 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:44:01 +0800] "GET /index.php?page=products HTTP/1.1" 200 7738 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:44:01 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:44:08 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 9403 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:44:08 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:44:14 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 8062 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:44:14 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:44:18 +0800] "GET /index.php HTTP/1.1" 200 7877 "https://the-scent.com/index.php?page=products&page_num=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:44:18 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:44:27 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 10836 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:44:27 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:44:29 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 827 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:44:31 +0800] "GET /index.php HTTP/1.1" 200 7877 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:44:31 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [23/Apr/2025:11:44:38 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 2492 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
pete@pop-os:/cdrom/project/The-Scent-oa5
$ cat apache_logs/apache-error.log 
[Wed Apr 23 11:41:45.838225 2025] [ssl:warn] [pid 237165] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Wed Apr 23 11:41:45.878746 2025] [ssl:warn] [pid 237166] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Wed Apr 23 11:42:47.934928 2025] [php:notice] [pid 237170] [client 127.0.0.1:45374] [showProductList] conditions: []
[Wed Apr 23 11:42:47.934972 2025] [php:notice] [pid 237170] [client 127.0.0.1:45374] [showProductList] params: []
[Wed Apr 23 11:42:48.035468 2025] [php:notice] [pid 237172] [client 127.0.0.1:45404] [showProductList] conditions: []
[Wed Apr 23 11:42:48.035506 2025] [php:notice] [pid 237172] [client 127.0.0.1:45404] [showProductList] params: []
[Wed Apr 23 11:42:48.086130 2025] [php:notice] [pid 237168] [client 127.0.0.1:58070] [showProductList] conditions: []
[Wed Apr 23 11:42:48.086160 2025] [php:notice] [pid 237168] [client 127.0.0.1:58070] [showProductList] params: []
[Wed Apr 23 11:44:01.603790 2025] [php:notice] [pid 237565] [client 127.0.0.1:52246] [showProductList] conditions: [], referer: https://the-scent.com/index.php?page=privacy
[Wed Apr 23 11:44:01.603849 2025] [php:notice] [pid 237565] [client 127.0.0.1:52246] [showProductList] params: [], referer: https://the-scent.com/index.php?page=privacy
[Wed Apr 23 11:44:08.148224 2025] [php:notice] [pid 237567] [client 127.0.0.1:39614] [showProductList] conditions: [], referer: https://the-scent.com/index.php?page=products
[Wed Apr 23 11:44:08.148272 2025] [php:notice] [pid 237567] [client 127.0.0.1:39614] [showProductList] params: [], referer: https://the-scent.com/index.php?page=products
[Wed Apr 23 11:44:14.585963 2025] [php:notice] [pid 237569] [client 127.0.0.1:39634] [showProductList] conditions: [], referer: https://the-scent.com/index.php?page=products&page_num=2
[Wed Apr 23 11:44:14.586014 2025] [php:notice] [pid 237569] [client 127.0.0.1:39634] [showProductList] params: [], referer: https://the-scent.com/index.php?page=products&page_num=2

