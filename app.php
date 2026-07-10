<?php
require_once __DIR__ . '/config.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT a.*, c.name AS cat_name, c.slug AS cat_slug
    FROM apps a LEFT JOIN categories c ON a.category_id=c.id
    WHERE a.slug=? AND a.status='published'");
$stmt->execute([$slug]);
$app = $stmt->fetch();

if (!$app) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>404</title>
    <link rel="stylesheet" href="assets/css/main.css"></head><body style="display:flex;align-items:center;justify-content:center;height:100vh;flex-direction:column;gap:16px">
    <p style="font-size:64px;font-family:var(--f-mono);color:var(--cyan)">404</p>
    <p style="color:var(--muted)">التطبيق غير موجود</p>
    <a href="index.php" style="color:var(--cyan)">العودة للرئيسية</a></body></html>';
    exit;
}

// Track view
$pdo->prepare("UPDATE apps SET views=views+1 WHERE id=?")->execute([$app['id']]);

$screenshots  = json_decode($app['screenshots'] ?? '[]', true) ?: [];
$features     = json_decode($app['features'] ?? '[]', true) ?: [];
$pros         = json_decode($app['pros'] ?? '[]', true) ?: [];
$cons         = json_decode($app['cons'] ?? '[]', true) ?: [];
$installSteps = json_decode($app['install_steps'] ?? '[]', true) ?: [];
$faq          = json_decode($app['faq'] ?? '[]', true) ?: [];

