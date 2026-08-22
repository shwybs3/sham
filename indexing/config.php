<?php
/**
 * config.php — Google Indexing Pro (No Database, File Storage)
 */

// ── Site ──────────────────────────────────────────────────────────────────────
define('SITE_URL',   'https://yoursite.com');        // NO trailing slash
define('SITE_NAME',  'IndexPro');
define('SITE_TAGLINE','Instant Google Indexing via Search Console API');
define('SITE_BASE',  '');                            // e.g. '/tools' if in a subfolder

// ── Admin credentials ─────────────────────────────────────────────────────────
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'change_me_now_123!');          // Change immediately!

// ── NOWPayments ───────────────────────────────────────────────────────────────
define('NP_API_KEY',    'M9WQVSQ-YABMQQ8-MDHM5S6-152GTMV');
define('NP_IPN_SECRET', 'S6msX8/gGLnY/LLECqqzB7ld7yszvvcF');

// ── Plans (name, days, daily_limit, price_usd) ────────────────────────────────
define('PLANS', [
    'free'     => ['name'=>'Free',     'days'=>0,  'limit'=>100,   'price'=>0],
    'starter'  => ['name'=>'Starter',  'days'=>30, 'limit'=>500,   'price'=>7],
    'pro'      => ['name'=>'Pro',      'days'=>30, 'limit'=>2000,  'price'=>10],
    'business' => ['name'=>'Business', 'days'=>30, 'limit'=>10000, 'price'=>25],
]);

// ── Google OAuth (optional) ───────────────────────────────────────────────────
define('GOOGLE_CLIENT_ID',     '');
define('GOOGLE_CLIENT_SECRET', '');

// ── Data retention (days) ─────────────────────────────────────────────────────
define('DATA_RETENTION_DAYS', 30);

// ── Data directory ────────────────────────────────────────────────────────────
define('DATA_DIR', __DIR__ . '/data');

// ═══════════════════════════════════════════════════════════════════════════════
// Bootstrap — do not edit below
// ═══════════════════════════════════════════════════════════════════════════════
if (session_status() === PHP_SESSION_NONE) session_start();

foreach ([DATA_DIR, DATA_DIR.'/users', DATA_DIR.'/payments', DATA_DIR.'/logs', DATA_DIR.'/sessions', DATA_DIR.'/reports'] as $d)
    if (!is_dir($d)) @mkdir($d, 0755, true);

// Protect data dir
$ht = DATA_DIR . '/.htaccess';
if (!file_exists($ht)) file_put_contents($ht, "Require all denied\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n");

// ── Helpers ───────────────────────────────────────────────────────────────────
function site_url(string $path = ''): string {
    return rtrim(SITE_URL,'') . rtrim(SITE_BASE,'/') . '/' . ltrim($path,'/');
}
function h(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['_csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.csrf_token().'">'; }
function csrf_ok(): bool { return hash_equals(csrf_token(), $_POST['_csrf'] ?? ''); }
function get_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k)
        if (!empty($_SERVER[$k])) return explode(',',$_SERVER[$k])[0];
    return '0.0.0.0';
}
function get_country(): string {
    $ip = get_ip();
    // Cloudflare header (most reliable)
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) return $_SERVER['HTTP_CF_IPCOUNTRY'];
    return 'Unknown';
}
function json_read(string $file): array {
    return file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
}
function json_write(string $file, array $data): void {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), LOCK_EX);
}
function now(): string { return date('Y-m-d H:i:s'); }

