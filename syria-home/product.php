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
        $orderError = 'Your session expired — please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $note = trim($_POST['note'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $orderError = 'Please enter your name and a valid email address.';
        } else {
            $pdo->prepare("INSERT INTO orders (product_id, product_name, name, email, note, amount, currency) VALUES (?,?,?,?,?,?,?)")
                ->execute([$product['id'], $product['name'], $name, $email, $note, $product['price'], $product['currency']]);
            $orderSent = true;
        }
    }
}

$pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$product['id']]);

$features = array_filter(array_map('trim', explode("\n", (string)$product['features'])));
$includes = array_filter(array_map('trim', explode("\n", (string)$product['includes_list'])));
$related = $pdo->prepare("SELECT * FROM products WHERE status='published' AND id != ? ORDER BY featured DESC, sort_order LIMIT 3");
$related->execute([$product['id']]);
$related = $related->fetchAll();

$off = null;
if (!empty($product['compare_at_price']) && (float)$product['compare_at_price'] > (float)$product['price']) {
    $off = (int)round(100 - ((float)$product['price'] / (float)$product['compare_at_price'] * 100));
}

$selfUrl = site_url('product.php?slug=' . $product['slug']);
$jsonld = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'description' => $product['meta_description'] ?: $product['short_description'],
    'category' => $product['product_type'],
    'brand' => ['@type' => 'Brand', 'name' => setting('site_name')],
    'offers' => [
        '@type' => 'Offer',
        'price' => number_format((float)$product['price'], 2, '.', ''),
        'priceCurrency' => $product['currency'],
        'availability' => 'https://schema.org/InStock',
        'url' => $selfUrl,
    ],
];
/* Only real visitor votes produce star markup — see includes/ratings.php. */
$agg = rating_jsonld('product', (int)$product['id']);
if ($agg) $jsonld['aggregateRating'] = $agg;

$crumbs = [
    ['name' => 'Home', 'url' => site_url('')],
    ['name' => 'Store', 'url' => site_url('products.php')],
    ['name' => $product['name'], 'url' => $selfUrl],
];
?><!doctype html><html lang="en"><head>
<?php seo_head([
    'title' => ($product['meta_title'] ?: $product['name']) . ' | ' . setting('site_name'),
    'description' => $product['meta_description'] ?: $product['short_description'],
    'keywords' => $product['meta_keywords'],
    'canonical' => $selfUrl,
    'type' => 'product',
    'jsonld' => [$jsonld, breadcrumb_jsonld($crumbs)],
]); ?>
</head><body>
<?php site_header('Store'); ?>

<div class="container article-hero">
  <div class="breadcrumb"><a href="<?= site_url('') ?>">Home</a> / <a href="<?= site_url('products.php') ?>">Store</a> / <?= e($product['name']) ?></div>
</div>

