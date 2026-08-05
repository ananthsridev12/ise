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
    $senderId    = (int)($_POST['sender_id'] ?? $_GET['sender_id'] ?? 0);
    $options     = [
        'funnel_stage' => trim($_POST['funnel_stage'] ?? $_GET['funnel_stage'] ?? ''),
        'mode'         => trim($_POST['mode'] ?? $_GET['mode'] ?? 'auto'),
        'persona_id'   => (int)($_POST['persona_id'] ?? $_GET['persona_id'] ?? 0),
        'signal_ids'   => trim($_POST['signal_ids'] ?? $_GET['signal_ids'] ?? ''),
    ];

    if (!$companyId) {
        ob_end_clean();
        echo json_encode(array('error' => 'No company_id', 'ok' => false));
        exit;
    }

    $result = EmailGenerator::generate($companyId, $touchNumber, $senderId, $options);
    ob_end_clean();
    echo json_encode($result);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(array('error' => $e->getMessage(), 'ok' => false));
}
