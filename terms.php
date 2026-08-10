<?php
require_once __DIR__.'/config.php';
$pageTitle = t('شروط الاستخدام','Terms of Service');
require_once __DIR__.'/header.php';
?>
<main class="page-content">
  <h1><?= t('شروط الاستخدام','Terms of Service') ?></h1>
  <div class="page-meta"><?= t('آخر تحديث','Last updated') ?>: <?= date('Y-m-d') ?></div>

  <p><?= t(
    'باستخدامك لموقع '.clean(setting('site_name_ar','DarkStore')).' فإنك توافق على الشروط التالية. يرجى قراءتها بعناية قبل الشراء.',
    'By using '.clean(setting('site_name_en','DarkStore')).' you agree to the following terms. Please read them carefully before purchasing.'
  ) ?></p>

  <h2><?= t('طبيعة المنتجات','Nature of Products') ?></h2>
  <p><?= t(
    'جميع المنتجات المعروضة هي منتجات رقمية (سكريبتات، بوتات، قوالب، أدوات). لا يوجد شحن مادي؛ التسليم يتم إلكترونياً عبر البريد الإلكتروني بعد التحقق من الدفع.',
    'All products listed are digital (scripts, bots, templates, tools). There is no physical shipping; delivery is electronic via email after payment verification.'
  ) ?></p>

  <h2><?= t('الدفع','Payment') ?></h2>
  <p><?= t(
    'نقبل الدفع بعملة USDT فقط عبر شبكتي TRC20 أو ERC20. يتحمّل المشتري مسؤولية إرسال المبلغ الصحيح إلى العنوان الصحيح والشبكة الصحيحة. لا نتحمل مسؤولية تحويلات خاطئة لعناوين أو شبكات غير صحيحة.',
    'We accept payment in USDT only via TRC20 or ERC20 networks. The buyer is responsible for sending the correct amount to the correct address and network. We are not responsible for transfers to wrong addresses or wrong networks.'
  ) ?></p>

  <h2><?= t('التحقق من الطلب','Order Verification') ?></h2>
  <p><?= t(
    'بعد إرسال الدفع وتعبئة نموذج الطلب، يقوم فريقنا بالتحقق يدوياً من معاملة الدفع قبل تسليم المنتج. قد تستغرق عملية التحقق بضع دقائق حتى ساعات حسب الحمل.',
    'After sending payment and submitting the order form, our team manually verifies the payment transaction before delivering the product. Verification may take a few minutes up to a few hours depending on load.'
  ) ?></p>

  <h2><?= t('الاستخدام المقبول','Acceptable Use') ?></h2>
  <ul>
    <li><?= t('يُمنع استخدام أي نموذج في الموقع لإرسال بيانات مزوّرة أو محاولة التحايل على أنظمة الحماية','Using any form on the site to submit fraudulent data or attempt to bypass protection systems is prohibited') ?></li>
    <li><?= t('يُمنع إعادة بيع المنتجات دون ترخيص صريح منا','Reselling products without our explicit authorization is prohibited') ?></li>
    <li><?= t('نحتفظ بحق رفض أو إلغاء أي طلب مشبوه','We reserve the right to refuse or cancel any suspicious order') ?></li>
  </ul>

  <h2><?= t('الاسترداد','Refunds') ?></h2>
  <p><?= t('راجع','See our') ?> <a href="refund-policy.php"><?= t('سياسة الاسترداد الكاملة','full Refund Policy') ?></a>.</p>

  <h2><?= t('تعديل الشروط','Changes to These Terms') ?></h2>
  <p><?= t('نحتفظ بحق تعديل هذه الشروط في أي وقت، وسيُنشر أي تعديل على هذه الصفحة.','We reserve the right to modify these terms at any time; any change will be published on this page.') ?></p>
</main>
<?php require_once __DIR__.'/footer.php'; ?>
