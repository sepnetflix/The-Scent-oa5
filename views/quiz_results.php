<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-quiz-results">
<div class="min-h-screen bg-gradient-to-br from-primary/5 to-secondary/5 py-20">
    <!-- Particles Background -->
    <div id="particles-js" class="absolute inset-0 z-0"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-4xl mx-auto">
            <!-- Results Header -->
            <div class="text-center mb-12" data-aos="fade-down">
                <h1 class="text-4xl font-heading font-semibold mb-4">Your Perfect Scent Match</h1>
                <p class="text-xl text-gray-600">Based on your preferences, we've curated these perfect matches for you.</p>
            </div>

            <!-- Product Recommendations -->
            <div class="grid md:grid-cols-3 gap-8 mb-12">
                <?php if (!isset($products) || !is_array($products)): $products = []; endif; ?>
                <?php foreach ($products as $product): ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden" data-aos="fade-up">
                        <div class="aspect-w-1 aspect-h-1">
                            <img 
                                src="<?= htmlspecialchars($product['image']) ?>" 
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                class="w-full h-full object-cover"
                                loading="lazy"
                            >
                        </div>
                        <div class="p-6">
                            <h3 class="font-heading text-xl mb-2"><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="text-gray-600 text-sm mb-4"><?= htmlspecialchars($product['description']) ?></p>
                            <div class="flex justify-between items-center">
                                <span class="text-primary font-semibold">$<?= number_format($product['price'], 2) ?></span>
                                <a 
                                    href="index.php?page=product&id=<?= $product['id'] ?>" 
                                    class="btn-primary text-sm"
                                >
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Action Buttons -->
            <div class="text-center space-x-4" data-aos="fade-up">
                <a href="index.php?page=quiz" class="btn-secondary">
                    Retake Quiz
                </a>
                <a href="index.php?page=products" class="btn-primary">
                    Shop All Products
                </a>
            </div>

            <!-- Newsletter Signup -->
            <div class="mt-16 bg-white rounded-xl shadow-lg p-8 text-center" data-aos="fade-up">
                <h3 class="font-heading text-2xl mb-4">Stay Updated</h3>
                <p class="text-gray-600 mb-6">Sign up for our newsletter to receive personalized aromatherapy tips and exclusive offers.</p>
                
                <form action="index.php?page=newsletter&action=subscribe" method="POST" class="flex flex-col md:flex-row gap-4 justify-center">
                    <input 
                        type="email" 
                        name="email" 
                        placeholder="Enter your email address"
                        class="flex-1 max-w-md px-4 py-2 rounded-lg border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none"
                        required
                    >
                    <button type="submit" class="btn-primary whitespace-nowrap">
                        Subscribe Now
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>