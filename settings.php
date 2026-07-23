<?php
require_once 'config.php';
require_once 'lib/DB.php';

$msg = '';
$ai  = DB::fetchOne('SELECT * FROM ai_settings LIMIT 1') ?: array();

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
        <select name="provider">
          <option value="gemini"  <?= ($ai['provider'] ?? 'gemini') === 'gemini'  ? 'selected' : '' ?>>Google Gemini</option>
          <option value="claude"  <?= ($ai['provider'] ?? '') === 'claude'  ? 'selected' : '' ?>>Anthropic Claude</option>
          <option value="openai"  <?= ($ai['provider'] ?? '') === 'openai'  ? 'selected' : '' ?>>OpenAI ChatGPT</option>
        </select>
        <div style="font-size:11px;color:var(--muted);margin-top:4px">Add API keys for all providers; switch without losing them.</div>
      </div>
      <div class="form-group">
        <label>Gemini API Key</label>
        <input type="password" name="gemini_key" value="<?= htmlspecialchars($ai['gemini_key'] ?? '') ?>" placeholder="AIza..." autocomplete="new-password">
      </div>
      <div class="form-group">
        <label>Claude API Key</label>
        <input type="password" name="claude_key" value="<?= htmlspecialchars($ai['claude_key'] ?? '') ?>" placeholder="sk-ant-..." autocomplete="new-password">
      </div>
      <div class="form-group">
        <label>OpenAI API Key</label>
        <input type="password" name="openai_key" value="<?= htmlspecialchars($ai['openai_key'] ?? '') ?>" placeholder="sk-..." autocomplete="new-password">
      </div>
      <div class="form-group">
        <label>Model override <span style="color:var(--muted);font-weight:400">(leave blank for default)</span></label>
        <input type="text" name="model" value="<?= htmlspecialchars($ai['model'] ?? '') ?>" placeholder="e.g. gemini-1.5-pro, gpt-4o, claude-opus-4-8">
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
        <textarea name="custom_instructions" rows="4" placeholder="e.g. Always mention our ISO certification. Never use the word 'leverage'."><?= htmlspecialchars($ai['custom_instructions'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Save Settings</button>
</form>

<?php include 'layout_end.php'; ?>
