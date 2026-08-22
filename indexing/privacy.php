<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/storage.php';
$settings = ['admin_email' => cfg('admin_email', '')];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Privacy Policy — <?= h(SITE_NAME) ?></title>
<meta name="description" content="Privacy Policy for <?= h(SITE_NAME) ?> — how we collect, use, and protect your data.">
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
.info-box strong{color:var(--text)}
.data-table{width:100%;border-collapse:collapse;margin:16px 0;font-size:14px}
.data-table th{background:var(--surface);padding:12px 16px;text-align:left;font-weight:600;color:var(--text2);border-bottom:1px solid var(--border)}
.data-table td{padding:12px 16px;border-bottom:1px solid rgba(42,45,62,.5);color:var(--text2)}
.data-table tr:hover td{background:rgba(255,255,255,.02)}
.updated{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px 18px;font-size:13px;color:var(--text3);text-align:center;margin-bottom:32px}
footer{background:var(--surface);border-top:1px solid var(--border);padding:32px 24px;text-align:center;color:var(--text3);font-size:13px}
footer a{color:var(--text3);margin:0 10px}
footer a:hover{color:var(--accent2)}
</style>
</head>
<body>
<nav class="nav">
  <a href="index.php" class="nav-logo"><div class="icon"><?= icon('bolt',15) ?></div><?= h(SITE_NAME) ?></a>
  <div class="nav-links">
    <a href="index.php">Home</a>
    <a href="terms.php">Terms</a>
    <a href="refund.php">Refund</a>
    <a href="about.php">About</a>
  </div>
</nav>

<div class="hero">
  <h1><?= icon('lock',30) ?> Privacy Policy</h1>
  <p>How we collect, use, and protect your information</p>
</div>

