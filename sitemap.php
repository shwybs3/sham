<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=utf-8');

$apps = $pdo->query("SELECT slug, updated_at FROM apps WHERE status='published'")->fetchAll();
$cats = $pdo->query("SELECT slug FROM categories")->fetchAll();
$developers = $pdo->query("SELECT DISTINCT developer FROM apps WHERE status='published' AND developer IS NOT NULL AND developer<>''")->fetchAll(PDO::FETCH_COLUMN);
$articles = $pdo->query("SELECT ar.slug, ar.created_at FROM app_articles ar JOIN apps a ON ar.app_id=a.id WHERE a.status='published'")->fetchAll();
$blogPosts = $pdo->query("SELECT slug, updated_at FROM blog_posts WHERE status='published'")->fetchAll();
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc><?= SITE_URL ?>/</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
  <url><loc><?= SITE_URL ?>/top.php?by=downloads</loc><changefreq>daily</changefreq><priority>0.8</priority></url>
  <url><loc><?= SITE_URL ?>/top.php?by=views</loc><changefreq>daily</changefreq><priority>0.8</priority></url>
  <url><loc><?= SITE_URL ?>/updates.php</loc><changefreq>hourly</changefreq><priority>0.8</priority></url>
  <url><loc><?= SITE_URL ?>/about.php</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>
  <url><loc><?= SITE_URL ?>/contact.php</loc><changefreq>monthly</changefreq><priority>0.4</priority></url>
  <url><loc><?= SITE_URL ?>/privacy-policy.php</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
  <url><loc><?= SITE_URL ?>/terms.php</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
  <url><loc><?= SITE_URL ?>/cookie-policy.php</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
  <url><loc><?= SITE_URL ?>/dmca.php</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
  <?php foreach ($cats as $c): ?>
  <url><loc><?= SITE_URL ?>/category.php?slug=<?= urlencode($c['slug']) ?></loc><changefreq>daily</changefreq><priority>0.7</priority></url>
  <?php endforeach; ?>
  <?php foreach ($developers as $d): ?>
  <url><loc><?= SITE_URL ?>/developer.php?name=<?= urlencode($d) ?></loc><changefreq>weekly</changefreq><priority>0.6</priority></url>
  <?php endforeach; ?>
  <?php foreach ($apps as $a): ?>
  <url>
    <loc><?= h(app_url($a['slug'])) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($a['updated_at'])) ?></lastmod>
    <changefreq>weekly</changefreq><priority>0.8</priority>
  </url>
  <?php endforeach; ?>
  <?php foreach ($articles as $art): ?>
  <url>
    <loc><?= h(article_url($art['slug'])) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($art['created_at'])) ?></lastmod>
    <changefreq>monthly</changefreq><priority>0.5</priority>
  </url>
  <?php endforeach; ?>
  <url><loc><?= SITE_URL ?>/blog.php</loc><changefreq>daily</changefreq><priority>0.7</priority></url>
  <?php foreach ($blogPosts as $bp): ?>
  <url>
    <loc><?= h(blog_post_url($bp['slug'])) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($bp['updated_at'])) ?></lastmod>
    <changefreq>monthly</changefreq><priority>0.6</priority>
  </url>
  <?php endforeach; ?>
</urlset>
