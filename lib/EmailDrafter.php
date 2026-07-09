<?php
class EmailDrafter {
    public static function draft(array $company, array $scoreData, array $techStack): array {
        $name    = $company['name'];
        $signal  = $scoreData['top_signal'] ?? 'recent activity';
        $types   = $scoreData['signal_types'] ?? [];
        $tools   = array_column($techStack, 'tool');
        $priority = $scoreData['priority'];

        $angle = self::pickAngle($types, $tools, $signal);
        $subject = self::buildSubject($name, $angle, $types);
        $body    = self::buildBody($name, $angle, $tools, $signal, $company['industry'] ?? '');

        return ['subject' => $subject, 'body' => $body, 'angle' => $angle, 'priority' => $priority];
    }

    private static function pickAngle(array $types, array $tools, string $signal): string {
        $legacyERP = array_intersect($tools, ['SAP ECC','Oracle EBS','Dynamics AX','JD Edwards','Infor LN','Infor M3']);
        if ($legacyERP && in_array('M&A', $types)) return 'post_merger_consolidation';
        if ($legacyERP && in_array('Expansion', $types)) return 'legacy_erp_expansion';
        if ($legacyERP) return 'legacy_erp_migration';
        if (array_intersect($tools, ['SAP CPQ','Salesforce CPQ','Oracle CPQ']) && in_array('Contract', $types)) return 'cpq_new_contract';
        if (in_array('M&A', $types)) return 'merger_integration';
        if (in_array('ERP', $types)) return 'erp_implementation';
        if (in_array('Digital Transformation', $types)) return 'digital_transformation';
        if (in_array('Expansion', $types)) return 'greenfield_expansion';
        if (in_array('Hiring', $types)) return 'manufacturing_hiring';
        if (in_array('Contract', $types)) return 'new_contract_win';
        return 'general_outreach';
    }

    private static function buildSubject(string $name, string $angle, array $types): string {
        $subjects = [
            'post_merger_consolidation' => "Helping $name unify systems post-merger",
            'legacy_erp_expansion'      => "$name's expansion — is your ERP ready to scale?",
            'legacy_erp_migration'      => "Modernizing $name's ERP landscape",
            'cpq_new_contract'          => "Accelerating $name's quote-to-cash after new contract win",
            'merger_integration'        => "Supporting $name's integration journey",
            'erp_implementation'        => "Ensuring $name's ERP rollout succeeds",
            'digital_transformation'    => "$name's digital transformation — a conversation worth having",
            'greenfield_expansion'      => "Building the right tech foundation for $name's new facility",
            'manufacturing_hiring'      => "Scaling $name's manufacturing ops with the right tools",
            'new_contract_win'          => "Helping $name deliver on their new contract",
            'general_outreach'          => "A conversation about $name's operational excellence",
        ];
        return $subjects[$angle] ?? "Following up with $name";
    }

    private static function buildBody(string $name, string $angle, array $tools, string $signal, string $industry): string {
        $toolStr = $tools ? implode(', ', array_slice($tools, 0, 3)) : '';
        $toolLine = $toolStr ? "Given your current environment ($toolStr), " : '';
        $intros = [
            'post_merger_consolidation' => "Congratulations on {$name}'s recent merger/acquisition. Post-merger ERP consolidation is one of the most complex integration challenges — duplicate data, mismatched BOMs, and disconnected workflows can slow your teams down for months.",
            'legacy_erp_expansion'      => "We noticed {$name} is expanding operations. {$toolLine}scaling manufacturing on a legacy ERP often creates bottlenecks that limit how fast you can ramp the new site.",
            'legacy_erp_migration'      => "{$toolLine}many manufacturers running legacy ERP are now facing pressure to modernize — whether for real-time visibility, cloud scalability, or compliance.",
            'cpq_new_contract'          => "Congratulations on {$name}'s new contract win! {$toolLine}the next 90 days are critical for quoting, scheduling, and delivery precision.",
            'merger_integration'        => "We saw the news about {$name}'s recent deal. Integration projects often surface gaps in ERP connectivity, data quality, and process alignment.",
            'erp_implementation'        => "ERP implementations are high-stakes. We've helped manufacturers in the {$industry} space navigate rollouts without disrupting production.",
            'digital_transformation'    => "{$name}'s digital transformation journey puts you in good company — and in a critical window where the right integration decisions compound over time.",
            'greenfield_expansion'      => "{$name}'s new facility is a rare greenfield opportunity to get the tech stack right from day one — no legacy debt, no workarounds.",
            'manufacturing_hiring'      => "We noticed {$name} is actively building out its manufacturing and technology teams. That level of hiring often signals operational scale-up — and the systems need to keep pace.",
            'new_contract_win'          => "{$name}'s new contract win is great news. Delivering on it on time and on margin requires tight coordination between quoting, planning, and shop floor execution.",
            'general_outreach'          => "We've been following {$name}'s recent activity and see strong alignment with what SolidPro's digital transformation services deliver.",
        ];
        $intro = $intros[$angle] ?? $intros['general_outreach'];
        return "Hi [First Name],\n\n{$intro}\n\nAt SolidPro, we specialize in ERP implementation, CPQ optimization, and digital transformation for manufacturers. We've helped companies like yours cut implementation timelines by 30% and reduce quote errors by over 60%.\n\nI'd love to share a few specific ideas relevant to {$name}'s situation — no pitch deck, just a 20-minute conversation.\n\nAre you available for a quick call this week or next?\n\nBest regards,\n[Your Name]\nSolidPro | [Your Phone]";
    }
}
