<?php
require_once __DIR__ . '/includes/admin_auth.php';
adminHead('Dashboard', 'Welcome back, ' . $_SESSION['admin_user']);

$pdo = getDB();

$totalBookings    = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pendingBookings  = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
$confirmedBookings= $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn();
$totalContacts    = $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
$unreadContacts   = $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn();
$totalUsers       = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

$recentBookings = $pdo->query("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 6")->fetchAll();
$recentContacts = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Activity feed (merge bookings + contacts, last 8)
$activity = $pdo->query("
    SELECT 'booking' as type, name, service as detail, created_at FROM bookings
    UNION ALL
    SELECT 'contact' as type, name, subject as detail, created_at FROM contacts
    ORDER BY created_at DESC LIMIT 8
")->fetchAll();
?>

<!-- ── Stat Cards ── -->
<div class="stats-row">
    <div class="stat-card teal">
        <div class="stat-icon teal"><i class="fas fa-calendar-days"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $totalBookings ?></div>
            <div class="stat-label">Total Bookings</div>
            <div class="stat-sub"><?= $confirmedBookings ?> confirmed · <?= $pendingBookings ?> pending</div>
        </div>
    </div>
    <div class="stat-card amber">
        <div class="stat-icon amber"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $pendingBookings ?></div>
            <div class="stat-label">Awaiting Confirmation</div>
            <div class="stat-sub">Needs your attention</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon green"><i class="fas fa-envelope-open-text"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $totalContacts ?></div>
            <div class="stat-label">Messages Received</div>
            <div class="stat-sub"><?= $unreadContacts ?> unread</div>
        </div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon blue"><i class="fas fa-circle-check"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $confirmedBookings ?></div>
            <div class="stat-label">Confirmed Sessions</div>
            <div class="stat-sub">Ready to go</div>
        </div>
    </div>
    <div class="stat-card" style="border-top:3px solid #7c3aed;">
        <div class="stat-icon" style="background:rgba(124,58,237,0.1);color:#7c3aed"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $totalUsers ?></div>
            <div class="stat-label">Registered Clients</div>
            <div class="stat-sub"><a href="users.php" style="color:#7c3aed;text-decoration:none;font-size:0.72rem">View all →</a></div>
        </div>
    </div>
</div>

<!-- ── Two-column layout ── -->
<div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;">

    <!-- Recent Bookings -->
    <div class="panel">
        <div class="panel-head">
            <div class="panel-head-left">
                <i class="fas fa-calendar-check panel-icon"></i>
                <h3>Recent Bookings</h3>
                <?php if ($pendingBookings > 0): ?>
                <span class="badge badge-pending"><?= $pendingBookings ?> pending</span>
                <?php endif; ?>
            </div>
            <a href="bookings.php" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-right"></i> View All</a>
        </div>
        <?php if (empty($recentBookings)): ?>
        <div class="empty-state"><i class="fas fa-calendar-xmark"></i><p>No bookings yet.</p></div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Service</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentBookings as $b):
                $initials = strtoupper(substr($b['name'], 0, 1)) . (strpos($b['name'], ' ') !== false ? strtoupper(substr(strrchr($b['name'], ' '), 1, 1)) : '');
            ?>
            <tr>
                <td>
                    <div class="client-cell">
                        <div class="client-avatar"><?= $initials ?></div>
                        <div>
                            <div class="client-name"><?= htmlspecialchars($b['name']) ?></div>
                            <div class="client-email"><?= htmlspecialchars($b['email']) ?></div>
                        </div>
                    </div>
                </td>
                <td style="font-size:0.82rem;"><?= htmlspecialchars($b['service']) ?></td>
                <td>
                    <div style="font-size:0.82rem;font-weight:500;"><?= date('d M Y', strtotime($b['preferred_date'])) ?></div>
                    <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($b['preferred_time']) ?></div>
                </td>
                <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                <td>
                    <a href="mailto:<?= htmlspecialchars($b['email']) ?>" class="btn btn-ghost btn-icon btn-sm" title="Email client"><i class="fas fa-envelope"></i></a>
                    <a href="https://wa.me/?text=Hi+<?= urlencode($b['name']) ?>" class="btn btn-success btn-icon btn-sm" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Activity Feed -->
    <div class="panel">
        <div class="panel-head">
            <div class="panel-head-left">
                <i class="fas fa-bolt panel-icon"></i>
                <h3>Activity Feed</h3>
            </div>
        </div>
        <?php if (empty($activity)): ?>
        <div class="empty-state"><i class="fas fa-inbox"></i><p>No activity yet.</p></div>
        <?php else: ?>
        <?php foreach ($activity as $item):
            $isBooking = $item['type'] === 'booking';
            $iconClass = $isBooking ? 'fas fa-calendar-plus' : 'fas fa-envelope';
            $iconBg    = $isBooking ? 'rgba(90,125,124,0.12)' : 'rgba(59,130,246,0.12)';
            $iconColor = $isBooking ? 'var(--primary)' : '#3b82f6';
        ?>
        <div class="activity-item">
            <div class="activity-dot" style="background:<?= $iconBg ?>;color:<?= $iconColor ?>">
                <i class="<?= $iconClass ?>"></i>
            </div>
            <div class="activity-text">
                <strong><?= htmlspecialchars($item['name']) ?></strong>
                <p><?= $isBooking ? 'booked' : 'sent message' ?> · <?= htmlspecialchars($item['detail']) ?></p>
            </div>
            <div class="activity-time"><?= date('H:i', strtotime($item['created_at'])) ?></div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Messages -->
