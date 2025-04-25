<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-login">
<section class="auth-section">
    <div class="container">
        <div class="auth-container" data-aos="fade-up">
            <h1>Login</h1>
            <p class="auth-description">Welcome back! Please enter your credentials to continue.</p>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="flash-message <?= $_SESSION['flash_type'] ?? 'info' ?>">
                    <?= htmlspecialchars($_SESSION['flash_message']) ?>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>

            <form action="index.php?page=login" method="POST" class="auth-form" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="Enter your email address">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required
                               placeholder="Enter your password">
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="forgot-password">
                        <a href="index.php?page=forgot-password">Forgot Password?</a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember_me" value="1"
                               <?= isset($_POST['remember_me']) ? 'checked' : '' ?>>
                        <span>Keep me logged in</span>
                    </label>
                </div>

                <button type="submit" class="btn-primary full-width" id="submitButton">
                    <span class="button-text">Login</span>
                    <span class="button-loader hidden">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>

            <div class="auth-links">
                <p>Don't have an account? <a href="index.php?page=register">Create one now</a></p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>