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

    public static function matchICP($service, $company) {
        if (!$service) return null;
        $icps = DB::fetchAll(
            'SELECT * FROM kb_icps WHERE service_id = ? OR vertical_id = ? ORDER BY service_id DESC LIMIT 1',
            array($service['id'], $service['vertical_id'])
        );
        return $icps ? $icps[0] : null;
    }
}
