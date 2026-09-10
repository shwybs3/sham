<?php
define('NARI_ADMIN', true);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
nari_require_login();

function v($val, $default = '') {
    return htmlspecialchars((string)($val ?? $default), ENT_QUOTES, 'UTF-8');
}

$settings = nari_read_json(SETTINGS_FILE);
$csrf = nari_csrf_token();
$saved = $_GET['saved'] ?? '';
$error = $_GET['error'] ?? '';
$count = $_GET['count'] ?? '';

$robotsPath = __DIR__ . '/../robots.txt';
$robotsContent = file_exists($robotsPath) ? file_get_contents($robotsPath) : '';

$sitemapPath = __DIR__ . '/../sitemap.xml';
$sitemapUrlCount = 0;
if (file_exists($sitemapPath)) {
    $sitemapUrlCount = substr_count(file_get_contents($sitemapPath), '<loc>');
}
$sitemapModified = file_exists($sitemapPath) ? date('Y-m-d H:i', filemtime($sitemapPath)) : 'غير موجود';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>الموقع والدومين | لوحة تحكم ناري ستور</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="top-bar">
  <div class="wrap">
    <h1><svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 2c1.5 2 2.5 4.5 2.8 8H9.2c.3-3.5 1.3-6 2.8-8zM9 12h6c-.2 3-1.2 5.5-3 7.7C10.2 17.5 9.2 15 9 12zM6.3 8.5A8 8 0 004 12c0 1.2.3 2.4.8 3.5H8c-.1-1.1-.2-2.3-.2-3.5s.1-2.4.2-3.5H6.3zm11.4 0c.1 1.1.2 2.3.2 3.5s-.1 2.4-.2 3.5h3.2c.5-1.1.8-2.3.8-3.5a8 8 0 00-2.3-3.5h-1.7z"/></svg> الموقع <span>والدومين</span></h1>
    <div style="display:flex; align-items:center; gap:14px;">
      <a href="../sitemap.xml" target="_blank" class="btn ghost small">عرض sitemap.xml ↗</a>
      <a href="logout.php" class="btn ghost small">تسجيل الخروج</a>
    </div>
  </div>
</div>
<?php nari_admin_nav('site'); ?>

<div class="wrap">

  <?php if ($saved === 'site'): ?>
    <div class="success-box">تم حفظ الدومين الأساسي بنجاح.</div>
  <?php elseif ($saved === 'robots'): ?>
    <div class="success-box">تم حفظ ملف robots.txt بنجاح.</div>
  <?php elseif ($saved === 'sitemap'): ?>
    <div class="success-box">تم إعادة بناء sitemap.xml بنجاح — يحتوي الآن <?= (int)$count ?> رابطاً.</div>
  <?php endif; ?>
  <?php if ($error === 'empty_robots'): ?>
    <div class="error-box">لا يمكن حفظ ملف robots.txt فارغاً.</div>
  <?php endif; ?>

  <div class="panel">
    <h2>الدومين الأساسي (Canonical Domain)</h2>
    <p class="hint">
      يُستخدم هذا الرابط في وسوم SEO (canonical، og:url) وفي بناء خريطة الموقع لصفحات <strong>المقالات والصفحات الديناميكية</strong>
      (page.php / article.php / blog.php) فقط. <strong>تنبيه مهم:</strong> صفحات الموقع الثابتة (index.html وباقي ملفات .html)
      يكون هذا الرابط مكتوباً داخلها مباشرة عند إنشائها، فتغييره هنا لا يغيّرها — يلزم تحديثها يدوياً أو عبر مدير الملفات إن انتقلت لدومين آخر فعلاً.
      كذلك: هذا الحقل لا يغيّر أي إعداد DNS أو تسجيل نطاق حقيقي — ذلك يُدار حصراً من لوحة تحكم مزوّد النطاق (Registrar) أو الاستضافة نفسها، وليس من كود الموقع.
    </p>
    <form method="post" action="save.php">
      <input type="hidden" name="csrf" value="<?= v($csrf) ?>">
      <input type="hidden" name="action" value="save_site">
      <div class="field" style="max-width:420px;">
        <label>رابط الموقع الأساسي</label>
        <input type="text" name="site_url" value="<?= v($settings['site_url'] ?? 'https://yassota.com') ?>" placeholder="https://yassota.com" dir="ltr" style="text-align:left;">
      </div>
      <button type="submit" class="btn" style="width:auto; padding:12px 28px;">حفظ الدومين</button>
    </form>
  </div>

  <div class="panel">
    <h2>خريطة الموقع (sitemap.xml)</h2>
    <p class="hint">
      يفحص هذا الزر مجلد الموقع تلقائياً ويبني ملف sitemap.xml من جديد: كل ملفات .html الموجودة فعلياً + كل صفحة ومقال
      "منشور" في نظام المقالات. أعد البناء بعد كل إضافة أو حذف لصفحة .html عبر مدير الملفات، أو بعد نشر مقال جديد.
    </p>
    <div style="display:flex; gap:20px; align-items:center; flex-wrap:wrap; margin-bottom:18px;">
      <span class="badge on">آخر بناء: <?= v($sitemapModified) ?></span>
      <span class="badge on"><?= (int)$sitemapUrlCount ?> رابط داخل الملف الحالي</span>
    </div>
    <form method="post" action="save.php">
      <input type="hidden" name="csrf" value="<?= v($csrf) ?>">
      <input type="hidden" name="action" value="regenerate_sitemap">
      <button type="submit" class="btn" style="width:auto; padding:12px 28px;">إعادة بناء sitemap.xml الآن</button>
    </form>
  </div>

  <div class="panel">
    <h2>ملف robots.txt</h2>
    <p class="hint">تحكّم كامل بما تسمح لمحركات البحث بفهرسته. يجب أن يبقى مجلد /admin/ و /data/ ممنوعين دائماً لأسباب أمنية.</p>
    <form method="post" action="save.php">
      <input type="hidden" name="csrf" value="<?= v($csrf) ?>">
      <input type="hidden" name="action" value="save_robots">
      <textarea name="robots_content" class="code-editor" style="min-height:220px;" spellcheck="false"><?= v($robotsContent) ?></textarea>
      <div style="margin-top:16px;">
        <button type="submit" class="btn" style="width:auto; padding:12px 28px;">حفظ robots.txt</button>
      </div>
    </form>
  </div>

  <div class="panel">
    <h2>ملاحظة حول "إدارة الدومينات"</h2>
    <p class="hint" style="margin-bottom:0;">
      لوحة تحكم موقع (أي موقع، وليس فقط ناري ستور) لا تستطيع فنياً شراء نطاقات جديدة أو تعديل سجلات DNS أو ربط نطاقات إضافية —
      هذه العمليات تحدث دائماً من طرف مسجّل النطاق (Registrar) أو لوحة تحكم الاستضافة (مثل cPanel في Infinity Free)، خارج كود الموقع تماماً،
      لأنها تتعلق بالبنية التحتية للإنترنت نفسها (DNS) لا بملفات الموقع. ما يمكن للوحة هذه فعله فعلياً — وهو ما بُني أعلاه — هو ضبط
      الرابط الذي يظهر في وسوم SEO وخريطة الموقع، وإدارة كل ملفات الموقع ومحتواه بالكامل.
    </p>
  </div>

</div>

<footer class="admin-foot">لوحة تحكم ناري ستور — للاستخدام الداخلي فقط، غير مفهرسة في محركات البحث.</footer>
</body>
</html>
