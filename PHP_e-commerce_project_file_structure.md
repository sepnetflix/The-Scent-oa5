# PHP e-commerce platform for "The Scent" store file structure

$ cd /cdrom/project/The-Scent-oa5

$ ls -l index.php css/style.css js/main.js particles.json .htaccess config.php includes/*php views/*php views/layout/*php controllers/*php models/*php views/admin/* | egrep -v 'test_|phpinfo'
```
-rw-rw-r-- 1 pete pete       508 Apr 18 04:44 .htaccess
-rwxr-xr-x 1 pete pete      3700 Apr 27 15:18 config.php
-rw-rw-r-- 1 pete pete     33978 Apr 27 08:35 controllers/AccountController.php
-rw-rw-r-- 1 pete pete     19024 Apr 27 11:13 controllers/BaseController.php
-rw-rw-r-- 1 pete pete     18837 Apr 26 09:32 controllers/CartController.php
-rw-rw-r-- 1 pete pete     26864 Apr 28 07:44 controllers/CheckoutController.php
-rw-rw-r-- 1 pete pete     21651 Apr 28 08:20 controllers/CouponController.php
-rw-rw-r-- 1 pete pete     15243 Apr 27 09:34 controllers/InventoryController.php
-rw-rw-r-- 1 pete pete     10374 Apr 27 09:34 controllers/NewsletterController.php
-rw-rw-r-- 1 pete pete     21096 Apr 28 08:25 controllers/PaymentController.php
-rwxr-xr-x 1 pete www-data 15167 Apr 25 12:22 controllers/ProductController.php
-rwxr-xr-x 1 pete www-data 11244 Apr 25 11:46 controllers/QuizController.php
-rwxr-xr-x 1 pete www-data  9695 Apr 14 07:16 controllers/TaxController.php
-rw-rw-r-- 1 pete pete     44919 Apr 25 07:48 css/style.css
-rw-rw-r-- 1 pete pete     20539 Apr 28 09:56 includes/EmailService.php
-rw-rw-r-- 1 pete pete     29222 Apr 27 13:24 includes/ErrorHandler.php
-rw-rw-r-- 1 pete pete     12475 Apr 25 21:15 includes/SecurityMiddleware.php
-rwxr-xr-x 1 pete www-data  1403 Apr 15 21:10 includes/auth.php
-rwxr-xr-x 1 pete pete       890 Apr 18 07:04 includes/db.php
-rw-rw-r-- 1 pete pete       716 Apr 28 09:30 includes/password_hash.php
-rw-rw-r-- 1 pete pete       193 Apr 27 08:24 includes/reset_cache.php
-rw-rw-r-- 1 pete pete     16253 Apr 28 08:25 index.php
-rw-rw-r-- 1 pete pete     70516 Apr 26 08:24 js/main.js
-rw-rw-r-- 1 pete pete      2402 Apr 25 11:06 models/Cart.php
-rw-rw-r-- 1 pete pete     13226 Apr 28 08:46 models/Order.php
-rwxr-xr-x 1 pete www-data 11978 Apr 25 12:22 models/Product.php
-rwxr-xr-x 1 pete www-data  9900 Apr 14 15:33 models/Quiz.php
-rwxr-xr-x 1 pete www-data  2067 Apr 27 08:42 models/User.php
-rwxr-xr-x 1 pete pete      1401 Apr 18 04:53 particles.json
-rw-rw-r-- 1 pete pete      1111 Apr 25 07:44 views/404.php
-rw-rw-r-- 1 pete pete      6198 Apr 26 18:54 views/about.php
-rwxr-xr-x 1 pete www-data  7898 Apr 25 07:48 views/admin/coupons.php
-rw-rw-r-- 1 pete pete      4091 Apr 25 07:48 views/admin/quiz_analytics.php
-rw-rw-r-- 1 pete pete     10765 Apr 26 13:46 views/cart.php
-rw-rw-r-- 1 pete pete     26634 Apr 28 07:40 views/checkout.php
-rw-rw-r-- 1 pete pete      1329 Apr 23 08:16 views/contact.php
-rw-rw-r-- 1 pete pete      1521 Apr 25 07:44 views/error.php
-rw-rw-r-- 1 pete pete      1248 Apr 23 08:16 views/faq.php
-rwxr-xr-x 1 pete pete      1942 Apr 25 07:44 views/forgot_password.php
-rw-rw-r-- 1 pete pete     13818 Apr 25 23:21 views/home.php
-rw-rw-r-- 1 pete pete       187 Apr 14 09:07 views/layout/admin_footer.php
-rw-rw-r-- 1 pete pete      2833 Apr 14 09:07 views/layout/admin_header.php
-rw-rw-r-- 1 pete pete      4191 Apr 25 07:34 views/layout/footer.php
-rw-rw-r-- 1 pete pete      7013 Apr 26 09:00 views/layout/header.php
-rw-rw-r-- 1 pete pete      5307 Apr 27 07:04 views/login.php
-rw-rw-r-- 1 pete pete       975 Apr 23 08:16 views/order-tracking.php
-rwxr-xr-x 1 pete pete      5800 Apr 25 08:16 views/order_confirmation.php
-rw-rw-r-- 1 pete pete       756 Apr 23 08:16 views/privacy.php
-rwxr-xr-x 1 pete pete     23227 Apr 25 08:15 views/product_detail.php
-rwxr-xr-x 1 pete pete     13522 Apr 25 08:54 views/products.php
-rwxr-xr-x 1 pete www-data  4174 Apr 25 07:46 views/quiz.php
-rwxr-xr-x 1 pete www-data  3977 Apr 25 08:18 views/quiz_results.php
-rw-rw-r-- 1 pete pete      9085 Apr 27 07:20 views/register.php
-rwxr-xr-x 1 pete pete      4112 Apr 25 07:44 views/reset_password.php
-rw-rw-r-- 1 pete pete       768 Apr 23 08:16 views/shipping.php
```

$ cat .htaccess
Options +SymLinksIfOwnerMatch
RewriteEngine On

# Allow Installatron requests
RewriteCond %{REQUEST_FILENAME} deleteme\.\w+\.php
RewriteRule (.*) - [L]

# If the requested file or directory exists, do not rewrite
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Exclude all URLs like /test_xxx.php and /sample_xxx.html from being rewritten
RewriteCond %{REQUEST_URI} !^/test_.*\.php$
RewriteCond %{REQUEST_URI} !^/sample_.*\.html$
RewriteRule ^ index.php [L]

$ cat particles.json
{
  "particles": {
    "number": {
      "value": 40,
      "density": {
        "enable": true,
        "value_area": 1000
      }
    },
    "color": {
      "value": "#ffffff"
    },
    "shape": {
      "type": "circle"
    },
    "opacity": {
      "value": 0.2,
      "random": true,
      "anim": {
        "enable": true,
        "speed": 0.5,
        "opacity_min": 0.1,
        "sync": false
      }
    },
    "size": {
      "value": 2,
      "random": true,
      "anim": {
        "enable": true,
        "speed": 1,
        "size_min": 0.1,
        "sync": false
      }
    },
    "line_linked": {
      "enable": true,
      "distance": 200,
      "color": "#ffffff",
      "opacity": 0.15,
      "width": 0.5
    },
    "move": {
      "enable": true,
      "speed": 0.8,
      "direction": "none",
      "random": false,
      "straight": false,
      "out_mode": "out",
      "bounce": false,
      "attract": {
        "enable": true,
        "rotateX": 400,
        "rotateY": 800
      }
    }
  },
  "interactivity": {
    "detect_on": "canvas",
    "events": {
      "onhover": {
        "enable": true,
        "mode": "grab"
      },
      "onclick": {
        "enable": false
      },
      "resize": true
    },
    "modes": {
      "grab": {
        "distance": 180,
        "line_linked": {
          "opacity": 0.3
        }
      }
    }
  },
  "retina_detect": true
}
