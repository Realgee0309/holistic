<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/user_auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../profile.php');
    exit;
}

$user        = getCurrentUser();
$currentPwd  = $_POST['current_password']  ?? '';
$newPwd      = trim($_POST['new_password'] ?? '');
$confirmPwd  = trim($_POST['confirm_password'] ?? '');

$pdo = getDB();

// Fetch the stored hash
$row = $pdo->prepare("SELECT password_hash FROM users WHERE id=:id");
$row->execute([':id' => $user['id']]);
$row = $row->fetch();

// Verify current password
if (!$row || !password_verify($currentPwd, $row['password_hash'])) {
    setFlash('error', 'Current password is incorrect.');
    header('Location: ../profile.php');
    exit;
}

// Validate new password
if (strlen($newPwd) < 8) {
    setFlash('error', 'New password must be at least 8 characters.');
    header('Location: ../profile.php');
    exit;
}

if ($newPwd !== $confirmPwd) {
    setFlash('error', 'New passwords do not match.');
    header('Location: ../profile.php');
    exit;
}

// Prevent reuse of the same password
if (password_verify($newPwd, $row['password_hash'])) {
    setFlash('error', 'Your new password must be different from your current password.');
    header('Location: ../profile.php');
    exit;
}

// All good — update
$hash = password_hash($newPwd, PASSWORD_BCRYPT);
$pdo->prepare("UPDATE users SET password_hash=:h WHERE id=:id")
    ->execute([':h' => $hash, ':id' => $user['id']]);

setFlash('success', 'Password changed successfully. Keep it safe!');
header('Location: ../profile.php');
exit;
