<?php
require_once __DIR__ . '/includes/admin_auth.php';

$pdo = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($paymentId && in_array($status, ['pending', 'completed', 'failed', 'refunded'])) {
        $pdo->prepare('UPDATE payments SET status = :status WHERE id = :id')
            ->execute([':status' => $status, ':id' => $paymentId]);
    }
    header('Location: payments.php?msg=updated');
    exit;
}

// Counts per payment status
$counts = ['all' => 0, 'pending' => 0, 'completed' => 0, 'failed' => 0, 'refunded' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) as c FROM payments GROUP BY status')->fetchAll() as $r) {
    $counts[$r['status']] = (int)$r['c'];
    $counts['all'] += (int)$r['c'];
}

$methodCounts = ['all' => 0, 'mpesa' => 0, 'card' => 0, 'paypal' => 0, 'bank' => 0];
foreach ($pdo->query('SELECT method, COUNT(*) as c FROM payments GROUP BY method')->fetchAll() as $r) {
    $methodCounts[$r['method']] = (int)$r['c'];
    $methodCounts['all'] += (int)$r['c'];
}

$statusFilter = $_GET['status'] ?? '';
$methodFilter = $_GET['method'] ?? '';
$where = [];
$params = [];
if ($statusFilter) {
    $where[] = 'p.status = :status';
    $params[':status'] = $statusFilter;
}
if ($methodFilter) {
    $where[] = 'p.method = :method';
    $params[':method'] = $methodFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$perPage = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM payments p $whereSql");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));

$query = "SELECT p.*, b.name as client_name, b.email as client_email, b.service, b.preferred_date, b.preferred_time
          FROM payments p
          LEFT JOIN bookings b ON b.id = p.booking_id
          $whereSql
          ORDER BY p.created_at DESC
          LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$payments = $stmt->fetchAll();

adminHead('Payments', 'Monitor and reconcile payment activity');
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
<div class="flash success">
    <i class="fas fa-check-circle"></i> Payment status updated successfully.
</div>
<?php endif; ?>

<div class="filter-tabs">
    <a href="payments.php" class="filter-tab <?= !$statusFilter && !$methodFilter ? 'active' : '' ?>">All <span>(<?= $counts['all'] ?>)</span></a>
    <a href="payments.php?status=pending" class="filter-tab <?= $statusFilter==='pending' ? 'active' : '' ?>">Pending <span>(<?= $counts['pending'] ?>)</span></a>
    <a href="payments.php?status=completed" class="filter-tab <?= $statusFilter==='completed' ? 'active' : '' ?>">Completed <span>(<?= $counts['completed'] ?>)</span></a>
    <a href="payments.php?status=failed" class="filter-tab <?= $statusFilter==='failed' ? 'active' : '' ?>">Failed <span>(<?= $counts['failed'] ?>)</span></a>
    <a href="payments.php?status=refunded" class="filter-tab <?= $statusFilter==='refunded' ? 'active' : '' ?>">Refunded <span>(<?= $counts['refunded'] ?>)</span></a>
    <a href="payments.php?method=mpesa" class="filter-tab <?= $methodFilter==='mpesa' ? 'active' : '' ?>">M-Pesa <span>(<?= $methodCounts['mpesa'] ?>)</span></a>
    <a href="payments.php?method=card" class="filter-tab <?= $methodFilter==='card' ? 'active' : '' ?>">Card <span>(<?= $methodCounts['card'] ?>)</span></a>
    <a href="payments.php?method=paypal" class="filter-tab <?= $methodFilter==='paypal' ? 'active' : '' ?>">PayPal <span>(<?= $methodCounts['paypal'] ?>)</span></a>
    <a href="payments.php?method=bank" class="filter-tab <?= $methodFilter==='bank' ? 'active' : '' ?>">Bank Transfer <span>(<?= $methodCounts['bank'] ?>)</span></a>
    <div style="margin-left:auto;font-size:0.78rem;color:var(--text-muted);align-self:center">
        Showing <?= count($payments) ?> of <?= $total ?> payments
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <div class="panel-head-left">
            <i class="fas fa-wallet panel-icon"></i>
            <h3>Payment Records</h3>
        </div>
    </div>

    <?php if (empty($payments)): ?>
        <div class="empty-state"><i class="fas fa-money-bill-wave"></i><p>No payments found<?= $statusFilter ? ' with status "' . htmlspecialchars($statusFilter) . '"' : '' ?><?= $methodFilter ? ' for ' . htmlspecialchars(strtoupper($methodFilter)) : '' ?>.</p></div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>Service</th>
                <th>Method</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Transaction</th>
                <th>Received</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
            <td>#<?= $p['id'] ?></td>
            <td>
                <?= htmlspecialchars($p['client_name'] ?: $p['email']) ?><br>
                <span style="font-size:0.78rem;color:var(--text-muted)"><?= htmlspecialchars($p['client_email'] ?: 'no email') ?></span>
            </td>
            <td><?= htmlspecialchars($p['service'] ?? '—') ?><br><span style="font-size:0.78rem;color:var(--text-muted)"><?= $p['preferred_date'] ? date('d M Y', strtotime($p['preferred_date'])) : 'No date' ?></span></td>
            <td><?= strtoupper(htmlspecialchars($p['method'])) ?></td>
            <td>KES <?= number_format($p['amount'], 0) ?></td>
            <td>
                <form method="POST" style="margin:0">
                    <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="update_payment" value="1">
                    <select name="status" class="status-select">
                        <option value="pending" <?= $p['status']==='pending' ? 'selected':'' ?>>Pending</option>
                        <option value="completed" <?= $p['status']==='completed' ? 'selected':'' ?>>Completed</option>
                        <option value="failed" <?= $p['status']==='failed' ? 'selected':'' ?>>Failed</option>
                        <option value="refunded" <?= $p['status']==='refunded' ? 'selected':'' ?>>Refunded</option>
                    </select>
                </form>
            </td>
            <td style="font-size:0.78rem;color:var(--text-muted);max-width:160px;word-break:break-all;">
                <?= htmlspecialchars($p['transaction_id'] ?: $p['reference'] ?: 'N/A') ?>
            </td>
            <td style="white-space:nowrap;font-size:0.78rem;color:var(--text-muted)">
                <?= date('d M Y', strtotime($p['created_at'])) ?><br><?= date('H:i', strtotime($p['created_at'])) ?>
            </td>
            <td>
                <a href="bookings.php?status=pending" class="btn btn-ghost btn-sm" title="View booking"><i class="fas fa-arrow-right"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php if ($pages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?page=<?= $page-1 ?><?= $statusFilter ? '&status='.urlencode($statusFilter) : '' ?><?= $methodFilter ? '&method='.urlencode($methodFilter) : '' ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
    <?php endif; ?>
    <?php for ($p = 1; $p <= $pages; $p++): ?>
    <a href="?page=<?= $p ?><?= $statusFilter ? '&status='.urlencode($statusFilter) : '' ?><?= $methodFilter ? '&method='.urlencode($methodFilter) : '' ?>" class="page-btn <?= $p===$page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
    <?php if ($page < $pages): ?>
    <a href="?page=<?= $page+1 ?><?= $statusFilter ? '&status='.urlencode($statusFilter) : '' ?><?= $methodFilter ? '&method='.urlencode($methodFilter) : '' ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php adminFoot(); ?>
