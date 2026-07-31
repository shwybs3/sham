<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=utf-8');

$prods = db()->query("SELECT slug, created_at FROM products WHERE active=1")->fetchAll();
$cats  = db()->query("SELECT slug FROM categories")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc><?= clean(siteUrl('index.php')) ?></loc><changefreq>daily</changefreq><priority>1.0</priority></url>
  <url><loc><?= clean(siteUrl('index.php')) ?>#products</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
  <url><loc><?= clean(siteUrl('about.php')) ?></loc><changefreq>monthly</changefreq><priority>0.5</priority></url>
  <url><loc><?= clean(siteUrl('contact.php')) ?></loc><changefreq>monthly</changefreq><priority>0.4</priority></url>
  <url><loc><?= clean(siteUrl('privacy-policy.php')) ?></loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
  <url><loc><?= clean(siteUrl('terms.php')) ?></loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
  <url><loc><?= clean(siteUrl('refund-policy.php')) ?></loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
  <url><loc><?= clean(siteUrl('cookie-policy.php')) ?></loc><changefreq>monthly</changefreq><priority>0.2</priority></url>
  <?php foreach ($prods as $p): ?>
  <url>
    <loc><?= clean(siteUrl('product.php?slug=' . rawurlencode($p['slug']))) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($p['created_at'])) ?></lastmod>
    <changefreq>weekly</changefreq><priority>0.8</priority>
  </url>
  <?php endforeach; ?>
</urlset>
