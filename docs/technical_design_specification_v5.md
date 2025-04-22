# The Scent – Technical Design Specification (v3.0)

**Document Version:** 3.0
**Date:** 2025-04-23

*(Note: This version reflects the project state incorporating fixes for UI consistency and CSRF token handling as detailed in `suggested_improvements_and_fixes.md` v1.1)*

---

## Table of Contents

1.  [Introduction](#introduction)
2.  [Project Philosophy & Goals](#project-philosophy--goals)
3.  [System Architecture Overview](#system-architecture-overview)
    *   3.1 [High-Level Workflow](#high-level-workflow)
    *   3.2 [Request-Response Life Cycle](#request-response-life-cycle)
4.  [Directory & File Structure](#directory--file-structure)
    *   4.1 [Folder Map](#folder-map)
    *   4.2 [Key Files Explained](#key-files-explained)
5.  [Routing and Application Flow](#routing-and-application-flow)
    *   5.1 [URL Routing via .htaccess](#url-routing-via-htaccess)
    *   5.2 [index.php: The Application Entry Point](#indexphp-the-application-entry-point)
    *   5.3 [Controller Dispatch & Action Flow](#controller-dispatch--action-flow)
    *   5.4 [Views: Templating and Rendering](#views-templating-and-rendering)
6.  [Frontend Architecture](#frontend-architecture)
    *   6.1 [CSS (css/style.css), Tailwind (CDN), and Other Libraries](#css-cssstylecss-tailwind-cdn-and-other-libraries)
    *   6.2 [Responsive Design and Accessibility](#responsive-design-and-accessibility)
    *   6.3 [JavaScript: Interactivity, Libraries, and Issues](#javascript-interactivity-libraries-and-issues)
7.  [Key Pages & Components](#key-pages--components)
    *   7.1 [Home/Landing Page (views/home.php)](#homelanding-page-viewshomephp)
    *   7.2 [Header and Navigation (views/layout/header.php)](#header-and-navigation-viewslayoutheaderphp)
    *   7.3 [Footer and Newsletter (views/layout/footer.php)](#footer-and-newsletter-viewslayoutfooterphp)
    *   7.4 [Product Grid & Cards](#product-grid--cards)
    *   7.5 [Shopping Cart (views/cart.php)](#shopping-cart-viewscartphp)
    *   7.6 [Product Detail Page (views/product_detail.php)](#product-detail-page-viewsproduct_detailphp)
    *   7.7 [Quiz Flow & Personalization](#quiz-flow--personalization)
8.  [Backend Logic & Core PHP Components](#backend-logic--core-php-components)
    *   8.1 [Includes: Shared Logic (includes/)](#includes-shared-logic-includes)
    *   8.2 [Controllers: Business Logic Layer (controllers/ & BaseController.php)](#controllers-business-logic-layer-controllers--basecontrollerphp)
    *   8.3 [Database Abstraction (includes/db.php & models/)](#database-abstraction-includesdbphp--models)
    *   8.4 [Security Middleware & Error Handling](#security-middleware--error-handling)
    *   8.5 [Session, Auth, and User Flow](#session-auth-and-user-flow)
9.  [Database Design](#database-design)
    *   9.1 [Entity-Relationship Model (Conceptual)](#entity-relationship-model-conceptual)
    *   9.2 [Core Tables (from schema.sql)](#core-tables-from-schemasql)
    *   9.3 [Schema Considerations & Recommendations](#schema-considerations--recommendations)
    *   9.4 [Data Flow Examples](#data-flow-examples)
10. [Security Considerations & Best Practices](#security-considerations--best-practices)
    *   10.1 [Input Sanitization & Validation](#input-sanitization--validation)
    *   10.2 [Session Management](#session-management)
    *   10.3 [CSRF Protection (Implemented)](#csrf-protection-implemented)
    *   10.4 [Security Headers & CSP Issue](#security-headers--csp-issue)
    *   10.5 [Rate Limiting (Mechanism Unclear)](#rate-limiting-mechanism-unclear)
    *   10.6 [File Uploads & Permissions](#file-uploads--permissions)
    *   10.7 [Audit Logging & Error Handling](#audit-logging--error-handling)
    *   10.8 [SQL Injection Prevention](#sql-injection-prevention)
11. [Extensibility & Onboarding](#extensibility--onboarding)
    *   11.1 [Adding Features, Pages, or Controllers](#adding-features-pages-or-controllers)
    *   11.2 [Adding Products, Categories, and Quiz Questions](#adding-products-categories-and-quiz-questions)
    *   11.3 [Developer Onboarding Checklist](#developer-onboarding-checklist)
    *   11.4 [Testing & Debugging Notes](#testing--debugging-notes)
12. [Future Enhancements & Recommendations](#future-enhancements--recommendations)
13. [Appendices](#appendices)
    *   A. [Key File Summaries](#a-key-file-summaries)
    *   B. [Glossary](#b-glossary)
    *   C. [Code Snippets and Patterns](#c-code-snippets-and-patterns)

---

## 1. Introduction

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document (v3.0) serves as the updated technical design specification, incorporating findings from code review and analysis, and reflecting the project state *after* implementing fixes for UI consistency and CSRF protection as of April 23, 2025. It aims to offer deep insight into the system’s current structure, logic, and flow, serving as a comprehensive onboarding and reference guide.

---

## 2. Project Philosophy & Goals

*   **Security First:** All data input and user interactions are validated and sanitized. Strong session management and CSRF protection mechanisms are implemented and applied consistently.
*   **Simplicity & Maintainability:** Clear, modular code structure. Direct `require_once` usage in `index.php` for core component loading provides transparency but lacks autoloading benefits.
*   **Extensibility:** Architecture allows adding new features, pages, controllers, or views, requiring manual includes but offering straightforward extension points.
*   **Performance:** Direct routing is potentially fast. Relies on PDO prepared statements. CDN usage for frontend libraries impacts external dependencies.
*   **Modern User Experience:** Responsive design, smooth animations (AOS.js, Particles.js), and AJAX interactions (cart updates/removal, newsletter) provide a seamless interface. UI consistency across product displays has been addressed.
*   **Transparency:** No magic – application flow and routing are explicit in `index.php`'s include and switch logic.
*   **Accessibility & SEO:** Semantic HTML used. `aria-label` observed. Basic accessibility practices followed, further audit recommended.

---

## 3. System Architecture Overview

### 3.1 High-Level Workflow

```
[Browser/Client]
   |
   | (HTTP request, e.g., /index.php?page=product&id=1 or /product/1 via rewrite)
   v
[Apache2 Web Server] <-- DocumentRoot points to project root (/cdrom/project/The-Scent-oa5)
   |
   | (URL rewriting via /.htaccess)
   v
[/index.php]  <-- MAIN ENTRY POINT
   |
   | (Defines ROOT_PATH, includes core files: config, db, auth, security, error handler)
   | (Initializes ErrorHandler, applies SecurityMiddleware settings)
   | (Determines $page, $action from $_GET, validates input)
   | (Validates CSRF token for POST requests - reads token from POST data)
   v
[Controller]  (e.g., ProductController, CartController)
   |           (Included via require_once *within* index.php's switch case)
   |           (Instantiated, passed $pdo connection)
   |           (Often extends BaseController)
   |           (Generates CSRF token using $this->getCsrfToken())
   |           (Passes token and other data to the View)
   |
   | (Executes action method: business logic, DB access via $this->db or Models)
   v
[Model / DB Layer] (e.g., models/Product.php, direct PDO in BaseController/includes/db.php)
   |
   | (Prepare/execute SQL queries, fetch data)
   v
[View / Response]
   |--> [View File] (e.g., views/home.php)
   |       (Included via require_once from index.php or controller)
   |       (Generates HTML using PHP variables, includes layout partials)
   |       (Outputs CSRF token into hidden input field #csrf-token-value)
   |       (Output MUST use htmlspecialchars())
   |
   |--> [JSON Response] (via BaseController::jsonResponse for AJAX)
   |       (e.g., for cart add/update/remove, newsletter)
   |
   |--> [Redirect] (via BaseController::redirect or header())
   |
   v
[Browser/Client] <-- Renders HTML, applies CSS (Tailwind CDN, custom)
                     Executes JS (libraries, custom handlers)
                     JS reads CSRF token from #csrf-token-value for AJAX POSTs
```

### 3.2 Request-Response Life Cycle

1.  **Request Initiation:** User accesses a URL.
2.  **.htaccess Rewrite:** Apache rewrites the request to `/index.php` if applicable.
3.  **Initialization (`/index.php`):** Core files loaded (`config`, `db`, `auth`, `SecurityMiddleware`, `ErrorHandler`), `$pdo` connection established, error handling set up, security settings applied (`SecurityMiddleware::apply`).
4.  **Routing (`/index.php`):** `$page`, `$action` extracted and validated.
5.  **CSRF Check (`/index.php`):** If `POST`, `SecurityMiddleware::validateCSRF()` compares `$_POST['csrf_token']` with `$_SESSION['csrf_token']`.
6.  **Controller/View Dispatch (`/index.php` `switch ($page)`):** Relevant controller loaded, instantiated. Action method called or view directly included.
7.  **Controller Action:** Executes logic, interacts with DB (Models/PDO), prepares data. **Crucially, calls `$this->getCsrfToken()` to generate/retrieve the token.**
8.  **View Rendering / Response Generation:**
    *   **HTML Page:** Controller passes data (including `$csrfToken`) to the view. View (`views/*.php`) generates HTML. Layout included. **The view outputs the `$csrfToken` into `<input type="hidden" id="csrf-token-value" ...>`**. Output is escaped.
    *   **JSON Response:** Controller uses `BaseController::jsonResponse()` for AJAX.
    *   **Redirect:** Controller uses `BaseController::redirect()`.
9.  **Response Transmission:** Server sends HTML/JSON/Redirect to browser.
10. **Client-Side Execution:** Browser renders HTML, applies CSS, executes JS. AJAX handlers in `footer.php` read the CSRF token from `#csrf-token-value` when making POST requests.

---

## 4. Directory & File Structure

### 4.1 Folder Map

(Verified against `PHP_e-commerce_project_file_structure.md` - structure is accurate)

```
/ (project root: /cdrom/project/The-Scent-oa5) <-- Apache DocumentRoot
|-- index.php              # Main entry script (routing, core includes, dispatch)
|-- config.php             # Environment, DB, security settings (SECURITY_SETTINGS array)
|-- css/
|   |-- style.css          # Custom CSS rules
|-- images/                # Public image assets (structure assumed, contains products/)
|-- videos/                # Public video assets (e.g., hero.mp4)
|-- particles.json         # Particles.js configuration
|-- includes/              # Shared PHP utility/core files
|   |-- auth.php           # Helpers: isLoggedIn(), isAdmin()
|   |-- db.php             # PDO connection setup (makes $pdo available)
|   |-- SecurityMiddleware.php # Security helpers (validation, CSRF gen/validation)
|   |-- ErrorHandler.php   # Error/exception handling setup
|   |-- EmailService.php   # Email sending logic
|-- controllers/           # Business logic / request handlers
|   |-- BaseController.php # Abstract base with shared helpers (DB, JSON, redirect, validation, CSRF, auth checks, logging, etc.)
|   |-- ProductController.php
|   |-- CartController.php
|   |-- CheckoutController.php
|   |-- AccountController.php
|   |-- QuizController.php
|   |-- ... (Coupon, Inventory, Newsletter, Payment, Tax)
|-- models/                # Data representation / DB interaction
|   |-- Product.php
|   |-- User.php
|   |-- Order.php
|   |-- Quiz.php
|-- views/                 # HTML templates (PHP files)
|   |-- home.php
|   |-- products.php         # (UI updated for consistency)
|   |-- product_detail.php   # (Enhanced version implemented)
|   |-- cart.php
|   |-- checkout.php
|   |-- register.php, login.php, forgot_password.php, reset_password.php
|   |-- quiz.php, quiz_results.php
|   |-- error.php, 404.php
|   |-- layout/
|   |   |-- header.php     # Sitewide header, nav, assets, session data display
|   |   |-- footer.php     # Sitewide footer, JS init, AJAX handlers
|   |   |-- admin_header.php, admin_footer.php
|-- .htaccess              # Apache URL rewrite rules & config
|-- .env                   # (Not present, recommended for secrets)
|-- README.md              # Project documentation
|-- technical_design_specification.md # (This document v3)
|-- ... (other docs, schema file)
```

### 4.2 Key Files Explained

*   **index.php**: Central orchestrator. Includes core components, performs routing, validates basic input and CSRF (POST), includes/instantiates controllers, and dispatches to controller actions or includes views.
*   **config.php**: Defines DB constants, app settings, API keys, email config, `SECURITY_SETTINGS` array.
*   **includes/SecurityMiddleware.php**: Provides static methods: `apply()` (sets headers, session params), `validateInput()`, `validateCSRF()` (compares POST/Session token), `generateCSRFToken()` (creates/retrieves session token).
*   **controllers/BaseController.php**: Abstract base providing shared functionality: `$db` (PDO), response helpers (`jsonResponse`, `redirect`), view rendering, validation helpers, authentication checks, logging, **`getCsrfToken()`** (calls `SecurityMiddleware::generateCSRFToken`).
*   **controllers/*Controller.php**: Handle module-specific logic, extend `BaseController`. Fetch data (Models/PDO), prepare data for views, **call `$this->getCsrfToken()` before rendering**, handle AJAX.
*   **models/*.php**: Encapsulate entity-specific DB logic (e.g., `Product.php`).
*   **views/layout/header.php**: Common header, includes assets, displays dynamic session info.
*   **views/layout/footer.php**: Common footer, JS initializations (AOS, Particles), **global AJAX handlers** for `.add-to-cart` and newsletter (now correctly reading CSRF token from hidden input), `showFlashMessage` JS helper.
*   **views/*.php**: Specific page templates. **Output CSRF token via `htmlspecialchars($csrfToken)` into `<input type="hidden" id="csrf-token-value" ...>`** when needed for forms/AJAX.
*   **views/products.php**: Product listing page view, **now uses consistent Tailwind card styling**.
*   **views/product_detail.php**: **Enhanced version** with improved layout, gallery, tabs, AJAX cart functionality.

---

## 5. Routing and Application Flow

### 5.1 URL Routing via .htaccess

*   Mechanism: Apache `mod_rewrite` routes most non-file/directory requests to `/index.php`.

### 5.2 index.php: The Application Entry Point

*   Role: Front Controller/Router.
*   Process: Initializes core components, extracts/validates `$page`/`$action`, performs CSRF check on POST requests (using `SecurityMiddleware::validateCSRF`), dispatches to controllers via `switch($page)`.

### 5.3 Controller Dispatch & Action Flow

*   Controllers included/instantiated by `index.php`. Extend `BaseController`.
*   Execute business logic, use Models/PDO for data.
*   **Crucially, call `$csrfToken = $this->getCsrfToken();` before rendering views requiring CSRF protection.**
*   Pass data (including `$csrfToken`) to views.
*   Terminate via view inclusion, `jsonResponse`, or `redirect`.

### 5.4 Views: Templating and Rendering

*   PHP files in `views/` mixing HTML and PHP.
*   Include layout partials (`header.php`, `footer.php`).
*   **Output CSRF token using `htmlspecialchars($csrfToken)` into `<input type="hidden" id="csrf-token-value" ...>` where needed.**
*   Use `htmlspecialchars()` for all dynamic output.
*   Styling via `/css/style.css` and Tailwind CDN.
*   JS initialized/handled primarily in `footer.php`.

---

## 6. Frontend Architecture

### 6.1 CSS (css/style.css), Tailwind (CDN), and Other Libraries

*   Hybrid styling: Tailwind CDN utilities + custom CSS in `/css/style.css`.
*   Libraries via CDN: Google Fonts, Font Awesome 6, AOS.js, Particles.js.

### 6.2 Responsive Design and Accessibility

*   Responsive via Tailwind breakpoints. Mobile menu functional.
*   Basic accessibility practices followed. Further audit recommended.

### 6.3 JavaScript: Interactivity, Libraries, and Issues

*   **Library Initialization:** AOS, Particles initialized in `footer.php`.
*   **Custom Handlers (Primarily `footer.php`):**
    *   **Add-to-Cart:** Global handler for `.add-to-cart`. **Reads CSRF token from `#csrf-token-value`**, sends via AJAX POST to `cart/add`, handles JSON response/errors, uses `showFlashMessage`.
    *   **Newsletter:** Handlers for `#newsletter-form` / `#newsletter-form-footer`. **Reads CSRF token from `#csrf-token-value`**, sends via AJAX POST, uses `showFlashMessage`. *(Ensure token input is available near footer form too)*.
*   **Page-Specific JS:** Mobile menu (`header.php`), Cart page AJAX (`cart.php`), Product Detail AJAX/UI (`product_detail.php`). These also rely on the presence of `#csrf-token-value` for POST actions.
*   **Standardization:** `showFlashMessage` helper used for feedback. JS redundancy minimized by using global handlers in `footer.php`.

---

## 7. Key Pages & Components

### 7.1 Home/Landing Page (views/home.php)

*   Displays standard sections. Product cards use consistent styling. `.add-to-cart` buttons functional via global handler (relies on `#csrf-token-value` being outputted).

### 7.2 Header and Navigation (views/layout/header.php)

*   Standard header. Displays dynamic session info. Includes assets.

### 7.3 Footer and Newsletter (views/layout/footer.php)

*   Standard footer. Contains JS init and global AJAX handlers (Add-to-Cart, Newsletter) which **now correctly read and send the CSRF token**. Defines `showFlashMessage`. *(Requires `#csrf-token-value` input on pages where footer newsletter is used)*.

### 7.4 Product Grid & Cards

*   **Consistent styling applied** using Tailwind classes across `home.php`, `products.php`, and `product_detail.php` (related products). Includes stock status display and functional Add-to-Cart buttons.

### 7.5 Shopping Cart (views/cart.php)

*   Displays items. JS handles quantity updates, AJAX removal, and AJAX cart updates via form submission. **Relies on `#csrf-token-value` being outputted on the page.**

### 7.6 Product Detail Page (views/product_detail.php)

*   **Displays using the enhanced, modern layout.** Includes gallery, tabs, detailed info, related products. AJAX Add-to-Cart for main and related products is functional (relies on `#csrf-token-value`). **Requires necessary fields in `products` table (see 9.3).**

### 7.7 Quiz Flow & Personalization

*   Standard quiz flow via `QuizController` and views.

---

## 8. Backend Logic & Core PHP Components

### 8.1 Includes: Shared Logic (includes/)

*   Foundational files: `auth.php`, `db.php`, `SecurityMiddleware.php`, `ErrorHandler.php`, `EmailService.php`.

### 8.2 Controllers: Business Logic Layer (controllers/ & BaseController.php)

*   `BaseController.php`: Provides shared methods including `$db`, response helpers, validation, auth checks, **`getCsrfToken()`**.
*   Specific Controllers: Extend `BaseController`. Handle module logic. **Must call `$this->getCsrfToken()` and pass it to views requiring CSRF protection.**

### 8.3 Database Abstraction (includes/db.php & models/)

*   Connection via `includes/db.php` (`$pdo`).
*   Interaction via Models (`models/*.php`) using prepared statements or direct PDO usage in controllers.

### 8.4 Security Middleware & Error Handling

*   `SecurityMiddleware.php`: Static methods for applying security settings, input validation, **CSRF generation (`generateCSRFToken`) and validation (`validateCSRF`)**.
*   `ErrorHandler.php`: Sets up error/exception handling.

### 8.5 Session, Auth, and User Flow

*   Session started securely (`SecurityMiddleware::apply`). Includes standard data: `user_id`, `user_role`, `cart`, `cart_count`, **`csrf_token`**, `flash`, regeneration timestamp.
*   Auth flow via `AccountController`, helpers in `auth.php`, enforcement via `BaseController`.

---

## 9. Database Design

### 9.1 Entity-Relationship Model (Conceptual)

Standard e-commerce relationships remain valid.

### 9.2 Core Tables (from schema.sql)

Core tables (`users`, `products`, `categories`, `orders`, `order_items`, `quiz_results`, etc.) as defined in `the_scent_schema.sql.txt`.

### 9.3 Schema Considerations & Recommendations

*   **Product Detail Fields:** The `products` table **must contain** the fields required by the enhanced `views/product_detail.php`: `short_description`, `benefits`, `ingredients`, `usage_instructions`, `gallery_images`, `size`, `scent_profile`, `origin`, `sku`, `backorder_allowed`. If these are missing, `ALTER TABLE` statements are necessary.
*   **Redundant Stock Column:** `products.stock` is redundant if `products.stock_quantity` is used. Recommend removing `stock`.
*   **Cart Table Usage:** `CartController` uses `$_SESSION['cart']`. The `cart_items` table appears unused by current cart logic. Decide whether to implement DB cart persistence or remove the table.

### 9.4 Data Flow Examples

*   **Add to Cart:** (AJAX) JS reads product ID and CSRF token (from `#csrf-token-value`) -> POST to `cart/add` -> `CartController::addToCart()` validates CSRF -> Updates session -> Returns JSON.
*   **Place Order:** Form POST to `checkout/process` -> `CheckoutController::processCheckout()` validates CSRF -> Creates DB records -> Updates stock -> Clears session cart -> Redirects.
*   **View Product List:** Request to `products` -> `ProductController::showProductList()` fetches products, gets CSRF token -> Includes `views/products.php`, passing data and token -> View renders cards and outputs hidden CSRF token.

---

## 10. Security Considerations & Best Practices

### 10.1 Input Sanitization & Validation

*   Implemented via `SecurityMiddleware::validateInput()` and `BaseController` helpers. PDO prepared statements prevent SQLi. Output escaping via `htmlspecialchars()` is standard.

### 10.2 Session Management

*   Secure session settings applied (`HttpOnly`, `SameSite`, periodic regeneration). Session integrity checks in place.

### 10.3 CSRF Protection (Implemented)

*   **Mechanism:** Synchronizer Token Pattern implemented.
    *   Token generated/retrieved via `SecurityMiddleware::generateCSRFToken` (called by `BaseController::getCsrfToken`). Stored in `$_SESSION['csrf_token']`.
    *   Controllers pass the token to necessary Views.
    *   Views output the token into `<input type="hidden" id="csrf-token-value" ...>`.
    *   Client-side JS reads token from this input for AJAX POST requests.
    *   Server-side validation via `SecurityMiddleware::validateCSRF` compares `$_POST['csrf_token']` with `$_SESSION['csrf_token']` using `hash_equals` for POST requests.
*   **Status:** The mechanism is sound. **Consistent application** (passing token to all relevant views, outputting the hidden field, using it in JS) is crucial and assumed implemented per the fixes.

### 10.4 Security Headers & CSP Issue

*   Standard security headers (`X-Frame-Options`, etc.) are applied via `SecurityMiddleware::apply()`.
*   **CSP:** The `Content-Security-Policy` header is currently commented out in `SecurityMiddleware.php`. **Recommendation:** Re-enable and configure properly, aiming to remove `'unsafe-inline'` for enhanced XSS protection.

### 10.5 Rate Limiting (Mechanism Unclear)

*   Configuration exists (`config.php`), but `BaseController` contains multiple, potentially conflicting helper methods (`isRateLimited`, `checkRateLimit`, `validateRateLimit`). **Recommendation:** Standardize on one implementation (e.g., APCu or Redis/Memcached) and apply consistently to sensitive endpoints.

### 10.6 File Uploads & Permissions

*   Validation logic exists (`SecurityMiddleware::validateFileUpload`). Secure storage practices and strict file permissions are essential but implementation details aren't fully specified.

### 10.7 Audit Logging & Error Handling

*   Mechanisms exist (`logAuditTrail`, `logSecurityEvent`, `ErrorHandler`). Consistent application is key. Error display should be disabled in production.

### 10.8 SQL Injection Prevention

*   The primary defense is the consistent use of **PDO Prepared Statements** in Models and database interactions, which is observed.
*   The `preventSQLInjection` function in `SecurityMiddleware.php` using `preg_replace`/`addslashes` is generally **not recommended** and potentially harmful; reliance should be solely on prepared statements. **Recommendation:** Remove or disable the `preventSQLInjection` function.

---

## 11. Extensibility & Onboarding

### 11.1 Adding Features, Pages, or Controllers

*   Follow the pattern: Create Controller (extending `BaseController`), create View(s), add routing case in `index.php`. Remember to handle CSRF token generation/passing if POST actions are involved.

### 11.2 Adding Products, Categories, and Quiz Questions

*   Requires DB manipulation or an Admin UI. Ensure new products have all necessary fields for display. Quiz updates require modifying `QuizController` or database.

### 11.3 Developer Onboarding Checklist

1.  Setup LAMP/LEMP stack, enable `mod_rewrite`.
2.  Clone repo.
3.  Setup DB (`the_scent`, `scent_user`), import schema.
4.  Update `config.php` (or use `.env` if implemented).
5.  Set file permissions (restrict `config.php`, ensure web server write access only where needed).
6.  Configure Apache VirtualHost (`DocumentRoot` to project root, `AllowOverride All`).
7.  Browse site, check logs.
8.  **Verify CSRF implementation:** Check token output in views and transmission in AJAX POSTs using browser dev tools.

### 11.4 Testing & Debugging Notes

*   Use browser dev tools (Network, Console). Check server logs.
*   Use `error_log()`/`var_dump()`. Consider Xdebug.
*   **Key Areas to Verify:** CSRF token flow, AJAX request/responses, session state, data consistency between DB and views, UI responsiveness.

---

## 12. Future Enhancements & Recommendations

*   **Autoloader:** Implement PSR-4 autoloading (via Composer).
*   **Dependency Management:** Use Composer.
*   **Routing:** Replace `index.php` switch with a dedicated Router class.
*   **Templating Engine:** Use Twig or BladeOne.
*   **Environment Config:** Use `.env` files.
*   **Database Migrations:** Use Phinx or similar.
*   **Testing:** Implement PHPUnit tests.
*   **API:** Develop a RESTful API.
*   **Admin Panel:** Build a comprehensive admin interface.
*   **Standardize Rate Limiting:** Choose and consistently implement one mechanism.
*   **Refactor BaseController:** Move some logic (e.g., rate limiting) to dedicated services.
*   **Review/Remove `preventSQLInjection`:** Rely on prepared statements.
*   **Implement & Configure CSP:** Re-enable the Content-Security-Policy header.

---

## 13. Appendices

### A. Key File Summaries

| File/Folder                 | Purpose                                                                                             |
| :-------------------------- | :-------------------------------------------------------------------------------------------------- |
| `index.php`                 | Entry point, routing, core includes, CSRF POST validation, controller/view dispatch                 |
| `config.php`                | DB credentials, App/Security settings, API keys, Email config                                       |
| `includes/SecurityMiddleware.php` | Static helpers: `apply()`, `validateInput()`, `validateCSRF()`, `generateCSRFToken()`                 |
| `controllers/BaseController.php` | Abstract base: `$db`, helpers (JSON, redirect, render), validation, auth checks, `getCsrfToken()` |
| `controllers/*Controller.php` | Module logic, extend BaseController, **pass CSRF token to views**                                   |
| `models/*.php`              | Entity DB logic (Prepared Statements)                                                               |
| `views/*.php`               | HTML/PHP templates, **output CSRF token into `#csrf-token-value` hidden input**                       |
| `views/layout/footer.php`   | Footer, JS init, **global AJAX handlers reading CSRF token from `#csrf-token-value`**                 |

### B. Glossary

(Standard terms: MVC, CSRF, XSS, SQLi, PDO, AJAX, CDN, etc.)

### C. Code Snippets and Patterns

#### Correct CSRF Token Implementation Pattern (Summary)

1.  **Controller:** `$csrfToken = $this->getCsrfToken();` -> Pass `$csrfToken` to view data.
2.  **View:** `<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">`
3.  **JavaScript (AJAX POST):** `const csrfToken = document.getElementById('csrf-token-value')?.value;` -> Include `csrf_token=${encodeURIComponent(csrfToken)}` in request body.
4.  **Server (POST):** `SecurityMiddleware::validateCSRF();` (called early in `index.php` or controller).

https://drive.google.com/file/d/10fMrHUpv-e6_GHSyhkAFgrDAZlKI9Xuk/view?usp=sharing, https://drive.google.com/file/d/18vABpLEymmkSblUTQLGJ73ieUIiw2V98/view?usp=sharing, https://drive.google.com/file/d/1Nfkog4SAab82YbmLXVegBGVSmtmnyi-X/view?usp=sharing, https://drive.google.com/file/d/1RFoWLN1aU1YGGbsbz1yd1LM9Ng5Wh84a/view?usp=sharing, https://drive.google.com/file/d/1U1oSGdxX1tqwnhBToYF4g4mCs4BJlI9I/view?usp=sharing, https://drive.google.com/file/d/1b1YZO-1OsJ3cpT7B25feVus8foot4ERF/view?usp=sharing, https://drive.google.com/file/d/1ewwpRYfRB7G3-dTj_n70bL1w1arXnIwY/view?usp=sharing, https://drive.google.com/file/d/1gzj_IipNQdswfIijgHodS5XbxSUHnZrF/view?usp=sharing, https://drive.google.com/file/d/1iwmgE_CD8b7P-4Qi9PGQXFGqNw-rle6K/view?usp=sharing, https://drive.google.com/file/d/1kGTcMsRTECgvlOrnFpJ1C352iq_3dJe9/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221kduXBK9pkvGuzoLyV8O7ONsINSk-N9o0%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1sWR-zjpQ7-WGTqxE34zrV9I7jfC4TfwQ/view?usp=sharing
