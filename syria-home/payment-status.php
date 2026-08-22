<?php
/** Tiny public polling endpoint for the checkout page. order_id is an
 *  unguessable 24-hex-char token, so this leaks nothing beyond a status
 *  string to anyone who doesn't already have that link. */
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$orderId = $_GET['order'] ?? '';
if (!preg_match('/^[a-f0-9]{24}$/', $orderId)) {
    echo json_encode(['status' => 'unknown']);
    exit;
}

$stmt = $pdo->prepare("SELECT status FROM payments WHERE order_id = ? LIMIT 1");
$stmt->execute([$orderId]);
$status = $stmt->fetchColumn();

echo json_encode(['status' => $status ?: 'unknown']);
