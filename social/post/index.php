<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = $social_pdo;
$me  = sc_user($pdo);

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SOCIAL_URL); exit; }

$st = $pdo->prepare("
    SELECT p.*, u.username, u.display_name, u.avatar, u.verified
    FROM posts p JOIN users u ON u.id=p.user_id
    WHERE p.id=? AND p.visibility='public'
");
$st->execute([$id]);
$post = $st->fetch();
if (!$post) { http_response_code(404); echo '<p>المنشور غير موجود</p>'; exit; }

// Comments
$cst = $pdo->prepare("
    SELECT c.*, u.username, u.display_name, u.avatar, u.verified
    FROM comments c JOIN users u ON u.id=c.user_id
    WHERE c.post_id=? AND c.parent_id IS NULL
    ORDER BY c.created_at ASC LIMIT 50
");
$cst->execute([$id]);
$comments = $cst->fetchAll();

// Increment view
$pdo->prepare("UPDATE posts SET view_count=view_count+1 WHERE id=?")->execute([$id]);

$pageTitle = 'منشور @' . $post['username'] . ' — ' . SOCIAL_NAME;
$metaDesc  = mb_substr(strip_tags($post['content']), 0, 160);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= sc_h($pageTitle) ?></title>
<meta name="description" content="<?= sc_h($metaDesc) ?>">
<meta property="og:title" content="<?= sc_h($pageTitle) ?>">
<meta property="og:description" content="<?= sc_h($metaDesc) ?>">
<meta property="og:image" content="<?= sc_avatar_url($post['avatar']) ?>">
<link rel="canonical" href="<?= sc_h(SOCIAL_URL . '/post/' . $id) ?>">
<link rel="icon" href="/social/assets/favicon.svg" type="image/svg+xml">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0e1117;color:#e6edf3;min-height:100vh}
.nav{background:#161b22;border-bottom:1px solid #30363d;padding:0 20px;height:52px;display:flex;align-items:center;gap:14px}
.nav a{color:#e6edf3;text-decoration:none;font-size:.9rem}
.nav-brand{font-weight:800;color:#2f81f7}
.container{max-width:700px;margin:24px auto;padding:0 16px}
.card{background:#161b22;border:1px solid #30363d;border-radius:12px;padding:20px;margin-bottom:16px}
.post-header{display:flex;gap:12px;align-items:center;margin-bottom:14px}
.avatar{width:44px;height:44px;border-radius:50%;object-fit:cover;background:#30363d}
.post-content{font-size:.97rem;line-height:1.7;margin-bottom:14px;word-break:break-word}
.post-images{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;margin-bottom:14px}
.post-images img{width:100%;border-radius:8px;object-fit:cover}
.post-stats{display:flex;gap:18px;font-size:.85rem;color:#8b949e;padding:12px 0;border-top:1px solid #21262d;border-bottom:1px solid #21262d;margin-bottom:14px}
.post-actions{display:flex;gap:10px;margin-bottom:14px}
.action-btn{flex:1;padding:9px;border:1px solid #30363d;background:transparent;color:#8b949e;border-radius:8px;cursor:pointer;font-size:.88rem;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .15s}
.action-btn:hover{border-color:#2f81f7;color:#2f81f7}
.comment{display:flex;gap:10px;margin-bottom:14px}
.comment-avatar{width:34px;height:34px;border-radius:50%;object-fit:cover;background:#30363d;flex-shrink:0}
.comment-body{background:#0e1117;border-radius:10px;padding:10px 14px;flex:1}
.comment-name{font-weight:600;font-size:.85rem;margin-bottom:4px}
.comment-text{font-size:.9rem;line-height:1.55}
.comment-time{font-size:.76rem;color:#8b949e;margin-top:4px}
.new-comment{display:flex;gap:10px;align-items:flex-start;margin-top:16px}
.comment-input{flex:1;background:#0e1117;border:1px solid #30363d;border-radius:8px;padding:10px 14px;color:#e6edf3;font-size:.9rem;min-height:44px;resize:vertical;font-family:inherit}
.comment-input:focus{outline:none;border-color:#2f81f7}
.post-btn{background:#2f81f7;color:#fff;border:none;border-radius:8px;padding:10px 16px;cursor:pointer;font-size:.88rem;font-weight:600}
</style>
</head>
<body>
<nav class="nav">
  <a href="<?= SOCIAL_URL ?>" class="nav-brand">◉ <?= sc_h(SOCIAL_NAME) ?></a>
  <span style="color:#8b949e">·</span>
  <a href="<?= SOCIAL_URL ?>">← العودة</a>
</nav>

<div class="container">
  <div class="card" itemscope itemtype="https://schema.org/SocialMediaPosting">
    <div class="post-header">
      <a href="<?= SOCIAL_URL ?>/profile/<?= sc_h($post['username']) ?>">
        <img src="<?= sc_avatar_url($post['avatar']) ?>" class="avatar" alt="<?= sc_h($post['username']) ?>">
      </a>
      <div>
        <div style="font-weight:700">
          <a href="<?= SOCIAL_URL ?>/profile/<?= sc_h($post['username']) ?>" style="color:#e6edf3;text-decoration:none" itemprop="author">
            <?= sc_h($post['display_name'] ?: $post['username']) ?>
          </a>
          <?php if ($post['verified']): ?><span style="color:#2f81f7;font-size:.85rem">✓</span><?php endif; ?>
        </div>
        <div style="font-size:.78rem;color:#8b949e">@<?= sc_h($post['username']) ?> · <?= sc_time_ago($post['created_at']) ?></div>
      </div>
    </div>

    <div class="post-content" itemprop="text"><?= nl2br(sc_h($post['content'])) ?></div>

    <?php if (!empty($post['media_urls'])): $imgs = json_decode($post['media_urls'],true) ?? []; ?>
      <?php if ($imgs): ?>
      <div class="post-images">
        <?php foreach ($imgs as $img): ?>
        <img src="<?= sc_h(SOCIAL_UPLOAD_URL . '/posts/' . $img) ?>" alt="" loading="lazy">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="post-stats">
      <span>❤️ <strong id="like-count"><?= number_format($post['like_count']) ?></strong> إعجاب</span>
      <span>💬 <?= number_format($post['comment_count']) ?> تعليق</span>
      <span>👁 <?= number_format($post['view_count']) ?> مشاهدة</span>
    </div>

    <div class="post-actions">
      <button class="action-btn" id="like-btn" onclick="toggleLike(<?= $post['id'] ?>)">❤️ إعجاب</button>
      <button class="action-btn" onclick="document.getElementById('comment-input').focus()">💬 تعليق</button>
      <button class="action-btn" onclick="sharePost()">🔗 مشاركة</button>
    </div>
  </div>

  <!-- Comments -->
  <div class="card">
    <h3 style="font-size:.95rem;margin-bottom:16px">التعليقات (<?= count($comments) ?>)</h3>

    <?php foreach ($comments as $c): ?>
    <div class="comment">
      <img src="<?= sc_avatar_url($c['avatar']) ?>" class="comment-avatar" alt="<?= sc_h($c['username']) ?>">
      <div class="comment-body">
        <div class="comment-name">
          <a href="<?= SOCIAL_URL ?>/profile/<?= sc_h($c['username']) ?>" style="color:#e6edf3;text-decoration:none"><?= sc_h($c['display_name'] ?: $c['username']) ?></a>
          <?php if ($c['verified']): ?><span style="color:#2f81f7;font-size:.8rem">✓</span><?php endif; ?>
        </div>
        <div class="comment-text"><?= nl2br(sc_h($c['content'])) ?></div>
        <div class="comment-time"><?= sc_time_ago($c['created_at']) ?></div>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if ($me): ?>
    <div class="new-comment" style="margin-top:8px">
      <img src="<?= sc_avatar_url($me['avatar']) ?>" class="comment-avatar" alt="">
      <textarea class="comment-input" id="comment-input" placeholder="أضف تعليقاً..." rows="2"></textarea>
      <button class="post-btn" onclick="addComment()">نشر</button>
    </div>
    <?php else: ?>
    <p style="text-align:center;color:#8b949e;margin-top:12px;font-size:.88rem">
      <a href="<?= SOCIAL_URL ?>/auth/login" style="color:#2f81f7">سجّل دخولك</a> للتعليق
    </p>
    <?php endif; ?>
  </div>
</div>

<script>
const POST_ID = <?= $id ?>;
const ME      = <?= $me ? $me['id'] : 'null' ?>;
const BASE    = '<?= SOCIAL_URL ?>/api';

async function toggleLike(pid) {
  if (!ME) { location.href='<?= SOCIAL_URL ?>/auth/login'; return; }
  const r = await fetch(BASE+'/like',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({post_id:pid})});
  const d = await r.json();
  if(d.ok){document.getElementById('like-count').textContent=d.likes;document.getElementById('like-btn').style.color=d.liked?'#f85149':'';}
}

async function addComment() {
  const inp = document.getElementById('comment-input');
  const text = inp.value.trim();
  if (!text) return;
  const r = await fetch(BASE+'/comment',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({post_id:POST_ID,content:text})});
  const d = await r.json();
  if (d.ok) location.reload();
  else alert(d.error);
}

function sharePost() {
  const url = location.href;
  if (navigator.share) navigator.share({url, title: '<?= sc_h(mb_substr($post['content'],0,60)) ?>'});
  else { navigator.clipboard?.writeText(url); alert('تم نسخ الرابط'); }
}
</script>
</body>
</html>
