<?php
/**
 * Action: Reset Password — validate token, update hash
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/user_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../login.php'); exit; }

csrfVerify();

$token    = cleanStr($_POST['token']    ?? '', 64);
$password = trim($_POST['password']     ?? '');
$confirm  = trim($_POST['confirm']      ?? '');

$errors = [];
if (!$token)                    $errors[] = 'Invalid reset link.';
if (strlen($password) < 8)     $errors[] = 'Password must be at least 8 characters.';
if ($password !== $confirm)     $errors[] = 'Passwords do not match.';

if (!empty($errors)) {
    setFlash('error', implode(' ', $errors));
    header('Location: ../reset_password.php?token=' . urlencode($token));
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token=:t AND used=0 AND expires_at > NOW() LIMIT 1");
$stmt->execute([':t' => $token]);
$row = $stmt->fetch();

if (!$row) {
    setFlash('error', 'This reset link is invalid or has expired. Please request a new one.');
    header('Location: ../forgot_password.php');
    exit;
}

// Update user password
$hash = password_hash($password, PASSWORD_BCRYPT);
$pdo->prepare("UPDATE users SET password_hash=:h WHERE email=:e")
    ->execute([':h' => $hash, ':e' => $row['email']]);

// Invalidate token
$pdo->prepare("UPDATE password_resets SET used=1 WHERE id=:id")
    ->execute([':id' => $row['id']]);

// Clear dev link from session
unset($_SESSION['dev_reset_link']);

setFlash('success', '✅ Password updated successfully. Please sign in with your new password.');
header('Location: ../login.php');
exit;
