 curl -Lk https://the-scent.com/ -o current_landing_page.html          
curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html          
curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html          
curl -Lk 'https://the-scent.com/index.php?page=contact' -o contact_page.html          
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=1' -o products_page_1.html          
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=2' -o products_page_2.html    
curl -Lk 'https://the-scent.com/index.php?page=about' -o about_page.html    
curl -Lk 'https://the-scent.com/index.php?page=login' -o login_page.html  
curl -Lk 'https://the-scent.com/index.php?page=register' -o register_page.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 29487    0 29487    0     0   741k      0 --:--:-- --:--:-- --:--:--  757k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 27782    0 27782    0     0  1981k      0 --:--:-- --:--:-- --:--:-- 2086k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 44193    0 44193    0     0  3669k      0 --:--:-- --:--:-- --:--:-- 3923k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100  9842    0  9842    0     0   993k      0 --:--:-- --:--:-- --:--:-- 1067k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 44193    0 44193    0     0  3524k      0 --:--:-- --:--:-- --:--:-- 3596k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 22651    0 22651    0     0  1913k      0 --:--:-- --:--:-- --:--:-- 2010k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 14541    0 14541    0     0  1974k      0 --:--:-- --:--:-- --:--:-- 2028k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 12714    0 12714    0     0   864k      0 --:--:-- --:--:-- --:--:--  886k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 16494    0 16494    0     0  2009k      0 --:--:-- --:--:-- --:--:-- 2013k  
  
$ cat apache_logs/apache-error.log  
[Sun Apr 27 07:21:34.151648 2025] [ssl:warn] [pid 394922] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)  
[Sun Apr 27 07:21:34.189394 2025] [ssl:warn] [pid 394923] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)  
  
$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'  
127.0.0.1 - - [27/Apr/2025:07:21:53 +0800] "GET / HTTP/1.1" 200 32255 "-" "curl/8.5.0"  
127.0.0.1 - - [27/Apr/2025:07:21:53 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 30550 "-" "curl/8.5.0"  
127.0.0.1 - - [27/Apr/2025:07:21:53 +0800] "GET /index.php?page=products HTTP/1.1" 200 47109 "-" "curl/8.5.0"  
127.0.0.1 - - [27/Apr/2025:07:21:53 +0800] "GET /index.php?page=contact HTTP/1.1" 200 12462 "-" "curl/8.5.0"  
127.0.0.1 - - [27/Apr/2025:07:21:53 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 47109 "-" "curl/8.5.0"  
127.0.0.1 - - [27/Apr/2025:07:21:53 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 25346 "-" "curl/8.5.0"  
127.0.0.1 - - [27/Apr/2025:07:21:53 +0800] "GET /index.php?page=about HTTP/1.1" 200 17162 "-" "curl/8.5.0"  
127.0.0.1 - - [27/Apr/2025:07:21:53 +0800] "GET /index.php?page=login HTTP/1.1" 200 15327 "-" "curl/8.5.0"  
127.0.0.1 - - [27/Apr/2025:07:21:53 +0800] "GET /index.php?page=register HTTP/1.1" 200 19107 "-" "curl/8.5.0"  
127.0.0.1 - - [27/Apr/2025:07:22:31 +0800] "GET / HTTP/1.1" 200 8037 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:22:31 +0800] "GET /css/style.css HTTP/1.1" 200 8319 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:22:31 +0800] "GET /js/main.js HTTP/1.1" 200 15367 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:22:32 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:22:32 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
::1 - - [27/Apr/2025:07:22:42 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"  
127.0.0.1 - - [27/Apr/2025:07:22:42 +0800] "GET /index.php?page=products HTTP/1.1" 200 7553 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:22:42 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:22:42 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:22:48 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 5463 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:22:48 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:22:48 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:22:54 +0800] "GET /index.php HTTP/1.1" 200 7894 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:22:54 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:22:54 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:00 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 859 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:00 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1064 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:01 +0800] "GET /index.php?page=product&id=2 HTTP/1.1" 200 6381 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:01 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1064 "https://the-scent.com/index.php?page=product&id=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:01 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:14 +0800] "-" 408 323 "-" "-"  
127.0.0.1 - - [27/Apr/2025:07:23:14 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 1183 "https://the-scent.com/index.php?page=product&id=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:15 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1064 "https://the-scent.com/index.php?page=product&id=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
::1 - - [27/Apr/2025:07:23:22 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"  
127.0.0.1 - - [27/Apr/2025:07:23:26 +0800] "GET /index.php?page=cart HTTP/1.1" 200 6703 "https://the-scent.com/index.php?page=product&id=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:26 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1064 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:26 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:40 +0800] "GET /index.php?page=checkout HTTP/1.1" 302 1094 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:40 +0800] "GET /index.php?page=login HTTP/1.1" 200 4401 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:40 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1064 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:41 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:47 +0800] "GET /index.php?page=register HTTP/1.1" 200 6592 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:47 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1064 "https://the-scent.com/index.php?page=register" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:23:47 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=register" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [27/Apr/2025:07:25:26 +0800] "POST /index.php?page=register HTTP/1.1" 503 1108 "https://the-scent.com/index.php?page=register" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
  
