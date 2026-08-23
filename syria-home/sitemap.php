<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=utf-8');

$urls = [
    ['loc' => site_url(''), 'priority' => '1.0'],
    ['loc' => site_url('ar/'), 'priority' => '1.0'],
    ['loc' => site_url('articles.php'), 'priority' => '0.9'],
    ['loc' => site_url('articles.php?lang=ar'), 'priority' => '0.9'],
    ['loc' => site_url('tools.php'), 'priority' => '0.9'],
    ['loc' => site_url('tools.php?lang=ar'), 'priority' => '0.9'],
    ['loc' => site_url('products.php'), 'priority' => '0.9'],
    ['loc' => site_url('about.php'), 'priority' => '0.4'],
    ['loc' => site_url('contact.php'), 'priority' => '0.3'],
    ['loc' => site_url('privacy-policy.php'), 'priority' => '0.2'],
    ['loc' => site_url('terms.php'), 'priority' => '0.2'],
    ['loc' => site_url('editorial-policy.php'), 'priority' => '0.2'],
    ['loc' => site_url('refund-policy.php'), 'priority' => '0.2'],
    ['loc' => site_url('license.php'), 'priority' => '0.2'],
    ['loc' => site_url('cookie-policy.php'), 'priority' => '0.2'],
];

foreach ($pdo->query("SELECT slug, updated_at, lang FROM articles WHERE status='published'") as $a) {
    $loc = $a['lang'] === 'ar' ? site_url('ar/article/' . $a['slug']) : site_url('article.php?slug=' . $a['slug']);
    $urls[] = ['loc' => $loc, 'lastmod' => date('c', strtotime($a['updated_at'])), 'priority' => '0.8'];
}
foreach ($pdo->query("SELECT slug, lang FROM tools WHERE status='published'") as $t) {
    $loc = $t['lang'] === 'ar' ? site_url('ar/tool/' . $t['slug']) : site_url('tool.php?slug=' . $t['slug']);
    $urls[] = ['loc' => $loc, 'priority' => '0.7'];
}
foreach ($pdo->query("SELECT slug FROM products WHERE status='published'") as $p) {
    $urls[] = ['loc' => site_url('product.php?slug=' . $p['slug']), 'priority' => '0.7'];
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
