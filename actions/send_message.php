<?php
/**
 * Action: Client sends a message to therapist
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/user_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../messages.php'); exit; }

requireLogin();
csrfVerify();

if (!rateLimitCheck('send_msg', 10, 60)) {
    setFlash('error', 'You are sending messages too quickly. Please wait a moment.');
    header('Location: ../messages.php');
    exit;
}

$user = getCurrentUser();
$body = cleanText($_POST['body'] ?? '', 3000);

if (strlen(trim($body)) < 1) {
    setFlash('error', 'Message cannot be empty.');
    header('Location: ../messages.php');
    exit;
}

try {
    $pdo = getDB();
    $pdo->prepare("INSERT INTO thread_messages (user_id, sender, body) VALUES (:uid, 'client', :body)")
        ->execute([':uid' => $user['id'], ':body' => $body]);
    header('Location: ../messages.php#thread');
    exit;
} catch (PDOException $e) {
    error_log('Message send error: ' . $e->getMessage());
    setFlash('error', 'Could not send message. Please try again.');
    header('Location: ../messages.php');
    exit;
}
