<?php
require_once __DIR__ . '/../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
// Admin guard
if (empty($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../contacts.php');
    exit;
}

$contactId = (int)($_POST['contact_id'] ?? 0);
$reply     = trim($_POST['reply'] ?? '');

if (!$contactId || !$reply) {
    header('Location: ../contacts.php?msg=reply_error');
    exit;
}

$pdo = getDB();

// Verify the contact message exists
$exists = $pdo->prepare("SELECT id FROM contacts WHERE id=:id");
$exists->execute([':id' => $contactId]);
if (!$exists->fetch()) {
    header('Location: ../contacts.php?msg=reply_error');
    exit;
}

// Insert reply
$pdo->prepare("INSERT INTO admin_replies (contact_id, reply) VALUES (:cid, :reply)")
    ->execute([':cid' => $contactId, ':reply' => $reply]);

// Auto-mark the message as read
$pdo->prepare("UPDATE contacts SET is_read=1 WHERE id=:id")
    ->execute([':id' => $contactId]);

header('Location: ../contacts.php?msg=reply_sent&view=' . $contactId);
exit;
