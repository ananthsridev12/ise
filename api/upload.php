<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST only']); exit; }

$imported = 0;
$skipped  = 0;

if (isset($_FILES['csv']) && $_FILES['csv']['error'] === UPLOAD_ERR_OK) {
    $handle = fopen($_FILES['csv']['tmp_name'], 'r');
    $header = fgetcsv($handle);
    $header = array_map('strtolower', array_map('trim', $header));
    $nameIdx    = array_search('name', $header);
    $urlIdx     = array_search('url', $header) ?: array_search('website', $header);
    $industryIdx= array_search('industry', $header);
    $countryIdx = array_search('country', $header);
    while (($row = fgetcsv($handle)) !== false) {
        $name = trim($row[$nameIdx] ?? '');
        if (!$name) { $skipped++; continue; }
        $url      = trim($row[$urlIdx] ?? '');
        $industry = trim($row[$industryIdx] ?? '');
        $country  = trim($row[$countryIdx] ?? 'US');
        $existing = DB::fetchOne('SELECT id FROM companies WHERE name = ?', [$name]);
        if ($existing) { $skipped++; continue; }
        DB::insert('companies', compact('name','url','industry','country'));
        $imported++;
    }
    fclose($handle);
}

if (isset($_POST['name'])) {
    $name     = trim($_POST['name']);
    $url      = trim($_POST['url'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $country  = trim($_POST['country'] ?? 'US');
    $existing = DB::fetchOne('SELECT id FROM companies WHERE name = ?', [$name]);
    if (!$existing) {
        DB::insert('companies', compact('name','url','industry','country'));
        $imported++;
    } else {
        $skipped++;
    }
}

echo json_encode(['ok' => true, 'imported' => $imported, 'skipped' => $skipped]);
