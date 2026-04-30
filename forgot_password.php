<?php
$pageTitle = 'Forgot Password';
$metaDesc  = 'Reset your Holistic Wellness account password.';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/includes/user_auth.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }
sendSecurityHeaders();
require_once __DIR__ . '/includes/header.php';
?>
<style>
.auth-page{min-height:80vh;display:flex;align-items:center;justify-content:center;padding:4rem 1rem;background:linear-gradient(135deg,#f7f4f1 0%,#e8eeee 100%)}
.auth-card{background:white;border-radius:20px;box-shadow:0 20px 60px rgba(90,125,124,0.12);padding:3rem;width:100%;max-width:420px}
.auth-icon{width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,var(--primary),var(--primary-d));display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 1rem;box-shadow:0 8px 24px rgba(90,125,124,0.3)}
.auth-header{text-align:center;margin-bottom:2rem}
.auth-header h1{font-size:1.6rem;color:var(--dark);margin-bottom:0.4rem}
.auth-header p{font-size:0.9rem;color:#888}
.form-group label{font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.45rem}
.input-wrap{position:relative}
.input-wrap i{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:.9rem;pointer-events:none}
.form-control{width:100%;padding:.8rem 1rem .8rem 2.8rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:.95rem;font-family:inherit;color:#1f2937;transition:all .3s;background:#fafafa}
.form-control:focus{outline:none;border-color:var(--primary);background:white;box-shadow:0 0 0 3px rgba(90,125,124,0.12)}
.btn-auth{width:100%;padding:.9rem;background:linear-gradient(135deg,var(--primary),var(--primary-d));color:white;border:none;border-radius:10px;font-size:1rem;font-weight:600;font-family:inherit;cursor:pointer;transition:all .3s;box-shadow:0 6px 20px rgba(90,125,124,0.3)}
.btn-auth:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(90,125,124,0.4)}
.auth-footer{text-align:center;margin-top:1.5rem;font-size:.88rem;color:#888}
.auth-footer a{color:var(--primary);font-weight:600;text-decoration:none}
.info-box{background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:1rem 1.2rem;font-size:.85rem;color:#1e40af;margin-bottom:1.5rem;display:flex;gap:.6rem;align-items:flex-start}
.success-state{text-align:center;padding:1rem 0}
.success-state .big-icon{font-size:3.5rem;margin-bottom:1rem}
.success-state h2{color:var(--primary);margin-bottom:.8rem}
.success-state p{color:#555;font-size:.9rem;line-height:1.7}
</style>

<div class="auth-page">
  <div class="auth-card">
    <?php renderFlash(); ?>
    <div class="auth-header">
      <div class="auth-icon">🔐</div>
      <h1>Forgot Password?</h1>
      <p>Enter your email and we'll send reset instructions.</p>
    </div>

    <?php if (isset($_GET['sent'])): ?>
    <div class="success-state">
      <div class="big-icon">📬</div>
      <h2>Check Your Email</h2>
      <p>If an account exists for that address, a password reset link has been sent. Check your inbox (and spam folder).</p>
      <p style="margin-top:1.5rem"><a href="login.php" style="color:var(--primary);font-weight:600">← Back to Sign In</a></p>
    </div>
    <?php else: ?>

    <div class="info-box">
      <i class="fas fa-info-circle" style="margin-top:2px;flex-shrink:0"></i>
      <span>On XAMPP, the reset link will appear in a on-screen notice since no mail server is configured. In production, configure PHPMailer/SMTP.</span>
    </div>

    <form method="POST" action="actions/forgot_password.php">
      <?= csrfField() ?>
      <div class="form-group" style="margin-bottom:1.3rem">
        <label for="email">Email Address</label>
        <div class="input-wrap">
          <i class="fas fa-envelope"></i>
          <input type="email" id="email" name="email" class="form-control" required
                 placeholder="you@email.com" autocomplete="email">
        </div>
      </div>
      <button type="submit" class="btn-auth">
        <i class="fas fa-paper-plane" style="margin-right:.5rem"></i> Send Reset Link
      </button>
    </form>

    <div class="auth-footer">
      <a href="login.php">← Back to Sign In</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
