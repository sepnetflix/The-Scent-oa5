<?php require_once 'layout/header.php'; ?>

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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirm');
    const submitButton = document.getElementById('submitButton');
    
    const requirements = {
        length: {
            regex: /.{8,}/,
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

<?php require_once 'layout/footer.php'; ?>