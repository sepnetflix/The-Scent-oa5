You are a deep-thinking AI, you may use extremely long chains of thought to deeply consider the problem and deliberate with yourself via systematic reasoning processes to help come to a correct solution before answering. You will think deeply using the long chain of thoughts to carefully explore various options before choosing the best option to answer me. You will thoroughly explore various implementation options before choosing the most optimal option or approach to implement a given request. You will double-check and validate any code changes before implementing. You should enclose your thoughts and internal monologue inside <think> </think> tags, and then provide your solution or response to the problem.

Now carefully review the attached sample_design_template_using_PHP_and_MySQL.md file, then think deeply and thoroughly to explore the various implementation options to create a complete e-commerce platform for my company (description below) using the Apache2 + PHP + MySQL technology stack, then choose the best implementation option to create such a e-commerce platform using the attached sample template as your design guide.

---
Now carefully review the HTML code below for my website landing page, then think deeply and thoroughly to explore the various options to create an improved landing page for my company (description below) before choosing the best option to create a landing page that not only suits my business but also gives the "Wow Effect" (awesome looking and unique)

```  
# Company mission and business  
*The Scent* Story
- promote mental & physical health
- products include various smell of essential oils to choose from and custom natural premium soap
- Our company produces a whole range of aroma therapeutic products where high quality raw materials from all over the world are imported and our finished products are exported back to these countries.  This is possible due to our unique and creative product formulations and our knowledge for the various applications, to create harmonious, balanced and well rounded aromatherapy products.  Stress is an ever-increasing part of our lives, and this can only mean that aromatherapy is more relevant today than ever before.
```  
# company products to showcase:  
https://raw.githubusercontent.com/nordeim/The-Scent/refs/heads/main/images/scent2.jpg  
https://raw.githubusercontent.com/nordeim/The-Scent/refs/heads/main/images/scent4.jpg  
https://raw.githubusercontent.com/nordeim/The-Scent/refs/heads/main/images/scent5.jpg  
https://raw.githubusercontent.com/nordeim/The-Scent/refs/heads/main/images/scent6.jpg  
https://raw.githubusercontent.com/nordeim/The-Scent/refs/heads/main/images/soap2.jpg  
https://raw.githubusercontent.com/nordeim/The-Scent/refs/heads/main/images/soap4.jpg  
https://raw.githubusercontent.com/nordeim/The-Scent/refs/heads/main/images/soap5.jpg  
https://raw.githubusercontent.com/nordeim/The-Scent/refs/heads/main/images/soap6.jpg  

