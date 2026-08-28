<?php
/**
 * Fetches the public, static HTML of a Google Play Store app listing page
 * and extracts what's actually available without an API key: the app
 * name, developer, description and icon (from standard Open Graph meta
 * tags every Play Store page carries for link previews), plus a
 * best-effort list of screenshot image URLs found in the page markup.
 *
 * There is no official public API for reading a third-party app's store
 * listing — the Play Developer API is scoped to apps you own. This is
 * read-only, admin-triggered (one URL at a time, never automated mass
 * scraping), and only touches the same publicly-visible page a browser
 * would show. Screenshot extraction is genuinely best-effort: Play Store
 * markup isn't a stable contract, so a future layout change could return
 * fewer screenshots (or none) without this being a bug to chase — the
 * icon, name and description come from OG tags and are far more stable.
 */
function fetch_play_store_app_info(string $playUrl): array {
    if (!preg_match('~^https://play\.google\.com/store/apps/details\?id=~i', $playUrl)) {
        return ['ok' => false, 'error' => 'That doesn\'t look like a Google Play app listing URL (expected https://play.google.com/store/apps/details?id=...).'];
    }

    $host = parse_url($playUrl, PHP_URL_HOST);
    $ip = $host ? gethostbyname($host) : false;
    if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return ['ok' => false, 'error' => 'That host cannot be reached.'];
    }

    $ch = curl_init($playUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
        CURLOPT_HTTPHEADER => ['Accept-Language: en-US,en;q=0.9'],
    ]);
    $html = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($html === false) return ['ok' => false, 'error' => 'Could not fetch the Play Store page: ' . $err];
    if ($code >= 400) return ['ok' => false, 'error' => "Play Store returned HTTP $code — check the link is correct and public."];

    $meta = function (string $property) use ($html): string {
        if (preg_match('~<meta\s+property=["\']' . preg_quote($property, '~') . '["\']\s+content=["\']([^"\']*)["\']~i', $html, $m)) return html_entity_decode($m[1], ENT_QUOTES);
        if (preg_match('~<meta\s+content=["\']([^"\']*)["\']\s+property=["\']' . preg_quote($property, '~') . '["\']~i', $html, $m)) return html_entity_decode($m[1], ENT_QUOTES);
        return '';
    };

    $name = $meta('og:title');
    $description = $meta('og:description');
    $icon = $meta('og:image');

    if ($name === '') return ['ok' => false, 'error' => 'Could not read the app name from that page — it may not be a valid or public listing.'];

    /* Developer name: best-effort from JSON-LD if the page includes it. */
    $developer = '';
    if (preg_match('~"author"\s*:\s*\{[^}]*"name"\s*:\s*"([^"]+)"~', $html, $m)) $developer = html_entity_decode($m[1], ENT_QUOTES);

    /* Screenshots: best-effort — collect distinct play-lh.googleusercontent.com
       image URLs from the raw markup, drop the icon itself, cap at 8. */
    $screenshots = [];
    if (preg_match_all('~https://play-lh\.googleusercontent\.com/[A-Za-z0-9_\-]+(?:=w\d+-h\d+[^"\'\s\\\\]*)?~i', $html, $mm)) {
        $seen = [$icon => true];
        foreach ($mm[0] as $u) {
            $base = preg_replace('~=w\d+-h\d+.*$~', '', $u);
            if (isset($seen[$base]) || isset($seen[$u])) continue;
            $seen[$base] = true;
            $screenshots[] = $u;
            if (count($screenshots) >= 8) break;
        }
    }

    return [
        'ok' => true,
        'name' => $name,
        'description' => $description,
        'icon_url' => $icon,
        'developer' => $developer,
        'screenshot_urls' => $screenshots,
    ];
}
