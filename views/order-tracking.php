<?php require_once __DIR__ . '/layout/header.php'; ?>
<main class="container py-12">
    <h1 class="text-3xl font-heading mb-6">Track Your Order</h1>
    <form method="post" class="max-w-lg bg-white p-6 rounded shadow" action="#">
        <input type="hidden" name="csrf_token" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <div class="mb-4">
            <label for="order_id" class="block font-medium mb-1">Order ID</label>
            <input type="text" id="order_id" name="order_id" class="w-full border rounded px-3 py-2" required>
        </div>
        <div class="mb-4">
            <label for="email" class="block font-medium mb-1">Email</label>
            <input type="email" id="email" name="email" class="w-full border rounded px-3 py-2" required>
        </div>
        <button type="submit" class="btn-primary">Track Order</button>
    </form>
</main>
<?php require_once __DIR__ . '/layout/footer.php'; ?>