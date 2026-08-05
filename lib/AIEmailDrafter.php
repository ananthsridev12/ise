<?php
class AIEmailDrafter {

    public static function draft($company, $scoreData, $techStack, $service, $sender, $tone, $aiSettings, $touchNumber = 1, $priorSubject = '', $persona = null, $thread = null, $assets = array()) {
        $provider = $aiSettings['provider'] ?? 'gemini';

        $systemPrompt = self::buildSystemPrompt($service, $sender, $tone, $aiSettings);
        $userMessage  = self::buildUserMessage($company, $scoreData, $techStack, $touchNumber, $priorSubject, $persona, $aiSettings, $assets);

        $messages = array();
        if ($thread && !empty($thread['messages'])) {
            $messages = json_decode($thread['messages'], true) ?: array();
        }
        $messages[] = array('role' => 'user', 'content' => $userMessage);

        $raw = '';
        if ($provider === 'claude' && !empty($aiSettings['claude_key'])) {
            $raw = self::callClaude($messages, $systemPrompt, $aiSettings['claude_key'], $aiSettings['model'] ?? '');
        } elseif ($provider === 'gemini' && !empty($aiSettings['gemini_key'])) {
            $compressed = self::compressSystemPrompt($systemPrompt);
            $combined   = $compressed . "\n\n" . self::flattenMessages($messages);
            $raw = self::callGemini($combined, $aiSettings['gemini_key'], $aiSettings['model'] ?? '');
        } elseif ($provider === 'openai' && !empty($aiSettings['openai_key'])) {
            $raw = self::callOpenAI($messages, $systemPrompt, $aiSettings['openai_key'], $aiSettings['model'] ?? '');
        }

        if (!$raw || strpos($raw, 'ERROR:') === 0) {
            return array(
                'subject'       => '',
                'body'          => '',
                'angle'         => 'ai_failed',
                'provider'      => $provider,
                'prompt'        => $userMessage,
                'messages'      => json_encode($messages),
                'system_prompt' => $systemPrompt,
                'error'         => $raw ?: 'No response from AI provider.',
            );
        }

        $parsed     = self::parseResponse($raw);
        $messages[] = array('role' => 'assistant', 'content' => $raw);

        return array(
            'subject'       => $parsed['subject'],
            'body'          => $parsed['body'],
            'angle'         => 'ai_' . $provider,
            'provider'      => $provider,
            'prompt'        => $userMessage,
            'messages'      => json_encode($messages),
            'system_prompt' => $systemPrompt,
        );
    }

