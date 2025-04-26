$ curl -Lk https://the-scent.com/ -o current_landing_page.html        
curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html        
curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html        
curl -Lk 'https://the-scent.com/index.php?page=contact' -o contact_page.html        
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=1' -o products_page_1.html        
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=2' -o products_page_2.html  
curl -Lk 'https://the-scent.com/index.php?page=about' -o about_page.html  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 29487    0 29487    0     0   859k      0 --:--:-- --:--:-- --:--:--  872k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 27782    0 27782    0     0  2004k      0 --:--:-- --:--:-- --:--:-- 2086k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 44193    0 44193    0     0  2935k      0 --:--:-- --:--:-- --:--:-- 3082k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100  9842    0  9842    0     0   977k      0 --:--:-- --:--:-- --:--:-- 1067k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 44193    0 44193    0     0  2823k      0 --:--:-- --:--:-- --:--:-- 2877k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 22651    0 22651    0     0  2039k      0 --:--:-- --:--:-- --:--:-- 2212k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 14541    0 14541    0     0  1850k      0 --:--:-- --:--:-- --:--:-- 2028k  
  
$ curl -Lk 'https://the-scent.com/index.php?page=login' -o login_page.html   
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 12558    0 12558    0     0   982k      0 --:--:-- --:--:-- --:--:-- 1021k  
  
$ cat apache_logs/apache-error.log  
[Sat Apr 26 19:29:12.773874 2025] [ssl:warn] [pid 382968] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)  
[Sat Apr 26 19:29:12.809851 2025] [ssl:warn] [pid 382969] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)  
  
$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'   
127.0.0.1 - - [26/Apr/2025:19:31:00 +0800] "GET / HTTP/1.1" 200 32255 "-" "curl/8.5.0"  
127.0.0.1 - - [26/Apr/2025:19:31:00 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 30550 "-" "curl/8.5.0"  
127.0.0.1 - - [26/Apr/2025:19:31:00 +0800] "GET /index.php?page=products HTTP/1.1" 200 47109 "-" "curl/8.5.0"  
127.0.0.1 - - [26/Apr/2025:19:31:00 +0800] "GET /index.php?page=contact HTTP/1.1" 200 12462 "-" "curl/8.5.0"  
127.0.0.1 - - [26/Apr/2025:19:31:01 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 47109 "-" "curl/8.5.0"  
127.0.0.1 - - [26/Apr/2025:19:31:01 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 25346 "-" "curl/8.5.0"  
127.0.0.1 - - [26/Apr/2025:19:31:01 +0800] "GET /index.php?page=about HTTP/1.1" 200 17162 "-" "curl/8.5.0"  
127.0.0.1 - - [26/Apr/2025:19:31:30 +0800] "GET /index.php?page=login HTTP/1.1" 200 15171 "-" "curl/8.5.0"  
127.0.0.1 - - [26/Apr/2025:19:32:26 +0800] "GET / HTTP/1.1" 200 8037 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:32:26 +0800] "GET /css/style.css HTTP/1.1" 200 8319 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:32:26 +0800] "GET /js/main.js HTTP/1.1" 200 15367 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:32:27 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:32:27 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:32:41 +0800] "GET /index.php?page=about HTTP/1.1" 200 6765 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:32:42 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:32:42 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:32:57 +0800] "GET /index.php?page=products HTTP/1.1" 200 6211 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:32:57 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:32:57 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:09 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 6804 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:09 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:09 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:17 +0800] "GET /index.php?page=contact HTTP/1.1" 200 4053 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:17 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:17 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:19 +0800] "GET /index.php?page=faq HTTP/1.1" 200 3768 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:19 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:19 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:21 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 3704 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:21 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:21 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:23 +0800] "GET /index.php?page=order-tracking HTTP/1.1" 200 3618 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:23 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:23 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:25 +0800] "GET /index.php?page=privacy HTTP/1.1" 200 3679 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:25 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:25 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:30 +0800] "GET /index.php?page=contact HTTP/1.1" 200 5394 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:31 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:31 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:35 +0800] "GET /index.php?page=quiz HTTP/1.1" 200 4099 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:35 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:35 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:38 +0800] "GET /index.php HTTP/1.1" 200 6229 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:38 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2472 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:38 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:44 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 6386 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:44 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:45 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:48 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 859 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:48 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1064 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:55 +0800] "GET /index.php HTTP/1.1" 200 7891 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:55 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2729 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:33:55 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:34:01 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 2524 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:34:01 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1064 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:34:10 +0800] "GET /index.php?page=cart HTTP/1.1" 200 5362 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:34:11 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1064 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:34:11 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:34:15 +0800] "GET /index.php?page=login HTTP/1.1" 200 4366 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:34:15 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1064 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [26/Apr/2025:19:34:15 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
  
