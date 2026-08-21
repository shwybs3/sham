<?php
/**
 * tools/szad/pay.php — Szad AI standalone subscription payment page.
 * Dark-red aesthetic matching szad-subdomain. 20-minute countdown per invoice.
 */
require_once dirname(__DIR__) . '/_base.php';

$mainCfg = dirname(dirname(__DIR__)) . '/config.php';
if (!file_exists($mainCfg)) { http_response_code(503); echo 'Service unavailable'; exit; }
require_once $mainCfg;
require_once dirname(__DIR__) . '/chat/_auth.php';

$me = ys_current_user($pdo);
$h  = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

/* Redirect to login if not authenticated */
if (!$me) {
    $back = urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    header('Location: ' . (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '') . '/tools/chat/auth.php?redirect=' . $back);
    exit;
}

$nowpayEnabled = get_cfg($pdo, 'nowpay_api_key', '') !== '';
$activeSub     = ys_active_sub($pdo, (int)$me['id']);

$tiers = [
    'plus'    => ['name' => 'بلاس',    'price' => 5,  'color' => '#c50000', 'msgs' => '500 رسالة/شهر',    'models' => 'GPT-4o + Claude Sonnet'],
    'pro'     => ['name' => 'برو',     'price' => 10, 'color' => '#b30000', 'msgs' => '2,000 رسالة/شهر', 'models' => 'GPT-4o + Claude Opus + Gemini Pro'],
    'premium' => ['name' => 'بريميوم', 'price' => 20, 'color' => '#900000', 'msgs' => 'غير محدود',        'models' => 'جميع النماذج + أولوية قصوى'],
];

$selectedTier = $_GET['tier'] ?? 'plus';
if (!isset($tiers[$selectedTier])) $selectedTier = 'plus';

$error   = '';
$invoice = null;

/* ── Handle checkout POST ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tier'])) {
    if (!csrf_check()) {
        $error = 'جلسة غير صالحة، أعد تحميل الصفحة.';
    } else {
        $tier = $_POST['tier'] ?? '';
        if (!isset($tiers[$tier])) {
            $error = 'خطة غير صالحة.';
        } elseif (!$nowpayEnabled) {
            $error = 'نظام الدفع غير مفعّل حالياً — تواصل معنا مباشرة.';
        } else {
            $price   = (float)$tiers[$tier]['price'];
            $invoice = nowpay_create_invoice($pdo, (int)$me['id'], $tier, $price, 30);
            if (!$invoice) {
                $error = 'تعذّر إنشاء طلب الدفع، حاول مجدداً أو تواصل معنا.';
            }
        }
    }
}

$pageTitle = 'اشترك في صَعد AI — ' . $tiers[$selectedTier]['name'];
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $h($pageTitle) ?></title>
<meta name="robots" content="noindex,nofollow">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --red:   #c50000;
  --red2:  #ff2200;
  --red3:  #900000;
  --bg:    #050008;
  --bg2:   #0a0010;
  --glass: rgba(180,0,0,.08);
  --border:#3a0008;
  --text:  #f0e0e0;
  --muted: #a88888;
  --success:#10b981;
  --ff: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
}
body {
  font-family: var(--ff);
  background: var(--bg);
  color: var(--text);
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  padding: 0 16px 60px;
}
a { color: var(--red2); text-decoration: none; }
a:hover { text-decoration: underline; }

/* Header */
.sz-header {
  width: 100%;
  max-width: 700px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 0;
  border-bottom: 1px solid var(--border);
  margin-bottom: 36px;
}
.sz-logo {
  font-size: 22px;
  font-weight: 800;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 8px;
}
.sz-logo span { color: var(--red2); }
.sz-user {
  font-size: 13px;
  color: var(--muted);
}

/* Wrap */
.pay-wrap {
  width: 100%;
  max-width: 700px;
}

