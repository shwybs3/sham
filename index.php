<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/cache.php';

if (cache_serve('index')) exit;
cache_start('index');

$pageTitle = t('الرئيسية','Home');
require_once __DIR__.'/header.php';

$prods = products();
$cats  = categoryList();
$lang  = getLang();
?>

<!-- ===== HERO ===== -->
<section class="hero">
  <div class="hero-grid"></div>
  <div class="hero-badge">
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
      <path d="M8 1L10 6H15L11 9.5L12.5 15L8 12L3.5 15L5 9.5L1 6H6L8 1Z" fill="#00f0ff"/>
    </svg>
    <?= t('متجر أدوات رقمي احترافي وموثوق','Professional & Trusted Digital Tools Store') ?>
  </div>
  <h1><?= t(
    'أدوات وخدمات برمجية <span>احترافية</span>',
    'Professional Digital <span>Tools & Services</span>'
  ) ?></h1>
  <p><?= t(
    'بوتات تيليغرام · سكريبتات PHP · قوالب ويب · أدوات API — تسليم فوري آمن',
    'Telegram bots · PHP scripts · Web templates · API tools — Instant secure delivery'
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
      <div style="width:52px;height:52px;border-radius:14px;background:rgba(0,240,255,.08);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
        <i class="fa-solid fa-users" style="font-size:22px;color:var(--cyan);"></i>
      </div>
      <div class="stat-num">500+</div>
      <div class="stat-label"><?= t('عميل راضٍ','Happy Clients') ?></div>
    </div>
    <div class="stat-item">
      <div style="width:52px;height:52px;border-radius:14px;background:rgba(0,255,136,.08);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
        <i class="fa-solid fa-boxes-stacked" style="font-size:22px;color:var(--green);"></i>
      </div>
      <div class="stat-num" style="color:var(--green);"><?= count($prods) ?>+</div>
      <div class="stat-label"><?= t('منتج رقمي','Digital Products') ?></div>
    </div>
    <div class="stat-item">
      <div style="width:52px;height:52px;border-radius:14px;background:rgba(255,184,0,.08);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
        <i class="fa-solid fa-star" style="font-size:22px;color:var(--gold);"></i>
      </div>
      <div class="stat-num" style="color:var(--gold);">4.9★</div>
      <div class="stat-label"><?= t('تقييم العملاء','Customer Rating') ?></div>
    </div>
    <div class="stat-item">
      <div style="width:52px;height:52px;border-radius:14px;background:rgba(124,58,237,.08);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
        <i class="fa-solid fa-bolt" style="font-size:22px;color:var(--purple);"></i>
      </div>
      <div class="stat-num" style="color:var(--purple);">FAST</div>
      <div class="stat-label"><?= t('تسليم فوري','Instant Delivery') ?></div>
    </div>
  </div>
</div>

<!-- ===== WHY US ===== -->
<section style="padding:65px 20px;background:var(--bg2);">
  <div class="container">
    <div class="section-header">
      <h2><?= t('لماذا <span>تختارنا</span>؟','Why <span>Choose Us</span>?') ?></h2>
      <div class="section-divider"></div>
      <p><?= t('نقدم أدوات ومنتجات رقمية عالية الجودة بأسعار تنافسية وتسليم فوري','High-quality digital tools at competitive prices with instant delivery') ?></p>
    </div>
    <div class="features-grid">

      <div class="feat-card">
        <div class="feat-svg">
          <svg viewBox="0 0 56 56" fill="none">
            <circle cx="28" cy="28" r="28" fill="rgba(0,240,255,.07)"/>
            <path d="M28 14L32 22H40L33.5 27L36 35.5L28 31L20 35.5L22.5 27L16 22H24L28 14Z" fill="#00f0ff" opacity=".9"/>
          </svg>
        </div>
        <h3><?= t('تسليم فوري','Instant Delivery') ?></h3>
        <p><?= t('تحصل على منتجك مباشرة بعد تأكيد الدفع — بدون انتظار','Get your product immediately after payment confirmation — no waiting') ?></p>
      </div>

      <div class="feat-card">
        <div class="feat-svg">
          <svg viewBox="0 0 56 56" fill="none">
            <circle cx="28" cy="28" r="28" fill="rgba(0,255,136,.07)"/>
            <path d="M28 14C20.3 14 14 20.3 14 28C14 35.7 20.3 42 28 42C35.7 42 42 35.7 42 28C42 20.3 35.7 14 28 14ZM25.5 34.2L19 27.7L20.8 25.9L25.5 30.6L35.2 20.9L37 22.7L25.5 34.2Z" fill="#00ff88"/>
          </svg>
        </div>
        <h3><?= t('جودة مضمونة','Guaranteed Quality') ?></h3>
        <p><?= t('كل منتج مختبر ومضمون مع دعم ما بعد البيع عبر تيليغرام','Every product is tested and guaranteed with after-sale support on Telegram') ?></p>
      </div>

      <div class="feat-card">
        <div class="feat-svg">
          <svg viewBox="0 0 56 56" fill="none">
            <circle cx="28" cy="28" r="28" fill="rgba(124,58,237,.07)"/>
            <rect x="16" y="20" width="24" height="18" rx="3.5" stroke="#7c3aed" stroke-width="2"/>
            <path d="M21 20V17C21 14.8 22.8 13 25 13H31C33.2 13 35 14.8 35 17V20" stroke="#7c3aed" stroke-width="2"/>
            <circle cx="28" cy="29" r="3" fill="#7c3aed"/>
            <path d="M28 32V35" stroke="#7c3aed" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <h3><?= t('دفع USDT آمن','Secure USDT Payment') ?></h3>
        <p><?= t('نقبل USDT على شبكتي TRC20 وERC20 — مشفّر وآمن 100%','Accept USDT on TRC20 & ERC20 — encrypted and 100% secure') ?></p>
      </div>

      <div class="feat-card">
        <div class="feat-svg">
          <svg viewBox="0 0 56 56" fill="none">
            <circle cx="28" cy="28" r="28" fill="rgba(255,184,0,.07)"/>
            <path d="M28 14C20.3 14 14 20.3 14 28C14 35.7 20.3 42 28 42C35.7 42 42 35.7 42 28C42 20.3 35.7 14 28 14ZM29.5 30H20V27H27.5V20H29.5V30Z" fill="#ffb800"/>
          </svg>
        </div>
        <h3><?= t('دعم 24/7','24/7 Support') ?></h3>
        <p><?= t('فريق دعم فني متاح على مدار الساعة عبر تيليغرام لمساعدتك','Technical support team available 24/7 on Telegram to help you') ?></p>
      </div>

      <div class="feat-card">
        <div class="feat-svg">
          <svg viewBox="0 0 56 56" fill="none">
            <circle cx="28" cy="28" r="28" fill="rgba(255,0,85,.07)"/>
            <path d="M42 20L28 14L14 20V30C14 37.5 20.2 43 28 43C35.8 43 42 37.5 42 30V20Z" stroke="#ff0055" stroke-width="2" fill="none"/>
            <path d="M22 28.5L26 32.5L34 23.5" stroke="#ff0055" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3><?= t('أمان عالي','High Security') ?></h3>
        <p><?= t('حماية CSRF وكابتشا وتحديد معدل الطلبات — موقعنا وبياناتك آمنان','CSRF protection, captcha & rate limiting — your data and our site are secure') ?></p>
      </div>

      <div class="feat-card">
        <div class="feat-svg">
          <svg viewBox="0 0 56 56" fill="none">
            <circle cx="28" cy="28" r="28" fill="rgba(0,240,255,.07)"/>
            <path d="M22 15H34C36.2 15 38 16.8 38 19V37C38 39.2 36.2 41 34 41H22C19.8 41 18 39.2 18 37V19C18 16.8 19.8 15 22 15ZM28 35C29.1 35 30 34.1 30 33C30 31.9 29.1 31 28 31C26.9 31 26 31.9 26 33C26 34.1 26.9 35 28 35Z" fill="#00f0ff" opacity=".7"/>
            <path d="M23 22H33M23 26H30" stroke="#00f0ff" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </div>
        <h3><?= t('منتجات موثّقة','Documented Products') ?></h3>
        <p><?= t('كل منتج يأتي مع توثيق كامل ودليل تثبيت خطوة بخطوة','Every product comes with full documentation and step-by-step installation guide') ?></p>
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

    <div style="max-width:520px;margin:0 auto 28px;">
      <div style="position:relative;">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute;top:50%;transform:translateY(-50%);<?= $lang==='ar'?'right':'left' ?>:18px;color:var(--dim);font-size:15px;"></i>
        <input type="text" id="searchInput" placeholder="<?= t('ابحث عن أداة...','Search tools...') ?>"
          onkeyup="filterSearch(this.value)"
          style="width:100%;background:var(--bg3);border:1px solid var(--border);color:var(--text);padding:13px <?= $lang==='ar'?'48px 13px 18px':'13px 18px 48px' ?>;border-radius:30px;font-size:15px;font-family:inherit;transition:all .3s;"
          onfocus="this.style.borderColor='var(--cyan)';this.style.boxShadow='0 0 0 3px rgba(0,240,255,.07)'"
          onblur="this.style.borderColor='';this.style.boxShadow=''">
      </div>
    </div>

    <div class="cats-bar">
      <button class="cat-btn active" onclick="filterCat('all',this)">
        <i class="fa-solid fa-th-large"></i> <?= t('الكل','All') ?>
      </button>
      <?php foreach($cats as $c): ?>
      <button class="cat-btn" onclick="filterCat('<?= clean($c['name_ar']) ?>',this)">
        <i class="fa-solid <?= clean($c['icon']) ?>"></i>
        <?= t(clean($c['name_ar']), clean($c['name_en'])) ?>
      </button>
      <?php endforeach; ?>
    </div>

    <div class="products-grid" id="productsGrid">
      <?php foreach($prods as $p):
        $fAr = $p['features_ar'] ? explode("\n",$p['features_ar']) : [];
        $fEn = $p['features_en'] ? explode("\n",$p['features_en']) : [];
      ?>
      <div class="product-card" data-cat="<?= clean($p['category']) ?>">
        <div class="card-top">
          <?php if($p['badge_ar']): ?>
          <div class="card-badge" style="background:<?= clean($p['badge_color']) ?>22;color:<?= clean($p['badge_color']) ?>;border:1px solid <?= clean($p['badge_color']) ?>44;">
            <i class="fa-solid fa-certificate"></i> <?= t(clean($p['badge_ar']),clean($p['badge_en']?:'')) ?>
          </div>
          <?php endif; ?>

          <div class="card-icon-wrap" style="color:<?= clean($p['color']) ?>;background:<?= clean($p['color']) ?>12;">
            <i class="fa-solid <?= clean($p['icon']) ?>"></i>
          </div>

          <div class="card-title"><?= t(clean($p['name_ar']),clean($p['name_en'])) ?></div>
          <div class="card-desc"><?= t(clean($p['short_ar']),clean($p['short_en'])) ?></div>

          <div class="card-meta">
            <span><i class="fa-solid fa-star"></i> <?= number_format($p['rating'],1) ?></span>
            <span><i class="fa-solid fa-bag-shopping" style="color:var(--dim)"></i> <?= number_format($p['sales']) ?> <?= t('مبيعة','sold') ?></span>
          </div>

          <?php if(!empty($fAr)): ?>
          <div style="margin-top:12px;">
            <?php foreach(array_slice($lang==='ar'?$fAr:$fEn,0,3) as $f): ?>
              <div style="font-size:12.5px;color:var(--dim);margin-bottom:5px;display:flex;align-items:flex-start;gap:6px;">
                <i class="fa-solid fa-check" style="color:var(--green);font-size:11px;margin-top:3px;flex-shrink:0;"></i>
                <span><?= clean(ltrim($f,'✅ ')) ?></span>
              </div>
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
            <i class="fa-solid fa-bag-shopping"></i> <?= t('اشتر','Buy') ?>
          </a>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if(empty($prods)): ?>
      <div style="grid-column:1/-1;text-align:center;padding:60px 20px;">
        <i class="fa-solid fa-box-open" style="font-size:48px;color:var(--border);display:block;margin-bottom:16px;"></i>
        <p style="color:var(--dim);font-size:16px;"><?= t('لا توجد منتجات متاحة حالياً. أضف منتجات من لوحة التحكم.','No products available yet. Add products from the admin panel.') ?></p>
        <?php if(isAdmin()): ?>
        <a href="admin.php?action=add_product" class="btn btn-primary" style="margin-top:16px;display:inline-flex;">
          <i class="fa-solid fa-plus"></i> <?= t('إضافة منتج','Add Product') ?>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section style="padding:65px 20px;background:var(--bg2);">
  <div class="container">
    <div class="section-header">
      <h2><?= t('كيف <span>يعمل</span>؟','How Does It <span>Work</span>?') ?></h2>
      <div class="section-divider"></div>
    </div>
    <div class="steps-grid">
      <?php
      $steps = [
        ['icon'=>'fa-store','color'=>'#00f0ff','title_ar'=>'اختر المنتج','title_en'=>'Choose Product','desc_ar'=>'تصفح الكتالوج واختر الأداة المناسبة لمشروعك','desc_en'=>'Browse the catalog and select the tool that fits your project','num'=>'01'],
        ['icon'=>'fa-wallet','color'=>'#7c3aed','title_ar'=>'ادفع بـ USDT','title_en'=>'Pay with USDT','desc_ar'=>'أرسل المبلغ لمحفظتنا وأدخل hash المعاملة في النموذج','desc_en'=>'Send the amount to our wallet and enter the transaction hash','num'=>'02'],
        ['icon'=>'fa-bolt','color'=>'#00ff88','title_ar'=>'استلم فوراً','title_en'=>'Get Instantly','desc_ar'=>'بعد التحقق ستتلقى المنتج مباشرة على بريدك الإلكتروني','desc_en'=>'After verification you receive the product directly via email','num'=>'03'],
      ];
      foreach($steps as $s): ?>
      <div class="step-card">
        <div class="step-num" style="background:<?= $s['color'] ?>;color:var(--bg);"><?= $s['num'] ?></div>
        <div class="step-icon" style="background:<?= $s['color'] ?>18;color:<?= $s['color'] ?>;">
          <i class="fa-solid <?= $s['icon'] ?>"></i>
        </div>
        <h3 style="font-size:17px;font-weight:700;margin-bottom:10px;"><?= t($s['title_ar'],$s['title_en']) ?></h3>
        <p style="font-size:13.5px;color:var(--dim);line-height:1.7;"><?= t($s['desc_ar'],$s['desc_en']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== TESTIMONIALS ===== -->
<section style="padding:65px 20px;">
  <div class="container">
    <div class="section-header">
      <h2><?= t('ماذا يقول <span>عملاؤنا</span>؟','What Our <span>Clients Say</span>?') ?></h2>
      <div class="section-divider"></div>
    </div>
    <div class="testimonials-grid">
      <?php
      $testimonials = [
        ['name'=>'Ahmad K.','role_ar'=>'مطور ويب','role_en'=>'Web Developer','bg'=>'linear-gradient(135deg,#00f0ff,#7c3aed)','text_ar'=>'منتج احترافي جداً! البوت يعمل بشكل مثالي واللوحة الإدارية سهلة الاستخدام. التسليم كان فورياً والدعم ممتاز.','text_en'=>'Very professional product! The bot works perfectly and the admin panel is easy to use. Delivery was instant and support is excellent.','stars'=>5],
        ['name'=>'Mohammed S.','role_ar'=>'صاحب متجر إلكتروني','role_en'=>'E-commerce Owner','bg'=>'linear-gradient(135deg,#ff0055,#7c3aed)','text_ar'=>'اشتريت سكريبت المتجر وأنا سعيد جداً بالنتيجة. يعمل على استضافة مجانية InfinityFree بدون مشاكل.','text_en'=>'Bought the store script and I am very happy with the result. Works on InfinityFree hosting without any issues.','stars'=>5],
        ['name'=>'Sara R.','role_ar'=>'مستقلة في التسويق','role_en'=>'Freelance Marketer','bg'=>'linear-gradient(135deg,#ffb800,#ff0055)','text_ar'=>'قالب الصفحة الرئيسية أفضل ما رأيته بهذا السعر. التصميم احترافي والكود نظيف ومنظم.','text_en'=>'Best landing page template I\'ve seen at this price. Professional design and clean, organized code.','stars'=>4],
      ];
      foreach($testimonials as $t): ?>
      <div class="testi-card">
        <div class="testi-stars">
          <?php for($i=0;$i<5;$i++): ?>
            <i class="fa-solid fa-star" style="color:<?= $i<$t['stars']?'var(--gold)':'var(--border)' ?>;font-size:14px;"></i>
          <?php endfor; ?>
        </div>
        <p class="testi-text"><?= getLang()==='ar' ? clean($t['text_ar']) : clean($t['text_en']) ?></p>
        <div class="testi-author">
          <div class="testi-avatar" style="background:<?= $t['bg'] ?>;">
            <?= strtoupper(substr($t['name'],0,1)) ?>
          </div>
          <div>
            <div class="testi-name"><?= clean($t['name']) ?></div>
            <div class="testi-role"><?= getLang()==='ar' ? clean($t['role_ar']) : clean($t['role_en']) ?></div>
          </div>
          <i class="fa-solid fa-quote-right" style="color:var(--border);font-size:24px;margin-<?= getLang()==='ar'?'right':'left' ?>:auto;"></i>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== CTA ===== -->
<section style="padding:60px 20px;background:var(--bg2);text-align:center;border-top:1px solid var(--border);">
  <div style="max-width:600px;margin:0 auto;">
    <div style="width:64px;height:64px;border-radius:18px;background:rgba(0,240,255,.08);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:28px;color:var(--cyan);">
      <i class="fa-brands fa-telegram"></i>
    </div>
    <h2 style="font-size:clamp(20px,3.5vw,30px);font-weight:900;margin-bottom:12px;">
      <?= t('هل لديك سؤال؟ نحن هنا!','Have a Question? We\'re Here!') ?>
    </h2>
    <p style="color:var(--dim);font-size:15px;margin-bottom:28px;line-height:1.75;">
      <?= t('تواصل معنا عبر تيليغرام للاستفسار قبل الشراء أو للدعم الفني. فريقنا يرد خلال دقائق.','Contact us on Telegram for pre-purchase inquiries or technical support. Our team responds within minutes.') ?>
    </p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
      <a href="<?= clean(setting('telegram','#')) ?>" class="btn btn-primary" target="_blank" rel="noopener">
        <i class="fa-brands fa-telegram"></i> <?= t('تواصل عبر تيليغرام','Chat on Telegram') ?>
      </a>
      <a href="contact.php" class="btn btn-outline">
        <i class="fa-solid fa-envelope"></i> <?= t('أرسل رسالة','Send Message') ?>
      </a>
    </div>
  </div>
</section>

<?php require_once __DIR__.'/footer.php'; ?>
