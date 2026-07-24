<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';
require_once __DIR__ . '/../lib/NewsFetcher.php';
require_once __DIR__ . '/../lib/TechExtractor.php';
require_once __DIR__ . '/../lib/Scorer.php';

try {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if (!$id) { echo json_encode(array('error' => 'No company id')); exit; }

    $company = DB::fetchOne('SELECT * FROM companies WHERE id = ?', array($id));
    if (!$company) { echo json_encode(array('error' => 'Not found')); exit; }

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

    echo json_encode(array(
        'ok'         => true,
        'score'      => $scoreData['score'],
        'priority'   => $scoreData['priority'],
        'news_count' => count($news),
        'jobs_count' => count($jobs),
        'tech_found' => count($techStack),
    ));

} catch (Exception $e) {
    echo json_encode(array('error' => $e->getMessage(), 'ok' => false));
}
