<?php
require_once __DIR__ . '/_base.php';

$tools = [
  ['slug'=>'compress','title'=>'ضاغط الصور','desc'=>'ضغط JPG وPNG وتحويلها إلى WebP بتوفير يصل 85%','icon'=>'<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M3 9h18M9 21V9"/>','color'=>'#2563eb','bg'=>'#dbeafe'],
  ['slug'=>'resize','title'=>'تغيير حجم الصورة','desc'=>'غيّر أبعاد صورتك بدقة مع الحفاظ على نسبة الأبعاد','icon'=>'<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>','color'=>'#dc2626','bg'=>'#fee2e2'],
  ['slug'=>'qr','title'=>'مولّد QR Code','desc'=>'حوّل أي رابط أو نص إلى رمز QR فوري قابل للتحميل','icon'=>'<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h.01M14 17h.01M17 14h.01M17 17h.01M20 14h.01M20 17h.01"/>','color'=>'#0f172a','bg'=>'#f1f5f9'],
  ['slug'=>'pass','title'=>'مولّد كلمات المرور','desc'=>'أنشئ كلمة مرور قوية وآمنة حسب متطلباتك','icon'=>'<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>','color'=>'#dc2626','bg'=>'#fee2e2'],
  ['slug'=>'colors','title'=>'منتقي الألوان','desc'=>'اختر لوناً وولّد لوحة ألوان متناسقة مع HEX وRGB وHSL','icon'=>'<circle cx="13.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="10.5" r="2.5"/><circle cx="8.5" cy="7.5" r="2.5"/><circle cx="6.5" cy="12.5" r="2.5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 011.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>','color'=>'#7c3aed','bg'=>'#ede9fe'],
  ['slug'=>'encode','title'=>'مشفّر Base64 وURL','desc'=>'شفّر وفكّ Base64 وURL واحسب MD5 وSHA-256','icon'=>'<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/><path d="M12 12v4M10 14h4"/>','color'=>'#0891b2','bg'=>'#cffafe'],
  ['slug'=>'words','title'=>'عدّاد الكلمات العربي','desc'=>'عدّ كلمات وأحرف وجمل نصك مع الكلمات الأكثر تكراراً','icon'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>','color'=>'#059669','bg'=>'#d1fae5'],
  ['slug'=>'whatsapp','title'=>'مولّد روابط واتساب','desc'=>'أنشئ رابط واتساب مباشر لأي رقم ورسالة مخصصة','icon'=>'<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a6.145 6.145 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>','color'=>'#16a34a','bg'=>'#dcfce7'],
  ['slug'=>'write','title'=>'كاتب المحتوى بالذكاء الاصطناعي','desc'=>'أنشئ مقالات ومنشورات وأوصاف منتجات باللغة العربية','icon'=>'<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>','color'=>'#0891b2','bg'=>'#e0f2fe'],
  ['slug'=>'hashtag','title'=>'مولّد الهاشتاق بالذكاء الاصطناعي','desc'=>'أنشئ هاشتاقات احترافية لإنستغرام وتيك توك وتويتر','icon'=>'<path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>','color'=>'#e11d48','bg'=>'#ffe4e6'],
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
