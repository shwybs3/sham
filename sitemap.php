<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=utf-8');

$apps = $pdo->query("SELECT slug, updated_at FROM apps WHERE status='published'")->fetchAll();
$cats = $pdo->query("SELECT slug FROM categories")->fetchAll();
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc><?= SITE_URL ?>/</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
  <?php foreach ($cats as $c): ?>
  <url><loc><?= SITE_URL ?>/index.php?cat=<?= urlencode($c['slug']) ?></loc><changefreq>daily</changefreq><priority>0.7</priority></url>
  <?php endforeach; ?>
  <?php foreach ($apps as $a): ?>
  <url>
    <loc><?= SITE_URL ?>/app.php?slug=<?= urlencode($a['slug']) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($a['updated_at'])) ?></lastmod>
    <changefreq>weekly</changefreq><priority>0.8</priority>
  </url>
  <?php endforeach; ?>
</urlset>
