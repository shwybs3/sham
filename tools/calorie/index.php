<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode(['@context'=>'https://schema.org','@type'=>'WebApplication','name'=>'حاسبة السعرات الحرارية والاحتياج اليومي','url'=>TOOLS_BASE_URL.'/tools/calorie/','description'=>'احسب احتياجك اليومي من السعرات الحرارية (BMR وTDEE) وخطتك لفقدان الوزن أو اكتسابه.','applicationCategory'=>'HealthApplication','operatingSystem'=>'All','offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD']]);
tool_head('حاسبة السعرات الحرارية اليومية — BMR وTDEE لفقدان الوزن | yassota','احسب معدل الأيض الأساسي (BMR) واحتياجك اليومي الحقيقي من السعرات (TDEE) بناءً على وزنك ونشاطك. مجانية وفورية.',$schema,'#f97316');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#fff7ed"><span style="font-size:24px">🔥</span></div>
    <h1>حاسبة السعرات الحرارية</h1>
    <p>احسب احتياجك اليومي من السعرات وخطتك المثالية لتحقيق هدفك الصحي.</p>
  </div>
  <div class="t-card" style="max-width:580px;margin:0 auto 24px">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
      <div><label class="t-label">الجنس</label>
        <select id="gender" class="t-input" style="width:100%"><option value="male">ذكر</option><option value="female">أنثى</option></select></div>
      <div><label class="t-label">العمر (سنة)</label>
        <input type="number" id="age" class="t-input" value="25" min="10" max="100" style="width:100%"></div>
      <div><label class="t-label">الوزن (كغ)</label>
        <input type="number" id="weight" class="t-input" value="70" min="20" max="300" step="0.5" style="width:100%"></div>
      <div><label class="t-label">الطول (سم)</label>
        <input type="number" id="height" class="t-input" value="170" min="100" max="250" style="width:100%"></div>
    </div>
    <label class="t-label">مستوى النشاط</label>
    <select id="activity" class="t-input" style="width:100%;margin-bottom:14px">
      <option value="1.2">خامل (لا رياضة)</option>
      <option value="1.375">خفيف (1-3 أيام أسبوعياً)</option>
      <option value="1.55" selected>معتدل (3-5 أيام)</option>
      <option value="1.725">مكثف (6-7 أيام)</option>
      <option value="1.9">رياضي محترف (مرتين يومياً)</option>
    </select>
    <button onclick="calculate()" class="t-btn" style="width:100%;background:#f97316">احسب</button>
    <div id="result" style="display:none;margin-top:20px">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
        <div style="background:#fff7ed;border-radius:10px;padding:14px;text-align:center">
          <div style="font-size:11px;color:#64748b">BMR — معدل الأيض الأساسي</div>
          <div style="font-size:28px;font-weight:900;color:#f97316" id="bmr-val"></div>
          <div style="font-size:11px;color:#94a3b8">سعرة / يوم</div>
        </div>
        <div style="background:#fef3c7;border-radius:10px;padding:14px;text-align:center">
          <div style="font-size:11px;color:#64748b">TDEE — الاحتياج الفعلي</div>
          <div style="font-size:28px;font-weight:900;color:#d97706" id="tdee-val"></div>
          <div style="font-size:11px;color:#94a3b8">سعرة / يوم</div>
        </div>
      </div>
      <h3 style="font-size:14px;margin-bottom:10px">خطط التحكم بالوزن:</h3>
      <div style="display:grid;gap:8px" id="plans"></div>
    </div>
  </div>
  <?php tool_ad(); ?>
  <div class="t-card">
    <h2>ما هو BMR وTDEE؟</h2>
    <p style="color:#64748b;line-height:1.8"><strong>BMR (Basal Metabolic Rate)</strong> هو عدد السعرات التي يحرقها جسمك أثناء الراحة التامة للحفاظ على وظائفه الحيوية (التنفس، ضربات القلب، التفكير). <strong>TDEE (Total Daily Energy Expenditure)</strong> هو مجموع ما تحرقه يومياً بعد احتساب نشاطك البدني — وهو العدد الفعلي الذي يجب أن تستهدفه في نظامك الغذائي.</p>
    <h3 style="margin-top:14px">قاعدة Mifflin-St Jeor (الأدق):</h3>
    <div style="background:#f8fafc;border-radius:8px;padding:12px;font-size:13px;color:#475569;direction:ltr;font-family:monospace">
      ذكر: BMR = 10×الوزن(كغ) + 6.25×الطول(سم) − 5×العمر + 5<br>
      أنثى: BMR = 10×الوزن(كغ) + 6.25×الطول(سم) − 5×العمر − 161
    </div>
  </div>
</main>
<script>
function calculate(){
  const g=document.getElementById('gender').value;
  const age=+document.getElementById('age').value;
  const w=+document.getElementById('weight').value;
  const h=+document.getElementById('height').value;
  const act=+document.getElementById('activity').value;
  const bmr = g==='male' ? 10*w+6.25*h-5*age+5 : 10*w+6.25*h-5*age-161;
  const tdee = Math.round(bmr*act);
  const bmrR = Math.round(bmr);
  document.getElementById('bmr-val').textContent = bmrR.toLocaleString('ar');
  document.getElementById('tdee-val').textContent = tdee.toLocaleString('ar');
  const plans=[
    {name:'فقدان سريع (0.5 كغ/أسبوع)',cal:tdee-500,color:'#dc2626',bg:'#fee2e2'},
    {name:'فقدان متوسط (0.25 كغ/أسبوع)',cal:tdee-250,color:'#d97706',bg:'#fef3c7'},
    {name:'الحفاظ على الوزن',cal:tdee,color:'#059669',bg:'#d1fae5'},
    {name:'زيادة الوزن (0.25 كغ/أسبوع)',cal:tdee+250,color:'#2563eb',bg:'#dbeafe'},
  ];
  document.getElementById('plans').innerHTML = plans.map(p=>`
    <div style="background:${p.bg};border-radius:10px;padding:12px;display:flex;justify-content:space-between;align-items:center">
      <span style="font-size:13px;color:${p.color};font-weight:600">${p.name}</span>
      <span style="font-size:18px;font-weight:900;color:${p.color};font-family:monospace">${p.cal.toLocaleString('ar')} سعرة</span>
    </div>`).join('');
  document.getElementById('result').style.display='block';
}
calculate();
</script>
<?php tool_footer(); ?>
