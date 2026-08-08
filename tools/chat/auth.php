<?php
/**
 * Yasmin chat — sign in / create account / sign out / Google OAuth callback.
 *
 * Every branch that changes state (sign in, register, sign out) is POST-only
 * and CSRF-checked. Google's callback is a GET by protocol, and is guarded by
 * the one-time `state` value instead.
 */
require_once dirname(__DIR__) . '/_base.php';

$mainCfg = dirname(dirname(__DIR__)) . '/config.php';
if (!file_exists($mainCfg)) { http_response_code(503); echo 'Service unavailable'; exit; }
require_once $mainCfg;
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_style.php';

$action  = $_GET['a'] ?? 'login';
$chatUrl = ys_chat_url();
$isPost  = ($_SERVER['REQUEST_METHOD'] === 'POST');
$error   = '';
$notice  = '';

/* ── Already signed in? Nothing to do here. ─────────────────────── */
if (ys_current_user($pdo) && !in_array($action, ['logout'], true)) {
    header('Location: ' . $chatUrl);
    exit;
}

/* ── Sign out ───────────────────────────────────────────────────── */
if ($action === 'logout') {
    if ($isPost && csrf_check()) {
        ys_logout();
        session_regenerate_id(true);
    }
    header('Location: ' . $chatUrl);
    exit;
}

/* ── Start the Google flow ──────────────────────────────────────── */
if ($action === 'google') {
    if (!ys_google_enabled($pdo)) {
        header('Location: ' . $chatUrl . 'auth.php?a=login&e=nogoogle');
        exit;
    }
    header('Location: ' . ys_google_auth_url($pdo));
    exit;
}

/* ── Google callback ────────────────────────────────────────────── */
if ($action === 'google_cb') {
    $state    = (string)($_GET['state'] ?? '');
    $code     = (string)($_GET['code'] ?? '');
    $expected = (string)($_SESSION['ys_oauth_state'] ?? '');
    unset($_SESSION['ys_oauth_state']);

    if ($code === '' || $state === '' || $expected === '' || !hash_equals($expected, $state)) {
        header('Location: ' . $chatUrl . 'auth.php?a=login&e=oauth');
        exit;
    }

    $profile = ys_google_profile($pdo, $code);
    if (!$profile || empty($profile['email_verified'])) {
        header('Location: ' . $chatUrl . 'auth.php?a=login&e=oauth');
        exit;
    }

    $user = ys_upsert_google($pdo, $profile);
    if (!$user) {
        header('Location: ' . $chatUrl . 'auth.php?a=login&e=oauth');
        exit;
    }
    if ($user['status'] !== 'active') {
        header('Location: ' . $chatUrl . 'auth.php?a=login&e=banned');
        exit;
    }

    ys_login($pdo, $user);
    header('Location: ' . $chatUrl);
    exit;
}

/* ── Password sign in ───────────────────────────────────────────── */
if ($action === 'login' && $isPost) {
    if (!csrf_check()) {
        $error = 'انتهت صلاحية الجلسة. حاول مجدداً.';
    } elseif (ys_login_locked($pdo)) {
        $error = 'محاولات كثيرة فاشلة. انتظر ' . YS_LOGIN_WINDOW_MIN . ' دقيقة ثم حاول مجدداً.';
    } else {
        $email = (string)($_POST['email'] ?? '');
        $pass  = (string)($_POST['password'] ?? '');
        $user  = ys_find_by_email($pdo, $email);

        if (!$user || !$user['pass_hash'] || !password_verify($pass, $user['pass_hash'])) {
            ys_record_login_fail($pdo, $email);
            // One message for both cases, so the form cannot be used to learn
            // which addresses have accounts.
            $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
        } elseif ($user['status'] !== 'active') {
            $error = 'هذا الحساب موقوف. تواصل معنا إذا كنت تعتقد أن هذا خطأ.';
        } else {
            ys_login($pdo, $user);
            header('Location: ' . $chatUrl);
            exit;
        }
    }
}

/* ── Registration ───────────────────────────────────────────────── */
if ($action === 'register' && $isPost) {
    if (!csrf_check()) {
        $error = 'انتهت صلاحية الجلسة. حاول مجدداً.';
    } else {
        [$user, $err] = ys_register(
            $pdo,
            (string)($_POST['email'] ?? ''),
            (string)($_POST['name'] ?? ''),
            (string)($_POST['password'] ?? '')
        );
        if ($err !== '' || !$user) {
            $error = $err ?: 'تعذّر إنشاء الحساب، حاول مجدداً.';
        } else {
            ys_login($pdo, $user);
            header('Location: ' . $chatUrl);
            exit;
        }
    }
}

