<?php
require_once 'config.php';
require_once 'lib/DB.php';
require_once 'lib/EmailGenerator.php';
require_once 'lib/KBMatcher.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: companies.php'); exit; }

$company = DB::fetchOne('SELECT * FROM companies WHERE id = ?', array($id));
if (!$company) { header('Location: companies.php'); exit; }

$signals = DB::fetchAll('SELECT * FROM signals WHERE company_id = ? ORDER BY created_at DESC', array($id));
$tech    = DB::fetchAll('SELECT * FROM company_tech WHERE company_id = ? ORDER BY confidence DESC', array($id));

try {
    $emails = DB::fetchAll('SELECT * FROM email_drafts WHERE company_id = ? ORDER BY id ASC', array($id));
} catch (Exception $e) {
    $emails = array();
}

$matchedService = null;
foreach ($emails as $em) {
    if (!empty($em['matched_service_id'])) {
        try {
            $matchedService = DB::fetchOne(
                'SELECT s.*, v.name as vertical_name FROM kb_services s LEFT JOIN kb_verticals v ON s.vertical_id=v.id WHERE s.id=?',
                array($em['matched_service_id'])
            );
        } catch (Exception $e) { /* kb_services may not exist yet */ }
        if ($matchedService) break;
    }
}

$priority  = strtolower($company['priority'] ?? 'low');
$newsSigs  = array_values(array_filter($signals, function($s) { return $s['source'] === 'GoogleNews'; }));
$jobSigs   = array_values(array_filter($signals, function($s) { return $s['source'] !== 'GoogleNews'; }));

$techByCategory = array();
foreach ($tech as $t) {
    $techByCategory[$t['category']][] = $t;
}

$scoreColor = $priority === 'high' ? 'var(--success)' : ($priority === 'medium' ? 'var(--warning)' : 'var(--muted)');

$maxTouch = 0;
foreach ($emails as $em) { if (($em['touch_number'] ?? 1) > $maxTouch) $maxTouch = $em['touch_number'] ?? 1; }
$nextTouch = $maxTouch + 1;

$currentPage = 'companies';

// Detect generation mode for this tenant
$generationMode = 'full';
try {
    $tenantId = Auth::tenantId();
    if ($tenantId) {
        $generationMode = EmailGenerator::detectMode((int)$tenantId);
    }
} catch (Exception $e) { /* graceful skip */ }

// Load top signals and ICPs for pickers
$topSignals = KBMatcher::topSignals($id, 5);
$topICPs    = $matchedService ? KBMatcher::matchICPs($matchedService, $company, 5) : array();

// Load AI settings for default num_touches
$aiSettingsForPage = array();
try {
    $aiSettingsForPage = DB::fetchOne('SELECT * FROM ai_settings LIMIT 1') ?: array();
} catch (Exception $e) {}
$defaultNumTouches = (int)($aiSettingsForPage['num_touches'] ?? 3);
if ($defaultNumTouches < 1) $defaultNumTouches = 3;
if ($defaultNumTouches > 5) $defaultNumTouches = 5;

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
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <button onclick="enrichNow()" class="btn btn-secondary" id="enrichBtn">&#9889; Re-Enrich</button>
    <button onclick="generateEmail(<?= $nextTouch ?>)" class="btn btn-primary" id="genBtnTop">&#10024; <?= $emails ? 'Generate Touch #'.$nextTouch : 'Generate Email' ?></button>
  </div>
</div>

<?php if ($generationMode === 'lite'): ?>
<div style="background:rgba(245,158,11,0.1);border:1px solid #f59e0b;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#fcd34d">
  &#9889; Running in <strong>Lite Mode</strong> &mdash; emails are generated without service matching.
  <a href="/knowledge.php" style="color:#f59e0b;text-decoration:underline">Fill your Knowledge Hub</a> to unlock service-matched, higher-quality emails.
</div>
<?php endif; ?>

<!-- Score Banner -->
<div class="score-banner" style="display:grid;grid-template-columns:140px 1fr;gap:0;background:var(--card);border:1px solid var(--border);border-radius:10px;padding:24px;margin-bottom:24px;align-items:center">
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
      <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;font-weight:600">Matched Service</div>
      <?php if ($matchedService): ?>
      <div style="font-size:12px;font-weight:600;margin-top:4px;color:var(--accent)"><?= htmlspecialchars($matchedService['name']) ?></div>
      <?php if ($matchedService['vertical_name']): ?>
      <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($matchedService['vertical_name']) ?></div>
      <?php endif; ?>
      <?php else: ?>
      <div style="font-size:13px;color:var(--muted);margin-top:4px">&mdash; not matched</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div id="enrichStatus" style="display:none;margin-bottom:16px;padding:12px 18px;border-radius:8px;font-size:13px"></div>
