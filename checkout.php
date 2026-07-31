<?php
require_once __DIR__.'/config.php';
$lang  = getLang();
$slug  = clean($_GET['slug'] ?? $_POST['slug'] ?? '');
$p     = $slug ? productBySlug($slug) : null;
if (!$p) { header('Location: index.php'); exit; }

$wallet  = setting('wallet_usdt', WALLET_USDT);
$timeout = (int)setting('pay_timeout','1200');
$orderId = '';
$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $email  = filter_var(trim($_POST['email']??''), FILTER_VALIDATE_EMAIL);
    $txHash = clean($_POST['tx_hash'] ?? '');
    $note   = clean($_POST['note'] ?? '');

    if (!csrfCheck()) {
        $error = t('انتهت صلاحية الجلسة، يرجى إعادة تحميل الصفحة والمحاولة مجدداً','Session expired, please reload the page and try again');
    } elseif (rateLimited('checkout')) {
        $error = t('عدد محاولات كبير خلال فترة قصيرة، يرجى المحاولة لاحقاً','Too many attempts in a short time, please try again later');
    } elseif (!verifyCaptcha()) {
        $error = t('يرجى إتمام التحقق الأمني (CAPTCHA)','Please complete the security check (CAPTCHA)');
    } elseif (!$email) {
        $error = t('يرجى إدخال بريد إلكتروني صحيح','Please enter a valid email address');
    } elseif (strlen($txHash)<10 || strlen($txHash)>255) {
        $error = t('يرجى إدخال hash المعاملة بشكل صحيح','Please enter a valid transaction hash');
    } else {
        $orderId = genOrderId();
        db()->prepare("INSERT INTO orders(order_id,product_id,product_slug,amount,wallet,user_ip,user_email,tx_hash,note,expires_at)
            VALUES(?,?,?,?,?,?,?,?,?,".dbNow('+20 minutes').")")
        ->execute([$orderId,$p['id'],$p['slug'],$p['price'],$wallet,$_SERVER['REMOTE_ADDR'],$email,$txHash,$note]);
        db()->prepare("UPDATE products SET sales=sales+1 WHERE slug=?")->execute([$p['slug']]);
        $success = true;
    }
}

$pageTitle = t('الدفع','Checkout');
require_once __DIR__.'/header.php';
?>

