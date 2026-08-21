<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'حاسبة مؤشر كتلة الجسم BMI','url'=>TOOLS_BASE_URL.'/tools/bmi/',
    'description'=>'احسب مؤشر كتلة جسمك BMI واعرف وزنك المثالي وتصنيفك الصحي. حاسبة BMI مجانية تدعم الكيلوغرام والرطل.',
    'applicationCategory'=>'HealthApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('حاسبة BMI مؤشر كتلة الجسم — وزنك المثالي وتصنيفك الصحي | yassota','احسب مؤشر كتلة جسمك BMI بالكيلوغرام أو الرطل واعرف إن كنت بوزن مثالي أو تحتاج للمتابعة الصحية.',$schema,'#059669');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#d1fae5">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M18 8h1a4 4 0 010 8h-1"/><path d="M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
    </div>
    <h1>حاسبة مؤشر كتلة الجسم BMI</h1>
    <p>احسب مؤشر كتلة جسمك، واعرف تصنيفك الصحي ووزنك المثالي الموصى به.</p>
  </div>

  <div class="t-card" style="max-width:520px;margin:0 auto 24px">
    <div style="display:flex;gap:10px;margin-bottom:16px">
      <button id="unit-kg" class="t-btn" style="flex:1;background:#059669">كيلوغرام / سنتيمتر</button>
      <button id="unit-lb" class="t-btn" style="flex:1;background:#f1f5f9;color:#475569">رطل / بوصة</button>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
      <div>
        <label class="t-label" id="weight-label">الوزن (كيلوغرام)</label>
        <input type="number" id="weight" class="t-input" placeholder="70" min="1" max="500" style="width:100%">
      </div>
      <div>
        <label class="t-label" id="height-label">الطول (سنتيمتر)</label>
        <input type="number" id="height" class="t-input" placeholder="170" min="50" max="300" style="width:100%">
      </div>
    </div>
    <div style="margin-bottom:16px">
      <label class="t-label">الجنس (للوزن المثالي)</label>
      <div style="display:flex;gap:10px;margin-top:6px">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer"><input type="radio" name="gender" value="m" checked> ذكر</label>
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer"><input type="radio" name="gender" value="f"> أنثى</label>
      </div>
    </div>
    <button id="calc-btn" class="t-btn" style="width:100%;background:#059669">احسب BMI</button>
    <div id="result" style="display:none;margin-top:20px"></div>
  </div>

  <div class="t-card" style="margin-bottom:24px">
    <h2>جدول تصنيف BMI العالمي (WHO)</h2>
    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;font-size:14px">
        <thead><tr style="background:#f1f5f9">
          <th style="padding:10px 14px;text-align:right;font-weight:700">التصنيف</th>
          <th style="padding:10px 14px;text-align:right;font-weight:700">BMI</th>
          <th style="padding:10px 14px;text-align:right;font-weight:700">المخاطر الصحية</th>
        </tr></thead>
        <tbody>
          <?php $rows = [
            ['نقص حاد في الوزن','أقل من 16','مخاطر صحية عالية جداً','#fee2e2','#dc2626'],
            ['نقص في الوزن','16 – 18.4','مخاطر صحية متوسطة','#fef3c7','#d97706'],
            ['وزن طبيعي','18.5 – 24.9','مخاطر منخفضة (الأفضل)','#d1fae5','#059669'],
            ['زيادة في الوزن','25 – 29.9','مخاطر صحية متوسطة','#fef3c7','#d97706'],
            ['سمنة من الدرجة الأولى','30 – 34.9','مخاطر صحية عالية','#fee2e2','#dc2626'],
            ['سمنة من الدرجة الثانية','35 – 39.9','مخاطر صحية عالية جداً','#fee2e2','#dc2626'],
            ['سمنة مفرطة','40 فأكثر','مخاطر صحية خطيرة جداً','#fecaca','#991b1b'],
          ]; foreach ($rows as [$cat,$range,$risk,$bg,$col]): ?>
          <tr style="background:<?= $bg ?>">
            <td style="padding:9px 14px;font-weight:700;color:<?= $col ?>"><?= $cat ?></td>
            <td style="padding:9px 14px;color:#475569;font-variant-numeric:tabular-nums"><?= $range ?></td>
            <td style="padding:9px 14px;color:#475569"><?= $risk ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p style="color:#94a3b8;font-size:12px;margin-top:10px">* هذه الحاسبة للتوعية الصحية فقط وليست بديلاً عن استشارة طبيب متخصص.</p>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card">
    <h2>ما هو مؤشر كتلة الجسم BMI؟</h2>
    <p style="color:#64748b;line-height:1.8">مؤشر كتلة الجسم (Body Mass Index) هو قياس يُستخدم عالمياً لتقدير كمية الدهون في الجسم بناءً على الوزن والطول. ابتكره العالم البلجيكي أدولف كيتلي في القرن التاسع عشر واعتمدته منظمة الصحة العالمية (WHO) معياراً دولياً للفرز الصحي السريع.</p>
    <h3 style="margin-top:16px">معادلة حساب BMI</h3>
    <div style="background:#f8fafc;border-radius:10px;padding:14px;font-family:monospace;font-size:14px;color:#0f172a;text-align:center;margin:8px 0">BMI = الوزن (كغ) ÷ [الطول (م)]²</div>
    <h3 style="margin-top:16px">حدود BMI كمؤشر</h3>
    <p style="color:#64748b;line-height:1.8">لا يفرّق BMI بين الكتلة العضلية والدهنية، ويتأثر بالجنس والعمر والعرق. لذا، الرياضيون قد يُصنَّفون "زيادة وزن" بسبب كتلتهم العضلية رغم صحتهم الجيدة، كما أن المسنّين قد يُصنَّفون "طبيعيين" رغم زيادة نسبة الدهون.</p>
  </div>
