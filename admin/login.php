<?php
/**
 * Admin Login Page — Refined
 */
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($username && $password) {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = :u LIMIT 1");
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['admin_id']   = $user['id'];
                $_SESSION['admin_user'] = $user['username'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Holistic Wellness</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <style>
        :root {
            --primary: #5a7d7c;
            --primary-d: #436865;
            --accent: #d2aa7e;
            --dark: #1e2a35;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #1e2a35 0%, #2d4a47 50%, #1e2a35 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        /* Animated background orbs */
        body::before {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(90,125,124,0.15) 0%, transparent 70%);
            top: -200px; left: -200px;
            animation: float 8s ease-in-out infinite;
        }
        body::after {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(210,170,126,0.1) 0%, transparent 70%);
            bottom: -150px; right: -150px;
            animation: float 10s ease-in-out infinite reverse;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, -30px); }
        }
        .login-wrapper {
            position: relative; z-index: 1;
            width: 100%; max-width: 440px;
            padding: 1.5rem;
        }
        .login-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }
        .login-brand {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .brand-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, var(--primary), var(--primary-d));
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.2rem;
            box-shadow: 0 10px 30px rgba(90,125,124,0.4);
        }
        .login-brand h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            color: white;
            margin-bottom: 0.3rem;
        }
        .login-brand p {
            color: rgba(255,255,255,0.5);
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        .error-box {
            background: rgba(220,53,69,0.15);
            border: 1px solid rgba(220,53,69,0.3);
            color: #ff8b96;
            padding: 0.9rem 1.1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.88rem;
            display: flex; align-items: center; gap: 0.6rem;
        }
        .form-group { margin-bottom: 1.3rem; }
        .form-group label {
            display: block;
            color: rgba(255,255,255,0.7);
            font-size: 0.82rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .input-wrap { position: relative; }
        .input-wrap i {
            position: absolute;
            left: 1rem; top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.35);
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.8rem;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            color: white;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
        }
        .form-control::placeholder { color: rgba(255,255,255,0.25); }
        .form-control:focus {
            outline: none;
            background: rgba(255,255,255,0.1);
            border-color: rgba(90,125,124,0.6);
            box-shadow: 0 0 0 3px rgba(90,125,124,0.2);
        }
        .btn-login {
            width: 100%;
            padding: 0.95rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-d));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.97rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 0.5rem;
            letter-spacing: 0.5px;
            box-shadow: 0 8px 25px rgba(90,125,124,0.4);
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 12px 35px rgba(90,125,124,0.5); }
        .btn-login:active { transform: translateY(0); }
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .login-footer a {
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            font-size: 0.82rem;
            transition: color 0.2s;
        }
        .login-footer a:hover { color: var(--accent); }
        .login-hint {
            margin-top: 1rem;
            background: rgba(210,170,126,0.1);
            border: 1px solid rgba(210,170,126,0.2);
            border-radius: 8px;
            padding: 0.7rem 1rem;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.45);
            text-align: center;
        }
        .login-hint strong { color: var(--accent); }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-brand">
            <div class="brand-icon">🌿</div>
            <h1>Holistic Wellness</h1>
            <p>ADMIN PORTAL</p>
        </div>

        <?php if ($error): ?>
        <div class="error-box"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label>Username</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-arrow-right-to-bracket" style="margin-right:0.5rem"></i> Sign In to Dashboard
            </button>
        </form>

        <div class="login-footer">
            <a href="../index.php"><i class="fas fa-arrow-left" style="margin-right:4px"></i>Back to Website</a>
        </div>
        <div class="login-hint">Default: <strong>admin</strong> / <strong>admin123</strong> — change after first login</div>
    </div>
</div>
</body>
</html>
