<?php
/**
 * setup.php
 * شغّله مرة واحدة فقط من المتصفح لإنشاء أول حساب إداري، ثم احذف هذا
 * الملف فورًا من الاستضافة لأسباب أمنية.
 */
require_once __DIR__ . '/config.php';

$done = false;
$error = '';

$existingCount = (int)$pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($existingCount > 0) {
        $error = 'يوجد حساب إداري بالفعل. احذف هذا الملف.';
    } elseif ($username === '' || strlen($password) < 6) {
        $error = 'اسم مستخدم صالح وكلمة مرور لا تقل عن 6 أحرف.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>الإعداد الأولي</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="display:flex; align-items:center; justify-content:center; min-height:100vh;">
<div style="max-width:400px; width:100%; background:var(--panel); border:1px solid var(--line); border-radius:16px; padding:30px;">
<h1 style="font-family:var(--disp); font-size:19px;"><?= icon('shield', 20) ?> إعداد أول حساب إداري</h1>

<?php if ($done): ?>
  <div class="notice ok" style="background:rgba(34,197,142,.1); border:1px solid rgba(34,197,142,.3); color:var(--brand); padding:12px 16px; border-radius:10px; margin-top:14px;">
    <?= icon('check-circle', 16) ?> تم إنشاء الحساب بنجاح.
  </div>
  <p style="margin-top:16px;"><a class="btn btn-primary" href="admin/login.php"><?= icon('login', 16) ?> الذهاب لتسجيل الدخول</a></p>
  <p style="color:var(--debt); font-size:13px; margin-top:20px;"><?= icon('alert', 14) ?> احذف ملف setup.php من الاستضافة الآن لأسباب أمنية.</p>
<?php elseif ($existingCount > 0): ?>
  <div class="notice err" style="background:rgba(239,106,95,.1); border:1px solid rgba(239,106,95,.3); color:var(--debt); padding:12px 16px; border-radius:10px; margin-top:14px;">
    <?= icon('alert', 16) ?> يوجد حساب إداري بالفعل. احذف هذا الملف من الاستضافة.
  </div>
<?php else: ?>
  <?php if ($error): ?><div class="notice err" style="background:rgba(239,106,95,.1); border:1px solid rgba(239,106,95,.3); color:var(--debt); padding:12px 16px; border-radius:10px; margin:14px 0;"><?= icon('alert', 16) ?> <?= h($error) ?></div><?php endif; ?>
  <form method="post" style="margin-top:16px;">
    <div class="field"><label>اسم المستخدم</label><input type="text" name="username" required></div>
    <div class="field"><label>كلمة المرور (6 أحرف على الأقل)</label><input type="password" name="password" required minlength="6"></div>
    <button class="btn btn-primary btn-block" type="submit"><?= icon('check', 16) ?> إنشاء الحساب</button>
  </form>
<?php endif; ?>
</div>
</body>
</html>
