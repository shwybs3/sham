<?php require_once __DIR__.'/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Terms of Service — <?= h(SITE_NAME) ?></title>
<meta name="description" content="Terms of Service for <?= h(SITE_NAME) ?> — rules and conditions for using our Google Indexing API tool.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<style>
:root{--bg:#f7f7f8;--surface:#ffffff;--border:#e6e7eb;--accent:#dc2626;--accent2:#b91c1c;--text:#16181d;--text2:#4b5563;--text3:#6b7280;--radius:12px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);line-height:1.7}
a{color:var(--accent2);text-decoration:none}a:hover{text-decoration:underline}
.nav{background:rgba(255,255,255,.9);border-bottom:1px solid var(--border);padding:16px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;backdrop-filter:blur(12px)}
.nav-logo{font-size:20px;font-weight:800;display:flex;align-items:center;gap:8px}
.nav-logo .icon{width:32px;height:32px;background:linear-gradient(135deg,#6c63ff,#a78bfa);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px}
.nav-links{display:flex;gap:20px}
.nav-links a{color:var(--text2);font-size:14px}
.hero{background:linear-gradient(135deg,rgba(220,38,38,.15) 0%,transparent 60%);border-bottom:1px solid var(--border);padding:60px 24px;text-align:center}
.hero h1{font-size:clamp(28px,5vw,44px);font-weight:800;background:linear-gradient(135deg,var(--text),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:12px}
.hero p{color:var(--text2);font-size:16px}
.container{max-width:800px;margin:0 auto;padding:48px 24px}
.section{margin-bottom:40px}
.section h2{font-size:20px;font-weight:700;color:var(--text);margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px}
.section p,.section li{color:var(--text2);font-size:15px;margin-bottom:10px}
.section ul{padding-left:20px}
.section li{margin-bottom:6px}
.info-box{background:var(--surface);border:1px solid var(--border);border-left:4px solid var(--accent);border-radius:var(--radius);padding:18px 20px;margin:16px 0;font-size:14px;color:var(--text2)}
.warn-box{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.3);border-left:4px solid #f59e0b;border-radius:var(--radius);padding:18px 20px;margin:16px 0;font-size:14px;color:var(--text2)}
.updated{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px 18px;font-size:13px;color:var(--text3);text-align:center;margin-bottom:32px}
footer{background:var(--surface);border-top:1px solid var(--border);padding:32px 24px;text-align:center;color:var(--text3);font-size:13px}
footer a{color:var(--text3);margin:0 10px}footer a:hover{color:var(--accent2)}
</style>
</head>
<body>
<nav class="nav">
  <a href="index.php" class="nav-logo"><div class="icon"><?= icon('bolt',15) ?></div><?= h(SITE_NAME) ?></a>
  <div class="nav-links">
    <a href="index.php">Home</a>
    <a href="privacy.php">Privacy</a>
    <a href="refund.php">Refund</a>
    <a href="about.php">About</a>
  </div>
</nav>
<div class="hero">
  <h1><?= icon('clipboard',30) ?> Terms of Service</h1>
  <p>Please read these terms carefully before using <?= h(SITE_NAME) ?></p>
</div>
<div class="container">
  <div class="updated">Last updated: <?= date('F d, Y') ?> &nbsp;|&nbsp; Governing Law: International</div>

  <div class="section">
    <h2>1. Acceptance of Terms</h2>
    <p>By accessing or using <?= h(SITE_NAME) ?> ("the Service"), you agree to be bound by these Terms of Service. If you do not agree to all terms, you may not use the Service.</p>
    <p>We reserve the right to update these terms at any time. Continued use after changes constitutes acceptance.</p>
  </div>

  <div class="section">
    <h2>2. Description of Service</h2>
    <p><?= h(SITE_NAME) ?> is a web tool that helps webmasters submit URLs to Google's Indexing API using their own Google Search Console Service Account credentials. The Service:</p>
    <ul>
      <li>Accepts and securely stores your Google Service Account JSON key (for up to 30 days)</li>
      <li>Submits your URLs to Google's Indexing API on your behalf</li>
      <li>Provides sitemap parsing, URL management, and submission history</li>
      <li>Offers subscription plans with higher daily submission limits</li>
    </ul>
  </div>

  <div class="section">
    <h2>3. User Accounts</h2>
    <ul>
      <li>You must be at least 16 years old to use this Service</li>
      <li>You are responsible for maintaining the security of your account credentials</li>
      <li>You must provide accurate information when creating an account</li>
      <li>One person may not operate multiple accounts to circumvent quotas</li>
      <li>We reserve the right to suspend or terminate accounts that violate these terms</li>
    </ul>
  </div>

  <div class="section">
    <h2>4. Acceptable Use</h2>
    <p>You agree to use the Service only for lawful purposes. You may NOT:</p>
    <ul>
      <li>Submit URLs for spam websites, phishing pages, or malicious content</li>
      <li>Attempt to circumvent daily submission quotas</li>
      <li>Share your account credentials with others</li>
      <li>Use automated scripts to abuse the Service beyond your plan limits</li>
      <li>Reverse-engineer, decompile, or attempt to extract source code</li>
      <li>Use the Service to violate Google's Terms of Service</li>
      <li>Submit URLs for content that is illegal in your jurisdiction</li>
    </ul>
    <div class="warn-box"><?= icon('warning',15) ?> Violation of these terms may result in immediate account termination without refund.</div>
  </div>

  <div class="section">
    <h2>5. Google Service Account &amp; API Usage</h2>
    <ul>
      <li>You are solely responsible for your Google Service Account and its permissions</li>
      <li>You must own or be authorized to manage the Search Console properties you submit</li>
      <li>We submit URLs on your behalf — all API calls count against Google's quotas</li>
      <li>Google may reject submissions for various reasons (quota exceeded, permission denied, etc.)</li>
      <li>We do not guarantee that submitted URLs will be indexed by Google</li>
    </ul>
  </div>

  <div class="section">
    <h2>6. Subscription Plans &amp; Payments</h2>
    <ul>
      <li>Plans are billed monthly and provide daily URL submission limits</li>
      <li>Payments are processed via NOWPayments (cryptocurrency)</li>
      <li>Plan access is activated automatically upon confirmed payment</li>
      <li>Unused daily quota does not roll over to the next day</li>
      <li>Plan limits reset daily at midnight UTC</li>
      <li>See our <a href="refund.php">Refund Policy</a> for details on cancellations</li>
    </ul>
  </div>

  <div class="section">
    <h2>7. Free Plan Limitations</h2>
    <p>The Free plan includes 100 URL submissions per day with the following restrictions:</p>
    <ul>
      <li>Maximum 100 URLs per day (resets at midnight UTC)</li>
      <li>No priority processing</li>
      <li>Service Account key stored for up to 30 days</li>
      <li>Standard support only</li>
    </ul>
  </div>

  <div class="section">
    <h2>8. Service Availability</h2>
    <ul>
      <li>We strive for 99% uptime but do not guarantee uninterrupted service</li>
      <li>Scheduled maintenance will be announced when possible</li>
      <li>We are not responsible for Google API outages or rate limiting</li>
      <li>Service features may change without prior notice</li>
    </ul>
  </div>

  <div class="section">
    <h2>9. Limitation of Liability</h2>
    <div class="info-box">
      THE SERVICE IS PROVIDED "AS IS" WITHOUT WARRANTY OF ANY KIND. WE ARE NOT LIABLE FOR:
    </div>
    <ul>
      <li>URLs not being indexed by Google despite successful submission</li>
      <li>Loss of data due to technical failures</li>
      <li>Indirect, consequential, or special damages arising from use</li>
      <li>Changes to Google's Indexing API policies or quotas</li>
      <li>Actions taken by Google as a result of your API usage</li>
    </ul>
  </div>

  <div class="section">
    <h2>10. Intellectual Property</h2>
    <p>The <?= h(SITE_NAME) ?> platform, its design, code, and branding are our intellectual property. You may not copy, reproduce, or distribute any part of the Service without written permission.</p>
  </div>

  <div class="section">
    <h2>11. Termination</h2>
    <p>We may terminate or suspend your account immediately, without prior notice, for conduct that violates these Terms. Upon termination:</p>
    <ul>
      <li>Your access to the Service will be revoked immediately</li>
      <li>Your Service Account JSON key will be deleted</li>
      <li>No refunds will be issued for violations of these terms</li>
    </ul>
  </div>

  <div class="section">
    <h2>12. Contact</h2>
    <p>Questions about these Terms? Contact us at: <a href="about.php">our contact page</a></p>
  </div>
</div>
<footer>
  <p style="margin-bottom:12px">© <?= date('Y') ?> <?= h(SITE_NAME) ?> — All rights reserved</p>
  <a href="index.php">Home</a><a href="privacy.php">Privacy</a><a href="terms.php">Terms</a><a href="refund.php">Refund</a><a href="about.php">About</a>
</footer>
</body>
</html>
