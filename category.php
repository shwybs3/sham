<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$slug = trim($_GET['slug'] ?? '');
$stmt = $pdo->prepare("SELECT * FROM categories WHERE slug=?");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="' . (defined('UI_LANG') ? UI_LANG : 'ar') . '" dir="' . (defined('UI_DIR') ? UI_DIR : 'rtl') . '"><head><meta charset="UTF-8"><title>404</title>
    <link rel="stylesheet" href="assets/css/main.css"></head><body style="display:flex;align-items:center;justify-content:center;height:100vh;flex-direction:column;gap:16px">
    <p style="font-size:64px;font-family:var(--f-mono);color:var(--cyan)">404</p>
    <p style="color:var(--muted)">التصنيف غير موجود</p>
    <a href="/" style="color:var(--cyan)">العودة للرئيسية</a></body></html>';
    exit;
}

if (page_cache_start($pdo, $_SERVER['REQUEST_URI'])) exit;

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM apps WHERE category_id=? AND status='published'");
$countStmt->execute([$category['id']]);
$totalApps  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalApps / $perPage));

$stmt = $pdo->prepare("SELECT a.*, c.name AS cat_name FROM apps a LEFT JOIN categories c ON a.category_id=c.id
    WHERE a.category_id=? AND a.status='published' ORDER BY a.downloads DESC, a.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute([$category['id']]);
$apps = $stmt->fetchAll();

$seoTitle = "أفضل تطبيقات وألعاب {$category['name']} لأندرويد — تحميل مجاني | yassota";
$metaDesc = !empty($category['description'])
    ? mb_substr(trim(strip_tags($category['description'])), 0, 160)
    : "تصفح وحمّل أفضل {$category['name']} لأندرويد مجاناً وبسرعة على yassota — تحديث مستمر وروابط تحميل مباشرة.";

$breadcrumbSchema = json_encode([
    "@context" => "https://schema.org", "@type" => "BreadcrumbList",
    "itemListElement" => [
        ["@type" => "ListItem", "position" => 1, "name" => "الرئيسية", "item" => url('')],
        ["@type" => "ListItem", "position" => 2, "name" => $category['name'], "item" => url('category/' . $category['slug'])],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$activeNav = $category['slug'] === 'games' ? 'games' : ($category['slug'] === 'apps' ? 'apps' : 'home');
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
  <link rel="canonical" href="<?= h(url('category/' . $category['slug'])) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <meta property="og:url" content="<?= h(url('category/' . $category['slug'])) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= h($seoTitle) ?>">
  <meta name="twitter:description" content="<?= h($metaDesc) ?>">
  <script type="application/ld+json"><?= $breadcrumbSchema ?></script>
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189"
     crossorigin="anonymous"></script>
</head>
<body>

<?php render_site_header('', $activeNav); ?>

<div class="page-wrap fw">

<main class="main-content">

  <nav style="font-size:12px;color:var(--muted);margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <a href="/" style="color:var(--cyan)">الرئيسية</a>
    <span>/</span>
    <span><?= h($category['name']) ?></span>
  </nav>

  <div class="section-head reveal">
    <span class="section-title"><?= h($category['name']) ?></span>
    <span style="font-family:var(--f-mono);font-size:12px;color:var(--muted)"><?= number_format($totalApps) ?> تطبيق</span>
  </div>

  <?php $catDescription = clean_long_text($category['description'] ?? ''); if ($catDescription !== ''): ?>
  <div class="section-box reveal" style="margin-bottom:16px">
    <div style="color:var(--muted);font-size:14px;line-height:1.85"><?= nl2br(h($catDescription)) ?></div>
  </div>
  <?php endif; ?>

  <?php render_app_grid($apps, "لا توجد تطبيقات في تصنيف \"{$category['name']}\" بعد"); ?>
  <?php render_pagination($page, $totalPages); ?>

</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
<?php page_cache_end($pdo, $_SERVER['REQUEST_URI']); ?>
