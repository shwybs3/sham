<?php
/**
 * products.php — منتجات yassota (ديجيتال وأجهزة موصى بها)
 */
require_once __DIR__ . '/config.php';

/* ── Route: single product ──────────────────────────────────── */
$slug = trim($_GET['slug'] ?? '', '/');

if ($slug !== '') {
    /* ── Single product page ─────────────────────────────────── */
    $prod = $pdo->prepare("SELECT * FROM products WHERE slug=? AND status='published' LIMIT 1");
    $prod->execute([$slug]);
    $p = $prod->fetch(PDO::FETCH_ASSOC);
    if (!$p) { header('Location: /products'); exit; }

    $catLabels = ['digital'=>'منتجات رقمية','hardware'=>'أجهزة وإكسسوارات','courses'=>'كورسات','templates'=>'قوالب'];
    $catLabel  = $catLabels[$p['category']] ?? $p['category'];
    $discount  = ($p['original_price'] && $p['price']) ? round((1 - $p['price']/$p['original_price'])*100) : 0;

    $schema = json_encode([
        '@context'     => 'https://schema.org',
        '@type'        => 'Product',
        'name'         => $p['name'],
        'description'  => $p['short_description'],
        'url'          => SITE_URL . '/products?slug=' . urlencode($p['slug']),
        'offers'       => ['@type'=>'Offer','priceCurrency'=>$p['currency'],'price'=>$p['price'],'availability'=>'https://schema.org/InStock'],
        'aggregateRating' => $p['rating'] ? ['@type'=>'AggregateRating','ratingValue'=>$p['rating'],'reviewCount'=>max(1,$p['reviews_count'])] : null,
    ], JSON_UNESCAPED_UNICODE);

    $pageTitle    = $p['name'] . ' — yassota';
    $pageDesc     = $p['short_description'];
    $pageKeywords = $p['name'] . ', منتجات yassota';

    ?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="<?= h($pageDesc) ?>">
<link rel="canonical" href="<?= h(SITE_URL . '/products?slug=' . urlencode($p['slug'])) ?>">
<script type="application/ld+json"><?= $schema ?></script>
<?php head_extras($pdo) ?>
</head>
<body>
<?php echo get_main_header($pdo) ?>
<main style="max-width:900px;margin:30px auto;padding:0 16px">
  <!-- Breadcrumb -->
  <nav style="font-size:13px;color:var(--muted);margin-bottom:18px">
    <a href="/" style="color:var(--muted);text-decoration:none">الرئيسية</a> ›
    <a href="/products" style="color:var(--muted);text-decoration:none">المنتجات</a> ›
    <span><?= h($p['name']) ?></span>
  </nav>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start">
    <!-- Icon / visual -->
    <div style="background:<?= h($p['icon_bg'] ?: '#f3f4f6') ?>;border-radius:20px;padding:40px;text-align:center">
      <svg viewBox="0 0 24 24" style="width:120px;height:120px;fill:<?= h($p['icon_color'] ?: '#6366f1') ?>" xmlns="http://www.w3.org/2000/svg">
        <?= $p['icon_svg'] ?: '<circle cx="12" cy="12" r="10"/>' ?>
      </svg>
      <?php if ($p['badge']): ?>
      <div style="margin-top:14px;display:inline-block;background:<?= h($p['icon_color'] ?: '#6366f1') ?>;color:#fff;font-size:12px;padding:4px 12px;border-radius:20px;font-weight:600"><?= h($p['badge']) ?></div>
      <?php endif ?>
    </div>

    <!-- Info -->
    <div>
      <div style="font-size:12px;color:var(--muted);margin-bottom:6px"><?= h($catLabel) ?></div>
      <h1 style="font-size:22px;font-weight:700;margin-bottom:10px;line-height:1.4"><?= h($p['name']) ?></h1>
      <p style="color:var(--muted);font-size:15px;margin-bottom:18px;line-height:1.7"><?= h($p['short_description']) ?></p>

      <!-- Rating -->
      <?php if ($p['rating']): ?>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px">
        <span style="color:#f59e0b;font-size:18px"><?= str_repeat('★', (int)$p['rating']) ?><?= ((float)$p['rating'] - (int)$p['rating'] >= 0.5) ? '½' : '' ?></span>
        <span style="font-weight:600"><?= number_format((float)$p['rating'], 1) ?></span>
        <span style="color:var(--muted);font-size:13px">(<?= number_format((int)$p['reviews_count']) ?> تقييم)</span>
      </div>
      <?php endif ?>

      <!-- Price -->
      <div style="margin-bottom:20px">
        <?php if ($p['original_price'] && $discount > 0): ?>
        <span style="text-decoration:line-through;color:var(--muted);font-size:16px;margin-left:8px"><?= number_format((float)$p['original_price'], 2) ?> <?= h($p['currency']) ?></span>
        <span style="background:#fef3c7;color:#92400e;font-size:12px;padding:2px 7px;border-radius:4px;margin-left:6px">-<?= $discount ?>%</span>
        <?php endif ?>
        <div style="font-size:28px;font-weight:800;color:var(--primary, #2563eb)">
          <?= $p['price'] ? number_format((float)$p['price'], 2) . ' ' . h($p['currency']) : 'مجاناً' ?>
        </div>
      </div>

      <!-- CTA -->
      <?php if ($p['buy_url']): ?>
      <a href="<?= h($p['buy_url']) ?>" target="_blank" rel="noopener nofollow sponsored"
         style="display:block;background:var(--primary,#2563eb);color:#fff;text-align:center;padding:14px 24px;border-radius:12px;font-size:16px;font-weight:700;text-decoration:none;margin-bottom:10px">
        اشترِ الآن ←
      </a>
      <p style="font-size:11px;color:var(--muted);text-align:center">* قد يحتوي الرابط على عمولة تسويقية</p>
      <?php endif ?>
    </div>
  </div>

  <!-- Description -->
  <?php if ($p['description']): ?>
  <div style="margin-top:32px;background:var(--card-bg,#fff);border:1px solid var(--border,#e5e7eb);border-radius:16px;padding:28px">
    <h2 style="font-size:17px;font-weight:700;margin-bottom:14px">تفاصيل المنتج</h2>
    <div style="line-height:1.8;color:var(--body-text,#374151)"><?= $p['description'] ?></div>
  </div>
  <?php endif ?>

  <!-- More products -->
  <?php
  $more = $pdo->prepare("SELECT * FROM products WHERE status='published' AND slug!=? ORDER BY sort_order LIMIT 4");
  $more->execute([$slug]);
  $moreRows = $more->fetchAll(PDO::FETCH_ASSOC);
  ?>
  <?php if ($moreRows): ?>
  <div style="margin-top:36px">
    <h2 style="font-size:17px;font-weight:700;margin-bottom:16px">منتجات أخرى</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px">
      <?php foreach ($moreRows as $m): ?>
      <a href="<?= h(url('products?slug=' . urlencode($m['slug']))) ?>" style="text-decoration:none;color:inherit">
        <div style="background:var(--card-bg,#fff);border:1px solid var(--border,#e5e7eb);border-radius:14px;padding:18px;transition:.2s;cursor:pointer" onmouseenter="this.style.boxShadow='0 4px 20px rgba(0,0,0,.08)'" onmouseleave="this.style.boxShadow='none'">
          <div style="background:<?= h($m['icon_bg'] ?: '#f3f4f6') ?>;border-radius:10px;padding:14px;text-align:center;margin-bottom:12px">
            <svg viewBox="0 0 24 24" style="width:40px;height:40px;fill:<?= h($m['icon_color'] ?: '#6366f1') ?>" xmlns="http://www.w3.org/2000/svg">
              <?= $m['icon_svg'] ?: '<circle cx="12" cy="12" r="10"/>' ?>
            </svg>
          </div>
          <div style="font-size:13px;font-weight:600;margin-bottom:4px;line-height:1.4"><?= h($m['name']) ?></div>
          <div style="font-size:12px;font-weight:700;color:var(--primary,#2563eb)"><?= $m['price'] ? number_format((float)$m['price'],2).' '.$m['currency'] : 'مجاناً' ?></div>
        </div>
      </a>
      <?php endforeach ?>
    </div>
  </div>
  <?php endif ?>
</main>
<?php echo get_main_footer($pdo) ?>
</body>
</html>
<?php
    exit;
}

