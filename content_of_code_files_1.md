# index.php  
```php
<?php
echo '<!-- DEBUG: index.php loaded -->';
define('ROOT_PATH', __DIR__);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/SecurityMiddleware.php';
require_once __DIR__ . '/includes/ErrorHandler.php';

// Initialize error handling
ErrorHandler::init();

// Apply security middleware
SecurityMiddleware::apply();

try {
    // Handle routing
    $page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string');
    $action = SecurityMiddleware::validateInput($_GET['action'] ?? 'index', 'string');
    
    // Validate CSRF token for POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        SecurityMiddleware::validateCSRF();
    }
    
    // Route to appropriate controller/action
    switch ($page) {
        case 'home':
            require_once __DIR__ . '/controllers/ProductController.php';
            $productController = new ProductController($pdo);
            $productController->showHomePage();
            break;
        case 'product':
            require_once __DIR__ . '/controllers/ProductController.php';
            $productController = new ProductController($pdo);
            $productController->showProduct($_GET['id'] ?? null);
            break;
        case 'products':
            require_once __DIR__ . '/controllers/ProductController.php';
            $productController = new ProductController($pdo);
            $productController->showProductList();
            break;
        case 'cart':
            require_once __DIR__ . '/controllers/CartController.php';
            $controller = new CartController($pdo);
            
            if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                // AJAX Add to Cart endpoint
                $controller->addToCart();
                // jsonResponse will exit
            }
            
            $cartItems = $controller->getCartItems();
            require_once __DIR__ . '/views/cart.php';
            break;
            
        case 'checkout':
            if (!isLoggedIn()) {
                $_SESSION['redirect_after_login'] = 'checkout';
                header('Location: index.php?page=login');
                exit;
            }
            
            require_once __DIR__ . '/controllers/CheckoutController.php';
            $controller = new CheckoutController($pdo);
            
            if ($action === 'process') {
                $controller->processCheckout($_POST);
            } else {
                $cartItems = (new CartController($pdo))->getCartItems();
                if (empty($cartItems)) {
                    header('Location: index.php?page=cart');
                    exit;
                }
                require_once __DIR__ . '/views/checkout.php';
            }
            break;
            
        case 'register':
            if (isLoggedIn()) {
                header('Location: index.php?page=account');
                exit;
            }
            require_once __DIR__ . '/controllers/AccountController.php';
            $controller = new AccountController($pdo);
            
            if ($action === 'submit') {
                $controller->register();
            } else {
                require_once __DIR__ . '/views/register.php';
            }
            break;
            
        case 'quiz':
            require_once __DIR__ . '/controllers/QuizController.php';
            $controller = new QuizController($pdo);
            
            if ($action === 'submit') {
                $results = $controller->processQuiz($_POST);
                require_once __DIR__ . '/views/quiz_results.php';
            } else {
                $questions = $controller->getQuestions();
                require_once __DIR__ . '/views/quiz.php';
            }
            break;
            
        case 'admin':
            require_once __DIR__ . '/includes/auth.php';
            if (!isAdmin()) {
                header('Location: index.php?page=login');
                exit;
            }

            $section = SecurityMiddleware::validateInput($_GET['section'] ?? 'dashboard', 'string');
            
            switch ($section) {
                case 'quiz_analytics':
                    require_once __DIR__ . '/controllers/QuizController.php';
                    $controller = new QuizController($pdo);
                    $controller->getAnalytics();
                    break;
                    
                default:
                    require_once __DIR__ . '/views/admin/dashboard.php';
                    break;
            }
            break;

        case 'contact':
            $pageTitle = 'Contact Us';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            require_once __DIR__ . '/views/contact.php';
            break;
        case 'faq':
            $pageTitle = 'FAQs';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            require_once __DIR__ . '/views/faq.php';
            break;
        case 'shipping':
            $pageTitle = 'Shipping & Returns';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            require_once __DIR__ . '/views/shipping.php';
            break;
        case 'order-tracking':
            $pageTitle = 'Track Your Order';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            require_once __DIR__ . '/views/order-tracking.php';
            break;
        case 'privacy':
            $pageTitle = 'Privacy Policy';
            $csrfToken = SecurityMiddleware::generateCSRFToken();
            require_once __DIR__ . '/views/privacy.php';
            break;
            
        default:
            http_response_code(404);
            require_once __DIR__ . '/views/404.php';
            break;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    throw new Exception('A database error occurred');
} catch (Exception $e) {
    // ErrorHandler will catch and handle the exception
    throw $e;
}

```

