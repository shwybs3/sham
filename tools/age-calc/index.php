<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'حاسبة العمر الدقيقة','url'=>TOOLS_BASE_URL.'/tools/age-calc/',
    'description'=>'احسب عمرك بالسنوات والأشهر والأيام والساعات والدقائق والثواني. حاسبة عمر عربية دقيقة ومجانية.',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('حاسبة العمر بالسنوات والأشهر والأيام — احسب عمرك الآن | yassota','احسب عمرك بدقة متناهية بالسنوات والأشهر والأيام والساعات والثواني. حاسبة عمر مجانية تدعم العربية.',$schema,'#7c3aed');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#ede9fe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/></svg>
    </div>
    <h1>حاسبة العمر الدقيقة</h1>
    <p>احسب عمرك بالسنوات والأشهر والأيام والساعات والدقائق والثواني — نتيجة فورية دقيقة.</p>
  </div>

  <div class="t-card" style="max-width:560px;margin:0 auto 24px">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
      <div>
        <label class="t-label">تاريخ الميلاد</label>
        <input type="date" id="dob" class="t-input" style="width:100%;font-size:15px">
      </div>
      <div>
        <label class="t-label">حتى تاريخ (اختياري)</label>
        <input type="date" id="todate" class="t-input" style="width:100%;font-size:15px">
        <div style="font-size:11px;color:#94a3b8;margin-top:4px">الافتراضي: اليوم</div>
      </div>
    </div>
    <button id="calc-btn" class="t-btn" style="width:100%">احسب العمر</button>
    <div id="result" style="display:none;margin-top:20px"></div>
  </div>

  <div class="t-card" style="margin-bottom:24px">
    <h2>كيف تعمل حاسبة العمر؟</h2>
    <p style="color:#64748b;line-height:1.8">حاسبة العمر تحتسب الفارق الدقيق بين تاريخ الميلاد وتاريخ اليوم (أو أي تاريخ آخر تختاره) مع مراعاة السنوات الكبيسة وعدد الأيام في كل شهر. على عكس الحاسبات البسيطة التي تقسّم عدد الأيام على 365، تحاسبتنا تعطيك النتيجة الحقيقية الكاملة: السنوات الكاملة، ثم الأشهر الكاملة المتبقية، ثم الأيام المتبقية.</p>
    <h3 style="margin-top:20px">ما استخدامات حاسبة العمر؟</h3>
    <ul style="color:#64748b;line-height:2;padding-right:20px">
      <li>معرفة عمرك الدقيق للأوراق الرسمية والوثائق</li>
      <li>حساب عمر الرضيع بالأسابيع والأيام لمتابعة النمو</li>
      <li>التحقق من الأهلية للتقديم على وظيفة أو قبول جامعي</li>
      <li>حساب عمر العقارات والمركبات والمعدات</li>
      <li>معرفة كم تبقى حتى عيد ميلادك القادم</li>
      <li>حساب أعمار الشخصيات التاريخية</li>
    </ul>
    <h3 style="margin-top:20px">السنوات الكبيسة وتأثيرها</h3>
    <p style="color:#64748b;line-height:1.8">السنة الكبيسة تحدث كل 4 سنوات (أو 400 سنة دقيقاً) وفيها فبراير 29 يوماً. حاسبتنا تراعي هذا بدقة لضمان نتيجة صحيحة 100%.</p>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>أسئلة شائعة عن حساب العمر</h2>
    <div style="display:flex;flex-direction:column;gap:12px;margin-top:8px">
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">هل تأخذ الحاسبة في الاعتبار السنوات الكبيسة؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">نعم، الحاسبة تحسب بدقة كاملة مع مراعاة السنوات الكبيسة وأيام فبراير (28 أو 29 يوماً).</p>
      </details>
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">هل يمكنني حساب عمر شخص ولد قبل عام 1900؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">نعم، الحاسبة تدعم أي تاريخ ميلاد ضمن ما يسمح به المتصفح (عادةً من عام 100 م).</p>
      </details>
      <details style="background:#f8fafc;border-radius:10px;padding:14px">
        <summary style="cursor:pointer;font-weight:700;color:#0f172a">هل تختلف طريقة حساب العمر في التقويم الهجري؟</summary>
        <p style="margin:8px 0 0;color:#64748b;line-height:1.7">هذه الحاسبة تعمل بالتقويم الميلادي. السنة الهجرية أقصر (~354 يوماً)، لذا العمر بالهجري يختلف عن الميلادي.</p>
      </details>
    </div>
  </div>
</main>

<script>
const calcBtn = document.getElementById('calc-btn');
const dobInput = document.getElementById('dob');
const toInput = document.getElementById('todate');
const result = document.getElementById('result');

// Set default to date to today
const today = new Date();
toInput.value = today.toISOString().slice(0,10);
// Set max for dob
dobInput.max = today.toISOString().slice(0,10);

calcBtn.addEventListener('click', calcAge);
dobInput.addEventListener('change', calcAge);
toInput.addEventListener('change', calcAge);

function calcAge() {
  const dob = new Date(dobInput.value);
  const to  = new Date(toInput.value || today.toISOString().slice(0,10));
  if (!dobInput.value || isNaN(dob)) { result.style.display='none'; return; }
  if (dob > to) { result.style.display='block'; result.innerHTML='<div style="color:#ef4444">تاريخ الميلاد يجب أن يكون قبل التاريخ المحدد</div>'; return; }

  let years = to.getFullYear() - dob.getFullYear();
  let months = to.getMonth() - dob.getMonth();
  let days = to.getDate() - dob.getDate();

  if (days < 0) {
    months--;
    const prevMonth = new Date(to.getFullYear(), to.getMonth(), 0);
    days += prevMonth.getDate();
  }
  if (months < 0) { years--; months += 12; }

  const totalDays = Math.floor((to - dob) / 864e5);
  const totalHours = totalDays * 24;
  const totalMins  = totalHours * 60;
  const nextBday = new Date(to.getFullYear(), dob.getMonth(), dob.getDate());
  if (nextBday <= to) nextBday.setFullYear(to.getFullYear() + 1);
  const daysToNext = Math.floor((nextBday - to) / 864e5);

  result.style.display = 'block';
  result.innerHTML = `
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px">
      ${stat(years,'سنة')}${stat(months,'شهر')}${stat(days,'يوم')}
    </div>
    <div style="background:#f8fafc;border-radius:12px;padding:14px;font-size:13px;color:#475569;line-height:2">
      <div>📅 إجمالي الأيام: <strong style="color:#0f172a">${totalDays.toLocaleString('ar')}</strong></div>
      <div>🕐 إجمالي الساعات: <strong style="color:#0f172a">${totalHours.toLocaleString('ar')}</strong></div>
      <div>⏱ إجمالي الدقائق: <strong style="color:#0f172a">${totalMins.toLocaleString('ar')}</strong></div>
      <div>🎂 أيام حتى عيد الميلاد القادم: <strong style="color:#7c3aed">${daysToNext.toLocaleString('ar')} يوم</strong></div>
    </div>`;
}
function stat(n, label) {
  return `<div style="background:linear-gradient(135deg,#7c3aed,#6d28d9);border-radius:12px;padding:16px 8px;text-align:center">
    <div style="font-size:28px;font-weight:900;color:#fff">${n}</div>
    <div style="font-size:13px;color:rgba(255,255,255,.8);margin-top:4px">${label}</div>
  </div>`;
}
// Auto calc if dob already set
if (dobInput.value) calcAge();
</script>
<?php tool_footer(); ?>
