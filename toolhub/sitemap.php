<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

  <!-- Static pages -->
  <?php
  $staticPages = [
    ['',           '1.0',  'daily'],
    ['exchange',   '0.9',  'hourly'],
    ['gold',       '0.9',  'hourly'],
    ['fuel',       '0.8',  'weekly'],
    ['food',       '0.7',  'weekly'],
    ['phones',     '0.7',  'weekly'],
    ['pdf',        '0.8',  'monthly'],
    ['images',     '0.8',  'monthly'],
    ['ai',         '0.8',  'monthly'],
    ['calculators','0.8',  'monthly'],
    ['solar',      '0.8',  'monthly'],
    ['articles',   '0.8',  'daily'],
    ['about',      '0.5',  'monthly'],
    ['privacy',    '0.4',  'monthly'],
    ['contact',    '0.5',  'monthly'],
  ];
  foreach ($staticPages as [$slug, $priority, $freq]):
    $url = rtrim(SITE_URL, '/') . ($slug ? "/$slug" : '');
  ?>
  <url>
    <loc><?= htmlspecialchars($url) ?></loc>
    <changefreq><?= $freq ?></changefreq>
    <priority><?= $priority ?></priority>
    <lastmod><?= date('Y-m-d') ?></lastmod>
  </url>
  <?php endforeach; ?>

  <!-- Article pages -->
  <?php
  $pdo = db();
  if ($pdo) {
    $arts = $pdo->query("SELECT slug, updated_at, created_at FROM articles WHERE status='published' ORDER BY created_at DESC LIMIT 500")->fetchAll();
    foreach ($arts as $a):
      $mod = $a['updated_at'] ?? $a['created_at'];
  ?>
  <url>
    <loc><?= htmlspecialchars(rtrim(SITE_URL,'/') . '/article/' . urlencode($a['slug'])) ?></loc>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
    <lastmod><?= date('Y-m-d', strtotime($mod)) ?></lastmod>
  </url>
  <?php endforeach; } ?>

</urlset>
