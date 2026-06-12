<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$_page = 'Dashboard';

$d = db();

// ── KPI ──
$kpi = [
  'total_expenses'  => (int)$d->query("SELECT COUNT(*) FROM expenses")->fetchColumn(),
  'expenses_total'  => (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn(),
  'expenses_paid'   => (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE payment_status='paid'")->fetchColumn(),
  'expenses_unpaid' => (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE payment_status='unpaid'")->fetchColumn(),
  'overdue_count'   => (int)$d->query("SELECT COUNT(*) FROM expenses WHERE due_date IS NOT NULL AND payment_status='unpaid' AND due_date < CURDATE()")->fetchColumn(),
];

// ── Rekod Tempoh (Harian/Mingguan/Bulanan/Tahunan) ──
$periods = [
  'harian'  => [
    'label' => 'Hari Ini',
    'icon'  => 'bi-calendar-day',
    'color' => '#6366f1',
    'where' => "DATE(expense_date) = CURDATE()",
  ],
  'mingguan' => [
    'label' => 'Minggu Ini',
    'icon'  => 'bi-calendar-week',
    'color' => '#0ea5e9',
    'where' => "YEARWEEK(expense_date, 1) = YEARWEEK(CURDATE(), 1)",
  ],
  'bulanan' => [
    'label' => 'Bulan Ini',
    'icon'  => 'bi-calendar-month',
    'color' => '#f59e0b',
    'where' => "MONTH(expense_date)=MONTH(CURDATE()) AND YEAR(expense_date)=YEAR(CURDATE())",
  ],
  'tahunan' => [
    'label' => 'Tahun Ini',
    'icon'  => 'bi-calendar2-range',
    'color' => '#10b981',
    'where' => "YEAR(expense_date)=YEAR(CURDATE())",
  ],
];

$period_stats = [];
foreach ($periods as $key => $p) {
  $w = $p['where'];
  $period_stats[$key] = [
    'count'  => (int)$d->query("SELECT COUNT(*) FROM expenses WHERE {$w}")->fetchColumn(),
    'total'  => (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE {$w}")->fetchColumn(),
    'paid'   => (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE payment_status='paid' AND {$w}")->fetchColumn(),
    'unpaid' => (float)$d->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE payment_status='unpaid' AND {$w}")->fetchColumn(),
  ];
}

// ── Rekod harian 7 hari lepas (untuk chart bar) ──
$daily_labels = []; $daily_data = [];
for ($i = 6; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $daily_labels[] = date('d M', strtotime($date));
  $s = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE(expense_date)=?");
  $s->execute([$date]);
  $daily_data[] = (float)$s->fetchColumn();
}

// ── Chart bulanan 6 bulan ──
$months = []; $exp = [];
for ($i = 5; $i >= 0; $i--) {
  $m = date('Y-m', strtotime("-$i months"));
  $months[] = date('M Y', strtotime($m . '-01'));
  $s = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')=?");
  $s->execute([$m]); $exp[] = (float)$s->fetchColumn();
}

// ── Budget vs used ──
$budget_data = $d->query("
  SELECT c.name, c.budget, COALESCE(SUM(e.amount),0) used
  FROM expense_categories c
  LEFT JOIN expenses e ON e.category_id = c.id
  GROUP BY c.id ORDER BY used DESC
")->fetchAll();

// ── Expense reminders ──
$exp_reminders = $d->query("
  SELECT e.*, ec.name AS cat_name
  FROM expenses e
  LEFT JOIN expense_categories ec ON ec.id = e.category_id
  WHERE e.due_date IS NOT NULL
    AND e.payment_status = 'unpaid'
    AND e.due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
  ORDER BY e.due_date ASC LIMIT 8
")->fetchAll();

// ── Recent expenses ──
$recent_exp = $d->query("
  SELECT e.*, c.name cat_name
  FROM expenses e
  LEFT JOIN expense_categories c ON c.id = e.category_id
  ORDER BY e.id DESC LIMIT 6
")->fetchAll();

// ── Payment Report For Year ──
$report_year = (int)($_GET['report_year'] ?? date('Y'));
$year_months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$year_invoice = []; $year_paid = [];
for ($m = 1; $m <= 12; $m++) {
  $ym = sprintf('%04d-%02d', $report_year, $m);
  // Invoice Value = jumlah keseluruhan (paid + unpaid)
  $s  = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')=?");
  $s->execute([$ym]); $year_invoice[] = (float)$s->fetchColumn();
  // Payments Received = yang dah paid sahaja
  $s  = $d->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')=? AND payment_status='paid'");
  $s->execute([$ym]); $year_paid[] = (float)$s->fetchColumn();
}
// Available years (from DB)
$avail_years = $d->query("SELECT DISTINCT YEAR(expense_date) yr FROM expenses ORDER BY yr DESC")->fetchAll(PDO::FETCH_COLUMN);
if (!$avail_years) $avail_years = [date('Y')];

include __DIR__ . '/includes/header.php';
?>

<style>
/* ── Period Summary Cards ── */
.period-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
  margin-bottom: 24px;
}
@media(max-width:900px) { .period-grid { grid-template-columns: repeat(2,1fr); } }
@media(max-width:500px) { .period-grid { grid-template-columns: 1fr; } }

.period-card {
  background: #fff;
  border: 1px solid #f0f0f0;
  border-radius: 14px;
  padding: 16px 18px;
  box-shadow: 0 1px 4px rgba(0,0,0,.04);
  transition: box-shadow .2s, transform .2s;
  position: relative;
  overflow: hidden;
}
.period-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.08); transform: translateY(-2px); }
.period-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--period-color);
}
.period-card-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 10px;
}
.period-label {
  font-size: 11px; font-weight: 600; color: #9ca3af;
  text-transform: uppercase; letter-spacing: .06em;
}
.period-icon {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px;
  background: var(--period-bg);
  color: var(--period-color);
}
.period-total {
  font-size: 20px; font-weight: 700; color: #111;
  margin-bottom: 10px; font-family: 'DM Mono', monospace;
}
.period-meta {
  display: flex; gap: 10px; flex-wrap: wrap;
}
.period-meta-item {
  font-size: 11px; color: #9ca3af;
}
.period-meta-item strong { color: #374151; font-weight: 600; }
.period-meta-item.paid strong { color: #15803d; }
.period-meta-item.unpaid strong { color: #b91c1c; }
.period-count-badge {
  display: inline-block;
  font-size: 10px; font-weight: 600;
  padding: 2px 7px; border-radius: 99px;
  background: var(--period-bg);
  color: var(--period-color);
  margin-top: 6px;
}

/* ── Chart toggle tabs ── */
.chart-tabs {
  display: flex; gap: 4px;
}
.chart-tab {
  padding: 5px 12px; border-radius: 7px; border: 1px solid #e5e7eb;
  font-size: 12px; font-weight: 500; cursor: pointer;
  background: #fafafa; color: #6b7280;
  transition: all .15s;
}
.chart-tab.active { background: #6366f1; color: #fff; border-color: #6366f1; }
</style>

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

<!-- ── REKOD TEMPOH: Harian / Mingguan / Bulanan / Tahunan ── -->
<div class="period-grid mb-2">
<?php
$period_colors = [
  'harian'   => ['color'=>'#6366f1','bg'=>'#ede9fe'],
  'mingguan' => ['color'=>'#0ea5e9','bg'=>'#e0f2fe'],
  'bulanan'  => ['color'=>'#f59e0b','bg'=>'#fef9c3'],
  'tahunan'  => ['color'=>'#10b981','bg'=>'#d1fae5'],
];
foreach ($periods as $key => $p):
  $s = $period_stats[$key];
  $col = $period_colors[$key];
?>
  <div class="period-card" style="--period-color:<?= $col['color'] ?>;--period-bg:<?= $col['bg'] ?>">
    <div class="period-card-header">
      <div class="period-label"><?= $p['label'] ?></div>
      <div class="period-icon"><i class="bi <?= $p['icon'] ?>"></i></div>
    </div>
    <div class="period-total"><?= money($s['total']) ?></div>
    <div class="period-meta">
      <div class="period-meta-item paid">Paid: <strong><?= money($s['paid']) ?></strong></div>
      <div class="period-meta-item unpaid">Unpaid: <strong><?= money($s['unpaid']) ?></strong></div>
    </div>
    <div class="period-count-badge"><i class="bi bi-receipt"></i> <?= $s['count'] ?> rekod</div>
  </div>
<?php endforeach; ?>
</div>

<!-- ── CHART: Harian + Bulanan ── -->
<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card-panel">
      <div class="card-panel-header">
        <h6 class="m-0 fw-semibold">Graf Perbelanjaan</h6>
        <div class="chart-tabs">
          <button class="chart-tab active" onclick="switchChart('daily', this)">7 Hari</button>
          <button class="chart-tab" onclick="switchChart('monthly', this)">6 Bulan</button>
        </div>
      </div>
      <div class="card-panel-body">
        <canvas id="dailyChart" height="110"></canvas>
        <canvas id="monthlyChart" height="110" style="display:none"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-panel h-100">
      <div class="card-panel-header"><h6 class="m-0 fw-semibold">Budget Kategori</h6></div>
      <div class="card-panel-body">
        <?php foreach(array_slice($budget_data, 0, 5) as $b):
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
        <?php if(!$budget_data): ?><div class="text-center text-muted py-3">Tiada data</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Recent + Reminders -->
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
        <h6 class="m-0 fw-semibold text-danger"><i class="bi bi-bell-fill"></i> Perlu Dibayar
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
              <i class="bi bi-check-circle text-success d-block fs-4 mb-1"></i>Tiada bayaran tertunggak
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Full reminders table -->
<?php if ($exp_reminders): ?>
<div class="row g-3">
  <div class="col-12">
    <div class="card-panel">
      <div class="card-panel-header">
        <h6 class="m-0 fw-semibold text-warning">
          <i class="bi bi-alarm-fill"></i> Expense Due Payment Reminders
          <span class="badge bg-danger ms-2"><?= count($exp_reminders) ?></span>
        </h6>
        <a href="<?= APP_URL ?>/expenses/index.php" class="small">Lihat semua</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr><th>Vendor</th><th>Perkara</th><th>Kategori</th><th>Due Date</th><th>Masa Tinggal</th><th class="text-end">Jumlah (RM)</th><th></th></tr>
          </thead>
          <tbody>
          <?php foreach($exp_reminders as $er):
            $days = (int)ceil((strtotime($er['due_date']) - strtotime(date('Y-m-d'))) / 86400);
            $rc = $days < 0 ? 'table-danger' : ($days <= 7 ? 'table-warning' : '');
          ?>
            <tr class="<?= $rc ?>">
              <td class="fw-semibold"><?= e($er['vendor'] ?: '—') ?></td>
              <td><?= e($er['title']) ?></td>
              <td><span class="badge bg-light text-dark"><?= e($er['cat_name']) ?></span></td>
              <td><?= fmt_date($er['due_date']) ?></td>
              <td>
                <?php if ($days < 0): ?>
                  <span class="badge bg-danger">🚨 Overdue <?= abs($days) ?> hari</span>
                <?php elseif ($days === 0): ?>
                  <span class="badge bg-danger">🔴 Hari ini!</span>
                <?php elseif ($days <= 7): ?>
                  <span class="badge bg-warning text-dark">⚠️ <?= $days ?> hari lagi</span>
                <?php else: ?>
                  <span class="badge bg-info text-dark"><?= $days ?> hari lagi</span>
                <?php endif; ?>
              </td>
              <td class="text-end fw-bold <?= $days < 0 ? 'text-danger' : '' ?>"><?= money($er['amount']) ?></td>
              <td class="text-end">
                <a href="<?= APP_URL ?>/expenses/mark_paid.php?id=<?= $er['id'] ?>"
                   onclick="return confirm('Tandakan sebagai Paid?')"
                   class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Paid</a>
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

<!-- ── PAYMENT REPORT FOR YEAR ── -->
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="card-panel">
      <div class="card-panel-header">
        <h6 class="m-0 fw-semibold">
          Payment Report For Year <?= $report_year ?>
        </h6>
        <!-- Year dropdown -->
        <form method="get" class="d-flex align-items-center gap-2 mb-0">
          <select name="report_year" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            <?php foreach($avail_years as $yr): ?>
              <option value="<?= $yr ?>" <?= $yr==$report_year?'selected':'' ?>><?= $yr ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
      <div class="card-panel-body">
        <canvas id="yearChart" height="80"></canvas>
      </div>
      <!-- Summary row -->
      <div class="card-panel-body border-top pt-3">
        <div class="row g-3 text-center">
          <div class="col-6 col-md-3">
            <div class="fw-semibold text-success fs-6"><?= money(array_sum($year_paid)) ?></div>
            <div class="small text-muted">Total Dibayar <?= $report_year ?></div>
          </div>
          <div class="col-6 col-md-3">
            <div class="fw-semibold text-danger fs-6"><?= money(array_sum($year_invoice) - array_sum($year_paid)) ?></div>
            <div class="small text-muted">Total Belum Dibayar <?= $report_year ?></div>
          </div>
          <div class="col-6 col-md-3">
            <div class="fw-semibold text-dark fs-6"><?= money(array_sum($year_invoice)) ?></div>
            <div class="small text-muted">Jumlah Keseluruhan</div>
          </div>
          <div class="col-6 col-md-3">
            <div class="fw-semibold text-primary fs-6">
              <?php $tot = array_sum($year_invoice);
                echo $tot > 0 ? round(array_sum($year_paid)/$tot*100,1).'%' : '0%'; ?>
            </div>
            <div class="small text-muted">% Sudah Dibayar</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// ── Charts ──
const dailyCtx   = document.getElementById('dailyChart');
const monthlyCtx = document.getElementById('monthlyChart');

const dailyChart = new Chart(dailyCtx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($daily_labels) ?>,
    datasets: [{
      label: 'Perbelanjaan (RM)',
      data: <?= json_encode($daily_data) ?>,
      backgroundColor: 'rgba(99,102,241,.7)',
      borderColor: '#6366f1',
      borderWidth: 1,
      borderRadius: 5,
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

const monthlyChart = new Chart(monthlyCtx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($months) ?>,
    datasets: [{
      label: 'Perbelanjaan (RM)',
      data: <?= json_encode($exp) ?>,
      backgroundColor: 'rgba(239,68,68,.65)',
      borderColor: '#ef4444',
      borderWidth: 1,
      borderRadius: 5,
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

// ── Year Chart ──
const yearCtx = document.getElementById('yearChart');
new Chart(yearCtx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($year_months) ?>,
    datasets: [
      {
        label: 'Invoice Value',
        data: <?= json_encode($year_invoice) ?>,
        backgroundColor: 'rgba(202,138,4,.75)',
        borderColor: '#ca8a04',
        borderWidth: 1,
        borderRadius: 4,
      },
      {
        label: 'Payments Received',
        data: <?= json_encode($year_paid) ?>,
        backgroundColor: 'rgba(20,83,45,.75)',
        borderColor: '#14532d',
        borderWidth: 1,
        borderRadius: 4,
      }
    ]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'bottom' },
      tooltip: {
        callbacks: {
          label: ctx => ' RM ' + ctx.parsed.y.toLocaleString('en-MY', {minimumFractionDigits:2})
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        title: { display: true, text: 'Value (RM)', font: { size: 11 } }
      }
    }
  }
});


function switchChart(type, btn) {
  document.querySelectorAll('.chart-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  if (type === 'daily') {
    dailyCtx.style.display = '';
    monthlyCtx.style.display = 'none';
  } else {
    dailyCtx.style.display = 'none';
    monthlyCtx.style.display = '';
  }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
