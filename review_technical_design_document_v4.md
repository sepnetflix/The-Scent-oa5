Okay, I have meticulously reviewed the provided code files (`index.php`, `.htaccess`, `views/home.php`, `views/cart.php`, `views/layout/header.php`, `views/layout/footer.php`, `controllers/BaseController.php`, `includes/db.php`, `config.php`, `includes/SecurityMiddleware.php`) and compared them against the previous specification (`technical_design_specification_v2.md`).

The code reveals a more detailed and nuanced implementation than previously documented, including specific security features, configuration structures, helper methods in `BaseController`, and importantly, confirms the inconsistency in the Add-to-Cart functionality (frontend JS expects AJAX, backend PHP redirects). There are also minor discrepancies like duplicate JS initializations and missing CSRF tokens in some cart AJAX calls.

Based on this detailed validation, here is the **refined and further updated** Technical Design Specification, now designated as **Revision 3**:

```markdown
# The Scent – Technical Design Specification (Updated - Rev 3)

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
    *   8.6 [Configuration (config.php)](#configuration-configphp)
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
    *   10.6 [Logging (Error, Audit, Security)](#logging-error-audit-security)
    *   10.7 [Encryption](#encryption)
    *   10.8 [Anomaly Detection](#anomaly-detection)
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

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document (**Revision 3**) is the definitive technical design specification for The Scent’s codebase, updated based on analysis of core project files. It offers deep insight into the system’s structure, logic, and flow, serving as a comprehensive onboarding and reference guide.

---

## 2. Project Philosophy & Goals

*   **Security First:** Enforced through input validation, CSRF protection, secure session management, security headers, rate limiting, anomaly detection, and encryption features found in `SecurityMiddleware` and `BaseController`.
*   **Simplicity & Maintainability:** Clear, modular structure (`includes`, `controllers`, `views`, `models`). Direct `require_once` usage in `index.php` prioritizes transparency over complex autoloading. Extensive helpers in `BaseController` promote code reuse.
*   **Extensibility:** Straightforward process for adding new routes, controllers, and views via `index.php` routing and controller inheritance.
*   **Performance:** Direct routing avoids framework overhead. Database queries should be optimized. (Note: CDN usage adds external dependencies).
*   **Modern User Experience:** Responsive design (Tailwind), smooth animations (AOS.js), particle effects (Particles.js), and AJAX interactions where implemented correctly (cart update/remove, newsletter).
*   **Transparency:** Application flow explicit in `index.php`. `BaseController` provides clear, reusable logic patterns.
*   **Accessibility & SEO:** Semantic HTML used. ARIA labels present on icons. Meta tags assumed. Further audits recommended.

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
   | (Includes core, Init Error/Security, Global Controller Instantiation (ProductController), Routing Logic, CSRF Check)
   v
[Controller]  (e.g., CartController - Included & Instantiated within route case; Inherits from BaseController)
   |
   | (Business logic using BaseController helpers, DB access via $this->db)
   v
[Model or DB Layer]  (e.g., models/Product.php, $this->db PDO object)
   |
   | (Fetch/prepare data using prepared statements)
   v
[View]  (e.g., views/home.php - Included from index.php or BaseController::renderView)
   |
   | (HTML/CSS/JS output using htmlspecialchars())
   v
[Browser/Client] <-- Renders HTML, executes JS (Tailwind, AOS, Particles, custom AJAX)
```

### 3.2 Request-Response Life Cycle

1.  **Request Initiation:** User navigates to URL (e.g., `/`, `/index.php?page=products`, `/products`).
2.  **.htaccess Rewrite:** `/.htaccess` rewrites requests (excluding existing files/dirs and specific patterns like `test_*.php`) to `/index.php`, preserving query strings.
3.  **Initialization:** `/index.php` defines `ROOT_PATH`, includes core files (`config.php`, `includes/*.php`). `$pdo` object becomes available globally from `db.php`. Error handling (`ErrorHandler::init()`) and global security middleware (`SecurityMiddleware::apply()`) are initialized. `ProductController` is instantiated globally.
4.  **Routing:** `index.php` determines `$page` and `$action` from `$_GET`, validating using `SecurityMiddleware::validateInput()`.
5.  **CSRF Check:** If POST request, `SecurityMiddleware::validateCSRF()` validates `$_POST['csrf_token']` against `$_SESSION['csrf_token']`.
6.  **Controller/View Dispatch:** `switch ($page)` routes the request:
    *   For most pages (cart, checkout, register, quiz, admin), the specific controller file is included, and the controller is instantiated *within* the `case`.
    *   Logic calls controller methods or directly includes view files (`require_once`).
