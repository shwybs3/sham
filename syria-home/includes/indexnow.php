<?php
/**
 * ═══════════════════════════════════════════════
 * Multi-engine indexing module.
 *
 * Submits URLs to every search engine that offers a real,
 * free submission API:
 *   · IndexNow  — Bing, Yandex, Seznam, Naver (one key, five endpoints)
 *   · Google Indexing API — via the connected Google account
 *   · Google Search Console URL Inspection — read back real index status
 *
 * Every submission is written to the `index_log` table so the admin
 * Indexing page can show what was actually sent and what came back.
 * ═══════════════════════════════════════════════
 */

/** The five live IndexNow endpoints. api.indexnow.org forwards to all
 *  participating engines; the rest are direct in case forwarding lags. */
const SH_INDEXNOW_ENDPOINTS = [
    'indexnow' => 'https://api.indexnow.org/indexnow',
    'bing'     => 'https://www.bing.com/indexnow',
    'yandex'   => 'https://yandex.com/indexnow',
    'seznam'   => 'https://search.seznam.cz/indexnow',
    'naver'    => 'https://searchadvisor.naver.com/indexnow',
];

/**
 * Returns the site's IndexNow key, generating and persisting one on first
 * use. The key is an arbitrary 8–128 char hex string per the IndexNow spec.
 */
function indexnow_key(bool $autoCreate = true): string {
    $key = trim(setting('indexnow_key', ''));
    if ($key === '' && $autoCreate) {
        $key = bin2hex(random_bytes(16));
        set_setting('indexnow_key', $key);
    }
    return $key;
}

/**
 * Writes /{key}.txt to the webroot — IndexNow requires this file to
 * contain exactly the key, proving you control the host.
 * Returns the public URL of the key file, or '' if it couldn't be written.
 */
function indexnow_keyfile(): string {
    $key = indexnow_key();
    if ($key === '') return '';
    $path = ROOT_PATH . '/' . $key . '.txt';
    if (!file_exists($path) || trim((string)@file_get_contents($path)) !== $key) {
        if (@file_put_contents($path, $key) === false) return '';
    }
    return rtrim(SITE_URL, '/') . '/' . $key . '.txt';
}

/** Records one submission attempt. Never throws — logging must not break publishing. */
function index_log(string $url, string $engine, string $status, int $httpCode = 0, string $message = ''): void {
    global $pdo;
    try {
        $pdo->prepare("INSERT INTO index_log (url, engine, status, http_code, message) VALUES (?,?,?,?,?)")
            ->execute([mb_substr($url, 0, 600), $engine, $status, $httpCode, mb_substr($message, 0, 400)]);
    } catch (Throwable $e) { /* logging is best-effort */ }
}

/**
 * Submits up to 10,000 URLs to every IndexNow endpoint in one batch each.
 * Returns ['endpoint' => ['code' => int, 'ok' => bool], ...]
 */
function indexnow_submit(array $urls): array {
    $urls = array_values(array_filter(array_unique($urls)));
    if (!$urls) return [];

    $key = indexnow_key();
    if ($key === '') return [];
    $keyLocation = indexnow_keyfile();

    $payload = json_encode([
        'host'        => parse_url(SITE_URL, PHP_URL_HOST),
        'key'         => $key,
        'keyLocation' => $keyLocation,
        'urlList'     => array_slice($urls, 0, 10000),
    ], JSON_UNESCAPED_SLASHES);

    $results = [];
    foreach (SH_INDEXNOW_ENDPOINTS as $name => $endpoint) {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        // 200 = accepted, 202 = accepted pending key validation.
        $ok = ($code >= 200 && $code < 300);
        $results[$name] = ['code' => $code, 'ok' => $ok];

        $label = count($urls) === 1 ? $urls[0] : count($urls) . ' URLs (batch)';
        index_log($label, 'indexnow:' . $name, $ok ? 'ok' : 'failed', $code,
            $ok ? '' : ($err ?: mb_substr((string)$body, 0, 200)));
    }
    return $results;
}

