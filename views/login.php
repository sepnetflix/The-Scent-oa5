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
