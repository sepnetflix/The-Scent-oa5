$ curl -Lk https://the-scent.com/ -o current_landing_page.html
curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html
curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html
curl -Lk 'https://the-scent.com/index.php?page=contact' -o contact_page.html
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=1' -o products_page_1.html
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=2' -o products_page_2.html
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 28246    0 28246    0     0   798k      0 --:--:-- --:--:-- --:--:--  811k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 26516    0 26516    0     0  1942k      0 --:--:-- --:--:-- --:--:-- 1849k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 42927    0 42927    0     0  2806k      0 --:--:-- --:--:-- --:--:-- 2994k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100  8576    0  8576    0     0   838k      0 --:--:-- --:--:-- --:--:--  930k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 42927    0 42927    0     0  2828k      0 --:--:-- --:--:-- --:--:-- 2994k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 21385    0 21385    0     0  2333k      0 --:--:-- --:--:-- --:--:-- 2610k

$ tail -70 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'
127.0.0.1 - - [25/Apr/2025:12:28:42 +0800] "GET / HTTP/1.1" 200 30941 "-" "curl/8.5.0"
127.0.0.1 - - [25/Apr/2025:12:28:42 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 29277 "-" "curl/8.5.0"
127.0.0.1 - - [25/Apr/2025:12:28:42 +0800] "GET /index.php?page=products HTTP/1.1" 200 45843 "-" "curl/8.5.0"
127.0.0.1 - - [25/Apr/2025:12:28:42 +0800] "GET /index.php?page=contact HTTP/1.1" 200 11189 "-" "curl/8.5.0"
127.0.0.1 - - [25/Apr/2025:12:28:42 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 45843 "-" "curl/8.5.0"
127.0.0.1 - - [25/Apr/2025:12:28:42 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 24080 "-" "curl/8.5.0"
127.0.0.1 - - [25/Apr/2025:12:29:19 +0800] "GET / HTTP/1.1" 200 7676 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:12:29:19 +0800] "GET /js/main.js HTTP/1.1" 200 6359 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:12:29:19 +0800] "GET /css/style.css HTTP/1.1" 200 8319 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:12:29:20 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [25/Apr/2025:12:29:27 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
::1 - - [25/Apr/2025:12:29:28 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [25/Apr/2025:12:29:31 +0800] "GET /index.php?page=products HTTP/1.1" 200 7179 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:12:29:32 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:12:29:38 +0800] "GET /index.php HTTP/1.1" 200 6192 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:12:29:38 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:12:29:45 +0800] "GET /index.php?page=products HTTP/1.1" 200 5514 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [25/Apr/2025:12:29:45 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [25/Apr/2025:12:29:52 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"

$ cat apache_logs/apache-error.log
[Fri Apr 25 12:28:19.204849 2025] [ssl:warn] [pid 317895] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Fri Apr 25 12:28:19.243082 2025] [ssl:warn] [pid 317896] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)

