# The Scent – Technical Design Specification (v5.0)

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
    *   7.7 [Products Page (views/products.php)](#products-page-viewsproductsphp)
    *   7.8 [Quiz Flow & Personalization](#quiz-flow--personalization)
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
    *   10.5 [Rate Limiting (Implementation Inconsistent)](#rate-limiting-implementation-inconsistent)
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

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document (**v5.0**) serves as the updated technical design specification, reflecting the project's current state, architecture, and areas requiring attention or improvement (such as CSRF token consistency and rate-limiting standardization). **This version incorporates fixes for previous issues related to incorrect navigation links and AJAX request validation.** It aims to offer deep insight into the system’s structure, logic, and flow, serving as a comprehensive onboarding and reference guide.

---

## 2. Project Philosophy & Goals

*   **Security First:** All data input and user interactions are validated and sanitized. Strong session management and CSRF protection mechanisms are implemented, although **consistent application of CSRF token provisioning to views requires ongoing diligence**.
*   **Simplicity & Maintainability:** Clear, modular code structure. Direct `require_once` usage in `index.php` for core component loading provides transparency but lacks autoloading benefits.
*   **Extensibility:** Architecture allows adding new features, pages, controllers, or views, requiring manual includes but offering straightforward extension points.
*   **Performance:** Direct routing is potentially fast. Relies on PDO prepared statements. CDN usage for frontend libraries impacts external dependencies.
*   **Modern User Experience:** Responsive design, smooth animations (AOS.js, Particles.js), and AJAX interactions (cart updates/removal, newsletter) provide a seamless interface. UI consistency across product displays has been addressed. **AJAX functionality has been restored.**
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
   |           (*** MUST call $this->getCsrfToken() before rendering views needing CSRF ***)
   |           (*** MUST pass the generated token to the View data ***)
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
   |       (*** MUST output CSRF token into hidden input <input id="csrf-token-value"> if needed ***)
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
                     (*** JS reads CSRF token from #csrf-token-value for AJAX POSTs ***)
```

### 3.2 Request-Response Life Cycle

1.  **Request Initiation:** User accesses a URL.
2.  **.htaccess Rewrite:** Apache rewrites the request to `/index.php` if applicable.
3.  **Initialization (`/index.php`):** Core files loaded (`config`, `db`, `auth`, `SecurityMiddleware`, `ErrorHandler`), `$pdo` connection established, error handling set up, security settings applied (`SecurityMiddleware::apply`).
4.  **Routing (`/index.php`):** `$page`, `$action` extracted and validated.
5.  **CSRF Check (`/index.php`):** If `POST`, `SecurityMiddleware::validateCSRF()` compares `$_POST['csrf_token']` with `$_SESSION['csrf_token']`. Crucial for security.
6.  **Controller/View Dispatch (`/index.php` `switch ($page)`):** Relevant controller loaded, instantiated. Action method called or view directly included.
7.  **Controller Action:** Executes logic, interacts with DB (Models/PDO), prepares data. **If rendering a view that requires subsequent CSRF-protected actions (forms/AJAX), the controller MUST call `$csrfToken = $this->getCsrfToken()` to generate/retrieve the token.**
8.  **View Rendering / Response Generation:**
    *   **HTML Page:** Controller passes data (including `$csrfToken` if fetched) to the view. View (`views/*.php`) generates HTML, includes layout partials. **If the view needs to support CSRF-protected forms or AJAX initiated from it, it MUST output the `$csrfToken` into `<input type="hidden" id="csrf-token-value" ...>`**. All dynamic output must be escaped using `htmlspecialchars()`.
    *   **JSON Response:** Controller uses `BaseController::jsonResponse()` for AJAX actions (e.g., Cart operations). Note: Unnecessary `validateAjax()` checks have been removed from relevant controllers.
    *   **Redirect:** Controller uses `BaseController::redirect()`.
9.  **Response Transmission:** Server sends HTML/JSON/Redirect to browser.
10. **Client-Side Execution:** Browser renders HTML, applies CSS, executes JS. **AJAX handlers (e.g., in `footer.php`) read the CSRF token from the `#csrf-token-value` hidden input when making POST requests.**

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
|   |-- CartController.php # Note: validateAjax() removed from AJAX methods
|   |-- CheckoutController.php # Note: validateAjax() removed from AJAX methods if applicable
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
|   |-- products.php         # Displays product listing/filters/pagination
|   |-- product_detail.php   # Enhanced version implemented
|   |-- cart.php
|   |-- checkout.php
|   |-- register.php, login.php, forgot_password.php, reset_password.php
|   |-- quiz.php, quiz_results.php
|   |-- error.php, 404.php
|   |-- layout/
|   |   |-- header.php     # Sitewide header, nav (Shop link corrected), assets
|   |   |-- footer.php     # Sitewide footer, JS init, AJAX handlers
|   |   |-- admin_header.php, admin_footer.php
|-- .htaccess              # Apache URL rewrite rules & config
|-- .env                   # (Not present, recommended for secrets)
|-- README.md              # Project documentation
|-- technical_design_specification.md # (This document v5)
|-- ... (other docs, schema file)
```

### 4.2 Key Files Explained

*   **index.php**: Central orchestrator. Includes core components, performs routing, validates basic input and CSRF (POST), includes/instantiates controllers, and dispatches to controller actions or includes views.
*   **config.php**: Defines DB constants, app settings, API keys, email config, `SECURITY_SETTINGS` array.
*   **includes/SecurityMiddleware.php**: Provides static methods: `apply()` (sets headers, session params), `validateInput()`, `validateCSRF()` (compares POST/Session token), `generateCSRFToken()` (creates/retrieves session token).
*   **controllers/BaseController.php**: Abstract base providing shared functionality: `$db` (PDO), response helpers (`jsonResponse`, `redirect`), view rendering (`renderView`), validation helpers, authentication checks, logging, **`getCsrfToken()`** (calls `SecurityMiddleware::generateCSRFToken`). **`validateAjax()` method exists but is no longer called by AJAX endpoints like CartController actions.**
*   **controllers/*Controller.php**: Handle module-specific logic, extend `BaseController`. Fetch data (Models/PDO), prepare data for views, **MUST call `$this->getCsrfToken()` and pass token to views requiring CSRF protection.** Handle AJAX responses.
*   **models/*.php**: Encapsulate entity-specific DB logic (e.g., `Product.php`) using prepared statements.
*   **views/layout/header.php**: Common header, includes assets, displays dynamic session info. **Contains corrected "Shop" navigation link (`index.php?page=products`).**
*   **views/layout/footer.php**: Common footer, JS initializations (AOS, Particles), **global AJAX handlers** for `.add-to-cart` and newsletter (relies on reading CSRF token from hidden input), `showFlashMessage` JS helper.
*   **views/*.php**: Specific page templates. **MUST output CSRF token via `htmlspecialchars($csrfToken)` into `<input type="hidden" id="csrf-token-value" ...>`** when needed for subsequent forms/AJAX. Use `htmlspecialchars()` for all dynamic output.
*   **views/products.php**: Product listing page view. Displays product grid, search, filters, sorting, and pagination. **Loads all products by default.**
*   **views/product_detail.php**: Enhanced version with improved layout, gallery, tabs, AJAX cart functionality.

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
*   **Crucially, controllers rendering views that need CSRF protection for subsequent actions MUST call `$csrfToken = $this->getCsrfToken();` and pass this token to the view.**
*   Pass data (including `$csrfToken`) to views using `require_once` scope or the `$data` array with `renderView`.
*   Terminate via view inclusion, `jsonResponse`, or `redirect`. AJAX actions no longer perform the `validateAjax()` check.

### 5.4 Views: Templating and Rendering

*   PHP files in `views/` mixing HTML and PHP.
*   Include layout partials (`header.php`, `footer.php`).
*   **Required Pattern:** Where subsequent forms or AJAX POSTs are initiated, the view MUST output the passed `$csrfToken` into a hidden input: `<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">`.
*   Use `htmlspecialchars()` for all dynamic user-generated or database-sourced output.
*   Styling via `/css/style.css` and Tailwind CDN.
*   JS initialized/handled primarily in `footer.php`, relying on the presence of `#csrf-token-value`.

---

## 6. Frontend Architecture

### 6.1 CSS (css/style.css), Tailwind (CDN), and Other Libraries

*   Hybrid styling: Tailwind CDN utilities + custom CSS in `/css/style.css`.
*   Libraries via CDN: Google Fonts, Font Awesome 6, AOS.js, Particles.js.

### 6.2 Responsive Design and Accessibility

*   Responsive via Tailwind breakpoints. Mobile menu functional.
*   Basic accessibility practices followed (semantic HTML, `aria-label`). Further audit recommended.

### 6.3 JavaScript: Interactivity, Libraries, and Issues

*   **Library Initialization:** AOS, Particles initialized in `footer.php`.
*   **Custom Handlers (Primarily `footer.php`):**
    *   **Add-to-Cart:** Global handler for `.add-to-cart`. **Relies on reading CSRF token from `#csrf-token-value`**, sends via AJAX POST to `cart/add`, handles JSON response/errors, uses `showFlashMessage`. **Now functional.**
    *   **Newsletter:** Handlers for `#newsletter-form` / `#newsletter-form-footer`. **Relies on reading CSRF token from `#csrf-token-value`**, sends via AJAX POST, uses `showFlashMessage`. **Assumed functional (pending `validateAjax` check removal if present).**
*   **Page-Specific JS:** Mobile menu (`header.php`), Cart page AJAX (`cart.php`), Product Detail AJAX/UI (`product_detail.php`). These also rely on the presence of `#csrf-token-value` for POST actions. **Product Detail Add-to-Cart now functional.** Cart page AJAX assumed functional.
*   **Standardization:** `showFlashMessage` helper used for feedback. JS redundancy minimized by using global handlers in `footer.php`. **Key Dependency:** Functionality of global AJAX handlers is critically dependent on the `#csrf-token-value` input being present and correctly populated on the page.

---

## 7. Key Pages & Components

### 7.1 Home/Landing Page (views/home.php)

*   Displays standard sections. Product cards use consistent styling. `.add-to-cart` buttons **functional** via global handler (provided CSRF token input is outputted).

### 7.2 Header and Navigation (views/layout/header.php)

*   Standard header. Displays dynamic session info. Includes assets. Mobile menu functional. **Shop link corrected to point to `index.php?page=products`.**

### 7.3 Footer and Newsletter (views/layout/footer.php)

*   Standard footer. Contains JS init and global AJAX handlers (Add-to-Cart, Newsletter) which **rely on reading the CSRF token from the standard hidden input**. Defines `showFlashMessage`.

### 7.4 Product Grid & Cards

*   Consistent styling applied using Tailwind classes across `home.php`, `products.php`, and `product_detail.php` (related products). Includes stock status display and functional Add-to-Cart buttons (dependent on CSRF token availability).

### 7.5 Shopping Cart (views/cart.php)

*   Displays items. JS handles quantity updates, AJAX removal, and AJAX cart updates via form submission. **Relies on `#csrf-token-value` being outputted on the page for its AJAX actions.** Assumed functional after `validateAjax` removal.

### 7.6 Product Detail Page (views/product_detail.php)

*   Displays using the enhanced, modern layout. Includes gallery, tabs, detailed info, related products. AJAX Add-to-Cart for main and related products is **functional** (provided CSRF token input is outputted). Requires necessary fields in `products` table (see 9.3).

### 7.7 Products Page (views/products.php)

*   Displays product listing with filters, sorting, and pagination. **Loads all products by default** when accessed via the corrected navigation link (`index.php?page=products`). Functioning of Add-to-Cart buttons depends on CSRF token availability.

### 7.8 Quiz Flow & Personalization

*   Standard quiz flow via `QuizController` and views.

---

## 8. Backend Logic & Core PHP Components

### 8.1 Includes: Shared Logic (includes/)

*   Foundational files: `auth.php`, `db.php`, `SecurityMiddleware.php`, `ErrorHandler.php`, `EmailService.php`.

### 8.2 Controllers: Business Logic Layer (controllers/ & BaseController.php)

*   `BaseController.php`: Provides shared methods including `$db`, response helpers, validation, auth checks, **`getCsrfToken()`**. `validateAjax()` is no longer used for standard AJAX endpoints.
*   Specific Controllers: Extend `BaseController`. Handle module logic. **MUST call `$this->getCsrfToken()` and pass the token to views requiring CSRF protection for subsequent actions.** `CartController` (and similar AJAX endpoints) have had `validateAjax()` removed.

### 8.3 Database Abstraction (includes/db.php & models/)

*   Connection via `includes/db.php` (`$pdo`).
*   Interaction via Models (`models/*.php`) using prepared statements or direct PDO usage in controllers/BaseController.

### 8.4 Security Middleware & Error Handling

*   `SecurityMiddleware.php`: Static methods for applying security settings, input validation, **CSRF generation (`generateCSRFToken`) and validation (`validateCSRF`)**.
*   `ErrorHandler.php`: Sets up error/exception handling.

### 8.5 Session, Auth, and User Flow

*   Session started securely (`SecurityMiddleware::apply`). Includes standard data: `user_id`, `user_role`, `cart`, `cart_count`, **`csrf_token`**, `flash`, regeneration timestamp. Secure session settings applied.
*   Auth flow via `AccountController`, helpers in `auth.php`, enforcement via `BaseController`.

---

## 9. Database Design

### 9.1 Entity-Relationship Model (Conceptual)

Standard e-commerce relationships: Users have Orders, Orders have OrderItems, OrderItems relate to Products, Products belong to Categories. Users can have QuizResults.

### 9.2 Core Tables (from schema.sql)

Core tables (`users`, `products`, `categories`, `orders`, `order_items`, `quiz_results`, `newsletter_subscribers`, `product_attributes`, `inventory_movements`) as defined in `the_scent_schema.sql.txt`.

### 9.3 Schema Considerations & Recommendations

*   **Product Detail Fields:** The enhanced `views/product_detail.php` requires fields like `short_description`, `benefits` (JSON), `ingredients`, `usage_instructions`, `gallery_images` (JSON), `size`, `scent_profile`, `origin`, `sku`, `backorder_allowed` in the `products` table. Ensure these exist and are populated, or adjust the view accordingly.
*   **Cart Table Usage:** `CartController` currently uses `$_SESSION['cart']`. The `cart_items` table appears unused by this logic. **Decision Needed:** Implement DB-backed persistent carts (especially for logged-in users) leveraging the `cart_items` table, or remove the table if only session carts are intended.

### 9.4 Data Flow Examples

*   **Add to Cart:** (AJAX) JS reads product ID and CSRF token (from `#csrf-token-value`) -> POST to `cart/add` -> `CartController::addToCart()` validates CSRF -> Updates session -> Returns JSON. (**Now functional**)
*   **Place Order:** Form POST to `checkout/process` -> `CheckoutController::processCheckout()` validates CSRF -> Creates DB records (`orders`, `order_items`) -> Updates stock (`inventory_movements`, `products`) -> Clears session cart -> Processes payment -> Returns JSON/Redirects.
*   **View Product List:** Request to `products` (default) -> `ProductController::showProductList()` fetches products, calculates pagination, gets CSRF token -> Includes `views/products.php`, passing data and token -> View renders cards, pagination, and outputs hidden CSRF token. (**Default load corrected**)

---

## 10. Security Considerations & Best Practices

### 10.1 Input Sanitization & Validation

*   Implemented via `SecurityMiddleware::validateInput()` and `BaseController` helpers. Uses `filter_var`, `htmlspecialchars`, and type checks.

### 10.2 Session Management

*   Secure session settings applied (`HttpOnly`, `Secure`, `SameSite=Lax`, periodic regeneration). Session integrity checks (IP/User-Agent binding) are present in `BaseController` (`validateSessionIntegrity`).

### 10.3 CSRF Protection (Implemented)

*   **Mechanism:** Synchronizer Token Pattern.
    *   Token generated/retrieved via `SecurityMiddleware::generateCSRFToken` (called by `BaseController::getCsrfToken`). Stored in `$_SESSION['csrf_token']`.
    *   Server-side validation via `SecurityMiddleware::validateCSRF` compares `$_POST['csrf_token']` with `$_SESSION['csrf_token']` using `hash_equals`. Called automatically in `index.php` for POST and explicitly in AJAX controllers.
*   **Implementation Status:** The core mechanism is sound. **Crucial Requirement:** Consistent application is needed - controllers rendering views (GET) **must** fetch and pass the token to the view, and the view **must** output it into `<input type="hidden" id="csrf-token-value" ...>` for subsequent AJAX/form submissions.

### 10.4 Security Headers & CSP Issue

*   Standard security headers (`X-Frame-Options`, `X-XSS-Protection`, `X-Content-Type-Options`, `Referrer-Policy`, `Strict-Transport-Security`) are applied via `SecurityMiddleware::apply()` or `AccountController`.
*   **CSP:** The `Content-Security-Policy` header is currently commented out in `SecurityMiddleware.php` but defined (less strictly) in `AccountController`. **Recommendation:** Standardize, enable globally (e.g., in `SecurityMiddleware` or `BaseController`), and configure properly, aiming to remove `'unsafe-inline'` and `'unsafe-eval'` for enhanced XSS protection.

### 10.5 Rate Limiting (Implementation Inconsistent)

*   Configuration exists (`config.php`), and multiple, differing implementation approaches are present (`BaseController` helpers, `AccountController`, `NewsletterController`). This lacks standardization and clarity on the effective mechanism.
*   **Recommendation:** Refactor to use a single, robust rate-limiting strategy (e.g., based on Redis, Memcached, or APCu via a dedicated service) and apply it consistently to sensitive endpoints (login, registration, password reset, newsletter subscription, potentially checkout).

### 10.6 File Uploads & Permissions

*   Validation logic exists (`SecurityMiddleware::validateFileUpload`, `BaseController::validateFileUpload`). Requires proper implementation for any actual file upload features (e.g., admin product images). Secure storage practices (outside web root or with restricted access) and strict file permissions are essential.

### 10.7 Audit Logging & Error Handling

*   Mechanisms exist (`logAuditTrail`, `logSecurityEvent` in `BaseController`, `ErrorHandler.php`). Consistent application across controllers is key for effective monitoring. Error display should be disabled in production (`display_errors=Off`).

### 10.8 SQL Injection Prevention

*   The primary defense is the consistent use of **PDO Prepared Statements** in Models and database interactions, which is observed.
*   **Recommendation:** The commented-out `preventSQLInjection` function in `SecurityMiddleware.php` should be **removed** as it's unnecessary with prepared statements and potentially harmful.

---

## 11. Extensibility & Onboarding

### 11.1 Adding Features, Pages, or Controllers

*   Follow the pattern: Create Controller (extending `BaseController`), create View(s), add routing case in `index.php`. **Remember to implement correct CSRF token handling** (fetch token in controller, pass to view, output hidden input in view) if the new feature involves POST actions.

### 11.2 Adding Products, Categories, and Quiz Questions

*   Requires DB manipulation or an Admin UI. Ensure new products have all necessary fields for display (especially for the detailed view). Quiz updates require modifying `QuizController` or database potentially.

### 11.3 Developer Onboarding Checklist

1.  Setup LAMP/LEMP stack, enable `mod_rewrite`.
2.  Clone repo.
3.  Setup DB (`the_scent`, `scent_user`), import schema (`the_scent_schema.sql.txt`).
4.  Update `config.php` (or implement `.env`).
5.  Set file permissions (restrict `config.php`, ensure web server write access only where needed, e.g., logs, uploads).
6.  Configure Apache VirtualHost (`DocumentRoot` to project root, `AllowOverride All`).
7.  Browse site, check server logs (`error_log`, `access.log`).
8.  **Verify CSRF implementation:** Use browser dev tools (Network tab) to confirm the `csrf_token` is sent in POST requests (form data or AJAX body) and check views' source code for the presence of `<input id="csrf-token-value">`.
9.  **Verify Core Functionality:** Test default product page load, add-to-cart from multiple pages, cart updates/removal.

### 11.4 Testing & Debugging Notes

*   Use browser dev tools (Network, Console, Application->Session Storage/Cookies).
*   Check server logs (`error_log`, `apache-access.log`, custom logs).
*   Use `error_log()`/`var_dump()`/`print_r()`. Consider Xdebug for step debugging.
*   **Key Areas to Verify:** CSRF token flow (generation, passing, output, submission, validation), AJAX request/responses, session state persistence and data, data consistency between DB and views, UI responsiveness across devices, pagination logic.

---

## 12. Future Enhancements & Recommendations

*   **Consistent CSRF Token Handling:** Ensure all controllers rendering views pass the CSRF token, and views output the `#csrf-token-value` hidden input. (**Ongoing Diligence Required**)
*   **Standardize Rate Limiting:** Implement a single, robust mechanism.
*   **Remove `preventSQLInjection`:** Rely solely on prepared statements.
*   **Implement & Configure CSP:** Enable and configure the Content-Security-Policy header properly.
*   **Autoloader:** Implement PSR-4 autoloading (via Composer).
*   **Dependency Management:** Use Composer.
*   **Routing:** Replace `index.php` switch with a dedicated Router component/library.
*   **Templating Engine:** Consider using Twig or BladeOne for cleaner views.
*   **Environment Configuration:** Use `.env` files for sensitive configuration.
*   **Database Migrations:** Implement a migration system (e.g., Phinx).
*   **Unit/Integration Testing:** Add PHPUnit tests.
*   **API Development:** Consider a RESTful API for potential future mobile apps or decoupled frontends.
*   **Admin Panel:** Develop a comprehensive admin interface for managing products, orders, users, etc.
*   **Database Cart:** Implement persistent cart functionality using the `cart_items` table if desired.

---

## 13. Appendices

### A. Key File Summaries

| File/Folder                 | Purpose                                                                                                     | Status Notes                                       |
| :-------------------------- | :---------------------------------------------------------------------------------------------------------- | :------------------------------------------------- |
| `index.php`                 | Entry point, routing, core includes, CSRF POST validation, controller/view dispatch                         | OK                                                 |
| `config.php`                | DB credentials, App/Security settings, API keys, Email config                                               | Move secrets to .env recommended                   |
| `includes/SecurityMiddleware.php` | Static helpers: `apply()`, `validateInput()`, `validateCSRF()`, `generateCSRFToken()`                         | Remove `preventSQLInjection` recommendation        |
| `controllers/BaseController.php` | Abstract base: `$db`, helpers (JSON, redirect, render), validation, auth checks, `getCsrfToken()`, `validateAjax` | `validateAjax` no longer used by AJAX endpoints    |
| `controllers/CartController.php`| Handles AJAX cart actions                                                                                   | `validateAjax` removed from AJAX methods         |
| `controllers/*Controller.php` | Module logic, extend BaseController, **pass CSRF token to views**                                           | Needs consistent token passing                     |
| `models/*.php`              | Entity DB logic (Prepared Statements)                                                                       | OK                                                 |
| `views/layout/header.php`   | Header, navigation, assets                                                                                  | Shop link corrected                                |
| `views/*.php`               | HTML/PHP templates, **output CSRF token into `#csrf-token-value` hidden input**                               | Needs consistent token output                      |
| `views/layout/footer.php`   | Footer, JS init, **global AJAX handlers reading CSRF token from `#csrf-token-value`**                         | OK (relies on token input)                         |
| `views/products.php`        | Product list, search, filters, sorting, pagination                                                          | Loads all products by default, pagination added    |

### B. Glossary

(Standard terms: MVC, CSRF, XSS, SQLi, PDO, AJAX, CDN, CSP, Rate Limiting, Prepared Statements, Synchronizer Token Pattern)

### C. Code Snippets and Patterns

#### Correct CSRF Token Implementation Pattern (Required)

1.  **Controller (Rendering View):**
    ```php
    // Inside controller method handling GET request for a view
    $csrfToken = $this->getCsrfToken(); // Fetch/generate token
    // ... prepare other $viewData ...
    // Make token available to the view (e.g., via extract() or passing array)
    require_once __DIR__ . '/../views/path/to/view.php';
    // OR if using renderView:
    // $viewData['csrfToken'] = $csrfToken;
    // return $this->renderView('path/to/view', $viewData);
    ```
2.  **View (e.g., `views/home.php`, `views/product_detail.php`):**
    ```html
    <main>
        <!-- ** ESSENTIAL FOR AJAX ** -->
        <input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <!-- Rest of view content -->

        <!-- Example Form -->
        <form method="POST" ...>
            <!-- ** ESSENTIAL FOR FORMS ** -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <!-- Other form fields -->
            <button type="submit">Submit</button>
        </form>

        <!-- Example Button triggering AJAX -->
        <button class="add-to-cart" data-product-id="123">Add to Cart</button>
    </main>
    ```
3.  **JavaScript (AJAX POST in `footer.php` or specific scripts):**
    ```javascript
    // Get token from the standard hidden input
    const csrfTokenInput = document.getElementById('csrf-token-value');
    const csrfToken = csrfTokenInput ? csrfTokenInput.value : ''; // Use value from input

    if (!csrfToken) {
        showFlashMessage('Security token missing. Please refresh.', 'error');
        return; // Abort if token not found
    }
    // Include in fetch request body (URL-encoded example)
    fetch('index.php?page=cart&action=add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${productId}&quantity=1&csrf_token=${encodeURIComponent(csrfToken)}` // Send token in body
    })
    // ... .then() handlers ...
    ```
4.  **Server (Controller handling POST):**
    ```php
    // Early in the POST handling method (e.g., CartController::addToCart)
    $this->validateCSRF(); // Uses SecurityMiddleware::validateCSRF() which checks $_POST['csrf_token']
    // ... proceed with processing POST data ...
    ```

---
https://drive.google.com/file/d/17sQ-jAwpmCw5AH1aHdOOrSK9097fi4BO/view?usp=sharing, https://drive.google.com/file/d/1FyAA1sG46WO7EFkG2r3L4J0WemXSNubI/view?usp=sharing, https://drive.google.com/file/d/1H2R68QZCm8Nj1TvJ5vYz-sqGOYEkXvkn/view?usp=sharing, https://drive.google.com/file/d/1J2idBpA46EyMVTgO7rtrmEqs4KOCbmsz/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221M8ZMO9RYtFFaiwVV4fHitLNmxnZWE1go%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1bnysHWRLA0U-hXWBU6aM48R1-mYkSTcn/view?usp=sharing, https://drive.google.com/file/d/1iYtVZhH7PoblGzR3WViZnVSBF0nU5oT4/view?usp=sharing, https://drive.google.com/file/d/1lDSuDrMFsSCw7fktqHQr_bRFW4pfsmKC/view?usp=sharing, https://drive.google.com/file/d/1mcCunnB3JE5sNAR7alkRgRjFxPDca5qB/view?usp=sharing, https://drive.google.com/file/d/1mm-kHs4jpmySYRmEeKf68tcby04j9ZzJ/view?usp=sharing, https://drive.google.com/file/d/1nAx3ra0ScJF4NXawSpw5n7bsnZTGznqc/view?usp=sharing, https://drive.google.com/file/d/1t1NBJZ1MbfXL-SwX2Oimh3T8uwOwImeW/view?usp=sharing, https://drive.google.com/file/d/1tG2uF5vL5RVr7V59HifVCkiwn02hZ7pS/view?usp=sharing, https://drive.google.com/file/d/1yrb78QgFuSheVsJjQGDvHypZmJNjOFLf/view?usp=sharing