# config.php  
```php
<?php
// Environment
define('ENVIRONMENT', getenv('APP_ENV') ?: 'production');

// Security Settings
define('SECURITY_SETTINGS', [
    'session' => [
        'lifetime' => 3600,
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
        'regenerate_id_interval' => 900 // 15 minutes
    ],
    'rate_limiting' => [
        'enabled' => true,
        'default_window' => 3600,
        'default_max_requests' => 100,
        'ip_whitelist' => [], // Add trusted IPs here
        'endpoints' => [
            'login' => ['window' => 300, 'max_requests' => 5],
            'reset_password' => ['window' => 3600, 'max_requests' => 3],
            'register' => ['window' => 3600, 'max_requests' => 5]
        ]
    ],
    'encryption' => [
        'algorithm' => 'AES-256-CBC',
        'key_length' => 32
    ],
    'password' => [
        'min_length' => 12,
        'require_special' => true,
        'require_number' => true,
        'require_mixed_case' => true,
        'max_attempts' => 5,
        'lockout_duration' => 900
    ],
    'logging' => [
        'security_log' => __DIR__ . '/logs/security.log',
        'error_log' => __DIR__ . '/logs/error.log',
        'audit_log' => __DIR__ . '/logs/audit.log',
        'rotation_size' => 10485760, // 10MB
        'max_files' => 10
    ],
    'cors' => [
        'allowed_origins' => ['https://the-scent.com'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
        'allowed_headers' => ['Content-Type', 'Authorization'],
        'expose_headers' => ['X-Request-ID'],
        'max_age' => 3600
    ],
    'csrf' => [
        'enabled' => true,
        'token_length' => 32,
        'token_lifetime' => 3600
    ],
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://js.stripe.com 'unsafe-inline'; style-src 'self' 'unsafe-inline'; frame-src https://js.stripe.com; img-src 'self' data: https:; connect-src 'self' https://api.stripe.com",
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'
    ],
    'file_upload' => [
        'max_size' => 5242880, // 5MB
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf'
        ],
        'scan_malware' => true
    ]
]);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'the_scent');
define('DB_USER', 'scent_user');
define('DB_PASS', 'StrongPassword123');
define('BASE_URL', '/');

// Stripe Configuration
define('STRIPE_PUBLIC_KEY', 'pk_test_your_stripe_public_key');
define('STRIPE_SECRET_KEY', 'sk_test_your_stripe_secret_key');
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_stripe_webhook_secret');

// Email Configuration (for next phase)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_email_app_password');
define('SMTP_FROM', 'noreply@thescent.com');
define('SMTP_FROM_NAME', 'The Scent');

// Application Settings
define('TAX_RATE', 0.10); // 10% tax rate
define('FREE_SHIPPING_THRESHOLD', 50.00); // Free shipping on orders over $50
define('SHIPPING_COST', 5.99); // Standard shipping cost

// Error Logging Configuration
define('ERROR_LOG_PATH', '/var/log/thescent/');
define('ERROR_LOG_LEVEL', E_ALL);

// Quiz Configuration
define('QUIZ_MAX_ATTEMPTS', 3);
define('QUIZ_RESULT_EXPIRY_DAYS', 30);
define('RECOMMENDATION_LIMIT', 5);

```

# includes/db.php  
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display in production

if (!file_exists(__DIR__ . '/../config.php')) {
    error_log("Config file not found");
    require_once __DIR__ . '/../views/error.php';
    exit();
}

require_once __DIR__ . '/../config.php';

if (!defined('DB_HOST')) {
    error_log("DB_HOST not defined");
    require_once __DIR__ . '/../views/error.php';
    exit();
}

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    require_once __DIR__ . '/../views/error.php';
    exit();
}

```

# views/home.php  
```php
<?php 
// home.php (v3 - Merged UI Fixes + Preserved Functionality)
require_once __DIR__ . '/layout/header.php'; // Uses header-fixed.php content implicitly
echo '<!-- DEBUG: home.php loaded -->';
$delay = 0; // Initialize delay counter for animations
?>
<!-- Output CSRF token for JS (for AJAX add-to-cart/newsletter) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

<!-- Hero Section with Video Background -->
<section class="hero-section relative min-h-screen flex items-center justify-center overflow-hidden">
    <div id="particles-js" class="absolute inset-0 z-0"></div>
    <video class="absolute inset-0 w-full h-full object-cover z-[-1]" autoplay muted loop playsinline poster="/images/scent5.jpg">
        <source src="/videos/hero.mp4" type="video/mp4">
        <img src="/images/scent5.jpg" alt="Calming Nature" class="w-full h-full object-cover" />
    </video>
    <div class="absolute inset-0 bg-gradient-to-br from-primary/40 to-primary-dark/50 z-10"></div>
    <div class="container relative z-20 flex flex-col items-center justify-center text-center text-white px-6">
        <div data-aos="fade-down">
            <h1 class="text-4xl md:text-5xl font-bold mb-6 font-heading" style="text-shadow: 0 2px 4px rgba(0,0,0,0.7);">Find Your Moment of Calm</h1>
            <p class="text-lg md:text-xl mb-8 max-w-2xl mx-auto font-body">Experience premium, natural aromatherapy crafted to enhance well-being and restore balance.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#featured-products" class="btn btn-primary">Explore Our Collections</a>
            </div>
        </div>
    </div>
</section>

<!-- About/Mission Section (Keep existing) -->
<section class="about-section py-20 bg-white" id="about">
    <div class="container">
        <div class="about-container grid md:grid-cols-2 gap-12 items-center">
            <div class="about-image" data-aos="fade-left">
                <img src="<?= file_exists($_SERVER['DOCUMENT_ROOT'] . '/images/about.jpg') ? '/images/about.jpg' : 'https://placehold.co/800x600/e9ecef/495057?text=About+The+Scent' ?>"
                     alt="About The Scent" 
                     class="rounded-lg shadow-xl w-full">
            </div>
            <div class="about-content" data-aos="fade-right">
                <h2 class="text-3xl font-bold mb-6">Rooted in Nature, Crafted with Care</h2>
                <p class="mb-6">At The Scent, we harness the power of nature to nurture your mental and physical well-being. Our high-quality, sustainably sourced ingredients are transformed into exquisite aromatherapy products by expert hands.</p>
                <p class="mb-6">Our unique and creative formulations are crafted with expertise to create harmonious, balanced, and well-rounded aromatherapy products that enhance both mental and physical health.</p>
                <a href="index.php?page=about" class="btn btn-secondary">Learn Our Story</a>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products Section (Merged UI) -->
