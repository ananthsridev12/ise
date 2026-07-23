<?php
require_once 'config.php';
require_once 'lib/DB.php';

$tab = $_GET['tab'] ?? 'company';
$allowedTabs = array('company','verticals','services','icps','tone','senders');
if (!in_array($tab, $allowedTabs)) $tab = 'company';

$kbCompany = DB::fetchOne('SELECT * FROM kb_company LIMIT 1') ?: array();
$kbTone    = DB::fetchOne('SELECT * FROM kb_tone LIMIT 1') ?: array();
$verticals = DB::fetchAll('SELECT * FROM kb_verticals ORDER BY name') ?: array();
$services  = DB::fetchAll('SELECT s.*, v.name as vertical_name FROM kb_services s LEFT JOIN kb_verticals v ON s.vertical_id = v.id ORDER BY s.name') ?: array();
$icps      = DB::fetchAll('SELECT i.*, s.name as service_name, v.name as vertical_name FROM kb_icps i LEFT JOIN kb_services s ON i.service_id = s.id LEFT JOIN kb_verticals v ON i.vertical_id = v.id ORDER BY i.name') ?: array();
$senders   = DB::fetchAll('SELECT * FROM kb_senders ORDER BY is_default DESC, full_name') ?: array();

include 'layout.php';

$tabs = array(
    'company'   => '&#127970; Our Company',
    'verticals' => '&#127970; Verticals',
    'services'  => '&#9881; Services',
    'icps'      => '&#127919; ICPs',
    'tone'      => '&#127908; Tone & Voice',
    'senders'   => '&#128100; Senders',
);
?>

<div class="page-header">
  <div>
    <div class="page-title">&#128218; Knowledge Hub</div>
    <div class="page-sub">SolidPro's GTM context &mdash; used to personalise every AI-generated email</div>
  </div>
</div>

<div style="display:flex;gap:4px;margin-bottom:24px;border-bottom:1px solid var(--border);padding-bottom:0">
<?php foreach ($tabs as $key => $label): ?>
  <a href="?tab=<?= $key ?>" style="padding:8px 16px;font-size:13px;font-weight:500;color:<?= $tab===$key ? 'var(--accent)' : 'var(--muted)' ?>;text-decoration:none;border-bottom:2px solid <?= $tab===$key ? 'var(--accent)' : 'transparent' ?>;white-space:nowrap"><?= $label ?></a>
<?php endforeach; ?>
</div>

<div id="kbMsg" style="display:none;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px"></div>

<?php if ($tab === 'company'): ?>
<!-- COMPANY -->
<form id="kbForm" style="max-width:720px">
  <input type="hidden" name="action" value="save_company">
  <div class="card" style="margin-bottom:20px">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Company Identity</div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:14px">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group" style="margin:0">
          <label>Company Name</label>
          <input type="text" name="name" value="<?= htmlspecialchars($kbCompany['name'] ?? '') ?>" placeholder="SolidPro">
        </div>
        <div class="form-group" style="margin:0">
          <label>Website</label>
          <input type="text" name="website" value="<?= htmlspecialchars($kbCompany['website'] ?? '') ?>" placeholder="https://solidpro.com">
        </div>
      </div>
      <div class="form-group" style="margin:0">
        <label>Tagline</label>
        <input type="text" name="tagline" value="<?= htmlspecialchars($kbCompany['tagline'] ?? '') ?>" placeholder="One-line company pitch">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px">
        <div class="form-group" style="margin:0"><label>Founded Year</label><input type="text" name="founded_year" value="<?= htmlspecialchars($kbCompany['founded_year'] ?? '') ?>" placeholder="2010"></div>
        <div class="form-group" style="margin:0"><label>Company Size</label><input type="text" name="size" value="<?= htmlspecialchars($kbCompany['size'] ?? '') ?>" placeholder="50-200 employees"></div>
        <div class="form-group" style="margin:0"><label>Headquarters</label><input type="text" name="hq" value="<?= htmlspecialchars($kbCompany['hq'] ?? '') ?>" placeholder="London, UK"></div>
      </div>
      <div class="form-group" style="margin:0">
        <label>Credibility Statement <span style="color:var(--muted);font-weight:400">(injected into every AI prompt)</span></label>
        <textarea name="credibility_statement" rows="4" placeholder="In one paragraph: why should a buyer trust this company? Use specifics — years, clients, industries. No superlatives."><?= htmlspecialchars($kbCompany['credibility_statement'] ?? '') ?></textarea>
      </div>
      <div class="form-group" style="margin:0"><label>Mission</label><textarea name="mission" rows="2"><?= htmlspecialchars($kbCompany['mission'] ?? '') ?></textarea></div>
      <div class="form-group" style="margin:0"><label>Vision</label><textarea name="vision" rows="2"><?= htmlspecialchars($kbCompany['vision'] ?? '') ?></textarea></div>
      <div class="form-group" style="margin:0"><label>Notable Clients <span style="color:var(--muted);font-weight:400">(comma-separated)</span></label><input type="text" name="notable_clients" value="<?= htmlspecialchars($kbCompany['notable_clients'] ?? '') ?>"></div>
      <div class="form-group" style="margin:0"><label>Awards &amp; Certifications</label><input type="text" name="awards" value="<?= htmlspecialchars($kbCompany['awards'] ?? '') ?>"></div>
    </div>
  </div>
  <button type="button" onclick="kbSave()" class="btn btn-primary">Save Company</button>
