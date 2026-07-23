<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';
require_once __DIR__ . '/../lib/NewsFetcher.php';
require_once __DIR__ . '/../lib/TechExtractor.php';
require_once __DIR__ . '/../lib/Scorer.php';
require_once __DIR__ . '/../lib/EmailDrafter.php';
require_once __DIR__ . '/../lib/KBMatcher.php';
require_once __DIR__ . '/../lib/AIEmailDrafter.php';

try {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if (!$id) { echo json_encode(array('error' => 'No company id')); exit; }

    $company = DB::fetchOne('SELECT * FROM companies WHERE id = ?', array($id));
    if (!$company) { echo json_encode(array('error' => 'Not found')); exit; }

    $country = $company['country'] ?? 'US';
    $news = NewsFetcher::fetchGoogleNews($company['name'], $country);
    $jobs = NewsFetcher::fetchAdzunaJobs($company['name'], $country);
    $allItems = array_merge($news, $jobs);

    DB::query('DELETE FROM signals WHERE company_id = ?', array($id));
    DB::query('DELETE FROM company_tech WHERE company_id = ?', array($id));

    foreach ($allItems as $item) {
        DB::insert('signals', array(
            'company_id'     => $id,
            'title'          => substr($item['title'], 0, 499),
            'snippet'        => substr($item['snippet'], 0, 2000),
            'url'            => substr($item['url'], 0, 999),
            'published_date' => $item['published_date'],
            'source'         => $item['source'],
        ));
    }

    $techStack = array();
    foreach ($jobs as $job) {
        $text = $job['title'] . ' ' . $job['snippet'];
        $hits = TechExtractor::extract($text);
        foreach ($hits as $hit) {
            DB::query(
                'INSERT INTO company_tech (company_id, tool, category, confidence, source_url, source_title)
                 VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE confidence=VALUES(confidence), detected_at=NOW()',
                array($id, $hit['tool'], $hit['category'], $hit['confidence'], substr($job['url'],0,999), substr($job['title'],0,499))
            );
            $techStack[] = $hit;
        }
    }
    $techStack = array_unique($techStack, SORT_REGULAR);

    $scoreData = Scorer::score($allItems, $techStack, $company);

    DB::update('companies', array(
        'score'        => $scoreData['score'],
        'priority'     => $scoreData['priority'],
        'signal_count' => $scoreData['signal_count'],
        'top_signal'   => $scoreData['top_signal'],
        'signal_types' => implode(', ', $scoreData['signal_types']),
        'tech_stack'   => json_encode(array_column($techStack, 'tool')),
        'enriched_at'  => date('Y-m-d H:i:s'),
        'status'       => 'enriched',
    ), 'id = ?', array($id));

    // Match service from KB
    $service    = KBMatcher::matchService($scoreData['signal_types'], $techStack, $company['industry']);
    $aiSettings = DB::fetchOne('SELECT * FROM ai_settings LIMIT 1') ?: array();
    $tone       = DB::fetchOne('SELECT * FROM kb_tone LIMIT 1');
    $sender     = DB::fetchOne('SELECT * FROM kb_senders WHERE is_default=1 LIMIT 1');
    if (!$sender) $sender = DB::fetchOne('SELECT * FROM kb_senders ORDER BY id LIMIT 1');

    $hasAiKey = (!empty($aiSettings['gemini_key']) && $aiSettings['provider'] === 'gemini')
             || (!empty($aiSettings['claude_key'])  && $aiSettings['provider'] === 'claude')
             || (!empty($aiSettings['openai_key'])  && $aiSettings['provider'] === 'openai');

    DB::query('DELETE FROM email_drafts WHERE company_id = ?', array($id));

    if ($hasAiKey) {
        $email = AIEmailDrafter::draft($company, $scoreData, $techStack, $service, $sender, $tone, $aiSettings, 1, '');
        DB::insert('email_drafts', array(
            'company_id'         => $id,
            'subject'            => $email['subject'],
            'body'               => $email['body'],
            'angle'              => $email['angle'],
            'touch_number'       => 1,
            'ai_provider'        => $email['provider'],
            'matched_service_id' => $service ? $service['id'] : null,
            'prompt_context'     => $email['prompt'],
        ));
    } else {
        $email = EmailDrafter::draft($company, $scoreData, $techStack);
        DB::insert('email_drafts', array(
            'company_id'  => $id,
            'subject'     => $email['subject'],
            'body'        => $email['body'],
            'angle'       => $email['angle'],
            'touch_number' => 1,
        ));
    }

    $adzunaActive = (ADZUNA_APP_ID && ADZUNA_APP_KEY);
    echo json_encode(array(
        'ok'              => true,
        'score'           => $scoreData['score'],
        'priority'        => $scoreData['priority'],
        'news_count'      => count($news),
        'jobs_count'      => count($jobs),
        'tech_found'      => count($techStack),
        'adzuna_active'   => $adzunaActive,
        'ai_used'         => $hasAiKey,
        'matched_service' => $service ? $service['name'] : null,
    ));

} catch (Exception $e) {
    echo json_encode(array('error' => $e->getMessage(), 'ok' => false));
}
