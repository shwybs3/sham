<?php
require __DIR__ . '/config.php';

$cats = [];
foreach ($pdo->query("SELECT * FROM packages WHERE active=1 ORDER BY category, sort_order, id") as $p) {
    $cats[$p['category']][] = $p;
}
$title = setting('site_title');
$desc  = setting('site_desc');

/* أيقونة لكل فئة — لمسة بصرية للفيديو */
function cat_icon($c) {
    if (mb_strpos($c, 'انستقرام') !== false) return '📸';
    if (mb_strpos($c, 'فيسبوك') !== false) return '👤';
    if (mb_strpos($c, 'يوتيوب') !== false) return '▶';
    if (mb_strpos($c, 'تيليجرام') !== false) return '✈';
    if (mb_strpos($c, 'أمن') !== false || mb_strpos($c, 'سيبر') !== false) return '☠';
    return '⚡';
}
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> | متابعين، تيليجرام بريميوم وأدوات أمن سيبراني</title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="robots" content="index,follow">
<meta name="theme-color" content="#e10600">
<link rel="canonical" href="<?= e(site_url()) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e(site_url()) ?>">
<style>
:root{--bg:#0a0a0c;--bg2:#111114;--card:#141417;--line:#2a1416;--red:#ff0033;--red2:#e10600;--red-dim:#8a0018;--txt:#f2f2f4;--muted:#8a8a92;--glow:0 0 12px rgba(255,0,51,.55)}
*{box-sizing:border-box}
html,body{margin:0}
body{font-family:'Courier New',ui-monospace,'Cascadia Code',monospace;background:var(--bg);color:var(--txt);line-height:1.8;overflow-x:hidden}
a{color:inherit;text-decoration:none}
/* خطوط المسح (scanlines) لأجواء الهاكر */
body::before{content:"";position:fixed;inset:0;pointer-events:none;z-index:2;background:repeating-linear-gradient(0deg,rgba(0,0,0,0) 0,rgba(0,0,0,0) 2px,rgba(255,0,51,.025) 3px,rgba(0,0,0,0) 4px)}
/* توهج خلفي أحمر */
body::after{content:"";position:fixed;top:-30%;left:50%;transform:translateX(-50%);width:900px;height:600px;max-width:120vw;background:radial-gradient(circle,rgba(225,6,0,.22),transparent 60%);pointer-events:none;z-index:0}
.wrap{position:relative;z-index:3}

/* شريط علوي طرفية */
.termbar{background:#000;border-bottom:1px solid var(--line);font-size:12px;color:var(--red);padding:6px 14px;letter-spacing:1px;display:flex;gap:8px;align-items:center}
.termbar .dot{width:9px;height:9px;border-radius:50%;background:var(--red);box-shadow:var(--glow);animation:blink 1.4s infinite}
@keyframes blink{50%{opacity:.25}}

header.hero{padding:60px 18px 44px;text-align:center;position:relative}
.skull{font-size:52px;filter:drop-shadow(0 0 14px rgba(255,0,51,.7));animation:float 3s ease-in-out infinite}
@keyframes float{50%{transform:translateY(-8px)}}
header.hero h1{font-size:30px;margin:14px 0 8px;font-weight:800;letter-spacing:1px;text-shadow:var(--glow);position:relative;display:inline-block}
/* تأثير الغليتش على العنوان */
.glitch{position:relative}
.glitch::before,.glitch::after{content:attr(data-text);position:absolute;inset:0;overflow:hidden}
.glitch::before{color:#00e5ff;transform:translate(-2px,0);clip-path:inset(0 0 55% 0);animation:g1 2.3s infinite linear alternate}
.glitch::after{color:var(--red);transform:translate(2px,0);clip-path:inset(55% 0 0 0);animation:g2 1.9s infinite linear alternate}
@keyframes g1{0%,90%,100%{transform:translate(-2px,0)}45%{transform:translate(2px,-1px)}}
@keyframes g2{0%,90%,100%{transform:translate(2px,0)}45%{transform:translate(-2px,1px)}}
header.hero p{color:var(--muted);max-width:600px;margin:0 auto;font-size:14px}
.badges{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:22px}
.badge{background:#000;border:1px solid var(--red-dim);border-radius:4px;padding:7px 14px;font-size:12px;color:var(--red);box-shadow:inset 0 0 8px rgba(255,0,51,.15)}
.cta{margin-top:26px}
.cta a{display:inline-block;background:var(--red2);color:#fff;font-weight:800;padding:13px 30px;border-radius:4px;letter-spacing:1px;box-shadow:var(--glow);text-transform:uppercase;font-size:14px;transition:.2s}
.cta a:hover{background:#fff;color:#000;box-shadow:0 0 20px rgba(255,255,255,.5)}

main{max-width:1000px;margin:0 auto;padding:10px 16px 70px}
h2.catname{font-size:19px;margin:38px 0 16px;padding:8px 14px;border-inline-start:4px solid var(--red);background:linear-gradient(90deg,rgba(255,0,51,.10),transparent);letter-spacing:.5px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}
.card{background:var(--card);border:1px solid var(--line);border-radius:8px;padding:18px;display:flex;flex-direction:column;position:relative;transition:.2s}
.card:hover{border-color:var(--red);box-shadow:var(--glow);transform:translateY(-3px)}
.card::before{content:">_";position:absolute;top:12px;left:14px;color:var(--red-dim);font-size:12px}
.card h3{font-size:15px;margin:0 0 6px;padding-top:6px}
.card p{color:var(--muted);font-size:12.5px;margin:0 0 14px;flex:1}
.price{font-size:22px;color:var(--red);font-weight:800;margin-bottom:12px;text-shadow:var(--glow)}
.price small{font-size:12px;color:var(--muted);text-shadow:none}
.buy{background:transparent;color:var(--red);border:1px solid var(--red);border-radius:4px;padding:10px;font-weight:800;cursor:pointer;font-size:13px;letter-spacing:1px;font-family:inherit;transition:.15s}
.buy:hover{background:var(--red2);color:#fff;box-shadow:var(--glow)}
footer{text-align:center;color:var(--muted);font-size:12px;padding:34px 16px;border-top:1px solid var(--line);background:#000}
footer a{color:var(--red)}

/* المودال */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.82);z-index:50;align-items:center;justify-content:center;padding:16px}
.modal-bg.open{display:flex}
.modal{background:var(--bg2);border:1px solid var(--red);border-radius:8px;max-width:410px;width:100%;padding:24px;box-shadow:0 0 40px rgba(255,0,51,.3)}
.modal h3{margin:0 0 4px;font-size:16px;color:var(--red)}
.modal .sub{color:var(--muted);font-size:12px;margin:0 0 12px}
.modal label{display:block;font-size:12px;color:var(--muted);margin:11px 0 4px}
.modal input,.modal select{width:100%;padding:10px;border-radius:4px;border:1px solid var(--line);background:#000;color:var(--txt);font-size:13.5px;font-family:inherit}
.modal input:focus,.modal select:focus{outline:none;border-color:var(--red);box-shadow:var(--glow)}
.modal .row{display:flex;gap:8px;margin-top:18px}
.modal button{flex:1;padding:11px;border:0;border-radius:4px;font-weight:800;cursor:pointer;font-size:13px;font-family:inherit;letter-spacing:1px}
.modal .cancel{background:#222;color:var(--txt)}
.modal .submit{background:var(--red2);color:#fff;box-shadow:var(--glow)}

/* الشات */
#chatBtn{position:fixed;bottom:18px;left:18px;background:var(--red2);color:#fff;border:0;width:56px;height:56px;border-radius:50%;font-size:24px;cursor:pointer;box-shadow:var(--glow);z-index:60;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 10px rgba(255,0,51,.5)}50%{box-shadow:0 0 22px rgba(255,0,51,.9)}}
#chatBox{display:none;position:fixed;bottom:84px;left:18px;width:320px;max-width:92vw;height:430px;background:var(--bg2);border:1px solid var(--red);border-radius:10px;z-index:60;flex-direction:column;overflow:hidden;box-shadow:0 0 40px rgba(255,0,51,.35)}
#chatBox.open{display:flex}
#chatHead{padding:11px 14px;background:#000;color:var(--red);font-size:13px;font-weight:800;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--line)}
#chatMsgs{flex:1;overflow-y:auto;padding:12px;font-size:12.5px;display:flex;flex-direction:column;gap:9px}
.msg{padding:8px 11px;border-radius:8px;max-width:85%}
.msg.me{background:var(--red2);color:#fff;align-self:flex-start}
.msg.bot{background:#000;align-self:flex-end;color:var(--txt);border:1px solid var(--line)}
#chatForm{display:flex;border-top:1px solid var(--line);background:#000}
#chatForm input{flex:1;border:0;background:transparent;color:var(--txt);padding:11px;font-size:13px;font-family:inherit}
#chatForm input:focus{outline:none}
#chatForm button{border:0;background:var(--red2);color:#fff;padding:0 16px;cursor:pointer;font-weight:800}
@media(max-width:480px){header.hero h1{font-size:24px}.skull{font-size:44px}}
</style>
</head>
<body>
<div class="wrap">
<div class="termbar"><span class="dot"></span> root@<?= e(parse_url(site_url(), PHP_URL_HOST) ?: 'yassota') ?>:~# ./launch --services --secure</div>

<header class="hero">
  <div class="skull">☠</div>
  <h1 class="glitch" data-text="<?= e($title) ?>"><?= e($title) ?></h1>
  <p><?= e($desc) ?></p>
  <div class="badges">
    <span class="badge">[✓] دفع مشفّر بالعملات الرقمية</span>
    <span class="badge">[✓] تسليم فوري</span>
    <span class="badge">[✓] بدون كلمة مرور حسابك</span>
  </div>
  <div class="cta"><a href="#catalog">تصفّح الخدمات ▼</a></div>
</header>

<main id="catalog">
<?php foreach ($cats as $cat => $items): ?>
  <h2 class="catname"><?= cat_icon($cat) ?> <?= e($cat) ?></h2>
  <div class="grid">
    <?php foreach ($items as $p): ?>
    <div class="card">
      <h3><?= e($p['name']) ?></h3>
      <p><?= e($p['description']) ?></p>
      <div class="price">$<?= number_format((float)$p['price_usd'], 2) ?> <small>/ وحدة</small></div>
      <button class="buy" onclick="openOrder(<?= (int)$p['id'] ?>,'<?= e(addslashes($p['name'])) ?>',<?= (float)$p['price_usd'] ?>)">&gt;_ اطلب الآن</button>
    </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
<?php if (!$cats): ?><p style="color:var(--muted);text-align:center;margin-top:40px">لا توجد باقات بعد — أضفها من لوحة الإدارة.</p><?php endif; ?>
</main>

<footer>
  <?php if (setting('contact')): ?><p style="color:var(--red)">// للتواصل: <?= e(setting('contact')) ?></p><?php endif; ?>
  <p>© <?= date('Y') ?> <?= e($title) ?> — <a href="admin.php">[ لوحة الإدارة ]</a></p>
  <p style="font-size:10.5px;opacity:.5">الخدمات لأغراض التسويق الرقمي المشروع فقط.</p>
</footer>
</div>

<div class="modal-bg" id="orderBg">
  <form class="modal" id="orderForm" method="post" action="checkout.php">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="package_id" id="f_pkg">
    <h3 id="f_title">&gt;_ الطلب</h3>
    <p class="sub">أدخل بيانات التنفيذ ثم اختر عملة الدفع.</p>
    <label>الكمية</label>
    <input type="number" name="quantity" id="f_qty" value="1" min="1" max="20">
    <label>الرابط / اسم المستخدم المطلوب</label>
    <input type="text" name="target_info" placeholder="instagram.com/username" required>
    <label>وسيلة التواصل (تيليجرام / واتساب / بريد)</label>
    <input type="text" name="contact" placeholder="@username أو 9639xxxxxxx" required>
    <label>الدفع بعملة</label>
    <select name="currency" required>
      <option value="">— اختر العملة —</option>
      <?php foreach (NP_CURRENCIES as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?>
    </select>
    <div class="row">
      <button type="button" class="cancel" onclick="closeOrder()">إلغاء</button>
      <button type="submit" class="submit">متابعة الدفع ←</button>
    </div>
  </form>
</div>

<button id="chatBtn" onclick="toggleChat()">💬</button>
<div id="chatBox">
  <div id="chatHead"><span>&gt;_ المساعد الذكي</span><span style="cursor:pointer" onclick="toggleChat()">✕</span></div>
  <div id="chatMsgs"><div class="msg bot">أهلاً 👋 اسألني عن الخدمات، الأسعار، أو طريقة الدفع.</div></div>
  <form id="chatForm">
    <input id="chatInput" placeholder="اكتب رسالتك..." autocomplete="off">
    <button type="submit">▶</button>
  </form>
</div>

<script>
function openOrder(id, name, price) {
  document.getElementById('f_pkg').value = id;
  document.getElementById('f_title').textContent = '>_ ' + name + ' — $' + price.toFixed(2) + ' / وحدة';
  document.getElementById('orderBg').classList.add('open');
}
function closeOrder() { document.getElementById('orderBg').classList.remove('open'); }
document.getElementById('orderBg').addEventListener('click', function (e) { if (e.target === this) closeOrder(); });

function toggleChat() { document.getElementById('chatBox').classList.toggle('open'); }
document.getElementById('chatForm').addEventListener('submit', function (ev) {
  ev.preventDefault();
  var input = document.getElementById('chatInput');
  var text = input.value.trim();
  if (!text) return;
  var msgs = document.getElementById('chatMsgs');
  msgs.insertAdjacentHTML('beforeend', '<div class="msg me"></div>');
  msgs.lastElementChild.textContent = text;
  input.value = '';
  msgs.scrollTop = msgs.scrollHeight;
  fetch('chat.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'message=' + encodeURIComponent(text) })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      msgs.insertAdjacentHTML('beforeend', '<div class="msg bot"></div>');
      msgs.lastElementChild.textContent = d.reply || d.error || '...';
      msgs.scrollTop = msgs.scrollHeight;
    })
    .catch(function () {
      msgs.insertAdjacentHTML('beforeend', '<div class="msg bot">تعذر الاتصال، حاول مرة أخرى.</div>');
      msgs.scrollTop = msgs.scrollHeight;
    });
});
</script>
</body>
</html>
