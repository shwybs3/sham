<?php
/* ============================================================
   DarkStore — config.php
   إعدادات الموقع الأساسية — عدّل هذا الملف ببيانات استضافتك فقط
   ============================================================ */

define('SITE_NAME',    'DarkStore');
define('SITE_URL',     (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

// عنوان محفظة USDT الافتراضي (يمكن تغييره لاحقاً من لوحة التحكم ← الإعدادات ← الدفع)
define('WALLET_USDT', '0xYOUR_USDT_WALLET_HERE');

// ============================================================
// إعدادات قاعدة البيانات
// ============================================================
// DB_MODE:
//  'mysql'  → يتصل بقاعدة بيانات MySQL خارجية من لوحة cPanel (موصى به لـ InfinityFree)
//  'sqlite' → قاعدة محلية بملف واحد (تُستخدم تلقائياً كخطة طوارئ إن فشل الاتصال بـ MySQL)
define('DB_MODE', 'mysql');

// بيانات اتصال MySQL — خذها من: cPanel ← MySQL Databases / Remote MySQL
// ملاحظة مهمة: الـ Host غالباً ليس "localhost" بل شيء مثل sqlXXX.epizy.com
// واسم القاعدة والمستخدم عادة تُسبق تلقائياً بـ epiz_XXXXXXX_
define('DB_HOST', 'sqlXXX.epizy.com');          // ← غيّره من بيانات قاعدتك بالـ cPanel
define('DB_NAME', 'epiz_XXXXXXX_darkstore');    // ← اسم القاعدة كامل مع البادئة
define('DB_USER', 'epiz_XXXXXXX');              // ← اسم المستخدم كامل مع البادئة
define('DB_PASS', 'YOUR_DB_PASSWORD_HERE');     // ← كلمة مرور قاعدة البيانات

// قاعدة SQLite الاحتياطية (تُستخدم فقط لو تعذّر الاتصال بـ MySQL)
define('DB_FILE', __DIR__ . '/data/store.sqlite');

// مصدر الحقيقة الوحيد لحالة الاتصال الفعلية
$GLOBALS['__db_active_mode'] = null;

/* ============================================================
   جلسة آمنة
   ============================================================ */
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function db(): PDO {
    static $conn = null;
    if ($conn) return $conn;

    if (defined('DB_MODE') && DB_MODE === 'mysql') {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT            => 5,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            ]);
            $GLOBALS['__db_active_mode'] = 'mysql';
            return $conn;
        } catch (Throwable $e) {
            // فشل الاتصال بـ MySQL الخارجي → رجوع تلقائي لـ SQLite المحلي بدل تعطّل
            // الموقع بالكامل، مع تسجيل السبب لمراجعته لاحقاً
            error_log('[DB] فشل الاتصال بـ MySQL الخارجي: ' . $e->getMessage() . ' — تم التحويل لـ SQLite محلي مؤقتاً');
        }
    }

    @mkdir(dirname(DB_FILE), 0755, true);
    $conn = new PDO('sqlite:' . DB_FILE);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->exec('PRAGMA journal_mode=DELETE;');
    $conn->exec('PRAGMA busy_timeout=5000;');
    $GLOBALS['__db_active_mode'] = 'sqlite';
    return $conn;
}

function db_mode(): string {
    if ($GLOBALS['__db_active_mode'] === null) db();
    return $GLOBALS['__db_active_mode'] ?? 'sqlite';
}

function dbInsertIgnore(string $table, array $cols, array $values): void {
    $colsList = implode(',', $cols);
    $ph = implode(',', array_fill(0, count($cols), '?'));
    $sql = db_mode() === 'mysql'
        ? "INSERT IGNORE INTO $table($colsList) VALUES($ph)"
        : "INSERT OR IGNORE INTO $table($colsList) VALUES($ph)";
    db()->prepare($sql)->execute($values);
}

