<?php
require __DIR__ . '/config.php';
$me = require_login();

$cv = $pdo->prepare("SELECT c.*, IF(c.user_a=?,c.user_b,c.user_a) other_id FROM conversations c WHERE c.user_a=? OR c.user_b=? ORDER BY c.last_at DESC LIMIT 40");
$cv->execute([$me['id'], $me['id'], $me['id']]);
$convs = $cv->fetchAll();
$otherIds = array_column($convs, 'other_id');
$others = [];
if ($otherIds) {
    $in = implode(',', array_fill(0, count($otherIds), '?'));
    $us = $pdo->prepare("SELECT id,username,name,avatar,verified FROM users WHERE id IN ($in)");
    $us->execute($otherIds);
    foreach ($us->fetchAll() as $u) $others[$u['id']] = $u;
}

$active = null;
if (!empty($_GET['to'])) {
    $tu = $pdo->prepare("SELECT id,username,name,avatar,verified FROM users WHERE username=? AND banned=0"); $tu->execute([$_GET['to']]);
    $active = $tu->fetch();
}

layout_top(['title' => 'الرسائل | ' . setting('site_name', 'YASSOTA'), 'noindex' => true, 'active' => 'messages']);
?>
<div class="phead"><div><h1>الرسائل</h1><p class="sub">محادثاتك الخاصة</p></div></div>

<?php if ($active): ?>
  <div class="card" style="display:flex;flex-direction:column;height:calc(100vh - 220px);min-height:420px">
    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-bottom:1px solid var(--line)">
      <a href="<?= e(url('messages')) ?>" class="ic-btn"><?= icon('x',18) ?></a>
      <a href="<?= e(user_url($active['username'])) ?>" style="display:flex;align-items:center;gap:10px"><?= avatar_html($active,38) ?><b><?= e($active['name'] ?: $active['username']) ?><?= verified($active) ?></b></a>
    </div>
    <div id="msgArea" style="flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:8px"></div>
    <form id="msgForm" style="display:flex;gap:8px;padding:10px;border-top:1px solid var(--line)">
      <input class="input" id="msgBody" placeholder="اكتب رسالة…" autocomplete="off" style="flex:1">
      <button class="btn btn-primary" type="submit"><?= icon('send',18) ?></button>
    </form>
  </div>
  <script>
  (function(){
    var to=<?= (int)$active['id'] ?>, area=document.getElementById('msgArea'), last=0;
    function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
    function render(m){var el=document.createElement('div');el.style.cssText='max-width:78%;padding:9px 13px;border-radius:16px;font-size:14px;'+(m.mine?'align-self:flex-start;background:var(--grad);color:#fff;border-bottom-inline-start-radius:5px':'align-self:flex-end;background:var(--surface-2);border-bottom-inline-end-radius:5px');el.innerHTML=esc(m.body)+'<div style="font-size:10px;opacity:.7;margin-top:2px">'+m.time+(m.mine&&m.seen?' ✓✓':'')+'</div>';area.appendChild(el);}
    function poll(){yaApi('msg_list',{to:to,after:last}).then(function(r){if(r.ok&&r.messages.length){r.messages.forEach(function(m){render(m);last=Math.max(last,m.id);});area.scrollTop=area.scrollHeight;}});}
    document.getElementById('msgForm').addEventListener('submit',function(e){e.preventDefault();var b=document.getElementById('msgBody');var t=b.value.trim();if(!t)return;b.value='';yaApi('msg_send',{to:to,body:t}).then(function(r){if(r.ok)poll();else yaToast(r.error||'تعذّر الإرسال','err');});});
    poll();setInterval(poll,4000);
  })();
  </script>
<?php elseif ($convs): ?>
  <div class="feed" style="gap:2px">
  <?php foreach ($convs as $c): $o = $others[$c['other_id']] ?? null; if (!$o) continue; ?>
    <a class="card" style="display:flex;align-items:center;gap:12px;padding:12px 14px" href="<?= e(url('messages?to=' . $o['username'])) ?>">
      <?= avatar_html($o,44) ?><div style="flex:1"><b><?= e($o['name'] ?: $o['username']) ?><?= verified($o) ?></b><div style="color:var(--faint);font-size:12.5px">@<?= e($o['username']) ?></div></div>
      <small style="color:var(--faint)"><?= e(time_ago($c['last_at'])) ?></small>
    </a>
  <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="empty"><?= icon('chat',44) ?><p>لا محادثات بعد. افتح ملف مستخدم واضغط زر الرسالة لبدء محادثة.</p></div>
<?php endif; ?>
<?php layout_bottom(); ?>
