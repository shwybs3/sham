<?php
require_once dirname(__DIR__) . '/_base.php';

/* ── Server-side SYP rates proxy ── */
if (isset($_GET['action']) && $_GET['action'] === 'syp') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=1800');

    $cacheFile = sys_get_temp_dir() . '/yas_syp_rates.json';
    $cacheAge  = file_exists($cacheFile) ? (time() - filemtime($cacheFile)) : PHP_INT_MAX;

    if ($cacheAge < 1800) {
        echo file_get_contents($cacheFile);
        exit;
    }

    // Fetch USD-based rates from Frankfurter (free, no key needed)
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'yassota-tools/1.0']]);
    $raw = @file_get_contents('https://api.frankfurter.app/latest?base=USD&symbols=EUR,GBP,SAR,AED,KWD,TRY,IQD,LBP,EGP,JOD', false, $ctx);

    // SYP market rates (updated manually — no public API for Syrian Pound)
    $syp_per_usd = 13000.0;

    $rates = [];
    if ($raw && ($d = json_decode($raw, true)) && !empty($d['rates'])) {
        foreach ($d['rates'] as $code => $usd_rate) {
            $rates[$code] = round($syp_per_usd / $usd_rate, 2);
        }
    } else {
        // Fallback hardcoded rates (SYP per 1 unit)
        $rates = [
            'USD' => 13000, 'EUR' => 14100, 'GBP' => 16300, 'SAR' => 3465,
            'AED' => 3540, 'KWD' => 42400, 'TRY' => 395, 'IQD' => 9.9,
            'LBP' => 0.14, 'EGP' => 260, 'JOD' => 18340,
        ];
    }
    $rates['USD'] = $syp_per_usd;

    $out = json_encode([
        'ok'          => true,
        'syp_per_usd' => $syp_per_usd,
        'rates'       => $rates,
        'updated_at'  => date('Y-m-d H:i'),
        'note'        => 'الأسعار تقريبية وتعكس سعر السوق الموازي',
    ]);
    file_put_contents($cacheFile, $out);
    echo $out;
    exit;
}

