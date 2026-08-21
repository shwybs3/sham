<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'حاسبة القروض والتمويل','url'=>TOOLS_BASE_URL.'/tools/loan/',
    'description'=>'احسب أقساطك الشهرية والفائدة الإجمالية على أي قرض أو تمويل. حاسبة قروض مجانية مع جدول السداد الكامل.',
    'applicationCategory'=>'FinanceApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('حاسبة القروض والأقساط الشهرية — احسب تمويلك الآن | yassota','احسب القسط الشهري والفائدة الإجمالية لأي قرض أو تمويل بسهولة. جدول سداد كامل وخطة دفع واضحة.',$schema,'#2563eb');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#dbeafe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
    </div>
    <h1>حاسبة القروض والتمويل</h1>
    <p>احسب القسط الشهري والفائدة الإجمالية وجدول السداد الكامل لأي قرض أو تمويل.</p>
  </div>

  <div class="t-card" style="max-width:580px;margin:0 auto 24px">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
      <div>
        <label class="t-label">مبلغ القرض</label>
        <input type="number" id="principal" class="t-input" value="100000" min="1" style="width:100%">
      </div>
      <div>
        <label class="t-label">العملة</label>
        <select id="currency" class="t-input" style="width:100%">
          <option value="ل.س">ل.س (ليرة سورية)</option>
          <option value="$" selected>$ (دولار أمريكي)</option>
          <option value="ر.س">ر.س (ريال سعودي)</option>
          <option value="د.إ">د.إ (درهم)</option>
          <option value="ج.م">ج.م (جنيه مصري)</option>
          <option value="د.ك">د.ك (دينار كويتي)</option>
          <option value="₺">₺ (ليرة تركية)</option>
        </select>
      </div>
      <div>
        <label class="t-label">نسبة الفائدة السنوية (%)</label>
        <input type="number" id="rate" class="t-input" value="5" min="0" max="100" step="0.1" style="width:100%">
      </div>
      <div>
        <label class="t-label">مدة القرض (بالأشهر)</label>
        <input type="number" id="months" class="t-input" value="36" min="1" max="600" style="width:100%">
      </div>
    </div>
    <button id="calc-btn" class="t-btn" style="width:100%;background:#2563eb">احسب</button>
    <div id="result" style="display:none;margin-top:20px"></div>
    <div id="schedule-wrap" style="display:none;margin-top:16px">
      <button id="toggle-schedule" style="background:none;border:1px solid #e2e8f0;border-radius:8px;padding:8px 16px;font-size:13px;cursor:pointer;width:100%">عرض جدول السداد الكامل ▼</button>
      <div id="schedule" style="display:none;margin-top:12px;overflow-x:auto"></div>
    </div>
  </div>

  <div class="t-card" style="margin-bottom:24px">
    <h2>كيف تُحسب الأقساط الشهرية؟</h2>
    <p style="color:#64748b;line-height:1.8">تستخدم الحاسبة معادلة القسط المتساوي (Amortization Formula)، وهي الطريقة الأكثر شيوعاً في البنوك ومؤسسات التمويل:</p>
    <div style="background:#f8fafc;border-radius:10px;padding:14px;font-family:monospace;font-size:13px;color:#0f172a;text-align:center;margin:10px 0;direction:ltr">
      M = P × [r(1+r)ⁿ] / [(1+r)ⁿ − 1]
    </div>
    <p style="color:#64748b;font-size:13px">حيث: M = القسط الشهري، P = المبلغ الأصلي، r = نسبة الفائدة الشهرية (السنوية ÷ 12)، n = عدد الأقساط</p>
    <h3 style="margin-top:16px">نصائح لتخفيض تكلفة القرض</h3>
    <ul style="color:#64748b;line-height:2;padding-right:20px">
      <li>كلما كانت مدة القرض أقصر، قلّت الفائدة الإجمالية — حتى لو ارتفع القسط الشهري</li>
      <li>دفع مبالغ إضافية على أصل الدين يُسرّع السداد ويوفّر فائدة كبيرة</li>
      <li>قارن بين عدة بنوك ومؤسسات تمويل قبل التوقيع</li>
      <li>احذر من الرسوم الإدارية والغرامات المخفية التي تزيد التكلفة الفعلية</li>
    </ul>
  </div>

  <?php tool_ad(); ?>
