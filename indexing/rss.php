<?php
require_once __DIR__.'/config.php';
header('Content-Type: application/rss+xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$siteUrl = h(site_url());
$feedUrl = h(site_url('rss.php'));

$items = [
    [
        'title' => 'IndexPro Now Supports OpenRouter AI Chat',
        'desc'  => 'The support chat widget now uses free AI models from OpenRouter to answer indexing questions instantly, before escalating to a human support ticket.',
        'link'  => $siteUrl,
        'date'  => 'Fri, 01 Aug 2025 09:00:00 +0000',
    ],
    [
        'title' => 'Failure Reason Explanations Added',
        'desc'  => 'After a batch submission, IndexPro now shows detailed explanations and step-by-step fixes for the most common Google Indexing API error codes.',
        'link'  => $siteUrl,
        'date'  => 'Sat, 15 Feb 2025 09:00:00 +0000',
    ],
    [
        'title' => 'Messenger-Style Chat Support Widget',
        'desc'  => 'A full messenger-style support panel is now available on every page — send messages or browse automated answers for common issues 24/7.',
        'link'  => $siteUrl,
        'date'  => 'Mon, 03 Feb 2025 09:00:00 +0000',
    ],
    [
        'title' => 'Bulk CSV URL Import',
        'desc'  => 'Paste raw CSV data, search-console exports, or any text containing URLs — IndexPro extracts and deduplicates them automatically.',
        'link'  => $siteUrl,
        'date'  => 'Mon, 20 Jan 2025 09:00:00 +0000',
    ],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title><?= h(SITE_NAME) ?> — Updates &amp; News</title>
    <link><?= $siteUrl ?></link>
    <description>Latest updates and feature releases from <?= h(SITE_NAME) ?>, the fastest way to submit URLs to Google's official Indexing API.</description>
    <language>en-us</language>
    <atom:link href="<?= $feedUrl ?>" rel="self" type="application/rss+xml"/>
    <image>
      <url><?= $siteUrl ?>favicon.ico</url>
      <title><?= h(SITE_NAME) ?></title>
      <link><?= $siteUrl ?></link>
    </image>
<?php foreach ($items as $item): ?>
    <item>
      <title><?= h($item['title']) ?></title>
      <description><?= h($item['desc']) ?></description>
      <link><?= $item['link'] ?></link>
      <pubDate><?= $item['date'] ?></pubDate>
      <guid isPermaLink="false"><?= $item['link'] ?>?rss=<?= md5($item['title']) ?></guid>
    </item>
<?php endforeach; ?>
  </channel>
</rss>
