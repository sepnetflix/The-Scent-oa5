<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-forgot-password">
<section class="auth-section">
    <div class="container">
        <div class="auth-container" data-aos="fade-up">
            <h1>Forgot Password</h1>
            <p class="auth-description">Enter your email address and we'll send you a link to reset your password.</p>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="flash-message <?= $_SESSION['flash_type'] ?? 'info' ?>">
                    <?= htmlspecialchars($_SESSION['flash_message']) ?>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>

            <form action="index.php?page=forgot-password" method="POST" class="auth-form" id="forgotPasswordForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Enter your email address"
                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                           title="Please enter a valid email address">
                    <div class="form-hint">We'll send password reset instructions to this email.</div>
                </div>

                <button type="submit" class="btn-primary full-width" id="submitButton">
                    <span class="button-text">Send Reset Link</span>
                    <span class="button-loader hidden">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>

            <div class="auth-links">
                <p>Remember your password? <a href="index.php?page=login">Back to Login</a></p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>