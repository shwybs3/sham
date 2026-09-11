<?php
require __DIR__ . '/config.php';
header('Content-Type: application/rss+xml; charset=utf-8');
$site = setting('site_name', 'YASSOTA');
$posts = $pdo->query("SELECT p.*, u.name author_name FROM posts p JOIN users u ON u.id=p.user_id
    WHERE p.status='published' AND p.visibility='public' ORDER BY p.created_at DESC LIMIT 40")->fetchAll();
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
<title><?= htmlspecialchars($site, ENT_XML1) ?></title>
<link><?= htmlspecialchars(url(''), ENT_XML1) ?></link>
<description><?= htmlspecialchars(setting('site_desc'), ENT_XML1) ?></description>
<language>ar</language>
<atom:link href="<?= htmlspecialchars(url('rss.xml'), ENT_XML1) ?>" rel="self" type="application/rss+xml"/>
<?php foreach ($posts as $p): $link = url('post/' . $p['slug']); ?>
<item>
<title><?= htmlspecialchars($p['title'], ENT_XML1) ?></title>
<link><?= htmlspecialchars($link, ENT_XML1) ?></link>
<guid isPermaLink="true"><?= htmlspecialchars($link, ENT_XML1) ?></guid>
<pubDate><?= date(DATE_RSS, strtotime($p['created_at'])) ?></pubDate>
<author><?= htmlspecialchars($p['author_name'], ENT_XML1) ?></author>
<description><?= htmlspecialchars(mb_substr(strip_tags($p['description']), 0, 300), ENT_XML1) ?></description>
</item>
<?php endforeach; ?>
</channel>
</rss>
