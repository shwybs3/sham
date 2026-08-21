<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$currentUser = getCurrentSiteUser($pdo);
$messages    = $currentUser ? getChatMessages($pdo, 60) : [];
$pageTitle   = 'المجموعة العامة';
require __DIR__ . '/includes/page-header.php';
?>
<main class="page narrow" style="padding-top:28px;">
  <h2 style="font-family:var(--disp); font-size:20px; margin:0 0 18px; display:flex; align-items:center; gap:10px;">
    <?= icon('chat', 20) ?> المجموعة العامة
    <span style="font-size:13px; color:var(--ink-faint); font-weight:400;">الجميع يستطيع المشاركة</span>
  </h2>

  <?php if (!$currentUser): ?>
  <div class="chat-login-prompt">
    <?= icon('chat', 40) ?>
    <h2>انضم للمجموعة</h2>
    <p>سجّل دخولك بحساب Google للمشاركة في النقاشات</p>
    <a href="login.php?redirect=community.php" class="btn btn-google">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
      </svg>
      الدخول بـ Google
    </a>
  </div>

  <?php else: ?>
  <div class="chat-container">
    <div class="chat-messages" id="chat-messages">
      <?php foreach ($messages as $msg):
        $isMine = $msg['user_id'] === $currentUser['id'];
        $initial = mb_substr($msg['display_name'], 0, 1, 'UTF-8');
      ?>
      <div class="chat-msg <?= $isMine ? 'mine' : '' ?>" data-id="<?= $msg['id'] ?>">
        <div class="chat-avatar">
          <?php if ($msg['avatar_url']): ?>
          <img src="<?= h($msg['avatar_url']) ?>" alt="<?= h($msg['display_name']) ?>" loading="lazy">
          <?php else: ?>
          <?= h($initial) ?>
          <?php endif; ?>
        </div>
        <div class="chat-bubble">
          <?php if (!$isMine): ?>
          <div class="chat-sender"><?= h($msg['username'] ?: $msg['display_name']) ?></div>
          <?php endif; ?>
          <div class="chat-text"><?= nl2br(h($msg['message'])) ?></div>
          <div class="chat-time"><?= date('H:i', strtotime($msg['created_at'])) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (!$messages): ?>
      <div style="text-align:center; padding:40px; color:var(--ink-faint);">
        <?= icon('chat', 32) ?>
        <p>لا توجد رسائل بعد. كن أول من يكتب!</p>
      </div>
      <?php endif; ?>
    </div>

    <div class="chat-input-row">
      <input type="text" id="chat-input" placeholder="اكتب رسالتك..." maxlength="500" autocomplete="off">
      <button class="btn btn-primary" id="chat-send"><?= icon('send', 18) ?></button>
    </div>
    <div style="font-size:12px; color:var(--ink-faint); margin-top:8px; display:flex; justify-content:space-between;">
      <span>مرحباً <?= h($currentUser['username'] ?: $currentUser['display_name']) ?></span>
      <a href="profile.php" style="color:var(--brand);"><?= icon('user', 13) ?> ملفي</a>
    </div>
  </div>
  <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/page-footer.php'; ?>

<?php if ($currentUser): ?>
<script>
(function(){
  const container = document.getElementById('chat-messages');
  const input     = document.getElementById('chat-input');
  const sendBtn   = document.getElementById('chat-send');
  const currentUserId = <?= (int)$currentUser['id'] ?>;
  const me = <?= json_encode($currentUser['username'] ?: $currentUser['display_name']) ?>;
  let lastId = <?= $messages ? max(array_column($messages, 'id')) : 0 ?>;

  container.scrollTop = container.scrollHeight;

  function escH(s){ return s.replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

  function addMsg(msg, isMine){
    const el = document.createElement('div');
    el.className = 'chat-msg' + (isMine ? ' mine' : '');
    el.dataset.id = msg.id;
    const avatar = msg.avatar_url
      ? `<img src="${escH(msg.avatar_url)}" loading="lazy" style="width:100%;height:100%;object-fit:cover;">`
      : escH((msg.display_name||'?').substring(0,1));
    el.innerHTML = `
      <div class="chat-avatar">${avatar}</div>
      <div class="chat-bubble">
        ${!isMine ? `<div class="chat-sender">${escH(msg.username||msg.display_name)}</div>` : ''}
        <div class="chat-text">${escH(msg.message).replace(/\n/g,'<br>')}</div>
        <div class="chat-time">${msg.time||'الآن'}</div>
      </div>`;
    container.appendChild(el);
    container.scrollTop = container.scrollHeight;
  }

  function send(){
    const text = input.value.trim();
    if (!text) return;
    input.value = '';
    sendBtn.disabled = true;
    fetch('api/chat.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({message: text})
    }).then(r=>r.json()).then(d=>{
      sendBtn.disabled = false;
      if (d.ok) { addMsg({...d.msg, display_name:me, username:me}, true); lastId = d.msg.id; }
    }).catch(()=>{ sendBtn.disabled=false; });
  }

  sendBtn.addEventListener('click', send);
  input.addEventListener('keydown', e=>{ if(e.key==='Enter'&&!e.shiftKey){ e.preventDefault(); send(); } });

  // استقبال الرسائل الجديدة كل 4 ثوانٍ
  setInterval(()=>{
    fetch('api/chat.php?after='+lastId)
      .then(r=>r.json()).then(d=>{
        if (!d.messages) return;
        d.messages.forEach(msg=>{
          if (msg.user_id !== currentUserId) { addMsg(msg, false); lastId = Math.max(lastId, msg.id); }
        });
      }).catch(()=>{});
  }, 4000);
})();
</script>
<?php endif; ?>
