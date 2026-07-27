<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';
require_once __DIR__ . '/../lib/AIEmailDrafter.php';

try {
    $action = $_POST['action'] ?? '';

    // Legacy behaviour: no action = field update
    if (!$action) {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            ob_end_clean();
            echo json_encode(array('error' => 'No id', 'ok' => false));
            exit;
        }
        $updates = array();
        if (isset($_POST['status']))           $updates['status']           = $_POST['status'];
        if (isset($_POST['subject']))          $updates['subject']          = $_POST['subject'];
        if (isset($_POST['body']))             $updates['body']             = $_POST['body'];
        if (isset($_POST['next_action_date'])) $updates['next_action_date'] = $_POST['next_action_date'] ?: null;
        if ($updates) DB::update('email_drafts', $updates, 'id = ?', array($id));
        ob_end_clean();
        echo json_encode(array('ok' => true));
        exit;
    }

    Auth::requireLogin();
    $tenantId = Auth::tenantId();

    if ($action === 'refine') {
        $draftId      = (int)($_POST['draft_id'] ?? 0);
        $instructions = trim($_POST['instructions'] ?? '');
        if (!$draftId || !$instructions) {
            ob_end_clean();
            echo json_encode(array('ok' => false, 'error' => 'draft_id and instructions are required'));
            exit;
        }

        $draft = DB::fetchOne('SELECT * FROM email_drafts WHERE id = ? AND company_id IN (SELECT id FROM companies WHERE tenant_id = ?)', array($draftId, $tenantId));
        if (!$draft) {
            ob_end_clean();
            echo json_encode(array('ok' => false, 'error' => 'Draft not found'));
            exit;
        }

        $aiSettings = DB::fetchOne('SELECT * FROM ai_settings WHERE tenant_id = ? LIMIT 1', array($tenantId)) ?: DB::fetchOne('SELECT * FROM ai_settings LIMIT 1') ?: array();

        $thread = null;
        if (!empty($draft['thread_id'])) {
            try { $thread = DB::fetchOne('SELECT * FROM email_threads WHERE id = ?', array($draft['thread_id'])); } catch (Exception $e) {}
        }

        $result = AIEmailDrafter::refine($draft, $instructions, $aiSettings, $thread);

        if (!empty($result['error'])) {
            ob_end_clean();
            echo json_encode(array('ok' => false, 'error' => $result['error']));
            exit;
        }

        DB::update('email_drafts', array('subject' => $result['subject'], 'body' => $result['body']), 'id = ?', array($draftId));

        ob_end_clean();
        echo json_encode(array('ok' => true, 'subject' => $result['subject'], 'body' => $result['body']));

    } elseif ($action === 'delete') {
        $draftId = (int)($_POST['draft_id'] ?? 0);
        if (!$draftId) {
            ob_end_clean();
            echo json_encode(array('ok' => false, 'error' => 'draft_id is required'));
            exit;
        }

        $draft = DB::fetchOne('SELECT * FROM email_drafts WHERE id = ? AND company_id IN (SELECT id FROM companies WHERE tenant_id = ?)', array($draftId, $tenantId));
        if (!$draft) {
            ob_end_clean();
            echo json_encode(array('ok' => false, 'error' => 'Draft not found'));
            exit;
        }

        DB::query('DELETE FROM email_drafts WHERE id = ?', array($draftId));

        if (!empty($draft['thread_id'])) {
            // Only delete thread if no other drafts reference it
            $remaining = DB::fetchOne('SELECT COUNT(*) as c FROM email_drafts WHERE thread_id = ?', array($draft['thread_id']));
            if (!$remaining || $remaining['c'] == 0) {
                try { DB::query('DELETE FROM email_threads WHERE id = ?', array($draft['thread_id'])); } catch (Exception $e) {}
            }
        }

        ob_end_clean();
        echo json_encode(array('ok' => true));

    } else {
        // action present but unrecognised — treat as legacy field update
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            ob_end_clean();
            echo json_encode(array('error' => 'No id', 'ok' => false));
            exit;
        }
        $updates = array();
        if (isset($_POST['status']))           $updates['status']           = $_POST['status'];
        if (isset($_POST['subject']))          $updates['subject']          = $_POST['subject'];
        if (isset($_POST['body']))             $updates['body']             = $_POST['body'];
        if (isset($_POST['next_action_date'])) $updates['next_action_date'] = $_POST['next_action_date'] ?: null;
        if ($updates) DB::update('email_drafts', $updates, 'id = ?', array($id));
        ob_end_clean();
        echo json_encode(array('ok' => true));
    }

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(array('error' => $e->getMessage(), 'ok' => false));
}
