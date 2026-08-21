<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = $social_pdo;
$me  = sc_user($pdo);

// Get username from URL: /profile/{username}
$username = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['u'] ?? '');
if (!$username) { header('Location: ' . SOCIAL_URL); exit; }

$st = $pdo->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
$st->execute([$username]);
$profile = $st->fetch();
if (!$profile) { http_response_code(404); echo '<h1>404 — المستخدم غير موجود</h1>'; exit; }

// Is $me following $profile?
$isFollowing = false;
if ($me && $me['id'] !== $profile['id']) {
    $fst = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id=? AND following_id=?");
    $fst->execute([$me['id'], $profile['id']]);
    $isFollowing = (bool)$fst->fetchColumn();
}

$tab   = in_array($_GET['tab'] ?? '', ['tools','followers','following']) ? $_GET['tab'] : 'posts';
$posts = $tools = $followers = $following = [];

if ($tab === 'tools') {
    $st = $pdo->prepare("SELECT * FROM marketplace_tools WHERE user_id=? AND status='published' ORDER BY created_at DESC LIMIT 30");
    $st->execute([$profile['id']]);
    $tools = $st->fetchAll();
} elseif ($tab === 'followers') {
    $st = $pdo->prepare("SELECT u.id,u.username,u.display_name,u.avatar,u.verified,u.follower_count FROM follows f JOIN users u ON u.id=f.follower_id WHERE f.following_id=? LIMIT 50");
    $st->execute([$profile['id']]);
    $followers = $st->fetchAll();
} elseif ($tab === 'following') {
    $st = $pdo->prepare("SELECT u.id,u.username,u.display_name,u.avatar,u.verified,u.follower_count FROM follows f JOIN users u ON u.id=f.following_id WHERE f.follower_id=? LIMIT 50");
    $st->execute([$profile['id']]);
    $following = $st->fetchAll();
} else {
    $st = $pdo->prepare("SELECT p.*,'post' as ptype FROM posts p WHERE p.user_id=? AND p.visibility='public' ORDER BY p.created_at DESC LIMIT 30");
    $st->execute([$profile['id']]);
    $posts = $st->fetchAll();
}

