<?php
require_once __DIR__ . '/_base.php';

$tools = [
  // أدوات الصور
  ['slug'=>'compress','title'=>'ضاغط الصور','desc'=>'ضغط JPG وPNG وتحويلها إلى WebP بتوفير يصل 85%','icon'=>'<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M3 9h18M9 21V9"/>','color'=>'#2563eb','bg'=>'#dbeafe'],
  ['slug'=>'resize','title'=>'تغيير حجم الصورة','desc'=>'غيّر أبعاد صورتك بدقة مع الحفاظ على نسبة الأبعاد','icon'=>'<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>','color'=>'#dc2626','bg'=>'#fee2e2'],
  ['slug'=>'img-crop','title'=>'قصّ الصور','desc'=>'اقطع صورتك بدقة — نسب جاهزة أو أبعاد مخصصة','icon'=>'<path d="M6 2v14a2 2 0 002 2h14"/><path d="M18 22V8a2 2 0 00-2-2H2"/>','color'=>'#0891b2','bg'=>'#e0f2fe'],
  ['slug'=>'img-convert','title'=>'تحويل تنسيق الصور','desc'=>'حوّل JPG إلى WebP وPNG وبالعكس مجاناً','icon'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>','color'=>'#059669','bg'=>'#d1fae5'],
  ['slug'=>'ocr','title'=>'استخراج النص (OCR)','desc'=>'استخرج النص من أي صورة — يدعم العربية والإنجليزية','icon'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><path d="M9 21V9"/>','color'=>'#0891b2','bg'=>'#cffafe'],
  // أدوات PDF
  ['slug'=>'pdf-merge','title'=>'دمج ملفات PDF','desc'=>'ادمج عدة ملفات PDF في ملف واحد مجاناً في المتصفح','icon'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="12" y2="18"/>','color'=>'#7c3aed','bg'=>'#ede9fe'],
  ['slug'=>'pdf-split','title'=>'تقسيم PDF','desc'=>'قسّم ملف PDF أو استخرج صفحات محددة','icon'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><line x1="9" y1="15" x2="15" y2="15"/>','color'=>'#dc2626','bg'=>'#fee2e2'],
  ['slug'=>'pdf-compress','title'=>'ضغط PDF','desc'=>'قلّل حجم ملف PDF مع الحفاظ على الجودة','icon'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><line x1="12" y1="18" x2="12" y2="12"/>','color'=>'#ea580c','bg'=>'#ffedd5'],
  // أدوات المال والأسعار
  ['slug'=>'currency','title'=>'محوّل العملات','desc'=>'أسعار صرف لحظية لأكثر من 150 عملة عالمية','icon'=>'<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>','color'=>'#f59e0b','bg'=>'#fef3c7'],
  ['slug'=>'gold-calc','title'=>'حاسبة الذهب','desc'=>'احسب سعر الذهب بجميع العيارات بالعملات العربية','icon'=>'<circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/>','color'=>'#d97706','bg'=>'#fef9c3'],
  // أدوات للمطورين
  ['slug'=>'json-fmt','title'=>'منسّق JSON','desc'=>'نسّق وتحقق وضغط ملفات JSON مجاناً','icon'=>'<path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/>','color'=>'#6366f1','bg'=>'#eef2ff'],
  ['slug'=>'encode','title'=>'مشفّر Base64 وURL','desc'=>'شفّر وفكّ Base64 وURL واحسب MD5 وSHA-256','icon'=>'<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/>','color'=>'#0891b2','bg'=>'#cffafe'],
  // أدوات عامة
  ['slug'=>'qr','title'=>'مولّد QR Code','desc'=>'حوّل أي رابط أو نص إلى رمز QR فوري قابل للتحميل','icon'=>'<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>','color'=>'#0f172a','bg'=>'#f1f5f9'],
  ['slug'=>'pass','title'=>'مولّد كلمات المرور','desc'=>'أنشئ كلمة مرور قوية وآمنة حسب متطلباتك','icon'=>'<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>','color'=>'#dc2626','bg'=>'#fee2e2'],
  ['slug'=>'unit','title'=>'محوّل الوحدات','desc'=>'حوّل بين وحدات الطول والوزن والحرارة والمساحة والسرعة','icon'=>'<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>','color'=>'#0891b2','bg'=>'#e0f2fe'],
  ['slug'=>'colors','title'=>'منتقي الألوان','desc'=>'اختر لوناً وولّد لوحة ألوان متناسقة مع HEX وRGB وHSL','icon'=>'<circle cx="13.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="10.5" r="2.5"/><circle cx="8.5" cy="7.5" r="2.5"/><circle cx="6.5" cy="12.5" r="2.5"/>','color'=>'#7c3aed','bg'=>'#ede9fe'],
  // أدوات النصوص والمحتوى
  ['slug'=>'words','title'=>'عدّاد الكلمات العربي','desc'=>'عدّ كلمات وأحرف وجمل نصك مع الكلمات الأكثر تكراراً','icon'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>','color'=>'#059669','bg'=>'#d1fae5'],
  ['slug'=>'whatsapp','title'=>'مولّد روابط واتساب','desc'=>'أنشئ رابط واتساب مباشر لأي رقم ورسالة مخصصة','icon'=>'<path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>','color'=>'#16a34a','bg'=>'#dcfce7'],
  // أدوات الذكاء الاصطناعي
  ['slug'=>'write','title'=>'كاتب المحتوى AI','desc'=>'أنشئ مقالات ومنشورات وأوصاف منتجات باللغة العربية','icon'=>'<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>','color'=>'#0891b2','bg'=>'#e0f2fe'],
  ['slug'=>'hashtag','title'=>'مولّد الهاشتاق AI','desc'=>'أنشئ هاشتاقات احترافية لإنستغرام وتيك توك وتويتر','icon'=>'<line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/>','color'=>'#e11d48','bg'=>'#ffe4e6'],
  // أدوات الحياة والصحة
  ['slug'=>'age-calc','title'=>'حاسبة العمر','desc'=>'احسب عمرك بالسنوات والشهور والأيام والساعات مع ذكرى ميلادك القادمة','icon'=>'<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>','color'=>'#0891b2','bg'=>'#cffafe'],
  ['slug'=>'bmi','title'=>'حاسبة مؤشر كتلة الجسم','desc'=>'احسب BMI وتعرّف على تصنيفك الصحي ووزنك المثالي','icon'=>'<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>','color'=>'#16a34a','bg'=>'#dcfce7'],
  ['slug'=>'loan','title'=>'حاسبة القرض والتقسيط','desc'=>'احسب قسطك الشهري والفائدة الكلية وجدول السداد كاملاً','icon'=>'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>','color'=>'#d97706','bg'=>'#fef3c7'],
  ['slug'=>'percentage','title'=>'حاسبة النسبة المئوية','desc'=>'6 أنواع من حسابات النسبة: خصم، ضريبة، نسبة التغيير وأكثر','icon'=>'<line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>','color'=>'#dc2626','bg'=>'#fee2e2'],
  // أدوات الوقت
  ['slug'=>'timer','title'=>'ساعة إيقاف ومؤقت','desc'=>'ساعة إيقاف بدقة المليثانية ومؤقت عداد تنازلي مع تنبيه صوتي','icon'=>'<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>','color'=>'#e11d48','bg'=>'#ffe4e6'],
  ['slug'=>'timezone','title'=>'محوّل المناطق الزمنية','desc'=>'حوّل الوقت بين دمشق وبيروت والرياض ودبي وأي مدينة عالمية','icon'=>'<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/>','color'=>'#0891b2','bg'=>'#e0f2fe'],
  // أدوات المطورين
  ['slug'=>'diff','title'=>'مقارنة النصوص Diff','desc'=>'قارن بين نصّين وابحث عن الاختلافات — الإضافات خضراء والحذف أحمر','icon'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M9 11h6M9 15h6M9 7h3"/>','color'=>'#6366f1','bg'=>'#eef2ff'],
  ['slug'=>'base-conv','title'=>'محوّل قواعد الأرقام','desc'=>'حوّل الأرقام بين عشري وثنائي وست عشري وثماني فوراً','icon'=>'<rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/>','color'=>'#0f172a','bg'=>'#f1f5f9'],
  ['slug'=>'slug','title'=>'مولّد Slug الروابط','desc'=>'حوّل أي عنوان عربي أو إنجليزي إلى Slug صالح لعناوين URL','icon'=>'<path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>','color'=>'#dc2626','bg'=>'#fee2e2'],
  ['slug'=>'gradient','title'=>'مولّد تدرجات CSS','desc'=>'أنشئ تدرجات CSS جميلة خطية ودائرية وانسخ الكود جاهزاً','icon'=>'<defs><linearGradient id="gi"><stop offset="0%" stop-color="#8b5cf6"/><stop offset="100%" stop-color="#ec4899"/></linearGradient></defs><rect x="2" y="3" width="20" height="18" rx="3" fill="url(#gi)" stroke="none"/>','color'=>'#8b5cf6','bg'=>'#ede9fe'],
  ['slug'=>'lorem','title'=>'مولّد Lorem Ipsum عربي','desc'=>'أنشئ نصوص تعبئة عربية أو Lorem Ipsum للتصميم والنماذج','icon'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/>','color'=>'#0f766e','bg'=>'#ccfbf1'],
  ['slug'=>'markdown','title'=>'محوّل Markdown إلى HTML','desc'=>'اكتب Markdown وشاهد معاينة HTML فورية مع إمكانية التنزيل','icon'=>'<path d="M17 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2z"/><path d="M9 8l2 2-2 2M13 12h2"/>','color'=>'#0369a1','bg'=>'#e0f2fe'],
];

$schema = json_encode([
  '@context'=>'https://schema.org',
  '@type'=>'WebSite',
  'name'=>'yassota أدوات — أدوات ويب مجانية',
  'url'=>TOOLS_BASE_URL.'/',
  'description'=>'مجموعة أدوات ويب مجانية احترافية: ضغط صور، QR Code، كلمات مرور، ذكاء اصطناعي، وأكثر',
]);
tool_head('yassota أدوات — أدوات ويب مجانية ومتقدمة','مجموعة أدوات ويب مجانية احترافية: ضغط الصور، توليد QR، كلمات المرور، ذكاء اصطناعي، تحليل النصوص وأكثر',$schema);
tool_header();
?>
<main class="t-main">
  <div class="t-hero" style="text-align:center">
    <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:18px;background:#dbeafe;margin:0 auto 16px">
      <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
    </div>
    <h1 style="font-size:28px">أدوات ويب مجانية ومتقدمة</h1>
    <p>أدوات احترافية تعمل مباشرة في متصفحك — بدون تسجيل، بدون حدود، مجانية 100%</p>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;margin-bottom:24px">
    <?php foreach ($tools as $t):
      $toolUrl = TOOLS_BASE_URL . '/tools/' . $t['slug'] . '/';
    ?>
    <a href="<?= htmlspecialchars($toolUrl) ?>" style="text-decoration:none;display:block">
      <div class="t-card" style="margin:0;height:100%;transition:transform .18s,box-shadow .18s;cursor:pointer" onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.12)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
        <div style="width:46px;height:46px;border-radius:12px;background:<?= $t['bg'] ?>;display:flex;align-items:center;justify-content:center;margin-bottom:12px">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="<?= in_array($t['slug'],['whatsapp']) ? $t['color'] : 'none' ?>" stroke="<?= $t['color'] ?>" stroke-width="2"><?= $t['icon'] ?></svg>
        </div>
        <div style="font-size:15px;font-weight:700;color:#0f172a;margin-bottom:6px"><?= htmlspecialchars($t['title']) ?></div>
        <div style="font-size:13px;color:#64748b;line-height:1.6"><?= htmlspecialchars($t['desc']) ?></div>
        <div style="margin-top:12px;font-size:12px;font-weight:700;color:<?= $t['color'] ?>">جرّب الآن ←</div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>لماذا أدوات yassota؟</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-top:4px">
      <?php
      $features = [
        ['icon'=>'🔒','title'=>'خصوصية تامة','desc'=>'أدوات كثيرة تعمل داخل متصفحك بالكامل دون إرسال أي بيانات'],
        ['icon'=>'⚡','title'=>'سريعة جداً','desc'=>'لا انتظار، لا تسجيل، لا حدود — نتائج فورية'],
        ['icon'=>'🆓','title'=>'مجانية 100%','desc'=>'جميع الأدوات مجانية ولا تتطلب اشتراكاً أو بطاقة ائتمانية'],
        ['icon'=>'🤖','title'=>'ذكاء اصطناعي','desc'=>'أدوات مدعومة بأحدث نماذج الذكاء الاصطناعي العربية'],
      ];
      foreach ($features as $f):
      ?>
      <div style="padding:14px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0">
        <div style="font-size:22px;margin-bottom:6px"><?= $f['icon'] ?></div>
        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:4px"><?= $f['title'] ?></div>
        <div style="font-size:12px;color:#64748b"><?= $f['desc'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>
<?php tool_footer(); ?>
