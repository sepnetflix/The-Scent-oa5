$ curl -Lk https://the-scent.com/ -o current_landing_page.html    
curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html    
curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html    
curl -Lk 'https://the-scent.com/index.php?page=contact' -o contact_page.html    
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=1' -o products_page_1.html    
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=2' -o products_page_2.html 
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 28246    0 28246    0     0   869k      0 --:--:-- --:--:-- --:--:--  889k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 26516    0 26516    0     0  1697k      0 --:--:-- --:--:-- --:--:-- 1726k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 42927    0 42927    0     0  2904k      0 --:--:-- --:--:-- --:--:-- 2994k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100  8576    0  8576    0     0   644k      0 --:--:-- --:--:-- --:--:--  697k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 42927    0 42927    0     0  3476k      0 --:--:-- --:--:-- --:--:-- 3810k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 21385    0 21385    0     0  2264k      0 --:--:-- --:--:-- --:--:-- 2320k

$ tail -70 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos' 
127.0.0.1 - - [25/Apr/2025:21:20:30 +0800] "GET / HTTP/1.1" 200 31150 "-" "curl/8.5.0"
127.0.0.1 - - [25/Apr/2025:21:20:30 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 29486 "-" "curl/8.5.0"
127.0.0.1 - - [25/Apr/2025:21:20:30 +0800] "GET /index.php?page=products HTTP/1.1" 200 46052 "-" "curl/8.5.0"
127.0.0.1 - - [25/Apr/2025:21:20:30 +0800] "GET /index.php?page=contact HTTP/1.1" 200 11398 "-" "curl/8.5.0"
127.0.0.1 - - [25/Apr/2025:21:20:30 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 46052 "-" "curl/8.5.0"
127.0.0.1 - - [25/Apr/2025:21:20:30 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 24289 "-" "curl/8.5.0"
127.0.0.1 - - [25/Apr/2025:21:21:06 +0800] "GET / HTTP/1.1" 200 7889 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:06 +0800] "GET /js/main.js HTTP/1.1" 200 6359 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:06 +0800] "GET /css/style.css HTTP/1.1" 200 9984 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:07 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:17 +0800] "GET /index.php?page=contact HTTP/1.1" 200 3875 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:17 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:19 +0800] "GET /index.php?page=faq HTTP/1.1" 200 3568 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:19 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:21 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 3501 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:21 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:22 +0800] "GET /index.php?page=order-tracking HTTP/1.1" 200 3432 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:22 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:24 +0800] "GET /index.php?page=privacy HTTP/1.1" 200 3479 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:24 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:27 +0800] "GET /index.php?page=products HTTP/1.1" 200 5729 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:27 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:48 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 6636 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:21:48 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:22:04 +0800] "GET /index.php HTTP/1.1" 200 6405 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:22:04 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:22:14 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 2733 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:22:14 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 2733 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:22:24 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 6537 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:22:24 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:22:32 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 2733 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:22:32 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 1392 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:22:39 +0800] "GET /index.php?page=cart HTTP/1.1" 200 5446 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:22:39 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:22:47 +0800] "GET /index.php?page=cart HTTP/1.1" 200 4105 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:22:47 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:22:49 +0800] "GET /index.php?page=cart HTTP/1.1" 200 3781 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:21:22:49 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"

$ cat apache_logs/apache-error.log 
[Fri Apr 25 21:20:07.768233 2025] [ssl:warn] [pid 330127] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Fri Apr 25 21:20:07.805298 2025] [ssl:warn] [pid 330128] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)

