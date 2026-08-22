<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/storage.php';
require_once __DIR__.'/functions.php';

// Handle OAuth callback redirect
if (($_GET['action']??'') === 'oauth_callback') {
    require __DIR__.'/api.php'; exit;
}

$u   = current_user();
if (!$u) { $uid = uid_from_cookie(); $u = user_ensure($uid); }
$uid = $u['id'];
$q   = quota($uid);
$k   = key_get($uid);
$oauthUrl = google_oauth_url();
$pct = $q['limit'] > 0 ? min(100, round($q['used']/$q['limit']*100)) : 0;
$pctLeft = 100 - $pct;
$dashR = 54; $dashC = round(2*3.14159*$dashR,1); $dashOff = round($dashC*$pct/100,1);
$planName = PLANS[$q['plan']]['name'] ?? 'Free';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="google-site-verification" content="TSJD6_N7XRQC1haHsZYd-RwDmYV3XkVXRzaGlgA6VAk">
<title>IndexPro — Submit URLs to Google's Indexing API Instantly</title>
<meta name="description" content="Submit your URLs directly to Google's official Indexing API using your own Service Account key. Sitemap import, CSV extraction, submission history, and a free plan with 100 URLs/day.">
<meta name="keywords" content="google indexing api, google search console, submit urls to google, sitemap submission, indexnow">
<meta name="theme-color" content="#dc2626">
<meta property="og:title" content="IndexPro — Google Indexing API Tool">
<meta property="og:description" content="Submit URLs directly to Google's official Indexing API. Free plan included, no credit card required.">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= h(site_url()) ?>">
<link rel="icon" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" rx="6" fill="#dc2626"/><path d="M13 3 5 14h6l-1 8 9-13h-6l1-7z" fill="#fff"/></svg>') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ════════════════ RESET + ROOT ════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#f7f7f8;--bg2:#ffffff;--surface:#ffffff;--surface2:#f4f5f7;--surface3:#e9eaed;
  --border:#e6e7eb;--border2:#d4d6dc;
  --red:#dc2626;--red2:#b91c1c;--red3:#991b1b;--red-soft:#fee2e2;
  --slate:#6b7280;--green:#16a34a;--yellow:#d97706;--charcoal:#374151;
  --gold:#d97706;--text:#16181d;--muted:#6b7280;--muted2:#464b54;
  --radius:14px;--radius-sm:8px;--radius-lg:20px;
  --glow-red:0 0 24px rgba(220,38,38,.18);
  --shadow-sm:0 2px 8px rgba(17,17,20,.06);--shadow-md:0 8px 24px rgba(17,17,20,.08);--shadow-lg:0 16px 48px rgba(17,17,20,.12);
}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden}
a{text-decoration:none;color:inherit}
button{font-family:'Inter',sans-serif;cursor:pointer}
img{max-width:100%}

/* ════════════════ ANIMATIONS ════════════════ */
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
@keyframes glow{0%,100%{box-shadow:0 0 20px rgba(220,38,38,.3)}50%{box-shadow:0 0 40px rgba(220,38,38,.6)}}
@keyframes gradientShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
@keyframes countUp{from{opacity:0;transform:scale(.8)}to{opacity:1;transform:scale(1)}}
@keyframes slideInRight{from{transform:translateX(100%)}to{transform:translateX(0)}}
@keyframes slideInLeft{from{transform:translateX(-100%)}to{transform:translateX(0)}}
@keyframes ripple{to{transform:scale(4);opacity:0}}
@keyframes orbit{from{transform:rotate(0deg) translateX(80px) rotate(0deg)}to{transform:rotate(360deg) translateX(80px) rotate(-360deg)}}
@keyframes blinkCursor{50%{border-color:transparent}}

/* ════════════════ HEADER ════════════════ */
.header{position:sticky;top:0;z-index:200;background:rgba(255,255,255,.88);backdrop-filter:blur(20px);
  border-bottom:1px solid var(--border);padding:0 24px;height:64px;
  display:flex;align-items:center;justify-content:space-between}
.logo{display:flex;align-items:center;gap:10px;font-size:20px;font-weight:800}
.logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--red),var(--slate));
  border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;
  box-shadow:0 0 20px rgba(220,38,38,.4);animation:glow 3s ease-in-out infinite}
.logo span{color:var(--text)}
.logo span b{color:var(--red)}
.nav{display:flex;align-items:center;gap:4px}
.nav a,.nav button.nav-link{padding:7px 14px;border-radius:8px;font-size:13px;font-weight:500;color:var(--muted2);
  background:none;border:none;transition:.2s;white-space:nowrap}
.nav a:hover,.nav button.nav-link:hover{color:var(--text);background:var(--surface2)}
.nav .btn-nav-cta{background:var(--red);color:#fff;padding:8px 18px;border-radius:9px;
  font-weight:600;font-size:13px;border:none;transition:.2s}
.nav .btn-nav-cta:hover{background:var(--red2);box-shadow:var(--glow-red)}
.hamburger{display:none;background:none;border:none;color:var(--text);font-size:22px;padding:4px}

/* ════════════════ HERO ════════════════ */
.hero{position:relative;padding:100px 24px 80px;text-align:center;overflow:hidden}
.hero-bg{position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(220,38,38,.05) 0%,transparent 70%),
  radial-gradient(ellipse 40% 40% at 80% 50%,rgba(107,114,128,.04) 0%,transparent 60%),
  radial-gradient(ellipse 40% 40% at 20% 60%,rgba(55,65,81,.03) 0%,transparent 60%);pointer-events:none}
.hero-grid{position:absolute;inset:0;background-image:linear-gradient(var(--border) 1px,transparent 1px),linear-gradient(90deg,var(--border) 1px,transparent 1px);
  background-size:60px 60px;opacity:.5;pointer-events:none}
.hero-badge{display:inline-flex;align-items:center;gap:6px;background:var(--red-soft);
  border:1px solid rgba(220,38,38,.25);color:var(--red2);padding:6px 16px;
  border-radius:40px;font-size:12px;font-weight:700;letter-spacing:.05em;
  margin-bottom:28px;animation:fadeUp .6s ease both}
.hero-badge svg{vertical-align:-3px;margin-right:2px}
.hero h1{font-size:clamp(2.2rem,5vw,3.8rem);font-weight:900;line-height:1.1;margin-bottom:20px;
  animation:fadeUp .7s .1s ease both}
.hero h1 .grad{color:var(--red)}
.hero p{font-size:1.1rem;color:var(--muted2);max-width:600px;margin:0 auto 36px;line-height:1.7;
  animation:fadeUp .7s .2s ease both}
.hero-cta{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;animation:fadeUp .7s .3s ease both}
.btn-hero{display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:12px;
  font-size:15px;font-weight:700;border:none;transition:.25s;position:relative;overflow:hidden}
.btn-hero-primary{background:linear-gradient(135deg,var(--red),var(--slate));color:#fff;
  box-shadow:0 8px 32px rgba(220,38,38,.35)}
.btn-hero-primary:hover{transform:translateY(-2px);box-shadow:0 12px 40px rgba(220,38,38,.5)}
.btn-hero-ghost{background:transparent;border:1px solid var(--border2);color:var(--text)}
.btn-hero-ghost:hover{background:var(--surface2);border-color:var(--red)}
.btn-hero::after{content:'';position:absolute;inset:0;background:linear-gradient(rgba(255,255,255,.1),transparent);
  opacity:0;transition:.2s}
.btn-hero:hover::after{opacity:1}

/* ════════════════ STATS BAR ════════════════ */
.stats-bar{background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border);
  padding:28px 24px}
.stats-inner{max-width:960px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr);gap:0}
.stat-item{text-align:center;padding:0 20px;position:relative}
.stat-item+.stat-item::before{content:'';position:absolute;left:0;top:10%;height:80%;width:1px;background:var(--border)}
.stat-num{font-size:2rem;font-weight:800;background:linear-gradient(135deg,var(--red),var(--slate));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;display:block}
.stat-lbl{font-size:12px;color:var(--muted);margin-top:4px}

/* ════════════════ LAYOUT ════════════════ */
.main-layout{max-width:1200px;margin:0 auto;padding:40px 24px;display:grid;grid-template-columns:1fr 320px;gap:32px;align-items:start}
.tool-area{display:flex;flex-direction:column;gap:24px}

/* ════════════════ CARD ════════════════ */
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;
  transition:.3s;animation:fadeUp .6s ease both}
.card:hover{border-color:var(--border2)}
.card-header{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
.card-header h2{font-size:15px;font-weight:700}
.step-num{width:28px;height:28px;background:linear-gradient(135deg,var(--red),var(--slate));
  border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0}
.card-body{padding:24px}

/* ════════════════ DROPZONE ════════════════ */
.dropzone{border:2px dashed var(--border2);border-radius:12px;padding:40px 24px;text-align:center;
  cursor:pointer;transition:.25s;background:var(--surface2);position:relative}
.dropzone:hover,.dropzone.drag-over{border-color:var(--red);background:rgba(220,38,38,.06);box-shadow:0 0 0 4px rgba(220,38,38,.1)}
.dropzone input{position:absolute;inset:0;opacity:0;cursor:pointer}
.dz-icon{display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;
  border-radius:50%;background:var(--red-soft);color:var(--red);margin:0 auto 14px;animation:float 3s ease-in-out infinite}
.dz-title{font-size:15px;font-weight:700;margin-bottom:6px}
.dz-sub{font-size:12px;color:var(--muted);margin-bottom:10px}
.dz-path{font-size:11px;color:var(--muted2);display:inline-flex;align-items:center;gap:5px;
  background:var(--surface3);padding:5px 12px;border-radius:20px}
.key-loaded{display:none;background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.25);
  border-radius:10px;padding:14px 18px;align-items:center;gap:12px}
.key-loaded.show{display:flex}
.key-dot{width:10px;height:10px;background:var(--green);border-radius:50%;flex-shrink:0;animation:pulse 2s infinite}

/* ════════════════ URL AREA ════════════════ */
.url-tabs{display:flex;gap:4px;background:var(--surface2);padding:4px;border-radius:10px;margin-bottom:16px}
.url-tab{flex:1;padding:8px;border:none;background:none;color:var(--muted);font-size:12px;font-weight:600;
  border-radius:7px;transition:.2s;font-family:'Inter',sans-serif;cursor:pointer}