<main class="checkout-page">

  <div style="font-size:13px;color:var(--dim);margin-bottom:22px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
    <a href="index.php"><?= t('الرئيسية','Home') ?></a>
    <i class="fa-solid fa-chevron-<?= $lang==='ar'?'left':'right' ?>" style="font-size:11px;"></i>
    <a href="product.php?slug=<?= urlencode($slug) ?>"><?= t(clean($p['name_ar']),clean($p['name_en'])) ?></a>
    <i class="fa-solid fa-chevron-<?= $lang==='ar'?'left':'right' ?>" style="font-size:11px;"></i>
    <span><?= t('الدفع','Checkout') ?></span>
  </div>

  <?php if ($success): ?>
  <div class="checkout-card" style="text-align:center;">
    <svg width="72" height="72" viewBox="0 0 72 72" fill="none" style="display:block;margin:0 auto 18px;">
      <circle cx="36" cy="36" r="36" fill="rgba(0,255,102,0.1)"/>
      <circle cx="36" cy="36" r="28" fill="rgba(0,255,102,0.15)"/>
      <path d="M24 36L32 44L48 28" stroke="#00ff66" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <h2 style="color:var(--green);margin-bottom:10px;"><?= t('تم استلام طلبك!','Order Received!') ?></h2>
    <p style="color:var(--dim);margin-bottom:20px;"><?= t(
      'سيتم التحقق من الدفع وإرسال المنتج إلى بريدك الإلكتروني خلال دقائق.',
      'Your payment will be verified and the product sent to your email within minutes.'
    ) ?></p>
    <div class="alert alert-info">
      <i class="fa-solid fa-receipt"></i>
      <?= t('رقم الطلب','Order ID') ?>: <strong><?= clean($orderId) ?></strong>
    </div>
    <a href="index.php" class="btn btn-primary" style="margin-top:18px;">
      <i class="fa-solid fa-house"></i> <?= t('العودة للرئيسية','Back to Home') ?>
    </a>
  </div>

  <?php else: ?>

  <div class="checkout-card" style="margin-bottom:18px;">
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
      <div style="width:50px;height:50px;border-radius:12px;background:<?= clean($p['color']) ?>18;display:flex;align-items:center;justify-content:center;font-size:22px;color:<?= clean($p['color']) ?>;flex-shrink:0;">
        <i class="fa-solid <?= clean($p['icon']) ?>"></i>
      </div>
      <div style="flex:1;">
        <div style="font-size:17px;font-weight:700;"><?= t(clean($p['name_ar']),clean($p['name_en'])) ?></div>
        <div style="font-size:13px;color:var(--dim);"><?= t(clean($p['duration_ar']),clean($p['duration_en'])) ?></div>
      </div>
      <div style="font-size:26px;font-weight:900;color:var(--cyan);"><?= number_format($p['price'],0) ?> USDT</div>
    </div>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> <?= $error ?></div>
  <?php endif; ?>

  <div class="checkout-card" style="margin-bottom:18px;">
    <h2><span style="background:var(--cyan);color:var(--bg);width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:14px;font-weight:900;margin-<?= $lang==='ar'?'left':'right' ?>:8px;">1</span>
      <?= t('أرسل USDT إلى المحفظة','Send USDT to Wallet') ?>
    </h2>

    <div style="text-align:center;margin:16px 0 10px;">
      <svg width="56" height="56" viewBox="0 0 56 56" fill="none" style="display:block;margin:0 auto 8px;">
        <circle cx="28" cy="28" r="28" fill="#26A17B22"/>
        <circle cx="28" cy="28" r="22" fill="#26A17B"/>
        <path d="M29.8 27.5V25.4H35.4V22H20.6V25.4H26.2V27.5C22.4 27.9 19.6 29 19.6 30.3C19.6 31.6 22.4 32.7 26.2 33.1V40H29.8V33.1C33.6 32.7 36.4 31.6 36.4 30.3C36.4 29 33.6 27.9 29.8 27.5ZM28 32.2C24.7 32.2 22 31.6 22 30.9C22 30.2 24.7 29.6 28 29.6C31.3 29.6 34 30.2 34 30.9C34 31.6 31.3 32.2 28 32.2Z" fill="white"/>
      </svg>
    </div>

    <p style="font-size:14px;color:var(--dim);text-align:center;margin-bottom:10px;">
      <?= t('أرسل بالضبط','Send exactly') ?> <strong style="color:var(--cyan);font-size:18px;"><?= number_format($p['price'],0) ?> USDT</strong>
      <?= t('إلى العنوان أدناه','to the address below') ?>:
    </p>

    <div class="wallet-box" onclick="copyText('<?= clean($wallet) ?>',this)" title="<?= t('انقر للنسخ','Click to copy') ?>">
      <i class="fa-solid fa-copy"></i>
      <span style="font-family:monospace;font-size:14px;word-break:break-all;"><?= clean($wallet) ?></span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px;">
      <div class="alert alert-info" style="font-size:12px;justify-content:center;">
        <i class="fa-solid fa-network-wired"></i> TRC20 (TRON)
      </div>
      <div class="alert alert-info" style="font-size:12px;justify-content:center;">
        <i class="fa-solid fa-network-wired"></i> ERC20 (ETH)
      </div>
    </div>

    <div class="timer-box">
      <i class="fa-solid fa-clock"></i> <?= t('وقت التأكيد:','Confirmation time:') ?>
      <span id="countdown"></span>
    </div>
  </div>

  <div class="checkout-card">
    <h2><span style="background:var(--cyan);color:var(--bg);width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:14px;font-weight:900;margin-<?= $lang==='ar'?'left':'right' ?>:8px;">2</span>
      <?= t('أدخل بيانات الطلب','Enter Order Details') ?>
    </h2>

    <form method="POST" class="admin-form" style="margin-top:18px;">
      <input type="hidden" name="slug" value="<?= clean($slug) ?>">
      <?php csrfField(); ?>

      <div class="form-group">
        <label><i class="fa-solid fa-envelope"></i> <?= t('البريد الإلكتروني *','Email Address *') ?></label>
        <input type="email" name="email" required placeholder="your@email.com" value="<?= clean($_POST['email']??'') ?>">
        <div style="font-size:12px;color:var(--dim);margin-top:6px;"><?= t('سيُرسل المنتج إلى هذا البريد','The product will be sent to this email') ?></div>
      </div>

      <div class="form-group">
        <label><i class="fa-solid fa-hashtag"></i> <?= t('Transaction Hash *','Transaction Hash *') ?></label>
        <input type="text" name="tx_hash" required minlength="10" maxlength="255" placeholder="0x..." value="<?= clean($_POST['tx_hash']??'') ?>">
        <div style="font-size:12px;color:var(--dim);margin-top:6px;"><?= t('انسخ hash المعاملة من محفظتك','Copy the transaction hash from your wallet') ?></div>
      </div>

      <div class="form-group">
        <label><i class="fa-solid fa-comment"></i> <?= t('ملاحظة (اختياري)','Note (optional)') ?></label>
        <textarea name="note" rows="3" maxlength="1000" placeholder="<?= t('أي تعليق أو طلب خاص...','Any comment or special request...') ?>"><?= clean($_POST['note']??'') ?></textarea>
      </div>

      <?php renderCaptcha(); ?>

      <button type="submit" class="btn btn-primary w-full" style="justify-content:center;font-size:16px;padding:14px;">
        <i class="fa-solid fa-paper-plane"></i> <?= t('تأكيد الطلب','Confirm Order') ?>
      </button>

      <p style="font-size:12px;color:var(--dim);text-align:center;margin-top:14px;">
        <?= t('بالمتابعة أنت توافق على','By continuing you agree to') ?>
        <a href="terms.php" style="font-size:12px;"><?= t('شروط الاستخدام','Terms of Service') ?></a>
        <?= t('و','and') ?>
        <a href="refund-policy.php" style="font-size:12px;"><?= t('سياسة الاسترداد','Refund Policy') ?></a>
      </p>
    </form>
  </div>

  <?php endif; ?>
</main>

<script>startCountdown(<?= $timeout ?>, document.getElementById('countdown'));</script>

<?php require_once __DIR__.'/footer.php'; ?>
