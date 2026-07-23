<?php
require_once 'config.php';
require_once 'lib/DB.php';

$tab = $_GET['tab'] ?? 'company';
$tabs = array('company','verticals','services','icps','tone','senders');
if (!in_array($tab, $tabs)) $tab = 'company';

$currentPage = 'knowledge';
include 'layout.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">&#128218; Knowledge Hub</div>
    <div class="page-sub">SolidPro GTM context used to generate AI emails</div>
  </div>
</div>

<div style="display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:24px">
  <?php foreach ($tabs as $t): ?>
  <a href="?tab=<?= $t ?>" style="padding:10px 18px;font-size:13px;font-weight:500;text-decoration:none;border-bottom:2px solid <?= $tab===$t?'var(--accent)':'transparent' ?>;color:<?= $tab===$t?'var(--accent)':'var(--muted)' ?>;">
    <?= ucfirst($t === 'icps' ? 'ICPs' : $t) ?>
  </a>
  <?php endforeach; ?>
</div>

<div id="saveMsg" style="display:none;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:13px"></div>

<?php if ($tab === 'company'): ?>
<?php $co = DB::fetchOne('SELECT * FROM kb_company LIMIT 1'); ?>
<div class="card" style="max-width:760px">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Our Company (Block 1)</div>
  <div style="padding:20px">
    <form id="coForm">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
      <div class="form-group"><label>Company Name</label><input type="text" name="name" value="<?= htmlspecialchars($co['name']??'') ?>"></div>
      <div class="form-group"><label>Tagline</label><input type="text" name="tagline" value="<?= htmlspecialchars($co['tagline']??'') ?>"></div>
      <div class="form-group"><label>Website</label><input type="url" name="website" value="<?= htmlspecialchars($co['website']??'') ?>"></div>
      <div class="form-group"><label>Founded Year</label><input type="text" name="founded_year" value="<?= htmlspecialchars($co['founded_year']??'') ?>"></div>
      <div class="form-group"><label>Company Size</label><input type="text" name="size" placeholder="e.g. 500-1000" value="<?= htmlspecialchars($co['size']??'') ?>"></div>
      <div class="form-group"><label>HQ Location</label><input type="text" name="hq" value="<?= htmlspecialchars($co['hq']??'') ?>"></div>
    </div>
    <div class="form-group"><label>Mission</label><textarea name="mission" rows="2"><?= htmlspecialchars($co['mission']??'') ?></textarea></div>
    <div class="form-group"><label>Vision</label><textarea name="vision" rows="2"><?= htmlspecialchars($co['vision']??'') ?></textarea></div>
    <div class="form-group"><label>Company Story</label><textarea name="story" rows="3"><?= htmlspecialchars($co['story']??'') ?></textarea></div>
    <div class="form-group"><label>Credibility Statement <span style="color:var(--muted);font-size:11px">(used in AI prompts)</span></label><textarea name="credibility_statement" rows="3"><?= htmlspecialchars($co['credibility_statement']??'') ?></textarea></div>
    <div class="form-group"><label>Notable Clients</label><textarea name="notable_clients" rows="2" placeholder="Comma-separated or prose"><?= htmlspecialchars($co['notable_clients']??'') ?></textarea></div>
    <div class="form-group"><label>Awards / Recognitions</label><textarea name="awards" rows="2"><?= htmlspecialchars($co['awards']??'') ?></textarea></div>
    <button type="button" onclick="kbSave('save_company', 'coForm')" class="btn btn-primary">Save Company Info</button>
    </form>
  </div>
</div>

<?php elseif ($tab === 'verticals'): ?>
<?php $verticals = DB::fetchAll('SELECT * FROM kb_verticals ORDER BY priority, name'); ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Add / Edit Vertical</div>
  <div style="padding:20px">
    <form id="vertForm">
    <input type="hidden" name="id" id="vert_id" value="">
    <div class="form-group"><label>Name *</label><input type="text" name="name" id="vert_name" required></div>
    <div class="form-group"><label>Focus</label><textarea name="focus" id="vert_focus" rows="2"></textarea></div>
    <div class="form-group"><label>Industries Served</label><input type="text" name="industries" id="vert_industries" placeholder="Manufacturing, Retail, ..."></div>
    <div class="form-group"><label>Priority</label><select name="priority" id="vert_priority"><option value="core">Core</option><option value="growth">Growth</option><option value="emerging">Emerging</option></select></div>
    <div class="form-group"><label>Differentiators</label><textarea name="differentiators" id="vert_differentiators" rows="2"></textarea></div>
    <div class="form-group"><label>Head / Lead</label><input type="text" name="head_name" id="vert_head_name"></div>
    <div class="form-group"><label>Positioning</label><textarea name="positioning" id="vert_positioning" rows="2"></textarea></div>
    <div style="display:flex;gap:8px">
      <button type="button" onclick="kbSave('save_vertical','vertForm')" class="btn btn-primary">Save Vertical</button>
      <button type="button" onclick="clearVertForm()" class="btn btn-secondary">Clear</button>
    </div>
    </form>
  </div>
