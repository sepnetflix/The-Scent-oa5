# 🌿 The Scent - Premium Aromatherapy E-commerce Platform

Welcome to **The Scent**, a modern, full-featured, and beautifully crafted e-commerce platform built to showcase and sell premium natural aromatherapy products. This project is designed from the ground up for extensibility, security, and seamless user experience.

> 🧘 “Find your moment of calm” – Discover your perfect scent and enhance your well-being.

---

## 🚀 Live Demo

[🌐 Click here to view the demo](#) *(Coming Soon)*

---

## 🔖 Badges

![PHP](https://img.shields.io/badge/PHP-8.0+-blue?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange?logo=mysql)
![Apache](https://img.shields.io/badge/Apache-2.4+-red?logo=apache)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-2.x-blue?logo=tailwindcss)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 📚 Table of Contents

1. [🌟 Introduction](#-introduction)
2. [🎯 Features](#-features)
3. [🖼️ Screenshots](#-screenshots)
4. [🧱 Architecture](#-system-architecture)
5. [⚙️ Technology Stack](#-technology-stack)
6. [📁 Folder Structure](#-folder-structure)
7. [🗃️ Database Schema](#-database-schema)
8. [📦 Installation Instructions](#-installation-instructions)
9. [🚀 Deployment Guide Summary](#-deployment-guide-summary)
10. [🧪 Scent Finder Quiz](#-scent-quiz)
11. [🛡️ Security Best Practices](#-security-best-practices)
12. [🔧 Customization & Extensibility](#-customization--extensibility)
13. [🤝 Contributing](#-contributing)
14. [📄 License](#-license)
15. [🙏 Credits](#-credits)
16. [📎 Appendix](#-appendix)

---

## 🌟 Introduction

**The Scent** is more than just an e-commerce platform — it’s an experience. Built specifically to support the sale and recommendation of **premium aromatherapy products**, the platform integrates:

- A clean, modern, responsive UI/UX
- Personalized shopping via a scent quiz
- Dynamic product catalog and featured collections
- Flexible cart and order system
- Modular codebase for easy customization and future growth

Designed for extensibility, performance, and user-centric experience, The Scent is a robust foundation for wellness or natural product businesses.

---

## 🎯 Features

### 🛍️ Core E-commerce
- **Modern Landing Page** with video and animated hero
- **Product Catalog** with categories and featured items
- **Product Detail Pages** with gallery, descriptions, and related items
- **Personalized Scent Finder Quiz**
- **Product Recommendations** based on quiz results
- **Responsive Design (Mobile-Friendly)**
- **AJAX-powered Add-to-Cart** from various pages
- **AJAX Newsletter Signup**
- **Known Issue:** Product list pagination currently displays only the first page of results.

### 🔐 User Management
- **User Authentication (Login/Register)**
- **Password Reset System**
- **User Profile Management** *(Basic structure)*
- **Order History & Tracking** *(Basic structure)*

### 🛒 Shopping Experience
- **Functional Shopping Cart Page** (Display fixed, AJAX updates for quantity/removal)
- **Mini-Cart** dropdown in header (AJAX updated)
- **Stock Validation** during Add-to-Cart and Checkout
- **Price Calculations**
- **Secure Checkout Process** *(Requires Login)*
- **Order Confirmation Page**

### 💼 Business Features *(partially implemented / extensible)*
- **Inventory Management** *(Basic stock tracking)*
- **Tax System** *(Basic implementation)*
- **Coupon System** *(Planned)*

### 📧 Communication
- **Email Notification System** (Order confirmation, password reset, newsletter welcome - Requires SMTP setup)

### 👑 Admin Features *(modular, basic implementation; dashboard expansion planned)*
- **Basic Product Management Views/Logic** *(Requires Admin Role)*
- **Basic Order Processing Views/Logic** *(Requires Admin Role)*
- **Basic User Management Views/Logic** *(Requires Admin Role)*

---

## 🖼️ Screenshots

> 📸 Full resolution screenshots are available in the `/images/screenshots/` folder.

| Page | Screenshot |
|------|------------|
| Landing Page | ![Home](images/screenshots/home.png) |
| Product Details | ![Product](images/screenshots/product.png) |
| Quiz Intro | ![Quiz](images/screenshots/quiz.png) |
| Quiz Results | ![Results](images/screenshots/results.png) |
| Cart Page | ![Cart](images/screenshots/cart.png) |

*(If these files are missing, please add screenshots or update/remove this section.)*

---

## 🧱 System Architecture

**Custom MVC-Inspired Modular PHP Architecture:**

```
[Browser/Client]
   ↓
[Apache2 Server]
   ↓ (.htaccess rewrite)
[index.php] → [Controllers] → [Models / PDO] → [MySQL DB]
        ↓          ↑                ↑
     [Views] ←---(Data)←---------+
        ↓
     [Includes (layout, db, auth, security)]
        ↓
    [Session Management, CSRF Protection, Headers]
```

- `index.php`: Front Controller, routing, core initialization, global CSRF validation.
- `Controllers`: Business logic and request handling (Cart, Product, Quiz, Account, etc.). Extend `BaseController`.
- `Models`: Database interaction logic (using PDO Prepared Statements).
- `Views`: Server-rendered HTML templates (PHP files mixing HTML & PHP logic).
- `Includes`: Shared core utilities (header/footer layouts, authentication helpers, database connection, security middleware, error handler).
- `config.php`: Central configuration for DB, security, and application settings.

---

## ⚙️ Technology Stack

| Layer | Technology |
|-------|------------|
| Frontend | HTML5, Tailwind CSS (CDN), Custom CSS (`css/style.css`), JavaScript (Vanilla, AOS.js, Particles.js), Font Awesome 6 (CDN) |
| Backend | PHP 8.0+, Apache 2.4+ |
| Database | MySQL 5.7+ (or MariaDB equivalent) |
| Server-Side Libs | PDO (for DB access) |
| Optional | Docker, Composer (for future dependency management) |

---

## 📁 Folder Structure

```
/cdrom/project/The-Scent-oa5  # Web root, assets, entry point
├── index.php             # Main entry/routing script
├── config.php            # DB, Security, App configuration
├── css/                  # Custom CSS (style.css)
├── images/               # Product, hero, and UI images
├── videos/               # Hero background video(s)
├── particles.json        # Particles.js settings
├── .htaccess             # URL rewriting & security
├── includes/             # Shared PHP core scripts
│   ├── auth.php          # Authentication helpers
│   ├── db.php            # Database connection ($pdo)
│   ├── SecurityMiddleware.php # Validation, CSRF, Headers, Session setup
│   ├── ErrorHandler.php  # Global error handling
│   └── EmailService.php  # Email sending logic
├── controllers/          # Business logic controllers
│   ├── BaseController.php# Abstract base class for controllers
│   ├── ProductController.php
│   ├── CartController.php
│   ├── QuizController.php
│   └── ... (Account, Checkout, etc.)
├── models/               # Database interaction models
│   ├── Product.php
│   ├── Cart.php
│   └── ... (User, Order, Quiz)
├── views/                # HTML/PHP templates
│   ├── home.php
│   ├── products.php      # Product list (Pagination broken)
│   ├── product_detail.php
│   ├── cart.php          # Cart view (Functional)
│   ├── layout/
│   │   ├── header.php
│   │   └── footer.php    # Includes global JS handlers
│   └── ... (others: quiz, login, register, checkout, etc.)
├── admin/                # (Basic, extensible) Admin views/controllers
├── logs/                 # Log files directory (requires write permissions)
│   ├── security.log
│   ├── error.log
│   └── audit.log
├── db/                   # Database schema and seed data
│   └── the_scent_schema.sql.txt # Current schema definition
├── README.md             # Project documentation (This file)
├── technical_design_specification.md # Detailed technical docs (v8.0)
├── suggested_improvements_and_fixes.md # Code review findings
└── LICENSE               # MIT License file
```

---

## 🗃️ Database Schema

### ➕ Core Tables

- `users` – Authentication, roles
- `products` – Product catalog (includes details like `description`, `short_description`, `image`, `benefits`, `ingredients`, `stock_quantity`, `category_id`, etc.)
- `categories` – Product categories
- `orders` – Order headers
- `order_items` – Order lines
- `cart_items` – Shopping cart items (primarily for logged-in users, see note below)
- `quiz_results` – Scent quiz results
- `newsletter_subscribers` – Newsletter opt-ins
- `audit_log` – Records significant actions

### 🔑 ER Diagram (Simplified)

```
users ───< orders ───< order_items >─── products
   |                                        ↑
   +───< cart_items >───────────────────────+
   |
   +───< quiz_results
   |
   +───< audit_log (user_id nullable)

products >─── categories
```

See [`db/the_scent_schema.sql.txt`](db/the_scent_schema.sql.txt) for the full schema.
**Note on `cart_items`:** While the table exists, the application currently relies heavily on `$_SESSION['cart']` for cart management, especially for guests. `models/Cart.php` interacts with the DB table, but consistency could be improved (See Recommendation 3 in `suggested_improvements_and_fixes.md`).

---

## 📦 Installation Instructions

### 1. Clone the repo

```bash
git clone https://github.com/sepnetflix/The-Scent-gpt6.git # Replace with actual repo URL if different
cd The-Scent-gpt6
```

### 2. Set up the database

```sql
-- Using MySQL CLI or a GUI tool:
CREATE DATABASE the_scent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'scent_user'@'localhost' IDENTIFIED BY 'StrongPassword123'; -- Use a strong, unique password
GRANT ALL PRIVILEGES ON the_scent.* TO 'scent_user'@'localhost';
FLUSH PRIVILEGES;
```

Then import the schema:

```bash
mysql -u scent_user -p the_scent < db/the_scent_schema.sql.txt
```
*(Enter the password when prompted)*

### 3. Configure `config.php`

Edit the `config.php` file in the project root and set your DB credentials and other environment settings:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'the_scent');
define('DB_USER', 'scent_user');
define('DB_PASS', 'StrongPassword123'); // Use the password you set
// Review other settings like SMTP, Stripe keys (if applicable)
```

### 4. Set permissions

Ensure the web server (e.g., `www-data`) has write permissions to the `logs/` directory:

```bash
# Navigate to the project root directory
mkdir -p logs # Create logs directory if it doesn't exist
sudo chown www-data:www-data logs
sudo chmod 755 logs # Or 775 if needed, ensure security implications are understood
# Secure config file (readable by owner and group, not others)
sudo chmod 640 config.php
sudo chown $(whoami):www-data config.php # Owner: current user, Group: web server
```

### 5. Set up Apache Virtual Host

Configure an Apache Virtual Host to point to the project's root directory. Ensure `mod_rewrite` is enabled and `AllowOverride All` is set for the directory to allow `.htaccess` rules.

Example basic Virtual Host config (`/etc/apache2/sites-available/the-scent.conf`):

```apache
<VirtualHost *:80>
    ServerName the-scent.local # Or your desired local domain
    DocumentRoot /path/to/your/project/The-Scent-oa5 # Use absolute path

    <Directory /path/to/your/project/The-Scent-oa5>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/the-scent-error.log
    CustomLog ${APACHE_LOG_DIR}/the-scent-access.log combined
</VirtualHost>
```

Enable the site and restart Apache:
```bash
sudo a2ensite the-scent.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```
*(Remember to add `the-scent.local` or your chosen domain to your `/etc/hosts` file if testing locally: `127.0.0.1 the-scent.local`)*

---

## 🚀 Deployment Guide Summary

See [`deployment_guide.md`](deployment_guide.md) *(if available)* for full instructions.

### Basic LAMP Deployment Steps:
1.  Transfer project files to the server.
2.  Set up the database and user.
3.  Import the schema (`the_scent_schema.sql.txt`).
4.  Configure `config.php` with production database credentials and settings.
5.  Set appropriate file permissions (`logs/` writable by web server, `config.php` restricted).
6.  Configure Apache VirtualHost (`DocumentRoot`, `AllowOverride All`).
7.  Enable `mod_rewrite`.
8.  Secure the server (HTTPS, firewall, regular updates).
9.  Consider moving sensitive config out of `config.php` into environment variables or a `.env` file outside the web root.

---

## 🧪 Scent Quiz

The scent quiz helps users discover personalized product recommendations.

- **Flow:** User answers questions on `/views/quiz.php`. Submission handled by `QuizController::processQuiz`. Results displayed on `/views/quiz_results.php`.
- **Logic:** `QuizController` contains logic to map answers to product attributes or categories and fetch recommendations from `models/Product.php`.
- **Persistence:** Results can be stored for logged-in users in the `quiz_results` table.

---

## 🛡️ Security Best Practices Implemented

Security is a core consideration. Key measures include:

### 🔐 Authentication & Authorization
- Passwords hashed using `password_hash()` (bcrypt default).
- Login verification using `password_verify()`.
- Secure session management (HttpOnly, Secure, SameSite cookies, periodic ID regeneration).
- Role-based access control checks (`isAdmin()`, `requireLogin()`, `requireAdmin()`).

### 🛡️ Input/Output Handling
- Input validation using `filter_var` and custom checks via `SecurityMiddleware::validateInput`.
- Output escaping using `htmlspecialchars()` in views to prevent XSS.
- **SQL Injection Prevention:** Consistent use of **PDO Prepared Statements** in all database interactions (Models and direct PDO usage).

### 🔄 CSRF Protection
- **Synchronizer Token Pattern:** Implemented globally.
- Tokens generated per session (`SecurityMiddleware::generateCSRFToken`).
- Tokens embedded in forms (`name="csrf_token"`) and globally for AJAX (`id="csrf-token-value"`).
- Automatic validation on ALL POST requests in `index.php` via `SecurityMiddleware::validateCSRF`.
- AJAX handlers explicitly read token from `#csrf-token-value` and include it in requests.

### 🔒 Server & Configuration
- Security headers (X-Frame-Options, XSS Protection, CSP, etc.) applied via `SecurityMiddleware::apply` based on `config.php`.
- `.htaccess` used for URL rewriting and potentially basic access restrictions.
- Recommended secure file permissions for `config.php` and `logs/`.

### 🏦 Rate Limiting
- Basic mechanism available via `BaseController::validateRateLimit` (intended to use APCu).
- **Status:** Currently implemented inconsistently across controllers. Standardization recommended (See Recommendation 4).

---

## 🔧 Customization & Extensibility

The codebase is designed to be extended:

### ➕ Add a New Product
- Primarily through DB insertion or a future Admin UI.
- Ensure all relevant fields in `products` table are populated, including `image`, `category_id`, `price`, `name`, `stock_quantity`.

### ➕ Add a New Page/Controller
1.  Create a new controller file in `controllers/` extending `BaseController`.
2.  Create corresponding view file(s) in `views/`.
3.  Add a `case` for the new page name in the `switch ($page)` block in `index.php`.
4.  Include/instantiate the controller and call the appropriate action method within the `case`.
5.  Remember to implement the CSRF token pattern if the page involves POST actions.

### 🎨 Customize Appearance
- Modify Tailwind utility classes directly in view files.
- Add custom styles to `/css/style.css`.
- Update Tailwind configuration within `<script>` tag in `views/layout/header.php` if needed (or ideally move to a build process).

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

### 🧾 Code Standards
- Adhere to PSR-12 PHP coding standards.
- Use semantic HTML5.
- Prefer TailwindCSS utility classes; use custom CSS (`style.css`) for complex components or overrides.
- Ensure code is well-commented, especially complex logic.

### 🛠️ How to Contribute
1.  Fork the repository.
2.  Create a feature branch (`git checkout -b feature/YourFeature`).
3.  Commit your changes (`git commit -m 'Add some feature'`).
4.  Push to the branch (`git push origin feature/YourFeature`).
5.  Open a Pull Request.

### 📌 Issues & Bugs
Report bugs or suggest features via the project's Issue Tracker.

---

## 📄 License

Distributed under the **MIT License**.
See the [LICENSE](LICENSE) file for details.

---

## 🙏 Credits

- **Frameworks/Libraries:** Tailwind CSS, AOS.js, Particles.js, Font Awesome
- **Inspiration/Assistance:** OpenAI ChatGPT, PHP & MySQL Communities
- **Imagery:** Placeholder images, potential sources like Unsplash/Pexels

---

## 📎 Appendix

### 📘 Related Documentation
- [`technical_design_specification.md`](./technical_design_specification.md) (v8.0)
- [`suggested_improvements_and_fixes.md`](./suggested_improvements_and_fixes.md) (v1.0)

### 🧪 Testing Scenarios
- ✅ Can register and sign in/out.
- ✅ Can browse featured products on homepage.
- ✅ Can view product list page (Category filters work, Sorting works).
- ❌ Can paginate through product list (**Known Issue:** Displays page 1 only).
- ✅ Can view product detail page.
- ✅ Can add items to cart (from Home, Product List, Product Detail).
- ✅ Can view Cart page (display correct).
- ✅ Can update quantity / remove items from Cart page (AJAX).
- ✅ Can proceed to Checkout (requires login).
- ✅ Can take the Scent Quiz and view results.
- ✅ Can subscribe to newsletter (AJAX).
- ☐ Can complete checkout process (Requires payment gateway integration).
- ☐ Can view order history (Requires implementation).
- ☐ Can use admin features (Requires admin user & implementation).

### 🔮 Future Enhancements / Recommendations
- **Fix Product Pagination Logic.**
- Standardize Rate Limiting Implementation.
- Implement DB-backed Carts for logged-in users.
- Tighten Content Security Policy (CSP).
- Implement Payment Gateway Integration (Stripe/PayPal).
- Develop full Admin Panel (CRUD operations, analytics).
- Implement PSR-4 Autoloader (Composer).
- Refactor routing away from `index.php` switch.
- Add Unit/Integration Tests.

---

## 📫 Contact

- Project Maintainer/Support: [Specify Contact Method Here]
- GitHub Issues: [Link to Issues]

---

Built with ❤️ for aromatherapy enthusiasts.