<section class="featured-section py-16 bg-light" id="featured-products">
    <div class="container mx-auto text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-12" data-aos="fade-up">Featured Collections</h2>
        <div class="featured-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 px-6">
            <?php if (!empty($featuredProducts)): ?>
                <?php foreach ($featuredProducts as $product): ?>
                    <!-- Apply suggested card structure/style -->
                    <div class="product-card sample-card" data-aos="zoom-in" style="border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.05); overflow:hidden;">
                        <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             class="w-full h-64 object-cover" loading="lazy">
                        <div class="product-info" style="padding:1.5rem; text-align:center;">
                            <h3 style="margin-bottom:0.5rem; font-size:1.3rem;"><?= htmlspecialchars($product['name']) ?></h3>
                            
                            <!-- Apply suggested short description / category display logic -->
                            <?php if (!empty($product['short_description'])): ?>
                                <p style="font-size:0.9rem; color:#666; margin-bottom:1rem;"><?= htmlspecialchars($product['short_description']) ?></p>
                            <?php elseif (!empty($product['category_name'])): ?>
                                <p style="font-size:0.9rem; color:#666; margin-bottom:1rem;"><?= htmlspecialchars($product['category_name']) ?></p>
                            <?php endif; ?>

                            <!-- *** Re-integrate existing actions to preserve functionality *** -->
                            <div class="product-actions flex gap-2 justify-center mt-4">
                                <a href="index.php?page=product&id=<?= $product['id'] ?>" class="btn btn-primary">View Details</a> 
                                <?php if (isset($product['stock_quantity']) && $product['stock_quantity'] > 0): ?>
                                    <button class="btn btn-secondary add-to-cart" 
                                            data-product-id="<?= $product['id'] ?>"
                                            <?= isset($product['low_stock_threshold']) && $product['stock_quantity'] <= $product['low_stock_threshold'] ? 'data-low-stock="true"' : '' ?>>
                                        Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-disabled" disabled>Out of Stock</button>
                                <?php endif; ?>
                            </div>
                            <!-- *** End of re-integrated actions *** -->
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center text-gray-600">
                    <p>Discover our curated collection of premium aromatherapy products.</p>
                    <a href="index.php?page=products" class="inline-block mt-4 text-primary hover:underline">Browse All Products</a>
                </div>
            <?php endif; ?>
        </div>
        <!-- Apply suggested "Shop All" CTA below grid -->
        <div class="view-all-cta" style="text-align:center; margin-top:3rem;">
            <a href="index.php?page=products" class="btn btn-primary">Shop All Products</a>
        </div>
    </div>
</section>

<!-- Benefits Section (Keep existing) -->
<section class="py-20 bg-white">
    <div class="container">
        <h2 class="text-3xl font-bold text-center mb-12" data-aos="fade-up">Why Choose The Scent</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="0">
                <i class="fas fa-leaf text-4xl text-primary mb-4"></i>
                <h3 class="text-xl font-semibold mb-4">Natural Ingredients</h3>
                <p>Premium quality raw materials sourced from around the world.</p>
            </div>
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-heart text-4xl text-primary mb-4"></i>
                <h3 class="text-xl font-semibold mb-4">Wellness Focus</h3>
                <p>Products designed to enhance both mental and physical well-being.</p>
            </div>
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-certificate text-4xl text-primary mb-4"></i>
                <h3 class="text-xl font-semibold mb-4">Expert Crafted</h3>
                <p>Unique formulations created by aromatherapy experts.</p>
            </div>
        </div>
    </div>
</section>

<!-- Quiz/Finder Section (Keep existing) -->
<section class="quiz-section py-20 bg-light" id="finder">
    <div class="container">
        <h2 class="text-3xl font-bold text-center mb-8" data-aos="fade-up">Discover Your Perfect Scent</h2>
        <p class="text-center mb-12 text-lg" data-aos="fade-up" data-aos-delay="100">Tailor your aromatherapy experience to your mood and needs.</p>
        <div class="grid md:grid-cols-5 gap-6 mb-8 finder-grid">
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="0">
                <i class="fas fa-leaf text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Relaxation</h3>
                <p class="text-sm text-gray-600 text-center">Calming scents to help you unwind.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-bolt text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Energy</h3>
                <p class="text-sm text-gray-600 text-center">Invigorating aromas to uplift your day.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-brain text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Focus</h3>
                <p class="text-sm text-gray-600 text-center">Clarifying blends for a clear mind.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="300">
                <i class="fas fa-moon text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Sleep</h3>
                <p class="text-sm text-gray-600 text-center">Soothing scents for a peaceful night's rest.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="400">
                <i class="fas fa-balance-scale text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Balance</h3>
                <p class="text-sm text-gray-600 text-center">Harmonious aromas to center you.</p>
            </div>
        </div>
        <div class="text-center" data-aos="fade-up" data-aos-delay="500">
            <a href="index.php?page=quiz" class="btn btn-secondary">Take the Full Scent Quiz</a>
        </div>
    </div>
</section>

<!-- Newsletter Section (Merged UI) -->
<section class="newsletter-section py-20 bg-light" id="newsletter">
    <div class="container">
        <div class="max-w-2xl mx-auto text-center" data-aos="fade-up">
            <h2 class="text-3xl font-bold mb-6">Stay Connected</h2>
            <p class="mb-8">Subscribe to receive updates, exclusive offers, and aromatherapy tips.</p>
            <!-- Apply suggested form structure/style -->
            <form id="newsletter-form" class="newsletter-form flex flex-col sm:flex-row gap-4 justify-center">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="email" name="email" placeholder="Enter your email" required class="newsletter-input flex-1 px-4 py-2 rounded-full border border-gray-300 focus:border-primary">
                <button type="submit" class="btn btn-primary newsletter-btn">Subscribe</button>
            </form>
            <p class="newsletter-consent" style="font-size:0.8rem;opacity:0.7; margin-top:1rem;">By subscribing, you agree to our <a href="index.php?page=privacy" style="color:#A0C1B1;text-decoration:underline;">Privacy Policy</a>.</p>
        </div>
    </div>
