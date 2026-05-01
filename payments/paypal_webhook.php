<?php
/**
 * PayPal webhook endpoint for payment events.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../config/paypal.php';
require_once __DIR__ . '/../config/email.php';

$payload = @file_get_contents('php://input');
$headers = [];
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
    }
}

if (!$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

if (!verifyPayPalWebhook($headers, $payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Webhook verification failed']);
    exit;
}

$event = json_decode($payload, true);
$eventType = $event['event_type'] ?? '';
$resource = $event['resource'] ?? [];

try {
    $pdo = getDB();
    if ($eventType === 'PAYMENT.CAPTURE.COMPLETED' || $eventType === 'CHECKOUT.ORDER.COMPLETED' || $eventType === 'PAYMENT.CAPTURE.DENIED') {
        $orderId = $resource['supplementary_data']['related_ids']['order_id'] ?? $resource['invoice_id'] ?? $resource['custom_id'] ?? $resource['id'] ?? '';
        $transactionId = $resource['id'] ?? '';
        $status = $eventType === 'PAYMENT.CAPTURE.DENIED' ? 'failed' : 'completed';

        if (!$orderId && isset($resource['links'][0]['href'])) {
            $orderId = $resource['links'][0]['href'];
        }

        $updateStmt = $pdo->prepare('UPDATE payments SET status = ?, transaction_id = ?, paid_at = NOW() WHERE transaction_id = ? OR reference = ?');
        $updateStmt->execute([$status, $transactionId, $orderId, $orderId]);

        $paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE transaction_id = ? OR reference = ? LIMIT 1');
        $paymentStmt->execute([$transactionId, $orderId]);
        $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

        if ($payment && $status === 'completed' && $payment['booking_id']) {
            $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute(['confirmed', $payment['booking_id']]);
            $bookingStmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
            $bookingStmt->execute([$payment['booking_id']]);
            $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);
            if ($booking) {
                sendPaymentConfirmationEmail($booking, $payment);
            }
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('PayPal webhook error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Webhook processing failed']);
}
