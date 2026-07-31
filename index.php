<?php
require_once __DIR__.'/config.php';
$pageTitle = t('الرئيسية','Home');
require_once __DIR__.'/header.php';

$prods = products();
$cats  = categoryList();
$lang  = getLang();
?>

<!-- ===== HERO ===== -->
<section class="hero">
  <div class="hero-badge">
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
      <path d="M8 1L10 6H15L11 9.5L12.5 15L8 12L3.5 15L5 9.5L1 6H6L8 1Z" fill="#00f0ff"/>
    </svg>
    <?= t('متجر أدوات رقمي احترافي وموثوق','Professional & Trusted Digital Tools Store') ?>
  </div>
  <h1><?= t(
    'أدوات وخدمات برمجية رقمية <span>احترافية</span>',
    'Professional Digital <span>Tools & Dev Services</span>'
  ) ?></h1>
  <p><?= t(
    'بوتات تيليغرام، سكريبتات PHP، قوالب ويب، وأدوات API — تسليم فوري بعد الدفع',
    'Telegram bots, PHP scripts, web templates, and API tools — instant delivery after payment'
  ) ?></p>
  <div class="hero-btns">
    <a href="#products" class="btn btn-primary">
      <i class="fa-solid fa-store"></i> <?= t('تصفح المنتجات','Browse Products') ?>
    </a>
    <a href="<?= clean(setting('telegram','#')) ?>" class="btn btn-outline" target="_blank" rel="noopener">
      <i class="fa-brands fa-telegram"></i> <?= t('تواصل معنا','Contact Us') ?>
    </a>
  </div>
</section>

<!-- ===== STATS ===== -->
<div class="stats-bar">
  <div class="stats-inner">
    <div class="stat-item">
      <svg width="32" height="32" viewBox="0 0 32 32" fill="none" style="margin:0 auto 8px;">
        <rect width="32" height="32" rx="8" fill="rgba(0,240,255,0.08)"/>
        <path d="M16 8C11.6 8 8 11.6 8 16C8 20.4 11.6 24 16 24C20.4 24 24 20.4 24 16C24 11.6 20.4 8 16 8ZM17 20H15V15H17V20ZM17 13H15V11H17V13Z" fill="#00f0ff"/>
      </svg>
      <div class="stat-num">500+</div>
      <div class="stat-label"><?= t('عميل راضٍ','Happy Clients') ?></div>
    </div>
    <div class="stat-item">
      <svg width="32" height="32" viewBox="0 0 32 32" fill="none" style="margin:0 auto 8px;">
        <rect width="32" height="32" rx="8" fill="rgba(0,255,102,0.08)"/>
        <path d="M10 22L14 18L17 21L22 14" stroke="#00ff66" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <div class="stat-num"><?= count($prods) ?>+</div>
      <div class="stat-label"><?= t('منتج رقمي','Digital Products') ?></div>
    </div>
    <div class="stat-item">
      <svg width="32" height="32" viewBox="0 0 32 32" fill="none" style="margin:0 auto 8px;">
        <rect width="32" height="32" rx="8" fill="rgba(255,184,0,0.08)"/>
        <path d="M16 9L18.5 14.5H24L19.5 17.5L21.5 23L16 20L10.5 23L12.5 17.5L8 14.5H13.5L16 9Z" fill="#ffb800"/>
      </svg>
      <div class="stat-num">4.9★</div>
      <div class="stat-label"><?= t('تقييم العملاء','Customer Rating') ?></div>
    </div>
    <div class="stat-item">
      <svg width="32" height="32" viewBox="0 0 32 32" fill="none" style="margin:0 auto 8px;">
        <rect width="32" height="32" rx="8" fill="rgba(124,58,237,0.08)"/>
        <path d="M22 10H10C8.9 10 8 10.9 8 12V22C8 23.1 8.9 24 10 24H22C23.1 24 24 23.1 24 22V12C24 10.9 23.1 10 22 10ZM16 20C13.8 20 12 18.2 12 16C12 13.8 13.8 12 16 12C18.2 12 20 13.8 20 16C20 18.2 18.2 20 16 20Z" fill="#7c3aed"/>
      </svg>
      <div class="stat-num">USDT</div>
      <div class="stat-label"><?= t('دفع آمن','Secure Payment') ?></div>
    </div>
  </div>
</div>

