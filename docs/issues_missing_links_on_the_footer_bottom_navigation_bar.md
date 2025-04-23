The following links on the footer (bottom) navigation current give the standard page not found error page (page_not_found_error.html). Please help to generate appropriate .php file to properly display the pages with the usual expect content. You can use popular or usual content as dummy content for each of the missing pages. Please help to generate each of the missing page as the corresponding .php file within ```php and ``` tags with the expected filename. Make sure that the .php files for the missing pages listed below give the consistent UI appearance as the main landing page (current_landing_page.html), invoking appropriate controller .php and layout .php files (e.g. header.php, footer.php, css/style.css, etc...)

Under "Help" column on the bottom navigation bar, the links are:

1. Contact Us : https://the-scent.com/index.php?page=contact  
2. FAQs : https://the-scent.com/index.php?page=faq
3. Shipping & Returns : https://the-scent.com/index.php?page=shipping
4. Track Your Order : https://the-scent.com/index.php?page=order-tracking
5. Privacy Policy : https://the-scent.com/index.php?page=privacy

You can use the analysis and suggestions below as your guide for your own independent review and analysis. You need to validate the following suggestions against your independent investigation and analysis before finalizing your own fixes. Think deeply and thoroughly to explore the best implementation option to fix this issue before choosing the best implementation option to carefully merge the validated changes/fixes to the existing code files, taking care not to loose other features and functions while making changes. Before doing anything, carefully plan how you will make the necessary changes, then execute accordingly to the plan step-by-step carefully.

---
Analysis: The "Page Not Found" errors indicate that the routing logic in `index.php` doesn't currently handle the `page` values (`contact`, `faq`, `shipping`, `order-tracking`, `privacy`).

We need to:

1.  **Update `index.php`:** Add `case` statements for these new page values in the main `switch ($page)` block. Since these are mostly static content pages, we can directly include the corresponding view file from within the `case`. We also need to ensure the `$csrfToken` is generated because the layout (`header.php`/`footer.php`) might rely on it for global elements like the newsletter form.
2.  **Create View Files:** Create the missing `.php` files within the `views/` directory (`contact.php`, `faq.php`, `shipping.php`, `order-tracking.php`, `privacy.php`).
3.  **Populate View Files:** Each file will include the standard header and footer (`views/layout/header.php`, `views/layout/footer.php`) and contain relevant placeholder content styled using Tailwind CSS classes for consistency.

Here are the necessary additions to `index.php` and the content for the new view files:

**1. Updates to `index.php`**

Add the following `case` statements within the main `switch ($page)` block in your `index.php` file:

