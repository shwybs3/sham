<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = $social_pdo;
$me  = sc_user($pdo);

$plan = $_GET['plan'] ?? 'badge';
$plans = [
  'badge'   => ['name'=>'توثيق','ar'=>'شارة التوثيق ✓','price'=>BADGE_PRICE_USD,'color'=>'#e3b341','desc'=>'احصل على شارة التوثيق الزرقاء على ملفك الشخصي','features'=>['شارة ✓ على ملفك','ظهور أعلى في نتائج البحث','ميزة الاشتراكات الحصرية','دعم أولوية']],
  'plus'    => ['name'=>'بلس','ar'=>'بلس ⚡','price'=>5,'color'=>'#2f81f7','desc'=>'ميزات إضافية لتعزيز تجربتك','features'=>['بدون إعلانات','100 رسالة/يوم إضافية','أدوات AI متقدمة','وصول مبكر للميزات الجديدة']],
  'pro'     => ['name'=>'برو','ar'=>'برو 🔥','price'=>10,'color'=>'#7c3aed','desc'=>'للمبدعين والمحترفين','features'=>['كل مزايا بلس','نشر غير محدود','أدوات marketplace مميزة','إحصائيات متقدمة','خاصية البيع المباشر']],
  'premium' => ['name'=>'بريميوم','ar'=>'بريميوم 👑','price'=>20,'color'=>'#f85149','desc'=>'أقصى مستوى مع دعم مباشر','features'=>['كل مزايا برو','دعم على واتساب','حساب تجاري موثّق','أولوية قصوى في كل مكان']],
];
if (!isset($plans[$plan])) $plan = 'badge';
$p = $plans[$plan];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ترقية الحساب — <?= sc_h($p['ar']) ?> | <?= sc_h(SOCIAL_NAME) ?></title>
<link rel="icon" href="/social/assets/favicon.svg" type="image/svg+xml">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0e1117;color:#e6edf3;min-height:100vh}
.hero{background:<?= sc_h($p['color']) ?>;padding:36px 20px;text-align:center}
.hero h1{font-size:1.8rem;font-weight:900;margin-bottom:8px}
.hero .price{font-size:2.8rem;font-weight:900}
.hero .price small{font-size:1rem;font-weight:400;opacity:.8}
.nav{background:rgba(0,0,0,.2);padding:12px 20px;display:flex;align-items:center;gap:10px}
.nav a{color:rgba(255,255,255,.85);text-decoration:none;font-size:.88rem}
.container{max-width:700px;margin:0 auto;padding:24px 16px}
.card{background:#161b22;border:1px solid #30363d;border-radius:12px;padding:24px;margin-bottom:20px}
.card h2{font-size:1rem;font-weight:700;margin-bottom:16px;color:#8b949e;text-transform:uppercase;letter-spacing:.5px}
.feature{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #21262d;font-size:.92rem}
.feature:last-child{border:0}
.check{color:#3fb950;font-size:1rem}
.plan-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px}
.plan-tab{padding:8px 16px;border-radius:8px;border:2px solid #30363d;background:transparent;color:#8b949e;cursor:pointer;font-size:.85rem;font-weight:600;text-decoration:none;transition:all .15s}
.plan-tab:hover,.plan-tab.active{border-color:<?= sc_h($p['color']) ?>;color:<?= sc_h($p['color']) ?>}
.plan-tab.active{background:rgba(255,255,255,.05)}
.pay-iframe{width:100%;max-width:430px;height:700px;border:none;border-radius:12px;overflow:hidden;display:block;margin:0 auto;box-shadow:0 4px 20px rgba(0,0,0,.4)}
.pay-btn{display:inline-block;margin-top:16px;padding:13px 36px;background:<?= sc_h($p['color']) ?>;color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:1rem}
.pay-btn:hover{opacity:.88}
.timer-box{background:#1a1200;border:1px solid <?= sc_h($p['color']) ?>;border-radius:8px;padding:10px 16px;text-align:center;font-size:.88rem;color:#e3b341;margin-bottom:16px}
.timer-box #cd{font-weight:800;font-size:1.1rem}
.security-card{background:#0d1a0e;border:1px solid #1a4a1c;border-radius:12px;padding:20px;margin-bottom:20px}
.security-card h2{color:#3fb950}
</style>
</head>
<body>

<div class="hero">
  <nav class="nav" style="max-width:700px;margin:0 auto -12px;background:transparent;padding:0 0 12px">
    <a href="<?= SOCIAL_URL ?>">← <?= sc_h(SOCIAL_NAME) ?></a>
    <span style="opacity:.5">›</span>
    <span>ترقية الحساب</span>
  </nav>
  <h1>ترقية إلى <?= sc_h($p['ar']) ?></h1>
  <div class="price">$<?= $p['price'] ?> <small>/ شهرياً</small></div>
  <p style="opacity:.8;margin-top:8px;font-size:.9rem"><?= sc_h($p['desc']) ?></p>
</div>

<div class="container">
  <div class="plan-tabs">
    <?php foreach ($plans as $k=>$pl): ?>
    <a href="?plan=<?= $k ?>" class="plan-tab <?= $k===$plan?'active':'' ?>">
      <?= sc_h($pl['ar']) ?> — $<?= $pl['price'] ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h2>ما تحصل عليه</h2>
    <?php foreach ($p['features'] as $f): ?>
    <div class="feature"><span class="check">✓</span><?= sc_h($f) ?></div>
    <?php endforeach; ?>
    <div class="feature"><span class="check">✓</span>تحديثات مستمرة بدون رسوم إضافية</div>
  </div>

  <div class="card">
    <h2>الدفع بالعملات الرقمية</h2>
    <div class="timer-box">العرض ينتهي خلال <span id="cd">20:00</span></div>
    <p style="color:#8b949e;font-size:.84rem;text-align:center;margin-bottom:14px">
      ادفع بأمان — Bitcoin, USDT, ETH و+50 عملة
    </p>
    <iframe src="https://nowpayments.io/embeds/payment-widget?iid=<?= NOWPAY_IID ?>"
            width="410" height="696" frameborder="0" scrolling="no" class="pay-iframe">
      Can't load widget
    </iframe>
    <div style="text-align:center;margin-top:16px">
      <a href="https://nowpayments.io/payment/?iid=<?= NOWPAY_IID ?>&source=button" target="_blank" rel="noreferrer noopener" class="pay-btn">
        💰 ادفع الآن عبر NOWPayments
      </a>
    </div>
  </div>

  <div class="security-card">
    <h2>🔒 ضمان الأمان</h2>
    <p style="font-size:.88rem;line-height:1.7;color:#8b949e;margin-top:8px">
      جميع المعاملات تتم عبر بوابة NOWPayments المشفرة. لا نحتفظ ببيانات بطاقتك.
      يُفعَّل اشتراكك فوراً بعد تأكيد الدفع.
    </p>
  </div>
</div>

<script>
var m=19,s=59,el=document.getElementById('cd');
setInterval(function(){
  s--; if(s<0){s=59;m--;} if(m<0){el.textContent='00:00';return;}
  el.textContent=(m<10?'0':'')+m+':'+(s<10?'0':'')+s;
},1000);
</script>
</body>
</html>
