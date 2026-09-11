<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

/* Reached only through the ^__rewrite-probe rule in .htaccess — lets the admin
   panel verify from the browser that mod_rewrite is really working here. */
if (isset($_GET['__rewrite_probe'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'SH_REWRITE_OK';
    exit;
}

$featured = $pdo->query("SELECT * FROM products WHERE status='published' AND featured=1 ORDER BY sort_order LIMIT 6")->fetchAll();
$latest = $pdo->query("SELECT * FROM products WHERE status='published' ORDER BY sort_order, id LIMIT 8")->fetchAll();
$totalPackages = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status='published'")->fetchColumn();

$platforms = [
    'instagram' => ['label' => 'إنستقرام', 'icon' => 'fa-brands fa-instagram'],
    'facebook'  => ['label' => 'فيسبوك', 'icon' => 'fa-brands fa-facebook'],
    'youtube'   => ['label' => 'يوتيوب', 'icon' => 'fa-brands fa-youtube'],
    'telegram'  => ['label' => 'تيليجرام', 'icon' => 'fa-brands fa-telegram'],
    'security'  => ['label' => 'حماية رقمية', 'icon' => 'fa-solid fa-shield-halved'],
];

$latestArticles = [];
$topTools = [];
try {
    $latestArticles = $pdo->query("SELECT a.*, c.name AS category_name FROM articles a LEFT JOIN categories c ON c.id=a.category_id
        WHERE a.status='published' ORDER BY a.published_at DESC LIMIT 3")->fetchAll();
    $topTools = $pdo->query("SELECT * FROM tools WHERE status='published' ORDER BY uses_count DESC, id DESC LIMIT 4")->fetchAll();
} catch (Throwable $e) {}

?><!doctype html><html lang="ar" dir="rtl"><head>
<?php seo_head([
    'title' => setting('site_name', 'Yassota') . ' — باقات متابعين ومشاهدات حقيقية + أدوات حماية رقمية',
    'description' => setting('site_description', 'باقات متابعين ومشاهدات وإعجابات حقيقية لإنستقرام وفيسبوك ويوتيوب وتيليجرام، بالإضافة إلى أدوات حماية رقمية، بالدفع الفوري بالعملات الرقمية عبر NOWPayments.'),
    'keywords' => 'متابعين انستقرام, متابعين تيليجرام, مشتركين يوتيوب, تيليجرام بريميوم, زيادة متابعين, أدوات حماية رقمية, دفع بالعملات الرقمية',
    'canonical' => site_url(''),
]); ?>
</head><body>
<?php site_header('الرئيسية'); ?>

<section class="page-hero container">
  <?= svg_hero_pattern() ?>
  <span class="eyebrow"><i class="fa-solid fa-bolt"></i> تفعيل فوري بعد تأكيد الدفع</span>
  <h1>وسّع حضورك الرقمي وحمِ حساباتك — بدفعة واحدة بالعملات الرقمية.</h1>
  <p class="lead"><?= e(setting('site_tagline', 'باقات متابعين ومشاهدات وإعجابات حقيقية لإنستقرام وفيسبوك ويوتيوب وتيليجرام، وأدوات حماية رقمية (VPN، مدير كلمات مرور) — بدون كلمة مرور حسابك، وبدون اشتراكات مخفية.')) ?></p>
  <form class="hero-search" action="<?= site_url('products.php') ?>" method="get">
    <input type="text" name="q" placeholder="بحث: متابعين إنستقرام، تيليجرام بريميوم، VPN...">
    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
  </form>
  <div class="chip-row">
    <a class="chip active" href="<?= site_url('products.php') ?>"><i class="fa-solid fa-layer-group"></i> كل الباقات</a>
    <?php foreach ($platforms as $key => $p): ?>
      <a class="chip" href="<?= site_url('products.php?platform=' . $key) ?>"><i class="<?= e($p['icon']) ?>"></i> <?= e($p['label']) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="stat-strip">
    <div><i class="fa-solid fa-box-open"></i><b><?= $totalPackages ?: '10+' ?></b><span>باقة متاحة</span></div>
    <div><i class="fa-solid fa-bolt"></i><b>فوري</b><span>تفعيل بعد تأكيد الدفع</span></div>
    <div><i class="fa-solid fa-coins"></i><b>عملات رقمية</b><span>BTC · ETH · USDT وأكثر</span></div>
    <div><i class="fa-solid fa-headset"></i><b>دعم مباشر</b><span>عبر تواصل معنا والمساعد الذكي</span></div>
  </div>
</section>

<div class="container">
<?php ad_zone('home_top'); ?>

<?php if ($featured): ?>
<div class="section-head"><h2><span class="icon-badge" style="background:var(--grad-warm)"><i class="fa-solid fa-star"></i></span> باقات مميزة</h2></div>
<div class="grid-products">
  <?php foreach ($featured as $p) product_card($p); ?>
</div>
<?php endif; ?>

<div class="section-head"><h2><span class="icon-badge" style="background:var(--grad-brand)"><i class="fa-solid fa-cubes"></i></span> كل الباقات</h2><a class="more" href="<?= site_url('products.php') ?>">عرض الكل ←</a></div>
<div class="grid-products">
  <?php foreach ($latest as $p) product_card($p); ?>
</div>

<div class="pay-strip">
  <div class="pill-pay"><i class="fa-solid fa-lock"></i> دفع مشفّر عبر NOWPayments</div>
  <div class="pill-pay"><i class="fa-solid fa-coins"></i> Bitcoin · Ethereum · USDT وأكثر</div>
  <div class="pill-pay"><i class="fa-solid fa-bolt"></i> تفعيل الطلب بعد تأكيد الدفع مباشرة</div>
</div>

<?php ad_zone('home_mid'); ?>

<?php if ($latestArticles): ?>
<div class="section-head"><h2><i class="fa-solid fa-newspaper" style="color:var(--brand1)"></i> من المدونة</h2><a class="more" href="<?= site_url('articles.php') ?>">عرض الكل ←</a></div>
<div class="grid">
  <?php foreach ($latestArticles as $a) article_card($a); ?>
</div>
<?php endif; ?>

<?php if ($topTools): ?>
<div class="section-head"><h2><i class="fa-solid fa-wrench" style="color:var(--accent-green)"></i> أدوات مجانية</h2><a class="more" href="<?= site_url('tools.php') ?>">عرض الكل ←</a></div>
<div class="grid grid-tools">
  <?php foreach ($topTools as $t) tool_card($t); ?>
</div>
<?php endif; ?>
</div>

<?php site_footer(); ?>
</body></html>
