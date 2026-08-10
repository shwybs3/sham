<?php
require_once __DIR__.'/config.php';
$pageTitle = t('سياسة الخصوصية','Privacy Policy');
require_once __DIR__.'/header.php';
?>
<main class="page-content">
  <h1><?= t('سياسة الخصوصية','Privacy Policy') ?></h1>
  <div class="page-meta"><?= t('آخر تحديث','Last updated') ?>: <?= date('Y-m-d') ?></div>

  <p><?= t(
    'نحترم خصوصيتك ونلتزم بحماية بياناتك الشخصية. توضح هذه السياسة البيانات التي نجمعها وكيفية استخدامها.',
    'We respect your privacy and are committed to protecting your personal data. This policy explains what data we collect and how we use it.'
  ) ?></p>

  <h2><?= t('البيانات التي نجمعها','Data We Collect') ?></h2>
  <ul>
    <li><?= t('البريد الإلكتروني عند إتمام الطلب أو التواصل معنا','Email address when placing an order or contacting us') ?></li>
    <li><?= t('عنوان IP لأغراض الأمان ومنع الاحتيال والفلود','IP address for security, fraud prevention, and flood protection purposes') ?></li>
    <li><?= t('hash معاملة الدفع (USDT) للتحقق من الطلب','Payment transaction hash (USDT) to verify the order') ?></li>
    <li><?= t('ملفات تعريف ارتباط (كوكيز) لحفظ تفضيلات اللغة والموافقة على الشروط','Cookies to save language preference and terms acceptance') ?></li>
  </ul>

  <h2><?= t('كيف نستخدم بياناتك','How We Use Your Data') ?></h2>
  <ul>
    <li><?= t('تسليم المنتج المشترى إلى بريدك الإلكتروني','Delivering the purchased product to your email') ?></li>
    <li><?= t('الرد على استفساراتك عبر نموذج التواصل','Responding to your inquiries via the contact form') ?></li>
    <li><?= t('حماية الموقع من البوتات، الفلود، ومحاولات الاحتيال','Protecting the site from bots, flooding, and fraud attempts') ?></li>
  </ul>

  <div class="highlight-box">
    <?= t(
      'لا نبيع أو نشارك بياناتك مع أي طرف ثالث لأغراض تسويقية. تُستخدم بياناتك فقط لإتمام طلبك وتقديم الدعم.',
      'We do not sell or share your data with any third party for marketing purposes. Your data is used only to fulfill your order and provide support.'
    ) ?>
  </div>

  <h2><?= t('خدمات طرف ثالث','Third-Party Services') ?></h2>
  <p><?= t(
    'قد نستخدم Cloudflare Turnstile للتحقق الأمني (CAPTCHA) عند تعبئة النماذج. راجع سياسة خصوصية Cloudflare لمزيد من التفاصيل.',
    'We may use Cloudflare Turnstile for security verification (CAPTCHA) when submitting forms. See Cloudflare\'s privacy policy for details.'
  ) ?></p>

  <h2><?= t('تواصل معنا','Contact Us') ?></h2>
  <p><?= t('لأي استفسار حول هذه السياسة، راسلنا عبر','For any question about this policy, contact us via') ?> <a href="contact.php"><?= t('صفحة التواصل','the Contact page') ?></a>.</p>
</main>
<?php require_once __DIR__.'/footer.php'; ?>
