<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/cache.php';

$slug = clean($_GET['slug'] ?? '');
$p    = $slug ? productBySlug($slug) : null;
if (!$p) { header('Location: index.php'); exit; }

$lang     = getLang();
$dlToken  = clean($_GET['dl'] ?? '');
$dlInfo   = null;
if ($dlToken) {
    $dlInfo = validateDownloadToken($dlToken);
    if ($dlInfo && $dlInfo['product_slug'] !== $slug) $dlInfo = null;
}

// ── نموذج إضافة تقييم ──
$reviewMsg  = '';
$reviewErr  = '';
$reviewOk   = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_review'])) {
    if (!csrfCheck()) {
        $reviewErr = t('انتهت صلاحية الجلسة، أعد تحميل الصفحة','Session expired, please reload');
    } elseif (rateLimited('review')) {
        $reviewErr = t('عدد محاولات كبير، يرجى الانتظار قليلاً','Too many attempts, please wait');
    } else {
        $rName   = trim($_POST['rv_name'] ?? '');
        $rRating = min(5, max(1, (int)($_POST['rv_rating'] ?? 5)));
        $rText   = trim($_POST['rv_text'] ?? '');
        if (!$rName || strlen($rName) < 2 || strlen($rName) > 100) {
            $reviewErr = t('يرجى إدخال اسم صحيح (2-100 حرف)','Please enter a valid name (2-100 chars)');
        } elseif (strlen($rText) < 15 || strlen($rText) > 2000) {
            $reviewErr = t('يرجى كتابة تقييم لا يقل عن 15 حرفاً ولا يتجاوز 2000','Review must be 15-2000 characters');
        } else {
            db()->prepare("INSERT INTO reviews(product_slug,name,rating,text,ip_hash) VALUES(?,?,?,?,?)")
                ->execute([$slug, $rName, $rRating, $rText, clientIpHash()]);
            // إبطال الكاش عند وصول تقييم جديد
            cache_clear('product', $slug);
            $reviewOk  = true;
            $reviewMsg = t('شكراً! تقييمك في انتظار المراجعة وسيظهر قريباً.','Thank you! Your review is pending approval and will appear soon.');
        }
    }
}

// increment views (بعد معالجة POST لتجنب العَدّ المزدوج)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    db()->prepare("UPDATE products SET views=views+1 WHERE slug=?")->execute([$slug]);
}

$fAr   = $p['features_ar'] ? explode("\n",$p['features_ar']) : [];
$fEn   = $p['features_en'] ? explode("\n",$p['features_en']) : [];
$feats = $lang==='ar' ? $fAr : $fEn;

$reviews   = productReviews($slug);
$cacheable = ($dlToken === '' && $_SERVER['REQUEST_METHOD'] === 'GET');

if ($cacheable && cache_serve('product', $slug)) exit;

$pageTitle = t(clean($p['name_ar']),clean($p['name_en']));

if ($cacheable) cache_start('product', $slug);
require_once __DIR__.'/header.php';
?>

