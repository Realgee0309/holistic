<?php
/**
 * Stripe webhook endpoint for payment events.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../vendor/autoload.php';

$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (!$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

try {
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    if (defined('STRIPE_WEBHOOK_SECRET') && STRIPE_WEBHOOK_SECRET) {
        $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, STRIPE_WEBHOOK_SECRET);
    } else {
        $event = json_decode($payload, true);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$eventType = is_array($event) ? ($event['type'] ?? '') : ($event->type ?? '');
$object = is_array($event) ? ($event['data']['object'] ?? []) : ($event->data->object ?? null);

try {
    if ($eventType === 'payment_intent.succeeded' || $eventType === 'charge.succeeded') {
        $intentId = is_array($object) ? ($object['id'] ?? '') : ($object->id ?? '');
        $bookingId = 0;
        if (is_array($object)) {
            $bookingId = isset($object['metadata']['booking_id']) ? (int)$object['metadata']['booking_id'] : 0;
        } else {
            $bookingId = isset($object->metadata->booking_id) ? (int)$object->metadata->booking_id : 0;
        }

        $pdo = getDB();
        $stmt = $pdo->prepare('UPDATE payments SET status = ?, transaction_id = ?, paid_at = NOW() WHERE transaction_id = ? OR reference = ?');
        $stmt->execute(['completed', $intentId, $intentId, $intentId]);

        if ($bookingId) {
            $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute(['confirmed', $bookingId]);

            $bookingStmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
            $bookingStmt->execute([$bookingId]);
            $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);
            $paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE transaction_id = ? OR reference = ? LIMIT 1');
            $paymentStmt->execute([$intentId, $intentId]);
            $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
            if ($booking && $payment) {
                sendPaymentConfirmationEmail($booking, $payment);
            }
        }
    } elseif ($eventType === 'payment_intent.payment_failed' || $eventType === 'charge.failed') {
        $intentId = is_array($object) ? ($object['id'] ?? '') : ($object->id ?? '');
        $pdo = getDB();
        $pdo->prepare('UPDATE payments SET status = ?, paid_at = NOW() WHERE transaction_id = ? OR reference = ?')
            ->execute(['failed', $intentId, $intentId]);
    }

    echo json_encode(['received' => true]);
} catch (Exception $e) {
    error_log('Stripe webhook error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Webhook processing failed']);
}
