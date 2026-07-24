<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$faqs = [
    'عن Apkzilo' => [
        ['q' => 'ما هو موقع Apkzilo؟',
         'a' => 'Apkzilo هو دليل تحريري عربي مستقل متخصص في مراجعة وتقييم تطبيقات وألعاب أندرويد. يقدم الموقع محتوى تحريرياً دقيقاً ومحايداً يساعد المستخدمين العرب على اكتشاف أفضل التطبيقات واتخاذ قرار التحميل بثقة.'],
        ['q' => 'هل Apkzilo موقع رسمي لـ Google Play أو أي شركة تطبيقات؟',
         'a' => 'لا، Apkzilo موقع تحريري مستقل تماماً ولا ينتمي لأي متجر تطبيقات أو شركة برمجيات. نحن نراجع التطبيقات بشكل موضوعي ونضع روابط تحميل من المصادر الرسمية مثل Google Play أو من المطورين مباشرة.'],
        ['q' => 'كيف يعمل فريق Apkzilo؟',
         'a' => 'يضم فريق Apkzilo محررين متخصصين في عالم التطبيقات يراجعون كل تطبيق قبل نشره، يتحققون من جودة الرابط وأمانه، ويكتبون وصفاً تفصيلياً بالعربية يغطي المميزات والعيوب واستخدامات التطبيق.'],
        ['q' => 'هل الموقع مجاني للاستخدام؟',
         'a' => 'نعم، Apkzilo مجاني تماماً للمستخدمين. يعتمد الموقع على الإعلانات لتغطية تكاليف التشغيل. لا توجد رسوم اشتراك أو مدفوعات مخفية من أي نوع.'],
    ],
    'التحميل والروابط' => [
        ['q' => 'كيف أحمّل التطبيق من Apkzilo؟',
         'a' => 'انقر على زر "تحميل" في صفحة التطبيق، ستُنقل مباشرة إلى صفحة التحميل حيث يمكنك الاختيار بين رابط Google Play الرسمي أو الرابط المباشر إن وُجد. نوصي دائماً باستخدام Google Play للحصول على أحدث إصدار وأمانه.'],
        ['q' => 'هل روابط التحميل آمنة؟',
         'a' => 'نعم، جميع روابط التحميل على Apkzilo تُفحص قبل نشرها. نعتمد على روابط Google Play الرسمية كمصدر أساسي، وعند توفر ملفات APK مباشرة نتحقق منها بأدوات فحص الفيروسات ونعرض نتيجة الفحص بجانب الرابط.'],
        ['q' => 'لماذا رابط التحميل لا يعمل؟',
         'a' => 'قد يتغير رابط التطبيق على Google Play أو قد يحذف المطور التطبيق. إذا واجهت رابطاً معطلاً يرجى إبلاغنا عبر صفحة الاتصال أو التعليقات وسنحدثه خلال 24-48 ساعة.'],
        ['q' => 'هل يمكنني تحميل تطبيقات لأجهزة iOS من Apkzilo؟',
         'a' => 'لا، يتخصص Apkzilo حالياً في تطبيقات أندرويد فقط. جميع التطبيقات المدرجة مخصصة لأجهزة Android ونظام التشغيل Android.'],
        ['q' => 'ما الفرق بين الرابط الرسمي ورابط APK المباشر؟',
         'a' => 'رابط Google Play الرسمي: يحوّلك لمتجر Google حيث تتم عملية التحميل والتحديث تلقائياً وبأعلى مستوى أمان. رابط APK المباشر: ملف التطبيق نفسه للتحميل المباشر وقد يكون مفيداً في غياب Play Store. نوصي دائماً بالمصدر الرسمي.'],
    ],
    'التقييمات والمراجعات' => [
        ['q' => 'كيف تُحسب تقييمات التطبيقات على Apkzilo؟',
         'a' => 'تقييمات Apkzilo مزيج من تقييم فريق التحرير بناءً على تجربة فعلية مع التطبيق، وتقييمات المستخدمين المقدَّمة عبر قسم التعليقات. يظهر التقييم الإجمالي من 5 نجوم في أعلى كل صفحة تطبيق.'],
        ['q' => 'كيف أضيف تقييمي لتطبيق ما؟',
         'a' => 'افتح صفحة التطبيق، انتقل لقسم "التعليقات والتقييمات" في أسفل الصفحة، اختر عدد النجوم واكتب رأيك ثم أرسل. تخضع التعليقات لمراجعة قبل نشرها للتحقق من ملاءمتها.'],
        ['q' => 'هل التعليقات على Apkzilo حقيقية؟',
         'a' => 'نعم، نراجع كل تعليق يدوياً قبل نشره للتأكد من أنه تعليق حقيقي وليس محتوى ترويجي أو مسيء. نرفض التعليقات المصطنعة والعروض التجارية ونحافظ على موضوعية المحتوى.'],
    ],
    'المحتوى والسياسات' => [
        ['q' => 'كيف أطلب إضافة تطبيق لـ Apkzilo؟',
         'a' => 'يمكنك إرسال اقتراح إضافة تطبيق عبر صفحة "اتصل بنا"، اذكر اسم التطبيق ورابط Google Play وسبب اقتراحك. يراجع الفريق الطلب ويقرر إضافته وفق معاييرنا التحريرية.'],
        ['q' => 'كيف أبلغ عن محتوى غير لائق أو خاطئ؟',
         'a' => 'إذا وجدت معلومات غير دقيقة أو محتوى مخالفاً في أي صفحة، أرسل بلاغاً عبر نموذج الاتصال مع ذكر رابط الصفحة ونوع المشكلة. نستجيب لجميع البلاغات خلال 48 ساعة.'],
        ['q' => 'هل يمكنني طلب إزالة معلومات تطبيقي؟',
         'a' => 'إذا كنت مطور التطبيق وتريد تحديث المعلومات أو إزالة صفحة التطبيق، تواصل معنا عبر صفحة الاتصال مع إثبات ملكيتك للتطبيق (مثل رابط صفحة المطور على Play Store) وسنتعامل مع طلبك فوراً.'],
        ['q' => 'ما هو برنامج الشراكة الإعلانية على Apkzilo؟',
         'a' => 'يستخدم Apkzilo برنامج Google AdSense لعرض إعلانات مناسبة. هذه الإعلانات تلقائية ولا تؤثر على موضوعية مراجعاتنا. للاستفسارات الإعلانية الأخرى تواصل معنا مباشرة.'],
    ],
    'الخصوصية والبيانات' => [
        ['q' => 'هل يحتاج Apkzilo لتسجيل حساب؟',
         'a' => 'لا، يمكنك تصفح جميع محتويات Apkzilo ومراجعة التطبيقات والتحميل بدون إنشاء حساب. التعليقات تتطلب فقط اسماً مستعاراً دون بريد إلكتروني أو حساب.'],
        ['q' => 'ما البيانات التي يجمعها Apkzilo؟',
         'a' => 'يجمع Apkzilo بيانات مجهولة الهوية عن استخدام الموقع (الصفحات المزارة، وقت الزيارة) عبر ملفات تعريف الارتباط (Cookies) لتحسين تجربة الاستخدام وعرض إعلانات مناسبة. لا نجمع بيانات شخصية دون موافقتك. اقرأ <a href="' . h(url('privacy-policy')) . '">سياسة الخصوصية</a> للتفاصيل الكاملة.'],
        ['q' => 'كيف يمكنني حذف بياناتي من Apkzilo؟',
         'a' => 'لحذف أي تعليق أضفته أو بيانات مرتبطة بك، تواصل معنا عبر البريد الإلكتروني أو نموذج الاتصال مع ذكر التفاصيل. نلتزم بتلبية طلبات الحذف خلال 30 يوماً وفق <a href="' . h(url('privacy-policy')) . '">سياسة الخصوصية</a>.'],
    ],
];

