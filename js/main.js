// Tailwind CSS custom config (must be loaded before Tailwind CDN in header.php)
window.tailwind = {
    theme: {
        extend: {
            colors: {
                primary: '#1A4D5A',
                secondary: '#A0C1B1',
                accent: '#D4A76A',
            },
            fontFamily: {
                heading: ['Cormorant Garamond', 'serif'],
                body: ['Montserrat', 'sans-serif'],
                accent: ['Raleway', 'sans-serif'],
            },
        },
    },
};

// Mobile menu toggle
window.addEventListener('DOMContentLoaded', function() {
    var menuToggle = document.querySelector('.mobile-menu-toggle');
    var navLinks = document.querySelector('.nav-links');
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });
    }
});

// showFlashMessage utility
window.showFlashMessage = function(message, type = 'info') {
    let flash = document.createElement('div');
    flash.className = 'flash-message ' + type;
    flash.textContent = message;
    document.body.appendChild(flash);
    setTimeout(() => {
        flash.classList.add('slide-in');
    }, 10);
    setTimeout(() => {
        flash.classList.remove('slide-in');
        flash.remove();
    }, 3500);
};

// Global AJAX handlers (Add-to-Cart, Newsletter, etc.)
window.addEventListener('DOMContentLoaded', function() {
    // Add-to-Cart handler
    document.body.addEventListener('click', function(e) {
        var btn = e.target.closest('.add-to-cart');
        if (!btn) return;
        e.preventDefault();
        var productId = btn.dataset.productId;
        var csrfToken = document.getElementById('csrf-token-value')?.value;
        if (!productId || !csrfToken) {
            showFlashMessage('Missing product or security token', 'error');
            return;
        }
        btn.disabled = true;
        var originalText = btn.textContent;
        btn.textContent = 'Adding...';
        fetch('index.php?page=cart&action=add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${productId}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showFlashMessage(data.message || 'Added to cart', 'success');
                btn.textContent = 'Added!';
                setTimeout(() => { btn.textContent = originalText; btn.disabled = false; }, 1200);
            } else {
                showFlashMessage(data.message || 'Error adding to cart', 'error');
                btn.textContent = originalText;
                btn.disabled = false;
            }
        })
        .catch(() => {
            showFlashMessage('Error adding to cart. Check connection or refresh.', 'error');
            btn.textContent = originalText;
            btn.disabled = false;
        });
    });
    // Newsletter AJAX handler (if present)
    var newsletterForm = document.getElementById('newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var email = newsletterForm.querySelector('input[name="email"]').value;
            var csrfToken = document.getElementById('csrf-token-value')?.value;
            if (!email || !csrfToken) {
                showFlashMessage('Please enter your email.', 'error');
                return;
            }
            fetch('index.php?page=newsletter&action=subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `email=${encodeURIComponent(email)}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(res => res.json())
            .then(data => {
                showFlashMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) newsletterForm.reset();
            })
            .catch(() => {
                showFlashMessage('Error subscribing. Try again later.', 'error');
            });
        });
    }
});

// --- Page Initializers ---
function initHomePage() {
    // Newsletter AJAX (already handled globally, but ensure id is correct)
    // No additional JS needed for home page if global handlers are present
}

function initProductsPage() {
    // Sorting
    const sortSelect = document.getElementById('sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', this.value);
            window.location.href = url.toString();
        });
    }
    // Price filter
    const applyPriceFilter = document.querySelector('.apply-price-filter');
    if (applyPriceFilter) {
        applyPriceFilter.addEventListener('click', function() {
            const minPrice = document.getElementById('minPrice').value;
            const maxPrice = document.getElementById('maxPrice').value;
            const url = new URL(window.location.href);
            if (minPrice) url.searchParams.set('min_price', minPrice);
            if (maxPrice) url.searchParams.set('max_price', maxPrice);
            window.location.href = url.toString();
        });
    }
}

