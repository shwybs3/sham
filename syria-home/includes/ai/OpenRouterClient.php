<?php
/** OpenRouter — free-tier fallback content generator when no Gemini key is set. */
class OpenRouterClient
{
    const FREE_MODELS = [
        'meta-llama/llama-3.3-70b-instruct:free',
        'google/gemini-2.0-flash-exp:free',
        'deepseek/deepseek-chat-v3.1:free',
        'openrouter/auto',
    ];

    public static function apiKey(): string { return trim(setting('openrouter_api_key')); }
    public static function isConfigured(): bool { return self::apiKey() !== ''; }

    public static function generate(string $prompt, string $system = ''): array {
        if (!self::isConfigured()) {
            return ['ok' => false, 'error' => 'No OpenRouter API key set. Add one in Settings > API Keys, or add a Gemini key instead.'];
        }

        $messages = [];
        if ($system !== '') $messages[] = ['role' => 'system', 'content' => $system];
        $messages[] = ['role' => 'user', 'content' => $prompt];

        foreach (self::FREE_MODELS as $model) {
            $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['model' => $model, 'messages' => $messages]),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . self::apiKey(),
                    'Content-Type: application/json',
                    'HTTP-Referer: ' . SITE_URL,
                    'X-Title: Syria Home',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 45,
            ]);
            $body = curl_exec($ch);
            curl_close($ch);
            $json = json_decode((string)$body, true);
            $text = $json['choices'][0]['message']['content'] ?? null;
            if ($text) return ['ok' => true, 'text' => $text, 'model' => $model];
        }
        return ['ok' => false, 'error' => 'All free OpenRouter models failed or are rate-limited right now.'];
    }
}
