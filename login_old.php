<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) { header('Location: dashboard.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Invalid email';
    elseif (attempt_login($email, $pass)) { header('Location: dashboard.php'); exit; }
    else $err = 'Invalid credentials';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login · <?= e(APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-shell">
  <div class="login-card">
    <div class="login-brand">
      <i class="bi bi-receipt-cutoff"></i>
      <h3>Invois</h3>
      <p class="text-muted small mb-0">Financial Management System</p>
    </div>
    <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input name="email" type="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input name="password" type="password" class="form-control" required>
      </div>
      <button class="btn btn-primary w-100 py-2 fw-semibold">Sign in</button>
    </form>
    <div class="mt-4 p-3 rounded bg-light small">
      <strong>Demo:</strong><br>
      Admin: admin@invois.com / admin123<br>
      Staff: staff@invois.com / admin123
    </div>
  </div>
</div>
</body></html>