function initProductDetailPage() {
    // Gallery logic
    const mainImage = document.getElementById('mainImage');
    const thumbnails = document.querySelectorAll('.thumbnail-grid img');
    function updateMainImage(thumbnailElement) {
        if (mainImage && thumbnailElement) {
            mainImage.src = thumbnailElement.src;
            mainImage.alt = thumbnailElement.alt.replace('View', 'Main view');
            thumbnails.forEach(img => img.classList.remove('active'));
            thumbnailElement.classList.add('active');
        }
    }
    window.updateMainImage = updateMainImage;
    if (thumbnails.length > 0) {
        thumbnails.forEach(img => {
            img.addEventListener('click', function() { updateMainImage(this); });
        });
    }
    // Quantity selector
    const quantityInput = document.querySelector('.quantity-selector input');
    if (quantityInput) {
        const quantityMax = parseInt(quantityInput.getAttribute('max') || '99');
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                if (isNaN(value)) value = 1;
                if (this.classList.contains('plus')) {
                    if (value < quantityMax) quantityInput.value = value + 1;
                    else quantityInput.value = quantityMax;
                } else if (this.classList.contains('minus')) {
                    if (value > 1) quantityInput.value = value - 1;
                }
            });
        });
    }
    // Tab switching
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            tabBtns.forEach(b => b.classList.remove('active', 'text-primary', 'border-primary'));
            tabBtns.forEach(b => b.classList.add('text-gray-500', 'border-transparent'));
            this.classList.add('active', 'text-primary', 'border-primary');
            this.classList.remove('text-gray-500', 'border-transparent');
            tabPanes.forEach(pane => {
                if (pane.id === tabId) {
                    pane.classList.add('active');
                    pane.classList.remove('hidden');
                } else {
                    pane.classList.remove('active');
                    pane.classList.add('hidden');
                }
            });
        });
    });
    // Ensure initial active tab's pane is visible
    const initialActiveTab = document.querySelector('.tab-btn.active');
    if(initialActiveTab) {
        const initialTabId = initialActiveTab.dataset.tab;
        tabPanes.forEach(pane => {
            if (pane.id === initialTabId) {
                pane.classList.add('active');
                pane.classList.remove('hidden');
            } else {
                pane.classList.remove('active');
                pane.classList.add('hidden');
            }
        });
    }
    // Add-to-cart AJAX for main form and related products handled globally
}

function initCartPage() {
    const cartForm = document.getElementById('cartForm');
    if (!cartForm) return;
    // Quantity buttons
    document.querySelectorAll('.quantity-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            let value = parseInt(input.value);
            if (this.classList.contains('plus')) {
                if (value < 99) input.value = value + 1;
            } else {
                if (value > 1) input.value = value - 1;
            }
            input.dispatchEvent(new Event('change'));
        });
    });
    // Quantity input changes
    document.querySelectorAll('.item-quantity input').forEach(input => {
        input.addEventListener('change', function() {
            updateCartItem(this.closest('.cart-item'));
        });
    });
    // Remove item buttons
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            fetch('index.php?page=cart&action=remove', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.closest('.cart-item').remove();
                    updateCartTotal();
                    updateCartCount(data.cartCount);
                    if (data.cartCount === 0) {
                        location.reload();
                    }
                    showFlashMessage(data.message || 'Product removed from cart', 'success');
                } else {
                    showFlashMessage(data.message || 'Error removing item', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFlashMessage('Error removing item', 'error');
            });
        });
    });
    // Update cart total
    function updateCartTotal() {
        let total = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            const price = parseFloat(item.querySelector('.item-price').textContent.replace('$', ''));
            const quantity = parseInt(item.querySelector('.item-quantity input').value);
            total += price * quantity;
            item.querySelector('.item-subtotal').textContent = '$' + (price * quantity).toFixed(2);
        });
        document.querySelector('.summary-row.total span:last-child').textContent = '$' + total.toFixed(2);
    }
    // Update cart count in header
    function updateCartCount(count) {
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            if (count > 0) {
                cartCount.textContent = count;
                cartCount.style.display = 'inline';
            } else {
                cartCount.style.display = 'none';
            }
        }
    }
    // Form submission
    cartForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('index.php?page=cart&action=update', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartTotal();
                updateCartCount(data.cartCount);
                showFlashMessage(data.message || 'Cart updated', 'success');
            } else {
                showFlashMessage(data.message || 'Error updating cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showFlashMessage('Error updating cart', 'error');
        });
    });
}

function initLoginPage() {
    const form = document.getElementById('loginForm');
    const submitButton = document.getElementById('submitButton');
    // Password visibility toggle
    const toggleBtn = document.querySelector('.toggle-password');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
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
    }
    // Form loading state
    if (form) {
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
    }
}

