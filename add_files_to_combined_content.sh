#%#!/bin/bash
#%
#%cd /cdrom/project/The-Scent-oa5
#%
#%for i in index.php config.php includes/db.php views/home.php views/layout/header.php views/layout/footer.php views/cart.php controllers/BaseController.php ; do (echo -e "# $i  \n\`\`\`php" ; cat $i ; echo -e "\n\`\`\`\n" ) ; done | tee content_of_code_files_1.md
#%# for i in index.php config.php includes/db.php views/home.php views/layout/header.php views/layout/footer.php views/cart.php controllers/BaseController.php ; do (echo -e "# $i  \n\`\`\`php" ; cat $i ; echo -e "\n\`\`\`\n" ) ; done | tee content_of_code_files_1.md
#%
#%for i in controllers/CartController.php controllers/ProductController.php views/product_detail.php views/products.php models/Product.php  ; do (echo -e "# $i  \n\`\`\`php" ; cat $i ; echo -e "\n\`\`\`\n" ) ; done | tee content_of_code_files_2.md
#%
#%for i in includes/SecurityMiddleware.php controllers/ProductController.php  ; do (echo -e "# $i  \n\`\`\`php" ; cat $i ; echo -e "\n\`\`\`\n" ) ; done | tee content_of_code_files_3.md
#%
#%for i in controllers/AccountController.php controllers/NewsletterController.php controllers/CheckoutController.php  ; do (echo -e "# $i  \n\`\`\`php" ; cat $i ; echo -e "\n\`\`\`\n" ) ; done | tee content_of_code_files_4.md
#%
#%# for i in controllers/ProductController.php views/product_detail.php models/Product.php  ; do (echo -e "# $i  \n\`\`\`php" ; cat $i ; echo -e "\n\`\`\`\n" ) ; done | tee content_of_code_files_2.md
#%#
#%
for i in index.php config.php includes/db.php views/home.php views/layout/header.php views/layout/footer.php views/cart.php controllers/BaseController.php models/User.php models/Quiz.php ; do (echo -e "# $i  \n\`\`\`php" ; cat $i ; echo -e "\n\`\`\`\n" ) ; done | tee content_of_code_files_1.md
for i in controllers/CartController.php controllers/ProductController.php views/product_detail.php views/login.php views/products.php models/Product.php models/Order.php ; do (echo -e "# $i  \n\`\`\`php" ; cat $i ; echo -e "\n\`\`\`\n" ) ; done | tee content_of_code_files_2.md
for i in includes/SecurityMiddleware.php models/Cart.php includes/ErrorHandler.php js/main.js controllers/PaymentController.php controllers/TaxController.php controllers/InventoryController.php controllers/CouponController.php ; do (echo -e "# $i  \n\`\`\`php" ; cat $i ; echo -e "\n\`\`\`\n" ) ; done | tee content_of_code_files_3.md
for i in controllers/AccountController.php controllers/NewsletterController.php controllers/CheckoutController.php views/register.php views/quiz.php views/quiz_results.php views/order_confirmation.php views/order-tracking.php ; do (echo -e "# $i  \n\`\`\`php" ; cat $i ; echo -e "\n\`\`\`\n" ) ; done | tee content_of_code_files_4.md

for i in includes/EmailService.php views/account/dashboard.php views/account/order_details.php views/account/orders.php views/account/profile.php views/checkout.php includes/auth.php ; do (echo -e "# $i  \n\`\`\`php" ; cat $i ; echo -e "\n\`\`\`\n" ) ; done | tee content_of_code_files_5.md

for i in models/User.php models/Order.php models/Quiz.php  ; do (echo -e "# $i  \n\`\`\`php" ; cat $i ; echo -e "\n\`\`\`\n" ) ; done | tee content_of_code_files_6.md 
#### for i in index.php controllers/PaymentController.php controllers/CouponController.php  ; do (echo -e "# $i  \n\`\`\`php" ; cat $i ; echo -e "\n\`\`\`\n" ) ; done | tee content_of_code_files_6.md
#
#### for i in controllers/AccountController.php controllers/BaseController.php controllers/CartController.php controllers/CheckoutController.php ; do (echo -e "# $i  \n\`\`\`php" ; cat $i ; echo -e "\n\`\`\`\n" ) ; done | tee content_of_code_files_5.md

