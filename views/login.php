<?php require_once __DIR__ . '/layout/header.php'; ?>

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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const submitButton = document.getElementById('submitButton');
    
    // Handle password visibility toggle
    document.querySelector('.toggle-password').addEventListener('click', function() {
        const input = this.parentElement.querySelector('input');
        const icon = this.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    // Handle form submission
    form.addEventListener('submit', function(e) {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        
        if (!email || !password) {
            e.preventDefault();
            return;
        }
        
        const buttonText = submitButton.querySelector('.button-text');
        const buttonLoader = submitButton.querySelector('.button-loader');
        
        buttonText.classList.add('hidden');
        buttonLoader.classList.remove('hidden');
        submitButton.disabled = true;
    });
});
</script>

<style>
.auth-container {
    max-width: 400px;
    margin: 2rem auto;
    padding: 2rem;
    background: #fff;
    border-radius: 0.5rem;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
}

.auth-description {
    text-align: center;
    color: #6c757d;
    margin-bottom: 2rem;
}

.password-input {
    position: relative;
    display: flex;
    align-items: center;
}

.toggle-password {
    position: absolute;
    right: 0.75rem;
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 0.25rem;
}

.toggle-password:hover {
    color: #495057;
}

.forgot-password {
    text-align: right;
    margin-top: 0.5rem;
    font-size: 0.875rem;
}

.forgot-password a {
    color: #4a90e2;
    text-decoration: none;
}

.forgot-password a:hover {
    text-decoration: underline;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.button-loader {
    display: inline-block;
}

.button-loader.hidden,
.button-text.hidden {
    display: none;
}

.auth-links {
    text-align: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e9ecef;
}

.auth-links a {
    color: #4a90e2;
    text-decoration: none;
}

.auth-links a:hover {
    text-decoration: underline;
}
</style>

<?php require_once __DIR__ . '/layout/footer.php'; ?>