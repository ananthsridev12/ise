<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['error'=>'POST only']);
    exit;
}

try {
    $imported = 0;
    $skipped  = 0;

    if (isset($_FILES['csv']) && $_FILES['csv']['error'] === UPLOAD_ERR_OK) {
        $handle = fopen($_FILES['csv']['tmp_name'], 'r');
        $header = fgetcsv($handle);
        $header = array_map('strtolower', array_map('trim', $header));

        $nameIdx     = array_search('name', $header);
        $urlIdx      = array_search('url', $header) !== false ? array_search('url', $header) : array_search('website', $header);
        $industryIdx = array_search('industry', $header);
        $countryIdx  = array_search('country', $header);

        while (($row = fgetcsv($handle)) !== false) {
            $name = trim(isset($row[$nameIdx]) ? $row[$nameIdx] : '');
            if (!$name) { $skipped++; continue; }

            $url      = trim(isset($row[$urlIdx])      ? $row[$urlIdx]      : '');
            $industry = trim(isset($row[$industryIdx]) ? $row[$industryIdx] : '');
            $country  = trim(isset($row[$countryIdx])  ? $row[$countryIdx]  : 'US');
            if (!$country) $country = 'US';

            $existing = DB::fetchOne('SELECT id FROM companies WHERE name = ?', [$name]);
            if ($existing) { $skipped++; continue; }

            DB::insert('companies', array('name' => $name, 'url' => $url, 'industry' => $industry, 'country' => $country));
            $imported++;
        }
        fclose($handle);
    }

    if (isset($_POST['name']) && trim($_POST['name']) !== '') {
        $name     = trim($_POST['name']);
        $url      = trim(isset($_POST['url'])      ? $_POST['url']      : '');
        $industry = trim(isset($_POST['industry']) ? $_POST['industry'] : '');
        $country  = trim(isset($_POST['country'])  ? $_POST['country']  : 'US');
        if (!$country) $country = 'US';

        $existing = DB::fetchOne('SELECT id FROM companies WHERE name = ?', [$name]);
        if (!$existing) {
            DB::insert('companies', array('name' => $name, 'url' => $url, 'industry' => $industry, 'country' => $country));
            $imported++;
        } else {
            $skipped++;
        }
    }

    ob_end_clean();
    echo json_encode(array('ok' => true, 'imported' => $imported, 'skipped' => $skipped));

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(array('error' => $e->getMessage(), 'ok' => false));
}
