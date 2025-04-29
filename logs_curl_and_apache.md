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
100 29649    0 29649    0     0   997k      0 --:--:-- --:--:-- --:--:-- 1034k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 27944    0 27944    0     0  2257k      0 --:--:-- --:--:-- --:--:-- 2480k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44381    0 44381    0     0  3754k      0 --:--:-- --:--:-- --:--:-- 3940k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 10016    0 10016    0     0  1124k      0 --:--:-- --:--:-- --:--:-- 1222k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44381    0 44381    0     0  4540k      0 --:--:-- --:--:-- --:--:-- 4815k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 22803    0 22803    0     0  2211k      0 --:--:-- --:--:-- --:--:-- 2474k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14703    0 14703    0     0  1772k      0 --:--:-- --:--:-- --:--:-- 2051k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 12876    0 12876    0     0   958k      0 --:--:-- --:--:-- --:--:-- 1047k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16656    0 16656    0     0  1912k      0 --:--:-- --:--:-- --:--:-- 2033k

$ cat logs/security.log
[2025-04-29 06:48:47 UTC] [WARNING] Potentially security-related exception caught | Context: {
    "ip": "127.0.0.1",
    "user_id": null,
    "type": "Exception",
    "message": "CSRF token validation failed",
    "file": "/cdrom/project/The-Scent-oa5/includes/SecurityMiddleware.php",
    "line": 276,
    "trace": "#0 /cdrom/project/The-Scent-oa5/index.php(39): SecurityMiddleware::validateCSRF()\n#1 {main}",
    "context": {
        "url": "/index.php?page=login",
        "method": "POST",
        "ip": "127.0.0.1",
        "timestamp": "2025-04-29 06:48:47 UTC"
    }
}
[2025-04-29 06:48:47 UTC] [WARNING] Security-related error detected | Context: {
    "ip": "127.0.0.1",
    "user_id": null,
    "type": "Exception",
    "message": "CSRF token validation failed",
    "file": "/cdrom/project/The-Scent-oa5/includes/SecurityMiddleware.php",
    "line": 276,
    "trace": "#0 /cdrom/project/The-Scent-oa5/index.php(39): SecurityMiddleware::validateCSRF()\n#1 {main}",
    "context": {
        "url": "/index.php?page=login",
        "method": "POST",
        "ip": "127.0.0.1",
        "timestamp": "2025-04-29 06:48:47 UTC"
    }
}

$ cat apache_logs/apache-error.log 
[Tue Apr 29 14:43:49.417461 2025] [ssl:warn] [pid 524552] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Tue Apr 29 14:43:49.454979 2025] [ssl:warn] [pid 524553] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Tue Apr 29 14:48:47.082026 2025] [proxy_fcgi:error] [pid 524559] [client 127.0.0.1:52738] AH01071: Got error 'PHP message: General error/exception in index.php: CSRF token validation failed Trace: #0 /cdrom/project/The-Scent-oa5/index.php(39): SecurityMiddleware::validateCSRF()\n#1 {main}; PHP message: Uncaught Exception 'Exception': "CSRF token validation failed" in /cdrom/project/The-Scent-oa5/includes/SecurityMiddleware.php:276; PHP message: Stack trace:\n#0 /cdrom/project/The-Scent-oa5/index.php(39): SecurityMiddleware::validateCSRF()\n#1 {main}; PHP message: [2025-04-29 06:48:47 UTC] [Exception] CSRF token validation failed in /cdrom/project/The-Scent-oa5/includes/SecurityMiddleware.php on line 276\nStack trace:\n#0 /cdrom/project/The-Scent-oa5/index.php(39): SecurityMiddleware::validateCSRF()\n#1 {main}\nContext: {\n    "url": "/index.php?page=login",\n    "method": "POST",\n    "ip": "127.0.0.1",\n    "timestamp": "2025-04-29 06:48:47 UTC"\n}', referer: https://the-scent.com/index.php?page=login

$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos' 
127.0.0.1 - - [29/Apr/2025:14:44:13 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:14:45:32 +0800] "GET / HTTP/1.1" 200 32658 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:14:45:32 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 30953 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:14:45:32 +0800] "GET /index.php?page=products HTTP/1.1" 200 47494 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:14:45:32 +0800] "GET /index.php?page=contact HTTP/1.1" 200 12920 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:14:45:32 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 47494 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:14:45:32 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 25804 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:14:45:32 +0800] "GET /index.php?page=about HTTP/1.1" 200 17608 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:14:45:32 +0800] "GET /index.php?page=login HTTP/1.1" 200 15781 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:14:45:32 +0800] "GET /index.php?page=register HTTP/1.1" 200 19634 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:14:46:39 +0800] "GET / HTTP/1.1" 200 8400 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:39 +0800] "GET /css/style.css HTTP/1.1" 200 8319 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:39 +0800] "GET /js/main.js HTTP/1.1" 200 20889 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:39 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:48 +0800] "GET /index.php?page=contact HTTP/1.1" 200 5764 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:48 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [29/Apr/2025:14:46:48 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [29/Apr/2025:14:46:50 +0800] "GET /index.php?page=faq HTTP/1.1" 200 4132 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:50 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:51 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 4063 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:51 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:53 +0800] "GET /index.php?page=order-tracking HTTP/1.1" 200 3985 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:53 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:54 +0800] "GET /index.php?page=privacy HTTP/1.1" 200 4045 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:54 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:56 +0800] "GET /index.php?page=about HTTP/1.1" 200 5459 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:56 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:59 +0800] "GET /index.php?page=contact HTTP/1.1" 200 4099 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:46:59 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:02 +0800] "GET /index.php?page=quiz HTTP/1.1" 200 4464 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:02 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:04 +0800] "GET /index.php?page=products HTTP/1.1" 200 6263 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:04 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:11 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 7170 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:11 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:16 +0800] "-" 408 1664 "-" "-"
127.0.0.1 - - [29/Apr/2025:14:47:18 +0800] "GET /index.php HTTP/1.1" 200 6916 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:18 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:23 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 6747 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:23 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:28 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 1513 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:28 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart&action=add" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [29/Apr/2025:14:47:35 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [29/Apr/2025:14:47:40 +0800] "GET / HTTP/1.1" 200 8254 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:40 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:52 +0800] "GET /index.php?page=cart HTTP/1.1" 200 5710 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:52 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:59 +0800] "GET /index.php?page=checkout HTTP/1.1" 302 2719 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:59 +0800] "GET /index.php?page=login HTTP/1.1" 200 4757 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:47:59 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:48:47 +0800] "POST /index.php?page=login HTTP/1.1" 500 10640 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:48:47 +0800] "GET /js/main.js HTTP/1.1" 200 20889 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:14:48:47 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"

