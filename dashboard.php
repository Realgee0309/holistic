<?php
$pageTitle = 'My Dashboard';
$metaDesc  = 'View your Holistic Wellness session history, messages, and progress notes.';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/user_auth.php';
requireLogin();
$user = getCurrentUser();
$pdo  = getDB();

$activeTab = $_GET['tab'] ?? 'overview';

// Fetch data
$bookings = $pdo->prepare("SELECT * FROM bookings WHERE user_id = :uid ORDER BY created_at DESC");
$bookings->execute([':uid' => $user['id']]);
$bookings = $bookings->fetchAll();

$messages = $pdo->prepare("SELECT * FROM contacts WHERE user_id = :uid ORDER BY created_at DESC");
$messages->execute([':uid' => $user['id']]);
$messages = $messages->fetchAll();

$notes = $pdo->prepare("SELECT pn.*, b.service, b.preferred_date FROM progress_notes pn LEFT JOIN bookings b ON b.id = pn.booking_id WHERE pn.user_id = :uid AND pn.is_visible = 1 ORDER BY pn.created_at DESC");
$notes->execute([':uid' => $user['id']]);
$notes = $notes->fetchAll();

// Fetch admin replies for this user's messages
$adminReplies = [];
if (!empty($messages)) {
    $cids = array_column($messages, 'id');
    $placeholders = implode(',', array_fill(0, count($cids), '?'));
    $repStmt = $pdo->prepare("SELECT * FROM admin_replies WHERE contact_id IN ($placeholders) ORDER BY created_at ASC");
    $repStmt->execute($cids);
    foreach ($repStmt->fetchAll() as $r) {
        $adminReplies[$r['contact_id']][] = $r;
    }
}

$totalSessions   = count($bookings);
$confirmedSessions = count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed'));
$pendingSessions = count(array_filter($bookings, fn($b) => $b['status'] === 'pending'));
$totalNotes      = count($notes);

