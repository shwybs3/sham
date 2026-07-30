<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'محوّل العملات لحظة بلحظة','url'=>TOOLS_BASE_URL.'/tools/currency/',
    'description'=>'حوّل بين العملات العالمية بأسعار صرف لحظية مجاناً',
    'applicationCategory'=>'FinancialApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('محوّل العملات — أسعار الصرف اللحظية مجاناً | yassota','حوّل بين الدولار والريال والدرهم واليورو وأكثر من 150 عملة بأسعار صرف حقيقية ومحدّثة',$schema,'#f59e0b');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#fef3c7">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
    </div>
    <h1>محوّل العملات اللحظي</h1>
    <p>حوّل بين أكثر من 150 عملة عالمية بأسعار صرف محدّثة لحظياً — مجاني تماماً بدون حد استخدام.</p>
  </div>

  <div class="t-card">
    <div class="t-row" style="margin-bottom:14px;align-items:flex-end">
      <div class="t-col">
        <label class="t-label">المبلغ</label>
        <input type="number" id="amount" class="t-input" value="1" min="0" step="any" oninput="convert()">
      </div>
      <div class="t-col">
        <label class="t-label">من</label>
        <select id="from-cur" class="t-input" onchange="convert()"></select>
      </div>
      <div style="display:flex;align-items:flex-end;padding-bottom:2px">
        <button onclick="swapCur()" style="background:none;border:1.5px solid #cbd5e1;border-radius:8px;padding:10px;cursor:pointer;font-size:18px" title="تبديل">⇄</button>
      </div>
      <div class="t-col">
        <label class="t-label">إلى</label>
        <select id="to-cur" class="t-input" onchange="convert()"></select>
      </div>
    </div>
    <div id="result" style="background:#fffbeb;border:1.5px solid #fcd34d;border-radius:10px;padding:16px 18px;font-size:20px;font-weight:700;color:#92400e;margin-bottom:14px;display:none"></div>
    <div id="rate-info" style="font-size:12px;color:#94a3b8"></div>
    <div id="loading" style="color:#94a3b8;font-size:13px;padding:10px 0">جارٍ تحميل أسعار الصرف...</div>
  </div>

  <div class="t-card">
    <h2 style="margin-bottom:14px">جدول المقارنة السريعة</h2>
    <div id="compare-table" style="display:none">
      <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px" id="ct"></table>
      </div>
      <p style="font-size:11px;color:#94a3b8;margin-top:10px">المصدر: Frankfurter.app — تُحدَّث يومياً</p>
    </div>
  </div>

  <?php tool_ad(); ?>
</main>

<script>
var rates = {}, baseCur = 'USD';
var mainCurs = ['USD','EUR','GBP','SAR','AED','EGP','KWD','QAR','BHD','OMR','JOD','MAD','TRY','CAD','AUD','JPY','CHF','CNY'];
var names = {USD:'دولار أمريكي',EUR:'يورو',GBP:'جنيه إسترليني',SAR:'ريال سعودي',AED:'درهم إماراتي',EGP:'جنيه مصري',KWD:'دينار كويتي',QAR:'ريال قطري',BHD:'دينار بحريني',OMR:'ريال عُماني',JOD:'دينار أردني',MAD:'درهم مغربي',TRY:'ليرة تركية',CAD:'دولار كندي',AUD:'دولار أسترالي',JPY:'ين ياباني',CHF:'فرنك سويسري',CNY:'يوان صيني'};

fetch('https://api.frankfurter.app/latest?base=USD')
  .then(function(r){return r.json();})
  .then(function(d){
    rates = d.rates; rates['USD'] = 1;
    document.getElementById('loading').style.display='none';
    document.getElementById('result').style.display='block';
    buildSelects();
    convert();
    buildCompare();
  }).catch(function(){
    document.getElementById('loading').textContent='تعذّر تحميل الأسعار — تحقق من اتصالك بالإنترنت';
  });

function buildSelects() {
  var from = document.getElementById('from-cur');
  var to = document.getElementById('to-cur');
  [from,to].forEach(function(sel,idx){
    sel.innerHTML='';
    mainCurs.forEach(function(c){
      var opt = document.createElement('option');
      opt.value=c; opt.textContent=c+' — '+(names[c]||c);
      sel.appendChild(opt);
    });
    sel.value = idx===0 ? 'USD' : 'SAR';
  });
}

function convert() {
  if (!Object.keys(rates).length) return;
  var amt = parseFloat(document.getElementById('amount').value)||0;
  var f = document.getElementById('from-cur').value;
  var t = document.getElementById('to-cur').value;
  var inUSD = amt / (rates[f]||1);
  var out = inUSD * (rates[t]||1);
  var r = (rates[t]||1) / (rates[f]||1);
  document.getElementById('result').textContent = fmt(amt) + ' ' + f + ' = ' + fmt(out) + ' ' + t;
  document.getElementById('rate-info').textContent = '1 ' + f + ' = ' + r.toFixed(4) + ' ' + t + ' · 1 ' + t + ' = ' + (1/r).toFixed(4) + ' ' + f;
}

function swapCur() {
  var f=document.getElementById('from-cur'), t=document.getElementById('to-cur'), tmp=f.value;
  f.value=t.value; t.value=tmp; convert();
}

function fmt(n) {
  return n.toLocaleString('ar-SA',{minimumFractionDigits:2,maximumFractionDigits:4});
}

function buildCompare() {
  var f = document.getElementById('from-cur').value;
  var amount = parseFloat(document.getElementById('amount').value)||1;
  var ct = document.getElementById('ct');
  ct.innerHTML='<tr style="background:#f8fafc"><th style="padding:10px 12px;text-align:right;font-size:12px;color:#64748b;border-bottom:1px solid #e2e8f0">العملة</th><th style="padding:10px 12px;text-align:right;font-size:12px;color:#64748b;border-bottom:1px solid #e2e8f0">المعدل</th><th style="padding:10px 12px;text-align:right;font-size:12px;color:#64748b;border-bottom:1px solid #e2e8f0">' + amount + ' ' + f + '</th></tr>';
  mainCurs.slice(0,12).forEach(function(c){
    if (c===f) return;
    var r = (rates[c]||1)/(rates[f]||1);
    ct.innerHTML += '<tr style="border-bottom:1px solid #f1f5f9"><td style="padding:9px 12px;font-size:13px">' + c + ' <span style="color:#94a3b8;font-size:11px">'+( names[c]||'')+'</span></td><td style="padding:9px 12px;font-size:13px;font-variant-numeric:tabular-nums">' + r.toFixed(4) + '</td><td style="padding:9px 12px;font-size:13px;font-weight:600;font-variant-numeric:tabular-nums">' + fmt(amount*r) + '</td></tr>';
  });
  document.getElementById('compare-table').style.display='block';
}
</script>
<?php tool_footer(); ?>
