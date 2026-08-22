<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$sent = false; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Your session expired — please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please fill in your name, a valid email, and a message.';
        } else {
            $pdo->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?,?,?)")->execute([$name, $email, $message]);

            $contactEmail = trim(setting('contact_email', 'contact@yassota.com'));
            if ($contactEmail !== '' && function_exists('mail')) {
                $subject = 'New contact message on ' . setting('site_name');
                $body = "Name: $name\nEmail: $email\n\nMessage:\n$message";
                @mail($contactEmail, $subject, $body, 'From: no-reply@' . parse_url(SITE_URL, PHP_URL_HOST) . "\r\nReply-To: $email");
            }

            $sent = true;
        }
    }
}
?><!doctype html><html lang="en"><head>
<?php seo_head(['title' => 'Contact Us | ' . setting('site_name'), 'description' => 'Get in touch with the ' . setting('site_name') . ' team.', 'canonical' => site_url('contact.php')]); ?>
</head><body>
<?php site_header(); ?>
<div class="page-hero container">
  <span class="eyebrow"><i class="fa-solid fa-envelope"></i> Contact</span>
  <h1>Get in touch</h1>
  <p class="lead">Questions, feedback, corrections, or a tool idea — we'd love to hear it.</p>
</div>
<div class="container" style="max-width:560px;padding-bottom:60px">
  <?php $contactEmail = trim(setting('contact_email', 'contact@yassota.com')); if ($contactEmail !== ''): ?>
  <a href="mailto:<?= e($contactEmail) ?>" class="tool-shell" style="display:flex;align-items:center;gap:14px;margin-bottom:22px;text-decoration:none">
    <span class="icon-badge" style="background:var(--grad-brand);width:46px;height:46px;font-size:19px"><i class="fa-solid fa-envelope"></i></span>
    <div><div style="font-size:12px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em">Email us directly</div>
    <div style="font-size:16px;font-weight:800;color:var(--ink)"><?= e($contactEmail) ?></div></div>
  </a>
  <?php endif; ?>
  <?php if ($sent): ?>
    <div class="empty-state" style="background:#fff;border:1px solid var(--line);border-radius:16px">
      <i class="fa-solid fa-circle-check" style="font-size:32px;color:var(--accent-green)"></i>
      <p>Thanks — your message has been received.</p>
    </div>
  <?php else: ?>
    <?php if ($error): ?><div class="flash err" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 14px;border-radius:10px;margin-bottom:16px"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="tool-shell" style="padding:24px">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <label style="font-weight:700;font-size:13px;display:block;margin-bottom:6px">Name</label>
      <input type="text" name="name" required style="margin-bottom:14px">
      <label style="font-weight:700;font-size:13px;display:block;margin-bottom:6px">Email</label>
      <input type="text" name="email" required style="margin-bottom:14px">
      <label style="font-weight:700;font-size:13px;display:block;margin-bottom:6px">Message</label>
      <textarea name="message" required style="margin-bottom:14px"></textarea>
      <button class="btn-run" type="submit" style="width:100%">Send message</button>
    </form>
  <?php endif; ?>
</div>
<?php site_footer(); ?>
</body></html>
