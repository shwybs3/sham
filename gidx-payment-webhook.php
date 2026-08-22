<?php
/**
 * gidx-payment-webhook.php
 * IPN endpoint for NOWPayments — the ONLY place that grants Premium.
 * Called directly by NOWPayments servers, never from a browser.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/gidx-functions.php';

http_response_code(200);
header('Content-Type: application/json');

$rawBody     = file_get_contents('php://input');
$receivedSig = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';

$ipnSecret = get_cfg($pdo, 'nowpayments_ipn_secret', '');
if ($ipnSecret === '' || $receivedSig === '') {
    error_log('[gidx-webhook] رُفض: لا يوجد IPN secret مُعدّ أو لا يوجد توقيع بالطلب');
    echo json_encode(['ok' => false]); exit;
}

$data = json_decode($rawBody, true);
if (!is_array($data)) { echo json_encode(['ok' => false]); exit; }

function gidx_ipn_sort(array $arr): array {
    ksort($arr);
    foreach ($arr as $k => $v) if (is_array($v)) $arr[$k] = gidx_ipn_sort($v);
    return $arr;
}
$canonical   = json_encode(gidx_ipn_sort($data), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$expectedSig = hash_hmac('sha512', $canonical, $ipnSecret);

if (!hash_equals($expectedSig, $receivedSig)) {
    error_log('[gidx-webhook] رُفض: توقيع غير مطابق — order_id=' . ($data['order_id'] ?? '?'));
    echo json_encode(['ok' => false]); exit;
}

$orderId       = $data['order_id']       ?? '';
$paymentStatus = $data['payment_status'] ?? '';
$actuallyPaid  = (float)($data['actually_paid'] ?? 0);
$priceAmount   = (float)($data['price_amount']   ?? 15);

if ($orderId === '') { echo json_encode(['ok' => false]); exit; }

$stmt = $pdo->prepare("SELECT * FROM gidx_payments WHERE order_id = ? LIMIT 1");
$stmt->execute([$orderId]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$payment) { echo json_encode(['ok' => false]); exit; }

if ($payment['status'] === 'finished') { echo json_encode(['ok' => true]); exit; }

$pdo->prepare("UPDATE gidx_payments SET status = ?, actually_paid = ?, raw_ipn_json = ?, updated_at = NOW() WHERE order_id = ?")
    ->execute([$paymentStatus, $actuallyPaid, $rawBody, $orderId]);

$isConfirmed = in_array($paymentStatus, ['finished', 'confirmed'], true);
$amountOk    = $actuallyPaid >= ($priceAmount * 0.98);

if ($isConfirmed && $amountOk) {
    gidx_grant_premium($pdo, $payment['identifier'], 30);
    error_log("[gidx-webhook] Premium granted — identifier={$payment['identifier']} order_id=$orderId");
}

echo json_encode(['ok' => true]);
