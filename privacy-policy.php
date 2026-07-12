<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$contactEmail = get_cfg($pdo, 'contact_email') ?: 'contact@' . parse_url(SITE_URL, PHP_URL_HOST);
$seoTitle = 'سياسة الخصوصية — yassota';
$metaDesc = 'سياسة الخصوصية الخاصة بمنصة yassota: البيانات التي نجمعها، كيفية استخدامها، ملفات تعريف الارتباط، والإعلانات.';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($seoTitle) ?></title>
  <meta name="description" content="<?= h($metaDesc) ?>">
  <link rel="canonical" href="<?= h(url('privacy-policy.php')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189"
     crossorigin="anonymous"></script>
</head>
<body>

<?php render_site_header(); ?>

<div class="page-wrap">
<?php render_site_sidebar($pdo); ?>

<main class="main-content">
  <nav style="font-size:12px;color:var(--muted);margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <a href="/" style="color:var(--cyan)">الرئيسية</a><span>/</span><span>سياسة الخصوصية</span>
  </nav>

  <div class="section-head reveal"><span class="section-title">سياسة الخصوصية</span></div>

  <div class="section-box reveal" style="color:var(--muted);font-size:14px;line-height:1.9">
    <p>تحترم منصة yassota خصوصية زوارها، وتوضح هذه السياسة نوع البيانات التي قد نجمعها أثناء استخدامك للموقع، وكيفية استخدامها وحمايتها. باستخدامك لموقعنا فإنك توافق على الممارسات الموضحة في هذه السياسة.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">١. البيانات التي نجمعها</h3>
    <p>لا نطلب من الزوار إنشاء حساب أو تقديم بيانات شخصية لتصفح الموقع أو تحميل التطبيقات. قد نجمع بشكل تلقائي بيانات تقنية غير شخصية مثل: نوع المتصفح والجهاز، عنوان IP التقريبي، الصفحات التي تمت زيارتها، ومدة الزيارة، وذلك لأغراض إحصائية وتحسين الأداء فقط.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">٢. ملفات تعريف الارتباط (Cookies)</h3>
    <p>يستخدم الموقع ملفات تعريف ارتباط أساسية لعمل الجلسة، بالإضافة إلى ملفات تعريف ارتباط من Google AdSense لعرض إعلانات ذات صلة. لمزيد من التفاصيل راجع <a href="<?= h(url('cookie-policy')) ?>" style="color:var(--cyan)">سياسة ملفات تعريف الارتباط</a>.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">٣. الإعلانات وأطراف ثالثة</h3>
    <p>نستخدم شبكة Google AdSense الإعلانية، والتي قد تستخدم ملفات تعريف الارتباط وتقنيات مشابهة لعرض إعلانات مبنية على زياراتك لهذا الموقع ومواقع أخرى. يمكنك التحكم بإعلانات Google المخصصة من خلال <a href="https://adssettings.google.com" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">إعدادات إعلانات Google</a>.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">٤. روابط التحميل الخارجية</h3>
    <p>yassota دليل لعرض معلومات عن التطبيقات، وقد تُوجّه روابط التحميل إلى مصادر ومتاجر خارجية. لا نتحمل مسؤولية ممارسات الخصوصية لتلك المواقع الخارجية، وننصح بمراجعة سياساتها الخاصة قبل التحميل منها.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">٥. أمان البيانات</h3>
    <p>نتخذ إجراءات معقولة لحماية أي بيانات تقنية يتم جمعها من الوصول أو الاستخدام أو الإفصاح غير المصرح به، لكن لا يمكن ضمان أمان مطلق لأي نقل بيانات عبر الإنترنت.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">٦. حقوقك</h3>
    <p>يحق لك طلب معرفة نوع البيانات التقنية المرتبطة بزيارتك، أو طلب حذفها إن أمكن ذلك تقنياً، عبر التواصل معنا على البريد الموضح أدناه.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">٧. التعديلات على هذه السياسة</h3>
    <p>قد نقوم بتحديث هذه السياسة من وقت لآخر. سيتم نشر أي تعديلات على هذه الصفحة، ويعني استمرار استخدامك للموقع بعد التعديل موافقتك على النسخة المحدثة.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">٨. التواصل معنا</h3>
    <p>لأي استفسار بخصوص هذه السياسة، يمكنك مراسلتنا على: <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--cyan)"><?= h($contactEmail) ?></a> أو عبر <a href="<?= h(url('contact')) ?>" style="color:var(--cyan)">صفحة اتصل بنا</a>.</p>
  </div>
</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
