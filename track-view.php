<?php
// Fired by navigator.sendBeacon() on every real app-page load (see app.php),
// independent of whether that page's HTML came from cache — this is what
// keeps the view counter accurate now that app.php is cached.
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $pdo->prepare("UPDATE apps SET views=views+1 WHERE id=? AND status='published'")->execute([$id]);
}
http_response_code(204);
