<?php
/* ═══════════════════════════════════════════
   sitemap.xml
   كل تطبيق بحالة "published" يُدرج في الخريطة
   — تقييم الجودة (Layos) للإرشاد فقط وليس بوابة
   ═══════════════════════════════════════════ */
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');
// 1-hour public cache so bots don't hammer the DB
header('Cache-Control: public, max-age=3600, s-maxage=3600');

// ── كل التطبيقات المنشورة بدون استثناء ──
$apps = [];
try {
    $apps = $pdo->query(
        "SELECT id, slug, name, seo_title, meta_desc, meta_description,
                long_description, icon_path, download_url, updated_at
         FROM apps
         WHERE status='published'
         ORDER BY updated_at DESC"
    )->fetchAll();
} catch (\Throwable $e) { $apps = []; }

// ── التصنيفات التي فيها تطبيقات مقبولة ──
$cats = [];
try {
    $cats = $pdo->query(
        "SELECT DISTINCT c.slug
         FROM categories c
         INNER JOIN apps a ON a.category_id = c.id AND a.status='published'
         WHERE c.slug IS NOT NULL AND c.slug <> ''"
    )->fetchAll();
} catch (\Throwable $e) { $cats = []; }

// ── المطورون ──
$developers = [];
try {
    $developers = $pdo->query(
        "SELECT DISTINCT developer FROM apps
         WHERE status='published' AND developer IS NOT NULL AND developer <> ''
         LIMIT 50"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (\Throwable $e) { $developers = []; }

// ── مقالات المدونة ──
$blogPosts = [];
try {
    $blogPosts = $pdo->query(
        "SELECT slug, updated_at FROM blog_posts
         WHERE status='published' ORDER BY updated_at DESC LIMIT 500"
    )->fetchAll();
} catch (\Throwable $e) { $blogPosts = []; }

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

  <!-- الصفحات الثابتة -->
  <url><loc><?= SITE_URL ?>/</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
  <url><loc><?= SITE_URL ?>/top?by=downloads</loc><changefreq>daily</changefreq><priority>0.8</priority></url>
  <url><loc><?= SITE_URL ?>/updates</loc><changefreq>hourly</changefreq><priority>0.8</priority></url>
  <url><loc><?= SITE_URL ?>/blog</loc><changefreq>daily</changefreq><priority>0.7</priority></url>
  <url><loc><?= SITE_URL ?>/exchange</loc><changefreq>daily</changefreq><priority>0.7</priority></url>
  <url><loc><?= SITE_URL ?>/gold</loc><changefreq>daily</changefreq><priority>0.7</priority></url>
  <url><loc><?= SITE_URL ?>/calculators</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/solar</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>
  <!-- الأدوات -->
  <url><loc><?= SITE_URL ?>/tools/age-calc</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/ascii</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/aspect-ratio</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/barcode</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/base-conv</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/bmi</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/calorie</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/chat</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/color-names</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/color-picker</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/colors</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/compress</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/currency</loc><changefreq>daily</changefreq><priority>0.7</priority></url>
  <url><loc><?= SITE_URL ?>/tools/diff</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/dns</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/encode</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/gold-calc</loc><changefreq>daily</changefreq><priority>0.7</priority></url>
  <url><loc><?= SITE_URL ?>/tools/gradient</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/hashtag</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/html-minifier</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/img-convert</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/img-crop</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/invoice</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/ip</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/json-fmt</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/loan</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/lorem</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/markdown</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/ocr</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/pass</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/password</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/pdf-compress</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/pdf-merge</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/pdf-split</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/percentage</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/qr</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/qr-code</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/reading-time</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/regex</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/resize</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/slug</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/syp</loc><changefreq>daily</changefreq><priority>0.7</priority></url>
  <url><loc><?= SITE_URL ?>/tools/timer</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/timezone</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/unit</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/uuid</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/whatsapp</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/whois</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/words</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/tools/write</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
  <url><loc><?= SITE_URL ?>/about</loc><changefreq>monthly</changefreq><priority>0.4</priority></url>
  <url><loc><?= SITE_URL ?>/privacy-policy</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
  <url><loc><?= SITE_URL ?>/terms</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>

  <!-- التصنيفات -->
  <?php foreach ($cats as $c): ?>
  <url>
    <loc><?= SITE_URL ?>/category/<?= rawurlencode($c['slug']) ?></loc>
    <changefreq>daily</changefreq><priority>0.7</priority>
  </url>
  <?php endforeach; ?>

  <!-- المطورون -->
  <?php foreach ($developers as $d): ?>
  <url>
    <loc><?= SITE_URL ?>/developer/<?= rawurlencode($d) ?></loc>
    <changefreq>weekly</changefreq><priority>0.5</priority>
  </url>
  <?php endforeach; ?>

  <!-- التطبيقات المقبولة من Layos (≥50٪) -->
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
    <changefreq>monthly</changefreq><priority>0.6</priority>
  </url>
  <?php endforeach; ?>

</urlset>
