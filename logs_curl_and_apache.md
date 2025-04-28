$ curl -Lk https://the-scent.com/ -o current_landing_page.html                
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
100 29487    0 29487    0     0   909k      0 --:--:-- --:--:-- --:--:--  928k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 27782    0 27782    0     0  2247k      0 --:--:-- --:--:-- --:--:-- 2466k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 44219    0 44219    0     0  3898k      0 --:--:-- --:--:-- --:--:-- 4318k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100  9854    0  9854    0     0  1046k      0 --:--:-- --:--:-- --:--:-- 1069k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 44219    0 44219    0     0  4341k      0 --:--:-- --:--:-- --:--:-- 4798k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 22641    0 22641    0     0  2338k      0 --:--:-- --:--:-- --:--:-- 2456k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 14541    0 14541    0     0  1846k      0 --:--:-- --:--:-- --:--:-- 2028k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 12714    0 12714    0     0   763k      0 --:--:-- --:--:-- --:--:--  776k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 16494    0 16494    0     0  1984k      0 --:--:-- --:--:-- --:--:-- 2013k  
  
$ cat apache_logs/apache-error.log   
[Mon Apr 28 19:41:50.015713 2025] [ssl:warn] [pid 486222] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)  
[Mon Apr 28 19:41:50.053913 2025] [ssl:warn] [pid 486223] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)  
  
$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'  
127.0.0.1 - - [28/Apr/2025:19:42:12 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"  
127.0.0.1 - - [28/Apr/2025:19:42:38 +0800] "GET / HTTP/1.1" 200 32496 "-" "curl/8.5.0"  
127.0.0.1 - - [28/Apr/2025:19:42:38 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 30790 "-" "curl/8.5.0"  
127.0.0.1 - - [28/Apr/2025:19:42:38 +0800] "GET /index.php?page=products HTTP/1.1" 200 47331 "-" "curl/8.5.0"  
127.0.0.1 - - [28/Apr/2025:19:42:38 +0800] "GET /index.php?page=contact HTTP/1.1" 200 12758 "-" "curl/8.5.0"  
127.0.0.1 - - [28/Apr/2025:19:42:38 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 47331 "-" "curl/8.5.0"  
127.0.0.1 - - [28/Apr/2025:19:42:38 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 25642 "-" "curl/8.5.0"  
127.0.0.1 - - [28/Apr/2025:19:42:38 +0800] "GET /index.php?page=about HTTP/1.1" 200 17446 "-" "curl/8.5.0"  
127.0.0.1 - - [28/Apr/2025:19:42:38 +0800] "GET /index.php?page=login HTTP/1.1" 200 15619 "-" "curl/8.5.0"  
127.0.0.1 - - [28/Apr/2025:19:42:38 +0800] "GET /index.php?page=register HTTP/1.1" 200 19476 "-" "curl/8.5.0"  
127.0.0.1 - - [28/Apr/2025:19:44:19 +0800] "GET / HTTP/1.1" 200 8322 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:19 +0800] "GET /css/style.css HTTP/1.1" 200 8319 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:19 +0800] "GET /js/main.js HTTP/1.1" 200 17032 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:19 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:19 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:28 +0800] "GET /index.php?page=contact HTTP/1.1" 200 5685 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:28 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:28 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:30 +0800] "GET /index.php?page=faq HTTP/1.1" 200 4057 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:30 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:30 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:31 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 3993 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:31 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:31 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:34 +0800] "GET /index.php?page=order-tracking HTTP/1.1" 200 3910 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:34 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:35 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:36 +0800] "GET /index.php?page=privacy HTTP/1.1" 200 3970 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:36 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:36 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:37 +0800] "GET /index.php?page=about HTTP/1.1" 200 5386 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:37 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:37 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:43 +0800] "GET /index.php?page=contact HTTP/1.1" 200 5685 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:43 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:43 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:44 +0800] "GET /index.php?page=quiz HTTP/1.1" 200 4383 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:44 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:44 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:50 +0800] "GET /index.php?page=products HTTP/1.1" 200 6503 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:50 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:44:50 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:02 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 7093 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:02 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:02 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:09 +0800] "GET /index.php HTTP/1.1" 200 6838 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:09 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2808 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:09 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:15 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 6669 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:15 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:15 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:22 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 1513 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:22 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1260 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:26 +0800] "GET /index.php?page=cart HTTP/1.1" 200 5312 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:26 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1260 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:26 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:34 +0800] "GET /index.php?page=checkout HTTP/1.1" 302 2719 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:34 +0800] "GET /index.php?page=login HTTP/1.1" 200 4685 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:34 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1260 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:45:34 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:46:22 +0800] "POST /index.php?page=login HTTP/1.1" 200 1605 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:46:23 +0800] "GET /index.php?page=checkout HTTP/1.1" 200 9603 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:46:23 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1260 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [28/Apr/2025:19:46:23 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
  
