<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';
require_once __DIR__ . '/../lib/NewsFetcher.php';
require_once __DIR__ . '/../lib/TechExtractor.php';
require_once __DIR__ . '/../lib/Scorer.php';

try {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if (!$id) {
        ob_end_clean();
        echo json_encode(array('error' => 'No company id', 'ok' => false));
        exit;
    }

    $company = DB::fetchOne('SELECT * FROM companies WHERE id = ?', array($id));
    if (!$company) {
        ob_end_clean();
        echo json_encode(array('error' => 'Not found', 'ok' => false));
        exit;
    }

    $country  = $company['country'] ?? 'US';
    $news     = NewsFetcher::fetchGoogleNews($company['name'], $country);
    $jobs     = NewsFetcher::fetchAdzunaJobs($company['name'], $country);
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

    // Auto-generate touch 1 email if AI is configured and this company has none yet
    $emailGenerated = false;
    try {
        require_once __DIR__ . '/../lib/KBMatcher.php';
        require_once __DIR__ . '/../lib/AIEmailDrafter.php';
        require_once __DIR__ . '/../lib/EmailGenerator.php';
        $aiCfg      = DB::fetchOne('SELECT * FROM ai_settings LIMIT 1') ?: array();
        $provKey    = ($aiCfg['provider'] ?? '') . '_key';
        $hasKey     = !empty($aiCfg[$provKey]);
        $hasEmail   = (bool)DB::fetchOne('SELECT id FROM email_drafts WHERE company_id = ? AND touch_number = 1', array($id));
        if ($hasKey && !$hasEmail) {
            $genResult      = EmailGenerator::generate($id, 1);
            $emailGenerated = !empty($genResult['ok']);
        }
    } catch (Exception $ignored) {
        // Auto-generate is best-effort — enrich response is still returned
    }

    ob_end_clean();
    echo json_encode(array(
        'ok'              => true,
        'score'           => $scoreData['score'],
        'priority'        => $scoreData['priority'],
        'news_count'      => count($news),
        'jobs_count'      => count($jobs),
        'tech_found'      => count($techStack),
        'email_generated' => $emailGenerated,
    ));

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(array('error' => $e->getMessage(), 'ok' => false));
}
