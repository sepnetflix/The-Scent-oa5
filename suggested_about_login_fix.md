Okay, I understand the issues. Let's address them step-by-step.

1.  **About Page (`/index.php?page=about`):** The error logs confirm that `views/about.php` is missing, causing the 500 error and the display of the generic error page. We need to create this file with appropriate content and styling.
2.  **Login Page (`/index.php?page=login`):** The layout needs a significant overhaul using Tailwind CSS to match modern e-commerce standards.

Here are the necessary code modifications:

**1. Create `views/about.php`**

This file needs to be created in the `views/` directory.

```php
<?php
// views/about.php
// Set page-specific variables
$pageTitle = 'About Us - The Scent';
$bodyClass = 'page-about bg-light'; // Add a background color via class

require_once __DIR__ . '/layout/header.php'; // Include header
?>

<section class="py-16 md:py-24 text-center bg-secondary/10 border-b border-gray-200" data-aos="fade-in">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl md:text-5xl font-bold font-heading text-primary mb-4">Our Story</h1>
        <p class="text-lg md:text-xl text-gray-700 max-w-3xl mx-auto">Discover the passion and principles behind The Scent.</p>
    </div>
</section>

<section class="py-16 md:py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-12 items-center mb-16 md:mb-24">
            <div data-aos="fade-right">
                <h2 class="text-3xl font-bold font-heading text-primary-dark mb-6">Rooted in Nature, Crafted with Care</h2>
                <p class="text-gray-700 mb-4 leading-relaxed">
                    At The Scent, we believe in the profound power of nature to restore balance and enhance well-being. Our journey began with a simple desire: to harness the purest botanical essences and share their therapeutic benefits with the world.
                </p>
                <p class="text-gray-700 mb-4 leading-relaxed">
                    We meticulously source high-quality, sustainable ingredients from ethical growers globally. Each essential oil, herb, and natural component is selected for its purity, potency, and aromatic profile.
                </p>
                <p class="text-gray-700 leading-relaxed">
                    Our unique and creative formulations are developed by expert aromatherapists, resulting in harmonious, well-rounded products designed to nurture both mind and body.
                </p>
            </div>
            <div class="about-image" data-aos="fade-left">
                <img src="/images/about_hero.jpg" alt="Natural ingredients and essential oils" class="rounded-lg shadow-xl w-full h-auto object-cover aspect-[4/3]">
                <!-- Replace with an actual relevant image if available -->
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-12 items-center">
             <div class="about-image order-last md:order-first" data-aos="fade-right">
                 <img src="/images/about_team.jpg" alt="The Scent Team crafting products" class="rounded-lg shadow-xl w-full h-auto object-cover aspect-[4/3]">
                 <!-- Replace with an actual relevant image if available -->
            </div>
            <div data-aos="fade-left" class="order-first md:order-last">
                <h2 class="text-3xl font-bold font-heading text-primary-dark mb-6">Our Mission & Values</h2>
                <ul class="space-y-4 text-gray-700 leading-relaxed">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-secondary mr-3 mt-1 text-xl"></i>
                        <span><strong>Purity & Quality:</strong> To offer only the finest, 100% natural aromatherapy products without compromise.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-leaf text-secondary mr-3 mt-1 text-xl"></i>
                        <span><strong>Sustainability:</strong> To operate responsibly, respecting the environment and supporting ethical sourcing practices.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-heart text-secondary mr-3 mt-1 text-xl"></i>
                        <span><strong>Well-being:</strong> To empower our customers to find moments of calm, energy, and balance through the power of scent.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-lightbulb text-secondary mr-3 mt-1 text-xl"></i>
                        <span><strong>Expertise & Innovation:</strong> To continually explore and craft unique, effective aromatherapy solutions based on traditional wisdom and modern science.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="py-16 md:py-20 bg-light">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold font-heading text-primary-dark mb-12" data-aos="fade-up">Meet the Team (Optional)</h2>
        <p class="text-gray-700 max-w-2xl mx-auto mb-12" data-aos="fade-up" data-aos-delay="100">
            Behind every blend is a team passionate about natural wellness. (Add team member profiles here if desired).
        </p>
        <!-- Placeholder for team members -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Example Team Member Card -->
            <div class="bg-white p-6 rounded-lg shadow text-center" data-aos="fade-up" data-aos-delay="200">
                <img src="/images/team_placeholder.png" alt="Team Member" class="w-24 h-24 rounded-full mx-auto mb-4 border-2 border-secondary object-cover">
                <h3 class="text-xl font-semibold text-primary mb-1">Jane Doe</h3>
                <p class="text-sm text-accent font-medium mb-2">Lead Aromatherapist</p>
                <p class="text-sm text-gray-600">Passionate about blending traditional knowledge with modern techniques to create effective wellness solutions.</p>
            </div>
            <!-- Add more team members -->
        </div>
    </div>
</section>

<section class="py-16 md:py-20 bg-secondary text-center text-primary-dark" data-aos="fade-in">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold font-heading mb-6">Ready to Find Your Scent?</h2>
        <p class="text-lg mb-8 max-w-xl mx-auto">Take our quick quiz to discover personalized recommendations.</p>
        <a href="index.php?page=quiz" class="btn btn-primary text-lg px-8 py-3 transition duration-300 ease-in-out transform hover:scale-105">Take the Quiz</a>
    </div>
</section>


<?php
require_once __DIR__ . '/layout/footer.php'; // Include footer
?>
```

**2. Update `index.php`**

Modify the `case 'about':` block to correctly set variables and include the new view.

