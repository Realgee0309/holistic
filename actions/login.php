<?php
/**
 * Action: Client Login (hardened with CSRF + rate limiting)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/user_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../login.php'); exit; }

csrfVerify();

if (!rateLimitCheck('login', 5, 300)) {
    setFlash('error', 'Too many login attempts. Please wait 5 minutes and try again.');
    header('Location: ../login.php');
    exit;
}

$email    = cleanEmail($_POST['email']    ?? '');
$password = trim($_POST['password']       ?? '');
$redirect = $_POST['redirect']            ?? '';

// Sanitize redirect to prevent open redirect
if (!preg_match('#^/(Holistic-Wellness-main|holistic.wellness)#', $redirect)) {
    $redirect = '../dashboard.php';
}

if (!$email || !$password) {
    setFlash('error', 'Please enter your email and password.');
    header('Location: ../login.php');
    exit;
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :e LIMIT 1");
    $stmt->execute([':e' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        rateLimitReset('login');
        loginUser($user);
        setFlash('success', 'Welcome back, ' . htmlspecialchars($user['is_anonymous'] ? 'there' : $user['name']) . '!');
        header('Location: ' . $redirect);
        exit;
    } else {
        setFlash('error', 'Invalid email or password. Please try again.');
        header('Location: ../login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Login error: ' . $e->getMessage());
    setFlash('error', 'A server error occurred. Please try again.');
    header('Location: ../login.php');
    exit;
}
