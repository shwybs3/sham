<?php
/**
 * db-test.php — أداة تشخيص مؤقتة
 * ارفعه، افتحه في المتصفح، ثم احذفه فورًا بعد حل المشكلة
 */

// ✏️ نفس بيانات config.php تمامًا
$DB_HOST    = 'localhost';
$DB_NAME    = 'shop_debts';
$DB_USER    = 'shop_debts_user';
$DB_PASS    = 'CHANGE_ME';
$DB_PORT    = 3306;
$DB_CHARSET = 'utf8mb4';

echo '<meta charset="UTF-8">';
echo '<h2>🔍 تشخيص الاتصال بقاعدة البيانات</h2>';

// 1. فحص إصدار PHP
echo '<p>✅ PHP: <strong>' . PHP_VERSION . '</strong></p>';

// 2. فحص وجود PDO
echo '<p>' . (extension_loaded('pdo') ? '✅' : '❌') . ' PDO extension</p>';
echo '<p>' . (extension_loaded('pdo_mysql') ? '✅' : '❌') . ' pdo_mysql extension</p>';

// 3. محاولة الاتصال وعرض الخطأ الحقيقي
echo '<hr><h3>محاولة الاتصال:</h3>';
try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset={$DB_CHARSET}",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo '<p style="color:green;font-size:18px">✅ <strong>الاتصال ناجح!</strong></p>';
    $v = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo '<p>MySQL version: <strong>' . htmlspecialchars($v) . '</strong></p>';
} catch (PDOException $e) {
    echo '<p style="color:red;font-size:16px">❌ <strong>فشل الاتصال</strong></p>';
    echo '<pre style="background:#fee;padding:12px;border-radius:8px">';
    echo htmlspecialchars($e->getMessage());
    echo '</pre>';
    echo '<h3>🔧 الحلول الممكنة حسب الخطأ:</h3><ul>';
    echo '<li>Access denied → اسم المستخدم أو كلمة المرور خاطئة</li>';
    echo '<li>Unknown database → اسم قاعدة البيانات خاطئ</li>';
    echo '<li>Connection refused → DB_HOST خاطئ أو MySQL لا يعمل</li>';
    echo '<li>Can\'t connect to MySQL → جرب 127.0.0.1 بدل localhost</li>';
    echo '</ul>';
}
echo '<hr><p style="color:red"><strong>⚠️ احذف هذا الملف فورًا بعد التشخيص!</strong></p>';
