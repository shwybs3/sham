<?php
require_once dirname(__DIR__) . '/_base.php';

/* ── Server-side rates proxy ── */
if (isset($_GET['action']) && $_GET['action'] === 'rates') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    header('Access-Control-Allow-Origin: *');

    $cacheFile = sys_get_temp_dir() . '/yas_currency_rates.json';
    $cacheAge  = file_exists($cacheFile) ? (time() - filemtime($cacheFile)) : PHP_INT_MAX;

    if ($cacheAge < 3600) {
        echo file_get_contents($cacheFile);
        exit;
    }

    $caBundle = '/root/.ccr/ca-bundle.crt';
    $sslOpts  = is_file($caBundle) ? ['ssl' => ['cafile' => $caBundle, 'verify_peer' => true, 'verify_peer_name' => true]] : [];

    // Primary: fawazahmed0 via jsDelivr (includes SYP, no key, reliable CDN)
    $ctx1 = stream_context_create(array_merge(['http' => ['timeout' => 10, 'user_agent' => 'yassota-tools/1.0']], $sslOpts));
    $raw1 = @file_get_contents('https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json', false, $ctx1);
    $rates = [];
    $rateDate = date('Y-m-d');
    if ($raw1) {
        $d1 = json_decode($raw1, true);
        if (!empty($d1['usd']) && is_array($d1['usd'])) {
            foreach ($d1['usd'] as $code => $rate) {
                $rates[strtoupper($code)] = 1.0 / $rate; // rates relative to USD
            }
            $rates['USD'] = 1.0;
            $rateDate = $d1['date'] ?? date('Y-m-d');
        }
    }

    // Fallback: Frankfurter (ECB — no SYP)
    if (empty($rates)) {
        $ctx2 = stream_context_create(array_merge(['http' => ['timeout' => 10, 'user_agent' => 'yassota-tools/1.0', 'header' => 'Accept: application/json']], $sslOpts));
        $raw2 = @file_get_contents('https://api.frankfurter.app/latest?base=USD', false, $ctx2);
        if ($raw2) {
            $d2 = json_decode($raw2, true);
            if (!empty($d2['rates'])) {
                $rates = $d2['rates'];
                $rates['USD'] = 1.0;
                $rateDate = $d2['date'] ?? date('Y-m-d');
            }
        }
    }

    if (!empty($rates)) {
        // Ensure SYP is set (fawazahmed0 includes it; add fallback if absent)
        if (!isset($rates['SYP'])) $rates['SYP'] = 13000.0;
        if (!isset($rates['IQD'])) $rates['IQD'] = 1310.0;
        if (!isset($rates['LYD'])) $rates['LYD'] = 4.85;

        $out = json_encode([
            'ok'       => true,
            'rates'    => $rates,
            'date'     => $rateDate,
            'syp_note' => 'سعر الليرة السورية تقريبي (سعر السوق الموازي) وليس رسمياً',
        ]);
        file_put_contents($cacheFile, $out);
        echo $out;
        exit;
    }

    // Serve stale cache on network failure
    if (file_exists($cacheFile)) {
        echo file_get_contents($cacheFile);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'فشل تحميل الأسعار من المصدر']);
    exit;
}

