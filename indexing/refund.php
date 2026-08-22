<?php require_once __DIR__.'/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Refund Policy — <?= h(SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<style>
:root{--bg:#f7f7f8;--surface:#ffffff;--border:#e6e7eb;--accent:#dc2626;--accent2:#b91c1c;--green:#16a34a;--text:#16181d;--text2:#4b5563;--text3:#6b7280;--radius:12px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);line-height:1.7}
a{color:var(--accent2);text-decoration:none}a:hover{text-decoration:underline}
.nav{background:rgba(255,255,255,.9);border-bottom:1px solid var(--border);padding:16px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;backdrop-filter:blur(12px)}
.nav-logo{font-size:20px;font-weight:800;display:flex;align-items:center;gap:8px}
.nav-logo .icon{width:32px;height:32px;background:linear-gradient(135deg,#6c63ff,#a78bfa);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px}
.nav-links{display:flex;gap:20px}.nav-links a{color:var(--text2);font-size:14px}
.hero{background:linear-gradient(135deg,rgba(16,185,129,.12) 0%,transparent 60%);border-bottom:1px solid var(--border);padding:60px 24px;text-align:center}
.hero h1{font-size:clamp(28px,5vw,44px);font-weight:800;background:linear-gradient(135deg,var(--text),#166534);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:12px}
.hero p{color:var(--text2);font-size:16px}
.container{max-width:800px;margin:0 auto;padding:48px 24px}
.section{margin-bottom:40px}
.section h2{font-size:20px;font-weight:700;color:var(--text);margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border)}
.section p,.section li{color:var(--text2);font-size:15px;margin-bottom:10px}
.section ul{padding-left:20px}.section li{margin-bottom:6px}
.policy-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin:20px 0}
.policy-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;text-align:center}
.policy-card .emoji{font-size:32px;margin-bottom:8px}
.policy-card h3{font-size:15px;font-weight:700;margin-bottom:6px}
.policy-card p{font-size:13px;color:var(--text2);margin:0}
.policy-card.eligible{border-color:rgba(16,185,129,.3);background:rgba(16,185,129,.05)}
.policy-card.not-eligible{border-color:rgba(239,68,68,.3);background:rgba(239,68,68,.05)}
.process-steps{display:flex;flex-direction:column;gap:14px;margin:16px 0}
.step{display:flex;align-items:flex-start;gap:14px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px}
.step-num{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0}
.step-text h4{font-size:14px;font-weight:600;margin-bottom:4px}
.step-text p{font-size:13px;color:var(--text2);margin:0}
.info-box{background:var(--surface);border:1px solid var(--border);border-left:4px solid var(--green);border-radius:var(--radius);padding:18px 20px;margin:16px 0;font-size:14px;color:var(--text2)}
.updated{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px 18px;font-size:13px;color:var(--text3);text-align:center;margin-bottom:32px}
footer{background:var(--surface);border-top:1px solid var(--border);padding:32px 24px;text-align:center;color:var(--text3);font-size:13px}
footer a{color:var(--text3);margin:0 10px}footer a:hover{color:var(--accent2)}
</style>
</head>
<body>
<nav class="nav">
  <a href="index.php" class="nav-logo"><div class="icon"><?= icon('bolt',15) ?></div><?= h(SITE_NAME) ?></a>
  <div class="nav-links"><a href="index.php">Home</a><a href="privacy.php">Privacy</a><a href="terms.php">Terms</a><a href="about.php">About</a></div>
</nav>
<div class="hero">
  <h1><?= icon('checkCircle',30) ?> Refund Policy</h1>
  <p>Our fair and transparent refund policy</p>
</div>
<div class="container">
  <div class="updated">Last updated: <?= date('F d, Y') ?></div>

  <div class="section">
    <h2>Our Commitment</h2>
    <p>We want you to be completely satisfied with <?= h(SITE_NAME) ?>. If our service doesn't meet your expectations, we're here to help. Please read our refund policy carefully before making a purchase.</p>
    <div class="info-box">
      <?= icon('checkCircle',15) ?> <strong>7-Day Satisfaction Guarantee</strong> — If you're not satisfied within the first 7 days of your subscription, contact us for a full refund.
    </div>
  </div>

  <div class="section">
    <h2>Eligible vs. Non-Eligible Refunds</h2>
    <div class="policy-grid">
      <div class="policy-card eligible">
        <div class="emoji"><?= icon('checkCircle',26) ?></div>
        <h3>Eligible for Refund</h3>
        <p>Request within 7 days of purchase, service was non-functional, billing error occurred</p>
      </div>
      <div class="policy-card not-eligible">
        <div class="emoji"><?= icon('xCircle',26) ?></div>
        <h3>Not Eligible</h3>
        <p>After 7 days, quota was used, Terms of Service violation, crypto exchange rate changes</p>
      </div>
    </div>
  </div>

  <div class="section">
    <h2>Specific Refund Conditions</h2>
    <ul>
      <li><strong>Full refund eligible:</strong> Technical failure on our end preventing service use within 7 days</li>
      <li><strong>Full refund eligible:</strong> Accidental duplicate payment</li>
      <li><strong>Full refund eligible:</strong> Billing error (wrong plan charged)</li>
      <li><strong>Partial refund considered:</strong> Service disruption lasting more than 48 hours</li>
      <li><strong>No refund:</strong> Google API rejection (not our fault — we submit correctly)</li>
      <li><strong>No refund:</strong> Change of mind after using the service</li>
      <li><strong>No refund:</strong> Account suspended for Terms of Service violations</li>
      <li><strong>No refund:</strong> Cryptocurrency payment losses due to market volatility</li>
    </ul>
  </div>

  <div class="section">
    <h2>How to Request a Refund</h2>
    <div class="process-steps">
      <div class="step">
        <div class="step-num">1</div>
        <div class="step-text">
          <h4>Contact Support</h4>
          <p>Email us at the address on our <a href="about.php">About page</a> within 7 days of purchase</p>
        </div>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <div class="step-text">
          <h4>Provide Details</h4>
          <p>Include your Order ID, email address, payment date, and reason for refund</p>
        </div>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <div class="step-text">
          <h4>We Review</h4>
          <p>We'll review your request within 2–3 business days and respond via email</p>
        </div>
      </div>
      <div class="step">
        <div class="step-num">4</div>
        <div class="step-text">
          <h4>Refund Processed</h4>
          <p>Approved refunds are processed to the original cryptocurrency address within 5–7 business days</p>
        </div>
      </div>
    </div>
  </div>

  <div class="section">
    <h2>Cryptocurrency Refund Notes</h2>
    <p>Since payments are made in cryptocurrency via NOWPayments:</p>
    <ul>
      <li>Refunds are issued in the <strong>same cryptocurrency</strong> used for payment</li>
      <li>Refund amount is based on the <strong>USD value at time of payment</strong> (not current exchange rate)</li>
      <li>Network fees (gas/transaction fees) are non-refundable</li>
      <li>You must provide your wallet address for the refund</li>
    </ul>
  </div>

  <div class="section">
    <h2>Plan Cancellation</h2>
    <p>Subscription plans are monthly (non-auto-renewing). When your plan expires:</p>
    <ul>
      <li>Your account automatically reverts to the Free plan (100 URLs/day)</li>
      <li>No cancellation is needed — simply don't renew</li>
      <li>Your data and account remain intact</li>
    </ul>
  </div>

  <div class="section">
    <h2>Questions?</h2>
    <p>Contact our support team via the <a href="about.php">About Us</a> page. We typically respond within 24 hours.</p>
  </div>
</div>
<footer>
  <p style="margin-bottom:12px">© <?= date('Y') ?> <?= h(SITE_NAME) ?> — All rights reserved</p>
  <a href="index.php">Home</a><a href="privacy.php">Privacy</a><a href="terms.php">Terms</a><a href="refund.php">Refund</a><a href="about.php">About</a>
</footer>
</body>
</html>
