<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';
$siteName = setting('site_name', 'Syria Home');
?><!doctype html><html lang="en"><head>
<?php seo_head(['title' => 'Terms of Use | ' . $siteName, 'description' => 'The terms that govern your use of ' . $siteName . '.', 'canonical' => site_url('terms.php')]); ?>
</head><body>
<?php site_header(); ?>
<div class="page-hero container"><span class="eyebrow"><i class="fa-solid fa-gavel"></i> Legal</span><h1>Terms of Use</h1><p class="lead">Last updated: <?= date('F Y') ?></p></div>
<div class="container article-body" style="padding-bottom:60px">
  <h2>Acceptance of terms</h2>
  <p>By accessing <?= e($siteName) ?>, you agree to these Terms of Use. If you don't agree, please don't use the site.</p>

  <h2>Use of content</h2>
  <p>Articles, images and other content on this site are provided for informational purposes. You may share links to our content; republishing full articles elsewhere requires our written permission.</p>

  <h2>Free tools — use at your own risk</h2>
  <p>Our web tools are provided "as is," free of charge, with no warranty of any kind. They're intended for general convenience use; always keep independent backups of anything important and verify critical output yourself before relying on it.</p>

  <h2>No professional advice</h2>
  <p>Content on this site (including calculators such as our BMI or age calculator) is for general informational purposes only and is not medical, financial, legal, or other professional advice. Consult a qualified professional for decisions that matter.</p>

  <h2>Third-party links</h2>
  <p>We may link to third-party websites. We don't control and aren't responsible for their content or practices.</p>

  <h2>Limitation of liability</h2>
  <p>To the fullest extent permitted by law, <?= e($siteName) ?> is not liable for any indirect, incidental, or consequential damages arising from your use of the site or its tools.</p>

  <h2>Changes</h2>
  <p>We may update these terms at any time; continued use of the site after a change constitutes acceptance of the updated terms.</p>

  <h2>Contact</h2>
  <p>Questions about these terms? <a href="<?= site_url('contact.php') ?>">Contact us</a>.</p>
</div>
<?php site_footer(); ?>
</body></html>
