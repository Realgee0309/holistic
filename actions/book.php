<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/user_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../book.php'); exit; }

csrfVerify();

if (!rateLimitCheck('book', 5, 300)) {
    setFlash('error', 'Too many booking attempts. Please wait a few minutes.');
    header('Location: ../book.php');
    exit;
}

$name    = cleanStr($_POST['name']    ?? '', 100);
$email   = cleanEmail($_POST['email'] ?? '');
$service = cleanStr($_POST['service'] ?? '', 100);
$date    = cleanDate($_POST['date']   ?? '');
$time    = cleanStr($_POST['time']    ?? '', 50);
$message = cleanText($_POST['message']?? '', 2000);
$userId  = isLoggedIn() ? getCurrentUser()['id'] : null;

$errors = [];
if (!$name)                               $errors[] = 'Name is required.';
if (!$email)                              $errors[] = 'Valid email is required.';
if (!$service)                            $errors[] = 'Please select a service.';
if (!$date)                               $errors[] = 'Please pick a valid future date.';
if (!$time)                               $errors[] = 'Please pick a preferred time.';
if ($date && $date < date('Y-m-d'))       $errors[] = 'Please choose a future date.';

if (!empty($errors)) {
    setFlash('error', implode(' ', $errors));
    header('Location: ../book.php');
    exit;
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id,name,email,service,preferred_date,preferred_time,message) VALUES (:uid,:name,:email,:service,:date,:time,:msg)");
    $stmt->execute([':uid'=>$userId,':name'=>$name,':email'=>$email,':service'=>$service,':date'=>$date,':time'=>$time,':msg'=>$message]);
    setFlash('success', '✅ Booking request received! We\'ll confirm via email or WhatsApp within 24 hours.');
    header('Location: ' . ($userId ? '../dashboard.php?tab=bookings' : '../book.php'));
    exit;
} catch (PDOException $e) {
    error_log('Booking error: ' . $e->getMessage());
    setFlash('error', 'Something went wrong. Please try again or contact us via WhatsApp.');
    header('Location: ../book.php');
    exit;
}
