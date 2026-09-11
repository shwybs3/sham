<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$sent = false; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'انتهت صلاحية الجلسة — يرجى المحاولة مرة أخرى.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'يرجى تعبئة الاسم، بريد إلكتروني صحيح، والرسالة.';
        } else {
            $pdo->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?,?,?)")->execute([$name, $email, $message]);

            $contactEmail = trim(setting('contact_email', 'contact@yassota.com'));
            if ($contactEmail !== '' && function_exists('mail')) {
                $subject = 'رسالة تواصل جديدة على ' . setting('site_name');
                $body = "الاسم: $name\nالبريد: $email\n\nالرسالة:\n$message";
                @mail($contactEmail, $subject, $body, 'From: no-reply@' . parse_url(SITE_URL, PHP_URL_HOST) . "\r\nReply-To: $email");
            }

            $sent = true;
        }
    }
}
?><!doctype html><html lang="ar" dir="rtl"><head>
<?php seo_head(['title' => 'تواصل معنا | ' . setting('site_name', 'Yassota'), 'description' => 'تواصل مع فريق ' . setting('site_name', 'Yassota') . '.', 'canonical' => site_url('contact.php')]); ?>
</head><body>
<?php site_header(); ?>
<div class="page-hero container">
  <span class="eyebrow"><i class="fa-solid fa-envelope"></i> تواصل معنا</span>
  <h1>تواصل معنا</h1>
  <p class="lead">أسئلة عن باقة، استفسار عن الدفع، أو أي ملاحظة — يسعدنا سماعك.</p>
</div>
<div class="container" style="max-width:560px;padding-bottom:60px">
  <?php $contactEmail = trim(setting('contact_email', 'contact@yassota.com')); if ($contactEmail !== ''): ?>
  <a href="mailto:<?= e($contactEmail) ?>" class="tool-shell" style="display:flex;align-items:center;gap:14px;margin-bottom:22px;text-decoration:none">
    <span class="icon-badge" style="background:var(--grad-brand);width:46px;height:46px;font-size:19px"><i class="fa-solid fa-envelope"></i></span>
    <div><div style="font-size:12px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.03em">راسلنا مباشرة</div>
    <div style="font-size:16px;font-weight:800;color:#fff"><?= e($contactEmail) ?></div></div>
  </a>
  <?php endif; ?>
  <?php $tg = trim(setting('support_telegram')); if ($tg !== ''): ?>
  <a href="<?= e('https://t.me/' . ltrim($tg, '@')) ?>" target="_blank" rel="noopener" class="tool-shell" style="display:flex;align-items:center;gap:14px;margin-bottom:22px;text-decoration:none">
    <span class="icon-badge" style="background:var(--grad-cool);width:46px;height:46px;font-size:19px"><i class="fa-brands fa-telegram"></i></span>
    <div><div style="font-size:12px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.03em">تيليجرام</div>
    <div style="font-size:16px;font-weight:800;color:#fff"><?= e($tg) ?></div></div>
  </a>
  <?php endif; ?>
  <?php if ($sent): ?>
    <div class="empty-state" style="background:var(--panel);border:1px solid var(--line);border-radius:16px">
      <i class="fa-solid fa-circle-check" style="font-size:32px;color:var(--accent-green)"></i>
      <p>تم استلام رسالتك، سنرد عليك قريباً.</p>
    </div>
  <?php else: ?>
    <?php if ($error): ?><div class="flash err" style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.35);color:#fca5a5;padding:12px 14px;border-radius:10px;margin-bottom:16px"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="tool-shell" style="padding:24px">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <label style="font-weight:700;font-size:13px;display:block;margin-bottom:6px">الاسم</label>
      <input type="text" name="name" required style="margin-bottom:14px">
      <label style="font-weight:700;font-size:13px;display:block;margin-bottom:6px">البريد الإلكتروني</label>
      <input type="text" name="email" required style="margin-bottom:14px">
      <label style="font-weight:700;font-size:13px;display:block;margin-bottom:6px">الرسالة</label>
      <textarea name="message" required style="margin-bottom:14px"></textarea>
      <button class="btn-run" type="submit" style="width:100%">إرسال الرسالة</button>
    </form>
  <?php endif; ?>
</div>
<?php site_footer(); ?>
</body></html>
