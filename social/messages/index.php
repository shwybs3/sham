<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = $social_pdo;
$me  = sc_user($pdo);
if (!$me) { header('Location: ' . SOCIAL_URL . '/auth/login'); exit; }

$activeConv = (int)($_GET['c'] ?? 0);
$toUser     = (int)($_GET['u'] ?? 0);

// Load conversations
$st = $pdo->prepare("
    SELECT c.*, cm.last_read,
      (SELECT COUNT(*) FROM messages m WHERE m.conv_id=c.id
       AND m.created_at > COALESCE(cm.last_read,'1970-01-01') AND m.is_deleted=0) as unread,
      (SELECT m2.content FROM messages m2 WHERE m2.conv_id=c.id AND m2.is_deleted=0 ORDER BY m2.created_at DESC LIMIT 1) as last_msg,
      (SELECT u2.username FROM messages m3 JOIN users u2 ON u2.id=m3.user_id WHERE m3.conv_id=c.id AND m3.is_deleted=0 ORDER BY m3.created_at DESC LIMIT 1) as last_msg_by
    FROM conversations c
    JOIN conversation_members cm ON cm.conv_id=c.id AND cm.user_id=?
    ORDER BY c.last_msg_at DESC LIMIT 50
");
$st->execute([$me['id']]);
$convs = $st->fetchAll();

// Load messages for active conv
$convMessages  = [];
$convPartner   = null;
if ($activeConv) {
    $check = $pdo->prepare("SELECT 1 FROM conversation_members WHERE conv_id=? AND user_id=?");
    $check->execute([$activeConv, $me['id']]);
    if ($check->fetchColumn()) {
        $mst = $pdo->prepare("
            SELECT m.*, u.username, u.display_name, u.avatar, u.verified
            FROM messages m JOIN users u ON u.id=m.user_id
            WHERE m.conv_id=? AND m.is_deleted=0
            ORDER BY m.created_at ASC LIMIT 100
        ");
        $mst->execute([$activeConv]);
        $convMessages = $mst->fetchAll();
        $pdo->prepare("UPDATE conversation_members SET last_read=NOW() WHERE conv_id=? AND user_id=?")->execute([$activeConv,$me['id']]);

        // Get partner info for direct chats
        $pst = $pdo->prepare("
            SELECT u.* FROM conversation_members cm JOIN users u ON u.id=cm.user_id
            WHERE cm.conv_id=? AND cm.user_id != ? LIMIT 1
        ");
        $pst->execute([$activeConv, $me['id']]);
        $convPartner = $pst->fetch();
    }
}

// Start DM with a user
if ($toUser && !$activeConv) {
    $pst = $pdo->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
    $pst->execute([$toUser]);
    $convPartner = $pst->fetch();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>الرسائل — <?= sc_h(SOCIAL_NAME) ?></title>
<link rel="icon" href="/social/assets/favicon.svg" type="image/svg+xml">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0e1117;color:#e6edf3;height:100vh;display:flex;flex-direction:column}
.nav{background:#161b22;border-bottom:1px solid #30363d;padding:0 20px;height:52px;display:flex;align-items:center;gap:14px;flex-shrink:0}
.nav a{color:#e6edf3;text-decoration:none;font-size:.9rem}
.nav-brand{font-weight:800;color:#2f81f7}
.msgs-layout{display:flex;flex:1;overflow:hidden}
.sidebar{width:300px;border-left:1px solid #30363d;overflow-y:auto;flex-shrink:0}
.sidebar-header{padding:14px 16px;border-bottom:1px solid #30363d;font-weight:700;font-size:.95rem}
.conv-row{display:flex;gap:10px;padding:12px 16px;border-bottom:1px solid #1e2532;cursor:pointer;text-decoration:none;color:inherit;align-items:center;transition:background .12s}
.conv-row:hover,.conv-row.active{background:#1e2532}
.conv-avatar{width:42px;height:42px;border-radius:50%;object-fit:cover;background:#30363d;flex-shrink:0}
.conv-info{flex:1;min-width:0}
.conv-name{font-weight:600;font-size:.9rem;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.conv-last{font-size:.78rem;color:#8b949e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.conv-unread{background:#2f81f7;color:#fff;border-radius:10px;padding:2px 7px;font-size:.72rem;font-weight:700;flex-shrink:0}
.chat-area{flex:1;display:flex;flex-direction:column;overflow:hidden}
.chat-header{padding:14px 20px;border-bottom:1px solid #30363d;display:flex;align-items:center;gap:12px;flex-shrink:0;background:#161b22}
.chat-header .name{font-weight:700}
.chat-messages{flex:1;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:12px}
.msg{display:flex;gap:10px;align-items:flex-end}
.msg.mine{flex-direction:row-reverse}
.msg-avatar{width:32px;height:32px;border-radius:50%;object-fit:cover;background:#30363d;flex-shrink:0}
.msg-bubble{max-width:65%;background:#1e2532;border-radius:12px 12px 4px 12px;padding:10px 14px;font-size:.9rem;line-height:1.5}
.msg.mine .msg-bubble{background:#2f81f7;border-radius:12px 12px 12px 4px}
.msg-time{font-size:.7rem;color:#8b949e;margin-top:4px;text-align:right}
.msg.mine .msg-time{text-align:left}
.chat-input-bar{padding:14px 20px;border-top:1px solid #30363d;display:flex;gap:10px;flex-shrink:0;background:#161b22}
.chat-input{flex:1;background:#0e1117;border:1px solid #30363d;border-radius:24px;padding:10px 16px;color:#e6edf3;font-size:.9rem;resize:none;font-family:inherit;max-height:120px}
.chat-input:focus{outline:none;border-color:#2f81f7}
.send-btn{background:#2f81f7;border:none;border-radius:50%;width:42px;height:42px;color:#fff;font-size:1.1rem;cursor:pointer;flex-shrink:0}
.send-btn:hover{opacity:.88}
.empty-chat{flex:1;display:flex;align-items:center;justify-content:center;color:#8b949e;font-size:.95rem;flex-direction:column;gap:12px}
.empty-chat .icon{font-size:3rem}
@media(max-width:640px){.sidebar{display:<?= $activeConv?'none':'flex' ?>;flex-direction:column;width:100%}.chat-area{display:<?= $activeConv?'flex':'none' ?>}}
</style>
</head>
<body>
<nav class="nav">
  <a href="<?= SOCIAL_URL ?>" class="nav-brand">← <?= sc_h(SOCIAL_NAME) ?></a>
  <span style="color:#8b949e">|</span>
  <span>الرسائل</span>
</nav>

<div class="msgs-layout">
  <!-- Conversations sidebar -->
  <div class="sidebar">
    <div class="sidebar-header">💬 محادثاتك</div>
    <?php if (empty($convs)): ?>
      <div style="padding:20px;color:#8b949e;font-size:.88rem;text-align:center">لا توجد محادثات بعد</div>
    <?php else: ?>
      <?php foreach ($convs as $c): ?>
      <a href="?c=<?= $c['id'] ?>" class="conv-row <?= $c['id']==$activeConv?'active':'' ?>">
        <img src="<?= sc_h($c['avatar'] ? SOCIAL_UPLOAD_URL.'/avatars/'.$c['avatar'] : SOCIAL_URL.'/assets/default-avatar.svg') ?>"
             class="conv-avatar" alt="">
        <div class="conv-info">
          <div class="conv-name"><?= sc_h($c['name'] ?: 'محادثة خاصة') ?></div>
          <div class="conv-last"><?= sc_h(mb_substr($c['last_msg'] ?? '', 0, 40)) ?></div>
        </div>
        <?php if ($c['unread'] > 0): ?><span class="conv-unread"><?= $c['unread'] ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Chat area -->
  <div class="chat-area">
    <?php if ($activeConv || $toUser): ?>
      <div class="chat-header">
        <?php if ($convPartner): ?>
        <img src="<?= sc_avatar_url($convPartner['avatar']) ?>" style="width:38px;height:38px;border-radius:50%;object-fit:cover">
        <div>
          <div class="name"><?= sc_h($convPartner['display_name'] ?: $convPartner['username']) ?></div>
          <a href="<?= SOCIAL_URL ?>/profile/<?= sc_h($convPartner['username']) ?>" style="font-size:.78rem;color:#8b949e">@<?= sc_h($convPartner['username']) ?></a>
        </div>
        <?php endif; ?>
      </div>
      <div class="chat-messages" id="chat-messages">
        <?php foreach ($convMessages as $m): $isMine = $m['user_id'] == $me['id']; ?>
        <div class="msg <?= $isMine?'mine':'' ?>">
          <img src="<?= sc_avatar_url($m['avatar']) ?>" class="msg-avatar" alt="">
          <div>
            <div class="msg-bubble"><?= nl2br(sc_h($m['content'])) ?></div>
            <div class="msg-time"><?= sc_time_ago($m['created_at']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($convMessages)): ?>
          <div style="text-align:center;color:#8b949e;margin-top:40px">ابدأ المحادثة 👋</div>
        <?php endif; ?>
      </div>
      <div class="chat-input-bar">
        <textarea class="chat-input" id="msg-input" placeholder="اكتب رسالتك..." rows="1"
                  onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMsg()}"></textarea>
        <button class="send-btn" onclick="sendMsg()">➤</button>
      </div>
    <?php else: ?>
      <div class="empty-chat">
        <div class="icon">💬</div>
        <p>اختر محادثة أو ابدأ محادثة جديدة</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
const CONV_ID  = <?= $activeConv ?: 0 ?>;
const TO_USER  = <?= $toUser ?: 0 ?>;
const BASE     = '<?= SOCIAL_URL ?>/api';

async function sendMsg() {
  const input = document.getElementById('msg-input');
  const text  = input.value.trim();
  if (!text) return;
  input.value = '';
  const body = {content: text};
  if (CONV_ID) body.conv_id = CONV_ID;
  else body.to_user = TO_USER;
  const r = await fetch(BASE + '/messages/send', {
    method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)
  });
  const d = await r.json();
  if (d.ok && !CONV_ID) {
    location.href = '?c=' + d.conv_id;
  } else if (d.ok) {
    appendMsg(text);
  } else alert(d.error);
}

function appendMsg(text) {
  const wrap = document.getElementById('chat-messages');
  const div  = document.createElement('div');
  div.className = 'msg mine';
  div.innerHTML = `<img src="<?= sc_avatar_url($me['avatar']) ?>" class="msg-avatar">
    <div><div class="msg-bubble">${text.replace(/</g,'&lt;')}</div><div class="msg-time">الآن</div></div>`;
  wrap.appendChild(div);
  wrap.scrollTop = wrap.scrollHeight;
}

// Scroll to bottom
document.addEventListener('DOMContentLoaded', () => {
  const m = document.getElementById('chat-messages');
  if (m) m.scrollTop = m.scrollHeight;
});
</script>
</body>
</html>
