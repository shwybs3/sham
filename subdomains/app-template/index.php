<?php
/* ════════════════════════════════════════════════════════════════
   {slug}.yassota.com — Per-app subdomain (full SEO landing page)
   Loads app data from DB, renders complete landing page with:
   - SoftwareApplication + FAQPage + BreadcrumbList JSON-LD
   - Screenshots gallery, features, install guide, FAQ
   - Related apps from same category
   - Local legal pages (required for AdSense compliance)
   ════════════════════════════════════════════════════════════════ */

$host     = $_SERVER['HTTP_HOST'] ?? '';
$app_slug = strtolower(explode('.', $host)[0]);
$page     = $_GET['page'] ?? '';

// ── Serve local legal / sitemap pages ────────────────────────────
if ($page === 'sitemap' || (isset($_SERVER['REQUEST_URI']) && rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') === '/sitemap.xml')) {
    serve_sitemap($host, $app_slug);
    exit;
}
if (in_array($page, ['privacy', 'terms', 'about', 'contact', 'dmca'])) {
    serve_legal_page($page, $app_slug);
    exit;
}

// ── Load app data from DB ─────────────────────────────────────────
$config_path = dirname(__DIR__, 2) . '/config.php';
$app = null; $related = [];
if (file_exists($config_path)) {
    require_once $config_path;
    try {
        $pdo2 = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $stmt = $pdo2->prepare(
            "SELECT a.*, c.name AS cat_name, c.slug AS cat_slug
             FROM apps a LEFT JOIN categories c ON c.id=a.category_id
             WHERE a.slug=? AND a.status='published' LIMIT 1"
        );
        $stmt->execute([$app_slug]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($app && !empty($app['category_id'])) {
            $rs = $pdo2->prepare(
                "SELECT name, slug, icon_path, rating, short_description
                 FROM apps WHERE category_id=? AND status='published'
                 AND slug != ? ORDER BY rating DESC LIMIT 4"
            );
            $rs->execute([$app['category_id'], $app_slug]);
            $related = $rs->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {}
}

// ── Computed variables ────────────────────────────────────────────
$main_url   = 'https://yassota.com/' . rawurlencode($app_slug) . '/apk';
$site_url   = 'https://' . $host;
$app_name   = $app['name'] ?? ucfirst(str_replace('-', ' ', $app_slug));
$app_desc   = $app['short_description'] ?? '';
$long_desc  = $app['long_description'] ?? '';
$app_cat    = $app['cat_name'] ?? 'تطبيق';
$cat_slug   = $app['cat_slug'] ?? '';
$play_url   = $app['download_url'] ?? 'https://play.google.com/store/search?q=' . rawurlencode($app_name);
$icon_url   = !empty($app['icon_path']) ? 'https://yassota.com/' . ltrim($app['icon_path'], '/') : '';
$rating     = (float)($app['rating'] ?? 4.5);
$rat_count  = (int)($app['rating_count'] ?? 0);
$version    = $app['version'] ?? '';
$developer  = $app['developer'] ?? '';
$android_v  = $app['android_version'] ?? '';
$license    = $app['license'] ?? 'مجاني';
$updated    = $app['updated_at'] ?? date('Y-m-d');

// Parse JSON fields safely
$screenshots = [];
if (!empty($app['screenshots'])) {
    $sc = json_decode($app['screenshots'], true);
    if (is_array($sc)) $screenshots = array_slice($sc, 0, 6);
}
$features = [];
if (!empty($app['features'])) {
    $ft = json_decode($app['features'], true);
    if (is_array($ft)) $features = array_slice($ft, 0, 8);
}
$pros = [];
if (!empty($app['pros'])) {
    $pr = json_decode($app['pros'], true);
    if (is_array($pr)) $pros = array_slice($pr, 0, 5);
}
$faq_items = [];
if (!empty($app['faq'])) {
    $fq = json_decode($app['faq'], true);
    if (is_array($fq)) $faq_items = array_slice($fq, 0, 5);
}
$install_steps = [];
if (!empty($app['install_steps'])) {
    $is = json_decode($app['install_steps'], true);
    if (is_array($is)) $install_steps = array_slice($is, 0, 5);
}

// Star string for schema
$stars_str = str_repeat('★', min(5, (int)round($rating))) . str_repeat('☆', max(0, 5 - (int)round($rating)));

// Page title + meta description
$page_title = $app['seo_title'] ?? "تحميل {$app_name} APK للأندرويد — أحدث إصدار مجاناً";
$meta_desc  = $app['meta_description'] ?? ($app_desc ?: "حمّل تطبيق {$app_name} للأندرويد مجاناً. أحدث إصدار، تثبيت بخطوة واحدة، بدون روت.");
$og_image   = $icon_url ?: 'https://yassota.com/assets/img/og-default.png';

// Dynamic gradient accent based on slug hash
$hue = abs(crc32($app_slug)) % 360;
$accent = "hsl({$hue},72%,42%)";
$accent_dark = "hsl({$hue},72%,30%)";

// ── JSON-LD schemas ───────────────────────────────────────────────
$schema_app = [
    '@context'            => 'https://schema.org',
    '@type'               => 'SoftwareApplication',
    'name'                => $app_name,
    'description'         => $meta_desc,
    'url'                 => $site_url . '/',
    'applicationCategory' => 'MobileApplication',
    'operatingSystem'     => 'Android' . ($android_v ? " {$android_v}+" : ''),
    'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
    'author'              => ['@type' => 'Organization', 'name' => $developer ?: 'yassota.com'],
    'publisher'           => ['@type' => 'Organization', 'name' => 'yassota.com', 'url' => 'https://yassota.com'],
    'dateModified'        => date('Y-m-d', strtotime($updated)),
    'inLanguage'          => 'ar',
    'softwareVersion'     => $version ?: '1.0',
];
if ($icon_url) $schema_app['image'] = $icon_url;
if ($rating && $rat_count) {
    $schema_app['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => number_format($rating, 1),
        'ratingCount' => $rat_count,
        'bestRating'  => '5',
        'worstRating' => '1',
    ];
}

$schema_breadcrumb = [
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'يسوتا', 'item' => 'https://yassota.com/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $app_cat, 'item' => 'https://yassota.com/category/' . rawurlencode($cat_slug)],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $app_name, 'item' => $site_url . '/'],
    ],
];

$schema_faq = null;
if ($faq_items) {
    $qa_list = [];
    foreach ($faq_items as $fq) {
        if (!empty($fq['q']) && !empty($fq['a'])) {
            $qa_list[] = [
                '@type'          => 'Question',
                'name'           => $fq['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $fq['a']],
            ];
        }
    }
    if ($qa_list) {
        $schema_faq = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $qa_list];
    }
}

// Default FAQ if none from DB
if (!$schema_faq && !$faq_items) {
    $faq_items = [
        ['q' => "هل تطبيق {$app_name} مجاني؟", 'a' => "نعم، تطبيق {$app_name} متاح للتحميل مجاناً على أجهزة أندرويد."],
        ['q' => "كيف أحمّل {$app_name} على أندرويد؟", 'a' => "اضغط على زر «تحميل التطبيق»، ستُحال إلى متجر Google Play حيث يمكنك التثبيت مباشرة."],
        ['q' => "هل {$app_name} آمن للاستخدام؟", 'a' => "نعم، التطبيق منشور في متجر Google Play الرسمي ويخضع لمراجعة جوجل المستمرة."],
        ['q' => "ما الإصدار المطلوب من أندرويد لتشغيل {$app_name}؟", 'a' => $android_v ? "يتطلب أندرويد {$android_v} أو أحدث." : "يعمل على معظم أجهزة أندرويد الحديثة."],
    ];
    $qa_list = [];
    foreach ($faq_items as $fq) {
        $qa_list[] = [
            '@type'          => 'Question',
            'name'           => $fq['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $fq['a']],
        ];
    }
    $schema_faq = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $qa_list];
}

// Default install steps
if (!$install_steps) {
    $install_steps = [
        "اضغط على زر «تحميل التطبيق» أدناه",
        "سيفتح متجر Google Play تلقائياً",
        "اضغط على «تثبيت» في صفحة التطبيق",
        "انتظر اكتمال التحميل ثم اضغط «فتح»",
    ];
}

?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($page_title) ?></title>
<meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="index,follow">
<?php if (!empty($app['keywords'])): ?>
<meta name="keywords" content="<?= htmlspecialchars($app['keywords']) ?>">
<?php endif; ?>
<link rel="canonical" href="<?= htmlspecialchars($site_url) ?>/">
<!-- Open Graph -->
<meta property="og:type"        content="website">
<meta property="og:title"       content="<?= htmlspecialchars($page_title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta property="og:url"         content="<?= htmlspecialchars($site_url) ?>/">
<meta property="og:image"       content="<?= htmlspecialchars($og_image) ?>">
<meta property="og:site_name"   content="يسوتا — yassota.com">
<meta property="og:locale"      content="ar_AR">
<!-- Twitter Card -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?= htmlspecialchars($page_title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta name="twitter:image"       content="<?= htmlspecialchars($og_image) ?>">
<!-- Dynamic SVG favicon -->
<link rel="icon" href="<?= htmlspecialchars($site_url) ?>/favicon.svg" type="image/svg+xml">
<!-- Sitemap -->
<link rel="sitemap" type="application/xml" href="<?= htmlspecialchars($site_url) ?>/sitemap.xml">
<!-- JSON-LD schemas -->
<script type="application/ld+json"><?= json_encode($schema_app, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script type="application/ld+json"><?= json_encode($schema_breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php if ($schema_faq): ?>
<script type="application/ld+json"><?= json_encode($schema_faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<style>
:root{
  --accent:<?= $accent ?>;--accent-dk:<?= $accent_dark ?>;
  --cyan:#0ea5e9;--purple:#7c3aed;--green:#01875f;
  --hdr:#0c1e36;--bg:#f4f7fb;--surface:#fff;--text:#0f172a;--muted:#64748b;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Cairo','Tajawal',system-ui,sans-serif;background:var(--bg);color:var(--text);direction:rtl;line-height:1.68;-webkit-font-smoothing:antialiased}

/* ── Header ── */
header{background:var(--hdr);color:#e2e8f0;padding:12px 20px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 1px 12px rgba(0,0,0,.4)}
.logo{font-family:monospace;font-size:14px;font-weight:700;letter-spacing:1.5px;text-decoration:none}
.logo-yas{background:linear-gradient(135deg,#22d3ee,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.logo-sota{background:linear-gradient(135deg,#7c3aed,#f97316);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
nav{display:flex;gap:4px;flex-wrap:wrap}
nav a{color:rgba(226,232,240,.5);font-size:11.5px;text-decoration:none;padding:4px 8px;border-radius:6px;transition:color .15s,background .15s}
nav a:hover{color:#0ea5e9;background:rgba(14,165,233,.1)}

/* ── Hero ── */
.hero{background:linear-gradient(140deg,#0c1e36 0%,#111833 50%,#1a1060 100%);padding:36px 20px 28px;text-align:center;position:relative;overflow:hidden}
.hero::before,.hero::after{content:'';position:absolute;border-radius:50%;pointer-events:none}
.hero::before{top:-40%;right:-8%;width:300px;height:300px;background:radial-gradient(circle,rgba(14,165,233,.12),transparent 70%)}
.hero::after{bottom:-30%;left:-5%;width:260px;height:260px;background:radial-gradient(circle,rgba(124,58,237,.1),transparent 70%)}
.hero-icon{width:96px;height:96px;border-radius:22px;object-fit:cover;border:2.5px solid rgba(255,255,255,.12);box-shadow:0 10px 32px rgba(0,0,0,.35);margin:0 auto 14px;display:block;background:#1a2840;position:relative;z-index:1}
.hero-icon-svg{width:96px;height:96px;border-radius:22px;margin:0 auto 14px;display:block;position:relative;z-index:1}
.hero-cat{display:inline-block;padding:3px 12px;background:rgba(14,165,233,.15);border:1px solid rgba(14,165,233,.28);border-radius:20px;color:#38bdf8;font-size:10.5px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;margin-bottom:10px;position:relative;z-index:1}
.hero-name{font-size:28px;font-weight:900;color:#fff;margin-bottom:8px;letter-spacing:-.03em;position:relative;z-index:1;text-shadow:0 2px 10px rgba(0,0,0,.3)}
.hero-desc{font-size:13.5px;color:rgba(226,232,240,.65);max-width:500px;margin:0 auto 18px;line-height:1.72;position:relative;z-index:1}
.rating-row{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:20px;position:relative;z-index:1}
.stars{color:#f59e0b;font-size:17px;letter-spacing:2px}
.rating-num{color:rgba(226,232,240,.85);font-size:14px;font-weight:700}
.rating-count{color:rgba(226,232,240,.4);font-size:12px}
.btn-dl{display:inline-flex;align-items:center;gap:9px;padding:14px 30px;background:linear-gradient(135deg,#01875f,#016b4b);color:#fff;border-radius:14px;font-size:16px;font-weight:800;text-decoration:none;box-shadow:0 6px 24px rgba(1,135,95,.45);transition:transform .18s,box-shadow .18s;position:relative;z-index:1;margin-bottom:10px}
.btn-dl:hover{transform:translateY(-2px);box-shadow:0 10px 32px rgba(1,135,95,.6)}
.btn-play{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:transparent;color:rgba(226,232,240,.6);border:1px solid rgba(255,255,255,.14);border-radius:10px;font-size:12.5px;font-weight:600;text-decoration:none;transition:border-color .15s,color .15s;position:relative;z-index:1;margin-top:6px}
.btn-play:hover{border-color:var(--cyan);color:var(--cyan)}
.badges{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:14px;position:relative;z-index:1}
.badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:rgba(226,232,240,.7)}

/* ── Layout ── */
.wrap{max-width:860px;margin:0 auto;padding:26px 16px}
.card{background:var(--surface);border:1px solid rgba(15,23,42,.07);border-radius:18px;padding:22px;margin-bottom:18px;box-shadow:0 2px 10px rgba(15,23,42,.05)}
.card-title{font-size:16px;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:8px;color:var(--text)}
.card-title::before{content:'';display:inline-block;width:4px;height:20px;background:linear-gradient(180deg,var(--cyan),var(--purple));border-radius:4px;flex-shrink:0}

/* ── Breadcrumb ── */
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);margin-bottom:18px;flex-wrap:wrap}
.breadcrumb a{color:var(--cyan);text-decoration:none}
.breadcrumb a:hover{text-decoration:underline}

/* ── Main URL link ── */
.main-url-block{background:linear-gradient(135deg,rgba(14,165,233,.06),rgba(124,58,237,.06));border:1px solid rgba(14,165,233,.15);border-radius:12px;padding:14px 18px;margin-bottom:18px;text-align:center;font-size:13.5px;color:var(--muted)}
.main-url-block a{color:var(--cyan);font-weight:700;text-decoration:none}
.main-url-block a:hover{text-decoration:underline}

/* ── Screenshots ── */
.screenshots{display:flex;gap:10px;overflow-x:auto;padding-bottom:6px;scrollbar-width:thin}
.screenshots img{height:200px;width:auto;border-radius:10px;flex-shrink:0;border:1px solid rgba(15,23,42,.08);object-fit:cover}

/* ── Features grid ── */
.features-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px}
.feature-item{display:flex;align-items:flex-start;gap:9px;padding:10px 12px;background:#f8fafc;border-radius:10px;border:1px solid rgba(15,23,42,.06)}
.feature-item .fi-icon{color:var(--green);font-size:16px;flex-shrink:0;margin-top:1px}
.feature-item span{font-size:13px;color:#334155;line-height:1.45}

/* ── Pros list ── */
.pros-list{list-style:none;display:flex;flex-direction:column;gap:7px}
.pros-list li{display:flex;align-items:flex-start;gap:9px;font-size:13.5px;color:#334155}
.pros-list .pi{color:var(--green);font-size:15px;flex-shrink:0;margin-top:2px}

/* ── App info table ── */
.info-table{width:100%;border-collapse:collapse;font-size:13px}
.info-table tr{border-bottom:1px solid #f1f5f9}
.info-table tr:last-child{border:0}
.info-table td{padding:9px 4px;vertical-align:top}
.info-table td:first-child{font-weight:700;color:var(--text);width:42%;padding-left:0}
.info-table td:last-child{color:var(--muted)}

/* ── Install steps ── */
.steps{display:flex;flex-direction:column;gap:10px;counter-reset:step}
.step{display:flex;align-items:flex-start;gap:12px;padding:12px 14px;background:#f8fafc;border-radius:12px;border:1px solid rgba(15,23,42,.06)}
.step-num{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--cyan),var(--purple));color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0}
.step-text{font-size:13.5px;color:#334155;line-height:1.5;padding-top:3px}

/* ── FAQ ── */
.faq-item{border-bottom:1px solid #f1f5f9;padding:14px 0}
.faq-item:last-child{border:0}
.faq-q{font-size:14px;font-weight:700;color:var(--text);cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:10px;line-height:1.45}
.faq-q::after{content:'＋';font-size:18px;color:var(--cyan);flex-shrink:0;transition:transform .2s}
.faq-item.open .faq-q::after{transform:rotate(45deg)}
.faq-a{font-size:13.5px;color:var(--muted);line-height:1.75;max-height:0;overflow:hidden;transition:max-height .3s ease,padding .2s}
.faq-item.open .faq-a{max-height:300px;padding-top:10px}

/* ── Related apps ── */
.related-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}
.rel-card{background:var(--surface);border:1px solid rgba(15,23,42,.07);border-radius:14px;padding:14px;text-decoration:none;color:inherit;transition:transform .18s,box-shadow .18s;display:flex;flex-direction:column;gap:6px}
.rel-card:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(15,23,42,.1)}
.rel-icon{width:52px;height:52px;border-radius:14px;object-fit:cover;border:1px solid rgba(15,23,42,.07)}
.rel-icon-ph{width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#0c1e36,#2d1b69);display:flex;align-items:center;justify-content:center;font-size:22px}
.rel-name{font-size:13px;font-weight:700;color:var(--text);line-height:1.3}
.rel-desc{font-size:11.5px;color:var(--muted);line-height:1.4}
.rel-stars{color:#f59e0b;font-size:11px}

/* ── Footer ── */
footer{background:var(--hdr);color:rgba(226,232,240,.45);text-align:center;padding:22px 20px;font-size:12px;margin-top:40px}
footer a{color:rgba(226,232,240,.4);text-decoration:none;margin:0 5px;transition:color .15s}
footer a:hover{color:var(--cyan)}
.footer-legal{margin-top:10px;display:flex;flex-wrap:wrap;justify-content:center;gap:2px 4px}
</style>
</head>
<body>

<header>
  <a href="https://yassota.com" class="logo"><span class="logo-yas">yas</span><span class="logo-sota">sota</span></a>
  <nav>
    <a href="https://yassota.com">الرئيسية</a>
    <a href="https://yassota.com/blog">المدونة</a>
    <a href="?page=about">من نحن</a>
    <a href="?page=privacy">الخصوصية</a>
    <a href="?page=contact">تواصل</a>
  </nav>
</header>

<!-- ── Hero ─────────────────────────────────────────────── -->
<div class="hero">
  <?php if ($icon_url): ?>
  <img src="<?= htmlspecialchars($icon_url) ?>" alt="أيقونة <?= htmlspecialchars($app_name) ?>" class="hero-icon" loading="eager" width="96" height="96">
  <?php else: ?>
  <svg class="hero-icon-svg" viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
    <rect width="96" height="96" rx="22" fill="url(#g<?= crc32($app_slug) ?>)"/>
    <defs><linearGradient id="g<?= crc32($app_slug) ?>" x1="0" y1="0" x2="96" y2="96" gradientUnits="userSpaceOnUse">
      <stop stop-color="<?= htmlspecialchars($accent) ?>"/>
      <stop offset="1" stop-color="<?= htmlspecialchars($accent_dark) ?>"/>
    </linearGradient></defs>
    <text x="50%" y="60%" text-anchor="middle" font-family="Cairo,sans-serif" font-size="42" font-weight="900" fill="white">
      <?= mb_strtoupper(mb_substr($app_name, 0, 1)) ?>
    </text>
  </svg>
  <?php endif; ?>
  <div class="hero-cat"><?= htmlspecialchars($app_cat) ?></div>
  <h1 class="hero-name"><?= htmlspecialchars($app_name) ?></h1>
  <?php if ($app_desc): ?>
  <p class="hero-desc"><?= htmlspecialchars($app_desc) ?></p>
  <?php endif; ?>
  <div class="rating-row">
    <span class="stars"><?= $stars_str ?></span>
    <span class="rating-num"><?= number_format($rating, 1) ?></span>
    <?php if ($rat_count): ?><span class="rating-count">(<?= number_format($rat_count) ?> تقييم)</span><?php endif; ?>
  </div>
  <div>
    <a href="<?= htmlspecialchars($play_url) ?>" class="btn-dl" target="_blank" rel="noopener noreferrer">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M5 3l14 9-14 9V3z"/></svg>
      تحميل <?= htmlspecialchars($app_name) ?>
    </a>
    <br>
    <a href="<?= htmlspecialchars($play_url) ?>" class="btn-play" target="_blank" rel="noopener noreferrer">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M2 3h20v18H2z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 9l8 3-8 3V9z"/></svg>
      افتح في Google Play
    </a>
  </div>
  <div class="badges">
    <?php if ($version): ?><span class="badge">🏷 إصدار <?= htmlspecialchars($version) ?></span><?php endif; ?>
    <?php if ($android_v): ?><span class="badge">🤖 أندرويد <?= htmlspecialchars($android_v) ?>+</span><?php endif; ?>
    <span class="badge">✅ <?= htmlspecialchars($license ?: 'مجاني') ?></span>
    <?php if ($developer): ?><span class="badge">👨‍💻 <?= htmlspecialchars($developer) ?></span><?php endif; ?>
  </div>
</div>

<!-- ── Main content ──────────────────────────────────────── -->
<div class="wrap">

  <!-- Breadcrumb -->
  <nav class="breadcrumb" aria-label="مسار التنقل">
    <a href="https://yassota.com">يسوتا</a>
    <span>›</span>
    <?php if ($cat_slug): ?>
    <a href="https://yassota.com/category/<?= rawurlencode($cat_slug) ?>"><?= htmlspecialchars($app_cat) ?></a>
    <span>›</span>
    <?php endif; ?>
    <span><?= htmlspecialchars($app_name) ?></span>
  </nav>

  <!-- Main site link -->
  <div class="main-url-block">
    📱 الصفحة الكاملة للتطبيق مع التعليقات والمراجعات:
    <a href="<?= htmlspecialchars($main_url) ?>" target="_blank" rel="noopener">yassota.com/<?= htmlspecialchars($app_slug) ?>/apk</a>
  </div>

  <?php if ($screenshots): ?>
  <!-- Screenshots -->
  <div class="card">
    <div class="card-title">لقطات الشاشة</div>
    <div class="screenshots">
      <?php foreach ($screenshots as $sc): ?>
      <img src="<?= htmlspecialchars(is_string($sc) ? 'https://yassota.com/' . ltrim($sc, '/') : '') ?>"
           alt="لقطة شاشة <?= htmlspecialchars($app_name) ?>"
           loading="lazy">
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($long_desc): ?>
  <!-- About the app -->
  <div class="card">
    <div class="card-title">عن التطبيق</div>
    <div style="font-size:13.5px;color:#475569;line-height:1.82">
      <?= nl2br(htmlspecialchars(mb_substr($long_desc, 0, 1000))) ?>
      <?php if (mb_strlen($long_desc) > 1000): ?>
      …<br><a href="<?= htmlspecialchars($main_url) ?>" style="color:var(--cyan);font-weight:700">اقرأ الوصف الكامل ←</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($features): ?>
  <!-- Features -->
  <div class="card">
    <div class="card-title">أبرز المميزات</div>
    <div class="features-grid">
      <?php foreach ($features as $f): ?>
      <div class="feature-item">
        <span class="fi-icon">✦</span>
        <span><?= htmlspecialchars(is_string($f) ? $f : ($f['text'] ?? $f['feature'] ?? '')) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($pros): ?>
  <!-- Pros -->
  <div class="card">
    <div class="card-title">لماذا تستخدم <?= htmlspecialchars($app_name) ?>؟</div>
    <ul class="pros-list">
      <?php foreach ($pros as $p): ?>
      <li><span class="pi">✔</span><?= htmlspecialchars(is_string($p) ? $p : ($p['text'] ?? $p['pro'] ?? '')) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <!-- App info table -->
  <div class="card">
    <div class="card-title">معلومات التطبيق</div>
    <table class="info-table">
      <?php if ($developer): ?><tr><td>المطور</td><td><?= htmlspecialchars($developer) ?></td></tr><?php endif; ?>
      <?php if ($version): ?><tr><td>الإصدار</td><td><?= htmlspecialchars($version) ?></td></tr><?php endif; ?>
      <?php if ($android_v): ?><tr><td>يتطلب أندرويد</td><td><?= htmlspecialchars($android_v) ?> أو أحدث</td></tr><?php endif; ?>
      <?php if (!empty($app['size_mb'])): ?><tr><td>حجم التطبيق</td><td><?= htmlspecialchars((string)$app['size_mb']) ?> MB</td></tr><?php endif; ?>
      <tr><td>الترخيص</td><td><?= htmlspecialchars($license ?: 'مجاني') ?></td></tr>
      <tr><td>التصنيف</td><td><?= htmlspecialchars($app_cat) ?></td></tr>
      <tr><td>آخر تحديث</td><td><?= date('Y-m-d', strtotime($updated)) ?></td></tr>
    </table>
  </div>

  <!-- Install guide -->
  <div class="card">
    <div class="card-title">طريقة التثبيت</div>
    <div class="steps">
      <?php foreach ($install_steps as $i => $step): ?>
      <div class="step">
        <div class="step-num"><?= $i + 1 ?></div>
        <div class="step-text"><?= htmlspecialchars(is_string($step) ? $step : ($step['step'] ?? '')) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="margin-top:16px;text-align:center">
      <a href="<?= htmlspecialchars($play_url) ?>" class="btn-dl" target="_blank" rel="noopener noreferrer" style="display:inline-flex">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M5 3l14 9-14 9V3z"/></svg>
        تحميل من Google Play
      </a>
    </div>
  </div>

  <!-- FAQ -->
  <?php if ($faq_items): ?>
  <div class="card">
    <div class="card-title">الأسئلة الشائعة</div>
    <?php foreach ($faq_items as $fq): ?>
    <?php
      $fq_q = is_array($fq) ? ($fq['q'] ?? $fq['question'] ?? '') : '';
      $fq_a = is_array($fq) ? ($fq['a'] ?? $fq['answer'] ?? '') : '';
      if (!$fq_q) continue;
    ?>
    <div class="faq-item">
      <div class="faq-q"><?= htmlspecialchars($fq_q) ?></div>
      <div class="faq-a"><?= htmlspecialchars($fq_a) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($related): ?>
  <!-- Related apps -->
  <div class="card">
    <div class="card-title">تطبيقات مشابهة في «<?= htmlspecialchars($app_cat) ?>»</div>
    <div class="related-grid">
      <?php foreach ($related as $rel): ?>
      <a href="https://<?= htmlspecialchars($rel['slug']) ?>.yassota.com/" class="rel-card">
        <?php if (!empty($rel['icon_path'])): ?>
        <img src="https://yassota.com/<?= htmlspecialchars(ltrim($rel['icon_path'], '/')) ?>"
             alt="<?= htmlspecialchars($rel['name']) ?>"
             class="rel-icon" loading="lazy" width="52" height="52">
        <?php else: ?>
        <div class="rel-icon-ph">📱</div>
        <?php endif; ?>
        <div class="rel-name"><?= htmlspecialchars($rel['name']) ?></div>
        <?php if (!empty($rel['short_description'])): ?>
        <div class="rel-desc"><?= htmlspecialchars(mb_substr($rel['short_description'], 0, 60)) ?>…</div>
        <?php endif; ?>
        <?php if (!empty($rel['rating'])): ?>
        <div class="rel-stars">★ <?= htmlspecialchars(number_format((float)$rel['rating'], 1)) ?></div>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /wrap -->

<footer>
  <p>© <?= date('Y') ?> <a href="https://yassota.com">yassota.com</a> — دليل التطبيقات العربية</p>
  <nav class="footer-legal" aria-label="الصفحات القانونية">
    <a href="?page=about">من نحن</a>
    <a href="?page=privacy">الخصوصية</a>
    <a href="?page=terms">الشروط</a>
    <a href="?page=contact">تواصل</a>
    <a href="?page=dmca">DMCA</a>
    <a href="/sitemap.xml">Sitemap</a>
    <a href="https://yassota.com">يسوتا الرئيسية</a>
  </nav>
</footer>

<script>
// FAQ accordion
document.querySelectorAll('.faq-item').forEach(function(el){
  el.querySelector('.faq-q').addEventListener('click',function(){
    el.classList.toggle('open');
  });
});
</script>
</body>
</html>
<?php

/* ── Legal pages ─────────────────────────────────────────────── */
function serve_legal_page(string $page, string $slug): void {
    $nm = htmlspecialchars(str_replace('-', ' ', $slug));
    $pages = [
        'about' => [
            'title' => "من نحن — {$nm}",
            'h'     => 'من نحن',
            'body'  => "<p>هذه الصفحة الفرعية <strong>{$nm}.yassota.com</strong> جزء من منصة <a href='https://yassota.com'>يسوتا</a> لدليل التطبيقات العربية.</p>
                        <p>نهدف إلى تقديم معلومات دقيقة وشاملة عن التطبيقات الأندرويد للمستخدمين الناطقين بالعربية، مع توفير روابط مباشرة لمتجر Google Play الرسمي.</p>
                        <p>لا نستضيف أي ملفات APK على خوادمنا. جميع الروابط تتجه إلى المصادر الرسمية.</p>
                        <p><a href='https://yassota.com/about'>المزيد عن يسوتا ←</a></p>",
        ],
        'privacy' => [
            'title' => "سياسة الخصوصية — {$nm}",
            'h'     => 'سياسة الخصوصية',
            'body'  => "<p>آخر تحديث: " . date('Y-m-d') . "</p>
                        <h3 style='margin:12px 0 6px;font-size:15px'>البيانات التي نجمعها</h3>
                        <p>لا نجمع أي بيانات شخصية من زوار الصفحات الفرعية للتطبيقات. لا تسجيل دخول، لا نماذج، لا كوكيز تتبع.</p>
                        <h3 style='margin:12px 0 6px;font-size:15px'>خدمات الطرف الثالث</h3>
                        <p>قد تستخدم هذه الصفحة Google AdSense لعرض الإعلانات. تخضع الإعلانات <a href='https://policies.google.com/privacy'>لسياسة خصوصية جوجل</a>.</p>
                        <p><a href='https://yassota.com/privacy-policy'>سياسة الخصوصية الكاملة ←</a></p>",
        ],
        'terms' => [
            'title' => "شروط الاستخدام — {$nm}",
            'h'     => 'شروط الاستخدام',
            'body'  => "<p>باستخدامك هذه الصفحة توافق على <a href='https://yassota.com/terms'>شروط استخدام يسوتا</a>.</p>
                        <p>المحتوى المقدم للأغراض المعلوماتية فقط. جميع الروابط تتجه إلى متجر Google Play الرسمي ونحن لسنا مسؤولين عن محتوى التطبيقات ذاتها.</p>",
        ],
        'contact' => [
            'title' => "تواصل معنا — {$nm}",
            'h'     => 'تواصل معنا',
            'body'  => "<p>للتواصل بشأن تطبيق <strong>{$nm}</strong> أو للإبلاغ عن محتوى غير لائق:</p>
                        <p><a href='https://yassota.com/contact'>نموذج التواصل على يسوتا ←</a></p>",
        ],
        'dmca' => [
            'title' => "DMCA — {$nm}",
            'h'     => 'إشعار DMCA',
            'body'  => "<p>إذا كنت تعتقد أن محتوى هذه الصفحة ينتهك حقوقك الملكية الفكرية، يرجى إرسال إشعار DMCA عبر:</p>
                        <p><a href='https://yassota.com/dmca'>نموذج إشعار DMCA ←</a></p>",
        ],
    ];
    $p = $pages[$page] ?? $pages['about'];
    echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='utf-8'>"
        . "<title>" . htmlspecialchars($p['title']) . "</title>"
        . "<meta name='viewport' content='width=device-width,initial-scale=1'>"
        . "<meta name='robots' content='noindex,follow'>"
        . "<style>*{box-sizing:border-box}body{font-family:Cairo,system-ui,sans-serif;background:#f4f7fb;color:#0f172a;direction:rtl;line-height:1.75;padding:0}header{background:#0c1e36;padding:12px 20px;color:#e2e8f0;font-size:14px}.wrap{max-width:700px;margin:0 auto;padding:32px 20px}h1{font-size:22px;font-weight:800;margin-bottom:16px;color:#0f172a}h3{font-size:15px;font-weight:700;color:#0f172a}p{color:#475569;margin-bottom:12px;font-size:14px}a{color:#0ea5e9}.back{display:inline-flex;align-items:center;gap:6px;margin-bottom:20px;color:#0ea5e9;text-decoration:none;font-weight:600;font-size:13px}footer{background:#0c1e36;color:rgba(226,232,240,.4);text-align:center;padding:16px;font-size:12px;margin-top:40px}footer a{color:rgba(226,232,240,.35);text-decoration:none;margin:0 6px}</style>"
        . "</head><body>"
        . "<header><a href='/' style='color:#38bdf8;text-decoration:none;font-weight:700'>" . $nm . ".yassota.com</a></header>"
        . "<div class='wrap'><a href='/' class='back'>← العودة للتطبيق</a><h1>" . $p['h'] . "</h1>" . $p['body'] . "</div>"
        . "<footer><a href='/'>الرئيسية</a><a href='/?page=about'>من نحن</a><a href='/?page=privacy'>الخصوصية</a><a href='https://yassota.com'>يسوتا</a></footer>"
        . "</body></html>";
}

/* ── Sitemap ─────────────────────────────────────────────────── */
function serve_sitemap(string $host, string $slug): void {
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    $base = 'https://' . $host;
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    echo "  <url><loc>{$base}/</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>\n";
    foreach (['about','privacy','terms','contact','dmca'] as $p) {
        echo "  <url><loc>{$base}/?page={$p}</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>\n";
    }
    echo '</urlset>';
}
