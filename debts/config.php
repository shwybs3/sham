<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

/* ══════════════════════════════════════════════════════════
   config.php — دفتر الدكان
   ✏️  عدّل القسمين (1) و(2) ببيانات استضافتك فقط
   ══════════════════════════════════════════════════════════ */

/* ──────────────────────────────────────────────────────────
   1. بيانات قاعدة البيانات ← عدّلها
   ──────────────────────────────────────────────────────── */
$DB_HOST    = 'localhost';
$DB_NAME    = 'shop_debts';       // اسم قاعدة البيانات
$DB_USER    = 'shop_debts_user';  // اسم المستخدم
$DB_PASS    = 'CHANGE_ME';        // كلمة المرور
$DB_PORT    = 3306;
$DB_CHARSET = 'utf8mb4';

/* ──────────────────────────────────────────────────────────
   2. إعدادات الموقع ← عدّلها
   ──────────────────────────────────────────────────────── */
define('SITE_URL',  'https://dark.yassota.com'); // بدون / في النهاية
define('SITE_LANG', 'ar');

/* ──────────────────────────────────────────────────────────
   3. الاتصال بقاعدة البيانات — لا تعدّل هذا القسم
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
    // ── لتشخيص المشكلة مؤقتًا: غيّر السطر التالي إلى true ──
    $showDbError = false;
    if ($showDbError) {
        die('<pre>DB Error: ' . htmlspecialchars($e->getMessage()) . '</pre>');
    }
    die('تعذّر الاتصال بقاعدة البيانات. حاول لاحقًا.');
}

// تنظيف المتغيرات الحساسة من الذاكرة
unset($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS, $DB_PORT, $DB_CHARSET);

/* ──────────────────────────────────────────────────────────
   4. تحميل الدوال والأيقونات
   ──────────────────────────────────────────────────────── */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/icons.php';

$settings = getAllSettings($pdo);