---  
```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>The Scent - Premium Aromatherapy & Natural Well-being</title>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Montserrat:wght@300;400;500&family=Raleway:wght@400;500;600&display=swap" rel="stylesheet">
  <!-- Font Awesome for Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- Link to CSS -->
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <!-- Header -->
  <header class="main-header">
    <div class="container header-container">
      <div class="logo">
        <a href="#">The Scent</a>
        <span>AROMATHERAPY</span>
      </div>
      <nav class="main-nav">
        <ul>
          <li><a href="#hero">Home</a></li>
          <li><a href="#products">Shop</a></li>
          <li><a href="#finder">Scent Finder</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="#contact">Contact</a></li>
        </ul>
      </nav>
      <div class="header-icons">
        <a href="#" aria-label="Search"><i class="fas fa-search"></i></a>
        <a href="#" aria-label="Account"><i class="fas fa-user"></i></a>
        <a href="#" aria-label="Cart"><i class="fas fa-shopping-bag"></i></a>
      </div>
      <button class="mobile-nav-toggle" aria-label="Toggle navigation">
        <i class="fas fa-bars"></i>
      </button>
    </div>
    <!-- Mobile Menu (hidden by default) -->
    <nav class="mobile-nav">
      <ul>
        <li><a href="#hero">Home</a></li>
        <li><a href="#products">Shop</a></li>
        <li><a href="#finder">Scent Finder</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#contact">Contact</a></li>
        <li><a href="#">Search</a></li>
        <li><a href="#">Account</a></li>
        <li><a href="#">Cart</a></li>
      </ul>
    </nav>
  </header>

  <main>
    <!-- Hero Section with Video Background -->
    <section id="hero" class="hero-section">
      <div class="hero-media">
        <!-- Use a video for modern browsers, fallback to image -->
        <video autoplay muted loop playsinline>
          <source src="path-to-your-video.mp4" type="video/mp4">
          <!-- Fallback image -->
          <img src="https://raw.githubusercontent.com/nordeim/The-Scent/refs/heads/main/images/scent5.jpg" alt="Calming Nature">
        </video>
      </div>
      <div class="container hero-content">
        <h1>Find Your Moment of Calm</h1>
        <p>Experience premium, natural aromatherapy crafted to enhance well-being and restore balance.</p>
        <a href="#products" class="btn btn-primary">Explore Our Collections</a>
      </div>
    </section>

    <!-- About/Mission Section -->
    <section id="about" class="about-section">
      <div class="container about-container">
        <div class="about-image">
          <img src="https://raw.githubusercontent.com/nordeim/The-Scent/refs/heads/main/images/scent6.jpg" alt="Natural Ingredients">
        </div>
        <div class="about-text">
          <h2>Rooted in Nature, Crafted with Care</h2>
          <p>At The Scent, we harness the power of nature to nurture your mental and physical well-being. Our high-quality, sustainably sourced ingredients are transformed into exquisite aromatherapy products that help you reclaim balance and serenity.</p>
          <a href="#" class="btn btn-secondary">Learn Our Story</a>
        </div>
      </div>
    </section>

    <!-- Featured Products Section -->
    <section id="products" class="products-section">
      <div class="container">
        <h2>Featured Collections</h2>
        <div class="product-grid">
          <!-- Product 1: Essential Oil -->
          <div class="product-card">
            <img src="https://raw.githubusercontent.com/nordeim/The-Scent/refs/heads/main/images/scent2.jpg" alt="Serenity Blend Oil">
            <div class="product-info">
              <h3>Serenity Blend Oil</h3>
              <p>Calming Lavender & Chamomile</p>
              <a href="#" class="product-link">View Product</a>
            </div>
          </div>
          <!-- Product 2: Soap -->
          <div class="product-card">
            <img src="https://raw.githubusercontent.com/nordeim/The-Scent/refs/heads/main/images/soap4.jpg" alt="Citrus Burst Soap">
            <div class="product-info">
              <h3>Citrus Burst Soap</h3>
              <p>Energizing Lemon & Orange Peel</p>
              <a href="#" class="product-link">View Product</a>
            </div>
          </div>
          <!-- Product 3: Essential Oil -->
          <div class="product-card">
            <img src="https://raw.githubusercontent.com/nordeim/The-Scent/refs/heads/main/images/scent4.jpg" alt="Focus Flow Oil">
            <div class="product-info">
              <h3>Focus Flow Oil</h3>
              <p>Invigorating Rosemary & Mint</p>
              <a href="#" class="product-link">View Product</a>
            </div>
          </div>
          <!-- Product 4: Soap -->
          <div class="product-card">
            <img src="https://raw.githubusercontent.com/nordeim/The-Scent/refs/heads/main/images/soap6.jpg" alt="Woodland Retreat Soap">
            <div class="product-info">
              <h3>Woodland Retreat Soap</h3>
              <p>Grounding Cedarwood & Pine</p>
              <a href="#" class="product-link">View Product</a>
            </div>
          </div>
        </div>
        <div class="view-all-cta">
          <a href="#" class="btn btn-primary">Shop All Products</a>
        </div>
      </div>
    </section>

    <!-- Scent Finder & Interactive Story Section -->
    <section id="finder" class="finder-section">
      <div class="container">
        <h2>Discover Your Perfect Scent</h2>
        <p class="finder-subtitle">Tailor your aromatherapy experience to your mood and needs.</p>
        <div class="finder-grid">
          <div class="finder-card">
            <!-- Replace with custom SVG icon if available -->
            <i class="fas fa-leaf finder-icon"></i>
            <h3>Relaxation</h3>
            <p>Calming scents to help you unwind.</p>
          </div>
          <div class="finder-card">
            <i class="fas fa-bolt finder-icon"></i>
            <h3>Energy</h3>
            <p>Invigorating aromas to uplift your day.</p>
          </div>
          <div class="finder-card">
            <i class="fas fa-brain finder-icon"></i>
            <h3>Focus</h3>
            <p>Clarifying blends for a clear mind.</p>
          </div>
          <div class="finder-card">
            <i class="fas fa-moon finder-icon"></i>
            <h3>Sleep</h3>
            <p>Soothing scents for a peaceful night's rest.</p>
          </div>
          <div class="finder-card">
            <i class="fas fa-balance-scale finder-icon"></i>
            <h3>Balance</h3>
            <p>Harmonious aromas to center you.</p>
          </div>
        </div>
        <div class="finder-cta">
          <a href="#" class="btn btn-secondary">Take the Full Scent Quiz</a>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials-section">
      <div class="container">
        <h2>What Our Community Says</h2>
        <div class="testimonial-grid">
          <div class="testimonial-card">
            <p>"The Lavender Essential Oil transformed my bedtime routine—its calming aroma truly helps me unwind."</p>
            <span class="testimonial-author">- Sarah L., Los Angeles</span>
            <div class="testimonial-rating">★★★★★</div>
          </div>
          <div class="testimonial-card">
            <p>"The Focus Blend oil improved my concentration at home without overwhelming my senses."</p>
            <span class="testimonial-author">- Michael T., Chicago</span>
            <div class="testimonial-rating">★★★★★</div>
          </div>
          <div class="testimonial-card">
            <p>"Handcrafted soaps that feel divine and truly nourish sensitive skin. A luxurious experience."</p>
            <span class="testimonial-author">- Emma R., Seattle</span>
            <div class="testimonial-rating">★★★★★</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Newsletter Section -->
    <section id="newsletter" class="newsletter-section">
      <div class="container newsletter-container">
        <h2>Join Our Community</h2>
        <p>Subscribe for exclusive offers, aromatherapy tips, and first access to new products.</p>
        <form class="newsletter-form">
          <input type="email" placeholder="Your email address" required>
          <button type="submit" class="btn btn-primary">Subscribe</button>
        </form>
        <p class="newsletter-consent">By subscribing, you agree to our Privacy Policy.</p>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer id="contact" class="main-footer">
    <div class="container footer-grid">
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
          <li><a href="#">Essential Oils</a></li>
          <li><a href="#">Natural Soaps</a></li>
          <li><a href="#">Gift Sets</a></li>
          <li><a href="#">New Arrivals</a></li>
          <li><a href="#">Bestsellers</a></li>
        </ul>
      </div>
      <div class="footer-links">
        <h3>Help</h3>
        <ul>
          <li><a href="#">Contact Us</a></li>
          <li><a href="#">FAQs</a></li>
          <li><a href="#">Shipping & Returns</a></li>
          <li><a href="#">Track Your Order</a></li>
          <li><a href="#">Privacy Policy</a></li>
        </ul>
      </div>
      <div class="footer-contact">
        <h3>Contact Us</h3>
        <p><i class="fas fa-map-marker-alt"></i> 123 Aromatherapy Lane, Wellness City, WB 12345</p>
        <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
        <p><i class="fas fa-envelope"></i> hello@thescent.com</p>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="container">
        <p>&copy; 2025 The Scent. All rights reserved.</p>
        <div class="payment-methods">
          <span>Accepted Payments:</span>
          <i class="fab fa-cc-visa"></i>
          <i class="fab fa-cc-mastercard"></i>
          <i class="fab fa-cc-paypal"></i>
          <i class="fab fa-cc-amex"></i>
        </div>
      </div>
    </div>
  </footer>

  <script>
    // Mobile Menu Toggle Script
    const toggleButton = document.querySelector('.mobile-nav-toggle');
    const mobileNav = document.querySelector('.mobile-nav');
    const mainHeader = document.querySelector('.main-header');
  
    if (toggleButton && mobileNav && mainHeader) {
      toggleButton.addEventListener('click', () => {
        mobileNav.classList.toggle('active');
        mainHeader.classList.toggle('mobile-menu-active');
        const icon = toggleButton.querySelector('i');
        icon.classList.toggle('fa-bars');
        icon.classList.toggle('fa-times');
      });
  
      mobileNav.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
          mobileNav.classList.remove('active');
          mainHeader.classList.remove('mobile-menu-active');
          const icon = toggleButton.querySelector('i');
          icon.classList.add('fa-bars');
          icon.classList.remove('fa-times');
        });
      });
    }
  
    // Optional: Add scroll animations here (or integrate a library like AOS)
    window.addEventListener('scroll', () => {
      if (window.scrollY > 50) {
        mainHeader.classList.add('sticky');
      } else {
        mainHeader.classList.remove('sticky');
      }
    });
  </script>
</body>
</html>
```
```
/* style.css */
/* --- Base Styles & Variables --- */
:root {
  --font-heading: 'Cormorant Garamond', serif;
  --font-body: 'Montserrat', sans-serif;
  --font-accent: 'Raleway', sans-serif;

  --color-primary: #1A4D5A; /* Deep Teal */
  --color-secondary: #A0C1B1; /* Soft Mint Green */
  --color-accent: #D4A76A; /* Muted Gold/Ochre */
  --color-background: #F8F5F2; /* Warm Off-White */
  --color-text: #333333;
  --color-text-light: #FFFFFF;
  --color-border: #e0e0e0;

  --container-width: 1200px;
  --spacing-unit: 1rem;
  --transition-speed: 0.3s;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; font-size: 100%; }
body {
  font-family: var(--font-body);
  background-color: var(--color-background);
  color: var(--color-text);
  line-height: 1.7;
  overflow-x: hidden;
}

/* Headings & Paragraphs */
h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-heading);
  font-weight: 600;
  color: var(--color-primary);
  margin-bottom: calc(var(--spacing-unit) * 1);
  line-height: 1.2;
}
h1 { font-size: clamp(2.5rem, 5vw, 4rem); }
h2 { font-size: clamp(1.8rem, 4vw, 2.8rem); }
h3 { font-size: clamp(1.3rem, 3vw, 1.8rem); }
p { margin-bottom: calc(var(--spacing-unit) * 1); max-width: 70ch; }

a { color: var(--color-primary); text-decoration: none; transition: color var(--transition-speed) ease; }
a:hover, a:focus { color: var(--color-accent); }
img { max-width: 100%; display: block; }
ul { list-style: none; }

/* Container */
.container {
  width: 90%;
  max-width: var(--container-width);
  margin: 0 auto;
  padding: 0 var(--spacing-unit);
}

/* Buttons */
.btn {
  display: inline-block;
  font-family: var(--font-accent);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: calc(var(--spacing-unit) * 0.8) calc(var(--spacing-unit) * 2);
  border-radius: 50px;
  cursor: pointer;
  transition: background-color var(--transition-speed) ease, transform var(--transition-speed) ease;
  border: 2px solid transparent;
}
.btn-primary {
  background-color: var(--color-primary);
  color: var(--color-text-light);
  border-color: var(--color-primary);
}
.btn-primary:hover,
.btn-primary:focus {
  background-color: var(--color-accent);
  border-color: var(--color-accent);
  transform: translateY(-2px);
}
.btn-secondary {
  background-color: transparent;
  color: var(--color-primary);
  border-color: var(--color-primary);
}
.btn-secondary:hover,
.btn-secondary:focus {
  background-color: var(--color-primary);
  color: var(--color-text-light);
  transform: translateY(-2px);
}

/* --- Header --- */
.main-header {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 1000;
  padding: calc(var(--spacing-unit) * 1.5) 0;
  background: transparent;
  transition: background-color var(--transition-speed) ease, box-shadow var(--transition-speed) ease, padding var(--transition-speed) ease;
}

/* When sticky, apply a light background and update text colors */
.main-header.sticky {
  position: fixed;
  background-color: rgba(255, 255, 255, 0.95);
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  padding: calc(var(--spacing-unit) * 0.8) 0;
}
.main-header.sticky .logo a,
.main-header.sticky .logo span,
.main-header.sticky .main-nav a,
.main-header.sticky .header-icons a {
  color: var(--color-primary);
}

.header-container { display: flex; justify-content: space-between; align-items: center; }
.logo a {
  font-family: var(--font-heading);
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--color-text-light);
  text-transform: uppercase;
  letter-spacing: 1px;
}
.logo span {
  display: block;
  font-family: var(--font-accent);
  font-size: 0.6rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--color-text-light);
  margin-top: -5px;
  opacity: 0.8;
}
.main-nav ul { display: flex; gap: calc(var(--spacing-unit) * 2); }
.main-nav a {
  font-family: var(--font-accent);
  font-weight: 500;
  color: var(--color-text-light);
  text-transform: uppercase;
  letter-spacing: 1px;
  padding: 5px 0;
  position: relative;
}
.main-nav a::after {
  content: '';
  position: absolute;
  width: 0;
  height: 2px;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  background-color: var(--color-accent);
  transition: width var(--transition-speed) ease;
}
.main-nav a:hover::after, .main-nav a:focus::after { width: 100%; }
.header-icons { display: flex; gap: calc(var(--spacing-unit) * 1.5); }
.header-icons a { color: var(--color-text-light); font-size: 1.2rem; }
.mobile-nav-toggle {
  display: none;
  background: none;
  border: none;
  color: var(--color-text-light);
  font-size: 1.5rem;
  cursor: pointer;
  z-index: 1001;
}
.mobile-nav { display: none; position: absolute; top: 100%; left: 0; width: 100%; background-color: rgba(255, 255, 255, 0.98); box-shadow: 0 5px 15px rgba(0,0,0,0.1); padding: var(--spacing-unit); max-height: 0; overflow: hidden; transition: max-height 0.5s ease-out; }
.mobile-nav.active { display: block; max-height: 500px; }
.main-header.mobile-menu-active { background-color: rgba(255, 255, 255, 0.98); }
.mobile-nav ul { display: flex; flex-direction: column; gap: var(--spacing-unit); }
.mobile-nav a {
  display: block;
  padding: calc(var(--spacing-unit) * 0.5);
  color: var(--color-primary);
  font-family: var(--font-accent);
  text-transform: uppercase;
  text-align: center;
  font-size: 1.1rem;
  transition: background-color var(--transition-speed) ease;
}
.mobile-nav a:hover, .mobile-nav a:focus {
  background-color: var(--color-secondary);
  color: var(--color-primary);
}

/* --- Hero Section --- */
.hero-section {
  position: relative;
  height: 100vh;
  min-height: 600px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  overflow: hidden;
  color: var(--color-text-light);
}
.hero-media {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  overflow: hidden;
  z-index: -2;
}
.hero-media video,
.hero-media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  animation: zoomInOut 25s infinite alternate ease-in-out;
}
.hero-section::before {
  content: '';
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background-color: rgba(0,0,0,0.4);
  z-index: -1;
}
/* Add text-shadow for better readability on the video background */
.hero-content h1 {
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.7);
}
.hero-content {
  position: relative;
  z-index: 1;
  max-width: 800px;
  animation: fadeIn 1.5s ease-out;
}
.hero-content h1 { font-weight: 700; margin-bottom: calc(var(--spacing-unit) * 1); }
.hero-content p { font-size: 1.2rem; margin-bottom: calc(var(--spacing-unit) * 2); max-width: 60ch; margin-left: auto; margin-right: auto; }

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes zoomInOut {
  from { transform: scale(1); }
  to { transform: scale(1.05); }
}

/* --- Generic Section Styling --- */
section { padding: calc(var(--spacing-unit) * 5) 0; }
section:nth-child(odd) { background-color: #fff; }
section h2 { text-align: center; margin-bottom: calc(var(--spacing-unit) * 3); }

/* --- About Section --- */
.about-section { background-color: #fff; }
.about-container { display: grid; grid-template-columns: 1fr 1fr; gap: calc(var(--spacing-unit) * 4); align-items: center; }
.about-image img { border-radius: 8px; box-shadow: 0 10px 20px rgba(0,0,0,0.1); transition: transform var(--transition-speed) ease; }
.about-image img:hover { transform: scale(1.03); }
.about-text h2 { text-align: left; }
.about-text p { margin-bottom: calc(var(--spacing-unit) * 2); }

/* --- Products Section --- */
.product-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: calc(var(--spacing-unit) * 2.5); }
.product-card {
  background-color: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  transition: transform var(--transition-speed) ease, box-shadow var(--transition-speed) ease;
}
.product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
.product-card img { height: 250px; object-fit: cover; transition: opacity var(--transition-speed) ease; }
.product-card:hover img { opacity: 0.85; }
.product-info { padding: calc(var(--spacing-unit) * 1.5); text-align: center; }
.product-info h3 { margin-bottom: calc(var(--spacing-unit) * 0.5); font-size: 1.3rem; }
.product-info p { font-size: 0.9rem; color: #666; margin-bottom: calc(var(--spacing-unit) * 1); }
.product-link {
  font-family: var(--font-accent);
  font-weight: 500;
  color: var(--color-accent);
  text-transform: uppercase;
  font-size: 0.85rem;
  letter-spacing: 0.5px;
  position: relative;
  padding-bottom: 3px;
}
.product-link::after {
  content: '';
  position: absolute;
  width: 0;
  height: 1px;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  background-color: var(--color-accent);
  transition: width var(--transition-speed) ease;
}
.product-card:hover .product-link::after { width: 50%; }
.view-all-cta { text-align: center; margin-top: calc(var(--spacing-unit) * 3); }

/* --- Scent Finder Section --- */
.finder-section {
  background-color: var(--color-secondary);
  color: var(--color-primary);
}
.finder-section h2 { color: var(--color-primary); }
.finder-subtitle { text-align: center; margin: calc(var(--spacing-unit) * -2) 0 calc(var(--spacing-unit) * 3) 0; opacity: 0.9; }
.finder-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: calc(var(--spacing-unit) * 2); }
.finder-card {
  background-color: rgba(255,255,255,0.5);
  padding: calc(var(--spacing-unit) * 2) calc(var(--spacing-unit) * 1.5);
  border-radius: 8px;
  text-align: center;
  transition: background-color var(--transition-speed) ease, transform var(--transition-speed) ease;
  cursor: pointer;
}
.finder-card:hover { background-color: rgba(255,255,255,0.8); transform: translateY(-5px); }
.finder-icon { font-size: 2.5rem; color: var(--color-primary); margin-bottom: var(--spacing-unit); display: block; }
.finder-card h3 { font-size: 1.2rem; margin-bottom: calc(var(--spacing-unit) * 0.5); }
.finder-card p { font-size: 0.9rem; line-height: 1.5; color: var(--color-text); margin-bottom: 0; }
.finder-cta { text-align: center; margin-top: calc(var(--spacing-unit) * 3); }
.finder-cta .btn-secondary { border-color: var(--color-primary); }
.finder-cta .btn-secondary:hover { background-color: var(--color-primary); color: var(--color-text-light); }

/* --- Testimonials Section --- */
.testimonials-section { background-color: #fff; }
.testimonial-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: calc(var(--spacing-unit) * 2); }
.testimonial-card {
  background-color: var(--color-background);
  padding: calc(var(--spacing-unit) * 2);
  border-radius: 8px;
  border-left: 5px solid var(--color-secondary);
  box-shadow: 0 4px 10px rgba(0,0,0,0.03);
}
.testimonial-card p { font-style: italic; margin-bottom: var(--spacing-unit); color: #555; }
.testimonial-author { font-weight: 500; color: var(--color-primary); margin-bottom: calc(var(--spacing-unit) * 0.5); }
.testimonial-rating { color: var(--color-accent); font-size: 1.1rem; }

/* --- Newsletter Section --- */
.newsletter-section {
  background-color: var(--color-primary);
  color: var(--color-text-light);
  padding: calc(var(--spacing-unit) * 4) 0;
}
.newsletter-section h2 { color: var(--color-text-light); }
.newsletter-container { text-align: center; max-width: 700px; }
.newsletter-container p { opacity: 0.9; margin-bottom: calc(var(--spacing-unit) * 1.5); }
.newsletter-form { display: flex; justify-content: center; gap: var(--spacing-unit); margin-bottom: var(--spacing-unit); flex-wrap: wrap; }
.newsletter-form input[type="email"] {
  padding: calc(var(--spacing-unit) * 0.8);
  border: 1px solid var(--color-secondary);
  border-radius: 50px;
  font-family: var(--font-body);
  min-width: 300px;
  flex-grow: 1;
}
.newsletter-form .btn {
  background-color: var(--color-accent);
  color: var(--color-primary);
  border-color: var(--color-accent);
}
.newsletter-form .btn:hover {
  background-color: var(--color-secondary);
  border-color: var(--color-secondary);
  color: var(--color-primary);
}
.newsletter-consent { font-size: 0.8rem; opacity: 0.7; margin-bottom: 0; }
.newsletter-consent a { color: var(--color-secondary); text-decoration: underline; }
.newsletter-consent a:hover { color: var(--color-text-light); }

/* --- Footer --- */
.main-footer {
  background-color: #2f3d41;
  color: #ccc;
  padding-top: calc(var(--spacing-unit) * 4);
  font-size: 0.9rem;
}
.footer-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: calc(var(--spacing-unit) * 3);
  margin-bottom: calc(var(--spacing-unit) * 3);
}
.footer-about h3, .footer-links h3, .footer-contact h3 {
  font-family: var(--font-accent);
  color: var(--color-text-light);
  font-weight: 600;
  margin-bottom: calc(var(--spacing-unit) * 1.2);
  font-size: 1.1rem;
}
.footer-about p { line-height: 1.6; margin-bottom: var(--spacing-unit); }
.social-icons { display: flex; gap: var(--spacing-unit); }
.social-icons a { color: #ccc; font-size: 1.2rem; transition: color var(--transition-speed) ease, transform var(--transition-speed) ease; }
.social-icons a:hover { color: var(--color-accent); transform: scale(1.1); }
.footer-links ul li { margin-bottom: calc(var(--spacing-unit) * 0.5); }
.footer-links a { color: #ccc; }
.footer-links a:hover { color: var(--color-text-light); text-decoration: underline; }
.footer-contact p { margin-bottom: calc(var(--spacing-unit) * 0.6); display: flex; align-items: center; gap: calc(var(--spacing-unit) * 0.5); }
.footer-contact i { color: var(--color-secondary); width: 16px; text-align: center; }
.footer-bottom { background-color: #222b2e; padding: calc(var(--spacing-unit) * 1.5) 0; margin-top: calc(var(--spacing-unit) * 2); }
.footer-bottom .container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--spacing-unit);
  font-size: 0.85rem;
}
.payment-methods { display: flex; align-items: center; gap: calc(var(--spacing-unit) * 0.8); }
.payment-methods span { margin-right: calc(var(--spacing-unit) * 0.5); }
.payment-methods i { font-size: 1.8rem; color: #aaa; }

/* --- Responsive Design --- */
@media (max-width: 992px) {
  .header-container { padding: 0 calc(var(--spacing-unit) * 1.5); }
  .main-nav, .header-icons { display: none; }
  .mobile-nav-toggle { display: block; }
  .about-container { grid-template-columns: 1fr; text-align: center; }
  .about-image { margin-bottom: calc(var(--spacing-unit) * 2); order: -1; }
  .footer-grid { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
}
@media (max-width: 576px) {
  h1 { font-size: 2rem; }
  h2 { font-size: 1.6rem; }
  h3 { font-size: 1.2rem; }
  .hero-section { min-height: 500px; }
  .hero-content p { font-size: 1rem; }
  .btn { padding: calc(var(--spacing-unit) * 0.7) calc(var(--spacing-unit) * 1.5); font-size: 0.9rem; }
  section { padding: calc(var(--spacing-unit) * 3) 0; }
  section h2 { margin-bottom: calc(var(--spacing-unit) * 2); }
  .product-grid { grid-template-columns: 1fr; gap: calc(var(--spacing-unit) * 1.5); }
  .finder-grid { grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: var(--spacing-unit); }
  .finder-card { padding: calc(var(--spacing-unit) * 1.5) var(--spacing-unit); }
  .testimonial-grid { grid-template-columns: 1fr; }
  .newsletter-form { flex-direction: column; align-items: stretch; }
  .newsletter-form input[type="email"] { width: 100%; }
  .newsletter-form .btn { width: 100%; }
  .footer-grid { grid-template-columns: 1fr; text-align: center; }
  .social-icons { justify-content: center; margin-top: var(--spacing-unit); }
  .footer-bottom .container { flex-direction: column; text-align: center; }
  .payment-methods { margin-top: var(--spacing-unit); }
}
```
