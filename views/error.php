<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-error">
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
<?php require_once __DIR__ . '/layout/footer.php'; ?>