function initRegisterPage() {
    const form = document.getElementById('registerForm');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const submitButton = document.getElementById('submitButton');
    const requirements = {
        length: { regex: /.{12,}/, element: document.getElementById('length') },
        uppercase: { regex: /[A-Z]/, element: document.getElementById('uppercase') },
        lowercase: { regex: /[a-z]/, element: document.getElementById('lowercase') },
        number: { regex: /[0-9]/, element: document.getElementById('number') },
        special: { regex: /[@$!%*?&]/, element: document.getElementById('special') },
        match: { element: document.getElementById('match') }
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
    if (password && confirmPassword) {
        password.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validatePassword);
    }
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
    if (form) {
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
    }
}

function initForgotPasswordPage() {
    const form = document.getElementById('forgotPasswordForm');
    const submitButton = document.getElementById('submitButton');
    const buttonText = submitButton?.querySelector('.button-text');
    const buttonLoader = submitButton?.querySelector('.button-loader');
    if (form) {
        form.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            if (!email) {
                e.preventDefault();
                return;
            }
            if (buttonText && buttonLoader) {
                buttonText.classList.add('hidden');
                buttonLoader.classList.remove('hidden');
                submitButton.disabled = true;
            }
        });
    }
}

function initResetPasswordPage() {
    const form = document.getElementById('resetPasswordForm');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirm');
    const submitButton = document.getElementById('submitButton');
    const requirements = {
        length: { regex: /.{8,}/, element: document.getElementById('length') },
        uppercase: { regex: /[A-Z]/, element: document.getElementById('uppercase') },
        lowercase: { regex: /[a-z]/, element: document.getElementById('lowercase') },
        number: { regex: /[0-9]/, element: document.getElementById('number') },
        special: { regex: /[@$!%*?&]/, element: document.getElementById('special') },
        match: { element: document.getElementById('match') }
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
    if (password && confirmPassword) {
        password.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validatePassword);
    }
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
    if (form) {
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
    }
}

