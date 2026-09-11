<?php
/**
 * webhook.php — نقطة استقبال IPN من NOWPayments. المكان الوحيد الذي
 * يُحدّث حالة الطلب إلى "مدفوع". يستدعيه خادم NOWPayments مباشرة، وليس
 * المتصفح — التحقق من توقيع HMAC-SHA512 أدناه هو ما يجعل الوثوق به آمناً.
 */
require __DIR__ . '/config.php';

http_response_code(200);
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';

if (!np_verify_ipn($raw, $sig)) {
    error_log('[webhook] rejected: bad/missing signature');
    echo json_encode(['ok' => false]);
    exit;
}

$data = json_decode($raw, true);
$orderId = $data['order_id'] ?? '';
$status = $data['payment_status'] ?? '';
$paid = (float)($data['actually_paid'] ?? 0);
if ($orderId === '') { echo json_encode(['ok' => false]); exit; }

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? LIMIT 1");
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order) { echo json_encode(['ok' => false]); exit; }

if (in_array($order['status'], ['finished', 'confirmed'], true)) { echo json_encode(['ok' => true]); exit; }

$pdo->prepare("UPDATE orders SET status = ?, actually_paid = ?, raw_ipn_json = ? WHERE order_id = ?")
    ->execute([$status, $paid, $raw, $orderId]);

echo json_encode(['ok' => true]);
