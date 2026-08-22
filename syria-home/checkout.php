<?php
/**
 * Real crypto checkout (NOWPayments). Three flows share this one page:
 *   ?type=product&id=N        — buy a store product
 *   ?type=tip&amount=5&label=...&content_type=article&content_id=N (optional)
 *   ?type=unlock_article&id=N — unlock a premium article
 *   ?type=unlock_tool&id=N    — unlock a premium tool
 *
 * Flow: GET without ?order= shows a currency picker -> POST creates the
 * NOWPayments invoice once and redirects to ?order=ID -> GET with ?order=
 * just displays that existing invoice and polls payment-status.php.
 * This avoids creating a duplicate invoice if the page is refreshed.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$identifier = visitor_identifier();
$error = '';

/* ── Already-created invoice: just show it ── */
if (!empty($_GET['order'])) {
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? AND identifier = ? LIMIT 1");
    $stmt->execute([$_GET['order'], $identifier]);
    $payment = $stmt->fetch();
    if (!$payment) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
}
/* ── Create a new invoice ── */
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Your session expired — please try again.';
    } elseif (!NOWPayments::isConfigured()) {
        $error = 'Crypto payments are not set up yet on this site.';
    } else {
        $type = $_POST['type'] ?? '';
        $currency = $_POST['currency'] ?? '';
        $priceUsd = 0; $label = ''; $refId = null;

        if ($type === 'product') {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status='published'");
            $stmt->execute([(int)($_POST['id'] ?? 0)]);
            $row = $stmt->fetch();
            if ($row) { $priceUsd = (float)$row['price']; $label = $row['name']; $refId = (int)$row['id']; }
        } elseif ($type === 'unlock_article') {
            $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ? AND status='published' AND is_premium=1");
            $stmt->execute([(int)($_POST['id'] ?? 0)]);
            $row = $stmt->fetch();
            if ($row) { $priceUsd = (float)$row['premium_price']; $label = $row['title']; $refId = (int)$row['id']; }
        } elseif ($type === 'unlock_tool') {
            $stmt = $pdo->prepare("SELECT * FROM tools WHERE id = ? AND status='published' AND is_premium=1");
            $stmt->execute([(int)($_POST['id'] ?? 0)]);
            $row = $stmt->fetch();
            if ($row) { $priceUsd = (float)$row['premium_price']; $label = $row['name']; $refId = (int)$row['id']; }
        } elseif ($type === 'tip') {
            $priceUsd = round((float)($_POST['amount'] ?? 0), 2);
            $label = 'Support: ' . trim((string)($_POST['label'] ?? setting('site_name')));
        }

        if ($priceUsd <= 0) {
            $error = 'That item is not available for checkout right now.';
        } elseif (!array_key_exists($currency, NOWPayments::CURRENCIES)) {
            $error = 'Please choose a currency.';
        } else {
            $orderId = bin2hex(random_bytes(12));
            $result = NOWPayments::createPayment($priceUsd, $currency, $orderId, substr($label, 0, 180));
            if ($result['ok']) {
                $email = trim((string)($_POST['email'] ?? ''));
                $stmt = $pdo->prepare("INSERT INTO payments
                    (order_id, identifier, reference_type, reference_id, reference_label, customer_email, price_usd, pay_currency, pay_address, pay_amount, status)
                    VALUES (?,?,?,?,?,?,?,?,?,?, 'pending')");
                $stmt->execute([$orderId, $identifier, $type, $refId, $label, $email, $priceUsd, $result['pay_currency'], $result['pay_address'], $result['pay_amount']]);
                header('Location: ' . site_url('checkout.php?order=' . $orderId));
                exit;
            }
            $error = $result['error'];
        }
    }
    // fall through to re-render the picker with the error
    $pendingType = $_POST['type'] ?? ($_GET['type'] ?? 'tip');
    $pendingId = $_POST['id'] ?? ($_GET['id'] ?? '');
    $pendingAmount = $_POST['amount'] ?? ($_GET['amount'] ?? '');
    $pendingLabel = $_POST['label'] ?? ($_GET['label'] ?? '');
}
/* ── First visit: show the currency picker ── */
else {
    $pendingType = $_GET['type'] ?? '';
    $pendingId = $_GET['id'] ?? '';
    $pendingAmount = $_GET['amount'] ?? '';
    $pendingLabel = $_GET['label'] ?? '';
    if (!in_array($pendingType, ['product', 'tip', 'unlock_article', 'unlock_tool'], true)) {
        http_response_code(404); require __DIR__ . '/404.php'; exit;
    }
}
?><!doctype html><html lang="en"><head>
<?php seo_head(['title' => 'Checkout | ' . setting('site_name'), 'description' => 'Secure crypto checkout.', 'canonical' => site_url('checkout.php')]); ?>
<meta name="robots" content="noindex">
</head><body>
<?php site_header(); ?>

