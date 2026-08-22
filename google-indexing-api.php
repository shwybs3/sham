<?php
/**
 * Google Indexing API Tool — Yassota
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/gidx-functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

function gidx_icon(string $name, int $size = 16): string {
    $paths = [
        'check'     => '<path d="M20 6 9 17l-5-5"/>',
        'x'         => '<path d="M18 6 6 18M6 6l12 12"/>',
        'circle'    => '<circle cx="12" cy="12" r="9"/>',
        'rocket'    => '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>',
        'star'      => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'gem'       => '<path d="M6 3h12l4 6-10 12L2 9Z"/><path d="M11 3 8 9l4 12 4-12-3-6"/><path d="M2 9h20"/>',
        'chart'     => '<path d="M3 3v18h18"/><path d="M18.7 8 12 15l-4-4-4.3 4.3"/>',
        'zap'       => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
        'lightbulb' => '<path d="M9 18h6M10 22h4M15 14a5 5 0 1 0-6 0c.6.5 1 1.3 1 2.1V17h4v-.9c0-.8.4-1.6 1-2.1Z"/>',
        'download'  => '<path d="M12 3v12"/><path d="m6 11 6 6 6-6"/><path d="M4 21h16"/>',
        'key'       => '<circle cx="8" cy="15" r="4"/><path d="m10.85 12.15 7.65-7.65M15 7l2 2M18 4l2 2"/>',
        'edit'      => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'lock'      => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'refresh'   => '<path d="M21 12a9 9 0 1 1-2.6-6.3"/><path d="M21 4v6h-6"/>',
        'shield'    => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'phone'     => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/>',
        'card'      => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
    ];
    $p = $paths[$name] ?? $paths['circle'];
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-3px">' . $p . '</svg>';
}

$identifier  = gidx_identifier();
$quota       = gidx_quota($pdo, $identifier);
$storedKey   = gidx_get_stored_key($pdo, $identifier);
$googleEmail = $_COOKIE['gidx_google_email'] ?? '';
$googleName  = $_COOKIE['gidx_google_name']  ?? '';
$googlePic   = $_COOKIE['gidx_google_picture'] ?? '';
$isLoggedIn  = $googleEmail !== '';

/* ══════════════════════════════════════════════════════════
   AJAX / ACTION HANDLERS
   ══════════════════════════════════════════════════════════ */

if (isset($_GET['gidx_action']) && $_GET['gidx_action'] === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (!$isLoggedIn) {
        echo json_encode(['ok' => false, 'error' => 'يجب تسجيل الدخول بحساب Google أولاً قبل الدفع.']);
        exit;
    }
    if (!empty($quota['premium_active'])) {
        echo json_encode(['ok' => false, 'error' => 'اشتراكك Premium مفعَّل بالفعل حتى ' . date('Y-m-d', strtotime($quota['premium_until']))]);
        exit;
    }
    $payCurrency = trim($_POST['currency'] ?? 'usdttrc20');
    $allowed = ['usdttrc20','usdterc20','eth','btc','ltc','bnb','usdtbsc'];
    if (!in_array($payCurrency, $allowed, true)) $payCurrency = 'usdttrc20';

    $result = nowpayments_create_payment($pdo, $identifier, $payCurrency);
    if (!$result['ok']) { echo json_encode(['ok' => false, 'error' => $result['error']]); exit; }

    $_SESSION['gidx_pending_order'] = $result['order_id'];
    echo json_encode([
        'ok'             => true,
        'order_id'       => $result['order_id'],
        'pay_amount'     => $result['data']['pay_amount']    ?? 0,
        'pay_currency'   => $result['data']['pay_currency']  ?? $payCurrency,
        'pay_address'    => $result['data']['pay_address']   ?? '',
        'price_amount'   => $result['data']['price_amount']  ?? 15,
        'price_currency' => strtoupper($result['data']['price_currency'] ?? 'USD'),
    ]);
    exit;
}

if (isset($_GET['gidx_action']) && $_GET['gidx_action'] === 'payment_status') {
    header('Content-Type: application/json');
    $orderId = trim($_GET['order_id'] ?? '');
    if ($orderId === '' || strpos($orderId, $identifier) !== 0) {
        echo json_encode(['ok' => false]); exit;
    }
    $stmt = $pdo->prepare("SELECT status FROM gidx_payments WHERE order_id=? LIMIT 1");
    $stmt->execute([$orderId]);
    $status = $stmt->fetchColumn();
    echo json_encode(['ok' => (bool)$status, 'status' => $status ?: 'unknown']);
    exit;
}

if (isset($_GET['gidx_action']) && $_GET['gidx_action'] === 'login') {
    $clientId = get_cfg($pdo, 'google_oauth_client_id', '');
    if ($clientId === '') { http_response_code(500); die('تسجيل الدخول بحساب Google غير مُفعَّل — يرجى إعداده من لوحة التحكم.'); }
    $state    = bin2hex(random_bytes(16));
    $returnTo = $_GET['return'] ?? '';
    setcookie('gidx_oauth_state', $state . '|' . $returnTo, time() + 600, '/', '', true, true);
    $redirectUri = rtrim(SITE_URL, '/') . '/google-indexing-api/callback';
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id'     => $clientId,
        'redirect_uri'  => $redirectUri,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'prompt'        => 'select_account',
    ]);
    header('Location: ' . $authUrl);
    exit;
}

if (isset($_GET['gidx_action']) && $_GET['gidx_action'] === 'callback') {
    $cookieVal   = $_COOKIE['gidx_oauth_state'] ?? '';
    $parts       = explode('|', $cookieVal, 2);
    $cookieState = $parts[0] ?? '';
    setcookie('gidx_oauth_state', '', time() - 3600, '/');
    $stateOk = !empty($_GET['state']) && $cookieState !== '' && hash_equals($cookieState, $_GET['state']);

    if (!$stateOk || empty($_GET['code'])) {
        header('Location: ' . url('google-indexing-api') . '?gidx_login=fail'); exit;
    }

    $clientId     = get_cfg($pdo, 'google_oauth_client_id', '');
    $clientSecret = get_cfg($pdo, 'google_oauth_client_secret', '');
    $redirectUri  = rtrim(SITE_URL, '/') . '/google-indexing-api/callback';

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'code'          => $_GET['code'],
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]),
        CURLOPT_TIMEOUT => 15,
    ]);
    $tokenResp = curl_exec($ch);
    curl_close($ch);
    $tokenData = $tokenResp ? json_decode($tokenResp, true) : null;

    if (empty($tokenData['id_token'])) {
        header('Location: ' . url('google-indexing-api') . '?gidx_login=fail'); exit;
    }

    $parts   = explode('.', $tokenData['id_token']);
    $payload = count($parts) === 3 ? json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) : null;

    if (!$payload || empty($payload['sub']) || empty($payload['email'])) {
        header('Location: ' . url('google-indexing-api') . '?gidx_login=fail'); exit;
    }

    $gidIdentifier = substr(hash('sha256', 'google_' . $payload['sub']), 0, 32);
    $exp = time() + 3600 * 24 * 365 * 2;
    setcookie('gidx_id',             $gidIdentifier,            $exp, '/', '', true, true);
    setcookie('gidx_google_email',   $payload['email'],         $exp, '/', '', true, true);
    setcookie('gidx_google_name',    $payload['name'] ?? '',    $exp, '/', '', true, true);
    setcookie('gidx_google_picture', $payload['picture'] ?? '', $exp, '/', '', true, true);

    gidx_quota($pdo, $gidIdentifier);
    try {
        $pdo->prepare("INSERT INTO subscribers (email, source) VALUES (?, 'google_oauth') ON DUPLICATE KEY UPDATE source=source")
            ->execute([$payload['email']]);
    } catch (\Throwable $e) {}

    header('Location: ' . url('google-indexing-api') . '?gidx_login=ok&show_premium=1');
    exit;
}

