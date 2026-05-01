<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/user_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot-password.php');
    exit;
}

$token   = trim($_POST['token']            ?? '');
$pass    = trim($_POST['password']         ?? '');
$confirm = trim($_POST['confirm_password'] ?? '');

// Validate token again (server-side)
$pdo = getDB();
$row = $pdo->prepare("SELECT * FROM password_resets WHERE token=:t AND used=0 AND expires_at > NOW()");
$row->execute([':t' => $token]);
$reset = $row->fetch();

if (!$reset) {
    setFlash('error', 'This reset link is invalid or has expired. Please request a new one.');
    header('Location: ../forgot-password.php');
    exit;
}

// Validate passwords
if (strlen($pass) < 8) {
    setFlash('error', 'Password must be at least 8 characters.');
    header('Location: ../reset-password.php?token=' . urlencode($token));
    exit;
}
if ($pass !== $confirm) {
    setFlash('error', 'Passwords do not match.');
    header('Location: ../reset-password.php?token=' . urlencode($token));
    exit;
}

// Update the user's password
$hash = password_hash($pass, PASSWORD_BCRYPT);
$pdo->prepare("UPDATE users SET password_hash=:h WHERE email=:email")
    ->execute([':h' => $hash, ':email' => $reset['email']]);

// Invalidate all tokens for this email
$pdo->prepare("UPDATE password_resets SET used=1 WHERE email=:email")
    ->execute([':email' => $reset['email']]);

setFlash('success', 'Your password has been updated. Please sign in with your new password.');
header('Location: ../login.php');
exit;
