<?php
$pageTitle = 'Reset Password';
$metaDesc  = 'Set a new password for your Holistic Wellness account.';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/user_auth.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

$token = trim($_GET['token'] ?? '');
$validToken = null;

if ($token) {
    $pdo = getDB();
    $row = $pdo->prepare("SELECT * FROM password_resets WHERE token=:t AND used=0 AND expires_at > NOW()");
    $row->execute([':t' => $token]);
    $validToken = $row->fetch();
}

require_once __DIR__ . '/includes/header.php';
?>
<style>
.auth-page { min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 4rem 1rem; background: linear-gradient(135deg, #f7f4f1 0%, #e8eeee 100%); }
.auth-card { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(90,125,124,0.12); padding: 3rem; width: 100%; max-width: 420px; }
.auth-header { text-align: center; margin-bottom: 2.2rem; }
.auth-icon { width: 64px; height: 64px; border-radius: 18px; background: linear-gradient(135deg, var(--primary), var(--primary-d)); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1rem; box-shadow: 0 8px 24px rgba(90,125,124,0.3); }
.auth-header h1 { font-size: 1.7rem; color: var(--dark); margin-bottom: 0.4rem; }
.auth-header p { font-size: 0.9rem; color: #888; }
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
.error-box { background: #fff1f2; border: 1px solid #fecdd3; border-radius: 10px; padding: 1.2rem; text-align: center; color: #be123c; font-size: 0.9rem; line-height: 1.6; }
.error-box i { font-size: 2rem; display: block; margin-bottom: 0.6rem; }
.pwd-strength { height: 4px; border-radius: 2px; background: #e5e7eb; margin-top: 0.5rem; overflow: hidden; }
.pwd-strength-bar { height: 100%; border-radius: 2px; width: 0; transition: width 0.3s, background 0.3s; }
.pwd-hint { font-size: 0.75rem; color: #9ca3af; margin-top: 0.3rem; }
</style>

<div class="auth-page">
    <div class="auth-card">
        <?php renderFlash(); ?>

        <?php if (!$validToken): ?>
        <div class="error-box">
            <i class="fas fa-link-slash"></i>
            <strong>This link is invalid or has expired.</strong><br>
            Reset links are only valid for 30 minutes and can only be used once.
        </div>
        <div class="auth-footer">
            <a href="forgot-password.php">← Request a new reset link</a>
        </div>

        <?php else: ?>
        <div class="auth-header">
            <div class="auth-icon">🔒</div>
            <h1>Set New Password</h1>
            <p>Choose a strong password for your account.</p>
        </div>

        <form method="POST" action="actions/reset-password.php" novalidate id="resetForm">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="form-group">
                <label for="password">New Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control"
                           required placeholder="Min. 8 characters" oninput="checkStrength(this.value)">
                </div>
                <div class="pwd-strength"><div class="pwd-strength-bar" id="strengthBar"></div></div>
                <div class="pwd-hint" id="strengthLabel">Enter your new password</div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password"
                           class="form-control" required placeholder="Repeat new password">
                </div>
            </div>
            <button type="submit" class="btn-auth">
                <i class="fas fa-check" style="margin-right:0.5rem"></i> Save New Password
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>
<script>
function checkStrength(val) {
    var bar = document.getElementById('strengthBar');
    var lbl = document.getElementById('strengthLabel');
    var score = 0;
    if (val.length >= 8)  score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    var colors = ['#ef4444','#f59e0b','#22c55e','#10b981'];
    var labels = ['Weak','Fair','Good','Strong'];
    var widths = ['25%','50%','75%','100%'];
    if (val.length === 0) { bar.style.width = '0'; lbl.textContent = 'Enter your new password'; return; }
    var idx = Math.min(score - 1, 3);
    bar.style.width  = widths[idx];
    bar.style.background = colors[idx];
    lbl.textContent  = labels[idx];
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
