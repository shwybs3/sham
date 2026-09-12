<?php
require __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

function smap(string $loc, string $mod = '', string $freq = 'daily', string $pri = '0.6'): void {
    echo "  <url><loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>";
    if ($mod) echo "<lastmod>$mod</lastmod>";
    echo "<changefreq>$freq</changefreq><priority>$pri</priority></url>\n";
}

smap(url(''), '', 'hourly', '1.0');
foreach (['explore', 'trending', 'videos'] as $s) smap(url($s), '', 'hourly', '0.8');

foreach ($pdo->query("SELECT slug FROM categories ORDER BY sort_order") as $c) smap(category_url($c['slug']), '', 'daily', '0.7');

$posts = $pdo->query("SELECT slug, updated_at FROM posts WHERE status='published' AND visibility='public' ORDER BY updated_at DESC LIMIT 5000");
foreach ($posts as $p) smap(url('post/' . $p['slug']), date('c', strtotime($p['updated_at'])), 'weekly', '0.8');

$users = $pdo->query("SELECT username FROM users WHERE banned=0 AND is_private=0 AND posts_count>0 LIMIT 5000");
foreach ($users as $u) smap(user_url($u['username']), '', 'weekly', '0.5');

$tags = $pdo->query("SELECT slug FROM hashtags WHERE posts_count>0 ORDER BY posts_count DESC LIMIT 2000");
foreach ($tags as $t) smap(tag_url($t['slug']), '', 'weekly', '0.4');

echo '</urlset>';