</section>

<script>
    // Newsletter form submission
    const newsletterForm = document.getElementById('newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const response = await fetch('index.php?page=newsletter&action=subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(formData)
                });
                const data = await response.json();
                if (data.success) {
                    showFlashMessage(data.message || 'Thank you for subscribing!', 'success');
                    newsletterForm.querySelector('input[type="email"]').value = '';
                    newsletterForm.querySelector('button').disabled = true;
                } else {
                    showFlashMessage(data.message || 'Subscription failed', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showFlashMessage('Subscription failed', 'error');
            }
        });
    }
</script>

<!-- Testimonials Section (Keep existing) -->
<section class="py-20 bg-white" id="testimonials">
    <div class="container">
        <h2 class="text-3xl font-bold text-center mb-12" data-aos="fade-up">What Our Community Says</h2>
        <div class="testimonial-grid grid md:grid-cols-3 gap-8">
            <div class="testimonial-card bg-light p-8 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="0">
                <p class="mb-4 italic">"The Lavender Essential Oil transformed my bedtime routine—its calming aroma truly helps me unwind."</p>
                <span class="block font-semibold mb-2">- Sarah L., Los Angeles</span>
                <div class="text-accent text-lg">★★★★★</div>
            </div>
            <div class="testimonial-card bg-light p-8 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="100">
                <p class="mb-4 italic">"The Focus Blend oil improved my concentration at home without overwhelming my senses."</p>
                <span class="block font-semibold mb-2">- Michael T., Chicago</span>
                <div class="text-accent text-lg">★★★★★</div>
            </div>
            <div class="testimonial-card bg-light p-8 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="200">
                <p class="mb-4 italic">"Handcrafted soaps that feel divine and truly nourish sensitive skin. A luxurious experience."</p>
                <span class="block font-semibold mb-2">- Emma R., Seattle</span>
                <div class="text-accent text-lg">★★★★★</div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; // Uses footer-fixed.php content implicitly ?>

```

# views/layout/header.php  
```php
<?php
require_once __DIR__ . '/../../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'The Scent - Premium Aromatherapy Products' ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Montserrat:wght@400;500;600&family=Raleway:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1A4D5A',
                        'primary-dark': '#164249',
                        secondary: '#A0C1B1',
                        accent: '#D4A76A'
                    },
                    fontFamily: {
                        heading: ['Cormorant Garamond', 'serif'],
                        body: ['Montserrat', 'sans-serif'],
                        accent: ['Raleway', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="/css/style.css">
    <!-- Scripts: Only mobile menu JS here, remove AOS/Particles.js init from header -->
    <script>
        // Initialize mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const mobileMenu = document.getElementById('mobile-menu');
            let isMenuOpen = false;

            function toggleMenu() {
                isMenuOpen = !isMenuOpen;
                mobileMenu.classList.toggle('active');
                document.body.classList.toggle('menu-open');
                mobileMenuToggle.innerHTML = isMenuOpen ? 
                    '<i class="fas fa-times"></i>' : 
                    '<i class="fas fa-bars"></i>';
            }

            function closeMenu() {
                if (isMenuOpen) {
                    isMenuOpen = false;
                    mobileMenu.classList.remove('active');
                    document.body.classList.remove('menu-open');
                    mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                }
            }

            mobileMenuToggle?.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleMenu();
            });

            document.addEventListener('click', function(e) {
                if (isMenuOpen && !e.target.closest('.main-nav')) {
                    closeMenu();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && isMenuOpen) {
                    closeMenu();
                }
            });

            mobileMenu?.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', closeMenu);
            });
        });
    </script>
</head>
<body>
    <header>
        <nav class="main-nav sample-header">
            <div class="container header-container">
                <div class="logo">
                    <a href="index.php" style="text-transform:uppercase; letter-spacing:1px;">The Scent</a>
                    <span style="display:block; font-family:'Raleway',sans-serif; font-size:0.7rem; letter-spacing:2px; text-transform:uppercase; color:#A0C1B1; margin-top:-5px; opacity:0.8;">AROMATHERAPY</span>
                </div>
                <div class="nav-links" id="mobile-menu">
                    <a href="index.php">Home</a>
                    <a href="index.php?page=products">Shop</a>
                    <a href="index.php?page=quiz">Scent Finder</a>
                    <a href="index.php?page=about">About</a>
                    <a href="index.php?page=contact">Contact</a>
                </div>
                <div class="header-icons">
                    <a href="#" aria-label="Search"><i class="fas fa-search"></i></a>
                    <?php if (isLoggedIn()): ?>
                        <a href="index.php?page=account" aria-label="Account"><i class="fas fa-user"></i></a>
                    <?php else: ?>
                        <a href="index.php?page=login" aria-label="Login"><i class="fas fa-user"></i></a>
                    <?php endif; ?>
                    <a href="index.php?page=cart" class="cart-link" aria-label="Cart">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="cart-count"><?= isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : 0 ?></span>
                    </a>
                </div>
                <button class="mobile-menu-toggle md:hidden" aria-label="Toggle Menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>
    </header>
    <main>
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="flash-message <?= $_SESSION['flash_type'] ?? 'info' ?>">
                <?= $_SESSION['flash_message'] ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

