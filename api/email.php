<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';

try {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        ob_end_clean();
        echo json_encode(array('error' => 'No id', 'ok' => false));
        exit;
    }

    $updates = array();
    if (isset($_POST['status']))           $updates['status']           = $_POST['status'];
    if (isset($_POST['subject']))          $updates['subject']          = $_POST['subject'];
    if (isset($_POST['body']))             $updates['body']             = $_POST['body'];
    if (isset($_POST['next_action_date'])) $updates['next_action_date'] = $_POST['next_action_date'] ?: null;

    if ($updates) DB::update('email_drafts', $updates, 'id = ?', array($id));

    ob_end_clean();
    echo json_encode(array('ok' => true));
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(array('error' => $e->getMessage(), 'ok' => false));
}
