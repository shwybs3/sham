<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

evil_check_ban($pdo);
waf_check($pdo);
public_cache_headers(60);

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
$perPage = 16;
$offset  = ($page - 1) * $perPage;
$catSlug = trim($_GET['cat'] ?? '');
$search  = trim($_GET['q'] ?? '');

$cacheable = ($search === '' && !detect_lang_from_subdomain() && !$_multisite);
$_cacheKey = $_SERVER['REQUEST_URI'] . ':lang:' . (defined('UI_LANG') ? UI_LANG : 'ar');
if ($cacheable && page_cache_start($pdo, $_cacheKey)) exit;

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

$stmt = $pdo->prepare("SELECT a.id, a.name, a.slug, a.rating, a.size_mb, a.icon_path, a.short_description, a.category_id, c.name AS cat_name, c.slug AS cat_slug
    FROM apps a LEFT JOIN categories c ON a.category_id=c.id
    $where ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$apps = $stmt->fetchAll();

// Top app for OG image only (no carousel)
$featured = $pdo->query("SELECT icon_path FROM apps WHERE status='published' ORDER BY downloads DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Latest blog posts for homepage
$latestPosts = [];
if (!$search && !$catSlug) {
    $latestPosts = $pdo->query("SELECT id,title,slug,type,excerpt,created_at FROM blog_posts WHERE status='published' ORDER BY created_at DESC LIMIT 2")->fetchAll();
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
<html lang="<?= defined('UI_LANG') ? UI_LANG : 'ar' ?>" dir="<?= defined('UI_DIR') ? UI_DIR : 'rtl' ?>">
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

  <!-- ── Apps Grid ── -->
  <div class="section-head reveal" id="apps-grid">
    <span class="section-title">
      <?= $search ? 'نتائج البحث: ' . h($search) : ($catSlug ? h($categories[array_search($catSlug, array_column($categories,'slug'))]['name'] ?? 'التطبيقات') : 'أحدث التطبيقات') ?>
    </span>
    <span style="font-family:var(--f-mono);font-size:12px;color:var(--muted)"><?= number_format($totalApps) ?> تطبيق</span>
  </div>

  <?php render_app_grid($apps, $search ? __('no_results_for') . " \"$search\"" : __('no_results')); ?>
  <?php render_pagination($page, $totalPages); ?>

  <!-- Ad Zone Mid -->
  <?= partial_wave() ?>
  <div class="ad-zone">
    <?= ad_slot() ?>
  </div>

  <!-- ── Related Web Tools (4 most popular tools) ── -->
  <?php if (!$search && !$catSlug):
    $relatedTools = [];
    try {
      $relatedTools = $pdo->query(
        "SELECT id, slug, name, icon_path, short_description FROM web_tools WHERE status='published' ORDER BY views DESC LIMIT 4"
      )->fetchAll();
    } catch (\Throwable $e) { $relatedTools = []; }
  ?>
  <?php if (!empty($relatedTools)): ?>
  <?= partial_wave() ?>
  <section style="margin-top:16px;padding:20px;background:linear-gradient(135deg, rgba(99,102,241,.08), rgba(59,130,246,.08));border:1px solid rgba(99,102,241,.2);border-radius:var(--radius-lg)" id="related-tools-apps">
    <div class="section-head reveal" style="margin-bottom:12px">
      <span class="section-title"><?= h(__('tools_complete_experience')) ?></span>
      <a href="<?= h(url('tools')) ?>" style="font-size:12px;color:var(--cyan);text-decoration:none;font-weight:600"><?= h(__('view_all_tools_count')) ?> <?= partial_icon('arrow-r') ?></a>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px" class="reveal">
      <?php foreach ($relatedTools as $tool): ?>
      <a href="<?= h(SITE_URL . '/tools?slug=' . rawurlencode($tool['slug'])) ?>"
         style="display:block;background:var(--surface);border:1px solid var(--border-c);border-radius:8px;padding:14px;text-decoration:none;transition:.2s"
         onmouseover="this.style.borderColor='rgba(99,102,241,.4)';this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(99,102,241,.1)'"
         onmouseout="this.style.borderColor='var(--border-c)';this.style.transform='';this.style.boxShadow=''">
        <div style="display:flex;align-items:flex-start;gap:10px">
          <?php if (!empty($tool['icon_path'])): ?>
          <img src="<?= h(media_url($tool['icon_path'])) ?>" alt="<?= h($tool['name']) ?>" style="width:32px;height:32px;border-radius:6px;object-fit:cover;flex-shrink:0">
          <?php else: ?>
          <div style="width:32px;height:32px;border-radius:6px;background:rgba(99,102,241,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
          </div>
          <?php endif; ?>
          <div style="flex:1">
            <div style="font-weight:700;font-size:13px;color:var(--fg);line-height:1.3"><?= h($tool['name']) ?></div>
            <p style="font-size:11px;color:var(--muted);line-height:1.5;margin:4px 0 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
              <?= h(mb_substr($tool['short_description'], 0, 80)) ?>
            </p>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
  <?php endif; ?>

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
<?php if ($cacheable) page_cache_end($pdo, $_cacheKey); ?>
