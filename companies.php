<?php
require_once 'config.php';
require_once 'lib/DB.php';
include 'layout.php';

$companies = DB::fetchAll('SELECT * FROM companies ORDER BY score DESC, created_at DESC');
$total = count($companies);
$enriched = count(array_filter($companies, fn($c) => $c['status'] === 'enriched'));
?>

<div class="page-header">
  <div>
    <div class="page-title">Companies</div>
    <div class="page-sub"><?= $total ?> total · <?= $enriched ?> enriched</div>
  </div>
  <div style="display:flex;gap:10px;">
    <button onclick="enrichAll()" class="btn btn-secondary" id="enrichAllBtn">⚡ Enrich All Pending</button>
    <a href="upload.php" class="btn btn-primary">+ Add</a>
  </div>
</div>

<div class="filters">
  <input type="text" id="search" placeholder="Search companies..." style="width:220px" oninput="filterTable()">
  <select id="filterPriority" onchange="filterTable()">
    <option value="">All Priorities</option>
    <option>High</option><option>Medium</option><option>Low</option>
  </select>
  <select id="filterStatus" onchange="filterTable()">
    <option value="">All Status</option>
    <option value="pending">Pending</option>
    <option value="enriched">Enriched</option>
    <option value="outreach">In Outreach</option>
    <option value="done">Done</option>
  </select>
</div>

<div id="enrichProgress" style="display:none;margin-bottom:16px;" class="card card-body">
  <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
    <span id="progressLabel">Enriching...</span>
    <span id="progressCount"></span>
  </div>
  <div class="progress-bar"><div class="progress-fill" id="progressFill" style="width:0%"></div></div>
</div>

<div class="card">
<div class="table-wrap">
<table id="companiesTable">
  <thead>
    <tr>
      <th><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
      <th>Company</th><th>Industry</th><th>Country</th><th>Score</th><th>Priority</th>
      <th>Signals</th><th>Tech Stack</th><th>Top Signal</th><th>Status</th><th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($companies as $c):
    $tech = $c['tech_stack'] ? json_decode($c['tech_stack'], true) : [];
    $priority = strtolower($c['priority'] ?? 'low');
  ?>
  <tr data-id="<?= $c['id'] ?>" data-priority="<?= $priority ?>" data-status="<?= $c['status'] ?>" data-name="<?= strtolower(htmlspecialchars($c['name'])) ?>">
    <td><input type="checkbox" class="row-check" value="<?= $c['id'] ?>"></td>
    <td>
      <div style="font-weight:600"><?= htmlspecialchars($c['name']) ?></div>
      <?php if ($c['url']): ?>
      <div style="color:var(--muted);font-size:11px"><a href="<?= htmlspecialchars($c['url']) ?>" target="_blank" style="color:var(--muted)"><?= parse_url($c['url'], PHP_URL_HOST) ?></a></div>
      <?php endif; ?>
    </td>
    <td style="color:var(--muted)"><?= htmlspecialchars($c['industry'] ?? '—') ?></td>
    <td style="color:var(--muted)"><?= htmlspecialchars($c['country'] ?? '—') ?></td>
    <td>
      <?php if ($c['status'] === 'enriched'): ?>
      <div class="score-bar">
        <div class="score-track"><div class="score-fill <?= $priority ?>" style="width:<?= $c['score'] ?>%"></div></div>
        <span style="font-weight:600;min-width:24px"><?= $c['score'] ?></span>
      </div>
      <?php else: ?><span style="color:var(--muted)">—</span><?php endif; ?>
    </td>
    <td>
      <?php if ($c['status'] === 'enriched'): ?>
      <span class="badge badge-<?= $priority ?>"><?= $c['priority'] ?></span>
      <?php else: ?><span class="badge badge-pending">Pending</span><?php endif; ?>
    </td>
    <td style="color:var(--muted)"><?= $c['signal_count'] ?: '—' ?></td>
    <td>
      <?php foreach (array_slice($tech, 0, 2) as $tool): ?>
      <span class="badge badge-tech" style="margin:1px"><?= htmlspecialchars($tool) ?></span>
      <?php endforeach; ?>
      <?php if (count($tech) > 2): ?><span style="color:var(--muted);font-size:11px">+<?= count($tech)-2 ?></span><?php endif; ?>
    </td>
    <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($c['top_signal'] ?? '—') ?></td>
    <td>
      <select class="status-select" onchange="updateStatus(<?= $c['id'] ?>, this.value)" style="width:auto;padding:4px 6px;font-size:12px">
        <option <?= $c['status']==='pending'  ?'selected':'' ?> value="pending">Pending</option>
        <option <?= $c['status']==='enriched' ?'selected':'' ?> value="enriched">Enriched</option>
        <option <?= $c['status']==='outreach' ?'selected':'' ?> value="outreach">Outreach</option>
        <option <?= $c['status']==='done'     ?'selected':'' ?> value="done">Done</option>
      </select>
    </td>
    <td>
      <div style="display:flex;gap:4px">
        <button onclick="enrichOne(<?= $c['id'] ?>, this)" class="btn btn-ghost btn-sm" title="Enrich">⚡</button>
        <button onclick="viewDetail(<?= $c['id'] ?>)" class="btn btn-ghost btn-sm" title="View">👁</button>
        <a href="outreach.php?company=<?= $c['id'] ?>" class="btn btn-ghost btn-sm" title="Email">✉</a>
        <button onclick="deleteCompany(<?= $c['id'] ?>)" class="btn btn-ghost btn-sm" title="Delete" style="color:var(--danger)">✕</button>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>

