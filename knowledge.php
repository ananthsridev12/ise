<?php
require_once 'config.php';
require_once 'lib/DB.php';

$tabs = array('company','verticals','services','icps','personas','tone','senders','proof','documents','assets');
$tabLabels = array(
  'company'   => 'Company',
  'verticals' => 'Verticals',
  'services'  => 'Services',
  'icps'      => 'ICPs',
  'personas'  => 'Personas',
  'tone'      => 'Tone & Voice',
  'senders'   => 'Senders',
  'proof'     => 'Proof Points',
  'documents' => 'Documents',
  'assets'    => 'Assets & Offers',
);
$tab = $_GET['tab'] ?? 'company';
if (!in_array($tab, $tabs)) $tab = 'company';

$currentPage = 'knowledge';
include 'layout.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">&#128218; Knowledge Hub</div>
    <div class="page-sub">Your company's GTM knowledge base for AI email generation</div>
  </div>
</div>

<div style="display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:24px;overflow-x:auto">
  <?php foreach ($tabs as $t): ?>
  <a href="?tab=<?= $t ?>" style="padding:10px 16px;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;border-bottom:2px solid <?= $tab===$t?'var(--accent)':'transparent' ?>;color:<?= $tab===$t?'var(--accent)':'var(--muted)' ?>">
    <?= htmlspecialchars($tabLabels[$t] ?? ucfirst($t)) ?>
  </a>
  <?php endforeach; ?>
</div>

<div id="saveMsg" style="display:none;padding:12px 18px;border-radius:8px;margin-bottom:20px;font-size:13px;font-weight:500"></div>

<?php if ($tab === 'company'): ?>
<?php $co = DB::fetchOne('SELECT * FROM kb_company LIMIT 1'); ?>
<div class="card" style="max-width:760px">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Company Identity (Block 1)</div>
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
    <button type="button" onclick="kbSave('save_company','coForm',this)" class="btn btn-primary">Save Company Info</button>
    </form>
  </div>
</div>

<?php elseif ($tab === 'verticals'): ?>
<?php $verticals = DB::fetchAll('SELECT * FROM kb_verticals ORDER BY priority, name'); ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Add / Edit Vertical (Business Unit)</div>
  <div style="padding:20px">
    <form id="vertForm">
    <input type="hidden" name="id" id="vert_id" value="">
    <div class="form-group"><label>Name *</label><input type="text" name="name" id="vert_name" required placeholder="e.g. ERP Practice, SCM Division"></div>
    <div class="form-group"><label>Focus</label><textarea name="focus" id="vert_focus" rows="2" placeholder="What this BU specialises in"></textarea></div>
    <div class="form-group"><label>Industries Served</label><input type="text" name="industries" id="vert_industries" placeholder="Manufacturing, Retail, ..."></div>
    <div class="form-group"><label>Priority</label><select name="priority" id="vert_priority"><option value="core">Core</option><option value="growth">Growth</option><option value="emerging">Emerging</option></select></div>
    <div class="form-group"><label>Differentiators</label><textarea name="differentiators" id="vert_differentiators" rows="2"></textarea></div>
    <div class="form-group"><label>Head / Lead</label><input type="text" name="head_name" id="vert_head_name"></div>
    <div class="form-group"><label>Positioning</label><textarea name="positioning" id="vert_positioning" rows="2"></textarea></div>
    <div style="display:flex;gap:8px">
      <button type="button" onclick="kbSave('save_vertical','vertForm',this)" class="btn btn-primary">Save Vertical</button>
      <button type="button" onclick="clearVertForm()" class="btn btn-secondary">Clear</button>
    </div>
    </form>
  </div>
</div>
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Verticals / BUs (<?= count($verticals) ?>)</div>
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
  <div style="padding:20px;color:var(--muted);font-size:13px">No verticals yet. Add your business units above.</div>
  <?php endif; ?>
  <div style="padding:10px 20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <span style="font-size:12px;color:var(--muted)">Bulk import:</span>
    <input type="file" id="imp_verticals" accept=".csv" style="font-size:12px;color:var(--muted)">
    <button onclick="importCSV('verticals','imp_verticals',this)" class="btn btn-secondary btn-sm">Import CSV</button>
    <a href="api/kb_template.php?entity=verticals" class="btn btn-ghost btn-sm">&#8595; Template</a>
  </div>
</div>
</div>

