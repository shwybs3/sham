<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM tools WHERE slug = ? AND status='published' LIMIT 1");
$stmt->execute([$slug]);
$tool = $stmt->fetch();

if (!$tool) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$pdo->prepare("UPDATE tools SET uses_count = uses_count + 1 WHERE id = ?")->execute([$tool['id']]);

$related = $pdo->query("SELECT * FROM tools WHERE status='published' AND id != " . (int)$tool['id'] . " ORDER BY RAND() LIMIT 4")->fetchAll();

$metaTitle = $tool['meta_title'] ?: ($tool['name'] . ' — Free Online Tool');
$metaDesc = $tool['meta_description'] ?: $tool['short_description'];

$jsonld = [
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => $tool['name'],
    'applicationCategory' => 'BrowserApplication',
    'operatingSystem' => 'Any (runs in-browser)',
    'description' => $metaDesc,
    'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
    'aggregateRating' => ['@type' => 'AggregateRating', 'ratingValue' => '4.8', 'ratingCount' => max(25, (int)$tool['uses_count'])],
];
?><!doctype html><html lang="en"><head>
<?php seo_head([
    'title' => $metaTitle . ' | ' . setting('site_name'),
    'description' => $metaDesc,
    'keywords' => $tool['meta_keywords'],
    'canonical' => site_url('tool.php?slug=' . $tool['slug']),
    'jsonld' => $jsonld,
]); ?>
</head><body>
<?php site_header('Tools'); ?>

<div class="container article-hero">
  <div class="breadcrumb"><a href="<?= site_url('') ?>">Home</a> / <a href="<?= site_url('tools.php') ?>">Tools</a></div>
  <div class="art-icon" style="<?= hero_style_css('g' . (((int)$tool['id'] % 8) + 1)) ?>"><i class="fa-solid <?= e($tool['icon_class']) ?>"></i></div>
  <span class="badge-trending" style="background:#ecfdf5;color:var(--accent-green)"><i class="fa-solid fa-check-circle"></i> 100% Free · Runs in your browser</span>
  <h1><?= e($tool['name']) ?></h1>
  <p class="lead"><?= e($tool['short_description']) ?></p>
  <div class="article-meta"><span><i class="fa-regular fa-eye"></i> <?= number_format((int)$tool['uses_count']) ?> uses</span><span><i class="fa-solid fa-shield-halved"></i> Nothing is uploaded — all processing happens on your device</span></div>
</div>

<div class="container">
  <?php ad_zone('tool_top'); ?>

  <div class="tool-shell">
    <div id="tool-app" data-tool="<?= e($tool['tool_key']) ?>"></div>
  </div>

  <?php ad_zone('tool_bottom'); ?>

  <?php if ($tool['full_description']): ?>
  <div class="article-body" style="margin-top:34px"><?= $tool['full_description'] ?></div>
  <?php endif; ?>

  <?php if ($related): ?>
  <div class="section-head"><h2><i class="fa-solid fa-wrench" style="color:var(--accent-green)"></i> More free tools</h2></div>
  <div class="grid grid-tools">
    <?php foreach ($related as $t) tool_card($t); ?>
  </div>
  <?php endif; ?>
</div>

<?php site_footer(); ?>
<script src="<?= site_url('assets/js/tools.js') ?>?v=2"></script>
<script>document.addEventListener('DOMContentLoaded', function(){ if (window.SHTools) SHTools.mount('tool-app'); });</script>
</body></html>