    public static function draftLite($company, $scoreData, $techStack, $aiSettings, $sender, $tone) {
        $provider = $aiSettings['provider'] ?? 'gemini';
        $keyField  = $provider . '_key';

        // KB company info (optional)
        $kbCompany   = DB::fetchOne('SELECT * FROM kb_company LIMIT 1') ?: array();
        $ourCompany  = $kbCompany['name'] ?? 'Our Company';
        $credibility = $kbCompany['credibility_statement'] ?? 'We help companies improve their operations.';

        // Sender fallback
        if (!$sender) {
            $sender = array('full_name' => 'Our Team', 'title' => '', 'calendar_link' => '');
        }
        $senderName     = $sender['full_name'] ?? 'Our Team';
        $senderTitle    = $sender['title'] ?? '';
        $senderCalendar = $sender['calendar_link'] ?? '';

        // Tone fallback
        $toneDescriptors = $tone ? ($tone['tone_descriptors'] ?? 'Professional, concise') : 'Professional, concise, direct.';
        $emailLength     = $aiSettings['email_length'] ?? 'medium';

        // Signals and tech
        $signalList = !empty($scoreData['signal_types']) ? implode(', ', $scoreData['signal_types']) : 'None detected';
        $techTools  = $techStack ? implode(', ', array_column($techStack, 'tool')) : 'Not detected';

        $prompt  = "You are writing a cold outreach email on behalf of {$senderName}" . ($senderTitle ? ", {$senderTitle}" : '') . " at {$ourCompany}.\n\n";
        $prompt .= "=== ABOUT US ===\n{$credibility}\n\n";
        $prompt .= "=== TARGET COMPANY ===\n";
        $prompt .= "Company: {$company['name']}, " . ($company['industry'] ?? 'Unknown industry') . ", " . ($company['country'] ?? 'Unknown') . "\n";
        $prompt .= "Detected signals: {$signalList}\n";
        $prompt .= "Detected tech stack: {$techTools}\n";
        $prompt .= "Intent score: " . ($scoreData['score'] ?? 0) . "/100\n\n";
        $prompt .= "=== TONE ===\n{$toneDescriptors}\nEmail length: {$emailLength}\n\n";
        $prompt .= "=== SENDER ===\n";
        $prompt .= "From: {$senderName}" . ($senderTitle ? ", {$senderTitle}" : '') . "\n";
        if ($senderCalendar) $prompt .= "Calendar link for CTA: {$senderCalendar}\n";
        $prompt .= "\n";
        $prompt .= "Write a {$emailLength} cold outreach email with subject line and body.\n";
        $prompt .= "Focus on the prospect's situation and signals. Do not fabricate specific service names or case studies.\n";
        $prompt .= "No placeholders except [First Name].\n\n";
        $prompt .= "=== OUTPUT FORMAT ===\n";
        $prompt .= "Always output exactly:\nSUBJECT: <subject line>\nBODY:\n<email body>\n";

        $custom = $aiSettings['custom_instructions'] ?? '';
        if ($custom) $prompt .= "\nAdditional instructions: {$custom}\n";

        $raw = '';
        if ($provider === 'claude' && !empty($aiSettings['claude_key'])) {
            $raw = self::callClaude(
                array(array('role' => 'user', 'content' => $prompt)),
                '',
                $aiSettings['claude_key'],
                $aiSettings['model'] ?? ''
            );
        } elseif ($provider === 'gemini' && !empty($aiSettings['gemini_key'])) {
            $raw = self::callGemini($prompt, $aiSettings['gemini_key'], $aiSettings['model'] ?? '');
        } elseif ($provider === 'openai' && !empty($aiSettings['openai_key'])) {
            $raw = self::callOpenAI(
                array(array('role' => 'user', 'content' => $prompt)),
                '',
                $aiSettings['openai_key'],
                $aiSettings['model'] ?? ''
            );
        }

        if (!$raw || strpos($raw, 'ERROR:') === 0) {
            return array(
                'subject'        => '',
                'body'           => '',
                'angle'          => 'ai_failed',
                'provider'       => $provider,
                'prompt_context' => $prompt,
                'error'          => $raw ?: 'No response from AI provider.',
            );
        }

        $parsed = self::parseResponse($raw);

        return array(
            'subject'        => $parsed['subject'],
            'body'           => $parsed['body'],
            'angle'          => 'ai_' . $provider,
            'provider'       => $provider,
            'prompt_context' => $prompt,
        );
    }

    public static function refine($draft, string $instructions, $aiSettings, $thread = null): array {
        $provider = $aiSettings['provider'] ?? 'gemini';

        $refinePrompt = "Here is an existing email draft:\n\n"
            . "SUBJECT: {$draft['subject']}\nBODY:\n{$draft['body']}\n\n"
            . "Refinement instructions: {$instructions}\n\n"
            . "Rewrite the email applying these instructions. Keep the same audience and intent.\n"
            . "Output format:\nSUBJECT: <subject>\nBODY:\n<body>";

        $raw = '';
        if ($provider === 'claude' && !empty($aiSettings['claude_key'])) {
            $raw = self::callClaude(
                array(array('role' => 'user', 'content' => $refinePrompt)),
                '',
                $aiSettings['claude_key'],
                $aiSettings['model'] ?? ''
            );
        } elseif ($provider === 'gemini' && !empty($aiSettings['gemini_key'])) {
            $raw = self::callGemini($refinePrompt, $aiSettings['gemini_key'], $aiSettings['model'] ?? '');
        } elseif ($provider === 'openai' && !empty($aiSettings['openai_key'])) {
            $raw = self::callOpenAI(
                array(array('role' => 'user', 'content' => $refinePrompt)),
                '',
                $aiSettings['openai_key'],
                $aiSettings['model'] ?? ''
            );
        }

        if (!$raw || strpos($raw, 'ERROR:') === 0) {
            return array(
                'subject'  => '',
                'body'     => '',
                'angle'    => 'ai_error',
                'provider' => $provider,
                'error'    => $raw ?: 'No response from AI provider.',
            );
        }

        $parsed = self::parseResponse($raw);
        return array(
            'subject'  => $parsed['subject'],
            'body'     => $parsed['body'],
            'provider' => $provider,
        );
    }

