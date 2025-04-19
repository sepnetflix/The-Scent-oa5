https://g.co/gemini/share/6419be0e8a1f  
Okay, I have carefully reviewed the additional file information (`.htaccess`, `index.php`, `views/layout/header.php`, `views/layout/footer.php`, `views/home.php`, `BaseController.php`, `views/cart.php`) against the technical specification document I generated previously.

This review revealed several discrepancies and areas needing refinement for accuracy. Key findings include:
* The `.htaccess` file largely confirms the rewrite rules assumed in the updated spec.
* The `index.php` file shows different initialization calls (`ErrorHandler::init()`, `SecurityMiddleware::apply()`), a different CSRF validation function (`validateCSRF`), and loads most controllers *within* their respective routing `case` statements.
* Crucially, the `index.php` logic for the `cart` page's `add` action performs a server-side redirect (`header('Location: ...'); exit;`), contradicting the AJAX behavior expected by the JavaScript found in `views/home.php` and `views/layout/footer.php`. The JavaScript attempts AJAX calls expecting JSON, while the backend performs a redirect. This indicates an inconsistency in the current implementation.
* The `BaseController.php` provides many useful helper methods (JSON/redirect responses, validation, auth checks, view rendering, logging, etc.) that should be documented as available patterns. It also suggests potential inconsistencies in CSRF function usage (static vs. instance calls) and multiple approaches to rate limiting (session, APCu, Redis).
* `header.php` confirms session usage for cart count (`$_SESSION['cart_count']`) and server-side flash messages (`$_SESSION['flash_message']`).
* `cart.php` confirms AJAX usage for *updating* quantities and *removing* items from the cart, expecting JSON responses.

Based on this detailed validation, the technical specification requires modification to accurately reflect the *actual* implementation shown in these files, including the noted inconsistencies.

Here is the **refined and further updated** Technical Design Specification:

