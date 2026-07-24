<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/rss+xml; charset=utf-8');

$apps = $pdo->query("SELECT name,slug,short_description,meta_description,updated_at FROM apps
    WHERE status='published' ORDER BY updated_at DESC LIMIT 50")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0">
  <channel>
    <title>Apkzilo — آخر التحديثات</title>
    <link><?= h(url('')) ?></link>
    <description>أحدث تحديثات التطبيقات والألعاب على منصة Apkzilo</description>
    <language>ar</language>
    <atom:link xmlns:atom="http://www.w3.org/2005/Atom" href="<?= h(url('rss.php')) ?>" rel="self" type="application/rss+xml"/>
    <?php foreach ($apps as $a): ?>
    <item>
      <title><?= h($a['name']) ?></title>
      <link><?= h(app_url($a['slug'])) ?></link>
      <guid><?= h(app_url($a['slug'])) ?></guid>
      <description><?= h($a['meta_description'] ?: $a['short_description']) ?></description>
      <pubDate><?= date(DATE_RSS, strtotime($a['updated_at'])) ?></pubDate>
    </item>
    <?php endforeach; ?>
  </channel>
</rss>
