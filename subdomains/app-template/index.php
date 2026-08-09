<?php
/* ════════════════════════════════════════════════════
   {app-slug}.yassota.com — Per-app subdomain template
   Detects which app this subdomain is for, pulls data
   from DB, then renders an app landing page with full
   legal pages for AdSense compliance.
   ════════════════════════════════════════════════════ */

// Detect which subdomain/app we're serving
$host      = $_SERVER['HTTP_HOST'] ?? '';
$app_slug  = strtolower(explode('.', $host)[0]);
$page      = $_GET['page'] ?? '';

// Serve legal pages locally
if (in_array($page, ['privacy', 'terms', 'about', 'contact', 'dmca'])) {
    serve_legal_page($page, $app_slug);
    exit;
}

// Find the config.php from the parent site
$config_path = dirname(__DIR__, 2) . '/config.php';
$app = null;
if (file_exists($config_path)) {
    require_once $config_path;
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $stmt = $pdo->prepare("SELECT a.*, c.name AS cat_name FROM apps a LEFT JOIN categories c ON c.id=a.category_id WHERE a.slug=? AND a.status='published' LIMIT 1");
        $stmt->execute([$app_slug]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

$main_url   = 'https://yassota.com/' . $app_slug;
$app_name   = $app['name'] ?? $app_slug;
$app_desc   = $app['short_description'] ?? '';
$app_cat    = $app['cat_name'] ?? 'تطبيق';
$play_url   = $app['download_url'] ?? "https://play.google.com/store/search?q={$app_slug}";
$icon_url   = !empty($app['icon_path']) ? 'https://yassota.com/' . ltrim($app['icon_path'], '/') : '';
$rating     = $app['rating'] ?? '4.5';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>تحميل <?= htmlspecialchars($app_name) ?> APK للأندرويد | <?= htmlspecialchars($app_slug) ?>.yassota.com</title>
<meta name="description" content="<?= htmlspecialchars($app_desc ?: "تحميل تطبيق {$app_name} للأندرويد مجاناً — احدث اصدار بدون انتظار") ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta property="og:title" content="تحميل <?= htmlspecialchars($app_name) ?> APK">
<meta property="og:url" content="https://<?= htmlspecialchars($host) ?>/">
<link rel="canonical" href="https://<?= htmlspecialchars($host) ?>/">
<?php if ($icon_url): ?>
<meta property="og:image" content="<?= htmlspecialchars($icon_url) ?>">
<?php endif; ?>
<style>
:root{--cyan:#0ea5e9;--purple:#7c3aed;--green:#01875f}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Cairo','Tajawal',system-ui,sans-serif;background:#f4f7fb;color:#0f172a;direction:rtl;line-height:1.65}
header{background:#0c1e36;color:#e2e8f0;padding:12px 20px;display:flex;align-items:center;justify-content:space-between}
.logo{font-family:monospace;font-size:14px;font-weight:700;letter-spacing:1.5px}
.logo-yas{background:linear-gradient(135deg,#22d3ee,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.logo-sota{background:linear-gradient(135deg,#7c3aed,#f97316);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
nav a{color:rgba(226,232,240,.55);font-size:12px;text-decoration:none;margin-right:12px}
nav a:hover{color:#0ea5e9}
.hero{background:linear-gradient(135deg,#0c1e36 0%,#132240 60%,#1a1060 100%);padding:40px 20px;text-align:center;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;top:-30%;right:-5%;width:250px;height:250px;border-radius:50%;background:radial-gradient(circle,rgba(14,165,233,.15),transparent 70%);pointer-events:none}
.hero-icon{width:100px;height:100px;border-radius:22px;object-fit:cover;border:2px solid rgba(14,165,233,.25);box-shadow:0 8px 28px rgba(0,0,0,.3);margin:0 auto 16px;display:block;background:#1a2840}
.hero-icon-placeholder{width:100px;height:100px;border-radius:22px;background:linear-gradient(135deg,#1a2840,#2d1b69);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:40px}
.hero-cat{display:inline-block;padding:3px 12px;background:rgba(14,165,233,.15);border:1px solid rgba(14,165,233,.25);border-radius:20px;color:#0ea5e9;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;margin-bottom:10px}
.hero-name{font-size:26px;font-weight:900;color:#fff;margin-bottom:8px;letter-spacing:-.02em}
.hero-desc{font-size:14px;color:rgba(226,232,240,.65);max-width:480px;margin:0 auto 20px;line-height:1.7}
.rating{display:flex;align-items:center;justify-content:center;gap:4px;margin-bottom:20px}
.stars{color:#f59e0b;font-size:16px;letter-spacing:1px}
.rating-num{color:rgba(226,232,240,.8);font-size:14px;font-weight:700}
.btn-dl{display:inline-flex;align-items:center;gap:10px;padding:14px 32px;background:linear-gradient(135deg,#01875f,#017a57);color:#fff;border:none;border-radius:14px;font-size:16px;font-weight:800;text-decoration:none;cursor:pointer;box-shadow:0 6px 20px rgba(1,135,95,.4);transition:transform .2s,box-shadow .2s;margin-bottom:10px}
.btn-dl:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(1,135,95,.55)}
.btn-play{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:transparent;color:rgba(226,232,240,.7);border:1px solid rgba(255,255,255,.15);border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:border-color .15s,color .15s}
.btn-play:hover{border-color:var(--cyan);color:var(--cyan)}
.wrap{max-width:820px;margin:0 auto;padding:28px 20px}
.card{background:#fff;border:1px solid rgba(15,23,42,.07);border-radius:16px;padding:24px;margin-bottom:18px;box-shadow:0 2px 8px rgba(15,23,42,.05)}
.card-title{font-size:17px;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.card-title::before{content:'';display:inline-block;width:4px;height:20px;background:linear-gradient(180deg,var(--cyan),var(--purple));border-radius:4px}
.main-url-block{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:14px 18px;margin-bottom:18px;text-align:center}
.main-url-block a{color:var(--cyan);font-weight:700;font-size:14px}
footer{background:#0c1e36;color:rgba(226,232,240,.5);text-align:center;padding:20px;font-size:12px;margin-top:40px}
footer a{color:rgba(226,232,240,.4);text-decoration:none;margin:0 6px}
footer a:hover{color:var(--cyan)}
</style>
</head>
<body>

<header>
  <a href="https://yassota.com" class="logo"><span class="logo-yas">yas</span><span class="logo-sota">sota</span></a>
  <nav>
    <a href="https://yassota.com">الرئيسية</a>
    <a href="?page=about">من نحن</a>
    <a href="?page=privacy">الخصوصية</a>
    <a href="?page=contact">تواصل</a>
  </nav>
</header>

<div class="hero">
  <?php if ($icon_url): ?>
  <img src="<?= htmlspecialchars($icon_url) ?>" alt="<?= htmlspecialchars($app_name) ?>" class="hero-icon">
  <?php else: ?>
  <div class="hero-icon-placeholder">📱</div>
  <?php endif; ?>
  <span class="hero-cat"><?= htmlspecialchars($app_cat) ?></span>
  <h1 class="hero-name"><?= htmlspecialchars($app_name) ?></h1>
  <?php if ($app_desc): ?>
  <p class="hero-desc"><?= htmlspecialchars($app_desc) ?></p>
  <?php endif; ?>
  <div class="rating">
    <span class="stars">★★★★★</span>
    <span class="rating-num"><?= htmlspecialchars((string)$rating) ?>/5</span>
  </div>
  <div>
    <a href="<?= htmlspecialchars($play_url) ?>" class="btn-dl" target="_blank" rel="noopener">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3v12m0 0l-4-4m4 4l4-4M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>
      تحميل التطبيق
    </a>
    <br>
    <a href="<?= htmlspecialchars($play_url) ?>" class="btn-play" target="_blank" rel="noopener">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M5 3l14 9-14 9V3z"/></svg>
      متجر جوجل بلاي
    </a>
  </div>
</div>

<div class="wrap">
  <div class="main-url-block">
    📄 صفحة التطبيق الكاملة: <a href="<?= htmlspecialchars($main_url) ?>"><?= htmlspecialchars($main_url) ?></a>
  </div>

  <?php if (!empty($app['long_description'])): ?>
  <div class="card">
    <div class="card-title">عن التطبيق</div>
    <div style="font-size:14px;color:#475569;line-height:1.8"><?= nl2br(htmlspecialchars(mb_substr($app['long_description'], 0, 800))) ?>
    <?php if (mb_strlen($app['long_description']) > 800): ?>
    … <a href="<?= htmlspecialchars($main_url) ?>" style="color:var(--cyan)">اقرأ المزيد</a>
    <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-title">معلومات التطبيق</div>
    <table style="width:100%;font-size:13px;color:#475569">
      <?php if (!empty($app['developer'])): ?><tr><td style="padding:6px 0;font-weight:700;color:#0f172a;width:40%">المطور</td><td><?= htmlspecialchars($app['developer']) ?></td></tr><?php endif; ?>
      <?php if (!empty($app['version'])): ?><tr><td style="padding:6px 0;font-weight:700;color:#0f172a">الإصدار</td><td><?= htmlspecialchars($app['version']) ?></td></tr><?php endif; ?>
      <?php if (!empty($app['android_version'])): ?><tr><td style="padding:6px 0;font-weight:700;color:#0f172a">أندرويد</td><td><?= htmlspecialchars($app['android_version']) ?>+</td></tr><?php endif; ?>
      <?php if (!empty($app['license'])): ?><tr><td style="padding:6px 0;font-weight:700;color:#0f172a">الترخيص</td><td><?= htmlspecialchars($app['license']) ?></td></tr><?php endif; ?>
    </table>
  </div>
</div>

<footer>
  <p>© <?= date('Y') ?> yassota.com — جميع الحقوق محفوظة</p>
  <p style="margin-top:8px">
    <a href="?page=about">من نحن</a>
    <a href="?page=privacy">الخصوصية</a>
    <a href="?page=terms">الشروط</a>
    <a href="?page=contact">تواصل</a>
    <a href="?page=dmca">DMCA</a>
    <a href="https://yassota.com">يسوتا الرئيسية</a>
  </p>
</footer>

</body>
</html>
<?php

function serve_legal_page(string $page, string $app_slug): void {
    $app_name = str_replace('-', ' ', $app_slug);
    $pages = [
        'about'   => ['title' => "من نحن — {$app_name}", 'content' => "<h2>عن هذه الصفحة</h2><p>هذه الصفحة جزء من منصة يسوتا (yassota.com) لتوفير معلومات عن تطبيق {$app_name} للمستخدمين العرب.</p><p>للمزيد عن يسوتا: <a href='https://yassota.com/about'>من نحن</a></p>"],
        'privacy' => ['title' => "سياسة الخصوصية — {$app_name}", 'content' => "<h2>سياسة الخصوصية</h2><p>هذه الصفحة مُدارة من منصة يسوتا. لا نجمع بيانات شخصية من زوار صفحات التطبيقات.</p><p><a href='https://yassota.com/privacy-policy'>سياسة خصوصية يسوتا الكاملة</a></p>"],
        'terms'   => ['title' => "شروط الاستخدام — {$app_name}", 'content' => "<h2>شروط الاستخدام</h2><p><a href='https://yassota.com/terms'>شروط استخدام يسوتا</a></p>"],
        'contact' => ['title' => "تواصل معنا — {$app_name}", 'content' => "<h2>تواصل معنا</h2><p><a href='https://yassota.com/contact'>صفحة التواصل</a></p>"],
        'dmca'    => ['title' => "DMCA — {$app_name}", 'content' => "<h2>DMCA</h2><p><a href='https://yassota.com/dmca'>إشعار DMCA</a></p>"],
    ];
    $p = $pages[$page] ?? $pages['about'];
    echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='utf-8'><title>" . htmlspecialchars($p['title']) . "</title><meta name='viewport' content='width=device-width,initial-scale=1'><style>body{font-family:Cairo,system-ui;background:#f4f7fb;color:#0f172a;direction:rtl;line-height:1.7;padding:40px 20px}h2{font-size:20px;font-weight:800;margin-bottom:12px;color:#0f172a}p{color:#475569;margin-bottom:12px}a{color:#0ea5e9}.back{display:inline-block;margin-bottom:20px;color:#0ea5e9;text-decoration:none;font-weight:600}</style></head><body><a href='/' class='back'>← العودة للتطبيق</a>" . $p['content'] . "<p style='margin-top:20px'><a href='/'>← العودة</a> | <a href='https://yassota.com'>يسوتا الرئيسية</a></p></body></html>";
}
