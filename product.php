<?php
require_once __DIR__.'/config.php';
$slug = clean($_GET['slug'] ?? '');
$p    = $slug ? productBySlug($slug) : null;
if (!$p) { header('Location: index.php'); exit; }

// increment views
db()->prepare("UPDATE products SET views=views+1 WHERE slug=?")->execute([$slug]);

$lang = getLang();
$fAr  = $p['features_ar'] ? explode("\n",$p['features_ar']) : [];
$fEn  = $p['features_en'] ? explode("\n",$p['features_en']) : [];
$feats = $lang==='ar' ? $fAr : $fEn;

$pageTitle = t(clean($p['name_ar']),clean($p['name_en']));
require_once __DIR__.'/header.php';
?>

<main class="product-page">

  <div style="font-size:13px;color:var(--dim);margin-bottom:22px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
    <a href="index.php"><?= t('الرئيسية','Home') ?></a>
    <i class="fa-solid fa-chevron-<?= $lang==='ar'?'left':'right' ?>" style="font-size:11px;"></i>
    <a href="index.php#products"><?= t('المنتجات','Products') ?></a>
    <i class="fa-solid fa-chevron-<?= $lang==='ar'?'left':'right' ?>" style="font-size:11px;"></i>
    <span><?= t(clean($p['name_ar']),clean($p['name_en'])) ?></span>
  </div>

  <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start;">

    <div>
      <div class="product-hero-card">
        <div class="product-hero-top">
          <div class="product-big-icon" style="background:<?= clean($p['color']) ?>18;color:<?= clean($p['color']) ?>;">
            <i class="fa-solid <?= clean($p['icon']) ?>"></i>
          </div>
          <div class="product-info-main">
            <?php if($p['badge_ar']): ?>
            <div class="card-badge" style="background:<?= clean($p['badge_color']) ?>22;color:<?= clean($p['badge_color']) ?>;border:1px solid <?= clean($p['badge_color']) ?>44;margin-bottom:10px;">
              <?= t(clean($p['badge_ar']),clean($p['badge_en']?:'')) ?>
            </div>
            <?php endif; ?>
            <h1><?= t(clean($p['name_ar']),clean($p['name_en'])) ?></h1>
            <div class="product-tags">
              <span class="ptag"><i class="fa-solid fa-tag"></i> <?= clean($p['category']) ?></span>
              <span class="ptag"><i class="fa-solid fa-clock"></i> <?= t(clean($p['duration_ar']),clean($p['duration_en'])) ?></span>
              <span class="ptag"><i class="fa-solid fa-bolt" style="color:var(--green)"></i> <?= t('تسليم فوري','Instant Delivery') ?></span>
            </div>
            <div class="product-rating">
              <?php for($i=0;$i<5;$i++): ?>
                <i class="fa-solid fa-star" style="color:<?= $i<floor($p['rating'])?'var(--gold)':'var(--border)' ?>;font-size:14px;"></i>
              <?php endfor; ?>
              <span style="font-weight:700;"><?= number_format($p['rating'],1) ?></span>
              <span style="color:var(--dim);font-size:13px;">(<?= number_format($p['sales']) ?> <?= t('مبيعة','sold') ?>)</span>
            </div>
          </div>
        </div>
      </div>

      <div class="admin-card">
        <h3><i class="fa-solid fa-circle-info"></i> <?= t('وصف المنتج','Product Description') ?></h3>
        <p style="font-size:15px;color:#c0c0c0;line-height:1.85;"><?= nl2br(t(clean($p['long_ar']),clean($p['long_en']))) ?></p>
      </div>

      <?php if(!empty($feats)): ?>
      <div class="admin-card">
        <h3><i class="fa-solid fa-list-check"></i> <?= t('المميزات','Features') ?></h3>
        <ul class="features-list">
          <?php foreach($feats as $f): ?>
          <li><?= clean($f) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <div class="admin-card" style="border-color:rgba(0,255,102,.2);">
        <h3 style="color:var(--green);"><i class="fa-solid fa-bolt"></i> <?= t('طريقة التسليم','Delivery Method') ?></h3>
        <p style="font-size:14px;color:#c0c0c0;"><?= t(clean($p['delivery_ar']),clean($p['delivery_en'])) ?></p>
      </div>
    </div>

    <div class="order-box">
      <div style="text-align:center;margin-bottom:16px;">
        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" style="display:block;margin:0 auto 8px;">
          <circle cx="24" cy="24" r="24" fill="#26A17B22"/>
          <circle cx="24" cy="24" r="20" fill="#26A17B"/>
          <path d="M25.6 23.8V22H30.4V19.2H17.6V22H22.4V23.8C19.2 24.1 16.8 25 16.8 26.1C16.8 27.2 19.2 28.1 22.4 28.4V34H25.6V28.4C28.8 28.1 31.2 27.2 31.2 26.1C31.2 25 28.8 24.1 25.6 23.8ZM24 27.6C21.3 27.6 19.1 27.1 19.1 26.5C19.1 25.9 21.3 25.4 24 25.4C26.7 25.4 28.9 25.9 28.9 26.5C28.9 27.1 26.7 27.6 24 27.6Z" fill="white"/>
        </svg>
        <div style="font-size:12px;color:var(--dim);">TRC20 / ERC20</div>
      </div>

      <div class="order-price"><?= number_format($p['price'],0) ?> <small>USDT</small></div>
      <div style="font-size:13px;color:var(--dim);margin-bottom:20px;">
        <?= t(clean($p['duration_ar']),clean($p['duration_en'])) ?>
      </div>

      <div style="font-size:13px;color:var(--green);display:flex;align-items:center;gap:6px;margin-bottom:18px;">
        <i class="fa-solid fa-bolt"></i> <?= t('تسليم فوري بعد الدفع','Instant delivery after payment') ?>
      </div>

      <a href="checkout.php?slug=<?= urlencode($slug) ?>" class="btn btn-primary w-full" style="justify-content:center;margin-bottom:12px;">
        <i class="fa-solid fa-bag-shopping"></i> <?= t('اشتر الآن','Buy Now') ?>
      </a>
      <a href="<?= clean(setting('telegram','#')) ?>" class="btn btn-outline w-full" style="justify-content:center;" target="_blank" rel="noopener">
        <i class="fa-brands fa-telegram"></i> <?= t('استفسر قبل الشراء','Inquire Before Buying') ?>
      </a>

      <div style="margin-top:18px;padding-top:14px;border-top:1px solid var(--border);font-size:12px;color:var(--dim);">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;"><i class="fa-solid fa-shield-halved" style="color:var(--cyan);"></i> <?= t('دفع آمن 100%','100% Secure Payment') ?></div>
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;"><i class="fa-solid fa-rotate-left" style="color:var(--gold);"></i> <a href="refund-policy.php" style="font-size:12px;"><?= t('سياسة الاسترداد','Refund Policy') ?></a></div>
        <div style="display:flex;align-items:center;gap:6px;"><i class="fa-solid fa-eye" style="color:var(--dim);"></i> <?= number_format($p['views']) ?> <?= t('مشاهدة','views') ?></div>
      </div>
    </div>

  </div>

  <style>
  @media(max-width:768px){
    .product-page > div:nth-child(2){grid-template-columns:1fr!important;}
    .order-box{position:static!important;}
  }
  </style>
</main>

<?php require_once __DIR__.'/footer.php'; ?>
