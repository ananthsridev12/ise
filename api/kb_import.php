<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/DB.php';

try {
    $entity = $_POST['entity'] ?? '';
    if (!in_array($entity, array('verticals','services','icps','personas'))) {
        echo json_encode(array('ok'=>false,'error'=>'Invalid entity')); exit;
    }
    if (empty($_FILES['file']['tmp_name'])) {
        echo json_encode(array('ok'=>false,'error'=>'No file uploaded')); exit;
    }

    $fh = fopen($_FILES['file']['tmp_name'], 'r');
    if (!$fh) { echo json_encode(array('ok'=>false,'error'=>'Cannot read file')); exit; }

    $headers = fgetcsv($fh);
    if (!$headers) { echo json_encode(array('ok'=>false,'error'=>'Empty CSV')); exit; }
    $headers = array_map('trim', $headers);

    $imported = 0; $skipped = 0; $errors = array();

    $vertMap = array(); $svcMap = array();
    if (in_array($entity, array('services','icps','personas'))) {
        foreach (DB::fetchAll('SELECT id, name FROM kb_verticals') as $v)
            $vertMap[strtolower(trim($v['name']))] = $v['id'];
    }
    if (in_array($entity, array('icps','personas'))) {
        foreach (DB::fetchAll('SELECT id, name FROM kb_services') as $s)
            $svcMap[strtolower(trim($s['name']))] = $s['id'];
    }

    $row = 1;
    while (($cols = fgetcsv($fh)) !== false) {
        $row++;
        $data = array();
        foreach ($headers as $i => $h) $data[$h] = isset($cols[$i]) ? trim($cols[$i]) : '';

        try {
            if ($entity === 'verticals') {
                if (empty($data['name'])) { $skipped++; continue; }
                DB::insert('kb_verticals', array(
                    'name'            => $data['name'],
                    'focus'           => $data['focus'] ?? '',
                    'industries'      => $data['industries'] ?? '',
                    'priority'        => in_array($data['priority']??'', array('core','growth','emerging')) ? $data['priority'] : 'core',
                    'differentiators' => $data['differentiators'] ?? '',
                    'head_name'       => $data['head_name'] ?? '',
                    'positioning'     => $data['positioning'] ?? '',
                ));
                $imported++;

            } elseif ($entity === 'services') {
                if (empty($data['name'])) { $skipped++; continue; }
                $vId = !empty($data['vertical_name']) ? ($vertMap[strtolower($data['vertical_name'])] ?? null) : null;
                DB::insert('kb_services', array(
                    'vertical_id'       => $vId,
                    'name'              => $data['name'],
                    'one_liner'         => $data['one_liner'] ?? '',
                    'industries'        => $data['industries'] ?? '',
                    'icp_size'          => $data['icp_size'] ?? '',
                    'buyer_titles'      => $data['buyer_titles'] ?? '',
                    'engagement_model'  => $data['engagement_model'] ?? '',
                    'signal_keywords'   => $data['signal_keywords'] ?? '',
                    'signal_types'      => $data['signal_types'] ?? '',
                    'tech_triggers'     => $data['tech_triggers'] ?? '',
                    'problem_statement' => $data['problem_statement'] ?? '',
                    'outcomes'          => $data['outcomes'] ?? '',
                    'differentiators'   => $data['differentiators'] ?? '',
                    'description'       => $data['description'] ?? '',
                ));
                $imported++;

            } elseif ($entity === 'icps') {
                if (empty($data['name'])) { $skipped++; continue; }
                $vId = !empty($data['vertical_name']) ? ($vertMap[strtolower($data['vertical_name'])] ?? null) : null;
                $sId = !empty($data['service_name'])  ? ($svcMap[strtolower($data['service_name'])]  ?? null) : null;
                DB::insert('kb_icps', array(
                    'name'               => $data['name'],
                    'vertical_id'        => $vId,
                    'service_id'         => $sId,
                    'size_range'         => $data['size_range'] ?? '',
                    'revenue_range'      => $data['revenue_range'] ?? '',
                    'industries'         => $data['industries'] ?? '',
                    'geographies'        => $data['geographies'] ?? '',
                    'tech_stack_signals' => $data['tech_stack_signals'] ?? '',
                    'trigger_events'     => $data['trigger_events'] ?? '',
                    'perfect_fit'        => $data['perfect_fit'] ?? '',
                    'disqualifiers'      => $data['disqualifiers'] ?? '',
                    'buying_process'     => $data['buying_process'] ?? '',
                ));
                $imported++;

            } elseif ($entity === 'personas') {
                if (empty($data['name'])) { $skipped++; continue; }
                $vId = !empty($data['vertical_name']) ? ($vertMap[strtolower($data['vertical_name'])] ?? null) : null;
                $sId = !empty($data['service_name'])  ? ($svcMap[strtolower($data['service_name'])]  ?? null) : null;
                $seniorityOpts    = array('C-Suite','VP','Director','Manager','Individual Contributor');
                $decisionRoleOpts = array('Economic Buyer','Champion','Technical Buyer','End User','Influencer','Blocker');
                DB::insert('kb_personas', array(
                    'name'                => $data['name'],
                    'title'               => $data['title'] ?? '',
                    'department'          => $data['department'] ?? '',
                    'seniority'           => in_array($data['seniority']??'', $seniorityOpts) ? $data['seniority'] : 'Director',
                    'vertical_id'         => $vId,
                    'service_id'          => $sId,
                    'reporting_to'        => $data['reporting_to'] ?? '',
                    'goals'               => $data['goals'] ?? '',
                    'pain_points'         => $data['pain_points'] ?? '',
                    'objections'          => $data['objections'] ?? '',
                    'kpis'                => $data['kpis'] ?? '',
                    'decision_role'       => in_array($data['decision_role']??'', $decisionRoleOpts) ? $data['decision_role'] : 'Champion',
                    'communication_style' => $data['communication_style'] ?? '',
                    'preferred_content'   => $data['preferred_content'] ?? '',
                    'watering_holes'      => $data['watering_holes'] ?? '',
                    'email_hook'          => $data['email_hook'] ?? '',
                ));
                $imported++;
            }
        } catch (Exception $e) {
            $errors[] = "Row {$row}: " . $e->getMessage();
        }
    }
    fclose($fh);

    $msg = "Imported {$imported} rows.";
    if ($skipped) $msg .= " Skipped {$skipped} (missing name).";
    if ($errors)  $msg .= ' Errors: ' . implode('; ', array_slice($errors, 0, 3));

    echo json_encode(array('ok'=>true,'imported'=>$imported,'skipped'=>$skipped,'errors'=>$errors,'message'=>$msg));

} catch (Exception $e) {
    echo json_encode(array('ok'=>false,'error'=>$e->getMessage()));
}
