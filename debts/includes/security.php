<?php
/**
 * includes/security.php — حماية شاملة ضد الهجمات
 * ─────────────────────────────────────────────────
 * - Rate limiting (تحديد معدل الطلبات)
 * - Security headers (رؤوس الأمان)
 * - IP blocklist
 * - Brute force protection
 * - Bot/DDoS mitigation
 */

/* ── إرسال رؤوس الأمان ── */
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    // Content-Security-Policy - مرن ليتوافق مع Google GSI و Cloudflare Turnstile
    header("Content-Security-Policy: default-src 'self'; "
         . "script-src 'self' 'unsafe-inline' https://accounts.google.com https://www.google.com https://www.gstatic.com https://challenges.cloudflare.com; "
         . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "font-src 'self' https://fonts.gstatic.com; "
         . "img-src 'self' data: https:; "
         . "frame-src https://accounts.google.com https://www.google.com https://challenges.cloudflare.com; "
         . "connect-src 'self';");
}

/* ── الحصول على IP الحقيقي ── */
function getClientIp(): string {
    $trusted = ['127.0.0.1', '::1'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR'] as $h) {
        if (!empty($_SERVER[$h])) {
            $candidate = trim(explode(',', $_SERVER[$h])[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $candidate;
            }
        }
    }
    return $ip;
}

/* ── Rate Limiter (مستند إلى ملفات) ── */
function rateLimitCheck(string $key, int $maxRequests = 120, int $windowSec = 60): bool {
    $dir = sys_get_temp_dir() . '/dukkan_rl/';
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    $file = $dir . hash('sha256', $key);
    $now  = time();

    $data = [];
    if (file_exists($file)) {
        $raw = @file_get_contents($file);
        if ($raw) $data = json_decode($raw, true) ?? [];
    }
    // حذف الطلبات القديمة
    $data = array_values(array_filter($data, fn($t) => $t > $now - $windowSec));

    if (count($data) >= $maxRequests) {
        return false; // تجاوز الحد
    }

    $data[] = $now;
    @file_put_contents($file, json_encode($data), LOCK_EX);
    return true;
}

/* ── حماية من Brute Force (تسجيل دخول) ── */
function loginAttemptCheck(string $ip): bool {
    return rateLimitCheck('login_' . $ip, 5, 300); // 5 محاولات / 5 دقائق
}
function loginAttemptReset(string $ip): void {
    $dir  = sys_get_temp_dir() . '/dukkan_rl/';
    $file = $dir . hash('sha256', 'login_' . $ip);
    @unlink($file);
}

/* ── فحص الطلب الحالي ── */
$_clientIp = getClientIp();

// قائمة الـ User Agents المحظورة (بوتات معروفة)
$_badUa = ['masscan', 'nikto', 'sqlmap', 'zgrab', 'nmap', 'dirbuster', 'gobuster', 'hydra'];
$_ua    = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
foreach ($_badUa as $_b) {
    if (str_contains($_ua, $_b)) {
        http_response_code(403);
        die('403 Forbidden');
    }
}
unset($_badUa, $_ua, $_b);

// Rate limit عام: 200 طلب / دقيقة لكل IP
if (!rateLimitCheck('global_' . $_clientIp, 200, 60)) {
    http_response_code(429);
    header('Retry-After: 60');
    die('429 Too Many Requests — حاول بعد دقيقة.');
}

/* ── دالة التحقق من Remember-Me للمدير ── */
function checkAdminRememberToken(PDO $pdo): bool {
    $raw = $_COOKIE['admin_remember'] ?? '';
    if (strlen($raw) < 32) return false;

    try {
        $hash = hash('sha256', $raw);
        $stmt = $pdo->prepare(
            "SELECT art.admin_id FROM admin_remember_tokens art
             WHERE art.token_hash = ? AND art.expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if (!$row) return false;

        // تجديد الكوكي
        $expires = time() + 5 * 365 * 24 * 3600;
        $secure  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
        setcookie('admin_remember', $raw, $expires, '/', '', $secure, true);

        // جلب بيانات المدير
        $a = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
        $a->execute([$row['admin_id']]);
        $admin = $a->fetch();
        if (!$admin) return false;

        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['admin_id']       = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
