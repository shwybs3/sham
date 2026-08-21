<?php
/* ═══════════════════════════════════════════
   rss.php — تغذية RSS مدعومة بـ Layos Security
   المقالات: تُدرج فقط إذا تجاوز تقييم الجودة 80٪
   التطبيقات: تُدرج دائماً (تُصفَّى في sitemap)
   ═══════════════════════════════════════════ */
require_once __DIR__ . '/config.php';
header('Content-Type: application/rss+xml; charset=utf-8');
header('X-Robots-Tag: noindex');

// ── التطبيقات المنشورة (50 آخر) ──
$apps = $pdo->query(
    "SELECT id, name, slug, short_description, meta_description, icon_path, updated_at
     FROM apps WHERE status='published' ORDER BY updated_at DESC LIMIT 50"
)->fetchAll();

// ── مقالات app_articles — مع فلتر جودة Layos ≥ 80 ──
$rawArticles = $pdo->query(
    "SELECT ar.id, ar.title, ar.slug, ar.body, ar.meta_description, ar.seo_title,
            ar.created_at, a.icon_path, a.name AS app_name, a.slug AS app_slug
     FROM app_articles ar
     JOIN apps a ON ar.app_id = a.id
     WHERE a.status = 'published'
       AND ar.title IS NOT NULL AND ar.title <> ''
       AND ar.body IS NOT NULL AND ar.body <> ''
     ORDER BY ar.created_at DESC
     LIMIT 300"
)->fetchAll();

// فلترة بمحرك Layos
$qualityArticles = [];
$excluded        = 0;
foreach ($rawArticles as $art) {
    $result = layos_score_article($art);
    if (layos_should_index($pdo, 'article', (int)$art['id'], $result['can_index'])) {
        $art['_layos_score'] = $result['score'];
        $qualityArticles[]   = $art;
        if (count($qualityArticles) >= 100) break;
    } else {
        $excluded++;
    }
}

// إشعار YAI إذا نسبة المستبعد عالية (مرة كل 24 ساعة)
if ($excluded > 5) {
    $lastNotify = get_cfg($pdo, 'layos_rss_notify_at', '');
    if (!$lastNotify || strtotime($lastNotify) < strtotime('-24 hours')) {
        yai_push($pdo, 'seo',
            "🔍 Layos: {$excluded} مقال مستبعد من RSS لضعف الجودة",
            "تم استبعاد {$excluded} مقال من تغذية RSS لأن نقاط جودتهم أقل من 80٪.\n" .
            "اذهب إلى لوحة Layos Security لعرض التفاصيل وإصلاحها.",
            'warning', url('admin.php?page=layos'), false, ''
        );
        set_cfg($pdo, 'layos_rss_notify_at', date('Y-m-d H:i:s'));
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0"
  xmlns:atom="http://www.w3.org/2005/Atom"
  xmlns:media="http://search.yahoo.com/mrss/"
  xmlns:content="http://purl.org/rss/1.0/modules/content/">
  <channel>
    <title><?= h(SITE_NAME) ?> — أحدث التطبيقات والمقالات</title>
    <link><?= h(url('')) ?></link>
    <description>أحدث تحديثات التطبيقات والمقالات على منصة <?= h(SITE_NAME) ?></description>
    <language>ar</language>
    <lastBuildDate><?= date(DATE_RSS) ?></lastBuildDate>
    <atom:link href="<?= h(url('rss')) ?>" rel="self" type="application/rss+xml"/>
    <generator>Yassota + Layos Security</generator>

    <!-- التطبيقات -->
    <?php foreach ($apps as $a):
      $desc = $a['meta_description'] ?: $a['short_description'];
    ?>
    <item>
      <title><?= h($a['name']) ?> — تطبيق أندرويد</title>
      <link><?= h(app_url($a['slug'])) ?></link>
      <guid isPermaLink="true"><?= h(app_url($a['slug'])) ?></guid>
      <description><?= h($desc) ?></description>
      <pubDate><?= date(DATE_RSS, strtotime($a['updated_at'])) ?></pubDate>
      <?php if (!empty($a['icon_path'])): ?>
      <media:thumbnail url="<?= h(media_url($a['icon_path'])) ?>" width="128" height="128"/>
      <?php endif; ?>
    </item>
    <?php endforeach; ?>

    <!-- مقالات عالية الجودة (Layos ≥ 80٪) -->
    <?php foreach ($qualityArticles as $art):
      $artDesc = $art['meta_description'] ?: mb_substr(trim(strip_tags($art['body'])), 0, 160);
    ?>
    <item>
      <title><?= h($art['title']) ?></title>
      <link><?= h(article_url($art['slug'])) ?></link>
      <guid isPermaLink="true"><?= h(article_url($art['slug'])) ?></guid>
      <description><?= h($artDesc) ?></description>
      <pubDate><?= date(DATE_RSS, strtotime($art['created_at'])) ?></pubDate>
      <?php if (!empty($art['icon_path'])): ?>
      <media:thumbnail url="<?= h(media_url($art['icon_path'])) ?>" width="128" height="128"/>
      <?php endif; ?>
    </item>
    <?php endforeach; ?>

  </channel>
</rss>
