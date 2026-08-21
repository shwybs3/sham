<?php
/* ═══════════════════════════════════════════════════════════
   blog.yassota.com — Dedicated blog/articles hub subdomain
   Full listing + search + trending articles + trending searches,
   BlogPosting-list CollectionPage JSON-LD, local legal pages,
   sitemap.xml — AdSense-compliant standalone mini-site.
   Same publishing mechanism as the app subdomains (copied via
   admin-subdomains.php's tool_sub action into public_html).
   ═══════════════════════════════════════════════════════════ */

$host = $_SERVER['HTTP_HOST'] ?? 'blog.yassota.com';
$page = $_GET['page'] ?? '';

$config_path = dirname(__DIR__, 2) . '/config.php';
if (!file_exists($config_path)) { http_response_code(503); echo 'Service unavailable'; exit; }
require_once $config_path; // gives us $pdo, BLOG_TYPES, blog_type_label(), blog_post_url(),
                            // blog_trending_posts(), trending_searches(), render_blog_body()

if ($page === 'sitemap') { serve_hub_sitemap($pdo, $host); exit; }
if (in_array($page, ['privacy', 'terms', 'about', 'contact', 'dmca'])) {
    serve_hub_legal_page($page);
    exit;
}

// ── Query params ─────────────────────────────────────────────
$type    = trim($_GET['type'] ?? '');
if ($type !== '' && !isset(BLOG_TYPES[$type])) $type = '';
$q       = trim($_GET['q'] ?? '');
$pageNum = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;
$offset  = ($pageNum - 1) * $perPage;

$where  = "WHERE status='published'";
$params = [];
if ($type !== '') { $where .= " AND type=?"; $params[] = $type; }
if ($q !== '')     { $where .= " AND (title LIKE ? OR excerpt LIKE ?)"; $params[] = "%{$q}%"; $params[] = "%{$q}%"; }

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM blog_posts $where");
$countStmt->execute($params);
$totalPosts = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalPosts / $perPage));

