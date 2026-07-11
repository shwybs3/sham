<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') { header('Location: ' . url('')); exit; }

$stmt = $pdo->prepare("SELECT ar.*, a.name AS app_name, a.slug AS app_slug, a.icon_path, a.short_description, a.rating
    FROM app_articles ar JOIN apps a ON ar.app_id = a.id
    WHERE ar.slug=? AND a.status='published'");
$stmt->execute([$slug]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>404</title>
    <link rel="stylesheet" href="assets/css/main.css"></head><body style="display:flex;align-items:center;justify-content:center;height:100vh;flex-direction:column;gap:16px">
    <p style="font-size:64px;font-family:var(--f-mono);color:var(--cyan)">404</p>
    <p style="color:var(--muted)">المقال غير موجود</p>
    <a href="/" style="color:var(--cyan)">العودة للرئيسية</a></body></html>';
    exit;
}

$body = clean_long_text($article['body'] ?? '');
$seoTitle = $article['seo_title'] ?: $article['title'];
$metaDesc = $article['meta_description'] ?: mb_substr(trim(strip_tags($body)), 0, 160);

// Other articles about the same app, for further internal linking
$otherStmt = $pdo->prepare("SELECT id,title,slug FROM app_articles WHERE app_id=? AND id!=? ORDER BY created_at DESC LIMIT 5");
$otherStmt->execute([$article['app_id'], $article['id']]);
$otherArticles = $otherStmt->fetchAll();

$breadcrumbSchema = json_encode([
    "@context" => "https://schema.org", "@type" => "BreadcrumbList",
    "itemListElement" => [
        ["@type" => "ListItem", "position" => 1, "name" => "الرئيسية", "item" => url('')],
        ["@type" => "ListItem", "position" => 2, "name" => $article['app_name'], "item" => app_url($article['app_slug'])],
        ["@type" => "ListItem", "position" => 3, "name" => $article['title'], "item" => article_url($article['slug'])],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$articleSchema = json_encode([
    "@context" => "https://schema.org", "@type" => "Article",
    "headline" => $article['title'],
    "description" => $metaDesc,
    "datePublished" => date('c', strtotime($article['created_at'])),
    "author" => ["@type" => "Organization", "name" => "yassota"],
    "publisher" => ["@type" => "Organization", "name" => "yassota"],
    "mainEntityOfPage" => article_url($article['slug']),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($seoTitle) ?></title>
  <meta name="description" content="<?= h($metaDesc) ?>">
  <link rel="canonical" href="<?= h(article_url($article['slug'])) ?>">
  <meta property="og:type" content="article">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <?php if ($article['icon_path']): ?><meta property="og:image" content="<?= h(url($article['icon_path'])) ?>"><?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">
  <script type="application/ld+json"><?= $breadcrumbSchema ?></script>
  <script type="application/ld+json"><?= $articleSchema ?></script>
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
    <a href="<?= h(app_url($article['app_slug'])) ?>" style="color:var(--cyan)"><?= h($article['app_name']) ?></a>
    <span>/</span>
    <span><?= h($article['title']) ?></span>
  </nav>

  <div class="section-head reveal">
    <span class="section-title"><?= h($article['title']) ?></span>
  </div>

  <div class="section-box reveal" style="margin-bottom:20px">
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px;padding-bottom:18px;border-bottom:1px solid var(--border-c)">
      <?php if ($article['icon_path']): ?>
        <img src="<?= h($article['icon_path']) ?>" alt="<?= h($article['app_name']) ?>" style="width:48px;height:48px;border-radius:12px;object-fit:cover;flex-shrink:0">
      <?php endif; ?>
      <div style="flex:1;min-width:0">
        <div style="font-size:12px;color:var(--muted)">مقال عن</div>
        <div style="font-weight:700"><?= h($article['app_name']) ?></div>
      </div>
      <a href="<?= h(download_url($article['app_slug'])) ?>" class="btn-primary" style="padding:10px 18px;font-size:13px;flex-shrink:0" data-hardnav="1">
        <?= partial_icon('download') ?> تحميل <?= h($article['app_name']) ?>
      </a>
    </div>

    <div style="color:var(--muted);font-size:14px;line-height:1.9"><?= nl2br(h($body)) ?></div>
  </div>

  <div class="section-box reveal" style="text-align:center;padding:28px">
    <p style="margin-bottom:14px;font-size:14px">أعجبك <?= h($article['app_name']) ?>؟ حمّله الآن مجاناً وبرابط مباشر</p>
    <a href="<?= h(download_url($article['app_slug'])) ?>" class="btn-primary" data-hardnav="1">
      <?= partial_icon('download') ?> تحميل <?= h($article['app_name']) ?> آخر إصدار
    </a>
  </div>

  <?php if ($otherArticles): ?>
  <div class="section-head reveal"><span class="section-title">مقالات أخرى عن <?= h($article['app_name']) ?></span></div>
  <div class="section-box reveal" style="margin-bottom:20px">
    <?php foreach ($otherArticles as $oa): ?>
      <a href="<?= h(article_url($oa['slug'])) ?>" class="sidebar-link" style="border-bottom:1px solid var(--border-c);padding:12px 4px"><?= partial_icon('arrow-r') ?> <?= h($oa['title']) ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
