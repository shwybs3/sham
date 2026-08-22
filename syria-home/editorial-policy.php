<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';
$siteName = setting('site_name', 'Syria Home');
?><!doctype html><html lang="en"><head>
<?php seo_head(['title' => 'Editorial Policy | ' . $siteName, 'description' => 'How we research, write, and fact-check content at ' . $siteName . ', including our use of AI assistance.', 'canonical' => site_url('editorial-policy.php')]); ?>
</head><body>
<?php site_header(); ?>
<div class="page-hero container"><span class="eyebrow"><i class="fa-solid fa-feather"></i> Legal</span><h1>Editorial Policy</h1></div>
<div class="container article-body" style="padding-bottom:60px">
  <h2>Our standards</h2>
  <p>We aim to publish clear, practical, and accurate content. Every article is reviewed before publication, and we welcome corrections from readers.</p>

  <h2>Use of AI in our workflow</h2>
  <p>Parts of our content pipeline use AI language models to help draft articles and suggest edits. Every AI-assisted draft is created in a private admin workspace as a draft, then reviewed and edited by a member of our editorial team before it is published — AI-generated drafts are never published automatically or without human review.</p>

  <h2>Corrections</h2>
  <p>If you spot an inaccuracy, please <a href="<?= site_url('contact.php') ?>">let us know</a>. We correct factual errors promptly and note significant corrections where appropriate.</p>

  <h2>Independence</h2>
  <p>Our editorial judgments are not influenced by advertisers. Advertising on this site, including via Google AdSense, is served independently of our content decisions.</p>

  <h2>Affiliate &amp; sponsored content</h2>
  <p>If we ever publish sponsored content or affiliate links, we will clearly label them as such within the article.</p>
</div>
<?php site_footer(); ?>
</body></html>
