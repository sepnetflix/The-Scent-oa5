$ curl -Lk https://the-scent.com/ -o current_landing_page.html      
curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html      
curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html      
curl -Lk 'https://the-scent.com/index.php?page=contact' -o contact_page.html      
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=1' -o products_page_1.html      
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=2' -o products_page_2.html
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 29487    0 29487    0     0   732k      0 --:--:-- --:--:-- --:--:--  738k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 27782    0 27782    0     0  1780k      0 --:--:-- --:--:-- --:--:-- 1808k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44193    0 44193    0     0  3179k      0 --:--:-- --:--:-- --:--:-- 3319k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100  9842    0  9842    0     0   912k      0 --:--:-- --:--:-- --:--:--  961k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44193    0 44193    0     0  3490k      0 --:--:-- --:--:-- --:--:-- 3596k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 22651    0 22651    0     0  2165k      0 --:--:-- --:--:-- --:--:-- 2212k

$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos' 
127.0.0.1 - - [26/Apr/2025:14:11:23 +0800] "GET / HTTP/1.1" 200 32255 "-" "curl/8.5.0"
127.0.0.1 - - [26/Apr/2025:14:11:23 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 30550 "-" "curl/8.5.0"
127.0.0.1 - - [26/Apr/2025:14:11:23 +0800] "GET /index.php?page=products HTTP/1.1" 200 47109 "-" "curl/8.5.0"
127.0.0.1 - - [26/Apr/2025:14:11:23 +0800] "GET /index.php?page=contact HTTP/1.1" 200 12462 "-" "curl/8.5.0"
127.0.0.1 - - [26/Apr/2025:14:11:23 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 47109 "-" "curl/8.5.0"
127.0.0.1 - - [26/Apr/2025:14:11:23 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 25346 "-" "curl/8.5.0"
127.0.0.1 - - [26/Apr/2025:14:11:57 +0800] "GET / HTTP/1.1" 200 8040 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:11:57 +0800] "GET /css/style.css HTTP/1.1" 200 8319 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:11:57 +0800] "GET /js/main.js HTTP/1.1" 200 15367 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:11:57 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:11:58 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [26/Apr/2025:14:12:06 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [26/Apr/2025:14:12:10 +0800] "GET /index.php?page=products HTTP/1.1" 200 7554 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:10 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:10 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:15 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 5466 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:16 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:16 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [26/Apr/2025:14:12:24 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [26/Apr/2025:14:12:24 +0800] "GET /index.php?page=contact HTTP/1.1" 200 5398 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:24 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:24 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:26 +0800] "GET /index.php?page=faq HTTP/1.1" 200 3771 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:26 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:26 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:28 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 3706 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:28 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:28 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:30 +0800] "GET /index.php?page=order-tracking HTTP/1.1" 200 3621 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:30 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:30 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:32 +0800] "GET /index.php?page=privacy HTTP/1.1" 200 3682 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:32 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:32 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:34 +0800] "GET /index.php?page=products HTTP/1.1" 200 5889 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:35 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:35 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:40 +0800] "GET /index.php?page=product&id=7 HTTP/1.1" 200 6708 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:40 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 807 "https://the-scent.com/index.php?page=product&id=7" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:40 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=7" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:42 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 859 "https://the-scent.com/index.php?page=product&id=7" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:42 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1064 "https://the-scent.com/index.php?page=product&id=7" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:52 +0800] "GET /index.php?page=cart HTTP/1.1" 200 6695 "https://the-scent.com/index.php?page=product&id=7" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:52 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1064 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:52 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:56 +0800] "GET /index.php?page=login HTTP/1.1" 200 3908 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:56 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1064 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:12:56 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [26/Apr/2025:14:13:00 +0800] "-" 408 1664 "-" "-"

$ cat apache_logs/apache-error.log 
[Sat Apr 26 14:11:03.953861 2025] [ssl:warn] [pid 373823] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Sat Apr 26 14:11:03.989307 2025] [ssl:warn] [pid 373824] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)

$ cat logs/security.log 
[2025-04-26 01:48:05] [WARNING] Security-related error detected | {"type":"Compile Error","message":"Access level to AccountController::$emailService must be protected (as in class BaseController) or weaker","file":"\/cdrom\/project\/The-Scent-oa5\/controllers\/AccountController.php","line":13,"context":{"url":"\/index.php?page=login","method":"GET","ip":"127.0.0.1","timestamp":"2025-04-26 01:48:05"}}

