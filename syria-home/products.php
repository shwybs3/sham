<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$platforms = [
    'instagram' => ['label' => 'إنستقرام', 'icon' => 'fa-brands fa-instagram'],
    'facebook'  => ['label' => 'فيسبوك', 'icon' => 'fa-brands fa-facebook'],
    'youtube'   => ['label' => 'يوتيوب', 'icon' => 'fa-brands fa-youtube'],
    'telegram'  => ['label' => 'تيليجرام', 'icon' => 'fa-brands fa-telegram'],
    'security'  => ['label' => 'حماية رقمية', 'icon' => 'fa-solid fa-shield-halved'],
];
$activePlatform = $_GET['platform'] ?? '';
if (!array_key_exists($activePlatform, $platforms)) $activePlatform = '';

$q = trim((string)($_GET['q'] ?? ''));

$sql = "SELECT * FROM products WHERE status='published'";
$params = [];
if ($activePlatform !== '') { $sql .= " AND platform = ?"; $params[] = $activePlatform; }
if ($q !== '') { $sql .= " AND (name LIKE ? OR tagline LIKE ? OR short_description LIKE ?)"; $like = '%' . $q . '%'; array_push($params, $like, $like, $like); }
$sql .= " ORDER BY sort_order, id";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$all = $stmt->fetchAll();

$featured = $activePlatform === '' && $q === ''
    ? $pdo->query("SELECT * FROM products WHERE status='published' AND featured=1 ORDER BY sort_order")->fetchAll()
    : [];

$pageTitle = $activePlatform !== '' ? 'باقات ' . $platforms[$activePlatform]['label'] : 'كل الباقات';
?><!doctype html><html lang="ar" dir="rtl"><head>
<?php seo_head([
    'title' => $pageTitle . ' | ' . setting('site_name', 'Yassota'),
    'description' => 'باقات متابعين ومشاهدات وإعجابات حقيقية وأدوات حماية رقمية، بالدفع الفوري بالعملات الرقمية.',
    'keywords' => 'متابعين انستقرام, متابعين فيسبوك, مشتركين يوتيوب, تيليجرام بريميوم, ادوات حماية رقمية',
    'canonical' => site_url('products.php' . ($activePlatform !== '' ? '?platform=' . $activePlatform : '')),
]); ?>
</head><body>
<?php site_header('الباقات'); ?>

<section class="page-hero container">
  <?= svg_hero_pattern() ?>
  <span class="eyebrow"><i class="fa-solid fa-store"></i> المتجر</span>
  <h1><?= e($pageTitle) ?></h1>
  <p class="lead">اختر المنصة، ثم الباقة المناسبة — تفعيل فوري بعد تأكيد الدفع بالعملات الرقمية عبر NOWPayments.</p>

  <form class="hero-search" action="<?= site_url('products.php') ?>" method="get" style="max-width:480px">
    <?php if ($activePlatform !== ''): ?><input type="hidden" name="platform" value="<?= e($activePlatform) ?>"><?php endif; ?>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="بحث عن باقة...">
    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
  </form>

  <div class="chip-row">
    <a class="chip <?= $activePlatform === '' ? 'active' : '' ?>" href="<?= site_url('products.php') ?>"><i class="fa-solid fa-layer-group"></i> الكل</a>
    <?php foreach ($platforms as $key => $p): ?>
      <a class="chip <?= $activePlatform === $key ? 'active' : '' ?>" href="<?= site_url('products.php?platform=' . $key) ?>"><i class="<?= e($p['icon']) ?>"></i> <?= e($p['label']) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="stat-strip">
    <div><i class="fa-solid fa-box-open"></i><b><?= count($all) ?></b><span>باقة</span></div>
    <div><i class="fa-solid fa-bolt"></i><b>فوري</b><span>تفعيل بعد الدفع</span></div>
    <div><i class="fa-solid fa-rotate-left"></i><b>ضمان</b><span>سياسة استرجاع واضحة</span></div>
    <div><i class="fa-solid fa-headset"></i><b>مدعوم</b><span>مساعد ذكي + تواصل مباشر</span></div>
  </div>
</section>

<div class="container">
  <?php ad_zone('home_top'); ?>

  <?php if ($featured): ?>
  <div class="section-head">
    <h2><span class="icon-badge" style="background:var(--grad-warm)"><i class="fa-solid fa-star"></i></span> باقات مميزة</h2>
  </div>
  <div class="grid-products">
    <?php foreach ($featured as $p) product_card($p); ?>
  </div>
  <?php endif; ?>

  <div class="section-head">
    <h2><span class="icon-badge" style="background:var(--grad-brand)"><i class="fa-solid fa-cubes"></i></span> <?= e($pageTitle) ?></h2>
  </div>
  <?php if ($all): ?>
  <div class="grid-products">
    <?php foreach ($all as $p) product_card($p); ?>
  </div>
  <?php else: ?>
  <div class="empty-state">
    <i class="fa-solid fa-box-open" style="font-size:32px;color:var(--muted)"></i>
    <p>لا توجد باقات مطابقة حالياً.</p>
  </div>
  <?php endif; ?>

  <div class="honest-note" style="margin-top:34px">
    <strong>ملاحظة مهمة:</strong> باقات المتابعين والمشاهدات تعتمد على نمو تدريجي وحقيقي حسب طبيعة كل منصة، وقد تتأخر أو تتذبذب الأرقام
    بحسب سياسات المنصة نفسها. لا نطلب كلمة مرور حسابك أبداً — فقط رابط الحساب أو المعرّف العام. راجع
    <a href="<?= site_url('refund-policy.php') ?>">سياسة الاسترجاع</a> لتفاصيل الضمان.
  </div>

  <?php ad_zone('home_mid'); ?>
</div>

<?php site_footer(); ?>
</body></html>
