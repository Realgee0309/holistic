<?php
require_once __DIR__ . '/includes/admin_auth.php';

$pdo = getDB();

// Mark single as read
if (isset($_GET['read'])) {
    $id = (int)$_GET['read'];
    if ($id) $pdo->prepare("UPDATE contacts SET is_read=1 WHERE id=:id")->execute([':id'=>$id]);
    header('Location: contacts.php');
    exit;
}
// Mark all read
if (isset($_GET['readall'])) {
    $pdo->query("UPDATE contacts SET is_read=1");
    header('Location: contacts.php?msg=readall');
    exit;
}
// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id) $pdo->prepare("DELETE FROM contacts WHERE id=:id")->execute([':id'=>$id]);
    header('Location: contacts.php?msg=deleted');
    exit;
}

// Fetch replies for all visible contacts (keyed by contact_id)
$repliesRaw = $pdo->query("SELECT * FROM admin_replies ORDER BY created_at ASC")->fetchAll();
$repliesByContact = [];
foreach ($repliesRaw as $r) {
    $repliesByContact[$r['contact_id']][] = $r;
}

// Counts
$unreadCount = $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn();
$totalCount  = $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
$readCount   = $totalCount - $unreadCount;

// Filter
$filterRead = $_GET['filter'] ?? '';
$where = '';
if ($filterRead === 'unread') $where = 'WHERE is_read=0';
if ($filterRead === 'read')   $where = 'WHERE is_read=1';

