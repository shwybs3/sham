<?php
/* ═══════════════════════════════════════════════════════════
   {slug}.yassota.com — Per-article subdomain landing page
   Full BlogPosting + BreadcrumbList JSON-LD, local legal pages,
   sitemap.xml — AdSense-compliant standalone mini-site
   ═══════════════════════════════════════════════════════════ */

$host = $_SERVER['HTTP_HOST'] ?? '';
$slug = strtolower(explode('.', $host)[0]);
$page = $_GET['page'] ?? '';

if ($page === 'sitemap') { serve_sitemap($host, $slug); exit; }
if (in_array($page, ['privacy','terms','about','contact','dmca'])) {
    serve_legal_page($page, $slug);
    exit;
}

// ── Load post from DB ─────────────────────────────────────────
$config_path = dirname(__DIR__, 2) . '/config.php';
$post = null; $related_posts = [];
if (file_exists($config_path)) {
    require_once $config_path;
    try {
        $pdo2 = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $stmt = $pdo2->prepare(
            "SELECT * FROM blog_posts WHERE slug=? AND status='published' LIMIT 1"
        );
        $stmt->execute([$slug]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        // Track this subdomain view for the trending widget on blog.yassota.com.
        // Reuses the existing 'view' event_type (its ENUM has no 'blog_view'
        // member) and tags the row via meta so it never mixes with app views,
        // which always carry app_id and no meta.
        if ($post) {
            try { $pdo2->prepare("INSERT INTO page_events (event_type, meta) VALUES ('view', ?)")->execute(['blog:' . $post['id']]); } catch (Throwable $e) {}
        }

        if ($post && !empty($post['type'])) {
            $rs = $pdo2->prepare(
                "SELECT title, slug, cover_image, excerpt, created_at
                 FROM blog_posts WHERE type=? AND status='published'
                 AND slug != ? ORDER BY created_at DESC LIMIT 3"
            );
            $rs->execute([$post['type'], $slug]);
            $related_posts = $rs->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {}
}

// ── Computed variables ────────────────────────────────────────
$site_url   = 'https://' . $host;
$main_url   = 'https://yassota.com/blog/' . rawurlencode($slug);
$title      = $post['title']       ?? ucwords(str_replace('-', ' ', $slug));
$excerpt    = $post['excerpt']     ?? ($post['meta_description'] ?? '');
$body       = $post['body']        ?? '';
$cat        = function_exists('blog_type_label') ? blog_type_label($post['type'] ?? 'article') : 'مقال';
$cat_slug   = $post['type']        ?? '';
$cover      = !empty($post['cover_image']) ? 'https://yassota.com/'.ltrim($post['cover_image'],'/') : '';
$author     = 'فريق يسوتا';
$published  = $post['created_at']  ?? date('Y-m-d');
$updated    = $post['updated_at']  ?? $published;
$date_fmt   = date('d/m/Y', strtotime($published));
$read_time  = max(1, (int)ceil(mb_strlen(strip_tags($body ?: $excerpt)) / 1000));

$page_title = $post['seo_title']         ?? "مقال: {$title} | يسوتا";
$meta_desc  = $post['meta_description']  ?? ($excerpt ?: "اقرأ مقال {$title} على منصة يسوتا — محتوى عربي حصري");
$og_image   = $cover ?: 'https://yassota.com/assets/img/og-default.png';

// Dynamic accent
$hue    = abs(crc32($slug)) % 360;
$accent = "hsl({$hue},68%,44%)";

// ── JSON-LD ───────────────────────────────────────────────────
$schema_article = [
    '@context'         => 'https://schema.org',
    '@type'            => 'BlogPosting',
    'headline'         => $title,
    'description'      => $meta_desc,
    'url'              => $site_url . '/',
    'datePublished'    => date('Y-m-d', strtotime($published)),
    'dateModified'     => date('Y-m-d', strtotime($updated)),
    'inLanguage'       => 'ar',
    'author'           => ['@type' => 'Person', 'name' => $author],
    'publisher'        => ['@type' => 'Organization', 'name' => 'yassota.com', 'url' => 'https://yassota.com',
                           'logo' => ['@type' => 'ImageObject', 'url' => 'https://yassota.com/favicon.svg']],
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $site_url . '/'],
];
if ($cover) $schema_article['image'] = ['@type' => 'ImageObject', 'url' => $cover];

$schema_breadcrumb = [
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'يسوتا',   'item' => 'https://yassota.com/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'المدونة', 'item' => 'https://yassota.com/blog'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $title,    'item' => $site_url . '/'],
    ],
];
if ($cat_slug) {
    array_splice($schema_breadcrumb['itemListElement'], 2, 0, [[
        '@type' => 'ListItem', 'position' => 3,
        'name'  => $cat, 'item' => 'https://yassota.com/blog?type=' . rawurlencode($cat_slug),
    ]]);
    $schema_breadcrumb['itemListElement'][3]['position'] = 4;
}

?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($page_title) ?></title>
<meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="index,follow">
<?php if (!empty($post['keywords'])): ?>
<meta name="keywords" content="<?= htmlspecialchars($post['keywords']) ?>">
<?php endif; ?>
<link rel="canonical" href="<?= htmlspecialchars($site_url) ?>/">
<!-- Open Graph -->
<meta property="og:type"        content="article">
<meta property="og:title"       content="<?= htmlspecialchars($page_title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta property="og:url"         content="<?= htmlspecialchars($site_url) ?>/">
<meta property="og:image"       content="<?= htmlspecialchars($og_image) ?>">
<meta property="og:site_name"   content="يسوتا — yassota.com">
<meta property="og:locale"      content="ar_AR">
<meta property="article:published_time" content="<?= htmlspecialchars(date('c', strtotime($published))) ?>">
<meta property="article:modified_time"  content="<?= htmlspecialchars(date('c', strtotime($updated))) ?>">
<meta property="article:author"         content="<?= htmlspecialchars($author) ?>">
<!-- Twitter Card -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?= htmlspecialchars($page_title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta name="twitter:image"       content="<?= htmlspecialchars($og_image) ?>">
<link rel="sitemap" type="application/xml" href="<?= htmlspecialchars($site_url) ?>/sitemap.xml">
<!-- JSON-LD -->
<script type="application/ld+json"><?= json_encode($schema_article,   JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
<script type="application/ld+json"><?= json_encode($schema_breadcrumb, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
<style>
:root{--accent:<?= $accent ?>;--cyan:#0ea5e9;--purple:#7c3aed;--hdr:#0c1e36;--bg:#f4f7fb;--surface:#fff;--text:#0f172a;--muted:#64748b}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Cairo','Tajawal',system-ui,sans-serif;background:var(--bg);color:var(--text);direction:rtl;line-height:1.7;-webkit-font-smoothing:antialiased}
header{background:var(--hdr);padding:12px 20px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 1px 12px rgba(0,0,0,.4)}
.logo{font-family:monospace;font-size:14px;font-weight:700;letter-spacing:1.5px;text-decoration:none}
.logo-yas{background:linear-gradient(135deg,#22d3ee,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.logo-sota{background:linear-gradient(135deg,#7c3aed,#f97316);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
nav a{color:rgba(226,232,240,.5);font-size:11.5px;text-decoration:none;padding:4px 8px;border-radius:6px}
nav a:hover{color:#0ea5e9;background:rgba(14,165,233,.1)}

/* Hero */
.hero{background:linear-gradient(140deg,var(--hdr) 0%,#111833 55%,#18103a 100%);padding:40px 20px 32px;text-align:center;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;top:-30%;right:-5%;width:280px;height:280px;border-radius:50%;background:radial-gradient(circle,rgba(14,165,233,.1),transparent 70%);pointer-events:none}
.cover-img{width:100%;max-width:640px;height:220px;object-fit:cover;border-radius:16px;margin:0 auto 22px;display:block;border:1px solid rgba(255,255,255,.08);box-shadow:0 8px 30px rgba(0,0,0,.35);position:relative;z-index:1}
.hero-cat{display:inline-block;padding:3px 12px;background:rgba(14,165,233,.15);border:1px solid rgba(14,165,233,.25);border-radius:20px;color:#38bdf8;font-size:10.5px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;margin-bottom:12px;position:relative;z-index:1}
.hero-title{font-size:24px;font-weight:900;color:#fff;margin-bottom:12px;line-height:1.35;max-width:640px;margin-left:auto;margin-right:auto;position:relative;z-index:1;text-shadow:0 2px 10px rgba(0,0,0,.3)}
.hero-meta{display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap;font-size:12.5px;color:rgba(226,232,240,.5);margin-bottom:18px;position:relative;z-index:1}
.hero-meta span{display:flex;align-items:center;gap:5px}
.hero-excerpt{font-size:13.5px;color:rgba(226,232,240,.65);max-width:560px;margin:0 auto 22px;line-height:1.75;position:relative;z-index:1}
.btn-read{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;background:linear-gradient(135deg,var(--purple),#4f46e5);color:#fff;border-radius:14px;font-size:15px;font-weight:800;text-decoration:none;box-shadow:0 6px 22px rgba(124,58,237,.45);transition:transform .18s,box-shadow .18s;position:relative;z-index:1}
.btn-read:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(124,58,237,.6)}

/* Layout */
.wrap{max-width:820px;margin:0 auto;padding:26px 16px}
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);margin-bottom:18px;flex-wrap:wrap}
.breadcrumb a{color:var(--cyan);text-decoration:none}
.breadcrumb a:hover{text-decoration:underline}
.card{background:var(--surface);border:1px solid rgba(15,23,42,.07);border-radius:18px;padding:22px;margin-bottom:18px;box-shadow:0 2px 10px rgba(15,23,42,.05)}
.card-title{font-size:16px;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.card-title::before{content:'';display:inline-block;width:4px;height:20px;background:linear-gradient(180deg,var(--cyan),var(--purple));border-radius:4px;flex-shrink:0}

/* Notice */
.notice{background:linear-gradient(135deg,rgba(14,165,233,.06),rgba(124,58,237,.06));border:1px solid rgba(14,165,233,.18);border-radius:14px;padding:18px 20px;margin-bottom:18px;display:flex;gap:14px;align-items:flex-start}
.notice-icon{font-size:22px;flex-shrink:0}
.notice-body{font-size:13.5px;color:var(--muted);line-height:1.7}
.notice-body a{color:var(--cyan);font-weight:700;text-decoration:none}
.notice-body strong{color:var(--text)}

/* Body preview */
.body-preview{font-size:14px;color:#475569;line-height:1.85;overflow:hidden;max-height:340px;position:relative}
.body-preview::after{content:'';position:absolute;bottom:0;left:0;right:0;height:80px;background:linear-gradient(transparent,var(--surface))}
.read-more-wrap{text-align:center;margin-top:8px}
.read-more{display:inline-flex;align-items:center;gap:6px;color:var(--cyan);font-weight:700;font-size:13.5px;text-decoration:none}

/* Related posts */
.rel-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px}
.rel-card{background:var(--surface);border:1px solid rgba(15,23,42,.07);border-radius:14px;overflow:hidden;text-decoration:none;color:inherit;transition:transform .18s,box-shadow .18s;display:flex;flex-direction:column}
.rel-card:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(15,23,42,.1)}
.rel-cover{width:100%;height:120px;object-fit:cover;background:#e2e8f0;display:block}
.rel-cover-ph{width:100%;height:120px;background:linear-gradient(135deg,var(--hdr),#2d1b69);display:flex;align-items:center;justify-content:center;font-size:32px}
.rel-body{padding:12px}
.rel-title{font-size:13px;font-weight:700;line-height:1.4;color:var(--text);margin-bottom:4px}
.rel-date{font-size:11px;color:var(--muted)}

footer{background:var(--hdr);color:rgba(226,232,240,.45);text-align:center;padding:22px 20px;font-size:12px;margin-top:40px}
footer a{color:rgba(226,232,240,.4);text-decoration:none;margin:0 5px}
footer a:hover{color:var(--cyan)}
.footer-legal{margin-top:10px;display:flex;flex-wrap:wrap;justify-content:center;gap:2px 6px}
</style>
</head>
<body>

<header>
  <a href="https://yassota.com" class="logo"><span class="logo-yas">yas</span><span class="logo-sota">sota</span></a>
  <nav>
    <a href="https://yassota.com">الرئيسية</a>
    <a href="https://yassota.com/blog">المدونة</a>
    <a href="?page=about">من نحن</a>
    <a href="?page=privacy">الخصوصية</a>
    <a href="?page=contact">تواصل</a>
  </nav>
</header>

<div class="hero">
  <?php if ($cover): ?>
  <img src="<?= htmlspecialchars($cover) ?>" alt="<?= htmlspecialchars($title) ?>" class="cover-img" loading="eager">
  <?php endif; ?>
  <div class="hero-cat"><?= htmlspecialchars($cat) ?></div>
  <h1 class="hero-title"><?= htmlspecialchars($title) ?></h1>
  <div class="hero-meta">
    <span>✍️ <?= htmlspecialchars($author) ?></span>
    <span>📅 <?= htmlspecialchars($date_fmt) ?></span>
    <span>⏱ <?= $read_time ?> دقيقة للقراءة</span>
  </div>
  <?php if ($excerpt): ?>
  <p class="hero-excerpt"><?= htmlspecialchars($excerpt) ?></p>
  <?php endif; ?>
  <a href="<?= htmlspecialchars($main_url) ?>" class="btn-read" target="_blank" rel="noopener">
    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
    قراءة المقال كاملاً
  </a>
</div>

<div class="wrap">

  <nav class="breadcrumb" aria-label="مسار التنقل">
    <a href="https://yassota.com">يسوتا</a>
    <span>›</span>
    <a href="https://yassota.com/blog">المدونة</a>
    <?php if ($cat_slug): ?>
    <span>›</span>
    <a href="https://yassota.com/blog?type=<?= rawurlencode($cat_slug) ?>"><?= htmlspecialchars($cat) ?></a>
    <?php endif; ?>
    <span>›</span>
    <span><?= htmlspecialchars(mb_substr($title, 0, 40)) ?>…</span>
  </nav>

  <div class="notice">
    <span class="notice-icon">📄</span>
    <div class="notice-body">
      هذه الصفحة ملخص لمقال <strong><?= htmlspecialchars($title) ?></strong> من منصة يسوتا.<br>
      للقراءة الكاملة مع التعليقات والمحتوى التفاعلي:
      <a href="<?= htmlspecialchars($main_url) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($main_url) ?></a>
    </div>
  </div>

  <?php if ($body): ?>
  <div class="card">
    <div class="card-title">من المقال</div>
    <div class="body-preview">
      <?= nl2br(htmlspecialchars(mb_substr(strip_tags($body), 0, 800))) ?>
    </div>
    <div class="read-more-wrap" style="margin-top:16px">
      <a href="<?= htmlspecialchars($main_url) ?>" class="read-more" target="_blank" rel="noopener">
        متابعة القراءة على يسوتا ←
      </a>
    </div>
  </div>
  <?php elseif ($excerpt): ?>
  <div class="card">
    <div class="card-title">ملخص المقال</div>
    <p style="font-size:14px;color:#475569;line-height:1.85"><?= htmlspecialchars($excerpt) ?></p>
    <div class="read-more-wrap" style="margin-top:16px">
      <a href="<?= htmlspecialchars($main_url) ?>" class="read-more" target="_blank" rel="noopener">قراءة المقال كاملاً ←</a>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($related_posts): ?>
  <div class="card">
    <div class="card-title">مقالات ذات صلة</div>
    <div class="rel-grid">
      <?php foreach ($related_posts as $rp): ?>
      <a href="https://<?= htmlspecialchars($rp['slug']) ?>.yassota.com/" class="rel-card">
        <?php if (!empty($rp['cover_image'])): ?>
        <img src="https://yassota.com/<?= htmlspecialchars(ltrim($rp['cover_image'],'/')) ?>"
             alt="<?= htmlspecialchars($rp['title']) ?>" class="rel-cover" loading="lazy">
        <?php else: ?>
        <div class="rel-cover-ph">📝</div>
        <?php endif; ?>
        <div class="rel-body">
          <div class="rel-title"><?= htmlspecialchars($rp['title']) ?></div>
          <div class="rel-date"><?= date('d/m/Y', strtotime($rp['created_at'])) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<footer>
  <p>© <?= date('Y') ?> <a href="https://yassota.com">yassota.com</a> — محتوى عربي حصري</p>
  <nav class="footer-legal" aria-label="الصفحات القانونية">
    <a href="?page=about">من نحن</a>
    <a href="?page=privacy">الخصوصية</a>
    <a href="?page=terms">الشروط</a>
    <a href="?page=contact">تواصل</a>
    <a href="?page=dmca">DMCA</a>
    <a href="/sitemap.xml">Sitemap</a>
    <a href="https://yassota.com/blog">المدونة</a>
  </nav>
</footer>

</body>
</html>
<?php

function serve_sitemap(string $host, string $slug): void {
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    $base = 'https://' . $host;
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    echo "  <url><loc>{$base}/</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>\n";
    foreach (['about','privacy','terms','contact','dmca'] as $p) {
        echo "  <url><loc>{$base}/?page={$p}</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>\n";
    }
    echo '</urlset>';
}

function serve_legal_page(string $page, string $slug): void {
    $nm = htmlspecialchars(str_replace('-', ' ', $slug));
    $pages = [
        'about' => [
            'title' => "من نحن — {$nm}",
            'h'     => 'من نحن',
            'body'  => "<p>هذه الصفحة الفرعية <strong>{$nm}.yassota.com</strong> جزء من منصة <a href='https://yassota.com'>يسوتا</a> للمحتوى العربي.</p>
                        <p>نقدم مقالات وأدلة متخصصة باللغة العربية بأعلى معايير الجودة والدقة.</p>
                        <p><a href='https://yassota.com/about'>المزيد عن يسوتا ←</a></p>",
        ],
        'privacy' => [
            'title' => "سياسة الخصوصية — {$nm}",
            'h'     => 'سياسة الخصوصية',
            'body'  => "<p>آخر تحديث: ".date('Y-m-d')."</p>
                        <p>لا نجمع بيانات شخصية من زوار هذه الصفحة. قد تستخدم إعلانات Google AdSense التي تخضع <a href='https://policies.google.com/privacy'>لسياسة جوجل</a>.</p>
                        <p><a href='https://yassota.com/privacy-policy'>سياسة الخصوصية الكاملة ←</a></p>",
        ],
        'terms' => [
            'title' => "شروط الاستخدام — {$nm}",
            'h'     => 'شروط الاستخدام',
            'body'  => "<p>باستخدامك هذه الصفحة توافق على <a href='https://yassota.com/terms'>شروط استخدام يسوتا</a>. المحتوى المقدم للأغراض المعلوماتية فقط.</p>",
        ],
        'contact' => [
            'title' => "تواصل معنا — {$nm}",
            'h'     => 'تواصل معنا',
            'body'  => "<p>للتواصل بشأن محتوى هذه الصفحة:</p><p><a href='https://yassota.com/contact'>نموذج التواصل على يسوتا ←</a></p>",
        ],
        'dmca' => [
            'title' => "DMCA — {$nm}",
            'h'     => 'إشعار DMCA',
            'body'  => "<p>لإرسال إشعار إزالة محتوى: <a href='https://yassota.com/dmca'>نموذج DMCA ←</a></p>",
        ],
    ];
    $p = $pages[$page] ?? $pages['about'];
    echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='utf-8'>"
        ."<title>".htmlspecialchars($p['title'])."</title>"
        ."<meta name='viewport' content='width=device-width,initial-scale=1'>"
        ."<meta name='robots' content='noindex,follow'>"
        ."<style>*{box-sizing:border-box}body{font-family:Cairo,system-ui,sans-serif;background:#f4f7fb;color:#0f172a;direction:rtl;line-height:1.75;padding:0}header{background:#0c1e36;padding:12px 20px;color:#e2e8f0;font-size:14px}.wrap{max-width:700px;margin:0 auto;padding:32px 20px}h1{font-size:22px;font-weight:800;margin-bottom:16px}p{color:#475569;margin-bottom:12px;font-size:14px}a{color:#0ea5e9}.back{display:inline-flex;align-items:center;gap:6px;margin-bottom:20px;color:#0ea5e9;text-decoration:none;font-weight:600;font-size:13px}footer{background:#0c1e36;color:rgba(226,232,240,.4);text-align:center;padding:16px;font-size:12px;margin-top:40px}footer a{color:rgba(226,232,240,.35);margin:0 6px;text-decoration:none}</style>"
        ."</head><body>"
        ."<header><a href='/' style='color:#38bdf8;text-decoration:none;font-weight:700'>{$nm}.yassota.com</a></header>"
        ."<div class='wrap'><a href='/' class='back'>← العودة للمقال</a><h1>{$p['h']}</h1>{$p['body']}</div>"
        ."<footer><a href='/'>الرئيسية</a><a href='/?page=privacy'>الخصوصية</a><a href='https://yassota.com'>يسوتا</a></footer>"
        ."</body></html>";
}
