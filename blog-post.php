<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') { header('Location: ' . url('blog.php')); exit; }

$stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug=? AND status='published'");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>404</title>
    <link rel="stylesheet" href="assets/css/main.css"></head><body style="display:flex;align-items:center;justify-content:center;height:100vh;flex-direction:column;gap:16px">
    <p style="font-size:64px;font-family:var(--f-mono);color:var(--cyan)">404</p>
    <p style="color:var(--muted)">المقال غير موجود</p>
    <a href="/" style="color:var(--cyan)">العودة للرئيسية</a></body></html>';
    exit;
}
if (page_cache_start($pdo, $_SERVER['REQUEST_URI'])) exit;

// Body is stored as HTML from the WYSIWYG editor (admin-only authoring,
// so raw HTML output is intentional and safe — no public user input here).
$body = trim($post['body'] ?? '');
$seoTitle = $post['seo_title'] ?: $post['title'];
$metaDesc = $post['meta_description'] ?: ($post['excerpt'] ?: mb_substr(trim(strip_tags($body)), 0, 160));

// A few apps mentioned by name in the post title/excerpt, for a natural
// "related apps" block linking back to download pages — same internal-
// linking pattern as app_articles.
$related = $pdo->prepare("SELECT id,name,slug,icon_path,rating FROM apps WHERE status='published' ORDER BY downloads DESC LIMIT 6");
$related->execute();
$relatedApps = $related->fetchAll();

$otherStmt = $pdo->prepare("SELECT id,title,slug,cover_image FROM blog_posts WHERE type=? AND id!=? AND status='published' ORDER BY created_at DESC LIMIT 4");
$otherStmt->execute([$post['type'], $post['id']]);
$otherPosts = $otherStmt->fetchAll();

$breadcrumbSchema = json_encode([
    "@context" => "https://schema.org", "@type" => "BreadcrumbList",
    "itemListElement" => [
        ["@type" => "ListItem", "position" => 1, "name" => "الرئيسية", "item" => url('')],
        ["@type" => "ListItem", "position" => 2, "name" => blog_type_label($post['type']), "item" => blog_type_url($post['type'])],
        ["@type" => "ListItem", "position" => 3, "name" => $post['title'], "item" => blog_post_url($post['slug'])],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$articleSchema = json_encode(array_filter([
    "@context" => "https://schema.org", "@type" => "Article",
    "headline" => $post['title'],
    "description" => $metaDesc,
    "image" => $post['cover_image'] ? url($post['cover_image']) : null,
    "datePublished" => date('c', strtotime($post['created_at'])),
    "dateModified" => date('c', strtotime($post['updated_at'])),
    "author" => ["@type" => "Organization", "name" => "yassota"],
    "publisher" => ["@type" => "Organization", "name" => "yassota"],
    "mainEntityOfPage" => blog_post_url($post['slug']),
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
  <?php if ($post['keywords']): ?><meta name="keywords" content="<?= h($post['keywords']) ?>"><?php endif; ?>
  <link rel="canonical" href="<?= h(blog_post_url($post['slug'])) ?>">
  <meta property="og:type" content="article">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <?php if ($post['cover_image']): ?><meta property="og:image" content="<?= h(url($post['cover_image'])) ?>"><?php endif; ?>
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
    <a href="<?= h(blog_type_url($post['type'])) ?>" style="color:var(--cyan)"><?= h(blog_type_label($post['type'])) ?></a>
    <span>/</span>
    <span><?= h($post['title']) ?></span>
  </nav>

  <?php if ($post['cover_image']): ?>
  <img src="<?= h(url($post['cover_image'])) ?>" alt="<?= h($post['title']) ?>" style="width:100%;max-height:340px;object-fit:cover;border-radius:var(--radius-lg);margin-bottom:20px">
  <?php endif; ?>

  <div class="section-head reveal">
    <span class="section-title"><?= h($post['title']) ?></span>
  </div>

  <div class="section-box" style="margin-bottom:20px">
    <div class="blog-body"><?= $body ?></div>
  </div>

  <?php if ($relatedApps): ?>
  <div class="section-head reveal"><span class="section-title">تطبيقات وألعاب قد تعجبك</span></div>
  <div class="apps-grid" style="margin-bottom:20px">
    <?php foreach ($relatedApps as $r): render_app_card($r); endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($otherPosts): ?>
  <div class="section-head reveal"><span class="section-title">مقالات أخرى في <?= h(blog_type_label($post['type'])) ?></span></div>
  <div class="blog-grid" style="margin-bottom:20px">
    <?php foreach ($otherPosts as $op): ?>
    <a href="<?= h(blog_post_url($op['slug'])) ?>" class="blog-card reveal" data-hardnav="1">
      <?php if ($op['cover_image']): ?>
        <img src="<?= h(url($op['cover_image'])) ?>" alt="<?= h($op['title']) ?>" class="blog-card-img" loading="lazy">
      <?php else: ?>
        <div class="blog-card-img blog-card-img-placeholder"><?= partial_icon('article') ?></div>
      <?php endif; ?>
      <div class="blog-card-body"><div class="blog-card-title"><?= h($op['title']) ?></div></div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
<?php page_cache_end($pdo, $_SERVER['REQUEST_URI']); ?>
