<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
  '@context'=>'https://schema.org','@type'=>'WebApplication',
  'name'=>'مولد الباركود المجاني','url'=>TOOLS_BASE_URL.'/tools/barcode/',
  'description'=>'أنشئ باركود بأشهر التنسيقات CODE128 وEAN-13 وUPC-A مجاناً مع إمكانية التحميل كـ SVG',
  'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
  'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],'inLanguage'=>'ar'
]);
tool_head('مولد الباركود المجاني | CODE128 EAN-13 UPC | yassota','أنشئ باركود بتنسيقات CODE128 وEAN-13 وEAN-8 وUPC-A وCode 39 مجاناً. تحميل SVG/PNG عالي الجودة للطباعة.',$schema,'#065f46');
tool_header();
?>
<main class="t-main">
<div class="t-hero">
  <div class="t-hero-icon" style="background:#d1fae5">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#065f46" stroke-width="2"><path d="M3 9.5V5a1 1 0 011-1h3.5M3 14.5V19a1 1 0 001 1h3.5M20.5 9.5V5a1 1 0 00-1-1H16M20.5 14.5V19a1 1 0 01-1 1H16"/><line x1="7" y1="4" x2="7" y2="20"/><line x1="10" y1="4" x2="10" y2="20"/><line x1="12" y1="4" x2="12" y2="20" stroke-width="3"/><line x1="15" y1="4" x2="15" y2="20"/><line x1="17" y1="4" x2="17" y2="20" stroke-width="3"/></svg>
  </div>
  <h1>مولد الباركود</h1>
  <p>أنشئ باركوداً احترافياً قابلاً للتحميل والطباعة</p>
</div>

<div class="t-card">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    <div>
      <label class="t-label">نوع الباركود</label>
      <select id="bc-format" class="t-input" onchange="onFormatChange()">
        <option value="CODE128">CODE128 — عام (نصوص وأرقام)</option>
        <option value="EAN13">EAN-13 — المنتجات التجارية</option>
        <option value="EAN8">EAN-8 — المنتجات الصغيرة</option>
        <option value="UPCA">UPC-A — أمريكا وكندا</option>
        <option value="CODE39">Code 39 — الصناعة والجيش</option>
        <option value="ITF14">ITF-14 — الكراتين والتوزيع</option>
      </select>
    </div>
    <div>
      <label class="t-label">الارتفاع (بكسل)</label>
      <input type="range" id="bc-height" min="40" max="200" value="100" oninput="document.getElementById('bc-h-label').textContent=this.value+'px';generateBarcode()" style="width:100%;accent-color:#065f46">
      <div style="font-size:12px;color:#6b7280;margin-top:4px"><span id="bc-h-label">100px</span></div>
    </div>
  </div>
  <div id="bc-hint" style="font-size:12px;color:#065f46;background:#ecfdf5;padding:8px 12px;border-radius:6px;margin-bottom:12px"></div>
  <label class="t-label">البيانات</label>
  <input type="text" id="bc-data" class="t-input" placeholder="أدخل البيانات..." oninput="generateBarcode()" style="font-family:monospace;direction:ltr;text-align:left">
  <div style="display:flex;align-items:center;gap:16px;margin-top:12px;flex-wrap:wrap">
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" id="bc-label" checked onchange="generateBarcode()"> إظهار القيمة أسفل الباركود</label>
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" id="bc-dark" onchange="generateBarcode()"> وضع مقلوب (أبيض على أسود)</label>
  </div>

  <div id="bc-result" style="margin-top:20px;text-align:center;display:none">
    <div id="bc-container" style="display:inline-block;padding:16px;background:#fff;border:1px solid #e5e7eb;border-radius:8px"></div>
    <div id="bc-error" class="t-error" style="display:none"></div>
    <div style="display:flex;gap:10px;justify-content:center;margin-top:12px;flex-wrap:wrap">
      <button class="t-btn" onclick="downloadSVG()">تحميل SVG</button>
      <button onclick="downloadPNG()" style="padding:10px 20px;border:2px solid #065f46;background:transparent;color:#065f46;border-radius:8px;cursor:pointer;font-weight:700">تحميل PNG</button>
    </div>
  </div>
  <div id="bc-empty" style="margin-top:20px;text-align:center;padding:30px;border:2px dashed #a7f3d0;border-radius:10px;color:#6ee7b7">
    أدخل بيانات أعلاه لإنشاء الباركود
  </div>
</div>

<?php tool_ad(); ?>

