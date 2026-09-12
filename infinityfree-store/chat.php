<?php
/** chat.php — نقطة نهاية AJAX لروبوت الدردشة (OpenRouter، نماذج مجانية). */
require __DIR__ . '/config.php';

header('Content-Type: application/json');
$message = trim((string)($_POST['message'] ?? ''));
if ($message === '' || mb_strlen($message) > 600) {
    echo json_encode(['ok' => false, 'error' => 'رسالة غير صالحة.']);
    exit;
}

$cats = $pdo->query("SELECT category, name, price_usd FROM packages WHERE active=1 ORDER BY category, sort_order")->fetchAll();
$catalog = '';
foreach ($cats as $p) $catalog .= "- [{$p['category']}] {$p['name']}: \${$p['price_usd']}\n";

$system = "أنت مساعد مبيعات لمتجر \"" . setting('site_title') . "\" الذي يبيع خدمات زيادة متابعين ومشاهدات لمنصات التواصل الاجتماعي، اشتراكات تيليجرام بريميوم، وأدوات/استشارات أمن سيبراني. "
    . "أجب بالعربية، بإيجاز ووضوح، وساعد الزائر على اختيار الباقة المناسبة وشرح طريقة الدفع (عملات رقمية عبر NOWPayments — يضغط الزائر زر اطلب الآن على الباقة ثم يختار العملة ويحوّل المبلغ لعنوان الدفع الظاهر). لا تدّعِ تقديم خدمات غير موجودة في القائمة، ولا تطلب من الزائر كلمة مرور حسابه أبداً. قائمة الباقات الحالية:\n" . $catalog;

if (empty($_SESSION['chat_history'])) $_SESSION['chat_history'] = [];
$history = array_slice($_SESSION['chat_history'], -6);
$messages = array_merge([['role' => 'system', 'content' => $system]], $history, [['role' => 'user', 'content' => $message]]);

$result = or_chat($messages);
if ($result['ok']) {
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $result['text']];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -10);
    echo json_encode(['ok' => true, 'reply' => $result['text']]);
} else {
    echo json_encode(['ok' => false, 'error' => $result['error']]);
}
