<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$seoTitle = 'أسعار الصرف اليوم — تحويل العملات لحظة بلحظة | yassota';
$metaDesc = 'أسعار صرف الدولار واليورو والجنيه مقابل الريال السعودي والدرهم والجنيه المصري لحظة بلحظة مع أداة تحويل مجانية';
$canonical = url('exchange');

$schema = json_encode([
    "@context" => "https://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => [
        ["@type" => "ListItem", "position" => 1, "name" => "الرئيسية", "item" => url('')],
        ["@type" => "ListItem", "position" => 2, "name" => "أسعار الصرف", "item" => $canonical],
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
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <meta name="twitter:card" content="summary">
  <script type="application/ld+json"><?= $schema ?></script>
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189" crossorigin="anonymous"></script>
  <style>
  .rate-card{background:var(--surface);border:1px solid var(--border-c);border-radius:14px;padding:18px 16px;display:flex;align-items:center;gap:14px;transition:transform .2s,box-shadow .2s}
  .rate-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.15)}
  .rate-flag{font-size:26px;width:42px;text-align:center}
  .rate-name{font-size:13px;color:var(--muted);margin-bottom:2px}
  .rate-val{font-size:20px;font-weight:700;font-variant-numeric:tabular-nums;letter-spacing:-.5px}
  .rate-change{font-size:11px;font-weight:600;margin-top:2px}
  .conv-box{background:var(--surface);border:1px solid var(--border-c);border-radius:16px;padding:22px 20px}
  .conv-input{background:var(--bg);border:1.5px solid var(--border-c);border-radius:10px;padding:12px 14px;font-size:16px;color:var(--fg);width:100%;outline:none;direction:ltr}
  .conv-input:focus{border-color:var(--cyan)}
  .conv-select{background:var(--bg);border:1.5px solid var(--border-c);border-radius:10px;padding:11px 14px;font-size:14px;color:var(--fg);outline:none}
  .page-header{padding:32px 0 0;margin-bottom:24px}
  .breadcrumb{font-size:12px;color:var(--muted);margin-bottom:12px}
  .breadcrumb a{color:var(--muted);text-decoration:none}
  .breadcrumb a:hover{color:var(--fg)}
  .live-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;background:rgba(16,185,129,.15);color:#10b981;font-size:11px;font-weight:600;border:1px solid rgba(16,185,129,.3)}
  .live-dot{width:6px;height:6px;border-radius:50%;background:#10b981;animation:pulse 2s infinite}
  @keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
  .rates-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-bottom:24px}
  .section-title{font-size:16px;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:8px}
  .swap-btn{background:none;border:1.5px solid var(--border-c);border-radius:8px;padding:10px 12px;cursor:pointer;font-size:18px;color:var(--fg);transition:all .2s}
  .swap-btn:hover{border-color:var(--cyan);color:var(--cyan)}
  </style>
</head>
<body>
<?php render_site_header('', 'exchange'); ?>

