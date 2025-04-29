<?php
// views/login.php (Updated)
$pageTitle = 'Login - The Scent';
// Apply consistent gradient background and page identifier class
$bodyClass = 'page-login bg-gradient-to-br from-light to-secondary/20';

require_once __DIR__ . '/layout/header.php'; // Includes CSRF token output globally
?>

<section class="auth-section flex items-center justify-center min-h-[calc(100vh-80px)] py-12 px-4">
    <div class="container max-w-md mx-auto">
        <div class="auth-container bg-white p-8 md:p-12 rounded-xl shadow-2xl" data-aos="fade-up" data-aos-delay="100">
            <div class="text-center mb-10">
                <h1 class="text-3xl lg:text-4xl font-bold font-heading text-primary mb-3">Welcome Back</h1>
                <p class="text-gray-600 font-body">Log in to continue your journey with The Scent.</p>
            </div>

            <?php // Standard Flash Message Display (from header or dynamic)
                // This relies on the flash message container in the header/footer layout
            ?>
            <?php if (isset($_SESSION['flash_message'])): ?>
                <script>
                    // Use the JS function immediately if available, or queue it
                    document.addEventListener('DOMContentLoaded', function() {
                        if (typeof window.showFlashMessage === 'function') {
                            window.showFlashMessage(<?= json_encode($_SESSION['flash_message']) ?>, <?= json_encode($_SESSION['flash_type'] ?? 'info') ?>);
                        }
                    });
                </script>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>


            <form action="index.php?page=login" method="POST" class="auth-form space-y-6" id="loginForm">
		 <!-- CSRF Token is handled globally by JS reading #csrf-token-value -->
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-group">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1 font-body">Email Address</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out font-body"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="you@example.com">
                </div>

                <div class="form-group">
                    <div class="flex justify-between items-baseline mb-1">
                         <label for="password" class="block text-sm font-medium text-gray-700 font-body">Password</label>
                         <a href="index.php?page=forgot_password" class="text-sm text-primary hover:text-primary-dark font-medium transition duration-150 ease-in-out font-body">Forgot Password?</a>
                    </div>
                    <div class="password-input relative">
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out font-body"
                               placeholder="Enter your password">
                        <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary transition duration-150 ease-in-out" aria-label="Toggle password visibility">
                            <i class="fas fa-eye text-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group flex items-center justify-between">
                    <label class="checkbox-label flex items-center text-sm text-gray-700 cursor-pointer font-body">
                        <input type="checkbox" name="remember_me" value="1"
                               class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary mr-2"
                               <?= isset($_POST['remember_me']) ? 'checked' : '' ?>>
                        <span>Keep me logged in</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-full py-3 text-lg font-semibold rounded-md shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-dark transition duration-150 ease-in-out flex items-center justify-center font-body" id="submitButton">
                    <span class="button-text">Log In</span>
                    <span class="button-loader hidden ml-2">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>

            <div class="auth-links mt-8 pt-6 border-t border-gray-200 text-center">
                <p class="text-sm text-gray-600 font-body">Don't have an account?
                     <a href="index.php?page=register" class="font-medium text-primary hover:text-primary-dark transition duration-150 ease-in-out">Create one now</a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