<div class="t-card" style="line-height:1.9">
  <h2 class="t-article-h2">ما هو الباركود وتاريخه؟</h2>
  <p>الباركود (Barcode) أو "الرمز الشريطي" هو تمثيل بصري للبيانات في شكل أشرطة عمودية بعرض متفاوت وفراغات بينها. يُقرأ بماسح ضوئي (Scanner) يُحوّل الأنماط الضوئية إلى بيانات رقمية. اختُرع الباركود عام 1952 على يد نورمان وودلاند وبيرنارد سيلفر، لكنه دخل الاستخدام التجاري الواسع عام 1974 حين مُسح أول منتج (علبة لبان Wrigley's) في متجر أمريكي.</p>
  <p>منذ ذلك اليوم، غيّر الباركود صناعات بأكملها. سلاسل التوريد، المستودعات، المستشفيات، المكتبات، المطارات — كل هذه البيئات تعتمد على الباركود لتتبع المخزون وتسريع المعاملات وتقليل الأخطاء البشرية. اليوم يُسح ما يزيد على 5 مليار باركود يومياً حول العالم.</p>

  <h2 class="t-article-h2">أنواع الباركود المدعومة وكيف تختار</h2>
  <p><strong>CODE128:</strong> الأكثر مرونة. يدعم جميع أحرف ASCII (أحرف كبيرة وصغيرة وأرقام ورموز). الاختيار الأمثل إذا لم يكن لديك متطلب محدد. يُستخدم في الشحن واللوجستيات وتصنيع المنتجات والبطاقات الشخصية.</p>
  <p><strong>EAN-13:</strong> المعيار العالمي للمنتجات الاستهلاكية. يُشفّر 13 رقماً بالضبط. الرمز الوطني للمملكة العربية السعودية والإمارات هو 629، لبنان 569، مصر 622. يُستخدم على كل المنتجات التي تُباع في محلات البقالة والأسواق حول العالم.</p>
  <p><strong>EAN-8:</strong> نسخة مُقلَّصة من EAN-13 للمنتجات صغيرة الحجم التي لا تتسع للباركود الكامل. يُشفّر 8 أرقام.</p>
  <p><strong>UPC-A:</strong> المعيار الأمريكي والكندي للمنتجات. يُشفّر 12 رقماً. إذا كنت تبيع في أسواق أمريكا الشمالية فأنت تحتاجه.</p>
  <p><strong>Code 39:</strong> أقدم معيار (1974). يدعم الأحرف الكبيرة والأرقام وبعض الرموز. يُستخدم في الجيش والصناعة والرعاية الصحية الأمريكية بحكم متطلبات المواصفات القديمة.</p>
  <p><strong>ITF-14:</strong> مصمم للكراتين والصناديق الكبيرة في سلاسل التوريد. يُشفّر 14 رقماً وسهل القراءة حتى على السطوح المموجة.</p>

  <h2 class="t-article-h2">الفرق بين الباركود 1D وQR Code 2D</h2>
  <p>الباركود التقليدي (1D) يُخزّن المعلومات في بُعد واحد — عرض الأشرطة وفراغاتها. رمز QR (2D) يُخزّن في بُعدين. الفرق العملي: باركود 1D يُخزّن عشرات الأحرف فقط وهو كافٍ كمعرّف (ID) يُراجع قاعدة بيانات. QR يُخزّن آلاف الأحرف ويمكنه حمل رابط كامل أو بطاقة تواصل بدون قاعدة بيانات. في البيع بالتجزئة نستخدم الباركود لأنه أسرع مسحاً. في التسويق نستخدم QR لأنه أحمل للمعلومات.</p>

  <h2 class="t-article-h2">كيفية الحصول على رقم باركود رسمي للمنتجات</h2>
  <p>لبيع منتجاتك في المحلات التجارية الكبرى، تحتاج لرقم EAN-13 رسمي. تُصدره منظمة GS1 العالمية التي لها مكاتب في كل دولة. في المملكة العربية السعودية: GS1 SA (gs1.org.sa). في الإمارات: GS1 UAE. رسوم التسجيل تختلف بحسب حجم الشركة وعدد المنتجات. بعد التسجيل تحصل على بادئة خاصة بشركتك لتعريف منتجاتك بشكل فريد عالمياً.</p>
  <p>ملاحظة: الباركودات التي تُنشئها بهذه الأداة مناسبة للاستخدام الداخلي (المستودع، تنظيم المخزون، الاستخدام الشخصي). للبيع في المتاجر الكبرى تحتاج تسجيلاً رسمياً مع GS1.</p>

  <h2 class="t-article-h2">نصائح لطباعة الباركود بجودة عالية</h2>
  <p><strong>استخدم SVG للطباعة:</strong> SVG صيغة متجهية تُطبع بوضوح تام بأي حجم دون تشويه. قاوم إغراء استخدام PNG إذا كنت ستطبع. إذا اضطررت لاستخدام PNG، اختر دقة لا تقل عن 300 DPI.</p>
  <p><strong>اختبر بمسّاح حقيقي:</strong> قبل طباعة آلاف الملصقات، اطبع عينة واختبرها بماسح POS أو تطبيق هاتف. الماسح الضوئي الصناعي أكثر تسامحاً من الهاتف.</p>
  <p><strong>الحجم الأدنى:</strong> الحد الأدنى الموصى به لـ EAN-13 هو 3.7×2.7 سم. الحجم المثالي 5.2×3.7 سم. البعد الأفضل يُضاعف معدل نجاح المسح.</p>
  <p><strong>التباين والألوان:</strong> الباركود الأسود على أبيض هو الأفضل. يمكن استخدام ألوان داكنة على خلفية فاتحة لكن تجنّب الأحمر (الماسح الضوئي الليزري الأحمر لن يرى الأشرطة الحمراء).</p>

  <h2 class="t-article-h2">أسئلة شائعة حول الباركود</h2>
  <div class="t-faq-item">
    <div class="t-faq-q">ما هو رقم الفحص (Check Digit) في باركود EAN-13؟</div>
    <div class="t-faq-a">الرقم الأخير في EAN-13 هو رقم مُحسوب رياضياً (Modulo 10 checksum) للتحقق من صحة الأرقام الـ12 قبله. الماسح يحسب رقم الفحص ويقارنه بالرقم المُشفَّر — إذا تطابقا قُبل الباركود، وإلا رُفض. هذا يمنع أخطاء القراءة الجزئية. الأداة تحسب هذا الرقم تلقائياً.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">هل يمكنني استخدام باركود مُخصص لمخزوني الخاص؟</div>
    <div class="t-faq-a">نعم تماماً. CODE128 مثالي لهذا — أدخل معرّفات المنتجات الخاصة بك (أرقام الجرد الداخلي) وستحصل على باركود صالح للاستخدام مع أي ماسح ضوئي متوافق مع CODE128. لا تحتاج تسجيلاً خارجياً للاستخدام الداخلي.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">لماذا يرفض النظام رقم EAN-13 الذي أُدخله؟</div>
    <div class="t-faq-a">EAN-13 يتطلب بالضبط 12 أو 13 رقماً. إذا أدخلت 12 رقماً، الأداة تحسب الرقم الثالث عشر تلقائياً. إذا أدخلت 13 رقماً، يجب أن يكون الرقم الأخير صحيحاً (checksum صالح). EAN-13 لا يقبل حروفاً أو مسافات.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">ما الفرق بين الماسح الليزري والكاميرا في قراءة الباركود؟</div>
    <div class="t-faq-a">الماسح الليزري أسرع وأدق للباركودات 1D، يعمل على مسافات أبعد ولا يتأثر بالإضاءة. ماسح الكاميرا (Imager) يقرأ كل من الباركود 1D وQR Code 2D، ومناسب لشاشات الهاتف. أجهزة الكاشير الحديثة تستخدم Omnidirectional Laser Scanner الذي يقرأ الباركود من أي اتجاه.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">كيف أُضيف باركود لفاتورة أو شهادة PDF؟</div>
    <div class="t-faq-a">صدّر الباركود بصيغة SVG من هذه الأداة، ثم أضفه لمستند Word أو Publisher أو InDesign كصورة مُضمَّنة. في PHP يمكن استخدام مكتبة TCPDF أو Dompdf التي تدعم إنشاء الباركود مباشرةً في PDF. في Python مكتبة reportlab أو python-barcode.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">هل للباركود حقوق ملكية فكرية؟</div>
    <div class="t-faq-a">معايير الباركود (EAN, UPC, Code 128) ملك عام ومفتوح. منظمة GS1 تدير تخصيص أرقام الشركات والمنتجات. الشعارات أو الرسوم المضافة لتصميم الباركود قد تكون محمية، لكن الباركود النصي الخالص بدون تصميم إضافي لا يخضع لأي حماية.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">ما هو GS1-128 وكيف يختلف عن CODE128؟</div>
    <div class="t-faq-a">GS1-128 يستخدم رموز CODE128 لكن بتنسيق محدد يتضمن Application Identifiers (AI) — بادئات معيارية تُحدد معنى البيانات التالية لها. مثلاً AI (01) = رقم المنتج GTIN، AI (17) = تاريخ الصلاحية، AI (10) = رقم الدُفعة. يُستخدم في التوريد الصيدلاني والغذائي حيث تُطبع معلومات متعددة في باركود واحد.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">هل يمكن للباركود أن يحتوي على معلومات سرية؟</div>
    <div class="t-faq-a">الباركود ليس وسيلة تشفير — أي ماسح يمكنه قراءة بياناته. لا تضع معلومات حساسة في باركود مكشوف. إذا أردت تشفير البيانات، شفّرها أولاً (Base64, AES) ثم أنشئ باركود للنص المشفر — لكن انتبه أن القارئ سيحتاج مفتاح فك التشفير.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">كيف تعمل بطاقات الولاء والمتاجر؟</div>
    <div class="t-faq-a">بطاقة الولاء (Card de fidélité) تحتوي على باركود يُمثّل معرّف العميل. عند المسح يُبحث عن هذا المعرّف في قاعدة بيانات المتجر لجلب رصيد النقاط والعروض والتاريخ. الباركود نفسه لا يُخزّن النقاط — إنه مجرد مفتاح للوصول لقاعدة البيانات.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">ما هو مستوى الدقة المطلوب لطباعة باركود بجودة عالية؟</div>
    <div class="t-faq-a">الطابعات الليزرية والحرارية تُعطي أفضل النتائج. للطباعة التجارية ينبغي أن لا يقل MIL (Minimum Bar Width) عن 7.5 مل (0.19 mm) بدقة 203 DPI، أو 5 مل بدقة 300 DPI. تجنّب الطابعات النافثة للحبر للباركود الدقيق لأن الحبر قد ينتشر.</div>
  </div>

  <h2 class="t-article-h2">الخلاصة</h2>
  <p>الباركود ليس مجرد خطوط — إنه لغة عالمية صامتة تُتيح تتبع ملايين المنتجات بسرعة ودقة لا يُعادلها الإدخال اليدوي. سواء كنت تُخصص مخزونك أو تُعدّ ملصقات شحن أو تُجهّز منتجات للسوق، هذه الأداة توفر لك باركوداً احترافياً جاهزاً للتحميل والطباعة في ثوانٍ.</p>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
let bcTimer=null;
function onFormatChange(){
  const fmt=document.getElementById('bc-format').value;
  const hints={
    'CODE128':'يقبل: أحرف وأرقام ورموز ASCII — لا قيد على الطول',
    'EAN13':'أدخل 12 أو 13 رقماً (رقم الفحص يُحسب تلقائياً)',
    'EAN8':'أدخل 7 أو 8 أرقام (رقم الفحص يُحسب تلقائياً)',
    'UPCA':'أدخل 11 أو 12 رقماً (رقم الفحص يُحسب تلقائياً)',
    'CODE39':'يقبل: A-Z 0-9 - . $ / + % مسافة',
    'ITF14':'أدخل 13 أو 14 رقماً'
  };
  document.getElementById('bc-hint').textContent=hints[fmt]||'';
  generateBarcode();
}
function generateBarcode(){
  clearTimeout(bcTimer);
  bcTimer=setTimeout(()=>{
    const data=document.getElementById('bc-data').value.trim();
    if(!data){
      document.getElementById('bc-result').style.display='none';
      document.getElementById('bc-empty').style.display='block';
      return;
    }
    const fmt=document.getElementById('bc-format').value;
    const h=parseInt(document.getElementById('bc-height').value);
    const showLabel=document.getElementById('bc-label').checked;
    const dark=document.getElementById('bc-dark').checked;
    const container=document.getElementById('bc-container');
    container.style.background=dark?'#000':'#fff';
    container.innerHTML='<svg id="bc-svg"></svg>';
    document.getElementById('bc-error').style.display='none';
    try{
      JsBarcode('#bc-svg',data,{
        format:fmt,
        width:2,
        height:h,
        displayValue:showLabel,
        lineColor:dark?'#fff':'#000',
        background:dark?'#000':'#fff',
        margin:10,
        fontSize:14,
        textMargin:4
      });
      document.getElementById('bc-result').style.display='block';
      document.getElementById('bc-empty').style.display='none';
    }catch(e){
      document.getElementById('bc-error').style.display='block';
      document.getElementById('bc-error').textContent='خطأ: '+e.message;
      document.getElementById('bc-result').style.display='block';
      document.getElementById('bc-empty').style.display='none';
    }
  },300);
}
function downloadSVG(){
  const svg=document.getElementById('bc-svg');
  if(!svg)return;
  const s=new XMLSerializer().serializeToString(svg);
  const b=new Blob([s],{type:'image/svg+xml'});
  const a=document.createElement('a');a.href=URL.createObjectURL(b);a.download='barcode.svg';a.click();
}
function downloadPNG(){
  const svg=document.getElementById('bc-svg');
  if(!svg)return;
  const s=new XMLSerializer().serializeToString(svg);
  const img=new Image();
  const scale=3;
  img.onload=()=>{
    const c=document.createElement('canvas');
    c.width=img.width*scale;c.height=img.height*scale;
    const ctx=c.getContext('2d');ctx.drawImage(img,0,0,c.width,c.height);
    const a=document.createElement('a');a.href=c.toDataURL('image/png');a.download='barcode.png';a.click();
  };
  img.src='data:image/svg+xml;base64,'+btoa(unescape(encodeURIComponent(s)));
}
onFormatChange();
document.getElementById('bc-data').value='1234567890128';
generateBarcode();
</script>
<?php tool_footer(); ?>
