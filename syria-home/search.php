<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$q = trim($_GET['q'] ?? '');
$articles = []; $tools = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare("SELECT a.*, c.name AS category_name FROM articles a LEFT JOIN categories c ON c.id=a.category_id
        WHERE a.status='published' AND (a.title LIKE ? OR a.excerpt LIKE ? OR a.tags LIKE ?) ORDER BY a.published_at DESC LIMIT 12");
    $stmt->execute([$like, $like, $like]);
    $articles = $stmt->fetchAll();

    $stmt2 = $pdo->prepare("SELECT * FROM tools WHERE status='published' AND (name LIKE ? OR short_description LIKE ?) ORDER BY uses_count DESC LIMIT 8");
    $stmt2->execute([$like, $like]);
    $tools = $stmt2->fetchAll();
}
?><!doctype html><html lang="en"><head>
<?php seo_head([
    'title' => ($q !== '' ? 'Search results for “' . $q . '”' : 'Search') . ' | ' . setting('site_name'),
    'description' => 'Search articles, guides and free tools on ' . setting('site_name') . '.',
    'canonical' => site_url('search.php'),
]); ?>
</head><body>
<?php site_header(); ?>

<div class="page-hero container">
  <span class="eyebrow"><i class="fa-solid fa-magnifying-glass"></i> Search</span>
  <h1><?= $q !== '' ? 'Results for “' . e($q) . '”' : 'Search Syria Home' ?></h1>
  <form class="hero-search" action="<?= site_url('search.php') ?>" method="get">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search articles &amp; tools...">
    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
  </form>
</div>

<div class="container">
  <?php if ($q === ''): ?>
    <div class="empty-state"><p>Type something to search.</p></div>
  <?php elseif (!$articles && !$tools): ?>
    <div class="empty-state"><p>No results for “<?= e($q) ?>”. Try a different term.</p></div>
  <?php else: ?>
    <?php if ($articles): ?>
      <div class="section-head"><h2>Articles</h2></div>
      <div class="grid"><?php foreach ($articles as $a) article_card($a); ?></div>
    <?php endif; ?>
    <?php if ($tools): ?>
      <div class="section-head"><h2>Tools</h2></div>
      <div class="grid grid-tools"><?php foreach ($tools as $t) tool_card($t); ?></div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php site_footer(); ?>
</body></html>