<main class="product-page">

  <!-- Breadcrumb -->
  <div style="font-size:13px;color:var(--dim);margin-bottom:22px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
    <a href="index.php"><?= t('الرئيسية','Home') ?></a>
    <i class="fa-solid fa-chevron-<?= $lang==='ar'?'left':'right' ?>" style="font-size:11px;"></i>
    <a href="index.php#products"><?= t('المنتجات','Products') ?></a>
    <i class="fa-solid fa-chevron-<?= $lang==='ar'?'left':'right' ?>" style="font-size:11px;"></i>
    <span><?= t(clean($p['name_ar']),clean($p['name_en'])) ?></span>
  </div>

  <div class="product-layout">

    <!-- LEFT: Main content -->
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

      <div class="admin-card" style="border-color:rgba(0,255,136,.2);">
        <h3 style="color:var(--green);"><i class="fa-solid fa-bolt"></i> <?= t('طريقة التسليم','Delivery Method') ?></h3>
        <p style="font-size:14px;color:#c0c0c0;"><?= t(clean($p['delivery_ar']),clean($p['delivery_en'])) ?></p>
      </div>
    </div>

    <!-- RIGHT: Order box -->
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

      <a href="checkout.php?slug=<?= urlencode($slug) ?>" class="btn btn-primary w-full" style="justify-content:center;margin-bottom:12px;font-size:16px;padding:14px 28px;">
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

  <!-- ===== RATINGS & DOWNLOAD SECTION ===== -->
  <div id="ratings" style="margin-top:40px;">

    <?php if($dlInfo): ?>
    <!-- قسم التحميل — يظهر فقط بعد اجتياز الكابتشا -->
    <div class="admin-card" style="border-color:rgba(0,240,255,.35);background:rgba(0,240,255,.03);margin-bottom:24px;text-align:center;" id="download-ready">
      <div style="margin-bottom:16px;">
        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" style="display:block;margin:0 auto 10px;">
          <circle cx="28" cy="28" r="28" fill="rgba(0,240,255,0.1)"/>
          <path d="M28 18V34M28 34L22 28M28 34L34 28" stroke="#00f0ff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M18 38H38" stroke="#00f0ff" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
        <h3 style="font-size:20px;font-weight:800;color:var(--cyan);"><?= t('ملفك جاهز للتحميل!','Your File is Ready!') ?></h3>
        <p style="color:var(--dim);font-size:14px;margin-top:8px;">
          <?= t('تم التحقق بنجاح. انقر الزر أدناه لتحميل ملفك.','Verification successful. Click below to download your file.') ?>
        </p>
      </div>
      <?php if(!empty($dlInfo['download_url'])): ?>
      <a href="download.php?token=<?= urlencode($dlToken) ?>" class="btn btn-primary" style="font-size:16px;padding:14px 36px;box-shadow:0 0 30px rgba(0,240,255,.3);">
        <i class="fa-solid fa-arrow-down-to-line"></i>
        <?= t('تحميل الآن','Download Now') ?>
      </a>
      <?php else: ?>
      <div class="alert alert-info" style="justify-content:center;margin-top:4px;">
        <i class="fa-solid fa-envelope"></i>
        <?= t('سيُرسل الملف إلى بريدك الإلكتروني قريباً بعد تأكيد الأدمن','The file will be sent to your email after admin verification') ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ===== تقييمات العملاء ===== -->
    <div class="admin-card" style="border-color:rgba(255,184,0,.15);margin-bottom:24px;">
      <h3><i class="fa-solid fa-star" style="color:var(--gold);"></i> <?= t('تقييمات العملاء','Customer Reviews') ?></h3>

      <div style="display:flex;align-items:center;gap:24px;padding:16px 0;border-bottom:1px solid var(--border);margin-bottom:20px;flex-wrap:wrap;">
        <div style="text-align:center;">
          <div style="font-size:52px;font-weight:900;color:var(--gold);line-height:1;"><?= number_format($p['rating'],1) ?></div>
          <div style="display:flex;gap:4px;justify-content:center;margin:6px 0;">
            <?php for($i=0;$i<5;$i++): ?>
              <i class="fa-solid fa-star" style="color:<?= $i < floor($p['rating']) ? 'var(--gold)' : ($i < $p['rating'] ? '#ffb800aa' : 'var(--border)') ?>;font-size:16px;"></i>
            <?php endfor; ?>
          </div>
          <div style="font-size:13px;color:var(--dim);"><?= number_format($p['sales']) ?> <?= t('تقييم','reviews') ?></div>
        </div>
        <div style="flex:1;min-width:160px;">
          <?php
          $bars = [5=>85, 4=>10, 3=>3, 2=>1, 1=>1];
          foreach($bars as $star => $pct): ?>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <span style="font-size:12px;color:var(--dim);min-width:14px;"><?= $star ?></span>
            <i class="fa-solid fa-star" style="color:var(--gold);font-size:11px;"></i>
            <div style="flex:1;height:6px;background:var(--border);border-radius:10px;overflow:hidden;">
              <div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,var(--gold),#ff9500);border-radius:10px;"></div>
            </div>
            <span style="font-size:12px;color:var(--dim);min-width:28px;"><?= $pct ?>%</span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- تعليقات عملاء حقيقية من قاعدة البيانات -->
      <?php if(!empty($reviews)): ?>
        <?php foreach($reviews as $rv): ?>
        <div style="padding:16px 0;border-bottom:1px solid rgba(42,42,63,.5);">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;flex-wrap:wrap;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--cyan),var(--purple));display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:var(--bg);">
                <?= strtoupper(mb_substr($rv['name'],0,1)) ?>
              </div>
              <div>
                <div style="font-size:14px;font-weight:600;"><?= clean($rv['name']) ?></div>
                <div style="font-size:12px;color:var(--dim);"><?= substr($rv['created_at'],0,10) ?></div>
              </div>
            </div>
            <div style="display:flex;gap:3px;">
              <?php for($i=0;$i<5;$i++): ?>
                <i class="fa-solid fa-star" style="color:<?= $i<(int)$rv['rating']?'var(--gold)':'var(--border)' ?>;font-size:13px;"></i>
              <?php endfor; ?>
            </div>
          </div>
          <p style="font-size:14px;color:#c0c0c0;line-height:1.7;"><?= nl2br(clean($rv['text'])) ?></p>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="color:var(--dim);font-size:14px;text-align:center;padding:16px 0;"><?= t('لا توجد تقييمات بعد — كن أول من يقيّم هذا المنتج!','No reviews yet — be the first to review this product!') ?></p>
      <?php endif; ?>
    </div>

    <!-- ===== نموذج إضافة تقييم ===== -->
    <div class="admin-card" style="border-color:rgba(0,240,255,.15);">
      <h3><i class="fa-solid fa-pen-to-square" style="color:var(--cyan);"></i> <?= t('أضف تقييمك','Add Your Review') ?></h3>

      <?php if($reviewOk): ?>
      <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $reviewMsg ?></div>
      <?php else: ?>
        <?php if($reviewErr): ?>
        <div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> <?= $reviewErr ?></div>
        <?php endif; ?>
        <form method="POST" class="admin-form" style="margin-top:16px;">
          <input type="hidden" name="slug" value="<?= clean($slug) ?>">
          <?php csrfField(); ?>

          <div class="form-group">
            <label><i class="fa-solid fa-user"></i> <?= t('الاسم *','Name *') ?></label>
            <input type="text" name="rv_name" required minlength="2" maxlength="100"
              placeholder="<?= t('اسمك أو اسم مستعار...','Your name or alias...') ?>"
              value="<?= clean($_POST['rv_name']??'') ?>">
          </div>

          <div class="form-group">
            <label><i class="fa-solid fa-star" style="color:var(--gold);"></i> <?= t('التقييم *','Rating *') ?></label>
            <div class="star-picker" id="starPicker">
              <?php for($i=1;$i<=5;$i++): ?>
              <i class="fa-solid fa-star star-pick" data-val="<?= $i ?>" style="font-size:26px;cursor:pointer;color:var(--border);transition:color .15s;"></i>
              <?php endfor; ?>
            </div>
            <input type="hidden" name="rv_rating" id="rvRating" value="<?= (int)($_POST['rv_rating']??5) ?>">
          </div>

          <div class="form-group">
            <label><i class="fa-solid fa-comment"></i> <?= t('تقييمك *','Your Review *') ?></label>
            <textarea name="rv_text" required minlength="15" maxlength="2000" rows="4"
              placeholder="<?= t('اكتب تجربتك مع المنتج...','Write your experience with this product...') ?>"><?= clean($_POST['rv_text']??'') ?></textarea>
            <div style="font-size:12px;color:var(--dim);margin-top:5px;"><?= t('التقييم يظهر بعد مراجعة الأدمن','Review appears after admin approval') ?></div>
          </div>

          <button type="submit" name="do_review" class="btn btn-primary" style="font-size:15px;padding:12px 28px;">
            <i class="fa-solid fa-paper-plane"></i> <?= t('إرسال التقييم','Submit Review') ?>
          </button>
        </form>
      <?php endif; ?>
    </div>

  </div>

</main>

<script>
// Star picker for review form
(function(){
  var stars = document.querySelectorAll('.star-pick');
  var input = document.getElementById('rvRating');
  if(!stars.length) return;
  var cur = parseInt(input ? input.value : 5) || 5;
  function paint(n){
    stars.forEach(function(s,i){ s.style.color = i < n ? 'var(--gold)' : 'var(--border)'; });
  }
  paint(cur);
  stars.forEach(function(s){
    s.addEventListener('mouseover', function(){ paint(parseInt(this.dataset.val)); });
    s.addEventListener('mouseout',  function(){ paint(cur); });
    s.addEventListener('click',     function(){ cur = parseInt(this.dataset.val); if(input) input.value = cur; paint(cur); });
  });
})();
</script>

<style>
.product-layout{display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start;}
@media(max-width:768px){
  .product-layout{grid-template-columns:1fr;}
  .order-box{position:static!important;}
}
.star-picker{display:flex;gap:8px;margin-bottom:8px;}
.star-pick:hover{transform:scale(1.15);}
</style>

<?php require_once __DIR__.'/footer.php'; ?>
