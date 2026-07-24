<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';
require_once __DIR__ . '/../lib/KBMatcher.php';
require_once __DIR__ . '/../lib/AIEmailDrafter.php';
require_once __DIR__ . '/../lib/EmailGenerator.php';

try {
    $companyId   = (int)($_POST['company_id'] ?? $_GET['company_id'] ?? 0);
    $touchNumber = (int)($_POST['touch_number'] ?? $_GET['touch_number'] ?? 1);
    $senderId    = (int)($_POST['sender_id'] ?? 0);

    if (!$companyId) {
        ob_end_clean();
        echo json_encode(array('error' => 'No company_id', 'ok' => false));
        exit;
    }

    $result = EmailGenerator::generate($companyId, $touchNumber, $senderId);
    ob_end_clean();
    echo json_encode($result);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(array('error' => $e->getMessage(), 'ok' => false));
}
