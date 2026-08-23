<?php
/** Rating endpoint — receives one star vote and returns the fresh average. */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST only']);
    exit;
}

$type   = $_POST['type'] ?? '';
$id     = (int)($_POST['id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);

if (!in_array($type, ['article', 'tool', 'product'], true) || $id <= 0 || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid rating']);
    exit;
}

/* Only allow votes on content that actually exists and is published. */
$table = ['article' => 'articles', 'tool' => 'tools', 'product' => 'products'][$type];
$stmt  = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE id = ? AND status = 'published'");
$stmt->execute([$id]);
if (!(int)$stmt->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Not found']);
    exit;
}

try {
    $summary = rating_cast($type, $id, $rating);
    echo json_encode(['ok' => true, 'avg' => (float)$summary['avg'], 'count' => (int)$summary['count']]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save your rating']);
}
