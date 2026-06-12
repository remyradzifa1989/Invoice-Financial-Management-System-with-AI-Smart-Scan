<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) { header('Location: dashboard.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'E-mel yang dimasukkan tidak sah.';
    } elseif (attempt_login($email, $pass)) {
        header('Location: dashboard.php'); exit;
    } else {
        $err = 'E-mel atau kata laluan tidak betul.';
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Log Masuk &middot; <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root{
  --navy:       #0C1221;
  --navy-2:     #121B32;
  --navy-3:     #192340;
  --accent:     #4F46E5;
  --accent-dim: rgba(79,70,229,0.14);
  --accent-glo: rgba(79,70,229,0.35);
  --violet:     #818CF8;
  --vio-pale:   #C7D2FE;
  --white:      #FFFFFF;
  --form-bg:    #F7F9FF;
  --slate:      #64748B;
  --slate-lt:   #94A3B8;
  --glass:      rgba(255,255,255,0.055);
  --glass-brd:  rgba(255,255,255,0.1);
  --divider:    #E4E9F2;
  --ok:         #10B981;
  --ok-bg:      rgba(16,185,129,0.13);
  --warn:       #F59E0B;
  --warn-bg:    rgba(245,158,11,0.13);
  --gray-bg:    rgba(100,116,139,0.13);
  --danger:     #DC2626;
  --fd:'Plus Jakarta Sans',system-ui,sans-serif;
  --fb:'Inter',system-ui,sans-serif;
}

html,body{height:100%;font-family:var(--fb)}

body{
  display:grid;
  grid-template-columns:44% 1fr;
  min-height:100vh;
  background:var(--navy);
}

/* ══════════════════════════════
   BRAND PANEL
══════════════════════════════ */
.brand{
  position:relative;
  background:linear-gradient(148deg,var(--navy) 0%,var(--navy-2) 50%,var(--navy-3) 100%);
  display:flex;flex-direction:column;
  padding:2.4rem 2rem 2.4rem 2.5rem;
  overflow:hidden;
}

/* Ambient glow layers */
.brand::before{
  content:'';position:absolute;inset:0;
  background:
    radial-gradient(ellipse 70% 55% at 15% 22%,rgba(79,70,229,0.22) 0%,transparent 65%),
    radial-gradient(ellipse 50% 45% at 88% 78%,rgba(129,140,248,0.13) 0%,transparent 55%);
  pointer-events:none;z-index:0;
}

/* Bottom vignette — protects hero text legibility */
.brand::after{
  content:'';position:absolute;
  left:0;right:0;bottom:0;height:230px;
  background:linear-gradient(to top,var(--navy) 0%,transparent 100%);
  pointer-events:none;z-index:1;
}

/* ── Logo ── */
.b-logo{
  position:relative;z-index:5;
  display:flex;align-items:center;gap:.65rem;
  text-decoration:none;flex-shrink:0;
}
.b-logo-icon{
  width:42px;height:42px;border-radius:11px;
  background:linear-gradient(135deg,var(--accent) 0%,var(--violet) 100%);
  display:flex;align-items:center;justify-content:center;
  font-size:1.25rem;color:var(--white);
  box-shadow:0 4px 18px var(--accent-glo);
  flex-shrink:0;
}
.b-logo-name{
  font-family:var(--fd);
  font-size:1.45rem;font-weight:800;
  color:var(--white);letter-spacing:-.035em;
}

/* ── Cards stage ── */
.stage{
  position:absolute;inset:0;
  z-index:2;pointer-events:none;
}

/* Revenue stat card */
.stat-card{
  position:absolute;top:17%;right:1.5rem;
  background:var(--glass);
  backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
  border:1px solid var(--glass-brd);
  border-radius:15px;padding:1rem 1.3rem;
  box-shadow:0 12px 44px rgba(0,0,0,0.28),0 1px 0 rgba(255,255,255,0.07) inset;
  animation:fA 4.2s ease-in-out infinite alternate;
}
.s-lbl{
  font-size:.62rem;font-weight:600;text-transform:uppercase;
  letter-spacing:.1em;color:var(--slate-lt);margin-bottom:.3rem;
}
.s-val{
  font-family:var(--fd);font-size:1.38rem;font-weight:800;
  color:var(--white);letter-spacing:-.04em;
  line-height:1;margin-bottom:.38rem;
}
.s-trend{
  display:flex;align-items:center;gap:4px;
  font-size:.68rem;color:var(--ok);font-weight:600;
}

/* Invoice cards */
.inv{
  position:absolute;
  background:var(--glass);
  backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
  border:1px solid var(--glass-brd);
  border-radius:14px;padding:.95rem 1.15rem;min-width:195px;
  box-shadow:0 18px 52px rgba(0,0,0,0.3),0 1px 0 rgba(255,255,255,0.08) inset;
}
.inv-a{top:34%;left:.75rem; animation:fB 5.6s ease-in-out infinite alternate;}
.inv-b{top:50%;right:.75rem;animation:fC 6.3s ease-in-out 1.2s infinite alternate;}
.inv-c{top:64%;left:2rem;   animation:fA 5.1s ease-in-out .7s infinite alternate;}

.i-num{
  font-size:.6rem;font-weight:600;letter-spacing:.09em;
  text-transform:uppercase;color:var(--slate-lt);margin-bottom:.28rem;
}
.i-cl{
  font-family:var(--fd);font-size:.82rem;font-weight:700;
  color:var(--white);margin-bottom:.5rem;white-space:nowrap;
}
.i-row{display:flex;align-items:center;justify-content:space-between;gap:.75rem;}
.i-amt{
  font-family:var(--fd);font-size:1.05rem;font-weight:800;
  color:var(--white);letter-spacing:-.03em;
}
.badge{
  font-size:.57rem;font-weight:700;text-transform:uppercase;
  letter-spacing:.07em;padding:3px 8px;border-radius:99px;
}
.b-paid   {background:var(--ok-bg);color:var(--ok);}
.b-pending{background:var(--warn-bg);color:var(--warn);}
.b-draft  {background:var(--gray-bg);color:var(--slate-lt);}

/* ── Hero text ── */
.b-hero{position:relative;z-index:5;margin-top:auto;margin-bottom:1.2rem;}
.b-eye{
  font-size:.66rem;font-weight:700;text-transform:uppercase;
  letter-spacing:.14em;color:var(--violet);margin-bottom:.7rem;
}
.b-h1{
  font-family:var(--fd);
  font-size:clamp(1.7rem,2.7vw,2.45rem);font-weight:800;
  color:var(--white);line-height:1.14;letter-spacing:-.045em;
  margin-bottom:.8rem;
}
.b-h1 em{
  font-style:normal;
  background:linear-gradient(90deg,var(--vio-pale),var(--violet));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
  background-clip:text;
}
.b-desc{font-size:.84rem;color:var(--slate-lt);line-height:1.65;max-width:30ch;}
.b-foot{position:relative;z-index:5;font-size:.66rem;color:rgba(148,163,184,.4);}

/* ══════════════════════════════
   ANIMATIONS
══════════════════════════════ */
@keyframes fA{from{transform:translateY(0)}to{transform:translateY(-12px)}}
@keyframes fB{from{transform:translateY(0) rotate(-.8deg)}to{transform:translateY(-10px) rotate(.4deg)}}
@keyframes fC{from{transform:translateY(0) rotate(.4deg)}to{transform:translateY(-14px) rotate(-.5deg)}}
@keyframes slideIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}

/* ══════════════════════════════
   FORM PANEL
══════════════════════════════ */
.form-panel{
  background:var(--white);
  display:flex;align-items:center;justify-content:center;
  padding:3rem 2rem;
}
.form-box{width:100%;max-width:385px;animation:slideIn .4s ease both;}

.f-h{
  font-family:var(--fd);font-size:1.8rem;font-weight:800;
  color:#0F172A;letter-spacing:-.045em;margin-bottom:.3rem;
}
.f-sub{font-size:.875rem;color:var(--slate);margin-bottom:2rem;}

/* Error */
.f-err{
  display:flex;align-items:center;gap:.5rem;
  background:#FEF2F2;border:1.5px solid #FECACA;
  color:var(--danger);font-size:.83rem;font-weight:500;
  padding:.75rem 1rem;border-radius:10px;margin-bottom:1.25rem;
}

/* Fields */
.field{margin-bottom:1.2rem;}
.field label{display:block;font-size:.8rem;font-weight:600;color:#334155;margin-bottom:.4rem;}
.iw{position:relative;}
.iw .ic{
  position:absolute;left:.85rem;top:50%;transform:translateY(-50%);
  color:var(--slate-lt);font-size:.95rem;pointer-events:none;z-index:1;
}
.iw input{
  display:block;width:100%;height:46px;
  padding:0 1rem 0 2.5rem;
  border:1.5px solid var(--divider);border-radius:10px;
  font-size:.9rem;font-family:var(--fb);color:#0F172A;
  background:#FAFBFF;outline:none;
  transition:border-color .18s,box-shadow .18s,background .18s;
}
.iw input:focus{border-color:var(--accent);background:var(--white);box-shadow:0 0 0 3px var(--accent-dim);}
.iw input::placeholder{color:#CBD5E1;}

/* Password toggle */
.pw-btn{
  position:absolute;right:.75rem;top:50%;transform:translateY(-50%);
  background:none;border:none;cursor:pointer;
  color:var(--slate-lt);font-size:.9rem;
  line-height:1;padding:4px;border-radius:4px;
  transition:color .15s;display:flex;align-items:center;justify-content:center;
}
.pw-btn:hover{color:var(--accent);}

/* Submit */
.f-btn{
  display:flex;align-items:center;justify-content:center;gap:.4rem;
  width:100%;height:48px;
  background:linear-gradient(135deg,var(--accent) 0%,#6366F1 100%);
  color:var(--white);border:none;border-radius:10px;
  font-family:var(--fd);font-size:.95rem;font-weight:700;letter-spacing:-.01em;
  cursor:pointer;box-shadow:0 4px 18px var(--accent-glo);
  transition:opacity .18s,transform .18s,box-shadow .18s;margin-top:.25rem;
}
.f-btn:hover{opacity:.92;transform:translateY(-1px);box-shadow:0 6px 24px rgba(79,70,229,.46);}
.f-btn:active{transform:translateY(0);box-shadow:0 2px 10px var(--accent-glo);}

/* Demo credentials */
.demo{margin-top:1.75rem;padding:.95rem 1rem;background:var(--form-bg);border:1.5px dashed #C8D1E0;border-radius:12px;}
.demo-ttl{
  display:flex;align-items:center;gap:5px;
  font-size:.66rem;font-weight:700;text-transform:uppercase;
  letter-spacing:.1em;color:var(--slate);margin-bottom:.65rem;
}
.demo-row{
  display:flex;align-items:center;justify-content:space-between;
  padding:.42rem .5rem;border-radius:8px;cursor:pointer;
  transition:background .15s;margin-bottom:.25rem;
}
.demo-row:last-child{margin-bottom:0;}
.demo-row:hover{background:rgba(79,70,229,.07);}
.demo-lft{display:flex;align-items:center;gap:.55rem;}
.demo-av{
  width:28px;height:28px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:.62rem;font-weight:800;color:var(--white);flex-shrink:0;
}
.av-a{background:linear-gradient(135deg,var(--accent),var(--violet));}
.av-s{background:linear-gradient(135deg,#0EA5E9,#38BDF8);}
.d-name{font-size:.77rem;font-weight:600;color:#334155;}
.d-cred{font-size:.67rem;color:var(--slate);margin-top:1px;}
.demo-chip{
  font-size:.62rem;font-weight:700;color:var(--accent);
  background:var(--accent-dim);padding:2px 8px;border-radius:99px;
  opacity:0;transition:opacity .15s;white-space:nowrap;
}
.demo-row:hover .demo-chip{opacity:1;}

/* ══════════════════════════════
   RESPONSIVE
══════════════════════════════ */
@media(max-width:767px){
  body{display:block;background:var(--white);}
  .brand{display:none;}
  .form-panel{min-height:100vh;padding:3rem 1.5rem;align-items:flex-start;}
}
@media(prefers-reduced-motion:reduce){
  .stat-card,.inv,.f-btn{animation:none!important;transition:none!important;}
}
</style>
</head>
<body>

<!-- ─── Brand Panel ─── -->
<aside class="brand">
  <a class="b-logo" href="#">
    <div class="b-logo-icon"><i class="bi bi-receipt-cutoff"></i></div>
    <span class="b-logo-name">Invois</span>
  </a>

  <div class="stage">
    <!-- Revenue stat -->
    <div class="stat-card">
      <div class="s-lbl">Hasil bulan ini</div>
      <div class="s-val">RM 98,420</div>
      <div class="s-trend"><i class="bi bi-arrow-up-right"></i>&nbsp;+12.4% vs bulan lalu</div>
    </div>

    <!-- Invoice A -->
    <div class="inv inv-a">
      <div class="i-num">#INV-2024-041</div>
      <div class="i-cl">Syarikat ABC Sdn Bhd</div>
      <div class="i-row">
        <span class="i-amt">RM 4,200</span>
        <span class="badge b-paid">Dibayar</span>
      </div>
    </div>

    <!-- Invoice B -->
    <div class="inv inv-b">
      <div class="i-num">#INV-2024-042</div>
      <div class="i-cl">Tech Solutions Bhd</div>
      <div class="i-row">
        <span class="i-amt">RM 12,800</span>
        <span class="badge b-pending">Tertangguh</span>
      </div>
    </div>

    <!-- Invoice C -->
    <div class="inv inv-c">
      <div class="i-num">#INV-2024-043</div>
      <div class="i-cl">Kreatif Design Studio</div>
      <div class="i-row">
        <span class="i-amt">RM 6,500</span>
        <span class="badge b-draft">Draf</span>
      </div>
    </div>
  </div>

  <div class="b-hero">
    <div class="b-eye">Sistem Pengurusan Kewangan</div>
    <h1 class="b-h1">Urus invois<br>dengan <em>lebih bijak</em></h1>
    <p class="b-desc">Pantau, hantar, dan jejak pembayaran dalam satu platform yang teratur dan mudah digunakan.</p>
  </div>

  <p class="b-foot">&copy; <?= date('Y') ?> Invois. Semua hak terpelihara.</p>
</aside>

<!-- ─── Form Panel ─── -->
<main class="form-panel">
  <div class="form-box">

    <h2 class="f-h">Selamat kembali &#128075;</h2>
    <p class="f-sub">Log masuk ke akaun anda untuk meneruskan</p>

    <?php if ($err): ?>
    <div class="f-err">
      <i class="bi bi-exclamation-circle-fill"></i>
      <?= e($err) ?>
    </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <?= csrf_field() ?>

      <div class="field">
        <label for="email">Emel</label>
        <div class="iw">
          <i class="bi bi-envelope ic"></i>
          <input id="email" name="email" type="email"
                 placeholder="nama@syarikat.com" required
                 value="<?= e($_POST['email'] ?? '') ?>">
        </div>
      </div>

      <div class="field">
        <label for="password">Kata Laluan</label>
        <div class="iw">
          <i class="bi bi-lock ic"></i>
          <input id="password" name="password" type="password"
                 placeholder="Masukkan kata laluan" required>
          <button type="button" class="pw-btn" id="pwBtn" aria-label="Tunjuk/sembunyi kata laluan">
            <i class="bi bi-eye" id="pwIco"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="f-btn">
        <i class="bi bi-box-arrow-in-right"></i>
        Log Masuk
      </button>
    </form>

    <!-- Demo credentials -->
    <div class="demo">
      <div class="demo-ttl"><i class="bi bi-info-circle"></i>&nbsp;Akaun Demo</div>

      <div class="demo-row" onclick="useDemo('admin@invois.com','admin123')">
        <div class="demo-lft">
          <div class="demo-av av-a">A</div>
          <div>
            <div class="d-name">Pentadbir</div>
            <div class="d-cred">admin@invois.com &middot; admin123</div>
          </div>
        </div>
        <span class="demo-chip">Guna &rarr;</span>
      </div>

      <div class="demo-row" onclick="useDemo('staff@invois.com','admin123')">
        <div class="demo-lft">
          <div class="demo-av av-s">S</div>
          <div>
            <div class="d-name">Staf</div>
            <div class="d-cred">staff@invois.com &middot; admin123</div>
          </div>
        </div>
        <span class="demo-chip">Guna &rarr;</span>
      </div>
    </div>

  </div>
</main>

<script>
// Password show / hide
const pwBtn = document.getElementById('pwBtn');
const pwInp = document.getElementById('password');
const pwIco = document.getElementById('pwIco');

pwBtn.addEventListener('click', () => {
  const show = pwInp.type === 'password';
  pwInp.type = show ? 'text' : 'password';
  pwIco.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
  pwInp.focus();
});

// Auto-fill demo credentials on click
function useDemo(email, pass) {
  document.getElementById('email').value = email;
  pwInp.value = pass;
  pwInp.type = 'password';
  pwIco.className = 'bi bi-eye';
}
</script>
</body>
</html>
