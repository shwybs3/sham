<?php
require __DIR__ . '/config.php';
$do = $_GET['do'] ?? 'login';
$me = current_user();
$next = $_GET['next'] ?? ($_POST['next'] ?? url(''));
if (strpos($next, '://') !== false && strpos($next, site_origin()) !== 0) $next = url('');

if ($do === 'logout') { auth_logout(); header('Location: ' . url('')); exit; }
if ($me && in_array($do, ['login', 'register'], true)) { header('Location: ' . url('')); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) $err = 'انتهت صلاحية الجلسة، أعد المحاولة.';
    elseif ($do === 'google') {
        if (!rate_ok('goog:' . ($_SERVER['REMOTE_ADDR'] ?? '0'), 20, 600)) $err = 'محاولات كثيرة، انتظر قليلاً.';
        else {
            $r = auth_google($_POST['credential'] ?? '');
            if ($r['ok']) { header('Location: ' . $next); exit; }
            $err = $r['error']; $do = 'login';
        }
    } elseif ($do === 'register') {
        if (!rate_ok('reg:' . ($_SERVER['REMOTE_ADDR'] ?? '0'), 10, 3600)) $err = 'محاولات كثيرة، انتظر قليلاً.';
        else {
            $r = auth_register($_POST['email'] ?? '', $_POST['password'] ?? '', $_POST['name'] ?? '');
            if ($r['ok']) { header('Location: ' . $next); exit; }
            $err = $r['error'];
        }
    } elseif ($do === 'login') {
        if (!rate_ok('login:' . ($_SERVER['REMOTE_ADDR'] ?? '0'), 15, 900)) $err = 'محاولات دخول كثيرة، انتظر قليلاً.';
        else {
            $r = auth_login($_POST['email'] ?? '', $_POST['password'] ?? '');
            if ($r['ok']) { header('Location: ' . $next); exit; }
            $err = $r['error'];
        }
    }
}

$isReg = $do === 'register';
$gClient = setting('google_client_id');
layout_top(['title' => ($isReg ? 'إنشاء حساب' : 'تسجيل الدخول') . ' | ' . setting('site_name', 'YASSOTA'), 'noindex' => true, 'active' => 'login']);
?>
<div class="center">
  <div class="auth-card">
    <div style="text-align:center;margin-bottom:22px">
      <span class="logo" style="width:52px;height:52px;font-size:28px;margin:0 auto 12px">Y</span>
      <h1 style="font-family:var(--fd);font-size:23px;margin:0"><?= $isReg ? 'أنشئ حسابك في يسوتا' : 'أهلاً بعودتك' ?></h1>
      <p style="color:var(--muted);font-size:14px;margin:6px 0 0"><?= $isReg ? 'انضم لمجتمع يسوتا وشارك محتواك' : 'سجّل الدخول للمتابعة' ?></p>
    </div>
    <?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
    <?php if ($gClient): ?>
      <div id="g_id_onload" data-client_id="<?= e($gClient) ?>" data-callback="onGoogle" data-auto_prompt="false"></div>
      <div class="g_id_signin" data-type="standard" data-shape="pill" data-theme="outline" data-text="<?= $isReg ? 'signup_with' : 'signin_with' ?>" data-size="large" data-logo_alignment="center" data-width="360" style="display:flex;justify-content:center"></div>
      <form id="gform" method="post" action="<?= e(url('auth/google')) ?>" style="display:none">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="credential"><input type="hidden" name="next" value="<?= e($next) ?>">
      </form>
      <script src="https://accounts.google.com/gsi/client" async></script>
      <script>function onGoogle(r){var f=document.getElementById('gform');f.credential.value=r.credential;f.submit();}</script>
      <div class="divider">أو بالبريد الإلكتروني</div>
    <?php else: ?>
      <div class="alert" style="background:var(--surface-2);color:var(--muted)"><?= icon('google',16) ?> لتفعيل الدخول بجوجل، أضف <b>Google Client ID</b> من لوحة الإدارة.</div>
    <?php endif; ?>
    <form method="post" action="<?= e(url($isReg ? 'register' : 'login')) ?>">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <?php if ($isReg): ?><div class="field"><label>الاسم</label><input class="input" name="name" placeholder="اسمك الظاهر"></div><?php endif; ?>
      <div class="field"><label>البريد الإلكتروني</label><input class="input" type="email" name="email" required placeholder="you@example.com" value="<?= e($_POST['email'] ?? '') ?>"></div>
      <div class="field"><label>كلمة المرور</label><input class="input" type="password" name="password" required minlength="6" placeholder="••••••••"></div>
      <button class="btn btn-primary btn-lg btn-block" type="submit"><?= $isReg ? 'إنشاء الحساب' : 'دخول' ?></button>
    </form>
    <p style="text-align:center;color:var(--muted);font-size:14px;margin-top:18px">
      <?php if ($isReg): ?>لديك حساب؟ <a href="<?= e(url('login')) ?>" style="color:var(--brand-ink);font-weight:600">سجّل الدخول</a>
      <?php else: ?>لا تملك حساباً؟ <a href="<?= e(url('register')) ?>" style="color:var(--brand-ink);font-weight:600">أنشئ حساباً</a><?php endif; ?>
    </p>
  </div>
</div>
<?php layout_bottom(); ?>
