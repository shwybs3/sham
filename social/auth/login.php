<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = $social_pdo;
if (sc_user($pdo)) { header('Location: ' . SOCIAL_URL); exit; }
$error = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>تسجيل الدخول — <?= sc_h(SOCIAL_NAME) ?></title>
<link rel="icon" href="/social/assets/favicon.svg" type="image/svg+xml">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0e1117;color:#e6edf3;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#161b22;border:1px solid #30363d;border-radius:14px;padding:36px;width:100%;max-width:420px;text-align:center}
.logo{font-size:2rem;font-weight:900;margin-bottom:6px;color:#2f81f7}
.logo .dot{display:inline-block;width:10px;height:10px;border-radius:50%;background:#2f81f7;margin-left:4px;vertical-align:middle}
h1{font-size:1.2rem;font-weight:700;margin-bottom:6px}
.sub{color:#8b949e;font-size:.88rem;margin-bottom:28px}
.google-btn{display:flex;align-items:center;justify-content:center;gap:10px;background:#fff;color:#1f2328;border-radius:10px;padding:12px 20px;font-weight:600;font-size:.95rem;text-decoration:none;transition:opacity .15s;margin-bottom:20px}
.google-btn:hover{opacity:.9}
.google-btn svg{width:20px;height:20px}
.divider{display:flex;align-items:center;gap:10px;color:#8b949e;font-size:.8rem;margin-bottom:20px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#30363d}
.input{width:100%;background:#0e1117;border:1px solid #30363d;border-radius:8px;padding:10px 14px;color:#e6edf3;font-size:.92rem;margin-bottom:10px}
.input:focus{outline:none;border-color:#2f81f7}
.btn-primary{width:100%;background:#2f81f7;color:#fff;border:none;border-radius:8px;padding:11px;font-size:.95rem;font-weight:700;cursor:pointer}
.btn-primary:hover{opacity:.88}
.err{background:rgba(248,81,73,.1);border:1px solid #f85149;border-radius:8px;padding:10px;color:#f85149;font-size:.85rem;margin-bottom:14px}
.link{color:#2f81f7;font-size:.85rem}
</style>
</head>
<body>
<div class="card">
  <div class="logo"><span class="dot"></span><?= sc_h(SOCIAL_NAME) ?></div>
  <h1>مرحباً بعودتك</h1>
  <p class="sub">سجّل دخولك للوصول إلى حسابك</p>

  <?php if ($error): ?>
  <div class="err"><?= sc_h(match($error) {
    'oauth_fail'    => 'فشل تسجيل الدخول عبر جوجل، حاول مجدداً',
    'email_exists'  => 'البريد الإلكتروني مستخدم بالفعل',
    'invalid_creds' => 'البريد أو كلمة المرور غير صحيحة',
    default         => $error
  }) ?></div>
  <?php endif; ?>

  <!-- Google OAuth -->
  <a href="<?= SOCIAL_URL ?>/auth/google" class="google-btn">
    <svg viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
    المتابعة مع Google
  </a>

  <div class="divider">أو بالبريد الإلكتروني</div>

  <form method="post" action="<?= SOCIAL_URL ?>/auth/login-post">
    <input class="input" type="email" name="email" placeholder="البريد الإلكتروني" required autocomplete="email">
    <input class="input" type="password" name="password" placeholder="كلمة المرور" required autocomplete="current-password">
    <button class="btn-primary" type="submit">دخول</button>
  </form>
  <p style="margin-top:16px;color:#8b949e;font-size:.85rem">
    ليس لديك حساب؟ <a href="<?= SOCIAL_URL ?>/auth/register" class="link">إنشاء حساب</a>
  </p>
</div>
</body>
</html>
