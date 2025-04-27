# The Scent - Premium Aromatherapy E-commerce Platform (v1.0)

Welcome to The Scent, a modern, full-featured, and beautifully crafted e-commerce platform built to showcase and sell premium natural aromatherapy products. This project is designed from the ground up for extensibility, security, and seamless user experience, featuring a custom MVC-inspired PHP architecture.

🧘 "Find your moment of calm" – Discover your perfect scent and enhance your well-being.

![The Scent Platform Preview](https://placeholder-image.com/the-scent-preview.jpg)

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Technology Stack](#technology-stack)
- [Getting Started](#getting-started)
- [Technical Architecture](#technical-architecture)
- [Project Structure](#project-structure)
- [Component Logic Flowchart](#component-logic-flowchart)
- [Frontend Implementation](#frontend-implementation)
- [Backend Implementation](#backend-implementation)
- [Database Schema](#database-schema)
- [API Documentation](#api-documentation)
- [Testing Strategy](#testing-strategy)
- [Deployment Guide](#deployment-guide)
- [Security Considerations](#security-considerations)
- [Performance Optimization](#performance-optimization)
- [Maintenance and Updates](#maintenance-and-updates)
- [Contributing](#contributing)
- [License](#license)

## Overview

The Scent is a comprehensive e-commerce solution designed specifically for aromatherapy and wellness products. Our platform combines modern design aesthetics with efficient backend functionality to create a seamless shopping experience for customers seeking premium scent products. The integration of immersive UI elements, such as ambient sound toggles and day/night mode, creates a sensory-rich online shopping environment that reflects the essence of the products being sold.

This project leverages Laravel's robust framework capabilities while implementing custom architectural patterns to ensure scalability and maintainability. The focus on responsiveness, accessibility, and performance optimization makes The Scent a standout e-commerce platform in the wellness space.

## Key Features

- **Immersive User Interface**: Elegant, responsive design with interactive elements like ambient sound and dark mode
- **Product Discovery Engine**: Advanced filtering and recommendation system based on scent preferences and wellness goals
- **Personalized User Accounts**: Custom dashboards, saved preferences, and order history
- **Secure Checkout Process**: PCI-compliant payment gateway integration with multiple payment options
- **Order Management System**: Comprehensive order tracking and notification system
- **Admin Dashboard**: Powerful backend for inventory, order, and customer management
- **Analytics Integration**: Detailed insights into customer behavior and sales performance
- **Multi-language Support**: Internationalization ready with initial support for English, French, and Spanish
- **Responsive Design**: Seamless experience across desktop, tablet, and mobile devices
- **Performance Optimized**: Fast loading times with advanced caching strategies

## Technology Stack

The Scent platform is built using a modern technology stack:

- **Frontend**:
  - HTML5, CSS3, JavaScript (ES6+)
  - Tailwind CSS v3.3 for styling
  - Alpine.js for interactive components
  - GSAP for animations
  - Webpack for asset bundling

- **Backend**:
  - PHP v8.3
  - Laravel v12 framework
  - MariaDB v11.7 for database
  - Redis for caching
  - Laravel Sanctum for API authentication

- **Server**:
  - Apache 2.4
  - Ubuntu 24.04 LTS
  - Composer for PHP dependencies
  - Node.js and NPM for frontend dependencies

- **DevOps**:
  - Docker for containerization
  - GitHub Actions for CI/CD
  - Laravel Forge for deployment
  - AWS S3 for media storage
  - Cloudflare for CDN

## Getting Started

### Prerequisites

Before installing The Scent platform, ensure you have the following prerequisites:

- PHP >= 8.3
- Composer >= 2.5
- Node.js >= 18.x
- NPM >= 9.x
- MariaDB >= 11.7
- Apache >= 2.4 or Nginx >= 1.25
- Git >= 2.40

### Installation

1. **Clone the repository**

```bash
git clone https://github.com/yourusername/the-scent.git
cd the-scent
```

2. **Install PHP dependencies**

```bash
composer install
```

3. **Install JavaScript dependencies**

```bash
npm install
```

4. **Environment configuration**

```bash
cp .env.example .env
php artisan key:generate
```

Edit the `.env` file to configure your database connection, mail server, and other environment-specific settings.

5. **Set up the database**

```bash
php artisan migrate
php artisan db:seed
```

6. **Build frontend assets**

```bash
npm run dev  # For development
npm run build  # For production
```

7. **Start the development server**

```bash
php artisan serve
```

Your application should now be running at `http://localhost:8000`.

### Development Environment Setup

For a consistent development experience, we recommend using Docker with Laravel Sail:

```bash
# Setup Laravel Sail
php artisan sail:install

# Start the Docker environment
./vendor/bin/sail up -d

# Run migrations and seed the database
./vendor/bin/sail artisan migrate --seed

# Compile frontend assets
./vendor/bin/sail npm run dev
```

## Technical Architecture

The Scent platform follows a modified MVC architecture with additional layers for improved separation of concerns and maintainability.

### System Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                      Client Layer                            │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐    │
│  │  Web Browser │   │ Mobile App  │   │ Third-party API │    │
│  └─────────────┘   └─────────────┘   └─────────────────┘    │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐    │
│  │    Views    │   │   API       │   │    Frontend     │    │
│  │ (Blade/Vue) │   │ Controllers │   │   Components    │    │
│  └─────────────┘   └─────────────┘   └─────────────────┘    │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      Application Layer                       │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐    │
│  │ Controllers │   │  Services   │   │     Events      │    │
│  └─────────────┘   └─────────────┘   └─────────────────┘    │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐    │
│  │  Requests   │   │  Responses  │   │    Listeners    │    │
│  └─────────────┘   └─────────────┘   └─────────────────┘    │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                       Domain Layer                           │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐    │
│  │   Models    │   │ Repositories│   │    Factories    │    │
│  └─────────────┘   └─────────────┘   └─────────────────┘    │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐    │
│  │   Entities  │   │   Traits    │   │     Scopes      │    │
│  └─────────────┘   └─────────────┘   └─────────────────┘    │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                     Infrastructure Layer                     │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐    │
│  │  Database   │   │   Cache     │   │    External     │    │
│  │  (MariaDB)  │   │   (Redis)   │   │      APIs       │    │
│  └─────────────┘   └─────────────┘   └─────────────────┘    │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐    │
│  │  Queue      │   │   Storage   │   │    Payment      │    │
│  │  System     │   │   (S3)      │   │    Gateways     │    │
│  └─────────────┘   └─────────────┘   └─────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

### Key Architectural Patterns

1. **Service Layer**: Business logic is encapsulated in service classes, keeping controllers thin and focused on HTTP concerns.

2. **Repository Pattern**: Database interactions are abstracted through repository interfaces, enabling easier testing and potential database changes.

3. **DTO (Data Transfer Objects)**: Used to define structured data passing between layers.

4. **CQRS-inspired approach**: Separation of command (write) and query (read) operations for complex domains like order processing.

5. **Event-Driven Architecture**: Using Laravel's event system for loose coupling between components.

6. **Domain-Driven Design principles**: For core business logic in complex areas of the application.

### Request Lifecycle

1. HTTP request is received by the web server
2. Laravel routing system directs the request to the appropriate controller
3. Request validation occurs through Form Request classes
4. Controller invokes the appropriate service class with validated data
5. Service interacts with repositories to fetch or manipulate data
6. Service applies business logic and returns results
7. Controller transforms the data into the appropriate response format
8. Response is returned to the client

## Project Structure

The Scent follows an extended Laravel project structure with additional organization for maintainability:

```
the-scent/
├── app/                         # Application code
│   ├── Console/                 # Artisan commands
│   ├── Exceptions/              # Exception handlers
│   ├── Http/                    # HTTP layer
│   │   ├── Controllers/         # Request handlers
│   │   │   ├── Admin/           # Admin controllers
│   │   │   ├── Api/             # API controllers
│   │   │   └── Web/             # Web controllers
│   │   ├── Middleware/          # HTTP middleware
│   │   └── Requests/            # Form request validation
│   ├── Models/                  # Eloquent models
│   ├── Providers/               # Service providers
│   ├── Repositories/            # Data access layer
│   │   ├── Contracts/           # Repository interfaces
│   │   └── Eloquent/            # Eloquent implementations
│   ├── Services/                # Business logic
│   │   ├── Cart/                # Cart management
│   │   ├── Checkout/            # Checkout process
│   │   ├── Inventory/           # Inventory management
│   │   ├── Payment/             # Payment processing
│   │   └── Product/             # Product management
│   └── Support/                 # Helper classes
├── bootstrap/                   # Framework bootstrap
├── config/                      # Configuration files
├── database/                    # Database migrations and seeds
│   ├── factories/               # Model factories
│   ├── migrations/              # Database migrations
│   └── seeders/                 # Database seeders
├── public/                      # Publicly accessible files
│   ├── css/                     # Compiled CSS
│   ├── js/                      # Compiled JavaScript
│   └── images/                  # Image assets
├── resources/                   # Frontend resources
│   ├── css/                     # CSS source files
│   ├── js/                      # JavaScript source files
│   │   ├── components/          # Vue/Alpine components
│   │   ├── pages/               # Page-specific scripts
│   │   └── utils/               # Utility functions
│   ├── lang/                    # Language files
│   └── views/                   # Blade templates
│       ├── admin/               # Admin panel views
│       ├── auth/                # Authentication views
│       ├── checkout/            # Checkout process views
│       ├── components/          # Reusable view components
│       ├── layouts/             # Layout templates
│       ├── pages/               # Page templates
│       └── partials/            # Partial templates
├── routes/                      # Route definitions
│   ├── api.php                  # API routes
│   ├── channels.php             # Broadcast channels
│   ├── console.php              # Console routes
│   └── web.php                  # Web routes
├── storage/                     # Storage directory
├── tests/                       # Test suite
│   ├── Feature/                 # Feature tests
│   └── Unit/                    # Unit tests
├── vendor/                      # Composer dependencies
├── .env.example                 # Environment configuration example
├── .gitignore                   # Git ignore configuration
├── composer.json                # PHP dependencies
├── package.json                 # Node.js dependencies
├── phpunit.xml                  # PHPUnit configuration
├── README.md                    # Project documentation
├── tailwind.config.js           # Tailwind CSS configuration
├── vite.config.js               # Vite configuration
└── webpack.mix.js               # Laravel Mix configuration
```

### Key Configuration Files

- **config/cart.php**: Configuration for shopping cart behavior
- **config/payment.php**: Payment gateway integration settings
- **config/product.php**: Product-related configurations
- **config/shipping.php**: Shipping options and calculations
- **config/scent.php**: Scent platform-specific configurations

## Component Logic Flowchart

### User Authentication Flow

```
┌──────────┐     ┌──────────┐     ┌───────────────┐     ┌─────────────┐     ┌─────────────┐
│  User    │     │ Login/   │     │  Validate     │     │ Generate    │     │ Redirect to │
│ Request  ├────►│ Register ├────►│  Credentials  ├────►│ Auth Token  ├────►│ Dashboard   │
└──────────┘     └──────────┘     └───────────────┘     └─────────────┘     └─────────────┘
                                          │
                                          │ Validation Fails
                                          ▼
                                  ┌───────────────┐
                                  │ Return Error  │
                                  │ Response      │
                                  └───────────────┘
```

### Product Browsing Flow

```
┌──────────┐     ┌──────────┐     ┌───────────────┐     ┌─────────────┐     ┌─────────────┐
│  User    │     │ Product  │     │  Apply        │     │ Load        │     │ Display     │
│ Request  ├────►│ Catalog  ├────►│  Filters      ├────►│ Products    ├────►│ Results     │
└──────────┘     └──────────┘     └───────────────┘     └─────────────┘     └─────────────┘
                                          │                     │
                                          │                     │ User Selects Product
                                          │                     ▼
                                          │             ┌───────────────┐
                                          │             │ Product       │
                                          │             │ Detail Page   │
                                          │             └───────────────┘
                                          │                     │
                                          │                     │ Add to Cart
                                          │                     ▼
                                          │             ┌───────────────┐
                                          │             │ Update        │
                                          │             │ Shopping Cart │
                                          │             └───────────────┘
                                          │
                                          │ No Results
                                          ▼
                                  ┌───────────────┐
                                  │ Show Related  │
                                  │ Suggestions   │
                                  └───────────────┘
```

### Checkout Process Flow

```
┌──────────┐     ┌──────────┐     ┌───────────────┐     ┌─────────────┐     ┌─────────────┐
│  Cart    │     │ Initiate │     │  Collect      │     │ Process     │     │ Order       │
│ Review   ├────►│ Checkout ├────►│  Info         ├────►│ Payment     ├────►│ Confirmation│
└──────────┘     └──────────┘     └───────────────┘     └─────────────┘     └─────────────┘
                                          │                     │                   │
                                          │                     │ Payment Failed    │
                                          │                     ▼                   │
                                          │             ┌───────────────┐          │
                                          │             │ Retry Payment │          │
                                          │             │ or Update     │          │
                                          │             │ Payment Method│          │
                                          │             └───────────────┘          │
                                          │                                        │
                                          │ Validation Error                       │
                                          ▼                                        │
                                  ┌───────────────┐                                │
                                  │ Display Input │                                │
                                  │ Errors        │                                │
                                  └───────────────┘                                │
                                                                                   │
                                                                                   ▼
                                                                           ┌───────────────┐
                                                                           │ Send Order    │
                                                                           │ Confirmation  │
                                                                           │ Email         │
                                                                           └───────────────┘
```

### Order Management Flow

```
┌──────────┐     ┌──────────┐     ┌───────────────┐     ┌─────────────┐     ┌─────────────┐
│  Order   │     │ Admin    │     │  Inventory    │     │ Update Order│     │ Customer    │
│ Received ├────►│ Review   ├────►│  Check        ├────►│ Status      ├────►│ Notification│
└──────────┘     └──────────┘     └───────────────┘     └─────────────┘     └─────────────┘
                      │                    │                    │                   │
                      │                    │ Insufficient       │                   │
                      │                    │ Inventory          │                   │
                      │                    ▼                    │                   │
                      │            ┌───────────────┐           │                   │
                      │            │ Trigger       │           │                   │
                      │            │ Backorder     │           │                   │
                      │            │ Process       │           │                   │
                      │            └───────────────┘           │                   │
                      │                                        │                   │
                      │ Order Fulfillment                      │                   │
                      ▼                                        │                   │
              ┌───────────────┐                               │                   │
              │ Generate      │                               │                   │
              │ Shipping Label│                               │                   │
              └───────────────┘                               │                   │
                      │                                        │                   │
                      └────────────────────┬──────────────────┘                   │
                                           │                                       │
                                           ▼                                       │
                                   ┌───────────────┐                               │
                                   │ Mark as       │                               │
                                   │ Shipped       │                               │
                                   └───────────────┘                               │
                                           │                                       │
                                           └───────────────────────────────────────┘
```

## Frontend Implementation

The frontend of The Scent platform is built with a focus on creating a sensory-rich, immersive shopping experience that reflects the essence of aromatherapy products.

### Design Philosophy

Our design philosophy centers around five core principles:

1. **Sensory Engagement**: Leveraging visual, auditory, and interactive elements to create a multi-sensory digital experience
2. **Intuitive Navigation**: Ensuring users can easily find products and information without friction
3. **Emotional Connection**: Using design elements that evoke calm, wellness, and natural beauty
4. **Accessibility**: Ensuring the platform is usable by people of all abilities
5. **Responsive Design**: Providing a consistent experience across all device types and screen sizes

### Component Architecture

The frontend is built using a component-based architecture with Blade components for server-rendered views and Alpine.js for client-side interactivity:

```
┌─────────────────────────────────────────────────────────────┐
│                     Layout Components                        │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐    │
│  │    Header   │   │    Footer   │   │    Sidebar      │    │
│  └─────────────┘   └─────────────┘   └─────────────────┘    │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                     UI Components                            │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐    │
│  │   Button    │   │    Modal    │   │      Card       │    │
│  └─────────────┘   └─────────────┘   └─────────────────┘    │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐    │
│  │    Form     │   │   Carousel  │   │     Loader      │    │
│  └─────────────┘   └─────────────┘   └─────────────────┘    │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                  Business Components                         │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐    │
│  │  Product    │   │  Cart Item  │   │ Checkout Form   │    │
│  │   Card      │   │             │   │                 │    │
│  └─────────────┘   └─────────────┘   └─────────────────┘    │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐    │
│  │   Filter    │   │   Review    │   │ Product Gallery │    │
│  │   Panel     │   │   Widget    │   │                 │    │
│  └─────────────┘   └─────────────┘   └─────────────────┘    │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                   Page Templates                             │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐    │
│  │   Home      │   │   Product   │   │    Category     │    │
│  │   Page      │   │    Page     │   │      Page       │    │
│  └─────────────┘   └─────────────┘   └─────────────────┘    │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐    │
│  │   Cart      │   │  Checkout   │   │     Account     │    │
│  │   Page      │   │    Page     │   │      Pages      │    │
│  └─────────────┘   └─────────────┘   └─────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

### Tailwind CSS Implementation

Tailwind CSS is used as the primary styling framework with a customized configuration:

```javascript
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        'bg': '#f6f5f1',
        'bg-dark': '#182024',
        'text': '#262c2f',
        'text-dark': '#ebeaea',
        'primary': '#2c899a',
        'primary-dark': '#30ccbb',
        'accent': '#d69f61',
        'accent-dark': '#e2bb85',
        'soap': '#b9cfc3',
        'overlay': 'rgba(44,137,154,0.08)',
        'overlay-dark': 'rgba(24,32,36,0.6)',
        'cta': '#ff7f50',
        'cta-hover': '#ff6347',
      },
      fontFamily: {
        'head': ['Cormorant Garamond', 'serif'],
        'body': ['Montserrat', 'sans-serif'],
        'accent': ['Raleway', 'sans-serif'],
      },
      boxShadow: {
        'custom': '0 8px 34px 0 rgba(60,35,16,0.11)',
        'custom-dark': '0 8px 34px 0 rgba(0,0,0,0.16)',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
    require('@tailwindcss/aspect-ratio'),
  ],
  darkMode: 'class',
}
```

### Responsive Design Strategy

Our responsive design strategy follows a mobile-first approach with several breakpoints:

- **Small (sm)**: ≥ 640px
- **Medium (md)**: ≥ 768px
- **Large (lg)**: ≥ 1024px
- **Extra Large (xl)**: ≥ 1280px
- **2XL (2xl)**: ≥ 1536px

Key responsive design patterns include:

1. **Fluid Typography**: Using `clamp()` for responsive font sizes
2. **Grid-Based Layouts**: Using CSS Grid for complex layouts that adapt across screen sizes
3. **Component Adaptability**: Components that change their layout and behavior based on screen size
4. **Strategic Content Prioritization**: Showing the most important content first on mobile displays
5. **Touch-Friendly Interactions**: Larger touch targets on mobile devices

### Accessibility Implementation

Accessibility features include:

- Semantic HTML structure
- ARIA attributes for complex interactive elements
- Keyboard navigation support
- Screen reader compatibility
- Sufficient color contrast ratios
- Focus state indicators
- Skip-to-content links
- Alternative text for images
- Accessible form labels and validation

## Backend Implementation

### Controller Structure

Controllers follow the single responsibility principle and are organized by domain and type:

- **Web Controllers**: Handle web requests and return views
- **API Controllers**: Handle API requests and return JSON responses
- **Admin Controllers**: Handle admin panel requests
- **Ajax Controllers**: Handle AJAX requests for dynamic updates

Example controller methods:

```php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductSearchRequest;
use App\Services\Product\ProductService;
use Illuminate\View\View;

class ProductController extends Controller
{
    protected $productService;
    
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
    
    public function index(ProductSearchRequest $request): View
    {
        $products = $this->productService->searchProducts(
            $request->validated(),
            $request->get('page', 1),
            $request->get('per_page', 12)
        );
        
        return view('products.index', [
            'products' => $products,
            'categories' => $this->productService->getCategories(),
            'filters' => $request->validated()
        ]);
    }
    
    public function show(string $slug): View
    {
        $product = $this->productService->getProductBySlug($slug);
        
        abort_if(!$product, 404);
        
        return view('products.show', [
            'product' => $product,
            'relatedProducts' => $this->productService->getRelatedProducts($product->id, 4)
        ]);
    }
}
```

### Service Layer

Services encapsulate business logic and are organized by domain:

```php
namespace App\Services\Cart;

use App\Exceptions\CartException;
use App\Models\Product;
use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CartService
{
    protected $cartRepository;
    
    public function __construct(CartRepositoryInterface $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }
    
    public function getUserCart()
    {
        $userId = Auth::id() ?? session()->getId();
        return $this->cartRepository->getCartByUserId($userId);
    }
    
    public function addItem(Product $product, int $quantity = 1, array $options = [])
    {
        try {
            $userId = Auth::id() ?? session()->getId();
            
            // Check inventory
            if (!$this->hasEnoughInventory($product, $quantity)) {
                throw new CartException("Not enough inventory for {$product->name}");
            }
            
            return $this->cartRepository->addItemToCart($userId, $product->id, $quantity, $options);
        } catch (\Exception $e) {
            Log::error('Failed to add item to cart: ' . $e->getMessage(), [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'options' => $options
            ]);
            
            throw new CartException('Failed to add item to cart', 0, $e);
        }
    }
    
    public function updateItem($itemId, int $quantity)
    {
        // Implementation
    }
    
    public function removeItem($itemId)
    {
        // Implementation
    }
    
    public function clearCart()
    {
        // Implementation
    }
    
    public function calculateTotal()
    {
        // Implementation
    }
    
    protected function hasEnoughInventory(Product $product, int $quantity): bool
    {
        // Implementation
    }
}
```

### Repository Pattern

Repositories abstract data access operations:

```php
namespace App\Repositories\Contracts;

interface ProductRepositoryInterface
{
    public function getAll(int $perPage = 15);
    public function findById(int $id);
    public function findBySlug(string $slug);
    public function search(array $criteria, int $page = 1, int $perPage = 15);
    public function getByCategory(int $categoryId, int $perPage = 15);
    public function getRelated(int $productId, int $limit = 4);
    public function getBestSellers(int $limit = 8);
    public function getNew(int $limit = 8);
    public function getFeatured(int $limit = 4);
}
```

Implementation example:

```php
namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository implements ProductRepositoryInterface
{
    protected $model;
    
    public function __construct(Product $model)
    {
        $this->model = $model;
    }
    
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->active()->with(['category', 'images'])->paginate($perPage);
    }
    
    public function findById(int $id)
    {
        return $this->model->with(['category', 'images', 'reviews'])->findOrFail($id);
    }
    
    public function findBySlug(string $slug)
    {
        return $this->model->where('slug', $slug)
            ->with(['category', 'images', 'reviews', 'attributes'])
            ->first();
    }
    
    public function search(array $criteria, int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->active()->with(['category', 'images']);
        
        // Apply search criteria
        if (!empty($criteria['keyword'])) {
            $query->where(function (Builder $query) use ($criteria) {
                $query->where('name', 'like', "%{$criteria['keyword']}%")
                    ->orWhere('description', 'like', "%{$criteria['keyword']}%");
            });
        }
        
        if (!empty($criteria['category_id'])) {
            $query->where('category_id', $criteria['category_id']);
        }
        
        if (!empty($criteria['price_min'])) {
            $query->where('price', '>=', $criteria['price_min']);
        }
        
        if (!empty($criteria['price_max'])) {
            $query->where('price', '<=', $criteria['price_max']);
        }
        
        // More filter conditions...
        
        return $query->paginate($perPage, ['*'], 'page', $page);
    }
    
    // Other method implementations...
}
```

### Event-Driven Components

Laravel's event system is used for loosely coupled communication between components:

```php
namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced
{
    use Dispatchable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
```

Listener example:

```php
namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Services\Inventory\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateInventoryOnOrderPlaced implements ShouldQueue
{
    protected $inventoryService;
    
    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }
    
    public function handle(OrderPlaced $event)
    {
        foreach ($event->order->items as $item) {
            $this->inventoryService->decreaseStock(
                $item->product_id, 
                $item->quantity
            );
        }
    }
}
```

## Database Schema

The database schema is designed to efficiently support all e-commerce operations while maintaining data integrity and enabling fast queries.

### Core Tables

#### Users Table
```sql
CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
);
```

#### User Profiles Table
```sql
CREATE TABLE `user_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `preferences` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_profiles_user_id_foreign` (`user_id`),
  CONSTRAINT `user_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
```

#### Categories Table
```sql
CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
);
```

#### Products Table
```sql
CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `weight` decimal(8,2) DEFAULT NULL,
  `dimensions` json DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  KEY `products_category_id_foreign` (`category_id`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
);
```

#### Scent Attributes Table
```sql
CREATE TABLE `scent_attributes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('base_note','middle_note','top_note','mood','intensity') NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `scent_attributes_name_type_unique` (`name`, `type`)
);
```

#### Product Scent Attributes Table
```sql
CREATE TABLE `product_scent_attribute` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `scent_attribute_id` bigint(20) UNSIGNED NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_scent_attribute_product_id_foreign` (`product_id`),
  KEY `product_scent_attribute_scent_attribute_id_foreign` (`scent_attribute_id`),
  CONSTRAINT `product_scent_attribute_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_scent_attribute_scent_attribute_id_foreign` FOREIGN KEY (`scent_attribute_id`) REFERENCES `scent_attributes` (`id`) ON DELETE CASCADE
);
```

#### Orders Table
```sql
CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `shipping_address` json NOT NULL,
  `billing_address` json NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `orders_user_id_foreign` (`user_id`),
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
```

#### Order Items Table
```sql
CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `options` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
);
```

### Entity Relationship Diagram

```
┌─────────────┐       ┌──────────────┐       ┌────────────┐
│   Users     │       │ UserProfiles │       │ Addresses  │
├─────────────┤       ├──────────────┤       ├────────────┤
│ id          │───┐   │ id           │       │ id         │
│ name        │   └──>│ user_id      │       │ user_id    │<─┐
│ email       │       │ phone        │       │ type       │  │
│ password    │       │ birth_date   │       │ address1   │  │
└─────────────┘       │ preferences  │       │ city       │  │
                      └──────────────┘       └────────────┘  │
                                                             │
┌─────────────┐       ┌──────────────┐       ┌────────────┐  │
│ Categories  │       │   Products   │       │   Orders   │  │
├─────────────┤       ├──────────────┤       ├────────────┤  │
│ id          │       │ id           │       │ id         │  │
│ parent_id   │<──┐   │ category_id  │<──────│ user_id    │──┘
│ name        │   │   │ name         │       │ status     │
│ slug        │   └───│ slug         │       │ total      │
│ description │       │ price        │       │ addresses  │
└─────────────┘       └──────────────┘       └────────────┘
                             │                      │
                             │                      │
                             ▼                      ▼
                      ┌──────────────┐       ┌────────────┐
                      │ProductImages │       │ OrderItems │
                      ├──────────────┤       ├────────────┤
                      │ id           │       │ id         │
                      │ product_id   │       │ order_id   │
                      │ image_path   │       │ product_id │
                      │ is_primary   │       │ quantity   │
                      └──────────────┘       │ price      │
                                             └────────────┘
                                                    │
                             ┌──────────────┐       │
                             │ScentAttributes│<─────┘
                             ├──────────────┤
                             │ id           │
                             │ name         │
                             │ type         │
                             └──────────────┘
```

## API Documentation

The Scent platform includes a comprehensive RESTful API for integration with mobile apps and third-party services.

### API Authentication

API authentication uses Laravel Sanctum for token-based authentication. Clients must obtain an API token by authenticating with valid credentials.

Example authentication:

```
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}

Response:
{
  "status": "success",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com"
    }
  }
}
```

### API Endpoints

#### Products API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/products | List all products with pagination |
| GET | /api/products/{id} | Get a single product by ID |
| GET | /api/products/slug/{slug} | Get a single product by slug |
| GET | /api/products/search | Search products with filters |
| GET | /api/products/categories/{categoryId} | Get products by category |
| GET | /api/products/featured | Get featured products |

Example request and response:

```
GET /api/products?page=1&per_page=10
Authorization: Bearer {token}

Response:
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Lavender Dreams",
        "slug": "lavender-dreams",
        "price": "29.99",
        "category": {
          "id": 2,
          "name": "Essential Oils"
        },
        "images": [
          {
            "id": 1,
            "path": "/storage/products/lavender-dreams-1.jpg",
            "is_primary": true
          }
        ]
      },
      // More products...
    ],
    "from": 1,
    "last_page": 5,
    "per_page": 10,
    "to": 10,
    "total": 50
  }
}
```

#### Cart API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/cart | Get the current cart |
| POST | /api/cart/items | Add an item to cart |
| PUT | /api/cart/items/{id} | Update cart item quantity |
| DELETE | /api/cart/items/{id} | Remove an item from cart |
| DELETE | /api/cart/clear | Clear the entire cart |

Example add to cart:

```
POST /api/cart/items
Authorization: Bearer {token}
Content-Type: application/json

{
  "product_id": 1,
  "quantity": 2,
  "options": {
    "size": "100ml"
  }
}

Response:
{
  "status": "success",
  "message": "Item added to cart",
  "data": {
    "cart": {
      "id": "cart_123",
      "items": [
        {
          "id": "item_1",
          "product": {
            "id": 1,
            "name": "Lavender Dreams",
            "price": "29.99"
          },
          "quantity": 2,
          "options": {
            "size": "100ml"
          },
          "subtotal": "59.98"
        }
      ],
      "total": "59.98"
    }
  }
}
```

#### Order API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/orders | List user orders |
| GET | /api/orders/{id} | Get a specific order |
| POST | /api/orders | Create a new order |
| GET | /api/orders/{id}/tracking | Get order tracking information |

### API Response Format

All API responses follow a consistent format:

```json
{
  "status": "success|error",
  "message": "Optional message string",
  "data": {
    // Response data
  },
  "errors": {
    // Error details (only when status is "error")
  },
  "meta": {
    // Pagination or other metadata
  }
}
```

### Rate Limiting

API rate limiting is implemented to prevent abuse:

- 60 requests per minute for authenticated users
- 30 requests per minute for unauthenticated requests
- Different rate limits for specific endpoints like authentication

## Testing Strategy

The Scent platform employs a comprehensive testing strategy to ensure code quality and prevent regressions.

### Unit Testing

Unit tests focus on testing individual components in isolation:

- Service classes: Business logic
- Repository classes: Data access
- Helper/Utility classes: Standalone functionality

Example unit test:

```php
namespace Tests\Unit\Services;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Product\ProductService;
use Mockery;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    public function test_get_product_by_slug_returns_product_when_found()
    {
        // Arrange
        $expectedProduct = new Product([
            'id' => 1,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 29.99
        ]);
        
        $mockRepository = Mockery::mock(ProductRepositoryInterface::class);
        $mockRepository->shouldReceive('findBySlug')
            ->once()
            ->with('test-product')
            ->andReturn($expectedProduct);
            
        $service = new ProductService($mockRepository);
        
        // Act
        $product = $service->getProductBySlug('test-product');
        
        // Assert
        $this->assertSame($expectedProduct, $product);
    }
    
    public function test_get_product_by_slug_returns_null_when_not_found()
    {
        // Arrange
        $mockRepository = Mockery::mock(ProductRepositoryInterface::class);
        $mockRepository->shouldReceive('findBySlug')
            ->once()
            ->with('nonexistent-product')
            ->andReturnNull();
            
        $service = new ProductService($mockRepository);
        
        // Act
        $product = $service->getProductBySlug('nonexistent-product');
        
        // Assert
        $this->assertNull($product);
    }
}
```

### Feature Testing

Feature tests cover complete business workflows and ensure integration between components:

```php
namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

class CartManagementTest extends TestCase
{
    public function test_user_can_add_product_to_cart()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Lavender Dreams',
            'price' => 29.99,
            'stock_quantity' => 10
        ]);
        
        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2
            ]);
            
        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'cart' => [
                        'items' => [
                            [
                                'product' => [
                                    'id' => $product->id,
                                    'name' => 'Lavender Dreams'
                                ],
                                'quantity' => 2
                            ]
                        ]
                    ]
                ]
            ]);
            
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2
        ]);
    }
}
```

### Browser Testing

Laravel Dusk is used for browser testing to ensure the front-end user interfaces work correctly:

```php
namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CheckoutProcessTest extends DuskTestCase
{
    public function test_user_can_complete_checkout_process()
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create();
            
            $browser->loginAs($user)
                ->visit('/cart')
                ->assertSee('Shopping Cart')
                ->press('Proceed to Checkout')
                ->assertPathIs('/checkout')
                ->type('billing_name', 'John Doe')
                ->type('billing_email', 'john@example.com')
                ->type('billing_address', '123 Main St')
                ->type('billing_city', 'New York')
                ->type('billing_zip', '10001')
                ->select('billing_country', 'US')
                ->press('Continue to Payment')
                ->assertSee('Payment Information')
                ->type('card_number', '4242424242424242')
                ->type('card_exp', '12/25')
                ->type('card_cvc', '123')
                ->press('Complete Order')
                ->assertSee('Order Confirmation')
                ->assertSee('Thank you for your order!');
        });
    }
}
```

### API Testing

API endpoints are tested to ensure they function correctly and return the expected responses:

```php
namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Product;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    public function test_api_returns_paginated_products()
    {
        // Arrange
        Product::factory()->count(15)->create();
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/products?page=1&per_page=10');
        
        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'price'
                        ]
                    ],
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total'
                ]
            ])
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'current_page' => 1,
                    'per_page' => 10,
                    'total' => 15
                ]
            ]);
    }
}
```

### Performance Testing

Performance testing ensures the application meets performance requirements:

- Load testing: Using k6 or Apache JMeter to simulate multiple users
- Stress testing: Testing system limits and recovery capability
- Endurance testing: Testing system behavior under sustained load

### Security Testing

Security testing includes:

- Vulnerability scanning with tools like OWASP ZAP
- CSRF protection verification
- XSS protection testing
- Input validation testing
- Authentication and authorization testing

## Deployment Guide

The Scent platform can be deployed in multiple environments with different configurations.

### Development Environment

Local development setup:

1. **Prerequisites:**
   - PHP 8.3
   - Composer 2.5+
   - Node.js 18+
   - Docker Desktop

2. **Setup Laravel Sail:**
   ```bash
   composer require laravel/sail --dev
   php artisan sail:install
   ```

3. **Start the development environment:**
   ```bash
   ./vendor/bin/sail up -d
   ```

4. **Run migrations and seed:**
   ```bash
   ./vendor/bin/sail artisan migrate:fresh --seed
   ```

5. **Build assets:**
   ```bash
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run dev
   ```

### Staging Environment

The staging environment mirrors production but allows for testing before deployment:

1. **Server Requirements:**
   - Ubuntu 24.04 LTS
   - PHP 8.3
   - MariaDB 11.7
   - Apache 2.4
   - Redis 7.0+

2. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/the-scent.git /var/www/staging.thescent.com
   cd /var/www/staging.thescent.com
   git checkout develop
   ```

3. **Install dependencies:**
   ```bash
   composer install --no-dev
   npm install
   npm run build
   ```

4. **Environment setup:**
   ```bash
   cp .env.staging .env
   php artisan key:generate
   ```

5. **Database setup:**
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=StagingSeeder
   ```

6. **Configure web server:**
   ```apache
   # /etc/apache2/sites-available/staging.thescent.com.conf
   <VirtualHost *:80>
       ServerName staging.thescent.com
       DocumentRoot /var/www/staging.thescent.com/public
       
       <Directory /var/www/staging.thescent.com/public>
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/staging.thescent.com-error.log
       CustomLog ${APACHE_LOG_DIR}/staging.thescent.com-access.log combined
   </VirtualHost>
   ```

7. **Enable site and restart Apache:**
   ```bash
   a2ensite staging.thescent.com.conf
   systemctl reload apache2
   ```

8. **Set up SSL with Certbot:**
   ```bash
   certbot --apache -d staging.thescent.com
   ```

### Production Environment

Production deployment uses Laravel Forge for simplified server management:

1. **Create a server in Laravel Forge:**
   - Select AWS as the provider
   - Choose Ubuntu 24.04 LTS
   - Configure server resources (recommended: 4GB RAM, 2 CPUs minimum)

2. **Create a new site in Forge:**
   - Domain: thescent.com
   - PHP version: 8.3
   - Repository: GitHub repository URL
   - Branch: main

3. **Configure environment variables in Forge:**
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://thescent.com
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=thescent
   DB_USERNAME=forge
   DB_PASSWORD=********
   CACHE_DRIVER=redis
   SESSION_DRIVER=redis
   QUEUE_CONNECTION=redis
   ```

4. **Set up deployment script:**
   ```bash
   cd /home/forge/thescent.com
   git pull origin main
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan storage:link
   ```

5. **Configure SSL certificate:**
   - Use the "Let's Encrypt" option in Forge

6. **Set up queue workers:**
   - Configure Forge to run Laravel queues as a daemon

7. **Configure Redis:**
   - Enable Redis in Forge
   - Configure proper persistence and memory settings

8. **Set up backups:**
   - Configure automated database backups
   - Configure file system backups

### CI/CD Pipeline

The CI/CD pipeline uses GitHub Actions:

```yaml
# .github/workflows/deploy.yml
name: Deploy

on:
  push:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: php artisan test

  deploy-staging:
    needs: test
    if: github.ref == 'refs/heads/develop'
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to staging
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.STAGING_HOST }}
          username: ${{ secrets.STAGING_USERNAME }}
          key: ${{ secrets.STAGING_SSH_KEY }}
          script: |
            cd /var/www/staging.thescent.com
            git pull origin develop
            composer install --no-dev
            npm ci
            npm run build
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache

  deploy-production:
    needs: test
    if: github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to production using Forge
        uses: fjogeleit/http-request-action@master
        with:
          url: ${{ secrets.FORGE_DEPLOYMENT_WEBHOOK }}
          method: 'POST'
```

## Security Considerations

### Authentication Security

- Secure password hashing with Bcrypt
- Login throttling to prevent brute force attacks
- Remember-me cookie security with strict expiration
- Two-factor authentication option for user accounts
- Secure password reset process

### Data Protection

- HTTPS enforcement across all pages
- Proper handling of sensitive data (payment information)
- Data encryption at rest for sensitive fields
- Privacy-focused data retention policies
- GDPR compliance for EU customers

### CSRF Protection

- Laravel's CSRF protection enabled for all forms
- CSRF tokens for all state-changing requests
- SameSite cookie attributes set to 'Lax'
- X-Frame-Options headers to prevent clickjacking

### Input Validation

- Form Request validation for all user inputs
- Sanitization of user-generated content
- Strict typing and data validation
- Protection against SQL injection via Eloquent
- HTML purification for rich text inputs

### API Security

- Token-based authentication with Laravel Sanctum
- Token rotation policies
- API rate limiting
- Scope-based permissions for API access
- Validation of all API inputs

## Performance Optimization

### Caching Strategies

- Page caching for static and semi-static content
- Redis cache for session and application data
- Model caching for frequently accessed data
- API response caching with proper cache headers
- Cache tags for granular cache invalidation

### Database Optimization

- Optimized database indexing
- Eager loading to prevent N+1 query problems
- Query optimization and monitoring
- Database connection pooling
- Read/write splitting for high-traffic scenarios

### Asset Optimization

- JavaScript and CSS bundling and minification
- Image optimization with WebP support
- Lazy loading of images and non-critical resources
- Static asset caching with appropriate cache headers
- Critical CSS inlining for faster initial render

### CDN Integration

- Cloudflare CDN for static assets
- Edge caching of appropriate responses
- Geographic distribution of assets
- CDN-backed image resizing
- Asset versioning for cache busting

## Maintenance and Updates

### Versioning Strategy

The Scent platform follows Semantic Versioning (SemVer):

- **MAJOR** version for incompatible API changes
- **MINOR** version for backwards-compatible functionality
- **PATCH** version for backwards-compatible bug fixes

### Backup Procedures

- Daily automated database backups
- Hourly transaction log backups
- Weekly full system backups
- Offsite backup storage
- Regular backup restoration testing

### Update Process

1. **Pre-update checks:**
   - Review release notes
   - Back up database and files
   - Check for breaking changes

2. **Development environment:**
   - Pull latest changes
   - Run migrations
   - Run tests
   - Check for deprecation notices

3. **Staging environment:**
   - Deploy updated code
   - Run migrations
   - Perform UAT testing
   - Verify performance

4. **Production deployment:**
   - Schedule maintenance window if necessary
   - Deploy using zero-downtime techniques when possible
   - Monitor closely after deployment
   - Have rollback plan ready

### Monitoring

- Application performance monitoring with New Relic
- Error tracking with Sentry
- Server monitoring with Datadog
- Database performance monitoring
- Real user monitoring for frontend performance

## Contributing

We welcome contributions to The Scent platform! Please follow these guidelines:

### Code Standards

- Follow PSR-12 coding standards
- Use Laravel naming conventions
- Document code with PHPDoc comments
- Write descriptive commit messages

### Development Workflow

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Pull Request Process

1. Ensure all tests pass
2. Update documentation as needed
3. Have at least one core team member review your changes
4. Squash commits before merging

## License

The Scent platform is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

© 2025 The Scent. All rights reserved.