</main>

<script>
document.getElementById('calc-btn').addEventListener('click', calc);

function calc() {
  const P = parseFloat(document.getElementById('principal').value);
  const annualRate = parseFloat(document.getElementById('rate').value);
  const n = parseInt(document.getElementById('months').value);
  const cur = document.getElementById('currency').value;
  if (!P || P<=0 || n<=0) { alert('أدخل بيانات صحيحة'); return; }

  const r = annualRate / 100 / 12;
  let monthly, totalPay, totalInt;
  if (r === 0) {
    monthly = P / n;
    totalPay = P;
    totalInt = 0;
  } else {
    monthly = P * (r * Math.pow(1+r,n)) / (Math.pow(1+r,n)-1);
    totalPay = monthly * n;
    totalInt = totalPay - P;
  }

  const fmt = v => v.toLocaleString('ar', {maximumFractionDigits:2});
  const res = document.getElementById('result');
  res.style.display = 'block';
  res.innerHTML = `
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px">
      ${statCard('القسط الشهري', fmt(monthly)+' '+cur, '#2563eb', '#dbeafe')}
      ${statCard('إجمالي الفائدة', fmt(totalInt)+' '+cur, '#dc2626', '#fee2e2')}
      ${statCard('إجمالي المدفوعات', fmt(totalPay)+' '+cur, '#059669', '#d1fae5')}
    </div>
    <div style="height:12px;background:#f1f5f9;border-radius:6px;overflow:hidden">
      <div style="height:100%;width:${Math.min(100,(P/totalPay*100)).toFixed(1)}%;background:#2563eb;border-radius:6px"></div>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:12px;color:#94a3b8;margin-top:4px">
      <span>أصل الدين ${(P/totalPay*100).toFixed(1)}%</span>
      <span>فائدة ${(totalInt/totalPay*100).toFixed(1)}%</span>
    </div>`;

  // Build schedule
  const sw = document.getElementById('schedule-wrap');
  sw.style.display = 'block';
  const sched = document.getElementById('schedule');
  let rows = `<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="background:#f1f5f9">
      <th style="padding:8px;text-align:right">القسط</th>
      <th style="padding:8px;text-align:right">المدفوع</th>
      <th style="padding:8px;text-align:right">أصل</th>
      <th style="padding:8px;text-align:right">فائدة</th>
      <th style="padding:8px;text-align:right">الرصيد</th>
    </tr></thead><tbody>`;
  let bal = P;
  for (let i=1; i<=n; i++) {
    const int = bal * r;
    const prin = r===0 ? P/n : monthly - int;
    bal -= prin;
    if (bal<0) bal=0;
    rows += `<tr style="background:${i%2?'#fff':'#f8fafc'}">
      <td style="padding:7px 8px">${i}</td>
      <td style="padding:7px 8px">${fmt(monthly)}</td>
      <td style="padding:7px 8px;color:#2563eb">${fmt(prin)}</td>
      <td style="padding:7px 8px;color:#dc2626">${fmt(int)}</td>
      <td style="padding:7px 8px">${fmt(bal)}</td>
    </tr>`;
  }
  rows += '</tbody></table>';
  sched.innerHTML = rows;
}

document.getElementById('toggle-schedule').addEventListener('click', () => {
  const s = document.getElementById('schedule');
  s.style.display = s.style.display === 'none' ? 'block' : 'none';
  document.getElementById('toggle-schedule').textContent = s.style.display==='none' ? 'عرض جدول السداد الكامل ▼' : 'إخفاء جدول السداد ▲';
});

function statCard(label, val, color, bg) {
  return `<div style="background:${bg};border-radius:12px;padding:14px;text-align:center">
    <div style="font-size:13px;color:#64748b;margin-bottom:6px">${label}</div>
    <div style="font-size:16px;font-weight:700;color:${color}">${val}</div>
  </div>`;
}
calc();
</script>
<?php tool_footer(); ?>
