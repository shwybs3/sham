<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'حاسبة سعر الذهب اليوم','url'=>TOOLS_BASE_URL.'/tools/gold-calc/',
    'description'=>'احسب سعر الذهب اليوم بجميع العيارات — 24 22 21 18 قيراط بالجرام والمثقال',
    'applicationCategory'=>'FinancialApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('حاسبة سعر الذهب اليوم — عيار 24 22 21 18 | yassota','احسب سعر الذهب اليوم بجميع العيارات بالجرام بالريال السعودي والدرهم والجنيه المصري',$schema,'#f59e0b');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#fef3c7">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
    </div>
    <h1>حاسبة سعر الذهب اليوم</h1>
    <p>احسب سعر الذهب بجميع العيارات (24، 22، 21، 18، 14 قيراط) بالعملات الخليجية والمصرية — أسعار محدّثة لحظياً.</p>
  </div>

  <div class="t-card">
    <div id="live-price" style="background:#fffbeb;border:1.5px solid #fcd34d;border-radius:10px;padding:14px 16px;margin-bottom:18px;font-size:13px;color:#92400e">
      <div id="gold-now">جارٍ تحميل سعر الذهب...</div>
    </div>

    <div class="t-row" style="margin-bottom:14px">
      <div class="t-col">
        <label class="t-label">العملة</label>
        <select id="cur" class="t-input" onchange="calcGold()">
          <option value="SAR">ريال سعودي (SAR)</option>
          <option value="AED">درهم إماراتي (AED)</option>
          <option value="EGP">جنيه مصري (EGP)</option>
          <option value="KWD">دينار كويتي (KWD)</option>
          <option value="USD">دولار أمريكي (USD)</option>
        </select>
      </div>
      <div class="t-col">
        <label class="t-label">الوزن بالجرام</label>
        <input type="number" id="grams" class="t-input" value="10" min="0.01" step="0.01" oninput="calcGold()">
      </div>
    </div>
    <label class="t-label">العيار</label>
    <div class="chip-group" style="margin-bottom:16px">
      <div class="chip" onclick="setKarat(this,24)">24 قيراط (999)</div>
      <div class="chip active" onclick="setKarat(this,22)">22 قيراط (916)</div>
      <div class="chip" onclick="setKarat(this,21)">21 قيراط (875)</div>
      <div class="chip" onclick="setKarat(this,18)">18 قيراط (750)</div>
      <div class="chip" onclick="setKarat(this,14)">14 قيراط (585)</div>
    </div>
    <div id="calc-result" style="display:none;background:#fef9c3;border:2px solid #fde047;border-radius:12px;padding:20px;text-align:center">
      <div style="font-size:13px;color:#92400e;margin-bottom:6px" id="res-label"></div>
      <div style="font-size:32px;font-weight:800;color:#78350f;font-variant-numeric:tabular-nums" id="res-val"></div>
      <div style="font-size:11px;color:#a16207;margin-top:8px" id="res-per-gram"></div>
    </div>
  </div>

  <div class="t-card">
    <h2>جدول أسعار اليوم (لكل جرام)</h2>
    <div id="price-table" style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;font-size:13px" id="pt">
        <thead><tr style="background:#fef9c3"><th style="padding:10px 12px;text-align:right;border-bottom:1px solid #e2e8f0">العيار</th><th style="padding:10px 12px;text-align:right;border-bottom:1px solid #e2e8f0">SAR/ج</th><th style="padding:10px 12px;text-align:right;border-bottom:1px solid #e2e8f0">AED/ج</th><th style="padding:10px 12px;text-align:right;border-bottom:1px solid #e2e8f0">EGP/ج</th></tr></thead>
        <tbody id="pt-body"><tr><td colspan="4" style="text-align:center;padding:20px;color:#94a3b8">جارٍ التحميل...</td></tr></tbody>
      </table>
    </div>
    <p style="font-size:11px;color:#94a3b8;margin-top:8px">المصدر: Frankfurter.app · تُحدَّث يومياً</p>
  </div>

  <?php tool_ad(); ?>
</main>

<script>
var goldUSD = 0, fxRates = {};
var karat = 22;
var karats = [{k:24,p:1},{k:22,p:22/24},{k:21,p:21/24},{k:18,p:18/24},{k:14,p:14/24}];

Promise.all([
  fetch('https://api.frankfurter.app/latest?base=USD&symbols=SAR,AED,EGP,KWD').then(function(r){return r.json();}),
  fetch('https://api.frankfurter.app/latest?base=XAU&symbols=USD').then(function(r){return r.json();})
]).then(function(results) {
  fxRates = results[0].rates; fxRates['USD'] = 1;
  goldUSD = 1 / results[1].rates['USD'];
  document.getElementById('gold-now').innerHTML = '<strong>سعر الذهب عيار 24:</strong> ' +
    (goldUSD*fxRates['SAR']/31.1035).toFixed(2) + ' ريال/جرام &nbsp;·&nbsp; ' +
    (goldUSD/31.1035).toFixed(2) + ' دولار/جرام &nbsp;·&nbsp; 1 أوقية = ' + goldUSD.toFixed(2) + ' USD';
  calcGold();
  buildTable();
}).catch(function(){
  document.getElementById('gold-now').textContent = 'تعذّر تحميل السعر، يرجى المحاولة لاحقاً';
});

function setKarat(el, k) {
  document.querySelectorAll('.chip').forEach(function(c){ c.className='chip'; });
  el.className='chip active'; karat = k; calcGold();
}

function calcGold() {
  if (!goldUSD) return;
  var g = parseFloat(document.getElementById('grams').value)||0;
  var cur = document.getElementById('cur').value;
  var rate = fxRates[cur]||1;
  var purity = karat/24;
  var pricePerGramUSD = goldUSD / 31.1035;
  var total = g * pricePerGramUSD * purity * rate;
  var perGram = pricePerGramUSD * purity * rate;
  document.getElementById('res-label').textContent = g + ' جرام ذهب عيار ' + karat;
  document.getElementById('res-val').textContent = total.toLocaleString('ar-SA',{minimumFractionDigits:2,maximumFractionDigits:2}) + ' ' + cur;
  document.getElementById('res-per-gram').textContent = 'سعر الجرام: ' + perGram.toFixed(2) + ' ' + cur;
  document.getElementById('calc-result').style.display = 'block';
}

function buildTable() {
  var body = document.getElementById('pt-body');
  body.innerHTML = '';
  karats.forEach(function(ki) {
    var pricePerGramUSD = goldUSD / 31.1035 * ki.p;
    var sar = (pricePerGramUSD*(fxRates['SAR']||3.75)).toFixed(2);
    var aed = (pricePerGramUSD*(fxRates['AED']||3.67)).toFixed(2);
    var egp = (pricePerGramUSD*(fxRates['EGP']||30)).toFixed(2);
    body.innerHTML += '<tr style="border-bottom:1px solid #f1f5f9">' +
      '<td style="padding:9px 12px;font-weight:700;color:#92400e">عيار ' + ki.k + '</td>' +
      '<td style="padding:9px 12px;font-variant-numeric:tabular-nums">' + sar + '</td>' +
      '<td style="padding:9px 12px;font-variant-numeric:tabular-nums">' + aed + '</td>' +
      '<td style="padding:9px 12px;font-variant-numeric:tabular-nums">' + egp + '</td></tr>';
  });
}
</script>
<?php tool_footer(); ?>
