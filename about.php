<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$totalApps = (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn();
$totalCats = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

$seoTitle = 'من نحن — تعرّف على منصة yassota';
$metaDesc = 'تعرّف على yassota: منصة عربية لتحميل أفضل تطبيقات وألعاب أندرويد، رسالتنا، وكيف نختار ونحدّث المحتوى.';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($seoTitle) ?></title>
  <meta name="description" content="<?= h($metaDesc) ?>">
  <link rel="canonical" href="<?= h(url('about.php')) ?>">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
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
    <a href="index.php" style="color:var(--cyan)">الرئيسية</a><span>/</span><span>من نحن</span>
  </nav>

  <div class="section-head reveal"><span class="section-title">من نحن</span></div>

  <div class="section-box reveal" style="color:var(--muted);font-size:14px;line-height:1.9">
    <h2 style="font-family:var(--f-head);font-size:20px;font-weight:900;margin-bottom:12px;background:linear-gradient(135deg,var(--white),var(--cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent">
      yassota — منصتك العربية للتطبيقات والألعاب
    </h2>

    <p>انطلقت yassota من فكرة بسيطة: أن يجد المستخدم العربي مكاناً واحداً موثوقاً وسريعاً لاكتشاف وتحميل أفضل تطبيقات وألعاب أندرويد، دون الحاجة للتنقل بين مواقع متفرقة أو التعامل مع روابط غير آمنة. نؤمن أن تجربة التحميل يجب أن تكون بسيطة، شفافة، وخالية من التعقيدات غير الضرورية.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">ماذا نقدّم</h3>
    <p>نوفّر أدلة تفصيلية لكل تطبيق تشمل: الوصف الكامل، الإصدار الحالي وحجم الملف، متطلبات التشغيل، صور حقيقية من واجهة الاستخدام، أبرز المميزات، الإيجابيات والسلبيات، خطوات التثبيت، والأسئلة الشائعة. كما نوفّر تصنيفات مرتبة (تطبيقات، ألعاب، أدوات، إنتاجية، تواصل اجتماعي، تعديل وتصميم) وصفحات مخصصة لكل مطوّر، بالإضافة إلى قوائم "الأكثر تحميلاً" و"الأكثر زيارة" و"آخر التحديثات" لتسهيل الاكتشاف.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">كيف نُحدّث المحتوى</h3>
    <p>يعتمد فريقنا على مزيج من الأدوات الآلية المدعومة بالذكاء الاصطناعي لصياغة الأوصاف وتحديث بيانات SEO بسرعة، إلى جانب مراجعة وتعديل مستمر من قِبل القائمين على الموقع لضمان دقة المعلومات الأساسية مثل روابط التحميل وأرقام الإصدارات. نعمل باستمرار على تحديث قاعدة التطبيقات لتبقى المعلومات المعروضة قريبة قدر الإمكان من أحدث الإصدارات المتوفرة.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">التزامنا تجاهك</h3>
    <p>نحرص على أن تكون صفحات التحميل واضحة ومباشرة، وأن نُبيّن بصراحة عندما لا يتوفر رابط تحميل لتطبيق معيّن بدلاً من تضليل الزائر. كما نلتزم بعدم استخدام أي أساليب تحميل قسري أو نوافذ منبثقة مزعجة تعطّل تجربة التصفح.</p>

    <h3 style="color:var(--white);margin:22px 0 8px;font-size:16px">من يقف خلف yassota</h3>
    <p>yassota مشروع مستقل يديره فريق صغير شغوف بعالم التطبيقات والتقنية، يهدف لخدمة المستخدم العربي بمحتوى عالي الجودة ومحدّث باستمرار. نرحّب دائماً باقتراحاتكم وملاحظاتكم عبر <a href="contact.php" style="color:var(--cyan)">صفحة اتصل بنا</a>.</p>

    <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:24px">
      <div style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:12px;padding:16px;display:flex;align-items:center;gap:16px;flex:1;min-width:180px">
        <span style="font-family:var(--f-mono);font-size:28px;font-weight:600;color:var(--cyan)"><?= number_format($totalApps) ?></span>
        <span style="color:var(--muted);font-size:14px">تطبيق متاح</span>
      </div>
      <div style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:12px;padding:16px;display:flex;align-items:center;gap:16px;flex:1;min-width:180px">
        <span style="font-family:var(--f-mono);font-size:28px;font-weight:600;color:var(--cyan)"><?= number_format($totalCats) ?></span>
        <span style="color:var(--muted);font-size:14px">تصنيف</span>
      </div>
    </div>

    <p style="margin-top:24px">لمزيد من الشفافية، راجع أيضاً <a href="privacy-policy.php" style="color:var(--cyan)">سياسة الخصوصية</a>، <a href="terms.php" style="color:var(--cyan)">شروط الاستخدام</a>، و<a href="dmca.php" style="color:var(--cyan)">سياسة DMCA</a>.</p>
  </div>
</main>
</div>

<?php render_site_footer(); ?>
<script src="assets/js/main.js"></script>
</body>
</html>
