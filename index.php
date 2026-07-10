<?php
require_once __DIR__ . '/config.php';

/* ── Data ── */
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;
$catSlug = trim($_GET['cat'] ?? '');
$search  = trim($_GET['q'] ?? '');

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();

$where  = "WHERE a.status='published'";
$params = [];
if ($catSlug && $catSlug !== 'all') {
    $where .= " AND c.slug = ?"; $params[] = $catSlug;
}
if ($search !== '') {
    $where .= " AND (a.name LIKE ? OR a.short_description LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM apps a LEFT JOIN categories c ON a.category_id=c.id $where");
$countStmt->execute($params);
$totalApps  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalApps / $perPage));

$params2 = $params;
$stmt = $pdo->prepare("SELECT a.*, c.name AS cat_name, c.slug AS cat_slug
    FROM apps a LEFT JOIN categories c ON a.category_id=c.id
    $where ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params2);
$apps = $stmt->fetchAll();

// Featured: أحدث تطبيق منشور
$featured = $pdo->query("SELECT a.*, c.name AS cat_name FROM apps a LEFT JOIN categories c ON a.category_id=c.id WHERE a.status='published' ORDER BY a.id DESC LIMIT 1")->fetch();

/* ── SVG helpers ── */
function wave_svg(): string {
    return '<svg class="wave-divider" viewBox="0 0 1200 40" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0,20 C150,40 350,0 600,20 C850,40 1050,0 1200,20" stroke="#00f5ff" stroke-width="1.5" fill="none"/>
    </svg>';
}

function icon_svg(string $name): string {
    $icons = [
        'download' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>',
        'search'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>',
        'menu'     => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>',
        'star'     => '<svg width="13" height="13" viewBox="0 0 24 24" fill="#fbbf24" stroke="#fbbf24" stroke-width="1"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
        'apps'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="9" height="9" rx="2"/><rect x="13" y="2" width="9" height="9" rx="2"/><rect x="2" y="13" width="9" height="9" rx="2"/><rect x="13" y="13" width="9" height="9" rx="2"/></svg>',
        'games'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 12h4m-2-2v4"/><circle cx="17" cy="10" r="1" fill="currentColor"/><circle cx="17" cy="14" r="1" fill="currentColor"/><path d="M2 8a2 2 0 012-2h16a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V8z"/></svg>',
        'info'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>',
        'home'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-4h4v4h4a1 1 0 001-1v-9"/></svg>',
        'arrow-r'  => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>',
        'external' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
    ];
    return $icons[$name] ?? '';
}

$siteName = 'yassota';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($search ? "نتائج: $search" : ($catSlug ? ucfirst($catSlug) : 'تحميل أفضل التطبيقات')) ?> — yassota</title>
  <meta name="description" content="حمّل أفضل تطبيقات وألعاب أندرويد مجاناً على yassota — سريع، آمن، مباشر">
  <link rel="canonical" href="<?= h(url('index.php')) ?>">
  <meta property="og:title" content="yassota — تحميل التطبيقات">
  <meta property="og:description" content="أفضل تطبيقات أندرويد مجاناً">
  <link rel="stylesheet" href="assets/css/main.css">
  <!-- MoneyTag Ads -->
  <script src="https://quge5.com/88/tag.min.js" data-zone="258058" async data-cfasync="false"></script>
</head>
<body>

<!-- ══ HEADER ══ -->
<header class="site-header">
  <a href="index.php" class="logo">yass<span>ota</span></a>

  <form action="index.php" method="get" class="header-search">
    <input type="text" name="q" placeholder="ابحث عن تطبيق أو لعبة..." value="<?= h($search) ?>">
    <button type="submit"><?= icon_svg('search') ?></button>
  </form>

  <nav class="header-nav">
    <a href="index.php" class="<?= !$catSlug && !$search ? 'active' : '' ?>">الرئيسية</a>
    <a href="index.php?cat=apps">تطبيقات</a>
    <a href="index.php?cat=games">ألعاب</a>
    <button class="nav-toggle" aria-label="القائمة"><?= icon_svg('menu') ?></button>
  </nav>
