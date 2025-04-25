# PHP e-commerce platform for "The Scent" store file structure

$ cd /cdrom/project/The-Scent-oa5

$ ls -l index.php css/style.css js/main.js particles.json .htaccess config.php includes/*php views/*php views/layout/*php controllers/*php models/*php views/admin/* | egrep -v 'test_|phpinfo'
```
-rw-rw-r-- 1 pete pete       508 Apr 18 04:44 .htaccess
-rwxr-xr-x 1 pete www-data  3703 Apr 24 20:56 config.php
-rwxr-xr-x 1 pete www-data 24637 Apr 24 20:01 controllers/AccountController.php
-rw-rw-r-- 1 pete pete     18675 Apr 23 21:24 controllers/BaseController.php
-rwxr-xr-x 1 pete www-data 12188 Apr 24 20:12 controllers/CartController.php
-rwxr-xr-x 1 pete www-data 14205 Apr 24 20:11 controllers/CheckoutController.php
-rwxr-xr-x 1 pete www-data 11307 Apr 14 07:08 controllers/CouponController.php
-rwxr-xr-x 1 pete www-data  9875 Apr 14 07:16 controllers/InventoryController.php
-rwxr-xr-x 1 pete www-data  7035 Apr 23 21:24 controllers/NewsletterController.php
-rwxr-xr-x 1 pete www-data  9106 Apr 14 07:08 controllers/PaymentController.php
-rwxr-xr-x 1 pete www-data 14649 Apr 24 19:50 controllers/ProductController.php
-rwxr-xr-x 1 pete www-data 11146 Apr 14 15:28 controllers/QuizController.php
-rwxr-xr-x 1 pete www-data  9695 Apr 14 07:16 controllers/TaxController.php
-rw-rw-r-- 1 pete pete     44919 Apr 25 07:48 css/style.css
-rwxr-xr-x 1 pete www-data 16899 Apr 14 08:24 includes/EmailService.php
-rw-rw-r-- 1 pete pete      8947 Apr 14 08:24 includes/ErrorHandler.php
-rw-rw-r-- 1 pete pete     12475 Apr 24 19:51 includes/SecurityMiddleware.php
-rwxr-xr-x 1 pete www-data  1403 Apr 15 21:10 includes/auth.php
-rwxr-xr-x 1 pete pete       890 Apr 18 07:04 includes/db.php
-rwxr-xr-x 1 pete pete      6248 Apr 25 07:14 index.php
-rw-rw-r-- 1 pete pete     28485 Apr 25 07:48 js/main.js
-rw-rw-r-- 1 pete pete      2224 Apr 24 20:00 models/Cart.php
-rwxr-xr-x 1 pete www-data  2939 Apr 13 13:00 models/Order.php
-rwxr-xr-x 1 pete www-data 12286 Apr 25 07:20 models/Product.php
-rwxr-xr-x 1 pete www-data  9900 Apr 14 15:33 models/Quiz.php
-rwxr-xr-x 1 pete www-data  2053 Apr 13 13:00 models/User.php
-rwxr-xr-x 1 pete pete      1401 Apr 18 04:53 particles.json
-rw-rw-r-- 1 pete pete      1111 Apr 25 07:44 views/404.php
-rwxr-xr-x 1 pete www-data  7898 Apr 25 07:48 views/admin/coupons.php
-rw-rw-r-- 1 pete pete      4091 Apr 25 07:48 views/admin/quiz_analytics.php
-rwxr-xr-x 1 pete pete      3986 Apr 25 07:34 views/cart.php
-rwxr-xr-x 1 pete pete     15657 Apr 22 20:06 views/checkout.php
-rw-rw-r-- 1 pete pete      1329 Apr 23 08:16 views/contact.php
-rw-rw-r-- 1 pete pete      1521 Apr 25 07:44 views/error.php
-rw-rw-r-- 1 pete pete      1248 Apr 23 08:16 views/faq.php
-rwxr-xr-x 1 pete pete      1942 Apr 25 07:44 views/forgot_password.php
-rw-rw-r-- 1 pete pete     13779 Apr 25 07:34 views/home.php
-rw-rw-r-- 1 pete pete       187 Apr 14 09:07 views/layout/admin_footer.php
-rw-rw-r-- 1 pete pete      2833 Apr 14 09:07 views/layout/admin_header.php
-rw-rw-r-- 1 pete pete      4191 Apr 25 07:34 views/layout/footer.php
-rwxr-xr-x 1 pete www-data  3881 Apr 25 07:34 views/layout/header.php
-rwxr-xr-x 1 pete pete      2747 Apr 25 07:44 views/login.php
-rw-rw-r-- 1 pete pete       975 Apr 23 08:16 views/order-tracking.php
-rwxr-xr-x 1 pete pete      5800 Apr 18 08:08 views/order_confirmation.php
-rw-rw-r-- 1 pete pete       756 Apr 23 08:16 views/privacy.php
-rwxr-xr-x 1 pete pete     23227 Apr 25 07:34 views/product_detail.php
-rwxr-xr-x 1 pete pete     13522 Apr 25 07:34 views/products.php
-rwxr-xr-x 1 pete www-data  4174 Apr 25 07:46 views/quiz.php
-rwxr-xr-x 1 pete www-data  3881 Apr 25 07:46 views/quiz_results.php
-rw-rw-r-- 1 pete pete      5002 Apr 25 07:44 views/register.php
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