$stmt = $pdo->prepare("SELECT * FROM blog_posts $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Log this search the same way the main site does, so it feeds the
// trending-searches widget both here and on the main site.
if ($q !== '' && mb_strlen($q) >= 2) {
    try { $pdo->prepare("INSERT INTO page_events (event_type, meta) VALUES ('search', ?)")->execute([mb_substr($q, 0, 255)]); } catch (Throwable $e) {}
}

$trending      = blog_trending_posts($pdo, 8, 14);
$topSearches   = trending_searches($pdo, 10, 7);
$totalArticles = (int)($pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status='published'")->fetchColumn() ?: 0);

$hubTitle = $q !== '' ? "نتائج البحث عن \"{$q}\"" : ($type !== '' ? blog_type_label($type) : 'مدونة يسوتا');
$pageTitle = $q !== '' || $type !== '' || $pageNum > 1
    ? "{$hubTitle} — مدونة يسوتا | blog.yassota.com"
    : 'مدونة يسوتا — مقالات وشروحات وأخبار عربية أصيلة';
$metaDesc = 'مقالات وشروحات وأخبار عربية أصيلة عن التطبيقات والتقنية — بدون ترجمة آلية، مكتوبة ومراجعة بعناية. تصفّح، ابحث، وتابع الأكثر رواجاً على مدونة يسوتا.';

$schema = [
    '@context'    => 'https://schema.org',
    '@type'       => 'CollectionPage',
    'name'        => $pageTitle,
    'description' => $metaDesc,
    'url'         => 'https://' . $host . '/',
    'inLanguage'  => 'ar',
    'isPartOf'    => ['@type' => 'WebSite', 'name' => 'yassota', 'url' => 'https://yassota.com/'],
    'hasPart'     => array_map(fn($p) => [
        '@type'         => 'BlogPosting',
        'headline'      => $p['title'],
        'url'           => blog_post_url($p['slug']),
        'datePublished' => date('Y-m-d', strtotime($p['created_at'])),
    ], array_slice($posts, 0, 10)),
];
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="<?= h($metaDesc) ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://<?= h($host) ?>/<?= $type ? '?type=' . rawurlencode($type) : '' ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= h($pageTitle) ?>">
<meta property="og:description" content="<?= h($metaDesc) ?>">
<meta property="og:url" content="https://<?= h($host) ?>/">
<meta property="og:site_name" content="مدونة يسوتا">
<meta property="og:locale" content="ar_AR">
<meta name="twitter:card" content="summary_large_image">
<link rel="sitemap" type="application/xml" href="https://<?= h($host) ?>/sitemap.xml">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%230ea5e9'/><text y='.75em' font-size='60' x='18'>📝</text></svg>">
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<style>
:root{--bg:#f4f7fb;--surface:#fff;--text:#0f172a;--muted:#64748b;--cyan:#0ea5e9;--purple:#7c3aed;--hdr:#0c1e36;--line:rgba(15,23,42,.08)}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Cairo','Tajawal',system-ui,sans-serif;background:var(--bg);color:var(--text);direction:rtl;line-height:1.7;-webkit-font-smoothing:antialiased}
a{color:inherit}

header{background:var(--hdr);padding:12px 20px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 1px 12px rgba(0,0,0,.35);gap:14px;flex-wrap:wrap}
.logo{font-family:monospace;font-size:15px;font-weight:800;letter-spacing:1px;text-decoration:none;display:flex;align-items:center;gap:8px;flex-shrink:0}
.logo-yas{background:linear-gradient(135deg,#22d3ee,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.logo-sota{background:linear-gradient(135deg,#7c3aed,#f97316);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.logo-tag{background:rgba(14,165,233,.15);border:1px solid rgba(14,165,233,.3);color:#7dd3fc;font-size:10px;font-weight:800;padding:2px 8px;border-radius:20px;font-family:'Cairo',sans-serif;letter-spacing:0}
nav.top-nav{display:flex;gap:4px;flex-wrap:wrap}
nav.top-nav a{color:rgba(226,232,240,.5);font-size:12px;text-decoration:none;padding:5px 9px;border-radius:7px;transition:all .15s}
nav.top-nav a:hover,nav.top-nav a.active{color:#38bdf8;background:rgba(14,165,233,.1)}

/* Hero */
.hero{background:linear-gradient(140deg,var(--hdr) 0%,#111833 55%,#18103a 100%);padding:46px 20px 34px;text-align:center;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;top:-30%;right:-5%;width:320px;height:320px;border-radius:50%;background:radial-gradient(circle,rgba(14,165,233,.12),transparent 70%);pointer-events:none}
.hero-title{font-size:clamp(24px,4.5vw,34px);font-weight:900;color:#fff;margin-bottom:10px;position:relative;z-index:1;text-shadow:0 2px 10px rgba(0,0,0,.3)}
.hero-sub{font-size:14px;color:rgba(226,232,240,.6);max-width:600px;margin:0 auto 24px;position:relative;z-index:1}
.hero-stat{font-family:monospace;color:#38bdf8;font-weight:800}
.search-box{max-width:520px;margin:0 auto;position:relative;z-index:1}
.search-box form{display:flex;gap:8px}
.search-box input{flex:1;padding:13px 18px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#fff;font-family:inherit;font-size:14px;outline:none;transition:border-color .15s}
.search-box input::placeholder{color:rgba(226,232,240,.4)}
.search-box input:focus{border-color:#38bdf8}
.search-box button{padding:13px 22px;border-radius:14px;border:none;background:linear-gradient(135deg,var(--purple),#4f46e5);color:#fff;font-weight:800;font-size:14px;cursor:pointer;white-space:nowrap;box-shadow:0 6px 20px rgba(124,58,237,.4)}

/* Trending searches strip */
.trend-strip{max-width:1080px;margin:18px auto 0;padding:0 20px;position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:7px;justify-content:center}
.trend-chip{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(226,232,240,.75);font-size:12px;padding:5px 12px;border-radius:20px;text-decoration:none;transition:all .15s}
.trend-chip:hover{border-color:#38bdf8;color:#7dd3fc}
.trend-chip b{color:#fb923c;font-weight:800}

.wrap{max-width:1080px;margin:0 auto;padding:30px 18px}

/* Category chips */
.cat-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:26px}
.cat-chip{display:inline-block;padding:8px 16px;border-radius:20px;border:1px solid var(--line);background:var(--surface);color:var(--muted);font-size:13px;font-weight:700;text-decoration:none;transition:all .15s}
.cat-chip:hover{border-color:var(--cyan);color:var(--cyan)}
.cat-chip.active{background:linear-gradient(135deg,var(--cyan),var(--purple));color:#fff;border-color:transparent}

.section-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;gap:10px;flex-wrap:wrap}
.section-title{font-size:18px;font-weight:900;display:flex;align-items:center;gap:8px}
.section-count{font-family:monospace;font-size:12px;color:var(--muted)}

/* Trending articles — horizontal scroll */
.trend-scroll{display:flex;gap:14px;overflow-x:auto;padding-bottom:10px;margin-bottom:34px;scroll-snap-type:x proximity}
.trend-scroll::-webkit-scrollbar{height:5px}
.trend-scroll::-webkit-scrollbar-thumb{background:var(--line);border-radius:3px}
.trend-card{flex:0 0 240px;scroll-snap-align:start;background:var(--surface);border:1px solid var(--line);border-radius:16px;overflow:hidden;text-decoration:none;color:inherit;transition:transform .18s,box-shadow .18s;position:relative}
.trend-card:hover{transform:translateY(-3px);box-shadow:0 10px 26px rgba(15,23,42,.1)}
.trend-rank{position:absolute;top:10px;right:10px;width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--purple),#4f46e5);color:#fff;font-weight:900;font-size:13px;display:flex;align-items:center;justify-content:center;z-index:2;box-shadow:0 3px 10px rgba(0,0,0,.25)}
.trend-cover{width:100%;height:130px;object-fit:cover;background:linear-gradient(135deg,var(--hdr),#2d1b69);display:flex;align-items:center;justify-content:center;font-size:30px}
.trend-body{padding:13px}
.trend-title{font-size:13.5px;font-weight:800;line-height:1.4;margin-bottom:6px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.trend-meta{font-size:11px;color:var(--muted);display:flex;align-items:center;gap:5px}

/* Main grid */
.blog-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:18px;margin-bottom:30px}
.blog-card{background:var(--surface);border:1px solid var(--line);border-radius:16px;overflow:hidden;text-decoration:none;color:inherit;transition:transform .18s,box-shadow .18s;display:flex;flex-direction:column}
.blog-card:hover{transform:translateY(-3px);box-shadow:0 10px 26px rgba(15,23,42,.08)}
.blog-card-img{width:100%;height:160px;object-fit:cover;background:linear-gradient(135deg,var(--hdr),#2d1b69);display:flex;align-items:center;justify-content:center;font-size:34px}
.blog-card-body{padding:16px}
.blog-card-type{display:inline-block;font-size:10.5px;font-weight:800;color:var(--cyan);background:rgba(14,165,233,.08);padding:3px 10px;border-radius:20px;margin-bottom:9px;letter-spacing:.3px}
.blog-card-title{font-size:15px;font-weight:800;line-height:1.45;margin-bottom:7px}
.blog-card-excerpt{font-size:12.5px;color:var(--muted);line-height:1.7;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.blog-card-date{font-size:11px;color:var(--muted);margin-top:10px;display:flex;align-items:center;gap:5px}

.empty{text-align:center;padding:70px 20px;color:var(--muted)}
.empty p{font-size:16px;margin-bottom:14px}
.empty a{color:var(--cyan);font-weight:700;text-decoration:none}

/* Pagination */
.pager{display:flex;justify-content:center;gap:6px;flex-wrap:wrap;margin-top:10px}
.pager a,.pager span{display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 10px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:700;color:var(--text);background:var(--surface);border:1px solid var(--line)}
.pager a:hover{border-color:var(--cyan);color:var(--cyan)}
.pager span.current{background:linear-gradient(135deg,var(--cyan),var(--purple));color:#fff;border-color:transparent}

footer{background:var(--hdr);color:rgba(226,232,240,.45);text-align:center;padding:28px 20px 22px;font-size:12.5px;margin-top:30px}
footer a{color:rgba(226,232,240,.4);text-decoration:none;margin:0 5px}
footer a:hover{color:var(--cyan)}
.footer-legal{margin-top:12px;display:flex;flex-wrap:wrap;justify-content:center;gap:3px 8px;padding-top:12px;border-top:1px solid rgba(255,255,255,.06);max-width:520px;margin-inline:auto}
</style>
</head>
<body>

<header>
  <a href="https://<?= h($host) ?>/" class="logo">
    <span class="logo-yas">yas</span><span class="logo-sota">sota</span>
    <span class="logo-tag">مدونة</span>
  </a>
  <nav class="top-nav">
    <a href="https://yassota.com">الموقع الرئيسي</a>
    <a href="https://<?= h($host) ?>/">أحدث المقالات</a>
    <a href="?page=about">من نحن</a>
    <a href="?page=contact">تواصل</a>
  </nav>
</header>

<div class="hero">
  <h1 class="hero-title">📝 مدونة يسوتا</h1>
  <p class="hero-sub">
    <span class="hero-stat"><?= number_format($totalArticles) ?></span>
    مقال وشرح وخبر عربي أصيل — بدون ترجمة آلية، مكتوب ومراجَع بعناية
  </p>
  <div class="search-box">
    <form method="get" action="https://<?= h($host) ?>/">
      <input type="search" name="q" value="<?= h($q) ?>" placeholder="ابحث في المدونة...">
      <button type="submit">🔍 بحث</button>
    </form>
  </div>
  <?php if ($topSearches): ?>
  <div class="trend-strip">
    <span class="trend-chip" style="background:none;border:none;color:rgba(226,232,240,.4)">🔥 الأكثر بحثاً:</span>
    <?php foreach ($topSearches as $s): ?>
    <a class="trend-chip" href="?q=<?= rawurlencode($s['term']) ?>"><?= h(mb_strimwidth($s['term'], 0, 28, '…')) ?> <b>(<?= (int)$s['cnt'] ?>)</b></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<div class="wrap">

  <div class="cat-chips">
    <a href="https://<?= h($host) ?>/" class="cat-chip <?= $type === '' && $q === '' ? 'active' : '' ?>">الكل</a>
    <?php foreach (BLOG_TYPES as $t => $label): if ($t === 'code-page') continue; ?>
    <a href="?type=<?= rawurlencode($t) ?>" class="cat-chip <?= $type === $t ? 'active' : '' ?>"><?= h($label) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($q === '' && $type === '' && $pageNum === 1 && $trending): ?>
  <div class="section-head">
    <span class="section-title">🔥 الأكثر قراءة</span>
  </div>
  <div class="trend-scroll">
    <?php foreach ($trending as $i => $tp): ?>
    <a href="<?= h(blog_post_url($tp['slug'])) ?>" class="trend-card" data-hardnav="1">
      <span class="trend-rank">#<?= $i + 1 ?></span>
      <?php if (!empty($tp['cover_image'])): ?>
      <img src="https://yassota.com/<?= h(ltrim($tp['cover_image'], '/')) ?>" alt="<?= h($tp['title']) ?>" class="trend-cover" loading="lazy" onerror="this.style.display='none'">
      <?php else: ?>
      <div class="trend-cover">📄</div>
      <?php endif; ?>
      <div class="trend-body">
        <div class="trend-title"><?= h($tp['title']) ?></div>
        <div class="trend-meta">👁 <?= number_format((int)($tp['view_count'] ?? 0)) ?> مشاهدة · <?= h(blog_type_label($tp['type'])) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="section-head">
    <span class="section-title"><?= h($hubTitle) ?></span>
    <span class="section-count"><?= number_format($totalPosts) ?> مقال</span>
  </div>

  <?php if (!$posts): ?>
  <div class="empty">
    <p><?= $q !== '' ? 'لا نتائج لبحثك — جرّب كلمات أخرى' : 'لا توجد مقالات في هذا القسم بعد' ?></p>
    <a href="https://<?= h($host) ?>/">← عرض كل المقالات</a>
  </div>
  <?php else: ?>
  <div class="blog-grid">
    <?php foreach ($posts as $p): ?>
    <a href="<?= h(blog_post_url($p['slug'])) ?>" class="blog-card" data-hardnav="1">
      <?php if (!empty($p['cover_image'])): ?>
      <img src="https://yassota.com/<?= h(ltrim($p['cover_image'], '/')) ?>" alt="<?= h($p['title']) ?>" class="blog-card-img" loading="lazy" onerror="this.outerHTML='<div class=&quot;blog-card-img&quot;>📄</div>'">
      <?php else: ?>
      <div class="blog-card-img">📄</div>
      <?php endif; ?>
      <div class="blog-card-body">
        <span class="blog-card-type"><?= h(blog_type_label($p['type'])) ?></span>
        <div class="blog-card-title"><?= h($p['title']) ?></div>
        <?php if ($p['excerpt']): ?>
        <p class="blog-card-excerpt"><?= h(mb_strimwidth($p['excerpt'], 0, 110, '…')) ?></p>
        <?php endif; ?>
        <div class="blog-card-date">📅 <?= date('Y-m-d', strtotime($p['created_at'])) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($totalPages > 1):
    $qs = fn($n) => http_build_query(array_filter(['type' => $type, 'q' => $q, 'p' => $n > 1 ? $n : null]));
  ?>
  <div class="pager">
    <?php if ($pageNum > 1): ?><a href="?<?= $qs($pageNum - 1) ?>">‹ السابق</a><?php endif; ?>
    <?php for ($i = max(1, $pageNum - 2); $i <= min($totalPages, $pageNum + 2); $i++): ?>
      <?php if ($i === $pageNum): ?><span class="current"><?= $i ?></span>
      <?php else: ?><a href="?<?= $qs($i) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
    <?php if ($pageNum < $totalPages): ?><a href="?<?= $qs($pageNum + 1) ?>">التالي ›</a><?php endif; ?>
  </div>
  <?php endif; ?>

</div>

<footer>
  <p>© <?= date('Y') ?> <a href="https://yassota.com">yassota.com</a> — محتوى عربي حصري، جميع الحقوق محفوظة</p>
  <nav class="footer-legal" aria-label="الصفحات القانونية">
    <a href="?page=about">من نحن</a>
    <a href="?page=privacy">الخصوصية</a>
    <a href="?page=terms">الشروط</a>
    <a href="?page=contact">تواصل</a>
    <a href="?page=dmca">DMCA</a>
    <a href="/sitemap.xml">Sitemap</a>
    <a href="https://yassota.com/blog">المدونة الرئيسية</a>
    <a href="https://yassota.com">yassota.com</a>
  </nav>
</footer>

</body>
</html>
<?php

function serve_hub_sitemap(PDO $pdo, string $host): void {
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: public, max-age=1800');
    $base = 'https://' . $host;
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    echo "  <url><loc>{$base}/</loc><changefreq>daily</changefreq><priority>1.0</priority></url>\n";
    foreach (['about', 'privacy', 'terms', 'contact', 'dmca'] as $p) {
        echo "  <url><loc>{$base}/?page={$p}</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>\n";
    }
    try {
        $rows = $pdo->query("SELECT slug, updated_at FROM blog_posts WHERE status='published' ORDER BY created_at DESC LIMIT 5000")->fetchAll();
        foreach ($rows as $r) {
            $loc = htmlspecialchars(blog_post_url($r['slug']), ENT_QUOTES);
            $mod = date('Y-m-d', strtotime($r['updated_at'] ?? 'now'));
            echo "  <url><loc>{$loc}</loc><lastmod>{$mod}</lastmod><changefreq>weekly</changefreq><priority>0.6</priority></url>\n";
        }
    } catch (Throwable $e) {}
    echo '</urlset>';
}

function serve_hub_legal_page(string $page): void {
    $today = date('Y-m-d');
    $pages = [
        'about' => [
            'title' => 'من نحن — مدونة يسوتا',
            'h'     => 'عن مدونة يسوتا',
            'body'  => '<p><strong>مدونة يسوتا</strong> (blog.yassota.com) هي القسم التحريري لمنصة <a href="https://yassota.com">يسوتا</a> — مقالات وشروحات وأخبار تقنية بالعربية الفصحى، مكتوبة أصالةً لا مترجمة آلياً.</p>
                        <p>نغطي: شروحات استخدام التطبيقات والأدوات، أخبار التقنية، مقارنات بين التطبيقات، وقوائم "أفضل" مبنية على تجربة فعلية لا نسخ ولصق.</p>
                        <p>كل مقال يمر بمراجعة قبل النشر للتأكد من دقة المعلومات ووضوح الشرح.</p>
                        <p><a href="https://yassota.com/about">المزيد عن يسوتا ←</a></p>',
        ],
        'privacy' => [
            'title' => 'سياسة الخصوصية — مدونة يسوتا',
            'h'     => 'سياسة الخصوصية',
            'body'  => '<p>آخر تحديث: ' . $today . '</p>
                        <p>لا نجمع بيانات شخصية من زوار هذه المدونة الفرعية بخلاف ما يُجمع تلقائياً لأغراض إحصائية مجمّعة (صفحة مُشاهَدة، مصطلح بحث).</p>
                        <p>قد تظهر إعلانات <strong>Google AdSense</strong> على هذه الصفحة، تخضع <a href="https://policies.google.com/privacy">لسياسة خصوصية جوجل</a>.</p>
                        <p><a href="https://yassota.com/privacy-policy">سياسة الخصوصية الكاملة لمنصة يسوتا ←</a></p>',
        ],
        'terms' => [
            'title' => 'شروط الاستخدام — مدونة يسوتا',
            'h'     => 'شروط الاستخدام',
            'body'  => '<p>باستخدامك مدونة يسوتا توافق على <a href="https://yassota.com/terms">شروط استخدام يسوتا</a> الكاملة.</p>
                        <p>المحتوى المنشور لأغراض معلوماتية عامة، ولا يُعدّ استشارة تقنية متخصصة ملزمة.</p>',
        ],
        'contact' => [
            'title' => 'تواصل معنا — مدونة يسوتا',
            'h'     => 'تواصل معنا',
            'body'  => '<p>لتصحيح معلومة في مقال، أو اقتراح موضوع، أو أي استفسار: <a href="https://yassota.com/contact">نموذج التواصل على يسوتا ←</a></p>',
        ],
        'dmca' => [
            'title' => 'DMCA — مدونة يسوتا',
            'h'     => 'إشعار DMCA',
            'body'  => '<p>لإرسال إشعار انتهاك حقوق نشر بخصوص محتوى منشور هنا: <a href="https://yassota.com/dmca">نموذج DMCA الكامل ←</a></p>',
        ],
    ];
    $p = $pages[$page] ?? $pages['about'];
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='utf-8'>"
        . "<title>" . htmlspecialchars($p['title']) . "</title>"
        . "<meta name='viewport' content='width=device-width,initial-scale=1'>"
        . "<meta name='robots' content='index,follow'>"
        . "<style>*{box-sizing:border-box}body{font-family:Cairo,system-ui,sans-serif;background:#f4f7fb;color:#0f172a;direction:rtl;line-height:1.8;padding:0}"
        . "header{background:#0c1e36;padding:14px 20px;display:flex;align-items:center;justify-content:space-between}"
        . ".brand{font-family:monospace;font-weight:800;font-size:15px;color:#e2e8f0;text-decoration:none}"
        . ".wrap{max-width:720px;margin:0 auto;padding:34px 20px}"
        . "h1{font-size:22px;font-weight:800;margin-bottom:16px}"
        . "p{color:#475569;margin-bottom:12px;font-size:14px}"
        . "a{color:#0ea5e9}.back{display:inline-flex;gap:6px;margin-bottom:22px;color:#0ea5e9;text-decoration:none;font-weight:700;font-size:13px}"
        . "footer{background:#0c1e36;color:rgba(226,232,240,.4);text-align:center;padding:18px;font-size:12px;margin-top:40px}footer a{color:rgba(226,232,240,.35);margin:0 6px;text-decoration:none}"
        . "</style></head><body>"
        . "<header><a href='/' class='brand'>مدونة يسوتا</a></header>"
        . "<div class='wrap'><a href='/' class='back'>← العودة للمدونة</a><h1>{$p['h']}</h1>{$p['body']}</div>"
        . "<footer><a href='/'>الرئيسية</a><a href='/?page=privacy'>الخصوصية</a><a href='https://yassota.com'>يسوتا</a></footer>"
        . "</body></html>";
}
