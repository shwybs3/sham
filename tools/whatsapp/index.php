<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'مولّد روابط واتساب','url'=>TOOLS_BASE_URL.'/tools/whatsapp/','description'=>'أنشئ رابط واتساب مباشر برقم وأي رسالة مجاناً بدون حفظ الرقم','applicationCategory'=>'CommunicationApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('مولّد رابط واتساب مباشر — بدون حفظ الرقم | yassota أدوات','أنشئ رابط واتساب مباشر لأي رقم ورسالة مخصصة — مثالي للأعمال والتسويق، مجاني 100%',$schema,'#16a34a');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#dcfce7">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="#16a34a"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    </div>
    <h1>مولّد رابط واتساب المباشر</h1>
    <p>أنشئ رابطاً مباشراً لفتح محادثة واتساب مع أي رقم ورسالة جاهزة — لا يحتاج المستخدم لحفظ الرقم أولاً.</p>
  </div>

  <div class="t-card">
    <div class="t-row">
      <div class="t-col">
        <label class="t-label">رمز الدولة</label>
        <select id="cc" class="t-input" onchange="buildLink()">
          <option value="966">🇸🇦 +966 السعودية</option>
          <option value="971">🇦🇪 +971 الإمارات</option>
          <option value="965">🇰🇼 +965 الكويت</option>
          <option value="974">🇶🇦 +974 قطر</option>
          <option value="973">🇧🇭 +973 البحرين</option>
          <option value="968">🇴🇲 +968 عُمان</option>
          <option value="20">🇪🇬 +20 مصر</option>
          <option value="962">🇯🇴 +962 الأردن</option>
          <option value="961">🇱🇧 +961 لبنان</option>
          <option value="213">🇩🇿 +213 الجزائر</option>
          <option value="212">🇲🇦 +212 المغرب</option>
          <option value="216">🇹🇳 +216 تونس</option>
          <option value="218">🇱🇾 +218 ليبيا</option>
          <option value="249">🇸🇩 +249 السودان</option>
          <option value="1">🇺🇸 +1 USA/Canada</option>
          <option value="44">🇬🇧 +44 UK</option>
          <option value="49">🇩🇪 +49 Germany</option>
          <option value="33">🇫🇷 +33 France</option>
          <option value="91">🇮🇳 +91 India</option>
          <option value="55">🇧🇷 +55 Brazil</option>
          <option value="34">🇪🇸 +34 Spain</option>
          <option value="39">🇮🇹 +39 Italy</option>
          <option value="7">🇷🇺 +7 Russia</option>
          <option value="86">🇨🇳 +86 China</option>
          <option value="81">🇯🇵 +81 Japan</option>
          <option value="82">🇰🇷 +82 Korea</option>
          <option value="90">🇹🇷 +90 Turkey</option>
          <option value="92">🇵🇰 +92 Pakistan</option>
          <option value="234">🇳🇬 +234 Nigeria</option>
          <option value="27">🇿🇦 +27 South Africa</option>
        </select>
      </div>
      <div class="t-col">
        <label class="t-label">رقم الهاتف</label>
        <input type="tel" id="phone" class="t-input" placeholder="5XXXXXXXX" oninput="buildLink()">
      </div>
    </div>
    <div style="margin-top:12px">
      <label class="t-label">الرسالة المُعبَّأة مسبقاً (اختياري)</label>
      <textarea id="msg" class="t-textarea" rows="3" placeholder="مرحباً، أودّ الاستفسار عن..." oninput="buildLink()"></textarea>
    </div>
    <div style="margin-top:16px">
      <label class="t-label">الرابط الناتج</label>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <input type="text" id="result-link" class="t-input" readonly style="font-family:monospace;font-size:12px;flex:1" placeholder="أدخل رقماً للحصول على الرابط">
        <button class="t-btn" onclick="copyLink()"><span id="copy-lbl">نسخ</span></button>
        <a id="open-link" href="#" target="_blank" rel="noopener" class="t-btn t-btn-success" style="display:none">فتح واتساب</a>
      </div>
    </div>
  </div>

  <div class="t-card" id="templ-card">
    <h2>قوالب رسائل جاهزة</h2>
    <div class="chip-group">
      <button class="chip" onclick="setMsg('مرحباً، أودّ الاستفسار عن خدماتكم.')">استفسار خدمات</button>
      <button class="chip" onclick="setMsg('مرحباً، أريد طلب منتج.')">طلب منتج</button>
      <button class="chip" onclick="setMsg('مرحباً، هل ما زلتم تقبلون حجوزات؟')">حجز موعد</button>
      <button class="chip" onclick="setMsg('مرحباً، رأيت إعلانكم وأودّ معرفة المزيد.')">من الإعلان</button>
      <button class="chip" onclick="setMsg('مرحباً، هل المنتج متاح؟')">استفسار توفر</button>
      <button class="chip" onclick="setMsg('مرحباً، أريد الحصول على عرض سعر.')">طلب عرض سعر</button>
    </div>
  </div>

  <?php tool_ad(); ?>
</main>
<script>
function buildLink(){
  var cc=document.getElementById('cc').value;
  var phone=document.getElementById('phone').value.replace(/[^0-9]/g,'');
  var msg=document.getElementById('msg').value;
  if(!phone){document.getElementById('result-link').value='';document.getElementById('open-link').style.display='none';return;}
  var full=cc+phone;
  var url='https://wa.me/'+full+(msg?'?text='+encodeURIComponent(msg):'');
  document.getElementById('result-link').value=url;
  var a=document.getElementById('open-link');a.href=url;a.style.display='inline-flex';
}
function copyLink(){
  var v=document.getElementById('result-link').value;if(!v)return;
  if(navigator.clipboard){navigator.clipboard.writeText(v).then(function(){document.getElementById('copy-lbl').textContent='✓ تم';setTimeout(function(){document.getElementById('copy-lbl').textContent='نسخ';},2000);});}
  else{var t=document.createElement('textarea');t.value=v;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);document.getElementById('copy-lbl').textContent='✓ تم';}
}
function setMsg(m){document.getElementById('msg').value=m;buildLink();}
</script>
<?php tool_footer(); ?>
