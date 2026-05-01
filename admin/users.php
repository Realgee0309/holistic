<?php
require_once __DIR__ . '/includes/admin_auth.php';

$pdo = getDB();

// Handle add progress note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $uid    = (int)($_POST['user_id']    ?? 0);
    $bid    = (int)($_POST['booking_id'] ?? 0) ?: null;
    $note   = trim($_POST['note']  ?? '');
    $vis    = !empty($_POST['is_visible']) ? 1 : 0;
    if ($uid && $note) {
        $pdo->prepare("INSERT INTO progress_notes (user_id, booking_id, note, is_visible) VALUES (:uid, :bid, :note, :vis)")
            ->execute([':uid'=>$uid, ':bid'=>$bid, ':note'=>$note, ':vis'=>$vis]);
        header('Location: users.php?view=' . $uid . '&msg=note_added');
    } else {
        header('Location: users.php?view=' . $uid . '&msg=note_error');
    }
    exit;
}

// Handle delete user
if (isset($_GET['delete_user'])) {
    $id = (int)$_GET['delete_user'];
    if ($id) $pdo->prepare("DELETE FROM users WHERE id=:id")->execute([':id'=>$id]);
    header('Location: users.php?msg=user_deleted');
    exit;
}

// ── View single user ──
$viewUser = null;
$userBookings = $userMessages = $userNotes = [];
if (isset($_GET['view'])) {
    $uid = (int)$_GET['view'];
    $viewUser = $pdo->prepare("SELECT * FROM users WHERE id=:id");
    $viewUser->execute([':id'=>$uid]);
    $viewUser = $viewUser->fetch();
    if ($viewUser) {
        $userBookings = $pdo->prepare("SELECT * FROM bookings WHERE user_id=:uid ORDER BY created_at DESC");
        $userBookings->execute([':uid'=>$uid]);
        $userBookings = $userBookings->fetchAll();

        $userMessages = $pdo->prepare("SELECT * FROM contacts WHERE user_id=:uid ORDER BY created_at DESC");
        $userMessages->execute([':uid'=>$uid]);
        $userMessages = $userMessages->fetchAll();

        $userNotes = $pdo->prepare("SELECT pn.*, b.service FROM progress_notes pn LEFT JOIN bookings b ON b.id=pn.booking_id WHERE pn.user_id=:uid ORDER BY pn.created_at DESC");
        $userNotes->execute([':uid'=>$uid]);
        $userNotes = $userNotes->fetchAll();
    }
}

