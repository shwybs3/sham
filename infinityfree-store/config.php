<?php
/* ═══════════════════════════════════════════════
   متجر يسوتا — config.php
   نسخة مختصرة مخصصة لاستضافة InfinityFree المجانية.
   لا تُكتب بيانات الاتصال الحقيقية هنا أبداً — يقوم
   المعالج أدناه بإنشاء config.local.php (مستبعد من
   git عبر .gitignore) عند أول زيارة للموقع.
   ═══════════════════════════════════════════════ */

if (!defined('IFS_CONFIG_LOADED')) define('IFS_CONFIG_LOADED', true);
define('IFS_ROOT', __DIR__);

date_default_timezone_set('Asia/Riyadh');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
if (session_status() === PHP_SESSION_NONE) session_start();

/* ── هل تم الإعداد؟ اقرأ config.local.php إن وُجد ── */
$__local = IFS_ROOT . '/config.local.php';
if (file_exists($__local)) require $__local;

/* لم يتم الإعداد بعد: اعرض معالج تثبيت من خطوة واحدة وتوقف */
if (!defined('DB_HOST')) {
    ifs_setup_wizard();
    exit;
}

define('UPLOAD_PATH', IFS_ROOT . '/uploads');

/* ── الاتصال بقاعدة البيانات ── */
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(503);
    die('<!doctype html><meta charset="utf-8"><body style="font-family:Tahoma,sans-serif;max-width:640px;margin:60px auto;padding:0 16px;direction:rtl;color:#111">'
        . '<h2 style="color:#c0392b">تعذر الاتصال بقاعدة البيانات</h2>'
        . '<p>تحقق من بيانات الاتصال في <code>config.local.php</code>. إذا كانت خاطئة احذف الملف وأعد فتح الموقع لإعادة الإعداد.</p>'
        . '<pre style="background:#f3f3f3;padding:12px;border-radius:8px;white-space:pre-wrap;direction:ltr;text-align:left">' . htmlspecialchars($e->getMessage()) . '</pre></body>');
}

ifs_ensure_schema($pdo);

