<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'محوّل التاريخ الهجري والميلادي','url'=>TOOLS_BASE_URL.'/tools/hijri-date/',
    'description'=>'حوّل التاريخ بين الهجري والميلادي فوراً وبدقة — أداة مجانية تدعم جميع التواريخ من 1937م حتى اليوم.',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('📅 محوّل التاريخ الهجري والميلادي 2026 | تحويل فوري ودقيق | yassota','حوّل أي تاريخ بين الهجري والميلادي في ثانية واحدة — أداة مجانية دقيقة تدعم كل التواريخ، بدون تسجيل وبدون تحميل تطبيق.',$schema,'#0f766e');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#ccfbf1">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0f766e" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    </div>
    <h1>محوّل التاريخ الهجري والميلادي</h1>
    <p>حوّل أي تاريخ بين التقويمين الهجري والميلادي فوراً — دقيق ومجاني بدون تسجيل.</p>
  </div>

  <div class="t-card" style="max-width:640px;margin:0 auto 24px">
    <div style="display:flex;gap:8px;margin-bottom:18px;background:#f1f5f9;border-radius:10px;padding:4px">
      <button id="tab-g2h" class="hd-tab active" onclick="switchTab('g2h')" style="flex:1;padding:10px;border:none;border-radius:8px;background:#0f766e;color:#fff;font-weight:700;cursor:pointer;font-size:13px">ميلادي ← هجري</button>
      <button id="tab-h2g" class="hd-tab" onclick="switchTab('h2g')" style="flex:1;padding:10px;border:none;border-radius:8px;background:transparent;color:#475569;font-weight:700;cursor:pointer;font-size:13px">هجري ← ميلادي</button>
    </div>

    <div id="panel-g2h">
      <label class="t-label">اختر التاريخ الميلادي</label>
      <input type="date" id="g-input" class="t-input" style="width:100%;font-size:15px">
    </div>

    <div id="panel-h2g" style="display:none">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
        <div>
          <label class="t-label">اليوم</label>
          <input type="number" id="h-day" class="t-input" min="1" max="30" value="1" style="width:100%">
        </div>
        <div>
          <label class="t-label">الشهر</label>
          <select id="h-month" class="t-input" style="width:100%">
            <?php
            $hMonths = ['محرم','صفر','ربيع الأول','ربيع الآخر','جمادى الأولى','جمادى الآخرة','رجب','شعبان','رمضان','شوال','ذو القعدة','ذو الحجة'];
            foreach ($hMonths as $i => $m) echo '<option value="'.($i+1).'">'.$m.'</option>';
            ?>
          </select>
        </div>
        <div>
          <label class="t-label">السنة</label>
          <input type="number" id="h-year" class="t-input" min="1300" max="1500" value="1447" style="width:100%">
        </div>
      </div>
    </div>

    <button id="convert-btn" class="t-btn" style="width:100%;margin-top:16px;background:#0f766e">تحويل التاريخ</button>
    <div id="result" style="display:none;margin-top:20px"></div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>كيف يعمل محوّل التاريخ الهجري والميلادي؟</h2>
    <p style="color:#64748b;line-height:1.9">التقويم الهجري تقويم قمري يعتمد على دورة القمر حول الأرض، حيث تتكون السنة الهجرية من 12 شهراً قمرياً بمتوسط 354 يوماً تقريباً — أقصر من السنة الميلادية الشمسية بنحو 11 يوماً. هذا الفارق يجعل التواريخ الهجرية "تتحرك" عبر فصول السنة الميلادية على مدى السنين، فرمضان مثلاً يأتي أبكر بـ 10-11 يوماً كل عام مقارنة بالعام السابق.</p>
    <p style="color:#64748b;line-height:1.9;margin-top:12px">تستخدم هذه الأداة خوارزمية التقويم الهجري المدني (الحسابي) القائمة على حساب رقم اليوم الجولياني (Julian Day Number) — وهي نفس الطريقة المعتمدة في أغلب الأنظمة والتطبيقات الرقمية لتحويل التقويمين بدقة رياضية. النتيجة تقارب التقويم الهجري الرسمي (المعتمد على رؤية الهلال) بفارق يوم واحد أحياناً حسب الدولة والمرجعية الفلكية المعتمدة فيها — لذا للمناسبات الدينية الرسمية (بداية رمضان، عيد الفطر، عيد الأضحى) يُفضّل دائماً التأكد من الإعلان الرسمي لدولتك.</p>

    <h3 style="margin-top:20px">استخدامات محوّل التاريخ</h3>
    <ul style="color:#64748b;line-height:2;padding-right:20px">
      <li>تحويل تاريخ ميلادك إلى الهجري ومعرفة عمرك بالسنوات الهجرية</li>
      <li>معرفة التاريخ الهجري الموافق لعقود الزواج والوثائق الرسمية</li>
      <li>التخطيط لمواعيد دينية مثل رمضان والحج بناءً على الميلادي</li>
      <li>تحويل تواريخ الأحداث التاريخية الإسلامية إلى الميلادي للمقارنة</li>
      <li>تعبئة النماذج الحكومية التي تطلب التاريخ الهجري في بعض الدول العربية</li>
    </ul>

    <h3 style="margin-top:20px">الفرق بين الشهور الهجرية الاثني عشر</h3>
    <p style="color:#64748b;line-height:1.9">الأشهر الهجرية هي: محرم، صفر، ربيع الأول، ربيع الآخر، جمادى الأولى، جمادى الآخرة، رجب، شعبان، رمضان، شوال، ذو القعدة، وذو الحجة. من بينها أربعة أشهر حُرُم: رجب، ذو القعدة، ذو الحجة، ومحرم. رمضان هو شهر الصيام، وفي ذي الحجة تقع فريضة الحج وعيد الأضحى.</p>

    <h3 style="margin-top:20px">الخصوصية والأمان</h3>
    <p style="color:#64748b;line-height:1.9">هذه الأداة تعمل بالكامل داخل متصفحك عبر JavaScript — لا يُرسَل أي تاريخ تُدخله إلى أي خادم، ولا نحتفظ بأي بيانات. يمكنك استخدامها بلا قيود وبخصوصية تامة.</p>
  </div>

  <div class="t-card" style="margin-top:16px">
    <h2>أسئلة شائعة</h2>
    <div style="display:flex;flex-direction:column;gap:12px;margin-top:8px">
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">هل التاريخ الهجري الناتج مطابق للإعلان الرسمي؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">النتيجة حسابية دقيقة رياضياً (التقويم الهجري المدني) وقد تختلف بيوم واحد عن التقويم المعتمد على رؤية الهلال الفعلية في بعض الدول.</p>
      </details>
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">من أي سنة تدعم الأداة التحويل؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">تدعم الأداة تحويل أي تاريخ ميلادي أو هجري بدقة رياضية كاملة عبر الخوارزمية الفلكية المستخدمة.</p>
      </details>
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">هل الأداة مجانية بالكامل؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">نعم، مجانية 100% وبدون أي تسجيل أو حدود استخدام.</p>
      </details>
    </div>
  </div>
