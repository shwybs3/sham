<?php
/* ═══════════════════════════════════════════
   sitemap.xml — يحتوي فقط على الصفحات الحقيقية
   المقالات التلقائية (app_articles) مُستبعدة
   لأنها محتوى تكميلي وليست صفحات رئيسية
   ═══════════════════════════════════════════ */
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

/* ── فقط التطبيقات المنشورة والموجودة فعلاً ── */
$apps = $pdo->query(
    "SELECT slug, updated_at, icon_path, name
     FROM apps
     WHERE status='published'
       AND (download_url IS NOT NULL AND download_url <> '')
     ORDER BY updated_at DESC"
)->fetchAll();

/* ── التصنيفات التي تحتوي على تطبيقات فعلاً ── */
$cats = $pdo->query(
    "SELECT DISTINCT c.slug
     FROM categories c
     INNER JOIN apps a ON a.category_slug = c.slug AND a.status='published'
     WHERE c.slug IS NOT NULL AND c.slug <> ''"
)->fetchAll();

/* ── المطورون الذين لهم تطبيقات منشورة ── */
$developers = $pdo->query(
    "SELECT DISTINCT developer
     FROM apps
     WHERE status='published'
       AND developer IS NOT NULL AND developer <> ''
     LIMIT 50"
)->fetchAll(PDO::FETCH_COLUMN);

/* ── مقالات المدونة الرئيسية فقط (ليس app_articles) ── */
$blogPosts = $pdo->query(
    "SELECT slug, updated_at
     FROM blog_posts
     WHERE status='published'
     ORDER BY updated_at DESC
     LIMIT 200"
)->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

  <!-- الصفحات الثابتة -->
  <url><loc><?= SITE_URL ?>/</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
  <url><loc><?= SITE_URL ?>/top?by=downloads</loc><changefreq>daily</changefreq><priority>0.8</priority></url>
  <url><loc><?= SITE_URL ?>/updates</loc><changefreq>hourly</changefreq><priority>0.8</priority></url>
  <url><loc><?= SITE_URL ?>/blog</loc><changefreq>daily</changefreq><priority>0.7</priority></url>
  <url><loc><?= SITE_URL ?>/about</loc><changefreq>monthly</changefreq><priority>0.4</priority></url>
  <url><loc><?= SITE_URL ?>/privacy-policy</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
  <url><loc><?= SITE_URL ?>/terms</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>

  <!-- التصنيفات (التي تحتوي على تطبيقات فقط) -->
  <?php foreach ($cats as $c): ?>
  <url>
    <loc><?= SITE_URL ?>/category/<?= rawurlencode($c['slug']) ?></loc>
    <changefreq>daily</changefreq>
    <priority>0.7</priority>
  </url>
  <?php endforeach; ?>

  <!-- المطورون -->
  <?php foreach ($developers as $d): ?>
  <url>
    <loc><?= SITE_URL ?>/developer/<?= rawurlencode($d) ?></loc>
    <changefreq>weekly</changefreq>
    <priority>0.5</priority>
  </url>
  <?php endforeach; ?>

  <!-- التطبيقات (المنشورة ولها رابط تحميل فعلي) -->
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

  <!-- مقالات المدونة الرئيسية -->
  <?php foreach ($blogPosts as $bp): ?>
  <url>
    <loc><?= h(blog_post_url($bp['slug'])) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($bp['updated_at'] ?: 'now')) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
  <?php endforeach; ?>

</urlset>