/* ═══ المخطط الذاتي الإصلاح — لا حاجة لاستيراد أي SQL يدوياً ═══ */
function ifs_ensure_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
      k VARCHAR(80) PRIMARY KEY,
      v TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS packages (
      id INT AUTO_INCREMENT PRIMARY KEY,
      category VARCHAR(60) NOT NULL DEFAULT 'عام',
      name VARCHAR(160) NOT NULL,
      description VARCHAR(400) NOT NULL DEFAULT '',
      price_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
      active TINYINT NOT NULL DEFAULT 1,
      sort_order INT NOT NULL DEFAULT 0,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
      id INT AUTO_INCREMENT PRIMARY KEY,
      order_id VARCHAR(64) NOT NULL UNIQUE,
      package_id INT NULL,
      package_name VARCHAR(160) NOT NULL DEFAULT '',
      quantity INT NOT NULL DEFAULT 1,
      target_info VARCHAR(255) NOT NULL DEFAULT '',
      contact VARCHAR(160) NOT NULL DEFAULT '',
      price_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
      pay_currency VARCHAR(20) NOT NULL DEFAULT '',
      pay_address VARCHAR(255) NOT NULL DEFAULT '',
      pay_amount DECIMAL(24,8) NOT NULL DEFAULT 0,
      actually_paid DECIMAL(24,8) NOT NULL DEFAULT 0,
      payment_id VARCHAR(64) NOT NULL DEFAULT '',
      status VARCHAR(24) NOT NULL DEFAULT 'pending',
      fulfilled TINYINT NOT NULL DEFAULT 0,
      raw_ipn_json TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if ((int)$pdo->query("SELECT COUNT(*) c FROM packages")->fetch()['c'] === 0) {
        $seed = [
            ['انستقرام', 'متابعين انستقرام حقيقيين — 1000', 'متابعين عرب/عالميين، جودة عالية، بدون كلمة مرور', 4.00],
            ['انستقرام', 'لايكات انستقرام — 1000', 'تسليم سريع على آخر منشوراتك', 1.50],
            ['فيسبوك', 'متابعين صفحة فيسبوك — 1000', 'متابعين حقيقيين لصفحتك أو بروفايلك', 4.50],
            ['يوتيوب', 'مشاهدات يوتيوب — 1000', 'مشاهدات آمنة تدريجية لا تخالف سياسة يوتيوب', 2.00],
            ['يوتيوب', 'مشتركين يوتيوب — 100', 'اشتراكات حقيقية تدريجية', 6.00],
            ['تيليجرام', 'تيليجرام بريميوم — شهر', 'تفعيل Telegram Premium لحسابك لمدة شهر كامل', 8.00],
            ['تيليجرام', 'أعضاء قناة تيليجرام — 1000', 'أعضاء حقيقيين لقناتك أو مجموعتك', 3.50],
            ['أمن سيبراني', 'فحص أمني لموقعك (تقرير)', 'فحص ثغرات أساسي + تقرير توصيات خلال 48 ساعة', 15.00],
            ['أمن سيبراني', 'استشارة حماية حساب', 'مساعدة لتأمين حساباتك (2FA، كلمات مرور، تسريبات)', 10.00],
        ];
        $ins = $pdo->prepare("INSERT INTO packages (category, name, description, price_usd, sort_order) VALUES (?,?,?,?,?)");
        foreach ($seed as $i => $p) $ins->execute([$p[0], $p[1], $p[2], $p[3], $i]);
    }

    $defaults = [
        'site_title' => 'متجر يسوتا للخدمات الرقمية',
        'site_desc'  => 'زيادة متابعين انستقرام وفيسبوك ويوتيوب، تيليجرام بريميوم، وأدوات أمن سيبراني بأسعار مناسبة — دفع فوري وآمن بالعملات الرقمية.',
        'contact'    => '',
        'nowpayments_api_key' => '',
        'nowpayments_ipn_secret' => '',
        'openrouter_api_key' => '',
    ];
    $existing = $pdo->query("SELECT k FROM settings")->fetchAll(PDO::FETCH_COLUMN);
    $ins = $pdo->prepare("INSERT INTO settings (k, v) VALUES (?, ?)");
    foreach ($defaults as $k => $v) if (!in_array($k, $existing, true)) $ins->execute([$k, $v]);
}

