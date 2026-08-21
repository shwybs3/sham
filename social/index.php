<?php
/* ══════════════════════════════════════════════════════
   Yassota Connect — Explore Feed (public, SEO-indexed)
   ══════════════════════════════════════════════════════ */
ob_start();
require_once __DIR__ . '/config.php';

$pdo = $social_pdo;
$me  = sc_user($pdo);

// Pagination
$page  = max(1, (int)($_GET['p'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$tab   = in_array($_GET['tab'] ?? '', ['posts','tools','scripts']) ? $_GET['tab'] : 'posts';

// Fetch posts
$posts = [];
$tools = [];
if ($tab === 'tools') {
    $st = $pdo->prepare("
        SELECT t.*, u.username, u.display_name, u.avatar, u.verified
        FROM marketplace_tools t
        JOIN users u ON u.id = t.user_id
        WHERE t.status='published' AND t.is_approved=1
        ORDER BY t.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $st->execute([$limit, $offset]);
    $tools = $st->fetchAll();
} else {
    $typeFilter = $tab === 'scripts' ? "AND p.post_type='script'" : "AND p.post_type IN ('post','article')";
    $st = $pdo->prepare("
        SELECT p.*, u.username, u.display_name, u.avatar, u.verified
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE p.visibility='public' AND p.is_flagged=0 $typeFilter
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $st->execute([$limit, $offset]);
    $posts = $st->fetchAll();
}

// Friend suggestions for logged-in users
$suggestions = [];
if ($me) {
    $st = $pdo->prepare("
        SELECT u.id, u.username, u.display_name, u.avatar, u.verified, u.follower_count
        FROM users u
        WHERE u.id != ?
          AND u.status = 'active'
          AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id=?)
        ORDER BY u.follower_count DESC, RAND()
        LIMIT 5
    ");
    $st->execute([$me['id'], $me['id']]);
    $suggestions = $st->fetchAll();
}

$siteTitle = SOCIAL_NAME . ' — اكتشف، تواصل، شارك';
$siteDesc  = 'منصة اجتماعية عربية للتواصل ومشاركة الأدوات والسكريبتات القانونية.';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= sc_h($siteTitle) ?></title>
<meta name="description" content="<?= sc_h($siteDesc) ?>">
<meta property="og:title" content="<?= sc_h($siteTitle) ?>">
<meta property="og:description" content="<?= sc_h($siteDesc) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= sc_h(SOCIAL_URL) ?>">
<link rel="canonical" href="<?= sc_h(SOCIAL_URL) ?>">
<link rel="icon" href="/social/assets/favicon.svg" type="image/svg+xml">
<style>
:root {
  --bg:      #0e1117;
  --surface: #161b22;
  --border:  #30363d;
  --text:    #e6edf3;
  --muted:   #8b949e;
  --accent:  #2f81f7;
  --accent2: #3fb950;
  --red:     #f85149;
  --gold:    #e3b341;
  --radius:  10px;
  --gap:     16px;
}
@media (prefers-color-scheme: light) { :root:not([data-theme="dark"]) {
  --bg:#f0f2f5; --surface:#fff; --border:#d0d7de; --text:#1f2328; --muted:#57606a;
}}
:root[data-theme="light"] {
  --bg:#f0f2f5; --surface:#fff; --border:#d0d7de; --text:#1f2328; --muted:#57606a;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,'Segoe UI',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
a{color:var(--accent);text-decoration:none}
a:hover{text-decoration:underline}

/* ── Nav ── */
.nav{position:sticky;top:0;z-index:100;background:var(--surface);border-bottom:1px solid var(--border);padding:0 20px;height:56px;display:flex;align-items:center;gap:16px}
.nav-brand{font-weight:800;font-size:1.15rem;color:var(--text);display:flex;align-items:center;gap:8px}
.nav-brand .dot{width:8px;height:8px;border-radius:50%;background:var(--accent);display:inline-block}
.nav-links{display:flex;gap:4px;margin-right:auto}
.nav-links a{padding:6px 12px;border-radius:6px;font-size:.85rem;color:var(--muted);font-weight:500}
.nav-links a:hover,.nav-links a.act{background:rgba(47,129,247,.1);color:var(--accent);text-decoration:none}
.nav-btn{padding:7px 16px;border-radius:8px;font-size:.85rem;font-weight:600;white-space:nowrap}
.btn-outline{border:1px solid var(--border);color:var(--text);background:transparent}
.btn-outline:hover{background:var(--border)}
.btn-primary{background:var(--accent);color:#fff;border:none}
.btn-primary:hover{opacity:.88;text-decoration:none}
.nav-avatar{width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid var(--border)}

/* ── Layout ── */
.layout{max-width:1100px;margin:0 auto;padding:24px 16px;display:grid;grid-template-columns:1fr 300px;gap:24px}
@media(max-width:768px){.layout{grid-template-columns:1fr}.sidebar{display:none}}

/* ── Tabs ── */
.tabs{display:flex;gap:8px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:0}
.tab{padding:10px 16px;font-size:.9rem;font-weight:600;color:var(--muted);border-bottom:2px solid transparent;cursor:pointer;text-decoration:none;transition:color .15s}
.tab:hover{color:var(--text);text-decoration:none}
.tab.act{color:var(--accent);border-bottom-color:var(--accent)}

/* ── Post Card ── */
.post-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:12px;transition:border-color .15s}
.post-card:hover{border-color:var(--accent)}
.post-meta{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.post-avatar{width:40px;height:40px;border-radius:50%;object-fit:cover;background:var(--border)}
.post-meta-info .name{font-weight:700;font-size:.92rem}
.post-meta-info .name a{color:var(--text)}
.post-meta-info .time{font-size:.78rem;color:var(--muted)}
.badge-verified{color:var(--accent);font-size:.85rem;margin-right:4px}
.post-content{font-size:.95rem;line-height:1.65;margin-bottom:12px;word-break:break-word}
.post-images{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:6px;margin-bottom:12px}
.post-images img{width:100%;border-radius:6px;object-fit:cover;aspect-ratio:4/3}
.post-actions{display:flex;gap:16px;font-size:.82rem;color:var(--muted)}
.post-actions button,.post-actions a{background:none;border:none;color:var(--muted);cursor:pointer;font-size:.82rem;display:flex;align-items:center;gap:4px;padding:4px 6px;border-radius:6px;transition:all .15s}
.post-actions button:hover{background:rgba(47,129,247,.1);color:var(--accent)}
.post-type-tag{display:inline-block;font-size:.72rem;padding:2px 8px;border-radius:20px;margin-left:8px;font-weight:600}
.tag-script{background:rgba(63,185,80,.15);color:var(--accent2)}
.tag-article{background:rgba(47,129,247,.15);color:var(--accent)}

/* ── Tool Card ── */
.tool-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:12px;display:flex;gap:14px;align-items:flex-start;transition:border-color .15s}
.tool-card:hover{border-color:var(--accent)}
.tool-icon{width:56px;height:56px;border-radius:10px;background:linear-gradient(135deg,var(--accent),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0}
.tool-info .title{font-weight:700;font-size:.95rem;margin-bottom:4px}
.tool-info .desc{font-size:.82rem;color:var(--muted);line-height:1.5;margin-bottom:8px}
.tool-price{font-size:.8rem;font-weight:700;padding:3px 10px;border-radius:20px}
.price-free{background:rgba(63,185,80,.15);color:var(--accent2)}
.price-paid{background:rgba(47,129,247,.15);color:var(--accent)}

/* ── Sidebar ── */
.sidebar-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:16px}
.sidebar-card h3{font-size:.9rem;font-weight:700;margin-bottom:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
.suggest-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)}
.suggest-row:last-child{border:0}
.suggest-avatar{width:36px;height:36px;border-radius:50%;object-fit:cover;background:var(--border)}
.suggest-info .name{font-weight:600;font-size:.88rem}
.suggest-info .count{font-size:.78rem;color:var(--muted)}
.suggest-follow{margin-right:auto;padding:4px 12px;border-radius:20px;font-size:.8rem;font-weight:600;border:1px solid var(--accent);color:var(--accent);background:transparent;cursor:pointer;transition:all .15s}
.suggest-follow:hover{background:var(--accent);color:#fff}

/* ── New Post Box ── */
.new-post-box{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px;margin-bottom:16px;display:flex;gap:10px;align-items:flex-start}
.new-post-box textarea{flex:1;background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:10px;color:var(--text);font-size:.92rem;resize:vertical;min-height:60px;font-family:inherit}
.new-post-box textarea:focus{outline:none;border-color:var(--accent)}

/* ── Empty state ── */
.empty{text-align:center;padding:60px 20px;color:var(--muted)}
.empty .icon{font-size:3rem;margin-bottom:12px}
</style>
</head>
<body>

<!-- Nav -->
<nav class="nav">
  <a href="<?= SOCIAL_URL ?>" class="nav-brand" style="text-decoration:none">
    <span class="dot"></span><?= sc_h(SOCIAL_NAME) ?>
  </a>
  <div class="nav-links">
    <a href="<?= SOCIAL_URL ?>" class="act">استكشاف</a>
    <?php if ($me): ?>
    <a href="<?= SOCIAL_URL ?>/messages">الرسائل</a>
    <a href="<?= SOCIAL_URL ?>/marketplace">السوق</a>
    <?php endif; ?>
  </div>
  <?php if ($me): ?>
    <a href="<?= SOCIAL_URL ?>/profile/<?= sc_h($me['username']) ?>">
      <img src="<?= sc_avatar_url($me['avatar']) ?>" class="nav-avatar" alt="<?= sc_h($me['display_name']) ?>">
    </a>
    <a href="<?= SOCIAL_URL ?>/logout" class="nav-btn btn-outline" style="text-decoration:none">خروج</a>
  <?php else: ?>
    <a href="<?= SOCIAL_URL ?>/auth/login" class="nav-btn btn-outline" style="text-decoration:none">دخول</a>
    <a href="<?= SOCIAL_URL ?>/auth/register" class="nav-btn btn-primary" style="text-decoration:none">انضم الآن</a>
  <?php endif; ?>
</nav>

<!-- Layout -->
<div class="layout">
  <!-- Feed -->
  <main>
    <?php if ($me): ?>
    <div class="new-post-box">
      <img src="<?= sc_avatar_url($me['avatar']) ?>" style="width:38px;height:38px;border-radius:50%;object-fit:cover">
      <textarea placeholder="شارك شيئاً مع المجتمع..." id="new-post-text"></textarea>
      <div>
        <button class="nav-btn btn-primary" onclick="submitPost()" style="margin-bottom:6px;display:block;width:100%">نشر</button>
        <select id="post-type" style="width:100%;padding:5px 8px;border-radius:6px;border:1px solid var(--border);background:var(--bg);color:var(--text);font-size:.8rem">
          <option value="post">منشور</option>
          <option value="script">سكريبت</option>
          <option value="article">مقال</option>
        </select>
      </div>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tabs">
      <a href="?tab=posts" class="tab <?= $tab==='posts'?'act':'' ?>">المنشورات</a>
      <a href="?tab=scripts" class="tab <?= $tab==='scripts'?'act':'' ?>">السكريبتات</a>
      <a href="?tab=tools" class="tab <?= $tab==='tools'?'act':'' ?>">الأدوات</a>
    </div>

    <?php if ($tab === 'tools'): ?>
      <?php if (empty($tools)): ?>
        <div class="empty"><div class="icon">🛠️</div><p>لا توجد أدوات بعد — كن أول من ينشر!</p></div>
      <?php else: ?>
        <?php foreach ($tools as $t): ?>
        <div class="tool-card">
          <div class="tool-icon">🛠️</div>
          <div class="tool-info" style="flex:1">
            <div class="title">
              <a href="<?= SOCIAL_URL ?>/tools/<?= sc_h($t['slug']) ?>"><?= sc_h($t['title']) ?></a>
              <span class="tool-price <?= $t['tool_type']==='free'?'price-free':'price-paid' ?>">
                <?= $t['tool_type']==='free' ? 'مجاني' : '$' . number_format($t['price_usd'],2) ?>
              </span>
            </div>
            <div class="desc"><?= sc_h(mb_substr($t['description'],0,120)) ?>...</div>
            <div style="font-size:.78rem;color:var(--muted)">
              بواسطة <a href="<?= SOCIAL_URL ?>/profile/<?= sc_h($t['username']) ?>"><?= sc_h($t['display_name'] ?: $t['username']) ?></a>
              · <?= number_format($t['download_count']) ?> تحميل
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    <?php else: ?>
      <?php if (empty($posts)): ?>
        <div class="empty"><div class="icon">✨</div><p>لا توجد منشورات بعد — ابدأ المحادثة!</p></div>
      <?php else: ?>
        <?php foreach ($posts as $post): ?>
        <article class="post-card" itemscope itemtype="https://schema.org/SocialMediaPosting">
          <div class="post-meta">
            <a href="<?= SOCIAL_URL ?>/profile/<?= sc_h($post['username']) ?>">
              <img src="<?= sc_avatar_url($post['avatar']) ?>" class="post-avatar" alt="<?= sc_h($post['display_name']) ?>">
            </a>
            <div class="post-meta-info">
              <div class="name">
                <a href="<?= SOCIAL_URL ?>/profile/<?= sc_h($post['username']) ?>" itemprop="author">
                  <?= sc_h($post['display_name'] ?: $post['username']) ?>
                </a>
                <?php if ($post['verified']): ?><span class="badge-verified" title="موثّق">✓</span><?php endif; ?>
                <?php if ($post['post_type'] === 'script'): ?>
                  <span class="post-type-tag tag-script">سكريبت</span>
                <?php elseif ($post['post_type'] === 'article'): ?>
                  <span class="post-type-tag tag-article">مقال</span>
                <?php endif; ?>
              </div>
              <div class="time"><?= sc_time_ago($post['created_at']) ?></div>
            </div>
          </div>
          <div class="post-content" itemprop="text"><?= nl2br(sc_h(mb_substr($post['content'],0,400))) ?></div>
          <?php if (!empty($post['media_urls'])): $imgs = json_decode($post['media_urls'],true) ?? []; ?>
            <?php if (!empty($imgs)): ?>
            <div class="post-images">
              <?php foreach (array_slice($imgs,0,4) as $img): ?>
              <img src="<?= sc_h(SOCIAL_UPLOAD_URL . '/posts/' . $img) ?>" alt="صورة المنشور" loading="lazy">
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          <?php endif; ?>
          <div class="post-actions">
            <button onclick="toggleLike(<?= $post['id'] ?>,this)">❤️ <?= number_format($post['like_count']) ?></button>
            <a href="<?= SOCIAL_URL ?>/post/<?= $post['id'] ?>">💬 <?= number_format($post['comment_count']) ?></a>
            <button onclick="sharePost(<?= $post['id'] ?>)">🔗 مشاركة</button>
          </div>
        </article>
        <?php endforeach; ?>
        <!-- Pagination -->
        <div style="display:flex;justify-content:center;gap:8px;margin-top:20px">
          <?php if ($page > 1): ?>
            <a href="?tab=<?= $tab ?>&p=<?= $page-1 ?>" class="nav-btn btn-outline">← السابق</a>
          <?php endif; ?>
          <?php if (count($posts) === $limit): ?>
            <a href="?tab=<?= $tab ?>&p=<?= $page+1 ?>" class="nav-btn btn-primary">التالي →</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </main>

  <!-- Sidebar -->
  <aside class="sidebar">
    <?php if (!$me): ?>
    <div class="sidebar-card" style="text-align:center">
      <div style="font-size:2rem;margin-bottom:8px">🌐</div>
      <p style="font-size:.88rem;margin-bottom:14px;color:var(--muted)">انضم إلى <?= sc_h(SOCIAL_NAME) ?> وتفاعل مع المجتمع</p>
      <a href="<?= SOCIAL_URL ?>/auth/register" class="nav-btn btn-primary" style="display:block;text-align:center;text-decoration:none;margin-bottom:8px">إنشاء حساب</a>
      <a href="<?= SOCIAL_URL ?>/auth/login" class="nav-btn btn-outline" style="display:block;text-align:center;text-decoration:none">تسجيل الدخول</a>
    </div>
    <?php endif; ?>

    <?php if (!empty($suggestions)): ?>
    <div class="sidebar-card">
      <h3>اقتراحات لك</h3>
      <?php foreach ($suggestions as $s): ?>
      <div class="suggest-row">
        <img src="<?= sc_avatar_url($s['avatar']) ?>" class="suggest-avatar" alt="<?= sc_h($s['username']) ?>">
        <div class="suggest-info">
          <div class="name">
            <?= sc_h($s['display_name'] ?: $s['username']) ?>
            <?php if ($s['verified']): ?><span style="color:var(--accent)">✓</span><?php endif; ?>
          </div>
          <div class="count"><?= number_format($s['follower_count']) ?> متابع</div>
        </div>
        <button class="suggest-follow" data-id="<?= $s['id'] ?>" onclick="followUser(<?= $s['id'] ?>,this)">متابعة</button>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="sidebar-card">
      <h3>الخطط المميزة</h3>
      <div style="font-size:.85rem;line-height:1.7">
        <div style="margin-bottom:8px">✅ <strong>توثيق</strong> — <span style="color:var(--gold)">$7/شهر</span></div>
        <div style="margin-bottom:8px">⚡ <strong>بلس</strong> — $5/شهر</div>
        <div style="margin-bottom:8px">🔥 <strong>برو</strong> — $10/شهر</div>
        <div style="margin-bottom:12px">👑 <strong>بريميوم</strong> — $20/شهر</div>
        <a href="<?= SOCIAL_URL ?>/upgrade" class="nav-btn btn-primary" style="display:block;text-align:center;text-decoration:none">ترقية الحساب</a>
      </div>
    </div>
  </aside>
</div>

<script>
const ME = <?= $me ? $me['id'] : 'null' ?>;
const BASE = '<?= SOCIAL_URL ?>/api';

async function apiFetch(path, body) {
  const r = await fetch(BASE + path, {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(body)
  });
  return r.json();
}

async function toggleLike(postId, btn) {
  if (!ME) { location.href = '<?= SOCIAL_URL ?>/auth/login'; return; }
  const d = await apiFetch('/like', {post_id: postId});
  if (d.ok) {
    btn.textContent = '❤️ ' + d.likes;
    btn.style.color = d.liked ? 'var(--red)' : '';
  }
}

async function followUser(uid, btn) {
  if (!ME) { location.href = '<?= SOCIAL_URL ?>/auth/login'; return; }
  const d = await apiFetch('/follow', {user_id: uid});
  if (d.ok) btn.textContent = d.following ? 'متابَع' : 'متابعة';
}

function sharePost(postId) {
  const url = '<?= SOCIAL_URL ?>/post/' + postId;
  if (navigator.share) {
    navigator.share({url});
  } else {
    navigator.clipboard?.writeText(url);
    alert('تم نسخ الرابط');
  }
}

async function submitPost() {
  const text = document.getElementById('new-post-text').value.trim();
  const type = document.getElementById('post-type').value;
  if (!text) return;
  const d = await apiFetch('/post/create', {content: text, post_type: type});
  if (d.ok) location.reload();
  else alert(d.error || 'حدث خطأ');
}
</script>
</body>
</html>
<?php ob_end_flush(); ?>