    public static function testConnection($aiSettings): array {
        $provider = $aiSettings['provider'] ?? 'gemini';
        $keyField = $provider . '_key';

        if (empty($aiSettings[$keyField])) {
            return array('ok' => false, 'error' => "No {$provider} API key saved. Add it in Settings and save first.");
        }

        $model = $aiSettings['model'] ?? '';

        if ($provider === 'gemini') {
            $model   = $model ?: 'gemini-2.0-flash';
            $url     = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$aiSettings['gemini_key']}";
            $payload = json_encode(array(
                'contents'         => array(array('parts' => array(array('text' => 'Respond with exactly: Connection successful.')))),
                'generationConfig' => array('maxOutputTokens' => 50),
            ));
            $ctx = stream_context_create(array('http' => array(
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\n",
                'content'       => $payload,
                'timeout'       => 30,
                'ignore_errors' => true,
            )));
            $raw = @file_get_contents($url, false, $ctx);
            if ($raw === false || $raw === '') {
                return array('ok' => false, 'error' => 'No response from Gemini. The server may be blocking outbound HTTPS, or the API key is invalid. Try Claude or OpenAI instead.');
            }
            $data = json_decode($raw, true);
            if (isset($data['error'])) {
                return array('ok' => false, 'error' => 'Gemini API error: ' . ($data['error']['message'] ?? json_encode($data['error'])));
            }
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if (!$text) return array('ok' => false, 'error' => 'Gemini returned empty content. Model: ' . $model);
            return array('ok' => true, 'provider' => 'gemini', 'response' => trim($text));
        }

        if ($provider === 'claude') {
            $model   = $model ?: 'claude-haiku-4-5-20251001';
            $url     = 'https://api.anthropic.com/v1/messages';
            $payload = json_encode(array(
                'model'      => $model,
                'max_tokens' => 50,
                'messages'   => array(array('role' => 'user', 'content' => 'Respond with exactly: Connection successful.')),
            ));
            $ctx = stream_context_create(array('http' => array(
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\nx-api-key: {$aiSettings['claude_key']}\r\nanthropic-version: 2023-06-01\r\n",
                'content'       => $payload,
                'timeout'       => 30,
                'ignore_errors' => true,
            )));
            $raw = @file_get_contents($url, false, $ctx);
            if ($raw === false || $raw === '') {
                return array('ok' => false, 'error' => 'No response from Claude. Server may be blocking outbound HTTPS.');
            }
            $data = json_decode($raw, true);
            if (isset($data['error'])) {
                return array('ok' => false, 'error' => 'Claude API error: ' . ($data['error']['message'] ?? json_encode($data['error'])));
            }
            $text = $data['content'][0]['text'] ?? '';
            if (!$text) return array('ok' => false, 'error' => 'Claude returned empty content. Model: ' . $model);
            return array('ok' => true, 'provider' => 'claude', 'response' => trim($text));
        }

