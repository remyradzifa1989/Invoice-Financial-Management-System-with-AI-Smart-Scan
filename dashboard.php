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

<!-- ── Google Fonts ── -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════
   DASHBOARD — Premium Navy & Teal Theme
   Tokens scoped to .db so they don't bleed into
   the host layout (header / sidebar).
═══════════════════════════════════════════════ */
.db {
  --navy:      #0f2d5a;
  --navy-mid:  #1a3d73;
  --teal:      #0d9488;
  --teal-lt:   #ccfbf1;
  --gold:      #d97706;
  --gold-lt:   #fef3c7;
  --rose:      #e11d48;
  --rose-lt:   #ffe4e6;
  --sky:       #0ea5e9;
  --sky-lt:    #e0f2fe;

  --bg:        #f1f5f9;
  --card:      #ffffff;
  --card-alt:  #f8fafc;
  --border:    #e2e8f0;
  --border-md: #cbd5e1;

  --txt-h:     #0f172a;
  --txt-b:     #334155;
  --txt-s:     #64748b;
  --txt-m:     #94a3b8;

  --r-sm:  8px;
  --r-md:  12px;
  --r-lg:  16px;
  --r-xl:  20px;

  --shadow-sm:  0 1px 3px rgba(15,45,90,.06), 0 1px 2px rgba(15,45,90,.04);
  --shadow-md:  0 4px 16px rgba(15,45,90,.08), 0 1px 4px rgba(15,45,90,.04);
  --shadow-lg:  0 12px 40px rgba(15,45,90,.12);

  font-family: 'DM Sans', system-ui, sans-serif;
  color: var(--txt-b);
}

/* ─── Page header ─── */
.db-topbar {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--border);
}
.db-title {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 20px;
  font-weight: 800;
  color: var(--navy);
  letter-spacing: -.4px;
  line-height: 1.1;
}
.db-subtitle {
  font-size: 12px;
  color: var(--txt-m);
  margin-top: 3px;
}
.db-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  font-weight: 500;
  color: var(--txt-s);
  background: var(--card);
  border: 1px solid var(--border);
  padding: 6px 14px;
  border-radius: 99px;
  box-shadow: var(--shadow-sm);
}
.db-chip i { color: var(--navy); font-size: 12px; }

/* ─── Section eyebrow ─── */
.db-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
  margin-top: 4px;
}
.db-section-label {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .1em;
  color: var(--txt-m);
  white-space: nowrap;
}
.db-section-line {
  flex: 1;
  height: 1px;
  background: var(--border);
}

/* ─── KPI Cards ─── */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4,1fr);
  gap: 12px;
  margin-bottom: 10px;
}
@media(max-width:960px){.kpi-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:520px){.kpi-grid{grid-template-columns:1fr}}

.kpi-box {
  background: var(--navy);
  border-radius: var(--r-lg);
  padding: 20px;
  position: relative;
  overflow: hidden;
  transition: transform .2s, box-shadow .2s;
  box-shadow: var(--shadow-md);
}
.kpi-box:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }

/* Subtle diagonal highlight on card */
.kpi-box::before {
  content: '';
  position: absolute;
  top: -30px; right: -30px;
  width: 100px; height: 100px;
  border-radius: 50%;
  background: rgba(255,255,255,.05);
  pointer-events: none;
}

