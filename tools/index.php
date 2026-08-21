<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/_base.php';

/* ── Load all published tools from DB ── */
$allTools = [];
try {
    $rows = $pdo->query(
        "SELECT slug, name AS title, short_description AS `desc`,
                icon_svg AS icon, icon_color AS color, icon_bg AS bg,
                seo_title, meta_description, views, sort_order
         FROM web_tools WHERE status='published'
         ORDER BY sort_order, id"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $r['has_dir'] = is_dir(__DIR__ . '/' . $r['slug']);
        $allTools[] = $r;
    }
} catch (\Throwable $e) { $allTools = []; }

/* ── Also include directory-based tools not yet in DB ── */
try {
    $dbSlugs = array_column($allTools, 'slug');
    foreach (glob(__DIR__ . '/*/index.php') ?: [] as $f) {
        $slug = basename(dirname($f));
        if ($slug[0] === '_') continue;
        if (in_array($slug, $dbSlugs, true)) continue;
        $allTools[] = [
            'slug'  => $slug,
            'title' => ucwords(str_replace(['-','_'],' ',$slug)),
            'desc'  => '',
            'icon'  => '<path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>',
            'color' => '#2563eb',
            'bg'    => '#dbeafe',
            'has_dir' => true,
            'seo_title' => '',
            'meta_description' => '',
            'views' => 0,
        ];
    }
} catch (\Throwable $e) {}

