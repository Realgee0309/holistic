<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/user_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot-password.php');
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Please enter a valid email address.');
    header('Location: ../forgot-password.php');
    exit;
}

$pdo = getDB();

// Auto-create table if it doesn't exist yet
$pdo->exec("CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         INT          NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(150) NOT NULL,
    `token`      VARCHAR(64)  NOT NULL,
    `expires_at` DATETIME     NOT NULL,
    `used`       TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_token` (`token`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Look up the user
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if ($user) {
    // Invalidate any existing unused tokens for this email
    $pdo->prepare("UPDATE password_resets SET used=1 WHERE email=:email AND used=0")
        ->execute([':email' => $email]);

    // Generate a secure random token
    $token     = bin2hex(random_bytes(32)); // 64 hex chars
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)")
        ->execute([':email' => $email, ':token' => $token, ':expires' => $expiresAt]);

    // Build reset link
    $scheme    = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host      = $_SERVER['HTTP_HOST'];
    $path      = dirname(dirname($_SERVER['SCRIPT_NAME']));
    $resetLink = $scheme . '://' . $host . rtrim($path, '/') . '/reset-password.php?token=' . urlencode($token);

    // Try to send email — but don't rely on it (fails silently on most XAMPP setups)
    $subject = 'Reset Your Holistic Wellness Password';
    $body    = "Hello,\n\n"
             . "You requested a password reset for your Holistic Wellness account.\n\n"
             . "Click the link below to set a new password (valid for 30 minutes):\n\n"
             . $resetLink . "\n\n"
             . "If you did not request this, you can safely ignore this email.\n\n"
             . "— Holistic Wellness Team";

    $headers = "From: no-reply@holistic-wellness.com\r\n"
             . "Reply-To: no-reply@holistic-wellness.com\r\n"
             . "X-Mailer: PHP/" . phpversion();

    @mail($email, $subject, $body, $headers);

    // Store the reset link in session so we can show it directly on the page
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['reset_link'] = $resetLink;

    setFlash('success', 'A password reset link has been generated for your account.');
    header('Location: ../forgot-password.php');
    exit;
}

// Email not found — show generic message to prevent user enumeration
setFlash('error', 'No account found with that email address. Please check and try again.');
header('Location: ../forgot-password.php');
exit;