```markdown
# The Scent – Technical Design Specification (Updated - Rev 2)

---

## Table of Contents

1.  [Introduction](#introduction)
2.  [Project Philosophy & Goals](#project-philosophy--goals)
3.  [System Architecture Overview](#system-architecture-overview)
    * 3.1 [High-Level Workflow](#high-level-workflow)
    * 3.2 [Request-Response Life Cycle](#request-response-life-cycle)
4.  [Directory & File Structure](#directory--file-structure)
    * 4.1 [Folder Map](#folder-map)
    * 4.2 [Key Files Explained](#key-files-explained)
5.  [Routing and Application Flow](#routing-and-application-flow)
    * 5.1 https://stackoverflow.com/questions/34981045/url-routing-with-php-and-htaccess(#url-routing-via-htaccess)
    * 5.2 [index.php: The Application Entry Point](#indexphp-the-application-entry-point)
    * 5.3 [Controller Dispatch & Action Flow](#controller-dispatch--action-flow)
    * 5.4 [Views: Templating and Rendering](#views-templating-and-rendering)
6.  [Frontend Architecture](#frontend-architecture)
    * 6.1 [CSS (css/style.css), Tailwind (CDN), and Other Libraries](#css-cssstylecss-tailwind-cdn-and-other-libraries)
    * 6.2 [Responsive Design and Accessibility](#responsive-design-and-accessibility)
    * 6.3 [JavaScript: Interactivity, AOS.js, Particles.js](#javascript-interactivity-aosjs-particlesjs)
7.  [Key Pages & Components](#key-pages--components)
    * 7.1 [Home/Landing Page (views/home.php)](#homelanding-page-viewshomephp)
    * 7.2 [Header and Navigation (views/layout/header.php)](#header-and-navigation-viewslayoutheaderphp)
    * 7.3 [Footer and Newsletter (views/layout/footer.php)](#footer-and-newsletter-viewslayoutfooterphp)
    * 7.4 [Product Grid & Cards](#product-grid--cards)
    * 7.5 [Shopping Cart (views/cart.php)](#shopping-cart-viewscartphp)
    * 7.6 [Quiz Flow & Personalization](#quiz-flow--personalization)
8.  [Backend Logic & Core PHP Components](#backend-logic--core-php-components)
    * 8.1 [Includes: Shared Logic (includes/)](#includes-shared-logic-includes)
    * 8.2 [Controllers: Business Logic Layer (controllers/ & BaseController.php)](#controllers-business-logic-layer-controllers--basecontrollerphp)
    * 8.3 [Database Abstraction (includes/db.php)](#database-abstraction-includesdbphp)
    * 8.4 [Security Middleware & Error Handling](#security-middleware--error-handling)
    * 8.5 [Session, Auth, and User Flow](#session-auth-and-user-flow)
9.  [Database Design](#database-design)
    * 9.1 [Entity-Relationship Model](#entity-relationship-model)
    * 9.2 [Main Tables & Sample Schemas](#main-tables--sample-schemas)
    * 9.3 [Data Flow Examples](#data-flow-examples)
10. [Security Considerations](#security-considerations)
    * 10.1 [Input Sanitization & Validation](#input-sanitization--validation)
    * 10.2 [Session Management](#session-management)
    * 10.3 [CSRF Protection](#csrf-protection)
    * 10.4 [Security Headers & Rate Limiting](#security-headers--rate-limiting)
    * 10.5 [File Uploads & Permissions](#file-uploads--permissions)
    * 10.6 [Audit Logging & Error Handling](#audit-logging--error-handling)
11. [Extensibility & Onboarding](#extensibility--onboarding)
    * 11.1 [Adding Features, Pages, or Controllers](#adding-features-pages-or-controllers)
    * 11.2 [Adding Products, Categories, and Quiz Questions](#adding-products-categories-and-quiz-questions)
    * 11.3 [Developer Onboarding Checklist](#developer-onboarding-checklist)
    * 11.4 [Testing & Debugging](#testing--debugging)
12. [Future Enhancements & Recommendations](#future-enhancements--recommendations)
13. [Appendices](#appendices)
    * A. [Key File Summaries](#a-key-file-summaries)
    * B. [Glossary](#b-glossary)
    * C. [Code Snippets and Patterns](#c-code-snippets-and-patterns)

---

## 1. Introduction

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document is the definitive technical design specification for The Scent’s codebase. It is intended to offer deep insight into the system’s structure, logic, and flow, and to serve as a comprehensive onboarding and reference guide for both current developers and future maintainers.

---

## 2. Project Philosophy & Goals

* **Security First:** All data input and user interactions are validated and sanitized. Strong session and CSRF protection are enforced.
* **Simplicity & Maintainability:** Clear, modular code structure. No over-engineered abstractions. Direct `require_once` usage observed in `index.php` fits this philosophy.
* **Extensibility:** Easy to add new features, pages, controllers, or views.
* **Performance:** Direct routing, optimized queries, and minimized external dependencies. (Note: CDN usage for frontend libraries introduces external dependencies).
* **Modern User Experience:** Responsive design, smooth animations (AOS.js, Particles.js), and AJAX interactions where implemented (cart updates/removal, newsletter).
* **Transparency:** No magic – all application flow and routing is explicit in `index.php`'s include and switch logic.
* **Accessibility & SEO:** Semantic HTML, ARIA attributes where needed, and meta tags for discoverability.

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
[View]  (e.g., views/home.php - often included directly from index.php route case)
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
5.  **CSRF Check:** If the request method is POST, `SecurityMiddleware::validateCSRF()` is called to validate the CSRF token.
6.  **Controller/View Dispatch:** A `switch` statement based on `$page` typically:
    * Includes the necessary controller file(s) (e.g., `require_once __DIR__ . '/controllers/CartController.php';`).
    * Instantiates the controller, passing the `$pdo` database connection (which is assumed to be available, likely initialized within `includes/db.php`).
    * Calls a method on the controller (e.g., `$productController->showHomePage();`) OR directly includes the relevant view file (`require_once __DIR__ . '/views/cart.php';`) after potentially fetching data.
7.  **Controller Action:** Controller methods execute business logic, interact with the database (often using `$this->db` inherited from `BaseController`), validate input further, and prepare data for the view.
8.  **View Rendering:** The included view file (`views/*.php`) generates HTML output, often including layout partials (`views/layout/header.php`, `views/layout/footer.php`). Data is accessed via variables set before the include or potentially global scope. Output is escaped using `htmlspecialchars()`.
9.  **Response:** Output (HTML, CSS, JS) is sent to the browser.
10. **Client-Side Execution:** The browser renders HTML, applies CSS (from `/css/style.css` and Tailwind CDN), and executes JavaScript (AOS, Particles, mobile menu, AJAX for newsletter/cart updates/cart removal).
11. **AJAX Interactions:** Client-side JS sends `Workspace` requests (typically POST) back to `index.php` with appropriate `page` and `action` parameters for tasks like newsletter subscription or cart updates/removal. The corresponding controller action handles the request and usually responds with JSON (using `jsonResponse` helper from `BaseController`), although the Add-to-Cart backend logic currently redirects instead.

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
|-- technical_design_specification.md # This document (Updated - Rev 2)
```

### 4.2 Key Files Explained

* **index.php**: Application entry point in the project root. Includes core files, initializes error handling and security middleware, performs routing based on `$_GET` params, validates CSRF for POST requests, includes/instantiates controllers, and includes view files.
* **config.php**: Central configuration for Database credentials, email settings, security parameters, potentially API keys. Located in the project root.
* **css/style.css**: Contains custom CSS rules, complementing styles provided by the Tailwind CSS framework (loaded via CDN).
* **particles.json**: Configuration for the Particles.js library used for animated backgrounds.
* **.htaccess**: Located in the project root. Configures Apache settings, primarily `RewriteRule` directives to route non-file/directory requests to `index.php` for handling, enabling clean URLs. Excludes certain paths (e.g., `test_*.php`) from rewriting.
* **includes/db.php**: Establishes the PDO database connection, likely making the `$pdo` object available (possibly globally or via a function) for use in `index.php` and controllers. Configures PDO error mode.
* **includes/auth.php**: Provides authentication-related helper functions like `isLoggedIn()`, `isAdmin()`, potentially handling session checks.
* **includes/SecurityMiddleware.php**: Class or functions providing security services called from `index.php` and potentially `BaseController`. Includes `apply()` (global setup), `validateInput()`, `validateCSRF()`, potentially CSRF token generation, session management, header setting.
* **includes/ErrorHandler.php**: Class or functions for centralized error/exception handling. Includes `init()` method (called in `index.php`) to register handlers. Likely logs errors and displays user-friendly error pages.
* **controllers/BaseController.php**: Abstract base class likely extended by other controllers. Provides shared functionality like database connection (`$this->db`), middleware access (`$this->securityMiddleware`), helper methods for JSON/redirect responses, view rendering (`renderView`), input validation, auth checks, logging, etc..
* **controllers/*Controller.php**: Specific controllers handling logic for different application modules (Products, Cart, Checkout, Account, Quiz, etc.). Instantiated in `index.php`, often extend `BaseController`.
* **models/*.php**: Classes representing data entities (Product, User, Order, Quiz) and potentially containing database interaction logic specific to those entities.
* **views/layout/header.php**: Generates common site header (logo, navigation, icons), includes CSS/JS assets (CDNs, custom CSS), handles dynamic elements like login status and cart count (from session), and displays server-side flash messages (from session).
* **views/layout/footer.php**: Generates common site footer (links, newsletter, social icons, copyright), includes/initializes JavaScript (AOS, Particles, custom AJAX handlers).
* **views/*.php**: Individual page templates combining HTML and PHP to display content. Included by `index.php` or controller methods.

---

## 5. Routing and Application Flow

### 5.1 URL Routing via .htaccess

* **File:** `/.htaccess` (in project root).
* **Mechanism:** Uses Apache's `mod_rewrite`.
    * `RewriteEngine On` enables the module.
    * `RewriteCond` directives check if the request is *not* for an existing file (`!-f`) or directory (`!-d`) and *not* for specific patterns like `/test_*.php` or `/sample_*.html`.
    * If conditions are met, `RewriteRule ^ index.php [L]` rewrites the request URI to `/index.php`, stopping further rule processing (`[L]`). Query strings are typically appended automatically (`QSA` flag is not explicitly present but often default or implicit depending on Apache version/config).
* **Requirement:** Relies on Apache `DocumentRoot` pointing to the project root and `AllowOverride` being enabled.
* **Result:** All "clean URL" requests (or requests directly to `index.php` with query parameters) are handled by the single entry point script `/index.php`.

### 5.2 index.php: The Application Entry Point

**Location:** Project Root (`/index.php`)

**Key Responsibilities:**
* Define `ROOT_PATH` constant.
* Include essential files from `/includes/` (`config.php`, `db.php`, `auth.php`, `SecurityMiddleware.php`, `ErrorHandler.php`) using `require_once`.
* Initialize error handling via `ErrorHandler::init()`.
* Apply global security middleware settings via `SecurityMiddleware::apply()`. (Note: `BaseController` also interacts with SecurityMiddleware).
* Determine requested page (`$page`) and action (`$action`) from `$_GET` parameters, using `SecurityMiddleware::validateInput()`.
* Validate CSRF token for POST requests using `SecurityMiddleware::validateCSRF()`.
* **(Database Connection):** Assumes `$pdo` database connection object is made available, likely initialized within the included `includes/db.php`.
* Route requests using a `switch ($page)` statement:
    * Include necessary controller file(s) *within* the case block (e.g., `require_once __DIR__ . '/controllers/CartController.php';`).
    * Instantiate the relevant controller (e.g., `$controller = new CartController($pdo);`).
    * Call a method on the controller to handle the request/action (e.g., `$productController->showHomePage();`, `$controller->addToCart(...)`) OR directly include a view file (`require_once __DIR__ . '/views/register.php';`).
* Handle default/unknown pages with a 404 response and view (`require_once __DIR__ . '/views/404.php';`).
* Wrap the main routing logic in a `try...catch` block to handle exceptions, logging PDOExceptions specifically and re-throwing others for the ErrorHandler.

### 5.3 Controller Dispatch & Action Flow

* Controllers (`controllers/*.php`) encapsulate logic for specific modules. Most likely extend `controllers/BaseController.php` to inherit common functionality.
* They are included and instantiated within the routing logic of `index.php` based on the requested `$page`.
* Controller methods handle specific `$action`s or default page views.
* They utilize the injected `$pdo` object (via constructor, inherited `$this->db`) or Models (`models/*.php`) for database operations.
* Input validation is performed using `SecurityMiddleware::validateInput()` (called from `index.php` or potentially within controller methods using helpers from `BaseController`).
* Security checks like `isLoggedIn()`, `isAdmin()`, or potentially `requireLogin()`/`requireAdmin()` helpers from `BaseController` are used to gate access.
* They prepare data needed by the view.
* They conclude by:
    * Including a view file (`require_once ...`), often done directly in `index.php` after controller setup, or potentially via a `renderView` helper.
    * Sending a JSON response (for AJAX requests like cart updates/removals) using helpers like `jsonResponse`.
    * Performing a redirect using `header('Location: ...')` or potentially a `redirect` helper.

### 5.4 Views: Templating and Rendering

* Views (`views/*.php`) generate the HTML output. They mix HTML markup with PHP tags (`<?= ... ?>`, `<?php ... ?>`) for dynamic content.
* They are typically included via `require_once` directly from the `index.php` routing logic or potentially via a `renderView` helper method provided by `BaseController` which uses output buffering (`ob_start`, `ob_get_clean`).
* Data is made available either through variables set in the scope before the include (e.g., `$cartItems`, `$questions` in `index.php`) or passed as an associative array to the `renderView` helper.
* Most views include common layout partials (`views/layout/header.php`, `views/layout/footer.php`).
* Output originating from variables or database content MUST be escaped using `htmlspecialchars()` to prevent XSS.
* Styling uses CSS classes from `/css/style.css` and the Tailwind CDN.
* JavaScript for interactivity is loaded via CDNs or inline/included scripts, often within `views/layout/footer.php`.

---

## 6. Frontend Architecture

### 6.1 CSS (css/style.css), Tailwind (CDN), and Other Libraries

* **Styling Approach:** Hybrid.
    * **Tailwind CSS:** Included via CDN `<script>` tag in `views/layout/header.php`. Basic theme customizations (colors, fonts) are configured inline. Provides utility classes.
    * **Custom CSS:** `/css/style.css` is loaded locally. Contains custom styles, component rules, overrides, and potentially CSS variables.
* **Other Libraries (Loaded via CDN in `views/layout/header.php`):**
    * Google Fonts (`Cormorant Garamond`, `Montserrat`, `Raleway`).
    * Font Awesome (for icons).
    * AOS.js (Animate On Scroll) CSS and JS.
    * Particles.js JS library.

### 6.2 Responsive Design and Accessibility

* **Responsive Design:** Primarily uses Tailwind's responsive prefixes (e.g., `md:`, `lg:`) for grid layouts, visibility, and spacing. Custom media queries may exist in `/css/style.css`. Mobile navigation uses a JavaScript toggle.
* **Accessibility:**
    * Uses semantic HTML elements (`<header>`, `<footer>`, `<nav>`, etc.).
    * `aria-label` used on icon buttons.
    * Basic keyboard navigation expected; mobile menu has escape key closing.
    * Color contrast and font sizes should be manually audited for WCAG compliance.

### 6.3 JavaScript: Interactivity, AOS.js, Particles.js

* **Libraries Initialization:** AOS.js and Particles.js are initialized via scripts, likely in `views/layout/footer.php`, although AOS script tag also appears in header. `particlesJS.load` uses `/particles.json` config.
* **Custom Interactivity:**
    * **Mobile Menu Toggle:** JS in `views/layout/header.php` handles the toggle mechanism.
    * **AJAX Operations:** Implemented using `Workspace` API in scripts (likely in `views/layout/footer.php`, `views/home.php`, `views/cart.php`).
        * *Newsletter Subscription:* Forms (`#newsletter-form`, `#newsletter-form-footer`) submit via AJAX to `index.php?page=newsletter&action=subscribe`, expecting JSON response. Feedback uses `alert()` or direct DOM manipulation.
        * *Cart Item Removal:* Remove button (`.remove-item`) triggers AJAX POST to `index.php?page=cart&action=remove`, expecting JSON response. Updates UI, totals, and header count.
        * *Cart Quantity Update:* Form (`#cartForm`) submission via AJAX POST to `index.php?page=cart&action=update`, expecting JSON response. Updates totals and header count. +/- buttons update input value client-side.
    * **Flash Messages:** A client-side `showFlashMessage()` function exists in `views/home.php` for dynamic feedback (likely after AJAX). Separately, `views/layout/header.php` displays server-side flash messages stored in `$_SESSION['flash_message']`. The feedback mechanism (JS flash vs. alert vs. server flash) appears inconsistent across features.
    * **Sticky Header:** JS in `views/home.php` adds/removes a class based on scroll position.
* **Add-to-Cart Inconsistency:** JavaScript handlers exist in `views/home.php` and `views/layout/footer.php` that *attempt* to add items to the cart via AJAX (`Workspace` to `index.php?page=cart&action=add`), expecting a JSON response. However, the backend code in `index.php` for this specific action performs a server-side redirect. Therefore, the **current Add-to-Cart functionality from product listings/home page is likely NOT AJAX-based** but relies on the redirect, despite the frontend JS code. The JS handlers may be remnants of a previous implementation or intended for future backend changes.

---

## 7. Key Pages & Components

### 7.1 Home/Landing Page (views/home.php)

* Includes `layout/header.php` and `layout/footer.php`.
* Displays sections: Hero (video/particles), About, Featured Products, Benefits, Quiz Finder, Newsletter, Testimonials.
* **Data Dependency:** Expects a `$featuredProducts` array variable passed from the controller (`ProductController::showHomePage()`). Each element in the array should have keys like `id`, `name`, `image` (path), `stock_quantity`, and optionally `short_description`, `category_name`.
* Uses `htmlspecialchars()` for displaying product names, descriptions.
* Conditionally displays "Add to Cart" or "Out of Stock" button based on `stock_quantity`. Add-to-Cart buttons have class `.add-to-cart` and `data-product-id` attribute.
* Contains inline/included JavaScript for AOS, Particles, sticky header, newsletter AJAX, and the (currently mismatched with backend) Add-to-Cart AJAX handler using `showFlashMessage`.

### 7.2 Header and Navigation (views/layout/header.php)

* Includes `includes/auth.php`.
* Generates site header with logo, navigation links (Home, Shop, Scent Finder, About, Contact), and icons (Search, User, Cart).
* Conditionally shows Account link (`page=account`) or Login link (`page=login`) based on `isLoggedIn()`.
* Displays cart item count dynamically from `$_SESSION['cart_count']`.
* Includes CSS assets (CDNs, local `/css/style.css`) and JS assets (CDNs for AOS, Particles, Tailwind runtime).
* Contains JS for mobile menu toggle.
* Displays server-side flash messages from `$_SESSION['flash_message']` if set, then unsets them.

### 7.3 Footer and Newsletter (views/layout/footer.php)

* Generates site footer with multiple columns (About snippet, Shop links, Help links, Contact info, Newsletter form), social icons, copyright (dynamic year), and payment method icons.
* Contains newsletter signup form (`#newsletter-form-footer`) with CSRF token field.
* Includes JavaScript for:
    * AOS initialization (potentially redundant).
    * Particles.js initialization check.
    * Newsletter form AJAX submission (using `alert()` for feedback).
    * An Add-to-Cart AJAX handler (likely non-functional due to backend redirect, uses `alert()` for feedback).

### 7.4 Product Grid & Cards

* Used on Home page (`views/home.php`) and likely Product Listing page (`views/products.php`).
* Uses responsive grid layout (Tailwind).
* Each card displays product image, name, category/description, "View Details" link, and a conditional "Add to Cart" (class `.add-to-cart`, `data-product-id`) or "Out of Stock" button based on `$product['stock_quantity']`.
* **(Functionality Note):** Clicking ".add-to-cart" button currently triggers a non-AJAX form submission/redirect based on `index.php`, despite JS handlers attempting AJAX calls.

### 7.5 Shopping Cart (views/cart.php)

* Includes `layout/header.php` and `layout/footer.php`.
* **Data Dependency:** Expects a `$cartItems` array (containing product details, quantity, subtotal for each item) and a `$total` variable passed from the controller (`CartController::getCartItems()` likely prepares this).
* Displays items in a list/table format, showing image, name, price, quantity input, subtotal, and remove button.
* Shows a summary section with Subtotal, Shipping (hardcoded as FREE), and Total.
* Includes a form (`#cartForm`) wrapping the items, targeting `index.php?page=cart&action=update`.
* Contains buttons: "Update Cart" (submits the form via AJAX) and "Proceed to Checkout" (links to `index.php?page=checkout`).
* Includes JavaScript for:
    * Client-side quantity +/- button functionality.
    * AJAX item removal (POST to `action=remove`, expects JSON).
    * AJAX cart update via form submission (POST to `action=update`, expects JSON).
    * Client-side recalculation/updating of totals and header cart count based on interactions and AJAX responses.

### 7.6 Quiz Flow & Personalization

* **Entry:** Home page links to `index.php?page=quiz`.
* **Interface:** `views/quiz.php` displays questions (data likely from `QuizController::getQuestions()`).
* **Submission:** Form likely POSTs to `index.php?page=quiz&action=submit`.
* **Processing:** `QuizController::processQuiz()` handles POST data. Logic maps answers to recommendations.
* **Results:** `index.php` includes `views/quiz_results.php`, passing results data (implicitly or via `$results` variable).
* **Admin:** An admin section (`index.php?page=admin&section=quiz_analytics`) exists, calling `QuizController::getAnalytics()`.
* **Personalization:** Results might be saved to `quiz_results` table (linked to user/session) by `QuizController`.

---

## 8. Backend Logic & Core PHP Components

### 8.1 Includes: Shared Logic (includes/)

* **Location:** `/includes/` directory. Contains foundational PHP files.
* **Key Files:**
    * `auth.php`: Provides `isLoggedIn()`, `isAdmin()` helper functions used for access control checks in `index.php` and views. May contain core login/logout/registration logic or delegate to `AccountController`.
    * `db.php`: Responsible for establishing the PDO database connection (using credentials from `config.php`) and making the `$pdo` object available for `index.php` and controllers.
    * `SecurityMiddleware.php`: Provides static methods like `apply()` (called once in `index.php`), `validateInput()`, `validateCSRF()`. May also provide instance methods used by `BaseController` for CSRF token generation/validation, rate limiting, etc. (Potential static/instance inconsistency).
    * `ErrorHandler.php`: Provides `init()` method called in `index.php` to set error/exception handlers. Likely contains the handler logic to log errors and display safe error pages. `BaseController` may use `ErrorHandler::logError()`.
    * `EmailService.php`: Class likely used by controllers (instantiated in `BaseController`) for sending emails.

### 8.2 Controllers: Business Logic Layer (controllers/ & BaseController.php)

* **Location:** `/controllers/` directory. Contains specific logic handlers.
* **Base Class:** `controllers/BaseController.php` is an `abstract class` providing common functionality inherited by other controllers.
    * Takes `$pdo` in constructor.
    * Provides protected properties: `$db` (PDO), `$securityMiddleware` (instance), `$emailService` (instance).
    * Initializes default security headers.
    * Offers helper methods for: JSON responses (`jsonResponse`, `sendError`), redirects (`redirect`), view rendering (`renderView`), input validation (`validateInput`, `validateRequest`), authentication/authorization checks (`requireLogin`, `requireAdmin`), CSRF handling (`getCsrfToken`, `validateCSRF`), session flash messages (`set/getFlashMessage`), database transactions (`beginTransaction`, `commit`, `rollback`), rate limiting, logging (`log`, `logAuditTrail`, `logSecurityEvent`), file uploads (`validateFileUpload`), etc..
* **Specific Controllers:** Files like `ProductController.php`, `CartController.php`, etc. handle requests for their respective domains.
    * Likely `extend BaseController`.
    * Instantiated within `index.php` routing logic, passed the `$pdo` connection.
    * Implement public methods corresponding to actions (e.g., `showHomePage`, `addToCart`, `processCheckout`).
    * Use inherited properties (`$this->db`) and helper methods from `BaseController` for database access, validation, responses, etc.
    * Prepare data and either call `renderView` or let `index.php` include the appropriate view file.

### 8.3 Database Abstraction (includes/db.php)

* **Method:** Uses PDO.
* **Connection:** Logic within `includes/db.php` creates the PDO connection using details from `config.php`. It needs to make the `$pdo` object accessible to `index.php` (e.g., by returning it from an include or defining it globally - exact mechanism not shown but implied by `index.php` usage).
* **Configuration:** Sets PDO error mode to exceptions (`PDO::ERRMODE_EXCEPTION`) likely within `BaseController` constructor or `db.php`. Default fetch mode (`PDO::FETCH_ASSOC`) is recommended.
* **Usage:** Controllers access the database via the `$pdo` object (e.g., `$this->db` inherited from `BaseController`). Prepared statements (`prepare`, `execute`) MUST be used for all queries involving external data.

### 8.4 Security Middleware & Error Handling

* **SecurityMiddleware.php (`includes/`):**
    * *Initialization:* `SecurityMiddleware::apply()` called once near the start of `index.php` for potential global setup (e.g., starting secure session, sending initial headers).
    * *CSRF Protection:* `SecurityMiddleware::validateCSRF()` called in `index.php` for POST requests. `BaseController` provides `getCsrfToken()` (potentially static `SecurityMiddleware::generateCSRFToken()`) and `validateCSRF()` (potentially instance `$this->securityMiddleware->validateCSRFToken()`). The exact static/instance usage seems inconsistent between `index.php` and `BaseController` and needs clarification/standardization. Tokens are stored in session and expected in POST data (`csrf_token` field).
    * *Input Validation:* `SecurityMiddleware::validateInput()` used in `index.php` and potentially via `BaseController` helpers.
    * *Security Headers:* Default headers initialized in `BaseController::initializeSecurityHeaders()` and likely sent via helper methods or `SecurityMiddleware::apply()`.
* **ErrorHandler.php (`includes/`):**
    * *Initialization:* `ErrorHandler::init()` called early in `index.php` to register error/exception handlers.
    * *Handling:* Catches errors/exceptions (including those re-thrown from `index.php`'s `try...catch` block). Logs detailed errors (using `error_log` or custom logic, possibly `BaseController::logSecurityEvent`) and displays a generic error page (`views/error.php`).

### 8.5 Session, Auth, and User Flow

* **Session Start:** Secure session likely started via `SecurityMiddleware::apply()` or an explicit call within it. Secure cookie attributes (HttpOnly, Secure, SameSite) should be configured. `BaseController` includes helpers for session integrity checks and regeneration.
* **Authentication:**
    * Login status checked using `isLoggedIn()` (from `includes/auth.php`). `BaseController` also provides `requireLogin` helper.
    * Login logic resides in `AccountController`, verifying passwords using `password_verify()`. On success, session ID is regenerated, and user data stored in `$_SESSION`. Expects `$_SESSION['user_id']` and `$_SESSION['user_role']` to be set. `BaseController` also refers to `$_SESSION['user']` array. Session structure needs clarification.
* **Authorization:** Admin access checked using `isAdmin()` (from `includes/auth.php`). `BaseController` provides `requireAdmin` helper checking `$_SESSION['user_role'] === 'admin'`.
* **Cart Count:** Stored in `$_SESSION['cart_count']` and displayed in the header. Updated by cart controller actions.
* **Flash Messages:** Stored in `$_SESSION['flash']` (containing `message` and `type`) by `BaseController::setFlashMessage()`. Displayed once and unset by `views/layout/header.php`. Client-side JS also has a separate `showFlashMessage` function.

---

## 9. Database Design

*(No changes based on new file info; schemas remain assumed unless schema file provided)*

### 9.1 Entity-Relationship Model
*(Previous description retained)*

### 9.2 Main Tables & Sample Schemas
*(Previous description retained - Note: requires validation against actual schema)*

### 9.3 Data Flow Examples
*(Refined based on observed index.php and cart.php logic)*
* **User adds to cart:** POST request to `index.php?page=cart&action=add` with product ID, quantity, CSRF token. `index.php` includes `CartController`, calls `addToCart()`. Controller updates session/DB cart. `index.php` then redirects back to `index.php?page=cart`. (Note: This contradicts the AJAX JS in views).
* **User updates cart quantity:** Form submission via AJAX POST to `index.php?page=cart&action=update` with quantities and CSRF token. `CartController::updateCart()` (assumed method) updates session/DB. Controller responds with JSON containing success status and updated cart count. JS updates totals/header count client-side.
* **User removes cart item:** Button click triggers AJAX POST to `index.php?page=cart&action=remove` with product ID and CSRF token. `CartController::removeCartItem()` (assumed method) updates session/DB. Controller responds with JSON containing success status and updated cart count. JS removes item from UI and updates totals/header count.
* **Order placed:** User navigates to checkout (`page=checkout`), which requires login. Form POSTs to `index.php?page=checkout&action=process`. `CheckoutController::processCheckout()` validates, creates `orders` and `order_items` records, clears cart, processes payment, redirects to confirmation page or shows errors.
* **Quiz taken:** Form POSTs to `index.php?page=quiz&action=submit`. `QuizController::processQuiz()` processes answers, determines recommendations (`$results`), and `index.php` includes `views/quiz_results.php` to display them. Results potentially saved to DB by controller.
* **Newsletter signup:** Form submits via AJAX POST to `index.php?page=newsletter&action=subscribe` with email and CSRF token. `NewsletterController::subscribe()` validates and saves email, responds with JSON success/error status. JS updates form UI or shows alert.

---

## 10. Security Considerations

### 10.1 Input Sanitization & Validation

* Use `SecurityMiddleware::validateInput()` in `index.php` for basic routing params.
* Use controller-level validation, potentially via `BaseController::validateInput` or `BaseController::validateRequest`, before processing any data from `$_POST`, `$_GET`.
* Use PDO prepared statements for ALL database queries involving external data.
* Use `htmlspecialchars()` for ALL output in HTML context.

### 10.2 Session Management

* Secure session cookie attributes should be set (via `SecurityMiddleware::apply()` or session config).
* Regenerate session ID on login/logout (`session_regenerate_id(true)`). `BaseController` includes logic for periodic regeneration and integrity checks (IP/User Agent).
* Implement server-side session timeouts.

### 10.3 CSRF Protection

* Tokens generated (likely via `SecurityMiddleware::generateCSRFToken` or `BaseController::getCsrfToken`) and stored in session (`$_SESSION['csrf_token']`).
* Token included in forms (`<input type="hidden" name="csrf_token" ...>`) and sent with AJAX POST requests (in form data).
* Validation performed for POST requests in `index.php` using `SecurityMiddleware::validateCSRF()`. `BaseController` also has `validateCSRF` helpers. (Need to clarify if static or instance method is consistently used). Uses `hash_equals()` for comparison.

### 10.4 Security Headers & Rate Limiting

* **Security Headers:** Set via `BaseController::initializeSecurityHeaders()` and potentially `SecurityMiddleware::apply()`. Includes `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, basic `Content-Security-Policy`.
* **Rate Limiting:** Multiple implementation patterns seem present in `BaseController` (using `SecurityMiddleware::checkRateLimit`, session-based `isRateLimited`, APCu-based `validateRateLimit`, Redis-based `checkRateLimit`). The active mechanism and configuration (via `SECURITY_SETTINGS` constant/array) needs verification. Should be applied to login, registration, password reset, etc.

### 10.5 File Uploads & Permissions

* `BaseController` includes a `validateFileUpload` helper method checking error codes, size limits (`$maxSize`), and MIME type (`finfo`) against an allowlist (`$allowedTypes`). Use this for any file uploads.
* Store uploads outside webroot or disable script execution in upload directory. Use secure filenames.
* Apply restrictive file system permissions (web server write access only where essential). Protect `config.php`.

### 10.6 Audit Logging & Error Handling

* **Audit Logging:** `BaseController` provides `logAuditTrail()` method to insert records into an `audit_log` table (capturing action, user ID, IP, user agent, details). `BaseController` also has `logSecurityEvent()` method writing formatted security events to a configured log file. Use these for significant actions/events.
* **Error Handling:** `ErrorHandler::init()` registers handlers. Errors/exceptions are caught, logged (`error_log` or custom logger), and a generic error page is shown (`views/error.php`). Ensure `display_errors` is Off in production.

---

## 11. Extensibility & Onboarding

### 11.1 Adding Features, Pages, or Controllers

1.  **Controller:** Create `/controllers/NewController.php`, likely extending `BaseController`.
2.  **View(s):** Create `/views/new_feature.php`.
3.  **Routing:** Add `case 'new_feature':` in `/index.php`'s main switch. Include and instantiate `NewController`, call its method(s) or include the view.
4.  **(Optional) Model:** Create `/models/NewModel.php`.
5.  **(Optional) Database:** Update schema.
6.  **Navigation:** Add links in `/views/layout/header.php` or elsewhere.

### 11.2 Adding Products, Categories, and Quiz Questions

* **Products/Categories:** Requires direct DB access or admin interface development.
* **Quiz Questions:** Requires modification of `QuizController` logic or moving mapping to DB/config file.

### 11.3 Developer Onboarding Checklist

1.  **Prerequisites:** Apache, PHP (8+), MySQL/MariaDB, Git. (Composer optional unless libraries added).
2.  **Clone Repo.**
3.  **Configuration:** Update `config.php` (DB credentials, etc.). Protect file permissions.
4.  **Database:** Create DB, import schema (`/db/schema.sql`?), seed data.
5.  **Web Server:** Configure Apache VHost: `DocumentRoot` -> project root, `AllowOverride All`. Enable `mod_rewrite`. Restart Apache.
6.  **File Permissions:** Ensure web server user (`www-data`) has minimal write access.
7.  **Testing:** Access site, test core flows (homepage, products, add-to-cart (expect redirect), cart view (updates/removal), login, register, quiz). Check logs.

### 11.4 Testing & Debugging

* Check PHP/Apache error logs.
* Use browser dev tools (Network, Console).
* **Inconsistencies:** Pay attention to mismatches like the Add-to-Cart AJAX JS vs. backend redirect. Note potential duplicate JS loading (AOS in header and footer).
* Perform manual flow testing and security checks (XSS, CSRF, SQLi via prepared statements, session security).
* Use `var_dump`/`error_log` or setup Xdebug.

---

## 12. Future Enhancements & Recommendations

*(No changes from previous version based on new file info)*
* Autoloader (Composer PSR-4)
* Templating Engine (Twig)
* Framework Adoption (Laravel, Symfony, Slim)
* API Layer (RESTful)
* Enhanced Admin Dashboard
* Formal Testing (PHPUnit, Cypress/Playwright)
* Dependency Management (Composer/NPM/Yarn)
* Dockerization
* CI/CD Pipelines
* Performance Optimization (Caching, CDN, Asset Bundling)
* Internationalization (i18n) / Localization (l10n)
* Accessibility Audit (WCAG)
* Enhanced User Profiles
* Advanced Analytics

---

## 13. Appendices

### A. Key File Summaries

*(Paths and descriptions validated/refined)*

| File/Folder                 | Purpose                                                                               |
| :-------------------------- | :------------------------------------------------------------------------------------ |
| `index.php`                 | Application entry, routing, core includes, CSRF check, controller/view dispatch         |
| `config.php`                | Environment, DB, email, security configuration                                       |
| `css/style.css`             | Custom CSS rules complementing CDN Tailwind                                            |
| `particles.json`          | Particle animation config for Particles.js                                            |
| `.htaccess`                 | (Root directory) Apache URL rewriting, access control                                 |
| `includes/auth.php`         | Authentication helper functions (isLoggedIn, isAdmin)                                |
| `includes/db.php`           | PDO DB connection setup                                                               |
| `includes/SecurityMiddleware.php` | Security class/functions (CSRF, validation, headers) called by index/BaseController |
| `includes/ErrorHandler.php` | Centralized error/exception handling (init, logging)                                  |
| `controllers/BaseController.php` | Abstract base class providing shared controller helpers (DB, validation, response)   |
| `controllers/*Controller.php` | Specific business logic handlers                                                      |
| `models/*.php`              | Data entity representation and DB interaction logic                                   |
| `views/*.php`               | HTML/PHP templates for rendering pages                                               |
| `views/layout/header.php`   | Sitewide header, navigation, asset loading, session flash/cart display                 |
| `views/layout/footer.php`   | Sitewide footer, JS initialization, newsletter AJAX                                    |

### B. Glossary
*(No changes from previous version)*

### C. Code Snippets and Patterns

#### Example: Secure Controller Routing in `/index.php`

```php
// (Inside index.php, after includes and setup)
$page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string');
$action = SecurityMiddleware::validateInput($_GET['action'] ?? 'index', 'string');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityMiddleware::validateCSRF(); // Use the actual function name
}

try {
    switch ($page) {
        case 'home':
            // ProductController likely included earlier or via autoloader if implemented
            (new ProductController($pdo))->showHomePage();
            break;
        case 'cart':
             require_once ROOT_PATH . '/controllers/CartController.php'; // Included within case
             $controller = new CartController($pdo);
             if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                  $productId = SecurityMiddleware::validateInput($_POST['product_id'] ?? null, 'int');
                  $quantity = SecurityMiddleware::validateInput($_POST['quantity'] ?? 1, 'int');
                  $controller->addToCart($productId, $quantity);
                  header('Location: index.php?page=cart'); // Actual behavior: Redirect
                  exit;
             } else if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                  // Assumes updateCart handles AJAX response via BaseController::jsonResponse
                  $controller->updateCart($_POST['updates'] ?? []);
             } else if ($action === 'remove' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                   // Assumes removeCartItem handles AJAX response via BaseController::jsonResponse
                   $productId = SecurityMiddleware::validateInput($_POST['product_id'] ?? null, 'int');
                   $controller->removeCartItem($productId);
             } else {
                  $cartItems = $controller->getCartItems();
                  $total = $controller->getCartTotal(); // Assume controller calculates total
                  // Pass data to view via include scope
                  require_once ROOT_PATH . '/views/cart.php';
             }
             break;
        // ...other cases...
        default:
            http_response_code(404);
            require_once ROOT_PATH . '/views/404.php';
            break;
    }
} catch (Exception $e) {
    ErrorHandler::handle($e); // Assumes ErrorHandler::handle exists or init registered one
}
```

#### Example: CSRF-Protected Form (`views/layout/footer.php` Newsletter)

```php
<form id="newsletter-form-footer" class="newsletter-form" style="margin-top:1rem;">
    <?php
        // Assuming BaseController::getCsrfToken() or similar is used
        // or CSRF token is added globally/available in session
        $csrfToken = $_SESSION['csrf_token'] ?? '';
        if (empty($csrfToken) && class_exists('SecurityMiddleware')) {
             // Attempt to generate if missing, adjust based on actual implementation
             // This might be better handled before view rendering
             if(method_exists('SecurityMiddleware', 'generateCSRFToken')) {
                  $csrfToken = SecurityMiddleware::generateCSRFToken();
             } else if (isset($securityMiddleware) && method_exists($securityMiddleware, 'generateCSRFToken')) {
                 $csrfToken = $securityMiddleware->generateCSRFToken();
             }
        }
    ?>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <input type="email" name="email" placeholder="Enter your email" required class="newsletter-input">
    <button type="submit" class="btn btn-primary newsletter-btn">Subscribe</button>
</form>
```

#### Example: AJAX Cart Update (JavaScript in `views/cart.php`)

```javascript
// Example for removing an item (Assumes CSRF token is available in a hidden input)
document.querySelectorAll('.remove-item').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const csrfToken = document.querySelector('input[name="csrf_token"]').value; // Get token

        if (!confirm('Are you sure you want to remove this item?')) {
            return;
        }

        const formData = new URLSearchParams();
        formData.append('product_id', productId);
        formData.append('csrf_token', csrfToken); // Include CSRF token

        fetch('index.php?page=cart&action=remove', { // Target remove action
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json' // Expect JSON back
            },
            body: formData
        })
        .then(response => {
             if (!response.ok) {
                 throw new Error(`HTTP error! status: ${response.status}`);
             }
             return response.json(); // Parse JSON body
         })
        .then(data => {
            if (data.success) {
                // Remove item row from UI
                this.closest('.cart-item').remove();
                // Update totals displayed on page (implementation depends on updateCartTotal function)
                updateCartTotal();
                // Update cart count in header (implementation depends on updateCartCount function)
                updateCartCount(data.cart_count || 0);

                // Optional: Show success flash message
                // showFlashMessage('Item removed from cart.', 'success');

                 // If cart becomes empty, maybe display empty message or reload
                 if ((data.cart_count || 0) === 0) {
                      // Example: display empty cart message or reload
                      document.querySelector('.cart-items').innerHTML = '<p>Your cart is now empty.</p>';
                      document.querySelector('.cart-summary').style.display = 'none';
                      document.querySelector('.cart-actions').style.display = 'none';
                 }
            } else {
                // Show error message from server response
                alert(data.message || 'Could not remove item. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error removing item:', error);
            alert('An error occurred while removing the item. Please try again.');
        });
    });
});

// Placeholder/Example for updateCartTotal and updateCartCount - actual implementation needed
function updateCartTotal() { console.log("Updating cart total display..."); /* Recalculate from DOM */ }
function updateCartCount(count) {
     const cartCountSpan = document.querySelector('.cart-count');
     if (cartCountSpan) cartCountSpan.textContent = count;
     console.log("Updating header cart count display...");
 }
 // Placeholder for client-side flash messages if used
 // function showFlashMessage(message, type) { alert(`${type}: ${message}`); }
```

*(Self-Correction: Updated AJAX example to reflect cart removal which *does* use AJAX according to `cart.php`, included CSRF token handling as seen in other JS examples, and added notes about placeholder functions.)*

---

**End of Technical Design Specification (Updated - Rev 2)**

This document reflects the analysis of the provided code snippets (`.htaccess`, `index.php`, layout files, `BaseController`, `cart.php`). Key areas like the Add-to-Cart mechanism show inconsistencies between frontend JavaScript expectations (AJAX) and backend PHP implementation (redirect). These should be addressed in future development. For questions, improvements, or onboarding, start here and cross-reference with the actual codebase.
```
