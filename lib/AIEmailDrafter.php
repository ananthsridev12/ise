<?php
class AIEmailDrafter {

    public static function draft($company, $scoreData, $techStack, $service, $sender, $tone, $aiSettings, $touchNumber = 1, $priorSubject = '', $persona = null) {
        $prompt = self::buildPrompt($company, $scoreData, $techStack, $service, $sender, $tone, $aiSettings, $touchNumber, $priorSubject, $persona);

        $provider = $aiSettings['provider'] ?? 'gemini';
        $raw = '';

        if ($provider === 'gemini' && !empty($aiSettings['gemini_key'])) {
            $raw = self::callGemini($prompt, $aiSettings['gemini_key'], $aiSettings['model'] ?? '');
        } elseif ($provider === 'claude' && !empty($aiSettings['claude_key'])) {
            $raw = self::callClaude($prompt, $aiSettings['claude_key'], $aiSettings['model'] ?? '');
        } elseif ($provider === 'openai' && !empty($aiSettings['openai_key'])) {
            $raw = self::callOpenAI($prompt, $aiSettings['openai_key'], $aiSettings['model'] ?? '');
        }

        if (!$raw) {
            return array('subject' => '', 'body' => '', 'angle' => 'ai_failed', 'provider' => $provider, 'prompt' => $prompt);
        }

        $parsed = self::parseResponse($raw);
        return array(
            'subject'  => $parsed['subject'],
            'body'     => $parsed['body'],
            'angle'    => 'ai_' . $provider,
            'provider' => $provider,
            'prompt'   => $prompt,
        );
    }

    public static function testConnection($aiSettings): array {
        $provider = $aiSettings['provider'] ?? 'gemini';
        $prompt   = 'Respond with exactly the text: Connection successful.';
        $raw = '';
        if ($provider === 'gemini' && !empty($aiSettings['gemini_key'])) {
            $raw = self::callGemini($prompt, $aiSettings['gemini_key'], $aiSettings['model'] ?? '');
        } elseif ($provider === 'claude' && !empty($aiSettings['claude_key'])) {
            $raw = self::callClaude($prompt, $aiSettings['claude_key'], $aiSettings['model'] ?? '');
        } elseif ($provider === 'openai' && !empty($aiSettings['openai_key'])) {
            $raw = self::callOpenAI($prompt, $aiSettings['openai_key'], $aiSettings['model'] ?? '');
        }
        if (!$raw) {
            $keyField = $provider . '_key';
            if (empty($aiSettings[$keyField])) {
                return array('ok'=>false,'error'=>"No {$provider} API key saved.");
            }
            return array('ok'=>false,'error'=>"No response from {$provider}. Check the API key.");
        }
        return array('ok'=>true,'provider'=>$provider,'response'=>trim($raw));
    }

