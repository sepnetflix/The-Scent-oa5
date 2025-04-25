# The Scent – Technical Design Specification (v9.0)

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
    *   C. [Code Snippets and Patterns (CSRF Implementation & Error Handling Fix Recommendation)](#c-code-snippets-and-patterns-csrf-implementation--error-handling-fix-recommendation)

---

## 1. Introduction

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document (**v9.0**) serves as the updated technical design specification, reflecting the project's current state after addressing key functional issues and incorporating analysis from previous reviews.

This version documents the **resolution of the shopping cart page display error** (caused by an incorrect image key reference in the view, fix applied/required). It confirms that the **product list pagination is functional**, contradicting previous reports. It clarifies the inconsistent **cart storage mechanism**, and reiterates recommendations for **standardizing rate limiting** and **tightening the Content Security Policy (CSP)**. Add-to-Cart AJAX functionality remains operational following strict CSRF token handling patterns. A minor error handling quirk ("Headers Already Sent" warning) has also been identified and a fix recommended.

This document aims to offer deep insight into the system’s structure, logic, and flow, serving as a comprehensive onboarding and reference guide for the current state of the application, including known issues and recommended next steps.

---

## 2. Project Philosophy & Goals

*   **Security First:** All data input and user interactions are validated and sanitized. Strong session management and CSRF protection mechanisms are implemented and strictly required. **Strict adherence to the documented CSRF token handling pattern (Controller->View->HiddenInput `#csrf-token-value`->JS->Server Validation) is mandatory and implemented for all functional POST/AJAX operations.**
*   **Simplicity & Maintainability:** Clear, modular code structure. Direct `require_once` usage in `index.php` provides transparency but lacks autoloading benefits. Consistent coding patterns are enforced, particularly for security features like CSRF handling.
*   **Extensibility:** Architecture allows adding new features, pages, controllers, or views, requiring manual includes but offering straightforward extension points. New features involving POST must follow the established CSRF pattern.
*   **Performance:** Direct routing is potentially fast. Relies on PDO prepared statements. CDN usage for frontend libraries impacts external dependencies. Caching mechanisms (e.g., APCu for rate limiting) are recommended where applicable.
*   **Modern User Experience:** Responsive design, smooth animations (AOS.js, Particles.js), and AJAX interactions (cart updates/removal, newsletter, Add-to-Cart) provide a seamless interface. UI consistency across product displays is maintained. **Add-to-Cart functionality is operational.** **Shopping Cart display is functional.** **Product list pagination is functional.**
*   **Transparency:** No magic – application flow and routing are explicit in `index.php`'s include and switch logic.
*   **Accessibility & SEO:** Semantic HTML used. `aria-label` observed. Basic accessibility practices followed, further audit recommended.

---

## 3. System Architecture Overview

### 3.1 High-Level Workflow

```
[Browser/Client]
   |
   | (HTTP request, e.g., /index.php?page=cart or /index.php?page=product&id=1)
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
   | (e.g., CartController::showCart() now handles the main cart view request correctly)
   v
[Model / DB Layer] (e.g., models/Product.php, models/Cart.php, direct PDO in includes/db.php)
   |
   | (Prepare/execute SQL queries, fetch data using PDO Prepared Statements. Product pagination logic confirmed working.)
   v
[View / Response]
   |--> [View File] (e.g., views/cart.php, views/products.php)
   |       (Included via require_once from controller action, e.g., CartController::showCart includes views/cart.php)
   |       (Generates HTML using PHP variables passed from controller, includes layout partials)
   |       (*** MUST output $csrfToken into <input type="hidden" id="csrf-token-value" ...> IF subsequent CSRF protection needed ***)
   |       (Output MUST use htmlspecialchars())
   |       (*** cart.php requires fix: use 'image' key instead of 'image_url' ***)
   |
   |--> [JSON Response] (via BaseController::jsonResponse for AJAX)
   |       (e.g., for cart add/update/remove, newsletter subscribe)
   |       (Clean JSON output)
   |
   |--> [Redirect] (via BaseController::redirect or header())
   |
   v
[Browser/Client] <-- Renders HTML, applies CSS (Tailwind CDN, custom)
                     Executes JS (libraries, custom handlers)
                     (*** JS MUST read CSRF token STRICTLY from #csrf-token-value for AJAX POSTs ***)
```

### 3.2 Request-Response Life Cycle

*(Largely unchanged from v8.0, reflecting the now-correct routing and data flow for the cart page)*

1.  **Request Initiation:** User accesses a URL (e.g., `index.php?page=cart`).
2.  **.htaccess Rewrite:** Apache rewrites the request to `/index.php` if applicable.
3.  **Initialization (`/index.php`):** Core files loaded, `$pdo` connection established, `ErrorHandler` initialized, `SecurityMiddleware::apply` sets security headers and secure session parameters.
4.  **Routing (`/index.php`):** `$page` ('cart'), `$action` ('index') extracted and validated.
5.  **CSRF Check (`/index.php`):** (Not applicable for GET).
6.  **Controller/View Dispatch (`/index.php` `switch ($page)`):**
    *   Case `cart`: `CartController.php` is included, `$controller = new CartController($pdo)` is instantiated.
    *   `$controller->showCart();` is executed.
7.  **Controller Action (`CartController::showCart()`):**
    *   Fetches cart items using `$this->getCartItems()` (using session/DB).
    *   Calculates `$total`.
    *   Gets CSRF token via `$this->getCsrfToken();`.
    *   Includes `views/cart.php`, passing `$cartItems`, `$total`, `$csrfToken`.
8.  **View Rendering (`views/cart.php`):**
    *   Includes `layout/header.php`.
    *   Outputs the CSRF token into `<input type="hidden" id="csrf-token-value" ...>`.
    *   **Critical Point:** Renders items using `$item['product']`. **Must use `$item['product']['image']`** (not `image_url`) for the image source to avoid errors. Uses `htmlspecialchars()`.
    *   Includes `layout/footer.php`.
9.  **Response Transmission:** Server sends the complete HTML page.
10. **Client-Side Execution:** Browser renders the cart page. JS attaches listeners.

---

## 4. Directory & File Structure

### 4.1 Folder Map

*(No changes from v8.0)*

```
/ (project root: /cdrom/project/The-Scent-oa5) <-- Apache DocumentRoot
|-- index.php              # Main entry script (routing, core includes, dispatch, POST CSRF validation)
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
|   |-- products.php       # Product list - Requires CSRF token output for Add-to-Cart. *Pagination functional.*
|   |-- product_detail.php # Product detail - Requires CSRF token output for Add-to-Cart
|   |-- cart.php           # Cart view - Functional (requires view fix). Requires CSRF token output for AJAX.
|   |-- checkout.php       # Checkout form - Requires CSRF token output
|   |-- register.php, login.php, forgot_password.php, reset_password.php # Auth forms - Require CSRF token output
|   |-- quiz.php, quiz_results.php
|   |-- error.php, 404.php
|   |-- layout/
|   |   |-- header.php     # Sitewide header, nav, assets
|   |   |-- footer.php     # Sitewide footer, JS init, global AJAX handlers (reading #csrf-token-value)
|   |   |-- admin_header.php, admin_footer.php
|-- .htaccess              # Apache URL rewrite rules & config
|-- logs/                  # Directory for log files (needs write permissions)
|   |-- security.log
|   |-- error.log
|   |-- audit.log
|-- README.md              # Project documentation
|-- technical_design_specification.md # (This document v9)
|-- suggested_improvements_and_fixes.md # (Analysis document v1.0)
|-- the_scent_schema.sql.txt # Database schema
|-- ... (other docs, HTML output files)
```

### 4.2 Key Files Explained

*   **index.php**: Central orchestrator. Routing fixed for cart page. **Auto POST CSRF validation**. Dispatches to controllers.
*   **config.php**: Configuration store. CSP needs review. Rate limit settings used by `BaseController`.
*   **includes/SecurityMiddleware.php**: Security helpers. `validateCSRF()` enforces token check. `preventSQLInjection` **should be removed**.
*   **controllers/BaseController.php**: Abstract base. Provides shared helpers. `getCsrfToken()` used by controllers. `validateRateLimit()` **needs consistent usage**.
*   **controllers/ProductController.php**: Handles product views. **Pagination logic confirmed working**.
*   **controllers/CartController.php**: Handles cart logic. **`showCart()` renders main view correctly.** Handles AJAX. Cart storage inconsistency remains.
*   **models/Product.php**: DB logic via **PDO Prepared Statements**. **`getFiltered()` pagination logic confirmed working**.
*   **views/layout/header.php**: Standard header.
*   **views/layout/footer.php**: Standard footer. **Global AJAX handlers read CSRF from `#csrf-token-value`**.
*   **views/*.php**: Templates. **Must output CSRF token correctly**. Use `htmlspecialchars()`.
*   **views/products.php**: Product list page. **Pagination functional.** Requires CSRF token output.
*   **views/cart.php**: Cart view. **Functional**, but **requires code fix (`image` key)**. Requires CSRF token output.
*   **views/product_detail.php**: Product detail. AJAX functional. Requires CSRF token output.

---

## 5. Routing and Application Flow

### 5.1 URL Routing via .htaccess

*(No changes from v8.0)*

*   Mechanism: Apache `mod_rewrite` routes most non-file/directory requests to `/index.php`. Standard configuration.

### 5.2 index.php: The Application Entry Point

*(Updated based on cart fix)*

*   Role: Front Controller/Router.
*   Process: Initializes core components, extracts/validates `$page`/`$action`, reads `page_num` for pagination, **automatically performs CSRF check on ALL POST requests via `SecurityMiddleware::validateCSRF()`**, dispatches to controllers via `switch($page)`. **Routing logic for `case 'cart':` now correctly calls `CartController::showCart()` for the default view.**

### 5.3 Controller Dispatch & Action Flow

*(No significant changes from v8.0, emphasizes CSRF pattern)*

*   Controllers included/instantiated by `index.php`. Extend `BaseController`.
*   Execute business logic, use Models/PDO for data. Read parameters like `page_num`.
*   **Required CSRF Pattern:** Controllers rendering views that will initiate POST/AJAX requests **must** call `$csrfToken = $this->getCsrfToken();` and pass this token to the view data.
*   Pass data (including `$csrfToken`, pagination data) to views.
*   Check rate limits using `$this->validateRateLimit()` on sensitive actions (**standardization needed**).
*   Terminate via view inclusion (`require_once`), `jsonResponse`, or `redirect`.

### 5.4 Views: Templating and Rendering

*(Updated based on cart fix and CSRF pattern emphasis)*

*   PHP files in `views/` mixing HTML and PHP.
*   Include layout partials (`header.php`, `footer.php`).
*   **Required CSRF Pattern:** Where subsequent forms or AJAX POSTs are initiated, the view **must** output the passed `$csrfToken` into a dedicated hidden input: `<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">`. Standard forms should *also* include `<input type="hidden" name="csrf_token" value="...">`.
*   Use `htmlspecialchars()` for all dynamic output.
*   Styling via `/css/style.css` and Tailwind CDN.
*   JS initialized/handled primarily in `footer.php`, **strictly relying on `#csrf-token-value` for CSRF tokens in AJAX.**
*   Pagination UI rendered in `views/products.php` based on data passed from the controller (**Functional**).
*   `views/cart.php` **requires fix** to use `$item['product']['image']` instead of `image_url`.

---

## 6. Frontend Architecture

*(No significant changes from v8.0, emphasizes CSRF JS handling)*

### 6.1 CSS (css/style.css), Tailwind (CDN), and Other Libraries

*   Hybrid styling: Tailwind CDN utilities + custom CSS in `/css/style.css`.
*   Libraries via CDN: Google Fonts, Font Awesome 6, AOS.js, Particles.js.

### 6.2 Responsive Design and Accessibility

*   Responsive via Tailwind breakpoints. Mobile menu functional.
*   Basic accessibility practices followed. Further audit recommended.

### 6.3 JavaScript: Interactivity, Libraries, and CSRF Handling

*   **Library Initialization:** AOS, Particles initialized in `footer.php`.
*   **Custom Handlers (Primarily `js/main.js` included in `footer.php`):**
    *   **Add-to-Cart:** Global handler for `.add-to-cart`. **Reads CSRF token *strictly* from `#csrf-token-value`**. Sends AJAX POST. Handles JSON response. **Functional.**
    *   **Newsletter:** Handlers for `#newsletter-form` / `#newsletter-form-footer`. **Reads CSRF token strictly from `#csrf-token-value`**. Sends AJAX POST. Handles response.
*   **Page-Specific JS:** Mobile menu, Cart page AJAX, Product Detail AJAX/UI, Products page filter/sort triggers. **All POST actions depend on `#csrf-token-value`.**
*   **Standardization:** `showFlashMessage` helper used. Global handlers in `footer.php`. **Critical Dependency:** AJAX POST functionality relies on `#csrf-token-value` being present and correctly populated.

---

## 7. Key Pages & Components

*(Updated status for Cart and Products pages)*

### 7.1 Home/Landing Page (views/home.php)

*   Displays standard sections. Consistent product cards. Functional Add-to-Cart. **Requires CSRF token output (`#csrf-token-value`)**.

### 7.2 Header and Navigation (views/layout/header.php)

*   Standard header. Dynamic session info. Includes assets. Mobile menu functional.

### 7.3 Footer and Newsletter (views/layout/footer.php)

*   Standard footer. JS init. **Global AJAX handlers read CSRF from `#csrf-token-value`**.

### 7.4 Product Grid & Cards

*   Consistent styling. Functional Add-to-Cart (depends on CSRF token).

### 7.5 Shopping Cart (views/cart.php)

*   **Functional (Requires View Fix).** Displays items/totals. JS handles updates/removal via AJAX. **Requires CSRF token output (`#csrf-token-value`)**. Uses inconsistent storage (Session/DB).

### 7.6 Product Detail Page (views/product_detail.php)

*   Enhanced layout. Functional AJAX Add-to-Cart. **Requires CSRF token output (`#csrf-token-value`)**.

### 7.7 Products Page (views/products.php)

*   Product list, filters, sorting. **Pagination functional.** Functional Add-to-Cart. **Requires CSRF token output (`#csrf-token-value`)**.

### 7.8 Quiz Flow & Personalization

*   Standard quiz flow. Forms require CSRF token.

---

## 8. Backend Logic & Core PHP Components

*(Updated status/notes for Product/Cart Controller and Product Model)*

### 8.1 Includes: Shared Logic (includes/)

*   Foundational files: `auth.php`, `db.php`, `SecurityMiddleware.php`, `ErrorHandler.php`, `EmailService.php`.

### 8.2 Controllers: Business Logic Layer (controllers/ & BaseController.php)

*   `BaseController.php`: Shared methods. **`getCsrfToken()` is crucial.** **`validateRateLimit()` needs consistent usage.**
*   `ProductController.php`: Handles product views. **Pagination logic confirmed working.** Must pass CSRF token.
*   `CartController.php`: **`showCart()` renders main view correctly.** Handles AJAX. Cart storage inconsistency remains.
*   Specific Controllers: Extend `BaseController`. **Must follow CSRF token pattern.** Use `validateRateLimit()` (**standardization needed**).

### 8.3 Database Abstraction (includes/db.php & models/)

*   Connection via `$pdo`.
*   Interaction via Models/PDO using **Prepared Statements**. `Product::getAllCategories` uses `DISTINCT`. `Product::getFiltered` **pagination logic confirmed working**.

### 8.4 Security Middleware & Error Handling

*   `SecurityMiddleware.php`: Applies security settings, validation, **CSRF generation/validation**. `preventSQLInjection` **should be removed**.
*   `ErrorHandler.php`: Global handling. **"Headers Already Sent" warning identified** when errors occur during view rendering; fix recommended (see Section 10.7).

### 8.5 Session, Auth, and User Flow

*   Secure session settings applied. Session integrity checks in place.
*   Auth flow via `AccountController`. Rate limiting inconsistent.
*   **Cart data storage inconsistency** (Session vs. DB) needs addressing.

---

## 9. Database Design

*(No changes from v8.0, recommendations remain)*

### 9.1 Entity-Relationship Model (Conceptual)

Standard e-commerce relationships.

### 9.2 Core Tables (from schema.sql)

Core tables as defined in `the_scent_schema.sql.txt`.

### 9.3 Schema Considerations & Recommendations

*   `products` table uses `image` field for primary image, not `image_url`.
*   **Cart Table Usage:** `cart_items` table exists but `$_SESSION['cart']` is primary for guests. **Recommendation:** Standardize on DB cart for logged-in users.
*   **Category Data:** Potential duplicate names. Rely on `DISTINCT` query or clean data.

### 9.4 Data Flow Examples

*(Updated based on current findings)*

*   **Add to Cart:** Functional AJAX flow using CSRF token from `#csrf-token-value`.
*   **View Cart Page:** Functional flow, renders `views/cart.php`. **Requires view fix (`image` key).**
*   **View Product List (Page 2):** **Functional**. `ProductController` calls `ProductModel::getFiltered` with correct offset/limit using explicit PDO binding. View renders correct product set and pagination UI.

---

## 10. Security Considerations & Best Practices

*(Updated based on findings and previous review)*

### 10.1 Input Sanitization & Validation

*   Handled via `SecurityMiddleware::validateInput()` and `BaseController`.

### 10.2 Session Management

*   Secure settings applied via `config.php`. Integrity checks implemented.

### 10.3 CSRF Protection (Implemented - Strict Pattern Required)

*   **Mechanism:** Synchronizer Token Pattern fully implemented and enforced for POST requests.
*   **Status:** Functional and mandatory for security. Pattern: Controller (`getCsrfToken`) -> View (`#csrf-token-value` output) -> JS (read from hidden input) -> Server (`validateCSRF` in `index.php`).

### 10.4 Security Headers & CSP Standardization

*   Standard headers applied globally.
*   **Current CSP:** `default-src 'self'; script-src 'self' https://js.stripe.com; style-src 'self'; frame-src https://js.stripe.com; img-src 'self' data: https:; connect-src 'self' https://api.stripe.com` (Includes Stripe example, may need adjustment).
*   **Recommendation:** Review and tighten CSP further (remove `'unsafe-inline'`, `'unsafe-eval'` if possible by refactoring JS/CSS).

### 10.5 Rate Limiting (Standardization Recommended)

*   Mechanism exists (`BaseController::validateRateLimit`) using APCu.
*   **Status:** Usage inconsistent. Reliability depends on APCu availability.
*   **Recommendation:** Standardize usage across controllers. Ensure cache backend reliability or implement robust fallback.

### 10.6 File Uploads & Permissions

*   Validation logic exists (`BaseController`, `SecurityMiddleware`). Secure handling required if used.

### 10.7 Audit Logging & Error Handling

*   `ErrorHandler.php` provides global handling. `BaseController` provides logging methods.
*   **"Headers Already Sent" Issue:** Identified when errors occur during view rendering. **Recommendation:** Fix by making `views/error.php` self-contained (no header/footer includes) or use output buffering in `ErrorHandler`.
*   **Recommendation:** Consistent use of logging methods. Remove debug `error_log` calls.

### 10.8 SQL Injection Prevention

*   **Primary Defense: PDO Prepared Statements.** Consistently used and effective.
*   **Recommendation:** Remove commented-out `preventSQLInjection` from `SecurityMiddleware.php`.

---

## 11. Extensibility & Onboarding

*(Updated testing checklist)*

### 11.1 Adding Features, Pages, or Controllers

*   Follow pattern: Controller -> View -> `index.php` route. **Implement strict CSRF token pattern** for POST actions.

### 11.2 Adding Products, Categories, and Quiz Questions

*   Via DB or future Admin UI. Ensure schema fields populated. Address category duplicates.

### 11.3 Developer Onboarding Checklist

1.  Setup LAMP/LEMP, enable `mod_rewrite`.
2.  Clone repo.
3.  Setup DB, import schema.
4.  Configure `config.php`.
5.  Set file permissions (`logs/`, `config.php`).
6.  Configure Apache VirtualHost.
7.  Browse site, check server logs.
8.  **Verify CSRF implementation:** Inspect views for `#csrf-token-value`, test POST actions.
9.  **Verify Core Functionality:** Test Add-to-Cart. **Test Cart Page (apply view fix if needed).** **Test Product List Pagination (confirm working).** Test Category Filters. Test cart updates/removal. Test newsletter signup. Test login/registration.

### 11.4 Testing & Debugging Notes

*   Use browser dev tools, application logs, server logs.
*   **Debug Cart View:** Ensure `views/cart.php` uses `$item['product']['image']`.
*   **Key Areas to Verify:** Rate limiting implementation, Cart storage consistency, CSRF token flow, AJAX responses, Error handling flow.

---

## 12. Future Enhancements & Recommendations

*   **Standardize Rate Limiting:** Implement consistently using `BaseController::validateRateLimit`. Ensure backend reliability. (**High Priority**)
*   **Database Cart:** Standardize cart storage on DB for logged-in users. (**High Priority**)
*   **Tighten CSP:** Remove `'unsafe-inline'`/`'unsafe-eval'` if possible. (**Medium Priority**)
*   **Fix "Headers Already Sent":** Implement recommended fix in `ErrorHandler`. (**Medium Priority**)
*   **Remove Dead Code:** Delete commented `preventSQLInjection`. (**Low Priority**)
*   **Code Quality:** Implement Autoloader (Composer), Dependency Management (Composer), Routing Component, Templating Engine, Environment Variables (.env), DB Migrations, Unit Tests. (**Ongoing/Future**)
*   **Features:** Payment Gateway, Full Admin Panel, Advanced Search/Filtering. (**Future**)

---

## 13. Appendices

### A. Key File Summaries

| File/Folder                 | Purpose                                                                                                     | Status Notes                                                                                               |
| :-------------------------- | :---------------------------------------------------------------------------------------------------------- | :--------------------------------------------------------------------------------------------------------- |
| `index.php`                 | Entry point, routing (**cart fixed**), core includes, **auto POST CSRF validation**, dispatch.             | OK                                                                                                         |
| `config.php`                | DB credentials, App/Security settings (**CSP needs review**, **Rate Limits need usage review**), API keys         | OK. CSP tightening recommended. Rate limit config needs consistent usage. Move secrets recommended.      |
| `includes/SecurityMiddleware.php` | Static helpers: `apply()`, `validateInput()`, `validateCSRF()`, `generateCSRFToken()`                     | OK. `preventSQLInjection` removal recommended.                                                           |
| `controllers/BaseController.php` | Abstract base: `$db`, helpers, validation, auth checks, `getCsrfToken()`, `validateRateLimit`          | OK. Rate limiting usage needs standardization.                                                           |
| `controllers/CartController.php`| Handles cart logic. **`showCart()` renders main view correctly.** Handles AJAX.                         | Functional. Cart storage consistency recommended.                                                        |
| `controllers/ProductController.php` | Product listing/detail. **Pagination confirmed working.**                                               | OK. Requires consistent CSRF token passing.                                                              |
| `models/Product.php`              | Entity DB logic (**PDO Prepared Statements**). **Pagination logic confirmed working.**                      | OK.                                                                                                      |
| `views/layout/header.php`   | Header, navigation, assets                                                                                  | OK.                                                                                                      |
| `views/*.php`               | HTML/PHP templates, **must output CSRF token into `#csrf-token-value`**.                                    | Requires consistent token output. `cart.php` needs `image` key fix.                                      |
| `views/layout/footer.php`   | Footer, JS init, **global AJAX handlers strictly reading CSRF token from `#csrf-token-value`**               | OK. JS handler works correctly.                                                                            |
| `views/products.php`        | Product list, filters, sorting, pagination UI. **Pagination functional.** Requires CSRF token output.     | OK.                                                                                                      |
| `views/cart.php`            | Cart view. **Functional**, **Requires view fix (`image` key)**. Requires CSRF token output.                  | Needs View Fix. Cart storage consistency recommended.                                                    |
| `includes/ErrorHandler.php` | Global error handling.                                                                                      | **"Headers Already Sent" issue identified**, fix recommended.                                            |

### B. Glossary

(Standard terms: MVC, CSRF, XSS, SQLi, PDO, AJAX, CDN, CSP, Rate Limiting, Prepared Statements, Synchronizer Token Pattern, APCu)

### C. Code Snippets and Patterns (CSRF Implementation & Error Handling Fix Recommendation)

#### Correct & Required CSRF Token Implementation Pattern

*(Remains the same as v8.0 - Ensure pattern is followed)*

1.  **Controller (Rendering View):** `$csrfToken = $this->getCsrfToken();` -> Pass `$csrfToken` to view data.
2.  **View:** Output `<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">`. Also include `<input type="hidden" name="csrf_token" value="...">` in standard forms.
3.  **JavaScript:** Read token *only* from `#csrf-token-value` for AJAX POST requests.
4.  **Server (`index.php`):** Automatic POST validation via `SecurityMiddleware::validateCSRF()`.

#### Recommended Error Handling Fix ("Headers Already Sent")

*   **File:** `ErrorHandler.php` (or modify `views/error.php`)
*   **Recommendation:** Make `views/error.php` self-contained (no header/footer includes) **OR** use output buffering in `ErrorHandler::handleException`.

    **Option B (Output Buffering):**
    ```php
    // Inside ErrorHandler::handleException (Conceptual)
    public static function handleException(Throwable $exception): void {
        // ... logging ...
        if (!headers_sent()) { // Check before setting code
             http_response_code(500);
             header('Content-Type: text/html; charset=UTF-8'); // Set content type early
        }

        ob_start(); // Start buffering AFTER potentially setting headers
        require_once ROOT_PATH . '/views/error.php'; // Include view (can include header/footer now)
        echo ob_get_clean(); // Output buffered content
        exit();
    }
    ```

---
https://drive.google.com/file/d/1ASIDYu7u9yJmZBkfpI4kiDo4xWfN5VfN/view?usp=sharing, https://drive.google.com/file/d/1MGczMz59axRzd1s1gYuEN0-WT-v34HGD/view?usp=sharing, https://drive.google.com/file/d/1Mjm2LJH6nEPGOYAD5W8fLTEL4IrLERJQ/view?usp=sharing, https://drive.google.com/file/d/1Ncq8ecY9bbLOJg9GIGCseBurMkVGWGJG/view?usp=sharing, https://drive.google.com/file/d/1TYJ43llThkbZyPJ6cuBc0jkw9mf4omYh/view?usp=sharing, https://drive.google.com/file/d/1VvoNkgfOl0ZnWbEoVs_HeblgCG0tNWEV/view?usp=sharing, https://drive.google.com/file/d/1WN6or43vKYFYOng7ek78qr0Hllo30i2D/view?usp=sharing, https://drive.google.com/file/d/1XpBYJDtRvcJ9YOtTKDXTmvbvM3EG-AZX/view?usp=sharing, https://drive.google.com/file/d/1ZY517G8nAnwggDNPhlJrO5FdHubTvFw9/view?usp=sharing, https://drive.google.com/file/d/1ZeYUobe9OCV0MDAgz05dQwqQN54Xvrw5/view?usp=sharing, https://drive.google.com/file/d/1_3DB9P_baKm1u1fe-dcaUrXHGNMR7Jvz/view?usp=sharing, https://drive.google.com/file/d/1d09iR34p8zqWXY6u8A74UY1DehPuhiM7/view?usp=sharing, https://drive.google.com/file/d/1esRbj9gfhAfxLxobN_S56xs5rc7EMVog/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221g-dyxEQuJubUlN99tHeJwJaAItsZRSlC%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1gLb6YB6CBoIhBA2NlwdBIS3uk_yogmsC/view?usp=sharing, https://drive.google.com/file/d/1rmEfmhukIUkEvb-PArYMhsaGKSbBOr41/view?usp=sharing, https://drive.google.com/file/d/1s0Y1vUXPHTnYzBOxmf_Y6pP3GLdHMJt0/view?usp=sharing, https://drive.google.com/file/d/1zL9RaqOGNusURuCmrKovSZ7rv48aJeCR/view?usp=sharing