<div id="genStatusTop" style="display:none;margin-bottom:16px;padding:12px 18px;border-radius:8px;font-size:13px"></div>

<!-- Signal Picker -->
<?php if ($topSignals): ?>
<div class="card" style="margin-bottom:16px">
  <div style="padding:14px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">&#128268; Select Signals to Reference</div>
  <div style="padding:14px 20px;display:flex;flex-wrap:wrap;gap:12px">
  <?php foreach ($topSignals as $i => $sig): ?>
    <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-size:13px;background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:8px 12px;max-width:360px">
      <input type="checkbox" name="signal_ids[]" value="<?= $sig['id'] ?>" <?= $i === 0 ? 'checked' : '' ?> style="margin-top:2px">
      <span>
        <span style="font-size:10px;padding:2px 7px;border-radius:4px;background:rgba(99,102,241,0.15);color:#818cf8;margin-right:6px"><?= htmlspecialchars($sig['source'] ?? '') ?></span>
        <?= htmlspecialchars($sig['title'] ?? '') ?>
        <span style="display:block;font-size:11px;color:var(--muted);margin-top:2px"><?= htmlspecialchars(substr($sig['created_at'] ?? '', 0, 10)) ?></span>
      </span>
    </label>
  <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ICP / Persona Picker -->
<?php if ($topICPs): ?>
<div class="card" style="margin-bottom:16px">
  <div style="padding:14px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">&#128100; Select Buyer Persona</div>
  <div style="padding:14px 20px;display:flex;flex-wrap:wrap;gap:10px">
  <?php foreach ($topICPs as $i => $icp): ?>
    <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-size:13px;background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:8px 12px;max-width:320px">
      <input type="radio" name="selected_icp_id" value="<?= $icp['id'] ?>" <?= $i === 0 ? 'checked' : '' ?> style="margin-top:2px">
      <span>
        <strong><?= htmlspecialchars($icp['name']) ?></strong>
        <span style="font-size:10px;padding:2px 7px;border-radius:4px;background:rgba(34,197,94,0.12);color:var(--success);margin-left:6px">score <?= (int)($icp['_score'] ?? 0) ?></span>
        <?php if ($icp['industries']): ?>
        <span style="display:block;font-size:11px;color:var(--muted);margin-top:2px"><?= htmlspecialchars($icp['industries']) ?><?php if ($icp['size_range']): ?> &middot; <?= htmlspecialchars($icp['size_range']) ?><?php endif; ?></span>
        <?php endif; ?>
      </span>
    </label>
  <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Batch Email Sequence Generator -->
<div class="card" style="margin-bottom:16px;padding:16px 20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
  <button onclick="openSequenceModal()" class="btn btn-primary">&#10024; Generate Email Sequence</button>
  <span style="font-size:13px;color:var(--muted)">Generate multiple touches in sequence automatically</span>
</div>

<!-- Sequence Modal -->
<div id="seqModal" style="display:none;position:fixed;inset:0;z-index:1000;display:none">
  <div class="modal-backdrop" onclick="closeSequenceModal()" style="position:absolute;inset:0;background:rgba(0,0,0,0.6)"></div>
  <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:var(--card);border:1px solid var(--border);border-radius:12px;padding:28px;min-width:320px;z-index:1">
    <div style="font-size:15px;font-weight:600;margin-bottom:16px">Generate Email Sequence</div>
    <div class="form-group">
      <label>Number of touches (1–5)</label>
      <input type="number" id="seqTouches" min="1" max="5" value="<?= $defaultNumTouches ?>" style="width:80px">
    </div>
    <div id="seqProgress" style="margin:12px 0;font-size:13px;color:var(--muted)"></div>
    <div style="display:flex;gap:8px;margin-top:16px">
      <button onclick="runSequence()" class="btn btn-primary" id="seqBtn">Generate</button>
      <button onclick="closeSequenceModal()" class="btn btn-secondary">Cancel</button>
    </div>
  </div>
</div>

