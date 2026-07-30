<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
  '@context'=>'https://schema.org','@type'=>'WebApplication',
  'name'=>'مولد QR Code مجاني','url'=>TOOLS_BASE_URL.'/tools/qr-code/',
  'description'=>'أنشئ رمز QR Code مجانياً لأي رابط أو نص أو رقم هاتف أو بريد إلكتروني — بدون تسجيل',
  'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
  'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],'inLanguage'=>'ar'
]);
tool_head('مولد QR Code مجاني | إنشاء رمز QR فوري | yassota','أنشئ QR Code لأي رابط أو نص أو هاتف أو واي فاي مجاناً. حجم قابل للتعديل، تحميل PNG/SVG، فوري بدون تسجيل.',$schema,'#1d4ed8');
tool_header();
?>
<main class="t-main">
<div class="t-hero">
  <div class="t-hero-icon" style="background:#dbeafe">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="3" height="3"/><rect x="18" y="14" width="3" height="3"/><rect x="14" y="18" width="3" height="3"/><rect x="18" y="18" width="3" height="3"/></svg>
  </div>
  <h1>مولد QR Code</h1>
  <p>أنشئ رمز QR لأي محتوى في ثانية واحدة</p>
</div>

<div class="t-card">
  <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <?php foreach(['url'=>'رابط','text'=>'نص','tel'=>'هاتف','email'=>'بريد','wifi'=>'واي فاي'] as $k=>$v): ?>
    <button onclick="setType('<?= $k ?>')" id="type-<?= $k ?>" class="<?= $k==='url'?'t-btn':'' ?>" style="<?= $k!=='url'?'padding:8px 14px;border:1px solid #1d4ed8;background:transparent;color:#1d4ed8;border-radius:8px;cursor:pointer;font-weight:600':'' ?>"><?= $v ?></button>
    <?php endforeach; ?>
  </div>

  <div id="input-url">
    <label class="t-label">رابط URL</label>
    <input type="url" id="qr-url" class="t-input" placeholder="https://example.com" oninput="generateQR()" style="direction:ltr;text-align:left">
  </div>
  <div id="input-text" style="display:none">
    <label class="t-label">النص</label>
    <textarea id="qr-text" class="t-input" rows="3" placeholder="أدخل أي نص هنا..." oninput="generateQR()" style="resize:vertical"></textarea>
  </div>
  <div id="input-tel" style="display:none">
    <label class="t-label">رقم الهاتف</label>
    <input type="tel" id="qr-tel" class="t-input" placeholder="+966501234567" oninput="generateQR()" style="direction:ltr;text-align:left">
  </div>
  <div id="input-email" style="display:none">
    <label class="t-label">البريد الإلكتروني</label>
    <input type="email" id="qr-email-addr" class="t-input" placeholder="user@example.com" oninput="generateQR()" style="direction:ltr;text-align:left">
    <label class="t-label" style="margin-top:10px">الموضوع (اختياري)</label>
    <input type="text" id="qr-email-sub" class="t-input" placeholder="موضوع الرسالة" oninput="generateQR()">
  </div>
  <div id="input-wifi" style="display:none">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <div><label class="t-label">اسم الشبكة (SSID)</label><input type="text" id="qr-ssid" class="t-input" placeholder="My Network" oninput="generateQR()"></div>
      <div><label class="t-label">كلمة المرور</label><input type="password" id="qr-wpass" class="t-input" placeholder="..." oninput="generateQR()"></div>
      <div><label class="t-label">نوع الأمان</label>
        <select id="qr-wsec" class="t-input" onchange="generateQR()"><option value="WPA">WPA/WPA2</option><option value="WEP">WEP</option><option value="nopass">بدون كلمة مرور</option></select>
      </div>
    </div>
  </div>

  <div style="display:flex;align-items:center;gap:16px;margin-top:14px;flex-wrap:wrap">
    <div style="flex:1;min-width:140px">
      <label class="t-label">الحجم: <span id="size-label">300</span>px</label>
      <input type="range" id="qr-size" min="100" max="600" step="50" value="300" oninput="document.getElementById('size-label').textContent=this.value;generateQR()" style="width:100%;accent-color:#1d4ed8">
    </div>
    <div>
      <label class="t-label">مستوى التصحيح</label>
      <select id="qr-ecc" class="t-input" onchange="generateQR()">
        <option value="L">منخفض (L)</option>
        <option value="M" selected>متوسط (M)</option>
        <option value="Q">عالٍ (Q)</option>
        <option value="H">أعلى (H)</option>
      </select>
    </div>
  </div>

  <div id="qr-result" style="margin-top:20px;text-align:center;display:none">
    <img id="qr-img" src="" alt="QR Code" style="max-width:100%;border-radius:10px;border:1px solid #e5e7eb">
    <div style="display:flex;gap:10px;justify-content:center;margin-top:12px;flex-wrap:wrap">
      <a id="qr-dl-png" download="qrcode.png" class="t-btn">تحميل PNG</a>
      <a id="qr-dl-svg" download="qrcode.svg" style="padding:10px 20px;border:2px solid #1d4ed8;background:transparent;color:#1d4ed8;border-radius:8px;cursor:pointer;font-weight:700;text-decoration:none">تحميل SVG</a>
    </div>
  </div>
  <div id="qr-empty" style="margin-top:20px;text-align:center;padding:30px;border:2px dashed #bfdbfe;border-radius:10px;color:#93c5fd">
    أدخل محتوى أعلاه لإنشاء الرمز
  </div>
