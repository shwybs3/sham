<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';
$siteName = setting('site_name', 'Syria Home');
?><!doctype html><html lang="en"><head>
<?php seo_head(['title' => 'Refund Policy | ' . $siteName, 'description' => 'Our refund window and guarantee for digital products sold on ' . $siteName . '.', 'canonical' => site_url('refund-policy.php')]); ?>
</head><body>
<?php site_header('Store'); ?>
<div class="page-hero container"><span class="eyebrow"><i class="fa-solid fa-rotate-left"></i> Legal</span><h1>Refund Policy</h1><p class="lead">Last updated: <?= date('F Y') ?></p></div>
<div class="container article-body" style="padding-bottom:60px">
  <div class="guarantee-box">
    <?= svg_guarantee_seal() ?>
    <div>
      <h3>14-day refund window</h3>
      <p>If a product you purchased from <?= e($siteName) ?> doesn't work as described on its product page, contact us within 14 days of purchase and we'll fix the issue or refund you in full.</p>
    </div>
  </div>

  <h2>What is covered</h2>
  <ul>
    <li>The product does not install or run as described, and we're unable to resolve it through support.</li>
    <li>The product is materially different from its description on the product page.</li>
    <li>You were charged in error or charged twice for the same order.</li>
  </ul>

  <h2>What is not covered</h2>
  <ul>
    <li>Change of mind after you've received working access to the files.</li>
    <li>Compatibility issues caused by custom modifications you made to the code.</li>
    <li>Third-party outcomes we don't control — most importantly, <strong>Google AdSense approval</strong>. A product labeled "AdSense-ready" is built to meet AdSense's technical and policy prerequisites; whether Google approves any specific site is Google's own manual decision based on your domain, traffic and content, and is never guaranteed by us or refundable on that basis alone.</li>
  </ul>

  <h2>How to request a refund</h2>
  <p>Email us through the <a href="<?= site_url('contact.php') ?>">contact page</a> with your order details and what went wrong. We aim to respond within 2 business days.</p>

  <h2>Updates</h2>
  <p>Free updates for the period listed on each product page are included in your purchase and don't count as a new sale — you won't be charged again to receive them.</p>
</div>
<?php site_footer(); ?>
</body></html>
