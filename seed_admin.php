<?php
/**
 * One-time setup: creates the first SolidPro (tenant_id=1) admin user.
 * DELETE THIS FILE after use.
 */
require_once __DIR__ . '/config.php';

$existing = DB::fetchOne('SELECT COUNT(*) as c FROM users WHERE tenant_id = 1');
if ($existing && $existing['c'] > 0) {
    die('<p style="font-family:sans-serif;padding:40px;color:#fff;background:#0f1117;min-height:100vh;margin:0">Admin already seeded for this tenant. <strong>Delete this file.</strong></p>');
}

$done  = false;
$error = '';
$seededEmail = '';
$seededName  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dname   = trim($_POST['display_name'] ?? '');
    $email   = strtolower(trim($_POST['email'] ?? ''));
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm']  ?? '';

    if (!$dname || !$email || !$pass) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        DB::insert('users', [
            'tenant_id'     => 1,
            'email'         => $email,
            'password_hash' => Auth::hashPassword($pass),
            'display_name'  => $dname,
            'role'          => 'admin',
            'active'        => 1,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $seededEmail = $email;
        $seededName  = $dname;
        $done = true;
    }
}
?><!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Seed Admin — ISE</title>
<style>
body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#0f1117;margin:0}
.box{background:#1a1d27;border:1px solid #2a2d3a;border-radius:12px;padding:36px;width:400px;color:#fff}
h2{margin:0 0 8px}
p{color:#9ca3af;font-size:13px;margin:0 0 24px}
label{display:block;font-size:12px;color:#9ca3af;margin-bottom:5px}
input{width:100%;background:#0f1117;border:1px solid #2a2d3a;border-radius:8px;color:#fff;padding:10px 12px;font-size:14px;box-sizing:border-box;margin-bottom:14px}
button{width:100%;background:#4f46e5;color:#fff;border:none;border-radius:8px;padding:11px;font-size:14px;font-weight:600;cursor:pointer}
.err{background:rgba(239,68,68,.1);border:1px solid #ef4444;border-radius:8px;color:#f87171;font-size:13px;padding:10px;margin-bottom:16px}
.ok{background:rgba(34,197,94,.1);border:1px solid #22c55e;border-radius:8px;color:#4ade80;font-size:13px;padding:10px;margin-bottom:16px}
.warn{background:rgba(251,191,36,.1);border:1px solid #fbbf24;border-radius:8px;color:#fcd34d;font-size:13px;padding:12px;margin-top:16px;font-weight:600}
</style></head>
<body>
<div class="box">
<?php if ($done): ?>
  <h2>&#10003; Admin Created</h2>
  <div class="ok">
    <strong><?= htmlspecialchars($seededName) ?></strong> (<?= htmlspecialchars($seededEmail) ?>) added as admin for SolidPro (tenant_id=1).<br><br>
    You can now <a href="/login.php" style="color:#818cf8">sign in at /login.php</a>.
  </div>
  <div class="warn">&#9888; DELETE THIS FILE IMMEDIATELY.<br>Anyone who visits it could see this message. Remove <code>seed_admin.php</code> from the server.</div>
<?php else: ?>
  <h2>Seed Admin User</h2>
  <p>Creates the first admin user for SolidPro (tenant_id=1). Delete this file after use.</p>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <label>Display Name</label><input type="text" name="display_name" required placeholder="Jane Smith" value="<?= htmlspecialchars($_POST['display_name'] ?? '') ?>">
    <label>Email</label><input type="email" name="email" required placeholder="admin@solidpro.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    <label>Password</label><input type="password" name="password" required placeholder="Min 8 characters">
    <label>Confirm Password</label><input type="password" name="confirm" required placeholder="Repeat password">
    <button type="submit">Create Admin User</button>
  </form>
<?php endif; ?>
</div>
</body></html>
