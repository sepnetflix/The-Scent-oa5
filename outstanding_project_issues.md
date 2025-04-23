Issue still outstanding: "Add to cart" button on the main landing page failed with the message "Error adding to cart" as shown in the attached screenshot.

Analysis: The most likely cause of "Error adding to cart" on the main landing page:

The CSRF token is missing from the DOM on the landing page, so the AJAX request fails CSRF validation.
5. Suggestion: Ensure that the main landing page (views/home.php) outputs the CSRF token in a hidden input with id="csrf-token-value" at the top of the page, just like on the products and product detail pages.
Next step:

Consider adding the CSRF token hidden input to home.php to ensure the global AJAX handler can always find it, which will fix the "Add to cart" error on the main landing page.

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
pete@pop-os:/cdrom/project/The-Scent-oa5
$ ls -l apache_logs/
total 20
-rw-r--r-- 1 root root 13361 Apr 23 11:44 apache-access.log
-rw-r--r-- 1 root root  2068 Apr 23 11:44 apache-error.log
pete@pop-os:/cdrom/project/The-Scent-oa5
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



---
# Issue resolved:      
1. the output HTML page from the "SHOP" tab on the header navigation bar with link 'https://the-scent.com/index.php?page=products' (shop_products.html) looks messy and does not show any product. It should list 15 products in a nice list format as how most beautifully designed e-commerce sites would list their products with typical product details. If there are more products matching user-selectable criteria for filtering (like newest or most popular sorting criteria), then have a list of pages in a bar at the bottom for the user to scroll through the pages or jump to a certain page.  
  
 - Observation: shop_products.html shows "Search Results for '1'" and "No products found...". The URL likely was index.php?page=products&search=1. Correct behavior when user click on the SHOP tab on the top header navigation bar, it should lead to a page listing all projects with a search criteria box for user to enter search string and a separate search filter dropdown list for filtering according to "Most Popular", "Most Recent" or whatever the popular filter terms maybe.  
 - Hypothesis: The ProductController::showProductList() method is receiving search=1 and correctly filtering, but no products match the name or description "1". The expected behavior (listing all products by default) isn't happening because the initial navigation click probably shouldn't include a search term.  
  
$ cd /cdrom/project/The-Scent-oa5  
  
$ curl -Lk https://the-scent.com/ -o current_landing_page.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 36778    0 36778    0     0  1049k      0 --:--:-- --:--:-- --:--:-- 1056k  
  
$ curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 42677    0 42677    0     0  3159k      0 --:--:-- --:--:-- --:--:-- 3205k  
  
$ curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 54981    0 54981    0     0  3085k      0 --:--:-- --:--:-- --:--:-- 3158k  
  
$ curl -Lk 'https://the-scent.com/index.php?page=contact' -o contact_page.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 15829    0 15829    0     0  1446k      0 --:--:-- --:--:-- --:--:-- 1545k  
  
$ curl -Lk 'https://the-scent.com/index.php?page=products&page_num=1' -o products_page_1.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 54981    0 54981    0     0  3699k      0 --:--:-- --:--:-- --:--:-- 3835k  
  
$ curl -Lk 'https://the-scent.com/index.php?page=products&page_num=2' -o products_page_2.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 54981    0 54981    0     0  3904k      0 --:--:-- --:--:-- --:--:-- 4130k  
  
$ ls -l apache_logs/  
total 8  
-rw-r--r-- 1 root root  662 Apr 23 10:23 apache-access.log  
-rw-r--r-- 1 root root 1016 Apr 23 10:23 apache-error.log  
  
