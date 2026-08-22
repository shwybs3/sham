<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';
$siteName = setting('site_name', 'Syria Home');
?><!doctype html><html lang="en"><head>
<?php seo_head(['title' => 'Cookie Policy | ' . $siteName, 'description' => 'What cookies ' . $siteName . ' uses and how to control them.', 'canonical' => site_url('cookie-policy.php')]); ?>
</head><body>
<?php site_header(); ?>
<div class="page-hero container"><span class="eyebrow"><i class="fa-solid fa-cookie-bite"></i> Legal</span><h1>Cookie Policy</h1><p class="lead">Last updated: <?= date('F Y') ?></p></div>
<div class="container article-body" style="padding-bottom:60px">
  <p>This page explains what cookies and similar technologies <?= e($siteName) ?> uses, why, and how you can control them. It complements our <a href="<?= site_url('privacy-policy.php') ?>">Privacy Policy</a>.</p>

  <h2>What are cookies?</h2>
  <p>Cookies are small text files a website stores in your browser to remember information between visits — like a preference, a login session, or an anonymous visitor ID.</p>

  <h2>Cookies we use</h2>
  <table>
    <tr><th>Type</th><th>Purpose</th><th>Duration</th></tr>
    <tr><td><strong>Essential</strong></td><td>Admin login sessions, form security (CSRF protection)</td><td>Session / short-term</td></tr>
    <tr><td><strong>Preference</strong></td><td>Remembering that you've dismissed the cookie notice</td><td>Persistent (browser storage)</td></tr>
    <tr><td><strong>Analytics</strong></td><td>Google Analytics — understanding aggregate traffic and usage patterns</td><td>Up to 2 years</td></tr>
    <tr><td><strong>Advertising</strong></td><td>Google AdSense — ad delivery and, where applicable, personalization (only once AdSense is approved and active)</td><td>Varies by provider</td></tr>
  </table>

  <h2>How to control cookies</h2>
  <ul>
    <li><strong>Browser settings</strong> — every major browser lets you block or delete cookies; check your browser's privacy settings.</li>
    <li><strong>Google Ad Settings</strong> — opt out of personalized advertising at <a href="https://adssettings.google.com" target="_blank" rel="noopener">adssettings.google.com</a>.</li>
    <li><strong>Our cookie notice</strong> — dismissing the banner on this site only remembers that choice locally in your browser; it doesn't block third-party cookies on its own — use the options above for that.</li>
  </ul>

  <h2>Third-party cookies</h2>
  <p>Some cookies are set by third-party services we use (Google Analytics, Google AdSense) rather than directly by us. Those providers' own privacy and cookie policies govern how they use that data.</p>

  <h2>Changes</h2>
  <p>We may update this policy as our use of cookies changes. Check back periodically for updates.</p>

  <h2>Contact</h2>
  <p>Questions? <a href="<?= site_url('contact.php') ?>">Contact us</a>.</p>
</div>
<?php site_footer(); ?>
</body></html>
