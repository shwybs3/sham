<?php
require __DIR__ . '/config.php';

$cats = [];
foreach ($pdo->query("SELECT * FROM packages WHERE active=1 ORDER BY category, sort_order, id") as $p) {
    $cats[$p['category']][] = $p;
}
$title = setting('site_title');
$desc  = setting('site_desc');
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> | متابعين، تيليجرام بريميوم وأدوات أمن سيبراني</title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="robots" content="index,follow">
<link rel="canonical" href="<?= e(site_url()) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e(site_url()) ?>">
<style>
:root{--bg:#0b1120;--card:#141c30;--line:#243149;--txt:#e5edf7;--muted:#8ea0bd;--brand:#22c55e;--brand2:#38bdf8}
*{box-sizing:border-box}
body{margin:0;font-family:Tahoma,'Segoe UI',Arial,sans-serif;background:var(--bg);color:var(--txt);line-height:1.8}
a{color:inherit}
header.hero{padding:56px 18px 40px;text-align:center;background:radial-gradient(circle at 50% -20%,#1e3a5f,transparent 60%)}
header.hero h1{font-size:26px;margin:0 0 10px}
header.hero p{color:var(--muted);max-width:560px;margin:0 auto;font-size:14.5px}
.badges{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:18px}
.badge{background:var(--card);border:1px solid var(--line);border-radius:30px;padding:6px 14px;font-size:12.5px;color:var(--muted)}
main{max-width:980px;margin:0 auto;padding:10px 16px 60px}
h2.catname{font-size:18px;margin:34px 0 14px;padding-inline-start:12px;border-inline-start:4px solid var(--brand)}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px}
.card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:16px;display:flex;flex-direction:column}
.card h3{font-size:15px;margin:0 0 6px}
.card p{color:var(--muted);font-size:12.5px;margin:0 0 12px;flex:1}
.price{font-size:18px;color:var(--brand2);font-weight:bold;margin-bottom:10px}
.buy{background:var(--brand);color:#052e16;border:0;border-radius:9px;padding:9px;font-weight:bold;cursor:pointer;font-size:13.5px}
.buy:hover{filter:brightness(1.08)}
footer{text-align:center;color:var(--muted);font-size:12px;padding:30px 16px;border-top:1px solid var(--line)}
/* modal */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:50;align-items:center;justify-content:center;padding:16px}
.modal-bg.open{display:flex}
.modal{background:var(--card);border:1px solid var(--line);border-radius:14px;max-width:400px;width:100%;padding:22px}
.modal h3{margin:0 0 14px;font-size:16px}
.modal label{display:block;font-size:12.5px;color:var(--muted);margin:10px 0 4px}
.modal input,.modal select{width:100%;padding:9px 10px;border-radius:8px;border:1px solid var(--line);background:var(--bg);color:var(--txt);font-size:13.5px}
.modal .row{display:flex;gap:8px;margin-top:16px}
.modal button{flex:1;padding:10px;border:0;border-radius:8px;font-weight:bold;cursor:pointer;font-size:13.5px}
.modal .cancel{background:#243149;color:var(--txt)}
.modal .submit{background:var(--brand);color:#052e16}
.err{background:#450a0a;border:1px solid #991b1b;color:#fecaca;padding:8px 10px;border-radius:8px;font-size:12.5px;margin-top:10px}
/* chat */
#chatBtn{position:fixed;bottom:18px;left:18px;background:var(--brand2);color:#052e2e;border:0;width:54px;height:54px;border-radius:50%;font-size:22px;cursor:pointer;box-shadow:0 6px 20px rgba(0,0,0,.4);z-index:60}
#chatBox{display:none;position:fixed;bottom:82px;left:18px;width:310px;max-width:92vw;height:420px;background:var(--card);border:1px solid var(--line);border-radius:14px;z-index:60;flex-direction:column;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,.5)}
#chatBox.open{display:flex}
#chatHead{padding:10px 14px;background:#0f1a30;font-size:13.5px;font-weight:bold;display:flex;justify-content:space-between;align-items:center}
#chatMsgs{flex:1;overflow-y:auto;padding:10px;font-size:12.5px;display:flex;flex-direction:column;gap:8px}
.msg{padding:8px 10px;border-radius:10px;max-width:85%}
.msg.me{background:var(--brand2);color:#052e2e;align-self:flex-start}
.msg.bot{background:#0f1a30;align-self:flex-end;color:var(--txt)}
#chatForm{display:flex;border-top:1px solid var(--line)}
#chatForm input{flex:1;border:0;background:transparent;color:var(--txt);padding:10px;font-size:13px}
#chatForm button{border:0;background:var(--brand2);color:#052e2e;padding:0 14px;cursor:pointer;font-weight:bold}
</style>
</head>
<body>
<header class="hero">
  <h1>🛒 <?= e($title) ?></h1>
  <p><?= e($desc) ?></p>
  <div class="badges">
    <span class="badge">✅ دفع آمن بالعملات الرقمية</span>
    <span class="badge">⚡ تسليم سريع</span>
    <span class="badge">🔒 بدون كلمة مرور حسابك</span>
  </div>
</header>

<main>
<?php foreach ($cats as $cat => $items): ?>
  <h2 class="catname"><?= e($cat) ?></h2>
  <div class="grid">
    <?php foreach ($items as $p): ?>
    <div class="card">
      <h3><?= e($p['name']) ?></h3>
      <p><?= e($p['description']) ?></p>
      <div class="price">$<?= number_format((float)$p['price_usd'], 2) ?></div>
      <button class="buy" onclick="openOrder(<?= (int)$p['id'] ?>,'<?= e(addslashes($p['name'])) ?>',<?= (float)$p['price_usd'] ?>)">اطلب الآن</button>
    </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
<?php if (!$cats): ?><p style="color:var(--muted);text-align:center;margin-top:40px">لا توجد باقات بعد — أضفها من لوحة الإدارة.</p><?php endif; ?>
</main>

<footer>
  <?php if (setting('contact')): ?><p>للتواصل: <?= e(setting('contact')) ?></p><?php endif; ?>
  © <?= date('Y') ?> <?= e($title) ?> — <a href="admin.php">لوحة الإدارة</a>
</footer>

<div class="modal-bg" id="orderBg">
  <form class="modal" id="orderForm" method="post" action="checkout.php">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="package_id" id="f_pkg">
    <h3 id="f_title">الطلب</h3>
    <label>الكمية</label>
    <input type="number" name="quantity" id="f_qty" value="1" min="1" max="20">
    <label>الرابط / اسم المستخدم المطلوب تنفيذ الخدمة عليه</label>
    <input type="text" name="target_info" placeholder="مثال: instagram.com/username" required>
    <label>وسيلة التواصل (تيليجرام / واتساب / بريد)</label>
    <input type="text" name="contact" placeholder="@username أو 9639xxxxxxx" required>
    <label>الدفع بعملة</label>
    <select name="currency" required>
      <option value="">— اختر العملة —</option>
      <?php foreach (NP_CURRENCIES as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?>
    </select>
    <div class="row">
      <button type="button" class="cancel" onclick="closeOrder()">إلغاء</button>
      <button type="submit" class="submit">متابعة الدفع</button>
    </div>
  </form>
</div>

<button id="chatBtn" onclick="toggleChat()">💬</button>
<div id="chatBox">
  <div id="chatHead"><span>المساعد الذكي</span><span style="cursor:pointer" onclick="toggleChat()">✕</span></div>
  <div id="chatMsgs"><div class="msg bot">مرحباً 👋 اسألني عن الباقات، الأسعار، أو طريقة الدفع.</div></div>
  <form id="chatForm">
    <input id="chatInput" placeholder="اكتب رسالتك..." autocomplete="off">
    <button type="submit">إرسال</button>
  </form>
</div>

<script>
function openOrder(id, name, price) {
  document.getElementById('f_pkg').value = id;
  document.getElementById('f_title').textContent = name + ' — $' + price.toFixed(2) + ' / وحدة';
  document.getElementById('orderBg').classList.add('open');
}
function closeOrder() { document.getElementById('orderBg').classList.remove('open'); }

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
    });
});
</script>
</body>
</html>
