<?php
/* ═══════════════════════════════════════════
   sitemap.xml — مدعوم بـ Layos Security
   التطبيقات تُدرج فقط إذا تجاوز تقييم الجودة 50٪
   ═══════════════════════════════════════════ */
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

// ── التطبيقات المنشورة ──
$allApps = $pdo->query(
    "SELECT id, slug, name, seo_title, meta_desc, meta_description,
            long_description, icon_path, download_url, updated_at
     FROM apps
     WHERE status='published'
     ORDER BY updated_at DESC"
)->fetchAll();

// فلتر Layos ≥ 50٪
$apps    = [];
$skipped = 0;
foreach ($allApps as $a) {
    // merge meta_desc aliases
    if (empty($a['meta_desc'])) $a['meta_desc'] = $a['meta_description'] ?? '';
    $result = layos_score_app($a);
    if (layos_should_index($pdo, 'app', (int)$a['id'], $result['can_index'])) {
        $apps[] = $a;
    } else {
        $skipped++;
    }
}

// إشعار YAI عند استبعاد تطبيقات (مرة كل 12 ساعة)
if ($skipped > 0) {
    $lastNotify = get_cfg($pdo, 'layos_sitemap_notify_at', '');
    if (!$lastNotify || strtotime($lastNotify) < strtotime('-12 hours')) {
        yai_push($pdo, 'seo',
            "🗺️ Layos: {$skipped} تطبيق مستبعد من Sitemap",
            "تم استبعاد {$skipped} تطبيق من sitemap.xml لأن نقاط جودتهم أقل من 50٪.\n" .
            "يؤدي وجودهم في الخريطة إلى تدهور سمعة الموقع في Google.\n" .
            "افتح لوحة Layos Security لإصلاحها تلقائياً.",
            'warning', url('admin.php?page=layos'), false, ''
        );
        set_cfg($pdo, 'layos_sitemap_notify_at', date('Y-m-d H:i:s'));
    }
}

// ── التصنيفات التي فيها تطبيقات مقبولة ──
$cats = $pdo->query(
    "SELECT DISTINCT c.slug
     FROM categories c
     INNER JOIN apps a ON a.category_slug = c.slug AND a.status='published'
     WHERE c.slug IS NOT NULL AND c.slug <> ''"
)->fetchAll();

// ── المطورون ──
$developers = $pdo->query(
    "SELECT DISTINCT developer FROM apps
     WHERE status='published' AND developer IS NOT NULL AND developer <> ''
     LIMIT 50"
)->fetchAll(PDO::FETCH_COLUMN);

// ── مقالات المدونة ──
$blogPosts = $pdo->query(
    "SELECT slug, updated_at FROM blog_posts
     WHERE status='published' ORDER BY updated_at DESC LIMIT 200"
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
