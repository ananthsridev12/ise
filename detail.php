<?php
require_once 'config.php';
require_once 'lib/DB.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: companies.php'); exit; }

$company = DB::fetchOne('SELECT * FROM companies WHERE id = ?', [$id]);
if (!$company) { header('Location: companies.php'); exit; }

$signals = DB::fetchAll('SELECT * FROM signals WHERE company_id = ? ORDER BY created_at DESC', [$id]);
$tech    = DB::fetchAll('SELECT * FROM company_tech WHERE company_id = ? ORDER BY confidence DESC', [$id]);
$email   = DB::fetchOne('SELECT * FROM email_drafts WHERE company_id = ? ORDER BY id DESC LIMIT 1', [$id]);

$priority  = strtolower($company['priority'] ?? 'low');
$newsSigs  = array_values(array_filter($signals, function($s) { return $s['source'] === 'GoogleNews'; }));
$jobSigs   = array_values(array_filter($signals, function($s) { return $s['source'] === 'Indeed'; }));

$techByCategory = [];
foreach ($tech as $t) {
    $techByCategory[$t['category']][] = $t;
}

$scoreColor = $priority === 'high' ? 'var(--success)' : ($priority === 'medium' ? 'var(--warning)' : 'var(--muted)');

include 'layout.php';
?>

<div class="page-header">
  <div>
    <a href="companies.php" style="color:var(--muted);font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:8px">&larr; Back to Companies</a>
    <div class="page-title"><?= htmlspecialchars($company['name']) ?></div>
    <div class="page-sub">
      <?= htmlspecialchars($company['industry'] ?? '') ?>
      <?php if ($company['country']): ?> &middot; <?= htmlspecialchars($company['country']) ?><?php endif; ?>
      <?php if ($company['url']): ?> &middot; <a href="<?= htmlspecialchars($company['url']) ?>" target="_blank" style="color:var(--muted)"><?= htmlspecialchars($company['url']) ?></a><?php endif; ?>
    </div>
  </div>
  <div style="display:flex;gap:10px;align-items:center">
    <button onclick="enrichNow()" class="btn btn-secondary" id="enrichBtn">&#9889; Re-Enrich</button>
    <?php if ($email): ?>
    <a href="outreach.php?company=<?= $id ?>" class="btn btn-primary">&#9993; View Email Draft</a>
    <?php endif; ?>
  </div>
</div>

<!-- Score Banner -->
<div style="display:grid;grid-template-columns:140px 1fr;gap:0;background:var(--card);border:1px solid var(--border);border-radius:10px;padding:24px;margin-bottom:24px;align-items:center">
  <div style="text-align:center;padding-right:24px;border-right:1px solid var(--border)">
    <div style="font-size:52px;font-weight:800;line-height:1;color:<?= $scoreColor ?>"><?= $company['score'] ?? 0 ?></div>
    <div style="font-size:11px;color:var(--muted);margin-top:4px;text-transform:uppercase;letter-spacing:.05em">Intent Score</div>
    <div style="margin-top:8px"><span class="badge badge-<?= $priority ?>"><?= $company['priority'] ?? 'Low' ?></span></div>
  </div>
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;padding-left:24px">
    <div>
      <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;font-weight:600">Signals Found</div>
      <div style="font-size:22px;font-weight:700;margin-top:4px"><?= count($signals) ?></div>
      <div style="font-size:11px;color:var(--muted)"><?= count($newsSigs) ?> news &middot; <?= count($jobSigs) ?> jobs</div>
    </div>
    <div>
      <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;font-weight:600">Top Signal</div>
      <div style="font-size:14px;font-weight:600;margin-top:4px"><?= htmlspecialchars(ucwords($company['top_signal'] ?? '&mdash;')) ?></div>
    </div>
    <div>
      <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;font-weight:600">Signal Types</div>
      <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:4px">
        <?php foreach (array_filter(explode(', ', $company['signal_types'] ?? '')) as $type): ?>
        <span class="badge badge-tech" style="font-size:10px"><?= htmlspecialchars($type) ?></span>
        <?php endforeach; ?>
        <?php if (!$company['signal_types']): ?><span style="color:var(--muted);font-size:13px">&mdash;</span><?php endif; ?>
      </div>
    </div>
    <div>
      <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;font-weight:600">Last Enriched</div>
      <div style="font-size:13px;font-weight:500;margin-top:4px"><?= $company['enriched_at'] ? date('d M Y, H:i', strtotime($company['enriched_at'])) : '&mdash;' ?></div>
    </div>
  </div>
</div>

<div id="enrichStatus" style="display:none;margin-bottom:16px" class="card card-body"></div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

