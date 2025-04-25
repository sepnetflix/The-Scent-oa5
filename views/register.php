<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-register">
<section class="auth-section">
    <div class="container">
        <div class="auth-container" data-aos="fade-up">
            <h1>Create Account</h1>
            <p class="auth-description">Join The Scent community to discover your perfect fragrance.</p>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="flash-message <?= $_SESSION['flash_type'] ?? 'info' ?>">
                    <?= htmlspecialchars($_SESSION['flash_message']) ?>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>

            <form action="index.php?page=register" method="POST" class="auth-form" id="registerForm">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                           placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="Enter your email address"
                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                           title="Please enter a valid email address">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required 
                               minlength="12"
                               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$"
                               placeholder="Enter your password">
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-input">
                        <input type="password" id="confirm_password" name="confirm_password" required
                               placeholder="Confirm your password">
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="password-requirements" id="passwordRequirements">
                    <h4>Password Requirements:</h4>
                    <ul>
                        <li id="length" class="requirement">
                            <i class="fas fa-circle"></i> At least 12 characters
                        </li>
                        <li id="uppercase" class="requirement">
                            <i class="fas fa-circle"></i> One uppercase letter
                        </li>
                        <li id="lowercase" class="requirement">
                            <i class="fas fa-circle"></i> One lowercase letter
                        </li>
                        <li id="number" class="requirement">
                            <i class="fas fa-circle"></i> One number
                        </li>
                        <li id="special" class="requirement">
                            <i class="fas fa-circle"></i> One special character
                        </li>
                        <li id="match" class="requirement">
                            <i class="fas fa-circle"></i> Passwords match
                        </li>
                    </ul>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="newsletter_signup" value="1" checked>
                        <span>Sign up for our newsletter to receive updates and exclusive offers</span>
                    </label>
                </div>

                <button type="submit" class="btn-primary full-width" id="submitButton" disabled>
                    <span class="button-text">Create Account</span>
                    <span class="button-loader hidden">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>

            <div class="auth-links">
                <p>Already have an account? <a href="index.php?page=login">Login here</a></p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>