// ── User list ──
$users = $pdo->query("
    SELECT u.*,
        (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) as booking_count,
        (SELECT COUNT(*) FROM contacts c WHERE c.user_id = u.id) as message_count
    FROM users u ORDER BY u.created_at DESC
")->fetchAll();

adminHead('Users', 'Registered client accounts');
?>

<!-- Flash -->
<?php if (isset($_GET['msg'])): ?>
<div class="flash <?= in_array($_GET['msg'], ['user_deleted','note_error']) ? 'error' : 'success' ?>">
    <i class="fas <?= in_array($_GET['msg'], ['user_deleted','note_error']) ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
    <?php
    $msgs = ['note_added'=>'Progress note added.','note_error'=>'Note text is required.','user_deleted'=>'User deleted.'];
    echo $msgs[$_GET['msg']] ?? 'Done.';
    ?>
</div>
<?php endif; ?>

<?php if ($viewUser): ?>
<!-- ════ SINGLE USER VIEW ════ -->
<div style="display:grid;grid-template-columns:1fr 1.4fr;gap:1.5rem;align-items:start;">

    <!-- User Profile Card -->
    <div>
        <div class="panel" style="margin-bottom:1.5rem;">
            <div style="background:linear-gradient(135deg,var(--primary),var(--primary-d));padding:1.8rem;color:white;text-align:center;">
                <div style="width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,0.2);border:2px solid rgba(255,255,255,0.4);display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:700;margin:0 auto 0.8rem;">
                    <?= strtoupper(substr($viewUser['name'], 0, 1)) ?>
                </div>
                <strong style="display:block;font-size:1.05rem;">
                    <?= $viewUser['is_anonymous'] ? '🔒 Anonymous User' : htmlspecialchars($viewUser['name']) ?>
                </strong>
                <span style="opacity:0.8;font-size:0.82rem;"><?= htmlspecialchars($viewUser['email']) ?></span><br>
                <span style="font-size:0.75rem;opacity:0.65;">Joined <?= date('d M Y', strtotime($viewUser['created_at'])) ?></span>
                <?php if ($viewUser['is_anonymous']): ?>
                <div style="background:rgba(255,255,255,0.15);border-radius:50px;padding:0.2rem 0.7rem;font-size:0.75rem;display:inline-block;margin-top:0.5rem;">Anonymous Mode</div>
                <?php endif; ?>
            </div>
            <div style="padding:1.2rem 1.4rem;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;text-align:center;margin-bottom:1rem;">
                    <div style="background:#f9fafb;border-radius:10px;padding:0.8rem;">
                        <div style="font-size:1.5rem;font-weight:700;color:var(--primary);font-family:'Playfair Display',serif"><?= count($userBookings) ?></div>
                        <div style="font-size:0.75rem;color:#888">Bookings</div>
                    </div>
                    <div style="background:#f9fafb;border-radius:10px;padding:0.8rem;">
                        <div style="font-size:1.5rem;font-weight:700;color:var(--primary);font-family:'Playfair Display',serif"><?= count($userNotes) ?></div>
                        <div style="font-size:0.75rem;color:#888">Notes</div>
                    </div>
                </div>
                <a href="users.php" class="btn btn-ghost btn-sm" style="width:100%;justify-content:center;display:flex;">← Back to All Users</a>
            </div>
        </div>

        <!-- Add Progress Note -->
        <div class="panel">
            <div class="panel-head"><div class="panel-head-left"><i class="fas fa-notes-medical panel-icon"></i><h3>Add Progress Note</h3></div></div>
            <div style="padding:1.2rem 1.4rem;">
                <form method="POST">
                    <input type="hidden" name="add_note" value="1">
                    <input type="hidden" name="user_id" value="<?= $viewUser['id'] ?>">
                    <div style="margin-bottom:0.9rem;">
                        <label style="font-size:0.82rem;font-weight:600;color:#374151;display:block;margin-bottom:0.4rem">Linked Booking <span style="font-weight:400;color:#aaa">(optional)</span></label>
                        <select name="booking_id" class="status-select" style="width:100%">
                            <option value="">— General note —</option>
                            <?php foreach ($userBookings as $ub): ?>
                            <option value="<?= $ub['id'] ?>"><?= htmlspecialchars($ub['service']) ?> · <?= date('d M Y', strtotime($ub['preferred_date'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="margin-bottom:0.9rem;">
                        <label style="font-size:0.82rem;font-weight:600;color:#374151;display:block;margin-bottom:0.4rem">Note <span style="color:#ef4444">*</span></label>
                        <textarea name="note" rows="5" required placeholder="Session observations, progress, recommendations..."
                            style="width:100%;border:1.5px solid #e5e7eb;border-radius:10px;padding:0.75rem 1rem;font-family:inherit;font-size:0.85rem;resize:vertical;"></textarea>
                    </div>
                    <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.82rem;color:#374151;margin-bottom:1rem;cursor:pointer;">
                        <input type="checkbox" name="is_visible" value="1" checked style="accent-color:var(--primary)">
                        Visible to client in their dashboard
                    </label>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;display:flex;">
                        <i class="fas fa-plus"></i> Add Note
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right: Tabs -->
    <div>
        <!-- Bookings -->
        <div class="panel" style="margin-bottom:1.5rem;">
            <div class="panel-head"><div class="panel-head-left"><i class="fas fa-calendar-check panel-icon"></i><h3>Bookings</h3></div></div>
            <?php if (empty($userBookings)): ?>
            <div class="empty-state"><i class="fas fa-calendar-xmark"></i><p>No bookings yet.</p></div>
            <?php else: foreach ($userBookings as $b): ?>
            <div style="padding:0.9rem 1.2rem;border-bottom:1px solid #f3f4f6;display:flex;gap:1rem;align-items:center;">
                <div style="flex:1;">
                    <div style="font-size:0.88rem;font-weight:600"><?= htmlspecialchars($b['service']) ?></div>
                    <div style="font-size:0.78rem;color:#9ca3af"><?= date('d M Y', strtotime($b['preferred_date'])) ?> · <?= htmlspecialchars($b['preferred_time']) ?></div>
                </div>
                <span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Progress Notes -->
        <div class="panel">
            <div class="panel-head"><div class="panel-head-left"><i class="fas fa-notes-medical panel-icon"></i><h3>Progress Notes</h3></div></div>
            <?php if (empty($userNotes)): ?>
            <div class="empty-state"><i class="fas fa-notes-medical"></i><p>No notes yet. Add one using the form.</p></div>
            <?php else: foreach ($userNotes as $n): ?>
            <div style="padding:1.1rem 1.2rem;border-bottom:1px solid #f3f4f6;">
                <div style="display:flex;justify-content:space-between;margin-bottom:0.6rem;flex-wrap:wrap;gap:0.4rem;">
                    <?php if ($n['service']): ?><span class="badge badge-confirmed"><?= htmlspecialchars($n['service']) ?></span><?php endif; ?>
                    <span style="font-size:0.72rem;color:#9ca3af"><?= date('d M Y H:i', strtotime($n['created_at'])) ?></span>
                    <span style="font-size:0.72rem;color:<?= $n['is_visible'] ? '#16a34a' : '#9ca3af' ?>">
                        <?= $n['is_visible'] ? '👁 Visible to client' : '🔒 Hidden' ?>
                    </span>
                </div>
                <div style="font-size:0.85rem;color:#374151;background:#f9fafb;padding:0.9rem;border-radius:8px;border-left:3px solid var(--primary)">
                    <?= nl2br(htmlspecialchars($n['note'])) ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ════ USER LIST ════ -->
<div class="panel">
    <div class="panel-head">
        <div class="panel-head-left">
            <i class="fas fa-users panel-icon"></i>
            <h3>Registered Clients</h3>
            <span style="font-size:0.78rem;color:var(--text-muted);margin-left:0.5rem"><?= count($users) ?> total</span>
        </div>
    </div>
    <?php if (empty($users)): ?>
    <div class="empty-state"><i class="fas fa-users"></i><p>No registered users yet.</p></div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="data-table">
        <thead>
            <tr><th>#</th><th>Client</th><th>Mode</th><th>Bookings</th><th>Messages</th><th>Joined</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u):
            $initials = strtoupper(substr($u['name'], 0, 1)) . (strpos($u['name'], ' ') !== false ? strtoupper(substr(strrchr($u['name'],' '),1,1)):'');
        ?>
        <tr>
            <td style="font-size:0.75rem;color:var(--text-muted);font-weight:600">#<?= $u['id'] ?></td>
            <td>
                <div class="client-cell">
                    <div class="client-avatar" style="<?= $u['is_anonymous'] ? 'background:linear-gradient(135deg,#64748b,#374151)' : '' ?>"><?= $initials ?></div>
                    <div>
                        <div class="client-name"><?= $u['is_anonymous'] ? '🔒 Anonymous User' : htmlspecialchars($u['name']) ?></div>
                        <div class="client-email"><?= htmlspecialchars($u['email']) ?></div>
                    </div>
                </div>
            </td>
            <td>
                <?php if ($u['is_anonymous']): ?>
                <span class="badge" style="background:#f1f5f9;color:#475569">🔒 Anonymous</span>
                <?php else: ?>
                <span class="badge" style="background:#d1fae5;color:#065f46">✓ Named</span>
                <?php endif; ?>
            </td>
            <td style="font-size:0.85rem;font-weight:600;text-align:center"><?= $u['booking_count'] ?></td>
            <td style="font-size:0.85rem;font-weight:600;text-align:center"><?= $u['message_count'] ?></td>
            <td style="font-size:0.78rem;color:var(--text-muted)"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td>
                <div style="display:flex;gap:0.35rem">
                    <a href="users.php?view=<?= $u['id'] ?>" class="btn btn-primary btn-sm btn-icon" title="View & add notes"><i class="fas fa-eye"></i></a>
                    <a href="mailto:<?= htmlspecialchars($u['email']) ?>" class="btn btn-ghost btn-sm btn-icon" title="Email client"><i class="fas fa-envelope"></i></a>
                    <a href="users.php?delete_user=<?= $u['id'] ?>"
                       onclick="return confirm('Delete this user and their account? Bookings/messages are kept.')"
                       class="btn btn-danger btn-sm btn-icon" title="Delete user">
                        <i class="fas fa-trash-can"></i>
                    </a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php adminFoot(); ?>
