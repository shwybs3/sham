<?php
require_once dirname(__DIR__) . '/_base.php';

/* ── SYP Telegram notification helpers ──────────────────────────────────
   Self-contained: reads config.local.php constants only (no side-effects),
   opens its own short-lived PDO, sends via curl. All errors are swallowed. */
function _syp_pdo(): ?PDO {
    $lcfg = dirname(dirname(__DIR__)) . '/config.local.php';
    if (!file_exists($lcfg)) return null;
    if (!defined('DB_HOST')) @include_once $lcfg;
    try {
        return new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]
        );
    } catch (Throwable $e) { return null; }
}
function _syp_cfg(PDO $pdo, string $k): string {
    try {
        $s = $pdo->prepare("SELECT value FROM settings WHERE `key`=? LIMIT 1");
        $s->execute([$k]);
        return (string)($s->fetchColumn() ?? '');
    } catch (Throwable $e) { return ''; }
}
function _syp_notify(float $syp, array $rates): void {
    if (!function_exists('curl_init')) return;
    $rfFile = sys_get_temp_dir() . '/yas_syp_tg_last_rate.txt';
    $tsFile = sys_get_temp_dir() . '/yas_syp_tg_last_sent.txt';
    $lastRate = (float)(@file_get_contents($rfFile) ?: '0');
    $lastSent = (int)(@file_get_contents($tsFile)  ?: '0');
    $rateChanged = ($lastRate > 0) && (abs($syp - $lastRate) >= 20);
    $timeExpired = (time() - $lastSent) >= 21600; // 6 hours
    if (!$rateChanged && !$timeExpired) return;

    $pdo = _syp_pdo();
    if (!$pdo) return;
    $token  = _syp_cfg($pdo, 'telegram_bot_token');
    $chatId = _syp_cfg($pdo, 'telegram_channel_id');
    if (!$token || !$chatId) return;

    $flags = ['USD'=>'🇺🇸','EUR'=>'🇪🇺','SAR'=>'🇸🇦','AED'=>'🇦🇪','KWD'=>'🇰🇼',
              'GBP'=>'🇬🇧','TRY'=>'🇹🇷','IQD'=>'🇮🇶','LBP'=>'🇱🇧','EGP'=>'🇪🇬','JOD'=>'🇯🇴'];
    $names = ['USD'=>'الدولار الأمريكي','EUR'=>'اليورو','SAR'=>'الريال السعودي',
              'AED'=>'الدرهم الإماراتي','KWD'=>'الدينار الكويتي','GBP'=>'الجنيه الإسترليني',
              'TRY'=>'الليرة التركية','IQD'=>'الدينار العراقي','LBP'=>'الليرة اللبنانية',
              'EGP'=>'الجنيه المصري','JOD'=>'الدينار الأردني'];

    $reason = $rateChanged
        ? '⚡ تغيّر السعر ' . ($syp > $lastRate ? '↑' : '↓') . ' ' . number_format(abs($syp - $lastRate), 0) . ' ل.س'
        : '🕐 تحديث دوري (كل 6 ساعات)';

    $lines = ["💱 <b>تحديث سعر الليرة السورية</b>", $reason, ''];
    foreach ($flags as $code => $flag) {
        $val = $rates[$code] ?? null;
        if ($val === null) continue;
        $fmt = $val >= 100 ? number_format($val, 0) : number_format($val, 2);
        $lines[] = "{$flag} {$names[$code]}: <b>{$fmt} ل.س</b>";
    }
    $lines[] = '';
    $lines[] = '📅 ' . date('Y-m-d H:i');
    $lines[] = '⚠️ أسعار السوق الموازي — للاستخدام المرجعي فقط';
    $lines[] = '🔗 ' . TOOLS_BASE_URL . '/tools/syp/';

    $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 4, CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'chat_id' => $chatId, 'text' => implode("\n", $lines), 'parse_mode' => 'HTML',
        ]),
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $d = json_decode((string)$res, true);
    if (!empty($d['ok'])) {
        @file_put_contents($rfFile, (string)$syp);
        @file_put_contents($tsFile, (string)time());
    }
}

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

    $caBundle = '/root/.ccr/ca-bundle.crt';
    $sslOpts  = is_file($caBundle) ? ['ssl' => ['cafile' => $caBundle, 'verify_peer' => true, 'verify_peer_name' => true]] : [];

    // Primary: fawazahmed0 via jsDelivr (includes real SYP rate)
    $ctx1 = stream_context_create(array_merge(['http' => ['timeout' => 10, 'user_agent' => 'yassota-tools/1.0']], $sslOpts));
    $raw1 = @file_get_contents('https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json', false, $ctx1);

    $syp_per_usd = 13000.0;
    $rates = [];
    $wantedCodes = ['EUR', 'GBP', 'SAR', 'AED', 'KWD', 'TRY', 'IQD', 'LBP', 'EGP', 'JOD'];

    if ($raw1 && ($d1 = json_decode($raw1, true)) && !empty($d1['usd'])) {
        $usdRates = $d1['usd']; // $usdRates[code] = units_per_USD (actually it's value of 1 USD in that currency)
        // fawazahmed0 gives: usd.syp = SYP units for 1 USD
        if (!empty($usdRates['syp'])) $syp_per_usd = (float)$usdRates['syp'];
        foreach ($wantedCodes as $code) {
            $lc = strtolower($code);
            if (!empty($usdRates[$lc])) {
                // usdRates[$lc] = how many $code per USD
                // We want: how many SYP per 1 $code
                $rates[$code] = round($syp_per_usd / $usdRates[$lc], 2);
            }
        }
    }

    // Fallback hardcoded rates (SYP per 1 unit)
    if (empty($rates)) {
        $rates = [
            'EUR' => 14100, 'GBP' => 16300, 'SAR' => 3465,
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
    // Telegram: notify if rate changed ≥20 ل.س OR 6 hours since last send
    try { _syp_notify($syp_per_usd, $rates); } catch (Throwable $_e) {}
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
