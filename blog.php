<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$type = trim($_GET['type'] ?? '');
if ($type !== '' && !isset(BLOG_TYPES[$type])) $type = '';
if (page_cache_start($pdo, $_SERVER['REQUEST_URI'])) exit;

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

$where  = "WHERE status='published'";
$params = [];
if ($type !== '') { $where .= " AND type=?"; $params[] = $type; }

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM blog_posts $where");
$countStmt->execute($params);
$totalPosts = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalPosts / $perPage));

$stmt = $pdo->prepare("SELECT * FROM blog_posts $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$posts = $stmt->fetchAll();

$title    = $type !== '' ? blog_type_label($type) : 'المدونة';
$seoTitle = "{$title} — yassota";
$metaDesc = $type !== '' ? "مقالات وشروحات في قسم {$title} على yassota." : "مقالات وشروحات وأخبار ومقارنات تطبيقات وألعاب أندرويد على yassota.";

$breadcrumbSchema = json_encode([
    "@context" => "https://schema.org", "@type" => "BreadcrumbList",
    "itemListElement" => [
        ["@type" => "ListItem", "position" => 1, "name" => "الرئيسية", "item" => url('')],
        ["@type" => "ListItem", "position" => 2, "name" => $title, "item" => url('blog.php' . ($type !== '' ? '?type=' . $type : ''))],
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
  <link rel="canonical" href="<?= h(url('blog.php' . ($type !== '' ? '?type=' . $type : ''))) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <script type="application/ld+json"><?= $breadcrumbSchema ?></script>
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189"
     crossorigin="anonymous"></script>
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
    <a href="<?= h(url('blog')) ?>" class="cat-chip <?= $type === '' ? 'active' : '' ?>" style="text-decoration:none">الكل</a>
    <?php foreach (BLOG_TYPES as $t => $label): ?>
    <a href="<?= h(url('blog?type=' . rawurlencode($t))) ?>" class="cat-chip <?= $type === $t ? 'active' : '' ?>" style="text-decoration:none"><?= h($label) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="section-head reveal">
    <span class="section-title"><?= h($title) ?></span>
    <span style="font-family:var(--f-mono);font-size:12px;color:var(--muted)"><?= number_format($totalPosts) ?> مقال</span>
  </div>

  <?php if (!$posts): ?>
    <div style="text-align:center;padding:60px 20px;color:var(--muted)"><p style="font-size:16px">لا توجد مقالات بعد</p></div>
  <?php else: ?>
  <div class="blog-grid">
    <?php foreach ($posts as $p):
        $isCP   = $p['type'] === 'code-page';
        $cpSecs = $isCP ? decode_code_page($p['body'] ?? '') : [];
    ?>
    <a href="<?= h(blog_post_url($p['slug'])) ?>" class="blog-card reveal <?= $isCP ? 'blog-card-codepage' : '' ?>" data-hardnav="1">
      <?php if ($p['cover_image']): ?>
        <img src="<?= h(url($p['cover_image'])) ?>" alt="<?= h($p['title']) ?>" class="blog-card-img" loading="lazy">
      <?php elseif ($isCP): ?>
        <div class="blog-card-img blog-card-img-placeholder blog-card-code-preview">
          <div class="bc-code-deco">
            <?php foreach (array_slice(array_keys($cpSecs), 0, 4) as $l):
                $m = CODE_PAGE_LANGS[$l]; ?>
            <span style="color:<?= h($m['color']) ?>"><?= $m['icon'] ?> <?= h($m['label']) ?></span>
            <?php endforeach; ?>
          </div>
          <div class="bc-code-lines"><span></span><span></span><span></span></div>
        </div>
      <?php else: ?>
        <div class="blog-card-img blog-card-img-placeholder"><?= partial_icon('article') ?></div>
      <?php endif; ?>
      <div class="blog-card-body">
        <span class="blog-card-type <?= $isCP ? 'blog-card-type-code' : '' ?>"><?= h(blog_type_label($p['type'])) ?></span>
        <div class="blog-card-title"><?= h($p['title']) ?></div>
        <?php if ($isCP && $cpSecs): ?>
          <div class="bc-lang-pills">
            <?php foreach (array_keys($cpSecs) as $l):
                $m = CODE_PAGE_LANGS[$l]; ?>
            <span class="bc-lang-pill" style="--lc:<?= h($m['color']) ?>"><?= $m['icon'] ?> <?= h($m['label']) ?></span>
            <?php endforeach; ?>
          </div>
          <p class="blog-card-excerpt"><?= count($cpSecs) ?> <?= count($cpSecs) === 1 ? 'لغة برمجية' : 'لغات برمجية' ?></p>
        <?php elseif ($p['excerpt']): ?>
          <p class="blog-card-excerpt"><?= h(mb_strimwidth($p['excerpt'], 0, 110, '...')) ?></p>
        <?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php render_pagination($page, $totalPages); ?>

</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
<?php page_cache_end($pdo, $_SERVER['REQUEST_URI']); ?>
