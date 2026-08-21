<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (($settings['support_enabled'] ?? '1') !== '1') {
    echo json_encode(['ok'=>false]); exit;
}

$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$name    = mb_substr(trim($input['name']    ?? ''), 0, 100);
$message = mb_substr(trim($input['message'] ?? ''), 0, 500);

if (!$message) { echo json_encode(['ok'=>false,'error'=>'الرسالة فارغة']); exit; }

$currentUser = getCurrentSiteUser($pdo);
$userId = $currentUser ? $currentUser['id'] : null;
if (!$name && $currentUser) $name = $currentUser['display_name'];

try {
    $pdo->prepare("INSERT INTO support_messages (user_id, name, message, ip, created_at) VALUES (?,?,?,?,NOW())")
        ->execute([$userId, $name, $message, $_SERVER['REMOTE_ADDR'] ?? '']);
} catch (PDOException) {}

// إرسال إشعار تيليجرام إن توفر
$token  = $settings['telegram_bot_token'] ?? '';
$chatId = $settings['telegram_chat_id']   ?? '';
if ($token && $chatId) {
    $text = "📨 رسالة دعم جديدة\n👤 ".($name?:"مجهول")."\n💬 $message";
    @file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?chat_id=".urlencode($chatId)."&text=".urlencode($text));
}

echo json_encode(['ok' => true]);
