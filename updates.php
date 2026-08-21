<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$today   = isset($_GET['today']);
public_cache_headers(30);
$_cacheKey = $_SERVER['REQUEST_URI'] . ':lang:' . (defined('UI_LANG') ? UI_LANG : 'ar');
if (page_cache_start($pdo, $_cacheKey)) exit;

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where = "WHERE status='published'" . ($today ? " AND DATE(updated_at) = CURDATE()" : '');

$totalApps  = (int)$pdo->query("SELECT COUNT(*) FROM apps $where")->fetchColumn();
$totalPages = max(1, (int)ceil($totalApps / $perPage));

$stmt = $pdo->prepare("SELECT a.*, c.name AS cat_name FROM apps a LEFT JOIN categories c ON a.category_id=c.id
    $where ORDER BY a.updated_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute();
$apps = $stmt->fetchAll();

$title    = $today ? 'آخر التحديثات اليوم' : 'آخر التحديثات';
$seoTitle = "{$title} — تطبيقات وألعاب أندرويد محدّثة | yassota";
$metaDesc = "تابع أحدث تحديثات التطبيقات والألعاب على yassota أولاً بأول.";

$breadcrumbSchema = json_encode([
    "@context" => "https://schema.org", "@type" => "BreadcrumbList",
    "itemListElement" => [
        ["@type" => "ListItem", "position" => 1, "name" => "الرئيسية", "item" => url('')],
        ["@type" => "ListItem", "position" => 2, "name" => $title, "item" => url('updates.php' . ($today ? '?today=1' : ''))],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="<?= defined('UI_LANG') ? UI_LANG : 'ar' ?>" dir="<?= defined('UI_DIR') ? UI_DIR : 'rtl' ?>">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($seoTitle) ?></title>
  <meta name="description" content="<?= h($metaDesc) ?>">
  <link rel="canonical" href="<?= h(url('updates.php')) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= h($seoTitle) ?>">
  <meta name="twitter:description" content="<?= h($metaDesc) ?>">
  <script type="application/ld+json"><?= $breadcrumbSchema ?></script>
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
</head>
<body>

<?php render_site_header(); ?>

<div class="page-wrap fw">

<main class="main-content">

  <nav style="font-size:12px;color:var(--muted);margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <a href="/" style="color:var(--cyan)">الرئيسية</a>
    <span>/</span>
    <span><?= h($title) ?></span>
  </nav>

  <div class="cat-chips reveal" style="margin-bottom:20px">
    <a href="<?= h(url('updates')) ?>" class="cat-chip <?= !$today ? 'active' : '' ?>" style="text-decoration:none"><?= partial_icon('clock') ?> كل التحديثات</a>
    <a href="<?= h(url('updates?today=1')) ?>" class="cat-chip <?= $today ? 'active' : '' ?>" style="text-decoration:none"><?= partial_icon('clock') ?> تحديثات اليوم فقط</a>
  </div>

  <div class="section-head reveal">
    <span class="section-title"><?= h($title) ?></span>
    <span style="font-family:var(--f-mono);font-size:12px;color:var(--muted)"><?= number_format($totalApps) ?> تطبيق</span>
  </div>

  <?php render_app_grid($apps, $today ? __('no_updates_today') : __('no_updates_yet')); ?>
  <?php render_pagination($page, $totalPages); ?>

</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
<?php page_cache_end($pdo, $_cacheKey); ?>
