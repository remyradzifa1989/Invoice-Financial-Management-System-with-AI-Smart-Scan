<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$_page = 'Dashboard';

$d = db();
$kpi = [
  'total_invoices' => (int)$d->query("SELECT COUNT(*) FROM invoices")->fetchColumn(),
  'received'       => (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed'")->fetchColumn(),
  'outstanding'    => (float)$d->query("SELECT COALESCE(SUM(total_amount-paid_amount),0) FROM invoices WHERE status IN ('sent','partial','overdue')")->fetchColumn(),
  'monthly_revenue'=> (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())")->fetchColumn(),
  'expenses'       => (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn(),
  'overdue_count'  => (int)$d->query("SELECT COUNT(*) FROM invoices WHERE status='overdue'")->fetchColumn(),
];

// Monthly chart
$months = []; $rev = []; $exp = [];
for ($i=5; $i>=0; $i--) {
  $m = date('Y-m', strtotime("-$i months"));
  $months[] = date('M Y', strtotime($m . '-01'));
  $stmt = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE_FORMAT(payment_date,'%Y-%m')=?");
  $stmt->execute([$m]); $rev[] = (float)$stmt->fetchColumn();
  $stmt = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')=?");
  $stmt->execute([$m]); $exp[] = (float)$stmt->fetchColumn();
}

$recent  = $d->query("SELECT i.*, c.company_name FROM invoices i LEFT JOIN clients c ON c.id=i.client_id ORDER BY i.id DESC LIMIT 6")->fetchAll();
$overdue = $d->query("SELECT i.*, c.company_name FROM invoices i LEFT JOIN clients c ON c.id=i.client_id WHERE i.status IN ('overdue','sent','partial') AND i.due_date < CURDATE() ORDER BY i.due_date LIMIT 5")->fetchAll();

// ── Expense due date reminders (30 hari) ──
$exp_reminders = $d->query("
  SELECT e.*, ec.name AS cat_name
  FROM expenses e
  LEFT JOIN expense_categories ec ON ec.id = e.category_id
  WHERE e.due_date IS NOT NULL
    AND e.payment_status = 'unpaid'
    AND e.due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
  ORDER BY e.due_date ASC
  LIMIT 8
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- KPI Cards -->
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

<!-- Chart + Snapshot -->
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

<!-- Recent Invoices + Invoice Reminders -->
<div class="row g-3 mb-4">
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
      <div class="card-panel-header"><h6 class="m-0 fw-semibold text-danger"><i class="bi bi-bell-fill"></i> Invoice Payment Reminders</h6></div>
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

<!-- ── EXPENSE DUE PAYMENT REMINDERS (BAHARU) ── -->
<div class="row g-3">
  <div class="col-12">
    <div class="card-panel">
      <div class="card-panel-header">
        <h6 class="m-0 fw-semibold text-warning">
          <i class="bi bi-alarm-fill"></i> Expense Due Payment Reminders
          <?php if(count($exp_reminders)): ?>
            <span class="badge bg-danger ms-2"><?= count($exp_reminders) ?></span>
          <?php endif; ?>
        </h6>
        <a href="<?= APP_URL ?>/expenses/index.php" class="small">View all expenses</a>
      </div>
      <?php if (!$exp_reminders): ?>
        <div class="card-panel-body text-center text-muted py-4">
          <i class="bi bi-check-circle fs-4 d-block mb-2 text-success"></i>
          Tiada bayaran tertunggak dalam 30 hari akan datang. Semua selesai!
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Vendor</th>
              <th>Perkara</th>
              <th>Kategori</th>
              <th>Due Date</th>
              <th>Masa Tinggal</th>
              <th class="text-end">Jumlah (RM)</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($exp_reminders as $er):
            $days = (int)ceil((strtotime($er['due_date']) - strtotime(date('Y-m-d'))) / 86400);
            $row_class = $days < 0 ? 'table-danger' : ($days <= 7 ? 'table-warning' : '');
          ?>
            <tr class="<?= $row_class ?>">
              <td class="fw-semibold"><?= e($er['vendor'] ?: '—') ?></td>
              <td><?= e($er['title']) ?></td>
              <td><span class="badge bg-light text-dark"><?= e($er['cat_name']) ?></span></td>
              <td><?= fmt_date($er['due_date']) ?></td>
              <td>
                <?php if ($days < 0): ?>
                  <span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill"></i> Overdue <?= abs($days) ?> hari</span>
                <?php elseif ($days === 0): ?>
                  <span class="badge bg-danger"><i class="bi bi-clock-fill"></i> Hari ini!</span>
                <?php elseif ($days <= 7): ?>
                  <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> <?= $days ?> hari lagi</span>
                <?php else: ?>
                  <span class="badge bg-info text-dark"><?= $days ?> hari lagi</span>
                <?php endif; ?>
              </td>
              <td class="text-end fw-bold <?= $days < 0 ? 'text-danger' : '' ?>"><?= money($er['amount']) ?></td>
              <td class="text-end">
                <a href="<?= APP_URL ?>/expenses/mark_paid.php?id=<?= $er['id'] ?>"
                   onclick="return confirm('Tandakan bayaran ini sebagai Paid?')"
                   class="btn btn-sm btn-success" title="Mark as Paid">
                  <i class="bi bi-check-lg"></i> Paid
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot class="table-light">
            <tr>
              <td colspan="5" class="fw-semibold text-end">Jumlah Tertunggak:</td>
              <td class="text-end fw-bold text-danger"><?= money(array_sum(array_column($exp_reminders, 'amount'))) ?></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const ctx = document.getElementById('revChart');
new Chart(ctx, {type:'line', data:{labels:<?= json_encode($months) ?>,
  datasets:[
    {label:'Revenue', data:<?= json_encode($rev) ?>, borderColor:'#4f46e5', backgroundColor:'rgba(79,70,229,.1)', fill:true, tension:.35},
    {label:'Expenses',data:<?= json_encode($exp) ?>, borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,.08)', fill:true, tension:.35}
  ]}, options:{responsive:true, plugins:{legend:{position:'bottom'}}, scales:{y:{beginAtZero:true}}}});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
