<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

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
    $where .= " AND (a.name LIKE ? OR a.short_description LIKE ? OR a.developer LIKE ? OR a.keywords LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
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

$activeNav = $catSlug === 'games' ? 'games' : ($catSlug === 'apps' ? 'apps' : 'home');
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
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="yassota — تحميل التطبيقات">
  <meta name="twitter:description" content="أفضل تطبيقات أندرويد مجاناً">
  <link rel="stylesheet" href="assets/css/main.css">
  <!-- MoneyTag Ads -->
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189"
     crossorigin="anonymous"></script>
  <script src="https://quge5.com/88/tag.min.js" data-zone="258058" async data-cfasync="false"></script>
</head>
<body>

<?php render_site_header($search, $activeNav); ?>

<div class="page-wrap">

<?php render_site_sidebar($pdo, $catSlug); ?>

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
  <?= partial_wave() ?>
  <?php endif; ?>

  <!-- Category Chips -->
  <div class="cat-chips reveal">
    <div class="cat-chip <?= !$catSlug ? 'active' : '' ?>" data-cat="all"><?= partial_icon('apps') ?> الكل</div>
    <?php foreach ($categories as $cat): ?>
    <div class="cat-chip <?= $catSlug === $cat['slug'] ? 'active' : '' ?>" data-cat="<?= h($cat['slug']) ?>">
      <?= partial_icon($cat['slug'] === 'games' ? 'games' : 'apps') ?> <?= h($cat['name']) ?>
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
      <span class="btn-outline"><?= partial_icon('download') ?> تحميل مجاني</span>
    </div>
  </a>
  <?= partial_wave() ?>
  <?php endif; ?>

  <!-- Apps Grid -->
  <div class="section-head reveal">
    <span class="section-title">
      <?= $search ? 'نتائج: ' . h($search) : ($catSlug ? h($categories[array_search($catSlug, array_column($categories,'slug'))]['name'] ?? 'التطبيقات') : 'أحدث التطبيقات') ?>
    </span>
    <span style="font-family:var(--f-mono);font-size:12px;color:var(--muted)"><?= number_format($totalApps) ?> تطبيق</span>
  </div>

  <?php render_app_grid($apps, 'لا توجد نتائج' . ($search ? " لـ \"$search\"" : '')); ?>
  <?php render_pagination($page, $totalPages); ?>

  <!-- Ad Zone Mid -->
  <?= partial_wave() ?>
  <div class="ad-zone">
    <script>/* MoneyTag Zone 258058 */</script>
  </div>

  <!-- About Section -->
  <section id="about" style="margin-top:12px">
    <?= partial_wave() ?>
    <div class="section-head reveal"><span class="section-title">من نحن</span></div>
    <div style="background:var(--navy-700);border:1px solid var(--border-c);border-radius:var(--radius-lg);padding:32px;display:grid;grid-template-columns:1fr 1fr;gap:24px" class="reveal">
      <div>
        <h2 style="font-family:var(--f-head);font-size:20px;font-weight:900;margin-bottom:12px;background:linear-gradient(135deg,var(--white),var(--cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent">
          yassota — منصتك العربية للتطبيقات
        </h2>
        <p style="color:var(--muted);font-size:14px;line-height:1.8">
          yassota منصة عربية متخصصة في توفير أفضل تطبيقات وألعاب أندرويد. نسعى لتقديم محتوى موثوق وآمن ومجاني للمستخدم العربي، مع معلومات دقيقة عن كل تطبيق من إصدار ومطور وحجم.
        </p>
        <a href="about.php" style="color:var(--cyan);font-size:13px;display:inline-block;margin-top:10px"><?= partial_icon('arrow-r') ?> اقرأ المزيد عن yassota</a>
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

<?php render_site_footer(); ?>

<script src="assets/js/main.js"></script>
</body>
</html>