<!-- Refine Modal -->
<div id="refineModal" style="display:none;position:fixed;inset:0;z-index:1000">
  <div class="modal-backdrop" onclick="closeRefineModal()" style="position:absolute;inset:0;background:rgba(0,0,0,0.6)"></div>
  <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:var(--card);border:1px solid var(--border);border-radius:12px;padding:28px;min-width:380px;max-width:540px;width:90%;z-index:1">
    <div style="font-size:15px;font-weight:600;margin-bottom:16px">Refine Email</div>
    <input type="hidden" id="refineDraftId" value="">
    <div class="form-group">
      <label>Refinement instructions</label>
      <textarea id="refineInstructions" rows="4" placeholder="Make it shorter, focus on the SAP migration angle, change CTA to a free audit offer..."></textarea>
    </div>
    <div id="refineStatus" style="font-size:13px;margin:8px 0"></div>
    <div style="display:flex;gap:8px;margin-top:12px">
      <button onclick="submitRefine()" class="btn btn-primary" id="refineBtn">Refine</button>
      <button onclick="closeRefineModal()" class="btn btn-secondary">Cancel</button>
    </div>
  </div>
</div>

<!-- Email Drafts -->
<?php if ($emails): ?>
<div style="margin-bottom:24px">
  <?php foreach ($emails as $em): ?>
  <?php $emailStatus = $em['status'] ?? 'draft'; ?>
  <div class="card" style="margin-bottom:16px" id="emailCard<?= $em['id'] ?>">
    <div class="email-card-header" style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <span style="font-size:13px;font-weight:600">&#9993; Touch #<?= $em['touch_number'] ?? 1 ?></span>
        <?php if (!empty($em['ai_provider'])): ?>
        <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:4px;background:rgba(99,102,241,0.15);color:#818cf8;letter-spacing:.05em"><?= strtoupper(htmlspecialchars($em['ai_provider'])) ?></span>
        <?php endif; ?>
        <?php if (!empty($em['matched_service_id']) && $matchedService): ?>
        <span style="font-size:11px;color:var(--muted)">&#8227; <?= htmlspecialchars($matchedService['name']) ?><?php if($matchedService['vertical_name']): ?> &middot; <?= htmlspecialchars($matchedService['vertical_name']) ?><?php endif; ?></span>
        <?php endif; ?>
        <?php $emMode = $em['generation_mode'] ?? 'full'; ?>
        <?php if ($emMode === 'lite'): ?>
        <span class="badge badge-warning" style="font-size:10px;font-weight:700;letter-spacing:.05em">LITE MODE</span>
        <a href="/knowledge.php" style="font-size:11px;color:var(--warning);text-decoration:none">&rarr; Fill Knowledge Hub</a>
        <?php else: ?>
        <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:4px;background:rgba(34,197,94,0.12);color:var(--success);letter-spacing:.05em">KB-MATCHED</span>
        <?php endif; ?>
        <?php if ($emailStatus === 'sent'): ?>
        <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px;background:rgba(34,197,94,0.12);color:var(--success)">&#10003; Sent</span>
        <?php elseif ($emailStatus === 'replied'): ?>
        <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px;background:rgba(99,102,241,0.15);color:#818cf8">&#8617; Replied</span>
        <?php endif; ?>
      </div>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span style="font-size:11px;color:var(--muted)"><?= date('d M Y', strtotime($em['created_at'])) ?></span>
        <button onclick="copyEmail(<?= $em['id'] ?>)" class="btn btn-secondary btn-sm">&#128203; Copy</button>
        <a href="mailto:?subject=<?= urlencode($em['subject']) ?>&body=<?= urlencode($em['body']) ?>" class="btn btn-ghost btn-sm">&#128232; Mail</a>
        <button onclick="openRefineModal(<?= $em['id'] ?>)" class="btn btn-ghost btn-sm" style="color:#818cf8">&#9998; Refine</button>
        <button onclick="deleteEmail(<?= $em['id'] ?>)" class="btn btn-ghost btn-sm" style="color:var(--danger)">&#128465; Delete</button>
        <?php if ($emailStatus === 'draft'): ?>
        <button onclick="markEmail(<?= $em['id'] ?>, 'sent')" class="btn btn-ghost btn-sm" style="color:var(--success)">&#10003; Sent</button>
        <?php elseif ($emailStatus === 'sent'): ?>
        <button onclick="markEmail(<?= $em['id'] ?>, 'replied')" class="btn btn-ghost btn-sm">&#8617; Replied</button>
        <?php endif; ?>
      </div>
    </div>
    <div class="email-body-grid" style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:20px">
      <div>
        <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;font-weight:600;margin-bottom:8px">Subject</div>
        <div style="font-size:14px;font-weight:600;line-height:1.4;color:var(--text)" class="email-subject-text"><?= htmlspecialchars($em['subject']) ?></div>
      </div>
      <div>
        <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;font-weight:600;margin-bottom:8px">Body</div>
        <textarea id="emailBody<?= $em['id'] ?>" rows="10" style="width:100%;font-size:12px;line-height:1.7;resize:vertical;background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:10px;color:var(--text)"><?= htmlspecialchars($em['body']) ?></textarea>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <div style="background:var(--card);border:1px dashed var(--border);border-radius:10px;padding:20px;text-align:center">
    <div style="font-size:13px;color:var(--muted);margin-bottom:12px">Generate next follow-up with AI</div>
    <button onclick="generateEmail(<?= $nextTouch ?>)" class="btn btn-primary" id="genBtn">&#10024; Generate Touch #<?= $nextTouch ?></button>
    <div id="genStatus" style="margin-top:12px;font-size:13px"></div>
  </div>