if (isset($_GET['gidx_action']) && $_GET['gidx_action'] === 'logout') {
    setcookie('gidx_google_email',   '', time()-1, '/');
    setcookie('gidx_google_name',    '', time()-1, '/');
    setcookie('gidx_google_picture', '', time()-1, '/');
    setcookie('gidx_id',             '', time()-1, '/');
    header('Location: ' . url('google-indexing-api')); exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'import_sitemap') {
    header('Content-Type: application/json; charset=utf-8');
    $xml = @file_get_contents(url('sitemap.php'));
    if ($xml === false) { echo json_encode(['ok' => false, 'error' => 'تعذّر جلب sitemap.php']); exit; }
    preg_match_all('/<loc>(.*?)<\/loc>/i', $xml, $m);
    $urls = array_slice(array_map('html_entity_decode', $m[1] ?? []), 0, 500);
    echo json_encode(['ok' => true, 'urls' => $urls], JSON_UNESCAPED_SLASHES);
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'connect_key' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $raw = '';
    if (!empty($_FILES['key_file']) && $_FILES['key_file']['error'] === UPLOAD_ERR_OK) {
        $raw = file_get_contents($_FILES['key_file']['tmp_name']);
    } else {
        $raw = trim($_POST['key_text'] ?? '');
    }
    if ($raw === '') { echo json_encode(['ok' => false, 'error' => 'ارفع ملف JSON أو الصق محتواه']); exit; }
    $check = gidx_validate_key($raw);
    if (!$check['ok']) { echo json_encode(['ok' => false, 'error' => $check['error']]); exit; }
    gidx_store_key($pdo, $identifier, $raw, $check['client_email']);
    echo json_encode(['ok' => true, 'client_email' => $check['client_email']]);
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'disconnect_key') {
    header('Content-Type: application/json; charset=utf-8');
    gidx_disconnect_key($pdo, $identifier);
    echo json_encode(['ok' => true]);
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $jsonKey = trim($_POST['json_key'] ?? '');
    $urlsRaw = trim($_POST['urls'] ?? '');
    $urls    = array_values(array_filter(array_map('trim', explode("\n", $urlsRaw))));

    if ($jsonKey === '') {
        $stored = gidx_get_stored_key($pdo, $identifier);
        if ($stored) $jsonKey = $stored['service_account_json'];
    }
    if ($jsonKey === '') { echo json_encode(['ok' => false, 'error' => 'اربط ملف JSON لمفتاح حساب الخدمة أولاً']); exit; }
    if (!$urls)          { echo json_encode(['ok' => false, 'error' => 'أضف رابطاً واحداً على الأقل']); exit; }

    $quota     = gidx_quota($pdo, $identifier);
    $remaining = ($quota['premium_active'] ? PHP_INT_MAX : $quota['limit']) - $quota['used'];
    if ($remaining <= 0) {
        echo json_encode(['ok' => false, 'error' => 'استهلكت رصيدك المجاني. اشترك بالباقة الاحترافية للمزيد.']); exit;
    }
    $urls     = array_slice($urls, 0, min($remaining, 500));
    $tokenRes = gidx_get_access_token($jsonKey);
    if (!$tokenRes['ok']) { echo json_encode(['ok' => false, 'error' => $tokenRes['error']]); exit; }

    $results = [];
    foreach ($urls as $u) {
        if (!filter_var($u, FILTER_VALIDATE_URL)) {
            $results[] = ['url' => $u, 'ok' => false, 'message' => 'رابط غير صالح']; continue;
        }
        $r = gidx_submit_url($tokenRes['token'], $u);
        $results[] = ['url' => $u, 'ok' => $r['ok'], 'message' => $r['message']];
        $pdo->prepare("INSERT INTO gidx_log (identifier, url, result, message) VALUES (?,?,?,?)")
            ->execute([$identifier, $u, $r['ok'] ? 'ok' : 'fail', $r['message']]);
        if ($r['ok']) gidx_increment_usage($pdo, $identifier);
    }
    $quota = gidx_quota($pdo, $identifier);
    echo json_encode(['ok' => true, 'results' => $results, 'remaining' => $quota['limit'] - $quota['used']], JSON_UNESCAPED_UNICODE);
    exit;
}

function gidx_img_slot(PDO $pdo, string $slot, string $alt): void {
    $src = get_cfg($pdo, 'gidx_img_' . $slot, '');
    if ($src !== '') {
        echo '<img src="' . h($src) . '" alt="' . h($alt) . '" loading="lazy"'
           . ' style="width:100%;border-radius:14px;margin:20px 0;box-shadow:0 4px 20px rgba(0,0,0,.08)">';
    }
}

$showPremiumModal = !empty($_GET['show_premium']);
$loginOk          = ($_GET['gidx_login'] ?? '') === 'ok';
$loginFail        = ($_GET['gidx_login'] ?? '') === 'fail';
$premiumUntil     = !empty($quota['premium_until']) ? date('Y/m/d', strtotime($quota['premium_until'])) : '';