/* ═══ إعدادات ═══ */
$GLOBALS['__settings_cache'] = null;
function setting(string $key, string $default = ''): string {
    global $pdo;
    if ($GLOBALS['__settings_cache'] === null) {
        $GLOBALS['__settings_cache'] = $pdo->query("SELECT k, v FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $GLOBALS['__settings_cache'][$key] ?? $default;
}
function set_setting(string $key, string $value): void {
    global $pdo;
    $pdo->prepare("INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)")->execute([$key, $value]);
    if (is_array($GLOBALS['__settings_cache'])) $GLOBALS['__settings_cache'][$key] = $value;
}

/* ═══ أدوات عامة ═══ */
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function site_url(string $path = ''): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $scheme . '://' . $host . $base . '/' . ltrim($path, '/');
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}
function is_admin(): bool { return !empty($_SESSION['ifs_admin']); }
function require_admin(): void {
    if (!is_admin()) { header('Location: admin.php'); exit; }
}

/* ═══ NOWPayments (دفع بالعملات الرقمية) ═══ */
const NP_CURRENCIES = [
    'usdttrc20' => 'Tether · TRC20 (USDT)', 'usdterc20' => 'Tether · ERC20 (USDT)',
    'btc' => 'Bitcoin (BTC)', 'eth' => 'Ethereum (ETH)', 'ltc' => 'Litecoin (LTC)',
    'trx' => 'Tron (TRX)', 'bnbbsc' => 'BNB Smart Chain (BNB)', 'doge' => 'Dogecoin (DOGE)',
];
function np_configured(): bool { return setting('nowpayments_api_key') !== ''; }
function np_create_payment(float $priceUsd, string $currency, string $orderId, string $desc): array {
    if (!np_configured()) return ['ok' => false, 'error' => 'الدفع بالعملات الرقمية غير مُفعّل بعد من لوحة الإدارة.'];
    if (!array_key_exists($currency, NP_CURRENCIES)) return ['ok' => false, 'error' => 'عملة غير مدعومة.'];
    $ch = curl_init('https://api.nowpayments.io/v1/payment');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_POSTFIELDS => json_encode([
            'price_amount' => $priceUsd, 'price_currency' => 'usd', 'pay_currency' => $currency,
            'order_id' => $orderId, 'order_description' => $desc,
            'ipn_callback_url' => site_url('webhook.php'),
        ]),
        CURLOPT_HTTPHEADER => ['x-api-key: ' . setting('nowpayments_api_key'), 'Content-Type: application/json'],
    ]);
    $res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    if ($res === false) return ['ok' => false, 'error' => 'تعذر الوصول إلى NOWPayments: ' . $err];
    $data = json_decode($res, true);
    if ($code < 200 || $code >= 300 || empty($data['pay_address'])) {
        return ['ok' => false, 'error' => 'تعذر إنشاء الفاتورة: ' . ($data['message'] ?? "HTTP $code")];
    }
    return ['ok' => true, 'pay_address' => $data['pay_address'], 'pay_amount' => (float)($data['pay_amount'] ?? 0),
        'pay_currency' => $data['pay_currency'] ?? $currency, 'payment_id' => $data['payment_id'] ?? ''];
}
function np_verify_ipn(string $rawBody, string $sig): bool {
    $secret = setting('nowpayments_ipn_secret');
    if ($secret === '' || $sig === '') return false;
    $data = json_decode($rawBody, true);
    if (!is_array($data)) return false;
    ksort($data);
    $canonical = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return hash_equals(hash_hmac('sha512', $canonical, $secret), $sig);
}

/* ═══ OpenRouter (روبوت الدردشة المساعد — نماذج مجانية) ═══ */
const OR_FREE_MODELS = [
    'meta-llama/llama-3.3-70b-instruct:free', 'google/gemini-2.0-flash-exp:free',
    'deepseek/deepseek-chat-v3.1:free', 'openrouter/auto',
];
function or_configured(): bool { return setting('openrouter_api_key') !== ''; }
function or_chat(array $messages): array {
    if (!or_configured()) return ['ok' => false, 'error' => 'روبوت الدردشة غير مُفعّل — أضف مفتاح OpenRouter من لوحة الإدارة.'];
    foreach (OR_FREE_MODELS as $model) {
        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => json_encode(['model' => $model, 'messages' => $messages]),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . setting('openrouter_api_key'), 'Content-Type: application/json',
                'HTTP-Referer: ' . site_url(), 'X-Title: ' . setting('site_title'),
            ],
        ]);
        $body = curl_exec($ch); curl_close($ch);
        $json = json_decode((string)$body, true);
        $text = $json['choices'][0]['message']['content'] ?? null;
        if ($text) return ['ok' => true, 'text' => $text];
    }
    return ['ok' => false, 'error' => 'كل نماذج OpenRouter المجانية مشغولة الآن، حاول لاحقاً أو تواصل معنا مباشرة.'];
}