/* ── Products listing page ───────────────────────────────────── */
$cat    = trim($_GET['cat'] ?? '');
$search = trim($_GET['q'] ?? '');

$where  = ["status='published'"];
$params = [];
if ($cat)    { $where[] = "category=?"; $params[] = $cat; }
if ($search) { $where[] = "(name LIKE ? OR short_description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereStr = implode(' AND ', $where);

$totalCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE $whereStr", ...array_map(fn($v)=>[$v], $params))->fetchColumn();

$st = $pdo->prepare("SELECT * FROM products WHERE $whereStr ORDER BY sort_order, id LIMIT 60");
$st->execute($params);
$products = $st->fetchAll(PDO::FETCH_ASSOC);

$catLabels = ['digital'=>'منتجات رقمية','hardware'=>'أجهزة وإكسسوارات','courses'=>'كورسات','templates'=>'قوالب'];

$pageTitle = 'المنتجات الموصى بها — yassota';
$pageDesc  = 'اكتشف أفضل منتجات التقنية والبرامج المختارة بعناية — أجهزة، اشتراكات رقمية، وأدوات إنتاجية بأسعار مميزة.';

?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="<?= h($pageDesc) ?>">
<link rel="canonical" href="<?= h(SITE_URL . '/products') ?>">
<script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'ItemList','name'=>'منتجات yassota','description'=>$pageDesc,'numberOfItems'=>count($products)], JSON_UNESCAPED_UNICODE) ?></script>
<?php head_extras($pdo) ?>
<style>
.prod-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px}
.prod-card{background:var(--card-bg,#fff);border:1px solid var(--border,#e5e7eb);border-radius:16px;overflow:hidden;transition:.2s;text-decoration:none;color:inherit;display:flex;flex-direction:column}
.prod-card:hover{box-shadow:0 6px 28px rgba(0,0,0,.1);transform:translateY(-2px)}
.prod-card-hero{padding:28px;text-align:center}
.prod-card-body{padding:16px;flex:1;display:flex;flex-direction:column}
.prod-badge{display:inline-block;font-size:11px;padding:3px 10px;border-radius:20px;font-weight:600;margin-bottom:8px}
.prod-name{font-size:15px;font-weight:700;margin-bottom:6px;line-height:1.4}
.prod-desc{font-size:13px;color:var(--muted);line-height:1.6;flex:1;margin-bottom:12px}
.prod-price{font-size:20px;font-weight:800;color:var(--primary,#2563eb)}
.prod-orig{font-size:12px;color:var(--muted);text-decoration:line-through;margin-left:6px}
.prod-discount{font-size:11px;background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:4px}
.prod-cta{margin-top:12px;background:var(--primary,#2563eb);color:#fff;text-align:center;padding:10px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;display:block}
.cat-chip{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:30px;border:1.5px solid var(--border,#e5e7eb);font-size:13px;cursor:pointer;text-decoration:none;color:var(--body-text,#374151);transition:.15s}
.cat-chip.active,.cat-chip:hover{background:var(--primary,#2563eb);color:#fff;border-color:transparent}
@media(max-width:600px){.prod-grid{grid-template-columns:1fr 1fr}}
@media(max-width:360px){.prod-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php echo get_main_header($pdo) ?>
<main style="max-width:1100px;margin:0 auto;padding:24px 16px 48px">

  <!-- Hero -->
  <div style="text-align:center;margin-bottom:32px">
    <div style="font-size:36px;margin-bottom:8px">🛒</div>
    <h1 style="font-size:26px;font-weight:800;margin-bottom:8px">المنتجات الموصى بها</h1>
    <p style="color:var(--muted);font-size:15px;max-width:520px;margin:0 auto">منتجات تقنية وبرامج اختارها فريق yassota بعناية — جميعها مُستخدَمة ومُوصى بها بصدق</p>
  </div>

  <!-- Search -->
  <form method="get" action="/products" style="margin-bottom:22px;display:flex;gap:10px;max-width:500px;margin-left:auto;margin-right:auto">
    <input name="q" value="<?= h($search) ?>" placeholder="ابحث عن منتج..." style="flex:1;padding:10px 16px;border:1.5px solid var(--border,#e5e7eb);border-radius:30px;font-size:14px;font-family:inherit;outline:none">
    <button type="submit" style="background:var(--primary,#2563eb);color:#fff;border:none;border-radius:30px;padding:10px 22px;font-size:14px;cursor:pointer;font-family:inherit">بحث</button>
  </form>

  <!-- Category filters -->
  <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:28px;justify-content:center">
    <a href="/products" class="cat-chip <?= !$cat ? 'active' : '' ?>">🗂️ الكل</a>
    <?php foreach ($catLabels as $k => $v): ?>
    <a href="<?= h(url('products?cat=' . urlencode($k))) ?>" class="cat-chip <?= $cat === $k ? 'active' : '' ?>"><?= h($v) ?></a>
    <?php endforeach ?>
  </div>

  <!-- Count -->
  <div style="font-size:13px;color:var(--muted);margin-bottom:18px"><?= count($products) ?> منتج<?= $search ? " — نتائج: «".h($search)."»" : '' ?></div>

  <?php if (!$products): ?>
  <div style="text-align:center;padding:60px 0;color:var(--muted)">
    <div style="font-size:48px;margin-bottom:12px">🔍</div>
    <p>لم يُوجد منتجات مطابقة للبحث.</p>
    <a href="/products" style="color:var(--primary,#2563eb)">عرض كل المنتجات</a>
  </div>
  <?php else: ?>
  <div class="prod-grid">
    <?php foreach ($products as $p):
      $discount = ($p['original_price'] && $p['price']) ? round((1 - $p['price']/$p['original_price'])*100) : 0;
    ?>
    <a href="<?= h(url('products?slug=' . urlencode($p['slug']))) ?>" class="prod-card">
      <div class="prod-card-hero" style="background:<?= h($p['icon_bg'] ?: '#f3f4f6') ?>">
        <svg viewBox="0 0 24 24" style="width:72px;height:72px;fill:<?= h($p['icon_color'] ?: '#6366f1') ?>" xmlns="http://www.w3.org/2000/svg">
          <?= $p['icon_svg'] ?: '<circle cx="12" cy="12" r="10"/>' ?>
        </svg>
      </div>
      <div class="prod-card-body">
        <?php if ($p['badge']): ?>
        <span class="prod-badge" style="background:<?= h($p['icon_color'] ?: '#6366f1') ?>20;color:<?= h($p['icon_color'] ?: '#6366f1') ?>"><?= h($p['badge']) ?></span>
        <?php endif ?>
        <div class="prod-name"><?= h($p['name']) ?></div>
        <div class="prod-desc"><?= h(mb_substr($p['short_description'] ?? '', 0, 90)) ?>…</div>
        <?php if ($p['rating']): ?>
        <div style="color:#f59e0b;font-size:13px;margin-bottom:6px">
          <?= str_repeat('★', (int)$p['rating']) ?>
          <span style="color:var(--muted);font-size:11px;margin-right:4px"><?= number_format((float)$p['rating'],1) ?> (<?= number_format((int)$p['reviews_count']) ?>)</span>
        </div>
        <?php endif ?>
        <div>
          <?php if ($p['original_price'] && $discount > 0): ?>
          <span class="prod-orig"><?= number_format((float)$p['original_price'],2) ?></span>
          <span class="prod-discount">-<?= $discount ?>%</span>
          <?php endif ?>
          <div class="prod-price"><?= $p['price'] ? number_format((float)$p['price'],2).' '.$p['currency'] : 'مجاناً' ?></div>
        </div>
        <span class="prod-cta">عرض التفاصيل →</span>
      </div>
    </a>
    <?php endforeach ?>
  </div>
  <?php endif ?>

  <!-- SEO text -->
  <div style="margin-top:52px;background:var(--card-bg,#fff);border:1px solid var(--border,#e5e7eb);border-radius:16px;padding:28px">
    <h2 style="font-size:17px;font-weight:700;margin-bottom:14px">لماذا nوثق yassota المنتجات؟</h2>
    <p style="color:var(--muted);line-height:1.8;font-size:14px">فريق yassota يختبر المنتجات بشكل مستقل قبل التوصية بها. لا نروّج لمنتج لم نستخدمه بأنفسنا. كل منتج في هذه القائمة اجتاز معياريّ الجودة والقيمة مقابل السعر. بعض الروابط قد تُولّد عمولة تسويقية تُساهم في تمويل الموقع دون أي تكلفة إضافية عليك.</p>
  </div>
</main>
<?php echo get_main_footer($pdo) ?>
</body>
</html>
