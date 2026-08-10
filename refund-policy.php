<?php
require_once __DIR__.'/config.php';

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit_refund'])) {
    $orderId = clean($_POST['order_id'] ?? '');
    $email   = filter_var(trim($_POST['email']??''), FILTER_VALIDATE_EMAIL);
    $reason  = clean($_POST['reason'] ?? '');
    $details = clean($_POST['details'] ?? '');

    if (!csrfCheck()) {
        $error = t('انتهت صلاحية الجلسة، أعد تحميل الصفحة','Session expired, reload the page');
    } elseif (rateLimited('contact')) {
        $error = t('عدد طلبات كبير خلال فترة قصيرة، يرجى المحاولة لاحقاً','Too many requests in a short time, please try again later');
    } elseif (!verifyCaptcha()) {
        $error = t('يرجى إتمام التحقق الأمني (CAPTCHA)','Please complete the security check (CAPTCHA)');
    } elseif (!$orderId || !$email || !$reason || !$details) {
        $error = t('يرجى تعبئة جميع الحقول','Please fill in all fields');
    } else {
        db()->prepare("INSERT INTO refund_requests(order_id,email,reason,details) VALUES(?,?,?,?)")
            ->execute([$orderId,$email,$reason,$details]);
        $success = true;
    }
}

$pageTitle = t('سياسة الاسترداد','Refund Policy');
require_once __DIR__.'/header.php';
?>
<main class="page-content">
  <h1><?= t('سياسة الاسترداد','Refund Policy') ?></h1>
  <div class="page-meta"><?= t('آخر تحديث','Last updated') ?>: <?= date('Y-m-d') ?></div>

  <p><?= t(
    'نظراً لطبيعة المنتجات الرقمية القابلة للنسخ، فإن الاسترداد يخضع للشروط التالية:',
    'Given the nature of copyable digital products, refunds are subject to the following conditions:'
  ) ?></p>

  <h2><?= t('حالات يحق فيها الاسترداد','Cases Eligible for Refund') ?></h2>
  <ul>
    <li><?= t('عدم تسليم المنتج خلال 48 ساعة من تأكيد الدفع دون سبب واضح','The product was not delivered within 48 hours of payment confirmation without a clear reason') ?></li>
    <li><?= t('المنتج المُسلَّم معطوب أو لا يعمل كما هو موصوف ولم تُحل المشكلة عبر الدعم الفني','The delivered product is broken or does not work as described and support could not resolve it') ?></li>
    <li><?= t('الدفع المزدوج لنفس الطلب عن طريق الخطأ','Accidental double payment for the same order') ?></li>
  </ul>

  <h2><?= t('حالات لا يحق فيها الاسترداد','Cases Not Eligible for Refund') ?></h2>
  <ul>
    <li><?= t('بعد تحميل الملفات أو الوصول الكامل للمنتج بنجاح','After the files have been downloaded or the product fully accessed successfully') ?></li>
    <li><?= t('تغيير رأي المشتري دون وجود عيب فعلي في المنتج','Buyer\'s change of mind without an actual product defect') ?></li>
    <li><?= t('إرسال المبلغ لعنوان محفظة أو شبكة خاطئة من قبل المشتري','Sending funds to the wrong wallet address or network by the buyer') ?></li>
  </ul>

  <h2><?= t('مدة معالجة الاسترداد','Refund Processing Time') ?></h2>
  <p><?= t('يتم مراجعة كل طلب استرداد يدوياً خلال 3-7 أيام عمل، ويُرسل الرد إلى بريدك الإلكتروني.','Every refund request is manually reviewed within 3-7 business days, and the response is sent to your email.') ?></p>

  <div class="highlight-box">
    <?= t('عملات الاسترداد تُرسل بنفس عملة الدفع (USDT) إلى عنوان محفظة تحدده أنت في التفاصيل أدناه.','Refunds are sent in the same currency as payment (USDT) to a wallet address you specify in the details below.') ?>
  </div>

  <h2 id="request"><?= t('تقديم طلب استرداد','Submit a Refund Request') ?></h2>
  <div class="contact-card" style="margin-top:16px;">
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= t('تم استلام طلب الاسترداد، سيتم مراجعته والرد عليك عبر البريد الإلكتروني.','Your refund request has been received and will be reviewed; we\'ll reply via email.') ?></div>
    <?php else: ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> <?= $error ?></div>
    <?php endif; ?>
    <form method="POST" class="admin-form">
      <?php csrfField(); ?>
      <div class="form-group">
        <label><?= t('رقم الطلب (ORD-...) *','Order ID (ORD-...) *') ?></label>
        <input type="text" name="order_id" required value="<?= clean($_POST['order_id']??'') ?>">
      </div>
      <div class="form-group">
        <label><?= t('البريد الإلكتروني المستخدم بالطلب *','Email used for the order *') ?></label>
        <input type="email" name="email" required value="<?= clean($_POST['email']??'') ?>">
      </div>
      <div class="form-group">
        <label><?= t('سبب الاسترداد *','Reason for refund *') ?></label>
        <input type="text" name="reason" required value="<?= clean($_POST['reason']??'') ?>">
      </div>
      <div class="form-group">
        <label><?= t('التفاصيل + عنوان محفظة USDT للاسترداد *','Details + USDT refund wallet address *') ?></label>
        <textarea name="details" rows="4" required maxlength="2000"><?= clean($_POST['details']??'') ?></textarea>
      </div>
      <?php renderCaptcha(); ?>
      <button type="submit" name="submit_refund" class="btn btn-primary w-full" style="justify-content:center;">
        <i class="fa-solid fa-paper-plane"></i> <?= t('إرسال طلب الاسترداد','Submit Refund Request') ?>
      </button>
    </form>
    <?php endif; ?>
  </div>
</main>
<?php require_once __DIR__.'/footer.php'; ?>
