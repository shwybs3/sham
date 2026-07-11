<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$contactEmail = get_cfg($pdo, 'contact_email') ?: 'contact@' . parse_url(SITE_URL, PHP_URL_HOST);
$seoTitle = 'سياسة ملفات تعريف الارتباط — yassota';
$metaDesc = 'كيف يستخدم موقع yassota ملفات تعريف الارتباط (الكوكيز) وكيفية التحكم بها.';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($seoTitle) ?></title>
  <meta name="description" content="<?= h($metaDesc) ?>">
  <link rel="canonical" href="<?= h(url('cookie-policy.php')) ?>">
  <link rel="stylesheet" href="assets/css/main.css">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189"
     crossorigin="anonymous"></script>
</head>
<body>

<?php render_site_header(); ?>

<div class="page-wrap">
<?php render_site_sidebar($pdo); ?>

<main class="main-content">
  <nav style="font-size:12px;color:var(--muted);margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <a href="index.php" style="color:var(--cyan)">الرئيسية</a><span>/</span><span>سياسة ملفات تعريف الارتباط</span>
  </nav>

  <div class="section-head reveal"><span class="section-title">سياسة ملفات تعريف الارتباط (Cookies)</span></div>

  <div class="section-box reveal" style="color:var(--muted);font-size:14px;line-height:1.9">
    <p>ملفات تعريف الارتباط (Cookies) هي ملفات نصية صغيرة يخزّنها متصفحك عند زيارة موقع إلكتروني، وتُستخدم لتذكّر تفضيلاتك أو نشاطك أثناء التصفح.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">أنواع الكوكيز التي نستخدمها</h3>
    <p><strong style="color:var(--white)">كوكيز أساسية (ضرورية):</strong> مثل كوكي الجلسة (PHPSESSID) المستخدم فقط داخل لوحة تحكم الإدارة لتسجيل الدخول، وكوكيز CSRF لحماية النماذج من الهجمات. هذه الكوكيز ضرورية لعمل الموقع ولا تُستخدم لتتبع الزوار العاديين.</p>
    <p><strong style="color:var(--white)">كوكيز الإعلانات:</strong> تضعها شبكات إعلانية خارجية مثل Google AdSense وMoneyTag لعرض إعلانات، وقد تُستخدم لعرض إعلانات مبنية على اهتماماتك عبر مواقع مختلفة.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">كيفية التحكم بالكوكيز</h3>
    <p>يمكنك حذف أو حظر الكوكيز من إعدادات متصفحك في أي وقت؛ لاحظ أن تعطيل بعض الكوكيز قد يؤثر على بعض وظائف الموقع. للتحكم في إعلانات Google المخصصة تحديداً، تفضّل بزيارة <a href="https://adssettings.google.com" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">إعدادات إعلانات Google</a>. يمكنك أيضاً استخدام إضافات المتصفح لمنع التتبع الإعلاني إن رغبت.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">مزيد من المعلومات</h3>
    <p>لأي استفسار حول استخدامنا لملفات تعريف الارتباط، راجع <a href="privacy-policy.php" style="color:var(--cyan)">سياسة الخصوصية</a> أو تواصل معنا على: <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--cyan)"><?= h($contactEmail) ?></a></p>
  </div>
</main>
</div>

<?php render_site_footer(); ?>
<script src="assets/js/main.js"></script>
</body>
</html>