/* Colour variants */
.kpi-box.kpi-sky  { background: linear-gradient(135deg,#0e4d7e 0%,#0369a1 100%); }
.kpi-box.kpi-teal { background: linear-gradient(135deg,#0f766e 0%,#14b8a6 100%); }
.kpi-box.kpi-rose { background: linear-gradient(135deg,#9f1239 0%,#e11d48 100%); }

.kpi-icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px; height: 36px;
  border-radius: 10px;
  background: rgba(255,255,255,.13);
  font-size: 16px;
  color: #fff;
  margin-bottom: 16px;
}
.kpi-lbl {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .09em;
  color: rgba(255,255,255,.55);
  margin-bottom: 5px;
}
.kpi-val {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 22px;
  font-weight: 800;
  color: #fff;
  letter-spacing: -.5px;
  line-height: 1;
}
.kpi-val .kpi-rm {
  font-size: 13px;
  font-weight: 600;
  opacity: .7;
  margin-right: 1px;
}
.kpi-foot {
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px solid rgba(255,255,255,.12);
  font-size: 10.5px;
  color: rgba(255,255,255,.55);
  display: flex;
  align-items: center;
  gap: 5px;
}
.kpi-dot {
  width: 5px; height: 5px;
  border-radius: 50%;
  background: rgba(255,255,255,.4);
  flex-shrink: 0;
}

/* ─── Period cards ─── */
.prd-grid {
  display: grid;
  grid-template-columns: repeat(4,1fr);
  gap: 12px;
  margin-bottom: 10px;
}
@media(max-width:960px){.prd-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:520px){.prd-grid{grid-template-columns:1fr}}

.prd-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  padding: 16px 18px;
  position: relative;
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  transition: border-color .2s, box-shadow .2s, transform .2s;
}
.prd-card:hover {
  border-color: var(--border-md);
  box-shadow: var(--shadow-md);
  transform: translateY(-1px);
}
/* Left accent bar */
.prd-card::before {
  content: '';
  position: absolute;
  left: 0; top: 0; bottom: 0;
  width: 3px;
  background: var(--pc);
  border-radius: 0 2px 2px 0;
}
.prd-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.prd-name {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .09em;
  color: var(--txt-m);
}
.prd-icon {
  width: 28px; height: 28px;
  border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px;
  background: var(--pc-bg);
  color: var(--pc);
}
.prd-total {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 19px;
  font-weight: 800;
  color: var(--txt-h);
  letter-spacing: -.4px;
  margin-bottom: 10px;
}
.prd-pills { display: flex; gap: 6px; flex-wrap: wrap; }
.prd-pill {
  font-size: 10px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 99px;
}
.prd-pill.paid   { background: var(--teal-lt); color: var(--teal); }
.prd-pill.unpaid { background: var(--rose-lt);  color: var(--rose); }
.prd-count {
  margin-top: 10px;
  font-size: 10.5px;
  color: var(--txt-m);
  display: flex; align-items: center; gap: 4px;
}
.prd-count i { color: var(--pc); opacity: .8; font-size: 11px; }

/* ─── Panel card (generic white card) ─── */
.panel {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  transition: box-shadow .2s;
}
.panel:hover { box-shadow: var(--shadow-md); }
.panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
}
.panel-title {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 13px;
  font-weight: 700;
  color: var(--txt-h);
  display: flex;
  align-items: center;
  gap: 7px;
}
.panel-title-icon {
  width: 26px; height: 26px;
  border-radius: 6px;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px;
}
.panel-link {
  font-size: 11px;
  font-weight: 600;
  color: var(--navy);
  text-decoration: none;
  opacity: .7;
  transition: opacity .15s;
}
.panel-link:hover { opacity: 1; color: var(--navy); }
.panel-body { padding: 20px; }

/* ─── Chart toggle ─── */
.chart-switch {
  display: flex;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  padding: 3px;
  gap: 2px;
}
.chart-switch-btn {
  padding: 4px 13px;
  border-radius: 6px;
  border: none;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  background: transparent;
  color: var(--txt-m);
  transition: all .15s;
  font-family: 'DM Sans', sans-serif;
}
.chart-switch-btn.on {
  background: var(--navy);
  color: #fff;
  box-shadow: 0 1px 4px rgba(15,45,90,.25);
}

/* ─── Budget bars ─── */
.bgt-row { margin-bottom: 15px; }
.bgt-row:last-child { margin-bottom: 0; }
.bgt-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 5px;
}
.bgt-name {
  font-size: 12px;
  font-weight: 600;
  color: var(--txt-b);
}
.bgt-pct {
  font-family: 'JetBrains Mono', monospace;
  font-size: 11px;
  font-weight: 600;
}
.bgt-track {
  height: 6px;
  background: var(--border);
  border-radius: 99px;
  overflow: hidden;
  margin-bottom: 4px;
}
.bgt-fill {
  height: 100%;
  border-radius: 99px;
  transition: width .7s cubic-bezier(.16,.5,.3,1);
}
.bgt-amt {
  display: flex;
  justify-content: space-between;
  font-size: 10.5px;
  color: var(--txt-m);
}

/* ─── Tables ─── */
.dtbl { width: 100%; border-collapse: collapse; }
.dtbl thead th {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .09em;
  color: var(--txt-m);
  padding: 10px 18px;
  border-bottom: 1px solid var(--border);
  text-align: left;
  white-space: nowrap;
  background: var(--card-alt);
}
.dtbl thead th.tr { text-align: right; }
.dtbl tbody td {
  font-size: 13px;
  color: var(--txt-b);
  padding: 12px 18px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}
.dtbl tbody td.tr { text-align: right; }
.dtbl tbody tr:last-child td { border-bottom: none; }
.dtbl tbody tr:hover td { background: #f8fafc; }
.dtbl tfoot td {
  font-size: 12px;
  padding: 12px 18px;
  border-top: 1px solid var(--border);
  background: var(--card-alt);
  color: var(--txt-b);
}
.dtbl tfoot td.tr { text-align: right; }
.dtbl a { color: var(--navy); text-decoration: none; font-weight: 600; }
.dtbl a:hover { color: var(--teal); }

.tr-danger td { background: #fff5f7 !important; }
.tr-warn   td { background: #fffbeb !important; }

/* ─── Status badges ─── */
.sbadge {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 9px;
  border-radius: 99px;
  white-space: nowrap;
}
.sbadge-cat     { background: #f1f5f9; color: var(--txt-s); }
.sbadge-overdue { background: var(--rose-lt); color: var(--rose); }
.sbadge-today   { background: var(--rose-lt); color: var(--rose); }
.sbadge-soon    { background: var(--gold-lt); color: var(--gold); }
.sbadge-later   { background: var(--sky-lt);  color: var(--sky); }

/* ─── Reminder sidebar list ─── */
.rem-list { list-style: none; padding: 0; margin: 0; }
.rem-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  padding: 13px 20px;
  border-bottom: 1px solid #f1f5f9;
  transition: background .15s;
}
.rem-item:last-child { border-bottom: none; }
.rem-item:hover { background: var(--card-alt); }
.rem-title { font-size: 13px; font-weight: 600; color: var(--txt-h); }
.rem-sub   { font-size: 10.5px; color: var(--txt-m); margin-top: 2px; }
.rem-amt   {
  font-family: 'JetBrains Mono', monospace;
  font-size: 13px; font-weight: 600;
  color: var(--txt-h); text-align: right;
  white-space: nowrap;
}
.rem-days { font-size: 10px; text-align: right; margin-top: 4px; }

/* ─── Paid action button ─── */
.btn-paid {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: #d1fae5;
  color: #065f46;
  font-size: 11px;
  font-weight: 700;
  padding: 5px 11px;
  border-radius: 7px;
  text-decoration: none;
  transition: background .15s, color .15s;
  white-space: nowrap;
}
.btn-paid:hover { background: #a7f3d0; color: #064e3b; }

/* ─── Payment Report summary strip ─── */
.pay-strip {
  display: grid;
  grid-template-columns: repeat(4,1fr);
  border-top: 1px solid var(--border);
  background: var(--card-alt);
}
.pay-stat {
  padding: 18px 20px;
  text-align: center;
  border-right: 1px solid var(--border);
}
.pay-stat:last-child { border-right: none; }
.pay-val {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 17px;
  font-weight: 800;
  letter-spacing: -.4px;
  line-height: 1;
  margin-bottom: 5px;
}
.pay-lbl {
  font-size: 10px;
  color: var(--txt-m);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .07em;
}

/* ─── Year select ─── */
.yr-sel {
  appearance: none;
  background: var(--card);
  border: 1px solid var(--border);
  color: var(--txt-h);
  font-size: 12px;
  font-weight: 600;
  padding: 5px 26px 5px 11px;
  border-radius: var(--r-sm);
  cursor: pointer;
  font-family: 'DM Sans', sans-serif;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' fill='none' stroke='%2394a3b8' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 7px center;
  transition: border-color .15s;
}
.yr-sel:focus { outline: none; border-color: var(--navy); }

/* ─── Overrides to nullify Bootstrap interference on panels ─── */
.db .table { margin: 0; }
.db .progress { border-radius: 99px; }

/* ─── Responsive ─── */
@media(max-width:520px){
  .pay-strip { grid-template-columns: repeat(2,1fr); }
  .pay-stat:nth-child(2) { border-right: none; }
}
</style>

<!-- ══════════════════════════════════════════════
     BEGIN DASHBOARD WRAPPER
══════════════════════════════════════════════ -->
<div class="db">

<!-- ── Page Header ── -->
<div class="db-topbar">
  <div>
    <div class="db-title"><i class="bi bi-grid-1x2-fill me-2" style="color:var(--teal)"></i>Dashboard Perbelanjaan</div>
    <div class="db-subtitle">Gambaran keseluruhan kewangan IT Department secara real-time</div>
  </div>
  <div class="db-chip">
    <i class="bi bi-calendar3"></i>
    <?= date('d M Y, l') ?>
  </div>
</div>

<!-- ══ KPI CARDS ══ -->
<div class="db-section"><div class="db-section-label">Ringkasan Utama</div><div class="db-section-line"></div></div>
<div class="kpi-grid" style="margin-bottom:22px">

  <div class="kpi-box">
    <div class="kpi-icon-wrap"><i class="bi bi-receipt-cutoff"></i></div>
    <div class="kpi-lbl">Total Rekod</div>
    <div class="kpi-val"><?= number_format($kpi['total_expenses']) ?></div>
    <div class="kpi-foot"><div class="kpi-dot"></div>Semua perbelanjaan dicatat</div>
  </div>

  <div class="kpi-box kpi-sky">
    <div class="kpi-icon-wrap"><i class="bi bi-cash-stack"></i></div>
    <div class="kpi-lbl">Jumlah Keseluruhan</div>
    <div class="kpi-val"><span class="kpi-rm">RM</span><?= number_format($kpi['expenses_total'],2) ?></div>
    <div class="kpi-foot"><div class="kpi-dot"></div>Semua perbelanjaan</div>
  </div>

  <div class="kpi-box kpi-teal">
    <div class="kpi-icon-wrap"><i class="bi bi-check-circle-fill"></i></div>
    <div class="kpi-lbl">Sudah Dibayar</div>
    <div class="kpi-val"><span class="kpi-rm">RM</span><?= number_format($kpi['expenses_paid'],2) ?></div>
    <div class="kpi-foot"><div class="kpi-dot"></div>Status: paid</div>
  </div>

  <div class="kpi-box kpi-rose">
    <div class="kpi-icon-wrap"><i class="bi bi-hourglass-split"></i></div>
    <div class="kpi-lbl">Belum Dibayar</div>
    <div class="kpi-val"><span class="kpi-rm">RM</span><?= number_format($kpi['expenses_unpaid'],2) ?></div>
    <div class="kpi-foot"><div class="kpi-dot"></div>
      <?= $kpi['overdue_count'] ? $kpi['overdue_count'].' rekod overdue' : 'Tiada overdue' ?>
    </div>
  </div>

</div>

<!-- ══ PERIOD CARDS ══ -->
<div class="db-section"><div class="db-section-label">Rekod Mengikut Tempoh</div><div class="db-section-line"></div></div>
<div class="prd-grid" style="margin-bottom:22px">
<?php
$prd_cfg = [
  'harian'   => ['c'=>'#0f2d5a','bg'=>'#dbeafe','icon'=>'bi-sun-fill'],
  'mingguan' => ['c'=>'#0ea5e9','bg'=>'#e0f2fe','icon'=>'bi-calendar-week-fill'],
  'bulanan'  => ['c'=>'#d97706','bg'=>'#fef3c7','icon'=>'bi-calendar-month-fill'],
  'tahunan'  => ['c'=>'#0d9488','bg'=>'#ccfbf1','icon'=>'bi-calendar2-range-fill'],
];
foreach ($periods as $key => $p):
  $s  = $period_stats[$key];
  $c  = $prd_cfg[$key];
?>
  <div class="prd-card" style="--pc:<?= $c['c'] ?>;--pc-bg:<?= $c['bg'] ?>">
    <div class="prd-head">
      <div class="prd-name"><?= $p['label'] ?></div>
      <div class="prd-icon"><i class="bi <?= $c['icon'] ?>"></i></div>
    </div>
    <div class="prd-total"><?= money($s['total']) ?></div>
    <div class="prd-pills">
      <span class="prd-pill paid"><i class="bi bi-check-circle-fill" style="font-size:9px"></i> <?= money($s['paid']) ?></span>
      <span class="prd-pill unpaid"><i class="bi bi-clock-fill" style="font-size:9px"></i> <?= money($s['unpaid']) ?></span>
    </div>
    <div class="prd-count"><i class="bi bi-receipt"></i> <?= $s['count'] ?> rekod</div>
  </div>
<?php endforeach; ?>
</div>



<!-- ══ RECENT + REMINDERS ══ -->
<div class="db-section"><div class="db-section-label">Rekod & Peringatan</div><div class="db-section-line"></div></div>
<div class="row g-3" style="margin-bottom:22px">

  <div class="col-lg-7">
    <div class="panel">
      <div class="panel-head">
        <div class="panel-title">
          <div class="panel-title-icon" style="background:var(--sky-lt);color:var(--sky)"><i class="bi bi-clock-history"></i></div>
          Perbelanjaan Terkini
        </div>
        <a href="<?= APP_URL ?>/expenses/index.php" class="panel-link">Lihat semua →</a>
      </div>
      <div style="overflow-x:auto">
        <table class="dtbl">
          <thead>
            <tr><th>Tajuk</th><th>Kategori</th><th>Tarikh</th><th class="tr">Jumlah</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recent_exp as $r): ?>
            <tr>
              <td><a href="<?= APP_URL ?>/expenses/view.php?id=<?= $r['id'] ?>"><?= e($r['title']) ?></a></td>
              <td><span class="sbadge sbadge-cat"><?= e($r['cat_name'] ?: '—') ?></span></td>
              <td style="color:var(--txt-m);font-size:12px"><?= fmt_date($r['expense_date']) ?></td>
              <td class="tr" style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--navy)"><?= money($r['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(!$recent_exp): ?>
              <tr><td colspan="4" style="text-align:center;color:var(--txt-m);padding:36px">Tiada rekod</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="panel">
      <div class="panel-head">
        <div class="panel-title">
          <div class="panel-title-icon" style="background:var(--rose-lt);color:var(--rose)"><i class="bi bi-bell-fill"></i></div>
          Perlu Dibayar
          <?php if($kpi['overdue_count']): ?>
            <span style="background:var(--rose);color:#fff;font-size:9.5px;font-weight:700;padding:1px 7px;border-radius:99px;margin-left:2px"><?= $kpi['overdue_count'] ?></span>
          <?php endif; ?>
        </div>
        <a href="<?= APP_URL ?>/expenses/index.php?status=unpaid" class="panel-link">Lihat semua →</a>
      </div>
      <ul class="rem-list">
        <?php foreach(array_slice($exp_reminders,0,5) as $o):
          $days = (int)ceil((strtotime($o['due_date']) - strtotime(date('Y-m-d'))) / 86400);
        ?>
        <li class="rem-item">
          <div>
            <div class="rem-title"><?= e($o['title']) ?></div>
            <div class="rem-sub"><?= e($o['vendor'] ?: '—') ?> · <?= fmt_date($o['due_date']) ?></div>
          </div>
          <div>
            <div class="rem-amt" style="<?= $days < 0 ? 'color:var(--rose)' : '' ?>"><?= money($o['amount']) ?></div>
            <div class="rem-days">
              <?php if ($days < 0): ?>
                <span class="sbadge sbadge-overdue">Overdue <?= abs($days) ?>h</span>
              <?php elseif ($days === 0): ?>
                <span class="sbadge sbadge-today">Hari ini!</span>
              <?php elseif ($days <= 7): ?>
                <span class="sbadge sbadge-soon"><?= $days ?>h lagi</span>
              <?php else: ?>
                <span class="sbadge sbadge-later"><?= $days ?>h lagi</span>
              <?php endif; ?>
            </div>
          </div>
        </li>
        <?php endforeach; ?>
        <?php if(!$exp_reminders): ?>
          <li style="text-align:center;padding:36px;color:var(--txt-m)">
            <i class="bi bi-check-circle-fill d-block mb-2" style="font-size:26px;color:var(--teal);opacity:.6"></i>
            Tiada bayaran tertunggak
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

</div>

<!-- ══ FULL REMINDERS TABLE ══ -->
<?php if($exp_reminders): ?>
<div class="db-section"><div class="db-section-label">Senarai Penuh Tertunggak</div><div class="db-section-line"></div></div>
<div class="row g-3" style="margin-bottom:22px">
  <div class="col-12">
    <div class="panel">
      <div class="panel-head">
        <div class="panel-title">
          <div class="panel-title-icon" style="background:var(--gold-lt);color:var(--gold)"><i class="bi bi-alarm-fill"></i></div>
          Expense Due Payment Reminders
          <span style="background:var(--rose);color:#fff;font-size:9.5px;font-weight:700;padding:1px 7px;border-radius:99px;margin-left:2px"><?= count($exp_reminders) ?></span>
        </div>
        <a href="<?= APP_URL ?>/expenses/index.php" class="panel-link">Lihat semua →</a>
      </div>
      <div style="overflow-x:auto">
        <table class="dtbl">
          <thead>
            <tr>
              <th>Vendor</th><th>Perkara</th><th>Kategori</th>
              <th>Due Date</th><th>Masa Tinggal</th>
              <th class="tr">Jumlah (RM)</th><th class="tr">Tindakan</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($exp_reminders as $er):
            $days = (int)ceil((strtotime($er['due_date']) - strtotime(date('Y-m-d'))) / 86400);
            $rc   = $days < 0 ? 'tr-danger' : ($days <= 7 ? 'tr-warn' : '');
          ?>
            <tr class="<?= $rc ?>">
              <td style="font-weight:600;color:var(--txt-h)"><?= e($er['vendor'] ?: '—') ?></td>
              <td><?= e($er['title']) ?></td>
              <td><span class="sbadge sbadge-cat"><?= e($er['cat_name']) ?></span></td>
              <td style="font-size:12px;color:var(--txt-s)"><?= fmt_date($er['due_date']) ?></td>
              <td>
                <?php if ($days < 0): ?>
                  <span class="sbadge sbadge-overdue">🚨 Overdue <?= abs($days) ?> hari</span>
                <?php elseif ($days === 0): ?>
                  <span class="sbadge sbadge-today">🔴 Hari ini!</span>
                <?php elseif ($days <= 7): ?>
                  <span class="sbadge sbadge-soon">⚠️ <?= $days ?> hari lagi</span>
                <?php else: ?>
                  <span class="sbadge sbadge-later"><?= $days ?> hari lagi</span>
                <?php endif; ?>
              </td>
              <td class="tr" style="font-family:'JetBrains Mono',monospace;font-weight:700;<?= $days < 0 ? 'color:var(--rose)' : 'color:var(--navy)' ?>"><?= money($er['amount']) ?></td>
              <td class="tr">
                <a href="<?= APP_URL ?>/expenses/mark_paid.php?id=<?= $er['id'] ?>"
                   onclick="return confirm('Tandakan sebagai Paid?')"
                   class="btn-paid"><i class="bi bi-check-lg"></i> Paid</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="5" style="text-align:right;font-weight:700;color:var(--txt-b)">Jumlah Tertunggak:</td>
              <td class="tr" style="font-family:'JetBrains Mono',monospace;font-weight:800;color:var(--rose)"><?= money(array_sum(array_column($exp_reminders,'amount'))) ?></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>



<!-- ══════════════════════════════════════════════
     CHART.JS – STYLED FOR PREMIUM THEME
══════════════════════════════════════════════ -->
<script>
Chart.defaults.font.family = "'DM Sans', system-ui, sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#94a3b8';
Chart.defaults.borderColor = '#e2e8f0';

const mkTooltip = () => ({
  backgroundColor: '#0f2d5a',
  titleColor: '#fff',
  bodyColor: '#94a3b8',
  borderColor: 'rgba(255,255,255,.1)',
  borderWidth: 1,
  padding: 10,
  cornerRadius: 8,
  callbacks: {
    label: ctx => '  RM ' + ctx.parsed.y.toLocaleString('en-MY', {minimumFractionDigits:2})
  }
});

const mkScales = () => ({
  x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
  y: {
    beginAtZero: true,
    grid: { color: '#f1f5f9' },
    ticks: {
      color: '#94a3b8',
      callback: v => 'RM ' + (v >= 1000 ? (v/1000).toFixed(v%1000===0?0:1)+'k' : v)
    }
  }
});

/* ── Daily chart ── */
const dailyEl = document.getElementById('dailyChart');
const dailyGrad = dailyEl.getContext('2d').createLinearGradient(0,0,0,240);
dailyGrad.addColorStop(0,'rgba(15,45,90,.75)');
dailyGrad.addColorStop(1,'rgba(15,45,90,.08)');

const dailyChart = new Chart(dailyEl, {
  type: 'bar',
  data: {
    labels: <?= json_encode($daily_labels) ?>,
    datasets: [{
      label: 'Perbelanjaan (RM)',
      data: <?= json_encode($daily_data) ?>,
      backgroundColor: dailyGrad,
      borderColor: '#0f2d5a',
      borderWidth: 1,
      borderRadius: 7,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false }, tooltip: mkTooltip() },
    scales: mkScales()
  }
});

/* ── Monthly chart ── */
const monthlyEl = document.getElementById('monthlyChart');
const monthGrad = monthlyEl.getContext('2d').createLinearGradient(0,0,0,240);
monthGrad.addColorStop(0,'rgba(14,165,233,.8)');
monthGrad.addColorStop(1,'rgba(14,165,233,.08)');

const monthlyChart = new Chart(monthlyEl, {
  type: 'bar',
  data: {
    labels: <?= json_encode($months) ?>,
    datasets: [{
      label: 'Perbelanjaan (RM)',
      data: <?= json_encode($exp) ?>,
      backgroundColor: monthGrad,
      borderColor: '#0ea5e9',
      borderWidth: 1,
      borderRadius: 7,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false }, tooltip: mkTooltip() },
    scales: mkScales()
  }
});

/* ── Year chart ── */
new Chart(document.getElementById('yearChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($year_months) ?>,
    datasets: [
      {
        label: 'Invoice Value',
        data: <?= json_encode($year_invoice) ?>,
        backgroundColor: 'rgba(217,119,6,.75)',
        borderColor: '#d97706',
        borderWidth: 1,
        borderRadius: 5,
        borderSkipped: false,
      },
      {
        label: 'Payments Received',
        data: <?= json_encode($year_paid) ?>,
        backgroundColor: 'rgba(13,148,136,.75)',
        borderColor: '#0d9488',
        borderWidth: 1,
        borderRadius: 5,
        borderSkipped: false,
      }
    ]
  },
  options: {
    responsive: true,
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          color: '#64748b',
          boxWidth: 10, boxHeight: 10,
          borderRadius: 3, useBorderRadius: true,
          padding: 18,
          font: { weight: '600', size: 11 }
        }
      },
      tooltip: mkTooltip()
    },
    scales: {
      ...mkScales(),
      y: {
        ...mkScales().y,
        title: {
          display: true,
          text: 'Nilai (RM)',
          color: '#94a3b8',
          font: { size: 10, weight: '600' }
        }
      }
    }
  }
});

/* ── Tab switch ── */
function switchChart(type, btn) {
  document.querySelectorAll('.chart-switch-btn').forEach(b => b.classList.remove('on'));
  btn.classList.add('on');
  dailyEl.style.display   = type === 'daily'   ? '' : 'none';
  monthlyEl.style.display = type === 'monthly' ? '' : 'none';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
