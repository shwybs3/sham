<?php
/**
 * بوابة الأدوات — Core configuration
 * Copy this as config.local.php and set real DB credentials there.
 */

if (file_exists(__DIR__ . '/config.local.php')) require_once __DIR__ . '/config.local.php';

// ── Site identity ────────────────────────────────────
if (!defined('SITE_NAME'))    define('SITE_NAME',    'بوابة الأدوات');
if (!defined('SITE_TAGLINE')) define('SITE_TAGLINE', 'أسعار الصرف والذهب • أدوات PDF والصور • حاسبات البناء والطاقة الشمسية');
if (!defined('SITE_URL'))     define('SITE_URL',     'https://your-domain.com');
if (!defined('SITE_LOGO'))    define('SITE_LOGO',    '⚡');

// ── Database ─────────────────────────────────────────
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'toolhub');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// ── Cache dir ────────────────────────────────────────
define('CACHE_DIR', __DIR__ . '/cache');
if (!is_dir(CACHE_DIR)) @mkdir(CACHE_DIR, 0755, true);

// ── PDO singleton ────────────────────────────────────
function db(): ?PDO {
    static $pdo;
    if ($pdo !== null) return $pdo;
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        _ensure_schema($pdo);
    } catch (PDOException) { $pdo = null; }
    return $pdo;
}

function _ensure_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings(k VARCHAR(120) PRIMARY KEY, v MEDIUMTEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS price_cache(cache_key VARCHAR(100) PRIMARY KEY, data MEDIUMTEXT, expires INT UNSIGNED) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS articles(
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(255) NOT NULL UNIQUE, title VARCHAR(500), excerpt TEXT,
        content LONGTEXT, category VARCHAR(100) DEFAULT 'عام', tags TEXT,
        meta_title VARCHAR(255), meta_desc TEXT, thumb VARCHAR(500),
        status ENUM('draft','published') DEFAULT 'draft',
        views INT UNSIGNED DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX(status, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS phones(
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        brand VARCHAR(80), model VARCHAR(200), price_sar DECIMAL(10,2),
        price_usd DECIMAL(10,2), ram VARCHAR(20), storage VARCHAR(20),
        screen VARCHAR(30), camera VARCHAR(80), battery VARCHAR(20),
        year SMALLINT, thumb VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(brand)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ── Cache helpers ────────────────────────────────────
function cache_get(string $key): ?array {
    $pdo = db();
    if ($pdo) {
        $r = $pdo->prepare("SELECT data FROM price_cache WHERE cache_key=? AND expires>UNIX_TIMESTAMP()");
        $r->execute([$key]); $row = $r->fetch();
        if ($row) return json_decode($row['data'], true);
    }
    $f = CACHE_DIR.'/'.md5($key).'.json';
    if (file_exists($f) && filemtime($f) > time()-3600) return json_decode(file_get_contents($f), true);
    return null;
}
function cache_set(string $key, array $data, int $ttl = 3600): void {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    $pdo = db();
    if ($pdo) {
        $pdo->prepare("REPLACE INTO price_cache(cache_key,data,expires) VALUES(?,?,UNIX_TIMESTAMP()+?)")
            ->execute([$key, $json, $ttl]);
    }
    file_put_contents(CACHE_DIR.'/'.md5($key).'.json', $json);
}

// ── Exchange rates (Frankfurter — no key needed) ─────
function get_exchange_rates(string $base = 'USD'): array {
    $key = "fx_{$base}";
    $cached = cache_get($key);
    if ($cached) return $cached;
    $url = "https://api.frankfurter.app/latest?from={$base}&to=SAR,AED,EGP,KWD,QAR,BHD,OMR,JOD,EUR,GBP,JPY,TRY,CNY,INR,CHF,CAD,AUD";
    $ctx = stream_context_create(['http'=>['timeout'=>8,'user_agent'=>'Mozilla/5.0']]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw) { $d = json_decode($raw, true); if (!empty($d['rates'])) { cache_set($key, $d, 3600); return $d; } }
    return ['base'=>$base, 'date'=>date('Y-m-d'), 'rates'=>[], '_stale'=>true];
}

// ── Gold prices (XAU via Frankfurter) ───────────────
function get_gold_prices(): array {
    $cached = cache_get('gold_all');
    if ($cached) return $cached;
    $url = 'https://api.frankfurter.app/latest?from=XAU&to=USD,SAR,AED,EGP,EUR,GBP,KWD';
    $ctx = stream_context_create(['http'=>['timeout'=>8,'user_agent'=>'Mozilla/5.0']]);
    $raw = @file_get_contents($url, false, $ctx);
    $pricePerOz = 2050.0; // fallback USD per troy oz
    $rates = [];
    if ($raw) {
        $d = json_decode($raw, true);
        if (!empty($d['rates']['USD'])) { $pricePerOz = $d['rates']['USD']; $rates = $d['rates']; }
    }
    $g = $pricePerOz / 31.1035; // USD per gram 24k
    $sarRate = $rates['SAR'] ?? 3.75;
    $aedRate = $rates['AED'] ?? 3.67;
    $egpRate = $rates['EGP'] ?? 30.9;
    $kwdRate = $rates['KWD'] ?? 0.31;
    $result = [
        'usd_oz'  => round($pricePerOz, 2),
        'usd_g'   => round($g, 2),
        'sar_g'   => round($g * $sarRate, 2),
        'aed_g'   => round($g * $aedRate, 2),
        'egp_g'   => round($g * $egpRate, 2),
        'kwd_g'   => round($g * $kwdRate, 4),
        'purities'=> ['24'=>1.0,'22'=>22/24,'21'=>21/24,'18'=>18/24,'14'=>14/24,'10'=>10/24],
        'date'    => date('Y-m-d H:i'),
    ];
    cache_set('gold_all', $result, 1800);
    return $result;
}

// ── Settings ─────────────────────────────────────────
function get_cfg(string $k, string $def = ''): string {
    $pdo = db(); if (!$pdo) return $def;
    $r = $pdo->prepare("SELECT v FROM settings WHERE k=?"); $r->execute([$k]);
    $row = $r->fetch(); return $row ? $row['v'] : $def;
}

// ── Helpers ──────────────────────────────────────────
function h(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function n(float $v, int $d = 2): string { return number_format($v, $d); }
function base_url(string $p = ''): string { return rtrim(SITE_URL, '/').'/'.ltrim($p, '/'); }
function active(string $page, string $cur): string { return $page === $cur ? ' active' : ''; }
