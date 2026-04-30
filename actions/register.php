<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/user_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../register.php'); exit; }

csrfVerify();

if (!rateLimitCheck('register', 3, 600)) {
    setFlash('error', 'Too many registration attempts. Please wait 10 minutes.');
    header('Location: ../register.php');
    exit;
}

$name     = cleanStr(trim($_POST['name']     ?? ''), 100);
$email    = cleanEmail(trim($_POST['email']   ?? ''));
$password = trim($_POST['password'] ?? '');
$confirm  = trim($_POST['confirm']  ?? '');
$is_anon  = !empty($_POST['is_anonymous']) ? 1 : 0;

$errors = [];
if (strlen($name) < 2)                      $errors[] = 'Full name must be at least 2 characters.';
if (!$email)                                $errors[] = 'Please enter a valid email address.';
if (strlen($password) < 8)                  $errors[] = 'Password must be at least 8 characters.';
if ($password !== $confirm)                  $errors[] = 'Passwords do not match.';

if (!empty($errors)) {
    setFlash('error', implode(' | ', $errors));
    header('Location: ../register.php');
    exit;
}

try {
    $pdo = getDB();
    $check = $pdo->prepare("SELECT id FROM users WHERE email=:e");
    $check->execute([':e' => $email]);
    if ($check->fetch()) {
        setFlash('error', 'An account with that email already exists. Try logging in.');
        header('Location: ../register.php');
        exit;
    }
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,is_anonymous) VALUES (:n,:e,:h,:a)");
    $stmt->execute([':n'=>$name,':e'=>$email,':h'=>$hash,':a'=>$is_anon]);
    $userId = $pdo->lastInsertId();
    loginUser(['id'=>$userId,'name'=>$name,'email'=>$email,'is_anonymous'=>$is_anon]);
    setFlash('success', 'Welcome to Holistic Wellness! Your account has been created.');
    header('Location: ../dashboard.php');
    exit;
} catch (PDOException $e) {
    error_log('Register error: ' . $e->getMessage());
    setFlash('error', 'Registration failed due to a server error. Please try again.');
    header('Location: ../register.php');
    exit;
}