<!-- ===== WHY US ===== -->
<section style="padding:60px 20px;background:var(--bg2);">
  <div class="container">
    <div class="section-header">
      <h2><?= t('لماذا <span>تختارنا</span>؟','Why <span>Choose Us</span>?') ?></h2>
      <div class="section-divider"></div>
      <p><?= t('نقدم أدوات ومنتجات رقمية عالية الجودة بأسعار تنافسية وتسليم فوري','High-quality digital tools and products at competitive prices with instant delivery') ?></p>
    </div>
    <div class="features-grid">

      <div class="feat-card">
        <div class="feat-svg">
          <svg viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="26" cy="26" r="26" fill="rgba(0,240,255,0.07)"/>
            <path d="M26 14L30 22H38L31.5 26.5L34 35L26 30L18 35L20.5 26.5L14 22H22L26 14Z" fill="#00f0ff" opacity=".9"/>
          </svg>
        </div>
        <h3><?= t('تسليم فوري','Instant Delivery') ?></h3>
        <p><?= t('تحصل على المنتج مباشرة بعد تأكيد الدفع دون انتظار','Get your product immediately after payment confirmation') ?></p>
      </div>

      <div class="feat-card">
        <div class="feat-svg">
          <svg viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="26" cy="26" r="26" fill="rgba(0,255,102,0.07)"/>
            <path d="M26 14C19.4 14 14 19.4 14 26C14 32.6 19.4 38 26 38C32.6 38 38 32.6 38 26C38 19.4 32.6 14 26 14ZM24 32L18 26L19.4 24.6L24 29.2L32.6 20.6L34 22L24 32Z" fill="#00ff66"/>
          </svg>
        </div>
        <h3><?= t('جودة مضمونة','Guaranteed Quality') ?></h3>
        <p><?= t('كل منتج مختبر ومضمون الجودة مع دعم ما بعد البيع','Every product is tested and quality-guaranteed with after-sale support') ?></p>
      </div>

      <div class="feat-card">
        <div class="feat-svg">
          <svg viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="26" cy="26" r="26" fill="rgba(124,58,237,0.07)"/>
            <rect x="15" y="20" width="22" height="16" rx="3" stroke="#7c3aed" stroke-width="2"/>
            <path d="M20 20V17C20 14.8 21.8 13 24 13H28C30.2 13 32 14.8 32 17V20" stroke="#7c3aed" stroke-width="2"/>
            <circle cx="26" cy="28" r="2.5" fill="#7c3aed"/>
          </svg>
        </div>
        <h3><?= t('دفع USDT آمن','Secure USDT Payment') ?></h3>
        <p><?= t('نقبل USDT على شبكتي TRC20 وERC20 — آمن وسريع','We accept USDT on TRC20 & ERC20 — secure and fast') ?></p>
      </div>

      <div class="feat-card">
        <div class="feat-svg">
          <svg viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="26" cy="26" r="26" fill="rgba(255,184,0,0.07)"/>
            <path d="M26 14C19.4 14 14 19.4 14 26C14 32.6 19.4 38 26 38C32.6 38 38 32.6 38 26C38 19.4 32.6 14 26 14ZM27 27H19V25H25V19H27V27Z" fill="#ffb800"/>
          </svg>
        </div>
        <h3><?= t('دعم على مدار الساعة','24/7 Support') ?></h3>
        <p><?= t('فريق دعم متاح دائماً عبر تيليغرام للإجابة على استفساراتك','Support team always available on Telegram to answer your questions') ?></p>
      </div>

    </div>
  </div>
</section>

