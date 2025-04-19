https://g.co/gemini/share/6419be0e8a1f  
\<think\>
The user wants an updated technical design specification (`technical_design_specification.md`) for their e-commerce project. I need to compare the provided original specification (`technical_design_specification.md.txt`), the current project file structure (`ls` and `grep` output), and the actual landing page HTML (`current_landing_page.html.txt`) to identify discrepancies and produce an accurate, updated document in Markdown format.

**1. Deconstruct the Request:**
\* **Goal:** Create an enhanced and updated `technical_design_specification.md`.
\* **Inputs:** Original spec (Markdown), current file structure (`ls`, `grep`), current landing page HTML, tech stack info (Apache/PHP/MySQL/Ubuntu).
\* **Key Constraint:** The current file structure differs from the original spec (specifically, the removal of the `/public` directory as the webroot).
\* **Process:** Careful review, deep thinking, systematic reasoning, explore implementation options, choose the best, generate the updated Markdown.
\* **Output:** Complete Markdown content for the updated specification.

**2. Analyze Provided Information & Identify Discrepancies:**
\* **File Structure:**
\* *Original Spec:* Describes a `/public` directory containing `index.php`, `css`, `images`, `videos`, `particles.json`, `.htaccess`. Other core directories (`includes`, `controllers`, `models`, `views`) are outside `/public`[cite: 36, 41, 42, 44].
\* *Actual (`ls` output):* `index.php`, `config.php`, `css/`, `images/`, `videos/`, `particles.json`, `includes/`, `controllers/`, `models/`, `views/` are all in the project root (`/cdrom/project/The-Scent-oa5`). There is no `/public` directory.
\* *Discrepancy:* Major structural change - the web root is now the project root, not a dedicated `/public` subdirectory. `config.php` is also in the root.
\* **Entry Point & Routing:**
\* *Original Spec:* `public/index.php` is the entry point[cite: 17, 22, 30, 55, 66]. `.htaccess` in `/public` rewrites to `public/index.php`[cite: 35, 59, 66]. Routing logic is within `public/index.php`.
\* *Actual (`ls`, `grep`):* `index.php` is in the root. `grep` shows `index.php` uses `require_once __DIR__ . '/...'` to include files from `includes/`, `controllers/`, `views/`, confirming it operates from the root.
\* *Discrepancy:* Entry point is now `index.php` in the root. `.htaccess` (if used for clean URLs) would need to be in the root and Apache's `DocumentRoot` configured to the project root. The internal routing logic within `index.php` seems conceptually similar based on the `grep` output showing controller/view includes based on logic.
\* **Asset Paths:**
\* *Original Spec:* Implies paths relative to `/public` (e.g., `/css/style.css` within the web context)[cite: 31].
\* *Actual (HTML):* Uses root-relative paths (e.g., `/css/style.css`, `/images/products/1.jpg`, `/videos/hero.mp4`, `/particles.json`)[cite: 206, 220, 227, 304].
\* *Discrepancy:* Confirms the web root change. Asset paths in the spec need updating.
\* **Frontend Dependencies:**
\* *Original Spec:* Mentions Tailwind *integration* potentially within `style.css`[cite: 31, 57, 84, 175], AOS.js, Particles.js[cite: 12, 93, 94, 190].
\* *Actual (HTML):* Uses Tailwind via CDN script, AOS.js via CDN link[cite: 202], Particles.js via CDN link[cite: 202], Google Fonts[cite: 202], Font Awesome[cite: 202]. Also links local `/css/style.css`[cite: 206].
\* *Discrepancy:* Tailwind usage differs (CDN vs. integrated). Other JS libraries are loaded via CDN. The spec should reflect CDN usage for Tailwind, AOS, Particles, and the inclusion of Google Fonts/Font Awesome, alongside the custom `style.css`.
\* **PHP Includes (`grep`):**
\* Shows extensive use of `require_once __DIR__ . '/...'` in `index.php`. This confirms a direct inclusion approach rather than a sophisticated autoloader, matching the non-framework philosophy mentioned in the spec[cite: 5].

**3. Explore Options for Updating the Specification:**
\* **Option 1: Patching:** Minimal changes, just fix paths like `public/` -\> `/`. Quick but potentially inconsistent.
\* **Option 2: Partial Rewrite:** Rewrite Sections 4 (Structure) and 5 (Routing). Better but might miss related details elsewhere.
\* **Option 3: Comprehensive Review & Update:** Go through the entire document, comparing each section to the actual state (`ls`, `grep`, HTML) and updating accordingly. This includes structure, routing, asset paths, frontend dependencies, code examples, and onboarding instructions. This is the most thorough and accurate approach.

**4. Select the Best Option:**
\* Option 3 (Comprehensive Review & Update) best matches the user's request for a careful, thorough, enhanced, and updated document, fulfilling the "deep-thinking" requirement.

