<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM products WHERE slug = ? AND status='published' LIMIT 1");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

$orderSent = false; $orderError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $orderError = 'انتهت صلاحية الجلسة — يرجى المحاولة مرة أخرى.';
    } else {
        $target = trim($_POST['target'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        if ($target === '' || $contact === '') {
            $orderError = 'يرجى تعبئة رابط/معرّف الحساب وبيانات التواصل.';
        } else {
            $pdo->prepare("INSERT INTO orders (product_id, product_name, name, email, note, amount, currency) VALUES (?,?,?,?,?,?,?)")
                ->execute([$product['id'], $product['name'], $contact, $contact, 'الهدف: ' . $target, $product['price'], $product['currency']]);
            $orderSent = true;
        }
    }
}

$pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$product['id']]);

$features = array_filter(array_map('trim', explode("\n", (string)$product['features'])));
$includes = array_filter(array_map('trim', explode("\n", (string)$product['includes_list'])));
$related = $pdo->prepare("SELECT * FROM products WHERE status='published' AND id != ? AND platform = ? ORDER BY featured DESC, sort_order LIMIT 3");
$related->execute([$product['id'], $product['platform']]);
$related = $related->fetchAll();
if (!$related) {
    $related = $pdo->prepare("SELECT * FROM products WHERE status='published' AND id != ? ORDER BY featured DESC, sort_order LIMIT 3");
    $related->execute([$product['id']]);
    $related = $related->fetchAll();
}

$off = null;
if (!empty($product['compare_at_price']) && (float)$product['compare_at_price'] > (float)$product['price']) {
    $off = (int)round(100 - ((float)$product['price'] / (float)$product['compare_at_price'] * 100));
}

$jsonld = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'description' => $product['meta_description'] ?: $product['short_description'],
    'category' => $product['product_type'],
    'brand' => ['@type' => 'Brand', 'name' => setting('site_name', 'Yassota')],
    'offers' => [
        '@type' => 'Offer',
        'price' => number_format((float)$product['price'], 2, '.', ''),
        'priceCurrency' => $product['currency'],
        'availability' => 'https://schema.org/InStock',
        'url' => site_url('product.php?slug=' . $product['slug']),
    ],
];
?><!doctype html><html lang="ar" dir="rtl"><head>
<?php seo_head([
    'title' => ($product['meta_title'] ?: $product['name']) . ' | ' . setting('site_name', 'Yassota'),
    'description' => $product['meta_description'] ?: $product['short_description'],
    'keywords' => $product['meta_keywords'],
    'canonical' => site_url('product.php?slug=' . $product['slug']),
    'type' => 'product',
    'jsonld' => $jsonld,
]); ?>
</head><body>
<?php site_header('الباقات'); ?>

<div class="container article-hero">
  <div class="breadcrumb"><a href="<?= site_url('') ?>">الرئيسية</a> / <a href="<?= site_url('products.php') ?>">الباقات</a> / <?= e($product['name']) ?></div>
</div>

