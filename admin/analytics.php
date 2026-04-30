<?php
/**
 * Admin — Analytics Dashboard
 */
require_once __DIR__ . '/includes/admin_auth.php';

$pdo = getDB();

// ── Core counts ─────────────────────────────────────────────
$totalBookings    = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$confirmedBookings= $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn();
$pendingBookings  = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
$cancelledBookings= $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='cancelled'")->fetchColumn();
$totalUsers       = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$anonUsers        = $pdo->query("SELECT COUNT(*) FROM users WHERE is_anonymous=1")->fetchColumn();
$totalMessages    = $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
$unreadMessages   = $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn();

// ── Bookings by month (last 6 months) ───────────────────────
$byMonth = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%b %Y') AS label,
           DATE_FORMAT(created_at,'%Y-%m') AS sort_key,
           COUNT(*) AS total,
           SUM(status='confirmed') AS confirmed,
           SUM(status='pending')   AS pending,
           SUM(status='cancelled') AS cancelled
    FROM bookings
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY sort_key, label
    ORDER BY sort_key ASC
")->fetchAll();

// ── Bookings by service ──────────────────────────────────────
$byService = $pdo->query("
    SELECT service, COUNT(*) as total
    FROM bookings
    GROUP BY service
    ORDER BY total DESC
    LIMIT 8
")->fetchAll();

// ── Bookings by time slot ────────────────────────────────────
$byTime = $pdo->query("
    SELECT preferred_time, COUNT(*) as total
    FROM bookings
    GROUP BY preferred_time
    ORDER BY total DESC
")->fetchAll();

// ── New users per month (last 6) ─────────────────────────────
$usersByMonth = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%b %Y') AS label,
           DATE_FORMAT(created_at,'%Y-%m') AS sort_key,
           COUNT(*) AS total
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY sort_key, label
    ORDER BY sort_key ASC
")->fetchAll();

// ── Recent activity ──────────────────────────────────────────
$recentActivity = $pdo->query("
    SELECT 'booking' as type, name, service as detail, created_at, status FROM bookings
    UNION ALL
    SELECT 'message' as type, name, subject as detail, created_at, NULL FROM contacts
    ORDER BY created_at DESC LIMIT 10
")->fetchAll();

// JSON encode for chart.js
$monthLabels   = json_encode(array_column($byMonth, 'label'));
$monthTotal    = json_encode(array_map('intval', array_column($byMonth, 'total')));
$monthConfirmed= json_encode(array_map('intval', array_column($byMonth, 'confirmed')));
$monthPending  = json_encode(array_map('intval', array_column($byMonth, 'pending')));
$serviceLabels = json_encode(array_column($byService, 'service'));
$serviceData   = json_encode(array_map('intval', array_column($byService, 'total')));
$timeLabels    = json_encode(array_column($byTime, 'preferred_time'));
$timeData      = json_encode(array_map('intval', array_column($byTime, 'total')));
$userMonthLabels = json_encode(array_column($usersByMonth, 'label'));
$userMonthData   = json_encode(array_map('intval', array_column($usersByMonth, 'total')));

adminHead('Analytics', 'Practice performance overview');
?>
<style>
.chart-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem}
.chart-panel{background:white;border-radius:var(--radius);border:1px solid var(--border);box-shadow:var(--shadow);padding:1.4rem;position:relative}
.chart-panel.full{grid-column:1/-1}
.chart-title{font-size:.88rem;font-weight:700;color:var(--dark);margin-bottom:1.2rem;display:flex;align-items:center;gap:.5rem}
.chart-title i{color:var(--primary)}
.chart-wrap{position:relative;height:240px}
.chart-wrap.sm{height:200px}
.kpi-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:1.2rem;margin-bottom:1.5rem}
.kpi{background:white;border-radius:var(--radius);border:1px solid var(--border);box-shadow:var(--shadow);padding:1.3rem 1.5rem;display:flex;align-items:center;gap:1rem;position:relative;overflow:hidden}
.kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.kpi.teal::before{background:linear-gradient(90deg,var(--primary),var(--primary-d))}
.kpi.amber::before{background:linear-gradient(90deg,#f59e0b,#d97706)}
.kpi.green::before{background:linear-gradient(90deg,#22c55e,#16a34a)}
.kpi.red::before{background:linear-gradient(90deg,#ef4444,#dc2626)}
.kpi.blue::before{background:linear-gradient(90deg,#3b82f6,#1d4ed8)}
.kpi.purple::before{background:linear-gradient(90deg,#8b5cf6,#6d28d9)}
.kpi-icon{width:44px;height:44px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;flex-shrink:0}
.kpi-icon.teal{background:rgba(90,125,124,.1);color:var(--primary)}
.kpi-icon.amber{background:rgba(245,158,11,.1);color:#d97706}
.kpi-icon.green{background:rgba(34,197,94,.1);color:#16a34a}
.kpi-icon.red{background:rgba(239,68,68,.1);color:#dc2626}
.kpi-icon.blue{background:rgba(59,130,246,.1);color:#1d4ed8}
.kpi-icon.purple{background:rgba(139,92,246,.1);color:#6d28d9}
.kpi-num{font-size:1.85rem;font-weight:700;color:var(--dark);font-family:'Playfair Display',serif;line-height:1}
.kpi-label{font-size:.78rem;color:var(--text-muted);margin-top:.25rem;font-weight:500}
.kpi-sub{font-size:.71rem;color:var(--text-muted);margin-top:.4rem}
.activity-row{display:flex;gap:.75rem;padding:.75rem 1.4rem;border-bottom:1px solid #f3f4f6;align-items:center;font-size:.82rem}
.activity-row:last-child{border-bottom:none}
.act-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
@media(max-width:900px){.chart-grid{grid-template-columns:1fr}}
</style>

<!-- KPI Row -->
<div class="kpi-row">
  <div class="kpi teal">
    <div class="kpi-icon teal"><i class="fas fa-calendar-days"></i></div>
    <div><div class="kpi-num"><?= $totalBookings ?></div><div class="kpi-label">Total Bookings</div><div class="kpi-sub"><?= $confirmedBookings ?> confirmed</div></div>
  </div>
  <div class="kpi green">
    <div class="kpi-icon green"><i class="fas fa-circle-check"></i></div>
    <div><div class="kpi-num"><?= $confirmedBookings ?></div><div class="kpi-label">Confirmed Sessions</div><div class="kpi-sub"><?= $totalBookings > 0 ? round($confirmedBookings/$totalBookings*100) : 0 ?>% rate</div></div>
  </div>
  <div class="kpi amber">
    <div class="kpi-icon amber"><i class="fas fa-clock"></i></div>
    <div><div class="kpi-num"><?= $pendingBookings ?></div><div class="kpi-label">Pending Bookings</div><div class="kpi-sub">Needs attention</div></div>
  </div>
  <div class="kpi red">
    <div class="kpi-icon red"><i class="fas fa-ban"></i></div>
    <div><div class="kpi-num"><?= $cancelledBookings ?></div><div class="kpi-label">Cancelled</div><div class="kpi-sub"><?= $totalBookings > 0 ? round($cancelledBookings/$totalBookings*100) : 0 ?>% rate</div></div>
  </div>
  <div class="kpi blue">
    <div class="kpi-icon blue"><i class="fas fa-users"></i></div>
    <div><div class="kpi-num"><?= $totalUsers ?></div><div class="kpi-label">Registered Clients</div><div class="kpi-sub"><?= $anonUsers ?> anonymous</div></div>
  </div>
  <div class="kpi purple">
    <div class="kpi-icon purple"><i class="fas fa-envelope"></i></div>
    <div><div class="kpi-num"><?= $totalMessages ?></div><div class="kpi-label">Messages Received</div><div class="kpi-sub"><?= $unreadMessages ?> unread</div></div>
  </div>
</div>

<!-- Charts row 1 -->
<div class="chart-grid">
  <div class="chart-panel full">
    <div class="chart-title"><i class="fas fa-chart-line"></i> Bookings Over Time (Last 6 Months)</div>
    <div class="chart-wrap"><canvas id="bookingsTrend"></canvas></div>
  </div>

  <div class="chart-panel">
    <div class="chart-title"><i class="fas fa-chart-pie"></i> Bookings by Service</div>
    <div class="chart-wrap sm"><canvas id="serviceChart"></canvas></div>
  </div>

  <div class="chart-panel">
    <div class="chart-title"><i class="fas fa-chart-bar"></i> Preferred Time Slots</div>
    <div class="chart-wrap sm"><canvas id="timeChart"></canvas></div>
  </div>

  <div class="chart-panel">
    <div class="chart-title"><i class="fas fa-user-plus"></i> New Client Registrations</div>
    <div class="chart-wrap sm"><canvas id="userChart"></canvas></div>
  </div>

  <!-- Booking Status Donut -->
  <div class="chart-panel">
    <div class="chart-title"><i class="fas fa-circle-half-stroke"></i> Booking Status Breakdown</div>
    <div class="chart-wrap sm"><canvas id="statusDonut"></canvas></div>
  </div>
</div>

<!-- Recent Activity -->
<div class="panel">
  <div class="panel-head">
    <div class="panel-head-left"><i class="fas fa-bolt panel-icon"></i><h3>Recent Activity</h3></div>
  </div>
  <?php foreach ($recentActivity as $a): ?>
  <div class="activity-row">
    <div class="act-dot" style="background:<?= $a['type']==='booking' ? 'var(--primary)' : '#3b82f6' ?>"></div>
    <div style="flex:1">
      <strong><?= htmlspecialchars($a['name']) ?></strong>
      <span style="color:var(--text-muted)"> · <?= $a['type']==='booking' ? 'booked' : 'messaged' ?> · <?= htmlspecialchars($a['detail']) ?></span>
    </div>
    <?php if ($a['type']==='booking' && $a['status']): ?>
    <span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span>
    <?php endif; ?>
    <span style="font-size:.73rem;color:var(--text-muted);white-space:nowrap;margin-left:.75rem"><?= date('d M, H:i', strtotime($a['created_at'])) ?></span>
  </div>
  <?php endforeach; ?>
</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" crossorigin="anonymous"></script>
<script>
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#6b7280';

const primary = '#5a7d7c', accent = '#d2aa7e', green = '#22c55e', red = '#ef4444', amber = '#f59e0b', blue = '#3b82f6';

// 1. Bookings trend (line)
new Chart(document.getElementById('bookingsTrend'), {
  type: 'line',
  data: {
    labels: <?= $monthLabels ?>,
    datasets: [
      { label:'Total', data:<?= $monthTotal ?>, borderColor:primary, backgroundColor:'rgba(90,125,124,.1)', fill:true, tension:.4, pointRadius:5 },
      { label:'Confirmed', data:<?= $monthConfirmed ?>, borderColor:green, backgroundColor:'transparent', borderDash:[4,4], tension:.4, pointRadius:4 },
      { label:'Pending', data:<?= $monthPending ?>, borderColor:amber, backgroundColor:'transparent', borderDash:[4,4], tension:.4, pointRadius:4 },
    ]
  },
  options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'top'}}, scales:{ y:{ beginAtZero:true, ticks:{stepSize:1} } } }
});

// 2. Service breakdown (doughnut)
new Chart(document.getElementById('serviceChart'), {
  type: 'doughnut',
  data: {
    labels: <?= $serviceLabels ?>,
    datasets:[{ data:<?= $serviceData ?>, backgroundColor:[primary,accent,green,amber,blue,'#8b5cf6','#ec4899','#14b8a6'], borderWidth:2 }]
  },
  options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'right',labels:{font:{size:11}}}} }
});

// 3. Time slots (bar)
new Chart(document.getElementById('timeChart'), {
  type:'bar',
  data:{
    labels:<?= $timeLabels ?>,
    datasets:[{ label:'Bookings', data:<?= $timeData ?>, backgroundColor:[primary,accent,blue], borderRadius:6 }]
  },
  options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true,ticks:{stepSize:1}} } }
});

// 4. New users (bar)
new Chart(document.getElementById('userChart'), {
  type:'bar',
  data:{
    labels:<?= $userMonthLabels ?>,
    datasets:[{ label:'New Clients', data:<?= $userMonthData ?>, backgroundColor:blue, borderRadius:6 }]
  },
  options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true,ticks:{stepSize:1}} } }
});

// 5. Status donut
new Chart(document.getElementById('statusDonut'), {
  type:'doughnut',
  data:{
    labels:['Confirmed','Pending','Cancelled'],
    datasets:[{ data:[<?= $confirmedBookings ?>,<?= $pendingBookings ?>,<?= $cancelledBookings ?>], backgroundColor:[green,amber,red], borderWidth:2 }]
  },
  options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom',labels:{font:{size:12}}}} }
});
</script>

<?php adminFoot(); ?>