$ tail -70 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'  
127.0.0.1 - - [23/Apr/2025:10:22:02 +0800] "GET / HTTP/1.1" 200 39483 "-" "curl/8.5.0"  
127.0.0.1 - - [23/Apr/2025:10:22:08 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 45448 "-" "curl/8.5.0"  
127.0.0.1 - - [23/Apr/2025:10:22:18 +0800] "GET /index.php?page=products HTTP/1.1" 200 57900 "-" "curl/8.5.0"  
127.0.0.1 - - [23/Apr/2025:10:22:27 +0800] "GET /index.php?page=contact HTTP/1.1" 200 18386 "-" "curl/8.5.0"  
127.0.0.1 - - [23/Apr/2025:10:23:28 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 57900 "-" "curl/8.5.0"  
127.0.0.1 - - [23/Apr/2025:10:23:36 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 57900 "-" "curl/8.5.0"  
  
$ cat apache_logs/apache-error.log  
[Wed Apr 23 10:21:21.257580 2025] [ssl:warn] [pid 229669] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)  
[Wed Apr 23 10:21:21.295752 2025] [ssl:warn] [pid 229670] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)  
[Wed Apr 23 10:22:18.889157 2025] [php:notice] [pid 229674] [client 127.0.0.1:37754] [showProductList] conditions: []  
[Wed Apr 23 10:22:18.889244 2025] [php:notice] [pid 229674] [client 127.0.0.1:37754] [showProductList] params: []  
[Wed Apr 23 10:23:28.353504 2025] [php:notice] [pid 229676] [client 127.0.0.1:58102] [showProductList] conditions: []  
[Wed Apr 23 10:23:28.353548 2025] [php:notice] [pid 229676] [client 127.0.0.1:58102] [showProductList] params: []  
[Wed Apr 23 10:23:36.192877 2025] [php:notice] [pid 229732] [client 127.0.0.1:58116] [showProductList] conditions: []  
[Wed Apr 23 10:23:36.192920 2025] [php:notice] [pid 229732] [client 127.0.0.1:58116] [showProductList] params: []  
  
---  
# Issue still outstanding:  
  
2. "Add to cart" button on the main landing page failed with the message "Error adding to cart". Is because there is no product ID passed as the product to add to the cart on both the main landing page or the product detail page ("https://the-scent.com/index.php?page=product&id=1") ?    
 
$ curl -Lk https://the-scent.com/ -o current_landing_page.html
curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html
curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html
curl -Lk 'https://the-scent.com/index.php?page=contact' -o contact_page.html
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=1' -o products_page_1.html
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=2' -o products_page_2.html
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 36778    0 36778    0     0   219k      0 --:--:-- --:--:-- --:--:--  220k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 42729    0 42729    0     0  2937k      0 --:--:-- --:--:-- --:--:-- 2781k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 54981    0 54981    0     0  2308k      0 --:--:-- --:--:-- --:--:-- 2334k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 15829    0 15829    0     0  1208k      0 --:--:-- --:--:-- --:--:-- 1288k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 54981    0 54981    0     0  3554k      0 --:--:-- --:--:-- --:--:-- 3579k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 54981    0 54981    0     0  5559k      0 --:--:-- --:--:-- --:--:-- 5965k
pete@pop-os:/cdrom/project/The-Scent-oa5
$ ls -l apache_logs/
total 8
-rw-r--r-- 1 root root  662 Apr 23 11:09 apache-access.log
-rw-r--r-- 1 root root 1016 Apr 23 11:09 apache-error.log
pete@pop-os:/cdrom/project/The-Scent-oa5
$ cat apache_logs/apache-error.log
[Wed Apr 23 11:07:40.931373 2025] [ssl:warn] [pid 234155] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Wed Apr 23 11:07:40.968192 2025] [ssl:warn] [pid 234156] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Wed Apr 23 11:09:19.388655 2025] [php:notice] [pid 234160] [client 127.0.0.1:48808] [showProductList] conditions: []
[Wed Apr 23 11:09:19.388723 2025] [php:notice] [pid 234160] [client 127.0.0.1:48808] [showProductList] params: []
[Wed Apr 23 11:09:19.493188 2025] [php:notice] [pid 234162] [client 127.0.0.1:48820] [showProductList] conditions: []
[Wed Apr 23 11:09:19.493234 2025] [php:notice] [pid 234162] [client 127.0.0.1:48820] [showProductList] params: []
[Wed Apr 23 11:09:19.542758 2025] [php:notice] [pid 234158] [client 127.0.0.1:48826] [showProductList] conditions: []
[Wed Apr 23 11:09:19.542790 2025] [php:notice] [pid 234158] [client 127.0.0.1:48826] [showProductList] params: []
 
