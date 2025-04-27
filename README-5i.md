# 🌿 **The Scent – Premium Aromatherapy E-commerce Platform (v1.0)**

> **Version:** 1.0.0 | **Last Updated:** 2025-04-27 | **License:** MIT

> Welcome to **The Scent**, a modern, full-featured, and beautifully crafted e-commerce platform designed to showcase and sell premium natural aromatherapy products. This project serves not only as a functional storefront but also as a reference implementation demonstrating best practices in modern web application development using PHP and the Laravel framework. It is architected from the ground up for extensibility, maintainability, security, and a seamless, mindful user experience.

🧘 **“Find your moment of calm” – Discover your perfect scent and enhance your well-being.**

---

<div align="center">

![Version](https://img.shields.io/badge/version-1.0.0-blue?style=flat-square)
![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)
![Laravel](https://img.shields.io/badge/Laravel-12.x-red?style=flat-square)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-3.x-38BDF8?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-8.3-blueviolet?style=flat-square)
![MariaDB](https://img.shields.io/badge/MariaDB-11.7-00648F?style=flat-square)
![Build Status](https://img.shields.io/github/actions/workflow/status/your-org/the-scent/ci.yml?label=CI&style=flat-square&logo=githubactions)

</div>

---

> **IMPORTANT NOTE:** This README is intentionally **very long and detailed** (targeting ~6000 words) to function as a comprehensive, self-contained **Developer Handbook** for the project. It covers architecture, setup, development workflows, testing, deployment, and core concepts. Please use the Table of Contents, your editor's search function, or collapse sections in GitHub’s UI to navigate efficiently.

---

## 📚 Table of Contents

1.  [**Project Vision & Philosophy**](#-project-vision--philosophy)
    * [Core Goals](#core-goals)
    * [Guiding Principles](#guiding-principles)
2.  [**Key Features Deep Dive**](#-key-features-deep-dive)
    * [UI/UX Highlights](#uiux-highlights)
    * [E-commerce Engine](#e-commerce-engine)
    * [Authentication & Authorization](#authentication--authorization)
    * [Admin Dashboard](#admin-dashboard)
    * [API Capabilities](#api-capabilities)
3.  [**Technology Stack Rationale**](#-technology-stack-rationale)
    * [Why Laravel 12?](#why-laravel-12)
    * [Why Tailwind CSS 3?](#why-tailwind-css-3)
    * [Why Alpine.js?](#why-alpinejs)
    * [Why PHP 8.3?](#why-php-83)
    * [Why MariaDB 11.7?](#why-mariadb-117)
    * [Why Docker & Laravel Sail?](#why-docker--laravel-sail)
4.  [**Screenshots (Illustrative)**](#-screenshots-illustrative)
5.  [**Architectural Overview: A Layered Approach**](#-architectural-overview-a-layered-approach)
    * [High-Level Diagram](#high-level-diagram)
    * [Architectural Layers Explained](#architectural-layers-explained)
        * [Presentation Layer (Client/Browser)](#presentation-layer-clientbrowser)
        * [Web Application Layer (Laravel)](#web-application-layer-laravel)
        * [Domain Layer (Core Business Logic)](#domain-layer-core-business-logic)
        * [Infrastructure Layer (Persistence, External Services)](#infrastructure-layer-persistence-external-services)
        * [Async Processing Layer (Queue Workers)](#async-processing-layer-queue-workers)
    * [Design Philosophy: DDD Influences](#design-philosophy-ddd-influences)
6.  [**Core Concepts & Design Patterns Employed**](#-core-concepts--design-patterns-employed)
    * [Model-View-Controller (MVC)](#model-view-controller-mvc)
    * [Service Layer Pattern](#service-layer-pattern)
    * [Repository Pattern (Implicit via Eloquent)](#repository-pattern-implicit-via-eloquent)
    * [Data Transfer Objects (DTOs)](#data-transfer-objects-dtos)
    * [Dependency Injection & Service Container](#dependency-injection--service-container)
    * [Events & Listeners (Decoupling)](#events--listeners-decoupling)
    * [Middleware (HTTP Request Handling)](#middleware-http-request-handling)
7.  [**Detailed Component Logic Flow: Checkout Example**](#-detailed-component-logic-flow-checkout-example)
    * [Flowchart Diagram](#flowchart-diagram)
    * [Sequence Narrative & Technical Details](#sequence-narrative--technical-details)
8.  [**Directory & File Layout Explained**](#-directory--file-layout-explained)
    * [Standard Laravel Structure](#standard-laravel-structure)
    * [Key Customizations (`app/Domain`)](#key-customizations-appdomain)
    * [Directory Breakdown](#directory-breakdown)
9.  [**Database Schema & Entity Relationships**](#-database-schema--entity-relationships)
    * [Entity-Relationship Diagram (ERD)](#entity-relationship-diagram-erd)
    * [Key Table Deep Dive](#key-table-deep-dive)
    * [Data Modeling Choices & Rationale](#data-modeling-choices--rationale)
    * [Migrations & Seeding Strategy](#migrations--seeding-strategy)
10. [**API & Service Contracts: Usage Guide**](#-api--service-contracts-usage-guide)
    * [API Philosophy (RESTful, Versioned)](#api-philosophy-restful-versioned)
    * [Authentication Mechanisms](#authentication-mechanisms)
        * [API Tokens (Laravel Sanctum)](#api-tokens-laravel-sanctum)
        * [Webhook Signatures (Example: Stripe)](#webhook-signatures-example-stripe)
    * [Key REST Endpoint Examples](#key-rest-endpoint-examples)
        * [Fetching Products](#fetching-products)
        * [User Authentication](#user-authentication)
        * [Cart Management](#cart-management)
        * [Checkout Process](#checkout-process)
    * [GraphQL Endpoint](#graphql-endpoint)
    * [API Versioning](#api-versioning)
    * [Rate Limiting](#rate-limiting)
    * [API Documentation (OpenAPI)](#api-documentation-openapi)
11. [**Environment Configuration In-Depth**](#-environment-configuration-in-depth)
    * [The `.env` File](#the-env-file)
    * [Core Application Settings](#core-application-settings)
    * [Database Connection](#database-connection)
    * [Session, Cache, Queue Drivers](#session-cache-queue-drivers)
    * [External Service Keys (Mail, Payments)](#external-service-keys-mail-payments)
    * [Feature Flags](#feature-flags)
    * [Configuration Caching (`config:cache`)](#configuration-caching-configcache)
    * [Environment Validation](#environment-validation)
12. [**Comprehensive Local Development Setup Guide**](#-comprehensive-local-development-setup-guide)
    * [Prerequisites](#prerequisites)
    * [Cloning the Repository](#cloning-the-repository)
    * [Setting Up Laravel Sail (Docker)](#setting-up-laravel-sail-docker)
    * [Initial Bootstrap Commands](#initial-bootstrap-commands)
    * [Running the Application](#running-the-application)
    * [Working with Sail: Common Commands](#working-with-sail-common-commands)
    * [Frontend Asset Pipeline (Vite)](#frontend-asset-pipeline-vite)
    * [Database Interaction (Sail & GUI Tools)](#database-interaction-sail--gui-tools)
    * [Debugging with Xdebug & Sail](#debugging-with-xdebug--sail)
    * [Running Tests Locally](#running-tests-locally)
    * [Common Troubleshooting Tips](#common-troubleshooting-tips)
13. [**Robust Testing Strategy & Implementation**](#-robust-testing-strategy--implementation)
    * [Testing Philosophy](#testing-philosophy)
    * [Unit Tests (Pest/PHPUnit)](#unit-tests-pestphpunit)
    * [Feature Tests (Laravel HTTP Tests)](#feature-tests-laravel-http-tests)
    * [End-to-End (E2E) Tests (Cypress)](#end-to-end-e2e-tests-cypress)
    * [API Contract Tests (Dredd - Optional)](#api-contract-tests-dredd---optional)
    * [Mutation Testing (Infection)](#mutation-testing-infection)
    * [Static Analysis (PHPStan, Psalm)](#static-analysis-phpstan-psalm)
    * [Code Style (Pint/PHP-CS-Fixer)](#code-style-pintphp-cs-fixer)
    * [Running Tests in CI](#running-tests-in-ci)
14. [**Detailed Deployment Guide: Fly.io & AWS Lightsail**](#-detailed-deployment-guide-flyio--aws-lightsail)
    * [General Deployment Principles](#general-deployment-principles)
        * [Environment Parity](#environment-parity)
        * [Build Artifacts](#build-artifacts)
        * [Configuration & Secrets Management](#configuration--secrets-management)
        * [Zero-Downtime Deployments Goal](#zero-downtime-deployments-goal)
    * **A) Deploying to Fly.io (Recommended for Simplicity)**
        * [Prerequisites: Fly CLI & Account](#prerequisites-fly-cli--account)
        * [Initial Launch (`fly launch`)](#initial-launch-fly-launch)
        * [Database Provisioning](#database-provisioning)
        * [Setting Secrets (`fly secrets set`)](#setting-secrets-fly-secrets-set)
        * [The `fly.toml` Configuration File](#the-flytoml-configuration-file)
        * [Deploying (`fly deploy`)](#deploying-fly-deploy)
        * [Running Migrations & Release Commands](#running-migrations--release-commands)
        * [Scaling Services](#scaling-services)
        * [Custom Domains & TLS](#custom-domains--tls)
        * [Logging & Monitoring](#logging--monitoring)
        * [Accessing the Production Container (`fly ssh console`)](#accessing-the-production-container-fly-ssh-console)
    * **B) Deploying to AWS Lightsail (LAMP Blueprint - More Manual)**
        * [Prerequisites: AWS Account & SSH Key](#prerequisites-aws-account--ssh-key)
        * [Creating the Lightsail Instance (LAMP Stack)](#creating-the-lightsail-instance-lamp-stack)
        * [Configuring the Firewall (Security Group)](#configuring-the-firewall-security-group)
        * [Connecting via SSH](#connecting-via-ssh)
        * [Installing Dependencies (Git, Composer, Node.js if needed)](#installing-dependencies-git-composer-nodejs-if-needed)
        * [Cloning the Repository](#cloning-the-repository-1)
        * [Installing Application Dependencies (`composer`, `npm`)](#installing-application-dependencies-composer-npm)
        * [Configuring Apache Virtual Host](#configuring-apache-virtual-host)
        * [Setting Up the `.env` File](#setting-up-the-env-file)
        * [Laravel Production Setup Commands](#laravel-production-setup-commands)
        * [Setting File Permissions](#setting-file-permissions)
        * [Setting Up Cron for Scheduled Tasks](#setting-up-cron-for-scheduled-tasks)
        * [Configuring HTTPS (Let's Encrypt via `bncert-tool`)](#configuring-https-lets-encrypt-via-bncert-tool)
        * [Setting Up Queue Workers (Supervisor)](#setting-up-queue-workers-supervisor)
        * [Monitoring (CloudWatch Agent)](#monitoring-cloudwatch-agent)
    * **Zero-Downtime Deployment Strategies**
        * [Laravel Maintenance Mode](#laravel-maintenance-mode)
        * [Atomic Deploys (Symlink Switching - Lightsail)](#atomic-deploys-symlink-switching---lightsail)
        * [Rolling Deploys (Fly.io Default)](#rolling-deploys-flyio-default)
    * **Post-Deployment Checklist & Troubleshooting**
15. [**CI/CD Pipeline Explained (GitHub Actions)**](#-cicd-pipeline-explained-github-actions)
    * [Workflow Triggers (`on:`)](#workflow-triggers-on)
    * [Jobs, Runners, and Services](#jobs-runners-and-services)
    * [Checkout & Setup Steps](#checkout--setup-steps)
    * [Dependency Caching (`actions/cache`)](#dependency-caching-actionscache)
    * [Running Linters & Static Analysis](#running-linters--static-analysis)
    * [Executing Tests (Unit, Feature)](#executing-tests-unit-feature)
    * [Building Frontend Assets](#building-frontend-assets)
    * [Building Docker Image (Optional, for Fly.io)](#building-docker-image-optional-for-flyio)
    * [Uploading/Downloading Artifacts](#uploadingdownloading-artifacts)
    * [Conditional Deployment Job](#conditional-deployment-job)
    * [Using Secrets in CI/CD](#using-secrets-in-cicd)
16. [**Performance Optimization & Scalability Considerations**](#-performance-optimization--scalability-considerations)
    * [PHP OpCache & JIT Tuning](#php-opcache--jit-tuning)
    * [Leveraging Laravel Octane (RoadRunner/Swoole)](#leveraging-laravel-octane-roadrunnerswoole)
    * [HTTP Caching Strategies (ETag, Cache-Control)](#http-caching-strategies-etag-cache-control)
    * [Content Delivery Network (CDN) Integration](#content-delivery-network-cdn-integration)
    * [Database Optimization (Indexing, Query Analysis)](#database-optimization-indexing-query-analysis)
    * [Read/Write Splitting & Database Replication](#readwrite-splitting--database-replication)
    * [Background Job Processing & Queues](#background-job-processing--queues)
    * [Horizontal Scaling (Stateless Application)](#horizontal-scaling-stateless-application)
    * [Load Balancing](#load-balancing)
    * [Application & Server Monitoring (Observability)](#application--server-monitoring-observability)
17. [**Security Best Practices Implemented**](#-security-best-practices-implemented)
    * [Mitigating OWASP Top 10 Risks](#mitigating-owasp-top-10-risks)
        * [A01: Broken Access Control](#a01-broken-access-control)
        * [A02: Cryptographic Failures](#a02-cryptographic-failures)
        * [A03: Injection](#a03-injection)
        * [A04: Insecure Design](#a04-insecure-design)
        * [A05: Security Misconfiguration](#a05-security-misconfiguration)
        * [A06: Vulnerable and Outdated Components](#a06-vulnerable-and-outdated-components)
        * [A07: Identification and Authentication Failures](#a07-identification-and-authentication-failures)
        * [A08: Software and Data Integrity Failures](#a08-software-and-data-integrity-failures)
        * [A09: Security Logging and Monitoring Failures](#a09-security-logging-and-monitoring-failures)
        * [A10: Server-Side Request Forgery (SSRF)](#a10-server-side-request-forgery-ssrf)
    * [Cross-Site Scripting (XSS) Prevention](#cross-site-scripting-xss-prevention)
    * [Cross-Site Request Forgery (CSRF) Protection](#cross-site-request-forgery-csrf-protection)
    * [Rate Limiting Implementation](#rate-limiting-implementation)
    * [Security Headers (CSP, HSTS, etc.)](#security-headers-csp-hsts-etc)
    * [Dependency Scanning (Composer Audit, Dependabot)](#dependency-scanning-composer-audit-dependabot)
    * [Secrets Management](#secrets-management)
18. [**Accessibility (a11y), Internationalization (i18n) & SEO**](#-accessibility-a11y-internationalization-i18n--seo)
    * [Web Content Accessibility Guidelines (WCAG) Compliance](#web-content-accessibility-guidelines-wcag-compliance)
    * [Semantic HTML & ARIA Roles](#semantic-html--aria-roles)
    * [Keyboard Navigation & Focus Management](#keyboard-navigation--focus-management)
    * [Internationalization Strategy (Laravel Localization)](#internationalization-strategy-laravel-localization)
    * [Search Engine Optimization (SEO) Techniques](#search-engine-optimization-seo-techniques)
    * [Performance Metrics (Core Web Vitals)](#performance-metrics-core-web-vitals)
    * [Testing Tools (axe-core, Lighthouse)](#testing-tools-axe-core-lighthouse)
19. [**Project Roadmap & Future Enhancements**](#-project-roadmap--future-enhancements)
    * [v1.1 - Post-Launch Iterations](#v11---post-launch-iterations)
    * [v1.2 - Feature Expansion](#v12---feature-expansion)
    * [v2.0 - Architectural Evolution](#v20---architectural-evolution)
    * [Contributing to the Roadmap](#contributing-to-the-roadmap)
20. [**Contribution Guidelines: How to Participate**](#-contribution-guidelines-how-to-participate)
    * [Getting Started](#getting-started)
    * [Branching Strategy (Gitflow Variant)](#branching-strategy-gitflow-variant)
    * [Commit Message Format (Conventional Commits)](#commit-message-format-conventional-commits)
    * [Code Style & Linting](#code-style--linting)
    * [Adding Tests](#adding-tests)
    * [Submitting Pull Requests (PRs)](#submitting-pull-requests-prs)
    * [Code Review Process](#code-review-process)
    * [Reporting Bugs & Suggesting Features](#reporting-bugs--suggesting-features)
    * [Code of Conduct](#code-of-conduct)
21. [**License Information**](#-license-information)
22. [**Acknowledgements & Credits**](#-acknowledgements--credits)

---

## ✨ Project Vision & Philosophy

### Core Goals

"The Scent" aims to be more than just another e-commerce site. It's envisioned as a digital sanctuary where users can mindfully explore and purchase high-quality aromatherapy products. The primary goals for v1.0 are:

1.  **Flawless User Experience:** A beautiful, intuitive, responsive, and accessible interface that guides users effortlessly from discovery to checkout. Mobile-first design is paramount.
2.  **Robust & Reliable Backend:** A stable, secure, and well-tested platform capable of handling typical e-commerce workloads, built on proven technologies.
3.  **Developer Ergonomics:** A clean, maintainable, and well-documented codebase that is easy for developers to understand, extend, and contribute to. Leverage the power and elegance of the Laravel ecosystem.
4.  **Future-Proof Architecture:** A modular design that facilitates future expansion, such as integrating new features (subscriptions, gift cards), scaling components independently, or potentially adopting a headless architecture.

### Guiding Principles

* **Simplicity & Elegance:** Strive for clarity in code and design. Leverage framework conventions but don't be afraid to deviate where it makes sense.
* **Security by Default:** Integrate security considerations from the outset, not as an afterthought. Follow best practices for web application security.
* **Test-Driven Development (TDD) Mindset:** Write tests alongside or before application code to ensure correctness and prevent regressions. Aim for high, meaningful test coverage.
* **Modularity & Decoupling:** Design components with clear responsibilities and minimal dependencies on each other, often facilitated by Domain-Driven Design concepts and Laravel's service container/event system.
* **Performance Awareness:** Build with performance in mind, utilizing caching, optimized queries, efficient background processing, and modern frontend techniques.
* **Accessibility First:** Ensure the application is usable by everyone, regardless of ability, by adhering to WCAG standards.

---

## 🚀 Key Features Deep Dive

This section elaborates on the highlights mentioned earlier.

### UI/UX Highlights

* **Responsive Design:** Fully adaptive layout for optimal viewing on desktops, tablets, and smartphones using Tailwind CSS's responsive utilities.
* **Light/Dark Mode:** User-selectable themes stored in local storage and applied via CSS variables and Tailwind's `dark:` variant.
* **Interactive Elements:** Subtle animations, smooth transitions (potentially using SPA-like techniques with Turbo/Hotwire or Inertia.js if integrated later), and engaging micro-interactions powered by Alpine.js.
* **Accessibility (WCAG 2.1 AA):** Semantic HTML structure, ARIA attributes where necessary, sufficient color contrast (defined in Tailwind config), keyboard navigability, focus indicators.
* **Parallax Scrolling:** Used judiciously on the landing page hero section for visual depth.
* **Ambient Sound Toggle:** An optional feature to enhance the calming atmosphere (implementation details TBD, likely simple HTML5 audio toggle).

### E-commerce Engine

* **Product Catalog:** Supports products with multiple categories, descriptive tags, variants (e.g., size, scent), rich descriptions, and high-quality images. Includes distinction between physical and digital goods.
* **Inventory Management:** Tracks stock levels per product/variant, prevents overselling. Potential for multi-warehouse support later.
* **Shopping Cart:** Persistent cart for logged-in users (database-backed), session-based cart for guests. Real-time updates via Alpine.js or potentially Livewire/Vue/React if added.
* **Flexible Checkout:** Supports guest checkout and registered user flows. Address management for logged-in users.
* **Discount Engine:** Applies coupon codes based on defined rules (percentage, fixed amount, usage limits, date ranges).
* **Tax Calculation:** Configurable tax rules based on shipping zones/product types.
* **Shipping Zones & Rates:** Define shipping methods and costs based on geographical regions.
* **Payment Integration:** Secure payment processing via adapters for Stripe (Cards, SEPA, etc.) and PayPal, utilizing their respective SDKs and handling webhooks for payment confirmation.

### Authentication & Authorization

* **Standard Authentication:** Secure email/password login with password hashing (bcrypt). Includes password reset functionality via email tokens.
* **Social Login:** Integration with popular providers (e.g., Google, Facebook) using Laravel Socialite.
* **Multi-Factor Authentication (MFA):** Optional enhanced security via Time-based One-Time Passwords (TOTP) or potentially WebAuthn (Passkeys) using relevant Laravel packages (e.g., `laravel/fortify` features or dedicated packages).
* **Role-Based Access Control (RBAC):** Simple role system (e.g., `admin`, `staff`, `customer`) implemented using Laravel Gates & Policies to control access to specific actions or resources (like the admin dashboard).

### Admin Dashboard

* **Overview KPIs:** Real-time or near-real-time display of key metrics like total orders, revenue, new customers (implementation might use scheduled commands or event listeners to update cached stats).
* **Order Management:** View, search, filter orders. Update order status (pending, processing, shipped, completed, cancelled). Trigger fulfillment actions.
* **Product Management:** CRUD interface for products, categories, tags, variants, and inventory.
* **User Management:** View customer list, potentially manage roles (with caution).
* **Data Export:** Functionality to export orders or customer data to CSV format using packages like `maatwebsite/excel` or custom streaming responses.

### API Capabilities

* **RESTful API:** Versioned (`/api/v1/`) endpoints for core resources (products, cart, auth, orders). Follows standard HTTP verbs and status codes. See [API & Service Contracts](#-api--service-contracts-usage-guide) for details.
* **GraphQL API:** Optional secondary endpoint (`/graphql`) providing a flexible query language for clients, potentially powered by libraries like Lighthouse PHP.
* **Authentication:** Supports API token authentication (Laravel Sanctum) for SPAs or external clients, and secure signature verification for webhooks.
* **Rate Limiting:** Protects the API from abuse using Laravel's built-in rate limiting middleware.

---

## 🛠️ Technology Stack Rationale

The choice of technologies is crucial for building a modern, maintainable, and performant web application.

### Why Laravel 12?

* **Developer Productivity:** Laravel's elegant syntax, extensive features (routing, ORM, templating, queues, caching, testing utilities), and convention-over-configuration approach significantly speed up development.
* **Rich Ecosystem:** Access to a vast collection of official and community packages (Sail, Sanctum, Socialite, Telescope, Cashier, etc.) that solve common problems effectively.
* **Strong Community & Documentation:** Excellent official documentation and a large, active community provide ample support and resources.
* **Scalability Features:** Built-in support for queues, caching, task scheduling, and features like Octane make scaling applications easier.
* **Security Focus:** Laravel includes built-in protection against common web vulnerabilities like XSS, CSRF, and SQL injection, along with a clear security release process.

### Why Tailwind CSS 3?

* **Utility-First Efficiency:** Rapidly build custom designs without writing extensive custom CSS. Encourages consistency and maintainability.
* **Highly Configurable:** Easily customize the design system (colors, spacing, fonts) via the `tailwind.config.js` file.
* **Performance:** Just-in-Time (JIT) engine generates only the necessary CSS based on template usage, resulting in small production builds.
* **Responsive Design Focus:** Mobile-first responsive utilities are intuitive and powerful.
* **Dark Mode Support:** Built-in `dark:` variant simplifies implementing dark themes.

### Why Alpine.js?

* **Minimal & Lightweight:** Provides reactive and declarative JavaScript behavior directly within HTML templates without the overhead of larger frameworks like Vue or React, perfect for adding interactivity to server-rendered Blade views.
* **Laravel Synergy:** Works seamlessly with Blade components and complements Tailwind CSS well. Often sufficient for UI interactions in traditional full-stack applications.
* **Gentle Learning Curve:** Familiar syntax for developers experienced with Vue.js or basic JavaScript DOM manipulation.

### Why PHP 8.3?

* **Performance Improvements:** Each new PHP version brings performance gains. PHP 8.x introduced significant improvements, including the JIT compiler.
* **Modern Language Features:** Leverage features like readonly properties, enums, constructor property promotion, attributes, union/intersection types, fibers (for Octane), and more for cleaner, more expressive, and type-safe code.
* **Active Support & Security:** Using a recent, actively supported version ensures access to bug fixes and security updates.

### Why MariaDB 11.7?

* **Performance & Features:** A high-performance, open-source relational database, often considered a drop-in replacement for MySQL with potential performance advantages and additional features (e.g., advanced clustering, storage engines).
* **Compatibility:** Fully compatible with Laravel's Eloquent ORM and MySQL drivers.
* **Open Source:** Avoids vendor lock-in associated with some proprietary database systems.
* **Mature & Reliable:** Widely used and well-tested in production environments.

### Why Docker & Laravel Sail?

* **Consistent Development Environment:** Docker containers ensure that all developers (and CI/CD pipelines) run the application with the exact same dependencies and configurations, eliminating "it works on my machine" problems.
* **Simplified Setup:** Laravel Sail provides a simple command-line interface (`sail`) to manage the Docker environment (PHP, database, Redis, etc.), abstracting away complex Docker commands.
* **Isolation:** Prevents conflicts between different project dependencies installed directly on the host machine.
* **Production Parity (Closer):** While not identical, using Docker locally makes it easier to containerize the application for production deployment (e.g., on Fly.io or Kubernetes).

---

## 🖼️ Screenshots (Illustrative)

> **Note:** These are placeholders. Actual screenshots should be added once the UI is implemented. Ensure the images exist at the specified paths relative to the README file in the repository root.

| Landing page (light)                                   | Landing page (dark)                                  |
| :----------------------------------------------------- | :--------------------------------------------------- |
| ![Landing Light](docs/screens/landing_light.png)       | ![Landing Dark](docs/screens/landing_dark.png)       |
| _Hero section with parallax effect and product highlights_ | _Dark mode variant showcasing theme switching_         |

| Product detail page                                    | Admin dashboard overview                               |
| :----------------------------------------------------- | :--------------------------------------------------- |
| ![Product Detail](docs/screens/product_detail.png)     | ![Dashboard](docs/screens/dashboard.png)             |
| _Detailed product view with images, description, variants_ | _Admin panel showing KPIs and navigation for management_ |

---

## 🏗️ Architectural Overview: A Layered Approach

This application adopts a layered architecture, influenced by Domain-Driven Design (DDD) principles, to promote separation of concerns, maintainability, and testability.

### High-Level Diagram

```mermaid
graph TD
    subgraph User Interaction
        direction LR
        CLIENT[Browser / SPA / Mobile App]
    end

    CLIENT -- HTTPS --> GW(API Gateway / Load Balancer)

    subgraph Web Application (Laravel)
        direction TB
        GW --> ROUTER[Laravel Router (web.php / api.php)]
        ROUTER --> MIDDLEWARE[HTTP Middleware (Auth, CSRF, CORS, etc.)]
        MIDDLEWARE --> CONTROLLERS[HTTP Controllers (app/Http/Controllers)]
        CONTROLLERS --> SERVICES[Service Layer (app/Domain/.../Services)]
        CONTROLLERS --> VIEWS[Presentation (Blade Views / API Resources)]
        VIEWS -- Vite --> ASSETS[Compiled CSS/JS (public/build)]
    end

    subgraph Domain Layer (Core Logic)
        direction TB
        SERVICES --> DOMAIN_MODELS[Domain Models / Aggregates (app/Domain/.../Models)]
        SERVICES --> REPOSITORIES[Repositories (Implicit via Eloquent / Explicit Interfaces)]
        SERVICES --> DOMAIN_EVENTS[Domain Events & Listeners (app/Domain/.../Events)]
        DOMAIN_MODELS -- Uses --> VALUE_OBJECTS[Value Objects (e.g., Money, Address)]
    end

    subgraph Infrastructure Layer
        direction TB
        REPOSITORIES -- Eloquent ORM --> DB[(MariaDB 11.7)]
        SERVICES -- Adapters --> EXT_PAYMENT[Payment Gateways (Stripe, PayPal)]
        SERVICES -- Adapters --> EXT_MAIL[Email Services (Mailgun, SES)]
        SERVICES -- Adapters --> EXT_SEARCH[Search Services (Algolia - Future)]
        DOMAIN_EVENTS -- Dispatched To --> QUEUE[Job Queue (Redis / Database)]
    end

    subgraph Async Processing
        direction LR
        QUEUE --> WORKERS[Queue Workers (Laravel Octane / Horizon)]
        WORKERS --> SERVICES  // Workers re-interact with Services
    end

    style CLIENT fill:#f9f,stroke:#333,stroke-width:2px
    style GW fill:#ccf,stroke:#333,stroke-width:2px
    style DB fill:#fcc,stroke:#333,stroke-width:2px
    style QUEUE fill:#fcc,stroke:#333,stroke-width:2px
    style EXT_PAYMENT fill:#eee,stroke:#333,stroke-width:1px,stroke-dasharray: 5 5
    style EXT_MAIL fill:#eee,stroke:#333,stroke-width:1px,stroke-dasharray: 5 5
    style EXT_SEARCH fill:#eee,stroke:#333,stroke-width:1px,stroke-dasharray: 5 5
