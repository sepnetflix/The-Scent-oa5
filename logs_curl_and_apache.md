$ curl -Lk https://the-scent.com/ -o current_landing_page.html      
curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html      
curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html      
curl -Lk 'https://the-scent.com/index.php?page=contact' -o contact_page.html      
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=1' -o products_page_1.html      
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=2' -o products_page_2.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 29487    0 29487    0     0   532k      0 --:--:-- --:--:-- --:--:--  543k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 27782    0 27782    0     0  1990k      0 --:--:-- --:--:-- --:--:-- 2086k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44193    0 44193    0     0  3057k      0 --:--:-- --:--:-- --:--:-- 3082k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100  9842    0  9842    0     0   996k      0 --:--:-- --:--:-- --:--:-- 1067k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44193    0 44193    0     0  3265k      0 --:--:-- --:--:-- --:--:-- 3319k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 22651    0 22651    0     0  2585k      0 --:--:-- --:--:-- --:--:-- 2765k

$ cat logs/security.log
[2025-04-26 01:48:05] [WARNING] Security-related error detected | {"type":"Compile Error","message":"Access level to AccountController::$emailService must be protected (as in class BaseController) or weaker","file":"\/cdrom\/project\/The-Scent-oa5\/controllers\/AccountController.php","line":13,"context":{"url":"\/index.php?page=login","method":"GET","ip":"127.0.0.1","timestamp":"2025-04-26 01:48:05"}}

$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos' 
127.0.0.1 - - [26/Apr/2025:09:45:43 +0800] "GET / HTTP/1.1" 200 32255 "-" "curl/8.5.0"
127.0.0.1 - - [26/Apr/2025:09:45:43 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 30550 "-" "curl/8.5.0"
127.0.0.1 - - [26/Apr/2025:09:45:43 +0800] "GET /index.php?page=products HTTP/1.1" 200 47109 "-" "curl/8.5.0"
127.0.0.1 - - [26/Apr/2025:09:45:43 +0800] "GET /index.php?page=contact HTTP/1.1" 200 12462 "-" "curl/8.5.0"
127.0.0.1 - - [26/Apr/2025:09:45:43 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 47109 "-" "curl/8.5.0"
127.0.0.1 - - [26/Apr/2025:09:45:43 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 25346 "-" "curl/8.5.0"
127.0.0.1 - - [26/Apr/2025:09:46:24 +0800] "GET / HTTP/1.1" 200 8036 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:46:24 +0800] "GET /css/style.css HTTP/1.1" 200 8319 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:46:24 +0800] "GET /js/main.js HTTP/1.1" 200 15367 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:46:25 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:46:26 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:46:38 +0800] "GET /index.php?page=products HTTP/1.1" 200 6208 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:46:38 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:46:38 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:46:54 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 6803 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:46:54 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:46:54 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:08 +0800] "GET /index.php?page=contact HTTP/1.1" 200 4052 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:08 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:08 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:15 +0800] "GET /index.php?page=faq HTTP/1.1" 200 5431 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:15 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:15 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:19 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 3703 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:19 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:19 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:23 +0800] "GET /index.php?page=order-tracking HTTP/1.1" 200 3616 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:24 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:24 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:28 +0800] "GET /index.php?page=privacy HTTP/1.1" 200 3678 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:28 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:28 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:31 +0800] "GET /index.php HTTP/1.1" 200 6228 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:31 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2472 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:31 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:42 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 8046 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:42 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:42 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:48 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 1183 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:48 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1064 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [26/Apr/2025:09:47:56 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [26/Apr/2025:09:47:59 +0800] "GET /index.php?page=cart HTTP/1.1" 200 5602 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:59 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1064 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:47:59 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:48:05 +0800] "GET /index.php?page=checkout HTTP/1.1" 302 1094 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:48:05 +0800] "GET /index.php?page=login HTTP/1.1" 500 9805 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:48:05 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2729 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:09:48:06 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"

$ cat apache_logs/apache-error.log
[Sat Apr 26 09:40:01.482213 2025] [ssl:warn] [pid 363949] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Sat Apr 26 09:40:01.518073 2025] [ssl:warn] [pid 363950] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Sat Apr 26 09:48:05.829704 2025] [php:error] [pid 363952] [client 127.0.0.1:37902] PHP Fatal error:  Access level to AccountController::$emailService must be protected (as in class BaseController) or weaker in /cdrom/project/The-Scent-oa5/controllers/AccountController.php on line 13, referer: https://the-scent.com/index.php?page=cart
[Sat Apr 26 09:48:05.829894 2025] [php:notice] [pid 363952] [client 127.0.0.1:37902] [2025-04-26 01:48:05] Compile Error: Access level to AccountController::$emailService must be protected (as in class BaseController) or weaker in /cdrom/project/The-Scent-oa5/controllers/AccountController.php on line 13\nContext: {"url":"\\/index.php?page=login","method":"GET","ip":"127.0.0.1","timestamp":"2025-04-26 01:48:05"}, referer: https://the-scent.com/index.php?page=cart