<div class="panel">
    <div class="panel-head">
        <div class="panel-head-left">
            <i class="fas fa-envelope panel-icon"></i>
            <h3>Recent Messages</h3>
            <?php if ($unreadContacts > 0): ?>
            <span class="badge badge-unread"><?= $unreadContacts ?> unread</span>
            <?php endif; ?>
        </div>
        <a href="contacts.php" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-right"></i> View All</a>
    </div>
    <?php if (empty($recentContacts)): ?>
    <div class="empty-state"><i class="fas fa-inbox"></i><p>No messages yet.</p></div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="data-table">
        <thead>
            <tr><th>Sender</th><th>Subject</th><th>Preview</th><th>Status</th><th>Received</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($recentContacts as $c):
            $initials = strtoupper(substr($c['name'], 0, 1)) . (strpos($c['name'], ' ') !== false ? strtoupper(substr(strrchr($c['name'], ' '), 1, 1)) : '');
        ?>
        <tr style="<?= !$c['is_read'] ? 'background:#fafeff;' : '' ?>">
            <td>
                <div class="client-cell">
                    <div class="client-avatar" style="<?= $c['is_read'] ? 'opacity:0.6' : '' ?>"><?= $initials ?></div>
                    <div>
                        <div class="client-name" style="<?= !$c['is_read'] ? 'font-weight:700' : '' ?>"><?= htmlspecialchars($c['name']) ?></div>
                        <div class="client-email"><?= htmlspecialchars($c['email']) ?></div>
                    </div>
                </div>
            </td>
            <td style="font-size:0.82rem;font-weight:<?= !$c['is_read'] ? '600' : '400' ?>"><?= htmlspecialchars($c['subject']) ?></td>
            <td style="max-width:200px;font-size:0.8rem;color:var(--text-muted)">
                <?= htmlspecialchars(mb_substr($c['message'], 0, 60)) ?>…
            </td>
            <td><span class="badge <?= $c['is_read'] ? 'badge-read' : 'badge-unread' ?>"><?= $c['is_read'] ? 'Read' : 'Unread' ?></span></td>
            <td style="font-size:0.78rem;color:var(--text-muted);white-space:nowrap"><?= date('d M, H:i', strtotime($c['created_at'])) ?></td>
            <td>
                <a href="mailto:<?= htmlspecialchars($c['email']) ?>" class="btn btn-ghost btn-icon btn-sm" title="Reply"><i class="fas fa-reply"></i></a>
                <?php if (!$c['is_read']): ?>
                <a href="contacts.php?read=<?= $c['id'] ?>" class="btn btn-success btn-icon btn-sm" title="Mark read"><i class="fas fa-check"></i></a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php adminFoot(); ?>
