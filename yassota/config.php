<?php
/* ═══ YASSOTA — config.php (النواة) ═══ */
if (!defined('YASSOTA')) define('YASSOTA', '1.0.0');
define('APP_ROOT', __DIR__);
define('UPLOAD_DIR', APP_ROOT . '/uploads');

date_default_timezone_set('Asia/Riyadh');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

$__https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true,
        'samesite' => 'Lax', 'secure' => $__https,
    ]);
    session_name('yassota_sid');
    session_start();
}

$__local = APP_ROOT . '/config.local.php';
if (file_exists($__local)) require $__local;

if (!defined('DB_HOST')) { ya_setup_wizard(); exit; }

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(503);
    die('<!doctype html><meta charset="utf-8"><body style="font-family:system-ui,sans-serif;max-width:640px;margin:60px auto;padding:0 16px;direction:rtl">'
        . '<h2 style="color:#dc2626">تعذر الاتصال بقاعدة البيانات</h2>'
        . '<p>تحقق من <code>config.local.php</code>، أو احذفه لإعادة الإعداد.</p>'
        . '<pre style="background:#f3f4f6;padding:12px;border-radius:8px;white-space:pre-wrap;direction:ltr;text-align:left">' . htmlspecialchars($e->getMessage()) . '</pre></body>');
}

ya_ensure_schema($pdo);
$GLOBALS['pdo'] = $pdo;

