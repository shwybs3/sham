<section class="page-hero small solar-hero">
  <div class="container">
    <nav class="breadcrumb"><a href="/">الرئيسية</a> <span>›</span> <span>الطاقة الشمسية</span></nav>
    <h1>☀️ حاسبة الطاقة الشمسية</h1>
    <p>احسب عدد الألواح الشمسية وتكلفة النظام وتوفير الكهرباء السنوي وفترة الاسترداد</p>
  </div>
</section>

<div class="container page-body">

  <!-- Main Solar Calculator -->
  <section class="card solar-main-calc" aria-labelledby="h-solar-calc">
    <h2 id="h-solar-calc">🔆 حاسبة النظام الشمسي</h2>
    <div class="solar-form">
      <div class="solar-form-grid">
        <label>
          فاتورة الكهرباء الشهرية
          <div class="input-unit"><input type="number" id="sol_bill" value="500" min="50" step="10"><span id="sol_bill_unit">ريال</span></div>
        </label>
        <label>
          الدولة / المنطقة
          <select id="sol_country" onchange="updateSolarUnits()">
            <option value="sa">🇸🇦 السعودية</option>
            <option value="ae">🇦🇪 الإمارات</option>
            <option value="eg">🇪🇬 مصر</option>
            <option value="kw">🇰🇼 الكويت</option>
            <option value="qa">🇶🇦 قطر</option>
            <option value="jo">🇯🇴 الأردن</option>
          </select>
        </label>
        <label>
          قدرة اللوح الشمسي (وات)
          <select id="sol_watt">
            <option value="400" selected>400 وات</option>
            <option value="450">450 وات</option>
            <option value="500">500 وات</option>
            <option value="550">550 وات</option>
            <option value="600">600 وات</option>
          </select>
        </label>
        <label>
          نوع النظام
          <select id="sol_type">
            <option value="ongrid">مربوط بالشبكة (On-Grid)</option>
            <option value="hybrid">هجين (Hybrid)</option>
            <option value="offgrid">منفصل (Off-Grid)</option>
          </select>
        </label>
        <label>
          نسبة الاستخدام النهاري
          <select id="sol_dayuse">
            <option value="0.7" selected>70٪ (معتدل)</option>
            <option value="0.5">50٪ (ليلي بالغالب)</option>
            <option value="0.9">90٪ (نهاري كثير)</option>
          </select>
        </label>
        <label>
          سعر اللوح الشمسي (بالعملة المحلية)
          <input type="number" id="sol_panel_cost" value="1500" min="500" step="100">
        </label>
      </div>
      <button onclick="calcSolarFull()" class="btn-primary btn-lg">
        ☀️ احسب نظامي الشمسي
      </button>
    </div>
    <div id="solarFullResult" class="solar-result-panel" hidden></div>
  </section>

  <!-- Appliance Load Calculator -->
  <section class="card" aria-labelledby="h-load">
    <h2 id="h-load">🔌 حاسبة الأحمال الكهربائية</h2>
    <p>أضف أجهزتك الكهربائية لحساب استهلاكك اليومي بدقة</p>
    <div class="appliance-list" id="appList">
      <div class="appliance-row">
        <input type="text" placeholder="اسم الجهاز" value="مكيف هواء">
        <input type="number" placeholder="القدرة (وات)" value="2000">
        <input type="number" placeholder="ساعات/يوم" value="8">
        <input type="number" placeholder="العدد" value="2">
        <button onclick="removeApp(this)" class="btn-remove">✕</button>
      </div>
      <div class="appliance-row">
        <input type="text" placeholder="اسم الجهاز" value="ثلاجة">
        <input type="number" placeholder="القدرة (وات)" value="150">
        <input type="number" placeholder="ساعات/يوم" value="24">
        <input type="number" placeholder="العدد" value="1">
        <button onclick="removeApp(this)" class="btn-remove">✕</button>
      </div>
      <div class="appliance-row">
        <input type="text" placeholder="اسم الجهاز" value="غسالة">
        <input type="number" placeholder="القدرة (وات)" value="500">
        <input type="number" placeholder="ساعات/يوم" value="2">
        <input type="number" placeholder="العدد" value="1">
        <button onclick="removeApp(this)" class="btn-remove">✕</button>
      </div>
    </div>
    <div class="app-actions">
      <button onclick="addAppliance()" class="btn-outline">+ إضافة جهاز</button>
      <button onclick="calcLoad()" class="btn-primary">احسب الأحمال</button>
    </div>
    <div id="loadResult" class="calc-result" hidden></div>
  </section>

  <!-- Solar FAQ -->
  <section class="card" aria-labelledby="h-solar-faq">
    <h2 id="h-solar-faq">❓ أسئلة شائعة عن الطاقة الشمسية</h2>
    <div class="faq-list">
      <details class="faq-item">
        <summary>كم عدد الألواح الشمسية التي أحتاجها؟</summary>
        <p>يعتمد ذلك على استهلاكك الشهري وعدد ساعات الشمس في منطقتك. استخدم الحاسبة أعلاه للحصول على تقدير دقيق. عادةً نظام 5 كيلوواط يكفي لمنزل متوسط في السعودية.</p>
      </details>
      <details class="faq-item">
        <summary>ما الفرق بين النظام المربوط بالشبكة والهجين؟</summary>
        <p>النظام المربوط بالشبكة (On-Grid) يبيع الفائض للشبكة ولكنه لا يعمل عند انقطاع الكهرباء. النظام الهجين يتضمن بطاريات ويعمل دائماً. النظام المنفصل مستقل تماماً عن الشبكة.</p>
      </details>
      <details class="faq-item">
        <summary>ما متوسط تكلفة نظام الطاقة الشمسية؟</summary>
        <p>في السعودية تتراوح تكلفة نظام 5 كيلوواط بين 18,000–30,000 ريال حسب الشركة والجودة. يسترد معظم المنازل تكلفة النظام في 5–8 سنوات.</p>
      </details>
      <details class="faq-item">
        <summary>كم تدوم الألواح الشمسية؟</summary>
        <p>معظم الألواح الشمسية مضمونة لمدة 25 سنة وتعمل 30–40 سنة. الإنتاج يتراجع بمعدل 0.5٪ سنوياً.</p>
      </details>
    </div>
  </section>

  <section class="seo-content card">
    <h2>📌 الدليل الشامل للطاقة الشمسية في المنازل العربية</h2>
    <div class="prose">
      <p>تُعدّ المملكة العربية السعودية والإمارات ومصر والأردن من أفضل دول العالم لتطبيق الطاقة الشمسية نظراً لسطوع الشمس 8-10 ساعات يومياً طوال العام. مع انخفاض أسعار الألواح الشمسية بأكثر من 90٪ خلال العقد الماضي وتوفّر خيارات التمويل، أصبح الاستثمار في الطاقة الشمسية المنزلية خياراً اقتصادياً بامتياز.</p>

      <h3>خطوات تركيب نظام طاقة شمسية منزلية</h3>
      <ol>
        <li><strong>تقييم الاستهلاك:</strong> راجع فواتيرك الكهربائية لآخر 12 شهراً واحسب متوسطها</li>
        <li><strong>تقييم السطح:</strong> يحتاج كل كيلوواط من الألواح 6-8 م² من السطح الصالح</li>
        <li><strong>اختيار النظام:</strong> On-Grid للتصدير للشبكة، Hybrid للاستقلالية الجزئية، Off-Grid للمناطق البعيدة</li>
        <li><strong>استشارة المتخصصين:</strong> اطلب عروض أسعار من 3 شركات معتمدة على الأقل</li>
        <li><strong>التمويل:</strong> كثير من البنوك الخليجية تقدم قروض طاقة شمسية بفائدة تنافسية</li>
        <li><strong>الربط بالشبكة:</strong> في السعودية تتولى شركة الكهرباء (SEC) ربط النظام وتركيب عداد العكس</li>
      </ol>

      <h3>مقارنة أنواع الألواح الشمسية</h3>
      <ul>
        <li><strong>Monocrystalline (أحادي البلورة):</strong> كفاءة 20-23٪، الأغلى ثمناً والأطول عمراً (25-30 سنة)، الأفضل للمساحات المحدودة</li>
        <li><strong>Polycrystalline (متعدد البلورات):</strong> كفاءة 15-18٪، أرخص ثمناً، مناسب للمساحات الواسعة والميزانيات المحدودة</li>
        <li><strong>Bifacial (وجهان):</strong> ينتج الكهرباء من الوجهين، أعلى إنتاجاً بنسبة 5-15٪، مثالي للأسطح البيضاء العاكسة</li>
      </ul>

      <h3>توقعات العائد على الاستثمار بالأرقام</h3>
      <p>نظام 5 كيلوواط في السعودية يُنتج تقريباً 22,000-25,000 كيلوواط ساعة سنوياً. مع سعر كهرباء 0.18 ر.س/كيلوواط ساعة، هذا يعني توفيراً سنوياً من 3,960 إلى 4,500 ريال. بتكلفة تركيب 20,000-25,000 ريال، يُسترد الاستثمار في 5-7 سنوات، ثم توفير صافٍ لـ 18-20 سنة.</p>
    </div>
  </section>
