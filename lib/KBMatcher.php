<?php
class KBMatcher {
    public static function matchService($signalTypes, $techStack, $industry) {
        $services = DB::fetchAll(
            'SELECT s.*, v.name as vertical_name FROM kb_services s LEFT JOIN kb_verticals v ON s.vertical_id = v.id'
        );
        if (!$services) return null;

        $best      = null;
        $bestScore = 0;
        $techTools = is_array($techStack) ? array_column($techStack, 'tool') : array();

        foreach ($services as $service) {
            $score = 0;

            $svcSignalTypes = array_filter(array_map('trim', explode(',', $service['signal_types'] ?? '')));
            foreach ($signalTypes as $st) {
                if (in_array($st, $svcSignalTypes)) $score += 3;
            }

            $svcTechTriggers = array_filter(array_map('trim', explode(',', $service['tech_triggers'] ?? '')));
            foreach ($techTools as $tool) {
                if (in_array($tool, $svcTechTriggers)) $score += 3;
            }

            $svcIndustries = array_filter(array_map('strtolower', array_map('trim', explode(',', $service['industries'] ?? ''))));
            if ($industry && in_array(strtolower($industry), $svcIndustries)) $score += 2;

            if ($score > $bestScore) {
                $bestScore = $score;
                $best      = $service;
            }
        }

        return ($bestScore >= 2) ? $best : null;
    }

    public static function matchICPs($service, $company, $limit = 5): array {
        if (!$service) return array();

        $icps = DB::fetchAll(
            'SELECT * FROM kb_icps WHERE service_id = ? OR vertical_id = ?',
            array($service['id'], $service['vertical_id'] ?? 0)
        );
        if (!$icps) return array();

        $techTools = array();
        if (!empty($company['id'])) {
            $tech = DB::fetchAll('SELECT tool FROM company_tech WHERE company_id = ?', array($company['id']));
            $techTools = array_column($tech ?: array(), 'tool');
        }

        foreach ($icps as &$icp) {
            $score = 0;

            // +3 if icp.industries overlaps company.industry
            if (!empty($icp['industries']) && !empty($company['industry'])) {
                $icpIndustries = array_filter(array_map('strtolower', array_map('trim', explode(',', $icp['industries']))));
                if (in_array(strtolower($company['industry']), $icpIndustries)) $score += 3;
            }

            // +2 if icp.geographies contains company.country
            if (!empty($icp['geographies']) && !empty($company['country'])) {
                if (stripos($icp['geographies'], $company['country']) !== false) $score += 2;
            }

            // +3 if icp.tech_stack_signals overlaps detected tech tools
            if (!empty($icp['tech_stack_signals']) && $techTools) {
                $icpTech = array_filter(array_map('trim', explode(',', $icp['tech_stack_signals'])));
                foreach ($techTools as $tool) {
                    foreach ($icpTech as $it) {
                        if (stripos($tool, $it) !== false || stripos($it, $tool) !== false) {
                            $score += 3;
                            break 2;
                        }
                    }
                }
            }

            // +2 if matched service_id == icp.service_id
            if (!empty($icp['service_id']) && (int)$icp['service_id'] === (int)$service['id']) $score += 2;

            // +1 if matched vertical_id == icp.vertical_id
            if (!empty($icp['vertical_id']) && !empty($service['vertical_id']) && (int)$icp['vertical_id'] === (int)$service['vertical_id']) $score += 1;

            $icp['_score'] = $score;
        }
        unset($icp);

        usort($icps, function($a, $b) { return $b['_score'] - $a['_score']; });

        return array_slice($icps, 0, $limit);
    }

    // Legacy single-match method kept for backwards compatibility
    public static function matchICP($service, $company) {
        $results = self::matchICPs($service, $company, 1);
        return $results ? $results[0] : null;
    }

    public static function topSignals(int $companyId, int $limit = 5): array {
        try {
            $rows = DB::fetchAll(
                'SELECT id, title, source, created_at FROM signals WHERE company_id = ? ORDER BY created_at DESC LIMIT ' . (int)$limit,
                array($companyId)
            );
            return $rows ?: array();
        } catch (Exception $e) {
            return array();
        }
    }

    public static function matchAssets(int $serviceId, int $tenantId, int $touchNumber): array {
        try {
            $assets = DB::fetchAll(
                'SELECT * FROM kb_assets WHERE tenant_id = ? AND is_active = 1 AND (service_id = ? OR service_id IS NULL)',
                array($tenantId, $serviceId)
            );
            if (!$assets) return array();

            $matched = array();
            foreach ($assets as $asset) {
                $touches = array_filter(array_map('trim', explode(',', $asset['use_in_touch'] ?? '')));
                if (in_array((string)$touchNumber, $touches)) {
                    $matched[] = $asset;
                }
            }
            return $matched;
        } catch (Exception $e) {
            return array();
        }
    }
}
