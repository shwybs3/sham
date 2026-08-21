<?php
/**
 * api/backup.php — إرسال نسخة احتياطية عبر تيليجرام
 * الاستخدام: GET ?key=SECRET_KEY أو من لوحة الإدارة
 */
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

$key    = trim($_GET['key'] ?? '');
$isAdmin = false;

if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['admin_id'])) $isAdmin = true;

$backupKey = $settings['backup_key'] ?? '';
if (!$isAdmin && ($backupKey === '' || $key !== $backupKey)) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'غير مصرح']);
    exit;
}

$token  = $settings['telegram_bot_token'] ?? '';
$chatId = $settings['telegram_chat_id']   ?? '';

if (!$token || !$chatId) {
    echo json_encode(['ok'=>false,'error'=>'إعدادات تيليجرام غير مكتملة']);
    exit;
}

$ok = sendTelegramBackup($pdo, $token, $chatId);
echo json_encode(['ok' => $ok, 'time' => date('Y-m-d H:i:s')]);
