<?php
/* ============================================================
   إنشاء/تحديث حساب الأدمن — شغّله مرة واحدة فقط بعد الرفع
   ثم احذف هذا الملف (أو مجلد install/) بالكامل من السيرفر فوراً

   الاستخدام (مرة واحدة، من المتصفح):
   https://yourdomain.com/install/create-admin.php?u=admin&p=كلمة_مرور_قوية_جداً
   ============================================================ */

require_once __DIR__ . '/../config.php';

header('Content-Type: text/plain; charset=utf-8');

$u = trim($_GET['u'] ?? '');
$p = trim($_GET['p'] ?? '');

if ($u === '' || $p === '') {
    http_response_code(400);
    exit("الاستخدام: create-admin.php?u=اسم_المستخدم&p=كلمة_مرور_قوية (10 أحرف على الأقل)\nمثال: create-admin.php?u=admin&p=MyStr0ngP@ss2026");
}
if (strlen($p) < 10) {
    http_response_code(400);
    exit("كلمة المرور قصيرة جداً — استخدم 10 أحرف على الأقل تتضمن أرقاماً ورموزاً.");
}
if (!preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $u)) {
    http_response_code(400);
    exit("اسم مستخدم غير صالح — استخدم أحرفاً/أرقاماً إنجليزية فقط.");
}

createOrUpdateAdmin($u, $p);

echo "تم إنشاء/تحديث حساب الأدمن بنجاح.\n";
echo "اسم المستخدم: $u\n";
echo "\n⚠️  مهم جداً: احذف هذا الملف (install/create-admin.php) من السيرفر الآن قبل أي شيء آخر.\n";
echo "سجّل الدخول من: " . siteUrl('admin.php') . "\n";
