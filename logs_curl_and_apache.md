$ curl -Lk https://the-scent.com/ -o current_landing_page.html      
curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html      
curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html      
curl -Lk 'https://the-scent.com/index.php?page=contact' -o contact_page.html      
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=1' -o products_page_1.html      
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=2' -o products_page_2.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 28246    0 28246    0     0   915k      0 --:--:-- --:--:-- --:--:--  951k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 26516    0 26516    0     0  1847k      0 --:--:-- --:--:-- --:--:-- 1991k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 42927    0 42927    0     0  3550k      0 --:--:-- --:--:-- --:--:-- 3810k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100  8576    0  8576    0     0   846k      0 --:--:-- --:--:-- --:--:--  930k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 42927    0 42927    0     0  3283k      0 --:--:-- --:--:-- --:--:-- 3493k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 21385    0 21385    0     0  2455k      0 --:--:-- --:--:-- --:--:-- 2610k  
  
$ tail -70 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'  
127.0.0.1 - - [25/Apr/2025:21:41:47 +0800] "GET / HTTP/1.1" 200 31150 "-" "curl/8.5.0"  
127.0.0.1 - - [25/Apr/2025:21:41:47 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 29486 "-" "curl/8.5.0"  
127.0.0.1 - - [25/Apr/2025:21:41:47 +0800] "GET /index.php?page=products HTTP/1.1" 200 46052 "-" "curl/8.5.0"  
127.0.0.1 - - [25/Apr/2025:21:41:47 +0800] "GET /index.php?page=contact HTTP/1.1" 200 11398 "-" "curl/8.5.0"  
127.0.0.1 - - [25/Apr/2025:21:41:47 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 46052 "-" "curl/8.5.0"  
127.0.0.1 - - [25/Apr/2025:21:41:48 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 24289 "-" "curl/8.5.0"  
127.0.0.1 - - [25/Apr/2025:21:42:44 +0800] "GET / HTTP/1.1" 200 7886 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:42:44 +0800] "GET /js/main.js HTTP/1.1" 200 6359 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:42:44 +0800] "GET /css/style.css HTTP/1.1" 200 8319 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:42:45 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:42:50 +0800] "GET /index.php?page=products HTTP/1.1" 200 5725 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:42:50 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:42:59 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 5293 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:43:00 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:43:10 +0800] "GET /index.php HTTP/1.1" 200 7743 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:43:10 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:43:13 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 6213 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:43:13 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:43:20 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 1392 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:43:20 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 2733 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:43:37 +0800] "GET /index.php HTTP/1.1" 200 6402 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:43:37 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:43:44 +0800] "GET /index.php?page=cart HTTP/1.1" 200 3780 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:43:44 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:43:55 +0800] "GET /index.php HTTP/1.1" 200 7743 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:43:55 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:44:06 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 2733 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:44:06 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 2733 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:44:11 +0800] "GET /index.php?page=cart HTTP/1.1" 200 3780 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:21:44:11 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
  
$ cat apache_logs/apache-error.log   
[Fri Apr 25 21:41:16.451393 2025] [ssl:warn] [pid 332779] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)  
[Fri Apr 25 21:41:16.488469 2025] [ssl:warn] [pid 332780] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)  
  