/* ── Messages carried back on redirect ──────────────────────────── */
switch ($_GET['e'] ?? '') {
    case 'oauth':    $error = 'تعذّر إكمال تسجيل الدخول عبر Google. حاول مجدداً.'; break;
    case 'nogoogle': $error = 'تسجيل الدخول عبر Google غير مفعّل حالياً.'; break;
    case 'banned':   $error = 'هذا الحساب موقوف.'; break;
}

/* ── Render ─────────────────────────────────────────────────────── */
$isRegister = ($action === 'register');
$title = $isRegister
    ? 'إنشاء حساب — ياسمين | مساعدة الذكاء الاصطناعي السورية'
    : 'تسجيل الدخول — ياسمين | مساعدة الذكاء الاصطناعي السورية';
$desc = $isRegister
    ? 'أنشئ حساباً مجانياً في ياسمين لحفظ محادثاتك والوصول إليها من أي جهاز.'
    : 'سجّل الدخول إلى ياسمين للوصول إلى محادثاتك المحفوظة من أي جهاز.';

// tool_head() always emits `robots: index,follow`; sign-in screens should not
// be indexed, and a header overrides the meta tag without touching the shared
// layout every other tool depends on.
header('X-Robots-Tag: noindex, follow');

tool_head($title, $desc, '', '#7c5cff');
tool_header('ياسمين');
ys_styles();

$googleOn = ys_google_enabled($pdo);
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<main class="ys-wrap">
<div class="ys-shell">
  <div class="ys-auth">
    <div class="ys-card">
      <div class="ys-welcome-bloom" aria-hidden="true">🌸</div>

      <h1><?= $isRegister ? 'أنشئ حسابك في ياسمين' : 'أهلاً بعودتك' ?></h1>
      <p class="sub">
        <?= $isRegister
            ? 'احفظ محادثاتك ورجّع لها من أي جهاز — مجاناً بالكامل.'
            : 'سجّل دخولك للوصول إلى كل محادثاتك المحفوظة.' ?>
      </p>

      <?php if ($error): ?>
        <div class="ys-msg bad" role="alert">⚠️ <?= $h($error) ?></div>
      <?php elseif ($notice): ?>
        <div class="ys-msg ok"><?= $h($notice) ?></div>
      <?php endif; ?>

      <?php if ($googleOn): ?>
      <a href="?a=google" class="ys-google" style="text-decoration:none">
        <svg viewBox="0 0 48 48" aria-hidden="true">
          <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
          <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
          <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24s.92 7.54 2.56 10.78l7.97-6.19z"/>
          <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
        </svg>
        <?= $isRegister ? 'المتابعة باستخدام Google' : 'تسجيل الدخول باستخدام Google' ?>
      </a>
      <div class="ys-or">أو بالبريد الإلكتروني</div>
      <?php endif; ?>

      <form method="post" action="?a=<?= $isRegister ? 'register' : 'login' ?>" autocomplete="on">
        <?= csrf_field() ?>

        <?php if ($isRegister): ?>
        <div class="ys-fld">
          <label class="ys-lbl" for="ys-name">الاسم</label>
          <input class="ys-in" id="ys-name" name="name" type="text" required maxlength="60"
                 autocomplete="name" placeholder="شو منناديك؟"
                 value="<?= $h($_POST['name'] ?? '') ?>">
        </div>
        <?php endif; ?>

        <div class="ys-fld">
          <label class="ys-lbl" for="ys-email">البريد الإلكتروني</label>
          <input class="ys-in" id="ys-email" name="email" type="email" required dir="ltr"
                 autocomplete="email" placeholder="you@example.com"
                 value="<?= $h($_POST['email'] ?? '') ?>">
        </div>

        <div class="ys-fld">
          <label class="ys-lbl" for="ys-pass">
            كلمة المرور<?= $isRegister ? ' — ' . YS_PASS_MIN . ' أحرف على الأقل' : '' ?>
          </label>
          <?php /* The field is dir=ltr so the typed password is not reordered,
                   which means an Arabic placeholder would render bidi-shuffled
                   inside it — hence the requirement lives in the label. */ ?>
          <input class="ys-in" id="ys-pass" name="password" type="password" required dir="ltr"
                 minlength="<?= YS_PASS_MIN ?>"
                 autocomplete="<?= $isRegister ? 'new-password' : 'current-password' ?>"
                 placeholder="••••••••">
        </div>

        <button type="submit" class="ys-btn">
          <?= $isRegister ? 'إنشاء الحساب' : 'تسجيل الدخول' ?>
        </button>
      </form>

      <p class="ys-swap">
        <?php if ($isRegister): ?>
          عندك حساب؟ <a href="?a=login">سجّل الدخول</a>
        <?php else: ?>
          ما عندك حساب؟ <a href="?a=register">أنشئ واحد مجاناً</a>
        <?php endif; ?>
      </p>

      <p class="ys-skip">
        <a href="<?= $h($chatUrl) ?>">المتابعة بدون حساب ←</a>
      </p>
    </div>
  </div>
</div>
</main>
<?php tool_footer(); ?>