<div class="modal-backdrop" id="detailModal">
  <div class="modal" style="max-width:760px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <div class="modal-title" id="detailTitle">Company Details</div>
      <button onclick="closeModal()" class="btn btn-ghost">✕</button>
    </div>
    <div id="detailContent">Loading...</div>
  </div>
</div>

<script>
function filterTable() {
  const q = document.getElementById('search').value.toLowerCase();
  const p = document.getElementById('filterPriority').value.toLowerCase();
  const s = document.getElementById('filterStatus').value;
  document.querySelectorAll('#companiesTable tbody tr').forEach(row => {
    const show = (!q || row.dataset.name.includes(q)) && (!p || row.dataset.priority === p) && (!s || row.dataset.status === s);
    row.style.display = show ? '' : 'none';
  });
}

function toggleAll(cb) { document.querySelectorAll('.row-check').forEach(c => c.checked = cb.checked); }

async function enrichOne(id, btn) {
  btn.textContent = '...';
  btn.disabled = true;
  const r = await fetch(`api/enrich.php?id=${id}`, {method:'POST'});
  const d = await r.json();
  if (d.ok) { toast(`Enriched — Score: ${d.score} (${d.priority})`); setTimeout(() => location.reload(), 1000); }
  else { toast('Error: ' + (d.error||'unknown'), 'error'); btn.textContent = '⚡'; btn.disabled = false; }
}

async function enrichAll() {
  const pending = [...document.querySelectorAll('tr[data-status="pending"]')].map(r => r.dataset.id);
  if (!pending.length) { toast('No pending companies', 'error'); return; }
  document.getElementById('enrichProgress').style.display = 'block';
  document.getElementById('enrichAllBtn').disabled = true;
  let done = 0;
  for (const id of pending) {
    document.getElementById('progressLabel').textContent = `Enriching company ${done+1} of ${pending.length}...`;
    document.getElementById('progressCount').textContent = `${done}/${pending.length}`;
    document.getElementById('progressFill').style.width = `${(done/pending.length)*100}%`;
    await fetch(`api/enrich.php?id=${id}`, {method:'POST'});
    done++;
  }
  document.getElementById('progressFill').style.width = '100%';
  document.getElementById('progressLabel').textContent = `Done! Enriched ${done} companies.`;
  toast(`Enriched ${done} companies`);
  setTimeout(() => location.reload(), 1500);
}