```php
// index.php (snippet)

        case 'about': // Add route for about page if needed
             $pageTitle = 'About Us - The Scent'; // Set here for consistency
             $csrfToken = SecurityMiddleware::generateCSRFToken(); // Generate token
             $bodyClass = 'page-about'; // Set body class
             // Make variables available to the view scope before including it
             extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
             require_once __DIR__ . '/views/about.php'; // Require the *new* view file
             break;
```

**3. Update `views/login.php`**

Replace the content of `views/login.php` with the following structure, heavily utilizing Tailwind CSS.

```php
<?php
// views/login.php
$pageTitle = 'Login - The Scent'; // Ensure page title is set
$bodyClass = 'page-login bg-gradient-to-br from-light to-secondary/20'; // Add gradient background

require_once __DIR__ . '/layout/header.php';
?>

<section class="auth-section flex items-center justify-center min-h-[calc(100vh-80px)] py-12 px-4">
    <div class="container max-w-md mx-auto">
        <div class="auth-container bg-white p-8 md:p-10 rounded-xl shadow-2xl" data-aos="fade-up">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold font-heading text-primary mb-2">Welcome Back</h1>
                <p class="text-gray-600">Log in to continue your journey with The Scent.</p>
            </div>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="flash-message <?= $_SESSION['flash_type'] ?? 'info' ?> mb-6 border text-sm px-4 py-3 rounded relative shadow-md <?=
                    match ($_SESSION['flash_type'] ?? 'info') {
                        'success' => 'bg-green-100 border-green-400 text-green-700',
                        'error'   => 'bg-red-100 border-red-400 text-red-700',
                        'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
                        default   => 'bg-blue-100 border-blue-400 text-blue-700',
                    } ?>">
                    <?= htmlspecialchars($_SESSION['flash_message']) ?>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                </div>
            <?php endif; ?>

            <form action="index.php?page=login" method="POST" class="auth-form space-y-6" id="loginForm">
                 <!-- CSRF Token is output globally in header, read by JS -->
                <div class="form-group">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="you@example.com">
                </div>

                <div class="form-group">
                    <div class="flex justify-between items-baseline mb-1">
                         <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                         <a href="index.php?page=forgot_password" class="text-sm text-primary hover:text-primary-dark font-medium transition duration-150 ease-in-out">Forgot Password?</a>
                    </div>
                    <div class="password-input relative">
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out"
                               placeholder="Enter your password">
                        <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary transition duration-150 ease-in-out" aria-label="Toggle password visibility">
                            <i class="fas fa-eye text-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group flex items-center justify-between">
                    <label class="checkbox-label flex items-center text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" name="remember_me" value="1"
                               class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary mr-2"
                               <?= isset($_POST['remember_me']) ? 'checked' : '' ?>>
                        <span>Keep me logged in</span>
                    </label>
                </div>

                <button type="submit" class="btn-primary w-full py-3 text-lg font-semibold rounded-md shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-dark transition duration-150 ease-in-out flex items-center justify-center" id="submitButton">
                    <span class="button-text">Log In</span>
                    <span class="button-loader hidden ml-2">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>

            <div class="auth-links mt-8 pt-6 border-t border-gray-200 text-center">
                <p class="text-sm text-gray-600">Don't have an account?
                     <a href="index.php?page=register" class="font-medium text-primary hover:text-primary-dark transition duration-150 ease-in-out">Create one now</a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

**4. Update `controllers/AccountController.php`**

Ensure the `login()` method in `AccountController.php` passes the correct `$bodyClass`. It already seems to do this based on the provided code. Just double-check the GET request part:

```php
// controllers/AccountController.php (snippet inside login method)

    public function login() {
        // Handle showing the login form on GET
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             // Data for the view
             $data = [
                 'pageTitle' => 'Login - The Scent',
                 'csrfToken' => $this->generateCSRFToken(),
                 'bodyClass' => 'page-login bg-gradient-to-br from-light to-secondary/20' // Ensure this is set
             ];
             // Use renderView to pass data correctly
             echo $this->renderView('login', $data);
             return;
        }
        // ... POST logic remains the same ...
    }
```

**Explanation of Changes:**

*   **`views/about.php`:**
    *   A new file is created with a standard structure (`header.php`, `footer.php`).
    *   Includes relevant sections (Our Story, Mission/Values, Team placeholder) using Tailwind CSS for layout and styling, aiming for consistency with `home.php`.
    *   Uses dummy text that fits the "The Scent" brand. Replace image paths (`/images/about_hero.jpg`, etc.) with actual images if available.
    *   Sets `$pageTitle` and `$bodyClass` locally.
*   **`index.php`:**
    *   The `case 'about'` is updated to correctly set `$pageTitle`, `$csrfToken`, `$bodyClass` using `extract()` before requiring the new `views/about.php`. This ensures the header/footer receive the necessary variables.
*   **`views/login.php`:**
    *   The entire structure is refactored using Tailwind CSS utility classes for a modern, centered layout.
    *   Uses flexbox, padding, margins, shadows, rounded corners for a cleaner look.
    *   Form elements (`label`, `input`, `button`) are styled using Tailwind classes, including focus states (`focus:ring-primary`, `focus:border-primary`).
    *   The password visibility toggle button is positioned correctly within the input field.
    *   "Forgot Password?" and "Create account" links are styled.
    *   The submit button retains its necessary structure (`#submitButton`, `.button-text`, `.button-loader`) for the JavaScript functionality.
    *   A gradient background is added via `$bodyClass` for visual appeal.
*   **`controllers/AccountController.php`:**
    *   Minor confirmation to ensure the `$bodyClass` for the login page is correctly set and passed during the GET request handling within the `login` method.

After implementing these changes:

1.  The "About" link should now load the newly created `views/about.php` with styled content.
2.  The Login page should display with a much cleaner, centered, and modern layout, similar to professional e-commerce sites.
