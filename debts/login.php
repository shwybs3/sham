<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['site_user_id'])) {
    header('Location: ' . ($_GET['redirect'] ?? 'index.php'));
    exit;
}

$googleClientId   = $settings['google_client_id'] ?? '';
$cfEnabled        = ($settings['cf_turnstile_enabled'] ?? '0') === '1' && !empty($settings['cf_turnstile_site_key']);
$rcEnabled        = ($settings['recaptcha_enabled']   ?? '0') === '1' && !empty($settings['recaptcha_site_key']);
$rcVersion        = $settings['recaptcha_version'] ?? 'v2';
$cfSiteKey        = $settings['cf_turnstile_site_key'] ?? '';
$rcSiteKey        = $settings['recaptcha_site_key'] ?? '';

$pageTitle = 'تسجيل الدخول';
require __DIR__ . '/includes/page-header.php';
?>
<main class="page narrow" style="padding-top:40px;">
  <div class="login-card">
    <div class="logo"><?= icon('store', 28) ?></div>
    <h1><?= h($settings['shop_name'] ?? 'دفتر الدكان') ?></h1>
    <p>سجّل دخولك للوصول إلى المجموعة العامة وميزات الموقع</p>

    <?php if ($googleClientId): ?>
    <div id="g_id_onload"
         data-client_id="<?= h($googleClientId) ?>"
         data-callback="handleGoogleLogin"
         data-auto_prompt="false">
    </div>
    <div class="g_id_signin"
         data-type="standard"
         data-shape="pill"
         data-theme="filled_black"
         data-text="signin_with"
         data-size="large"
         data-locale="ar"
         data-width="300">
    </div>
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <?php if ($cfEnabled): ?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <div class="cf-turnstile" data-sitekey="<?= h($cfSiteKey) ?>" data-theme="auto" style="margin:12px auto;"></div>
    <?php elseif ($rcEnabled && $rcVersion === 'v2'): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <div class="g-recaptcha" data-sitekey="<?= h($rcSiteKey) ?>" data-theme="dark" style="margin:12px auto;"></div>
    <?php elseif ($rcEnabled && $rcVersion === 'v3'): ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= h($rcSiteKey) ?>"></script>
    <?php endif; ?>

    <script>
    async function handleGoogleLogin(response) {
      let captchaToken = '';
      <?php if ($cfEnabled): ?>
        captchaToken = document.querySelector('[name="cf-turnstile-response"]')?.value || '';
      <?php elseif ($rcEnabled && $rcVersion === 'v3'): ?>
        captchaToken = await grecaptcha.execute('<?= h($rcSiteKey) ?>', {action:'login'});
      <?php elseif ($rcEnabled && $rcVersion === 'v2'): ?>
        captchaToken = grecaptcha.getResponse();
      <?php endif; ?>

      fetch('auth/google.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          credential: response.credential,
          captcha_token: captchaToken,
          redirect: <?= json_encode($_GET['redirect'] ?? 'community.php') ?>
        })
      }).then(r => r.json()).then(data => {
        if (data.ok) window.location.href = data.redirect;
        else alert(data.error || 'حدث خطأ');
      });
    }
    </script>
    <?php else: ?>
    <div class="notice err"><?= icon('alert', 16) ?> تسجيل Google غير مفعّل. يرجى ضبط المفاتيح من لوحة الإدارة.</div>
    <?php endif; ?>

    <div class="login-divider">أو</div>
    <a href="index.php" class="btn btn-ghost btn-block"><?= icon('home', 16) ?> متابعة كزائر</a>

    <p style="font-size:12px; color:var(--ink-faint); margin-top:20px; line-height:1.6;">
      بتسجيل دخولك توافق على <a href="terms.php">شروط الاستخدام</a>
      و<a href="privacy-policy.php">سياسة الخصوصية</a>.
      نجمع بيانات أساسية من حسابك على Google لإنشاء ملفك الشخصي.
    </p>
  </div>
</main>

<?php require __DIR__ . '/includes/page-footer.php'; ?>