```

# views/layout/footer.php  
```php
</main>
    <footer>
        <div class="container">
            <div class="footer-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:3rem;margin-bottom:3rem;">
                <div class="footer-about">
                    <h3>About The Scent</h3>
                    <p>Creating premium aromatherapy products to enhance mental and physical well-being through the power of nature.</p>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Pinterest"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                <div class="footer-links">
                    <h3>Shop</h3>
                    <ul>
                        <li><a href="index.php?page=products">Essential Oils</a></li>
                        <li><a href="index.php?page=products">Natural Soaps</a></li>
                        <li><a href="index.php?page=products">Gift Sets</a></li>
                        <li><a href="index.php?page=products">New Arrivals</a></li>
                        <li><a href="index.php?page=products">Bestsellers</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h3>Help</h3>
                    <ul>
                        <li><a href="index.php?page=contact">Contact Us</a></li>
                        <li><a href="index.php?page=faq">FAQs</a></li>
                        <li><a href="index.php?page=shipping">Shipping & Returns</a></li>
                        <li><a href="index.php?page=order-tracking">Track Your Order</a></li>
                        <li><a href="index.php?page=privacy">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Aromatherapy Lane, Wellness City, WB 12345</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> hello@thescent.com</p>
                    <form id="newsletter-form-footer" class="newsletter-form" style="margin-top:1rem;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="email" name="email" placeholder="Enter your email" required class="newsletter-input">
                        <button type="submit" class="btn btn-primary newsletter-btn">Subscribe</button>
                    </form>
                    <p class="newsletter-consent" style="font-size:0.8rem;opacity:0.7; margin-top:1rem;">By subscribing, you agree to our <a href="index.php?page=privacy" style="color:#A0C1B1; text-decoration:underline;">Privacy Policy</a> and consent to receive emails from The Scent.</p>
                </div>
            </div>
            <div class="footer-bottom" style="background-color:#222b2e; padding:1.5rem 0; margin-top:2rem;">
                <div class="container" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;font-size:0.85rem;">
                    <p>&copy; <?= date('Y') ?> The Scent. All rights reserved.</p>
                    <div class="payment-methods" style="display:flex;align-items:center;gap:0.8rem;">
                        <span>Accepted Payments:</span>
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-paypal"></i>
                        <i class="fab fa-cc-amex"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        // Initialize AOS
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                offset: 100,
                once: true
            });

            if (document.getElementById('particles-js')) {
                particlesJS.load('particles-js', '/particles.json');
            }
        });

        // Canonical flash message helper
        window.showFlashMessage = function(message, type = 'info') {
            let flashContainer = document.querySelector('.flash-message-container');
            if (!flashContainer) {
                flashContainer = document.createElement('div');
                flashContainer.className = 'flash-message-container fixed top-5 right-5 z-[1100] max-w-sm w-full';
                document.body.appendChild(flashContainer);
            }
            const flashDiv = document.createElement('div');
            const colorMap = {
                success: 'bg-green-100 border-green-400 text-green-700',
                error: 'bg-red-100 border-red-400 text-red-700',
                info: 'bg-blue-100 border-blue-400 text-blue-700',
                warning: 'bg-yellow-100 border-yellow-400 text-yellow-700'
            };
            flashDiv.className = `flash-message border px-4 py-3 rounded relative shadow-md mb-2 ${colorMap[type] || colorMap['info']}`;
            flashDiv.setAttribute('role', 'alert');
            const messageSpan = document.createElement('span');
            messageSpan.className = 'block sm:inline';
            messageSpan.textContent = message;
            flashDiv.appendChild(messageSpan);
            const closeButton = document.createElement('span');
            closeButton.className = 'absolute top-0 bottom-0 right-0 px-4 py-3';
            closeButton.innerHTML = '&times;';
            closeButton.onclick = () => flashDiv.remove();
            flashDiv.appendChild(closeButton);
            flashContainer.appendChild(flashDiv);
            setTimeout(() => {
                if (flashDiv) {
                    flashDiv.style.opacity = '0';
                    flashDiv.style.transition = 'opacity 0.5s ease-out';
                    setTimeout(() => flashDiv.remove(), 500);
                }
            }, 5000);
        };

        // Canonical Add-to-Cart handler (event delegation)
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.add-to-cart');
            if (!btn) return;
            e.preventDefault();
            if (btn.disabled) return;
            const productId = btn.dataset.productId;
            // Always get CSRF token from #csrf-token-value (for products page)
            let csrfToken = '';
            const csrfTokenInput = document.getElementById('csrf-token-value');
            if (csrfTokenInput) {
                csrfToken = csrfTokenInput.value;
            } else {
                // fallback for other pages
                const legacyInput = document.querySelector('input[name="csrf_token"]');
                csrfToken = legacyInput ? legacyInput.value : '';
            }
            if (!csrfToken) {
                showFlashMessage('Security token missing. Please refresh.', 'error');
                return;
            }
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = 'Adding...';
            fetch('index.php?page=cart&action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${encodeURIComponent(productId)}&quantity=1&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                        cartCount.style.display = data.cart_count > 0 ? 'inline' : 'none';
                    }
                    showFlashMessage(data.message || 'Product added to cart', 'success');
                    if (data.stock_status === 'out_of_stock') {
                        btn.disabled = true;
                        btn.classList.remove('btn-secondary');
                        btn.classList.add('btn-disabled');
                        btn.textContent = 'Out of Stock';
                    } else if (data.stock_status === 'low_stock') {
                        showFlashMessage('Limited quantity available', 'info');
                        btn.dataset.lowStock = 'true';
                        btn.textContent = originalText;
                        btn.disabled = false;
                    } else {
                        btn.textContent = originalText;
                        btn.disabled = false;
                    }
                } else {
                    showFlashMessage(data.message || 'Error adding to cart', 'error');
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFlashMessage('Error adding to cart', 'error');
                btn.textContent = originalText;
                btn.disabled = false;
            });
        });
    </script>