<div class="container product-layout">
  <div>
    <div style="border-radius:16px;overflow:hidden;border:1px solid var(--line);margin-bottom:24px;line-height:0">
      <?= svg_product_art($product['art_key']) ?>
    </div>

    <span class="badge-trending" style="background:#eef1ff;color:var(--brand1)"><i class="fa-solid <?= e($product['icon_class']) ?>"></i> <?= e($product['product_type']) ?></span>
    <?php if ($product['badge']): ?> <span class="badge-trending"><i class="fa-solid fa-star"></i> <?= e($product['badge']) ?></span><?php endif; ?>

    <h1 style="margin-top:14px"><?= e($product['name']) ?></h1>
    <p class="lead" style="color:var(--muted);font-size:17px"><?= e($product['short_description']) ?></p>

    <?php if ($features): ?>
    <div class="section-head" style="margin:32px 0 8px"><h2 style="font-size:20px"><span class="icon-badge" style="background:var(--grad-brand)"><i class="fa-solid fa-list-check"></i></span> What's included</h2></div>
    <div class="feature-grid">
      <?php foreach ($features as $f): ?>
        <div><i class="fa-solid fa-circle-check"></i> <span><?= e($f) ?></span></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="guarantee-box">
      <?= svg_guarantee_seal() ?>
      <div>
        <h3>Our guarantee — what we can actually promise</h3>
        <p>Free updates for the period listed, email support for installation and setup, and a full refund within 14 days
        if the product doesn't work as described. We don't promise third-party outcomes we don't control (see the note below).</p>
      </div>
    </div>

    <div class="article-body"><?= $product['full_description'] ?></div>

    <?php rating_widget('product', (int)$product['id']); ?>

    <div class="order-form" id="order">
      <h2 style="margin-top:0;font-size:19px"><i class="fa-solid fa-paper-plane" style="color:var(--brand1)"></i> Request this product</h2>
      <?php if ($orderSent): ?>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#047857;padding:14px;border-radius:10px;font-size:14px">
          <i class="fa-solid fa-circle-check"></i> Thanks — your request was received. We'll email you payment and download details shortly.
        </div>
      <?php else: ?>
        <?php if ($orderError): ?><div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px;border-radius:10px;font-size:13.5px;margin-bottom:14px"><?= e($orderError) ?></div><?php endif; ?>
        <p style="font-size:13.5px;color:var(--muted);margin-top:0">Send a request and we'll reply with payment details and your download link.</p>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <label>Your name</label><input type="text" name="name" required>
          <label>Email address</label><input type="text" name="email" required>
          <label>Anything we should know? (optional)</label><textarea name="note" placeholder="Hosting setup, customization questions, etc."></textarea>
          <button class="btn-buy" type="submit" style="border:0"><i class="fa-solid fa-paper-plane"></i> Send request</button>
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
      <p style="font-size:12.5px;color:var(--muted);margin:0">One-time payment · lifetime license</p>

      <?php if (NOWPayments::isConfigured()): ?>
        <a class="btn-buy" href="<?= site_url('checkout.php?type=product&id=' . (int)$product['id']) ?>"><i class="fa-solid fa-wallet"></i> Pay with crypto</a>
        <p style="font-size:11.5px;color:var(--muted);margin:8px 0 0;text-align:center">BTC, ETH, USDT and more — via NOWPayments</p>
      <?php elseif (trim((string)$product['payment_url']) !== ''): ?>
        <a class="btn-buy" href="<?= e($product['payment_url']) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-cart-shopping"></i> Buy now</a>
      <?php else: ?>
        <a class="btn-buy" href="#order"><i class="fa-solid fa-cart-shopping"></i> Request to buy</a>
      <?php endif; ?>

      <?php if (NOWPayments::isConfigured() && trim((string)$product['payment_url']) !== ''): ?>
        <a class="btn-demo" href="<?= e($product['payment_url']) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-credit-card"></i> Other payment methods</a>
      <?php endif; ?>

      <?php if (trim((string)$product['demo_url']) !== ''): ?>
        <a class="btn-demo" href="<?= e($product['demo_url']) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-eye"></i> View live demo</a>
      <?php endif; ?>

      <?php if ($includes): ?>
      <ul class="trust-list">
        <?php foreach ($includes as $inc): ?><li><i class="fa-solid fa-check"></i> <span><?= e($inc) ?></span></li><?php endforeach; ?>
      </ul>
      <?php endif; ?>

      <div style="border-top:1px solid var(--line);margin-top:16px;padding-top:14px;font-size:12.5px;color:var(--muted);line-height:1.7">
        <div><i class="fa-solid fa-shield-halved" style="color:var(--accent-green)"></i> Secure, one-time purchase</div>
        <div><i class="fa-solid fa-file-code" style="color:var(--accent-green)"></i> Full, unencrypted source code</div>
        <div><i class="fa-solid fa-ban" style="color:var(--accent-green)"></i> No subscription, no license server</div>
      </div>
    </div>
  </aside>
</div>

<div class="container">
  <?php ad_zone('article_bottom'); ?>
  <?php if ($related): ?>
    <div class="section-head"><h2><span class="icon-badge" style="background:var(--grad-cool)"><i class="fa-solid fa-layer-group"></i></span> More from the store</h2></div>
    <div class="grid-products"><?php foreach ($related as $r) product_card($r); ?></div>
  <?php endif; ?>
</div>

<?php site_footer(); ?>
</body></html>
