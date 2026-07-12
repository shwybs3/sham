<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$contactEmail = get_cfg($pdo, 'contact_email') ?: 'contact@' . parse_url(SITE_URL, PHP_URL_HOST);
$seoTitle = 'DMCA — سياسة حقوق النشر — yassota';
$metaDesc = 'كيفية تقديم بلاغ إزالة محتوى بموجب DMCA على منصة yassota.';
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
  <link rel="canonical" href="<?= h(url('dmca.php')) ?>">
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
    <a href="/" style="color:var(--cyan)">الرئيسية</a><span>/</span><span>DMCA</span>
  </nav>

  <div class="section-head reveal"><span class="section-title">سياسة DMCA وحقوق النشر</span></div>

  <div class="section-box reveal" style="color:var(--muted);font-size:14px;line-height:1.9">
    <p>يحترم موقع yassota حقوق الملكية الفكرية للغير، ويستجيب لبلاغات إزالة المحتوى المخالف بموجب قانون الألفية الرقمية لحقوق النشر (DMCA) أو ما يعادله من قوانين محلية. الموقع دليل معلوماتي يعرض بيانات عن تطبيقات ويوجّه لروابط تحميل خارجية في الغالب، ولا يستضيف بنفسه محتوى محمياً بحقوق نشر عمداً.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">تقديم بلاغ إزالة محتوى</h3>
    <p>إذا كنت تعتقد أن محتوى منشوراً على هذا الموقع ينتهك حقوق الملكية الفكرية الخاصة بك، يرجى إرسال إشعار مكتوب يتضمن العناصر التالية:</p>
    <ol style="padding-inline-start:22px;display:flex;flex-direction:column;gap:8px;margin-top:10px">
      <li>توقيع (إلكتروني مقبول) لمالك الحق أو من يمثله قانونياً.</li>
      <li>تحديد واضح للعمل المحمي بحقوق النشر الذي تدّعي انتهاكه.</li>
      <li>الرابط (URL) المباشر للصفحة المخالفة على yassota.</li>
      <li>معلومات تواصل كافية: الاسم، البريد الإلكتروني، ورقم الهاتف إن أمكن.</li>
      <li>إفادة بحسن نية بأن استخدام المحتوى المذكور غير مصرّح به من مالك الحق أو وكيله أو القانون.</li>
      <li>إفادة، تحت طائلة المسؤولية القانونية، بأن المعلومات الواردة في البلاغ دقيقة وأنك مالك الحق أو مخوّل بالتصرف نيابة عنه.</li>
    </ol>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">أين تُرسل البلاغات</h3>
    <p>أرسل بلاغك إلى: <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--cyan)"><?= h($contactEmail) ?></a> مع كتابة "DMCA Takedown Request" في عنوان الرسالة.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">مدة المعالجة</h3>
    <p>نلتزم بمراجعة البلاغات الجادة والمكتملة العناصر والرد عليها خلال مدة معقولة، وإزالة أو تعطيل الوصول للمحتوى المخالف إذا ثبتت صحة البلاغ.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">بلاغ مضاد (Counter-Notice)</h3>
    <p>إذا تمت إزالة محتوى تعتقد أنه أُزيل خطأً أو بشكل غير مبرر، يمكنك إرسال بلاغ مضاد إلى نفس البريد أعلاه يتضمن هويتك، تحديد المحتوى الذي أُزيل ورابطه السابق، وإفادة بحسن نية بأن الإزالة كانت نتيجة خطأ أو تحديد خاطئ للمحتوى.</p>
  </div>
</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