/* Tier tabs */
.tier-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 28px;
  flex-wrap: wrap;
}
.tier-tab {
  flex: 1;
  min-width: 80px;
  padding: 10px 16px;
  border-radius: 10px;
  border: 1.5px solid var(--border);
  background: var(--glass);
  color: var(--muted);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  text-align: center;
  transition: all .18s;
  text-decoration: none;
  display: block;
}
.tier-tab:hover { border-color: var(--red); color: var(--text); text-decoration: none; }
.tier-tab.active { border-color: var(--red2); background: rgba(197,0,0,.18); color: #fff; }

/* Plan card */
.plan-card {
  background: var(--glass);
  border: 1.5px solid var(--border);
  border-radius: 20px;
  padding: 28px 24px;
  margin-bottom: 24px;
}
.plan-title {
  font-size: 26px;
  font-weight: 800;
  color: #fff;
  margin-bottom: 4px;
}
.plan-price {
  font-size: 48px;
  font-weight: 900;
  color: var(--red2);
  line-height: 1.1;
  margin-bottom: 16px;
}
.plan-price sup { font-size: 22px; vertical-align: super; }
.plan-price span { font-size: 16px; color: var(--muted); }
.plan-feats {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 8px;
}
.plan-feats li {
  font-size: 14px;
  color: #ddd;
  display: flex;
  align-items: center;
  gap: 10px;
}
.plan-feats li::before { content: '✓'; color: var(--red2); font-weight: 700; }

/* Cross-site promo */
.xsite-promo {
  background: rgba(197,0,0,.06);
  border: 1px solid rgba(197,0,0,.2);
  border-radius: 14px;
  padding: 18px 20px;
  margin-bottom: 24px;
}
.xsite-promo h3 { font-size: 15px; font-weight: 700; color: #fff; margin-bottom: 10px; }
.xsite-icons {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.xsite-icon {
  background: rgba(255,255,255,.06);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 12px;
  color: var(--muted);
}

/* Countdown */
.countdown-box {
  background: rgba(197,0,0,.1);
  border: 1px solid rgba(197,0,0,.3);
  border-radius: 12px;
  padding: 12px 18px;
  text-align: center;
  margin-bottom: 20px;
}
.countdown-box .cdlabel { font-size: 12px; color: var(--muted); margin-bottom: 4px; }
.countdown-box .cdtime { font-size: 26px; font-weight: 800; color: var(--red2); font-variant-numeric: tabular-nums; font-family: monospace; }
.countdown-box.expired .cdtime { color: #f87171; }

/* Invoice box */
.invoice-box {
  background: var(--bg2);
  border: 1.5px solid var(--border);
  border-radius: 16px;
  padding: 24px;
}
.invoice-label { font-size: 12px; color: var(--muted); margin-bottom: 4px; }
.invoice-addr {
  background: rgba(255,255,255,.05);
  border-radius: 8px;
  padding: 12px 14px;
  font-family: monospace;
  font-size: 13px;
  color: #f0e0e0;
  word-break: break-all;
  margin-bottom: 10px;
  border: 1px solid var(--border);
}
.copy-btn {
  background: var(--red);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 9px 18px;
  font-size: 13px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: opacity .15s;
}
.copy-btn:hover { opacity: .85; }
.invoice-note {
  font-size: 12px;
  color: var(--muted);
  line-height: 1.8;
  margin-top: 14px;
}

/* Submit button */
.pay-btn {
  display: block;
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, var(--red), var(--red2));
  color: #fff;
  border: none;
  border-radius: 14px;
  font-size: 18px;
  font-weight: 700;
  cursor: pointer;
  text-align: center;
  transition: opacity .15s;
  letter-spacing: .02em;
}
.pay-btn:hover { opacity: .9; }

/* Error */
.pay-error {
  background: rgba(239,68,68,.12);
  border: 1px solid rgba(239,68,68,.3);
  color: #fca5a5;
  padding: 12px 18px;
  border-radius: 10px;
  margin-bottom: 18px;
  font-size: 14px;
}

/* Active sub notice */
.active-notice {
  background: rgba(16,185,129,.1);
  border: 1px solid rgba(16,185,129,.3);
  color: #6ee7b7;
  padding: 14px 18px;
  border-radius: 12px;
  margin-bottom: 20px;
  font-size: 14px;
}

@media (max-width: 520px) {
  .plan-price { font-size: 36px; }
}
</style>
</head>
<body>

<header class="sz-header">
  <div class="sz-logo">🔥 <span>صَعد</span> AI</div>
  <div class="sz-user">مرحباً، <?= $h($me['name'] ?? $me['email'] ?? 'مستخدم') ?></div>
</header>

<div class="pay-wrap">

  <?php if ($activeSub && !$invoice): ?>
  <div class="active-notice">
    ✅ لديك اشتراك نشط: <strong><?= $h(strtoupper((string)$activeSub['tier'])) ?></strong>
    — ينتهي <?= $h(substr((string)($activeSub['expires_at'] ?? ''), 0, 10)) ?>.
    يمكنك الترقية بإنشاء اشتراك جديد.
  </div>
  <?php endif; ?>

  <?php if (!$invoice): ?>

  <!-- Tier selector -->
  <div class="tier-tabs">
    <?php foreach ($tiers as $k => $t): ?>
    <a href="?tier=<?= $k ?>" class="tier-tab<?= $k === $selectedTier ? ' active' : '' ?>">
      <?= $h($t['name']) ?> — $<?= $t['price'] ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if ($error): ?>
  <div class="pay-error"><?= $h($error) ?></div>
  <?php endif; ?>

  <!-- Selected plan details -->
  <?php $t = $tiers[$selectedTier]; ?>
  <div class="plan-card">
    <div style="font-size:12px;color:var(--muted);margin-bottom:6px">الخطة المختارة</div>
    <div class="plan-title">صَعد <?= $h($t['name']) ?></div>
    <div class="plan-price"><sup>$</sup><?= $t['price'] ?> <span>/ شهرياً</span></div>
    <ul class="plan-feats">
      <li><?= $h($t['msgs']) ?></li>
      <li>نماذج: <?= $h($t['models']) ?></li>
      <li>بحث ويب متقدم + تفكير عميق</li>
      <li>ذاكرة محادثة طويلة</li>
      <li>أولوية في الاستجابة</li>
      <?php if ($selectedTier === 'premium'): ?>
      <li>تكامل API مباشر</li>
      <li>دعم مخصص 24/7</li>
      <?php endif; ?>
    </ul>
  </div>

  <!-- Cross-site benefit -->
  <div class="xsite-promo">
    <h3>🌐 اشتراكك يشمل جميع أدوات yassota.com</h3>
    <div class="xsite-icons">
      <span class="xsite-icon">🌸 ياسمين AI</span>
      <span class="xsite-icon">🔥 صَعد AI</span>
      <span class="xsite-icon">📅 تحويل التاريخ</span>
      <span class="xsite-icon">✍️ كاتب AI</span>
      <span class="xsite-icon">#️⃣ هاشتاق AI</span>
      <span class="xsite-icon">🌍 مترجم AI</span>
      <span class="xsite-icon">🔢 تحويل أرقام</span>
      <span class="xsite-icon">🕌 حاسبة الزكاة</span>
      <span class="xsite-icon">⌨️ إصلاح لوحة مفاتيح</span>
      <span class="xsite-icon">+ 80 أداة أخرى</span>
    </div>
  </div>

  <!-- Checkout form -->
  <?php if ($nowpayEnabled): ?>
  <form method="POST">
    <input type="hidden" name="_csrf" value="<?= $h(csrf_token()) ?>">
    <input type="hidden" name="tier" value="<?= $h($selectedTier) ?>">
    <button type="submit" class="pay-btn">
      💳 ادفع الآن بالعملة الرقمية — $<?= $t['price'] ?>
    </button>
    <p style="text-align:center;font-size:12px;color:var(--muted);margin-top:10px">
      الدفع عبر NOWPayments — USDT / BTC / ETH / TRX وأكثر من 50 عملة
    </p>
  </form>
  <?php else: ?>
  <div class="pay-error">
    ⚠️ نظام الدفع غير مفعّل حالياً — تواصل معنا عبر <a href="mailto:info@yassota.com">info@yassota.com</a>
  </div>
  <?php endif; ?>

  <?php else: /* ── Invoice created ── */ ?>

  <!-- 20-minute countdown -->
  <div class="countdown-box" id="cdbox">
    <div class="cdlabel">⏳ أرسل الدفعة خلال</div>
    <div class="cdtime" id="cdtime">20:00</div>
    <div style="font-size:11px;color:var(--muted);margin-top:4px">
      ينتهي هذا الطلب — لا تغلق الصفحة
    </div>
  </div>

  <?php if ($error): ?>
  <div class="pay-error"><?= $h($error) ?></div>
  <?php endif; ?>

  <div class="invoice-box">
    <h2 style="font-size:18px;font-weight:700;margin-bottom:18px;color:#fff">
      💰 تفاصيل الدفع — صَعد <?= $h($tiers[$invoice['tier'] ?? $selectedTier]['name'] ?? '') ?>
    </h2>

    <?php if (!empty($invoice['pay_address'])): ?>
    <div class="invoice-label">عنوان المحفظة (<?= $h($invoice['pay_currency'] ?? 'USDT') ?>)</div>
    <div class="invoice-addr" id="pay-addr"><?= $h($invoice['pay_address']) ?></div>
    <button onclick="copyAddr()" class="copy-btn">📋 نسخ العنوان</button>

    <div style="margin:18px 0;text-align:center">
      <div class="invoice-label" style="margin-bottom:6px">المبلغ المطلوب</div>
      <div style="font-size:28px;font-weight:800;color:var(--red2)">
        <?= $h($invoice['pay_amount'] ?? '') ?> <?= $h($invoice['pay_currency'] ?? '') ?>
      </div>
      <div style="font-size:13px;color:var(--muted)">
        ≈ $<?= $h($invoice['price_amount'] ?? '') ?> USD
      </div>
    </div>

    <?php if (!empty($invoice['qr_data'])): ?>
    <img src="<?= $h($invoice['qr_data']) ?>" alt="QR Code" class="ys-qr" width="160" height="160">
    <?php endif; ?>

    <div class="invoice-note">
      📌 أرسل المبلغ بالضبط إلى العنوان أعلاه.<br>
      ✅ سيتم تفعيل اشتراكك تلقائياً خلال دقائق من استلام التأكيد.<br>
      📧 ستصلك رسالة تأكيد على <?= $h($me['email'] ?? '') ?>.<br>
      ❓ مشكلة؟ <a href="mailto:info@yassota.com">تواصل معنا</a>
    </div>

    <?php elseif (!empty($invoice['invoice_url'])): ?>
    <p style="margin-bottom:16px;font-size:14px;color:var(--muted)">
      اضغط الزر أدناه للانتقال إلى صفحة الدفع الآمنة:
    </p>
    <a href="<?= $h($invoice['invoice_url']) ?>" target="_blank" class="pay-btn" style="display:block;text-align:center;text-decoration:none">
      💳 الانتقال لصفحة الدفع
    </a>
    <?php else: ?>
    <div class="pay-error">تعذّر الحصول على تفاصيل الدفع — تواصل معنا مباشرة.</div>
    <?php endif; ?>
  </div>

  <p style="text-align:center;margin-top:20px">
    <a href="?tier=<?= $h($selectedTier) ?>" style="color:var(--muted);font-size:13px">
      ← إلغاء والعودة
    </a>
  </p>

  <?php endif; ?>

  <!-- Back to Szad -->
  <div style="text-align:center;margin-top:32px;font-size:13px;color:var(--muted)">
    <a href="<?= defined('SITE_URL') ? $h(rtrim(SITE_URL, '/')) : '' ?>/tools/szad/">
      ← العودة إلى صَعد AI
    </a>
  </div>

</div><!-- /pay-wrap -->

<?php if ($invoice): ?>
<script>
/* 20-minute countdown */
(function() {
  var end = Date.now() + 20 * 60 * 1000;
  var box = document.getElementById('cdbox');
  var cd  = document.getElementById('cdtime');
  function tick() {
    var rem = Math.max(0, Math.round((end - Date.now()) / 1000));
    var m   = String(Math.floor(rem / 60)).padStart(2, '0');
    var s   = String(rem % 60).padStart(2, '0');
    cd.textContent = m + ':' + s;
    if (rem === 0) {
      box.classList.add('expired');
      cd.textContent = 'انتهى الوقت';
    } else {
      setTimeout(tick, 1000);
    }
  }
  tick();
})();

function copyAddr() {
  var addr = document.getElementById('pay-addr').textContent.trim();
  navigator.clipboard.writeText(addr).then(function() {
    var btn = document.querySelector('.copy-btn');
    var old = btn.textContent;
    btn.textContent = '✓ تم النسخ!';
    setTimeout(function() { btn.textContent = old; }, 2000);
  });
}
</script>
<?php endif; ?>
</body>
</html>
