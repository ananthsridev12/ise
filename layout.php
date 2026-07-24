<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
Auth::requireLogin();
$_authUser = Auth::user();
$_isAdmin  = Auth::isAdmin();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= APP_NAME ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<button class="hamburger" onclick="toggleSidebar()" aria-label="Menu">&#9776;</button>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar">
  <div class="sidebar-logo">ISE <span>&middot;</span> <?= htmlspecialchars($_authUser['tenant_name'] ?? 'ISE') ?></div>
  <a href="/index.php"       class="<?= $currentPage==='index'      ?'active':'' ?>" onclick="closeSidebar()"><span class="sidebar-icon">&#9889;</span> Dashboard</a>
  <a href="/companies.php"   class="<?= $currentPage==='companies'  ?'active':'' ?>" onclick="closeSidebar()"><span class="sidebar-icon">&#127962;</span> Companies</a>
  <a href="/upload.php"      class="<?= $currentPage==='upload'     ?'active':'' ?>" onclick="closeSidebar()"><span class="sidebar-icon">&#8679;</span> Upload</a>
  <a href="/outreach.php"    class="<?= $currentPage==='outreach'   ?'active':'' ?>" onclick="closeSidebar()"><span class="sidebar-icon">&#9993;</span> Outreach</a>
  <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);padding:16px 16px 4px;margin-top:8px">Configure</div>
  <a href="/knowledge.php"   class="<?= $currentPage==='knowledge'  ?'active':'' ?>" onclick="closeSidebar()"><span class="sidebar-icon">&#128218;</span> Knowledge</a>
<?php if ($_isAdmin): ?>
  <a href="/settings.php"    class="<?= $currentPage==='settings'   ?'active':'' ?>" onclick="closeSidebar()"><span class="sidebar-icon">&#9881;</span> Settings</a>
  <a href="/admin/users.php" class="<?= $currentPage==='users'      ?'active':'' ?>" onclick="closeSidebar()"><span class="sidebar-icon">&#128101;</span> Users</a>
<?php endif; ?>
  <div class="sidebar-user">
    <div class="sidebar-user-name"><?= htmlspecialchars($_authUser['display_name'] ?: $_authUser['email']) ?></div>
    <div class="sidebar-user-meta">
      <span class="role-badge role-<?= $_authUser['role'] ?>"><?= $_authUser['role'] ?></span>
    </div>
    <a href="/logout.php" class="sidebar-logout">Sign out</a>
  </div>
</aside>

<main class="main">
<script>
function toggleSidebar() { document.body.classList.toggle('sidebar-open'); }
function closeSidebar()  { document.body.classList.remove('sidebar-open'); }
</script>
