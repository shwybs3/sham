<?php
/** Picks Gemini if configured, else falls back to OpenRouter's free models. */
class AIRouter
{
    public static function generate(string $prompt, string $system = ''): array {
        if (GeminiClient::isConfigured()) {
            $r = GeminiClient::generate($prompt, $system);
            if ($r['ok']) { $r['provider'] = 'gemini'; return $r; }
            if (OpenRouterClient::isConfigured()) {
                $fallback = OpenRouterClient::generate($prompt, $system);
                if ($fallback['ok']) { $fallback['provider'] = 'openrouter'; return $fallback; }
            }
            return $r;
        }
        if (OpenRouterClient::isConfigured()) {
            $r = OpenRouterClient::generate($prompt, $system);
            $r['provider'] = 'openrouter';
            return $r;
        }
        return ['ok' => false, 'error' => 'No AI provider configured. Add a Gemini or OpenRouter API key in Settings > API Keys.'];
    }
}
