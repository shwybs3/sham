<?php
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
?>
User-agent: *
Allow: /
Disallow: /admin.php
Disallow: /install/
Disallow: /config.php
Disallow: /data/

Sitemap: <?= siteUrl('sitemap.php') ?>
