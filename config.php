<?php
/* ═══════════════════════════════════════════════
   YASSOTA  — config.php
   عدّل هذا الملف فقط ببيانات استضافتك
   ═══════════════════════════════════════════════ */

define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

define('SITE_URL',    'https://example.com');   // بدون / في النهاية
define('SITE_NAME',   'yassota');
define('APP_SECRET',  'change-me-to-random-string-xyz789');

define('MAX_ICON_MB',  5);
define('MAX_APK_MB',   500);
define('ROOT_PATH',    __DIR__);
define('UPLOAD_PATH',  __DIR__ . '/uploads');
define('UPLOAD_URL',   SITE_URL . '/uploads');

date_default_timezone_set('Asia/Riyadh');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

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
    $dbErr = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    @file_put_contents(__DIR__ . '/db-error.log', '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    die('<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>فشل الاتصال بقاعدة البيانات</title>
    <style>body{margin:0;font-family:Tahoma,Arial,sans-serif;background:#03080f;color:#e8f4f8;padding:40px 16px;line-height:1.9}
    .box{max-width:680px;margin:0 auto;background:#0a1628;border:1px solid rgba(255,68,102,.3);border-radius:14px;padding:32px}
    h1{color:#ff4466;font-size:20px;margin:0 0 6px} p.sub{color:#7a9ab8;font-size:13px;margin:0 0 20px}
    ol{padding-inline-start:20px;font-size:14px} li{margin-bottom:10px}
    code{background:#152642;color:#00f5ff;padding:2px 8px;border-radius:6px;font-family:monospace;direction:ltr;display:inline-block}
    .err{margin-top:22px;background:#152642;border:1px solid rgba(255,68,102,.25);border-radius:10px;padding:14px;font-family:monospace;font-size:12px;color:#ff8fa3;direction:ltr;text-align:left;word-break:break-all}
    </style></head><body><div class="box">
    <h1>تعذر الاتصال بقاعدة البيانات</h1>
    <p class="sub">اتبع الخطوات التالية للحل:</p>
    <ol>
    <li>تأكد من صحة بيانات config.php: DB_HOST و DB_NAME و DB_USER و DB_PASS.</li>
    <li>تأكد أن قاعدة البيانات تم إنشاؤها فعلياً من لوحة الاستضافة.</li>
    <li>تأكد أن المستخدم مرتبط بقاعدة البيانات بكل الصلاحيات.</li>
    <li>بعض الاستضافات تتطلب DB_HOST غير localhost — راجع لوحة الاستضافة.</li>
    <li>لا حاجة لاستيراد أي ملف SQL يدوياً — الجداول تُنشأ تلقائياً بمجرد صحة الاتصال.</li>
    </ol>
    <div class="err">' . $dbErr . '</div></div></body></html>');
}

if (session_status() === PHP_SESSION_NONE) session_start();

/* ═══════════════════════════════════════════════
   Self-healing schema — creates / repairs every
   required table automatically. No manual SQL
   import is ever required to run or update the site.
   ═══════════════════════════════════════════════ */
function ensure_schema(PDO $pdo): array {
    $log = [];

    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
      id INT AUTO_INCREMENT PRIMARY KEY,
      username VARCHAR(80) NOT NULL UNIQUE,
      password_hash VARCHAR(255) NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'admins';

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(120) NOT NULL,
      slug VARCHAR(140) NOT NULL UNIQUE,
      icon_svg TEXT,
      sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'categories';

    $pdo->exec("CREATE TABLE IF NOT EXISTS apps (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(220) NOT NULL,
      slug VARCHAR(240) NOT NULL UNIQUE,
      category_id INT,
      developer VARCHAR(200),
      version VARCHAR(60),
      play_store_version VARCHAR(60),
      android_version VARCHAR(60),
      size_mb VARCHAR(30),
      license VARCHAR(60) DEFAULT 'Free',
      package_name VARCHAR(200),
      icon_path VARCHAR(300),
      screenshots JSON,
      short_description VARCHAR(500),
      long_description MEDIUMTEXT,
      features JSON,
      pros JSON,
      cons JSON,
      install_steps JSON,
      faq JSON,
      whats_new TEXT,
      download_url VARCHAR(600),
      mirror2_url VARCHAR(600),
      mirror3_url VARCHAR(600),
      rating DECIMAL(2,1) DEFAULT 4.5,
      downloads INT DEFAULT 0,
      views INT DEFAULT 0,
      seo_title VARCHAR(255),
      meta_description VARCHAR(320),
      keywords VARCHAR(500),
      status ENUM('published','draft') DEFAULT 'draft',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_slug (slug),
      INDEX idx_status (status),
      INDEX idx_category (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'apps';

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
      `key` VARCHAR(100) PRIMARY KEY,
      `value` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'settings';

    // Foreign key (best-effort, ignored if already present or unsupported)
    try {
        $fk = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='apps' AND CONSTRAINT_TYPE='FOREIGN KEY'")->fetchColumn();
        if (!$fk) {
            $pdo->exec("ALTER TABLE apps ADD CONSTRAINT fk_apps_category
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL");
        }
    } catch (Throwable $e) { /* non-critical */ }

    // Seed default categories if the table is empty
    $count = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($count === 0) {
        $pdo->exec("INSERT IGNORE INTO categories (name, slug, sort_order) VALUES
            ('تطبيقات', 'apps', 1),
            ('ألعاب', 'games', 2),
            ('تعديل وتصميم', 'design', 3),
            ('أدوات', 'tools', 4),
            ('تواصل اجتماعي', 'social', 5),
            ('إنتاجية', 'productivity', 6)");
    }

    // Safe migrations: add any columns introduced in later versions
    // without ever requiring a manual SQL import.
    $wanted = [
        'apps' => [
            'playstore_url'    => "VARCHAR(600) NULL AFTER play_store_version",
            'privacy_policy'   => "MEDIUMTEXT NULL AFTER faq",
            'terms_content'    => "MEDIUMTEXT NULL AFTER privacy_policy",
            'offers_text'      => "MEDIUMTEXT NULL AFTER terms_content",
            'changelog'        => "JSON NULL AFTER whats_new",
            'needs_update'     => "TINYINT(1) NOT NULL DEFAULT 0 AFTER status",
            'source_url'       => "VARCHAR(600) NULL AFTER download_url",
        ],
    ];
    foreach ($wanted as $table => $cols) {
        try {
            $existing = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table'")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($cols as $col => $def) {
                if (!in_array($col, $existing, true)) {
                    try { $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def"); }
                    catch (Throwable $e2) { $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` " . preg_replace('/\s+AFTER\s+\S+/', '', $def)); }
                }
            }
        } catch (Throwable $e) { /* ignore, non-critical */ }
    }

    return $log;
}
ensure_schema($pdo);

function get_cfg(PDO $pdo, string $k, string $d = ''): string {
    $r = $pdo->prepare("SELECT `value` FROM settings WHERE `key`=?");
    $r->execute([$k]);
    $v = $r->fetchColumn();
    return $v !== false ? $v : $d;
}
function set_cfg(PDO $pdo, string $k, string $v): void {
    $pdo->prepare("INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)")->execute([$k, $v]);
}

/* ═══════════════════════════════════════════════
   OpenRouter helpers — multi-key rotation +
   auto model switching between free models.
   ═══════════════════════════════════════════════ */

// Fallback list used only if the live /models call also fails.
// آخر تحقق: يوليو 2026. المعرّفات القديمة (llama-3.1، gemma-2، mistral-7b، phi-3، zephyr، openchat، mythomax)
// أصبحت غير متاحة على OpenRouter وهذا كان السبب الفعلي لفشل كل المحاولات سابقاً.
// "openrouter/free" هو الموجّه التلقائي الرسمي من OpenRouter نفسه ويختار نموذجاً مجانياً متاحاً
// تلقائياً، لذا نضعه أولاً كضمان يعمل دائماً حتى لو تغيّرت أسماء النماذج الفردية لاحقاً.
function openrouter_default_free_models(): array {
    return [
        'openrouter/free',
        'meta-llama/llama-3.3-70b-instruct:free',
        'qwen/qwen3-coder:free',
        'openai/gpt-oss-20b:free',
        'nvidia/nemotron-nano-9b-v2:free',
    ];
}

// Fetch the live list of free models from OpenRouter (public endpoint, no key needed).
function fetch_openrouter_free_models(): array {
    $ch = curl_init('https://openrouter.ai/api/v1/models');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$res) return [];

    $data = json_decode($res, true);
    $list = $data['data'] ?? [];
    $free = [];
    foreach ($list as $m) {
        $id = $m['id'] ?? '';
        $promptPrice = $m['pricing']['prompt'] ?? '1';
        $completionPrice = $m['pricing']['completion'] ?? '1';
        $isFree = str_ends_with($id, ':free') || ((float)$promptPrice === 0.0 && (float)$completionPrice === 0.0);
        if ($id && $isFree) {
            $free[] = [
                'id' => $id,
                'name' => $m['name'] ?? $id,
                'context_length' => $m['context_length'] ?? null,
            ];
        }
    }
    return $free;
}

// Split a settings value that may contain multiple keys (comma / newline separated).
function openrouter_keys(string $raw): array {
    $parts = preg_split('/[\r\n,]+/', $raw);
    $parts = array_map('trim', $parts);
    return array_values(array_filter($parts, fn($k) => $k !== ''));
}

// يخزّن نتيجة /models مؤقتاً (6 ساعات) لتفادي استدعاء بطيء متكرر في كل طلب
function get_cached_free_models(): array {
    $cacheFile = UPLOAD_PATH . '/.openrouter_models_cache.json';
    if (is_file($cacheFile) && (time() - filemtime($cacheFile) < 21600)) {
        $cached = json_decode((string)file_get_contents($cacheFile), true);
        if (is_array($cached)) return $cached;
    }
    $live = fetch_openrouter_free_models();
    if ($live) { @file_put_contents($cacheFile, json_encode($live)); }
    return $live;
}

/**
 * يبني قائمة النماذج المرشحة بالترتيب: اختيار الأدمن اليدوي أولاً، ثم النماذج المجانية
 * المتاحة فعلياً الآن (تُجلب حياً من OpenRouter)، ثم قائمة احتياطية ثابتة، وأخيراً
 * "openrouter/free" كضمان أخير لا يفشل أبداً مهما تغيّرت أسماء النماذج الفردية.
 */
function build_model_rotation(PDO $pdo, bool $forceAll = false): array {
    $primary    = get_cfg($pdo, 'openrouter_model', 'openrouter/free');
    $fallback   = get_cfg($pdo, 'openrouter_fallback', 'meta-llama/llama-3.3-70b-instruct:free');
    $autoRotate = $forceAll || get_cfg($pdo, 'openrouter_auto_rotate', '1') === '1';

    $models = array_values(array_filter([$primary, $fallback]));

    if ($autoRotate) {
        foreach (get_cached_free_models() as $m) { $models[] = $m['id']; }
        foreach (openrouter_default_free_models() as $m) { $models[] = $m; }
    }

    $models[] = 'openrouter/free'; // ضمان أخير يعمل دائماً

    return array_values(array_unique(array_filter($models)));
}

// Low level call to OpenRouter chat completions. Returns ['ok'=>bool,'content'=>?string,'error'=>?string,'http'=>int]
function openrouter_call(string $key, string $model, string $prompt, int $timeout = 55): array {
    if (!$key) return ['ok' => false, 'content' => null, 'error' => 'لا يوجد مفتاح API', 'http' => 0];
    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $key, 'Content-Type: application/json',
            'HTTP-Referer: ' . SITE_URL, 'X-Title: yassota',
        ],
        CURLOPT_POSTFIELDS => json_encode(['model' => $model, 'messages' => [['role' => 'user', 'content' => $prompt]], 'temperature' => 0.7]),
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) return ['ok' => false, 'content' => null, 'error' => "خطأ اتصال: $err", 'http' => 0];
    if ($code !== 200 || !$res) {
        $d = json_decode((string)$res, true);
        $msg = $d['error']['message'] ?? "رمز استجابة غير متوقع: $code";
        return ['ok' => false, 'content' => null, 'error' => $msg, 'http' => $code];
    }
    $d = json_decode($res, true);
    $content = $d['choices'][0]['message']['content'] ?? null;
    if (!$content) return ['ok' => false, 'content' => null, 'error' => 'رد فارغ من الموديل', 'http' => $code];
    return ['ok' => true, 'content' => $content, 'error' => null, 'http' => $code];
}

// High level: tries every key × every model until one succeeds. Returns full trace for diagnostics.
function openrouter_call_rotating(array $keys, array $models, string $prompt): array {
    $trace = [];
    foreach ($keys as $key) {
        foreach ($models as $model) {
            $r = openrouter_call($key, $model, $prompt);
            $trace[] = ['model' => $model, 'key_tail' => substr($key, -4), 'ok' => $r['ok'], 'error' => $r['error'], 'http' => $r['http']];
            if ($r['ok']) {
                return ['ok' => true, 'content' => $r['content'], 'model' => $model, 'trace' => $trace];
            }
        }
    }
    return ['ok' => false, 'content' => null, 'model' => null, 'trace' => $trace];
}

// Convenience: builds the key/model list from saved settings and runs a plain-text prompt.
// Returns ['ok'=>bool,'content'=>?string,'error'=>?string]
function ai_text(PDO $pdo, string $prompt): array {
    $keys = openrouter_keys(get_cfg($pdo, 'openrouter_key'));
    if (!$keys) return ['ok' => false, 'content' => null, 'error' => 'لم يتم إضافة مفتاح OpenRouter بعد.'];
    $primary  = get_cfg($pdo, 'openrouter_model', 'meta-llama/llama-3.1-8b-instruct:free');
    $fallback = get_cfg($pdo, 'openrouter_fallback', 'google/gemma-2-9b-it:free');
    $autoRotate = get_cfg($pdo, 'openrouter_auto_rotate', '1') === '1';
    $models = array_values(array_unique(array_filter([$primary, $fallback])));
    if ($autoRotate) $models = array_values(array_unique(array_merge($models, openrouter_default_free_models())));
    $r = openrouter_call_rotating($keys, $models, $prompt);
    if (!$r['ok']) return ['ok' => false, 'content' => null, 'error' => 'فشل الاتصال بكل الموديلات المتاحة — راجع صفحة اختبار الاتصال.'];
    return ['ok' => true, 'content' => $r['content'], 'error' => null];
}

// Extract the first {...} JSON object from a raw AI response (strips markdown fences).
function ai_extract_json(string $raw): ?array {
    $raw = trim(preg_replace(['/^```json\s*/mi', '/\s*```$/m', '/^```\s*/m'], '', $raw));
    $s = strpos($raw, '{'); $e = strrpos($raw, '}');
    if ($s === false || $e === false) return null;
    return json_decode(substr($raw, $s, $e - $s + 1), true);
}

/* ─── Helpers ─── */

function h(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function url(string $path = ''): string { return SITE_URL . '/' . ltrim($path, '/'); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">'; }
function csrf_check(): bool {
    return isset($_POST['_csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['_csrf']);
}

function slugify(string $t): string {
    $t = trim($t);
    $t = preg_replace('/\s+/u', '-', $t);
    $t = preg_replace('/[^\p{Arabic}a-zA-Z0-9\-]/u', '', $t);
    $t = preg_replace('/-+/', '-', $t);
    return mb_strtolower(trim($t, '-')) ?: 'app-' . time();
}

function unique_slug(PDO $pdo, string $base): string {
    $slug = slugify($base);
    $orig = $slug; $i = 1;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM apps WHERE slug=?");
    $stmt->execute([$slug]);
    while ($stmt->fetchColumn() > 0) {
        $slug = $orig . '-' . (++$i);
        $stmt->execute([$slug]);
    }
    return $slug;
}

function is_admin(): bool { return !empty($_SESSION['admin_id']); }

function require_admin(): void {
    if (!is_admin()) { header('Location: ' . url('admin.php?page=login')); exit; }
}

function process_icon(array $file, string $slug): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > MAX_ICON_MB * 1048576) return null;
    $info = getimagesize($file['tmp_name']);
    if (!$info) return null;
    $src = match($info['mime']) {
        'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
        'image/png'  => imagecreatefrompng($file['tmp_name']),
        'image/webp' => imagecreatefromwebp($file['tmp_name']),
        default => null,
    };
    if (!$src) return null;
    $dir = UPLOAD_PATH . '/icons';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = $slug . '-' . substr(md5(uniqid()), 0, 6);
    [$w, $h] = [imagesx($src), imagesy($src)];
    $s = min($w, $h);
    $dst = imagecreatetruecolor(400, 400);
    imagealphablending($dst, false); imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, intval(($w-$s)/2), intval(($h-$s)/2), 400, 400, $s, $s);
    imagewebp($dst, "$dir/$name.webp", 88);
    imagedestroy($src); imagedestroy($dst);
    return "uploads/icons/$name.webp";
}

// Resize (preserving aspect ratio, capped to $maxW x $maxH) and compress to WebP.
function compress_image_to(string $tmpName, string $mime, string $destPath, int $maxW, int $maxH, int $quality): bool {
    $src = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($tmpName),
        'image/png'  => imagecreatefrompng($tmpName),
        'image/webp' => imagecreatefromwebp($tmpName),
        default => null,
    };
    if (!$src) return false;

    $w = imagesx($src); $h = imagesy($src);
    $ratio = min(1, $maxW / $w, $maxH / $h);
    $newW = max(1, (int)round($w * $ratio));
    $newH = max(1, (int)round($h * $ratio));

    $dst = imagecreatetruecolor($newW, $newH);
    imagealphablending($dst, false); imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
    $ok = imagewebp($dst, $destPath, $quality);
    imagedestroy($src); imagedestroy($dst);
    return $ok;
}

function process_screenshots(array $files, string $slug): array {
    $result = []; $n = count($files['name']);
    $dir = UPLOAD_PATH . '/screenshots';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    for ($i = 0; $i < $n; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        if ($files['size'][$i] > MAX_ICON_MB * 4 * 1048576) continue; // sane upper bound
        $tmp = $files['tmp_name'][$i];
        $info = @getimagesize($tmp);
        if (!$info) continue;

        // Screenshots are usually tall (phone) images — cap to 1080x2000, keep aspect ratio (no forced crop).
        $fname = $slug . '-ss' . $i . '-' . substr(md5(uniqid()), 0, 6) . '.webp';
        $dest = "$dir/$fname";
        if (compress_image_to($tmp, $info['mime'], $dest, 1080, 2000, 82)) {
            $result[] = "uploads/screenshots/$fname";
        }
    }
    return $result;
}

function time_ago(string $dt): string {
    $d = time() - strtotime($dt);
    if ($d < 60) return 'الآن';
    if ($d < 3600) return floor($d/60) . ' دقيقة';
    if ($d < 86400) return floor($d/3600) . ' ساعة';
    return floor($d/86400) . ' يوم';
}

/* ═══════════════════════════════════════════════
   Hard-navigation guard.
   Ad networks (like the MoneyTag popunder tag) hook
   document-level click listeners and can swallow the
   click before our links get to navigate. This must be
   printed FIRST in <head>, before the ad <script> tag,
   so our listener is registered before theirs — that
   guarantees it runs even if the ad script later calls
   stopPropagation() on its own (later-registered) handler.
   Any link marked data-hardnav="1" is guaranteed to
   navigate a beat after the click, ad or no ad.
   ═══════════════════════════════════════════════ */
function nav_guard_script(): string {
    return <<<HTML
<script>
(function(){
  document.addEventListener('click', function(e){
    var el = e.target.closest('[data-hardnav]');
    if(!el) return;
    var url = el.getAttribute('href') || el.getAttribute('data-hardnav-url');
    if(!url || url === '#') return;
    setTimeout(function(){ window.location.href = url; }, 40);
  }, true);
})();
</script>
HTML;
}
