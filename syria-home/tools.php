<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$lang = is_ar_request() ? 'ar' : 'en';
$dir = $lang === 'ar' ? 'rtl' : 'ltr';

$tools = $pdo->prepare("SELECT * FROM tools WHERE status='published' AND lang = ? ORDER BY uses_count DESC, id DESC");
$tools->execute([$lang]);
$tools = $tools->fetchAll();
?><!doctype html><html lang="<?= e($lang) ?>" dir="<?= $dir ?>"><head>
<?php seo_head([
    'title' => t('Free Online Tools', 'أدوات مجانية عبر الإنترنت', $lang) . ' | ' . setting('site_name'),
    'description' => t('A growing collection of free, fast, browser-based tools — converters, generators and calculators. No installs, no sign-up, nothing uploaded.', 'مجموعة متنامية من الأدوات المجانية والسريعة التي تعمل داخل المتصفح — محولات ومولدات وحاسبات. بدون تثبيت أو تسجيل أو رفع ملفات.', $lang),
    'canonical' => site_url($lang === 'ar' ? 'tools.php?lang=ar' : 'tools.php'),
    'lang' => $lang,
]); ?>
</head><body>
<?php site_header(t('Tools', 'الأدوات', $lang), $lang); ?>

<div class="page-hero container">
  <span class="eyebrow"><i class="fa-solid fa-wrench"></i> <?= count($tools) ?> <?= e(t('free tools', 'أداة مجانية', $lang)) ?></span>
  <h1><?= e(t('Free web tools that just work.', 'أدوات ويب مجانية تعمل بكفاءة.', $lang)) ?></h1>
  <p class="lead"><?= e(t('Everything runs locally in your browser — nothing is ever uploaded to a server.', 'كل شيء يعمل محلياً داخل متصفحك — لا يتم رفع أي شيء إلى أي خادم.', $lang)) ?></p>
</div>

<div class="container">
  <div class="grid grid-tools">
    <?php foreach ($tools as $t) tool_card($t); ?>
  </div>
</div>

<?php site_footer($lang); ?>
</body></html>
