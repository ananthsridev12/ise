<?php
require_once 'config.php';
require_once 'lib/DB.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: companies.php'); exit; }

$company = DB::fetchOne('SELECT * FROM companies WHERE id = ?', array($id));
if (!$company) { header('Location: companies.php'); exit; }

$signals = DB::fetchAll('SELECT * FROM signals WHERE company_id = ? ORDER BY created_at DESC', array($id));
$tech    = DB::fetchAll('SELECT * FROM company_tech WHERE company_id = ? ORDER BY confidence DESC', array($id));
$emails  = DB::fetchAll('SELECT ed.*, s.name as service_name FROM email_drafts ed LEFT JOIN kb_services s ON ed.matched_service_id = s.id WHERE ed.company_id = ? ORDER BY ed.touch_number ASC, ed.id DESC', array($id));
$email   = $emails ? $emails[0] : null;

$aiSettings = DB::fetchOne('SELECT num_touches FROM ai_settings LIMIT 1') ?: array();
$maxTouches = (int)($aiSettings['num_touches'] ?? 3);
$nextTouch  = count($emails) + 1;

$priority  = strtolower($company['priority'] ?? 'low');
$newsSigs  = array_values(array_filter($signals, function($s) { return $s['source'] === 'GoogleNews'; }));
$jobSigs   = array_values(array_filter($signals, function($s) { return $s['source'] !== 'GoogleNews'; }));

$techByCategory = array();
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
    <a href="outreach.php?company=<?= $id ?>" class="btn btn-primary">&#9993; View Email Drafts</a>
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
      <?php if ($email && $email['service_name']): ?>
      <div style="font-size:11px;color:var(--accent);margin-top:4px">Matched: <?= htmlspecialchars($email['service_name']) ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div id="enrichStatus" style="display:none;margin-bottom:16px" class="card card-body"></div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

<!-- LEFT COLUMN -->
<div style="display:flex;flex-direction:column;gap:20px">

  <!-- Tech Stack -->
  <div class="card">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600;display:flex;justify-content:space-between;align-items:center">
      <span>&#128736; Detected Tech Stack</span>
      <button onclick="togglePasteBox()" class="btn btn-secondary btn-sm">+ Paste Job Description</button>
    </div>
    <div id="pasteBox" style="display:none;padding:16px 20px;border-bottom:1px solid var(--border);background:rgba(0,0,0,0.2)">
      <div style="font-size:12px;color:var(--muted);margin-bottom:10px">Paste a job description &mdash; we'll extract tools automatically.</div>
      <div class="form-group"><label>Job Title (optional)</label><input type="text" id="pasteTitle" placeholder="e.g. SAP Consultant"></div>
      <div class="form-group"><label>Job URL (optional)</label><input type="url" id="pasteUrl" placeholder="https://..."></div>
      <div class="form-group"><label>Job Description Text *</label><textarea id="pasteText" rows="7" placeholder="Paste here..."></textarea></div>
      <div style="display:flex;gap:8px">
        <button onclick="extractFromPaste()" class="btn btn-primary btn-sm" id="extractBtn">Extract Tech Stack</button>
        <button onclick="togglePasteBox()" class="btn btn-ghost btn-sm">Cancel</button>
      </div>
      <div id="extractResult" style="margin-top:10px;font-size:13px"></div>
    </div>
    <div style="padding:16px 20px">
      <?php if ($tech): ?>
        <?php foreach ($techByCategory as $cat => $tools): ?>
        <div style="margin-bottom:16px">
          <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;font-weight:700;margin-bottom:8px"><?= htmlspecialchars($cat) ?></div>
          <?php foreach ($tools as $t): ?>
          <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(42,45,58,0.4)">
            <div>
              <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($t['tool']) ?></div>
              <?php if ($t['source_title']): ?><div style="font-size:11px;color:var(--muted);margin-top:2px">Source: &ldquo;<?= htmlspecialchars(substr($t['source_title'], 0, 60)) ?>&rdquo;</div><?php endif; ?>
              <?php if ($t['source_url']): ?><a href="<?= htmlspecialchars($t['source_url']) ?>" target="_blank" style="font-size:11px;color:var(--accent)">View source &rarr;</a><?php endif; ?>
            </div>
            <span style="font-size:11px;color:<?= $t['confidence'] >= 90 ? 'var(--success)' : 'var(--warning)' ?>;white-space:nowrap;margin-left:12px"><?= $t['confidence'] ?>% match</span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="color:var(--muted);font-size:13px;line-height:1.8">No tools detected yet.<br><strong style="color:var(--text)">Try:</strong> Click <em>Re-Enrich</em> or paste a job description above.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Job Postings -->
  <div class="card">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">&#128188; Job Postings <span style="color:var(--muted);font-weight:400">(<?= count($jobSigs) ?> fetched)</span></div>
    <?php if ($jobSigs): ?>
    <div>
      <?php foreach ($jobSigs as $sig): ?>
      <div style="padding:12px 20px;border-bottom:1px solid rgba(42,45,58,0.4)">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px">
          <a href="<?= htmlspecialchars($sig['url']) ?>" target="_blank" style="color:var(--text);font-size:13px;font-weight:500;text-decoration:none"><?= htmlspecialchars($sig['title']) ?></a>
          <span style="font-size:10px;color:var(--muted);background:rgba(255,255,255,0.05);padding:1px 6px;border-radius:4px"><?= htmlspecialchars($sig['source']) ?></span>
        </div>
        <div style="color:var(--muted);font-size:11px"><?= htmlspecialchars($sig['published_date']) ?></div>
        <?php if ($sig['snippet']): ?><div style="color:var(--muted);font-size:12px;margin-top:4px;line-height:1.5"><?= htmlspecialchars(substr($sig['snippet'], 0, 200)) ?>...</div><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="padding:20px;color:var(--muted);font-size:13px">No job postings fetched. Try <strong style="color:var(--text)">Re-Enrich</strong> or use the paste feature.</div>
    <?php endif; ?>
  </div>