</div>
<?php else: ?>
<div class="card" style="margin-bottom:24px;padding:28px;text-align:center">
  <div style="font-size:24px;margin-bottom:8px">&#9993;</div>
  <div style="font-size:15px;font-weight:600;margin-bottom:6px">No email draft yet</div>
  <div style="font-size:13px;color:var(--muted);margin-bottom:20px">Generate a cold email using your Knowledge Base &amp; AI, or re-enrich first to refresh signals.</div>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
    <button onclick="generateEmail(1)" class="btn btn-primary" id="genBtn">&#10024; Generate Email Now</button>
    <button onclick="enrichNow()" class="btn btn-secondary">&#9889; Re-Enrich First, Then Generate</button>
  </div>
  <div id="genStatus" style="margin-top:16px;font-size:13px"></div>
</div>
<?php endif; ?>

<div class="detail-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

<div style="display:flex;flex-direction:column;gap:20px">
  <div class="card">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600;display:flex;justify-content:space-between;align-items:center">
      <span>&#128736; Detected Tech Stack</span>
      <button onclick="togglePasteBox()" class="btn btn-secondary btn-sm">+ Paste Job Description</button>
    </div>
    <div id="pasteBox" style="display:none;padding:16px 20px;border-bottom:1px solid var(--border);background:rgba(0,0,0,0.2)">
      <div style="font-size:12px;color:var(--muted);margin-bottom:10px">Paste a job description &mdash; we'll extract the tools automatically.</div>
      <div class="form-group"><label>Job Title (optional)</label><input type="text" id="pasteTitle" placeholder="e.g. SAP Consultant"></div>
      <div class="form-group"><label>Job URL (optional)</label><input type="url" id="pasteUrl"></div>
      <div class="form-group"><label>Job Description Text *</label><textarea id="pasteText" rows="7"></textarea></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
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
              <?php if ($t['source_title']): ?>
              <div style="font-size:11px;color:var(--muted);margin-top:2px">Source: &ldquo;<?= htmlspecialchars(substr($t['source_title'], 0, 60)) ?>&rdquo;</div>
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
        <div style="color:var(--muted);font-size:13px;line-height:1.8">No tools detected yet.<br><strong style="color:var(--text)">Try:</strong> Click <em>Re-Enrich</em> or paste a job description above.</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">&#128188; Job Postings <span style="color:var(--muted);font-weight:400">(<?= count($jobSigs) ?> fetched)</span></div>
    <?php if ($jobSigs): ?>
    <div>
      <?php foreach ($jobSigs as $sig): ?>
      <div style="padding:12px 20px;border-bottom:1px solid rgba(42,45,58,0.4)">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px;flex-wrap:wrap">
          <a href="<?= htmlspecialchars($sig['url']) ?>" target="_blank" style="color:var(--text);font-size:13px;font-weight:500;text-decoration:none"><?= htmlspecialchars($sig['title']) ?></a>
          <span style="font-size:10px;color:var(--muted);background:rgba(255,255,255,0.05);padding:1px 6px;border-radius:4px;white-space:nowrap"><?= htmlspecialchars($sig['source']) ?></span>
        </div>
        <div style="color:var(--muted);font-size:11px"><?= htmlspecialchars($sig['published_date']) ?></div>
        <?php if ($sig['snippet']): ?>
        <div style="color:var(--muted);font-size:12px;margin-top:4px;line-height:1.5"><?= htmlspecialchars(substr($sig['snippet'], 0, 200)) ?>...</div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="padding:20px;color:var(--muted);font-size:13px">No job postings fetched. Try <strong style="color:var(--text)">Re-Enrich</strong>.</div>
    <?php endif; ?>
  </div>