// Pagination
$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$total   = $pdo->query("SELECT COUNT(*) FROM contacts $where")->fetchColumn();
$pages   = max(1, ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT * FROM contacts $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute();
$contacts = $stmt->fetchAll();

adminHead('Messages', 'Manage client contact messages');
?>

<!-- Flash -->
<?php if (isset($_GET['msg'])): ?>
<div class="flash <?= in_array($_GET['msg'],['deleted','reply_error']) ? 'error' : 'success' ?>">
    <i class="fas <?= in_array($_GET['msg'],['deleted','reply_error']) ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
    <?php
        $msgs = [
            'deleted'     => 'Message deleted.',
            'readall'     => 'All messages marked as read.',
            'reply_sent'  => 'Reply sent and saved successfully.',
            'reply_error' => 'Could not send reply — please try again.',
        ];
        echo $msgs[$_GET['msg']] ?? 'Done.';
    ?>
</div>
<?php endif; ?>

<style>
.reply-panel { display:none; padding:1rem 1.2rem 1.2rem; background:#f0fdf4; border-top:1px solid #bbf7d0; }
.reply-panel.open { display:block; animation:fadeIn 0.2s ease; }
.reply-history { margin-bottom:0.9rem; }
.reply-bubble { background:linear-gradient(135deg,var(--primary),var(--primary-d)); color:white; border-radius:12px 12px 12px 3px; padding:0.75rem 1rem; font-size:0.83rem; line-height:1.6; margin-bottom:0.5rem; max-width:90%; }
.reply-bubble-meta { font-size:0.7rem; color:#9ca3af; margin-bottom:0.5rem; }
@keyframes fadeIn { from{opacity:0;transform:translateY(-4px)} to{opacity:1;transform:none} }
</style>

<!-- Filter Tabs -->
<div class="filter-tabs">
    <a href="contacts.php" class="filter-tab <?= !$filterRead ? 'active' : '' ?>">
        All (<?= $totalCount ?>)
    </a>
    <a href="contacts.php?filter=unread" class="filter-tab <?= $filterRead==='unread' ? 'active' : '' ?>"
       style="<?= ($filterRead !== 'unread' && $unreadCount > 0) ? 'border-color:#93c5fd;color:#1e40af;background:#dbeafe' : '' ?>">
        <i class="fas fa-circle" style="font-size:0.5rem;color:#3b82f6"></i> Unread (<?= $unreadCount ?>)
    </a>
    <a href="contacts.php?filter=read" class="filter-tab <?= $filterRead==='read' ? 'active' : '' ?>">
        Read (<?= $readCount ?>)
    </a>
    <?php if ($unreadCount > 0): ?>
    <a href="contacts.php?readall=1" class="btn btn-success btn-sm" style="margin-left:auto">
        <i class="fas fa-check-double"></i> Mark All Read
    </a>
    <?php endif; ?>
</div>

<!-- Table Panel -->
<div class="panel">
    <div class="panel-head">
        <div class="panel-head-left">
            <i class="fas fa-envelope panel-icon"></i>
            <h3>Contact Messages</h3>
            <?php if ($unreadCount > 0): ?>
            <span class="badge badge-unread"><?= $unreadCount ?> new</span>
            <?php endif; ?>
        </div>
        <span style="font-size:0.78rem;color:var(--text-muted)">Showing <?= count($contacts) ?> of <?= $total ?></span>
    </div>

    <?php if (empty($contacts)): ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <p>No messages found<?= $filterRead ? ' in "' . $filterRead . '" filter' : '' ?>.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Sender</th>
                <th>Subject</th>
                <th>Message Preview</th>
                <th>Status</th>
                <th>Received</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($contacts as $c):
            $initials = strtoupper(substr($c['name'], 0, 1)) . (strpos($c['name'], ' ') !== false ? strtoupper(substr(strrchr($c['name'], ' '), 1, 1)) : '');
            $isUnread = !$c['is_read'];
        ?>
        <tr style="<?= $isUnread ? 'background:#fafeff;' : '' ?>">
            <td style="font-size:0.75rem;color:var(--text-muted);font-weight:600">
                #<?= $c['id'] ?>
                <?php if ($isUnread): ?>
                <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#3b82f6;margin-left:3px;vertical-align:middle"></span>
                <?php endif; ?>
            </td>
            <td>
                <div class="client-cell">
                    <div class="client-avatar" style="<?= !$isUnread ? 'opacity:0.55' : '' ?>"><?= $initials ?></div>
                    <div>
                        <div class="client-name" style="<?= $isUnread ? 'font-weight:700' : '' ?>"><?= htmlspecialchars($c['name']) ?></div>
                        <div class="client-email"><?= htmlspecialchars($c['email']) ?></div>
                    </div>
                </div>
            </td>
            <td>
                <span style="font-size:0.82rem;font-weight:<?= $isUnread ? '600' : '400' ?>;color:var(--dark)">
                    <?= htmlspecialchars($c['subject']) ?>
                </span>
            </td>
            <td style="max-width:240px">
                <span style="font-size:0.78rem;color:var(--text-muted)">
                    <?= htmlspecialchars(mb_substr($c['message'], 0, 90)) ?><?= mb_strlen($c['message']) > 90 ? '…' : '' ?>
                </span>
            </td>
            <td>
                <span class="badge <?= $isUnread ? 'badge-unread' : 'badge-read' ?>">
                    <?= $isUnread ? 'Unread' : 'Read' ?>
                </span>
                <?php if (!empty($repliesByContact[$c['id']])): ?>
                <span class="badge" style="background:#d1fae5;color:#065f46;margin-top:3px">
                    <i class="fas fa-reply" style="font-size:0.6rem"></i> Replied
                </span>
                <?php endif; ?>
            </td>
            <td style="font-size:0.75rem;color:var(--text-muted);white-space:nowrap">
                <?= date('d M Y', strtotime($c['created_at'])) ?><br>
                <span style="font-size:0.7rem"><?= date('H:i', strtotime($c['created_at'])) ?></span>
            </td>
            <td>
                <div style="display:flex;gap:0.3rem">
                    <button onclick="toggleReply(<?= $c['id'] ?>)"
                       class="btn btn-primary btn-icon btn-sm" title="Reply in-app">
                        <i class="fas fa-reply"></i>
                    </button>
                    <a href="mailto:<?= htmlspecialchars($c['email']) ?>?subject=Re: <?= urlencode($c['subject']) ?>"
                       class="btn btn-ghost btn-icon btn-sm" title="Reply by email">
                        <i class="fas fa-envelope"></i>
                    </a>
                    <?php if ($isUnread): ?>
                    <a href="contacts.php?read=<?= $c['id'] ?>"
                       class="btn btn-success btn-icon btn-sm" title="Mark as read">
                        <i class="fas fa-check"></i>
                    </a>
                    <?php endif; ?>
                    <a href="contacts.php?delete=<?= $c['id'] ?>"
                       onclick="return confirm('Delete message from <?= htmlspecialchars(addslashes($c['name'])) ?>?')"
                       class="btn btn-danger btn-icon btn-sm" title="Delete">
                        <i class="fas fa-trash-can"></i>
                    </a>
                </div>
            </td>
        </tr>
        <!-- Reply Expand Panel -->
        <tr id="reply-row-<?= $c['id'] ?>" style="display:none">
            <td colspan="7" style="padding:0">
                <div class="reply-panel open">
                    <?php if (!empty($repliesByContact[$c['id']])): ?>
                    <div class="reply-history">
                        <div style="font-size:0.75rem;font-weight:600;color:var(--primary);margin-bottom:0.6rem;">Previous replies</div>
                        <?php foreach ($repliesByContact[$c['id']] as $rep): ?>
                        <div class="reply-bubble-meta"><?= date('d M Y H:i', strtotime($rep['created_at'])) ?> · You</div>
                        <div class="reply-bubble"><?= nl2br(htmlspecialchars($rep['reply'])) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <form method="POST" action="actions/send-reply.php" style="display:flex;gap:0.6rem;align-items:flex-end">
                        <input type="hidden" name="contact_id" value="<?= $c['id'] ?>">
                        <div style="flex:1">
                            <label style="font-size:0.78rem;font-weight:600;color:#374151;display:block;margin-bottom:0.35rem">Reply to <?= htmlspecialchars($c['name']) ?></label>
                            <textarea name="reply" rows="3" required
                                placeholder="Type your reply here..."
                                style="width:100%;border:1.5px solid #a7f3d0;border-radius:8px;padding:0.65rem 0.85rem;font-family:inherit;font-size:0.84rem;resize:vertical;background:white"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="height:fit-content;white-space:nowrap">
                            <i class="fas fa-paper-plane"></i> Send Reply
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?page=<?= $page-1 ?><?= $filterRead ? '&filter='.$filterRead : '' ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
    <?php endif; ?>
    <?php for ($p = 1; $p <= $pages; $p++): ?>
    <a href="?page=<?= $p ?><?= $filterRead ? '&filter='.$filterRead : '' ?>" class="page-btn <?= $p===$page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
    <?php if ($page < $pages): ?>
    <a href="?page=<?= $page+1 ?><?= $filterRead ? '&filter='.$filterRead : '' ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
function toggleReply(id) {
    var row = document.getElementById('reply-row-' + id);
    if (!row) return;
    row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
    if (row.style.display === 'table-row') row.querySelector('textarea').focus();
}
<?php if (isset($_GET['view'], $_GET['msg']) && $_GET['msg'] === 'reply_sent'): ?>
window.addEventListener('DOMContentLoaded', function() {
    var row = document.getElementById('reply-row-<?= (int)$_GET['view'] ?>');
    if (row) row.style.display = 'table-row';
});
<?php endif; ?>
</script>

<?php adminFoot(); ?>
