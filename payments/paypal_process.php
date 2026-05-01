<?php
/**
 * PayPal Payment Processing
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../config/paypal.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

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
        case 'create_payment':
            $bookingId = $data['booking_id'] ?? 0;
            $amount = $data['amount'] ?? 0;
            $description = $data['description'] ?? 'Therapy Session Payment';

            if (!$bookingId || !$amount) {
                throw new Exception('Missing booking_id or amount');
            }

            // Verify booking exists
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                throw new Exception('Booking not found');
            }

            // Create PayPal payment
            $paypalResult = createPayPalPayment($amount, $description, $bookingId);

            if ($paypalResult['success']) {
                // Record payment as pending
                recordPayment($bookingId, $amount, 'paypal', 'pending', $paypalResult['order_id']);

                echo json_encode([
                    'success' => true,
                    'approval_url' => $paypalResult['approval_url'],
                    'order_id' => $paypalResult['order_id']
                ]);
            } else {
                throw new Exception('Failed to create PayPal payment');
            }
            break;

        case 'confirm_payment':
            $orderId = $data['order_id'] ?? '';
            $bookingId = $data['booking_id'] ?? 0;

            if (!$orderId || !$bookingId) {
                throw new Exception('Missing order_id or booking_id');
            }

            // Verify PayPal payment
            $verification = verifyPayPalPayment($orderId);

            if ($verification['success'] && $verification['status'] === 'completed') {
                // Update payment status
                updatePaymentStatusByTransaction($orderId, 'completed', $verification['transaction_id']);

                // Update booking status
                $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")
                    ->execute([$bookingId]);

                // Send confirmation email
                $bookingStmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
                $bookingStmt->execute([$bookingId]);
                $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);

                $paymentStmt = $pdo->prepare("SELECT * FROM payments WHERE transaction_id = ?");
                $paymentStmt->execute([$orderId]);
                $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

                if ($booking && $payment) {
                    require_once __DIR__ . '/../config/email.php';
                    sendPaymentConfirmationEmail($booking, $payment);
                }

                echo json_encode(['success' => true, 'message' => 'Payment confirmed']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }

} catch (Exception $e) {
    error_log('PayPal payment error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Update payment status by transaction ID
 */
function updatePaymentStatusByTransaction(string $transactionId, string $status, string $newTransactionId = null): bool {
    $pdo = getDB();

    $stmt = $pdo->prepare("
        UPDATE payments
        SET status = ?, transaction_id = ?, paid_at = NOW()
        WHERE transaction_id = ?
    ");

    return $stmt->execute([$status, $newTransactionId ?: $transactionId, $transactionId]);
}
?>
