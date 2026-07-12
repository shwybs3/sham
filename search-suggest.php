<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) { echo json_encode([]); exit; }

$stmt = $pdo->prepare("SELECT name,slug,icon_path FROM apps
    WHERE status='published' AND name LIKE ? ORDER BY downloads DESC LIMIT 6");
$stmt->execute(['%' . $q . '%']);
$rows = $stmt->fetchAll();

echo json_encode(array_map(fn($r) => [
    'name' => $r['name'],
    'url'  => app_url($r['slug']),
    'icon' => $r['icon_path'] ? url($r['icon_path']) : null,
], $rows), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
