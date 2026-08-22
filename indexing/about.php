<?php require_once __DIR__.'/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>About Us — <?= h(SITE_NAME) ?></title>
<meta name="description" content="Learn about <?= h(SITE_NAME) ?> — the fastest way to get your pages indexed by Google using the official Indexing API.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<style>
:root{--bg:#f7f7f8;--surface:#ffffff;--border:#e6e7eb;--accent:#dc2626;--accent2:#b91c1c;--green:#16a34a;--text:#16181d;--text2:#4b5563;--text3:#6b7280;--radius:12px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);line-height:1.7}
a{color:var(--accent2);text-decoration:none}a:hover{text-decoration:underline}
.nav{background:rgba(255,255,255,.9);border-bottom:1px solid var(--border);padding:16px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;backdrop-filter:blur(12px)}
.nav-logo{font-size:20px;font-weight:800;display:flex;align-items:center;gap:8px}
.nav-logo .icon{width:32px;height:32px;background:linear-gradient(135deg,#6c63ff,#a78bfa);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px}
.nav-links{display:flex;gap:20px}.nav-links a{color:var(--text2);font-size:14px}
.hero{background:radial-gradient(ellipse at 30% 50%,rgba(220,38,38,.2) 0%,transparent 60%),var(--bg);border-bottom:1px solid var(--border);padding:80px 24px;text-align:center}
.hero h1{font-size:clamp(32px,6vw,56px);font-weight:800;background:linear-gradient(135deg,var(--text),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:16px}
.hero p{color:var(--text2);font-size:18px;max-width:600px;margin:0 auto}
.container{max-width:900px;margin:0 auto;padding:60px 24px}
.section{margin-bottom:60px}
.section-title{font-size:28px;font-weight:800;margin-bottom:8px;text-align:center}
.section-sub{color:var(--text2);text-align:center;margin-bottom:40px;font-size:16px}
.feature-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px}
.feature-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:28px;transition:.2s}
.feature-card:hover{border-color:rgba(220,38,38,.4);transform:translateY(-2px)}
.feature-card .icon{font-size:36px;margin-bottom:14px}
.feature-card h3{font-size:17px;font-weight:700;margin-bottom:8px}
.feature-card p{color:var(--text2);font-size:14px;line-height:1.6;margin:0}
.values-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px}
.value-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:22px;text-align:center}
.value-card .icon{font-size:28px;margin-bottom:10px}
.value-card h3{font-size:15px;font-weight:700;margin-bottom:6px}
.value-card p{color:var(--text2);font-size:13px;margin:0}
.contact-box{background:linear-gradient(135deg,rgba(220,38,38,.15),rgba(185,28,28,.08));border:1px solid rgba(220,38,38,.3);border-radius:16px;padding:48px;text-align:center}
.contact-box h2{font-size:28px;font-weight:800;margin-bottom:12px}
.contact-box p{color:var(--text2);margin-bottom:28px;font-size:16px}
.contact-methods{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
.contact-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;transition:.2s}
.contact-btn.primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}
.contact-btn.primary:hover{opacity:.9;transform:translateY(-1px)}
.contact-btn.secondary{background:var(--surface);border:1px solid var(--border);color:var(--text2)}
.contact-btn.secondary:hover{border-color:var(--accent2);color:var(--text)}
.stats-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:20px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:32px;margin-bottom:60px}
.stat{text-align:center}
.stat .num{font-size:36px;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.stat .label{font-size:13px;color:var(--text2);margin-top:4px}
footer{background:var(--surface);border-top:1px solid var(--border);padding:32px 24px;text-align:center;color:var(--text3);font-size:13px}
footer a{color:var(--text3);margin:0 10px}footer a:hover{color:var(--accent2)}
</style>
</head>
<body>
<nav class="nav">
  <a href="index.php" class="nav-logo"><div class="icon"><?= icon('bolt',15) ?></div><?= h(SITE_NAME) ?></a>
  <div class="nav-links"><a href="index.php">Home</a><a href="privacy.php">Privacy</a><a href="terms.php">Terms</a><a href="refund.php">Refund</a></div>
</nav>

<div class="hero">
  <h1>About <?= h(SITE_NAME) ?></h1>
  <p>The fastest, most reliable way to get your pages indexed by Google using the official Search Console API</p>
</div>

<div class="container">

  <div class="stats-bar">
    <div class="stat"><div class="num">100</div><div class="label">Free daily submissions</div></div>
    <div class="stat"><div class="num">10K</div><div class="label">Max daily on Business plan</div></div>
    <div class="stat"><div class="num">30s</div><div class="label">Average time to submit</div></div>
    <div class="stat"><div class="num">0</div><div class="label">Databases required</div></div>
  </div>

  <div class="section">
    <div class="section-title">Our Mission</div>
    <div class="section-sub">Helping webmasters get indexed faster</div>
    <p style="color:var(--text2);font-size:16px;max-width:680px;margin:0 auto;text-align:center;line-height:1.8">
      <?= h(SITE_NAME) ?> was built because getting pages indexed quickly matters. Whether you're launching new content, updating product pages, or fixing broken links — waiting days or weeks for Google to crawl your site is frustrating. Our tool connects directly to Google's official Indexing API so your pages get noticed immediately.
    </p>
  </div>

  <div class="section">
    <div class="section-title">What We Offer</div>
    <div class="section-sub">Everything you need to manage Google indexing at scale</div>
    <div class="feature-grid">
      <div class="feature-card">
        <div class="icon"><?= icon('key',24) ?></div>
        <h3>Secure Key Management</h3>
        <p>Upload your Google Service Account JSON key with drag-and-drop. Stored securely for up to 30 days, then automatically deleted.</p>
      </div>
      <div class="feature-card">
        <div class="icon"><?= icon('map',24) ?></div>
        <h3>Sitemap Import</h3>
        <p>Paste your sitemap URL and we'll fetch all pages automatically — including nested sitemap indexes with hundreds of URLs.</p>
      </div>
      <div class="feature-card">
        <div class="icon"><?= icon('chart',24) ?></div>
        <h3>Batch Submission</h3>
        <p>Submit up to 10,000 URLs per day (Business plan) with real-time progress tracking and detailed success/failure reports.</p>
      </div>
      <div class="feature-card">
        <div class="icon"><?= icon('clipboard',24) ?></div>
        <h3>Submission History</h3>
        <p>Track all your submissions with timestamps, success rates, and detailed error messages to troubleshoot issues.</p>
      </div>
      <div class="feature-card">
        <div class="icon"><?= icon('lock',24) ?></div>
        <h3>Privacy First</h3>
        <p>No database required — everything stored as encrypted local files. Your data never leaves our server or gets shared.</p>
      </div>
      <div class="feature-card">
        <div class="icon"><?= icon('creditCard',24) ?></div>
        <h3>Crypto Payments</h3>
        <p>Pay with USDT, ETH, BTC, BNB, SOL and more via NOWPayments. Anonymous, fast, and globally accessible.</p>
      </div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Our Values</div>
    <div class="section-sub">What we stand for</div>
    <div class="values-grid">
      <div class="value-card"><div class="icon"><?= icon('shield',22) ?></div><h3>Privacy</h3><p>Minimal data collection, auto-deletion, no tracking</p></div>
      <div class="value-card"><div class="icon"><?= icon('zap',22) ?></div><h3>Speed</h3><p>Instant submissions, real-time feedback, no delays</p></div>
      <div class="value-card"><div class="icon"><?= icon('gem',22) ?></div><h3>Transparency</h3><p>Clear pricing, honest policies, no hidden fees</p></div>
      <div class="value-card"><div class="icon"><?= icon('shield',22) ?></div><h3>Security</h3><p>CSRF protection, secure storage, HTTPS only</p></div>
    </div>
  </div>

  <div class="contact-box">
    <h2><?= icon('mail',22) ?> Get in Touch</h2>
    <p>Have questions, feedback, or need help? We typically respond within 24 hours.</p>
    <div class="contact-methods">
      <a href="index.php" class="contact-btn primary"><?= icon('zap',15) ?> Try the Tool</a>
      <a href="privacy.php" class="contact-btn secondary"><?= icon('lock',15) ?> Privacy Policy</a>
      <a href="terms.php" class="contact-btn secondary"><?= icon('clipboard',15) ?> Terms of Service</a>
      <a href="refund.php" class="contact-btn secondary"><?= icon('checkCircle',15) ?> Refund Policy</a>
    </div>
    <p style="margin-top:24px;font-size:13px;color:var(--text3)">
      For urgent support, include your Order ID or user email in your message.
    </p>
  </div>

</div>

<footer>
  <p style="margin-bottom:12px">© <?= date('Y') ?> <?= h(SITE_NAME) ?> — All rights reserved</p>
  <a href="index.php">Home</a><a href="privacy.php">Privacy</a><a href="terms.php">Terms</a><a href="refund.php">Refund</a><a href="about.php">About</a>
</footer>
</body>
</html>