**5. Plan the Implementation (Updating the Markdown):**
\* Load the original `technical_design_specification.md.txt` content.
\* **Title/Intro:** Keep as is.
\* **Table of Contents:** Update section titles/numbers if changes necessitate it (unlikely based on planned changes). Remove references to `public/` in section titles if any exist (e.g., "CSS (public/css/style.css)" -\> "CSS (css/style.css)"). Specifically update 5.2 title.
\* **Section 3 (Architecture):** Update diagram [cite: 17] and lifecycle text [cite: 22, 23] to show `index.php` at the root as the entry point.
\* **Section 4 (Structure):**
\* Rewrite `Folder Map` (4.1) based on `ls` output: `index.php`, `config.php`, `css/`, `images/`, `videos/`, `particles.json`, `.htaccess` (mention it's likely hidden but in root), `includes/`, `controllers/`, `models/`, `views/`. No `/public`.
\* Update `Key Files Explained` (4.2): Correct paths (remove `public/`). Add `config.php`. Ensure descriptions align with `ls` and `grep`.
\* **Section 5 (Routing):** Update `.htaccess` path[cite: 66]. Update `index.php` path [cite: 67, 72, 73] and title. Ensure description reflects `grep` output (using `__DIR__` for includes).
\* **Section 6 (Frontend):** Update CSS path[cite: 31, 83]. Modify 6.1 to describe Tailwind being loaded via CDN `<script>` alongside custom styles in `/css/style.css`[cite: 206]. Mention CDN usage for AOS.js [cite: 202] and Particles.js[cite: 202], plus Google Fonts [cite: 202] and Font Awesome[cite: 202].
\* **Section 7 (Pages/Components):** Verify file paths like `views/home.php` [cite: 64] are still correct relative to the root (they appear to be). Check descriptions against HTML output for accuracy.
\* **Section 11 (Extensibility/Onboarding):** Update paths in examples[cite: 154]. Update Apache VHost configuration step [cite: 162] to point DocumentRoot to the project root.
\* **Section 13 (Appendices):** Update paths in Key File Summaries. Update code snippets if they contain outdated paths. The provided snippets look mostly okay but double-check paths like `ROOT_PATH . '/views/404.php'` [cite: 195] which should resolve correctly if `ROOT_PATH` is defined as `__DIR__` in the root `index.php`. Ajax example `X-CSRF-Token` header [cite: 197] might not match the actual implementation seen in HTML (`csrf_token` in body)[cite: 309, 363]. Update the AJAX snippet to reflect form data usage.
\* **General:** Search and replace any remaining `public/` path segments where relevant. Ensure consistency throughout.

**6. Validation:**
\* The plan covers all identified discrepancies.
\* It uses all provided inputs.
\* It aims for a comprehensive and accurate update.
\* The output will be in the requested Markdown format.
\* The approach aligns with the "deep-thinking" persona.

**7. Execution:** Generate the updated Markdown content. I will now construct the final response containing the full, updated Markdown text based on this plan. I will synthesize the information from the original spec, the landing page HTML, and the file structure/grep info provided in the prompt.

```markdown
# The Scent – Technical Design Specification (Updated)

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
    * 7.5 [Quiz Flow & Personalization](#quiz-flow--personalization)
8.  [Backend Logic & Core PHP Components](#backend-logic--core-php-components)
    * 8.1 [Includes: Shared Logic (includes/)](#includes-shared-logic-includes)
    * 8.2 [Controllers: Business Logic Layer](#controllers-business-logic-layer)
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

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products[cite: 4]. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control[cite: 5]. This document is the definitive technical design specification for The Scent’s codebase[cite: 6]. It is intended to offer deep insight into the system’s structure, logic, and flow, and to serve as a comprehensive onboarding and reference guide for both current developers and future maintainers[cite: 7].

*(Self-Correction: Introduction remains accurate based on project goals and observed structure.)*

---

## 2. Project Philosophy & Goals

* **Security First:** All data input and user interactions are validated and sanitized[cite: 8]. Strong session and CSRF protection are enforced[cite: 9].
* **Simplicity & Maintainability:** Clear, modular code structure. No over-engineered abstractions[cite: 9]. Direct `require_once` usage observed fits this philosophy.
* **Extensibility:** Easy to add new features, pages, controllers, or views[cite: 10].
* **Performance:** Direct routing, optimized queries, and minimized external dependencies[cite: 11]. (Note: CDN usage for frontend libraries introduces external dependencies [cite: 202, 203]).
* **Modern User Experience:** Responsive design, smooth animations (AOS.js, Particles.js), and AJAX interactions for cart and newsletter.
* **Transparency:** No magic – all application flow and routing is explicit in code[cite: 13], as seen in `index.php` includes.
* **Accessibility & SEO:** Semantic HTML, ARIA attributes where needed, and meta tags for discoverability[cite: 14, 89, 91, 217, 218, 219].

*(Self-Correction: Added note about CDN dependencies affecting the "minimized external dependencies" goal slightly. Otherwise, goals align with observed implementation.)*

---

## 3. System Architecture Overview

### 3.1 High-Level Workflow

```

[Browser/Client]
|
| (HTTP request)
v
[Apache2 Web Server] \<-- DocumentRoot points to project root
|
| (URL rewriting via .htaccess in root)
v
[index.php]  \<-- ENTRY POINT (in project root)
|
| (Routing logic, includes core files)
v
[Controller]  (e.g., ProductController, CartController)
|
| (Business logic, DB access via includes/db.php)
v
[Model or DB Layer]  (e.g., models/Product.php, includes/db.php)
|
| (Fetch/prepare data)
v
[View]  (e.g., views/home.php)
|
| (HTML/CSS/JS output)
v
[Browser/Client] \<-- Renders HTML, executes JS (Tailwind, AOS, Particles, custom) [cite: 202, 203, 206, 304]

```

*(Self-Correction: Updated diagram and annotations to reflect `index.php` being in the root and acting as the entry point, and Apache pointing to the root.)*

### 3.2 Request-Response Life Cycle

1.  **Request Initiation:** User navigates to any URL (e.g., `/`, `/index.php?page=products`, `/products` if clean URLs are configured).
2.  **.htaccess Rewrite:** If clean URLs are enabled via `.htaccess` in the project root, requests are rewritten to `index.php`, passing query strings as needed[cite: 66]. (Requires Apache `DocumentRoot` set to project root and `AllowOverride All`).
3.  **Initialization:** `index.php` (in the root) loads configuration (`config.php`), database (`includes/db.php`), authentication (`includes/auth.php`), security (`includes/SecurityMiddleware.php`), and error handling (`includes/ErrorHandler.php`) using `require_once __DIR__ . '/...'`.
4.  **Routing:** Based on `$_GET['page']` and `$_GET['action']`, the correct controller and view includes are triggered within `index.php`'s logic.
5.  **Controller Action:** If a controller file is included, its methods perform business logic (database fetch, form processing, etc.)[cite: 25].
6.  **View Rendering:** The correct view (template PHP file from `views/`) is included, outputting HTML/CSS/JS. Header and footer layouts are typically included within views[cite: 62, 63, 79].
7.  **Response:** Output is sent to the browser, which renders the page and runs frontend JS (AOS, Particles, Tailwind runtime, custom logic for menus, AJAX, etc.).
8.  **AJAX Interactions:** For cart/newsletter, AJAX requests hit `index.php` with appropriate `page` and `action` parameters, returning JSON or status updates for the frontend JS to handle without a full page reload.
9.  **Security:** Every step is subject to security middleware checks (input validation, CSRF), and error handling[cite: 29, 61, 131, 133, 142].

*(Self-Correction: Updated paths and emphasis based on the root `index.php` entry point and observed `require_once` usage.)*

---

## 4. Directory & File Structure

### 4.1 Folder Map

```

/ (project root - e.g., /cdrom/project/The-Scent-oa5) \<-- Apache DocumentRoot points here
|-- index.php              \# Main entry script (routing, dispatch, includes core files)
|-- config.php             \# Environment, DB, and app configuration
|-- css/
|   |-- style.css          \# Custom CSS styles (used alongside CDN Tailwind)
|-- images/                \# Public image assets (product images, UI elements, etc.)
|   |-- about/
|   |-- backgrounds/
|   |-- icons/
|   |-- logo/
|   |-- products/
|   |-- textures/
|   |-- ui/
|   |-- ... (various image files)
|-- videos/                \# Public video assets (e.g., hero background)
|   |-- hero.mp4
|-- particles.json         \# Particles.js configuration file
|-- includes/              \# Shared PHP utility/core files
|   |-- auth.php           \# User login/logout/session helpers
|   |-- db.php             \# Database connection via PDO
|   |-- SecurityMiddleware.php \# CSRF, input validation, security headers
|   |-- ErrorHandler.php   \# Centralized error handling/logging
|   |-- EmailService.php   \# Email sending logic (SMTP)
|   |-- ... (test files, backups - should be cleaned up)
|-- controllers/           \# Business logic / request handlers
|   |-- ProductController.php
|   |-- CartController.php
|   |-- CheckoutController.php
|   |-- AccountController.php
|   |-- QuizController.php
|   |-- BaseController.php \# Likely a base class for controllers
|   |-- ... (others for newsletter, payment, tax, inventory etc.)
|-- models/                \# Data representation / Database interaction logic
|   |-- Product.php
|   |-- User.php
|   |-- Order.php
|   |-- Quiz.php
|-- views/                 \# HTML templates (server-rendered PHP files)
|   |-- home.php           \# Main landing page view
|   |-- products.php       \# Product listing view
|   |-- product\_detail.php \# Individual product page view
|   |-- cart.php           \# Shopping cart view
|   |-- checkout.php       \# Checkout process view
|   |-- register.php       \# Registration view
|   |-- login.php          \# Login form view
|   |-- quiz.php           \# Scent quiz interface view
|   |-- quiz\_results.php   \# Quiz results view
|   |-- error.php          \# Error display view
|   |-- 404.php            \# Page not found view
|   |-- account/           \# Views related to user accounts
|   |-- admin/             \# Views for administrative functions
|   |-- emails/            \# Email templates
|   |-- layout/
|   |   |-- header.php     \# Sitewide header/nav view partial
|   |   |-- footer.php     \# Sitewide footer/newsletter/social view partial
|-- .htaccess              \# (Optional/Recommended) Apache URL rewrite rules for clean routing (should be in root)
|-- .env                   \# (Optional) Environment variables, secrets (should NOT be web accessible)
|-- README.md              \# Project intro/instructions
|-- technical\_design\_specification.md \# This document (Updated)

````

*(Self-Correction: Completely revised the folder map based on the `ls` output provided in the prompt. Removed the `/public` directory structure. Added `config.php` to the root. Confirmed subdirectory contents like `images/`.)*

### 4.2 Key Files Explained

* **index.php**: Application entry point in the project root. Implements basic routing by including controllers/views based on URL parameters (`$_GET['page']`, `$_GET['action']`). Loads core dependencies (`config.php`, `includes/*.php`) using `require_once __DIR__`.
* **config.php**: Central configuration for Database credentials, email settings, security parameters, potentially API keys (like Stripe). Located in the project root.
* **css/style.css**: Contains custom CSS rules for the site. It complements styles provided by the Tailwind CSS framework, which is loaded via CDN. Used for component-specific styles, overrides, or non-utility styling needs[cite: 206].
* **particles.json**: Defines particle backgrounds configuration used by the Particles.js library. Located in the project root.
* **.htaccess**: (Recommended, in project root) Apache rewrite rules to enable clean URLs (e.g., `/products` instead of `/index.php?page=products`). All traffic (except existing files/directories) is routed to `index.php`[cite: 66]. Requires Apache `AllowOverride` enabled.
* **includes/db.php**: Establishes PDO database connection using credentials from `config.php`. Includes basic error handling.
* **includes/auth.php**: Handles user session management, login/logout functions, registration logic, and potentially role/permission checks.
* **includes/SecurityMiddleware.php**: Provides functions for input validation/sanitization (`validateInput`), CSRF token generation/validation, and setting security-related HTTP headers.
* **includes/ErrorHandler.php**: Defines custom error handling logic, likely logging errors and displaying user-friendly error pages.
* **views/layout/header.php**: Generates the common site header, including logo, navigation menu (desktop and mobile logic), account/cart icons, and potentially outputs session flash messages. Included by most other view files.
* **views/layout/footer.php**: Generates the common site footer, including links, newsletter form, social icons, and copyright information. Also often includes sitewide JavaScript initialization (like AOS, Particles).
* **views/home.php**: The view file specifically for the main landing page. It integrates various sections like the hero banner, featured products, about snippet, benefits, quiz call-to-action, newsletter signup, and testimonials.
* **controllers/**: Directory containing PHP classes/files responsible for handling specific application domains (e.g., `ProductController.php` for product logic, `CartController.php` for cart actions). They process requests, interact with models/database, and prepare data for the views.
* **models/**: Directory containing PHP classes that represent data structures (e.g., `Product.php`, `User.php`) and may contain logic for database interactions related to that data.

*(Self-Correction: Updated paths, added `config.php`, clarified CSS/Tailwind relationship, confirmed locations based on `ls`.)*

---

## 5. Routing and Application Flow

### 5.1 URL Routing via .htaccess

*(Assumption: Clean URLs are desired, requiring `.htaccess` in the project root)*

**/.htaccess** (typical content):

```apache
# Enable URL rewriting
RewriteEngine On

# Do not rewrite for existing files or directories
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Route all other requests to index.php in the root, preserving query params
RewriteRule ^(.*)$ index.php [QSA,L]
````

  * Requires Apache's `DocumentRoot` to be set to the project's root directory.
  * Requires `AllowOverride All` (or appropriate setting) in Apache config for the directory.
  * All requests not matching physical files/directories are handled by `/index.php`[cite: 66].
  * URLs like `/`, `/products`, `/cart` would be internally routed to `/index.php` which then uses `$_SERVER['REQUEST_URI']` or parses the path (or relies on query params like `/index.php?page=products`) to determine the action. The current implementation seems reliant on query parameters (`?page=...`) based on HTML links [cite: 215, 216, 217, 218] and `index.php` logic[cite: 73].

*(Self-Correction: Specified `.htaccess` should be in the root and updated path references. Clarified reliance on query parameters based on observed HTML.)*

### 5.2 index.php: The Application Entry Point

**Location:** Project Root (`/index.php`)

**Key Responsibilities:**

  * Define constants if needed (e.g., `ROOT_PATH = __DIR__;`).
  * Require all core `includes/` files (config, db, auth, security, error handler) using `require_once __DIR__ . '/includes/...'`.
  * Initialize error handling (`ErrorHandler::register()` or similar).
  * Apply security middleware (e.g., start secure session, set headers via `SecurityMiddleware`).
  * Determine requested page/action, typically via `$_GET` parameters (e.g., `$_GET['page']`, `$_GET['action']`), with validation using `SecurityMiddleware::validateInput()`[cite: 70, 73].
  * Validate CSRF tokens on POST requests using `SecurityMiddleware::checkCsrfToken()`[cite: 71].
  * Use a `switch` statement or `if/else` structure based on the `$page` variable to:
      * Include the relevant `Controller` file (e.g., `require_once __DIR__ . '/controllers/ProductController.php';`).
      * Instantiate the controller (e.g., `$controller = new ProductController($pdo);`).
      * Call an appropriate method on the controller OR directly include the relevant `View` file (e.g., `require_once __DIR__ . '/views/home.php';`).
  * Handle default/unknown pages by showing a 404 error page (e.g., `require_once __DIR__ . '/views/404.php';`).
  * Wrap main dispatch logic in a try/catch block to pass exceptions to the `ErrorHandler`[cite: 196].

**Excerpt (Reflecting actual includes):**

```php
<?php
// index.php (in project root)

// Strict types and error reporting (recommended)
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Log errors, don't display in prod

// Define Root Path
define('ROOT_PATH', __DIR__);

// Core Includes
require_once ROOT_PATH . '/config.php'; // Load Config first
require_once ROOT_PATH . '/includes/ErrorHandler.php';
require_once ROOT_PATH . '/includes/SecurityMiddleware.php';
require_once ROOT_PATH . '/includes/db.php'; // Needs config vars
require_once ROOT_PATH . '/includes/auth.php'; // Needs Session/DB

// Register Error Handler
ErrorHandler::register();

// Start secure session
SecurityMiddleware::startSecureSession();

// Basic CSRF protection for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityMiddleware::checkCsrfToken();
}

