<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$lang = is_ar_request() ? 'ar' : 'en';
$dir = $lang === 'ar' ? 'rtl' : 'ltr';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM apps WHERE slug = ? AND status='published' LIMIT 1");
$stmt->execute([$slug]);
$app = $stmt->fetch();

if (!$app) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

$pdo->prepare("UPDATE apps SET views = views + 1 WHERE id = ?")->execute([$app['id']]);

$screenshots = json_decode($app['screenshots'] ?? '[]', true) ?: [];
$selfUrl = site_url('app.php?slug=' . $app['slug']);
$metaTitle = $app['meta_title'] ?: ($app['name'] . ' — ' . t('App info & screenshots', 'معلومات وصور التطبيق', $lang));
$metaDesc = $app['meta_description'] ?: $app['short_description'];

$jsonld = [
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => $app['name'],
    'description' => $metaDesc,
    'applicationCategory' => $app['category'] ?: 'MobileApplication',
    'operatingSystem' => 'Android',
];
if ($app['icon_path']) $jsonld['image'] = site_url($app['icon_path']);
if ($app['developer']) $jsonld['author'] = ['@type' => 'Organization', 'name' => $app['developer']];

$crumbs = [
    ['name' => t('Home', 'الرئيسية', $lang), 'url' => site_url($lang === 'ar' ? 'ar/' : '')],
    ['name' => t('Apps', 'التطبيقات', $lang), 'url' => site_url('apps.php')],
    ['name' => $app['name'], 'url' => $selfUrl],
];
?><!doctype html><html lang="<?= e($lang) ?>" dir="<?= $dir ?>"><head>
<?php seo_head([
    'title' => $metaTitle . ' | ' . setting('site_name'),
    'description' => $metaDesc,
    'canonical' => $selfUrl,
    'jsonld' => [$jsonld, breadcrumb_jsonld($crumbs)],
    'lang' => $lang,
]); ?>
</head><body>
<?php site_header(t('Apps', 'التطبيقات', $lang), $lang); ?>

<div class="container article-hero">
  <div class="breadcrumb"><a href="<?= site_url($lang === 'ar' ? 'ar/' : '') ?>"><?= e(t('Home', 'الرئيسية', $lang)) ?></a> / <a href="<?= site_url('apps.php') ?>"><?= e(t('Apps', 'التطبيقات', $lang)) ?></a></div>
  <div style="display:flex;align-items:center;gap:16px;margin-top:14px">
    <?php if ($app['icon_path']): ?>
      <img src="<?= site_url($app['icon_path']) ?>" alt="<?= e($app['name']) ?>" style="width:76px;height:76px;border-radius:18px;box-shadow:var(--shadow-lg)">
    <?php endif; ?>
    <div>
      <h1 style="margin:0"><?= e($app['name']) ?></h1>
      <?php if ($app['developer']): ?><p class="lead" style="margin:4px 0 0;font-size:14px"><?= e($app['developer']) ?></p><?php endif; ?>
    </div>
  </div>
  <?php if ($app['short_description']): ?><p class="lead" style="margin-top:14px"><?= e($app['short_description']) ?></p><?php endif; ?>
  <a class="btn-buy" style="display:inline-flex;width:auto;margin-top:6px" href="<?= e($app['play_store_url']) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-google-play"></i> <?= e(t('Get it on Google Play', 'احصل عليه من Google Play', $lang)) ?></a>
</div>

<div class="container">
  <?php if ($screenshots): ?>
  <div style="display:flex;gap:14px;overflow-x:auto;padding:6px 0 18px">
    <?php foreach ($screenshots as $s): ?>
      <img src="<?= site_url($s) ?>" alt="<?= e($app['name']) ?> screenshot" style="height:340px;border-radius:14px;border:1px solid var(--line);flex-shrink:0">
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($app['full_description']): ?>
    <div class="article-body"><?= $app['full_description'] ?></div>
  <?php endif; ?>

  <div class="guarantee-box" style="background:#eef1ff;border-color:#c7d2fe;margin-top:24px">
    <div class="icon-badge" style="background:var(--grad-brand)"><i class="fa-brands fa-google-play"></i></div>
    <div>
      <h3 style="color:var(--brand1)"><?= e(t('Install it from the real Play Store listing', 'ثبّته من صفحة المتجر الحقيقية', $lang)) ?></h3>
      <p style="color:var(--ink-soft)"><?= e(t('We only host the icon, screenshots and this write-up. The app itself installs directly from Google Play — never from a third-party file.', 'نستضيف هنا فقط الأيقونة والصور وهذا الشرح. التطبيق نفسه يُثبَّت مباشرة من Google Play — أبدًا من ملف طرف ثالث.', $lang)) ?></p>
      <a class="btn-buy" style="display:inline-flex;width:auto;margin-top:10px" href="<?= e($app['play_store_url']) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-google-play"></i> <?= e(t('Open on Google Play', 'افتح على Google Play', $lang)) ?></a>
    </div>
  </div>
</div>

<?php site_footer($lang); ?>
</body></html>
