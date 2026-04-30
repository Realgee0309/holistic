<?php
/**
 * Action: Forgot Password — generate reset token
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/user_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../forgot_password.php'); exit; }

csrfVerify();

if (!rateLimitCheck('forgot_pw', 3, 600)) {
    setFlash('error', 'Too many attempts. Please wait 10 minutes before trying again.');
    header('Location: ../forgot_password.php');
    exit;
}

$email = cleanEmail($_POST['email'] ?? '');

if (!$email) {
    setFlash('error', 'Please enter a valid email address.');
    header('Location: ../forgot_password.php');
    exit;
}

$pdo = getDB();

// Always redirect to "sent" to prevent user enumeration
$user = $pdo->prepare("SELECT id, name FROM users WHERE email = :e LIMIT 1");
$user->execute([':e' => $email]);
$user = $user->fetch();

if ($user) {
    // Expire old tokens for this email
    $pdo->prepare("UPDATE password_resets SET used=1 WHERE email=:e AND used=0")->execute([':e' => $email]);

    $token     = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:e, :t, :ex)")
        ->execute([':e' => $email, ':t' => $token, ':ex' => $expiresAt]);

    // --- XAMPP: no real email — surface the link in a flash message ---
    $resetLink = 'http://localhost/Holistic-Wellness-main/reset_password.php?token=' . $token;

    // In production: replace the block below with PHPMailer / SMTP send
    // For XAMPP dev we store the link in session so the dev can click it
    $_SESSION['dev_reset_link'] = $resetLink;

    // Production-ready email snippet (commented out):
    /*
    use PHPMailer\PHPMailer\PHPMailer;
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.example.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'no-reply@holisticwellness.com';
    $mail->Password   = 'YOUR_SMTP_PASSWORD';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->setFrom('no-reply@holisticwellness.com', 'Holistic Wellness');
    $mail->addAddress($email, $user['name']);
    $mail->isHTML(true);
    $mail->Subject = 'Reset your Holistic Wellness password';
    $mail->Body    = "Hi {$user['name']},<br><br>Click the link below to reset your password (expires in 1 hour):<br><br><a href='{$resetLink}'>{$resetLink}</a><br><br>If you didn't request this, ignore this email.";
    $mail->send();
    */
}

// Always redirect the same way (no enumeration)
header('Location: ../forgot_password.php?sent=1');
exit;
