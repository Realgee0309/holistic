<?php
/**
 * M-Pesa STK Push Callback Handler
 * This endpoint receives payment confirmations from M-Pesa
 */

// Allow cross-origin requests for M-Pesa callback
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payments.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the callback data
$callbackData = json_decode(file_get_contents('php://input'), true);

if (!$callbackData) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid callback data']);
    exit;
}

// Log the callback for debugging
error_log('M-Pesa Callback: ' . json_encode($callbackData));

// Process the callback
$result = $callbackData['Body']['stkCallback'] ?? null;

if (!$result) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid callback structure']);
    exit;
}

$merchantRequestId = $result['MerchantRequestID'];
$checkoutRequestId = $result['CheckoutRequestID'];
$resultCode = $result['ResultCode'];
$resultDesc = $result['ResultDesc'];

try {
    $pdo = getDB();

    // Find the payment by checkout request ID
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE reference = ? AND method = 'mpesa'");
    $stmt->execute([$checkoutRequestId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        error_log("Payment not found for CheckoutRequestID: $checkoutRequestId");
        http_response_code(404);
        echo json_encode(['error' => 'Payment not found']);
        exit;
    }

    if ($resultCode == 0) {
        // Payment successful
        $callbackMetadata = $result['CallbackMetadata']['Item'] ?? [];

        $transactionId = '';
        $amount = $payment['amount'];

        // Extract transaction details from callback
        foreach ($callbackMetadata as $item) {
            if ($item['Name'] == 'MpesaReceiptNumber') {
                $transactionId = $item['Value'];
            } elseif ($item['Name'] == 'Amount') {
                $amount = $item['Value'];
            }
        }

        // Update payment status
        updatePaymentStatus($payment['id'], 'completed', $transactionId);

        // Update booking status to confirmed
        $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")
            ->execute([$payment['booking_id']]);

        // Send payment confirmation email
        $bookingStmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $bookingStmt->execute([$payment['booking_id']]);
        $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);

        if ($booking) {
            require_once __DIR__ . '/../config/email.php';
            sendPaymentConfirmationEmail($booking, $payment);
        }

        error_log("Payment completed: $transactionId");

    } else {
        // Payment failed
        updatePaymentStatus($payment['id'], 'failed');

        error_log("Payment failed: $resultDesc");
    }

    // Respond to M-Pesa
    echo json_encode([
        'ResultCode' => 0,
        'ResultDesc' => 'Accepted'
    ]);

} catch (Exception $e) {
    error_log('M-Pesa callback error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
