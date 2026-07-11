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

    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(150) NOT NULL,
      email VARCHAR(190) NOT NULL,
      subject VARCHAR(200),
      message TEXT NOT NULL,
      status ENUM('new','read') NOT NULL DEFAULT 'new',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'contact_messages';

    $pdo->exec("CREATE TABLE IF NOT EXISTS app_versions (
      id INT AUTO_INCREMENT PRIMARY KEY,
      app_id INT NOT NULL,
      version VARCHAR(60),
      changelog TEXT,
      download_url VARCHAR(600),
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_app (app_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'app_versions';

    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
      id INT AUTO_INCREMENT PRIMARY KEY,
      app_id INT NOT NULL,
      name VARCHAR(150) NOT NULL,
      rating TINYINT NOT NULL,
      body TEXT NOT NULL,
      status ENUM('pending','approved') NOT NULL DEFAULT 'pending',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_app (app_id),
      INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'comments';

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
        'categories' => [
            'description' => "MEDIUMTEXT NULL AFTER icon_svg",
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

/* ═══════════════════════════════════════════════
   Lightweight page cache — file-based, for the
   expensive public listing pages only (index/category/
   top/updates). app.php is deliberately never cached
   since it must always increment the view counter and
   show fresh comments. Invalidated instantly (not just
   on a timer) whenever an app is added/edited/deleted,
   via a version token bumped in admin.php.
   ═══════════════════════════════════════════════ */
function cache_version(PDO $pdo): int {
    return (int)get_cfg($pdo, 'cache_version', '1');
}
function bump_cache_version(PDO $pdo): void {
    set_cfg($pdo, 'cache_version', (string)(cache_version($pdo) + 1));
}
function page_cache_dir(): string {
    $dir = UPLOAD_PATH . '/.cache';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir;
}
function page_cache_file(PDO $pdo, string $key): string {
    return page_cache_dir() . '/' . md5($key . '|v' . cache_version($pdo)) . '.html';
}
// Call at the very top of a cacheable page. If a fresh cached copy exists,
// echoes it and returns true (the caller must exit immediately). Otherwise
// starts output buffering and returns false — call page_cache_end() at the
// very end of the page to save+flush what was rendered.
function page_cache_start(PDO $pdo, string $key, int $ttl = 300): bool {
    $file = page_cache_file($pdo, $key);
    if (is_file($file) && (time() - filemtime($file)) < $ttl) {
        echo file_get_contents($file);
        return true;
    }
    ob_start();
    return false;
}
function page_cache_end(PDO $pdo, string $key): void {
    $html = ob_get_clean();
    @file_put_contents(page_cache_file($pdo, $key), $html);
    echo $html;
}

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

/* ═══════════════════════════════════════════════
   AI image generation — best-effort, since reliable
   free image-generation models on OpenRouter are
   scarce. Uses the admin-configured model only (no
   auto-rotation across dozens of text models like the
   content generator does); fails with a clear message
   rather than a crash when the model doesn't return an
   image, so the UI can point the admin at the Google
   Play icon-import fallback instead.
   ═══════════════════════════════════════════════ */
function ai_generate_image_raw(string $key, string $model, string $prompt, int $timeout = 90): array {
    if (!$key)   return ['ok' => false, 'error' => 'لا يوجد مفتاح API'];
    if (!$model) return ['ok' => false, 'error' => 'لم يتم تحديد موديل توليد صور'];

    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $key, 'Content-Type: application/json',
            'HTTP-Referer: ' . SITE_URL, 'X-Title: yassota',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'modalities' => ['image', 'text'],
        ]),
    ]);
    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) return ['ok' => false, 'error' => "خطأ اتصال: $err"];
    if ($code !== 200 || !$res) {
        $d = json_decode((string)$res, true);
        return ['ok' => false, 'error' => $d['error']['message'] ?? "رمز استجابة غير متوقع: $code"];
    }

    $d = json_decode($res, true);
    $msg = $d['choices'][0]['message'] ?? null;
    if (!$msg) return ['ok' => false, 'error' => 'رد فارغ من الموديل'];

    // OpenRouter's image-output convention: message.images[].image_url.url (a data: URI).
    $dataUri = $msg['images'][0]['image_url']['url'] ?? null;
    // Fallback: some models inline a data: URI directly in the text content.
    if (!$dataUri && is_string($msg['content'] ?? null)
        && preg_match('#data:image/\w+;base64,[A-Za-z0-9+/=]+#', $msg['content'], $m)) {
        $dataUri = $m[0];
    }
    if (!$dataUri) {
        return ['ok' => false, 'error' => 'هذا الموديل لا يُرجع صوراً فعلياً — جرّب موديل توليد صور مختلف من الإعدادات، أو استخدم استيراد الأيقونة من Google Play بدلاً من ذلك.'];
    }
    if (!preg_match('#^data:image/(\w+);base64,(.+)$#s', $dataUri, $m)) {
        return ['ok' => false, 'error' => 'صيغة الصورة المُرجعة غير مدعومة'];
    }
    $bin = base64_decode($m[2], true);
    if (!$bin) return ['ok' => false, 'error' => 'تعذر فك ترميز الصورة المُرجعة'];
    return ['ok' => true, 'bin' => $bin];
}

