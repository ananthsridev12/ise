<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/KBMatcher.php';
require_once __DIR__ . '/../lib/AIEmailDrafter.php';
require_once __DIR__ . '/../lib/SequencePlanner.php';

header('Content-Type: application/json');
Auth::requireLogin();

$companyId   = (int)($_GET['company_id'] ?? 0);
$touchNumber = (int)($_GET['touch_number'] ?? 1);
$senderId    = (int)($_GET['sender_id'] ?? 0);
$funnelStage = trim($_GET['funnel_stage'] ?? '');
$mode        = trim($_GET['mode'] ?? 'auto');

if (!$companyId) { echo json_encode(['ok'=>false,'error'=>'company_id required']); exit; }

$company = DB::fetchOne('SELECT * FROM companies WHERE id = ?', [$companyId]);
if (!$company) { echo json_encode(['ok'=>false,'error'=>'Company not found']); exit; }

$tenantId   = (int)($company['tenant_id'] ?? 0);
$aiSettings = DB::fetchOne('SELECT * FROM ai_settings LIMIT 1') ?: [];
$tone       = DB::fetchOne('SELECT * FROM kb_tone LIMIT 1');
$sender     = null;
if ($senderId) $sender = DB::fetchOne('SELECT * FROM kb_senders WHERE id = ?', [$senderId]);
if (!$sender)  $sender = DB::fetchOne('SELECT * FROM kb_senders WHERE is_default = 1 LIMIT 1');
if (!$sender)  $sender = DB::fetchOne('SELECT * FROM kb_senders ORDER BY id LIMIT 1');

$techStack   = DB::fetchAll('SELECT * FROM company_tech WHERE company_id = ? ORDER BY confidence DESC', [$companyId]) ?: [];
$signalTypes = $company['signal_types'] ? array_values(array_filter(array_map('trim', explode(',', $company['signal_types'])))) : [];

$scoreData = [
    'score'        => $company['score'] ?? 0,
    'priority'     => $company['priority'] ?? 'Low',
    'signal_types' => $signalTypes,
    'top_signal'   => $company['top_signal'] ?? '',
    'signal_count' => $company['signal_count'] ?? 0,
];

$service = KBMatcher::matchService($signalTypes, $techStack, $company['industry'], $tenantId);
$persona = null;
if ($service) {
    $persona = DB::fetchOne('SELECT * FROM kb_personas WHERE service_id = ? ORDER BY FIELD(decision_role,"Economic Buyer","Champion","Technical Buyer","End User","Influencer","Blocker") LIMIT 1', [$service['id']]);
}

$assets = $service ? KBMatcher::matchAssets((int)$service['id'], $tenantId, $touchNumber) : [];

$thread = null;
try { $thread = DB::fetchOne('SELECT * FROM email_threads WHERE company_id = ? ORDER BY id DESC LIMIT 1', [$companyId]); } catch (Exception $e) {}

$priorSubject = '';
if ($touchNumber > 1) {
    $prior = DB::fetchOne('SELECT subject FROM email_drafts WHERE company_id = ? AND touch_number = ? ORDER BY id DESC LIMIT 1', [$companyId, $touchNumber - 1]);
    $priorSubject = $prior ? $prior['subject'] : '';
}

// Build prompts WITHOUT calling AI
$systemPrompt = AIEmailDrafter::buildSystemPromptPublic($company, $scoreData, $techStack, $service, $sender, $tone, $aiSettings, $touchNumber, $priorSubject, $persona, $thread, $assets);
$userMessage  = AIEmailDrafter::buildUserMessagePublic($company, $scoreData, $techStack, $service, $sender, $tone, $aiSettings, $touchNumber, $priorSubject, $persona, $thread, $assets);

function countTokens(string $text): int { return (int)ceil(strlen($text) / 4); }

$sysTokens  = countTokens($systemPrompt);
$userTokens = countTokens($userMessage);
$totalIn    = $sysTokens + $userTokens;
$estOut     = 400;

// Cost estimates per 1M tokens (input/output)
$costs = [
    'gemini'       => ['in' => 0.00,  'out' => 0.00,  'label' => 'Gemini 1.5 Flash (free tier)'],
    'claude-haiku' => ['in' => 0.25,  'out' => 1.25,  'label' => 'Claude Haiku'],
    'claude-sonnet'=> ['in' => 3.00,  'out' => 15.00, 'label' => 'Claude Sonnet'],
    'openai-mini'  => ['in' => 0.15,  'out' => 0.60,  'label' => 'GPT-4o mini'],
    'openai-gpt4o' => ['in' => 2.50,  'out' => 10.00, 'label' => 'GPT-4o'],
];

$costEstimates = [];
foreach ($costs as $key => $c) {
    $costEstimates[$key] = [
        'label'    => $c['label'],
        'cost_usd' => round(($totalIn * $c['in'] + $estOut * $c['out']) / 1000000, 6),
    ];
}

echo json_encode([
    'ok'              => true,
    'system_prompt'   => $systemPrompt,
    'user_message'    => $userMessage,
    'sys_chars'       => strlen($systemPrompt),
    'sys_words'       => str_word_count($systemPrompt),
    'sys_tokens'      => $sysTokens,
    'user_chars'      => strlen($userMessage),
    'user_words'      => str_word_count($userMessage),
    'user_tokens'     => $userTokens,
    'total_in_tokens' => $totalIn,
    'est_out_tokens'  => $estOut,
    'cost_estimates'  => $costEstimates,
    'matched_service' => $service ? $service['name'] : null,
]);
