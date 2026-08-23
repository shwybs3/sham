<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') { header('Location: ' . site_url('')); exit; }

$st = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
$st->execute([$slug]);
$page = $st->fetch();
if (!$page) { include __DIR__ . '/404.php'; exit; }

$title = $page['meta_title'] ?: $page['title'];
$desc  = $page['meta_description'] ?: setting('site_description', '');
$crumbs = [
    ['name' => 'Home', 'url' => site_url('')],
    ['name' => $page['title'], 'url' => site_url('p/' . $slug)],
];
?>
<!doctype html>
<html lang="en">
<head>
<?php seo_head(['title' => $title, 'description' => $desc, 'canonical' => site_url('p/' . $slug), 'jsonld' => breadcrumb_jsonld($crumbs)]); ?>
</head>
<body>
<?php site_header(); ?>
<main class="container" style="max-width:820px;margin:40px auto;padding:0 20px 60px">
  <article class="prose-article">
    <h1><?= e($page['title']) ?></h1>
    <div class="article-body">
      <?= $page['body'] /* HTML stored & sanitized at write-time */ ?>
    </div>
  </article>
</main>
<?php site_footer(); ?>
</body>
</html>
