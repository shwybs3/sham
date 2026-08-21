<?php
// Renders the comment/rating form fragment with a CSRF token fresh for the
// CURRENT visitor's session. app.php's page cache means the surrounding
// page HTML is shared across many visitors — the form itself is intentionally
// NOT baked into that cache and is instead fetched into a placeholder by JS,
// so every visitor gets a token tied to their own real session, not
// whichever session happened to trigger the cached render.
require_once __DIR__ . '/config.php';

// Security: VPN/proxy + WAF checks before serving the form
if (!evil_is_admin_ip()) {
    waf_check($pdo);
    evil_check_ban($pdo);
}

$slug = trim($_GET['slug'] ?? '');
$stmt = $pdo->prepare("SELECT id, slug FROM apps WHERE slug=? AND status='published'");
$stmt->execute([$slug]);
$app = $stmt->fetch();
if (!$app) { http_response_code(404); exit; }
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

// Determine CAPTCHA type for comment form
$captchaType = 'none';
if (trim(get_cfg($pdo, 'turnstile_site_key', '')))    $captchaType = 'turnstile';
elseif (trim(get_cfg($pdo, 'recaptcha_v2_site_key', ''))) $captchaType = 'v2';
elseif (trim(get_cfg($pdo, 'recaptcha_v3_site_key', ''))) $captchaType = 'v3';
?>
<form method="post" action="<?= h(app_url($app['slug'])) ?>#comment-form" id="comment-form" style="display:flex;flex-direction:column;gap:12px;max-width:520px">
  <?= csrf_field() ?>
  <input type="hidden" name="comment_submit" value="1">
  <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px" aria-hidden="true">
  <div class="star-input" dir="ltr">
    <?php for ($s = 5; $s >= 1; $s--): ?>
    <input type="radio" name="rating" id="star-<?= $s ?>" value="<?= $s ?>" <?= $s === 5 ? 'checked' : '' ?>>
    <label for="star-<?= $s ?>">★</label>
    <?php endfor; ?>
  </div>
  <input type="text" name="name" placeholder="اسمك" required style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:10px;padding:11px 14px;color:var(--white);font-size:14px">
  <textarea name="body" placeholder="رأيك في التطبيق..." rows="3" required style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:10px;padding:11px 14px;color:var(--white);font-size:14px;resize:vertical"></textarea>
  <?php if ($captchaType !== 'none'): ?>
  <?= captcha_widget_html($pdo, $captchaType, 'comment') ?>
  <?php endif; ?>
  <button type="submit" class="btn-primary" style="align-self:flex-start">إرسال التقييم</button>
</form>