<?php elseif ($tab === 'services'): ?>
<?php
$services  = DB::fetchAll('SELECT s.*, v.name as vertical_name FROM kb_services s LEFT JOIN kb_verticals v ON s.vertical_id = v.id ORDER BY v.name, s.name');
$verticals = DB::fetchAll('SELECT id, name FROM kb_verticals ORDER BY name');
?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Add / Edit Service / Product</div>
  <div style="padding:20px">
    <form id="svcForm">
    <input type="hidden" name="id" id="svc_id" value="">
    <div class="form-group"><label>Service / Product Name *</label><input type="text" name="name" id="svc_name" required></div>
    <div class="form-group"><label>Vertical / BU</label>
      <select name="vertical_id" id="svc_vertical_id">
        <option value="">-- none --</option>
        <?php foreach ($verticals as $v): ?>
        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>One-liner</label><input type="text" name="one_liner" id="svc_one_liner" placeholder="One sentence description"></div>
    <div class="form-group"><label>Signal Types <span style="color:var(--muted);font-size:11px">(comma-sep, e.g. M&amp;A,ERP,Expansion)</span></label><input type="text" name="signal_types" id="svc_signal_types"></div>
    <div class="form-group"><label>Tech Triggers <span style="color:var(--muted);font-size:11px">(comma-sep, e.g. SAP ECC,Oracle EBS)</span></label><input type="text" name="tech_triggers" id="svc_tech_triggers"></div>
    <div class="form-group"><label>Signal Keywords</label><input type="text" name="signal_keywords" id="svc_signal_keywords"></div>
    <div class="form-group"><label>Industries</label><input type="text" name="industries" id="svc_industries"></div>
    <div class="form-group"><label>ICP Size</label><input type="text" name="icp_size" id="svc_icp_size" placeholder="500-5000 employees"></div>
    <div class="form-group"><label>Buyer Titles</label><input type="text" name="buyer_titles" id="svc_buyer_titles" placeholder="CIO, ERP Director, ..."></div>
    <div class="form-group"><label>Engagement Model</label><input type="text" name="engagement_model" id="svc_engagement_model" placeholder="Project, Retainer, T&amp;M"></div>
    <div class="form-group"><label>Problem Statement</label><textarea name="problem_statement" id="svc_problem_statement" rows="2"></textarea></div>
    <div class="form-group"><label>Outcomes / Results</label><textarea name="outcomes" id="svc_outcomes" rows="2"></textarea></div>
    <div class="form-group"><label>Differentiators</label><textarea name="differentiators" id="svc_differentiators" rows="2"></textarea></div>
    <div class="form-group"><label>Description</label><textarea name="description" id="svc_description" rows="3"></textarea></div>
    <div style="display:flex;gap:8px">
      <button type="button" onclick="kbSave('save_service','svcForm',this)" class="btn btn-primary">Save Service</button>
      <button type="button" onclick="clearSvcForm()" class="btn btn-secondary">Clear</button>
    </div>
    </form>
  </div>
