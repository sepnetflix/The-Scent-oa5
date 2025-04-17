<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; }
        .tracking-info { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .button { display: inline-block; padding: 10px 20px; background: #4f46e5; color: white; text-decoration: none; border-radius: 5px; }
        .icon { font-size: 24px; color: #4f46e5; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Your Order Has Shipped!</h1>
            <p>Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></p>
        </div>

        <p>Great news! Your order from The Scent is on its way.</p>

        <div class="tracking-info">
            <h2>Tracking Information</h2>
            <p><strong>Carrier:</strong> <?= htmlspecialchars($carrier) ?></p>
            <p><strong>Tracking Number:</strong> <?= htmlspecialchars($trackingNumber) ?></p>
            
            <p style="text-align: center; margin: 30px 0;">
                <a href="<?= $order['tracking_url'] ?>" class="button">Track Your Package</a>
            </p>
        </div>

        <div class="estimated-delivery">
            <h2>Estimated Delivery</h2>
            <p>Your package is expected to arrive by <?= date('F j, Y', strtotime('+5 days')) ?></p>
        </div>

        <p style="margin-top: 30px;">
            <a href="<?= BASE_URL ?>index.php?page=account&section=orders&id=<?= $order['id'] ?>" class="button">
                View Order Details
            </a>
        </p>

        <p>Thank you for choosing The Scent!</p>

        <div style="margin-top: 30px; font-size: 12px; color: #666; border-top: 1px solid #eee; padding-top: 20px;">
            <p>© <?= date('Y') ?> The Scent. All rights reserved.</p>
            <p>This email was sent to track your order shipping status.</p>
        </div>
    </div>
</body>
</html>