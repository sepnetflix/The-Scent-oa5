# PHP e-commerce platform for "The Scent" store file structure

$ cd /cdrom/project/The-Scent-oa5

$ ls -l index.php css/style.css particles.json .htaccess config.php includes/*php views/*php views/layout/*php controllers/*php models/*php | egrep -v 'test_|phpinfo'
-rw-rw-r-- 1 pete pete       508 Apr 18 04:44 .htaccess
-rwxr-xr-x 1 pete www-data  3652 Apr 15 21:10 config.php
-rwxr-xr-x 1 pete www-data 24459 Apr 23 21:24 controllers/AccountController.php
-rw-rw-r-- 1 pete pete     18675 Apr 23 21:24 controllers/BaseController.php
-rwxr-xr-x 1 pete www-data  7795 Apr 23 21:28 controllers/CartController.php
-rwxr-xr-x 1 pete www-data 12625 Apr 23 21:29 controllers/CheckoutController.php
-rwxr-xr-x 1 pete www-data 11307 Apr 14 07:08 controllers/CouponController.php
-rwxr-xr-x 1 pete www-data  9875 Apr 14 07:16 controllers/InventoryController.php
-rwxr-xr-x 1 pete www-data  7035 Apr 23 21:24 controllers/NewsletterController.php
-rwxr-xr-x 1 pete www-data  9106 Apr 14 07:08 controllers/PaymentController.php
-rwxr-xr-x 1 pete www-data 14675 Apr 23 21:28 controllers/ProductController.php
-rwxr-xr-x 1 pete www-data 11146 Apr 14 15:28 controllers/QuizController.php
-rwxr-xr-x 1 pete www-data  9695 Apr 14 07:16 controllers/TaxController.php
-rw-rw-r-- 1 pete pete     35087 Apr 17 10:32 css/style.css
-rwxr-xr-x 1 pete www-data 16899 Apr 14 08:24 includes/EmailService.php
-rw-rw-r-- 1 pete pete      8947 Apr 14 08:24 includes/ErrorHandler.php
-rw-rw-r-- 1 pete pete     12475 Apr 23 21:25 includes/SecurityMiddleware.php
-rwxr-xr-x 1 pete www-data  1403 Apr 15 21:10 includes/auth.php
-rwxr-xr-x 1 pete pete       890 Apr 18 07:04 includes/db.php
-rwxr-xr-x 1 pete pete      6103 Apr 23 08:16 index.php
-rwxr-xr-x 1 pete www-data  2939 Apr 13 13:00 models/Order.php
-rwxr-xr-x 1 pete www-data 10341 Apr 22 09:40 models/Product.php
-rwxr-xr-x 1 pete www-data  9900 Apr 14 15:33 models/Quiz.php
-rwxr-xr-x 1 pete www-data  2053 Apr 13 13:00 models/User.php
-rwxr-xr-x 1 pete pete      1401 Apr 18 04:53 particles.json
-rw-rw-r-- 1 pete pete      2242 Apr 14 07:00 views/404.php
-rwxr-xr-x 1 pete pete      8304 Apr 22 20:06 views/cart.php
-rwxr-xr-x 1 pete pete     15657 Apr 22 20:06 views/checkout.php
-rw-rw-r-- 1 pete pete      1329 Apr 23 08:16 views/contact.php
-rw-rw-r-- 1 pete pete      2610 Apr 18 08:07 views/error.php
-rw-rw-r-- 1 pete pete      1248 Apr 23 08:16 views/faq.php
-rwxr-xr-x 1 pete pete      2865 Apr 18 08:07 views/forgot_password.php
-rw-rw-r-- 1 pete pete     15058 Apr 23 11:30 views/home.php
-rw-rw-r-- 1 pete pete       187 Apr 14 09:07 views/layout/admin_footer.php
-rw-rw-r-- 1 pete pete      2833 Apr 14 09:07 views/layout/admin_header.php
-rw-rw-r-- 1 pete pete     10077 Apr 23 21:21 views/layout/footer.php
-rwxr-xr-x 1 pete www-data  5655 Apr 23 08:01 views/layout/header.php
-rwxr-xr-x 1 pete pete      5499 Apr 18 08:08 views/login.php
-rw-rw-r-- 1 pete pete       975 Apr 23 08:16 views/order-tracking.php
-rwxr-xr-x 1 pete pete      5800 Apr 18 08:08 views/order_confirmation.php
-rw-rw-r-- 1 pete pete       756 Apr 23 08:16 views/privacy.php
-rwxr-xr-x 1 pete pete     32187 Apr 23 11:07 views/product_detail.php
-rwxr-xr-x 1 pete pete     14524 Apr 23 10:12 views/products.php
-rwxr-xr-x 1 pete www-data  5171 Apr 14 15:31 views/quiz.php
-rwxr-xr-x 1 pete www-data  4121 Apr 14 15:31 views/quiz_results.php
-rw-rw-r-- 1 pete pete      8959 Apr 18 08:10 views/register.php
-rwxr-xr-x 1 pete pete      7996 Apr 18 08:09 views/reset_password.php
-rw-rw-r-- 1 pete pete       768 Apr 23 08:16 views/shipping.php

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
