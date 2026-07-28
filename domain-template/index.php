<?php
/*
 * domain-template/index.php
 * Dark professional site template — deployed to each new domain's docroot.
 * Shares the same DB as the main site; filters by the domain's category_slug
 * or shows all published apps if none is configured.
 * DO NOT put credentials here — the live site must copy config.local.php
 * into the domain's folder or symlink from the main installation.
 */

// ── Config / DB bootstrap ───────────────────────────────────────────────────
// Walk up from docroot up to 4 levels to find the main site's config.php.
// For cPanel subdomains (docroot: /home/user/sub.domain.com/public_html)
// the main site config is at /home/user/public_html/config.php — 3 levels up.
$_b = $_SERVER['DOCUMENT_ROOT'];
$_cfgFound = false;
for ($_i = 0; $_i < 5; $_i++) {
    if (file_exists($_b . '/config.php')) {
        if (file_exists($_b . '/config.local.php')) require_once $_b . '/config.local.php';
        require_once $_b . '/config.php';
        $_cfgFound = true;
        break;
    }
    $_b = dirname($_b);
}
if (!$_cfgFound || !isset($pdo)) { http_response_code(503); die('Configuration not found'); }

// ── Domain settings ─────────────────────────────────────────────────────────
$domainHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
$domainRow  = null;
try {
    $s = $pdo->prepare("SELECT * FROM domains WHERE full_domain=? LIMIT 1");
    $s->execute([$domainHost]);
    $domainRow = $s->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable) {}

$catSlug    = $domainRow['category_slug'] ?? '';
$siteName   = defined('SITE_NAME') ? SITE_NAME : 'AppStore';
$siteUrl    = 'https://' . $domainHost;

// ── Query apps ──────────────────────────────────────────────────────────────
$page   = max(1, (int)($_GET['p'] ?? 1));
$search = trim($_GET['q'] ?? '');
$perPage = 30;
$offset  = ($page - 1) * $perPage;