</body>
</html>

```

# views/cart.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>
<!-- Output CSRF token for JS (for AJAX cart actions) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

<section class="cart-section">
    <div class="container">
        <div class="cart-container" data-aos="fade-up">
            <h1>Your Shopping Cart</h1>
            
            <?php if (empty($cartItems)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty</p>
                    <a href="index.php?page=products" class="btn-primary">Continue Shopping</a>
                </div>
            <?php else: ?>
                <form id="cartForm" action="index.php?page=cart&action=update" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="cart-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item" data-product-id="<?= $item['product']['id'] ?>">
                                <div class="item-image">
                                    <img src="<?= htmlspecialchars($item['product']['image_url']) ?>" 
                                         alt="<?= htmlspecialchars($item['product']['name']) ?>">
                                </div>
                                <div class="item-details">
                                    <h3><?= htmlspecialchars($item['product']['name']) ?></h3>
                                    <p class="item-price">$<?= number_format($item['product']['price'], 2) ?></p>
                                </div>
                                <div class="item-quantity">
                                    <button type="button" class="quantity-btn minus">-</button>
                                    <input type="number" name="updates[<?= $item['product']['id'] ?>]" 
                                           value="<?= $item['quantity'] ?>" min="1" max="99">
                                    <button type="button" class="quantity-btn plus">+</button>
                                </div>
                                <div class="item-subtotal">
                                    $<?= number_format($item['subtotal'], 2) ?>
                                </div>
                                <button type="button" class="remove-item" 
                                        data-product-id="<?= $item['product']['id'] ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>
                        <div class="summary-row shipping">
                            <span>Shipping:</span>
                            <span>FREE</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>
                    </div>
                    
                    <div class="cart-actions">
                        <button type="submit" class="btn-secondary update-cart">Update Cart</button>
                        <a href="index.php?page=checkout" class="btn-primary checkout">Proceed to Checkout</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cartForm = document.getElementById('cartForm');
    
    // Handle quantity buttons
    document.querySelectorAll('.quantity-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            let value = parseInt(input.value);
            
            if (this.classList.contains('plus')) {
                if (value < 99) input.value = value + 1;
            } else {
                if (value > 1) input.value = value - 1;
            }
            
            // Trigger form change
            input.dispatchEvent(new Event('change'));
        });
    });
    
    // Handle quantity input changes
    document.querySelectorAll('.item-quantity input').forEach(input => {
        input.addEventListener('change', function() {
            updateCartItem(this.closest('.cart-item'));
        });
    });
    
    // Handle remove item buttons
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            fetch('index.php?page=cart&action=remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.closest('.cart-item').remove();
                    updateCartTotal();
                    updateCartCount(data.cartCount);
                    if (data.cartCount === 0) {
                        location.reload();
                    }
                    showFlashMessage(data.message || 'Product removed from cart', 'success');
                } else {
                    showFlashMessage(data.message || 'Error removing item', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFlashMessage('Error removing item', 'error');
            });
        });
    });
    
    // Update cart total
    function updateCartTotal() {
        let total = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            const price = parseFloat(item.querySelector('.item-price').textContent.replace('$', ''));
            const quantity = parseInt(item.querySelector('.item-quantity input').value);
            total += price * quantity;
            
            // Update item subtotal
            item.querySelector('.item-subtotal').textContent = 
                '$' + (price * quantity).toFixed(2);
        });
        
        // Update summary totals
        document.querySelector('.summary-row.total span:last-child').textContent = 
            '$' + total.toFixed(2);
    }
    
    // Update cart count in header
    function updateCartCount(count) {
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            if (count > 0) {
                cartCount.textContent = count;
                cartCount.style.display = 'inline';
            } else {
                cartCount.style.display = 'none';
            }
        }
    }
    
    // Handle form submission
    cartForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        fetch('index.php?page=cart&action=update', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartTotal();
                updateCartCount(data.cartCount);
                showFlashMessage(data.message || 'Cart updated', 'success');
            } else {
                showFlashMessage(data.message || 'Error updating cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showFlashMessage('Error updating cart', 'error');
        });
    });
});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

# controllers/BaseController.php  
```php
<?php
require_once __DIR__ . '/../includes/EmailService.php';

abstract class BaseController {
    protected $db;
    protected $securityMiddleware;
    protected $emailService;
    protected $responseHeaders = [];
    
    public function __construct($pdo) {
        $this->db = $pdo;
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->securityMiddleware = new SecurityMiddleware();
        $this->emailService = new EmailService();
        $this->initializeSecurityHeaders();
    }
    
    protected function initializeSecurityHeaders() {
        $this->responseHeaders = [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()'
        ];
    }
    
    protected function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        
        // Set security headers
        foreach ($this->responseHeaders as $header => $value) {
            header("$header: $value");
        }
        
