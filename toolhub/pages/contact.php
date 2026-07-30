<?php
$sent  = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name    = trim($_POST['name']    ?? '');
  $email   = trim($_POST['email']   ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $message = trim($_POST['message'] ?? '');
  $honey   = $_POST['website'] ?? ''; // honeypot

  if ($honey) {
    // bot detected — silently succeed
    $sent = true;
  } elseif (!$name || !$email || !$message) {
    $error = 'يرجى ملء جميع الحقول المطلوبة.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'عنوان البريد الإلكتروني غير صحيح.';
  } else {
    // Store in DB if available
    $pdo = db();
    if ($pdo) {
      try {
        $pdo->prepare("CREATE TABLE IF NOT EXISTS contact_messages (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), email VARCHAR(255), subject VARCHAR(500), message TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)")->execute();
        $pdo->prepare("INSERT INTO contact_messages (name,email,subject,message) VALUES (?,?,?,?)")->execute([$name,$email,$subject,$message]);
      } catch (Exception $e) {}
    }
    $sent = true;
  }
}
?>

<section class="page-hero small">
  <div class="container">
    <nav class="breadcrumb"><a href="/">الرئيسية</a> <span>›</span> <span>اتصل بنا</span></nav>
    <h1>📬 اتصل بنا</h1>
    <p>اقتراحاتك ومعلوماتك تهمنا — راسلنا وسنرد في أقرب وقت</p>
  </div>
</section>

<div class="container page-body" style="max-width:700px">

  <?php if ($sent): ?>
  <div class="card" style="text-align:center;padding:3rem 2rem">
    <div style="font-size:4rem;margin-bottom:1rem">✅</div>
    <h2>تم إرسال رسالتك بنجاح!</h2>
    <p style="color:var(--c-text2);margin:.75rem 0 1.5rem">شكراً لتواصلك معنا. سنرد على استفسارك قريباً.</p>
    <a href="/" class="btn-primary">العودة للرئيسية</a>
  </div>

  <?php else: ?>
  <section class="card" aria-labelledby="h-contact">
    <h2 id="h-contact">📨 نموذج التواصل</h2>
    <?php if ($error): ?>
    <div class="alert-info" style="background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.25);color:var(--c-red);margin-bottom:1rem">⚠️ <?= h($error) ?></div>
    <?php endif; ?>
    <form method="post" action="/contact" novalidate>
      <!-- Honeypot -->
      <div style="position:absolute;left:-9999px;opacity:0" aria-hidden="true">
        <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
      </div>
      <div class="form-grid" style="grid-template-columns:1fr 1fr">
        <label>الاسم <span style="color:var(--c-red)">*</span>
          <input type="text" name="name" value="<?= h($_POST['name'] ?? '') ?>" required placeholder="اسمك الكريم">
        </label>
        <label>البريد الإلكتروني <span style="color:var(--c-red)">*</span>
          <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required placeholder="example@domain.com">
        </label>
      </div>
      <label style="margin-top:.75rem;display:block">الموضوع
        <select name="subject">
          <option value="اقتراح">اقتراح أو ميزة جديدة</option>
          <option value="خطأ">الإبلاغ عن خطأ</option>
          <option value="شراكة">شراكة أو إعلان</option>
          <option value="أخرى">أخرى</option>
        </select>
      </label>
      <label style="margin-top:.75rem;display:block">الرسالة <span style="color:var(--c-red)">*</span>
        <textarea name="message" rows="6" required placeholder="اكتب رسالتك هنا..."><?= h($_POST['message'] ?? '') ?></textarea>
      </label>
      <button type="submit" class="btn-primary btn-lg" style="margin-top:1rem;width:100%">📨 إرسال الرسالة</button>
    </form>
  </section>

  <section class="card">
    <h2>⚡ ردود سريعة</h2>
    <div class="tools-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
      <div class="tool-card" style="cursor:default">
        <div class="tc-icon">💬</div>
        <div class="tc-body"><h3>أسئلة شائعة</h3><p>ابحث في صفحات FAQ لكل أداة لإجابات فورية</p></div>
      </div>
      <div class="tool-card" style="cursor:default">
        <div class="tc-icon">⏱️</div>
        <div class="tc-body"><h3>وقت الرد</h3><p>نرد عادةً خلال 24–48 ساعة في أيام العمل</p></div>
      </div>
    </div>
  </section>
  <?php endif; ?>

</div>
