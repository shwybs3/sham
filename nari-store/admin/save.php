<?php
define('NARI_ADMIN', true);
require_once __DIR__ . '/auth.php';
nari_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$token = $_POST['csrf'] ?? '';
if (!nari_csrf_check($token)) {
    header('Location: index.php?error=csrf');
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'save_settings') {
    $settings = nari_read_json(SETTINGS_FILE);

    $settings['site_name']            = trim($_POST['site_name'] ?? ($settings['site_name'] ?? 'ناري ستور'));
    $settings['tagline']              = trim($_POST['tagline'] ?? ($settings['tagline'] ?? ''));
    $settings['whatsapp_number']      = preg_replace('/\D/', '', $_POST['whatsapp_number'] ?? ($settings['whatsapp_number'] ?? ''));
    $settings['whatsapp_display']     = trim($_POST['whatsapp_display'] ?? ($settings['whatsapp_display'] ?? ''));
    $settings['announcement_enabled'] = isset($_POST['announcement_enabled']);
    $settings['announcement_text']    = trim($_POST['announcement_text'] ?? ($settings['announcement_text'] ?? ''));
    $settings['maintenance_mode']     = isset($_POST['maintenance_mode']);
    $settings['maintenance_message']  = trim($_POST['maintenance_message'] ?? ($settings['maintenance_message'] ?? ''));
    $settings['instagram_url']        = trim($_POST['instagram_url'] ?? ($settings['instagram_url'] ?? ''));
    $settings['tiktok_url']           = trim($_POST['tiktok_url'] ?? ($settings['tiktok_url'] ?? ''));
    $settings['facebook_url']         = trim($_POST['facebook_url'] ?? ($settings['facebook_url'] ?? ''));
    $settings['ad_header_code']       = trim($_POST['ad_header_code'] ?? ($settings['ad_header_code'] ?? ''));

    if ($settings['whatsapp_number'] === '') {
        header('Location: index.php?error=phone');
        exit;
    }

    nari_write_json(SETTINGS_FILE, $settings);
    header('Location: index.php?saved=settings');
    exit;
}

if ($action === 'save_ai') {
    $settings = nari_read_json(SETTINGS_FILE);

    $settings['ai_chat_enabled']         = isset($_POST['ai_chat_enabled']);
    $settings['openrouter_api_key']      = trim($_POST['openrouter_api_key'] ?? ($settings['openrouter_api_key'] ?? ''));
    $settings['openrouter_model']        = trim($_POST['openrouter_model'] ?? ($settings['openrouter_model'] ?? ''));
    $settings['ai_system_prompt']        = trim($_POST['ai_system_prompt'] ?? ($settings['ai_system_prompt'] ?? ''));
    $turns = (int)($_POST['ai_max_turns_per_session'] ?? 12);
    $settings['ai_max_turns_per_session'] = $turns > 0 ? $turns : 12;

    nari_write_json(SETTINGS_FILE, $settings);
    header('Location: index.php?saved=ai');
    exit;
}

if ($action === 'save_services') {
    $services = nari_read_json(SERVICES_FILE);
    $posted = $_POST['services'] ?? [];

    foreach ($services as &$svc) {
        $id = $svc['id'] ?? '';
        $svc['active'] = isset($posted[$id]['active']);
        $svc['note'] = isset($posted[$id]['note']) ? trim((string)$posted[$id]['note']) : ($svc['note'] ?? '');
    }
    unset($svc);

    nari_write_json(SERVICES_FILE, $services);
    header('Location: index.php?saved=services');
    exit;
}

if ($action === 'save_products') {
    $products = nari_read_json(PRODUCTS_FILE);
    $posted = $_POST['products'] ?? [];

    foreach ($products as &$p) {
        $id = $p['id'] ?? '';
        if (!isset($posted[$id])) {
            continue;
        }
        $price = preg_replace('/\D/', '', $posted[$id]['price_syp'] ?? '');
        $p['price_syp'] = $price === '' ? 0 : (int)$price;
        $p['active'] = isset($posted[$id]['active']);
        $p['note'] = trim((string)($posted[$id]['note'] ?? ($p['note'] ?? '')));
    }
    unset($p);

    nari_write_json(PRODUCTS_FILE, $products);
    header('Location: products.php?saved=1');
    exit;
}

if ($action === 'change_password') {
    // ملاحظة: هذا الإجراء يعرض لك القيمة الجاهزة لوضعها يدوياً في config.php
    // لأن استضافات مجانية كثيرة تمنع الكتابة على ملفات .php من داخل السكربت نفسه لأسباب أمنية.
    $new = (string)($_POST['new_password'] ?? '');
    if (strlen($new) < 8) {
        header('Location: index.php?error=weak_password');
        exit;
    }
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $_SESSION['nari_generated_hash'] = $hash;
    header('Location: index.php?saved=password_hash');
    exit;
}

header('Location: index.php');
exit;