$isOwnProfile = $me && $me['id'] === $profile['id'];
$pageTitle     = ($profile['display_name'] ?: $profile['username']) . ' (@' . $profile['username'] . ') — ' . SOCIAL_NAME;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= sc_h($pageTitle) ?></title>
<meta name="description" content="ملف <?= sc_h($profile['display_name'] ?: $profile['username']) ?> على <?= sc_h(SOCIAL_NAME) ?>. <?= sc_h(mb_substr($profile['bio'] ?? '', 0, 100)) ?>">
<meta property="og:title" content="<?= sc_h($pageTitle) ?>">
<meta property="og:image" content="<?= sc_avatar_url($profile['avatar']) ?>">
<link rel="canonical" href="<?= sc_h(SOCIAL_URL . '/profile/' . $profile['username']) ?>">
<link rel="icon" href="/social/assets/favicon.svg" type="image/svg+xml">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0e1117;color:#e6edf3;min-height:100vh}
.nav{background:#161b22;border-bottom:1px solid #30363d;padding:0 20px;height:52px;display:flex;align-items:center;gap:14px}
.nav a{color:#e6edf3;text-decoration:none;font-size:.9rem}
.nav-brand{font-weight:800;color:#2f81f7}
.profile-header{background:#161b22;border-bottom:1px solid #30363d;padding:28px 20px 0}
.profile-inner{max-width:800px;margin:0 auto;padding:0 0 0}
.profile-top{display:flex;gap:20px;align-items:flex-start;margin-bottom:16px}
.profile-avatar{width:88px;height:88px;border-radius:50%;object-fit:cover;background:#30363d;border:3px solid #30363d;flex-shrink:0}
.profile-info{flex:1}
.profile-name{font-size:1.3rem;font-weight:800;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.profile-username{color:#8b949e;font-size:.9rem;margin-top:2px}
.profile-bio{margin-top:8px;font-size:.92rem;line-height:1.55;color:#c9d1d9}
.profile-stats{display:flex;gap:20px;margin-top:12px;font-size:.88rem}
.stat-val{font-weight:700;color:#e6edf3}
.stat-lbl{color:#8b949e;margin-right:4px}
.profile-actions{display:flex;gap:8px;margin-top:14px}
.btn{padding:8px 20px;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer;border:none;text-decoration:none}
.btn-follow{background:#2f81f7;color:#fff}
.btn-follow.following{background:transparent;border:1px solid #30363d;color:#e6edf3}
.btn-msg{background:transparent;border:1px solid #30363d;color:#e6edf3}
.btn-edit{background:transparent;border:1px solid #30363d;color:#e6edf3}
.badge-verified{background:#2f81f7;color:#fff;font-size:.75rem;padding:2px 8px;border-radius:20px}
.badge-premium{background:#e3b341;color:#000;font-size:.75rem;padding:2px 8px;border-radius:20px}
.tabs{display:flex;gap:0;border-bottom:0}
.tab{padding:12px 18px;font-size:.88rem;font-weight:600;color:#8b949e;border-bottom:2px solid transparent;cursor:pointer;text-decoration:none}
.tab:hover{color:#e6edf3;text-decoration:none}
.tab.active{color:#2f81f7;border-bottom-color:#2f81f7}
.content-area{max-width:800px;margin:20px auto;padding:0 16px}
.post-card{background:#161b22;border:1px solid #30363d;border-radius:10px;padding:16px;margin-bottom:12px}
.post-content{font-size:.93rem;line-height:1.6}
.post-actions{display:flex;gap:14px;margin-top:10px;font-size:.82rem;color:#8b949e}
.post-actions a{color:#8b949e;text-decoration:none}
.tool-row{background:#161b22;border:1px solid #30363d;border-radius:10px;padding:14px;margin-bottom:10px;display:flex;gap:12px;align-items:center}
.tool-icon{width:46px;height:46px;border-radius:8px;background:linear-gradient(135deg,#2f81f7,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0}
.user-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #21262d}
.user-row:last-child{border:0}
.user-avatar{width:42px;height:42px;border-radius:50%;object-fit:cover;background:#30363d}
</style>
</head>
<body>
<nav class="nav">
  <a href="<?= SOCIAL_URL ?>" class="nav-brand">◉ <?= sc_h(SOCIAL_NAME) ?></a>
  <span style="color:#8b949e">·</span>
  <a href="<?= SOCIAL_URL ?>">استكشاف</a>
</nav>

<div class="profile-header">
  <div class="profile-inner">
    <div class="profile-top">
      <img src="<?= sc_avatar_url($profile['avatar']) ?>" class="profile-avatar" alt="<?= sc_h($profile['username']) ?>">
      <div class="profile-info">
        <div class="profile-name">
          <?= sc_h($profile['display_name'] ?: $profile['username']) ?>
          <?php if ($profile['verified']): ?><span class="badge-verified">✓ موثّق</span><?php endif; ?>
          <?php if ($profile['premium_tier'] !== 'free'): ?><span class="badge-premium">⭐ <?= sc_h($profile['premium_tier']) ?></span><?php endif; ?>
        </div>
        <div class="profile-username">@<?= sc_h($profile['username']) ?></div>
        <?php if ($profile['bio']): ?><div class="profile-bio"><?= nl2br(sc_h($profile['bio'])) ?></div><?php endif; ?>
        <div class="profile-stats">
          <span><span class="stat-val"><?= number_format($profile['post_count']) ?></span><span class="stat-lbl">منشور</span></span>
          <a href="?u=<?= sc_h($username) ?>&tab=followers" style="text-decoration:none;color:inherit">
            <span class="stat-val"><?= number_format($profile['follower_count']) ?></span><span class="stat-lbl">متابِع</span>
          </a>
          <a href="?u=<?= sc_h($username) ?>&tab=following" style="text-decoration:none;color:inherit">
            <span class="stat-val"><?= number_format($profile['following_count']) ?></span><span class="stat-lbl">متابَع</span>
          </a>
        </div>
        <div class="profile-actions">
          <?php if ($isOwnProfile): ?>
            <a href="<?= SOCIAL_URL ?>/profile/edit" class="btn btn-edit">تعديل الملف</a>
            <a href="<?= SOCIAL_URL ?>/upgrade" class="btn" style="background:#e3b341;color:#000">⭐ ترقية</a>
          <?php elseif ($me): ?>
            <button class="btn btn-follow <?= $isFollowing?'following':'' ?>" id="follow-btn"
                    onclick="toggleFollow(<?= $profile['id'] ?>)">
              <?= $isFollowing ? 'متابَع ✓' : 'متابعة' ?>
            </button>
            <a href="<?= SOCIAL_URL ?>/messages?u=<?= $profile['id'] ?>" class="btn btn-msg">رسالة</a>
          <?php else: ?>
            <a href="<?= SOCIAL_URL ?>/auth/login" class="btn btn-follow">متابعة</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="tabs">
      <a href="?u=<?= sc_h($username) ?>" class="tab <?= $tab==='posts'?'active':'' ?>">المنشورات</a>
      <a href="?u=<?= sc_h($username) ?>&tab=tools" class="tab <?= $tab==='tools'?'active':'' ?>">الأدوات</a>
      <a href="?u=<?= sc_h($username) ?>&tab=followers" class="tab <?= $tab==='followers'?'active':'' ?>">المتابعون</a>
      <a href="?u=<?= sc_h($username) ?>&tab=following" class="tab <?= $tab==='following'?'active':'' ?>">يتابع</a>
    </div>
  </div>
</div>

<div class="content-area">
  <?php if ($tab === 'posts'): ?>
    <?php if (empty($posts)): ?>
      <div style="text-align:center;padding:40px;color:#8b949e">لا توجد منشورات بعد</div>
    <?php else: foreach ($posts as $p): ?>
    <div class="post-card">
      <div class="post-content"><?= nl2br(sc_h(mb_substr($p['content'],0,400))) ?></div>
      <div class="post-actions">
        <a href="<?= SOCIAL_URL ?>/post/<?= $p['id'] ?>">❤️ <?= $p['like_count'] ?> · 💬 <?= $p['comment_count'] ?> · <?= sc_time_ago($p['created_at']) ?></a>
      </div>
    </div>
    <?php endforeach; endif; ?>

  <?php elseif ($tab === 'tools'): ?>
    <?php if (empty($tools)): ?>
      <div style="text-align:center;padding:40px;color:#8b949e">لا توجد أدوات بعد</div>
    <?php else: foreach ($tools as $t): ?>
    <div class="tool-row">
      <div class="tool-icon">🛠️</div>
      <div style="flex:1">
        <a href="<?= SOCIAL_URL ?>/marketplace/tool/<?= sc_h($t['slug']) ?>" style="font-weight:700;color:#e6edf3;text-decoration:none"><?= sc_h($t['title']) ?></a>
        <div style="font-size:.8rem;color:#8b949e;margin-top:3px"><?= sc_h(mb_substr($t['description'],0,80)) ?></div>
      </div>
      <span style="font-size:.82rem;font-weight:700;padding:4px 10px;border-radius:20px;background:<?= $t['tool_type']==='free'?'rgba(63,185,80,.15)':'rgba(47,129,247,.15)' ?>;color:<?= $t['tool_type']==='free'?'#3fb950':'#2f81f7' ?>">
        <?= $t['tool_type']==='free' ? 'مجاني' : '$'.$t['price_usd'] ?>
      </span>
    </div>
    <?php endforeach; endif; ?>

  <?php elseif ($tab === 'followers' || $tab === 'following'):
    $list = $tab === 'followers' ? $followers : $following; ?>
    <?php if (empty($list)): ?>
      <div style="text-align:center;padding:40px;color:#8b949e">لا يوجد <?= $tab==='followers'?'متابعون':'متابَعون' ?> بعد</div>
    <?php else: foreach ($list as $u): ?>
    <div class="user-row">
      <img src="<?= sc_avatar_url($u['avatar']) ?>" class="user-avatar" alt="<?= sc_h($u['username']) ?>">
      <div style="flex:1">
        <a href="<?= SOCIAL_URL ?>/profile/<?= sc_h($u['username']) ?>" style="font-weight:600;color:#e6edf3;text-decoration:none">
          <?= sc_h($u['display_name'] ?: $u['username']) ?>
          <?php if ($u['verified']): ?><span style="color:#2f81f7;font-size:.85rem">✓</span><?php endif; ?>
        </a>
        <div style="font-size:.78rem;color:#8b949e">@<?= sc_h($u['username']) ?> · <?= number_format($u['follower_count']) ?> متابع</div>
      </div>
    </div>
    <?php endforeach; endif; ?>
  <?php endif; ?>
</div>

<script>
async function toggleFollow(uid) {
  const btn = document.getElementById('follow-btn');
  const r = await fetch('<?= SOCIAL_URL ?>/api/follow', {
    method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({user_id:uid})
  });
  const d = await r.json();
  if (d.ok) {
    btn.textContent = d.following ? 'متابَع ✓' : 'متابعة';
    btn.className = 'btn btn-follow' + (d.following ? ' following' : '');
  }
}
</script>
</body>
</html>
