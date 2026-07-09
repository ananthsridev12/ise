<?php
require_once 'config.php';
require_once 'lib/DB.php';
include 'layout.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Upload Companies</div>
    <div class="page-sub">Add a single company or bulk-upload via CSV (50+ companies)</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
  <div class="card card-body">
    <div style="font-size:15px;font-weight:600;margin-bottom:16px;">📂 Bulk CSV Upload</div>
    <p style="color:var(--muted);font-size:12px;margin-bottom:16px;">CSV must have columns: <code>name, url, industry, country</code><br>Country codes: US, IN, GB, AU, DE, etc.</p>
    <a href="sample.csv" download class="btn btn-ghost btn-sm" style="margin-bottom:16px;">⬇ Download Sample CSV</a>
    <form id="csvForm">
      <div class="form-group"><label>Select CSV File</label><input type="file" id="csvFile" accept=".csv" required></div>
      <button type="submit" class="btn btn-primary" style="width:100%">Upload & Import</button>
    </form>
    <div id="csvResult" style="margin-top:12px;display:none"></div>
  </div>

  <div class="card card-body">
    <div style="font-size:15px;font-weight:600;margin-bottom:16px;">➕ Add Single Company</div>
    <form id="singleForm">
      <div class="form-group"><label>Company Name *</label><input type="text" name="name" placeholder="Acme Manufacturing" required></div>
      <div class="form-group"><label>Website URL</label><input type="url" name="url" placeholder="https://acme.com"></div>
      <div class="form-row">
        <div class="form-group"><label>Industry</label>
          <select name="industry">
            <option value="">Select...</option>
            <option>Manufacturing</option><option>Automotive</option><option>Aerospace & Defense</option>
            <option>Food & Beverage</option><option>Chemicals</option><option>Electronics</option>
            <option>Industrial Equipment</option><option>Pharmaceuticals</option><option>Other</option>
          </select>
        </div>
        <div class="form-group"><label>Country</label>
          <select name="country">
            <option value="US">United States</option><option value="IN">India</option>
            <option value="GB">United Kingdom</option><option value="AU">Australia</option>
            <option value="DE">Germany</option><option value="CA">Canada</option><option value="SG">Singapore</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%">Add Company</button>
    </form>
  </div>
</div>

<div class="card card-body" style="margin-top:24px;">
  <div style="font-size:15px;font-weight:600;margin-bottom:12px;">📋 Paste Company List</div>
  <p style="color:var(--muted);font-size:12px;margin-bottom:12px;">One company per line: <code>Company Name, URL, Industry, Country</code></p>
  <textarea id="pasteArea" rows="8" placeholder="Acme Corp, https://acme.com, Manufacturing, US&#10;Beta Industries, https://betaind.com, Automotive, IN"></textarea>
  <button onclick="importPaste()" class="btn btn-primary" style="margin-top:12px;">Import List</button>
  <div id="pasteResult" style="margin-top:12px"></div>
</div>

<script>
document.getElementById('csvForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData();
  fd.append('csv', document.getElementById('csvFile').files[0]);
  const r = await fetch('api/upload.php', {method:'POST', body: fd});
  const d = await r.json();
  const el = document.getElementById('csvResult');
  el.style.display = 'block';
  if (d.ok) { el.innerHTML = `<div style="color:var(--success)">✓ Imported ${d.imported} companies. Skipped ${d.skipped} duplicates.</div>`; toast(`Imported ${d.imported} companies`); }
  else { el.innerHTML = `<div style="color:var(--danger)">${d.error}</div>`; }
});

document.getElementById('singleForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const r = await fetch('api/upload.php', {method:'POST', body: fd});
  const d = await r.json();
  if (d.ok && d.imported > 0) { toast('Company added!'); e.target.reset(); }
  else { toast('Company already exists or error', 'error'); }
});

async function importPaste() {
  const lines = document.getElementById('pasteArea').value.trim().split('\n').filter(Boolean);
  let imported = 0, skipped = 0;
  for (const line of lines) {
    const [name, url, industry, country] = line.split(',').map(s => s.trim());
    if (!name) continue;
    const fd = new FormData();
    fd.append('name', name);
    if (url) fd.append('url', url);
    if (industry) fd.append('industry', industry);
    if (country) fd.append('country', country);
    const r = await fetch('api/upload.php', {method:'POST', body: fd});
    const d = await r.json();
    if (d.imported > 0) imported++; else skipped++;
  }
  document.getElementById('pasteResult').innerHTML = `<span style="color:var(--success)">✓ Imported ${imported} · Skipped ${skipped} duplicates</span>`;
  toast(`Imported ${imported} companies`);
}
</script>

<?php include 'layout_end.php'; ?>
