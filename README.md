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
- **Essential Oil & Soap Catalog** with categories and featured items
- **Personalized Scent Finder Quiz**
- **Product Recommendations**
- **Responsive Design (Mobile-Friendly)**

### 🔐 User Management
- **User Authentication (Login/Register)**
- **Password Reset System**
- **User Profile Management**
- **Order History & Tracking**

### 🛒 Shopping Experience
- **Real-time Shopping Cart** (AJAX updates)
- **Stock Validation**
- **Price Calculations**
- **Secure Checkout Process**
- **Order Confirmation**

### 💼 Business Features *(partially implemented / extensible)*
- **Inventory Management**
- **Tax System**
- **Coupon System** *(planned)*

### 📧 Communication
- **Email Notification System** (Order confirmation, password reset, newsletter)
- **Newsletter Signup with AJAX**

### 👑 Admin Features *(modular, basic implementation; dashboard expansion planned)*
- **Product Management**
- **Order Processing**
- **User Management**
- **Inventory Control**

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
   ↓
[index.php] → [Controllers] → [Models (optional/planned)] → [MySQL DB]
        ↓
     [Views] 
        ↓
   [Includes (header, footer, db, auth, security)]
        ↓
    [Sessions, CSRF, Middleware]
```

- `Controllers`: Business logic and request routing (Cart, Product, Quiz, Account, etc.)
- `Models`: (Optional/Planned) Database abstraction
- `Views`: Server-rendered HTML templates
- `Includes`: Shared core (header, footer, authentication, database, security)
- ``: Web root for assets and entry point

---

## ⚙️ Technology Stack

| Layer | Technology |
|-------|------------|
| Frontend | HTML5, Tailwind CSS, Custom CSS, AOS.js, Particles.js, Font Awesome |
| Backend | PHP 8.0+, Apache2 |
| Database | MySQL 5.7+ |
| Animations | AOS.js (fade/slide), Particles.js |
| Version Control | Git |
| Optional | Docker, Composer |

---

## 📁 Folder Structure

```
/cdrom/project/The-Scent-oa5  # Web root, assets, entry point
├── index.php             # Main entry/routing script
├── css/                  # Main CSS (style.css)
├── images/               # Product, hero, and UI images
├── videos/               # Hero background video(s)
├── particles.json        # Particles.js settings
└── .htaccess             # URL rewriting
├── includes/                 # Shared PHP scripts
│   ├── auth.php
│   ├── db.php
│   ├── SecurityMiddleware.php
│   └── ErrorHandler.php
├── controllers/              # Business logic controllers
│   ├── ProductController.php
│   ├── CartController.php
│   ├── QuizController.php
│   └── ... (others)
├── models/                   # (Optional/Planned) DB abstraction
├── views/                    # HTML templates
│   ├── home.php
│   ├── layout/
│   │   ├── header.php
│   │   └── footer.php
│   └── ... (others)
├── admin/                    # (Basic, extensible) Admin dashboard
├── db/                       # Database schema and seed data
│   └── schema.sql
├── config.php                # DB and app configuration
├── .env                      # (Optional) Environment variables
├── README.md                 # Project documentation
├── technical_design_specification.md
├── deployment_guide.md
└── LICENSE
```

---

## 🗃️ Database Schema

### ➕ Core Tables

- `users` – Authentication, roles
- `products` – Product catalog
- `categories` – Product categories
- `orders` – Order headers
- `order_items` – Order lines
- `cart_items` – Shopping cart (user/session)
- `quiz_results` – Scent quiz results
- `newsletter_subscribers` – Newsletter opt-ins

### 🔑 ER Diagram (Simplified)

```
users ───< orders ───< order_items >─── products
products >─── categories
users ───< quiz_results
```

See [`db/schema.sql`](db/schema.sql) for full schema.

---

## 📦 Installation Instructions

### 1. Clone the repo

```bash
git clone https://github.com/sepnetflix/The-Scent-gpt6.git
cd The-Scent-gpt6
```

### 2. Set up the database

```sql
CREATE DATABASE the_scent;
GRANT ALL ON the_scent.* TO 'scent_user'@'localhost' IDENTIFIED BY 'your_password';
```

Then import the schema:

```bash
mysql -u scent_user -p the_scent < db/schema.sql
```

### 3. Configure `/config.php`

Set your DB and app config:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'the_scent');
define('DB_USER', 'scent_user');
define('DB_PASS', 'your_password');
```

### 4. Set permissions

```bash
chmod -R 755 uploads
chown -R www-data:www-data uploads
```

### 5. Set up Apache

- Enable `mod_rewrite`
- Set `DocumentRoot` to `/public`
- Make sure `.htaccess` is enabled

---

## 🚀 Deployment Guide Summary

See [`deployment_guide.md`](deployment_guide.md) for full instructions.

### Basic LAMP Deployment

- Apache VirtualHost with `DocumentRoot /public`
- Enable `mod_rewrite`
- Secure `includes/`, `models/`, `config.php`

### Optional Docker Setup

