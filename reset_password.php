<?php
$pageTitle = 'Reset Password';
$metaDesc  = 'Set a new password for your Holistic Wellness account.';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/includes/user_auth.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }
sendSecurityHeaders();

$token = trim($_GET['token'] ?? '');
$valid = false;
$resetEmail = '';

if ($token) {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token=:t AND used=0 AND expires_at > NOW() LIMIT 1");
    $stmt->execute([':t' => $token]);
    $row = $stmt->fetch();
    if ($row) { $valid = true; $resetEmail = $row['email']; }
}

require_once __DIR__ . '/includes/header.php';
?>
<style>
.auth-page{min-height:80vh;display:flex;align-items:center;justify-content:center;padding:4rem 1rem;background:linear-gradient(135deg,#f7f4f1 0%,#e8eeee 100%)}
.auth-card{background:white;border-radius:20px;box-shadow:0 20px 60px rgba(90,125,124,0.12);padding:3rem;width:100%;max-width:430px}
.auth-icon{width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,var(--primary),var(--primary-d));display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 1rem;box-shadow:0 8px 24px rgba(90,125,124,0.3)}
.auth-header{text-align:center;margin-bottom:2rem}
.auth-header h1{font-size:1.6rem;color:var(--dark);margin-bottom:.4rem}
.auth-header p{font-size:.9rem;color:#888}
.form-group{margin-bottom:1.3rem}
.form-group label{font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.45rem}
.input-wrap{position:relative}
.input-wrap i{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:.9rem;pointer-events:none}
.form-control{width:100%;padding:.8rem 1rem .8rem 2.8rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:.95rem;font-family:inherit;color:#1f2937;transition:all .3s;background:#fafafa}
.form-control:focus{outline:none;border-color:var(--primary);background:white;box-shadow:0 0 0 3px rgba(90,125,124,0.12)}
.btn-auth{width:100%;padding:.9rem;background:linear-gradient(135deg,var(--primary),var(--primary-d));color:white;border:none;border-radius:10px;font-size:1rem;font-weight:600;font-family:inherit;cursor:pointer;transition:all .3s;box-shadow:0 6px 20px rgba(90,125,124,0.3)}
.btn-auth:hover{transform:translateY(-2px)}
.strength-bar{height:4px;border-radius:2px;margin-top:.4rem;background:#e5e7eb;overflow:hidden}
.strength-fill{height:100%;border-radius:2px;transition:all .3s;width:0}
.error-state{text-align:center;padding:1.5rem 0}
.error-state .big-icon{font-size:3rem;margin-bottom:1rem}
</style>

<div class="auth-page">
  <div class="auth-card">
    <?php renderFlash(); ?>

    <?php if (!$valid): ?>
    <div class="error-state">
      <div class="big-icon">⏰</div>
      <div class="auth-header">
        <h1>Link Expired</h1>
        <p>This password reset link is invalid or has already been used.</p>
      </div>
      <a href="forgot_password.php" class="btn-auth" style="display:block;text-decoration:none;text-align:center;padding:.9rem">Request a New Link</a>
    </div>

    <?php else: ?>
    <div class="auth-header">
      <div class="auth-icon">🔑</div>
      <h1>Set New Password</h1>
      <p>Choose a strong password for <strong><?= htmlspecialchars($resetEmail) ?></strong></p>
    </div>

    <form method="POST" action="actions/reset_password.php">
      <?= csrfField() ?>
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div class="form-group">
        <label for="password">New Password</label>
        <div class="input-wrap">
          <i class="fas fa-lock"></i>
          <input type="password" id="password" name="password" class="form-control"
                 required placeholder="Min. 8 characters" autocomplete="new-password">
        </div>
        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
        <div style="font-size:.75rem;color:#aaa;margin-top:.3rem" id="strengthLabel"></div>
      </div>
      <div class="form-group">
        <label for="confirm">Confirm Password</label>
        <div class="input-wrap">
          <i class="fas fa-lock"></i>
          <input type="password" id="confirm" name="confirm" class="form-control"
                 required placeholder="Repeat password" autocomplete="new-password">
        </div>
      </div>
      <button type="submit" class="btn-auth">
        <i class="fas fa-check" style="margin-right:.5rem"></i> Update Password
      </button>
    </form>
    <?php endif; ?>
  </div>
</div>

<script>
const pwd=document.getElementById('password');
const fill=document.getElementById('strengthFill');
const lbl=document.getElementById('strengthLabel');
if(pwd){
  const levels=[{c:'#ef4444',t:'Too short',w:'20%'},{c:'#f97316',t:'Weak',w:'40%'},{c:'#eab308',t:'Fair',w:'60%'},{c:'#22c55e',t:'Strong',w:'80%'},{c:'#16a34a',t:'Very strong',w:'100%'}];
  pwd.addEventListener('input',function(){
    const v=this.value;let s=0;
    if(v.length>=8)s++;if(v.length>=12)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
    const l=levels[Math.min(s,4)];
    fill.style.width=v.length?l.w:'0';fill.style.background=l.c;lbl.textContent=v.length?l.t:'';lbl.style.color=l.c;
  });
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
