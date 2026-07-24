<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';
require_once __DIR__ . '/../lib/KBMatcher.php';
require_once __DIR__ . '/../lib/AIEmailDrafter.php';

try {
    $companyId   = (int)($_POST['company_id'] ?? $_GET['company_id'] ?? 0);
    $touchNumber = (int)($_POST['touch_number'] ?? 1);
    $senderId    = (int)($_POST['sender_id'] ?? 0);

    if (!$companyId) { echo json_encode(array('error' => 'No company_id')); exit; }

    $company = DB::fetchOne('SELECT * FROM companies WHERE id = ?', array($companyId));
    if (!$company) { echo json_encode(array('error' => 'Company not found')); exit; }

    $aiSettings = DB::fetchOne('SELECT * FROM ai_settings LIMIT 1') ?: array();
    $tone       = DB::fetchOne('SELECT * FROM kb_tone LIMIT 1');

    $sender = null;
    if ($senderId) $sender = DB::fetchOne('SELECT * FROM kb_senders WHERE id = ?', array($senderId));
    if (!$sender)  $sender = DB::fetchOne('SELECT * FROM kb_senders WHERE is_default = 1 LIMIT 1');
    if (!$sender)  $sender = DB::fetchOne('SELECT * FROM kb_senders ORDER BY id LIMIT 1');

    $techStack = DB::fetchAll('SELECT * FROM company_tech WHERE company_id = ? ORDER BY confidence DESC', array($companyId)) ?: array();

    $signalTypes = array();
    if ($company['signal_types']) {
        $signalTypes = array_filter(array_map('trim', explode(',', $company['signal_types'])));
    }

    $scoreData = array(
        'score'        => $company['score'] ?? 0,
        'priority'     => $company['priority'] ?? 'Low',
        'signal_types' => $signalTypes,
        'top_signal'   => $company['top_signal'] ?? '',
        'signal_count' => $company['signal_count'] ?? 0,
    );

    $service = KBMatcher::matchService($signalTypes, $techStack, $company['industry']);

    $persona = null;
    if ($service) {
        $persona = DB::fetchOne(
            'SELECT * FROM kb_personas WHERE service_id = ? ORDER BY FIELD(decision_role,"Economic Buyer","Champion","Technical Buyer","End User","Influencer","Blocker") LIMIT 1',
            array($service['id'])
        );
        if (!$persona && !empty($service['vertical_id'])) {
            $persona = DB::fetchOne(
                'SELECT * FROM kb_personas WHERE vertical_id = ? ORDER BY FIELD(decision_role,"Economic Buyer","Champion","Technical Buyer","End User","Influencer","Blocker") LIMIT 1',
                array($service['vertical_id'])
            );
        }
    }

    $priorSubject = '';
    if ($touchNumber > 1) {
        $prior = DB::fetchOne(
            'SELECT subject FROM email_drafts WHERE company_id = ? AND touch_number = ? ORDER BY id DESC LIMIT 1',
            array($companyId, $touchNumber - 1)
        );
        $priorSubject = $prior ? $prior['subject'] : '';
    }

    $email = AIEmailDrafter::draft($company, $scoreData, $techStack, $service, $sender, $tone, $aiSettings, $touchNumber, $priorSubject, $persona);

    DB::insert('email_drafts', array(
        'company_id'         => $companyId,
        'subject'            => $email['subject'],
        'body'               => $email['body'],
        'angle'              => $email['angle'],
        'touch_number'       => $touchNumber,
        'ai_provider'        => $email['provider'],
        'matched_service_id' => $service ? $service['id'] : null,
        'prompt_context'     => $email['prompt'],
    ));

    echo json_encode(array(
        'ok'              => true,
        'subject'         => $email['subject'],
        'body'            => $email['body'],
        'provider'        => $email['provider'],
        'matched_service' => $service ? $service['name'] : null,
        'persona'         => $persona ? $persona['name'] : null,
        'touch_number'    => $touchNumber,
    ));

} catch (Exception $e) {
    echo json_encode(array('error' => $e->getMessage(), 'ok' => false));
}
