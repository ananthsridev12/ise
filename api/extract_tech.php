<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';
require_once __DIR__ . '/../lib/TechExtractor.php';

$companyId = (int)($_POST['company_id'] ?? 0);
$text      = trim($_POST['text'] ?? '');
$sourceTitle = trim($_POST['source_title'] ?? 'Manual paste');
$sourceUrl   = trim($_POST['source_url'] ?? '');

if (!$companyId || !$text) {
    echo json_encode(['error' => 'Missing company_id or text']);
    exit;
}

$hits = TechExtractor::extract($text);

if (!$hits) {
    echo json_encode(['ok' => true, 'found' => [], 'message' => 'No known tools detected in this text']);
    exit;
}

$saved = [];
foreach ($hits as $hit) {
    DB::query(
        'INSERT INTO company_tech (company_id, tool, category, confidence, source_url, source_title)
         VALUES (?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE confidence=VALUES(confidence), source_title=VALUES(source_title), detected_at=NOW()',
        [$companyId, $hit['tool'], $hit['category'], $hit['confidence'],
         substr($sourceUrl, 0, 999), substr($sourceTitle, 0, 499)]
    );
    $saved[] = $hit['tool'];
}

// Update tech_stack summary on company
$allTech = DB::fetchAll('SELECT tool FROM company_tech WHERE company_id = ?', [$companyId]);
$toolNames = array_column($allTech, 'tool');
DB::update('companies', ['tech_stack' => json_encode($toolNames)], 'id = ?', [$companyId]);

echo json_encode(['ok' => true, 'found' => $saved]);
