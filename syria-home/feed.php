<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/rss+xml; charset=utf-8');

$lang = ($_GET['lang'] ?? '') === 'ar' ? 'ar' : 'en';

$stmt = $pdo->prepare("SELECT a.*, c.name AS category_name FROM articles a LEFT JOIN categories c ON c.id=a.category_id
    WHERE a.status='published' AND a.lang = ? ORDER BY a.published_at DESC LIMIT 30");
$stmt->execute([$lang]);
$articles = $stmt->fetchAll();

$siteName = setting('site_name', 'Syria Home');
$selfUrl = site_url('feed.php' . ($lang === 'ar' ? '?lang=ar' : ''));
$homeUrl = site_url($lang === 'ar' ? 'ar/' : '');

function rss_esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
<title><?= rss_esc($siteName) ?></title>
<link><?= rss_esc($homeUrl) ?></link>
<atom:link href="<?= rss_esc($selfUrl) ?>" rel="self" type="application/rss+xml" />
<description><?= rss_esc(setting('site_description', '')) ?></description>
<language><?= $lang === 'ar' ? 'ar' : 'en-us' ?></language>
<lastBuildDate><?= date('r') ?></lastBuildDate>
<?php foreach ($articles as $a):
    $url = ($a['lang'] === 'ar' ? site_url('ar/article/' . $a['slug']) : site_url('article/' . $a['slug']));
?>
<item>
<title><?= rss_esc($a['title']) ?></title>
<link><?= rss_esc($url) ?></link>
<guid isPermaLink="true"><?= rss_esc($url) ?></guid>
<pubDate><?= date('r', strtotime($a['published_at'])) ?></pubDate>
<?php if ($a['category_name']): ?><category><?= rss_esc($a['category_name']) ?></category><?php endif; ?>
<description><?= rss_esc($a['excerpt']) ?></description>
<content:encoded><![CDATA[<?= $a['excerpt'] ? '<p>' . $a['excerpt'] . '</p>' : '' ?>]]></content:encoded>
</item>
<?php endforeach; ?>
</channel>
</rss>