async function viewDetail(id) {
  document.getElementById('detailModal').classList.add('open');
  document.getElementById('detailContent').innerHTML = 'Loading...';
  const r = await fetch(`api/companies.php?action=detail&id=${id}`);
  const d = await r.json();
  const c = d.company; const tech = d.tech || []; const signals = d.signals || []; const email = d.email;
  document.getElementById('detailTitle').textContent = c.name;
  const techHtml = tech.map(t => `<span class="badge badge-tech" style="margin:2px">${t.tool} <span style="color:var(--muted);font-size:10px">(${t.category})</span></span>`).join('') || '<span style="color:var(--muted)">None detected</span>';
  const signalsHtml = signals.slice(0,8).map(s => `<div style="padding:10px 0;border-bottom:1px solid var(--border)"><div style="font-size:12px;font-weight:500"><a href="${s.url}" target="_blank" style="color:var(--text)">${s.title}</a></div><div style="color:var(--muted);font-size:11px;margin-top:3px">${s.source} · ${s.published_date}</div><div style="color:var(--muted);font-size:12px;margin-top:4px">${(s.snippet||'').substring(0,150)}...</div></div>`).join('') || '<div style="color:var(--muted)">No signals found</div>';
  const emailHtml = email ? `<div style="margin-top:4px"><div style="font-size:12px;color:var(--muted);margin-bottom:6px">Subject: <strong style="color:var(--text)">${email.subject}</strong></div><textarea style="width:100%;height:180px;font-size:12px;line-height:1.6" id="emailBody">${email.body}</textarea><div style="display:flex;gap:8px;margin-top:8px"><button onclick="copyEmail()" class="btn btn-secondary btn-sm">📋 Copy</button><a href="outreach.php?company=${c.id}" class="btn btn-primary btn-sm">Open in Outreach →</a></div></div>` : '<span style="color:var(--muted)">Run enrichment first to generate email draft</span>';
  document.getElementById('detailContent').innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:20px">
      <div><span style="color:var(--muted);font-size:11px">Score</span><div style="font-size:24px;font-weight:700">${c.score}</div></div>
      <div><span style="color:var(--muted);font-size:11px">Priority</span><div style="margin-top:4px"><span class="badge badge-${(c.priority||'low').toLowerCase()}">${c.priority||'—'}</span></div></div>
      <div><span style="color:var(--muted);font-size:11px">Industry</span><div>${c.industry||'—'}</div></div>
      <div><span style="color:var(--muted);font-size:11px">Country</span><div>${c.country||'—'}</div></div>
    </div>
    <div style="margin-bottom:16px"><div class="detail-label">Detected Tech Stack</div>${techHtml}</div>
    <div style="margin-bottom:16px"><div class="detail-label">Signals (${signals.length})</div>${signalsHtml}</div>
    <div><div class="detail-label">Email Draft</div>${emailHtml}</div>`;
}

function copyEmail() { navigator.clipboard.writeText(document.getElementById('emailBody').value); toast('Email copied to clipboard'); }
function closeModal() { document.getElementById('detailModal').classList.remove('open'); }

async function updateStatus(id, status) {
  const fd = new FormData(); fd.append('status', status);
  await fetch(`api/companies.php?action=update_status&id=${id}`, {method:'POST', body:fd});
  toast('Status updated');
}

async function deleteCompany(id) {
  if (!confirm('Delete this company and all its data?')) return;
  await fetch(`api/companies.php?action=delete&id=${id}`);
  document.querySelector(`tr[data-id="${id}"]`).remove();
  toast('Deleted');
}

document.getElementById('detailModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
</script>

<?php include 'layout_end.php'; ?>