try {
    $catId = null;
    if ($catSlug) {
        $s = $pdo->prepare("SELECT id FROM categories WHERE slug=? LIMIT 1");
        $s->execute([$catSlug]);
        $catId = $s->fetchColumn() ?: null;
    }

    $where  = "WHERE a.status='published'";
    $params = [];
    if ($catId) { $where .= " AND a.category_id=?"; $params[] = $catId; }
    if ($search) { $where .= " AND (a.name LIKE ? OR a.short_description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

    $total = (int)$pdo->prepare("SELECT COUNT(*) FROM apps a $where")->execute($params) ?
             $pdo->prepare("SELECT COUNT(*) FROM apps a $where")->execute($params) && true ? 0 : 0 : 0;
    // Proper total count
    $cs = $pdo->prepare("SELECT COUNT(*) FROM apps a $where"); $cs->execute($params);
    $total = (int)$cs->fetchColumn();

    $as = $pdo->prepare("SELECT a.id,a.name,a.slug,a.icon_path,a.short_description,a.rating,a.version,a.download_count,c.name AS cat_name
                         FROM apps a LEFT JOIN categories c ON c.id=a.category_id
                         $where ORDER BY a.updated_at DESC LIMIT $perPage OFFSET $offset");
    $as->execute($params);
    $apps = $as->fetchAll(PDO::FETCH_ASSOC);

    $totalPages = $total ? (int)ceil($total / $perPage) : 1;
} catch (Throwable $e) {
    $apps = []; $total = 0; $totalPages = 1;
}

// ── SEO ─────────────────────────────────────────────────────────────────────
$pageTitle = $catSlug ? ucfirst($catSlug) . ' — ' . $siteName : $siteName;
if ($search) $pageTitle = 'بحث: ' . $search . ' — ' . $siteName;
$pageDesc  = 'أفضل تطبيقات وألعاب أندرويد — تحميل مباشر مجاني';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></title>
<meta name="description" content="<?= htmlspecialchars($pageDesc, ENT_QUOTES) ?>">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= htmlspecialchars($siteUrl, ENT_QUOTES) ?>">
<meta name="robots" content="index,follow">
<link rel="canonical" href="<?= htmlspecialchars($siteUrl . '/' . ($_SERVER['REQUEST_URI'] ?? ''), ENT_QUOTES) ?>">
<style>
/* ── Dark professional theme ── */
:root {
  --bg:        #0a0a0f;
  --surface:   #12121a;
  --surface2:  #1a1a26;
  --border:    rgba(255,255,255,.08);
  --primary:   #6366f1;
  --primary-g: linear-gradient(135deg,#6366f1,#8b5cf6);
  --accent:    #22d3ee;
  --text:      #e2e8f0;
  --text-muted:#64748b;
  --success:   #22c55e;
  --glow:      0 0 30px rgba(99,102,241,.25);
  --glow-sm:   0 0 12px rgba(99,102,241,.2);
  --radius:    12px;
  --radius-sm: 8px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.6;min-height:100vh}
a{color:inherit;text-decoration:none}
img{max-width:100%}

/* ── Animated background particles ── */
body::before{
  content:'';position:fixed;inset:0;
  background:
    radial-gradient(ellipse at 20% 20%,rgba(99,102,241,.12) 0%,transparent 60%),
    radial-gradient(ellipse at 80% 80%,rgba(34,211,238,.08) 0%,transparent 60%);
  pointer-events:none;z-index:0;
  animation:bgpulse 8s ease-in-out infinite alternate;
}
@keyframes bgpulse{from{opacity:.6}to{opacity:1}}

/* ── Header ── */
.site-header{
  position:sticky;top:0;z-index:100;
  background:rgba(10,10,15,.85);
  backdrop-filter:blur(20px);
  border-bottom:1px solid var(--border);
  padding:0 20px;
}
.header-inner{
  max-width:1200px;margin:0 auto;
  display:flex;align-items:center;gap:16px;height:64px;
}
.logo{
  font-size:22px;font-weight:800;
  background:var(--primary-g);-webkit-background-clip:text;-webkit-text-fill-color:transparent;
  letter-spacing:-.5px;flex-shrink:0;
}
.logo span{color:var(--accent);-webkit-text-fill-color:var(--accent)}

.search-wrap{flex:1;max-width:480px;position:relative}
.search-wrap input{
  width:100%;padding:10px 16px 10px 44px;
  background:var(--surface);border:1px solid var(--border);
  border-radius:24px;color:var(--text);font-size:14px;
  transition:border-color .2s,box-shadow .2s;
  outline:none;
}
.search-wrap input:focus{border-color:var(--primary);box-shadow:var(--glow-sm)}
.search-wrap svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted)}

/* ── Hero ── */
.hero{
  position:relative;z-index:1;
  padding:80px 20px 60px;text-align:center;
}
.hero h1{
  font-size:clamp(28px,6vw,56px);font-weight:900;
  line-height:1.1;margin-bottom:16px;
  background:linear-gradient(135deg,#fff 0%,var(--accent) 50%,var(--primary) 100%);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
}
.hero p{font-size:17px;color:var(--text-muted);max-width:560px;margin:0 auto 32px}
.hero-stats{display:flex;justify-content:center;gap:32px;flex-wrap:wrap}
.hero-stat{text-align:center}
.hero-stat .num{font-size:28px;font-weight:800;color:var(--accent)}
.hero-stat .lbl{font-size:12px;color:var(--text-muted);margin-top:2px}

/* ── App grid ── */
.section{position:relative;z-index:1;max-width:1200px;margin:0 auto;padding:0 20px 60px}
.section-title{
  font-size:20px;font-weight:700;margin-bottom:20px;
  display:flex;align-items:center;gap:10px;
}
.section-title::after{content:'';flex:1;height:1px;background:var(--border)}

.app-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(160px,1fr));
  gap:16px;
}