function dbNow(string $modifier = ''): string {
    if ($modifier === '') return db_mode() === 'mysql' ? 'NOW()' : "datetime('now')";
    if (preg_match('/^([+-]?\d+)\s+(\w+)/', trim($modifier), $m)) {
        $num  = (int)$m[1];
        $unit = strtoupper(rtrim($m[2], 's'));
        if (db_mode() === 'mysql') {
            $op = $num >= 0 ? 'DATE_ADD' : 'DATE_SUB';
            return "$op(NOW(), INTERVAL " . abs($num) . " $unit)";
        }
        return "datetime('now','" . $modifier . "')";
    }
    return db_mode() === 'mysql' ? 'NOW()' : "datetime('now')";
}

/* ============================================================
   مخطط قاعدة البيانات — يُنشأ وينصلح تلقائياً
   ============================================================ */
function install_db(): void {
    $db = db();
    $mysql = db_mode() === 'mysql';

    $engine = $mysql ? " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" : "";
    $pk     = $mysql ? "INT AUTO_INCREMENT PRIMARY KEY" : "INTEGER PRIMARY KEY AUTOINCREMENT";
    $dt     = $mysql ? "DATETIME DEFAULT CURRENT_TIMESTAMP" : "DATETIME DEFAULT CURRENT_TIMESTAMP";

    $db->exec("CREATE TABLE IF NOT EXISTS admins (
        id            $pk,
        username      VARCHAR(80) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at    $dt
    )$engine");

    $db->exec("CREATE TABLE IF NOT EXISTS products (
        id          $pk,
        slug        VARCHAR(191) UNIQUE NOT NULL,
        name_ar     VARCHAR(255) NOT NULL,
        name_en     VARCHAR(255) NOT NULL,
        price       DECIMAL(10,2) NOT NULL,
        category    VARCHAR(120) NOT NULL,
        icon        VARCHAR(60) DEFAULT 'fa-cube',
        color       VARCHAR(20) DEFAULT '#00f0ff',
        duration_ar VARCHAR(120) DEFAULT 'مدى الحياة',
        duration_en VARCHAR(120) DEFAULT 'Lifetime',
        rating      DECIMAL(3,1) DEFAULT 5.0,
        sales       INT DEFAULT 0,
        badge_ar    VARCHAR(120),
        badge_en    VARCHAR(120),
        badge_color VARCHAR(20) DEFAULT '#00f0ff',
        short_ar    TEXT NOT NULL,
        short_en    TEXT NOT NULL,
        long_ar     TEXT NOT NULL,
        long_en     TEXT NOT NULL,
        features_ar TEXT,
        features_en TEXT,
        delivery_ar VARCHAR(255) DEFAULT 'تسليم فوري عبر البريد الإلكتروني',
        delivery_en VARCHAR(255) DEFAULT 'Instant delivery via email',
        views       INT DEFAULT 0,
        featured    TINYINT DEFAULT 0,
        active      TINYINT DEFAULT 1,
        created_at  $dt
    )$engine");

    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id      $pk,
        name_ar VARCHAR(191) NOT NULL,
        name_en VARCHAR(191) NOT NULL,
        slug    VARCHAR(191) UNIQUE NOT NULL,
        icon    VARCHAR(60) DEFAULT 'fa-tag',
        color   VARCHAR(20) DEFAULT '#00f0ff'
    )$engine");

    $db->exec("CREATE TABLE IF NOT EXISTS orders (
        id           $pk,
        order_id     VARCHAR(50) UNIQUE NOT NULL,
        product_id   INT NOT NULL,
        product_slug VARCHAR(191) NOT NULL,
        amount       DECIMAL(10,2) NOT NULL,
        wallet       VARCHAR(255) NOT NULL,
        status       VARCHAR(20) DEFAULT 'pending',
        user_ip      VARCHAR(60),
        user_email   VARCHAR(191),
        tx_hash      VARCHAR(255),
        note         TEXT,
        created_at   $dt,
        expires_at   DATETIME NULL,
        paid_at      DATETIME NULL
    )$engine");

    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        k VARCHAR(191) PRIMARY KEY,
        v TEXT
    )$engine");

    $db->exec("CREATE TABLE IF NOT EXISTS refund_requests (
        id         $pk,
        order_id   VARCHAR(50) NOT NULL,
        email      VARCHAR(191) NOT NULL,
        reason     VARCHAR(255) NOT NULL,
        details    TEXT NOT NULL,
        status     VARCHAR(20) DEFAULT 'pending',
        created_at $dt
    )$engine");

    $db->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id         $pk,
        name       VARCHAR(150) NOT NULL,
        email      VARCHAR(191) NOT NULL,
        subject    VARCHAR(200),
        message    TEXT NOT NULL,
        status     VARCHAR(20) DEFAULT 'new',
        created_at $dt
    )$engine");

    // سجل محاولات لتحديد معدل الطلبات (حماية من الفلود والبوتات)
    $db->exec("CREATE TABLE IF NOT EXISTS rate_limits (
        id         $pk,
        action     VARCHAR(60) NOT NULL,
        ip_hash    VARCHAR(64) NOT NULL,
        created_at $dt
    )$engine");

    $db->exec("CREATE TABLE IF NOT EXISTS download_tokens (
        id         $pk,
        token      VARCHAR(64) UNIQUE NOT NULL,
        order_id   VARCHAR(50) NOT NULL,
        product_slug VARCHAR(191) NOT NULL,
        created_at $dt,
        expires_at DATETIME NOT NULL
    )$engine");

    $db->exec("CREATE TABLE IF NOT EXISTS reviews (
        id           $pk,
        product_slug VARCHAR(191) NOT NULL,
        name         VARCHAR(150) NOT NULL,
        rating       TINYINT NOT NULL DEFAULT 5,
        text         TEXT NOT NULL,
        ip_hash      VARCHAR(64),
        status       VARCHAR(20) DEFAULT 'pending',
        created_at   $dt
    )$engine");

    // ترقية الأعمدة لإصدارات قديمة (آمن على MySQL وSQLite)
    try { $db->query("SELECT download_url FROM products LIMIT 1"); }
    catch (Throwable) {
        $db->exec("ALTER TABLE products ADD COLUMN download_url VARCHAR(500) DEFAULT ''");
    }

    // ---- إعدادات افتراضية ----
    $defaults = [
        'site_name_ar'   => 'DarkStore',
        'site_name_en'   => 'DarkStore',
        'tagline_ar'     => 'متجر أدوات وخدمات برمجية رقمية احترافية',
        'tagline_en'     => 'Professional Digital Tools & Dev Services Store',
        'footer_ar'      => '© ' . date('Y') . ' DarkStore — جميع الحقوق محفوظة',
        'footer_en'      => '© ' . date('Y') . ' DarkStore — All rights reserved',
        'wallet_usdt'    => WALLET_USDT,
        'pay_timeout'    => '1200',
        'telegram'       => 'https://t.me/YourChannel',
        'contact_email'  => 'support@example.com',
        'currency'       => 'USDT',
        'maintenance'    => '0',
        'privacy_popup'  => '1',
        'cf_captcha'     => '0',
        'cf_site_key'    => '',
        'cf_secret_key'  => '',
        // إعدادات الحماية من الفلود والبوتات (قابلة للتعديل من لوحة التحكم)
        'rl_login_max'       => '5',
        'rl_login_window'    => '900',
        'rl_checkout_max'    => '6',
        'rl_checkout_window' => '600',
        'rl_contact_max'     => '4',
        'rl_contact_window'  => '600',
        'rl_download_max'    => '10',
        'rl_download_window' => '600',
        'rl_review_max'      => '3',
        'rl_review_window'   => '3600',
    ];
    foreach ($defaults as $k => $v) {
        dbInsertIgnore('settings', ['k', 'v'], [$k, $v]);
    }

    // ---- تصنيفات افتراضية ----
    $cats = [
        ['بوتات تيليغرام','Telegram Bots','telegram-bots','fa-paper-plane','#00f0ff'],
        ['تطبيقات ويب',   'Web Apps',     'web-apps',      'fa-globe',       '#7c3aed'],
        ['سكريبتات PHP',  'PHP Scripts',  'php-scripts',   'fa-code',        '#ff0055'],
        ['قوالب HTML',    'HTML Templates','html-templates','fa-layer-group', '#ffb800'],
        ['أدوات API',     'API Tools',    'api-tools',     'fa-plug',        '#00ff66'],
    ];
    foreach ($cats as [$nar, $nen, $slug, $icon, $color]) {
        dbInsertIgnore('categories', ['name_ar','name_en','slug','icon','color'], [$nar,$nen,$slug,$icon,$color]);
    }

    // ---- منتجات افتراضية شرعية (أدوات وخدمات برمجية من إنتاج المتجر نفسه) ----
    $products = [
        [
            'slug'=>'telegram-bot-basic','name_ar'=>'بوت تيليغرام أساسي','name_en'=>'Basic Telegram Bot',
            'price'=>25,'category'=>'Telegram Bots','icon'=>'fa-paper-plane','color'=>'#00f0ff',
            'duration_ar'=>'مدى الحياة','duration_en'=>'Lifetime','rating'=>4.9,'sales'=>312,
            'badge_ar'=>'الأكثر طلباً','badge_en'=>'Best Seller','badge_color'=>'#00f0ff',
            'short_ar'=>'بوت تيليغرام متكامل بلوحة تحكم سهلة — مفتوح المصدر',
            'short_en'=>'Full Telegram bot with easy admin panel — open source',
            'long_ar'=>'سكريبت احترافي لبناء بوت تيليغرام شامل مع لوحة تحكم إدارية، دعم الأوامر، الردود التلقائية، والتكامل مع APIs خارجية. يشمل الشفرة كاملة + توثيق + دعم التثبيت.',
            'long_en'=>'Professional script to build a full Telegram bot with admin panel, command support, auto-replies, and external API integration. Includes full source code + documentation + setup guide.',
            'features_ar'=>"✅ سكريبت كامل\n✅ لوحة تحكم إدارية\n✅ ردود تلقائية\n✅ دعم APIs\n✅ توثيق كامل\n✅ دعم التثبيت",
            'features_en'=>"✅ Full Source Script\n✅ Admin Control Panel\n✅ Auto-replies\n✅ API Integration\n✅ Full Documentation\n✅ Setup Support",
            'delivery_ar'=>'تسليم فوري — رابط تحميل على البريد الإلكتروني','delivery_en'=>'Instant delivery — download link via email','featured'=>1,
        ],
        [
            'slug'=>'php-store-script','name_ar'=>'سكريبت متجر PHP','name_en'=>'PHP Store Script',
            'price'=>40,'category'=>'PHP Scripts','icon'=>'fa-code','color'=>'#ff0055',
            'duration_ar'=>'مدى الحياة','duration_en'=>'Lifetime','rating'=>4.7,'sales'=>94,
            'badge_ar'=>'جديد','badge_en'=>'New','badge_color'=>'#ff0055',
            'short_ar'=>'سكريبت متجر رقمي PHP كامل مع لوحة إدارة',
            'short_en'=>'Full PHP digital store script with admin panel',
            'long_ar'=>'متجر رقمي احترافي مبني بـ PHP خالص. يشمل لوحة إدارة كاملة، إدارة المنتجات والطلبات، دفع كريبتو، صفحات السياسات، وأكثر. جاهز للرفع على استضافة مجانية.',
            'long_en'=>'Professional digital store built with pure PHP. Includes full admin panel, product & order management, crypto payments, policy pages, and more. Ready for free hosting.',
            'features_ar'=>"✅ PHP بدون frameworks\n✅ لوحة إدارة كاملة\n✅ دفع USDT\n✅ حماية CSRF وكابتشا\n✅ RTL + عربي + إنجليزي",
            'features_en'=>"✅ Pure PHP no frameworks\n✅ Full Admin Panel\n✅ USDT Payment\n✅ CSRF + Captcha Protection\n✅ RTL + Arabic + English",
            'delivery_ar'=>'تسليم فوري — رابط تحميل على البريد الإلكتروني','delivery_en'=>'Instant delivery — download link via email','featured'=>1,
        ],
        [
            'slug'=>'landing-page-template','name_ar'=>'قالب صفحة هبوط احترافي','name_en'=>'Pro Landing Page Template',
            'price'=>15,'category'=>'HTML Templates','icon'=>'fa-layer-group','color'=>'#ffb800',
            'duration_ar'=>'مدى الحياة','duration_en'=>'Lifetime','rating'=>4.6,'sales'=>210,
            'badge_ar'=>'','badge_en'=>'','badge_color'=>'#ffb800',
            'short_ar'=>'قالب HTML/CSS/JS احترافي لصفحات الهبوط — ثيم داكن',
            'short_en'=>'Pro HTML/CSS/JS landing page template — dark theme',
            'long_ar'=>'قالب صفحة هبوط مذهل بتصميم داكن عصري. يشمل أقسام: Hero، المميزات، التسعير، FAQ، نموذج تواصل. متجاوب بالكامل مع الجوال.',
            'long_en'=>'Stunning dark modern landing page template. Includes Hero, Features, Pricing, FAQ, Contact sections. Fully responsive.',
            'features_ar'=>"✅ تصميم داكن عصري\n✅ متجاوب مع الجوال\n✅ بدون مكتبات خارجية\n✅ سهل التعديل",
            'features_en'=>"✅ Modern Dark Design\n✅ Fully Responsive\n✅ No External Libraries\n✅ Easy to Edit",
            'delivery_ar'=>'تسليم فوري — رابط تحميل على البريد الإلكتروني','delivery_en'=>'Instant delivery — download link via email','featured'=>0,
        ],
        [
            'slug'=>'api-proxy-script','name_ar'=>'سكريبت API Proxy','name_en'=>'API Proxy Script',
            'price'=>30,'category'=>'API Tools','icon'=>'fa-plug','color'=>'#00ff66',
            'duration_ar'=>'مدى الحياة','duration_en'=>'Lifetime','rating'=>4.8,'sales'=>67,
            'badge_ar'=>'حصري','badge_en'=>'Exclusive','badge_color'=>'#00ff66',
            'short_ar'=>'بروكسي PHP لتوحيد APIs متعددة مع كاش وحماية',
            'short_en'=>'PHP proxy to unify multiple APIs with caching and protection',
            'long_ar'=>'سكريبت PHP لتوحيد عدة APIs في endpoint واحد. يشمل: كاش ذكي، rate limiting، إدارة API keys، حماية من الاستخدام الزائد.',
            'long_en'=>'PHP script to unify multiple APIs into one endpoint. Includes smart caching, rate limiting, API key management, abuse protection.',
            'features_ar'=>"✅ كاش ذكي\n✅ Rate Limiting\n✅ إدارة API Keys\n✅ حماية الاستخدام",
            'features_en'=>"✅ Smart Caching\n✅ Rate Limiting\n✅ API Key Management\n✅ Abuse Protection",
            'delivery_ar'=>'تسليم فوري — رابط تحميل على البريد الإلكتروني','delivery_en'=>'Instant delivery — download link via email','featured'=>0,
        ],
        [
            'slug'=>'dashboard-template','name_ar'=>'قالب لوحة تحكم ويب','name_en'=>'Web Dashboard Template',
            'price'=>20,'category'=>'HTML Templates','icon'=>'fa-gauge-high','color'=>'#7c3aed',
            'duration_ar'=>'مدى الحياة','duration_en'=>'Lifetime','rating'=>4.7,'sales'=>145,
            'badge_ar'=>'مميز','badge_en'=>'Featured','badge_color'=>'#7c3aed',
            'short_ar'=>'قالب لوحة تحكم HTML احترافية — ثيم داكن',
            'short_en'=>'Pro HTML admin dashboard template — dark theme',
            'long_ar'=>'قالب لوحة تحكم ويب متكامل بتصميم داكن عصري. يشمل: Sidebar، إحصائيات، جداول، رسوم بيانية، نماذج، وصفحات متعددة.',
            'long_en'=>'Full web admin dashboard template with modern dark design. Includes Sidebar, stats, tables, charts, forms, and multiple pages.',
            'features_ar'=>"✅ تصميم داكن احترافي\n✅ Sidebar متجاوبة\n✅ رسوم بيانية\n✅ جداول بيانات",
            'features_en'=>"✅ Professional Dark Design\n✅ Responsive Sidebar\n✅ Charts\n✅ Data Tables",
            'delivery_ar'=>'تسليم فوري — رابط تحميل على البريد الإلكتروني','delivery_en'=>'Instant delivery — download link via email','featured'=>1,
        ],
    ];

    foreach ($products as $p) {
        dbInsertIgnore('products',
            ['slug','name_ar','name_en','price','category','icon','color',
             'duration_ar','duration_en','rating','sales','badge_ar','badge_en','badge_color',
             'short_ar','short_en','long_ar','long_en','features_ar','features_en',
             'delivery_ar','delivery_en','featured'],
            [
                $p['slug'],$p['name_ar'],$p['name_en'],$p['price'],$p['category'],
                $p['icon'],$p['color'],$p['duration_ar'],$p['duration_en'],
                $p['rating'],$p['sales'],$p['badge_ar'],$p['badge_en'],$p['badge_color'],
                $p['short_ar'],$p['short_en'],$p['long_ar'],$p['long_en'],
                $p['features_ar'],$p['features_en'],$p['delivery_ar'],$p['delivery_en'],
                $p['featured'],
            ]
        );
    }
}

