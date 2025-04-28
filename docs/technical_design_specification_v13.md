# The Scent – Technical Design Specification (v10.0)

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

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document (**v10.0**) serves as the updated technical design specification, reflecting the project's current state after incorporating the latest fixes and analysis.

This version documents the **resolution of the fatal error in `AccountController`** (related to property visibility), which previously broke the login page and associated flows (like checkout redirection). It confirms that the **product list pagination is functional**. It acknowledges the **updated UI structure in `views/cart.php`** and confirms its general functionality. It clarifies the persistent **inconsistency in cart storage mechanisms** (Session vs. DB). Add-to-Cart AJAX functionality remains operational following strict CSRF token handling patterns. Recommendations for **standardizing rate limiting**, **tightening the Content Security Policy (CSP)**, and **fixing the "Headers Already Sent" error handling quirk** remain pertinent.

This document aims to offer deep insight into the system’s structure, logic, and flow, serving as a comprehensive onboarding and reference guide for the current state of the application, including known issues and recommended next steps.

---

## 2. Project Philosophy & Goals

*   **Security First:** All data input and user interactions are validated and sanitized. Strong session management and CSRF protection mechanisms are implemented and strictly required. **Strict adherence to the documented CSRF token handling pattern (Controller->View->HiddenInput `#csrf-token-value`->JS->Server Validation) is mandatory and implemented for all functional POST/AJAX operations.**
*   **Simplicity & Maintainability:** Clear, modular code structure. Direct `require_once` usage in `index.php` provides transparency but lacks autoloading benefits. Consistent coding patterns are enforced, particularly for security features like CSRF handling.
*   **Extensibility:** Architecture allows adding new features, pages, controllers, or views, requiring manual includes but offering straightforward extension points. New features involving POST must follow the established CSRF pattern.
*   **Performance:** Direct routing is potentially fast. Relies on PDO prepared statements. CDN usage for frontend libraries impacts external dependencies. Caching mechanisms (e.g., APCu for rate limiting) are recommended where applicable.
*   **Modern User Experience:** Responsive design, smooth animations (AOS.js, Particles.js), and AJAX interactions (cart updates/removal, newsletter, Add-to-Cart) provide a seamless interface. UI consistency across product displays is maintained. **Add-to-Cart functionality is operational.** **Shopping Cart display (with updated UI) is functional.** **Product list pagination is functional.** **Login/Registration flow is functional** (post `AccountController` fix).
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
[Controller]  (e.g., ProductController, CartController, AccountController (Fixed))
   |           (Included via require_once *within* index.php's switch case)
   |           (Instantiated, passed $pdo connection)
   |           (Extends BaseController)
   |           (*** Action method MUST call $csrfToken = $this->getCsrfToken() IF rendering a view that needs subsequent CSRF protection ***)
   |           (*** MUST pass $csrfToken to the View data ***)
   |
   | (Executes action method: business logic, DB access via Models/PDO, Rate Limiting Check)
   | (e.g., CartController::showCart() handles the main cart view request)
   v
[Model / DB Layer] (e.g., models/Product.php, models/Cart.php, models/User.php, direct PDO in includes/db.php)
   |
   | (Prepare/execute SQL queries, fetch data using PDO Prepared Statements. Product pagination logic confirmed working.)
   v
[View / Response]
   |--> [View File] (e.g., views/cart.php (New UI), views/products.php)
   |       (Included via require_once from controller action)
   |       (Generates HTML using PHP variables passed from controller, includes layout partials)
   |       (*** MUST output $csrfToken into <input type="hidden" id="csrf-token-value" ...> IF subsequent CSRF protection needed ***)
   |       (Output MUST use htmlspecialchars())
   |
   |--> [JSON Response] (via BaseController::jsonResponse for AJAX)
   |       (e.g., for cart add/update/remove, newsletter subscribe, login/register)
   |       (Clean JSON output)
   |
   |--> [Redirect] (via BaseController::redirect or header())
   |
   v
[Browser/Client] <-- Renders HTML, applies CSS (Tailwind CDN, custom)
                     Executes JS (libraries, custom handlers)
                     (*** JS MUST read CSRF token STRICTLY from #csrf-token-value for AJAX POSTs ***)
```

### 3.2 Request-Response Life Cycle (Example: Cart Page)

1.  **Request Initiation:** User accesses `index.php?page=cart`.
2.  **.htaccess Rewrite:** Standard rewrite to `/index.php`.
3.  **Initialization (`/index.php`):** Core files loaded, `$pdo` connected, `ErrorHandler` initialized, `SecurityMiddleware::apply` sets headers/session.
4.  **Routing (`/index.php`):** `$page` ('cart'), `$action` (null) extracted.
5.  **CSRF Check (`/index.php`):** (Not applicable for GET).
6.  **Controller/View Dispatch (`/index.php` `switch ($page)`):**
    *   Case `cart`: `CartController.php` included, `$controller = new CartController($pdo)` instantiated.
    *   `$controller->showCart();` executed.
7.  **Controller Action (`CartController::showCart()`):**
    *   Fetches cart items (using session/DB based on login status).
    *   Calculates `$total`.
    *   Gets `$csrfToken = $this->getCsrfToken();`.
    *   Includes `views/cart.php`, passing `$cartItems`, `$total`, `$csrfToken`.
8.  **View Rendering (`views/cart.php`):**
    *   Includes `layout/header.php`.
    *   Outputs CSRF token into `#csrf-token-value`.
    *   Renders items using `$item['product']['image']` (correct key). **Renders using the updated two-column grid layout.** Uses `htmlspecialchars()`.
    *   Includes `layout/footer.php`.
9.  **Response Transmission:** Server sends the complete HTML page.
10. **Client-Side Execution:** Browser renders the cart page with the new UI. JS attaches listeners for quantity changes, removal, etc.

---

## 4. Directory & File Structure

### 4.1 Folder Map

*(No changes)*

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
|   |-- AccountController.php # Assumed fixed (visibility error resolved)
|   |-- QuizController.php
|   |-- ... (Coupon, Inventory, Newsletter, Payment, Tax)
|-- models/                # Data representation / DB interaction (using PDO Prepared Statements)
|   |-- Product.php
|   |-- User.php
|   |-- Order.php
|   |-- Quiz.php
|-- views/                 # HTML templates (PHP files)
|   |-- home.php           # Landing page - Requires CSRF token output for Add-to-Cart
|   |-- products.php       # Product list - Requires CSRF token output for Add-to-Cart. Pagination functional.
|   |-- product_detail.php # Product detail - Requires CSRF token output for Add-to-Cart
|   |-- cart.php           # Cart view - Functional, uses updated grid UI. Requires CSRF token output for AJAX.
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
|-- technical_design_specification.md # (This document v10)
|-- suggested_improvements_and_fixes.md # (Analysis document v1.0 - May be outdated)
|-- the_scent_schema.sql.txt # Database schema
|-- ... (other docs, HTML output files)
```

### 4.2 Key Files Explained

*   **index.php**: Central orchestrator. **Auto POST CSRF validation**. Dispatches to controllers. Routing logic is sound.
*   **config.php**: Configuration store. **CSP needs review**. Rate limit settings used inconsistently.
*   **includes/SecurityMiddleware.php**: Security helpers. `validateCSRF()` enforces token check. `preventSQLInjection` **should be removed**.
*   **controllers/BaseController.php**: Abstract base. Provides shared helpers. `getCsrfToken()` used by controllers. `validateRateLimit()` **needs consistent usage**.
*   **controllers/AccountController.php**: Handles user auth/profile. **Fatal error fixed** (assuming visibility change applied). Login/Register flow relies on `jsonResponse`.
*   **controllers/ProductController.php**: Handles product views. **Pagination logic confirmed working**. Requires CSRF token passing.
*   **controllers/CartController.php**: Handles cart logic. `showCart()` renders main view. Handles AJAX. **Cart storage inconsistency remains**.
*   **models/Product.php**: DB logic via **PDO Prepared Statements**. **Pagination logic confirmed working**.
*   **views/layout/header.php**: Standard header.
*   **views/layout/footer.php**: Standard footer. **Global AJAX handlers read CSRF from `#csrf-token-value`**.
*   **views/*.php**: Templates. **Must output CSRF token correctly**. Use `htmlspecialchars()`.
*   **views/products.php**: Product list page. **Pagination functional.** Requires CSRF token output.
*   **views/cart.php**: Cart view. **Functional, uses updated grid layout**. Requires CSRF token output.
*   **views/product_detail.php**: Product detail. AJAX functional. Requires CSRF token output.
*   **views/login.php, views/register.php**: Functional (post `AccountController` fix). Rely on AJAX handled by `main.js`. Require CSRF token output.

---

## 5. Routing and Application Flow

### 5.1 URL Routing via .htaccess

*(No changes)*

*   Mechanism: Apache `mod_rewrite` routes most non-file/directory requests to `/index.php`. Standard configuration.

### 5.2 index.php: The Application Entry Point

*(No changes)*

*   Role: Front Controller/Router.
*   Process: Initializes core components, extracts/validates `$page`/`$action`, reads `page_num` for pagination, **automatically performs CSRF check on ALL POST requests via `SecurityMiddleware::validateCSRF()`**, dispatches to controllers via `switch($page)`. Routing logic is verified and functional.

### 5.3 Controller Dispatch & Action Flow

*(Emphasizes CSRF pattern and rate limiting)*

*   Controllers included/instantiated by `index.php`. Extend `BaseController`.
*   Execute business logic, use Models/PDO for data. Read parameters like `page_num`.
*   **Required CSRF Pattern:** Controllers rendering views that will initiate POST/AJAX requests **must** call `$csrfToken = $this->getCsrfToken();` and pass this token to the view data.
*   Pass data (including `$csrfToken`, pagination data) to views.
*   Check rate limits using `$this->validateRateLimit()` on sensitive actions (**standardization needed**).
*   Terminate via view inclusion (`require_once`), `jsonResponse`, or `redirect`.

### 5.4 Views: Templating and Rendering

*(Acknowledges updated cart UI)*

*   PHP files in `views/` mixing HTML and PHP.
*   Include layout partials (`header.php`, `footer.php`).
*   **Required CSRF Pattern:** Where subsequent forms or AJAX POSTs are initiated, the view **must** output the passed `$csrfToken` into a dedicated hidden input: `<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">`. Standard forms should *also* include `<input type="hidden" name="csrf_token" value="...">`.
*   Use `htmlspecialchars()` for all dynamic output.
*   Styling via `/css/style.css` and Tailwind CDN.
*   JS initialized/handled primarily in `footer.php`, **strictly relying on `#csrf-token-value` for CSRF tokens in AJAX.**
*   Pagination UI rendered in `views/products.php` based on data passed from the controller (**Functional**).
*   `views/cart.php` renders using the **updated two-column grid layout** and is functional.

---

## 6. Frontend Architecture

*(No changes)*

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
    *   **Login/Register:** Handled by `initLoginPage`/`initRegisterPage`. Read CSRF token, send AJAX POST, handle JSON response (success/error/redirect). **Functional** (post `AccountController` fix).
    *   **Cart Updates/Removal:** Handled by `initCartPage`. Read CSRF token, send AJAX POST, handle JSON response, update UI. **Functional**.
*   **Page-Specific JS:** Mobile menu, Product Detail UI (quantity, tabs, image gallery), Products page filter/sort triggers. **All POST actions depend on `#csrf-token-value`.**
*   **Standardization:** `showFlashMessage` helper used. Global handlers in `footer.php`. **Critical Dependency:** AJAX POST functionality relies on `#csrf-token-value` being present and correctly populated.

---

## 7. Key Pages & Components

*(Acknowledges fixed auth flow and updated cart UI)*

### 7.1 Home/Landing Page (views/home.php)

*   Displays standard sections. Consistent product cards. Functional Add-to-Cart. **Requires CSRF token output (`#csrf-token-value`)**.

### 7.2 Header and Navigation (views/layout/header.php)

*   Standard header. Dynamic session info. Includes assets. Mobile menu functional.

### 7.3 Footer and Newsletter (views/layout/footer.php)

*   Standard footer. JS init. **Global AJAX handlers read CSRF from `#csrf-token-value`**.

### 7.4 Product Grid & Cards

*   Consistent styling. Functional Add-to-Cart (depends on CSRF token).

### 7.5 Shopping Cart (views/cart.php)

*   **Functional.** Displays items/totals using the **updated two-column grid layout**. JS handles updates/removal via AJAX. **Requires CSRF token output (`#csrf-token-value`)**. Uses inconsistent storage (Session/DB).

### 7.6 Product Detail Page (views/product_detail.php)

*   Enhanced layout. Functional AJAX Add-to-Cart. **Requires CSRF token output (`#csrf-token-value`)**.

### 7.7 Products Page (views/products.php)

*   Product list, filters, sorting. **Pagination functional.** Functional Add-to-Cart. **Requires CSRF token output (`#csrf-token-value`)**.

### 7.8 Quiz Flow & Personalization

*   Standard quiz flow. Forms require CSRF token.

---

## 8. Backend Logic & Core PHP Components

*(Notes updated based on fixes and confirmations)*

### 8.1 Includes: Shared Logic (includes/)

*   Foundational files: `auth.php`, `db.php`, `SecurityMiddleware.php`, `ErrorHandler.php`, `EmailService.php`.

### 8.2 Controllers: Business Logic Layer (controllers/ & BaseController.php)

*   `BaseController.php`: Shared methods. **`getCsrfToken()` is crucial.** **`validateRateLimit()` needs consistent usage.**
*   `AccountController.php`: Handles user auth/profile. **Fatal error fixed**. Login/Register flow functional via `jsonResponse`.
*   `ProductController.php`: Handles product views. **Pagination logic confirmed working.** Must pass CSRF token.
*   `CartController.php`: Handles cart logic. Renders cart view correctly. Handles AJAX. **Cart storage inconsistency remains**.
*   Specific Controllers: Extend `BaseController`. **Must follow CSRF token pattern.** Use `validateRateLimit()` (**standardization needed**).

### 8.3 Database Abstraction (includes/db.php & models/)

*   Connection via `$pdo` in `db.php`.
*   Interaction via Models/PDO using **Prepared Statements**. `Product::getFiltered` **pagination logic confirmed working**.

### 8.4 Security Middleware & Error Handling

*   `SecurityMiddleware.php`: Applies security settings, validation, **CSRF generation/validation**. `preventSQLInjection` **should be removed**.
*   `ErrorHandler.php`: Global handling. **"Headers Already Sent" warning identified** when errors occur during view rendering; fix recommended (see Section 10.7).

### 8.5 Session, Auth, and User Flow

*   Secure session settings applied. Session integrity checks implemented.
*   Auth flow via `AccountController` functional (post-fix). Rate limiting inconsistent.
*   **Cart data storage inconsistency** (Session vs. DB) needs addressing.

---

## 9. Database Design

*(No changes)*

### 9.1 Entity-Relationship Model (Conceptual)

Standard e-commerce relationships: Users -> Orders -> OrderItems <- Products; Users -> CartItems <- Products; Products -> Categories.

### 9.2 Core Tables (from schema.sql)

Core tables as defined in `the_scent_schema.sql.txt`: `users`, `products`, `categories`, `orders`, `order_items`, `cart_items`, `quiz_results`, `newsletter_subscribers`, `audit_log`, etc.

### 9.3 Schema Considerations & Recommendations

*   `products` table uses `image` field for primary image. JSON fields (`gallery_images`, `benefits`) used.
*   **Cart Table Usage:** `cart_items` table exists but `$_SESSION['cart']` is primary for guests. **Recommendation:** Standardize on DB cart for logged-in users.
*   **Category Data:** Potential duplicate names. Relies on `DISTINCT` query or data cleanup.

### 9.4 Data Flow Examples

*(Updated based on current findings)*

*   **Add to Cart:** Functional AJAX flow using CSRF token from `#csrf-token-value`.
*   **View Cart Page:** Functional flow, renders `views/cart.php` with updated UI.
*   **View Product List (Page 2):** **Functional**. `ProductController` calls `ProductModel::getFiltered` with correct offset/limit. View renders correct product set and pagination UI.
*   **Login:** Functional AJAX flow using CSRF token from `#csrf-token-value`. Returns JSON with redirect URL.

---

## 10. Security Considerations & Best Practices

*(Recommendations reiterated)*

### 10.1 Input Sanitization & Validation

*   Handled via `SecurityMiddleware::validateInput()` and `BaseController`. Consistent usage observed.

### 10.2 Session Management

*   Secure settings applied via `config.php`. Integrity checks implemented. Periodic regeneration observed.

### 10.3 CSRF Protection (Implemented - Strict Pattern Required)

*   **Mechanism:** Synchronizer Token Pattern fully implemented and enforced for POST requests via `index.php` and `SecurityMiddleware::validateCSRF()`.
*   **Status:** Functional and mandatory. Pattern: Controller (`getCsrfToken`) -> View (`#csrf-token-value` output) -> JS (read from hidden input) -> Server (`validateCSRF`).

### 10.4 Security Headers & CSP Standardization

*   Standard headers applied globally.
*   **Current CSP:** Needs review and potential tightening based on actual third-party requirements (Stripe, etc.). Example in `config.php`.
*   **Recommendation:** Review and tighten CSP further (remove `'unsafe-inline'`, `'unsafe-eval'` if possible by refactoring JS/CSS).

### 10.5 Rate Limiting (Standardization Recommended)

*   Mechanism exists (`BaseController::validateRateLimit`) using APCu.
*   **Status:** Usage inconsistent across controllers/actions. Reliability depends on APCu availability.
*   **Recommendation:** Standardize usage across sensitive controller actions (login, register, password reset, checkout submission, potentially cart updates). Ensure cache backend reliability or implement robust fallback. Add specific keys for different actions.

### 10.6 File Uploads & Permissions

*   Validation logic exists (`BaseController`, `SecurityMiddleware`). Secure handling (storage outside web root, proper validation) required if used.

### 10.7 Audit Logging & Error Handling

*   `ErrorHandler.php` provides global handling. `BaseController` provides logging methods (`logAuditTrail`, `logSecurityEvent`). Usage observed.
*   **"Headers Already Sent" Issue:** Identified when errors occur during view rendering. **Recommendation:** Fix by making `views/error.php` self-contained (no header/footer includes) or use output buffering in `ErrorHandler`.
*   **Recommendation:** Consistent use of logging methods. Remove debug `error_log` calls.

### 10.8 SQL Injection Prevention

*   **Primary Defense: PDO Prepared Statements.** Consistently used and effective.
*   **Recommendation:** Remove commented-out `preventSQLInjection` from `SecurityMiddleware.php`.

---

## 11. Extensibility & Onboarding

*(Checklist updated)*

### 11.1 Adding Features, Pages, or Controllers

*   Follow pattern: Controller -> View -> `index.php` route. **Implement strict CSRF token pattern** for POST actions. Extend `BaseController`.

### 11.2 Adding Products, Categories, and Quiz Questions

*   Via DB or future Admin UI. Ensure schema fields populated. Address category duplicates.

### 11.3 Developer Onboarding Checklist

1.  Setup LAMP/LEMP, enable `mod_rewrite`.
2.  Clone repo.
3.  Setup DB, import schema.
4.  Configure `config.php`.
5.  **Apply `AccountController` fix** (remove `private $emailService;` line).
6.  Set file permissions (`logs/` writable, `config.php` restricted).
7.  Configure Apache VirtualHost.
8.  Browse site, check server logs for errors.
9.  **Verify CSRF implementation:** Inspect views for `#csrf-token-value`, test POST actions (Add-to-Cart, Login, Register, Cart Update/Remove, Newsletter).
10. **Verify Core Functionality:** Test Add-to-Cart. Test Cart Page UI and functionality. **Test Product List Pagination (page 1 & 2 links)**. Test Category Filters. Test cart updates/removal. Test newsletter signup. Test login/registration. Test password reset flow.

### 11.4 Testing & Debugging Notes

*   Use browser dev tools (network, console), application logs (`logs/`), server logs (`apache_logs/`).
*   **Verify `AccountController` Fix:** Ensure login/registration pages load and function correctly.
*   **Key Areas to Verify:** Rate limiting implementation (if standardized), Cart storage consistency (logged-in vs guest), CSRF token flow, AJAX responses, Error handling flow ("Headers Already Sent" fix verification). Test pagination links on product list. Verify cart UI elements (grid, sticky summary).

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

| File/Folder                 | Purpose                                                                                                     | Status Notes                                                                                                     |
| :-------------------------- | :---------------------------------------------------------------------------------------------------------- | :--------------------------------------------------------------------------------------------------------------- |
| `index.php`                 | Entry point, routing, core includes, **auto POST CSRF validation**, dispatch.                               | OK                                                                                                               |
| `config.php`                | DB credentials, App/Security settings (**CSP needs review**, **Rate Limits need usage review**), API keys         | OK. CSP tightening recommended. Rate limit config needs consistent usage. Move secrets recommended.            |
| `includes/SecurityMiddleware.php` | Static helpers: `apply()`, `validateInput()`, `validateCSRF()`, `generateCSRFToken()`                     | OK. `preventSQLInjection` removal recommended.                                                                 |
| `controllers/BaseController.php` | Abstract base: `$db`, helpers, validation, auth checks, `getCsrfToken()`, `validateRateLimit`          | OK. Rate limiting usage needs standardization.                                                                 |
| `controllers/AccountController.php` | User auth/profile logic. AJAX login/register.                                                           | **FIXED** (Fatal Error resolved). Functional.                                                                  |
| `controllers/CartController.php`| Handles cart logic. Renders view. Handles AJAX.                                                             | Functional. **Cart storage consistency recommended.**                                                          |
| `controllers/ProductController.php` | Product listing/detail.                                                                               | **Pagination functional.** Requires consistent CSRF token passing.                                             |
| `models/Product.php`              | Entity DB logic (**PDO Prepared Statements**).                                                              | **Pagination logic functional.** OK.                                                                           |
| `views/layout/header.php`   | Header, navigation, assets                                                                                  | OK.                                                                                                            |
| `views/*.php`               | HTML/PHP templates, **must output CSRF token into `#csrf-token-value`**.                                    | Requires consistent token output.                                                                              |
| `views/layout/footer.php`   | Footer, JS init, **global AJAX handlers strictly reading CSRF token from `#csrf-token-value`**               | OK. JS handler works correctly.                                                                                |
| `views/products.php`        | Product list, filters, sorting, pagination UI. Requires CSRF token output.                                | **Pagination functional.** OK.                                                                                 |
| `views/cart.php`            | Cart view. **Functional**. Uses updated **grid UI**. Requires CSRF token output.                                | **Updated UI acknowledged.** Cart storage consistency recommended.                                             |
| `includes/ErrorHandler.php` | Global error handling.                                                                                      | **"Headers Already Sent" issue identified**, fix recommended.                                                  |

### B. Glossary

(Standard terms: MVC, CSRF, XSS, SQLi, PDO, AJAX, CDN, CSP, Rate Limiting, Prepared Statements, Synchronizer Token Pattern, APCu)

### C. Code Snippets and Patterns (CSRF Implementation & Error Handling Fix Recommendation)

#### Correct & Required CSRF Token Implementation Pattern

*(Remains the same - Ensure pattern is followed)*

1.  **Controller (Rendering View):** `$csrfToken = $this->getCsrfToken();` -> Pass `$csrfToken` to view data.
2.  **View:** Output `<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">`. Also include `<input type="hidden" name="csrf_token" value="...">` in standard forms.
3.  **JavaScript:** Read token *only* from `#csrf-token-value` for AJAX POST requests.
4.  **Server (`index.php`):** Automatic POST validation via `SecurityMiddleware::validateCSRF()`.

#### Recommended Error Handling Fix ("Headers Already Sent")

*   **File:** `ErrorHandler.php` (or modify `views/error.php`)
*   **Recommendation:** Make `views/error.php` self-contained (no header/footer includes) **OR** use output buffering in `ErrorHandler::handleException` and `ErrorHandler::handleError` (where appropriate).

    **Option B (Output Buffering in `handleException`):**
    ```php
    // Inside ErrorHandler::handleException (Conceptual)
    public static function handleException($exception): void { // Use Throwable type hint if PHP 7+
        error_log("Exception caught by handler: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine()); // Enhanced logging
        // ... other logging/context gathering ...

        if (!headers_sent()) { // Check before setting code/headers
            http_response_code(500);
            // Ensure content type is set if not already, especially for HTML error page
            if (php_sapi_name() !== 'cli') { // Avoid setting header for CLI
                 header('Content-Type: text/html; charset=UTF-8');
            }
        }

        // Determine if development or production environment
        $isDevelopment = defined('ENVIRONMENT') && ENVIRONMENT === 'development';

        ob_start(); // Start buffering AFTER potentially setting headers

        if ($isDevelopment) {
            // Development: Show detailed error view
            $error = [ // Prepare error data for the view
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(), // Include trace for dev
                'context' => self::getSecureContext()
            ];
            extract(['error' => $error]); // Make $error available in the view
            require_once ROOT_PATH . '/views/error.php'; // Load the standard error view
        } else {
            // Production: Show generic error view
             require_once ROOT_PATH . '/views/error.php'; // Load the standard error view (ensure it hides details in prod)
            // OR load a dedicated production error view: require_once ROOT_PATH . '/views/error_production.php';
        }

        echo ob_get_clean(); // Output buffered content
        exit(); // Terminate script execution
    }
    ```
    *(Similar logic with `ob_start`/`ob_get_clean` should be applied to `handleError` and `handleFatalError` if they render HTML views)*

---
https://drive.google.com/file/d/1-h5nliZ76EPyVsvYKV4Fw90NCNxq9tkb/view?usp=sharing, https://drive.google.com/file/d/10Yq40a14_9vmT3uzAfgsS2kilylfTvPt/view?usp=sharing, https://drive.google.com/file/d/1329FY5UpOX2eK0v8vAQxgs2MXcGgkydc/view?usp=sharing, https://drive.google.com/file/d/163yCHucufj5U6umje23O6VxB2MpcZL0W/view?usp=sharing, https://drive.google.com/file/d/16fv1Baz-qG-GEvIufdTZcCRdKAPkWMya/view?usp=sharing, https://drive.google.com/file/d/18txrhiYojPLwVe_qKdMqACEnzvpEaZw6/view?usp=sharing, https://drive.google.com/file/d/1Ddzu5eg2qB1PJRT6gWOJmWh0zz_-1Dqd/view?usp=sharing, https://drive.google.com/file/d/1H5LRCr_cDAJJ48vbLiNq3nLVDc6IeErG/view?usp=sharing, https://drive.google.com/file/d/1LlSJ5rTSF8j-8JcEi1QZzzRStvAgJvXq/view?usp=sharing, https://drive.google.com/file/d/1ZnFG7nGTwXSJwIuvTE2f0Uoc8mezpEs3/view?usp=sharing, https://drive.google.com/file/d/1_TgHMWMICaYCOHO3eRSV2T7HZtHDyOeC/view?usp=sharing, https://drive.google.com/file/d/1bDx9NdU6EUp-0fsD_4ZUUoNVBwcdPRda/view?usp=sharing, https://drive.google.com/file/d/1g8OdwA0wb8f2GU9ogmh5iOPkttme-wH9/view?usp=sharing, https://drive.google.com/file/d/1iIGSc05_s3Bu5yd1IbxYxIqnVduYCNd0/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221j7C8jOnj2cbRo6Q1UjArTDYCXk6mLXgo%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1qU2RTMZI-h0-DLQ7Q7azHFmgiNsIPBH6/view?usp=sharing
