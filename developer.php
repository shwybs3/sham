<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$dev = trim($_GET['name'] ?? '');
if ($dev === '') { header('Location: ' . url('')); exit; }

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM apps WHERE developer=? AND status='published'");
$countStmt->execute([$dev]);
$totalApps = (int)$countStmt->fetchColumn();

if ($totalApps === 0) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>404</title>
    <link rel="stylesheet" href="assets/css/main.css"></head><body style="display:flex;align-items:center;justify-content:center;height:100vh;flex-direction:column;gap:16px">
    <p style="font-size:64px;font-family:var(--f-mono);color:var(--cyan)">404</p>
    <p style="color:var(--muted)">لا توجد تطبيقات منشورة لهذا المطور</p>
    <a href="/" style="color:var(--cyan)">العودة للرئيسية</a></body></html>';
    exit;
}

$totalPages = max(1, (int)ceil($totalApps / $perPage));
$stmt = $pdo->prepare("SELECT a.*, c.name AS cat_name FROM apps a LEFT JOIN categories c ON a.category_id=c.id
    WHERE a.developer=? AND a.status='published' ORDER BY a.downloads DESC, a.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute([$dev]);
$apps = $stmt->fetchAll();

$seoTitle = "تطبيقات وألعاب {$dev} — كل إصدارات {$dev} لأندرويد | yassota";
$metaDesc = "تصفح كل تطبيقات وألعاب المطور {$dev} المتاحة للتحميل المجاني على yassota.";

$breadcrumbSchema = json_encode([
    "@context" => "https://schema.org", "@type" => "BreadcrumbList",
    "itemListElement" => [
        ["@type" => "ListItem", "position" => 1, "name" => "الرئيسية", "item" => url('')],
        ["@type" => "ListItem", "position" => 2, "name" => $dev, "item" => url('developer/' . rawurlencode($dev))],
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
  <link rel="canonical" href="<?= h(url('developer/' . rawurlencode($dev))) ?>">
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
    <span><?= partial_icon('developer') ?> <?= h($dev) ?></span>
  </nav>

  <div class="section-head reveal">
    <span class="section-title">تطبيقات المطور: <?= h($dev) ?></span>
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