</div>

<div style="display:flex;flex-direction:column;gap:20px">
  <div class="card">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">&#128240; News &amp; Signals <span style="color:var(--muted);font-weight:400">(<?= count($newsSigs) ?> found)</span></div>
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
    <div style="padding:20px;color:var(--muted);font-size:13px">No news signals found. Try re-enriching.</div>
    <?php endif; ?>
  </div>
</div>
</div>

<script>
var companyId = <?= $id ?>;

function showEnrichStatus(html, isError) {
  var el = document.getElementById('enrichStatus');
  el.style.display = 'block';
  el.style.background = isError ? 'rgba(239,68,68,0.1)' : 'rgba(34,197,94,0.08)';
  el.style.border = '1px solid ' + (isError ? 'rgba(239,68,68,0.4)' : 'rgba(34,197,94,0.3)');
  el.style.color = isError ? '#ef4444' : 'var(--text)';
  el.innerHTML = html;
}

function togglePasteBox() {
  var box = document.getElementById('pasteBox');
  box.style.display = box.style.display === 'none' ? 'block' : 'none';
}

async function markEmail(id, status) {
  var fd = new FormData();
  fd.append('id', id);
  fd.append('status', status);
  try {
    await fetch('api/email.php', {method:'POST', body:fd});
    location.reload();
  } catch(e) { alert('Failed to update status: ' + e.message); }
}

async function extractFromPaste() {
  var text = document.getElementById('pasteText').value.trim();
  if (!text) { alert('Please paste a job description first'); return; }
  var btn = document.getElementById('extractBtn');
  btn.textContent = 'Extracting...';
  btn.disabled = true;
  var fd = new FormData();
  fd.append('company_id', companyId);
  fd.append('text', text);
  fd.append('source_title', document.getElementById('pasteTitle').value || 'Manual paste');
  fd.append('source_url', document.getElementById('pasteUrl').value || '');
  try {
    var r = await fetch('api/extract_tech.php', {method:'POST', body:fd});
    var d = await r.json();
    var result = document.getElementById('extractResult');
    if (d.ok) {
      if (d.found && d.found.length > 0) {
        result.innerHTML = '<span style="color:var(--success)">&#10003; Found: <strong>' + d.found.join(', ') + '</strong>. Reloading...</span>';
        setTimeout(function(){ location.reload(); }, 1500);
      } else {
        result.innerHTML = '<span style="color:var(--warning)">No known tools found. Try pasting the full job description.</span>';
        btn.textContent = 'Extract Tech Stack'; btn.disabled = false;
      }
    } else {
      result.innerHTML = '<span style="color:var(--danger)">Error: ' + (d.error || 'unknown') + '</span>';
      btn.textContent = 'Extract Tech Stack'; btn.disabled = false;
    }
  } catch(e) {
    document.getElementById('extractResult').innerHTML = '<span style="color:var(--danger)">Request failed: ' + e.message + '</span>';
    btn.textContent = 'Extract Tech Stack'; btn.disabled = false;
  }
}

async function enrichNow() {
  var btn = document.getElementById('enrichBtn');
  btn.textContent = 'Enriching...';
  btn.disabled = true;
  showEnrichStatus('<span style="color:var(--muted)">Fetching signals... this may take 15-30 seconds.</span>', false);
  try {
    var r = await fetch('api/enrich.php?id=' + companyId, {method:'POST'});
    var text = await r.text();
    var d;
    try { d = JSON.parse(text); } catch(e) {
      showEnrichStatus('PHP error: <pre style="font-size:11px;white-space:pre-wrap;margin:6px 0 0">' + text.substring(0, 800) + '</pre>', true);
      btn.textContent = '&#9889; Re-Enrich'; btn.disabled = false; return;
    }
    if (d.ok) {
      var msg = '&#10003; Enriched! Score: <strong>' + d.score + '</strong> (' + d.priority + '). News: ' + d.news_count + ', Jobs: ' + d.jobs_count + ', Tech: ' + d.tech_found;
      if (d.email_generated) msg += ' &middot; &#10024; Email auto-generated';
      msg += '. Reloading...';
      showEnrichStatus(msg, false);
      setTimeout(function(){ location.reload(); }, 2200);
    } else {
      showEnrichStatus('Error: ' + (d.error || 'unknown error'), true);
      btn.textContent = '&#9889; Re-Enrich'; btn.disabled = false;
    }
  } catch(e) {
    showEnrichStatus('Network error: ' + e.message, true);
    btn.textContent = '&#9889; Re-Enrich'; btn.disabled = false;
  }
}

