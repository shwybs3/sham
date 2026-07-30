<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$seoTitle = 'حاسبة الطاقة الشمسية — كم لوح شمسي أحتاج؟ | yassota';
$metaDesc = 'احسب عدد الألواح الشمسية المطلوبة وتكلفة نظام الطاقة الشمسية ومدة الاسترداد وتوفير الكهرباء السنوي';
$canonical = url('solar');

$schema = json_encode([
    "@context" => "https://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => [
        ["@type" => "ListItem", "position" => 1, "name" => "الرئيسية", "item" => url('')],
        ["@type" => "ListItem", "position" => 2, "name" => "حاسبة الطاقة الشمسية", "item" => $canonical],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($seoTitle) ?></title>
  <meta name="description" content="<?= h($metaDesc) ?>">
  <link rel="canonical" href="<?= h($canonical) ?>">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <meta name="twitter:card" content="summary">
  <script type="application/ld+json"><?= $schema ?></script>
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189" crossorigin="anonymous"></script>
  <style>
  .solar-hero{background:linear-gradient(135deg,#0c4a6e,#0369a1);color:#e0f2fe;border-radius:18px;padding:28px 24px;margin-bottom:24px}
  .solar-box{background:var(--surface);border:1px solid var(--border-c);border-radius:14px;padding:22px 20px;margin-bottom:20px}
  .fi{display:flex;flex-direction:column;gap:4px;margin-bottom:12px}
  .fi label{font-size:12px;color:var(--muted);font-weight:600}
  .fi input,.fi select{background:var(--bg);border:1.5px solid var(--border-c);border-radius:10px;padding:10px 12px;font-size:15px;color:var(--fg);outline:none}
  .fi input:focus,.fi select:focus{border-color:var(--cyan)}
  .fi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px}
  .appliance-row{display:flex;gap:8px;align-items:center;padding:8px 0;border-bottom:1px solid var(--border-c)}
  .appliance-row:last-child{border-bottom:none}
  .btn-add{background:rgba(6,182,212,.1);border:1px solid rgba(6,182,212,.2);border-radius:8px;padding:8px 16px;color:var(--cyan);font-size:13px;cursor:pointer}
  .btn-calc{background:linear-gradient(135deg,#0284c7,#0369a1);color:#fff;border:none;padding:13px 28px;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;width:100%;margin-top:14px;transition:opacity .2s}
  .btn-calc:hover{opacity:.9}
  .result-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-top:14px}
  .res-card{background:rgba(6,182,212,.1);border:1.5px solid rgba(6,182,212,.2);border-radius:12px;padding:14px;text-align:center}
  .res-val{font-size:26px;font-weight:800;color:var(--cyan);font-variant-numeric:tabular-nums}
  .res-label{font-size:12px;color:var(--muted);margin-top:4px}
  .page-header{padding:32px 0 0;margin-bottom:24px}
  .breadcrumb{font-size:12px;color:var(--muted);margin-bottom:12px}
  .breadcrumb a{color:var(--muted);text-decoration:none}
  </style>
</head>
<body>
<?php render_site_header('', 'solar'); ?>

<div class="container" style="max-width:900px;padding:0 16px">
  <div class="page-header">
    <div class="breadcrumb"><a href="<?= h(url('')) ?>">الرئيسية</a> ← حاسبة الطاقة الشمسية</div>
    <h1 style="font-size:26px;font-weight:800;margin:0 0 8px">حاسبة الطاقة الشمسية</h1>
    <p style="color:var(--muted);font-size:14px;margin:0">احسب بدقة عدد الألواح الشمسية، سعة البطاريات، وتكلفة النظام</p>
  </div>

  <!-- Appliances -->
  <div class="solar-box">
    <h2 style="font-size:16px;margin-bottom:14px">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--cyan)" stroke-width="2" style="vertical-align:middle;margin-left:6px"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
      الأجهزة الكهربائية
    </h2>
    <div id="appliances">
      <!-- Will be populated by JS -->
    </div>
    <button class="btn-add" onclick="addAppliance()">+ إضافة جهاز</button>
  </div>

  <!-- System Settings -->
  <div class="solar-box">
    <h2 style="font-size:16px;margin-bottom:14px">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--cyan)" stroke-width="2" style="vertical-align:middle;margin-left:6px"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M5.34 18.66l-1.41 1.41M12 2v2M12 20v2M2 12h2M20 12h2M19.07 19.07l-1.41-1.41M5.34 5.34L3.93 3.93"/></svg>
      إعدادات النظام
    </h2>
    <div class="fi-grid">
      <div class="fi"><label>الدولة/المنطقة</label><select id="sun-hrs">
        <option value="5">السعودية (5 ساعات/يوم)</option>
        <option value="5.5">الإمارات (5.5 ساعات)</option>
        <option value="6">اليمن/عُمان (6 ساعات)</option>
        <option value="4.5">مصر — شمال (4.5 ساعات)</option>
        <option value="5.5">مصر — جنوب (5.5 ساعات)</option>
        <option value="4">المغرب/الجزائر (4 ساعات)</option>
      </select></div>
      <div class="fi"><label>قدرة اللوح الواحد (واط)</label><select id="panel-w">
        <option value="300">300 واط</option>
        <option value="400" selected>400 واط</option>
        <option value="500">500 واط</option>
        <option value="550">550 واط</option>
      </select></div>
      <div class="fi"><label>جهد البطاريات (فولت)</label><select id="bat-v"><option value="12">12 فولت</option><option value="24" selected>24 فولت</option><option value="48">48 فولت</option></select></div>
      <div class="fi"><label>أيام الاستقلالية</label><input type="number" id="bat-days" value="1" min="1" max="7"></div>
      <div class="fi"><label>كفاءة النظام (%)</label><input type="number" id="sys-eff" value="80" min="60" max="95"></div>
      <div class="fi"><label>سعر الكهرباء (هللة/كيلوواط)</label><input type="number" id="elec-price" value="18" min="1"></div>
    </div>
    <div class="fi-grid" style="margin-top:8px">
      <div class="fi"><label>سعر اللوح الواحد (ريال)</label><input type="number" id="panel-price" value="800" min="100"></div>
      <div class="fi"><label>سعر البطارية 200Ah (ريال)</label><input type="number" id="bat-price" value="1200" min="200"></div>
      <div class="fi"><label>سعر الإنفرتر (ريال)</label><input type="number" id="inv-price" value="3000" min="500"></div>
    </div>
    <button class="btn-calc" onclick="calcSolar()">احسب نظام الطاقة الشمسية</button>
  </div>

  <!-- Results -->
  <div class="solar-box" id="results" style="display:none">
    <h2 style="font-size:16px;margin-bottom:14px">نتائج الحساب</h2>
    <div class="result-grid" id="res-grid"></div>
    <div id="breakdown" style="margin-top:18px;font-size:13px;border:1px solid var(--border-c);border-radius:10px;padding:14px"></div>
  </div>

  <!-- Info -->
  <div class="solar-box" style="margin-bottom:32px">
    <h2 style="font-size:15px;margin-bottom:12px">نصائح لتقليل استهلاك الكهرباء</h2>
    <ul style="color:var(--muted);font-size:13px;line-height:2;padding-right:18px">
      <li>استخدم LED بدلاً من الإضاءة التقليدية (توفير 70%)</li>
      <li>ضبط المكيف على 24-26 درجة يوفر 20-30% من الاستهلاك</li>
      <li>عزل الأسقف والجدران يقلل احتياجات التبريد</li>
      <li>الأجهزة ذات الفئة A+++ أكثر كفاءة بنسبة 40-60%</li>
    </ul>
  </div>
</div>

<?php render_site_footer(); ?>
<script>
var defaultAppliances = [
  {name:'مكيف 1.5 طن',watt:1400,hrs:8},
  {name:'ثلاجة',watt:150,hrs:24},
  {name:'غسالة',watt:500,hrs:1},
  {name:'تلفزيون 55"',watt:120,hrs:5},
  {name:'إضاءة LED (10 لمبة)',watt:100,hrs:8},
  {name:'شاحن هواتف',watt:50,hrs:4},
];

var appliances = [...defaultAppliances];

function renderAppliances() {
  var el = document.getElementById('appliances');
  el.innerHTML = '';
  appliances.forEach(function(a, i) {
    el.innerHTML += '<div class="appliance-row">' +
      '<input type="text" value="' + a.name + '" style="flex:2;background:var(--bg);border:1px solid var(--border-c);border-radius:8px;padding:7px 10px;font-size:13px;color:var(--fg)" onchange="appliances['+i+'].name=this.value">' +
      '<input type="number" value="' + a.watt + '" min="1" style="flex:1;background:var(--bg);border:1px solid var(--border-c);border-radius:8px;padding:7px 10px;font-size:13px;color:var(--fg);direction:ltr" onchange="appliances['+i+'].watt=+this.value">' +
      '<span style="font-size:11px;color:var(--muted);white-space:nowrap">واط</span>' +
      '<input type="number" value="' + a.hrs + '" min="0.5" max="24" step="0.5" style="flex:1;background:var(--bg);border:1px solid var(--border-c);border-radius:8px;padding:7px 10px;font-size:13px;color:var(--fg);direction:ltr" onchange="appliances['+i+'].hrs=+this.value">' +
      '<span style="font-size:11px;color:var(--muted);white-space:nowrap">س/يوم</span>' +
      '<button onclick="appliances.splice('+i+',1);renderAppliances()" style="background:none;border:none;cursor:pointer;color:#ef4444;font-size:18px;padding:0 4px">×</button></div>';
  });
}

function addAppliance() {
  appliances.push({name:'جهاز جديد',watt:100,hrs:4});
  renderAppliances();
}

function calcSolar() {
  var totalWh = appliances.reduce(function(s,a){ return s + a.watt * a.hrs; }, 0);
  var sunHrs = parseFloat(document.getElementById('sun-hrs').value);
  var panelW = parseInt(document.getElementById('panel-w').value);
  var batV = parseInt(document.getElementById('bat-v').value);
  var batDays = parseInt(document.getElementById('bat-days').value);
  var sysEff = parseFloat(document.getElementById('sys-eff').value)/100;
  var elecPrice = parseFloat(document.getElementById('elec-price').value)/100;
  var panelPrice = parseFloat(document.getElementById('panel-price').value);
  var batPrice = parseFloat(document.getElementById('bat-price').value);
  var invPrice = parseFloat(document.getElementById('inv-price').value);

  var totalWhNeeded = totalWh / sysEff;
  var panels = Math.ceil(totalWhNeeded / (panelW * sunHrs));
  var batAh = Math.ceil((totalWh * batDays) / (batV * 0.8));
  var batCount = Math.ceil(batAh / 200);
  var peakKW = (panels * panelW / 1000).toFixed(2);
  var dailyKWh = (totalWh / 1000).toFixed(2);
  var annualKWh = (totalWh / 1000 * 365).toFixed(0);
  var annualSaving = (annualKWh * elecPrice).toFixed(0);
  var totalCost = (panels * panelPrice + batCount * batPrice + invPrice + 2000).toFixed(0);
  var payback = (totalCost / annualSaving).toFixed(1);

  var grid = document.getElementById('res-grid');
  grid.innerHTML = [
    {val:panels+' لوح',lbl:'ألواح شمسية'},
    {val:peakKW+' كيلوواط',lbl:'قدرة النظام'},
    {val:dailyKWh+' كيلوواط',lbl:'إنتاج يومي'},
    {val:batCount+' بطارية',lbl:'بطاريات (200Ah)'},
    {val:parseInt(annualSaving).toLocaleString('ar-SA')+' ريال',lbl:'توفير سنوي'},
    {val:payback+' سنة',lbl:'مدة الاسترداد'},
    {val:parseInt(totalCost).toLocaleString('ar-SA')+' ريال',lbl:'تكلفة النظام'},
    {val:(annualKWh*0.5).toFixed(0)+' كج',lbl:'توفير CO₂ سنوياً'},
  ].map(function(c){ return '<div class="res-card"><div class="res-val">'+c.val+'</div><div class="res-label">'+c.lbl+'</div></div>'; }).join('');

  var topApps = [...appliances].sort(function(a,b){ return (b.watt*b.hrs)-(a.watt*a.hrs); }).slice(0,5);
  var breakdown = document.getElementById('breakdown');
  breakdown.innerHTML = '<div style="font-weight:700;margin-bottom:8px;color:var(--fg)">أكبر مستهلكات الكهرباء:</div>' +
    topApps.map(function(a){ var kwh=(a.watt*a.hrs/1000).toFixed(2); var pct=Math.round(a.watt*a.hrs/totalWh*100); return '<div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid var(--border-c)"><span>'+a.name+'</span><span style="color:var(--cyan);font-weight:600">'+kwh+' كيلوواط/يوم ('+pct+'%)</span></div>'; }).join('') +
    '<div style="display:flex;justify-content:space-between;padding:6px 0;font-weight:700"><span>الإجمالي</span><span style="color:var(--cyan)">'+dailyKWh+' كيلوواط/يوم</span></div>';

  document.getElementById('results').style.display='block';
  document.getElementById('results').scrollIntoView({behavior:'smooth'});
}

renderAppliances();
</script>
</body>
</html>
