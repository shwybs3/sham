<?php
require_once dirname(__DIR__) . '/_base.php';
$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'محوّل الوحدات الشامل','url'=>TOOLS_BASE_URL.'/tools/unit/',
    'description'=>'حوّل بين وحدات الطول والوزن والحرارة والمساحة والحجم والسرعة مجاناً',
    'applicationCategory'=>'UtilitiesApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('محوّل الوحدات الشامل — طول وزن حرارة مساحة | yassota','حوّل بين وحدات الطول والوزن والحرارة والمساحة والحجم والسرعة مجاناً',$schema,'#0891b2');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#e0f2fe">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0891b2" stroke-width="2"><path d="M12 22V8M5 12H2a10 10 0 0020 0h-3"/><path d="M8.5 2.5v2M12 2v2M15.5 2.5v2"/></svg>
    </div>
    <h1>محوّل الوحدات الشامل</h1>
    <p>حوّل بين جميع وحدات القياس — الطول والوزن والحرارة والمساحة والحجم والسرعة — مجاناً.</p>
  </div>

  <div class="t-card">
    <label class="t-label">نوع التحويل</label>
    <div class="chip-group" style="margin-bottom:18px" id="cat-chips">
      <div class="chip active" onclick="setCategory(this,'length')">الطول</div>
      <div class="chip" onclick="setCategory(this,'weight')">الوزن</div>
      <div class="chip" onclick="setCategory(this,'temp')">الحرارة</div>
      <div class="chip" onclick="setCategory(this,'area')">المساحة</div>
      <div class="chip" onclick="setCategory(this,'volume')">الحجم</div>
      <div class="chip" onclick="setCategory(this,'speed')">السرعة</div>
      <div class="chip" onclick="setCategory(this,'data')">البيانات</div>
    </div>

    <div class="t-row" style="margin-bottom:14px;align-items:flex-end">
      <div class="t-col">
        <label class="t-label">القيمة</label>
        <input type="number" id="val" class="t-input" value="1" step="any" oninput="convert()">
      </div>
      <div class="t-col">
        <label class="t-label">من</label>
        <select id="from" class="t-input" onchange="convert()"></select>
      </div>
      <div style="display:flex;align-items:flex-end;padding-bottom:2px">
        <button onclick="swap()" style="background:none;border:1.5px solid #cbd5e1;border-radius:8px;padding:10px;cursor:pointer;font-size:18px">⇄</button>
      </div>
      <div class="t-col">
        <label class="t-label">إلى</label>
        <select id="to" class="t-input" onchange="convert()"></select>
      </div>
    </div>

    <div id="result" style="background:#e0f2fe;border:1.5px solid #7dd3fc;border-radius:10px;padding:16px 18px;font-size:20px;font-weight:700;color:#0c4a6e;display:none"></div>
    <div id="all-results" style="margin-top:14px;overflow-x:auto;display:none">
      <table id="all-table" style="width:100%;border-collapse:collapse;font-size:13px"></table>
    </div>
  </div>

  <?php tool_ad(); ?>
</main>

<script>
var categories = {
  length: {
    name:'الطول',
    units:{م:1,'كم':1000,'سم':0.01,'مم':0.001,'ميل':1609.34,'ياردة':0.9144,'قدم':0.3048,'بوصة':0.0254,'نانومتر':1e-9},
  },
  weight: {
    name:'الوزن',
    units:{كجم:1,'جرام':0.001,'مجم':0.000001,'طن':1000,'رطل':0.453592,'أوقية':0.0283495,'مثقال':0.004666},
  },
  temp: {name:'الحرارة', special:true, units:{'°س (مئوية)':'C','°ف (فهرنهايت)':'F','°ك (كلفن)':'K'}},
  area: {
    name:'المساحة',
    units:{'م²':1,'كم²':1e6,'سم²':0.0001,'هكتار':10000,'فدان':4046.86,'قدم²':0.092903,'بوصة²':0.00064516},
  },
  volume: {
    name:'الحجم',
    units:{'لتر':1,'مللتر':0.001,'م³':1000,'سنتيلتر':0.01,'جالون(US)':3.78541,'كوب':0.236588,'ملعقة_طعام':0.0147868},
  },
  speed: {
    name:'السرعة',
    units:{'م/ث':1,'كم/س':0.277778,'ميل/س':0.44704,'عقدة':0.514444,'ماخ':340.29},
  },
  data: {
    name:'البيانات',
    units:{'بايت':1,'كيلوبايت':1024,'ميغابايت':1048576,'جيغابايت':1073741824,'تيرابايت':1099511627776,'بت':0.125},
  }
};

var currentCat = 'length';

function setCategory(el, cat) {
  document.querySelectorAll('#cat-chips .chip').forEach(function(c){ c.className='chip'; });
  el.className='chip active';
  currentCat=cat; buildSelects(); convert();
}

function buildSelects() {
  var cat = categories[currentCat];
  var units = Object.keys(cat.units);
  var from = document.getElementById('from'), to = document.getElementById('to');
  [from,to].forEach(function(sel, idx) {
    sel.innerHTML='';
    units.forEach(function(u){ var o=document.createElement('option'); o.value=u; o.textContent=u; sel.appendChild(o); });
    sel.value = units[idx===0 ? 0 : (units.length > 1 ? 1 : 0)];
  });
}

function convert() {
  var cat = categories[currentCat];
  var v = parseFloat(document.getElementById('val').value);
  if (isNaN(v)) return;
  var f = document.getElementById('from').value;
  var t = document.getElementById('to').value;
  var out;
  if (cat.special) {
    var fc=cat.units[f], tc=cat.units[t];
    var c;
    if(fc==='C') c=v; else if(fc==='F') c=(v-32)*5/9; else c=v-273.15;
    if(tc==='C') out=c; else if(tc==='F') out=c*9/5+32; else out=c+273.15;
  } else {
    var baseF = cat.units[f], baseT = cat.units[t];
    out = v * baseF / baseT;
  }
  var formatted = Math.abs(out) < 0.0001 || Math.abs(out) > 1e8 ? out.toExponential(4) : parseFloat(out.toFixed(8)).toString();
  document.getElementById('result').textContent = v + ' ' + f + ' = ' + formatted + ' ' + t;
  document.getElementById('result').style.display='block';

  if (!cat.special) {
    var allTable = document.getElementById('all-table');
    allTable.innerHTML = '<thead><tr style="background:#f0f9ff"><th style="padding:8px 12px;text-align:right;border-bottom:1px solid #e2e8f0">الوحدة</th><th style="padding:8px 12px;text-align:right;border-bottom:1px solid #e2e8f0">النتيجة</th></tr></thead><tbody>' +
      Object.entries(cat.units).map(function(e) {
        var r = v * cat.units[f] / e[1];
        var rv = Math.abs(r)<0.0001||Math.abs(r)>1e8 ? r.toExponential(4) : parseFloat(r.toFixed(6)).toString();
        return '<tr style="border-bottom:1px solid #f1f5f9"><td style="padding:8px 12px">'+e[0]+'</td><td style="padding:8px 12px;font-variant-numeric:tabular-nums;font-weight:600;direction:ltr">'+rv+'</td></tr>';
      }).join('') + '</tbody>';
    document.getElementById('all-results').style.display='block';
  } else {
    document.getElementById('all-results').style.display='none';
  }
}

function swap() {
  var f=document.getElementById('from'), t=document.getElementById('to'), tmp=f.value;
  f.value=t.value; t.value=tmp; convert();
}

buildSelects(); convert();
</script>
<?php tool_footer(); ?>