/**
 * Google Indexing API. Officially scoped to JobPosting and BroadcastEvent
 * pages — Google does not promise it will act on other URL types, so treat
 * a 200 here as "accepted", not "will be indexed".
 * Requires the `indexing` scope on the connected Google account.
 */
function google_index_submit(string $url, string $type = 'URL_UPDATED'): array {
    if (!GoogleOAuth::isConnected()) {
        return ['ok' => false, 'code' => 0, 'message' => 'Google account not connected'];
    }
    $resp = GoogleOAuth::postJson('https://indexing.googleapis.com/v3/urlNotifications:publish', [
        'url'  => $url,
        'type' => $type,
    ]);
    $ok  = empty($resp['error']);
    $msg = $ok ? '' : (is_array($resp['error'] ?? null) ? ($resp['error']['message'] ?? 'error') : (string)($resp['error'] ?? 'error'));
    index_log($url, 'google:indexing', $ok ? 'ok' : 'failed', $ok ? 200 : 0, $msg);
    return ['ok' => $ok, 'code' => $ok ? 200 : 0, 'message' => $msg, 'raw' => $resp];
}

/**
 * Search Console URL Inspection — reports whether Google has actually
 * indexed a URL, its coverage state, and last crawl time.
 * This is a read, not a submission, so it isn't logged.
 */
function gsc_inspect_url(string $url): array {
    $siteUrl = trim(setting('gsc_site_url', ''));
    if ($siteUrl === '') return ['error' => 'Set the Search Console site URL in Settings → API Keys first.'];
    if (!GoogleOAuth::isConnected()) return ['error' => 'Google account not connected.'];

    return GoogleOAuth::postJson('https://searchconsole.googleapis.com/v1/urlInspection/index:inspect', [
        'inspectionUrl' => $url,
        'siteUrl'       => $siteUrl,
    ]);
}

/**
 * One-call submission used by the publish hooks: fires IndexNow to all
 * five endpoints and, when the Google account is connected, the Indexing API.
 * Safe to call on every save — returns quickly and never throws.
 */
function index_submit_url(string $url): array {
    $out = ['indexnow' => [], 'google' => null];
    try { $out['indexnow'] = indexnow_submit([$url]); } catch (Throwable $e) {}
    if ((int)setting('google_indexing_enabled', 0) === 1) {
        try { $out['google'] = google_index_submit($url); } catch (Throwable $e) {}
    }
    return $out;
}

/**
 * Backwards-compatible wrapper — the article/tool/page save handlers call this.
 * Returns true when at least one engine accepted the URL.
 */
function indexnow_ping(string $url): bool {
    if ((int)setting('auto_index_on_publish', 1) !== 1) return false;
    if (trim(setting('indexnow_key', '')) === '' && (int)setting('indexnow_autokey', 1) !== 1) {
        return false;
    }
    $res = index_submit_url($url);
    foreach ($res['indexnow'] as $r) { if (!empty($r['ok'])) return true; }
    return !empty($res['google']['ok']);
}

/** Every public URL on this site — used by the "submit everything" action. */
function sh_all_public_urls(): array {
    global $pdo;
    $urls = [site_url('')];
    try {
        foreach ($pdo->query("SELECT slug FROM articles WHERE status='published'") as $r) {
            $urls[] = site_url('article/' . $r['slug']);
        }
        foreach ($pdo->query("SELECT slug FROM tools WHERE status='published'") as $r) {
            $urls[] = site_url('tool/' . $r['slug']);
        }
        foreach ($pdo->query("SELECT slug FROM pages WHERE status='published'") as $r) {
            $urls[] = site_url('p/' . $r['slug']);
        }
        foreach ($pdo->query("SELECT slug FROM products WHERE status='published'") as $r) {
            $urls[] = site_url('product/' . $r['slug']);
        }
    } catch (PDOException $e) { /* table may not exist yet on a fresh install */ }
    return array_values(array_unique($urls));
}
