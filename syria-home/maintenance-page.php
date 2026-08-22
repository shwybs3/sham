<?php
/** Rendered by sh_check_maintenance() — never requested directly. */
$siteName = setting('site_name', 'Syria Home');
$parentUrl = trim(setting('parent_site_url'));
$tagline = setting('site_tagline');
?><!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($siteName) ?> — Coming Soon</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
  :root{--brand1:#6366f1;--brand2:#22d3ee}
  *{box-sizing:border-box}
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
    font-family:'Segoe UI',Arial,sans-serif;background:linear-gradient(160deg,#0f172a,#1e1b4b);color:#e2e8f0;padding:20px}
  .box{max-width:560px;text-align:center;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);
    border-radius:22px;padding:48px 34px;backdrop-filter:blur(6px)}
  .mark{width:64px;height:64px;border-radius:18px;margin:0 auto 22px;background:linear-gradient(135deg,var(--brand1),var(--brand2));
    display:flex;align-items:center;justify-content:center;font-size:26px;color:#fff;animation:float 3s ease-in-out infinite}
  @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
  h1{font-size:26px;margin:0 0 8px}
  p.tag{color:#94a3b8;font-size:15px;margin:0 0 26px}
  .status{display:inline-flex;align-items:center;gap:8px;background:rgba(34,211,238,.12);color:#67e8f9;
    padding:8px 16px;border-radius:99px;font-size:13px;font-weight:700;margin-bottom:26px}
  .dot{width:8px;height:8px;border-radius:50%;background:#22d3ee;animation:pulse 1.6s ease-in-out infinite}
  @keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
  .parent-link{display:inline-flex;align-items:center;gap:10px;background:linear-gradient(135deg,var(--brand1),var(--brand2));
    color:#fff;text-decoration:none;padding:13px 26px;border-radius:12px;font-weight:800;font-size:14px}
</style>
</head><body>
<div class="box">
  <div class="mark"><i class="fa-solid fa-layer-group"></i></div>
  <div class="status"><span class="dot"></span> Under construction</div>
  <h1><?= e($siteName) ?> is almost ready</h1>
  <?php if ($tagline): ?><p class="tag"><?= e($tagline) ?></p><?php endif; ?>
  <?php if ($parentUrl !== ''): ?>
    <p style="color:#cbd5e1;font-size:14px;line-height:1.7">This site is part of the same network as <strong><?= e(parse_url($parentUrl, PHP_URL_HOST) ?: $parentUrl) ?></strong> — while this one is being set up, check that one out:</p>
    <a class="parent-link" href="<?= e($parentUrl) ?>"><i class="fa-solid fa-arrow-up-right-from-square"></i> Visit <?= e(parse_url($parentUrl, PHP_URL_HOST) ?: $parentUrl) ?></a>
  <?php else: ?>
    <p style="color:#cbd5e1;font-size:14px">We'll be live shortly — thanks for your patience.</p>
  <?php endif; ?>
</div>
</body></html>
