<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$lang = is_ar_request() ? 'ar' : 'en';
$dir = $lang === 'ar' ? 'rtl' : 'ltr';

$trending = $pdo->prepare("SELECT a.*, c.name AS category_name FROM articles a LEFT JOIN categories c ON c.id=a.category_id
    WHERE a.status='published' AND a.lang = ? AND a.trending=1 ORDER BY a.published_at DESC LIMIT 3");
$trending->execute([$lang]);
$trending = $trending->fetchAll();

$latest = $pdo->prepare("SELECT a.*, c.name AS category_name FROM articles a LEFT JOIN categories c ON c.id=a.category_id
    WHERE a.status='published' AND a.lang = ? ORDER BY a.published_at DESC LIMIT 8");
$latest->execute([$lang]);
$latest = $latest->fetchAll();

$tools = $pdo->prepare("SELECT * FROM tools WHERE status='published' AND lang = ? ORDER BY uses_count DESC, id DESC LIMIT 8");
$tools->execute([$lang]);
$tools = $tools->fetchAll();

$cats = $pdo->query("SELECT * FROM categories WHERE type='article' ORDER BY name")->fetchAll();

?><!doctype html><html lang="<?= e($lang) ?>" dir="<?= $dir ?>"><head>
<?php seo_head([
    'title' => setting('site_name') . t(' — Tech News, Comparisons, Guides & Free Web Tools', ' — أخبار تقنية ومقارنات وأدلة وأدوات ويب مجانية', $lang),
    'description' => setting('site_description'),
    'canonical' => site_url($lang === 'ar' ? 'ar/' : ''),
    'lang' => $lang,
    'hreflang' => ['en' => site_url(''), 'ar' => site_url('ar/'), 'x-default' => site_url('')],
]); ?>
</head><body>
<?php site_header(t('Home', 'الرئيسية', $lang), $lang); ?>

<section class="page-hero container">
  <span class="eyebrow"><i class="fa-solid fa-bolt"></i> <?= e(t('Updated daily', 'يُحدَّث يومياً', $lang)) ?></span>
  <h1><?= e(t('News, comparisons & guides worth your time — plus free tools that just work.', 'أخبار ومقارنات وأدلة تستحق وقتك — بالإضافة إلى أدوات مجانية تعمل بكفاءة.', $lang)) ?></h1>
  <p class="lead"><?= e(setting('site_tagline')) ?></p>
  <form class="hero-search" action="<?= site_url('search.php') ?>" method="get">
    <input type="text" name="q" placeholder="<?= e(t('Search "AI agents", "PNG to WebP", "Wi-Fi 7"…', 'ابحث عن "وكلاء الذكاء الاصطناعي"، "تحويل PNG إلى WebP"، "Wi-Fi 7"…', $lang)) ?>">
    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
  </form>
  <div class="chip-row">
    <a class="chip active" href="<?= site_url($lang === 'ar' ? 'articles.php?lang=ar' : 'articles.php') ?>"><i class="fa-solid fa-layer-group"></i> <?= e(t('All', 'الكل', $lang)) ?></a>
    <?php foreach ($cats as $c): ?>
      <a class="chip" href="<?= site_url('category.php?slug=' . urlencode($c['slug']) . ($lang === 'ar' ? '&lang=ar' : '')) ?>"><i class="fa-solid <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </div>
</section>

<div class="container">
<?php ad_zone('home_top'); ?>

<?php if ($trending): ?>
<div class="section-head"><h2><i class="fa-solid fa-fire" style="color:var(--accent-rose)"></i> <?= e(t('Trending right now', 'الأكثر رواجاً الآن', $lang)) ?></h2></div>
<div class="grid">
  <?php foreach ($trending as $a) article_card($a); ?>
</div>
<?php endif; ?>

<div class="section-head"><h2><i class="fa-solid fa-newspaper" style="color:var(--brand1)"></i> <?= e(t('Latest articles', 'أحدث المقالات', $lang)) ?></h2><a class="more" href="<?= site_url($lang === 'ar' ? 'articles.php?lang=ar' : 'articles.php') ?>"><?= e(t('View all →', 'عرض الكل ←', $lang)) ?></a></div>
<div class="grid">
  <?php foreach ($latest as $a) article_card($a); ?>
</div>

<?php ad_zone('home_mid'); ?>

<div class="section-head"><h2><i class="fa-solid fa-wrench" style="color:var(--accent-green)"></i> <?= e(t('Free web tools', 'أدوات ويب مجانية', $lang)) ?></h2><a class="more" href="<?= site_url($lang === 'ar' ? 'tools.php?lang=ar' : 'tools.php') ?>"><?= e(t('View all →', 'عرض الكل ←', $lang)) ?></a></div>
<div class="grid grid-tools">
  <?php foreach ($tools as $t) tool_card($t); ?>
</div>
</div>

<?php site_footer($lang); ?>
</body></html>
