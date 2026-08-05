<?php
class SequencePlanner {

    public static function detectStage(array $company, array $aiSettings): string {
        $default = $aiSettings['default_stage'] ?? 'auto';
        if ($default !== 'auto') return $default;

        $score = (int)($company['score'] ?? 0);
        $tofMax = (int)($aiSettings['tof_score_max'] ?? 40);
        $bofMin = (int)($aiSettings['bof_score_min'] ?? 71);

        if ($score <= $tofMax) return 'tof';
        if ($score >= $bofMin) return 'bof';
        return 'mof';
    }

    public static function plan(array $company, ?array $service, array $assets, array $aiSettings, int $numTouches, string $stage = ''): array {
        if (!$stage) $stage = self::detectStage($company, $aiSettings);

        // Load per-stage sequence config from ai_settings (JSON array of intents per touch)
        $stageKey = $stage . '_sequence';
        $sequenceConfig = [];
        if (!empty($aiSettings[$stageKey])) {
            $sequenceConfig = json_decode($aiSettings[$stageKey], true) ?: [];
        }

        // Default intent sequences
        $defaults = [
            'tof' => ['awareness', 'value_insight', 'lead_magnet', 'soft_cta', 'breakup'],
            'mof' => ['problem_agitate', 'tool_offer', 'entry_door', 'case_study', 'direct_ask'],
            'bof' => ['direct_pitch', 'entry_door', 'roi_case', 'urgency', 'breakup'],
        ];
        $intents = !empty($sequenceConfig) ? $sequenceConfig : ($defaults[$stage] ?? $defaults['mof']);

        // Index assets by category
        $contentAssets = array_filter($assets, fn($a) => $a['category'] === 'content');
        $entryDoors    = array_filter($assets, fn($a) => $a['category'] === 'entry_door');
        $tools         = array_filter($assets, fn($a) => $a['category'] === 'tool');

        $plan = [];
        for ($touch = 1; $touch <= $numTouches; $touch++) {
            $intent = $intents[$touch - 1] ?? 'follow_up';
            $asset  = null;

            // Asset fallback chain: match touch number -> entry_door -> content -> null (direct pitch)
            $touchAssets = array_filter($assets, function($a) use ($touch) {
                $touches = array_filter(array_map('trim', explode(',', $a['use_in_touch'] ?? '')));
                return in_array((string)$touch, $touches);
            });
            if ($touchAssets) {
                $asset = array_values($touchAssets)[0];
            } elseif (in_array($intent, ['entry_door','lead_magnet','tool_offer']) && $entryDoors) {
                $asset = array_values($entryDoors)[0];
            } elseif (in_array($intent, ['lead_magnet','value_insight']) && $contentAssets) {
                $asset = array_values($contentAssets)[0];
            } elseif (in_array($intent, ['tool_offer']) && $tools) {
                $asset = array_values($tools)[0];
            }

            $plan[] = [
                'touch'  => $touch,
                'stage'  => $stage,
                'intent' => $intent,
                'asset'  => $asset,
            ];
        }

        return $plan;
    }
}