/* ── Category definitions ── */
$catMap = [
  'ai'        => ['ar'=>'الذكاء الاصطناعي','en'=>'AI Tools','color'=>'#7c3aed','bg'=>'#ede9fe','icon'=>'<path d="M12 2a2 2 0 012 2v2a2 2 0 01-2 2 2 2 0 01-2-2V4a2 2 0 012-2z"/><path d="M12 16a2 2 0 012 2v2a2 2 0 01-2 2 2 2 0 01-2-2v-2a2 2 0 012-2z"/><path d="M2 12a2 2 0 012-2h2a2 2 0 012 2 2 2 0 01-2 2H4a2 2 0 01-2-2z"/><path d="M16 12a2 2 0 012-2h2a2 2 0 012 2 2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'],
  'image'     => ['ar'=>'أدوات الصور','en'=>'Image Tools','color'=>'#059669','bg'=>'#d1fae5','icon'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'],
  'pdf'       => ['ar'=>'أدوات PDF','en'=>'PDF Tools','color'=>'#dc2626','bg'=>'#fee2e2','icon'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><line x1="9" y1="13" x2="15" y2="13"/>'],
  'network'   => ['ar'=>'أدوات الشبكة','en'=>'Network Tools','color'=>'#0891b2','bg'=>'#e0f2fe','icon'=>'<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>'],
  'developer' => ['ar'=>'أدوات المطورين','en'=>'Developer Tools','color'=>'#d97706','bg'=>'#fef3c7','icon'=>'<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>'],
  'security'  => ['ar'=>'الأمان','en'=>'Security','color'=>'#16a34a','bg'=>'#dcfce7','icon'=>'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
  'seo'       => ['ar'=>'أدوات السيو','en'=>'SEO Tools','color'=>'#0284c7','bg'=>'#e0f2fe','icon'=>'<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>'],
  'text'      => ['ar'=>'أدوات النصوص','en'=>'Text Tools','color'=>'#6d28d9','bg'=>'#ede9fe','icon'=>'<polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/>'],
];

/* ── Assign each tool a category ── */
function tool_category(string $slug): string {
    static $aiPfx   = ['ai-','write','hashtag','speech-to-text','text-to-speech'];
    static $imgPfx  = ['compress','resize','img-','image-','ocr','screen-','favicon-','colors','color-','gradient','svg-','ascii','pixel-','aspect-'];
    static $pdfPfx  = ['pdf-'];
    static $netPfx  = ['dns','ip','whois','speed-','url-shortener','robots-gen','sitemap-gen'];
    static $devPfx  = ['json-','xml-','csv-','sql-','js-','css-','html-','base-conv','encode','cron-','uuid','jwt-','epoch-','regex','htaccess-','slug','diff','barcode','qr','pass','password','pomodoro','timer','timezone','unit'];
    static $secPfx  = ['password-strength'];
    static $seoPfx  = ['seo','meta-gen','slug'];
    static $txtPfx  = ['text-','word','lorem','char-','reading-','markdown','tashkeel-','keyboard-','number-to-','hijri-','zakat-'];
    foreach ($aiPfx  as $p) if (str_starts_with($slug,$p)||$slug===$p) return 'ai';
    foreach ($imgPfx as $p) if (str_starts_with($slug,$p)||$slug===$p) return 'image';
    foreach ($pdfPfx as $p) if (str_starts_with($slug,$p)) return 'pdf';
    foreach ($netPfx as $p) if (str_starts_with($slug,$p)||$slug===$p) return 'network';
    foreach ($secPfx as $p) if ($slug===$p||str_starts_with($slug,$p)) return 'security';
    foreach ($seoPfx as $p) if (str_starts_with($slug,$p)||$slug===$p) return 'seo';
    foreach ($txtPfx as $p) if (str_starts_with($slug,$p)||str_contains($slug,$p)) return 'text';
    foreach ($devPfx as $p) if (str_starts_with($slug,$p)||$slug===$p) return 'developer';
    return 'developer';
}

foreach ($allTools as &$t) { $t['cat'] = tool_category($t['slug']); }
unset($t);

$toolCount = count($allTools);
$toolsUrl  = TOOLS_BASE_URL . '/tools/';
$mainUrl   = MAIN_SITE_URL;

$schema = json_encode([
  '@context'=>'https://schema.org',
  '@type'=>'WebSite',
  'name'=>'yassota Tools',
  'url'=>$toolsUrl,
  'description'=>$toolCount . '+ free professional web tools — AI writing, image compression, QR codes, PDF tools and more. No registration required.',
  'potentialAction'=>['@type'=>'SearchAction','target'=>$toolsUrl.'?q={search_term_string}','query-input'=>'required name=search_term_string'],
]);
?><!DOCTYPE html>
<html lang="en" dir="ltr" id="html-root">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title id="page-title">yassota Tools — <?= $toolCount ?>+ Free Online Web Tools</title>
<meta id="page-desc" name="description" content="<?= $toolCount ?>+ free professional web tools — AI writing, image compression, QR codes, password generators, PDF tools and more. No registration required.">
<meta property="og:title" content="yassota Tools — Free Online Web Tools">
<meta property="og:description" content="<?= $toolCount ?>+ free web tools that work directly in your browser">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= htmlspecialchars($toolsUrl) ?>">
<meta name="theme-color" content="#0b1120">
<meta name="robots" content="index,follow">
<link rel="canonical" href="<?= htmlspecialchars($toolsUrl) ?>">
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= ADSENSE_PUB ?>" crossorigin="anonymous"></script>
<script type="application/ld+json"><?= $schema ?></script>
<script type="application/ld+json"><?= json_encode([
  '@context'=>'https://schema.org',
  '@type'=>'BreadcrumbList',
  'itemListElement'=>[
    ['@type'=>'ListItem','position'=>1,'name'=>'yassota','item'=>$mainUrl],
    ['@type'=>'ListItem','position'=>2,'name'=>'Tools','item'=>$toolsUrl],
  ],
]) ?></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;font-size:16px}
body{font-family:'Segoe UI',system-ui,-apple-system,Tahoma,Arial,sans-serif;background:#f1f5f9;color:#0f172a;line-height:1.6;min-height:100vh;overflow-x:hidden;padding-top:62px}
a{color:inherit;text-decoration:none}img{max-width:100%;display:block}
button,input,select{font-family:inherit;font-size:inherit}
/* ── Header ── */
.th{background:#0b1120;color:#e2e8f0;display:flex;align-items:center;height:62px;padding:0 20px;position:fixed;top:0;left:0;right:0;z-index:300;box-shadow:0 2px 20px rgba(0,0,0,.4);gap:10px}
.th-logo{font-size:18px;font-weight:800;display:flex;align-items:center;gap:8px;text-decoration:none;flex-shrink:0;color:#e2e8f0}
.th-logo-box{width:32px;height:32px;background:linear-gradient(135deg,#2563eb,#7c3aed);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.th-logo-box svg{width:16px;height:16px;stroke:#fff;fill:none;stroke-width:2.5}
.th-logo-text{background:linear-gradient(135deg,#22c55e,#3b82f6,#f59e0b,#ef4444);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.th-spacer{flex:1}
.th-btn{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);color:#e2e8f0;border-radius:8px;padding:7px 12px;font-size:13px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .15s;white-space:nowrap;flex-shrink:0}
.th-btn:hover{background:rgba(255,255,255,.15);color:#fff}
.th-btn svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2;flex-shrink:0}
.th-search-wrap{flex:0 1 360px;position:relative}
.th-search-wrap input{width:100%;padding:8px 14px 8px 36px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:8px;color:#fff;font-size:14px;outline:none;transition:border-color .15s}
.th-search-wrap input::placeholder{color:rgba(255,255,255,.38)}
.th-search-wrap input:focus{border-color:rgba(255,255,255,.4);background:rgba(255,255,255,.12)}
.th-search-icon{position:absolute;left:10px;top:50%;transform:translateY(-50%);opacity:.45;pointer-events:none}
.th-search-icon svg{width:15px;height:15px;stroke:#fff;fill:none;stroke-width:2}
.lang-sel{display:flex;align-items:center;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:8px;overflow:hidden;flex-shrink:0}
.lang-btn{background:transparent;border:none;padding:7px 11px;font-size:12px;font-weight:700;color:rgba(226,232,240,.5);cursor:pointer;transition:all .15s;letter-spacing:.3px}
.lang-btn.active{color:#fff;background:rgba(255,255,255,.15)}
.lang-btn:hover:not(.active){color:rgba(226,232,240,.85)}
/* ── Sidebar ── */
.sidebar{position:fixed;top:0;right:0;bottom:0;width:290px;background:#0d1527;z-index:500;overflow-y:auto;transform:translateX(110%);transition:transform .28s cubic-bezier(.4,0,.2,1);box-shadow:-6px 0 32px rgba(0,0,0,.5)}
.sidebar.open{transform:translateX(0)}
.sidebar-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid rgba(255,255,255,.08)}
.sidebar-logo{font-size:15px;font-weight:800;background:linear-gradient(135deg,#22c55e,#3b82f6,#f59e0b,#ef4444);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.sidebar-close{background:none;border:none;color:rgba(226,232,240,.5);cursor:pointer;font-size:20px;padding:4px;line-height:1;transition:color .15s}
.sidebar-close:hover{color:#fff}
.sb-section{padding:10px 0;border-bottom:1px solid rgba(255,255,255,.06)}
.sb-section-title{font-size:10px;font-weight:700;color:rgba(226,232,240,.35);letter-spacing:1.2px;text-transform:uppercase;padding:4px 20px 8px}
.sb-link{display:flex;align-items:center;gap:10px;padding:11px 20px;font-size:14px;color:rgba(226,232,240,.7);transition:all .15s;cursor:pointer;border:none;background:none;width:100%;text-align:right}
.sb-link:hover,.sb-link.active{background:rgba(255,255,255,.07);color:#fff}
.sb-link svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;flex-shrink:0}
.sb-cnt{margin-right:auto;margin-left:0;background:rgba(255,255,255,.1);color:rgba(226,232,240,.5);font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px}
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:499;opacity:0;pointer-events:none;transition:opacity .25s}
.overlay.open{opacity:1;pointer-events:all}
/* ── Layout ── */
.wrap{max-width:1200px;margin:0 auto;padding:24px 18px 80px}
/* ── Hero ── */
.hero{background:#fff;border-radius:16px;padding:30px 28px;margin-bottom:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #e2e8f0;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(37,99,235,.03) 0%,rgba(124,58,237,.04) 100%);pointer-events:none}
.hero-inner{position:relative;z-index:1;display:flex;align-items:center;gap:20px}
.hero-icon-wrap{width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,rgba(37,99,235,.12),rgba(124,58,237,.12));display:flex;align-items:center;justify-content:center;flex-shrink:0}
.hero-text h1{font-size:26px;font-weight:800;color:#0f172a;line-height:1.25;margin-bottom:10px}
.hero-text p{font-size:15px;color:#64748b;line-height:1.7;max-width:560px}
.hero-stats{display:flex;flex-wrap:wrap;gap:12px;margin-top:18px}
.hero-stat{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 18px;text-align:center;min-width:70px}
.hero-stat .n{font-size:22px;font-weight:800;color:#2563eb;line-height:1}
.hero-stat .l{font-size:11px;color:#64748b;margin-top:3px}
/* ── Filters ── */
.filters{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px}
.filter-chip{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:20px;font-size:13px;font-weight:600;cursor:pointer;border:1.5px solid #e2e8f0;background:#fff;color:#475569;transition:all .18s;white-space:nowrap}
.filter-chip:hover{border-color:#94a3b8}
.filter-chip.active{background:#2563eb;color:#fff;border-color:#2563eb}
.filter-chip svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2;flex-shrink:0}
/* ── Section heading ── */
.section-head{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.section-head h2{font-size:17px;font-weight:800;color:#0f172a}
.section-head .cnt-badge{font-size:13px;color:#64748b;font-weight:500}
/* ── Tools Grid ── */
.tools-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:14px}
.tool-card{background:#fff;border-radius:14px;padding:20px 18px;border:1px solid #e2e8f0;box-shadow:0 1px 6px rgba(0,0,0,.05);transition:all .18s;display:flex;flex-direction:column;text-decoration:none;color:inherit}
.tool-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1);border-color:#cbd5e1}
.tool-card.hidden{display:none}
.t-icon-box{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;flex-shrink:0}
.t-icon-box svg{width:24px;height:24px;stroke-width:2;fill:none}
.t-name{font-size:14px;font-weight:700;color:#0f172a;margin-bottom:6px;line-height:1.35}
.t-desc{font-size:12px;color:#64748b;line-height:1.65;flex:1}
.t-try{margin-top:12px;font-size:12px;font-weight:700;display:flex;align-items:center;gap:4px}
/* ── No results ── */
.no-results{grid-column:1/-1;text-align:center;padding:60px 20px;color:#94a3b8;font-size:15px;display:flex;flex-direction:column;align-items:center;gap:14px}
.no-results svg{width:44px;height:44px;stroke:currentColor;fill:none;stroke-width:1.5}
/* ── Why section ── */
.why-card{background:#fff;border-radius:16px;padding:28px;border:1px solid #e2e8f0;margin-top:20px}
.why-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-top:16px}
.why-item{padding:16px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0}
.why-emoji{font-size:22px;margin-bottom:8px}
.why-item-title{font-size:13px;font-weight:700;color:#0f172a;margin-bottom:4px}
.why-item-desc{font-size:12px;color:#64748b;line-height:1.6}
/* ── Ad ── */
.ad-wrap{margin:20px 0;text-align:center;min-height:90px}
/* ── Footer ── */
.t-footer{background:#0b1120;color:rgba(226,232,240,.6);padding:36px 24px 24px;margin-top:40px}
.t-footer-grid{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:28px;padding-bottom:24px;border-bottom:1px solid rgba(255,255,255,.08)}
.t-footer-bottom{max-width:1200px;margin:16px auto 0;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;font-size:12px;color:rgba(226,232,240,.3)}
.t-footer-logo{font-size:16px;font-weight:800;background:linear-gradient(135deg,#22c55e,#3b82f6,#f59e0b,#ef4444);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
/* ── Mobile ── */
#mobileSearchBar{display:none;position:fixed;top:62px;left:0;right:0;z-index:290;background:#0b1120;padding:10px 16px;border-bottom:1px solid rgba(255,255,255,.1)}
#mobileSearchBar input{width:100%;padding:9px 14px 9px 36px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:8px;color:#fff;font-size:14px;outline:none}
#mobileSearchBar .m-si{position:absolute;left:26px;top:50%;transform:translateY(-50%);opacity:.45;pointer-events:none}
#mobileSearchBar .m-si svg{width:15px;height:15px;stroke:#fff;fill:none;stroke-width:2}
@media(max-width:640px){
  .hero{padding:20px 16px}
  .hero-text h1{font-size:20px}
  .hero-inner{flex-direction:column;align-items:flex-start;gap:14px}
  .wrap{padding:16px 12px 60px}
  .tools-grid{grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:10px}
  .tool-card{padding:15px 13px}
  .th-search-wrap{display:none}
  #mobileSearchBtn{display:flex!important}
}
@media(max-width:380px){.tools-grid{grid-template-columns:1fr 1fr}}
/* RTL adjustments */
html[dir=rtl] .th-search-wrap input{padding:8px 36px 8px 14px}
html[dir=rtl] .th-search-icon{left:auto;right:10px}
html[dir=rtl] .sidebar{right:auto;left:0;transform:translateX(-110%)}
html[dir=rtl] .sidebar.open{transform:translateX(0)}
html[dir=rtl] .sb-cnt{margin-right:0;margin-left:auto}
html[dir=rtl] .sb-link{text-align:right}
html[dir=rtl] #mobileSearchBar input{padding:9px 36px 9px 14px}
html[dir=rtl] #mobileSearchBar .m-si{left:auto;right:26px}
</style>
</head>
<body>

<!-- ══ Header ══ -->
<header class="th">
  <a href="<?= $mainUrl ?>" class="th-logo" aria-label="yassota">
    <div class="th-logo-box">
      <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    </div>
    <span class="th-logo-text">yassota</span>
  </a>

  <!-- Desktop search -->
  <div class="th-search-wrap">
    <div class="th-search-icon"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
    <input type="search" id="searchInput" autocomplete="off"
           placeholder="Search tools…"
           oninput="filterTools()" onkeydown="if(event.key==='Escape'){this.value='';filterTools();}">
  </div>

  <div class="th-spacer"></div>

  <!-- Language selector -->
  <div class="lang-sel" aria-label="Language">
    <button class="lang-btn" id="btn-en" onclick="setLang('en')" aria-label="English">EN</button>
    <button class="lang-btn" id="btn-ar" onclick="setLang('ar')" aria-label="عربي">ع</button>
  </div>

  <!-- Mobile search button -->
  <button class="th-btn" id="mobileSearchBtn" style="display:none" onclick="toggleMobileSearch()" aria-label="Search">
    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
  </button>

  <!-- Menu button -->
  <button class="th-btn" onclick="toggleSidebar()" aria-label="Menu" id="menuBtn">
    <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    <span id="menuBtnLabel">Menu</span>
  </button>
</header>

<!-- Mobile search bar -->
<div id="mobileSearchBar">
  <div style="position:relative">
    <div class="m-si"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
    <input id="mobileSearchInput" type="search" placeholder="Search tools…" autocomplete="off"
           oninput="syncSearch(this)" onkeydown="if(event.key==='Escape'){this.value='';syncSearch(this);}">
  </div>
</div>

<!-- ══ Sidebar ══ -->
<nav class="sidebar" id="sidebar" aria-label="Navigation">
  <div class="sidebar-head">
    <span class="sidebar-logo">yassota Tools</span>
    <button class="sidebar-close" onclick="toggleSidebar()" aria-label="Close">✕</button>
  </div>

  <div class="sb-section">
    <div class="sb-section-title" data-i18n="nav_categories">Categories</div>
    <button class="sb-link active" id="sb-all" onclick="filterCat('all');toggleSidebar()">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      <span data-i18n="all_tools">All Tools</span>
      <span class="sb-cnt"><?= $toolCount ?></span>
    </button>
    <?php foreach ($catMap as $cslug => $cdata):
      $cnt = count(array_filter($allTools, fn($t) => $t['cat'] === $cslug));
      if (!$cnt) continue;
    ?>
    <button class="sb-link" id="sb-<?= $cslug ?>" onclick="filterCat('<?= $cslug ?>');toggleSidebar()">
      <svg viewBox="0 0 24 24"><?= $cdata['icon'] ?></svg>
      <span data-cat-ar="<?= htmlspecialchars($cdata['ar']) ?>" data-cat-en="<?= htmlspecialchars($cdata['en']) ?>"><?= htmlspecialchars($cdata['en']) ?></span>
      <span class="sb-cnt"><?= $cnt ?></span>
    </button>
    <?php endforeach; ?>
  </div>

  <div class="sb-section">
    <div class="sb-section-title" data-i18n="nav_links">Navigation</div>
    <a href="<?= $mainUrl ?>" class="sb-link">
      <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      <span data-i18n="home">Home</span>
    </a>
    <a href="<?= $mainUrl ?>/apps" class="sb-link">
      <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
      <span data-i18n="apps">Apps</span>
    </a>
    <a href="<?= $mainUrl ?>/blog" class="sb-link">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <span data-i18n="blog">Blog</span>
    </a>
    <a href="<?= $mainUrl ?>/privacy-policy" class="sb-link">
      <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      <span data-i18n="privacy">Privacy</span>
    </a>
  </div>

  <div style="padding:16px 20px 20px;font-size:11px;color:rgba(226,232,240,.3);line-height:1.7" data-i18n="footer_note">
    Free online tools — no sign-up required · © <?= date('Y') ?> yassota.com
  </div>
</nav>
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- ══ Main ══ -->
<main class="wrap">

  <!-- Hero -->
  <div class="hero">
    <div class="hero-inner">
      <div class="hero-icon-wrap">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
      </div>
      <div class="hero-text">
        <h1 id="hero-title">Free Online Web Tools</h1>
        <p id="hero-sub">Professional tools that work directly in your browser — no registration, no limits, 100% free.</p>
        <div class="hero-stats">
          <div class="hero-stat"><div class="n"><?= $toolCount ?>+</div><div class="l" data-i18n="stat_tools">Tools</div></div>
          <div class="hero-stat"><div class="n">100%</div><div class="l" data-i18n="stat_free">Free</div></div>
          <div class="hero-stat"><div class="n">0</div><div class="l" data-i18n="stat_signup">Sign-ups</div></div>
          <div class="hero-stat"><div class="n">8</div><div class="l" data-i18n="stat_cats">Categories</div></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Category filter chips -->
  <div class="filters" id="catChips">
    <button class="filter-chip active" onclick="filterCat('all')" id="chip-all">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      <span data-i18n="all_tools">All</span>
    </button>
    <?php foreach ($catMap as $cslug => $cdata):
      $cnt = count(array_filter($allTools, fn($t) => $t['cat'] === $cslug));
      if (!$cnt) continue;
    ?>
    <button class="filter-chip" onclick="filterCat('<?= $cslug ?>')" id="chip-<?= $cslug ?>"
            style="--cc:<?= $cdata['color'] ?>;--cb:<?= $cdata['bg'] ?>">
      <svg viewBox="0 0 24 24"><?= $cdata['icon'] ?></svg>
      <span data-cat-ar="<?= htmlspecialchars($cdata['ar']) ?>" data-cat-en="<?= htmlspecialchars($cdata['en']) ?>"><?= htmlspecialchars($cdata['en']) ?></span>
    </button>
    <?php endforeach; ?>
  </div>

  <div class="section-head">
    <h2 id="section-title">All Tools</h2>
    <span class="cnt-badge" id="result-count"><?= $toolCount ?> tools</span>
  </div>

  <!-- Tools grid -->
  <div class="tools-grid" id="toolsGrid">
    <?php foreach ($allTools as $t):
      $toolUrl  = $toolsUrl . $t['slug'] . '/';
      $cat      = $t['cat'];
      $catInfo  = $catMap[$cat] ?? $catMap['developer'];
      $color    = $t['color'] ?: $catInfo['color'];
      $bg       = $t['bg']    ?: $catInfo['bg'];
      $seoTitle = $t['seo_title'] ?: '';
      $metaDesc = $t['meta_description'] ?: '';
    ?>
    <a href="<?= htmlspecialchars($toolUrl) ?>"
       class="tool-card"
       data-cat="<?= $cat ?>"
       data-title-ar="<?= htmlspecialchars($t['title']) ?>"
       data-title-en="<?= htmlspecialchars($seoTitle ?: ucwords(str_replace(['-','_'],' ',$t['slug']))) ?>"
       data-desc-ar="<?= htmlspecialchars($t['desc']) ?>"
       data-desc-en="<?= htmlspecialchars($metaDesc ?: $t['desc']) ?>">
      <div class="t-icon-box" style="background:<?= $bg ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" style="stroke:<?= $color ?>;fill:none;stroke-width:2"><?= $t['icon'] ?></svg>
      </div>
      <div class="t-name" data-field="title"><?= htmlspecialchars($t['title']) ?></div>
      <div class="t-desc" data-field="desc"><?= htmlspecialchars($t['desc']) ?></div>
      <div class="t-try" style="color:<?= $color ?>">
        <span data-i18n="try_now">Try now</span>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </div>
    </a>
    <?php endforeach; ?>
    <div class="no-results" id="noResults" style="display:none">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <div id="noResultsText">No tools found matching your search.</div>
    </div>
  </div>

  <!-- Ad -->
  <div class="ad-wrap">
    <ins class="adsbygoogle" style="display:block" data-ad-client="<?= ADSENSE_PUB ?>"
         data-ad-slot="auto" data-ad-format="auto" data-full-width-responsive="true"></ins>
    <script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>
  </div>

  <!-- Why section -->
  <div class="why-card">
    <h2 id="why-title" style="font-size:18px;font-weight:800;color:#0f172a">Why yassota Tools?</h2>
    <div class="why-grid">
      <div class="why-item"><div class="why-emoji">🔒</div><div class="why-item-title" data-i18n="privacy_title">Privacy First</div><div class="why-item-desc" data-i18n="privacy_desc">Tools run entirely in your browser — no data sent to servers</div></div>
      <div class="why-item"><div class="why-emoji">⚡</div><div class="why-item-title" data-i18n="fast_title">Instant Results</div><div class="why-item-desc" data-i18n="fast_desc">No waiting, no upload delays — real-time processing</div></div>
      <div class="why-item"><div class="why-emoji">🆓</div><div class="why-item-title" data-i18n="free_title">Always Free</div><div class="why-item-desc" data-i18n="free_desc">No subscription, no credit card, no limits ever</div></div>
      <div class="why-item"><div class="why-emoji">🌍</div><div class="why-item-title" data-i18n="lang_title">Multi-language</div><div class="why-item-desc" data-i18n="lang_desc">Switch between Arabic and English with a single tap</div></div>
    </div>
  </div>

</main>

<!-- ══ Footer ══ -->
<footer class="t-footer">
  <div class="t-footer-grid">
    <div>
      <div class="t-footer-logo" style="margin-bottom:10px">yassota Tools</div>
      <p id="footer-desc" style="font-size:13px;line-height:1.7;color:rgba(226,232,240,.4)">Free online tools — no sign-up, no tracking, no limits.</p>
    </div>
    <div>
      <div style="font-size:10px;font-weight:700;color:rgba(226,232,240,.3);letter-spacing:1px;text-transform:uppercase;margin-bottom:10px" data-i18n="popular_tools">Popular Tools</div>
      <ul style="list-style:none;display:flex;flex-direction:column;gap:6px">
        <?php foreach (['compress'=>'Image Compressor','qr-code'=>'QR Code Generator','password'=>'Password Generator','json-fmt'=>'JSON Formatter','currency'=>'Currency Converter','ai-translator'=>'AI Translator','pdf-compress'=>'PDF Compressor','chat'=>'Yasmin AI Chat'] as $s=>$n): ?>
        <li><a href="<?= $toolsUrl . $s ?>/" style="font-size:13px;color:rgba(226,232,240,.45);transition:color .15s" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(226,232,240,.45)'"><?= $n ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <div style="font-size:10px;font-weight:700;color:rgba(226,232,240,.3);letter-spacing:1px;text-transform:uppercase;margin-bottom:10px" data-i18n="nav_links">Links</div>
      <ul style="list-style:none;display:flex;flex-direction:column;gap:6px">
        <li><a href="<?= $mainUrl ?>" style="font-size:13px;color:rgba(226,232,240,.45)" data-i18n="home">Home</a></li>
        <li><a href="<?= $mainUrl ?>/blog" style="font-size:13px;color:rgba(226,232,240,.45)" data-i18n="blog">Blog</a></li>
        <li><a href="<?= $mainUrl ?>/about" style="font-size:13px;color:rgba(226,232,240,.45)" data-i18n="about">About Us</a></li>
        <li><a href="<?= $mainUrl ?>/privacy-policy" style="font-size:13px;color:rgba(226,232,240,.45)" data-i18n="privacy">Privacy Policy</a></li>
        <li><a href="<?= $mainUrl ?>/terms" style="font-size:13px;color:rgba(226,232,240,.45)" data-i18n="terms">Terms of Use</a></li>
      </ul>
    </div>
  </div>
  <div class="t-footer-bottom">
    <div>© <?= date('Y') ?> yassota.com · <span data-i18n="rights">All rights reserved</span></div>
    <div><a href="<?= $mainUrl ?>/privacy-policy" style="color:inherit" data-i18n="privacy">Privacy</a> · <a href="<?= $mainUrl ?>/terms" style="color:inherit" data-i18n="terms">Terms</a></div>
  </div>
</footer>

<script>
/* ══════════════════════════════════════════
   i18n — full AR/EN translation system
   ══════════════════════════════════════════ */
var I18N = {
  en:{
    nav_categories:'Categories',nav_links:'Navigation',
    home:'Home',apps:'Apps',blog:'Blog',privacy:'Privacy',terms:'Terms of Use',about:'About Us',
    all_tools:'All',try_now:'Try now ›',
    stat_tools:'Tools',stat_free:'Free',stat_signup:'Sign-ups',stat_cats:'Categories',
    privacy_title:'Privacy First',privacy_desc:'Tools run entirely in your browser — no data sent to servers',
    fast_title:'Instant Results',fast_desc:'No waiting, no upload delays — real-time processing',
    free_title:'Always Free',free_desc:'No subscription, no credit card, no limits ever',
    lang_title:'Multi-language',lang_desc:'Switch between Arabic and English with a single tap',
    popular_tools:'Popular Tools',nav_links:'Links',rights:'All rights reserved',
    footer_note:'Free online tools — no sign-up required · © <?= date('Y') ?> yassota.com',
    search_ph:'Search tools…',menu:'Menu',results:'tools',no_result:'No tools found matching your search.',
    why:'Why yassota Tools?',footer_desc:'Free online tools — no sign-up, no tracking, no limits.',
  },
  ar:{
    nav_categories:'التصنيفات',nav_links:'روابط',
    home:'الرئيسية',apps:'التطبيقات',blog:'المدونة',privacy:'الخصوصية',terms:'شروط الاستخدام',about:'من نحن',
    all_tools:'الكل',try_now:'جرّب الآن ←',
    stat_tools:'أداة',stat_free:'مجاني',stat_signup:'تسجيل',stat_cats:'فئة',
    privacy_title:'خصوصية تامة',privacy_desc:'الأدوات تعمل داخل متصفحك — لا بيانات تُرسَل لأي خادم',
    fast_title:'نتائج فورية',fast_desc:'لا انتظار، لا رفع، معالجة فورية',
    free_title:'مجانية 100%',free_desc:'بدون اشتراك، بدون بطاقة ائتمانية، بدون قيود',
    lang_title:'متعدد اللغات',lang_desc:'بدّل بين العربية والإنجليزية بنقرة واحدة',
    popular_tools:'أدوات شائعة',nav_links:'روابط',rights:'جميع الحقوق محفوظة',
    footer_note:'أدوات مجانية بدون تسجيل · © <?= date('Y') ?> yassota.com',
    search_ph:'ابحث عن أداة…',menu:'القائمة',results:'أداة',no_result:'لا توجد أدوات مطابقة للبحث.',
    why:'لماذا أدوات yassota؟',footer_desc:'أدوات ويب مجانية — بدون تسجيل، بدون تتبع، بدون قيود.',
  }
};

var CAT_I18N = <?= json_encode(array_map(fn($v)=>['ar'=>$v['ar'],'en'=>$v['en']], $catMap)) ?>;

var HERO_TEXT = {
  en:{title:'<?= $toolCount ?>+ Free Online Web Tools',sub:'Professional tools that work directly in your browser — no registration, no limits, 100% free.'},
  ar:{title:'أكثر من <?= $toolCount ?> أداة ويب مجانية',sub:'أدوات احترافية تعمل مباشرة في متصفحك — بدون تسجيل، بدون حدود، مجانية 100%.'}
};

/* ── Language detection & persistence ── */
var _lang = 'en';
try {
  var stored = localStorage.getItem('yassota_lang');
  if (stored === 'ar' || stored === 'en') {
    _lang = stored;
  } else {
    _lang = (navigator.language || navigator.userLanguage || 'en').slice(0,2) === 'ar' ? 'ar' : 'en';
  }
} catch(e){ _lang = 'en'; }

function setLang(l) {
  _lang = l;
  try { localStorage.setItem('yassota_lang', l); } catch(e){}
  applyLang();
}

function applyLang() {
  var ar = _lang === 'ar';
  var root = document.getElementById('html-root');
  root.lang = ar ? 'ar' : 'en';
  root.dir  = ar ? 'rtl' : 'ltr';

  // Lang buttons
  document.getElementById('btn-en').classList.toggle('active', !ar);
  document.getElementById('btn-ar').classList.toggle('active', ar);

  // data-i18n
  document.querySelectorAll('[data-i18n]').forEach(function(el){
    var k = el.getAttribute('data-i18n');
    if (I18N[_lang][k] !== undefined) el.textContent = I18N[_lang][k];
  });

  // Category labels
  document.querySelectorAll('[data-cat-ar]').forEach(function(el){
    el.textContent = ar ? el.getAttribute('data-cat-ar') : el.getAttribute('data-cat-en');
  });

  // Tool cards
  document.querySelectorAll('.tool-card').forEach(function(card){
    var titleField = card.querySelector('[data-field=title]');
    var descField  = card.querySelector('[data-field=desc]');
    var tryField   = card.querySelector('[data-i18n=try_now]');
    if (titleField) titleField.textContent = ar
      ? card.getAttribute('data-title-ar')
      : (card.getAttribute('data-title-en') || card.getAttribute('data-title-ar'));
    if (descField)  descField.textContent  = ar
      ? card.getAttribute('data-desc-ar')
      : (card.getAttribute('data-desc-en')  || card.getAttribute('data-desc-ar'));
    if (tryField)   tryField.textContent   = I18N[_lang].try_now;
  });

  // Hero
  var ht = document.getElementById('hero-title');
  var hs = document.getElementById('hero-sub');
  if (ht) ht.textContent = HERO_TEXT[_lang].title;
  if (hs) hs.textContent = HERO_TEXT[_lang].sub;

  // Search placeholders
  ['searchInput','mobileSearchInput'].forEach(function(id){
    var el = document.getElementById(id);
    if (el) el.placeholder = I18N[_lang].search_ph;
  });

  // Menu button label
  var ml = document.getElementById('menuBtnLabel');
  if (ml) ml.textContent = I18N[_lang].menu;

  // Why title
  var wt = document.getElementById('why-title');
  if (wt) wt.textContent = I18N[_lang].why;

  // Footer desc
  var fd = document.getElementById('footer-desc');
  if (fd) fd.textContent = I18N[_lang].footer_desc;

  // No results text
  var nr = document.getElementById('noResultsText');
  if (nr) nr.textContent = I18N[_lang].no_result;

  // Page title
  document.getElementById('page-title').textContent = ar
    ? 'أدوات yassota — <?= $toolCount ?>+ أداة ويب مجانية'
    : 'yassota Tools — <?= $toolCount ?>+ Free Online Web Tools';

  // Sidebar footer note
  var sfn = document.querySelector('[data-i18n=footer_note]');
  if (sfn) sfn.textContent = I18N[_lang].footer_note;

  updateResultCount();
  updateSectionTitle();
}

/* ══ Category filter ══ */
var _activeCat = 'all';

function filterCat(cat) {
  _activeCat = cat;

  // Chips
  document.querySelectorAll('.filter-chip').forEach(function(c){
    var isActive = c.id === 'chip-' + cat;
    c.classList.toggle('active', isActive);
    if (isActive && cat !== 'all') {
      var color = getComputedStyle(c).getPropertyValue('--cc').trim();
      c.style.background  = color;
      c.style.borderColor = color;
      c.style.color       = '#fff';
    } else if (isActive) {
      c.style.background  = '#2563eb';
      c.style.borderColor = '#2563eb';
      c.style.color       = '#fff';
    } else {
      c.style.background = c.style.borderColor = c.style.color = '';
    }
  });

  // Sidebar links
  document.querySelectorAll('.sb-link').forEach(function(l){
    l.classList.toggle('active', l.id === 'sb-' + cat);
  });

  filterTools();
  updateSectionTitle();
}

/* ══ Search + visibility ══ */
function filterTools() {
  var q = ((document.getElementById('searchInput') || {}).value || '').toLowerCase().trim();
  var visible = 0;
  document.querySelectorAll('.tool-card').forEach(function(card){
    var ta  = (card.getAttribute('data-title-ar') || '').toLowerCase();
    var te  = (card.getAttribute('data-title-en') || '').toLowerCase();
    var da  = (card.getAttribute('data-desc-ar')  || '').toLowerCase();
    var de  = (card.getAttribute('data-desc-en')  || '').toLowerCase();
    var sl  = (card.getAttribute('href') || '').replace(/\/$/, '').split('/').pop() || '';
    var cat = card.getAttribute('data-cat') || '';
    var catOk    = _activeCat === 'all' || cat === _activeCat;
    var searchOk = !q || ta.includes(q) || te.includes(q) || da.includes(q) || de.includes(q) || sl.includes(q);
    var show = catOk && searchOk;
    card.classList.toggle('hidden', !show);
    if (show) visible++;
  });
  document.getElementById('noResults').style.display = visible ? 'none' : 'flex';
  updateResultCount(visible);
}

function updateResultCount(n) {
  var el = document.getElementById('result-count');
  if (!el) return;
  var cnt = (n !== undefined) ? n : document.querySelectorAll('.tool-card:not(.hidden)').length;
  el.textContent = cnt + ' ' + (I18N[_lang].results || 'tools');
}

function updateSectionTitle() {
  var el = document.getElementById('section-title');
  if (!el) return;
  if (_activeCat === 'all') {
    el.textContent = I18N[_lang].all_tools || 'All Tools';
  } else {
    var c = CAT_I18N[_activeCat];
    if (c) el.textContent = _lang === 'ar' ? c.ar : c.en;
  }
}

/* ══ Mobile search ══ */
var _mobOpen = false;
function toggleMobileSearch() {
  _mobOpen = !_mobOpen;
  var bar = document.getElementById('mobileSearchBar');
  bar.style.display = _mobOpen ? 'block' : 'none';
  if (_mobOpen) {
    document.getElementById('mobileSearchInput').focus();
    document.body.style.paddingTop = '108px';
  } else {
    document.body.style.paddingTop = '62px';
  }
}
function syncSearch(input) {
  var main = document.getElementById('searchInput');
  if (main) main.value = input.value;
  filterTools();
}

/* ══ Sidebar toggle ══ */
function toggleSidebar() {
  var sb = document.getElementById('sidebar');
  var ov = document.getElementById('overlay');
  var open = sb.classList.contains('open');
  sb.classList.toggle('open', !open);
  ov.classList.toggle('open', !open);
  document.body.style.overflow = open ? '' : 'hidden';
}
document.addEventListener('keydown', function(e){
  if (e.key !== 'Escape') return;
  var sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open')) toggleSidebar();
  if (_mobOpen) toggleMobileSearch();
});

/* ══ Responsive ══ */
function onResize() {
  var w = window.innerWidth;
  var mb = document.getElementById('mobileSearchBtn');
  if (mb) mb.style.display = w <= 640 ? 'flex' : 'none';
}
window.addEventListener('resize', onResize);
onResize();

/* ══ Init ══ */
applyLang();
filterTools();
</script>
</body>
</html>
