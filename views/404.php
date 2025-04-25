<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-404">
<section class="error-section">
    <div class="container">
        <div class="error-container" data-aos="fade-up">
            <h1>Page Not Found</h1>
            <div class="error-image">
                <img src="/images/404.svg" alt="404 Not Found" width="300" height="300">
            </div>
            <p>The page you're looking for doesn't exist or has been moved.</p>
            <div class="error-actions">
                <a href="/" class="btn-primary">Return Home</a>
                <a href="javascript:history.back()" class="btn-secondary">Go Back</a>
            </div>
            <div class="error-suggestions">
                <h3>You might want to:</h3>
                <ul>
                    <li><a href="/products">Browse our products</a></li>
                    <li><a href="/quiz">Take our scent quiz</a></li>
                    <li><a href="/contact">Contact us</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/layout/footer.php'; ?>