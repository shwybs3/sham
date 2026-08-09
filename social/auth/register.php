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
<title>إنشاء حساب — <?= sc_h(SOCIAL_NAME) ?></title>
<link rel="icon" href="/social/assets/favicon.svg" type="image/svg+xml">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0e1117;color:#e6edf3;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#161b22;border:1px solid #30363d;border-radius:14px;padding:36px;width:100%;max-width:440px}
.logo{font-size:1.5rem;font-weight:900;color:#2f81f7;margin-bottom:4px;text-align:center}
h1{font-size:1.15rem;font-weight:700;text-align:center;margin-bottom:4px}
.sub{color:#8b949e;font-size:.85rem;text-align:center;margin-bottom:24px}
.google-btn{display:flex;align-items:center;justify-content:center;gap:10px;background:#fff;color:#1f2328;border-radius:10px;padding:11px 20px;font-weight:600;font-size:.92rem;text-decoration:none;margin-bottom:18px}
.google-btn:hover{opacity:.9}
.google-btn svg{width:20px;height:20px}
.divider{display:flex;align-items:center;gap:10px;color:#8b949e;font-size:.8rem;margin-bottom:18px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#30363d}
.field{margin-bottom:12px}
label{display:block;font-size:.82rem;color:#8b949e;margin-bottom:4px}
.input{width:100%;background:#0e1117;border:1px solid #30363d;border-radius:8px;padding:10px 14px;color:#e6edf3;font-size:.92rem}
.input:focus{outline:none;border-color:#2f81f7}
.btn-primary{width:100%;background:#2f81f7;color:#fff;border:none;border-radius:8px;padding:11px;font-size:.95rem;font-weight:700;cursor:pointer;margin-top:4px}
.btn-primary:hover{opacity:.88}
.err{background:rgba(248,81,73,.1);border:1px solid #f85149;border-radius:8px;padding:10px;color:#f85149;font-size:.85rem;margin-bottom:14px}
.terms{font-size:.78rem;color:#8b949e;margin-top:12px;text-align:center}
.terms a{color:#2f81f7}
</style>
</head>
<body>
<div class="card">
  <div class="logo">🌐 <?= sc_h(SOCIAL_NAME) ?></div>
  <h1>إنشاء حساب جديد</h1>
  <p class="sub">انضم إلى المجتمع وابدأ التواصل</p>

  <?php if ($error): ?>
  <div class="err"><?= sc_h($error === 'username_taken' ? 'اسم المستخدم محجوز، اختر اسماً آخر' : $error) ?></div>
  <?php endif; ?>

  <a href="<?= SOCIAL_URL ?>/auth/google" class="google-btn">
    <svg viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
    التسجيل مع Google
  </a>
  <div class="divider">أو بالبريد الإلكتروني</div>

  <form method="post" action="<?= SOCIAL_URL ?>/auth/register-post">
    <div class="field">
      <label>اسم المستخدم</label>
      <input class="input" type="text" name="username" placeholder="مثال: ahmed_2025" required
             pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="30" autocomplete="username">
      <small style="color:#8b949e;font-size:.75rem">حروف إنجليزية وأرقام و _ فقط</small>
    </div>
    <div class="field">
      <label>الاسم الظاهر</label>
      <input class="input" type="text" name="display_name" placeholder="اسمك الكامل" required maxlength="60">
    </div>
    <div class="field">
      <label>البريد الإلكتروني</label>
      <input class="input" type="email" name="email" required autocomplete="email">
    </div>
    <div class="field">
      <label>كلمة المرور</label>
      <input class="input" type="password" name="password" required minlength="8" autocomplete="new-password">
    </div>
    <div class="field">
      <label>نبذة عنك (اختياري)</label>
      <textarea class="input" name="bio" rows="2" maxlength="200" placeholder="اكتب شيئاً عن نفسك..."></textarea>
    </div>
    <button class="btn-primary" type="submit">إنشاء الحساب</button>
  </form>
  <p class="terms">بإنشاء حساب، أنت توافق على <a href="<?= SOCIAL_URL ?>/terms">شروط الاستخدام</a> وسياسة الخصوصية</p>
  <p style="text-align:center;margin-top:14px;color:#8b949e;font-size:.85rem">
    لديك حساب بالفعل؟ <a href="<?= SOCIAL_URL ?>/auth/login" style="color:#2f81f7">تسجيل الدخول</a>
  </p>
</div>
</body>
</html>
