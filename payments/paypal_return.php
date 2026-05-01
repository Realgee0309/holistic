<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../config/paypal.php';
require_once __DIR__ . '/../config/email.php';

$orderId = $_GET['order_id'] ?? '';
$bookingId = intval($_GET['booking_id'] ?? 0);
$statusMessage = '';
$success = false;

if (!$orderId || !$bookingId) {
    $statusMessage = 'Invalid PayPal return request. Please try again from your dashboard.';
} else {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new Exception('Booking not found.');
        }

        $verification = verifyPayPalPayment($orderId);

        if ($verification['success'] && $verification['status'] === 'completed') {
            $transactionId = $verification['transaction_id'];

            $stmt = $pdo->prepare('UPDATE payments SET status = ?, transaction_id = ?, paid_at = NOW() WHERE transaction_id = ?');
            $stmt->execute(['completed', $transactionId, $orderId]);

            $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute(['confirmed', $bookingId]);

            $paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE transaction_id = ? OR reference = ? LIMIT 1');
            $paymentStmt->execute([$transactionId, $orderId]);
            $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

            if ($payment) {
                sendPaymentConfirmationEmail($booking, $payment);
            }

            $statusMessage = 'Your PayPal payment has been confirmed. Thank you!';
            $success = true;
        } else {
            $statusMessage = 'We could not verify your payment yet. If the issue persists, please contact support.';
        }
    } catch (Exception $e) {
        error_log('PayPal return error: ' . $e->getMessage());
        $statusMessage = 'Something went wrong while confirming your payment. Please try again later.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayPal Payment Confirmation</title>
    <style>
        body { margin:0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background:#f7fafc; color:#111827; }
        .container { max-width:700px; margin:5rem auto; padding:2rem; background:white; border-radius:18px; box-shadow:0 24px 80px rgba(15,23,42,.08); }
        .icon { width:72px; height:72px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem; font-size:2rem; }
        .icon.success { background: #d1fae5; color: #047857; }
        .icon.error { background:#fee2e2; color:#b91c1c; }
        h1 { margin:0 0 0.75rem; font-size:2rem; text-align:center; }
        p { margin:0; color:#4b5563; text-align:center; line-height:1.8; }
        .actions { margin-top:2rem; display:flex; justify-content:center; gap:1rem; flex-wrap:wrap; }
        .btn { display:inline-flex; align-items:center; justify-content:center; padding:0.9rem 1.4rem; border-radius:999px; text-decoration:none; font-weight:700; }
        .btn-primary { background:#2563eb; color:white; }
        .btn-secondary { background:#e5e7eb; color:#374151; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon <?= $success ? 'success' : 'error' ?>">
            <?= $success ? '✅' : '⚠️' ?>
        </div>
        <h1><?= $success ? 'Payment Confirmed' : 'Payment Confirmation Needed' ?></h1>
        <p><?= htmlspecialchars($statusMessage) ?></p>
        <div class="actions">
            <a href="/dashboard.php?tab=bookings" class="btn btn-primary">Go to Dashboard</a>
            <a href="/calendar.php" class="btn btn-secondary">Book Another Session</a>
        </div>
    </div>
</body>
</html>
