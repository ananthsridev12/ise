<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';

$id = (int)($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['error'=>'No id']); exit; }

$updates = [];
if (isset($_POST['status']))  $updates['status']  = $_POST['status'];
if (isset($_POST['subject'])) $updates['subject'] = $_POST['subject'];
if (isset($_POST['body']))    $updates['body']    = $_POST['body'];

if ($updates) DB::update('email_drafts', $updates, 'id = ?', [$id]);

echo json_encode(['ok' => true]);
