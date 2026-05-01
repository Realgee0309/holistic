<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../includes/user_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../calendar.php'); exit; }

csrfVerify();

// Check if this is an AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!rateLimitCheck('book', 5, 300)) {
    $errorMsg = 'Too many booking attempts. Please wait a few minutes.';
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit;
    }
    setFlash('error', $errorMsg);
    header('Location: ../calendar.php');
    exit;
}

$name           = cleanStr($_POST['name']           ?? '', 100);
$email          = cleanEmail($_POST['email']        ?? '');
$service        = cleanStr($_POST['service']        ?? '', 100);
$date           = cleanDate($_POST['selected_date'] ?? '');
$time           = cleanStr($_POST['selected_time']  ?? '', 50);
$message        = cleanText($_POST['message']       ?? '', 2000);
$paymentMethod  = cleanStr($_POST['payment_method'] ?? '', 20);
$phone          = cleanStr($_POST['phone']          ?? '', 20);
$userId         = isLoggedIn() ? getCurrentUser()['id'] : null;

$errors = [];
if (!$name)                               $errors[] = 'Name is required.';
if (!$email)                              $errors[] = 'Valid email is required.';
if (!$service)                            $errors[] = 'Please select a service.';
if (!$date)                               $errors[] = 'Please pick a valid future date.';
if (!$time)                               $errors[] = 'Please pick a preferred time.';
if ($date && $date < date('Y-m-d'))       $errors[] = 'Please choose a future date.';
if (getServicePrice($service) > 0 && !$paymentMethod) $errors[] = 'Please select a payment method.';
if ($paymentMethod === 'mpesa' && getServicePrice($service) > 0 && !$phone) $errors[] = 'M-Pesa phone number is required.';

if (!empty($errors)) {
    $errorMsg = implode(' ', $errors);
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit;
    }
    setFlash('error', $errorMsg);
    header('Location: ../calendar.php');
    exit;
}

try {
    $pdo  = getDB();

    // Insert booking
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id,name,email,service,preferred_date,preferred_time,message) VALUES (:uid,:name,:email,:service,:date,:time,:msg)");
    $stmt->execute([':uid'=>$userId,':name'=>$name,':email'=>$email,':service'=>$service,':date'=>$date,':time'=>$time,':msg'=>$message]);
    $bookingId = $pdo->lastInsertId();

    // Get the full booking data for email
    $bookingStmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $bookingStmt->execute([$bookingId]);
    $bookingData = $bookingStmt->fetch(PDO::FETCH_ASSOC);

    // Process payment if required
    $servicePrice = getServicePrice($service);
    if ($servicePrice > 0) {
        if ($paymentMethod === 'mpesa') {
            // Initiate M-Pesa payment
            $mpesaResponse = initiateMpesaPayment($bookingId, $phone, $servicePrice);

            if (isset($mpesaResponse['ResponseCode']) && $mpesaResponse['ResponseCode'] == '0') {
                // Record pending payment
                recordPayment($bookingId, $servicePrice, 'mpesa', 'pending', null, $mpesaResponse['CheckoutRequestID'] ?? null);
                $successMsg = '✅ Booking created! Please check your phone for the M-Pesa payment prompt.';
            } else {
                $successMsg = 'Booking created but M-Pesa payment failed. Please contact us to complete payment.';
            }
        } elseif ($paymentMethod === 'card') {
            // Stripe payment will be handled via AJAX
            recordPayment($bookingId, $servicePrice, 'card', 'pending');
            $successMsg = '✅ Booking created! Processing payment...';
        } elseif ($paymentMethod === 'paypal') {
            // PayPal payment will be handled via redirect
            recordPayment($bookingId, $servicePrice, 'paypal', 'pending');
            $successMsg = '✅ Booking created! Redirecting to PayPal...';
        } elseif ($paymentMethod === 'bank') {
            // Bank transfer - manual confirmation required
            recordPayment($bookingId, $servicePrice, 'bank', 'pending');
            $successMsg = '✅ Booking created! Please complete the bank transfer using the details provided. Your booking will be confirmed once payment is received.';
        }
    } else {
        $successMsg = '✅ Booking request received! We\'ll confirm via email or WhatsApp within 24 hours.';
    }

    // Send confirmation email
    sendBookingConfirmationEmail($bookingData);

    // Handle response
    if ($isAjax) {
        echo json_encode([
            'success' => true,
            'message' => $successMsg,
            'booking_id' => $bookingId
        ]);
        exit;
    }

    setFlash('success', $successMsg);
    header('Location: ' . ($userId ? '../dashboard.php?tab=bookings' : '../calendar.php'));
    exit;
} catch (PDOException $e) {
    error_log('Booking error: ' . $e->getMessage());
    $errorMsg = 'Something went wrong. Please try again or contact us via WhatsApp.';
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit;
    }
    setFlash('error', $errorMsg);
    header('Location: ../calendar.php');
    exit;
}
