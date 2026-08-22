<?php
/**
 * webhook.php — NOWPayments IPN endpoint
 * Premium is ONLY granted here — never from browser
 */
require_once __DIR__.'/config.php';
require_once __DIR__.'/storage.php';

http_response_code(200);
header('Content-Type: application/json');

$raw     = file_get_contents('php://input');
$sig     = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';
$secret  = cfg('np_ipn_secret', NP_IPN_SECRET);

if (!$secret || !$sig) { echo json_encode(['ok'=>false]); exit; }

$data = json_decode($raw, true);
if (!is_array($data)) { echo json_encode(['ok'=>false]); exit; }

function ipn_sort(array $a): array {
    ksort($a); foreach ($a as $k=>$v) if (is_array($v)) $a[$k]=ipn_sort($v); return $a;
}
$canonical  = json_encode(ipn_sort($data), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
$expected   = hash_hmac('sha512', $canonical, $secret);

if (!hash_equals($expected, $sig)) {
    error_log('[webhook] Signature mismatch order='.($data['order_id']??'?'));
    echo json_encode(['ok'=>false]); exit;
}

$orderId = $data['order_id'] ?? '';
$status  = $data['payment_status'] ?? '';
$paid    = (float)($data['actually_paid'] ?? 0);
$price   = (float)($data['price_amount'] ?? 0);

if (!$orderId) { echo json_encode(['ok'=>false]); exit; }

$pay = pay_get($orderId);
if (!$pay) { echo json_encode(['ok'=>false]); exit; }
if ($pay['status'] === 'finished') { echo json_encode(['ok'=>true]); exit; }

$pay['status'] = $status; $pay['actually_paid'] = $paid; $pay['raw_ipn'] = $raw;
pay_save($pay);

if (in_array($status, ['finished','confirmed'], true) && $paid >= $price * 0.98) {
    grant_plan($pay['uid'], $pay['plan'], (int)($pay['days'] ?? 30));
    error_log('[webhook] Plan granted uid='.$pay['uid'].' plan='.$pay['plan'].' order='.$orderId);
}

echo json_encode(['ok'=>true]);
