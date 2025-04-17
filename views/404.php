<?php require_once __DIR__ . '/layout/header.php'; ?>

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

<style>
.error-section {
    padding: 4rem 0;
    min-height: 60vh;
    display: flex;
    align-items: center;
}

.error-container {
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
    padding: 2rem;
}

.error-container h1 {
    font-size: 2.5rem;
    color: #1f2937;
    margin-bottom: 1.5rem;
}

.error-image {
    margin: 2rem 0;
}

.error-image img {
    max-width: 100%;
    height: auto;
}

.error-container p {
    font-size: 1.125rem;
    color: #4b5563;
    margin-bottom: 2rem;
}

.error-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 3rem;
}

.error-suggestions {
    text-align: left;
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
}

.error-suggestions h3 {
    color: #4b5563;
    margin-bottom: 1rem;
    font-size: 1.25rem;
}

.error-suggestions ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.error-suggestions li {
    margin: 0.5rem 0;
}

.error-suggestions a {
    color: #4f46e5;
    text-decoration: none;
    transition: color 0.2s;
}

.error-suggestions a:hover {
    color: #4338ca;
    text-decoration: underline;
}
</style>

<?php require_once __DIR__ . '/layout/footer.php'; ?>