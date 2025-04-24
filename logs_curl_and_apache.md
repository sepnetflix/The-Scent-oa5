$ curl -Lk https://the-scent.com/ -o current_landing_page.html
curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html
curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html
curl -Lk 'https://the-scent.com/index.php?page=contact' -o contact_page.html
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=1' -o products_page_1.html
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=2' -o products_page_2.html
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 40531    0 40531    0     0  1196k      0 --:--:-- --:--:-- --:--:-- 1236k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 46482    0 46482    0     0  2990k      0 --:--:-- --:--:-- --:--:-- 3026k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 55239    0 55239    0     0  1852k      0 --:--:-- --:--:-- --:--:-- 1860k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 19582    0 19582    0     0  1972k      0 --:--:-- --:--:-- --:--:-- 2124k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 55239    0 55239    0     0  3898k      0 --:--:-- --:--:-- --:--:-- 4149k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 55239    0 55239    0     0  5519k      0 --:--:-- --:--:-- --:--:-- 5993k

$ cat apache_logs/apache-access.log 
127.0.0.1 - - [24/Apr/2025:20:18:09 +0800] "GET / HTTP/1.1" 200 43575 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:18:09 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 49526 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:18:10 +0800] "GET /index.php?page=products HTTP/1.1" 200 58431 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:18:10 +0800] "GET /index.php?page=contact HTTP/1.1" 200 22478 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:18:10 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 58431 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:18:10 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 58431 "-" "curl/8.5.0"

$ cat apache_logs/apache-error.log
[Thu Apr 24 20:17:12.391297 2025] [ssl:warn] [pid 281196] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Thu Apr 24 20:17:12.429293 2025] [ssl:warn] [pid 281197] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)

$ cat apache_logs/apache-access.log 
127.0.0.1 - - [24/Apr/2025:20:18:09 +0800] "GET / HTTP/1.1" 200 43575 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:18:09 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 49526 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:18:10 +0800] "GET /index.php?page=products HTTP/1.1" 200 58431 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:18:10 +0800] "GET /index.php?page=contact HTTP/1.1" 200 22478 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:18:10 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 58431 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:18:10 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 58431 "-" "curl/8.5.0"
127.0.0.1 - - [24/Apr/2025:20:19:25 +0800] "GET / HTTP/1.1" 200 10893 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:25 +0800] "GET /css/style.css HTTP/1.1" 200 6843 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:25 +0800] "GET /images/scent5.jpg HTTP/1.1" 200 76794 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:25 +0800] "GET /images/about.jpg HTTP/1.1" 200 459466 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:25 +0800] "GET /images/products/1.jpg HTTP/1.1" 200 249956 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:25 +0800] "GET /images/products/2.jpg HTTP/1.1" 200 172498 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:25 +0800] "GET /videos/hero.mp4 HTTP/1.1" 206 136889 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:25 +0800] "GET /videos/hero.mp4 HTTP/1.1" 206 72145 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:25 +0800] "GET /images/products/4.jpg HTTP/1.1" 200 878198 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:25 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:29 +0800] "GET /images/products/6.jpg HTTP/1.1" 200 114397 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:29 +0800] "GET /images/products/9.jpg HTTP/1.1" 200 138359 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:29 +0800] "GET /images/products/7.jpg HTTP/1.1" 200 168060 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:34 +0800] "GET /index.php?page=contact HTTP/1.1" 200 6582 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:25 +0800] "GET /videos/hero.mp4 HTTP/1.1" 206 15668559 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:34 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:38 +0800] "GET /index.php?page=faq HTTP/1.1" 200 6286 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:38 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:40 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 6207 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:40 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:41 +0800] "GET /index.php?page=order-tracking HTTP/1.1" 200 6142 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:41 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:42 +0800] "GET /index.php?page=privacy HTTP/1.1" 200 6190 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:42 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:47 +0800] "GET /index.php?page=products HTTP/1.1" 200 8978 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:47 +0800] "GET /images/placeholder.jpg HTTP/1.1" 200 105855 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:19:47 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:20:27 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 10643 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:20:27 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:20:47 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 9302 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:20:47 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:20:55 +0800] "GET /index.php?page=product&id=7 HTTP/1.1" 200 11880 "https://the-scent.com/index.php?page=products&page_num=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:20:55 +0800] "GET /images/products/1.jpg HTTP/1.1" 200 249956 "https://the-scent.com/index.php?page=product&id=7" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:20:55 +0800] "GET /images/products/2.jpg HTTP/1.1" 200 170833 "https://the-scent.com/index.php?page=product&id=7" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:20:55 +0800] "GET /images/products/3.jpg HTTP/1.1" 200 362084 "https://the-scent.com/index.php?page=product&id=7" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:20:55 +0800] "GET /images/products/4.jpg HTTP/1.1" 200 879863 "https://the-scent.com/index.php?page=product&id=7" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:20:55 +0800] "GET /images/products/5.jpg HTTP/1.1" 200 158614 "https://the-scent.com/index.php?page=product&id=7" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:20:55 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=7" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:21:06 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 1392 "https://the-scent.com/index.php?page=product&id=7" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [24/Apr/2025:20:21:06 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart&action=add" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [24/Apr/2025:20:21:13 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"

$ cat apache_logs/apache-error.log
[Thu Apr 24 20:17:12.391297 2025] [ssl:warn] [pid 281196] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Thu Apr 24 20:17:12.429293 2025] [ssl:warn] [pid 281197] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)

