# The Scent – Technical Design Specification (v8.0)

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
    *   C. [Code Snippets and Patterns (CSRF Implementation & Pagination Fix Recommendation)](#c-code-snippets-and-patterns-csrf-implementation--pagination-fix-recommendation)

---

## 1. Introduction

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document (**v8.0**) serves as the updated technical design specification, reflecting the project's current state after addressing key functional issues and incorporating analysis from previous reviews.

This version documents the **successful resolution of the shopping cart page display error** by correcting the routing logic in `index.php`. It also details the **analysis and proposed fix for the product list pagination issue**, clarifies the inconsistent **cart storage mechanism**, and reiterates recommendations for **standardizing rate limiting** and **tightening the Content Security Policy (CSP)**. Add-to-Cart AJAX functionality remains operational following strict CSRF token handling patterns.

This document aims to offer deep insight into the system’s structure, logic, and flow, serving as a comprehensive onboarding and reference guide for the current state of the application, including known issues and recommended next steps.

---

## 2. Project Philosophy & Goals

*   **Security First:** All data input and user interactions are validated and sanitized. Strong session management and CSRF protection mechanisms are implemented and strictly required. **Strict adherence to the documented CSRF token handling pattern (Controller->View->HiddenInput `#csrf-token-value`->JS->Server Validation) is mandatory and implemented for all functional POST/AJAX operations.**
*   **Simplicity & Maintainability:** Clear, modular code structure. Direct `require_once` usage in `index.php` provides transparency but lacks autoloading benefits. Consistent coding patterns are enforced, particularly for security features like CSRF handling.
*   **Extensibility:** Architecture allows adding new features, pages, controllers, or views, requiring manual includes but offering straightforward extension points. New features involving POST must follow the established CSRF pattern.
*   **Performance:** Direct routing is potentially fast. Relies on PDO prepared statements. CDN usage for frontend libraries impacts external dependencies. Caching mechanisms (e.g., APCu for rate limiting) are recommended where applicable.
*   **Modern User Experience:** Responsive design, smooth animations (AOS.js, Particles.js), and AJAX interactions (cart updates/removal, newsletter, Add-to-Cart) provide a seamless interface. UI consistency across product displays is maintained. **Add-to-Cart functionality is operational across the site.** **Shopping Cart display is now functional.**
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
   | (e.g., CartController::showCart() now handles the main cart view request)
   v
[Model / DB Layer] (e.g., models/Product.php, models/Cart.php, direct PDO in includes/db.php)
   |
   | (Prepare/execute SQL queries, fetch data using PDO Prepared Statements)
   v
[View / Response]
   |--> [View File] (e.g., views/cart.php, views/products.php)
   |       (Included via require_once from controller action, e.g., CartController::showCart includes views/cart.php)
   |       (Generates HTML using PHP variables passed from controller, includes layout partials)
   |       (*** MUST output $csrfToken into <input type="hidden" id="csrf-token-value" ...> IF subsequent CSRF protection needed ***)
   |       (Output MUST use htmlspecialchars())
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

1.  **Request Initiation:** User accesses a URL (e.g., `index.php?page=cart`).
2.  **.htaccess Rewrite:** Apache rewrites the request to `/index.php` if applicable.
3.  **Initialization (`/index.php`):** Core files loaded, `$pdo` connection established, `ErrorHandler` initialized, `SecurityMiddleware::apply` sets security headers and secure session parameters.
4.  **Routing (`/index.php`):** `$page` ('cart'), `$action` ('index') extracted and validated. `page_num` parameter is read if present (for product lists).
5.  **CSRF Check (`/index.php`):** If `POST`, `SecurityMiddleware::validateCSRF()` is called automatically. (Not applicable for a GET request to the cart page).
6.  **Controller/View Dispatch (`/index.php` `switch ($page)`):**
    *   Case `cart`: `CartController.php` is included, `$controller = new CartController($pdo)` is instantiated.
    *   Since `$action` is 'index' (default) and it's not AJAX (`add` or `mini`), the code now executes `$controller->showCart();`.
7.  **Controller Action (`CartController::showCart()`):**
    *   Fetches cart items using `$this->getCartItems()` (which uses `$_SESSION['cart']` or `$this->cartModel` depending on login status).
    *   Calculates `$total`.
    *   Gets CSRF token via `$csrfToken = $this->getCsrfToken();`.
    *   Includes `views/cart.php`, passing the required variables (`$cartItems`, `$total`, `$csrfToken`).
8.  **View Rendering (`views/cart.php`):**
    *   Includes `layout/header.php`.
    *   Outputs the CSRF token into `<input type="hidden" id="csrf-token-value" ...>`.
    *   Renders the cart items, total, and action buttons using the passed variables (`$cartItems`, `$total`). Uses `htmlspecialchars()` for dynamic output.
    *   Includes `layout/footer.php`.
9.  **Response Transmission:** Server sends the complete HTML page to the browser.
10. **Client-Side Execution:** Browser renders the cart page. JS in `footer.php` and `cart.php` attaches event listeners for quantity changes, removal (which use AJAX and read `#csrf-token-value`).

---

## 4. Directory & File Structure

### 4.1 Folder Map

(Structure remains consistent with v7)

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
|   |-- products.php       # Product list - Requires CSRF token output for Add-to-Cart. *Pagination logic issue identified.*
|   |-- product_detail.php # Product detail - Requires CSRF token output for Add-to-Cart
|   |-- cart.php           # Cart view - Functional. Requires CSRF token output for AJAX updates/removal
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
|-- technical_design_specification.md # (This document v8)
|-- suggested_improvements_and_fixes.md # (Analysis document v1.0)
|-- the_scent_schema.sql.txt # Database schema
|-- ... (other docs, HTML output files)
```

### 4.2 Key Files Explained

*   **index.php**: Central orchestrator. Includes core components, performs routing (corrected logic for `page=cart`), validates basic input, **automatically validates CSRF token for ALL POST requests**, includes/instantiates controllers, dispatches to actions/views.
*   **config.php**: Defines constants and settings, including `SECURITY_SETTINGS` array controlling session behavior, rate limits, headers (CSP), etc.
*   **includes/SecurityMiddleware.php**: Static methods: `apply()` (sets headers/session params from config), `validateInput()`, `validateCSRF()` (compares `$_POST['csrf_token']` with session token), `generateCSRFToken()`.
*   **controllers/BaseController.php**: Abstract base providing shared functionality: `$db`, response helpers (clean `jsonResponse`, `redirect`), view rendering (`renderView`), validation helpers, authentication checks (`requireLogin`, `requireAdmin`), logging (`logAuditTrail`, `logSecurityEvent`), **CSRF token fetching (`getCsrfToken`)**, **standardized rate limiting check (`validateRateLimit`)**.
*   **controllers/ProductController.php**: Handles product listing, detail views, homepage featured products. Calculates pagination parameters. **Requires investigation/fix for pagination data fetching consistency (`getCount` vs `getFiltered`)**. Fetches distinct categories. Must pass CSRF token to views.
*   **controllers/CartController.php**: Handles cart logic. **`showCart()` method now correctly prepares data and renders the main cart view.** Handles AJAX actions (`add`, `mini`, `update`, `remove`). Interacts with session cart and `models/Cart.php` (DB cart) based on login status (consistency recommended).
*   **models/Product.php**: Encapsulates product DB logic (**PDO Prepared Statements**). `getFiltered()` requires update (explicit `PDO::PARAM_INT` binding) to potentially fix pagination. `getAllCategories()` updated for distinctness.
*   **views/layout/header.php**: Common header, includes assets, dynamic session info.
*   **views/layout/footer.php**: Common footer, JS initializations (AOS, Particles), **global AJAX handlers (Add-to-Cart, Newsletter) which strictly read CSRF token from `#csrf-token-value` hidden input**, `showFlashMessage` JS helper.
*   **views/*.php**: Page templates. **Must output `$csrfToken` into `<input type="hidden" id="csrf-token-value"...>` when forms/AJAX POSTs are initiated from the page.** Use `htmlspecialchars()` for all dynamic output.
*   **views/products.php**: Product list page. Displays grid, horizontal category filters, sorting, pagination. **Requires CSRF token output.** **Pagination logic currently broken (displays same products).**
*   **views/cart.php**: Cart view. **Now functional.** Displays items, totals. JS handles quantity updates and AJAX removal. **Requires CSRF token output.**
*   **views/product_detail.php**: Enhanced detail view. AJAX Add-to-Cart operational. **Requires CSRF token output.**

---

## 5. Routing and Application Flow

### 5.1 URL Routing via .htaccess

*   Mechanism: Apache `mod_rewrite` routes most non-file/directory requests to `/index.php`. Standard configuration.

### 5.2 index.php: The Application Entry Point

*   Role: Front Controller/Router.
*   Process: Initializes core components, extracts/validates `$page`/`$action`, reads `page_num` for pagination, **automatically performs CSRF check on ALL POST requests via `SecurityMiddleware::validateCSRF()`**, dispatches to controllers via `switch($page)`. **Routing logic for `case 'cart':` now correctly calls `CartController::showCart()` for the default view.**

### 5.3 Controller Dispatch & Action Flow

*   Controllers included/instantiated by `index.php`. Extend `BaseController`.
*   Execute business logic, use Models/PDO for data. Read parameters like `page_num`.
*   **Required CSRF Pattern:** Controllers rendering views that will initiate POST/AJAX requests **must** call `$csrfToken = $this->getCsrfToken();` and pass this token to the view data.
*   Pass data (including `$csrfToken`, pagination data) to views via `$data` array with `renderView` or `extract()` (or directly within the controller action like `CartController::showCart`).
*   Check rate limits using `$this->validateRateLimit()` on sensitive actions (standardization needed).
*   Terminate via view inclusion (`require_once`), `jsonResponse`, or `redirect`.

### 5.4 Views: Templating and Rendering

*   PHP files in `views/` mixing HTML and PHP.
*   Include layout partials (`header.php`, `footer.php`).
*   **Required CSRF Pattern:** Where subsequent forms or AJAX POSTs are initiated, the view **must** output the passed `$csrfToken` into a dedicated hidden input: `<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">`. Standard forms should *also* include `<input type="hidden" name="csrf_token" value="...">`.
*   Use `htmlspecialchars()` for all dynamic output.
*   Styling via `/css/style.css` and Tailwind CDN.
*   JS initialized/handled primarily in `footer.php`, **strictly relying on `#csrf-token-value` for CSRF tokens in AJAX.**
*   Pagination UI rendered in `views/products.php` based on data passed from the controller (**Data currently incorrect due to backend bug**).

---

## 6. Frontend Architecture

*(No significant changes from v7.0 in this section)*

### 6.1 CSS (css/style.css), Tailwind (CDN), and Other Libraries

*   Hybrid styling: Tailwind CDN utilities + custom CSS in `/css/style.css`. Custom styles added for horizontal category links (`.category-link`, `.category-link.active`).
*   Libraries via CDN: Google Fonts, Font Awesome 6, AOS.js, Particles.js.

### 6.2 Responsive Design and Accessibility

*   Responsive via Tailwind breakpoints. Mobile menu functional. Horizontal category filter bar designed to wrap on smaller screens.
*   Basic accessibility practices followed (semantic HTML, `aria-label`). Further audit recommended.

### 6.3 JavaScript: Interactivity, Libraries, and CSRF Handling

*   **Library Initialization:** AOS, Particles initialized in `footer.php`.
*   **Custom Handlers (Primarily `footer.php`):**
    *   **Add-to-Cart:** Global handler for `.add-to-cart` using event delegation. **Reads the CSRF token *strictly* from the `#csrf-token-value` hidden input.** Sends AJAX POST to `cart/add`, handles the clean JSON response, uses `showFlashMessage`. **Functional.**
    *   **Newsletter:** Handlers for `#newsletter-form` / `#newsletter-form-footer`. **Reads CSRF token strictly from `#csrf-token-value`**, sends via AJAX POST, uses `showFlashMessage`.
*   **Page-Specific JS:** Mobile menu (`header.php`), Cart page AJAX (`cart.php`), Product Detail AJAX/UI (`product_detail.php`), Products page filter/sort triggers (`products.php`). These rely on the presence of `#csrf-token-value` for POST actions.
*   **Standardization:** `showFlashMessage` helper used for feedback. Global handlers in `footer.php` minimize redundancy. **Key Dependency:** Functionality of AJAX POST handlers is critically dependent on the `#csrf-token-value` input being present and correctly populated on the page being viewed.

---

## 7. Key Pages & Components

### 7.1 Home/Landing Page (views/home.php)

*   Displays standard sections. Product cards use consistent styling. **Requires CSRF token output (`#csrf-token-value`)**. `.add-to-cart` buttons are functional.

### 7.2 Header and Navigation (views/layout/header.php)

*   Standard header. Displays dynamic session info. Includes assets. Mobile menu functional. Shop link points correctly to `index.php?page=products`.

### 7.3 Footer and Newsletter (views/layout/footer.php)

*   Standard footer. Contains JS init and **global AJAX handlers (Add-to-Cart, Newsletter) which strictly read the CSRF token from the `#csrf-token-value` hidden input.** Defines `showFlashMessage`.

### 7.4 Product Grid & Cards

*   Consistent styling using Tailwind classes across `home.php`, `products.php`, and `product_detail.php`. Includes stock status display and functional Add-to-Cart buttons (dependent on CSRF token output and JS handler).

### 7.5 Shopping Cart (views/cart.php)

*   **Functional.** Displays items and totals correctly due to the routing fix in `index.php`. JS handles quantity updates and AJAX removal. **Requires CSRF token output (`#csrf-token-value`)** for AJAX actions. Primarily uses session storage (`$_SESSION['cart']`), with inconsistent usage of `models/Cart.php` and `cart_items` table (see Recommendation 3).

### 7.6 Product Detail Page (views/product_detail.php)

*   Displays using the enhanced layout. Includes gallery, tabs, details, related products. AJAX Add-to-Cart for main and related products functional. **Requires CSRF token output (`#csrf-token-value`)**.

### 7.7 Products Page (views/products.php)

*   Displays product listing with horizontal category filters, sorting, pagination UI. **Requires CSRF token output (`#csrf-token-value`)**. Displays correct product images. **Pagination logic is currently broken (displays same products on pages 1 & 2)**; requires fix in `models/Product.php`.

### 7.8 Quiz Flow & Personalization

*   Standard quiz flow via `QuizController` and views. Forms require CSRF token.

---

## 8. Backend Logic & Core PHP Components

### 8.1 Includes: Shared Logic (includes/)

*   Foundational files: `auth.php`, `db.php`, `SecurityMiddleware.php`, `ErrorHandler.php`, `EmailService.php`.

### 8.2 Controllers: Business Logic Layer (controllers/ & BaseController.php)

*   `BaseController.php`: Provides shared methods including `$db`, response helpers (clean `jsonResponse`), validation, auth checks, **`getCsrfToken()`**, **`validateRateLimit()`**.
*   `ProductController.php`: Handles product listing, detail views, homepage featured products. Calculates pagination parameters. **Requires investigation/fix for pagination data fetching consistency (`getCount` vs `getFiltered`)**. Fetches distinct categories. Must pass CSRF token to views.
*   `CartController.php`: **`showCart()` method now correctly prepares data and renders the main cart view.** Handles AJAX actions. Interacts with session cart and `models/Cart.php` (consistency recommended).
*   Specific Controllers: Extend `BaseController`. Handle module logic. **Must call `$this->getCsrfToken()` and pass the token to views requiring subsequent CSRF protection.** Use `$this->validateRateLimit()` for sensitive actions (standardization needed).

### 8.3 Database Abstraction (includes/db.php & models/)

*   Connection via `includes/db.php` (`$pdo`).
*   Interaction via Models (`models/*.php`) or direct PDO usage, **strictly using Prepared Statements**. `Product::getAllCategories` updated to return distinct names. `Product::getFiltered` requires update (explicit `PDO::PARAM_INT` binding) to fix pagination.

### 8.4 Security Middleware & Error Handling

*   `SecurityMiddleware.php`: Static methods for applying security settings (headers/session from `config.php`), input validation, **CSRF generation (`generateCSRFToken`) and automatic validation (`validateCSRF` called in `index.php`)**.
*   `ErrorHandler.php`: Sets up global error/exception handling.

### 8.5 Session, Auth, and User Flow

*   Session started securely (`SecurityMiddleware::apply` uses settings from `config.php`). Includes standard data: `user_id`, `user_role`, `cart`, `cart_count`, `csrf_token`, `flash`, `last_regeneration`. Secure session settings applied. Session integrity validated in `BaseController::requireLogin`.
*   Auth flow via `AccountController`, helpers in `auth.php`, enforcement via `BaseController`. Rate limiting applied inconsistently (standardization recommended).
*   Cart data primarily stored in `$_SESSION['cart']` (consistency with DB cart recommended).

---

## 9. Database Design

*(No significant changes from v7.0 in this section, recommendations remain)*

### 9.1 Entity-Relationship Model (Conceptual)

Standard e-commerce relationships: Users have Orders, Orders have OrderItems, OrderItems relate to Products, Products belong to Categories. Users can have QuizResults.

### 9.2 Core Tables (from schema.sql)

Core tables (`users`, `products`, `categories`, `orders`, `order_items`, `cart_items`, `quiz_results`, `newsletter_subscribers`, `product_attributes`, `inventory_movements`) as defined in `the_scent_schema.sql.txt`. Duplicate names may exist in `categories` table data.

### 9.3 Schema Considerations & Recommendations

*   **Product Detail Fields:** Enhanced `views/product_detail.php` requires fields like `short_description`, `benefits` (JSON), etc. Schema includes these; ensure they are populated. `image` field holds the primary image path.
*   **Cart Table Usage:** `CartController` primarily uses `$_SESSION['cart']`. The `cart_items` table exists but is underutilized. **Recommendation:** Implement DB-backed persistent carts using `cart_items` for logged-in users (Recommendation 3).
*   **Category Data:** The `categories` table likely contains duplicate `name` entries. **Recommendation:** Clean up the data or rely on the `SELECT DISTINCT` query in `Product::getAllCategories`.

### 9.4 Data Flow Examples

*   **Add to Cart (Home/Product/Detail Page):** (AJAX) JS reads product ID, reads CSRF token *strictly* from `#csrf-token-value` -> POST to `index.php?page=cart&action=add` -> `index.php` validates CSRF -> `CartController::addToCart()` checks stock -> Updates `$_SESSION['cart']` -> Returns clean JSON response. (**Functional**)
*   **View Cart Page:** Request to `index.php?page=cart` -> `index.php` routes to `CartController::showCart()` -> Controller fetches items (session/DB), calculates total, gets CSRF -> Includes `views/cart.php`, passing data -> View renders correctly. (**Functional**)
*   **View Product List (Page 2):** Request to `products&page_num=2` -> `ProductController::showProductList()` calculates `offset=12`, fetches distinct categories, gets CSRF token -> Calls `Product::getFiltered` with `OFFSET 12` -> **(Currently returns same products as OFFSET 0 due to likely PDO binding issue)** -> Includes `views/products.php`, passing data -> View renders incorrect product set, correct pagination UI, and outputs `<input id="csrf-token-value">`. (**Broken - Needs Fix 2 applied**)

---

## 10. Security Considerations & Best Practices

*(No significant changes from v7.0 in this section, recommendations remain)*

### 10.1 Input Sanitization & Validation

*   Handled via `SecurityMiddleware::validateInput()` and specific checks within controllers/`BaseController`.

### 10.2 Session Management

*   Secure session settings applied via `SecurityMiddleware::apply()` using `config.php`. Session integrity checks implemented.

### 10.3 CSRF Protection (Implemented - Strict Pattern Required)

*   **Mechanism:** Synchronizer Token Pattern implemented via `SecurityMiddleware` and `BaseController`. Automatic validation in `index.php` for POST.
*   **Implementation Status:** Mechanism is sound and **strictly required** for all POST/AJAX actions, following the documented Controller->View->JS pattern.

### 10.4 Security Headers & CSP Standardization

*   Standard security headers applied globally via `SecurityMiddleware::apply()` based on `config.php`.
*   **Recommendation:** Review and tighten the `Content-Security-Policy` further (Recommendation 5).

### 10.5 Rate Limiting (Standardization Recommended)

*   Mechanism exists via `BaseController::validateRateLimit()`.
*   **Current Status:** Inconsistent implementation across controllers. Relies on APCu (may fail open).
*   **Recommendation:** Refactor controllers to consistently use the base method and ensure a working cache backend (Recommendation 4).

### 10.6 File Uploads & Permissions

*   Validation logic exists. Secure implementation required if feature is added.

### 10.7 Audit Logging & Error Handling

*   `ErrorHandler.php` provides global handling. `BaseController` provides logging methods.
*   **Recommendation:** Consistently use logging methods. Remove debug `error_log` calls.

### 10.8 SQL Injection Prevention

*   **Primary Defense: PDO Prepared Statements.** Consistently used. Sufficient.
*   **Recommendation:** Remove commented-out `preventSQLInjection` from `SecurityMiddleware.php`.

---

## 11. Extensibility & Onboarding

### 11.1 Adding Features, Pages, or Controllers

*   Follow the pattern: Create Controller (extending `BaseController`), create View(s), add routing case in `index.php`. **Implement the strict CSRF token handling pattern** if the feature involves POST actions.

### 11.2 Adding Products, Categories, and Quiz Questions

*   Requires DB manipulation or an Admin UI. Ensure new products have necessary fields populated. Clean up duplicate category names or rely on `DISTINCT` query.

### 11.3 Developer Onboarding Checklist

1.  Setup LAMP/LEMP stack, enable `mod_rewrite`.
2.  Clone repo.
3.  Setup DB, import schema. Check `categories` table.
4.  Configure `config.php`.
5.  Set file permissions (logs/, config.php).
6.  Configure Apache VirtualHost.
7.  Browse site, check server logs.
8.  **Verify CSRF implementation:** Check POST requests and `#csrf-token-value` in views.
9.  **Verify Core Functionality:** Test Add-to-Cart. **Test Cart Page (should work).** **Test Product List Pagination (needs Fix 2 applied/verified).** Test Category Filters. Test cart updates/removal. Test newsletter signup. Test login/registration.

### 11.4 Testing & Debugging Notes

*   Use browser dev tools (Network, Console, Application).
*   Check application logs (`logs/*.log`) and server logs.
*   **Debug Pagination:** Apply Fix 2 (explicit PDO binding in `Product::getFiltered`). Add `error_log` calls to trace SQL and parameters for different pages. Run generated SQL directly on DB.
*   **Key Areas to Verify:** Pagination logic implementation, Rate limiting standardization, Cart storage consistency, CSRF token flow, AJAX responses.

---

## 12. Future Enhancements & Recommendations

*   **Fix Pagination Logic:** Apply the recommended fix (explicit `PDO::PARAM_INT` binding in `Product::getFiltered`) and verify its effectiveness. (**Highest Priority Bug**)
*   **Standardize Rate Limiting:** Refactor controllers (`AccountController`, `NewsletterController`) to use `BaseController::validateRateLimit`. Ensure cache backend is functional or implement fallback. (**High Priority**)
*   **Database Cart:** Implement persistent cart functionality using `cart_items` table for logged-in users (Recommendation 3).
*   **Tighten CSP:** Review and refine the Content-Security-Policy in `config.php` to remove `'unsafe-inline'` and `'unsafe-eval'` (Recommendation 5).
*   **Remove SQLi Function:** Delete commented-out `preventSQLInjection` from `SecurityMiddleware.php`.
*   **Consistent Logging:** Ensure `logAuditTrail` and `logSecurityEvent` are used appropriately. Remove debug `error_log` calls.
*   **Autoloader:** Implement PSR-4 autoloading (via Composer).
*   **Dependency Management:** Use Composer.
*   **Routing:** Replace `index.php` switch with a dedicated Router component.
*   **Templating Engine:** Consider Twig or BladeOne.
*   **Environment Configuration:** Use `.env` files.
*   **Database Migrations:** Implement Phinx or similar.
*   **Unit/Integration Testing:** Add PHPUnit tests.
*   **API Development:** Consider a RESTful API layer.
*   **Admin Panel:** Develop a comprehensive admin interface.

---

## 13. Appendices

### A. Key File Summaries

| File/Folder                 | Purpose                                                                                                           | Status Notes                                                                                             |
| :-------------------------- | :---------------------------------------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------------------------- |
| `index.php`                 | Entry point, routing (**cart page fixed**), core includes, **auto POST CSRF validation**, dispatch.               | OK                                                                                                       |
| `config.php`                | DB credentials, App/Security settings (**CSP, Rate Limits, Session**), API keys                                     | OK. Needs rate limit config review. CSP tightening recommended. Move secrets to .env recommended.      |
| `includes/SecurityMiddleware.php` | Static helpers: `apply()`, `validateInput()`, `validateCSRF()`, `generateCSRFToken()`                           | OK. `preventSQLInjection` removal recommended.                                                         |
| `controllers/BaseController.php` | Abstract base: `$db`, helpers (JSON, redirect, render), validation, auth checks, `getCsrfToken()`, `validateRateLimit` | OK. Rate limiting usage needs enforcement/standardization in child controllers.                        |
| `controllers/CartController.php`| Handles cart logic. **`showCart()` renders main view correctly.** Handles AJAX actions.                           | Functional. Cart storage consistency recommended.                                                      |
| `controllers/ProductController.php` | Product listing/detail. Calculates pagination offset. **Pagination DB fetch requires fix.**                 | Pagination logic needs fix applied (`Product::getFiltered`). Requires consistent CSRF token passing. |
| `models/Product.php`              | Entity DB logic (**PDO Prepared Statements**). **`getFiltered` needs explicit binding fix for pagination.**     | Needs update for pagination fix.                                                                         |
| `views/layout/header.php`   | Header, navigation, assets                                                                                        | OK.                                                                                                    |
| `views/*.php`               | HTML/PHP templates, **must output CSRF token into `#csrf-token-value` hidden input**.                             | Requires consistent token output.                                                                        |
| `views/layout/footer.php`   | Footer, JS init, **global AJAX handlers strictly reading CSRF token from `#csrf-token-value`**                     | OK. JS handler works correctly.                                                                          |
| `views/products.php`        | Product list, horizontal category filters, sorting, pagination UI. **Requires CSRF token output.** **Pagination data broken.** | Layout OK. Pagination data needs backend fix.                                                        |
| `views/cart.php`            | Cart view. Displays items/totals. AJAX actions functional. **Requires CSRF token output.**                        | **Functional.** Cart storage consistency recommended.                                                  |

### B. Glossary

(Standard terms: MVC, CSRF, XSS, SQLi, PDO, AJAX, CDN, CSP, Rate Limiting, Prepared Statements, Synchronizer Token Pattern, APCu)

### C. Code Snippets and Patterns (CSRF Implementation & Pagination Fix Recommendation)

#### Correct & Required CSRF Token Implementation Pattern

*(Remains the same as v7.0 - Ensure pattern is followed)*

1.  **Controller (Rendering View):** Get token via `$this->getCsrfToken()` and pass to view.
2.  **View:** Output token into `<input type="hidden" id="csrf-token-value" value="...">`. Include `name="csrf_token"` in standard forms too.
3.  **JavaScript:** Read token *only* from `#csrf-token-value` for AJAX POST requests.
4.  **Server (`index.php`):** Automatic validation via `SecurityMiddleware::validateCSRF()` for POST.

#### Recommended Pagination Fix (PDO Binding)

*   **File:** `models/Product.php`
*   **Method:** `getFiltered`
*   **Recommendation:** Explicitly bind LIMIT and OFFSET parameters using `PDO::PARAM_INT`.

    ```php
    // Inside Product::getFiltered after preparing $sql and $params array...

    $sql .= " LIMIT ? OFFSET ?";
    $params[] = (int)$limit;
    $params[] = (int)$offset;

    $stmt = $this->pdo->prepare($sql);

    // Explicit binding approach (recommended):
    $paramCount = count($params);
    foreach ($params as $key => $value) {
        $paramIndex = $key + 1;
        if ($paramIndex === $paramCount - 1) { // Limit parameter
            $stmt->bindValue($paramIndex, (int)$value, PDO::PARAM_INT);
        } elseif ($paramIndex === $paramCount) { // Offset parameter
            $stmt->bindValue($paramIndex, (int)$value, PDO::PARAM_INT);
        } else {
            // Let PDO determine type for other filter parameters
            $stmt->bindValue($paramIndex, $value);
        }
    }
    $stmt->execute();

    // OR rely on execute (less explicit but often works):
    // $stmt->execute($params);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // ... rest of the method ...
    ```

---
https://drive.google.com/file/d/1-mmbmgDm5eSK9DRQTpI-kI952hZ6KMtW/view?usp=sharing, https://drive.google.com/file/d/193i6zLAWhG2Dk0oyRkr47Nv4F7lx5xYJ/view?usp=sharing, https://drive.google.com/file/d/1DPvAw1Fbd-1-nLNhfCCrYop93xjhhxt1/view?usp=sharing, https://drive.google.com/file/d/1H2FYaJ_6tuHGxYivfburbHWfAFVrEA3P/view?usp=sharing, https://drive.google.com/file/d/1MPZjnDevg2WhH4hnESIMgJ6leGZbSTwm/view?usp=sharing, https://drive.google.com/file/d/1MiH7a5XM_BRAz1MiZ5IxRz57692lwjw_/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221RU7dELKJT7Q8j9h7C4Y6IFMW8utfq_-q%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1Vj8BpOoSkyUxqgJWh4Qc0Yua4YvGE_s8/view?usp=sharing, https://drive.google.com/file/d/1VvlvagtoNe5qhFcKdSUBywUatxVWU115/view?usp=sharing, https://drive.google.com/file/d/1dKPcv55Jw-AthO8ZXsh0Jq0S6Yr1xM4s/view?usp=sharing, https://drive.google.com/file/d/1dj90s2V52aXob050iYyhmkw1tcG1NXXq/view?usp=sharing, https://drive.google.com/file/d/1iqMgMGVNI-qv0Yw-cVSoKnY1zoFyCIcg/view?usp=sharing, https://drive.google.com/file/d/1nEpjmkBuyn-Uf01U9r3PGHZeQIrCpEqc/view?usp=sharing, https://drive.google.com/file/d/1q83baNGu8o_BlHRLVw9bsSO9k8qzPa_4/view?usp=sharing, https://drive.google.com/file/d/1s1uKXO3KuOhpvM0g5WLXUE01N1AJEDZI/view?usp=sharing, https://drive.google.com/file/d/1ujfRAGbSlLrDcu-mX3kMaESDLyftE57E/view?usp=sharing