function ya_ensure_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return; $done = true;

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
      k VARCHAR(80) PRIMARY KEY, v TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      username VARCHAR(30) NOT NULL UNIQUE,
      name VARCHAR(80) NOT NULL DEFAULT '',
      email VARCHAR(190) NOT NULL DEFAULT '',
      password_hash VARCHAR(255) NOT NULL DEFAULT '',
      google_id VARCHAR(60) NOT NULL DEFAULT '',
      avatar VARCHAR(255) NOT NULL DEFAULT '',
      cover VARCHAR(255) NOT NULL DEFAULT '',
      bio VARCHAR(300) NOT NULL DEFAULT '',
      website VARCHAR(190) NOT NULL DEFAULT '',
      location VARCHAR(100) NOT NULL DEFAULT '',
      verified TINYINT NOT NULL DEFAULT 0,
      is_admin TINYINT NOT NULL DEFAULT 0,
      is_private TINYINT NOT NULL DEFAULT 0,
      banned TINYINT NOT NULL DEFAULT 0,
      followers_count INT NOT NULL DEFAULT 0,
      following_count INT NOT NULL DEFAULT 0,
      posts_count INT NOT NULL DEFAULT 0,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX (email), INDEX (google_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
      id INT AUTO_INCREMENT PRIMARY KEY,
      slug VARCHAR(80) NOT NULL UNIQUE,
      name VARCHAR(80) NOT NULL,
      icon VARCHAR(40) NOT NULL DEFAULT 'grid',
      description VARCHAR(300) NOT NULL DEFAULT '',
      sort_order INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      slug VARCHAR(200) NOT NULL UNIQUE,
      type VARCHAR(12) NOT NULL DEFAULT 'image',
      title VARCHAR(200) NOT NULL DEFAULT '',
      description MEDIUMTEXT,
      image VARCHAR(255) NOT NULL DEFAULT '',
      video VARCHAR(255) NOT NULL DEFAULT '',
      category_id INT NULL,
      location VARCHAR(120) NOT NULL DEFAULT '',
      seo_title VARCHAR(200) NOT NULL DEFAULT '',
      seo_description VARCHAR(300) NOT NULL DEFAULT '',
      seo_keywords VARCHAR(300) NOT NULL DEFAULT '',
      visibility VARCHAR(12) NOT NULL DEFAULT 'public',
      status VARCHAR(12) NOT NULL DEFAULT 'published',
      views INT NOT NULL DEFAULT 0,
      likes_count INT NOT NULL DEFAULT 0,
      comments_count INT NOT NULL DEFAULT 0,
      shares_count INT NOT NULL DEFAULT 0,
      saves_count INT NOT NULL DEFAULT 0,
      score DOUBLE NOT NULL DEFAULT 0,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX (user_id), INDEX (category_id), INDEX (created_at), INDEX (score), INDEX (status), INDEX (type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS post_media (
      id INT AUTO_INCREMENT PRIMARY KEY, post_id INT NOT NULL, url VARCHAR(255) NOT NULL,
      kind VARCHAR(10) NOT NULL DEFAULT 'image', sort_order INT NOT NULL DEFAULT 0, INDEX (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
      id INT AUTO_INCREMENT PRIMARY KEY, post_id INT NOT NULL, user_id INT NOT NULL,
      parent_id INT NULL, body VARCHAR(1000) NOT NULL, likes_count INT NOT NULL DEFAULT 0,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX (post_id), INDEX (parent_id), INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS post_likes (
      user_id INT NOT NULL, post_id INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (user_id, post_id), INDEX (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS comment_likes (
      user_id INT NOT NULL, comment_id INT NOT NULL, PRIMARY KEY (user_id, comment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS post_saves (
      user_id INT NOT NULL, post_id INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (user_id, post_id), INDEX (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS follows (
      follower_id INT NOT NULL, following_id INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (follower_id, following_id), INDEX (following_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS hashtags (
      id INT AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(100) NOT NULL UNIQUE,
      name VARCHAR(100) NOT NULL, posts_count INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS post_hashtags (
      post_id INT NOT NULL, hashtag_id INT NOT NULL, PRIMARY KEY (post_id, hashtag_id), INDEX (hashtag_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
      id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, actor_id INT NULL,
      type VARCHAR(20) NOT NULL, post_id INT NULL, comment_id INT NULL,
      is_read TINYINT NOT NULL DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX (user_id, is_read), INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS conversations (
      id INT AUTO_INCREMENT PRIMARY KEY, user_a INT NOT NULL, user_b INT NOT NULL,
      last_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_pair (user_a, user_b), INDEX (last_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
      id INT AUTO_INCREMENT PRIMARY KEY, conversation_id INT NOT NULL, sender_id INT NOT NULL,
      body VARCHAR(2000) NOT NULL DEFAULT '', image VARCHAR(255) NOT NULL DEFAULT '',
      seen TINYINT NOT NULL DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX (conversation_id, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
      id INT AUTO_INCREMENT PRIMARY KEY, reporter_id INT NOT NULL, target_type VARCHAR(12) NOT NULL,
      target_id INT NOT NULL, reason VARCHAR(30) NOT NULL, note VARCHAR(500) NOT NULL DEFAULT '',
      status VARCHAR(12) NOT NULL DEFAULT 'open', created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
      k VARCHAR(120) NOT NULL, window_start INT NOT NULL, hits INT NOT NULL DEFAULT 0, PRIMARY KEY (k)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $defaults = [
        'site_name' => 'YASSOTA', 'site_url' => '', 'lang' => 'ar',
        'site_desc' => 'YASSOTA — منصة اجتماعية عربية حديثة لمشاركة الصور والفيديوهات والمقالات. كل منشور صفحة مستقلة تظهر في محركات البحث.',
        'og_image' => '', 'google_client_id' => '', 'google_verification' => '', 'twitter_handle' => '@yassota',
        // بيانات الإصدار المبني فعلياً (yassota/android/build.sh) — تُعدَّل من لوحة الإدارة عند كل إصدار
        'apk_version' => '1.0.0', 'apk_size' => '21 KB', 'apk_min_android' => '7.0 (API 24)',
        'apk_sha256' => '0E:4B:6A:6B:8D:88:56:F7:DE:F8:65:41:74:F8:07:52:6D:6F:77:A7:B6:1F:FE:58:49:2F:18:59:E6:AE:BE:9E',
        'apk_url' => 'downloads/YASSOTA-1.0.0.apk', 'play_url' => '',
    ];
    $have = $pdo->query("SELECT k FROM settings")->fetchAll(PDO::FETCH_COLUMN);
    $ins = $pdo->prepare("INSERT INTO settings (k, v) VALUES (?, ?)");
    foreach ($defaults as $k => $v) if (!in_array($k, $have, true)) $ins->execute([$k, $v]);

    if ((int)$pdo->query("SELECT COUNT(*) c FROM categories")->fetch()['c'] === 0) ya_seed($pdo);
}

function ya_seed(PDO $pdo): void {
    $cats = [
        ['technology','التقنية','cpu','كل ما يخص التقنية والأجهزة والبرمجيات.'],
        ['apps','التطبيقات','grid','أفضل تطبيقات الأندرويد والآيفون ومراجعاتها.'],
        ['games','الألعاب','gamepad','ألعاب الجوال والحاسوب والأخبار والبثوث.'],
        ['ai','الذكاء الاصطناعي','sparkles','أدوات وأخبار الذكاء الاصطناعي التوليدي.'],
        ['news','الأخبار','news','آخر الأخبار التقنية والعامة.'],
        ['entertainment','الترفيه','film','فيديوهات ومحتوى ترفيهي متنوع.'],
        ['sports','الرياضة','trophy','أخبار ولحظات رياضية.'],
        ['lifestyle','نمط الحياة','heart','الحياة اليومية والعادات والإلهام.'],
        ['photography','التصوير','camera','صور احترافية ونصائح تصوير.'],
        ['business','الأعمال','briefcase','ريادة الأعمال والتسويق والمال.'],
        ['education','التعليم','book','دروس وشروحات ومصادر تعليمية.'],
    ];
    $ci = $pdo->prepare("INSERT INTO categories (slug,name,icon,description,sort_order) VALUES (?,?,?,?,?)");
    foreach ($cats as $i => $c) $ci->execute([$c[0], $c[1], $c[2], $c[3], $i]);
    $catIds = $pdo->query("SELECT slug,id FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);

    $users = [
        ['yassota','فريق يسوتا','#0ea5e9',1,1],
        ['tech_arab','التقنية بالعربي','#8b5cf6',1,0],
        ['brook','بروك','#f59e0b',0,0],
        ['nova_ai','نوفا للذكاء','#10b981',1,0],
    ];
    $ui = $pdo->prepare("INSERT INTO users (username,name,email,verified,is_admin,avatar) VALUES (?,?,?,?,?,?)");
    foreach ($users as $u) {
        $ui->execute([$u[0], $u[1], $u[0].'@yassota.com', $u[3], $u[4], 'avatar:'.$u[2]]);
    }
    $uids = $pdo->query("SELECT username,id FROM users")->fetchAll(PDO::FETCH_KEY_PAIR);

    $posts = [
        ['tech_arab','apps','أفضل تطبيقات أندرويد المجانية في 2026','مجموعة مختارة من أقوى تطبيقات الأندرويد المجانية لهذا العام، تغطي الإنتاجية والتصميم والخصوصية. جرّبناها جميعاً وانتقينا الأفضل فقط.','#0ea5e9','تطبيقات,اندرويد,2026,مجاني'],
        ['nova_ai','ai','كيف يغيّر الذكاء الاصطناعي طريقة عملنا اليومية','من كتابة النصوص إلى تحرير الصور، أصبح الذكاء الاصطناعي مساعداً حقيقياً. نستعرض أبرز الأدوات التي توفّر ساعات من وقتك يومياً.','#10b981','ذكاء اصطناعي,ai,أدوات,إنتاجية'],
        ['brook','photography','لقطة الغروب المثالية — إعدادات الكاميرا','دليل عملي لضبط كاميرا هاتفك لالتقاط ألوان الغروب دون فقدان التفاصيل، مع أمثلة قبل وبعد.','#f59e0b','تصوير,غروب,كاميرا,نصائح'],
        ['yassota','technology','مرحباً بك في يسوتا — منصتك العربية الجديدة','يسوتا منصة اجتماعية عربية حديثة: انشر صورك ومقالاتك، تابع من تحب، وكل منشور تنشره يصبح صفحة مستقلة تظهر في جوجل. ابدأ الآن.','#0ea5e9','يسوتا,منصة,عربية,تواصل'],
        ['tech_arab','games','أقوى ألعاب الجوال التي تستحق التجربة','قائمة بألعاب جوال بجرافيك مذهل وطور لعب إدماني، بعضها مجاني بالكامل. أيها لعبت من قبل؟','#8b5cf6','العاب,جوال,جيمنج,مراجعة'],
        ['nova_ai','business','5 طرق لاستخدام الذكاء الاصطناعي في تنمية مشروعك','نصائح عملية لأصحاب المشاريع الصغيرة لتوظيف أدوات الذكاء الاصطناعي في التسويق وخدمة العملاء والمحتوى.','#10b981','اعمال,تسويق,ذكاء اصطناعي,مشاريع'],
    ];
    $pi = $pdo->prepare("INSERT INTO posts (user_id,slug,type,title,description,image,category_id,seo_title,seo_description,seo_keywords,score,created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $t = time();
    foreach ($posts as $i => $p) {
        $uid = $uids[$p[0]]; $slug = ya_slugify($p[2]);
        $pi->execute([$uid, $slug, 'image', $p[2], $p[3], 'gradient:'.$p[4], $catIds[$p[1]] ?? null,
            $p[2].' | YASSOTA', mb_substr($p[3], 0, 155), $p[5], 100 - $i * 7, date('Y-m-d H:i:s', $t - $i * 7200)]);
        $pid = (int)$pdo->lastInsertId();
        foreach (explode(',', $p[5]) as $tag) ya_attach_hashtag($pdo, $pid, trim($tag));
    }
    $pdo->exec("UPDATE users u SET posts_count = (SELECT COUNT(*) FROM posts p WHERE p.user_id=u.id)");
    foreach ($uids as $id) {
        $pdo->prepare("UPDATE users SET followers_count = ?, following_count = ? WHERE id = ?")
            ->execute([random_int(120, 8400), random_int(30, 400), $id]);
    }
}

function ya_attach_hashtag(PDO $pdo, int $postId, string $name): void {
    $name = ltrim(trim($name), '#'); if ($name === '') return;
    $slug = ya_slugify($name); if ($slug === '') return;
    $pdo->prepare("INSERT INTO hashtags (slug,name,posts_count) VALUES (?,?,1) ON DUPLICATE KEY UPDATE posts_count = posts_count + 1")->execute([$slug, $name]);
    $hid = (int)$pdo->query("SELECT id FROM hashtags WHERE slug = " . $pdo->quote($slug))->fetch()['id'];
    $pdo->prepare("INSERT IGNORE INTO post_hashtags (post_id,hashtag_id) VALUES (?,?)")->execute([$postId, $hid]);
}

$GLOBALS['__set'] = null;
function setting(string $k, string $d = ''): string {
    global $pdo;
    if ($GLOBALS['__set'] === null) $GLOBALS['__set'] = $pdo->query("SELECT k,v FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    return $GLOBALS['__set'][$k] ?? $d;
}
function set_setting(string $k, string $v): void {
    global $pdo;
    $pdo->prepare("INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)")->execute([$k, $v]);
    if (is_array($GLOBALS['__set'])) $GLOBALS['__set'][$k] = $v;
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function base_path(): string {
    $b = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $b === '/' ? '' : $b;
}
function site_origin(): string {
    global $__https;
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($__https ? 'https' : 'http') . '://' . $host;
}
function url(string $path = ''): string {
    $cfg = setting('site_url');
    $base = $cfg !== '' ? rtrim($cfg, '/') : (site_origin() . base_path());
    return $base . '/' . ltrim($path, '/');
}
function asset(string $p): string { return url('assets/' . ltrim($p, '/')); }
function post_url(array $p): string { return url('post/' . $p['slug']); }
function user_url($username): string { return url('user/' . rawurlencode($username)); }
function tag_url($slug): string { return url('tag/' . rawurlencode($slug)); }
function category_url($slug): string { return url('category/' . rawurlencode($slug)); }

function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_ok(): bool {
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '');
    return !empty($_SESSION['csrf']) && is_string($t) && hash_equals($_SESSION['csrf'], $t);
}

function ya_slugify(string $s): string {
    $map = ['ا'=>'a','أ'=>'a','إ'=>'i','آ'=>'a','ب'=>'b','ت'=>'t','ث'=>'th','ج'=>'j','ح'=>'h','خ'=>'kh',
        'د'=>'d','ذ'=>'dh','ر'=>'r','ز'=>'z','س'=>'s','ش'=>'sh','ص'=>'s','ض'=>'d','ط'=>'t','ظ'=>'z',
        'ع'=>'a','غ'=>'gh','ف'=>'f','ق'=>'q','ك'=>'k','ل'=>'l','م'=>'m','ن'=>'n','ه'=>'h','و'=>'w',
        'ي'=>'y','ى'=>'a','ة'=>'a','ء'=>'','ئ'=>'y','ؤ'=>'w',
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'];
    $s = strtr($s, $map);
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('/[^a-z0-9]+/u', '-', $s);
    $s = trim((string)$s, '-');
    if (strlen($s) > 90) $s = substr($s, 0, 90);
    $s = trim($s, '-');
    return $s !== '' ? $s : 'post';
}
function ya_unique_slug(PDO $pdo, string $table, string $base): string {
    $base = ya_slugify($base); $slug = $base; $i = 1;
    $q = $pdo->prepare("SELECT 1 FROM `$table` WHERE slug = ? LIMIT 1");
    while (true) { $q->execute([$slug]); if (!$q->fetch()) return $slug; $slug = $base . '-' . (++$i); }
}

function time_ago($dt): string {
    $t = is_numeric($dt) ? (int)$dt : strtotime((string)$dt);
    $d = max(0, time() - $t);
    if ($d < 60) return 'الآن';
    if ($d < 3600) return floor($d / 60) . ' د';
    if ($d < 86400) return floor($d / 3600) . ' س';
    if ($d < 2592000) return floor($d / 86400) . ' ي';
    return date('Y/m/d', $t);
}
function num_fmt($n): string {
    $n = (int)$n;
    if ($n >= 1000000) return rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'M';
    if ($n >= 1000) return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K';
    return (string)$n;
}

function ya_hue(string $s): int { return crc32($s) % 360; }
function avatar_bg($u): string {
    $a = $u['avatar'] ?? '';
    if (strpos($a, 'avatar:') === 0) return substr($a, 7);
    $h = ya_hue(($u['username'] ?? 'x'));
    return 'hsl(' . $h . ',70%,55%)';
}
function post_bg(array $p): string {
    $img = $p['image'] ?? '';
    if (strpos($img, 'gradient:') === 0) {
        $c = substr($img, 9); $h = ya_hue($p['slug'] ?? 'x');
        return "linear-gradient(135deg,$c,hsl(" . (($h + 40) % 360) . ",70%,45%))";
    }
    return '';
}
function is_real_image($v): bool { return $v !== '' && strpos($v, 'gradient:') !== 0 && strpos($v, 'avatar:') !== 0; }

function current_user(): ?array {
    global $pdo;
    static $cached = false, $u = null;
    if ($cached) return $u;
    $cached = true;
    if (empty($_SESSION['uid'])) return $u = null;
    $st = $pdo->prepare("SELECT * FROM users WHERE id = ? AND banned = 0");
    $st->execute([(int)$_SESSION['uid']]);
    return $u = ($st->fetch() ?: null);
}
function require_login(): array {
    $u = current_user();
    if (!$u) { header('Location: ' . url('login?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/'))); exit; }
    return $u;
}
function require_admin(): array {
    $u = require_login();
    if ((int)$u['is_admin'] !== 1) { http_response_code(403); exit('403'); }
    return $u;
}

function ya_gen_username(PDO $pdo, string $seed): string {
    $base = ya_slugify(str_replace('-', '', $seed)); $base = $base !== 'post' ? $base : 'user';
    $base = substr($base, 0, 20); $name = $base; $i = 0;
    $q = $pdo->prepare("SELECT 1 FROM users WHERE username = ? LIMIT 1");
    while (true) { $q->execute([$name]); if (!$q->fetch()) return $name; $name = $base . random_int(10, 9999); if (++$i > 12) return $base . bin2hex(random_bytes(3)); }
}

function auth_register(string $email, string $password, string $name): array {
    global $pdo;
    $email = trim(mb_strtolower($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'error' => 'بريد إلكتروني غير صالح.'];
    if (strlen($password) < 6) return ['ok' => false, 'error' => 'كلمة المرور 6 أحرف على الأقل.'];
    $q = $pdo->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1"); $q->execute([$email]);
    if ($q->fetch()) return ['ok' => false, 'error' => 'هذا البريد مسجّل مسبقاً — سجّل الدخول.'];
    $username = ya_gen_username($pdo, explode('@', $email)[0] ?: ($name ?: 'user'));
    $pdo->prepare("INSERT INTO users (username,name,email,password_hash) VALUES (?,?,?,?)")
        ->execute([$username, trim($name) ?: $username, $email, password_hash($password, PASSWORD_DEFAULT)]);
    $_SESSION['uid'] = (int)$pdo->lastInsertId();
    session_regenerate_id(true);
    return ['ok' => true];
}
function auth_login(string $email, string $password): array {
    global $pdo;
    $email = trim(mb_strtolower($email));
    $st = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1"); $st->execute([$email]);
    $u = $st->fetch();
    if (!$u || $u['password_hash'] === '' || !password_verify($password, $u['password_hash']))
        return ['ok' => false, 'error' => 'البريد أو كلمة المرور غير صحيحة.'];
    if ((int)$u['banned'] === 1) return ['ok' => false, 'error' => 'هذا الحساب محظور.'];
    $_SESSION['uid'] = (int)$u['id']; session_regenerate_id(true);
    return ['ok' => true];
}
function auth_logout(): void { $_SESSION = []; session_destroy(); }

function google_verify(string $idToken): ?array {
    $clientId = setting('google_client_id');
    if ($clientId === '' || $idToken === '') return null;
    $ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12]);
    $res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($res === false || $code !== 200) return null;
    $d = json_decode($res, true);
    if (!is_array($d)) return null;
    if (($d['aud'] ?? '') !== $clientId) return null;
    if (!in_array($d['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true)) return null;
    if ((int)($d['exp'] ?? 0) < time()) return null;
    if (($d['email_verified'] ?? 'false') !== 'true' && ($d['email_verified'] ?? true) !== true) return null;
    return ['sub' => $d['sub'] ?? '', 'email' => mb_strtolower($d['email'] ?? ''), 'name' => $d['name'] ?? '', 'picture' => $d['picture'] ?? ''];
}
function auth_google(string $idToken): array {
    global $pdo;
    $g = google_verify($idToken);
    if (!$g || $g['sub'] === '') return ['ok' => false, 'error' => 'تعذّر التحقق من حساب جوجل. تأكد من ضبط Google Client ID في الإدارة.'];
    $st = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR (email <> '' AND email = ?) LIMIT 1");
    $st->execute([$g['sub'], $g['email']]);
    $u = $st->fetch();
    if ($u) {
        if ($u['google_id'] === '') $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?")->execute([$g['sub'], $u['id']]);
        if ($u['avatar'] === '' && $g['picture']) $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$g['picture'], $u['id']]);
        if ((int)$u['banned'] === 1) return ['ok' => false, 'error' => 'هذا الحساب محظور.'];
        $_SESSION['uid'] = (int)$u['id'];
    } else {
        $username = ya_gen_username($pdo, explode('@', $g['email'])[0] ?: 'user');
        $pdo->prepare("INSERT INTO users (username,name,email,google_id,avatar) VALUES (?,?,?,?,?)")
            ->execute([$username, $g['name'] ?: $username, $g['email'], $g['sub'], $g['picture']]);
        $_SESSION['uid'] = (int)$pdo->lastInsertId();
    }
    session_regenerate_id(true);
    return ['ok' => true];
}

function rate_ok(string $key, int $limit, int $window): bool {
    global $pdo;
    $now = time(); $start = $now - ($now % $window);
    $k = substr($key, 0, 120);
    $pdo->prepare("INSERT INTO rate_limits (k,window_start,hits) VALUES (?,?,1)
        ON DUPLICATE KEY UPDATE hits = IF(window_start = VALUES(window_start), hits + 1, 1), window_start = VALUES(window_start)")
        ->execute([$k, $start]);
    $st = $pdo->prepare("SELECT hits FROM rate_limits WHERE k = ?"); $st->execute([$k]);
    return (int)($st->fetch()['hits'] ?? 0) <= $limit;
}

function icon(string $n, int $s = 24): string {
    $I = [
        'home'=>'<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/>',
        'home-fill'=>'<path d="M3 10.5 12 3l9 7.5V21H3z" fill="currentColor" stroke="none"/>',
        'compass'=>'<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',
        'plus'=>'<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
        'plus-sq'=>'<rect x="3" y="3" width="18" height="18" rx="5"/><path d="M12 8v8M8 12h8"/>',
        'bell'=>'<path d="M18 9a6 6 0 1 0-12 0c0 5-2 6-2 6h16s-2-1-2-6"/><path d="M10.5 19a1.8 1.8 0 0 0 3 0"/>',
        'chat'=>'<path d="M21 12a8 8 0 0 1-11.5 7.2L4 21l1.8-5.5A8 8 0 1 1 21 12z"/>',
        'user'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'search'=>'<circle cx="11" cy="11" r="7"/><path d="m16.5 16.5 4 4"/>',
        'heart'=>'<path d="M12 20s-7-4.3-9.3-8.6C1 8 3 4.8 6.2 4.8c2 0 3.2 1.2 3.8 2.2.6-1 1.8-2.2 3.8-2.2C21 4.8 23 8 21.3 11.4 19 15.7 12 20 12 20z"/>',
        'heart-fill'=>'<path d="M12 20s-7-4.3-9.3-8.6C1 8 3 4.8 6.2 4.8c2 0 3.2 1.2 3.8 2.2.6-1 1.8-2.2 3.8-2.2C21 4.8 23 8 21.3 11.4 19 15.7 12 20 12 20z" fill="currentColor" stroke="none"/>',
        'comment'=>'<path d="M21 11.5a8 8 0 0 1-11.5 7.2L4 20l1.3-4.5A8 8 0 1 1 21 11.5z"/>',
        'share'=>'<path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7"/><path d="M12 15V3M8 7l4-4 4 4"/>',
        'bookmark'=>'<path d="M6 3h12v18l-6-4-6 4z"/>',
        'bookmark-fill'=>'<path d="M6 3h12v18l-6-4-6 4z" fill="currentColor" stroke="none"/>',
        'video'=>'<rect x="3" y="6" width="18" height="12" rx="3"/><path d="m10 9 5 3-5 3z" fill="currentColor" stroke="none"/>',
        'grid'=>'<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'fire'=>'<path d="M12 3c1 3 4 4 4 8a4 4 0 0 1-8 0c0-1.5.5-2.5 1-3 .3 1 1 1.5 1.5 1.5C10 8 11 5.5 12 3z"/>',
        'check'=>'<path d="M4 12l5 5L20 6"/>',
        'verified'=>'<path d="m12 2 2.4 1.8 3 .2.9 2.9 2.4 1.9-1 2.9 1 2.9-2.4 1.9-.9 2.9-3 .2L12 22l-2.4-1.8-3-.2-.9-2.9L3.3 15l1-2.9-1-2.9 2.4-1.9.9-2.9 3-.2z" fill="currentColor" stroke="none"/><path d="m8.5 12 2.3 2.3 4.7-4.6" stroke="#fff" stroke-width="2"/>',
        'x'=>'<path d="M6 6l12 12M18 6 6 18"/>',
        'send'=>'<path d="M21 4 3 11l6 2 2 6z"/><path d="m21 4-10 9"/>',
        'image'=>'<rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="8.5" cy="9.5" r="1.8"/><path d="m4 18 5-5 4 3 3-2 4 4"/>',
        'settings'=>'<circle cx="12" cy="12" r="3.2"/><path d="M12 3v2.4M12 18.6V21M4.2 7.5l2 1.2M17.8 15.3l2 1.2M4.2 16.5l2-1.2M17.8 8.7l2-1.2"/>',
        'logout'=>'<path d="M15 4h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-3"/><path d="M10 12H3m3-3-3 3 3 3"/>',
        'cpu'=>'<rect x="6" y="6" width="12" height="12" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 2v2M15 2v2M9 20v2M15 20v2M2 9h2M2 15h2M20 9h2M20 15h2"/>',
        'gamepad'=>'<rect x="2" y="7" width="20" height="10" rx="5"/><path d="M7 11v2M6 12h2M15 11.5h.01M18 13.5h.01"/>',
        'sparkles'=>'<path d="M12 3l1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6z"/><path d="M18 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/>',
        'news'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 9h6M7 13h10M7 16h10"/>',
        'film'=>'<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M3 15h18M8 4v16M16 4v16"/>',
        'trophy'=>'<path d="M7 4h10v4a5 5 0 0 1-10 0zM7 6H4v1a3 3 0 0 0 3 3M17 6h3v1a3 3 0 0 1-3 3M9 15h6M8 20h8M12 15v3"/>',
        'camera'=>'<rect x="3" y="7" width="18" height="13" rx="3"/><circle cx="12" cy="13.5" r="3.5"/><path d="M8 7l1.5-2h5L16 7"/>',
        'briefcase'=>'<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/>',
        'book'=>'<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 19a2 2 0 0 1 2-2h13"/>',
        'link'=>'<path d="M9 15l6-6M10 6l1-1a4 4 0 0 1 6 6l-1 1M14 18l-1 1a4 4 0 0 1-6-6l1-1"/>',
        'location'=>'<path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'download'=>'<path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M4 17v2a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-2"/>',
        'shield'=>'<path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'trash'=>'<path d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2M6 7l1 13h10l1-13"/>',
        'globe'=>'<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>',
        'sun'=>'<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5 19 19M5 19l1.5-1.5M17.5 6.5 19 5"/>',
        'moon'=>'<path d="M20 14a8 8 0 1 1-9-11 6 6 0 0 0 9 11z"/>',
        'menu'=>'<path d="M4 7h16M4 12h16M4 17h16"/>',
        'flag'=>'<path d="M5 21V4h11l-1.5 4L16 12H5"/>',
        'lock'=>'<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'eye'=>'<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'more'=>'<circle cx="5" cy="12" r="1.6" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.6" fill="currentColor" stroke="none"/><circle cx="19" cy="12" r="1.6" fill="currentColor" stroke="none"/>',
        'google'=>'<path d="M21 12.2c0-.6-.1-1.2-.2-1.8H12v3.6h5.1a4.4 4.4 0 0 1-1.9 2.9v2.4h3.1c1.8-1.7 2.7-4.1 2.7-7.1z" fill="#4285F4" stroke="none"/><path d="M12 21c2.4 0 4.5-.8 6-2.2l-3.1-2.4c-.8.6-1.9.9-2.9.9-2.3 0-4.2-1.5-4.9-3.6H3.9v2.5A9 9 0 0 0 12 21z" fill="#34A853" stroke="none"/><path d="M7.1 13.7a5.4 5.4 0 0 1 0-3.4V7.8H3.9a9 9 0 0 0 0 8.4z" fill="#FBBC05" stroke="none"/><path d="M12 6.6c1.3 0 2.5.5 3.4 1.3l2.6-2.6A9 9 0 0 0 3.9 7.8l3.2 2.5C7.8 8.1 9.7 6.6 12 6.6z" fill="#EA4335" stroke="none"/>',
    ];
    $b = $I[$n] ?? $I['grid'];
    $extra = $n === 'google' ? '' : ' fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"';
    return '<svg class="ic" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24"' . $extra . ' aria-hidden="true">' . $b . '</svg>';
}

require_once APP_ROOT . '/partials.php';

function ya_setup_wizard(): void {
    $err = '';
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $h = trim($_POST['db_host'] ?? ''); $n = trim($_POST['db_name'] ?? '');
        $u = trim($_POST['db_user'] ?? ''); $p = (string)($_POST['db_pass'] ?? '');
        $au = trim($_POST['admin_user'] ?? '') ?: 'admin'; $ap = (string)($_POST['admin_pass'] ?? '');
        if ($h === '' || $n === '' || $u === '') $err = 'أكمل بيانات قاعدة البيانات.';
        elseif (strlen($ap) < 6) $err = 'كلمة مرور المدير 6 أحرف على الأقل.';
        else {
            try {
                $t = new PDO("mysql:host=$h;dbname=$n;charset=utf8mb4", $u, $p, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]);
                $php = "<?php\ndefine('DB_HOST', " . var_export($h, true) . ");\ndefine('DB_NAME', " . var_export($n, true)
                    . ");\ndefine('DB_USER', " . var_export($u, true) . ");\ndefine('DB_PASS', " . var_export($p, true)
                    . ");\ndefine('APP_SECRET', " . var_export(bin2hex(random_bytes(16)), true) . ");\n";
                file_put_contents(APP_ROOT . '/config.local.php', $php);
                @chmod(APP_ROOT . '/config.local.php', 0600);
                ya_ensure_schema($t);
                $au2 = preg_replace('/[^a-z0-9_]/', '', mb_strtolower($au)) ?: 'admin';
                $t->prepare("INSERT INTO users (username,name,email,password_hash,is_admin,verified) VALUES (?,?,?,?,1,1)
                    ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), is_admin=1")
                    ->execute([$au2, 'المدير', $au2 . '@yassota.com', password_hash($ap, PASSWORD_DEFAULT)]);
                header('Location: ' . (dirname($_SERVER['SCRIPT_NAME']) === '/' ? '/' : dirname($_SERVER['SCRIPT_NAME']) . '/'));
                exit;
            } catch (Throwable $ex) { $err = 'فشل الاتصال: ' . $ex->getMessage(); }
        }
    }
    ?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>إعداد YASSOTA</title><style>
    body{margin:0;font-family:system-ui,'Segoe UI',Tahoma,sans-serif;background:#0b0d12;color:#e7eaf0;padding:40px 16px;display:flex;justify-content:center}
    .box{max-width:460px;width:100%;background:#141821;border:1px solid #232a37;border-radius:18px;padding:30px}
    h1{margin:0 0 4px;font-size:22px;background:linear-gradient(90deg,#0ea5e9,#8b5cf6);-webkit-background-clip:text;background-clip:text;color:transparent}
    p.s{color:#8b95a7;font-size:13px;margin:0 0 20px;line-height:1.8}
    label{display:block;font-size:12.5px;margin:13px 0 5px;color:#aeb7c6}
    input{width:100%;box-sizing:border-box;padding:11px 12px;border-radius:10px;border:1px solid #232a37;background:#0b0d12;color:#fff;font-size:14px}
    button{width:100%;margin-top:22px;padding:13px;border:0;border-radius:12px;background:linear-gradient(90deg,#0ea5e9,#6366f1);color:#fff;font-weight:700;font-size:15px;cursor:pointer}
    .err{background:#2a1015;border:1px solid #7f1d1d;color:#fca5a5;padding:10px;border-radius:10px;font-size:13px;margin-bottom:14px}
    hr{border:0;border-top:1px solid #232a37;margin:20px 0}</style></head><body><div class="box">
    <h1>YASSOTA</h1><p class="s">إعداد المنصة لأول مرة. أدخل بيانات MySQL من لوحة استضافتك — تُحفظ في ملف محلي مستبعد من git، ولا تُرفع أبداً.</p>
    <?php if ($err) echo '<div class="err">' . e($err) . '</div>'; ?>
    <form method="post">
    <label>MySQL Hostname</label><input name="db_host" value="<?= e($_POST['db_host'] ?? 'sqlXXX.infinityfree.com') ?>" required>
    <label>MySQL Database Name</label><input name="db_name" value="<?= e($_POST['db_name'] ?? 'if0_XXXXXXXX_yassota') ?>" required>
    <label>MySQL Username</label><input name="db_user" value="<?= e($_POST['db_user'] ?? 'if0_XXXXXXXX') ?>" required>
    <label>MySQL Password</label><input name="db_pass" type="password" required>
    <hr><label>اسم مستخدم المدير</label><input name="admin_user" value="admin" required>
    <label>كلمة مرور المدير</label><input name="admin_pass" type="password" minlength="6" required>
    <button type="submit">تثبيت YASSOTA</button></form></div></body></html><?php
}