async function generateEmail(touchNumber) {
  var btn = document.getElementById('genBtn');
  var btnTop = document.getElementById('genBtnTop');
  var status = document.getElementById('genStatus');
  var statusTop = document.getElementById('genStatusTop');
  if (btn) { btn.disabled = true; btn.textContent = 'Generating...'; }
  if (btnTop) { btnTop.disabled = true; btnTop.textContent = 'Generating...'; }
  if (status) status.innerHTML = '<span style="color:var(--muted)">Calling AI provider...</span>';
  if (statusTop) { statusTop.style.display='block'; statusTop.style.background='rgba(99,102,241,0.08)'; statusTop.style.border='1px solid rgba(99,102,241,0.3)'; statusTop.style.color='var(--muted)'; statusTop.textContent='Calling AI provider...'; }
  try {
    var r = await fetch('api/generate_email.php?company_id=' + companyId + '&touch_number=' + touchNumber);
    var text = await r.text();
    var d;
    try { d = JSON.parse(text); } catch(e) {
      var err = 'PHP error: ' + text.substring(0, 400);
      if (status) status.innerHTML = '<span style="color:var(--danger)">' + err + '</span>';
      if (statusTop) { statusTop.style.background='rgba(239,68,68,0.1)'; statusTop.style.border='1px solid rgba(239,68,68,0.4)'; statusTop.style.color='#ef4444'; statusTop.textContent=err; }
      if (btn) { btn.disabled=false; btn.textContent='&#10024; Generate Touch #'+touchNumber; }
      if (btnTop) { btnTop.disabled=false; btnTop.textContent='&#10024; '+(touchNumber===1?'Generate Email':'Generate Touch #'+touchNumber); }
      return;
    }
    if (d.ok) {
      var msg = '&#10003; Touch #' + d.touch_number + ' generated via ' + d.provider.toUpperCase();
      if (d.matched_service) msg += ' &middot; Service: ' + d.matched_service;
      msg += '. Reloading...';
      if (status) status.innerHTML = '<span style="color:var(--success)">' + msg + '</span>';
      if (statusTop) { statusTop.style.background='rgba(34,197,94,0.1)'; statusTop.style.border='1px solid rgba(34,197,94,0.4)'; statusTop.style.color='#22c55e'; statusTop.innerHTML=msg; }
      setTimeout(function(){ location.reload(); }, 1800);
    } else {
      var err = d.error || 'Generation failed. Check AI settings at /settings.php';
      if (status) status.innerHTML = '<span style="color:var(--danger)">' + err + '</span>';
      if (statusTop) { statusTop.style.background='rgba(239,68,68,0.1)'; statusTop.style.border='1px solid rgba(239,68,68,0.4)'; statusTop.style.color='#ef4444'; statusTop.textContent=err; }
      if (btn) { btn.disabled=false; btn.textContent='&#10024; Generate Touch #'+touchNumber; }
      if (btnTop) { btnTop.disabled=false; btnTop.textContent='&#10024; '+(touchNumber===1?'Generate Email':'Generate Touch #'+touchNumber); }
    }
  } catch(e) {
    var err = 'Network error: ' + e.message;
    if (status) status.innerHTML = '<span style="color:var(--danger)">' + err + '</span>';
    if (statusTop) { statusTop.style.background='rgba(239,68,68,0.1)'; statusTop.style.border='1px solid rgba(239,68,68,0.4)'; statusTop.style.color='#ef4444'; statusTop.textContent=err; }
    if (btn) { btn.disabled=false; btn.textContent='&#10024; Generate Touch #'+touchNumber; }
    if (btnTop) { btnTop.disabled=false; btnTop.textContent='&#10024; '+(touchNumber===1?'Generate Email':'Generate Touch #'+touchNumber); }
  }
}

