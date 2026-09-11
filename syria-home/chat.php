<?php
/**
 * Public storefront assistant — AJAX endpoint for the floating chat widget.
 * POST json { message, history: [{role,content}, ...] } -> { ok, reply }
 * Powered by OpenRouter's free-tier models (see includes/ai/OpenRouterClient.php).
 * Never used for anything beyond answering questions about the storefront —
 * no admin/file/SQL access, same guarantee as the admin AI assistant.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'reply' => 'طريقة غير مسموحة.']);
    exit;
}

if (!OpenRouterClient::isConfigured()) {
    echo json_encode([
        'ok' => false,
        'reply' => 'المساعد الذكي غير مُفعّل بعد. تواصل معنا مباشرة عبر صفحة "تواصل معنا" وسنساعدك باختيار الباقة المناسبة.',
    ]);
    exit;
}

$raw = json_decode((string)file_get_contents('php://input'), true);
$userMsg = trim((string)($raw['message'] ?? ''));
$history = is_array($raw['history'] ?? null) ? $raw['history'] : [];

if ($userMsg === '' || mb_strlen($userMsg) > 1000) {
    echo json_encode(['ok' => false, 'reply' => 'اكتب رسالة واضحة أولاً.']);
    exit;
}

$messages = [['role' => 'system', 'content' => storefront_chatbot_prompt()]];
foreach (array_slice($history, -8) as $h) {
    $role = $h['role'] ?? '';
    $content = trim((string)($h['content'] ?? ''));
    if ($content !== '' && in_array($role, ['user', 'assistant'], true)) {
        $messages[] = ['role' => $role, 'content' => mb_substr($content, 0, 1000)];
    }
}
$messages[] = ['role' => 'user', 'content' => $userMsg];

$res = OpenRouterClient::chat($messages);

if ($res['ok']) {
    echo json_encode(['ok' => true, 'reply' => $res['reply']]);
} else {
    echo json_encode([
        'ok' => false,
        'reply' => 'تعذّر الوصول للمساعد الآن، جرّب مرة أخرى أو تواصل معنا مباشرة عبر صفحة "تواصل معنا".',
    ]);
}