<!-- ===== PRODUCTS ===== -->
<section class="products-section" id="products">
  <div class="container">
    <div class="section-header">
      <h2><?= t('منتجاتنا <span>الرقمية</span>','Our <span>Digital Products</span>') ?></h2>
      <div class="section-divider"></div>
      <p><?= t('اختر الأداة المناسبة وابدأ في الحصول عليها فوراً','Choose the right tool and get it instantly') ?></p>
    </div>

    <div style="max-width:480px;margin:0 auto 24px;">
      <div style="position:relative;">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute;top:50%;transform:translateY(-50%);<?= $lang==='ar'?'right':'left' ?>:16px;color:var(--dim);"></i>
        <input type="text" placeholder="<?= t('ابحث عن منتج...','Search products...') ?>"
          onkeyup="filterSearch(this.value)"
          style="width:100%;background:var(--bg3);border:1px solid var(--border);color:var(--text);padding:12px <?= $lang==='ar'?'44px 12px 16px':'12px 16px 44px' ?>;border-radius:30px;font-size:15px;font-family:inherit;">
      </div>
    </div>

    <div class="cats-bar">
      <button class="cat-btn active" onclick="filterCat('all',this)"><?= t('الكل','All') ?></button>
      <?php foreach($cats as $c): ?>
      <button class="cat-btn" onclick="filterCat('<?= clean($c['name_ar']) ?>',this)">
        <i class="fa-solid <?= clean($c['icon']) ?>"></i>
        <?= t(clean($c['name_ar']), clean($c['name_en'])) ?>
      </button>
      <?php endforeach; ?>
    </div>

    <div class="products-grid">
      <?php foreach($prods as $p):
        $fAr = $p['features_ar'] ? explode("\n",$p['features_ar']) : [];
        $fEn = $p['features_en'] ? explode("\n",$p['features_en']) : [];
      ?>
      <div class="product-card" data-cat="<?= clean($p['category']) ?>">
        <div class="card-top">
          <?php if($p['badge_ar']): ?>
          <div class="card-badge" style="background:<?= clean($p['badge_color']) ?>22;color:<?= clean($p['badge_color']) ?>;border:1px solid <?= clean($p['badge_color']) ?>44;">
            <?= t(clean($p['badge_ar']),clean($p['badge_en']?:'')) ?>
          </div>
          <?php endif; ?>

          <div class="card-icon-wrap" style="color:<?= clean($p['color']) ?>;">
            <i class="fa-solid <?= clean($p['icon']) ?>"></i>
          </div>

          <div class="card-title"><?= t(clean($p['name_ar']),clean($p['name_en'])) ?></div>
          <div class="card-desc"><?= t(clean($p['short_ar']),clean($p['short_en'])) ?></div>

          <div class="card-meta">
            <span><i class="fa-solid fa-star"></i> <?= number_format($p['rating'],1) ?></span>
            <span><i class="fa-solid fa-bag-shopping" style="color:var(--dim)"></i> <?= number_format($p['sales']) ?></span>
          </div>

          <?php if(!empty($fAr)): ?>
          <div style="margin-top:10px;">
            <?php foreach(array_slice($lang==='ar'?$fAr:$fEn,0,3) as $f): ?>
              <div style="font-size:12.5px;color:var(--dim);margin-bottom:4px;"><?= clean($f) ?></div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <div class="card-delivery">
            <i class="fa-solid fa-bolt"></i>
            <?= t(clean($p['delivery_ar']),clean($p['delivery_en'])) ?>
          </div>
        </div>
        <div class="card-bottom">
          <div>
            <div class="card-price"><?= number_format($p['price'],0) ?> <span>USDT</span></div>
            <div class="card-duration"><?= t(clean($p['duration_ar']),clean($p['duration_en'])) ?></div>
          </div>
          <a href="product.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-primary btn-sm">
            <?= t('اشتر الآن','Buy Now') ?> <i class="fa-solid fa-arrow-left"></i>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if(empty($prods)): ?>
      <p style="text-align:center;color:var(--dim);grid-column:1/-1;"><?= t('لا توجد منتجات متاحة حالياً.','No products available yet.') ?></p>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section style="padding:60px 20px;background:var(--bg2);">
  <div class="container">
    <div class="section-header">
      <h2><?= t('كيف <span>يعمل</span>؟','How Does It <span>Work</span>?') ?></h2>
      <div class="section-divider"></div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:22px;max-width:900px;margin:0 auto;">
      <?php
      $steps = [
        ['icon'=>'fa-store','color'=>'#00f0ff','title_ar'=>'اختر المنتج','title_en'=>'Choose Product','desc_ar'=>'تصفح الكتالوج واختر الأداة المناسبة','desc_en'=>'Browse the catalog and select your tool','num'=>'01'],
        ['icon'=>'fa-credit-card','color'=>'#7c3aed','title_ar'=>'ادفع بـ USDT','title_en'=>'Pay with USDT','desc_ar'=>'أرسل المبلغ إلى عنوان محفظتنا وأدخل hash العملية','desc_en'=>'Send the amount to our wallet address and enter the transaction hash','num'=>'02'],
        ['icon'=>'fa-bolt','color'=>'#00ff66','title_ar'=>'استلم فوراً','title_en'=>'Get Instantly','desc_ar'=>'بعد التحقق ستستلم المنتج فوراً على بريدك الإلكتروني','desc_en'=>'After verification you receive the product instantly via email','num'=>'03'],
      ];
      foreach($steps as $s): ?>
      <div style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:26px;text-align:center;position:relative;">
        <div style="position:absolute;top:-14px;<?= $lang==='ar'?'right':'left' ?>:20px;background:<?= $s['color'] ?>;color:var(--bg);font-weight:900;font-size:12px;padding:4px 10px;border-radius:8px;"><?= $s['num'] ?></div>
        <div style="width:54px;height:54px;border-radius:14px;background:<?= $s['color'] ?>18;display:flex;align-items:center;justify-content:center;font-size:24px;color:<?= $s['color'] ?>;margin:0 auto 14px;">
          <i class="fa-solid <?= $s['icon'] ?>"></i>
        </div>
        <h3 style="font-size:17px;font-weight:700;margin-bottom:8px;"><?= t($s['title_ar'],$s['title_en']) ?></h3>
        <p style="font-size:13.5px;color:var(--dim);line-height:1.65;"><?= t($s['desc_ar'],$s['desc_en']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__.'/footer.php'; ?>