    private static function buildPrompt($company, $scoreData, $techStack, $service, $sender, $tone, $aiSettings, $touchNumber, $priorSubject, $persona = null) {
        $kbCompany  = DB::fetchOne('SELECT * FROM kb_company LIMIT 1') ?: array();
        $techTools  = $techStack ? implode(', ', array_column($techStack, 'tool')) : 'Not detected';
        $signalList = $scoreData['signal_types'] ? implode(', ', $scoreData['signal_types']) : 'General';
        $length     = $aiSettings['email_length'] ?? ($tone ? ($tone['email_length'] ?? 'medium') : 'medium');
        $customInstructions = $aiSettings['custom_instructions'] ?? '';

        $senderName     = $sender ? $sender['full_name']              : '[Your Name]';
        $senderTitle    = $sender ? $sender['title']                  : '[Your Title]';
        $senderCalendar = $sender ? ($sender['calendar_link'] ?? '')  : '';
        $senderStyle    = $sender ? ($sender['individual_tone'] ?? '') : '';
        $senderClosing  = $sender ? ($sender['email_closing_style'] ?? '') : '';
        $senderSig      = $sender ? ($sender['signature'] ?? '')      : '';

        $credibility = $kbCompany['credibility_statement'] ?? 'SolidPro specialises in digital transformation for manufacturers.';
        $companyName = $kbCompany['name'] ?? 'SolidPro';

        $serviceBlock = '';
        if ($service) {
            $serviceBlock = "=== SERVICE BEING PITCHED ===\n"
                . "Name: {$service['name']}\n"
                . "Problem it solves: {$service['problem_statement']}\n"
                . "What we deliver: {$service['outcomes']}\n"
                . "Why we are different: {$service['differentiators']}\n";
        }

        $personaBlock = '';
        if ($persona) {
            $personaBlock = "=== BUYER PERSONA ===\n"
                . "Persona: {$persona['name']} — {$persona['title']}\n"
                . ($persona['goals']       ? "Their goals: {$persona['goals']}\n"              : '')
                . ($persona['pain_points'] ? "Their pain points: {$persona['pain_points']}\n"  : '')
                . ($persona['email_hook']  ? "Best opening angle: {$persona['email_hook']}\n"  : '')
                . ($persona['objections']  ? "Typical objections: {$persona['objections']}\n"  : '');
        }

        $toneBlock = '';
        if ($tone) {
            $toneBlock = "=== TONE GUIDELINES ===\n"
                . "Voice: {$tone['tone_descriptors']}\n"
                . "Never sound: {$tone['anti_tone']}\n"
                . "Words to use: {$tone['words_always']}\n"
                . "Words to avoid: {$tone['words_never']}\n"
                . "Opening style: {$tone['email_opening_style']}\n"
                . "CTA style: {$tone['cta_style']}\n";
        }

        $touchBlock = '';
        if ($touchNumber > 1) {
            $touchBlock = "=== FOLLOW-UP CONTEXT ===\n"
                . "This is touch #{$touchNumber}. The prior email subject was: \"{$priorSubject}\"\n"
                . "Do NOT repeat the same opening hook. Reference the previous outreach briefly and add new value.\n";
        }

        $prompt = "You are writing a B2B cold outreach email on behalf of {$senderName}, {$senderTitle} at {$companyName}.\n\n"
            . "=== ABOUT {$companyName} ===\n{$credibility}\n\n"
            . $serviceBlock . "\n"
            . $personaBlock . "\n"
            . "=== TARGET COMPANY ===\n"
            . "Company: {$company['name']}\n"
            . "Industry: " . ($company['industry'] ?? 'Manufacturing') . "\n"
            . "Country: " . ($company['country'] ?? 'US') . "\n"
            . "Detected signals: {$signalList}\n"
            . "Top signal: " . ($scoreData['top_signal'] ?? 'business activity') . "\n"
            . "Detected tech stack: {$techTools}\n"
            . "Intent score: " . ($scoreData['score'] ?? 0) . "/100\n\n"
            . $toneBlock . "\n"
            . "=== SENDER ===\n"
            . "From: {$senderName}, {$senderTitle}\n"
            . ($senderStyle    ? "Writing style: {$senderStyle}\n"   : '')
            . ($senderClosing  ? "Closing style: {$senderClosing}\n" : '')
            . ($senderCalendar ? "Calendar link for CTA: {$senderCalendar}\n" : '')
            . ($senderSig      ? "Signature: {$senderSig}\n"         : '') . "\n"
            . $touchBlock . "\n"
            . "=== INSTRUCTIONS ===\n"
            . "Email length: {$length} (short = 3-4 sentences, medium = 2 short paragraphs, long = 3 paragraphs)\n"
            . "Write a professional cold email.\n"
            . "Use [First Name] as the only placeholder.\n"
            . "Output format: exactly two lines followed by the body:\n"
            . "SUBJECT: <subject line here>\n"
            . "BODY:\n<email body here>\n"
            . ($customInstructions ? "\nAdditional instructions: {$customInstructions}\n" : '');

        return $prompt;
    }

    private static function parseResponse($raw) {
        $raw     = trim($raw);
        $subject = '';
        $body    = $raw;

        if (preg_match('/^SUBJECT:\s*(.+)/im', $raw, $m)) {
            $subject = trim($m[1]);
        }
        if (preg_match('/^BODY:\s*([\s\S]+)/im', $raw, $m)) {
            $body = trim($m[1]);
        } elseif ($subject) {
            $body = preg_replace('/^SUBJECT:\s*.+\n?/im', '', $raw);
            $body = trim($body);
        }

        return array('subject' => $subject, 'body' => $body);
    }

    private static function callGemini($prompt, $key, $model) {
        $model = $model ?: 'gemini-1.5-flash';
        $url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
        $payload = json_encode(array(
            'contents'         => array(array('parts' => array(array('text' => $prompt)))),
            'generationConfig' => array('temperature' => 0.7, 'maxOutputTokens' => 1024),
        ));
        $ctx = stream_context_create(array('http' => array(
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 30,
        )));
        $raw  = @file_get_contents($url, false, $ctx);
        if (!$raw) return '';
        $data = json_decode($raw, true);
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    private static function callClaude($prompt, $key, $model) {
        $model = $model ?: 'claude-haiku-4-5-20251001';
        $url   = 'https://api.anthropic.com/v1/messages';
        $payload = json_encode(array(
            'model'      => $model,
            'max_tokens' => 1024,
            'messages'   => array(array('role' => 'user', 'content' => $prompt)),
        ));
        $ctx = stream_context_create(array('http' => array(
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nx-api-key: {$key}\r\nanthropic-version: 2023-06-01\r\n",
            'content' => $payload,
            'timeout' => 30,
        )));
        $raw  = @file_get_contents($url, false, $ctx);
        if (!$raw) return '';
        $data = json_decode($raw, true);
        return $data['content'][0]['text'] ?? '';
    }

    private static function callOpenAI($prompt, $key, $model) {
        $model = $model ?: 'gpt-4o-mini';
        $url   = 'https://api.openai.com/v1/chat/completions';
        $payload = json_encode(array(
            'model'       => $model,
            'messages'    => array(array('role' => 'user', 'content' => $prompt)),
            'max_tokens'  => 1024,
            'temperature' => 0.7,
        ));
        $ctx = stream_context_create(array('http' => array(
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nAuthorization: Bearer {$key}\r\n",
            'content' => $payload,
            'timeout' => 30,
        )));
        $raw  = @file_get_contents($url, false, $ctx);
        if (!$raw) return '';
        $data = json_decode($raw, true);
        return $data['choices'][0]['message']['content'] ?? '';
    }
}
