<?php
class Scorer {
    private static $SIGNAL_KEYWORDS = [
        'merger' => 20, 'acquisition' => 20, 'acquires' => 20, 'acquired' => 20,
        'expansion' => 15, 'expands' => 15, 'new plant' => 18, 'new facility' => 15,
        'digital transformation' => 18, 'erp implementation' => 20, 'sap implementation' => 20,
        'cloud migration' => 15, 'iot' => 12, 'automation' => 12, 'smart factory' => 15,
        'new contract' => 15, 'awarded contract' => 18, 'joint venture' => 15,
        'hiring' => 8, 'recruitment' => 8, 'talent acquisition' => 8,
        'ipo' => 15, 'funding' => 12, 'investment' => 10,
    ];

    private static $TECH_BOOST = [
        'SAP ECC' => 15, 'Oracle EBS' => 15, 'Dynamics AX' => 12, 'JD Edwards' => 12,
        'Infor LN' => 10, 'Infor M3' => 10, 'Epicor' => 10,
        'SAP CPQ' => 8, 'Salesforce CPQ' => 8, 'Oracle CPQ' => 8,
    ];

    public static function score($signals, $techStack, $company) {
        $signalScore = 0;
        $topSignal = null;
        $signalTypes = [];

        foreach ($signals as $sig) {
            $text = strtolower($sig['title'] . ' ' . $sig['snippet']);
            foreach (self::$SIGNAL_KEYWORDS as $kw => $weight) {
                if (strpos($text, $kw) !== false) {
                    $signalScore += $weight;
                    $signalTypes[] = self::classifySignal($kw);
                    if (!$topSignal) $topSignal = $kw;
                    break;
                }
            }
        }
        $signalScore = min(100, $signalScore);

        $freshCount = 0;
        foreach ($signals as $sig) {
            $ts = strtotime(isset($sig['published_date']) ? $sig['published_date'] : '');
            if ($ts && (time() - $ts) < 7 * 86400) $freshCount++;
        }
        $freshnessScore = min(100, $freshCount * 20);

        $techBoost = 0;
        $detectedTools = array_column($techStack, 'tool');
        foreach ($detectedTools as $tool) {
            $techBoost += isset(self::$TECH_BOOST[$tool]) ? self::$TECH_BOOST[$tool] : 0;
        }
        $techBoost = min(25, $techBoost);

        $volumeScore = min(100, count($signals) * 10);

        $final = (int) round(
            $signalScore    * 0.40 +
            $freshnessScore * 0.20 +
            $volumeScore    * 0.15 +
            $techBoost      * 0.25
        );
        $final = max(0, min(100, $final));
        $priority = $final >= 70 ? 'High' : ($final >= 40 ? 'Medium' : 'Low');

        return [
            'score'          => $final,
            'priority'       => $priority,
            'signal_score'   => $signalScore,
            'freshness_score'=> $freshnessScore,
            'tech_boost'     => $techBoost,
            'volume_score'   => $volumeScore,
            'top_signal'     => $topSignal,
            'signal_types'   => array_unique($signalTypes),
            'signal_count'   => count($signals),
        ];
    }

    private static function classifySignal($kw) {
        $map = [
            'merger'=>'M&A','acquisition'=>'M&A','acquires'=>'M&A','acquired'=>'M&A',
            'expansion'=>'Expansion','expands'=>'Expansion','new plant'=>'Expansion','new facility'=>'Expansion',
            'digital transformation'=>'Digital Transformation','erp implementation'=>'ERP',
            'sap implementation'=>'ERP','cloud migration'=>'Cloud',
            'iot'=>'Technology','automation'=>'Technology','smart factory'=>'Technology',
            'new contract'=>'Contract','awarded contract'=>'Contract',
            'joint venture'=>'Partnership','hiring'=>'Hiring','recruitment'=>'Hiring',
            'ipo'=>'Funding','funding'=>'Funding','investment'=>'Funding',
        ];
        return isset($map[$kw]) ? $map[$kw] : 'Other';
    }
}
