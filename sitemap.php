<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=utf-8');

$prods    = db()->query("SELECT slug, created_at, name_ar FROM products WHERE active=1 ORDER BY id DESC")->fetchAll();
$arts     = db()->query("SELECT slug, created_at FROM articles WHERE active=1 ORDER BY id DESC")->fetchAll();
$baseUrl  = rtrim(SITE_URL, '/');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
         xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

// الصفحة الرئيسية
echo "  <url><loc>{$baseUrl}/</loc><changefreq>daily</changefreq><priority>1.0</priority></url>\n";
echo "  <url><loc>{$baseUrl}/#products</loc><changefreq>daily</changefreq><priority>0.9</priority></url>\n";

// صفحات ثابتة
$staticPages = [
    ['about',          'monthly', '0.5'],
    ['contact',        'monthly', '0.4'],
    ['privacy-policy', 'monthly', '0.3'],
    ['terms',          'monthly', '0.3'],
    ['refund-policy',  'monthly', '0.3'],
    ['cookie-policy',  'monthly', '0.2'],
];
foreach ($staticPages as [$slug, $freq, $pri]) {
    echo "  <url><loc>{$baseUrl}/{$slug}</loc><changefreq>{$freq}</changefreq><priority>{$pri}</priority></url>\n";
}

// صفحات المنتجات — روابط نظيفة /p/{slug}
foreach ($prods as $p) {
    $lastmod = date('Y-m-d', strtotime($p['created_at'] ?? 'now'));
    $loc     = $baseUrl . '/p/' . rawurlencode($p['slug']);
    echo "  <url>\n";
    echo "    <loc>{$loc}</loc>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "    <changefreq>weekly</changefreq><priority>0.85</priority>\n";
    echo "  </url>\n";
}

// صفحة قائمة المقالات
echo "  <url><loc>{$baseUrl}/articles</loc><changefreq>daily</changefreq><priority>0.8</priority></url>\n";

// صفحات المقالات — روابط نظيفة /a/{slug}
foreach ($arts as $a) {
    $lastmod = date('Y-m-d', strtotime($a['created_at'] ?? 'now'));
    $loc     = $baseUrl . '/a/' . rawurlencode($a['slug']);
    echo "  <url>\n";
    echo "    <loc>{$loc}</loc>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "    <changefreq>monthly</changefreq><priority>0.7</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