/* ── Page ── */
$schema = json_encode([
    '@context' => 'https://schema.org', '@type' => 'WebApplication',
    'name'        => 'محوّل العملات — دولار وليرة سورية وتركية وعملات عربية',
    'url'         => TOOLS_BASE_URL . '/tools/currency/',
    'description' => 'حوّل بين الدولار والليرة السورية والتركية والريال والدرهم وأكثر من 150 عملة بأسعار محدّثة',
    'applicationCategory' => 'FinancialApplication',
    'operatingSystem'     => 'All',
    'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
]);
tool_head(
    'محوّل العملات — دولار وليرة سورية وتركية وعملات عربية | yassota',
    'حوّل بين الدولار الأمريكي والليرة السورية والتركية والريال السعودي والجنيه المصري وأكثر من 150 عملة. أسعار صرف محدّثة مجاناً.',
    $schema,
    '#f59e0b'
);
tool_header();
?>
<main class="t-main">

  <div class="t-hero">
    <div class="t-hero-icon" style="background:#fef3c7">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <path d="M12 6v6l4 2"/>
      </svg>
    </div>
    <h1>محوّل العملات — دولار وليرة سورية وتركية وعملات عالمية</h1>
    <p>حوّل بين أكثر من 155 عملة بما فيها الليرة السورية والتركية — أسعار محدّثة يومياً، مجانية 100%.</p>
  </div>

  <!-- Quick access buttons for most-requested pairs -->
  <div class="chip-group" style="margin-bottom:16px">
    <button class="chip" onclick="quickPair('USD','SYP')">$ دولار ↔ ل.س</button>
    <button class="chip" onclick="quickPair('USD','TRY')">$ دولار ↔ ليرة تركية</button>
    <button class="chip" onclick="quickPair('USD','SAR')">$ دولار ↔ ريال</button>
    <button class="chip" onclick="quickPair('USD','EGP')">$ دولار ↔ جنيه مصري</button>
    <button class="chip" onclick="quickPair('EUR','SYP')">€ يورو ↔ ل.س</button>
    <button class="chip" onclick="quickPair('TRY','SYP')">ليرة تركية ↔ ل.س</button>
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

    <div id="loading" style="color:#94a3b8;font-size:13px;padding:10px 0">جارٍ تحميل أسعار الصرف…</div>
    <div id="error-msg"  style="display:none" class="t-error"></div>
    <div id="result"     style="display:none;background:#fffbeb;border:1.5px solid #fcd34d;border-radius:10px;padding:16px 18px;font-size:20px;font-weight:700;color:#92400e;margin-bottom:8px"></div>
    <div id="rate-info"  style="font-size:12px;color:#94a3b8;margin-bottom:8px"></div>
    <div id="syp-note"   style="display:none;font-size:11px;color:#f59e0b;background:#fffbeb;border-radius:6px;padding:6px 10px;margin-bottom:4px"></div>
    <div id="date-info"  style="font-size:11px;color:#94a3b8"></div>
  </div>

  <!-- Quick converter boxes for most-popular pairs -->
  <div class="t-card">
    <h2 style="margin-bottom:14px">تحويل سريع — الدولار مقابل العملات العربية</h2>
    <div id="quick-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px"></div>
  </div>

  <div class="t-card">
    <h2 style="margin-bottom:14px">جدول المقارنة</h2>
    <div id="compare-table" style="display:none">
      <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px" id="ct"></table>
      </div>
      <p style="font-size:11px;color:#94a3b8;margin-top:10px">المصدر: Frankfurter.app (ECB) — تُحدَّث يومياً · سعر الليرة السورية تقريبي</p>
    </div>
  </div>

  <?php tool_ad(); ?>

  <div class="t-card" style="margin-top:16px">
    <h2 style="margin-bottom:12px">معلومات عن الليرة السورية والتركية</h2>
    <div style="font-size:13px;color:#64748b;line-height:1.8">
      <p style="margin-bottom:8px"><strong style="color:#0f172a">الليرة السورية (SYP):</strong> العملة الرسمية لسوريا. بسبب الأزمة الاقتصادية، يختلف سعر الصرف الرسمي عن سعر السوق الموازي بشكل كبير. السعر المعروض هنا تقريبي بناءً على سعر السوق الموازي (≈ 13,000 ل.س لكل دولار).</p>
      <p><strong style="color:#0f172a">الليرة التركية (TRY):</strong> العملة الرسمية لتركيا. سعرها متاح من البنك المركزي الأوروبي ويُحدَّث يومياً.</p>
    </div>
  </div>

