<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$featured = $pdo->query("SELECT * FROM products WHERE status='published' AND featured=1 ORDER BY sort_order")->fetchAll();
$all = $pdo->query("SELECT * FROM products WHERE status='published' ORDER BY sort_order, id")->fetchAll();
?><!doctype html><html lang="en"><head>
<?php seo_head([
    'title' => 'Store — Scripts, Templates & Toolkits | ' . setting('site_name'),
    'description' => 'Self-hosted PHP scripts, templates and developer toolkits. One-time payment, full source code, free updates and a 14-day refund window.',
    'keywords' => 'php scripts, website templates, adsense ready script, developer toolkits',
    'canonical' => site_url('products.php'),
]); ?>
</head><body>
<?php site_header('Store'); ?>

<section class="page-hero container">
  <?= svg_hero_pattern() ?>
  <span class="eyebrow"><i class="fa-solid fa-store"></i> Digital Store</span>
  <h1>Scripts, templates &amp; toolkits you own outright.</h1>
  <p class="lead">One-time payment. Full source code. No subscriptions, no license servers, no monthly fees — plus free updates and setup support on every product.</p>

  <div class="stat-strip">
    <div><i class="fa-solid fa-code"></i><b><?= count($all) ?></b><span>Products</span></div>
    <div><i class="fa-solid fa-infinity"></i><b>One-time</b><span>No subscriptions</span></div>
    <div><i class="fa-solid fa-rotate-left"></i><b>14 days</b><span>Refund window</span></div>
    <div><i class="fa-solid fa-headset"></i><b>Included</b><span>Setup support</span></div>
  </div>
</section>

<div class="container">
  <?php ad_zone('home_top'); ?>

  <?php if ($featured): ?>
  <div class="section-head">
    <h2><span class="icon-badge" style="background:var(--grad-warm)"><i class="fa-solid fa-star"></i></span> Featured</h2>
  </div>
  <div class="grid-products">
    <?php foreach ($featured as $p) product_card($p); ?>
  </div>
  <?php endif; ?>

  <div class="section-head">
    <h2><span class="icon-badge" style="background:var(--grad-brand)"><i class="fa-solid fa-cubes"></i></span> All products</h2>
  </div>
  <div class="grid-products">
    <?php foreach ($all as $p) product_card($p); ?>
  </div>

  <div class="honest-note" style="margin-top:34px">
    <strong>About "AdSense-ready":</strong> products described that way are built to meet Google's technical and policy
    prerequisites — original content structure, the required legal pages, fast load times, clear navigation and correctly
    placed ad zones. Approval itself is Google's decision and depends on your own domain, traffic and published content.
    No seller can honestly guarantee it, and we don't. What we do guarantee is in our
    <a href="<?= site_url('refund-policy.php') ?>">refund policy</a>.
  </div>

  <?php ad_zone('home_mid'); ?>
</div>

<?php site_footer(); ?>
</body></html>
