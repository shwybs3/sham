<?php
require_once __DIR__ . '/config.php';

$orderId = clean($_GET['order'] ?? '');
$dlToken = clean($_GET['token'] ?? '');
$error   = '';

// ─── تحقق من رمز التحميل (العودة من صفحة المنتج بعد الكابتشا) ───
if ($dlToken) {
    $dl = validateDownloadToken($dlToken);
    if (!$dl) {
        $error = t('رابط التحميل منتهي أو غير صالح، ابدأ من جديد','Download link expired or invalid, please start over');
    } else {
        $dlUrl = $dl['download_url'] ?? '';
        if ($dlUrl) {
            // لوغ التحميل ثم توجيه مباشر لرابط الملف
            error_log('[DL] token=' . $dlToken . ' order=' . $dl['order_id'] . ' product=' . $dl['product_slug']);
            header('Location: ' . $dlUrl);
            exit;
        }
        // لا يوجد رابط — عرض رسالة جاهزية
        $pageTitle = t('جاري تجهيز ملفك', 'Preparing Your File');
        require_once __DIR__ . '/header.php';
        ?>
<main style="max-width:580px;margin:60px auto;padding:0 20px 80px;text-align:center;">
  <svg width="80" height="80" viewBox="0 0 80 80" fill="none" style="display:block;margin:0 auto 20px;">
    <circle cx="40" cy="40" r="40" fill="rgba(0,240,255,0.08)"/>
    <path d="M40 22L46 34H58L48 41L52 54L40 47L28 54L32 41L22 34H34L40 22Z" fill="#00f0ff" opacity=".9"/>
  </svg>
  <h1 style="font-size:26px;font-weight:900;color:var(--cyan);margin-bottom:12px;">
    <?= t('تم التحقق بنجاح!','Verification Successful!') ?>
  </h1>
  <p style="color:#c0c0c0;font-size:15px;line-height:1.8;margin-bottom:24px;">
    <?= t(
      'تم تأكيد طلبك ويجري تجهيز ملفك. ستتلقى رابط التحميل على بريدك الإلكتروني خلال دقائق قليلة بعد تأكيد الأدمن للمعاملة.',
      'Your order is confirmed and your file is being prepared. You will receive the download link via email within minutes after the admin verifies the transaction.'
    ) ?>
  </p>
  <div class="alert alert-success" style="justify-content:center;">
    <i class="fa-solid fa-circle-check"></i>
    <?= t('رقم طلبك', 'Order ID') ?>: <strong><?= clean($dl['order_id']) ?></strong>
  </div>
  <div style="margin-top:28px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
    <a href="index.php" class="btn btn-outline"><i class="fa-solid fa-house"></i> <?= t('الرئيسية','Home') ?></a>
    <a href="contact.php" class="btn btn-primary"><i class="fa-brands fa-telegram"></i> <?= t('تواصل معنا','Contact Us') ?></a>
  </div>
</main>
        <?php
        require_once __DIR__ . '/footer.php';
        exit;
    }
}

// ─── صفحة الكابتشا الرئيسية ───
if (!$orderId) { header('Location: index.php'); exit; }

$s = db()->prepare("SELECT o.*, p.slug as product_slug, p.name_ar, p.name_en, p.icon, p.color, p.download_url FROM orders o LEFT JOIN products p ON o.product_slug=p.slug WHERE o.order_id=?");
$s->execute([$orderId]);
$order = $s->fetch();

if (!$order) {
    header('Location: index.php'); exit;
}

$productSlug = $order['product_slug'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck()) {
        $error = t('انتهت صلاحية الجلسة، أعد المحاولة','Session expired, try again');
    } elseif (rateLimited('download')) {
        $error = t('محاولات كثيرة جداً، انتظر قليلاً','Too many attempts, please wait');
    } elseif (!verifyCaptcha()) {
        $error = t('يرجى إتمام التحقق الأمني','Please complete the security check');
    } else {
        $token = genDownloadToken($orderId, $productSlug);
        // توجيه لأسفل صفحة المنتج قسم التقييمات مع رمز التحميل
        header('Location: product.php?slug=' . urlencode($productSlug) . '&dl=' . urlencode($token) . '#ratings');
        exit;
    }
}

$pageTitle = t('التحقق للتحميل', 'Download Verification');
require_once __DIR__ . '/header.php';
?>

<main style="max-width:500px;margin:50px auto;padding:0 20px 80px;">

  <div style="text-align:center;margin-bottom:32px;">
    <div style="width:72px;height:72px;border-radius:18px;background:<?= clean($order['color']??'#00f0ff') ?>18;color:<?= clean($order['color']??'#00f0ff') ?>;display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 16px;">
      <i class="fa-solid <?= clean($order['icon']??'fa-cube') ?>"></i>
    </div>
    <h1 style="font-size:22px;font-weight:800;margin-bottom:8px;">
      <?= t(clean($order['name_ar']??''),clean($order['name_en']??'')) ?>
    </h1>
    <p style="color:var(--dim);font-size:14px;">
      <?= t('أكمل التحقق الأمني للوصول إلى ملفك','Complete the security check to access your file') ?>
    </p>
  </div>

  <?php if($error): ?>
  <div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> <?= $error ?></div>
  <?php endif; ?>

  <div class="checkout-card">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--border);">
      <span class="badge-status badge-<?= $order['status'] ?>">
        <?= ['pending'=>t('معلق','Pending'),'paid'=>t('مدفوع','Paid'),'cancelled'=>t('ملغي','Cancelled')][$order['status']]??$order['status'] ?>
      </span>
      <span style="font-size:13px;color:var(--dim);">
        <?= t('رقم الطلب','Order') ?>: <strong style="font-family:monospace;"><?= clean($orderId) ?></strong>
      </span>
    </div>

    <p style="font-size:14px;color:#c0c0c0;margin-bottom:20px;line-height:1.75;">
      <i class="fa-solid fa-shield-halved" style="color:var(--cyan);margin-<?= getLang()==='ar'?'left':'right' ?>:6px;"></i>
      <?= t(
        'للحماية من البوتات والاستخدام العشوائي، نحتاج منك إتمام هذا التحقق السريع قبل الوصول لملفك.',
        'To protect against bots and unauthorized access, please complete this quick check before downloading your file.'
      ) ?>
    </p>

    <form method="POST">
      <?php csrfField(); ?>
      <?php renderCaptcha(); ?>
      <?php if(!captchaEnabled()): ?>
      <div class="alert alert-info" style="margin-bottom:18px;">
        <i class="fa-solid fa-circle-info"></i>
        <?= t('الكابتشا غير مفعّلة — سيتم التوجيه مباشرة','CAPTCHA not enabled — will redirect directly') ?>
      </div>
      <?php endif; ?>
      <button type="submit" class="btn btn-primary w-full" style="justify-content:center;font-size:16px;padding:14px;">
        <i class="fa-solid fa-arrow-down-to-line"></i>
        <?= t('تأكيد والوصول للتحميل','Confirm & Access Download') ?>
      </button>
    </form>
  </div>

  <div style="text-align:center;margin-top:22px;">
    <a href="product.php?slug=<?= urlencode($productSlug) ?>" style="font-size:13px;color:var(--dim);">
      <i class="fa-solid fa-arrow-right"></i> <?= t('العودة لصفحة المنتج','Back to Product') ?>
    </a>
  </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
