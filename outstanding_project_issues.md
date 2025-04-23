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
  
$ tail -70 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'  
127.0.0.1 - - [23/Apr/2025:10:22:02 +0800] "GET / HTTP/1.1" 200 39483 "-" "curl/8.5.0"  
127.0.0.1 - - [23/Apr/2025:10:22:08 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 45448 "-" "curl/8.5.0"  
127.0.0.1 - - [23/Apr/2025:10:22:18 +0800] "GET /index.php?page=products HTTP/1.1" 200 57900 "-" "curl/8.5.0"  
127.0.0.1 - - [23/Apr/2025:10:22:27 +0800] "GET /index.php?page=contact HTTP/1.1" 200 18386 "-" "curl/8.5.0"  
127.0.0.1 - - [23/Apr/2025:10:23:28 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 57900 "-" "curl/8.5.0"  
127.0.0.1 - - [23/Apr/2025:10:23:36 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 57900 "-" "curl/8.5.0"  
127.0.0.1 - - [23/Apr/2025:10:25:04 +0800] "GET / HTTP/1.1" 200 9682 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:04 +0800] "GET /css/style.css HTTP/1.1" 200 6843 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:07 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:18 +0800] "GET /index.php?page=contact HTTP/1.1" 200 5405 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:18 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:19 +0800] "GET /index.php?page=faq HTTP/1.1" 200 5111 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:20 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:21 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 5032 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:21 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:23 +0800] "GET /index.php?page=order-tracking HTTP/1.1" 200 4968 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:23 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:25 +0800] "GET /index.php?page=privacy HTTP/1.1" 200 5017 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:25 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:29 +0800] "GET /index.php?page=products HTTP/1.1" 200 7735 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:29 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:36 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 9400 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:36 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:42 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 8059 "https://the-scent.com/index.php?page=products&page_num=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:25:42 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:28:40 +0800] "GET /index.php HTTP/1.1" 200 9539 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:28:41 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:28:47 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 9160 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:28:48 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:28:49 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 827 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:28:55 +0800] "GET /index.php HTTP/1.1" 200 8198 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:28:55 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [23/Apr/2025:10:29:02 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 827 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
pete@pop-os:/cdrom/project/The-Scent-oa5  
$ cat apache_logs/apache-error.log  
[Wed Apr 23 10:21:21.257580 2025] [ssl:warn] [pid 229669] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)  
[Wed Apr 23 10:21:21.295752 2025] [ssl:warn] [pid 229670] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)  
[Wed Apr 23 10:22:18.889157 2025] [php:notice] [pid 229674] [client 127.0.0.1:37754] [showProductList] conditions: []  
[Wed Apr 23 10:22:18.889244 2025] [php:notice] [pid 229674] [client 127.0.0.1:37754] [showProductList] params: []  
[Wed Apr 23 10:23:28.353504 2025] [php:notice] [pid 229676] [client 127.0.0.1:58102] [showProductList] conditions: []  
[Wed Apr 23 10:23:28.353548 2025] [php:notice] [pid 229676] [client 127.0.0.1:58102] [showProductList] params: []  
[Wed Apr 23 10:23:36.192877 2025] [php:notice] [pid 229732] [client 127.0.0.1:58116] [showProductList] conditions: []  
[Wed Apr 23 10:23:36.192920 2025] [php:notice] [pid 229732] [client 127.0.0.1:58116] [showProductList] params: []  
[Wed Apr 23 10:25:29.642294 2025] [php:notice] [pid 229673] [client 127.0.0.1:50184] [showProductList] conditions: [], referer: https://the-scent.com/index.php?page=privacy  
[Wed Apr 23 10:25:29.642350 2025] [php:notice] [pid 229673] [client 127.0.0.1:50184] [showProductList] params: [], referer: https://the-scent.com/index.php?page=privacy  
[Wed Apr 23 10:25:36.576907 2025] [php:notice] [pid 229676] [client 127.0.0.1:60408] [showProductList] conditions: [], referer: https://the-scent.com/index.php?page=products  
[Wed Apr 23 10:25:36.576942 2025] [php:notice] [pid 229676] [client 127.0.0.1:60408] [showProductList] params: [], referer: https://the-scent.com/index.php?page=products  
[Wed Apr 23 10:25:42.413100 2025] [php:notice] [pid 229674] [client 127.0.0.1:48556] [showProductList] conditions: [], referer: https://the-scent.com/index.php?page=products&page_num=1  
[Wed Apr 23 10:25:42.413135 2025] [php:notice] [pid 229674] [client 127.0.0.1:48556] [showProductList] params: [], referer: https://the-scent.com/index.php?page=products&page_num=1  
  
