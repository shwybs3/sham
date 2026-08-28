<?php
/**
 * static-cache-check.php — earliest-possible page cache.
 *
 * Required at the very top of a public entry file, BEFORE config.php (so a
 * cache hit skips the DB connection, session, and every bootstrap cost
 * entirely). Deliberately self-contained: no dependency on config.php,
 * $pdo, or the session.
 *
 * Usage:
 *   require_once __DIR__ . '/static-cache-check.php';
 *   static_cache_try_serve('app', $key);   // may exit() on a hit
 *   ... require config.php, connect DB, etc ...
 *   static_cache_capture_start('app', $key);
 *   ... render the page normally ...
 *   // nothing else to call — a shutdown handler saves the buffered output
 */

define('STATIC_CACHE_TTL', 300);

function static_cache_dir(): string {
    $dir = __DIR__ . '/uploads/.static-cache';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir;
}

function static_cache_file(string $type, string $key): string {
    return static_cache_dir() . '/' . preg_replace('/[^a-z0-9]/i', '_', $type) . '_' . md5($key) . '.html';
}

/** A request is only safe to serve/store in the shared static cache when it
 *  carries no session (a logged-in admin browsing the public site must
 *  never see, or populate the cache with, a personalized response). */
function static_cache_eligible(): bool {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return false;
    if (isset($_GET['preview'])) return false;
    $sessionCookie = ini_get('session.name') ?: 'PHPSESSID';
    if (!empty($_COOKIE[$sessionCookie])) return false;
    return true;
}

/** Serve a fresh cached copy and exit(), or return so the page renders normally. */
function static_cache_try_serve(string $type, string $key): void {
    if (!static_cache_eligible()) return;
    $file = static_cache_file($type, $key);
    if (is_file($file) && (time() - filemtime($file)) < STATIC_CACHE_TTL) {
        header('Content-Type: text/html; charset=utf-8');
        header('X-Static-Cache: HIT');
        readfile($file);
        exit;
    }
}

/** Start buffering the page output and arrange for it to be saved on shutdown. */
function static_cache_capture_start(string $type, string $key): void {
    if (!static_cache_eligible()) return;
    ob_start();
    register_shutdown_function(function () use ($type, $key) {
        if (ob_get_level() < 1) return;
        $html = ob_get_clean();
        $code = http_response_code();
        if (($code === 200 || $code === false) && $html !== '') {
            $file = static_cache_file($type, $key);
            $tmp = $file . '.' . getmypid() . '.tmp';
            if (@file_put_contents($tmp, $html) !== false) {
                @rename($tmp, $file);
            }
        }
        echo $html;
    });
}