// Initialize DB Connection (example, db.php might return $pdo)
$pdo = connect_db(); // Assuming a function in db.php

// Routing Logic
$page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string_alphanum'); // Example validation
$action = SecurityMiddleware::validateInput($_GET['action'] ?? 'index', 'string_alphanum'); // Example validation

try {
    switch ($page) {
        case 'home':
            require_once ROOT_PATH . '/controllers/ProductController.php';
            $controller = new ProductController($pdo);
            $controller->showHomePage(); // Assumes controller handles including the view
            break;
        case 'products':
             require_once ROOT_PATH . '/controllers/ProductController.php';
             $controller = new ProductController($pdo);
             if ($action === 'show') {
                 $id = SecurityMiddleware::validateInput($_GET['id'] ?? null, 'int');
                 $controller->showProductDetail($id);
             } else {
                 $controller->listProducts();
             }
             break;
        case 'cart':
            require_once ROOT_PATH . '/controllers/CartController.php';
            $controller = new CartController($pdo);
            // Example: Actions could be 'add', 'remove', 'show'
            if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                 $controller->add(); // Handles AJAX response
            } else {
                 $controller->showCart(); // Includes views/cart.php
            }
            break;
        case 'quiz':
             require_once ROOT_PATH . '/controllers/QuizController.php';
             $controller = new QuizController($pdo);
             if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                 $controller->processResults();
             } else if ($action === 'results') {
                  require_once ROOT_PATH . '/views/quiz_results.php'; // Or controller method
             }
             else {
                 require_once ROOT_PATH . '/views/quiz.php'; // Or controller method
             }
             break;
        // ... other cases for login, register, checkout, account, newsletter etc. based on controllers found
        case 'newsletter':
             require_once ROOT_PATH . '/controllers/NewsletterController.php';
             $controller = new NewsletterController($pdo);
             if ($action === 'subscribe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                 $controller->subscribe(); // Handles AJAX
             }
             break;
        case 'login':
              require_once ROOT_PATH . '/controllers/AccountController.php'; // Assuming login is in AccountController
              $controller = new AccountController($pdo);
               if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                   $controller->processLogin();
               } else {
                   require_once ROOT_PATH . '/views/login.php';
               }
               break;
         case 'register':
               require_once ROOT_PATH . '/controllers/AccountController.php';
               $controller = new AccountController($pdo);
               if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                   $controller->processRegistration();
               } else {
                   require_once ROOT_PATH . '/views/register.php';
               }
               break;
        // ... etc.

        default:
            http_response_code(404);
            require_once ROOT_PATH . '/views/404.php';
            break;
    }
} catch (Exception $e) {
    ErrorHandler::handle($e); // Log error and show generic error page
}

