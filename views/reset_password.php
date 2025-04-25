<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-reset-password">
<section class="auth-section">
    <div class="container">
        <div class="auth-container" data-aos="fade-up">
            <h1>Reset Password</h1>
            <p class="auth-description">Please choose a strong password for your account.</p>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="flash-message <?= $_SESSION['flash_type'] ?? 'info' ?>">
                    <?= htmlspecialchars($_SESSION['flash_message']) ?>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>

            <form action="index.php?page=reset-password" method="POST" class="auth-form" id="resetPasswordForm">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required 
                               minlength="8"
                               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                               placeholder="Enter your new password"
                               title="Must contain at least 8 characters, including uppercase, lowercase, number and special character">
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirm New Password</label>
                    <div class="password-input">
                        <input type="password" id="password_confirm" name="password_confirm" required 
                               placeholder="Confirm your new password">
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="password-requirements" id="passwordRequirements">
                    <h4>Password Requirements:</h4>
                    <ul>
                        <li id="length" class="requirement">
                            <i class="fas fa-circle"></i> At least 8 characters
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

                <button type="submit" class="btn-primary full-width" id="submitButton" disabled>
                    <span class="button-text">Reset Password</span>
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