<div class="container product-layout">
  <div>
    <div style="border-radius:16px;overflow:hidden;border:1px solid var(--line);margin-bottom:24px;line-height:0">
      <?= svg_product_art($product['art_key']) ?>
    </div>

    <span class="badge-trending" style="background:rgba(34,211,238,.15);color:var(--brand1)"><i class="<?= e($product['icon_class'] ?: 'fa-solid fa-cube') ?>"></i> <?= e($product['product_type']) ?></span>
    <?php if ($product['badge']): ?> <span class="badge-trending"><i class="fa-solid fa-star"></i> <?= e($product['badge']) ?></span><?php endif; ?>

    <h1 style="margin-top:14px"><?= e($product['name']) ?></h1>
    <p class="lead" style="color:var(--muted);font-size:17px"><?= e($product['short_description']) ?></p>

    <?php if ($features): ?>
    <div class="section-head" style="margin:32px 0 8px"><h2 style="font-size:20px"><span class="icon-badge" style="background:var(--grad-brand)"><i class="fa-solid fa-list-check"></i></span> ما يشمله الباقة</h2></div>
    <div class="feature-grid">
      <?php foreach ($features as $f): ?>
        <div><i class="fa-solid fa-circle-check"></i> <span><?= e($f) ?></span></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="guarantee-box">
      <?= svg_guarantee_seal() ?>
      <div>
        <h3>ضماننا — ما نستطيع وعدك به فعلاً</h3>
        <p>تفعيل الطلب بعد تأكيد الدفع مباشرة، دعم مباشر عبر تواصل معنا أو المساعد الذكي، وسياسة استرجاع واضحة إذا لم تصل الخدمة كما هو موصوف.
        لا نطلب كلمة مرور حسابك أبداً — فقط رابط/معرّف الحساب العام.</p>
      </div>
    </div>

    <div class="article-body"><?= $product['full_description'] ?></div>

    <div class="order-form" id="order">
      <h2 style="margin-top:0;font-size:19px"><i class="fa-solid fa-paper-plane" style="color:var(--brand1)"></i> اطلب هذه الباقة</h2>
      <?php if ($orderSent): ?>
        <div style="background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.35);color:#6ee7b7;padding:14px;border-radius:10px;font-size:14px">
          <i class="fa-solid fa-circle-check"></i> تم استلام طلبك — إذا لم تدفع بعد، اضغط على "الدفع بالعملات الرقمية" لإكمال الطلب.
        </div>
      <?php else: ?>
        <?php if ($orderError): ?><div style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.35);color:#fca5a5;padding:12px;border-radius:10px;font-size:13.5px;margin-bottom:14px"><?= e($orderError) ?></div><?php endif; ?>
        <p style="font-size:13.5px;color:var(--muted);margin-top:0">أدخل بيانات الطلب، ثم أكمل الدفع من الزر بجانب السعر.</p>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <label>رابط أو معرّف الحساب المستهدف</label><input type="text" name="target" placeholder="مثال: instagram.com/username" required>
          <label>للتواصل معك (تيليجرام أو بريد إلكتروني)</label><input type="text" name="contact" placeholder="@username أو email@example.com" required>
          <button class="btn-run" type="submit" style="width:100%"><i class="fa-solid fa-paper-plane"></i> تسجيل بيانات الطلب</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <aside>
    <div class="buy-box">
      <div class="price-row">
        <span class="price-now"><?= money((float)$product['price'], $product['currency']) ?></span>
        <?php if ($off): ?>
          <span class="price-was"><?= money((float)$product['compare_at_price'], $product['currency']) ?></span>
          <span class="price-off">−<?= $off ?>%</span>
        <?php endif; ?>
      </div>
      <p style="font-size:12.5px;color:var(--muted);margin:0">دفعة واحدة · تفعيل فوري</p>

      <?php if (NOWPayments::isConfigured()): ?>
        <a class="btn-buy" href="<?= site_url('checkout.php?type=product&id=' . (int)$product['id']) ?>"><i class="fa-solid fa-wallet"></i> الدفع بالعملات الرقمية</a>
        <p style="font-size:11.5px;color:var(--muted);margin:8px 0 0;text-align:center">BTC · ETH · USDT وأكثر — عبر NOWPayments</p>
      <?php elseif (trim((string)$product['payment_url']) !== ''): ?>
        <a class="btn-buy" href="<?= e($product['payment_url']) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-cart-shopping"></i> اطلب الآن</a>
      <?php else: ?>
        <a class="btn-buy" href="#order"><i class="fa-solid fa-cart-shopping"></i> سجّل طلبك</a>
      <?php endif; ?>

      <?php if (trim((string)$product['demo_url']) !== ''): ?>
        <a class="btn-demo" href="<?= e($product['demo_url']) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-eye"></i> مشاهدة نموذج</a>
      <?php endif; ?>

      <?php if ($includes): ?>
      <ul class="trust-list">
        <?php foreach ($includes as $inc): ?><li><i class="fa-solid fa-check"></i> <span><?= e($inc) ?></span></li><?php endforeach; ?>
      </ul>
      <?php endif; ?>

      <div style="border-top:1px solid var(--line);margin-top:16px;padding-top:14px;font-size:12.5px;color:var(--muted);line-height:1.8">
        <div><i class="fa-solid fa-shield-halved" style="color:var(--accent-green)"></i> دفع آمن ومشفّر</div>
        <div><i class="fa-solid fa-user-lock" style="color:var(--accent-green)"></i> لا نطلب كلمة مرور حسابك</div>
        <div><i class="fa-solid fa-headset" style="color:var(--accent-green)"></i> دعم مباشر بعد الطلب</div>
      </div>
    </div>
  </aside>
</div>

<div class="container">
  <?php ad_zone('article_bottom'); ?>
  <?php if ($related): ?>
    <div class="section-head"><h2><span class="icon-badge" style="background:var(--grad-cool)"><i class="fa-solid fa-layer-group"></i></span> باقات أخرى قد تهمك</h2></div>
    <div class="grid-products"><?php foreach ($related as $r) product_card($r); ?></div>
  <?php endif; ?>
</div>

<?php site_footer(); ?>
</body></html>