</main>

<script>
var rates = {}, rateDate = '', baseCur = 'USD';
var arabicCurs = ['USD','SYP','TRY','SAR','AED','EGP','IQD','KWD','QAR','BHD','OMR','JOD','MAD','LYD'];
var mainCurs   = ['USD','SYP','TRY','SAR','AED','EGP','IQD','KWD','QAR','BHD','OMR','JOD','MAD','LYD','EUR','GBP','CAD','AUD','JPY','CHF','CNY','INR','PKR'];
var names = {
  USD:'دولار أمريكي',EUR:'يورو',GBP:'جنيه إسترليني',
  SYP:'ليرة سورية',TRY:'ليرة تركية',
  SAR:'ريال سعودي',AED:'درهم إماراتي',EGP:'جنيه مصري',
  IQD:'دينار عراقي',KWD:'دينار كويتي',QAR:'ريال قطري',
  BHD:'دينار بحريني',OMR:'ريال عُماني',JOD:'دينار أردني',
  MAD:'درهم مغربي',LYD:'دينار ليبي',
  CAD:'دولار كندي',AUD:'دولار أسترالي',JPY:'ين ياباني',
  CHF:'فرنك سويسري',CNY:'يوان صيني',INR:'روبية هندية',PKR:'روبية باكستانية'
};
var sypNote = '';

// Load rates from PHP proxy (server-side fetch avoids CORS issues)
fetch('?action=rates')
  .then(function(r){ return r.json(); })
  .then(function(d){
    document.getElementById('loading').style.display = 'none';
    if (!d.ok) {
      document.getElementById('error-msg').style.display = 'block';
      document.getElementById('error-msg').textContent = d.error || 'فشل تحميل الأسعار';
      return;
    }
    rates    = d.rates;
    rateDate = d.date || '';
    sypNote  = d.syp_note || '';
    if (rateDate) document.getElementById('date-info').textContent = 'آخر تحديث: ' + rateDate;
    buildSelects();
    convert();
    buildCompare();
    buildQuickGrid();
  }).catch(function(e){
    document.getElementById('loading').textContent = 'تعذّر الاتصال بالخادم — حاول مجدداً';
  });

function buildSelects() {
  var allCurs = mainCurs.concat(Object.keys(rates).filter(function(c){ return mainCurs.indexOf(c) < 0; }));
  allCurs.sort();
  // Put priority currencies first
  var priority = ['USD','SYP','TRY','SAR','AED','EGP'];
  allCurs = priority.concat(allCurs.filter(function(c){ return priority.indexOf(c)<0; }));

  ['from-cur','to-cur'].forEach(function(id, idx){
    var sel = document.getElementById(id);
    sel.innerHTML = '';
    allCurs.forEach(function(c){
      if (!rates[c]) return;
      var opt = document.createElement('option');
      opt.value = c;
      opt.textContent = c + (names[c] ? ' — ' + names[c] : '');
      sel.appendChild(opt);
    });
    sel.value = idx === 0 ? 'USD' : 'SYP';
  });
  document.getElementById('result').style.display = 'block';
}

function convert() {
  if (!Object.keys(rates).length) return;
  var amt = parseFloat(document.getElementById('amount').value) || 0;
  var f   = document.getElementById('from-cur').value;
  var t   = document.getElementById('to-cur').value;
  if (!rates[f] || !rates[t]) return;
  var inUSD = amt / rates[f];
  var out   = inUSD * rates[t];
  var r     = rates[t] / rates[f];
  document.getElementById('result').textContent = fmt(amt) + ' ' + f + ' = ' + fmt(out) + ' ' + t;
  document.getElementById('rate-info').textContent = '1 ' + f + ' = ' + r.toFixed(4) + ' ' + t;
  // Show SYP note when SYP is involved
  var sypEl = document.getElementById('syp-note');
  if (f === 'SYP' || t === 'SYP') {
    sypEl.textContent = '⚠️ ' + sypNote;
    sypEl.style.display = 'block';
  } else {
    sypEl.style.display = 'none';
  }
}

