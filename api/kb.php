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
            echo json_encode(array('ok' => true));
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
            if ($id) {
                DB::update('kb_verticals', $fields, 'id = ?', array($id));
            } else {
                DB::insert('kb_verticals', $fields);
            }
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
            if ($id) {
                DB::update('kb_services', $fields, 'id = ?', array($id));
            } else {
                DB::insert('kb_services', $fields);
            }
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
                'industries'         => trim($_POST['industries'] ?? ''),
                'geographies'        => trim($_POST['geographies'] ?? ''),
                'tech_stack_signals' => trim($_POST['tech_stack_signals'] ?? ''),
                'trigger_events'     => trim($_POST['trigger_events'] ?? ''),
                'perfect_fit'        => trim($_POST['perfect_fit'] ?? ''),
                'poor_fit'           => trim($_POST['poor_fit'] ?? ''),
                'disqualifiers'      => trim($_POST['disqualifiers'] ?? ''),
            );
            if ($id) {
                DB::update('kb_icps', $fields, 'id = ?', array($id));
            } else {
                DB::insert('kb_icps', $fields);
            }
            echo json_encode(array('ok' => true));
            break;

        case 'delete_icp':
            $id = (int)($_POST['id'] ?? 0);
            if ($id) DB::query('DELETE FROM kb_icps WHERE id = ?', array($id));
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
            if ($existing) {
                DB::update('kb_tone', $fields, 'id = ?', array($existing['id']));
            } else {
                DB::insert('kb_tone', $fields);
            }
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
                'is_default'          => isset($_POST['is_default']) ? 1 : 0,
            );
            if ($id) {
                DB::update('kb_senders', $fields, 'id = ?', array($id));
            } else {
                DB::insert('kb_senders', $fields);
            }
            if ($fields['is_default']) {
                $newId = $id ?: DB::fetchOne('SELECT LAST_INSERT_ID() as lid')['lid'];
                DB::query('UPDATE kb_senders SET is_default=0 WHERE id != ?', array($newId));
            }
            echo json_encode(array('ok' => true));
            break;

        case 'delete_sender':
            $id = (int)($_POST['id'] ?? 0);
            if ($id) DB::query('DELETE FROM kb_senders WHERE id = ?', array($id));
            echo json_encode(array('ok' => true));
            break;

        default:
            echo json_encode(array('error' => 'Unknown action'));
    }

} catch (Exception $e) {
    echo json_encode(array('error' => $e->getMessage(), 'ok' => false));
}