7.  **Controller Action:** Controller methods (inheriting from `BaseController`) execute logic, interact with `$this->db` (PDO), use `BaseController` helpers for validation, auth checks, logging, etc.
8.  **View Rendering:** View files (`views/*.php`) generate HTML. Data is passed via scope variables or `BaseController::renderView()`. Output uses `htmlspecialchars()`. Layouts (`header.php`, `footer.php`) are included.
9.  **Response:** Output sent to browser.
10. **Client-Side Execution:** Browser renders HTML, applies CSS, executes JS (CDNs, custom).
11. **AJAX Interactions:** JS uses `fetch` for cart updates/removal, newsletter signups, sending CSRF tokens (when implemented correctly). Backend responds with JSON via `BaseController::jsonResponse`. **(Add-to-Cart Inconsistency):** JS attempts AJAX but backend redirects.

---

## 4. Directory & File Structure

### 4.1 Folder Map
*(Map confirmed accurate based on provided files)*

```
/ (project root)
|-- index.php
|-- config.php
|-- css/
|   |-- style.css
|-- images/
|-- videos/
|-- particles.json
|-- includes/
|   |-- auth.php
|   |-- db.php
|   |-- SecurityMiddleware.php
|   |-- ErrorHandler.php
|   |-- EmailService.php
|   |-- ...
|-- controllers/
|   |-- BaseController.php
|   |-- ProductController.php
|   |-- CartController.php
|   |-- ...
|-- models/
|   |-- Product.php
|   |-- User.php
|   |-- ...
|-- views/
|   |-- home.php
|   |-- cart.php
|   |-- products.php
|   |-- ...
|   |-- layout/
|   |   |-- header.php
|   |   |-- footer.php
|   |-- admin/
|   |-- emails/
|-- .htaccess
|-- .env (optional)
|-- README.md
|-- technical_design_specification_v3.md (This document)
|-- logs/ (Implied by config/BaseController - needs creation/permissions)
|   |-- security.log
|   |-- error.log
|   |-- audit.log
|   |-- YYYY-MM-DD.log
```

### 4.2 Key Files Explained

