<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';
require_once __DIR__ . '/../lib/NewsFetcher.php';
require_once __DIR__ . '/../lib/TechExtractor.php';
require_once __DIR__ . '/../lib/Scorer.php';
require_once __DIR__ . '/../lib/EmailDrafter.php';

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'No company id']); exit; }

$company = DB::fetchOne('SELECT * FROM companies WHERE id = ?', [$id]);
if (!$company) { echo json_encode(['error' => 'Not found']); exit; }

$news = NewsFetcher::fetchGoogleNews($company['name'], $company['country'] ?? 'US');
$jobs = NewsFetcher::fetchIndeedJobs($company['name'], $company['country'] ?? 'US');
$allItems = array_merge($news, $jobs);

DB::query('DELETE FROM signals WHERE company_id = ?', [$id]);
DB::query('DELETE FROM company_tech WHERE company_id = ?', [$id]);

foreach ($allItems as $item) {
    DB::insert('signals', [
        'company_id'     => $id,
        'title'          => substr($item['title'], 0, 499),
        'snippet'        => substr($item['snippet'], 0, 2000),
        'url'            => substr($item['url'], 0, 999),
        'published_date' => $item['published_date'],
        'source'         => $item['source'],
    ]);
}

$techStack = [];
foreach ($jobs as $job) {
    $hits = TechExtractor::extract($job['title'] . ' ' . $job['snippet']);
    foreach ($hits as $hit) {
        DB::query(
            'INSERT INTO company_tech (company_id, tool, category, confidence, source_url, source_title)
             VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE confidence=VALUES(confidence), detected_at=NOW()',
            [$id, $hit['tool'], $hit['category'], $hit['confidence'], substr($job['url'],0,999), substr($job['title'],0,499)]
        );
        $techStack[] = $hit;
    }
}
$techStack = array_unique($techStack, SORT_REGULAR);

$scoreData = Scorer::score($allItems, $techStack, $company);

DB::query('DELETE FROM email_drafts WHERE company_id = ?', [$id]);
$email = EmailDrafter::draft($company, $scoreData, $techStack);
DB::insert('email_drafts', [
    'company_id' => $id,
    'subject'    => $email['subject'],
    'body'       => $email['body'],
    'angle'      => $email['angle'],
]);

DB::update('companies', [
    'score'       => $scoreData['score'],
    'priority'    => $scoreData['priority'],
    'signal_count'=> $scoreData['signal_count'],
    'top_signal'  => $scoreData['top_signal'],
    'signal_types'=> implode(', ', $scoreData['signal_types']),
    'tech_stack'  => json_encode(array_column($techStack, 'tool')),
    'enriched_at' => date('Y-m-d H:i:s'),
    'status'      => 'enriched',
], 'id = ?', [$id]);

echo json_encode(['ok' => true, 'score' => $scoreData['score'], 'priority' => $scoreData['priority']]);
