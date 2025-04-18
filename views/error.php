<?php require_once __DIR__ . '/layout/header.php'; ?>

<section class="error-section">
    <div class="container">
        <div class="error-container" data-aos="fade-up">
            <?php if (ENVIRONMENT === 'development' && isset($error)): ?>
                <h1><?= htmlspecialchars($error['type']) ?></h1>
                <div class="error-details">
                    <p class="error-message"><?= htmlspecialchars($error['message']) ?></p>
                    <p class="error-location">
                        File: <?= htmlspecialchars($error['file']) ?><br>
                        Line: <?= htmlspecialchars($error['line']) ?>
                    </p>
                    <?php if (isset($error['trace'])): ?>
                        <div class="error-trace">
                            <h3>Stack Trace:</h3>
                            <pre><?= htmlspecialchars($error['trace']) ?></pre>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <h1>Oops! Something went wrong</h1>
                <p>We apologize for the inconvenience. Please try again later.</p>
                <div class="error-actions">
                    <a href="/" class="btn-primary">Return Home</a>
                    <a href="javascript:history.back()" class="btn-secondary">Go Back</a>
                </div>
            <?php endif; ?>
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
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.error-container h1 {
    color: #dc2626;
    margin-bottom: 1.5rem;
}

.error-details {
    text-align: left;
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 6px;
    margin-top: 1.5rem;
}

.error-message {
    font-size: 1.1rem;
    color: #1f2937;
    margin-bottom: 1rem;
}

.error-location {
    font-family: monospace;
    background: #e5e7eb;
    padding: 0.75rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.error-trace {
    margin-top: 1.5rem;
}

.error-trace h3 {
    color: #4b5563;
    margin-bottom: 0.5rem;
}

.error-trace pre {
    background: #e5e7eb;
    padding: 1rem;
    border-radius: 4px;
    overflow-x: auto;
    font-size: 0.875rem;
}

.error-actions {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
    justify-content: center;
}
</style>

<?php require_once __DIR__ . '/layout/footer.php'; ?>