.app-card{
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--radius);overflow:hidden;
  transition:transform .25s,box-shadow .25s,border-color .25s;
  cursor:pointer;
}
.app-card:hover{
  transform:translateY(-4px);
  box-shadow:var(--glow),0 8px 32px rgba(0,0,0,.4);
  border-color:var(--primary);
}
.app-card-thumb{
  aspect-ratio:1;padding:16px;
  display:flex;align-items:center;justify-content:center;
  background:var(--surface2);position:relative;overflow:hidden;
}
.app-card-thumb::after{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(99,102,241,.1),transparent);
  opacity:0;transition:opacity .25s;
}
.app-card:hover .app-card-thumb::after{opacity:1}
.app-card-thumb img{width:72px;height:72px;border-radius:16px;object-fit:cover}
.app-card-thumb .no-icon{
  width:72px;height:72px;border-radius:16px;
  background:var(--primary-g);
  display:flex;align-items:center;justify-content:center;
  font-size:28px;
}
.app-card-body{padding:12px}
.app-card-name{font-size:13px;font-weight:600;line-height:1.3;margin-bottom:4px;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.app-card-meta{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-muted)}
.app-card-rating{color:#f59e0b}
.app-card-dl{
  width:100%;margin-top:10px;padding:8px;
  background:var(--primary-g);color:#fff;border:none;border-radius:var(--radius-sm);
  font-size:12px;font-weight:600;cursor:pointer;
  transition:opacity .2s,transform .1s;
}
.app-card-dl:hover{opacity:.9;transform:scale(.98)}

/* ── Pagination ── */
.pagination{display:flex;justify-content:center;gap:8px;padding:20px 0 40px;flex-wrap:wrap}
.pag-btn{
  padding:8px 16px;border-radius:var(--radius-sm);
  border:1px solid var(--border);background:var(--surface);
  color:var(--text);font-size:13px;cursor:pointer;transition:all .2s;
}
.pag-btn:hover,.pag-btn.active{background:var(--primary);border-color:var(--primary);color:#fff}

/* ── Footer ── */
.site-footer{
  border-top:1px solid var(--border);
  background:var(--surface);
  padding:32px 20px;text-align:center;
  color:var(--text-muted);font-size:13px;
  position:relative;z-index:1;
}
.footer-links{display:flex;justify-content:center;gap:20px;flex-wrap:wrap;margin-bottom:12px}
.footer-links a{color:var(--text-muted);transition:color .2s}
.footer-links a:hover{color:var(--accent)}

/* ── Floating search toggle for mobile ── */
@media(max-width:600px){
  .hero h1{font-size:28px}
  .app-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px}
}

/* ── Ripple effect on card click ── */
.ripple-host{position:relative;overflow:hidden}
.ripple{
  position:absolute;border-radius:50%;
  width:10px;height:10px;
  background:rgba(99,102,241,.4);
  animation:ripple-out .5s ease-out forwards;
  pointer-events:none;transform:translate(-50%,-50%);
}
@keyframes ripple-out{to{width:200px;height:200px;opacity:0}}

/* ── Scroll reveal ── */
.reveal{opacity:0;transform:translateY(20px);transition:opacity .5s,transform .5s}
.reveal.visible{opacity:1;transform:none}
</style>
</head>
<body>

<!-- Header -->
<header class="site-header">
  <div class="header-inner">
    <a href="/" class="logo"><?= htmlspecialchars($siteName, ENT_QUOTES) ?></a>
    <form class="search-wrap" method="get" action="/">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="search" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>" placeholder="ابحث عن تطبيق أو لعبة...">
    </form>
  </div>
</header>

<?php if (!$search && $page === 1): ?>
<!-- Hero -->
<section class="hero reveal">
  <h1><?= $catSlug ? htmlspecialchars(ucfirst($catSlug), ENT_QUOTES) . '<br>للأندرويد' : 'أفضل تطبيقات<br>الأندرويد' ?></h1>
  <p>مكتبة ضخمة من التطبيقات والألعاب — تحميل مجاني وسريع بدون أي رسوم</p>
  <div class="hero-stats">
    <div class="hero-stat"><div class="num"><?= number_format($total) ?>+</div><div class="lbl">تطبيق متاح</div></div>
    <div class="hero-stat"><div class="num">مجاناً</div><div class="lbl">100% مجاني</div></div>
    <div class="hero-stat"><div class="num">APK</div><div class="lbl">تحميل مباشر</div></div>
  </div>
</section>
<?php endif; ?>

<!-- App grid -->
<main class="section">
  <?php if ($search): ?>
  <div class="section-title">نتائج البحث عن: <em><?= htmlspecialchars($search, ENT_QUOTES) ?></em> (<?= $total ?>)</div>
  <?php else: ?>
  <div class="section-title">📱 <?= $catSlug ? htmlspecialchars(ucfirst($catSlug), ENT_QUOTES) : 'جميع التطبيقات' ?></div>
  <?php endif; ?>

  <?php if (empty($apps)): ?>
  <div style="text-align:center;padding:60px 20px;color:var(--text-muted)">
    <div style="font-size:48px;margin-bottom:12px">📭</div>
    <p>لا توجد تطبيقات <?= $search ? 'تطابق بحثك' : 'حتى الآن' ?></p>
  </div>
  <?php else: ?>
  <div class="app-grid">
    <?php foreach ($apps as $app): ?>
    <?php
      $icon = !empty($app['icon_path']) ? (defined('SITE_URL') ? rtrim(SITE_URL,'/').'/'.$app['icon_path'] : '/'.$app['icon_path']) : '';
      $appLink = defined('SITE_URL') ? rtrim(SITE_URL,'/')  . '/app/' . $app['slug'] : '/app/' . $app['slug'];
    ?>
    <a href="<?= htmlspecialchars($appLink, ENT_QUOTES) ?>" class="app-card ripple-host reveal">
      <div class="app-card-thumb">
        <?php if ($icon): ?>
        <img src="<?= htmlspecialchars($icon, ENT_QUOTES) ?>" alt="<?= htmlspecialchars($app['name'], ENT_QUOTES) ?>" loading="lazy">
        <?php else: ?>
        <div class="no-icon">📱</div>
        <?php endif; ?>
      </div>
      <div class="app-card-body">
        <div class="app-card-name"><?= htmlspecialchars($app['name'], ENT_QUOTES) ?></div>
        <div class="app-card-meta">
          <?php if ($app['rating']): ?>
          <span class="app-card-rating">★</span>
          <span><?= number_format((float)$app['rating'], 1) ?></span>
          <?php endif; ?>
          <?php if ($app['download_count']): ?>
          <span>· <?= number_format((int)$app['download_count']) ?> ⬇</span>
          <?php endif; ?>
        </div>
        <button class="app-card-dl">⬇ تحميل</button>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
    <a class="pag-btn" href="?<?= http_build_query(array_merge($_GET, ['p'=>$page-1])) ?>">◄ السابق</a>
    <?php endif; ?>
    <?php for ($pg = max(1,$page-2); $pg <= min($totalPages,$page+2); $pg++): ?>
    <a class="pag-btn <?= $pg===$page?'active':'' ?>" href="?<?= http_build_query(array_merge($_GET, ['p'=>$pg])) ?>"><?= $pg ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
    <a class="pag-btn" href="?<?= http_build_query(array_merge($_GET, ['p'=>$page+1])) ?>">التالي ►</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</main>

<!-- Footer -->
<footer class="site-footer">
  <div class="footer-links">
    <a href="<?= htmlspecialchars(defined('SITE_URL')?SITE_URL:'/', ENT_QUOTES) ?>">الموقع الرئيسي</a>
    <a href="<?= htmlspecialchars(defined('SITE_URL')?SITE_URL.'/privacy-policy':'/', ENT_QUOTES) ?>">سياسة الخصوصية</a>
    <a href="<?= htmlspecialchars(defined('SITE_URL')?SITE_URL.'/terms':'/', ENT_QUOTES) ?>">شروط الاستخدام</a>
    <a href="<?= htmlspecialchars(defined('SITE_URL')?SITE_URL.'/contact':'/', ENT_QUOTES) ?>">تواصل معنا</a>
  </div>
  <p>© <?= date('Y') ?> <?= htmlspecialchars($siteName, ENT_QUOTES) ?> — جميع الحقوق محفوظة</p>
</footer>

<script>
// ── Scroll reveal ──────────────────────────────────────────────────────────
(function(){
  var obs = new IntersectionObserver(function(entries){
    entries.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('visible'); obs.unobserve(e.target); }});
  },{threshold:0.05});
  document.querySelectorAll('.reveal').forEach(function(el){ obs.observe(el); });
})();

// ── Ripple effect ──────────────────────────────────────────────────────────
document.querySelectorAll('.ripple-host').forEach(function(el){
  el.addEventListener('click',function(e){
    var r = document.createElement('span');
    r.className='ripple';
    var rect = el.getBoundingClientRect();
    r.style.left=(e.clientX-rect.left)+'px';
    r.style.top=(e.clientY-rect.top)+'px';
    el.appendChild(r);
    setTimeout(function(){ r.remove(); },500);
  });
});

// ── Animate hero stats counter ─────────────────────────────────────────────
document.querySelectorAll('.hero-stat .num').forEach(function(el){
  var txt = el.textContent;
  var num = parseInt(txt.replace(/\D/g,''));
  if (!num || num < 10) return;
  var start=0, dur=1200, step=16;
  var timer=setInterval(function(){
    start += Math.max(1, Math.floor(num/dur*step));
    if(start>=num){el.textContent=txt;clearInterval(timer);return;}
    el.textContent = start.toLocaleString('ar');
  },step);
});
</script>
</body>
</html>