</div>

<!-- RIGHT COLUMN -->
<div style="display:flex;flex-direction:column;gap:20px">

  <!-- News Signals -->
  <div class="card">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">&#128240; News &amp; Signals <span style="color:var(--muted);font-weight:400">(<?= count($newsSigs) ?> found)</span></div>
    <?php if ($newsSigs): ?>
    <div>
      <?php foreach ($newsSigs as $sig): ?>
      <div style="padding:12px 20px;border-bottom:1px solid rgba(42,45,58,0.4)">
        <a href="<?= htmlspecialchars($sig['url']) ?>" target="_blank" style="color:var(--text);font-size:13px;font-weight:500;text-decoration:none;line-height:1.4;display:block"><?= htmlspecialchars($sig['title']) ?></a>
        <div style="color:var(--muted);font-size:11px;margin-top:4px"><?= htmlspecialchars($sig['published_date']) ?></div>
        <?php if ($sig['snippet']): ?><div style="color:var(--muted);font-size:12px;margin-top:4px;line-height:1.5"><?= htmlspecialchars(substr($sig['snippet'], 0, 220)) ?>...</div><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="padding:20px;color:var(--muted);font-size:13px">No news signals found. Try re-enriching or check the company name spelling.</div>
    <?php endif; ?>
  </div>

  <!-- Email Drafts -->
  <?php if ($emails): ?>
  <?php foreach ($emails as $i => $em): ?>
  <div class="card">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600;display:flex;justify-content:space-between;align-items:center">
      <span>&#9993; Email Draft &mdash; Touch #<?= $em['touch_number'] ?></span>
      <div style="display:flex;gap:8px;align-items:center">
        <?php if ($em['ai_provider']): ?><span style="font-size:10px;background:rgba(99,102,241,0.15);color:var(--accent);padding:2px 7px;border-radius:4px"><?= htmlspecialchars(strtoupper($em['ai_provider'])) ?></span><?php endif; ?>
        <?php if ($em['service_name']): ?><span style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($em['service_name']) ?></span><?php endif; ?>
      </div>
    </div>
    <div style="padding:16px 20px">
      <div style="font-size:11px;color:var(--muted);margin-bottom:4px">SUBJECT</div>
      <div style="font-size:13px;font-weight:600;margin-bottom:14px;line-height:1.4"><?= htmlspecialchars($em['subject']) ?></div>
      <div style="font-size:11px;color:var(--muted);margin-bottom:4px">BODY</div>
      <textarea id="emailBody<?= $i ?>" rows="10" style="width:100%;font-size:12px;line-height:1.7;resize:vertical"><?= htmlspecialchars($em['body']) ?></textarea>
      <div style="display:flex;gap:8px;margin-top:10px">
        <button onclick="copyEmail(<?= $i ?>)" class="btn btn-secondary btn-sm">&#128203; Copy</button>
        <a href="mailto:?subject=<?= urlencode($em['subject']) ?>&body=<?= urlencode($em['body']) ?>" class="btn btn-primary btn-sm">&#128232; Open in Mail</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

  <!-- Generate Follow-up -->
  <?php if ($nextTouch <= $maxTouches): ?>
  <div class="card">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">&#43; Generate Touch #<?= $nextTouch ?></div>
    <div style="padding:16px 20px">
      <div style="font-size:13px;color:var(--muted);margin-bottom:14px">
        <?php if (!$emails): ?>Generate a first AI email for this company.<?php else: ?>Generate follow-up #<?= $nextTouch ?> referencing touch #<?= $nextTouch - 1 ?>.<?php endif; ?>
      </div>
      <button onclick="generateEmail(<?= $nextTouch ?>)" class="btn btn-primary" id="genBtn">&#9889; Generate Email</button>
      <div id="genResult" style="margin-top:12px;font-size:13px"></div>
    </div>
  </div>
  <?php endif; ?>

