<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/rss+xml; charset=utf-8');

$prods = db()->query("SELECT * FROM products WHERE active=1 ORDER BY created_at DESC LIMIT 50")->fetchAll();
$siteName = clean(setting('site_name_ar','DarkStore'));

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0">
  <channel>
    <title><?= $siteName ?> — <?= t('أحدث المنتجات','Latest Products') ?></title>
    <link><?= clean(siteUrl('index.php')) ?></link>
    <description><?= clean(t(setting('tagline_ar'), setting('tagline_en'))) ?></description>
    <language><?= getLang() ?></language>
    <atom:link xmlns:atom="http://www.w3.org/2005/Atom" href="<?= clean(siteUrl('rss.php')) ?>" rel="self" type="application/rss+xml"/>
    <?php foreach ($prods as $p): ?>
    <item>
      <title><?= clean(t($p['name_ar'], $p['name_en'])) ?></title>
      <link><?= clean(siteUrl('product.php?slug=' . rawurlencode($p['slug']))) ?></link>
      <guid><?= clean(siteUrl('product.php?slug=' . rawurlencode($p['slug']))) ?></guid>
      <description><?= clean(t($p['short_ar'], $p['short_en'])) ?></description>
      <pubDate><?= date(DATE_RSS, strtotime($p['created_at'])) ?></pubDate>
    </item>
    <?php endforeach; ?>
  </channel>
</rss>
