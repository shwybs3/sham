<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$seoTitle = 'أسعار الذهب اليوم — عيار 24 22 21 18 قيراط | yassota';
$metaDesc = 'سعر الذهب اليوم بالجرام لجميع العيارات (24 22 21 18 14 قيراط) بالريال السعودي والدرهم الإماراتي والجنيه المصري';
$canonical = url('gold');

$schema = json_encode([
    "@context" => "https://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => [
        ["@type" => "ListItem", "position" => 1, "name" => "الرئيسية", "item" => url('')],
        ["@type" => "ListItem", "position" => 2, "name" => "أسعار الذهب", "item" => $canonical],
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
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <meta name="twitter:card" content="summary">
  <script type="application/ld+json"><?= $schema ?></script>
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189" crossorigin="anonymous"></script>
  <style>
  .gold-hero{background:linear-gradient(135deg,#78350f,#92400e);color:#fef9c3;border-radius:18px;padding:28px 24px;margin-bottom:24px;position:relative;overflow:hidden}
  .gold-hero::before{content:'◆';position:absolute;font-size:120px;opacity:.08;right:-20px;top:-20px;color:#fde047}
  .gold-card{background:var(--surface);border:1px solid var(--border-c);border-radius:14px;padding:18px 16px;text-align:center}
  .gold-karat{font-size:12px;color:var(--muted);margin-bottom:4px;font-weight:600;letter-spacing:.5px}
  .gold-price{font-size:22px;font-weight:800;color:#d97706;font-variant-numeric:tabular-nums}
  .gold-unit{font-size:11px;color:var(--muted);margin-top:2px}
  .page-header{padding:32px 0 0;margin-bottom:24px}
  .breadcrumb{font-size:12px;color:var(--muted);margin-bottom:12px}
  .breadcrumb a{color:var(--muted);text-decoration:none}
  .section-title{font-size:16px;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:8px}
  .karats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:24px}
  .gold-calc-box{background:var(--surface);border:1px solid var(--border-c);border-radius:16px;padding:22px 20px;margin-bottom:24px}
  .gold-input{background:var(--bg);border:1.5px solid var(--border-c);border-radius:10px;padding:11px 14px;font-size:16px;color:var(--fg);width:100%;outline:none}
  .gold-input:focus{border-color:#d97706}
  .gold-select{background:var(--bg);border:1.5px solid var(--border-c);border-radius:10px;padding:11px 14px;font-size:14px;color:var(--fg);outline:none}
  </style>
</head>
<body>
<?php render_site_header('', 'gold'); ?>

<div class="container" style="max-width:1100px;padding:0 16px">
  <div class="page-header">
    <div class="breadcrumb"><a href="<?= h(url('')) ?>">الرئيسية</a> ← أسعار الذهب</div>
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px">
      <h1 style="font-size:26px;font-weight:800;margin:0">أسعار الذهب اليوم</h1>
      <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;background:rgba(217,119,6,.15);color:#d97706;font-size:11px;font-weight:600;border:1px solid rgba(217,119,6,.3)">
        <span style="width:6px;height:6px;border-radius:50%;background:#d97706;animation:pulse 2s infinite;display:inline-block"></span>محدّث
      </span>
    </div>
    <p style="color:var(--muted);font-size:14px;margin:0" id="meta-line">سعر الذهب بجميع العيارات — المصدر: Frankfurter.app</p>
  </div>

  <!-- Hero price block -->
  <div class="gold-hero" style="margin-bottom:24px">
    <div style="font-size:13px;opacity:.7;margin-bottom:8px">سعر الأوقية الترويانية (عيار 24)</div>
    <div style="font-size:40px;font-weight:900;font-variant-numeric:tabular-nums;margin-bottom:6px" id="hero-price">...</div>
    <div style="font-size:14px;opacity:.8">الجرام: <span id="hero-gram">...</span> · الأوقية: <span id="hero-oz">...</span> دولار</div>
  </div>

  <!-- Karats grid -->
  <div class="section-title">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
    سعر الجرام لكل عيار — بالريال السعودي
  </div>
  <div class="karats-grid" id="karats-grid">
    <?php foreach ([24,22,21,18,14,10] as $k): ?>
    <div class="gold-card">
      <div class="gold-karat">عيار <?= $k ?> قيراط</div>
      <div class="gold-price" id="sar-<?= $k ?>">...</div>
      <div class="gold-unit">ريال / جرام</div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Calculator -->
  <div class="gold-calc-box">
    <div class="section-title">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="16" y1="10" x2="8" y2="10"/><line x1="11" y1="14" x2="16" y2="14"/><line x1="8" y1="18" x2="16" y2="18"/></svg>
      حاسبة سعر الذهب
    </div>
    <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:14px">
      <div style="flex:1;min-width:120px">
        <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">الوزن بالجرام</label>
        <input type="number" id="g-weight" class="gold-input" value="10" min="0.01" step="0.01" oninput="calcGold()">
      </div>
      <div style="flex:1;min-width:150px">
        <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">العيار</label>
        <select id="g-karat" class="gold-select" style="width:100%" onchange="calcGold()">
          <option value="24">24 قيراط (999)</option>
          <option value="22" selected>22 قيراط (916)</option>
          <option value="21">21 قيراط (875)</option>
          <option value="18">18 قيراط (750)</option>
          <option value="14">14 قيراط (585)</option>
          <option value="10">10 قيراط (417)</option>
        </select>
      </div>
      <div style="flex:1;min-width:150px">
        <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px">العملة</label>
        <select id="g-cur" class="gold-select" style="width:100%" onchange="calcGold()">
          <option value="SAR">ريال سعودي (SAR)</option>
          <option value="AED">درهم إماراتي (AED)</option>
          <option value="EGP">جنيه مصري (EGP)</option>
          <option value="KWD">دينار كويتي (KWD)</option>
          <option value="USD">دولار أمريكي (USD)</option>
        </select>
      </div>
    </div>
    <div id="g-result" style="padding:16px;background:rgba(217,119,6,.1);border:1.5px solid rgba(217,119,6,.3);border-radius:12px;font-size:24px;font-weight:800;color:#92400e;font-variant-numeric:tabular-nums;display:none"></div>
    <div id="g-perunit" style="font-size:12px;color:var(--muted);margin-top:6px"></div>
    <div id="g-loading" style="color:var(--muted);font-size:13px;padding:10px 0">جارٍ تحميل السعر...</div>
  </div>

  <!-- Multi-currency table -->
  <div style="background:var(--surface);border:1px solid var(--border-c);border-radius:14px;padding:18px;margin-bottom:32px">
    <div class="section-title">جدول أسعار الذهب بجميع العملات (لكل جرام)</div>
    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr style="background:var(--bg)">
          <th style="padding:10px 12px;text-align:right;border-bottom:1px solid var(--border-c)">العيار</th>
          <th style="padding:10px 12px;text-align:right;border-bottom:1px solid var(--border-c)">SAR</th>
          <th style="padding:10px 12px;text-align:right;border-bottom:1px solid var(--border-c)">AED</th>
          <th style="padding:10px 12px;text-align:right;border-bottom:1px solid var(--border-c)">EGP</th>
          <th style="padding:10px 12px;text-align:right;border-bottom:1px solid var(--border-c)">KWD</th>
          <th style="padding:10px 12px;text-align:right;border-bottom:1px solid var(--border-c)">USD</th>
        </tr></thead>
        <tbody id="gold-tbody"><tr><td colspan="6" style="text-align:center;padding:20px;color:var(--muted)">جارٍ التحميل...</td></tr></tbody>
      </table>
    </div>
    <p style="font-size:11px;color:var(--muted);margin-top:8px">تُحدَّث يومياً · <a href="<?= h(url('exchange')) ?>" style="color:var(--cyan)">أسعار الصرف</a></p>
  </div>
</div>

<?php render_site_footer(); ?>
<style>@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}</style>
<script>
var goldUSD = 0, fx = {};
var karats = [{k:24,p:1},{k:22,p:22/24},{k:21,p:21/24},{k:18,p:18/24},{k:14,p:14/24},{k:10,p:10/24}];

Promise.all([
  fetch('https://api.frankfurter.app/latest?base=USD&symbols=SAR,AED,EGP,KWD,OMR,QAR').then(function(r){return r.json();}),
  fetch('https://api.frankfurter.app/latest?base=XAU&symbols=USD').then(function(r){return r.json();})
]).then(function(res) {
  fx = res[0].rates; fx['USD'] = 1;
  goldUSD = 1 / res[1].rates['USD'];
  var gPerGramUSD = goldUSD / 31.1035;
  document.getElementById('hero-oz').textContent = Math.round(goldUSD).toLocaleString('ar-SA');
  document.getElementById('hero-gram').textContent = (gPerGramUSD * fx['SAR']).toFixed(2) + ' ريال';
  document.getElementById('hero-price').textContent = Math.round(goldUSD).toLocaleString('ar-SA') + ' دولار';
  document.getElementById('meta-line').textContent = 'آخر تحديث: ' + new Date().toLocaleDateString('ar-SA');
  karats.forEach(function(ki) {
    var el = document.getElementById('sar-' + ki.k);
    if (el) el.textContent = (gPerGramUSD * ki.p * (fx['SAR']||3.75)).toFixed(2);
  });
  buildTable(gPerGramUSD);
  document.getElementById('g-loading').style.display = 'none';
  document.getElementById('g-result').style.display = 'block';
  calcGold();
}).catch(function(){ document.getElementById('g-loading').textContent = 'تعذّر تحميل السعر'; });

function calcGold() {
  if (!goldUSD) return;
  var g = parseFloat(document.getElementById('g-weight').value)||0;
  var k = parseInt(document.getElementById('g-karat').value)||22;
  var cur = document.getElementById('g-cur').value;
  var purity = k / 24;
  var gPerGramUSD = goldUSD / 31.1035;
  var total = g * gPerGramUSD * purity * (fx[cur]||1);
  var perGram = gPerGramUSD * purity * (fx[cur]||1);
  document.getElementById('g-result').textContent = total.toLocaleString('ar-SA',{minimumFractionDigits:2,maximumFractionDigits:2}) + ' ' + cur;
  document.getElementById('g-perunit').textContent = 'سعر الجرام الواحد: ' + perGram.toFixed(2) + ' ' + cur + ' · سعر المثقال: ' + (perGram * 4.68).toFixed(2) + ' ' + cur;
}

function buildTable(gPerGramUSD) {
  var tbody = document.getElementById('gold-tbody');
  tbody.innerHTML = '';
  karats.forEach(function(ki) {
    tbody.innerHTML += '<tr style="border-bottom:1px solid var(--border-c)">' +
      '<td style="padding:9px 12px;font-weight:700;color:#92400e">عيار ' + ki.k + '</td>' +
      ['SAR','AED','EGP','KWD','USD'].map(function(c) {
        return '<td style="padding:9px 12px;font-variant-numeric:tabular-nums">' + (gPerGramUSD * ki.p * (fx[c]||1)).toFixed(2) + '</td>';
      }).join('') + '</tr>';
  });
}
</script>
</body>
</html>
