<?php
require_once __DIR__ . '/../../config.php';
requireAdminLogin();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'طريقة غير مسموحة']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (empty($input['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $input['csrf'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'انتهت صلاحية الجلسة، أعد تحميل الصفحة.']);
    exit;
}

$customerId   = isset($input['customer_id']) ? (int)$input['customer_id'] : 0;
$customerName = trim($input['customer_name'] ?? '');
$type         = $input['type']        ?? '';
$amount       = (float)($input['amount'] ?? 0);
$description  = trim($input['description']  ?? '');
$productType  = mb_substr(trim($input['product_type'] ?? ''), 0, 100);

if (!in_array($type, ['purchase', 'payment'], true)) {
    echo json_encode(['ok' => false, 'error' => 'نوع العملية غير صالح']);
    exit;
}
if ($amount <= 0) {
    echo json_encode(['ok' => false, 'error' => 'المبلغ يجب أن يكون أكبر من صفر']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($customerId > 0) {
        $customer = getCustomer($pdo, $customerId);
        if (!$customer) throw new RuntimeException('الزبون غير موجود');
    } else {
        if ($customerName === '') throw new RuntimeException('اكتب اسم الزبون');
        $customer = findOrCreateCustomer($pdo, $customerName);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO transactions (customer_id, type, amount, description, product_type, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())"
    );
    $stmt->execute([
        $customer['id'],
        $type,
        $amount,
        $description !== '' ? $description : null,
        $productType  !== '' ? $productType  : null,
    ]);

    $pdo->commit();

    $currencyCode = $settings['currency'] ?? 'SYP';
    $currency     = currencySymbol($currencyCode);
    $newBalance   = getCustomerBalance($pdo, (int)$customer['id']);

    echo json_encode([
        'ok' => true,
        'customer' => [
            'id'          => (int)$customer['id'],
            'name'        => $customer['name'],
            'balance'     => $newBalance,
            'balance_fmt' => number_format($newBalance, 0) . ' ' . $currency,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
