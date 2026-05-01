<?php
/**
 * Stripe Payment Processing
 */

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payments.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

$action = $data['action'] ?? '';

try {
    $pdo = getDB();

    switch ($action) {
        case 'create_payment_intent':
            // Create Stripe Payment Intent
            $bookingId = $data['booking_id'] ?? 0;
            $amount = $data['amount'] ?? 0;

            if (!$bookingId || !$amount) {
                throw new Exception('Missing booking_id or amount');
            }

            // Verify booking exists and belongs to user
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                throw new Exception('Booking not found');
            }

            require_once 'vendor/autoload.php'; // If using Composer

            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => 'kes',
                'metadata' => [
                    'booking_id' => $bookingId,
                    'service' => $booking['service']
                ]
            ]);

            // Record payment as pending
            recordPayment($bookingId, $amount, 'card', 'pending', $paymentIntent->id);

            echo json_encode([
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id
            ]);
            break;

        case 'confirm_payment':
            // Confirm Stripe payment
            $paymentIntentId = $data['payment_intent_id'] ?? '';
            $bookingId = $data['booking_id'] ?? 0;

            if (!$paymentIntentId || !$bookingId) {
                throw new Exception('Missing payment_intent_id or booking_id');
            }

            require_once 'vendor/autoload.php';

            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

            if ($paymentIntent->status === 'succeeded') {
                // Update payment status
                updatePaymentStatusByIntent($paymentIntentId, 'completed', $paymentIntent->id);

                // Update booking status
                $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")
                    ->execute([$bookingId]);

                // Send confirmation email
                $bookingStmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
                $bookingStmt->execute([$bookingId]);
                $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);

                $paymentStmt = $pdo->prepare("SELECT * FROM payments WHERE transaction_id = ?");
                $paymentStmt->execute([$paymentIntentId]);
                $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

                if ($booking && $payment) {
                    require_once __DIR__ . '/../config/email.php';
                    sendPaymentConfirmationEmail($booking, $payment);
                }

                echo json_encode(['success' => true, 'message' => 'Payment confirmed']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Payment not completed']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }

} catch (Exception $e) {
    error_log('Stripe payment error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Update payment status by Stripe payment intent ID
 */
function updatePaymentStatusByIntent(string $intentId, string $status, string $transactionId = null): bool {
    $pdo = getDB();

    $stmt = $pdo->prepare("
        UPDATE payments
        SET status = ?, transaction_id = ?, paid_at = NOW()
        WHERE transaction_id = ?
    ");

    return $stmt->execute([$status, $transactionId, $intentId]);
}
?>