$schema = json_encode([
    '@context'=>'https://schema.org','@type'=>'WebApplication',
    'name'=>'سعر صرف الليرة السورية','url'=>TOOLS_BASE_URL.'/tools/syp/',
    'description'=>'سعر صرف الليرة السورية مقابل الدولار واليورو والريال والدرهم وأكثر من 10 عملات — محدَّث يومياً.',
    'applicationCategory'=>'FinanceApplication','operatingSystem'=>'All',
    'offers'=>['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
]);
tool_head('سعر صرف الليرة السورية اليوم — مقابل الدولار واليورو والريال | yassota','سعر الليرة السورية مقابل الدولار الأمريكي واليورو والريال السعودي والدرهم الإماراتي والدينار الكويتي — محدَّث يومياً بسعر السوق.',$schema,'#dc2626');
tool_header();
?>
<main class="t-main">
  <div class="t-hero">
    <div class="t-hero-icon" style="background:#fee2e2">
      <span style="font-size:22px;font-weight:900;color:#dc2626">ل.س</span>
    </div>
    <h1>سعر صرف الليرة السورية</h1>
    <p>سعر الليرة السورية مقابل العملات الرئيسية — بسعر السوق الموازي المحدَّث يومياً.</p>
  </div>

  <!-- Featured Rate (USD/SYP) -->
  <div class="t-card" style="max-width:600px;margin:0 auto 16px;text-align:center">
    <div style="font-size:13px;color:#64748b;margin-bottom:8px">سعر الدولار الأمريكي مقابل الليرة السورية</div>
    <div id="usd-syp" style="font-size:52px;font-weight:900;color:#dc2626;letter-spacing:-2px;font-family:monospace;direction:ltr">جارٍ التحميل…</div>
    <div style="font-size:13px;color:#94a3b8;margin-top:4px">ليرة سورية للدولار الواحد (سعر السوق الموازي)</div>
    <div id="rates-updated" style="font-size:11px;color:#94a3b8;margin-top:8px"></div>
    <div style="margin-top:10px;padding:10px;background:#fef3c7;border-radius:8px;font-size:12px;color:#92400e">
      ⚠️ الأسعار تعكس سعر السوق الموازي وليست الأسعار الرسمية — للاستخدام المرجعي فقط
    </div>
  </div>

  <!-- Quick Converter -->
  <div class="t-card" style="max-width:600px;margin:0 auto 24px">
    <h2 style="font-size:16px;margin-bottom:14px">حاسبة التحويل السريع</h2>
    <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:10px;align-items:end">
      <div>
        <label class="t-label">المبلغ</label>
        <input type="number" id="conv-amount" class="t-input" value="1" min="0" step="any" oninput="updateConv()">
      </div>
      <button onclick="swapConv()" class="t-btn" style="padding:10px 14px;margin:0">⇄</button>
      <div>
        <label class="t-label">العملة</label>
        <select id="conv-currency" class="t-input" onchange="updateConv()">
          <option value="USD">دولار أمريكي (USD)</option>
          <option value="EUR">يورو (EUR)</option>
          <option value="SAR">ريال سعودي (SAR)</option>
          <option value="AED">درهم إماراتي (AED)</option>
          <option value="KWD">دينار كويتي (KWD)</option>
          <option value="TRY">ليرة تركية (TRY)</option>
          <option value="IQD">دينار عراقي (IQD)</option>
          <option value="LBP">ليرة لبنانية (LBP)</option>
          <option value="EGP">جنيه مصري (EGP)</option>
          <option value="GBP">جنيه إسترليني (GBP)</option>
          <option value="JOD">دينار أردني (JOD)</option>
        </select>
      </div>
    </div>
    <div id="conv-result" style="margin-top:14px;padding:14px;background:#fff5f5;border-radius:10px;text-align:center;display:none">
      <div style="font-size:24px;font-weight:900;color:#dc2626;direction:ltr" id="conv-out"></div>
      <div style="font-size:12px;color:#94a3b8;margin-top:4px" id="conv-label"></div>
    </div>
  </div>

  <!-- All Rates Grid -->
  <div class="t-card" style="margin-bottom:24px">
    <h2>سعر الليرة السورية مقابل العملات الرئيسية</h2>
    <div id="rates-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px;margin-top:14px">
      <?php
      $currencies = [
        ['USD','دولار أمريكي','🇺🇸'],
        ['EUR','يورو','🇪🇺'],
        ['SAR','ريال سعودي','🇸🇦'],
        ['AED','درهم إماراتي','🇦🇪'],
        ['KWD','دينار كويتي','🇰🇼'],
        ['TRY','ليرة تركية','🇹🇷'],
        ['GBP','جنيه إسترليني','🇬🇧'],
        ['IQD','دينار عراقي','🇮🇶'],
        ['LBP','ليرة لبنانية','🇱🇧'],
        ['EGP','جنيه مصري','🇪🇬'],
        ['JOD','دينار أردني','🇯🇴'],
      ]; foreach($currencies as [$code,$name,$flag]):
      ?>
      <div style="background:#f8fafc;border-radius:10px;padding:12px;text-align:center" id="card-<?= $code ?>">
        <div style="font-size:22px;margin-bottom:4px"><?= $flag ?></div>
        <div style="font-size:11px;color:#64748b;margin-bottom:4px"><?= $name ?></div>
        <div style="font-size:11px;font-family:monospace;color:#94a3b8"><?= $code ?></div>
        <div style="font-size:16px;font-weight:800;color:#dc2626;margin-top:6px;font-family:monospace;direction:ltr" id="rate-<?= $code ?>">—</div>
        <div style="font-size:10px;color:#94a3b8;margin-top:2px">ل.س للوحدة</div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php tool_ad(); ?>

  <!-- Info table -->
  <div class="t-card" style="margin-bottom:24px">
    <h2>جدول تحويل سريع — الليرة السورية</h2>
    <div style="overflow-x:auto;margin-top:12px">
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr style="background:#fff5f5">
          <th style="padding:9px 12px;text-align:right">المبلغ بالدولار</th>
          <th style="padding:9px 12px;text-align:right" id="th-usd">= بالليرة السورية</th>
        </tr></thead>
        <tbody id="quick-table">
          <?php foreach([1,5,10,20,50,100,200,500,1000] as $amt): ?>
          <tr style="border-bottom:1px solid #fef2f2" id="row-<?= $amt ?>">
            <td style="padding:8px 12px;font-weight:700;direction:ltr;text-align:right"><?= number_format($amt) ?> $</td>
            <td style="padding:8px 12px;font-family:monospace;color:#dc2626;direction:ltr" id="qt-<?= $amt ?>">—</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="t-card">
    <h2>معلومات عن الليرة السورية</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-top:12px">
      <div style="background:#fff5f5;border-radius:12px;padding:14px">
        <div style="font-weight:700;color:#dc2626;margin-bottom:6px">الرمز الدولي</div>
        <p style="font-size:13px;color:#64748b;line-height:1.6">SYP — Syrian Pound. الليرة هي العملة الرسمية للجمهورية العربية السورية منذ عام 1947.</p>
      </div>
      <div style="background:#fef9f0;border-radius:12px;padding:14px">
        <div style="font-weight:700;color:#d97706;margin-bottom:6px">البنك المركزي السوري</div>
        <p style="font-size:13px;color:#64748b;line-height:1.6">يصدر مصرف سورية المركزي الليرة السورية، لكن السعر الرسمي يختلف عن سعر السوق الموازي الموضح هنا.</p>
      </div>
      <div style="background:#f0fdf4;border-radius:12px;padding:14px">
        <div style="font-weight:700;color:#16a34a;margin-bottom:6px">تحويلات السوريين</div>
        <p style="font-size:13px;color:#64748b;line-height:1.6">يعتمد السوريون عادةً على تطبيقات مثل حوالة ويسترن يونيون للتحويلات، بسعر أقرب لسعر السوق.</p>
      </div>
      <div style="background:#f0f9ff;border-radius:12px;padding:14px">
        <div style="font-weight:700;color:#0891b2;margin-bottom:6px">تحديث الأسعار</div>
        <p style="font-size:13px;color:#64748b;line-height:1.6">الأسعار المعروضة تعكس سعر السوق الموازي وتُحدَّث يومياً. الأسعار للاستخدام المرجعي فقط وليست للتداول.</p>
      </div>
    </div>
  </div>
</main>

<script>
let ratesData = null;
let convDir = 'from'; // 'from' = foreign→SYP, 'to' = SYP→foreign

fetch('?action=syp')
  .then(r => r.json())
  .then(data => {
    if (!data.ok) return;
    ratesData = data;
    const syp = data.syp_per_usd;

    // Featured USD rate
    document.getElementById('usd-syp').textContent = Number(syp).toLocaleString('ar');
    document.getElementById('rates-updated').textContent = 'آخر تحديث: ' + data.updated_at;

    // Cards
    Object.entries(data.rates).forEach(([code, rate]) => {
      const el = document.getElementById('rate-' + code);
      if (el) el.textContent = Number(rate).toLocaleString('ar', {maximumFractionDigits:2});
    });

    // Quick table
    [1,5,10,20,50,100,200,500,1000].forEach(amt => {
      const el = document.getElementById('qt-' + amt);
      if (el) el.textContent = Number(amt * syp).toLocaleString('ar');
    });

    updateConv();
  })
  .catch(() => {
    document.getElementById('usd-syp').textContent = '~13,000';
    document.getElementById('rates-updated').textContent = 'تعذّر تحديث الأسعار — يعرض قيمة مرجعية';
  });

function updateConv() {
  if (!ratesData) return;
  const amount = parseFloat(document.getElementById('conv-amount').value) || 0;
  const currency = document.getElementById('conv-currency').value;
  const rate = ratesData.rates[currency] || ratesData.syp_per_usd;

  let result, label;
  if (convDir === 'from') {
    result = amount * rate;
    label = `${amount} ${currency} = ${Number(result).toLocaleString('ar', {maximumFractionDigits:0})} ل.س`;
  } else {
    result = amount / rate;
    label = `${Number(amount).toLocaleString('ar', {maximumFractionDigits:0})} ل.س = ${result.toFixed(4)} ${currency}`;
  }

  document.getElementById('conv-out').textContent = Number(result).toLocaleString('ar', {maximumFractionDigits: convDir==='from'?0:4});
  document.getElementById('conv-label').textContent = label;
  document.getElementById('conv-result').style.display = 'block';
}

function swapConv() {
  convDir = convDir === 'from' ? 'to' : 'from';
  const lbl = document.getElementById('conv-currency').previousElementSibling;
  if (lbl) lbl.textContent = convDir === 'from' ? 'العملة' : 'العملة (ل.س ←)';
  updateConv();
}
</script>
<?php tool_footer(); ?>