/* ═══ معالج الإعداد الأولي (خطوة واحدة) ═══ */
function ifs_setup_wizard(): void {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $host = trim($_POST['db_host'] ?? '');
        $name = trim($_POST['db_name'] ?? '');
        $user = trim($_POST['db_user'] ?? '');
        $pass = (string)($_POST['db_pass'] ?? '');
        $adminUser = trim($_POST['admin_user'] ?? '') ?: 'admin';
        $adminPass = (string)($_POST['admin_pass'] ?? '');

        if ($host === '' || $name === '' || $user === '') {
            $err = 'يرجى تعبئة بيانات قاعدة البيانات (المضيف، الاسم، المستخدم).';
        } elseif (strlen($adminPass) < 6) {
            $err = 'كلمة مرور المدير يجب أن تكون 6 أحرف على الأقل.';
        } else {
            try {
                $test = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]);
                $secret = bin2hex(random_bytes(16));
                $php = "<?php\n"
                    . "define('DB_HOST', " . var_export($host, true) . ");\n"
                    . "define('DB_NAME', " . var_export($name, true) . ");\n"
                    . "define('DB_USER', " . var_export($user, true) . ");\n"
                    . "define('DB_PASS', " . var_export($pass, true) . ");\n"
                    . "define('APP_SECRET', " . var_export($secret, true) . ");\n";
                file_put_contents(IFS_ROOT . '/config.local.php', $php);
                @chmod(IFS_ROOT . '/config.local.php', 0600);

                ifs_ensure_schema($test);
                $ins = $test->prepare("INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)");
                $ins->execute(['admin_user', $adminUser]);
                $ins->execute(['admin_pass_hash', password_hash($adminPass, PASSWORD_DEFAULT)]);

                header('Location: admin.php');
                exit;
            } catch (Throwable $e) {
                $err = 'فشل الاتصال بقاعدة البيانات: ' . $e->getMessage();
            }
        }
    }
    $host = e($_POST['db_host'] ?? 'sqlXXX.infinityfree.com');
    $name = e($_POST['db_name'] ?? 'if0_XXXXXXXX_store');
    $user = e($_POST['db_user'] ?? 'if0_XXXXXXXX');
    ?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>إعداد المتجر — الخطوة الأولى</title>
    <style>
    body{margin:0;font-family:Tahoma,Arial,sans-serif;background:#0f172a;color:#e2e8f0;padding:40px 16px}
    .box{max-width:480px;margin:0 auto;background:#1e293b;border-radius:16px;padding:28px;box-shadow:0 10px 40px rgba(0,0,0,.4)}
    h1{font-size:20px;margin:0 0 6px;color:#fff} p.sub{color:#94a3b8;font-size:13px;margin:0 0 22px;line-height:1.8}
    label{display:block;font-size:13px;margin:14px 0 5px;color:#cbd5e1}
    input{width:100%;box-sizing:border-box;padding:10px 12px;border-radius:8px;border:1px solid #334155;background:#0f172a;color:#fff;font-size:14px}
    button{width:100%;margin-top:22px;padding:12px;border:0;border-radius:10px;background:#22c55e;color:#052e16;font-weight:bold;font-size:15px;cursor:pointer}
    .err{background:#450a0a;border:1px solid #991b1b;color:#fecaca;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px}
    hr{border:0;border-top:1px solid #334155;margin:20px 0}
    </style></head><body><div class="box">
    <h1>🛒 إعداد المتجر</h1>
    <p class="sub">أدخل بيانات قاعدة بيانات MySQL من لوحة InfinityFree (تجدها في: MySQL Databases). لن تُحفظ هذه البيانات في مستودع الأكواد — تُكتب فقط في ملف محلي على استضافتك.</p>
    <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
    <form method="post">
      <label>MySQL Hostname</label>
      <input name="db_host" value="<?= $host ?>" required>
      <label>MySQL Database Name</label>
      <input name="db_name" value="<?= $name ?>" required>
      <label>MySQL Username</label>
      <input name="db_user" value="<?= $user ?>" required>
      <label>MySQL Password</label>
      <input name="db_pass" type="password" required>
      <hr>
      <label>اسم مستخدم المدير (لوحة الإدارة)</label>
      <input name="admin_user" value="admin" required>
      <label>كلمة مرور المدير</label>
      <input name="admin_pass" type="password" minlength="6" required>
      <button type="submit">إنشاء المتجر</button>
    </form>
    </div></body></html><?php
}
