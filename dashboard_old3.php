<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$_page = 'Dashboard';

$d = db();

// KPI — Expenses only
$kpi = [
  'total_expenses'  => (int)$d->query("SELECT COUNT(*) FROM expenses")->fetchColumn(),
  'expenses_total'  => (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn(),
  'expenses_paid'   => (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE payment_status='paid'")->fetchColumn(),
  'expenses_unpaid' => (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE payment_status='unpaid'")->fetchColumn(),
  'overdue_count'   => (int)$d->query("SELECT COUNT(*) FROM expenses WHERE due_date IS NOT NULL AND payment_status='unpaid' AND due_date < CURDATE()")->fetchColumn(),
];

// Monthly expenses chart (last 6 months)
$months = []; $exp = [];
for ($i = 5; $i >= 0; $i--) {
  $m = date('Y-m', strtotime("-$i months"));
  $months[] = date('M Y', strtotime($m . '-01'));
  $stmt = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')=?");
  $stmt->execute([$m]); $exp[] = (float)$stmt->fetchColumn();
}

// Budget vs used per category
$budget_data = $d->query("
  SELECT c.name, c.budget, COALESCE(SUM(e.amount),0) used
  FROM expense_categories c
  LEFT JOIN expenses e ON e.category_id = c.id
  GROUP BY c.id ORDER BY used DESC
")->fetchAll();

// Expense due payment reminders (30 days)
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

// Recent expenses
$recent_exp = $d->query("
  SELECT e.*, c.name cat_name
  FROM expenses e
  LEFT JOIN expense_categories c ON c.id = e.category_id
  ORDER BY e.id DESC LIMIT 6
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
  <div class="col-md-6 col-xl-3">
    <div class="kpi-card d-flex justify-content-between align-items-start">
      <div><div class="kpi-label">Total Perbelanjaan</div><div class="kpi-value"><?= $kpi['total_expenses'] ?></div></div>
      <div class="kpi-icon bg-primary-soft"><i class="bi bi-wallet2"></i></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="kpi-card d-flex justify-content-between align-items-start">
      <div><div class="kpi-label">Jumlah Keseluruhan</div><div class="kpi-value"><?= money($kpi['expenses_total']) ?></div></div>
      <div class="kpi-icon bg-info-soft"><i class="bi bi-cash-stack"></i></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="kpi-card d-flex justify-content-between align-items-start">
      <div><div class="kpi-label">Sudah Dibayar</div><div class="kpi-value text-success"><?= money($kpi['expenses_paid']) ?></div></div>
      <div class="kpi-icon bg-success-soft"><i class="bi bi-check-circle"></i></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="kpi-card d-flex justify-content-between align-items-start">
      <div><div class="kpi-label">Belum Dibayar</div><div class="kpi-value text-danger"><?= money($kpi['expenses_unpaid']) ?></div></div>
      <div class="kpi-icon bg-warning-soft"><i class="bi bi-hourglass-split"></i></div>
    </div>
  </div>
</div>

<!-- Chart + Budget Snapshot -->
<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card-panel">
      <div class="card-panel-header"><h6 class="m-0 fw-semibold">Perbelanjaan Bulanan (6 Bulan Lepas)</h6></div>
      <div class="card-panel-body"><canvas id="expChart" height="100"></canvas></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-panel h-100">
      <div class="card-panel-header"><h6 class="m-0 fw-semibold">Budget Kategori</h6></div>
      <div class="card-panel-body">
        <?php foreach(array_slice($budget_data, 0, 4) as $b):
          $pct = $b['budget'] > 0 ? min(100, round($b['used']/$b['budget']*100)) : 0;
          $col = $pct >= 90 ? 'danger' : ($pct >= 70 ? 'warning' : 'success');
        ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <small class="fw-semibold"><?= e($b['name']) ?></small>
            <small class="text-muted"><?= $pct ?>%</small>
          </div>
          <div class="progress" style="height:6px">
            <div class="progress-bar bg-<?= $col ?>" style="width:<?= $pct ?>%"></div>
          </div>
          <div class="d-flex justify-content-between mt-1">
            <small class="text-muted"><?= money($b['used']) ?></small>
            <small class="text-muted"><?= money($b['budget']) ?></small>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if(!$budget_data): ?>
          <div class="text-center text-muted py-3">Tiada data kategori</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Recent Expenses + Reminders -->
<div class="row g-3 mb-4">
  <div class="col-lg-7">
    <div class="card-panel">
      <div class="card-panel-header">
        <h6 class="m-0 fw-semibold">Perbelanjaan Terkini</h6>
        <a href="<?= APP_URL ?>/expenses/index.php" class="small">Lihat semua</a>
      </div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Tajuk</th><th>Kategori</th><th>Tarikh</th><th class="text-end">Jumlah</th></tr></thead>
          <tbody>
            <?php foreach ($recent_exp as $r): ?>
            <tr>
              <td><a href="<?= APP_URL ?>/expenses/view.php?id=<?= $r['id'] ?>"><?= e($r['title']) ?></a></td>
              <td><span class="badge bg-light text-dark"><?= e($r['cat_name'] ?: '—') ?></span></td>
              <td><?= fmt_date($r['expense_date']) ?></td>
              <td class="text-end fw-semibold"><?= money($r['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(!$recent_exp): ?><tr><td colspan="4" class="text-center text-muted py-4">Tiada rekod</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-panel">
      <div class="card-panel-header">
        <h6 class="m-0 fw-semibold text-danger"><i class="bi bi-bell-fill"></i> Overdue
          <?php if($kpi['overdue_count']): ?>
            <span class="badge bg-danger ms-1"><?= $kpi['overdue_count'] ?></span>
          <?php endif; ?>
        </h6>
        <a href="<?= APP_URL ?>/expenses/index.php?status=unpaid" class="small">Lihat semua</a>
      </div>
      <div class="card-panel-body p-0">
        <ul class="list-group list-group-flush">
          <?php foreach (array_slice($exp_reminders, 0, 5) as $o):
            $days = (int)ceil((strtotime($o['due_date']) - strtotime(date('Y-m-d'))) / 86400);
          ?>
          <li class="list-group-item d-flex justify-content-between align-items-start">
            <div>
              <strong><?= e($o['title']) ?></strong><br>
              <small class="text-muted"><?= e($o['vendor'] ?: '—') ?> · due <?= fmt_date($o['due_date']) ?></small>
            </div>
            <div class="text-end">
              <span class="fw-semibold <?= $days < 0 ? 'text-danger' : 'text-warning' ?>"><?= money($o['amount']) ?></span><br>
              <?php if ($days < 0): ?>
                <small class="text-danger">Overdue <?= abs($days) ?>h</small>
              <?php elseif ($days === 0): ?>
                <small class="text-danger">Hari ini!</small>
              <?php else: ?>
                <small class="text-muted"><?= $days ?> hari lagi</small>
              <?php endif; ?>
            </div>
          </li>
          <?php endforeach; ?>
          <?php if(!$exp_reminders): ?>
            <li class="list-group-item text-center text-muted py-4">
              <i class="bi bi-check-circle text-success d-block fs-4 mb-1"></i>
              Tiada bayaran tertunggak
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Expense Due Payment Reminders (full table) -->
<?php if ($exp_reminders): ?>
<div class="row g-3">
  <div class="col-12">
    <div class="card-panel">
      <div class="card-panel-header">
        <h6 class="m-0 fw-semibold text-warning">
          <i class="bi bi-alarm-fill"></i> Expense Due Payment Reminders
          <span class="badge bg-danger ms-2"><?= count($exp_reminders) ?></span>
        </h6>
        <a href="<?= APP_URL ?>/expenses/index.php" class="small">Lihat semua expenses</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Vendor</th><th>Perkara</th><th>Kategori</th>
              <th>Due Date</th><th>Masa Tinggal</th>
              <th class="text-end">Jumlah (RM)</th><th></th>
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
    </div>
  </div>
</div>
<?php endif; ?>

<script>
const ctx = document.getElementById('expChart');
new Chart(ctx, {type:'bar', data:{labels:<?= json_encode($months) ?>,
  datasets:[
    {label:'Perbelanjaan', data:<?= json_encode($exp) ?>, backgroundColor:'rgba(239,68,68,.7)', borderColor:'#ef4444', borderWidth:1, borderRadius:4}
  ]}, options:{responsive:true, plugins:{legend:{position:'bottom'}}, scales:{y:{beginAtZero:true}}}});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
