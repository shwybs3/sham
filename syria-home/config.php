<?php
/* ═══════════════════════════════════════════════
   SYRIA HOME — config.php
   Written automatically by install/index.php.
   You normally never need to edit this by hand.
   ═══════════════════════════════════════════════ */

if (!defined('SH_CONFIG_LOADED')) define('SH_CONFIG_LOADED', true);

$__generated = __DIR__ . '/config.generated.php';
if (file_exists($__generated)) {
    require $__generated;
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'your_db_name');
    define('DB_USER', 'your_db_user');
    define('DB_PASS', 'your_db_password');
    define('SITE_URL', 'https://syria-home.yassota.com');
    define('APP_SECRET', 'change-me-' . bin2hex(random_bytes(8)));
}

define('ROOT_PATH', __DIR__);
define('UPLOAD_PATH', __DIR__ . '/uploads');
define('UPLOAD_URL', rtrim(SITE_URL, '/') . '/uploads');
define('INSTALL_LOCK', __DIR__ . '/install/install.lock');

date_default_timezone_set('UTC');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

/* If not installed yet, send everything to the install wizard
   (except the wizard's own files and static assets). */
if (!file_exists(INSTALL_LOCK)) {
    $__uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (strpos($__uri, '/install/') === false && strpos($__uri, '/assets/') === false) {
        header('Location: ' . site_url_root_relative('install/'));
        exit;
    }
}

/** Redirect helper usable before the settings table / SITE_URL constant are trustworthy. */
function site_url_root_relative(string $path): string {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (basename($scriptDir) === 'admin') $scriptDir = dirname($scriptDir);
    $base = rtrim($scriptDir, '/');
    return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
}

if (session_status() === PHP_SESSION_NONE) session_start();

/* ─── PDO Connection ─── */
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(503);
    die('<!doctype html><meta charset="utf-8"><body style="font-family:sans-serif;max-width:640px;margin:60px auto;color:#111">
    <h2 style="color:#c0392b">Database connection failed</h2>
    <p>Check <code>config.generated.php</code> (created by the install wizard) or re-run <a href="install/">install/</a>.</p>
    <pre style="background:#f3f3f3;padding:12px;border-radius:8px;white-space:pre-wrap">' . htmlspecialchars($e->getMessage()) . '</pre></body>');
}

require_once __DIR__ . '/includes/schema.php';
sh_ensure_schema($pdo);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/google/GoogleOAuth.php';
require_once __DIR__ . '/includes/google/AdSenseClient.php';
require_once __DIR__ . '/includes/google/SearchConsoleClient.php';
require_once __DIR__ . '/includes/google/AnalyticsDataClient.php';
require_once __DIR__ . '/includes/google/GoogleAdsClient.php';
require_once __DIR__ . '/includes/ai/GeminiClient.php';
require_once __DIR__ . '/includes/ai/OpenRouterClient.php';
require_once __DIR__ . '/includes/ai/AIRouter.php';
require_once __DIR__ . '/includes/tool_registry.php';
require_once __DIR__ . '/includes/svg_art.php';
require_once __DIR__ . '/includes/payments/NOWPayments.php';
require_once __DIR__ . '/includes/cpanel/CPanelClient.php';
require_once __DIR__ . '/includes/cpanel/SiteProvisioner.php';
