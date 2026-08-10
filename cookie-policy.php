<?php
require_once __DIR__.'/config.php';
$pageTitle = t('سياسة الكوكيز','Cookie Policy');
require_once __DIR__.'/header.php';
?>
<main class="page-content">
  <h1><?= t('سياسة الكوكيز','Cookie Policy') ?></h1>
  <div class="page-meta"><?= t('آخر تحديث','Last updated') ?>: <?= date('Y-m-d') ?></div>

  <p><?= t('يستخدم هذا الموقع عدداً محدوداً من ملفات تعريف الارتباط (كوكيز) الضرورية فقط لعمل الموقع بشكل صحيح:','This site uses a limited number of cookies that are strictly necessary for the site to function correctly:') ?></p>

  <ul>
    <li><code>PHPSESSID</code> — <?= t('كوكي الجلسة، لحفظ حالة تسجيل الدخول والحماية من CSRF','Session cookie, used to keep login state and CSRF protection') ?></li>
    <li><code>pv_ok</code> — <?= t('لحفظ موافقتك على سياسة الخصوصية والشروط لمدة سنة',"To remember your acceptance of the privacy policy and terms for one year") ?></li>
  </ul>

  <div class="highlight-box">
    <?= t('لا نستخدم أي كوكيز إعلانية أو تتبع تسويقي طرف ثالث.','We do not use any advertising or third-party marketing tracking cookies.') ?>
  </div>

  <p><?= t('يمكنك حذف الكوكيز في أي وقت من إعدادات متصفحك، لكن هذا قد يعطّل بعض وظائف الموقع (مثل حفظ لغة العرض أو الموافقة على الشروط).','You can delete cookies at any time from your browser settings, but this may break some site functionality (like the display language or terms acceptance).') ?></p>
</main>
<?php require_once __DIR__.'/footer.php'; ?>