</header>

<div class="page-wrap">

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar">
  <div class="sidebar-section">
    <div class="sidebar-title">الأقسام</div>
    <a href="index.php" class="sidebar-link <?= !$catSlug ? 'active' : '' ?>">
      <?= icon_svg('home') ?> الكل
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="index.php?cat=<?= h($cat['slug']) ?>" class="sidebar-link <?= $catSlug === $cat['slug'] ? 'active' : '' ?>">
      <?= icon_svg($cat['slug'] === 'games' ? 'games' : 'apps') ?>
      <?= h($cat['name']) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-title">الموقع</div>
    <a href="index.php#about" class="sidebar-link">
      <?= icon_svg('info') ?> من نحن
    </a>
  </div>

  <div class="sidebar-about">
    <h4>من نحن</h4>
    <p>yassota منصة عربية لتحميل أفضل تطبيقات وألعاب أندرويد مجاناً. نقدم محتوى موثوق وآمن للمستخدم العربي.</p>
  </div>
</aside>

<!-- ══ MAIN ══ -->
<main class="main-content">

  <!-- Ad Zone Top -->
  <div class="ad-zone">
    <script>/* MoneyTag Zone 258058 */</script>
  </div>

  <!-- Hero Banner -->
  <?php if (!$search && !$catSlug): ?>
  <div class="hero-banner reveal">
    <div class="hero-grid">
      <div>
        <h1>أفضل التطبيقات<br>في مكان واحد</h1>
        <p>حمّل أحدث تطبيقات وألعاب أندرويد مجاناً وبسرعة — بدون تسجيل، بدون مشكلات.</p>
      </div>
      <div class="hero-rec">
        <div class="rec-badge"><span class="rec-dot"></span>REC</div>
        <div class="rec-timer">00:00</div>
        <div class="rec-label">مدة التصفح الحالية</div>
      </div>
    </div>
  </div>
  <?= wave_svg() ?>
  <?php endif; ?>

  <!-- Category Chips -->
  <div class="cat-chips reveal">
    <div class="cat-chip <?= !$catSlug ? 'active' : '' ?>" data-cat="all"><?= icon_svg('apps') ?> الكل</div>
    <?php foreach ($categories as $cat): ?>
    <div class="cat-chip <?= $catSlug === $cat['slug'] ? 'active' : '' ?>" data-cat="<?= h($cat['slug']) ?>">
      <?= icon_svg($cat['slug'] === 'games' ? 'games' : 'apps') ?> <?= h($cat['name']) ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Featured App -->
  <?php if ($featured && !$search && !$catSlug): ?>
  <a href="app.php?slug=<?= h($featured['slug']) ?>" class="featured-card reveal" data-hardnav="1">
    <?php if ($featured['icon_path']): ?>
      <img src="<?= h($featured['icon_path']) ?>" alt="<?= h($featured['name']) ?>" class="featured-icon">
    <?php else: ?>
      <div class="featured-icon" style="background:linear-gradient(135deg,#152642,#1e3356)"></div>
    <?php endif; ?>
    <div class="featured-info">
      <div class="app-card-cat">مميز — <?= h($featured['cat_name'] ?? '') ?></div>
      <div class="featured-name"><?= h($featured['name']) ?></div>
      <div class="featured-desc"><?= h(mb_strimwidth($featured['short_description'] ?? '', 0, 120, '...')) ?></div>
      <span class="btn-outline"><?= icon_svg('download') ?> تحميل مجاني</span>
    </div>
  </a>
  <?= wave_svg() ?>
  <?php endif; ?>

  <!-- Apps Grid -->
  <div class="section-head reveal">
    <span class="section-title">
      <?= $search ? 'نتائج: ' . h($search) : ($catSlug ? h($categories[array_search($catSlug, array_column($categories,'slug'))]['name'] ?? 'التطبيقات') : 'أحدث التطبيقات') ?>
    </span>
    <span style="font-family:var(--f-mono);font-size:12px;color:var(--muted)"><?= number_format($totalApps) ?> تطبيق</span>
  </div>

  <?php if ($apps): ?>
  <div class="apps-grid">
    <?php foreach ($apps as $app): ?>
    <div class="app-card reveal">
      <a href="app.php?slug=<?= h($app['slug']) ?>" data-hardnav="1">
        <?php if ($app['icon_path']): ?>
          <img src="<?= h($app['icon_path']) ?>" alt="<?= h($app['name']) ?>" class="app-card-icon" loading="lazy">
        <?php else: ?>
          <div class="app-card-icon-placeholder">
            <?= icon_svg('apps') ?>
          </div>
        <?php endif; ?>
        <div class="app-card-cat"><?= h($app['cat_name'] ?? 'تطبيق') ?></div>
        <div class="app-card-name"><?= h($app['name']) ?></div>
        <div class="app-card-meta">
          <div class="app-card-rating">
            <?= icon_svg('star') ?>
            <span><?= h($app['rating']) ?></span>
          </div>
          <span class="app-card-size"><?= h($app['size_mb'] ? $app['size_mb'] . ' MB' : '') ?></span>
        </div>
      </a>
      <a href="download.php?id=<?= $app['id'] ?>" class="btn-dl-card" data-hardnav="1">
        <?= icon_svg('download') ?> تحميل
      </a>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination reveal">
    <?php for ($p = max(1, $page-2); $p <= min($totalPages, $page+2); $p++): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
       class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div style="text-align:center;padding:60px 20px;color:var(--muted)">
    <p style="font-size:16px">لا توجد نتائج<?= $search ? " لـ \"" . h($search) . "\"" : '' ?></p>
  </div>
  <?php endif; ?>

  <!-- Ad Zone Mid -->
  <?= wave_svg() ?>
  <div class="ad-zone">
    <script>/* MoneyTag Zone 258058 */</script>
  </div>

  <!-- About Section -->
  <section id="about" style="margin-top:12px">
    <?= wave_svg() ?>
    <div class="section-head reveal"><span class="section-title">من نحن</span></div>
    <div style="background:var(--navy-700);border:1px solid var(--border-c);border-radius:var(--radius-lg);padding:32px;display:grid;grid-template-columns:1fr 1fr;gap:24px" class="reveal">
      <div>
        <h2 style="font-family:var(--f-head);font-size:20px;font-weight:900;margin-bottom:12px;background:linear-gradient(135deg,var(--white),var(--cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent">
          yassota — منصتك العربية للتطبيقات
        </h2>
        <p style="color:var(--muted);font-size:14px;line-height:1.8">
          yassota منصة عربية متخصصة في توفير أفضل تطبيقات وألعاب أندرويد. نسعى لتقديم محتوى موثوق وآمن ومجاني للمستخدم العربي، مع معلومات دقيقة عن كل تطبيق من إصدار ومطور وحجم.
        </p>
      </div>
      <div style="display:flex;flex-direction:column;gap:12px">
        <?php
        $stats = [
            ['num' => $totalApps, 'label' => 'تطبيق متاح'],
            ['num' => count($categories), 'label' => 'تصنيف'],
        ];
        foreach ($stats as $s): ?>
        <div style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:12px;padding:16px;display:flex;align-items:center;gap:16px">
          <span style="font-family:var(--f-mono);font-size:28px;font-weight:600;color:var(--cyan)"><?= number_format($s['num']) ?></span>
          <span style="color:var(--muted);font-size:14px"><?= $s['label'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

</main>
</div>

<!-- ══ FOOTER ══ -->
<footer class="site-footer">
  <div class="footer-logo">yass<span style="color:var(--purple)">ota</span></div>
  <p>&copy; <?= date('Y') ?> yassota — جميع الحقوق محفوظة</p>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
