<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$trending = $pdo->query("SELECT a.*, c.name AS category_name FROM articles a LEFT JOIN categories c ON c.id=a.category_id
    WHERE a.status='published' AND a.trending=1 ORDER BY a.published_at DESC LIMIT 3")->fetchAll();

$latest = $pdo->query("SELECT a.*, c.name AS category_name FROM articles a LEFT JOIN categories c ON c.id=a.category_id
    WHERE a.status='published' ORDER BY a.published_at DESC LIMIT 8")->fetchAll();

$tools = $pdo->query("SELECT * FROM tools WHERE status='published' ORDER BY uses_count DESC, id DESC LIMIT 8")->fetchAll();

$cats = $pdo->query("SELECT * FROM categories WHERE type='article' ORDER BY name")->fetchAll();

?><!doctype html><html lang="en"><head>
<?php seo_head([
    'title' => setting('site_name') . ' — Tech News, Comparisons, Guides & Free Web Tools',
    'description' => setting('site_description'),
    'canonical' => site_url(''),
]); ?>
</head><body>
<?php site_header('Home'); ?>

<section class="page-hero container">
  <span class="eyebrow"><i class="fa-solid fa-bolt"></i> Updated daily</span>
  <h1>News, comparisons &amp; guides worth your time — plus free tools that just work.</h1>
  <p class="lead"><?= e(setting('site_tagline')) ?></p>
  <form class="hero-search" action="<?= site_url('search.php') ?>" method="get">
    <input type="text" name="q" placeholder="Search “AI agents”, “PNG to WebP”, “Wi-Fi 7”…">
    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
  </form>
  <div class="chip-row">
    <a class="chip active" href="<?= site_url('articles.php') ?>"><i class="fa-solid fa-layer-group"></i> All</a>
    <?php foreach ($cats as $c): ?>
      <a class="chip" href="<?= site_url('category.php?slug=' . urlencode($c['slug'])) ?>"><i class="fa-solid <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </div>
</section>

<div class="container">
<?php ad_zone('home_top'); ?>

<?php if ($trending): ?>
<div class="section-head"><h2><i class="fa-solid fa-fire" style="color:var(--accent-rose)"></i> Trending right now</h2></div>
<div class="grid">
  <?php foreach ($trending as $a) article_card($a); ?>
</div>
<?php endif; ?>

<div class="section-head"><h2><i class="fa-solid fa-newspaper" style="color:var(--brand1)"></i> Latest articles</h2><a class="more" href="<?= site_url('articles.php') ?>">View all →</a></div>
<div class="grid">
  <?php foreach ($latest as $a) article_card($a); ?>
</div>

<?php ad_zone('home_mid'); ?>

<div class="section-head"><h2><i class="fa-solid fa-wrench" style="color:var(--accent-green)"></i> Free web tools</h2><a class="more" href="<?= site_url('tools.php') ?>">View all →</a></div>
<div class="grid grid-tools">
  <?php foreach ($tools as $t) tool_card($t); ?>
</div>
</div>

<?php site_footer(); ?>
</body></html>
