$ curl -Lk https://the-scent.com/ -o current_landing_page.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 36778    0 36778    0     0  1007k      0 --:--:-- --:--:-- --:--:-- 1026k  
  
$ curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 42677    0 42677    0     0  2432k      0 --:--:-- --:--:-- --:--:-- 2451k  
  
$ curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 24492    0 24492    0     0  1770k      0 --:--:-- --:--:-- --:--:-- 1839k  
  
$ ls -l apache_logs/  
total 8  
-rw-r--r-- 1 root root 311 Apr 22 20:16 apache-access.log  
-rw-r--r-- 1 root root 320 Apr 22 20:14 apache-error.log  

  
# Issues found:    
1. the output HTML page from the "SHOP" tab on the header navigation bar with link 'https://the-scent.com/index.php?page=products' (shop_products.html) looks messy and does not show any product. It should list 15 products in a nice list format as how most beautifully designed e-commerce sites would list their products with typical product details. If there are more products matching user-selectable criteria for filtering (like newest or most popular sorting criteria), then have a list of pages in a bar at the bottom for the user to scroll through the pages or jump to a certain page.
  
2. "Add to cart" button on the main landing page failed with the message "Error adding to cart". Is because there is no product ID passed as the product to add to the cart on both the main landing page or the product detail page ("https://the-scent.com/index.php?page=product&id=1") ?  

$ tail -70 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'  
127.0.0.1 - - [22/Apr/2025:20:15:36 +0800] "GET / HTTP/1.1" 200 39483 "-" "curl/8.5.0"  
127.0.0.1 - - [22/Apr/2025:20:15:57 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 45448 "-" "curl/8.5.0"  
127.0.0.1 - - [22/Apr/2025:20:16:21 +0800] "GET /index.php?page=products HTTP/1.1" 200 27123 "-" "curl/8.5.0"  
127.0.0.1 - - [22/Apr/2025:20:19:52 +0800] "GET / HTTP/1.1" 200 9679 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:20:19:52 +0800] "GET /css/style.css HTTP/1.1" 200 6843 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:20:19:53 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
::1 - - [22/Apr/2025:20:20:00 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"  
::1 - - [22/Apr/2025:20:20:01 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"  
127.0.0.1 - - [22/Apr/2025:20:20:05 +0800] "GET /index.php?page=products HTTP/1.1" 200 6294 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:20:20:06 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:20:20:21 +0800] "GET /index.php?page=products&sort=price_asc HTTP/1.1" 200 7635 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:20:20:21 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&sort=price_asc" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:20:20:37 +0800] "GET /index.php HTTP/1.1" 200 8195 "https://the-scent.com/index.php?page=products&sort=price_asc" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:20:20:37 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:20:20:45 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 10828 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:20:20:45 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:20:20:47 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 400 702 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:20:20:54 +0800] "GET /index.php HTTP/1.1" 200 9536 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:20:20:55 +0800] "GET /favicon.ico HTTP/1.1" 200 2318 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:20:21:01 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 400 1025 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
  
