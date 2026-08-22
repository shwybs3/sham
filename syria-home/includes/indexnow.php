<?php
/**
 * IndexNow — instantly ping Bing/Yandex/other engines when content is published.
 * Requires an API key in Settings → SEO → IndexNow API Key.
 * Key file must exist at /{key}.txt in the webroot (auto-created here).
 */
function indexnow_ping(string $url): bool {
    $key = trim(setting('indexnow_key', ''));
    if ($key === '') return false;

    $keyFile = ROOT_PATH . '/' . basename($key) . '.txt';
    if (!file_exists($keyFile)) {
        @file_put_contents($keyFile, $key);
    }

    $host = parse_url(SITE_URL, PHP_URL_HOST);
    $payload = json_encode([
        'host' => $host,
        'key'  => $key,
        'keyLocation' => rtrim(SITE_URL, '/') . '/' . basename($key) . '.txt',
        'urlList' => [$url],
    ]);

    $ch = curl_init('https://api.indexnow.org/indexnow');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_CAINFO => defined('CURLOPT_CAINFO') ? (ini_get('curl.cainfo') ?: null) : null,
    ]);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_exec($ch);
    curl_close($ch);
    return ($code >= 200 && $code < 300) || $code === 422;
}
