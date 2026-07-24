<?php
require_once __DIR__ . '/../config.php';

if (Auth::isPlatformAdmin()) { header('Location: /platform/tenants.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Auth::platformLogin($_POST['email'] ?? '', $_POST['password'] ?? '')) {
        header('Location: /platform/tenants.php');
        exit;
    }
    $error = 'Invalid credentials.';
}
?><!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Platform Login — ISE</title>
<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#0f1117;margin:0}
.box{background:#1a1d27;border:1px solid #2a2d3a;border-radius:12px;padding:36px;width:340px;color:#fff}
h2{margin:0 0 6px}p{color:#9ca3af;font-size:12px;margin:0 0 24px}
label{display:block;font-size:12px;color:#9ca3af;margin-bottom:5px}
input{width:100%;background:#0f1117;border:1px solid #2a2d3a;border-radius:8px;color:#fff;padding:10px 12px;font-size:14px;box-sizing:border-box;margin-bottom:14px}
button{width:100%;background:#7c3aed;color:#fff;border:none;border-radius:8px;padding:11px;font-size:14px;font-weight:600;cursor:pointer}
.err{background:rgba(239,68,68,.1);border:1px solid #ef4444;border-radius:8px;color:#f87171;font-size:13px;padding:10px;margin-bottom:14px}
</style></head>
<body>
<div class="box">
  <h2>&#9670; Platform Admin</h2>
  <p>ISE — Super admin access</p>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <label>Email</label><input type="email" name="email" required autofocus>
    <label>Password</label><input type="password" name="password" required>
    <button type="submit">Sign in</button>
  </form>
</div>
</body></html>
