<?php
// views/about.php
// Set page-specific variables
$pageTitle = 'About Us - The Scent';
$bodyClass = 'page-about bg-light'; // Add a background color via class

require_once __DIR__ . '/layout/header.php'; // Include header
?>

<section class="py-16 md:py-24 text-center bg-secondary/10 border-b border-gray-200" data-aos="fade-in">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl md:text-5xl font-bold font-heading text-primary mb-4">Our Story</h1>
        <p class="text-lg md:text-xl text-gray-700 max-w-3xl mx-auto">Discover the passion and principles behind The Scent.</p>
    </div>
</section>

<section class="py-16 md:py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-12 items-center mb-16 md:mb-24">
            <div data-aos="fade-right">
                <h2 class="text-3xl font-bold font-heading text-primary-dark mb-6">Rooted in Nature, Crafted with Care</h2>
                <p class="text-gray-700 mb-4 leading-relaxed">
                    At The Scent, we believe in the profound power of nature to restore balance and enhance well-being. Our journey began with a simple desire: to harness the purest botanical essences and share their therapeutic benefits with the world.
                </p>
                <p class="text-gray-700 mb-4 leading-relaxed">
                    We meticulously source high-quality, sustainable ingredients from ethical growers globally. Each essential oil, herb, and natural component is selected for its purity, potency, and aromatic profile.
                </p>
                <p class="text-gray-700 leading-relaxed">
                    Our unique and creative formulations are developed by expert aromatherapists, resulting in harmonious, well-rounded products designed to nurture both mind and body.
                </p>
            </div>
            <div class="about-image" data-aos="fade-left">
                <img src="/images/about_hero.jpg" alt="Natural ingredients and essential oils" class="rounded-lg shadow-xl w-full h-auto object-cover aspect-[4/3]">
                <!-- Replace with an actual relevant image if available -->
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-12 items-center">
             <div class="about-image order-last md:order-first" data-aos="fade-right">
                 <img src="/images/about_team.jpg" alt="The Scent Team crafting products" class="rounded-lg shadow-xl w-full h-auto object-cover aspect-[4/3]">
                 <!-- Replace with an actual relevant image if available -->
            </div>
            <div data-aos="fade-left" class="order-first md:order-last">
                <h2 class="text-3xl font-bold font-heading text-primary-dark mb-6">Our Mission & Values</h2>
                <ul class="space-y-4 text-gray-700 leading-relaxed">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-secondary mr-3 mt-1 text-xl"></i>
                        <span><strong>Purity & Quality:</strong> To offer only the finest, 100% natural aromatherapy products without compromise.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-leaf text-secondary mr-3 mt-1 text-xl"></i>
                        <span><strong>Sustainability:</strong> To operate responsibly, respecting the environment and supporting ethical sourcing practices.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-heart text-secondary mr-3 mt-1 text-xl"></i>
                        <span><strong>Well-being:</strong> To empower our customers to find moments of calm, energy, and balance through the power of scent.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-lightbulb text-secondary mr-3 mt-1 text-xl"></i>
                        <span><strong>Expertise & Innovation:</strong> To continually explore and craft unique, effective aromatherapy solutions based on traditional wisdom and modern science.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="py-16 md:py-20 bg-light">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold font-heading text-primary-dark mb-12" data-aos="fade-up">Meet the Team (Optional)</h2>
        <p class="text-gray-700 max-w-2xl mx-auto mb-12" data-aos="fade-up" data-aos-delay="100">
            Behind every blend is a team passionate about natural wellness. (Add team member profiles here if desired).
        </p>
        <!-- Placeholder for team members -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Example Team Member Card -->
            <div class="bg-white p-6 rounded-lg shadow text-center" data-aos="fade-up" data-aos-delay="200">
                <img src="/images/team_placeholder.png" alt="Team Member" class="w-24 h-24 rounded-full mx-auto mb-4 border-2 border-secondary object-cover">
                <h3 class="text-xl font-semibold text-primary mb-1">Jane Doe</h3>
                <p class="text-sm text-accent font-medium mb-2">Lead Aromatherapist</p>
                <p class="text-sm text-gray-600">Passionate about blending traditional knowledge with modern techniques to create effective wellness solutions.</p>
            </div>
            <!-- Add more team members -->
        </div>
    </div>
</section>

<section class="py-16 md:py-20 bg-secondary text-center text-primary-dark" data-aos="fade-in">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold font-heading mb-6">Ready to Find Your Scent?</h2>
        <p class="text-lg mb-8 max-w-xl mx-auto">Take our quick quiz to discover personalized recommendations.</p>
        <a href="index.php?page=quiz" class="btn btn-primary text-lg px-8 py-3 transition duration-300 ease-in-out transform hover:scale-105">Take the Quiz</a>
    </div>
</section>


<?php
require_once __DIR__ . '/layout/footer.php'; // Include footer
?>
