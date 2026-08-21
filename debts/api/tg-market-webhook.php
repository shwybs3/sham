<?php
/**
 * api/tg-market-webhook.php — استقبال أسعار السوق من مجموعات تيليجرام
 *
 * الإعداد:
 * 1. أضف البوت لمجموعة السوق
 * 2. سجّل الـ Webhook في Telegram:
 *    curl "https://api.telegram.org/bot{TOKEN}/setWebhook?url=https://yoursite.com/api/tg-market-webhook.php&secret_token={SECRET}"
 */
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

// التحقق من الـ secret token
$secret = $settings['tg_market_webhook_secret'] ?? '';
if ($secret) {
    $incomingSecret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    if (!hash_equals($secret, $incomingSecret)) {
        http_response_code(403);
        die('Forbidden');
    }
}

$body   = file_get_contents('php://input');
$update = json_decode($body, true);

if (!$update) exit('ok');

$message = $update['message'] ?? $update['channel_post'] ?? null;
if (!$message) exit('ok');

$text      = $message['text']   ?? $message['caption'] ?? '';
$groupName = $message['chat']['title'] ?? $message['chat']['username'] ?? 'telegram';
$groupId   = (string)($message['chat']['id'] ?? '');

if (!$text || strlen($text) < 5) exit('ok');

// استخراج الأسعار من النص
$extracted = extractPricesFromTgMessage($text);

if (!empty($extracted)) {
    $stmt = $pdo->prepare(
        "INSERT INTO market_prices_raw (product_name, price, currency, unit, source, source_group, raw_text)
         VALUES (?, ?, ?, ?, 'telegram', ?, ?)"
    );
    foreach ($extracted as $item) {
        $stmt->execute([$item['name'], $item['price'], $item['currency'], $item['unit'], $groupName, mb_substr($text, 0, 500)]);
    }
}

echo 'ok';

/* ── دالة استخراج الأسعار من النص العربي ── */
function extractPricesFromTgMessage(string $text): array {
    $results = [];
    $lines   = preg_split('/[\n\r]+/', $text);

    foreach ($lines as $line) {
        $line = trim($line);
        if (strlen($line) < 4) continue;

        // نمط 1: "اسم المنتج + رقم + عملة"
        // مثال: "رز 15000 ل.س" أو "دجاج الكيلو 20000 ليرة" أو "خبز 1$"
        $pattern1 = '/([ا-يa-zA-Z\s\-\/]{2,30}?)\s*[:=]?\s*(\d[\d,\.]*)\s*(ل\.?س|ليرة سورية|ليرة|دولار|dollar|\$|تركي|₺|lira)/iu';

        if (preg_match_all($pattern1, $line, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $product = trim(preg_replace('/\s+/', ' ', $match[1]));
                $price   = (float)str_replace(',', '', $match[2]);
                $curRaw  = mb_strtolower($match[3]);

                $currency = 'SYP';
                if (str_contains($curRaw, 'دولار') || str_contains($curRaw, 'dollar') || $curRaw === '$') {
                    $currency = 'USD';
                } elseif (str_contains($curRaw, 'تركي') || $curRaw === '₺' || $curRaw === 'lira') {
                    $currency = 'TRY';
                }

                // استخراج الوحدة (كيلو، لتر، قطعة)
                $unit = 'قطعة';
                if (preg_match('/(كيلو|كغ|kg)/iu', $product)) $unit = 'كيلو';
                elseif (preg_match('/(لتر|l)/iu', $product)) $unit = 'لتر';
                elseif (preg_match('/(حبة|بيضة)/iu', $product)) $unit = 'حبة';
                elseif (preg_match('/(ربطة)/iu', $product)) $unit = 'ربطة';

                // تنظيف اسم المنتج من الوحدات
                $product = trim(preg_replace('/(كيلو|كغ|لتر|الكيلو|اللتر)\s*/iu', '', $product));

                if ($product && $price > 0 && strlen($product) >= 2) {
                    $results[] = ['name' => $product, 'price' => $price, 'currency' => $currency, 'unit' => $unit];
                }
            }
        }

        // نمط 2: "منتج: سعر" (بدون ذكر العملة — نفترض SYP)
        if (preg_match('/^([ا-يa-zA-Z\s]{2,25})\s*[:：]\s*(\d[\d,\.]+)\s*$/', $line, $m2)) {
            $product = trim($m2[1]);
            $price   = (float)str_replace(',', '', $m2[2]);
            if ($product && $price > 0) {
                $results[] = ['name' => $product, 'price' => $price, 'currency' => 'SYP', 'unit' => 'قطعة'];
            }
        }
    }

    return $results;
}
