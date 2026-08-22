<?php
/** Google Gemini API — https://ai.google.dev/api/generate-content */
class GeminiClient
{
    public static function apiKey(): string { return trim(setting('gemini_api_key')); }
    public static function model(): string { return trim(setting('gemini_model', 'gemini-2.0-flash')); }
    public static function isConfigured(): bool { return self::apiKey() !== ''; }

    /**
     * @param string $prompt user/task prompt
     * @param string $system optional system instruction (persona + rules)
     * @return array{ok:bool, text?:string, error?:string}
     */
    public static function generate(string $prompt, string $system = ''): array {
        if (!self::isConfigured()) {
            return ['ok' => false, 'error' => 'No Gemini API key set. Add one in Settings > API Keys (starts with "AIza...").'];
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode(self::model())
             . ':generateContent?key=' . rawurlencode(self::apiKey());

        $payload = ['contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]]];
        if ($system !== '') {
            $payload['systemInstruction'] = ['parts' => [['text' => $system]]];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false) return ['ok' => false, 'error' => 'Network error: ' . $err];

        $json = json_decode($body, true);
        if (!empty($json['error'])) {
            return ['ok' => false, 'error' => $json['error']['message'] ?? 'Gemini API error'];
        }
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($text === null) return ['ok' => false, 'error' => 'Empty response from Gemini.'];
        return ['ok' => true, 'text' => $text];
    }
}