?>
```

*(Self-Correction: Updated path references, structure reflects root location. Added more detail based on `grep` output and standard practice for such a structure. Included sample validation and CSRF check.)*

### 5.3 Controller Dispatch & Action Flow

  * Controllers (`controllers/*.php`) contain the main application logic for specific sections (Products, Cart, User Accounts, etc.).
  * They are typically included and instantiated within the `index.php` routing logic.
  * Methods within controllers handle specific actions (e.g., `listProducts()`, `showProductDetail($id)`, `addToCart()`, `processLogin()`).
  * Controllers interact with the database, either directly via the PDO object (passed during instantiation) or through Model classes (`models/*.php`)[cite: 19].
  * They perform data validation/sanitization using `SecurityMiddleware` functions[cite: 142].
  * They prepare data (e.g., fetching product lists, user details) to be passed to the view.
  * They conclude by either:
      * Including the appropriate view file (`require_once ROOT_PATH . '/views/...'`) and passing data to it (often by setting variables in the controller's scope before the include).
      * Returning data (e.g., JSON for AJAX requests) using `echo json_encode(...)` and exiting.

*(Self-Correction: Aligned description with the root `index.php` including controllers directly.)*

### 5.4 Views: Templating and Rendering

  * Views (`views/*.php`) are primarily responsible for HTML generation[cite: 79]. They are plain PHP files mixed with HTML.
  * They receive data from controllers (either via passed variables or by calling helper functions/accessing objects set up by the controller).
  * Most views include common layout partials: `require_once ROOT_PATH . '/views/layout/header.php';` at the beginning and `require_once ROOT_PATH . '/views/layout/footer.php';` at the end[cite: 79, 80].
  * They use PHP constructs (`<?php echo ... ?>`, `<?php foreach(...){ ... } ?>`) to display dynamic data[cite: 81]. Output should be escaped using `htmlspecialchars()` to prevent XSS unless HTML is explicitly intended[cite: 143].
  * CSS classes (from custom `css/style.css` and CDN Tailwind) are used for styling.
  * JavaScript interactions (menu toggling, AJAX calls, animations) are handled by JS included via `<script>` tags, often defined in `layout/footer.php` or linked external files/CDNs.

*(Self-Correction: Updated paths for view includes.)*

-----

## 6\. Frontend Architecture

### 6.1 CSS (css/style.css), Tailwind (CDN), and Other Libraries

  * **Styling Approach:** A hybrid approach is used.
      * **Tailwind CSS:** Included via a CDN `<script>` tag in the `<head>`. This provides utility classes for rapid layout and styling directly in the HTML. Basic theme customizations (colors, fonts) are configured within the script tag.
      * **Custom CSS:** A local stylesheet `/css/style.css` is also loaded. This file contains custom CSS rules, potentially including:
          * CSS Variables (`:root`) for global design tokens (colors, fonts, spacing)[cite: 83].
          * Component-specific styles (cards, navigation, forms, etc.) that go beyond utility classes[cite: 85].
          * Overrides for Tailwind styles if needed.
          * Custom animations or complex selectors[cite: 86].
  * **Other Libraries:**
      * **Google Fonts:** Specific fonts (`Cormorant Garamond`, `Montserrat`, `Raleway`) are loaded via `<link>` tags from Google Fonts CDN[cite: 202].
      * **Font Awesome:** Icons are provided by Font Awesome, loaded via CDN CSS link[cite: 202].
      * **AOS.js (Animate On Scroll):** Loaded via CDN `<script>` and `<link>` tags for scroll-triggered animations[cite: 202, 93, 304]. Elements use `data-aos` attributes (e.g., `data-aos="fade-up"`)[cite: 85].
      * **Particles.js:** Loaded via CDN `<script>` tag for animated particle backgrounds[cite: 202, 94, 304], configured using `/particles.json`.

*(Self-Correction: Rewritten section to accurately reflect the observed frontend stack in the HTML output: CDN Tailwind, custom CSS, CDNs for AOS/Particles/Fonts/Icons.)*

### 6.2 Responsive Design and Accessibility

  * **Responsive Design:** Achieved primarily through Tailwind's responsive utility classes (e.g., `md:grid-cols-2`, `lg:grid-cols-4`) [cite: 226] and potentially custom media queries in `css/style.css`[cite: 86]. Layouts adapt for mobile, tablet, and desktop screens[cite: 88]. Mobile navigation is handled via JavaScript toggle.
  * **Accessibility:**
      * Semantic HTML elements (`<header>`, `<footer>`, `<nav>`, `<section>`) are used where appropriate.
      * ARIA attributes (`aria-label`) are used on icon buttons for clarity[cite: 217, 218, 219]. Role attributes may be used (e.g., `role="alert"` for flash messages [cite: 332]).
      * Basic keyboard navigation support is expected for interactive elements (links, buttons, forms)[cite: 90]. Mobile menu has escape key closing[cite: 212].
      * Color contrast and font sizes should meet accessibility guidelines (WCAG)[cite: 87, 92]. (Requires visual audit).

*(Self-Correction: Updated examples based on actual HTML. Tailwind utilities are the primary driver for responsiveness observed.)*

### 6.3 JavaScript: Interactivity, AOS.js, Particles.js

  * **AOS.js:** Initialized globally (likely in `layout/footer.php` script block) to enable animations on elements with `data-aos` attributes as they scroll into view[cite: 93, 304].
  * **Particles.js:** Initialized on specific elements (like `#particles-js` in the hero section [cite: 220]) to load configuration from `/particles.json` and render the animated background[cite: 94, 304].
  * **Custom Interactivity (Likely in scripts within `layout/footer.php` or linked JS file):**
      * **Mobile Menu Toggle:** JavaScript handles opening/closing the mobile navigation menu, toggling classes, changing the icon, and handling click-outside/escape key to close.
      * **AJAX Operations:**
          * *Add to Cart:* Buttons trigger fetch requests to `index.php?page=cart&action=add`[cite: 305, 362]. JS sends product ID and CSRF token[cite: 309, 363], handles JSON response (updating cart count, showing flash messages, potentially disabling button if out of stock).
          * *Newsletter Subscription:* Form submission is intercepted by JS[cite: 321, 357]. Data (email, CSRF token) is sent via fetch to `index.php?page=newsletter&action=subscribe`[cite: 322, 358]. JS handles JSON response, showing success or error messages.
      * **Flash Messages:** A helper function (`showFlashMessage`) likely exists to dynamically create and display temporary success/error/info messages (e.g., after AJAX actions), which auto-dismiss.
      * **Client-Side Validation:** Basic HTML5 validation (`required` attribute on email field [cite: 298, 349]) might be present, potentially augmented by JS validation. Server-side validation remains critical[cite: 98].
      * **Sticky Header:** JavaScript adds/removes a 'sticky' class to the header based on scroll position.

*(Self-Correction: Updated details based on observed JS in the HTML output, including specific AJAX endpoints, CSRF handling in JS, flash message implementation, and sticky header logic.)*

-----

## 7\. Key Pages & Components

*(Self-Correction: Descriptions below are updated slightly based on cross-referencing the original spec with the actual HTML output `current_landing_page.html.txt`)*

### 7.1 Home/Landing Page (views/home.php)

  * **Hero Section:** Fullscreen video background (`/videos/hero.mp4`) [cite: 220] with Particles.js overlay [cite: 220] and gradient[cite: 220]. Large headline, descriptive text, and call-to-action button(s)[cite: 221]. Uses AOS for animation[cite: 221].
  * **About/Mission Section:** Two-column layout with image (`/images/about.jpg`) [cite: 222] and text describing the brand's philosophy[cite: 223, 224]. Includes a "Learn Our Story" button linking to the about page[cite: 224]. Uses AOS for animation[cite: 222, 223].
  * **Featured Collections Section:** Grid display (responsive: 1, 2, then 4 columns) of selected products[cite: 226]. Each product is shown in a card format. Uses AOS for animation[cite: 226]. Includes a "Shop All Products" button below the grid[cite: 286, 287].
  * **Benefits Section:** Three-column layout highlighting key benefits (e.g., Natural Ingredients, Wellness Focus, Expert Crafted) with icons and text. Uses AOS for animation[cite: 288, 289, 290].
  * **Quiz/Finder Section:** Introduces the scent finder concept with mood-based icons/cards (Relaxation, Energy, Focus, Sleep, Balance). Includes a prominent button linking to the full quiz page (`index.php?page=quiz`)[cite: 296]. Uses AOS for animation.
  * **Newsletter Section:** Centered section with headline, descriptive text, and an AJAX-powered subscription form (email input and submit button). Includes CSRF token [cite: 298] and privacy policy link[cite: 299]. Uses AOS for animation[cite: 297].
  * **Testimonials Section:** Grid display (responsive: typically 1 then 3 columns) of customer quotes, names, and star ratings, presented in cards. Uses AOS for animation[cite: 300, 301, 302].

### 7.2 Header and Navigation (views/layout/header.php)

  * **Structure:** Contains the main navigation bar (`<nav class="main-nav">`)[cite: 215]. Uses sticky header functionality via JS.
  * **Logo:** Displays site name ("The Scent") and subtitle ("AROMATHERAPY"), linking to the home page (`index.php`)[cite: 215].
  * **Navigation Links:** Main site sections: Home, Shop, Scent Finder, About, Contact[cite: 215, 216]. Contained within `.nav-links` div which also serves as the mobile menu target (`id="mobile-menu"`)[cite: 215, 207].
  * **Header Icons:** Icons for Search, User Account/Login (`index.php?page=login`), and Shopping Cart (`index.php?page=cart`)[cite: 217, 218]. Cart icon includes a dynamic count badge (`.cart-count`)[cite: 218].
  * **Mobile Menu Toggle:** A button (`<button class="mobile-menu-toggle">`) with a bars/times icon, visible on smaller screens (e.g., `md:hidden`), used to trigger the mobile menu JS[cite: 219, 207].
  * **Flash Messages:** (Assumed, based on JS) A container area (likely positioned fixed/absolute near the top) where server-side or client-side generated flash messages (success/error) are displayed dynamically.

### 7.3 Footer and Newsletter (views/layout/footer.php)

  * **Layout:** Typically uses a grid structure for different sections (About, Shop Links, Help Links, Contact/Newsletter)[cite: 341]. Followed by a bottom bar for copyright and payment methods[cite: 351].
  * **Content:**
      * *About Snippet:* Short brand description and social media icons (Facebook, Instagram, Twitter, Pinterest)[cite: 341, 342].
      * *Quick Links:* Links to main shop categories and help pages (Contact, FAQs, Shipping, Privacy).
      * *Contact Info:* Address, phone number, email address[cite: 348].
      * *Newsletter Form:* Duplicate or primary newsletter signup form, AJAX-enabled.
      * *Copyright:* "© 2025 The Scent. All rights reserved."[cite: 352].
      * *Payment Icons:* Visual indicators for accepted payment methods (Visa, Mastercard, PayPal, Amex)[cite: 352, 353].
  * **JS Initialization:** Contains `<script>` tags to initialize libraries like AOS[cite: 354], Particles.js[cite: 355], and include custom JS logic for footer newsletter form submission and potentially other global functions like Add-to-Cart handlers if not already loaded.

### 7.4 Product Grid & Cards

  * **Grid Layout:** Uses CSS Flexbox or Grid (likely via Tailwind utilities like `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8`) for responsive arrangement of product cards[cite: 226]. Found on home page [cite: 226] and likely `products.php`.
  * **Product Card Structure:**
      * Image: Main product image (`<img>` tag).
      * Info Section: Contains product title (`<h3>`), category or short description (`<p>`).
      * Actions: Buttons for "View Details" (linking to `index.php?page=product&id=...`) and "Add to Cart" (or "Out of Stock"). Add-to-Cart button is likely AJAX-enabled[cite: 305, 362].
  * **Styling:** Cards have background, padding, shadow, rounded corners[cite: 226, 228]. Hover effects might be present (defined in `css/style.css` or via Tailwind). Images use `object-cover`[cite: 227].
  * **Stock Indication:** Logic (likely in the controller preparing data for the view) determines if the "Add to Cart" button should be active or replaced/disabled with an "Out of Stock" indicator[cite: 117, 234, 314].

### 7.5 Quiz Flow & Personalization

  * **Entry Point:** Home page has a "Discover Your Perfect Scent" section with mood cards and a button linking to the main quiz page (`index.php?page=quiz`).
  * **Quiz Interface (`views/quiz.php`):** Presents questions to the user (details not provided, but likely multi-step).
  * **Processing (`QuizController.php`):** Handles quiz form submission (`index.php?page=quiz&action=submit`). Processes user answers[cite: 127].
  * **Result Mapping:** Logic within `QuizController.php` maps quiz answers to recommended products. This mapping might be hardcoded in the controller or potentially stored in the database[cite: 119].
  * **Results Display (`views/quiz_results.php`):** Shows the user their recommended products based on the mapping[cite: 120].
  * **Personalization:** (Potential) Quiz results might be saved to the `quiz_results` table[cite: 139], linked to `user_id` if logged in, or `session_id` otherwise, allowing for personalized experiences later[cite: 120].

-----

## 8\. Backend Logic & Core PHP Components

### 8.1 Includes: Shared Logic (includes/)

  * **Location:** `/includes/` directory in the project root.
  * **Purpose:** Contains reusable PHP files for core functionalities, included directly via `require_once` in `index.php` or other controllers/scripts as needed.
  * **Key Files:**
      * `auth.php`: Session handling (start, destroy, regeneration), user login/logout functions, password verification, registration logic, helper functions like `isUserLoggedIn()`, `getCurrentUserId()`[cite: 121].
      * `db.php`: Contains function(s) to establish and return a PDO database connection object, using credentials from `config.php`. Includes basic error handling for connection failures[cite: 128, 129].
      * `SecurityMiddleware.php`: Class or set of functions for security tasks: CSRF token generation (`generateCsrfToken()`) and validation (`checkCsrfToken()`), input validation (`validateInput()`), setting security headers (`sendSecurityHeaders()`)[cite: 122, 131, 132, 148].
      * `ErrorHandler.php`: Class or set of functions to handle PHP errors and exceptions. Likely includes `register()` method (called early in `index.php`) to set custom handlers, and a `handle()` method to log errors and display a generic error page[cite: 123, 133].
      * `EmailService.php`: Logic for sending emails (e.g., registration confirmation, password reset, order confirmation) potentially using PHPMailer or similar library configured via `config.php`[cite: 123].
      * *(Note: `*.bak` and `test_*.php` files should be removed from production/deployment)*

### 8.2 Controllers: Business Logic Layer

  * **Location:** `/controllers/` directory.
  * **Purpose:** Handle incoming requests delegated from `index.php`, orchestrate actions, interact with models/database, and select/prepare data for views[cite: 78].
  * **Structure:** Typically PHP files containing classes (e.g., `ProductController`, `CartController`) or procedural logic grouped by functionality. A `BaseController.php` might exist for shared controller logic.
  * **Examples:**
      * `ProductController.php`: Methods for `showHomePage()`, `listProducts()`, `showProductDetail($id)`[cite: 124]. Fetches product data from DB (likely via `models/Product.php` or direct PDO calls). Includes relevant views (`views/home.php`, `views/products.php`, etc.).
      * `CartController.php`: Methods for `add()`, `remove()`, `update()`, `showCart()`. Interacts with session (`$_SESSION['cart']`) or `cart_items` table for cart data. Handles AJAX requests for cart modifications[cite: 125]. Includes `views/cart.php`.
      * `CheckoutController.php`: Manages the steps of the checkout process. Handles order creation (inserting into `orders` and `order_items` tables), interacts with payment gateways (e.g., Stripe, PayPal via `PaymentController.php` or direct integration), handles success/failure[cite: 126]. Includes `views/checkout.php`.
      * `AccountController.php`: Methods for `processRegistration()`, `processLogin()`, `showAccountDashboard()`, `processPasswordReset()`. Interacts with `users` table (via `models/User.php` or direct PDO). Handles user authentication and profile management[cite: 127]. Includes views like `views/login.php`, `views/register.php`, `views/account/...`.
      * `QuizController.php`: Methods for `showQuiz()`, `processResults()`, `showRecommendations()`. Handles quiz logic and result mapping[cite: 127]. Interacts with `quiz_results` table. Includes `views/quiz.php`, `views/quiz_results.php`.
      * Other controllers (`InventoryController`, `NewsletterController`, `PaymentController`, `TaxController`, `CouponController`) handle their respective domains.

### 8.3 Database Abstraction (includes/db.php)

  * **Method:** Uses PHP's PDO (PHP Data Objects) extension for database interaction[cite: 128, 191].
  * **Connection:** `includes/db.php` contains the logic to create a PDO instance (`new PDO(...)`) using connection details (DSN, username, password) sourced from `config.php`[cite: 129].
  * **Error Handling:** Configured to throw exceptions on errors (`PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`) allowing try/catch blocks for query handling[cite: 128].
  * **Fetch Mode:** Default fetch mode is likely set to `PDO::FETCH_ASSOC` for returning associative arrays, simplifying access in PHP[cite: 130].
  * **Prepared Statements:** PDO encourages prepared statements, which are crucial for preventing SQL injection vulnerabilities. Controllers/Models should use `$stmt = $pdo->prepare(...)` and `$stmt->execute([...])`.

### 8.4 Security Middleware & Error Handling

  * **SecurityMiddleware.php (`includes/`):**
      * *CSRF Protection:* Generates a token stored in the session (`$_SESSION['csrf_token']`). This token must be included as a hidden field in all state-changing forms) or sent via header/body in AJAX requests. A function `checkCsrfToken()` (called early in `index.php` for POST requests) validates the submitted token against the session token[cite: 131, 146].
      * *Input Validation:* Provides a function `validateInput($input, $type, $options = [])` to sanitize and validate data from `$_GET`, `$_POST`. Checks type (string, int, email, etc.), length, and potentially allowed characters or values[cite: 132, 142]. Used extensively before processing user input[cite: 73].
      * *Security Headers:* Contains a function `sendSecurityHeaders()` (called early in `index.php`) to output headers like `Strict-Transport-Security`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Content-Security-Policy` (if configured), `Referrer-Policy`[cite: 133, 148]. Configured via `config.php`.
  * **ErrorHandler.php (`includes/`):**
      * *Registration:* `ErrorHandler::register()` sets PHP's error and exception handlers (`set_error_handler`, `set_exception_handler`)[cite: 123].
      * *Handling:* The custom handler function (`ErrorHandler::handle()`) catches errors/exceptions. It logs the detailed error (message, file, line, stack trace) to a specified log file[cite: 133, 153].
      * *User Feedback:* It prevents leaking detailed errors to the user. Instead, it displays a generic, user-friendly error page (`views/error.php`) or message[cite: 133, 153].

### 8.5 Session, Auth, and User Flow

  * **Session Configuration:** Session settings (e.g., cookie lifetime, name) are configured in `config.php` or via `ini_set` within `SecurityMiddleware::startSecureSession()`. Crucially, settings should enforce `session.use_only_cookies = 1`, `session.cookie_secure = true` (requires HTTPS), `session.cookie_httponly = true`, `session.cookie_samesite = Lax` or `Strict`[cite: 134, 144].
  * **Session Start:** Secure session is started via `SecurityMiddleware::startSecureSession()`.
  * **Login:** `AccountController::processLogin()` validates credentials against the `users` table (using password hashing like `password_verify()`). On success, `session_regenerate_id(true)` is called, and user ID/role are stored in `$_SESSION`[cite: 134]. Rate limiting should be applied[cite: 149].
  * **Authentication Check:** Helper function like `isUserLoggedIn()` (in `auth.php`) checks for the presence of user data in `$_SESSION`. Used to control access to account pages or display user-specific elements[cite: 179].
  * **Roles:** The `users` table likely has a `role` column (e.g., 'user', 'admin')[cite: 137]. Logic in controllers or `auth.php` can check `$_SESSION['user_role']` to restrict access to admin areas (`views/admin/`)[cite: 135].
  * **Logout:** `AccountController::logout()` (or similar in `auth.php`) destroys the session data (`session_unset()`, `session_destroy()`) and potentially clears the session cookie[cite: 136].
  * **Registration:** `AccountController::processRegistration()` validates user input, checks for existing email, hashes the password (`password_hash()`), and inserts the new user into the `users` table[cite: 136].
  * **Password Hashing:** PHP's `password_hash()` (with `PASSWORD_DEFAULT` or `PASSWORD_ARGON2ID`) and `password_verify()` functions must be used[cite: 136].

*(Self-Correction: Aligned descriptions with likely implementation based on file structure and standard secure practices.)*

-----

## 9\. Database Design

*(Self-Correction: This section seems largely accurate based on standard e-commerce patterns and the models found. Schema examples need validation against actual `/db/schema.sql` if available, but provided examples are plausible.)*

### 9.1 Entity-Relationship Model

**Key Entities:**

  * `users`: Stores customer and admin account information (name, email, hashed password, role)[cite: 137].
  * `products`: Catalog of items for sale (name, description, price, image path, category link, stock)[cite: 138].
  * `categories`: Product categories (e.g., 'Essential Oils', 'Natural Soaps').
  * `orders`: Header information for customer orders (user link, order date, total amount, status, shipping address).
  * `order_items`: Line items for each order, linking `orders` and `products`, storing quantity and price paid.
  * `cart_items`: Temporary storage for items in user shopping carts (linked to user ID or session ID, product ID, quantity).
  * `quiz_results`: Stores user responses or generated recommendations from the scent quiz (linked to user ID or session ID)[cite: 139].
  * `newsletter_subscribers`: Stores email addresses collected from newsletter signups.
  * (Potential others: `addresses`, `payments`, `coupons`, `reviews`, etc.)

**Relationships (Simplified):**

  * `users` (1) --- (\*) `orders`
  * `users` (1) --- (\*) `cart_items` (for logged-in users)
  * `users` (1) --- (\*) `quiz_results` (for logged-in users)
  * `categories` (1) --- (\*) `products`
  * `orders` (1) --- (\*) `order_items`
  * `products` (1) --- (\*) `order_items`
  * `products` (1) --- (\*) `cart_items`

### 9.2 Main Tables & Sample Schemas

```sql
-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Hashed password
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
); [cite: 137]

-- Categories Table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products Table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255), -- Path relative to images/products/
    stock_quantity INT DEFAULT 0,
    display_badge VARCHAR(50) NULL, -- e.g., 'New', 'Best Seller'
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT -- Or SET NULL
); [cite: 138]

-- Orders Table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, -- Allow guest checkouts?
    total_amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending', -- e.g., pending, processing, shipped, delivered, cancelled
    shipping_address TEXT, -- Consider normalizing address to separate table
    billing_address TEXT, -- Consider normalizing
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Order Items Table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price_per_unit DECIMAL(10, 2) NOT NULL, -- Price at time of order
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT -- Prevent deleting product if ordered
);

-- Cart Items Table (Example for DB-backed cart, session often used too)
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, -- If user is logged in
    session_id VARCHAR(128) NULL, -- If user is guest
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_product` (`user_id`,`product_id`),
    UNIQUE KEY `session_product` (`session_id`,`product_id`),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Quiz Results Table
CREATE TABLE quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(128) NULL, -- Link to guest session if not logged in
    answers TEXT, -- Store raw answers (e.g., JSON)
    recommendations TEXT, -- Store recommended product IDs (e.g., JSON or comma-separated)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
); [cite: 139]

-- Newsletter Subscribers Table
CREATE TABLE newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Note: Refer to actual project schema file (e.g., /db/schema.sql) for definitive structure.
```

### 9.3 Data Flow Examples

  * **User adds to cart (Guest):** AJAX request hits `CartController::add()`. Controller gets `session_id()`, product ID, quantity. Inserts/updates row in `cart_items` with `session_id`, product ID, quantity[cite: 140]. Returns new cart count/status via JSON.
  * **User adds to cart (Logged In):** Similar to guest, but uses `$_SESSION['user_id']` instead of `session_id` when interacting with `cart_items`[cite: 140].
  * **User places order:** `CheckoutController::processOrder()` validates cart, calculates total, creates a record in `orders` (linked to `user_id` if logged in), creates records in `order_items` for each cart item, clears the user's `cart_items` (or session cart), potentially triggers payment processing, and sends confirmation email[cite: 141].
  * **User takes quiz:** `QuizController::processResults()` saves raw answers and/or mapped product recommendations to `quiz_results` table, linked to `user_id` or `session_id`[cite: 141].
  * **User subscribes to newsletter:** `NewsletterController::subscribe()` receives email via AJAX, validates it, inserts into `newsletter_subscribers` if not already present and active[cite: 141]. Returns success/failure via JSON.

-----

## 10\. Security Considerations

*(Self-Correction: Descriptions seem aligned with standard practices mentioned elsewhere in the spec and expected for a PHP app.)*

### 10.1 Input Sanitization & Validation

  * **Critical:** ALL external input (`$_GET`, `$_POST`, `$_COOKIE`, `$_SERVER`, uploaded files) MUST be treated as untrusted.
  * **Method:** Use a centralized validation function (like `SecurityMiddleware::validateInput()`) before using any input data[cite: 142]. Validate for expected type (int, string, email, etc.), length, format (e.g., date), and allowed character sets (e.g., alphanumeric for IDs).
  * **Database:** Use PDO prepared statements exclusively for all database queries involving user input to prevent SQL Injection.
  * **Output Encoding:** Encode ALL output being rendered in HTML using `htmlspecialchars($variable, ENT_QUOTES | ENT_HTML5, 'UTF-8')` to prevent Cross-Site Scripting (XSS), unless the variable explicitly contains safe, intended HTML[cite: 143].

### 10.2 Session Management

  * **Secure Configuration:** Enforce secure session cookie attributes: `secure` (requires HTTPS), `httponly` (prevents JS access), `samesite` (Lax or Strict)[cite: 144]. Configure these via `session_set_cookie_params()` or `ini_set` before `session_start()` (within `SecurityMiddleware::startSecureSession()`).
  * **Session ID Regeneration:** Regenerate the session ID using `session_regenerate_id(true)` upon any privilege level change, especially login and logout, to prevent session fixation[cite: 144]. Consider periodic regeneration even during an active session.
  * **Session Timeout:** Implement both inactivity timeout (server-side session garbage collection) and absolute timeout (track session start time in `$_SESSION` and force logout after N hours)[cite: 144].
  * **Logout:** Ensure logout properly destroys the session (`session_destroy()`) and unsets session variables (`$_SESSION = []`)[cite: 136].

### 10.3 CSRF Protection

  * **Mechanism:** Use the synchronizer token pattern.
    1.  Generate a strong random token and store it in the user's session (`$_SESSION['csrf_token']`)[cite: 145]. Generate once per session or per request if higher security needed.
    2.  Include this token as a hidden input field (`<input type="hidden" name="csrf_token" value="...">`) in all state-changing forms (POST, PUT, DELETE)[cite: 146].
    3.  For AJAX requests performing state changes, send the token in a custom HTTP header (e.g., `X-CSRF-Token`) or as part of the request body/form data.
    4.  On the server-side (`index.php` or controller), before processing any state-changing request, verify that the submitted token matches the token stored in the session using a timing-attack-safe comparison (`hash_equals()`)[cite: 146]. If tokens don't match or are missing, reject the request.

### 10.4 Security Headers & Rate Limiting

  * **HTTP Security Headers:** Set via `SecurityMiddleware::sendSecurityHeaders()`[cite: 148]:
      * `Strict-Transport-Security (HSTS)`: Enforces HTTPS usage. Requires site-wide HTTPS.
      * `X-Frame-Options: DENY` or `SAMEORIGIN`: Prevents clickjacking.
      * `X-Content-Type-Options: nosniff`: Prevents MIME-sniffing attacks.
      * `Content-Security-Policy (CSP)`: Defines allowed sources for scripts, styles, images, etc. Mitigates XSS. Requires careful configuration.
      * `Referrer-Policy`: Controls how much referrer information is sent. `strict-origin-when-cross-origin` or `same-origin` are good defaults.
  * **Rate Limiting:** Implement rate limiting on sensitive endpoints like login, registration, password reset requests, and potentially API calls or cart operations to prevent brute-force attacks and denial-of-service[cite: 149]. This typically involves tracking request counts per IP address (or user ID) within a time window (e.g., using a database table or a cache like Redis/Memcached). Configuration options in `config.php`.

### 10.5 File Uploads & Permissions

  * **File Uploads (If Implemented):**
      * Validate file type rigorously on the server-side (using MIME type, not just extension). Define an allowlist of safe types.
      * Validate file size against reasonable limits.
      * Scan uploaded files for malware using server-side tools (e.g., ClamAV).
      * Store uploaded files *outside* the web root if possible, or in a directory with script execution disabled (via `.htaccess` or server config).
      * Generate safe, unique filenames for stored files; do not use user-provided filenames directly.
      * Serve files via a controlled script that checks permissions, not direct links if access control is needed.
  * **File Permissions:**
      * Set restrictive file system permissions. Web server process (e.g., `www-data`) should only have write access where absolutely necessary (e.g., cache directories, upload directories).
      * Configuration files (`config.php`, `.env`) should NOT be readable by the web server user if possible, or have very restricted permissions (e.g., 600 or 640, owned by deployment user, not `www-data`)[cite: 151].
      * PHP files should generally not be writable by the web server user. Set permissions like 644 for files and 755 for directories.

### 10.6 Audit Logging & Error Handling

  * **Audit Logging:** Log security-sensitive events to a separate audit log file (not the general error log)[cite: 152]. Events include:
      * Successful/failed logins
      * Registrations
      * Password changes/resets
      * Role changes
      * Failed CSRF validations
      * Significant actions (e.g., order placement, payment attempts).
      * Log relevant information: timestamp, event type, user ID (if applicable), IP address, outcome.
  * **Error Handling:** As described in Section 8.4, use `ErrorHandler.php` to catch all errors/exceptions, log detailed technical information (stack trace, context) to a file, and show a generic error page to the user[cite: 153]. Ensure log files are rotated or managed to prevent filling disk space. Ensure error details are NEVER leaked in the HTTP response in a production environment (`display_errors = Off`).

-----

## 11\. Extensibility & Onboarding

*(Self-Correction: Update paths and configuration notes based on the root structure.)*

### 11.1 Adding Features, Pages, or Controllers

  * **New Page/Feature (Typical Flow):**
    1.  **Controller:** Create a new PHP file in `/controllers` (e.g., `controllers/NewFeatureController.php`). Define a class or functions to handle the feature's logic.
    2.  **View(s):** Create corresponding PHP template file(s) in `/views` (e.g., `views/new_feature.php`, `views/new_feature_form.php`).
    3.  **Routing:** Add a new `case` to the `switch` statement in `/index.php` to handle the new page request (e.g., `case 'new_feature':`). This case should include the controller file and call its method(s) or directly include the view file[cite: 154].
    4.  **Navigation:** Add links to the new page in relevant places (e.g., `views/layout/header.php`).
    5.  **Model (Optional):** If database interaction is needed beyond simple queries, create a corresponding model in `/models` (e.g., `models/NewFeature.php`)[cite: 156].
    6.  **Database (If needed):** Add new tables or columns via migration scripts or updates to `/db/schema.sql`.

### 11.2 Adding Products, Categories, and Quiz Questions

  * **Products & Categories:** Currently requires direct database manipulation (e.g., via phpMyAdmin, command-line SQL client) or a dedicated (but potentially basic or non-existent) admin interface (`views/admin/`, handled by specific admin controllers). Data needs to be inserted into the `products` and `categories` tables following their schema[cite: 157]. An enhanced admin panel is a future recommendation[cite: 168].
  * **Quiz Questions & Mapping:** Requires modifying the logic within `QuizController.php` where answers are processed and mapped to recommendations[cite: 158]. Alternatively, this mapping could be moved to a configuration file or database tables for easier updates without code changes.

### 11.3 Developer Onboarding Checklist

1.  **Prerequisites:** Install Apache, PHP (check version compatibility, likely 8+), MySQL (or MariaDB), Git. Composer might be needed if any PHP libraries are managed via it (though current structure suggests manual includes).
2.  **Clone Repository:** `git clone <repository_url>`
3.  **Configuration:**
      * Copy `.env.example` to `.env` (if used) and fill in secrets.
      * Update `config.php` with correct database credentials (host, db name, user, password), email settings, and any other environment-specific values[cite: 161]. Ensure `config.php` has restrictive read permissions (not world-readable).
4.  **Database Setup:**
      * Create the MySQL database specified in `config.php`.
      * Import the database schema, likely from a file like `/db/schema.sql` or `/db/migrations/`[cite: 161].
      * Seed the database with essential data (e.g., admin user, categories) and potentially sample products/quiz data[cite: 163].
5.  **Web Server Configuration:**
      * Configure an Apache Virtual Host for the project.
      * Set the `DocumentRoot` to the project's *root directory* (e.g., `/path/to/The-Scent-oa5`)[cite: 162].
      * Ensure `AllowOverride All` (or appropriate directive) is set for the project directory to allow `.htaccess` rules to function[cite: 162].
      * Enable necessary Apache modules (e.g., `mod_rewrite`).
      * Restart Apache.
6.  **File Permissions:** Ensure the web server user (`www-data`) has write permissions only on necessary directories (e.g., log directories, potentially cache or upload directories if they exist). Other files should generally not be writable by `www-data`[cite: 151, 162]. `config.php` should be protected.
7.  **Testing:** Access the site via the configured domain/IP. Test core functionality:
      * Home page loads correctly (CSS, JS, images, video)[cite: 163].
      * Navigation works.
      * Product listing and detail pages load.
      * Add-to-cart functionality (AJAX works, cart count updates).
      * Newsletter subscription (AJAX works).
      * Quiz flow (if data seeded).
      * User registration and login.
      * Check Apache and PHP error logs for issues.

### 11.4 Testing & Debugging

  * **Error Logs:** Check PHP error logs (path defined by PHP config or `ErrorHandler.php`) and Apache error logs for backend issues[cite: 164].
  * **Browser Developer Tools:** Use the browser's inspector (Network tab for AJAX requests, Console tab for JS errors, Elements tab for HTML/CSS)[cite: 165].
  * **Manual Testing:** Systematically test all user flows (guest checkout, logged-in user actions, admin functions) for both expected behavior and edge cases/error conditions[cite: 165].
  * **Security Testing:** Periodically check for common vulnerabilities:
      * XSS: Test input fields and URL parameters with script tags.
      * SQL Injection: Test inputs with SQL characters (quotes, semicolons). (Should be prevented by PDO prepared statements).
      * CSRF: Try submitting forms without valid tokens or via external tools.
      * Session Hijacking: Check cookie security flags.
      * Directory Traversal: Test file parameters.
      * Information Leakage: Ensure `display_errors` is off and generic error pages are shown.
  * **Debugging Code:** Use `var_dump()`, `print_r()` combined with `<pre>` tags or `error_log()` for simple debugging. Consider setting up Xdebug for step-debugging in a development environment.

-----

## 12\. Future Enhancements & Recommendations

*(Self-Correction: This section lists potential improvements; remains relevant regardless of the structural change.)*

  * **Autoloader:** Implement PSR-4 autoloading (using Composer) instead of manual `require_once` calls in `index.php` for better organization and maintainability.
  * **Templating Engine:** Introduce a templating engine like Twig or Blade (if moving towards a framework like Laravel/Symfony) for cleaner separation of logic and presentation in views.
  * **Framework Adoption:** Consider migrating to a lightweight PHP framework (like Slim, Laminas Mezzio) or a full-stack framework (like Laravel, Symfony) for robust routing, ORM, middleware, and community support.
  * **API Layer:** Develop RESTful API endpoints for frontend interactions (allowing a move towards a SPA like React/Vue/Angular) or for mobile app integration[cite: 167].
  * **Admin Dashboard:** Build a comprehensive web interface for managing products, categories, orders, users, discounts, and site settings, replacing direct DB manipulation[cite: 168].
  * **Testing:** Implement unit tests (PHPUnit) for backend logic (controllers, models, services) and integration tests. Add end-to-end tests (Cypress, Playwright) for user flows[cite: 168].
  * **Dependency Management:** Use Composer to manage PHP dependencies (e.g., email libraries, SDKs). Use npm/yarn for frontend dependencies (Tailwind, JS libraries) and build tools (Vite, Webpack) instead of CDNs for better performance and control.
  * **Dockerization:** Create `Dockerfile` and `docker-compose.yml` for consistent development, testing, and deployment environments[cite: 169].
  * **CI/CD:** Set up Continuous Integration/Continuous Deployment pipelines (GitHub Actions, GitLab CI) to automate testing and deployments[cite: 170].
  * **Performance Optimization:** Implement caching strategies (OpCache, APCu, Redis/Memcached for data/sessions), use a CDN for static assets, optimize images, minify CSS/JS[cite: 170].
  * **Internationalization (i18n) / Localization (l10n):** Add support for multiple languages and currencies.
  * **Accessibility Audit:** Conduct a formal accessibility audit (WCAG compliance) and implement necessary improvements[cite: 171].
  * **Enhanced User Profiles:** Add features like order history, saved addresses, wishlists, managing newsletter preferences[cite: 172].
  * **Advanced Analytics:** Integrate with dedicated e-commerce analytics platforms[cite: 172].

-----

## 13\. Appendices

### A. Key File Summaries

*(Self-Correction: Updated paths based on root structure)*

| File/Folder                 | Purpose                                                              |
| :-------------------------- | :------------------------------------------------------------------- |
| `index.php`                 | Application entry, routing, core includes, controller dispatch       |
| `config.php`                | Environment, DB, email, security configuration                      |
| `css/style.css`             | Custom CSS rules complementing CDN Tailwind                           |
| `particles.json`          | Particle animation config for Particles.js                           |
| `.htaccess`                 | (Root directory) URL rewriting for clean routing                     |
| `includes/auth.php`         | User login, logout, session helpers, registration logic             |
| `includes/db.php`           | PDO DB connection establishment, error handling                     |
| `includes/SecurityMiddleware.php` | CSRF protection, input validation, security headers                 |
| `includes/ErrorHandler.php` | Centralized error/exception handling and logging                     |
| `controllers/*Controller.php` | Business logic handlers for different application sections           |
| `models/*.php`              | Data representation and database interaction logic                   |
| `views/*.php`               | HTML/PHP templates for rendering pages                              |
| `views/layout/header.php`   | Sitewide navigation/header HTML partial                             |
| `views/layout/footer.php`   | Sitewide footer/newsletter/social HTML partial & JS initialization |

### B. Glossary

*(Self-Correction: Glossary terms remain relevant.)*

  * **MVC:** Model-View-Controller, a software design pattern separating application concerns. This project is "MVC-inspired"[cite: 5].
  * **CSRF:** Cross-Site Request Forgery, a web security vulnerability preventable using synchronizer tokens[cite: 189].
  * **XSS:** Cross-Site Scripting, a vulnerability preventable by proper output encoding (e.g., `htmlspecialchars`)[cite: 143].
  * **SQL Injection:** A vulnerability preventable by using prepared statements (e.g., via PDO).
  * **AOS.js:** Animate On Scroll – JS library for scroll-triggered animations[cite: 190].
  * **Particles.js:** JS library for creating animated particle backgrounds[cite: 191].
  * **Tailwind CSS:** A utility-first CSS framework, used here via CDN[cite: 203].
  * **PDO:** PHP Data Objects, PHP's modern database access layer providing a consistent interface[cite: 191].
  * **Session:** PHP mechanism for persisting user data across multiple requests[cite: 192].
  * **Flash message:** A one-time message stored in the session, typically used to provide feedback after an action (e.g., "Item added to cart")[cite: 192].
  * **CDN:** Content Delivery Network, used to host and serve assets (like JS/CSS libraries) quickly from geographically distributed servers[cite: 202, 203].
  * **AJAX:** Asynchronous JavaScript and XML (though JSON is now commonly used), technique for updating parts of a web page without a full reload[cite: 28].
  * **`.htaccess`:** Apache configuration file used for directory-level settings, commonly for URL rewriting[cite: 35].

### C. Code Snippets and Patterns

#### Example: Secure Controller Routing in `/index.php`

```php
// (Inside index.php, after includes and setup)
$page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string_alphanum');
try {
    switch ($page) {
        case 'home':
            require_once ROOT_PATH . '/controllers/ProductController.php';
            (new ProductController($pdo))->showHomePage(); // Assumes constructor takes PDO
            break;
        case 'product':
             require_once ROOT_PATH . '/controllers/ProductController.php';
             $id = SecurityMiddleware::validateInput($_GET['id'] ?? null, 'int');
             (new ProductController($pdo))->showProductDetail($id);
             break;
        // ...other cases...
        default:
            http_response_code(404);
            require_once ROOT_PATH . '/views/404.php';
            break; // Added break statement
    }
} catch (Exception $e) {
    ErrorHandler::handle($e); // Log and show generic error page
}
```

#### Example: CSRF-Protected Form in a View (`views/register.php`)

```php
<?php
// Ensure CSRF token is generated if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<form method="POST" action="index.php?page=register&action=submit">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

    <label for="name">Name:</label>
    <input type="text" id="name" name="name" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Register</button>
</form>
```

#### Example: AJAX Add-to-Cart with CSRF (in footer JS or linked file)

```javascript
document.querySelectorAll('.add-to-cart-btn').forEach(button => {
    button.addEventListener('click', async function(event) {
        event.preventDefault(); // Prevent default if it's a link/button in a form

        const productId = this.dataset.productId;
        const csrfTokenInput = document.querySelector('input[name="csrf_token"]'); // Get token from a hidden field on the page
        const csrfToken = csrfTokenInput ? csrfTokenInput.value : null;

        if (!productId) {
            console.error('Product ID missing from button dataset.');
            return;
        }
        if (!csrfToken) {
             console.error('CSRF token hidden field not found on the page.');
             // Optionally show flash message to user
             // showFlashMessage('Cannot add item: Security token missing. Please refresh.', 'error');
             return;
        }

        // Optional: Show loading state on button
        this.disabled = true;
        this.textContent = 'Adding...';

        const formData = new URLSearchParams();
        formData.append('product_id', productId);
        formData.append('quantity', '1');
        formData.append('csrf_token', csrfToken); // Send token in body

        try {
            const response = await fetch('index.php?page=cart&action=add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json' // Indicate we expect JSON back
                },
                body: formData
            });

            // Reset button state
            this.disabled = false;
            // Note: Need logic here to check if product is now out of stock
            this.textContent = 'Add to Cart';


            if (!response.ok) {
                 // Handle HTTP errors (e.g., 404, 500)
                 console.error('HTTP Error:', response.status, response.statusText);
                 // Try to get error message from response if JSON
                 let errorMsg = 'Error adding item to cart.';
                 try {
                     const errorData = await response.json();
                     errorMsg = errorData.message || errorMsg;
                 } catch (e) { /* Ignore if response is not JSON */ }
                 showFlashMessage(errorMsg, 'error'); // Use your flash message function
                 return;
            }

            const data = await response.json(); // Expecting JSON response

            if (data.success) {
                // Update cart count in header
                const cartCountSpan = document.querySelector('.cart-count');
                if (cartCountSpan) {
                    cartCountSpan.textContent = data.cart_count || '0';
                }
                showFlashMessage(data.message || 'Item added to cart!', 'success');

                // Update button state if product is now out of stock
                if (data.stock_status === 'out_of_stock') {
                     this.disabled = true;
                     this.textContent = 'Out of Stock';
                     // Optionally add a specific class
                     this.classList.add('btn-disabled');
                     this.classList.remove('add-to-cart-btn'); // Prevent re-adding listener if needed
                } else if (data.stock_status === 'low_stock') {
                     showFlashMessage('Limited quantity remaining.', 'info');
                     // Optionally add visual indicator
                }

            } else {
                // Handle application-level errors reported in JSON
                showFlashMessage(data.message || 'Could not add item.', 'error');
            }

        } catch (error) {
             // Handle network errors or JS errors during fetch
            console.error('Fetch Error:', error);
            showFlashMessage('Network error adding item to cart.', 'error');
            // Reset button state
            this.disabled = false;
            this.textContent = 'Add to Cart';
        }
    });
});
```

*(Self-Correction: Updated AJAX snippet to more closely match the implementation pattern observed in the HTML, sending CSRF token via form data and handling JSON response with success/error messages and stock status.)*

-----

**End of Technical Design Specification (Updated)**

This document is intended as a living resource reflecting the current state of the project (as of April 18, 2025). For questions, improvements, or onboarding, start here and follow file references into the codebase for specifics. The Scent codebase is designed for clarity, extensibility, and security, making it a robust foundation for e-commerce and further innovation.

```
```
