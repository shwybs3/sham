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

// ─── Evil security system ───────────────────────────────────────
// This IP is always exempt from all security checks, view/download
// counting, and analytics. Edit to match the real admin's IP.
define('EVIL_ADMIN_IP', '149.86.144.52');

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
    <style>body{margin:0;font-family:Tahoma,Arial,sans-serif;background:#f5f7fb;color:#0f172a;padding:40px 16px;line-height:1.9}
    .box{max-width:680px;margin:0 auto;background:#fff;border:1px solid rgba(15,23,42,.09);border-radius:14px;padding:32px;box-shadow:0 4px 24px rgba(15,23,42,.06)}
    h1{color:#dc2626;font-size:20px;margin:0 0 6px} p.sub{color:#64748b;font-size:13px;margin:0 0 20px}
    ol{padding-inline-start:20px;font-size:14px} li{margin-bottom:10px}
    code{background:#f1f4f9;color:#2563eb;padding:2px 8px;border-radius:6px;font-family:monospace;direction:ltr;display:inline-block}
    .err{margin-top:22px;background:#f1f4f9;border:1px solid rgba(220,38,38,.25);border-radius:10px;padding:14px;font-family:monospace;font-size:12px;color:#b91c1c;direction:ltr;text-align:left;word-break:break-all}
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
      parent_id INT NULL COMMENT 'NULL = original; set to parent app id for auto-translated versions',
      lang_code VARCHAR(10) NOT NULL DEFAULT 'ar' COMMENT 'BCP-47 language code',
      subdomain_slug VARCHAR(120) NULL COMMENT 'Optional custom subdomain slug (e.g. tiktok-download)',
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
      INDEX idx_category (category_id),
      INDEX idx_parent (parent_id),
      INDEX idx_lang (lang_code)
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

    $pdo->exec("CREATE TABLE IF NOT EXISTS app_articles (
      id INT AUTO_INCREMENT PRIMARY KEY,
      app_id INT NOT NULL,
      title VARCHAR(255) NOT NULL,
      slug VARCHAR(280) NOT NULL UNIQUE,
      seo_title VARCHAR(255),
      meta_description VARCHAR(320),
      body MEDIUMTEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_app (app_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'app_articles';

    // General-content blog — distinct from app_articles (which are always
    // tied to one specific app). Covers tutorials/news/comparisons/best-of
    // roundups/general articles: original editorial content beyond app
    // listing pages, for internal linking, SEO depth, and AdSense content volume.
    $pdo->exec("CREATE TABLE IF NOT EXISTS blog_posts (
      id INT AUTO_INCREMENT PRIMARY KEY,
      type ENUM('tutorial','news','comparison','best-apps','best-games','article') NOT NULL DEFAULT 'article',
      title VARCHAR(255) NOT NULL,
      slug VARCHAR(280) NOT NULL UNIQUE,
      seo_title VARCHAR(255),
      meta_description VARCHAR(320),
      keywords VARCHAR(500),
      excerpt VARCHAR(500),
      body MEDIUMTEXT,
      cover_image VARCHAR(300),
      status ENUM('published','draft') NOT NULL DEFAULT 'draft',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_type (type),
      INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'blog_posts';

    // Real, self-tracked event log (one row per view/download/search) — the
    // data source for the admin analytics dashboard. apps.views/downloads
    // stay as running totals for fast top-N queries; this table adds a
    // timestamp so the dashboard can show genuine daily trends, unlike the
    // running counters alone.
    $pdo->exec("CREATE TABLE IF NOT EXISTS page_events (
      id INT AUTO_INCREMENT PRIMARY KEY,
      event_type ENUM('view','download','search') NOT NULL,
      app_id INT NULL,
      meta VARCHAR(255) NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_type_date (event_type, created_at),
      INDEX idx_app (app_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'page_events';

    $pdo->exec("CREATE TABLE IF NOT EXISTS security_log (
      id INT AUTO_INCREMENT PRIMARY KEY,
      event_type VARCHAR(60) NOT NULL,
      severity ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
      ip VARCHAR(45) NULL,
      detail TEXT NULL,
      filename VARCHAR(300) NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_sev (severity),
      INDEX idx_date (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'security_log';

    $pdo->exec("CREATE TABLE IF NOT EXISTS indexnow_log (
      id INT AUTO_INCREMENT PRIMARY KEY,
      url TEXT NOT NULL,
      engine VARCHAR(60) NOT NULL DEFAULT 'indexnow',
      status ENUM('success','failed','skipped') NOT NULL DEFAULT 'success',
      http_code SMALLINT UNSIGNED NULL,
      reason VARCHAR(500) NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_status (status),
      INDEX idx_date (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'indexnow_log';

    // Additive migrations for apps table (existing installs don't have new columns)
    $appCols = $pdo->query("SHOW COLUMNS FROM apps")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('parent_id', $appCols))
        @$pdo->exec("ALTER TABLE apps ADD COLUMN parent_id INT NULL AFTER id");
    if (!in_array('lang_code', $appCols))
        @$pdo->exec("ALTER TABLE apps ADD COLUMN lang_code VARCHAR(10) NOT NULL DEFAULT 'ar' AFTER parent_id");
    if (!in_array('subdomain_slug', $appCols))
        @$pdo->exec("ALTER TABLE apps ADD COLUMN subdomain_slug VARCHAR(120) NULL AFTER lang_code");
    // rating_count: explicit count supplied by admin, used in schema.org AggregateRating
    // so Google can show star snippets without waiting for real comment accumulation.
    if (!in_array('rating_count', $appCols))
        @$pdo->exec("ALTER TABLE apps ADD COLUMN rating_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER rating");
    // needs_update: flag to track apps that need an update
    if (!in_array('needs_update', $appCols))
        @$pdo->exec("ALTER TABLE apps ADD COLUMN needs_update TINYINT(1) NOT NULL DEFAULT 0");

    // Comments: add ip column if missing
    $commentCols = $pdo->query("SHOW COLUMNS FROM comments")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('ip', $commentCols))
        @$pdo->exec("ALTER TABLE comments ADD COLUMN ip VARCHAR(45) NULL AFTER status");

    // WebAuthn / biometric login credentials for admin
    $pdo->exec("CREATE TABLE IF NOT EXISTS webauthn_credentials (
      id INT AUTO_INCREMENT PRIMARY KEY,
      admin_id INT NOT NULL,
      credential_id VARCHAR(500) NOT NULL,
      public_key TEXT NOT NULL,
      sign_count INT NOT NULL DEFAULT 0,
      label VARCHAR(120) NOT NULL DEFAULT 'مفتاح بصمة',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      last_used_at DATETIME NULL,
      UNIQUE KEY uq_cred (credential_id(200)),
      INDEX idx_admin (admin_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'webauthn_credentials';

    // Evil — brute-force login tracking per IP
    $pdo->exec("CREATE TABLE IF NOT EXISTS evil_login_attempts (
      id INT AUTO_INCREMENT PRIMARY KEY,
      ip VARCHAR(45) NOT NULL,
      attempts INT UNSIGNED NOT NULL DEFAULT 0,
      first_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      last_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      locked_until DATETIME NULL,
      UNIQUE KEY uq_ip (ip),
      INDEX idx_locked (locked_until)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'evil_login_attempts';

    // Evil — banned IPs (escalating duration per ban_count)
    $pdo->exec("CREATE TABLE IF NOT EXISTS evil_banned_ips (
      id INT AUTO_INCREMENT PRIMARY KEY,
      ip VARCHAR(45) NOT NULL,
      reason VARCHAR(300) NOT NULL DEFAULT '',
      ban_count INT UNSIGNED NOT NULL DEFAULT 1,
      banned_until DATETIME NULL COMMENT 'NULL = permanent',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uq_ip (ip),
      INDEX idx_until (banned_until)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'evil_banned_ips';

    // Foreign key (best-effort, ignored if already present or unsupported)
    try {
        $fk = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='apps' AND CONSTRAINT_TYPE='FOREIGN KEY'")->fetchColumn();
        if (!$fk) {
            $pdo->exec("ALTER TABLE apps ADD CONSTRAINT fk_apps_category
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL");
        }
    } catch (Throwable $e) { /* non-critical */ }
    try {
        $fk2 = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='app_articles' AND CONSTRAINT_TYPE='FOREIGN KEY'")->fetchColumn();
        if (!$fk2) {
            $pdo->exec("ALTER TABLE app_articles ADD CONSTRAINT fk_articles_app
                FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE");
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
            'vt_status'        => "VARCHAR(20) NULL AFTER source_url",
            'vt_malicious'     => "INT NULL AFTER vt_status",
            'vt_total_engines' => "INT NULL AFTER vt_malicious",
            'vt_scanned_at'    => "DATETIME NULL AFTER vt_total_engines",
            'vt_analysis_id'   => "VARCHAR(255) NULL AFTER vt_scanned_at",
            'vt_permalink'     => "VARCHAR(600) NULL AFTER vt_analysis_id",
            'link_verified'    => "TINYINT(1) NOT NULL DEFAULT 0 AFTER vt_permalink",
            'verified_at'      => "DATETIME NULL AFTER link_verified",
            'apk_path'         => "VARCHAR(500) NULL",
            'apk_size_bytes'   => "BIGINT UNSIGNED NULL",
            'apk_hash_sha256'  => "CHAR(64) NULL",
            'apk_hash_md5'     => "CHAR(32) NULL",
            'apk_uploaded_at'  => "DATETIME NULL",
            'download_source'  => "ENUM('playstore','apk','both') NOT NULL DEFAULT 'playstore'",
            'badge'            => "ENUM('','new','updated','hot','choice') NOT NULL DEFAULT ''",
            'last_indexed_at'  => "DATETIME NULL",
            'index_status'     => "ENUM('pending','indexed','error') NOT NULL DEFAULT 'pending'",
            'permissions'      => "TEXT NULL",
            'certificate_sha256' => "VARCHAR(120) NULL",
        ],
        'categories' => [
            'description' => "MEDIUMTEXT NULL AFTER icon_svg",
        ],
        'comments' => [
            'ip' => "VARCHAR(45) NULL AFTER body",
        ],
        'app_versions' => [
            'apk_path'        => "VARCHAR(500) NULL",
            'apk_hash_sha256' => "CHAR(64) NULL",
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

    // Seed 30 popular apps on first-ever DB connection (apps table empty)
    $appCount = (int)$pdo->query("SELECT COUNT(*) FROM apps")->fetchColumn();
    if ($appCount === 0) {
        $catMap = [];
        foreach ($pdo->query("SELECT id, slug FROM categories")->fetchAll() as $r) {
            $catMap[$r['slug']] = (int)$r['id'];
        }
        $seed = [
            // [slug, name, cat, developer, version, size_mb, rating, downloads, pkg, short_desc, long_desc, badge]
            ['whatsapp','واتساب','social','Meta Platforms','2.24.25','75',4.3,5000000,'com.whatsapp',
             'تطبيق المراسلة الفورية الأكثر شعبيةً في العالم بأكثر من ملياري مستخدم نشط.',
             'واتساب هو تطبيق مراسلة فورية يتيح إرسال الرسائل النصية والمقاطع الصوتية والصور والمستندات بكل سهولة. يدعم مكالمات الصوت والفيديو المجانية عبر الإنترنت ويعمل بتشفير تام من طرف إلى طرف لحماية خصوصيتك. يمكنك إنشاء مجموعات دردشة تضم آلاف الأعضاء، ومتابعة القنوات، ونشر الحالات اليومية. يعمل على كل شبكات الإنترنت بكفاءة عالية حتى مع الاتصالات البطيئة.','choice'],
            ['telegram','تيليجرام','social','Telegram FZ-LLC','10.3.5','80',4.7,1000000,'org.telegram.messenger',
             'منصة مراسلة سريعة وآمنة تتميز بمجموعات ضخمة وقنوات بث وروبوتات ذكية.',
             'تيليجرام هو تطبيق مراسلة يركز على السرعة والأمان، ويدعم إرسال ملفات يصل حجمها إلى 4 غيغابايت. يمكنك إنشاء مجموعات تضم 200,000 عضو وقنوات بث غير محدودة المتابعين. يوفر التطبيق مزامنة فورية عبر جميع أجهزتك ونسخاً احتياطياً سحابياً آمناً. يتميز بدعم الروبوتات والألعاب التفاعلية والملصقات المتحركة.','hot'],
            ['instagram','انستجرام','social','Meta Platforms','323.0.0','90',3.9,3000000,'com.instagram.android',
             'منصة مشاركة الصور ومقاطع الفيديو القصيرة الأشهر عالمياً.',
             'انستجرام هو التطبيق الأمثل لمشاركة لحظاتك اليومية عبر الصور ومقاطع الريلز القصيرة. يوفر أدوات تحرير احترافية وفلاتر متنوعة لتحسين صورك قبل نشرها. يمكنك متابعة أصدقائك ومشاهير العالم ومتاجرك المفضلة في خلاصة واحدة. يدعم القصص اليومية والبث المباشر والرسائل الخاصة وأرشيف المحادثات.',''],
            ['tiktok','تيك توك','social','TikTok Pte. Ltd.','34.5.3','95',4.3,2000000,'com.zhiliaoapp.musically',
             'منصة الفيديو القصير الأسرع نمواً في العالم تجمع الترفيه والإبداع في مكان واحد.',
             'تيك توك يتيح لك اكتشاف مقاطع فيديو مسلية وملهمة بإيماءة إصبع واحدة. يعتمد التطبيق على خوارزمية ذكية تتعلم اهتماماتك وتقدم لك محتوى مخصصاً لا يتوقف. يمكنك إنشاء مقاطعك الخاصة بأدوات تحرير متقدمة وإضافة مؤثرات صوتية وموسيقى. يضم مجتمعاً إبداعياً من المليارات حول العالم.','hot'],
            ['snapchat','سناب شات','social','Snap Inc.','12.85.0','85',4.1,2000000,'com.snapchat.android',
             'تطبيق المشاركة اللحظية للصور ومقاطع الفيديو التي تختفي تلقائياً.',
             'سناب شات يجعل التواصل أكثر متعةً وعفويةً من خلال صور ومقاطع تختفي تلقائياً بعد المشاهدة. يوفر فلاتر وعدسات واقع معزز مبتكرة تضاف يومياً. يمكنك متابعة أصدقائك عبر الخريطة ومشاركة موقعك الحقيقي، وإرسال صور الذاكرة المحفوظة. يدعم القصص اليومية وقناة Spotlight للمحتوى الفيروسي.',''],
            ['facebook','فيسبوك','social','Meta Platforms','467.0.0','100',2.9,5000000,'com.facebook.katana',
             'الشبكة الاجتماعية الأكبر في العالم للتواصل مع العائلة والأصدقاء ومتابعة الأخبار.',
             'فيسبوك يربطك بمن تحب ويبقيك على اطلاع بأحدث الأخبار والأحداث حول العالم. يتيح إنشاء مجموعات ومتابعة صفحات المشاهير والمتاجر والمؤسسات. يوفر سوقاً إلكترونياً للبيع والشراء، وميزة البث المباشر للتفاعل الفوري مع متابعيك. يدعم إنشاء فعاليات وتذكيرات أعياد الميلاد والتجمعات.',''],
            ['twitter-x','تويتر X','social','X Corp.','10.13.0','80',3.8,1000000,'com.twitter.android',
             'منصة التدوين المصغر حيث تتداول الأخبار والآراء وتُصاغ الاتجاهات العالمية.',
             'تويتر X هو الفضاء الرقمي للنقاش الحر والتعبير عن الرأي ومتابعة الأحداث لحظة بلحظة. يتيح نشر تغريدات نصية وصور ومقاطع فيديو والتفاعل مع المجتمع العالمي. يمكنك متابعة المستجدات عبر الوسوم الرائجة وتصفح محادثات الصوت الحية. يوفر وضع القراءة الطويلة للمقالات والمحتوى التحليلي.','new'],
            ['youtube','يوتيوب','tools','Google LLC','19.37.39','80',4.2,5000000,'com.google.android.youtube',
             'أكبر منصة لمشاركة ومشاهدة مقاطع الفيديو بأكثر من ملياري مستخدم شهرياً.',
             'يوتيوب يوفر وصولاً غير محدود إلى مئات الملايين من مقاطع الفيديو في شتى المجالات من التعليم والترفيه والموسيقى والأخبار. يمكنك الاشتراك في قنواتك المفضلة وإنشاء قوائم تشغيل خاصة ومشاهدة المحتوى دون إنترنت مع الاشتراك المدفوع. يدعم البث المباشر والمحتوى بدقة 4K وميزات التعليق والتفاعل.','choice'],
            ['google-maps','خرائط جوجل','tools','Google LLC','11.107.0','95',4.4,5000000,'com.google.android.apps.maps',
             'خرائط ملاحية دقيقة بتحديثات فورية للحركة المرورية وتوجيه ذكي لكل وجهة.',
             'خرائط جوجل هي المرجع الأول للملاحة والتنقل حول العالم، توفر اتجاهات دقيقة للسيارات والمشاة والدراجات ووسائل النقل العام. تعرض حركة المرور الفورية وتقترح مسارات بديلة لتوفير وقتك. يمكنك البحث عن المطاعم والمحلات وساعات عملها وتقييمات المستخدمين، وتحميل الخرائط للاستخدام دون إنترنت.','choice'],
            ['google-translate','ترجمة جوجل','tools','Google LLC','8.6.50','60',4.2,1000000,'com.google.android.apps.translate',
             'ترجمة فورية لأكثر من 130 لغة عبر النص والصوت والكاميرا.',
             'ترجمة جوجل تكسر حاجز اللغة وتتيح التواصل بسهولة في أي مكان. يمكنك كتابة النص أو التحدث به أو توجيه كاميرا هاتفك نحو لافتة أو قائمة طعام للترجمة الفورية بالواقع المعزز. يدعم الترجمة في الاتجاهين وحفظ الكلمات المفضلة وتصحيح اللفظ. يعمل دون اتصال بالإنترنت بعد تحميل حزم اللغات.',''],
            ['cloudflare-1111','1.1.1.1 - DNS الأسرع','tools','Cloudflare Inc.','6.26','30',4.5,50000000,'com.cloudflare.oneDotOneDotOneDotOne',
             'أسرع DNS عام في العالم يحميك من التتبع ويسرّع تصفحك للإنترنت.',
             'تطبيق 1.1.1.1 من كلاود فلير يضبط هاتفك لاستخدام خوادم DNS الأسرع والأكثر خصوصية في العالم. يحجب التتبع الإعلاني ويحمي استفساراتك من التجسس والتسجيل من قِبَل مزود الإنترنت. يوفر وضع WARP الذي يوجه حركة مرورك عبر شبكة كلاود فلير لزيادة السرعة والأمان. يحتوي على لوحة معلومات تظهر تحسّن سرعة الاتصال بعد التفعيل.','hot'],
            ['files-by-google','ملفات من جوجل','tools','Google LLC','1.0.638','35',4.4,1000000,'com.google.android.apps.nbu.files',
             'مدير ملفات ذكي من جوجل يحرر مساحة هاتفك بضغطة واحدة.',
             'ملفات من جوجل يساعدك على إدارة مخزن هاتفك بذكاء من خلال اكتشاف الملفات المكررة والصور الضبابية والتطبيقات غير المستخدمة. يتيح حذف الملفات غير الضرورية بأمان وتنظيم ما يهمك في مجلدات مرتبة. يدعم مشاركة الملفات دون إنترنت مع الأجهزة القريبة عبر اتصال نقطة إلى نقطة سريع جداً. يتكامل مع جوجل درايف للنسخ الاحتياطي السحابي التلقائي.',''],
            ['microsoft-word','مايكروسوفت وورد','productivity','Microsoft Corporation','16.0.17328','250',4.3,500000000,'com.microsoft.office.word',
             'معالج الكلمات الأشهر في العالم يتيح إنشاء وتعديل مستندات احترافية من هاتفك.',
             'مايكروسوفت وورد يوفر تجربة كتابة كاملة على الهاتف بأدوات تنسيق متقدمة وتدقيق إملائي ونحوي ذكي. يمكنك فتح ملفات DOCX وتحريرها والتعاون عليها مع فريقك في الوقت الفعلي. يدعم التحويل الفوري بين صيغ المستندات المختلفة وحفظها على OneDrive أو شريحة الذاكرة. يحتوي على قوالب احترافية جاهزة للمقالات والتقارير والسير الذاتية.',''],
            ['zoom','زووم','productivity','Zoom Video Communications','5.17.10','120',4.0,500000000,'us.zoom.videomeetings',
             'منصة الاجتماعات المرئية الأولى في العالم للعمل والتعلم عن بُعد.',
             'زووم يجعل اجتماعاتك المرئية سلسة ومثمرة مهما كان عدد المشاركين وموقعهم. يدعم ما يصل إلى 1000 مشارك في اجتماع واحد مع مشاركة الشاشة والتسجيل السحابي والغرف الجانبية. يوفر خلفيات افتراضية وتصفية الضوضاء بالذكاء الاصطناعي لاجتماعات نظيفة. يتيح جدولة الاجتماعات والتكامل مع التقويمات ورسائل الدردشة الفوري.',  ''],
            ['google-drive','جوجل درايف','productivity','Google LLC','2.24.357','75',4.3,5000000000,'com.google.android.apps.docs',
             'مساحة تخزين سحابية آمنة من جوجل تبقي ملفاتك في متناول يدك في أي وقت.',
             'جوجل درايف يوفر 15 غيغابايت مجاناً لتخزين صورك ومستنداتك وملفاتك بأمان على السحابة. يتيح الوصول إلى ملفاتك من أي جهاز ومشاركتها مع الآخرين بعروض أذونات مرنة. يتكامل مع تطبيقات جوجل الأخرى كمستندات وجداول وعروض جوجل للعمل التعاوني الفوري. يدعم البحث الذكي داخل الملفات حتى المصورة.',  ''],
            ['adobe-acrobat-reader','أدوبي أكروبات ريدر','productivity','Adobe Inc.','24.10.0','150',4.5,500000000,'com.adobe.reader',
             'قارئ PDF الأكثر ثقةً في العالم لفتح وتعليق وتوقيع المستندات الرقمية.',
             'أدوبي أكروبات ريدر هو التطبيق الأمثل لعرض ملفات PDF بدقة عالية على شاشة هاتفك. يتيح التعليق على النصوص وإضافة ملاحظات وتوقيع المستندات بصورة رقمية قانونية. يمكنك البحث داخل الوثائق وتحويلها إلى ملفات وورد أو الاستخراج المباشر للصور. يدعم قراءة PDF الضخمة بسلاسة مع ميزة التمرير الذكي.','choice'],
            ['notion','نوشن','productivity','Notion Labs Inc.','0.6.2398','90',4.6,50000000,'notion.id',
             'مساحة عمل شاملة تجمع بين الملاحظات وقواعد البيانات وإدارة المشاريع في مكان واحد.',
             'نوشن هو تطبيق إنتاجية متعدد الاستخدامات يتيح بناء أنظمة تنظيم مخصصة من الصفر. يمكنك إنشاء صفحات غنية بالنصوص والجداول والقوائم والصور والملفات المضمنة. يوفر قوالب للمشاريع والمذكرات الأسبوعية وتتبع العادات وإدارة المهام. يتيح التعاون المتزامن مع الفريق وربط الصفحات ببعضها لبناء شبكة معرفة شخصية.','hot'],
            ['google-meet','جوجل ميت','productivity','Google LLC','2024.09.1','70',4.1,1000000000,'com.google.android.apps.meetings',
             'تطبيق مكالمات مرئية آمن من جوجل مدمج مع بيئة عمل Google Workspace.',
             'جوجل ميت يوفر اجتماعات مرئية عالية الجودة مجاناً لما يصل إلى 100 مشارك. يتكامل تماماً مع جيميل وجوجل تقويم مما يجعل جدولة الاجتماعات والانضمام إليها أمراً سهلاً. يوفر تصفية الضوضاء الذكية وتعديل الإضاءة التلقائي لاجتماعات أوضح. يدعم مشاركة الشاشة والتسجيل والترجمة الفورية الآلية أثناء الاجتماع.',''],
            ['pubg-mobile','ببجي موبايل','games','KRAFTON Inc.','3.2.0','1200',4.2,1000000000,'com.tencent.ig',
             'لعبة البقاء الميداني الأشهر على الهاتف تجمعك مع 100 لاعب في معركة إلى الموت.',
             'ببجي موبايل هي لعبة battle royale تلقي بك في ساحة معركة ضخمة مع 99 لاعباً آخر بهدف الاستمرار وتحقيق النصر. تتميز بجرافيكس عالية الدقة وفيزياء واقعية وتنوع في الأسلحة والمركبات والخرائط. توفر أوضاع لعب فردية وثنائية وبالفرق، إضافة إلى أوضاع مؤقتة مبتكرة. تحدّث المواسم بانتظام بمحتوى جديد وخرائط ومسابقات عالمية.','hot'],
            ['free-fire','فري فاير','games','Garena Online','1.104.1','800',4.1,1000000000,'com.dts.freefireth',
             'لعبة بقاء ميداني مصممة خصيصاً للهواتف الذكية بمعارك 10 دقائق متسارعة.',
             'فري فاير تقدم تجربة battle royale سلسة على الهواتف المتوسطة والمنخفضة المواصفات دون التأثير على جودة اللعب. تجمع 50 لاعباً في معركة تدوم 10 دقائق على جزيرة مليئة بالأسلحة والمركبات والكنوز. تتميز بأنظمة شخصيات متفردة لكل منها قدرات خاصة تضيف عمقاً استراتيجياً. تتوفر أوضاع الزوجي والرباعي والمعارك الخاصة.','hot'],
            ['clash-of-clans','كلاش أوف كلانس','games','Supercell','16.0.5','250',4.6,500000000,'com.supercell.clashofclans',
             'لعبة استراتيجية تبني فيها قريتك وتشكل قبيلتك وتخوض حروب قبائل لا تنتهي.',
             'كلاش أوف كلانس هي لعبة استراتيجية ملحمية تبني فيها قريتك وتدربها وتحميها من هجمات الأعداء. انضم إلى قبيلة أو أسس قبيلتك الخاصة وتعاون مع أعضائها في حروب القبائل الأسبوعية. تطور جيشك من المجنداتي البسيط إلى التنين الناري والأبطال الأسطوريين. يحتوي التطبيق على تحديثات موسمية منتظمة بأحداث خاصة ومحتوى حصري.','choice'],
            ['minecraft','ماين كرافت','games','Mojang Studios','1.21.31','700',4.5,50000000,'com.mojang.minecraftpe',
             'عالم مفتوح لا حدود له تبني فيه وتستكشف وتبدع بمكعبات بكسلية أيقونية.',
             'ماين كرافت هي لعبة الإبداع الحر حيث تُشيّد أي شيء يمكن تخيّله من كوخ بسيط إلى ناطحات سحاب ضخمة. تستكشف عوالم توليدية لا نهائية مليئة بالكهوف والغابات والمحيطات والجبال. تبقى على قيد الحياة في وضع البقاء بجمع الموارد وصناعة الأدوات ومحاربة الوحوش. يدعم اللعب المتعدد عبر الإنترنت أو الشبكة المحلية مع أصدقائك.','choice'],
            ['subway-surfers','صب واي سيرفرز','games','Sybo Games ApS','3.32.0','180',4.5,1000000000,'com.kiloo.subwaysurf',
             'العاب الجري اللانهائي الأكثر تحميلاً في التاريخ تتخطى فيه القطارات والعقبات.',
             'صب واي سيرفرز هي لعبة الركض اللانهائي الكلاسيكية التي أعادت تعريف الألعاب المحمولة. تركض بسرعة جنونية عبر مسارات سكة الحديد متفادياً القطارات والحواجز وجامعاً العملات والمعززات. تنتقل اللعبة بين مدن العالم كل موسم مع شخصيات ولوحات تزلج حصرية. تتحدى أصدقاءك وتحاول تحطيم أعلى سجل في لوحة المتصدرين.',''],
            ['among-us','أمونج أس','games','InnerSloth LLC','2024.9.4','200',3.9,100000000,'com.innersloth.spacemafia',
             'لعبة خداع وذكاء اجتماعي تكشف المتسلل بين أفراد طاقم المركبة الفضائية.',
             'أمونج أس هي لعبة استراتيجية اجتماعية مثيرة يلعبها 4 إلى 15 شخصاً. معظم اللاعبين أعضاء طاقم فضائي يؤدون مهام الصيانة، بينما يتظاهر متسلل أو أكثر بالانتماء للطاقم ويحاولون تخريب المهمة. يتناقش اللاعبون ويصوتون لطرد المشتبه بهم في كل جولة. تنوع الخرائط والأدوار الجديدة يجعلها تجربة مختلفة في كل مرة.',''],
            ['candy-crush-saga','كاندي كراش ساغا','games','King','1.280.0.2','120',4.6,1000000000,'com.king.candycrushsaga',
             'لعبة حلوى إدمانية تطابق فيها الألوان وتحل الألغاز عبر آلاف المراحل.',
             'كاندي كراش ساغا هي لعبة ألغاز ممتعة تطابق فيها ثلاث حلويات أو أكثر من نفس اللون لتفجيرها. تتميز بمئات المراحل المتنوعة التي تزداد تعقيداً تدريجياً مع أهداف وتحديات مختلفة. تتوفر مكافآت يومية وبونصات خاصة ودوريات أسبوعية للتنافس مع الأصدقاء. تضيف King مراحل ومناطق جديدة باستمرار للحفاظ على الإثارة.',''],
            ['roblox','روبلوكس','games','Roblox Corporation','2.625.660','180',4.2,500000000,'com.roblox.client',
             'منصة ألعاب عملاقة يصنع فيها المستخدمون ويشاركون ملايين الألعاب الثلاثية الأبعاد.',
             'روبلوكس هو عالم افتراضي ضخم يجمع مئات الملايين من اللاعبين حول العالم في ألعاب من إبداع المجتمع نفسه. تتنوع الألعاب المتاحة من مغامرات الرعب إلى سباقات السيارات ومحاكيات الحياة الواقعية والألعاب الدراماتيكية. يمكنك بناء ألعابك الخاصة باستخدام Roblox Studio وكسب عملة روبوكس. يوفر بيئة آمنة للأطفال والشباب مع أدوات رقابية للوالدين.','new'],
            ['pokemon-go','بوكيمون جو','games','Niantic Inc.','0.299.1','250',3.9,100000000,'com.nianticlabs.pokemongo',
             'لعبة واقع معزز أيقونية تجعلك تصطاد البوكيمون في الشوارع والحدائق الحقيقية.',
             'بوكيمون جو ثورت مفهوم الألعاب المحمولة بدمج عالم البوكيمون الخيالي بالعالم الحقيقي عبر الواقع المعزز. استكشف محيطك واصطد بوكيمون نادرة تظهر في مواقع جغرافية متنوعة ومرتبطة بالطقس والوقت. تنافس في صالات البيجيم مع لاعبين آخرين وشارك في أحداث المداهمة لاصطياد أسطوريات قوية. تحديثات منتظمة تضيف أجيالاً جديدة وأحداثاً موسمية.',''],
            ['netflix','نتفليكس','tools','Netflix Inc.','8.100.0','100',3.9,1000000000,'com.netflix.mediaclient',
             'خدمة بث الأفلام والمسلسلات الأولى عالمياً بمحتوى أصيل حصري لا ينضب.',
             'نتفليكس تقدم مكتبة ضخمة من الأفلام والمسلسلات والوثائقيات من كل أنحاء العالم بما فيها محتوى عربي أصيل. تتيح تحميل المحتوى المفضل للمشاهدة دون إنترنت وضبط جودة العرض حسب سرعة الاتصال. يمكنك إنشاء ملفات شخصية منفصلة لكل فرد في العائلة مع توصيات مخصصة. يوفر خاصية المشاهدة المشتركة مع الأصدقاء عن بُعد.',''],
            ['spotify','سبوتيفاي','tools','Spotify AB','8.9.58','65',4.5,1000000000,'com.spotify.music',
             'خدمة بث الموسيقى الأولى في العالم بمكتبة تضم أكثر من 100 مليون مقطوعة.',
             'سبوتيفاي يوفر وصولاً فورياً إلى مكتبة ضخمة من الأغاني والبودكاست والكتب الصوتية من كل أنحاء العالم. تقترح الخوارزمية الذكية قوائم تشغيل مخصصة يومياً بناءً على أذواقك الموسيقية. يمكنك تحميل الأغاني للاستماع دون إنترنت مع الاشتراك المدفوع وإنشاء قوائم تشغيل مشتركة مع الأصدقاء. يتكامل مع مكبرات الصوت الذكية وشاشات السيارة وأجهزة الاستريو.',''],
            ['shahid','شاهد','tools','MBC Group','5.3.7','45',4.0,50000000,'com.mbc.shahid',
             'منصة البث العربية الأولى تجمع أشهر المسلسلات والأفلام والبرامج العربية والعالمية.',
             'شاهد هي منصة البث الترفيهي الرائدة في العالم العربي تقدم مكتبة ضخمة من المسلسلات العربية والتركية المدبلجة والأفلام الكوميدية والدرامية. تبث الأحداث الحية والبرامج الرياضية والمباريات العالمية الكبرى. يمكنك متابعة أحدث المسلسلات حلقة بحلقة فور عرضها وتحميلها للمشاهدة في الطائرة أو دون اتصال. يوفر دعماً كاملاً للغة العربية وتجربة مصممة للمستخدم العربي.',''],
        ];
        $ins = $pdo->prepare("INSERT IGNORE INTO apps
            (slug,name,category_id,developer,version,size_mb,rating,downloads,package_name,
             short_description,long_description,download_url,status,badge,download_source,
             seo_title,meta_description)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        foreach ($seed as $a) {
            [$slug,$name,$catSlug,$dev,$ver,$size,$rat,$dl,$pkg,$short,$long,$badge] = $a;
            $catId = $catMap[$catSlug] ?? null;
            $dlUrl = "https://play.google.com/store/apps/details?id={$pkg}";
            $seoTitle = "تحميل {$name} APK للأندرويد — " . SITE_NAME;
            $metaDesc = mb_substr($short, 0, 155);
            try { $ins->execute([$slug,$name,$catId,$dev,$ver,$size,$rat,$dl,$pkg,$short,$long,$dlUrl,'published',$badge,'playstore',$seoTitle,$metaDesc]); }
            catch (Throwable $e) { /* slug duplicate → skip */ }
        }
        // Ping indexing for each seeded app (deferred so schema() returns fast)
        register_shutdown_function(function() use ($pdo) {
            try {
                $apps = $pdo->query("SELECT id, slug FROM apps WHERE status='published' LIMIT 30")->fetchAll();
                foreach ($apps as $app) {
                    $url = rtrim(SITE_URL,'/') . '/' . rawurlencode($app['slug']);
                    ping_search_engines($pdo, $url, (int)$app['id']);
                }
            } catch (Throwable $e) {}
        });
    }

    // Visitor profiles — privacy-safe analytics, interests, geo, IP history
    $pdo->exec("CREATE TABLE IF NOT EXISTS visitor_profiles (
      id INT AUTO_INCREMENT PRIMARY KEY,
      fingerprint VARCHAR(64) NOT NULL,
      first_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      last_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      total_views INT UNSIGNED NOT NULL DEFAULT 0,
      total_downloads INT UNSIGNED NOT NULL DEFAULT 0,
      country VARCHAR(4) NULL,
      city VARCHAR(120) NULL,
      ip_hash VARCHAR(64) NOT NULL DEFAULT '',
      ip_history JSON NULL,
      category_interests JSON NULL,
      app_interests JSON NULL,
      browser_family VARCHAR(80) NULL,
      os_family VARCHAR(80) NULL,
      is_vpn TINYINT(1) NOT NULL DEFAULT 0,
      last_ip_change DATETIME NULL,
      captcha_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
      UNIQUE KEY uq_fp (fingerprint),
      INDEX idx_last_seen (last_seen),
      INDEX idx_country (country)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'visitor_profiles';

    $pdo->exec("CREATE TABLE IF NOT EXISTS captcha_challenges (
      id INT AUTO_INCREMENT PRIMARY KEY,
      token VARCHAR(64) NOT NULL,
      fingerprint VARCHAR(64) NOT NULL DEFAULT '',
      ip VARCHAR(45) NOT NULL DEFAULT '',
      challenge_type ENUM('v2','v3') NOT NULL DEFAULT 'v2',
      reason VARCHAR(60) NOT NULL DEFAULT '',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      solved_at DATETIME NULL,
      status ENUM('pending','solved','expired') NOT NULL DEFAULT 'pending',
      UNIQUE KEY uq_token (token),
      INDEX idx_fp_status (fingerprint, status),
      INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'captcha_challenges';

    return $log;
}
ensure_schema($pdo);

/* ═══════════════════════════════════════════════
   Lightweight page cache — file-based, for every public
   listing/detail page (index/category/top/updates/app).
   The view counter and the comment submission form are
   both deliberately kept OUT of the cached HTML (see
   track-view.php and comment-form.php) so caching app.php
   doesn't make either one stale or session-mismatched.
   Invalidated instantly (not just on a timer) whenever an
   app is added/edited/deleted or a comment is
   approved/deleted, via a version token bumped in
   admin.php.
   ═══════════════════════════════════════════════ */
function cache_version(PDO $pdo): int {
    return (int)get_cfg($pdo, 'cache_version', '1');
}
function ping_search_engines(PDO $pdo, string $url, ?int $appId = null): void {
    try {
        // Google sitemap ping deprecated since 2023 — skip silently
        // Bing sitemap ping deprecated (HTTP 410) — skip, use IndexNow instead

        $key = get_cfg($pdo, 'indexnow_key', '');
        if ($key && $url) {
            $host        = parse_url(SITE_URL, PHP_URL_HOST) ?: '';
            $keyLocation = 'https://' . $host . '/' . $key . '.txt';
            $body = json_encode([
                'host'        => $host,
                'key'         => $key,
                'keyLocation' => $keyLocation,
                'urlList'     => [$url],
            ]);
            $headers = "Content-Type: application/json\r\nContent-Length: " . strlen($body);
            $ictx = stream_context_create(['http' => [
                'method' => 'POST', 'timeout' => 8, 'ignore_errors' => true,
                'header' => $headers, 'content' => $body,
            ]]);
            $resp    = @file_get_contents('https://api.indexnow.org/indexnow', false, $ictx);
            $rh      = $http_response_header ?? [];
            $code    = 0;
            foreach ($rh as $h) {
                if (preg_match('#HTTP/\S+\s+(\d+)#', $h, $m)) { $code = (int)$m[1]; break; }
            }
            $ok     = in_array($code, [200, 202], true);
            $reason = $ok ? null : "HTTP {$code}" . ($resp ? ': ' . mb_substr(trim($resp), 0, 200) : '');
            try {
                $pdo->prepare("INSERT INTO indexnow_log (url, engine, status, http_code, reason) VALUES (?,?,?,?,?)")
                    ->execute([$url, 'indexnow', $ok ? 'success' : 'failed', $code ?: null, $reason]);
            } catch (Throwable $le) {}

            // Also ping Bing via IndexNow API
            $bingCtx = stream_context_create(['http' => [
                'method' => 'POST', 'timeout' => 8, 'ignore_errors' => true,
                'header' => $headers, 'content' => $body,
            ]]);
            @file_get_contents('https://www.bing.com/indexnow', false, $bingCtx);
        } elseif (!$key) {
            try {
                $pdo->prepare("INSERT INTO indexnow_log (url, engine, status, reason) VALUES (?,?,?,?)")
                    ->execute([$url, 'indexnow', 'skipped', 'مفتاح IndexNow غير مضبوط في الإعدادات']);
            } catch (Throwable $le) {}
        }

        if ($appId) {
            $pdo->prepare("UPDATE apps SET last_indexed_at=NOW(), index_status='indexed' WHERE id=?")
                ->execute([$appId]);
        }
    } catch (Throwable $e) {
        if ($appId) {
            try { $pdo->prepare("UPDATE apps SET index_status='error' WHERE id=?")->execute([$appId]); }
            catch (Throwable $e2) {}
        }
        try {
            $pdo->prepare("INSERT INTO indexnow_log (url, engine, status, reason) VALUES (?,?,?,?)")
                ->execute([$url, 'indexnow', 'failed', mb_substr($e->getMessage(), 0, 300)]);
        } catch (Throwable $le) {}
    }
}

/* ─── Security: deep file scanner ─── */
function scan_file_for_threats(string $filepath, string $context = 'general'): array {
    $threats = [];
    $raw = @file_get_contents($filepath, false, null, 0, 3 * 1024 * 1024);
    if ($raw === false) return ['unreadable file'];

    // Double extension in filename (file.php.jpg) — applies to all contexts
    $fname = basename($filepath);
    if (preg_match('/\.php\d*\.(jpg|jpeg|png|gif|webp|zip|rar)$/i', $fname))
        $threats[] = 'Double extension filename';

    // Binary image files (JPEG/PNG/WebP) legitimately contain null bytes and
    // random byte sequences that coincidentally match text patterns like
    // "exec(". Applying text-oriented checks to binary data produces
    // guaranteed false positives and blocks every icon upload. Only run the
    // text-pattern checks on non-image contexts.
    if ($context === 'image') {
        // The only meaningful check for images: an embedded PHP opening tag
        // that could turn the image into a PHP include vector.
        if (preg_match('/<\?php|\<\?=/i', $raw))
            $threats[] = 'PHP code embedded in image';
        return $threats;
    }

    // PHP opening tag in non-PHP uploads
    if ($context !== 'php' && preg_match('/<\?php|\<\?=/i', $raw)) {
        $threats[] = 'PHP code in uploaded file';
    }
    // Shell execution functions (text files only — binary data has false positives)
    foreach (['system(','exec(','passthru(','shell_exec(','popen(','proc_open(','pcntl_exec('] as $fn) {
        if (stripos($raw, $fn) !== false) $threats[] = "Dangerous function: $fn";
    }
    // Obfuscated eval
    if (preg_match('/eval\s*\(\s*(\$|\bbase64_decode|\bgzinflate|\bstr_rot13)/i', $raw))
        $threats[] = 'Obfuscated eval() pattern';
    // Large base64 blob (obfuscation)
    if (preg_match('/base64_decode\s*\(["\'][A-Za-z0-9+\/]{200,}/', $raw))
        $threats[] = 'Suspicious large base64 blob';
    // Superglobal → dangerous function
    if (preg_match('/\$_(GET|POST|REQUEST|COOKIE)\s*\[.{0,60}\]\s*[,\)]/s', $raw) &&
        preg_match('/(system|exec|passthru|shell_exec|eval|include|require)\s*\(/i', $raw))
        $threats[] = 'Superglobal → dangerous function pattern (webshell)';
    // Null bytes (suspicious in text/script files, but normal in binary — skipped for image above)
    if ($context !== 'apk' && str_contains($raw, "\0")) $threats[] = 'Null byte in content';
    // Known webshell fingerprints
    foreach (['c99shell','r57shell','FilesMan','b374k','WSO Shell','@eval(','preg_replace.*\/e','assert\($_'] as $sig) {
        if (preg_match('/' . preg_quote($sig, '/') . '/i', $raw)) $threats[] = "Webshell signature: $sig";
    }

    return $threats;
}

/* ═══════════════════════════════════════════════════════════════════
   WAF — PHP-level Web Application Firewall.
   Runs on every public page load. Blocks obvious SQLi, XSS, path
   traversal, and null-byte injection before any other PHP runs.
   Admin IP is always exempt. Toggle via evil_waf_enabled setting.
   ═══════════════════════════════════════════════════════════════════ */
function waf_check(PDO $pdo): void {
    if (evil_is_admin_ip()) return;
    if (get_cfg($pdo, 'evil_waf_enabled', '1') !== '1') return;

    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '';

    // Collect all input values to check
    $inputs = array_merge(
        array_values($_GET  ?? []),
        array_values($_POST ?? []),
        array_values($_COOKIE ?? []),
        [$uri, $_SERVER['HTTP_USER_AGENT'] ?? '', $_SERVER['HTTP_REFERER'] ?? '']
    );
    $rawInput = implode(' ', array_map('strval', $inputs));

    // Null byte injection
    if (str_contains($rawInput, "\0")) {
        waf_block($pdo, $ip, 'null_byte', 'Null byte injection attempt');
    }

    // Path traversal
    if (preg_match('#(\.\./|\.\.\\\\|%2e%2e%2f|%2e%2e/)#i', $rawInput)) {
        waf_block($pdo, $ip, 'path_traversal', 'Path traversal attempt: ' . substr($rawInput, 0, 200));
    }

    // SQL injection patterns (common keywords/operators that indicate SQLi)
    $sqliPatterns = [
        '/\bunion\b.*\bselect\b/i',
        '/\bselect\b.*\bfrom\b/i',
        '/\bdrop\b.*\btable\b/i',
        "/['\"].*\bor\b.*['\"]?[=<>]/i",
        '/--\s*$|;\s*--/m',
        '/\bexec\s*\(/i',
        '/\bxp_cmdshell\b/i',
        '/\binformation_schema\b/i',
        '/\bload_file\s*\(/i',
        '/\boutfile\b/i',
    ];
    foreach ($sqliPatterns as $pat) {
        if (preg_match($pat, $rawInput)) {
            waf_block($pdo, $ip, 'sqli', "SQLi pattern detected: $pat — " . substr($rawInput, 0, 200));
        }
    }

    // XSS patterns
    $xssPatterns = [
        '/<script[^>]*>/i',
        '/javascript\s*:/i',
        '/on(?:load|error|click|mouse\w+|focus|blur|key\w+|submit|change|drag\w*|scroll)\s*=/i',
        '/\beval\s*\(/i',
        '/expression\s*\(/i',
        '/vbscript\s*:/i',
    ];
    foreach ($xssPatterns as $pat) {
        if (preg_match($pat, $rawInput)) {
            waf_block($pdo, $ip, 'xss', "XSS pattern detected — " . substr($rawInput, 0, 200));
        }
    }

    // PHP/code injection via request params
    if (preg_match('/<\?php|<\?=/i', $rawInput)) {
        waf_block($pdo, $ip, 'code_injection', 'PHP tag in request');
    }

    // Shell command injection
    if (preg_match('/[;|&`]\s*(ls|cat|wget|curl|chmod|rm|id|whoami|pwd|nc|bash|sh)\b/i', $rawInput)) {
        waf_block($pdo, $ip, 'cmd_injection', 'Command injection attempt');
    }
}

function waf_block(PDO $pdo, string $ip, string $type, string $detail): never {
    log_security_event($pdo, 'waf_block_' . $type, 'critical', $detail . " from $ip");
    // Auto-ban repeat offenders (3rd WAF block = temp ban)
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM security_log WHERE ip=? AND event_type LIKE 'waf_block_%' AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
        $stmt->execute([$ip]);
        if ((int)$stmt->fetchColumn() >= 2) {
            evil_ban_ip($pdo, $ip, 'waf_repeat_offender');
        }
    } catch (Throwable $e) {}
    http_response_code(403);
    die('<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>مرفوض</title>
    <style>body{margin:0;font-family:Tahoma,sans-serif;background:#0f172a;color:#f1f5f9;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center}
    .b{max-width:380px;padding:32px;border:1px solid rgba(239,68,68,.3);border-radius:16px}</style></head>
    <body><div class="b"><h1 style="color:#ef4444;font-size:20px">🛡️ طلب مرفوض</h1>
    <p style="color:#94a3b8;font-size:14px">تم رصد نمط مشبوه في طلبك.</p></div></body></html>');
}

function log_security_event(PDO $pdo, string $type, string $severity, string $detail, string $filename = ''): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $pdo->prepare("INSERT INTO security_log (event_type,severity,ip,detail,filename) VALUES (?,?,?,?,?)")
            ->execute([$type, $severity, $ip, mb_substr($detail, 0, 2000), mb_substr($filename, 0, 300)]);
    } catch (Throwable $e) { /* non-critical */ }
}

/* ═══════════════════════════════════════════════════════════════
   EVIL — Security System
   All checks skip EVIL_ADMIN_IP entirely. Every protection type
   can be toggled independently from admin Settings.
   ═══════════════════════════════════════════════════════════════ */

/* ═══════════════════════════════════════════════════════════════
   Multilingual — auto-translate an app into target language via AI
   ═══════════════════════════════════════════════════════════════ */
function translate_app(PDO $pdo, array $app, string $targetLang): bool {
    $langNames = [
        'en'=>'English','ru'=>'Russian','fr'=>'French','de'=>'German','es'=>'Spanish',
        'tr'=>'Turkish','id'=>'Indonesian','pt'=>'Portuguese','ur'=>'Urdu','hi'=>'Hindi',
        'zh'=>'Chinese (Simplified)','ja'=>'Japanese','ko'=>'Korean','it'=>'Italian',
        'nl'=>'Dutch','fa'=>'Persian','bn'=>'Bengali','th'=>'Thai','vi'=>'Vietnamese',
    ];
    $langName = $langNames[$targetLang] ?? $targetLang;

    $catName = '';
    try {
        if (!empty($app['category_id'])) {
            $catRow = $pdo->prepare("SELECT name FROM categories WHERE id=? LIMIT 1");
            $catRow->execute([$app['category_id']]);
            $catName = (string)($catRow->fetchColumn() ?: '');
        }
    } catch (Throwable $e) {}

    $prompt = "You are a professional app localization specialist and marketing copywriter for the {$langName} market.
Your task is NOT simple word-for-word translation — you must create a completely SEPARATE, market-optimized page
that feels native to {$langName} speakers and maximizes organic search traffic and downloads in that market.

App category: {$catName}
Original Arabic app name: " . json_encode($app['name'], JSON_UNESCAPED_UNICODE) . "
Original short description: " . json_encode($app['short_description'] ?? '', JSON_UNESCAPED_UNICODE) . "
Original long description: " . json_encode(mb_substr($app['long_description'] ?? '', 0, 2500), JSON_UNESCAPED_UNICODE) . "
Original features: " . ($app['features'] ?? '[]') . "
Original pros: " . ($app['pros'] ?? '[]') . "
Original cons: " . ($app['cons'] ?? '[]') . "

REQUIREMENTS:
1. \"name\": Adapt the app name to sound catchy and natural in {$langName} — keep it recognizable but optimize for local SEO appeal
2. \"short_description\": Write a compelling 2-sentence marketing hook targeting {$langName} speakers (not translation, fresh copy)
3. \"long_description\": Write a COMPLETE, ORIGINAL marketing description of at least 800 words in {$langName} — cover features, benefits, use cases, comparison with alternatives, and a strong call-to-action. Use natural {$langName} vocabulary and address the local audience directly.
4. \"seo_title\": Write an SEO title (max 65 chars) using actual {$langName} search terms people use to find this app — format: [App Name] - [Primary Keyword] | Free Download
5. \"meta_description\": Write a compelling meta description (max 155 chars) in {$langName} with a call-to-action that drives clicks from search results
6. \"keywords\": List 15-20 actual {$langName}-language search keywords people use to find this type of app, comma-separated
7. \"whats_new\": Translate/adapt the whats_new section: " . json_encode($app['whats_new'] ?? '', JSON_UNESCAPED_UNICODE) . "
8. \"features\": Rewrite as {$langName} marketing bullet points (array of strings)
9. \"pros\": Rewrite as {$langName} benefits from local user perspective (array of strings)
10. \"cons\": Translate honestly (array of strings)
11. \"install_steps\": Translate clearly (array of strings): " . ($app['install_steps'] ?? '[]') . "

Return ONLY valid JSON — no commentary, no markdown:
{\"name\":\"\",\"short_description\":\"\",\"long_description\":\"\",\"seo_title\":\"\",\"meta_description\":\"\",\"keywords\":\"\",\"whats_new\":\"\",\"features\":[],\"pros\":[],\"cons\":[],\"install_steps\":[]}";

    $raw = ai_text($pdo, $prompt, 4000, 90);
    if (!$raw) return false;
    $data = ai_extract_json($raw);
    if (!$data || !isset($data['name'])) return false;

    // Build slug for translated version
    $baseSlug = $app['slug'] . '-' . $targetLang;
    $slug = $baseSlug;
    $n = 1;
    while ($pdo->prepare("SELECT id FROM apps WHERE slug=? AND id!=?")->execute([$slug, $app['id'] ?? 0])
           && $pdo->query("SELECT COUNT(*) FROM apps WHERE slug='$slug'")->fetchColumn()) {
        $slug = $baseSlug . '-' . $n++;
    }

    // Check if translation already exists
    $existing = $pdo->prepare("SELECT id FROM apps WHERE parent_id=? AND lang_code=?");
    $existing->execute([$app['id'], $targetLang]);
    $existId = $existing->fetchColumn();

    $d = [
        'parent_id'         => (int)$app['id'],
        'lang_code'         => $targetLang,
        'name'              => trim($data['name'] ?? $app['name']),
        'slug'              => $slug,
        'seo_title'         => trim($data['seo_title'] ?? ''),
        'meta_description'  => trim($data['meta_description'] ?? ''),
        'keywords'          => trim($data['keywords'] ?? ''),
        'short_description' => trim($data['short_description'] ?? ''),
        'long_description'  => trim($data['long_description'] ?? ''),
        'whats_new'         => trim($data['whats_new'] ?? ''),
        'features'          => json_encode($data['features'] ?? [], JSON_UNESCAPED_UNICODE),
        'pros'              => json_encode($data['pros'] ?? [], JSON_UNESCAPED_UNICODE),
        'cons'              => json_encode($data['cons'] ?? [], JSON_UNESCAPED_UNICODE),
        'install_steps'     => json_encode($data['install_steps'] ?? [], JSON_UNESCAPED_UNICODE),
        'developer'         => $app['developer'] ?? '',
        'version'           => $app['version'] ?? '',
        'android_version'   => $app['android_version'] ?? '',
        'size_mb'           => $app['size_mb'] ?? '',
        'license'           => $app['license'] ?? 'Free',
        'package_name'      => $app['package_name'] ?? '',
        'category_id'       => $app['category_id'] ?? null,
        'icon_path'         => $app['icon_path'] ?? '',
        'screenshots'       => $app['screenshots'] ?? '[]',
        'download_url'      => $app['download_url'] ?? '',
        'playstore_url'     => $app['playstore_url'] ?? '',
        'rating'            => (float)($app['rating'] ?? 4.5),
        'status'            => 'published',
        'faq'               => $app['faq'] ?? '[]',
    ];

    try {
        if ($existId) {
            $sets = implode(',', array_map(fn($k) => "$k=:$k", array_keys($d)));
            $d['id'] = $existId;
            $pdo->prepare("UPDATE apps SET $sets WHERE id=:id")->execute($d);
        } else {
            $cols = implode(',', array_keys($d));
            $vals = implode(',', array_map(fn($k) => ":$k", array_keys($d)));
            $pdo->prepare("INSERT INTO apps ($cols) VALUES ($vals)")->execute($d);
        }
        return true;
    } catch (Throwable $e) { return false; }
}

function evil_is_admin_ip(): bool {
    return (($_SERVER['REMOTE_ADDR'] ?? '') === EVIL_ADMIN_IP);
}

function evil_is_enabled(PDO $pdo, string $feature = 'master'): bool {
    if (evil_is_admin_ip()) return false; // exempt
    $key = $feature === 'master' ? 'evil_enabled' : "evil_{$feature}_enabled";
    return get_cfg($pdo, $key, '1') === '1' && get_cfg($pdo, 'evil_enabled', '1') === '1';
}

// Escalating lockout duration (seconds) for admin login brute force.
// Index = number of TOTAL failed attempts (1-based).
function evil_login_lockout_secs(int $attempts): int {
    $schedule = [0, 0, 30, 60, 120, 180, 240, 360, 720, 1800, 3600, 7200, 14400, 28800, 57600, 86400, 172800];
    $idx = min($attempts, count($schedule) - 1);
    return $schedule[$idx];
}

// Returns seconds remaining in current lockout (0 = not locked).
function evil_login_lockout_remaining(PDO $pdo, string $ip): int {
    if (evil_is_admin_ip()) return 0;
    try {
        $row = $pdo->prepare("SELECT locked_until FROM evil_login_attempts WHERE ip=? LIMIT 1");
        $row->execute([$ip]);
        $r = $row->fetch(PDO::FETCH_ASSOC);
        if (!$r || !$r['locked_until']) return 0;
        $remaining = strtotime($r['locked_until']) - time();
        return $remaining > 0 ? $remaining : 0;
    } catch (Throwable $e) { return 0; }
}

// Record a failed login attempt. Returns seconds to wait (0 if no lockout yet).
function evil_record_login_fail(PDO $pdo, string $ip): int {
    if (evil_is_admin_ip()) return 0;
    try {
        $pdo->prepare("INSERT INTO evil_login_attempts (ip, attempts, first_attempt_at, last_attempt_at)
            VALUES (?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE attempts=attempts+1, last_attempt_at=NOW()")->execute([$ip]);
        $row = $pdo->prepare("SELECT attempts FROM evil_login_attempts WHERE ip=? LIMIT 1");
        $row->execute([$ip]);
        $attempts = (int)($row->fetchColumn() ?: 1);
        $lockSecs = evil_login_lockout_secs($attempts);
        if ($lockSecs > 0) {
            $until = date('Y-m-d H:i:s', time() + $lockSecs);
            $pdo->prepare("UPDATE evil_login_attempts SET locked_until=? WHERE ip=?")->execute([$until, $ip]);
            // After many failures, also ban the IP temporarily
            if ($attempts >= 6) {
                evil_ban_ip($pdo, $ip, 'brute_force_login', false);
            }
        }
        log_security_event($pdo, 'login_fail', $attempts >= 3 ? 'critical' : 'warning',
            "Failed login attempt #$attempts from $ip");
        return $lockSecs;
    } catch (Throwable $e) { return 0; }
}

// Clear login attempts on successful login.
function evil_clear_login_fail(PDO $pdo, string $ip): void {
    try {
        $pdo->prepare("DELETE FROM evil_login_attempts WHERE ip=?")->execute([$ip]);
    } catch (Throwable $e) {}
}

// IP banning is disabled — suspicious IPs are shown a CAPTCHA challenge instead of being blocked.
// Log the event only; never write to evil_banned_ips so no visitor is permanently locked out.
function evil_ban_ip(PDO $pdo, string $ip, string $reason = '', bool $permanent = false): void {
    if (evil_is_admin_ip()) return;
    log_security_event($pdo, 'ip_flagged', 'warning',
        "IP $ip flagged (no ban applied — CAPTCHA-challenge mode) reason: $reason");
}

// Unban an IP (admin action).
function evil_unban_ip(PDO $pdo, string $ip): void {
    try { $pdo->prepare("DELETE FROM evil_banned_ips WHERE ip=?")->execute([$ip]); } catch (Throwable $e) {}
}

// Check if current IP is banned. Shows CAPTCHA challenge instead of hard 403.
// If session marker 'evil_captcha_ok' is set (CAPTCHA solved), allows through even if IP is banned.
function evil_check_ban(PDO $pdo): void {
    if (evil_is_admin_ip()) return;
    if (!evil_is_enabled($pdo, 'ban')) return;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!$ip) return;

    // Handle CAPTCHA verification POST from the ban-challenge page
    if (!empty($_POST['evil_captcha_verify'])) {
        $solved = false;
        $type   = $_POST['evil_captcha_type'] ?? '';
        $token  = $_POST['evil_captcha_token'] ?? '';
        if ($type === 'turnstile' && $token) {
            $solved = captcha_verify_turnstile($pdo, $token);
        } elseif ($type === 'v2' && $token) {
            $solved = captcha_verify_v2($pdo, $token);
        } elseif ($type === 'v3' && $token) {
            $solved = captcha_verify_v3($pdo, $token);
        }
        if ($solved) {
            $_SESSION['evil_captcha_ok'] = time();
            // Clear the ban for temporary bans (not permanent) as a reward
            try {
                $pdo->prepare("DELETE FROM evil_banned_ips WHERE ip=? AND banned_until IS NOT NULL")
                    ->execute([$ip]);
            } catch (Throwable $e) {}
            return; // Allow through
        }
        // CAPTCHA failed — fall through and show challenge again
    }

    // If already passed CAPTCHA in this session (within 2h), allow through
    if (!empty($_SESSION['evil_captcha_ok']) && (time() - (int)$_SESSION['evil_captcha_ok']) < 7200) {
        return;
    }

    try {
        $row = $pdo->prepare("SELECT banned_until, reason FROM evil_banned_ips WHERE ip=? LIMIT 1");
        $row->execute([$ip]);
        $r = $row->fetch(PDO::FETCH_ASSOC);
        if (!$r) return;
        $until = $r['banned_until'];
        if ($until !== null && strtotime($until) < time()) {
            $pdo->prepare("DELETE FROM evil_banned_ips WHERE ip=? AND banned_until IS NOT NULL AND banned_until < NOW()")
                ->execute([$ip]);
            return;
        }

        // Determine CAPTCHA type available
        $captchaType = 'none';
        if (trim(get_cfg($pdo, 'turnstile_site_key'))) $captchaType = 'turnstile';
        elseif (trim(get_cfg($pdo, 'recaptcha_v2_site_key'))) $captchaType = 'v2';
        elseif (trim(get_cfg($pdo, 'recaptcha_v3_site_key'))) $captchaType = 'v3';

        $isPermanent = ($until === null);
        $untilText = $until ? date('Y/m/d H:i', strtotime($until)) : '';
        $currentUrl = h(($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http') . '://' . h($_SERVER['HTTP_HOST'] ?? '') . h($_SERVER['REQUEST_URI'] ?? '/');

        // If no CAPTCHA configured, fall back to informational page
        if ($captchaType === 'none' || $isPermanent) {
            http_response_code(403);
            $msg = $isPermanent ? 'تم حظر عنوان IP الخاص بك بشكل دائم.' : 'تم حظر عنوان IP الخاص بك مؤقتاً.';
            die('<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">
            <title>محظور</title>
            <style>body{margin:0;font-family:Tahoma,Arial,sans-serif;background:#0f172a;color:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center}
            .box{max-width:440px;padding:40px 32px;border:1px solid rgba(239,68,68,.3);border-radius:16px;background:rgba(239,68,68,.05)}
            h1{color:#ef4444;margin:0 0 12px;font-size:22px}p{color:#94a3b8;margin:0 0 8px;font-size:14px}
            code{background:#1e293b;color:#38bdf8;padding:4px 10px;border-radius:6px;font-family:monospace;direction:ltr;display:inline-block;margin-top:4px}
            </style></head><body><div class="box">
            <h1>🛡️ محظور</h1><p>' . h($msg) . '</p>
            ' . ($until ? '<p>ينتهي الحظر: <code>' . h($untilText) . '</code></p>' : '') . '
            </div></body></html>');
        }

        // Render CAPTCHA challenge page
        $widgetHtml = captcha_widget_html($pdo, $captchaType, 'ban_bypass');
        $siteKeyJs = '';
        if ($captchaType === 'v3') {
            $siteKeyJs = h(trim(get_cfg($pdo, 'recaptcha_v3_site_key')));
        }

        http_response_code(403);
        die('<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>تحقق من هويتك</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Tahoma,Arial,sans-serif;background:#0f172a;color:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:16px}
.box{max-width:420px;width:100%;background:#1e293b;border:1px solid rgba(148,163,184,.15);border-radius:20px;padding:40px 32px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.5)}
h1{color:#f8fafc;font-size:22px;margin:0 0 8px}
.sub{color:#94a3b8;font-size:14px;margin:0 0 28px;line-height:1.6}
.captcha-wrap{display:flex;justify-content:center;margin:0 0 20px}
.cf-turnstile,.g-recaptcha{margin:0 auto}
.msg{margin-top:12px;color:#f87171;font-size:13px;display:none}
.icon{font-size:48px;margin:0 0 16px;display:block}
</style></head>
<body>
<div class="box">
  <span class="icon">🛡️</span>
  <h1>تحقق من هويتك</h1>
  <p class="sub">تم رصد نشاط غير عادي من عنوان IP الخاص بك.' . ($untilText ? '<br><small style="color:#64748b">الحظر ينتهي: ' . h($untilText) . '</small>' : '') . '<br>أكمل التحقق للمتابعة.</p>
  <form method="post" action="' . $currentUrl . '" id="banForm">
    <input type="hidden" name="evil_captcha_verify" value="1">
    <input type="hidden" name="evil_captcha_type" value="' . h($captchaType) . '">
    <input type="hidden" name="evil_captcha_token" id="banCaptchaToken" value="">
    <div class="captcha-wrap">' . $widgetHtml . '</div>
    <p class="msg" id="banMsg">فشل التحقق، حاول مجدداً.</p>
  </form>
</div>
<script>
function onCaptchaSolvedTurnstile(token){document.getElementById("banCaptchaToken").value=token;document.getElementById("banForm").submit();}
function onCaptchaSolvedV2(token){document.getElementById("banCaptchaToken").value=token;document.getElementById("banForm").submit();}
' . ($captchaType === 'v3' ? '
window.addEventListener("load",function(){
  grecaptcha.ready(function(){
    grecaptcha.execute("' . $siteKeyJs . '",{action:"ban_bypass"}).then(function(token){
      document.getElementById("banCaptchaToken").value=token;
      document.getElementById("banForm").submit();
    });
  });
});' : '') . '
</script>
</body></html>');
    } catch (Throwable $e) {}
}

// Rate-limit non-admin requests by IP — for public pages (not login).
// Returns true if OK to proceed, false if rate-limited.
function evil_rate_check(PDO $pdo, string $action, int $maxPerMinute = 60): bool {
    if (evil_is_admin_ip()) return true;
    if (!evil_is_enabled($pdo, 'ratelimit')) return true;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!$ip) return true;
    try {
        $count = (int)$pdo->prepare("SELECT COUNT(*) FROM security_log
            WHERE ip=? AND event_type=? AND created_at > (NOW() - INTERVAL 1 MINUTE)")
            ->execute([$ip, "rate_$action"]) ? $pdo->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;
        // Simpler: just count from security_log inserted this minute
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM security_log WHERE ip=? AND event_type=? AND created_at > (NOW() - INTERVAL 1 MINUTE)");
        $stmt->execute([$ip, "rate_$action"]);
        $count = (int)$stmt->fetchColumn();
        if ($count >= $maxPerMinute) {
            log_security_event($pdo, "rate_$action", 'warning', "Rate limit hit: $action ($count/min) from $ip");
            return false;
        }
        // Record this request lightly (only if near limit to reduce noise)
        if ($count >= (int)($maxPerMinute * 0.8)) {
            log_security_event($pdo, "rate_$action", 'info', "Rate near limit: $action ($count/min) from $ip");
        }
        return true;
    } catch (Throwable $e) { return true; }
}

/* ═══════════════════════════════════════════════════════════════════
   Visitor Analytics — privacy-safe tracking.
   IPs are never stored raw; only a SHA-256 hash is kept.
   Fingerprints use only server-visible headers (no client JS needed).
   All functions are no-ops when called from the admin IP.
   ═══════════════════════════════════════════════════════════════════ */
function visitor_fingerprint(): string {
    $ua   = $_SERVER['HTTP_USER_AGENT']      ?? '';
    $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    return hash('sha256', $ua . '|' . $lang);
}

function get_visitor_geo(string $ip, PDO $pdo): array {
    $default = ['country' => null, 'city' => null, 'is_vpn' => false];
    if (!$ip || $ip === '127.0.0.1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) return $default;

    // Cache per IP in settings (TTL 24h, key = _geo_<short-hash>)
    $cacheKey = '_geo_' . substr(hash('sha256', $ip), 0, 20);
    try {
        $cached = get_cfg($pdo, $cacheKey, '');
        if ($cached) {
            $c = json_decode($cached, true);
            if (is_array($c) && isset($c['ts']) && (time() - (int)$c['ts']) < 86400) {
                return ['country' => $c['country'] ?? null, 'city' => $c['city'] ?? null, 'is_vpn' => (bool)($c['vpn'] ?? false)];
            }
        }
    } catch (Throwable $e) {}

    $url = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=status,countryCode,city,proxy,hosting&lang=en';
    $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true, 'method' => 'GET']]);
    $res = @file_get_contents($url, false, $ctx);
    if (!$res) return $default;
    $d = json_decode($res, true);
    if (!is_array($d) || ($d['status'] ?? '') !== 'success') return $default;

    $geo = [
        'country' => $d['countryCode'] ?? null,
        'city'    => $d['city'] ?? null,
        'is_vpn'  => (bool)($d['proxy'] ?? false) || (bool)($d['hosting'] ?? false),
    ];
    try { set_cfg($pdo, $cacheKey, json_encode(['country' => $geo['country'], 'city' => $geo['city'], 'vpn' => (int)$geo['is_vpn'], 'ts' => time()])); } catch (Throwable $e) {}
    return $geo;
}

function visitor_ua_info(): array {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $browser = 'Other';
    foreach (['SamsungBrowser' => 'Samsung', 'Edg' => 'Edge', 'OPR' => 'Opera', 'Opera' => 'Opera',
              'Firefox' => 'Firefox', 'Chrome' => 'Chrome', 'Safari' => 'Safari'] as $tok => $name) {
        if (stripos($ua, $tok) !== false) { $browser = $name; break; }
    }
    $os = 'Other';
    foreach (['Android' => 'Android', 'iPhone' => 'iOS', 'iPad' => 'iOS',
              'Windows' => 'Windows', 'Macintosh' => 'macOS', 'Linux' => 'Linux'] as $tok => $name) {
        if (stripos($ua, $tok) !== false) { $os = $name; break; }
    }
    return [$browser, $os];
}

function visitor_track(PDO $pdo, ?int $appId = null, ?int $categoryId = null): void {
    if (evil_is_admin_ip()) return;
    try {
        $fp     = visitor_fingerprint();
        $ip     = client_ip();
        $ipHash = hash('sha256', $ip);

        [$browser, $os] = visitor_ua_info();

        // Category slug for interest tracking
        $catSlug = null;
        if ($categoryId) {
            $r = $pdo->prepare("SELECT slug FROM categories WHERE id=? LIMIT 1");
            $r->execute([$categoryId]);
            $catSlug = $r->fetchColumn() ?: null;
        }

        // Fetch existing profile
        $stmt = $pdo->prepare("SELECT id,ip_hash,ip_history,category_interests,app_interests,is_vpn FROM visitor_profiles WHERE fingerprint=? LIMIT 1");
        $stmt->execute([$fp]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        $ipChanged = $profile && $profile['ip_hash'] !== $ipHash;
        $isVpn = 0; $country = null; $city = null; $histJson = null;

        if ($ipChanged || !$profile) {
            $geo     = get_visitor_geo($ip, $pdo);
            $country = $geo['country'];
            $city    = $geo['city'];
            $isVpn   = (int)$geo['is_vpn'];
            $entry   = ['h' => substr($ipHash, 0, 16), 'c' => $country, 't' => date('Y-m-d H:i')];
            if ($profile) {
                $hist = json_decode($profile['ip_history'] ?? '[]', true) ?: [];
                array_unshift($hist, $entry);
                $histJson = json_encode(array_slice($hist, 0, 30));
            } else {
                $histJson = json_encode([$entry]);
            }
        }

        // Merge interests
        $catI = json_decode($profile['category_interests'] ?? '{}', true) ?: [];
        if ($catSlug) $catI[$catSlug] = ($catI[$catSlug] ?? 0) + 1;
        arsort($catI);

        $appI = json_decode($profile['app_interests'] ?? '[]', true) ?: [];
        if ($appId) {
            $appI = array_values(array_filter($appI, fn($x) => $x !== $appId));
            array_unshift($appI, $appId);
            $appI = array_slice($appI, 0, 50);
        }

        $catJson = json_encode($catI,  JSON_UNESCAPED_UNICODE);
        $appJson = json_encode($appI);

        if ($profile) {
            $params = [':br' => $browser, ':os' => $os, ':ci' => $catJson, ':ai' => $appJson, ':fp' => $fp];
            $sets   = ['last_seen=NOW()', 'total_views=total_views+1', 'browser_family=:br', 'os_family=:os', 'category_interests=:ci', 'app_interests=:ai'];
            if ($ipChanged) {
                $sets[] = 'ip_hash=:ih'; $sets[] = 'ip_history=:ih2';
                $sets[] = 'is_vpn=:vpn'; $sets[] = 'last_ip_change=NOW()';
                $params += [':ih' => $ipHash, ':ih2' => $histJson, ':vpn' => $isVpn];
                if ($country !== null) { $sets[] = 'country=:co'; $params[':co'] = $country; }
                if ($city !== null)    { $sets[] = 'city=:cy';    $params[':cy'] = $city; }
            }
            $pdo->prepare("UPDATE visitor_profiles SET " . implode(',', $sets) . " WHERE fingerprint=:fp")->execute($params);
        } else {
            $pdo->prepare("INSERT INTO visitor_profiles
                (fingerprint,ip_hash,ip_history,country,city,browser_family,os_family,is_vpn,total_views,category_interests,app_interests)
                VALUES (?,?,?,?,?,?,?,?,1,?,?)")
                ->execute([$fp, $ipHash, $histJson, $country, $city, $browser, $os, $isVpn, $catJson, $appJson]);
        }
    } catch (Throwable $e) { /* never break the page */ }
}

function visitor_record_download(PDO $pdo): void {
    if (evil_is_admin_ip()) return;
    try {
        $pdo->prepare("UPDATE visitor_profiles SET total_downloads=total_downloads+1 WHERE fingerprint=?")
            ->execute([visitor_fingerprint()]);
    } catch (Throwable $e) {}
}

/* ═══════════════════════════════════════════════════════════════════
   CAPTCHA challenge system — triggers on: IP change with same device
   fingerprint, rate limit exceeded (>20 req/60s), VPN detected.
   Returns 'none'|'v3'|'v2'.  All ops are no-ops for admin IP.
   ═══════════════════════════════════════════════════════════════════ */
function captcha_should_challenge(PDO $pdo): string {
    if (evil_is_admin_ip()) return 'none';

    $turnstileSite = trim(get_cfg($pdo, 'turnstile_site_key'));
    $v3Site = trim(get_cfg($pdo, 'recaptcha_v3_site_key'));
    $v2Site = trim(get_cfg($pdo, 'recaptcha_v2_site_key'));
    if (!$turnstileSite && !$v3Site && !$v2Site) return 'none'; // not configured

    // Already solved in this session (valid 1 h)?
    if (!empty($_SESSION['captcha_solved_at']) && (time() - (int)$_SESSION['captcha_solved_at']) < 3600) {
        return 'none';
    }

    $triggers = [];

    // Rate limit: >20 requests within 60 s (session rolling window)
    if (!isset($_SESSION['rv_window'])) $_SESSION['rv_window'] = ['t' => time(), 'n' => 0];
    if (time() - (int)$_SESSION['rv_window']['t'] > 60) $_SESSION['rv_window'] = ['t' => time(), 'n' => 0];
    $_SESSION['rv_window']['n'] = (int)$_SESSION['rv_window']['n'] + 1;
    if ($_SESSION['rv_window']['n'] > 20) $triggers[] = 'rate_limit';

    // IP change + VPN from visitor_profiles
    $fp     = visitor_fingerprint();
    $ipHash = hash('sha256', client_ip());
    try {
        $row = $pdo->prepare("SELECT ip_hash, is_vpn FROM visitor_profiles WHERE fingerprint=? LIMIT 1");
        $row->execute([$fp]);
        $p = $row->fetch(PDO::FETCH_ASSOC);
        if ($p) {
            if ($p['ip_hash'] !== $ipHash) $triggers[] = 'ip_change';
            if ($p['is_vpn'])              $triggers[] = 'vpn_suspect';
        }
    } catch (Throwable $e) {}

    if (!$triggers) return 'none';

    // Prefer Turnstile (free, privacy-friendly, no Google dependency)
    if ($turnstileSite) return 'turnstile';

    // High risk (VPN or heavy rate abuse) → v2 checkbox; mild → v3 invisible
    $highRisk = in_array('vpn_suspect', $triggers, true) ||
                (in_array('rate_limit', $triggers, true) && (int)($_SESSION['rv_window']['n'] ?? 0) > 40);
    if ($highRisk && $v2Site) return 'v2';
    if ($v3Site)  return 'v3';
    if ($v2Site)  return 'v2';
    return 'none';
}

function captcha_verify_turnstile(PDO $pdo, string $token): bool {
    $secret = trim(get_cfg($pdo, 'turnstile_secret_key'));
    if (!$secret || !$token) return false;
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 8,
        CURLOPT_POSTFIELDS => http_build_query(['secret' => $secret, 'response' => $token, 'remoteip' => client_ip()])]);
    $res = curl_exec($ch); curl_close($ch);
    $d = json_decode((string)$res, true);
    return (bool)($d['success'] ?? false);
}

function captcha_verify_v3(PDO $pdo, string $token, float $minScore = 0.5): bool {
    $secret = trim(get_cfg($pdo, 'recaptcha_v3_secret'));
    if (!$secret || !$token) return false;
    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 8,
        CURLOPT_POSTFIELDS => http_build_query(['secret' => $secret, 'response' => $token, 'remoteip' => client_ip()])]);
    $res = curl_exec($ch); curl_close($ch);
    $d = json_decode((string)$res, true);
    return ($d['success'] ?? false) && (float)($d['score'] ?? 0) >= $minScore;
}

function captcha_verify_v2(PDO $pdo, string $token): bool {
    $secret = trim(get_cfg($pdo, 'recaptcha_v2_secret'));
    if (!$secret || !$token) return false;
    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 8,
        CURLOPT_POSTFIELDS => http_build_query(['secret' => $secret, 'response' => $token, 'remoteip' => client_ip()])]);
    $res = curl_exec($ch); curl_close($ch);
    $d = json_decode((string)$res, true);
    return (bool)($d['success'] ?? false);
}

function captcha_mark_solved(PDO $pdo): void {
    $_SESSION['captcha_solved_at'] = time();
    $_SESSION['rv_window'] = ['t' => time(), 'n' => 0]; // reset rate counter
    $fp = visitor_fingerprint();
    try {
        $pdo->prepare("UPDATE visitor_profiles SET captcha_count=captcha_count+1 WHERE fingerprint=?")->execute([$fp]);
    } catch (Throwable $e) {}
}

/* Renders CAPTCHA widget HTML + JS to be embedded in any page.
   Call captcha_should_challenge() first; only render when needed. */
function captcha_widget_html(PDO $pdo, string $type, string $action = 'download'): string {
    if ($type === 'turnstile') {
        $site = h(trim(get_cfg($pdo, 'turnstile_site_key')));
        return '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>'
            . '<div class="cf-turnstile" data-sitekey="' . $site . '" data-callback="onCaptchaSolvedTurnstile" data-theme="light" data-size="normal"></div>';
    }
    $v3Site = h(trim(get_cfg($pdo, 'recaptcha_v3_site_key')));
    $v2Site = h(trim(get_cfg($pdo, 'recaptcha_v2_site_key')));
    if ($type === 'v3') {
        return '<script src="https://www.google.com/recaptcha/api.js?render=' . $v3Site . '" async defer></script>'
            . '<input type="hidden" name="g-recaptcha-response" id="captcha-token-v3">'
            . '<script>window._captchaAction="' . h($action) . '";window._captchaSiteV3="' . $v3Site . '";</script>';
    }
    return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>'
        . '<div class="g-recaptcha" data-sitekey="' . $v2Site . '" data-callback="onCaptchaSolvedV2"></div>';
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

    return ai_filter_text_models(array_values(array_unique(array_filter($models))));
}

// Low level call to OpenRouter chat completions. Returns ['ok'=>bool,'content'=>?string,'error'=>?string,'http'=>int]
// timeout is intentionally short (not the 55s this used to be): a failed/
// rate-limited/unavailable free model almost always fails fast, and this
// value is multiplied by every (key × model) pair openrouter_call_rotating()
// tries — a long per-attempt timeout is what made a single content-generation
// request able to block a PHP worker for many minutes.
function openrouter_call(string $key, string $model, string $prompt, int $timeout = 20, int $maxTokens = 0): array {
    if (!$key) return ['ok' => false, 'content' => null, 'error' => 'لا يوجد مفتاح API', 'http' => 0];
    $body = ['model' => $model, 'messages' => [['role' => 'user', 'content' => $prompt]], 'temperature' => 0.7];
    if ($maxTokens > 0) $body['max_tokens'] = $maxTokens;
    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $key, 'Content-Type: application/json',
            'HTTP-Referer: ' . SITE_URL, 'X-Title: yassota',
        ],
        CURLOPT_POSTFIELDS => json_encode($body),
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

// High level: tries key × model combinations until one succeeds. Returns full trace for diagnostics.
// Capped at MAX_ATTEMPTS total combinations — with several keys and a live-
// fetched free-model list this cross product can reach 100+ pairs, and at
// 20s/attempt that's still 30+ minutes worst case if every one fails or
// times out. Capping bounds a single request to at most a couple of minutes
// while still trying enough combinations to almost always succeed.
function openrouter_call_rotating(array $keys, array $models, string $prompt, int $timeout = 20, int $maxTokens = 0): array {
    $trace = [];
    $attempts = 0;
    $maxAttempts = 12;
    foreach ($keys as $key) {
        foreach ($models as $model) {
            if ($attempts >= $maxAttempts) break 2;
            $attempts++;
            $r = openrouter_call($key, $model, $prompt, $timeout, $maxTokens);
            $trace[] = ['model' => $model, 'key_tail' => substr($key, -4), 'ok' => $r['ok'], 'error' => $r['error'], 'http' => $r['http']];
            if ($r['ok']) {
                return ['ok' => true, 'content' => $r['content'], 'model' => $model, 'trace' => $trace];
            }
        }
    }
    return ['ok' => false, 'content' => null, 'model' => null, 'trace' => $trace];
}

// Turns a failed openrouter_call_rotating() trace into one concrete Arabic
// sentence explaining WHY, instead of a generic "connection failed" —
// the HTTP codes across attempts almost always point at one root cause
// (bad key, no credits, rate limit, or the host's outbound network itself
// being blocked) even when the admin never opens the raw trace.
function openrouter_diagnose_trace(array $trace): string {
    if (!$trace) return 'لم تتم أي محاولة اتصال.';
    $codes = array_column($trace, 'http');
    $count = count($trace);
    $countOf = fn($c) => count(array_filter($codes, fn($x) => $x === $c));

    if ($countOf(0) === $count) {
        return "تعذّر الوصول إلى openrouter.ai من الخادم نفسه ({$count} محاولة، فشل اتصال في كل مرة) — الأغلب أن استضافتك تحظر الاتصال الخارجي (outbound HTTPS) من كود PHP، أو أن اسم النطاق openrouter.ai محجوب. تواصل مع شركة الاستضافة للتأكد من السماح باتصالات cURL الخارجية.";
    }
    if ($countOf(401) === $count) {
        return "كل المفاتيح المضافة مرفوضة (HTTP 401 غير مصرح) — المفتاح غير صحيح أو منتهي. احصل على مفتاح جديد من openrouter.ai/keys وأضفه من الإعدادات.";
    }
    if ($countOf(402) === $count) {
        return "الرصيد غير كافٍ لكل المفاتيح (HTTP 402) — الموديلات المجانية قد تتطلب حداً أدنى من الرصيد في حساب OpenRouter حتى لو لم تُستهلك. أضف رصيداً بسيطاً لحسابك على openrouter.ai أو جرّب مفتاح حساب آخر.";
    }
    if ($countOf(429) === $count) {
        return "تم تجاوز الحد المسموح من الطلبات (HTTP 429) على كل المحاولات — الموديلات المجانية لها حد استخدام يومي/دقيق منخفض. انتظر بضع دقائق ثم أعد المحاولة، أو أضف مفتاح OpenRouter إضافي من حساب آخر لزيادة عدد المحاولات المتاحة.";
    }
    if ($countOf(404) === $count) {
        return "كل الموديلات المستخدمة غير موجودة على OpenRouter (HTTP 404) — على الأغلب أسماء الموديلات المحفوظة في الإعدادات لم تعد متوفرة مجاناً. افتح صفحة الإعدادات واختر موديلاً من القائمة المحدّثة، أو فعّل \"التدوير التلقائي\".";
    }
    // Mixed causes — summarize the most common one plus the last error text.
    $tally = array_count_values($codes);
    arsort($tally);
    $topCode = array_key_first($tally);
    $lastErr = end($trace)['error'] ?? '';
    $label = match (true) {
        $topCode === 0   => 'فشل اتصال بالخادم',
        $topCode === 401 => 'مفتاح مرفوض (401)',
        $topCode === 402 => 'رصيد غير كافٍ (402)',
        $topCode === 429 => 'تجاوز الحد المسموح (429)',
        default          => "رمز استجابة {$topCode}",
    };
    return "فشلت {$count} محاولة بأسباب متفاوتة — السبب الأكثر تكراراً: {$label} ({$tally[$topCode]} من {$count}). آخر خطأ: \"{$lastErr}\". راجع صفحة \"اختبار الاتصال\" لتفاصيل كل محاولة.";
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
    if ($title) $title = trim(preg_replace('/\s*[-–]\s*(Apps on Google Play|تطبيقات على Google Play).*$/i', '', $title));

    // Extract developer name from HTML
    $developer = null;
    if (preg_match('#/store/apps/developer\?id=[^"\']*["\'][^>]*>([^<]{2,60})</a>#u', $html, $m))
        $developer = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
    if (!$developer && preg_match('#"author"\s*:\s*\{\s*"@type"\s*:\s*"[^"]*"\s*,\s*"name"\s*:\s*"([^"]+)"#', $html, $m))
        $developer = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');

    // Extract version from JSON-LD or inline data
    $version = null;
    if (preg_match('#"softwareVersion"\s*:\s*"([^"]{1,30})"#', $html, $m)) $version = $m[1];
    if (!$version && preg_match('#"currentVersionName"\s*:\s*"([^"]{1,30})"#', $html, $m)) $version = $m[1];

    // Extract Android requirement
    $androidReq = null;
    if (preg_match('#"operatingSystem"\s*:\s*"([^"]{1,40})"#', $html, $m)) $androidReq = $m[1];

    // Extract screenshot URLs (src attributes of screenshot img tags from Play Store)
    $screenshots = [];
    if (preg_match_all('#\["(https://play-lh\.googleusercontent\.com/[^"]{20,})",[^]]*\[\d+,\d+\]#', $html, $ms)) {
        foreach (array_unique($ms[1]) as $su) {
            if (count($screenshots) >= 6) break;
            // Only portrait/screenshot images (exclude icons, which are square)
            $screenshots[] = $su;
        }
    }

    return [
        'name'              => $title,
        'short_description' => $desc ? mb_substr($desc, 0, 300) : null,
        'long_description'  => $desc,
        'icon_url'          => $image,
        'package_name'      => $pkg,
        'playstore_url'     => $url,
        'developer'         => $developer,
        'version'           => $version,
        'android_version'   => $androidReq,
        'screenshot_urls'   => $screenshots,
    ];
}

// Returns an absolute URL for a stored media path — handles both relative paths
// (uploads/icons/...) and absolute CDN URLs stored as fallback when server download fails.
function media_url(string $path): string {
    if (!$path) return '';
    return (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) ? $path : url($path);
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

// Downloads Play Store screenshot images for a given app page URL and saves them to uploads/screenshots/.
// Returns array of relative paths (e.g. "uploads/screenshots/tiktok-ss1-abc123.webp").
// Best-effort: returns [] when the page can't be fetched or yields no screenshot URLs.
function fetch_playstore_screenshots(string $playstoreUrl, string $slug, int $max = 4): array {
    $ch = curl_init($playstoreUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'Accept-Language: ar,en;q=0.8'],
    ]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$html || $code !== 200) return [];

    // The og:image is the icon — exclude it from screenshot candidates.
    $iconUrl = null;
    if (preg_match('#<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']#i', $html, $m)) {
        $iconUrl = strtok($m[1], '='); // strip size params
    }

    // Play Store embeds all CDN image URLs in inline JSON/script blocks.
    preg_match_all('#https://play-lh\.googleusercontent\.com/([A-Za-z0-9_\-]{20,})#', $html, $m);
    $candidates = array_unique($m[0] ?? []);

    // Filter out icon, dedupe, keep first $max
    $screenshots = [];
    foreach ($candidates as $u) {
        $base = strtok($u, '=');
        if ($iconUrl && $base === $iconUrl) continue;
        $screenshots[] = $u;
        if (count($screenshots) >= $max) break;
    }
    if (!$screenshots) return [];

    $dir = UPLOAD_PATH . '/screenshots';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $saved = [];
    foreach ($screenshots as $i => $ssUrl) {
        $fetchUrl = preg_replace('#=.*$#', '', $ssUrl) . '=w1280';
        $tmp = tempnam(sys_get_temp_dir(), 'ss');
        $ch2 = curl_init($fetchUrl);
        curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_FOLLOWLOCATION => true]);
        $bin = curl_exec($ch2);
        $ok  = curl_getinfo($ch2, CURLINFO_HTTP_CODE) === 200 && $bin;
        curl_close($ch2);
        if (!$ok) { @unlink($tmp); continue; }
        file_put_contents($tmp, $bin);
        $info = @getimagesize($tmp);
        if (!$info) { @unlink($tmp); continue; }
        $fname = $slug . '-ss' . ($i + 1) . '-' . substr(md5($ssUrl), 0, 6) . '.webp';
        $dest  = "$dir/$fname";
        if (compress_image_to($tmp, $info['mime'], $dest, 1080, 2000, 82)) {
            $saved[] = "uploads/screenshots/$fname";
        }
        @unlink($tmp);
    }
    return $saved;
}

// Convenience: builds the key/model list from saved settings and runs a plain-text prompt.
// Returns ['ok'=>bool,'content'=>?string,'error'=>?string]
// Models that return classifiers/JSON-schema outputs instead of free text —
// using them for content generation always produces non-JSON or policy labels
// instead of the article/SEO copy the prompt asked for.
const AI_NON_TEXT_MODEL_PATTERNS = ['content-safety', 'moderation', 'guard', 'classifier', 'toxicity'];

function ai_filter_text_models(array $models): array {
    return array_values(array_filter($models, function(string $m): bool {
        $m = strtolower($m);
        foreach (AI_NON_TEXT_MODEL_PATTERNS as $p) {
            if (str_contains($m, $p)) return false;
        }
        return true;
    }));
}

// ── Alternative AI provider: aifreeforever.com ──
// Uses browser-like headers to access the API (no API key required).
// Returns ['ok'=>bool,'content'=>?string,'error'=>?string,'model'=>string]
function aifreeforever_call(string $prompt, int $timeout = 25): array {
    $ch = curl_init('https://aifreeforever.com/api/generate-ai-answer');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => [
            'accept: */*',
            'accept-language: ar-EG,ar;q=0.9,en-US;q=0.8,en;q=0.7',
            'content-type: application/json',
            'origin: https://aifreeforever.com',
            'referer: https://aifreeforever.com/',
            'sec-ch-ua: "Chromium";v="107", "Not=A?Brand";v="24"',
            'sec-ch-ua-mobile: ?1',
            'sec-ch-ua-platform: "Android"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'user-agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Mobile Safari/537.36',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'question'            => $prompt,
            'tone'                => 'friendly',
            'format'              => 'paragraph',
            'file'                => null,
            'conversationHistory' => [],
        ]),
    ]);
    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err)             return ['ok' => false, 'content' => null, 'error' => "خطأ اتصال: $err",          'model' => 'aifreeforever'];
    if ($code !== 200)    return ['ok' => false, 'content' => null, 'error' => "رمز استجابة: $code",       'model' => 'aifreeforever'];
    $d      = json_decode((string)$res, true);
    $answer = $d['answer'] ?? null;
    if (!$answer)         return ['ok' => false, 'content' => null, 'error' => 'رد فارغ من aifreeforever', 'model' => 'aifreeforever'];
    return ['ok' => true, 'content' => $answer, 'error' => null, 'model' => 'aifreeforever'];
}

function ai_text(PDO $pdo, string $prompt): array {
    // Switch between providers based on admin setting
    if (get_cfg($pdo, 'ai_provider', 'openrouter') === 'aifreeforever') {
        $r = aifreeforever_call($prompt);
        if (!$r['ok']) return ['ok' => false, 'content' => null, 'error' => $r['error']];
        return ['ok' => true, 'content' => $r['content'], 'error' => null];
    }

    // OpenRouter path
    $keys = openrouter_keys(get_cfg($pdo, 'openrouter_key'));
    if (!$keys) return ['ok' => false, 'content' => null, 'error' => 'لم يتم إضافة مفتاح OpenRouter بعد.'];
    $primary  = get_cfg($pdo, 'openrouter_model', 'meta-llama/llama-3.1-8b-instruct:free');
    $fallback = get_cfg($pdo, 'openrouter_fallback', 'google/gemma-2-9b-it:free');
    $autoRotate = get_cfg($pdo, 'openrouter_auto_rotate', '1') === '1';
    $models = array_values(array_unique(array_filter([$primary, $fallback])));
    if ($autoRotate) $models = array_values(array_unique(array_merge($models, openrouter_default_free_models())));
    $models = ai_filter_text_models($models);
    if (!$models) $models = ['meta-llama/llama-3.1-8b-instruct:free'];
    $r = openrouter_call_rotating($keys, $models, $prompt);
    if (!$r['ok']) return ['ok' => false, 'content' => null, 'error' => openrouter_diagnose_trace($r['trace'])];
    return ['ok' => true, 'content' => $r['content'], 'error' => null];
}

/* ── Telegram Bot integration ──────────────────────────────────────────
   Low-level send function. $body is a complete Telegram Bot API payload
   array (without chat_id — that's injected from settings). Sends to the
   configured channel. Returns ['ok'=>bool,'error'=>?string].
   ────────────────────────────────────────────────────────────────────── */
function telegram_api(PDO $pdo, string $method, array $body): array {
    $token  = get_cfg($pdo, 'telegram_bot_token', '');
    $chatId = get_cfg($pdo, 'telegram_channel_id', '');
    if (!$token || !$chatId) return ['ok' => false, 'error' => 'Telegram غير مُكوَّن (bot token أو channel ID مفقود)'];

    $body['chat_id'] = $chatId;
    $ch = curl_init("https://api.telegram.org/bot{$token}/{$method}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($body),
    ]);
    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) return ['ok' => false, 'error' => "cURL: $err"];
    $d = json_decode((string)$res, true);
    return ['ok' => $d['ok'] ?? false, 'error' => $d['description'] ?? ($code !== 200 ? "HTTP $code" : null)];
}

/* Send a notification whenever an app is newly published.
   - Tries sendPhoto first (shows icon) then falls back to sendMessage.
   - Uses AI to write the caption if configured, otherwise builds it manually.
   - Silently returns on failure so it never breaks the save flow.            */
function telegram_notify_new_app(PDO $pdo, array $app): void {
    if (get_cfg($pdo, 'telegram_enabled', '0') !== '1') return;
    if (!get_cfg($pdo, 'telegram_bot_token') || !get_cfg($pdo, 'telegram_channel_id')) return;

    $name      = $app['name']              ?? '';
    $shortDesc = $app['short_description'] ?? '';
    $version   = $app['version']           ?? '';
    $sizeMb    = $app['size_mb']           ?? '';
    $developer = $app['developer']         ?? '';
    $pkg       = $app['package_name']      ?? '';
    $features  = array_slice(array_filter(json_decode($app['features'] ?? '[]', true) ?: []), 0, 5);

    // Build caption instantly from stored data — never call the AI API here
    // because this function runs synchronously during the save request and an
    // AI call would block the admin browser for 15-30 seconds.
    $lines = ["🆕 <b>{$name}</b> متاح الآن على yassota!"];
    if ($shortDesc) $lines[] = "\n{$shortDesc}";
    if ($features)  $lines[] = "\n✨ <b>المميزات:</b>\n" . implode("\n", array_map(fn($f) => "• {$f}", $features));
    $meta = array_filter([
        $developer ? "👨‍💻 {$developer}" : '',
        $version   ? "🔖 v{$version}"   : '',
        $sizeMb    ? "📦 {$sizeMb} MB"  : '',
        $pkg       ? "📦 {$pkg}"        : '',
    ]);
    if ($meta) $lines[] = "\n" . implode('  ·  ', $meta);

    $text = implode('', $lines);
    // Telegram caption limit is 1024 chars
    if (mb_strlen($text) > 1020) $text = mb_substr($text, 0, 1020) . '…';

    $buttons = [[
        ['text' => '📥 تحميل التطبيق',  'url' => download_url($app['slug'])],
        ['text' => '📄 صفحة التطبيق',   'url' => app_url($app['slug'])],
    ]];
    $markup = ['inline_keyboard' => $buttons];

    // Try sendPhoto if we have an icon
    if (!empty($app['icon_path'])) {
        $iconUrl = rtrim(SITE_URL, '/') . '/' . ltrim($app['icon_path'], '/');
        $r = telegram_api($pdo, 'sendPhoto', [
            'photo'        => $iconUrl,
            'caption'      => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => $markup,
        ]);
        if ($r['ok']) return;
    }

    // sendMessage fallback (also used when there's no icon, or sendPhoto failed)
    telegram_api($pdo, 'sendMessage', [
        'text'         => $text,
        'parse_mode'   => 'HTML',
        'reply_markup' => $markup,
    ]);
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
- seo_title: **بحد أقصى صارم 60 حرفاً** (عدّ الحروف قبل الإرسال). يبدأ بـ"تحميل"، ثم اسم التطبيق، ثم "APK"، ثم "{$year} للأندرويد". مثال (55 حرفاً): "تحميل TikTok APK {$year} للأندرويد مجاناً" — لا تتجاوز هذا الطول أبداً.
- meta_description: يبدأ بفعل أمر مثل "قم بتحميل" أو "حمّل"، يذكر اسم التطبيق ونوعه (لعبة/تطبيق) و"APK" و"آخر إصدار {$year}" و"للأندرويد مجاناً برابط مباشر وسريع"، ثم جملة تسويقية قصيرة عن أبرز مزايا هذا التطبيق تحديداً (وليست عامة). الطول 140-160 حرفاً.
- keywords: 15 إلى 18 كلمة/عبارة مفتاحية عربية طويلة الذيل مفصولة بفاصلة، تغطي أشكال البحث الشائعة: "تحميل [الاسم]"، "تنزيل [الاسم]"، "[الاسم] APK"، "[الاسم] آخر إصدار"، "تحميل [الاسم] للأندرويد"، "[الاسم] مجاناً"، "تحديث [الاسم]"، "[الاسم] Android"، "تحميل [الاسم] برابط مباشر"، "[الاسم] {$year}" وهكذا — بدون تكرار حرفي لنفس الصياغة، وبمزيج من العربية والإنجليزية عندما يكون اسم التطبيق إنجليزياً معروفاً.
P;
}

// Enforce max 60-char seo_title server-side — truncate at word boundary
function seo_title_clamp(string $t, int $max = 60): string {
    $t = trim($t);
    if (mb_strlen($t) <= $max) return $t;
    $cut = mb_substr($t, 0, $max);
    $last = mb_strrpos($cut, ' ');
    return $last > $max * 0.6 ? mb_substr($cut, 0, $last) : $cut;
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

/* ═══════════════════════════════════════════════
   Subdomain language detection.
   Works when the host runs under a wildcard DNS
   (*.yassota.com → same docroot). Returns the
   2-letter lang code from the subdomain, or null
   if on the main domain or an unknown subdomain.
   ═══════════════════════════════════════════════ */
function detect_lang_from_subdomain(): ?string {
    static $cached = false;
    if ($cached !== false) return $cached;
    $host    = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $mainHost = strtolower(parse_url(SITE_URL, PHP_URL_HOST) ?: '');
    if ($host === $mainHost || $host === '') { $cached = null; return null; }
    // e.g. en.yassota.com — strip port first
    $host = preg_replace('/:\d+$/', '', $host);
    $parts = explode('.', $host);
    if (count($parts) < 3) { $cached = null; return null; }
    $sub  = $parts[0];
    // Validate: must be a 2-letter language code we know about
    $valid = ['en','ru','fr','de','es','tr','id','pt','ur','hi','zh','ja','ko','it','nl','fa','bn','th','vi'];
    $cached = in_array($sub, $valid, true) ? $sub : null;
    return $cached;
}

/* Returns the URL for a given language subdomain.
   e.g. lang_subdomain_url('en') → https://en.yassota.com */
function lang_subdomain_url(string $lang, string $path = ''): string {
    $mainHost = parse_url(SITE_URL, PHP_URL_HOST) ?: '';
    $scheme   = parse_url(SITE_URL, PHP_URL_SCHEME) ?: 'https';
    return $scheme . '://' . $lang . '.' . $mainHost . '/' . ltrim($path, '/');
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
    // Transliterate common Arabic letters to approximate Latin equivalents
    static $arabic_map = [
        'أ'=>'a','إ'=>'i','آ'=>'a','ا'=>'a','ب'=>'b','ت'=>'t','ث'=>'th','ج'=>'j','ح'=>'h','خ'=>'kh',
        'د'=>'d','ذ'=>'dh','ر'=>'r','ز'=>'z','س'=>'s','ش'=>'sh','ص'=>'s','ض'=>'d','ط'=>'t','ظ'=>'z',
        'ع'=>'a','غ'=>'gh','ف'=>'f','ق'=>'q','ك'=>'k','ل'=>'l','م'=>'m','ن'=>'n','ه'=>'h','و'=>'w',
        'ي'=>'y','ى'=>'a','ة'=>'h','ء'=>'','ئ'=>'y','ؤ'=>'w','لا'=>'la',
        // Persian/Urdu extras
        'پ'=>'p','چ'=>'ch','ژ'=>'zh','گ'=>'g','ک'=>'k','ی'=>'y','ے'=>'e',
    ];
    $t = str_replace(array_keys($arabic_map), array_values($arabic_map), $t);
    // Remove any remaining non-ASCII characters (diacritics, etc.)
    $t = preg_replace('/[^\x00-\x7F]+/', '-', $t);
    $t = preg_replace('/\s+/', '-', $t);
    $t = preg_replace('/[^a-zA-Z0-9\-]/', '', $t);
    $t = preg_replace('/-+/', '-', $t);
    return strtolower(trim($t, '-')) ?: 'app-' . time();
}

// Route names that must never collide with an app's pretty URL (/{slug}).
const RESERVED_SLUGS = [
    'index', 'app', 'admin', 'download', 'category', 'developer', 'top', 'updates',
    'sitemap', 'robots', 'config', 'partials', 'install', 'uploads', 'assets',
    'about', 'contact', 'privacy-policy', 'terms', 'dmca', 'cookie-policy', 'article',
    'rss', 'track-view', 'comment-form', 'manifest.json', 'favicon.svg', 'search-suggest',
    'blog', 'blog-post',
];

function unique_slug(PDO $pdo, string $base, int $excludeId = 0): string {
    $slug = slugify($base);
    if (in_array($slug, RESERVED_SLUGS, true)) $slug .= '-app';
    $orig = $slug; $i = 1;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM apps WHERE slug=? AND id<>?");
    $stmt->execute([$slug, $excludeId]);
    while ($stmt->fetchColumn() > 0) {
        $slug = $orig . '-' . (++$i);
        $stmt->execute([$slug, $excludeId]);
    }
    return $slug;
}

// Pretty-URL helpers: yassota.com/{slug} and yassota.com/{slug}/download
function app_url(string $slug): string { return url(rawurlencode($slug)); }
function download_url(string $slug, int $mirror = 1): string {
    return url(rawurlencode($slug) . '/download') . ($mirror > 1 ? '?m=' . $mirror : '');
}
function article_url(string $slug): string { return url('article/' . rawurlencode($slug)); }

function unique_article_slug(PDO $pdo, string $base): string {
    $slug = slugify($base);
    $orig = $slug; $i = 1;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM app_articles WHERE slug=?");
    $stmt->execute([$slug]);
    while ($stmt->fetchColumn() > 0) {
        $slug = $orig . '-' . (++$i);
        $stmt->execute([$slug]);
    }
    return $slug;
}

/* ── General blog (tutorials/news/comparisons/best-of/articles) ── */
const BLOG_TYPES = [
    'tutorial'   => 'الشروحات',
    'news'       => 'الأخبار',
    'comparison' => 'المقارنات',
    'best-apps'  => 'أفضل التطبيقات',
    'best-games' => 'أفضل الألعاب',
    'article'    => 'مقالات عامة',
    'code-page'  => 'صفحة المحتوى',
];

/* Language sections available in a code-page blog post */
const CODE_PAGE_LANGS = [
    'html'   => ['label' => 'HTML',   'icon' => '🌐', 'color' => '#e34f26', 'ext' => '.html'],
    'css'    => ['label' => 'CSS',    'icon' => '🎨', 'color' => '#1572b6', 'ext' => '.css'],
    'js'     => ['label' => 'JS',     'icon' => '⚡', 'color' => '#f7df1e', 'ext' => '.js'],
    'php'    => ['label' => 'PHP',    'icon' => '🐘', 'color' => '#777bb4', 'ext' => '.php'],
    'python' => ['label' => 'Python', 'icon' => '🐍', 'color' => '#3776ab', 'ext' => '.py'],
    'java'   => ['label' => 'Java',   'icon' => '☕', 'color' => '#f89820', 'ext' => '.java'],
    'kotlin' => ['label' => 'Kotlin', 'icon' => '🎯', 'color' => '#7f52ff', 'ext' => '.kt'],
    'cpp'    => ['label' => 'C++',    'icon' => '⚙️', 'color' => '#00599c', 'ext' => '.cpp'],
];

/* Decode a code-page body JSON safely, returns assoc array of lang=>code */
function decode_code_page(string $body): array {
    $data = json_decode($body, true);
    if (!is_array($data)) return [];
    $sections = $data['sections'] ?? $data;
    $out = [];
    foreach (array_keys(CODE_PAGE_LANGS) as $lang) {
        $code = trim($sections[$lang] ?? '');
        if ($code !== '') $out[$lang] = $code;
    }
    return $out;
}
/* Render a blog body that might be HTML (from WYSIWYG) or plain text (old AI posts).
   If it contains HTML tags, output as-is (admin-only authoring — trusted source).
   If plain text, wrap each double-newline block in <p> so it renders correctly. */
function render_blog_body(string $body): string {
    if (trim($body) === '') return '';
    if (preg_match('/<[a-zA-Z][a-zA-Z0-9]*[\s\/>]/u', $body)) {
        return $body; // real HTML — output raw
    }
    // Plain text fallback: split on blank lines → paragraphs
    $paras = preg_split('/\n{2,}/', trim($body));
    $html  = '';
    foreach ($paras as $p) {
        $p = trim($p);
        if ($p === '') continue;
        // Single-line that looks like a heading (starts with a short bold phrase before : or —)
        if (preg_match('/^(.{4,60})[:\—\-]\s*$/u', $p)) {
            $html .= '<h3>' . htmlspecialchars($p, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h3>';
        } else {
            $html .= '<p>' . nl2br(htmlspecialchars($p, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>';
        }
    }
    return $html;
}

function blog_type_label(string $type): string { return BLOG_TYPES[$type] ?? 'مقالات'; }
function blog_post_url(string $slug): string { return url('blog/' . rawurlencode($slug)); }
function blog_type_url(string $type): string { return url('blog?type=' . rawurlencode($type)); }

function unique_blog_slug(PDO $pdo, string $base, int $excludeId = 0): string {
    $slug = slugify($base);
    if (!$slug) $slug = 'post-' . time();
    $orig = $slug; $i = 1;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM blog_posts WHERE slug=? AND id<>?");
    $stmt->execute([$slug, $excludeId]);
    while ($stmt->fetchColumn() > 0) {
        $slug = $orig . '-' . (++$i);
        $stmt->execute([$slug, $excludeId]);
    }
    return $slug;
}

// CSS/JS URL with a cache-busting ?v= based on the file's own mtime — required
// because .htaccess caches these for a month; without this, a bug fix to
// main.css/main.js would silently not reach any browser that already has the
// old file cached, until that month expires or the visitor hard-refreshes.
function asset_url(string $relPath): string {
    $full = ROOT_PATH . '/' . ltrim($relPath, '/');
    if (!is_file($full)) return url($relPath) . '?v=' . time();
    $v = filemtime($full);

    // Self-healing CSS minification: regenerate the sibling .min.css file
    // whenever the source changes (same idempotent pattern as ensure_schema),
    // and serve that as a normal static file instead — safe (only strips
    // comments/whitespace) unlike JS, where the same regex trick risks
    // breaking string or regex literals. JS is served as-is.
    if (str_ends_with($relPath, '.css')) {
        $minRelPath = substr($relPath, 0, -4) . '.min.css';
        $minFull = ROOT_PATH . '/' . ltrim($minRelPath, '/');
        if (!is_file($minFull) || filemtime($minFull) < $v) {
            $css = (string)file_get_contents($full);
            $css = preg_replace('#/\*.*?\*/#s', '', $css);
            $css = preg_replace('/\s+/', ' ', $css);
            $css = preg_replace('/\s*([{}:;,>])\s*/', '$1', $css);
            $css = preg_replace('/;}/', '}', (string)$css);
            @file_put_contents($minFull, trim((string)$css));
        }
        if (is_file($minFull)) return url($minRelPath) . '?v=' . $v;
    }

    return url($relPath) . '?v=' . $v;
}

function is_admin(): bool { return !empty($_SESSION['admin_id']); }

function require_admin(): void {
    if (!is_admin()) { header('Location: ' . url('admin.php?page=login')); exit; }
}

function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? ''; }

// Beyond the honeypot+CSRF pair, a bot (or an impatient human) could still
// flood the pending-comments moderation queue by submitting repeatedly.
// Simple IP-based cap: max 5 comments per IP per hour, across all apps.
function comment_rate_limit_ok(PDO $pdo, string $ip): bool {
    if ($ip === '') return true;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE ip=? AND created_at > (NOW() - INTERVAL 1 HOUR)");
    $stmt->execute([$ip]);
    return (int)$stmt->fetchColumn() < 5;
}

/* ═══════════════════════════════════════════════
   VirusTotal — real (not simulated) safety-scan badge
   for the download link. Never shows a badge unless a
   real API response produced one. Two-step because a
   fresh URL scan takes VirusTotal itself 30s-2min to
   finish, and blocking a PHP worker that long is exactly
   the mistake already fixed for the AI generation calls:
   vt_scan_url() does one fast lookup/submit and returns
   immediately; vt_check_pending() does one fast follow-up
   poll, called again later (a button, not an auto-loop).
   ═══════════════════════════════════════════════ */
function vt_api_key(PDO $pdo): string { return trim(get_cfg($pdo, 'virustotal_api_key')); }

function vt_scan_url(PDO $pdo, int $appId, string $url): array {
    $key = vt_api_key($pdo);
    if (!$key) return ['ok' => false, 'error' => 'لم يتم إضافة مفتاح VirusTotal API في الإعدادات'];
    if (!$url) return ['ok' => false, 'error' => 'لا يوجد رابط تحميل لفحصه'];

    // Look up an existing report first (URL identifier per VT's spec).
    $urlId = rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    $ch = curl_init("https://www.virustotal.com/api/v3/urls/$urlId");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ["x-apikey: $key"]]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200) {
        $d = json_decode((string)$res, true);
        $stats = $d['data']['attributes']['last_analysis_stats'] ?? null;
        if ($stats) {
            $malicious = (int)($stats['malicious'] ?? 0) + (int)($stats['suspicious'] ?? 0);
            $total = array_sum($stats);
            $pdo->prepare("UPDATE apps SET vt_status=?, vt_malicious=?, vt_total_engines=?, vt_scanned_at=NOW(), vt_permalink=?, vt_analysis_id=NULL WHERE id=?")
                ->execute([$malicious > 0 ? 'flagged' : 'clean', $malicious, $total, "https://www.virustotal.com/gui/url/$urlId", $appId]);
            return ['ok' => true, 'status' => $malicious > 0 ? 'flagged' : 'clean'];
        }
    } elseif ($code !== 404) {
        return ['ok' => false, 'error' => "فشل الاتصال بـ VirusTotal (رمز $code)"];
    }

    // No existing report — submit a new scan; VT needs time to finish it,
    // so this only stores the analysis id for a later vt_check_pending() call.
    $ch = curl_init("https://www.virustotal.com/api/v3/urls");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ["x-apikey: $key", "Content-Type: application/x-www-form-urlencoded"],
        CURLOPT_POSTFIELDS => http_build_query(['url' => $url]),
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) return ['ok' => false, 'error' => "فشل إرسال الرابط للفحص (رمز $code)"];
    $d = json_decode((string)$res, true);
    $analysisId = $d['data']['id'] ?? null;
    $pdo->prepare("UPDATE apps SET vt_status='pending', vt_analysis_id=?, vt_scanned_at=NOW() WHERE id=?")
        ->execute([$analysisId, $appId]);
    return ['ok' => true, 'status' => 'pending'];
}

function vt_check_pending(PDO $pdo, int $appId, string $analysisId): array {
    $key = vt_api_key($pdo);
    if (!$key || !$analysisId) return ['ok' => false, 'error' => 'لا يوجد فحص معلّق'];
    $ch = curl_init("https://www.virustotal.com/api/v3/analyses/$analysisId");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ["x-apikey: $key"]]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) return ['ok' => false, 'error' => "فشل الاتصال بـ VirusTotal (رمز $code)"];
    $d = json_decode((string)$res, true);
    if (($d['data']['attributes']['status'] ?? '') !== 'completed') return ['ok' => true, 'status' => 'pending'];
    $stats = $d['data']['attributes']['stats'] ?? [];
    $malicious = (int)($stats['malicious'] ?? 0) + (int)($stats['suspicious'] ?? 0);
    $total = array_sum($stats);
    $pdo->prepare("UPDATE apps SET vt_status=?, vt_malicious=?, vt_total_engines=? WHERE id=?")
        ->execute([$malicious > 0 ? 'flagged' : 'clean', $malicious, $total, $appId]);
    return ['ok' => true, 'status' => $malicious > 0 ? 'flagged' : 'clean'];
}

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
    <style>body{margin:0;font-family:Tahoma,Arial,sans-serif;background:#f5f7fb;color:#0f172a;padding:40px 16px;line-height:1.9;text-align:center}
    .box{max-width:480px;margin:60px auto;background:#fff;border:1px solid rgba(15,23,42,.09);border-radius:14px;padding:32px;box-shadow:0 4px 24px rgba(15,23,42,.06)}
    h1{color:#dc2626;font-size:20px;margin:0 0 14px}
    code{background:#f1f4f9;color:#2563eb;padding:4px 10px;border-radius:6px;font-family:monospace;direction:ltr;display:inline-block;margin-top:6px}
    </style></head><body><div class="box">
    <h1>الوصول مرفوض</h1>
    <p>عنوان IP الخاص بك غير مدرج ضمن قائمة الوصول المسموح بها للوحة التحكم.</p>
    <p>عنوانك الحالي:<br><code>' . $ip . '</code></p>
    </div></body></html>');
}

function process_icon(array $file, string $slug, PDO $pdo = null): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > MAX_ICON_MB * 1048576) return null;
    // Security scan before any processing
    $threats = scan_file_for_threats($file['tmp_name'], 'image');
    if ($threats && $pdo) {
        log_security_event($pdo, 'upload_blocked', 'critical',
            'Icon upload blocked: ' . implode('; ', $threats), $file['name'] ?? '');
    }
    if ($threats) return null; // Block malicious uploads
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
    // Strip non-ASCII from slug so the filename is always safe in HTTP headers and regex checks
    $safeBase = preg_replace('/[^a-z0-9\-]/i', '', strtolower($slug)) ?: 'icon';
    $name = $safeBase . '-' . substr(md5(uniqid()), 0, 6);
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

/* ═══════════════════════════════════════════════
   APK hosting — metadata extraction + file serving
   ═══════════════════════════════════════════════ */

function android_min_sdk_name(string|int $sdk): string {
    $map = [
        1=>'1.0',2=>'1.1',3=>'1.5',4=>'1.6',5=>'2.0',6=>'2.0.1',7=>'2.1',
        8=>'2.2',9=>'2.3',10=>'2.3.3',11=>'3.0',12=>'3.1',13=>'3.2',
        14=>'4.0',15=>'4.0.3',16=>'4.1',17=>'4.2',18=>'4.3',19=>'4.4',
        20=>'4.4W',21=>'5.0',22=>'5.1',23=>'6.0',24=>'7.0',25=>'7.1',
        26=>'8.0',27=>'8.1',28=>'9.0',29=>'10',30=>'11',31=>'12',
        32=>'12L',33=>'13',34=>'14',35=>'15',
    ];
    $n = (int)$sdk;
    return ($map[$n] ?? (string)$sdk) . '+';
}

function format_apk_size(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
    if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)       return round($bytes / 1024) . ' KB';
    return $bytes . ' B';
}

function parse_android_manifest_binary(string $raw): array {
    $out = ['package'=>'', 'version_name'=>'', 'version_code'=>'', 'min_sdk'=>'', 'target_sdk'=>''];
    if (strlen($raw) < 8) return $out;

    $fileType = unpack('v', substr($raw, 0, 2))[1];
    if ($fileType !== 0x0003) return $out; // must be RES_XML_TYPE

    $pos = 8; // skip 8-byte file header

    // Expect string pool chunk (type=0x0001)
    if ($pos + 28 > strlen($raw)) return $out;
    $spType = unpack('v', substr($raw, $pos, 2))[1];
    if ($spType !== 0x0001) return $out;

    $spHeaderSize = unpack('v', substr($raw, $pos+2, 2))[1];
    $spChunkSize  = unpack('V', substr($raw, $pos+4, 4))[1];
    $strCount     = unpack('V', substr($raw, $pos+8, 4))[1];
    $flags        = unpack('V', substr($raw, $pos+16, 4))[1];
    $stringsStart = unpack('V', substr($raw, $pos+20, 4))[1];

    $isUtf8    = ($flags & 0x100) !== 0;
    $offsetBase = $pos + $spHeaderSize;
    $strBase    = $pos + $stringsStart;
    $len        = strlen($raw);

    $pool = [];
    for ($i = 0; $i < $strCount; $i++) {
        if ($offsetBase + $i*4 + 4 > $len) break;
        $strOff = unpack('V', substr($raw, $offsetBase + $i*4, 4))[1];
        $s = $strBase + $strOff;
        if ($s >= $len) { $pool[] = ''; continue; }

        if ($isUtf8) {
            $b = ord($raw[$s++]);
            if ($b & 0x80) { $b = (($b & 0x7f) << 8) | ord($raw[$s++]); }
            $bl = ord($raw[$s++]);
            if ($bl & 0x80) { $bl = (($bl & 0x7f) << 8) | ord($raw[$s++]); }
            $pool[] = ($s + $bl <= $len) ? substr($raw, $s, $bl) : '';
        } else {
            if ($s + 2 > $len) { $pool[] = ''; continue; }
            $cl = unpack('v', substr($raw, $s, 2))[1]; $s += 2;
            if ($cl & 0x8000) {
                if ($s + 2 > $len) { $pool[] = ''; continue; }
                $cl = (($cl & 0x7fff) << 16) | unpack('v', substr($raw, $s, 2))[1]; $s += 2;
            }
            $bl = $cl * 2;
            $pool[] = ($s + $bl <= $len)
                ? mb_convert_encoding(substr($raw, $s, $bl), 'UTF-8', 'UTF-16LE')
                : '';
        }
    }

    $pos += $spChunkSize;

    while ($pos + 8 <= $len) {
        $chunkType = unpack('v', substr($raw, $pos, 2))[1];
        $chunkSize = unpack('V', substr($raw, $pos+4, 4))[1];
        if ($chunkSize < 8 || $pos + $chunkSize > $len) break;

        if ($chunkType === 0x0102) { // RES_XML_START_ELEMENT_TYPE
            // ResXMLTree_node (16B) + ResXMLTree_attrExt (20B) = 36B header
            $nameRef   = unpack('V', substr($raw, $pos+20, 4))[1] & 0x00ffffff;
            $attrStart = unpack('v', substr($raw, $pos+24, 2))[1]; // relative to pos+16
            $attrSize  = unpack('v', substr($raw, $pos+26, 2))[1]; // usually 20
            $attrCount = unpack('v', substr($raw, $pos+28, 2))[1];
            $elemName  = $pool[$nameRef] ?? '';
            $attrBase  = $pos + 16 + $attrStart;
            $attrSize  = $attrSize ?: 20;

            $attrs = [];
            for ($a = 0; $a < $attrCount; $a++) {
                $aOff = $attrBase + $a * $attrSize;
                if ($aOff + $attrSize > $len) break;
                $aName    = unpack('V', substr($raw, $aOff+4, 4))[1] & 0x00ffffff;
                $aRaw     = unpack('V', substr($raw, $aOff+8, 4))[1];
                $aType    = isset($raw[$aOff+15]) ? ord($raw[$aOff+15]) : 0;
                $aDataRaw = substr($raw, $aOff+16, 4);
                $aData    = strlen($aDataRaw) === 4 ? unpack('V', $aDataRaw)[1] : 0;

                $attrName = $pool[$aName] ?? '';
                if ($aRaw !== 0xffffffff && isset($pool[$aRaw]) && $pool[$aRaw] !== '') {
                    $val = $pool[$aRaw];
                } elseif ($aType === 0x03 && isset($pool[$aData])) {
                    $val = $pool[$aData];
                } elseif ($aType === 0x10 || $aType === 0x11) {
                    $val = (string)sprintf('%u', $aData);
                } elseif ($aType === 0x12) {
                    $val = $aData ? 'true' : 'false';
                } else {
                    $val = '';
                }
                if ($attrName !== '') $attrs[$attrName] = $val;
            }

            if ($elemName === 'manifest') {
                if (isset($attrs['package']))     $out['package']      = $attrs['package'];
                if (isset($attrs['versionName'])) $out['version_name'] = $attrs['versionName'];
                if (isset($attrs['versionCode'])) $out['version_code'] = $attrs['versionCode'];
            } elseif ($elemName === 'uses-sdk') {
                if (isset($attrs['minSdkVersion']))    $out['min_sdk']    = $attrs['minSdkVersion'];
                if (isset($attrs['targetSdkVersion'])) $out['target_sdk'] = $attrs['targetSdkVersion'];
            }
        }
        $pos += $chunkSize;
    }
    return $out;
}

function extract_apk_meta(string $apkPath): array {
    $meta = [
        'package_name' => '',
        'version'      => '',
        'version_code' => '',
        'min_sdk'      => '',
        'target_sdk'   => '',
        'size_bytes'   => (int)filesize($apkPath),
        'sha256'       => hash_file('sha256', $apkPath),
        'md5'          => hash_file('md5',    $apkPath),
    ];

    // Try aapt / aapt2 (preferred — always available on Android build systems)
    foreach (['aapt', 'aapt2', '/usr/bin/aapt', '/usr/local/bin/aapt', '/opt/android-sdk/build-tools/aapt'] as $bin) {
        $lines = [];
        @exec(escapeshellcmd($bin) . ' dump badging ' . escapeshellarg($apkPath) . ' 2>/dev/null', $lines, $rc);
        if ($rc !== 0 || !$lines) continue;
        foreach ($lines as $line) {
            if (preg_match("/^package:.*?name='([^']+)'/",        $line, $m)) $meta['package_name'] = $m[1];
            if (preg_match("/^package:.*?versionName='([^']+)'/", $line, $m)) $meta['version']      = $m[1];
            if (preg_match("/^package:.*?versionCode='([^']+)'/", $line, $m)) $meta['version_code'] = $m[1];
            if (preg_match("/^sdkVersion:'([^']+)'/",             $line, $m)) $meta['min_sdk']      = $m[1];
            if (preg_match("/^targetSdkVersion:'([^']+)'/",       $line, $m)) $meta['target_sdk']   = $m[1];
        }
        if ($meta['package_name']) return $meta;
    }

    // Fallback: parse binary AndroidManifest.xml from the APK (which is a ZIP)
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($apkPath) === true) {
            $raw = $zip->getFromName('AndroidManifest.xml');
            $zip->close();
            if ($raw) {
                $parsed = parse_android_manifest_binary($raw);
                if ($parsed['package'])      $meta['package_name'] = $parsed['package'];
                if ($parsed['version_name']) $meta['version']      = $parsed['version_name'];
                if ($parsed['version_code']) $meta['version_code'] = $parsed['version_code'];
                if ($parsed['min_sdk'])      $meta['min_sdk']      = $parsed['min_sdk'];
                if ($parsed['target_sdk'])   $meta['target_sdk']   = $parsed['target_sdk'];
            }
        }
    }
    return $meta;
}

function store_apk_file(array $uploadedFile, string $slug): array {
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'خطأ في رفع الملف: ' . $uploadedFile['error']];
    }
    $tmp = $uploadedFile['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'ملف غير صالح'];
    }

    // Verify APK magic: ZIP signature PK\x03\x04
    $fh = fopen($tmp, 'rb');
    $magic = fread($fh, 4);
    fclose($fh);
    if ($magic !== "PK\x03\x04") {
        return ['ok' => false, 'error' => 'الملف ليس APK صالح (توقيع ZIP غير صحيح)'];
    }

    // Scan for embedded threats (malicious APKs can embed PHP shells)
    global $pdo;
    $threats = scan_file_for_threats($tmp, 'apk');
    $criticalThreats = array_filter($threats, fn($t) => stripos($t, 'PHP code') !== false || stripos($t, 'webshell') !== false || stripos($t, 'eval') !== false);
    if ($criticalThreats && isset($pdo)) {
        log_security_event($pdo, 'apk_threat_detected', 'critical',
            'APK threat: ' . implode('; ', $criticalThreats), basename($tmp));
    }

    $dir = UPLOAD_PATH . '/apk';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $meta     = extract_apk_meta($tmp);
    $version  = preg_replace('/[^a-zA-Z0-9._\-]/', '', $meta['version'] ?: '1.0');
    $filename = $slug . '-v' . $version . '-' . substr($meta['sha256'], 0, 8) . '.apk';
    $dest     = "$dir/$filename";

    if (!move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'error' => 'فشل نقل الملف'];
    }
    chmod($dest, 0644);

    return [
        'ok'           => true,
        'apk_path'     => 'uploads/apk/' . $filename,
        'apk_size_bytes' => $meta['size_bytes'],
        'apk_hash_sha256' => $meta['sha256'],
        'apk_hash_md5'    => $meta['md5'],
        'package_name' => $meta['package_name'],
        'version'      => $meta['version'],
        'version_code' => $meta['version_code'],
        'min_sdk'      => $meta['min_sdk'] ? android_min_sdk_name($meta['min_sdk']) : '',
        'target_sdk'   => $meta['target_sdk'] ? android_min_sdk_name($meta['target_sdk']) : '',
    ];
}

function serve_apk_file(string $relPath, string $appName, string $version): never {
    $abs = __DIR__ . '/' . ltrim($relPath, '/');
    if (!file_exists($abs) || !is_file($abs)) {
        http_response_code(404); exit;
    }

    $fileSize = filesize($abs);
    $slug     = preg_replace('/[^a-zA-Z0-9._\-]/', '-', $appName);
    $fname    = $slug . '-v' . $version . '.apk';

    // Range request support for resumable downloads
    $start  = 0;
    $end    = $fileSize - 1;
    $length = $fileSize;
    $code   = 200;

    if (!empty($_SERVER['HTTP_RANGE'])) {
        if (preg_match('#bytes=(\d*)-(\d*)#', $_SERVER['HTTP_RANGE'], $m)) {
            $start = $m[1] !== '' ? (int)$m[1] : 0;
            $end   = $m[2] !== '' ? (int)$m[2] : $fileSize - 1;
            if ($start > $end || $start < 0 || $end >= $fileSize) {
                header('HTTP/1.1 416 Range Not Satisfiable');
                header("Content-Range: bytes */$fileSize");
                exit;
            }
            $length = $end - $start + 1;
            $code   = 206;
        }
    }

    http_response_code($code);
    header('Accept-Ranges: bytes');
    header('Content-Type: application/vnd.android.package-archive');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header("Content-Length: $length");
    if ($code === 206) header("Content-Range: bytes $start-$end/$fileSize");

    // Stream in 64KB chunks — never loads the whole APK into memory
    $fh = fopen($abs, 'rb');
    if (!$fh) { http_response_code(500); exit; }
    if ($start > 0) fseek($fh, $start);
    $sent = 0;
    while (!feof($fh) && $sent < $length) {
        $chunk = fread($fh, min(65536, $length - $sent));
        if ($chunk === false) break;
        echo $chunk;
        $sent += strlen($chunk);
        if (ob_get_level()) ob_flush();
        flush();
    }
    fclose($fh);
    exit;
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
// Search-engine ownership verification meta tags, printed in every page
// <head> when the admin has filled in the codes under Settings. Empty by
// default (no tag output) until configured — required before Search
// Console / Bing Webmaster Tools will accept the site, which in turn is
// required before Google will crawl/index it reliably or show it well in
// search results.
function search_console_meta(PDO $pdo): string {
    $out = '';
    $g = trim(get_cfg($pdo, 'google_site_verification'));
    $b = trim(get_cfg($pdo, 'bing_site_verification'));
    if ($g !== '') $out .= '<meta name="google-site-verification" content="' . h($g) . '">' . "\n  ";
    if ($b !== '') $out .= '<meta name="msvalidate.01" content="' . h($b) . '">' . "\n  ";
    return $out;
}

// Favicon + web-app manifest + theme-color + search-console verification,
// printed once in every page <head> right after the charset meta tag.
// Best-effort email notification to the configured contact address, gated
// by an opt-in setting (default off — PHP's mail() is unreliable on some
// hosts and shouldn't ever be allowed to break the form submission it's
// attached to, so failures here are silently ignored).
function notify_admin(PDO $pdo, string $subject, string $body): void {
    if (get_cfg($pdo, 'admin_email_notifications', '0') !== '1') return;
    $to = trim(get_cfg($pdo, 'contact_email'));
    if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) return;
    $headers = "From: yassota <no-reply@" . parse_url(SITE_URL, PHP_URL_HOST) . ">\r\n"
        . "Content-Type: text/plain; charset=UTF-8";
    @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}

function head_extras(PDO $pdo): string {
    $host     = parse_url(SITE_URL, PHP_URL_HOST) ?: 'example.com';
    $ga4Id    = trim(get_cfg($pdo, 'ga4_measurement_id', ''));
    $ogDefImg = trim(get_cfg($pdo, 'og_default_image', ''));

    // ── Google Consent Mode v2 — fires BEFORE any Google script ──────────
    // Default: deny until visitor explicitly accepts via cookie banner.
    // banner JS calls gtag('consent','update',{...}) on accept.
    $out = '<script>
window.dataLayer=window.dataLayer||[];
function gtag(){dataLayer.push(arguments);}
gtag("consent","default",{
  "ad_storage":"denied","ad_user_data":"denied",
  "ad_personalization":"denied","analytics_storage":"denied",
  "wait_for_update":500
});
gtag("set","ads_data_redaction",true);
gtag("set","url_passthrough",true);
</script>';

    // ── Google Analytics 4 ────────────────────────────────────────────────
    // Configure via Admin → Settings → "Google Analytics 4 Measurement ID".
    // Must run AFTER Consent Mode default above.
    if ($ga4Id !== '') {
        $out .= "\n  " . '<script async src="https://www.googletagmanager.com/gtag/js?id=' . h($ga4Id) . '"></script>'
              . "\n  " . '<script>gtag("js",new Date());gtag("config","' . h($ga4Id) . '",{"anonymize_ip":true});</script>';
    }

    // ── Preconnects for Google services ──────────────────────────────────
    $out .= "\n  " . '<link rel="preconnect" href="https://fonts.googleapis.com">'
          . "\n  " . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
          . "\n  " . '<link rel="preconnect" href="https://pagead2.googlesyndication.com" crossorigin>';

    // ── Google Fonts — non-blocking async load ────────────────────────────
    // @import inside CSS is parser-blocking and kills Core Web Vitals (LCP/FCP).
    // The media="print" trick loads the stylesheet asynchronously so the page
    // renders immediately with system fallback fonts, then swaps to the web
    // fonts once they arrive. font-display=swap (in the URL) prevents invisible
    // text during the swap window. A <noscript> fallback keeps fonts working
    // when JS is disabled.
    $fontsUrl = 'https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&family=Tajawal:wght@400;500;700&family=JetBrains+Mono:wght@400;600&display=swap';
    $out .= "\n  " . '<link rel="preload" as="style" href="' . $fontsUrl . '">'
          . "\n  " . '<link rel="stylesheet" href="' . $fontsUrl . '" media="print" onload="this.media=\'all\'">'
          . "\n  " . '<noscript><link rel="stylesheet" href="' . $fontsUrl . '"></noscript>';

    // ── AdSense auto-ads ──────────────────────────────────────────────────
    $out .= "\n  " . '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189" crossorigin="anonymous"></script>';

    // ── Site identity & discovery ─────────────────────────────────────────
    $out .= "\n  " . '<link rel="icon" type="image/svg+xml" href="' . h(url('favicon.svg')) . '">'
          . "\n  " . '<link rel="manifest" href="' . h(url('manifest.json')) . '">'
          . "\n  " . '<link rel="alternate" type="application/rss+xml" title="yassota — آخر التحديثات" href="' . h(url('rss')) . '">';

    // ── Global meta ───────────────────────────────────────────────────────
    $out .= "\n  " . '<meta name="theme-color" content="#2563eb">'
          . "\n  " . '<meta name="robots" content="index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1">'
          . "\n  " . '<meta name="language" content="ar">'
          . "\n  " . '<meta property="og:locale" content="ar_AR">'
          . "\n  " . '<meta property="og:site_name" content="yassota">'
          . "\n  " . '<meta name="twitter:site" content="@yassota">'
          . "\n  " . '<meta name="author" content="yassota">'
          . "\n  " . '<link rel="alternate" hreflang="ar" href="' . h(SITE_URL) . '">'
          . "\n  " . '<link rel="alternate" hreflang="x-default" href="' . h(SITE_URL) . '">';

    // ── Default OG image (site-wide fallback for pages without their own) ─
    if ($ogDefImg !== '') {
        $out .= "\n  " . '<meta property="og:image" content="' . h(url($ogDefImg)) . '">'
              . "\n  " . '<meta property="og:image:width" content="1200">'
              . "\n  " . '<meta property="og:image:height" content="630">'
              . "\n  " . '<meta name="twitter:image" content="' . h(url($ogDefImg)) . '">';
    }

    // ── Organization schema on every page ────────────────────────────────
    $orgSchema = json_encode([
        '@context'     => 'https://schema.org',
        '@type'        => 'Organization',
        'name'         => 'yassota',
        'url'          => SITE_URL,
        'logo'         => url('favicon.svg'),
        'description'  => 'دليل تحريري عربي مستقل لمراجعة تطبيقات وألعاب أندرويد',
        'contactPoint' => ['@type' => 'ContactPoint', 'contactType' => 'customer support', 'url' => url('contact')],
        'sameAs'       => [],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $out .= "\n  " . '<script type="application/ld+json">' . $orgSchema . '</script>';

    $out .= "\n  " . search_console_meta($pdo);
    return $out;
}

function ad_slot(): string {
    // The parent .ad-zone starts hidden (display:none, no reserved space)
    // — main.js reveals it only when AdSense sets data-ad-status="filled".
    // Slot ID is read from the admin settings (adsense_ad_slot_id).
    global $pdo;
    $slotId   = ($pdo instanceof PDO) ? trim(get_cfg($pdo, 'adsense_ad_slot_id', '')) : '';
    $slotAttr = $slotId ? ' data-ad-slot="' . h($slotId) . '"' : '';
    return '<ins class="adsbygoogle" style="display:block;width:100%"'
        . ' data-ad-client="ca-pub-5506877998492189"'
        . $slotAttr
        . ' data-ad-format="auto" data-full-width-responsive="true"></ins>'
        . '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
}
