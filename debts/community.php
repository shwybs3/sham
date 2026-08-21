<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$currentUser = getCurrentSiteUser($pdo);
$pageTitle   = 'المجموعة العامة';

try {
    $messages = getChatMessages($pdo, 60);
} catch (PDOException $e) {
    $messages = [];
}

require __DIR__ . '/includes/page-header.php';
?>

<style>
.feed-wrap{
  max-width:600px; margin:0 auto; padding:16px 14px 100px;
}
.feed-header{
  display:flex; align-items:center; justify-content:space-between;
  margin-bottom:20px;
}
.feed-header h1{
  font-size:18px; font-weight:800; margin:0;
  display:flex; align-items:center; gap:8px;
}
.feed-card{
  display:flex; gap:12px; align-items:flex-start;
  padding:14px 0; border-bottom:1px solid var(--line);
  animation:fadeUp .2s var(--ease);
}
.feed-card:first-child{ border-top:1px solid var(--line); }
.feed-avatar-wrap{ flex-shrink:0; }
.feed-avatar{
  width:46px; height:46px; border-radius:50%;
  background:linear-gradient(135deg,rgba(34,197,142,.25),rgba(34,197,142,.06));
  display:flex; align-items:center; justify-content:center;
  font-size:18px; font-weight:700; color:var(--brand);
  overflow:hidden;
  border:2px solid transparent;
  background-clip:padding-box;
  box-shadow:0 0 0 2px rgba(34,197,142,.35);
  text-decoration:none;
}
.feed-avatar img{ width:100%; height:100%; object-fit:cover; border-radius:50%; }
.feed-body{ flex:1; min-width:0; }
.feed-meta{
  display:flex; align-items:baseline; gap:6px; flex-wrap:wrap;
  margin-bottom:5px; line-height:1.3;
}
.feed-name{
  font-size:14px; font-weight:700; color:var(--ink);
  text-decoration:none;
}
.feed-name:hover{ color:var(--brand); }
.feed-handle{
  font-size:13px; color:var(--ink-faint); text-decoration:none;
}
.feed-handle:hover{ color:var(--brand); }
.feed-dot{ color:var(--ink-faint); font-size:11px; }
.feed-time{ font-size:12px; color:var(--ink-faint); }
.feed-text{
  font-size:14px; line-height:1.6; color:var(--ink);
  word-break:break-word; white-space:pre-wrap;
}
.feed-mine .feed-text{ color:var(--ink); }
.feed-mine .feed-avatar{ box-shadow:0 0 0 2px var(--brand); }

/* compose */
.feed-compose{
  position:fixed; bottom:0; inset-inline:0; z-index:300;
  background:var(--bg); border-top:1px solid var(--line);
  padding:10px 14px calc(10px + env(safe-area-inset-bottom));
}
.feed-compose-inner{
  max-width:600px; margin:0 auto;
  display:flex; gap:8px; align-items:center;
}
.feed-compose-avatar{
  width:34px; height:34px; border-radius:50%; flex-shrink:0;
  background:linear-gradient(135deg,rgba(34,197,142,.2),rgba(34,197,142,.05));
  display:flex; align-items:center; justify-content:center;
  font-size:13px; font-weight:700; color:var(--brand);
  overflow:hidden;
}
.feed-compose-avatar img{ width:100%; height:100%; object-fit:cover; border-radius:50%; }
.feed-compose-input{
  flex:1; background:var(--panel); border:1px solid var(--line);
  border-radius:22px; padding:9px 16px; font-size:14px;
  color:var(--ink); outline:none; font-family:inherit;
}
.feed-compose-input:focus{ border-color:var(--brand); }
.feed-compose-btn{
  width:38px; height:38px; border-radius:50%; border:none;
  background:var(--brand); color:#04150e; cursor:pointer;
  display:flex; align-items:center; justify-content:center;
  flex-shrink:0; transition:opacity .15s;
}
.feed-compose-btn:disabled{ opacity:.4; }

/* login prompt */
.feed-login{
  text-align:center; padding:50px 20px;
  background:var(--panel); border:1px solid var(--line);
  border-radius:var(--radius); margin-top:20px;
}
.feed-login h2{ margin:0 0 10px; font-size:18px; }
.feed-login p{ color:var(--ink-dim); margin:0 0 20px; font-size:14px; }
</style>

