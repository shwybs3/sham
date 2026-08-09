<?php
/* ══════════════════════════════════════════════════════
   social/config.php — Core config for the social platform
   (Yassota Connect — Telegram + Instagram hybrid)
   Local overrides go in social/config.local.php (not in git)
   ══════════════════════════════════════════════════════ */

$_lc = __DIR__ . '/config.local.php';
if (file_exists($_lc)) require_once $_lc;
unset($_lc);

defined('SOCIAL_DB_HOST')   || define('SOCIAL_DB_HOST',   'localhost');
defined('SOCIAL_DB_NAME')   || define('SOCIAL_DB_NAME',   'yassota_social');
defined('SOCIAL_DB_USER')   || define('SOCIAL_DB_USER',   'your_db_user');
defined('SOCIAL_DB_PASS')   || define('SOCIAL_DB_PASS',   'your_db_password');

defined('SOCIAL_URL')       || define('SOCIAL_URL',    'https://connect.yassota.com');
defined('SOCIAL_NAME')      || define('SOCIAL_NAME',   'Yassota Connect');
defined('SOCIAL_SECRET')    || define('SOCIAL_SECRET', 'change-this-to-a-long-random-string');

// Google OAuth
defined('GOOGLE_CLIENT_ID')     || define('GOOGLE_CLIENT_ID',     '');
defined('GOOGLE_CLIENT_SECRET') || define('GOOGLE_CLIENT_SECRET', '');
defined('GOOGLE_REDIRECT_URI')  || define('GOOGLE_REDIRECT_URI',  SOCIAL_URL . '/auth/google/callback');

// NOWPayments
defined('NOWPAY_IID')           || define('NOWPAY_IID', '4337098069');
defined('NOWPAY_API_KEY')       || define('NOWPAY_API_KEY', '');
defined('NOWPAY_IPN_SECRET')    || define('NOWPAY_IPN_SECRET', '');

// Upload paths
defined('SOCIAL_UPLOAD_PATH')   || define('SOCIAL_UPLOAD_PATH', __DIR__ . '/uploads');
defined('SOCIAL_UPLOAD_URL')    || define('SOCIAL_UPLOAD_URL',  SOCIAL_URL . '/uploads');

// Verification badge price (USD/month)
define('BADGE_PRICE_USD', 7);
// Currency (fixed forever)
define('SYP_RATE', 13500);

date_default_timezone_set('Asia/Riyadh');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

/* ─── PDO ─── */
try {
    $social_pdo = new PDO(
        "mysql:host=" . SOCIAL_DB_HOST . ";dbname=" . SOCIAL_DB_NAME . ";charset=utf8mb4",
        SOCIAL_DB_USER, SOCIAL_DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(503);
    die('{"error":"db_unavailable"}');
}

/* ─── Session ─── */
if (session_status() === PHP_SESSION_NONE) {
    session_name('sc_sess');
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ─── Helpers ─── */
function sc_h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function sc_user(PDO $pdo): ?array {
    $uid = $_SESSION['sc_uid'] ?? null;
    if (!$uid) return null;
    $st = $pdo->prepare("SELECT * FROM users WHERE id=? AND status='active' LIMIT 1");
    $st->execute([$uid]);
    return $st->fetch() ?: null;
}

function sc_login(array $user): void {
    $_SESSION['sc_uid'] = $user['id'];
}

function sc_logout(): void {
    session_destroy();
}

function sc_json(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sc_time_ago(string $dt): string {
    $diff = time() - strtotime($dt);
    if ($diff < 60)   return 'الآن';
    if ($diff < 3600) return floor($diff/60) . 'د';
    if ($diff < 86400)return floor($diff/3600) . 'س';
    if ($diff < 604800)return floor($diff/86400) . 'ي';
    return date('d M', strtotime($dt));
}

function sc_slug(string $text): string {
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\-_]/u', '-', $text);
    return preg_replace('/-+/', '-', trim($text, '-')) ?: 'user';
}

function sc_upload_image(array $file, string $dir, int $maxMb = 5): string|false {
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($file['type'] ?? '', $allowed)) return false;
    if (($file['size'] ?? 0) > $maxMb * 1024 * 1024) return false;
    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    $dest = SOCIAL_UPLOAD_PATH . '/' . $dir . '/' . $name;
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
    return move_uploaded_file($file['tmp_name'], $dest) ? $name : false;
}

function sc_avatar_url(?string $avatar): string {
    if ($avatar) return SOCIAL_UPLOAD_URL . '/avatars/' . sc_h($avatar);
    return SOCIAL_URL . '/assets/default-avatar.svg';
}

/* ─── Anti-pornography: basic keyword filter ─── */
function sc_is_clean(string $text): bool {
    static $bad = ['porn','sex','nude','naked','xxx','nsfw','adult content'];
    $lower = mb_strtolower($text);
    foreach ($bad as $w) {
        if (str_contains($lower, $w)) return false;
    }
    return true;
}
