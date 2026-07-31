<?php
header('Content-Type: application/xml; charset=utf-8');
require_once __DIR__ . '/_base.php';
$base = rtrim(TOOLS_BASE_URL, '/');
$tools = [
    'age-calc','ascii','aspect-ratio','barcode','base-conv','bmi','calorie',
    'chat','color-names','color-picker','colors','compress','currency','diff',
    'dns','encode','gold-calc','gradient','hashtag','html-minifier',
    'img-convert','img-crop','invoice','ip','json-fmt','loan','lorem',
    'markdown','ocr','pass','password','pdf-compress','pdf-merge','pdf-split',
    'percentage','qr','qr-code','reading-time','regex','resize','slug','syp',
    'timer','timezone','unit','uuid','whatsapp','whois','words','write',
];
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
echo '  <url><loc>' . htmlspecialchars($base) . '/tools/</loc><changefreq>weekly</changefreq><priority>0.9</priority></url>' . "\n";
foreach ($tools as $t) {
    echo '  <url><loc>' . htmlspecialchars($base) . '/tools/' . $t . '/</loc><changefreq>monthly</changefreq><priority>0.8</priority></url>' . "\n";
}
echo '</urlset>';