</main>

<script>
function switchTab(tab){
  document.getElementById('panel-g2h').style.display = tab==='g2h' ? 'block' : 'none';
  document.getElementById('panel-h2g').style.display = tab==='h2g' ? 'block' : 'none';
  document.getElementById('tab-g2h').style.background = tab==='g2h' ? '#0f766e' : 'transparent';
  document.getElementById('tab-g2h').style.color = tab==='g2h' ? '#fff' : '#475569';
  document.getElementById('tab-h2g').style.background = tab==='h2g' ? '#0f766e' : 'transparent';
  document.getElementById('tab-h2g').style.color = tab==='h2g' ? '#fff' : '#475569';
  document.getElementById('result').style.display = 'none';
  currentTab = tab;
}
let currentTab = 'g2h';

function gregorianToJD(year, month, day) {
  const a = Math.floor((14 - month) / 12);
  const y = year + 4800 - a;
  const m = month + 12 * a - 3;
  return day + Math.floor((153 * m + 2) / 5) + 365 * y + Math.floor(y / 4) - Math.floor(y / 100) + Math.floor(y / 400) - 32045;
}
function islamicToJD(year, month, day) {
  return day + Math.ceil(29.5 * (month - 1)) + (year - 1) * 354 + Math.floor((3 + 11 * year) / 30) + 1948440 - 1;
}
function jdToIslamic(jd) {
  jd = Math.floor(jd) + 0.5;
  const year = Math.floor((30 * (jd - 1948440) + 10646) / 10631);
  let month = Math.min(12, Math.ceil((jd - (29 + islamicToJD(year, 1, 1))) / 29.5) + 1);
  let day = jd - islamicToJD(year, month, 1) + 1;
  return {year: Math.round(year), month: Math.round(month), day: Math.round(day)};
}
function jdToGregorian(jd) {
  jd = Math.floor(jd) + 0.5;
  let a = jd + 32044;
  let b = Math.floor((4 * a + 3) / 146097);
  let c = a - Math.floor((146097 * b) / 4);
  let d = Math.floor((4 * c + 3) / 1461);
  let e = c - Math.floor((1461 * d) / 4);
  let m = Math.floor((5 * e + 2) / 153);
  const day = e - Math.floor((153 * m + 2) / 5) + 1;
  const month = m + 3 - 12 * Math.floor(m / 10);
  const year = 100 * b + d - 4800 + Math.floor(m / 10);
  return {year: Math.round(year), month: Math.round(month), day: Math.round(day)};
}

