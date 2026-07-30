<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

evil_check_ban($pdo);
waf_check($pdo);

// ── Legacy URL 301 redirects ─────────────────────────────────────────────────
// Old query-string format indexed by Google: ?page=app&id=N, ?page=apps, etc.
if (!empty($_GET['page'])) {
    $legacyPage = $_GET['page'];
    if ($legacyPage === 'app' && !empty($_GET['id'])) {
        $legacyId = (int)$_GET['id'];
        $legacyStmt = $pdo->prepare("SELECT slug FROM apps WHERE id=? AND status='published' LIMIT 1");
        $legacyStmt->execute([$legacyId]);
        $legacySlug = $legacyStmt->fetchColumn();
        if ($legacySlug) {
            header('Location: ' . app_url($legacySlug), true, 301);
        } else {
            header('Location: ' . url(''), true, 301);
        }
        exit;
    }
    // ?page=apps, ?page=orders, ?page=home, any other legacy page → homepage
    $legacyToHome = ['apps','games','orders','home','index','search','categories','all'];
    if (in_array(strtolower($legacyPage), $legacyToHome, true) || ctype_alpha($legacyPage)) {
        header('Location: ' . url(''), true, 301);
        exit;
    }
}

/* ── Multi-site routing ── */
$_multisite = detect_multisite_domain($pdo);
if ($_multisite) {
    $siteMode = $_multisite['site_mode'] ?? 'redirect';
    if ($siteMode === 'redirect') {
        header('Location: ' . SITE_URL, true, 301); exit;
    }
    if ($siteMode === 'category' && !empty($_multisite['category_slug'])) {
        $_GET['cat'] = $_multisite['category_slug'];
    }
}

/* ── Data ── */
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;
$catSlug = trim($_GET['cat'] ?? '');
$search  = trim($_GET['q'] ?? '');

$cacheable = ($search === '' && !detect_lang_from_subdomain() && !$_multisite);
if ($cacheable && page_cache_start($pdo, $_SERVER['REQUEST_URI'])) exit;

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();

// Subdomain language: on en.yassota.com show only English translations
$subdomainLang = detect_lang_from_subdomain();
// Only show original (non-translated) apps on homepage — translations are separate pages
// Exception: language subdomains show translated versions
if ($subdomainLang) {
    $where  = "WHERE a.status='published' AND a.lang_code=? AND a.parent_id IS NOT NULL";
    $params = [$subdomainLang];
} else {
    $where  = "WHERE a.status='published' AND (a.parent_id IS NULL OR a.parent_id = 0)";
    $params = [];
}
if ($catSlug && $catSlug !== 'all') {
    $where .= " AND c.slug = ?"; $params[] = $catSlug;
}
if ($search !== '') {
    $where .= " AND (a.name LIKE ? OR a.short_description LIKE ? OR a.developer LIKE ? OR a.keywords LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM apps a LEFT JOIN categories c ON a.category_id=c.id $where");
$countStmt->execute($params);
$totalApps  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalApps / $perPage));

