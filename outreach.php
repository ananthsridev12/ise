<?php
require_once 'config.php';
require_once 'lib/DB.php';
include 'layout.php';

$companyId = (int)($_GET['company'] ?? 0);
$drafts = DB::fetchAll(
  'SELECT e.*, c.name, c.score, c.priority, c.industry, c.country, c.tech_stack
   FROM email_drafts e JOIN companies c ON c.id = e.company_id '
  . ($companyId ? 'WHERE e.company_id = ?' : '') .
  ' ORDER BY c.score DESC, e.created_at DESC',
  $companyId ? [$companyId] : []
);
?>

<div class="page-header">
  <div><div class="page-title">Outreach</div><div class="page-sub"><?= count($drafts) ?> email drafts ready</div></div>
  <?php if ($companyId): ?><a href="outreach.php" class="btn btn-secondary">← All Drafts</a><?php endif; ?>
</div>

<?php if (!$drafts): ?>
<div class="card card-body" style="text-align:center;padding:60px 20px;">
  <div style="font-size:40px;margin-bottom:16px;">✉</div>
  <div style="font-size:16px;font-weight:600;margin-bottom:8px;">No email drafts yet</div>
  <div style="color:var(--muted);margin-bottom:20px;">Enrich companies first to auto-generate personalized email drafts.</div>
  <a href="companies.php" class="btn btn-primary">Go to Companies →</a>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:16px">
<?php foreach ($drafts as $d):
  $tech = $d['tech_stack'] ? json_decode($d['tech_stack'], true) : [];
  $priority = strtolower($d['priority'] ?? 'low');
?>
<div class="card">
  <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border)">
    <div>
      <span style="font-weight:600"><?= htmlspecialchars($d['name']) ?></span>
      <span style="margin:0 8px;color:var(--border)">·</span>
      <span class="badge badge-<?= $priority ?>"><?= $d['priority'] ?></span>
      <span style="margin:0 8px;color:var(--border)">·</span>
      <span style="color:var(--muted);font-size:12px">Score: <?= $d['score'] ?></span>
      <?php foreach (array_slice($tech, 0, 3) as $tool): ?>
      <span class="badge badge-tech" style="margin-left:4px"><?= htmlspecialchars($tool) ?></span>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;gap:8px">
      <span style="font-size:11px;color:var(--muted);align-self:center">Angle: <?= htmlspecialchars(str_replace('_',' ', $d['angle'])) ?></span>
      <select onchange="updateEmailStatus(<?= $d['id'] ?>, this.value)" style="width:auto;padding:4px 8px;font-size:12px">
        <option <?= $d['status']==='draft'   ?'selected':'' ?> value="draft">Draft</option>
        <option <?= $d['status']==='sent'    ?'selected':'' ?> value="sent">Sent</option>
        <option <?= $d['status']==='replied' ?'selected':'' ?> value="replied">Replied</option>
      </select>
    </div>
  </div>
  <div style="padding:16px 20px">
    <div class="form-group"><label>Subject</label><input type="text" value="<?= htmlspecialchars($d['subject']) ?>" id="subject-<?= $d['id'] ?>" style="font-weight:500"></div>
    <div class="form-group"><label>Email Body</label><textarea id="body-<?= $d['id'] ?>" rows="10" style="line-height:1.6;font-size:13px"><?= htmlspecialchars($d['body']) ?></textarea></div>
    <div style="display:flex;gap:8px">
      <button onclick="copyDraft(<?= $d['id'] ?>)" class="btn btn-secondary btn-sm">📋 Copy Email</button>
      <button onclick="saveDraft(<?= $d['id'] ?>)" class="btn btn-ghost btn-sm">💾 Save Changes</button>
      <a href="mailto:?subject=<?= urlencode($d['subject']) ?>&body=<?= urlencode($d['body']) ?>" class="btn btn-primary btn-sm">📨 Open in Mail Client</a>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
async function updateEmailStatus(id, status) {
  const fd = new FormData(); fd.append('status', status); fd.append('id', id);
  await fetch('api/email.php', {method:'POST', body:fd});
  toast('Status updated');
}
function copyDraft(id) {
  const subject = document.getElementById('subject-'+id).value;
  const body = document.getElementById('body-'+id).value;
  navigator.clipboard.writeText(`Subject: ${subject}\n\n${body}`);
  toast('Email copied to clipboard');
}
async function saveDraft(id) {
  const fd = new FormData();
  fd.append('id', id);
  fd.append('subject', document.getElementById('subject-'+id).value);
  fd.append('body', document.getElementById('body-'+id).value);
  await fetch('api/email.php', {method:'POST', body:fd});
  toast('Draft saved');
}
</script>

<?php include 'layout_end.php'; ?>
