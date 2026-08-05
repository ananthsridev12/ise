<?php
require_once 'config.php';
require_once 'lib/DB.php';

$msg = '';
$ai  = DB::fetchOne('SELECT * FROM ai_settings LIMIT 1') ?: array();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sequence') {
    // Save sequence strategy settings
    $seqFields = array(
        'default_stage' => $_POST['default_stage'] ?? 'auto',
        'tof_score_max' => (int)($_POST['tof_score_max'] ?? 40),
        'bof_score_min' => (int)($_POST['bof_score_min'] ?? 71),
        'tof_sequence'  => json_encode(array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", '', $_POST['tof_sequence'] ?? '')))))),
        'mof_sequence'  => json_encode(array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", '', $_POST['mof_sequence'] ?? '')))))),
        'bof_sequence'  => json_encode(array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", '', $_POST['bof_sequence'] ?? '')))))),
    );
    if ($ai) {
        DB::update('ai_settings', $seqFields, 'id = ?', array($ai['id']));
    } else {
        DB::insert('ai_settings', $seqFields);
    }
    $ai  = DB::fetchOne('SELECT * FROM ai_settings LIMIT 1') ?: array();
    $msg = 'Sequence strategy saved.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $fields = array(
        'provider'            => $_POST['provider'] ?? 'gemini',
        'gemini_key'          => trim($_POST['gemini_key'] ?? ''),
        'claude_key'          => trim($_POST['claude_key'] ?? ''),
        'openai_key'          => trim($_POST['openai_key'] ?? ''),
        'model'               => trim($_POST['model'] ?? ''),
        'email_length'        => $_POST['email_length'] ?? 'medium',
        'num_touches'         => (int)($_POST['num_touches'] ?? 3),
        'touch_intervals'     => trim($_POST['touch_intervals'] ?? '0,3,7'),
        'custom_instructions' => trim($_POST['custom_instructions'] ?? ''),
    );
    if ($ai) {
        DB::update('ai_settings', $fields, 'id = ?', array($ai['id']));
    } else {
        DB::insert('ai_settings', $fields);
    }
    $ai  = DB::fetchOne('SELECT * FROM ai_settings LIMIT 1') ?: array();
    $msg = 'Settings saved.';
}

$currentProvider = $ai['provider'] ?? 'gemini';
$currentModel    = $ai['model']    ?? '';

include 'layout.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">&#9881; Settings</div>
    <div class="page-sub">AI provider configuration and email generation preferences</div>
  </div>
</div>

<?php if ($msg): ?>
<div style="background:rgba(0,200,100,0.1);border:1px solid var(--success);color:var(--success);padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:13px"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="POST" style="max-width:720px">
  <input type="hidden" name="action" value="save">

  <div class="card" style="margin-bottom:20px">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">AI Provider</div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:16px">
      <div class="form-group">
        <label>Active Provider</label>
        <select name="provider" id="providerSelect" onchange="updateModelOptions()">
          <option value="gemini" <?= $currentProvider === 'gemini' ? 'selected' : '' ?>>Google Gemini</option>
          <option value="claude" <?= $currentProvider === 'claude' ? 'selected' : '' ?>>Anthropic Claude</option>
          <option value="openai" <?= $currentProvider === 'openai' ? 'selected' : '' ?>>OpenAI ChatGPT</option>
        </select>
        <div style="font-size:11px;color:var(--muted);margin-top:4px">Add API keys for all providers; switch without losing them.</div>
      </div>

      <div class="form-group">
        <label>Model</label>
        <select name="model" id="modelSelect"></select>
        <div style="font-size:11px;color:var(--muted);margin-top:4px">Recommended model pre-selected. Switch to a more powerful one if needed.</div>
      </div>

      <div class="form-group">
        <label>Gemini API Key</label>
        <input type="password" name="gemini_key" value="<?= htmlspecialchars($ai['gemini_key'] ?? '') ?>" placeholder="AIza..." autocomplete="new-password">
        <div style="font-size:11px;color:var(--muted);margin-top:4px">Get key at <a href="https://aistudio.google.com/app/apikey" target="_blank" style="color:var(--accent)">aistudio.google.com</a> &mdash; free tier available</div>
      </div>
      <div class="form-group">
        <label>Claude API Key</label>
        <input type="password" name="claude_key" value="<?= htmlspecialchars($ai['claude_key'] ?? '') ?>" placeholder="sk-ant-..." autocomplete="new-password">
        <div style="font-size:11px;color:var(--muted);margin-top:4px">Get key at <a href="https://console.anthropic.com/" target="_blank" style="color:var(--accent)">console.anthropic.com</a></div>
      </div>
      <div class="form-group">
        <label>OpenAI API Key</label>
        <input type="password" name="openai_key" value="<?= htmlspecialchars($ai['openai_key'] ?? '') ?>" placeholder="sk-..." autocomplete="new-password">
        <div style="font-size:11px;color:var(--muted);margin-top:4px">Get key at <a href="https://platform.openai.com/api-keys" target="_blank" style="color:var(--accent)">platform.openai.com</a></div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:20px">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Email Generation</div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:16px">
      <div class="form-group">
        <label>Default Email Length</label>
        <select name="email_length">
          <option value="short"  <?= ($ai['email_length'] ?? 'medium') === 'short'  ? 'selected' : '' ?>>Short (3-4 sentences)</option>
          <option value="medium" <?= ($ai['email_length'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Medium (2 short paragraphs)</option>
          <option value="long"   <?= ($ai['email_length'] ?? 'medium') === 'long'   ? 'selected' : '' ?>>Long (3 paragraphs)</option>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="form-group">
          <label>Number of Touches</label>
          <input type="number" name="num_touches" value="<?= (int)($ai['num_touches'] ?? 3) ?>" min="1" max="10">
        </div>
        <div class="form-group">
          <label>Touch Intervals (days)</label>
          <input type="text" name="touch_intervals" value="<?= htmlspecialchars($ai['touch_intervals'] ?? '0,3,7') ?>" placeholder="0,3,7">
          <div style="font-size:11px;color:var(--muted);margin-top:4px">Comma-separated days after first touch</div>
        </div>
      </div>
      <div class="form-group">
        <label>Custom Instructions <span style="color:var(--muted);font-weight:400">(appended to every AI prompt)</span></label>
        <textarea name="custom_instructions" rows="4" placeholder="e.g. Always mention our ISO certification. Never use the word &apos;leverage&apos;."><?= htmlspecialchars($ai['custom_instructions'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary" style="margin-bottom:20px">Save Settings</button>
</form>

<?php
// Decode stored sequences back to newline-separated for display
function seqJsonToLines($json) {
    if (!$json) return '';
    $arr = json_decode($json, true);
    return is_array($arr) ? implode("\n", $arr) : $json;
}
?>

<form method="POST" style="max-width:720px">
  <input type="hidden" name="action" value="sequence">

  <div class="card" style="margin-bottom:20px">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Sequence Strategy</div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:16px">
      <div class="form-group">
        <label>Default Funnel Stage</label>
        <select name="default_stage">
          <option value="auto" <?= ($ai['default_stage'] ?? 'auto') === 'auto' ? 'selected' : '' ?>>Auto-detect by score</option>
          <option value="tof"  <?= ($ai['default_stage'] ?? 'auto') === 'tof'  ? 'selected' : '' ?>>TOF &ndash; Awareness</option>
          <option value="mof"  <?= ($ai['default_stage'] ?? 'auto') === 'mof'  ? 'selected' : '' ?>>MOF &ndash; Consideration</option>
          <option value="bof"  <?= ($ai['default_stage'] ?? 'auto') === 'bof'  ? 'selected' : '' ?>>BOF &ndash; Decision</option>
        </select>
        <div style="font-size:11px;color:var(--muted);margin-top:4px">When set to Auto-detect, stage is determined by the company intent score.</div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="form-group">
          <label>TOF Score Threshold (max)</label>
          <input type="number" name="tof_score_max" value="<?= (int)($ai['tof_score_max'] ?? 40) ?>" min="0" max="100">
          <div style="font-size:11px;color:var(--muted);margin-top:4px">Scores at or below this = TOF</div>
        </div>
        <div class="form-group">
          <label>BOF Score Threshold (min)</label>
          <input type="number" name="bof_score_min" value="<?= (int)($ai['bof_score_min'] ?? 71) ?>" min="0" max="100">
          <div style="font-size:11px;color:var(--muted);margin-top:4px">Scores at or above this = BOF</div>
        </div>
      </div>
      <div class="form-group">
        <label>TOF Touch Intents <span style="color:var(--muted);font-weight:400">(one per line)</span></label>
        <textarea name="tof_sequence" rows="5" placeholder="awareness&#10;value_insight&#10;lead_magnet&#10;soft_cta&#10;breakup"><?= htmlspecialchars(seqJsonToLines($ai['tof_sequence'] ?? '')) ?></textarea>
      </div>
      <div class="form-group">
        <label>MOF Touch Intents <span style="color:var(--muted);font-weight:400">(one per line)</span></label>
        <textarea name="mof_sequence" rows="5" placeholder="problem_agitate&#10;tool_offer&#10;entry_door&#10;case_study&#10;direct_ask"><?= htmlspecialchars(seqJsonToLines($ai['mof_sequence'] ?? '')) ?></textarea>
      </div>
      <div class="form-group">
        <label>BOF Touch Intents <span style="color:var(--muted);font-weight:400">(one per line)</span></label>
        <textarea name="bof_sequence" rows="5" placeholder="direct_pitch&#10;entry_door&#10;roi_case&#10;urgency&#10;breakup"><?= htmlspecialchars(seqJsonToLines($ai['bof_sequence'] ?? '')) ?></textarea>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary" style="margin-bottom:20px">Save Sequence Strategy</button>
</form>

<div class="card" style="max-width:720px;margin-bottom:20px">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Test AI Connection</div>
  <div style="padding:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
    <button type="button" onclick="testAIConnection(this)" class="btn btn-secondary">&#9654; Test Connection</button>
    <div id="testResult" style="font-size:13px"></div>
  </div>
</div>

<script>
var providerModels = {
  gemini: [
    { value: 'gemini-2.0-flash',              label: 'gemini-2.0-flash (recommended)' },
    { value: 'gemini-2.5-flash-preview-05-20', label: 'gemini-2.5-flash-preview' },
    { value: 'gemini-1.5-flash',              label: 'gemini-1.5-flash' },
    { value: 'gemini-1.5-pro',                label: 'gemini-1.5-pro' },
  ],
  claude: [
    { value: 'claude-haiku-4-5-20251001', label: 'claude-haiku-4-5 (recommended, fastest)' },
    { value: 'claude-sonnet-4-5',         label: 'claude-sonnet-4-5 (balanced)' },
    { value: 'claude-opus-4-8',           label: 'claude-opus-4-8 (most capable)' },
  ],
  openai: [
    { value: 'gpt-4o-mini',   label: 'gpt-4o-mini (recommended, fast)' },
    { value: 'gpt-4o',        label: 'gpt-4o (more capable)' },
    { value: 'gpt-4-turbo',   label: 'gpt-4-turbo' },
    { value: 'gpt-3.5-turbo', label: 'gpt-3.5-turbo (cheapest)' },
  ]
};

var savedModel = <?= json_encode($currentModel) ?>;

function updateModelOptions() {
  var provider = document.getElementById('providerSelect').value;
  var sel      = document.getElementById('modelSelect');
  var options  = providerModels[provider] || [];
  sel.innerHTML = '';
  options.forEach(function(m) {
    var opt    = document.createElement('option');
    opt.value  = m.value;
    opt.textContent = m.label;
    if (m.value === savedModel) opt.selected = true;
    sel.appendChild(opt);
  });
  // If savedModel not found for this provider, default to first (recommended)
  if (!sel.value) sel.selectedIndex = 0;
}

// Init on page load
updateModelOptions();

async function testAIConnection(btn) {
  var origText = btn.textContent;
  btn.disabled = true; btn.textContent = 'Testing...';
  var res = document.getElementById('testResult');
  res.textContent = '';
  try {
    var r = await fetch('api/test_ai.php');
    var text = await r.text();
    var d;
    try { d = JSON.parse(text); } catch(e) {
      res.style.color = 'var(--danger)';
      res.textContent = 'PHP error: ' + text.substring(0, 200);
      btn.disabled = false; btn.textContent = origText;
      return;
    }
    if (d.ok) {
      res.style.color = 'var(--success)';
      res.textContent = '✓ ' + d.provider.toUpperCase() + ': ' + d.response;
    } else {
      res.style.color = 'var(--danger)';
      res.textContent = '✗ ' + (d.error || 'Connection failed.');
    }
  } catch(e) {
    res.style.color = 'var(--danger)';
    res.textContent = '✗ Network error: ' + e.message;
  }
  btn.disabled = false; btn.textContent = origText;
}
</script>

<?php include 'layout_end.php'; ?>
