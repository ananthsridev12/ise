<?php
require_once __DIR__ . '/../config.php';
Auth::requirePlatformLogin();

$tenantId = (int)($_GET['tenant_id'] ?? 0);
if (!$tenantId) { http_response_code(404); die('<p style="font-family:sans-serif;padding:40px;color:#fff;background:#0f1117">Tenant not found.</p>'); }

$tenant = DB::fetchOne('SELECT * FROM tenants WHERE id = ?', [$tenantId]);
if (!$tenant) { http_response_code(404); die('<p style="font-family:sans-serif;padding:40px;color:#fff;background:#0f1117">Tenant not found.</p>'); }

$msg = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_user') {
        $dname = trim($_POST['display_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        $role  = in_array($_POST['role'] ?? '', ['admin', 'member']) ? $_POST['role'] : 'member';
        if (!$dname || !$email || !$pass) {
            $msg = 'error:All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = 'error:Enter a valid email address.';
        } elseif (strlen($pass) < 8) {
            $msg = 'error:Password must be at least 8 characters.';
        } else {
            try {
                DB::insert('users', [
                    'tenant_id'     => $tenantId,
                    'email'         => $email,
                    'password_hash' => Auth::hashPassword($pass),
                    'display_name'  => $dname,
                    'role'          => $role,
                    'active'        => 1,
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
                $msg = 'ok:User ' . htmlspecialchars($email) . ' created.';
            } catch (Exception $e) {
                $msg = 'error:' . $e->getMessage();
            }
        }
    }

    if ($action === 'toggle_user') {
        $uid = (int)$_POST['user_id'];
        $cur = DB::fetchOne('SELECT active FROM users WHERE id = ? AND tenant_id = ?', [$uid, $tenantId]);
        if ($cur) DB::update('users', ['active' => $cur['active'] ? 0 : 1], 'id = ? AND tenant_id = ?', [$uid, $tenantId]);
        header('Location: /platform/users.php?tenant_id=' . $tenantId);
        exit;
    }

    if ($action === 'delete_user') {
        $uid = (int)$_POST['user_id'];
        DB::query('DELETE FROM sessions WHERE user_id = ?', [$uid]);
        DB::query('DELETE FROM users WHERE id = ? AND tenant_id = ?', [$uid, $tenantId]);
        header('Location: /platform/users.php?tenant_id=' . $tenantId);
        exit;
    }
}

$users = DB::fetchAll('SELECT * FROM users WHERE tenant_id = ? ORDER BY id', [$tenantId]);
?><!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Users — <?= htmlspecialchars($tenant['name']) ?> — ISE Platform</title>
<link rel="stylesheet" href="/assets/css/app.css">
<style>
body{margin:0;background:var(--bg);color:var(--text);font-family:var(--font-sans,sans-serif)}
.plat-header{background:var(--card);border-bottom:1px solid var(--border);padding:14px 28px;display:flex;align-items:center;justify-content:space-between}
.plat-title{font-weight:700;font-size:16px;color:var(--text)}
.plat-sub{font-size:12px;color:#7c3aed;font-weight:600;letter-spacing:.04em}
.plat-body{padding:32px;max-width:860px}
.card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:24px;margin-bottom:20px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
label{display:block;font-size:12px;color:var(--muted);margin-bottom:4px}
input,select{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:9px 12px;font-size:13px;box-sizing:border-box}
.btn{background:#7c3aed;color:#fff;border:none;border-radius:8px;padding:9px 18px;font-size:13px;font-weight:600;cursor:pointer}
.btn-sm{background:transparent;border:1px solid var(--border);color:var(--muted);border-radius:6px;padding:5px 10px;font-size:11px;cursor:pointer}
.btn-danger{background:transparent;border:1px solid rgba(239,68,68,.4);color:#f87171;border-radius:6px;padding:5px 10px;font-size:11px;cursor:pointer}
.user-row{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:var(--card);border:1px solid var(--border);border-radius:10px;margin-bottom:10px}
.badge-active{background:rgba(34,197,94,.15);color:#4ade80;border-radius:4px;padding:2px 8px;font-size:10px;font-weight:700;text-transform:uppercase}
.badge-inactive{background:rgba(239,68,68,.15);color:#f87171;border-radius:4px;padding:2px 8px;font-size:10px;font-weight:700;text-transform:uppercase}
.badge-role{background:rgba(124,58,237,.15);color:#a78bfa;border-radius:4px;padding:2px 8px;font-size:10px;font-weight:700;text-transform:uppercase;margin-left:6px}
.msg-ok{background:rgba(34,197,94,.1);border:1px solid #22c55e;border-radius:8px;color:#4ade80;font-size:13px;padding:10px 14px;margin-bottom:18px}
.msg-err{background:rgba(239,68,68,.1);border:1px solid #ef4444;border-radius:8px;color:#f87171;font-size:13px;padding:10px 14px;margin-bottom:18px}
</style></head>
<body>
<div class="plat-header">
  <div>
    <div class="plat-title">&#9670; ISE Platform Admin</div>
    <div class="plat-sub">SUPER ADMIN</div>
  </div>
  <a href="/platform/logout.php" style="font-size:12px;color:var(--muted)">Sign out</a>
</div>
<div class="plat-body">
  <div style="margin-bottom:20px">
    <a href="/platform/tenants.php" style="font-size:12px;color:var(--muted);text-decoration:none">&#8592; Tenants</a>
    <h2 style="margin:6px 0 0;font-size:20px">Users &mdash; <?= htmlspecialchars($tenant['name']) ?></h2>
    <p style="margin:4px 0 0;font-size:12px;color:var(--muted)">slug: <?= htmlspecialchars($tenant['slug']) ?> &middot; tenant_id: <?= $tenantId ?></p>
  </div>

  <?php if ($msg): list($type, $text) = explode(':', $msg, 2); ?>
  <div class="msg-<?= $type === 'ok' ? 'ok' : 'err' ?>"><?= htmlspecialchars($text) ?></div>
  <?php endif; ?>

  <!-- User list -->
  <?php if ($users): ?>
    <?php foreach ($users as $u): ?>
    <div class="user-row">
      <div>
        <strong><?= htmlspecialchars($u['display_name']) ?></strong>
        <span class="badge-role"><?= htmlspecialchars($u['role']) ?></span>
        <br>
        <span style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($u['email']) ?></span>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <span class="<?= $u['active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $u['active'] ? 'Active' : 'Inactive' ?></span>
        <form method="post" style="display:inline">
          <input type="hidden" name="action" value="toggle_user">
          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          <button class="btn-sm" type="submit"><?= $u['active'] ? 'Deactivate' : 'Activate' ?></button>
        </form>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete user <?= htmlspecialchars(addslashes($u['email'])) ?>? This cannot be undone.')">
          <input type="hidden" name="action" value="delete_user">
          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          <button class="btn-danger" type="submit">Delete</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p style="color:var(--muted);font-size:13px">No users yet for this tenant.</p>
  <?php endif; ?>

  <!-- Add new user -->
  <div class="card" style="margin-top:28px">
    <h3 style="margin:0 0 16px;font-size:15px">Add User</h3>
    <form method="post">
      <input type="hidden" name="action" value="create_user">
      <div class="form-grid">
        <div><label>Display Name *</label><input type="text" name="display_name" required placeholder="Jane Smith"></div>
        <div><label>Role *</label>
          <select name="role">
            <option value="member">Member</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div><label>Email *</label><input type="email" name="email" required placeholder="jane@example.com"></div>
        <div><label>Password *</label><input type="password" name="password" required placeholder="Min 8 characters"></div>
      </div>
      <div style="margin-top:14px"><button class="btn" type="submit">Add User</button></div>
    </form>
  </div>
</div>
</body></html>
