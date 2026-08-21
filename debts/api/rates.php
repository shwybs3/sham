<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// يقبل GET (عرض) أو POST (تحديث)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['rates' => getLatestRates($pdo), 'ok' => true]);
    exit;
}

// POST: جلب وحفظ الأسعار
$rates = fetchExchangeRates();

if (empty($rates)) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'فشل جلب الأسعار من جميع المصادر']);
    exit;
}

// حفظ في قاعدة البيانات
saveExchangeRates($pdo, $rates);

// تحقق من التغييرات وأرسل تنبيه تيليجرام
$alertEnabled   = ($settings['rate_alert_enabled']  ?? '0') === '1';
$threshold      = (float)($settings['rate_alert_threshold'] ?? 3);
$telegramToken  = $settings['telegram_bot_token'] ?? '';
$telegramChatId = $settings['telegram_chat_id']   ?? '';

if ($alertEnabled) {
    checkAndAlertRateChange($pdo, $rates, $telegramToken, $telegramChatId, $threshold);
}

// إضافة سعر الليرة السورية اليدوي للاستجابة
$sypManual = (float)($settings['syp_manual_rate'] ?? 0);
if ($sypManual > 0) {
    $rates['SYP'] = ['rate' => $sypManual, 'source' => 'manual'];
}

echo json_encode(['ok' => true, 'rates' => $rates, 'count' => count($rates)]);