```php
<?php
// index.php (Partial - showing where to add new cases)

// ... (previous code: includes, error handling, security, $page/$action extraction) ...

try {
    // Handle routing
    $page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string');
    $action = SecurityMiddleware::validateInput($_GET['action'] ?? 'index', 'string');

    // Validate CSRF token for POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        SecurityMiddleware::validateCSRF();
    }

    // Generate CSRF token *before* routing potentially includes layout needing it
    $csrfToken = SecurityMiddleware::generateCSRFToken();

    // Route to appropriate controller/action
    switch ($page) {
        case 'home':
            require_once __DIR__ . '/controllers/ProductController.php';
            $productController = new ProductController($pdo);
            $productController->showHomePage(); // This likely handles its own CSRF if needed internally for specific forms
            break;
        case 'product':
            require_once __DIR__ . '/controllers/ProductController.php';
            $productController = new ProductController($pdo);
            $productController->showProduct($_GET['id'] ?? null); // Controller handles CSRF token for view
            break;
        case 'products':
            require_once __DIR__ . '/controllers/ProductController.php';
            $productController = new ProductController($pdo);
            $productController->showProductList(); // Controller handles CSRF token for view
            break;
        case 'cart':
            require_once __DIR__ . '/controllers/CartController.php';
            $controller = new CartController($pdo);

            if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->addToCart(); // Handles JSON response and exits
            } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                 $controller->updateCart(); // Handles JSON response and exits
            } elseif ($action === 'remove' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                 $controller->removeFromCart(); // Handles JSON response and exits
            } else {
                // For showing the cart page (GET request)
                $cartItems = $controller->getCartItems();
                $total = 0; // Calculate total in the view or controller if needed
                foreach ($cartItems as $item) {
                    $total += $item['subtotal'];
                }
                // Make csrfToken available for the cart view's forms/AJAX
                require_once __DIR__ . '/views/cart.php';
            }
            break;

        // ... (other existing cases like checkout, register, quiz, admin) ...

        // --- ADD NEW CASES FOR STATIC PAGES ---
        case 'contact':
            $pageTitle = 'Contact Us - The Scent'; // Set page title
            require_once __DIR__ . '/views/contact.php'; // Directly include view
            break;
        case 'faq':
            $pageTitle = 'Frequently Asked Questions - The Scent';
            require_once __DIR__ . '/views/faq.php';
            break;
        case 'shipping':
            $pageTitle = 'Shipping & Returns - The Scent';
            require_once __DIR__ . '/views/shipping.php';
            break;
        case 'order-tracking':
            $pageTitle = 'Track Your Order - The Scent';
            // Potentially load a controller if tracking involves DB lookup
            // For now, direct view include
            require_once __DIR__ . '/views/order-tracking.php';
            break;
        case 'privacy':
            $pageTitle = 'Privacy Policy - The Scent';
            require_once __DIR__ . '/views/privacy.php';
            break;
        // --- END OF NEW CASES ---

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

**2. New View Files (`views/*.php`)**

Create the following files in the `views/` directory with the content provided:

```php
// filename: views/contact.php
<?php require_once __DIR__ . '/layout/header.php'; ?>

<section class="bg-light py-16 md:py-24">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-bold font-heading text-primary text-center mb-12" data-aos="fade-up">Get In Touch</h1>

        <div class="grid md:grid-cols-2 gap-12 items-start">

            <!-- Contact Information -->
            <div class="space-y-8" data-aos="fade-right">
                <div>
                    <h2 class="text-2xl font-semibold font-heading text-primary-dark mb-4">Contact Information</h2>
                    <p class="text-gray-600 leading-relaxed mb-6">We'd love to hear from you! Reach out with any questions, feedback, or inquiries.</p>
                    <div class="space-y-4 text-gray-700">
                        <p class="flex items-start">
                            <i class="fas fa-map-marker-alt text-primary mt-1 mr-3 fa-fw"></i>
                            <span>123 Aromatherapy Lane,<br>Wellness City, WB 12345</span>
                        </p>
                        <p class="flex items-center">
                            <i class="fas fa-phone text-primary mr-3 fa-fw"></i>
                            <span>+1 (555) 123-4567</span>
                        </p>
                        <p class="flex items-center">
                            <i class="fas fa-envelope text-primary mr-3 fa-fw"></i>
                            <span>hello@thescent.com</span>
                        </p>
                        <p class="flex items-center">
                            <i class="fas fa-clock text-primary mr-3 fa-fw"></i>
                            <span>Mon - Fri: 9:00 AM - 5:00 PM</span>
                        </p>
                    </div>
                </div>

                <div>
                    <h3 class="text-xl font-semibold font-heading text-primary-dark mb-3">Follow Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" aria-label="Facebook" class="text-primary hover:text-primary-dark text-2xl transition-colors duration-200"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram" class="text-primary hover:text-primary-dark text-2xl transition-colors duration-200"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter" class="text-primary hover:text-primary-dark text-2xl transition-colors duration-200"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Pinterest" class="text-primary hover:text-primary-dark text-2xl transition-colors duration-200"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="bg-white p-8 rounded-lg shadow-lg" data-aos="fade-left">
                <h2 class="text-2xl font-semibold font-heading text-primary-dark mb-6">Send Us a Message</h2>
                <form action="#" method="POST" class="space-y-6">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" id="name" name="name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out">
                    </div>
                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                        <input type="text" id="subject" name="subject" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out">
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea id="message" name="message" rows="5" required
                                  class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out"></textarea>
                    </div>
                    <div>
                        <button type="submit" class="w-full btn btn-primary py-3">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

```php
// filename: views/faq.php
<?php require_once __DIR__ . '/layout/header.php'; ?>

<section class="py-16 md:py-24 bg-white">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-bold font-heading text-primary text-center mb-12" data-aos="fade-up">Frequently Asked Questions</h1>

        <div class="max-w-3xl mx-auto space-y-8">

            <!-- FAQ Item 1 -->
            <div class="border-b border-gray-200 pb-6" data-aos="fade-up">
                <h2 class="text-xl font-semibold font-heading text-primary-dark mb-3">What types of products do you offer?</h2>
                <p class="text-gray-600 leading-relaxed">
                    We specialize in premium aromatherapy products, including 100% pure essential oils, natural handcrafted soaps, diffuser blends, and curated gift sets designed to enhance well-being.
                </p>
            </div>

            <!-- FAQ Item 2 -->
            <div class="border-b border-gray-200 pb-6" data-aos="fade-up">
                <h2 class="text-xl font-semibold font-heading text-primary-dark mb-3">Are your essential oils pure?</h2>
                <p class="text-gray-600 leading-relaxed">
                    Absolutely. We source high-quality, pure essential oils from reputable suppliers around the world. They are undiluted and free from synthetic additives, ensuring you receive the full therapeutic benefits.
                </p>
            </div>

            <!-- FAQ Item 3 -->
            <div class="border-b border-gray-200 pb-6" data-aos="fade-up">
                <h2 class="text-xl font-semibold font-heading text-primary-dark mb-3">How do I use essential oils?</h2>
                <p class="text-gray-600 leading-relaxed">
                    Essential oils can be used in various ways:
                    <ul class="list-disc list-inside text-gray-600 mt-2 space-y-1">
                        <li>**Diffusion:** Add a few drops to an ultrasonic diffuser.</li>
                        <li>**Topical Application:** Dilute with a carrier oil (like jojoba or coconut oil) before applying to the skin. Always perform a patch test first.</li>
                        <li>**Inhalation:** Inhale directly from the bottle or add a drop to a tissue.</li>
                        <li>**Baths:** Add a few drops (mixed with a dispersant like carrier oil or salt) to a warm bath.</li>
                    </ul>
                    Please refer to specific product instructions and safety guidelines.
                </p>
            </div>

            <!-- FAQ Item 4 -->
            <div class="border-b border-gray-200 pb-6" data-aos="fade-up">
                <h2 class="text-xl font-semibold font-heading text-primary-dark mb-3">What is your shipping policy?</h2>
                <p class="text-gray-600 leading-relaxed">
                    We offer standard and express shipping options. Orders over $50 qualify for free standard shipping within the continental US. Please visit our <a href="index.php?page=shipping" class="text-primary hover:underline">Shipping & Returns</a> page for detailed information on rates and delivery times.
                </p>
            </div>

            <!-- FAQ Item 5 -->
            <div class="border-b border-gray-200 pb-6" data-aos="fade-up">
                <h2 class="text-xl font-semibold font-heading text-primary-dark mb-3">What is your return policy?</h2>
                <p class="text-gray-600 leading-relaxed">
                    We accept returns of unopened and unused products within 30 days of delivery. Please see our <a href="index.php?page=shipping" class="text-primary hover:underline">Shipping & Returns</a> page for the full policy and instructions on how to initiate a return.
                </p>
            </div>

            <!-- FAQ Item 6 -->
            <div data-aos="fade-up">
                <h2 class="text-xl font-semibold font-heading text-primary-dark mb-3">How can I track my order?</h2>
                <p class="text-gray-600 leading-relaxed">
                    Once your order ships, you will receive a shipping confirmation email containing your tracking number and a link to track your package. You can also use our <a href="index.php?page=order-tracking" class="text-primary hover:underline">Track Your Order</a> page.
                </p>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

```php
// filename: views/shipping.php
<?php require_once __DIR__ . '/layout/header.php'; ?>

<section class="py-16 md:py-24 bg-white">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-bold font-heading text-primary text-center mb-12" data-aos="fade-up">Shipping & Returns</h1>

        <div class="max-w-3xl mx-auto prose lg:prose-lg text-gray-700">

            <div data-aos="fade-up">
                <h2 class="text-2xl font-semibold font-heading text-primary-dark mb-4">Shipping Policy</h2>
                <p>We strive to process and ship all orders within 1-2 business days (excluding weekends and holidays). You will receive a notification when your order has shipped.</p>

                <h3 class="text-xl font-semibold font-heading text-primary-dark mt-6 mb-3">Domestic Shipping Rates & Estimates</h3>
                <ul class="list-disc list-outside space-y-2 pl-5">
                    <li><strong>Free Standard Shipping:</strong> Orders over $50.00 (Typically 5-7 business days)</li>
                    <li><strong>Standard Shipping:</strong> $5.99 (Typically 5-7 business days)</li>
                    <li><strong>Express Shipping:</strong> $12.99 (Typically 2-3 business days)</li>
                    <li><strong>Next Day Delivery:</strong> $19.99 (Order must be placed before 1 PM EST for next business day delivery)</li>
                </ul>
                <p class="mt-4">Please note that shipping times are estimates and may vary due to carrier delays or high-volume periods.</p>

                <h3 class="text-xl font-semibold font-heading text-primary-dark mt-6 mb-3">International Shipping</h3>
                <p>We currently offer shipping to select international destinations. Shipping charges for your order will be calculated and displayed at checkout. Your order may be subject to import duties and taxes (including VAT), which are incurred once a shipment reaches your destination country. The Scent is not responsible for these charges if they are applied and are your responsibility as the customer.</p>

                <h3 class="text-xl font-semibold font-heading text-primary-dark mt-6 mb-3">Order Tracking</h3>
                <p>When your order has shipped, you will receive an email notification from us which will include a tracking number you can use to check its status. Please allow 48 hours for the tracking information to become available.</p>
            </div>

            <hr class="my-12 border-gray-300">

            <div data-aos="fade-up">
                <h2 class="text-2xl font-semibold font-heading text-primary-dark mb-4">Return Policy</h2>
                <p>We want you to be completely satisfied with your purchase. We accept returns up to 30 days after delivery, if the item is unused and in its original condition (including original packaging), and we will refund the full order amount minus the shipping costs for the return.</p>
                <p>In the event that your order arrives damaged in any way, please email us as soon as possible at hello@thescent.com with your order number and a photo of the item’s condition. We address these on a case-by-case basis but will try our best to work towards a satisfactory solution.</p>

                <h3 class="text-xl font-semibold font-heading text-primary-dark mt-6 mb-3">How to Initiate a Return</h3>
                <ol class="list-decimal list-outside space-y-2 pl-5">
                    <li>Please email our customer service team at returns@thescent.com with your order number and the reason for your return.</li>
                    <li>We will provide you with return instructions and a return shipping address.</li>
                    <li>Securely package the item(s) in the original packaging, if possible.</li>
                    <li>Ship the item back to us using a trackable shipping method. You are responsible for return shipping costs unless the item arrived damaged or was incorrect.</li>
                    <li>Once we receive and inspect your return, we will process your refund within 5-7 business days. The refund will be issued to the original payment method.</li>
                </ol>

                <p class="mt-4">If you have any further questions, please don't hesitate to <a href="index.php?page=contact" class="text-primary hover:underline">contact us</a>.</p>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

```php
// filename: views/order-tracking.php
<?php require_once __DIR__ . '/layout/header.php'; ?>

<section class="py-16 md:py-24 bg-light">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-bold font-heading text-primary text-center mb-12" data-aos="fade-up">Track Your Order</h1>

        <div class="max-w-lg mx-auto bg-white p-8 rounded-lg shadow-lg" data-aos="fade-up" data-aos-delay="100">
            <p class="text-gray-600 leading-relaxed mb-6 text-center">
                Enter your order details below to check the status of your shipment. You can find your Order ID in your confirmation email.
            </p>
            <form action="#" method="GET" class="space-y-6"> <!-- Usually GET, but could be POST to a processing endpoint -->
                 <!-- CSRF Token (needed if this form POSTs to a state-changing endpoint) -->
                 <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                 <!-- Add page value if needed for routing -->
                 <input type="hidden" name="page" value="order-tracking">
                 <input type="hidden" name="action" value="track"> <!-- Example action -->

                <div>
                    <label for="order_id" class="block text-sm font-medium text-gray-700 mb-1">Order ID</label>
                    <input type="text" id="order_id" name="order_id" required placeholder="e.g., 12345"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Email used for the order"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out">
                </div>
                <div>
                    <button type="submit" class="w-full btn btn-primary py-3">
                        Track Order
                    </button>
                </div>
            </form>

            <!-- Placeholder for tracking results (would be populated by backend/JS) -->
            <div id="tracking-results" class="mt-8 hidden">
                <h3 class="text-lg font-semibold text-primary-dark mb-4">Tracking Information:</h3>
                <div class="bg-gray-50 p-4 rounded border border-gray-200">
                    <p><strong>Status:</strong> <span id="tracking-status">Fetching...</span></p>
                    <p><strong>Carrier:</strong> <span id="tracking-carrier">N/A</span></p>
                    <p><strong>Tracking Number:</strong> <span id="tracking-number">N/A</span></p>
                    <p><a href="#" id="tracking-link" target="_blank" class="text-primary hover:underline hidden">Track on Carrier Website</a></p>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-6 text-center">
                Tracking information may take up to 48 hours to update after shipment. If you have issues, please <a href="index.php?page=contact" class="text-primary hover:underline">contact us</a>.
            </p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

```php
// filename: views/privacy.php
<?php require_once __DIR__ . '/layout/header.php'; ?>

<section class="py-16 md:py-24 bg-white">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-bold font-heading text-primary text-center mb-12" data-aos="fade-up">Privacy Policy</h1>

        <div class="max-w-3xl mx-auto prose lg:prose-lg text-gray-700 space-y-6">

            <p class="text-sm text-gray-500" data-aos="fade-up">Last Updated: <?= date('F j, Y') ?></p>

            <p data-aos="fade-up">
                The Scent ("us", "we", or "our") operates the https://the-scent.com website (the "Service"). This page informs you of our policies regarding the collection, use, and disclosure of personal data when you use our Service and the choices you have associated with that data.
            </p>

            <div data-aos="fade-up">
                <h2 class="text-2xl font-semibold font-heading text-primary-dark mt-8 mb-3">Information Collection and Use</h2>
                <p>We collect several different types of information for various purposes to provide and improve our Service to you.</p>
                <h3 class="text-xl font-semibold font-heading text-primary-dark mt-4 mb-2">Types of Data Collected</h3>
                <p><strong>Personal Data:</strong> While using our Service, we may ask you to provide us with certain personally identifiable information that can be used to contact or identify you ("Personal Data"). Personally identifiable information may include, but is not limited to:</p>
                <ul class="list-disc list-outside pl-5 space-y-1">
                    <li>Email address</li>
                    <li>First name and last name</li>
                    <li>Phone number</li>
                    <li>Address, State, Province, ZIP/Postal code, City</li>
                    <li>Cookies and Usage Data</li>
                </ul>
                 <p><strong>Usage Data:</strong> We may also collect information on how the Service is accessed and used ("Usage Data"). This Usage Data may include information such as your computer's Internet Protocol address (e.g. IP address), browser type, browser version, the pages of our Service that you visit, the time and date of your visit, the time spent on those pages, unique device identifiers and other diagnostic data.</p>
                 <p><strong>Tracking & Cookies Data:</strong> We use cookies and similar tracking technologies to track the activity on our Service and hold certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent. However, if you do not accept cookies, you may not be able to use some portions of our Service.</p>
            </div>

            <div data-aos="fade-up">
                <h2 class="text-2xl font-semibold font-heading text-primary-dark mt-8 mb-3">Use of Data</h2>
                <p>The Scent uses the collected data for various purposes:</p>
                <ul class="list-disc list-outside pl-5 space-y-1">
                    <li>To provide and maintain the Service</li>
                    <li>To notify you about changes to our Service</li>
                    <li>To allow you to participate in interactive features of our Service when you choose to do so</li>
                    <li>To provide customer care and support</li>
                    <li>To provide analysis or valuable information so that we can improve the Service</li>
                    <li>To monitor the usage of the Service</li>
                    <li>To detect, prevent and address technical issues</li>
                    <li>To process your orders and manage your account</li>
                    <li>To send you newsletters and marketing communications if you opt-in</li>
                </ul>
            </div>

            <div data-aos="fade-up">
                <h2 class="text-2xl font-semibold font-heading text-primary-dark mt-8 mb-3">Transfer Of Data</h2>
                <p>Your information, including Personal Data, may be transferred to — and maintained on — computers located outside of your state, province, country or other governmental jurisdiction where the data protection laws may differ than those from your jurisdiction.</p>
                <p>Your consent to this Privacy Policy followed by your submission of such information represents your agreement to that transfer.</p>
                <p>The Scent will take all steps reasonably necessary to ensure that your data is treated securely and in accordance with this Privacy Policy and no transfer of your Personal Data will take place to an organization or a country unless there are adequate controls in place including the security of your data and other personal information.</p>
            </div>

            <div data-aos="fade-up">
                <h2 class="text-2xl font-semibold font-heading text-primary-dark mt-8 mb-3">Disclosure Of Data</h2>
                <p>We may disclose your Personal Data in the good faith belief that such action is necessary to:</p>
                <ul class="list-disc list-outside pl-5 space-y-1">
                    <li>To comply with a legal obligation</li>
                    <li>To protect and defend the rights or property of The Scent</li>
                    <li>To prevent or investigate possible wrongdoing in connection with the Service</li>
                    <li>To protect the personal safety of users of the Service or the public</li>
                    <li>To protect against legal liability</li>
                </ul>
            </div>

            <div data-aos="fade-up">
                <h2 class="text-2xl font-semibold font-heading text-primary-dark mt-8 mb-3">Security of Data</h2>
                <p>The security of your data is important to us, but remember that no method of transmission over the Internet, or method of electronic storage is 100% secure. While we strive to use commercially acceptable means to protect your Personal Data, we cannot guarantee its absolute security.</p>
            </div>

             <div data-aos="fade-up">
                <h2 class="text-2xl font-semibold font-heading text-primary-dark mt-8 mb-3">Your Data Protection Rights</h2>
                <p>You have certain data protection rights. The Scent aims to take reasonable steps to allow you to correct, amend, delete, or limit the use of your Personal Data. If you wish to be informed what Personal Data we hold about you and if you want it to be removed from our systems, please contact us. In certain circumstances, you have the following data protection rights:</p>
                 <ul class="list-disc list-outside pl-5 space-y-1">
                    <li>The right to access, update or to delete the information we have on you.</li>
                    <li>The right of rectification.</li>
                    <li>The right to object.</li>
                    <li>The right of restriction.</li>
                    <li>The right to data portability</li>
                    <li>The right to withdraw consent</li>
                 </ul>
            </div>

            <div data-aos="fade-up">
                <h2 class="text-2xl font-semibold font-heading text-primary-dark mt-8 mb-3">Changes to This Privacy Policy</h2>
                <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page. We will let you know via email and/or a prominent notice on our Service, prior to the change becoming effective and update the "Last updated" date at the top of this Privacy Policy.</p>
                <p>You are advised to review this Privacy Policy periodically for any changes. Changes to this Privacy Policy are effective when they are posted on this page.</p>
            </div>

            <div data-aos="fade-up">
                <h2 class="text-2xl font-semibold font-heading text-primary-dark mt-8 mb-3">Contact Us</h2>
                <p>If you have any questions about this Privacy Policy, please contact us:</p>
                <ul class="list-disc list-outside pl-5 space-y-1">
                    <li>By email: privacy@thescent.com</li>
                    <li>By visiting this page on our website: <a href="index.php?page=contact" class="text-primary hover:underline">https://the-scent.com/index.php?page=contact</a></li>
                    <li>By phone number: +1 (555) 123-4567</li>
                </ul>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

With these changes in `index.php` and the new files in the `views/` directory, the footer links should now lead to properly formatted pages with relevant placeholder content, resolving the "Page Not Found" errors.

---
https://drive.google.com/file/d/17sQ-jAwpmCw5AH1aHdOOrSK9097fi4BO/view?usp=sharing, https://drive.google.com/file/d/1FyAA1sG46WO7EFkG2r3L4J0WemXSNubI/view?usp=sharing, https://drive.google.com/file/d/1H2R68QZCm8Nj1TvJ5vYz-sqGOYEkXvkn/view?usp=sharing, https://drive.google.com/file/d/1J2idBpA46EyMVTgO7rtrmEqs4KOCbmsz/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221M8ZMO9RYtFFaiwVV4fHitLNmxnZWE1go%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1bnysHWRLA0U-hXWBU6aM48R1-mYkSTcn/view?usp=sharing, https://drive.google.com/file/d/1iYtVZhH7PoblGzR3WViZnVSBF0nU5oT4/view?usp=sharing, https://drive.google.com/file/d/1lDSuDrMFsSCw7fktqHQr_bRFW4pfsmKC/view?usp=sharing, https://drive.google.com/file/d/1mcCunnB3JE5sNAR7alkRgRjFxPDca5qB/view?usp=sharing, https://drive.google.com/file/d/1mm-kHs4jpmySYRmEeKf68tcby04j9ZzJ/view?usp=sharing, https://drive.google.com/file/d/1nAx3ra0ScJF4NXawSpw5n7bsnZTGznqc/view?usp=sharing, https://drive.google.com/file/d/1t1NBJZ1MbfXL-SwX2Oimh3T8uwOwImeW/view?usp=sharing, https://drive.google.com/file/d/1tG2uF5vL5RVr7V59HifVCkiwn02hZ7pS/view?usp=sharing, https://drive.google.com/file/d/1yrb78QgFuSheVsJjQGDvHypZmJNjOFLf/view?usp=sharing