// Tries every configured key with the single admin-configured image model.
function ai_generate_image(PDO $pdo, string $prompt): array {
    $keys  = openrouter_keys(get_cfg($pdo, 'openrouter_key'));
    if (!$keys) return ['ok' => false, 'error' => 'لم يتم إضافة مفتاح OpenRouter بعد.'];
    $model = trim(get_cfg($pdo, 'openrouter_image_model'));
    if (!$model) return ['ok' => false, 'error' => 'لم يتم تحديد موديل توليد صور في الإعدادات. النماذج المجانية القادرة على توليد صور نادرة جداً — يمكنك تجربة موديل مدفوع، أو استخدام استيراد الأيقونة من Google Play بدلاً من ذلك.'];
    $last = ['ok' => false, 'error' => 'فشل غير متوقع'];
    foreach ($keys as $key) {
        $last = ai_generate_image_raw($key, $model, $prompt);
        if ($last['ok']) return $last;
    }
    return $last;
}

/* ═══════════════════════════════════════════════
   Google Play scraping helpers — public Open Graph
   tags only (title/description/icon), best effort.
   Play Store search results are heavily client-side
   rendered, so playstore_search() can legitimately
   find nothing for many queries; callers must treat
   a null return as normal, not an error.
   ═══════════════════════════════════════════════ */
function fetch_playstore_meta(string $url, int $timeout = 20): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'Accept-Language: ar,en;q=0.8'],
    ]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$html || $code !== 200) return null;

    $meta = function (string $prop) use ($html): ?string {
        if (preg_match('#<meta[^>]+property=["\']' . preg_quote($prop, '#') . '["\'][^>]+content=["\']([^"\']*)["\']#i', $html, $m)) return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        if (preg_match('#<meta[^>]+content=["\']([^"\']*)["\'][^>]+property=["\']' . preg_quote($prop, '#') . '["\']#i', $html, $m)) return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        return null;
    };

    $title = $meta('og:title');
    $desc  = $meta('og:description');
    $image = $meta('og:image');
    if (!$title && !$desc) return null;

    $pkg = null;
    if (preg_match('#[?&]id=([a-zA-Z0-9_.]+)#', $url, $m)) $pkg = $m[1];
    // Clean up common Play Store title suffix "- Apps on Google Play"
    if ($title) $title = trim(preg_replace('/\s*[-–]\s*(Apps on Google Play|تطبيقات على Google Play).*$/i', '', $title));

    return [
        'name' => $title,
        'short_description' => $desc ? mb_substr($desc, 0, 300) : null,
        'long_description' => $desc,
        'icon_url' => $image,
        'package_name' => $pkg,
        'playstore_url' => $url,
    ];
}

// Best-effort: finds the first Play Store app-details link for a free-text search query.
// Returns null (not an error) when nothing could be scraped — search results are mostly
// client-side rendered, so this legitimately fails often; callers must fall back gracefully.
function playstore_search(string $query, int $timeout = 20): ?string {
    $ch = curl_init('https://play.google.com/store/search?q=' . urlencode($query) . '&c=apps&hl=ar');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'Accept-Language: ar,en;q=0.8'],
    ]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$html || $code !== 200) return null;
    if (preg_match('#/store/apps/details\?id=([a-zA-Z0-9_.]+)#', $html, $m)) {
        return 'https://play.google.com/store/apps/details?id=' . $m[1];
    }
    return null;
}