</div>
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Services / Products (<?= count($services) ?>)</div>
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
  <div style="padding:10px 20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <span style="font-size:12px;color:var(--muted)">Bulk import:</span>
    <input type="file" id="imp_services" accept=".csv" style="font-size:12px;color:var(--muted)">
    <button onclick="importCSV('services','imp_services',this)" class="btn btn-secondary btn-sm">Import CSV</button>
    <a href="api/kb_template.php?entity=services" class="btn btn-ghost btn-sm">&#8595; Template</a>
  </div>
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
    <div class="form-group"><label>ICP Name * <span style="color:var(--muted);font-size:11px">e.g. &quot;Mid-market ERP Upgrader&quot;</span></label><input type="text" name="name" id="icp_name" required></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="form-group"><label>Vertical / BU</label>
        <select name="vertical_id" id="icp_vertical_id">
          <option value="">-- none --</option>
          <?php foreach ($verticals as $v): ?><option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Service / Product</label>
        <select name="service_id" id="icp_service_id">
          <option value="">-- none --</option>
          <?php foreach ($services as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Company Size Range</label><input type="text" name="size_range" id="icp_size_range" placeholder="500-5000 employees"></div>
      <div class="form-group"><label>Annual Revenue Range</label><input type="text" name="revenue_range" id="icp_revenue_range" placeholder="$50M-$500M"></div>
    </div>
    <div class="form-group"><label>Target Industries</label><input type="text" name="industries" id="icp_industries" placeholder="Manufacturing, Distribution, ..."></div>
    <div class="form-group"><label>Geographies</label><input type="text" name="geographies" id="icp_geographies" placeholder="North America, EMEA, APAC"></div>
    <div class="form-group"><label>Tech Stack Signals</label><input type="text" name="tech_stack_signals" id="icp_tech_stack_signals" placeholder="SAP ECC, Oracle EBS, Legacy ERP"></div>
    <div class="form-group"><label>Trigger Events</label><textarea name="trigger_events" id="icp_trigger_events" rows="2" placeholder="M&amp;A activity, system end-of-life, new CIO hire..."></textarea></div>
    <div class="form-group"><label>Perfect Fit Signals</label><textarea name="perfect_fit" id="icp_perfect_fit" rows="2"></textarea></div>
    <div class="form-group"><label>Poor Fit / Disqualifiers</label><textarea name="disqualifiers" id="icp_disqualifiers" rows="2"></textarea></div>
    <div class="form-group"><label>Buying Process Notes</label><textarea name="buying_process" id="icp_buying_process" rows="2" placeholder="Typical deal length, committee size, budget cycle..."></textarea></div>
    <div style="display:flex;gap:8px">
      <button type="button" onclick="kbSave('save_icp','icpForm',this)" class="btn btn-primary">Save ICP</button>
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
          <?php if ($icp['size_range']): ?> &middot; <?= htmlspecialchars($icp['size_range']) ?><?php endif; ?>
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
  <div style="padding:10px 20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <span style="font-size:12px;color:var(--muted)">Bulk import:</span>
    <input type="file" id="imp_icps" accept=".csv" style="font-size:12px;color:var(--muted)">
    <button onclick="importCSV('icps','imp_icps',this)" class="btn btn-secondary btn-sm">Import CSV</button>
    <a href="api/kb_template.php?entity=icps" class="btn btn-ghost btn-sm">&#8595; Template</a>
  </div>
</div>
</div>

<?php elseif ($tab === 'personas'): ?>
<?php
$personas  = DB::fetchAll('SELECT p.*, v.name as v_name, s.name as s_name FROM kb_personas p LEFT JOIN kb_verticals v ON p.vertical_id=v.id LEFT JOIN kb_services s ON p.service_id=s.id ORDER BY p.name');
$verticals = DB::fetchAll('SELECT id, name FROM kb_verticals ORDER BY name');
$services  = DB::fetchAll('SELECT id, name FROM kb_services ORDER BY name');
$seniorityOpts = array('C-Suite','VP','Director','Manager','Individual Contributor');
?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Add / Edit Buyer Persona (Block 5)</div>
  <div style="padding:20px">
    <form id="personaForm">
    <input type="hidden" name="id" id="persona_id" value="">
    <div class="form-group"><label>Persona Name * <span style="color:var(--muted);font-size:11px">e.g. &quot;The Digital CIO&quot;</span></label><input type="text" name="name" id="persona_name" required placeholder="The Operational CFO"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="form-group"><label>Job Title *</label><input type="text" name="title" id="persona_title" required placeholder="VP of IT, ERP Program Director"></div>
      <div class="form-group"><label>Department *</label><input type="text" name="department" id="persona_department" required placeholder="IT, Finance, Operations"></div>
      <div class="form-group"><label>Seniority</label>
        <select name="seniority" id="persona_seniority">
          <?php foreach ($seniorityOpts as $opt): ?>
          <option value="<?= $opt ?>"><?= $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Reports To</label><input type="text" name="reporting_to" id="persona_reporting_to" placeholder="CIO, CFO, COO"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="form-group"><label>Vertical / BU</label>
        <select name="vertical_id" id="persona_vertical_id">
          <option value="">-- none --</option>
          <?php foreach ($verticals as $v): ?><option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Service / Product</label>
        <select name="service_id" id="persona_service_id">
          <option value="">-- none --</option>
          <?php foreach ($services as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-group"><label>Goals *</label><textarea name="goals" id="persona_goals" rows="2" required placeholder="Reduce system downtime, consolidate platforms..."></textarea></div>
    <div class="form-group"><label>Pain Points *</label><textarea name="pain_points" id="persona_pain_points" rows="2" required placeholder="Legacy systems, manual processes, data silos..."></textarea></div>
    <div class="form-group"><label>Typical Objections</label><textarea name="objections" id="persona_objections" rows="2" placeholder="Too expensive, bad timing, need board approval..."></textarea></div>
    <div class="form-group"><label>KPIs They're Measured On</label><input type="text" name="kpis" id="persona_kpis" placeholder="System uptime, project delivery %, cost savings"></div>
    <div class="form-group"><label>Decision-Making Role</label>
      <select name="decision_role" id="persona_decision_role">
        <option value="Economic Buyer">Economic Buyer (signs the PO)</option>
        <option value="Champion">Champion (internal advocate)</option>
        <option value="Technical Buyer">Technical Buyer (evaluates fit)</option>
        <option value="End User">End User</option>
        <option value="Influencer">Influencer</option>
        <option value="Blocker">Blocker / Detractor</option>
      </select>
    </div>
    <div class="form-group"><label>Communication Style</label><textarea name="communication_style" id="persona_communication_style" rows="2" placeholder="Data-driven, prefers short emails, likes ROI focus..."></textarea></div>
    <div class="form-group"><label>Preferred Content Types</label><input type="text" name="preferred_content" id="persona_preferred_content" placeholder="Case studies, ROI calculators, live demos"></div>
    <div class="form-group"><label>Watering Holes</label><input type="text" name="watering_holes" id="persona_watering_holes" placeholder="LinkedIn, Gartner, SAP events, ASUG..."></div>
    <div class="form-group"><label>Email Hook / Opening Angle</label><textarea name="email_hook" id="persona_email_hook" rows="2" placeholder="Lead with operational risk, ROI, peer benchmarks..."></textarea></div>
    <div style="display:flex;gap:8px">
      <button type="button" onclick="kbSave('save_persona','personaForm',this)" class="btn btn-primary">Save Persona</button>
      <button type="button" onclick="clearPersonaForm()" class="btn btn-secondary">Clear</button>
    </div>
    </form>
  </div>
</div>
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Buyer Personas (<?= count($personas) ?>)</div>
  <?php if ($personas): ?>
  <?php foreach ($personas as $p): ?>
  <div style="padding:12px 20px;border-bottom:1px solid rgba(42,45,58,0.4)">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($p['name']) ?></div>
        <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($p['title']??'') ?> &middot; <?= htmlspecialchars($p['seniority']??'') ?></div>
        <?php if ($p['v_name'] || $p['s_name']): ?>
        <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($p['v_name']??'') ?><?php if ($p['s_name']): ?> &middot; <?= htmlspecialchars($p['s_name']) ?><?php endif; ?></div>
        <?php endif; ?>
      </div>
      <div style="display:flex;gap:6px">
        <button onclick="editPersona(<?= htmlspecialchars(json_encode($p)) ?>)" class="btn btn-secondary btn-sm">Edit</button>
        <button onclick="kbDelete('delete_persona',<?= $p['id'] ?>)" class="btn btn-ghost btn-sm" style="color:var(--danger)">Del</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php else: ?>
  <div style="padding:20px;color:var(--muted);font-size:13px">No personas yet.</div>
  <?php endif; ?>
  <div style="padding:10px 20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <span style="font-size:12px;color:var(--muted)">Bulk import:</span>
    <input type="file" id="imp_personas" accept=".csv" style="font-size:12px;color:var(--muted)">
    <button onclick="importCSV('personas','imp_personas',this)" class="btn btn-secondary btn-sm">Import CSV</button>
    <a href="api/kb_template.php?entity=personas" class="btn btn-ghost btn-sm">&#8595; Template</a>
  </div>
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
    <button type="button" onclick="kbSave('save_tone','toneForm',this)" class="btn btn-primary">Save Tone Settings</button>
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
      <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" id="sender_full_name" required></div>
      <div class="form-group"><label>Title *</label><input type="text" name="title" id="sender_title" required></div>
      <div class="form-group"><label>Email *</label><input type="email" name="email" id="sender_email" required></div>
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
      <button type="button" onclick="kbSave('save_sender','senderForm',this)" class="btn btn-primary">Save Sender</button>
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

<?php elseif ($tab === 'proof'): ?>
<?php
$proofs    = DB::fetchAll('SELECT p.*, v.name as v_name, s.name as s_name FROM kb_proof p LEFT JOIN kb_verticals v ON p.vertical_id=v.id LEFT JOIN kb_services s ON p.service_id=s.id ORDER BY p.client_name');
$verticals = DB::fetchAll('SELECT id, name FROM kb_verticals ORDER BY name');
$services  = DB::fetchAll('SELECT id, name FROM kb_services ORDER BY name');
?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Add / Edit Proof Point (Block 8)</div>
  <div style="padding:20px">
    <form id="proofForm">
    <input type="hidden" name="id" id="proof_id" value="">
    <div class="form-group"><label>Client Name *</label><input type="text" name="client_name" id="proof_client_name" required></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="form-group"><label>Industry</label><input type="text" name="client_industry" id="proof_client_industry"></div>
      <div class="form-group"><label>Client Size</label><input type="text" name="client_size" id="proof_client_size" placeholder="e.g. 2,000 employees"></div>
      <div class="form-group"><label>Vertical / BU</label>
        <select name="vertical_id" id="proof_vertical_id">
          <option value="">-- none --</option>
          <?php foreach ($verticals as $v): ?><option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Service / Product</label>
        <select name="service_id" id="proof_service_id">
          <option value="">-- none --</option>
          <?php foreach ($services as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-group"><label>Challenge / Problem</label><textarea name="challenge" id="proof_challenge" rows="2"></textarea></div>
    <div class="form-group"><label>Solution We Provided</label><textarea name="solution" id="proof_solution" rows="2"></textarea></div>
    <div class="form-group"><label>Outcomes / Results</label><textarea name="outcomes" id="proof_outcomes" rows="2"></textarea></div>
    <div class="form-group"><label>Key Metrics <span style="color:var(--muted);font-size:11px">(e.g. 30% cost reduction, 6-month go-live)</span></label><input type="text" name="metrics" id="proof_metrics"></div>
    <div class="form-group"><label>Client Quote</label><textarea name="quote" id="proof_quote" rows="2"></textarea></div>
    <div class="form-group"><label>Quote Attribution <span style="color:var(--muted);font-size:11px">(Name, Title at Company)</span></label><input type="text" name="quote_attribution" id="proof_quote_attribution"></div>
    <div class="form-group" style="display:flex;align-items:center;gap:10px">
      <input type="checkbox" name="is_public" id="proof_is_public" value="1" checked>
      <label for="proof_is_public" style="margin:0;cursor:pointer">Public / shareable</label>
    </div>
    <div style="display:flex;gap:8px">
      <button type="button" onclick="kbSave('save_proof','proofForm',this)" class="btn btn-primary">Save Proof Point</button>
      <button type="button" onclick="clearProofForm()" class="btn btn-secondary">Clear</button>
    </div>
    </form>
  </div>
</div>
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Proof Points / Case Studies (<?= count($proofs) ?>)</div>
  <?php if ($proofs): ?>
  <?php foreach ($proofs as $p): ?>
  <div style="padding:12px 20px;border-bottom:1px solid rgba(42,45,58,0.4)">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($p['client_name']) ?></div>
        <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($p['client_industry']??'') ?><?php if($p['v_name']): ?> &middot; <?= htmlspecialchars($p['v_name']) ?><?php endif; ?></div>
        <?php if ($p['metrics']): ?><div style="font-size:11px;color:var(--accent)"><?= htmlspecialchars($p['metrics']) ?></div><?php endif; ?>
      </div>
      <div style="display:flex;gap:6px">
        <button onclick="editProof(<?= htmlspecialchars(json_encode($p)) ?>)" class="btn btn-secondary btn-sm">Edit</button>
        <button onclick="kbDelete('delete_proof',<?= $p['id'] ?>)" class="btn btn-ghost btn-sm" style="color:var(--danger)">Del</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php else: ?>
  <div style="padding:20px;color:var(--muted);font-size:13px">No proof points yet.</div>
  <?php endif; ?>
</div>
</div>

<?php elseif ($tab === 'assets'): ?>
<?php
$assets   = DB::fetchAll(
    'SELECT a.*, s.name as service_name, v.name as vertical_name FROM kb_assets a LEFT JOIN kb_services s ON a.service_id=s.id LEFT JOIN kb_verticals v ON a.vertical_id=v.id WHERE a.tenant_id=? ORDER BY a.category, a.name',
    array(Auth::tenantId())
);
$assetServices = DB::fetchAll(
    'SELECT s.id, s.name, v.name as vname FROM kb_services s LEFT JOIN kb_verticals v ON s.vertical_id=v.id WHERE s.tenant_id=? ORDER BY vname, s.name',
    array(Auth::tenantId())
);
$assetVerticals = DB::fetchAll('SELECT id, name FROM kb_verticals WHERE tenant_id=? ORDER BY name', array(Auth::tenantId()));
?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Add / Edit Asset or Offer</div>
  <div style="padding:20px">
    <form id="assetForm">
    <input type="hidden" name="id" id="asset_id" value="">
    <div class="form-group"><label>Name *</label><input type="text" name="name" id="asset_name" required placeholder="e.g. ERP Readiness Checklist"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="form-group"><label>Category</label>
        <select name="category" id="asset_category">
          <option value="content">Content Asset</option>
          <option value="tool">Tool / Assessment</option>
          <option value="entry_door">Entry Door / Offer</option>
        </select>
      </div>
      <div class="form-group"><label>Asset Type</label>
        <select name="asset_type" id="asset_asset_type">
          <option value="pdf">PDF</option>
          <option value="guide">Guide</option>
          <option value="whitepaper">Whitepaper</option>
          <option value="assessment">Assessment</option>
          <option value="calculator">Calculator</option>
          <option value="diagnostic">Diagnostic</option>
          <option value="checklist">Checklist</option>
          <option value="template">Template</option>
          <option value="webinar">Webinar</option>
          <option value="free_audit">Free Audit</option>
          <option value="poc">POC</option>
          <option value="pilot">Pilot</option>
          <option value="consultation">Consultation</option>
          <option value="other">Other</option>
        </select>
      </div>
    </div>
    <div class="form-group"><label>Service</label>
      <select name="service_id" id="asset_service_id">
        <option value="">-- none --</option>
        <?php
        $lastVname = null;
        foreach ($assetServices as $s):
          if ($s['vname'] !== $lastVname):
            if ($lastVname !== null) echo '</optgroup>';
            echo '<optgroup label="' . htmlspecialchars($s['vname'] ?? 'No Vertical') . '">';
            $lastVname = $s['vname'];
          endif;
        ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
        <?php if ($lastVname !== null): ?></optgroup><?php endif; ?>
      </select>
    </div>
    <div class="form-group"><label>Vertical <span style="color:var(--muted);font-size:11px">(optional, for assets not tied to a specific service)</span></label>
      <select name="vertical_id" id="asset_vertical_id">
        <option value="">-- none --</option>
        <?php foreach ($assetVerticals as $v): ?>
        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Description</label><textarea name="description" id="asset_description" rows="2"></textarea></div>
    <div class="form-group"><label>URL <span style="color:var(--muted);font-size:11px">(download / landing page link)</span></label><input type="url" name="url" id="asset_url" placeholder="https://..."></div>
    <div class="form-group"><label>CTA Text <span style="color:var(--muted);font-size:11px">e.g. "Download the free checklist"</span></label><input type="text" name="cta_text" id="asset_cta_text" placeholder="Download the free checklist"></div>
    <div class="form-group"><label>Use in Touch # <span style="color:var(--muted);font-size:11px">comma-sep, e.g. 2,3</span></label><input type="text" name="use_in_touch" id="asset_use_in_touch" value="2" placeholder="2"></div>
    <div class="form-group"><label>Target Stage</label>
      <select name="target_stage" id="asset_target_stage">
        <option value="awareness">Awareness</option>
        <option value="consideration" selected>Consideration</option>
        <option value="decision">Decision</option>
      </select>
    </div>
    <div class="form-group" style="display:flex;align-items:center;gap:10px">
      <input type="checkbox" name="is_active" id="asset_is_active" value="1" checked>
      <label for="asset_is_active" style="margin:0;cursor:pointer">Active</label>
    </div>
    <div style="display:flex;gap:8px">
      <button type="button" onclick="kbSave('save_asset','assetForm',this)" class="btn btn-primary">Save Asset</button>
      <button type="button" onclick="clearAssetForm()" class="btn btn-secondary">Clear</button>
    </div>
    </form>
  </div>
</div>
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Assets &amp; Offers (<?= count($assets) ?>)</div>
  <?php if ($assets): ?>
  <?php foreach ($assets as $a): ?>
  <?php
    $catBadge = $a['category'] === 'content' ? 'background:rgba(99,102,241,0.15);color:#818cf8' : ($a['category'] === 'tool' ? 'background:rgba(34,197,94,0.12);color:var(--success)' : 'background:rgba(245,158,11,0.15);color:#f59e0b');
  ?>
  <div style="padding:12px 20px;border-bottom:1px solid rgba(42,45,58,0.4)">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($a['name']) ?> <span style="font-size:10px;padding:2px 7px;border-radius:4px;<?= $catBadge ?>"><?= htmlspecialchars($a['category']) ?></span></div>
        <div style="font-size:11px;color:var(--muted)">
          <?= htmlspecialchars($a['service_name'] ?? ($a['vertical_name'] ?? '')) ?>
          &middot; Touch <?= htmlspecialchars($a['use_in_touch']) ?>
          <?php if (!$a['is_active']): ?> <span style="color:var(--danger)">(inactive)</span><?php endif; ?>
        </div>
      </div>
      <div style="display:flex;gap:6px;white-space:nowrap">
        <button onclick="editAsset(<?= htmlspecialchars(json_encode($a)) ?>)" class="btn btn-secondary btn-sm">Edit</button>
        <button onclick="kbDelete('delete_asset',<?= $a['id'] ?>)" class="btn btn-ghost btn-sm" style="color:var(--danger)">Del</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php else: ?>
  <div style="padding:20px;color:var(--muted);font-size:13px">No assets yet. Add content assets, tools, or entry-door offers above.</div>
  <?php endif; ?>
  <div style="padding:10px 20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <span style="font-size:12px;color:var(--muted)">Bulk import:</span>
    <input type="file" id="imp_assets" accept=".csv" style="font-size:12px;color:var(--muted)">
    <button onclick="importCSV('assets','imp_assets',this)" class="btn btn-secondary btn-sm">Import CSV</button>
    <a href="api/kb_template.php?entity=assets" class="btn btn-ghost btn-sm">&#8595; Template</a>
  </div>
</div>
</div>

<?php elseif ($tab === 'documents'): ?>
<?php
$docs      = DB::fetchAll('SELECT d.*, v.name as v_name, s.name as s_name FROM kb_documents d LEFT JOIN kb_verticals v ON d.vertical_id=v.id LEFT JOIN kb_services s ON d.service_id=s.id ORDER BY d.doc_type, d.title');
$verticals = DB::fetchAll('SELECT id, name FROM kb_verticals ORDER BY name');
$services  = DB::fetchAll('SELECT id, name FROM kb_services ORDER BY name');
$docTypes  = array('case_study','whitepaper','brochure','deck','one_pager','roi_calculator','video','other');
?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Add / Edit Document / Collateral (Block 9)</div>
  <div style="padding:20px">
    <form id="docForm">
    <input type="hidden" name="id" id="doc_id" value="">
    <div class="form-group"><label>Title *</label><input type="text" name="title" id="doc_title" required></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="form-group"><label>Document Type</label>
        <select name="doc_type" id="doc_doc_type">
          <?php foreach ($docTypes as $dt): ?>
          <option value="<?= $dt ?>"><?= str_replace('_',' ',ucfirst($dt)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Vertical / BU</label>
        <select name="vertical_id" id="doc_vertical_id">
          <option value="">-- none --</option>
          <?php foreach ($verticals as $v): ?><option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-group"><label>Service / Product</label>
      <select name="service_id" id="doc_service_id">
        <option value="">-- none --</option>
        <?php foreach ($services as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>URL / Link *</label><input type="url" name="url" id="doc_url" required placeholder="https://..."></div>
    <div class="form-group"><label>Description</label><textarea name="description" id="doc_description" rows="2"></textarea></div>
    <div class="form-group"><label>When to Use <span style="color:var(--muted);font-size:11px">(context for AI to reference)</span></label><textarea name="use_case" id="doc_use_case" rows="2" placeholder="Use when prospect has M&amp;A signal and runs SAP ECC..."></textarea></div>
    <div class="form-group" style="display:flex;align-items:center;gap:10px">
      <input type="checkbox" name="is_public" id="doc_is_public" value="1" checked>
      <label for="doc_is_public" style="margin:0;cursor:pointer">Public / shareable</label>
    </div>
    <div style="display:flex;gap:8px">
      <button type="button" onclick="kbSave('save_document','docForm',this)" class="btn btn-primary">Save Document</button>
      <button type="button" onclick="clearDocForm()" class="btn btn-secondary">Clear</button>
    </div>
    </form>
  </div>
</div>
<div class="card">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Documents / Collateral (<?= count($docs) ?>)</div>
  <?php if ($docs): ?>
  <?php foreach ($docs as $d): ?>
  <div style="padding:12px 20px;border-bottom:1px solid rgba(42,45,58,0.4)">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($d['title']) ?></div>
        <div style="font-size:11px;color:var(--muted)"><?= str_replace('_',' ',ucfirst($d['doc_type']??'')) ?><?php if($d['v_name']): ?> &middot; <?= htmlspecialchars($d['v_name']) ?><?php endif; ?></div>
        <?php if ($d['url']): ?><div style="font-size:11px"><a href="<?= htmlspecialchars($d['url']) ?>" target="_blank" rel="noopener" style="color:var(--accent)">Open link</a></div><?php endif; ?>
      </div>
      <div style="display:flex;gap:6px">
        <button onclick="editDocument(<?= htmlspecialchars(json_encode($d)) ?>)" class="btn btn-secondary btn-sm">Edit</button>
        <button onclick="kbDelete('delete_document',<?= $d['id'] ?>)" class="btn btn-ghost btn-sm" style="color:var(--danger)">Del</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php else: ?>
  <div style="padding:20px;color:var(--muted);font-size:13px">No documents yet.</div>
  <?php endif; ?>
</div>
</div>
<?php endif; ?>

<script>
function showMsg(ok, text) {
  var msg = document.getElementById('saveMsg');
  if (ok) {
    msg.style.cssText = 'display:block;padding:12px 18px;border-radius:8px;margin-bottom:20px;font-size:13px;font-weight:500;background:rgba(34,197,94,0.12);color:#22c55e;border:1px solid rgba(34,197,94,0.4)';
  } else {
    msg.style.cssText = 'display:block;padding:12px 18px;border-radius:8px;margin-bottom:20px;font-size:13px;font-weight:500;background:rgba(239,68,68,0.12);color:#ef4444;border:1px solid rgba(239,68,68,0.4)';
  }
  msg.textContent = text;
  msg.scrollIntoView({behavior:'smooth', block:'nearest'});
}

async function kbSave(action, formId, btn) {
  var origText = btn.textContent;
  btn.disabled = true; btn.textContent = 'Saving...';
  try {
    var form = document.getElementById(formId);
    var data = new URLSearchParams();
    data.append('action', action);
    var els = form.querySelectorAll('input,textarea,select');
    for (var i = 0; i < els.length; i++) {
      var el = els[i];
      if (!el.name) continue;
      if (el.type === 'checkbox') { data.append(el.name, el.checked ? '1' : '0'); }
      else { data.append(el.name, el.value); }
    }
    var r = await fetch('api/kb.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:data.toString()});
    if (!r.ok) { showMsg(false, 'Server error ' + r.status + ' — check that api/ folder has 755 permissions.'); btn.disabled=false; btn.textContent=origText; return; }
    var text = await r.text();
    var d;
    try { d = JSON.parse(text); } catch(e) { showMsg(false, 'Unexpected response: ' + text.substring(0,150)); btn.disabled=false; btn.textContent=origText; return; }
    if (d.ok) { showMsg(true, '✓ Saved successfully.'); setTimeout(function(){ location.reload(); }, 1800); }
    else { showMsg(false, d.error || 'Save failed.'); btn.disabled=false; btn.textContent=origText; }
  } catch(e) { showMsg(false, 'Network error: ' + e.message); btn.disabled=false; btn.textContent=origText; }
}

async function kbDelete(action, id) {
  if (!confirm('Delete this item?')) return;
  try {
    var r = await fetch('api/kb.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action='+action+'&id='+id});
    var d = await r.json();
    if (d.ok) location.reload();
    else showMsg(false, d.error || 'Delete failed');
  } catch(e) { showMsg(false, 'Network error: ' + e.message); }
}

async function importCSV(entity, fileInputId, btn) {
  var fileInput = document.getElementById(fileInputId);
  if (!fileInput.files.length) { showMsg(false, 'Select a CSV file first.'); return; }
  var origText = btn.textContent;
  btn.disabled = true; btn.textContent = 'Importing...';
  try {
    var fd = new FormData();
    fd.append('entity', entity);
    fd.append('file', fileInput.files[0]);
    var r = await fetch('api/kb_import.php', {method:'POST', body:fd});
    var d = await r.json();
    if (d.ok) { showMsg(true, d.message); setTimeout(function(){ location.reload(); }, 1800); }
    else { showMsg(false, d.error || 'Import failed.'); btn.disabled=false; btn.textContent=origText; }
  } catch(e) { showMsg(false, 'Network error: ' + e.message); btn.disabled=false; btn.textContent=origText; }
}

function editVertical(v) {
  document.getElementById('vert_id').value = v.id||'';
  document.getElementById('vert_name').value = v.name||'';
  document.getElementById('vert_focus').value = v.focus||'';
  document.getElementById('vert_industries').value = v.industries||'';
  document.getElementById('vert_priority').value = v.priority||'core';
  document.getElementById('vert_differentiators').value = v.differentiators||'';
  document.getElementById('vert_head_name').value = v.head_name||'';
  document.getElementById('vert_positioning').value = v.positioning||'';
  window.scrollTo(0,0);
}
function clearVertForm() {
  ['vert_id','vert_name','vert_focus','vert_industries','vert_differentiators','vert_head_name','vert_positioning'].forEach(function(id){ document.getElementById(id).value=''; });
  document.getElementById('vert_priority').value='core';
}

function editService(s) {
  document.getElementById('svc_id').value = s.id||'';
  document.getElementById('svc_name').value = s.name||'';
  document.getElementById('svc_vertical_id').value = s.vertical_id||'';
  document.getElementById('svc_one_liner').value = s.one_liner||'';
  document.getElementById('svc_signal_types').value = s.signal_types||'';
  document.getElementById('svc_tech_triggers').value = s.tech_triggers||'';
  document.getElementById('svc_signal_keywords').value = s.signal_keywords||'';
  document.getElementById('svc_industries').value = s.industries||'';
  document.getElementById('svc_icp_size').value = s.icp_size||'';
  document.getElementById('svc_buyer_titles').value = s.buyer_titles||'';
  document.getElementById('svc_engagement_model').value = s.engagement_model||'';
  document.getElementById('svc_problem_statement').value = s.problem_statement||'';
  document.getElementById('svc_outcomes').value = s.outcomes||'';
  document.getElementById('svc_differentiators').value = s.differentiators||'';
  document.getElementById('svc_description').value = s.description||'';
  window.scrollTo(0,0);
}
function clearSvcForm() {
  ['svc_id','svc_name','svc_one_liner','svc_signal_types','svc_tech_triggers','svc_signal_keywords','svc_industries','svc_icp_size','svc_buyer_titles','svc_engagement_model','svc_problem_statement','svc_outcomes','svc_differentiators','svc_description'].forEach(function(id){ document.getElementById(id).value=''; });
  document.getElementById('svc_vertical_id').value='';
}

function editICP(icp) {
  document.getElementById('icp_id').value = icp.id||'';
  document.getElementById('icp_name').value = icp.name||'';
  document.getElementById('icp_vertical_id').value = icp.vertical_id||'';
  document.getElementById('icp_service_id').value = icp.service_id||'';
  document.getElementById('icp_size_range').value = icp.size_range||'';
  document.getElementById('icp_revenue_range').value = icp.revenue_range||'';
  document.getElementById('icp_industries').value = icp.industries||'';
  document.getElementById('icp_geographies').value = icp.geographies||'';
  document.getElementById('icp_tech_stack_signals').value = icp.tech_stack_signals||'';
  document.getElementById('icp_trigger_events').value = icp.trigger_events||'';
  document.getElementById('icp_perfect_fit').value = icp.perfect_fit||'';
  document.getElementById('icp_disqualifiers').value = icp.disqualifiers||'';
  document.getElementById('icp_buying_process').value = icp.buying_process||'';
  window.scrollTo(0,0);
}
function clearIcpForm() {
  ['icp_id','icp_name','icp_size_range','icp_revenue_range','icp_industries','icp_geographies','icp_tech_stack_signals','icp_trigger_events','icp_perfect_fit','icp_disqualifiers','icp_buying_process'].forEach(function(id){ document.getElementById(id).value=''; });
  document.getElementById('icp_vertical_id').value='';
  document.getElementById('icp_service_id').value='';
}

function editPersona(p) {
  document.getElementById('persona_id').value = p.id||'';
  document.getElementById('persona_name').value = p.name||'';
  document.getElementById('persona_title').value = p.title||'';
  document.getElementById('persona_department').value = p.department||'';
  document.getElementById('persona_seniority').value = p.seniority||'Director';
  document.getElementById('persona_reporting_to').value = p.reporting_to||'';
  document.getElementById('persona_vertical_id').value = p.vertical_id||'';
  document.getElementById('persona_service_id').value = p.service_id||'';
  document.getElementById('persona_goals').value = p.goals||'';
  document.getElementById('persona_pain_points').value = p.pain_points||'';
  document.getElementById('persona_objections').value = p.objections||'';
  document.getElementById('persona_kpis').value = p.kpis||'';
  document.getElementById('persona_decision_role').value = p.decision_role||'Economic Buyer';
  document.getElementById('persona_communication_style').value = p.communication_style||'';
  document.getElementById('persona_preferred_content').value = p.preferred_content||'';
  document.getElementById('persona_watering_holes').value = p.watering_holes||'';
  document.getElementById('persona_email_hook').value = p.email_hook||'';
  window.scrollTo(0,0);
}
function clearPersonaForm() {
  ['persona_id','persona_name','persona_title','persona_department','persona_reporting_to','persona_goals','persona_pain_points','persona_objections','persona_kpis','persona_communication_style','persona_preferred_content','persona_watering_holes','persona_email_hook'].forEach(function(id){ document.getElementById(id).value=''; });
  document.getElementById('persona_seniority').value='Director';
  document.getElementById('persona_decision_role').value='Economic Buyer';
  document.getElementById('persona_vertical_id').value='';
  document.getElementById('persona_service_id').value='';
}

function editSender(s) {
  document.getElementById('sender_id').value = s.id||'';
  document.getElementById('sender_full_name').value = s.full_name||'';
  document.getElementById('sender_title').value = s.title||'';
  document.getElementById('sender_email').value = s.email||'';
  document.getElementById('sender_linkedin_url').value = s.linkedin_url||'';
  document.getElementById('sender_years_experience').value = s.years_experience||'';
  document.getElementById('sender_calendar_link').value = s.calendar_link||'';
  document.getElementById('sender_background').value = s.background||'';
  document.getElementById('sender_credibility').value = s.credibility||'';
  document.getElementById('sender_individual_tone').value = s.individual_tone||'';
  document.getElementById('sender_email_opening_style').value = s.email_opening_style||'';
  document.getElementById('sender_email_closing_style').value = s.email_closing_style||'';
  document.getElementById('sender_verticals').value = s.verticals||'';
  document.getElementById('sender_signature').value = s.signature||'';
  document.getElementById('sender_is_default').checked = s.is_default==1;
  window.scrollTo(0,0);
}
function clearSenderForm() {
  ['sender_id','sender_full_name','sender_title','sender_email','sender_linkedin_url','sender_years_experience','sender_calendar_link','sender_background','sender_credibility','sender_individual_tone','sender_email_opening_style','sender_email_closing_style','sender_verticals','sender_signature'].forEach(function(id){ document.getElementById(id).value=''; });
  document.getElementById('sender_is_default').checked=false;
}

function editProof(p) {
  document.getElementById('proof_id').value = p.id||'';
  document.getElementById('proof_client_name').value = p.client_name||'';
  document.getElementById('proof_client_industry').value = p.client_industry||'';
  document.getElementById('proof_client_size').value = p.client_size||'';
  document.getElementById('proof_vertical_id').value = p.vertical_id||'';
  document.getElementById('proof_service_id').value = p.service_id||'';
  document.getElementById('proof_challenge').value = p.challenge||'';
  document.getElementById('proof_solution').value = p.solution||'';
  document.getElementById('proof_outcomes').value = p.outcomes||'';
  document.getElementById('proof_metrics').value = p.metrics||'';
  document.getElementById('proof_quote').value = p.quote||'';
  document.getElementById('proof_quote_attribution').value = p.quote_attribution||'';
  document.getElementById('proof_is_public').checked = p.is_public!=0;
  window.scrollTo(0,0);
}
function clearProofForm() {
  ['proof_id','proof_client_name','proof_client_industry','proof_client_size','proof_challenge','proof_solution','proof_outcomes','proof_metrics','proof_quote','proof_quote_attribution'].forEach(function(id){ document.getElementById(id).value=''; });
  document.getElementById('proof_vertical_id').value='';
  document.getElementById('proof_service_id').value='';
  document.getElementById('proof_is_public').checked=true;
}

function editDocument(d) {
  document.getElementById('doc_id').value = d.id||'';
  document.getElementById('doc_title').value = d.title||'';
  document.getElementById('doc_doc_type').value = d.doc_type||'other';
  document.getElementById('doc_vertical_id').value = d.vertical_id||'';
  document.getElementById('doc_service_id').value = d.service_id||'';
  document.getElementById('doc_url').value = d.url||'';
  document.getElementById('doc_description').value = d.description||'';
  document.getElementById('doc_use_case').value = d.use_case||'';
  document.getElementById('doc_is_public').checked = d.is_public!=0;
  window.scrollTo(0,0);
}
function clearDocForm() {
  ['doc_id','doc_title','doc_url','doc_description','doc_use_case'].forEach(function(id){ document.getElementById(id).value=''; });
  document.getElementById('doc_doc_type').value='other';
  document.getElementById('doc_vertical_id').value='';
  document.getElementById('doc_service_id').value='';
  document.getElementById('doc_is_public').checked=true;
}

function editAsset(a) {
  document.getElementById('asset_id').value = a.id||'';
  document.getElementById('asset_name').value = a.name||'';
  document.getElementById('asset_category').value = a.category||'content';
  document.getElementById('asset_asset_type').value = a.asset_type||'pdf';
  document.getElementById('asset_service_id').value = a.service_id||'';
  document.getElementById('asset_vertical_id').value = a.vertical_id||'';
  document.getElementById('asset_description').value = a.description||'';
  document.getElementById('asset_url').value = a.url||'';
  document.getElementById('asset_cta_text').value = a.cta_text||'';
  document.getElementById('asset_use_in_touch').value = a.use_in_touch||'2';
  document.getElementById('asset_target_stage').value = a.target_stage||'consideration';
  document.getElementById('asset_is_active').checked = a.is_active!=0;
  window.scrollTo(0,0);
}
function clearAssetForm() {
  ['asset_id','asset_name','asset_description','asset_url','asset_cta_text'].forEach(function(id){ document.getElementById(id).value=''; });
  document.getElementById('asset_category').value='content';
  document.getElementById('asset_asset_type').value='pdf';
  document.getElementById('asset_service_id').value='';
  document.getElementById('asset_vertical_id').value='';
  document.getElementById('asset_use_in_touch').value='2';
  document.getElementById('asset_target_stage').value='consideration';
  document.getElementById('asset_is_active').checked=true;
}
</script>

<?php include 'layout_end.php'; ?>
