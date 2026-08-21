<?php
require_once __DIR__ . '/config.php';

$allowRobots   = ($settings['robots_allow_public']        ?? '0') === '1';
$inclCustomers = ($settings['sitemap_include_customers']  ?? '0') === '1';

// أرسل كـ XML
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$siteUrl = rtrim($settings['site_url'] ?? (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\')
), '/');

$pages = [
    ['loc' => $siteUrl . '/index.php',          'priority' => '1.0',  'changefreq' => 'daily'],
    ['loc' => $siteUrl . '/community.php',       'priority' => '0.8',  'changefreq' => 'hourly'],
    ['loc' => $siteUrl . '/privacy-policy.php',  'priority' => '0.3',  'changefreq' => 'monthly'],
    ['loc' => $siteUrl . '/terms.php',           'priority' => '0.3',  'changefreq' => 'monthly'],
];

if ($inclCustomers) {
    $customers = $pdo->query("SELECT id, name, created_at FROM customers ORDER BY id")->fetchAll();
    foreach ($customers as $c) {
        $pages[] = [
            'loc'        => $siteUrl . '/customer.php?id=' . (int)$c['id'],
            'priority'   => '0.6',
            'changefreq' => 'daily',
            'lastmod'    => date('Y-m-d', strtotime($c['created_at'])),
        ];
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as $p) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($p['loc']) . "</loc>\n";
    echo "    <changefreq>{$p['changefreq']}</changefreq>\n";
    echo "    <priority>{$p['priority']}</priority>\n";
    if (isset($p['lastmod'])) echo "    <lastmod>{$p['lastmod']}</lastmod>\n";
    echo "  </url>\n";
}
echo '</urlset>';
