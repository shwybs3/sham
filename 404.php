<?php
// Called directly or via ErrorDocument 404 /404.php
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/partials.php';
}
http_response_code(404);

// Popular apps for suggestions
$popular = [];
try {
    $popular = $pdo->query("SELECT name,slug,icon_path FROM apps WHERE status='published' ORDER BY downloads DESC LIMIT 6")->fetchAll();
} catch (Throwable $e) { }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <?php if (function_exists('head_extras')): ?><?= head_extras($pdo) ?><?php endif; ?>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>الصفحة غير موجودة — Apkzilo</title>
  <meta name="robots" content="noindex,nofollow">
  <?php if (function_exists('asset_url')): ?>
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <?php endif; ?>
  <style>
    .e404-wrap {
      min-height: 80vh;
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      text-align: center; padding: 40px 20px;
    }
    .e404-code {
      font-size: clamp(80px, 18vw, 140px);
      font-weight: 900;
      line-height: 1;
      background: linear-gradient(135deg, var(--cyan,#0ea5e9), var(--purple,#7c3aed));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 12px;
    }
    .e404-title {
      font-size: 24px; font-weight: 800;
      color: var(--white,#f1f5f9);
      margin-bottom: 10px;
    }
    .e404-sub {
      font-size: 15px;
      color: var(--muted,#94a3b8);
      margin-bottom: 32px;
      max-width: 400px;
      line-height: 1.7;
    }
    .e404-actions {
      display: flex; flex-wrap: wrap; gap: 12px; justify-content: center;
      margin-bottom: 40px;
    }
    .e404-btn-primary {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 13px 28px; border-radius: 50px;
      background: linear-gradient(135deg, var(--cyan,#0ea5e9), var(--purple,#7c3aed));
      color: #fff; font-weight: 700; font-size: 14px;
      text-decoration: none;
      box-shadow: 0 4px 16px rgba(14,165,233,.25);
      transition: transform .2s, box-shadow .2s;
    }
    .e404-btn-primary:hover { transform: scale(1.04); box-shadow: 0 6px 24px rgba(14,165,233,.4); color:#fff; }
    .e404-btn-sec {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 12px 24px; border-radius: 50px;
      border: 1.5px solid var(--border-c,rgba(255,255,255,.1));
      color: var(--muted,#94a3b8); font-weight: 600; font-size: 14px;
      text-decoration: none;
      transition: border-color .2s, color .2s;
    }
    .e404-btn-sec:hover { border-color: var(--cyan,#0ea5e9); color: var(--cyan,#0ea5e9); }
    .e404-search {
      width: 100%; max-width: 420px;
      display: flex; gap: 8px; margin-bottom: 48px;
    }
    .e404-search input {
      flex: 1; padding: 12px 18px;
      border-radius: 50px;
      border: 1.5px solid var(--border-c,rgba(255,255,255,.1));
      background: var(--navy-700,#1e293b);
      color: var(--white,#f1f5f9);
      font-size: 14px; outline: none;
    }
    .e404-search input:focus { border-color: var(--cyan,#0ea5e9); }
    .e404-search button {
      padding: 12px 20px; border-radius: 50px;
      background: var(--cyan,#0ea5e9); color: #fff;
      border: none; cursor: pointer; font-size: 14px; font-weight: 700;
      transition: background .2s;
    }
    .e404-search button:hover { background: #0284c7; }
    .e404-popular-title {
      font-size: 16px; font-weight: 700;
      color: var(--white,#f1f5f9);
      margin-bottom: 16px; text-align: center;
    }
    .e404-popular-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
      max-width: 420px;
      width: 100%;
    }
    .e404-popular-card {
      display: flex; flex-direction: column; align-items: center; gap: 7px;
      padding: 12px 8px;
      border: 1px solid var(--border-c,rgba(255,255,255,.08));
      border-radius: 14px;
      background: var(--navy-700,#1e293b);
      text-decoration: none;
      transition: border-color .2s, background .2s;
    }
    .e404-popular-card:hover {
      border-color: rgba(6,182,212,.4);
      background: rgba(6,182,212,.06);
    }
    .e404-popular-icon {
      width: 46px; height: 46px; border-radius: 12px;
      border: 1px solid var(--border-c,rgba(255,255,255,.08));
      object-fit: cover;
    }
    .e404-popular-name {
      font-size: 11px; font-weight: 700;
      color: var(--white,#f1f5f9);
      text-align: center; line-height: 1.3;
    }
    @media (max-width: 480px) {
      .e404-popular-grid { grid-template-columns: repeat(3, 1fr); }
    }
  </style>
</head>
<body>
<?php if (function_exists('render_cookie_banner')): ?>
<header class="site-header">
  <a href="<?= h(url('')) ?>" class="logo">yass<span>ota</span></a>
  <nav class="header-nav">
    <a href="<?= h(url('')) ?>">الرئيسية</a>
    <a href="<?= h(url('blog')) ?>">المدونة</a>
  </nav>
</header>
<?php endif; ?>

<div class="e404-wrap">
  <div class="e404-code">404</div>
  <div class="e404-title">الصفحة غير موجودة</div>
  <p class="e404-sub">يبدو أن هذه الصفحة لم تعد موجودة أو تم تغيير رابطها. تحقق من الرابط أو ابحث عن التطبيق الذي تريده.</p>

  <div class="e404-actions">
    <?php if (function_exists('url')): ?>
    <a href="<?= h(url('')) ?>" class="e404-btn-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><path d="M9 22V12h6v10"/></svg>
      الرئيسية
    </a>
    <?php endif; ?>
    <a href="javascript:history.back()" class="e404-btn-sec">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5m0 0l7-7m-7 7l7 7"/></svg>
      الصفحة السابقة
    </a>
  </div>

  <form class="e404-search" action="<?= h(url('')) ?>" method="get">
    <input type="text" name="q" placeholder="ابحث عن تطبيق...">
    <button type="submit">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
    </button>
  </form>

  <?php if ($popular): ?>
  <div class="e404-popular-title">التطبيقات الأكثر تحميلاً</div>
  <div class="e404-popular-grid">
    <?php foreach ($popular as $app): ?>
    <a href="<?= h(app_url($app['slug'])) ?>" class="e404-popular-card">
      <?php if ($app['icon_path']): ?>
        <img src="<?= h(url($app['icon_path'])) ?>" alt="<?= h($app['name']) ?>" class="e404-popular-icon" loading="lazy">
      <?php else: ?>
        <div style="width:46px;height:46px;border-radius:12px;background:var(--navy-600,#334155);display:flex;align-items:center;justify-content:center">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--cyan,#0ea5e9)" stroke-width="1.5"><rect x="5" y="2" width="14" height="20" rx="3"/><path d="M9 7h6M9 11h6M9 15h4"/></svg>
        </div>
      <?php endif; ?>
      <div class="e404-popular-name"><?= h($app['name']) ?></div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php if (function_exists('render_cookie_banner')): ?>
<?= render_cookie_banner() ?? '' ?>
<?php endif; ?>
</body>
</html>
