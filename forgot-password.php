<?php
$pageTitle = 'Forgot Password';
$metaDesc  = 'Reset your Holistic Wellness account password.';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/user_auth.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

// Grab the reset link from session (if generated)
if (session_status() === PHP_SESSION_NONE) session_start();
$resetLink = $_SESSION['reset_link'] ?? null;
unset($_SESSION['reset_link']);

require_once __DIR__ . '/includes/header.php';
?>
<style>
.auth-page { min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 4rem 1rem; background: linear-gradient(135deg, #f7f4f1 0%, #e8eeee 100%); }
.auth-card { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(90,125,124,0.12); padding: 3rem; width: 100%; max-width: 420px; }
.auth-header { text-align: center; margin-bottom: 2.2rem; }
.auth-icon { width: 64px; height: 64px; border-radius: 18px; background: linear-gradient(135deg, var(--primary), var(--primary-d)); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1rem; box-shadow: 0 8px 24px rgba(90,125,124,0.3); }
.auth-header h1 { font-size: 1.7rem; color: var(--dark); margin-bottom: 0.4rem; }
.auth-header p { font-size: 0.9rem; color: #888; line-height: 1.6; }
.form-group { margin-bottom: 1.3rem; }
.form-group label { font-size: 0.85rem; font-weight: 600; color: #374151; display: block; margin-bottom: 0.45rem; }
.input-wrap { position: relative; }
.input-wrap i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 0.9rem; pointer-events: none; }
.form-control { width: 100%; padding: 0.8rem 1rem 0.8rem 2.8rem; border: 1.5px solid #e5e7eb; border-radius: 10px; font-size: 0.95rem; font-family: inherit; color: #1f2937; transition: all 0.3s; background: #fafafa; }
.form-control:focus { outline: none; border-color: var(--primary); background: white; box-shadow: 0 0 0 3px rgba(90,125,124,0.12); }
.btn-auth { width: 100%; padding: 0.9rem; background: linear-gradient(135deg, var(--primary), var(--primary-d)); color: white; border: none; border-radius: 10px; font-size: 1rem; font-weight: 600; font-family: inherit; cursor: pointer; transition: all 0.3s; box-shadow: 0 6px 20px rgba(90,125,124,0.3); }
.btn-auth:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(90,125,124,0.4); }
.auth-footer { text-align: center; margin-top: 1.8rem; padding-top: 1.5rem; border-top: 1px solid #f3f4f6; font-size: 0.88rem; color: #888; }
.auth-footer a { color: var(--primary); font-weight: 600; text-decoration: none; }
.info-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 1rem 1.1rem; font-size: 0.83rem; color: #1e40af; line-height: 1.6; margin-bottom: 1.5rem; display: flex; gap: 0.6rem; align-items: flex-start; }
.info-box i { margin-top: 2px; flex-shrink: 0; }
.reset-link-box { background: linear-gradient(135deg, #ecfdf5, #d1fae5); border: 1.5px solid #6ee7b7; border-radius: 12px; padding: 1.2rem 1.3rem; margin-top: 1.5rem; text-align: center; }
.reset-link-box p { font-size: 0.85rem; color: #065f46; margin-bottom: 0.8rem; font-weight: 500; }
.reset-link-box a { display: inline-block; padding: 0.7rem 1.6rem; background: linear-gradient(135deg, #10b981, #059669); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; box-shadow: 0 4px 14px rgba(16,185,129,0.3); }
.reset-link-box a:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16,185,129,0.4); }
</style>

<div class="auth-page">
    <div class="auth-card">
        <?php renderFlash(); ?>
        <div class="auth-header">
            <div class="auth-icon">🔓</div>
            <h1>Forgot Password?</h1>
            <p>Enter your email address and we'll generate a link to reset your password.</p>
        </div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <span>The reset link will be valid for <strong>30 minutes</strong> and can only be used once.</span>
        </div>

        <form method="POST" action="actions/forgot-password.php" novalidate>
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" required
                           placeholder="you@email.com">
                </div>
            </div>
            <button type="submit" class="btn-auth">
                <i class="fas fa-paper-plane" style="margin-right:0.5rem"></i> Send Reset Link
            </button>
        </form>

        <?php if ($resetLink): ?>
        <div class="reset-link-box">
            <p>✅ Click the button below to reset your password:</p>
            <a href="<?= htmlspecialchars($resetLink) ?>">
                <i class="fas fa-lock-open" style="margin-right:0.4rem"></i> Reset My Password
            </a>
        </div>
        <?php endif; ?>

        <div class="auth-footer">
            Remembered it? <a href="login.php">Back to Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
