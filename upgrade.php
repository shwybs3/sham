<?php
/* upgrade.php — Payment page with NOWPayments crypto widget */
require_once __DIR__ . '/config.php';

$tier  = $_GET['tier'] ?? 'plus';
$tiers = [
  'plus'    => ['name'=>'Plus',    'price'=>5,  'ar'=>'بلس',    'color'=>'#2563eb','features'=>['دعم أولوية','بدون إعلانات','100 طلب يومياً بالذكاء الاصطناعي']],
  'pro'     => ['name'=>'Pro',     'price'=>10, 'ar'=>'برو',    'color'=>'#7c3aed','features'=>['كل مزايا بلس','وصول غير محدود','جميع أدوات الذكاء الاصطناعي','نماذج GPT-4 و Claude']],
  'premium' => ['name'=>'Premium', 'price'=>20, 'ar'=>'بريميوم','color'=>'#c50000','features'=>['كل مزايا برو','أولوية قصوى','دعم مباشر على واتساب','حساب تجاري']],
];
if (!isset($tiers[$tier])) { $tier = 'plus'; }
$t = $tiers[$tier];
$pageTitle = 'ترقية الحساب — ' . $t['ar'] . ' | ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($pageTitle) ?></title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,sans-serif;background:#f4f6fb;color:#1e293b;direction:rtl;min-height:100vh}
.t-nav{background:#fff;border-bottom:1px solid #e8edf3;padding:12px 20px;display:flex;align-items:center;gap:10px;font-size:.84rem}
.t-nav a{color:#2563eb;text-decoration:none}
.hero{background:<?= h($t['color']) ?>;color:#fff;padding:32px 20px;text-align:center}
.hero h1{font-size:1.6rem;font-weight:800;margin-bottom:6px}
.hero .price{font-size:2.4rem;font-weight:900;margin:10px 0}
.hero .price span{font-size:1.1rem;font-weight:400;opacity:.8}
.container{max-width:700px;margin:0 auto;padding:24px 16px}
.card{background:#fff;border-radius:14px;box-shadow:0 1px 6px rgba(0,0,0,.07);padding:24px;margin-bottom:20px}
.card h2{font-size:1rem;font-weight:700;margin-bottom:16px;color:#1e293b}
.feature-list{list-style:none}
.feature-list li{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:.92rem}
.feature-list li:last-child{border:0}
.check{color:#16a34a;font-size:1.1rem}
.tier-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.tier-tab{padding:8px 18px;border-radius:8px;border:2px solid #e2e8f0;background:#fff;cursor:pointer;font-family:inherit;font-size:.88rem;font-weight:600;transition:all .18s;text-decoration:none;color:#64748b}
.tier-tab.active,.tier-tab:hover{border-color:<?= h($t['color']) ?>;color:<?= h($t['color']) ?>}
.tier-tab.active{background:#f0f4ff}
.payment-section{text-align:center}
.payment-section .sub{color:#64748b;font-size:.85rem;margin-bottom:16px}
.nowpay-iframe{width:100%;max-width:430px;height:700px;border:none;border-radius:12px;overflow:hidden;display:block;margin:0 auto;box-shadow:0 4px 20px rgba(0,0,0,.1)}
.pay-btn{display:inline-block;margin-top:16px;padding:12px 32px;background:<?= h($t['color']) ?>;color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:1rem}
.pay-btn:hover{opacity:.88}
.timer{background:#fff8e1;border:1px solid #fbbf24;border-radius:8px;padding:10px 16px;text-align:center;font-size:.9rem;color:#92400e;margin-bottom:16px}
.timer #countdown{font-weight:700;font-size:1.1rem}
</style>
</head>
<body>
<div class="t-nav">
  <a href="/">🏠 <?= h(SITE_NAME) ?></a>
  <span>›</span>
  <a href="/tools/">الأدوات</a>
  <span>›</span>
  <span>الترقية</span>
</div>

<div class="hero">
  <h1>ترقية إلى <?= h($t['ar']) ?></h1>
  <div class="price">$<?= $t['price'] ?> <span>/ شهرياً</span></div>
  <p style="opacity:.85;font-size:.9rem">يشمل اشتراكك جميع أدوات <?= h(SITE_NAME) ?> والذكاء الاصطناعي</p>
</div>

<div class="container">

  <div class="tier-tabs">
    <?php foreach($tiers as $k=>$ti): ?>
    <a href="?tier=<?= $k ?>" class="tier-tab <?= $k===$tier?'active':'' ?>">
      <?= h($ti['ar']) ?> — $<?= $ti['price'] ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h2>✨ ما تحصل عليه مع <?= h($t['ar']) ?></h2>
    <ul class="feature-list">
      <?php foreach($t['features'] as $f): ?>
      <li><span class="check">✓</span><?= h($f) ?></li>
      <?php endforeach; ?>
      <li><span class="check">✓</span>الوصول لجميع أدوات الذكاء الاصطناعي (مصحح، مترجم، ملخص، كاتب رسائل)</li>
      <li><span class="check">✓</span>تحديثات مستمرة بدون رسوم إضافية</li>
    </ul>
  </div>

  <div class="card">
    <h2>💳 الدفع بالعملات الرقمية</h2>
    <div class="payment-section">
      <div class="timer">
        العرض ينتهي خلال <span id="countdown">20:00</span> دقيقة
      </div>
      <p class="sub">ادفع بأمان بالعملات الرقمية — Bitcoin, USDT, ETH وأكثر من 50 عملة</p>
      <iframe
        src="https://nowpayments.io/embeds/payment-widget?iid=4337098069"
        width="410"
        height="696"
        frameborder="0"
        scrolling="no"
        class="nowpay-iframe"
        style="overflow-y:hidden">
        Can't load widget
      </iframe>
      <div style="margin-top:16px">
        <a href="https://nowpayments.io/payment/?iid=4337098069&source=button" target="_blank" rel="noreferrer noopener" class="pay-btn">
          💰 ادفع الآن عبر NOWPayments
        </a>
      </div>
    </div>
  </div>

  <div class="card" style="background:#f0fdf4;border:1px solid #bbf7d0">
    <h2 style="color:#166534">🔒 ضمان الأمان والخصوصية</h2>
    <p style="font-size:.9rem;line-height:1.7;color:#374151">
      تتم جميع معاملات الدفع عبر بوابة NOWPayments المشفرة ولا نحتفظ بأي بيانات بطاقة ائتمانية.
      دفع العملات الرقمية يمنحك أعلى مستوى من الخصوصية. بعد اكتمال الدفع يتم تفعيل اشتراكك فورياً.
    </p>
  </div>

</div>

<script>
var mins=19,secs=59,el=document.getElementById('countdown');
var iv=setInterval(function(){
  secs--;
  if(secs<0){secs=59;mins--;}
  if(mins<0){clearInterval(iv);el.textContent='00:00';return;}
  el.textContent=(mins<10?'0':'')+mins+':'+(secs<10?'0':'')+secs;
},1000);
</script>
</body>
</html>
