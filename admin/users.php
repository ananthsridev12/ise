<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../layout.php';
Auth::requireAdmin();

$tenantId = Auth::tenantId();
$msg = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_user') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        $dname = trim($_POST['display_name'] ?? '');
        $role  = $_POST['role'] === 'admin' ? 'admin' : 'member';
        if (!$email || !$pass) { $msg = 'error:Email and password required.'; }
        else {
            try {
                DB::insert('users', [
                    'tenant_id'     => $tenantId,
                    'email'         => $email,
                    'password_hash' => Auth::hashPassword($pass),
                    'display_name'  => $dname,
                    'role'          => $role,
                    'active'        => 1,
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
        header('Location: /admin/users.php'); exit;
    }

    if ($action === 'delete_user') {
        $uid = (int)$_POST['user_id'];
        if ($uid !== (int)Auth::user()['user_id']) {
            DB::query('DELETE FROM users WHERE id = ? AND tenant_id = ?', [$uid, $tenantId]);
        }
        header('Location: /admin/users.php'); exit;
    }

    if ($action === 'save_permissions') {
        $uid        = (int)$_POST['user_id'];
        $verticals  = array_map('intval', (array)($_POST['vertical_ids']  ?? []));
        $services   = array_map('intval', (array)($_POST['service_ids']   ?? []));
        DB::query('DELETE FROM user_kb_verticals WHERE user_id = ?', [$uid]);
        DB::query('DELETE FROM user_kb_services   WHERE user_id = ?', [$uid]);
        foreach ($verticals as $vid) DB::insert('user_kb_verticals', ['user_id' => $uid, 'vertical_id' => $vid]);
        foreach ($services  as $sid) DB::insert('user_kb_services',  ['user_id' => $uid, 'service_id'  => $sid]);
        $msg = 'ok:Permissions saved.';
    }
}

$users     = DB::fetchAll('SELECT * FROM users WHERE tenant_id = ? ORDER BY id', [$tenantId]);
$verticals = DB::fetchAll('SELECT * FROM kb_verticals WHERE tenant_id = ? ORDER BY name', [$tenantId]);
$services  = DB::fetchAll('SELECT * FROM kb_services  WHERE tenant_id = ? ORDER BY name', [$tenantId]);

// Selected user for permissions panel
$selUid = (int)($_GET['user_id'] ?? 0);
$selUser = $selUid ? DB::fetchOne('SELECT * FROM users WHERE id = ? AND tenant_id = ?', [$selUid, $tenantId]) : null;
$selVerticals = $selUid ? array_column(DB::fetchAll('SELECT vertical_id FROM user_kb_verticals WHERE user_id = ?', [$selUid]), 'vertical_id') : [];
$selServices  = $selUid ? array_column(DB::fetchAll('SELECT service_id  FROM user_kb_services  WHERE user_id = ?', [$selUid]), 'service_id')  : [];
?>
<div class="page-header">
  <div>
    <h1 class="page-title">Users</h1>
    <p style="margin:4px 0 0;color:var(--muted);font-size:13px">Manage team members and their access</p>
  </div>
  <button onclick="document.getElementById('addUserForm').style.display=document.getElementById('addUserForm').style.display==='none'?'block':'none'" class="btn btn-primary">+ Add User</button>
</div>

<?php if ($msg): list($type, $text) = explode(':', $msg, 2); ?>
<div style="background:<?= $type==='ok' ? 'rgba(34,197,94,.1)' : 'rgba(239,68,68,.1)' ?>;border:1px solid <?= $type==='ok' ? '#22c55e' : '#ef4444' ?>;border-radius:8px;color:<?= $type==='ok' ? '#4ade80' : '#f87171' ?>;font-size:13px;padding:10px 14px;margin-bottom:18px"><?= htmlspecialchars($text) ?></div>
<?php endif; ?>

<!-- Add user form -->
<div id="addUserForm" style="display:none;background:var(--card);border:1px solid var(--border);border-radius:12px;padding:24px;margin-bottom:20px">
  <h3 style="margin:0 0 16px;font-size:15px">New User</h3>
  <form method="post">
    <input type="hidden" name="action" value="create_user">
    <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div><label class="form-label">Display Name</label><input class="form-control" name="display_name" placeholder="Jane Smith"></div>
      <div><label class="form-label">Email *</label><input class="form-control" type="email" name="email" required placeholder="jane@company.com"></div>
      <div><label class="form-label">Password *</label><input class="form-control" type="password" name="password" required placeholder="Temporary password"></div>
      <div><label class="form-label">Role</label>
        <select class="form-control" name="role">
          <option value="member">Member</option>
          <option value="admin">Admin</option>
        </select>
      </div>
    </div>
    <div style="margin-top:14px;display:flex;gap:10px;justify-content:flex-end">
      <button type="button" onclick="document.getElementById('addUserForm').style.display='none'" class="btn btn-secondary">Cancel</button>
      <button type="submit" class="btn btn-primary">Create User</button>
    </div>
  </form>
</div>

<div style="display:grid;grid-template-columns:<?= $selUser ? '1fr 1fr' : '1fr' ?>;gap:20px">
  <div>
    <?php foreach ($users as $u): ?>
    <div style="background:var(--card);border:1px solid <?= $selUser && $selUser['id']==$u['id'] ? '#4f46e5' : 'var(--border)' ?>;border-radius:10px;padding:14px 16px;margin-bottom:10px;display:flex;align-items:center;justify-content:space-between">
      <div>
        <div style="font-size:14px;font-weight:600"><?= htmlspecialchars($u['display_name'] ?: $u['email']) ?></div>
        <div style="font-size:12px;color:var(--muted);margin-top:2px"><?= htmlspecialchars($u['email']) ?></div>
        <div style="margin-top:6px;display:flex;gap:6px;align-items:center">
          <span style="font-size:10px;font-weight:700;text-transform:uppercase;border-radius:4px;padding:2px 7px;background:<?= $u['role']==='admin'?'rgba(99,102,241,.2)':'rgba(34,197,94,.15)' ?>;color:<?= $u['role']==='admin'?'#818cf8':'#4ade80' ?>"><?= $u['role'] ?></span>
          <span style="font-size:10px;font-weight:700;text-transform:uppercase;border-radius:4px;padding:2px 7px;background:<?= $u['active']?'rgba(34,197,94,.15)':'rgba(239,68,68,.15)' ?>;color:<?= $u['active']?'#4ade80':'#f87171' ?>"><?= $u['active'] ? 'Active' : 'Inactive' ?></span>
          <?php if ($u['role']==='member'): ?>
          <a href="?user_id=<?= $u['id'] ?>" style="font-size:11px;color:#4f46e5;text-decoration:none">Manage access &rarr;</a>
          <?php endif; ?>
        </div>
      </div>
      <div style="display:flex;gap:8px">
        <form method="post" style="display:inline"><input type="hidden" name="action" value="toggle_user"><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><button class="btn btn-secondary" style="font-size:11px;padding:5px 10px"><?= $u['active'] ? 'Deactivate' : 'Activate' ?></button></form>
        <?php if ($u['id'] != Auth::user()['user_id']): ?>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete <?= htmlspecialchars($u['email']) ?>?')"><input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><button class="btn btn-secondary" style="font-size:11px;padding:5px 10px;color:#f87171">Delete</button></form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($selUser): ?>
  <div style="background:var(--card);border:1px solid #4f46e5;border-radius:10px;padding:20px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <h3 style="margin:0;font-size:14px">Access for <?= htmlspecialchars($selUser['display_name'] ?: $selUser['email']) ?></h3>
      <a href="/admin/users.php" style="font-size:12px;color:var(--muted);text-decoration:none">&#10005;</a>
    </div>
    <form method="post">
      <input type="hidden" name="action" value="save_permissions">
      <input type="hidden" name="user_id" value="<?= $selUser['id'] ?>">
      <div style="margin-bottom:16px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:10px">Verticals</div>
        <?php if (!$verticals): ?><p style="font-size:12px;color:var(--muted)">No verticals yet — add in Knowledge Hub</p><?php endif; ?>
        <?php foreach ($verticals as $v): ?>
        <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;cursor:pointer;font-size:13px">
          <input type="checkbox" name="vertical_ids[]" value="<?= $v['id'] ?>" <?= in_array($v['id'], $selVerticals)?'checked':'' ?>>
          <?= htmlspecialchars($v['name']) ?>
        </label>
        <?php endforeach; ?>
      </div>
      <div style="margin-bottom:20px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:10px">Services</div>
        <?php if (!$services): ?><p style="font-size:12px;color:var(--muted)">No services yet — add in Knowledge Hub</p><?php endif; ?>
        <?php foreach ($services as $s): ?>
        <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;cursor:pointer;font-size:13px">
          <input type="checkbox" name="service_ids[]" value="<?= $s['id'] ?>" <?= in_array($s['id'], $selServices)?'checked':'' ?>>
          <?= htmlspecialchars($s['name']) ?>
        </label>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn btn-primary">Save Permissions</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout_end.php'; ?>
