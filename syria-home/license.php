<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';
$siteName = setting('site_name', 'Syria Home');
?><!doctype html><html lang="en"><head>
<?php seo_head(['title' => 'License Terms | ' . $siteName, 'description' => 'What you can and can\'t do with digital products purchased from ' . $siteName . '.', 'canonical' => site_url('license.php')]); ?>
</head><body>
<?php site_header('Store'); ?>
<div class="page-hero container"><span class="eyebrow"><i class="fa-solid fa-file-contract"></i> Legal</span><h1>License Terms</h1><p class="lead">Last updated: <?= date('F Y') ?></p></div>
<div class="container article-body" style="padding-bottom:60px">
  <p>Unless a specific product page states otherwise, every digital product sold on <?= e($siteName) ?> is licensed — not sold as intellectual property — under the following single-project commercial license.</p>

  <h2>You may</h2>
  <ul>
    <li>Use the product on one live website or project per license purchased.</li>
    <li>Modify the source code freely for your own use.</li>
    <li>Use the product for a client project, as long as the license isn't resold or transferred to the client as a standalone asset.</li>
  </ul>

  <h2>You may not</h2>
  <ul>
    <li>Resell, redistribute, or repackage the source code itself as a competing product or template.</li>
    <li>Share your copy publicly (public code repositories, forums, marketplaces) in a way that lets others download it for free.</li>
    <li>Claim authorship of the original code.</li>
    <li>Use the product for anything illegal or that violates the terms of a third-party service it integrates with (for example, Google's API and AdSense program policies).</li>
  </ul>

  <h2>Multiple projects</h2>
  <p>Need to use the same product on more than one live site? Contact us for a multi-site license — most products can be licensed for additional projects at a reduced add-on price.</p>

  <h2>No warranty beyond our refund policy</h2>
  <p>Products are provided "as is." Our only obligations are the support, updates and refund window described on each product page and in our <a href="<?= site_url('refund-policy.php') ?>">Refund Policy</a> — see that page for what is and isn't covered, including a note on third-party approvals like Google AdSense.</p>

  <h2>Third-party services</h2>
  <p>Some products integrate with third-party APIs and services (for example, Google AdSense, Search Console, Analytics, Ads, or AI providers). You are responsible for complying with those services' own terms and for any costs or approvals they require — those relationships are between you and the third party, not us.</p>
</div>
<?php site_footer(); ?>
</body></html>