<div class="container">
  <div class="updated">Last updated: <?= date('F d, Y') ?> &nbsp;|&nbsp; Effective immediately upon use</div>

  <div class="section">
    <h2><?= icon('clipboard',18) ?> Overview</h2>
    <p>Welcome to <?= h(SITE_NAME) ?>. We are committed to protecting your privacy and being transparent about how we handle your data. This policy explains what information we collect, why we collect it, and how we use it.</p>
    <p>By using our service, you agree to the collection and use of information in accordance with this policy.</p>
  </div>

  <div class="section">
    <h2><?= icon('chart',18) ?> Data We Collect</h2>
    <table class="data-table">
      <thead><tr><th>Data Type</th><th>Why We Collect It</th><th>Retention Period</th></tr></thead>
      <tbody>
        <tr><td><strong>IP Address</strong></td><td>Security, abuse prevention, geographic analytics</td><td>30 days</td></tr>
        <tr><td><strong>User Agent (Browser)</strong></td><td>Compatibility, fraud detection</td><td>30 days</td></tr>
        <tr><td><strong>Country</strong></td><td>Regional analytics, compliance</td><td>30 days</td></tr>
        <tr><td><strong>Email Address</strong></td><td>Account identification, login</td><td>Until account deletion</td></tr>
        <tr><td><strong>Google Account Info</strong></td><td>OAuth login (name, email, picture)</td><td>Until account deletion</td></tr>
        <tr><td><strong>Service Account JSON Key</strong></td><td>Google Indexing API authentication</td><td><strong>30 days maximum</strong>, then auto-deleted</td></tr>
        <tr><td><strong>Submission Logs</strong></td><td>Usage tracking, quota management</td><td>30 days rolling</td></tr>
        <tr><td><strong>Payment Records</strong></td><td>Billing, plan activation, dispute resolution</td><td>7 years (financial regulation)</td></tr>
      </tbody>
    </table>
  </div>

  <div class="section">
    <h2><?= icon('key',18) ?> Google Service Account JSON Key</h2>
    <div class="info-box">
      <strong>Important:</strong> Your Google Service Account JSON key is the most sensitive data we handle. Here's exactly how we treat it:
    </div>
    <ul>
      <li>The JSON key file is stored <strong>encrypted on our servers</strong> in a directory inaccessible via web browser</li>
      <li>It is used <strong>only</strong> to authenticate API requests to Google Indexing API on your behalf</li>
      <li>It is <strong>automatically deleted after 30 days</strong> — we never retain it longer</li>
      <li>Only you (logged in) and our automated systems can access it</li>
      <li>You can delete it at any time from your dashboard</li>
      <li>We never share, sell, or expose your key to third parties</li>
    </ul>
  </div>

  <div class="section">
    <h2><?= icon('info',18) ?> Cookies &amp; Sessions</h2>
    <p>We use minimal, essential cookies:</p>
    <ul>
      <li><strong>_gidx_id</strong> — A unique anonymous identifier stored for 2 years. Used to maintain your session without requiring login. Contains no personal information.</li>
      <li><strong>Session cookie</strong> — Stores your login state during your browser session. Expires when you close the browser.</li>
    </ul>
    <p>We do <strong>not</strong> use advertising cookies, cross-site tracking cookies, or analytics platforms like Google Analytics or Facebook Pixel.</p>
  </div>

  <div class="section">
    <h2><?= icon('creditCard',18) ?> Payments</h2>
    <p>Payments are processed through <strong>NOWPayments</strong> (cryptocurrency payment processor). We receive:</p>
    <ul>
      <li>Order ID and payment status from their IPN (webhook) notifications</li>
      <li>Cryptocurrency payment amounts and transaction data</li>
    </ul>
    <p>We do <strong>not</strong> collect or store credit card numbers, bank account details, or crypto wallet private keys. All payment security is handled by NOWPayments.</p>
  </div>

  <div class="section">
    <h2><?= icon('send',18) ?> Data Sharing</h2>
    <p>We do <strong>not</strong> sell, trade, or share your personal data with third parties, except:</p>
    <ul>
      <li><strong>Google APIs</strong> — Your Service Account key is used to call Google Indexing API directly</li>
      <li><strong>NOWPayments</strong> — Payment processor for subscription handling</li>
      <li><strong>Legal requirements</strong> — If required by law, court order, or government authority</li>
    </ul>
  </div>

  <div class="section">
    <h2><?= icon('shield',18) ?> Data Security</h2>
    <ul>
      <li>All data is stored in files protected by <code>.htaccess</code> rules blocking direct web access</li>
      <li>HTTPS encryption for all data in transit</li>
      <li>CSRF tokens on all state-changing operations</li>
      <li>Passwords stored as bcrypt hashes (never plain text)</li>
      <li>Service Account keys stored in non-public directories</li>
      <li>Automatic data expiry after 30 days</li>
    </ul>
  </div>

  <div class="section">
    <h2><?= icon('checkCircle',18) ?> Your Rights</h2>
    <p>You have the right to:</p>
    <ul>
      <li><strong>Access</strong> — Request a copy of data we hold about you</li>
      <li><strong>Delete</strong> — Request deletion of your account and all associated data</li>
      <li><strong>Correct</strong> — Update inaccurate information in your profile</li>
      <li><strong>Export</strong> — Download your submission history</li>
      <li><strong>Withdraw</strong> — Delete your Service Account key at any time</li>
    </ul>
    <p>To exercise these rights, contact us at: <a href="mailto:support@<?= strtolower(preg_replace('/[^a-zA-Z0-9.]/','',$_SERVER['HTTP_HOST']??'example.com')) ?>">support@<?= h($settings['admin_email'] ?? 'your-domain.com') ?></a></p>
  </div>

  <div class="section">
    <h2><?= icon('user',18) ?> Children's Privacy</h2>
    <p>Our service is not directed to individuals under 16 years of age. We do not knowingly collect personal information from children. If you believe a child has provided us with personal information, please contact us immediately.</p>
  </div>

  <div class="section">
    <h2><?= icon('refresh',18) ?> Changes to This Policy</h2>
    <p>We may update this Privacy Policy from time to time. We will notify registered users of significant changes via email. Continued use of the service after changes constitutes acceptance of the updated policy.</p>
  </div>

  <div class="section">
    <h2><?= icon('mail',18) ?> Contact Us</h2>
    <div class="info-box">
      <strong><?= h(SITE_NAME) ?></strong><br>
      For privacy inquiries, data deletion requests, or security concerns:<br>
      Website: <a href="<?= h(site_url()) ?>"><?= h(SITE_URL) ?></a>
    </div>
  </div>
</div>

<footer>
  <p style="margin-bottom:12px">© <?= date('Y') ?> <?= h(SITE_NAME) ?> — All rights reserved</p>
  <a href="index.php">Home</a>
  <a href="privacy.php">Privacy</a>
  <a href="terms.php">Terms</a>
  <a href="refund.php">Refund</a>
  <a href="about.php">About</a>
</footer>
</body>
</html>
