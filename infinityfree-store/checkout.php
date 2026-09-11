<?php
/**
 * checkout.php — ينشئ فاتورة NOWPayments لطلب جديد، أو يعرض/يفحص حالة
 * طلب موجود عبر order_id المخزّن في الجلسة (بدون تسجيل دخول للعميل).
 *   POST (من نموذج index.php)            -> ينشئ الطلب ثم يحوّل إلى ?order=
 *   GET  ?order=ID                        -> يعرض صفحة الدفع
 *   GET  ?order=ID&status=1  (fetch/AJAX) -> يرجع JSON بحالة الدفع الحالية
 */
require __DIR__ . '/config.php';

if (!empty($_GET['order'])) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? LIMIT 1");
    $stmt->execute([$_GET['order']]);
    $order = $stmt->fetch();
    if (!$order || empty($_SESSION['orders'][$order['order_id']])) { http_response_code(404); exit('الطلب غير موجود.'); }

    if (isset($_GET['status'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => $order['status']]);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { $error = 'انتهت صلاحية الجلسة، يرجى المحاولة مجدداً من الصفحة الرئيسية.'; }
    else {
        $pkgId = (int)($_POST['package_id'] ?? 0);
        $qty = max(1, min(20, (int)($_POST['quantity'] ?? 1)));
        $target = trim((string)($_POST['target_info'] ?? ''));
        $contact = trim((string)($_POST['contact'] ?? ''));
        $currency = (string)($_POST['currency'] ?? '');

        $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ? AND active = 1");
        $stmt->execute([$pkgId]);
        $pkg = $stmt->fetch();

        if (!$pkg) $error = 'الباقة المطلوبة غير متوفرة.';
        elseif ($target === '' || $contact === '') $error = 'يرجى تعبئة كل الحقول.';
        elseif (!array_key_exists($currency, NP_CURRENCIES)) $error = 'اختر عملة صالحة.';
        elseif (!np_configured()) $error = 'الدفع بالعملات الرقمية غير مُفعّل بعد على هذا المتجر.';
        else {
            $priceUsd = round((float)$pkg['price_usd'] * $qty, 2);
            $orderId = bin2hex(random_bytes(12));
            $result = np_create_payment($priceUsd, $currency, $orderId, substr($pkg['name'] . ' x' . $qty, 0, 180));
            if ($result['ok']) {
                $pdo->prepare("INSERT INTO orders (order_id, package_id, package_name, quantity, target_info, contact, price_usd, pay_currency, pay_address, pay_amount, payment_id)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$orderId, $pkg['id'], $pkg['name'], $qty, $target, $contact, $priceUsd, $result['pay_currency'], $result['pay_address'], $result['pay_amount'], $result['payment_id']]);
                $_SESSION['orders'][$orderId] = true;
                header('Location: checkout.php?order=' . $orderId);
                exit;
            }
            $error = $result['error'];
        }
    }
} else {
    header('Location: index.php');
    exit;
}
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>الدفع | <?= e(setting('site_title')) ?></title>
<meta name="robots" content="noindex">
<style>
body{margin:0;font-family:'Courier New',ui-monospace,monospace;background:#0a0a0c;color:#f2f2f4;padding:40px 16px}
body::after{content:"";position:fixed;top:-30%;left:50%;transform:translateX(-50%);width:800px;height:500px;max-width:120vw;background:radial-gradient(circle,rgba(225,6,0,.2),transparent 60%);pointer-events:none;z-index:0}
.box{max-width:420px;margin:0 auto;background:#141417;border:1px solid #ff0033;border-radius:8px;padding:24px;position:relative;z-index:1;box-shadow:0 0 40px rgba(255,0,51,.25)}
h1{font-size:17px;margin:0 0 16px;color:#ff0033;text-shadow:0 0 12px rgba(255,0,51,.55)}
.err{background:#1a0507;border:1px solid #8a0018;color:#ff6b81;padding:10px;border-radius:6px;font-size:13px}
img.qr{display:block;margin:16px auto;border-radius:8px;border:1px solid #ff0033}
label{display:block;font-size:12px;color:#8a8a92;margin:12px 0 4px}
input{width:100%;box-sizing:border-box;padding:9px 10px;border-radius:4px;border:1px solid #2a1416;background:#000;color:#f2f2f4;font-size:13px;direction:ltr;text-align:center;font-family:inherit}
.copy{width:100%;margin-top:8px;padding:9px;border:1px solid #ff0033;border-radius:4px;background:transparent;color:#ff0033;cursor:pointer;font-size:12.5px;font-family:inherit;font-weight:800}
.copy:hover{background:#e10600;color:#fff}
.ok{background:#031a0d;border:1px solid #16a34a;color:#4ade80;padding:14px;border-radius:8px;font-size:13.5px}
a.back{color:#ff0033;font-size:13px}
</style></head><body>
<div class="box">
<?php if (!empty($error)): ?>
  <h1>تعذّر إتمام الطلب</h1>
  <div class="err"><?= e($error) ?></div>
  <p><a class="back" href="index.php">⟵ رجوع للمتجر</a></p>
<?php elseif (isset($order)): ?>
  <h1>💳 <?= e($order['package_name']) ?> × <?= (int)$order['quantity'] ?></h1>
  <div id="payStatus">
  <?php if (in_array($order['status'], ['finished', 'confirmed'], true)): ?>
    <div class="ok">✅ تم تأكيد الدفع! سنبدأ بتنفيذ طلبك قريباً وسنتواصل معك عبر: <?= e($order['contact']) ?></div>
  <?php else: ?>
    <img class="qr" width="180" height="180" src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($order['pay_address']) ?>">
    <label>المبلغ (<?= e(strtoupper($order['pay_currency'])) ?>)</label>
    <input readonly value="<?= e(rtrim(rtrim(number_format((float)$order['pay_amount'], 8, '.', ''), '0'), '.')) ?>">
    <label>عنوان الإرسال</label>
    <input id="addr" readonly value="<?= e($order['pay_address']) ?>">
    <button class="copy" onclick="navigator.clipboard.writeText(document.getElementById('addr').value)">نسخ العنوان</button>
    <p style="font-size:12px;color:#8a8a92;margin-top:14px">⏳ بانتظار الدفع (≈ $<?= number_format((float)$order['price_usd'], 2) ?>)... سيتحدّث هذا الصفحة تلقائياً.</p>
  <?php endif; ?>
  </div>
  <script>
  (function poll(){
    fetch('checkout.php?order=<?= urlencode($order['order_id']) ?>&status=1').then(function(r){return r.json();}).then(function(d){
      if (d.status === 'finished' || d.status === 'confirmed') location.reload(); else setTimeout(poll, 6000);
    }).catch(function(){ setTimeout(poll, 8000); });
  })();
  </script>
<?php endif; ?>
</div>
</body></html>
