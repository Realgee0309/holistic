<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/user_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../contact.php'); exit; }

csrfVerify();

if (!rateLimitCheck('contact', 3, 300)) {
    setFlash('error', 'You are sending messages too quickly. Please wait a few minutes.');
    header('Location: ../contact.php');
    exit;
}

$name    = cleanStr($_POST['name']    ?? '', 100);
$email   = cleanEmail($_POST['email'] ?? '');
$subject = cleanStr($_POST['subject'] ?? 'General Inquiry', 100);
$message = cleanText($_POST['message']?? '', 3000);
$userId  = isLoggedIn() ? getCurrentUser()['id'] : null;

$errors = [];
if (!$name)                        $errors[] = 'Name is required.';
if (!$email)                       $errors[] = 'A valid email is required.';
if (strlen(strip_tags($message)) < 10) $errors[] = 'Message must be at least 10 characters.';

if (!empty($errors)) {
    setFlash('error', implode(' ', $errors));
    header('Location: ../contact.php');
    exit;
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("INSERT INTO contacts (user_id,name,email,subject,message) VALUES (:uid,:name,:email,:subject,:message)");
    $stmt->execute([':uid'=>$userId,':name'=>$name,':email'=>$email,':subject'=>$subject,':message'=>$message]);
    setFlash('success', '✅ Message sent! We\'ll respond within 24 hours.');
    header('Location: ' . ($userId ? '../dashboard.php?tab=messages' : '../contact.php'));
    exit;
} catch (PDOException $e) {
    error_log('Contact error: ' . $e->getMessage());
    setFlash('error', 'Something went wrong. Please try again or contact us via WhatsApp.');
    header('Location: ../contact.php');
    exit;
}