        if ($provider === 'openai') {
            $model   = $model ?: 'gpt-4o-mini';
            $url     = 'https://api.openai.com/v1/chat/completions';
            $payload = json_encode(array(
                'model'      => $model,
                'max_tokens' => 50,
                'messages'   => array(array('role' => 'user', 'content' => 'Respond with exactly: Connection successful.')),
            ));
            $ctx = stream_context_create(array('http' => array(
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\nAuthorization: Bearer {$aiSettings['openai_key']}\r\n",
                'content'       => $payload,
                'timeout'       => 30,
                'ignore_errors' => true,
            )));
            $raw = @file_get_contents($url, false, $ctx);
            if ($raw === false || $raw === '') {
                return array('ok' => false, 'error' => 'No response from OpenAI. Server may be blocking outbound HTTPS.');
            }
            $data = json_decode($raw, true);
            if (isset($data['error'])) {
                return array('ok' => false, 'error' => 'OpenAI API error: ' . ($data['error']['message'] ?? json_encode($data['error'])));
            }
            $text = $data['choices'][0]['message']['content'] ?? '';
            if (!$text) return array('ok' => false, 'error' => 'OpenAI returned empty content. Model: ' . $model);
            return array('ok' => true, 'provider' => 'openai', 'response' => trim($text));
        }