function copyEmail(id) {
  var el = document.getElementById('emailBody' + id);
  el.select();
  document.execCommand('copy');
}

// --- Refine ---
function openRefineModal(draftId) {
  document.getElementById('refineDraftId').value = draftId;
  document.getElementById('refineInstructions').value = '';
  document.getElementById('refineStatus').textContent = '';
  document.getElementById('refineModal').style.display = 'block';
}
function closeRefineModal() {
  document.getElementById('refineModal').style.display = 'none';
}
async function submitRefine() {
  var draftId = document.getElementById('refineDraftId').value;
  var instructions = document.getElementById('refineInstructions').value.trim();
  if (!instructions) { alert('Please enter refinement instructions.'); return; }
  var btn = document.getElementById('refineBtn');
  btn.disabled = true; btn.textContent = 'Refining...';
  document.getElementById('refineStatus').innerHTML = '<span style="color:var(--muted)">Calling AI provider...</span>';
  try {
    var fd = new FormData();
    fd.append('action', 'refine');
    fd.append('draft_id', draftId);
    fd.append('instructions', instructions);
    var r = await fetch('api/email.php', {method:'POST', body:fd});
    var d = await r.json();
    if (d.ok) {
      // Update subject and body in-page
      var card = document.getElementById('emailCard' + draftId);
      if (card) {
        var subjectEl = card.querySelector('.email-subject-text');
        if (subjectEl) subjectEl.textContent = d.subject;
        var bodyEl = document.getElementById('emailBody' + draftId);
        if (bodyEl) bodyEl.value = d.body;
      }
      closeRefineModal();
    } else {
      document.getElementById('refineStatus').innerHTML = '<span style="color:var(--danger)">Error: ' + (d.error || 'Refine failed') + '</span>';
      btn.disabled = false; btn.textContent = 'Refine';
    }
  } catch(e) {
    document.getElementById('refineStatus').innerHTML = '<span style="color:var(--danger)">Network error: ' + e.message + '</span>';
    btn.disabled = false; btn.textContent = 'Refine';
  }
}

// --- Delete ---
async function deleteEmail(draftId) {
  if (!confirm('Delete this email draft? This cannot be undone.')) return;
  try {
    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('draft_id', draftId);
    var r = await fetch('api/email.php', {method:'POST', body:fd});
    var d = await r.json();
    if (d.ok) {
      var card = document.getElementById('emailCard' + draftId);
      if (card) card.remove();
    } else {
      alert('Delete failed: ' + (d.error || 'unknown error'));
    }
  } catch(e) { alert('Network error: ' + e.message); }
}

// --- Sequence Generator ---
function openSequenceModal() {
  document.getElementById('seqModal').style.display = 'block';
  document.getElementById('seqProgress').textContent = '';
  var btn = document.getElementById('seqBtn');
  btn.disabled = false; btn.textContent = 'Generate';
}
function closeSequenceModal() {
  document.getElementById('seqModal').style.display = 'none';
}
async function runSequence() {
  var n = parseInt(document.getElementById('seqTouches').value) || 3;
  if (n < 1) n = 1;
  if (n > 5) n = 5;
  var btn = document.getElementById('seqBtn');
  btn.disabled = true;
  var startTouch = <?= $nextTouch ?>;
  for (var i = 0; i < n; i++) {
    var touch = startTouch + i;
    document.getElementById('seqProgress').innerHTML = '<span style="color:var(--muted)">Generating touch ' + (i+1) + ' of ' + n + '...</span>';
    try {
      var r = await fetch('api/generate_email.php?company_id=' + companyId + '&touch_number=' + touch);
      var d = await r.json();
      if (!d.ok) {
        document.getElementById('seqProgress').innerHTML = '<span style="color:var(--danger)">Error on touch ' + touch + ': ' + (d.error || 'failed') + '</span>';
        btn.disabled = false;
        return;
      }
    } catch(e) {
      document.getElementById('seqProgress').innerHTML = '<span style="color:var(--danger)">Network error: ' + e.message + '</span>';
      btn.disabled = false;
      return;
    }
  }
  document.getElementById('seqProgress').innerHTML = '<span style="color:var(--success)">&#10003; All ' + n + ' touches generated. Reloading...</span>';
  setTimeout(function(){ location.reload(); }, 1500);
}
</script>

<?php include 'layout_end.php'; ?>
