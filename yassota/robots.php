<?php
require __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\n";
echo "Allow: /\n";
foreach (['/admin','/settings','/messages','/notifications','/api','/login','/register','/logout','/auth/','/search','/uploads/tmp/'] as $d) echo "Disallow: $d\n";
echo "\nSitemap: " . url('sitemap.xml') . "\n";
