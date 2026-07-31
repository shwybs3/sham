<?php
require_once __DIR__.'/config.php';
$pageTitle = t('من نحن','About Us');
require_once __DIR__.'/header.php';
?>
<main class="page-content">
  <h1><?= t('من نحن','About Us') ?></h1>
  <div class="page-meta"><?= clean(setting('site_name_ar','DarkStore')) ?></div>

  <p><?= t(
    'DarkStore متجر رقمي متخصص في بيع الأدوات والخدمات البرمجية الجاهزة: بوتات تيليغرام، سكريبتات PHP، قوالب ويب، وأدوات API. جميع المنتجات من إنتاجنا الخاص أو مرخّصة للتوزيع، وتُسلَّم فوراً بعد تأكيد الدفع.',
    'DarkStore is a digital store specialized in ready-made tools and dev services: Telegram bots, PHP scripts, web templates, and API tools. All products are our own work or licensed for distribution, and are delivered instantly after payment confirmation.'
  ) ?></p>

  <h2><?= t('ما الذي يميزنا؟','What Sets Us Apart?') ?></h2>
  <ul>
    <li><?= t('منتجات مختبرة وجاهزة للاستخدام مباشرة','Tested, ready-to-use products') ?></li>
    <li><?= t('تسليم فوري عبر البريد الإلكتروني بعد التحقق من الدفع','Instant email delivery after payment verification') ?></li>
    <li><?= t('دعم فني عبر تيليغرام','Technical support via Telegram') ?></li>
    <li><?= t('سياسة استرداد واضحة وعادلة','A clear and fair refund policy') ?></li>
  </ul>

  <div class="highlight-box">
    <?= t(
      'لأي استفسار قبل الشراء، تواصل معنا عبر صفحة <a href="contact.php">التواصل</a> أو تيليغرام.',
      'For any questions before purchase, reach us via the <a href="contact.php">Contact</a> page or Telegram.'
    ) ?>
  </div>
</main>
<?php require_once __DIR__.'/footer.php'; ?>
