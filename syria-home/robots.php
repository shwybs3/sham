<?php
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\n";
echo "Disallow: /admin/\n";
echo "Disallow: /install/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /checkout.php\n";
echo "Disallow: /payment-status.php\n";
echo "Disallow: /payment-webhook.php\n";
echo "Allow: /\n\n";
echo 'Sitemap: ' . site_url('sitemap.php') . "\n";