// ── Icon system (inline SVG, replaces emoji throughout the app) ────────────────
function icon(string $name, int $size = 20): string {
    $stroke = 'fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"';
    $paths = [
        'bolt'        => '<path d="M12 2 4 14h6l-1 8 9-13h-6l1-7z" stroke-linejoin="round"/>',
        'key'         => '<circle cx="7.5" cy="14.5" r="4.5"/><path d="M11 11l8-8m-3 3 2.5 2.5M15 5l2.5 2.5"/>',
        'upload'      => '<path d="M12 3v13m0-13 5 5m-5-5-5 5"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
        'map'         => '<path d="M9 4 4 6v14l5-2 6 2 5-2V4l-5 2-6-2Z" stroke-linejoin="round"/><path d="M9 4v14M15 6v14"/>',
        'csv'         => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'chart'       => '<path d="M4 20V10m6 10V4m6 16v-7m6 7V8"/>',
        'clipboard'   => '<rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1M9 11h6M9 15h4"/>',
        'rocket'      => '<path d="M13 3c3 1 6 4 7 7-2 1-4 2-6 4-2 2-3 4-4 6-3-1-5-3-6-6 2-1 4-2 6-4 2-2 3-4 3-7Z" stroke-linejoin="round"/><circle cx="14.5" cy="9.5" r="1.3"/><path d="M6 15c-2 0-3 2-3 5 3 0 5-1 5-3M9 18l-2 2"/>',
        'message'     => '<path d="M4 5h16v11H8l-4 4V5Z" stroke-linejoin="round"/>',
        'shield'      => '<path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3Z" stroke-linejoin="round"/><path d="M9 12l2 2 4-4"/>',
        'mail'        => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 6 8 6 8-6"/>',
        'refresh'     => '<path d="M20 11a8 8 0 0 0-14.9-3.5M4 13a8 8 0 0 0 14.9 3.5"/><path d="M4 4v5h5M20 20v-5h-5"/>',
        'trash'       => '<path d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13"/>',
        'warning'     => '<path d="M12 3 2 20h20L12 3Z" stroke-linejoin="round"/><path d="M12 10v5"/><circle cx="12" cy="17.5" r="0.9" fill="currentColor" stroke="none"/>',
        'info'        => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6"/><circle cx="12" cy="7.5" r="0.9" fill="currentColor" stroke="none"/>',
        'check'       => '<path d="M4 12l6 6 10-12"/>',
        'checkCircle' => '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>',
        'xCircle'     => '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6m0-6-6 6"/>',
        'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l4 2"/>',
        'lock'        => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'user'        => '<circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/>',
        'globe'       => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 4 6 4 9s-1.5 6.5-4 9c-2.5-2.5-4-6-4-9s1.5-6.5 4-9Z"/>',
        'search'      => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'settings'    => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M4.2 4.2l2.1 2.1m11.4 11.4 2.1 2.1M2 12h3m14 0h3M4.2 19.8l2.1-2.1m11.4-11.4 2.1-2.1"/>',
        'flag'        => '<path d="M5 3v18"/><path d="M5 4h11l-2 4 2 4H5" stroke-linejoin="round"/>',
        'send'        => '<path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7Z" stroke-linejoin="round"/>',
        'copy'        => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/>',
        'arrowRight'  => '<path d="M4 12h16M13 5l7 7-7 7"/>',
        'chevronDown' => '<path d="M5 8l7 7 7-7"/>',
        'external'    => '<path d="M14 4h6v6M20 4 10 14M8 5H5a1 1 0 0 0-1 1v13a1 1 0 0 0 1 1h13a1 1 0 0 0 1-1v-3"/>',
        'gem'         => '<path d="M4 8 8 3h8l4 5-10 12L4 8Z" stroke-linejoin="round"/><path d="M4 8h16M9 3l1 5-2 12M15 3l-1 5 2 12"/>',
        'building'    => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 7h1m4 0h1m-6 4h1m4 0h1m-6 4h1m4 0h1M10 21v-4h4v4"/>',
        'star'        => '<path d="M12 3l2.6 5.9 6.4.6-4.8 4.3 1.4 6.3-5.6-3.4-5.6 3.4 1.4-6.3-4.8-4.3 6.4-.6L12 3Z" stroke-linejoin="round"/>',
        'phone'       => '<path d="M6 3h3l2 5-2.5 1.5a11 11 0 0 0 5 5L15 12l5 2v3a2 2 0 0 1-2 2C10.5 19 5 13.5 5 6a2 2 0 0 1 1-3Z" stroke-linejoin="round"/>',
        'target'      => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><circle cx="12" cy="12" r="0.8" fill="currentColor" stroke="none"/>',
        'layers'      => '<path d="m12 3 9 5-9 5-9-5 9-5Z" stroke-linejoin="round"/><path d="m3 13 9 5 9-5M3 8l9 5 9-5"/>',
        'zap'         => '<path d="M13 2 4 14h6l-1 8 9-13h-6l1-7z" stroke-linejoin="round"/>',
        'download'    => '<path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
        'menu'        => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close'       => '<path d="M6 6l12 12M18 6 6 18"/>',
        'plus'        => '<path d="M12 5v14M5 12h14"/>',
        'creditCard'  => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>',
        'headset'     => '<path d="M4 13v-1a8 8 0 0 1 16 0v1"/><rect x="3" y="13" width="4" height="6" rx="1.5"/><rect x="17" y="13" width="4" height="6" rx="1.5"/><path d="M19 19v1a3 3 0 0 1-3 3h-3"/>',
    ];
    $body = $paths[$name] ?? $paths['info'];
    if (strpos($body,'stroke=') === false && strpos($body,'fill="currentColor"') === false) {
        // apply default stroke attrs to elements missing explicit style overrides handled inline already
    }
    return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" '.$stroke.' aria-hidden="true">'.$body.'</svg>';
}

// ── IndexNow (optional multi-engine submission: Bing, Yandex, Seznam.cz, Naver) ─
define('INDEXNOW_ENDPOINT', 'https://api.indexnow.org/indexnow');
