<?php
/**
 * Payment Status Checker
 * AJAX endpoint to check payment status
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$bookingId = $_GET['booking_id'] ?? $_POST['booking_id'] ?? 0;

if (!$bookingId) {
    http_response_code(400);
    echo json_encode(['error' => 'Booking ID required']);
    exit;
}

try {
    $pdo = getDB();

    // Get payment status for booking
    $stmt = $pdo->prepare("
        SELECT p.*, b.service, b.name, b.email
        FROM payments p
        JOIN bookings b ON b.id = p.booking_id
        WHERE p.booking_id = ?
        ORDER BY p.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$bookingId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        echo json_encode([
            'status' => 'no_payment',
            'message' => 'No payment found for this booking'
        ]);
        exit;
    }

    $response = [
        'status' => $payment['status'],
        'method' => $payment['method'],
        'amount' => $payment['amount'],
        'transaction_id' => $payment['transaction_id'],
        'paid_at' => $payment['paid_at'],
        'service' => $payment['service']
    ];

    // Add status-specific messages
    switch ($payment['status']) {
        case 'pending':
            $response['message'] = 'Payment is being processed. Please wait...';
            if ($payment['method'] === 'mpesa') {
                $response['message'] = 'Check your phone for the M-Pesa payment prompt.';
            }
            break;
        case 'completed':
            $response['message'] = 'Payment completed successfully!';
            break;
        case 'failed':
            $response['message'] = 'Payment failed. Please try again or contact support.';
            break;
        default:
            $response['message'] = 'Payment status: ' . ucfirst($payment['status']);
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log('Payment status check error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
