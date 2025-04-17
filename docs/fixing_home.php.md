Okay, I understand. We need to merge the "Add to Cart" button functionality from the original `home.php.txt` back into the featured products section of the `home-fixed.php.txt`, while keeping the updated card styling and other fixes.

Here's the plan:

1.  Identify the "Add to Cart" / "Out of Stock" button block (including the `if/else` for stock quantity) in the original `home.php.txt`.
2.  Identify the product card loop in the `home-fixed.php.txt`.
3.  Insert the button block into the `.product-info` div of the fixed card structure. It makes sense to group the "View Details" (which was styled as a link) and "Add to Cart" buttons together, perhaps using the original `.product-actions` div structure for clarity and applying consistent button styling (`btn btn-primary`, `btn btn-secondary`).
4.  Ensure the necessary `data-product-id` and conditional `data-low-stock` attributes are retained on the button.
5.  Generate the complete `home.php` file incorporating this change.

```php
<?php 
// home.php (v2 - Merged Add-to-Cart back)
require_once __DIR__ . '/layout/header.php'; // Uses header-fixed.php content implicitly
echo '<!-- DEBUG: home.php loaded -->';
$delay = 0; // Initialize delay counter for animations
?>

<!-- Hero Section with Video Background -->
<section class="hero-section relative min-h-screen flex items-center justify-center overflow-hidden">
    <div id="particles-js" class="absolute inset-0 z-0"></div>
    <video class="absolute inset-0 w-full h-full object-cover z-[-1]" autoplay muted loop playsinline poster="/public/images/scent5.jpg">
        <source src="/public/videos/hero.mp4" type="video/mp4">
        <img src="/public/images/scent5.jpg" alt="Calming Nature" class="w-full h-full object-cover" />
    </video>
    <div class="absolute inset-0 bg-gradient-to-br from-primary/70 to-primary-dark/80 z-10"></div>
    <div class="container relative z-20 flex flex-col items-center justify-center text-center text-white px-6">
        <div data-aos="fade-down">
            <h1 class="text-4xl md:text-5xl font-bold mb-6 font-heading" style="text-shadow: 0 2px 4px rgba(0,0,0,0.7);">Find Your Moment of Calm</h1>
            <p class="text-lg md:text-xl mb-8 max-w-2xl mx-auto font-body">Experience premium, natural aromatherapy crafted to enhance well-being and restore balance.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#featured-products" class="btn btn-primary">Explore Our Collections</a>
                <!-- Note: Original home.php had different buttons here, this matches home-fixed.php -->
            </div>
        </div>
    </div>
</section>

<!-- About/Mission Section (Moved up as per home-fixed.php) -->
<section class="about-section py-20 bg-white" id="about">
    <div class="container">
        <div class="about-container grid md:grid-cols-2 gap-12 items-center">
            <div class="about-image" data-aos="fade-left">
                <img src="<?= file_exists($_SERVER['DOCUMENT_ROOT'] . '/public/images/about/about.jpg') ? '/public/images/about/about.jpg' : 'https://placehold.co/800x600/e9ecef/495057?text=About+The+Scent' ?>"
                     alt="About The Scent" 
                     class="rounded-lg shadow-xl w-full">
            </div>
            <div class="about-content" data-aos="fade-right">
                <h2 class="text-3xl font-bold mb-6">Rooted in Nature, Crafted with Care</h2>
                <p class="mb-6">At The Scent, we harness the power of nature to nurture your mental and physical well-being. Our high-quality, sustainably sourced ingredients are transformed into exquisite aromatherapy products by expert hands.</p>
                <p class="mb-6">Our unique and creative formulations are crafted with expertise to create harmonious, balanced, and well-rounded aromatherapy products that enhance both mental and physical health.</p>
                <a href="index.php?page=about" class="btn btn-secondary">Learn Our Story</a>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="featured-section py-16 bg-light" id="featured-products">
    <div class="container mx-auto text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-12" data-aos="fade-up">Featured Collections</h2>
        <div class="featured-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 px-6">
            <?php if (!empty($featuredProducts)): ?>
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="product-card sample-card" data-aos="zoom-in" style="border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.05); overflow:hidden;">
                        <img src="<?= htmlspecialchars($product['image'] ?? '/public/images/placeholder.jpg') ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             class="w-full h-64 object-cover" loading="lazy">
                        <div class="product-info" style="padding:1.5rem; text-align:center;">
                            <h3 style="margin-bottom:0.5rem; font-size:1.3rem;"><?= htmlspecialchars($product['name']) ?></h3>
                            
                            <!-- Short Description / Category -->
                            <?php if (!empty($product['short_description'])): ?>
                                <p style="font-size:0.9rem; color:#666; margin-bottom:1rem;"><?= htmlspecialchars($product['short_description']) ?></p>
                            <?php elseif (!empty($product['category_name'])): ?>
                                <p style="font-size:0.9rem; color:#666; margin-bottom:1rem;"><?= htmlspecialchars($product['category_name']) ?></p>
                            <?php endif; ?>

                            <!-- Product Actions: View Details and Add to Cart -->
                            <div class="product-actions flex gap-2 justify-center mt-4">
                                <a href="index.php?page=product&id=<?= $product['id'] ?>" class="btn btn-primary">View Details</a> 
                                
                                <!-- Merged Add-to-Cart block from original home.php -->
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <button class="btn btn-secondary add-to-cart" 
                                            data-product-id="<?= $product['id'] ?>"
                                            <?= isset($product['low_stock_threshold']) && $product['stock_quantity'] <= $product['low_stock_threshold'] ? 'data-low-stock="true"' : '' ?>>
                                        Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-disabled" disabled>Out of Stock</button>
                                <?php endif; ?>
                                <!-- End of Merged Block -->
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center text-gray-600">
                    <p>Discover our curated collection of premium aromatherapy products.</p>
                    <a href="index.php?page=products" class="inline-block mt-4 text-primary hover:underline">Browse All Products</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="view-all-cta" style="text-align:center; margin-top:3rem;">
            <a href="index.php?page=products" class="btn btn-primary">Shop All Products</a>
        </div>
    </div>
</section>

<!-- Benefits Section -->
<section class="py-20 bg-white">
    <div class="container">
        <h2 class="text-3xl font-bold text-center mb-12" data-aos="fade-up">Why Choose The Scent</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="0">
                <i class="fas fa-leaf text-4xl text-primary mb-4"></i>
                <h3 class="text-xl font-semibold mb-4">Natural Ingredients</h3>
                <p>Premium quality raw materials sourced from around the world.</p>
            </div>
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-heart text-4xl text-primary mb-4"></i>
                <h3 class="text-xl font-semibold mb-4">Wellness Focus</h3>
                <p>Products designed to enhance both mental and physical well-being.</p>
            </div>
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-certificate text-4xl text-primary mb-4"></i>
                <h3 class="text-xl font-semibold mb-4">Expert Crafted</h3>
                <p>Unique formulations created by aromatherapy experts.</p>
            </div>
        </div>
    </div>
</section>

<!-- Quiz/Finder Section -->
<section class="quiz-section py-20 bg-light" id="finder">
    <div class="container">
        <h2 class="text-3xl font-bold text-center mb-8" data-aos="fade-up">Discover Your Perfect Scent</h2>
        <p class="text-center mb-12 text-lg" data-aos="fade-up" data-aos-delay="100">Tailor your aromatherapy experience to your mood and needs.</p>
        <div class="grid md:grid-cols-5 gap-6 mb-8 finder-grid">
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="0">
                <i class="fas fa-leaf text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Relaxation</h3>
                <p class="text-sm text-gray-600 text-center">Calming scents to help you unwind.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-bolt text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Energy</h3>
                <p class="text-sm text-gray-600 text-center">Invigorating aromas to uplift your day.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-brain text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Focus</h3>
                <p class="text-sm text-gray-600 text-center">Clarifying blends for a clear mind.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="300">
                <i class="fas fa-moon text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Sleep</h3>
                <p class="text-sm text-gray-600 text-center">Soothing scents for a peaceful night's rest.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="400">
                <i class="fas fa-balance-scale text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Balance</h3>
                <p class="text-sm text-gray-600 text-center">Harmonious aromas to center you.</p>
            </div>
        </div>
        <div class="text-center" data-aos="fade-up" data-aos-delay="500">
            <a href="index.php?page=quiz" class="btn btn-secondary">Take the Full Scent Quiz</a>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="newsletter-section py-20 bg-light" id="newsletter">
    <div class="container">
        <div class="max-w-2xl mx-auto text-center" data-aos="fade-up">
            <h2 class="text-3xl font-bold mb-6">Stay Connected</h2>
            <p class="mb-8">Subscribe to receive updates, exclusive offers, and aromatherapy tips.</p>
            <form id="newsletter-form" class="newsletter-form flex flex-col sm:flex-row gap-4 justify-center">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="email" name="email" placeholder="Enter your email" required class="newsletter-input flex-1 px-4 py-2 rounded-full border border-gray-300 focus:border-primary">
                <button type="submit" class="btn btn-primary newsletter-btn">Subscribe</button>
            </form>
            <p class="newsletter-consent" style="font-size:0.8rem;opacity:0.7; margin-top:1rem;">By subscribing, you agree to our <a href="index.php?page=privacy" style="color:#A0C1B1;text-decoration:underline;">Privacy Policy</a>.</p>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-20 bg-white" id="testimonials">
    <div class="container">
        <h2 class="text-3xl font-bold text-center mb-12" data-aos="fade-up">What Our Community Says</h2>
        <div class="testimonial-grid grid md:grid-cols-3 gap-8">
            <div class="testimonial-card bg-light p-8 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="0">
                <p class="mb-4 italic">"The Lavender Essential Oil transformed my bedtime routine—its calming aroma truly helps me unwind."</p>
                <span class="block font-semibold mb-2">- Sarah L., Los Angeles</span>
                <div class="text-accent text-lg">★★★★★</div>
            </div>
            <div class="testimonial-card bg-light p-8 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="100">
                <p class="mb-4 italic">"The Focus Blend oil improved my concentration at home without overwhelming my senses."</p>
                <span class="block font-semibold mb-2">- Michael T., Chicago</span>
                <div class="text-accent text-lg">★★★★★</div>
            </div>
            <div class="testimonial-card bg-light p-8 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="200">
                <p class="mb-4 italic">"Handcrafted soaps that feel divine and truly nourish sensitive skin. A luxurious experience."</p>
                <span class="block font-semibold mb-2">- Emma R., Seattle</span>
                <div class="text-accent text-lg">★★★★★</div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS
    AOS.init({
        duration: 800,
        offset: 100,
        once: true
    });

    // Initialize Particles.js if element exists
    if (document.getElementById('particles-js')) {
        particlesJS.load('particles-js', '/public/particles.json', function() {
            console.log('Particles.js loaded');
        });
    }

    // Handle add to cart buttons (Retained from original home.php JS logic)
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', async function() {
            const productId = this.dataset.productId;
            const isLowStock = this.dataset.lowStock === 'true';
            
            // Ensure CSRF token exists before trying to send
            const csrfTokenInput = document.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';

            if (!csrfToken) {
                 console.error('CSRF token not found!');
                 showFlashMessage('Security token missing. Please refresh.', 'error');
                 return; // Stop if no token
            }

            try {
                const response = await fetch('index.php?page=cart&action=add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        // Sending CSRF token in header might be preferred depending on backend setup
                        // 'X-CSRF-Token': csrfToken 
                    },
                    // Sending CSRF token in body as fallback or if backend expects it here
                    body: `product_id=${productId}&quantity=1&csrf_token=${encodeURIComponent(csrfToken)}` 
                });

                // Check if response is JSON before parsing
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    const data = await response.json();
                    
                    if (data.success) {
                        // Update cart count
                        const cartCount = document.querySelector('.cart-count');
                        if (cartCount) {
                            cartCount.textContent = data.cart_count; // Ensure backend sends 'cart_count'
                        }
                        
                        // Show success message
                        showFlashMessage('Product added to cart', 'success');
                        
                        // Disable button if product is now out of stock
                        if (data.stock_status === 'out_of_stock') {
                            this.disabled = true;
                            this.classList.remove('btn-secondary');
                            this.classList.add('btn-disabled');
                            this.textContent = 'Out of Stock';
                        }
                        
                        // Show low stock warning
                        if (data.stock_status === 'low_stock' && !isLowStock) {
                            showFlashMessage('Limited quantity available', 'info');
                            this.dataset.lowStock = 'true';
                        }
                    } else {
                        showFlashMessage(data.message || 'Error adding to cart', 'error');
                    }
                } else {
                    // Handle non-JSON response (e.g., HTML error page)
                    console.error('Received non-JSON response:', await response.text());
                    showFlashMessage('An unexpected error occurred.', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showFlashMessage('Error adding to cart', 'error');
            }
        });
    });

    // Newsletter form submission
    const newsletterForm = document.getElementById('newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            try {
                const response = await fetch('index.php?page=newsletter&action=subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(formData)
                });

                const data = await response.json();
                
                if (data.success) {
                    this.innerHTML = '<p class="text-success">Thank you for subscribing!</p>';
                } else {
                    showFlashMessage(data.message || 'Subscription failed', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showFlashMessage('Subscription failed', 'error');
            }
        });
    }

    // Flash message helper
    function showFlashMessage(message, type = 'info') {
        const flashDiv = document.createElement('div');
        flashDiv.className = `flash-message ${type}`;
        flashDiv.textContent = message;
        document.body.appendChild(flashDiv);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            flashDiv.style.opacity = '0';
            setTimeout(() => flashDiv.remove(), 300);
        }, 3000);
    }
});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; // Uses footer-fixed.php content implicitly ?>
```

