<?php
/* ═══════════════════════════════════════════════════════════
   szad.yassota.com — Szad AI dedicated subdomain
   Full-screen chat interface + legal pages
   ═══════════════════════════════════════════════════════════ */

$page = $_GET['page'] ?? '';

if (in_array($page, ['privacy','terms','about','contact','dmca'])) {
    serve_legal_page($page);
    exit;
}

// For sitemap
if ($page === 'sitemap') {
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    echo "  <url><loc>https://szad.yassota.com/</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>\n";
    foreach (['about','privacy','terms','contact','dmca'] as $p) {
        echo "  <url><loc>https://szad.yassota.com/?page={$p}</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>\n";
    }
    echo '</urlset>';
    exit;
}

$schema = [
    '@context' => 'https://schema.org',
    '@type'    => 'WebApplication',
    'name'     => 'Szad AI — مساعد الذكاء الاصطناعي العربي',
    'url'      => 'https://szad.yassota.com/',
    'description' => 'Szad AI مساعد ذكاء اصطناعي عربي متخصص في البحث والتحليل العميق. محادثة، بحث إنترنت، تفكير عميق — مجاناً.',
    'applicationCategory' => 'AIApplication',
    'operatingSystem' => 'All',
    'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
    'author' => ['@type' => 'Organization', 'name' => 'yassota.com', 'url' => 'https://yassota.com'],
    'inLanguage' => 'ar',
];
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>Szad AI — مساعد الذكاء الاصطناعي العربي | بحث + تفكير عميق + محادثة</title>
<meta name="description" content="Szad AI هو مساعدك الذكي العربي المتخصص في البحث الفوري والتفكير العميق والتحليل الشامل. مجاني، بدون تسجيل، يعمل الآن.">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://szad.yassota.com/">
<meta property="og:type"        content="website">
<meta property="og:title"       content="Szad AI — مساعد الذكاء الاصطناعي العربي">
<meta property="og:description" content="بحث إنترنت + تفكير عميق + محادثة ذكية. مجاناً وبدون تسجيل.">
<meta property="og:url"         content="https://szad.yassota.com/">
<meta property="og:image"       content="https://yassota.com/assets/img/szad-og.png">
<meta property="og:site_name"   content="Szad AI — yassota.com">
<meta name="twitter:card"       content="summary_large_image">
<link rel="sitemap" type="application/xml" href="https://szad.yassota.com/sitemap.xml">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%237c3aed'/><text y='.9em' font-size='75' x='12'>⚡</text></svg>">
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;overflow:hidden}
body{font-family:'Cairo','Tajawal',system-ui,sans-serif;background:#0d0d1a;color:#e2e8f0;direction:rtl;display:flex;flex-direction:column}

/* ── Top bar ── */
.top-bar{
  flex-shrink:0;height:48px;
  background:rgba(10,10,20,.96);
  border-bottom:1px solid rgba(124,58,237,.3);
  display:flex;align-items:center;justify-content:space-between;
  padding:0 16px;z-index:200;
  backdrop-filter:blur(16px);
  box-shadow:0 1px 20px rgba(124,58,237,.15);
}
.brand{display:flex;align-items:center;gap:10px}
.brand-icon{
  width:30px;height:30px;border-radius:8px;
  background:linear-gradient(135deg,#7c3aed,#dc2626);
  display:flex;align-items:center;justify-content:center;font-size:16px;
}
.brand-name{
  font-family:monospace;font-size:16px;font-weight:800;letter-spacing:2px;
  background:linear-gradient(135deg,#a78bfa,#f97316);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.badge-live{
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 9px;border-radius:20px;
  background:rgba(220,38,38,.15);border:1px solid rgba(220,38,38,.3);
  font-size:10.5px;font-weight:700;color:#fca5a5;
}
.badge-live::before{content:'';width:6px;height:6px;border-radius:50%;background:#dc2626;animation:pulse 1.5s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.top-links{display:flex;align-items:center;gap:4px}
.top-links a{color:rgba(226,232,240,.45);font-size:11.5px;text-decoration:none;padding:4px 8px;border-radius:6px;transition:all .15s}
.top-links a:hover{color:#a78bfa;background:rgba(124,58,237,.12)}
.top-links .btn-main{
  background:linear-gradient(135deg,#7c3aed,#4f46e5);
  color:#fff;border-radius:8px;padding:5px 12px;font-size:11.5px;font-weight:700;
}
.top-links .btn-main:hover{opacity:.9;background:linear-gradient(135deg,#7c3aed,#4f46e5)}

/* ── Chat iframe ── */
.chat-frame{
  flex:1;width:100%;border:none;
  background:#0d0d1a;
  display:block;
}

/* ── SEO section (hidden below iframe, crawlable) ── */
.seo-section{
  display:none; /* hidden from view but crawlable */
}
</style>
</head>
<body>

<div class="top-bar">
  <div class="brand">
    <div class="brand-icon">⚡</div>
    <span class="brand-name">SZAD</span>
    <span class="badge-live">متصل الآن</span>
  </div>
  <div class="top-links">
    <a href="https://yassota.com/tools/chat/" title="ياسمين AI">🌸 ياسمين</a>
    <a href="https://yassota.com">يسوتا</a>
    <a href="?page=about">من نحن</a>
    <a href="?page=privacy">الخصوصية</a>
    <a href="https://yassota.com/upgrade" class="btn-main">⭐ ترقية</a>
  </div>
</div>

<iframe
  src="https://yassota.com/tools/szad/"
  class="chat-frame"
  title="Szad AI — مساعد الذكاء الاصطناعي"
  loading="eager"
  allow="clipboard-write; microphone"
  referrerpolicy="origin"
  id="szad-frame">
</iframe>

<!-- SEO content — hidden visually but indexed by Google -->
<div class="seo-section" aria-hidden="true">
  <h1>Szad AI — مساعد الذكاء الاصطناعي العربي</h1>
  <p>Szad AI هو مساعدك الذكي المتخصص في البحث الفوري والتفكير العميق والتحليل الشامل. يدعم ثلاثة أوضاع:</p>
  <ul>
    <li>💬 محادثة — دردشة ذكية متعددة المجالات</li>
    <li>🔍 بحث في الإنترنت — معلومات آنية من أحدث المصادر</li>
    <li>🧠 تفكير عميق — تحليل منطقي خطوة بخطوة</li>
  </ul>
  <p>مجاني تماماً، بدون تسجيل، يعمل فوراً. <a href="https://yassota.com/tools/szad/">ابدأ الآن</a></p>
</div>

<script>
// If iframe blocked (X-Frame-Options from CDN/proxy), redirect to direct tool
document.getElementById('szad-frame').addEventListener('error', function() {
  window.location.href = 'https://yassota.com/tools/szad/';
});
// Fallback: after 5s if frame still not loaded
setTimeout(function() {
  try {
    var f = document.getElementById('szad-frame');
    if (!f.contentDocument && !f.contentWindow) {
      window.location.href = 'https://yassota.com/tools/szad/';
    }
  } catch(e) {
    // Cross-origin access blocked = iframe IS loading, which is correct
  }
}, 5000);
</script>
</body>
</html>
<?php

function serve_legal_page(string $page): void {
    $pages = [
        'about' => [
            'title' => 'من نحن — Szad AI',
            'h'     => 'عن Szad AI',
            'body'  => '<p>Szad AI هو مساعد ذكاء اصطناعي عربي متخصص في البحث والتفكير العميق، طوّرته منصة <a href="https://yassota.com">يسوتا</a>.</p>
                        <p>يتميز Szad بثلاثة أوضاع: محادثة ذكية، بحث فوري في الإنترنت، وتفكير عميق منطقي.</p>
                        <p>الاستخدام الأساسي مجاني بالكامل. <a href="https://yassota.com/upgrade">الاشتراك المدفوع</a> يوفر حدوداً أعلى ونماذج أقوى.</p>
                        <p><a href="https://yassota.com/about">المزيد عن يسوتا ←</a></p>',
        ],
        'privacy' => [
            'title' => 'سياسة الخصوصية — Szad AI',
            'h'     => 'سياسة الخصوصية',
            'body'  => '<p>آخر تحديث: ' . date('Y-m-d') . '</p>
                        <h3 style="margin:12px 0 6px;font-size:15px">المحادثات</h3>
                        <p>لا تُحفظ محادثاتك بدون تسجيل. عند التسجيل، تُحفظ المحادثات مشفرة في قاعدة البيانات ولا تُشارك مع أطراف ثالثة.</p>
                        <h3 style="margin:12px 0 6px;font-size:15px">الإعلانات</h3>
                        <p>قد تظهر إعلانات Google AdSense تخضع <a href="https://policies.google.com/privacy">لسياسة جوجل</a>.</p>
                        <p><a href="https://yassota.com/privacy-policy">سياسة الخصوصية الكاملة ←</a></p>',
        ],
        'terms' => [
            'title' => 'شروط الاستخدام — Szad AI',
            'h'     => 'شروط الاستخدام',
            'body'  => '<p>باستخدام Szad AI توافق على <a href="https://yassota.com/terms">شروط استخدام يسوتا</a>.</p>
                        <p>Szad AI مقدّم "كما هو" ولا يضمن دقة المعلومات المُقدَّمة من نماذج الذكاء الاصطناعي.</p>',
        ],
        'contact' => [
            'title' => 'تواصل — Szad AI',
            'h'     => 'تواصل معنا',
            'body'  => '<p>للتواصل بشأن Szad AI: <a href="https://yassota.com/contact">نموذج التواصل ←</a></p>',
        ],
        'dmca' => [
            'title' => 'DMCA — Szad AI',
            'h'     => 'إشعار DMCA',
            'body'  => '<p>لإرسال إشعار إزالة محتوى: <a href="https://yassota.com/dmca">نموذج DMCA ←</a></p>',
        ],
    ];
    $p = $pages[$page] ?? $pages['about'];
    echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='utf-8'>"
        . "<title>" . htmlspecialchars($p['title']) . "</title>"
        . "<meta name='viewport' content='width=device-width,initial-scale=1'>"
        . "<meta name='robots' content='noindex,follow'>"
        . "<style>*{box-sizing:border-box}body{font-family:Cairo,system-ui,sans-serif;background:#0d0d1a;color:#e2e8f0;direction:rtl;line-height:1.75;padding:0}"
        . "header{background:rgba(10,10,20,.98);padding:14px 20px;border-bottom:1px solid rgba(124,58,237,.3);display:flex;align-items:center;justify-content:space-between}"
        . ".brand{font-family:monospace;font-size:15px;font-weight:800;letter-spacing:2px;background:linear-gradient(135deg,#a78bfa,#f97316);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}"
        . ".wrap{max-width:720px;margin:0 auto;padding:36px 20px}"
        . "h1{font-size:22px;font-weight:800;margin-bottom:16px;color:#e2e8f0}"
        . "h3{font-size:15px;font-weight:700;color:#e2e8f0}"
        . "p{color:#94a3b8;margin-bottom:12px;font-size:14px;line-height:1.8}"
        . "a{color:#a78bfa}.back{display:inline-flex;align-items:center;gap:6px;margin-bottom:24px;color:#a78bfa;text-decoration:none;font-weight:600;font-size:13px}"
        . "footer{background:rgba(10,10,20,.98);color:rgba(226,232,240,.35);text-align:center;padding:16px;font-size:12px;margin-top:40px}footer a{color:rgba(226,232,240,.3);margin:0 6px;text-decoration:none}"
        . "</style></head><body>"
        . "<header><span class='brand'>SZAD AI</span><a href='/' style='color:#a78bfa;font-size:12px;text-decoration:none'>← العودة للمحادثة</a></header>"
        . "<div class='wrap'><a href='/' class='back'>← العودة</a><h1>{$p['h']}</h1>{$p['body']}</div>"
        . "<footer><a href='/'>الرئيسية</a><a href='/?page=privacy'>الخصوصية</a><a href='/?page=contact'>تواصل</a><a href='https://yassota.com'>يسوتا</a></footer>"
        . "</body></html>";
}
