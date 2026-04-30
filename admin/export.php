<?php
/**
 * Admin — Export Data to CSV
 * Supports: bookings, contacts, users
 */
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/security.php';

$pdo  = getDB();
$type = $_GET['type'] ?? 'bookings';
$allowed = ['bookings', 'contacts', 'users'];
if (!in_array($type, $allowed)) $type = 'bookings';

// Optional date range filter
$from = cleanDate($_GET['from'] ?? '') ?: null;
$to   = cleanDate($_GET['to']   ?? '') ?: null;

// ── If export requested ──────────────────────────────────────
if (isset($_GET['download'])) {
    $filename = 'holistic_' . $type . '_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // UTF-8 BOM for Excel
    fwrite($out, "\xEF\xBB\xBF");

    switch ($type) {
        case 'bookings':
            fputcsv($out, ['ID','Name','Email','Service','Preferred Date','Preferred Time','Status','Message','Booked At']);
            $where = '';
            $params = [];
            if ($from) { $where .= " AND preferred_date >= :from"; $params[':from'] = $from; }
            if ($to)   { $where .= " AND preferred_date <= :to";   $params[':to']   = $to;   }
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE 1=1 $where ORDER BY created_at DESC");
            $stmt->execute($params);
            while ($row = $stmt->fetch()) {
                fputcsv($out, [
                    $row['id'], $row['name'], $row['email'], $row['service'],
                    $row['preferred_date'], $row['preferred_time'], $row['status'],
                    $row['message'], $row['created_at']
                ]);
            }
            break;

        case 'contacts':
            fputcsv($out, ['ID','Name','Email','Subject','Message','Read','Received At']);
            $where = '';
            $params = [];
            if ($from) { $where .= " AND DATE(created_at) >= :from"; $params[':from'] = $from; }
            if ($to)   { $where .= " AND DATE(created_at) <= :to";   $params[':to']   = $to;   }
            $stmt = $pdo->prepare("SELECT * FROM contacts WHERE 1=1 $where ORDER BY created_at DESC");
            $stmt->execute($params);
            while ($row = $stmt->fetch()) {
                fputcsv($out, [
                    $row['id'], $row['name'], $row['email'], $row['subject'],
                    $row['message'], $row['is_read'] ? 'Yes' : 'No', $row['created_at']
                ]);
            }
            break;

        case 'users':
            fputcsv($out, ['ID','Name','Email','Anonymous','Registered At']);
            $stmt = $pdo->query("SELECT id,name,email,is_anonymous,created_at FROM users ORDER BY created_at DESC");
            while ($row = $stmt->fetch()) {
                fputcsv($out, [
                    $row['id'], $row['name'], $row['email'],
                    $row['is_anonymous'] ? 'Yes' : 'No', $row['created_at']
                ]);
            }
            break;
    }
    fclose($out);
    exit;
}

// ── Render UI ────────────────────────────────────────────────
// Preview counts
$counts = [
    'bookings' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'contacts' => $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn(),
    'users'    => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
];

