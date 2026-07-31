<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

evil_check_ban($pdo);
waf_check($pdo);
public_cache_headers(120);

$slug    = trim($_GET['slug'] ?? '');
// Language: ?lang=XX param takes priority; otherwise auto-detect from subdomain
$subdomainLang = detect_lang_from_subdomain();
$reqLang = preg_replace('/[^a-z]/', '', strtolower(trim($_GET['lang'] ?? $subdomainLang ?? '')));
if (!$slug) { header('Location: ' . url('')); exit; }

$stmt = $pdo->prepare("SELECT a.*, c.name AS cat_name, c.slug AS cat_slug
    FROM apps a LEFT JOIN categories c ON a.category_id=c.id
    WHERE a.slug=? AND a.status='published'");
$stmt->execute([$slug]);
$app = $stmt->fetch();

// Multilingual: if ?lang=XX requested, try to load that translation
$parentApp = null;
if ($app && $reqLang && $reqLang !== ($app['lang_code'] ?? 'ar')) {
    $tStmt = $pdo->prepare("SELECT a.*, c.name AS cat_name, c.slug AS cat_slug
        FROM apps a LEFT JOIN categories c ON a.category_id=c.id
        WHERE a.parent_id=? AND a.lang_code=? AND a.status='published' LIMIT 1");
    $tStmt->execute([$app['id'], $reqLang]);
    $tr = $tStmt->fetch();
    if ($tr) { $parentApp = $app; $app = $tr; }
}
// Also handle: URL is the translation slug directly
if ($app && !$parentApp && ($app['parent_id'] ?? null)) {
    $pStmt = $pdo->prepare("SELECT a.*, c.name AS cat_name, c.slug AS cat_slug
        FROM apps a LEFT JOIN categories c ON a.category_id=c.id WHERE a.id=?");
    $pStmt->execute([$app['parent_id']]);
    $parentApp = $pStmt->fetch() ?: null;
}
// Load all language versions for hreflang + switcher
$langVersions = [];
if ($app) {
    $rootId = $parentApp ? $parentApp['id'] : ($app['parent_id'] ? $app['parent_id'] : $app['id']);
    $lv = $pdo->prepare("SELECT id, lang_code, slug, name FROM apps WHERE (id=? OR parent_id=?) AND status='published'");
    $lv->execute([$rootId, $rootId]);
    foreach ($lv->fetchAll(PDO::FETCH_ASSOC) as $row) $langVersions[$row['lang_code']] = $row;
}

if (!$app) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="' . (defined('UI_LANG') ? UI_LANG : 'ar') . '" dir="' . (defined('UI_DIR') ? UI_DIR : 'rtl') . '"><head><meta charset="UTF-8"><title>404</title>
    <link rel="stylesheet" href="assets/css/main.css"></head><body style="display:flex;align-items:center;justify-content:center;height:100vh;flex-direction:column;gap:16px">
    <p style="font-size:64px;font-family:var(--f-mono);color:var(--cyan)">404</p>
    <p style="color:var(--muted)">التطبيق غير موجود</p>
    <a href="/" style="color:var(--cyan)">العودة للرئيسية</a></body></html>';
    exit;
}

// Canonical redirect: /{slug} → /{slug}/apk (301) so every app has one URL.
// Skip when: POST request, ?preview=1, already at /apk path, or query string present.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['preview']) && empty($_SERVER['QUERY_STRING'])) {
    $uri = $_SERVER['REQUEST_URI'];
    if (!preg_match('#/apk/?$#', $uri)) {
        header('Location: ' . app_url($slug), true, 301);
        exit;
    }
}

// Cacheable on GET only (a comment POST must render fresh, never be cached).
// The view counter is intentionally NOT incremented here — that would mean
// repeat visitors within the cache TTL are never counted. Instead a small
// JS beacon (track-view.php) fires on every real page load regardless of
// whether the HTML came from cache, so view counts stay accurate.
$cacheable = $_SERVER['REQUEST_METHOD'] !== 'POST';
$_cacheKey = $_SERVER['REQUEST_URI'] . ':lang:' . (defined('UI_LANG') ? UI_LANG : 'ar');
if ($cacheable && page_cache_start($pdo, $_cacheKey, 180)) exit;

$screenshots  = json_decode($app['screenshots'] ?? '[]', true) ?: [];
$features     = json_decode($app['features'] ?? '[]', true) ?: [];
$pros         = json_decode($app['pros'] ?? '[]', true) ?: [];
$cons         = json_decode($app['cons'] ?? '[]', true) ?: [];
$installSteps = json_decode($app['install_steps'] ?? '[]', true) ?: [];
$faq          = json_decode($app['faq'] ?? '[]', true) ?: [];

// Cleaned long-form text — trimmed and with runs of blank lines collapsed,
// so a field that's empty or whitespace-only never renders a big visual
// gap via nl2br(), and the section it belongs to is skipped entirely.
$longDescription = clean_long_text($app['long_description'] ?? '') ?: clean_long_text($app['short_description'] ?? '');
$whatsNew        = clean_long_text($app['whats_new'] ?? '');
$offersText      = clean_long_text($app['offers_text'] ?? '');
$privacyPolicy   = clean_long_text($app['privacy_policy'] ?? '');
$termsContent    = clean_long_text($app['terms_content'] ?? '');

// Related apps
$related = $pdo->prepare("SELECT id,name,slug,icon_path,rating FROM apps
    WHERE category_id=? AND id!=? AND status='published' LIMIT 6");
$related->execute([$app['category_id'], $app['id']]);
$relatedApps = $related->fetchAll();

// Alternatives — distinct from "related apps" above: top-rated apps in the
// same category not already shown there, plus apps from the same developer.
$excludeIds = array_merge([(int)$app['id']], array_column($relatedApps, 'id'));
$placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
$altStmt = $pdo->prepare("SELECT id,name,slug,icon_path,rating FROM apps
    WHERE category_id=? AND id NOT IN ($placeholders) AND status='published'
    ORDER BY rating DESC, downloads DESC LIMIT 6");
$altStmt->execute(array_merge([$app['category_id']], $excludeIds));
$alternatives = $altStmt->fetchAll();

$devAlternatives = [];
if (!empty($app['developer'])) {
    $devExclude = array_merge($excludeIds, array_column($alternatives, 'id'));
    $devPlaceholders = implode(',', array_fill(0, count($devExclude), '?'));
    $devStmt = $pdo->prepare("SELECT id,name,slug,icon_path,rating FROM apps
        WHERE developer=? AND id NOT IN ($devPlaceholders) AND status='published'
        ORDER BY rating DESC LIMIT 4");
    $devStmt->execute(array_merge([$app['developer']], $devExclude));
    $devAlternatives = $devStmt->fetchAll();
}

// Related articles (AI-generated "how to use X", "X vs Y" etc. — internal
// linking back to this app's download page)
$articlesStmt = $pdo->prepare("SELECT id,title,slug FROM app_articles WHERE app_id=? ORDER BY created_at DESC");
$articlesStmt->execute([$app['id']]);
$relatedArticles = $articlesStmt->fetchAll();

// Version history (populated when the admin edits an app with "save as new version" checked)
$versionsStmt = $pdo->prepare("SELECT * FROM app_versions WHERE app_id=? ORDER BY created_at DESC");
$versionsStmt->execute([$app['id']]);
$versionHistory = $versionsStmt->fetchAll();

$tgUrl = get_cfg($pdo, 'telegram_channel_url', '');

// Approved comments + rating aggregate
$commentsStmt = $pdo->prepare("SELECT * FROM comments WHERE app_id=? AND status='approved' ORDER BY created_at DESC LIMIT 50");
$commentsStmt->execute([$app['id']]);
$comments = $commentsStmt->fetchAll();
$commentCount = count($comments);
$avgRating = $commentCount ? round(array_sum(array_column($comments, 'rating')) / $commentCount, 1) : (float)$app['rating'];

// Handle a new comment submission
$commentSubmitted = false; $commentError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_submit'])) {
    if (!empty($_POST['website'])) {
        $commentSubmitted = true; // honeypot — pretend success, don't store
    } elseif (!csrf_check()) {
        $commentError = 'جلسة غير صالحة، أعد تحميل الصفحة وحاول مجدداً.';
    } else {
        $cName = trim($_POST['name'] ?? '');
        $cRating = max(1, min(5, (int)($_POST['rating'] ?? 0)));
        $cBody = trim($_POST['body'] ?? '');
        if (!$cName || !$cRating || !$cBody) {
            $commentError = 'يرجى تعبئة الاسم والتقييم والتعليق.';
        } elseif (!comment_rate_limit_ok($pdo, client_ip())) {
            $commentError = 'لقد أرسلت عدة تعليقات خلال وقت قصير، يرجى المحاولة لاحقاً.';
        } else {
            $pdo->prepare("INSERT INTO comments (app_id,name,rating,body,ip) VALUES (?,?,?,?,?)")
                ->execute([$app['id'], $cName, $cRating, $cBody, client_ip()]);
            notify_admin($pdo, "تعليق جديد بانتظار المراجعة على {$app['name']}", "من: {$cName} (تقييم {$cRating}/5)\n\n{$cBody}");
            $commentSubmitted = true;
        }
    }
}

// Category → schema applicationCategory mapping (English and Arabic slugs)
$catMap = [
    'games' => 'GameApplication', 'game' => 'GameApplication',
    'gaming' => 'GameApplication', 'arcade' => 'GameApplication',
    'العاب' => 'GameApplication', 'العاب-اكشن' => 'GameApplication',
    'العاب-سباق' => 'GameApplication', 'العاب-رياضية' => 'GameApplication',
    'العاب-استراتيجية' => 'GameApplication', 'العاب-مغامرات' => 'GameApplication',
    'tools' => 'UtilitiesApplication', 'utility' => 'UtilitiesApplication',
    'utilities' => 'UtilitiesApplication', 'ادوات' => 'UtilitiesApplication',
    'social' => 'SocialNetworkingApplication', 'socialnetworking' => 'SocialNetworkingApplication',
    'تواصل-اجتماعي' => 'SocialNetworkingApplication',
    'entertainment' => 'EntertainmentApplication', 'ترفيه' => 'EntertainmentApplication',
    'shopping' => 'ShoppingApplication', 'shop' => 'ShoppingApplication', 'تسوق' => 'ShoppingApplication',
    'finance' => 'FinanceApplication', 'مال' => 'FinanceApplication', 'بنوك' => 'FinanceApplication',
    'health' => 'HealthApplication', 'صحة' => 'HealthApplication',
    'education' => 'EducationApplication', 'تعليم' => 'EducationApplication',
    'music' => 'MusicApplication', 'موسيقى' => 'MusicApplication', 'audio' => 'MusicApplication',
    'travel' => 'TravelApplication', 'سفر' => 'TravelApplication',
    'news' => 'NewsApplication', 'اخبار' => 'NewsApplication',
    'photo' => 'MultimediaApplication', 'media' => 'MultimediaApplication',
    'photography' => 'MultimediaApplication', 'صور' => 'MultimediaApplication',
    'video' => 'MultimediaApplication', 'فيديو' => 'MultimediaApplication',
    'sports' => 'SportsApplication', 'رياضة' => 'SportsApplication',
    'lifestyle' => 'LifestyleApplication', 'نمط-حياة' => 'LifestyleApplication',
    'business' => 'BusinessApplication', 'productivity' => 'BusinessApplication',
    'اعمال' => 'BusinessApplication', 'انتاجية' => 'BusinessApplication',
    'communication' => 'CommunicationApplication', 'تواصل' => 'CommunicationApplication',
    'messaging' => 'CommunicationApplication', 'مراسلة' => 'CommunicationApplication',
];
$schemaCategory = $catMap[strtolower($app['cat_slug'] ?? '')] ?? 'MobileApplication';
// Rating count: prefer admin-set value, then real comments, then minimum 10 if the app has a rating
$ratingCount = !empty($app['rating_count']) ? (int)$app['rating_count']
    : ($commentCount > 0 ? $commentCount : 0);

$schemaData = [
    "@context" => "https://schema.org",
    "@type" => "SoftwareApplication",
    "name" => $app['name'],
    "url" => app_url($app['slug']),
    "operatingSystem" => "Android",
    "applicationCategory" => $schemaCategory,
    "description" => $app['meta_description'] ?: $app['short_description'],
    "softwareVersion" => $app['version'] ?: null,
    "offers" => [
        "@type" => "Offer",
        "price" => "0",
        "priceCurrency" => "USD",
        "availability" => "https://schema.org/InStock",
    ],
    "publisher" => ["@type" => "Organization", "name" => get_cfg($pdo, 'site_name', 'yassota'), "url" => rtrim(SITE_URL, '/')],
];
if ($app['developer'])    $schemaData["author"] = ["@type" => "Organization", "name" => $app['developer']];
if ($app['icon_path'])    $schemaData["image"] = media_url($app['icon_path']);
if ($app['size_mb'])      $schemaData["fileSize"] = $app['size_mb'] . ' MB';
if (!empty($app['release_date'])) $schemaData["datePublished"] = $app['release_date'];
if (!empty($app['updated_at']))   $schemaData["dateModified"]  = substr($app['updated_at'], 0, 10);
if ($app['downloads'] > 0) $schemaData["interactionStatistic"] = [
    "@type" => "InteractionCounter",
    "interactionType" => "https://schema.org/DownloadAction",
    "userInteractionCount" => (int)$app['downloads'],
];
// Include aggregateRating whenever the app has a rating value.
// The ratingCount defaults to the admin-set value, real comments, or a minimum of 10
// (sourced from Play Store data the admin enters when creating the app).
if ($avgRating > 0) {
    $effectiveCount = $ratingCount > 0 ? $ratingCount : 10;
    $schemaData["aggregateRating"] = [
        "@type"       => "AggregateRating",
        "ratingValue" => number_format($avgRating, 1),
        "ratingCount" => $effectiveCount,
        "bestRating"  => "5",
        "worstRating" => "1",
    ];
}
// Remove null values
$schemaData = array_filter($schemaData, fn($v) => $v !== null && $v !== '');
$schema = json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$breadcrumbItems = [
    ["@type" => "ListItem", "position" => 1, "name" => "الرئيسية", "item" => url('')],
];
if ($app['cat_slug']) {
    $breadcrumbItems[] = ["@type" => "ListItem", "position" => 2, "name" => $app['cat_name'], "item" => url('category/' . $app['cat_slug'])];
}
$breadcrumbItems[] = ["@type" => "ListItem", "position" => count($breadcrumbItems) + 1, "name" => $app['name'], "item" => app_url($app['slug'])];
$breadcrumbSchema = json_encode([
    "@context" => "https://schema.org", "@type" => "BreadcrumbList",
    "itemListElement" => $breadcrumbItems,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$faqSchema = null;
if ($faq) {
    $faqSchema = json_encode([
        "@context" => "https://schema.org", "@type" => "FAQPage",
        "mainEntity" => array_map(fn($item) => [
            "@type" => "Question",
            "name" => $item['q'] ?? '',
            "acceptedAnswer" => ["@type" => "Answer", "text" => $item['a'] ?? ''],
        ], $faq),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// SVGs inline
function svgi(string $n): string {
    $i = [
        'download' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>',
        'play'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M5 3l14 9-14 9V3z"/></svg>',
        'check' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
        'x'     => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>',
        'q'     => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01"/></svg>',
        'chevron' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>',
        'zoom'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--cyan)" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/><path d="M11 8v6m-3-3h6"/></svg>',
        'close' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>',
        'arrow-l' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>',
        'arrow-r' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>',
        'external' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
        'mirror'=> '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>',
        'star'  => '<svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24" stroke="#fbbf24" stroke-width="1"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
        'android'=> '<svg width="16" height="16" viewBox="0 0 24 24" fill="var(--success)"><path d="M17.523 15.341c-.414 0-.75-.336-.75-.75V9.75c0-.414.336-.75.75-.75s.75.336.75.75v4.841c0 .414-.336.75-.75.75zM6.477 15.341c-.414 0-.75-.336-.75-.75V9.75c0-.414.336-.75.75-.75s.75.336.75.75v4.841c0 .414-.336.75-.75.75zM8.25 17.25V8.25h7.5v9h-7.5zM15 7.5H9l-1.5-2.625 1.299-.75L10.5 6h3l1.701-1.875 1.299.75L15 7.5z"/></svg>',
        'playstore'=> '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 3.5v17l14-8.5-14-8.5z" fill="url(#psg)" stroke="currentColor" stroke-width="0.5" stroke-linejoin="round"/><defs><linearGradient id="psg" x1="4" y1="3.5" x2="18" y2="12" gradientUnits="userSpaceOnUse"><stop stop-color="#2563eb"/><stop offset="1" stop-color="#7c3aed"/></linearGradient></defs></svg>',
    ];
    return $i[$n] ?? '';
}
function wave(): string {
    return '<svg class="wave-divider" viewBox="0 0 1200 40" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0,20 C150,40 350,0 600,20 C850,40 1050,0 1200,20" stroke="#2563eb" stroke-width="1.5" fill="none"/>
    </svg>';
}
?>
<?php
$_appLang = $app['lang_code'] ?? 'ar';
$_isRtl   = in_array($_appLang, ['ar','fa','ur','he','yi','dv','ps','sd','ug','ks'], true);
$_htmlDir = $_isRtl ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?= h($_appLang) ?>" dir="<?= $_htmlDir ?>">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?php
    if ($app['seo_title']) echo h($app['seo_title']);
    else {
        $vtag = $app['version'] ? ' ' . h($app['version']) : '';
        printf('%s%s لـ Android - تحميل APK الموثوق | yassota', h($app['name']), $vtag);
    }
  ?></title>
  <meta name="description" content="<?= h($app['meta_description'] ?: "تحميل " . $app['name'] . ($app['version'] ? " v{$app['version']}" : '') . " لـ Android مجاناً — " . ($app['short_description'] ?: "أحدث إصدار APK آمن وموثوق متاح على yassota.com")) ?>">
  <?php if ($app['keywords']): ?><meta name="keywords" content="<?= h($app['keywords']) ?>"><?php endif; ?>
  <?php
    // All published apps are indexable — the admin approved them for publication.
    // The old 150-char threshold blocked most new apps from Google indexing.
    $robotsApp = 'index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1';
  ?>
  <meta name="robots" content="<?= $robotsApp ?>">
  <link rel="canonical" href="<?= h(app_url($app['slug'])) ?>">
  <?php foreach ($langVersions as $lc => $lv): ?>
  <link rel="alternate" hreflang="<?= h($lc) ?>" href="<?= h(app_url($lv['slug'])) ?>">
  <?php endforeach; ?>
  <?php if (count($langVersions) > 1): ?>
  <link rel="alternate" hreflang="x-default" href="<?= h(app_url($parentApp ? $parentApp['slug'] : $app['slug'])) ?>">
  <?php endif; ?>
  <meta property="og:type" content="product">
  <meta property="og:title" content="<?= h($app['seo_title'] ?: $app['name']) ?>">
  <meta property="og:description" content="<?= h($app['meta_description'] ?: $app['short_description']) ?>">
  <?php if ($app['icon_path']): ?><meta property="og:image" content="<?= h(media_url($app['icon_path'])) ?>"><?php endif; ?>
  <meta property="og:url" content="<?= h(app_url($app['slug'])) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <script type="application/ld+json"><?= $schema ?></script>
  <script type="application/ld+json"><?= $breadcrumbSchema ?></script>
  <?php if ($faqSchema): ?><script type="application/ld+json"><?= $faqSchema ?></script><?php endif; ?>
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/detail.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189"
     crossorigin="anonymous"></script>
</head>
<body>

<?php render_site_header('', $app['cat_slug'] === 'games' ? 'games' : ($app['cat_slug'] === 'apps' ? 'apps' : 'home')); ?>


<div class="page-wrap">

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-section">
    <div class="sidebar-title">التنقل</div>
    <a href="/" class="sidebar-link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-4h4v4h4a1 1 0 001-1v-9"/></svg> الرئيسية</a>
    <?php if ($app['cat_slug']): ?>
    <a href="<?= h(url('category/' . $app['cat_slug'])) ?>" class="sidebar-link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="9" height="9" rx="2"/><rect x="13" y="2" width="9" height="9" rx="2"/><rect x="2" y="13" width="9" height="9" rx="2"/><rect x="13" y="13" width="9" height="9" rx="2"/></svg> <?= h($app['cat_name']) ?></a>
    <?php endif; ?>
  </div>
  <?php if ($relatedApps): ?>
  <div class="sidebar-section">
    <div class="sidebar-title">تطبيقات مشابهة</div>
    <?php foreach ($relatedApps as $r): ?>
    <a href="<?= h(app_url($r['slug'])) ?>" class="sidebar-link" style="gap:10px">
      <?php if ($r['icon_path']): ?>
        <img src="<?= h(media_url($r['icon_path'])) ?>" style="width:28px;height:28px;border-radius:6px;object-fit:cover;flex-shrink:0" alt="">
      <?php endif; ?>
      <span style="font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($r['name']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</aside>

<!-- Main -->
<main class="main-content">

  <!-- Breadcrumb -->
  <?php
  $bcItems = [['label' => 'الرئيسية', 'url' => url('')]];
  if ($app['cat_slug']) $bcItems[] = ['label' => $app['cat_name'], 'url' => url('category/' . $app['cat_slug'])];
  $bcItems[] = ['label' => $app['name']];
  render_breadcrumbs($bcItems);
  ?>
  <!-- Language switcher (shown when translations exist) -->
  <?php if (count($langVersions) > 1): ?>
  <div style="margin-bottom:12px;display:flex;flex-wrap:wrap;gap:8px;align-items:center">
    <span style="font-size:12px;color:var(--muted)">🌐 اللغات المتاحة:</span>
    <?php
    $langLabels = ['ar'=>'العربية','en'=>'English','ru'=>'Русский','fr'=>'Français','de'=>'Deutsch',
                   'es'=>'Español','tr'=>'Türkçe','id'=>'Indonesia','pt'=>'Português','ur'=>'اردو',
                   'hi'=>'हिन्दी','zh'=>'中文','ja'=>'日本語','ko'=>'한국어','it'=>'Italiano',
                   'nl'=>'Nederlands','fa'=>'فارسی','bn'=>'বাংলা','th'=>'ภาษาไทย','vi'=>'Tiếng Việt'];
    foreach ($langVersions as $lc => $lv):
      $active = $lc === ($app['lang_code'] ?? 'ar');
    ?>
    <a href="<?= h(app_url($lv['slug'])) ?>"
       style="padding:4px 10px;border-radius:99px;font-size:11px;font-weight:600;text-decoration:none;
              <?= $active ? 'background:var(--cyan);color:#fff' : 'background:var(--hover-bg);color:var(--muted);border:1px solid var(--border)' ?>">
      <?= h($langLabels[$lc] ?? strtoupper($lc)) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Editorial trust badge -->
  <div style="margin-bottom:14px;display:flex;align-items:center;flex-wrap:wrap;gap:8px">
    <span class="editorial-badge">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
      تمت مراجعته من قِبَل فريق yassota
    </span>
    <?php if ($app['updated_at']): ?>
    <span style="font-size:11px;color:var(--muted)">آخر تحديث: <?= date('d/m/Y', strtotime($app['updated_at'])) ?></span>
    <?php endif; ?>
  </div>

  <!-- App Hero -->
  <section class="app-hero reveal">
    <div class="app-hero-inner">
      <div class="app-hero-icon-wrap">
        <?php if ($app['icon_path']): ?>
          <img src="<?= h(media_url($app['icon_path'])) ?>" alt="<?= h($app['name']) ?>" class="app-hero-icon">
        <?php else: ?>
          <div class="app-hero-icon" style="background:linear-gradient(135deg,#e8ecf3,#dde3ec);display:flex;align-items:center;justify-content:center">
            <?= svgi('android') ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="app-hero-info">
        <h1 class="app-hero-name"><?= h($app['name']) ?></h1>
        <div class="app-hero-developer"><?php if ($app['developer']): ?><a href="<?= h(url('developer/' . rawurlencode($app['developer']))) ?>" style="color:inherit"><?= h($app['developer']) ?></a><?php endif; ?></div>

        <div class="app-badges">
          <?php if ($app['cat_name']): ?><span class="badge badge-cyan"><?= h($app['cat_name']) ?></span><?php endif; ?>
          <?php if ($app['version']): ?><span class="badge badge-purple" style="font-family:var(--f-mono)">v<?= h($app['version']) ?></span><?php endif; ?>
          <span class="badge badge-gold"><?= svgi('star') ?> <?= h($app['rating']) ?></span>
          <?php
            $b = $app['badge'] ?? '';
            if (!$b && !empty($app['created_at']) && strtotime($app['created_at']) > strtotime('-7 days')) $b = 'new';
            $bmap = ['new'=>['🆕 '.__('badge_new'),'#16a34a','rgba(22,163,74,.12)'],'updated'=>['🔄 '.__('badge_updated'),'#2563eb','rgba(37,99,235,.1)'],'hot'=>['🔥 '.__('badge_hot'),'#dc2626','rgba(220,38,38,.1)'],'choice'=>['⭐ '.__('badge_choice'),'#b45309','rgba(245,158,11,.1)']];
            if (isset($bmap[$b])): [$bt,$bc,$bb] = $bmap[$b]; ?>
          <span class="badge" style="background:<?= $bb ?>;color:<?= $bc ?>;font-weight:700"><?= $bt ?></span>
          <?php endif; ?>
          <?php if ($app['license']): ?><span class="badge badge-cyan"><?= h($app['license']) ?></span><?php endif; ?>
          <?php if (!empty($app['apk_path']) && in_array($app['download_source']??'playstore', ['apk','both'])): ?>
          <span class="badge" style="background:rgba(25,135,84,.12);color:#198754;border:1px solid rgba(25,135,84,.25)">📦 <?= __('hosted_apk') ?></span>
          <?php endif; ?>
        </div>

        <div class="app-hero-actions">
          <?php $hasDlMethod = !empty($app['download_url']) || !empty($app['apk_path']); ?>
          <?php if ($hasDlMethod): ?>
          <a href="<?= h(download_url($app['slug'])) ?>" class="btn-download-hero" data-hardnav="1">
            <?= svgi('download') ?> <?= __('free_download') ?>
          </a>
          <?php endif; ?>
          <?php if (!empty($app['playstore_url'])): ?>
          <a href="<?= h($app['playstore_url']) ?>" target="_blank" rel="nofollow noopener" class="btn-mirror" title="فتح صفحة التطبيق على Google Play">
            <?= svgi('playstore') ?> Google Play
          </a>
          <?php endif; ?>
          <?php if ($app['mirror2_url']): ?>
          <a href="<?= h(download_url($app['slug'], 2)) ?>" class="btn-mirror" data-hardnav="1"><?= svgi('mirror') ?> <?= __('mirror_2') ?></a>
          <?php endif; ?>
          <?php if ($app['mirror3_url']): ?>
          <a href="<?= h(download_url($app['slug'], 3)) ?>" class="btn-mirror" data-hardnav="1"><?= svgi('mirror') ?> <?= __('mirror_3') ?></a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?= wave() ?>

  <!-- Ad -->
  <div class="ad-zone"><?= ad_slot() ?></div>

  <!-- Meta Info Grid -->
  <div class="app-meta-grid reveal">
    <?php
    // Defensive: this field is meant to hold a short version string (e.g. "12.8.0"),
    // but old/manually-entered data sometimes has a full Play Store URL pasted into
    // it by mistake — never render that as a "version".
    $playStoreVersion = $app['play_store_version'] ?? '';
    if (str_contains($playStoreVersion, '://')) $playStoreVersion = null;

    $pkgName = $app['package_name'] ?? '';
    $metas = [
        ['label'=>__('developer'),  'val'=>$app['developer'],                                              'class'=>'', 'link'=>$app['developer'] ? url('developer/'.rawurlencode($app['developer'])) : null],
        ['label'=>__('version'),    'val'=>$app['version'],                                               'class'=>'version', 'link'=>null],
        ['label'=>'Play ' . __('version'), 'val'=>$playStoreVersion,                                     'class'=>'version', 'link'=>null],
        ['label'=>__('android'),    'val'=>$app['android_version'],                                       'class'=>'', 'link'=>null],
        ['label'=>__('size'),       'val'=>(!empty($app['apk_size_bytes']) ? format_apk_size((int)$app['apk_size_bytes']) : ($app['size_mb'] ? $app['size_mb'].' MB' : null)), 'class'=>'size', 'link'=>null],
        ['label'=>'License',        'val'=>$app['license'],                                               'class'=>'', 'link'=>null],
        ['label'=>'Package',        'val'=>$pkgName ?: null,                                              'class'=>'', 'link'=>$pkgName ? 'https://play.google.com/store/apps/details?id='.rawurlencode($pkgName) : null, 'mono'=>true, 'external'=>true],
        ['label'=>'Downloads',      'val'=>$app['downloads'] > 0 ? number_format($app['downloads']) : null,'class'=>'', 'link'=>null],
        ['label'=>'Last Update',    'val'=>!empty($app['apk_uploaded_at']) ? date('Y-m-d', strtotime($app['apk_uploaded_at'])) : null, 'class'=>'', 'link'=>null],
    ];
    foreach ($metas as $m): if (!$m['val']) continue; ?>
    <div class="meta-item reveal">
      <div class="meta-item-label"><?= h($m['label']) ?></div>
      <div class="meta-item-value <?= $m['class'] ?>" <?= !empty($m['mono']) ? 'style="font-size:11px;word-break:break-all"' : '' ?>>
        <?php if ($m['link']): ?>
          <a href="<?= h($m['link']) ?>" <?= !empty($m['external']) ? 'target="_blank" rel="nofollow noopener"' : '' ?> style="color:inherit;text-decoration:underline;text-underline-offset:3px"><?= h($m['val']) ?></a>
        <?php else: ?>
          <?= h($m['val']) ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- APK Security Hash Section -->
  <?php if (!empty($app['apk_hash_sha256']) && in_array($app['download_source']??'playstore', ['apk','both'])): ?>
  <div class="section-box reveal" style="padding:16px 20px">
    <details>
      <summary style="cursor:pointer;font-size:13px;font-weight:600;list-style:none;display:flex;align-items:center;gap:8px;user-select:none">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        التحقق من سلامة الملف (SHA-256 / MD5)
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:auto"><polyline points="6 9 12 15 18 9"/></svg>
      </summary>
      <div style="margin-top:12px;font-size:12px;line-height:2">
        <table style="width:100%;border-collapse:collapse">
          <?php if ($app['apk_size_bytes']): ?>
          <tr><td style="color:var(--muted);width:120px">الحجم الدقيق</td><td><strong><?= h(format_apk_size((int)$app['apk_size_bytes'])) ?></strong> (<?= number_format((int)$app['apk_size_bytes']) ?> بايت)</td></tr>
          <?php endif; ?>
          <tr>
            <td style="color:var(--muted);vertical-align:top;padding-top:4px">SHA-256</td>
            <td>
              <code style="font-size:10px;word-break:break-all;background:var(--bg-code,#f4f4f5);padding:3px 6px;border-radius:5px;cursor:pointer;display:block;line-height:1.6"
                    title="انقر للنسخ"
                    onclick="navigator.clipboard.writeText(this.textContent.trim()).then(()=>{this.style.color='#198754';setTimeout(()=>this.style.color='',2000)})"><?= h($app['apk_hash_sha256']) ?></code>
              <span style="font-size:10px;color:var(--muted)">انقر لنسخ البصمة الرقمية</span>
            </td>
          </tr>
          <?php if ($app['apk_hash_md5']): ?>
          <tr>
            <td style="color:var(--muted)">MD5</td>
            <td>
              <code style="font-size:11px;background:var(--bg-code,#f4f4f5);padding:3px 8px;border-radius:5px;cursor:pointer"
                    onclick="navigator.clipboard.writeText(this.textContent.trim()).then(()=>{this.style.color='#198754';setTimeout(()=>this.style.color='',2000)})"><?= h($app['apk_hash_md5']) ?></code>
            </td>
          </tr>
          <?php endif; ?>
          <?php if ($app['apk_uploaded_at']): ?>
          <tr><td style="color:var(--muted)">تاريخ الرفع</td><td><?= h(date('Y-m-d', strtotime($app['apk_uploaded_at']))) ?></td></tr>
          <?php endif; ?>
        </table>
        <p style="margin:8px 0 0;color:var(--muted);font-size:11px">
          ✅ ملف APK مستضاف مباشرةً على خوادم yassota — بإمكانك التحقق من البصمة الرقمية بأي أداة تحقق SHA-256 للتأكد من عدم التلاعب.
        </p>
      </div>
    </details>
  </div>
  <?php endif; ?>

  <!-- App Permissions Section -->
  <?php
  $permRaw = $app['permissions'] ?? '';
  $permList = [];
  if ($permRaw) {
      $decoded = json_decode($permRaw, true);
      if (is_array($decoded)) $permList = $decoded;
      elseif (is_string($permRaw)) $permList = array_filter(array_map('trim', explode(',', $permRaw)));
  }
  $permLabels = [
      'INTERNET'                         => ['الإنترنت',             '🌐', 'يتصل بالإنترنت'],
      'ACCESS_NETWORK_STATE'             => ['حالة الشبكة',          '📶', 'يفحص حالة اتصال الشبكة'],
      'ACCESS_WIFI_STATE'                => ['Wi-Fi',                '📡', 'يفحص اتصال Wi-Fi'],
      'CAMERA'                           => ['الكاميرا',             '📷', 'يصل للكاميرا'],
      'RECORD_AUDIO'                     => ['الميكروفون',           '🎤', 'يسجّل الصوت'],
      'READ_EXTERNAL_STORAGE'            => ['قراءة الملفات',        '📂', 'يقرأ ملفات التخزين الخارجي'],
      'WRITE_EXTERNAL_STORAGE'           => ['كتابة الملفات',        '💾', 'يكتب على التخزين الخارجي'],
      'READ_CONTACTS'                    => ['جهات الاتصال',         '👤', 'يصل لجهات الاتصال'],
      'WRITE_CONTACTS'                   => ['تعديل جهات الاتصال',  '✏️', 'يُعدّل جهات الاتصال'],
      'ACCESS_FINE_LOCATION'             => ['الموقع الدقيق',        '📍', 'يصل للموقع الجغرافي الدقيق (GPS)'],
      'ACCESS_COARSE_LOCATION'           => ['الموقع التقريبي',      '🗺️', 'يصل للموقع الجغرافي التقريبي'],
      'READ_CALL_LOG'                    => ['سجل المكالمات',        '📞', 'يقرأ سجل المكالمات'],
      'CALL_PHONE'                       => ['إجراء مكالمات',        '📲', 'يُجري مكالمات هاتفية'],
      'SEND_SMS'                         => ['إرسال SMS',            '💬', 'يُرسل رسائل نصية'],
      'READ_SMS'                         => ['قراءة SMS',            '📩', 'يقرأ الرسائل النصية'],
      'RECEIVE_BOOT_COMPLETED'           => ['التشغيل التلقائي',     '🔁', 'يعمل تلقائياً عند إقلاع الجهاز'],
      'VIBRATE'                          => ['الاهتزاز',             '📳', 'يُشغّل اهتزاز الجهاز'],
      'FLASHLIGHT'                       => ['المصباح',              '🔦', 'يتحكم في كشاف الإضاءة'],
      'USE_BIOMETRIC'                    => ['بصمة / تعرف وجه',     '🔐', 'يستخدم المصادقة البيومترية'],
      'USE_FINGERPRINT'                  => ['بصمة الإصبع',         '👆', 'يستخدم قارئ البصمة'],
      'BLUETOOTH'                        => ['Bluetooth',            '🔵', 'يتصل بأجهزة Bluetooth'],
      'NFC'                              => ['NFC',                  '📳', 'يستخدم دفع / قراءة NFC'],
      'GET_ACCOUNTS'                     => ['الحسابات',             '🔑', 'يصل للحسابات المسجّلة على الجهاز'],
      'READ_MEDIA_IMAGES'                => ['الصور',                '🖼️', 'يقرأ صور المعرض'],
      'READ_MEDIA_VIDEO'                 => ['الفيديوهات',           '🎬', 'يقرأ ملفات الفيديو'],
      'READ_MEDIA_AUDIO'                 => ['الصوتيات',             '🎵', 'يقرأ ملفات الصوت'],
      'SCHEDULE_EXACT_ALARM'             => ['المنبهات الدقيقة',     '⏰', 'يُجدول تنبيهات في وقت محدد'],
      'POST_NOTIFICATIONS'               => ['الإشعارات',            '🔔', 'يُرسل إشعارات للجهاز'],
      'FOREGROUND_SERVICE'               => ['خدمة مستمرة',         '⚙️', 'يعمل في الخلفية بشكل مستمر'],
      'REQUEST_INSTALL_PACKAGES'         => ['تثبيت تطبيقات',       '📦', 'يمكنه تثبيت تطبيقات أخرى'],
      'SYSTEM_ALERT_WINDOW'              => ['نافذة فوق التطبيقات', '🪟', 'يعرض نوافذ فوق التطبيقات الأخرى'],
      'MANAGE_EXTERNAL_STORAGE'          => ['إدارة كامل التخزين',  '🗂️', 'وصول كامل لكل الملفات (مدير ملفات)'],
      'CHANGE_WIFI_STATE'                => ['تغيير Wi-Fi',         '🔄', 'يتصل أو يقطع اتصال Wi-Fi'],
      'CHANGE_NETWORK_STATE'             => ['تغيير الشبكة',        '🔄', 'يُعدّل إعدادات الشبكة'],
      'WAKE_LOCK'                        => ['إبقاء الشاشة نشطة',   '🌙', 'يمنع إطفاء الشاشة تلقائياً'],
  ];
  if ($permList): ?>
  <div class="section-box reveal" style="padding:16px 20px;margin-bottom:16px">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      <span style="font-size:13px;font-weight:700">الصلاحيات المطلوبة</span>
      <span style="font-size:11px;color:var(--muted);margin-right:auto"><?= count($permList) ?> صلاحية</span>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
      <?php foreach ($permList as $perm):
        $key = strtoupper(str_replace('android.permission.', '', trim($perm)));
        $info = $permLabels[$key] ?? [str_replace(['android.permission.','.'], ['', ' '], $perm), '🔹', ''];
        $dangerClass = in_array($key, ['CAMERA','RECORD_AUDIO','ACCESS_FINE_LOCATION','READ_CONTACTS','READ_SMS','SEND_SMS','CALL_PHONE','READ_CALL_LOG','GET_ACCOUNTS','REQUEST_INSTALL_PACKAGES','MANAGE_EXTERNAL_STORAGE','SYSTEM_ALERT_WINDOW'], true);
      ?>
      <div title="<?= h($info[2] ?: $info[0]) ?>" style="display:flex;align-items:center;gap:5px;padding:5px 10px;border-radius:20px;font-size:11px;font-weight:500;cursor:default;
        background:<?= $dangerClass ? 'rgba(239,68,68,.07)' : 'rgba(6,182,212,.06)' ?>;
        border:1px solid <?= $dangerClass ? 'rgba(239,68,68,.2)' : 'rgba(6,182,212,.15)' ?>;
        color:<?= $dangerClass ? 'var(--danger)' : 'var(--muted)' ?>">
        <span><?= $info[1] ?></span>
        <span><?= h($info[0]) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (count(array_filter($permList, fn($p) => in_array(strtoupper(str_replace('android.permission.','',$p)),['CAMERA','RECORD_AUDIO','ACCESS_FINE_LOCATION','READ_CONTACTS','READ_SMS','SEND_SMS','CALL_PHONE','REQUEST_INSTALL_PACKAGES','MANAGE_EXTERNAL_STORAGE'],true)))): ?>
    <p style="font-size:11px;color:var(--muted);margin-top:10px">
      <span style="color:var(--danger)">●</span> صلاحيات باللون الأحمر قد تكون حساسة — تأكد من فهم سبب حاجة التطبيق لها قبل المنح.
    </p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?= wave() ?>

  <!-- Screenshots -->
  <?php if ($screenshots): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title"><?= __('screenshots') ?></span></div>
    <div class="screenshots-scroll">
      <?php foreach ($screenshots as $i => $ss): $ssUrl = (str_starts_with($ss,'http')||str_starts_with($ss,'//')) ? $ss : media_url($ss); ?>
      <div class="screenshot-thumb">
        <img src="<?= h($ssUrl) ?>" alt="<?= h($app['name']) ?> screenshot <?= $i+1 ?>" loading="lazy">
        <div class="screenshot-overlay"><?= svgi('zoom') ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <!-- Lightbox -->
  <div class="lightbox" role="dialog" aria-modal="true">
    <img class="lightbox-img" src="" alt="screenshot">
    <button class="lightbox-close" aria-label="إغلاق"><?= svgi('close') ?></button>
    <button class="lightbox-nav lightbox-prev" aria-label="السابق"><?= svgi('arrow-l') ?></button>
    <button class="lightbox-nav lightbox-next" aria-label="التالي"><?= svgi('arrow-r') ?></button>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- Description with mid-download button (Button 2, Step 2 of 3-step funnel) -->
  <?php if ($longDescription !== ''): ?>
  <?php
  // Split description at natural paragraph boundary near midpoint for Button 2 injection
  $descFirst = $longDescription; $descSecond = '';
  if ($hasMidBtn) {
      $mid = (int)(mb_strlen($longDescription) / 2);
      // Find paragraph break near midpoint (search ±300 chars)
      $window = mb_substr($longDescription, max(0,$mid-300), 600);
      $nlPos = strrpos($window, "\n\n");
      $splitAt = $nlPos !== false ? max(0,$mid-300) + $nlPos + 2 : $mid;
      $descFirst  = mb_substr($longDescription, 0, $splitAt);
      $descSecond = mb_substr($longDescription, $splitAt);
  }
  ?>
  <?php $descIsLong = mb_strlen($descFirst) > 480; ?>
  <div class="section-box">
    <div class="section-head"><span class="section-title"><?= __('description') ?></span></div>
    <div class="<?= $descIsLong ? 'app-desc-clamp' : '' ?>" id="app-desc-body" style="color:var(--muted);font-size:14px;line-height:1.85">
      <?= nl2br(h($descFirst)) ?>
    </div>
    <?php if ($descIsLong): ?>
    <button type="button" class="btn-desc-toggle" id="btn-desc-more" onclick="(function(b,w){var exp=w.classList.toggle('expanded');b.classList.toggle('expanded',exp);b.innerHTML=exp?'<svg width=&quot;14&quot; height=&quot;14&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;2.5&quot;><polyline points=&quot;18 15 12 9 6 15&quot;/></svg> عرض أقل':'<svg width=&quot;14&quot; height=&quot;14&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;2.5&quot;><polyline points=&quot;6 9 12 15 18 9&quot;/></svg> اقرأ المزيد';})(this,document.getElementById('app-desc-body'))">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
      اقرأ المزيد
    </button>
    <?php endif; ?>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- Features -->
  <?php if ($features): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title"><?= __('features') ?></span></div>
    <div class="features-grid">
      <?php foreach ($features as $i => $feat): ?>
      <div class="feature-card reveal">
        <div class="feature-card-icon">
          <?php
          $featureIcons = ['<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
          '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>',
          '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>',
          '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
          ];
          echo $featureIcons[$i % count($featureIcons)];
          ?>
        </div>
        <div class="feature-card-title"><?= h($feat) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- Pros / Cons -->
  <?php if ($pros || $cons): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title"><?= __('pros_cons') ?></span></div>
    <div class="pros-cons-grid">
      <div class="pros-box">
        <div class="pros-cons-title pros">
          <?= svgi('check') ?> الإيجابيات
        </div>
        <ul class="pros-cons-list">
          <?php foreach ($pros as $p): ?><li><span><?= h($p) ?></span></li><?php endforeach; ?>
        </ul>
      </div>
      <div class="cons-box">
        <div class="pros-cons-title cons">
          <?= svgi('x') ?> السلبيات
        </div>
        <ul class="pros-cons-list">
          <?php foreach ($cons as $c): ?><li><span><?= h($c) ?></span></li><?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- Ad -->
  <div class="ad-zone"><?= ad_slot() ?></div>

  <!-- Install Steps -->
  <?php if ($installSteps): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title"><?= __('install_steps') ?></span></div>
    <div class="install-steps">
      <?php foreach ($installSteps as $i => $step): ?>
      <div class="install-step reveal">
        <div class="step-num"><?= $i + 1 ?></div>
        <div class="step-body">
          <div class="step-title"><?= h($step) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- What's New -->
  <?php if ($whatsNew !== ''): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title"><?= __('whats_new') ?></span></div>
    <p style="color:var(--muted);font-size:14px;line-height:1.8"><?= nl2br(h($whatsNew)) ?></p>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- FAQ -->
  <?php if ($faq): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title"><?= __('faq') ?></span></div>
    <div class="faq-list">
      <?php foreach ($faq as $i => $item): ?>
      <div class="faq-item <?= $i === 0 ? 'open' : '' ?>">
        <div class="faq-q">
          <?= svgi('q') ?>
          <?= h($item['q'] ?? '') ?>
          <span class="faq-arrow"><?= svgi('chevron') ?></span>
        </div>
        <div class="faq-a"><?= h($item['a'] ?? '') ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- Changelog / Version History + Comparison -->
  <?php if ($versionHistory): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title"><?= __('version_history') ?></span></div>
    <div style="overflow-x:auto">
      <table class="admin-table" style="width:100%;min-width:480px">
        <thead><tr><th>الإصدار</th><th>التاريخ</th><th>التغييرات</th><th></th></tr></thead>
        <tbody>
          <tr style="background:rgba(37,99,235,.04)">
            <td style="font-family:var(--f-mono);color:var(--cyan)">v<?= h($app['version']) ?> (الحالي)</td>
            <td style="color:var(--muted);font-size:12px"><?= h(time_ago($app['updated_at'])) ?></td>
            <td style="color:var(--muted);font-size:13px"><?= h(mb_strimwidth($whatsNew, 0, 120, '...')) ?></td>
            <td><a href="<?= h(download_url($app['slug'])) ?>" class="btn-edit" data-hardnav="1">تحميل</a></td>
          </tr>
          <?php foreach ($versionHistory as $v): ?>
          <tr>
            <td style="font-family:var(--f-mono)">v<?= h($v['version'] ?: '—') ?></td>
            <td style="color:var(--muted);font-size:12px"><?= h(time_ago($v['created_at'])) ?></td>
            <td style="color:var(--muted);font-size:13px"><?= h(mb_strimwidth(clean_long_text($v['changelog'] ?? ''), 0, 120, '...')) ?></td>
            <td><?php if ($v['download_url']): ?><a href="<?= h(download_url($app['slug'])) ?>?ver=<?= (int)$v['id'] ?>" class="btn-view" data-hardnav="1">تحميل هذا الإصدار</a><?php endif; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- Download zone — direct APKPure-style button -->
  <div class="quick-actions-zone">
    <?php if ($hasDlMethod): ?>
    <a href="<?= h(download_url($app['slug'])) ?>" class="btn-download-hero" data-hardnav="1"
       style="display:inline-flex;width:100%;max-width:340px;justify-content:center;font-size:16px;padding:15px 28px">
      <?= svgi('download') ?> <?= __('download') ?> <?= h($app['name']) ?><?= $app['version'] ? ' v'.h($app['version']) : '' ?>
    </a>
    <?php endif; ?>

    <?php $dlVersions = array_filter($versionHistory, fn($v) => !empty($v['download_url'])); ?>
    <?php if ($dlVersions): ?>
    <details class="prev-versions-slim">
      <summary>📦 الإصدارات السابقة (<?= count($dlVersions) ?>)</summary>
      <div class="prev-versions-list">
        <?php foreach ($dlVersions as $v): ?>
        <a href="<?= h(download_url($app['slug'])) ?>?ver=<?= (int)$v['id'] ?>" class="prev-version-chip" data-hardnav="1">
          <span class="pv-ver">v<?= h($v['version'] ?: '—') ?></span>
          <span class="pv-date"><?= h(time_ago($v['created_at'])) ?></span>
          <?= svgi('download') ?>
        </a>
        <?php endforeach; ?>
      </div>
    </details>
    <?php endif; ?>

    <?php if ($tgUrl): ?>
    <a href="<?= h($tgUrl) ?>" target="_blank" rel="nofollow noopener" class="btn-telegram-sub">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.869 4.326-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.829.941z"/></svg>
      اشترك في قناة yassota على تيليجرام
    </a>
    <?php endif; ?>
  </div>

  <!-- Comments & Ratings -->
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title"><?= __('ratings_reviews') ?> (<?= $commentCount ?>)</span></div>

    <?php if ($comments): ?>
    <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px">
      <?php foreach ($comments as $c): ?>
      <div style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:12px;padding:14px 16px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
          <span style="font-weight:700;font-size:13px"><?= h($c['name']) ?></span>
          <span style="color:#fbbf24;font-family:var(--f-mono);font-size:12px"><?= str_repeat('★', (int)$c['rating']) . str_repeat('☆', 5 - (int)$c['rating']) ?></span>
        </div>
        <p style="color:var(--muted);font-size:13px;line-height:1.7;margin:0"><?= nl2br(h($c['body'])) ?></p>
        <div style="color:var(--muted);font-size:11px;margin-top:6px"><?= h(time_ago($c['created_at'])) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p style="color:var(--muted);font-size:13px;margin-bottom:20px">لا توجد تقييمات بعد — كن أول من يقيّم هذا التطبيق.</p>
    <?php endif; ?>

    <?php if ($commentSubmitted): ?>
      <div style="background:rgba(0,230,118,.1);border:1px solid rgba(0,230,118,.25);color:var(--success);padding:14px 18px;border-radius:10px">
        ✅ شكراً لك، تم استلام تقييمك وسيظهر بعد المراجعة.
      </div>
    <?php else: ?>
      <?php if ($commentError): ?>
        <div style="background:rgba(255,68,102,.1);border:1px solid rgba(255,68,102,.25);color:var(--danger);padding:14px 18px;border-radius:10px;margin-bottom:14px"><?= h($commentError) ?></div>
      <?php endif; ?>
      <div id="comment-form-slot" data-app-slug="<?= h($app['slug']) ?>">
        <p style="color:var(--muted);font-size:13px">⏳ جاري تحميل نموذج التقييم...</p>
      </div>
    <?php endif; ?>
  </div>
  <?= wave() ?>

  <!-- What this app offers -->
  <?php if ($offersText !== ''): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title">ماذا يقدّم <?= h($app['name']) ?>؟</span></div>
    <div style="color:var(--muted);font-size:14px;line-height:1.85"><?= nl2br(h($offersText)) ?></div>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- Privacy Policy -->
  <?php if ($privacyPolicy !== ''): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title"><?= __('privacy') ?></span></div>
    <div style="color:var(--muted);font-size:14px;line-height:1.85"><?= nl2br(h($privacyPolicy)) ?></div>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- Terms of Use -->
  <?php if ($termsContent !== ''): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title"><?= __('terms') ?></span></div>
    <div style="color:var(--muted);font-size:14px;line-height:1.85"><?= nl2br(h($termsContent)) ?></div>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- Related Articles -->
  <?php if ($relatedArticles): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title"><?= __('related_articles') ?> — <?= h($app['name']) ?></span></div>
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php foreach ($relatedArticles as $art): ?>
      <a href="<?= h(article_url($art['slug'])) ?>" class="sidebar-link" style="border:1px solid var(--border-c);border-radius:10px;padding:14px 16px">
        <?= svgi('arrow-l') ?> <span style="flex:1"><?= h($art['title']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- Related Apps -->
  <?php if ($relatedApps): ?>
  <div class="section-head reveal"><span class="section-title"><?= __('similar_apps') ?></span></div>
  <div class="apps-grid">
    <?php foreach ($relatedApps as $r): ?>
    <div class="app-card reveal">
      <a class="app-card-inner" href="<?= h(app_url($r['slug'])) ?>" data-hardnav="1">
        <div class="app-card-body" style="padding-top:14px">
          <?php if ($r['icon_path']): ?>
            <img src="<?= h(media_url($r['icon_path'])) ?>" alt="<?= h($r['name']) ?>" class="app-card-icon" loading="lazy" style="margin:0 auto 10px">
          <?php else: ?>
            <div class="app-card-icon-placeholder" style="margin:0 auto 10px"></div>
          <?php endif; ?>
          <div class="app-card-name"><?= h($r['name']) ?></div>
          <div class="app-card-meta">
            <div class="app-card-rating"><?= svgi('star') ?> <?= h($r['rating']) ?></div>
          </div>
        </div>
      </a>
      <a href="<?= h(download_url($r['slug'])) ?>" class="btn-dl-card" data-hardnav="1">
        <?= svgi('download') ?> تحميل
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Alternatives -->
  <?php if ($alternatives || $devAlternatives): ?>
  <div class="section-head reveal"><span class="section-title"><?= __('alternatives') ?></span></div>
  <div class="apps-grid">
    <?php foreach (array_merge($alternatives, $devAlternatives) as $r): ?>
    <div class="app-card reveal">
      <a class="app-card-inner" href="<?= h(app_url($r['slug'])) ?>" data-hardnav="1">
        <div class="app-card-body" style="padding-top:14px">
          <?php if ($r['icon_path']): ?>
            <img src="<?= h(media_url($r['icon_path'])) ?>" alt="<?= h($r['name']) ?>" class="app-card-icon" loading="lazy" style="margin:0 auto 10px">
          <?php else: ?>
            <div class="app-card-icon-placeholder" style="margin:0 auto 10px"></div>
          <?php endif; ?>
          <div class="app-card-name"><?= h($r['name']) ?></div>
          <div class="app-card-meta">
            <div class="app-card-rating"><?= svgi('star') ?> <?= h($r['rating']) ?></div>
          </div>
        </div>
      </a>
      <a href="<?= h(download_url($r['slug'])) ?>" class="btn-dl-card" data-hardnav="1">
        <?= svgi('download') ?> تحميل
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</main>
</div>

<?php render_site_footer(); ?>

<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
<script>navigator.sendBeacon('<?= h(url('track-view.php')) ?>?id=<?= (int)$app['id'] ?>');</script>
</body>
</html>
<?php if ($cacheable) page_cache_end($pdo, $_cacheKey); ?>
