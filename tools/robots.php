<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/_base.php';
$base = rtrim(TOOLS_BASE_URL, '/');
echo "User-agent: *\n";
echo "Allow: /\n\n";
echo "Sitemap: " . $base . "/tools/sitemap.php\n";
