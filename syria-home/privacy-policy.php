<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';
$siteName = setting('site_name', 'Syria Home');
?><!doctype html><html lang="en"><head>
<?php seo_head(['title' => 'Privacy Policy | ' . $siteName, 'description' => 'How ' . $siteName . ' collects, uses and protects your information.', 'canonical' => site_url('privacy-policy.php')]); ?>
</head><body>
<?php site_header(); ?>
<div class="page-hero container"><span class="eyebrow"><i class="fa-solid fa-user-shield"></i> Legal</span><h1>Privacy Policy</h1><p class="lead">Last updated: <?= date('F Y') ?></p></div>
<div class="container article-body" style="padding-bottom:60px">
  <h2>Overview</h2>
  <p>This Privacy Policy explains what information <?= e($siteName) ?> ("we", "us") collects when you visit this website, how we use it, and the choices you have. By using this site, you agree to the practices described here.</p>

  <h2>Information we collect</h2>
  <ul>
    <li><strong>Usage data</strong> — pages viewed, time on site, referring pages, device and browser type, collected through analytics tools such as Google Analytics.</li>
    <li><strong>Contact form data</strong> — your name, email address, and message, if you choose to contact us.</li>
    <li><strong>Cookies</strong> — small files stored in your browser to support core site functionality, remember preferences, and support advertising and analytics (see below).</li>
  </ul>
  <p>Our free web tools (image converters, generators, calculators, etc.) run entirely in your browser. Files and text you process with them are not uploaded to our servers unless a tool explicitly says otherwise.</p>

  <h2>Advertising &amp; third-party services</h2>
  <p>We use or intend to use the following third-party services, each with its own privacy practices:</p>
  <ul>
    <li><strong>Google AdSense</strong> — may show personalized or non-personalized ads and use cookies to do so. You can review and adjust ad personalization at <a href="https://adssettings.google.com" target="_blank" rel="noopener">adssettings.google.com</a>.</li>
    <li><strong>Google Analytics</strong> — helps us understand how visitors use the site in aggregate.</li>
    <li><strong>Google Search Console</strong> — helps us understand search performance; it does not collect personal data from visitors directly.</li>
  </ul>
  <p>Third-party vendors, including Google, use cookies to serve ads based on a user's prior visits to this and other websites. Google's use of advertising cookies enables it and its partners to serve ads based on your visit to this site and/or other sites on the Internet. You may opt out of personalized advertising by visiting Google's Ads Settings.</p>

  <h2>Your choices</h2>
  <ul>
    <li>Most browsers let you block or delete cookies through their settings.</li>
    <li>You can opt out of personalized Google ads at the link above.</li>
    <li>You can request that we delete any contact-form message you've sent by emailing us.</li>
  </ul>

  <h2>Children's privacy</h2>
  <p>This site is not directed at children under 13, and we do not knowingly collect personal information from children.</p>

  <h2>Data retention &amp; security</h2>
  <p>We retain contact-form submissions only as long as needed to respond to and resolve your inquiry, and we use reasonable technical measures to protect data we hold. No method of transmission or storage is 100% secure.</p>

  <h2>Changes to this policy</h2>
  <p>We may update this policy from time to time. Material changes will be reflected by updating the "Last updated" date above.</p>

  <h2>Contact</h2>
  <p>Questions about this policy? <a href="<?= site_url('contact.php') ?>">Contact us</a>.</p>
</div>
<?php site_footer(); ?>
</body></html>
