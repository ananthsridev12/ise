<?php
class TechExtractor {
    private static $TOOLS = [
        ['name'=>'SAP S/4HANA',    'aliases'=>['s/4hana','s4hana','s4 hana'],            'category'=>'ERP'],
        ['name'=>'SAP ECC',        'aliases'=>['sap ecc','sap r/3','sap r3'],             'category'=>'ERP'],
        ['name'=>'SAP EWM',        'aliases'=>['sap ewm','extended warehouse'],            'category'=>'WMS'],
        ['name'=>'SAP MM',         'aliases'=>['sap mm','materials management'],           'category'=>'ERP'],
        ['name'=>'SAP PP',         'aliases'=>['sap pp','production planning'],            'category'=>'MES'],
        ['name'=>'SAP CPQ',        'aliases'=>['sap cpq','sap configure price'],           'category'=>'CPQ'],
        ['name'=>'Oracle EBS',     'aliases'=>['oracle ebs','e-business suite','oracle financials'], 'category'=>'ERP'],
        ['name'=>'Oracle Fusion',  'aliases'=>['oracle fusion','oracle cloud erp'],        'category'=>'ERP'],
        ['name'=>'Oracle SCM',     'aliases'=>['oracle scm','oracle supply chain'],        'category'=>'SCM'],
        ['name'=>'Oracle CPQ',     'aliases'=>['oracle cpq','big machines'],               'category'=>'CPQ'],
        ['name'=>'NetSuite',       'aliases'=>['netsuite','oracle netsuite'],              'category'=>'ERP'],
        ['name'=>'JD Edwards',     'aliases'=>['jd edwards','jde','jdedwards'],           'category'=>'ERP'],
        ['name'=>'Dynamics 365',   'aliases'=>['dynamics 365','d365','microsoft dynamics 365'], 'category'=>'ERP'],
        ['name'=>'Dynamics AX',    'aliases'=>['dynamics ax','axapta'],                   'category'=>'ERP'],
        ['name'=>'Dynamics NAV',   'aliases'=>['dynamics nav','navision'],                'category'=>'ERP'],
        ['name'=>'Business Central','aliases'=>['business central','bc365'],              'category'=>'ERP'],
        ['name'=>'Epicor',         'aliases'=>['epicor'],                                 'category'=>'ERP'],
        ['name'=>'Infor LN',       'aliases'=>['infor ln','baan'],                        'category'=>'ERP'],
        ['name'=>'Infor M3',       'aliases'=>['infor m3','movex'],                       'category'=>'ERP'],
        ['name'=>'IFS',            'aliases'=>['ifs erp','ifs applications'],             'category'=>'ERP'],
        ['name'=>'Sage X3',        'aliases'=>['sage x3','sage enterprise'],              'category'=>'ERP'],
        ['name'=>'QAD',            'aliases'=>['qad erp'],                                'category'=>'ERP'],
        ['name'=>'SYSPRO',         'aliases'=>['syspro'],                                 'category'=>'ERP'],
        ['name'=>'Salesforce CPQ', 'aliases'=>['sfdc cpq','salesforce cpq','steelbrick'], 'category'=>'CPQ'],
        ['name'=>'Apttus CPQ',     'aliases'=>['apttus','conga cpq'],                    'category'=>'CPQ'],
        ['name'=>'Pricefx',        'aliases'=>['pricefx'],                               'category'=>'CPQ'],
        ['name'=>'PROS',           'aliases'=>['pros pricing','pros cpq'],               'category'=>'CPQ'],
        ['name'=>'Vendavo',        'aliases'=>['vendavo'],                               'category'=>'CPQ'],
        ['name'=>'Siemens Opcenter','aliases'=>['opcenter','camstar'],                   'category'=>'MES'],
        ['name'=>'Rockwell Plex',  'aliases'=>['plex systems','plex erp'],               'category'=>'MES'],
        ['name'=>'Wonderware',     'aliases'=>['wonderware','aveva mes'],                'category'=>'MES'],
        ['name'=>'FactoryTalk',    'aliases'=>['factorytalk'],                           'category'=>'MES'],
        ['name'=>'Ignition SCADA', 'aliases'=>['ignition scada','inductive automation'], 'category'=>'MES'],
        ['name'=>'Manhattan Associates','aliases'=>['manhattan associates','manhattan wms'],'category'=>'WMS'],
        ['name'=>'Blue Yonder',    'aliases'=>['blue yonder','jda software'],            'category'=>'WMS'],
        ['name'=>'Salesforce CRM', 'aliases'=>['salesforce crm','sfdc'],                'category'=>'CRM'],
        ['name'=>'SAP CRM',        'aliases'=>['sap crm','sap c4c'],                    'category'=>'CRM'],
        ['name'=>'Siemens Teamcenter','aliases'=>['teamcenter'],                         'category'=>'PLM'],
        ['name'=>'PTC Windchill',  'aliases'=>['windchill','ptc windchill'],             'category'=>'PLM'],
        ['name'=>'SAP PLM',        'aliases'=>['sap plm'],                              'category'=>'PLM'],
    ];

    public static function extract($text) {
        $lower = strtolower($text);
        $hits = [];
        foreach (self::$TOOLS as $tool) {
            $aliases = isset($tool['aliases']) ? $tool['aliases'] : [];
            $allTerms = array_merge([strtolower($tool['name'])], array_map('strtolower', $aliases));
            foreach ($allTerms as $term) {
                if (strpos($lower, $term) !== false) {
                    $hits[] = [
                        'tool'       => $tool['name'],
                        'category'   => $tool['category'],
                        'confidence' => ($term === strtolower($tool['name'])) ? 90 : 60,
                    ];
                    break;
                }
            }
        }
        return $hits;
    }
}