require_once __DIR__ . '/includes/header.php';
?>
<style>
.dashboard-hero { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-d) 100%); padding: 3rem 0 5rem; color: white; }
.dashboard-hero h1 { font-family: 'Playfair Display', serif; font-size: 2rem; margin-bottom: 0.4rem; }
.dashboard-hero p { opacity: 0.85; font-size: 0.95rem; }
.dashboard-body { margin-top: -3rem; padding-bottom: 4rem; }
.dash-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px,1fr)); gap: 1rem; margin-bottom: 2rem; }
.dash-stat { background: white; border-radius: 14px; padding: 1.3rem; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
.dash-stat-num { font-size: 2rem; font-weight: 700; font-family: 'Playfair Display', serif; color: var(--primary); line-height: 1; }
.dash-stat-label { font-size: 0.78rem; color: #888; margin-top: 0.4rem; font-weight: 500; }
.dash-tabs { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.dash-tab { padding: 0.6rem 1.2rem; border-radius: 50px; font-size: 0.85rem; font-weight: 600; cursor: pointer; text-decoration: none; border: 1.5px solid #e5e7eb; color: #6b7280; background: white; transition: all 0.25s; display: flex; align-items: center; gap: 0.4rem; }
.dash-tab:hover { border-color: var(--primary); color: var(--primary); }
.dash-tab.active { background: var(--primary); color: white; border-color: var(--primary); }
.dash-tab .tab-count { background: rgba(255,255,255,0.25); font-size: 0.72rem; padding: 0.1rem 0.45rem; border-radius: 50px; }
.dash-tab:not(.active) .tab-count { background: #f3f4f6; color: #888; }
.panel { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); overflow: hidden; }
.panel-head { padding: 1.2rem 1.5rem; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: space-between; }
.panel-head h2 { font-size: 1.05rem; color: #1f2937; font-weight: 600; }
.booking-list { }
.booking-item { display: flex; gap: 1.2rem; align-items: flex-start; padding: 1.3rem 1.5rem; border-bottom: 1px solid #f9fafb; transition: background 0.2s; }
.booking-item:last-child { border-bottom: none; }
.booking-item:hover { background: #fafbfc; }
.booking-date-box { background: var(--secondary); border-radius: 10px; padding: 0.6rem 0.8rem; text-align: center; flex-shrink: 0; min-width: 56px; }
.booking-date-box .day { font-size: 1.3rem; font-weight: 700; font-family: 'Playfair Display', serif; color: var(--primary); line-height: 1; }
.booking-date-box .month { font-size: 0.68rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
.booking-info { flex: 1; min-width: 0; }
.booking-info h3 { font-size: 0.95rem; font-weight: 600; color: #1f2937; margin-bottom: 0.3rem; }
.booking-info p { font-size: 0.8rem; color: #9ca3af; }
.badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.25rem 0.7rem; border-radius: 50px; font-size: 0.72rem; font-weight: 600; }
.badge::before { content:''; width:6px; height:6px; border-radius:50%; display:inline-block; }
.badge-pending   { background:#fef3c7; color:#92400e; } .badge-pending::before   { background:#f59e0b; }
.badge-confirmed { background:#d1fae5; color:#065f46; } .badge-confirmed::before { background:#10b981; }
.badge-cancelled { background:#fee2e2; color:#991b1b; } .badge-cancelled::before { background:#ef4444; }
.note-card { padding: 1.4rem 1.5rem; border-bottom: 1px solid #f3f4f6; }
.note-card:last-child { border-bottom: none; }
.note-meta { display: flex; align-items: center; gap: 0.7rem; margin-bottom: 0.8rem; flex-wrap: wrap; }
.note-therapist { display: flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; font-weight: 600; color: var(--primary); }
.note-therapist-avatar { width: 26px; height: 26px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; font-size: 0.65rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.note-text { font-size: 0.9rem; color: #374151; line-height: 1.75; background: #f9fafb; padding: 1rem 1.2rem; border-radius: 10px; border-left: 3px solid var(--primary); }
.note-date { font-size: 0.75rem; color: #aaa; }
.msg-item { padding: 1.1rem 1.5rem; border-bottom: 1px solid #f9fafb; }
.msg-item:last-child { border-bottom: none; }
.msg-subject { font-weight: 600; font-size: 0.9rem; color: #1f2937; margin-bottom: 0.25rem; }
.msg-preview { font-size: 0.8rem; color: #9ca3af; }
.msg-meta { font-size: 0.75rem; color: #aaa; margin-top: 0.4rem; }
.anon-info-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 1.2rem 1.4rem; margin-bottom: 1.5rem; display: flex; gap: 0.8rem; }
.anon-info-box i { color: #3b82f6; margin-top: 2px; flex-shrink: 0; }
.anon-info-box p { font-size: 0.85rem; color: #1e40af; line-height: 1.6; }
.empty-state { text-align: center; padding: 3.5rem 2rem; color: #aaa; }
.empty-state .ei { font-size: 3rem; margin-bottom: 1rem; }
.empty-state p { font-size: 0.9rem; }
.empty-state a { color: var(--primary); font-weight: 600; text-decoration: none; }
</style>

<!-- Hero -->
<div class="dashboard-hero">
    <div class="container">
        <?php renderFlash(); ?>
        <h1>
            <?= $user['is_anonymous'] ? '🔒 Your Private Dashboard' : ('Welcome back, ' . htmlspecialchars(explode(' ', $user['name'])[0]) . ' 👋') ?>
        </h1>
        <p>Track your therapy journey, review your sessions, and read your progress notes.</p>
    </div>
</div>

<div class="dashboard-body">
    <div class="container">
        <!-- Anonymity notice -->
        <?php if ($user['is_anonymous']): ?>
        <div class="anon-info-box">
            <i class="fas fa-shield-halved fa-lg"></i>
            <p><strong>You're in anonymous mode.</strong> Your real name is hidden from our team. You can view your bookings and progress notes privately here.</p>
        </div>
        <?php endif; ?>

        <!-- Stat cards -->
        <div class="dash-stats">
            <div class="dash-stat">
                <div class="dash-stat-num"><?= $totalSessions ?></div>
                <div class="dash-stat-label">Total Sessions</div>
            </div>
            <div class="dash-stat">
                <div class="dash-stat-num"><?= $confirmedSessions ?></div>
                <div class="dash-stat-label">Confirmed</div>
            </div>
            <div class="dash-stat">
                <div class="dash-stat-num"><?= $pendingSessions ?></div>
                <div class="dash-stat-label">Pending</div>
            </div>
            <div class="dash-stat">
                <div class="dash-stat-num"><?= $totalNotes ?></div>
                <div class="dash-stat-label">Progress Notes</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="dash-tabs">
            <a href="?tab=overview"  class="dash-tab <?= $activeTab==='overview'  ? 'active':'' ?>"><i class="fas fa-gauge"></i> Overview</a>
            <a href="?tab=bookings"  class="dash-tab <?= $activeTab==='bookings'  ? 'active':'' ?>"><i class="fas fa-calendar-check"></i> My Bookings <span class="tab-count"><?= $totalSessions ?></span></a>
            <a href="?tab=notes"     class="dash-tab <?= $activeTab==='notes'     ? 'active':'' ?>"><i class="fas fa-notes-medical"></i> Progress Notes <span class="tab-count"><?= $totalNotes ?></span></a>
            <a href="?tab=messages"  class="dash-tab <?= $activeTab==='messages'  ? 'active':'' ?>"><i class="fas fa-envelope"></i> Messages <span class="tab-count"><?= count($messages) ?></span></a>
        </div>

        <!-- ── OVERVIEW TAB ── -->
        <?php if ($activeTab === 'overview'): ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
            <div class="panel">
                <div class="panel-head"><h2>📅 Recent Bookings</h2><a href="?tab=bookings" style="font-size:0.8rem;color:var(--primary);text-decoration:none">View all →</a></div>
                <?php $recent = array_slice($bookings, 0, 3); ?>
                <?php if (empty($recent)): ?>
                <div class="empty-state"><div class="ei">📅</div><p>No bookings yet. <a href="book.php">Book your first session</a></p></div>
                <?php else: ?>
                <div class="booking-list">
                    <?php foreach ($recent as $b): ?>
                    <div class="booking-item">
                        <div class="booking-date-box">
                            <div class="day"><?= date('d', strtotime($b['preferred_date'])) ?></div>
                            <div class="month"><?= date('M', strtotime($b['preferred_date'])) ?></div>
                        </div>
                        <div class="booking-info">
                            <h3><?= htmlspecialchars($b['service']) ?></h3>
                            <p><?= htmlspecialchars($b['preferred_time']) ?></p>
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.4rem">
                            <span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span>
                            <?php if ($b['status'] === 'pending'): ?>
                            <form method="POST" action="actions/cancel-booking.php"
                                  onsubmit="return confirm('Cancel your booking for &quot;<?= htmlspecialchars(addslashes($b['service'])) ?>&quot;?')">
                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                <button type="submit" style="background:none;border:none;font-size:0.72rem;color:#dc2626;cursor:pointer;padding:0;font-weight:600">Cancel</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="panel">
                <div class="panel-head"><h2>📝 Latest Note</h2><a href="?tab=notes" style="font-size:0.8rem;color:var(--primary);text-decoration:none">View all →</a></div>
                <?php if (empty($notes)): ?>
                <div class="empty-state"><div class="ei">📝</div><p>No progress notes yet. Notes appear after your sessions.</p></div>
                <?php else: $n = $notes[0]; ?>
                <div class="note-card">
                    <div class="note-meta">
                        <div class="note-therapist"><div class="note-therapist-avatar">Dr</div> Dr. Jerald</div>
                        <?php if ($n['service']): ?><span class="badge badge-confirmed"><?= htmlspecialchars($n['service']) ?></span><?php endif; ?>
                        <span class="note-date"><?= date('d M Y', strtotime($n['created_at'])) ?></span>
                    </div>
                    <div class="note-text"><?= htmlspecialchars($n['note']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($bookings)): ?>
        <div style="margin-top:1.5rem;text-align:center;">
            <a href="book.php" class="btn btn-whatsapp"><i class="fab fa-whatsapp"></i> Book Another Session</a>
        </div>
        <?php endif; ?>

        <!-- ── BOOKINGS TAB ── -->
        <?php elseif ($activeTab === 'bookings'): ?>
        <div class="panel">
            <div class="panel-head"><h2>📅 All Bookings</h2><a href="book.php" class="btn btn-sm btn-primary">+ New Booking</a></div>
            <?php if (empty($bookings)): ?>
            <div class="empty-state"><div class="ei">📅</div><p>No bookings yet. <a href="book.php">Book your first session today</a></p></div>
            <?php else: ?>
            <div class="booking-list">
                <?php foreach ($bookings as $b): ?>
                <div class="booking-item">
                    <div class="booking-date-box">
                        <div class="day"><?= date('d', strtotime($b['preferred_date'])) ?></div>
                        <div class="month"><?= date('M Y', strtotime($b['preferred_date'])) ?></div>
                    </div>
                    <div class="booking-info">
                        <h3><?= htmlspecialchars($b['service']) ?></h3>
                        <p><?= htmlspecialchars($b['preferred_time']) ?> · Booked <?= date('d M Y', strtotime($b['created_at'])) ?></p>
                        <?php if ($b['message']): ?><p style="margin-top:0.3rem;font-style:italic;">"<?= htmlspecialchars(mb_substr($b['message'],0,80)) ?>"</p><?php endif; ?>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.5rem">
                        <span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span>
                        <?php if ($b['status'] === 'pending'): ?>
                        <form method="POST" action="actions/cancel-booking.php"
                              onsubmit="return confirm('Cancel your booking for &quot;<?= htmlspecialchars(addslashes($b['service'])) ?>&quot;? This cannot be undone.')">
                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                            <button type="submit" style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;font-size:0.75rem;font-weight:600;border-radius:6px;padding:0.28rem 0.65rem;cursor:pointer;font-family:inherit">
                                <i class="fas fa-xmark"></i> Cancel
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── NOTES TAB ── -->
        <?php elseif ($activeTab === 'notes'): ?>
        <div class="panel">
            <div class="panel-head"><h2>📝 Progress Notes from Your Therapist</h2></div>
            <?php if (empty($notes)): ?>
            <div class="empty-state"><div class="ei">📝</div><p>No progress notes yet. These appear after your sessions when your therapist adds them.</p></div>
            <?php else: ?>
            <?php foreach ($notes as $n): ?>
            <div class="note-card">
                <div class="note-meta">
                    <div class="note-therapist"><div class="note-therapist-avatar">Dr</div> Dr. Jerald</div>
                    <?php if ($n['service']): ?><span class="badge badge-confirmed"><?= htmlspecialchars($n['service']) ?></span><?php endif; ?>
                    <?php if ($n['preferred_date']): ?><span class="note-date">Session: <?= date('d M Y', strtotime($n['preferred_date'])) ?></span><?php endif; ?>
                    <span class="note-date">Note added: <?= date('d M Y', strtotime($n['created_at'])) ?></span>
                </div>
                <div class="note-text"><?= nl2br(htmlspecialchars($n['note'])) ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ── MESSAGES TAB ── -->
        <?php elseif ($activeTab === 'messages'): ?>
        <div class="panel">
            <div class="panel-head"><h2>✉️ Messages You've Sent</h2><a href="contact.php" class="btn btn-sm btn-primary">+ New Message</a></div>
            <?php if (empty($messages)): ?>
            <div class="empty-state"><div class="ei">✉️</div><p>No messages yet. <a href="contact.php">Send us a message</a></p></div>
            <?php else: ?>
            <?php foreach ($messages as $m): ?>
            <div class="msg-item">
                <div class="msg-subject"><?= htmlspecialchars($m['subject']) ?></div>
                <div class="msg-preview"><?= htmlspecialchars(mb_substr($m['message'], 0, 100)) ?>…</div>
                <div class="msg-meta"><?= date('d M Y H:i', strtotime($m['created_at'])) ?> · <?= $m['is_read'] ? '<span style="color:#16a34a">✓ Read by team</span>' : '<span style="color:#f59e0b">Awaiting response</span>' ?></div>
                <?php if (!empty($adminReplies[$m['id']])): ?>
                <div style="margin-top:0.9rem;padding-top:0.8rem;border-top:1px dashed #e5e7eb">
                    <div style="font-size:0.72rem;font-weight:600;color:var(--primary);margin-bottom:0.5rem">💬 Replies from your therapist</div>
                    <?php foreach ($adminReplies[$m['id']] as $r): ?>
                    <div style="background:linear-gradient(135deg,var(--primary),var(--primary-d));color:white;border-radius:12px 12px 12px 3px;padding:0.75rem 1rem;font-size:0.83rem;line-height:1.6;margin-bottom:0.5rem">
                        <?= nl2br(htmlspecialchars($r['reply'])) ?>
                    </div>
                    <div style="font-size:0.7rem;color:#aaa;margin-bottom:0.6rem"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