<div class="container" style="max-width:1100px;padding:0 16px">
  <div class="page-header">
    <div class="breadcrumb"><a href="<?= h(url('')) ?>">الرئيسية</a> ← أسعار الصرف</div>
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px">
      <h1 style="font-size:26px;font-weight:800;margin:0">أسعار الصرف اليوم</h1>
      <span class="live-badge"><span class="live-dot"></span>مباشر</span>
    </div>
    <p style="color:var(--muted);font-size:14px;margin:0">أسعار صرف محدّثة لحظياً لأهم العملات العالمية — المصدر: Frankfurter.app</p>
  </div>

  <!-- Currency Converter -->
  <div class="conv-box" style="margin-bottom:24px">
    <div class="section-title">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--cyan)" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
      محوّل العملات
    </div>
    <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      <div style="flex:1;min-width:120px">
        <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">المبلغ</label>
        <input type="number" id="cv-amount" class="conv-input" value="1" min="0" step="any" oninput="doConvert()">
      </div>
      <div style="flex:1;min-width:150px">
        <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">من</label>
        <select id="cv-from" class="conv-select" style="width:100%" onchange="doConvert()">
          <option value="USD">🇺🇸 دولار USD</option>
          <option value="EUR">🇪🇺 يورو EUR</option>
          <option value="GBP">🇬🇧 جنيه GBP</option>
          <option value="SAR">🇸🇦 ريال SAR</option>
          <option value="AED">🇦🇪 درهم AED</option>
          <option value="EGP">🇪🇬 جنيه مصري EGP</option>
          <option value="KWD">🇰🇼 دينار KWD</option>
          <option value="QAR">🇶🇦 ريال قطري QAR</option>
        </select>
      </div>
      <button class="swap-btn" onclick="swapCv()" title="تبديل">⇄</button>
      <div style="flex:1;min-width:150px">
        <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">إلى</label>
        <select id="cv-to" class="conv-select" style="width:100%" onchange="doConvert()">
          <option value="SAR">🇸🇦 ريال SAR</option>
          <option value="USD">🇺🇸 دولار USD</option>
          <option value="EUR">🇪🇺 يورو EUR</option>
          <option value="EGP">🇪🇬 جنيه مصري EGP</option>
          <option value="AED">🇦🇪 درهم AED</option>
          <option value="KWD">🇰🇼 دينار KWD</option>
          <option value="GBP">🇬🇧 جنيه GBP</option>
          <option value="TRY">🇹🇷 ليرة تركية TRY</option>
        </select>
      </div>
    </div>
    <div id="cv-result" style="margin-top:14px;padding:14px 16px;background:rgba(6,182,212,.1);border:1px solid rgba(6,182,212,.2);border-radius:10px;font-size:22px;font-weight:800;color:var(--cyan);display:none;font-variant-numeric:tabular-nums"></div>
    <div id="cv-info" style="font-size:11px;color:var(--muted);margin-top:6px"></div>
    <div id="cv-loading" style="color:var(--muted);font-size:13px;padding:10px 0">جارٍ تحميل أسعار الصرف...</div>
  </div>

  <!-- Live Rates Grid -->
  <div class="section-title">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold,#f59e0b)" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
    أسعار العملات الرئيسية (مقابل الدولار)
  </div>
  <div class="rates-grid" id="rates-grid">
    <?php
    $currencies = [
      'EUR'=>['يورو','🇪🇺'],
      'GBP'=>['جنيه إسترليني','🇬🇧'],
      'SAR'=>['ريال سعودي','🇸🇦'],
      'AED'=>['درهم إماراتي','🇦🇪'],
      'EGP'=>['جنيه مصري','🇪🇬'],
      'KWD'=>['دينار كويتي','🇰🇼'],
      'QAR'=>['ريال قطري','🇶🇦'],
      'BHD'=>['دينار بحريني','🇧🇭'],
      'OMR'=>['ريال عُماني','🇴🇲'],
      'JOD'=>['دينار أردني','🇯🇴'],
      'MAD'=>['درهم مغربي','🇲🇦'],
      'TRY'=>['ليرة تركية','🇹🇷'],
    ];
    foreach ($currencies as $code => [$name, $flag]):
    ?>
    <div class="rate-card" id="card-<?= $code ?>">
      <div class="rate-flag"><?= $flag ?></div>
      <div style="flex:1">
        <div class="rate-name"><?= h($name) ?> (<?= h($code) ?>)</div>
        <div class="rate-val" id="val-<?= $code ?>">...</div>
        <div class="rate-change" id="chg-<?= $code ?>" style="color:var(--muted)">1 USD =</div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- All currencies table -->
  <div style="background:var(--surface);border:1px solid var(--border-c);border-radius:14px;padding:18px;margin-bottom:32px">
    <div class="section-title">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
      جدول أسعار كامل (150+ عملة)
    </div>
    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;font-size:13px" id="full-table">
        <thead><tr style="background:var(--bg)">
          <th style="padding:10px 12px;text-align:right;border-bottom:1px solid var(--border-c)">العملة</th>
          <th style="padding:10px 12px;text-align:right;border-bottom:1px solid var(--border-c)">الاسم</th>
          <th style="padding:10px 12px;text-align:right;border-bottom:1px solid var(--border-c)">السعر مقابل الدولار</th>
        </tr></thead>
        <tbody id="full-tbody"><tr><td colspan="3" style="text-align:center;padding:20px;color:var(--muted)">جارٍ التحميل...</td></tr></tbody>
      </table>
    </div>
    <p style="font-size:11px;color:var(--muted);margin-top:8px">المصدر: Frankfurter.app · تُحدَّث يومياً · <a href="<?= h(url('gold')) ?>" style="color:var(--cyan)">سعر الذهب اليوم</a></p>
  </div>
