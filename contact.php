<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$contactEmail = get_cfg($pdo, 'contact_email') ?: 'contact@' . parse_url(SITE_URL, PHP_URL_HOST);
$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['website'])) {
        // Honeypot field — bots fill every input, real users never see it (hidden via CSS).
        $sent = true; // pretend success, don't store, don't tip off the bot
    } elseif (!csrf_check()) {
        $error = 'جلسة غير صالحة، أعد تحميل الصفحة وحاول مجدداً.';
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$message) {
            $error = 'يرجى تعبئة الاسم والبريد الإلكتروني الصحيح والرسالة.';
        } else {
            $pdo->prepare("INSERT INTO contact_messages (name,email,subject,message) VALUES (?,?,?,?)")
                ->execute([$name, $email, $subject, $message]);
            notify_admin($pdo, "رسالة تواصل جديدة: {$subject}", "من: {$name} <{$email}>\n\n{$message}");
            $sent = true;
        }
    }
}

$seoTitle = 'اتصل بنا — yassota';
$metaDesc = 'تواصل مع فريق yassota لأي استفسار أو اقتراح أو بلاغ.';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <?= nav_guard_script() ?>
  <meta charset="UTF-8">
  <?= head_extras($pdo) ?>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($seoTitle) ?></title>
  <meta name="description" content="<?= h($metaDesc) ?>">
  <link rel="canonical" href="<?= h(url('contact.php')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/main.css')) ?>">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5506877998492189"
     crossorigin="anonymous"></script>
</head>
<body>

<?php render_site_header(); ?>

<div class="page-wrap">
<?php render_site_sidebar($pdo); ?>

<main class="main-content">
  <nav style="font-size:12px;color:var(--muted);margin-bottom:16px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <a href="/" style="color:var(--cyan)">الرئيسية</a><span>/</span><span>اتصل بنا</span>
  </nav>

  <div class="section-head reveal"><span class="section-title">اتصل بنا</span></div>

  <div class="section-box reveal" style="max-width:640px">
    <p style="color:var(--muted);font-size:14px;line-height:1.8;margin-bottom:20px">
      لأي استفسار، اقتراح تطبيق، بلاغ عن رابط لا يعمل، أو طلب إزالة محتوى (DMCA)، راسلنا مباشرة على
      <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--cyan)"><?= h($contactEmail) ?></a>
      أو عبر النموذج التالي:
    </p>

    <?php if ($sent): ?>
      <div class="alert alert-success" style="background:rgba(0,230,118,.1);border:1px solid rgba(0,230,118,.25);color:var(--success);padding:14px 18px;border-radius:10px;margin-bottom:16px">
        ✅ تم إرسال رسالتك بنجاح، سنتواصل معك قريباً إن لزم الأمر.
      </div>
    <?php else: ?>
      <?php if ($error): ?>
        <div class="alert alert-error" style="background:rgba(255,68,102,.1);border:1px solid rgba(255,68,102,.25);color:var(--danger);padding:14px 18px;border-radius:10px;margin-bottom:16px"><?= h($error) ?></div>
      <?php endif; ?>
      <form method="post" action="contact.php" style="display:flex;flex-direction:column;gap:14px">
        <?= csrf_field() ?>
        <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px" aria-hidden="true">
        <div style="display:flex;flex-direction:column;gap:6px">
          <label style="font-size:12px;color:var(--muted)">الاسم</label>
          <input type="text" name="name" required style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:10px;padding:11px 14px;color:var(--white);font-size:14px">
        </div>
        <div style="display:flex;flex-direction:column;gap:6px">
          <label style="font-size:12px;color:var(--muted)">البريد الإلكتروني</label>
          <input type="email" name="email" required style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:10px;padding:11px 14px;color:var(--white);font-size:14px">
        </div>
        <div style="display:flex;flex-direction:column;gap:6px">
          <label style="font-size:12px;color:var(--muted)">الموضوع (اختياري)</label>
          <input type="text" name="subject" style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:10px;padding:11px 14px;color:var(--white);font-size:14px">
        </div>
        <div style="display:flex;flex-direction:column;gap:6px">
          <label style="font-size:12px;color:var(--muted)">الرسالة</label>
          <textarea name="message" rows="6" required style="background:var(--navy-600);border:1px solid var(--border-c);border-radius:10px;padding:11px 14px;color:var(--white);font-size:14px;resize:vertical"></textarea>
        </div>
        <button type="submit" class="btn-primary" style="align-self:flex-start">إرسال</button>
      </form>
    <?php endif; ?>
  </div>
</main>
</div>

<?php render_site_footer(); ?>
<script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
