<?php
require_once __DIR__.'/config.php';

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name    = clean($_POST['name'] ?? '');
    $email   = filter_var(trim($_POST['email']??''), FILTER_VALIDATE_EMAIL);
    $subject = clean($_POST['subject'] ?? '');
    $msg     = clean($_POST['message'] ?? '');
    $honeypot = trim($_POST['website'] ?? ''); // حقل خفي — إن مُلئ فهو بوت

    if ($honeypot !== '') {
        $success = true; // نتظاهر بالنجاح دون تخزين أي شيء
    } elseif (!csrfCheck()) {
        $error = t('انتهت صلاحية الجلسة، أعد تحميل الصفحة','Session expired, reload the page');
    } elseif (rateLimited('contact')) {
        $error = t('عدد رسائل كبير خلال فترة قصيرة، يرجى المحاولة لاحقاً','Too many messages in a short time, please try again later');
    } elseif (!verifyCaptcha()) {
        $error = t('يرجى إتمام التحقق الأمني (CAPTCHA)','Please complete the security check (CAPTCHA)');
    } elseif (!$name || !$email || !$msg) {
        $error = t('يرجى تعبئة جميع الحقول المطلوبة','Please fill in all required fields');
    } else {
        db()->prepare("INSERT INTO contact_messages(name,email,subject,message) VALUES(?,?,?,?)")
            ->execute([$name,$email,$subject,$msg]);
        $success = true;
    }
}

$pageTitle = t('تواصل معنا','Contact Us');
require_once __DIR__.'/header.php';
?>
<main class="contact-page">
  <div class="section-header">
    <h2><?= t('تواصل <span>معنا</span>','Contact <span>Us</span>') ?></h2>
    <div class="section-divider"></div>
    <p><?= t('لديك سؤال قبل الشراء؟ راسلنا وسنرد خلال ساعات','Have a question before buying? Message us and we\'ll reply within hours') ?></p>
  </div>

  <div class="contact-card">
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= t('تم إرسال رسالتك بنجاح، سنتواصل معك قريباً.','Your message has been sent, we will reach out soon.') ?></div>
    <?php else: ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> <?= $error ?></div>
    <?php endif; ?>
    <form method="POST" class="admin-form">
      <?php csrfField(); ?>
      <div style="position:absolute;left:-9999px;" aria-hidden="true">
        <label>Website</label>
        <input type="text" name="website" tabindex="-1" autocomplete="off">
      </div>
      <div class="form-group">
        <label><?= t('الاسم *','Name *') ?></label>
        <input type="text" name="name" required value="<?= clean($_POST['name']??'') ?>">
      </div>
      <div class="form-group">
        <label><?= t('البريد الإلكتروني *','Email *') ?></label>
        <input type="email" name="email" required value="<?= clean($_POST['email']??'') ?>">
      </div>
      <div class="form-group">
        <label><?= t('الموضوع','Subject') ?></label>
        <input type="text" name="subject" value="<?= clean($_POST['subject']??'') ?>">
      </div>
      <div class="form-group">
        <label><?= t('الرسالة *','Message *') ?></label>
        <textarea name="message" rows="5" required maxlength="3000"><?= clean($_POST['message']??'') ?></textarea>
      </div>
      <?php renderCaptcha(); ?>
      <button type="submit" class="btn btn-primary w-full" style="justify-content:center;">
        <i class="fa-solid fa-paper-plane"></i> <?= t('إرسال','Send') ?>
      </button>
    </form>
    <?php endif; ?>

    <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border);text-align:center;">
      <a href="<?= clean(setting('telegram','#')) ?>" target="_blank" rel="noopener" class="btn btn-outline">
        <i class="fa-brands fa-telegram"></i> <?= t('أو تواصل معنا عبر تيليغرام','Or reach us on Telegram') ?>
      </a>
    </div>
  </div>
</main>
<?php require_once __DIR__.'/footer.php'; ?>
