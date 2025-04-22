</main>
    <footer>
        <div class="container">
            <div class="footer-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:3rem;margin-bottom:3rem;">
                <div class="footer-about">
                    <h3>About The Scent</h3>
                    <p>Creating premium aromatherapy products to enhance mental and physical well-being through the power of nature.</p>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Pinterest"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                <div class="footer-links">
                    <h3>Shop</h3>
                    <ul>
                        <li><a href="index.php?page=products">Essential Oils</a></li>
                        <li><a href="index.php?page=products">Natural Soaps</a></li>
                        <li><a href="index.php?page=products">Gift Sets</a></li>
                        <li><a href="index.php?page=products">New Arrivals</a></li>
                        <li><a href="index.php?page=products">Bestsellers</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h3>Help</h3>
                    <ul>
                        <li><a href="index.php?page=contact">Contact Us</a></li>
                        <li><a href="index.php?page=faq">FAQs</a></li>
                        <li><a href="index.php?page=shipping">Shipping & Returns</a></li>
                        <li><a href="index.php?page=order-tracking">Track Your Order</a></li>
                        <li><a href="index.php?page=privacy">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Aromatherapy Lane, Wellness City, WB 12345</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> hello@thescent.com</p>
                    <form id="newsletter-form-footer" class="newsletter-form" style="margin-top:1rem;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="email" name="email" placeholder="Enter your email" required class="newsletter-input">
                        <button type="submit" class="btn btn-primary newsletter-btn">Subscribe</button>
                    </form>
                    <p class="newsletter-consent" style="font-size:0.8rem;opacity:0.7; margin-top:1rem;">By subscribing, you agree to our <a href="index.php?page=privacy" style="color:#A0C1B1; text-decoration:underline;">Privacy Policy</a> and consent to receive emails from The Scent.</p>
                </div>
            </div>
            <div class="footer-bottom" style="background-color:#222b2e; padding:1.5rem 0; margin-top:2rem;">
                <div class="container" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;font-size:0.85rem;">
                    <p>&copy; <?= date('Y') ?> The Scent. All rights reserved.</p>
                    <div class="payment-methods" style="display:flex;align-items:center;gap:0.8rem;">
                        <span>Accepted Payments:</span>
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-paypal"></i>
                        <i class="fab fa-cc-amex"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        // Initialize AOS
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                offset: 100,
                once: true
            });

            if (document.getElementById('particles-js')) {
                particlesJS.load('particles-js', '/particles.json');
            }
        });

        // Canonical flash message helper
        window.showFlashMessage = function(message, type = 'info') {
            let flashContainer = document.querySelector('.flash-message-container');
            if (!flashContainer) {
                flashContainer = document.createElement('div');
                flashContainer.className = 'flash-message-container fixed top-5 right-5 z-[1100] max-w-sm w-full';
                document.body.appendChild(flashContainer);
            }
            const flashDiv = document.createElement('div');
            const colorMap = {
                success: 'bg-green-100 border-green-400 text-green-700',
                error: 'bg-red-100 border-red-400 text-red-700',
                info: 'bg-blue-100 border-blue-400 text-blue-700',
                warning: 'bg-yellow-100 border-yellow-400 text-yellow-700'
            };
            flashDiv.className = `flash-message border px-4 py-3 rounded relative shadow-md mb-2 ${colorMap[type] || colorMap['info']}`;
            flashDiv.setAttribute('role', 'alert');
            const messageSpan = document.createElement('span');
            messageSpan.className = 'block sm:inline';
            messageSpan.textContent = message;
            flashDiv.appendChild(messageSpan);
            const closeButton = document.createElement('span');
            closeButton.className = 'absolute top-0 bottom-0 right-0 px-4 py-3';
            closeButton.innerHTML = '&times;';
            closeButton.onclick = () => flashDiv.remove();
            flashDiv.appendChild(closeButton);
            flashContainer.appendChild(flashDiv);
            setTimeout(() => {
                if (flashDiv) {
                    flashDiv.style.opacity = '0';
                    flashDiv.style.transition = 'opacity 0.5s ease-out';
                    setTimeout(() => flashDiv.remove(), 500);
                }
            }, 5000);
        };

        // Canonical Add-to-Cart handler (event delegation)
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.add-to-cart');
            if (!btn) return;
            e.preventDefault();
            if (btn.disabled) return;
            const productId = btn.dataset.productId;
            const csrfTokenInput = document.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';
            if (!csrfToken) {
                showFlashMessage('Security token missing. Please refresh.', 'error');
                return;
            }
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = 'Adding...';
            fetch('index.php?page=cart&action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${encodeURIComponent(productId)}&quantity=1&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                        cartCount.style.display = data.cart_count > 0 ? 'inline' : 'none';
                    }
                    showFlashMessage(data.message || 'Product added to cart', 'success');
                    if (data.stock_status === 'out_of_stock') {
                        btn.disabled = true;
                        btn.classList.remove('btn-secondary');
                        btn.classList.add('btn-disabled');
                        btn.textContent = 'Out of Stock';
                    } else if (data.stock_status === 'low_stock') {
                        showFlashMessage('Limited quantity available', 'info');
                        btn.dataset.lowStock = 'true';
                        btn.textContent = originalText;
                        btn.disabled = false;
                    } else {
                        btn.textContent = originalText;
                        btn.disabled = false;
                    }
                } else {
                    showFlashMessage(data.message || 'Error adding to cart', 'error');
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFlashMessage('Error adding to cart', 'error');
                btn.textContent = originalText;
                btn.disabled = false;
            });
        });
    </script>
</body>
</html>