</div>

<?php tool_ad(); ?>

<div class="t-card" style="line-height:1.9">
  <h2 class="t-article-h2">ما هو رمز QR Code وكيف يعمل؟</h2>
  <p>QR Code اختصار لـ Quick Response Code (رمز الاستجابة السريعة). اخترعه المهندس الياباني ماسايوشي هارا في شركة Denso Wave عام 1994 لتتبع قطع غيار السيارات في خطوط التجميع. لكن ما بدأ كأداة صناعية تحوّل إلى أحد أكثر رموز المعلومات استخداماً على وجه الأرض، خاصةً بعد انتشار الهواتف الذكية التي تستطيع قراءته في ثانية.</p>
  <p>رمز QR هو مصفوفة ثنائية الأبعاد (2D Matrix Barcode) تُخزّن المعلومات في نمط من النقاط السوداء والبيضاء على خلفية مربعة الشكل. على عكس الباركود الخطي التقليدي الذي يُخزّن المعلومات في اتجاه واحد فقط، يُخزّن رمز QR المعلومات في كلا الاتجاهين الأفقي والرأسي، مما يُتيح له تخزين كميات أكبر بكثير من البيانات في مساحة أصغر.</p>
  <p>رمز QR قياسي يمكنه تخزين ما يصل إلى: 7,089 رقماً، أو 4,296 حرفاً أبجدياً رقمياً، أو 2,953 بايت من البيانات الثنائية، أو 1,817 حرف Kanji ياباني. هذه السعة الكبيرة تجعله مناسباً لتخزين روابط URL كاملة وبيانات WiFi وبطاقات تواصل شخصية وغيرها.</p>

  <h2 class="t-article-h2">كيفية استخدام مولد QR Code</h2>
  <p><strong>اختيار نوع المحتوى:</strong> الأداة تدعم خمسة أنواع — رابط URL للمواقع والمتاجر، نص حر لأي محتوى، رقم هاتف لمشاركة الاتصال السريع، بريد إلكتروني مع الموضوع، واتصال WiFi مع بيانات الشبكة الكاملة.</p>
  <p><strong>ضبط الحجم:</strong> اختر حجماً مناسباً لوسيلة الاستخدام. 150px للمواقع الإلكترونية، 300px للطباعة الرقمية، 500-600px للملصقات واللافتات المطبوعة كبيرة الحجم.</p>
  <p><strong>مستوى تصحيح الأخطاء:</strong> مستوى L يعني حجم رمز أصغر لكن حساسية أكبر للتلف. مستوى H يعني رمزاً أكبر وأكثر مقاومة للتلف. للطباعة اختر M أو Q. لعرض على الشاشة اختر L أو M.</p>
  <p><strong>التحميل:</strong> حمّل الرمز بصيغة PNG للاستخدام الرقمي أو SVG للطباعة بجودة لانهائية.</p>

  <h2 class="t-article-h2">أنواع رموز QR واستخداماتها</h2>
  <p><strong>QR للروابط (URL):</strong> الأكثر شيوعاً. يُوجّه الماسح للموقع أو الصفحة مباشرة. مستخدم في المطاعم للقوائم، المتاجر للعروض، البطاقات الشخصية لمواقع المحترفين، والمنتجات للوصول لمحتوى إضافي.</p>
  <p><strong>QR للواي فاي:</strong> بدلاً من إخبار ضيوفك بكلمة مرور الواي فاي، أعطهم QR Code ليمسحوه ويتصلوا تلقائياً. يُستخدم في الفنادق والمقاهي والمكاتب.</p>
  <p><strong>QR للدفع:</strong> منصات الدفع مثل PayPal وSTC Pay وSTRIPE تُولّد QR Codes لاستقبال المدفوعات دون الحاجة لإدخال أرقام الحسابات يدوياً.</p>
  <p><strong>QR للمنتجات:</strong> يُتيح للمستهلكين التحقق من أصالة المنتج، الحصول على تفاصيل إضافية، قراءة تعليمات الاستخدام، أو الوصول لضمان المنتج.</p>
  <p><strong>QR للتعليم:</strong> المعلمون يضمّون QR Codes في المناهج الورقية لتوجيه الطلاب لمقاطع فيديو تعليمية أو مواد إضافية على الإنترنت.</p>

  <h2 class="t-article-h2">الفرق بين QR Code وBarcode التقليدي</h2>
  <p>كلاهما يُخزّن معلومات في نمط بصري يمكن مسحه ضوئياً، لكنهما يختلفان في: <strong>السعة</strong> — QR يُخزّن آلاف الأحرف بينما الباركود يُخزّن عشرات فقط. <strong>الاتجاه</strong> — الباركود يُقرأ في اتجاه واحد، QR في اتجاهين. <strong>تصحيح الأخطاء</strong> — QR يعمل حتى عند تلف 30% من مساحته. <strong>الهاتف</strong> — كاميرا الهاتف تقرأ QR مباشرةً دون أداة خاصة.</p>

  <h2 class="t-article-h2">نصائح لإنشاء QR Code فعّال وقابل للمسح</h2>
  <p><strong>التباين العالي:</strong> الرمز الكلاسيكي أسود على خلفية بيضاء هو الأكثر قابلية للمسح. ألوان أخرى تعمل لكن يجب الحفاظ على تباين كافٍ. تجنّب استخدام ألوان متشابهة في القيمة.</p>
  <p><strong>المسافة الهادئة (Quiet Zone):</strong> QR Code يحتاج مساحة فارغة حوله (عادةً 4 وحدات). تأكد أن الرمز في تصميمك لا يُقطع أو يُطغى عليه عناصر أخرى.</p>
  <p><strong>الاختبار قبل الطباعة:</strong> دائماً اختبر القراءة على أجهزة مختلفة قبل الطباعة الضخمة. استخدم كاميرا iOS وAndroid وتطبيقات QR مختلفة للتحقق.</p>
  <p><strong>QR الديناميكي مقابل الساكن:</strong> رمز QR الساكن (Static) يُشفّر البيانات مباشرةً ولا يمكن تعديله. الرمز الديناميكي (Dynamic) يُشفّر رابطاً وسيطاً يمكن تغيير الوجهة منه لاحقاً — مفيد جداً للطباعة الكبيرة أو الإعلانات ذات الصلة المتغيّرة.</p>

  <h2 class="t-article-h2">أسئلة شائعة حول QR Code</h2>
  <div class="t-faq-item">
    <div class="t-faq-q">هل QR Code يمكن أن يحتوي على فيروس أو برنامج ضار؟</div>
    <div class="t-faq-a">رمز QR نفسه مجرد بيانات نصية لا يُشكّل خطراً. لكن الخطر هو في الرابط الذي يقودك إليه — قد يكون موقع تصيد احتيالي. دائماً تحقق من الرابط في المعاينة قبل فتحه، وكن حذراً من QR Codes في أماكن عامة تبدو مُلصقة فوق رموز أصلية.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">لماذا لا يستطيع هاتفي قراءة رمز QR معين؟</div>
    <div class="t-faq-a">الأسباب الشائعة: تباين منخفض جداً (ألوان متشابهة)، حجم صغير جداً مع طباعة منخفضة الجودة، تلف في الرمز يتجاوز قدرة مستوى التصحيح المختار، انعكاس الضوء على سطح لامع، أو مشكلة في ترميز البيانات. جرّب تطبيق QR مخصص إذا فشلت الكاميرا الأصلية.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">ما هو أقصى حجم للبيانات في QR Code؟</div>
    <div class="t-faq-a">بتنسيق نصي: 4,296 حرف. بيانات ثنائية: 2,953 بايت. أرقام فقط: 7,089 رقم. لكن كلما زادت البيانات، كلما أصبح الرمز أكثر كثافة وأصعب قراءة. للروابط الطويلة، استخدم مختصر روابط (URL shortener) قبل تحويلها لـ QR.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">هل يمكن إضافة شعار (Logo) لوسط رمز QR؟</div>
    <div class="t-faq-a">نعم، وهذا سبب تطوير خاصية تصحيح الأخطاء بمستوى عالٍ (H). عند استخدام مستوى H، يتحمل الرمز تغطية تصل إلى 30% من مساحته دون فقدان القدرة على القراءة. لكن للحصول على نتيجة احترافية، استخدم برامج متخصصة كـ Canva أو Adobe Illustrator لإضافة الشعار بدقة.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">هل رموز QR لها تاريخ انتهاء صلاحية؟</div>
    <div class="t-faq-a">الرموز الساكنة (Static) لا تنتهي أبداً — البيانات مُشفّرة فيها مباشرةً. أما الرموز الديناميكية فتعتمد على خادم خارجي لإعادة التوجيه، وستتوقف عن العمل إذا أوقفت الخدمة أو انتهت الاشتراك. هذه الأداة تُنشئ رموزاً ساكنة تعمل للأبد.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">ما أفضل حجم للطباعة في الملصقات؟</div>
    <div class="t-faq-a">الحجم الأدنى الموصى به للطباعة هو 2×2 سم (20×20 ملم) لقراءة سهلة على مسافة 25 سم. للملصقات الكبيرة يُحسب الحجم بمضاعفة المسافة المتوقعة للمسح مقسوماً على 10: مسافة 50 سم = حجم 5×5 سم على الأقل.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">هل يمكن استخدام QR Code دون إنترنت؟</div>
    <div class="t-faq-a">نعم للمحتوى المُشفَّر مباشرةً: رقم هاتف، نص، بيانات WiFi، bCard (بيانات تواصل). أما روابط URL فتحتاج إنترنت للوصول للموقع المستهدف (الرمز يُفتح محلياً لكن الرابط يحتاج اتصالاً). رمز WiFi يعمل بدون إنترنت لأنه يُعطي بيانات الاتصال المحلية.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">كيف أتتبع من مسح QR Code الخاص بي؟</div>
    <div class="t-faq-a">الرموز الساكنة لا يمكن تتبعها مباشرةً. لإضافة التتبع استخدم: Google Analytics UTM Parameters في الرابط (يُتتبع عبر Analytics الموقع)، أو استخدم QR Code ديناميكي مع خدمة مثل Bitly أو QR Code Generator Pro التي توفر لوحة إحصاءات كاملة.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">ما معنى الأنماط الثلاثة في زوايا رمز QR؟</div>
    <div class="t-faq-a">تلك المربعات الكبيرة في ثلاث زوايا تُسمى "نقاط البحث عن الموضع" (Finder Patterns). تُتيح للماسح الضوئي تحديد موقع الرمز وزاويته وحجمه بسرعة بغض النظر عن اتجاهه. الزاوية الرابعة (أسفل اليمين عادةً) تحتوي على نمط محاذاة أصغر يساعد في تصحيح التشويه.</div>
  </div>
  <div class="t-faq-item">
    <div class="t-faq-q">ما الفرق بين QR وDataMatrix وAztec؟</div>
    <div class="t-faq-a">الثلاثة رموز ثنائية الأبعاد. DataMatrix مشابه لـ QR لكن أصغر عند نفس سعة البيانات، يُستخدم في الصناعة الدوائية والإلكترونيات الصغيرة. Aztec (الذي تراه على بطاقات الطيران) يُمكن قراءته حتى عند تلف أكبر ولا يحتاج "منطقة هادئة" حوله. QR هو الأشمل لأنه مدعوم نباشياً من كاميرات الهاتف.</div>
  </div>

  <h2 class="t-article-h2">الخلاصة</h2>
  <p>QR Code ليس مجرد مربع أسود وأبيض — إنه جسر بين العالمين الورقي والرقمي. سواء كنت صاحب مطعم يريد قائمة إلكترونية، أو مسوّق يُريد تتبع حملاته، أو فرداً يُريد مشاركة بيانات تواصله بسهولة — رمز QR هو الحل. استخدم هذه الأداة لإنشاء رموز عالية الجودة قابلة للتحميل بصيغة SVG للطباعة أو PNG للاستخدام الرقمي.</p>
