<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$by = ($_GET['by'] ?? 'downloads') === 'views' ? 'views' : 'downloads';
if (page_cache_start($pdo, $_SERVER['REQUEST_URI'])) exit;

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$totalApps  = (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn();
$totalPages = max(1, (int)ceil($totalApps / $perPage));

$stmt = $pdo->prepare("SELECT a.*, c.name AS cat_name FROM apps a LEFT JOIN categories c ON a.category_id=c.id
    WHERE a.status='published' ORDER BY a.$by DESC, a.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute();
$apps = $stmt->fetchAll();

$title    = $by === 'views' ? 'الأكثر زيارة' : 'الأكثر تحميلاً';
$seoTitle = "أكثر التطبيقات والألعاب {$title} لأندرويد | yassota";
$metaDesc = "قائمة محدّثة بأكثر التطبيقات والألعاب {$title} على yassota — حمّلها مجاناً الآن.";

$breadcrumbSchema = json_encode([
    "@context" => "https://schema.org", "@type" => "BreadcrumbList",
    "itemListElement" => [
        ["@type" => "ListItem", "position" => 1, "name" => "الرئيسية", "item" => url('')],
        ["@type" => "ListItem", "position" => 2, "name" => $title, "item" => url('top.php?by=' . $by)],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($seoTitle) ?></title>
  <meta name="description" content="<?= h($metaDesc) ?>">
  <link rel="canonical" href="<?= h(url('top.php?by=' . $by)) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= h($seoTitle) ?>">
  <meta name="twitter:description" content="<?= h($metaDesc) ?>">
  <script type="application/ld+json"><?= $breadcrumbSchema ?></script>
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189"
     crossorigin="anonymous"></script>
</head>
<body>

<?php render_site_header(); ?>

<div class="page-wrap">
<?php render_site_sidebar($pdo); ?>

<main class="main-content">

  <nav style="font-size:12px;color:var(--muted);margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <a href="/" style="color:var(--cyan)">الرئيسية</a>
    <span>/</span>
    <span><?= h($title) ?></span>
  </nav>

  <div class="cat-chips reveal" style="margin-bottom:20px">
    <a href="top.php?by=downloads" class="cat-chip <?= $by === 'downloads' ? 'active' : '' ?>" style="text-decoration:none"><?= partial_icon('trending') ?> الأكثر تحميلاً</a>
    <a href="top.php?by=views" class="cat-chip <?= $by === 'views' ? 'active' : '' ?>" style="text-decoration:none"><?= partial_icon('trending') ?> الأكثر زيارة</a>
  </div>

  <div class="section-head reveal">
    <span class="section-title"><?= h($title) ?></span>
    <span style="font-family:var(--f-mono);font-size:12px;color:var(--muted)"><?= number_format($totalApps) ?> تطبيق</span>
  </div>

  <?php render_app_grid($apps); ?>
  <?php render_pagination($page, $totalPages); ?>

</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
<?php page_cache_end($pdo, $_SERVER['REQUEST_URI']); ?>
