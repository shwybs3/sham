<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$lang = is_ar_request() ? 'ar' : 'en';
$dir = $lang === 'ar' ? 'rtl' : 'ltr';

$apps = $pdo->query("SELECT * FROM apps WHERE status='published' ORDER BY id DESC")->fetchAll();
?><!doctype html><html lang="<?= e($lang) ?>" dir="<?= $dir ?>"><head>
<?php seo_head([
    'title' => t('App Directory', 'دليل التطبيقات', $lang) . ' | ' . setting('site_name'),
    'description' => t('Browse the apps we cover — icons, screenshots and details, with a direct link to each one on Google Play.', 'تصفح التطبيقات التي نغطيها — الأيقونات والصور والتفاصيل، مع رابط مباشر لكل تطبيق على Google Play.', $lang),
    'canonical' => site_url($lang === 'ar' ? 'apps.php?lang=ar' : 'apps.php'),
    'lang' => $lang,
]); ?>
</head><body>
<?php site_header(t('Apps', 'التطبيقات', $lang), $lang); ?>

<div class="page-hero container">
  <span class="eyebrow"><i class="fa-solid fa-mobile-screen"></i> <?= count($apps) ?> <?= e(t('apps', 'تطبيق', $lang)) ?></span>
  <h1><?= e(t('App Directory', 'دليل التطبيقات', $lang)) ?></h1>
  <p class="lead"><?= e(t('Icons and screenshots hosted here, always linking straight to the real listing on Google Play.', 'الأيقونات والصور مستضافة هنا دائمًا، مع رابط مباشر للصفحة الحقيقية على Google Play.', $lang)) ?></p>
</div>

<div class="container">
  <?php if ($apps): ?>
  <div class="grid-products">
    <?php foreach ($apps as $a) app_card($a); ?>
  </div>
  <?php else: ?>
    <div class="empty-state"><p><?= e(t('No apps added yet.', 'لا توجد تطبيقات مضافة بعد.', $lang)) ?></p></div>
  <?php endif; ?>
</div>

<?php site_footer($lang); ?>
</body></html>