</div>
</main>

<script>
let currentType='url';
function setType(t){
  currentType=t;
  ['url','text','tel','email','wifi'].forEach(x=>{
    document.getElementById('input-'+x).style.display=x===t?'block':'none';
    const btn=document.getElementById('type-'+x);
    btn.className=x===t?'t-btn':'';
    if(x!==t)btn.style.cssText='padding:8px 14px;border:1px solid #1d4ed8;background:transparent;color:#1d4ed8;border-radius:8px;cursor:pointer;font-weight:600';
    else btn.style.cssText='';
  });
  generateQR();
}
function getData(){
  if(currentType==='url')return document.getElementById('qr-url').value.trim();
  if(currentType==='text')return document.getElementById('qr-text').value.trim();
  if(currentType==='tel'){const t=document.getElementById('qr-tel').value.trim();return t?'tel:'+t:'';}
  if(currentType==='email'){const a=document.getElementById('qr-email-addr').value.trim();const s=document.getElementById('qr-email-sub').value.trim();return a?'mailto:'+a+(s?'?subject='+encodeURIComponent(s):''):'';}
  if(currentType==='wifi'){
    const ssid=document.getElementById('qr-ssid').value.trim();
    const pass=document.getElementById('qr-wpass').value;
    const sec=document.getElementById('qr-wsec').value;
    return ssid?`WIFI:T:${sec};S:${ssid};P:${pass};;`:'';
  }
  return '';
}
function generateQR(){
  const data=getData();
  const size=document.getElementById('qr-size').value;
  const ecc=document.getElementById('qr-ecc').value;
  if(!data){
    document.getElementById('qr-result').style.display='none';
    document.getElementById('qr-empty').style.display='block';
    return;
  }
  const url='https://api.qrserver.com/v1/create-qr-code/?size='+size+'x'+size+'&data='+encodeURIComponent(data)+'&ecc='+ecc+'&format=png&color=000000&bgcolor=ffffff&margin=1';
  const svgUrl='https://api.qrserver.com/v1/create-qr-code/?size='+size+'x'+size+'&data='+encodeURIComponent(data)+'&ecc='+ecc+'&format=svg&color=000000&bgcolor=ffffff&margin=1';
  document.getElementById('qr-img').src=url;
  document.getElementById('qr-dl-png').href=url;
  document.getElementById('qr-dl-svg').href=svgUrl;
  document.getElementById('qr-result').style.display='block';
  document.getElementById('qr-empty').style.display='none';
}
</script>
<?php tool_footer(); ?>
