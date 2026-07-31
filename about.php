<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$totalApps  = (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn();
$totalCats  = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalBlog  = (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status='published'")->fetchColumn();
$contactEmail = get_cfg($pdo, 'contact_email') ?: 'contact@' . (parse_url(SITE_URL, PHP_URL_HOST) ?: 'yassota.com');

$seoTitle = 'من نحن — yassota دليل تحريري مستقل لتطبيقات أندرويد';
$metaDesc = 'تعرّف على yassota: دليل تحريري عربي مستقل لمراجعة تطبيقات وألعاب أندرويد. رسالتنا، معايير المراجعة، وكيف نختار التطبيقات ونحدّث محتوانا يومياً.';
?>
<!DOCTYPE html>
<html lang="<?= defined('UI_LANG') ? UI_LANG : 'ar' ?>" dir="<?= defined('UI_DIR') ? UI_DIR : 'rtl' ?>">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($seoTitle) ?></title>
  <meta name="description" content="<?= h($metaDesc) ?>">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="<?= h(url('about')) ?>">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <meta property="og:type" content="website">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189"
     crossorigin="anonymous"></script>
</head>
<body>

<?php render_site_header(); ?>

<div class="page-wrap fw">

<main class="main-content">

  <nav style="font-size:12px;color:var(--muted);margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <a href="<?= h(url('')) ?>" style="color:var(--cyan)">الرئيسية</a><span>/</span><span>من نحن</span>
  </nav>

  <!-- ── Header ── -->
  <div class="section-head reveal"><span class="section-title">من نحن</span></div>

  <!-- ── Mission statement ── -->
  <div class="section-box reveal" style="background:linear-gradient(135deg,rgba(6,182,212,.07),rgba(124,58,237,.05));border-color:rgba(6,182,212,.2);margin-bottom:16px">
    <div style="font-size:11px;font-weight:700;letter-spacing:2px;color:var(--cyan);text-transform:uppercase;margin-bottom:12px">رسالتنا</div>
    <h1 style="font-family:var(--f-head);font-size:22px;font-weight:900;color:var(--white);line-height:1.4;margin-bottom:12px">
      دليلك التحريري العربي المستقل لعالم تطبيقات Android
    </h1>
    <p style="color:var(--muted);font-size:14px;line-height:1.85;margin:0">
      yassota منصة تحريرية مستقلة تأسّست عام 2024 بهدف واحد: تمكين المستخدم العربي من اتخاذ قرارات مدروسة وواثقة عند اختيار تطبيقات هاتفه. نؤمن أن لكل مستخدم الحق في الحصول على معلومات دقيقة وصادقة — لا إعلانات مقنّعة، لا توصيات مدفوعة، لا مبالغات تسويقية.
    </p>
  </div>

  <!-- ── Stats ── -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px" class="reveal">
    <?php foreach ([[$totalApps,'تطبيق مراجَع','smartphone'],[$totalCats,'تصنيف','folder'],[$totalBlog,'مقالة','pen']] as [$n,$l,$ic]): ?>
    <div style="background:var(--navy-700);border:1px solid var(--border-c);border-radius:var(--radius-lg);padding:20px;text-align:center">
      <div style="color:var(--cyan);margin-bottom:8px;display:flex;justify-content:center"><?= partial_icon($ic, 24) ?></div>
      <div style="font-family:var(--f-mono);font-size:28px;font-weight:700;color:var(--cyan)"><?= number_format($n) ?></div>
      <div style="font-size:13px;color:var(--muted);margin-top:4px"><?= $l ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── Main content ── -->
  <div class="section-box reveal" style="color:var(--muted);font-size:14px;line-height:1.9">

    <h2 style="color:var(--white);font-size:17px;font-weight:800;margin:0 0 10px">ماذا نقدّم بالضبط؟</h2>
    <p>
      نكتب مراجعات تفصيلية تشمل: وصفاً احترافياً لكل تطبيق، تقييم الإيجابيات والسلبيات، شرح المميزات الرئيسية، مقارنة بالبدائل، متطلبات التشغيل، ومعلومات الإصدار والحجم والمطوّر.
      كما نتحقق من كل رابط تحميل ونُشير بوضوح إلى مصدره — سواء Google Play أو موقع المطوّر الرسمي — حرصاً على أمانك.
    </p>

    <div style="background:rgba(6,182,212,.07);border:1px solid rgba(6,182,212,.15);border-radius:12px;padding:18px 20px;margin:20px 0">
      <div style="font-weight:700;color:var(--white);font-size:14px;margin-bottom:10px;display:flex;align-items:center;gap:7px"><?= partial_icon('folder', 16) ?> الأقسام التي نغطيها</div>
      <ul style="margin:0;padding-right:18px;display:grid;grid-template-columns:1fr 1fr;gap:6px">
        <?php foreach (['تطبيقات الإنتاجية والأعمال','ألعاب أندرويد بجميع أنواعها','تطبيقات التواصل الاجتماعي','أدوات التصوير والتصميم','تطبيقات التعليم والتطوير','برامج الحماية والأمان'] as $item): ?>
        <li style="font-size:13px"><?= h($item) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <h2 style="color:var(--white);font-size:17px;font-weight:800;margin:22px 0 10px">معايير مراجعة التطبيقات</h2>
    <p>لا ننشر أي تطبيق دون المرور بمنهجية تقييم واضحة:</p>
    <div style="display:grid;gap:10px;margin:14px 0">
      <?php foreach ([
        ['1','الأصالة والجودة','نتحقق أن التطبيق موجود في متاجر رسمية أو صادر عن مطوّر موثوق، ونتجنب المحتوى المقرصن أو المُعدَّل بدون إذن.'],
        ['2','الدقة المعلوماتية','كل بيانات التطبيق (إصدار، حجم، مطوّر) تُتحقق من المصدر الرسمي قبل النشر.'],
        ['3','جودة المحتوى التحريري','الوصف والمراجعة يُكتبان بأسلوب مفيد وواضح، لا مجرد ترجمة آلية لنص المتجر.'],
        ['4','التحديث الدوري','نُراجع بيانات كل تطبيق عند صدور تحديث ونضع علامة "محدّث" لإعلام القرّاء.'],
      ] as [$num,$title,$desc]): ?>
      <div style="display:flex;gap:14px;background:var(--navy-600);border:1px solid var(--border-c);border-radius:10px;padding:14px 16px">
        <div style="flex-shrink:0;width:28px;height:28px;border-radius:50%;background:rgba(6,182,212,.15);border:1px solid rgba(6,182,212,.3);color:var(--cyan);font-family:var(--f-mono);font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center"><?= $num ?></div>
        <div>
          <div style="font-weight:700;color:var(--white);font-size:13px;margin-bottom:4px"><?= h($title) ?></div>
          <p style="margin:0;font-size:12px;line-height:1.7"><?= h($desc) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <h2 style="color:var(--white);font-size:17px;font-weight:800;margin:22px 0 10px">كيف نُحدّث المحتوى</h2>
    <p>
      يعمل فريقنا يومياً على رصد التحديثات الجديدة للتطبيقات المنشورة، وإضافة تطبيقات جديدة تستحق الاكتشاف.
      نستعين بأدوات ذكاء اصطناعي لمساعدتنا في صياغة المسودات الأولية، لكن كل محتوى يمر بمراجعة بشرية من فريقنا قبل النشر.
      هدفنا: مئات التطبيقات موثّقة بشكل احترافي، وتحديثات أسبوعية على القائمة.
    </p>

    <h2 style="color:var(--white);font-size:17px;font-weight:800;margin:22px 0 10px">الاستقلالية التحريرية</h2>
    <p>
      yassota ليس تابعاً لأي شركة تطبيقات أو متجر رقمي.
      نموذج العمل يعتمد على الإعلانات (Google AdSense) وهو ما يُتيح لنا تقديم المحتوى مجاناً للقرّاء.
      <strong style="color:var(--white)">الإعلانات لا تؤثر على تقييماتنا أو اختياراتنا التحريرية بأي شكل.</strong>
      أي تطبيق يُعرض في أقسام "اختيار المحرر" أو "المميّز" هو اختيار تحريري بحت.
    </p>

    <h2 style="color:var(--white);font-size:17px;font-weight:800;margin:22px 0 10px">الفريق</h2>
    <p>
      خلف yassota فريق صغير من المهتمين بعالم تطبيقات أندرويد والتكنولوجيا العربية، يديرون الموقع بشغف حقيقي لخدمة المستخدم العربي.
      نرحّب دائماً باقتراحاتكم وتقاريركم عن أي تطبيق أو رابط تحميل يستحق الإضافة، أو أي خطأ يحتاج تصحيحاً.
    </p>

    <div style="background:rgba(6,182,212,.07);border:1px solid rgba(6,182,212,.2);border-radius:12px;padding:18px 20px;margin:22px 0 0">
      <div style="font-weight:700;color:var(--white);font-size:14px;margin-bottom:10px">للتواصل معنا</div>
      <div style="display:flex;flex-wrap:wrap;gap:14px">
        <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--cyan);font-size:13px;display:flex;align-items:center;gap:6px">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <?= h($contactEmail) ?>
        </a>
        <a href="<?= h(url('contact')) ?>" style="color:var(--cyan);font-size:13px;display:flex;align-items:center;gap:6px">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
          نموذج التواصل
        </a>
      </div>
    </div>

    <p style="margin-top:22px">
      لمزيد من الشفافية، راجع أيضاً:
      <a href="<?= h(url('privacy-policy')) ?>" style="color:var(--cyan)">سياسة الخصوصية</a>،
      <a href="<?= h(url('terms')) ?>" style="color:var(--cyan)">شروط الاستخدام</a>،
      <a href="<?= h(url('cookie-policy')) ?>" style="color:var(--cyan)">سياسة الكوكيز</a>، و
      <a href="<?= h(url('dmca')) ?>" style="color:var(--cyan)">سياسة DMCA</a>.
    </p>
  </div>

</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
