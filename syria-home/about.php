<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';
$siteName = setting('site_name', 'Syria Home');
?><!doctype html><html lang="en"><head>
<?php seo_head(['title' => 'About Us | ' . $siteName, 'description' => 'What ' . $siteName . ' covers, how we work, and why we built our free tools.', 'canonical' => site_url('about.php')]); ?>
</head><body>
<?php site_header(); ?>
<div class="page-hero container">
  <span class="eyebrow"><i class="fa-solid fa-circle-info"></i> About</span>
  <h1>About <?= e($siteName) ?></h1>
</div>

<!-- Founder profile: an abstract avatar by design, not a stock/AI-generated photo
     passed off as real. Swap the svg_initials_avatar() call below for a real
     <img> tag once an actual photo of Saad is available. -->
<div class="container">
  <div class="founder-card">
    <div class="founder-avatar-wrap">
      <?= svg_initials_avatar('S') ?>
      <span class="founder-verified"><i class="fa-solid fa-check"></i></span>
    </div>
    <div class="founder-name">Saad <i class="fa-solid fa-circle-check founder-check" title="Verified founder"></i></div>
    <div class="founder-role">Founder &amp; Editor</div>
    <p class="founder-bio">Hi, I'm Saad — I built <?= e($siteName) ?> because I was tired of tech sites padded with fluff before you reach the actual answer. Everything here is written to be useful first: practical guides, honest comparisons, and free tools that just work without an account or a subscription. When I'm not writing, I'm usually improving the tools themselves based on what readers ask for.</p>
    <div class="founder-socials">
      <?php foreach (['social_twitter'=>'fa-x-twitter','social_facebook'=>'fa-facebook-f','social_linkedin'=>'fa-linkedin-in'] as $key=>$icon): $url = trim(setting($key)); if ($url === '') continue; ?>
        <a href="<?= e($url) ?>" target="_blank" rel="noopener"><i class="fa-brands <?= $icon ?>"></i></a>
      <?php endforeach; ?>
      <a href="mailto:<?= e(setting('contact_email', 'contact@yassota.com')) ?>"><i class="fa-solid fa-envelope"></i></a>
    </div>
  </div>
</div>

<div class="container article-body" style="padding-bottom:60px">
  <p><?= e($siteName) ?> is an independent publication covering technology news, hands-on comparisons, practical tutorials and reviews — plus a growing collection of free, browser-based web tools that need no sign-up and no installation.</p>
  <h2>What we cover</h2>
  <p>We write about the tools, platforms and trends that shape how people actually use technology day to day: AI assistants and automation, hardware and gadgets, cybersecurity and privacy, mobile devices, and the ongoing shifts in internet and social media culture.</p>
  <h2>Our approach</h2>
  <ul>
    <li>We aim for clear, practical explanations over hype — what something actually changes for you, not just that it exists.</li>
    <li>Our free tools run entirely in your browser wherever technically possible; we don't ask you to create an account to use them.</li>
    <li>We use AI assistance in parts of our editorial workflow, always with human review before anything is published. See our <a href="<?= site_url('editorial-policy.php') ?>">Editorial Policy</a> for details.</li>
  </ul>
  <h2>Get in touch</h2>
  <p>Questions, corrections, or a tool you'd like to see built? <a href="<?= site_url('contact.php') ?>">Contact us</a> — we read every message.</p>
</div>
<?php site_footer(); ?>
</body></html>
