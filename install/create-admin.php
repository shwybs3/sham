<?php
/**
 * شغّل هذا الملف مرة واحدة فقط لإنشاء حساب الأدمن
 * الاستخدام: https://yoursite.com/install/create-admin.php?u=admin&p=YourPassword123
 * احذف الملف فوراً بعد التنفيذ
 */
require_once __DIR__ . '/../config.php';

$u = trim($_GET['u'] ?? '');
$p = trim($_GET['p'] ?? '');

if (!$u || !$p) {
    die('<div style="font:14px monospace;padding:2rem;background:#f5f7fb;color:#2563eb">
    الاستخدام: ?u=username&p=password (8 أحرف على الأقل)</div>');
}
if (strlen($p) < 8) {
    die('<div style="font:14px monospace;padding:2rem;background:#f5f7fb;color:#dc2626">كلمة المرور يجب أن تكون 8 أحرف على الأقل</div>');
}

$check = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username=?");
$check->execute([$u]);
if ($check->fetchColumn() > 0) {
    die('<div style="font:14px monospace;padding:2rem;background:#f5f7fb;color:#dc2626">اسم المستخدم موجود مسبقاً</div>');
}

$pdo->prepare("INSERT INTO admins (username,password_hash) VALUES(?,?)")
    ->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);

echo '<div style="font:14px monospace;padding:2rem;background:#f5f7fb;color:#16a34a">
✓ تم إنشاء حساب الأدمن بنجاح<br><br>
<a href="../admin.php" style="color:#2563eb">→ ادخل للوحة التحكم</a><br><br>
<span style="color:#dc2626">⚠️ احذف هذا الملف (install/create-admin.php) الآن من السيرفر فوراً!</span>
</div>';
