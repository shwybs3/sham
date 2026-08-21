<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (($settings['chat_enabled'] ?? '1') !== '1') {
    echo json_encode(['ok'=>false,'error'=>'الدردشة غير مفعلة']);
    exit;
}

$currentUser = getCurrentSiteUser($pdo);

// GET — جلب الرسائل الجديدة
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $afterId = (int)($_GET['after'] ?? 0);
    if (!$currentUser) { echo json_encode(['messages' => []]); exit; }
    $msgs = getChatMessages($pdo, 30, $afterId);
    $result = array_map(fn($m) => [
        'id'           => (int)$m['id'],
        'user_id'      => (int)$m['user_id'],
        'display_name' => $m['display_name'],
        'username'     => $m['username'],
        'avatar_url'   => $m['avatar_url'],
        'message'      => $m['message'],
        'time'         => date('H:i', strtotime($m['created_at'])),
    ], $msgs);
    echo json_encode(['messages' => $result]);
    exit;
}

// POST — إرسال رسالة
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false]); exit;
}
if (!$currentUser) { echo json_encode(['ok'=>false,'error'=>'يجب تسجيل الدخول']); exit; }

$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$message = trim($input['message'] ?? '');
if (!$message || mb_strlen($message) > 500) {
    echo json_encode(['ok'=>false,'error'=>'الرسالة غير صالحة']); exit;
}

// حد معدل: رسالة كل 3 ثوانٍ
$recent = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE user_id=? AND created_at > NOW() - INTERVAL 3 SECOND");
$recent->execute([$currentUser['id']]);
if ($recent->fetchColumn() > 0) {
    echo json_encode(['ok'=>false,'error'=>'انتظر قليلاً قبل الإرسال']); exit;
}

$id = addChatMessage($pdo, $currentUser['id'], $message);
echo json_encode([
    'ok'  => true,
    'msg' => [
        'id'           => $id,
        'user_id'      => $currentUser['id'],
        'display_name' => $currentUser['display_name'],
        'username'     => $currentUser['username'],
        'avatar_url'   => $currentUser['avatar_url'],
        'message'      => $message,
        'time'         => date('H:i'),
    ]
]);
