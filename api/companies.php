<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

try {
    if ($action === 'list') {
        $priority = $_GET['priority'] ?? '';
        $status   = $_GET['status'] ?? '';
        $search   = $_GET['q'] ?? '';
        $where = ['1=1'];
        $params = [];
        if ($priority) { $where[] = 'priority = ?'; $params[] = $priority; }
        if ($status)   { $where[] = 'status = ?';   $params[] = $status; }
        if ($search)   { $where[] = 'name LIKE ?';  $params[] = "%$search%"; }
        $sql = 'SELECT * FROM companies WHERE ' . implode(' AND ', $where) . ' ORDER BY score DESC, created_at DESC';
        ob_end_clean();
        echo json_encode(DB::fetchAll($sql, $params));

    } elseif ($action === 'detail' && $id) {
        $company = DB::fetchOne('SELECT * FROM companies WHERE id = ?', [$id]);
        $signals = DB::fetchAll('SELECT * FROM signals WHERE company_id = ? ORDER BY created_at DESC', [$id]);
        $tech    = DB::fetchAll('SELECT * FROM company_tech WHERE company_id = ?', [$id]);
        $email   = DB::fetchOne('SELECT * FROM email_drafts WHERE company_id = ? ORDER BY id DESC', [$id]);
        ob_end_clean();
        echo json_encode(compact('company','signals','tech','email'));

    } elseif ($action === 'delete' && $id) {
        DB::query('DELETE FROM signals WHERE company_id = ?', [$id]);
        DB::query('DELETE FROM company_tech WHERE company_id = ?', [$id]);
        DB::query('DELETE FROM email_drafts WHERE company_id = ?', [$id]);
        DB::query('DELETE FROM companies WHERE id = ?', [$id]);
        ob_end_clean();
        echo json_encode(['ok' => true]);

    } elseif ($action === 'update_status' && $id) {
        $status = $_POST['status'] ?? '';
        DB::update('companies', ['status' => $status], 'id = ?', [$id]);
        ob_end_clean();
        echo json_encode(['ok' => true]);

    } else {
        ob_end_clean();
        echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['error' => $e->getMessage(), 'ok' => false]);
}
