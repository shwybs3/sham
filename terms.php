<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$contactEmail = get_cfg($pdo, 'contact_email') ?: 'contact@' . parse_url(SITE_URL, PHP_URL_HOST);
$seoTitle = 'شروط الاستخدام — Tenzil';
$metaDesc = 'شروط استخدام منصة Tenzil لتحميل التطبيقات والألعاب.';
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
  <link rel="canonical" href="<?= h(url('terms.php')) ?>">
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
    <a href="/" style="color:var(--cyan)">الرئيسية</a><span>/</span><span>شروط الاستخدام</span>
  </nav>

  <div class="section-head reveal"><span class="section-title">شروط الاستخدام</span></div>

  <div class="section-box reveal" style="color:var(--muted);font-size:14px;line-height:1.9">
    <p>يرجى قراءة هذه الشروط بعناية قبل استخدام موقع Tenzil. باستخدامك للموقع فإنك توافق على الالتزام بهذه الشروط بالكامل.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">١. قبول الشروط</h3>
    <p>يُعد دخولك واستخدامك لهذا الموقع موافقة صريحة منك على هذه الشروط وأي تحديثات لاحقة عليها. إن لم توافق على أي جزء منها، يرجى التوقف عن استخدام الموقع.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">٢. طبيعة الخدمة</h3>
    <p>Tenzil منصة عربية تعمل كدليل لعرض معلومات عن تطبيقات وألعاب أندرويد (الاسم، الوصف، الإصدار، الصور) وتوجيه الزوار نحو روابط تحميل، سواء كانت رسمية من مطوّري التطبيقات أو من مصادر خارجية موثوقة. الموقع ليس الجهة المطوّرة لهذه التطبيقات ما لم يُذكر ذلك صراحةً.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">٣. الاستخدام المسموح به</h3>
    <p>يُسمح باستخدام الموقع للأغراض الشخصية وغير التجارية فقط. يُمنع محاولة اختراق الموقع، أو استخراج بياناته بشكل آلي مكثف (Scraping) دون إذن، أو استخدامه بأي شكل يضر بالخدمة أو بمستخدمين آخرين.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">٤. إخلاء المسؤولية</h3>
    <p>التطبيقات المعروضة قد تكون من تطوير أطراف ثالثة. نبذل جهداً معقولاً للتحقق من دقة المعلومات المعروضة، لكننا لا نضمن خلوّ أي تطبيق من الأخطاء، ولا نتحمل مسؤولية أي ضرر ناتج عن تحميل أو استخدام تطبيق تم الوصول إليه عبر الموقع. يقع على عاتق المستخدم التحقق من ملاءمة أي تطبيق قبل تثبيته.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">٥. حقوق الملكية الفكرية</h3>
    <p>جميع أسماء التطبيقات والعلامات التجارية والشعارات الظاهرة في الموقع مملوكة لأصحابها الأصليين. يحتفظ Tenzil بحقوق التصميم والمحتوى الأصلي الخاص بالموقع نفسه (النصوص التعريفية، التصميم، الشعار).</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">٦. حدود المسؤولية</h3>
    <p>يُقدَّم الموقع "كما هو" دون أي ضمانات صريحة أو ضمنية. لن يكون Tenzil مسؤولاً عن أي أضرار مباشرة أو غير مباشرة تنتج عن استخدام الموقع أو التعذر عن استخدامه.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">٧. التعديلات على الشروط</h3>
    <p>نحتفظ بالحق في تعديل هذه الشروط في أي وقت. يسري أي تعديل فور نشره على هذه الصفحة، ويُعد استمرارك في استخدام الموقع موافقة على النسخة المحدثة.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">٨. التواصل معنا</h3>
    <p>لأي استفسار بخصوص هذه الشروط: <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--cyan)"><?= h($contactEmail) ?></a></p>
  </div>
</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