adminHead('Export Data', 'Download records as CSV');
?>
<style>
.export-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem;margin-bottom:2rem}
.export-card{background:white;border:2px solid var(--border);border-radius:var(--radius);padding:1.8rem;cursor:pointer;transition:var(--transition);text-decoration:none;color:inherit;display:block}
.export-card:hover,.export-card.active{border-color:var(--primary);box-shadow:0 0 0 3px rgba(90,125,124,0.12)}
.export-card.active{background:#f0f9f8}
.export-icon{font-size:2.2rem;margin-bottom:.8rem}
.export-card h3{font-size:1rem;color:var(--dark);margin-bottom:.3rem}
.export-card p{font-size:.82rem;color:var(--text-muted)}
.export-count{display:inline-block;background:var(--primary);color:white;font-size:.72rem;font-weight:700;padding:.15rem .6rem;border-radius:50px;margin-top:.5rem}
.filter-panel{background:white;border-radius:var(--radius);border:1px solid var(--border);padding:1.5rem;margin-bottom:1.5rem}
.filter-row{display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap}
.filter-field label{font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:.35rem}
.filter-field input{padding:.6rem .9rem;border:1.5px solid #e5e7eb;border-radius:8px;font-size:.88rem;font-family:inherit}
.filter-field input:focus{outline:none;border-color:var(--primary)}
</style>

<!-- Type Selector -->
<div class="export-grid">
  <?php foreach (['bookings'=>['icon'=>'📅','title'=>'Bookings','desc'=>'All session booking requests'],
                  'contacts'=>['icon'=>'✉️','title'=>'Messages','desc'=>'Contact form submissions'],
                  'users'   =>['icon'=>'👥','title'=>'Clients','desc'=>'Registered user accounts']] as $k => $info): ?>
  <a href="export.php?type=<?= $k ?>" class="export-card <?= $type===$k?'active':'' ?>">
    <div class="export-icon"><?= $info['icon'] ?></div>
    <h3><?= $info['title'] ?></h3>
    <p><?= $info['desc'] ?></p>
    <span class="export-count"><?= $counts[$k] ?> records</span>
  </a>
  <?php endforeach; ?>
</div>

<!-- Filters + Download -->
<div class="filter-panel">
  <h3 style="font-size:.95rem;color:var(--dark);margin-bottom:1.2rem;font-weight:600">
    <i class="fas fa-file-csv" style="color:var(--primary);margin-right:.4rem"></i>
    Export: <strong><?= ucfirst($type) ?></strong>
  </h3>
  <form method="GET">
    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
    <div class="filter-row">
      <?php if ($type === 'bookings'): ?>
      <div class="filter-field">
        <label>From Date (session date)</label>
        <input type="date" name="from" value="<?= htmlspecialchars($from ?? '') ?>">
      </div>
      <div class="filter-field">
        <label>To Date (session date)</label>
        <input type="date" name="to" value="<?= htmlspecialchars($to ?? '') ?>">
      </div>
      <?php elseif ($type === 'contacts'): ?>
      <div class="filter-field">
        <label>From Date (received)</label>
        <input type="date" name="from" value="<?= htmlspecialchars($from ?? '') ?>">
      </div>
      <div class="filter-field">
        <label>To Date (received)</label>
        <input type="date" name="to" value="<?= htmlspecialchars($to ?? '') ?>">
      </div>
      <?php else: ?>
      <p style="font-size:.85rem;color:var(--text-muted);align-self:center">No date filter for user export.</p>
      <?php endif; ?>
      <div class="filter-field" style="margin-left:auto">
        <label>&nbsp;</label>
        <a href="export.php?type=<?= urlencode($type) ?><?= $from ? '&from='.urlencode($from) : '' ?><?= $to ? '&to='.urlencode($to) : '' ?>&download=1"
           class="btn btn-primary" style="display:inline-flex;align-items:center;gap:.5rem">
          <i class="fas fa-download"></i> Download CSV
        </a>
      </div>
    </div>
  </form>
</div>

<!-- Preview table -->
<div class="panel">
  <div class="panel-head">
    <div class="panel-head-left"><i class="fas fa-table panel-icon"></i><h3>Data Preview (first 20 rows)</h3></div>
  </div>
  <div style="overflow-x:auto;padding:0">
  <table class="data-table">
    <?php if ($type === 'bookings'): ?>
    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Service</th><th>Date</th><th>Time</th><th>Status</th><th>Booked At</th></tr></thead>
    <tbody>
    <?php foreach ($pdo->query("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 20")->fetchAll() as $r): ?>
    <tr>
      <td style="font-size:.75rem;color:var(--text-muted)">#<?= $r['id'] ?></td>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td style="font-size:.78rem;color:var(--text-muted)"><?= htmlspecialchars($r['email']) ?></td>
      <td style="font-size:.82rem"><?= htmlspecialchars($r['service']) ?></td>
      <td style="font-size:.82rem"><?= date('d M Y', strtotime($r['preferred_date'])) ?></td>
      <td style="font-size:.78rem;color:var(--text-muted)"><?= htmlspecialchars($r['preferred_time']) ?></td>
      <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
      <td style="font-size:.75rem;color:var(--text-muted)"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php elseif ($type === 'contacts'): ?>
    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Subject</th><th>Preview</th><th>Status</th><th>Received</th></tr></thead>
    <tbody>
    <?php foreach ($pdo->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 20")->fetchAll() as $r): ?>
    <tr>
      <td style="font-size:.75rem;color:var(--text-muted)">#<?= $r['id'] ?></td>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td style="font-size:.78rem;color:var(--text-muted)"><?= htmlspecialchars($r['email']) ?></td>
      <td style="font-size:.82rem"><?= htmlspecialchars($r['subject']) ?></td>
      <td style="font-size:.78rem;color:var(--text-muted);max-width:180px"><?= htmlspecialchars(mb_substr($r['message'],0,60)) ?>…</td>
      <td><span class="badge <?= $r['is_read']?'badge-read':'badge-unread' ?>"><?= $r['is_read']?'Read':'Unread' ?></span></td>
      <td style="font-size:.75rem;color:var(--text-muted)"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php else: ?>
    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Mode</th><th>Joined</th></tr></thead>
    <tbody>
    <?php foreach ($pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 20")->fetchAll() as $r): ?>
    <tr>
      <td style="font-size:.75rem;color:var(--text-muted)">#<?= $r['id'] ?></td>
      <td><?= $r['is_anonymous']?'🔒 Anonymous':htmlspecialchars($r['name']) ?></td>
      <td style="font-size:.78rem;color:var(--text-muted)"><?= htmlspecialchars($r['email']) ?></td>
      <td><span class="badge" style="<?= $r['is_anonymous']?'background:#f1f5f9;color:#475569':'background:#d1fae5;color:#065f46' ?>"><?= $r['is_anonymous']?'Anonymous':'Named' ?></span></td>
      <td style="font-size:.75rem;color:var(--text-muted)"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php adminFoot(); ?>
