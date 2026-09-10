<?php
define('NARI_ADMIN', true);
require_once __DIR__ . '/auth.php';

$error = '';

if (nari_is_logged_in()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (!nari_csrf_check($token)) {
        $error = 'انتهت صلاحية الجلسة، أعد المحاولة.';
    } elseif ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['nari_admin_logged_in'] = true;
        $_SESSION['nari_admin_user'] = $username;
        header('Location: index.php');
        exit;
    } else {
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
    }
}

$csrf = nari_csrf_token();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول | لوحة تحكم ناري ستور</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <h1>🔥 لوحة تحكم ناري ستور</h1>
    <p class="sub">سجّل الدخول لإدارة إعدادات الموقع والخدمات</p>
    <?php if ($error): ?>
      <div class="error-box"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <div class="field">
        <label for="username">اسم المستخدم</label>
        <input type="text" id="username" name="username" autocomplete="username" required autofocus>
      </div>
      <div class="field">
        <label for="password">كلمة المرور</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn">تسجيل الدخول</button>
    </form>
  </div>
</div>
</body>
</html>