```bash
docker-compose up -d
```

Includes:
- PHP + Apache
- MySQL
- Volume mounting
- Exposed ports

---

## 🧪 Scent Quiz

The scent quiz is a unique feature that helps users discover personalized product recommendations by selecting their **mood or need** (Relaxation, Energy, Focus, Sleep, Balance, etc.).

- Quiz logic is implemented in `/controllers/QuizController.php` and `/views/quiz.php`.
- Results are mapped to product recommendations.
- Results can be emailed or stored for logged-in users.

---

## 🛡️ Security Best Practices

Security is a top priority in *The Scent*. The platform includes several measures to protect user data and maintain safe operations across the stack.

### 🔐 Authentication

- Passwords are hashed using `password_hash()` (bcrypt).
- Login uses `password_verify()` with secure session handling.

### 🛡️ Input Sanitization

- All inputs are validated and sanitized.
- Output is escaped via `htmlspecialchars()` to prevent XSS.
- Prepared statements and PDO are used to prevent SQL injection.

### 🔄 CSRF Protection

- CSRF tokens are generated per session and validated on all POST forms (including AJAX).
- Example:

```php
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
```

### 🔒 File Permissions

- `config.php` permissions are set to `640`
- Upload directories like `uploads` are restricted for webserver use only

```bash
chmod 640 config.php
chown www-data:www-data config.php
```

### 🏦 Rate Limiting

- Login, registration, and sensitive flows are rate-limited to prevent brute force.

---

## 🔧 Customization & Extensibility

The codebase is modular and easy to extend.

### ➕ Add a New Product

Add via the admin dashboard (if enabled) or directly to the `products` table:

```sql
INSERT INTO products (name, price, image, category_id, stock_quantity, short_description)
VALUES ('New Scent Oil', 29.99, '/images/scent9.jpg', 1, 50, 'A calming blend...');
```

Set `is_featured = 1` to feature it on the homepage.

### ➕ Add a New Quiz Option

Update quiz mapping logic in `/controllers/QuizController.php`:

```php
// Example mapping
'confidence' => [9, 10] // New mood mapping
```

Update the quiz form in `/views/quiz.php`.

### 🔐 Add Admin Roles

Add an `admin` role in `users` table and restrict admin URLs:

```sql
ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user';
```

```php
if ($_SESSION['user']['role'] !== 'admin') {
    die("Access Denied");
}
```

### 💳 Integrate Stripe or PayPal *(planned/future)*

Payment integration points are in place in the checkout flow for future Stripe/PayPal modules.

---

## 🤝 Contributing

We welcome contributions from the community!

### 🧾 Code Standards

- Follow PSR-12 PHP coding standards
- Use semantic HTML5
- TailwindCSS utility classes and custom CSS for styling
- Reusable components (header/footer)

### 🛠️ How to Contribute

1. Fork the repository
2. Create a new branch (`feature/my-enhancement`)
3. Make your changes
4. Commit with descriptive messages
5. Push to your fork
6. Open a PR

### 📌 Issues & Bugs

Please use the [Issues](https://github.com/sepnetflix/The-Scent-gpt6/issues) tab to report bugs or request features.

---

## 📄 License

Distributed under the **MIT License**.  
You are free to use, modify, and distribute this code with attribution.

See the [LICENSE](LICENSE) file for full text.

---

## 🙏 Credits

This project wouldn’t be possible without:

- **Tailwind CSS** – Utility-first CSS framework
- **AOS.js** – Animate on scroll library
- **Particles.js** – Beautiful background effects
- **Font Awesome** – Icon library
- **Unsplash & Pexels** – Background imagery
- **OpenAI ChatGPT** – Assisted architectural planning and documentation
- **PHP + MySQL Community** – For decades of server-side inspiration

---

## 📎 Appendix

### 📘 Related Documentation

- [`technical_design_specification.md`](./technical_design_specification.md)
- [`deployment_guide.md`](./deployment_guide.md)

### 🧪 Testing Scenarios

- ✅ Can register and sign in as a user
- ✅ Can browse featured products
- ✅ Can take the scent quiz
- ✅ Can add items to cart and checkout
- ✅ Can access restricted admin panel (if role = admin)
- ✅ Can subscribe to newsletter

### 🔮 Future Enhancements

- Stripe/PayPal payment integration (planned)
- Email receipts and order tracking
- Advanced scent quiz (multi-step logic)
- Admin panel with analytics and audit trails
- RESTful API for mobile apps
- React/Vue frontend version

---

## 📫 Contact

Have questions or feedback?

- Email: support@thescent.com
- Twitter: [@thescentaroma](https://twitter.com/thescentaroma)
- GitHub Issues: [Submit Bug](https://github.com/sepnetflix/The-Scent-gpt6/issues)

---

## 🚀 Final Words

> *“The Scent is not just a store — it’s a journey into serenity, balance, and well-being.”*

Built with ❤️ to help people discover the power of aromatherapy.

Thank you for checking out this project.  
Please consider ⭐ starring the repo if you found it useful!
