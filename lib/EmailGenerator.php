<?php
/**
 * Shared email generation logic used by both api/generate_email.php and api/enrich.php.
 * Handles KB lookup, service matching, persona selection, thread management, and DB inserts.
 */
class EmailGenerator {

    public static function detectMode(int $tenantId): string {
        $vCount = DB::fetchOne('SELECT COUNT(*) as c FROM kb_verticals WHERE tenant_id = ?', [$tenantId]);
        $sCount = DB::fetchOne('SELECT COUNT(*) as c FROM kb_services WHERE tenant_id = ?', [$tenantId]);
        return ($vCount['c'] > 0 && $sCount['c'] > 0) ? 'full' : 'lite';
    }

    public static function generate($companyId, $touchNumber = 1, $senderId = 0) {
        $company = DB::fetchOne('SELECT * FROM companies WHERE id = ?', array($companyId));
        if (!$company) return array('ok' => false, 'error' => 'Company not found');

        $tenantId   = $company['tenant_id'] ?? null;

        $aiSettings = DB::fetchOne('SELECT * FROM ai_settings LIMIT 1') ?: array();
        $provider   = $aiSettings['provider'] ?? 'gemini';
        $keyField   = $provider . '_key';
        if (empty($aiSettings[$keyField])) {
            return array('ok' => false, 'error' => "No {$provider} API key configured. Add it at /settings.php");
        }

        $tone   = DB::fetchOne('SELECT * FROM kb_tone LIMIT 1');
        $sender = null;
        if ($senderId) $sender = DB::fetchOne('SELECT * FROM kb_senders WHERE id = ?', array($senderId));
        if (!$sender)  $sender = DB::fetchOne('SELECT * FROM kb_senders WHERE is_default = 1 LIMIT 1');
        if (!$sender)  $sender = DB::fetchOne('SELECT * FROM kb_senders ORDER BY id LIMIT 1');

        $techStack   = DB::fetchAll('SELECT * FROM company_tech WHERE company_id = ? ORDER BY confidence DESC', array($companyId)) ?: array();
        $signalTypes = array();
        if ($company['signal_types']) {
            $signalTypes = array_values(array_filter(array_map('trim', explode(',', $company['signal_types']))));
        }

        $scoreData = array(
            'score'        => $company['score'] ?? 0,
            'priority'     => $company['priority'] ?? 'Low',
            'signal_types' => $signalTypes,
            'top_signal'   => $company['top_signal'] ?? '',
            'signal_count' => $company['signal_count'] ?? 0,
        );

        // Detect mode: lite vs full
        $mode = $tenantId ? self::detectMode((int)$tenantId) : 'lite';

        if ($mode === 'lite') {
            // Lite mode — no KB service matching required
            $email = AIEmailDrafter::draftLite($company, $scoreData, $techStack, $aiSettings, $sender, $tone);

            $draftData = array(
                'company_id'         => $companyId,
                'subject'            => $email['subject'],
                'body'               => $email['body'],
                'angle'              => $email['angle'],
                'touch_number'       => $touchNumber,
                'ai_provider'        => $email['provider'],
                'matched_service_id' => null,
                'prompt_context'     => substr($email['prompt_context'], 0, 65535),
                'generation_mode'    => 'lite',
            );
            try {
                DB::insert('email_drafts', $draftData);
            } catch (Exception $e) {
                unset($draftData['generation_mode']);
                DB::insert('email_drafts', $draftData);
            }

            return array(
                'ok'              => true,
                'subject'         => $email['subject'],
                'body'            => $email['body'],
                'provider'        => $email['provider'],
                'matched_service' => null,
                'persona'         => null,
                'touch_number'    => $touchNumber,
                'generation_mode' => 'lite',
            );
        }

        // Full mode — existing KBMatcher + AIEmailDrafter flow
        $service = KBMatcher::matchService($signalTypes, $techStack, $company['industry']);

        $persona = null;
        if ($service) {
            $persona = DB::fetchOne(
                'SELECT * FROM kb_personas WHERE service_id = ? ORDER BY FIELD(decision_role,"Economic Buyer","Champion","Technical Buyer","End User","Influencer","Blocker") LIMIT 1',
                array($service['id'])
            );
            if (!$persona && !empty($service['vertical_id'])) {
                $persona = DB::fetchOne(
                    'SELECT * FROM kb_personas WHERE vertical_id = ? ORDER BY FIELD(decision_role,"Economic Buyer","Champion","Technical Buyer","End User","Influencer","Blocker") LIMIT 1',
                    array($service['vertical_id'])
                );
            }
        }

        // Load existing conversation thread for this company
        $thread = null;
        try {
            $thread = DB::fetchOne('SELECT * FROM email_threads WHERE company_id = ? ORDER BY id DESC LIMIT 1', array($companyId));
        } catch (Exception $e) { /* table may not exist yet — graceful skip */ }

        $priorSubject = '';
        if ($touchNumber > 1) {
            $prior = DB::fetchOne(
                'SELECT subject FROM email_drafts WHERE company_id = ? AND touch_number = ? ORDER BY id DESC LIMIT 1',
                array($companyId, $touchNumber - 1)
            );
            $priorSubject = $prior ? $prior['subject'] : '';
        }

        $email = AIEmailDrafter::draft(
            $company, $scoreData, $techStack, $service, $sender, $tone,
            $aiSettings, $touchNumber, $priorSubject, $persona, $thread
        );

        // Save or update conversation thread
        $threadId = $thread ? (int)$thread['id'] : null;
        try {
            if ($threadId) {
                DB::update('email_threads',
                    array('messages' => $email['messages'], 'updated_at' => date('Y-m-d H:i:s')),
                    'id = ?', array($threadId)
                );
            } else {
                $threadId = DB::insert('email_threads', array(
                    'company_id'    => $companyId,
                    'service_id'    => $service ? $service['id'] : null,
                    'sender_id'     => $sender  ? $sender['id']  : null,
                    'provider'      => $email['provider'],
                    'model'         => $aiSettings['model'] ?? null,
                    'system_prompt' => $email['system_prompt'],
                    'messages'      => $email['messages'],
                ));
            }
        } catch (Exception $e) {
            $threadId = null; // email_threads may not exist; continue without threading
        }

        // Insert email draft — with fallback if new columns don't exist yet
        $draftData = array(
            'company_id'         => $companyId,
            'subject'            => $email['subject'],
            'body'               => $email['body'],
            'angle'              => $email['angle'],
            'touch_number'       => $touchNumber,
            'ai_provider'        => $email['provider'],
            'matched_service_id' => $service ? $service['id'] : null,
            'prompt_context'     => substr($email['prompt'], 0, 65535),
            'thread_id'          => $threadId,
            'generation_mode'    => 'full',
        );
        try {
            DB::insert('email_drafts', $draftData);
        } catch (Exception $e) {
            // Fallback: insert without new columns in case migrations haven't run yet
            unset($draftData['thread_id']);
            unset($draftData['generation_mode']);
            DB::insert('email_drafts', $draftData);
        }

        return array(
            'ok'              => true,
            'subject'         => $email['subject'],
            'body'            => $email['body'],
            'provider'        => $email['provider'],
            'matched_service' => $service ? $service['name'] : null,
            'persona'         => $persona ? $persona['name']  : null,
            'touch_number'    => $touchNumber,
            'generation_mode' => 'full',
        );
    }
}
