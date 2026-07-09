<?php
require_once 'config.php';
require_once 'lib/DB.php';
include 'layout.php';

$total    = DB::fetchOne('SELECT COUNT(*) c FROM companies')['c'] ?? 0;
$enriched = DB::fetchOne("SELECT COUNT(*) c FROM companies WHERE status='enriched'")['c'] ?? 0;
$high     = DB::fetchOne("SELECT COUNT(*) c FROM companies WHERE priority='High'")['c'] ?? 0;
$medium   = DB::fetchOne("SELECT COUNT(*) c FROM companies WHERE priority='Medium'")['c'] ?? 0;
$pending  = DB::fetchOne("SELECT COUNT(*) c FROM companies WHERE status='pending'")['c'] ?? 0;
$outreach = DB::fetchOne("SELECT COUNT(*) c FROM email_drafts")['c'] ?? 0;
$topCos   = DB::fetchAll('SELECT name, score, priority, top_signal FROM companies WHERE status="enriched" ORDER BY score DESC LIMIT 5');
?>

<div class="page-header">
  <div>
    <div class="page-title">Dashboard</div>
    <div class="page-sub">ISE — Intent Signal Engine for SolidPro</div>
  </div>
  <a href="upload.php" class="btn btn-primary">+ Add Companies</a>
</div>

<div class="metric-grid">
  <div class="metric-card"><div class="metric-label">Total Companies</div><div class="metric-value"><?= $total ?></div></div>
  <div class="metric-card"><div class="metric-label">Enriched</div><div class="metric-value"><?= $enriched ?></div></div>
  <div class="metric-card"><div class="metric-label">High Intent</div><div class="metric-value high"><?= $high ?></div></div>
  <div class="metric-card"><div class="metric-label">Medium Intent</div><div class="metric-value medium"><?= $medium ?></div></div>
  <div class="metric-card"><div class="metric-label">Pending Enrichment</div><div class="metric-value"><?= $pending ?></div></div>
  <div class="metric-card"><div class="metric-label">Email Drafts</div><div class="metric-value"><?= $outreach ?></div></div>
</div>

<?php if ($total === 0): ?>
<div class="card card-body" style="text-align:center;padding:60px 20px;">
  <div style="font-size:40px;margin-bottom:16px;">🏢</div>
  <div style="font-size:16px;font-weight:600;margin-bottom:8px;">No companies yet</div>
  <div style="color:var(--muted);margin-bottom:20px;">Upload a CSV with company names, URLs, industry and country to get started.</div>
  <a href="upload.php" class="btn btn-primary">Upload Companies</a>
</div>
<?php else: ?>
<div class="card" style="margin-bottom:20px;">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600;">Top Companies by Intent Score</div>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Company</th><th>Score</th><th>Priority</th><th>Top Signal</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($topCos as $c): ?>
    <tr>
      <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
      <td><div class="score-bar"><div class="score-track"><div class="score-fill <?= strtolower($c['priority']) ?>" style="width:<?= $c['score'] ?>%"></div></div><span><?= $c['score'] ?></span></div></td>
      <td><span class="badge badge-<?= strtolower($c['priority']) ?>"><?= $c['priority'] ?></span></td>
      <td style="color:var(--muted)"><?= htmlspecialchars($c['top_signal'] ?? '—') ?></td>
      <td><a href="companies.php" class="btn btn-ghost btn-sm">View →</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php if ($pending > 0): ?>
<div class="card card-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px;">
  <div><strong><?= $pending ?> companies</strong> are waiting to be enriched.<span style="color:var(--muted)"> Run enrichment to fetch signals, detect tech stack, and score them.</span></div>
  <a href="companies.php?status=pending" class="btn btn-primary">Enrich Now →</a>
</div>
<?php endif; ?>
<?php endif; ?>

<?php include 'layout_end.php'; ?>