// FAQPage Schema.org
$faqSchema = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
foreach ($faqs as $cat => $items) {
    foreach ($items as $item) {
        $faqSchema['mainEntity'][] = [
            '@type' => 'Question',
            'name'  => $item['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($item['a'])],
        ];
    }
}

$seoTitle = 'الأسئلة الشائعة — Apkzilo';
$metaDesc = 'إجابات شاملة على أكثر الأسئلة شيوعاً حول موقع Apkzilo لمراجعة تطبيقات أندرويد — التحميل والأمان والمحتوى والخصوصية.';
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
  <link rel="canonical" href="<?= h(url('faq')) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <meta property="og:site_name" content="Apkzilo">
  <script type="application/ld+json"><?= json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189"
     crossorigin="anonymous"></script>
</head>
<body>

<?php render_site_header('', 'about'); ?>

<div class="page-wrap">
<?php render_site_sidebar($pdo); ?>

<main class="main-content">

  <?php render_breadcrumbs([
      ['label' => 'الرئيسية', 'url' => url('')],
      ['label' => 'الأسئلة الشائعة'],
  ]); ?>

  <!-- Hero -->
  <div style="background:linear-gradient(135deg,rgba(37,99,235,.06),rgba(124,58,237,.06));border:1px solid var(--border-p);border-radius:var(--radius-lg);padding:32px 28px;text-align:center;margin-bottom:24px" class="reveal">
    <div style="font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--cyan);margin-bottom:8px">مركز المساعدة</div>
    <h1 style="font-family:var(--f-head);font-size:clamp(20px,4vw,30px);font-weight:900;margin-bottom:10px;background:linear-gradient(135deg,var(--white),var(--cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent">
      الأسئلة الشائعة
    </h1>
    <p style="color:var(--muted);font-size:14px;max-width:500px;margin:0 auto">
      إجابات شاملة على كل ما يدور في ذهنك حول Apkzilo — التحميل، الأمان، المراجعات، والخصوصية.
    </p>
  </div>

  <!-- Ad Zone Top -->
  <div class="ad-zone"><?= ad_slot() ?></div>

  <!-- FAQ Sections -->
  <?php $sectionNum = 0; foreach ($faqs as $catLabel => $items): $sectionNum++; ?>
  <div style="margin-bottom:28px" class="reveal">
    <div class="section-head" style="margin-bottom:14px">
      <span class="section-title"><?= h($catLabel) ?></span>
      <span style="font-size:11px;color:var(--muted)"><?= count($items) ?> سؤال</span>
    </div>
    <div class="faq-list">
      <?php foreach ($items as $fi => $item): ?>
      <details class="faq-item" style="animation-delay:<?= ($fi * 0.06) ?>s">
        <summary class="faq-q">
          <span><?= h($item['q']) ?></span>
          <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
        </summary>
        <div class="faq-a"><?= $item['a'] ?></div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
  <?php if ($sectionNum === 2): ?>
  <!-- Ad Zone Mid -->
  <div class="ad-zone"><?= ad_slot() ?></div>
  <?php endif; ?>
  <?php endforeach; ?>

  <!-- Still have a question? -->
  <?= partial_wave() ?>
  <div class="newsletter-box reveal" style="margin-top:8px;text-align:center">
    <div style="font-size:36px;margin-bottom:10px">💬</div>
    <h2 style="font-family:var(--f-head);font-size:20px;font-weight:800;margin-bottom:8px">لم تجد إجابتك؟</h2>
    <p style="color:var(--muted);font-size:14px;max-width:420px;margin:0 auto 18px">
      فريق Apkzilo جاهز للإجابة على أي استفسار — تواصل معنا مباشرة وسنرد خلال 24 ساعة.
    </p>
    <a href="<?= h(url('contact')) ?>" class="btn-primary" style="display:inline-flex">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
      اتصل بنا
    </a>
  </div>

</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
