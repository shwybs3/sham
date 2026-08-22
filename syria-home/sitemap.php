<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=utf-8');

$urls = [
    ['loc' => site_url(''), 'priority' => '1.0'],
    ['loc' => site_url('articles.php'), 'priority' => '0.9'],
    ['loc' => site_url('tools.php'), 'priority' => '0.9'],
    ['loc' => site_url('about.php'), 'priority' => '0.4'],
    ['loc' => site_url('contact.php'), 'priority' => '0.3'],
];

foreach ($pdo->query("SELECT slug, updated_at FROM articles WHERE status='published'") as $a) {
    $urls[] = ['loc' => site_url('article.php?slug=' . $a['slug']), 'lastmod' => date('c', strtotime($a['updated_at'])), 'priority' => '0.8'];
}
foreach ($pdo->query("SELECT slug FROM tools WHERE status='published'") as $t) {
    $urls[] = ['loc' => site_url('tool.php?slug=' . $t['slug']), 'priority' => '0.7'];
}
foreach ($pdo->query("SELECT slug FROM categories") as $c) {
    $urls[] = ['loc' => site_url('category.php?slug=' . $c['slug']), 'priority' => '0.5'];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo '<url><loc>' . htmlspecialchars($u['loc']) . '</loc>';
    if (!empty($u['lastmod'])) echo '<lastmod>' . $u['lastmod'] . '</lastmod>';
    echo '<priority>' . $u['priority'] . '</priority></url>' . "\n";
}
echo '</urlset>';