</div>

<?php render_site_footer(); ?>

<script>
var rates = {}, curNames = {
  EUR:'يورو',GBP:'جنيه إسترليني',SAR:'ريال سعودي',AED:'درهم إماراتي',EGP:'جنيه مصري',
  KWD:'دينار كويتي',QAR:'ريال قطري',BHD:'دينار بحريني',OMR:'ريال عُماني',JOD:'دينار أردني',
  MAD:'درهم مغربي',TRY:'ليرة تركية',CAD:'دولار كندي',AUD:'دولار أسترالي',JPY:'ين ياباني',
  CHF:'فرنك سويسري',CNY:'يوان صيني',INR:'روبية هندية',PKR:'روبية باكستانية',
  NGN:'نيرة نيجيرية',RUB:'روبل روسي',BRL:'ريال برازيلي',MXN:'بيسو مكسيكي',
};

fetch('https://api.frankfurter.app/latest?base=USD')
  .then(function(r){return r.json();})
  .then(function(d){
    rates = d.rates; rates['USD'] = 1;
    document.getElementById('cv-loading').style.display = 'none';
    document.getElementById('cv-result').style.display = 'block';
    updateCards(); doConvert(); buildFullTable(d.rates);
  })
  .catch(function(){ document.getElementById('cv-loading').textContent = 'تعذّر تحميل الأسعار'; });

function updateCards() {
  var toShow = ['EUR','GBP','SAR','AED','EGP','KWD','QAR','BHD','OMR','JOD','MAD','TRY'];
  toShow.forEach(function(c){
    var el = document.getElementById('val-'+c);
    var chg = document.getElementById('chg-'+c);
    if (el && rates[c]) {
      el.textContent = rates[c].toFixed(4);
      chg.textContent = '1 USD = ' + rates[c].toFixed(2) + ' ' + c;
    }
  });
}

function doConvert() {
  if (!Object.keys(rates).length) return;
  var amt = parseFloat(document.getElementById('cv-amount').value)||0;
  var f = document.getElementById('cv-from').value;
  var t = document.getElementById('cv-to').value;
  var inUSD = amt / (rates[f]||1);
  var out = inUSD * (rates[t]||1);
  var r = (rates[t]||1)/(rates[f]||1);
  document.getElementById('cv-result').textContent = fmt(amt) + ' ' + f + ' = ' + fmt(out) + ' ' + t;
  document.getElementById('cv-info').textContent = '1 ' + f + ' = ' + r.toFixed(4) + ' ' + t;
}

function swapCv() {
  var f=document.getElementById('cv-from'), t=document.getElementById('cv-to'), tmp=f.value;
  f.value=t.value; t.value=tmp; doConvert();
}

function fmt(n) {
  return n.toLocaleString('ar-SA', {minimumFractionDigits:2,maximumFractionDigits:4});
}

function buildFullTable(r) {
  var tbody = document.getElementById('full-tbody');
  tbody.innerHTML = '';
  Object.entries(r).sort(function(a,b){ return a[0].localeCompare(b[0]); }).forEach(function(e){
    tbody.innerHTML += '<tr style="border-bottom:1px solid var(--border-c)"><td style="padding:8px 12px;font-weight:700;direction:ltr">'+e[0]+'</td><td style="padding:8px 12px;color:var(--muted)">'+( curNames[e[0]]||e[0])+'</td><td style="padding:8px 12px;font-variant-numeric:tabular-nums;direction:ltr">'+e[1].toFixed(4)+'</td></tr>';
  });
}
</script>
</body>
</html>
