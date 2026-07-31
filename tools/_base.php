<?php
/**
 * yassota Tools — Shared Layout Engine
 * Every tool requires this file. It sets up:
 *   - Per-tool color theming via CSS custom properties
 *   - Professional independent-site look per tool
 *   - Breadcrumb, structured data, canonical URL
 *   - Rich article content styles for SEO-quality pages
 */

define('TOOLS_BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'tools.yassota.com'));
define('MAIN_SITE_URL', 'https://yassota.com');
define('ADSENSE_PUB', 'ca-pub-5506877998492189');

// Global state set by tool_head(), consumed by tool_header() & tool_footer()
$GLOBALS['__tool_color']    = '#2563eb';
$GLOBALS['__tool_title']    = '';
$GLOBALS['__tool_subtitle'] = '';

function tool_canonical_url(): string {
    $base   = rtrim(TOOLS_BASE_URL, '/');
    $reqUri = rtrim(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'), '/');
    return $base . $reqUri . '/';
}

/**
 * Emit the HTML <head> and open <body>.
 * Also stores color/title for header/footer use.
 */
function tool_head(string $title, string $desc, string $schema = '', string $color = '#2563eb', string $subtitle = ''): void {
    $GLOBALS['__tool_color']    = $color;
    $GLOBALS['__tool_title']    = $title;
    $GLOBALS['__tool_subtitle'] = $subtitle ?: $desc;

    $adsensePub  = ADSENSE_PUB;
    $canonical   = tool_canonical_url();
    $schemaBlock = $schema ? "\n<script type=\"application/ld+json\">$schema</script>" : '';

    // Derive light variant of the tool color for backgrounds
    $c = ltrim($color, '#');
    $r = hexdec(substr($c, 0, 2));
    $g = hexdec(substr($c, 2, 2));
    $b = hexdec(substr($c, 4, 2));
    $colorRgb   = "$r,$g,$b";
    $siteTitle  = preg_replace('/\s*[|—–-].*/u', '', $title);

    // Breadcrumb slug from path
    $pathParts = array_filter(explode('/', trim($_SERVER['REQUEST_URI'] ?? '', '/')));
    $toolSlug  = end($pathParts) ?: (count($pathParts) > 1 ? $pathParts[array_key_last($pathParts) - 1] ?? '' : '');
    $GLOBALS['__tool_slug'] = $toolSlug;

    // BreadcrumbList schema
    $breadcrumbSchema = json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'yassota', 'item' => MAIN_SITE_URL],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'الأدوات', 'item' => TOOLS_BASE_URL . '/tools/'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $siteTitle, 'item' => $canonical],
        ],
    ]);

    echo <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title}</title>
<meta name="description" content="{$desc}">
<meta property="og:title" content="{$title}">
<meta property="og:description" content="{$desc}">
<meta property="og:type" content="website">
<meta property="og:url" content="{$canonical}">
<meta property="og:site_name" content="yassota أدوات">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{$title}">
<meta name="twitter:description" content="{$desc}">
<meta name="theme-color" content="{$color}">
<meta name="robots" content="index,follow">
<link rel="canonical" href="{$canonical}">
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={$adsensePub}" crossorigin="anonymous"></script>
{$schemaBlock}
<script type="application/ld+json">{$breadcrumbSchema}</script>
<style>
/* ── Reset & Base ─────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%;font-size:16px}
body{font-family:'Segoe UI',system-ui,-apple-system,Tahoma,Arial,sans-serif;background:var(--bg);color:var(--text);direction:rtl;line-height:1.65;min-height:100vh;overflow-x:hidden;padding-top:62px}
a{color:inherit;text-decoration:none}img{max-width:100%;display:block}
button,input,select,textarea{font-family:inherit;font-size:inherit}

/* ── Tool Color Theme (per-tool CSS vars) ─────────── */
:root {
  --tool-h:     {$color};
  --tool-rgb:   {$colorRgb};
  --tool-light: rgba({$colorRgb},.09);
  --tool-mid:   rgba({$colorRgb},.18);
  --tool-border: rgba({$colorRgb},.30);

  --bg:         #f8fafc;
  --surface:    #ffffff;
  --surface2:   #f1f5f9;
  --border:     #e2e8f0;
  --text:       #0f172a;
  --text2:      #334155;
  --muted:      #64748b;
  --muted2:     #94a3b8;

  --radius-sm:  8px;
  --radius:     12px;
  --radius-lg:  18px;
  --shadow-sm:  0 1px 4px rgba(0,0,0,.06);
  --shadow:     0 2px 12px rgba(0,0,0,.08);
  --shadow-lg:  0 4px 24px rgba(0,0,0,.12);
  --f-mono:     'Consolas','Courier New',monospace;
}