try {
    db()->query("SELECT 1 FROM products LIMIT 1");
} catch (Throwable $e) {
    // لا تحذف ملف SQLite هنا: db() يحتفظ باتصال PDO مفتوح ومخزَّن (static) — حذف
    // الملف تحت اتصال حي يكسر القراءة/الكتابة لاحقاً ("disk I/O error") على بعض
    // الأنظمة. الجداول تُنشأ بأمان بواسطة CREATE TABLE IF NOT EXISTS مهما كانت
    // حالة الملف الحالية.
    install_db();
}

/* ============================================================
   دوال مساعدة عامة
   ============================================================ */
function setting(string $k, string $default = ''): string {
    static $cache = [];
    if (isset($cache[$k])) return $cache[$k];
    $row = db()->prepare("SELECT v FROM settings WHERE k=?");
    $row->execute([$k]);
    $res = $row->fetch();
    return $cache[$k] = ($res ? $res['v'] : $default);
}
function setSetting(string $k, string $v): void {
    if (db_mode() === 'mysql') {
        db()->prepare("INSERT INTO settings(k,v) VALUES(?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)")->execute([$k, $v]);
    } else {
        db()->prepare("INSERT OR REPLACE INTO settings(k,v) VALUES(?,?)")->execute([$k, $v]);
    }
}
function isAdmin(): bool {
    return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
}
function requireAdmin(): void {
    if (!isAdmin()) { header('Location: admin.php'); exit; }
}
function clean(?string $s): string {
    return htmlspecialchars(trim($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function h(?string $s): string { return clean($s); }
function slug(string $s): string {
    return trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9-]/', '', strtolower($s))), '-');
}
function genOrderId(): string {
    return 'ORD-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
}
function getLang(): string {
    return $_SESSION['lang'] ?? 'ar';
}
function t(string $ar, string $en): string {
    return getLang() === 'ar' ? $ar : $en;
}
function siteUrl(string $path = ''): string {
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}
function products(bool $featuredOnly = false): array {
    $sql = "SELECT * FROM products WHERE active=1" . ($featuredOnly ? " AND featured=1" : "") . " ORDER BY featured DESC,sales DESC";
    return db()->query($sql)->fetchAll();
}
function productBySlug(string $slug): ?array {
    $s = db()->prepare("SELECT * FROM products WHERE slug=? AND active=1");
    $s->execute([$slug]);
    return $s->fetch() ?: null;
}
function allOrders(): array {
    return db()->query("SELECT o.*,p.name_ar,p.name_en FROM orders o LEFT JOIN products p ON o.product_slug=p.slug ORDER BY o.id DESC")->fetchAll();
}
function categoryList(): array {
    return db()->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
}
function allProducts(): array {
    return db()->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
}
function allRefundRequests(): array {
    return db()->query("SELECT * FROM refund_requests ORDER BY id DESC")->fetchAll();
}
function allContactMessages(): array {
    return db()->query("SELECT * FROM contact_messages ORDER BY id DESC")->fetchAll();
}
function productReviews(string $slug): array {
    $s = db()->prepare("SELECT * FROM reviews WHERE product_slug=? AND status='approved' ORDER BY id DESC LIMIT 20");
    $s->execute([$slug]);
    return $s->fetchAll();
}
function allReviews(): array {
    return db()->query("SELECT r.*, p.name_ar, p.name_en FROM reviews r LEFT JOIN products p ON r.product_slug=p.slug ORDER BY r.id DESC")->fetchAll();
}
function pendingReviewCount(): int {
    return (int)db()->query("SELECT COUNT(*) c FROM reviews WHERE status='pending'")->fetch()['c'];
}

/* ============================================================
   CSRF — يُستخدم في كل نموذج POST (تسجيل الدخول، الدفع،
   التواصل، الإعدادات...) لمنع تزوير الطلبات عبر مواقع أخرى
   ============================================================ */
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrfField(): void {
    echo '<input type="hidden" name="csrf" value="' . clean(csrfToken()) . '">';
}
function csrfCheck(): bool {
    $token = $_POST['csrf'] ?? '';
    return $token !== '' && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/* ============================================================
   الحماية من الفلود والبوتات — تحديد معدل الطلبات لكل IP
   يُستخدم عند: تسجيل دخول الأدمن، تأكيد الدفع، نموذج التواصل
   القيم قابلة للتعديل من لوحة التحكم ← الإعدادات ← الحماية
   ============================================================ */
function clientIpHash(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return hash('sha256', $ip);
}
// يسجّل محاولة جديدة ويتحقق مما إذا تم تجاوز الحد المسموح خلال النافذة الزمنية
function rateLimitHit(string $action, int $max, int $windowSeconds): bool {
    $ipHash = clientIpHash();
    $db = db();
    // تنظيف السجلات القديمة لهذا الإجراء (يمنع تضخم الجدول على الاستضافة المجانية)
    $db->prepare("DELETE FROM rate_limits WHERE action=? AND created_at < " . dbNow('-1 day'))->execute([$action]);

    $stmt = $db->prepare("SELECT COUNT(*) c FROM rate_limits WHERE action=? AND ip_hash=? AND created_at > " . dbNow('-' . $windowSeconds . ' seconds'));
    $stmt->execute([$action, $ipHash]);
    $count = (int)$stmt->fetch()['c'];

    if ($count >= $max) return false; // تم تجاوز الحد — رفض الطلب (حماية من الفلود/البوتات)

    $db->prepare("INSERT INTO rate_limits(action, ip_hash) VALUES(?,?)")->execute([$action, $ipHash]);
    return true;
}
function rateLimited(string $action): bool {
    $max    = (int)setting('rl_' . $action . '_max', '5');
    $window = (int)setting('rl_' . $action . '_window', '600');
    return !rateLimitHit($action, $max, $window);
}

/* ============================================================
   Cloudflare Turnstile (CAPTCHA) — عرض + تحقق سيرفر
   ============================================================ */
function captchaEnabled(): bool {
    return setting('cf_captcha') === '1' && setting('cf_site_key') !== '' && setting('cf_secret_key') !== '';
}
function renderCaptcha(): void {
    if (!captchaEnabled()) return;
    static $scriptPrinted = false;
    $siteKey = clean(setting('cf_site_key'));
    ?>
    <div class="captcha-wrap">
      <div class="cf-turnstile"
           data-sitekey="<?= $siteKey ?>"
           data-theme="dark"
           data-size="flexible"
           data-language="<?= getLang() ?>"></div>
    </div>
    <?php if (!$scriptPrinted): $scriptPrinted = true; ?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif;
}
function verifyCaptcha(): bool {
    if (!captchaEnabled()) return true;
    $token = $_POST['cf-turnstile-response'] ?? '';
    if ($token === '') return false;

    $postFields = http_build_query([
        'secret'   => setting('cf_secret_key'),
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $res = false;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
    }
    if ($res === false && ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $postFields,
            'timeout' => 6,
        ]]);
        $res = @file_get_contents($url, false, $ctx);
    }
    if ($res === false) {
        error_log('[Turnstile] تعذّر الاتصال بسيرفر Cloudflare للتحقق — تم تجاوز الفحص مؤقتاً');
        return true;
    }
    $json = json_decode($res, true);
    return !empty($json['success']);
}

/* ============================================================
   Download Tokens — رموز تحميل مؤقتة بعد التحقق بالكابتشا
   ============================================================ */
function genDownloadToken(string $orderId, string $productSlug): string {
    $token = bin2hex(random_bytes(32));
    $db = db();
    $db->prepare("DELETE FROM download_tokens WHERE order_id=? OR expires_at < " . dbNow())->execute([$orderId]);
    $db->prepare("INSERT INTO download_tokens(token,order_id,product_slug,expires_at) VALUES(?,?,?," . dbNow('+30 minutes') . ")")->execute([$token, $orderId, $productSlug]);
    return $token;
}
function validateDownloadToken(string $token): ?array {
    if (strlen($token) < 10) return null;
    $s = db()->prepare("SELECT dt.*, o.status as order_status, p.download_url, p.name_ar, p.name_en FROM download_tokens dt LEFT JOIN orders o ON dt.order_id=o.order_id LEFT JOIN products p ON dt.product_slug=p.slug WHERE dt.token=? AND dt.expires_at > " . dbNow());
    $s->execute([$token]);
    return $s->fetch() ?: null;
}

/* ============================================================
   إدارة حساب الأدمن (كلمة مرور مشفّرة — لا تُخزَّن نصاً صريحاً أبداً)
   ============================================================ */
function adminExists(): bool {
    return (int)db()->query("SELECT COUNT(*) c FROM admins")->fetch()['c'] > 0;
}
function verifyAdminLogin(string $username, string $password): bool {
    $s = db()->prepare("SELECT * FROM admins WHERE username=?");
    $s->execute([$username]);
    $row = $s->fetch();
    return $row && password_verify($password, $row['password_hash']);
}
function createOrUpdateAdmin(string $username, string $password): void {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if (db_mode() === 'mysql') {
        db()->prepare("INSERT INTO admins(username,password_hash) VALUES(?,?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)")->execute([$username, $hash]);
    } else {
        db()->prepare("INSERT OR REPLACE INTO admins(username,password_hash) VALUES(?,?)")->execute([$username, $hash]);
    }
}