</div>
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Verticals (<?= count($verticals) ?>)</div>
  <?php if ($verticals): ?>
  <?php foreach ($verticals as $v): ?>
  <div style="padding:12px 20px;border-bottom:1px solid rgba(42,45,58,0.4);display:flex;justify-content:space-between;align-items:flex-start">
    <div>
      <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($v['name']) ?> <span class="badge badge-tech" style="font-size:10px"><?= $v['priority'] ?></span></div>
      <?php if ($v['industries']): ?><div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($v['industries']) ?></div><?php endif; ?>
    </div>
    <div style="display:flex;gap:6px;white-space:nowrap">
      <button onclick="editVertical(<?= htmlspecialchars(json_encode($v)) ?>)" class="btn btn-secondary btn-sm">Edit</button>
      <button onclick="kbDelete('delete_vertical',<?= $v['id'] ?>)" class="btn btn-ghost btn-sm" style="color:var(--danger)">Del</button>
    </div>
  </div>
  <?php endforeach; ?>
  <?php else: ?>
  <div style="padding:20px;color:var(--muted);font-size:13px">No verticals yet.</div>
  <?php endif; ?>
</div>
</div>

<?php elseif ($tab === 'services'): ?>
<?php
$services  = DB::fetchAll('SELECT s.*, v.name as vertical_name FROM kb_services s LEFT JOIN kb_verticals v ON s.vertical_id = v.id ORDER BY v.name, s.name');
$verticals = DB::fetchAll('SELECT id, name FROM kb_verticals ORDER BY name');
?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Add / Edit Service</div>
  <div style="padding:20px">
    <form id="svcForm">
    <input type="hidden" name="id" id="svc_id" value="">
    <div class="form-group"><label>Service Name *</label><input type="text" name="name" id="svc_name" required></div>
    <div class="form-group"><label>Vertical</label>
      <select name="vertical_id" id="svc_vertical_id">
        <option value="">-- none --</option>
        <?php foreach ($verticals as $v): ?>
        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>One-liner</label><input type="text" name="one_liner" id="svc_one_liner"></div>
    <div class="form-group"><label>Signal Types <span style="color:var(--muted);font-size:11px">(comma-sep, e.g. M&A,ERP,Expansion)</span></label><input type="text" name="signal_types" id="svc_signal_types"></div>
    <div class="form-group"><label>Tech Triggers <span style="color:var(--muted);font-size:11px">(comma-sep, e.g. SAP ECC,Oracle EBS)</span></label><input type="text" name="tech_triggers" id="svc_tech_triggers"></div>
    <div class="form-group"><label>Signal Keywords</label><input type="text" name="signal_keywords" id="svc_signal_keywords"></div>
    <div class="form-group"><label>Industries</label><input type="text" name="industries" id="svc_industries"></div>
    <div class="form-group"><label>ICP Size</label><input type="text" name="icp_size" id="svc_icp_size" placeholder="500-5000 employees"></div>
    <div class="form-group"><label>Buyer Titles</label><input type="text" name="buyer_titles" id="svc_buyer_titles" placeholder="CIO, ERP Director, ..."></div>
    <div class="form-group"><label>Engagement Model</label><input type="text" name="engagement_model" id="svc_engagement_model" placeholder="Project, Retainer, ..."></div>
    <div class="form-group"><label>Problem Statement</label><textarea name="problem_statement" id="svc_problem_statement" rows="2"></textarea></div>
    <div class="form-group"><label>Outcomes</label><textarea name="outcomes" id="svc_outcomes" rows="2"></textarea></div>
    <div class="form-group"><label>Differentiators</label><textarea name="differentiators" id="svc_differentiators" rows="2"></textarea></div>
    <div class="form-group"><label>Description</label><textarea name="description" id="svc_description" rows="3"></textarea></div>
    <div style="display:flex;gap:8px">
      <button type="button" onclick="kbSave('save_service','svcForm')" class="btn btn-primary">Save Service</button>
      <button type="button" onclick="clearSvcForm()" class="btn btn-secondary">Clear</button>
    </div>
    </form>
  </div>
