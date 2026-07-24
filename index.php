<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

/* ── Data ── */
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;
$catSlug = trim($_GET['cat'] ?? '');
$search  = trim($_GET['q'] ?? '');

$cacheable = $search === '';
if ($cacheable && page_cache_start($pdo, $_SERVER['REQUEST_URI'])) exit;

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();

$where  = "WHERE a.status='published'";
$params = [];
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

// Featured: most downloaded published app
$featured = $pdo->query("SELECT a.*, c.name AS cat_name FROM apps a LEFT JOIN categories c ON a.category_id=c.id WHERE a.status='published' ORDER BY a.downloads DESC LIMIT 1")->fetch();

// Latest blog posts for homepage
$latestPosts = [];
if (!$search && !$catSlug) {
    $latestPosts = $pdo->query("SELECT id,title,slug,type,excerpt,created_at FROM blog_posts WHERE status='published' ORDER BY created_at DESC LIMIT 3")->fetchAll();
}

$activeNav = $catSlug === 'games' ? 'games' : ($catSlug === 'apps' ? 'apps' : 'home');
$siteName  = 'Apkzilo';
$siteHost  = parse_url(SITE_URL, PHP_URL_HOST) ?: 'apkzilo.com';

$websiteSchema = json_encode([
    "@context" => "https://schema.org", "@type" => "WebSite",
    "name" => "Apkzilo", "url" => url(''),
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
        ["@type"=>"Question","name"=>"هل Apkzilo موقع رسمي من Google Play؟",
         "acceptedAnswer"=>["@type"=>"Answer","text"=>"لا، Apkzilo دليل تحريري مستقل ولا ينتمي لأي متجر تطبيقات. نراجع التطبيقات بموضوعية ونضع روابط Google Play الرسمية للتحميل."]],
        ["@type"=>"Question","name"=>"هل روابط التحميل على Apkzilo آمنة؟",
         "acceptedAnswer"=>["@type"=>"Answer","text"=>"نعم، نتحقق من كل رابط قبل نشره ونعتمد بشكل أساسي على Google Play. الملفات APK المباشرة تُفحص بأدوات اكتشاف الفيروسات ونعرض نتيجة الفحص."]],
        ["@type"=>"Question","name"=>"كيف أضيف تقييمي لتطبيق على Apkzilo؟",
         "acceptedAnswer"=>["@type"=>"Answer","text"=>"افتح صفحة التطبيق وانتقل لقسم التعليقات في أسفل الصفحة، اختر عدد النجوم واكتب رأيك ثم أرسل. تخضع التعليقات لمراجعة قبل نشرها."]],
        ["@type"=>"Question","name"=>"هل Apkzilo مجاني؟",
         "acceptedAnswer"=>["@type"=>"Answer","text"=>"نعم، Apkzilo مجاني تماماً للمستخدمين. يعتمد الموقع على الإعلانات لتغطية تكاليف التشغيل ولا توجد رسوم مخفية."]],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$orgSchema = json_encode([
    "@context" => "https://schema.org", "@type" => "Organization",
    "name" => "Apkzilo", "url" => url(''),
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
  <title><?= h($search ? "نتائج البحث: $search" : ($catSlug ? ucfirst($catSlug) . ' — Apkzilo' : 'Apkzilo — مراجعات وأدلة تطبيقات أندرويد')) ?></title>
  <meta name="description" content="<?= h($search ? "نتائج البحث عن: $search على Apkzilo" : ($catSlug ? "تصفح وراجع أفضل تطبيقات $catSlug على منصة Apkzilo العربية" : 'Apkzilo — دليلك التحريري العربي المستقل لمراجعة واكتشاف أفضل تطبيقات وألعاب أندرويد. مراجعات احترافية، معلومات موثوقة، ومحتوى محدّث يومياً.')) ?>">
  <meta name="robots" content="index,follow,max-snippet:-1,max-image-preview:large">
  <link rel="canonical" href="<?= h(url('')) ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Apkzilo">
  <meta property="og:title" content="Apkzilo — دليل مراجعات تطبيقات أندرويد">
  <meta property="og:description" content="دليلك التحريري العربي المستقل لاكتشاف ومراجعة أفضل تطبيقات وألعاب أندرويد — محتوى محدّث يومياً.">
  <?php if ($featured && $featured['icon_path']): ?><meta property="og:image" content="<?= h(url($featured['icon_path'])) ?>"><?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Apkzilo — مراجعات تطبيقات أندرويد">
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

<div class="page-wrap">

<?php render_site_sidebar($pdo, $catSlug); ?>

<!-- ══ MAIN ══ -->
<main class="main-content">

  <!-- Ad Zone Top -->
  <div class="ad-zone">
    <?= ad_slot() ?>
  </div>

  <!-- ── Hero Banner (homepage only) ── -->
  <?php if (!$search && !$catSlug): ?>
  <div class="hero-banner reveal">
    <div style="font-size:11px;font-weight:700;letter-spacing:2px;color:var(--cyan);margin-bottom:10px;text-transform:uppercase">
      دليل تحريري مستقل — محتوى يومي
    </div>
    <h1>اكتشف وراجع أفضل<br>تطبيقات أندرويد</h1>
    <p>مراجعات احترافية، مقارنات دقيقة، ومعلومات موثوقة لكل تطبيق — Apkzilo دليلك العربي المستقل لاكتشاف عالم تطبيقات Android.</p>
    <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-top:20px">
      <a href="#apps-grid" style="padding:10px 24px;border-radius:50px;background:linear-gradient(135deg,var(--cyan),var(--purple));color:#fff;font-weight:700;font-size:13px;text-decoration:none">
        استعرض التطبيقات
      </a>
      <a href="<?= h(url('blog')) ?>" style="padding:10px 24px;border-radius:50px;border:1.5px solid rgba(6,182,212,.4);color:var(--cyan);font-weight:600;font-size:13px;text-decoration:none">
        اقرأ المراجعات
      </a>
    </div>
  </div>
  <?= partial_wave() ?>
  <?php endif; ?>

  <!-- ── Category Chips ── -->
  <div class="cat-chips reveal">
    <div class="cat-chip <?= !$catSlug ? 'active' : '' ?>" data-cat="all"><?= partial_icon('apps') ?> الكل</div>
    <?php foreach ($categories as $cat): ?>
    <div class="cat-chip <?= $catSlug === $cat['slug'] ? 'active' : '' ?>" data-cat="<?= h($cat['slug']) ?>">
      <?= partial_icon($cat['slug'] === 'games' ? 'games' : 'apps') ?> <?= h($cat['name']) ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── Featured App (editorial pick) ── -->
  <?php if ($featured && !$search && !$catSlug): ?>
  <a href="<?= h(app_url($featured['slug'])) ?>" class="featured-card reveal" data-hardnav="1">
    <?php if ($featured['icon_path']): ?>
      <img src="<?= h(url($featured['icon_path'])) ?>" alt="<?= h($featured['name']) ?>" class="featured-icon">
    <?php else: ?>
      <div class="featured-icon" style="background:linear-gradient(135deg,#e8ecf3,#dde3ec)"></div>
    <?php endif; ?>
    <div class="featured-info">
      <div class="app-card-cat">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="#fbbf24" stroke="#fbbf24" stroke-width="1" style="vertical-align:-2px"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        اختيار المحرر — <?= h($featured['cat_name'] ?? 'تطبيق') ?>
      </div>
      <div class="featured-name"><?= h($featured['name']) ?></div>
      <div class="featured-desc"><?= h(mb_strimwidth($featured['short_description'] ?? '', 0, 130, '...')) ?></div>
      <span class="btn-outline"><?= partial_icon('info') ?> اقرأ المراجعة الكاملة</span>
    </div>
  </a>
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
      <span class="section-title">من مدونة Apkzilo</span>
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

  <!-- ── Why Apkzilo (editorial values) ── -->
  <?php if (!$search && !$catSlug): ?>
  <?= partial_wave() ?>
  <section style="margin-top:8px">
    <div class="section-head reveal"><span class="section-title">لماذا Apkzilo؟</span></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px" class="reveal">
      <?php foreach ([
        ['icon'=>'✍️','title'=>'محتوى تحريري','desc'=>'كل مراجعة تطبيق تُكتب بعناية وتُدقَّق من فريق المحررين قبل النشر.'],
        ['icon'=>'🔄','title'=>'تحديث يومي','desc'=>'نتابع الإصدارات الجديدة يومياً ونحدّث معلومات كل تطبيق فور صدور تحديث.'],
        ['icon'=>'🔒','title'=>'روابط آمنة','desc'=>'نتحقق من كل رابط تحميل ونشير بوضوح لمصدره سواء Play Store أو مصدر رسمي آخر.'],
        ['icon'=>'🌐','title'=>'محتوى عربي','desc'=>'المراجعات مكتوبة بالعربية خصيصاً للمستخدم العربي مع الأخذ بعين الاعتبار خصوصيات المنطقة.'],
      ] as $v): ?>
      <div style="background:var(--navy-700);border:1px solid var(--border-c);border-radius:var(--radius-lg);padding:20px">
        <div style="font-size:28px;margin-bottom:10px"><?= $v['icon'] ?></div>
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
    <div class="section-head reveal"><span class="section-title">عن Apkzilo</span></div>

    <!-- Stats row -->
    <div class="stats-row reveal">
      <?php foreach ([
          [$totalApps,  'تطبيق مراجَع',   '📱'],
          [$totalCats,  'تصنيف',            '🗂️'],
          [$totalBlog,  'مقالة ومراجعة',   '✍️'],
          [date('Y') - 2023, 'سنوات خبرة', '⭐'],
      ] as [$num, $lbl, $ic]): ?>
      <div class="stat-cell reveal">
        <span class="stat-cell-num"><?= $num > 0 ? number_format($num) : '1+' ?></span>
        <div style="font-size:16px;margin-bottom:3px"><?= $ic ?></div>
        <div class="stat-cell-label"><?= $lbl ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="background:var(--navy-700);border:1px solid var(--border-c);border-radius:var(--radius-lg);padding:28px;margin-top:12px" class="reveal">
      <h2 style="font-family:var(--f-head);font-size:18px;font-weight:900;margin-bottom:10px;background:linear-gradient(135deg,var(--white),var(--cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent">
        منصتك التحريرية العربية للتطبيقات
      </h2>
      <p style="color:var(--muted);font-size:14px;line-height:1.85;margin-bottom:16px">
        Apkzilo دليل تحريري مستقل متخصص في مراجعة وتقييم تطبيقات وألعاب أندرويد. نقدّم معلومات دقيقة ومحايدة تساعدك على اتخاذ قرار التحميل بثقة، مع تحديث يومي لمواكبة أحدث الإصدارات. كل تطبيق يمر بمراجعة تحريرية كاملة قبل نشره.
      </p>
      <!-- Trust badges -->
      <div class="trust-row">
        <?php foreach ([
            ['✅ محتوى تحريري موثوق'],
            ['🔒 روابط مفحوصة وآمنة'],
            ['📅 تحديث يومي'],
            ['🌐 محتوى عربي متخصص'],
            ['⭐ تقييمات حقيقية'],
        ] as [$t]): ?>
        <div class="trust-item">
          <span style="font-size:13px;color:var(--muted)"><?= $t ?></span>
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
        ['q' => 'هل Apkzilo موقع رسمي من Google Play؟',
         'a' => 'لا، Apkzilo دليل تحريري مستقل ولا ينتمي لأي متجر. نراجع التطبيقات بموضوعية ونضع روابط Google Play الرسمية للتحميل.'],
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
