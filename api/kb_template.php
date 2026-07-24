<?php
$entity  = $_GET['entity'] ?? '';
$allowed = array('verticals','services','icps','personas');
if (!in_array($entity, $allowed)) { http_response_code(400); exit; }

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $entity . '_template.csv"');

$templates = array(
    'verticals' => array(
        'headers' => array('name','focus','industries','priority','differentiators','head_name','positioning'),
        'example' => array('ERP Practice','Enterprise resource planning implementations','Manufacturing,Distribution','core','SAP expertise, 50+ implementations','John Smith','The go-to partner for mid-market ERP'),
    ),
    'services' => array(
        'headers' => array('name','vertical_name','one_liner','industries','icp_size','buyer_titles','engagement_model','signal_keywords','signal_types','tech_triggers','problem_statement','outcomes','differentiators','description'),
        'example' => array('SAP S/4HANA Migration','ERP Practice','Migrate from SAP ECC to S/4HANA','Manufacturing,Retail','500-5000 employees','CIO,ERP Director','Project','migration,erp upgrade','ERP,Migration','SAP ECC,SAP ECC 6.0','Customers on legacy SAP face end-of-life risk','Reduced TCO and real-time reporting','Certified SAP partner with 40+ migrations','Full lifecycle migration services'),
    ),
    'icps' => array(
        'headers' => array('name','vertical_name','service_name','size_range','revenue_range','industries','geographies','tech_stack_signals','trigger_events','perfect_fit','disqualifiers','buying_process'),
        'example' => array('Mid-market ERP Upgrader','ERP Practice','SAP S/4HANA Migration','500-5000 employees','$100M-$1B','Manufacturing,Distribution','North America,EMEA','SAP ECC,SAP ECC 6.0','ECC end-of-life, new CIO, M&A','Running SAP ECC with 500+ users','Already on S/4HANA','12-18 month deal with IT + Finance committee'),
    ),
    'personas' => array(
        'headers' => array('name','title','department','seniority','vertical_name','service_name','reporting_to','goals','pain_points','objections','kpis','decision_role','communication_style','preferred_content','watering_holes','email_hook'),
        'example' => array('The Digital CIO','Chief Information Officer','IT','C-Suite','ERP Practice','SAP S/4HANA Migration','CEO','Modernize IT stack and reduce downtime','Legacy systems and integration complexity','Budget constraints and risk of disruption','System uptime and project delivery %','Economic Buyer','Data-driven and prefers concise emails','Case studies and ROI calculators','LinkedIn and Gartner and SAP events','Lead with operational risk and peer benchmarks'),
    ),
);

$fh  = fopen('php://output', 'w');
$tpl = $templates[$entity];
fputcsv($fh, $tpl['headers']);
fputcsv($fh, $tpl['example']);
fclose($fh);
