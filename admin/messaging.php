<?php
/**
 * Admin — Two-Way Messaging (per-client thread view)
 */
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/security.php';

$pdo = getDB();

// ── Handle therapist reply ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    csrfVerify();
    $uid  = cleanInt($_POST['user_id'] ?? 0);
    $body = cleanText($_POST['body']   ?? '', 3000);
    if ($uid && strlen(trim($body)) > 0) {
        $pdo->prepare("INSERT INTO thread_messages (user_id, sender, body) VALUES (:uid,'therapist',:body)")
            ->execute([':uid' => $uid, ':body' => $body]);
        // Mark client messages as read
        $pdo->prepare("UPDATE thread_messages SET is_read=1 WHERE user_id=:uid AND sender='client'")
            ->execute([':uid' => $uid]);
    }
    header('Location: messaging.php?uid=' . $uid);
    exit;
}

// ── Mark all messages as read for a thread ──────────────────
if (isset($_GET['mark_read'])) {
    $uid = cleanInt($_GET['mark_read']);
    if ($uid) {
        $pdo->prepare("UPDATE thread_messages SET is_read=1 WHERE user_id=:uid AND sender='client'")
            ->execute([':uid' => $uid]);
    }
    header('Location: messaging.php?uid=' . $uid);
    exit;
}

