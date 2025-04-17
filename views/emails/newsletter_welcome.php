<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; }
        .benefits { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .benefit-item { margin: 15px 0; }
        .button { display: inline-block; padding: 10px 20px; background: #4f46e5; color: white; text-decoration: none; border-radius: 5px; }
        .social-links { text-align: center; margin: 20px 0; }
        .social-links a { margin: 0 10px; color: #4f46e5; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to The Scent Newsletter!</h1>
        </div>

        <p>Thank you for joining our aromatherapy community! We're excited to share our passion for natural scents and well-being with you.</p>

        <div class="benefits">
            <h2>What to Expect</h2>
            <div class="benefit-item">
                <strong>🌿 Monthly Newsletter:</strong> Discover aromatherapy tips, new products, and wellness insights.
            </div>
            <div class="benefit-item">
                <strong>🎁 Exclusive Offers:</strong> Get early access to sales and special subscriber-only discounts.
            </div>
            <div class="benefit-item">
                <strong>💡 Expert Advice:</strong> Learn from aromatherapy experts about using essential oils effectively.
            </div>
            <div class="benefit-item">
                <strong>🔔 New Product Alerts:</strong> Be the first to know about our latest aromatherapy products.
            </div>
        </div>

        <p style="text-align: center;">
            <a href="<?= BASE_URL ?>index.php?page=products" class="button">
                Start Shopping
            </a>
        </p>

        <div class="social-links">
            <p>Connect with us:</p>
            <a href="https://facebook.com/thescent">Facebook</a>
            <a href="https://instagram.com/thescent">Instagram</a>
            <a href="https://pinterest.com/thescent">Pinterest</a>
        </div>

        <p style="font-size: 12px; color: #666; margin-top: 30px; text-align: center;">
            You're receiving this email because you subscribed to The Scent newsletter.<br>
            To unsubscribe, <a href="<?= BASE_URL ?>index.php?page=unsubscribe&email=<?= urlencode($email) ?>&token=<?= md5($email . 'unsubscribe-salt') ?>">click here</a>
        </p>
    </div>
</body>
</html>