        return array('ok' => false, 'error' => 'Unknown provider: ' . $provider);
    }

    public static function buildSystemPromptPublic($company, $scoreData, $techStack, $service, $sender, $tone, $aiSettings, $touchNumber, $priorSubject, $persona, $thread, $assets) {
        return self::buildSystemPrompt($service, $sender, $tone, $aiSettings);
    }

    public static function buildUserMessagePublic($company, $scoreData, $techStack, $service, $sender, $tone, $aiSettings, $touchNumber, $priorSubject, $persona, $thread, $assets) {
        return self::buildUserMessage($company, $scoreData, $techStack, $touchNumber, $priorSubject, $persona, $aiSettings, $assets);
    }

    private static function buildSystemPrompt($service, $sender, $tone, $aiSettings) {
        $kbCompany   = DB::fetchOne('SELECT * FROM kb_company LIMIT 1') ?: array();
        $companyName = $kbCompany['name'] ?? 'SolidPro';
        $credibility = $kbCompany['credibility_statement'] ?? 'SolidPro specialises in digital transformation for manufacturers.';

        $senderName     = $sender ? $sender['full_name']              : '[Your Name]';
        $senderTitle    = $sender ? $sender['title']                  : '[Your Title]';
        $senderCalendar = $sender ? ($sender['calendar_link'] ?? '')  : '';
        $senderStyle    = $sender ? ($sender['individual_tone'] ?? '') : '';
        $senderClosing  = $sender ? ($sender['email_closing_style'] ?? '') : '';
        $senderSig      = $sender ? ($sender['signature'] ?? '')      : '';

        $p  = "You are writing B2B cold outreach emails on behalf of {$senderName}, {$senderTitle} at {$companyName}.\n\n";
        $p .= "=== ABOUT {$companyName} ===\n{$credibility}\n\n";

        if ($service) {
            $p .= "=== SERVICE BEING PITCHED ===\n"
                . "Name: {$service['name']}\n"
                . "Problem it solves: {$service['problem_statement']}\n"
                . "What we deliver: {$service['outcomes']}\n"
                . "Why we are different: {$service['differentiators']}\n\n";
        }

        if ($tone) {
            $p .= "=== TONE GUIDELINES ===\n"
                . "Voice: {$tone['tone_descriptors']}\n"
                . "Never sound: {$tone['anti_tone']}\n"
                . "Words to use: {$tone['words_always']}\n"
                . "Words to avoid: {$tone['words_never']}\n"
                . "Opening style: {$tone['email_opening_style']}\n"
                . "CTA style: {$tone['cta_style']}\n\n";
        }

        $p .= "=== SENDER ===\n"
            . "From: {$senderName}, {$senderTitle}\n"
            . ($senderStyle    ? "Writing style: {$senderStyle}\n"   : '')
            . ($senderClosing  ? "Closing style: {$senderClosing}\n"  : '')
            . ($senderCalendar ? "Calendar link for CTA: {$senderCalendar}\n" : '')
            . ($senderSig      ? "Signature: {$senderSig}\n"         : '') . "\n";

        $p .= "=== OUTPUT FORMAT ===\n"
            . "Always output exactly:\nSUBJECT: <subject line>\nBODY:\n<email body>\n"
            . "Use [First Name] as the only placeholder.\n";

        $custom = $aiSettings['custom_instructions'] ?? '';
        if ($custom) $p .= "\nAdditional instructions: {$custom}\n";

        return $p;
    }

    private static function compressSystemPrompt(string $systemPrompt): string {
        // For Gemini, build a trimmed version to stay within token limits
        $lines = explode("\n", $systemPrompt);
        $out   = array();
        $skip  = false;
        $skipSections = array('=== TONE GUIDELINES ===', '=== SENDER ===', '=== OUTPUT FORMAT ===');
        $keepSections = array('You are writing', '=== ABOUT', '=== SERVICE BEING PITCHED ===');

        // Simple approach: keep everything but truncate to 1500 chars
        $compressed = implode("\n", $lines);
        if (strlen($compressed) > 1500) {
            $compressed = substr($compressed, 0, 1500) . "\n[truncated for brevity]";
        }
        return $compressed;
    }

    private static function buildUserMessage($company, $scoreData, $techStack, $touchNumber, $priorSubject, $persona, $aiSettings, $assets = array()) {
        $techTools  = $techStack ? implode(', ', array_column($techStack, 'tool')) : 'Not detected';
        $signalList = $scoreData['signal_types'] ? implode(', ', $scoreData['signal_types']) : 'General';
        $length     = $aiSettings['email_length'] ?? 'medium';

        $msg  = "=== TARGET COMPANY ===\n"
              . "Company: {$company['name']}\n"
              . "Industry: " . ($company['industry'] ?? 'Manufacturing') . "\n"
              . "Country: " . ($company['country'] ?? 'US') . "\n"
              . "Detected signals: {$signalList}\n"
              . "Top signal: " . ($scoreData['top_signal'] ?? 'business activity') . "\n"
              . "Detected tech stack: {$techTools}\n"
              . "Intent score: " . ($scoreData['score'] ?? 0) . "/100\n\n";

        if ($persona) {
            $msg .= "=== BUYER PERSONA ===\n"
                . "{$persona['name']} - {$persona['title']}\n"
                . (!empty($persona['goals'])       ? "Their goals: {$persona['goals']}\n"             : '')
                . (!empty($persona['pain_points']) ? "Their pain points: {$persona['pain_points']}\n" : '')
                . (!empty($persona['email_hook'])  ? "Best opening angle: {$persona['email_hook']}\n" : '')
                . (!empty($persona['objections'])  ? "Typical objections: {$persona['objections']}\n" : '')
                . "\n";
        }

        if (!empty($assets)) {
            $msg .= "=== RELEVANT ASSETS TO OFFER ===\n";
            foreach ($assets as $a) {
                $msg .= "- {$a['name']} ({$a['category']}): {$a['cta_text']} -> {$a['url']}\n";
            }
            $msg .= "Include the most relevant asset naturally in the email if appropriate.\n\n";
        }

        if ($touchNumber > 1 && $priorSubject) {
            $msg .= "=== FOLLOW-UP CONTEXT ===\n"
                . "This is touch #{$touchNumber}. Prior email subject: \"{$priorSubject}\"\n"
                . "Do NOT repeat the same opening hook. Reference previous outreach briefly, add new value.\n\n";
        }

        $msg .= "Write a {$length} cold email. Length guide: short = 3-4 sentences, medium = 2 short paragraphs, long = 3 paragraphs.\n";
        return $msg;
    }

    private static function flattenMessages($messages) {
        $out = '';
        foreach ($messages as $m) {
            $label = $m['role'] === 'assistant' ? 'Prior email written' : 'New task';
            $out  .= "--- {$label} ---\n{$m['content']}\n\n";
        }
        return trim($out);
    }

    private static function parseResponse($raw) {
        $raw     = trim($raw);
        $subject = '';
        $body    = $raw;
        if (preg_match('/^SUBJECT:\s*(.+)/im', $raw, $m)) $subject = trim($m[1]);
        if (preg_match('/^BODY:\s*([\s\S]+)/im', $raw, $m)) {
            $body = trim($m[1]);
        } elseif ($subject) {
            $body = trim(preg_replace('/^SUBJECT:\s*.+\n?/im', '', $raw));
        }
        return array('subject' => $subject, 'body' => $body);
    }

    private static function callGemini($prompt, $key, $model) {
        $model   = $model ?: 'gemini-2.0-flash';
        $url     = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
        $payload = json_encode(array(
            'contents'         => array(array('parts' => array(array('text' => $prompt)))),
            'generationConfig' => array('temperature' => 0.7, 'maxOutputTokens' => 2048),
        ));
        $ctx = stream_context_create(array('http' => array(
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n",
            'content'       => $payload,
            'timeout'       => 30,
            'ignore_errors' => true,
        )));
        $raw  = @file_get_contents($url, false, $ctx);
        if ($raw === false || $raw === '') return 'ERROR: No response from Gemini API.';
        $data = json_decode($raw, true);
        if (isset($data['error'])) return 'ERROR: Gemini API error: ' . ($data['error']['message'] ?? json_encode($data['error']));
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (!$text) return 'ERROR: Gemini returned empty content.';
        return $text;
    }

    private static function callClaude($messages, $systemPrompt, $key, $model) {
        $model   = $model ?: 'claude-haiku-4-5-20251001';
        $url     = 'https://api.anthropic.com/v1/messages';
        $payload = array(
            'model'      => $model,
            'max_tokens' => 1024,
            'messages'   => $messages,
        );
        if ($systemPrompt) {
            $payload['system'] = array(
                array('type' => 'text', 'text' => $systemPrompt, 'cache_control' => array('type' => 'ephemeral'))
            );
        }
        $ctx = stream_context_create(array('http' => array(
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n"
                             . "x-api-key: {$key}\r\n"
                             . "anthropic-version: 2023-06-01\r\n"
                             . "anthropic-beta: prompt-caching-2024-07-31\r\n",
            'content'       => json_encode($payload),
            'timeout'       => 30,
            'ignore_errors' => true,
        )));
        $raw  = @file_get_contents($url, false, $ctx);
        if ($raw === false || $raw === '') return 'ERROR: No response from Claude API.';
        $data = json_decode($raw, true);
        if (isset($data['error'])) return 'ERROR: Claude API error: ' . ($data['error']['message'] ?? json_encode($data['error']));
        $text = $data['content'][0]['text'] ?? '';
        if (!$text) return 'ERROR: Claude returned empty content.';
        return $text;
    }

    private static function callOpenAI($messages, $systemPrompt, $key, $model) {
        $model       = $model ?: 'gpt-4o-mini';
        $url         = 'https://api.openai.com/v1/chat/completions';
        $allMessages = array();
        if ($systemPrompt) $allMessages[] = array('role' => 'system', 'content' => $systemPrompt);
        foreach ($messages as $m) $allMessages[] = $m;
        $payload = json_encode(array(
            'model'       => $model,
            'messages'    => $allMessages,
            'max_tokens'  => 1024,
            'temperature' => 0.7,
        ));
        $ctx = stream_context_create(array('http' => array(
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\nAuthorization: Bearer {$key}\r\n",
            'content'       => $payload,
            'timeout'       => 30,
            'ignore_errors' => true,
        )));
        $raw  = @file_get_contents($url, false, $ctx);
        if ($raw === false || $raw === '') return 'ERROR: No response from OpenAI API.';
        $data = json_decode($raw, true);
        if (isset($data['error'])) return 'ERROR: OpenAI API error: ' . ($data['error']['message'] ?? json_encode($data['error']));
        $text = $data['choices'][0]['message']['content'] ?? '';
        if (!$text) return 'ERROR: OpenAI returned empty content.';
        return $text;
    }
}
