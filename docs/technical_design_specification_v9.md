# The Scent – Technical Design Specification (v7.0)

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
    *   6.3 [JavaScript: Interactivity, Libraries, and CSRF Handling](#javascript-interactivity-libraries-and-csrf-handling)
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
    *   10.3 [CSRF Protection (Implemented - Strict Pattern Required)](#csrf-protection-implemented---strict-pattern-required)
    *   10.4 [Security Headers & CSP Standardization](#security-headers--csp-standardization)
    *   10.5 [Rate Limiting (Standardization Recommended)](#rate-limiting-standardization-recommended)
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
    *   C. [Code Snippets and Patterns (CSRF Implementation)](#c-code-snippets-and-patterns-csrf-implementation)

---

## 1. Introduction

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document (**v7.0**) serves as the updated technical design specification, reflecting the project's current state, architecture, and implemented fixes, particularly regarding AJAX functionality and CSRF protection. It also outlines areas requiring further standardization (rate-limiting, CSP). This version documents the **resolution of the previously identified Add-to-Cart AJAX failure**, which was caused by debug output interfering with JSON responses. The fix involved removing the debug output and ensuring strict adherence to the documented CSRF token handling pattern. This document aims to offer deep insight into the system’s structure, logic, and flow, serving as a comprehensive onboarding and reference guide for the current, functional state of the application.

---

## 2. Project Philosophy & Goals

*   **Security First:** All data input and user interactions are validated and sanitized. Strong session management and CSRF protection mechanisms are implemented and strictly required. **Strict adherence to the documented CSRF token handling pattern (Controller->View->HiddenInput `#csrf-token-value`->JS->Server Validation) is mandatory and implemented for all functional POST/AJAX operations.**
*   **Simplicity & Maintainability:** Clear, modular code structure. Direct `require_once` usage in `index.php` provides transparency but lacks autoloading benefits. Consistent coding patterns are enforced, particularly for security features like CSRF handling.
*   **Extensibility:** Architecture allows adding new features, pages, controllers, or views, requiring manual includes but offering straightforward extension points. New features involving POST must follow the established CSRF pattern.
*   **Performance:** Direct routing is potentially fast. Relies on PDO prepared statements. CDN usage for frontend libraries impacts external dependencies. Caching mechanisms (e.g., APCu for rate limiting) are recommended where applicable.
*   **Modern User Experience:** Responsive design, smooth animations (AOS.js, Particles.js), and AJAX interactions (cart updates/removal, newsletter, Add-to-Cart) provide a seamless interface. UI consistency across product displays is maintained. **Add-to-Cart functionality is now operational across the site.**
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
   | (Defines ROOT_PATH, includes core files: config, db, auth, SecurityMiddleware, ErrorHandler)
   | (Initializes ErrorHandler, applies SecurityMiddleware settings: headers, session)
   | (Determines $page, $action from $_GET, validates input)
   | (*** Validates CSRF token via SecurityMiddleware::validateCSRF() for ALL POST requests ***)
   v
[Controller]  (e.g., ProductController, CartController)
   |           (Included via require_once *within* index.php's switch case)
   |           (Instantiated, passed $pdo connection)
   |           (Extends BaseController)
   |           (*** Action method MUST call $csrfToken = $this->getCsrfToken() IF rendering a view that needs subsequent CSRF protection ***)
   |           (*** MUST pass $csrfToken to the View data ***)
   |
   | (Executes action method: business logic, DB access via Models/PDO, Rate Limiting Check)
   v
[Model / DB Layer] (e.g., models/Product.php, direct PDO in includes/db.php)
   |
   | (Prepare/execute SQL queries, fetch data using PDO Prepared Statements)
   v
[View / Response]
   |--> [View File] (e.g., views/home.php, views/products.php)
   |       (Included via require_once from controller or index.php)
   |       (Generates HTML using PHP variables, includes layout partials)
   |       (*** MUST output $csrfToken into <input type="hidden" id="csrf-token-value" ...> IF subsequent CSRF protection needed ***)
   |       (Output MUST use htmlspecialchars())
   |
   |--> [JSON Response] (via BaseController::jsonResponse for AJAX)
   |       (e.g., for cart add/update/remove, newsletter subscribe)
   |       (*** No longer prepended with debug output ***)
   |
   |--> [Redirect] (via BaseController::redirect or header())
   |
   v
[Browser/Client] <-- Renders HTML, applies CSS (Tailwind CDN, custom)
                     Executes JS (libraries, custom handlers)
                     (*** JS MUST read CSRF token STRICTLY from #csrf-token-value for AJAX POSTs ***)
```

### 3.2 Request-Response Life Cycle

1.  **Request Initiation:** User accesses a URL.
2.  **.htaccess Rewrite:** Apache rewrites the request to `/index.php` if applicable.
3.  **Initialization (`/index.php`):** Core files loaded, `$pdo` connection established, `ErrorHandler` initialized, `SecurityMiddleware::apply` sets security headers and secure session parameters. **No initial debug output.**
4.  **Routing (`/index.php`):** `$page`, `$action` extracted and validated using `SecurityMiddleware::validateInput`.
5.  **CSRF Check (`/index.php`):** If `POST`, `SecurityMiddleware::validateCSRF()` is called *automatically*. It compares `$_POST['csrf_token']` with `$_SESSION['csrf_token']` using `hash_equals`. **Failure here throws an exception handled by ErrorHandler.**
6.  **Controller/View Dispatch (`/index.php` `switch ($page)`):** Relevant controller included/instantiated. Action method called or view directly included.
7.  **Controller Action:** Executes business logic. May perform rate limiting checks (`$this->validateRateLimit()`). Interacts with DB (Models/PDO). **If rendering a view requiring subsequent CSRF protection, MUST call `$csrfToken = $this->getCsrfToken();` and pass it to the view.**
8.  **View Rendering / Response Generation:**
    *   **HTML Page:** Controller passes data (including `$csrfToken` if needed) to the view. View (`views/*.php`) generates HTML, includes layout partials (`header.php`, `footer.php`). **Crucially, if the view needs to support CSRF-protected forms or AJAX initiated from it, it MUST output the `$csrfToken` into `<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">`**. All dynamic output must be escaped (`htmlspecialchars()`).
    *   **JSON Response:** Controller uses `BaseController::jsonResponse()` for AJAX actions (e.g., Cart operations). The response contains only valid JSON.
    *   **Redirect:** Controller uses `BaseController::redirect()`.
9.  **Response Transmission:** Server sends clean HTML/JSON/Redirect to browser.
10. **Client-Side Execution:** Browser renders HTML, applies CSS, executes JS. **AJAX handlers (e.g., in `footer.php`) MUST read the CSRF token strictly from the `#csrf-token-value` hidden input when making POST requests.** `JSON.parse()` now succeeds for AJAX responses.

---

## 4. Directory & File Structure

### 4.1 Folder Map

(Structure remains consistent with v6)

```
/ (project root: /cdrom/project/The-Scent-oa5) <-- Apache DocumentRoot
|-- index.php              # Main entry script (routing, core includes, dispatch, POST CSRF validation - *No initial debug output*)
|-- config.php             # Environment, DB, security settings (SECURITY_SETTINGS array)
|-- css/
|   |-- style.css          # Custom CSS rules
|-- images/                # Public image assets (structure assumed, contains products/)
|-- videos/                # Public video assets (e.g., hero.mp4)
|-- particles.json         # Particles.js configuration
|-- includes/              # Shared PHP utility/core files
|   |-- auth.php           # Helpers: isLoggedIn(), isAdmin()
|   |-- db.php             # PDO connection setup (makes $pdo available)
|   |-- SecurityMiddleware.php # Security helpers (apply headers/session, validation, CSRF gen/validation)
|   |-- ErrorHandler.php   # Error/exception handling setup
|   |-- EmailService.php   # Email sending logic
|-- controllers/           # Business logic / request handlers
|   |-- BaseController.php # Abstract base with shared helpers (DB, JSON, redirect, validation, CSRF token fetch, Rate Limiting, auth checks, logging, etc.)
|   |-- ProductController.php
|   |-- CartController.php
|   |-- CheckoutController.php
|   |-- AccountController.php
|   |-- QuizController.php
|   |-- ... (Coupon, Inventory, Newsletter, Payment, Tax)
|-- models/                # Data representation / DB interaction (using PDO Prepared Statements)
|   |-- Product.php
|   |-- User.php
|   |-- Order.php
|   |-- Quiz.php
|-- views/                 # HTML templates (PHP files)
|   |-- home.php           # Landing page - Requires CSRF token output for Add-to-Cart
|   |-- products.php       # Product list - Requires CSRF token output for Add-to-Cart
|   |-- product_detail.php # Product detail - Requires CSRF token output for Add-to-Cart
|   |-- cart.php           # Cart view - Requires CSRF token output for AJAX updates/removal
|   |-- checkout.php       # Checkout form - Requires CSRF token output
|   |-- register.php, login.php, forgot_password.php, reset_password.php # Auth forms - Require CSRF token output
|   |-- quiz.php, quiz_results.php
|   |-- error.php, 404.php
|   |-- layout/
|   |   |-- header.php     # Sitewide header, nav (Shop link corrected), assets
|   |   |-- footer.php     # Sitewide footer, JS init, global AJAX handlers (reading #csrf-token-value)
|   |   |-- admin_header.php, admin_footer.php
|-- .htaccess              # Apache URL rewrite rules & config
|-- logs/                  # Directory for log files (needs write permissions)
|   |-- security.log
|   |-- error.log
|   |-- audit.log
|-- README.md              # Project documentation
|-- technical_design_specification.md # (This document v7)
|-- suggested_improvements_and_fixes.md # (Analysis document, basis for v7)
|-- the_scent_schema.sql.txt # Database schema
|-- ... (other docs, HTML output files)
```

### 4.2 Key Files Explained

*   **index.php**: Central orchestrator. Includes core components, performs routing, validates basic input, **automatically validates CSRF token for ALL POST requests**, includes/instantiates controllers, dispatches to actions/views. **Crucially, does NOT output any content before controller dispatch.**
*   **config.php**: Defines constants and settings, including `SECURITY_SETTINGS` array controlling session behavior, rate limits, headers (CSP), etc. **Crucial for security configuration.**
*   **includes/SecurityMiddleware.php**: Static methods: `apply()` (sets headers/session params from config), `validateInput()`, `validateCSRF()` (compares `$_POST['csrf_token']` with session token), `generateCSRFToken()`.
*   **controllers/BaseController.php**: Abstract base providing shared functionality: `$db`, response helpers (`jsonResponse`, `redirect`), view rendering (`renderView`), validation helpers, authentication checks (`requireLogin`, `requireAdmin`), logging (`logAuditTrail`, `logSecurityEvent`), **CSRF token fetching (`getCsrfToken`)**, **standardized rate limiting check (`validateRateLimit`)**.
*   **controllers/*Controller.php**: Handle module-specific logic, extend `BaseController`. Fetch data (Models/PDO), prepare data for views. **Must call `$csrfToken = $this->getCsrfToken();` and pass token to views requiring subsequent CSRF protection.** Handle AJAX responses via `jsonResponse`. Call `validateRateLimit()` where needed (requires standardization).
*   **models/*.php**: Encapsulate entity-specific DB logic using **PDO Prepared Statements** for SQLi prevention.
*   **views/layout/header.php**: Common header, includes assets, dynamic session info. Corrected "Shop" link (`index.php?page=products`).
*   **views/layout/footer.php**: Common footer, JS initializations (AOS, Particles), **global AJAX handlers (Add-to-Cart, Newsletter) which strictly read CSRF token from `#csrf-token-value` hidden input**, `showFlashMessage` JS helper.
*   **views/*.php**: Page templates. **Must output `$csrfToken` into `<input type="hidden" id="csrf-token-value"...>` when forms/AJAX POSTs are initiated from the page.** Use `htmlspecialchars()` for all dynamic output.
*   **views/products.php**: Product list page. Displays grid, filters, sorting, pagination. **Requires CSRF token output.** Loads all products by default. Uses correct `image` field for product images.
*   **views/product_detail.php**: Enhanced detail view. AJAX Add-to-Cart operational. **Requires CSRF token output.**
*   **views/home.php**: Landing page. AJAX Add-to-Cart operational. **Requires CSRF token output.**

---

## 5. Routing and Application Flow

### 5.1 URL Routing via .htaccess

*   Mechanism: Apache `mod_rewrite` routes most non-file/directory requests to `/index.php`. Standard configuration.

### 5.2 index.php: The Application Entry Point

*   Role: Front Controller/Router.
*   Process: Initializes core components, extracts/validates `$page`/`$action`, **automatically performs CSRF check on ALL POST requests via `SecurityMiddleware::validateCSRF()`**, dispatches to controllers via `switch($page)`. **Does not output any debug information before dispatch.**

### 5.3 Controller Dispatch & Action Flow

*   Controllers included/instantiated by `index.php`. Extend `BaseController`.
*   Execute business logic, use Models/PDO for data.
*   **Required CSRF Pattern:** Controllers rendering views that will initiate POST/AJAX requests **must** call `$csrfToken = $this->getCsrfToken();` and pass this token to the view data.
*   Pass data (including `$csrfToken`) to views via `$data` array with `renderView` or `extract()`.
*   Check rate limits using `$this->validateRateLimit()` on sensitive actions (standardization needed).
*   Terminate via view inclusion, `jsonResponse`, or `redirect`.

### 5.4 Views: Templating and Rendering

*   PHP files in `views/` mixing HTML and PHP.
*   Include layout partials (`header.php`, `footer.php`).
*   **Required CSRF Pattern:** Where subsequent forms or AJAX POSTs are initiated, the view **must** output the passed `$csrfToken` into a dedicated hidden input: `<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">`. Standard forms should *also* include `<input type="hidden" name="csrf_token" value="...">`.
*   Use `htmlspecialchars()` for all dynamic output.
*   Styling via `/css/style.css` and Tailwind CDN.
*   JS initialized/handled primarily in `footer.php`, **strictly relying on `#csrf-token-value` for CSRF tokens in AJAX.**

---

## 6. Frontend Architecture

### 6.1 CSS (css/style.css), Tailwind (CDN), and Other Libraries

*   Hybrid styling: Tailwind CDN utilities + custom CSS in `/css/style.css`.
*   Libraries via CDN: Google Fonts, Font Awesome 6, AOS.js, Particles.js.

### 6.2 Responsive Design and Accessibility

*   Responsive via Tailwind breakpoints. Mobile menu functional.
*   Basic accessibility practices followed (semantic HTML, `aria-label`). Further audit recommended.

### 6.3 JavaScript: Interactivity, Libraries, and CSRF Handling

*   **Library Initialization:** AOS, Particles initialized in `footer.php`.
*   **Custom Handlers (Primarily `footer.php`):**
    *   **Add-to-Cart:** Global handler for `.add-to-cart` using event delegation. **Reads the CSRF token *strictly* from the `#csrf-token-value` hidden input.** Sends AJAX POST to `cart/add`, handles the *clean* JSON response, uses `showFlashMessage`. **This functionality is now working correctly.**
    *   **Newsletter:** Handlers for `#newsletter-form` / `#newsletter-form-footer`. **Reads CSRF token strictly from `#csrf-token-value`**, sends via AJAX POST, uses `showFlashMessage`.
*   **Page-Specific JS:** Mobile menu (`header.php`), Cart page AJAX (`cart.php`), Product Detail AJAX/UI (`product_detail.php`). These also rely on the presence of `#csrf-token-value` for POST actions.
*   **Standardization:** `showFlashMessage` helper used for feedback. Global handlers in `footer.php` minimize redundancy. **Key Dependency:** Functionality of AJAX POST handlers is critically dependent on the `#csrf-token-value` input being present and correctly populated on the page being viewed.

---

## 7. Key Pages & Components

### 7.1 Home/Landing Page (views/home.php)

*   Displays standard sections. Product cards use consistent styling. **Requires CSRF token output (`#csrf-token-value`)**. `.add-to-cart` buttons are functional via the global handler in `footer.php`.

### 7.2 Header and Navigation (views/layout/header.php)

*   Standard header. Displays dynamic session info. Includes assets. Mobile menu functional. Shop link points correctly to `index.php?page=products`.

### 7.3 Footer and Newsletter (views/layout/footer.php)

*   Standard footer. Contains JS init and **global AJAX handlers (Add-to-Cart, Newsletter) which strictly read the CSRF token from the `#csrf-token-value` hidden input.** Defines `showFlashMessage`.

### 7.4 Product Grid & Cards

*   Consistent styling using Tailwind classes across `home.php`, `products.php`, and `product_detail.php`. Includes stock status display and functional Add-to-Cart buttons (dependent on CSRF token output and JS handler). Product list page now uses correct `image` field.

### 7.5 Shopping Cart (views/cart.php)

*   Displays items. JS handles quantity updates and AJAX removal. **Requires CSRF token output (`#csrf-token-value`)** for AJAX actions. Uses session storage, not DB table `cart_items`.

### 7.6 Product Detail Page (views/product_detail.php)

*   Displays using the enhanced layout. Includes gallery, tabs, details, related products. AJAX Add-to-Cart for main and related products functional. **Requires CSRF token output (`#csrf-token-value`)**. Requires necessary fields in `products` table (see 9.3).

### 7.7 Products Page (views/products.php)

*   Displays product listing with filters, sorting, pagination. Loads all products by default. Functioning Add-to-Cart buttons via global handler. **Requires CSRF token output (`#csrf-token-value`)**. Displays correct product images.

### 7.8 Quiz Flow & Personalization

*   Standard quiz flow via `QuizController` and views. Forms require CSRF token.

---

## 8. Backend Logic & Core PHP Components

### 8.1 Includes: Shared Logic (includes/)

*   Foundational files: `auth.php`, `db.php`, `SecurityMiddleware.php`, `ErrorHandler.php`, `EmailService.php`.

### 8.2 Controllers: Business Logic Layer (controllers/ & BaseController.php)

*   `BaseController.php`: Provides shared methods including `$db`, response helpers (clean `jsonResponse`), validation, auth checks, **`getCsrfToken()`**, **`validateRateLimit()`**.
*   Specific Controllers: Extend `BaseController`. Handle module logic. **Must call `$this->getCsrfToken()` and pass the token to views requiring subsequent CSRF protection.** Use `$this->validateRateLimit()` for sensitive actions (requires standardization).

### 8.3 Database Abstraction (includes/db.php & models/)

*   Connection via `includes/db.php` (`$pdo`).
*   Interaction via Models (`models/*.php`) or direct PDO usage, **strictly using Prepared Statements**.

### 8.4 Security Middleware & Error Handling

*   `SecurityMiddleware.php`: Static methods for applying security settings (headers/session from `config.php`), input validation, **CSRF generation (`generateCSRFToken`) and automatic validation (`validateCSRF` called in `index.php`)**.
*   `ErrorHandler.php`: Sets up global error/exception handling.

### 8.5 Session, Auth, and User Flow

*   Session started securely (`SecurityMiddleware::apply` uses settings from `config.php`). Includes standard data: `user_id`, `user_role`, `cart`, `cart_count`, `csrf_token`, `flash`, `last_regeneration`. Secure session settings applied. Session integrity validated in `BaseController::requireLogin`.
*   Auth flow via `AccountController`, helpers in `auth.php`, enforcement via `BaseController`. Rate limiting applied to login/register/reset actions (requires standardization).

---

## 9. Database Design

### 9.1 Entity-Relationship Model (Conceptual)

Standard e-commerce relationships: Users have Orders, Orders have OrderItems, OrderItems relate to Products, Products belong to Categories. Users can have QuizResults.

### 9.2 Core Tables (from schema.sql)

Core tables (`users`, `products`, `categories`, `orders`, `order_items`, `cart_items`, `quiz_results`, `newsletter_subscribers`, `product_attributes`, `inventory_movements`) as defined in `the_scent_schema.sql.txt`.

### 9.3 Schema Considerations & Recommendations

*   **Product Detail Fields:** Enhanced `views/product_detail.php` requires fields like `short_description`, `benefits` (JSON), `ingredients`, `usage_instructions`, `gallery_images` (JSON), `size`, `scent_profile`, `origin`, `sku`, `backorder_allowed` in the `products` table. Schema includes these; ensure they are populated for full functionality. The `image` field (not `image_url`) holds the primary image path.
*   **Cart Table Usage:** `CartController` currently uses `$_SESSION['cart']`. The `cart_items` table exists but is unused by the current logic. **Recommendation:** Implement DB-backed persistent carts using `cart_items` (recommended for logged-in users) or remove the table from the schema if only session carts are intended.

### 9.4 Data Flow Examples

*   **Add to Cart (Home/Product/Detail Page):** (AJAX) JS reads product ID, reads CSRF token *strictly* from `#csrf-token-value` -> POST to `index.php?page=cart&action=add` -> `index.php` validates CSRF -> `CartController::addToCart()` checks stock -> Updates `$_SESSION['cart']` -> Returns *clean* JSON response via `BaseController::jsonResponse`. (**Functional**)
*   **Place Order:** Form POST to `checkout/process` -> `index.php` validates CSRF -> `CheckoutController::processCheckout()` validates input, checks stock -> Creates DB records (`orders`, `order_items`) -> Updates stock -> Clears session cart -> Processes payment -> Logs audit trail -> Returns JSON/Redirects.
*   **View Product List:** Request to `products` -> `ProductController::showProductList()` fetches products, calculates pagination, gets CSRF token (`$this->getCsrfToken()`) -> Includes `views/products.php`, passing data including `$csrfToken` -> View renders cards using correct `image` field, pagination, and outputs `<input id="csrf-token-value">`.

---

## 10. Security Considerations & Best Practices

### 10.1 Input Sanitization & Validation

*   Handled via `SecurityMiddleware::validateInput()` and specific checks within controllers/`BaseController`. Uses `filter_var`, `htmlspecialchars`, type checks.

### 10.2 Session Management

*   Secure session settings applied via `SecurityMiddleware::apply()` using `config.php`: `HttpOnly`, `Secure`, `SameSite=Lax`, periodic regeneration (`session_regenerate_id`). Session integrity checks (IP/User-Agent binding) implemented in `BaseController::requireLogin`.

### 10.3 CSRF Protection (Implemented - Strict Pattern Required)

*   **Mechanism:** Synchronizer Token Pattern implemented via `SecurityMiddleware` and `BaseController`.
    *   Token generated/retrieved via `SecurityMiddleware::generateCSRFToken` (called by `BaseController::getCsrfToken`). Stored in `$_SESSION['csrf_token']`.
    *   Validation via `SecurityMiddleware::validateCSRF` compares `$_POST['csrf_token']` with `$_SESSION['csrf_token']` using `hash_equals`. **Called automatically in `index.php` for all POST requests.**
*   **Implementation Status:** Mechanism is sound and **strictly required** for all POST/AJAX actions.
    1.  Controllers rendering views needing CSRF protection **MUST** call `$csrfToken = $this->getCsrfToken()` and pass it to the view.
    2.  Views **MUST** output this token into `<input type="hidden" id="csrf-token-value" ...>`.
    3.  JavaScript making AJAX POST requests **MUST** read the token *only* from `#csrf-token-value`.
    *   Adherence to this pattern resolved the critical Add-to-Cart AJAX issue.

### 10.4 Security Headers & CSP Standardization

*   Standard security headers (`X-Frame-Options`, `X-XSS-Protection`, `X-Content-Type-Options`, `Referrer-Policy`, `Strict-Transport-Security`) are **applied globally and consistently** via `SecurityMiddleware::apply()` based on definitions in `config.php` `SECURITY_SETTINGS['headers']`.
*   **CSP:** A default `Content-Security-Policy` is defined in `config.php` and applied. **Recommendation:** Review and tighten the CSP policy further, especially removing `'unsafe-inline'` and `'unsafe-eval'` if possible, by refactoring inline scripts/styles.

### 10.5 Rate Limiting (Standardization Recommended)

*   A standardized mechanism exists via `BaseController::validateRateLimit()`, intended to use APCu or a similar cache based on `config.php` settings.
*   **Current Status:** Implementation is inconsistent. `AccountController` and `NewsletterController` use custom logic. The base method depends on APCu, which might not be available (fails open with a warning).
*   **Recommendation:** **Refactor `AccountController` and `NewsletterController` to consistently use `$this->validateRateLimit('action_key')`**, configuring limits in `config.php`. Ensure the chosen backend (APCu/Redis) is properly configured and functional or implement a robust fallback (e.g., DB-based).

### 10.6 File Uploads & Permissions

*   Validation logic exists (`SecurityMiddleware::validateFileUpload`, `BaseController::validateFileUpload`). Secure implementation (validation, sanitization, storage location, permissions) is required if file uploads are added.

### 10.7 Audit Logging & Error Handling

*   `ErrorHandler.php` provides global error/exception handling (logging to file, displaying generic error in production).
*   `BaseController` provides `logAuditTrail` and `logSecurityEvent` methods. **Recommendation:** Consistently use these methods in controllers for significant user actions and security-related events. Remove debug `error_log` calls from production code.

### 10.8 SQL Injection Prevention

*   **Primary Defense: PDO Prepared Statements.** Models (`models/*.php`) and other direct DB interactions consistently use prepared statements. This is the correct and sufficient approach.
*   **Recommendation:** Remove the commented-out `preventSQLInjection` function from `SecurityMiddleware.php` as it's unnecessary.

---

## 11. Extensibility & Onboarding

### 11.1 Adding Features, Pages, or Controllers

*   Follow the pattern: Create Controller (extending `BaseController`), create View(s), add routing case in `index.php`. **Crucially, implement the strict CSRF token handling pattern** (fetch token in controller, pass to view, output `#csrf-token-value` hidden input in view) if the new feature involves POST actions.

### 11.2 Adding Products, Categories, and Quiz Questions

*   Requires DB manipulation or an Admin UI (future enhancement). Ensure new products have all necessary fields populated (especially JSON fields like `benefits`, `gallery_images`, and fields for `product_detail.php`, using `image` for the main image path).

### 11.3 Developer Onboarding Checklist

1.  Setup LAMP/LEMP stack, enable `mod_rewrite`.
2.  Clone repo.
3.  Setup DB (`the_scent`, `scent_user`), import schema (`the_scent_schema.sql.txt`).
4.  Configure `config.php` (database credentials, consider moving secrets to `.env`).
5.  Set file permissions (restrict `config.php`, ensure web server write access to `logs/` directory).
6.  Configure Apache VirtualHost (`DocumentRoot` to project root, `AllowOverride All`).
7.  Browse site, check server logs (`logs/error.log`, `logs/security.log`, `apache-access.log`).
8.  **Verify CSRF implementation:** Use browser dev tools (Network tab) to confirm `csrf_token` is sent in POST request bodies. Check view source for `<input id="csrf-token-value">` on pages like Home, Product List, Product Detail, Cart.
9.  **Verify Core Functionality:** Test Add-to-Cart from Home, Product List, and Product Detail pages (should now work). Test cart updates/removal. Test newsletter signup. Test login/registration.

### 11.4 Testing & Debugging Notes

*   Use browser dev tools (Network, Console, Application->Session Storage/Cookies).
*   Check application logs (`logs/*.log`) and server logs (`apache-error.log`, `apache-access.log`).
*   **Verify AJAX Responses:** Ensure JSON responses are clean and correctly parsed by the browser.
*   **Key Areas to Verify:** CSRF token flow (generation, passing, output in `#csrf-token-value`, submission, validation), AJAX request/responses, rate limiting effectiveness (once standardized), session state, data consistency, UI responsiveness, pagination.

---

## 12. Future Enhancements & Recommendations

*   **Standardize Rate Limiting:** Refactor controllers to use the `BaseController::validateRateLimit` method consistently. Ensure a working backend cache (APCu/Redis) or implement a fallback. (**High Priority**)
*   **Tighten CSP:** Review and refine the Content-Security-Policy in `config.php` to remove `'unsafe-inline'` and `'unsafe-eval'`.
*   **Database Cart:** Implement persistent cart functionality using the `cart_items` table for logged-in users.
*   **Remove SQLi Function:** Delete the commented-out `preventSQLInjection` from `SecurityMiddleware.php`.
*   **Consistent Logging:** Ensure `logAuditTrail` and `logSecurityEvent` are used appropriately throughout the codebase. Remove debug `error_log` calls.
*   **Autoloader:** Implement PSR-4 autoloading (via Composer).
*   **Dependency Management:** Use Composer for managing external libraries (instead of CDNs where feasible).
*   **Routing:** Replace `index.php` switch with a dedicated Router component/library (e.g., FastRoute, Symfony Routing).
*   **Templating Engine:** Consider using Twig or BladeOne for cleaner views.
*   **Environment Configuration:** Use `.env` files (e.g., via `vlucas/phpdotenv`) for sensitive configuration (DB credentials, API keys).
*   **Database Migrations:** Implement a migration system (e.g., Phinx).
*   **Unit/Integration Testing:** Add PHPUnit tests.
*   **API Development:** Consider a RESTful API layer.
*   **Admin Panel:** Develop a comprehensive admin interface.

---

## 13. Appendices

### A. Key File Summaries

| File/Folder                 | Purpose                                                                                                           | Status Notes                                                              |
| :-------------------------- | :---------------------------------------------------------------------------------------------------------------- | :------------------------------------------------------------------------ |
| `index.php`                 | Entry point, routing, core includes, **auto POST CSRF validation**, controller/view dispatch. **No debug output.** | OK                                                                        |
| `config.php`                | DB credentials, App/Security settings (**CSP, Rate Limits, Session**), API keys                                     | Defines centralized security config. Move secrets to .env recommended.      |
| `includes/SecurityMiddleware.php` | Static helpers: `apply()` (headers/session from config), `validateInput()`, `validateCSRF()`, `generateCSRFToken()` | OK. `preventSQLInjection` removal recommended.                          |
| `controllers/BaseController.php` | Abstract base: `$db`, helpers (JSON, redirect, render), validation, auth checks, `getCsrfToken()`, `validateRateLimit` | Provides standardized helpers. Rate limiting usage needs enforcement.       |
| `controllers/CartController.php`| Handles AJAX cart actions                                                                                         | Uses session cart. DB cart possible future enhancement. AJAX functional.  |
| `controllers/*Controller.php` | Module logic, extend BaseController, **must pass CSRF token to views**, **use validateRateLimit**                 | Requires consistent CSRF passing & Rate Limit standardization.            |
| `models/*.php`              | Entity DB logic (**PDO Prepared Statements**)                                                                     | OK                                                                        |
| `views/layout/header.php`   | Header, navigation, assets                                                                                        | Shop link corrected.                                                      |
| `views/*.php`               | HTML/PHP templates, **must output CSRF token into `#csrf-token-value` hidden input**                              | Requires consistent token output for AJAX/Forms. Use `image` field.       |
| `views/layout/footer.php`   | Footer, JS init, **global AJAX handlers strictly reading CSRF token from `#csrf-token-value`**                     | JS handler works correctly, relying on required hidden input.             |
| `views/products.php`        | Product list, filters, sorting, pagination. **Requires CSRF token output.** Uses correct `image` field.             | OK.                                                                       |

### B. Glossary

(Standard terms: MVC, CSRF, XSS, SQLi, PDO, AJAX, CDN, CSP, Rate Limiting, Prepared Statements, Synchronizer Token Pattern, APCu)

### C. Code Snippets and Patterns (CSRF Implementation)

#### Correct & Required CSRF Token Implementation Pattern

1.  **Controller (Rendering View):**
    ```php
    // Inside controller method handling GET request for a view (e.g., ProductController::showHomePage)
    try {
        $featuredProducts = $this->productModel->getFeatured();
        // ... other data fetching ...

        // *** CRITICAL: Get CSRF token ***
        $csrfToken = $this->getCsrfToken();

        // Pass token and other data to the view
        require_once __DIR__ . '/../views/home.php'; // or use $this->renderView('home', ['featuredProducts' => ..., 'csrfToken' => $csrfToken]);

    } catch (Exception $e) {
        // Handle error
    }
    ```
2.  **View (e.g., `views/home.php`, `views/products.php`, `views/product_detail.php`):**
    ```html
    <!-- Include header which sets up the basic page structure -->
    <?php require_once __DIR__ . '/layout/header.php'; ?>

    <!-- ** ESSENTIAL FOR AJAX/FORMS initiated from this page ** -->
    <input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <!-- Rest of view content -->

    <!-- Example Form -->
    <form method="POST" action="index.php?page=some_action">
        <!-- ** ESSENTIAL FOR STANDARD FORMS ** -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <!-- Other form fields -->
        <button type="submit">Submit</button>
    </form>

    <!-- Example Button triggering AJAX (handled by global JS in footer.php) -->
    <button class="add-to-cart" data-product-id="123">Add to Cart</button>

    <!-- Include footer which contains the global JS handlers -->
    <?php require_once __DIR__ . '/layout/footer.php'; ?>
    ```
3.  **JavaScript (Global AJAX POST handler in `views/layout/footer.php`):**
    ```javascript
    // Canonical Add-to-Cart handler (event delegation) - Functional & Strict
    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.add-to-cart');
        if (!btn) return;
        e.preventDefault();
        if (btn.disabled) return;
        const productId = btn.dataset.productId;

        // --- STRICTLY get CSRF token from the standard hidden input by ID ---
        let csrfToken = '';
        const csrfTokenInput = document.getElementById('csrf-token-value');
        if (csrfTokenInput) {
            csrfToken = csrfTokenInput.value;
        }
        // --- END STRICT retrieval ---

        if (!csrfToken) {
            showFlashMessage('Security token input not found on page. Please refresh.', 'error');
            console.error('CSRF token input #csrf-token-value not found.');
            return; // Abort if token input not found
        }

        btn.disabled = true;
        const originalText = btn.textContent;
        btn.textContent = 'Adding...';

        fetch('index.php?page=cart&action=add', {
            method: 'POST',
            headers: {
                 'Content-Type': 'application/x-www-form-urlencoded',
                 'Accept': 'application/json' // Crucial for expecting JSON
            },
            // Send the retrieved token in the body
            body: `product_id=${encodeURIComponent(productId)}&quantity=1&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => { // Check content type before parsing JSON
            const contentType = response.headers.get("content-type");
            if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                return response.json();
            }
            // Handle non-JSON or error responses gracefully
            return response.text().then(text => {
                 console.error('Received non-JSON response:', response.status, text);
                 throw new Error(`Server returned status ${response.status}. Check server logs.`);
            });
        })
        .then(data => { /* ... process success/failure based on JSON data ... */ })
        .catch(error => { /* ... handle fetch/parse error ... */ });
    });
    ```
4.  **Server (`index.php` before Controller Dispatch):**
    ```php
    // ... after determining page/action ...
    // Validate CSRF token for POST requests (AUTOMATICALLY CALLED)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        SecurityMiddleware::validateCSRF(); // Throws exception on failure
    }
    // ... dispatch to controller ...
    ```

---
