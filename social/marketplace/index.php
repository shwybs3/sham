<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = $social_pdo;
$me  = sc_user($pdo);

$q    = trim($_GET['q'] ?? '');
$cat  = trim($_GET['cat'] ?? '');
$type = in_array($_GET['type'] ?? '', ['free','paid']) ? $_GET['type'] : '';
$page = max(1,(int)($_GET['p'] ?? 1));
$lim  = 24;
$off  = ($page-1)*$lim;

$where = ["t.status='published'", "t.is_approved=1"];
$params = [];
if ($q) { $where[] = "MATCH(t.title,t.description) AGAINST(? IN BOOLEAN MODE)"; $params[] = $q . '*'; }
if ($cat) { $where[] = "t.category=?"; $params[] = $cat; }
if ($type) { $where[] = "t.tool_type=?"; $params[] = $type; }
$whereStr = implode(' AND ', $where);

$st = $pdo->prepare("
    SELECT t.*, u.username, u.display_name, u.avatar, u.verified
    FROM marketplace_tools t JOIN users u ON u.id=t.user_id
    WHERE $whereStr
    ORDER BY t.view_count DESC, t.created_at DESC
    LIMIT ? OFFSET ?
");
$st->execute([...$params, $lim, $off]);
$tools = $st->fetchAll();

// Categories
$cats = $pdo->query("SELECT DISTINCT category FROM marketplace_tools WHERE status='published' AND category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>سوق الأدوات — <?= sc_h(SOCIAL_NAME) ?></title>
<meta name="description" content="اكتشف أدوات وسكريبتات قانونية من مطورين عرب">
<link rel="canonical" href="<?= sc_h(SOCIAL_URL) ?>/marketplace">
<link rel="icon" href="/social/assets/favicon.svg" type="image/svg+xml">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0e1117;color:#e6edf3;min-height:100vh}
.nav{background:#161b22;border-bottom:1px solid #30363d;padding:0 20px;height:54px;display:flex;align-items:center;gap:14px}
.nav-brand{font-weight:800;font-size:1.1rem;color:#2f81f7;text-decoration:none}
.nav a{color:#e6edf3;text-decoration:none;font-size:.88rem}
.hero{background:linear-gradient(135deg,#161b22,#0d1117);padding:40px 20px;text-align:center;border-bottom:1px solid #30363d}
.hero h1{font-size:1.8rem;font-weight:800;margin-bottom:8px}
.hero p{color:#8b949e;margin-bottom:20px}
.search-bar{display:flex;gap:8px;max-width:500px;margin:0 auto}
.search-input{flex:1;background:#0e1117;border:1px solid #30363d;border-radius:8px;padding:10px 14px;color:#e6edf3;font-size:.92rem}
.search-input:focus{outline:none;border-color:#2f81f7}
.search-btn{background:#2f81f7;color:#fff;border:none;border-radius:8px;padding:10px 20px;cursor:pointer;font-weight:600}
.container{max-width:1100px;margin:0 auto;padding:24px 16px}
.filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;align-items:center}
.filter-tag{padding:6px 14px;border-radius:20px;border:1px solid #30363d;font-size:.82rem;color:#8b949e;text-decoration:none;transition:all .12s}
.filter-tag:hover,.filter-tag.active{border-color:#2f81f7;color:#2f81f7;background:rgba(47,129,247,.08)}
.btn-publish{margin-right:auto;padding:8px 18px;background:#3fb950;color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:700;cursor:pointer;text-decoration:none}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.tool-card{background:#161b22;border:1px solid #30363d;border-radius:12px;padding:18px;transition:border-color .15s;display:flex;flex-direction:column}
.tool-card:hover{border-color:#2f81f7}
.tool-header{display:flex;gap:12px;align-items:flex-start;margin-bottom:12px}
.tool-icon{width:52px;height:52px;border-radius:10px;background:linear-gradient(135deg,#2f81f7,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.tool-title{font-weight:700;font-size:.95rem;margin-bottom:4px}
.tool-title a{color:#e6edf3;text-decoration:none}
.tool-title a:hover{color:#2f81f7}
.tool-meta{font-size:.78rem;color:#8b949e}
.tool-desc{font-size:.85rem;color:#8b949e;line-height:1.55;flex:1;margin-bottom:12px}
.tool-footer{display:flex;align-items:center;justify-content:space-between;margin-top:auto}
.price-tag{font-size:.82rem;font-weight:700;padding:4px 12px;border-radius:20px}
.price-free{background:rgba(63,185,80,.15);color:#3fb950}
.price-paid{background:rgba(47,129,247,.15);color:#2f81f7}
.dl-count{font-size:.78rem;color:#8b949e}
.btn-get{padding:6px 14px;border-radius:20px;font-size:.8rem;font-weight:600;text-decoration:none;background:#2f81f7;color:#fff}
.empty{text-align:center;padding:60px 20px;color:#8b949e}
.empty .icon{font-size:3rem;margin-bottom:12px}
</style>
</head>
<body>
<nav class="nav">
  <a href="<?= SOCIAL_URL ?>" class="nav-brand">◉ <?= sc_h(SOCIAL_NAME) ?></a>
  <a href="<?= SOCIAL_URL ?>">استكشاف</a>
  <span>·</span>
  <span style="color:#2f81f7;font-weight:600">السوق</span>
  <?php if ($me): ?>
  <a href="<?= SOCIAL_URL ?>/profile/<?= sc_h($me['username']) ?>" style="margin-right:auto">
    <img src="<?= sc_avatar_url($me['avatar']) ?>" style="width:30px;height:30px;border-radius:50%;object-fit:cover;vertical-align:middle">
  </a>
  <?php else: ?>
  <a href="<?= SOCIAL_URL ?>/auth/login" style="margin-right:auto;color:#2f81f7">دخول</a>
  <?php endif; ?>
</nav>

<div class="hero">
  <h1>🛠️ سوق الأدوات</h1>
  <p>اكتشف أدوات وسكريبتات قانونية من مطورين عرب — مجانية ومدفوعة</p>
  <form method="get" class="search-bar">
    <input class="search-input" type="text" name="q" placeholder="ابحث عن أداة..." value="<?= sc_h($q) ?>">
    <?php if ($cat): ?><input type="hidden" name="cat" value="<?= sc_h($cat) ?>"><?php endif; ?>
    <?php if ($type): ?><input type="hidden" name="type" value="<?= sc_h($type) ?>"><?php endif; ?>
    <button class="search-btn" type="submit">بحث</button>
  </form>
</div>

<div class="container">
  <div class="filters">
    <a href="?<?= $q?'q='.urlencode($q).'&':'' ?>" class="filter-tag <?= !$type&&!$cat?'active':'' ?>">الكل</a>
    <a href="?type=free<?= $q?'&q='.urlencode($q):'' ?>" class="filter-tag <?= $type==='free'?'active':'' ?>">مجاني</a>
    <a href="?type=paid<?= $q?'&q='.urlencode($q):'' ?>" class="filter-tag <?= $type==='paid'?'active':'' ?>">مدفوع</a>
    <?php foreach ($cats as $c): ?>
    <a href="?cat=<?= urlencode($c) ?><?= $q?'&q='.urlencode($q):'' ?>" class="filter-tag <?= $cat===$c?'active':'' ?>"><?= sc_h($c) ?></a>
    <?php endforeach; ?>
    <?php if ($me): ?>
    <a href="<?= SOCIAL_URL ?>/marketplace/publish" class="btn-publish">+ نشر أداة</a>
    <?php endif; ?>
  </div>

  <?php if (empty($tools)): ?>
  <div class="empty">
    <div class="icon">🔍</div>
    <p><?= $q ? 'لا توجد نتائج لـ "' . sc_h($q) . '"' : 'لا توجد أدوات بعد — كن أول من ينشر!' ?></p>
    <?php if ($me): ?>
    <a href="<?= SOCIAL_URL ?>/marketplace/publish" style="display:inline-block;margin-top:16px;background:#2f81f7;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:600">+ نشر أداة الآن</a>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <div class="grid">
    <?php foreach ($tools as $t): ?>
    <div class="tool-card">
      <div class="tool-header">
        <div class="tool-icon">🛠️</div>
        <div style="flex:1;min-width:0">
          <div class="tool-title">
            <a href="<?= SOCIAL_URL ?>/marketplace/tool/<?= sc_h($t['slug']) ?>"><?= sc_h($t['title']) ?></a>
          </div>
          <div class="tool-meta">
            <a href="<?= SOCIAL_URL ?>/profile/<?= sc_h($t['username']) ?>" style="color:#2f81f7">@<?= sc_h($t['username']) ?></a>
            <?php if ($t['verified']): ?><span style="color:#2f81f7">✓</span><?php endif; ?>
          </div>
        </div>
      </div>
      <div class="tool-desc"><?= sc_h(mb_substr($t['description'],0,120)) ?><?= mb_strlen($t['description'])>120?'…':'' ?></div>
      <div class="tool-footer">
        <span class="price-tag <?= $t['tool_type']==='free'?'price-free':'price-paid' ?>">
          <?= $t['tool_type']==='free' ? 'مجاني' : '$' . number_format($t['price_usd'],2) ?>
        </span>
        <span class="dl-count">⬇ <?= number_format($t['download_count']) ?></span>
        <a href="<?= SOCIAL_URL ?>/marketplace/tool/<?= sc_h($t['slug']) ?>" class="btn-get">عرض</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <!-- Pagination -->
  <?php if ($page > 1 || count($tools)===$lim): ?>
  <div style="display:flex;justify-content:center;gap:10px;margin-top:28px">
    <?php if ($page > 1): ?>
    <a href="?<?= http_build_query(array_merge($_GET,['p'=>$page-1])) ?>" style="padding:8px 20px;border:1px solid #30363d;border-radius:8px;color:#e6edf3;text-decoration:none">← السابق</a>
    <?php endif; ?>
    <?php if (count($tools)===$lim): ?>
    <a href="?<?= http_build_query(array_merge($_GET,['p'=>$page+1])) ?>" style="padding:8px 20px;background:#2f81f7;color:#fff;border-radius:8px;text-decoration:none">التالي →</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>
