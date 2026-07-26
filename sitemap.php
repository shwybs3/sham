<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=utf-8');

$apps        = $pdo->query("SELECT slug, updated_at, icon_path, name FROM apps WHERE status='published' ORDER BY updated_at DESC")->fetchAll();
$cats        = $pdo->query("SELECT slug FROM categories")->fetchAll();
$developers  = $pdo->query("SELECT DISTINCT developer FROM apps WHERE status='published' AND developer IS NOT NULL AND developer<>''")->fetchAll(PDO::FETCH_COLUMN);
$articles    = $pdo->query("SELECT ar.slug, ar.created_at FROM app_articles ar JOIN apps a ON ar.app_id=a.id WHERE a.status='published'")->fetchAll();
$blogPosts   = $pdo->query("SELECT slug, updated_at FROM blog_posts WHERE status='published'")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

  <!-- Static pages -->
  <url><loc><?= SITE_URL ?>/</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
  <url><loc><?= SITE_URL ?>/top?by=downloads</loc><changefreq>daily</changefreq><priority>0.8</priority></url>
  <url><loc><?= SITE_URL ?>/top?by=views</loc><changefreq>daily</changefreq><priority>0.7</priority></url>
  <url><loc><?= SITE_URL ?>/updates</loc><changefreq>hourly</changefreq><priority>0.8</priority></url>
  <url><loc><?= SITE_URL ?>/blog</loc><changefreq>daily</changefreq><priority>0.7</priority></url>
  <url><loc><?= SITE_URL ?>/about</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>
  <url><loc><?= SITE_URL ?>/contact</loc><changefreq>monthly</changefreq><priority>0.4</priority></url>
  <url><loc><?= SITE_URL ?>/privacy-policy</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
  <url><loc><?= SITE_URL ?>/terms</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
  <url><loc><?= SITE_URL ?>/cookie-policy</loc><changefreq>monthly</changefreq><priority>0.2</priority></url>
  <url><loc><?= SITE_URL ?>/dmca</loc><changefreq>monthly</changefreq><priority>0.2</priority></url>

  <!-- Categories -->
  <?php foreach ($cats as $c): ?>
  <url><loc><?= SITE_URL ?>/category/<?= rawurlencode($c['slug']) ?></loc><changefreq>daily</changefreq><priority>0.7</priority></url>
  <?php endforeach; ?>

  <!-- Developers -->
  <?php foreach ($developers as $d): ?>
  <url><loc><?= SITE_URL ?>/developer/<?= rawurlencode($d) ?></loc><changefreq>weekly</changefreq><priority>0.6</priority></url>
  <?php endforeach; ?>

  <!-- Apps -->
  <?php foreach ($apps as $a): ?>
  <url>
    <loc><?= h(app_url($a['slug'])) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($a['updated_at'] ?: 'now')) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.9</priority>
    <?php if (!empty($a['icon_path'])): ?>
    <image:image>
      <image:loc><?= h(media_url($a['icon_path'])) ?></image:loc>
      <image:title><?= h($a['name']) ?></image:title>
    </image:image>
    <?php endif; ?>
  </url>
  <?php endforeach; ?>

  <!-- Blog posts -->
  <?php foreach ($blogPosts as $bp): ?>
  <url>
    <loc><?= h(blog_post_url($bp['slug'])) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($bp['updated_at'] ?: 'now')) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
  <?php endforeach; ?>

  <!-- Articles -->
  <?php foreach ($articles as $art): ?>
  <url>
    <loc><?= h(article_url($art['slug'])) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($art['created_at'] ?: 'now')) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.5</priority>
  </url>
  <?php endforeach; ?>

  <!-- HTML landing pages (/pages/*.html) -->
  <?php
  $pagesDir = __DIR__ . '/pages';
  if (is_dir($pagesDir)) {
      foreach (glob($pagesDir . '/*.html') ?: [] as $htmlFile) {
          $slug    = basename($htmlFile, '.html');
          $lastmod = date('Y-m-d', filemtime($htmlFile));
          echo "  <url>\n";
          echo "    <loc>" . SITE_URL . "/pages/" . rawurlencode($slug) . ".html</loc>\n";
          echo "    <lastmod>{$lastmod}</lastmod>\n";
          echo "    <changefreq>monthly</changefreq>\n";
          echo "    <priority>0.4</priority>\n";
          echo "  </url>\n";
      }
  }
  ?>


</urlset>
