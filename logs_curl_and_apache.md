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
100 29487    0 29487    0     0   904k      0 --:--:-- --:--:-- --:--:--  928k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 27782    0 27782    0     0  2238k      0 --:--:-- --:--:-- --:--:-- 2260k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44193    0 44193    0     0  3759k      0 --:--:-- --:--:-- --:--:-- 3923k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100  9854    0  9854    0     0  1000k      0 --:--:-- --:--:-- --:--:-- 1069k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44193    0 44193    0     0  4369k      0 --:--:-- --:--:-- --:--:-- 4795k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 22651    0 22651    0     0  2290k      0 --:--:-- --:--:-- --:--:-- 2457k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14541    0 14541    0     0  1784k      0 --:--:-- --:--:-- --:--:-- 2028k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 12714    0 12714    0     0  1002k      0 --:--:-- --:--:-- --:--:-- 1034k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16494    0 16494    0     0  2179k      0 --:--:-- --:--:-- --:--:-- 2301k

$ cat logs/security.log
[SECURITY] login_failure | {"email":"abc@def.com","ip":"127.0.0.1","user_id":null,"timestamp":"2025-04-27 07:11:03","user_agent":"Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/135.0.0.0 Safari\/537.36"}[2025-04-27 07:12:26 UTC] [ERROR] Email sending failed (Exception): SMTP Error: Could not authenticate. | Mailer Error: SMTP Error: Could not authenticate. | Context: {
    "ip": "127.0.0.1",
    "user_id": null,
    "to": "abc@def.com",
    "subject": "Welcome to The Scent!",
    "template": "welcome"
}
[SECURITY] register_failure | {"email":"abc@def.com","error":"An error occurred during registration. Please try again.","ip":"127.0.0.1","timestamp":"2025-04-27 07:12:26","user_id":null,"user_agent":"Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/135.0.0.0 Safari\/537.36"}pete@pop-os:/cdrom/project/The-Scent-oa5

$ cat apache_logs/apache-error.log 
[Sun Apr 27 15:07:17.166674 2025] [ssl:warn] [pid 433639] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Sun Apr 27 15:07:17.204469 2025] [ssl:warn] [pid 433640] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Sun Apr 27 15:11:03.004421 2025] [proxy_fcgi:error] [pid 433918] [client 127.0.0.1:46166] AH01071: Got error 'PHP message: Login failed for email 'abc@def.com' from IP 127.0.0.1: Invalid email or password.', referer: https://the-scent.com/index.php?page=login
[Sun Apr 27 15:12:26.657789 2025] [proxy_fcgi:error] [pid 433644] [client 127.0.0.1:56038] AH01071: Got error 'PHP message: EmailService Error: Email sending failed (Exception): SMTP Error: Could not authenticate. | Mailer Error: SMTP Error: Could not authenticate. | Context: {"to":"abc@def.com","subject":"Welcome to The Scent!","template":"welcome"}; PHP message: User creation transaction error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'recipient_email' in 'field list'; PHP message: Registration failed for email 'abc@def.com' from IP 127.0.0.1: An error occurred during registration. Please try again.', referer: https://the-scent.com/index.php?page=register

$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'  
127.0.0.1 - - [27/Apr/2025:15:08:19 +0800] "GET / HTTP/1.1" 200 32287 "-" "curl/8.5.0"
127.0.0.1 - - [27/Apr/2025:15:08:19 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 30581 "-" "curl/8.5.0"
127.0.0.1 - - [27/Apr/2025:15:08:19 +0800] "GET /index.php?page=products HTTP/1.1" 200 47096 "-" "curl/8.5.0"
127.0.0.1 - - [27/Apr/2025:15:08:19 +0800] "GET /index.php?page=contact HTTP/1.1" 200 12549 "-" "curl/8.5.0"
127.0.0.1 - - [27/Apr/2025:15:08:19 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 47096 "-" "curl/8.5.0"
127.0.0.1 - - [27/Apr/2025:15:08:19 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 25443 "-" "curl/8.5.0"
127.0.0.1 - - [27/Apr/2025:15:08:19 +0800] "GET /index.php?page=about HTTP/1.1" 200 17237 "-" "curl/8.5.0"
127.0.0.1 - - [27/Apr/2025:15:08:19 +0800] "GET /index.php?page=login HTTP/1.1" 200 15410 "-" "curl/8.5.0"
127.0.0.1 - - [27/Apr/2025:15:08:19 +0800] "GET /index.php?page=register HTTP/1.1" 200 19267 "-" "curl/8.5.0"
127.0.0.1 - - [27/Apr/2025:15:09:20 +0800] "GET / HTTP/1.1" 200 8112 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:20 +0800] "GET /css/style.css HTTP/1.1" 200 8319 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:20 +0800] "GET /js/main.js HTTP/1.1" 200 15367 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:21 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 901 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:21 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:31 +0800] "GET /index.php?page=contact HTTP/1.1" 200 4133 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:31 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 901 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:31 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:33 +0800] "GET /index.php?page=faq HTTP/1.1" 200 3846 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:33 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 901 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:33 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:34 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 3783 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:35 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 901 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:35 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:37 +0800] "GET /index.php?page=order-tracking HTTP/1.1" 200 3700 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:37 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 901 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:37 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:38 +0800] "GET /index.php?page=privacy HTTP/1.1" 200 3759 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:38 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 901 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:38 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:40 +0800] "GET /index.php?page=about HTTP/1.1" 200 5175 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:41 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 901 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:41 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:45 +0800] "GET /index.php?page=contact HTTP/1.1" 200 3809 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:45 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 901 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:45 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:48 +0800] "GET /index.php?page=quiz HTTP/1.1" 200 4174 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:48 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 901 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:48 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:50 +0800] "GET /index.php?page=products HTTP/1.1" 200 5958 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:50 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 901 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:50 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:55 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 6878 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:55 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 901 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:55 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:59 +0800] "GET /index.php HTTP/1.1" 200 6304 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:59 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2566 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:09:59 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:04 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 6457 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:05 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 901 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:05 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:06 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 952 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:06 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1158 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:15 +0800] "GET /index.php HTTP/1.1" 200 7966 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:15 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1158 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:15 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:20 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 952 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:20 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1158 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:27 +0800] "GET /index.php?page=cart HTTP/1.1" 200 5435 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:27 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1158 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:27 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:30 +0800] "GET /index.php?page=checkout HTTP/1.1" 302 845 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:30 +0800] "GET /index.php?page=login HTTP/1.1" 200 4474 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:30 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1158 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:10:30 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [27/Apr/2025:15:10:38 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [27/Apr/2025:15:11:03 +0800] "POST /index.php?page=login HTTP/1.1" 401 2563 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:11:09 +0800] "GET /index.php?page=register HTTP/1.1" 200 5324 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:11:09 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1158 "https://the-scent.com/index.php?page=register" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:11:09 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=register" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [27/Apr/2025:15:12:24 +0800] "POST /index.php?page=register HTTP/1.1" 400 2555 "https://the-scent.com/index.php?page=register" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"

