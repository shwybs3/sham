<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck()) {
        $error = 'انتهت صلاحية الجلسة، أعد المحاولة.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        usleep(250000);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
        }
    }
}
$shopName = $settings['shop_name'] ?? 'دفتر الدكان';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول — لوحة الإدارة</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  body{
    display:flex; align-items:center; justify-content:center; min-height:100vh; padding:20px;
    background:
      radial-gradient(700px 400px at 20% 0%, rgba(34,197,142,.09), transparent 60%),
      radial-gradient(600px 360px at 100% 100%, rgba(240,180,41,.06), transparent 55%),
      var(--bg);
  }
  .login-box{
    width:100%; max-width:380px; background:var(--panel); border:1px solid var(--line);
    border-radius:20px; padding:38px 34px; animation:fadeUp .4s var(--ease);
    box-shadow:0 24px 60px rgba(0,0,0,.4);
  }
  .login-mark{
    width:52px; height:52px; border-radius:14px; margin:0 auto 18px;
    background:linear-gradient(135deg, var(--brand), #17966b); color:#04150e;
    display:flex; align-items:center; justify-content:center;
  }
  .login-box h1{font-family:var(--disp); font-size:20px; text-align:center; margin:0 0 4px;}
  .login-box .sub{text-align:center; color:var(--ink-faint); font-size:13px; margin-bottom:28px; font-family:var(--mono);}
  .input-icon{position:relative;}
  .input-icon .icon{position:absolute; top:50%; transform:translateY(-50%); inset-inline-start:14px; color:var(--ink-faint); pointer-events:none;}
  .input-icon input{padding-inline-start:42px;}
</style>
</head>
<body>
  <div class="login-box">
    <div class="login-mark"><?= icon('shield', 24) ?></div>
    <h1><?= h($shopName) ?></h1>
    <div class="sub">لوحة الإدارة</div>
    <?php if ($error): ?>
    <div class="notice err" style="background:rgba(239,106,95,.1); border:1px solid rgba(239,106,95,.3); color:var(--debt); padding:11px 15px; border-radius:10px; margin-bottom:18px; font-size:13.5px; display:flex; align-items:center; gap:8px;">
      <?= icon('alert', 16) ?> <?= h($error) ?>
    </div>
    <?php endif; ?>
    <form method="post" action="login.php">
      <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
      <div class="field">
        <label for="username">اسم المستخدم</label>
        <div class="input-icon">
          <?= icon('user', 18) ?>
          <input type="text" id="username" name="username" required autofocus>
        </div>
      </div>
      <div class="field">
        <label for="password">كلمة المرور</label>
        <div class="input-icon">
          <?= icon('key', 18) ?>
          <input type="password" id="password" name="password" required>
        </div>
      </div>
      <button class="btn btn-primary btn-block" type="submit" style="margin-top:6px;"><?= icon('login', 18) ?> دخول</button>
    </form>
  </div>
</body>
</html>