</form>

<?php elseif ($tab === 'verticals'): ?>
<!-- VERTICALS -->
<?php if ($verticals): ?>
<div class="card" style="margin-bottom:20px">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Existing Verticals (<?= count($verticals) ?>)</div>
  <?php foreach ($verticals as $v): ?>
  <div style="padding:14px 20px;border-bottom:1px solid rgba(42,45,58,0.4);display:flex;justify-content:space-between;align-items:center">
    <div>
      <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($v['name']) ?></div>
      <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars(substr($v['focus'] ?? '', 0, 100)) ?></div>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="editVertical(<?= htmlspecialchars(json_encode($v)) ?>)" class="btn btn-ghost btn-sm">Edit</button>
      <button onclick="deleteKB('delete_vertical',<?= $v['id'] ?>)" class="btn btn-ghost btn-sm" style="color:var(--danger)">Delete</button>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600" id="vertFormTitle">Add Vertical</div>
  <div style="padding:20px">
    <form id="kbForm" style="display:flex;flex-direction:column;gap:14px">
      <input type="hidden" name="action" value="save_vertical">
      <input type="hidden" name="id" id="vertId" value="0">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group" style="margin:0"><label>Vertical Name *</label><input type="text" name="name" id="vertName" required placeholder="e.g. ERP Practice"></div>
        <div class="form-group" style="margin:0"><label>Priority</label><select name="priority" id="vertPriority"><option value="core">Core</option><option value="growth">Growth</option><option value="emerging">Emerging</option></select></div>
      </div>
      <div class="form-group" style="margin:0"><label>Focus (2-3 sentences)</label><textarea name="focus" id="vertFocus" rows="2"></textarea></div>
      <div class="form-group" style="margin:0"><label>Industries Targeted <span style="color:var(--muted);font-weight:400">(comma-separated)</span></label><input type="text" name="industries" id="vertIndustries" placeholder="Manufacturing, Automotive, Aerospace"></div>
      <div class="form-group" style="margin:0"><label>Differentiators</label><textarea name="differentiators" id="vertDiff" rows="2"></textarea></div>
      <div class="form-group" style="margin:0"><label>Vertical Head</label><input type="text" name="head_name" id="vertHead" placeholder="Name, Title"></div>
      <div style="display:flex;gap:8px">
        <button type="button" onclick="kbSave()" class="btn btn-primary">Save Vertical</button>
        <button type="button" onclick="resetVertForm()" class="btn btn-ghost">Reset</button>
      </div>
    </form>
  </div>
</div>
<script>
function editVertical(v) {
  document.getElementById('vertFormTitle').textContent = 'Edit Vertical';
  document.getElementById('vertId').value = v.id;
  document.getElementById('vertName').value = v.name || '';
  document.getElementById('vertPriority').value = v.priority || 'core';
  document.getElementById('vertFocus').value = v.focus || '';
  document.getElementById('vertIndustries').value = v.industries || '';
  document.getElementById('vertDiff').value = v.differentiators || '';
  document.getElementById('vertHead').value = v.head_name || '';
  document.querySelector('.card:last-child').scrollIntoView({behavior:'smooth'});
}
function resetVertForm() {
  document.getElementById('vertFormTitle').textContent = 'Add Vertical';
  document.getElementById('kbForm').reset();
  document.getElementById('vertId').value = '0';
}
</script>