</div>
</div>

<script>
function togglePasteBox() {
  var box = document.getElementById('pasteBox');
  box.style.display = box.style.display === 'none' ? 'block' : 'none';
}

async function extractFromPaste() {
  var text = document.getElementById('pasteText').value.trim();
  if (!text) { alert('Please paste a job description first'); return; }
  var btn = document.getElementById('extractBtn');
  btn.textContent = 'Extracting...';
  btn.disabled = true;
  var fd = new FormData();
  fd.append('company_id', '<?= $id ?>');
  fd.append('text', text);
  fd.append('source_title', document.getElementById('pasteTitle').value || 'Manual paste');
  fd.append('source_url',   document.getElementById('pasteUrl').value || '');
  var r = await fetch('api/extract_tech.php', {method: 'POST', body: fd});
  var d = await r.json();
  var result = document.getElementById('extractResult');
  if (d.ok) {
    if (d.found && d.found.length > 0) {
      result.innerHTML = '<span style="color:var(--success)">&#10003; Found: <strong>' + d.found.join(', ') + '</strong>. Reloading...</span>';
      setTimeout(function(){ location.reload(); }, 1500);
    } else {
      result.innerHTML = '<span style="color:var(--warning)">No known tools found in this text.</span>';
      btn.textContent = 'Extract Tech Stack';
      btn.disabled = false;
    }
  } else {
    result.innerHTML = '<span style="color:var(--danger)">Error: ' + (d.error || 'unknown') + '</span>';
    btn.textContent = 'Extract Tech Stack';
    btn.disabled = false;
  }
}

async function enrichNow() {
  var btn = document.getElementById('enrichBtn');
  var status = document.getElementById('enrichStatus');
  btn.textContent = 'Enriching...';
  btn.disabled = true;
  status.style.display = 'block';
  status.innerHTML = '<span style="color:var(--muted)">Fetching signals from Google News + Adzuna Jobs... this may take 15-20 seconds.</span>';
  var r = await fetch('api/enrich.php?id=<?= $id ?>', {method: 'POST'});
  var d = await r.json();
  if (d.ok) {
    var aiNote = d.ai_used ? ' (AI: ' + (d.ai_provider || 'configured') + ')' : ' (template)';
    var svcNote = d.matched_service ? ' Service: ' + d.matched_service + '.' : '';
    var msg = '&#10003; Done! Score: ' + d.score + ' (' + d.priority + '). News: ' + d.news_count + ', Jobs: ' + d.jobs_count + ', Tech: ' + d.tech_found + '.' + svcNote + aiNote + ' Reloading...';
    status.innerHTML = '<span style="color:var(--success)">' + msg + '</span>';
    setTimeout(function(){ location.reload(); }, 2000);
  } else {
    status.innerHTML = '<span style="color:var(--danger)">Error: ' + (d.error || 'unknown error') + '</span>';
    btn.textContent = '&#9889; Re-Enrich';
    btn.disabled = false;
  }
}

async function generateEmail(touchNumber) {
  var btn = document.getElementById('genBtn');
  var result = document.getElementById('genResult');
  btn.textContent = 'Generating...';
  btn.disabled = true;
  result.innerHTML = '<span style="color:var(--muted)">Calling AI... this may take 10-15 seconds.</span>';
  var fd = new FormData();
  fd.append('company_id', '<?= $id ?>');
  fd.append('touch_number', touchNumber);
  var r = await fetch('api/generate_email.php', {method: 'POST', body: fd});
  var d = await r.json();
  if (d.ok) {
    result.innerHTML = '<span style="color:var(--success)">&#10003; Touch #' + d.touch_number + ' generated via ' + d.provider + '. Reloading...</span>';
    setTimeout(function(){ location.reload(); }, 1500);
  } else {
    result.innerHTML = '<span style="color:var(--danger)">Error: ' + (d.error || 'unknown') + '</span>';
    btn.textContent = '&#9889; Generate Email';
    btn.disabled = false;
  }
}

function copyEmail(i) {
  var el = document.getElementById('emailBody' + i);
  el.select();
  document.execCommand('copy');
}
</script>

<?php include 'layout_end.php'; ?>
