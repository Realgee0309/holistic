<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/user_auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php?tab=bookings');
    exit;
}

$user      = getCurrentUser();
$bookingId = (int)($_POST['booking_id'] ?? 0);

if (!$bookingId) {
    setFlash('error', 'Invalid booking.');
    header('Location: ../dashboard.php?tab=bookings');
    exit;
}

$pdo = getDB();

// Verify this booking belongs to this user AND is still pending
$booking = $pdo->prepare("SELECT * FROM bookings WHERE id=:id AND user_id=:uid AND status='pending'");
$booking->execute([':id' => $bookingId, ':uid' => $user['id']]);
$booking = $booking->fetch();

if (!$booking) {
    setFlash('error', 'Booking not found or cannot be cancelled (only pending bookings can be cancelled).');
    header('Location: ../dashboard.php?tab=bookings');
    exit;
}

// Cancel it
$pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=:id")
    ->execute([':id' => $bookingId]);

setFlash('success', 'Your booking for "' . htmlspecialchars($booking['service']) . '" has been cancelled.');
header('Location: ../dashboard.php?tab=bookings');
exit;