<?php elseif ($tab === 'services'): ?>
<!-- SERVICES -->
<?php if ($services): ?>
<div class="card" style="margin-bottom:20px">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Existing Services (<?= count($services) ?>)</div>
  <?php foreach ($services as $s): ?>
  <div style="padding:14px 20px;border-bottom:1px solid rgba(42,45,58,0.4);display:flex;justify-content:space-between;align-items:center">
    <div>
      <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($s['name']) ?> <?php if ($s['vertical_name']): ?><span style="color:var(--muted);font-weight:400;font-size:12px">/ <?= htmlspecialchars($s['vertical_name']) ?></span><?php endif; ?></div>
      <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars(substr($s['one_liner'] ?? '', 0, 100)) ?></div>
      <?php if ($s['signal_types']): ?><div style="font-size:11px;color:var(--accent);margin-top:2px">Signals: <?= htmlspecialchars($s['signal_types']) ?></div><?php endif; ?>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="editService(<?= htmlspecialchars(json_encode($s)) ?>)" class="btn btn-ghost btn-sm">Edit</button>
      <button onclick="deleteKB('delete_service',<?= $s['id'] ?>)" class="btn btn-ghost btn-sm" style="color:var(--danger)">Delete</button>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600" id="svcFormTitle">Add Service</div>
  <div style="padding:20px">
    <form id="kbForm" style="display:flex;flex-direction:column;gap:14px">
      <input type="hidden" name="action" value="save_service">
      <input type="hidden" name="id" id="svcId" value="0">
      <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px">
        <div class="form-group" style="margin:0"><label>Service Name *</label><input type="text" name="name" id="svcName" required></div>
        <div class="form-group" style="margin:0"><label>Parent Vertical</label>
          <select name="vertical_id" id="svcVertical">
            <option value="0">-- None --</option>
            <?php foreach ($verticals as $v): ?>
            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group" style="margin:0"><label>One-liner</label><input type="text" name="one_liner" id="svcOneliner" placeholder="Single sentence elevator pitch"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group" style="margin:0"><label>Industries <span style="color:var(--muted);font-weight:400">(comma-separated)</span></label><input type="text" name="industries" id="svcIndustries" placeholder="Manufacturing, Automotive"></div>
        <div class="form-group" style="margin:0"><label>Engagement Model</label><input type="text" name="engagement_model" id="svcEngagement" placeholder="Retainer / Project / SaaS"></div>
      </div>
      <div style="background:rgba(99,102,241,0.07);border:1px solid rgba(99,102,241,0.2);border-radius:8px;padding:14px;display:flex;flex-direction:column;gap:14px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--accent)">Signal Matching (used for ISE matching)</div>
        <div class="form-group" style="margin:0"><label>Signal Types <span style="color:var(--muted);font-weight:400">(comma-sep: M&amp;A, ERP, Expansion, Hiring)</span></label><input type="text" name="signal_types" id="svcSignalTypes" placeholder="M&A, ERP, Expansion"></div>
        <div class="form-group" style="margin:0"><label>Signal Keywords <span style="color:var(--muted);font-weight:400">(comma-sep words to watch in news/jobs)</span></label><input type="text" name="signal_keywords" id="svcSignalKeywords" placeholder="merger, acquisition, erp implementation"></div>
        <div class="form-group" style="margin:0"><label>Tech Triggers <span style="color:var(--muted);font-weight:400">(comma-sep tools that indicate a fit)</span></label><input type="text" name="tech_triggers" id="svcTechTriggers" placeholder="SAP ECC, Oracle EBS, Dynamics AX"></div>
      </div>
      <div style="background:rgba(16,185,129,0.07);border:1px solid rgba(16,185,129,0.2);border-radius:8px;padding:14px;display:flex;flex-direction:column;gap:14px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--success)">AI Email Content (injected into prompt)</div>
        <div class="form-group" style="margin:0"><label>Problem It Solves <span style="color:var(--muted);font-weight:400">(in the buyer's own language)</span></label><textarea name="problem_statement" id="svcProblem" rows="3" placeholder="The pain a buyer feels before engaging you"></textarea></div>
        <div class="form-group" style="margin:0"><label>Outcomes / Results</label><textarea name="outcomes" id="svcOutcomes" rows="3" placeholder="Tangible results clients achieve"></textarea></div>
        <div class="form-group" style="margin:0"><label>Differentiators</label><textarea name="differentiators" id="svcDiff" rows="2" placeholder="What makes this different from competitors"></textarea></div>
        <div class="form-group" style="margin:0"><label>Proof Points</label><textarea name="proof_points" id="svcProof" rows="2" placeholder="Stats, numbers, benchmarks"></textarea></div>
      </div>
      <div style="display:flex;gap:8px">
        <button type="button" onclick="kbSave()" class="btn btn-primary">Save Service</button>
        <button type="button" onclick="resetSvcForm()" class="btn btn-ghost">Reset</button>
      </div>
    </form>
  </div>
</div>
<script>
function editService(s) {
  document.getElementById('svcFormTitle').textContent = 'Edit Service';
  document.getElementById('svcId').value = s.id;
  document.getElementById('svcName').value = s.name || '';
  document.getElementById('svcVertical').value = s.vertical_id || 0;
  document.getElementById('svcOneliner').value = s.one_liner || '';
  document.getElementById('svcIndustries').value = s.industries || '';
  document.getElementById('svcEngagement').value = s.engagement_model || '';
  document.getElementById('svcSignalTypes').value = s.signal_types || '';
  document.getElementById('svcSignalKeywords').value = s.signal_keywords || '';
  document.getElementById('svcTechTriggers').value = s.tech_triggers || '';
  document.getElementById('svcProblem').value = s.problem_statement || '';
  document.getElementById('svcOutcomes').value = s.outcomes || '';
  document.getElementById('svcDiff').value = s.differentiators || '';
  document.getElementById('svcProof').value = s.proof_points || '';
  document.querySelector('.card:last-child').scrollIntoView({behavior:'smooth'});
}
function resetSvcForm() {
  document.getElementById('svcFormTitle').textContent = 'Add Service';
  document.getElementById('kbForm').reset();
  document.getElementById('svcId').value = '0';
}
</script>

<?php elseif ($tab === 'icps'): ?>
<!-- ICPs -->
<?php if ($icps): ?>
<div class="card" style="margin-bottom:20px">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Existing ICPs (<?= count($icps) ?>)</div>
  <?php foreach ($icps as $ic): ?>
  <div style="padding:14px 20px;border-bottom:1px solid rgba(42,45,58,0.4);display:flex;justify-content:space-between;align-items:center">
    <div>
      <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($ic['name']) ?></div>
      <div style="font-size:11px;color:var(--muted)"><?php if ($ic['service_name']) echo htmlspecialchars($ic['service_name']); if ($ic['vertical_name']) echo ' / ' . htmlspecialchars($ic['vertical_name']); ?></div>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="editICP(<?= htmlspecialchars(json_encode($ic)) ?>)" class="btn btn-ghost btn-sm">Edit</button>
      <button onclick="deleteKB('delete_icp',<?= $ic['id'] ?>)" class="btn btn-ghost btn-sm" style="color:var(--danger)">Delete</button>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600" id="icpFormTitle">Add ICP</div>
  <div style="padding:20px">
    <form id="kbForm" style="display:flex;flex-direction:column;gap:14px">
      <input type="hidden" name="action" value="save_icp">
      <input type="hidden" name="id" id="icpId" value="0">
      <div class="form-group" style="margin:0"><label>ICP Name *</label><input type="text" name="name" id="icpName" required placeholder="e.g. Mid-market manufacturer — SAP migration"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group" style="margin:0"><label>Linked Service</label>
          <select name="service_id" id="icpService">
            <option value="0">-- None --</option>
            <?php foreach ($services as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin:0"><label>Linked Vertical</label>
          <select name="vertical_id" id="icpVertical">
            <option value="0">-- None --</option>
            <?php foreach ($verticals as $v): ?><option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px">
        <div class="form-group" style="margin:0"><label>Company Size</label><input type="text" name="size_range" id="icpSize" placeholder="500-5000 employees"></div>
        <div class="form-group" style="margin:0"><label>Industries</label><input type="text" name="industries" id="icpIndustries" placeholder="Manufacturing, FMCG"></div>
        <div class="form-group" style="margin:0"><label>Geographies</label><input type="text" name="geographies" id="icpGeo" placeholder="UK, Europe, US"></div>
      </div>
      <div class="form-group" style="margin:0"><label>Tech Stack Signals <span style="color:var(--muted);font-weight:400">(legacy systems they likely run)</span></label><input type="text" name="tech_stack_signals" id="icpTech" placeholder="SAP ECC 6.0, Oracle EBS R12"></div>
      <div class="form-group" style="margin:0"><label>Trigger Events</label><input type="text" name="trigger_events" id="icpTriggers" placeholder="M&A, ERP go-live, expansion, hiring surge"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group" style="margin:0"><label>Perfect Fit</label><textarea name="perfect_fit" id="icpPerfect" rows="3" placeholder="What the ideal account looks like"></textarea></div>
        <div class="form-group" style="margin:0"><label>Poor Fit / Disqualifiers</label><textarea name="poor_fit" id="icpPoor" rows="3" placeholder="What disqualifies a company"></textarea></div>
      </div>
      <div style="display:flex;gap:8px">
        <button type="button" onclick="kbSave()" class="btn btn-primary">Save ICP</button>
        <button type="button" onclick="resetICPForm()" class="btn btn-ghost">Reset</button>
      </div>
    </form>
  </div>
</div>
<script>
function editICP(ic) {
  document.getElementById('icpFormTitle').textContent = 'Edit ICP';
  document.getElementById('icpId').value = ic.id;
  document.getElementById('icpName').value = ic.name || '';
  document.getElementById('icpService').value = ic.service_id || 0;
  document.getElementById('icpVertical').value = ic.vertical_id || 0;
  document.getElementById('icpSize').value = ic.size_range || '';
  document.getElementById('icpIndustries').value = ic.industries || '';
  document.getElementById('icpGeo').value = ic.geographies || '';
  document.getElementById('icpTech').value = ic.tech_stack_signals || '';
  document.getElementById('icpTriggers').value = ic.trigger_events || '';
  document.getElementById('icpPerfect').value = ic.perfect_fit || '';
  document.getElementById('icpPoor').value = ic.poor_fit || '';
  document.querySelector('.card:last-child').scrollIntoView({behavior:'smooth'});
}
function resetICPForm() {
  document.getElementById('icpFormTitle').textContent = 'Add ICP';
  document.getElementById('kbForm').reset();
  document.getElementById('icpId').value = '0';
}
</script>

<?php elseif ($tab === 'tone'): ?>
<!-- TONE -->
<form id="kbForm" style="max-width:720px">
  <input type="hidden" name="action" value="save_tone">
  <div class="card" style="margin-bottom:20px">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Brand Tone &amp; Voice</div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:14px">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group" style="margin:0"><label>Tone Descriptors <span style="color:var(--muted);font-weight:400">(4-6 adjectives)</span></label><input type="text" name="tone_descriptors" value="<?= htmlspecialchars($kbTone['tone_descriptors'] ?? '') ?>" placeholder="confident, direct, human, no-nonsense"></div>
        <div class="form-group" style="margin:0"><label>Anti-tone <span style="color:var(--muted);font-weight:400">(never sounds like)</span></label><input type="text" name="anti_tone" value="<?= htmlspecialchars($kbTone['anti_tone'] ?? '') ?>" placeholder="salesy, corporate, vague, arrogant"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group" style="margin:0"><label>Words We Always Use</label><input type="text" name="words_always" value="<?= htmlspecialchars($kbTone['words_always'] ?? '') ?>"></div>
        <div class="form-group" style="margin:0"><label>Words We Never Use</label><input type="text" name="words_never" value="<?= htmlspecialchars($kbTone['words_never'] ?? '') ?>"></div>
      </div>
      <div class="form-group" style="margin:0"><label>Email Opening Style</label><textarea name="email_opening_style" rows="2"><?= htmlspecialchars($kbTone['email_opening_style'] ?? '') ?></textarea></div>
      <div class="form-group" style="margin:0"><label>CTA Style</label><textarea name="cta_style" rows="2"><?= htmlspecialchars($kbTone['cta_style'] ?? '') ?></textarea></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group" style="margin:0"><label>Email Length Preference</label>
          <select name="email_length">
            <option value="short"  <?= ($kbTone['email_length'] ?? 'medium') === 'short'  ? 'selected' : '' ?>>Short</option>
            <option value="medium" <?= ($kbTone['email_length'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Medium</option>
            <option value="long"   <?= ($kbTone['email_length'] ?? 'medium') === 'long'   ? 'selected' : '' ?>>Long</option>
          </select>
        </div>
        <div class="form-group" style="margin:0"><label>Paragraph Style</label>
          <select name="paragraph_style">
            <option value="one-liners"      <?= ($kbTone['paragraph_style'] ?? '') === 'one-liners'      ? 'selected' : '' ?>>One-liners</option>
            <option value="full-paragraphs" <?= ($kbTone['paragraph_style'] ?? 'full-paragraphs') === 'full-paragraphs' ? 'selected' : '' ?>>Full Paragraphs</option>
            <option value="bullet-heavy"    <?= ($kbTone['paragraph_style'] ?? '') === 'bullet-heavy'    ? 'selected' : '' ?>>Bullet-heavy</option>
          </select>
        </div>
      </div>
      <div class="form-group" style="margin:0"><label>Good Example <span style="color:var(--muted);font-weight:400">(write like this)</span></label><textarea name="good_example" rows="4"><?= htmlspecialchars($kbTone['good_example'] ?? '') ?></textarea></div>
      <div class="form-group" style="margin:0"><label>Bad Example <span style="color:var(--muted);font-weight:400">(never write like this)</span></label><textarea name="bad_example" rows="4"><?= htmlspecialchars($kbTone['bad_example'] ?? '') ?></textarea></div>
    </div>
  </div>
  <button type="button" onclick="kbSave()" class="btn btn-primary">Save Tone &amp; Voice</button>
</form>

<?php elseif ($tab === 'senders'): ?>
<!-- SENDERS -->
<?php if ($senders): ?>
<div class="card" style="margin-bottom:20px">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Existing Senders (<?= count($senders) ?>)</div>
  <?php foreach ($senders as $s): ?>
  <div style="padding:14px 20px;border-bottom:1px solid rgba(42,45,58,0.4);display:flex;justify-content:space-between;align-items:center">
    <div>
      <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($s['full_name']) ?> <?php if ($s['is_default']): ?><span style="font-size:10px;background:rgba(99,102,241,0.15);color:var(--accent);padding:1px 6px;border-radius:4px;margin-left:6px">DEFAULT</span><?php endif; ?></div>
      <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($s['title']) ?> <?= $s['email'] ? '&middot; ' . htmlspecialchars($s['email']) : '' ?></div>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="editSender(<?= htmlspecialchars(json_encode($s)) ?>)" class="btn btn-ghost btn-sm">Edit</button>
      <button onclick="deleteKB('delete_sender',<?= $s['id'] ?>)" class="btn btn-ghost btn-sm" style="color:var(--danger)">Delete</button>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600" id="sndFormTitle">Add Sender</div>
  <div style="padding:20px">
    <form id="kbForm" style="display:flex;flex-direction:column;gap:14px">
      <input type="hidden" name="action" value="save_sender">
      <input type="hidden" name="id" id="sndId" value="0">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group" style="margin:0"><label>Full Name *</label><input type="text" name="full_name" id="sndName" required></div>
        <div class="form-group" style="margin:0"><label>Job Title</label><input type="text" name="title" id="sndTitle"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group" style="margin:0"><label>Email</label><input type="email" name="email" id="sndEmail"></div>
        <div class="form-group" style="margin:0"><label>LinkedIn URL</label><input type="url" name="linkedin_url" id="sndLinkedin"></div>
      </div>
      <div class="form-group" style="margin:0"><label>Calendar / Meeting Link <span style="color:var(--muted);font-weight:400">(used in CTA)</span></label><input type="url" name="calendar_link" id="sndCalendar" placeholder="https://calendly.com/yourlink"></div>
      <div class="form-group" style="margin:0"><label>Background <span style="color:var(--muted);font-weight:400">(2-3 sentences about experience)</span></label><textarea name="background" id="sndBackground" rows="2"></textarea></div>
      <div class="form-group" style="margin:0"><label>Individual Tone <span style="color:var(--muted);font-weight:400">(overrides brand tone; describe how this person writes)</span></label><textarea name="individual_tone" id="sndTone" rows="2" placeholder="More casual than the brand. Uses short punchy sentences. Never uses jargon."></textarea></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group" style="margin:0"><label>Email Opening Style</label><textarea name="email_opening_style" id="sndOpening" rows="2"></textarea></div>
        <div class="form-group" style="margin:0"><label>Email Closing Style</label><textarea name="email_closing_style" id="sndClosing" rows="2"></textarea></div>
      </div>
      <div class="form-group" style="margin:0"><label>Email Signature</label><textarea name="signature" id="sndSig" rows="3" placeholder="Name | Title | Company | Phone"></textarea></div>
      <div class="form-group" style="margin:0">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="checkbox" name="is_default" id="sndDefault" value="1"> Set as default sender
        </label>
      </div>
      <div style="display:flex;gap:8px">
        <button type="button" onclick="kbSave()" class="btn btn-primary">Save Sender</button>
        <button type="button" onclick="resetSndForm()" class="btn btn-ghost">Reset</button>
      </div>
    </form>
  </div>
</div>
<script>
function editSender(s) {
  document.getElementById('sndFormTitle').textContent = 'Edit Sender';
  document.getElementById('sndId').value = s.id;
  document.getElementById('sndName').value = s.full_name || '';
  document.getElementById('sndTitle').value = s.title || '';
  document.getElementById('sndEmail').value = s.email || '';
  document.getElementById('sndLinkedin').value = s.linkedin_url || '';
  document.getElementById('sndCalendar').value = s.calendar_link || '';
  document.getElementById('sndBackground').value = s.background || '';
  document.getElementById('sndTone').value = s.individual_tone || '';
  document.getElementById('sndOpening').value = s.email_opening_style || '';
  document.getElementById('sndClosing').value = s.email_closing_style || '';
  document.getElementById('sndSig').value = s.signature || '';
  document.getElementById('sndDefault').checked = s.is_default == 1;
  document.querySelector('.card:last-child').scrollIntoView({behavior:'smooth'});
}
function resetSndForm() {
  document.getElementById('sndFormTitle').textContent = 'Add Sender';
  document.getElementById('kbForm').reset();
  document.getElementById('sndId').value = '0';
}
</script>
<?php endif; ?>

<script>
async function kbSave() {
  var form = document.getElementById('kbForm');
  if (form.reportValidity && !form.reportValidity()) return;
  var fd = new FormData(form);
  var msg = document.getElementById('kbMsg');
  var r = await fetch('api/kb.php', {method: 'POST', body: fd});
  var d = await r.json();
  if (d.ok) {
    msg.style.display = 'block';
    msg.style.background = 'rgba(0,200,100,0.1)';
    msg.style.border = '1px solid var(--success)';
    msg.style.color = 'var(--success)';
    msg.textContent = 'Saved successfully. Reloading...';
    setTimeout(function(){ location.reload(); }, 1000);
  } else {
    msg.style.display = 'block';
    msg.style.background = 'rgba(239,68,68,0.1)';
    msg.style.border = '1px solid var(--danger)';
    msg.style.color = 'var(--danger)';
    msg.textContent = 'Error: ' + (d.error || 'unknown');
  }
}
async function deleteKB(action, id) {
  if (!confirm('Delete this entry?')) return;
  var fd = new FormData();
  fd.append('action', action);
  fd.append('id', id);
  var r = await fetch('api/kb.php', {method: 'POST', body: fd});
  var d = await r.json();
  if (d.ok) location.reload();
  else alert('Error: ' + (d.error || 'unknown'));
}
</script>

<?php include 'layout_end.php'; ?>