.url-tab.active{background:var(--red);color:#fff}
.url-tab-panel{display:none}
.url-tab-panel.active{display:block}
.url-textarea{width:100%;background:var(--surface2);border:1px solid var(--border);color:var(--text);
  padding:14px;border-radius:10px;font-size:13px;font-family:monospace;resize:vertical;min-height:160px;
  outline:none;transition:.2s;line-height:1.6}
.url-textarea:focus{border-color:var(--red);box-shadow:0 0 0 3px rgba(220,38,38,.15)}
.sitemap-row{display:flex;gap:8px}
.input-field{width:100%;background:var(--surface2);border:1px solid var(--border);color:var(--text);
  padding:11px 14px;border-radius:10px;font-size:13px;font-family:'Inter',sans-serif;outline:none;transition:.2s}
.input-field:focus{border-color:var(--red);box-shadow:0 0 0 3px rgba(220,38,38,.15)}
.url-tools{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
.btn-tool{padding:6px 14px;border-radius:7px;font-size:12px;font-weight:600;border:1px solid var(--border);
  background:var(--surface2);color:var(--muted2);transition:.15s;font-family:'Inter',sans-serif;cursor:pointer}
.btn-tool:hover{border-color:var(--red);color:var(--red)}
.url-count{font-size:12px;color:var(--muted);margin-top:8px}

/* ════════════════ SUBMIT AREA ════════════════ */
.batch-info{background:rgba(220,38,38,.06);border:1px solid rgba(220,38,38,.2);
  border-radius:10px;padding:14px 18px;font-size:13px;color:var(--muted2);margin-bottom:16px}
.btn-submit{width:100%;padding:15px;background:linear-gradient(135deg,var(--red),var(--slate));
  border:none;color:#fff;font-size:15px;font-weight:700;border-radius:12px;
  transition:.25s;position:relative;overflow:hidden;font-family:'Inter',sans-serif;cursor:pointer}
.btn-submit:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 8px 32px rgba(220,38,38,.4)}
.btn-submit:disabled{opacity:.5;cursor:not-allowed}
.btn-submit .spinner{display:none;width:18px;height:18px;border:2px solid rgba(255,255,255,.3);
  border-top-color:#fff;border-radius:50%;animation:spin 1s linear infinite;margin-right:8px}
.btn-submit.loading .spinner{display:inline-block}
.btn-submit.loading .btn-text{display:none}

/* Progress */
.progress-wrap{display:none;margin-top:16px}
.progress-wrap.show{display:block}
.progress-bar-bg{background:var(--surface2);border-radius:99px;height:8px;overflow:hidden}
.progress-bar{height:100%;background:linear-gradient(90deg,var(--red),var(--slate));border-radius:99px;
  transition:width .3s;width:0}
.progress-txt{font-size:12px;color:var(--muted);margin-top:6px;text-align:center}

/* Results */
.results-wrap{display:none;margin-top:20px;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.results-wrap.show{display:block;animation:fadeUp .4s ease}
.results-header{padding:12px 16px;background:var(--surface2);display:flex;align-items:center;justify-content:space-between;font-size:13px}
.results-body{max-height:280px;overflow-y:auto}
.result-row{display:flex;align-items:center;gap:10px;padding:8px 16px;border-top:1px solid var(--border);font-size:12px}
.result-icon{width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;flex-shrink:0}
.result-icon.ok{background:rgba(16,185,129,.2);color:var(--green)}
.result-icon.fail{background:rgba(239,68,68,.2);color:var(--red)}
.result-url{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--muted2)}
.result-msg{font-size:11px;color:var(--muted);flex-shrink:0}
.fail-help{display:none;margin:0;padding:16px;background:var(--red-soft);border-top:1px solid rgba(220,38,38,.25)}
.fail-help.show{display:block;animation:fadeUp .3s ease}
.fail-help h4{font-size:13px;font-weight:700;color:var(--red3);display:flex;align-items:center;gap:6px;margin-bottom:10px}
.fail-help ul{list-style:none;display:flex;flex-direction:column;gap:8px}
.fail-help li{font-size:12px;color:var(--charcoal);line-height:1.6;display:flex;gap:8px}
.fail-help li b{color:var(--red3);flex-shrink:0}

/* ════════════════ SIDEBAR ════════════════ */
.sidebar{display:flex;flex-direction:column;gap:20px;position:sticky;top:80px}

/* Quota card */
.quota-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:24px;
  animation:fadeUp .6s .1s ease both}
.quota-ring-wrap{display:flex;flex-direction:column;align-items:center;margin-bottom:20px}
.quota-ring{transform:rotate(-90deg)}
.quota-ring-bg{fill:none;stroke:var(--surface3);stroke-width:8}
.quota-ring-fill{fill:none;stroke:url(#qgrad);stroke-width:8;stroke-linecap:round;
  stroke-dasharray:<?= $dashC ?>;stroke-dashoffset:<?= round($dashC*(1-$pct/100),1) ?>;transition:stroke-dashoffset 1s}
.quota-center{position:absolute;display:flex;flex-direction:column;align-items:center;justify-content:center}
.quota-pct{font-size:22px;font-weight:800;line-height:1}
.quota-sub{font-size:11px;color:var(--muted);margin-top:2px}
.quota-ring-container{position:relative;width:128px;height:128px;display:flex;align-items:center;justify-content:center}
.quota-details{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
.qd-item{background:var(--surface2);border-radius:10px;padding:12px;text-align:center}
.qd-val{font-size:18px;font-weight:800}
.qd-lbl{font-size:10px;color:var(--muted);margin-top:2px}
.plan-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;
  font-size:12px;font-weight:700;margin-bottom:12px}
.plan-free{background:rgba(100,116,139,.15);color:var(--muted2)}
.plan-starter{background:rgba(220,38,38,.15);color:#b91c1c}
.plan-pro{background:rgba(55,65,81,.15);color:#374151}
.plan-business{background:rgba(245,158,11,.15);color:#92400e}

/* User card */
.user-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;
  animation:fadeUp .6s .15s ease both}
.user-avatar{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--red),var(--charcoal));
  display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;flex-shrink:0}
.user-info{flex:1;overflow:hidden}
.user-name{font-size:14px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.user-email{font-size:11px;color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.btn-action{width:100%;padding:10px;border-radius:9px;font-size:13px;font-weight:600;border:none;
  transition:.2s;font-family:'Inter',sans-serif;cursor:pointer;margin-top:8px}
.btn-blue{background:var(--red);color:#fff}
.btn-blue:hover{background:var(--red2)}
.btn-outline{background:transparent;border:1px solid var(--border2);color:var(--muted2)}
.btn-outline:hover{border-color:var(--red);color:var(--red)}
.btn-green{background:var(--green);color:#fff}
.btn-green:hover{background:#059669}

/* ════════════════ TOOLS DRAWER ════════════════ */
.tools-drawer{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
  overflow:hidden;animation:fadeUp .6s .2s ease both}
.tools-drawer-header{padding:14px 18px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;
  user-select:none;transition:.15s}
.tools-drawer-header:hover{background:var(--surface2)}
.tools-drawer-header h3{font-size:13px;font-weight:700;display:flex;align-items:center;gap:6px}
#drawerArrow{display:inline-flex;transition:transform .2s;color:var(--muted)}
.tools-drawer-body{display:none;padding:12px}
.tools-drawer-body.open{display:block}
.tool-link{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;
  font-size:13px;color:var(--muted2);transition:.15s;cursor:pointer;border:none;background:none;
  width:100%;text-align:left;font-family:'Inter',sans-serif}
.tool-link:hover{background:var(--surface2);color:var(--text)}
.tool-link .tl-icon{font-size:16px;flex-shrink:0}
.tool-link .tl-badge{font-size:10px;background:rgba(245,158,11,.15);color:var(--yellow);
  padding:2px 7px;border-radius:10px;margin-left:auto;white-space:nowrap}

/* ════════════════ HISTORY TABLE ════════════════ */
.hist-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:12px}
th{padding:10px 14px;background:var(--surface2);color:var(--muted);font-weight:600;text-align:left;white-space:nowrap;border-bottom:1px solid var(--border)}
td{padding:9px 14px;border-top:1px solid var(--border);vertical-align:middle}
tr:hover td{background:rgba(255,255,255,.02)}
.chip{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600}
.chip-ok{background:rgba(16,185,129,.15);color:#166534}
.chip-fail{background:rgba(239,68,68,.15);color:#991b1b}

/* ════════════════ PRICING ════════════════ */
.pricing-section{padding:80px 24px;background:var(--surface)}
.section-title{text-align:center;margin-bottom:12px}
.section-title h2{font-size:clamp(1.6rem,3vw,2.4rem);font-weight:800}
.section-title p{color:var(--muted2);margin-top:8px}
.plans-grid{max-width:960px;margin:48px auto 0;display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.plans-grid-4{max-width:1180px;grid-template-columns:repeat(4,1fr)}
.plan-card{background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-lg);
  padding:28px;position:relative;transition:.3s}
.plan-card:hover{border-color:var(--red);transform:translateY(-4px);box-shadow:var(--shadow-lg)}
.plan-card.popular{border-color:var(--red);background:linear-gradient(to bottom,rgba(220,38,38,.08),var(--surface2))}
.popular-badge{position:absolute;top:-12px;left:50%;transform:translateX(-50%);
  background:linear-gradient(135deg,var(--red),var(--slate));color:#fff;
  padding:4px 16px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap}
.plan-name{font-size:14px;font-weight:700;color:var(--muted2);margin-bottom:8px;text-transform:uppercase;letter-spacing:.08em}
.plan-price{font-size:3rem;font-weight:900;line-height:1}
.plan-price span{font-size:16px;font-weight:600;color:var(--muted);vertical-align:top;margin-top:8px;display:inline-block}
.plan-period{font-size:13px;color:var(--muted);margin-top:4px;margin-bottom:20px}
.plan-features{list-style:none;margin-bottom:24px;display:flex;flex-direction:column;gap:10px}
.plan-features li{font-size:13px;color:var(--muted2);display:flex;align-items:center;gap:8px}
.plan-features li::before{content:'✓';color:var(--green);font-weight:700;font-size:14px;flex-shrink:0}
.btn-plan{width:100%;padding:13px;border-radius:10px;font-size:14px;font-weight:700;
  border:none;cursor:pointer;font-family:'Inter',sans-serif;transition:.2s}
.btn-plan-blue{background:var(--red);color:#fff}
.btn-plan-blue:hover{background:var(--red2);box-shadow:var(--glow-red)}
.btn-plan-outline{background:transparent;border:1px solid var(--border2);color:var(--text)}
.btn-plan-outline:hover{border-color:var(--red);color:var(--red)}

/* ════════════════ FEATURES COMPARE ════════════════ */
.features-section{padding:80px 24px;max-width:900px;margin:0 auto}
.compare-table{width:100%;border-collapse:collapse;font-size:13px;border:1px solid var(--border);border-radius:var(--radius)}
.compare-table th,.compare-table td{padding:14px 20px;border-bottom:1px solid var(--border)}
.compare-table th{background:var(--surface);font-weight:700;text-align:center}
.compare-table th:first-child{text-align:left}
.compare-table td:not(:first-child){text-align:center}
.compare-table tr:last-child td{border-bottom:none}
.compare-table tr:hover td{background:rgba(255,255,255,.02)}
.check-yes{color:var(--green);font-size:16px}
.check-no{color:var(--muted);font-size:16px}
.locked-feat{position:relative;cursor:default}
.locked-feat::after{content:'Premium';position:absolute;right:-10px;top:50%;transform:translateY(-50%);
  background:rgba(245,158,11,.15);color:var(--yellow);font-size:10px;padding:2px 8px;
  border-radius:10px;white-space:nowrap;font-weight:600}

/* ════════════════ HOW IT WORKS ════════════════ */
.how-section{padding:80px 24px;background:var(--surface)}
.steps-grid{max-width:900px;margin:48px auto 0;display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.step-box{background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);
  padding:28px;text-align:center;transition:.3s}
.step-box:hover{border-color:var(--red);transform:translateY(-4px)}
.step-icon{font-size:36px;margin-bottom:16px;display:block}
.step-box h3{font-size:15px;font-weight:700;margin-bottom:8px}
.step-box p{font-size:13px;color:var(--muted2);line-height:1.6}

/* ════════════════ FOOTER ════════════════ */
.footer{background:var(--surface);border-top:1px solid var(--border);padding:48px 24px 28px}
.footer-inner{max-width:1100px;margin:0 auto}
.footer-top{display:grid;grid-template-columns:1.5fr 1fr 1fr 1fr;gap:40px;margin-bottom:40px}
.footer-brand p{font-size:13px;color:var(--muted);margin-top:12px;line-height:1.7}
.footer-col h4{font-size:13px;font-weight:700;margin-bottom:16px;color:var(--text)}
.footer-col ul{list-style:none;display:flex;flex-direction:column;gap:8px}
.footer-col ul li a{font-size:13px;color:var(--muted);transition:.15s}
.footer-col ul li a:hover{color:var(--red)}
.footer-bottom{border-top:1px solid var(--border);padding-top:24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:gap}
.footer-bottom p{font-size:12px;color:var(--muted)}

/* ════════════════ MODALS ════════════════ */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:999;display:none;
  align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(4px)}
.modal-overlay.show{display:flex}
.modal{background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius-lg);
  width:100%;max-width:440px;max-height:90vh;overflow-y:auto;animation:fadeUp .3s ease}
.modal-header{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.modal-header h3{font-size:16px;font-weight:700}
.modal-close{background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer;padding:2px 6px;border-radius:6px;transition:.15s}
.modal-close:hover{color:var(--text);background:var(--surface2)}
.modal-body{padding:24px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:12px;font-weight:600;color:var(--muted2);margin-bottom:6px}
.form-group input,.form-group select{width:100%;background:var(--surface2);border:1px solid var(--border);
  color:var(--text);padding:11px 14px;border-radius:10px;font-size:13px;font-family:'Inter',sans-serif;outline:none;transition:.2s}
.form-group input:focus,.form-group select:focus{border-color:var(--red);box-shadow:0 0 0 3px rgba(220,38,38,.15)}
.or-divider{text-align:center;position:relative;margin:20px 0}
.or-divider::before{content:'';position:absolute;left:0;top:50%;width:100%;height:1px;background:var(--border)}
.or-divider span{background:var(--surface);padding:0 14px;font-size:12px;color:var(--muted);position:relative}
.btn-google{width:100%;padding:11px;border-radius:10px;border:1px solid var(--border2);background:var(--surface2);
  color:var(--text);font-size:14px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;
  display:flex;align-items:center;justify-content:center;gap:8px;transition:.2s}
.btn-google:hover{border-color:var(--red);background:var(--surface3)}
.tab-auth{display:flex;gap:4px;background:var(--surface2);padding:4px;border-radius:10px;margin-bottom:20px}
.tab-auth button{flex:1;padding:8px;border:none;background:none;color:var(--muted);font-size:13px;
  font-weight:600;border-radius:7px;cursor:pointer;transition:.2s;font-family:'Inter',sans-serif}
.tab-auth button.active{background:var(--red);color:#fff}
.auth-panel{display:none}
.auth-panel.active{display:block}
.auth-err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#991b1b;
  padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:12px;display:none}
.auth-err.show{display:block}
.btn-full{width:100%;padding:12px;border-radius:10px;font-size:14px;font-weight:700;border:none;
  cursor:pointer;font-family:'Inter',sans-serif;transition:.2s}
.btn-full-blue{background:var(--red);color:#fff}
.btn-full-blue:hover{background:var(--red2)}

/* Payment modal */
.payment-modal{max-width:480px}
.currency-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:20px}
.currency-btn{padding:10px;border:1px solid var(--border);border-radius:10px;background:var(--surface2);
  color:var(--muted2);font-size:12px;font-weight:600;cursor:pointer;transition:.2s;text-align:center;font-family:'Inter',sans-serif}
.currency-btn:hover,.currency-btn.selected{border-color:var(--red);color:var(--red);background:rgba(220,38,38,.08)}
.pay-addr-box{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:16px}
.pay-label{font-size:11px;color:var(--muted);margin-bottom:4px}
.pay-value{font-size:13px;font-family:monospace;word-break:break-all;color:var(--text)}
.btn-copy{padding:6px 14px;border:1px solid var(--border);background:var(--surface3);
  color:var(--muted2);border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;transition:.2s}
.btn-copy:hover{border-color:var(--red);color:var(--red)}
.pay-qr{text-align:center;margin-bottom:16px}
.pay-qr img{border-radius:10px;border:4px solid #fff;width:160px;height:160px}
.pay-timer{text-align:center;font-size:13px;color:var(--yellow);font-weight:600;margin-bottom:16px}
.pay-status-bar{padding:10px 14px;border-radius:8px;font-size:13px;text-align:center;font-weight:600}
.pay-pending{background:rgba(245,158,11,.1);color:var(--yellow);border:1px solid rgba(245,158,11,.3)}
.pay-success{background:rgba(16,185,129,.1);color:var(--green);border:1px solid rgba(16,185,129,.3)}

/* ════════════════ TOAST ════════════════ */
.toast-wrap{position:fixed;bottom:24px;left:24px;z-index:9999;display:flex;flex-direction:column;gap:8px}
.toast{background:var(--surface);border:1px solid var(--border2);border-radius:10px;padding:12px 18px;
  font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;
  box-shadow:var(--shadow-md);animation:fadeUp .3s ease;max-width:340px}
.toast.ok{border-left:3px solid var(--green)}
.toast.err{border-left:3px solid var(--red)}
.toast.info{border-left:3px solid var(--red)}

/* ════════════════ SUPPORT CHAT (messenger-style) ════════════════ */
.chat-launcher{position:fixed;bottom:24px;right:24px;z-index:600}
.support-bubble{position:relative;width:56px;height:56px;background:linear-gradient(135deg,var(--red),var(--red2));
  color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;
  box-shadow:0 4px 24px rgba(220,38,38,.4);cursor:pointer;transition:.25s;border:none}
.support-bubble:hover{transform:scale(1.08);box-shadow:0 8px 32px rgba(220,38,38,.5)}
.support-bubble-badge{position:absolute;top:-3px;right:-3px;background:var(--charcoal);color:#fff;
  width:19px;height:19px;border-radius:50%;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;
  border:2px solid var(--bg)}
.chat-panel{position:fixed;bottom:24px;right:24px;z-index:601;width:360px;max-width:calc(100vw - 32px);
  height:520px;max-height:calc(100vh - 48px);background:var(--surface);border:1px solid var(--border2);
  border-radius:18px;box-shadow:var(--shadow-lg);display:none;flex-direction:column;overflow:hidden}
.chat-panel.show{display:flex;animation:fadeUp .25s ease}
.chat-header{display:flex;align-items:center;gap:10px;padding:14px 16px;background:linear-gradient(135deg,var(--red),var(--red2));color:#fff}
.chat-header-avatar{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.2);
  display:flex;align-items:center;justify-content:center;flex-shrink:0}
.chat-header-name{font-size:14px;font-weight:700}
.chat-header-status{font-size:11px;opacity:.9;display:flex;align-items:center;gap:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.chat-dot{width:7px;height:7px;background:#4ade80;border-radius:50%;flex-shrink:0;animation:pulse 2s infinite}
.chat-close{background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:50%;width:28px;height:28px;
  display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0}
.chat-body{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px;background:var(--bg)}
.chat-msg{display:flex;flex-direction:column;max-width:82%}
.chat-msg.bot{align-self:flex-start}
.chat-msg.user{align-self:flex-end;align-items:flex-end}
.chat-bubble{padding:10px 14px;border-radius:16px;font-size:13px;line-height:1.5}
.chat-msg.bot .chat-bubble{background:var(--surface2);color:var(--text);border-bottom-left-radius:4px}
.chat-msg.user .chat-bubble{background:var(--red);color:#fff;border-bottom-right-radius:4px}
.chat-time{font-size:10px;color:var(--muted);margin-top:3px;padding:0 4px}
.chat-quick{display:flex;flex-direction:column;gap:6px;align-self:flex-start;max-width:88%}
.chat-quick button{text-align:left;background:var(--surface);border:1px solid var(--border2);color:var(--red2);
  padding:8px 12px;border-radius:12px;font-size:12px;font-weight:600;cursor:pointer;transition:.15s;font-family:'Inter',sans-serif}
.chat-quick button:hover{background:var(--red-soft);border-color:var(--red)}
.chat-input-row{display:flex;gap:8px;padding:12px;border-top:1px solid var(--border);background:var(--surface)}
.chat-input-row input{flex:1;border:1px solid var(--border);background:var(--surface2);border-radius:20px;
  padding:10px 16px;font-size:13px;outline:none;font-family:'Inter',sans-serif;color:var(--text)}
.chat-input-row input:focus{border-color:var(--red)}
.chat-send{width:38px;height:38px;border-radius:50%;background:var(--red);color:#fff;border:none;
  display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;transition:.15s}
.chat-send:hover{background:var(--red2)}

/* ════════════════ ALERT ════════════════ */
.alert{padding:12px 16px;border-radius:10px;font-size:13px;display:flex;align-items:center;gap:10px}
.alert-info{background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);color:#b91c1c}
.alert-warn{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);color:#92400e}
.alert-success{background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.2);color:#166534}

/* ════════════════ PLAN CHOICE MODAL ════════════════ */
.plan-choice-modal{max-width:900px;width:95%}
.plan-choice-modal .modal-header{background:linear-gradient(135deg,rgba(220,38,38,.15),rgba(55,65,81,.1));border-radius:var(--radius-lg) var(--radius-lg) 0 0;padding:28px 32px}
.plan-choice-modal .modal-header h3{font-size:24px;font-weight:900;color:var(--text)}
.plan-choice-modal .modal-header p{color:var(--muted2);font-size:14px;margin-top:6px}
.plan-choice-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;padding:24px 32px}
.pcc{background:var(--surface2);border:2px solid var(--border);border-radius:14px;padding:20px;text-align:center;transition:.2s;cursor:pointer}
.pcc:hover{border-color:var(--red);transform:translateY(-3px);box-shadow:0 10px 40px rgba(220,38,38,.2)}
.pcc.popular{border-color:var(--red);background:linear-gradient(to bottom,rgba(220,38,38,.1),var(--surface2))}
.pcc .badge{display:inline-block;font-size:10px;font-weight:700;background:var(--red);color:#fff;padding:2px 8px;border-radius:20px;margin-bottom:10px;letter-spacing:.05em}
.pcc .pcc-icon{font-size:32px;margin-bottom:10px}
.pcc .pcc-name{font-size:13px;font-weight:700;color:var(--muted2);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px}
.pcc .pcc-price{font-size:28px;font-weight:900;line-height:1.1}
.pcc .pcc-price small{font-size:12px;font-weight:500;color:var(--muted)}
.pcc .pcc-limit{font-size:12px;color:var(--muted2);margin:8px 0 14px}
.pcc .pcc-btn{width:100%;padding:10px;border-radius:8px;font-size:13px;font-weight:700;border:none;cursor:pointer;transition:.15s}
.pcc .pcc-btn-free{background:var(--surface3);color:var(--muted2);border:1px solid var(--border2)}
.pcc .pcc-btn-free:hover{background:var(--surface2);color:var(--text)}
.pcc .pcc-btn-paid{background:var(--red);color:#fff}
.pcc .pcc-btn-paid:hover{background:var(--red2);box-shadow:var(--glow-red)}
.pcc .pcc-btn-pop{background:linear-gradient(135deg,var(--red),var(--charcoal));color:#fff}
.pcc .pcc-btn-pop:hover{opacity:.9;box-shadow:0 0 30px rgba(55,65,81,.4)}
.pcc .pcc-feats{list-style:none;font-size:11px;color:var(--muted2);margin:0 0 14px;text-align:left}
.pcc .pcc-feats li{padding:3px 0;display:flex;align-items:center;gap:6px}
.pcc .pcc-feats li::before{content:'✓';color:var(--green);font-weight:700;font-size:12px}
.plan-choice-note{padding:0 32px 24px;text-align:center;font-size:12px;color:var(--muted)}
@media(max-width:768px){.plan-choice-grid{grid-template-columns:1fr 1fr;gap:12px;padding:16px}.plan-choice-modal .modal-header{padding:20px}}
@media(max-width:480px){.plan-choice-grid{grid-template-columns:1fr}}

/* ════════════════ RESPONSIVE ════════════════ */
@media(max-width:1024px){
  .main-layout{grid-template-columns:1fr;max-width:720px}
  .sidebar{position:static}
  .plans-grid{grid-template-columns:repeat(2,1fr)}
  .steps-grid{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:768px){
  .nav{display:none;position:fixed;top:64px;left:0;right:0;z-index:199;background:var(--surface);
    border-bottom:1px solid var(--border);flex-direction:column;align-items:stretch;gap:0;
    padding:8px 16px 16px;box-shadow:var(--shadow-md)}
  .nav.mobile-open{display:flex;animation:fadeUp .25s ease both}
  .nav a,.nav button.nav-link{padding:12px 10px;width:100%;text-align:left;border-radius:8px}
  .nav .btn-nav-cta{margin-top:8px;text-align:center}
  .hamburger{display:flex;align-items:center;justify-content:center}
  .stats-inner{grid-template-columns:repeat(2,1fr)}
  .stat-item+.stat-item::before{display:none}
  .footer-top{grid-template-columns:1fr 1fr}
  .plans-grid{grid-template-columns:1fr}
  .steps-grid{grid-template-columns:1fr}
  .hero{padding:60px 24px 48px}
  .footer-top{grid-template-columns:1fr}
  .plan-choice-grid{grid-template-columns:1fr 1fr;gap:12px;padding:16px}
  body{padding-bottom:88px}
  .support-bubble{width:46px;height:46px;bottom:16px;right:16px}
  .chat-panel{right:16px;left:16px;width:auto;bottom:16px;height:calc(100vh - 100px)}
  .chat-launcher{bottom:16px;right:16px}
  .card-body{padding:18px}
  .card-header{padding:16px 18px}
  .result-msg{display:none}
  .quota-ring-container{width:112px;height:112px}
}
@media(max-width:480px){
  .hero{padding:36px 16px 32px}
  .hero h1{font-size:1.75rem;line-height:1.15;margin-bottom:14px}
  .hero p{font-size:.93rem;margin-bottom:20px}
  .hero-cta{flex-direction:column;align-items:center;gap:10px}
  .btn-hero{width:100%;max-width:300px;justify-content:center;padding:13px 20px;font-size:14px}
  .hero-badge{font-size:11px;padding:5px 12px;margin-bottom:20px}
  .stats-inner{grid-template-columns:repeat(2,1fr)}
  .stat-num{font-size:1.5rem}
  .main-layout{padding:24px 16px}
}
@media(max-width:420px){
  .plan-choice-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- ════════════════ HEADER ════════════════ -->
<header class="header">
  <a href="index.php" class="logo">
    <div class="logo-icon"><?= icon('bolt',20) ?></div>
    <span>Index<b>Pro</b></span>
  </a>
  <nav class="nav" id="mainNav">
    <a href="#pricing">Pricing</a>
    <a href="#how-it-works">How It Works</a>
    <a href="about.php">About</a>
    <a href="privacy.php">Privacy</a>
    <a href="terms.php">Terms</a>
    <a href="report.php">Report a Problem</a>
    <?php if (!empty($_SESSION['uid'])): ?>
    <button class="nav-link" onclick="doLogout()">Logout</button>
    <?php else: ?>
    <button class="btn-nav-cta" onclick="openAuth()">Sign In</button>
    <?php endif ?>
  </nav>
  <button class="hamburger" id="hamburgerBtn" onclick="toggleMobileNav()" aria-label="Menu"><?= icon('menu',22) ?></button>
</header>

<!-- ════════════════ HERO ════════════════ -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-grid"></div>
  <div class="hero-badge"><?= icon('shield',14) ?> Official Google Indexing API Integration</div>
  <h1><span class="grad">Instant Google Indexing</span><br>For Any Website</h1>
  <p>Submit URLs directly to Google Search Console using the official Indexing API. Stop waiting weeks for crawlers — request indexing in minutes with your own Service Account credentials.</p>
  <div class="hero-cta">
    <button class="btn-hero btn-hero-primary" onclick="document.getElementById('tool').scrollIntoView({behavior:'smooth'})">
      <?= icon('zap',16) ?> Start Indexing Free
    </button>
    <a href="#how-it-works" class="btn-hero btn-hero-ghost"><?= icon('clipboard',16) ?> How It Works</a>
  </div>
</section>

<!-- ════════════════ STATS ════════════════ -->
<div class="stats-bar">
  <div class="stats-inner">
    <div class="stat-item"><span class="stat-num" id="sCnt">100</span><div class="stat-lbl">Free daily submissions</div></div>
    <div class="stat-item"><span class="stat-num">1</span><div class="stat-lbl">Official Google Indexing API</div></div>
    <div class="stat-item"><span class="stat-num">24/7</span><div class="stat-lbl">Support available</div></div>
    <div class="stat-item"><span class="stat-num">0</span><div class="stat-lbl">Database required</div></div>
  </div>
</div>

<!-- ════════════════ TOOL ════════════════ -->
<section id="tool">
<div class="main-layout">

<!-- LEFT: Tool steps -->
<div class="tool-area">

  <!-- STEP 1: Service Account -->
  <div class="card" style="animation-delay:.05s">
    <div class="card-header">
      <div class="step-num">1</div>
      <h2>Connect Service Account JSON Key</h2>
    </div>
    <div class="card-body">
      <div class="dropzone" id="dropzone" onclick="document.getElementById('keyFile').click()">
        <input type="file" id="keyFile" accept=".json" style="display:none" onchange="handleKeyUpload(this)">
        <div class="dz-icon"><?= icon('upload',28) ?></div>
        <div class="dz-title">Drag &amp; drop your Service Account JSON key here</div>
        <div class="dz-sub">or click to browse — .json files only, up to 10MB</div>
        <div class="dz-path"><?= icon('key',13) ?> Google Cloud Console → IAM &amp; Admin → Service Accounts → Keys</div>
      </div>
      <div class="key-loaded <?= $k ? 'show' : '' ?>" id="keyLoaded">
        <div class="key-dot"></div>
        <div style="flex:1">
          <div style="font-size:13px;font-weight:600">Key Connected</div>
          <div style="font-size:11px;color:var(--muted)" id="keyEmail"><?= h($k['email']??'') ?></div>
        </div>
        <button class="btn-tool" onclick="deleteKey()"><?= icon('trash',13) ?> Remove</button>
        <button class="btn-tool" onclick="testKey()" style="border-color:var(--red);color:var(--red)"><?= icon('check',13) ?> Test</button>
      </div>
      <div style="margin-top:16px">
        <div class="alert alert-info">
          <span><?= icon('info',18) ?></span>
          <div>
            <strong>How to get your key:</strong> Go to <a href="https://console.cloud.google.com" target="_blank" style="color:var(--red)">console.cloud.google.com</a> → APIs → Enable "Indexing API" → IAM → Service Accounts → Create Key → JSON. Then add the service account email as an <strong>Owner</strong> in Search Console (Settings → Users and permissions).
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 2: URLs -->
  <div class="card" style="animation-delay:.1s">
    <div class="card-header">
      <div class="step-num">2</div>
      <h2>Add URLs to Submit</h2>
    </div>
    <div class="card-body">
      <div class="url-tabs">
        <button class="url-tab active" data-tab="manual" onclick="switchTab('manual')">Manual</button>
        <button class="url-tab" data-tab="sitemap" onclick="switchTab('sitemap')">Sitemap Import</button>
        <button class="url-tab" data-tab="csv" onclick="switchTab('csv')">CSV Extract</button>
      </div>
      <!-- Manual -->
      <div class="url-tab-panel active" id="tab-manual">
        <textarea class="url-textarea" id="urlArea" placeholder="https://yoursite.com/page-1&#10;https://yoursite.com/page-2&#10;https://yoursite.com/page-3&#10;&#10;One URL per line" oninput="countUrls()"></textarea>
      </div>
      <!-- Sitemap -->
      <div class="url-tab-panel" id="tab-sitemap">
        <div class="sitemap-row">
          <input type="url" id="sitemapUrl" class="input-field" placeholder="https://yoursite.com/sitemap.xml">
          <button class="btn-tool" onclick="fetchSitemap()" style="white-space:nowrap;padding:0 20px">Fetch URLs</button>
        </div>
        <div id="sitemapStatus" style="margin-top:8px;font-size:12px;color:var(--muted)"></div>
      </div>
      <!-- CSV -->
      <div class="url-tab-panel" id="tab-csv">
        <textarea class="url-textarea" id="csvArea" placeholder="Paste CSV content here — URLs will be extracted automatically" style="min-height:100px" oninput="extractCSV()"></textarea>
      </div>
      <!-- URL tools -->
      <div class="url-tools">
        <button class="btn-tool" onclick="sortUrls()">Sort A-Z</button>
        <button class="btn-tool" onclick="dedupeUrls()">Remove Duplicates</button>
        <button class="btn-tool" onclick="copyUrls()">Copy All</button>
        <button class="btn-tool" onclick="clearUrls()" style="color:var(--red)">Clear</button>
      </div>
      <div class="url-count" id="urlCount">0 URLs ready</div>
    </div>
  </div>

  <!-- STEP 3: Submit -->
  <div class="card" style="animation-delay:.15s">
    <div class="card-header">
      <div class="step-num">3</div>
      <h2>Submit for Indexing</h2>
    </div>
    <div class="card-body">
      <?php $isLoggedIn = !empty($_SESSION['uid']); ?>
      <?php if (!$isLoggedIn): ?>
      <div class="alert alert-warn" style="margin-bottom:16px">
        <span><?= icon('lock',18) ?></span>
        <div><strong>Sign in required.</strong> You must sign in with Google or an email/password account before you can submit URLs for indexing. This keeps quota and your Service Account key tied to your account.</div>
      </div>
      <?php endif ?>
      <div class="batch-info" id="batchInfo">
        <?= icon('chart',15) ?> Daily quota: <strong id="qUsed"><?= $q['used'] ?></strong> / <strong id="qLimit"><?= $q['limit'] ?></strong> used &nbsp;|&nbsp;
        Remaining: <strong id="qLeft" style="color:var(--green)"><?= $q['remaining'] ?></strong>
        <?php if ($q['plan'] === 'free'): ?>&nbsp;|&nbsp;<a href="#pricing" style="color:var(--red)">Upgrade for more →</a><?php endif ?>
      </div>
      <button class="btn-submit" id="submitBtn" onclick="<?= $isLoggedIn ? 'submitUrls()' : 'openAuth()' ?>">
        <span class="spinner"></span>
        <span class="btn-text"><?= icon('zap',16) ?> <?= $isLoggedIn ? 'Submit for Indexing' : 'Sign In to Submit' ?></span>
      </button>
      <div class="progress-wrap" id="progressWrap">
        <div class="progress-bar-bg"><div class="progress-bar" id="progressBar"></div></div>
        <div class="progress-txt" id="progressTxt">Submitting…</div>
      </div>
      <div class="results-wrap" id="resultsWrap">
        <div class="results-header">
          <span>Results <span id="resultSummary"></span></span>
          <div style="display:flex;gap:6px">
            <button class="btn-tool" onclick="exportCSV()" style="font-size:11px;padding:4px 10px"><?= icon('download',12) ?> CSV</button>
            <button class="btn-tool" onclick="exportSitemap()" style="font-size:11px;padding:4px 10px"><?= icon('map',12) ?> Sitemap</button>
            <button class="btn-tool" onclick="exportRSS()" style="font-size:11px;padding:4px 10px"><?= icon('layers',12) ?> RSS</button>
          </div>
        </div>
        <div class="results-body" id="resultsBody"></div>
        <div class="fail-help" id="failHelp"></div>
      </div>
    </div>
  </div>

  <!-- History -->
  <div class="card" style="animation-delay:.2s">
    <div class="card-header">
      <h2><?= icon('clipboard',17) ?> Submission History</h2>
      <button class="btn-tool" onclick="loadHistory()" style="margin-left:auto"><?= icon('refresh',13) ?> Refresh</button>
    </div>
    <div class="card-body" style="padding:0">
      <div class="hist-wrap">
        <table id="histTable">
          <thead><tr><th>URL</th><th>Result</th><th>Time</th></tr></thead>
          <tbody id="histBody"><tr><td colspan="3" style="text-align:center;color:var(--muted);padding:24px">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- RIGHT: Sidebar -->
<div class="sidebar">

  <!-- Quota ring -->
  <div class="quota-card">
    <div class="quota-ring-wrap">
      <div class="quota-ring-container">
        <svg class="quota-ring" width="128" height="128" viewBox="0 0 128 128">
          <defs>
            <linearGradient id="qgrad" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#dc2626"/>
              <stop offset="100%" stop-color="#6b7280"/>
            </linearGradient>
          </defs>
          <circle class="quota-ring-bg" cx="64" cy="64" r="<?= $dashR ?>"/>
          <circle class="quota-ring-fill" id="quotaRing" cx="64" cy="64" r="<?= $dashR ?>"/>
        </svg>
        <div class="quota-center" style="position:absolute">
          <div class="quota-pct" id="quotaPct"><?= $pct ?>%</div>
          <div class="quota-sub">used</div>
        </div>
      </div>
    </div>
    <div style="text-align:center;margin-bottom:16px">
      <span class="plan-badge plan-<?= h($q['plan']) ?>">
        <?= $q['plan'] === 'free' ? icon('user',13).' Free Plan' : icon('star',13).' '.h($planName) ?>
      </span>
      <?php if ($q['plan_expires']): ?><div style="font-size:11px;color:var(--muted)">Expires <?= h(substr($q['plan_expires'],0,10)) ?></div><?php endif ?>
    </div>
    <div class="quota-details">
      <div class="qd-item"><div class="qd-val" id="qdUsed"><?= $q['used'] ?></div><div class="qd-lbl">Used Today</div></div>
      <div class="qd-item"><div class="qd-val" id="qdLeft" style="color:var(--slate)"><?= $q['remaining'] ?></div><div class="qd-lbl">Remaining</div></div>
    </div>
    <button class="btn-action btn-blue" onclick="document.getElementById('pricing').scrollIntoView({behavior:'smooth'})">
      <?= icon('rocket',15) ?> Upgrade Plan
    </button>
  </div>

  <!-- User card -->
  <div class="user-card">
    <?php if (!empty($_SESSION['uid']) && !empty($u['email'])): ?>
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
      <?php if (!empty($u['picture'])): ?>
      <img src="<?= h($u['picture']) ?>" style="width:48px;height:48px;border-radius:50%">
      <?php else: ?>
      <div class="user-avatar"><?= strtoupper(substr($u['name']??$u['email']??'?',0,1)) ?></div>
      <?php endif ?>
      <div class="user-info">
        <div class="user-name"><?= h($u['name']??'User') ?></div>
        <div class="user-email"><?= h($u['email']??'') ?></div>
      </div>
    </div>
    <button class="btn-action btn-outline" onclick="doLogout()">Sign Out</button>
    <?php else: ?>
    <div style="text-align:center;padding:8px 0">
      <div style="color:var(--red);margin-bottom:8px;display:flex;justify-content:center"><?= icon('user',26) ?></div>
      <div style="font-size:14px;font-weight:700;margin-bottom:4px">Sign in to save your progress</div>
      <div style="font-size:12px;color:var(--muted);margin-bottom:16px">Your quota resets daily. Sign in to keep your key saved.</div>
      <button class="btn-action btn-blue" onclick="openAuth()">Sign In / Register</button>
      <?php if ($oauthUrl): ?>
      <a href="<?= h($oauthUrl) ?>"><button class="btn-action btn-google" style="margin-top:8px"><img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTgiIGhlaWdodD0iMTgiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGcgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj48cGF0aCBkPSJNMTcuNiA5LjJsLS4xLTEuOEg5djMuNGg0LjhDMTMuNiAxMiAxMyAxMyAxMiAxMy42djIuMmgzYTguOCA4LjggMCAwIDAgMi42LTYuNnoiIGZpbGw9IiM0Mjg1RjQiIGZpbGwtcnVsZT0ibm9uemVybyIvPjxwYXRoIGQ9Ik05IDE4YzIuNCAwIDQuNS0uOCA2LTIuMmwtMy0yLjJhNS40IDUuNCAwIDAgMS04LTIuOUgxVjEzYTkgOSAwIDAgMCA4IDV6IiBmaWxsPSIjMzRBODUzIiBmaWxsLXJ1bGU9Im5vbnplcm8iLz48cGF0aCBkPSJNNCAxMC43YTUuNCA1LjQgMCAwIDEgMC0zLjRWNUgxYTkgOSAwIDAgMCAwIDhsMy0yLjN6IiBmaWxsPSIjRkJCQzA1IiBmaWxsLXJ1bGU9Im5vbnplcm8iLz48cGF0aCBkPSJNOSAzLjZjMS4zIDAgMi41LjQgMy40IDEuM0wxNSAyLjNBOSA5IDAgMCAwIDEgNWwzIDIuNGE1LjQgNS40IDAgMCAxIDUtMy44eiIgZmlsbD0iI0VBNDMzNSIgZmlsbC1ydWxlPSJub256ZXJvIi8+PC9nPjwvc3ZnPg==" style="width:18px;height:18px" alt="G"> Sign in with Google</button></a>
      <?php endif ?>
    </div>
    <?php endif ?>
  </div>

  <!-- Tools drawer -->
  <div class="tools-drawer">
    <div class="tools-drawer-header" onclick="toggleDrawer()">
      <h3><?= icon('settings',15) ?> More Tools</h3>
      <span id="drawerArrow"><?= icon('chevronDown',14) ?></span>
    </div>
    <div class="tools-drawer-body" id="drawerBody">
      <button class="tool-link" onclick="exportSitemap()"><span class="tl-icon"><?= icon('map',16) ?></span>Sitemap.xml Generator<span class="tl-badge" style="background:rgba(22,163,74,.12);color:var(--green)">Live</span></button>
      <button class="tool-link" onclick="exportRSS()"><span class="tl-icon"><?= icon('layers',16) ?></span>RSS Feed Generator<span class="tl-badge" style="background:rgba(22,163,74,.12);color:var(--green)">Live</span></button>
      <button class="tool-link" onclick="openMetaTool()"><span class="tl-icon"><?= icon('flag',16) ?></span>Meta Tag Generator<span class="tl-badge" style="background:rgba(22,163,74,.12);color:var(--green)">Live</span></button>
      <button class="tool-link" onclick="openRobotsTool()"><span class="tl-icon"><?= icon('shield',16) ?></span>Robots.txt Generator<span class="tl-badge" style="background:rgba(22,163,74,.12);color:var(--green)">Live</span></button>
      <div class="tool-link" style="cursor:default"><span class="tl-icon"><?= icon('search',16) ?></span>Keyword Rank Tracker<span class="tl-badge">Soon</span></div>
      <div class="tool-link" style="cursor:default"><span class="tl-icon"><?= icon('external',16) ?></span>Backlink Checker<span class="tl-badge">Soon</span></div>
      <div class="tool-link" style="cursor:default"><span class="tl-icon"><?= icon('target',16) ?></span>SEO Audit Tool<span class="tl-badge">Soon</span></div>
      <div class="tool-link" style="cursor:default"><span class="tl-icon"><?= icon('zap',16) ?></span>Page Speed Analyzer<span class="tl-badge">Soon</span></div>
    </div>
  </div>

</div><!-- /.sidebar -->
</div><!-- /.main-layout -->
</section>

<!-- ════════════════ HOW IT WORKS ════════════════ -->
<section class="how-section" id="how-it-works">
  <div class="section-title">
    <h2>How It Works</h2>
    <p>Three simple steps to get your pages indexed by Google instantly</p>
  </div>
  <div class="steps-grid" style="max-width:1100px;margin:48px auto 0">
    <div class="step-box"><span class="step-icon"><?= icon('key',30) ?></span><h3>1. Connect Your Key</h3><p>Upload your Google Service Account JSON key from Google Cloud Console. Enable the Indexing API and add the account as Search Console owner.</p></div>
    <div class="step-box"><span class="step-icon"><?= icon('flag',30) ?></span><h3>2. Add Your URLs</h3><p>Paste URLs manually, import from your sitemap.xml automatically, or extract from a CSV file. Up to 200 URLs per batch.</p></div>
    <div class="step-box"><span class="step-icon"><?= icon('zap',30) ?></span><h3>3. Submit &amp; Index</h3><p>Click submit and watch your URLs get sent to Google's Indexing API in real-time. Results appear within minutes in Search Console.</p></div>
    <div class="step-box"><span class="step-icon"><?= icon('chart',30) ?></span><h3>Track Progress</h3><p>View submission history, success/failure rates, and daily quota usage. Export results to CSV, sitemap.xml, or RSS.</p></div>
    <div class="step-box"><span class="step-icon"><?= icon('shield',30) ?></span><h3>Secure &amp; Private</h3><p>Your JSON key is stored in a directory blocked from direct web access. No third-party servers process your credentials. Auto-deleted after 30 days.</p></div>
    <div class="step-box"><span class="step-icon"><?= icon('rocket',30) ?></span><h3>Scale Up</h3><p>Free plan gives 100 submissions/day. Upgrade to Starter, Pro, or Business for up to 10,000 daily submissions.</p></div>
  </div>
</section>

<!-- ════════════════ FEATURES COMPARE ════════════════ -->
<section class="features-section">
  <div class="section-title">
    <h2>Feature Comparison</h2>
    <p>See what's included in each plan</p>
  </div>
  <div style="overflow-x:auto">
  <table class="compare-table" style="margin-top:32px">
    <thead>
      <tr>
        <th style="width:40%">Feature</th>
        <th>Free</th><th>Starter $7</th><th>Pro $10</th><th>Business $25</th>
      </tr>
    </thead>
    <tbody>
      <tr><td>Daily URL submissions</td><td>100</td><td>500</td><td>2,000</td><td>10,000</td></tr>
      <tr><td>Sitemap auto-import</td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td></tr>
      <tr><td>Service Account key storage (30 days)</td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td></tr>
      <tr><td>Batch submission (200 URLs)</td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td></tr>
      <tr><td>Export results CSV</td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td></tr>
      <tr><td>Submission history (30 days)</td><td><span class="check-no">—</span></td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td></tr>
      <tr><td>Priority processing queue</td><td><span class="check-no">—</span></td><td><span class="check-no">—</span></td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td></tr>
      <tr><td>Advanced error diagnostics</td><td><span class="check-no">—</span></td><td><span class="check-no">—</span></td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td></tr>
      <tr><td>Nested sitemap index support</td><td><span class="check-no">—</span></td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td><td><span class="check-yes">✓</span></td></tr>
      <tr><td>Direct technical support</td><td><span class="check-no">—</span></td><td><span class="check-no">—</span></td><td><span class="check-no">—</span></td><td><span class="check-yes">✓</span></td></tr>
    </tbody>
  </table>
  </div>
</section>

<!-- ════════════════ PRICING ════════════════ -->
<section class="pricing-section" id="pricing">
  <div class="section-title">
    <h2>Simple, Transparent Pricing</h2>
    <p>Every plan submits directly to Google's official Indexing API. Pay with crypto via NOWPayments — subscription activates automatically.</p>
  </div>
  <div class="plans-grid plans-grid-4">
    <div class="plan-card">
      <div class="plan-name">Free</div>
      <div class="plan-price">$<span style="font-size:3rem;font-weight:900">0</span></div>
      <div class="plan-period">forever</div>
      <ul class="plan-features">
        <li>100 URLs per day</li>
        <li>Manual + sitemap + CSV input</li>
        <li>Google Indexing API submission</li>
        <li>Sitemap.xml &amp; RSS export tools</li>
        <li>Key stored 30 days</li>
      </ul>
      <button class="btn-plan btn-plan-outline" onclick="openAuth()">Start Free</button>
    </div>
    <div class="plan-card">
      <div class="plan-name">Starter</div>
      <div class="plan-price">$<span style="font-size:3rem;font-weight:900">7</span></div>
      <div class="plan-period">per month</div>
      <ul class="plan-features">
        <li>500 URLs per day</li>
        <li>Everything in Free</li>
        <li>Submission history retained</li>
        <li>CSV export</li>
        <li>Email support</li>
      </ul>
      <button class="btn-plan btn-plan-outline" onclick="openPayment('starter')">Get Starter</button>
    </div>
    <div class="plan-card popular">
      <div class="popular-badge"><?= icon('star',12) ?> Most Popular</div>
      <div class="plan-name">Pro</div>
      <div class="plan-price">$<span style="font-size:3rem;font-weight:900">10</span></div>
      <div class="plan-period">per month</div>
      <ul class="plan-features">
        <li>2,000 URLs per day</li>
        <li>Priority processing queue</li>
        <li>Advanced error diagnostics</li>
        <li>Nested sitemap index support</li>
        <li>Priority email support</li>
      </ul>
      <button class="btn-plan btn-plan-blue" onclick="openPayment('pro')">Get Pro</button>
    </div>
    <div class="plan-card">
      <div class="plan-name">Business</div>
      <div class="plan-price">$<span style="font-size:3rem;font-weight:900">25</span></div>
      <div class="plan-period">per month</div>
      <ul class="plan-features">
        <li>10,000 URLs per day</li>
        <li>All Pro features</li>
        <li>Direct technical support</li>
        <li>Multiple Service Account keys</li>
        <li>Priority queue placement</li>
      </ul>
      <button class="btn-plan btn-plan-outline" onclick="openPayment('business')">Get Business</button>
    </div>
  </div>
  <div class="alert alert-info" style="max-width:640px;margin:32px auto 0">
    <span><?= icon('info',18) ?></span>
    <div><strong>What "indexing" actually does:</strong> every plan submits your URLs to Google's official Indexing API. This asks Google to crawl your page sooner — it does not guarantee ranking position, and no service can guarantee where a page ranks in search results. IndexNow support (Bing, Yandex, Seznam.cz, Naver) is on our roadmap.</div>
  </div>
  <p style="text-align:center;margin-top:20px;font-size:12px;color:var(--muted)">
    <?= icon('creditCard',13) ?> Payment via USDT, ETH, BTC, and 150+ other cryptocurrencies through NOWPayments.
    Subscription activates automatically after blockchain confirmation.
    <a href="refund.php" style="color:var(--red)">Refund Policy</a>
  </p>
</section>

<!-- ════════════════ FOOTER ════════════════ -->
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-top">
      <div class="footer-brand">
        <div class="logo"><div class="logo-icon"><?= icon('bolt',18) ?></div><span>Index<b>Pro</b></span></div>
        <p><?= h(SITE_TAGLINE) ?>. Submit URLs to Google for faster indexing via the official API.</p>
      </div>
      <div class="footer-col"><h4>Product</h4><ul><li><a href="#tool">Indexing Tool</a></li><li><a href="#how-it-works">How It Works</a></li><li><a href="#pricing">Pricing</a></li><li><a href="about.php">About</a></li></ul></div>
      <div class="footer-col"><h4>Legal</h4><ul><li><a href="privacy.php">Privacy Policy</a></li><li><a href="terms.php">Terms of Service</a></li><li><a href="refund.php">Refund Policy</a></li></ul></div>
      <div class="footer-col"><h4>Support</h4><ul><li><a href="about.php#contact">Contact Us</a></li><li><a href="report.php">Report a Problem</a></li><li><a href="#how-it-works">Documentation</a></li><li><a href="sitemap.xml">Sitemap</a></li><li><a href="rss.xml">RSS Feed</a></li></ul></div>
    </div>
    <div class="footer-bottom">
      <p>© <?= date('Y') ?> <?= h(SITE_NAME) ?>. All rights reserved.</p>
      <p>Powered by Google Indexing API</p>
    </div>
  </div>
</footer>

<!-- ════════════════ AUTH MODAL ════════════════ -->
<div class="modal-overlay" id="authModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Sign In to <?= h(SITE_NAME) ?></h3>
      <button class="modal-close" onclick="closeAuth()"><?= icon('close',18) ?></button>
    </div>
    <div class="modal-body">
      <?php if ($oauthUrl): ?>
      <a href="<?= h($oauthUrl) ?>">
        <button class="btn-google" style="width:100%;margin-bottom:16px">
          <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTgiIGhlaWdodD0iMTgiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGcgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj48cGF0aCBkPSJNMTcuNiA5LjJsLS4xLTEuOEg5djMuNGg0LjhDMTMuNiAxMiAxMyAxMyAxMiAxMy42djIuMmgzYTguOCA4LjggMCAwIDAgMi42LTYuNnoiIGZpbGw9IiM0Mjg1RjQiIGZpbGwtcnVsZT0ibm9uemVybyIvPjxwYXRoIGQ9Ik05IDE4YzIuNCAwIDQuNS0uOCA2LTIuMmwtMy0yLjJhNS40IDUuNCAwIDAgMS04LTIuOUgxVjEzYTkgOSAwIDAgMCA4IDV6IiBmaWxsPSIjMzRBODUzIiBmaWxsLXJ1bGU9Im5vbnplcm8iLz48cGF0aCBkPSJNNCAxMC43YTUuNCA1LjQgMCAwIDEgMC0zLjRWNUgxYTkgOSAwIDAgMCAwIDhsMy0yLjN6IiBmaWxsPSIjRkJCQzA1IiBmaWxsLXJ1bGU9Im5vbnplcm8iLz48cGF0aCBkPSJNOSAzLjZjMS4zIDAgMi41LjQgMy40IDEuM0wxNSAyLjNBOSA5IDAgMCAwIDEgNWwzIDIuNGE1LjQgNS40IDAgMCAxIDUtMy44eiIgZmlsbD0iI0VBNDMzNSIgZmlsbC1ydWxlPSJub256ZXJvIi8+PC9nPjwvc3ZnPg==" style="width:18px;height:18px" alt="G"> Continue with Google
        </button>
      </a>
      <div class="or-divider"><span>OR</span></div>
      <?php endif ?>
      <div class="tab-auth">
        <button class="active" onclick="authTab('login')">Sign In</button>
        <button onclick="authTab('register')">Register</button>
      </div>
      <div class="auth-err" id="authErr"></div>
      <!-- Login -->
      <div class="auth-panel active" id="authLogin">
        <div class="form-group"><label>Email address</label><input type="email" id="loginEmail" placeholder="you@example.com"></div>
        <div class="form-group"><label>Password</label><input type="password" id="loginPass" placeholder="••••••••"></div>
        <button class="btn-full btn-full-blue" onclick="doLogin()">Sign In</button>
      </div>
      <!-- Register -->
      <div class="auth-panel" id="authRegister">
        <div class="form-group"><label>Full name</label><input type="text" id="regName" placeholder="John Smith"></div>
        <div class="form-group"><label>Email address</label><input type="email" id="regEmail" placeholder="you@example.com"></div>
        <div class="form-group"><label>Password (min 6 chars)</label><input type="password" id="regPass" placeholder="••••••••"></div>
        <button class="btn-full btn-full-blue" onclick="doRegister()">Create Account</button>
      </div>
    </div>
  </div>
</div>

<!-- ════════════════ PLAN CHOICE MODAL (shown after login) ════════════════ -->
<div class="modal-overlay" id="planChoiceModal">
  <div class="modal plan-choice-modal">
    <div class="modal-header">
      <h3>Welcome to <?= h(SITE_NAME) ?></h3>
      <p>Choose your plan to get started — upgrade anytime, cancel anytime.</p>
      <button class="modal-close" onclick="closePlanChoice()" style="position:absolute;top:16px;right:16px"><?= icon('close',18) ?></button>
    </div>
    <div class="plan-choice-grid">
      <!-- Free -->
      <div class="pcc">
        <div class="pcc-icon" style="color:var(--slate)"><?= icon('user',28) ?></div>
        <div class="pcc-name">Free</div>
        <div class="pcc-price">$0<small>/mo</small></div>
        <div class="pcc-limit">100 URLs / day</div>
        <ul class="pcc-feats">
          <li>Manual + Sitemap input</li>
          <li>Submission history</li>
          <li>CSV extraction</li>
          <li>Key stored 30 days</li>
        </ul>
        <button class="pcc-btn pcc-btn-free" onclick="closePlanChoice()">Start Free</button>
      </div>
      <!-- Starter -->
      <div class="pcc">
        <div class="pcc-icon" style="color:var(--red)"><?= icon('rocket',28) ?></div>
        <div class="pcc-name">Starter</div>
        <div class="pcc-price">$7<small>/mo</small></div>
        <div class="pcc-limit">500 URLs / day</div>
        <ul class="pcc-feats">
          <li>Everything in Free</li>
          <li>5× more daily quota</li>
          <li>Priority processing</li>
          <li>Pay with crypto</li>
        </ul>
        <button class="pcc-btn pcc-btn-paid" onclick="closePlanChoice();openPayment('starter')">Get Starter</button>
      </div>
      <!-- Pro (popular) -->
      <div class="pcc popular">
        <div class="badge"><?= icon('star',10) ?> MOST POPULAR</div>
        <div class="pcc-icon" style="color:var(--red)"><?= icon('gem',28) ?></div>
        <div class="pcc-name">Pro</div>
        <div class="pcc-price">$10<small>/mo</small></div>
        <div class="pcc-limit">2,000 URLs / day</div>
        <ul class="pcc-feats">
          <li>Everything in Starter</li>
          <li>20× daily quota</li>
          <li>Sitemap batch import</li>
          <li>Advanced diagnostics</li>
        </ul>
        <button class="pcc-btn pcc-btn-pop" onclick="closePlanChoice();openPayment('pro')">Get Pro</button>
      </div>
      <!-- Business -->
      <div class="pcc">
        <div class="pcc-icon" style="color:var(--charcoal)"><?= icon('building',28) ?></div>
        <div class="pcc-name">Business</div>
        <div class="pcc-price">$25<small>/mo</small></div>
        <div class="pcc-limit">10,000 URLs / day</div>
        <ul class="pcc-feats">
          <li>Everything in Pro</li>
          <li>100× daily quota</li>
          <li>Direct technical support</li>
          <li>Multiple Service Account keys</li>
        </ul>
        <button class="pcc-btn pcc-btn-paid" onclick="closePlanChoice();openPayment('business')">Get Business</button>
      </div>
    </div>
    <div class="plan-choice-note">All plans include: CSRF protection · Key access restricted to your account · 30-day auto-delete · Crypto payments via NOWPayments</div>
  </div>
</div>

<!-- ════════════════ PAYMENT MODAL ════════════════ -->
<div class="modal-overlay" id="payModal">
  <div class="modal payment-modal">
    <div class="modal-header">
      <h3 id="payTitle">Upgrade to Pro</h3>
      <button class="modal-close" onclick="closePayment()"><?= icon('close',18) ?></button>
    </div>
    <div class="modal-body">
      <div id="payStep1">
        <p style="font-size:13px;color:var(--muted2);margin-bottom:16px">Select your preferred cryptocurrency:</p>
        <div class="currency-grid" id="currencyGrid">
          <button class="currency-btn selected" data-cur="usdttrc20" onclick="selCur(this)">USDT<br><small>TRC20</small></button>
          <button class="currency-btn" data-cur="usdterc20" onclick="selCur(this)">USDT<br><small>ERC20</small></button>
          <button class="currency-btn" data-cur="eth" onclick="selCur(this)">ETH</button>
          <button class="currency-btn" data-cur="btc" onclick="selCur(this)">BTC</button>
          <button class="currency-btn" data-cur="bnbbsc" onclick="selCur(this)">BNB</button>
          <button class="currency-btn" data-cur="sol" onclick="selCur(this)">SOL</button>
        </div>
        <button class="btn-full btn-full-blue" id="payNowBtn" onclick="createPayment()">
          <span id="payNowSpinner" style="display:none;width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin 1s linear infinite;display:inline-block;margin-right:8px;vertical-align:middle"></span>
          Generate Payment Address
        </button>
      </div>
      <div id="payStep2" style="display:none">
        <div class="pay-qr" id="payQR"></div>
        <div class="pay-addr-box">
          <div class="pay-label">Send exactly</div>
          <div class="pay-value" id="payAmount"></div>
          <div style="margin-top:8px;display:flex;justify-content:space-between;align-items:center">
            <div><div class="pay-label">To address</div><div class="pay-value" id="payAddr" style="font-size:11px"></div></div>
            <button class="btn-copy" onclick="copyAddr()">Copy</button>
          </div>
        </div>
        <div class="pay-timer" id="payTimer"></div>
        <div class="pay-status-bar pay-pending" id="payStatusBar"><?= icon('clock',14) ?> Waiting for payment confirmation…</div>
      </div>
    </div>
  </div>
</div>

<!-- ════════════════ TOAST ════════════════ -->
<div class="toast-wrap" id="toastWrap"></div>

<!-- ════════════════ META TAG / ROBOTS.TXT TOOL MODAL ════════════════ -->
<div class="modal-overlay" id="toolModal">
  <div class="modal" style="max-width:520px">
    <div class="modal-header">
      <h3 id="toolModalTitle">Meta Tag Generator</h3>
      <button class="modal-close" onclick="closeToolModal()"><?= icon('close',18) ?></button>
    </div>
    <div class="modal-body" id="toolModalBody"></div>
  </div>
</div>

<!-- ════════════════ MESSENGER-STYLE SUPPORT CHAT ════════════════ -->
<div class="chat-launcher" id="chatLauncher">
  <button class="support-bubble" onclick="toggleChat()" id="supportBtn">
    <?= icon('message',24) ?>
    <span class="support-bubble-badge">1</span>
  </button>
</div>
<div class="chat-panel" id="chatPanel">
  <div class="chat-header">
    <div class="chat-header-avatar"><?= icon('headset',20) ?></div>
    <div style="flex:1;min-width:0">
      <div class="chat-header-name"><?= h(SITE_NAME) ?> Support</div>
      <div class="chat-header-status"><span class="chat-dot"></span> Ticket-based support · replies within a few hours</div>
    </div>
    <button class="chat-close" onclick="toggleChat()"><?= icon('close',18) ?></button>
  </div>
  <div class="chat-body" id="chatBody">
    <div class="chat-msg bot">
      <div class="chat-bubble">Hi! I'm the automated assistant. I can point you to common fixes, or send your message straight to the support team as a ticket. What do you need help with?</div>
      <div class="chat-time">Just now</div>
    </div>
    <div class="chat-quick" id="chatQuick">
      <button onclick="chatQuickReply('why-failed')">Why did my submissions fail?</button>
      <button onclick="chatQuickReply('get-key')">How do I get a Service Account key?</button>
      <button onclick="chatQuickReply('billing')">I have a billing question</button>
      <button onclick="chatQuickReply('human')">Talk to a human</button>
    </div>
  </div>
  <form class="chat-input-row" id="chatForm" onsubmit="sendChatMessage(event)">
    <input type="text" id="chatInput" placeholder="Type a message…" autocomplete="off">
    <button type="submit" class="chat-send" aria-label="Send"><?= icon('send',18) ?></button>
  </form>
</div>

<!-- ════════════════ JAVASCRIPT ════════════════ -->
<script>
const CSRF = '<?= csrf_token() ?>';
const ICONS = {
  ok:   '<?= addslashes(icon("checkCircle",16)) ?>',
  err:  '<?= addslashes(icon("xCircle",16)) ?>',
  info: '<?= addslashes(icon("info",16)) ?>',
  warning: '<?= addslashes(icon("warning",16)) ?>'
};
let currentResults = [];
let paymentPlan = '';
let paymentOrderId = '';
let paymentInterval = null;
let paymentExpiry = null;

// ── Key upload ────────────────────────────────────────────────────────────────
const dz = document.getElementById('dropzone');
dz.addEventListener('dragover',e=>{e.preventDefault();dz.classList.add('drag-over')});
dz.addEventListener('dragleave',()=>dz.classList.remove('drag-over'));
dz.addEventListener('drop',e=>{e.preventDefault();dz.classList.remove('drag-over');const f=e.dataTransfer.files[0];if(f)uploadKey(f)});

function handleKeyUpload(inp){if(inp.files[0])uploadKey(inp.files[0])}
async function uploadKey(file){
  const fd=new FormData(); fd.append('action','upload_key'); fd.append('_csrf',CSRF); fd.append('sa_key',file);
  showToast('Validating key…','info');
  const r=await fetch('api.php',{method:'POST',body:fd}).then(x=>x.json()).catch(()=>({ok:false,error:'Network error'}));
  if(r.ok){document.getElementById('keyLoaded').classList.add('show');document.getElementById('keyEmail').textContent=r.email;
    dz.style.display='none';showToast('Key connected: '+r.email,'ok');}
  else showToast('Error: '+r.error,'err');
}
async function testKey(){
  showToast('Testing connection…','info');
  const r=await post({action:'test_key'});
  if(r.ok)showToast('✓ Access token obtained successfully','ok');
  else showToast('Error: '+r.error,'err');
}
async function deleteKey(){
  if(!confirm('Remove service account key?'))return;
  await post({action:'delete_key',_csrf:CSRF});
  document.getElementById('keyLoaded').classList.remove('show');
  dz.style.display='';document.getElementById('keyEmail').textContent='';
  showToast('Key removed','info');
}

// ── URL management ────────────────────────────────────────────────────────────
function switchTab(t){
  document.querySelectorAll('.url-tab').forEach(b=>b.classList.toggle('active',b.dataset.tab===t));
  document.querySelectorAll('.url-tab-panel').forEach(p=>p.classList.toggle('active',p.id==='tab-'+t));
}
function getUrls(){
  const ta=document.getElementById('urlArea').value;
  return [...new Set(ta.split('\n').map(s=>s.trim()).filter(s=>s.startsWith('http')))];
}
function countUrls(){
  const c=getUrls().length;
  document.getElementById('urlCount').textContent=c+' URL'+(c!==1?'s':'')+' ready';
}
function sortUrls(){const ta=document.getElementById('urlArea');ta.value=getUrls().sort().join('\n');countUrls()}
function dedupeUrls(){const ta=document.getElementById('urlArea');ta.value=[...new Set(getUrls())].join('\n');countUrls()}
function copyUrls(){navigator.clipboard.writeText(getUrls().join('\n'));showToast('Copied '+getUrls().length+' URLs','ok')}
function clearUrls(){document.getElementById('urlArea').value='';countUrls()}
function extractCSV(){
  const raw=document.getElementById('csvArea').value;
  const urls=[...raw.matchAll(/https?:\/\/[^\s,"'<>]+/g)].map(m=>m[0]);
  document.getElementById('urlArea').value=[...new Set(urls)].join('\n');
  switchTab('manual');countUrls();
  showToast('Extracted '+urls.length+' URLs from CSV','ok');
}
async function fetchSitemap(){
  const url=document.getElementById('sitemapUrl').value.trim();
  if(!url)return showToast('Enter a sitemap URL','err');
  const st=document.getElementById('sitemapStatus');
  st.textContent='Fetching sitemap…';st.style.color='var(--muted2)';
  const r=await post({action:'fetch_sitemap',url});
  if(r.ok){document.getElementById('urlArea').value=r.urls.join('\n');switchTab('manual');countUrls();
    st.textContent='✓ Fetched '+r.count+' URLs';st.style.color='var(--green)';showToast('Sitemap loaded: '+r.count+' URLs','ok');}
  else{st.textContent='✗ '+r.error;st.style.color='var(--red)';}
}

// ── Submit ────────────────────────────────────────────────────────────────────
const IS_LOGGED_IN = <?= !empty($_SESSION['uid']) ? 'true' : 'false' ?>;
async function submitUrls(){
  if(!IS_LOGGED_IN){openAuth();return;}
  const urls=getUrls();
  if(!urls.length)return showToast('Add at least one URL first','err');
  const btn=document.getElementById('submitBtn');
  btn.classList.add('loading');btn.disabled=true;
  document.getElementById('progressWrap').classList.add('show');
  document.getElementById('resultsWrap').classList.remove('show');
  document.getElementById('resultsBody').innerHTML='';
  currentResults=[];
  // Submit in batches of 50
  const batchSize=50; let done=0; let okC=0; let failC=0;
  const allResults=[];
  for(let i=0;i<urls.length;i+=batchSize){
    const batch=urls.slice(i,i+batchSize);
    updateProgress(Math.round(done/urls.length*100),'Submitting batch '+(Math.floor(i/batchSize)+1)+'…');
    const r=await post({action:'submit',urls:JSON.stringify(batch),_csrf:CSRF});
    if(!r.ok){showToast('Error: '+r.error,'err');break;}
    allResults.push(...r.results); okC+=r.ok_count; failC+=r.fail_count; done+=batch.length;
    if(r.skipped>0){showToast('Quota limit reached: '+r.skipped+' URLs skipped','err');break;}
  }
  updateProgress(100,'Done!');
  setTimeout(()=>document.getElementById('progressWrap').classList.remove('show'),1000);
  currentResults=allResults;
  showResults(allResults,okC,failC);
  btn.classList.remove('loading');btn.disabled=false;
  loadQuota();
}
function updateProgress(pct,txt){
  document.getElementById('progressBar').style.width=pct+'%';
  document.getElementById('progressTxt').textContent=txt;
}
const FAILURE_REASONS = [
  {test:/not a verified owner|verify.*owner|permission/i,
   title:'Service Account is not a verified owner',
   fix:'Open Google Search Console → your property → Settings → Users and permissions → Add user, and add your service account\'s email (the "client_email" field in your JSON key) as an Owner, not just a member. Ownership can take a few minutes to propagate.'},
  {test:/indexing api.*not enabled|api not enabled|has not been used/i,
   title:'Indexing API is not enabled on the Google Cloud project',
   fix:'Go to console.cloud.google.com → APIs & Services → Library → search "Web Search Indexing API" → click Enable. This must be enabled on the same project that issued your Service Account key.'},
  {test:/quota|rate limit|RESOURCE_EXHAUSTED/i,
   title:'Google\'s own daily quota was exceeded',
   fix:'The Indexing API has its own quota (Google sets a default of 200 requests/day per project, separate from your plan limit here). Request a quota increase in Google Cloud Console → IAM & Admin → Quotas, or wait until the quota resets (~24h).'},
  {test:/invalid.*url|malformed|INVALID_ARGUMENT/i,
   title:'The URL was rejected as invalid',
   fix:'Make sure each URL is a full absolute address (starting with https://) and belongs to a property you\'ve actually verified in Search Console.'},
  {test:/network|timeout|could not connect/i,
   title:'Network or timeout error reaching Google',
   fix:'This is usually temporary. Wait a minute and retry the failed URLs — they\'re still listed above so you can copy them back into the Manual tab.'}
];
function explainFailures(results){
  const seen=new Map();
  results.filter(r=>!r.ok).forEach(r=>{
    const match=FAILURE_REASONS.find(f=>f.test.test(r.msg||''));
    const key=match?match.title:'Other error: '+(r.msg||'Unknown');
    if(!seen.has(key))seen.set(key,match?match.fix:'Check the exact message above for this URL — it usually points to a Search Console or API Console setting that needs adjusting.');
  });
  return seen;
}
function showResults(results,ok,fail){
  const wrap=document.getElementById('resultsWrap');wrap.classList.add('show');
  document.getElementById('resultSummary').innerHTML=
    '<span style="color:var(--green)">✓ '+ok+' ok</span> / <span style="color:var(--red)">✗ '+fail+' failed</span>';
  const body=document.getElementById('resultsBody');
  body.innerHTML=results.map(r=>`<div class="result-row">
    <div class="result-icon ${r.ok?'ok':'fail'}">${r.ok?'✓':'✗'}</div>
    <div class="result-url" title="${r.url}">${r.url}</div>
    <div class="result-msg">${r.msg}</div>
  </div>`).join('');
  const help=document.getElementById('failHelp');
  if(fail>0){
    const reasons=explainFailures(results);
    let html='<h4>'+ICONS.warning+' Why these failed &amp; how to fix it</h4><ul>';
    reasons.forEach((fix,title)=>{html+=`<li><b>${title}.</b> ${fix}</li>`});
    html+='</ul>';
    help.innerHTML=html;help.classList.add('show');
  } else { help.classList.remove('show'); help.innerHTML=''; }
}
function exportCSV(){
  if(!currentResults.length)return showToast('No results to export yet','err');
  const csv='URL,Status,Message\n'+currentResults.map(r=>
    `"${r.url}","${r.ok?'OK':'FAIL'}","${(r.msg||'').replace(/"/g,"'")}"` ).join('\n');
  downloadFile('indexing-results-'+Date.now()+'.csv',csv,'text/csv');
}
function downloadFile(name,content,type){
  const blob=new Blob([content],{type});
  const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=name;
  document.body.appendChild(a);a.click();a.remove();
}
function xmlEscape(s){return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;')}

// ── Sitemap / RSS export (real, generated from the URLs currently in the tool) ─
function exportSitemap(){
  const urls=currentResults.length?currentResults.map(r=>r.url):getUrls();
  if(!urls.length)return showToast('Add or submit some URLs first','err');
  const now=new Date().toISOString().slice(0,10);
  const body=urls.map(u=>`  <url>\n    <loc>${xmlEscape(u)}</loc>\n    <lastmod>${now}</lastmod>\n  </url>`).join('\n');
  const xml=`<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${body}\n</urlset>\n`;
  downloadFile('sitemap.xml',xml,'application/xml');
  showToast('sitemap.xml generated with '+urls.length+' URLs','ok');
}
function exportRSS(){
  const urls=currentResults.length?currentResults.map(r=>r.url):getUrls();
  if(!urls.length)return showToast('Add or submit some URLs first','err');
  const now=new Date().toUTCString();
  const items=urls.map(u=>`    <item>\n      <title>${xmlEscape(u)}</title>\n      <link>${xmlEscape(u)}</link>\n      <guid>${xmlEscape(u)}</guid>\n      <pubDate>${now}</pubDate>\n    </item>`).join('\n');
  const rss=`<?xml version="1.0" encoding="UTF-8"?>\n<rss version="2.0"><channel>\n    <title><?= h(SITE_NAME) ?> — Submitted URLs</title>\n    <link><?= h(site_url()) ?></link>\n    <description>URLs submitted for indexing</description>\n${items}\n  </channel></rss>\n`;
  downloadFile('feed.xml',rss,'application/rss+xml');
  showToast('RSS feed generated with '+urls.length+' items','ok');
}

// ── Meta tag / robots.txt generators (client-side, no fake data) ───────────────
function openMetaTool(){
  document.getElementById('toolModalTitle').textContent='Meta Tag Generator';
  document.getElementById('toolModalBody').innerHTML=`
    <div class="form-group"><label>Page Title (50-60 characters)</label><input type="text" id="mtTitle" maxlength="70" oninput="renderMeta()" placeholder="Your Page Title"></div>
    <div class="form-group"><label>Meta Description (140-160 characters)</label><input type="text" id="mtDesc" maxlength="170" oninput="renderMeta()" placeholder="A concise, compelling description of the page"></div>
    <div class="form-group"><label>Canonical URL</label><input type="text" id="mtUrl" oninput="renderMeta()" placeholder="https://yoursite.com/page"></div>
    <div class="form-group"><label>Generated tags</label><textarea class="url-textarea" id="mtOut" style="min-height:140px;font-size:12px" readonly></textarea></div>
    <button class="btn-full btn-full-blue" onclick="navigator.clipboard.writeText(document.getElementById('mtOut').value);showToast('Copied to clipboard','ok')"><?= icon('copy',14) ?> Copy</button>`;
  document.getElementById('toolModal').classList.add('show');
}
function renderMeta(){
  const t=document.getElementById('mtTitle').value||'Your Page Title';
  const d=document.getElementById('mtDesc').value||'Your page description';
  const u=document.getElementById('mtUrl').value||'https://yoursite.com/page';
  document.getElementById('mtOut').value=
`<title>${t}</title>
<meta name="description" content="${d}">
<link rel="canonical" href="${u}">
<meta property="og:title" content="${t}">
<meta property="og:description" content="${d}">
<meta property="og:url" content="${u}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="${t}">
<meta name="twitter:description" content="${d}">`;
}
function openRobotsTool(){
  document.getElementById('toolModalTitle').textContent='Robots.txt Generator';
  document.getElementById('toolModalBody').innerHTML=`
    <div class="form-group"><label>Sitemap URL</label><input type="text" id="rtSitemap" oninput="renderRobots()" placeholder="https://yoursite.com/sitemap.xml"></div>
    <div class="form-group"><label>Disallowed paths (one per line)</label><textarea class="url-textarea" id="rtDisallow" style="min-height:80px" oninput="renderRobots()" placeholder="/admin/&#10;/private/"></textarea></div>
    <div class="form-group"><label>Generated robots.txt</label><textarea class="url-textarea" id="rtOut" style="min-height:140px;font-size:12px" readonly></textarea></div>
    <button class="btn-full btn-full-blue" onclick="downloadFile('robots.txt',document.getElementById('rtOut').value,'text/plain');showToast('robots.txt downloaded','ok')"><?= icon('download',14) ?> Download</button>`;
  document.getElementById('toolModal').classList.add('show');
  renderRobots();
}
function renderRobots(){
  const sm=document.getElementById('rtSitemap').value.trim();
  const dis=document.getElementById('rtDisallow').value.split('\n').map(s=>s.trim()).filter(Boolean);
  let out='User-agent: *\n';
  out+=dis.length?dis.map(d=>'Disallow: '+d).join('\n')+'\n':'Disallow:\n';
  if(sm)out+='\nSitemap: '+sm+'\n';
  document.getElementById('rtOut').value=out;
}
function closeToolModal(){document.getElementById('toolModal').classList.remove('show')}

// ── Quota ────────────────────────────────────────────────────────────────────
async function loadQuota(){
  const r=await post({action:'quota'});
  if(!r.ok)return;
  const q=r.quota;
  document.getElementById('qUsed').textContent=q.used;
  document.getElementById('qLimit').textContent=q.limit;
  document.getElementById('qLeft').textContent=q.remaining;
  document.getElementById('qdUsed').textContent=q.used;
  document.getElementById('qdLeft').textContent=q.remaining;
  const pct=q.limit>0?Math.min(100,Math.round(q.used/q.limit*100)):0;
  document.getElementById('quotaPct').textContent=pct+'%';
  const r2=<?= $dashR ?>;const c2=<?= $dashC ?>;
  const off=c2*(1-pct/100);
  document.getElementById('quotaRing').style.strokeDashoffset=off;
}

// ── History ──────────────────────────────────────────────────────────────────
async function loadHistory(){
  const r=await post({action:'history'});
  const body=document.getElementById('histBody');
  if(!r.ok||!r.logs.length){body.innerHTML='<tr><td colspan="3" style="text-align:center;color:var(--muted);padding:24px">No submissions yet</td></tr>';return;}
  body.innerHTML=r.logs.slice(0,50).map(l=>`<tr>
    <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px" title="${l.url}">${l.url}</td>
    <td><span class="chip ${l.ok?'chip-ok':'chip-fail'}">${l.ok?'OK':'FAIL'}</span></td>
    <td style="color:var(--muted);font-size:11px;white-space:nowrap">${l.at||''}</td>
  </tr>`).join('');
}

// ── Auth ──────────────────────────────────────────────────────────────────────
function openAuth(){document.getElementById('authModal').classList.add('show')}
function closeAuth(){document.getElementById('authModal').classList.remove('show')}
function authTab(t){
  document.querySelectorAll('.tab-auth button').forEach((b,i)=>b.classList.toggle('active',(t==='login'?i===0:i===1)));
  document.getElementById('authLogin').classList.toggle('active',t==='login');
  document.getElementById('authRegister').classList.toggle('active',t==='register');
  document.getElementById('authErr').classList.remove('show');
}
async function doLogin(){
  const err=document.getElementById('authErr');err.classList.remove('show');
  const r=await post({action:'login_email',_csrf:CSRF,email:document.getElementById('loginEmail').value,password:document.getElementById('loginPass').value});
  if(r.ok){closeAuth();if(r.plan==='free'||!r.plan)openPlanChoice();else location.reload();}else{err.textContent=r.error;err.classList.add('show');}
}
async function doRegister(){
  const err=document.getElementById('authErr');err.classList.remove('show');
  const r=await post({action:'register',_csrf:CSRF,name:document.getElementById('regName').value,email:document.getElementById('regEmail').value,password:document.getElementById('regPass').value});
  if(r.ok){closeAuth();openPlanChoice();}else{err.textContent=r.error;err.classList.add('show');}
}
function openPlanChoice(){document.getElementById('planChoiceModal').classList.add('show')}
function closePlanChoice(){document.getElementById('planChoiceModal').classList.remove('show');location.reload();}
async function doLogout(){await post({action:'logout'});location.reload();}

// ── Payment ──────────────────────────────────────────────────────────────────
const PLAN_NAMES={starter:'Starter ($7/mo)',pro:'Pro ($10/mo)',business:'Business ($25/mo)'};
function openPayment(plan){
  paymentPlan=plan;
  document.getElementById('payTitle').textContent='Upgrade to '+PLAN_NAMES[plan];
  document.getElementById('payStep1').style.display='';
  document.getElementById('payStep2').style.display='none';
  document.getElementById('payModal').classList.add('show');
}
function closePayment(){
  document.getElementById('payModal').classList.remove('show');
  if(paymentInterval)clearInterval(paymentInterval);
}
function selCur(btn){document.querySelectorAll('.currency-btn').forEach(b=>b.classList.remove('selected'));btn.classList.add('selected')}
async function createPayment(){
  const cur=document.querySelector('.currency-btn.selected')?.dataset.cur||'usdttrc20';
  document.getElementById('payNowBtn').disabled=true;
  document.getElementById('payNowSpinner').style.display='inline-block';
  const r=await post({action:'create_payment',_csrf:CSRF,plan:paymentPlan,currency:cur});
  document.getElementById('payNowBtn').disabled=false;
  document.getElementById('payNowSpinner').style.display='none';
  if(!r.ok){showToast('Payment error: '+r.error,'err');return;}
  paymentOrderId=r.order_id;
  const d=r.data;
  document.getElementById('payAmount').textContent=(d.pay_amount||'?')+' '+(d.pay_currency||'').toUpperCase();
  document.getElementById('payAddr').textContent=d.pay_address||'';
  document.getElementById('payQR').innerHTML=`<img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=${encodeURIComponent(d.pay_address||'')}" alt="QR">`;
  paymentExpiry=Date.now()+20*60*1000;
  document.getElementById('payStep1').style.display='none';
  document.getElementById('payStep2').style.display='block';
  startPayTimer();pollPayment();
}
function startPayTimer(){
  const el=document.getElementById('payTimer');
  const tick=()=>{const left=Math.max(0,paymentExpiry-Date.now());const m=Math.floor(left/60000);const s=Math.floor((left%60000)/1000);el.textContent=`⏱ Expires in ${m}:${s.toString().padStart(2,'0')}`;};
  tick();paymentInterval=setInterval(tick,1000);
}
function pollPayment(){
  const iv=setInterval(async()=>{
    const r=await fetch('api.php?action=check_payment&order_id='+paymentOrderId).then(x=>x.json()).catch(()=>({}));
    if(r.status==='finished'||r.status==='confirmed'){
      clearInterval(iv);if(paymentInterval)clearInterval(paymentInterval);
      document.getElementById('payStatusBar').className='pay-status-bar pay-success';
      document.getElementById('payStatusBar').textContent='✓ Payment confirmed! Your plan is now active.';
      setTimeout(()=>{closePayment();location.reload();},2000);
    }
  },8000);
}
function copyAddr(){navigator.clipboard.writeText(document.getElementById('payAddr').textContent);showToast('Address copied!','ok')}

// ── Drawer ────────────────────────────────────────────────────────────────────
function toggleDrawer(){
  const b=document.getElementById('drawerBody');const a=document.getElementById('drawerArrow');
  b.classList.toggle('open');a.style.transform=b.classList.contains('open')?'rotate(180deg)':'';
}

// ── Mobile nav ───────────────────────────────────────────────────────────────
function toggleMobileNav(){
  document.getElementById('mainNav').classList.toggle('mobile-open');
}
document.addEventListener('click',e=>{
  const nav=document.getElementById('mainNav'),btn=document.getElementById('hamburgerBtn');
  if(nav.classList.contains('mobile-open')&&!nav.contains(e.target)&&!btn.contains(e.target))nav.classList.remove('mobile-open');
});

// ── Support chat (messenger-style widget backed by real tickets) ───────────────
const CHAT_REPLIES = {
  'why-failed': "Common causes: (1) the service account isn't added as an Owner in Search Console, (2) the Indexing API isn't enabled on your Google Cloud project, or (3) Google's own daily quota (separate from your plan) was hit. Scroll to the red box under your results after a submission — it lists the exact reason and fix for each failure.",
  'get-key': 'Go to console.cloud.google.com → IAM & Admin → Service Accounts → Create Service Account → Keys → Add Key → JSON. Then enable the "Web Search Indexing API" and add the service account email as an Owner in Search Console → Settings → Users and permissions.',
  'billing': 'For billing or payment issues, please describe what happened (order ID if you have one) and send it — our team reviews tickets and replies by email within a few hours.',
  'human': "Type your message below and hit send — it's saved as a support ticket our team reviews directly. We don't have a live agent watching this window right now, but we do reply to every ticket."
};
function toggleChat(){
  document.getElementById('chatPanel').classList.toggle('show');
  const badge=document.querySelector('.support-bubble-badge');if(badge)badge.style.display='none';
}
function chatTimeNow(){return new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}
function addChatMsg(text,who){
  const body=document.getElementById('chatBody');
  const div=document.createElement('div');div.className='chat-msg '+who;
  div.innerHTML=`<div class="chat-bubble"></div><div class="chat-time">${chatTimeNow()}</div>`;
  div.querySelector('.chat-bubble').textContent=text;
  body.appendChild(div);body.scrollTop=body.scrollHeight;
}
function chatQuickReply(key){
  const labels={'why-failed':'Why did my submissions fail?','get-key':'How do I get a Service Account key?','billing':'I have a billing question','human':'Talk to a human'};
  addChatMsg(labels[key],'user');
  document.getElementById('chatQuick').style.display='none';
  setTimeout(()=>addChatMsg(CHAT_REPLIES[key],'bot'),400);
}
function addTypingIndicator(){
  const body=document.getElementById('chatBody');
  const d=document.createElement('div');d.className='chat-msg bot';d.id='chatTyping';
  d.innerHTML='<div class="chat-bubble" style="color:var(--muted);font-style:italic;opacity:.8">Typing…</div>';
  body.appendChild(d);body.scrollTop=body.scrollHeight;
}
function removeTypingIndicator(){const t=document.getElementById('chatTyping');if(t)t.remove();}
async function sendChatMessage(e){
  e.preventDefault();
  const inp=document.getElementById('chatInput');
  const text=inp.value.trim();if(!text)return;
  addChatMsg(text,'user');inp.value='';
  document.getElementById('chatQuick').style.display='none';
  addTypingIndicator();
  const r=await post({action:'ai_chat',_csrf:CSRF,message:text});
  removeTypingIndicator();
  if(r.ok&&r.reply){
    addChatMsg(r.reply,'bot');
    post({action:'support_message',_csrf:CSRF,message:text});
  } else {
    const t=await post({action:'support_message',_csrf:CSRF,message:text});
    addChatMsg(t.ok?'Got it — sent to our support team as a ticket. We reply by email within a few hours.':'Could not send right now, please try again.','bot');
  }
}

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg,type='info'){
  const el=document.createElement('div');el.className='toast '+type;
  el.innerHTML=(ICONS[type]||ICONS.info)+'<span>'+msg+'</span>';
  document.getElementById('toastWrap').appendChild(el);
  setTimeout(()=>{el.style.opacity='0';el.style.transition='opacity .4s';setTimeout(()=>el.remove(),400)},3500);
}

// ── HTTP helper ───────────────────────────────────────────────────────────────
async function post(data){
  const fd=new FormData();
  for(const[k,v]of Object.entries(data))fd.append(k,v);
  if(!fd.has('_csrf'))fd.append('_csrf',CSRF);
  return fetch('api.php',{method:'POST',body:fd}).then(x=>x.json()).catch(e=>({ok:false,error:e.message}));
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded',()=>{loadHistory();});
document.getElementById('authModal').addEventListener('click',e=>{if(e.target===e.currentTarget)closeAuth()});
document.getElementById('payModal').addEventListener('click',e=>{if(e.target===e.currentTarget)closePayment()});
document.getElementById('planChoiceModal').addEventListener('click',e=>{if(e.target===e.currentTarget)closePlanChoice()});
document.getElementById('toolModal').addEventListener('click',e=>{if(e.target===e.currentTarget)closeToolModal()});
</script>

</body>
</html>