*   **index.php**: Entry point. Includes core files, initializes error/security, **globally instantiates `ProductController`**, handles routing via `switch`, **instantiates other controllers within cases**, validates CSRF, calls controller actions or includes views.
*   **config.php**: Defines constants for environment, database (`DB_*`), base URL (`BASE_URL`), Stripe (`STRIPE_*`), SMTP (`SMTP_*`), application settings (tax, shipping), logging paths, quiz config, and a detailed `SECURITY_SETTINGS` array (session, rate limiting, encryption, password policy, logging, CORS, CSRF, headers, file uploads).
*   **css/style.css**: Custom site-specific CSS rules.
*   **particles.json**: Configuration for Particles.js background animations.
*   **.htaccess**: Apache config. Enables rewriting, routes non-file/dir requests (excluding specific patterns like `test_*.php`, `deleteme.*.php`) to `index.php`.
*   **includes/db.php**: Establishes PDO connection using `config.php` constants. Sets PDO attributes. **Makes `$pdo` object globally available.** Handles connection errors.
*   **includes/auth.php**: Provides authentication helpers `isLoggedIn()`, `isAdmin()`.
*   **includes/SecurityMiddleware.php**: Class with static methods for security setup (`apply`), input validation (`validateInput` - covers various types including `filename`, `xml`, `json`, `html` via `HTMLPurifier`), CSRF validation (`validateCSRF`), token generation (`generateCSRFToken`). Implements request tracking and basic anomaly detection. Includes encryption (`encrypt`/`decrypt`) and file validation (`validateFileUpload` with optional ClamAV check). Includes supplemental `preventSQLInjection` (use with caution, prefer prepared statements).
*   **includes/ErrorHandler.php**: Provides static `init()` method to register error/exception handlers. Assumed to contain handler logic for logging and displaying error pages.
*   **controllers/BaseController.php**: Abstract base class. Provides `$db` (PDO), `$securityMiddleware` (instance), `$emailService` properties. Offers extensive helper methods for JSON/redirect responses, view rendering (`renderView`), validation, auth checks (`requireLogin`/`Admin`), instance-based CSRF methods (`validateCSRFToken`/`generateCSRFToken`), session flash messages, DB transactions, multiple rate limiting strategies (Session, APCu, Redis via `checkRateLimit`), logging (audit DB table, security file, general file), session integrity checks/regeneration, file uploads.
*   **controllers/*Controller.php**: Specific controllers (e.g., `ProductController`, `CartController`) extending `BaseController`, implementing module-specific logic.
*   **models/*.php**: Data entity classes (structure not detailed in provided files).
*   **views/layout/header.php**: Generates header, includes CSS/JS assets (**including AOS/Particles JS**), displays dynamic cart count (`$_SESSION['cart_count']`) and server flash messages (`$_SESSION['flash_message']`). Contains mobile menu JS.
*   **views/layout/footer.php**: Generates footer, includes JS (**also initializing AOS/Particles - duplicate init**), contains newsletter AJAX handler. Includes a **non-functional Add-to-Cart AJAX handler**.
*   **views/*.php**: Page templates mixing HTML/PHP. Included via `index.php` or `BaseController::renderView`.

---

## 5. Routing and Application Flow

### 5.1 URL Routing via .htaccess

*   **File:** `/.htaccess` (root).
*   **Mechanism:** `mod_rewrite` routes requests not matching existing files/directories or excluded patterns (`test_*.php`, `sample_*.html`, `deleteme.*.php`) to `/index.php`.
*   **Requires:** Apache `DocumentRoot` -> project root, `AllowOverride All`.

### 5.2 index.php: The Application Entry Point

**Location:** Project Root (`/index.php`)

**Execution Flow:**
1.  Define `ROOT_PATH`.
2.  Include core files (`config.php`, `db.php`, `auth.php`, `SecurityMiddleware.php`, `ErrorHandler.php`). `$pdo` becomes globally available.
3.  `ErrorHandler::init()`.
4.  `SecurityMiddleware::apply()` (sets headers, session params, starts session, periodic regen, init encryption, starts request tracking).
5.  **(Global) Instantiate `ProductController`.**
6.  Get/validate `$page`, `$action` from `$_GET` via `SecurityMiddleware::validateInput()`.
7.  If POST request, validate CSRF via `SecurityMiddleware::validateCSRF()`.
8.  Enter `switch ($page)`:
    *   Handle `home`, `product`, `products` using the global `$productController`.
    *   For other cases (`cart`, `checkout`, `register`, `quiz`, `admin`):
        *   `require_once` the specific controller file.
        *   Instantiate the controller (`new Controller($pdo)`).
        *   Execute logic based on `$action`, potentially calling controller methods or directly including views.
9.  Handle `default` case (404).
10. `try...catch` block handles PDOExceptions (logs) and re-throws other Exceptions for `ErrorHandler`.

### 5.3 Controller Dispatch & Action Flow

*   Controllers (`controllers/*.php`) extend `BaseController`.
*   `ProductController` instantiated globally in `index.php`; others instantiated within their route cases.
*   Methods handle specific actions, using inherited `$this->db` (PDO) and `BaseController` helpers.
*   Logic includes validation, auth checks (`requireLogin`/`Admin`), data fetching/processing.
*   Responses generated via `BaseController::jsonResponse`, `BaseController::redirect`, or by letting `index.php`/`BaseController::renderView` handle view inclusion.

### 5.4 Views: Templating and Rendering

*   Views (`views/*.php`) mix HTML and PHP for output.
*   Included via `require_once` (from `index.php`) or `BaseController::renderView()`.
*   Data passed via variable scope or `renderView` data array.
*   Include `header.php`, `footer.php`.
*   Output dynamic data using `htmlspecialchars()`.
*   Styling via `/css/style.css` and Tailwind CDN.
*   JS loaded via `<script>` tags in `header.php` / `footer.php`.

---

## 6. Frontend Architecture

### 6.1 CSS (css/style.css), Tailwind (CDN), and Other Libraries

*   **Styling:** Hybrid. Tailwind CDN (`header.php`) for utilities; `/css/style.css` for custom rules.
*   **Libraries (CDN via `header.php`):** Google Fonts, Font Awesome, AOS.js CSS, Particles.js JS, Tailwind Runtime JS.
*   **JS Libraries (CDN via `footer.php`):** AOS.js JS. **(Note: AOS.js script included in both header and footer - potential duplication/inefficiency).**

### 6.2 Responsive Design and Accessibility

*   **Responsive:** Primarily uses Tailwind prefixes (`md:`, `lg:`). Custom media queries possible in `/css/style.css`. JS handles mobile menu.
*   **Accessibility:** Semantic HTML, ARIA labels on icons. Mobile menu JS includes Escape key closing. Visual WCAG audit recommended.

### 6.3 JavaScript: Interactivity, AOS.js, Particles.js

*   **Initialization:** AOS (`AOS.init()`) and Particles (`particlesJS.load`) initialized in JS found in **both** `header.php` and `footer.php` (AOS script tag in header, init call in footer; Particles script tag in header, init call in footer). Uses `/particles.json`.
*   **Custom Interactivity:**
    *   **Mobile Menu Toggle:** Inline JS in `header.php`.
    *   **Sticky Header:** JS in `home.php`.
    *   **AJAX (using `fetch`):**
        *   *Newsletter:* Handlers in `home.php` (uses `showFlashMessage` for error) and `footer.php` (uses `alert` for error). POST to `newsletter/subscribe`, expect JSON.
        *   *Cart Removal (`cart.php`):* Button (`.remove-item`) triggers AJAX POST to `cart/remove`, expects JSON. **Missing CSRF token in fetch call.** Updates UI/totals.
        *   *Cart Update (`cart.php`):* Form (`#cartForm`) submit via AJAX POST to `cart/update`, expects JSON. **Missing CSRF token in fetch call.** Updates totals.
    *   **Flash Messages:**
        *   Server-side: Set via `BaseController::setFlashMessage` (`$_SESSION['flash']`), displayed/unset by `header.php`.
        *   Client-side (`home.php`): `showFlashMessage()` function creates dynamic Tailwind alerts. Used by (non-functional) Add-to-Cart JS and Newsletter error handling.
    *   **(MAJOR INCONSISTENCY) Add-to-Cart:** JS handlers (`.add-to-cart` in `home.php`/`footer.php`) attempt AJAX POST to `cart/add`, sending CSRF token, expecting JSON. However, the backend (`index.php`) explicitly performs a `header('Location: ...'); exit;` redirect for this action. **Result: Add-to-cart from product listings is currently a redirect, not AJAX.**

---

## 7. Key Pages & Components

### 7.1 Home/Landing Page (views/home.php)
*   Standard sections (Hero, About, Featured, Benefits, Quiz, Newsletter, Testimonials). Expects `$featuredProducts`. Uses `htmlspecialchars`. Conditional Add-to-Cart buttons.
*   Contains JS for AOS/Particles, sticky header, newsletter AJAX (`showFlashMessage` error), and the non-functional Add-to-Cart AJAX handler (`showFlashMessage`). Includes local `showFlashMessage` function definition.

### 7.2 Header and Navigation (views/layout/header.php)
*   Includes `auth.php`. Generates header, nav, icons. Includes CSS/JS assets (AOS/Particles JS included here). Conditional login/account link. Dynamic cart count (`$_SESSION['cart_count']`). Displays server flash messages (`$_SESSION['flash_message']`). Contains mobile menu JS.

### 7.3 Footer and Newsletter (views/layout/footer.php)
*   Generates footer content. Includes JS for AOS/Particles init (**duplicate init**). Contains newsletter AJAX handler (`alert` error). Contains non-functional Add-to-Cart AJAX handler (`alert` feedback).

### 7.4 Product Grid & Cards
*   Used on Home/Product Listing. Responsive grid. Card shows image, info, "View Details", conditional "Add to Cart" (`.add-to-cart`, `data-product-id`) / "Out of Stock". **Add-to-Cart currently triggers redirect.**

### 7.5 Shopping Cart (views/cart.php)
*   Expects `$cartItems`, `$total`. Displays items, summary. `#cartForm` targets `cart/update`.
*   Contains JS for: +/- buttons, AJAX item removal (POST `cart/remove`, **no CSRF**), AJAX cart update (POST `cart/update`, **no CSRF**), UI updates.

### 7.6 Quiz Flow & Personalization
*   Entry `page=quiz`. Interface `views/quiz.php`. Submit POST `quiz/submit`. Process `QuizController::processQuiz`. Results `views/quiz_results.php`. Admin analytics `admin/quiz_analytics`. Results potentially saved to DB.

---

## 8. Backend Logic & Core PHP Components

### 8.1 Includes: Shared Logic (includes/)
*   `/includes/` contains core files.
*   `auth.php`: Helpers `isLoggedIn`, `isAdmin`.
*   `db.php`: Sets up PDO, makes `$pdo` **globally available**.
*   `SecurityMiddleware.php`: Static methods (`apply`, `validateInput`, `validateCSRF`, `generateCSRFToken`). Also includes request tracking/anomaly detection, encryption, advanced validation, file upload checks (ClamAV).
*   `ErrorHandler.php`: Static `init`, handler logic.
*   `EmailService.php`: Used by `BaseController`.

### 8.2 Controllers: Business Logic Layer (controllers/ & BaseController.php)

*   Controllers extend abstract `BaseController`.
*   `BaseController.php` provides:
    *   Properties: `$db`, `$securityMiddleware` (instance), `$emailService`.
    *   Extensive Helpers: Responses (`jsonResponse`, `redirect`), rendering (`renderView`), validation (`validateInput`, `validateRequest`), auth (`requireLogin`/`Admin`), CSRF (instance `validateCSRFToken`/`generateCSRFToken`), flash messages, DB transactions, rate limiting (Session/APCu/Redis methods), logging (`logAuditTrail`, `logSecurityEvent`, `log`), session integrity, file uploads (`validateFileUpload`).
*   Specific controllers implement actions using inherited helpers. `ProductController` instantiated globally in `index.php`, others within route cases.

### 8.3 Database Abstraction (includes/db.php)

*   Uses PDO. `includes/db.php` creates connection using `config.php` constants, makes `$pdo` **globally available**. Sets `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES=false`. Controllers use `$this->db` (via `BaseController`). Prepared statements are essential.

### 8.4 Security Middleware & Error Handling

*   **SecurityMiddleware.php (`includes/`):**
    *   Static `apply()` called in `index.php` (sets headers, session params, starts session, tracks requests).
    *   Static `validateCSRF()` called in `index.php` for POSTs.
    *   Static `validateInput()` used for validation.
    *   Includes anomaly detection based on request frequency/patterns.
    *   Includes encryption/decryption methods.
*   **BaseController.php:**
    *   Instantiates `SecurityMiddleware`.
    *   Provides instance-based CSRF helpers (`validateCSRFToken`/`generateCSRFToken` - potential conflict/overlap with static methods).
    *   Provides rate limiting helpers (using Session/APCu/Redis).
    *   Provides logging helpers.
*   **ErrorHandler.php (`includes/`):**
    *   Static `init()` registers handlers. Catches exceptions, logs details, shows generic error page.

### 8.5 Session, Auth, and User Flow

*   **Session:** Started securely via `SecurityMiddleware::apply`. Secure attributes set. ID regenerated periodically. `BaseController` provides integrity checks (IP/User Agent) and regeneration helpers.
*   **Authentication:** `isLoggedIn()` helper. `AccountController` handles login, sets `$_SESSION['user_id']`, `$_SESSION['user_role']`. `BaseController::requireLogin`.
*   **Authorization:** `isAdmin()` helper. `BaseController::requireAdmin` checks role.
*   **Cart Count:** `$_SESSION['cart_count']` updated by `CartController`. Displayed by `header.php`.
*   **Flash Messages:** `$_SESSION['flash']` (`message`, `type`) set/get via `BaseController` helpers. Displayed/unset by `header.php`.

### 8.6 Configuration (config.php)

*   Defines constants for core settings:
    *   `ENVIRONMENT`: Runtime environment ('production', 'development', etc.).
    *   `DB_*`: Database connection details.
    *   `BASE_URL`: Application base URL path.
    *   `STRIPE_*`: Stripe API keys and webhook secret.
    *   `SMTP_*`: Email server configuration.
    *   App Settings: `TAX_RATE`, `FREE_SHIPPING_THRESHOLD`, `SHIPPING_COST`.
    *   Logging: `ERROR_LOG_PATH`, `ERROR_LOG_LEVEL`.
    *   Quiz: `QUIZ_MAX_ATTEMPTS`, `QUIZ_RESULT_EXPIRY_DAYS`, `RECOMMENDATION_LIMIT`.
    *   **`SECURITY_SETTINGS` (Array Constant):** Contains detailed sub-arrays for:
        *   `session`: lifetime, secure, httponly, samesite, regenerate interval.
        *   `rate_limiting`: enabled, defaults (window, max_requests), IP whitelist, specific endpoint limits (login, reset_password, register).
        *   `encryption`: algorithm, key length.
        *   `password`: min length, complexity requirements, max attempts, lockout duration.
        *   `logging`: paths for security, error, audit logs; rotation settings.
        *   `cors`: allowed origins, methods, headers, etc. (for potential API).
        *   `csrf`: enabled, token length, lifetime.
        *   `headers`: Specific values for security headers (X-Frame-Options, CSP, HSTS, etc.).
        *   `file_upload`: max size, allowed MIME types, malware scan flag.

---

## 9. Database Design

*(Schema details remain assumed unless schema file provided)*

### 9.1 Entity-Relationship Model
*(Standard e-commerce entities and relationships)*

### 9.2 Main Tables & Sample Schemas
*(Plausible SQL schemas provided. Requires validation against actual schema file. Assumes an `audit_log` table exists based on `BaseController`.)*

### 9.3 Data Flow Examples
*   **Add to Cart:** POST to `cart/add`. `CartController::addToCart`. Backend **redirects** to `cart` page.
*   **Update Cart:** AJAX POST to `cart/update` (**no CSRF in JS**). `CartController::updateCart`. Backend responds JSON. JS updates UI.
*   **Remove Cart Item:** AJAX POST to `cart/remove` (**no CSRF in JS**). `CartController::removeCartItem`. Backend responds JSON. JS updates UI.
*   **Place Order:** POST to `checkout/process`. `CheckoutController::processCheckout`. Creates DB records, clears cart, handles payment, redirects.
*   **Take Quiz:** POST to `quiz/submit`. `QuizController::processQuiz`. Saves/processes results, includes results view.
*   **Newsletter Signup:** AJAX POST to `newsletter/subscribe`. `NewsletterController::subscribe`. Saves email, responds JSON. JS shows feedback.

---

## 10. Security Considerations

### 10.1 Input Sanitization & Validation
*   Use `SecurityMiddleware::validateInput` (covers strings, int, float, email, url, password strength, date, array, filename, xml, json, html via HTMLPurifier). Use `BaseController` helpers.
*   Use PDO prepared statements EXCLUSIVELY for DB queries.
*   Use `htmlspecialchars()` for ALL HTML output.
*   Supplemental `SecurityMiddleware::preventSQLInjection` exists but rely on prepared statements.

### 10.2 Session Management
*   Secure attributes set via `SecurityMiddleware::apply` (Secure, HttpOnly, SameSite=Lax).
*   Session ID regenerated periodically (`SecurityMiddleware::apply`) and on login/logout.
*   `BaseController` provides session integrity checks (IP/User Agent) and termination (`terminateSession`). Configure lifetime via `SECURITY_SETTINGS`.

### 10.3 CSRF Protection
*   Enabled via `SECURITY_SETTINGS['csrf']['enabled']`.
*   Token generated (`SecurityMiddleware::generateCSRFToken` static or `BaseController::generateCSRFToken` instance), stored in session. Length/lifetime configured.
*   Token included in forms (`csrf_token` field) and should be sent with state-changing AJAX POSTs.
*   Validation via static `SecurityMiddleware::validateCSRF()` in `index.php` for POSTs (uses `hash_equals`). Instance validation also available in `BaseController`.
*   **Deficiency:** AJAX calls in `views/cart.php` (update/remove) currently **do not send** the CSRF token.

### 10.4 Security Headers & Rate Limiting
*   **Headers:** Set via `SecurityMiddleware::apply()` and `BaseController::initializeSecurityHeaders`. Specific headers and CSP configured in `SECURITY_SETTINGS['headers']`. Includes HSTS.
*   **Rate Limiting:** Enabled via `SECURITY_SETTINGS['rate_limiting']['enabled']`. Multiple methods available in `BaseController` (Session, APCu, Redis - requires setup). Configurable defaults and endpoint-specific limits in `SECURITY_SETTINGS`. IP whitelist available.

### 10.5 File Uploads & Permissions
*   Use `BaseController::validateFileUpload` or `SecurityMiddleware::validateFileUpload`. Validates error code, size (`SECURITY_SETTINGS['file_upload']['max_size']`), MIME type against allowlist (`SECURITY_SETTINGS['file_upload']['allowed_types']`). Optional malware scan via ClamAV (`SECURITY_SETTINGS['file_upload']['scan_malware']`).
*   Use `SecurityMiddleware::sanitizeFileName`.
*   Store uploads securely. Apply restrictive filesystem permissions. Protect `config.php`.

### 10.6 Logging (Error, Audit, Security)
*   **Error Logging:** `ErrorHandler::init` registers handler. Logs PHP errors/exceptions to file specified in `config.php` (likely via `SECURITY_SETTINGS['logging']['error_log']`) or default PHP log. `display_errors=Off`.
*   **Audit Logging:** `BaseController::logAuditTrail` logs actions to `audit_log` DB table (action, user ID, IP, user agent, details).
*   **Security Logging:** `BaseController::logSecurityEvent` logs security-relevant events (rate limit, CSRF failure, auth failure, session termination) to file (`SECURITY_SETTINGS['logging']['security_log']`).
*   Log rotation configured via `SECURITY_SETTINGS['logging']`.

### 10.7 Encryption
*   `SecurityMiddleware` provides `encrypt()` and `decrypt()` methods using AES-256-CBC.
*   Requires an encryption key (set via `ENCRYPTION_KEY` environment variable or auto-generated by `SecurityMiddleware`). Algorithm/key length configurable via `SECURITY_SETTINGS['encryption']`. Used for sensitive data storage if needed.

### 10.8 Anomaly Detection
*   `SecurityMiddleware::apply()` includes basic request tracking per IP.
*   `detectAnomaly` checks for rapid requests (e.g., >100/min) and basic attack patterns in URIs (SQLi fragments, path traversal, script tags).
*   If anomaly detected, logs error, adds IP to temporary blacklist (`self::$ipTracker`), and exits with 403.

---

## 11. Extensibility & Onboarding

### 11.1 Adding Features, Pages, or Controllers
1.  Create `Controller` extending `BaseController`.
2.  Create `View(s)`.
3.  Add `case` to `index.php`: `require_once`, instantiate controller, call method or include view.
4.  Add nav links.
5.  (Optional) Create `Model`, update DB schema.

### 11.2 Adding Products, Categories, and Quiz Questions
*   **Products/Categories:** Requires direct DB access or admin interface.
*   **Quiz:** Modify `QuizController` or move mapping to DB/config.

### 11.3 Developer Onboarding Checklist
1.  Install Apache, PHP 8+, MySQL/MariaDB, Git. (Potentially Composer if HTMLPurifier/other libs added). Ensure required PHP extensions enabled (PDO, OpenSSL, Finfo, potentially APCu/Redis if used for rate limiting).
2.  Clone repo.
3.  Update `config.php` (DB credentials, SMTP, Stripe, adjust `SECURITY_SETTINGS` if needed). Protect permissions (e.g., 640). Set `ENCRYPTION_KEY` env var.
4.  Create DB, import schema, seed data. Create `audit_log` table if using DB audit trail.
5.  Configure Apache VHost: `DocumentRoot` -> project root, `AllowOverride All`. Enable `mod_rewrite`. Restart.
6.  Create `logs/` directory if needed, ensure web server user (`www-data`) has write permission to it. Restrict other write permissions.
7.  Test site flows (Home, Products, Add-to-Cart (redirect), Cart (update/remove - check CSRF issue), Checkout, Login, Register, Quiz). Check PHP/Apache logs AND application logs (`logs/`).

### 11.4 Testing & Debugging
*   Check PHP (`error_log`), Apache (`error_log`), and Application (`logs/*.log`) logs.
*   Use browser dev tools.
*   **Address Inconsistencies:** Fix Add-to-Cart AJAX/Redirect mismatch. Fix missing CSRF tokens in `cart.php` AJAX. Resolve duplicate JS init (AOS/Particles). Standardize flash message feedback. Standardize CSRF method usage (static vs. instance). Verify active rate limiting mechanism.
*   Manual flow testing & security checks.
*   Use `var_dump`/`error_log` or Xdebug.

---

## 12. Future Enhancements & Recommendations

*(Standard recommendations remain valid: Autoloader, Templating Engine, Framework, API, Admin Panel, Formal Testing, Dependency Management, Docker, CI/CD, Performance Opt., i18n, Accessibility Audit, User Profiles, Analytics.)*

---

## 13. Appendices

### A. Key File Summaries

| File/Folder                 | Purpose                                                                                               |
| :-------------------------- | :---------------------------------------------------------------------------------------------------- |
| `index.php`                 | Entry point, routing, core includes, global `ProductController` init, case-specific controller init, CSRF check |
| `config.php`                | Defines constants: DB, Stripe, SMTP, App settings, detailed `SECURITY_SETTINGS` array                     |
| `css/style.css`             | Custom CSS rules                                                                                       |
| `particles.json`            | Particles.js configuration                                                                           |
| `.htaccess`                 | Apache URL rewriting, access control (excludes `test_*`, `deleteme.*`)                                  |
| `includes/auth.php`         | Authentication helper functions (`isLoggedIn`, `isAdmin`)                                             |
| `includes/db.php`           | PDO DB connection setup (provides global `$pdo`)                                                        |
| `includes/SecurityMiddleware.php` | Security class: Static methods (`apply`, `validateInput`, `validateCSRF`), anomaly detection, encryption |
| `includes/ErrorHandler.php` | Centralized error/exception handling (static `init`, logging logic)                                     |
| `controllers/BaseController.php` | Abstract base: Provides shared helpers (DB, validation, response, auth, CSRF, logging, rate limit)    |
| `controllers/*Controller.php` | Specific business logic handlers extending `BaseController`                                             |
| `models/*.php`              | Data entity representation                                                                            |
| `views/*.php`               | HTML/PHP templates                                                                                   |
| `views/layout/header.php`   | Header, nav, asset loading (CSS/JS), session flash/cart display, mobile menu JS                        |
| `views/layout/footer.php`   | Footer, JS init (AOS/Particles - duplicate), newsletter AJAX, non-functional add-to-cart JS             |

### B. Glossary
*(Standard terms remain valid)*

### C. Code Snippets and Patterns

#### Example: Routing/Controller Instantiation in `index.php`

```php
<?php
// ... includes ...
ErrorHandler::init();
SecurityMiddleware::apply();
$pdo = new PDO(...); // Assuming db.php makes $pdo available globally

// Global instantiation for ProductController
require_once __DIR__ . '/controllers/ProductController.php';
$productController = new ProductController($pdo);

// ... CSRF Check ...

try {
    switch ($page) {
        case 'home':
            $productController->showHomePage(); // Uses global instance
            break;
        case 'cart':
             require_once ROOT_PATH . '/controllers/CartController.php'; // Include inside case
             $controller = new CartController($pdo); // Instantiate inside case
             if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                  // ... validation ...
                  $controller->addToCart($productId, $quantity);
                  header('Location: index.php?page=cart'); // Redirect behavior
                  exit;
             } // ... other cart actions ...
             break;
        // ... other cases instantiate controllers inside ...
    }
} catch (Exception $e) { // ... }
?>
```

#### Example: Using BaseController Helpers (Conceptual)

```php
<?php
// Inside a controller method extending BaseController (e.g., CartController::updateCart)
public function updateCart($quantities) {
    $this->requireLogin(); // Check authentication
    // $this->validateCSRFToken(); // Validate CSRF (instance method - ensure consistency)

    // Validate input using BaseController helper which uses SecurityMiddleware
    // $this->validateInput($quantities, ['type' => 'array', 'itemType' => 'int']); // Example

    try {
        // Update logic using $this->db (PDO object)
        // ...
        $this->logAuditTrail('cart_update', $this->getUserId(), ['items_updated' => count($quantities)]);
        $newCartCount = $this->calculateCartCount(); // Example calculation
        // Respond with JSON using BaseController helper
        $this->jsonResponse(['success' => true, 'message' => 'Cart updated', 'cart_count' => $newCartCount]);
    } catch (Exception $e) {
        $this->log('Cart update failed: ' . $e->getMessage(), 'error');
        $this->jsonResponse(['success' => false, 'message' => 'Could not update cart'], 500);
    }
}
?>
```

#### Example: Corrected AJAX Cart Removal JS (Adding CSRF)

```javascript
// In views/cart.php script block
document.querySelectorAll('.remove-item').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        // *** Get CSRF token from a hidden field (ensure one exists on the page) ***
        const csrfTokenInput = document.querySelector('input[name="csrf_token"]');
        const csrfToken = csrfTokenInput ? csrfTokenInput.value : null;

        if (!csrfToken) {
             alert('Security token missing. Please refresh the page.');
             // Optionally disable button or provide better feedback
             return;
        }
        if (!confirm('Are you sure you want to remove this item?')) {
            return;
        }

        const formData = new URLSearchParams();
        formData.append('product_id', productId);
        formData.append('csrf_token', csrfToken); // *** SEND CSRF TOKEN ***

        fetch('index.php?page=cart&action=remove', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: formData
        })
        .then(/* ... standard response handling ... */)
        .catch(/* ... standard error handling ... */);
    });
});
// NOTE: Similar correction needed for cart update AJAX form submission
```

---

**End of Technical Design Specification (Updated - Rev 3)**

This revision incorporates findings from the provided code snippets, offering a more accurate reflection of the current implementation, including its strengths (e.g., `BaseController` helpers, detailed security config) and weaknesses (e.g., Add-to-Cart inconsistency, missing CSRF in some AJAX calls). It serves as an up-to-date guide for development, onboarding, and identifying areas for refactoring.
```
