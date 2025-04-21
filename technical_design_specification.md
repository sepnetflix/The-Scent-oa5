# The Scent – Technical Design Specification (v2.0)

**Document Version:** 2.0
**Date:** 2024-05-16
**Status:** Updated based on code review and analysis as of May 16, 2024.

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
    *   9.3 [Schema Inconsistencies & Recommendations](#schema-inconsistencies--recommendations)
    *   9.4 [Data Flow Examples](#data-flow-examples)
10. [Security Considerations & Identified Issues](#security-considerations--identified-issues)
    *   10.1 [Input Sanitization & Validation](#input-sanitization--validation)
    *   10.2 [Session Management](#session-management)
    *   10.3 [CSRF Protection (CRITICAL ISSUE IDENTIFIED)](#csrf-protection-critical-issue-identified)
    *   10.4 [Security Headers & CSP Issue](#security-headers--csp-issue)
    *   10.5 [Rate Limiting (Mechanism Unclear)](#rate-limiting-mechanism-unclear)
    *   10.6 [File Uploads & Permissions](#file-uploads--permissions)
    *   10.7 [Audit Logging & Error Handling](#audit-logging--error-handling)
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

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document (v2.0) serves as the updated technical design specification, incorporating findings from code review and analysis performed on May 16, 2024. It aims to offer deep insight into the system’s *actual* structure, logic, identified issues, and flow, serving as a comprehensive onboarding and reference guide.

---

## 2. Project Philosophy & Goals

*   **Security First:** (Intended Goal) All data input and user interactions *should be* validated and sanitized. Strong session and CSRF protection *are intended* but implementation requires review (see Section 10).
*   **Simplicity & Maintainability:** Clear, modular code structure observed. Direct `require_once` usage in `index.php` fits this philosophy but lacks autoloading benefits.
*   **Extensibility:** Architecture allows adding new features, pages, controllers, or views, though requires manual includes.
*   **Performance:** Direct routing is potentially fast. Relies on PDO prepared statements. CDN usage for frontend libraries impacts external dependencies.
*   **Modern User Experience:** Responsive design, smooth animations (AOS.js, Particles.js), and AJAX interactions observed (cart updates/removal, newsletter), though inconsistencies exist.
*   **Transparency:** No magic – application flow and routing are explicit in `index.php`'s include and switch logic.
*   **Accessibility & SEO:** Semantic HTML used. `aria-label` observed. Further accessibility audit recommended.

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
   | (CRITICAL: Validates CSRF token for POST requests - relies on token being present/correct)
   v
[Controller]  (e.g., ProductController, CartController)
   |           (Included via require_once *within* index.php's switch case)
   |           (Instantiated, passed $pdo connection)
   |           (Often extends BaseController)
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
   |       (Output MUST use htmlspecialchars())
   |
   |--> [JSON Response] (via BaseController::jsonResponse for AJAX)
   |       (e.g., for cart add/update/remove, newsletter)
   |
   |--> [Redirect] (via BaseController::redirect or header())
   |
   v
[Browser/Client] <-- Renders HTML, applies CSS (Tailwind CDN, custom), executes JS (libraries, custom handlers)
```

### 3.2 Request-Response Life Cycle

1.  **Request Initiation:** User accesses a URL (e.g., `/`, `/index.php?page=cart`, `/product/1`).
2.  **.htaccess Rewrite:** Apache (with `mod_rewrite` enabled and `AllowOverride All`) uses rules in `/.htaccess`. If the request isn't for an existing file/directory (and not excluded like `test_*.php`), it's rewritten to `/index.php`, preserving the query string.
3.  **Initialization (`/index.php`):**
    *   `ROOT_PATH` defined.
    *   Core files included: `config.php`, `includes/db.php` (makes `$pdo` available), `includes/auth.php`, `includes/SecurityMiddleware.php`, `includes/ErrorHandler.php`.
    *   `ErrorHandler::init()` registers error/exception handlers.
    *   `SecurityMiddleware::apply()` potentially sets global security settings (e.g., session config).
4.  **Routing (`/index.php`):** `$page`, `$action` extracted from `$_GET`, validated using `SecurityMiddleware::validateInput()`.
5.  **CSRF Check (`/index.php`):** If `$_SERVER['REQUEST_METHOD'] === 'POST'`, `SecurityMiddleware::validateCSRF()` is called. **(CRITICAL: This currently fails if tokens are not correctly generated/outputted in forms - see Section 10.3)**.
6.  **Controller/View Dispatch (`/index.php` `switch ($page)`):**
    *   The relevant `case` block executes.
    *   `require_once` loads the necessary Controller file(s).
    *   The Controller class is instantiated (e.g., `$controller = new CartController($pdo);`).
    *   Based on `$action` or default logic:
        *   A controller method is called (e.g., `$productController->showProduct($_GET['id'])`).
        *   OR a view file is directly included (`require_once __DIR__ . '/views/cart.php';`).
        *   OR an AJAX handling method is called which then uses `jsonResponse()` (e.g., `$controller->addToCart()`).
7.  **Controller Action:** Executes business logic, interacts with the database (via `$this->db` from `BaseController` or Models), prepares data for the view. Performs necessary checks (auth, validation).
8.  **View Rendering / Response Generation:**
    *   **HTML Page:** The view file (`views/*.php`) generates HTML, embedding data using PHP variables (passed via scope or `renderView`). Layout partials (`header.php`, `footer.php`) are included. `htmlspecialchars()` is used for output escaping.
    *   **JSON Response:** Controller uses `BaseController::jsonResponse()` to output JSON and exit (for AJAX).
    *   **Redirect:** Controller uses `BaseController::redirect()` or `header('Location: ...'); exit;`.
9.  **Response Transmission:** Apache sends the generated HTML/JSON/Redirect headers to the browser.
10. **Client-Side Execution:** Browser renders HTML, applies CSS (`/css/style.css`, Tailwind CDN), executes JS (AOS, Particles, custom handlers for mobile menu, AJAX cart/newsletter, etc.).

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
|   |-- SecurityMiddleware.php # Security helpers (validation, CSRF)
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
|   |-- products.php
|   |-- product_detail.php # (Needs update based on suggestions)
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
|-- technical_design_specification.md # (This document v1)
|-- ... (other docs, schema file)
```

### 4.2 Key Files Explained

*   **index.php**: Central orchestrator. Includes core components, performs routing via `switch($page)`, validates basic input and CSRF (POST), includes and instantiates controllers *dynamically within cases*, and typically includes the final view or triggers JSON response/redirect via controller.
*   **config.php**: Defines constants/globals for DB credentials (`DB_HOST`, `DB_USER`, etc.), application settings (`BASE_URL`, `TAX_RATE`), API keys (Stripe), email config (SMTP), and crucially, the `SECURITY_SETTINGS` array (session, rate limiting, CSP, CSRF config).
*   **css/style.css**: Holds custom styles supplementing Tailwind.
*   **particles.json**: JSON config for the Particles.js library.
*   **.htaccess**: Routes non-file/directory requests (excluding specified patterns) to `index.php`. Requires `mod_rewrite` and `AllowOverride All`.
*   **includes/db.php**: Creates the PDO connection (`$pdo`) using `config.php` constants, sets error mode, and makes `$pdo` globally available for `index.php`.
*   **includes/auth.php**: Contains `isLoggedIn()` and `isAdmin()` likely checking `$_SESSION` variables set during login.
*   **includes/SecurityMiddleware.php**: Provides static methods: `apply()` (called once in `index.php`), `validateInput()`, `validateCSRF()`. CSRF token generation likely happens via `BaseController` calling a helper (static or instance).
*   **includes/ErrorHandler.php**: Static `init()` registers PHP error/exception handlers. Likely logs errors and includes `views/error.php` for user display.
*   **controllers/BaseController.php**: Abstract class providing foundational methods for other controllers: `$db` (PDO), validation helpers (`validateInput`, `validateRequest`), response helpers (`jsonResponse`, `sendError`, `redirect`), view rendering (`renderView`), CSRF generation/validation (`getCsrfToken`, `validateCSRFToken`, `validateCSRF`), authentication checks (`requireLogin`, `requireAdmin`), rate limiting helpers (multiple, need consolidation), logging (`logAuditTrail`, `logSecurityEvent`), file upload validation (`validateFileUpload`), session integrity/regeneration helpers.
*   **controllers/*Controller.php**: Implement specific module logic (e.g., `ProductController::showProduct`, `CartController::addToCart`). Extend `BaseController`. Use `$this->db` or Models. Prepare data for views or handle AJAX requests.
*   **models/*.php**: (e.g., `Product.php`) Encapsulate data logic and database interactions for specific entities. Methods like `getById`, `getAll`, `getFeatured`, `updateStock`, `isInStock` are observed.
*   **views/layout/header.php**: Common header, includes CSS/JS (CDNs, `/css/style.css`), sets up Tailwind config, renders navigation, displays dynamic session info (login status via `isLoggedIn()`, cart count `$_SESSION['cart_count']`), displays server-side flash messages (`$_SESSION['flash_message']`). Contains mobile menu JS.
*   **views/layout/footer.php**: Common footer, social links, newsletter form. **Crucially contains JS initialization** for AOS, Particles, and **event handlers for Add-to-Cart (`.add-to-cart`) and Newsletter submission (AJAX)**. Also includes the `showFlashMessage` JS helper.
*   **views/*.php**: Specific page templates mixing HTML and PHP. Use data passed from controllers/`index.php`. Escape output with `htmlspecialchars()`.

---

## 5. Routing and Application Flow

### 5.1 URL Routing via .htaccess

*   **Mechanism:** Apache `mod_rewrite` used via `/.htaccess`.
*   **Rules:** `RewriteEngine On`. Conditions check `REQUEST_FILENAME` is not a file (`!-f`) or directory (`!-d`), and `REQUEST_URI` doesn't match `/test_*.php` or `/sample_*.html`. If conditions pass, request is rewritten to `/index.php` using `RewriteRule ^ index.php [L]`. Query string is automatically appended.
*   **Effect:** Routes most requests through the single `/index.php` entry point, enabling a front controller pattern.

### 5.2 index.php: The Application Entry Point

*   **Role:** Acts as the Front Controller and Router.
*   **Process:**
    1.  Initialization (includes, error handling, security middleware).
    2.  Input Extraction: `$page = $_GET['page'] ?? 'home'`, `$action = $_GET['action'] ?? 'index'`. Validated via `SecurityMiddleware::validateInput()`.
    3.  CSRF Validation: `SecurityMiddleware::validateCSRF()` called for all `POST` requests. **(Currently ineffective due to token generation issue - see 10.3)**.
    4.  Routing: `switch ($page)` block directs flow.
    5.  Controller Loading: `require_once __DIR__ . '/controllers/...'` happens *inside* the specific `case` statement.
    6.  Controller Instantiation: `new ControllerName($pdo)` happens *inside* the `case`.
    7.  Action Dispatch: Controller method is called (e.g., `$controller->actionName()`) or view is directly included (`require_once`).
    8.  Error Handling: `try...catch` block around routing handles exceptions, logging via `ErrorHandler` or displaying error view. Default case handles 404.

### 5.3 Controller Dispatch & Action Flow

*   Controllers (`controllers/`) are included and instantiated dynamically by `index.php`.
*   They extend `BaseController` to inherit common functionality (`$this->db`, helpers).
*   Methods within controllers handle specific actions (e.g., `CartController::addToCart()`, `ProductController::showProductList()`).
*   Methods use `$this->db` (PDO) or Models (`$this->productModel = new Product($pdo)`) for data operations.
*   Perform validation using inherited or specific logic.
*   Use auth helpers (`requireLogin`/`Admin`).
*   Prepare data arrays or variables for views.
*   Terminate by:
    *   Allowing `index.php` to include the view.
    *   Calling `$this->renderView($viewPath, $data)`.
    *   Calling `$this->jsonResponse($data, $statusCode)` for AJAX.
    *   Calling `$this->redirect($url)`.

### 5.4 Views: Templating and Rendering

*   Located in `views/`. Are PHP files mixing HTML and PHP logic.
*   Included via `require_once` (variables available via scope) or `$this->renderView()` (variables available via `extract($data)`).
*   Layout uses `require_once __DIR__ . '/layout/header.php'` and `footer.php`.
*   **Output Escaping:** `htmlspecialchars()` is used inconsistently but is present in key areas (e.g., product names, descriptions). Needs rigorous application.
*   Styling relies on `/css/style.css` and Tailwind CDN utility classes.
*   JavaScript included via CDNs in `header.php` or initialized/defined in `footer.php`.

---

## 6. Frontend Architecture

### 6.1 CSS (css/style.css), Tailwind (CDN), and Other Libraries

*   **Styling:** Hybrid approach.
    *   **Tailwind CSS:** Loaded via CDN in `header.php`. Configuration defined inline in `<script>`. Used for utility-first styling.
    *   **Custom CSS:** `/css/style.css` provides custom component styles, overrides, and potentially base styles.
*   **Libraries:** Loaded via CDN in `header.php`:
    *   Google Fonts (`Cormorant Garamond`, `Montserrat`, `Raleway`).
    *   Font Awesome 6 (icons).
    *   AOS.js (scroll animations - CSS in header, JS init in footer).
    *   Particles.js (background animations - JS init in footer, config `/particles.json`).

### 6.2 Responsive Design and Accessibility

*   **Responsive:** Uses Tailwind's breakpoints (`md:`, `lg:`). Mobile menu handled by JS. Visual check needed across devices.
*   **Accessibility:** Basic semantic HTML. `aria-label` used on some icon buttons. Mobile menu has keyboard support (`Escape`). `showFlashMessage` uses `role="alert"`. Further audit (contrast, focus states, form labels, image alts) is recommended.

### 6.3 JavaScript: Interactivity, Libraries, and Issues

*   **Library Initialization:** AOS (`AOS.init()`) and Particles (`particlesJS.load`) are initialized in `footer.php`.
*   **Custom Handlers (Primarily in `footer.php` using Event Delegation where appropriate):**
    *   **Add-to-Cart:** `document.body` listener for `.add-to-cart` clicks. Sends AJAX POST to `cart/add` using `fetch`. Expects JSON response. Uses `showFlashMessage` for feedback. **(CRITICAL: Relies on non-existent CSRF token in forms)**.
    *   **Newsletter:** Listener on `#newsletter-form` (home) and `#newsletter-form-footer` (footer). Sends AJAX POST to `newsletter/subscribe` using `fetch`. Uses `showFlashMessage` for feedback (corrected from `alert`). **(CRITICAL: Relies on non-existent CSRF token in forms)**.
*   **Custom Handlers (Page-Specific):**
    *   **Mobile Menu:** (`header.php`) Toggles menu visibility and icon.
    *   **Sticky Header:** (`home.php`) Adds/removes class on scroll. *(Should be moved to global scope if desired on all pages)*.
    *   **Cart Page (`cart.php`):** Quantity +/- buttons (client-side update). AJAX form submission (`#cartForm`) for updates. AJAX link (`.remove-item`) for removal. Uses `showFlashMessage` for feedback (corrected from `alert`). **(CRITICAL: Relies on non-existent CSRF token in forms)**.
    *   **Product Detail Page (`product_detail.php` - Enhanced Version):** Gallery thumbnail interaction (`updateMainImage`). Quantity +/- buttons. Tab switching logic. AJAX Add-to-Cart for main product and related products. **(CRITICAL: Relies on non-existent CSRF token in forms)**.
*   **Identified Issues:**
    *   **JS Redundancy:** AOS/Particles init likely duplicated (present in `home.php` script block and `footer.php`). Add-to-Cart handler potentially duplicated. *(Recommendation: Centralize in `footer.php`)*.
    *   **Feedback Inconsistency:** Previously used `alert` in cart/newsletter, now standardized to `showFlashMessage` helper (defined in `footer.php`).
    *   **Image Path Mismatch:** JS doesn't directly handle images, but PHP views had `image_url` vs `image` mismatch (addressed in Section 7.6).

---

## 7. Key Pages & Components

### 7.1 Home/Landing Page (views/home.php)

*   Displays Hero (video/particles), About, Featured Products, Benefits, Quiz Finder, Newsletter, Testimonials.
*   Receives `$featuredProducts` from `ProductController::showHomePage()`.
*   Product cards use `htmlspecialchars()`, display based on `stock_quantity`. Includes `.add-to-cart` buttons handled by `footer.php` JS.
*   Contains JS for sticky header *(should be moved)*. Newsletter form handled by `footer.php` JS.

### 7.2 Header and Navigation (views/layout/header.php)

*   Generates standard header. Displays login/account link based on `isLoggedIn()`. Shows cart count from `$_SESSION['cart_count']`. Includes assets. Displays server-side `$_SESSION['flash_message']`. Contains mobile menu JS.

### 7.3 Footer and Newsletter (views/layout/footer.php)

*   Generates standard footer. Includes JS initializations (AOS, Particles). **Contains global AJAX handlers** for `.add-to-cart` and newsletter forms. Defines `showFlashMessage` JS helper.

### 7.4 Product Grid & Cards

*   Used on Home (`home.php`), Product Listing (`products.php`), Related Products (`product_detail.php`). Uses Tailwind grid.
*   Card displays image (`$product['image']`), name, category (`$product['category_name']`), price. Links to detail page. Includes `.add-to-cart` button (handled by footer JS) or "Out of Stock". Style consistency improved in suggested `product_detail.php`.

### 7.5 Shopping Cart (views/cart.php)

*   Receives `$cartItems`, `$total` from `CartController`. Displays items in a form (`#cartForm`).
*   JS handles quantity buttons, AJAX removal (`.remove-item`), AJAX update (form submit). Updates UI totals and header count. Uses `showFlashMessage`. **Requires CSRF token fix.**

### 7.6 Product Detail Page (views/product_detail.php)

*   **Current State (Observed):** Displays placeholder image due to `image_url` vs `image` mismatch. Basic layout, inconsistent styling. Missing data in output.
*   **Target State (Recommended Implementation):** Use the enhanced code from `suggestions_for_improvement_images-v2.md`.
    *   Fixes image path (`$product['image']`).
    *   Uses Tailwind for consistent, modern 2-column layout.
    *   Includes enhanced gallery, tabs, info hierarchy.
    *   Includes AJAX add-to-cart functionality.
    *   **Dependencies:** Requires corresponding database fields (see Section 9.3) and controller passing complete `$product` data. **Requires CSRF token fix.**

### 7.7 Quiz Flow & Personalization

*   Entry `index.php?page=quiz`. View `views/quiz.php` displays questions from `QuizController`.
*   Submission POSTs to `quiz/submit`, processed by `QuizController::processQuiz`.
*   Results view `views/quiz_results.php`. Admin analytics route exists. Logic relies on `QuizController` and `Quiz` model.

---

## 8. Backend Logic & Core PHP Components

### 8.1 Includes: Shared Logic (includes/)

*   Foundational PHP files: `auth.php` (session helpers), `db.php` (PDO setup), `SecurityMiddleware.php` (validation, CSRF helpers), `ErrorHandler.php` (error setup), `EmailService.php`.

### 8.2 Controllers: Business Logic Layer (controllers/ & BaseController.php)

*   `BaseController.php`: Abstract class providing extensive shared functionality:
    *   Properties: `$db` (PDO), `$securityMiddleware`, `$emailService`.
    *   Methods: `jsonResponse`, `sendError`, `redirect`, `renderView`, `validateInput`, `validateRequest`, `requireLogin`, `requireAdmin`, `getCsrfToken` (generates/retrieves session token), `validateCSRFToken` (instance check), `validateCSRF` (likely calls instance check), `set/getFlashMessage`, DB transactions (`beginTransaction`, `commit`, `rollback`), rate limiting helpers (multiple - need consolidation: `checkRateLimit`, `validateRateLimit`, `isRateLimited`), logging (`logAuditTrail`, `logSecurityEvent`), file uploads (`validateFileUpload`).
*   Specific Controllers (e.g., `ProductController`, `CartController`): Extend `BaseController`. Handle module-specific logic. Instantiate Models or use `$this->db`.

### 8.3 Database Abstraction (includes/db.php & models/)

*   **Connection:** `includes/db.php` creates `$pdo` using `config.php`, sets `ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`.
*   **Interaction:**
    *   Controllers use `$this->db` (inherited PDO instance).
    *   Models (e.g., `models/Product.php`) encapsulate entity-specific queries (using injected PDO). Prepared statements (`prepare`, `execute`) are used. `fetchAll(PDO::FETCH_ASSOC)` is common.

### 8.4 Security Middleware & Error Handling

*   **SecurityMiddleware.php (`includes/`):** Provides static methods called early in `index.php` (`apply`, `validateInput`, `validateCSRF`). Also likely used by `BaseController` for CSRF token generation/validation logic.
*   **ErrorHandler.php (`includes/`):** Static `init()` registers handlers. Catches errors/exceptions, logs details, includes `views/error.php`.

### 8.5 Session, Auth, and User Flow

*   **Session:** Started via `SecurityMiddleware::apply()` or implicitly. Secure cookie attributes (HttpOnly, SameSite) defined in `config.php`. Session ID regeneration logic exists in `BaseController` (`regenerateSession`, called by `requireLogin` if needed). Session integrity checks also present (`validateSessionIntegrity`).
*   **Auth:** `isLoggedIn()` / `isAdmin()` helpers check `$_SESSION`. `AccountController` handles login (`password_verify`), sets `$_SESSION['user_id']`, potentially `$_SESSION['user_role']`. `BaseController::requireLogin/Admin` enforce access.
*   **Data in Session:** `user_id`, `user_role`, `cart` (array `[product_id => quantity]`), `cart_count`, `csrf_token`, flash messages (`flash`), rate limit counters.

---

## 9. Database Design

### 9.1 Entity-Relationship Model (Conceptual)

Standard e-commerce relationships:
`users` (1) --< `orders` (M) --< `order_items` (M) >-- (1) `products`
`products` (M) >-- (1) `categories`
`users` (1) --< `cart_items` (M) >-- (1) `products` (or session-based cart)
`users` (1) --< `quiz_results` (M)
`products` (1) --< `product_attributes` (M)
`products` (1) --< `inventory_movements` (M)

### 9.2 Core Tables (from schema.sql)

*   `users`: id, name, email, password (hashed), role, created_at
*   `products`: id, name, description, **image** (varchar), price, category_id, is_featured, created_at, low_stock_threshold, reorder_point, updated_at, highlight_text, **stock_quantity**, **stock** (redundant).
*   `categories`: id, name, description
*   `orders`: id, user_id, total_price, status, created_at
*   `order_items`: id, order_id, product_id, quantity, price
*   `cart_items`: id, user_id, session_id, product_id, quantity (*Note: Cart logic in CartController uses `$_SESSION['cart']` directly, not this table based on reviewed code.*)
*   `quiz_results`: id, user_id, email, answers, recommendations, created_at
*   `newsletter_subscribers`: id, email, subscribed_at
*   `product_attributes`: id, product_id, scent_type, mood_effect, intensity_level
*   `inventory_movements`: id, product_id, quantity_change, type, reference_id, notes, created_at

### 9.3 Schema Inconsistencies & Recommendations

*   **Missing Fields for Enhanced Product View:** The `products` table lacks columns needed for the suggested `product_detail.php` layout: `short_description`, `benefits` (JSON/TEXT), `ingredients` (TEXT), `usage_instructions` (TEXT), `gallery_images` (JSON/TEXT), `size` (VARCHAR), `scent_profile` (VARCHAR, or use `product_attributes` join), `origin` (VARCHAR), `sku` (VARCHAR), `backorder_allowed` (TINYINT).
    *   **Recommendation:** Implement **Solution Option A** from `suggestions_for_improvement_images-v2.md`: Use `ALTER TABLE` to add these missing columns to the `products` table. This is required for the enhanced view to function fully. Update `ProductController`/`Product` model queries accordingly.
*   **Redundant Stock Column:** `products` table has both `stock` and `stock_quantity`. Code uses `stock_quantity`.
    *   **Recommendation:** Remove the `stock` column: `ALTER TABLE products DROP COLUMN stock;`
*   **Cart Table Usage:** The `cart_items` table exists but `CartController` uses `$_SESSION['cart']`. Decide if the table is for persistence across sessions (requires significant controller logic change) or if it's unused/legacy. If unused, consider removing it. If intended, implement logic to sync session cart with DB table.

### 9.4 Data Flow Examples

*   **Add to Cart:** (AJAX) JS POST -> `index.php?page=cart&action=add` -> `CartController::addToCart()` -> Updates `$_SESSION['cart']`, `$_SESSION['cart_count']` -> Returns JSON.
*   **Place Order:** (POST) Form -> `index.php?page=checkout&action=process` -> `CheckoutController::processCheckout()` -> Creates `orders`, `order_items` records -> Updates product stock -> Clears session cart -> Redirects to confirmation.
*   **View Product:** Request -> `index.php?page=product&id=X` -> `ProductController::showProduct(X)` -> Fetches data via `Product::getById(X)` (needs to select all required fields) -> Includes `views/product_detail.php`.

---

## 10. Security Considerations & Identified Issues

### 10.1 Input Sanitization & Validation

*   **Mechanism:** `SecurityMiddleware::validateInput()` used in `index.php` for basic type checking. `BaseController::validateInput/validateRequest` provide more helpers. PDO Prepared Statements used in Models/DB access prevent SQLi.
*   **Output Escaping:** `htmlspecialchars()` used in views, but needs consistent application everywhere user-controlled data is displayed.
*   **Status:** Generally good practice observed, but ensure validation rules are comprehensive and output escaping is universal.

### 10.2 Session Management

*   **Configuration:** Secure attributes (HttpOnly, SameSite) set via `config.php` (`SECURITY_SETTINGS['session']`).
*   **Handling:** Session ID regeneration (`regenerateSession`) and integrity checks (`validateSessionIntegrity`) implemented in `BaseController` and called appropriately (e.g., during `requireLogin`). Session lifetime configured.
*   **Status:** Good practices implemented.

### 10.3 CSRF Protection (CRITICAL ISSUE IDENTIFIED)

*   **Intention:** `config.php` enables CSRF (`SECURITY_SETTINGS['csrf']['enabled']`). `index.php` calls `SecurityMiddleware::validateCSRF()` for POST requests. `BaseController` has `getCsrfToken()` (generates/retrieves from session) and `validateCSRFToken()`.
*   **Observed Failure:** View files (`home.php`, `footer.php`, `cart.php`, sample HTML) **do not output the generated CSRF token** into the hidden `<input type="hidden" name="csrf_token" value="...">`. The value is empty.
*   **Impact:** All POST requests (newsletter, add-to-cart, cart updates, login, register, etc.) are vulnerable to Cross-Site Request Forgery attacks as the validation check in `index.php` will always fail against an empty/missing token from the form, or worse, might pass if validation is faulty against an empty session token. **This effectively bypasses CSRF protection.**
*   **Required Fix:**
    1.  Ensure `BaseController::getCsrfToken()` or a similar mechanism reliably generates/retrieves the token from `$_SESSION['csrf_token']`.
    2.  Pass this token to *all* views containing forms that require protection.
    3.  Modify *all* relevant view files (`.php` templates) to correctly output the token:
        ```php
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
        ```
        (Where `$csrfToken` is the variable holding the token passed to the view).
    4.  Ensure AJAX POST requests correctly retrieve this token from the DOM and include it in their payload.

### 10.4 Security Headers & CSP Issue

*   **Implementation:** Headers defined in `config.php` (`SECURITY_SETTINGS['headers']`) and likely applied via `BaseController` constructor or `SecurityMiddleware::apply()`. Includes `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `HSTS`, etc.
*   **CSP Issue:** The configured Content Security Policy allows `'unsafe-inline'` for `script-src` and `style-src`.
    ```
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://js.stripe.com 'unsafe-inline'; style-src 'self' 'unsafe-inline'; ..."
    ```
*   **Impact:** Reduces protection against XSS if attacker can inject inline scripts or styles.
*   **Recommendation:** Refactor JS to remove inline event handlers (`onclick`) and inline `<script>` blocks. Move styles to CSS files. Aim to remove `'unsafe-inline'`. Investigate if Stripe requires it or if nonce/hash can be used.

### 10.5 Rate Limiting (Mechanism Unclear)

*   **Intention:** Configured in `config.php`.
*   **Implementation:** `BaseController` has multiple conflicting helper methods (`isRateLimited` using Session, `checkRateLimit` mentioning Redis, `validateRateLimit` using APCu). It's unclear which is active or intended.
*   **Impact:** Rate limiting may not function correctly or consistently.
*   **Recommendation:** Standardize on one mechanism (APCu for single server, Redis/Memcached for multi-server). Ensure required extensions are available. Refactor `BaseController` to use only the chosen method. Apply checks consistently to sensitive endpoints (login, register, password reset).

### 10.6 File Uploads & Permissions

*   **Validation:** `BaseController::validateFileUpload` checks errors, size (`max_size`), MIME type (`allowed_types` from config). `scan_malware` option exists but implementation isn't shown.
*   **Storage:** Secure storage (outside webroot or with execution disabled) is recommended but not specified in current code/docs. Safe filename generation needed.
*   **Permissions:** Restrictive filesystem permissions needed (web server read-only for most files, write access only where necessary like uploads/logs). `config.php` should be read-only for the web server.

### 10.7 Audit Logging & Error Handling

*   **Audit:** `BaseController::logAuditTrail` (to DB table) and `logSecurityEvent` (to log file specified in config) provide logging capabilities. Need consistent application for significant events.
*   **Error:** `ErrorHandler::init` registers handlers. Logs errors (destination configured in `config.php`), shows generic error page (`views/error.php`). `display_errors=Off` must be set in production.

---

## 11. Extensibility & Onboarding

### 11.1 Adding Features, Pages, or Controllers

1.  Create `YourController extends BaseController` in `controllers/`.
2.  Create view file(s) in `views/`.
3.  Add a `case 'yourpage':` block in `index.php`'s main switch:
    *   `require_once __DIR__ . '/controllers/YourController.php';`
    *   `$controller = new YourController($pdo);`
    *   `$controller->yourActionMethod();` (which might render the view or return JSON).
4.  Add links in navigation/other views.
5.  (Optional) Create `YourModel` in `models/`.
6.  (Optional) Update DB schema and Model methods.

### 11.2 Adding Products, Categories, and Quiz Questions

*   **Products/Categories:** Requires direct DB manipulation or building an admin interface. If adding fields (like for enhanced product view), requires schema changes (`ALTER TABLE`) and controller/model query updates.
*   **Quiz:** Requires modifying `QuizController` logic/mapping or ideally moving questions/logic to the database.

### 11.3 Developer Onboarding Checklist

1.  Setup LAMP/LEMP stack (Apache/Nginx, PHP 8+, MySQL/MariaDB). Enable Apache `mod_rewrite`.
2.  Clone repository.
3.  Create MySQL database (`the_scent`) and user (`scent_user`). Grant privileges.
4.  Import database schema: `mysql -u scent_user -p the_scent < db/schema.sql` (or the `.txt` file).
5.  Copy/Rename `.env.example` to `.env` (if implemented) OR directly update `config.php` with correct DB credentials, `BASE_URL`.
6.  Set file permissions: Make `config.php` readable only by appropriate user. Ensure web server has write access *only* to necessary directories (e.g., logs, uploads - if implemented).
7.  Configure Apache VirtualHost: `DocumentRoot` to the project root (e.g., `/cdrom/project/The-Scent-oa5`), `AllowOverride All` for the directory to enable `.htaccess`. Restart Apache.
8.  Browse the site. Check Apache/PHP error logs for issues.
9.  **CRITICAL:** Implement the CSRF token fix (Section 10.3) before extensive testing/use.

### 11.4 Testing & Debugging Notes

*   Use browser Developer Tools (Network tab for AJAX, Console for JS errors).
*   Check PHP/Apache error logs.
*   Use `error_log()` or `var_dump()`/`die()` for debugging PHP variables/flow. Consider Xdebug.
*   **Key Areas to Debug/Verify:**
    *   **CSRF Token implementation.**
    *   Product data fetching and display (especially on detail page).
    *   AJAX request/response cycles for cart and newsletter (check JSON validity, success status, messages).
    *   Session variable state (`cart_count`, `user_id`).
    *   Rate limiting functionality (if enabled/configured).
    *   JavaScript redundancy and consolidation.

---

## 12. Future Enhancements & Recommendations

*   **Autoloader:** Implement PSR-4 autoloading (via Composer or manually) to replace `require_once`.
*   **Dependency Management:** Use Composer.
*   **Routing:** Replace `index.php` switch with a dedicated Router class (e.g., nikic/fast-route, Aura.Router) for cleaner route definitions.
*   **Templating Engine:** Use Twig or BladeOne for better view logic separation and features like inheritance, auto-escaping.
*   **Environment Config:** Use `.env` files (e.g., `vlucas/phpdotenv`) for environment-specific settings.
*   **Database Migrations:** Use a tool like Phinx or Doctrine Migrations to manage schema changes.
*   **Testing:** Implement unit tests (PHPUnit) for Models, Controllers, core logic. Consider integration/browser tests.
*   **API:** Develop a RESTful API for potential future mobile app or decoupled frontend.
*   **Admin Panel:** Build a comprehensive admin interface for managing products, orders, users, etc.
*   **Refactor BaseController:** Some logic (like specific rate limiting, advanced validation) could potentially move to dedicated services/middleware classes.

---

## 13. Appendices

### A. Key File Summaries

| File/Folder                 | Purpose                                                                                        |
| :-------------------------- | :--------------------------------------------------------------------------------------------- |
| `index.php`                 | Entry point, core includes, routing (switch), CSRF validation, controller/view dispatch          |
| `config.php`                | DB credentials, App settings, Security config (`SECURITY_SETTINGS`), API keys, Email config    |
| `css/style.css`             | Custom CSS rules                                                                               |
| `.htaccess`                 | Apache URL rewriting (to `index.php`), potentially access control                              |
| `includes/db.php`           | Creates global `$pdo` connection                                                               |
| `includes/auth.php`         | `isLoggedIn()`, `isAdmin()` session helpers                                                    |
| `includes/SecurityMiddleware.php` | Static helpers: `apply()`, `validateInput()`, `validateCSRF()`. May support BaseController. |
| `includes/ErrorHandler.php` | Registers error handlers via `init()`.                                                         |
| `controllers/BaseController.php` | Abstract base: `$db`, JSON/redirect/render helpers, validation, auth checks, CSRF, logging |
| `controllers/*Controller.php` | Module-specific logic handlers extending BaseController                                        |
| `models/*.php`              | Entity-specific DB interaction logic (e.g., `Product::getById`)                                 |
| `views/*.php`               | HTML/PHP templates                                                                             |
| `views/layout/header.php`   | Header, nav, asset includes, session data display (cart count, login status, flash msg)        |
| `views/layout/footer.php`   | Footer, JS init (AOS, Particles), **global AJAX handlers (cart, newsletter)**, `showFlashMessage` |

### B. Glossary

*   **MVC:** Model-View-Controller architectural pattern.
*   **CSRF:** Cross-Site Request Forgery. Attack tricking users into unwanted actions. Requires token validation.
*   **XSS:** Cross-Site Scripting. Attack injecting malicious scripts. Prevented by output escaping (`htmlspecialchars`).
*   **SQLi:** SQL Injection. Attack manipulating database queries. Prevented by Prepared Statements.
*   **PDO:** PHP Data Objects. Standard PHP database access layer.
*   **Tailwind CSS:** Utility-first CSS framework.
*   **AOS.js:** Animate On Scroll JavaScript library.
*   **Particles.js:** Particle animation JavaScript library.
*   **AJAX:** Asynchronous JavaScript and XML. Used for background requests (e.g., updating cart without page reload).
*   **CDN:** Content Delivery Network. Hosts libraries (Tailwind, fonts) for faster delivery.
*   **`.htaccess`:** Apache configuration file for directory-level settings (like URL rewriting).
*   **Session:** Server-side mechanism to store user data across multiple requests.
*   **Flash Message:** Temporary message stored in session, displayed once (e.g., "Item added successfully").

### C. Code Snippets and Patterns

#### Routing/Dispatch Pattern in `/index.php`

```php
// (Simplified inside try...catch block)
$page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string');
$action = SecurityMiddleware::validateInput($_GET['action'] ?? 'index', 'string');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CRITICAL: This check currently fails if forms don't output the token correctly.
    SecurityMiddleware::validateCSRF();
}

// $pdo is available from includes/db.php

switch ($page) {
    case 'home':
        require_once __DIR__ . '/controllers/ProductController.php';
        $productController = new ProductController($pdo);
        $productController->showHomePage(); // Method likely includes views/home.php
        break;
    case 'product':
        require_once __DIR__ . '/controllers/ProductController.php';
        $productController = new ProductController($pdo);
        $productId = SecurityMiddleware::validateInput($_GET['id'] ?? null, 'int');
        $productController->showProduct($productId); // Method includes views/product_detail.php
        break;
    case 'cart':
        require_once __DIR__ . '/controllers/CartController.php';
        $controller = new CartController($pdo);

        if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            // Controller handles validation, logic, and JSON response internally
            $controller->addToCart();
            // jsonResponse exits script
        } else {
            // Default: Show cart page
            $cartItems = $controller->getCartItems(); // Fetch data
            // $total = ...; // Fetch total
            require_once __DIR__ . '/views/cart.php'; // Include view
        }
        break;
    // ... other cases ...
    default:
        http_response_code(404);
        require_once __DIR__ . '/views/404.php';
        break;
}
```

#### Correct CSRF Token Implementation Pattern

```php
<?php // --- In BaseController or wherever token is needed before view ---
// Method to make token available (e.g., called before rendering view)
protected function prepareViewData(array $data = []): array {
    $data['csrfToken'] = $this->getCsrfToken(); // Assuming getCsrfToken generates/retrieves from session
    // Add other common view data...
    return $data;
}

// Calling renderView
$viewData = $this->prepareViewData(['product' => $productData]);
echo $this->renderView('product_detail', $viewData);

// --- In View File (e.g., product_detail.php form) --- ?>
<form action="..." method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <!-- Other form fields -->
    <button type="submit">Submit</button>
</form>

<?php // --- In JavaScript sending AJAX POST --- ?>
<script>
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
    if (csrfToken) {
        const formData = new FormData(yourFormElement); // Or URLSearchParams
        formData.append('csrf_token', csrfToken);

        fetch('/index.php?page=cart&action=add', {
            method: 'POST',
            body: formData // Or new URLSearchParams(formData) if using that
            // ... headers ...
        })
        // ... .then() handling ...
    } else {
        console.error('CSRF Token not found in form!');
        // Display error to user
    }
</script>
```

---
https://drive.google.com/file/d/10gCnL8NJp79PUjHWxDtMcW4-Nj5In661/view?usp=sharing, https://drive.google.com/file/d/126KjzuTW6OQd1YXyc5oKi7XUArOEP96m/view?usp=sharing, https://drive.google.com/file/d/1BM2Pr-Q-dRs2lQtzFYIABmcqcFVllSsN/view?usp=sharing, https://drive.google.com/file/d/1Bp0-5HMlGKICNb4U_YbJ_mFD35T2YfOf/view?usp=sharing, https://drive.google.com/file/d/1FXsDOP7FCoP1cUYxDI4hEC4AXRGjQwAC/view?usp=sharing, https://drive.google.com/file/d/1GDqixZr8XpKYZgWn7p7_BUJVTGbp7c8p/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221Tsva1prccYU-Un90emc34sB2sHhMLXja%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1XyxEK8Yb9GQZ0Ahk1P_Wf-FkfF965Omj/view?usp=sharing, https://drive.google.com/file/d/1bDNZgMUeBQNrCoO8Sr-w5Z0N0dCFDJjU/view?usp=sharing, https://drive.google.com/file/d/1eUiM9-m0SALwdiqcRWmeYkDz-17JUIoj/view?usp=sharing, https://drive.google.com/file/d/1tcI9kfjgyvoAe8xjYs0xfOxpCYYFYp0H/view?usp=sharing
