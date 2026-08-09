<?php
/* ════════════════════════════════════════════════════
   {slug}.yassota.com — Per-article subdomain template
   Detects which article this subdomain is for, pulls
   data from DB, renders a blog article landing page
   with full legal pages for AdSense compliance.
   ════════════════════════════════════════════════════ */

$host     = $_SERVER['HTTP_HOST'] ?? '';
$slug     = strtolower(explode('.', $host)[0]);
$page     = $_GET['page'] ?? '';

if (in_array($page, ['privacy', 'terms', 'about', 'contact', 'dmca'])) {
    serve_legal_page($page, $slug);
    exit;
}

$config_path = dirname(__DIR__, 2) . '/config.php';
$post = null;
if (file_exists($config_path)) {
    require_once $config_path;
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $stmt = $pdo->prepare("SELECT p.*, c.name AS cat_name FROM posts p LEFT JOIN categories c ON c.id=p.category_id WHERE p.slug=? AND p.status='published' LIMIT 1");
        $stmt->execute([$slug]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

$main_url    = 'https://yassota.com/' . $slug;
$title       = $post['title'] ?? $slug;
$excerpt     = $post['excerpt'] ?? ($post['meta_description'] ?? '');
$cat         = $post['cat_name'] ?? 'مقال';
$cover       = !empty($post['featured_image']) ? 'https://yassota.com/' . ltrim($post['featured_image'], '/') : '';
$author      = $post['author_name'] ?? 'فريق يسوتا';
$date        = !empty($post['published_at']) ? date('Y-m-d', strtotime($post['published_at'])) : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($title) ?> | <?= htmlspecialchars($slug) ?>.yassota.com</title>
<meta name="description" content="<?= htmlspecialchars($excerpt ?: $title) ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta property="og:title" content="<?= htmlspecialchars($title) ?>">
<meta property="og:url" content="https://<?= htmlspecialchars($host) ?>/">
<?php if ($cover): ?>
<meta property="og:image" content="<?= htmlspecialchars($cover) ?>">
<?php endif; ?>
<?php if ($date): ?>
<meta property="article:published_time" content="<?= htmlspecialchars($date) ?>">
<?php endif; ?>
<link rel="canonical" href="https://<?= htmlspecialchars($host) ?>/">
<style>
:root{--cyan:#0ea5e9;--purple:#7c3aed;--green:#01875f}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Cairo','Tajawal',system-ui,sans-serif;background:#f4f7fb;color:#0f172a;direction:rtl;line-height:1.7}
header{background:#0c1e36;color:#e2e8f0;padding:12px 20px;display:flex;align-items:center;justify-content:space-between}
.logo{font-family:monospace;font-size:14px;font-weight:700;letter-spacing:1.5px}
.logo-yas{background:linear-gradient(135deg,#22d3ee,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.logo-sota{background:linear-gradient(135deg,#7c3aed,#f97316);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
nav a{color:rgba(226,232,240,.55);font-size:12px;text-decoration:none;margin-right:12px}
nav a:hover{color:#0ea5e9}
.hero{background:linear-gradient(135deg,#0c1e36 0%,#132240 60%,#1a1060 100%);padding:48px 20px 36px;text-align:center;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;top:-20%;right:-5%;width:280px;height:280px;border-radius:50%;background:radial-gradient(circle,rgba(14,165,233,.12),transparent 70%);pointer-events:none}
.hero-cover{width:100%;max-width:700px;height:240px;object-fit:cover;border-radius:16px;margin:0 auto 20px;display:block;border:1px solid rgba(255,255,255,.08)}
.hero-cat{display:inline-block;padding:3px 12px;background:rgba(14,165,233,.15);border:1px solid rgba(14,165,233,.25);border-radius:20px;color:#0ea5e9;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;margin-bottom:12px}
.hero-title{font-size:26px;font-weight:900;color:#fff;margin-bottom:12px;line-height:1.35;max-width:680px;margin-left:auto;margin-right:auto}
.hero-meta{font-size:13px;color:rgba(226,232,240,.5);margin-bottom:20px}
.hero-meta span{margin:0 8px}
.hero-excerpt{font-size:14px;color:rgba(226,232,240,.65);max-width:560px;margin:0 auto 24px;line-height:1.75}
.btn-read{display:inline-flex;align-items:center;gap:10px;padding:13px 28px;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;border:none;border-radius:14px;font-size:15px;font-weight:800;text-decoration:none;cursor:pointer;box-shadow:0 6px 20px rgba(124,58,237,.4);transition:transform .2s,box-shadow .2s}
.btn-read:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(124,58,237,.55)}
.wrap{max-width:780px;margin:0 auto;padding:28px 20px}
.notice-box{background:#fff;border:1px solid rgba(14,165,233,.18);border-radius:14px;padding:18px 22px;margin-bottom:20px;display:flex;align-items:flex-start;gap:14px;box-shadow:0 2px 10px rgba(15,23,42,.05)}
.notice-icon{font-size:24px;flex-shrink:0;margin-top:2px}
.notice-text{font-size:14px;color:#475569;line-height:1.7}
.notice-text a{color:var(--cyan);font-weight:700;text-decoration:none}
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
  <?php if ($cover): ?>
  <img src="<?= htmlspecialchars($cover) ?>" alt="<?= htmlspecialchars($title) ?>" class="hero-cover">
  <?php endif; ?>
  <span class="hero-cat"><?= htmlspecialchars($cat) ?></span>
  <h1 class="hero-title"><?= htmlspecialchars($title) ?></h1>
  <?php if ($author || $date): ?>
  <div class="hero-meta">
    <?php if ($author): ?><span>✍️ <?= htmlspecialchars($author) ?></span><?php endif; ?>
    <?php if ($date): ?><span>📅 <?= htmlspecialchars($date) ?></span><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php if ($excerpt): ?>
  <p class="hero-excerpt"><?= htmlspecialchars($excerpt) ?></p>
  <?php endif; ?>
  <a href="<?= htmlspecialchars($main_url) ?>" class="btn-read" target="_blank" rel="noopener">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
    قراءة المقال كاملاً
  </a>
</div>

<div class="wrap">
  <div class="notice-box">
    <span class="notice-icon">📄</span>
    <div class="notice-text">
      هذه صفحة مرجعية لمقال <strong><?= htmlspecialchars($title) ?></strong> من منصة يسوتا.<br>
      للقراءة الكاملة مع التعليقات والمحتوى التفاعلي:
      <a href="<?= htmlspecialchars($main_url) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($main_url) ?></a>
    </div>
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

function serve_legal_page(string $page, string $slug): void {
    $title_slug = str_replace('-', ' ', $slug);
    $pages = [
        'about'   => ['title' => "من نحن — {$title_slug}", 'content' => "<h2>عن هذه الصفحة</h2><p>هذه الصفحة جزء من منصة يسوتا (yassota.com) وتعرض مقالاً بعنوان {$title_slug}.</p><p>للمزيد: <a href='https://yassota.com/about'>من نحن</a></p>"],
        'privacy' => ['title' => "سياسة الخصوصية — {$title_slug}", 'content' => "<h2>سياسة الخصوصية</h2><p>لا نجمع بيانات شخصية من زوار الصفحات الفرعية.</p><p><a href='https://yassota.com/privacy-policy'>سياسة خصوصية يسوتا الكاملة</a></p>"],
        'terms'   => ['title' => "شروط الاستخدام — {$title_slug}", 'content' => "<h2>شروط الاستخدام</h2><p><a href='https://yassota.com/terms'>شروط استخدام يسوتا</a></p>"],
        'contact' => ['title' => "تواصل معنا — {$title_slug}", 'content' => "<h2>تواصل معنا</h2><p><a href='https://yassota.com/contact'>صفحة التواصل</a></p>"],
        'dmca'    => ['title' => "DMCA — {$title_slug}", 'content' => "<h2>DMCA</h2><p><a href='https://yassota.com/dmca'>إشعار DMCA</a></p>"],
    ];
    $p = $pages[$page] ?? $pages['about'];
    echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='utf-8'><title>" . htmlspecialchars($p['title']) . "</title><meta name='viewport' content='width=device-width,initial-scale=1'><style>body{font-family:Cairo,system-ui;background:#f4f7fb;color:#0f172a;direction:rtl;line-height:1.7;padding:40px 20px}h2{font-size:20px;font-weight:800;margin-bottom:12px}p{color:#475569;margin-bottom:12px}a{color:#0ea5e9}.back{display:inline-block;margin-bottom:20px;color:#0ea5e9;text-decoration:none;font-weight:600}</style></head><body><a href='/' class='back'>← العودة للمقال</a>" . $p['content'] . "<p style='margin-top:20px'><a href='/'>← العودة</a> | <a href='https://yassota.com'>يسوتا الرئيسية</a></p></body></html>";
}
