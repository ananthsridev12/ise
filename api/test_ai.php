<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';
require_once __DIR__ . '/../lib/AIEmailDrafter.php';

try {
    $aiSettings = DB::fetchOne('SELECT * FROM ai_settings LIMIT 1');
    if (!$aiSettings) {
        echo json_encode(array('ok'=>false,'error'=>'No AI settings saved yet. Save your settings first.'));
        exit;
    }
    $result = AIEmailDrafter::testConnection($aiSettings);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(array('ok'=>false,'error'=>$e->getMessage()));
}
