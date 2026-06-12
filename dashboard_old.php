<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$_page = 'Dashboard';

$d = db();
$kpi = [
  'total_invoices' => (int)$d->query("SELECT COUNT(*) FROM invoices")->fetchColumn(),
  'received' => (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed'")->fetchColumn(),
  'outstanding' => (float)$d->query("SELECT COALESCE(SUM(total_amount-paid_amount),0) FROM invoices WHERE status IN ('sent','partial','overdue')")->fetchColumn(),
  'monthly_revenue' => (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())")->fetchColumn(),
  'expenses' => (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn(),
  'overdue_count' => (int)$d->query("SELECT COUNT(*) FROM invoices WHERE status='overdue'")->fetchColumn(),
];

// Monthly chart (last 6 months)
$months = []; $rev = []; $exp = [];
for ($i=5; $i>=0; $i--) {
  $m = date('Y-m', strtotime("-$i months"));
  $months[] = date('M Y', strtotime($m . '-01'));
  $stmt = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE_FORMAT(payment_date,'%Y-%m')=?");
  $stmt->execute([$m]); $rev[] = (float)$stmt->fetchColumn();
  $stmt = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')=?");
  $stmt->execute([$m]); $exp[] = (float)$stmt->fetchColumn();
}

$recent = $d->query("SELECT i.*, c.company_name FROM invoices i LEFT JOIN clients c ON c.id=i.client_id ORDER BY i.id DESC LIMIT 6")->fetchAll();
$overdue = $d->query("SELECT i.*, c.company_name FROM invoices i LEFT JOIN clients c ON c.id=i.client_id WHERE i.status IN ('overdue','sent','partial') AND i.due_date < CURDATE() ORDER BY i.due_date LIMIT 5")->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="row g-3 mb-4">
  <div class="col-md-6 col-xl-3">
    <div class="kpi-card d-flex justify-content-between align-items-start">
      <div><div class="kpi-label">Total Invoices</div><div class="kpi-value"><?= $kpi['total_invoices'] ?></div></div>
      <div class="kpi-icon bg-primary-soft"><i class="bi bi-file-earmark-text"></i></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="kpi-card d-flex justify-content-between align-items-start">
      <div><div class="kpi-label">Payment Received</div><div class="kpi-value"><?= money($kpi['received']) ?></div></div>
      <div class="kpi-icon bg-success-soft"><i class="bi bi-cash-coin"></i></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="kpi-card d-flex justify-content-between align-items-start">
      <div><div class="kpi-label">Outstanding</div><div class="kpi-value"><?= money($kpi['outstanding']) ?></div></div>
      <div class="kpi-icon bg-warning-soft"><i class="bi bi-hourglass-split"></i></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="kpi-card d-flex justify-content-between align-items-start">
      <div><div class="kpi-label">Monthly Revenue</div><div class="kpi-value"><?= money($kpi['monthly_revenue']) ?></div></div>
      <div class="kpi-icon bg-info-soft"><i class="bi bi-graph-up-arrow"></i></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card-panel">
      <div class="card-panel-header"><h6 class="m-0 fw-semibold">Revenue vs Expenses (Last 6 months)</h6></div>
      <div class="card-panel-body"><canvas id="revChart" height="100"></canvas></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-panel h-100">
      <div class="card-panel-header"><h6 class="m-0 fw-semibold">Financial Snapshot</h6></div>
      <div class="card-panel-body">
        <div class="mb-3"><div class="kpi-label">Total Expenses</div><div class="kpi-value text-danger"><?= money($kpi['expenses']) ?></div></div>
        <div class="mb-3"><div class="kpi-label">Net Profit</div><div class="kpi-value text-success"><?= money($kpi['received'] - $kpi['expenses']) ?></div></div>
        <div><div class="kpi-label">Overdue Invoices</div><div class="kpi-value text-warning"><?= $kpi['overdue_count'] ?></div></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card-panel">
      <div class="card-panel-header"><h6 class="m-0 fw-semibold">Recent Invoices</h6><a href="<?= APP_URL ?>/invoices/index.php" class="small">View all</a></div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>#</th><th>Client</th><th>Amount</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($recent as $r): ?>
            <tr>
              <td><a href="<?= APP_URL ?>/invoices/view.php?id=<?= $r['id'] ?>"><?= e($r['invoice_number']) ?></a></td>
              <td><?= e($r['company_name']) ?></td>
              <td><?= money($r['total_amount']) ?></td>
              <td><?= status_badge($r['status']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(!$recent): ?><tr><td colspan="4" class="text-center text-muted py-4">No invoices yet</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-panel">
      <div class="card-panel-header"><h6 class="m-0 fw-semibold text-danger"><i class="bi bi-bell"></i> Due Payment Reminders</h6></div>
      <div class="card-panel-body p-0">
        <ul class="list-group list-group-flush">
          <?php foreach ($overdue as $o): ?>
          <li class="list-group-item d-flex justify-content-between">
            <div><strong><?= e($o['invoice_number']) ?></strong><br><small class="text-muted"><?= e($o['company_name']) ?> · due <?= fmt_date($o['due_date']) ?></small></div>
            <div class="text-end"><span class="fw-semibold text-danger"><?= money($o['total_amount']-$o['paid_amount']) ?></span></div>
          </li>
          <?php endforeach; ?>
          <?php if(!$overdue): ?><li class="list-group-item text-center text-muted py-4">No pending reminders</li><?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
const ctx=document.getElementById('revChart');
new Chart(ctx,{type:'line',data:{labels:<?= json_encode($months) ?>,
  datasets:[
    {label:'Revenue',data:<?= json_encode($rev) ?>,borderColor:'#4f46e5',backgroundColor:'rgba(79,70,229,.1)',fill:true,tension:.35},
    {label:'Expenses',data:<?= json_encode($exp) ?>,borderColor:'#ef4444',backgroundColor:'rgba(239,68,68,.08)',fill:true,tension:.35}
  ]},options:{responsive:true,plugins:{legend:{position:'bottom'}},scales:{y:{beginAtZero:true}}}});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
