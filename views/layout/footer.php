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
    <script src="/js/main.js"></script>
</body>
</html>
