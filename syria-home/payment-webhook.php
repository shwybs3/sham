<?php
/**
 * payment-webhook.php — NOWPayments IPN endpoint.
 * The ONLY place that marks a payment paid or grants a premium unlock.
 * Called directly by NOWPayments' servers, never from a browser — the
 * HMAC-SHA512 signature check below is what makes that safe to trust.
 */
require_once __DIR__ . '/config.php';

http_response_code(200);
header('Content-Type: application/json');

$rawBody = file_get_contents('php://input');
$receivedSig = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';

if (!NOWPayments::verifyIpnSignature($rawBody, $receivedSig)) {
    error_log('[payment-webhook] rejected: signature mismatch or missing');
    echo json_encode(['ok' => false]);
    exit;
}

$data = json_decode($rawBody, true);
$orderId = $data['order_id'] ?? '';
$paymentStatus = $data['payment_status'] ?? '';
$actuallyPaid = (float)($data['actually_paid'] ?? 0);

if ($orderId === '') { echo json_encode(['ok' => false]); exit; }

$stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? LIMIT 1");
$stmt->execute([$orderId]);
$payment = $stmt->fetch();
if (!$payment) { echo json_encode(['ok' => false]); exit; }

if (in_array($payment['status'], ['finished', 'confirmed'], true)) {
    echo json_encode(['ok' => true]); // already processed
    exit;
}

$pdo->prepare("UPDATE payments SET status = ?, actually_paid = ?, raw_ipn_json = ?, updated_at = NOW() WHERE order_id = ?")
    ->execute([$paymentStatus, $actuallyPaid, $rawBody, $orderId]);

// Require both a final-ish status AND that the paid amount (in the same
// crypto currency NOWPayments quoted) actually covers what was invoiced —
// a status of "confirmed" alone can still precede an underpayment.
$isConfirmed = in_array($paymentStatus, ['finished', 'confirmed'], true)
    && $actuallyPaid >= ((float)$payment['pay_amount'] * 0.98);
if ($isConfirmed) {
    switch ($payment['reference_type']) {
        case 'product':
            $product = null;
            if ($payment['reference_id']) {
                $ps = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                $ps->execute([$payment['reference_id']]);
                $product = $ps->fetch();
            }
            $pdo->prepare("INSERT INTO orders (product_id, product_name, name, email, note, amount, currency, status)
                VALUES (?,?,?,?,?,?,?, 'paid')")
                ->execute([
                    $payment['reference_id'],
                    $product['name'] ?? $payment['reference_label'],
                    $payment['customer_email'] ?: 'crypto-checkout',
                    $payment['customer_email'],
                    'Paid via NOWPayments — order ' . $orderId,
                    $payment['price_usd'],
                    'USD',
                ]);
            break;

        case 'unlock_article':
            $pdo->prepare("INSERT IGNORE INTO unlocks (identifier, content_type, content_id, order_id) VALUES (?, 'article', ?, ?)")
                ->execute([$payment['identifier'], $payment['reference_id'], $orderId]);
            break;

        case 'unlock_tool':
            $pdo->prepare("INSERT IGNORE INTO unlocks (identifier, content_type, content_id, order_id) VALUES (?, 'tool', ?, ?)")
                ->execute([$payment['identifier'], $payment['reference_id'], $orderId]);
            break;

        case 'tip':
            // Nothing further to do — the payments row itself is the record, visible in Admin > Payments.
            break;
    }
}

echo json_encode(['ok' => true]);
