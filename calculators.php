<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$seoTitle = 'حاسبات البناء المجانية — خرسانة وطوب وبلاط وصبة وحديد | yassota';
$metaDesc = 'حاسبات البناء المجانية: كمية الخرسانة والطوب والبلاط والدهان وحديد التسليح والصبة المسلحة';
$canonical = url('calculators');

$schema = json_encode([
    "@context" => "https://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => [
        ["@type" => "ListItem", "position" => 1, "name" => "الرئيسية", "item" => url('')],
        ["@type" => "ListItem", "position" => 2, "name" => "حاسبات البناء", "item" => $canonical],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="<?= defined('UI_LANG') ? UI_LANG : 'ar' ?>" dir="<?= defined('UI_DIR') ? UI_DIR : 'rtl' ?>">
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
  <style>
  .calc-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;border-bottom:2px solid var(--border-c);padding-bottom:0}
  .calc-tab{padding:10px 18px;font-size:13px;font-weight:600;color:var(--muted);cursor:pointer;border:none;background:none;border-bottom:2.5px solid transparent;margin-bottom:-2px;transition:all .2s}
  .calc-tab.active{color:var(--cyan);border-bottom-color:var(--cyan)}
  .calc-panel{display:none}.calc-panel.active{display:block}
  .calc-box{background:var(--surface);border:1px solid var(--border-c);border-radius:14px;padding:22px 20px}
  .fi{display:flex;flex-direction:column;gap:4px;margin-bottom:14px}
  .fi label{font-size:12px;color:var(--muted);font-weight:600}
  .fi input,.fi select{background:var(--bg);border:1.5px solid var(--border-c);border-radius:10px;padding:10px 12px;font-size:15px;color:var(--fg);outline:none;transition:border-color .2s}
  .fi input:focus,.fi select:focus{border-color:var(--cyan)}
  .fi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-bottom:14px}
  .calc-result{background:rgba(6,182,212,.1);border:1.5px solid rgba(6,182,212,.2);border-radius:12px;padding:18px 16px;margin-top:14px;display:none}
  .calc-result.show{display:block}
  .cr-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid rgba(6,182,212,.15)}
  .cr-row:last-child{border-bottom:none;font-weight:700;font-size:16px}
  .cr-label{color:var(--muted);font-size:13px}
  .cr-val{font-weight:600;color:var(--cyan);font-variant-numeric:tabular-nums}
  .btn-calc{background:var(--cyan);color:#0f172a;border:none;padding:12px 24px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:opacity .2s;width:100%}
  .btn-calc:hover{opacity:.9}
  .page-header{padding:32px 0 0;margin-bottom:24px}
  .breadcrumb{font-size:12px;color:var(--muted);margin-bottom:12px}
  .breadcrumb a{color:var(--muted);text-decoration:none}
  </style>
</head>
<body>
<?php render_site_header('', 'calculators'); ?>

<div class="container" style="max-width:900px;padding:0 16px">
  <div class="page-header">
    <div class="breadcrumb"><a href="<?= h(url('')) ?>">الرئيسية</a> ← حاسبات البناء</div>
    <h1 style="font-size:26px;font-weight:800;margin:0 0 8px">حاسبات البناء المجانية</h1>
    <p style="color:var(--muted);font-size:14px;margin:0">احسب كميات مواد البناء بدقة — خرسانة، طوب، بلاط، دهان، حديد تسليح والمزيد</p>
  </div>

  <div class="calc-tabs" id="calc-tabs">
    <button class="calc-tab active" onclick="showCalc(this,'concrete')">الخرسانة</button>
    <button class="calc-tab" onclick="showCalc(this,'bricks')">الطوب والبلوك</button>
    <button class="calc-tab" onclick="showCalc(this,'tiles')">البلاط</button>
    <button class="calc-tab" onclick="showCalc(this,'paint')">الدهان</button>
    <button class="calc-tab" onclick="showCalc(this,'steel')">حديد التسليح</button>
    <button class="calc-tab" onclick="showCalc(this,'sand')">الرمل والبحص</button>
  </div>

  <!-- Concrete -->
  <div class="calc-panel active calc-box" id="panel-concrete">
    <h2 style="font-size:16px;margin-bottom:16px">حاسبة الخرسانة</h2>
    <div class="fi-grid">
      <div class="fi"><label>الطول (م)</label><input type="number" id="c-len" value="5" min="0.01" step="0.01"></div>
      <div class="fi"><label>العرض (م)</label><input type="number" id="c-wid" value="5" min="0.01" step="0.01"></div>
      <div class="fi"><label>الارتفاع / السماكة (م)</label><input type="number" id="c-hgt" value="0.15" min="0.01" step="0.01"></div>
      <div class="fi"><label>نوع الخلطة</label><select id="c-mix">
        <option value="1:2:4">1:2:4 عادية</option>
        <option value="1:1.5:3" selected>1:1.5:3 جيدة</option>
        <option value="1:1:2">1:1:2 عالية القوة</option>
      </select></div>
    </div>
    <button class="btn-calc" onclick="calcConcrete()">احسب الكميات</button>
    <div class="calc-result" id="r-concrete">
      <div class="cr-row"><span class="cr-label">الحجم الإجمالي</span><span class="cr-val" id="c-vol">—</span></div>
      <div class="cr-row"><span class="cr-label">الأسمنت (أكياس 50كج)</span><span class="cr-val" id="c-cement">—</span></div>
      <div class="cr-row"><span class="cr-label">الرمل (م³)</span><span class="cr-val" id="c-sand">—</span></div>
      <div class="cr-row"><span class="cr-label">الحصى/الزلط (م³)</span><span class="cr-val" id="c-gravel">—</span></div>
      <div class="cr-row"><span class="cr-label">الماء (لتر)</span><span class="cr-val" id="c-water">—</span></div>
    </div>
  </div>

  <!-- Bricks -->
  <div class="calc-panel calc-box" id="panel-bricks">
    <h2 style="font-size:16px;margin-bottom:16px">حاسبة الطوب والبلوك</h2>
    <div class="fi-grid">
      <div class="fi"><label>عرض الجدار (م)</label><input type="number" id="b-wid" value="10" min="0.1" step="0.1"></div>
      <div class="fi"><label>ارتفاع الجدار (م)</label><input type="number" id="b-hgt" value="3" min="0.1" step="0.1"></div>
      <div class="fi"><label>نوع الطوب</label><select id="b-type">
        <option value="250x120x65">طوب أحمر (25×12×6.5سم)</option>
        <option value="400x200x200" selected>بلوك خرساني (40×20×20سم)</option>
        <option value="390x190x190">بلوك أسمنتي (39×19×19سم)</option>
      </select></div>
      <div class="fi"><label>الفواصل (سم)</label><input type="number" id="b-joint" value="1" min="0.5" step="0.5"></div>
    </div>
    <button class="btn-calc" onclick="calcBricks()">احسب الكميات</button>
    <div class="calc-result" id="r-bricks">
      <div class="cr-row"><span class="cr-label">مساحة الجدار</span><span class="cr-val" id="b-area">—</span></div>
      <div class="cr-row"><span class="cr-label">عدد القطع</span><span class="cr-val" id="b-count">—</span></div>
      <div class="cr-row"><span class="cr-label">مع هدر 10%</span><span class="cr-val" id="b-waste">—</span></div>
      <div class="cr-row"><span class="cr-label">الملاط المطلوب (م³)</span><span class="cr-val" id="b-mortar">—</span></div>
    </div>
  </div>

  <!-- Tiles -->
  <div class="calc-panel calc-box" id="panel-tiles">
    <h2 style="font-size:16px;margin-bottom:16px">حاسبة البلاط والسيراميك</h2>
    <div class="fi-grid">
      <div class="fi"><label>طول الغرفة (م)</label><input type="number" id="t-rlen" value="5" min="0.1" step="0.1"></div>
      <div class="fi"><label>عرض الغرفة (م)</label><input type="number" id="t-rwid" value="4" min="0.1" step="0.1"></div>
      <div class="fi"><label>حجم البلاطة (سم × سم)</label><select id="t-size">
        <option value="30">30 × 30</option>
        <option value="40">40 × 40</option>
        <option value="50">50 × 50</option>
        <option value="60" selected>60 × 60</option>
        <option value="80">80 × 80</option>
        <option value="100">100 × 100</option>
      </select></div>
      <div class="fi"><label>هدر (%)</label><input type="number" id="t-waste" value="10" min="0" max="30"></div>
    </div>
    <button class="btn-calc" onclick="calcTiles()">احسب الكميات</button>
    <div class="calc-result" id="r-tiles">
      <div class="cr-row"><span class="cr-label">مساحة الغرفة</span><span class="cr-val" id="t-area">—</span></div>
      <div class="cr-row"><span class="cr-label">عدد البلاطات (بدون هدر)</span><span class="cr-val" id="t-base">—</span></div>
      <div class="cr-row"><span class="cr-label">عدد البلاطات (مع الهدر)</span><span class="cr-val" id="t-total">—</span></div>
      <div class="cr-row"><span class="cr-label">عدد الكراتين (10 قطع)</span><span class="cr-val" id="t-boxes">—</span></div>
    </div>
  </div>

  <!-- Paint -->
  <div class="calc-panel calc-box" id="panel-paint">
    <h2 style="font-size:16px;margin-bottom:16px">حاسبة الدهان</h2>
    <div class="fi-grid">
      <div class="fi"><label>مساحة الجدران (م²)</label><input type="number" id="p-area" value="60" min="1"></div>
      <div class="fi"><label>اطرح النوافذ والأبواب (م²)</label><input type="number" id="p-minus" value="8" min="0"></div>
      <div class="fi"><label>عدد الطبقات</label><select id="p-coats"><option value="1">طبقة واحدة</option><option value="2" selected>طبقتان</option><option value="3">ثلاث طبقات</option></select></div>
      <div class="fi"><label>تغطية الدهان (م²/لتر)</label><input type="number" id="p-cov" value="10" min="1" step="0.5"></div>
    </div>
    <button class="btn-calc" onclick="calcPaint()">احسب الكميات</button>
    <div class="calc-result" id="r-paint">
      <div class="cr-row"><span class="cr-label">المساحة الفعلية</span><span class="cr-val" id="p-net">—</span></div>
      <div class="cr-row"><span class="cr-label">دهان مطلوب (لتر)</span><span class="cr-val" id="p-liters">—</span></div>
      <div class="cr-row"><span class="cr-label">مع هدر 10% (لتر)</span><span class="cr-val" id="p-final">—</span></div>
    </div>
  </div>

  <!-- Steel -->
  <div class="calc-panel calc-box" id="panel-steel">
    <h2 style="font-size:16px;margin-bottom:16px">حاسبة حديد التسليح</h2>
    <div class="fi-grid">
      <div class="fi"><label>نوع العنصر</label><select id="s-type"><option value="beam">عتبة</option><option value="column">عمود</option><option value="slab" selected>بلاطة</option></select></div>
      <div class="fi"><label>الطول (م)</label><input type="number" id="s-len" value="5" min="0.5" step="0.1"></div>
      <div class="fi"><label>العرض (م)</label><input type="number" id="s-wid" value="4" min="0.5" step="0.1"></div>
      <div class="fi"><label>قطر الحديد الرئيسي (مم)</label><select id="s-dia"><option value="10">Ø10</option><option value="12">Ø12</option><option value="16" selected>Ø16</option><option value="20">Ø20</option><option value="25">Ø25</option></select></div>
    </div>
    <button class="btn-calc" onclick="calcSteel()">احسب الكميات</button>
    <div class="calc-result" id="r-steel">
      <div class="cr-row"><span class="cr-label">المساحة</span><span class="cr-val" id="s-area">—</span></div>
      <div class="cr-row"><span class="cr-label">وزن الحديد (كج)</span><span class="cr-val" id="s-weight">—</span></div>
      <div class="cr-row"><span class="cr-label">عدد القضبان (12م)</span><span class="cr-val" id="s-bars">—</span></div>
    </div>
  </div>

  <!-- Sand & Gravel -->
  <div class="calc-panel calc-box" id="panel-sand">
    <h2 style="font-size:16px;margin-bottom:16px">حاسبة الرمل والبحص</h2>
    <div class="fi-grid">
      <div class="fi"><label>الطول (م)</label><input type="number" id="sg-len" value="10" min="0.1" step="0.1"></div>
      <div class="fi"><label>العرض (م)</label><input type="number" id="sg-wid" value="8" min="0.1" step="0.1"></div>
      <div class="fi"><label>الارتفاع/السماكة (م)</label><input type="number" id="sg-hgt" value="0.2" min="0.01" step="0.01"></div>
      <div class="fi"><label>الكثافة (طن/م³)</label><select id="sg-den"><option value="1.5">رمل (1.5)</option><option value="1.7" selected>بحص (1.7)</option><option value="2">حجر مكسور (2.0)</option></select></div>
    </div>
    <button class="btn-calc" onclick="calcSand()">احسب الكميات</button>
    <div class="calc-result" id="r-sand">
      <div class="cr-row"><span class="cr-label">الحجم (م³)</span><span class="cr-val" id="sg-vol">—</span></div>
      <div class="cr-row"><span class="cr-label">الوزن (طن)</span><span class="cr-val" id="sg-tons">—</span></div>
    </div>
  </div>

  <!-- Tools -->
  <div style="margin:24px 0 32px;background:var(--surface);border:1px solid var(--border-c);border-radius:14px;padding:18px">
    <div style="font-size:14px;font-weight:700;margin-bottom:12px">حاسبات أخرى مفيدة</div>
    <div style="display:flex;flex-wrap:wrap;gap:10px">
      <a href="<?= h(url('solar')) ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:rgba(6,182,212,.1);border:1px solid rgba(6,182,212,.2);border-radius:8px;color:var(--cyan);text-decoration:none;font-size:13px">
        ☀️ حاسبة الطاقة الشمسية
      </a>
      <a href="<?= h(url('tools/currency')) ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);border-radius:8px;color:#f59e0b;text-decoration:none;font-size:13px">
        💱 محوّل العملات
      </a>
      <a href="<?= h(url('tools/unit')) ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.2);border-radius:8px;color:#6366f1;text-decoration:none;font-size:13px">
        📏 محوّل الوحدات
      </a>
    </div>
  </div>
</div>

<?php render_site_footer(); ?>
<script>
function showCalc(tab, id) {
  document.querySelectorAll('.calc-tab').forEach(function(t){ t.className='calc-tab'; });
  document.querySelectorAll('.calc-panel').forEach(function(p){ p.className='calc-panel calc-box'; });
  tab.className='calc-tab active';
  document.getElementById('panel-'+id).className='calc-panel active calc-box';
}
function fmt(n,d=2) { return parseFloat(n.toFixed(d)).toLocaleString('ar-SA'); }
function showR(id) { var el=document.getElementById('r-'+id); el.classList.add('show'); }
function setR(id,val) { var el=document.getElementById(id); if(el) el.textContent=val; }

function calcConcrete() {
  var L=parseFloat(document.getElementById('c-len').value), W=parseFloat(document.getElementById('c-wid').value), H=parseFloat(document.getElementById('c-hgt').value);
  var vol=L*W*H;
  var mix=document.getElementById('c-mix').value;
  var ratios={'1:2:4':[1,2,4],'1:1.5:3':[1,1.5,3],'1:1:2':[1,1,2]};
  var r=ratios[mix]||[1,1.5,3];
  var sum=r[0]+r[1]+r[2];
  var cement=Math.ceil(vol*(r[0]/sum)*1500/50);
  var sand=(vol*(r[1]/sum)).toFixed(2);
  var gravel=(vol*(r[2]/sum)).toFixed(2);
  var water=Math.round(vol*180);
  setR('c-vol',fmt(vol)+' م³'); setR('c-cement',fmt(cement)+' كيس'); setR('c-sand',sand+' م³'); setR('c-gravel',gravel+' م³'); setR('c-water',fmt(water)+' لتر');
  showR('concrete');
}

function calcBricks() {
  var W=parseFloat(document.getElementById('b-wid').value), H=parseFloat(document.getElementById('b-hgt').value);
  var area=W*H;
  var type=document.getElementById('b-type').value;
  var dims=type.split('x').map(Number);
  var j=parseFloat(document.getElementById('b-joint').value)/100;
  var bw=(dims[0]/100+j), bh=(dims[2]/100+j);
  var bPerM2=1/(bw*bh);
  var count=Math.ceil(area*bPerM2);
  var waste=Math.ceil(count*1.1);
  var mortar=(area*0.015).toFixed(3);
  setR('b-area',fmt(area)+' م²'); setR('b-count',fmt(count)+' قطعة'); setR('b-waste',fmt(waste)+' قطعة'); setR('b-mortar',mortar+' م³');
  showR('bricks');
}

function calcTiles() {
  var RL=parseFloat(document.getElementById('t-rlen').value), RW=parseFloat(document.getElementById('t-rwid').value);
  var area=RL*RW;
  var sz=parseInt(document.getElementById('t-size').value)/100;
  var waste=parseInt(document.getElementById('t-waste').value)/100;
  var base=Math.ceil(area/(sz*sz));
  var total=Math.ceil(base*(1+waste));
  var boxes=Math.ceil(total/10);
  setR('t-area',fmt(area)+' م²'); setR('t-base',fmt(base)+' قطعة'); setR('t-total',fmt(total)+' قطعة'); setR('t-boxes',fmt(boxes)+' كرتون');
  showR('tiles');
}

function calcPaint() {
  var area=parseFloat(document.getElementById('p-area').value);
  var minus=parseFloat(document.getElementById('p-minus').value)||0;
  var coats=parseInt(document.getElementById('p-coats').value);
  var cov=parseFloat(document.getElementById('p-cov').value);
  var net=area-minus;
  var liters=(net*coats/cov).toFixed(1);
  var final=Math.ceil(net*coats/cov*1.1);
  setR('p-net',fmt(net)+' م²'); setR('p-liters',liters+' لتر'); setR('p-final',final+' لتر');
  showR('paint');
}

function calcSteel() {
  var L=parseFloat(document.getElementById('s-len').value), W=parseFloat(document.getElementById('s-wid').value);
  var area=L*W;
  var dia=parseInt(document.getElementById('s-dia').value);
  var wPerM=Math.PI*(dia/2000)*(dia/2000)*7850;
  var spacing=0.2;
  var totalLen=area/spacing*2;
  var weight=(totalLen*wPerM).toFixed(1);
  var bars=Math.ceil(totalLen/12);
  setR('s-area',fmt(area)+' م²'); setR('s-weight',weight+' كج'); setR('s-bars',bars+' قضيب');
  showR('steel');
}

function calcSand() {
  var L=parseFloat(document.getElementById('sg-len').value), W=parseFloat(document.getElementById('sg-wid').value), H=parseFloat(document.getElementById('sg-hgt').value);
  var den=parseFloat(document.getElementById('sg-den').value);
  var vol=L*W*H;
  var tons=(vol*den).toFixed(2);
  setR('sg-vol',fmt(vol)+' م³'); setR('sg-tons',tons+' طن');
  showR('sand');
}
</script>
</body>
</html>
