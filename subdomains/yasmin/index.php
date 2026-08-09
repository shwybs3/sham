<?php
/* ════════════════════════════════════════════════════
   yasmin.yassota.com — Yasmin AI Chat subdomain
   Serves the Yasmin chat tool from its own domain.
   Includes full legal pages for AdSense compliance.
   ════════════════════════════════════════════════════ */

// Redirect to main site's Yasmin tool
// Set the canonical back to yassota.com for SEO
$main_url = 'https://yassota.com/tools/chat/';
$page     = $_GET['page'] ?? '';

// Legal pages served locally on this subdomain
if (in_array($page, ['privacy', 'terms', 'about', 'contact', 'dmca'])) {
    serve_legal_page($page);
    exit;
}

// For all other requests: show a lightweight wrapper that loads the chat
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>ياسمين — مساعدة ذكاء اصطناعي | yasmin.yassota.com</title>
<meta name="description" content="ياسمين هي مساعدتك الذكية باللغة العربية — اسألها عن أي شيء، احصل على إجابات فورية ودقيقة.">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta property="og:title" content="ياسمين — مساعدة ذكاء اصطناعي">
<meta property="og:description" content="مساعدة ذكاء اصطناعي باللغة العربية من يسوتا">
<meta property="og:url" content="https://yasmin.yassota.com/">
<link rel="canonical" href="https://yasmin.yassota.com/">
<link rel="icon" href="https://yassota.com/favicon.ico">
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;overflow:hidden}
body{font-family:'Cairo','Tajawal',sans-serif;background:#0c1e36;color:#e2e8f0;direction:rtl}
.top-bar{
  position:fixed;top:0;left:0;right:0;height:42px;
  background:rgba(12,30,54,.95);
  border-bottom:1px solid rgba(14,165,233,.2);
  display:flex;align-items:center;justify-content:space-between;
  padding:0 20px;z-index:100;backdrop-filter:blur(10px);
}
.top-bar-logo{
  font-family:monospace;font-size:15px;font-weight:700;letter-spacing:2px;
  background:linear-gradient(135deg,#22d3ee,#7c3aed);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.top-bar-links{display:flex;gap:12px}
.top-bar-links a{color:rgba(226,232,240,.55);font-size:12px;text-decoration:none;transition:color .15s}
.top-bar-links a:hover{color:#0ea5e9}
iframe{
  position:fixed;top:42px;left:0;right:0;bottom:0;
  width:100%;height:calc(100% - 42px);
  border:none;background:#0c1e36;
}
</style>
</head>
<body>
<div class="top-bar">
  <span class="top-bar-logo">ياسمين</span>
  <div class="top-bar-links">
    <a href="https://yassota.com">يسوتا الرئيسية</a>
    <a href="?page=about">من نحن</a>
    <a href="?page=privacy">الخصوصية</a>
    <a href="?page=contact">تواصل</a>
  </div>
</div>
<iframe src="https://yassota.com/tools/chat/" title="ياسمين — مساعدة ذكاء اصطناعي" loading="eager" allowfullscreen></iframe>
</body>
</html>
<?php

function serve_legal_page(string $page): void {
    $pages = [
        'about'   => ['title' => 'من نحن — ياسمين AI', 'content' => '<h2>عن ياسمين</h2><p>ياسمين هي مساعدة ذكاء اصطناعي باللغة العربية تعمل بواسطة منصة يسوتا (yassota.com). تهدف ياسمين إلى تقديم إجابات دقيقة وفورية لأسئلتك باللغة العربية.</p><h2>من نحن</h2><p>يسوتا هو دليل تطبيقات أندرويد العربي الأكبر، يقدم معلومات ومراجعات وتطبيقات موثوقة للمستخدمين العرب حول العالم.</p>'],
        'privacy' => ['title' => 'سياسة الخصوصية — ياسمين AI', 'content' => '<h2>سياسة الخصوصية</h2><p>تحترم ياسمين خصوصيتك الكاملة. نحن لا نجمع بيانات شخصية من محادثاتك. المحادثات تُعالج بشكل مؤقت ولا تُخزن بصورة دائمة.</p><h2>ملفات تعريف الارتباط</h2><p>نستخدم ملفات تعريف الارتباط الأساسية فقط لتحسين تجربة الاستخدام. لا نشارك بياناتك مع أطراف ثالثة.</p><p>للمزيد: <a href="https://yassota.com/privacy-policy">سياسة خصوصية يسوتا</a></p>'],
        'terms'   => ['title' => 'شروط الاستخدام — ياسمين AI', 'content' => '<h2>شروط الاستخدام</h2><p>باستخدام ياسمين، توافق على عدم استخدامها لأغراض غير قانونية أو ضارة. ياسمين أداة مساعدة ذكاء اصطناعي ولا تُعتبر إجاباتها بديلاً عن الاستشارة المتخصصة.</p><p>تفاصيل إضافية في <a href="https://yassota.com/terms">شروط استخدام يسوتا</a></p>'],
        'contact' => ['title' => 'تواصل معنا — ياسمين AI', 'content' => '<h2>تواصل معنا</h2><p>للاستفسارات المتعلقة بياسمين، تواصل معنا عبر <a href="https://yassota.com/contact">صفحة التواصل على يسوتا</a>.</p>'],
        'dmca'    => ['title' => 'DMCA — ياسمين AI', 'content' => '<h2>إشعار DMCA</h2><p>لإرسال إشعار حقوق ملكية، تواصل معنا عبر <a href="https://yassota.com/dmca">صفحة DMCA على يسوتا</a>.</p>'],
    ];
    $p = $pages[$page] ?? $pages['about'];
    ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($p['title']) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="canonical" href="https://yasmin.yassota.com/?page=<?= htmlspecialchars($page) ?>">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Cairo','Tajawal',sans-serif;background:#f4f7fb;color:#0f172a;direction:rtl;line-height:1.7;padding:0 0 60px}
.top-bar{background:#0c1e36;color:#e2e8f0;padding:14px 24px;display:flex;align-items:center;justify-content:space-between;margin-bottom:0}
.top-bar a{color:rgba(226,232,240,.6);font-size:13px;text-decoration:none}
.top-bar a:hover{color:#0ea5e9}
.top-bar-logo{font-family:monospace;font-size:15px;font-weight:700;letter-spacing:2px}
.wrap{max-width:780px;margin:0 auto;padding:40px 20px}
h2{font-size:22px;font-weight:800;margin-bottom:12px;margin-top:24px;color:#0f172a}
p{color:#475569;margin-bottom:14px;font-size:15px}
a{color:#0ea5e9}
.legal-links{display:flex;flex-wrap:wrap;gap:10px;margin-top:32px;padding-top:20px;border-top:1px solid #e2e8f0}
.legal-links a{font-size:13px;color:#64748b;text-decoration:none;padding:4px 10px;border:1px solid #e2e8f0;border-radius:20px}
.legal-links a:hover{color:#0ea5e9;border-color:#0ea5e9}
</style>
</head>
<body>
<div class="top-bar">
  <span class="top-bar-logo">ياسمين</span>
  <a href="/">← العودة للمحادثة</a>
</div>
<div class="wrap">
  <?= $p['content'] ?>
  <div class="legal-links">
    <a href="?page=about">من نحن</a>
    <a href="?page=privacy">الخصوصية</a>
    <a href="?page=terms">الشروط</a>
    <a href="?page=contact">تواصل</a>
    <a href="?page=dmca">DMCA</a>
    <a href="https://yassota.com">يسوتا الرئيسية</a>
  </div>
</div>
</body>
</html>
<?php
}
