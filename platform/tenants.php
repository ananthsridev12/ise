<?php
require_once __DIR__ . '/../config.php';
Auth::requirePlatformLogin();

$msg = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_tenant') {
        $name  = trim($_POST['name'] ?? '');
        $slug  = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($_POST['slug'] ?? '')));
        $email = strtolower(trim($_POST['admin_email'] ?? ''));
        $pass  = $_POST['admin_password'] ?? '';
        $dname = trim($_POST['admin_name'] ?? '');
        if (!$name || !$slug || !$email || !$pass) {
            $msg = 'error:All fields are required.';
        } else {
            try {
                DB::insert('tenants', ['name' => $name, 'slug' => $slug, 'active' => 1]);
                $tenantId = DB::fetchOne('SELECT id FROM tenants WHERE slug = ?', [$slug])['id'];
                DB::insert('users', [
                    'tenant_id'     => $tenantId,
                    'email'         => $email,
                    'password_hash' => Auth::hashPassword($pass),
                    'display_name'  => $dname ?: $name . ' Admin',
                    'role'          => 'admin',
                    'active'        => 1,
                ]);
                // Seed tenant row in kb_company and ai_settings
                DB::insert('ai_settings', ['tenant_id' => $tenantId, 'provider' => 'gemini']);
                $msg = 'ok:Tenant "' . htmlspecialchars($name) . '" created with admin ' . htmlspecialchars($email);
            } catch (Exception $e) {
                $msg = 'error:' . $e->getMessage();
            }
        }
    }

    if ($action === 'toggle_tenant') {
        $id  = (int)$_POST['tenant_id'];
        $cur = DB::fetchOne('SELECT active FROM tenants WHERE id = ?', [$id]);
        if ($cur) DB::update('tenants', ['active' => $cur['active'] ? 0 : 1], 'id = ?', [$id]);
        header('Location: /platform/tenants.php');
        exit;
    }
}

$tenants = DB::fetchAll('SELECT t.*, COUNT(u.id) as user_count FROM tenants t LEFT JOIN users u ON u.tenant_id = t.id GROUP BY t.id ORDER BY t.id');
?><!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Tenants — ISE Platform</title>
<link rel="stylesheet" href="/assets/css/app.css">
<style>
body{margin:0;background:var(--bg);color:var(--text);font-family:var(--font-sans,sans-serif)}
.plat-header{background:var(--card);border-bottom:1px solid var(--border);padding:14px 28px;display:flex;align-items:center;justify-content:space-between}
.plat-title{font-weight:700;font-size:16px;color:var(--text)}
.plat-sub{font-size:12px;color:#7c3aed;font-weight:600;letter-spacing:.04em}
.plat-body{padding:32px;max-width:900px}
.card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:24px;margin-bottom:20px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
label{display:block;font-size:12px;color:var(--muted);margin-bottom:4px}
input{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:9px 12px;font-size:13px;box-sizing:border-box}
.btn{background:#7c3aed;color:#fff;border:none;border-radius:8px;padding:9px 18px;font-size:13px;font-weight:600;cursor:pointer}
.btn-sm{background:transparent;border:1px solid var(--border);color:var(--muted);border-radius:6px;padding:5px 10px;font-size:11px;cursor:pointer}
.tenant-row{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:var(--card);border:1px solid var(--border);border-radius:10px;margin-bottom:10px}
.badge-active{background:rgba(34,197,94,.15);color:#4ade80;border-radius:4px;padding:2px 8px;font-size:10px;font-weight:700;text-transform:uppercase}
.badge-inactive{background:rgba(239,68,68,.15);color:#f87171;border-radius:4px;padding:2px 8px;font-size:10px;font-weight:700;text-transform:uppercase}
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
  <h2 style="margin:0 0 20px;font-size:20px">Tenants</h2>

  <?php if ($msg): list($type, $text) = explode(':', $msg, 2); ?>
  <div class="msg-<?= $type === 'ok' ? 'ok' : 'err' ?>"><?= htmlspecialchars($text) ?></div>
  <?php endif; ?>

  <!-- Existing tenants -->
  <?php foreach ($tenants as $t): ?>
  <div class="tenant-row">
    <div>
      <strong><?= htmlspecialchars($t['name']) ?></strong>
      <span style="color:var(--muted);font-size:12px;margin-left:10px">slug: <?= htmlspecialchars($t['slug']) ?></span>
      <span style="color:var(--muted);font-size:12px;margin-left:10px"><?= $t['user_count'] ?> user(s)</span>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      <span class="<?= $t['active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $t['active'] ? 'Active' : 'Inactive' ?></span>
      <form method="post" style="display:inline">
        <input type="hidden" name="action" value="toggle_tenant">
        <input type="hidden" name="tenant_id" value="<?= $t['id'] ?>">
        <button class="btn-sm" type="submit"><?= $t['active'] ? 'Deactivate' : 'Activate' ?></button>
      </form>
      <a href="/platform/users.php?tenant_id=<?= $t['id'] ?>" class="btn-sm">Manage Users</a>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Create new tenant -->
  <div class="card" style="margin-top:28px">
    <h3 style="margin:0 0 16px;font-size:15px">Create New Tenant</h3>
    <form method="post">
      <input type="hidden" name="action" value="create_tenant">
      <div class="form-grid">
        <div><label>Company Name *</label><input name="name" required placeholder="Company Y"></div>
        <div><label>Slug (URL-safe, unique) *</label><input name="slug" required placeholder="company-y"></div>
        <div><label>Admin Email *</label><input type="email" name="admin_email" required placeholder="admin@companyy.com"></div>
        <div><label>Admin Password *</label><input type="password" name="admin_password" required placeholder="Min 8 chars"></div>
        <div style="grid-column:1/-1"><label>Admin Display Name</label><input name="admin_name" placeholder="Jane Smith"></div>
      </div>
      <div style="margin-top:14px"><button class="btn" type="submit">Create Tenant + Admin</button></div>
    </form>
  </div>
</div>
</body></html>
