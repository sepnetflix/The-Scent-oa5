<?php require_once __DIR__ . '/layout/header.php'; ?>

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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const submitButton = document.getElementById('submitButton');
    
    const requirements = {
        length: {
            regex: /.{12,}/,
            element: document.getElementById('length')
        },
        uppercase: {
            regex: /[A-Z]/,
            element: document.getElementById('uppercase')
        },
        lowercase: {
            regex: /[a-z]/,
            element: document.getElementById('lowercase')
        },
        number: {
            regex: /[0-9]/,
            element: document.getElementById('number')
        },
        special: {
            regex: /[@$!%*?&]/,
            element: document.getElementById('special')
        },
        match: {
            element: document.getElementById('match')
        }
    };

    function validatePassword() {
        const isValid = Object.keys(requirements).every(requirement => {
            if (requirement === 'match') {
                const matches = password.value === confirmPassword.value;
                requirements[requirement].element.classList.toggle('met', matches);
                return matches;
            }
            const meetsRequirement = requirements[requirement].regex.test(password.value);
            requirements[requirement].element.classList.toggle('met', meetsRequirement);
            return meetsRequirement;
        });

        submitButton.disabled = !isValid;
    }

    password.addEventListener('input', validatePassword);
    confirmPassword.addEventListener('input', validatePassword);
    
    // Handle password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
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
    });

    // Handle form submission
    form.addEventListener('submit', function(e) {
        if (submitButton.disabled) {
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
.password-requirements {
    margin: 1rem 0;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.password-requirements h4 {
    margin: 0 0 0.5rem 0;
    font-size: 0.875rem;
    color: #495057;
}

.password-requirements ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.requirement {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0.25rem 0;
    display: flex;
    align-items: center;
}

.requirement i {
    font-size: 0.625rem;
    margin-right: 0.5rem;
}

.requirement.met {
    color: #198754;
}

.requirement.met i {
    color: #198754;
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

.button-loader {
    display: inline-block;
}

.button-loader.hidden,
.button-text.hidden {
    display: none;
}
</style>

<?php require_once __DIR__ . '/layout/footer.php'; ?>