</div>

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"SoftwareApplication",
  "name":"حاسبة الطاقة الشمسية — <?= h(SITE_NAME) ?>",
  "applicationCategory":"UtilitiesApplication",
  "description":"احسب عدد الألواح الشمسية وتكلفة النظام وتوفير الكهرباء السنوي",
  "url":"<?= h(base_url('solar')) ?>",
  "inLanguage":"ar",
  "offers":{"@type":"Offer","price":"0","priceCurrency":"SAR"}
}
</script>

<script>
const solarCfg = {
  sa: {rate:0.18, sun:8.5, currency:'ريال',  label:'السعودية'},
  ae: {rate:0.23, sun:7.5, currency:'درهم',  label:'الإمارات'},
  eg: {rate:0.10, sun:8.0, currency:'جنيه',  label:'مصر'},
  kw: {rate:0.02, sun:8.0, currency:'دينار', label:'الكويت'},
  qa: {rate:0.028,sun:8.5, currency:'ريال',  label:'قطر'},
  jo: {rate:0.14, sun:7.5, currency:'دينار', label:'الأردن'},
};

function updateSolarUnits() {
  const c = solarCfg[document.getElementById('sol_country').value];
  document.getElementById('sol_bill_unit').textContent = c.currency;
}

function calcSolarFull() {
  const bill  = +document.getElementById('sol_bill').value;
  const cKey  = document.getElementById('sol_country').value;
  const watt  = +document.getElementById('sol_watt').value;
  const sType = document.getElementById('sol_type').value;
  const day   = +document.getElementById('sol_dayuse').value;
  const pCost = +document.getElementById('sol_panel_cost').value;
  const cfg   = solarCfg[cKey];

  const kwhMonth  = bill / cfg.rate;
  const kwhDay    = kwhMonth / 30;
  const systemKw  = kwhDay / (cfg.sun * 0.8);
  const numPanels = Math.ceil(systemKw * 1000 / watt);
  const actualKw  = (numPanels * watt) / 1000;
  const battBanks = sType === 'offgrid' ? Math.ceil(kwhDay / 10) : (sType === 'hybrid' ? Math.ceil(kwhDay * (1-day) / 10) : 0);
  const panelsCost = numPanels * pCost;
  const inverterCost = actualKw * 1200;
  const structCost = numPanels * 200;
  const battCost   = battBanks * 4000;
  const installCost= panelsCost * 0.15;
  const totalCost  = panelsCost + inverterCost + structCost + battCost + installCost;
  const yearlySave = bill * 12;
  const payback    = totalCost / yearlySave;
  const co2Save    = (kwhMonth * 12 * 0.5) / 1000; // tons/year

  const el = document.getElementById('solarFullResult');
  el.hidden = false;
  el.innerHTML = `
    <div class="solar-results">
      <div class="solar-highlight">
        <div class="sh-item">
          <div class="sh-num">${numPanels}</div>
          <div class="sh-label">لوح شمسي<br>${watt}وات</div>
        </div>
        <div class="sh-item">
          <div class="sh-num">${actualKw.toFixed(1)}</div>
          <div class="sh-label">كيلوواط<br>قدرة النظام</div>
        </div>
        <div class="sh-item">
          <div class="sh-num">${Math.ceil(payback)}</div>
          <div class="sh-label">سنوات<br>فترة الاسترداد</div>
        </div>
        ${battBanks > 0 ? `<div class="sh-item"><div class="sh-num">${battBanks}</div><div class="sh-label">بنك بطاريات<br>10 كيلوواط/ساعة</div></div>` : ''}
      </div>
      <div class="result-grid">
        <div class="result-item"><span>استهلاكك الشهري</span><strong>${kwhMonth.toFixed(0)} كيلوواط/ساعة</strong></div>
        <div class="result-item"><span>الاستهلاك اليومي</span><strong>${kwhDay.toFixed(1)} كيلوواط/ساعة</strong></div>
        <div class="result-item"><span>قدرة النظام</span><strong>${actualKw.toFixed(2)} كيلوواط</strong></div>
        <div class="result-item"><span>تكلفة الألواح</span><strong>${Math.ceil(panelsCost).toLocaleString()} ${cfg.currency}</strong></div>
        <div class="result-item"><span>تكلفة العاكس</span><strong>${Math.ceil(inverterCost).toLocaleString()} ${cfg.currency}</strong></div>
        ${battCost > 0 ? `<div class="result-item"><span>تكلفة البطاريات</span><strong>${Math.ceil(battCost).toLocaleString()} ${cfg.currency}</strong></div>` : ''}
        <div class="result-item"><span>التركيب والأساسات</span><strong>${Math.ceil(installCost + structCost).toLocaleString()} ${cfg.currency}</strong></div>
        <div class="result-item highlight"><span>إجمالي التكلفة</span><strong>${Math.ceil(totalCost).toLocaleString()} ${cfg.currency}</strong></div>
        <div class="result-item highlight"><span>التوفير السنوي</span><strong>${yearlySave.toLocaleString()} ${cfg.currency}/سنة</strong></div>
        <div class="result-item"><span>خفض انبعاثات CO₂</span><strong>${co2Save.toFixed(1)} طن/سنة 🌱</strong></div>
      </div>
    </div>`;
}