function initQuizPage() {
    // Initialize particles
    if (window.particlesJS) {
        particlesJS.load('particles-js', '/particles.json');
    }
    // Handle option selection
    const options = document.querySelectorAll('.quiz-option');
    options.forEach(option => {
        option.addEventListener('click', () => {
            options.forEach(opt => opt.querySelector('div').classList.remove('border-primary', 'bg-primary/5'));
            option.querySelector('div').classList.add('border-primary', 'bg-primary/5');
        });
    });
    // Smooth scroll/validation on submit
    const quizForm = document.getElementById('scent-quiz');
    if (quizForm) {
        quizForm.addEventListener('submit', (e) => {
            e.preventDefault();
            if (!quizForm.mood.value) {
                alert('Please select an option to continue.');
                return;
            }
            quizForm.submit();
        });
    }
}
function initQuizResultsPage() {
    // Initialize particles
    if (window.particlesJS) {
        particlesJS.load('particles-js', '/particles.json');
    }
    // Initialize AOS
    if (window.AOS) {
        AOS.init({ duration: 800, offset: 100, once: true });
    }
}
function initAdminQuizAnalyticsPage() {
    // Chart.js is loaded via CDN in admin_header.php or should be loaded globally
    let charts = {};
    function updateAnalytics() {
        const timeRange = document.getElementById('timeRange').value;
        fetchAnalyticsData(timeRange);
    }
    async function fetchAnalyticsData(timeRange) {
        try {
            const response = await fetch(`index.php?page=admin&action=quiz_analytics&range=${timeRange}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();
            if (!data.success) throw new Error(data.error || 'Failed to fetch analytics');
            updateStatCards(data.data.statistics);
            updateCharts(data.data.preferences);
            updateRecommendationsTable(data.data.recommendations);
        } catch (error) {
            console.error('Error fetching analytics:', error);
            alert('Failed to load analytics data. Please try again.');
        }
    }
    function updateStatCards(stats) {
        document.getElementById('totalParticipants').textContent = stats.total_quizzes;
        document.getElementById('conversionRate').textContent = `${stats.conversion_rate}%`;
        document.getElementById('avgCompletionTime').textContent = `${stats.avg_completion_time}s`;
    }
    function updateCharts(preferences) {
        if (charts.scent) charts.scent.destroy();
        charts.scent = new Chart(document.getElementById('scentChart'), {
            type: 'doughnut',
            data: {
                labels: preferences.scent_types.map(p => p.type),
                datasets: [{
                    data: preferences.scent_types.map(p => p.count),
                    backgroundColor: [
                        '#4299e1','#48bb78','#ed8936','#9f7aea','#f56565']
                }]
            }
        });
        if (charts.mood) charts.mood.destroy();
        charts.mood = new Chart(document.getElementById('moodChart'), {
            type: 'bar',
            data: {
                labels: preferences.mood_effects.map(p => p.effect),
                datasets: [{
                    data: preferences.mood_effects.map(p => p.count),
                    backgroundColor: '#4299e1'
                }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });
        if (charts.completions) charts.completions.destroy();
        charts.completions = new Chart(document.getElementById('completionsChart'), {
            type: 'line',
            data: {
                labels: preferences.daily_completions.map(d => d.date),
                datasets: [{
                    label: 'Completions',
                    data: preferences.daily_completions.map(d => d.count),
                    borderColor: '#4299e1',
                    tension: 0.1
                }]
            }
        });
    }
    function updateRecommendationsTable(recommendations) {
        const tbody = document.getElementById('recommendationsTable');
        tbody.innerHTML = recommendations.map(product => `
            <tr>
                <td>${product.name}</td>
                <td>${product.category}</td>
                <td>${product.recommendation_count}</td>
                <td>${product.conversion_rate}%</td>
                <td>
                    <a href="index.php?page=admin&action=products&id=${product.id}" class="btn-icon" title="View Product">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
        `).join('');
    }
    document.getElementById('timeRange').addEventListener('change', updateAnalytics);
    document.addEventListener('DOMContentLoaded', updateAnalytics);
}
function initAdminCouponsPage() {
    function showCreateCouponForm() {
        document.getElementById('couponForm').classList.remove('hidden');
    }
    function hideCouponForm() {
        document.getElementById('couponForm').classList.add('hidden');
        document.querySelector('#couponForm form').reset();
    }
    function editCoupon(coupon) {
        const form = document.querySelector('#couponForm form');
        form.reset();
        Object.keys(coupon).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'datetime-local' && coupon[key]) {
                    input.value = new Date(coupon[key]).toISOString().slice(0, 16);
                } else {
                    input.value = coupon[key];
                }
            }
        });
        document.getElementById('couponForm').classList.remove('hidden');
    }
    function toggleCouponStatus(couponId) {
        if (confirm('Are you sure you want to toggle this coupon\'s status?')) {
            fetch(`index.php?page=admin&action=coupons&id=${couponId}&toggle=status`, { method: 'POST' })
                .then(() => location.reload());
        }
    }
    function deleteCoupon(couponId) {
        if (confirm('Are you sure you want to delete this coupon?')) {
            fetch(`index.php?page=admin&action=coupons&id=${couponId}&delete=1`, { method: 'POST' })
                .then(() => location.reload());
        }
    }
    window.showCreateCouponForm = showCreateCouponForm;
    window.hideCouponForm = hideCouponForm;
    window.editCoupon = editCoupon;
    window.toggleCouponStatus = toggleCouponStatus;
    window.deleteCoupon = deleteCoupon;
    document.getElementById('discount_type').addEventListener('change', function() {
        const hint = document.getElementById('valueHint');
        hint.textContent = this.value === 'percentage' ? '(Enter percentage between 0-100)' : '(Enter amount in dollars)';
    });
}
document.addEventListener('DOMContentLoaded', function() {
    const body = document.body;
    if (body.classList.contains('page-home')) initHomePage();
    if (body.classList.contains('page-products')) initProductsPage();
    if (body.classList.contains('page-product-detail')) initProductDetailPage();
    if (body.classList.contains('page-cart')) initCartPage();
    if (document.body.classList.contains('page-login')) initLoginPage();
    if (document.body.classList.contains('page-register')) initRegisterPage();
    if (document.body.classList.contains('page-forgot-password')) initForgotPasswordPage();
    if (document.body.classList.contains('page-reset-password')) initResetPasswordPage();
    if (document.body.classList.contains('page-quiz')) initQuizPage();
    if (document.body.classList.contains('page-quiz-results')) initQuizResultsPage();
    if (document.body.classList.contains('page-admin-quiz-analytics')) initAdminQuizAnalyticsPage();
    if (document.body.classList.contains('page-admin-coupons')) initAdminCouponsPage();
});
