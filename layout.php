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
  <div class="sidebar-logo">ISE <span>·</span> SolidPro</div>
  <a href="/index.php"       class="<?= $currentPage==='index'      ?'active':'' ?>"><span class="sidebar-icon">⚡</span> Dashboard</a>
  <a href="/companies.php"   class="<?= $currentPage==='companies'  ?'active':'' ?>"><span class="sidebar-icon">🏢</span> Companies</a>
  <a href="/upload.php"      class="<?= $currentPage==='upload'     ?'active':'' ?>"><span class="sidebar-icon">⬆</span> Upload</a>
  <a href="/outreach.php"    class="<?= $currentPage==='outreach'   ?'active':'' ?>"><span class="sidebar-icon">✉</span> Outreach</a>
</aside>

<main class="main">
