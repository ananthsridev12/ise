<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= APP_NAME ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">ISE <span>&middot;</span> SolidPro</div>
  <a href="/index.php"       class="<?= $currentPage==='index'      ?'active':'' ?>"><span class="sidebar-icon">&#9889;</span> Dashboard</a>
  <a href="/companies.php"   class="<?= $currentPage==='companies'  ?'active':'' ?>"><span class="sidebar-icon">&#127962;</span> Companies</a>
  <a href="/upload.php"      class="<?= $currentPage==='upload'     ?'active':'' ?>"><span class="sidebar-icon">&#8679;</span> Upload</a>
  <a href="/outreach.php"    class="<?= $currentPage==='outreach'   ?'active':'' ?>"><span class="sidebar-icon">&#9993;</span> Outreach</a>
  <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);padding:16px 16px 4px;margin-top:8px">Configure</div>
  <a href="/knowledge.php"   class="<?= $currentPage==='knowledge'  ?'active':'' ?>"><span class="sidebar-icon">&#128218;</span> Knowledge</a>
  <a href="/settings.php"    class="<?= $currentPage==='settings'   ?'active':'' ?>"><span class="sidebar-icon">&#9881;</span> Settings</a>
</aside>

<main class="main">