function calcLoad() {
  let totalKwh = 0;
  const rows = document.querySelectorAll('#appList .appliance-row');
  let html = '<table class="rates-table"><thead><tr><th>الجهاز</th><th>الاستهلاك اليومي</th><th>الشهري</th></tr></thead><tbody>';
  rows.forEach(row => {
    const inputs = row.querySelectorAll('input');
    const name = inputs[0].value || 'جهاز';
    const watt = +inputs[1].value || 0;
    const hrs  = +inputs[2].value || 0;
    const qty  = +inputs[3].value || 1;
    const daily = watt * hrs * qty / 1000;
    totalKwh += daily;
    html += `<tr><td>${name} (×${qty})</td><td>${daily.toFixed(2)} كيلوواط/ساعة</td><td>${(daily*30).toFixed(1)} كيلوواط/ساعة</td></tr>`;
  });
  html += `</tbody><tfoot><tr><td><strong>الإجمالي</strong></td><td><strong>${totalKwh.toFixed(2)} كيلوواط/ساعة</strong></td><td><strong>${(totalKwh*30).toFixed(1)} كيلوواط/ساعة</strong></td></tr></tfoot></table>`;
  const el = document.getElementById('loadResult');
  el.hidden = false;
  el.innerHTML = html;
}

function addAppliance() {
  const div = document.createElement('div');
  div.className = 'appliance-row';
  div.innerHTML = '<input type="text" placeholder="اسم الجهاز"><input type="number" placeholder="القدرة (وات)" value="100"><input type="number" placeholder="ساعات/يوم" value="4"><input type="number" placeholder="العدد" value="1"><button onclick="removeApp(this)" class="btn-remove">✕</button>';
  document.getElementById('appList').appendChild(div);
}
function removeApp(btn) { btn.parentElement.remove(); }

function calcSolar() {
  const bill = +document.getElementById('solarBill')?.value || 500;
  const country = document.getElementById('solarCountry')?.value || 'sa';
  // Simple version for homepage
  document.getElementById('sol_bill') && (document.getElementById('sol_bill').value = bill);
  document.getElementById('sol_country') && (document.getElementById('sol_country').value = country);
  calcSolarFull();
  document.getElementById('solarFullResult')?.scrollIntoView({behavior:'smooth'});
}
</script>