<div class="feed-wrap">
  <div class="feed-header">
    <h1><?= icon('chat', 20) ?> المجموعة العامة</h1>
    <?php if ($currentUser): ?>
    <a href="profile.php" class="btn btn-ghost btn-sm"><?= icon('user', 14) ?> ملفي</a>
    <?php endif; ?>
  </div>

  <?php if (!$currentUser): ?>
  <div class="feed-login">
    <?= icon('chat', 42) ?>
    <h2>انضم للمجموعة</h2>
    <p>سجّل دخولك بحساب Google للمشاركة في النقاشات</p>
    <a href="login.php?redirect=community.php" class="btn btn-google">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="18" height="18">
        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
      </svg>
      الدخول بـ Google
    </a>
    <div style="margin-top:16px; color:var(--ink-faint); font-size:13px;">يمكنك تصفح المجموعة بدون تسجيل دخول</div>
  </div>

  <!-- Feed (read-only for guests) -->
  <div id="feed-list" style="margin-top:20px;">
  <?php else: ?>
  <div id="feed-list">
  <?php endif; ?>

    <?php if (empty($messages)): ?>
    <div style="text-align:center; padding:50px 0; color:var(--ink-faint);">
      <?= icon('chat', 36) ?>
      <p style="margin-top:12px;">لا توجد رسائل بعد. كن أول من يكتب!</p>
    </div>
    <?php else: ?>
    <?php foreach ($messages as $msg):
      $isMine   = $currentUser && $msg['user_id'] === $currentUser['id'];
      $initial  = mb_substr($msg['display_name'], 0, 1, 'UTF-8');
      $handle   = $msg['username'] ?: null;
      $profUrl  = $handle ? 'profile.php?u=' . urlencode($handle) : 'profile.php';
    ?>
    <div class="feed-card <?= $isMine ? 'feed-mine' : '' ?>" data-id="<?= $msg['id'] ?>">
      <div class="feed-avatar-wrap">
        <a href="<?= h($profUrl) ?>" class="feed-avatar">
          <?php if ($msg['avatar_url']): ?>
          <img src="<?= h($msg['avatar_url']) ?>" alt="<?= h($msg['display_name']) ?>" loading="lazy">
          <?php else: ?>
          <?= h($initial) ?>
          <?php endif; ?>
        </a>
      </div>
      <div class="feed-body">
        <div class="feed-meta">
          <a href="<?= h($profUrl) ?>" class="feed-name"><?= h($msg['display_name']) ?></a>
          <?php if ($handle): ?>
          <a href="<?= h($profUrl) ?>" class="feed-handle">@<?= h($handle) ?></a>
          <?php endif; ?>
          <span class="feed-dot">·</span>
          <span class="feed-time"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
        </div>
        <div class="feed-text"><?= nl2br(h($msg['message'])) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php if ($currentUser): ?>
<!-- Compose bar -->
<div class="feed-compose">
  <div class="feed-compose-inner">
    <div class="feed-compose-avatar">
      <?php if ($currentUser['avatar_url']): ?>
      <img src="<?= h($currentUser['avatar_url']) ?>" alt="">
      <?php else: ?>
      <?= h(mb_substr($currentUser['display_name'], 0, 1, 'UTF-8')) ?>
      <?php endif; ?>
    </div>
    <input type="text" id="feed-input" class="feed-compose-input"
           placeholder="شارك برأيك..." maxlength="500" autocomplete="off">
    <button class="feed-compose-btn" id="feed-send" aria-label="إرسال">
      <?= icon('send', 16) ?>
    </button>
  </div>
</div>

<script>
(function(){
  const feedList    = document.getElementById('feed-list');
  const input       = document.getElementById('feed-input');
  const sendBtn     = document.getElementById('feed-send');
  const currentUid  = <?= (int)$currentUser['id'] ?>;
  const me          = <?= json_encode(['name' => $currentUser['display_name'], 'username' => $currentUser['username'], 'avatar' => $currentUser['avatar_url']]) ?>;
  let lastId = <?= $messages ? max(array_column($messages, 'id')) : 0 ?>;

  function esc(s){ return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

  function timeStr(d){
    const t = new Date(d || Date.now());
    return t.getHours().toString().padStart(2,'0')+':'+t.getMinutes().toString().padStart(2,'0');
  }

  function buildCard(msg, isMine){
    const handle  = msg.username || '';
    const profUrl = handle ? 'profile.php?u=' + encodeURIComponent(handle) : 'profile.php';
    const avatar  = msg.avatar_url
      ? `<img src="${esc(msg.avatar_url)}" alt="" loading="lazy">`
      : esc((msg.display_name||'?')[0]);
    const handleHtml = handle
      ? `<a href="${esc(profUrl)}" class="feed-handle">@${esc(handle)}</a>` : '';
    return `<div class="feed-card${isMine?' feed-mine':''}" data-id="${msg.id}">
      <div class="feed-avatar-wrap">
        <a href="${esc(profUrl)}" class="feed-avatar">${avatar}</a>
      </div>
      <div class="feed-body">
        <div class="feed-meta">
          <a href="${esc(profUrl)}" class="feed-name">${esc(msg.display_name)}</a>
          ${handleHtml}
          <span class="feed-dot">·</span>
          <span class="feed-time">${msg.time||timeStr()}</span>
        </div>
        <div class="feed-text">${esc(msg.message).replace(/\n/g,'<br>')}</div>
      </div>
    </div>`;
  }

  function appendCard(msg, isMine){
    const noMsg = feedList.querySelector('[style*="text-align:center"]');
    if (noMsg) noMsg.remove();
    feedList.insertAdjacentHTML('beforeend', buildCard(msg, isMine));
    feedList.lastElementChild.scrollIntoView({behavior:'smooth', block:'end'});
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
      if (d.ok) {
        appendCard({...d.msg, display_name:me.name, username:me.username, avatar_url:me.avatar}, true);
        lastId = d.msg.id;
      }
    }).catch(()=>{ sendBtn.disabled=false; });
  }

  sendBtn.addEventListener('click', send);
  input.addEventListener('keydown', e=>{ if(e.key==='Enter'&&!e.shiftKey){ e.preventDefault(); send(); } });

  // poll for new messages every 4s
  setInterval(()=>{
    fetch('api/chat.php?after='+lastId)
      .then(r=>r.json()).then(d=>{
        if (!d.messages) return;
        d.messages.forEach(msg=>{
          if (msg.user_id !== currentUid){
            appendCard(msg, false);
            lastId = Math.max(lastId, msg.id);
          }
        });
      }).catch(()=>{});
  }, 4000);
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/page-footer.php'; ?>
