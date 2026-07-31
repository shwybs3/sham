<?php
require_once __DIR__ . '/config.php';

// ── Routing ───────────────────────────────────────────
$uri = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$uri = preg_replace('#^index\.php/?#', '', $uri);
if ($uri === '') $uri = 'home';

// Segment routing
$seg = explode('/', $uri, 3);
$page = $seg[0]; $sub1 = $seg[1] ?? ''; $sub2 = $seg[2] ?? '';

// Route table: page → [file, title, desc, keywords, schema_type]
$routes = [
  'home'        => ['pages/home.php',        SITE_NAME.' — أسعار الصرف والذهب وأدوات PDF وحاسبات البناء',
    'موقع شامل لأسعار الصرف اليومية وأسعار الذهب وأدوات PDF والصور وحاسبات البناء والطاقة الشمسية مجاناً',
    'أسعار الصرف,سعر الذهب,أدوات PDF,حاسبة البناء,طاقة شمسية', 'WebSite'],

  'exchange'    => ['pages/exchange.php',    'أسعار الصرف اليوم — تحويل العملات لحظة بلحظة | '.SITE_NAME,
    'أسعار صرف الدولار واليورو والجنيه مقابل الريال السعودي والدرهم والجنيه المصري لحظة بلحظة مع أداة تحويل مجانية',
    'سعر الدولار اليوم,تحويل العملات,أسعار الصرف,سعر اليورو,الريال السعودي', 'FinancialService'],

  'gold'        => ['pages/gold.php',        'أسعار الذهب اليوم — عيار 24 22 21 18 قيراط | '.SITE_NAME,
    'سعر الذهب اليوم بالجرام لجميع العيارات (24 22 21 18 14 قيراط) بالريال السعودي والدرهم الإماراتي والجنيه المصري',
    'سعر الذهب اليوم,عيار 21,عيار 18,سعر الذهب بالريال,سعر الجرام', 'FinancialService'],

  'fuel'        => ['pages/fuel.php',        'أسعار المحروقات اليوم في دول الخليج | '.SITE_NAME,
    'أسعار البنزين والديزل والكيروسين في السعودية والإمارات ومصر والكويت وقطر والبحرين وعمان',
    'أسعار البنزين,سعر الوقود,أسعار الديزل,محروقات', 'Dataset'],

  'food'        => ['pages/food.php',        'أسعار المواد الغذائية والخضار واللحوم | '.SITE_NAME,
    'تابع أسعار الخضروات والفواكه واللحوم والدواجن والحبوب والمواد الغذائية الأساسية في الأسواق العربية',
    'أسعار الخضروات,أسعار اللحوم,أسعار المواد الغذائية,أسعار الدجاج', 'Dataset'],

  'phones'      => ['pages/phones.php',      'أسعار الهواتف الذكية — مقارنة آيفون وسامسونج | '.SITE_NAME,
    'مقارنة أسعار أحدث الهواتف الذكية: آيفون 16، سامسونج جالكسي، شاومي، هواوي، أوبو بالريال والدرهم والجنيه',
    'أسعار الهواتف,سعر آيفون,سعر سامسونج,أسعار موبايلات', 'ItemList'],

  'pdf'         => ['pages/pdf.php',         'أدوات PDF مجانية — دمج وضغط وتقسيم وتحويل | '.SITE_NAME,
    'أدوات PDF احترافية مجانية بالمتصفح: دمج PDF، ضغطه، تقسيمه، تحويله إلى Word أو صور، إضافة كلمة مرور',
    'دمج PDF,ضغط PDF,تحويل PDF,أدوات PDF مجانية,PDF to Word', 'SoftwareApplication'],

  'images'      => ['pages/images.php',      'أدوات الصور المجانية — ضغط وتحويل وتعديل | '.SITE_NAME,
    'أدوات الصور المجانية: ضغط الصور مع الحفاظ على الجودة، تحويل PNG إلى JPG وWebP، تغيير الحجم، إزالة الخلفية',
    'ضغط الصور,تحويل الصور,تغيير حجم الصورة,PNG إلى JPG,إزالة الخلفية', 'SoftwareApplication'],

  'ai'          => ['pages/ai.php',          'أدوات الذكاء الاصطناعي العربية المجانية | '.SITE_NAME,
    'مجموعة أفضل أدوات الذكاء الاصطناعي المجانية: ChatGPT، توليد الصور، الترجمة، التلخيص، كتابة المحتوى',
    'أدوات الذكاء الاصطناعي,AI عربي,ChatGPT,توليد الصور,ترجمة AI', 'ItemList'],

  'calculators' => ['pages/calculators.php', 'حاسبات البناء — خرسانة وطوب وبلاط وحديد وصبة | '.SITE_NAME,
    'حاسبات البناء المجانية: كمية الخرسانة والطوب والبلاط والدهان وحديد التسليح والصبة المسلحة',
    'حاسبة البناء,حاسبة الخرسانة,كمية الطوب,حاسبة البلاط,حاسبة الصبة', 'SoftwareApplication'],

  'solar'       => ['pages/solar.php',       'حاسبة الطاقة الشمسية — ألواح وتكلفة وتوفير | '.SITE_NAME,
    'احسب عدد الألواح الشمسية المطلوبة وتكلفة نظام الطاقة الشمسية ومدة الاسترداد وتوفير الكهرباء السنوي',
    'حاسبة الطاقة الشمسية,ألواح شمسية,كم لوح شمسي,تكلفة الطاقة الشمسية', 'SoftwareApplication'],

  'articles'    => ['pages/articles.php',    'المقالات والأدلة التقنية والمالية | '.SITE_NAME,
    'مقالات وأدلة شاملة في التقنية والمال وأسعار العملات والذهب والبناء والطاقة الشمسية',
    'مقالات تقنية,مقالات مالية,أدلة البناء,ذكاء اصطناعي', 'Blog'],

  'article'     => ['pages/article.php',     SITE_NAME, '', '', 'Article'],
  'about'       => ['pages/about.php',       'من نحن | '.SITE_NAME, 'موقع '.SITE_NAME.' الشامل لأدوات الأسعار والبناء', '', 'AboutPage'],
  'privacy'     => ['pages/privacy.php',     'سياسة الخصوصية | '.SITE_NAME, 'سياسة الخصوصية وحماية بيانات مستخدمي '.SITE_NAME, '', 'WebPage'],
  'contact'     => ['pages/contact.php',     'اتصل بنا | '.SITE_NAME, 'تواصل مع فريق '.SITE_NAME.' عبر نموذج الاتصال أو البريد الإلكتروني', '', 'ContactPage'],
];

if (!isset($routes[$page])) { $page = 'home'; $uri = 'home'; }
[$page_file, $page_title, $page_desc, $page_keywords, $schema_type] = $routes[$page];
$page_canonical = base_url($page === 'home' ? '' : $uri);
?>
<!DOCTYPE html>
<html lang="<?= UI_LANG ?>" dir="<?= UI_DIR ?>">
<?php include __DIR__.'/partials/head.php'; ?>
<body class="page-<?= h($page) ?>">
<?php include __DIR__.'/partials/header.php'; ?>
<main id="main-content">
<?php
// Pass slug for article pages
$extra = $sub1;

$file = __DIR__.'/'.$page_file;
if (file_exists($file)) include $file;
else { http_response_code(404); echo '<div class="container" style="padding:80px 20px;text-align:center"><h1>404 — الصفحة غير موجودة</h1><a href="/">العودة للرئيسية</a></div>'; }
?>
</main>
<?php include __DIR__.'/partials/footer.php'; ?>
<script src="/assets/js/tools.js"></script>
</body>
</html>