</main>

<script>
let useMetric = true;
document.getElementById('unit-kg').addEventListener('click', () => {
  useMetric = true;
  document.getElementById('unit-kg').style.background = '#059669'; document.getElementById('unit-kg').style.color = '#fff';
  document.getElementById('unit-lb').style.background = '#f1f5f9'; document.getElementById('unit-lb').style.color = '#475569';
  document.getElementById('weight-label').textContent = 'الوزن (كيلوغرام)';
  document.getElementById('height-label').textContent = 'الطول (سنتيمتر)';
  document.getElementById('weight').placeholder = '70';
  document.getElementById('height').placeholder = '170';
});
document.getElementById('unit-lb').addEventListener('click', () => {
  useMetric = false;
  document.getElementById('unit-lb').style.background = '#059669'; document.getElementById('unit-lb').style.color = '#fff';
  document.getElementById('unit-kg').style.background = '#f1f5f9'; document.getElementById('unit-kg').style.color = '#475569';
  document.getElementById('weight-label').textContent = 'الوزن (رطل)';
  document.getElementById('height-label').textContent = 'الطول (بوصة)';
  document.getElementById('weight').placeholder = '154';
  document.getElementById('height').placeholder = '67';
});

document.getElementById('calc-btn').addEventListener('click', () => {
  let w = parseFloat(document.getElementById('weight').value);
  let h = parseFloat(document.getElementById('height').value);
  const gender = document.querySelector('[name=gender]:checked').value;
  if (!w || !h || w<=0 || h<=0) { alert('أدخل الوزن والطول'); return; }
  if (!useMetric) { w = w * 0.453592; h = h * 2.54; }
  const hm = h / 100;
  const bmi = w / (hm * hm);
  const ideal = gender === 'm' ? 22 * hm * hm : 21.5 * hm * hm;
  let cat, color, bg;
  if (bmi < 16) { cat='نقص حاد في الوزن'; color='#991b1b'; bg='#fee2e2'; }
  else if (bmi < 18.5) { cat='نقص في الوزن'; color='#d97706'; bg='#fef3c7'; }
  else if (bmi < 25) { cat='وزن طبيعي مثالي ✅'; color='#059669'; bg='#d1fae5'; }
  else if (bmi < 30) { cat='زيادة في الوزن'; color='#d97706'; bg='#fef3c7'; }
  else if (bmi < 35) { cat='سمنة الدرجة الأولى'; color='#dc2626'; bg='#fee2e2'; }
  else if (bmi < 40) { cat='سمنة الدرجة الثانية'; color='#dc2626'; bg='#fee2e2'; }
  else { cat='سمنة مفرطة'; color='#991b1b'; bg='#fecaca'; }
  const pct = Math.min(100, Math.max(0, (bmi-10) / 30 * 100));
  const res = document.getElementById('result');
  res.style.display = 'block';
  res.innerHTML = `
    <div style="background:${bg};border-radius:14px;padding:20px;text-align:center;margin-bottom:14px">
      <div style="font-size:48px;font-weight:900;color:${color}">${bmi.toFixed(1)}</div>
      <div style="font-size:16px;font-weight:700;color:${color};margin-top:4px">${cat}</div>
    </div>
    <div style="height:10px;background:#f1f5f9;border-radius:5px;overflow:hidden;margin-bottom:6px">
      <div style="height:100%;width:${pct}%;background:${color};border-radius:5px;transition:width .4s"></div>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:11px;color:#94a3b8;margin-bottom:14px">
      <span>نحيل 16</span><span>مثالي 18.5</span><span>زائد 25</span><span>سمنة 30+</span>
    </div>
    <div style="background:#f8fafc;border-radius:10px;padding:12px;font-size:13px;color:#475569;line-height:2">
      <div>⚖️ وزنك الحالي: <strong>${w.toFixed(1)} كغ</strong> | طولك: <strong>${h.toFixed(1)} سم</strong></div>
      <div>🎯 الوزن المثالي المقترح: <strong style="color:#059669">${(ideal-2).toFixed(1)} – ${(ideal+2).toFixed(1)} كغ</strong></div>
      <div>📊 mؤشر BMI: <strong>${bmi.toFixed(2)}</strong></div>
    </div>`;
});
</script>
<?php tool_footer(); ?>
