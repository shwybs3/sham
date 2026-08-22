<?php
require_once __DIR__.'/config.php';
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=86400');

$pages = [
    [''           , '1.0', 'weekly' ],
    ['about.php'  , '0.7', 'monthly'],
    ['privacy.php', '0.5', 'monthly'],
    ['terms.php'  , '0.5', 'monthly'],
    ['refund.php' , '0.5', 'monthly'],
    ['report.php' , '0.6', 'monthly'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as [$path, $priority, $freq]) {
    $loc = h(site_url($path));
    echo "  <url>\n";
    echo "    <loc>{$loc}</loc>\n";
    echo "    <changefreq>{$freq}</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
    echo "  </url>\n";
}
echo '</urlset>';
