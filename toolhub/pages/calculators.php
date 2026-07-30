<section class="page-hero small">
  <div class="container">
    <nav class="breadcrumb"><a href="/">الرئيسية</a> <span>›</span> <span>حاسبات البناء</span></nav>
    <h1>🏗️ حاسبات البناء المجانية</h1>
    <p>حاسبات دقيقة للخرسانة والطوب والبلاط والدهان وحديد التسليح والصبة المسلحة — مجاناً وبدون تسجيل</p>
  </div>
</section>

<div class="container page-body">
  <div class="calc-tabs">
    <button class="ctab active" data-target="concrete">🏛️ خرسانة</button>
    <button class="ctab" data-target="bricks">🧱 طوب</button>
    <button class="ctab" data-target="tiles">🟫 بلاط</button>
    <button class="ctab" data-target="paint">🎨 دهان</button>
    <button class="ctab" data-target="steel">⚙️ حديد</button>
    <button class="ctab" data-target="area">📐 مساحة</button>
    <button class="ctab" data-target="cement">🧱 أسمنت</button>
  </div>

  <!-- Concrete -->
  <div class="calc-panel active" id="calc-concrete">
    <div class="card">
      <h2>🏛️ حاسبة الخرسانة (الصبة)</h2>
      <p>احسب كميات الأسمنت والرمل والحصى والمياه لصب الخرسانة</p>
      <div class="calc-form">
        <div class="form-row">
          <label>الطول (متر) <input type="number" id="c_len" value="5" min="0.1" step="0.1"></label>
          <label>العرض (متر) <input type="number" id="c_wid" value="4" min="0.1" step="0.1"></label>
          <label>السماكة (سم) <input type="number" id="c_thk" value="20" min="5" step="1"></label>
        </div>
        <div class="form-row">
          <label>نسبة الخلط
            <select id="c_mix">
              <option value="1:2:4">1:2:4 (عادي)</option>
              <option value="1:1.5:3">1:1.5:3 (قوي)</option>
              <option value="1:3:6">1:3:6 (خفيف)</option>
            </select>
          </label>
          <label>نوع الأسمنت
            <select id="c_type">
              <option value="50">كيس 50 كجم</option>
              <option value="40">كيس 40 كجم</option>
            </select>
          </label>
        </div>
        <button onclick="calcConcrete()" class="btn-calc">🧮 احسب الكميات</button>
      </div>
      <div id="concreteResult" class="calc-result" hidden></div>
      <div class="calc-note">
        <h3>معادلة الحساب</h3>
        <p>الحجم = الطول × العرض × السماكة (بالمتر). كمية الأسمنت = الحجم × معامل الخلط × 1440 كجم/م³</p>
      </div>
    </div>
  </div>

  <!-- Bricks -->
  <div class="calc-panel" id="calc-bricks">
    <div class="card">
      <h2>🧱 حاسبة الطوب (الأجر)</h2>
      <p>احسب عدد الطوب اللازم لبناء الجدران مع نسبة الهدر</p>
      <div class="calc-form">
        <div class="form-row">
          <label>طول الجدار (م) <input type="number" id="b_len" value="10" min="0.1" step="0.1"></label>
          <label>ارتفاع الجدار (م) <input type="number" id="b_hgt" value="3" min="0.1" step="0.1"></label>
        </div>
        <div class="form-row">
          <label>سماكة الجدار
            <select id="b_thk">
              <option value="0.12">12 سم (طوبة)</option>
              <option value="0.20" selected>20 سم (طوبتان)</option>
              <option value="0.25">25 سم</option>
            </select>
          </label>
          <label>حجم الطوبة (سم)
            <select id="b_size">
              <option value="0.25:0.12:0.06" selected>25×12×6 سم (قياسي)</option>
              <option value="0.30:0.15:0.10">30×15×10 سم (كبير)</option>
              <option value="0.20:0.10:0.05">20×10×5 سم (صغير)</option>
            </select>
          </label>
          <label>نسبة الهدر (٪) <input type="number" id="b_waste" value="10" min="0" max="20"></label>
        </div>
        <button onclick="calcBricks()" class="btn-calc">🧮 احسب</button>
      </div>
      <div id="bricksResult" class="calc-result" hidden></div>
    </div>
  </div>

  <!-- Tiles -->
  <div class="calc-panel" id="calc-tiles">
    <div class="card">
      <h2>🟫 حاسبة البلاط والسيراميك</h2>
      <p>احسب عدد قطع البلاط اللازمة لأي مساحة مع نسبة الهدر</p>
      <div class="calc-form">
        <div class="form-row">
          <label>طول الغرفة (م) <input type="number" id="t_len" value="5" min="0.1" step="0.1"></label>
          <label>عرض الغرفة (م) <input type="number" id="t_wid" value="4" min="0.1" step="0.1"></label>
        </div>
        <div class="form-row">
          <label>حجم البلاطة (سم)
            <select id="t_size">
              <option value="30">30×30 سم</option>
              <option value="40">40×40 سم</option>
              <option value="60" selected>60×60 سم</option>
              <option value="80">80×80 سم</option>
              <option value="100">100×100 سم</option>
              <option value="120">120×60 سم</option>
            </select>
          </label>
          <label>نسبة الهدر (٪) <input type="number" id="t_waste" value="10" min="5" max="30"></label>
          <label>سعر الكرتون (ر.س) <input type="number" id="t_price" value="0" min="0" step="0.5"></label>
        </div>
        <button onclick="calcTiles()" class="btn-calc">🧮 احسب</button>
      </div>
      <div id="tilesResult" class="calc-result" hidden></div>
    </div>
  </div>

  <!-- Paint -->
  <div class="calc-panel" id="calc-paint">
    <div class="card">
      <h2>🎨 حاسبة الدهان</h2>
      <p>احسب كمية الدهان اللازمة لطلاء الجدران والأسقف</p>
      <div class="calc-form">
        <div class="form-row">
          <label>طول الغرفة (م) <input type="number" id="p_len" value="5" min="1" step="0.1"></label>
          <label>عرض الغرفة (م) <input type="number" id="p_wid" value="4" min="1" step="0.1"></label>
          <label>ارتفاع السقف (م) <input type="number" id="p_hgt" value="3" min="2" step="0.1"></label>
        </div>
        <div class="form-row">
          <label>عدد الأبواب <input type="number" id="p_doors" value="1" min="0" max="10"></label>
          <label>عدد النوافذ <input type="number" id="p_wins" value="2" min="0" max="20"></label>
          <label>عدد طبقات الدهان <select id="p_coats">
            <option value="1">طبقة واحدة</option>
            <option value="2" selected>طبقتان</option>
            <option value="3">ثلاث طبقات</option>
          </select></label>
        </div>
        <button onclick="calcPaint()" class="btn-calc">🧮 احسب</button>
      </div>
      <div id="paintResult" class="calc-result" hidden></div>
    </div>
  </div>

  <!-- Steel -->
  <div class="calc-panel" id="calc-steel">
    <div class="card">
      <h2>⚙️ حاسبة حديد التسليح</h2>
      <p>احسب وزن حديد التسليح (الدوبارة) من حيث القطر والطول</p>
      <div class="calc-form">
        <div class="form-row">
          <label>قطر الحديد (مم)
            <select id="s_dia">
              <option value="8">8 مم</option>
              <option value="10">10 مم</option>
              <option value="12" selected>12 مم</option>
              <option value="14">14 مم</option>
              <option value="16">16 مم</option>
              <option value="20">20 مم</option>
              <option value="25">25 مم</option>
            </select>
          </label>
          <label>الطول الكلي (م) <input type="number" id="s_len" value="100" min="1"></label>
          <label>سعر الطن (ر.س) <input type="number" id="s_price" value="3000" min="0" step="50"></label>
        </div>
        <button onclick="calcSteel()" class="btn-calc">🧮 احسب</button>
      </div>
      <div id="steelResult" class="calc-result" hidden></div>
    </div>
  </div>

  <!-- Area -->
  <div class="calc-panel" id="calc-area">
    <div class="card">
      <h2>📐 حاسبة المساحات</h2>
      <div class="area-shapes">
        <div>
          <h3>مستطيل / مربع</h3>
          <div class="form-row">
            <label>الطول (م) <input type="number" id="ar_len" value="10" step="0.01"></label>
            <label>العرض (م) <input type="number" id="ar_wid" value="8" step="0.01"></label>
          </div>
          <button onclick="calcAreaRect()" class="btn-calc">احسب</button>
          <div id="areaRectResult" class="calc-result" hidden></div>
        </div>
        <div>
          <h3>دائرة</h3>
          <div class="form-row">
            <label>القطر (م) <input type="number" id="ar_dia" value="6" step="0.01"></label>
          </div>
          <button onclick="calcAreaCircle()" class="btn-calc">احسب</button>
          <div id="areaCircResult" class="calc-result" hidden></div>
        </div>
        <div>
          <h3>مثلث</h3>
          <div class="form-row">
            <label>القاعدة (م) <input type="number" id="ar_base" value="8" step="0.01"></label>
            <label>الارتفاع (م) <input type="number" id="ar_h" value="6" step="0.01"></label>
          </div>
          <button onclick="calcAreaTri()" class="btn-calc">احسب</button>
          <div id="areaTriResult" class="calc-result" hidden></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Cement bags -->
  <div class="calc-panel" id="calc-cement">
    <div class="card">
      <h2>🧱 حاسبة أكياس الأسمنت للطوب</h2>
      <p>احسب عدد أكياس الأسمنت اللازمة لتقديم الطوب بالملاط</p>
      <div class="calc-form">
        <div class="form-row">
          <label>عدد الطوب <input type="number" id="cm_bricks" value="1000" min="1"></label>
          <label>سماكة الملاط (سم) <input type="number" id="cm_joint" value="1.5" step="0.5" min="0.5"></label>
          <label>نسبة الخلط (أسمنت:رمل)
            <select id="cm_ratio">
              <option value="4">1:4</option>
              <option value="5" selected>1:5</option>
              <option value="6">1:6</option>
            </select>
          </label>
        </div>
        <button onclick="calcCementBags()" class="btn-calc">🧮 احسب</button>
      </div>
      <div id="cementResult" class="calc-result" hidden></div>
    </div>
  </div>

  <section class="seo-content card">
    <h2>📌 دليل استخدام حاسبات البناء</h2>
    <div class="prose">
      <h3>كيف تحسب كمية الخرسانة؟</h3>
      <p>كمية الخرسانة = الطول × العرض × السماكة. وللحصول على كميات المواد: لكل م³ خرسانة بنسبة 1:2:4 تحتاج تقريباً 8 أكياس أسمنت (50كجم) + 0.5 م³ رمل + 1 م³ حصى.</p>
      <h3>كيف تحسب عدد الطوب؟</h3>
      <p>عدد الطوب = (طول الجدار × ارتفاعه) ÷ مساحة وجه الطوبة مع الملاط. ثم أضف 10% هدراً.</p>
      <h3>وزن حديد التسليح</h3>
      <p>الوزن بالكجم/م = (القطر بالمم)² ÷ 162. مثال: حديد 12مم = 144÷162 = 0.888 كجم/م</p>
    </div>
  </section>