const hMonths = <?= json_encode($hMonths, JSON_UNESCAPED_UNICODE) ?>;
const gMonths = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
const gDays = ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];

document.getElementById('g-input').valueAsDate = new Date();

document.getElementById('convert-btn').addEventListener('click', () => {
  const result = document.getElementById('result');
  if (currentTab === 'g2h') {
    const val = document.getElementById('g-input').value;
    if (!val) return;
    const [y, m, d] = val.split('-').map(Number);
    const jd = gregorianToJD(y, m, d);
    const h = jdToIslamic(jd);
    const dow = gDays[new Date(y, m-1, d).getDay()];
    result.style.display = 'block';
    result.innerHTML = `
      <div style="background:linear-gradient(135deg,#0f766e,#0d9488);border-radius:14px;padding:22px;text-align:center;color:#fff">
        <div style="font-size:12px;opacity:.85;margin-bottom:6px">${dow}</div>
        <div style="font-size:26px;font-weight:900">${h.day} ${hMonths[h.month-1]} ${h.year} هـ</div>
        <div style="font-size:13px;opacity:.85;margin-top:8px">الموافق ${d} ${gMonths[m-1]} ${y} م</div>
      </div>`;
  } else {
    const d = parseInt(document.getElementById('h-day').value);
    const m = parseInt(document.getElementById('h-month').value);
    const y = parseInt(document.getElementById('h-year').value);
    if (!d || !m || !y) return;
    const jd = islamicToJD(y, m, d);
    const g = jdToGregorian(jd);
    const dow = gDays[new Date(g.year, g.month-1, g.day).getDay()];
    result.style.display = 'block';
    result.innerHTML = `
      <div style="background:linear-gradient(135deg,#0f766e,#0d9488);border-radius:14px;padding:22px;text-align:center;color:#fff">
        <div style="font-size:12px;opacity:.85;margin-bottom:6px">${dow}</div>
        <div style="font-size:26px;font-weight:900">${g.day} ${gMonths[g.month-1]} ${g.year} م</div>
        <div style="font-size:13px;opacity:.85;margin-top:8px">الموافق ${d} ${hMonths[m-1]} ${y} هـ</div>
      </div>`;
  }
});
document.getElementById('convert-btn').click();
</script>
<?php tool_footer(); ?>
