<?php
/**
 * syp/sitemap.php — XML sitemap for syp.yassota.com
 */
define('SYP_DATA', true);
$pairs = require __DIR__ . '/data.php';
$base  = 'https://syp.yassota.com';
$today = date('Y-m-d');

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= $base ?>/</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
<?php foreach ($pairs as $slug => $p): ?>
  <url>
    <loc><?= $base ?>/<?= htmlspecialchars($slug) ?>/</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.8</priority>
  </url>
<?php endforeach; ?>
</urlset>