</div>

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"SoftwareApplication",
  "name":"حاسبات البناء — <?= h(SITE_NAME) ?>",
  "applicationCategory":"UtilitiesApplication",
  "description":"حاسبات البناء المجانية: خرسانة وطوب وبلاط ودهان وحديد تسليح",
  "url":"<?= h(base_url('calculators')) ?>",
  "inLanguage":"ar",
  "offers":{"@type":"Offer","price":"0","priceCurrency":"SAR"}
}
</script>

<script>
// Tabs
document.querySelectorAll('.ctab').forEach(tab => {
  tab.addEventListener('click', function() {
    document.querySelectorAll('.ctab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.calc-panel').forEach(p => p.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('calc-' + this.dataset.target).classList.add('active');
  });
});

function calcPaint() {
  const l = +document.getElementById('p_len').value;
  const w = +document.getElementById('p_wid').value;
  const h = +document.getElementById('p_hgt').value;
  const doors = +document.getElementById('p_doors').value;
  const wins  = +document.getElementById('p_wins').value;
  const coats = +document.getElementById('p_coats').value;
  const wallArea = 2*(l+w)*h - doors*1.9 - wins*1.5;
  const ceilArea = l*w;
  const total = (wallArea + ceilArea) * coats;
  const liters = total / 10; // ~10m² per liter
  const gallons = liters / 3.785;
  const el = document.getElementById('paintResult');
  el.hidden = false;
  el.innerHTML = `
    <div class="result-grid">
      <div class="result-item"><span>مساحة الجدران</span><strong>${wallArea.toFixed(1)} م²</strong></div>
      <div class="result-item"><span>مساحة السقف</span><strong>${ceilArea.toFixed(1)} م²</strong></div>
      <div class="result-item"><span>إجمالي المساحة (×${coats} طبقات)</span><strong>${total.toFixed(1)} م²</strong></div>
      <div class="result-item highlight"><span>كمية الدهان المطلوبة</span><strong>~${liters.toFixed(1)} لتر / ${gallons.toFixed(1)} جالون</strong></div>
    </div>`;
}

function calcSteel() {
  const dia   = +document.getElementById('s_dia').value;
  const len   = +document.getElementById('s_len').value;
  const price = +document.getElementById('s_price').value;
  const wPerM = (dia * dia) / 162; // kg/m
  const totalKg = wPerM * len;
  const totalTon = totalKg / 1000;
  const cost  = totalTon * price;
  const el = document.getElementById('steelResult');
  el.hidden = false;
  el.innerHTML = `
    <div class="result-grid">
      <div class="result-item"><span>وزن المتر الواحد</span><strong>${wPerM.toFixed(3)} كجم/م</strong></div>
      <div class="result-item highlight"><span>الوزن الكلي</span><strong>${totalKg.toFixed(1)} كجم (${totalTon.toFixed(3)} طن)</strong></div>
      ${price ? `<div class="result-item"><span>التكلفة التقديرية</span><strong>${cost.toFixed(0)} ر.س</strong></div>` : ''}
    </div>`;
}

function calcAreaRect() {
  const a = +document.getElementById('ar_len').value * +document.getElementById('ar_wid').value;
  const el = document.getElementById('areaRectResult');
  el.hidden = false;
  el.innerHTML = `<div class="result-item highlight"><span>المساحة</span><strong>${a.toFixed(2)} م²</strong></div>`;
}
function calcAreaCircle() {
  const r = +document.getElementById('ar_dia').value / 2;
  const a = Math.PI * r * r;
  const el = document.getElementById('areaCircResult');
  el.hidden = false;
  el.innerHTML = `<div class="result-item highlight"><span>المساحة</span><strong>${a.toFixed(2)} م²</strong></div>`;
}
function calcAreaTri() {
  const a = 0.5 * +document.getElementById('ar_base').value * +document.getElementById('ar_h').value;
  const el = document.getElementById('areaTriResult');
  el.hidden = false;
  el.innerHTML = `<div class="result-item highlight"><span>المساحة</span><strong>${a.toFixed(2)} م²</strong></div>`;
}

function calcCementBags() {
  const bricks = +document.getElementById('cm_bricks').value;
  const joint  = +document.getElementById('cm_joint').value / 100;
  const ratio  = +document.getElementById('cm_ratio').value;
  // mortar volume ≈ 30% of brick volume
  const brickVol = 0.25 * 0.12 * 0.06; // standard brick m³
  const mortarVol = bricks * brickVol * 0.30;
  const cementM3 = mortarVol / (1 + ratio);
  const bags = Math.ceil(cementM3 * 1440 / 50); // density 1440 kg/m³
  const sand = mortarVol - cementM3;
  const el = document.getElementById('cementResult');
  el.hidden = false;
  el.innerHTML = `
    <div class="result-grid">
      <div class="result-item highlight"><span>أكياس الأسمنت (50كجم)</span><strong>${bags} كيس</strong></div>
      <div class="result-item"><span>الرمل</span><strong>${sand.toFixed(2)} م³</strong></div>
    </div>`;
}
</script>
