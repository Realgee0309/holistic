<?php
$pageTitle = 'Messages';
$metaDesc  = 'Send and receive messages with your therapist at Holistic Wellness.';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/includes/user_auth.php';
requireLogin();
sendSecurityHeaders();

$user = getCurrentUser();
$pdo  = getDB();

// Mark therapist messages as read
$pdo->prepare("UPDATE thread_messages SET is_read=1 WHERE user_id=:uid AND sender='therapist' AND is_read=0")
    ->execute([':uid' => $user['id']]);

// Load thread
$thread = $pdo->prepare("SELECT * FROM thread_messages WHERE user_id=:uid ORDER BY created_at ASC");
$thread->execute([':uid' => $user['id']]);
$thread = $thread->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<style>
.msg-page{background:linear-gradient(135deg,#f7f4f1,#e8eeee);min-height:100vh;padding:3rem 0}
.msg-wrap{max-width:820px;margin:0 auto;display:flex;flex-direction:column;gap:0}
.msg-header{background:linear-gradient(135deg,var(--primary),var(--primary-d));color:white;padding:1.5rem 2rem;border-radius:16px 16px 0 0;display:flex;align-items:center;gap:1rem}
.therapist-bubble{width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,0.2);border:2px solid rgba(255,255,255,0.4);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.msg-header-info h2{font-size:1.1rem;margin-bottom:0.15rem}
.msg-header-info span{font-size:0.8rem;opacity:0.8;display:flex;align-items:center;gap:0.4rem}
.online-dot{width:8px;height:8px;border-radius:50%;background:#4ade80;display:inline-block}
.thread{background:white;padding:2rem;min-height:480px;max-height:560px;overflow-y:auto;display:flex;flex-direction:column;gap:1.2rem}
.bubble-wrap{display:flex;gap:0.7rem;align-items:flex-end}
.bubble-wrap.client{flex-direction:row-reverse}
.avatar-sm{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.78rem;font-weight:700;flex-shrink:0}
.avatar-therapist{background:linear-gradient(135deg,var(--primary),var(--primary-d));color:white}
.avatar-client{background:linear-gradient(135deg,var(--accent),var(--accent-d));color:white}
.bubble{max-width:65%;padding:0.85rem 1.1rem;border-radius:16px;font-size:0.9rem;line-height:1.6;position:relative}
.bubble.therapist{background:#f3f4f6;color:#1f2937;border-bottom-left-radius:4px}
.bubble.client{background:linear-gradient(135deg,var(--primary),var(--primary-d));color:white;border-bottom-right-radius:4px}
.bubble-time{font-size:0.68rem;opacity:0.6;margin-top:0.3rem;display:block}
.bubble.client .bubble-time{text-align:right}
.compose{background:white;border-top:1px solid #e5e7eb;padding:1.2rem 1.5rem;border-radius:0 0 16px 16px}
.compose-form{display:flex;gap:0.75rem;align-items:flex-end}
.compose-input{flex:1;border:1.5px solid #e5e7eb;border-radius:12px;padding:0.8rem 1rem;font-size:0.9rem;font-family:inherit;resize:none;min-height:52px;max-height:140px;transition:all 0.3s;outline:none;overflow-y:auto}
.compose-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(90,125,124,0.1)}
.send-btn{width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,var(--primary),var(--primary-d));border:none;color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all 0.3s}
.send-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(90,125,124,0.4)}
.empty-thread{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#aaa;padding:3rem}
.empty-thread i{font-size:3rem;margin-bottom:1rem;opacity:0.3}
.empty-thread p{font-size:0.9rem;text-align:center}
.unread-badge{background:#ef4444;color:white;font-size:0.7rem;padding:0.1rem 0.5rem;border-radius:50px;font-weight:700}
</style>

<div class="msg-page">
  <div class="container">
    <?php renderFlash(); ?>
    <div style="margin-bottom:1rem">
      <a href="dashboard.php" style="color:var(--primary);font-size:0.88rem;text-decoration:none"><i class="fas fa-arrow-left" style="margin-right:4px"></i> Back to Dashboard</a>
    </div>

    <div class="msg-wrap">
      <!-- Header -->
      <div class="msg-header">
        <div class="therapist-bubble">🧑‍⚕️</div>
        <div class="msg-header-info">
          <h2>Dr. Jerald</h2>
          <span><span class="online-dot"></span> Licensed Clinical Psychologist · Secure messaging</span>
        </div>
        <div style="margin-left:auto;font-size:0.78rem;opacity:0.75;text-align:right">
          🔒 End-to-end secure<br>
          <span><?= count($thread) ?> messages</span>
        </div>
      </div>

      <!-- Thread -->
      <div class="thread" id="thread">
        <?php if (empty($thread)): ?>
        <div class="empty-thread">
          <i class="fas fa-comments"></i>
          <p>No messages yet.<br>Send your first message to start the conversation.</p>
        </div>
        <?php else: ?>
        <?php foreach ($thread as $msg): ?>
        <div class="bubble-wrap <?= $msg['sender'] ?>">
          <div class="avatar-sm avatar-<?= $msg['sender'] ?>">
            <?= $msg['sender'] === 'therapist' ? 'Dr' : strtoupper(substr($user['name'], 0, 1)) ?>
          </div>
          <div>
            <div class="bubble <?= $msg['sender'] ?>">
              <?= nl2br(htmlspecialchars($msg['body'])) ?>
              <span class="bubble-time"><?= date('d M, H:i', strtotime($msg['created_at'])) ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Compose -->
      <div class="compose">
        <form method="POST" action="actions/send_message.php" id="msgForm">
          <?= csrfField() ?>
          <div class="compose-form">
            <textarea id="msgBody" name="body" class="compose-input"
                      placeholder="Type your message..." rows="1" required maxlength="3000"></textarea>
            <button type="submit" class="send-btn" title="Send">
              <i class="fas fa-paper-plane"></i>
            </button>
          </div>
        </form>
        <p style="font-size:0.75rem;color:#aaa;margin-top:0.6rem;text-align:center">
          🔒 Messages are confidential · Replies within 24 hours · For emergencies call 999
        </p>
      </div>
    </div>
  </div>
</div>

<script>
// Auto-scroll thread to bottom
const thread = document.getElementById('thread');
if (thread) thread.scrollTop = thread.scrollHeight;

// Auto-grow textarea
const ta = document.getElementById('msgBody');
if (ta) {
  ta.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 140) + 'px';
  });
  // Ctrl/Cmd+Enter to submit
  ta.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      document.getElementById('msgForm').submit();
    }
  });
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
