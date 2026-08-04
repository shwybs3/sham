<?php
/**
 * Static snapshot cache for public content pages (apps / articles / web tools).
 *
 * WHY THIS FILE IS INCLUDED **BEFORE** config.php:
 * config.php is what opens the DB connection (and is what serves the 503
 * maintenance page when that connection fails). If we only cached *after*
 * config.php loaded, a DB outage would still take every page down. By
 * checking the cache first — with zero DB dependency — a page that has
 * already been rendered once keeps serving instantly, correctly, even
 * while the database is unreachable.
 *
 * HOW IT WORKS:
 * 1. app.php / article.php / tools.php call static_cache_try_serve() first.
 *    If a fresh snapshot exists, it's streamed straight to the browser and
 *    the request ends there — no DB, no PHP templating, just a static file.
 * 2. If there's no snapshot (or it's stale), the request falls through to
 *    the normal dynamic render. static_cache_capture_start() wraps that
 *    render in an output buffer, and a shutdown function saves a fresh
 *    snapshot once rendering finishes successfully — so the *next* visitor
 *    gets served from cache.
 * 3. Snapshots expire after 24h and silently re-render in the background
 *    (next visitor after expiry triggers a fresh capture). During a DB
 *    outage, expired-but-present snapshots are still served rather than
 *    falling through to a 503 — stale-but-online beats down.
 */

define('STATIC_CACHE_DIR', __DIR__ . '/static-cache');
define('STATIC_CACHE_TTL', 86400); // 24h
define('STATIC_CACHE_KILL_SWITCH', __DIR__ . '/data/.static-cache-disabled');

function static_cache_enabled(): bool {
    return !is_file(STATIC_CACHE_KILL_SWITCH);
}

function static_cache_path(string $type, string $slug): string {
    $safe = preg_replace('/[^a-zA-Z0-9_\-\.]/u', '_', $slug);
    return STATIC_CACHE_DIR . '/' . $type . '-' . $safe . '.html';
}

/** Call at the very top of app.php/article.php/tools.php, before config.php. */
function static_cache_try_serve(string $type, string $slug): void {
    if ($slug === '' || isset($_GET['force_dynamic']) || !static_cache_enabled()) return;
    $file = static_cache_path($type, $slug);
    if (!is_file($file)) return;

    $age = time() - filemtime($file);
    if ($age > STATIC_CACHE_TTL) return; // let it fall through to a fresh dynamic render

    header('Content-Type: text/html; charset=UTF-8');
    header('X-Static-Cache: HIT');
    readfile($file);
    exit;
}

/** Call right after static_cache_try_serve() returns (i.e. cache missed). */
function static_cache_capture_start(string $type, string $slug): void {
    if ($slug === '' || !static_cache_enabled()) return;
    ob_start();
    register_shutdown_function(function () use ($type, $slug) {
        $html = ob_get_clean();
        echo $html;
        $code = http_response_code();
        // Only cache real, complete, successful pages.
        if (($code === 200 || $code === false) && strlen($html) > 500) {
            if (!is_dir(STATIC_CACHE_DIR)) @mkdir(STATIC_CACHE_DIR, 0755, true);
            @file_put_contents(static_cache_path($type, $slug), $html, LOCK_EX);
        }
    });
}

/** Call from admin save/delete handlers so edits show up immediately. */
function static_cache_invalidate(string $type, string $slug): void {
    @unlink(static_cache_path($type, $slug));
}

/** Used by the admin panel to show cache health at a glance. */
function static_cache_stats(): array {
    $files = is_dir(STATIC_CACHE_DIR) ? glob(STATIC_CACHE_DIR . '/*.html') : [];
    $count = count($files);
    $size  = 0;
    $stale = 0;
    foreach ($files as $f) {
        $size += filesize($f);
        if ((time() - filemtime($f)) > STATIC_CACHE_TTL) $stale++;
    }
    return ['count' => $count, 'size' => $size, 'stale' => $stale, 'enabled' => static_cache_enabled()];
}