// ── Get all client threads with unread counts ────────────────
$threads = $pdo->query("
    SELECT u.id, u.name, u.email, u.is_anonymous,
        COUNT(tm.id)                                          AS total_msgs,
        SUM(tm.sender='client' AND tm.is_read=0)             AS unread_client,
        MAX(tm.created_at)                                    AS last_activity,
        (SELECT body FROM thread_messages WHERE user_id=u.id ORDER BY created_at DESC LIMIT 1) AS last_msg
    FROM users u
    JOIN thread_messages tm ON tm.user_id = u.id
    GROUP BY u.id
    ORDER BY last_activity DESC
")->fetchAll();

// ── Load single thread if uid passed ────────────────────────
$activeUser = null;
$activeThread = [];
if (isset($_GET['uid'])) {
    $uid = cleanInt($_GET['uid']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=:id");
    $stmt->execute([':id' => $uid]);
    $activeUser = $stmt->fetch();
    if ($activeUser) {
        $activeThread = $pdo->prepare("SELECT * FROM thread_messages WHERE user_id=:uid ORDER BY created_at ASC");
        $activeThread->execute([':uid' => $uid]);
        $activeThread = $activeThread->fetchAll();
        // Auto-mark as read when admin opens thread
        $pdo->prepare("UPDATE thread_messages SET is_read=1 WHERE user_id=:uid AND sender='client'")
            ->execute([':uid' => $uid]);
    }
}

$totalUnread = $pdo->query("SELECT COUNT(*) FROM thread_messages WHERE sender='client' AND is_read=0")->fetchColumn();

adminHead('Messaging', 'Two-way client conversations');
?>
<style>
.messaging-layout{display:grid;grid-template-columns:300px 1fr;gap:0;height:calc(100vh - var(--topbar-h) - 4rem);min-height:520px;border-radius:var(--radius);overflow:hidden;border:1px solid var(--border);box-shadow:var(--shadow)}
.thread-list{background:white;border-right:1px solid var(--border);overflow-y:auto;display:flex;flex-direction:column}
.thread-list-header{padding:1rem 1.2rem;border-bottom:1px solid var(--border);font-size:.82rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.8px;display:flex;align-items:center;justify-content:space-between;background:#f9fafb;flex-shrink:0}
.thread-item{display:flex;gap:.75rem;padding:.9rem 1.2rem;border-bottom:1px solid #f3f4f6;cursor:pointer;text-decoration:none;color:inherit;transition:background .2s;align-items:flex-start}
.thread-item:hover{background:#f9fafb}
.thread-item.active{background:#eff6ff;border-right:3px solid var(--primary)}
.thread-avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));color:white;font-size:.88rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.thread-meta{flex:1;min-width:0}
.thread-name{font-size:.85rem;font-weight:600;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.thread-preview{font-size:.75rem;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:.15rem}
.thread-right{display:flex;flex-direction:column;align-items:flex-end;gap:.3rem;flex-shrink:0}
.thread-time{font-size:.7rem;color:var(--text-muted);white-space:nowrap}
.unread-dot{width:20px;height:20px;border-radius:50%;background:#ef4444;color:white;font-size:.68rem;font-weight:700;display:flex;align-items:center;justify-content:center}
.chat-area{background:#f8f9fa;display:flex;flex-direction:column}
.chat-header{background:white;padding:1rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:.75rem;flex-shrink:0}
.chat-header-info h3{font-size:.95rem;font-weight:600;color:var(--dark);margin-bottom:.1rem}
.chat-header-info span{font-size:.75rem;color:var(--text-muted)}
.chat-thread{flex:1;overflow-y:auto;padding:1.5rem;display:flex;flex-direction:column;gap:1rem}
.bubble-wrap{display:flex;gap:.6rem;align-items:flex-end}
.bubble-wrap.therapist{flex-direction:row-reverse}
.avt{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;flex-shrink:0}
.avt-client{background:linear-gradient(135deg,var(--primary),var(--accent));color:white}
.avt-therapist{background:linear-gradient(135deg,#1e2a35,#374151);color:white}
.bubble{max-width:62%;padding:.75rem 1rem;border-radius:14px;font-size:.85rem;line-height:1.6}
.bubble.client{background:white;color:#1f2937;border-bottom-left-radius:4px;box-shadow:var(--shadow)}
.bubble.therapist{background:linear-gradient(135deg,var(--primary),var(--primary-d));color:white;border-bottom-right-radius:4px}
.btime{font-size:.67rem;opacity:.6;margin-top:.25rem;display:block}
.bubble.therapist .btime{text-align:right}
.compose-area{background:white;border-top:1px solid var(--border);padding:1rem 1.5rem;flex-shrink:0}
.compose-row{display:flex;gap:.75rem;align-items:flex-end}
.compose-ta{flex:1;border:1.5px solid #e5e7eb;border-radius:10px;padding:.75rem 1rem;font-size:.88rem;font-family:inherit;resize:none;min-height:52px;max-height:120px;transition:all .3s;outline:none}
.compose-ta:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(90,125,124,0.1)}
.send-btn{height:44px;padding:0 1.2rem;border-radius:9px;background:var(--primary);color:white;border:none;font-size:.85rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:.4rem;transition:all .3s;white-space:nowrap}
.send-btn:hover{background:var(--primary-d)}
.no-thread-selected{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--text-muted)}
.no-thread-selected i{font-size:3rem;opacity:.2;margin-bottom:1rem}
.empty-threads{padding:2rem;text-align:center;color:var(--text-muted)}
.empty-threads i{font-size:2rem;opacity:.25;display:block;margin-bottom:.75rem}
</style>

<?php if ($totalUnread > 0): ?>
<div class="flash success"><i class="fas fa-comment-dots"></i> <?= $totalUnread ?> unread client message<?= $totalUnread > 1 ? 's' : '' ?> waiting for a reply.</div>
<?php endif; ?>

<div class="messaging-layout">

  <!-- Thread List -->
  <div class="thread-list">
    <div class="thread-list-header">
      <span>Clients</span>
      <?php if ($totalUnread > 0): ?>
      <span style="background:#ef4444;color:white;font-size:.68rem;padding:.15rem .5rem;border-radius:50px"><?= $totalUnread ?> new</span>
      <?php endif; ?>
    </div>

    <?php if (empty($threads)): ?>
    <div class="empty-threads">
      <i class="fas fa-comments"></i>
      <p style="font-size:.83rem">No client conversations yet.</p>
    </div>
    <?php else: ?>
    <?php foreach ($threads as $t):
      $initials = strtoupper(substr($t['name'],0,1)) . (strpos($t['name'],' ')!==false ? strtoupper(substr(strrchr($t['name'],' '),1,1)):'');
      $isActive = isset($_GET['uid']) && cleanInt($_GET['uid']) === (int)$t['id'];
    ?>
    <a href="messaging.php?uid=<?= $t['id'] ?>" class="thread-item <?= $isActive ? 'active' : '' ?>">
      <div class="thread-avatar"><?= $t['is_anonymous'] ? '🔒' : $initials ?></div>
      <div class="thread-meta">
        <div class="thread-name"><?= $t['is_anonymous'] ? 'Anonymous User' : htmlspecialchars($t['name']) ?></div>
        <div class="thread-preview"><?= htmlspecialchars(mb_substr($t['last_msg'] ?? '', 0, 45)) ?>…</div>
      </div>
      <div class="thread-right">
        <div class="thread-time"><?= date('d M', strtotime($t['last_activity'])) ?></div>
        <?php if ($t['unread_client'] > 0): ?>
        <div class="unread-dot"><?= $t['unread_client'] ?></div>
        <?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Chat Area -->
  <div class="chat-area">
    <?php if ($activeUser): ?>

    <div class="chat-header">
      <div class="thread-avatar" style="width:42px;height:42px">
        <?= $activeUser['is_anonymous'] ? '🔒' : strtoupper(substr($activeUser['name'],0,1)) ?>
      </div>
      <div class="chat-header-info">
        <h3><?= $activeUser['is_anonymous'] ? 'Anonymous Client' : htmlspecialchars($activeUser['name']) ?></h3>
        <span><?= htmlspecialchars($activeUser['email']) ?> · <?= count($activeThread) ?> messages</span>
      </div>
      <div style="margin-left:auto;display:flex;gap:.5rem">
        <a href="mailto:<?= htmlspecialchars($activeUser['email']) ?>" class="btn btn-ghost btn-sm btn-icon" title="Email client"><i class="fas fa-envelope"></i></a>
        <a href="users.php?view=<?= $activeUser['id'] ?>" class="btn btn-ghost btn-sm" style="font-size:.78rem">View Profile</a>
      </div>
    </div>

    <div class="chat-thread" id="chatThread">
      <?php if (empty($activeThread)): ?>
      <div style="flex:1;display:flex;align-items:center;justify-content:center;color:var(--text-muted);flex-direction:column;gap:.5rem">
        <i class="fas fa-comment-slash" style="font-size:2rem;opacity:.2"></i>
        <span style="font-size:.85rem">No messages yet — reply below to start the conversation.</span>
      </div>
      <?php else: ?>
      <?php foreach ($activeThread as $msg): ?>
      <div class="bubble-wrap <?= $msg['sender'] ?>">
        <div class="avt avt-<?= $msg['sender'] ?>">
          <?= $msg['sender'] === 'therapist' ? 'Dr' : strtoupper(substr($activeUser['name'],0,1)) ?>
        </div>
        <div>
          <div class="bubble <?= $msg['sender'] ?>">
            <?= nl2br(htmlspecialchars($msg['body'])) ?>
            <span class="btime"><?= date('d M Y, H:i', strtotime($msg['created_at'])) ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Reply Form -->
    <div class="compose-area">
      <form method="POST" id="replyForm">
        <?= csrfField() ?>
        <input type="hidden" name="reply" value="1">
        <input type="hidden" name="user_id" value="<?= $activeUser['id'] ?>">
        <div class="compose-row">
          <textarea name="body" class="compose-ta" id="replyBody"
                    placeholder="Type your reply as Dr. Jerald…" required maxlength="3000" rows="1"></textarea>
          <button type="submit" class="send-btn">
            <i class="fas fa-paper-plane"></i> Send
          </button>
        </div>
        <p style="font-size:.72rem;color:var(--text-muted);margin-top:.5rem">Ctrl+Enter to send · Message is visible to client in their dashboard</p>
      </form>
    </div>

    <?php else: ?>
    <div class="no-thread-selected">
      <i class="fas fa-comments"></i>
      <p style="font-size:.9rem">Select a conversation to view messages</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
const ct = document.getElementById('chatThread');
if (ct) ct.scrollTop = ct.scrollHeight;
const rb = document.getElementById('replyBody');
if (rb) {
  rb.addEventListener('input', function() { this.style.height='auto'; this.style.height=Math.min(this.scrollHeight,120)+'px'; });
  rb.addEventListener('keydown', function(e) { if ((e.ctrlKey||e.metaKey)&&e.key==='Enter') document.getElementById('replyForm').submit(); });
}
</script>

<?php adminFoot(); ?>
