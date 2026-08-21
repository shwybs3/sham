<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

/* ══════════════════════════════════════════════════════════
   config.php — دفتر الدكان
   ✏️  لا تعدّل هذا الملف — عدّل config.local.php بدلاً منه
   ══════════════════════════════════════════════════════════ */

/* ──────────────────────────────────────────────────────────
   1. تحميل الإعدادات المحلية (محمية من التحديثات)
   ──────────────────────────────────────────────────────── */
$_localConfig = __DIR__ . '/config.local.php';
if (file_exists($_localConfig)) {
    require_once $_localConfig;
} else {
    // قيم افتراضية — يُفضَّل استخدام config.local.php
    $DB_HOST    = 'localhost';
    $DB_NAME    = 'shop_debts';
    $DB_USER    = 'shop_debts_user';
    $DB_PASS    = 'CHANGE_ME';
    $DB_PORT    = 3306;
    $DB_CHARSET = 'utf8mb4';
    if (!defined('SITE_URL'))  define('SITE_URL',  'https://dark.yassota.com');
    if (!defined('SITE_LANG')) define('SITE_LANG', 'ar');
}
unset($_localConfig);

/* ──────────────────────────────────────────────────────────
   2. الاتصال بقاعدة البيانات
   ──────────────────────────────────────────────────────── */
try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset={$DB_CHARSET}",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(503);
    error_log('[dukkan] DB error: ' . $e->getMessage());
    die('تعذّر الاتصال بقاعدة البيانات. حاول لاحقًا.');
}
unset($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS, $DB_PORT, $DB_CHARSET);

/* ──────────────────────────────────────────────────────────
   3. تحميل الدوال والأيقونات والأمان
   ──────────────────────────────────────────────────────── */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/icons.php';
require_once __DIR__ . '/includes/security.php';

$settings = getAllSettings($pdo);
