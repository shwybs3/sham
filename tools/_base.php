<?php
// Shared layout helpers for yassota web tools
// Include from each tool: require_once dirname(__DIR__).'/_base.php';

define('TOOLS_BASE_URL', (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on'?'https':'http').'://'.($_SERVER['HTTP_HOST']??'tools.yassota.com'));
define('MAIN_SITE_URL', 'https://yassota.com');
define('ADSENSE_PUB', 'ca-pub-5506877998492189');

$toolHost = $_SERVER['HTTP_HOST'] ?? '';
$toolSlug = explode('.', $toolHost)[0] ?? 'tools';

/** Returns the canonical URL for this tool page, derived from the request URI. */
function tool_canonical_url(): string {
    $base   = rtrim(TOOLS_BASE_URL, '/');
    $reqUri = rtrim(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'), '/');
    // Normalise: ensure path starts with /tools/ when served from main site
    return $base . $reqUri . '/';
}

function tool_head(string $title, string $desc, string $schema = '', string $color = '#2563eb'): void {
    $adsensePub  = ADSENSE_PUB;
    $siteUrl     = rtrim(TOOLS_BASE_URL, '/');
    $canonical   = tool_canonical_url();
    $schemaBlock = $schema ? "\n<script type=\"application/ld+json\">$schema</script>" : '';
    echo <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>$title</title>
<meta name="description" content="$desc">
<meta property="og:title" content="$title">
<meta property="og:description" content="$desc">
<meta property="og:type" content="website">
<meta property="og:url" content="$canonical">
<meta property="og:site_name" content="yassota أدوات">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="$title">
<meta name="twitter:description" content="$desc">
<meta name="theme-color" content="$color">
<link rel="canonical" href="$canonical">
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=$adsensePub" crossorigin="anonymous"></script>
$schemaBlock
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{font-family:'Segoe UI',Tahoma,Arial,sans-serif;background:#f1f5f9;color:#0f172a;direction:rtl;line-height:1.6;min-height:100vh;overflow-x:hidden}
a{color:inherit;text-decoration:none}
img{max-width:100%;display:block}
button,input,select,textarea{font-family:inherit}
.t-header{background:#0c1e36;color:#e2e8f0;display:flex;align-items:center;justify-content:space-between;padding:0 20px;height:56px;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.25)}
.t-logo{font-size:18px;font-weight:800;letter-spacing:-0.5px}.t-logo span{color:#0ea5e9}
.t-nav a{color:rgba(226,232,240,.75);font-size:13px;padding:6px 12px;border-radius:8px;transition:color .2s}
.t-nav a:hover{color:#fff}
.t-main{max-width:860px;margin:0 auto;padding:28px 16px 60px}
.t-hero{background:#fff;border-radius:18px;padding:28px 24px;margin-bottom:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);border:1px solid #e2e8f0}
.t-hero-icon{width:54px;height:54px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:14px}
.t-hero h1{font-size:24px;font-weight:800;color:#0f172a;margin-bottom:8px}
.t-hero p{color:#64748b;font-size:14px;line-height:1.7}
.t-card{background:#fff;border-radius:16px;padding:22px 20px;box-shadow:0 1px 6px rgba(0,0,0,.06);border:1px solid #e2e8f0;margin-bottom:16px}
.t-card h2{font-size:16px;font-weight:700;color:#0f172a;margin-bottom:14px}
.t-label{display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px}
.t-input{width:100%;padding:11px 14px;border:1.5px solid #cbd5e1;border-radius:10px;font-size:14px;color:#0f172a;background:#f8fafc;outline:none;transition:border-color .2s;direction:rtl}
.t-input:focus{border-color:#2563eb;background:#fff}
.t-textarea{width:100%;padding:12px 14px;border:1.5px solid #cbd5e1;border-radius:10px;font-size:14px;color:#0f172a;background:#f8fafc;outline:none;transition:border-color .2s;resize:vertical;min-height:120px;direction:rtl}
.t-textarea:focus{border-color:#2563eb;background:#fff}
.t-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:#2563eb;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:all .2s}
.t-btn:hover{background:#1d4ed8}
.t-btn:disabled{background:#94a3b8;cursor:not-allowed}
.t-btn-secondary{background:#f1f5f9;color:#0f172a;border:1.5px solid #cbd5e1}
.t-btn-secondary:hover{background:#e2e8f0}
.t-btn-success{background:#16a34a}.t-btn-success:hover{background:#15803d}
.t-result{background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:14px 16px;font-size:14px;color:#166534;word-break:break-all;margin-top:14px}
.t-error{background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:14px 16px;font-size:14px;color:#991b1b;margin-top:14px}
.t-row{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-start}
.t-col{flex:1 1 220px;min-width:0}
.t-tag{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:#dbeafe;color:#1e40af;margin:3px 2px}
.t-progress{height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden;margin-top:8px}
.t-progress-bar{height:100%;background:#2563eb;border-radius:3px;transition:width .3s}
.t-footer{text-align:center;color:#94a3b8;font-size:12px;padding:20px 16px;border-top:1px solid #e2e8f0;margin-top:40px}
.t-footer a{color:#64748b}
.t-ad{margin:20px 0;text-align:center;min-height:90px}
.chip-group{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.chip{padding:6px 14px;border-radius:20px;font-size:13px;cursor:pointer;border:1.5px solid #cbd5e1;background:#f8fafc;transition:all .2s}
.chip:hover,.chip.active{background:#2563eb;color:#fff;border-color:#2563eb}
select.t-input option{direction:rtl}
@media(max-width:600px){.t-hero h1{font-size:20px}.t-main{padding:18px 12px 50px}.t-hero{padding:20px 16px}}
</style>
</head>
<body>
HTML;
}

function tool_header(string $title = 'yassota أدوات'): void {
    $main = MAIN_SITE_URL;
    echo <<<HTML
<header class="t-header">
  <a href="$main" class="t-logo">yass<span>ota</span></a>
  <nav class="t-nav" style="display:flex;gap:4px">
    <a href="$main">الموقع الرئيسي</a>
    <a href="$main/tools" style="display:none">أدواتنا</a>
  </nav>
</header>
HTML;
}

function tool_footer(): void {
    $main = MAIN_SITE_URL;
    $yr   = date('Y');
    echo <<<HTML
<footer class="t-footer">
  <p>جزء من شبكة <a href="$main">yassota.com</a> — لتحميل تطبيقات الأندرويد مجاناً</p>
  <p style="margin-top:4px">جميع الأدوات مجانية 100% ولا تتطلب تسجيلاً · © $yr yassota</p>
</footer>
</body></html>
HTML;
}

function tool_ad(): void {
    $pub = ADSENSE_PUB;
    echo <<<HTML
<div class="t-ad">
  <ins class="adsbygoogle" style="display:block" data-ad-client="$pub" data-ad-slot="auto" data-ad-format="auto" data-full-width-responsive="true"></ins>
  <script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>
</div>
HTML;
}