**Changes Made:**

1.  **Featured Products Loop:**
    *   Inside the `div.product-info`, after the description/category `<p>` tag, I added a new `div.product-actions`.
    *   Inside `div.product-actions`, I placed the original "View Details" link, but styled it as a button (`class="btn btn-primary"` for consistency).
    *   Immediately following that link, I inserted the entire `<?php if ($product['stock_quantity'] > 0): ?> ... <?php endif; ?>` block from the original `home.php.txt`, which contains the logic for the "Add to Cart" button (with `data-product-id`, conditional `data-low-stock`, and class `btn btn-secondary add-to-cart`) and the disabled "Out of Stock" button.
    *   Added `mt-4` (margin-top) to the `div.product-actions` for spacing.
    *   Added an `isset()` check around the `low_stock_threshold` condition for robustness.
2.  **JavaScript:**
    *   The JavaScript block at the end already contained the necessary `fetch` logic for the `.add-to-cart` buttons, including CSRF token handling. I added a check to ensure the CSRF token input exists before making the fetch call and added better handling for non-JSON responses which can happen if the server returns an error page instead of JSON.

This version (`home.php` v2) should now reflect the updated UI styling from `home-fixed.php.txt` while retaining the crucial "Add to Cart" functionality within the featured products section.  
https://drive.google.com/file/d/1-5kuPh4yjRESivcIHjet2TzcZgLdabMJ/view?usp=sharing, https://drive.google.com/file/d/10ljWP00UED-cQrIk2qEZFL35YDxRkNtD/view?usp=sharing, https://drive.google.com/file/d/16xALhuoOQesGIjah8EUj8ZKxMq8s3jhp/view?usp=sharing, https://drive.google.com/file/d/1HbCsWr1eXAb9Ro6Q_SFrUZB862kqgwfg/view?usp=sharing, https://drive.google.com/file/d/1JTrtIHrmQaMiZl8Yw_ZDUFVi2t1CIml6/view?usp=sharing, https://drive.google.com/file/d/1KbmgMDovJ-D_3d5EFvwcktcAN9saWhQr/view?usp=sharing, https://drive.google.com/file/d/1OBHNxq7L45YQvvYmCObrCY9SoKf7VvDT/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221Qx-0gbQdFBr673VbrDw5cBYLdlQOB_UJ%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1XWi_-yg_4Q6dG_CYdJ7SvEQThjr-fXh3/view?usp=sharing, https://drive.google.com/file/d/1dhktNkg1G0OcULCMEk0eV7E74cvjoM28/view?usp=sharing, https://drive.google.com/file/d/1hE6Z2n7QH-4Nmo_XRDKFvVvtCOG0YxG5/view?usp=sharing, https://drive.google.com/file/d/1jOZIaaCil1MCtklWadVDPOHzVN9GZ1Sv/view?usp=sharing, https://drive.google.com/file/d/1lMALi5uyYR5Ev0y3U-iXKLcCoZK69S8B/view?usp=sharing
