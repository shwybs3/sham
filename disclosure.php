<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$contactEmail = get_cfg($pdo, 'contact_email') ?: 'contact@' . parse_url(SITE_URL, PHP_URL_HOST);
$siteHost     = parse_url(SITE_URL, PHP_URL_HOST) ?: 'yassota.com';
$seoTitle     = 'الإفصاح الإعلاني — yassota';
$metaDesc     = 'إفصاح كامل عن طبيعة الإعلانات في موقع yassota: Google AdSense، الروابط التابعة، واستقلالية المحتوى التحريري.';
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
  <link rel="canonical" href="<?= h(url('disclosure')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189"
     crossorigin="anonymous"></script>
</head>
<body>

<?php render_site_header(); ?>

<div class="page-wrap fw">
<main class="main-content">

  <nav style="font-size:12px;color:var(--muted);margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <a href="<?= h(url('')) ?>" style="color:var(--cyan)">الرئيسية</a><span>/</span><span>الإفصاح الإعلاني</span>
  </nav>

  <div class="section-head reveal"><span class="section-title">الإفصاح الإعلاني</span></div>

  <div class="section-box reveal" style="color:var(--muted);font-size:14px;line-height:1.9">

    <!-- Notice box -->
    <div style="background:rgba(249,115,22,.08);border:1px solid rgba(249,115,22,.25);border-radius:12px;padding:18px 22px;margin-bottom:24px">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
        <strong style="color:var(--white);font-size:15px">إشعار مهم للقرّاء</strong>
      </div>
      <p style="margin:0;font-size:13px">
        يحتوي موقع yassota على <strong style="color:var(--white)">إعلانات مدفوعة</strong> من Google AdSense.
        هذه الإعلانات تظهر بوضوح وتُميَّز عن المحتوى التحريري. نلتزم بالإفصاح الكامل والشفافية وفق متطلبات
        لجنة التجارة الفيدرالية الأمريكية (FTC) ومعايير الإعلانات الرقمية الدولية.
      </p>
    </div>

    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:0 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">١. الإعلانات عبر Google AdSense</h2>
    <p>
      يستخدم yassota شبكة <strong style="color:var(--white)">Google AdSense</strong> (معرّف الناشر: ca-pub-5506877998492189)
      لعرض الإعلانات. هذه الإعلانات:
    </p>
    <ul style="padding-right:20px;margin:10px 0 18px">
      <li>قد تكون <strong style="color:var(--white)">مخصصة</strong> بناءً على تاريخ تصفحك واهتماماتك (إعلانات قائمة على الاهتمامات).</li>
      <li>يختارها خوارزمية Google تلقائياً — نحن لا نتدخل في اختيار محتوى الإعلان المحدد.</li>
      <li>تُدار من لوحة Google AdSense الخاصة بنا؛ لا نتحكم في المعلنين الفرديين.</li>
      <li>تعمل Google على جمع بيانات معينة لأغراض عرض الإعلانات (راجع <a href="https://policies.google.com/privacy" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">سياسة خصوصية Google</a>).</li>
    </ul>
    <p>
      <strong style="color:var(--white)">يمكنك إيقاف الإعلانات المخصصة:</strong>
      من خلال <a href="https://adssettings.google.com" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">إعدادات إعلانات Google</a>
      أو عبر <a href="https://www.aboutads.info/choices/" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">aboutads.info</a>.
    </p>

    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">٢. الروابط الخارجية وGoogle Play</h2>
    <p>
      روابط التحميل الموجودة على الموقع تُوجَّه في الغالب نحو <strong style="color:var(--white)">Google Play</strong>
      أو المواقع الرسمية للمطوّرين. yassota <strong style="color:var(--white)">لا يشارك في برامج التابعين لأي شركة</strong>
      ولا يحصل على عمولة أو مكافأة مادية من روابط التحميل هذه — هدفها الوحيد إيصالك للمصدر الرسمي للتطبيق.
    </p>

    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">٣. استقلالية المحتوى التحريري</h2>
    <p>
      <strong style="color:var(--white)">الإعلانات لا تؤثر بأي شكل على محتوانا التحريري.</strong>
      اختيار التطبيقات لمراجعتها، والتقييمات المعطاة، والآراء الواردة في المقالات — جميعها تصدر بناءً على
      معايير تحريرية مستقلة دون أي تأثير من المعلنين. المعلن لا يحصل على معاملة تفضيلية في محتوانا مقابل إعلانه.
    </p>

    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">٤. كيف نُميّز الإعلانات عن المحتوى</h2>
    <p>الإعلانات في موقع yassota مُميّزة بوضوح بالطرق التالية:</p>
    <ul style="padding-right:20px;margin:10px 0">
      <li>شارة "إعلان" تظهر فوق وحدات الإعلانات أو حولها.</li>
      <li>تصميم مرئي مختلف عن المحتوى التحريري.</li>
      <li>وضع الإعلانات في مناطق مخصصة لا تتداخل مع المحتوى الرئيسي.</li>
    </ul>
    <p>إذا كان لديك أي شك حول ما إذا كان قسم ما إعلاناً أم محتوىً تحريرياً، تواصل معنا للاستيضاح.</p>

    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">٥. تمويل الموقع</h2>
    <p>
      عائدات الإعلانات هي المصدر الرئيسي لتمويل yassota، وتُتيح لنا تقديم المحتوى مجاناً للقرّاء.
      هذه العائدات تُستخدم لتغطية تكاليف الاستضافة، التطوير، وإنتاج المحتوى.
      نؤمن بأن الشفافية حول تمويل المحتوى جزء أساسي من الثقة مع قرّائنا.
    </p>

    <h2 style="color:var(--white);font-size:16px;font-weight:800;margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">٦. تواصل معنا</h2>
    <p>
      لأي استفسار حول الإعلانات أو المحتوى الممول أو سياسة الإفصاح لدينا:
    </p>
    <ul style="padding-right:20px;margin:10px 0">
      <li>البريد الإلكتروني: <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--cyan)"><?= h($contactEmail) ?></a></li>
      <li>نموذج التواصل: <a href="<?= h(url('contact')) ?>" style="color:var(--cyan)">صفحة اتصل بنا</a></li>
    </ul>
  </div>

  <div style="background:rgba(6,182,212,.06);border:1px solid rgba(6,182,212,.18);border-radius:12px;padding:16px 20px;margin-top:20px" class="reveal">
    <div style="font-size:12px;color:var(--muted);line-height:1.8">
      راجع أيضاً:
      <a href="<?= h(url('privacy-policy')) ?>" style="color:var(--cyan);margin:0 8px">سياسة الخصوصية</a> |
      <a href="<?= h(url('terms')) ?>" style="color:var(--cyan);margin:0 8px">شروط الاستخدام</a> |
      <a href="<?= h(url('cookie-policy')) ?>" style="color:var(--cyan);margin:0 8px">سياسة الكوكيز</a>
    </div>
  </div>

</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