</div>
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Services (<?= count($services) ?>)</div>
  <?php if ($services): ?>
  <?php foreach ($services as $s): ?>
  <div style="padding:12px 20px;border-bottom:1px solid rgba(42,45,58,0.4)">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($s['name']) ?></div>
        <?php if ($s['vertical_name']): ?><div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($s['vertical_name']) ?></div><?php endif; ?>
        <?php if ($s['signal_types']): ?><div style="font-size:11px;color:var(--accent)">Signals: <?= htmlspecialchars($s['signal_types']) ?></div><?php endif; ?>
      </div>
      <div style="display:flex;gap:6px;white-space:nowrap">
        <button onclick="editService(<?= htmlspecialchars(json_encode($s)) ?>)" class="btn btn-secondary btn-sm">Edit</button>
        <button onclick="kbDelete('delete_service',<?= $s['id'] ?>)" class="btn btn-ghost btn-sm" style="color:var(--danger)">Del</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php else: ?>
  <div style="padding:20px;color:var(--muted);font-size:13px">No services yet.</div>
  <?php endif; ?>
</div>
</div>

<?php elseif ($tab === 'icps'): ?>
<?php
$icps      = DB::fetchAll('SELECT i.*, v.name as v_name, s.name as s_name FROM kb_icps i LEFT JOIN kb_verticals v ON i.vertical_id=v.id LEFT JOIN kb_services s ON i.service_id=s.id ORDER BY i.name');
$verticals = DB::fetchAll('SELECT id, name FROM kb_verticals ORDER BY name');
$services  = DB::fetchAll('SELECT id, name FROM kb_services ORDER BY name');
?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Add / Edit ICP</div>
  <div style="padding:20px">
    <form id="icpForm">
    <input type="hidden" name="id" id="icp_id" value="">
    <div class="form-group"><label>ICP Name *</label><input type="text" name="name" id="icp_name" required></div>
    <div class="form-group"><label>Vertical</label>
      <select name="vertical_id" id="icp_vertical_id">
        <option value="">-- none --</option>
        <?php foreach ($verticals as $v): ?><option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Service</label>
      <select name="service_id" id="icp_service_id">
        <option value="">-- none --</option>
        <?php foreach ($services as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Size Range</label><input type="text" name="size_range" id="icp_size_range" placeholder="500-5000 employees"></div>
    <div class="form-group"><label>Industries</label><input type="text" name="industries" id="icp_industries"></div>
    <div class="form-group"><label>Geographies</label><input type="text" name="geographies" id="icp_geographies" placeholder="North America, EMEA"></div>
    <div class="form-group"><label>Tech Stack Signals</label><input type="text" name="tech_stack_signals" id="icp_tech_stack_signals" placeholder="SAP ECC, Oracle EBS"></div>
    <div class="form-group"><label>Trigger Events</label><textarea name="trigger_events" id="icp_trigger_events" rows="2" placeholder="M&A activity, ERP go-live, ..."></textarea></div>
    <div class="form-group"><label>Perfect Fit</label><textarea name="perfect_fit" id="icp_perfect_fit" rows="2"></textarea></div>
    <div class="form-group"><label>Poor Fit / Disqualifiers</label><textarea name="disqualifiers" id="icp_disqualifiers" rows="2"></textarea></div>
    <div style="display:flex;gap:8px">
      <button type="button" onclick="kbSave('save_icp','icpForm')" class="btn btn-primary">Save ICP</button>
      <button type="button" onclick="clearIcpForm()" class="btn btn-secondary">Clear</button>
    </div>
    </form>
  </div>
</div>
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">ICPs (<?= count($icps) ?>)</div>
  <?php if ($icps): ?>
  <?php foreach ($icps as $icp): ?>
  <div style="padding:12px 20px;border-bottom:1px solid rgba(42,45,58,0.4)">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($icp['name']) ?></div>
        <div style="font-size:11px;color:var(--muted)">
          <?= htmlspecialchars($icp['v_name'] ?? '') ?>
          <?php if ($icp['s_name']): ?> &middot; <?= htmlspecialchars($icp['s_name']) ?><?php endif; ?>
        </div>
      </div>
      <div style="display:flex;gap:6px">
        <button onclick="editICP(<?= htmlspecialchars(json_encode($icp)) ?>)" class="btn btn-secondary btn-sm">Edit</button>
        <button onclick="kbDelete('delete_icp',<?= $icp['id'] ?>)" class="btn btn-ghost btn-sm" style="color:var(--danger)">Del</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php else: ?>
  <div style="padding:20px;color:var(--muted);font-size:13px">No ICPs yet.</div>
  <?php endif; ?>
</div>
</div>

<?php elseif ($tab === 'tone'): ?>
<?php $tone = DB::fetchOne('SELECT * FROM kb_tone LIMIT 1'); ?>
<div class="card" style="max-width:760px">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Brand Tone &amp; Voice (Block 6)</div>
  <div style="padding:20px">
    <form id="toneForm">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
      <div class="form-group"><label>Tone Descriptors <span style="color:var(--muted);font-size:11px">(comma-sep)</span></label><input type="text" name="tone_descriptors" value="<?= htmlspecialchars($tone['tone_descriptors']??'') ?>"></div>
      <div class="form-group"><label>Anti-Tone (never sound like)</label><input type="text" name="anti_tone" value="<?= htmlspecialchars($tone['anti_tone']??'') ?>"></div>
      <div class="form-group"><label>Words to Always Use</label><input type="text" name="words_always" value="<?= htmlspecialchars($tone['words_always']??'') ?>"></div>
      <div class="form-group"><label>Words to Never Use</label><input type="text" name="words_never" value="<?= htmlspecialchars($tone['words_never']??'') ?>"></div>
      <div class="form-group"><label>Email Length</label>
        <select name="email_length">
          <?php foreach (array('short','medium','long') as $opt): ?>
          <option value="<?= $opt ?>" <?= ($tone['email_length']??'medium')===$opt?'selected':'' ?>><?= ucfirst($opt) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Paragraph Style</label>
        <select name="paragraph_style">
          <?php foreach (array('one-liners','full-paragraphs','bullet-heavy') as $opt): ?>
          <option value="<?= $opt ?>" <?= ($tone['paragraph_style']??'full-paragraphs')===$opt?'selected':'' ?>><?= $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-group"><label>Email Opening Style</label><textarea name="email_opening_style" rows="2"><?= htmlspecialchars($tone['email_opening_style']??'') ?></textarea></div>
    <div class="form-group"><label>CTA Style</label><textarea name="cta_style" rows="2"><?= htmlspecialchars($tone['cta_style']??'') ?></textarea></div>
    <div class="form-group"><label>Good Example Email</label><textarea name="good_example" rows="4"><?= htmlspecialchars($tone['good_example']??'') ?></textarea></div>
    <div class="form-group"><label>Bad Example Email</label><textarea name="bad_example" rows="3"><?= htmlspecialchars($tone['bad_example']??'') ?></textarea></div>
    <button type="button" onclick="kbSave('save_tone','toneForm')" class="btn btn-primary">Save Tone Settings</button>
    </form>
  </div>
</div>

<?php elseif ($tab === 'senders'): ?>
<?php $senders = DB::fetchAll('SELECT * FROM kb_senders ORDER BY is_default DESC, full_name'); ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Add / Edit Sender</div>
  <div style="padding:20px">
    <form id="senderForm">
    <input type="hidden" name="id" id="sender_id" value="">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="form-group"><label>Full Name</label><input type="text" name="full_name" id="sender_full_name"></div>
      <div class="form-group"><label>Title</label><input type="text" name="title" id="sender_title"></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" id="sender_email"></div>
      <div class="form-group"><label>LinkedIn URL</label><input type="url" name="linkedin_url" id="sender_linkedin_url"></div>
      <div class="form-group"><label>Years Experience</label><input type="number" name="years_experience" id="sender_years_experience"></div>
      <div class="form-group"><label>Calendar Link</label><input type="url" name="calendar_link" id="sender_calendar_link"></div>
    </div>
    <div class="form-group"><label>Background</label><textarea name="background" id="sender_background" rows="2"></textarea></div>
    <div class="form-group"><label>Credibility Statement</label><textarea name="credibility" id="sender_credibility" rows="2"></textarea></div>
    <div class="form-group"><label>Individual Tone</label><textarea name="individual_tone" id="sender_individual_tone" rows="2"></textarea></div>
    <div class="form-group"><label>Email Opening Style</label><textarea name="email_opening_style" id="sender_email_opening_style" rows="2"></textarea></div>
    <div class="form-group"><label>Email Closing Style</label><textarea name="email_closing_style" id="sender_email_closing_style" rows="2"></textarea></div>
    <div class="form-group"><label>Verticals</label><input type="text" name="verticals" id="sender_verticals" placeholder="ERP, SCM, ..."></div>
    <div class="form-group"><label>Signature (HTML/text)</label><textarea name="signature" id="sender_signature" rows="3"></textarea></div>
    <div class="form-group" style="display:flex;align-items:center;gap:10px">
      <input type="checkbox" name="is_default" id="sender_is_default" value="1">
      <label for="sender_is_default" style="margin:0;cursor:pointer">Set as default sender</label>
    </div>
    <div style="display:flex;gap:8px">
      <button type="button" onclick="kbSave('save_sender','senderForm')" class="btn btn-primary">Save Sender</button>
      <button type="button" onclick="clearSenderForm()" class="btn btn-secondary">Clear</button>
    </div>
    </form>
  </div>
</div>
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Senders (<?= count($senders) ?>)</div>
  <?php if ($senders): ?>
  <?php foreach ($senders as $s): ?>
  <div style="padding:12px 20px;border-bottom:1px solid rgba(42,45,58,0.4)">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($s['full_name']) ?>
          <?php if ($s['is_default']): ?> <span class="badge badge-tech" style="font-size:10px">Default</span><?php endif; ?>
        </div>
        <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($s['title']??'') ?> &middot; <?= htmlspecialchars($s['email']??'') ?></div>
      </div>
      <div style="display:flex;gap:6px">
        <button onclick="editSender(<?= htmlspecialchars(json_encode($s)) ?>)" class="btn btn-secondary btn-sm">Edit</button>
        <button onclick="kbDelete('delete_sender',<?= $s['id'] ?>)" class="btn btn-ghost btn-sm" style="color:var(--danger)">Del</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php else: ?>
  <div style="padding:20px;color:var(--muted);font-size:13px">No senders yet.</div>
  <?php endif; ?>
</div>
</div>
<?php endif; ?>

<script>
async function kbSave(action, formId) {
  var form = document.getElementById(formId);
  var data = new URLSearchParams();
  data.append('action', action);
  var els = form.querySelectorAll('input,textarea,select');
  for (var i = 0; i < els.length; i++) {
    var el = els[i];
    if (el.type === 'checkbox') {
      data.append(el.name, el.checked ? '1' : '0');
    } else {
      data.append(el.name, el.value);
    }
  }
  var r = await fetch('api/kb.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: data.toString()});
  var d = await r.json();
  var msg = document.getElementById('saveMsg');
  if (d.ok) {
    msg.style.background = 'rgba(34,197,94,0.1)';
    msg.style.color = 'var(--success)';
    msg.style.border = '1px solid rgba(34,197,94,0.3)';
    msg.textContent = d.message || 'Saved.';
    msg.style.display = 'block';
    setTimeout(function(){ location.reload(); }, 1200);
  } else {
    msg.style.background = 'rgba(239,68,68,0.1)';
    msg.style.color = 'var(--danger)';
    msg.style.border = '1px solid rgba(239,68,68,0.3)';
    msg.textContent = d.error || 'Error saving.';
    msg.style.display = 'block';
  }
}

async function kbDelete(action, id) {
  if (!confirm('Delete this item?')) return;
  var r = await fetch('api/kb.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action='+action+'&id='+id});
  var d = await r.json();
  if (d.ok) location.reload();
  else alert(d.error || 'Delete failed');
}

function editVertical(v) {
  document.getElementById('vert_id').value = v.id || '';
  document.getElementById('vert_name').value = v.name || '';
  document.getElementById('vert_focus').value = v.focus || '';
  document.getElementById('vert_industries').value = v.industries || '';
  document.getElementById('vert_priority').value = v.priority || 'core';
  document.getElementById('vert_differentiators').value = v.differentiators || '';
  document.getElementById('vert_head_name').value = v.head_name || '';
  document.getElementById('vert_positioning').value = v.positioning || '';
  window.scrollTo(0, 0);
}
function clearVertForm() {
  var ids = ['vert_id','vert_name','vert_focus','vert_industries','vert_differentiators','vert_head_name','vert_positioning'];
  for (var i=0;i<ids.length;i++) document.getElementById(ids[i]).value = '';
  document.getElementById('vert_priority').value = 'core';
}

function editService(s) {
  document.getElementById('svc_id').value = s.id || '';
  document.getElementById('svc_name').value = s.name || '';
  document.getElementById('svc_vertical_id').value = s.vertical_id || '';
  document.getElementById('svc_one_liner').value = s.one_liner || '';
  document.getElementById('svc_signal_types').value = s.signal_types || '';
  document.getElementById('svc_tech_triggers').value = s.tech_triggers || '';
  document.getElementById('svc_signal_keywords').value = s.signal_keywords || '';
  document.getElementById('svc_industries').value = s.industries || '';
  document.getElementById('svc_icp_size').value = s.icp_size || '';
  document.getElementById('svc_buyer_titles').value = s.buyer_titles || '';
  document.getElementById('svc_engagement_model').value = s.engagement_model || '';
  document.getElementById('svc_problem_statement').value = s.problem_statement || '';
  document.getElementById('svc_outcomes').value = s.outcomes || '';
  document.getElementById('svc_differentiators').value = s.differentiators || '';
  document.getElementById('svc_description').value = s.description || '';
  window.scrollTo(0, 0);
}
function clearSvcForm() {
  var ids = ['svc_id','svc_name','svc_one_liner','svc_signal_types','svc_tech_triggers','svc_signal_keywords','svc_industries','svc_icp_size','svc_buyer_titles','svc_engagement_model','svc_problem_statement','svc_outcomes','svc_differentiators','svc_description'];
  for (var i=0;i<ids.length;i++) document.getElementById(ids[i]).value = '';
  document.getElementById('svc_vertical_id').value = '';
}

function editICP(icp) {
  document.getElementById('icp_id').value = icp.id || '';
  document.getElementById('icp_name').value = icp.name || '';
  document.getElementById('icp_vertical_id').value = icp.vertical_id || '';
  document.getElementById('icp_service_id').value = icp.service_id || '';
  document.getElementById('icp_size_range').value = icp.size_range || '';
  document.getElementById('icp_industries').value = icp.industries || '';
  document.getElementById('icp_geographies').value = icp.geographies || '';
  document.getElementById('icp_tech_stack_signals').value = icp.tech_stack_signals || '';
  document.getElementById('icp_trigger_events').value = icp.trigger_events || '';
  document.getElementById('icp_perfect_fit').value = icp.perfect_fit || '';
  document.getElementById('icp_disqualifiers').value = icp.disqualifiers || '';
  window.scrollTo(0, 0);
}
function clearIcpForm() {
  var ids = ['icp_id','icp_name','icp_size_range','icp_industries','icp_geographies','icp_tech_stack_signals','icp_trigger_events','icp_perfect_fit','icp_disqualifiers'];
  for (var i=0;i<ids.length;i++) document.getElementById(ids[i]).value = '';
  document.getElementById('icp_vertical_id').value = '';
  document.getElementById('icp_service_id').value = '';
}

function editSender(s) {
  document.getElementById('sender_id').value = s.id || '';
  document.getElementById('sender_full_name').value = s.full_name || '';
  document.getElementById('sender_title').value = s.title || '';
  document.getElementById('sender_email').value = s.email || '';
  document.getElementById('sender_linkedin_url').value = s.linkedin_url || '';
  document.getElementById('sender_years_experience').value = s.years_experience || '';
  document.getElementById('sender_calendar_link').value = s.calendar_link || '';
  document.getElementById('sender_background').value = s.background || '';
  document.getElementById('sender_credibility').value = s.credibility || '';
  document.getElementById('sender_individual_tone').value = s.individual_tone || '';
  document.getElementById('sender_email_opening_style').value = s.email_opening_style || '';
  document.getElementById('sender_email_closing_style').value = s.email_closing_style || '';
  document.getElementById('sender_verticals').value = s.verticals || '';
  document.getElementById('sender_signature').value = s.signature || '';
  document.getElementById('sender_is_default').checked = s.is_default == 1;
  window.scrollTo(0, 0);
}
function clearSenderForm() {
  var ids = ['sender_id','sender_full_name','sender_title','sender_email','sender_linkedin_url','sender_years_experience','sender_calendar_link','sender_background','sender_credibility','sender_individual_tone','sender_email_opening_style','sender_email_closing_style','sender_verticals','sender_signature'];
  for (var i=0;i<ids.length;i++) document.getElementById(ids[i]).value = '';
  document.getElementById('sender_is_default').checked = false;
}
</script>

<?php include 'layout_end.php'; ?>
