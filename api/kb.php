<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';

try {
    $action = $_POST['action'] ?? '';

    switch ($action) {

        case 'save_company':
            $fields = array(
                'name'                  => trim($_POST['name'] ?? ''),
                'tagline'               => trim($_POST['tagline'] ?? ''),
                'website'               => trim($_POST['website'] ?? ''),
                'founded_year'          => trim($_POST['founded_year'] ?? ''),
                'size'                  => trim($_POST['size'] ?? ''),
                'hq'                    => trim($_POST['hq'] ?? ''),
                'mission'               => trim($_POST['mission'] ?? ''),
                'vision'                => trim($_POST['vision'] ?? ''),
                'story'                 => trim($_POST['story'] ?? ''),
                'credibility_statement' => trim($_POST['credibility_statement'] ?? ''),
                'notable_clients'       => trim($_POST['notable_clients'] ?? ''),
                'awards'                => trim($_POST['awards'] ?? ''),
            );
            $existing = DB::fetchOne('SELECT id FROM kb_company LIMIT 1');
            if ($existing) {
                DB::update('kb_company', $fields, 'id = ?', array($existing['id']));
            } else {
                DB::insert('kb_company', $fields);
            }
            echo json_encode(array('ok' => true, 'message' => 'Company info saved.'));
            break;

        case 'save_vertical':
            $id = (int)($_POST['id'] ?? 0);
            $fields = array(
                'name'            => trim($_POST['name'] ?? ''),
                'focus'           => trim($_POST['focus'] ?? ''),
                'industries'      => trim($_POST['industries'] ?? ''),
                'priority'        => $_POST['priority'] ?? 'core',
                'differentiators' => trim($_POST['differentiators'] ?? ''),
                'head_name'       => trim($_POST['head_name'] ?? ''),
                'positioning'     => trim($_POST['positioning'] ?? ''),
            );
            if (!$fields['name']) { echo json_encode(array('ok'=>false,'error'=>'Name is required')); break; }
            if ($id) { DB::update('kb_verticals', $fields, 'id = ?', array($id)); }
            else { DB::insert('kb_verticals', $fields); }
            echo json_encode(array('ok' => true));
            break;

        case 'delete_vertical':
            $id = (int)($_POST['id'] ?? 0);
            if ($id) DB::query('DELETE FROM kb_verticals WHERE id = ?', array($id));
            echo json_encode(array('ok' => true));
            break;

        case 'save_service':
            $id = (int)($_POST['id'] ?? 0);
            $fields = array(
                'vertical_id'       => (int)($_POST['vertical_id'] ?? 0) ?: null,
                'name'              => trim($_POST['name'] ?? ''),
                'one_liner'         => trim($_POST['one_liner'] ?? ''),
                'industries'        => trim($_POST['industries'] ?? ''),
                'icp_size'          => trim($_POST['icp_size'] ?? ''),
                'buyer_titles'      => trim($_POST['buyer_titles'] ?? ''),
                'engagement_model'  => trim($_POST['engagement_model'] ?? ''),
                'signal_keywords'   => trim($_POST['signal_keywords'] ?? ''),
                'signal_types'      => trim($_POST['signal_types'] ?? ''),
                'tech_triggers'     => trim($_POST['tech_triggers'] ?? ''),
                'competing_tools'   => trim($_POST['competing_tools'] ?? ''),
                'description'       => trim($_POST['description'] ?? ''),
                'problem_statement' => trim($_POST['problem_statement'] ?? ''),
                'outcomes'          => trim($_POST['outcomes'] ?? ''),
                'differentiators'   => trim($_POST['differentiators'] ?? ''),
                'proof_points'      => trim($_POST['proof_points'] ?? ''),
            );
            if (!$fields['name']) { echo json_encode(array('ok'=>false,'error'=>'Name is required')); break; }
            if ($id) { DB::update('kb_services', $fields, 'id = ?', array($id)); }
            else { DB::insert('kb_services', $fields); }
            echo json_encode(array('ok' => true));
            break;

        case 'delete_service':
            $id = (int)($_POST['id'] ?? 0);
            if ($id) DB::query('DELETE FROM kb_services WHERE id = ?', array($id));
            echo json_encode(array('ok' => true));
            break;

        case 'save_icp':
            $id = (int)($_POST['id'] ?? 0);
            $fields = array(
                'name'               => trim($_POST['name'] ?? ''),
                'vertical_id'        => (int)($_POST['vertical_id'] ?? 0) ?: null,
                'service_id'         => (int)($_POST['service_id'] ?? 0) ?: null,
                'size_range'         => trim($_POST['size_range'] ?? ''),
                'revenue_range'      => trim($_POST['revenue_range'] ?? ''),
                'industries'         => trim($_POST['industries'] ?? ''),
                'geographies'        => trim($_POST['geographies'] ?? ''),
                'tech_stack_signals' => trim($_POST['tech_stack_signals'] ?? ''),
                'trigger_events'     => trim($_POST['trigger_events'] ?? ''),
                'perfect_fit'        => trim($_POST['perfect_fit'] ?? ''),
                'disqualifiers'      => trim($_POST['disqualifiers'] ?? ''),
                'buying_process'     => trim($_POST['buying_process'] ?? ''),
            );
            if (!$fields['name']) { echo json_encode(array('ok'=>false,'error'=>'Name is required')); break; }
            if ($id) { DB::update('kb_icps', $fields, 'id = ?', array($id)); }
            else { DB::insert('kb_icps', $fields); }
            echo json_encode(array('ok' => true));
            break;

        case 'delete_icp':
            $id = (int)($_POST['id'] ?? 0);
            if ($id) DB::query('DELETE FROM kb_icps WHERE id = ?', array($id));
            echo json_encode(array('ok' => true));
            break;

        case 'save_persona':
            $id = (int)($_POST['id'] ?? 0);
            $fields = array(
                'name'                 => trim($_POST['name'] ?? ''),
                'title'                => trim($_POST['title'] ?? ''),
                'department'           => trim($_POST['department'] ?? ''),
                'seniority'            => $_POST['seniority'] ?? 'Director',
                'vertical_id'          => (int)($_POST['vertical_id'] ?? 0) ?: null,
                'service_id'           => (int)($_POST['service_id'] ?? 0) ?: null,
                'reporting_to'         => trim($_POST['reporting_to'] ?? ''),
                'goals'                => trim($_POST['goals'] ?? ''),
                'pain_points'          => trim($_POST['pain_points'] ?? ''),
                'objections'           => trim($_POST['objections'] ?? ''),
                'kpis'                 => trim($_POST['kpis'] ?? ''),
                'decision_role'        => $_POST['decision_role'] ?? 'Champion',
                'communication_style'  => trim($_POST['communication_style'] ?? ''),
                'preferred_content'    => trim($_POST['preferred_content'] ?? ''),
                'watering_holes'       => trim($_POST['watering_holes'] ?? ''),
                'email_hook'           => trim($_POST['email_hook'] ?? ''),
            );
            if (!$fields['name']) { echo json_encode(array('ok'=>false,'error'=>'Persona name is required')); break; }
            if ($id) { DB::update('kb_personas', $fields, 'id = ?', array($id)); }
            else { DB::insert('kb_personas', $fields); }
            echo json_encode(array('ok' => true));
            break;

        case 'delete_persona':
            $id = (int)($_POST['id'] ?? 0);
            if ($id) DB::query('DELETE FROM kb_personas WHERE id = ?', array($id));
            echo json_encode(array('ok' => true));
            break;

        case 'save_tone':
            $fields = array(
                'tone_descriptors'    => trim($_POST['tone_descriptors'] ?? ''),
                'anti_tone'           => trim($_POST['anti_tone'] ?? ''),
                'words_always'        => trim($_POST['words_always'] ?? ''),
                'words_never'         => trim($_POST['words_never'] ?? ''),
                'email_opening_style' => trim($_POST['email_opening_style'] ?? ''),
                'cta_style'           => trim($_POST['cta_style'] ?? ''),
                'email_length'        => $_POST['email_length'] ?? 'medium',
                'paragraph_style'     => $_POST['paragraph_style'] ?? 'full-paragraphs',
                'good_example'        => trim($_POST['good_example'] ?? ''),
                'bad_example'         => trim($_POST['bad_example'] ?? ''),
            );
            $existing = DB::fetchOne('SELECT id FROM kb_tone LIMIT 1');
            if ($existing) { DB::update('kb_tone', $fields, 'id = ?', array($existing['id'])); }
            else { DB::insert('kb_tone', $fields); }
            echo json_encode(array('ok' => true));
            break;

        case 'save_sender':
            $id = (int)($_POST['id'] ?? 0);
            $fields = array(
                'full_name'           => trim($_POST['full_name'] ?? ''),
                'title'               => trim($_POST['title'] ?? ''),
                'email'               => trim($_POST['email'] ?? ''),
                'linkedin_url'        => trim($_POST['linkedin_url'] ?? ''),
                'background'          => trim($_POST['background'] ?? ''),
                'credibility'         => trim($_POST['credibility'] ?? ''),
                'years_experience'    => (int)($_POST['years_experience'] ?? 0),
                'individual_tone'     => trim($_POST['individual_tone'] ?? ''),
                'email_opening_style' => trim($_POST['email_opening_style'] ?? ''),
                'email_closing_style' => trim($_POST['email_closing_style'] ?? ''),
                'verticals'           => trim($_POST['verticals'] ?? ''),
                'calendar_link'       => trim($_POST['calendar_link'] ?? ''),
                'signature'           => trim($_POST['signature'] ?? ''),
                'example_emails'      => trim($_POST['example_emails'] ?? ''),
                'is_default'          => ($_POST['is_default'] ?? '0') === '1' ? 1 : 0,
            );
            if (!$fields['full_name']) { echo json_encode(array('ok'=>false,'error'=>'Full name is required')); break; }
            if ($id) { DB::update('kb_senders', $fields, 'id = ?', array($id)); }
            else { DB::insert('kb_senders', $fields); }
            if ($fields['is_default']) {
                $newId = $id ?: DB::fetchOne('SELECT LAST_INSERT_ID() as lid')['lid'];
                if ($newId) DB::query('UPDATE kb_senders SET is_default=0 WHERE id != ?', array($newId));
            }
            echo json_encode(array('ok' => true));
            break;

        case 'delete_sender':
            $id = (int)($_POST['id'] ?? 0);
            if ($id) DB::query('DELETE FROM kb_senders WHERE id = ?', array($id));
            echo json_encode(array('ok' => true));
            break;

        case 'save_proof':
            $id = (int)($_POST['id'] ?? 0);
            $fields = array(
                'client_name'       => trim($_POST['client_name'] ?? ''),
                'client_industry'   => trim($_POST['client_industry'] ?? ''),
                'client_size'       => trim($_POST['client_size'] ?? ''),
                'vertical_id'       => (int)($_POST['vertical_id'] ?? 0) ?: null,
                'service_id'        => (int)($_POST['service_id'] ?? 0) ?: null,
                'challenge'         => trim($_POST['challenge'] ?? ''),
                'solution'          => trim($_POST['solution'] ?? ''),
                'outcomes'          => trim($_POST['outcomes'] ?? ''),
                'metrics'           => trim($_POST['metrics'] ?? ''),
                'quote'             => trim($_POST['quote'] ?? ''),
                'quote_attribution' => trim($_POST['quote_attribution'] ?? ''),
                'is_public'         => ($_POST['is_public'] ?? '0') === '1' ? 1 : 0,
            );
            if (!$fields['client_name']) { echo json_encode(array('ok'=>false,'error'=>'Client name is required')); break; }
            if ($id) { DB::update('kb_proof', $fields, 'id = ?', array($id)); }
            else { DB::insert('kb_proof', $fields); }
            echo json_encode(array('ok' => true));
            break;

        case 'delete_proof':
            $id = (int)($_POST['id'] ?? 0);
            if ($id) DB::query('DELETE FROM kb_proof WHERE id = ?', array($id));
            echo json_encode(array('ok' => true));
            break;

        case 'save_document':
            $id = (int)($_POST['id'] ?? 0);
            $docTypes = array('case_study','whitepaper','brochure','deck','one_pager','roi_calculator','video','other');
            $fields = array(
                'title'       => trim($_POST['title'] ?? ''),
                'doc_type'    => in_array($_POST['doc_type']??'', $docTypes) ? $_POST['doc_type'] : 'other',
                'url'         => trim($_POST['url'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'use_case'    => trim($_POST['use_case'] ?? ''),
                'vertical_id' => (int)($_POST['vertical_id'] ?? 0) ?: null,
                'service_id'  => (int)($_POST['service_id'] ?? 0) ?: null,
                'is_public'   => ($_POST['is_public'] ?? '0') === '1' ? 1 : 0,
            );
            if (!$fields['title']) { echo json_encode(array('ok'=>false,'error'=>'Title is required')); break; }
            if ($id) { DB::update('kb_documents', $fields, 'id = ?', array($id)); }
            else { DB::insert('kb_documents', $fields); }
            echo json_encode(array('ok' => true));
            break;

        case 'delete_document':
            $id = (int)($_POST['id'] ?? 0);
            if ($id) DB::query('DELETE FROM kb_documents WHERE id = ?', array($id));
            echo json_encode(array('ok' => true));
            break;

        case 'save_asset':
            $id       = (int)($_POST['id'] ?? 0);
            $tenantId = Auth::tenantId();
            $validCategories  = array('content','tool','entry_door');
            $validAssetTypes  = array('pdf','guide','whitepaper','assessment','calculator','diagnostic','checklist','template','webinar','free_audit','poc','pilot','consultation','other');
            $validStages      = array('awareness','consideration','decision');
            $fields = array(
                'tenant_id'    => $tenantId,
                'service_id'   => (int)($_POST['service_id'] ?? 0) ?: null,
                'vertical_id'  => (int)($_POST['vertical_id'] ?? 0) ?: null,
                'category'     => in_array($_POST['category']??'', $validCategories) ? $_POST['category'] : 'content',
                'asset_type'   => in_array($_POST['asset_type']??'', $validAssetTypes) ? $_POST['asset_type'] : 'pdf',
                'name'         => trim($_POST['name'] ?? ''),
                'description'  => trim($_POST['description'] ?? ''),
                'url'          => trim($_POST['url'] ?? ''),
                'cta_text'     => trim($_POST['cta_text'] ?? ''),
                'use_in_touch' => trim($_POST['use_in_touch'] ?? '2') ?: '2',
                'target_stage' => in_array($_POST['target_stage']??'', $validStages) ? $_POST['target_stage'] : 'consideration',
                'is_active'    => ($_POST['is_active'] ?? '0') === '1' ? 1 : 0,
            );
            if (!$fields['name']) { echo json_encode(array('ok'=>false,'error'=>'Name is required')); break; }
            if ($id) {
                DB::update('kb_assets', $fields, 'id = ? AND tenant_id = ?', array($id, $tenantId));
            } else {
                DB::insert('kb_assets', $fields);
            }
            echo json_encode(array('ok' => true));
            break;

        case 'delete_asset':
            $id       = (int)($_POST['id'] ?? 0);
            $tenantId = Auth::tenantId();
            if ($id) DB::query('DELETE FROM kb_assets WHERE id = ? AND tenant_id = ?', array($id, $tenantId));
            echo json_encode(array('ok' => true));
            break;

        case 'import_assets':
            $tenantId = Auth::tenantId();
            $rows = $_POST['rows'] ?? array();
            $inserted = 0;
            foreach ($rows as $row) {
                $name = trim($row['name'] ?? '');
                if (!$name) continue;
                DB::insert('kb_assets', array(
                    'tenant_id'    => $tenantId,
                    'name'         => $name,
                    'category'     => $row['category'] ?? 'content',
                    'asset_type'   => $row['asset_type'] ?? 'pdf',
                    'description'  => $row['description'] ?? '',
                    'url'          => $row['url'] ?? '',
                    'cta_text'     => $row['cta_text'] ?? '',
                    'use_in_touch' => $row['use_in_touch'] ?? '2',
                    'target_stage' => $row['target_stage'] ?? 'consideration',
                    'is_active'    => 1,
                ));
                $inserted++;
            }
            echo json_encode(array('ok' => true, 'message' => "Imported {$inserted} assets."));
            break;

        default:
            echo json_encode(array('error' => 'Unknown action: ' . htmlspecialchars($action)));
    }

} catch (Exception $e) {
    echo json_encode(array('error' => $e->getMessage(), 'ok' => false));
}
