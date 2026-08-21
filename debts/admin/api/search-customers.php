<?php
require_once __DIR__ . '/../../config.php';
requireAdminLogin();
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q, 'UTF-8') < 1) {
    echo json_encode(['results' => []]);
    exit;
}

$currency = $settings['currency'] ?? 'ل.س';
$stmt = $pdo->prepare(
    "SELECT c.id, c.name, c.phone,
        COALESCE(SUM(CASE WHEN t.type='purchase' THEN t.amount ELSE -t.amount END),0) AS balance
     FROM customers c
     LEFT JOIN transactions t ON t.customer_id = c.id
     WHERE c.name LIKE :q
     GROUP BY c.id
     ORDER BY (c.name LIKE :qstart) DESC, c.name ASC
     LIMIT 8"
);
$stmt->execute(['q' => '%' . $q . '%', 'qstart' => $q . '%']);
$rows = $stmt->fetchAll();

$results = array_map(function ($r) use ($currency) {
    return [
        'id' => (int)$r['id'],
        'name' => $r['name'],
        'phone' => $r['phone'],
        'balance_fmt' => number_format((float)$r['balance'], 0) . ' ' . $currency,
    ];
}, $rows);

echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