function swapCur() {
  var f = document.getElementById('from-cur');
  var t = document.getElementById('to-cur');
  var tmp = f.value; f.value = t.value; t.value = tmp;
  convert();
}

function quickPair(from, to) {
  document.getElementById('from-cur').value = from;
  document.getElementById('to-cur').value   = to;
  convert();
  document.getElementById('result').scrollIntoView({behavior:'smooth', block:'nearest'});
}

function buildQuickGrid() {
  var grid = document.getElementById('quick-grid');
  var amount = 1;
  arabicCurs.forEach(function(c){
    if (c === 'USD' || !rates[c]) return;
    var rate = rates[c];
    var box = document.createElement('div');
    box.style.cssText = 'background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;cursor:pointer;transition:border-color .2s';
    box.onmouseover = function(){ this.style.borderColor='#0ea5e9'; };
    box.onmouseout  = function(){ this.style.borderColor='#e2e8f0'; };
    box.onclick = function(){ quickPair('USD', c); };
    box.innerHTML =
      '<div style="font-size:11px;color:#94a3b8;margin-bottom:4px">1 USD =</div>' +
      '<div style="font-size:16px;font-weight:700;color:#0f172a">' + fmt(rate) + '</div>' +
      '<div style="font-size:12px;color:#64748b">' + c + (names[c] ? ' ' + names[c] : '') + '</div>' +
      (c === 'SYP' ? '<div style="font-size:10px;color:#f59e0b;margin-top:3px">سعر تقريبي</div>' : '');
    grid.appendChild(box);
  });
}

function buildCompare() {
  var f      = document.getElementById('from-cur').value;
  var amount = parseFloat(document.getElementById('amount').value) || 1;
  var ct     = document.getElementById('ct');
  ct.innerHTML = '<tr style="background:#f8fafc">' +
    '<th style="padding:10px 12px;text-align:right;font-size:12px;color:#64748b;border-bottom:1px solid #e2e8f0">العملة</th>' +
    '<th style="padding:10px 12px;text-align:right;font-size:12px;color:#64748b;border-bottom:1px solid #e2e8f0">المعدل</th>' +
    '<th style="padding:10px 12px;text-align:right;font-size:12px;color:#64748b;border-bottom:1px solid #e2e8f0">' + fmt(amount) + ' ' + f + '</th></tr>';

  mainCurs.forEach(function(c){
    if (c === f || !rates[c] || !rates[f]) return;
    var r   = rates[c] / rates[f];
    var val = amount * r;
    ct.innerHTML += '<tr style="border-bottom:1px solid #f1f5f9">' +
      '<td style="padding:9px 12px;font-size:13px">' + c +
        (names[c] ? ' <span style="color:#94a3b8;font-size:11px">' + names[c] + '</span>' : '') +
        (c==='SYP' ? ' <span style="color:#f59e0b;font-size:10px">تقريبي</span>' : '') +
      '</td>' +
      '<td style="padding:9px 12px;font-size:13px;font-variant-numeric:tabular-nums">' + r.toFixed(4) + '</td>' +
      '<td style="padding:9px 12px;font-size:13px;font-weight:600;font-variant-numeric:tabular-nums">' + fmt(val) + '</td></tr>';
  });
  document.getElementById('compare-table').style.display = 'block';
}

function fmt(n) {
  if (n >= 1000) return n.toLocaleString('ar-SA', {maximumFractionDigits:2});
  if (n >= 1)    return n.toLocaleString('ar-SA', {minimumFractionDigits:2, maximumFractionDigits:4});
  return n.toLocaleString('ar-SA', {minimumFractionDigits:4, maximumFractionDigits:6});
}
</script>
<?php tool_footer(); ?>
