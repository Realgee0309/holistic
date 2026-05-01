<?php
require_once __DIR__ . '/includes/admin_auth.php';

$pdo = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id     = (int)($_POST['booking_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($id && in_array($status, ['pending','confirmed','cancelled'])) {
        $pdo->prepare("UPDATE bookings SET status=:s WHERE id=:id")->execute([':s'=>$status,':id'=>$id]);
    }
    header('Location: bookings.php?msg=updated');
    exit;
}
// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id) $pdo->prepare("DELETE FROM bookings WHERE id=:id")->execute([':id'=>$id]);
    header('Location: bookings.php?msg=deleted');
    exit;
}

// Counts per status for filter tabs
$counts = ['all'=>0,'pending'=>0,'confirmed'=>0,'cancelled'=>0];
foreach ($pdo->query("SELECT status, COUNT(*) as c FROM bookings GROUP BY status")->fetchAll() as $r) {
    $counts[$r['status']] = (int)$r['c'];
    $counts['all'] += (int)$r['c'];
}

// Filter
$statusFilter = $_GET['status'] ?? '';
$where  = $statusFilter ? "WHERE status = :s" : '';
$params = $statusFilter ? [':s' => $statusFilter] : [];

// Pagination
$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$total   = $pdo->prepare("SELECT COUNT(*) FROM bookings $where");
$total->execute($params);
$total   = (int)$total->fetchColumn();
$pages   = max(1, ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT * FROM bookings $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

adminHead('Bookings', 'Manage all session booking requests');
?>

<!-- Flash -->
<?php if (isset($_GET['msg'])): ?>
<div class="flash <?= $_GET['msg']==='deleted' ? 'error' : 'success' ?>">
    <i class="fas <?= $_GET['msg']==='deleted' ? 'fa-trash' : 'fa-check-circle' ?>"></i>
    <?= $_GET['msg']==='deleted' ? 'Booking deleted successfully.' : 'Booking status updated.' ?>
</div>
<?php endif; ?>

<!-- Filter Tabs -->
<div class="filter-tabs">
    <a href="bookings.php" class="filter-tab <?= !$statusFilter ? 'active' : '' ?>">
        All <span style="margin-left:3px;opacity:0.7">(<?= $counts['all'] ?>)</span>
    </a>
    <a href="bookings.php?status=pending" class="filter-tab <?= $statusFilter==='pending' ? 'active' : '' ?>"
       style="<?= $statusFilter==='pending' ? '' : ($counts['pending']>0 ? 'border-color:#fcd34d;color:#92400e;background:#fef3c7' : '') ?>">
        <i class="fas fa-clock" style="font-size:0.7rem"></i> Pending (<?= $counts['pending'] ?>)
    </a>
    <a href="bookings.php?status=confirmed" class="filter-tab <?= $statusFilter==='confirmed' ? 'active' : '' ?>">
        <i class="fas fa-check" style="font-size:0.7rem"></i> Confirmed (<?= $counts['confirmed'] ?>)
    </a>
    <a href="bookings.php?status=cancelled" class="filter-tab <?= $statusFilter==='cancelled' ? 'active' : '' ?>">
        <i class="fas fa-xmark" style="font-size:0.7rem"></i> Cancelled (<?= $counts['cancelled'] ?>)
    </a>
    <div style="margin-left:auto;font-size:0.78rem;color:var(--text-muted);align-self:center">
        Showing <?= count($bookings) ?> of <?= $total ?>
    </div>
</div>

<!-- Table Panel -->
<div class="panel">
    <div class="panel-head">
        <div class="panel-head-left">
            <i class="fas fa-calendar-check panel-icon"></i>
            <h3>Booking Requests</h3>
        </div>
    </div>

    <?php if (empty($bookings)): ?>
    <div class="empty-state">
        <i class="fas fa-calendar-xmark"></i>
        <p>No bookings found<?= $statusFilter ? ' with status "' . $statusFilter . '"' : '' ?>.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>Service</th>
                <th>Session Date</th>
                <th>Time Slot</th>
                <th>Note</th>
                <th>Status</th>
                <th>Received</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($bookings as $b):
            $initials = strtoupper(substr($b['name'], 0, 1)) . (strpos($b['name'], ' ') !== false ? strtoupper(substr(strrchr($b['name'], ' '), 1, 1)) : '');
        ?>
        <tr>
            <td style="font-size:0.75rem;color:var(--text-muted);font-weight:600">#<?= $b['id'] ?></td>
            <td>
                <div class="client-cell">
                    <div class="client-avatar"><?= $initials ?></div>
                    <div>
                        <div class="client-name"><?= htmlspecialchars($b['name']) ?></div>
                        <div class="client-email"><?= htmlspecialchars($b['email']) ?></div>
                    </div>
                </div>
            </td>
            <td>
                <span style="display:inline-block;background:#f3f4f6;color:var(--dark);font-size:0.75rem;font-weight:500;padding:0.2rem 0.6rem;border-radius:6px;">
                    <?= htmlspecialchars($b['service']) ?>
                </span>
            </td>
            <td>
                <div style="font-size:0.85rem;font-weight:600;"><?= date('D, d M Y', strtotime($b['preferred_date'])) ?></div>
            </td>
            <td style="font-size:0.82rem;color:var(--text-muted)"><?= htmlspecialchars($b['preferred_time']) ?></td>
            <td style="max-width:160px">
                <?php if ($b['message']): ?>
                <span style="font-size:0.78rem;color:var(--text-muted)" title="<?= htmlspecialchars($b['message']) ?>">
                    <?= htmlspecialchars(mb_substr($b['message'], 0, 50)) ?><?= mb_strlen($b['message']) > 50 ? '…' : '' ?>
                </span>
                <?php else: ?>
                <span style="font-size:0.75rem;color:#d1d5db;font-style:italic">—</span>
                <?php endif; ?>
            </td>
            <td>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <input type="hidden" name="update_status" value="1">
                    <select name="status" class="status-select">
                        <option value="pending"   <?= $b['status']==='pending'   ? 'selected':'' ?>>⏳ Pending</option>
                        <option value="confirmed" <?= $b['status']==='confirmed' ? 'selected':'' ?>>✅ Confirmed</option>
                        <option value="cancelled" <?= $b['status']==='cancelled' ? 'selected':'' ?>>❌ Cancelled</option>
                    </select>
                </form>
            </td>
            <td style="font-size:0.75rem;color:var(--text-muted);white-space:nowrap">
                <?= date('d M Y', strtotime($b['created_at'])) ?><br>
                <span style="font-size:0.7rem"><?= date('H:i', strtotime($b['created_at'])) ?></span>
            </td>
            <td>
                <div style="display:flex;gap:0.3rem">
                    <a href="mailto:<?= htmlspecialchars($b['email']) ?>"
                       class="btn btn-ghost btn-icon btn-sm" title="Email client">
                        <i class="fas fa-envelope"></i>
                    </a>
                    <a href="https://wa.me/254797582384?text=<?= urlencode('Hi '.$b['name'].', your booking for '.$b['service'].' on '.date('d M Y', strtotime($b['preferred_date'])).' has been confirmed.') ?>"
                       class="btn btn-success btn-icon btn-sm" title="WhatsApp client" target="_blank">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="bookings.php?delete=<?= $b['id'] ?>"
                       onclick="return confirm('Delete booking for <?= htmlspecialchars(addslashes($b['name'])) ?>?')"
                       class="btn btn-danger btn-icon btn-sm" title="Delete">
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

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?page=<?= $page-1 ?><?= $statusFilter ? '&status='.$statusFilter : '' ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
    <?php endif; ?>
    <?php for ($p = 1; $p <= $pages; $p++): ?>
    <a href="?page=<?= $p ?><?= $statusFilter ? '&status='.$statusFilter : '' ?>" class="page-btn <?= $p===$page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
    <?php if ($page < $pages): ?>
    <a href="?page=<?= $page+1 ?><?= $statusFilter ? '&status='.$statusFilter : '' ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php adminFoot(); ?>
