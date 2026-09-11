<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') { header('Location: ' . site_url('')); exit; }

$st = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
$st->execute([$slug]);
$page = $st->fetch();
if (!$page) { http_response_code(404); include __DIR__ . '/404.php'; exit; }

enforce_canonical_url(page_url($slug));

$title = $page['meta_title'] ?: $page['title'];
$desc  = $page['meta_description'] ?: setting('site_description', '');
?>
<!doctype html>
<html lang="en">
<head>
<?php seo_head(['title' => $title, 'description' => $desc, 'canonical' => page_url($slug)]); ?>
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
