<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';
$siteName = setting('site_name', 'Yassota');
?><!doctype html><html lang="ar" dir="rtl"><head>
<?php seo_head(['title' => 'من نحن | ' . $siteName, 'description' => 'من نحن وكيف نعمل في ' . $siteName . '.', 'canonical' => site_url('about.php')]); ?>
</head><body>
<?php site_header(); ?>
<div class="page-hero container">
  <span class="eyebrow"><i class="fa-solid fa-circle-info"></i> من نحن</span>
  <h1>من نحن — <?= e($siteName) ?></h1>
</div>

<div class="container">
  <div class="founder-card">
    <div class="founder-avatar-wrap">
      <?= svg_initials_avatar('S') ?>
      <span class="founder-verified"><i class="fa-solid fa-check"></i></span>
    </div>
    <div class="founder-name">Saad <i class="fa-solid fa-circle-check founder-check" title="مؤسس موثّق"></i></div>
    <div class="founder-role">المؤسس</div>
    <p class="founder-bio">أهلاً، أنا سعد — أسست <?= e($siteName) ?> لأقدّم باقات نمو رقمي وحماية سيبرانية بأسعار عادلة ودفع فوري وواضح، بدون وعود
    مبالغ فيها. كل باقة موصوفة بصدق: ما تحصل عليه فعلاً، وما لا نستطيع ضمانه (كثبات المتابعين على المدى الطويل مثلاً يعتمد على سياسات كل منصة).
    عندما لا أكتب، أعمل على تحسين الباقات والدعم بحسب طلبات الزوار.</p>
    <div class="founder-socials">
      <?php foreach (['social_twitter'=>'fa-x-twitter','social_facebook'=>'fa-facebook-f','social_linkedin'=>'fa-linkedin-in'] as $key=>$icon): $url = trim(setting($key)); if ($url === '') continue; ?>
        <a href="<?= e($url) ?>" target="_blank" rel="noopener"><i class="fa-brands <?= $icon ?>"></i></a>
      <?php endforeach; ?>
      <a href="mailto:<?= e(setting('contact_email', 'contact@yassota.com')) ?>"><i class="fa-solid fa-envelope"></i></a>
    </div>
  </div>
</div>

<div class="container article-body" style="padding-bottom:60px">
  <p><?= e($siteName) ?> منصة مستقلة تقدّم باقات متابعين ومشاهدات وإعجابات لإنستقرام وفيسبوك ويوتيوب وتيليجرام، بالإضافة إلى أدوات حماية رقمية
  (VPN، مدير كلمات مرور، وغيرها) — بالدفع الفوري بالعملات الرقمية عبر NOWPayments.</p>
  <h2>ماذا نقدّم</h2>
  <ul>
    <li>باقات متابعين ومشاهدات وإعجابات لأربع منصات رئيسية: إنستقرام، فيسبوك، يوتيوب، تيليجرام.</li>
    <li>أدوات حماية رقمية موثوقة لتصفح أكثر أمناً وحماية لحساباتك.</li>
    <li>دفع فوري وآمن بالعملات الرقمية، بدون الحاجة لبطاقة بنكية أو حساب بنكي.</li>
    <li>مساعد ذكي على الموقع يساعدك باختيار الباقة المناسبة على مدار الساعة.</li>
  </ul>
  <h2>أسلوب عملنا</h2>
  <ul>
    <li>وصف صادق لكل باقة — لا نعِد بما لا نتحكم به (كسياسات كل منصة تجاه المتابعين والمشاهدات).</li>
    <li>لا نطلب كلمة مرور حسابك أبداً — فقط رابط أو معرّف الحساب العام.</li>
    <li>دعم مباشر عبر المساعد الذكي أو صفحة "تواصل معنا"، وسياسة استرجاع واضحة عند وجود خلل حقيقي في الخدمة.</li>
  </ul>
  <h2>تواصل معنا</h2>
  <p>أسئلة أو استفسارات عن باقة معينة؟ <a href="<?= site_url('contact.php') ?>">تواصل معنا</a> — نقرأ كل رسالة.</p>
</div>
<?php site_footer(); ?>
</body></html>
