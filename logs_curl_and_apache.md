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
100 29487    0 29487    0     0   630k      0 --:--:-- --:--:-- --:--:--  639k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 27782    0 27782    0     0  2288k      0 --:--:-- --:--:-- --:--:-- 2466k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44219    0 44219    0     0  4096k      0 --:--:-- --:--:-- --:--:-- 4318k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100  9854    0  9854    0     0   973k      0 --:--:-- --:--:-- --:--:-- 1069k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44219    0 44219    0     0  4673k      0 --:--:-- --:--:-- --:--:-- 4798k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 22641    0 22641    0     0  2025k      0 --:--:-- --:--:-- --:--:-- 2211k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14541    0 14541    0     0  1958k      0 --:--:-- --:--:-- --:--:-- 2028k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 12714    0 12714    0     0   849k      0 --:--:-- --:--:-- --:--:--  886k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16494    0 16494    0     0  1949k      0 --:--:-- --:--:-- --:--:-- 2013k
pete@pop-os:/cdrom/project/The-Scent-oa5
$ cat apache_logs/apache-error.log 
[Mon Apr 28 14:45:26.855464 2025] [ssl:warn] [pid 476864] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Mon Apr 28 14:45:26.893604 2025] [ssl:warn] [pid 476865] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
pete@pop-os:/cdrom/project/The-Scent-oa5
$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'  
127.0.0.1 - - [28/Apr/2025:14:45:44 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [28/Apr/2025:14:46:09 +0800] "GET / HTTP/1.1" 200 32496 "-" "curl/8.5.0"
127.0.0.1 - - [28/Apr/2025:14:46:09 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 30790 "-" "curl/8.5.0"
127.0.0.1 - - [28/Apr/2025:14:46:09 +0800] "GET /index.php?page=products HTTP/1.1" 200 47331 "-" "curl/8.5.0"
127.0.0.1 - - [28/Apr/2025:14:46:09 +0800] "GET /index.php?page=contact HTTP/1.1" 200 12758 "-" "curl/8.5.0"
127.0.0.1 - - [28/Apr/2025:14:46:09 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 47331 "-" "curl/8.5.0"
127.0.0.1 - - [28/Apr/2025:14:46:09 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 25642 "-" "curl/8.5.0"
127.0.0.1 - - [28/Apr/2025:14:46:09 +0800] "GET /index.php?page=about HTTP/1.1" 200 17446 "-" "curl/8.5.0"
127.0.0.1 - - [28/Apr/2025:14:46:09 +0800] "GET /index.php?page=login HTTP/1.1" 200 15619 "-" "curl/8.5.0"
127.0.0.1 - - [28/Apr/2025:14:46:09 +0800] "GET /index.php?page=register HTTP/1.1" 200 19476 "-" "curl/8.5.0"

