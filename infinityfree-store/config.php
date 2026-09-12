<?php
/* ═══════════════════════════════════════════════
   متجر يسوتا — config.php
   نسخة InfinityFree المجانية. لا تُكتب بيانات الاتصال
   الحقيقية هنا — المعالج ينشئ config.local.php المستبعد
   من git عند أول زيارة.
   ═══════════════════════════════════════════════ */

if (!defined('IFS_CONFIG_LOADED')) define('IFS_CONFIG_LOADED', true);
define('IFS_ROOT', __DIR__);

date_default_timezone_set('Asia/Riyadh');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
if (session_status() === PHP_SESSION_NONE) session_start();

$__local = IFS_ROOT . '/config.local.php';
if (file_exists($__local)) require $__local;

if (!defined('DB_HOST')) {
    ifs_setup_wizard();
    exit;
}

define('UPLOAD_PATH', IFS_ROOT . '/uploads');

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

/* ═══ المخطط الذاتي الإصلاح ═══ */
function ifs_add_col(PDO $pdo, string $table, string $col, string $def): void {
    try {
        $q = $pdo->prepare("SELECT COUNT(*) c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
        $q->execute([$table, $col]);
        if ((int)$q->fetch()['c'] === 0) $pdo->exec("ALTER TABLE `$table` ADD COLUMN $def");
    } catch (Throwable $e) { /* تجاهل — الجدول جديد أصلاً */ }
}

function ifs_ensure_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
      k VARCHAR(80) PRIMARY KEY, v TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS packages (
      id INT AUTO_INCREMENT PRIMARY KEY,
      category VARCHAR(60) NOT NULL DEFAULT 'عام',
      icon VARCHAR(40) NOT NULL DEFAULT 'bolt',
      slug VARCHAR(190) NOT NULL DEFAULT '',
      name VARCHAR(190) NOT NULL,
      description VARCHAR(400) NOT NULL DEFAULT '',
      long_description TEXT,
      features TEXT,
      delivery VARCHAR(80) NOT NULL DEFAULT 'خلال 0-24 ساعة',
      rating DECIMAL(2,1) NOT NULL DEFAULT 4.8,
      sales INT NOT NULL DEFAULT 0,
      price_usd DECIMAL(10,2) NOT NULL DEFAULT 0,
      active TINYINT NOT NULL DEFAULT 1,
      featured TINYINT NOT NULL DEFAULT 0,
      sort_order INT NOT NULL DEFAULT 0,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* ترقية جداول قديمة إن وُجدت */
    foreach ([
        ['icon', "icon VARCHAR(40) NOT NULL DEFAULT 'bolt'"],
        ['slug', "slug VARCHAR(190) NOT NULL DEFAULT ''"],
        ['long_description', "long_description TEXT"],
        ['features', "features TEXT"],
        ['delivery', "delivery VARCHAR(80) NOT NULL DEFAULT 'خلال 0-24 ساعة'"],
        ['rating', "rating DECIMAL(2,1) NOT NULL DEFAULT 4.8"],
        ['sales', "sales INT NOT NULL DEFAULT 0"],
        ['featured', "featured TINYINT NOT NULL DEFAULT 0"],
    ] as $c) ifs_add_col($pdo, 'packages', $c[0], $c[1]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
      id INT AUTO_INCREMENT PRIMARY KEY,
      order_id VARCHAR(64) NOT NULL UNIQUE,
      package_id INT NULL,
      package_name VARCHAR(190) NOT NULL DEFAULT '',
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
        ifs_seed_products($pdo);
    }

    $defaults = [
        'site_title' => 'متجر يسوتا',
        'site_tagline' => 'أقوى متجر خدمات رقمية عربي',
        'site_desc'  => 'زيادة متابعين انستقرام وتيك توك ويوتيوب وفيسبوك، تيليجرام بريميوم، وأدوات أمن سيبراني احترافية — أكثر من 100 خدمة رقمية بأسعار منافسة، دفع فوري وآمن بالعملات الرقمية وتسليم سريع.',
        'site_keywords' => 'زيادة متابعين, متابعين انستقرام, متابعين تيك توك, مشتركين يوتيوب, تيليجرام بريميوم, أدوات أمن سيبراني, رشق متابعين, SMM عربي',
        'contact'    => '',
        'nowpayments_api_key' => '',
        'nowpayments_ipn_secret' => '',
        'openrouter_api_key' => '',
    ];
    $existing = $pdo->query("SELECT k FROM settings")->fetchAll(PDO::FETCH_COLUMN);
    $ins = $pdo->prepare("INSERT INTO settings (k, v) VALUES (?, ?)");
    foreach ($defaults as $k => $v) if (!in_array($k, $existing, true)) $ins->execute([$k, $v]);
}

/* ═══ توليد أكثر من 100 منتج احترافي ═══ */
function ifs_seed_products(PDO $pdo): void {
    // كل خدمة: [الفئة, الأيقونة, مفتاح-السلاق, الاسم, الوحدة, الوصف القصير, [ميزات], [[كمية, سعر], ...]]
    $S = [
        ['انستقرام','instagram','instagram-followers-ar','متابعين انستقرام عرب','متابع','متابعون عرب حقيقيون متفاعلون بجودة عالية وبدء فوري.',['بدون كلمة مرور','بدء خلال دقائق','تعويض عن النقص','آمن 100%'],[[1000,3.50],[5000,15.00],[10000,28.00]]],
        ['انستقرام','instagram','instagram-followers-global','متابعين انستقرام عالميين','متابع','متابعون عالميون بأسرع تسليم وأفضل سعر في السوق.',['تسليم سريع','ثبات عالٍ','دعم متواصل'],[[1000,2.20],[5000,9.50],[10000,17.00]]],
        ['انستقرام','instagram','instagram-likes','لايكات انستقرام','لايك','لايكات فورية ترفع تفاعل منشورك وتصدّره في الاكسبلور.',['بدء فوري','توزيع طبيعي','بدون هبوط'],[[1000,1.20],[5000,5.00],[10000,9.00]]],
        ['انستقرام','instagram','instagram-views','مشاهدات ريلز انستقرام','مشاهدة','مشاهدات ريلز وفيديو ترفع انتشارك خوارزمياً.',['سرعة عالية','آمن تماماً'],[[5000,1.00],[20000,3.50],[50000,8.00]]],
        ['انستقرام','instagram','instagram-comments','تعليقات انستقرام عربية','تعليق','تعليقات عربية إيجابية مخصصة تعزز مصداقية حسابك.',['نصوص عربية','تخصيص متاح'],[[50,3.00],[100,5.50],[250,12.00]]],
        ['انستقرام','instagram','instagram-story-views','مشاهدات ستوري انستقرام','مشاهدة','مشاهدات ستوري تصل لكل القصص النشطة تلقائياً.',['تلقائي','فوري'],[[1000,1.50],[5000,6.00]]],

        ['تيك توك','tiktok','tiktok-followers','متابعين تيك توك','متابع','متابعون حقيقيون يقوّون حسابك ويفتحون ميزات المنصة.',['بدون كلمة مرور','بدء سريع','تعويض'],[[1000,3.00],[5000,13.00],[10000,24.00]]],
        ['تيك توك','tiktok','tiktok-likes','لايكات تيك توك','لايك','لايكات ترفع فيديوهاتك في صفحة For You.',['بدء فوري','ثبات'],[[1000,1.00],[5000,4.50],[10000,8.00]]],
        ['تيك توك','tiktok','tiktok-views','مشاهدات تيك توك','مشاهدة','مشاهدات فورية بأرخص سعر لتصدّر الترند.',['رخيص جداً','فوري'],[[10000,0.80],[50000,3.00],[100000,5.50]]],
        ['تيك توك','tiktok','tiktok-shares','مشاركات تيك توك','مشاركة','مشاركات ترفع معدل انتشار الفيديو خوارزمياً.',['طبيعي','آمن'],[[1000,1.20],[5000,5.00]]],
        ['تيك توك','tiktok','tiktok-live-views','مشاهدات بث تيك توك المباشر','مشاهدة','رفع عدد مشاهدي البث المباشر لحظياً.',['لحظي','مستقر'],[[100,2.50],[500,10.00]]],

        ['يوتيوب','youtube','youtube-subscribers','مشتركين يوتيوب','مشترك','مشتركون حقيقيون تدريجيون آمنون لشروط التحقيق.',['متوافق مع الشروط','تدريجي','تعويض'],[[100,5.50],[500,24.00],[1000,45.00]]],
        ['يوتيوب','youtube','youtube-views','مشاهدات يوتيوب','مشاهدة','مشاهدات آمنة تدريجية من مصادر حقيقية.',['آمن للقناة','احتساب في الأرباح'],[[1000,2.00],[10000,17.00],[50000,75.00]]],
        ['يوتيوب','youtube','youtube-watchhours','ساعات مشاهدة يوتيوب','ساعة','ساعات مشاهدة لإكمال شرط تحقيق الدخل (4000 ساعة).',['من فيديو طويل','آمن'],[[1000,45.00],[4000,160.00]]],
        ['يوتيوب','youtube','youtube-likes','لايكات يوتيوب','لايك','لايكات ترفع تقييم الفيديو وترتيبه.',['فوري','ثابت'],[[500,2.50],[1000,4.50],[5000,20.00]]],
        ['يوتيوب','youtube','youtube-comments','تعليقات يوتيوب عربية','تعليق','تعليقات عربية واقعية تزيد تفاعل القناة.',['عربي','مخصص'],[[50,4.00],[100,7.50]]],

        ['فيسبوك','facebook','facebook-page-followers','متابعين صفحة فيسبوك','متابع','متابعون لصفحتك أو بروفايلك بجودة عالية وثبات.',['صفحة أو بروفايل','ثبات عالٍ'],[[1000,3.80],[5000,17.00],[10000,32.00]]],
        ['فيسبوك','facebook','facebook-post-likes','لايكات منشور فيسبوك','لايك','تفاعلات ولايكات فورية لأي منشور عام.',['فوري','أنواع تفاعل'],[[500,2.00],[1000,3.50],[5000,15.00]]],
        ['فيسبوك','facebook','facebook-group-members','أعضاء مجموعة فيسبوك','عضو','توسيع مجتمعك بأعضاء حقيقيين لمجموعتك.',['حقيقي','آمن'],[[1000,5.00],[5000,22.00]]],
        ['فيسبوك','facebook','facebook-video-views','مشاهدات فيديو فيسبوك','مشاهدة','مشاهدات فيديو ترفع وصول محتواك.',['سريع','رخيص'],[[5000,1.50],[20000,5.50]]],

        ['تويتر / X','twitter','x-followers','متابعين تويتر X','متابع','متابعون لحساب X يعززون حضورك ومصداقيتك.',['بدون كلمة مرور','ثبات'],[[1000,4.50],[5000,20.00],[10000,38.00]]],
        ['تويتر / X','twitter','x-likes','إعجابات تويتر X','إعجاب','إعجابات فورية لتغريداتك ترفع تفاعلها.',['فوري','آمن'],[[500,2.50],[1000,4.50]]],
        ['تويتر / X','twitter','x-retweets','إعادات نشر تويتر X','ريتويت','ريتويت يوسّع انتشار تغريدتك بسرعة.',['طبيعي','سريع'],[[500,3.00],[1000,5.50]]],
        ['تويتر / X','twitter','x-views','مشاهدات تويتر X','مشاهدة','رفع عدد مشاهدات التغريدة (Impressions).',['رخيص','فوري'],[[10000,1.20],[50000,5.00]]],

        ['سناب شات','snapchat','snapchat-followers','متابعين سناب شات','متابع','متابعون لحسابك على سناب شات لزيادة وصولك.',['حقيقي','آمن'],[[1000,5.50],[5000,25.00]]],
        ['سناب شات','snapchat','snapchat-views','مشاهدات سناب شات','مشاهدة','رفع مشاهدات القصص لتصدّر محتواك.',['فوري','مستقر'],[[1000,3.00],[5000,13.00]]],

        ['تيليجرام','telegram','telegram-premium','تيليجرام بريميوم','اشتراك','تفعيل Telegram Premium الرسمي لحسابك بالكامل.',['رسمي','تفعيل مباشر','بالمعرف فقط'],[[1,7.50],[3,20.00],[12,65.00]]],
        ['تيليجرام','telegram','telegram-members','أعضاء قناة تيليجرام','عضو','أعضاء حقيقيون لقناتك أو مجموعتك بثبات عالٍ.',['بدون هبوط','آمن'],[[1000,3.20],[5000,14.00],[10000,26.00]]],
        ['تيليجرام','telegram','telegram-views','مشاهدات منشور تيليجرام','مشاهدة','مشاهدات لآخر المنشورات ترفع مصداقية القناة.',['فوري','رخيص'],[[1000,0.60],[10000,4.50]]],
        ['تيليجرام','telegram','telegram-reactions','تفاعلات تيليجرام','تفاعل','تفاعلات إيموجي إيجابية لمنشوراتك.',['أنواع متعددة','فوري'],[[500,1.50],[1000,2.80]]],

        ['واتساب وأخرى','whatsapp','whatsapp-channel','متابعين قناة واتساب','متابع','متابعون لقناتك على واتساب لتوسيع جمهورك.',['حقيقي','آمن'],[[1000,4.00],[5000,18.00]]],
        ['واتساب وأخرى','spotify','spotify-plays','استماعات سبوتيفاي','تشغيل','تشغيلات لأغانيك ترفع ترتيبك على سبوتيفاي.',['من حسابات حقيقية','آمن'],[[1000,2.50],[10000,20.00]]],
        ['واتساب وأخرى','discord','discord-members','أعضاء سيرفر ديسكورد','عضو','توسيع مجتمع سيرفرك بأعضاء نشطين.',['أونلاين','آمن'],[[500,4.00],[1000,7.00]]],
        ['واتساب وأخرى','twitch','twitch-followers','متابعين تويتش','متابع','متابعون لقناة البث لديك على Twitch.',['حقيقي','ثبات'],[[500,3.50],[1000,6.50]]],

        ['أمن سيبراني','shield','cyber-website-scan','فحص أمني شامل لموقعك','خدمة','فحص ثغرات احترافي (OWASP) مع تقرير وتوصيات معالجة.',['تقرير PDF','توصيات عملية','خلال 48 ساعة'],[[1,15.00],[1,35.00]]],
        ['أمن سيبراني','lock','cyber-account-protection','استشارة حماية الحسابات','استشارة','تأمين حساباتك: التحقق الثنائي، كلمات المرور، فحص التسريبات.',['جلسة مباشرة','خطة تأمين'],[[1,10.00]]],
        ['أمن سيبراني','search','cyber-osint-report','تقرير OSINT للسمعة الرقمية','تقرير','ما الذي يكشفه الإنترنت عنك أو عن علامتك، مع خطة تقليل الأثر.',['بحث مفتوح المصدر','سري تماماً'],[[1,20.00],[1,45.00]]],
        ['أمن سيبراني','shield','cyber-malware-removal','إزالة برمجيات خبيثة من موقع','خدمة','تنظيف موقع مصاب واستعادته وتحصينه ضد العودة.',['تنظيف كامل','تحصين','خلال 72 ساعة'],[[1,30.00]]],
        ['أمن سيبراني','lock','cyber-2fa-setup','إعداد التحقق الثنائي الاحترافي','خدمة','تفعيل 2FA ومفاتيح الاسترجاع لكل حساباتك المهمة.',['خطوة بخطوة','نسخ احتياطي آمن'],[[1,8.00]]],
        ['أمن سيبراني','search','cyber-darkweb-monitor','مراقبة تسريبات الدارك ويب','اشتراك','تنبيهك فور ظهور بياناتك في تسريبات معروفة.',['تنبيهات','تقرير شهري'],[[1,12.00],[3,30.00]]],
        ['أمن سيبراني','shield','cyber-pentest-basic','اختبار اختراق مبدئي لتطبيق','خدمة','اختبار اختراق موجّه لتطبيق ويب صغير مع إثبات المفهوم.',['PoC','تقرير تنفيذي','أخلاقي بإذنك'],[[1,60.00]]],
        ['أمن سيبراني','lock','cyber-phishing-awareness','تدريب التوعية ضد التصيّد','دورة','دورة عملية لفريقك للتعرف على هجمات التصيّد وتفاديها.',['أمثلة واقعية','شهادة حضور'],[[1,25.00]]],

        ['خدمات احترافية','bolt','pro-logo-design','تصميم شعار احترافي','تصميم','هوية بصرية وشعار احترافي بصيغ متعددة جاهزة للطباعة والويب.',['3 مفاهيم','ملفات مصدرية','تعديلات مجانية'],[[1,20.00],[1,45.00]]],
        ['خدمات احترافية','search','pro-seo-audit','تدقيق SEO لموقعك','تقرير','تحليل ظهورك في البحث مع خطة تحسين عملية للكلمات المفتاحية.',['تقرير مفصّل','خطة 30 يوم'],[[1,18.00],[1,40.00]]],
        ['خدمات احترافية','bolt','pro-video-edit','مونتاج فيديو ريلز احترافي','فيديو','مونتاج فيديو قصير جذاب للريلز والتيك توك مع مؤثرات وترند.',['حتى 60 ثانية','تسليم سريع'],[[1,12.00],[1,25.00]]],
    ];

    $ins = $pdo->prepare("INSERT INTO packages (category, icon, slug, name, description, long_description, features, delivery, rating, sales, price_usd, featured, sort_order)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $order = 0;
    foreach ($S as $svc) {
        [$cat, $icon, $key, $baseName, $unit, $short, $features, $tiers] = $svc;
        foreach ($tiers as $ti => $tier) {
            [$qty, $price] = $tier;
            $qtyLabel = $qty >= 1000 ? (fmod($qty, 1000) === 0.0 ? ($qty / 1000) . 'K' : number_format($qty)) : (string)$qty;
            // اسم مميز لكل باقة
            if ($unit === 'خدمة' || $unit === 'استشارة' || $unit === 'تقرير' || $unit === 'دورة' || $unit === 'تصميم' || $unit === 'فيديو') {
                $name = $baseName . ($ti > 0 ? ' — الباقة المتقدمة' : ' — الباقة الأساسية');
                $slug = $key . '-' . ($ti > 0 ? 'pro' : 'basic');
                $qtyForOrder = 1;
                $unitTxt = '';
            } elseif ($unit === 'اشتراك') {
                $name = $baseName . ' — ' . $qty . ' شهر';
                $slug = $key . '-' . $qty . 'm';
                $qtyForOrder = 1;
                $unitTxt = $qty . ' شهر';
            } else {
                $name = $baseName . ' — ' . $qtyLabel . ' ' . $unit;
                $slug = $key . '-' . $qty;
                $qtyForOrder = 1;
                $unitTxt = number_format($qty) . ' ' . $unit;
            }
            $long = $short . ' ' . ($unitTxt !== '' ? "تشمل هذه الباقة $unitTxt. " : '')
                . 'نبدأ التنفيذ تلقائياً بعد تأكيد الدفع، ونوفّر دعماً مباشراً ومتابعة حتى اكتمال طلبك بالكامل. جميع خدماتنا آمنة ولا تتطلب كلمة مرور حسابك أبداً.';
            $rating = 4.6 + (($ti + strlen($key)) % 4) / 10; // 4.6 - 4.9
            $sales = 120 + (strlen($key) * 37 + $qty % 900);
            $featured = ($order % 9 === 0) ? 1 : 0;
            $ins->execute([$cat, $icon, $slug, $name, $short, $long, json_encode($features, JSON_UNESCAPED_UNICODE), 'خلال 0-24 ساعة', $rating, $sales, $price, $featured, $order++]);
        }
    }
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
function product_url(array $p): string { return site_url('product.php?slug=' . urlencode($p['slug'])); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}
function is_admin(): bool { return !empty($_SESSION['ifs_admin']); }

/* ═══ مجموعة أيقونات SVG حديثة (بلا اعتماديات) ═══ */
function icon(string $name, int $size = 24): string {
    $p = [
        'instagram' => '<rect x="2" y="2" width="20" height="20" rx="6"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none"/>',
        'tiktok' => '<path d="M16 4c.5 2.5 2 4 4.5 4.3v3c-1.8 0-3.4-.6-4.5-1.5V15a5.5 5.5 0 1 1-5.5-5.5c.4 0 .7 0 1 .1v3.1a2.6 2.6 0 1 0 1.5 2.4V4H16z"/>',
        'youtube' => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="M10 9l5 3-5 3z" fill="currentColor" stroke="none"/>',
        'facebook' => '<path d="M14 8h2V5h-2a3 3 0 0 0-3 3v2H9v3h2v6h3v-6h2.2l.8-3H14V8.5c0-.4.2-.5.6-.5H14z"/>',
        'twitter' => '<path d="M4 4l6.5 8.5L4.5 20H7l4.7-5 3.8 5H20l-6.8-9L19.5 4H17l-4.3 4.6L9.2 4H4z" fill="currentColor" stroke="none"/>',
        'snapchat' => '<path d="M12 3c2.5 0 4 2 4 4.2 0 1 .2 1.8.8 2.3.4.3 1 .3 1.4.5.3.2.2.7-.2.9-.6.3-1.4.3-1.6.8-.2.5.4 1.3 1 1.9-1 .8-1.8.6-2.4 1-.4.3-.5 1-1 1.2-.6.2-1.3-.4-2.2-.4s-1.6.6-2.2.4c-.5-.2-.6-.9-1-1.2-.6-.4-1.4-.2-2.4-1 .6-.6 1.2-1.4 1-1.9-.2-.5-1-.5-1.6-.8-.4-.2-.5-.7-.2-.9.4-.2 1-.2 1.4-.5.6-.5.8-1.3.8-2.3C8 5 9.5 3 12 3z"/>',
        'telegram' => '<path d="M21 5L3 12l5 2 2 5 3-3 4 3 4-14z"/><path d="M8 14l9-6-6 7" fill="none"/>',
        'whatsapp' => '<path d="M4 20l1.5-4A8 8 0 1 1 9 19.5L4 20z"/><path d="M9 9c0 3 3 6 6 6 .8 0 1.3-.8 1-1.4l-1.3-.8-1 1c-1-.4-2.1-1.5-2.5-2.5l1-1-.8-1.3C10.8 7.7 10 8.2 10 9" fill="currentColor" stroke="none"/>',
        'spotify' => '<circle cx="12" cy="12" r="9"/><path d="M8 10c3-.8 6-.4 8 1M8 13c2.5-.6 4.8-.2 6.5 1M8.5 16c2-.4 3.6-.1 4.8.8" fill="none"/>',
        'discord' => '<path d="M7 8a13 13 0 0 1 10 0M7 16a13 13 0 0 0 10 0M6 8c-1 3-1 6 0 9M18 8c1 3 1 6 0 9"/><circle cx="9.5" cy="13" r="1.2" fill="currentColor" stroke="none"/><circle cx="14.5" cy="13" r="1.2" fill="currentColor" stroke="none"/>',
        'twitch' => '<path d="M4 4h16v10l-4 4h-4l-3 3v-3H4z"/><path d="M12 8v4M16 8v4" fill="none"/>',
        'shield' => '<path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z"/><path d="M9 12l2 2 4-4" fill="none"/>',
        'lock' => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3" fill="none"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M16.5 16.5L21 21" fill="none"/>',
        'bolt' => '<path d="M13 2L4 14h6l-1 8 9-12h-6z"/>',
        'cart' => '<circle cx="9" cy="20" r="1.5" fill="currentColor" stroke="none"/><circle cx="18" cy="20" r="1.5" fill="currentColor" stroke="none"/><path d="M3 4h2l2 12h11l2-8H6" fill="none"/>',
        'check' => '<path d="M4 12l5 5L20 6" fill="none"/>',
        'star' => '<path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.8 6.4 20.2l1.1-6.2L3 9.6l6.2-.9z"/>',
        'shield-check' => '<path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z"/><path d="M9 12l2 2 4-4" fill="none"/>',
        'rocket' => '<path d="M5 15c-1 1-1 4-1 4s3 0 4-1M14 4c3 0 6 3 6 6 0 3-5 8-8 9l-3-3c1-3 6-8 9-8" fill="none"/><circle cx="15" cy="9" r="1.5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2" fill="none"/>',
        'headset' => '<path d="M4 13v-1a8 8 0 0 1 16 0v1" fill="none"/><rect x="3" y="13" width="4" height="6" rx="1.5"/><rect x="17" y="13" width="4" height="6" rx="1.5"/><path d="M20 19a4 4 0 0 1-4 3h-2" fill="none"/>',
    ];
    $body = $p[$name] ?? $p['bolt'];
    return '<svg class="ic" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}

/* ═══ NOWPayments ═══ */
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

/* ═══ OpenRouter (روبوت الدردشة — نماذج مجانية) ═══ */
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

/* ═══ معالج الإعداد الأولي ═══ */
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
    body{margin:0;font-family:Tahoma,Arial,sans-serif;background:#0a0a0c;color:#e2e8f0;padding:40px 16px}
    .box{max-width:480px;margin:0 auto;background:#141417;border:1px solid #2a1416;border-radius:16px;padding:28px;box-shadow:0 10px 40px rgba(255,0,51,.15)}
    h1{font-size:20px;margin:0 0 6px;color:#fff} p.sub{color:#8a8a92;font-size:13px;margin:0 0 22px;line-height:1.8}
    label{display:block;font-size:13px;margin:14px 0 5px;color:#cbd5e1}
    input{width:100%;box-sizing:border-box;padding:10px 12px;border-radius:8px;border:1px solid #2a1416;background:#000;color:#fff;font-size:14px}
    button{width:100%;margin-top:22px;padding:12px;border:0;border-radius:10px;background:#e10600;color:#fff;font-weight:bold;font-size:15px;cursor:pointer}
    .err{background:#1a0507;border:1px solid #8a0018;color:#ff6b81;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px}
    hr{border:0;border-top:1px solid #2a1416;margin:20px 0}
    </style></head><body><div class="box">
    <h1>🛒 إعداد المتجر</h1>
    <p class="sub">أدخل بيانات قاعدة بيانات MySQL من لوحة InfinityFree (MySQL Databases). لن تُحفظ هذه البيانات في مستودع الأكواد — تُكتب فقط في ملف محلي على استضافتك.</p>
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
      <label>اسم مستخدم المدير</label>
      <input name="admin_user" value="admin" required>
      <label>كلمة مرور المدير</label>
      <input name="admin_pass" type="password" minlength="6" required>
      <button type="submit">إنشاء المتجر</button>
    </form>
    </div></body></html><?php
}