$pageTitle = 'أداة فهرسة Google Indexing API — فهرسة فورية لموقعك | yassota';
$pageDesc  = 'أرسل صفحاتك مباشرة لفهرسة Google Indexing API — 100 عملية فهرسة مجانية، استيراد تلقائي من Sitemap، فهرسة خلال دقائق.';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="<?= h($pageDesc) ?>">
<link rel="canonical" href="<?= h(url('google-indexing-api')) ?>">
<link rel="icon" type="image/svg+xml" href="<?= h(url('favicon.svg')) ?>">
<?= head_extras($pdo) ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<script type="application/ld+json"><?= json_encode([
    "@context"=>"https://schema.org","@type"=>"SoftwareApplication",
    "name"=>"أداة فهرسة Google Indexing API — yassota",
    "applicationCategory"=>"SEO Tool","operatingSystem"=>"Web",
    "offers"=>["@type"=>"Offer","price"=>"0","priceCurrency"=>"USD"],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#f8fafc;--surface:#ffffff;--surface-2:#f1f5f9;
  --text:#0f172a;--text-muted:#64748b;--border:#e2e8f0;
  --primary:#0ea5e9;--primary-d:#0284c7;
  --success:#16a34a;--danger:#ef4444;--warn:#f59e0b;
  --radius:14px;--shadow:0 4px 24px rgba(0,0,0,.08);
  --font:'Cairo',system-ui,sans-serif;
}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh}
.gidx-header{background:#fff;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.06)}
.gidx-header-inner{max-width:1100px;margin:0 auto;padding:0 16px;height:58px;display:flex;align-items:center;gap:12px}
.gidx-logo{font-size:20px;font-weight:900;text-decoration:none;color:var(--text);display:flex;align-items:center;gap:6px;letter-spacing:-.5px}
.gidx-logo span{color:var(--primary)}
.gidx-badge{background:linear-gradient(135deg,#0ea5e9,#7c3aed);color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;white-space:nowrap}
.gidx-header-sep{flex:1}
.gidx-nav-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:20px;font-size:13px;font-weight:600;text-decoration:none;cursor:pointer;transition:.15s;border:none;font-family:var(--font)}
.gidx-nav-btn.login{background:#0f172a;color:#fff}
.gidx-nav-btn.login:hover{background:#1e293b}
.gidx-nav-btn.outline{background:transparent;color:var(--text-muted);border:1px solid var(--border)}
.gidx-nav-btn.outline:hover{border-color:var(--primary);color:var(--primary)}
.gidx-profile{position:relative}
.gidx-profile-btn{display:flex;align-items:center;gap:8px;padding:4px 10px 4px 4px;background:var(--surface-2);border:1px solid var(--border);border-radius:30px;cursor:pointer;font-family:var(--font);font-size:13px;font-weight:600;color:var(--text);transition:.15s}
.gidx-profile-btn:hover{border-color:var(--primary)}
.gidx-profile-btn img,.gidx-profile-avatar{width:30px;height:30px;border-radius:50%;object-fit:cover}
.gidx-profile-avatar{background:linear-gradient(135deg,#0ea5e9,#7c3aed);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700}
.gidx-dropdown{position:absolute;top:calc(100% + 8px);left:0;background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);min-width:220px;display:none;z-index:200;overflow:hidden}
.gidx-profile.open .gidx-dropdown{display:block}
.gidx-dropdown-header{padding:14px 16px;border-bottom:1px solid var(--border);background:var(--surface-2)}
.gidx-dropdown-email{font-size:11px;color:var(--text-muted);direction:ltr;text-align:left}
.gidx-dropdown-name{font-size:13px;font-weight:700;margin-bottom:2px}
.gidx-dropdown-plan{display:inline-block;margin-top:4px;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:700}
.gidx-dropdown-plan.free{background:rgba(100,116,139,.1);color:#64748b}
.gidx-dropdown-plan.premium{background:rgba(14,165,233,.12);color:var(--primary)}
.gidx-dropdown-item{display:flex;align-items:center;gap:8px;padding:10px 16px;font-size:13px;text-decoration:none;color:var(--text);transition:.1s;cursor:pointer;border:none;background:none;width:100%;text-align:right;font-family:var(--font)}
.gidx-dropdown-item:hover{background:var(--surface-2)}
.gidx-dropdown-item.danger{color:var(--danger)}
.gidx-wrap{max-width:1100px;margin:0 auto;padding:20px 16px 40px}
.gidx-hero{background:linear-gradient(135deg,#0ea5e9 0%,#7c3aed 100%);border-radius:var(--radius);padding:28px 24px;margin-bottom:20px;color:#fff;position:relative;overflow:hidden}
.gidx-hero::before{content:'';position:absolute;top:-50px;left:-50px;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.06)}
.gidx-hero h1{font-size:clamp(17px,4vw,22px);font-weight:900;margin-bottom:6px}
.gidx-hero p{opacity:.92;font-size:13px;line-height:1.7}
.gidx-hero-meta{margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.gidx-chip{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.18);padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;backdrop-filter:blur(4px)}
.gidx-login-hero{display:inline-flex;align-items:center;gap:8px;background:#16a34a;color:#fff;padding:8px 18px;border-radius:20px;font-size:13px;font-weight:700;text-decoration:none;transition:.15s}
.gidx-login-hero:hover{background:#15803d}
.gidx-alert{padding:11px 16px;border-radius:10px;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.gidx-alert.success{background:rgba(22,163,74,.08);border:1px solid rgba(22,163,74,.25);color:#16a34a}
.gidx-alert.error{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);color:#ef4444}
.gidx-panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:18px;box-shadow:0 1px 6px rgba(0,0,0,.04)}
.gidx-panel h2{font-size:16px;font-weight:800;margin-bottom:14px}
.gidx-panel h3{font-size:14px;font-weight:700;margin:18px 0 8px}
.gidx-pricing-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:520px){.gidx-pricing-grid{grid-template-columns:1fr}}
.gidx-plan{border:2px solid var(--border);border-radius:var(--radius);padding:20px 16px;text-align:center;transition:.15s}
.gidx-plan.featured{border-color:var(--primary);background:rgba(14,165,233,.02)}
.gidx-plan-name{font-weight:800;font-size:15px;margin-bottom:6px}
.gidx-plan-price{font-size:34px;font-weight:900;color:var(--text);margin:8px 0}
.gidx-plan-price sup{font-size:18px;font-weight:700;vertical-align:top;margin-top:8px;display:inline-block}
.gidx-plan-desc{font-size:12px;color:var(--text-muted);line-height:1.6;margin-bottom:14px}
.gidx-plan-features{list-style:none;text-align:right;margin-bottom:16px}
.gidx-plan-features li{font-size:12px;padding:3px 0;display:flex;align-items:center;gap:6px}
.gidx-plan-features li::before{content:'';display:inline-block;width:15px;height:15px;flex-shrink:0;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2316a34a' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20 6 9 17l-5-5'/%3E%3C/svg%3E") no-repeat center / contain}
.gidx-plan-features li.no::before{background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23cbd5e1' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='9'/%3E%3C/svg%3E") no-repeat center / contain}
.gidx-btn{display:block;width:100%;padding:11px 20px;border-radius:10px;border:none;font-size:14px;font-weight:700;cursor:pointer;font-family:var(--font);text-align:center;text-decoration:none;transition:.15s}
.gidx-btn.primary{background:var(--primary);color:#fff}
.gidx-btn.primary:hover{background:var(--primary-d)}
.gidx-btn.success{background:#16a34a;color:#fff}
.gidx-btn.success:hover{background:#15803d}
.gidx-btn.outline{background:transparent;border:2px solid var(--border);color:var(--text-muted)}
.gidx-btn.outline:hover{border-color:var(--primary);color:var(--primary)}
.gidx-btn:disabled{opacity:.6;cursor:not-allowed}
.gidx-input{width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:10px;font-size:13px;font-family:var(--font);background:var(--surface);color:var(--text);outline:none;transition:.15s}
.gidx-input:focus{border-color:var(--primary)}
.gidx-input[dir="ltr"]{text-align:left}
.gidx-textarea{resize:vertical;min-height:100px}
.gidx-drop-zone{border:2px dashed var(--border);border-radius:12px;padding:24px;cursor:pointer;text-align:center;transition:.15s}
.gidx-drop-zone:hover{border-color:var(--primary);background:rgba(14,165,233,.02)}
.gidx-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px}
@media(max-width:600px){.gidx-stats{grid-template-columns:repeat(2,1fr)}}
.gidx-stat{background:var(--surface-2);border-radius:10px;padding:14px;text-align:center}
.gidx-stat-val{font-size:24px;font-weight:900}
.gidx-stat-lbl{font-size:11px;color:var(--text-muted);margin-top:2px}
.gidx-table{width:100%;border-collapse:collapse;font-size:12px}
.gidx-table th{padding:7px 8px;text-align:right;border-bottom:2px solid var(--border);font-weight:700;font-size:11px;color:var(--text-muted)}
.gidx-table td{padding:8px;border-bottom:1px solid var(--border);vertical-align:middle}
.gidx-badge-ok{background:rgba(22,163,74,.1);color:#16a34a;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:700;white-space:nowrap}
.gidx-badge-err{background:rgba(239,68,68,.1);color:#ef4444;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:700;white-space:nowrap}
.gidx-article{line-height:2;font-size:14.5px}
.gidx-article h2{font-size:19px;font-weight:800;margin:28px 0 10px}
.gidx-article h3{font-size:16px;font-weight:700;margin:20px 0 8px}
.gidx-article p{margin-bottom:12px}
.gidx-article pre{background:var(--surface-2);padding:14px;border-radius:10px;direction:ltr;text-align:left;font-size:12px;overflow-x:auto;margin:12px 0}
.gidx-article strong{font-weight:700}
.gidx-article code{background:var(--surface-2);padding:2px 6px;border-radius:4px;font-size:12px;direction:ltr;display:inline-block}
.gidx-footer{background:#0f172a;color:rgba(255,255,255,.6);margin-top:40px}
.gidx-footer-inner{max-width:1100px;margin:0 auto;padding:24px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.gidx-footer-logo{color:#fff;font-weight:900;font-size:16px;text-decoration:none}
.gidx-footer-logo span{color:var(--primary)}
.gidx-footer-links{display:flex;gap:16px;flex-wrap:wrap}
.gidx-footer-links a{color:rgba(255,255,255,.5);text-decoration:none;font-size:12px}
.gidx-footer-links a:hover{color:#fff}
.gidx-footer-copy{font-size:11px;color:rgba(255,255,255,.35)}
.gidx-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1000;display:flex;align-items:center;justify-content:center;padding:16px;opacity:0;pointer-events:none;transition:opacity .2s}
.gidx-modal-overlay.active{opacity:1;pointer-events:all}
.gidx-modal-premium{background:#fff;border-radius:20px;width:100%;max-width:440px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);transform:scale(.95);transition:transform .2s}
.gidx-modal-overlay.active .gidx-modal-premium{transform:scale(1)}
.gidx-modal-header{padding:20px 20px 0;display:flex;align-items:flex-start;justify-content:space-between}
.gidx-modal-close{width:32px;height:32px;border-radius:50%;border:none;background:var(--surface-2);color:var(--text-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;transition:.15s}
.gidx-modal-close:hover{background:var(--border)}
.gidx-modal-body{padding:16px 20px 24px}
.gidx-features-icon{width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#0ea5e9,#7c3aed);display:flex;align-items:center;justify-content:center;margin-bottom:12px}
.gidx-features-title{font-size:20px;font-weight:800;margin-bottom:4px}
.gidx-features-sub{font-size:13px;color:var(--text-muted);margin-bottom:16px}
.gidx-features-list{list-style:none;display:flex;flex-direction:column;gap:10px;margin-bottom:20px}
.gidx-features-list li{display:flex;align-items:flex-start;gap:10px;padding:10px 12px;background:var(--surface-2);border-radius:10px;font-size:13px}
.gidx-features-list li .fi{font-size:18px;flex-shrink:0;margin-top:1px}
.gidx-price-badge{text-align:center;background:linear-gradient(135deg,#0ea5e9,#7c3aed);border-radius:12px;padding:14px;color:#fff;margin-bottom:16px}
.gidx-price-badge .big{font-size:32px;font-weight:900}
.gidx-price-badge .small{font-size:13px;opacity:.85}
.gidx-payment-modal{background:#fff;border-radius:20px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.3);transform:scale(.95);transition:transform .2s;overflow:hidden}
.gidx-modal-overlay.active .gidx-payment-modal{transform:scale(1)}
.gidx-pay-timer-bar{background:#f1f5f9;padding:8px 16px;text-align:center;font-size:13px;font-weight:700}
.gidx-pay-timer-bar .timer-val{color:#f59e0b}
.gidx-pay-steps{display:flex;gap:0;border-bottom:1px solid var(--border)}
.gidx-pay-step{flex:1;padding:10px 6px;text-align:center;font-size:12px;font-weight:600;color:var(--text-muted);border-bottom:2px solid transparent;transition:.15s}
.gidx-pay-step.active{color:var(--primary);border-bottom-color:var(--primary)}
.gidx-pay-step.done{color:var(--success)}
.gidx-pay-step1{padding:20px}
.gidx-pay-title{font-size:18px;font-weight:800;margin-bottom:16px}
.gidx-currency-sel{width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:12px;font-size:15px;font-weight:600;cursor:pointer;appearance:none;background:var(--surface-2) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%2394a3b8' d='M1 1l5 5 5-5'/%3E%3C/svg%3E") no-repeat left 14px center;padding-left:36px;font-family:var(--font)}
.gidx-pay-amount-info{margin:14px 0}
.gidx-pay-amount-label{font-size:12px;color:var(--text-muted);margin-bottom:3px}
.gidx-pay-amount-val{font-size:22px;font-weight:800;letter-spacing:-.5px}
.gidx-pay-amount-usd{font-size:13px;color:var(--text-muted)}
.gidx-next-btn{width:100%;padding:14px;background:var(--primary);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:var(--font);transition:.15s;margin-top:16px;display:flex;align-items:center;justify-content:center;gap:6px}
.gidx-next-btn:hover{background:var(--primary-d)}
.gidx-next-btn:disabled{opacity:.6;cursor:not-allowed}
.gidx-pay-logo{padding:10px 16px 14px;text-align:center;font-size:12px;color:var(--text-muted);border-top:1px solid var(--border)}
.gidx-pay-logo strong{color:#3b82f6}
.gidx-pay-step2{padding:16px;display:none}
.gidx-pay-detail-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.gidx-pay-detail-label{font-size:12px;color:var(--text-muted)}
.gidx-pay-detail-val{font-size:13px;font-weight:700;direction:ltr;text-align:left}
.gidx-copy-btn{background:none;border:none;cursor:pointer;padding:2px 6px;color:var(--primary);font-size:18px;transition:.15s}
.gidx-copy-btn:hover{color:var(--primary-d)}
.gidx-address-box{background:var(--surface-2);border-radius:10px;padding:10px 12px;font-size:12px;font-family:monospace;direction:ltr;text-align:left;word-break:break-all;margin-bottom:12px;position:relative}
.gidx-address-copy{position:absolute;top:6px;left:6px;background:var(--surface);border:1px solid var(--border);border-radius:6px;padding:3px 8px;font-size:11px;cursor:pointer;color:var(--primary);font-family:var(--font);transition:.15s}
.gidx-address-copy:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
.gidx-qr-tabs{display:flex;gap:0;background:var(--surface-2);border-radius:8px;margin-bottom:10px;overflow:hidden}
.gidx-qr-tab{flex:1;padding:7px;text-align:center;font-size:12px;cursor:pointer;border:none;background:none;font-family:var(--font);color:var(--text-muted);font-weight:600;transition:.15s}
.gidx-qr-tab.active{background:#fff;color:var(--primary);border-radius:6px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.gidx-qr-container{text-align:center;padding:12px 0;min-height:180px;display:flex;align-items:center;justify-content:center}
.gidx-pay-status{text-align:center;padding:10px;font-size:13px;font-weight:700;border-radius:10px;margin-top:10px}
.gidx-pay-status.waiting{background:rgba(245,158,11,.1);color:#f59e0b}
.gidx-pay-status.confirmed{background:rgba(22,163,74,.1);color:#16a34a}
.gidx-pay-status.failed{background:rgba(239,68,68,.1);color:#ef4444}
.gidx-cancel-btn{width:100%;padding:11px;background:none;border:1px solid var(--danger);color:var(--danger);border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;margin-top:10px;font-family:var(--font);transition:.15s}
.gidx-cancel-btn:hover{background:rgba(239,68,68,.06)}
.gidx-spinner{width:32px;height:32px;border:3px solid var(--border);border-top-color:var(--primary);border-radius:50%;animation:spin .7s linear infinite;margin:0 auto}
@keyframes spin{to{transform:rotate(360deg)}}
.gidx-tool-hint{background:rgba(14,165,233,.08);border:1px solid rgba(14,165,233,.2);border-radius:10px;padding:10px 14px;font-size:12.5px;color:var(--primary);margin-bottom:14px;display:none}
@media(max-width:600px){.gidx-hero{padding:20px 16px}.gidx-panel{padding:16px}.gidx-dropdown{left:auto;right:0}}
</style>
</head>
<body>

<header class="gidx-header">
  <div class="gidx-header-inner">
    <a href="<?= h(url()) ?>" class="gidx-logo">yass<span>ota</span></a>
    <div class="gidx-badge">Indexing API</div>
    <div class="gidx-header-sep"></div>
    <?php if ($isLoggedIn): ?>
    <div class="gidx-profile" id="gidx-profile-wrap">
      <button class="gidx-profile-btn" id="gidx-profile-btn">
        <?php if ($googlePic): ?>
          <img src="<?= h($googlePic) ?>" alt="<?= h($googleName) ?>">
        <?php else: ?>
          <div class="gidx-profile-avatar"><?= mb_substr($googleName ?: $googleEmail, 0, 1) ?></div>
        <?php endif; ?>
        <span><?= h($googleName ?: explode('@', $googleEmail)[0]) ?></span>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
      <div class="gidx-dropdown" id="gidx-dropdown">
        <div class="gidx-dropdown-header">
          <div class="gidx-dropdown-name"><?= h($googleName) ?></div>
          <div class="gidx-dropdown-email"><?= h($googleEmail) ?></div>
          <span class="gidx-dropdown-plan <?= $quota['premium_active'] ? 'premium' : 'free' ?>">
            <?= $quota['premium_active'] ? (gidx_icon('star') . ' Premium حتى ' . $premiumUntil) : 'مجاني' ?>
          </span>
        </div>
        <?php if (!$quota['premium_active']): ?>
        <button class="gidx-dropdown-item" onclick="openFeaturesModal()">
          <span><?= gidx_icon('gem') ?></span> ترقية إلى Premium — $15
        </button>
        <?php endif; ?>
        <div style="height:1px;background:var(--border)"></div>
        <a href="<?= h(url('google-indexing-api')) ?>?gidx_action=logout" class="gidx-dropdown-item danger">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
          تسجيل خروج
        </a>
      </div>
    </div>
    <?php else: ?>
    <a href="<?= h(url('google-indexing-api')) ?>?gidx_action=login" class="gidx-nav-btn login">
      <svg width="15" height="15" viewBox="0 0 24 24"><path fill="#fff" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#fff" fill-opacity=".8" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#fff" fill-opacity=".6" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#fff" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
      تسجيل الدخول
    </a>
    <?php endif; ?>
  </div>
</header>

<?php if ($loginOk): ?>
<div style="background:#16a34a;color:#fff;text-align:center;padding:8px;font-size:13px">
  <?= gidx_icon('check') ?> تم تسجيل الدخول بنجاح بحساب <?= h($googleEmail) ?>
</div>
<?php elseif ($loginFail): ?>
<div style="background:#ef4444;color:#fff;text-align:center;padding:8px;font-size:13px">
  <?= gidx_icon('x') ?> فشل تسجيل الدخول عبر Google — حاول مجدداً
</div>
<?php endif; ?>

<div class="gidx-wrap">

  <nav style="font-size:12px;color:var(--text-muted);margin-bottom:14px">
    <a href="<?= h(url()) ?>" style="color:var(--primary)">الرئيسية</a> /
    <a href="<?= h(url('tools')) ?>" style="color:var(--primary)">أدوات الويب</a> /
    أداة فهرسة Google
  </nav>

  <div class="gidx-hero">
    <h1><?= gidx_icon('zap') ?> فهرسة فورية بواسطة Google Indexing API</h1>
    <p>أرسل صفحاتك لجوجل مباشرة ليتم فهرستها خلال دقائق — عبر الواجهة الرسمية من Google بدون انتظار الزحف العادي.</p>
    <div class="gidx-hero-meta">
      <span class="gidx-chip">
        <?= gidx_icon('chart') ?> رصيدك: <?= (int)($quota['limit'] - $quota['used']) ?> / <?= $quota['premium_active'] ? '∞' : (int)$quota['limit'] ?> عملية
      </span>
      <?php if ($quota['premium_active']): ?>
        <span class="gidx-chip"><?= gidx_icon('star') ?> Premium مفعَّل حتى <?= $premiumUntil ?></span>
      <?php elseif (!$isLoggedIn): ?>
        <a href="<?= h(url('google-indexing-api')) ?>?gidx_action=login" class="gidx-login-hero">
          <svg width="16" height="16" viewBox="0 0 24 24"><path fill="#fff" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#fff" fill-opacity=".8" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#fff" fill-opacity=".6" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#fff" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
          سجّل دخولك بـ Google لحفظ رصيدك
        </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$isLoggedIn): ?>
  <div class="gidx-tool-hint" style="display:block">
    <?= gidx_icon('lightbulb') ?> سجّل دخولك بحساب Google لتثبيت رصيدك على جميع أجهزتك ولتفعيل الميزات المتقدمة.
  </div>
  <?php endif; ?>

  <div class="gidx-panel">
    <h2><?= gidx_icon('key') ?> الخطوة 1 — ربط مفتاح Service Account</h2>

    <?php if ($storedKey): ?>
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:rgba(22,163,74,.06);border:1px solid rgba(22,163,74,.25);border-radius:12px;padding:14px 16px">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
      <div style="flex:1">
        <div style="font-weight:700;color:#16a34a;font-size:14px"><?= gidx_icon('check') ?> متصل ومحفوظ — لا حاجة لإعادة الرفع</div>
        <div style="font-size:12px;color:var(--text-muted);direction:ltr;text-align:left"><?= h($storedKey['client_email']) ?></div>
      </div>
      <button id="gidx-disconnect-btn" class="gidx-btn outline" style="width:auto;padding:7px 14px;font-size:12px">فصل الاتصال</button>
    </div>
    <?php else: ?>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px">
      اربط ملف <strong>Service Account JSON</strong> من Google Cloud Console ليتم حفظه مرة واحدة ويبقى متصلاً.
    </p>
    <label id="gidx-drop-zone" class="gidx-drop-zone">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      <div style="font-size:14px;font-weight:700;margin:6px 0 3px">اضغط لرفع ملف JSON</div>
      <div style="font-size:12px;color:var(--text-muted)">أو اسحبه وأفلته هنا — يُتحقق منه مع Google قبل الحفظ</div>
      <input type="file" id="gidx-key-file" accept=".json,application/json" style="display:none">
    </label>
    <div style="text-align:center;margin:8px 0;font-size:12px;color:var(--text-muted)">— أو الصق المحتوى —</div>
    <textarea id="gidx-json" class="gidx-input gidx-textarea" rows="3" dir="ltr" placeholder='{"type":"service_account","private_key":"...","client_email":"...'></textarea>
    <div style="margin-top:8px;display:flex;gap:8px">
      <button id="gidx-connect-btn" class="gidx-btn success" style="width:auto;padding:9px 20px"><?= gidx_icon('check') ?> تحقق واتصال دائم</button>
    </div>
    <div id="gidx-connect-msg" style="margin-top:8px;font-size:13px"></div>
    <?php endif; ?>

    <h2 style="margin-top:20px"><?= gidx_icon('edit') ?> الخطوة 2 — المسارات المراد فهرستها</h2>
    <textarea id="gidx-urls" class="gidx-input gidx-textarea" rows="5" dir="ltr" placeholder="https://yassota.com/chatgpt-apk&#10;https://yassota.com/sitemap.php"></textarea>
    <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
      <button id="gidx-import-btn" class="gidx-btn outline" style="width:auto;padding:9px 16px;font-size:13px"><?= gidx_icon('download') ?> استيراد من Sitemap.php</button>
      <button id="gidx-submit-btn" class="gidx-btn success" style="width:auto;padding:9px 20px;font-size:13px"><?= gidx_icon('rocket') ?> إرسال للفهرسة الآن</button>
    </div>
    <p style="font-size:11.5px;color:var(--text-muted);margin-top:8px"><?= gidx_icon('lightbulb') ?> استيراد Sitemap يجلب كل المسارات دفعة واحدة تلقائياً.</p>
    <div id="gidx-results" style="margin-top:14px"></div>
  </div>

  <?php
  $myTotal = 0; $mySuccess = 0; $myFailed = 0; $recent = [];
  try {
      $st = $pdo->prepare("SELECT status, COUNT(*) c FROM gidx_log WHERE identifier=? GROUP BY status");
      $st->execute([$identifier]);
      foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
          $myTotal += (int)$r['c'];
          if ($r['status'] === 'success') $mySuccess = (int)$r['c'];
          if ($r['status'] === 'error')   $myFailed  = (int)$r['c'];
      }
      $st2 = $pdo->prepare("SELECT url, status, message, created_at FROM gidx_log WHERE identifier=? ORDER BY created_at DESC LIMIT 8");
      $st2->execute([$identifier]);
      $recent = $st2->fetchAll(PDO::FETCH_ASSOC);
  } catch (\Throwable $e) {}
  ?>
  <div class="gidx-panel">
    <h2><?= gidx_icon('chart') ?> لوحة التحكم</h2>
    <div class="gidx-stats">
      <div class="gidx-stat"><div class="gidx-stat-val"><?= number_format($myTotal) ?></div><div class="gidx-stat-lbl">إجمالي الإرسال</div></div>
      <div class="gidx-stat"><div class="gidx-stat-val" style="color:#16a34a"><?= number_format($mySuccess) ?></div><div class="gidx-stat-lbl">فُهرست بنجاح</div></div>
      <div class="gidx-stat"><div class="gidx-stat-val" style="color:#ef4444"><?= number_format($myFailed) ?></div><div class="gidx-stat-lbl">فشل الإرسال</div></div>
      <div class="gidx-stat"><div class="gidx-stat-val" style="color:var(--primary)"><?= (int)($quota['limit'] - $quota['used']) ?></div><div class="gidx-stat-lbl">الرصيد المتبقي</div></div>
    </div>
    <?php if ($recent): ?>
    <div style="overflow-x:auto">
      <table class="gidx-table">
        <thead><tr><th>التاريخ</th><th>الحالة</th><th style="text-align:left">URL</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
        <tr>
          <td style="white-space:nowrap;color:var(--text-muted);font-size:11px"><?= h(date('m/d H:i', strtotime($r['created_at']))) ?></td>
          <td><?php if ($r['status']==='success'): ?><span class="gidx-badge-ok"><?= gidx_icon('check') ?> تم</span><?php else: ?><span class="gidx-badge-err" title="<?= h($r['message']) ?>"><?= gidx_icon('x') ?> فشل</span><?php endif; ?></td>
          <td style="direction:ltr;text-align:left;word-break:break-all;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><small><?= h($r['url']) ?></small></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div class="gidx-panel">
    <h2><?= gidx_icon('gem') ?> باقات الفهرسة</h2>
    <div class="gidx-pricing-grid">
      <div class="gidx-plan">
        <div class="gidx-plan-name">مجانية</div>
        <div class="gidx-plan-price"><sup>$</sup>0</div>
        <div class="gidx-plan-desc">للاستخدام الأساسي والمواقع الصغيرة</div>
        <ul class="gidx-plan-features">
          <li>100 عملية فهرسة</li>
          <li>استيراد من Sitemap</li>
          <li class="no">فهرسة غير محدودة</li>
          <li class="no">تقارير تفصيلية</li>
          <li class="no">دعم فني</li>
        </ul>
        <?php if (!$isLoggedIn): ?>
        <a href="<?= h(url('google-indexing-api')) ?>?gidx_action=login" class="gidx-btn outline">ابدأ مجاناً — تسجيل بـ Google</a>
        <?php else: ?>
        <div class="gidx-btn outline" style="cursor:default">خطتك الحالية</div>
        <?php endif; ?>
      </div>
      <div class="gidx-plan featured">
        <div class="gidx-plan-name"><?= gidx_icon('star') ?> احترافية</div>
        <div class="gidx-plan-price"><sup>$</sup>15</div>
        <div class="gidx-plan-desc">فهرسة غير محدودة لمدة شهر — دفع USDT عبر NOWPayments</div>
        <ul class="gidx-plan-features">
          <li>فهرسة <strong>غير محدودة</strong></li>
          <li>استيراد تلقائي من Sitemap</li>
          <li>تقارير فهرسة تفصيلية</li>
          <li>أولوية في قائمة الانتظار</li>
          <li>دعم فني مباشر</li>
        </ul>
        <?php if ($quota['premium_active']): ?>
        <div class="gidx-btn primary" style="cursor:default"><?= gidx_icon('check') ?> مفعَّل حتى <?= $premiumUntil ?></div>
        <?php elseif (!$isLoggedIn): ?>
        <a href="<?= h(url('google-indexing-api')) ?>?gidx_action=login&return=upgrade" class="gidx-btn primary">سجّل وادفع — $15</a>
        <?php else: ?>
        <button class="gidx-btn primary" onclick="openFeaturesModal()">اشترك الآن — $15 USDT</button>
        <?php endif; ?>
      </div>
    </div>
    <p style="font-size:11.5px;color:var(--text-muted);margin-top:10px">
      <?= gidx_icon('lock') ?> الدفع عبر شبكة USDT/ETH عبر NOWPayments — يُفعَّل الاشتراك تلقائياً خلال دقائق من تأكيد الشبكة.
    </p>
  </div>

  <article class="gidx-panel gidx-article">
    <h2>الدليل الكامل: فهرسة موقعك فوراً عبر Google Indexing API خطوة بخطوة</h2>

    <p>إذا كنت تدير موقعاً إلكترونياً وتنتظر أياماً حتى تظهر صفحاتك في نتائج بحث Google، فإن Google Indexing API ستغيّر هذا إلى الأبد. بدلاً من الاعتماد على زحف Googlebot العشوائي، تتيح لك هذه الواجهة الرسمية إخبار Google مباشرة بفهرسة صفحاتك فوراً.</p>

    <?php gidx_img_slot($pdo, 'slot1', 'واجهة Google Cloud Console'); ?>

    <h3>لماذا الفهرسة الفورية أهم مما تتخيل؟</h3>
    <p>زحف Google له أولويات وموارد محدودة. الصفحة المُحدَّثة حديثاً قد لا تُزحف لأسابيع، مما يعني ضياع فرص الترتيب في أهم نافذة زمنية — أول أيام نشر المحتوى. Indexing API يرسل إشعاراً مباشراً لخوادم Google بطلب الفهرسة الآن.</p>

    <?php gidx_img_slot($pdo, 'slot2', 'مخطط الفرق بين الزحف العادي والفهرسة الفورية'); ?>

    <h3>الخطوة 1: إنشاء مشروع في Google Cloud Console</h3>
    <p>زر <code>console.cloud.google.com</code> وأنشئ مشروعاً جديداً. اختر اسماً واضحاً مثل <code>yassota-indexing</code> ثم اضغط "إنشاء".</p>

    <h3>الخطوة 2: تفعيل Indexing API</h3>
    <p>من "مكتبة الواجهات البرمجية" ابحث عن <code>Indexing API</code> من Google واضغط "تفعيل". هذه الخطوة إلزامية — بدونها ستفشل جميع الطلبات.</p>

    <h3>الخطوة 3: إنشاء Service Account وتنزيل JSON</h3>
    <p>من "بيانات الاعتماد" ← "إنشاء بيانات اعتماد" ← "حساب خدمة"، أنشئ حساباً واضغط على "مفاتيح" ← "إضافة مفتاح" ← "JSON". الملف الذي يُنزَّل هو ما ستلصقه في الأداة أعلاه.</p>

    <h3>الخطوة 4: إضافة حساب الخدمة كمالك في Search Console</h3>
    <p>افتح Search Console ← موقعك ← الإعدادات ← "مستخدمون وأذونات" ← "إضافة مستخدم" ← الصق بريد حساب الخدمة (من <code>client_email</code> في ملف JSON) ← اختر <strong>"مالك" (Owner)</strong> تحديداً.</p>

    <h3>الخطوة 5: الاستخدام — الصق واستورد وأرسل</h3>
    <p>انسخ محتوى ملف JSON والصقه في الأداة أعلاه، ثم أضف روابط الصفحات أو استخدم زر "استيراد من Sitemap" لجلبها تلقائياً، ثم اضغط "إرسال للفهرسة الآن".</p>

    <h3>أسئلة شائعة</h3>
    <p><strong>هل Indexing API يعمل مع كل أنواع الصفحات؟</strong><br>
    رسمياً للوظائف والبث المباشر، لكن عملياً يقبل Google أي رابط من ملكية مُتحقق منها، وكثير من المواقع تستخدمها بنجاح لصفحات المقالات والتطبيقات.</p>

    <p><strong>ما الفرق بين هذه الأداة وإرسال Sitemap من Search Console؟</strong><br>
    Sitemap يخبر Google بقائمة روابط لتُزحفها حسب جدولها. Indexing API طلب فهرسة فوري ومباشر. الأفضل استخدام الاثنين معاً.</p>
  </article>

</div>

<footer class="gidx-footer">
  <div class="gidx-footer-inner">
    <a href="<?= h(url()) ?>" class="gidx-footer-logo">yass<span>ota</span></a>
    <div class="gidx-footer-links">
      <a href="<?= h(url('privacy-policy')) ?>">سياسة الخصوصية</a>
      <a href="<?= h(url('terms')) ?>">شروط الاستخدام</a>
      <a href="<?= h(url('contact')) ?>">تواصل معنا</a>
      <a href="<?= h(url('tools')) ?>">أدوات الويب</a>
    </div>
    <div class="gidx-footer-copy">© <?= date('Y') ?> yassota — أداة Google Indexing API</div>
  </div>
</footer>

<!-- MODAL 1: PREMIUM FEATURES -->
<div class="gidx-modal-overlay" id="features-overlay">
  <div class="gidx-modal-premium">
    <div class="gidx-modal-header">
      <div></div>
      <button class="gidx-modal-close" onclick="closeFeaturesModal()"><?= gidx_icon('x') ?></button>
    </div>
    <div class="gidx-modal-body">
      <div class="gidx-features-icon">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
          <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
        </svg>
      </div>
      <div class="gidx-features-title">باقة Premium — فهرسة بلا حدود</div>
      <div class="gidx-features-sub">شهر كامل بـ $15 فقط — ادفع عبر USDT ويُفعَّل تلقائياً</div>
      <div class="gidx-price-badge">
        <div class="big">$15 <span style="font-size:16px;opacity:.8">/ شهر</span></div>
        <div class="small">دفع آمن عبر USDT — لا بطاقة ائتمان</div>
      </div>
      <ul class="gidx-features-list">
        <li><span class="fi"><?= gidx_icon('zap') ?></span><div><strong>فهرسة غير محدودة</strong><br><span style="font-size:12px;color:var(--text-muted)">أرسل آلاف الروابط يومياً بدون قيود</span></div></li>
        <li><span class="fi"><?= gidx_icon('chart') ?></span><div><strong>تقارير تفصيلية</strong><br><span style="font-size:12px;color:var(--text-muted)">تتبع حالة كل رابط ومعدلات النجاح</span></div></li>
        <li><span class="fi"><?= gidx_icon('rocket') ?></span><div><strong>أولوية في الفهرسة</strong><br><span style="font-size:12px;color:var(--text-muted)">طلباتك تُعالَج بأولوية أعلى</span></div></li>
        <li><span class="fi"><?= gidx_icon('shield') ?></span><div><strong>دعم فني مباشر</strong><br><span style="font-size:12px;color:var(--text-muted)">تواصل معنا عند أي مشكلة تقنية</span></div></li>
        <li><span class="fi"><?= gidx_icon('phone') ?></span><div><strong>تفعيل فوري</strong><br><span style="font-size:12px;color:var(--text-muted)">يُفعَّل تلقائياً خلال دقائق من تأكيد الشبكة</span></div></li>
      </ul>
      <button class="gidx-btn primary" style="font-size:15px;padding:13px" onclick="closeFeaturesModal();openPaymentModal()">
        <?= gidx_icon('card') ?> اشترك الآن — $15 USDT
      </button>
    </div>
  </div>
</div>

<!-- MODAL 2: PAYMENT -->
<div class="gidx-modal-overlay" id="payment-overlay">
  <div class="gidx-payment-modal">
    <div class="gidx-pay-timer-bar" id="pay-timer-bar">
      ⏱ الوقت المتبقي: <span class="timer-val" id="pay-timer-val">20:00</span>
    </div>
    <div id="pay-loading" style="padding:40px;text-align:center">
      <div class="gidx-spinner"></div>
      <div style="margin-top:12px;font-size:13px;color:var(--text-muted)">جارٍ إنشاء فاتورة الدفع...</div>
    </div>
    <div class="gidx-pay-step1" id="pay-step1" style="display:none">
      <div class="gidx-pay-steps" style="margin:-20px -20px 16px;border-radius:0">
        <div class="gidx-pay-step active" id="tab-step1">1. اختيار العملة</div>
        <div class="gidx-pay-step"        id="tab-step2">2. إرسال المبلغ</div>
      </div>
      <div class="gidx-pay-title">Pay with crypto</div>
      <select class="gidx-currency-sel" id="pay-currency-sel">
        <option value="usdttrc20">USDT (TRC20 — Tron)</option>
        <option value="usdterc20">USDT (ERC20 — Ethereum)</option>
        <option value="eth">ETH — Ethereum</option>
        <option value="btc">BTC — Bitcoin</option>
        <option value="ltc">LTC — Litecoin</option>
        <option value="bnb">BNB — Binance Smart Chain</option>
      </select>
      <div class="gidx-pay-amount-info" id="pay-amount-preview" style="display:none">
        <div class="gidx-pay-amount-label">Amount to pay</div>
        <div class="gidx-pay-amount-val" id="pay-amount-val">—</div>
        <div class="gidx-pay-amount-usd">~ $15</div>
      </div>
      <button class="gidx-next-btn" id="pay-next-btn" onclick="goToStep2()">
        Next step <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>
    <div class="gidx-pay-step2" id="pay-step2">
      <div class="gidx-pay-steps" style="margin:-16px -16px 14px;border-radius:0">
        <div class="gidx-pay-step done" id="tab2-step1">1. اختيار العملة</div>
        <div class="gidx-pay-step active" id="tab2-step2">2. إرسال المبلغ</div>
      </div>
      <div style="margin-bottom:10px">
        <div class="gidx-pay-detail-label">Amount ~ $15</div>
        <div style="display:flex;align-items:center;gap:8px">
          <div class="gidx-pay-detail-val" style="font-size:18px" id="s2-amount"></div>
          <button class="gidx-copy-btn" onclick="copyText(document.getElementById('s2-amount-raw').value)" title="نسخ">⧉</button>
        </div>
        <input type="hidden" id="s2-amount-raw">
      </div>
      <div style="margin-bottom:10px">
        <div class="gidx-pay-detail-label">Address</div>
        <div class="gidx-address-box" id="s2-address">
          <button class="gidx-address-copy" onclick="copyText(document.getElementById('s2-address-raw').value)">نسخ</button>
          <span id="s2-address-text"></span>
        </div>
        <input type="hidden" id="s2-address-raw">
      </div>
      <div class="gidx-qr-tabs">
        <button class="gidx-qr-tab active" onclick="switchQrTab('address')">Address</button>
        <button class="gidx-qr-tab" onclick="switchQrTab('amount')">With amount</button>
      </div>
      <div class="gidx-qr-container" id="qr-container">
        <div class="gidx-spinner"></div>
      </div>
      <div style="margin-top:10px">
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px">Deposit with</div>
        <div style="display:flex;gap:8px">
          <a id="metamask-link" href="#" target="_blank" style="width:44px;height:44px;border:1px solid var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;text-decoration:none" title="MetaMask">
            <svg width="26" height="26" viewBox="0 0 318.6 318.6" xmlns="http://www.w3.org/2000/svg"><polygon fill="#E2761B" points="274.1,35.5 174.6,109.4 193,65.8"/><polygon fill="#E4761B" points="44.4,35.5 143.1,110.1 125.6,65.8"/><polygon fill="#D7C1B3" points="238.3,206.8 211.8,247.4 268.5,263 284.8,207.7"/><polygon fill="#233447" points="33.9,207.7 50.1,263 106.8,247.4 80.3,206.8"/><polygon fill="#CD6116" points="103.6,138.2 87.8,162.1 144.1,164.6 142.1,104.1"/><polygon fill="#E4751F" points="214.9,138.2 175.9,103.4 174.6,164.6 230.8,162.1"/><polygon fill="#F6851B" points="106.8,247.4 140.6,230.9 111.4,208.1"/><polygon fill="#F6851B" points="177.9,230.9 211.8,247.4 207.1,208.1"/></svg>
          </a>
        </div>
      </div>
      <div class="gidx-pay-status waiting" id="pay-status-box">⏳ بانتظار تأكيد الشبكة...</div>
      <button class="gidx-cancel-btn" onclick="cancelPayment()">إلغاء العملية</button>
    </div>
    <div class="gidx-pay-logo">Powered by <strong>NOW</strong>Payments</div>
  </div>
</div>

<script>
var paymentData = null;
var paymentTimerInterval = null;
var paymentPollInterval  = null;
var paymentTimeLeft      = 20 * 60;
var qrInstance           = null;
var currentQrTab         = 'address';

var profileWrap = document.getElementById('gidx-profile-wrap');
var profileBtn  = document.getElementById('gidx-profile-btn');
if (profileBtn) {
  profileBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    profileWrap.classList.toggle('open');
  });
  document.addEventListener('click', function() {
    profileWrap.classList.remove('open');
  });
}

function openFeaturesModal() {
  document.getElementById('features-overlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeFeaturesModal() {
  document.getElementById('features-overlay').classList.remove('active');
  document.body.style.overflow = '';
}
document.getElementById('features-overlay').addEventListener('click', function(e) {
  if (e.target === this) closeFeaturesModal();
});

function openPaymentModal() {
  document.getElementById('pay-loading').style.display = 'block';
  document.getElementById('pay-step1').style.display   = 'none';
  document.getElementById('pay-step2').style.display   = 'none';
  document.getElementById('payment-overlay').classList.add('active');
  document.body.style.overflow = 'hidden';
  clearInterval(paymentTimerInterval);
  clearInterval(paymentPollInterval);
  paymentTimeLeft = 20 * 60;
  updateTimerDisplay();
  var currency = document.getElementById('pay-currency-sel') ? document.getElementById('pay-currency-sel').value : 'usdttrc20';
  var fd = new FormData();
  fd.append('currency', currency);
  fetch('?gidx_action=checkout', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      document.getElementById('pay-loading').style.display = 'none';
      if (!data.ok) { showPaymentError(data.error || 'حدث خطأ غير متوقع.'); return; }
      paymentData = data;
      fillStep1(data);
      document.getElementById('pay-step1').style.display = 'block';
      startTimer();
    })
    .catch(function() {
      document.getElementById('pay-loading').style.display = 'none';
      showPaymentError('تعذّر الاتصال بالسيرفر. حاول مجدداً.');
    });
}
function showPaymentError(msg) {
  document.getElementById('pay-loading').innerHTML =
    '<div style="padding:20px;text-align:center;color:#ef4444;font-size:13px">✗ ' + msg +
    '<br><br><button class="gidx-cancel-btn" onclick="closePaymentModal()">إغلاق</button></div>';
}
function closePaymentModal() {
  document.getElementById('payment-overlay').classList.remove('active');
  document.body.style.overflow = '';
  clearInterval(paymentTimerInterval);
  clearInterval(paymentPollInterval);
}
document.getElementById('payment-overlay').addEventListener('click', function(e) {
  if (e.target === this) closePaymentModal();
});

function fillStep1(data) {
  var amtEl = document.getElementById('pay-amount-val');
  var preview = document.getElementById('pay-amount-preview');
  if (amtEl && data.pay_amount) {
    amtEl.textContent = data.pay_amount + ' ' + (data.pay_currency || '').toUpperCase();
    preview.style.display = 'block';
  }
}

var curSel = document.getElementById('pay-currency-sel');
if (curSel) {
  curSel.addEventListener('change', function() {
    if (!paymentData) return;
    var fd = new FormData();
    fd.append('currency', this.value);
    document.getElementById('pay-amount-val').textContent = '...';
    fetch('?gidx_action=checkout', { method: 'POST', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(data) { if (data.ok) { paymentData = data; fillStep1(data); } });
  });
}

function goToStep2() {
  if (!paymentData) return;
  document.getElementById('pay-step1').style.display = 'none';
  var step2 = document.getElementById('pay-step2');
  step2.style.display = 'block';
  var amount  = paymentData.pay_amount + ' ' + (paymentData.pay_currency || '').toUpperCase();
  var address = paymentData.pay_address || '';
  document.getElementById('s2-amount').textContent      = amount;
  document.getElementById('s2-amount-raw').value        = paymentData.pay_amount;
  document.getElementById('s2-address-text').textContent = address;
  document.getElementById('s2-address-raw').value       = address;
  generateQR(address);
  pollPaymentStatus(paymentData.order_id);
}

function generateQR(data) {
  var container = document.getElementById('qr-container');
  container.innerHTML = '';
  if (typeof QRCode !== 'undefined') {
    try {
      qrInstance = new QRCode(container, { text: data, width: 180, height: 180,
        colorDark: '#0f172a', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
    } catch(e) {
      container.innerHTML = '<div style="font-size:12px;color:var(--text-muted)">جارٍ تحميل QR...</div>';
    }
  } else {
    var img = document.createElement('img');
    img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent(data);
    img.style.borderRadius = '8px';
    container.appendChild(img);
  }
}

function switchQrTab(tab) {
  currentQrTab = tab;
  var tabs = document.querySelectorAll('.gidx-qr-tab');
  tabs.forEach(function(t, i) {
    t.classList.toggle('active', (i === 0 && tab === 'address') || (i === 1 && tab === 'amount'));
  });
  if (!paymentData) return;
  generateQR(tab === 'address' ? paymentData.pay_address : paymentData.pay_address + '?amount=' + paymentData.pay_amount);
}

function startTimer() {
  clearInterval(paymentTimerInterval);
  paymentTimerInterval = setInterval(function() {
    paymentTimeLeft--;
    updateTimerDisplay();
    if (paymentTimeLeft <= 0) {
      clearInterval(paymentTimerInterval);
      var sb = document.getElementById('pay-status-box');
      if (sb) { sb.className = 'gidx-pay-status failed'; sb.textContent = '⌛ انتهت مهلة الدفع — يمكنك إعادة المحاولة.'; }
    }
  }, 1000);
}
function updateTimerDisplay() {
  var m = Math.floor(paymentTimeLeft / 60);
  var s = paymentTimeLeft % 60;
  var el = document.getElementById('pay-timer-val');
  if (el) el.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
}

function pollPaymentStatus(orderId) {
  clearInterval(paymentPollInterval);
  var tries = 0;
  paymentPollInterval = setInterval(function() {
    tries++;
    fetch('?gidx_action=payment_status&order_id=' + encodeURIComponent(orderId))
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (!res.ok) return;
        var sb = document.getElementById('pay-status-box');
        if (!sb) return;
        if (res.status === 'finished' || res.status === 'confirmed') {
          clearInterval(paymentPollInterval); clearInterval(paymentTimerInterval);
          sb.className = 'gidx-pay-status confirmed';
          sb.innerHTML = '✓ تم تأكيد الدفع — جارٍ تفعيل اشتراكك...';
          setTimeout(function() { window.location.reload(); }, 2500);
        } else if (res.status === 'failed' || res.status === 'expired') {
          clearInterval(paymentPollInterval);
          sb.className = 'gidx-pay-status failed';
          sb.innerHTML = '✗ فشلت العملية أو انتهت مهلتها.';
        } else {
          sb.textContent = '⏳ بانتظار تأكيد الشبكة... (' + res.status + ')';
        }
      }).catch(function() {});
    if (tries > 120) clearInterval(paymentPollInterval);
  }, 10000);
}

function cancelPayment() {
  clearInterval(paymentPollInterval);
  clearInterval(paymentTimerInterval);
  closePaymentModal();
}

function copyText(text) {
  if (!text) return;
  navigator.clipboard.writeText(text).then(function() {
    var btn = event.target.closest('button');
    if (btn) {
      var orig = btn.textContent;
      btn.innerHTML = '✓ تم النسخ'; btn.style.color = '#16a34a';
      setTimeout(function() { btn.textContent = orig; btn.style.color = ''; }, 1500);
    }
  }).catch(function() {
    var ta = document.createElement('textarea');
    ta.value = text; document.body.appendChild(ta); ta.select();
    document.execCommand('copy'); document.body.removeChild(ta);
  });
}

(function() {
  var jsonEl        = document.getElementById('gidx-json');
  var fileEl        = document.getElementById('gidx-key-file');
  var dropZone      = document.getElementById('gidx-drop-zone');
  var connectBtn    = document.getElementById('gidx-connect-btn');
  var connectMsg    = document.getElementById('gidx-connect-msg');
  var disconnectBtn = document.getElementById('gidx-disconnect-btn');
  var urlsEl        = document.getElementById('gidx-urls');
  var importBtn     = document.getElementById('gidx-import-btn');
  var submitBtn     = document.getElementById('gidx-submit-btn');
  var resultsEl     = document.getElementById('gidx-results');

  function doConnect(fd) {
    if (!connectBtn) return;
    connectBtn.disabled = true; connectBtn.textContent = '⏳ جاري التحقق...';
    if (connectMsg) { connectMsg.style.color = 'var(--text-muted)'; connectMsg.textContent = ''; }
    fetch('?ajax=connect_key', { method: 'POST', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        connectBtn.disabled = false; connectBtn.innerHTML = '✓ تحقق واتصال دائم';
        if (!d.ok) { if (connectMsg) { connectMsg.style.color = '#ef4444'; connectMsg.textContent = d.error; } return; }
        window.location.reload();
      })
      .catch(function() {
        connectBtn.disabled = false; connectBtn.innerHTML = '✓ تحقق واتصال دائم';
        if (connectMsg) { connectMsg.style.color = '#ef4444'; connectMsg.textContent = 'خطأ في الاتصال'; }
      });
  }

  if (dropZone && fileEl) {
    dropZone.addEventListener('click', function(e) { if (e.target !== fileEl) fileEl.click(); });
    fileEl.addEventListener('change', function() {
      if (!this.files[0]) return;
      var fd = new FormData(); fd.append('key_file', this.files[0]); doConnect(fd);
    });
    dropZone.addEventListener('dragover', function(e) { e.preventDefault(); this.style.borderColor = '#16a34a'; });
    dropZone.addEventListener('dragleave', function() { this.style.borderColor = ''; });
    dropZone.addEventListener('drop', function(e) {
      e.preventDefault(); this.style.borderColor = '';
      if (e.dataTransfer.files[0]) { var fd = new FormData(); fd.append('key_file', e.dataTransfer.files[0]); doConnect(fd); }
    });
  }
  if (connectBtn) {
    connectBtn.addEventListener('click', function() {
      var txt = jsonEl ? jsonEl.value.trim() : '';
      if (!txt) { if (connectMsg) { connectMsg.style.color = '#ef4444'; connectMsg.textContent = 'ارفع ملفاً أو الصق النص أولاً'; } return; }
      var fd = new FormData(); fd.append('key_text', txt); doConnect(fd);
    });
  }
  if (disconnectBtn) {
    disconnectBtn.addEventListener('click', function() {
      if (!confirm('فصل المفتاح المتصل؟ ستحتاج لإعادة رفعه لاحقاً.')) return;
      fetch('?ajax=disconnect_key').then(function() { window.location.reload(); });
    });
  }
  if (importBtn) {
    importBtn.addEventListener('click', function() {
      var btn = this; btn.disabled = true; btn.textContent = '⏳ جاري الاستيراد...';
      fetch('?ajax=import_sitemap')
        .then(function(r) { return r.json(); })
        .then(function(d) {
          btn.disabled = false;
          if (d.ok && urlsEl) { urlsEl.value = d.urls.join('\n'); btn.innerHTML = '✓ ' + d.urls.length + ' رابط'; }
          else { btn.innerHTML = '✗ ' + (d.error || 'فشل الاستيراد'); }
          setTimeout(function() { btn.innerHTML = '⬇ استيراد من Sitemap.php'; }, 3000);
        }).catch(function() { btn.disabled = false; btn.innerHTML = '✗ خطأ في الاتصال'; });
    });
  }
  if (submitBtn) {
    submitBtn.addEventListener('click', function() {
      var btn = this;
      var jsonKey = jsonEl ? jsonEl.value.trim() : '';
      var urls    = urlsEl ? urlsEl.value.trim() : '';
      if (!urls) { if (resultsEl) resultsEl.innerHTML = '<div class="gidx-alert error">أضف رابطاً واحداً على الأقل</div>'; return; }
      btn.disabled = true; btn.textContent = '⏳ جاري الإرسال...';
      var fd = new FormData();
      fd.append('json_key', jsonKey); fd.append('urls', urls);
      fetch('?ajax=submit', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
          btn.disabled = false; btn.innerHTML = '🚀 إرسال للفهرسة الآن';
          if (!resultsEl) return;
          if (!d.ok) { resultsEl.innerHTML = '<div class="gidx-alert error">' + d.error + '</div>'; return; }
          var html = '<table class="gidx-table"><thead><tr><th>الحالة</th><th style="text-align:left">URL</th></tr></thead><tbody>';
          d.results.forEach(function(r) {
            html += '<tr><td>' + (r.ok
              ? '<span class="gidx-badge-ok">✓ تم</span>'
              : '<span class="gidx-badge-err" title="' + r.message + '">✗ فشل</span>') + '</td>'
              + '<td style="direction:ltr;text-align:left;word-break:break-all;font-size:11px">' + r.url + '</td></tr>';
          });
          html += '</tbody></table><div style="font-size:12px;color:var(--text-muted);margin-top:6px">الرصيد المتبقي: ' + d.remaining + '</div>';
          resultsEl.innerHTML = html;
        })
        .catch(function() {
          btn.disabled = false; btn.innerHTML = '🚀 إرسال للفهرسة الآن';
          if (resultsEl) resultsEl.innerHTML = '<div class="gidx-alert error">خطأ في الاتصال</div>';
        });
    });
  }
})();

<?php if ($showPremiumModal && $isLoggedIn && !$quota['premium_active']): ?>
window.addEventListener('load', function() {
  setTimeout(openFeaturesModal, 600);
});
<?php endif; ?>
</script>

<!-- QR code library (lazy load) -->
<script>
if (document.getElementById('payment-overlay')) {
  document.getElementById('payment-overlay').addEventListener('click', function() {}, {once:false});
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
</body>
</html>