/* ── Header ───────────────────────────────────────── */
.t-header {
  background:#0b1120;
  color:#e2e8f0;
  display:flex;align-items:center;justify-content:space-between;
  padding:0 24px;height:62px;
  position:fixed;top:0;left:0;right:0;z-index:200;
  box-shadow:0 2px 16px rgba(0,0,0,.3);
  border-top:4px solid var(--tool-h);
}
.t-logo {
  font-size:17px;font-weight:800;letter-spacing:-.5px;
  display:flex;align-items:center;gap:8px;
}
.t-logo-mark {
  width:30px;height:30px;background:var(--tool-h);border-radius:7px;
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;
}
.t-logo-mark svg{width:16px;height:16px;stroke:#fff;fill:none;stroke-width:2.5}
.t-logo-text{
  background:linear-gradient(135deg,#22c55e 0%,#3b82f6 35%,#f59e0b 70%,#ef4444 100%);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.t-logo-text em{font-style:normal}
.t-nav{display:flex;align-items:center;gap:4px}
.t-nav a {
  color:rgba(226,232,240,.7);font-size:12px;font-weight:500;
  padding:6px 12px;border-radius:7px;transition:all .18s;
  display:flex;align-items:center;gap:5px;
}
.t-nav a:hover{color:#fff;background:rgba(255,255,255,.1)}
.t-nav a.active{color:#fff;background:var(--tool-light)}
.t-nav svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2;flex-shrink:0}

/* ── Hamburger ────────────────────────────────────── */
.t-hamburger{
  display:none;background:transparent;border:none;cursor:pointer;
  padding:8px;border-radius:8px;color:rgba(226,232,240,.7);
  transition:background .15s;flex-shrink:0;
}
.t-hamburger:hover{background:rgba(255,255,255,.1);color:#fff}
.t-hamburger svg{display:block}

/* ── Breadcrumb ───────────────────────────────────── */
.t-breadcrumb {
  background:var(--surface);border-bottom:1px solid var(--border);
  padding:10px 24px;font-size:12px;color:var(--muted);
  display:flex;align-items:center;gap:6px;flex-wrap:wrap;
}
.t-breadcrumb a{color:var(--muted);transition:color .15s}
.t-breadcrumb a:hover{color:var(--tool-h)}
.t-breadcrumb span{color:var(--tool-h);font-weight:600}
.t-breadcrumb svg{width:12px;height:12px;stroke:var(--muted2);fill:none;stroke-width:2;flex-shrink:0}

/* ── Main Content ─────────────────────────────────── */
.t-main{max-width:900px;margin:0 auto;padding:28px 20px 80px}

/* ── Hero ─────────────────────────────────────────── */
.t-hero {
  background:var(--surface);border-radius:var(--radius-lg);
  padding:32px 28px;margin-bottom:20px;
  box-shadow:var(--shadow);border:1px solid var(--border);
  position:relative;overflow:hidden;
}
.t-hero::after {
  content:'';position:absolute;top:-60px;left:-60px;
  width:220px;height:220px;border-radius:50%;
  background:var(--tool-light);pointer-events:none;
}
.t-hero-icon {
  width:56px;height:56px;border-radius:var(--radius);
  background:var(--tool-light);border:1.5px solid var(--tool-border);
  display:flex;align-items:center;justify-content:center;
  margin-bottom:16px;position:relative;z-index:1;
}
.t-hero-icon svg{width:26px;height:26px;stroke:var(--tool-h);fill:none;stroke-width:2}
.t-hero h1 {
  font-size:26px;font-weight:800;color:var(--text);margin-bottom:10px;
  line-height:1.25;position:relative;z-index:1;
}
.t-hero p {color:var(--muted);font-size:15px;line-height:1.75;position:relative;z-index:1}
.t-hero-badges {
  display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;position:relative;z-index:1;
}
.t-badge {
  display:inline-flex;align-items:center;gap:5px;
  padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;
  background:var(--tool-light);color:var(--tool-h);
  border:1px solid var(--tool-border);
}
.t-badge svg{width:11px;height:11px;stroke:currentColor;fill:none;stroke-width:2}

/* ── Cards ────────────────────────────────────────── */
.t-card {
  background:var(--surface);border-radius:var(--radius-lg);
  padding:24px 22px;box-shadow:var(--shadow-sm);
  border:1px solid var(--border);margin-bottom:18px;
}
.t-card > h2:first-child,.t-card > h2:first-of-type {
  font-size:16px;font-weight:700;color:var(--text);margin-bottom:16px;
  padding-bottom:10px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:8px;
}
.t-card > h2 svg{width:16px;height:16px;stroke:var(--tool-h);fill:none;stroke-width:2}

/* ── Article content ──────────────────────────────── */
.t-article { font-size:15px;color:var(--text2);line-height:1.9 }
.t-article h2 {
  font-size:20px;font-weight:800;color:var(--text);
  margin:36px 0 16px;padding-bottom:8px;
  border-bottom:2px solid var(--tool-light);
  display:flex;align-items:center;gap:10px;
}
.t-article h2 svg{width:20px;height:20px;stroke:var(--tool-h);fill:none;stroke-width:2;flex-shrink:0}
.t-article h3 {font-size:16px;font-weight:700;color:var(--text);margin:24px 0 10px}
.t-article p {margin-bottom:14px}
.t-article ul,.t-article ol {margin:0 24px 16px;padding:0}
.t-article li {margin-bottom:8px}
.t-article strong {color:var(--text);font-weight:700}
.t-article code {
  background:var(--surface2);color:var(--tool-h);
  padding:2px 7px;border-radius:5px;font-family:var(--f-mono);font-size:13px;
}
.t-article blockquote {
  border-right:4px solid var(--tool-h);padding:14px 18px;
  background:var(--tool-light);border-radius:0 var(--radius-sm) var(--radius-sm) 0;
  margin:20px 0;color:var(--text2);font-style:italic;
}
.t-faq-item {
  border:1px solid var(--border);border-radius:var(--radius);
  margin-bottom:10px;overflow:hidden;
}
.t-faq-q {
  padding:14px 18px;font-weight:700;font-size:14px;cursor:pointer;
  display:flex;align-items:center;justify-content:space-between;
  background:var(--surface);transition:background .15s;
}
.t-faq-q:hover{background:var(--tool-light)}
.t-faq-q svg{width:16px;height:16px;stroke:var(--muted);fill:none;stroke-width:2.5;flex-shrink:0;transition:transform .2s}
.t-faq-q.open svg{transform:rotate(180deg);stroke:var(--tool-h)}
.t-faq-a {display:none;padding:0 18px 14px;font-size:14px;color:var(--text2);line-height:1.75}
.t-faq-a.open{display:block}

/* ── Form Elements ────────────────────────────────── */
.t-label{display:block;font-size:13px;font-weight:600;color:var(--text2);margin-bottom:6px}
.t-input {
  width:100%;padding:11px 14px;
  border:1.5px solid var(--border);border-radius:var(--radius-sm);
  font-size:14px;color:var(--text);background:var(--surface2);
  outline:none;transition:border-color .18s,background .18s;direction:rtl;
}
.t-input:focus{border-color:var(--tool-h);background:var(--surface);box-shadow:0 0 0 3px var(--tool-light)}
.t-textarea{width:100%;padding:12px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:14px;color:var(--text);background:var(--surface2);outline:none;transition:border-color .18s;resize:vertical;min-height:130px;direction:rtl;line-height:1.6}
.t-textarea:focus{border-color:var(--tool-h);background:var(--surface);box-shadow:0 0 0 3px var(--tool-light)}
select.t-input option{direction:rtl}

/* ── File Input ───────────────────────────────────── */
input[type=file]{
  width:100%;border:1.5px dashed var(--border);border-radius:var(--radius-sm);
  background:var(--surface2);color:var(--text);font-size:14px;cursor:pointer;
  transition:border-color .18s;overflow:hidden;padding:0;
}
input[type=file]:focus,input[type=file]:hover{border-color:var(--tool-h);outline:none}
input[type=file]::file-selector-button{
  padding:11px 16px;background:var(--tool-h);color:#fff;border:none;
  font-size:13px;font-weight:700;cursor:pointer;transition:opacity .15s;
  font-family:inherit;margin-inline-end:12px;
}
input[type=file]::file-selector-button:hover{opacity:.85}

/* ── Buttons ──────────────────────────────────────── */
.t-btn {
  display:inline-flex;align-items:center;gap:8px;
  padding:12px 24px;background:var(--tool-h);color:#fff;
  border:none;border-radius:var(--radius-sm);font-size:14px;font-weight:700;
  cursor:pointer;transition:all .18s;letter-spacing:.2px;
}
.t-btn:hover{opacity:.88;transform:translateY(-1px);box-shadow:0 4px 12px rgba(var(--tool-rgb),.35)}
.t-btn:active{transform:none;box-shadow:none}
.t-btn:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none}
.t-btn svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2.5;flex-shrink:0}
.t-btn-outline{background:transparent;color:var(--tool-h);border:1.5px solid var(--tool-h)}
.t-btn-outline:hover{background:var(--tool-light)}
.t-btn-ghost{background:var(--surface2);color:var(--text2);border:1px solid var(--border)}
.t-btn-ghost:hover{background:var(--border);box-shadow:none;transform:none}
.t-btn-sm{padding:8px 16px;font-size:12px}

/* ── Result / Status ──────────────────────────────── */
.t-result {
  background:rgba(22,163,74,.07);border:1.5px solid rgba(22,163,74,.3);
  border-radius:var(--radius-sm);padding:14px 16px;
  font-size:14px;color:#166534;word-break:break-all;margin-top:14px;
}
.t-error {
  background:rgba(220,38,38,.07);border:1.5px solid rgba(220,38,38,.3);
  border-radius:var(--radius-sm);padding:14px 16px;
  font-size:14px;color:#991b1b;margin-top:14px;
}
.t-info-box {
  background:var(--tool-light);border:1px solid var(--tool-border);
  border-radius:var(--radius-sm);padding:14px 16px;
  font-size:13px;color:var(--text2);margin-top:14px;
}

/* ── Utility ──────────────────────────────────────── */
.t-row{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-start}
.t-col{flex:1 1 220px;min-width:0}
.t-tag{display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:var(--tool-light);color:var(--tool-h);margin:3px 2px}
.t-progress{height:6px;background:var(--border);border-radius:3px;overflow:hidden;margin-top:8px}
.t-progress-bar{height:100%;background:var(--tool-h);border-radius:3px;transition:width .3s}
.t-mono{font-family:var(--f-mono);font-size:13px}
.t-ad{margin:22px 0;text-align:center;min-height:90px}
.chip-group{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.chip{padding:7px 15px;border-radius:20px;font-size:13px;cursor:pointer;border:1.5px solid var(--border);background:var(--surface2);transition:all .18s;font-weight:500}
.chip:hover,.chip.active{background:var(--tool-h);color:#fff;border-color:var(--tool-h)}
.t-divider{height:1px;background:var(--border);margin:20px 0}
.t-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.t-grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.t-stat-box{background:var(--surface2);border-radius:var(--radius);padding:16px;text-align:center}
.t-stat-box .num{font-size:24px;font-weight:800;color:var(--tool-h)}
.t-stat-box .lbl{font-size:12px;color:var(--muted);margin-top:4px}

/* ── Footer ───────────────────────────────────────── */
.t-footer {
  background:#0b1120;color:rgba(226,232,240,.7);
  padding:40px 24px 28px;margin-top:60px;
}
.t-footer-grid {
  max-width:900px;margin:0 auto;
  display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:32px;
  padding-bottom:28px;border-bottom:1px solid rgba(255,255,255,.08);
}
.t-footer-col h4{font-size:13px;font-weight:700;color:#fff;margin-bottom:12px;letter-spacing:.5px;text-transform:uppercase}
.t-footer-col ul{list-style:none}
.t-footer-col li{margin-bottom:7px}
.t-footer-col a{font-size:13px;color:rgba(226,232,240,.6);transition:color .15s}
.t-footer-col a:hover{color:var(--tool-h)}
.t-footer-bottom {
  max-width:900px;margin:20px auto 0;
  display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;
  font-size:12px;color:rgba(226,232,240,.4);
}
.t-footer-logo{
  font-weight:800;font-size:16px;
  background:linear-gradient(135deg,#22c55e 0%,#3b82f6 35%,#f59e0b 70%,#ef4444 100%);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}

/* ── Mobile Nav Drawer ────────────────────────────── */
.t-mobile-nav{
  position:fixed;top:0;right:0;bottom:0;width:280px;
  background:#0b1120;z-index:600;padding:20px 0;overflow-y:auto;
  transform:translateX(110%);transition:transform .28s cubic-bezier(.4,0,.2,1);
  box-shadow:-6px 0 32px rgba(0,0,0,.5);
}
.t-mobile-nav.open{transform:translateX(0)}
.t-mobile-nav-head{
  display:flex;align-items:center;justify-content:space-between;
  padding:0 20px 16px;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:8px;
}
.t-mobile-nav-head span{
  font-size:16px;font-weight:800;
  background:linear-gradient(135deg,#22c55e 0%,#3b82f6 35%,#f59e0b 70%,#ef4444 100%);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.t-mobile-nav-head button{
  background:transparent;border:none;cursor:pointer;
  color:rgba(226,232,240,.6);font-size:20px;padding:4px;line-height:1;
  transition:color .15s;
}
.t-mobile-nav-head button:hover{color:#fff}
.t-mobile-nav a{
  display:flex;align-items:center;gap:10px;
  padding:12px 20px;font-size:14px;color:rgba(226,232,240,.75);
  transition:background .15s,color .15s;
}
.t-mobile-nav a:hover{background:rgba(255,255,255,.07);color:#fff}
.t-mobile-nav svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2;flex-shrink:0}
.t-nav-overlay{
  position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:599;
  opacity:0;pointer-events:none;transition:opacity .25s;
}
.t-nav-overlay.open{opacity:1;pointer-events:all}

/* ── Responsive ───────────────────────────────────── */
@media(max-width:640px){
  .t-hero h1{font-size:21px}
  .t-hero{padding:22px 16px}
  .t-main{padding:18px 12px 60px}
  .t-card{padding:18px 14px}
  .t-header{padding:0 14px}
  .t-hamburger{display:flex;align-items:center;justify-content:center}
  .t-nav{display:none}
  .t-grid-2,.t-grid-3{grid-template-columns:1fr}
  .t-article h2{font-size:17px}
  .t-breadcrumb{padding:8px 14px}
}
@media(max-width:400px){
  .t-btn{padding:11px 16px;font-size:13px}
}
</style>
</head>
<body>
HTML;
}

/**
 * Render the fixed header with tool branding, nav, hamburger, and mobile drawer.
 */
function tool_header(string $toolName = '', string $toolsIndexUrl = ''): void {
    $main      = MAIN_SITE_URL;
    $toolsUrl  = $toolsIndexUrl ?: (TOOLS_BASE_URL . '/tools/');
    $name      = $toolName ?: preg_replace('/\s*[|—–-].*/u', '', $GLOBALS['__tool_title'] ?? 'أداة');
    $shortName = mb_strlen($name) > 30 ? mb_substr($name, 0, 28) . '…' : $name;

    echo <<<HTML
<header class="t-header">
  <a href="{$main}" class="t-logo" aria-label="yassota الصفحة الرئيسية">
    <div class="t-logo-mark">
      <svg viewBox="0 0 24 24" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    </div>
    <span class="t-logo-text">yassota</span>
  </a>
  <nav class="t-nav" aria-label="التنقل الرئيسي">
    <a href="{$toolsUrl}">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      <span>جميع الأدوات</span>
    </a>
    <a href="{$main}/apps">
      <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
      <span>التطبيقات</span>
    </a>
    <a href="{$main}">
      <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      <span>الرئيسية</span>
    </a>
  </nav>
  <button class="t-hamburger" onclick="toggleToolNav()" aria-label="القائمة" aria-expanded="false" id="t-hamburger">
    <svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor" aria-hidden="true">
      <circle cx="9" cy="3" r="2.2"/><circle cx="9" cy="9" r="2.2"/><circle cx="9" cy="15" r="2.2"/>
    </svg>
  </button>
</header>

<!-- Mobile nav drawer -->
<nav class="t-mobile-nav" id="tMobileNav" aria-label="القائمة المتنقلة">
  <div class="t-mobile-nav-head">
    <span>yassota</span>
    <button onclick="toggleToolNav()" aria-label="إغلاق">✕</button>
  </div>
  <a href="{$main}">
    <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    الرئيسية
  </a>
  <a href="{$toolsUrl}">
    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    جميع الأدوات
  </a>
  <a href="{$main}/apps">
    <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
    تطبيقات
  </a>
  <a href="{$main}/categories">
    <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
    التصنيفات
  </a>
  <a href="{$main}/privacy">
    <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    الخصوصية
  </a>
  <a href="{$main}/terms">
    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    شروط الاستخدام
  </a>
</nav>
<div class="t-nav-overlay" id="tNavOverlay" onclick="toggleToolNav()"></div>

<nav class="t-breadcrumb" aria-label="مسار التنقل">
  <a href="{$main}">yassota</a>
  <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
  <a href="{$toolsUrl}">الأدوات</a>
  <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
  <span aria-current="page">{$shortName}</span>
</nav>
HTML;
}

/**
 * Render the rich footer with tool-specific links.
 */
function tool_footer(): void {
    $main = MAIN_SITE_URL;
    $yr   = date('Y');
    $toolsUrl = TOOLS_BASE_URL . '/tools/';

    // Popular tools links for footer
    $popularTools = [
        ['compress',    'ضاغط الصور'],
        ['currency',    'محوّل العملات'],
        ['qr-code',     'مولد QR Code'],
        ['password',    'مولد كلمات المرور'],
        ['json-fmt',    'منسّق JSON'],
        ['color-picker','أداة الألوان'],
        ['uuid',        'مولد UUID'],
        ['syp',         'سعر الليرة السورية'],
        ['ip',          'معلومات IP'],
        ['invoice',     'مولد الفاتورة'],
        ['chat',        'ياسمين AI'],
        ['barcode',     'مولد الباركود'],
    ];

    $toolLinks = '';
    foreach ($popularTools as [$slug, $label]) {
        $toolLinks .= "<li><a href=\"{$toolsUrl}{$slug}/\">{$label}</a></li>\n      ";
    }

    echo <<<HTML
<footer class="t-footer" role="contentinfo">
  <div class="t-footer-grid">
    <div class="t-footer-col">
      <h4>yassota أدوات</h4>
      <p style="font-size:13px;color:rgba(226,232,240,.5);line-height:1.75;margin-bottom:16px">أدوات ويب مجانية 100% تعمل مباشرة في متصفحك — بدون تسجيل، بدون تتبع، بدون قيود.</p>
      <a href="{$toolsUrl}" style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.08);color:#fff;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;transition:background .2s">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        تصفح جميع الأدوات
      </a>
    </div>
    <div class="t-footer-col">
      <h4>أدوات شائعة</h4>
      <ul>{$toolLinks}</ul>
    </div>
    <div class="t-footer-col">
      <h4>الموقع الرئيسي</h4>
      <ul>
        <li><a href="{$main}">yassota — تحميل تطبيقات</a></li>
        <li><a href="{$main}/categories">تصفح التصنيفات</a></li>
        <li><a href="{$main}/top">الأكثر تحميلاً</a></li>
        <li><a href="{$main}/articles">المقالات</a></li>
        <li><a href="{$main}/privacy">سياسة الخصوصية</a></li>
        <li><a href="{$main}/terms">شروط الاستخدام</a></li>
        <li><a href="{$main}/contact">تواصل معنا</a></li>
      </ul>
    </div>
    <div class="t-footer-col">
      <h4>معلومات</h4>
      <ul>
        <li><a href="{$toolsUrl}">فهرس الأدوات</a></li>
        <li><a href="{$main}/about">من نحن</a></li>
        <li><a href="{$main}/dmca">إبلاغ عن محتوى</a></li>
      </ul>
      <div style="margin-top:20px;padding:14px;background:rgba(255,255,255,.05);border-radius:10px;border:1px solid rgba(255,255,255,.08)">
        <div style="font-size:11px;color:rgba(226,232,240,.5);margin-bottom:6px;font-weight:600;letter-spacing:.5px">متوافق مع</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
          <span style="font-size:11px;color:rgba(226,232,240,.6);background:rgba(255,255,255,.06);padding:3px 8px;border-radius:5px">Chrome</span>
          <span style="font-size:11px;color:rgba(226,232,240,.6);background:rgba(255,255,255,.06);padding:3px 8px;border-radius:5px">Firefox</span>
          <span style="font-size:11px;color:rgba(226,232,240,.6);background:rgba(255,255,255,.06);padding:3px 8px;border-radius:5px">Safari</span>
          <span style="font-size:11px;color:rgba(226,232,240,.6);background:rgba(255,255,255,.06);padding:3px 8px;border-radius:5px">Edge</span>
        </div>
      </div>
    </div>
  </div>
  <div class="t-footer-bottom">
    <div class="t-footer-logo">yassota</div>
    <div>جميع الأدوات مجانية · لا تتطلب تسجيلاً · © {$yr} yassota.com · جميع الحقوق محفوظة</div>
    <div style="display:flex;gap:12px">
      <a href="{$main}/privacy" style="color:inherit">الخصوصية</a>
      <a href="{$main}/terms" style="color:inherit">الشروط</a>
    </div>
  </div>
</footer>
<script>
// FAQ accordion
document.querySelectorAll('.t-faq-q').forEach(q => {
  q.addEventListener('click', () => {
    const a = q.nextElementSibling;
    if (!a) return;
    const isOpen = a.classList.contains('open');
    document.querySelectorAll('.t-faq-a.open').forEach(el => { el.classList.remove('open'); el.previousElementSibling?.classList.remove('open'); });
    if (!isOpen) { a.classList.add('open'); q.classList.add('open'); }
  });
});

// Mobile nav toggle
function toggleToolNav() {
  const nav     = document.getElementById('tMobileNav');
  const overlay = document.getElementById('tNavOverlay');
  const btn     = document.getElementById('t-hamburger');
  if (!nav) return;
  const isOpen = nav.classList.contains('open');
  if (isOpen) {
    nav.classList.remove('open');
    overlay.classList.remove('open');
    btn?.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  } else {
    nav.classList.add('open');
    overlay.classList.add('open');
    btn?.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }
}
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    const nav = document.getElementById('tMobileNav');
    if (nav && nav.classList.contains('open')) toggleToolNav();
  }
});
</script>
</body></html>
HTML;
}

/**
 * Render an AdSense ad unit.
 */
function tool_ad(): void {
    $pub = ADSENSE_PUB;
    echo <<<HTML
<div class="t-ad">
  <ins class="adsbygoogle" style="display:block" data-ad-client="{$pub}" data-ad-slot="auto" data-ad-format="auto" data-full-width-responsive="true"></ins>
  <script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>
</div>
HTML;
}

/**
 * Emit a section divider with heading and optional icon SVG path.
 */
function tool_section(string $title, string $iconPath = ''): void {
    $icon = $iconPath
        ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' . $iconPath . '</svg>'
        : '';
    echo '<h2 style="font-size:18px;font-weight:800;color:var(--text);margin:32px 0 16px;display:flex;align-items:center;gap:10px">' . $icon . htmlspecialchars($title, ENT_QUOTES) . '</h2>';
}
