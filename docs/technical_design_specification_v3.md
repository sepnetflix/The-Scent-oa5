# The Scent – Technical Design Specification (April 21, 2025)

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
    *   6.3 [JavaScript: Interactivity, AOS.js, Particles.js](#javascript-interactivity-aosjs-particlesjs)
7.  [Key Pages & Components](#key-pages--components)
    *   7.1 [Home/Landing Page (views/home.php)](#homelanding-page-viewshomephp)
    *   7.2 [Header and Navigation (views/layout/header.php)](#header-and-navigation-viewslayoutheaderphp)
    *   7.3 [Footer and Newsletter (views/layout/footer.php)](#footer-and-newsletter-viewslayoutfooterphp)
    *   7.4 [Product Grid & Cards](#product-grid--cards)
    *   7.5 [Shopping Cart (views/cart.php)](#shopping-cart-viewscartphp)
    *   7.6 [Quiz Flow & Personalization](#quiz-flow--personalization)
8.  [Backend Logic & Core PHP Components](#backend-logic--core-php-components)
    *   8.1 [Includes: Shared Logic (includes/)](#includes-shared-logic-includes)
    *   8.2 [Controllers: Business Logic Layer (controllers/ & BaseController.php)](#controllers-business-logic-layer-controllers--basecontrollerphp)
    *   8.3 [Database Abstraction (includes/db.php)](#database-abstraction-includesdbphp)
    *   8.4 [Security Middleware & Error Handling](#security-middleware--error-handling)
    *   8.5 [Session, Auth, and User Flow](#session-auth-and-user-flow)
9.  [Database Design](#database-design)
    *   9.1 [Entity-Relationship Model](#entity-relationship-model)
    *   9.2 [Main Tables & Sample Schemas](#main-tables--sample-schemas)
    *   9.3 [Data Flow Examples](#data-flow-examples)
10. [Security Considerations](#security-considerations)
    *   10.1 [Input Sanitization & Validation](#input-sanitization--validation)
    *   10.2 [Session Management](#session-management)
    *   10.3 [CSRF Protection](#csrf-protection)
    *   10.4 [Security Headers & Rate Limiting](#security-headers--rate-limiting)
    *   10.5 [File Uploads & Permissions](#file-uploads--permissions)
    *   10.6 [Audit Logging & Error Handling](#audit-logging--error-handling)
11. [Extensibility & Onboarding](#extensibility--onboarding)
    *   11.1 [Adding Features, Pages, or Controllers](#adding-features-pages-or-controllers)
    *   11.2 [Adding Products, Categories, and Quiz Questions](#adding-products-categories-and-quiz-questions)
    *   11.3 [Developer Onboarding Checklist](#developer-onboarding-checklist)
    *   11.4 [Testing & Debugging](#testing--debugging)
12. [Future Enhancements & Recommendations](#future-enhancements--recommendations)
13. [Appendices](#appendices)
    *   A. [Key File Summaries](#a-key-file-summaries)
    *   B. [Glossary](#b-glossary)
    *   C. [Code Snippets and Patterns](#c-code-snippets-and-patterns)

---

## 1. Introduction

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document is the definitive technical design specification for The Scent’s codebase. It is intended to offer deep insight into the system’s structure, logic, and flow, and to serve as a comprehensive onboarding and reference guide for both current developers and future maintainers.

---

## 2. Project Philosophy & Goals

*   **Security First:** All data input and user interactions are validated and sanitized. Strong session and CSRF protection are enforced.
*   **Simplicity & Maintainability:** Clear, modular code structure. No over-engineered abstractions. Direct `require_once` usage observed in `index.php` fits this philosophy.
*   **Extensibility:** Easy to add new features, pages, controllers, or views.
*   **Performance:** Direct routing, optimized queries, and minimized external dependencies. (Note: CDN usage for frontend libraries introduces external dependencies).
*   **Modern User Experience:** Responsive design, smooth animations (AOS.js, Particles.js), and AJAX interactions where implemented (cart updates/removal, newsletter).
*   **Transparency:** No magic – all application flow and routing is explicit in `index.php`'s include and switch logic.
*   **Accessibility & SEO:** Semantic HTML, ARIA attributes where needed, and meta tags for discoverability.

---

## 3. System Architecture Overview

### 3.1 High-Level Workflow

```
[Browser/Client]
   |
   | (HTTP request)
   v
[Apache2 Web Server] <-- DocumentRoot points to project root
   |
   | (URL rewriting via .htaccess in root)
   v
[index.php]  <-- ENTRY POINT (in project root)
   |
   | (Routing logic, includes core files, calls Middleware, validates CSRF)
   v
[Controller]  (e.g., ProductController, CartController - often included within route case)
   |           (May inherit from BaseController)
   |
   | (Business logic, DB access via includes/db.php or BaseController methods)
   v
[Model or DB Layer]  (e.g., models/Product.php, includes/db.php)
   |
   | (Fetch/prepare data)
   v
[View]  (e.g., views/home.php - often included directly from index.php route case or via BaseController::renderView)
   |
   | (HTML/CSS/JS output)
   v
[Browser/Client] <-- Renders HTML, executes JS (Tailwind, AOS, Particles, custom)
```

### 3.2 Request-Response Life Cycle

1.  **Request Initiation:** User navigates to any URL (e.g., `/`, `/index.php?page=products`, `/products` if clean URLs are configured).
2.  **.htaccess Rewrite:** If clean URLs are enabled via `/.htaccess`, requests not matching existing files/directories or specific exclusions (like `test_*.php`) are rewritten to `/index.php`, preserving query strings. (Requires Apache `DocumentRoot` set to project root and `AllowOverride All`).
3.  **Initialization:** `/index.php` defines `ROOT_PATH`, includes core files (`config.php`, `includes/*.php`), initializes error handling (`ErrorHandler::init()`), and applies security middleware (`SecurityMiddleware::apply()`).
4.  **Routing:** `index.php` determines the requested `$page` and `$action` from `$_GET` parameters, performing basic validation (`SecurityMiddleware::validateInput()`).
5.  **CSRF Check:** If the request method is POST, `SecurityMiddleware::validateCSRF()` is called to validate the CSRF token submitted in the request data.
6.  **Controller/View Dispatch:** A `switch` statement based on `$page` typically:
    *   Includes the necessary controller file(s) (e.g., `require_once __DIR__ . '/controllers/CartController.php';`) *within* the specific `case`.
    *   Instantiates the controller, passing the `$pdo` database connection (which is assumed to be available globally or returned from `includes/db.php`).
    *   Calls a method on the controller (e.g., `$productController->showHomePage();`) OR directly includes the relevant view file (`require_once __DIR__ . '/views/cart.php';`) after potentially fetching data.
7.  **Controller Action:** Controller methods (often inheriting from `BaseController`) execute business logic, interact with the database (often using `$this->db`), validate input further, and prepare data for the view.
8.  **View Rendering:** The included view file (`views/*.php`) generates HTML output, often including layout partials (`views/layout/header.php`, `views/layout/footer.php`). Data is accessed via variables set before the include or passed via `BaseController::renderView`. Output is escaped using `htmlspecialchars()`.
9.  **Response:** Output (HTML, CSS, JS) is sent to the browser.
10. **Client-Side Execution:** The browser renders HTML, applies CSS (from `/css/style.css` and Tailwind CDN), and executes JavaScript (AOS, Particles, mobile menu, AJAX for newsletter/cart updates/cart removal).
11. **AJAX Interactions:** Client-side JS sends `fetch` requests (typically POST) back to `index.php` with appropriate `page` and `action` parameters for tasks like newsletter subscription or cart updates/removal. The corresponding controller action handles the request and usually responds with JSON (using `jsonResponse` helper from `BaseController`).

---

## 4. Directory & File Structure

### 4.1 Folder Map

```
/ (project root - e.g., /cdrom/project/The-Scent-oa5) <-- Apache DocumentRoot points here
|-- index.php              # Main entry script (routing, dispatch, includes core files)
|-- config.php             # Environment, DB, and app configuration
|-- css/
|   |-- style.css          # Custom CSS styles (used alongside CDN Tailwind)
|-- images/                # Public image assets (product images, UI elements, etc.)
|   |-- about/
|   |-- backgrounds/
|   |-- icons/
|   |-- logo/
|   |-- products/
|   |-- textures/
|   |-- ui/
|   |-- ... (various image files)
|-- videos/                # Public video assets (e.g., hero background)
|   |-- hero.mp4
|-- particles.json         # Particles.js configuration file
|-- includes/              # Shared PHP utility/core files
|   |-- auth.php           # User login/logout/session helpers (isLoggedIn, isAdmin)
|   |-- db.php             # Database connection setup (likely defines/returns $pdo)
|   |-- SecurityMiddleware.php # Class/functions for CSRF, input validation, security headers, etc.
|   |-- ErrorHandler.php   # Class/functions for centralized error handling/logging
|   |-- EmailService.php   # Email sending logic (SMTP)
|   |-- ... (test files, backups - should be cleaned up)
|-- controllers/           # Business logic / request handlers
|   |-- BaseController.php # Abstract base class with common helpers
|   |-- ProductController.php
|   |-- CartController.php
|   |-- CheckoutController.php
|   |-- AccountController.php
|   |-- QuizController.php
|   |-- ... (others for newsletter, payment, tax, inventory etc.)
|-- models/                # Data representation / Database interaction logic
|   |-- Product.php
|   |-- User.php
|   |-- Order.php
|   |-- Quiz.php
|-- views/                 # HTML templates (server-rendered PHP files)
|   |-- home.php           # Main landing page view
|   |-- products.php       # Product listing view
|   |-- product_detail.php # Individual product page view
|   |-- cart.php           # Shopping cart view
|   |-- checkout.php       # Checkout process view
|   |-- register.php       # Registration view
|   |-- login.php          # Login form view
|   |-- quiz.php           # Scent quiz interface view
|   |-- quiz_results.php   # Quiz results view
|   |-- error.php          # Error display view
|   |-- 404.php            # Page not found view
|   |-- account/           # Views related to user accounts
|   |-- admin/             # Views for administrative functions
|   |-- emails/            # Email templates
|   |-- layout/
|   |   |-- header.php     # Sitewide header/nav view partial
|   |   |-- footer.php     # Sitewide footer/newsletter/social view partial
|-- .htaccess              # Apache URL rewrite rules & configuration
|-- .env                   # (Optional) Environment variables, secrets (should NOT be web accessible)
|-- README.md              # Project intro/instructions
|-- technical_design_specification_v2.md # This document
```

### 4.2 Key Files Explained

*   **index.php**: Application entry point in the project root. Includes core files, initializes error handling (`ErrorHandler::init()`) and security middleware (`SecurityMiddleware::apply()`), performs routing based on `$_GET` params, validates CSRF for POST requests (`SecurityMiddleware::validateCSRF()`), includes/instantiates controllers *within* route cases, and includes view files.
*   **config.php**: Central configuration for Database credentials, email settings, security parameters (`SECURITY_SETTINGS` constant/array), potentially API keys. Located in the project root.
*   **css/style.css**: Contains custom CSS rules, complementing styles provided by the Tailwind CSS framework (loaded via CDN).
*   **particles.json**: Configuration for the Particles.js library used for animated backgrounds.
*   **.htaccess**: Located in the project root. Configures Apache settings, primarily `RewriteRule` directives to route non-file/directory requests to `index.php` for handling, enabling clean URLs. Excludes certain paths (e.g., `test_*.php`) from rewriting.
*   **includes/db.php**: Establishes the PDO database connection using credentials from `config.php`. Needs to make the `$pdo` object available (e.g., return it, define globally) for `index.php` and controllers. Configures PDO error mode.
*   **includes/auth.php**: Provides authentication-related helper functions like `isLoggedIn()`, `isAdmin()`, potentially handling session checks.
*   **includes/SecurityMiddleware.php**: Provides static methods like `apply()` (global setup), `validateInput()`, `validateCSRF()`. May also provide instance methods used by `BaseController` for CSRF token generation/validation, rate limiting. (Static/instance usage might need standardization).
*   **includes/ErrorHandler.php**: Provides `init()` method called in `index.php` to register handlers. Likely contains the handler logic to log errors and display safe error pages. `BaseController` may use `ErrorHandler::logError()`.
*   **controllers/BaseController.php**: Abstract base class extended by most other controllers. Provides shared functionality: database connection (`$this->db`), middleware access (`$this->securityMiddleware`), email service (`$this->emailService`), helper methods for JSON/redirect responses, view rendering (`renderView`), validation, auth checks (`requireLogin`/`Admin`), CSRF handling, flash messages, DB transactions, rate limiting, logging (`logAuditTrail`, `logSecurityEvent`), file uploads.
*   **controllers/*Controller.php**: Specific controllers handling logic for different application modules (Products, Cart, Checkout, Account, Quiz, etc.). Instantiated in `index.php`, extend `BaseController`.
*   **models/*.php**: Classes representing data entities (Product, User, Order, Quiz) and potentially containing database interaction logic specific to those entities.
*   **views/layout/header.php**: Generates common site header (logo, navigation, icons), includes CSS/JS assets (CDNs, custom CSS), handles dynamic elements like login status and cart count (from `$_SESSION['cart_count']`), displays server-side flash messages (from `$_SESSION['flash_message']`).
*   **views/layout/footer.php**: Generates common site footer (links, newsletter, social icons, copyright), includes/initializes JavaScript (AOS, Particles, custom AJAX handlers for newsletter). 
*   **views/*.php**: Individual page templates combining HTML and PHP to display content. Included by `index.php` or controller methods (e.g., `BaseController::renderView`).

---

## 5. Routing and Application Flow

### 5.1 URL Routing via .htaccess

*   **File:** `/.htaccess` (in project root).
*   **Mechanism:** Uses Apache's `mod_rewrite`.
    *   `RewriteEngine On` enables the module.
    *   `RewriteCond` directives check if the request is *not* for an existing file (`!-f`) or directory (`!-d`) and *not* for specific patterns like `/test_*.php` or `/sample_*.html`.
    *   If conditions are met, `RewriteRule ^ index.php [L]` rewrites the request URI to `/index.php`, stopping further rule processing (`[L]`). Query strings are appended.
*   **Requirement:** Relies on Apache `DocumentRoot` pointing to the project root and `AllowOverride` being enabled.
*   **Result:** All "clean URL" requests (or requests directly to `index.php` with query parameters) are handled by the single entry point script `/index.php`.

### 5.2 index.php: The Application Entry Point

**Location:** Project Root (`/index.php`)

**Key Responsibilities:**
*   Define `ROOT_PATH` constant.
*   Include essential files from `/includes/` (`config.php`, `db.php`, `auth.php`, `SecurityMiddleware.php`, `ErrorHandler.php`) using `require_once`.
*   Initialize error handling via `ErrorHandler::init()`.
*   Apply global security middleware settings via `SecurityMiddleware::apply()`.
*   Determine requested page (`$page`) and action (`$action`) from `$_GET` parameters, using `SecurityMiddleware::validateInput()`.
*   Validate CSRF token for POST requests using `SecurityMiddleware::validateCSRF()`.
*   **(Database Connection):** Assumes `$pdo` database connection object is made available (e.g., returned/defined by `includes/db.php`).
*   Route requests using a `switch ($page)` statement:
    *   Include necessary controller file(s) *within* the case block (e.g., `require_once __DIR__ . '/controllers/CartController.php';`).
    *   Instantiate the relevant controller (e.g., `$controller = new CartController($pdo);`).
    *   Call a method on the controller to handle the request/action (e.g., `$productController->showHomePage();`, `$controller->addToCart(...)`) OR directly include a view file (`require_once __DIR__ . '/views/register.php';`).
*   Handle default/unknown pages with a 404 response and view (`require_once __DIR__ . '/views/404.php';`).
*   Wrap the main routing logic in a `try...catch` block, logging PDOExceptions specifically and potentially re-throwing others for the main error handler.

### 5.3 Controller Dispatch & Action Flow

*   Controllers (`controllers/*.php`) encapsulate logic for specific modules. Most extend `controllers/BaseController.php`.
*   They are included and instantiated within the routing logic of `index.php` based on the requested `$page`.
*   Controller methods handle specific `$action`s or default page views.
*   Utilize the injected `$pdo` object (via constructor, inherited `$this->db`) or Models (`models/*.php`) for database operations.
*   Input validation is performed using `SecurityMiddleware::validateInput()` (called from `index.php`) and potentially controller-level helpers (e.g., `BaseController::validateInput`).
*   Security checks (`isLoggedIn()`, `isAdmin()`, `BaseController::requireLogin`/`Admin`) gate access.
*   They prepare data needed by the view.
*   They conclude by:
    *   Letting `index.php` include the view file after setup.
    *   Calling `BaseController::renderView()` to render a view with data.
    *   Sending a JSON response using `BaseController::jsonResponse` (for AJAX: cart update/remove, newsletter).
    *   Performing a redirect using `BaseController::redirect` or `header()`.

### 5.4 Views: Templating and Rendering

*   Views (`views/*.php`) generate HTML output using mixed PHP and HTML.
*   Included via `require_once` from `index.php` or rendered via `BaseController::renderView()` (which uses output buffering).
*   Data is accessed via variables set in the calling scope or passed explicitly to `renderView`.
*   Include layout partials (`views/layout/header.php`, `views/layout/footer.php`).
*   Output MUST be escaped using `htmlspecialchars()` unless safe HTML is intended.
*   Styling uses CSS classes from `/css/style.css` and the Tailwind CDN.
*   JavaScript for interactivity is loaded via CDNs or inline/included scripts (often in `footer.php`).

---

## 6. Frontend Architecture

### 6.1 CSS (css/style.css), Tailwind (CDN), and Other Libraries

*   **Styling Approach:** Hybrid.
    *   **Tailwind CSS:** Included via CDN `<script>` in `views/layout/header.php`. Theme configured inline. Provides utility classes.
    *   **Custom CSS:** `/css/style.css` loaded locally. Contains custom styles, component rules, overrides.
*   **Other Libraries (Loaded via CDN in `views/layout/header.php`):**
    *   Google Fonts (`Cormorant Garamond`, `Montserrat`, `Raleway`).
    *   Font Awesome (icons).
    *   AOS.js (CSS and JS).
    *   Particles.js (JS).

### 6.2 Responsive Design and Accessibility

*   **Responsive Design:** Primarily uses Tailwind's responsive prefixes (`md:`, `lg:`). Custom media queries may exist in `/css/style.css`. JS handles mobile menu.
*   **Accessibility:**
    *   Uses semantic HTML elements.
    *   `aria-label` used on icon buttons.
    *   Basic keyboard navigation expected; mobile menu has escape key closing.
    *   Visual audit needed for WCAG compliance (contrast, sizes).

### 6.3 JavaScript: Interactivity, AOS.js, Particles.js

*   **Libraries Initialization:** AOS.js (`AOS.init()`) and Particles.js (`particlesJS.load`) initialized via scripts (likely in `footer.php`, but AOS link/script also seen in header). Uses `/particles.json` config.
*   **Custom Interactivity:**
    *   **Mobile Menu Toggle:** JS in `header.php` handles toggle.
    *   **Sticky Header:** JS in `home.php` adds/removes class on scroll.
    *   **AJAX Operations:** Using `fetch` API.
        *   *Newsletter Subscription:* Forms (`#newsletter-form`, `#newsletter-form-footer` in `home.php`/`footer.php`) submit via AJAX to `index.php?page=newsletter&action=subscribe`, expecting JSON. Feedback via `alert()`.
        *   *Cart Item Removal (`cart.php`):* Button (`.remove-item`) triggers AJAX POST to `index.php?page=cart&action=remove`, expecting JSON. Updates UI, totals, header count.
        *   *Cart Quantity Update (`cart.php`):* Form (`#cartForm`) submission via AJAX POST to `index.php?page=cart&action=update`, expecting JSON. Updates totals, header count. +/- buttons update input value client-side.
    *   **Flash Messages:** Multiple mechanisms exist:
        *   Server-side: `$_SESSION['flash_message']` set by controllers (e.g., using `BaseController::setFlashMessage`), displayed/unset by `header.php`.
        *   Client-side (`home.php`): `showFlashMessage()` function used by add-to-cart AJAX handler.
        *   Client-side (`footer.php`): Newsletter AJAX uses `alert()`.
    *   **Add-to-Cart:** JS handlers (`.add-to-cart` in `home.php`/`footer.php`) trigger AJAX POST to `index.php?page=cart&action=add`, expecting JSON and using `showFlashMessage`.

---

## 7. Key Pages & Components

### 7.1 Home/Landing Page (views/home.php)

*   Includes layouts. Displays Hero, About, Featured Products, Benefits, Quiz Finder, Newsletter, Testimonials sections.
*   Expects `$featuredProducts` array from `ProductController::showHomePage()`.
*   Uses `htmlspecialchars()` for product data.
*   Conditionally shows "Add to Cart" (`.add-to-cart`, `data-product-id`) or "Out of Stock" based on `stock_quantity`.
*   Contains JS for AOS, Particles, sticky header, newsletter AJAX (`alert`), and the Add-to-Cart AJAX handler (`showFlashMessage`).

### 7.2 Header and Navigation (views/layout/header.php)

*   Includes `includes/auth.php`. Generates header with logo, nav links, icons.
*   Shows Account/Login link based on `isLoggedIn()`.
*   Displays cart count from `$_SESSION['cart_count']`.
*   Includes CSS/JS assets (CDNs, local CSS).
*   Contains JS for mobile menu toggle.
*   Displays/unsets server-side flash messages from `$_SESSION['flash_message']`.

### 7.3 Footer and Newsletter (views/layout/footer.php)

*   Generates footer with links, contact info, newsletter form (`#newsletter-form-footer`), social icons, copyright, payment icons.
*   Includes JS for AOS/Particles init, newsletter form AJAX (`alert`), and Add-to-Cart AJAX handler (`showFlashMessage`).

### 7.4 Product Grid & Cards

*   Used on Home page and likely Product Listing page. Responsive grid via Tailwind.
*   Card shows image, name, category/desc, "View Details" link, and conditional "Add to Cart" (`.add-to-cart`, `data-product-id`) or "Out of Stock" button.

### 7.5 Shopping Cart (views/cart.php)

*   Includes layouts. Expects `$cartItems` array and `$total` variable from `CartController`.
*   Displays item list (image, name, price, quantity input, subtotal, remove button).
*   Shows summary (Subtotal, Shipping, Total).
*   Wraps items in `#cartForm` targeting `action=update`.
*   Buttons: "Update Cart" (triggers AJAX form submit), "Proceed to Checkout" link.
*   Contains JS for: +/- buttons, AJAX item removal (`.remove-item`, expects JSON), AJAX cart update (`#cartForm` submit, expects JSON), client-side UI updates (totals, header count).

### 7.6 Quiz Flow & Personalization

*   Entry via link from Home page (`index.php?page=quiz`).
*   Interface (`views/quiz.php`) displays questions from `QuizController`.
*   Submission POSTs to `index.php?page=quiz&action=submit`.
*   Processing (`QuizController::processQuiz()`) maps answers to recommendations.
*   Results (`views/quiz_results.php`) displays recommendations.
*   Admin analytics section exists (`page=admin&section=quiz_analytics`).
*   Results potentially saved to DB (`quiz_results` table) by controller.

---

## 8. Backend Logic & Core PHP Components

### 8.1 Includes: Shared Logic (includes/)

*   **Location:** `/includes/`. Foundational PHP files.
*   **Key Files:** `auth.php` (helpers `isLoggedIn`, `isAdmin`), `db.php` (PDO setup, provides `$pdo`), `SecurityMiddleware.php` (static methods `apply`, `validateInput`, `validateCSRF`), `ErrorHandler.php` (static `init`, handler logic), `EmailService.php`.

### 8.2 Controllers: Business Logic Layer (controllers/ & BaseController.php)

*   **Location:** `/controllers/`. Specific logic handlers.
*   **Base Class:** `controllers/BaseController.php` (abstract). Provides shared functionality:
    *   Properties: `$db` (PDO), `$securityMiddleware` (instance), `$emailService` (instance).
    *   Initializes security headers.
    *   Helpers: `jsonResponse`, `sendError`, `redirect`, `renderView`, `validateInput`, `validateRequest`, `requireLogin`, `requireAdmin`, `getCsrfToken`, `validateCSRF` (instance), `set/getFlashMessage`, DB transactions, rate limiting (multiple methods), logging (`logAuditTrail`, `logSecurityEvent`), file uploads (`validateFileUpload`).
*   **Specific Controllers:** Extend `BaseController`. Instantiated in `index.php`, passed `$pdo`. Implement action methods. Use inherited helpers.

### 8.3 Database Abstraction (includes/db.php)

*   **Method:** Uses PDO.
*   **Connection:** `includes/db.php` creates PDO instance using `config.php` details. Makes `$pdo` object available to `index.php`.
*   **Configuration:** Sets `PDO::ERRMODE_EXCEPTION` (likely in `BaseController` or `db.php`). `PDO::FETCH_ASSOC` recommended.
*   **Usage:** Controllers use `$this->db` (inherited from `BaseController`). Prepared statements (`prepare`, `execute`) MUST be used.

### 8.4 Security Middleware & Error Handling

*   **SecurityMiddleware.php (`includes/`):**
    *   *Initialization:* Static `apply()` called in `index.php`.
    *   *CSRF Protection:* Static `validateCSRF()` called in `index.php` for POSTs. Token generation/instance validation helpers likely in `BaseController` (using SecurityMiddleware instance or static methods). Uses session token and form field (`csrf_token`).
    *   *Input Validation:* Static `validateInput()` used in `index.php` and via `BaseController` helpers.
    *   *Security Headers:* Initialized/sent via `BaseController` or `SecurityMiddleware::apply()`.
*   **ErrorHandler.php (`includes/`):**
    *   *Initialization:* Static `init()` called in `index.php` registers handlers.
    *   *Handling:* Catches errors/exceptions. Logs details (`error_log` or custom). Displays generic error page (`views/error.php`).

### 8.5 Session, Auth, and User Flow

*   **Session:** Started securely (via `SecurityMiddleware::apply()` or similar). Secure cookie attributes assumed. ID regenerated on login/logout. `BaseController` provides integrity checks/regeneration helpers.
*   **Authentication:** `isLoggedIn()` helper. `AccountController` handles login (`password_verify`), sets `$_SESSION['user_id']`, `$_SESSION['user_role']` (potentially `$_SESSION['user']` array). `BaseController::requireLogin`.
*   **Authorization:** `isAdmin()` helper. `BaseController::requireAdmin` checks role.
*   **Cart Count:** `$_SESSION['cart_count']` updated by `CartController`. Displayed by `header.php`.
*   **Flash Messages:** `$_SESSION['flash']` (`message`, `type`) set by `BaseController::setFlashMessage`. Displayed/unset by `header.php`.

---

## 9. Database Design

*(Schema details remain assumed unless schema file provided)*

### 9.1 Entity-Relationship Model
*(Standard e-commerce entities: users, products, categories, orders, order_items, cart_items, quiz_results, newsletter_subscribers. Standard relationships.)*

### 9.2 Main Tables & Sample Schemas
*(Provides plausible SQL CREATE TABLE statements for key entities. Requires validation against actual schema file.)*

### 9.3 Data Flow Examples
*   **Add to Cart:** AJAX POST to `cart/add`. `CartController::addToCart`. Backend responds with JSON. JS updates UI.
*   **Update Cart:** AJAX POST to `cart/update`. `CartController::updateCart`. Backend responds with JSON. JS updates UI.
*   **Remove Cart Item:** AJAX POST to `cart/remove`. `CartController::removeCartItem`. Backend responds with JSON. JS updates UI.
*   **Place Order:** POST to `checkout/process`. `CheckoutController::processCheckout`. Creates DB records, clears cart, processes payment, redirects.
*   **Take Quiz:** POST to `quiz/submit`. `QuizController::processQuiz`. Saves results, displays recommendations view.
*   **Newsletter Signup:** AJAX POST to `newsletter/subscribe`. `NewsletterController::subscribe`. Saves email, responds with JSON. JS shows feedback.

---

## 10. Security Considerations

### 10.1 Input Sanitization & Validation
*   Use `SecurityMiddleware::validateInput()` and `BaseController` validation helpers.
*   Use PDO prepared statements exclusively.
*   Use `htmlspecialchars()` for all HTML output.

### 10.2 Session Management
*   Enforce secure cookie attributes (Secure, HttpOnly, SameSite=Lax/Strict).
*   Regenerate session ID on login/logout. Use `BaseController` integrity checks.
*   Implement session timeouts.

### 10.3 CSRF Protection
*   Session token (`$_SESSION['csrf_token']`).
*   Token in forms (`csrf_token` hidden field) and AJAX data.
*   Validation via `SecurityMiddleware::validateCSRF()` in `index.php` for POSTs.
*   Use `hash_equals()` for comparison.

### 10.4 Security Headers & Rate Limiting
*   **Headers:** Set via `BaseController`. Includes HSTS (if HTTPS), X-Frame-Options, X-Content-Type-Options, Referrer-Policy, basic CSP.
*   **Rate Limiting:** Logic exists in `BaseController` (Session, APCu, Redis methods via `SecurityMiddleware`). Active mechanism/config needs verification. Apply to sensitive endpoints.

### 10.5 File Uploads & Permissions
*   Use `BaseController::validateFileUpload` (checks size, MIME type allowlist).
*   Store uploads securely (outside webroot or execution disabled). Use safe filenames.
*   Apply restrictive file system permissions. Protect `config.php`.

### 10.6 Audit Logging & Error Handling
*   **Audit:** Use `BaseController::logAuditTrail` (DB table) and `BaseController::logSecurityEvent` (log file) for significant events.
*   **Error:** `ErrorHandler::init` registers handlers. Log details, show generic error page (`views/error.php`). `display_errors=Off` in production.

---

## 11. Extensibility & Onboarding

### 11.1 Adding Features, Pages, or Controllers
1.  Create `Controller` (extending `BaseController`).
2.  Create `View(s)`.
3.  Add `case` to `index.php` routing: include/instantiate controller, call method or include view.
4.  Add navigation links.
5.  (Optional) Create `Model`.
6.  (Optional) Update DB schema.

### 11.2 Adding Products, Categories, and Quiz Questions
*   **Products/Categories:** Requires direct DB access or admin interface development.
*   **Quiz Questions:** Requires modifying `QuizController` logic or moving mapping to DB/config.

### 11.3 Developer Onboarding Checklist
1.  Install Apache, PHP 8+, MySQL/MariaDB, Git.
2.  Clone repo.
3.  Update `config.php` (DB credentials, etc.). Protect permissions.
4.  Create DB, import schema, seed data.
5.  Configure Apache VHost: `DocumentRoot` -> project root, `AllowOverride All`. Enable `mod_rewrite`. Restart.
6.  Set file permissions (minimal web server write access).
7.  Test site flows, check logs.

### 11.4 Testing & Debugging
*   Check PHP/Apache error logs.
*   Use browser dev tools.
*   **Note Inconsistencies:** Add-to-Cart AJAX vs. redirect, multiple flash mechanisms, duplicate JS includes (AOS).
*   Manual flow testing & security checks.
*   Use `var_dump`/`error_log` or Xdebug.

---

## 12. Future Enhancements & Recommendations

*(Standard recommendations remain valid: Autoloader, Templating Engine, Framework, API, Admin Panel, Testing, Dependency Management, Docker, CI/CD, Performance Opt., i18n, Accessibility Audit, User Profiles, Analytics.)*

---

## 13. Appendices

### A. Key File Summaries

| File/Folder                 | Purpose                                                                               |
| :-------------------------- | :------------------------------------------------------------------------------------ |
| `index.php`                 | Application entry, routing, core includes, CSRF check, controller/view dispatch         |
| `config.php`                | Environment, DB, email, security configuration (`SECURITY_SETTINGS`)                  |
| `css/style.css`             | Custom CSS rules complementing CDN Tailwind                                            |
| `particles.json`          | Particle animation config for Particles.js                                            |
| `.htaccess`                 | (Root directory) Apache URL rewriting, access control                                 |
| `includes/auth.php`         | Authentication helper functions (isLoggedIn, isAdmin)                                |
| `includes/db.php`           | PDO DB connection setup (provides `$pdo`)                                             |
| `includes/SecurityMiddleware.php` | Security class/functions (CSRF, validation, headers) called by index/BaseController |
| `includes/ErrorHandler.php` | Centralized error/exception handling (init, logging)                                  |
| `controllers/BaseController.php` | Abstract base class providing shared controller helpers (DB, validation, response, etc.) |
| `controllers/*Controller.php` | Specific business logic handlers extending BaseController                           |
| `models/*.php`              | Data entity representation and DB interaction logic                                   |
| `views/*.php`               | HTML/PHP templates for rendering pages                                               |
| `views/layout/header.php`   | Sitewide header, navigation, asset loading, session flash/cart display                 |
| `views/layout/footer.php`   | Sitewide footer, JS initialization, newsletter AJAX, Add-to-Cart AJAX handler          |

### B. Glossary
*(Standard terms: MVC, CSRF, XSS, SQLi, AOS.js, Particles.js, Tailwind, PDO, Session, Flash Message, CDN, AJAX, .htaccess)*

### C. Code Snippets and Patterns

#### Example: Secure Controller Routing in `/index.php` (Cart Add Example)

```php
// (Inside index.php's switch($page) block)
case 'cart':
     require_once ROOT_PATH . '/controllers/CartController.php'; // Included within case
     $controller = new CartController($pdo); // Instantiate controller

     if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
          // Validate CSRF token (already done earlier for all POST requests)
          $productId = SecurityMiddleware::validateInput($_POST['product_id'] ?? null, 'int');
          $quantity = SecurityMiddleware::validateInput($_POST['quantity'] ?? 1, 'int');
          // Delegate logic to controller method
          $controller->addToCart($productId, $quantity);
          // Respond with JSON for AJAX
          $controller->jsonResponse(['success' => true, 'message' => 'Product added to cart', 'cart_count' => $_SESSION['cart_count']]);
     } else if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
          // Assumes updateCart handles AJAX via BaseController::jsonResponse
          $controller->updateCart($_POST['quantities'] ?? []); // Expects array like [product_id => quantity]
     } else if ($action === 'remove' && $_SERVER['REQUEST_METHOD'] === 'POST') {
           // Assumes removeCartItem handles AJAX via BaseController::jsonResponse
           $productId = SecurityMiddleware::validateInput($_POST['product_id'] ?? null, 'int');
           $controller->removeCartItem($productId);
     } else {
          // Default action: Show the cart page
          $cartItems = $controller->getCartItems();
          $total = $controller->getCartTotal();
          // Pass data to view via include scope
          require_once ROOT_PATH . '/views/cart.php';
     }
     break; // End case 'cart'
```

#### Example: CSRF Token Generation/Usage (Conceptual)

```php
<?php
// In BaseController.php or SecurityMiddleware.php (ensure consistent usage)
public function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// In a view (e.g., views/layout/footer.php newsletter form)
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->getCsrfToken(), ENT_QUOTES, 'UTF-8') // Assuming $this is BaseController instance ?>">

// In JavaScript sending AJAX POST (e.g., cart.php removal)
const csrfToken = document.querySelector('input[name="csrf_token"]').value;
formData.append('csrf_token', csrfToken);
// ... fetch call ...

// In index.php (for POST validation)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityMiddleware::validateCSRF(); // Assumes this checks $_POST['csrf_token'] against $_SESSION['csrf_token']
}
?>
```

#### Example: AJAX Cart Removal (JavaScript in `views/cart.php`)

```javascript
// (Assumes updateCartTotal() and updateCartCount() functions exist for UI updates)
document.querySelectorAll('.remove-item').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const csrfTokenInput = document.querySelector('input[name="csrf_token"]');
        const csrfToken = csrfTokenInput ? csrfTokenInput.value : null;

        if (!csrfToken) {
             alert('Security token missing. Please refresh the page.');
             return;
        }
        if (!confirm('Are you sure you want to remove this item?')) {
            return;
        }

        const formData = new URLSearchParams();
        formData.append('product_id', productId);
        formData.append('csrf_token', csrfToken);

        fetch('index.php?page=cart&action=remove', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: formData
        })
        .then(response => response.ok ? response.json() : Promise.reject(`HTTP error ${response.status}`))
        .then(data => {
            if (data.success) {
                this.closest('.cart-item')?.remove(); // Remove row from UI
                updateCartTotal(); // Update displayed totals
                updateCartCount(data.cart_count ?? 0); // Update header count

                // Handle empty cart display
                if ((data.cart_count ?? 0) === 0) {
                    document.querySelector('.cart-items').innerHTML = '<p>Your cart is now empty.</p>';
                    document.querySelector('.cart-summary')?.remove(); // Remove summary
                    document.querySelector('.cart-actions')?.remove(); // Remove buttons
                }
            } else {
                alert(data.message || 'Could not remove item.');
            }
        })
        .catch(error => {
            console.error('Error removing item:', error);
            alert('An error occurred while removing the item.');
        });
    });
});
```

---

**End of Technical Design Specification (April 21, 2025)**

This document reflects the analysis of the provided code snippets and aims to accurately represent the project's current state, including noted inconsistencies (e.g., Add-to-Cart mechanism). It should serve as a guide for development and onboarding, highlighting existing patterns and areas needing potential refactoring or clarification.
