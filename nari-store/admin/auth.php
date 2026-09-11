<?php
/** أدوات الجلسة والتحقق من تسجيل الدخول للوحة تحكم ناري ستور. */

if (!defined('NARI_ADMIN')) {
    http_response_code(403);
    exit('محظور');
}

require_once __DIR__ . '/config.php';

session_name(ADMIN_SESSION_NAME);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function nari_is_logged_in(): bool
{
    return !empty($_SESSION['nari_admin_logged_in']);
}

function nari_require_login(): void
{
    if (!nari_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function nari_csrf_token(): string
{
    if (empty($_SESSION['nari_csrf'])) {
        $_SESSION['nari_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['nari_csrf'];
}

function nari_csrf_check(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['nari_csrf']) && hash_equals($_SESSION['nari_csrf'], $token);
}

function nari_read_json(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function nari_write_json(string $path, array $data): bool
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }
    return file_put_contents($path, $json) !== false;
}