<div class="container" style="max-width:560px;padding:50px 20px 70px">

<?php if (isset($payment)): ?>
  <?php
    $isFinal = in_array($payment['status'], ['finished', 'confirmed'], true);
  ?>
  <div class="tool-shell">
    <h1 style="font-size:20px;margin-top:0"><i class="fa-solid fa-circle-dollar-to-slot" style="color:var(--brand1)"></i> <?= e($payment['reference_label']) ?></h1>

    <div id="payStatus">
      <?php if ($isFinal): ?>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#047857;padding:16px;border-radius:12px">
          <i class="fa-solid fa-circle-check"></i> Payment confirmed — thank you!
          <?php if ($payment['reference_type'] === 'product'): ?><p style="margin:8px 0 0">We'll be in touch at the email you provided with your download.</p><?php endif; ?>
          <?php if (str_starts_with($payment['reference_type'], 'unlock_')): ?><p style="margin:8px 0 0">Content unlocked — <a href="javascript:history.back()">go back to view it</a>.</p><?php endif; ?>
        </div>
      <?php else: ?>
        <p style="color:var(--muted);font-size:13.5px">Send exactly the amount below to this address. This page updates automatically once payment is detected on the network.</p>
        <div style="text-align:center;margin:18px 0">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?= urlencode($payment['pay_address']) ?>" width="180" height="180" style="border-radius:10px;border:1px solid var(--line)">
        </div>
        <label>Amount (<?= e(strtoupper($payment['pay_currency'])) ?>)</label>
        <input type="text" readonly value="<?= e(rtrim(rtrim(number_format((float)$payment['pay_amount'], 8, '.', ''), '0'), '.')) ?>">
        <label>Address</label>
        <input type="text" id="payAddr" readonly value="<?= e($payment['pay_address']) ?>">
        <button class="btn-ghost" style="margin-top:10px" onclick="navigator.clipboard.writeText(document.getElementById('payAddr').value)">Copy address</button>
        <p class="hint" style="margin-top:14px"><i class="fa-solid fa-hourglass-half"></i> Waiting for payment… (~<?= e(number_format((float)$payment['price_usd'], 2)) ?> USD)</p>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$isFinal): ?>
  <script>
  (function poll() {
    fetch('<?= site_url('payment-status.php?order=' . urlencode($payment['order_id'])) ?>')
      .then(r => r.json())
      .then(d => { if (d.status === 'finished' || d.status === 'confirmed') location.reload(); else setTimeout(poll, 6000); })
      .catch(() => setTimeout(poll, 8000));
  })();
  </script>
  <?php endif; ?>

<?php else: ?>
  <div class="tool-shell">
    <h1 style="font-size:20px;margin-top:0"><i class="fa-solid fa-wallet" style="color:var(--brand1)"></i> Pay with crypto</h1>

    <?php if (!NOWPayments::isConfigured()): ?>
      <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:14px;border-radius:10px;font-size:13.5px">Crypto payments aren't configured on this site yet.</div>
    <?php else: ?>
      <?php if ($error): ?><div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px;border-radius:10px;font-size:13.5px;margin-bottom:14px"><?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="type" value="<?= e($pendingType) ?>">
        <input type="hidden" name="id" value="<?= e((string)$pendingId) ?>">
        <input type="hidden" name="label" value="<?= e((string)$pendingLabel) ?>">

        <?php if ($pendingType === 'tip'): ?>
          <label>Amount (USD)</label>
          <input type="text" name="amount" value="<?= e((string)($pendingAmount ?: '5')) ?>" required>
        <?php endif; ?>

        <?php if ($pendingType === 'product'): ?>
          <label>Email (for delivery)</label>
          <input type="text" name="email" placeholder="you@example.com" required>
        <?php endif; ?>

        <label>Pay with</label>
        <select name="currency" required>
          <option value="">— choose a currency —</option>
          <?php foreach (NOWPayments::CURRENCIES as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?>
        </select>

        <button class="btn-run" style="margin-top:16px;width:100%" type="submit"><i class="fa-solid fa-wallet"></i> Create payment</button>
      </form>
    <?php endif; ?>
  </div>
<?php endif; ?>

</div>
<?php site_footer(); ?>
</body></html>