        // Add CSRF token to responses that might lead to forms
        if ($this->shouldIncludeCSRFToken()) {
            $data['csrf_token'] = $this->securityMiddleware->generateCSRFToken();
        }
        
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($this->sanitizeOutput($data));
    }
    
    protected function sendError($message, $statusCode = 400, $context = []) {
        $errorResponse = [
            'error' => true,
            'message' => $message,
            'code' => $statusCode
        ];
        
        // Log error with context for monitoring
        ErrorHandler::logError($message, $context);
        
        // Only include debug info in development
        if (DEBUG_MODE && !empty($context)) {
            $errorResponse['debug'] = $context;
        }
        
        $this->sendResponse($errorResponse, $statusCode);
    }
    
    protected function validateRequest($rules) {
        $errors = [];
        $input = $this->getRequestInput();
        
        if (!is_array($rules) && !is_object($rules)) {
            // Defensive: if rules is not array/object, skip validation
            return true;
        }
        foreach ($rules as $field => $validations) {
            if (!isset($input[$field]) && strpos($validations, 'required') !== false) {
                $errors[$field] = "The {$field} field is required";
                continue;
            }
            
            if (isset($input[$field])) {
                $value = $input[$field];
                $validationArray = explode('|', $validations);
                
                foreach ($validationArray as $validation) {
                    if (!$this->validateField($value, $validation)) {
                        $errors[$field] = "The {$field} field failed {$validation} validation";
                    }
                }
            }
        }
        
        if (!empty($errors)) {
            $this->sendError('Validation failed', 422, ['validation_errors' => $errors]);
            return false;
        }
        
        return true;
    }
    
    protected function validateField($value, $rule) {
        switch ($rule) {
            case 'required':
                return !empty($value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL);
            case 'numeric':
                return is_numeric($value);
            case 'array':
                return is_array($value);
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL);
            // Add more validation rules as needed
        }
        
        // Check for min:x, max:x patterns
        if (preg_match('/^(min|max):(\d+)$/', $rule, $matches)) {
            $type = $matches[1];
            $limit = (int)$matches[2];
            
            if ($type === 'min') {
                return strlen($value) >= $limit;
            } else {
                return strlen($value) <= $limit;
            }
        }
        
        return true;
    }
    
    protected function getRequestInput() {
        $input = [];
        
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $input = $_GET;
                break;
            case 'POST':
            case 'PUT':
            case 'DELETE':
                $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
                
                if (strpos($contentType, 'application/json') !== false) {
                    $input = json_decode(file_get_contents('php://input'), true) ?? [];
                } else {
                    $input = $_POST;
                }
                break;
        }
        
        return $this->sanitizeInput($input);
    }
    
    protected function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        // Remove NULL bytes
        $data = str_replace(chr(0), '', $data);
        
        // Convert special characters to HTML entities
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    protected function sanitizeOutput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeOutput'], $data);
        }
        
        if (is_string($data)) {
            // Ensure proper UTF-8 encoding
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
        
        return $data;
    }
    
    protected function shouldIncludeCSRFToken() {
        $safeRoutes = [
            'login',
            'register',
            'password/reset',
            'checkout'
        ];
        
        $currentRoute = strtolower($_SERVER['REQUEST_URI']);
        
        foreach ($safeRoutes as $route) {
            if (strpos($currentRoute, $route) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function requireAuthentication() {
        if (!$this->securityMiddleware->isAuthenticated()) {
            $this->sendError('Unauthorized', 401);
            return false;
        }
        return true;
    }
    
    protected function requireCSRFToken() {
        if (!$this->securityMiddleware->validateCSRFToken()) {
            $this->sendError('Invalid CSRF token', 403);
            return false;
        }
        return true;
    }
    
    protected function rateLimit($key, $maxAttempts = 60, $decayMinutes = 1) {
        if (!$this->securityMiddleware->checkRateLimit($key, $maxAttempts, $decayMinutes)) {
            $this->sendError('Too many requests', 429);
            return false;
        }
        return true;
    }
    
    protected function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            $this->logSecurityEvent('unauthorized_access_attempt', [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'uri' => $_SERVER['REQUEST_URI']
            ]);
            $this->jsonResponse(['error' => 'Authentication required'], 401);
        }
        
        // Verify session integrity
        if (!$this->validateSessionIntegrity()) {
            $this->terminateSession('Session integrity check failed');
        }
        
        // Check session age and regenerate if needed
        if ($this->shouldRegenerateSession()) {
            $this->regenerateSession();
        }
    }
    
    protected function requireAdmin() {
        $this->requireLogin();
        
        if ($_SESSION['user_role'] !== 'admin') {
            $this->logSecurityEvent('unauthorized_admin_attempt', [
                'user_id' => $_SESSION['user_id'],
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            $this->jsonResponse(['error' => 'Admin access required'], 403);
        }
    }
    
    protected function validateInput($data, $rules) {
        $errors = [];
        if (!is_array($rules) && !is_object($rules)) {
            // Defensive: if rules is not array/object, skip validation
            return true;
        }
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field]) && $rule['required'] ?? false) {
                $errors[$field] = 'Field is required';
                continue;
            }
            
            if (isset($data[$field])) {
                $value = $data[$field];
                $error = $this->securityMiddleware->validateInput($value, $rule['type'], $rule);
                if ($error !== true) {
                    $errors[$field] = $error;
                }
            }
        }
        
        if (!empty($errors)) {
            $this->jsonResponse(['errors' => $errors], 422);
        }
        
        return true;
    }
    
    protected function getCsrfToken() {
        return SecurityMiddleware::generateCSRFToken();
    }
    
    protected function validateCSRF() {
        SecurityMiddleware::validateCSRF();
    }
    
    protected function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    protected function redirect($url, $statusCode = 302) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $url = BASE_URL . ltrim($url, '/');
        }
        
        header('Location: ' . $url, true, $statusCode);
        exit;
    }
    
    protected function setFlashMessage($message, $type = 'info') {
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type,
            'created' => time()
        ];
    }
    
    protected function getFlashMessage() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            
            // Ensure flash messages don't persist too long
            if (time() - $flash['created'] < 300) { // 5 minutes
                return $flash;
            }
        }
        return null;
    }
    
    protected function beginTransaction() {
        $this->db->beginTransaction();
    }
    
    protected function commit() {
        $this->db->commit();
    }
    
    protected function rollback() {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
    
    protected function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
    
    protected function getUserId() {
        return $_SESSION['user']['id'] ?? null;
    }
    
    protected function validateAjax() {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
        }
    }
    
    protected function isRateLimited($key, $maxAttempts, $timeWindow) {
        $rateLimitKey = "rate_limit:{$key}:" . $_SERVER['REMOTE_ADDR'];
        $attempts = $_SESSION[$rateLimitKey] ?? ['count' => 0, 'first_attempt' => time()];
        
        if (time() - $attempts['first_attempt'] > $timeWindow) {
            // Reset if time window has passed
            $attempts = ['count' => 1, 'first_attempt' => time()];
        } else {
            $attempts['count']++;
        }
        
        $_SESSION[$rateLimitKey] = $attempts;
        
        return $attempts['count'] > $maxAttempts;
    }
    
    protected function renderView($viewPath, $data = []) {
        // Extract data to make it available in view
        extract($data);
        
        // Start output buffering
        ob_start();
        
        $viewFile = __DIR__ . '/../views/' . $viewPath . '.php';
        if (!file_exists($viewFile)) {
            throw new Exception("View not found: {$viewPath}");
        }
        
        require $viewFile;
        
        return ob_get_clean();
    }
    
    protected function validateFileUpload($file, $allowedTypes, $maxSize = 5242880) { // 5MB default
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid file upload');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File too large');
            case UPLOAD_ERR_PARTIAL:
                throw new Exception('File upload interrupted');
            default:
                throw new Exception('Unknown upload error');
        }

        if ($file['size'] > $maxSize) {
            throw new Exception('File too large');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type');
        }

        return true;
    }
    
    protected function log($message, $level = 'info') {
        $logFile = __DIR__ . '/../logs/' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        error_log($formattedMessage, 3, $logFile);
    }

    protected function checkRateLimit($key, $limit = null, $window = null) {
        $limit = $limit ?? $this->rateLimit['max_requests'];
        $window = $window ?? $this->rateLimit['window'];
        
        $redis = RedisConnection::getInstance();
        $requests = $redis->incr("rate_limit:{$key}");
        
        if ($requests === 1) {
            $redis->expire("rate_limit:{$key}", $window);
        }
        
        return $requests <= $limit;
    }

    protected function logAuditTrail($action, $userId, $details = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_log (
                    action, user_id, ip_address, user_agent, details
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $action,
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                json_encode($details)
            ]);
        } catch (Exception $e) {
            error_log("Audit logging failed: " . $e->getMessage());
        }
    }

    private function validateSessionIntegrity() {
        if (!isset($_SESSION['user_agent']) || !isset($_SESSION['ip_address'])) {
            return false;
        }
        
        return $_SESSION['user_agent'] === $_SERVER['HTTP_USER_AGENT'] &&
               $_SESSION['ip_address'] === $_SERVER['REMOTE_ADDR'];
    }
    
    private function shouldRegenerateSession() {
        return !isset($_SESSION['last_regeneration']) ||
               (time() - $_SESSION['last_regeneration']) > SECURITY_SETTINGS['session']['regenerate_id_interval'];
    }
    
    private function regenerateSession() {
        $oldSession = $_SESSION;
        session_regenerate_id(true);
        $_SESSION = $oldSession;
        $_SESSION['last_regeneration'] = time();
    }
    
    protected function terminateSession($reason) {
        $userId = $_SESSION['user_id'] ?? null;
        $this->logSecurityEvent('session_terminated', [
            'reason' => $reason,
            'user_id' => $userId
        ]);
        
        session_destroy();
        $this->jsonResponse(['error' => 'Session terminated for security reasons'], 401);
    }
    
    protected function validateRateLimit($action) {
        $settings = SECURITY_SETTINGS['rate_limiting']['endpoints'][$action] ?? 
                   ['window' => SECURITY_SETTINGS['rate_limiting']['default_window'],
                    'max_requests' => SECURITY_SETTINGS['rate_limiting']['default_max_requests']];
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = "rate_limit:{$action}:{$ip}";
        
        // Check whitelist
        if (in_array($ip, SECURITY_SETTINGS['rate_limiting']['ip_whitelist'])) {
            return true;
        }
        
        $attempts = apcu_fetch($key) ?: 0;
        if ($attempts >= $settings['max_requests']) {
            $this->logSecurityEvent('rate_limit_exceeded', [
                'action' => $action,
                'ip' => $ip,
                'attempts' => $attempts
            ]);
            $this->jsonResponse(['error' => 'Rate limit exceeded'], 429);
        }
        
        apcu_inc($key, 1, $success, $settings['window']);
    }
    
    protected function validateCSRFToken() {
        if (!SECURITY_SETTINGS['csrf']['enabled']) {
            return true;
        }
        
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->logSecurityEvent('csrf_validation_failed', [
                'user_id' => $_SESSION['user_id'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            $this->jsonResponse(['error' => 'CSRF token validation failed'], 403);
        }
        
        return true;
    }
    
    protected function generateCSRFToken() {
        if (!SECURITY_SETTINGS['csrf']['enabled']) {
            return '';
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(SECURITY_SETTINGS['csrf']['token_length']));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    protected function logSecurityEvent($event, $details = []) {
        $details = array_merge($details, [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        error_log(
            sprintf(
                "[SECURITY] %s | %s",
                $event,
                json_encode($details)
            ),
            3,
            SECURITY_SETTINGS['logging']['security_log']
        );
    }
}
```