// Downloads a remote image URL and saves it as a processed app icon (shared by
// Play Store import and the bulk generator).
function import_remote_icon(string $remoteUrl, string $slug): ?string {
    $remote = filter_var(trim($remoteUrl), FILTER_VALIDATE_URL);
    if (!$remote) return null;
    $tmp = tempnam(sys_get_temp_dir(), 'icn');
    $ch = curl_init($remote);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_FOLLOWLOCATION => true]);
    $bin = curl_exec($ch);
    $ok = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200 && $bin;
    curl_close($ch);
    $path = null;
    if ($ok) {
        file_put_contents($tmp, $bin);
        $path = process_icon(['tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen($bin)], $slug);
    }
    @unlink($tmp);
    return $path;
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

// Trims and collapses 3+ consecutive blank lines down to a single blank
// line. AI-generated long-form text occasionally contains long runs of
// blank lines (from concatenating multiple "continue writing" passes, or
// a model emitting extra whitespace); left as-is, nl2br() turns each one
// into a <br>, which renders as a large empty visual gap on the page even
// though the field technically isn't empty.
function clean_long_text(?string $s): string {
    $s = trim($s ?? '');
    if ($s === '') return '';
    return preg_replace('/\n{3,}/', "\n\n", $s);
}

// Shared SEO-field standards embedded in every AI prompt that generates
// seo_title/meta_description/keywords, so the whole site produces the same
// search-optimized pattern real Arabic app-download sites rank with —
// not the generic "SEO title less than 60 chars" instruction that produced
// weak, unspecific output before.
function seo_prompt_standards(): string {
    $year = date('Y');
    return <<<P
اتبع بدقة معايير SEO التالية عند كتابة الحقول التالية (لا تكتب حقولاً عامة أو مبهمة):
- seo_title: يبدأ بكلمة "تحميل"، يتضمن اسم التطبيق كاملاً (بالإنجليزية إن كان اسمه إنجليزياً)، ثم "APK" إن كان تطبيق/لعبة أندرويد، ثم "آخر إصدار {$year}"، ثم "للأندرويد مجاناً"، ثم فاصل " | " ثم صياغة ثانية مختصرة تبدأ بـ"تنزيل" واسم التطبيق و"برابط مباشر". مثال على الشكل المطلوب (لا تنسخه، استخدمه كقالب فقط): "تحميل PUBG MOBILE APK آخر إصدار {$year} للأندرويد مجانًا | تنزيل ببجي موبايل برابط مباشر".
- meta_description: يبدأ بفعل أمر مثل "قم بتحميل" أو "حمّل"، يذكر اسم التطبيق ونوعه (لعبة/تطبيق) و"APK" و"آخر إصدار {$year}" و"للأندرويد مجاناً برابط مباشر وسريع"، ثم جملة تسويقية قصيرة عن أبرز مزايا هذا التطبيق تحديداً (وليست عامة). الطول 140-160 حرفاً.
- keywords: 15 إلى 18 كلمة/عبارة مفتاحية عربية طويلة الذيل مفصولة بفاصلة، تغطي أشكال البحث الشائعة: "تحميل [الاسم]"، "تنزيل [الاسم]"، "[الاسم] APK"، "[الاسم] آخر إصدار"، "تحميل [الاسم] للأندرويد"، "[الاسم] مجاناً"، "تحديث [الاسم]"، "[الاسم] Android"، "تحميل [الاسم] برابط مباشر"، "[الاسم] {$year}" وهكذا — بدون تكرار حرفي لنفس الصياغة، وبمزيج من العربية والإنجليزية عندما يكون اسم التطبيق إنجليزياً معروفاً.
P;
}

// Extract the first {...} JSON object from a raw AI response (strips markdown fences).
function ai_extract_json(string $raw): ?array {
    $raw = trim(preg_replace(['/^```json\s*/mi', '/\s*```$/m', '/^```\s*/m'], '', $raw));
    $s = strpos($raw, '{'); $e = strrpos($raw, '}');
    if ($s === false || $e === false) return null;
    return json_decode(substr($raw, $s, $e - $s + 1), true);
}

/* ─── Helpers ─── */

// ENT_SUBSTITUTE is critical here: without it, htmlspecialchars() silently
// returns an EMPTY STRING for the *entire* input if it contains even one
// invalid UTF-8 byte anywhere — which is exactly what long AI-generated
// Arabic text occasionally contains, and is why long descriptions could
// render as a blank gap instead of the actual (mostly valid) text.
function h(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Defense-in-depth for the same blank-description problem: strip invalid
// UTF-8 bytes at SAVE time (not only at render time via h()'s
// ENT_SUBSTITUTE), so bad bytes never reach the database in the first
// place — some hosts run MySQL in non-strict mode and will silently accept
// or mangle them on INSERT. Safe to run on already-clean text (no-op).
function clean_utf8(?string $s): string {
    $s = $s ?? '';
    if ($s === '') return '';
    $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
    return $clean !== false ? $clean : mb_convert_encoding($s, 'UTF-8', 'UTF-8');
}

// Recursive clean_utf8() over an array (e.g. $_POST) so every text field in
// a submitted form is sanitized in one call, arrays and nested arrays included.
function clean_utf8_deep($v) {
    if (is_array($v)) return array_map('clean_utf8_deep', $v);
    return is_string($v) ? clean_utf8($v) : $v;
}

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

// Route names that must never collide with an app's pretty URL (/{slug}).
const RESERVED_SLUGS = [
    'index', 'app', 'admin', 'download', 'category', 'developer', 'top', 'updates',
    'sitemap', 'robots', 'config', 'partials', 'install', 'uploads', 'assets',
    'about', 'contact', 'privacy-policy', 'terms', 'dmca', 'cookie-policy',
];

function unique_slug(PDO $pdo, string $base): string {
    $slug = slugify($base);
    if (in_array($slug, RESERVED_SLUGS, true)) $slug .= '-app';
    $orig = $slug; $i = 1;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM apps WHERE slug=?");
    $stmt->execute([$slug]);
    while ($stmt->fetchColumn() > 0) {
        $slug = $orig . '-' . (++$i);
        $stmt->execute([$slug]);
    }
    return $slug;
}

// Pretty-URL helpers: yassota.com/{slug} and yassota.com/{slug}/download
function app_url(string $slug): string { return url(rawurlencode($slug)); }
function download_url(string $slug, int $mirror = 1): string {
    return url(rawurlencode($slug) . '/download') . ($mirror > 1 ? '?m=' . $mirror : '');
}

// CSS/JS URL with a cache-busting ?v= based on the file's own mtime — required
// because .htaccess caches these for a month; without this, a bug fix to
// main.css/main.js would silently not reach any browser that already has the
// old file cached, until that month expires or the visitor hard-refreshes.
function asset_url(string $relPath): string {
    $full = ROOT_PATH . '/' . ltrim($relPath, '/');
    $v = is_file($full) ? filemtime($full) : time();
    return url($relPath) . '?v=' . $v;
}

function is_admin(): bool { return !empty($_SESSION['admin_id']); }

function require_admin(): void {
    if (!is_admin()) { header('Location: ' . url('admin.php?page=login')); exit; }
}

function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? ''; }

/**
 * Optional defense-in-depth IP allowlist for the whole admin panel (including
 * the login page). Disabled by default — an empty `admin_ip_allowlist` setting
 * means no restriction. This is always ADDITIONAL to username/password login,
 * never a replacement for it, and is configured by an already-logged-in admin
 * from Settings (which auto-includes their own current IP, so saving it can
 * never lock them out of the account that just saved it).
 */
function admin_ip_check(PDO $pdo): void {
    $raw = trim(get_cfg($pdo, 'admin_ip_allowlist'));
    if ($raw === '') return;
    $allowed = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $raw)));
    if (in_array(client_ip(), $allowed, true)) return;

    http_response_code(403);
    $ip = h(client_ip());
    die('<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>الوصول مرفوض</title>
    <style>body{margin:0;font-family:Tahoma,Arial,sans-serif;background:#03080f;color:#e8f4f8;padding:40px 16px;line-height:1.9;text-align:center}
    .box{max-width:480px;margin:60px auto;background:#0a1628;border:1px solid rgba(255,68,102,.3);border-radius:14px;padding:32px}
    h1{color:#ff4466;font-size:20px;margin:0 0 14px}
    code{background:#152642;color:#00f5ff;padding:4px 10px;border-radius:6px;font-family:monospace;direction:ltr;display:inline-block;margin-top:6px}
    </style></head><body><div class="box">
    <h1>الوصول مرفوض</h1>
    <p>عنوان IP الخاص بك غير مدرج ضمن قائمة الوصول المسموح بها للوحة التحكم.</p>
    <p>عنوانك الحالي:<br><code>' . $ip . '</code></p>
    </div></body></html>');
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

// Real Google AdSense auto-relaxed ad unit, used in every "ad-zone" slot
// site-wide. Renders nothing visible until AdSense approves the site (empty
// <ins> until then), then starts serving real ads with no further code
// changes. Previously these slots held an aggressive third-party popunder/
// redirect network (MoneyTag) instead — the kind of interstitial, click-
// hijacking ad behavior Google's Publisher Policies explicitly reject sites
// for, and very likely a real contributor to the AdSense rejection. It has
// been removed site-wide rather than just worked around.
function ad_slot(): string {
    return '<ins class="adsbygoogle" style="display:block;width:100%" data-ad-client="ca-pub-5506877998492189" data-ad-format="auto" data-full-width-responsive="true"></ins><script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
}
