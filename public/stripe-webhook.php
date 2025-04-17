<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controllers/PaymentController.php';

$paymentController = new PaymentController();
$result = $paymentController->handleWebhook();

if (!$result['success']) {
    error_log('Webhook error: ' . $result['error']);
    http_response_code(400);
} else {
    http_response_code(200);
}