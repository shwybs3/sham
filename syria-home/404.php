<?php
if (!isset($pdo)) { require_once __DIR__ . '/config.php'; }
require_once __DIR__ . '/partials.php';
?><!doctype html><html lang="en"><head>
<?php seo_head(['title' => 'Page not found | ' . setting('site_name'), 'description' => 'This page does not exist.']); ?>
</head><body>
<?php site_header(); ?>
<div class="container empty-state" style="padding:100px 20px">
  <i class="fa-solid fa-compass" style="font-size:40px;color:var(--brand1)"></i>
  <h1 style="margin-top:16px">404 — Page not found</h1>
  <p>The page you're looking for doesn't exist or was moved.</p>
  <a class="btn" style="display:inline-block;margin-top:10px;background:linear-gradient(135deg,var(--brand1),var(--brand2));color:#fff;padding:12px 22px;border-radius:10px;font-weight:700" href="<?= site_url('') ?>">Back to homepage</a>
</div>
<?php site_footer(); ?>
</body></html>
