$ curl -Lk https://the-scent.com/ -o current_landing_page.html    
curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html    
curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html    
curl -Lk 'https://the-scent.com/index.php?page=contact' -o contact_page.html    
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=1' -o products_page_1.html    
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=2' -o products_page_2.html    
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 28246    0 28246    0     0   785k      0 --:--:-- --:--:-- --:--:--  811k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 26516    0 26516    0     0  1525k      0 --:--:-- --:--:-- --:--:-- 1618k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 42927    0 42927    0     0  3243k      0 --:--:-- --:--:-- --:--:-- 3493k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100  8576    0  8576    0     0   849k      0 --:--:-- --:--:-- --:--:--  930k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 42927    0 42927    0     0  3047k      0 --:--:-- --:--:-- --:--:-- 3224k  
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current  
                                 Dload  Upload   Total   Spent    Left  Speed  
100 21385    0 21385    0     0  2260k      0 --:--:-- --:--:-- --:--:-- 2320k  
  
$ tail -70 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'  
127.0.0.1 - - [25/Apr/2025:19:58:57 +0800] "GET / HTTP/1.1" 200 31150 "-" "curl/8.5.0"  
127.0.0.1 - - [25/Apr/2025:19:58:57 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 29486 "-" "curl/8.5.0"  
127.0.0.1 - - [25/Apr/2025:19:58:57 +0800] "GET /index.php?page=products HTTP/1.1" 200 46052 "-" "curl/8.5.0"  
127.0.0.1 - - [25/Apr/2025:19:58:57 +0800] "GET /index.php?page=contact HTTP/1.1" 200 11398 "-" "curl/8.5.0"  
127.0.0.1 - - [25/Apr/2025:19:58:57 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 46052 "-" "curl/8.5.0"  
127.0.0.1 - - [25/Apr/2025:19:58:57 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 24289 "-" "curl/8.5.0"  
127.0.0.1 - - [25/Apr/2025:19:59:26 +0800] "GET / HTTP/1.1" 200 7889 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:19:59:26 +0800] "GET /js/main.js HTTP/1.1" 200 6359 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:19:59:26 +0800] "GET /css/style.css HTTP/1.1" 200 9984 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:19:59:26 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:19:59:43 +0800] "GET /index.php?page=products HTTP/1.1" 200 7391 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:19:59:43 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:00:13 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 5297 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:00:13 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:00:22 +0800] "GET /index.php?page=products HTTP/1.1" 200 7391 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:00:22 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:00:31 +0800] "GET /index.php HTTP/1.1" 200 6405 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:00:31 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:06 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 6538 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:06 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:15 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 2733 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:15 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 2733 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:23 +0800] "GET /index.php HTTP/1.1" 200 6405 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:23 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:29 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 2733 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:29 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 2733 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:39 +0800] "GET /index.php?page=cart HTTP/1.1" 500 12358 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:39 +0800] "GET /%3Cbody%20class= HTTP/1.1" 200 7746 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:39 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:41 +0800] "GET /index.php?page=cart HTTP/1.1" 500 12035 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:41 +0800] "GET /%3Cbody%20class= HTTP/1.1" 200 6405 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:41 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:52 +0800] "GET /index.php?page=cart HTTP/1.1" 500 13699 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:52 +0800] "GET /%3Cbody%20class= HTTP/1.1" 200 6405 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
127.0.0.1 - - [25/Apr/2025:20:01:52 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"  
  
$ cat apache_logs/apache-error.log   
[Fri Apr 25 19:58:21.680679 2025] [ssl:warn] [pid 321741] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)  
[Fri Apr 25 19:58:21.720331 2025] [ssl:warn] [pid 321742] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)  
[Fri Apr 25 20:01:39.541263 2025] [php:notice] [pid 321745] [client 127.0.0.1:37210] [2025-04-25 12:01:39] Warning: Undefined array key "image_url" in /cdrom/project/The-Scent-oa5/views/cart.php on line 24\nContext: {"url":"\\/index.php?page=cart","method":"GET","ip":"127.0.0.1","timestamp":"2025-04-25 12:01:39"}, referer: https://the-scent.com/index.php  
[Fri Apr 25 20:01:39.542228 2025] [php:notice] [pid 321745] [client 127.0.0.1:37210] [2025-04-25 12:01:39] Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in /cdrom/project/The-Scent-oa5/views/cart.php on line 24\nContext: {"url":"\\/index.php?page=cart","method":"GET","ip":"127.0.0.1","timestamp":"2025-04-25 12:01:39"}, referer: https://the-scent.com/index.php  
[Fri Apr 25 20:01:39.542308 2025] [php:warn] [pid 321745] [client 127.0.0.1:37210] PHP Warning:  http_response_code(): Cannot set response code - headers already sent (output started at /cdrom/project/The-Scent-oa5/views/error.php:2) in /cdrom/project/The-Scent-oa5/includes/ErrorHandler.php on line 225, referer: https://the-scent.com/index.php  
[Fri Apr 25 20:01:41.778841 2025] [php:notice] [pid 321746] [client 127.0.0.1:37228] [2025-04-25 12:01:41] Warning: Undefined array key "image_url" in /cdrom/project/The-Scent-oa5/views/cart.php on line 24\nContext: {"url":"\\/index.php?page=cart","method":"GET","ip":"127.0.0.1","timestamp":"2025-04-25 12:01:41"}, referer: https://the-scent.com/index.php?page=cart  
[Fri Apr 25 20:01:41.779062 2025] [php:notice] [pid 321746] [client 127.0.0.1:37228] [2025-04-25 12:01:41] Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in /cdrom/project/The-Scent-oa5/views/cart.php on line 24\nContext: {"url":"\\/index.php?page=cart","method":"GET","ip":"127.0.0.1","timestamp":"2025-04-25 12:01:41"}, referer: https://the-scent.com/index.php?page=cart  
[Fri Apr 25 20:01:41.779091 2025] [php:warn] [pid 321746] [client 127.0.0.1:37228] PHP Warning:  http_response_code(): Cannot set response code - headers already sent (output started at /cdrom/project/The-Scent-oa5/views/error.php:2) in /cdrom/project/The-Scent-oa5/includes/ErrorHandler.php on line 225, referer: https://the-scent.com/index.php?page=cart  
[Fri Apr 25 20:01:52.533184 2025] [php:notice] [pid 321897] [client 127.0.0.1:51446] [2025-04-25 12:01:52] Warning: Undefined array key "image_url" in /cdrom/project/The-Scent-oa5/views/cart.php on line 24\nContext: {"url":"\\/index.php?page=cart","method":"GET","ip":"127.0.0.1","timestamp":"2025-04-25 12:01:52"}, referer: https://the-scent.com/index.php?page=cart  
[Fri Apr 25 20:01:52.533431 2025] [php:notice] [pid 321897] [client 127.0.0.1:51446] [2025-04-25 12:01:52] Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in /cdrom/project/The-Scent-oa5/views/cart.php on line 24\nContext: {"url":"\\/index.php?page=cart","method":"GET","ip":"127.0.0.1","timestamp":"2025-04-25 12:01:52"}, referer: https://the-scent.com/index.php?page=cart  
[Fri Apr 25 20:01:52.533464 2025] [php:warn] [pid 321897] [client 127.0.0.1:51446] PHP Warning:  http_response_code(): Cannot set response code - headers already sent (output started at /cdrom/project/The-Scent-oa5/views/error.php:2) in /cdrom/project/The-Scent-oa5/includes/ErrorHandler.php on line 225, referer: https://the-scent.com/index.php?page=cart  
  
