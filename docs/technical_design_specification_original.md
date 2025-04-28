# The Scent – Technical Design Specification (Deep-Dive Edition)

---

## Table of Contents

1. [Introduction](#introduction)
2. [Project Philosophy & Goals](#project-philosophy--goals)
3. [System Architecture Overview](#system-architecture-overview)
   - 3.1 [High-Level Workflow](#high-level-workflow)
   - 3.2 [Request-Response Life Cycle](#request-response-life-cycle)
4. [Directory & File Structure](#directory--file-structure)
   - 4.1 [Folder Map](#folder-map)
   - 4.2 [Key Files Explained](#key-files-explained)
5. [Routing and Application Flow](#routing-and-application-flow)
   - 5.1 [URL Routing via .htaccess](#url-routing-via-htaccess)
   - 5.2 [public/index.php: The Application Entry Point](#publicindexphp-the-application-entry-point)
   - 5.3 [Controller Dispatch & Action Flow](#controller-dispatch--action-flow)
   - 5.4 [Views: Templating and Rendering](#views-templating-and-rendering)
6. [Frontend Architecture](#frontend-architecture)
   - 6.1 [CSS (public/css/style.css) & Tailwind Integration](#css-publiccssstylecss--tailwind-integration)
   - 6.2 [Responsive Design and Accessibility](#responsive-design-and-accessibility)
   - 6.3 [JavaScript: Interactivity, AOS.js, Particles.js](#javascript-interactivity-aosjs-particlesjs)
7. [Key Pages & Components](#key-pages--components)
   - 7.1 [Home/Landing Page (views/home.php)](#homelanding-page-viewshomephp)
   - 7.2 [Header and Navigation (views/layout/header.php)](#header-and-navigation-viewslayoutheaderphp)
   - 7.3 [Footer and Newsletter (views/layout/footer.php)](#footer-and-newsletter-viewslayoutfooterphp)
   - 7.4 [Product Grid & Cards](#product-grid--cards)
   - 7.5 [Quiz Flow & Personalization](#quiz-flow--personalization)
8. [Backend Logic & Core PHP Components](#backend-logic--core-php-components)
   - 8.1 [Includes: Shared Logic (includes/)](#includes-shared-logic-includes)
   - 8.2 [Controllers: Business Logic Layer](#controllers-business-logic-layer)
   - 8.3 [Database Abstraction (includes/db.php)](#database-abstraction-includesdbphp)
   - 8.4 [Security Middleware & Error Handling](#security-middleware--error-handling)
   - 8.5 [Session, Auth, and User Flow](#session-auth-and-user-flow)
9. [Database Design](#database-design)
   - 9.1 [Entity-Relationship Model](#entity-relationship-model)
   - 9.2 [Main Tables & Sample Schemas](#main-tables--sample-schemas)
   - 9.3 [Data Flow Examples](#data-flow-examples)
10. [Security Considerations](#security-considerations)
    - 10.1 [Input Sanitization & Validation](#input-sanitization--validation)
    - 10.2 [Session Management](#session-management)
    - 10.3 [CSRF Protection](#csrf-protection)
    - 10.4 [Security Headers & Rate Limiting](#security-headers--rate-limiting)
    - 10.5 [File Uploads & Permissions](#file-uploads--permissions)
    - 10.6 [Audit Logging & Error Handling](#audit-logging--error-handling)
11. [Extensibility & Onboarding](#extensibility--onboarding)
    - 11.1 [Adding Features, Pages, or Controllers](#adding-features-pages-or-controllers)
    - 11.2 [Adding Products, Categories, and Quiz Questions](#adding-products-categories-and-quiz-questions)
    - 11.3 [Developer Onboarding Checklist](#developer-onboarding-checklist)
    - 11.4 [Testing & Debugging](#testing--debugging)
12. [Future Enhancements & Recommendations](#future-enhancements--recommendations)
13. [Appendices](#appendices)
    - A. [Key File Summaries](#a-key-file-summaries)
    - B. [Glossary](#b-glossary)
    - C. [Code Snippets and Patterns](#c-code-snippets-and-patterns)

---

## 1. Introduction

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control.

This document is the definitive technical design specification for The Scent’s codebase. It is intended to offer deep insight into the system’s structure, logic, and flow, and to serve as a comprehensive onboarding and reference guide for both current developers and future maintainers.

---

## 2. Project Philosophy & Goals

- **Security First:** All data input and user interactions are validated and sanitized. Strong session and CSRF protection are enforced.
- **Simplicity & Maintainability:** Clear, modular code structure. No over-engineered abstractions.
- **Extensibility:** Easy to add new features, pages, controllers, or views.
- **Performance:** Direct routing, optimized queries, and minimized external dependencies.
- **Modern User Experience:** Responsive design, smooth animations (AOS.js, Particles.js), and AJAX interactions for cart and newsletter.
- **Transparency:** No magic – all application flow and routing is explicit in code.
- **Accessibility & SEO:** Semantic HTML, ARIA attributes where needed, and meta tags for discoverability.

---

## 3. System Architecture Overview

### 3.1 High-Level Workflow

```
[Browser/Client]
   |
   |  (HTTP request)
   v
[Apache2 Web Server]
   |
   |  (URL rewriting via .htaccess)
   v
[public/index.php]  <-- ENTRY POINT
   |
   |  (Routing logic)
   v
[Controller]  (e.g., ProductController, CartController)
   |
   |  (Business logic, DB access)
   v
[Model or DB Layer]  (if any)
   |
   |  (Fetch/prepare data)
   v
[View]  (e.g., views/home.php)
   |
   |  (HTML/CSS/JS output)
   v
[Browser/Client]
```

### 3.2 Request-Response Life Cycle

1. **Request Initiation:** User navigates to any URL (e.g., `/`, `/index.php?page=products`, etc.)
2. **.htaccess Rewrite:** All requests are rewritten to `public/index.php`, passing query strings as needed.
3. **Initialization:** `public/index.php` loads configuration, database, authentication, security, and error handling.
4. **Routing:** Based on `$_GET['page']` and `$_GET['action']`, the correct controller and view are selected.
5. **Controller Action:** Controller does any required business logic (database fetch, form processing, etc.).
6. **View Rendering:** The correct view (template) is included, outputting HTML/CSS/JS.
7. **Response:** Output is sent to the browser, which renders the page and runs frontend JS (AOS, Particles, AJAX).
8. **AJAX Interactions:** For cart/newsletter, AJAX requests update the UI without a full page reload.
9. **Security:** Every step is subject to security middleware, CSRF checks, and error handling.

---

## 4. Directory & File Structure

### 4.1 Folder Map

```
/ (project root)
|-- public/                # Web root, entry point, public assets
|   |-- index.php          # Main entry script (routing, dispatch)
|   |-- css/
|   |   |-- style.css      # Main CSS (custom + Tailwind utility classes)
|   |-- images/            # Public image assets
|   |-- videos/            # Public video assets (hero background)
|   |-- particles.json     # Particles.js config for ambient backgrounds
|   |-- .htaccess          # URL rewrite rules for clean routing
|
|-- includes/              # Shared PHP includes
|   |-- auth.php           # User login/logout/session helpers
|   |-- db.php             # Database connection via PDO
|   |-- SecurityMiddleware.php # CSRF, input validation, security headers
|   |-- ErrorHandler.php   # Centralized error handling/logging
|   |-- EmailService.php   # Email sending logic (SMTP)
|
|-- controllers/           # Business logic / request handlers
|   |-- ProductController.php
|   |-- CartController.php
|   |-- CheckoutController.php
|   |-- AccountController.php
|   |-- QuizController.php
|   |-- ... (others for newsletter, payment, etc.)
|
|-- models/                # (Optional/Planned) Database abstraction
|   |-- Product.php
|   |-- User.php
|   |-- Order.php
|   |-- ... (future)
|
|-- views/                 # HTML templates (server-rendered)
|   |-- home.php           # Main landing page
|   |-- products.php       # Product listing
|   |-- product_detail.php # Individual product page
|   |-- cart.php           # Shopping cart
|   |-- checkout.php       # Checkout process
|   |-- register.php       # Registration
|   |-- login.php          # Login form
|   |-- quiz.php           # Scent quiz interface
|   |-- quiz_results.php   # Quiz results
|   |-- error.php          # Error display
|   |-- layout/
|   |   |-- header.php     # Sitewide header/nav
|   |   |-- footer.php     # Sitewide footer/newsletter/social
|   |-- ... (404.php, emails/, admin/, etc.)
|
|-- config.php             # Environment, DB, and app configuration
|-- .env                   # (Optional) Environment variables, secrets
|-- README.md              # Project intro/instructions
|-- technical_design_specification.md # This document
```

### 4.2 Key Files Explained

- **public/index.php**: Application entry. Implements custom routing, loads core dependencies, and delegates to appropriate controllers/views.
- **public/css/style.css**: Aggregates custom CSS, Tailwind utility classes, and responsive/animation styles for the whole site.
- **public/particles.json**: Defines particle backgrounds for aesthetic animation (used by Particles.js).
- **public/.htaccess**: Apache rewrite rules for clean URLs (all traffic routed to index.php).
- **includes/db.php**: Establishes PDO DB connection, with error logging and secure defaults.
- **includes/auth.php**: User session management, login/logout, registration, permission checks.
- **includes/SecurityMiddleware.php**: Input validation, CSRF token generation/validation, security headers.
- **views/layout/header.php**: Navigation bar, logo, mobile menu, includes CSS/JS, outputs session flash messages.
- **views/layout/footer.php**: Footer links, newsletter form, social icons, JS for interactivity (AOS, AJAX).
- **views/home.php**: Main landing page, integrating hero section, featured products, benefits, quiz CTA, testimonials, and newsletter.
- **controllers/ProductController.php**: Handles retrieving products, showing home page, etc.
- **controllers/CartController.php**: Logic for cart operations (add, remove, update).
- **controllers/QuizController.php**: Logic for quiz steps, results, and analytics.
- **config.php**: Central configuration for DB, email, Stripe, security settings, etc.

---

## 5. Routing and Application Flow

### 5.1 URL Routing via .htaccess

**public/.htaccess** (typical content):

```apache
# Enable URL rewriting
RewriteEngine On
# Route all requests to index.php, preserving query params
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

- All requests (except direct file/image access) are handled by `public/index.php`.
- URLs like `/`, `/index.php?page=home`, `/index.php?page=product&id=3` are all routed through the same entry point.

### 5.2 public/index.php: The Application Entry Point

**Key Responsibilities:**
- Define `ROOT_PATH` for easy file referencing.
- Require all core includes (config, DB, auth, security, error handler).
- Initialize error handling and apply security middleware (headers, session checks).
- Determine which page/action is being requested via `$_GET` (with input validation).
- Validate CSRF tokens on POST requests.
- Dispatch to the correct controller and action:
  - Home, product, products, cart, checkout, register, quiz, admin, etc.
- Redirect or render the appropriate view, passing in any required data.
- Handle errors with try/catch, logging, and graceful user feedback.

**Excerpt (simplified):**
```php
$page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string');
$action = SecurityMiddleware::validateInput($_GET['action'] ?? 'index', 'string');

switch ($page) {
    case 'home':
        $productController->showHomePage();
        break;
    case 'cart':
        $controller = new CartController($pdo);
        // ...cart logic...
        require_once ROOT_PATH . '/views/cart.php';
        break;
    // ...other cases...
    default:
        http_response_code(404);
        require_once ROOT_PATH . '/views/404.php';
        break;
}
```

### 5.3 Controller Dispatch & Action Flow

- Each controller (e.g., ProductController, CartController) encapsulates business logic for a domain.
- Controllers typically:
  - Fetch data from DB via `$pdo`
  - Perform input validation and security checks
  - Set up any variables or session state required by the views
  - Call or render the appropriate view template with the data

### 5.4 Views: Templating and Rendering

- Views are plain PHP files that output HTML, interpolating any server-side data as needed.
- Common includes: `views/layout/header.php` and `views/layout/footer.php` for sitewide consistency.
- Views use Bootstrap/Tailwind/utilities and custom CSS for styling.
- Some views (e.g., home.php, products.php) loop through arrays of products or categories, outputting dynamic content.
- AJAX endpoints (e.g., for cart add/remove, newsletter signup) return JSON or status updates for the frontend JS.

---

## 6. Frontend Architecture

### 6.1 CSS (public/css/style.css) & Tailwind Integration

- **CSS Variables:** Define color palette, spacing, font families, and other design tokens at the `:root` level for easy theming.
- **Utility Classes:** Many classes mirror Tailwind’s approach (e.g., `.container`, `.btn-primary`, `.grid`, `.text-primary`), offering flexibility and consistency.
- **Component Styles:** Dedicated styles for cards, navigation, hero sections, product grids, forms, flash messages, etc.
- **AOS.js Classes:** `data-aos="fade-up"`, etc., used for scroll-triggered animations.
- **Responsive Breakpoints:** Generous use of media queries for mobile/tablet/desktop layouts.
- **Custom Animations:** Keyframes for transitions, hover effects on cards/buttons, etc.
- **Accessibility:** Font sizes, colors, and contrast ratios considered for readability and usability.

### 6.2 Responsive Design and Accessibility

- **Mobile-First:** All layouts degrade gracefully, with stacked columns, collapsible navigation, and optimized images for mobile.
- **ARIA Attributes:** Applied where necessary for accessibility (navigation, interactive forms).
- **Keyboard Navigation:** Menus and forms are accessible via keyboard shortcuts/tab order.
- **Semantic HTML:** `<header>`, `<footer>`, `<section>`, `<nav>`, and proper heading hierarchy.
- **High Contrast:** Text and button colors chosen for best readability.

### 6.3 JavaScript: Interactivity, AOS.js, Particles.js

- **AOS.js:** For fade-up/fade-in animations on scroll. Initialized on DOMContentLoaded.
- **Particles.js:** Optional animated particles background on hero section, loaded from `particles.json`.
- **AJAX:** Cart add-to-cart, newsletter subscription, and potentially quiz submission all use AJAX to prevent full page reloads and provide instant feedback.
- **Mobile Menu:** JS logic toggles mobile navigation, disables scroll when open, and handles closing via click-out or escape key.
- **Flash Messages:** Created dynamically in JS after actions (cart add, newsletter subscribe), auto-dismiss after timeout.
- **Form Validation:** Both client-side (HTML5 + JS) and server-side (PHP) validation for all forms.

---

## 7. Key Pages & Components

### 7.1 Home/Landing Page (views/home.php)

- **Hero Section:** Fullscreen video background with particles and gradient overlay. Large, bold tagline and subtitle. Prominent CTA buttons (“Find Your Perfect Scent”, “Shop Collection”).
- **Featured Products:** Grid of highlighted products (dynamically populated). Each card includes image, badge (“New”, “Best Seller”), category, price, and buttons for “View Details” and “Add to Cart”.
- **About/Story:** Brief brand narrative, image, and “Learn More” link.
- **Benefits:** Three-column section highlighting key selling points (natural ingredients, wellness focus, expert crafted), each with icon and description.
- **Quiz/Finder:** Section with mood cards (Relaxation, Energy, Focus, Sleep, Balance), quiz CTA, and animated transitions.
- **Newsletter:** AJAX-enabled signup form with CSRF protection and instant feedback.
- **Testimonials:** Customer reviews, star ratings, and attributions, presented in a visually appealing card layout.
- **Footer:** See below.

### 7.2 Header and Navigation (views/layout/header.php)

- **Logo:** Brand name and subtitle, styled for both desktop and mobile.
- **Navigation Links:** Shop, Find Your Scent, About, Contact.
- **Account/Cart:** Login/account links (conditional on session), cart icon with item count badge.
- **Mobile Menu:** Hamburger icon toggles off-canvas mobile nav. Menu can be closed by clicking outside or pressing escape.
- **Flash Messages:** Any server-set flash messages are displayed at the top of the page, styled and auto-dismissing.

### 7.3 Footer and Newsletter (views/layout/footer.php)

- **Quick Links:** Navigation to shop, quiz, about, contact.
- **Customer Service:** Links to shipping, returns, FAQ, privacy.
- **Newsletter:** Signup form (AJAX), CSRF-protected, with confirmation message on success.
- **Social Links:** Facebook, Instagram, Pinterest icons (FontAwesome), open in new tabs.
- **Footer Bottom:** Copyright.
- **JS Initialization:** AOS, Particles.js, and AJAX logic for newsletter and cart.

### 7.4 Product Grid & Cards

- **Layout:** Responsive grid (flex/grid), auto-filling columns based on screen size.
- **Product Card:** Image, badge, title, category, price, and action buttons.
- **Hover Effects:** Cards lift/shadow, image zoom on hover.
- **Stock Status:** “Add to Cart” enabled/disabled based on inventory; “Out of Stock” disables button.

### 7.5 Quiz Flow & Personalization

- **Quiz Section:** Mood-based cards lead to multi-step quiz (via quiz page/controller).
- **Result Mapping:** User selections mapped to recommended products via hardcoded PHP mapping (or DB in future).
- **Personalization:** Recommendations shown and can be saved/emailed; quiz results stored in DB for logged-in users.

---

## 8. Backend Logic & Core PHP Components

### 8.1 Includes: Shared Logic (includes/)

- **auth.php:** Session lifetime, secure cookies, login/logout logic, session user helpers.
- **db.php:** PDO connection, error handling, config checks.
- **SecurityMiddleware.php:** CSRF token generation and checking, input validation, security headers (XSS, content-type, etc.).
- **ErrorHandler.php:** Centralized error logging, user-friendly error pages, log rotation.
- **EmailService.php:** SMTP config and sending logic for transactional emails.

### 8.2 Controllers: Business Logic Layer

- **ProductController.php:** Retrieves product data, renders home/product views, handles featured products.
- **CartController.php:** CRUD operations for cart, session or DB-backed depending on login state.
- **CheckoutController.php:** Manages checkout process, order creation, payment integration (Stripe), and post-order logic.
- **AccountController.php:** Handles registration, login, user account pages.
- **QuizController.php:** Renders quiz, processes answers, maps results, and generates recommendations.
- **TaxController.php, InventoryController.php, NewsletterController.php, PaymentController.php:** Specialized logic.

### 8.3 Database Abstraction (includes/db.php)

- **PDO Connection:** Secure, persistent connection with error handling and prepared statements.
- **Config Driven:** Loads DB credentials and options from config.php.
- **Default Fetch Mode:** `PDO::FETCH_ASSOC` for security and ease of use.

### 8.4 Security Middleware & Error Handling

- **CSRF Protection:** Token generated per session, included in all forms, validated on POST.
- **Input Validation:** All GET/POST params validated and sanitized (type, length, allowed values).
- **Security Headers:** Strict transport security, X-Frame-Options, X-XSS-Protection, Content-Security-Policy, etc.
- **Error Logging:** All exceptions and errors logged, friendly error shown to user.

### 8.5 Session, Auth, and User Flow

- **Session Hardening:** Session cookies are secure, HttpOnly, SameSite=Lax, regenerated on login.
- **User Roles:** `user` and `admin` roles, with admin-only page gating.
- **Authentication:** Passwords hashed with bcrypt, verified on login, login attempts rate-limited.
- **Logout:** Session destroyed fully on logout.

---

## 9. Database Design

### 9.1 Entity-Relationship Model

**Key Entities:**

- **users:** User accounts (auth, roles)
- **products:** Product catalog
- **categories:** Product categories
- **orders:** Order headers (date, user, total)
- **order_items:** Each product in an order
- **cart_items:** User/guest carts
- **quiz_results:** User quiz answers/results
- **newsletter_subscribers:** Email captures

**Relationships:**

- `users` (1) ←→ (∞) `orders`
- `orders` (1) ←→ (∞) `order_items`
- `products` (∞) ←→ (1) `categories`
- `users` (1) ←→ (∞) `quiz_results`

### 9.2 Main Tables & Sample Schemas

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(150) UNIQUE,
    password VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150),
    description TEXT,
    price DECIMAL(10,2),
    image VARCHAR(255),
    category_id INT,
    stock_quantity INT,
    display_badge VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(128),
    result TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
-- ...see /db/schema.sql for complete details
```

### 9.3 Data Flow Examples

- **User adds to cart:** New row in `cart_items` (linked to `user_id` or session).
- **Order placed:** Rows inserted into `orders` and `order_items`; cart cleared.
- **Quiz taken:** Result mapped and saved in `quiz_results`.
- **Newsletter signup:** Email inserted into `newsletter_subscribers`.

---

## 10. Security Considerations

### 10.1 Input Sanitization & Validation

- All user input (GET/POST) is validated for type, length, and allowed values.
- `SecurityMiddleware::validateInput()` is used throughout controllers and index.php.
- Output is escaped via `htmlspecialchars()` to prevent XSS.

### 10.2 Session Management

- Secure session cookies: `secure=true`, `httponly=true`, `samesite=Lax`.
- Session ID regenerated periodically and on login.
- Session lifetime limited (e.g., 1 hour), auto-logout on expiry.

### 10.3 CSRF Protection

- CSRF token generated per session.
- Embedded as hidden field in all forms.
- Verified on every POST request (server-side).
- CSRF tokens for AJAX requests sent in headers or as form fields.

### 10.4 Security Headers & Rate Limiting

- Configured in `config.php` and set by SecurityMiddleware.
- Example headers: `X-Frame-Options: DENY`, `Strict-Transport-Security`, `Content-Security-Policy`.
- Rate limiting on login/register/reset to prevent brute force (see config.php settings).

### 10.5 File Uploads & Permissions

- File uploads (if enabled) limited by type, size, and scanned for malware.
- Sensitive files (config.php, .env) set with restrictive permissions (e.g., 640).

### 10.6 Audit Logging & Error Handling

- All critical events (login, register, password reset, payment) logged to audit files.
- Application errors logged to error logs, rotated to prevent overflow.
- User-friendly error pages shown; details never leaked to end user.

---

## 11. Extensibility & Onboarding

### 11.1 Adding Features, Pages, or Controllers

- **New Page:** Add controller in `/controllers`, create view in `/views`, add route case in `public/index.php`.
- **New Controller:** Place PHP file in `/controllers`, ensure it loads via index.php.
- **New View:** Place template in `/views`, include via controller.
- **New Model:** (Planned) Add to `/models` for DB abstraction.

### 11.2 Adding Products, Categories, and Quiz Questions

- **Products/Categories:** Insert directly via DB (phpMyAdmin) or build `/admin` form.
- **Quiz Questions:** Extend mapping in `QuizController.php` or external mapping file.
- **Admin Extensibility:** Add new forms and logic to `/admin` and controllers.

### 11.3 Developer Onboarding Checklist

- Clone repo, install PHP 8+, MySQL, Composer (if used).
- Create `.env` (optional) and set config.php with DB credentials.
- Run `/db/schema.sql` to initialize DB.
- Set file permissions (config.php, uploads).
- Configure Apache vhost and ensure `.htaccess` rewriting works.
- Seed DB with sample data (products, categories, users).
- Test home page, cart, login, quiz, and newsletter flow end to end.

### 11.4 Testing & Debugging

- ErrorHandler logs all errors; check logs for troubleshooting.
- Use browser dev tools for AJAX/JS debugging.
- Validate forms and flows for both success and error cases.
- Security: periodically test for XSS, CSRF, and session vulnerabilities.

---

## 12. Future Enhancements & Recommendations

- **API Layer:** Add REST API endpoints for SPA/mobile clients.
- **Admin Dashboard:** Enhanced admin panel for product/order/user management.
- **Unit/Integration Testing:** PHPUnit for backend; Cypress or Jest for frontend JS.
- **Dockerization:** Containerize for easy deployment and onboarding.
- **Continuous Integration:** GitHub Actions or similar for test/deploy pipelines.
- **Performance:** Caching (OPcache, Redis), CDN for assets, image optimization.
- **Internationalization:** Multi-language support (i18n).
- **Accessibility Audit:** Further improve ARIA, keyboard nav, screen reader support.
- **User Profiles:** Allow users to save preferences, view order history, etc.
- **Advanced Analytics:** Integrate with analytics dashboard for sales/trends.

---

## 13. Appendices

### A. Key File Summaries

| File/Folder                      | Purpose |
|----------------------------------|---------|
| `public/index.php`               | Application entry, routing, controller dispatch |
| `public/css/style.css`           | All site CSS, including Tailwind and custom styles |
| `public/particles.json`          | Particle animation config for Particles.js |
| `public/.htaccess`               | URL rewriting for clean routing |
| `includes/auth.php`              | User login, logout, session helpers |
| `includes/db.php`                | PDO DB connection, error handling |
| `includes/SecurityMiddleware.php`| CSRF, input validation, security headers |
| `includes/ErrorHandler.php`      | Centralized error handling/logging |
| `controllers/ProductController.php` | Product/business logic for home/product pages |
| `controllers/CartController.php`     | Cart logic (add/remove) |
| `controllers/QuizController.php`     | Quiz logic, mapping, result handling |
| `views/home.php`                 | Main landing page HTML/PHP |
| `views/layout/header.php`        | Sitewide navigation/header |
| `views/layout/footer.php`        | Sitewide footer/newsletter/social |
| `config.php`                     | Environment/DB configuration |

### B. Glossary

- **MVC:** Model-View-Controller, a software pattern separating logic, UI, and data.
- **CSRF:** Cross-Site Request Forgery, a security exploit mitigated by tokens.
- **AOS.js:** Animate On Scroll – library to animate elements when they enter viewport.
- **Particles.js:** JS library for particle backgrounds.
- **PDO:** PHP Data Objects, a secure DB abstraction.
- **Session:** PHP feature to persist user data across requests.
- **Flash message:** One-time session message for feedback (success/error).

### C. Code Snippets and Patterns

#### Example: Secure Controller Routing

```php
try {
    $page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string');
    switch ($page) {
        case 'home':
            (new ProductController($pdo))->showHomePage();
            break;
        // ...other cases...
        default:
            http_response_code(404);
            require_once ROOT_PATH . '/views/404.php';
    }
} catch (Exception $e) {
    ErrorHandler::handle($e);
}
```

#### Example: CSRF-Protected Form

```php
<form method="POST" action="index.php?page=register">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <!-- form fields -->
</form>
```

#### Example: AJAX Add-to-Cart

```js
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', async function() {
        const productId = this.dataset.productId;
        await fetch('index.php?page=cart&action=add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?>'
            },
            body: `product_id=${productId}&quantity=1`
        });
        // update UI...
    });
});
```

---

**End of Technical Design Specification**

This document is intended as a living resource. For questions, improvements, or onboarding, start here and follow file references into the codebase for specifics. The Scent codebase is designed for clarity, extensibility, and security, making it a robust foundation for e-commerce and further innovation.  
https://github.com/copilot/share/4a1d4382-00c0-8823-9913-420164a269ec