<!-- LEFT COLUMN -->
<div style="display:flex;flex-direction:column;gap:20px">

  <!-- Tech Stack -->
  <div class="card">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">&#128736; Detected Tech Stack <span style="color:var(--muted);font-weight:400;font-size:12px">&mdash; inferred from job postings</span></div>
    <div style="padding:16px 20px">
      <?php if ($tech): ?>
        <?php foreach ($techByCategory as $cat => $tools): ?>
        <div style="margin-bottom:16px">
          <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;font-weight:700;margin-bottom:8px"><?= htmlspecialchars($cat) ?></div>
          <?php foreach ($tools as $t): ?>
          <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(42,45,58,0.4)">
            <div>
              <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($t['tool']) ?></div>
              <?php if ($t['source_title']): ?>
              <div style="font-size:11px;color:var(--muted);margin-top:2px">Job: &ldquo;<?= htmlspecialchars(substr($t['source_title'], 0, 55)) ?>...&rdquo;</div>
              <?php endif; ?>
              <?php if ($t['source_url']): ?>
              <a href="<?= htmlspecialchars($t['source_url']) ?>" target="_blank" style="font-size:11px;color:var(--accent)">View source &rarr;</a>
              <?php endif; ?>
            </div>
            <span style="font-size:11px;color:<?= $t['confidence'] >= 90 ? 'var(--success)' : 'var(--warning)' ?>;white-space:nowrap;margin-left:12px"><?= $t['confidence'] ?>% match</span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="color:var(--muted);font-size:13px;line-height:1.6">
          No tools detected. This usually means:<br>
          &bull; No recent job postings found on Indeed<br>
          &bull; Job descriptions didn't mention known ERP/CPQ tools
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Job Postings -->
  <div class="card">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">&#128188; Job Postings <span style="color:var(--muted);font-weight:400">(<?= count($jobSigs) ?> found)</span></div>
    <?php if ($jobSigs): ?>
    <div>
      <?php foreach ($jobSigs as $sig): ?>
      <div style="padding:12px 20px;border-bottom:1px solid rgba(42,45,58,0.4)">
        <a href="<?= htmlspecialchars($sig['url']) ?>" target="_blank" style="color:var(--text);font-size:13px;font-weight:500;text-decoration:none"><?= htmlspecialchars($sig['title']) ?></a>
        <div style="color:var(--muted);font-size:11px;margin-top:3px"><?= htmlspecialchars($sig['published_date']) ?></div>
        <?php if ($sig['snippet']): ?>
        <div style="color:var(--muted);font-size:12px;margin-top:4px;line-height:1.5"><?= htmlspecialchars(substr($sig['snippet'], 0, 200)) ?>...</div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="padding:20px;color:var(--muted);font-size:13px">No job postings found for this company.</div>
    <?php endif; ?>
  </div>

</div>

<!-- RIGHT COLUMN -->
<div style="display:flex;flex-direction:column;gap:20px">

  <!-- News Signals -->
  <div class="card">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">&#128240; News & Signals <span style="color:var(--muted);font-weight:400">(<?= count($newsSigs) ?> found)</span></div>
    <?php if ($newsSigs): ?>
    <div>
      <?php foreach ($newsSigs as $sig): ?>
      <div style="padding:12px 20px;border-bottom:1px solid rgba(42,45,58,0.4)">
        <a href="<?= htmlspecialchars($sig['url']) ?>" target="_blank" style="color:var(--text);font-size:13px;font-weight:500;text-decoration:none;line-height:1.4;display:block"><?= htmlspecialchars($sig['title']) ?></a>
        <div style="color:var(--muted);font-size:11px;margin-top:4px"><?= htmlspecialchars($sig['published_date']) ?></div>
        <?php if ($sig['snippet']): ?>
        <div style="color:var(--muted);font-size:12px;margin-top:4px;line-height:1.5"><?= htmlspecialchars(substr($sig['snippet'], 0, 220)) ?>...</div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="padding:20px;color:var(--muted);font-size:13px">No news signals found. Try re-enriching or check the company name spelling.</div>
    <?php endif; ?>
  </div>

  <!-- Email Draft -->
  <?php if ($email): ?>
  <div class="card">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">
      &#9993; Email Draft
      <span style="color:var(--muted);font-weight:400;font-size:12px;margin-left:8px">Angle: <?= htmlspecialchars(str_replace('_', ' ', $email['angle'])) ?></span>
    </div>
    <div style="padding:16px 20px">
      <div style="font-size:11px;color:var(--muted);margin-bottom:4px">SUBJECT</div>
      <div style="font-size:13px;font-weight:600;margin-bottom:14px;line-height:1.4"><?= htmlspecialchars($email['subject']) ?></div>
      <div style="font-size:11px;color:var(--muted);margin-bottom:4px">BODY</div>
      <textarea id="emailBody" rows="11" style="width:100%;font-size:12px;line-height:1.7;resize:vertical"><?= htmlspecialchars($email['body']) ?></textarea>
      <div style="display:flex;gap:8px;margin-top:10px">
        <button onclick="copyEmail()" class="btn btn-secondary btn-sm">&#128203; Copy</button>
        <a href="mailto:?subject=<?= urlencode($email['subject']) ?>&body=<?= urlencode($email['body']) ?>" class="btn btn-primary btn-sm">&#128232; Open in Mail Client</a>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>
</div>

<script>
async function enrichNow() {
  const btn = document.getElementById('enrichBtn');
  const status = document.getElementById('enrichStatus');
  btn.textContent = 'Enriching...';
  btn.disabled = true;
  status.style.display = 'block';
  status.innerHTML = '<span style="color:var(--muted)">Fetching signals from Google News and Indeed... this may take 15-20 seconds.</span>';
  const r = await fetch('api/enrich.php?id=<?= $id ?>', {method: 'POST'});
  const d = await r.json();
  if (d.ok) {
    status.innerHTML = '<span style="color:var(--success)">&#10003; Done! Score: ' + d.score + ' (' + d.priority + '). Reloading...</span>';
    setTimeout(() => location.reload(), 1500);
  } else {
    status.innerHTML = '<span style="color:var(--danger)">Error: ' + (d.error || 'unknown error') + '</span>';
    btn.textContent = '&#9889; Re-Enrich';
    btn.disabled = false;
  }
}
function copyEmail() {
  const el = document.getElementById('emailBody');
  el.select();
  document.execCommand('copy');
  toast('Email copied to clipboard');
}
</script>

<?php include 'layout_end.php'; ?>