// Related apps
$related = $pdo->prepare("SELECT id,name,slug,icon_path,rating FROM apps
    WHERE category_id=? AND id!=? AND status='published' LIMIT 6");
$related->execute([$app['category_id'], $app['id']]);
$relatedApps = $related->fetchAll();

// Schema.org
$schema = json_encode([
    "@context" => "https://schema.org",
    "@type" => "SoftwareApplication",
    "name" => $app['name'],
    "operatingSystem" => "ANDROID",
    "applicationCategory" => $app['cat_name'] ?? "Application",
    "description" => $app['meta_description'] ?: $app['short_description'],
    "softwareVersion" => $app['version'],
    "aggregateRating" => [
        "@type" => "AggregateRating",
        "ratingValue" => $app['rating'],
        "ratingCount" => max(10, intval($app['downloads'] / 3)),
    ],
    "offers" => ["@type" => "Offer", "price" => "0", "priceCurrency" => "USD"],
    "author" => ["@type" => "Organization", "name" => $app['developer']],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// SVGs inline
function svgi(string $n): string {
    $i = [
        'download' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>',
        'play'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M5 3l14 9-14 9V3z"/></svg>',
        'check' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#00e676" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
        'x'     => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ff4466" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>',
        'q'     => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01"/></svg>',
        'chevron' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>',
        'zoom'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--cyan)" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/><path d="M11 8v6m-3-3h6"/></svg>',
        'close' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>',
        'arrow-l' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>',
        'arrow-r' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>',
        'external' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
        'rec'   => '<svg width="8" height="8" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4" fill="#ff4466"/></svg>',
        'mirror'=> '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>',
        'star'  => '<svg width="14" height="14" viewBox="0 0 24 24" fill="#fbbf24" stroke="#fbbf24" stroke-width="1"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
        'android'=> '<svg width="16" height="16" viewBox="0 0 24 24" fill="var(--success)"><path d="M17.523 15.341c-.414 0-.75-.336-.75-.75V9.75c0-.414.336-.75.75-.75s.75.336.75.75v4.841c0 .414-.336.75-.75.75zM6.477 15.341c-.414 0-.75-.336-.75-.75V9.75c0-.414.336-.75.75-.75s.75.336.75.75v4.841c0 .414-.336.75-.75.75zM8.25 17.25V8.25h7.5v9h-7.5zM15 7.5H9l-1.5-2.625 1.299-.75L10.5 6h3l1.701-1.875 1.299.75L15 7.5z"/></svg>',
        'playstore'=> '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 3.5v17l14-8.5-14-8.5z" fill="url(#psg)" stroke="currentColor" stroke-width="0.5" stroke-linejoin="round"/><defs><linearGradient id="psg" x1="4" y1="3.5" x2="18" y2="12" gradientUnits="userSpaceOnUse"><stop stop-color="#00f5ff"/><stop offset="1" stop-color="#a855f7"/></linearGradient></defs></svg>',
    ];
    return $i[$n] ?? '';
}
function wave(): string {
    return '<svg class="wave-divider" viewBox="0 0 1200 40" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0,20 C150,40 350,0 600,20 C850,40 1050,0 1200,20" stroke="#00f5ff" stroke-width="1.5" fill="none"/>
    </svg>';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($app['seo_title'] ?: $app['name']) ?></title>
  <meta name="description" content="<?= h($app['meta_description'] ?: $app['short_description']) ?>">
  <?php if ($app['keywords']): ?><meta name="keywords" content="<?= h($app['keywords']) ?>"><?php endif; ?>
  <link rel="canonical" href="<?= h(url('app.php?slug=' . $app['slug'])) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= h($app['seo_title'] ?: $app['name']) ?>">
  <meta property="og:description" content="<?= h($app['meta_description'] ?: $app['short_description']) ?>">
  <?php if ($app['icon_path']): ?><meta property="og:image" content="<?= h(url($app['icon_path'])) ?>"><?php endif; ?>
  <meta property="og:url" content="<?= h(url('app.php?slug=' . $app['slug'])) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <script type="application/ld+json"><?= $schema ?></script>
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/detail.css">
  <!-- MoneyTag -->
  <script src="https://quge5.com/88/tag.min.js" data-zone="258058" async data-cfasync="false"></script>
</head>
<body>

<!-- Header -->
<header class="site-header">
  <a href="index.php" class="logo">yass<span>ota</span></a>
  <form action="index.php" method="get" class="header-search">
    <input type="text" name="q" placeholder="ابحث...">
    <button type="submit"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></button>
  </form>
  <nav class="header-nav">
    <a href="index.php">الرئيسية</a>
    <?php if ($app['cat_slug']): ?>
    <a href="index.php?cat=<?= h($app['cat_slug']) ?>"><?= h($app['cat_name']) ?></a>
    <?php endif; ?>
  </nav>
</header>

<div class="page-wrap">

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-section">
    <div class="sidebar-title">التنقل</div>
    <a href="index.php" class="sidebar-link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-4h4v4h4a1 1 0 001-1v-9"/></svg> الرئيسية</a>
    <?php if ($app['cat_slug']): ?>
    <a href="index.php?cat=<?= h($app['cat_slug']) ?>" class="sidebar-link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="9" height="9" rx="2"/><rect x="13" y="2" width="9" height="9" rx="2"/><rect x="2" y="13" width="9" height="9" rx="2"/><rect x="13" y="13" width="9" height="9" rx="2"/></svg> <?= h($app['cat_name']) ?></a>
    <?php endif; ?>
  </div>
  <?php if ($relatedApps): ?>
  <div class="sidebar-section">
    <div class="sidebar-title">تطبيقات مشابهة</div>
    <?php foreach ($relatedApps as $r): ?>
    <a href="app.php?slug=<?= h($r['slug']) ?>" class="sidebar-link" style="gap:10px">
      <?php if ($r['icon_path']): ?>
        <img src="<?= h($r['icon_path']) ?>" style="width:28px;height:28px;border-radius:6px;object-fit:cover;flex-shrink:0" alt="">
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
  <nav style="font-size:12px;color:var(--muted);margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <a href="index.php" style="color:var(--cyan)">الرئيسية</a>
    <span>/</span>
    <?php if ($app['cat_slug']): ?>
      <a href="index.php?cat=<?= h($app['cat_slug']) ?>" style="color:var(--cyan)"><?= h($app['cat_name']) ?></a>
      <span>/</span>
    <?php endif; ?>
    <span><?= h($app['name']) ?></span>
  </nav>

  <!-- App Hero -->
  <section class="app-hero reveal">
    <div class="app-hero-inner">
      <div class="app-hero-icon-wrap">
        <?php if ($app['icon_path']): ?>
          <img src="<?= h($app['icon_path']) ?>" alt="<?= h($app['name']) ?>" class="app-hero-icon">
        <?php else: ?>
          <div class="app-hero-icon" style="background:linear-gradient(135deg,#152642,#1e3356);display:flex;align-items:center;justify-content:center">
            <?= svgi('android') ?>
          </div>
        <?php endif; ?>
        <div class="rec-overlay"><?= svgi('rec') ?> REC</div>
      </div>

      <div class="app-hero-info">
        <h1 class="app-hero-name neon-text"><?= h($app['name']) ?></h1>
        <div class="app-hero-developer"><?= h($app['developer'] ?? '') ?></div>

        <div class="app-badges">
          <?php if ($app['cat_name']): ?><span class="badge badge-cyan"><?= h($app['cat_name']) ?></span><?php endif; ?>
          <?php if ($app['version']): ?><span class="badge badge-purple" style="font-family:var(--f-mono)">v<?= h($app['version']) ?></span><?php endif; ?>
          <span class="badge badge-gold"><?= svgi('star') ?> <?= h($app['rating']) ?></span>
          <?php if ($app['license']): ?><span class="badge badge-cyan"><?= h($app['license']) ?></span><?php endif; ?>
        </div>

        <div class="app-hero-actions">
          <a href="download.php?id=<?= $app['id'] ?>" class="btn-download-hero" data-hardnav="1">
            <?= svgi('download') ?> تحميل مجاني
          </a>
          <?php if (!empty($app['playstore_url'])): ?>
          <a href="<?= h($app['playstore_url']) ?>" target="_blank" rel="nofollow noopener" class="btn-mirror" title="فتح صفحة التطبيق على Google Play">
            <?= svgi('playstore') ?> Google Play
          </a>
          <?php endif; ?>
          <?php if ($app['mirror2_url']): ?>
          <a href="download.php?id=<?= $app['id'] ?>&m=2" class="btn-mirror" data-hardnav="1"><?= svgi('mirror') ?> مرآة 2</a>
          <?php endif; ?>
          <?php if ($app['mirror3_url']): ?>
          <a href="download.php?id=<?= $app['id'] ?>&m=3" class="btn-mirror" data-hardnav="1"><?= svgi('mirror') ?> مرآة 3</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?= wave() ?>

  <!-- Ad -->
  <div class="ad-zone"><script>/* MoneyTag 258058 */</script></div>

  <!-- Meta Info Grid -->
  <div class="app-meta-grid reveal">
    <?php
    $metas = [
        ['label'=>'الإصدار','val'=>$app['version'],'class'=>'version'],
        ['label'=>'إصدار جوجل بلاي','val'=>$app['play_store_version'],'class'=>'version'],
        ['label'=>'يتطلب أندرويد','val'=>$app['android_version'],'class'=>''],
        ['label'=>'الحجم','val'=>$app['size_mb'] ? $app['size_mb'].' MB' : null,'class'=>'size'],
        ['label'=>'المطور','val'=>$app['developer'],'class'=>''],
        ['label'=>'التحميلات','val'=>$app['downloads'] > 0 ? number_format($app['downloads']) : null,'class'=>''],
    ];
    foreach ($metas as $m): if (!$m['val']) continue; ?>
    <div class="meta-item reveal">
      <div class="meta-item-label"><?= h($m['label']) ?></div>
      <div class="meta-item-value <?= $m['class'] ?>"><?= h($m['val']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <?= wave() ?>

  <!-- Screenshots -->
  <?php if ($screenshots): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title">صور من التطبيق</span></div>
    <div class="screenshots-scroll">
      <?php foreach ($screenshots as $i => $ss): ?>
      <div class="screenshot-thumb">
        <img src="<?= h($ss) ?>" alt="<?= h($app['name']) ?> screenshot <?= $i+1 ?>" loading="lazy">
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

  <!-- Description -->
  <?php if ($app['long_description'] || $app['short_description']): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title">الوصف</span></div>
    <div style="color:var(--muted);font-size:14px;line-height:1.85">
      <?= nl2br(h($app['long_description'] ?: $app['short_description'])) ?>
    </div>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- Features -->
  <?php if ($features): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title">المميزات</span></div>
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
    <div class="section-head"><span class="section-title">الإيجابيات والسلبيات</span></div>
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
  <div class="ad-zone"><script>/* MoneyTag 258058 */</script></div>

  <!-- Install Steps -->
  <?php if ($installSteps): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title">طريقة التثبيت</span></div>
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
  <?php if ($app['whats_new']): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title">ما الجديد</span></div>
    <p style="color:var(--muted);font-size:14px;line-height:1.8"><?= nl2br(h($app['whats_new'])) ?></p>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- FAQ -->
  <?php if ($faq): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title">الأسئلة الشائعة</span></div>
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

  <!-- What this app offers -->
  <?php if (!empty($app['offers_text'])): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title">ماذا يقدّم <?= h($app['name']) ?>؟</span></div>
    <div style="color:var(--muted);font-size:14px;line-height:1.85"><?= nl2br(h($app['offers_text'])) ?></div>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- Privacy Policy -->
  <?php if (!empty($app['privacy_policy'])): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title">سياسة الخصوصية</span></div>
    <div style="color:var(--muted);font-size:14px;line-height:1.85"><?= nl2br(h($app['privacy_policy'])) ?></div>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- Terms of Use -->
  <?php if (!empty($app['terms_content'])): ?>
  <div class="section-box reveal">
    <div class="section-head"><span class="section-title">شروط الاستخدام</span></div>
    <div style="color:var(--muted);font-size:14px;line-height:1.85"><?= nl2br(h($app['terms_content'])) ?></div>
  </div>
  <?= wave() ?>
  <?php endif; ?>

  <!-- Related Apps -->
  <?php if ($relatedApps): ?>
  <div class="section-head reveal"><span class="section-title">تطبيقات مشابهة</span></div>
  <div class="apps-grid">
    <?php foreach ($relatedApps as $r): ?>
    <div class="app-card reveal">
      <a href="app.php?slug=<?= h($r['slug']) ?>" data-hardnav="1">
        <?php if ($r['icon_path']): ?>
          <img src="<?= h($r['icon_path']) ?>" alt="<?= h($r['name']) ?>" class="app-card-icon" loading="lazy">
        <?php endif; ?>
        <div class="app-card-name"><?= h($r['name']) ?></div>
        <div class="app-card-meta">
          <div class="app-card-rating"><?= svgi('star') ?> <?= h($r['rating']) ?></div>
        </div>
      </a>
      <a href="download.php?id=<?= $r['id'] ?>" class="btn-dl-card" data-hardnav="1">
        <?= svgi('download') ?> تحميل
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</main>
</div>

<!-- Sticky Download Bar (mobile) -->
<div class="sticky-dl">
  <div class="sticky-dl-inner">
    <?php if ($app['icon_path']): ?>
      <img src="<?= h($app['icon_path']) ?>" class="sticky-dl-icon" alt="">
    <?php endif; ?>
    <div class="sticky-dl-name"><?= h($app['name']) ?></div>
    <a href="download.php?id=<?= $app['id'] ?>" class="btn-primary" style="padding:10px 20px;font-size:14px;flex-shrink:0" data-hardnav="1">
      <?= svgi('download') ?> تحميل
    </a>
  </div>
</div>

<footer class="site-footer" style="grid-column:1/-1">
  <div class="footer-logo">yass<span style="color:var(--purple)">ota</span></div>
  <p>&copy; <?= date('Y') ?> yassota</p>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
