<?php
require_once __DIR__ . '/config.php';

// Already logged in — redirect
if (Auth::session()) {
    header('Location: /index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (Auth::login($email, $password)) {
        $redirect = $_GET['redirect'] ?? '/index.php';
        header('Location: ' . $redirect);
        exit;
    }
    $error = 'Invalid email or password.';
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in — ISE</title>
<link rel="stylesheet" href="/assets/css/app.css">
<style>
.login-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; background:var(--bg); }
.login-box { background:var(--card); border:1px solid var(--border); border-radius:14px; padding:40px 36px; width:360px; box-shadow:0 8px 32px rgba(0,0,0,.35); }
.login-logo { font-weight:700; font-size:20px; color:var(--text); margin-bottom:4px; }
.login-sub  { font-size:13px; color:var(--muted); margin-bottom:28px; }
.login-label { display:block; font-size:12px; color:var(--muted); font-weight:500; margin-bottom:5px; }
.login-input { width:100%; background:var(--bg); border:1px solid var(--border); border-radius:8px; color:var(--text); padding:10px 12px; font-size:14px; outline:none; box-sizing:border-box; margin-bottom:16px; }
.login-btn { width:100%; background:#4f46e5; color:#fff; border:none; border-radius:8px; padding:11px; font-size:14px; font-weight:600; cursor:pointer; margin-top:4px; }
.login-btn:hover { background:#4338ca; }
.login-error { background:rgba(239,68,68,.1); border:1px solid #ef4444; border-radius:8px; color:#f87171; font-size:13px; padding:10px 14px; margin-bottom:16px; }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <div class="login-logo">&#9889; ISE</div>
    <div class="login-sub">Intent Signal Engine &mdash; Sign in</div>
    <?php if ($error): ?>
    <div class="login-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <label class="login-label">Email</label>
      <input class="login-input" type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus placeholder="you@company.com">
      <label class="login-label">Password</label>
      <input class="login-input" type="password" name="password" required placeholder="••••••••">
      <button class="login-btn" type="submit">Sign in</button>
    </form>
  </div>
</div>
</body>
</html>
