<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; }
        .order-details { background: #f8f9fa; padding: 20px; margin: 20px 0; }
        .product-item { padding: 10px 0; border-bottom: 1px solid #eee; }
        .total-section { margin-top: 20px; text-align: right; }
        .button { display: inline-block; padding: 10px 20px; background: #4f46e5; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Thank You for Your Order!</h1>
            <p>Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></p>
        </div>

        <p>Dear <?= htmlspecialchars($user['name']) ?>,</p>
        
        <p>We're excited to confirm your order with The Scent. Here's a summary of your purchase:</p>

        <div class="order-details">
            <h2>Order Summary</h2>
            <?php foreach ($order['items'] as $item): ?>
                <div class="product-item">
                    <p>
                        <?= htmlspecialchars($item['quantity']) ?>x 
                        <?= htmlspecialchars($item['product_name']) ?> - 
                        $<?= number_format($item['price'], 2) ?>
                    </p>
                </div>
            <?php endforeach; ?>

            <div class="total-section">
                <p>Subtotal: $<?= number_format($order['subtotal'], 2) ?></p>
                <p>Shipping: $<?= number_format($order['shipping_cost'], 2) ?></p>
                <?php if ($order['discount_amount'] > 0): ?>
                    <p>Discount: -$<?= number_format($order['discount_amount'], 2) ?></p>
                <?php endif; ?>
                <p>Tax: $<?= number_format($order['tax_amount'], 2) ?></p>
                <h3>Total: $<?= number_format($order['total_amount'], 2) ?></h3>
            </div>
        </div>

        <div class="shipping-info">
            <h2>Shipping Address</h2>
            <p><?= htmlspecialchars($order['shipping_name']) ?></p>
            <p><?= htmlspecialchars($order['shipping_address']) ?></p>
            <p><?= htmlspecialchars($order['shipping_city']) ?>, <?= htmlspecialchars($order['shipping_state']) ?> <?= htmlspecialchars($order['shipping_zip']) ?></p>
            <p><?= htmlspecialchars($order['shipping_country']) ?></p>
        </div>

        <p>
            <a href="<?= BASE_URL ?>index.php?page=account&section=orders&id=<?= $order['id'] ?>" class="button">
                View Order Details
            </a>
        </p>

        <p>We'll send you another email when your order ships. If you have any questions, please don't hesitate to contact our customer service team.</p>

        <p>Thank you for choosing The Scent!</p>

        <div style="margin-top: 30px; font-size: 12px; color: #666;">
            <p>This email was sent to <?= htmlspecialchars($user['email']) ?></p>
            <p>© <?= date('Y') ?> The Scent. All rights reserved.</p>
        </div>
    </div>
</body>
</html>