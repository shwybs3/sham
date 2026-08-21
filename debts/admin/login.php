<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// تحقق من كوكي "تذكرني" أولاً
if (checkAdminRememberToken($pdo)) {
    header('Location: index.php');
    exit;
}

$ip    = getClientIp();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck()) {
        $error = 'انتهت صلاحية الجلسة، أعد المحاولة.';
    } elseif (!loginAttemptCheck($ip)) {
        $error = 'تم تجاوز عدد المحاولات المسموح بها. حاول بعد 5 دقائق.';
    } else {
        $username   = trim($_POST['username'] ?? '');
        $password   = $_POST['password'] ?? '';
        $rememberMe = !empty($_POST['remember_me']);

        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        usleep(250000); // تأخير مقصود لإعاقة الـ brute force

        if ($user && password_verify($password, $user['password_hash'])) {
            loginAttemptReset($ip);
            session_regenerate_id(true);
            $_SESSION['admin_id']       = $user['id'];
            $_SESSION['admin_username'] = $user['username'];

            // ── تذكرني للأبد ──
            if ($rememberMe) {
                $rawToken = bin2hex(random_bytes(32));
                $hash     = hash('sha256', $rawToken);
                $expires  = date('Y-m-d H:i:s', time() + 5 * 365 * 24 * 3600);
                // إزالة الرموز القديمة لهذا المستخدم (تنظيف)
                $pdo->prepare("DELETE FROM admin_remember_tokens WHERE admin_id = ?")->execute([$user['id']]);
                $pdo->prepare("INSERT INTO admin_remember_tokens (admin_id, token_hash, expires_at) VALUES (?,?,?)")
                    ->execute([$user['id'], $hash, $expires]);
                $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
                setcookie('admin_remember', $rawToken, time() + 5 * 365 * 24 * 3600, '/', '', $secure, true);
            }

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
<link rel="stylesheet" href="../assets/css/style.css?v=<?= cssVersion() ?>">
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
  .remember-row{display:flex; align-items:center; gap:10px; margin:10px 0 16px; font-size:14px; color:var(--ink-dim); cursor:pointer;}
  .remember-row input{width:17px; height:17px; cursor:pointer; accent-color:var(--brand);}
</style>
</head>
<body>
  <div class="login-box">
    <div class="login-mark"><?= icon('shield', 24) ?></div>
    <h1><?= h($shopName) ?></h1>
    <div class="sub">لوحة الإدارة</div>
    <?php if ($error): ?>
    <div style="background:rgba(239,106,95,.1); border:1px solid rgba(239,106,95,.3); color:var(--debt); padding:11px 15px; border-radius:10px; margin-bottom:18px; font-size:13.5px; display:flex; align-items:center; gap:8px;">
      <?= icon('alert', 16) ?> <?= h($error) ?>
    </div>
    <?php endif; ?>
    <form method="post" action="login.php">
      <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
      <div class="field">
        <label for="username">اسم المستخدم</label>
        <div class="input-icon">
          <?= icon('user', 18) ?>
          <input type="text" id="username" name="username" required autofocus autocomplete="username">
        </div>
      </div>
      <div class="field">
        <label for="password">كلمة المرور</label>
        <div class="input-icon">
          <?= icon('key', 18) ?>
          <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
      </div>
      <label class="remember-row">
        <input type="checkbox" name="remember_me" value="1">
        تذكرني للأبد (لا تطلب كلمة المرور مجدداً)
      </label>
      <button class="btn btn-primary btn-block" type="submit"><?= icon('login', 18) ?> دخول</button>
    </form>
  </div>
</body>
</html>
