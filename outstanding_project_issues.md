$ curl -Lk https://the-scent.com/ -o current_landing_page.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 36593    0 36593    0     0  2976k      0 --:--:-- --:--:-- --:--:-- 3248k  
  
$ curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 42503    0 42503    0     0  3880k      0 --:--:-- --:--:-- --:--:-- 4150k  
  
$ curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 24158    0 24158    0     0  2317k      0 --:--:-- --:--:-- --:--:-- 2359k  
  
  
# Issues found:    
1. the output HTML page from the link 'https://the-scent.com/index.php?page=products' (shop_products.html) looks messy and not consistent with the UI appearance of the main landing page or the individual product detail page.  
  
2. "Add to cart" button on the main landing page failed with the message "Error adding to cart". Is because there is no product ID passed as the product to add to the cart on both the main landing page or the product detail page ("https://the-scent.com/index.php?page=product&id=1") ?  
  
$ tail -40 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'   
127.0.0.1 - - [22/Apr/2025:15:22:23 +0800] "GET / HTTP/1.1" 200 39298 "-" "curl/8.5.0"  
127.0.0.1 - - [22/Apr/2025:15:22:38 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 45274 "-" "curl/8.5.0"  
127.0.0.1 - - [22/Apr/2025:15:23:03 +0800] "GET /index.php?page=products HTTP/1.1" 200 26789 "-" "curl/8.5.0"  
127.0.0.1 - - [22/Apr/2025:15:26:20 +0800] "GET / HTTP/1.1" 200 9634 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:15:26:20 +0800] "GET /css/style.css HTTP/1.1" 200 6843 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:15:26:20 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
::1 - - [22/Apr/2025:15:26:28 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"  
127.0.0.1 - - [22/Apr/2025:15:26:32 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 9432 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:15:26:32 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:15:26:40 +0800] "GET /index.php?page=products HTTP/1.1" 200 7555 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:15:26:40 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
::1 - - [22/Apr/2025:15:26:48 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"  
127.0.0.1 - - [22/Apr/2025:15:26:55 +0800] "GET /index.php HTTP/1.1" 200 8150 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:15:26:56 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:15:27:01 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 400 702 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:15:27:08 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 400 2366 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [22/Apr/2025:15:27:16 +0800] "-" 408 1664 "-" "-"  
  
