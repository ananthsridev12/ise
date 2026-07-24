<?php
/**
 * One-time setup: creates the first platform admin.
 * DELETE THIS FILE after use.
 */
require_once __DIR__ . '/../config.php';

$existing = DB::fetchOne('SELECT COUNT(*) as c FROM platform_admins');
if ($existing && $existing['c'] > 0) {
    die('<p style="font-family:sans-serif;padding:40px">Setup already complete. Delete this file.</p>');
}

$done  = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';
    if (!$email || !$password) { $error = 'Email and password required.'; }
    elseif ($password !== $confirm) { $error = 'Passwords do not match.'; }
    elseif (strlen($password) < 8)  { $error = 'Password must be at least 8 characters.'; }
    else {
        DB::insert('platform_admins', ['email' => $email, 'password_hash' => Auth::hashPassword($password)]);
        $done = true;
    }
}
?><!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Platform Setup</title>
<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#0f1117;margin:0}
.box{background:#1a1d27;border:1px solid #2a2d3a;border-radius:12px;padding:36px;width:360px;color:#fff}
h2{margin:0 0 8px}p{color:#9ca3af;font-size:13px;margin:0 0 24px}
label{display:block;font-size:12px;color:#9ca3af;margin-bottom:5px}
input{width:100%;background:#0f1117;border:1px solid #2a2d3a;border-radius:8px;color:#fff;padding:10px 12px;font-size:14px;box-sizing:border-box;margin-bottom:14px}
button{width:100%;background:#4f46e5;color:#fff;border:none;border-radius:8px;padding:11px;font-size:14px;font-weight:600;cursor:pointer}
.err{background:rgba(239,68,68,.1);border:1px solid #ef4444;border-radius:8px;color:#f87171;font-size:13px;padding:10px;margin-bottom:16px}
.ok{background:rgba(34,197,94,.1);border:1px solid #22c55e;border-radius:8px;color:#4ade80;font-size:13px;padding:10px;margin-bottom:16px}
</style></head>
<body>
<div class="box">
<?php if ($done): ?>
  <h2>&#10003; Done</h2>
  <div class="ok">Platform admin created. <strong>Delete this file now.</strong><br>Then go to <a href="/platform/login.php" style="color:#818cf8">/platform/login.php</a></div>
<?php else: ?>
  <h2>Platform Setup</h2>
  <p>Create the platform super-admin account. Delete this file after use.</p>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <label>Email</label><input type="email" name="email" required>
    <label>Password</label><input type="password" name="password" required>
    <label>Confirm Password</label><input type="password" name="confirm" required>
    <button type="submit">Create Platform Admin</button>
  </form>
<?php endif; ?>
</div>
</body></html>
