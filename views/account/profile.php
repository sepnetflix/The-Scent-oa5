<?php require_once __DIR__ . '/../layout/header.php'; ?>

<section class="account-section">
    <div class="container">
        <div class="account-grid">
            <!-- Sidebar Navigation -->
            <aside class="account-sidebar" data-aos="fade-right">
                <div class="account-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <h3><?= htmlspecialchars($user['name']) ?></h3>
                        <p><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    
                    <nav>
                        <ul>
                            <li>
                                <a href="index.php?page=account">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=orders">
                                    <i class="fas fa-shopping-bag"></i> My Orders
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=profile" class="active">
                                    <i class="fas fa-user"></i> Profile Settings
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=quiz">
                                    <i class="fas fa-clipboard-list"></i> Quiz History
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=logout">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </aside>
            
            <!-- Main Content -->
            <div class="account-content">
                <h1 class="page-title" data-aos="fade-up">Profile Settings</h1>
                
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash_type'] ?>" data-aos="fade-up">
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                    </div>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>
                
                <div class="profile-grid">
                    <!-- Personal Information -->
                    <div class="profile-card" data-aos="fade-up">
                        <h2>Personal Information</h2>
                        <form action="index.php?page=account&section=profile&action=update" method="POST" 
                              class="profile-form" id="profileForm">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" required
                                       value="<?= htmlspecialchars($user['name']) ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" required
                                       value="<?= htmlspecialchars($user['email']) ?>">
                            </div>
                            
                            <button type="submit" class="btn-primary">Save Changes</button>
                        </form>
                    </div>
                    
                    <!-- Change Password -->
                    <div class="profile-card" data-aos="fade-up">
                        <h2>Change Password</h2>
                        <form action="index.php?page=account&section=profile&action=update" method="POST" 
                              class="password-form" id="passwordForm">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <div class="password-input">
                                    <input type="password" id="current_password" name="current_password">
                                    <button type="button" class="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <div class="password-input">
                                    <input type="password" id="new_password" name="new_password"
                                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                           title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                                    <button type="button" class="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <div class="password-input">
                                    <input type="password" id="confirm_password" name="confirm_password">
                                    <button type="button" class="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="password-requirements">
                                <h4>Password Requirements:</h4>
                                <ul>
                                    <li id="length">At least 8 characters</li>
                                    <li id="uppercase">One uppercase letter</li>
                                    <li id="lowercase">One lowercase letter</li>
                                    <li id="number">One number</li>
                                </ul>
                            </div>
                            
                            <button type="submit" class="btn-primary">Update Password</button>
                        </form>
                    </div>
                    
                    <!-- Communication Preferences -->
                    <div class="profile-card" data-aos="fade-up">
                        <h2>Communication Preferences</h2>
                        <form action="index.php?page=account&section=profile&action=update" method="POST" 
                              class="preferences-form">
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="email_marketing" 
                                           <?= $user['email_marketing'] ? 'checked' : '' ?>>
                                    <span>Promotional emails about new products and special offers</span>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="email_orders" 
                                           <?= $user['email_orders'] ? 'checked' : '' ?>>
                                    <span>Order status updates and shipping notifications</span>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="email_newsletter" 
                                           <?= $user['email_newsletter'] ? 'checked' : '' ?>>
                                    <span>Monthly newsletter with aromatherapy tips and trends</span>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn-primary">Update Preferences</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });
    
    // Password validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const requirements = {
        length: document.getElementById('length'),
        uppercase: document.getElementById('uppercase'),
        lowercase: document.getElementById('lowercase'),
        number: document.getElementById('number')
    };
    
    newPassword.addEventListener('input', function() {
        const password = this.value;
        
        // Update requirement indicators
        requirements.length.classList.toggle('met', password.length >= 8);
        requirements.uppercase.classList.toggle('met', /[A-Z]/.test(password));
        requirements.lowercase.classList.toggle('met', /[a-z]/.test(password));
        requirements.number.classList.toggle('met', /\d/.test(password));
    });
    
    // Form validation
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        if (newPassword.value !== confirmPassword.value) {
            e.preventDefault();
            alert('New passwords do not match.');
            return;
        }
        
        if (newPassword.value && !newPassword.checkValidity()) {
            e.preventDefault();
            alert('Please meet all password requirements.');
            return;
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>