$stmt = $pdo->prepare("SELECT a.*, c.name AS cat_name, c.slug AS cat_slug
    FROM apps a LEFT JOIN categories c ON a.category_id=c.id
    $where ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$apps = $stmt->fetchAll();

// Featured carousel: top 6 downloaded original (non-translated) apps
$featuredApps = $pdo->query("SELECT a.*, c.name AS cat_name FROM apps a LEFT JOIN categories c ON a.category_id=c.id WHERE a.status='published' AND (a.parent_id IS NULL OR a.parent_id=0) ORDER BY a.downloads DESC LIMIT 6")->fetchAll();
$featured = $featuredApps[0] ?? null;

// Latest blog posts for homepage
$latestPosts = [];
if (!$search && !$catSlug) {
    $latestPosts = $pdo->query("SELECT id,title,slug,type,excerpt,created_at FROM blog_posts WHERE status='published' ORDER BY created_at DESC LIMIT 3")->fetchAll();
}

$activeNav = $catSlug === 'games' ? 'games' : ($catSlug === 'apps' ? 'apps' : 'home');
$siteName  = 'yassota';
$siteHost  = parse_url(SITE_URL, PHP_URL_HOST) ?: 'yassota.com';

$websiteSchema = json_encode([
    "@context" => "https://schema.org", "@type" => "WebSite",
    "name" => "yassota", "url" => url(''),
    "description" => "دليل تحريري عربي مستقل لاكتشاف ومراجعة تطبيقات وألعاب أندرويد",
    "potentialAction" => [
        "@type" => "SearchAction",
        "target" => url('') . '?q={search_term_string}',
        "query-input" => "required name=search_term_string",
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$faqSchemaHome = json_encode([
    "@context" => "https://schema.org", "@type" => "FAQPage",
    "mainEntity" => [
        ["@type"=>"Question","name"=>"هل yassota موقع رسمي من Google Play؟",
         "acceptedAnswer"=>["@type"=>"Answer","text"=>"لا، yassota دليل تحريري مستقل ولا ينتمي لأي متجر تطبيقات. نراجع التطبيقات بموضوعية ونضع روابط Google Play الرسمية للتحميل."]],
        ["@type"=>"Question","name"=>"هل روابط التحميل على yassota آمنة؟",
         "acceptedAnswer"=>["@type"=>"Answer","text"=>"نعم، نتحقق من كل رابط قبل نشره ونعتمد بشكل أساسي على Google Play. الملفات APK المباشرة تُفحص بأدوات اكتشاف الفيروسات ونعرض نتيجة الفحص."]],
        ["@type"=>"Question","name"=>"كيف أضيف تقييمي لتطبيق على yassota؟",
         "acceptedAnswer"=>["@type"=>"Answer","text"=>"افتح صفحة التطبيق وانتقل لقسم التعليقات في أسفل الصفحة، اختر عدد النجوم واكتب رأيك ثم أرسل. تخضع التعليقات لمراجعة قبل نشرها."]],
        ["@type"=>"Question","name"=>"هل yassota مجاني؟",
         "acceptedAnswer"=>["@type"=>"Answer","text"=>"نعم، yassota مجاني تماماً للمستخدمين. يعتمد الموقع على الإعلانات لتغطية تكاليف التشغيل ولا توجد رسوم مخفية."]],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$orgSchema = json_encode([
    "@context" => "https://schema.org", "@type" => "Organization",
    "name" => "yassota", "url" => url(''),
    "logo" => url('favicon.svg'),
    "description" => "دليل تحريري عربي مستقل لمراجعة تطبيقات أندرويد",
    "contactPoint" => ["@type" => "ContactPoint", "contactType" => "customer support", "url" => url('contact')],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($search ? "نتائج البحث: $search" : ($catSlug ? ucfirst($catSlug) . ' — yassota' : 'yassota — مراجعات وأدلة تطبيقات أندرويد')) ?></title>
  <meta name="description" content="<?= h($search ? "نتائج البحث عن: $search على yassota" : ($catSlug ? "تصفح وراجع أفضل تطبيقات $catSlug على منصة yassota العربية" : 'yassota — دليلك التحريري العربي المستقل لمراجعة واكتشاف أفضل تطبيقات وألعاب أندرويد. مراجعات احترافية، معلومات موثوقة، ومحتوى محدّث يومياً.')) ?>">
  <?php
    if ($search) {
        $idxRobots  = 'noindex,follow';
        $idxCanon   = h(url(''));
    } elseif ($catSlug) {
        $idxRobots  = 'index,follow,max-snippet:-1,max-image-preview:large';
        $idxCanon   = h(url('category/' . $catSlug));
    } else {
        $idxRobots  = 'index,follow,max-snippet:-1,max-image-preview:large';
        $idxCanon   = h(url(''));
    }
  ?>
  <meta name="robots" content="<?= $idxRobots ?>">
  <link rel="canonical" href="<?= $idxCanon ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="yassota">
  <meta property="og:title" content="yassota — دليل مراجعات تطبيقات أندرويد">
  <meta property="og:description" content="دليلك التحريري العربي المستقل لاكتشاف ومراجعة أفضل تطبيقات وألعاب أندرويد — محتوى محدّث يومياً.">
  <?php if ($featured && $featured['icon_path']): ?><meta property="og:image" content="<?= h(media_url($featured['icon_path'])) ?>"><?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="yassota — مراجعات تطبيقات أندرويد">
  <meta name="twitter:description" content="دليلك التحريري العربي لاكتشاف ومراجعة أفضل تطبيقات أندرويد">
  <script type="application/ld+json"><?= $websiteSchema ?></script>
  <script type="application/ld+json"><?= $orgSchema ?></script>
  <?php if (!$search && !$catSlug): ?><script type="application/ld+json"><?= $faqSchemaHome ?></script><?php endif; ?>
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189"
     crossorigin="anonymous"></script>
</head>
<body>

<?php render_site_header($search, $activeNav); ?>
<?php render_category_tabs_bar($pdo, $catSlug); ?>

<div class="page-wrap fw">

<!-- ══ MAIN ══ -->
<main class="main-content">

  <!-- Ad Zone Top -->
  <div class="ad-zone">
    <?= ad_slot() ?>
  </div>


  <!-- ── Featured Apps Carousel (editor's picks) ── -->
  <?php if (!empty($featuredApps) && !$search && !$catSlug): ?>
  <div class="featured-carousel reveal" id="featured-carousel">
    <div class="fc-stage" id="fc-stage">
      <?php foreach ($featuredApps as $fi => $fa): ?>
      <a href="<?= h(app_url($fa['slug'])) ?>" class="fc-slide<?= $fi === 0 ? ' active' : '' ?>" data-index="<?= $fi ?>" data-hardnav="1">
        <div class="fc-icon-wrap">
          <?php if ($fa['icon_path']): ?>
            <img src="<?= h(media_url($fa['icon_path'])) ?>" alt="<?= h($fa['name']) ?>" class="fc-icon" loading="lazy">
          <?php else: ?>
            <div class="fc-icon fc-icon-blank"><?= partial_icon('smartphone', 32) ?></div>
          <?php endif; ?>
        </div>
        <div class="fc-info">
          <div class="fc-badge"><?= partial_icon('award', 12) ?> اختيار المحرر<?= $fa['cat_name'] ? ' — ' . h($fa['cat_name']) : '' ?></div>
          <div class="fc-name"><?= h($fa['name']) ?></div>
          <div class="fc-desc"><?= h(mb_strimwidth($fa['short_description'] ?? '', 0, 120, '...')) ?></div>
          <span class="fc-cta"><?= partial_icon('info') ?> اقرأ المراجعة الكاملة</span>
        </div>
      </a>
      <?php endforeach; ?>
      <button class="fc-arrow fc-prev" id="fc-prev" aria-label="السابق"><?= partial_icon('chevron-r', 18) ?></button>
      <button class="fc-arrow fc-next" id="fc-next" aria-label="التالي"><?= partial_icon('chevron-l', 18) ?></button>
    </div>
    <div class="fc-footer">
      <div class="fc-progress-bar"><div class="fc-progress-fill" id="fc-progress"></div></div>
      <div class="fc-thumbs" id="fc-thumbs">
        <?php foreach ($featuredApps as $fi => $fa): ?>
        <button class="fc-thumb<?= $fi === 0 ? ' active' : '' ?>" data-index="<?= $fi ?>" aria-label="<?= h($fa['name']) ?>">
          <?php if ($fa['icon_path']): ?>
            <img src="<?= h(media_url($fa['icon_path'])) ?>" alt="<?= h($fa['name']) ?>" loading="lazy">
          <?php else: ?>
            <div class="fc-thumb-blank"></div>
          <?php endif; ?>
          <span class="fc-thumb-name"><?= h(mb_strimwidth($fa['name'], 0, 12, '…')) ?></span>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?= partial_wave() ?>
  <?php endif; ?>

  <!-- ── Apps Grid ── -->
  <div class="section-head reveal" id="apps-grid">
    <span class="section-title">
      <?= $search ? 'نتائج البحث: ' . h($search) : ($catSlug ? h($categories[array_search($catSlug, array_column($categories,'slug'))]['name'] ?? 'التطبيقات') : 'أحدث التطبيقات') ?>
    </span>
    <span style="font-family:var(--f-mono);font-size:12px;color:var(--muted)"><?= number_format($totalApps) ?> تطبيق</span>
  </div>

  <?php render_app_grid($apps, 'لا توجد نتائج' . ($search ? " لـ \"$search\"" : '')); ?>
  <?php render_pagination($page, $totalPages); ?>

  <!-- Ad Zone Mid -->
  <?= partial_wave() ?>
  <div class="ad-zone">
    <?= ad_slot() ?>
  </div>

  <!-- ── Latest Blog Posts (homepage only) ── -->
  <?php if ($latestPosts && !$search && !$catSlug): ?>
  <?= partial_wave() ?>
  <section style="margin-top:8px">
    <div class="section-head reveal">
      <span class="section-title">من مدونة yassota</span>
      <a href="<?= h(url('blog')) ?>" style="font-size:12px;color:var(--cyan);text-decoration:none">عرض الكل <?= partial_icon('arrow-r') ?></a>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px" class="reveal">
      <?php foreach ($latestPosts as $post): ?>
      <a href="<?= h(blog_post_url($post['slug'])) ?>" style="display:block;background:var(--navy-700);border:1px solid var(--border-c);border-radius:var(--radius-lg);padding:20px;text-decoration:none;transition:border-color .2s,transform .2s"
         onmouseover="this.style.borderColor='rgba(6,182,212,.4)';this.style.transform='translateY(-2px)'"
         onmouseout="this.style.borderColor='var(--border-c)';this.style.transform=''">
        <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--cyan);margin-bottom:8px">
          <?= h(BLOG_TYPES[$post['type']] ?? 'مقالة') ?>
        </div>
        <div style="font-size:15px;font-weight:800;color:var(--white);line-height:1.4;margin-bottom:10px">
          <?= h($post['title']) ?>
        </div>
        <?php if (!empty($post['excerpt'])): ?>
        <p style="font-size:12px;color:var(--muted);line-height:1.7;margin:0 0 12px">
          <?= h(mb_strimwidth($post['excerpt'], 0, 100, '...')) ?>
        </p>
        <?php endif; ?>
        <div style="font-size:11px;color:var(--muted);display:flex;align-items:center;gap:6px">
          <?= partial_icon('clock') ?>
          <?= date('d M Y', strtotime($post['created_at'])) ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Web Tools Section (all 20 tools) ── -->
  <?php if (!$search && !$catSlug): ?>
  <?= partial_wave() ?>
  <section style="margin-top:8px" id="web-tools">
    <div class="section-head reveal">
      <span class="section-title">أدوات ويب مجانية</span>
      <a href="<?= h(url('tools')) ?>" style="font-size:12px;color:var(--cyan);text-decoration:none">عرض الكل <?= partial_icon('arrow-r') ?></a>
    </div>
    <?php
    $webToolGroups = [
      'ملفات PDF' => [
        ['slug'=>'pdf-merge',  'name'=>'دمج PDF',       'desc'=>'ادمج ملفات PDF في ملف واحد مجاناً',          'clr'=>'#7c3aed','svg'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="12" y2="18"/><line x1="15" y1="15" x2="12" y2="18"/>'],
        ['slug'=>'pdf-split',  'name'=>'تقسيم PDF',     'desc'=>'قسّم PDF أو استخرج صفحات منه',               'clr'=>'#dc2626','svg'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="12" x2="15" y2="12"/>'],
        ['slug'=>'pdf-compress','name'=>'ضغط PDF',      'desc'=>'قلّل حجم PDF بدون فقدان الجودة',             'clr'=>'#ea580c','svg'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="12" y2="18"/><line x1="15" y1="15" x2="12" y2="18"/>'],
      ],
      'أدوات الصور' => [
        ['slug'=>'img-crop',   'name'=>'قصّ الصور',     'desc'=>'قصّ أي صورة بدقة عالية مجاناً',              'clr'=>'#0891b2','svg'=>'<path d="M6 2v14a2 2 0 002 2h14"/><path d="M18 22V8a2 2 0 00-2-2H2"/>'],
        ['slug'=>'img-convert','name'=>'تحويل الصور',   'desc'=>'حوّل بين JPG وPNG وWebP',                    'clr'=>'#059669','svg'=>'<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'],
        ['slug'=>'resize',     'name'=>'تغيير حجم الصور','desc'=>'غيّر أبعاد أي صورة مع الحفاظ على النسبة',  'clr'=>'#dc2626','svg'=>'<polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/>'],
        ['slug'=>'compress',   'name'=>'ضغط الصور',     'desc'=>'ضغط الصور وتحويلها لـ WebP بنسبة 85%',      'clr'=>'#7c3aed','svg'=>'<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>'],
        ['slug'=>'ocr',        'name'=>'OCR — قراءة النصوص','desc'=>'استخرج النص من الصور عربياً وإنجليزياً', 'clr'=>'#0891b2','svg'=>'<path d="M14.5 4h-5L7 7H4a2 2 0 00-2 2v9a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>'],
      ],
      'حاسبات ومحوّلات' => [
        ['slug'=>'currency',   'name'=>'محوّل العملات', 'desc'=>'أسعار صرف حقيقية لأكثر من 150 عملة',        'clr'=>'#f59e0b','svg'=>'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>'],
        ['slug'=>'gold-calc',  'name'=>'حاسبة الذهب',  'desc'=>'احسب سعر الذهب بجميع العيارات اليوم',       'clr'=>'#f59e0b','svg'=>'<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'],
        ['slug'=>'unit',       'name'=>'محوّل الوحدات', 'desc'=>'طول وزن حرارة مساحة وسرعة — كل شيء',       'clr'=>'#0891b2','svg'=>'<path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/>'],
      ],
      'أدوات المطورين' => [
        ['slug'=>'json-fmt',   'name'=>'منسّق JSON',    'desc'=>'نسّق وتحقق من صحة ملفات JSON',               'clr'=>'#6366f1','svg'=>'<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>'],
        ['slug'=>'encode',     'name'=>'مشفّر Base64',  'desc'=>'شفّر وفكّ تشفير Base64 وURL وHash',          'clr'=>'#0891b2','svg'=>'<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>'],
        ['slug'=>'colors',     'name'=>'منتقي الألوان', 'desc'=>'اختر لوناً وأنشئ لوحة ألوان متناسقة',       'clr'=>'#7c3aed','svg'=>'<circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10c.28 0 .5-.22.5-.5a.49.49 0 00-.17-.36 6.89 6.89 0 01-1.49-2.84.5.5 0 01.48-.8 4.5 4.5 0 004.24-4.5A6 6 0 0012 2z"/>'],
      ],
      'أدوات الأعمال' => [
        ['slug'=>'pass',       'name'=>'مولّد كلمات المرور','desc'=>'أنشئ كلمة مرور قوية وآمنة فوراً',        'clr'=>'#dc2626','svg'=>'<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>'],
        ['slug'=>'qr',         'name'=>'مولّد QR Code', 'desc'=>'أنشئ رمز QR لأي رابط أو نص',                'clr'=>'#7c3aed','svg'=>'<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="5" y="5" width="3" height="3"/><rect x="16" y="5" width="3" height="3"/><rect x="16" y="16" width="3" height="3"/><rect x="5" y="16" width="3" height="3"/>'],
        ['slug'=>'whatsapp',   'name'=>'رابط واتساب',   'desc'=>'أنشئ رابط واتساب مباشر بدون حفظ الرقم',     'clr'=>'#16a34a','svg'=>'<path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>'],
        ['slug'=>'hashtag',    'name'=>'مولّد الهاشتاق', 'desc'=>'هاشتاقات احترافية بالذكاء الاصطناعي',      'clr'=>'#e11d48','svg'=>'<line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/>'],
        ['slug'=>'words',      'name'=>'عدّاد الكلمات', 'desc'=>'عدّ كلمات وأحرف وجمل نصك فورياً',           'clr'=>'#059669','svg'=>'<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>'],
        ['slug'=>'write',      'name'=>'كاتب محتوى AI', 'desc'=>'أنشئ مقالات ومحتوى عربي احترافي',           'clr'=>'#0891b2','svg'=>'<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>'],
      ],
    ];
    foreach ($webToolGroups as $groupName => $tools):
    ?>
    <div style="margin-top:14px">
      <div style="font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:10px;padding-right:4px"><?= $groupName ?></div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px" class="reveal">
        <?php foreach ($tools as $t): ?>
        <a href="<?= h(url('tools/' . $t['slug'])) ?>"
           style="display:flex;align-items:flex-start;gap:12px;padding:14px;background:var(--navy-700);border:1px solid var(--border-c);border-radius:var(--radius-lg);text-decoration:none;transition:border-color .2s,transform .2s"
           onmouseover="this.style.borderColor='<?= $t['clr'] ?>44';this.style.transform='translateY(-2px)'"
           onmouseout="this.style.borderColor='var(--border-c)';this.style.transform=''">
          <div style="width:38px;height:38px;border-radius:10px;background:<?= $t['clr'] ?>1a;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= $t['clr'] ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $t['svg'] ?></svg>
          </div>
          <div>
            <div style="font-size:13px;font-weight:700;color:var(--white);margin-bottom:3px"><?= h($t['name']) ?></div>
            <div style="font-size:11px;color:var(--muted);line-height:1.5"><?= h($t['desc']) ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <div style="text-align:center;margin-top:14px" class="reveal">
      <a href="<?= h(url('tools')) ?>" style="display:inline-flex;align-items:center;gap:6px;padding:10px 22px;background:var(--navy-700);border:1px solid var(--border-c);border-radius:var(--radius-lg);color:var(--cyan);font-size:13px;font-weight:600;text-decoration:none;transition:border-color .2s"
         onmouseover="this.style.borderColor='rgba(6,182,212,.4)'" onmouseout="this.style.borderColor='var(--border-c)'">
        <?= partial_icon('globe') ?> جميع الأدوات (20 أداة مجانية)
      </a>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Why yassota (editorial values) ── -->
  <?php if (!$search && !$catSlug): ?>
  <?= partial_wave() ?>
  <section style="margin-top:8px">
    <div class="section-head reveal"><span class="section-title">لماذا yassota؟</span></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px" class="reveal">
      <?php foreach ([
        ['icon'=>'pen',     'title'=>'محتوى تحريري','desc'=>'كل مراجعة تطبيق تُكتب بعناية وتُدقَّق من فريق المحررين قبل النشر.'],
        ['icon'=>'refresh', 'title'=>'تحديث يومي',  'desc'=>'نتابع الإصدارات الجديدة يومياً ونحدّث معلومات كل تطبيق فور صدور تحديث.'],
        ['icon'=>'lock',    'title'=>'روابط آمنة',   'desc'=>'نتحقق من كل رابط تحميل ونشير بوضوح لمصدره سواء Play Store أو مصدر رسمي آخر.'],
        ['icon'=>'globe',   'title'=>'محتوى عربي',   'desc'=>'المراجعات مكتوبة بالعربية خصيصاً للمستخدم العربي مع الأخذ بعين الاعتبار خصوصيات المنطقة.'],
      ] as $v): ?>
      <div style="background:var(--navy-700);border:1px solid var(--border-c);border-radius:var(--radius-lg);padding:20px">
        <div class="feature-icon-wrap"><?= partial_icon($v['icon'], 22) ?></div>
        <div style="font-weight:800;font-size:14px;color:var(--white);margin-bottom:6px"><?= $v['title'] ?></div>
        <p style="font-size:12px;color:var(--muted);line-height:1.7;margin:0"><?= $v['desc'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Stats / About snippet ── -->
  <?php if (!$search && !$catSlug):
    $totalCats = count($categories);
    $totalBlog = (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status='published'")->fetchColumn();
  ?>
  <?= partial_wave() ?>
  <section id="about" style="margin-top:12px">
    <div class="section-head reveal"><span class="section-title">عن yassota</span></div>

    <!-- Stats row -->
    <div class="stats-row reveal">
      <?php foreach ([
          [$totalApps,       'تطبيق مراجَع',  'smartphone'],
          [$totalCats,       'تصنيف',           'folder'],
          [$totalBlog,       'مقالة ومراجعة',  'pen'],
          [date('Y') - 2023, 'سنوات خبرة',     'award'],
      ] as [$num, $lbl, $ic]): ?>
      <div class="stat-cell reveal">
        <span class="stat-cell-num"><?= $num > 0 ? number_format($num) : '1+' ?></span>
        <div style="margin-bottom:4px;color:var(--cyan)"><?= partial_icon($ic, 18) ?></div>
        <div class="stat-cell-label"><?= $lbl ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="background:var(--navy-700);border:1px solid var(--border-c);border-radius:var(--radius-lg);padding:28px;margin-top:12px" class="reveal">
      <h2 style="font-family:var(--f-head);font-size:18px;font-weight:900;margin-bottom:10px;background:linear-gradient(135deg,var(--white),var(--cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent">
        منصتك التحريرية العربية للتطبيقات
      </h2>
      <p style="color:var(--muted);font-size:14px;line-height:1.85;margin-bottom:16px">
        yassota دليل تحريري مستقل متخصص في مراجعة وتقييم تطبيقات وألعاب أندرويد. نقدّم معلومات دقيقة ومحايدة تساعدك على اتخاذ قرار التحميل بثقة، مع تحديث يومي لمواكبة أحدث الإصدارات. كل تطبيق يمر بمراجعة تحريرية كاملة قبل نشره.
      </p>
      <!-- Trust badges -->
      <div class="trust-row">
        <?php foreach ([
            ['icon'=>'check-circle', 'text'=>'محتوى تحريري موثوق'],
            ['icon'=>'lock',         'text'=>'روابط مفحوصة وآمنة'],
            ['icon'=>'calendar',     'text'=>'تحديث يومي'],
            ['icon'=>'globe',        'text'=>'محتوى عربي متخصص'],
            ['icon'=>'award',        'text'=>'تقييمات حقيقية'],
        ] as $t): ?>
        <div class="trust-item">
          <span style="color:var(--cyan);line-height:1;flex-shrink:0"><?= partial_icon($t['icon'], 14) ?></span>
          <span style="font-size:13px;color:var(--muted)"><?= $t['text'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <a href="<?= h(url('about')) ?>" style="color:var(--cyan);font-size:13px;display:inline-flex;align-items:center;gap:5px;margin-top:4px">
        <?= partial_icon('arrow-r') ?> تعرّف علينا أكثر
      </a>
    </div>
  </section>

  <!-- ── FAQ Section (FAQPage schema already built into dedicated /faq page) ── -->
  <?= partial_wave() ?>
  <section style="margin-top:8px">
    <div class="section-head reveal">
      <span class="section-title">أسئلة شائعة</span>
      <a href="<?= h(url('faq')) ?>" style="font-size:12px;color:var(--cyan);text-decoration:none">كل الأسئلة <?= partial_icon('arrow-r') ?></a>
    </div>
    <div class="faq-list reveal">
      <?php $homeFaqs = [
        ['q' => 'هل yassota موقع رسمي من Google Play؟',
         'a' => 'لا، yassota دليل تحريري مستقل ولا ينتمي لأي متجر. نراجع التطبيقات بموضوعية ونضع روابط Google Play الرسمية للتحميل.'],
        ['q' => 'هل روابط التحميل آمنة؟',
         'a' => 'نعم، نتحقق من كل رابط قبل نشره ونعتمد بشكل أساسي على Google Play. الملفات APK المباشرة تُفحص بأدوات اكتشاف الفيروسات.'],
        ['q' => 'كيف أضيف تقييمي لتطبيق؟',
         'a' => 'افتح صفحة التطبيق وانتقل لقسم التعليقات في أسفل الصفحة، اختر النجوم واكتب رأيك. التعليقات تخضع لمراجعة قبل نشرها.'],
        ['q' => 'كيف أقترح إضافة تطبيق جديد؟',
         'a' => 'أرسل اقتراحك عبر صفحة "اتصل بنا" مع اسم التطبيق ورابط Google Play وسيراجعه فريقنا.'],
      ]; foreach ($homeFaqs as $fi => $faq): ?>
      <details class="faq-item" style="animation-delay:<?= $fi * 0.08 ?>s">
        <summary class="faq-q">
          <span><?= h($faq['q']) ?></span>
          <svg class="faq-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
        </summary>
        <div class="faq-a"><?= h($faq['a']) ?></div>
      </details>
      <?php endforeach; ?>
    </div>
  </section>

  <?php endif; ?>

</main>
</div>

<?php render_site_footer(); ?>

<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
<?php if ($cacheable) page_cache_end($pdo, $_SERVER['REQUEST_URI']); ?>
