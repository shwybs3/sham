<?php
/**
 * auth/google.php — معالجة Google One Tap / Sign-In callback
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function jsonErr(string $msg): void { echo json_encode(['ok'=>false,'error'=>$msg]); exit; }

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$credential    = trim($input['credential']    ?? '');
$captchaToken  = trim($input['captcha_token'] ?? '');
$redirect      = $input['redirect'] ?? 'community.php';

if (!$credential) jsonErr('بيانات غير صالحة');

$clientId = $settings['google_client_id'] ?? '';
if (!$clientId) jsonErr('تسجيل Google غير مفعّل');

// التحقق من CAPTCHA إن كان مفعلاً
$cfEnabled = ($settings['cf_turnstile_enabled'] ?? '0') === '1';
$rcEnabled = ($settings['recaptcha_enabled']   ?? '0') === '1';
if (($cfEnabled || $rcEnabled) && !verifyCaptcha($settings, $captchaToken)) {
    jsonErr('فشل التحقق من CAPTCHA، أعد المحاولة');
}

// التحقق من JWT الصادر عن Google
function verifyGoogleJWT(string $token, string $clientId): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    $payload = json_decode(base64_decode(str_pad(strtr($parts[1],'-_','+/'), strlen($parts[1])%4==0?strlen($parts[1]):strlen($parts[1])+(4-strlen($parts[1])%4), '=', STR_PAD_RIGHT)), true);
    if (!$payload) return null;
    if (($payload['aud'] ?? '') !== $clientId) return null;
    if (($payload['iss'] ?? '') !== 'https://accounts.google.com' && ($payload['iss'] ?? '') !== 'accounts.google.com') return null;
    if (($payload['exp'] ?? 0) < time()) return null;
    return $payload;
}

$payload = verifyGoogleJWT($credential, $clientId);
if (!$payload) {
    // محاولة التحقق عبر Google API كبديل
    $ch = curl_init("https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($credential));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8]);
    $body = curl_exec($ch);
    curl_close($ch);
    $payload = json_decode($body, true);
    if (!$payload || ($payload['aud'] ?? '') !== $clientId) jsonErr('التحقق فشل، حاول مجدداً');
}

$googleId = $payload['sub']     ?? '';
$email    = $payload['email']   ?? '';
$name     = $payload['name']    ?? $email;
$avatar   = $payload['picture'] ?? '';

if (!$googleId || !$email) jsonErr('بيانات Google غير مكتملة');

// تسجيل أو تحديث المستخدم
$user = upsertSiteUser($pdo, $googleId, $email, $name, $avatar);
if (!$user) jsonErr('خطأ في إنشاء الحساب');

$_SESSION['site_user_id'] = $user['id'];

// تصفية الـ redirect
$safeRedirect = preg_match('/^[a-zA-Z0-9\-_.\/]+\.php$/', $redirect) ? $redirect : 'community.php';

echo json_encode(['ok' => true, 'redirect' => $safeRedirect]);
