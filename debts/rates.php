<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'أسعار الصرف — ' . ($settings['shop_name'] ?? 'دفتر الدكان');
require __DIR__ . '/includes/page-header.php';

$rates = getLatestRates($pdo);
$sypManual = (float)($settings['syp_manual_rate'] ?? 0);
$currencyCode = $settings['currency'] ?? 'SYP';

// تحقق هل يوجد بيانات حديثة (أقل من ساعة)
$stale = true;
foreach ($rates as $r) {
    if (strtotime($r['recorded_at']) > time() - 3600) { $stale = false; break; }
}
?>
<main class="page narrow" style="padding-top:32px;">

  <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
    <h1 style="font-family:var(--disp); font-size:22px; margin:0;"><?= icon('trending-up', 22) ?> أسعار الصرف</h1>
    <div style="display:flex; gap:8px; align-items:center;">
      <span style="font-size:12px; color:var(--ink-faint);" id="last-update">
        <?php if (!empty($rates)): ?>
          آخر تحديث: <?= date('H:i', strtotime($rates[0]['recorded_at'])) ?>
        <?php else: ?>
          لا يوجد بيانات بعد
        <?php endif; ?>
      </span>
      <button class="btn btn-ghost btn-sm" onclick="refreshRates()" id="refresh-btn">
        <?= icon('bell', 14) ?> تحديث
      </button>
    </div>
  </div>

  <?php if ($stale && !empty($rates)): ?>
  <div class="alert alert-warn" style="margin-bottom:16px;">
    <?= icon('bell', 16) ?> البيانات قد تكون قديمة. اضغط "تحديث" للحصول على أحدث الأسعار.
  </div>
  <?php endif; ?>

  <!-- سعر الليرة السورية (يدوي) -->
  <?php if ($sypManual > 0): ?>
  <div class="panel" style="margin-bottom:16px; border-right:4px solid var(--brand);">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
      <div>
        <div style="font-size:13px; color:var(--ink-faint); margin-bottom:4px;">الليرة السورية (سوق السوداء)</div>
        <div style="font-size:28px; font-weight:700; font-family:var(--disp); color:var(--brand);">
          <?= number_format($sypManual, 0) ?> <span style="font-size:16px;">ل.س / دولار</span>
        </div>
      </div>
      <div style="text-align:center; padding:12px 20px; background:var(--surface-2); border-radius:12px;">
        <div style="font-size:11px; color:var(--ink-faint);">التحديث</div>
        <div style="font-size:13px; font-weight:600;">يدوي</div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- أسعار العملات الدولية -->
  <div class="panel" style="margin-bottom:16px;">
    <h2 style="font-size:15px; margin:0 0 16px; color:var(--ink-faint);">العملات مقابل الدولار الأمريكي</h2>
    <?php if (empty($rates)): ?>
      <div class="cc-empty-state" style="padding:40px 0;">
        <?= icon('trending-up', 40) ?>
        <p>لا توجد بيانات. اضغط "تحديث" لجلب الأسعار.</p>
      </div>
    <?php else: ?>
      <div style="display:grid; gap:12px;" id="rates-grid">
        <?php
        $labels = ['TRY' => 'ليرة تركية 🇹🇷', 'EUR' => 'يورو 🇪🇺', 'GBP' => 'جنيه إسترليني 🇬🇧'];
        foreach ($rates as $r):
            if ($r['currency'] === 'USD') continue;
            $label = $labels[$r['currency']] ?? $r['currency'];
        ?>
        <div class="rate-card" data-currency="<?= h($r['currency']) ?>">
          <div>
            <div style="font-size:13px; color:var(--ink-faint); margin-bottom:4px;"><?= h($label) ?></div>
            <div style="font-size:22px; font-weight:700; font-family:var(--disp);" class="rate-val">
              <?= number_format((float)$r['rate_to_usd'], 4) ?>
            </div>
          </div>
          <div style="text-align:end;">
            <div style="font-size:11px; color:var(--ink-faint);">المصدر</div>
            <div style="font-size:12px;"><?= h($r['source'] ?? '—') ?></div>
            <div style="font-size:11px; color:var(--ink-faint); margin-top:4px;" class="rate-time">
              <?= date('H:i', strtotime($r['recorded_at'])) ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- حاسبة التحويل -->
  <div class="panel">
    <h2 style="font-size:15px; margin:0 0 16px;"><?= icon('swap', 16) ?> حاسبة التحويل</h2>
    <div style="display:grid; grid-template-columns:1fr auto 1fr; gap:12px; align-items:center;">
      <div>
        <label style="font-size:12px; color:var(--ink-faint);">المبلغ</label>
        <input type="number" id="calc-amount" class="input" value="100" min="0" step="any" oninput="calcConvert()">
      </div>
      <div style="text-align:center; padding-top:20px;">→</div>
      <div>
        <label style="font-size:12px; color:var(--ink-faint);">العملة</label>
        <select id="calc-currency" class="input" onchange="calcConvert()">
          <option value="1">USD - دولار</option>
          <?php foreach ($rates as $r): if ($r['currency'] === 'USD') continue; ?>
          <option value="<?= (float)$r['rate_to_usd'] ?>"><?= h($r['currency']) ?></option>
          <?php endforeach; ?>
          <?php if ($sypManual > 0): ?>
          <option value="<?= $sypManual ?>">SYP - ليرة سورية</option>
          <?php endif; ?>
        </select>
      </div>
    </div>
    <div style="margin-top:16px; padding:16px; background:var(--surface-2); border-radius:12px; text-align:center;">
      <div style="font-size:13px; color:var(--ink-faint);">النتيجة</div>
      <div style="font-size:28px; font-weight:700; font-family:var(--disp); color:var(--brand);" id="calc-result">—</div>
    </div>
  </div>

</main>

<style>
.rate-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: var(--surface-2);
  border-radius: 12px;
  border: 1px solid var(--line);
}
.alert-warn {
  padding: 12px 16px;
  background: rgba(245,158,11,0.1);
  border: 1px solid rgba(245,158,11,0.3);
  border-radius: 10px;
  color: var(--ink);
  font-size: 13px;
}
</style>

<script>
function calcConvert() {
  const amount = parseFloat(document.getElementById('calc-amount').value) || 0;
  const rate   = parseFloat(document.getElementById('calc-currency').value) || 1;
  const result = amount * rate;
  document.getElementById('calc-result').textContent =
    result.toLocaleString('ar', {maximumFractionDigits: 2});
}
calcConvert();

async function refreshRates() {
  const btn = document.getElementById('refresh-btn');
  btn.disabled = true;
  btn.textContent = '...';
  try {
    const res = await fetch('api/rates.php', {method:'POST'});
    const data = await res.json();
    if (data.ok) {
      location.reload();
    } else {
      alert('تعذر التحديث: ' + (data.error || 'خطأ غير معروف'));
    }
  } catch(e) {
    alert('خطأ في الاتصال');
  }
  btn.disabled = false;
  btn.innerHTML = '<?= icon('bell', 14) ?> تحديث';
}
</script>

<?php require __DIR__ . '/includes/page-footer.php'; ?>
